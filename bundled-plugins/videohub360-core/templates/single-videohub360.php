<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
get_header();

// Helper function to validate if an ad URL is properly configured
function videohub360_has_valid_ad_url($url) {
    return !empty($url) && is_string($url) && trim($url) !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Helper function to determine which ad types should be active for the current video
function videohub360_get_active_ads($post_id) {
    $active_ads = array();
    
    // Check preroll ad
    $preroll_url = get_post_meta($post_id, 'ad_video_url', true);
    if (empty($preroll_url)) {
        $preroll_url = get_option('videohub360_global_ad_url', '');
    }
    if (videohub360_has_valid_ad_url($preroll_url)) {
        $active_ads['preroll'] = $preroll_url;
    }
    
    // Check midroll ad
    $midroll_url = get_post_meta($post_id, 'midroll_ad_video_url', true);
    if (empty($midroll_url)) {
        $midroll_url = get_option('videohub360_global_midroll_ad_url', '');
    }
    if (videohub360_has_valid_ad_url($midroll_url)) {
        $active_ads['midroll'] = $midroll_url;
        
        // Also get timing for midroll ads
        $midroll_timing = get_post_meta($post_id, 'midroll_ad_timing', true);
        if (empty($midroll_timing)) {
            $midroll_timing = get_option('videohub360_global_midroll_timing', '30,60,120');
        }
        $active_ads['midroll_timing'] = $midroll_timing;
    }
    
    // Check postroll ad
    $postroll_url = get_post_meta($post_id, 'postroll_ad_video_url', true);
    $postroll_enabled_meta = get_post_meta($post_id, 'postroll_ad_enabled', true);
    
    // Determine which URL to use: per-video or global
    $using_per_video_url = !empty($postroll_url);
    if (!$using_per_video_url) {
        // No per-video URL, fall back to global
        $postroll_url = get_option('videohub360_global_postroll_ad_url', '');
    }
    
    if (videohub360_has_valid_ad_url($postroll_url)) {
        // Determine enabled status
        if ($using_per_video_url) {
            // Has per-video URL - check per-video enabled setting (with global fallback)
            if (empty($postroll_enabled_meta)) {
                // No explicit per-video choice, use global enabled setting
                $postroll_enabled = get_option('videohub360_global_postroll_enabled', 0) ? 'yes' : 'no';
            } else {
                // Use explicit per-video choice
                $postroll_enabled = $postroll_enabled_meta;
            }
        } else {
            // Using global URL - use global enabled setting
            // (per-video enabled setting doesn't apply when there's no per-video URL)
            $postroll_enabled = get_option('videohub360_global_postroll_enabled', 0) ? 'yes' : 'no';
        }
        
        // Only add postroll if it's enabled
        if ($postroll_enabled === 'yes') {
            $active_ads['postroll'] = $postroll_url;
            $active_ads['postroll_enabled'] = $postroll_enabled;
        }
    }
    
    return $active_ads;
}

// Helper function to get ad click-through URLs with proper hierarchy
function videohub360_get_ad_click_urls($post_id) {
    $click_urls = array();
    
    // Get global settings
    $global_click_url = get_option('vh360_global_ad_click_url', '');
    $click_tracking_enabled = get_option('vh360_ad_click_tracking_enabled', 0);
    $click_new_tab = get_option('vh360_ad_click_new_tab', 1);
    
    // Get per-video click URLs
    $preroll_click_url = get_post_meta($post_id, '_vh360_ad_click_url', true);
    $midroll_click_url = get_post_meta($post_id, '_vh360_midroll_ad_click_url', true);
    $postroll_click_url = get_post_meta($post_id, '_vh360_postroll_ad_click_url', true);
    
    // Determine effective click URLs using hierarchy:
    // 1. Per-video URL (if set)
    // 2. Global URL (if set)
    // 3. Empty (non-clickable)
    
    // Preroll click URL
    $click_urls['preroll'] = !empty($preroll_click_url) ? $preroll_click_url : $global_click_url;
    
    // Midroll click URL (falls back to preroll, then global)
    if (!empty($midroll_click_url)) {
        $click_urls['midroll'] = $midroll_click_url;
    } elseif (!empty($preroll_click_url)) {
        $click_urls['midroll'] = $preroll_click_url;
    } else {
        $click_urls['midroll'] = $global_click_url;
    }
    
    // Postroll click URL (falls back to preroll, then global)
    if (!empty($postroll_click_url)) {
        $click_urls['postroll'] = $postroll_click_url;
    } elseif (!empty($preroll_click_url)) {
        $click_urls['postroll'] = $preroll_click_url;
    } else {
        $click_urls['postroll'] = $global_click_url;
    }
    
    // Add global settings
    $click_urls['tracking_enabled'] = $click_tracking_enabled;
    $click_urls['new_tab'] = $click_new_tab;
    
    return $click_urls;
}

if ( have_posts() ) : while ( have_posts() ) : the_post();

    // Get active ads using helper function
    $active_ads = videohub360_get_active_ads(get_the_ID());
    $has_any_ads = !empty($active_ads);
    
    // Get ad click-through URLs
    $ad_click_urls = videohub360_get_ad_click_urls(get_the_ID());
    
    // Extract individual ad URLs for backward compatibility (if needed)
    $ad_video_url = isset($active_ads['preroll']) ? $active_ads['preroll'] : '';
    $midroll_ad_url = isset($active_ads['midroll']) ? $active_ads['midroll'] : '';
    $midroll_timing = isset($active_ads['midroll_timing']) ? $active_ads['midroll_timing'] : '';
    $postroll_ad_url = isset($active_ads['postroll']) ? $active_ads['postroll'] : '';
    $postroll_enabled = isset($active_ads['postroll_enabled']) ? $active_ads['postroll_enabled'] : 'no';

    // Regular video fields
    $video_url = get_post_meta( get_the_ID(), 'video_url', true );
    $views = get_post_meta( get_the_ID(), '_videohub360_post_views_count', true );
    $views = $views ? $views : 0;
    $views_display = number_format( $views );
    $custom_html = get_post_meta( get_the_ID(), 'videohub360_custom_html', true );
    $poster = get_the_post_thumbnail_url( get_the_ID(), 'large' );
    $permalink = get_permalink();
    $title = get_the_title();
    $published_date = get_the_date();

    // Get sidebar configuration to determine layout
    $sidebar_config = get_post_meta(get_the_ID(), '_vh360_sidebar_config', true);
    if (empty($sidebar_config)) {
        $sidebar_config = array();
    }
    $sidebar_config = wp_parse_args($sidebar_config, array(
        'video_layout' => 'sidebar'
    ));
    $video_layout = $sidebar_config['video_layout'];

    // Livestream fields
    $livestream_fields = [
        'is_live' => get_post_meta(get_the_ID(), '_vh360_is_live', true) ?: 'no',
        'type' => get_post_meta(get_the_ID(), '_vh360_type', true) ?: 'embed',
        'embed_code' => get_post_meta(get_the_ID(), '_vh360_embed_code', true),
        'stream_url' => get_post_meta(get_the_ID(), '_vh360_stream_url', true),
        'api_url' => get_post_meta(get_the_ID(), '_vh360_api_url', true),
        'poster' => get_post_meta(get_the_ID(), '_vh360_poster', true),
        'viewer_count' => get_post_meta(get_the_ID(), '_vh360_viewer_count', true) ?: 'no',
        'live_badge' => get_post_meta(get_the_ID(), '_vh360_live_badge', true) ?: 'yes',
        'badge_text' => get_post_meta(get_the_ID(), '_vh360_badge_text', true) ?: 'LIVE',
        'badge_color' => get_post_meta(get_the_ID(), '_vh360_badge_color', true) ?: '#e53935',
        'live_start_time' => get_post_meta(get_the_ID(), '_vh360_live_start_time', true),
        'offline_message' => get_post_meta(get_the_ID(), '_vh360_offline_message', true),
        'stream_stopped' => get_post_meta(get_the_ID(), '_vh360_stream_stopped', true) ?: 'no',
        // Agora.io fields
        'agora_channel_name' => get_post_meta(get_the_ID(), '_vh360_agora_channel_name', true),
        'agora_mode' => get_post_meta(get_the_ID(), '_vh360_agora_mode', true) ?: 'interactive',
        'agora_everyone_is_host' => get_post_meta(get_the_ID(), '_vh360_agora_everyone_is_host', true) ?: 'no',
        'host_passcode' => get_post_meta(get_the_ID(), '_vh360_host_passcode', true) ?: '',
    ];

    if ($livestream_fields['is_live'] === 'yes' && !empty($livestream_fields['poster'])) {
        $poster = $livestream_fields['poster'];
    }

    $videohub360_categories = get_the_terms(get_the_ID(), 'videohub360_category');
    $videohub360_series = get_the_terms(get_the_ID(), 'videohub360_series');
    $videohub360_locations = get_the_terms(get_the_ID(), 'videohub360_location');

    // Determine if chat should be enabled - for all livestreams when chat is enabled
    $is_live = get_post_meta(get_the_ID(), '_vh360_is_live', true);
    $chat_enabled = false;
    $chat_placement = 'inline'; // Default placement
    
    if ($is_live === 'yes') {
        $per_video_chat = get_post_meta(get_the_ID(), '_vh360_chat_enabled', true);
        if ($per_video_chat === 'yes') {
            $chat_enabled = true;
        } elseif ($per_video_chat === 'no') {
            $chat_enabled = false;
        } else {
            $chat_enabled = get_option('videohub360_chat_enabled', 1);
        }
        
        // Determine chat placement (per-video override or global)
        $per_video_placement = get_post_meta(get_the_ID(), '_vh360_chat_placement', true);
        if (!empty($per_video_placement)) {
            $chat_placement = $per_video_placement;
        } else {
            $chat_placement = get_option('videohub360_chat_placement', 'inline');
        }
        
        // If placement is 'off', disable chat
        if ($chat_placement === 'off') {
            $chat_enabled = false;
        }
    } else {
        $chat_enabled = false;
    }

    $current_user = wp_get_current_user();
    $is_user_logged_in = is_user_logged_in();
    $user_avatar = $is_user_logged_in ? get_avatar($current_user->ID, 24) : '';
    $user_display_name = $is_user_logged_in ? $current_user->display_name : '';
    $user_login_url = function_exists('vh360_get_login_page_url_with_redirect')
        ? vh360_get_login_page_url_with_redirect(get_permalink())
        : wp_login_url(get_permalink());
    $user_logout_url = $is_user_logged_in ? wp_logout_url(get_permalink()) : '';
    
    // Define moderation capabilities for the current user
    $can_moderate = (
        current_user_can('manage_options')
        || current_user_can('moderate_comments')
        || ((int) get_post_field('post_author', get_the_ID()) === (int) get_current_user_id())
        || current_user_can('edit_post', get_the_ID())
    );
?>

    <div class="videohub360-main-wrapper <?php echo ($video_layout === 'full-width') ? 'videohub360-full-width' : 'videohub360-sidebar-layout'; ?>">
        <div class="videohub360-content-area">
            <?php
            // Check membership requirement before rendering video
            $required_plan = function_exists('vh360_post_requires_membership') ? vh360_post_requires_membership(get_the_ID()) : false;
            $user_has_access = true;
            
            if ($required_plan) {
                $current_user_id = get_current_user_id();
                
                if (!$current_user_id) {
                    // Not logged in - always deny access
                    // Logged-out users should see login/upgrade gate, not content
                    $user_has_access = false;
                } else {
                    // Check if user has the required plan
                    if ($required_plan === 'any') {
                        $user_has_access = function_exists('vh360_user_has_active_membership') ? vh360_user_has_active_membership($current_user_id) : false;
                    } else {
                        $user_has_access = function_exists('vh360_user_has_membership_plan') ? vh360_user_has_membership_plan($current_user_id, $required_plan) : false;
                    }
                }
            }
            
            if (!$user_has_access) {
                // Render membership gate instead of video
                if (class_exists('VH360_Membership_Frontend')) {
                    $frontend = VH360_Membership_Frontend::get_instance();
                    $reflection = new ReflectionClass($frontend);
                    
                    if (!get_current_user_id()) {
                        $method = $reflection->getMethod('render_login_required_notice');
                        $method->setAccessible(true);
                        echo $method->invoke($frontend);
                    } else {
                        $method = $reflection->getMethod('render_upgrade_required_notice');
                        $method->setAccessible(true);
                        echo $method->invoke($frontend, $required_plan);
                    }
                }
            } else {
                // User has access, render video
            ?>
            <?php if ($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] !== 'yes'): ?>
                <div class="videohub360-video-player" id="videohub360-livestream-player-root">
                    <?php echo videohub360_render_livestream($livestream_fields, $chat_enabled, $chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url); ?>
                </div>
            <?php elseif ($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] === 'yes'): ?>
                <div class="videohub360-video-player">
                    <div class="vh360-offline-wrapper">
                        <?php if (!empty($livestream_fields['offline_message'])) : ?>
                            <div class="vh360-offline-message">
                                <?php echo wp_kses_post($livestream_fields['offline_message']); ?>
                            </div>
                        <?php else : ?>
                            <div class="vh360-offline-message">
                                <?php vh360_the_offline_message(get_the_ID(), 'stream_ended'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="videohub360-video-player" id="videohub360-video-player-root">
                    <div class="videohub360-static-poster-wrap" id="videohub360-static-poster-wrap">
                        <?php if ($poster): ?>
                            <img src="<?php echo esc_url($poster); ?>" alt="Video Poster" class="videohub360-static-poster-img" />
                        <?php else: ?>
                            <div class="videohub360-static-poster-img vh360-no-poster"></div>
                        <?php endif; ?>
                        <button class="videohub360-static-play-btn" id="videohub360-static-play-btn" aria-label="Play video">
                            <svg viewBox="0 0 72 72" aria-hidden="true">
                                <polygon points="28,20 54,36 28,52"/>
                            </svg>
                        </button>
                    </div>
                    <?php if ($has_any_ads && isset($active_ads['preroll'])): ?>
                    <div id="videohub360-ad-container" class="videohub360-hide <?php echo !empty($ad_click_urls['preroll']) ? 'videohub360-ad-clickable' : ''; ?>" 
                         data-ad-type="preroll" 
                         data-ad-url="<?php echo esc_attr($ad_click_urls['preroll']); ?>" 
                         data-ad-new-tab="<?php echo esc_attr($ad_click_urls['new_tab']); ?>"
                         data-tracking-enabled="<?php echo esc_attr($ad_click_urls['tracking_enabled']); ?>"
                         data-post-id="<?php echo esc_attr(get_the_ID()); ?>">
                        <div class="videohub360-ad-label" id="videohub360-ad-label">Advertisement</div>
                        <span id="videohub360-ad-skip-msg"></span>
                        <button class="videohub360-ad-skip-btn" id="videohub360-ad-skip-btn" type="button">Skip Ad</button>
                        <?php if (!empty($ad_click_urls['preroll'])): ?>
                        <div class="videohub360-ad-click-overlay" tabindex="0" role="button" aria-label="Click to visit advertiser">
                            <div class="videohub360-ad-click-indicator">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 6px;">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 12H7V7h2v5zm0-6H7V4h2v2z"/>
                                </svg>
                                Learn More
                            </div>
                        </div>
                        <?php endif; ?>
                        <video id="videohub360-ad-video" width="100%" height="auto" controls playsinline poster="<?php echo esc_url($poster); ?>">
                            <source src="<?php echo esc_url($ad_video_url); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <?php endif; ?>
                    <div id="videohub360-main-container" class="videohub360-hide">
                        <?php if ($custom_html): ?>
                            <div class="videohub360-custom-embed-container">
                                <?php 
                                // Output custom HTML with proper sanitization
                                // For users with unfiltered_html capability, output as-is (already saved securely)
                                // For others, use comprehensive whitelist that allows common embed codes
                                if (current_user_can('unfiltered_html')) {
                                    echo $custom_html;
                                } else {
                                    // Allow common embed elements (iframes, scripts, etc.) for video embeds
                                    $allowed_embed_html = array(
                                        'iframe' => array(
                                            'src' => true,
                                            'width' => true,
                                            'height' => true,
                                            'frameborder' => true,
                                            'allowfullscreen' => true,
                                            'allow' => true,
                                            'title' => true,
                                            'referrerpolicy' => true,
                                            'loading' => true,
                                            'style' => true,
                                            'class' => true,
                                            'id' => true,
                                            'name' => true,
                                            'scrolling' => true,
                                            'sandbox' => true,
                                        ),
                                        'script' => array(
                                            'src' => true,
                                            'type' => true,
                                            'async' => true,
                                            'defer' => true,
                                        ),
                                        'div' => array(
                                            'class' => true,
                                            'id' => true,
                                            'style' => true,
                                            'data-*' => true,
                                        ),
                                        'blockquote' => array(
                                            'class' => true,
                                            'cite' => true,
                                            'data-*' => true,
                                        ),
                                        'a' => array(
                                            'href' => true,
                                            'title' => true,
                                            'class' => true,
                                            'target' => true,
                                        ),
                                    );
                                    echo wp_kses($custom_html, $allowed_embed_html);
                                }
                                ?>
                            </div>
                        <?php else: ?>
                            <video id="videohub360-main-video" width="100%" height="auto" controls playsinline poster="<?php echo esc_url($poster); ?>">
                                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Hidden ad data for JavaScript - only for regular videos (not custom HTML) and only when ads exist -->
                <?php if (!$custom_html && $has_any_ads): ?>
                <div style="display: none;">
                    <?php if (isset($active_ads['midroll'])): ?>
                    <span id="videohub360-midroll-ad-source"><?php echo esc_url($midroll_ad_url); ?></span>
                    <span id="videohub360-midroll-timing"><?php echo esc_attr($midroll_timing); ?></span>
                    <span id="videohub360-midroll-click-url"><?php echo esc_attr($ad_click_urls['midroll']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (isset($active_ads['postroll'])): ?>
                    <span id="videohub360-postroll-ad-source"><?php echo esc_url($postroll_ad_url); ?></span>
                    <span id="videohub360-postroll-enabled"><?php echo esc_attr($postroll_enabled); ?></span>
                    <span id="videohub360-postroll-click-url"><?php echo esc_attr($ad_click_urls['postroll']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php } // End membership access check ?>

            <!-- Video Engagement Actions (Like, Dislike, Save, Share) -->
            <div class="videohub360-engagement-bar">
                <?php 
                // Get reaction counts and user's reaction
                $reaction_counts = VideoHub360_Video_Reactions::get_counts(get_the_ID());
                $user_reaction = is_user_logged_in() ? VideoHub360_Video_Reactions::get_user_reaction(get_the_ID(), get_current_user_id()) : null;
                ?>
                
                <!-- Like/Dislike Buttons -->
                <div class="vh360-reactions-container">
                    <button class="vh360-reaction-btn vh360-like-btn <?php echo ($user_reaction === 'like') ? 'active' : ''; ?>" 
                            data-video-id="<?php echo esc_attr(get_the_ID()); ?>" 
                            data-reaction="like"
                            <?php echo !is_user_logged_in() ? 'data-login-required="true"' : ''; ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
                        </svg>
                        <span class="vh360-reaction-count"><?php echo esc_html($reaction_counts['likes']); ?></span>
                    </button>
                    <button class="vh360-reaction-btn vh360-dislike-btn <?php echo ($user_reaction === 'dislike') ? 'active' : ''; ?>" 
                            data-video-id="<?php echo esc_attr(get_the_ID()); ?>" 
                            data-reaction="dislike"
                            <?php echo !is_user_logged_in() ? 'data-login-required="true"' : ''; ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/>
                        </svg>
                        <span class="vh360-reaction-count"><?php echo esc_html($reaction_counts['dislikes']); ?></span>
                    </button>
                </div>
                
                <!-- Save to Playlist Button -->
                <button class="vh360-save-btn" id="vh360-save-btn" data-video-id="<?php echo esc_attr(get_the_ID()); ?>"
                        <?php echo !is_user_logged_in() ? 'data-login-required="true"' : ''; ?>>
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                    Save
                </button>
                
                <!-- Share Button -->
                <button class="videohub360-share-btn" id="videohub360-share-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    Share
                </button>
                
                <?php if ($chat_enabled && $chat_placement === 'popup'): ?>
                <!-- Chat Button -->
                <button class="videohub360-open-chat-btn" id="videohub360-open-chat-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,3C6.5,3 2,6.58 2,11C2.05,13.15 3.06,15.17 4.75,16.5C4.75,17.1 4.33,18.67 2,21C4.37,20.89 6.64,20 8.47,18.5C9.61,18.83 10.81,19 12,19C17.5,19 22,15.42 22,11C22,6.58 17.5,3 12,3M12,17C7.58,17 4,14.31 4,11C4,7.69 7.58,5 12,5C16.42,5 20,7.69 20,11C20,14.31 16.42,17 12,17Z"/>
                    </svg>
                    Chat
                </button>
                <?php endif; ?>
            </div>

            <div class="videohub360-meta-container">
                <h1 class="videohub360-title"><?php the_title(); ?></h1>
                <?php 
                // Author badge under video title - videohub360_render_author_badge() handles all escaping internally
                echo videohub360_render_author_badge(get_the_ID(), array(
                    'variant' => 'default',
                    'avatar_size' => 56,
                    'show_username' => false, // Only show display name
                )); 
                ?>
                <div class="videohub360-meta-row">
                    <div class="videohub360-meta-left">
                        <?php if ($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] !== 'yes'): ?>
                            <div class="videohub360-live-info">
                                <?php if ($livestream_fields['live_badge'] === 'yes'): ?>
                                    <span class="videohub360-live-badge" data-badge-color="<?php echo esc_attr($livestream_fields['badge_color']); ?>"><?php echo esc_html($livestream_fields['badge_text']); ?></span>
                                <?php endif; ?>
                                <?php if ($livestream_fields['viewer_count'] === 'yes'): ?>
                                    <span class="videohub360-live-viewers">
                                        <span id="vh360-viewer-count-meta">--</span> watching now
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php echo esc_html( $views_display ) . ' views'; ?></span>
                                <?php if (!empty($livestream_fields['live_start_time'])): ?>
                                    <span class="videohub360-stream-duration" id="vh360-stream-started-meta" data-start="<?php echo esc_attr($livestream_fields['live_start_time']); ?>"></span>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($livestream_fields['is_live'] === 'yes'): ?>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php echo esc_html( $views_display ) . ' views'; ?></span>
                                <?php if (!empty($livestream_fields['live_start_time'])): ?>
                                    <span class="videohub360-stream-duration" id="vh360-stream-ended-meta" data-start="<?php echo esc_attr($livestream_fields['live_start_time']); ?>"></span>
                                <?php else: ?>
                                    <span class="videohub360-published-date"><?php echo esc_html( $published_date ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php echo esc_html( $views_display ) . ' views'; ?></span>
                                <span class="videohub360-published-date"><?php echo esc_html( $published_date ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="videohub360-meta-taxonomies">
                    <?php if (!empty($videohub360_categories) && !is_wp_error($videohub360_categories)): ?>
                        <span>
                            <strong>Category:</strong>
                            <?php
                            $cat_links = array();
                            foreach ($videohub360_categories as $term) {
                                $cat_links[] = '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                            }
                            echo implode(', ', $cat_links);
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($videohub360_series) && !is_wp_error($videohub360_series)): ?>
                        <span>
                            <strong>Series:</strong>
                            <?php
                            $series_links = array();
                            foreach ($videohub360_series as $term) {
                                $series_links[] = '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                            }
                            echo implode(', ', $series_links);
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($videohub360_locations) && !is_wp_error($videohub360_locations)): ?>
                        <span>
                            <strong>Location:</strong>
                            <?php
                            $location_links = array();
                            foreach ($videohub360_locations as $term) {
                                $location_links[] = '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                            }
                            echo implode(', ', $location_links);
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="videohub360-content">
                <?php the_content(); ?>
            </div>
            
            <?php
            // Render chat based on chat placement. Inline and popup modes are handled directly, while
            // sidebar mode will render inline only when the layout has no sidebar (i.e., full-width).
            if ($chat_enabled):
                if ($chat_placement === 'inline'): ?>
                    <!-- Inline Chat Section -->
                    <div class="videohub360-chat-section">
                        <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields); ?>
                    </div>
                <?php elseif ($chat_placement === 'popup'): ?>
                    <!-- Popup Chat - render outside the main content flow -->
                    <?php echo videohub360_render_chat_container('popup', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields); ?>
                <?php elseif ($chat_placement === 'sidebar' && $video_layout === 'full-width'): ?>
                    <!-- Sidebar placement fallback for full-width layout -->
                    <div class="videohub360-chat-section">
                        <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields); ?>
                    </div>
                <?php endif;
            endif; ?>

            <?php
            // Show comments only when chat is disabled or not occupying the inline/sidebar location.
            if (!$chat_enabled || !in_array($chat_placement, array('inline', 'sidebar'), true)): ?>
                <div class="videohub360-comments-section">
                    <h2>Comments</h2>
                    <?php
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    } else {
                        echo '<p>Comments are closed.</p>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($video_layout !== 'full-width'): ?>
        <aside class="videohub360-sidebar">
            <?php if ($chat_enabled && $chat_placement === 'sidebar'): ?>
                <div class="videohub360-sidebar-chat">
                    <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields); ?>
                </div>
            <?php endif; ?>
            <h2><?php echo videohub360_get_sidebar_title(get_the_ID()); ?></h2>
            <ul>
            <?php
            // Use global helper function for sidebar query
            $videohub360_query = videohub360_build_sidebar_query(get_the_ID());
            
            if ( $videohub360_query->have_posts() ) :
                while ( $videohub360_query->have_posts() ) : $videohub360_query->the_post();
                    $sidebar_views = get_post_meta( get_the_ID(), '_videohub360_post_views_count', true );
                    $sidebar_views = $sidebar_views ? $sidebar_views : 0;
                    $sidebar_views_display = number_format( $sidebar_views );
            ?>
                    <li>
    <a href="<?php the_permalink(); ?>" style="position:relative;display:block;">
        <?php 
        $is_live = get_post_meta(get_the_ID(), '_vh360_is_live', true);
        $stream_stopped = get_post_meta(get_the_ID(), '_vh360_stream_stopped', true);
        $live_badge = get_post_meta(get_the_ID(), '_vh360_live_badge', true);
        $badge_text = get_post_meta(get_the_ID(), '_vh360_badge_text', true) ?: 'LIVE';
        $badge_color = get_post_meta(get_the_ID(), '_vh360_badge_color', true) ?: '#e53935';
        $show_live_badge = ($is_live === 'yes' && $stream_stopped !== 'yes' && $live_badge !== 'no');

        if ( has_post_thumbnail() ) {
            the_post_thumbnail( 'medium', array(
                'class' => 'videohub360-sidebar-thumbnail',
                'alt' => get_the_title(),
            ));
        } else { ?>
            <div class="videohub360-sidebar-thumbnail"></div>
        <?php } 
        if ($show_live_badge): ?>
            <span class="videohub360-live-badge videohub360-live-badge-sidebar" data-badge-color="<?php echo esc_attr($badge_color); ?>">
                <?php echo esc_html($badge_text); ?>
            </span>
        <?php endif; ?>
    </a>
    <div class="videohub360-sidebar-info">
        <a href="<?php the_permalink(); ?>" class="videohub360-sidebar-title"><?php the_title(); ?></a>
        <?php 
        // videohub360_render_author_badge() handles all escaping internally
        echo videohub360_render_author_badge(get_the_ID(), array(
            'variant' => 'name_only',
            'link' => true,
        )); 
        ?>
        <div class="videohub360-sidebar-meta">
            <?php echo get_the_date(); ?><br>
            <?php echo esc_html( videohub360_compact_views( $sidebar_views ) ); ?> views
        </div>
    </div>
</li>
                <?php endwhile;
                    wp_reset_postdata();
                else: ?>
                    <li>No other VideoHub360 videos found.</li>
                <?php endif; ?>
                </ul>
        </aside>
        <?php endif; // End if video layout is not full-width ?>
    </div>



    <div class="videohub360-modal-overlay" id="videohub360-modal-overlay">
        <div class="videohub360-modal">
            <div class="videohub360-modal-header">
                <h3 class="videohub360-modal-title">Share this video</h3>
                <button class="videohub360-modal-close" id="videohub360-modal-close">&times;</button>
            </div>
            <div class="videohub360-modal-body">
                <div class="videohub360-modal-section">
                    <h3>Copy link</h3>
                    <div class="videohub360-link-copy">
                        <input type="text" class="videohub360-link-input" id="videohub360-link-input" value="<?php echo esc_attr($permalink); ?>" readonly>
                        <button class="videohub360-copy-btn" id="videohub360-copy-btn">Copy</button>
                    </div>
                </div>
                <div class="videohub360-modal-section">
                    <h3>Share on social media</h3>
                    <div class="videohub360-social-icons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($permalink); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon facebook" title="Share on Facebook">
                            <svg viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($permalink); ?>&text=<?php echo urlencode($title); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon twitter" title="Share on Twitter">
                            <svg viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($permalink); ?>&title=<?php echo urlencode($title); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon linkedin" title="Share on LinkedIn">
                            <svg viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($title . ' - ' . $permalink); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon whatsapp" title="Share on WhatsApp">
                            <svg viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                            </svg>
                        </a>
                        <a href="https://t.me/share/url?url=<?php echo urlencode($permalink); ?>&text=<?php echo urlencode($title); ?>"
                           target="_blank" rel="noopener" class="videohub360-social-icon telegram" title="Share on Telegram">
                            <svg viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="videohub360-modal-section videohub360-email-section">
                    <button type="button" class="videohub360-email-toggle" id="videohub360-email-toggle">
                        <span>Send via email</span>
                        <svg class="videohub360-email-toggle-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    <div class="videohub360-email-form-container" id="videohub360-email-form-container">
                        <form class="videohub360-email-form" id="videohub360-email-form">
                            <div class="videohub360-form-group">
                                <label for="videohub360-from-name-input">Your name:</label>
                                <input type="text" class="videohub360-form-input" id="videohub360-from-name-input"
                                       placeholder="Enter your name" 
                                       value="<?php echo esc_attr($is_user_logged_in ? $user_display_name : ''); ?>" required>
                            </div>
                            <div class="videohub360-form-group">
                                <label for="videohub360-email-input"><?php esc_html_e('Recipient email:', 'videohub360'); ?></label>
                                <input type="email" class="videohub360-form-input videohub360-email-input" id="videohub360-email-input"
                                       placeholder="<?php echo esc_attr__('Enter email address', 'videohub360'); ?>" required>
                            </div>
                            <div class="videohub360-form-group">
                                <label for="videohub360-message-input"><?php esc_html_e('Message (optional):', 'videohub360'); ?></label>
                                <textarea class="videohub360-form-input" id="videohub360-message-input" rows="3"
                                          placeholder="<?php echo esc_attr__('Add a personal message...', 'videohub360'); ?>"><?php echo esc_textarea(__('Check out this video I thought you might enjoy!', 'videohub360')); ?></textarea>
                            </div>
                            <button type="submit" class="videohub360-send-btn" id="videohub360-send-btn"><?php esc_html_e('Send Link', 'videohub360'); ?></button>
                            <div class="videohub360-email-message" id="videohub360-email-message" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save to Playlist Modal -->
    <div class="vh360-playlist-modal-overlay" id="vh360-playlist-modal-overlay" style="display: none;">
        <div class="vh360-playlist-modal">
            <div class="vh360-playlist-modal-header">
                <h3 class="vh360-playlist-modal-title"><?php esc_html_e('Save to Playlist', 'videohub360'); ?></h3>
                <button class="vh360-playlist-modal-close" id="vh360-playlist-modal-close">&times;</button>
            </div>
            <div class="vh360-playlist-modal-body">
                <div class="vh360-playlist-list" id="vh360-playlist-list">
                    <p class="vh360-playlist-loading"><?php esc_html_e('Loading playlists...', 'videohub360'); ?></p>
                </div>
                <div class="vh360-create-playlist-section">
                    <button class="vh360-create-playlist-toggle" id="vh360-create-playlist-toggle">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        <?php esc_html_e('Create New Playlist', 'videohub360'); ?>
                    </button>
                    <div class="vh360-create-playlist-form" id="vh360-create-playlist-form" style="display: none;">
                        <input type="text" 
                               id="vh360-new-playlist-title" 
                               class="vh360-playlist-input" 
                               placeholder="<?php esc_attr_e('Playlist title', 'videohub360'); ?>" 
                               maxlength="255">
                        <div class="vh360-create-playlist-actions">
                            <button class="vh360-btn vh360-btn-cancel" id="vh360-cancel-create-playlist">
                                <?php esc_html_e('Cancel', 'videohub360'); ?>
                            </button>
                            <button class="vh360-btn vh360-btn-primary" id="vh360-submit-create-playlist">
                                <?php esc_html_e('Create', 'videohub360'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="videohub360-login-modal" id="videohub360-login-modal">
        <div class="videohub360-login-modal-content">
            <div class="videohub360-login-modal-header">
                <h3 class="videohub360-login-modal-title"><?php esc_html_e('Login Required', 'videohub360'); ?></h3>
                <button class="videohub360-login-modal-close" id="videohub360-login-modal-close">&times;</button>
            </div>
            <div class="videohub360-login-modal-body" id="videohub360-login-modal-body">
                <?php
                $login_modal_type = get_option('videohub360_login_modal_type', 'default');
                $login_modal_shortcode = get_option('videohub360_login_modal_shortcode', '');
                
                switch ($login_modal_type) {
                    case 'shortcode':
                        if (!empty($login_modal_shortcode)) {
                            echo '<div class="videohub360-shortcode-login-form">';
                            $shortcode_output = do_shortcode($login_modal_shortcode);
                            if (!empty(trim($shortcode_output))) {
                                echo $shortcode_output;
                            } else {
                                // Shortcode produced no output, show error message and fallback
                                echo '<div class="videohub360-shortcode-error" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; border-radius: 4px; margin-bottom: 16px; color: #856404;">';
                                echo '<strong>' . esc_html__('Notice:', 'videohub360') . '</strong> ';
                                /* translators: %s: The login shortcode that failed */
                                echo sprintf( esc_html__( 'The login shortcode %s did not produce any output. Please check the shortcode or contact the administrator.', 'videohub360' ), '<code>' . esc_html( $login_modal_shortcode ) . '</code>' );
                                echo '</div>';
                                
                                // Fallback to default login
                                ?>
                                <div class="videohub360-login-modal-message">
                                    <p><?php esc_html_e('Please log in to your account to participate in the live chat.', 'videohub360'); ?></p>
                                </div>
                                <div class="videohub360-login-modal-actions">
                                    <a href="<?php echo esc_url($user_login_url); ?>" class="videohub360-login-modal-btn">
                                        <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                                    </a>
                                    <?php if ( get_option( 'users_can_register' ) ) : ?>
                                        <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                            <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                            echo '</div>';
                        } else {
                            // Fallback to default if shortcode is empty
                            ?>
                            <div class="videohub360-login-modal-message">
                                <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                            </div>
                            <div class="videohub360-login-modal-actions">
                                <a href="<?php echo esc_url( $user_login_url ); ?>" class="videohub360-login-modal-btn">
                                    <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                                </a>
                                <?php if ( get_option( 'users_can_register' ) ) : ?>
                                    <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                        <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                        break;
                        
                    case 'redirect':
                    case 'javascript':
                        // For redirect and javascript types, show minimal content
                        // The actual handling happens in JavaScript
                        ?>
                        <div class="videohub360-login-modal-message">
                            <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                        </div>
                        <div class="videohub360-login-modal-actions">
                            <button type="button" class="videohub360-login-modal-btn" id="videohub360-login-action-btn">
                                <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                            </button>
                            <?php if ( get_option( 'users_can_register' ) ) : ?>
                                <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                    <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;
                        
                    case 'builtin':
                        // Allow developers to override the entire form HTML
                        if (has_filter('videohub360_builtin_login_form')) {
                            echo apply_filters('videohub360_builtin_login_form', '');
                        } else {
                            // Default built-in login form
                            ?>
                            <div class="videohub360-builtin-login-form">
                                <form id="videohub360-builtin-login-form" method="post" novalidate>
                                    <?php wp_nonce_field('videohub360_login_nonce', 'videohub360_login_nonce'); ?>
                                    
                                    <div class="vh360-form-field">
                                        <label for="vh360-username"><?php esc_html_e('Username or Email', 'videohub360'); ?></label>
                                        <input 
                                            type="text" 
                                            id="vh360-username" 
                                            name="username" 
                                            required 
                                            autocomplete="username" 
                                            aria-required="true"
                                        />
                                    </div>
                                    
                                    <div class="vh360-form-field">
                                        <label for="vh360-password"><?php esc_html_e('Password', 'videohub360'); ?></label>
                                        <input 
                                            type="password" 
                                            id="vh360-password" 
                                            name="password" 
                                            required 
                                            autocomplete="current-password" 
                                            aria-required="true"
                                        />
                                    </div>
                                    
                                    <div class="vh360-form-field vh360-form-checkbox">
                                        <label for="vh360-remember">
                                            <input 
                                                type="checkbox" 
                                                id="vh360-remember" 
                                                name="remember" 
                                                value="1"
                                            />
                                            <?php esc_html_e('Remember Me', 'videohub360'); ?>
                                        </label>
                                    </div>
                                    
                                    <div class="vh360-form-message" id="vh360-login-message" role="alert"></div>
                                    
                                    <div class="vh360-form-actions">
                                        <button type="submit" class="videohub360-login-modal-btn" id="vh360-login-submit">
                                            <?php esc_html_e('Log In', 'videohub360'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="vh360-form-footer">
                                        <a href="<?php echo esc_url(function_exists('vh360_get_lost_password_page_url') ? vh360_get_lost_password_page_url() : wp_lostpassword_url()); ?>" class="vh360-lost-password">
                                            <?php esc_html_e('Lost your password?', 'videohub360'); ?>
                                        </a>
                                        <?php if (get_option('users_can_register')) : ?>
                                            <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="vh360-register-link">
                                                <?php esc_html_e('Register', 'videohub360'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <?php
                        }
                        break;
                        
                    default: // 'default'
                        ?>
                        <div class="videohub360-login-modal-message">
                            <p><?php esc_html_e( 'Please log in to your account to participate in the live chat.', 'videohub360' ); ?></p>
                        </div>
                        <div class="videohub360-login-modal-actions">
                            <a href="<?php echo esc_url( $user_login_url ); ?>" class="videohub360-login-modal-btn">
                                <?php echo esc_html__( 'Log In', 'videohub360' ); ?>
                            </a>
                            <?php if ( get_option( 'users_can_register' ) ) : ?>
                                <a href="<?php echo esc_url(function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url()); ?>" class="videohub360-login-modal-btn secondary">
                                    <?php echo esc_html__( 'Register', 'videohub360' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php
                        break;
                }
                ?>
            </div>
        </div>
    </div>


    <!-- Moderation Panel Modal -->
    <?php if ($can_moderate): ?>
    <div id="vh360-moderation-modal" class="vh360-moderation-modal" style="display: none;">
        <div class="vh360-moderation-modal-content">
            <div class="vh360-moderation-modal-header">
                <h3>Moderation Panel</h3>
                <button type="button" class="vh360-moderation-modal-close" id="vh360-moderation-modal-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                    </svg>
                </button>
            </div>
            <div class="vh360-moderation-modal-body">
                <div class="vh360-moderation-loading" id="vh360-moderation-loading">
                    <p>Loading moderation data...</p>
                </div>
                
                <div class="vh360-moderation-content" id="vh360-moderation-content" style="display: none;">
                    <div class="vh360-moderation-section">
                        <h4>💬 Text Chat - Banned Users <span class="vh360-moderation-count" id="vh360-chat-banned-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-chat-banned-users-list">
                            <p class="vh360-no-items">No chat banned users</p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4>💬 Text Chat - Timed Out Users <span class="vh360-moderation-count" id="vh360-chat-timeout-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-chat-timeout-users-list">
                            <p class="vh360-no-items">No chat timed out users</p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4>🎥 Video Chat - Banned Users <span class="vh360-moderation-count" id="vh360-agora-banned-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-agora-banned-users-list">
                            <p class="vh360-no-items">No video banned users</p>
                        </div>
                    </div>
                    
                    <div class="vh360-moderation-section">
                        <h4>🎥 Video Chat - Timed Out Users <span class="vh360-moderation-count" id="vh360-agora-timeout-count">0</span></h4>
                        <div class="vh360-moderation-list" id="vh360-agora-timeout-users-list">
                            <p class="vh360-no-items">No video timed out users</p>
                        </div>
                    </div>
                </div>
                
                <div class="vh360-moderation-error" id="vh360-moderation-error" style="display: none;">
                    <p>Failed to load moderation data. Please try again.</p>
                    <button type="button" class="vh360-moderation-retry" id="vh360-moderation-retry">Retry</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


<?php
endwhile; endif;
get_footer();
?>
