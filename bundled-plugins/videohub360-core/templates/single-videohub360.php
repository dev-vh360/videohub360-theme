<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
get_header();

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
    $studio_asset_id = get_post_meta( get_the_ID(), '_vh360_studio_video_asset_id', true );
    $studio_playback = ( $studio_asset_id && function_exists( 'vh360_studio_get_video_playback' ) ) ? vh360_studio_get_video_playback( $studio_asset_id ) : null;
    if ( $studio_playback && 'ready' === $studio_playback['status'] ) {
        $custom_html = '';
    }
    $poster = get_the_post_thumbnail_url( get_the_ID(), 'large' );
    $permalink = get_permalink();
    $title = get_the_title();
    $published_date = get_the_date();

    // Resolve the internal VideoHub360 sidebar/full-width layout.
    $video_layout = videohub360_get_single_video_layout(get_the_ID());

    // Livestream fields
    $is_youtube_auto_managed = get_post_meta(get_the_ID(), '_vh360_youtube_auto_managed', true) === 'yes';

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

    $is_legacy_studio_livestream_replay = (
        get_post_meta(get_the_ID(), '_vh360_studio_converted_live_to_replay', true) === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_ready', true) === 'yes'
        && (int) get_post_meta(get_the_ID(), '_vh360_studio_replay_source_live_video_id', true) === (int) get_the_ID()
    );

    $is_studio_replay_processing = (
        $livestream_fields['stream_stopped'] === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_controlled_live', true) === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_ready', true) !== 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_pending', true) === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_failed', true) !== 'yes'
    );

    $is_studio_replay_failed = (
        $livestream_fields['stream_stopped'] === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_controlled_live', true) === 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_ready', true) !== 'yes'
        && get_post_meta(get_the_ID(), '_vh360_studio_replay_failed', true) === 'yes'
    );

    $has_studio_replay_playback = (
        get_post_meta(get_the_ID(), '_vh360_studio_replay_ready', true) === 'yes'
        && (
            !empty($video_url)
            || !empty($custom_html)
        )
    );

    $is_stopped_livestream_replay = (
        (
            $livestream_fields['is_live'] === 'yes'
            && $livestream_fields['stream_stopped'] === 'yes'
            && $has_studio_replay_playback
        )
        || (
            $is_legacy_studio_livestream_replay
            && $has_studio_replay_playback
        )
    );

    $is_livestream_surface = $livestream_fields['is_live'] === 'yes' || $is_legacy_studio_livestream_replay;

    $videohub360_categories = get_the_terms(get_the_ID(), 'videohub360_category');
    $videohub360_series = get_the_terms(get_the_ID(), 'videohub360_series');
    $videohub360_locations = get_the_terms(get_the_ID(), 'videohub360_location');

    // Determine if chat should be enabled - for all livestreams when chat is enabled
    $is_live = get_post_meta(get_the_ID(), '_vh360_is_live', true);
    $chat_enabled = false;
    $chat_placement = 'popup'; // Default placement
    
    if ($is_live === 'yes' || $is_legacy_studio_livestream_replay) {
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
            $chat_placement = get_option('videohub360_chat_placement', 'popup');
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
    $is_stream_stopped = (($livestream_fields['stream_stopped'] ?? 'no') === 'yes');
    $can_live_moderate = $can_moderate && !$is_stream_stopped;
?>

    <div class="videohub360-main-wrapper <?php echo ($video_layout === 'full-width') ? 'videohub360-full-width' : 'videohub360-sidebar-layout'; ?>">
        <div class="videohub360-content-area">
            <?php
            // Check membership/course purchase requirement before rendering video.
            $required_plan = function_exists('vh360_post_requires_membership') ? vh360_post_requires_membership(get_the_ID()) : false;
            $user_has_access = true;

            if (function_exists('videohub360_user_can_access_lesson') && function_exists('videohub360_course_features_enabled') && videohub360_course_features_enabled()) {
                $user_has_access = videohub360_user_can_access_lesson(get_the_ID(), get_current_user_id());
            } elseif ($required_plan) {
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
                // Render access gate instead of video.
                if (function_exists('videohub360_render_course_lesson_access_gate')) {
                    echo videohub360_render_course_lesson_access_gate(get_the_ID());
                } elseif (function_exists('vh360_render_membership_gate')) {
                    echo vh360_render_membership_gate(array('required_plan' => $required_plan ?: 'course'));
                } else {
                    echo '<div class="vh360-membership-gate"><p>' . esc_html__('Please log in or purchase access to view this lesson.', 'videohub360') . '</p></div>';
                }
            } else {
                // User has access – record enrollment activity for logged-in learners.
                // Exclude free-preview lessons (they are public; viewing them must not
                // create an enrollment row) and admins (manage_options capability).
                if ( is_user_logged_in()
                    && ! current_user_can( 'manage_options' )
                    && 'yes' !== get_post_meta( get_the_ID(), '_vh360_lesson_is_preview', true )
                    && function_exists( 'videohub360_course_features_enabled' )
                    && videohub360_course_features_enabled()
                    && function_exists( 'vh360_update_course_enrollment_activity' )
                    && function_exists( 'videohub360_get_lesson_course' )
                    && function_exists( 'vh360_user_can_access_course' )
                ) {
                    $vh360_activity_course = videohub360_get_lesson_course( get_the_ID() );
                    if ( $vh360_activity_course ) {
                        $vh360_activity_user = get_current_user_id();
                        // Only create/update enrollment when the user has full course access
                        // (not merely because the lesson is a free preview).
                        if ( vh360_user_can_access_course( $vh360_activity_user, (int) $vh360_activity_course->term_id ) ) {
                            vh360_update_course_enrollment_activity(
                                $vh360_activity_user,
                                (int) $vh360_activity_course->term_id,
                                get_the_ID()
                            );
                            if ( function_exists( 'vh360_mark_lesson_started' ) ) {
                                vh360_mark_lesson_started( $vh360_activity_user, get_the_ID() );
                            }
                        }
                        unset( $vh360_activity_course, $vh360_activity_user );
                    }
                }

                // User has access, render video
            ?>
            <?php
            $vh360_video_player_classes = 'videohub360-video-player';
            if (
                $livestream_fields['is_live'] === 'yes'
                && $livestream_fields['stream_stopped'] !== 'yes'
                && $livestream_fields['type'] === 'agora'
                && $livestream_fields['agora_mode'] === 'interactive'
            ) {
                $vh360_video_player_classes .= ' vh360-has-agora-interactive';
            }
            ?>
            <?php if ($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] !== 'yes'): ?>
                <div class="<?php echo esc_attr($vh360_video_player_classes); ?>" id="videohub360-livestream-player-root">
                    <?php echo videohub360_render_livestream($livestream_fields, $chat_enabled, $chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url); ?>
                </div>
            <?php elseif ((($livestream_fields['is_live'] === 'yes' && $livestream_fields['stream_stopped'] === 'yes') || $is_legacy_studio_livestream_replay) && !$is_stopped_livestream_replay && !$is_youtube_auto_managed): ?>
                <div class="videohub360-video-player">
                    <div class="vh360-offline-wrapper">
                        <?php if ($is_studio_replay_processing) : ?>
                            <div class="vh360-offline-message">
                                <?php echo wp_kses_post(vh360_get_stream_replay_processing_html()); ?>
                            </div>
                        <?php elseif ($is_studio_replay_failed) : ?>
                            <div class="vh360-offline-message">
                                <div class="vh360-stream-ended-content"><div class="vh360-stream-ended-icon">📴</div><h3 class="vh360-stream-ended-title"><?php esc_html_e('Stream Ended', 'videohub360'); ?></h3><p class="vh360-stream-ended-text"><?php esc_html_e('The replay is not available yet. Please check back later.', 'videohub360'); ?></p></div>
                            </div>
                        <?php elseif (!empty($livestream_fields['offline_message'])) : ?>
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
                            <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr__('Video Poster', 'videohub360'); ?>" class="videohub360-static-poster-img" />
                        <?php else: ?>
                            <div class="videohub360-static-poster-img vh360-no-poster"></div>
                        <?php endif; ?>
                        <button class="videohub360-static-play-btn" id="videohub360-static-play-btn" aria-label="<?php esc_attr_e('Play video', 'videohub360'); ?>">
                            <svg viewBox="0 0 72 72" aria-hidden="true">
                                <polygon points="28,20 54,36 28,52"/>
                            </svg>
                        </button>
                    </div>
                    <?php if ($has_any_ads): ?>
                    <div id="videohub360-ad-container" class="videohub360-hide <?php echo !empty($ad_click_urls['preroll']) ? 'videohub360-ad-clickable' : ''; ?>"
                         data-ad-type="preroll" 
                         data-ad-url="<?php echo esc_attr($ad_click_urls['preroll']); ?>" 
                         data-ad-new-tab="<?php echo esc_attr($ad_click_urls['new_tab']); ?>"
                         data-tracking-enabled="<?php echo esc_attr($ad_click_urls['tracking_enabled']); ?>"
                         data-post-id="<?php echo esc_attr(get_the_ID()); ?>">
                        <div class="videohub360-ad-label" id="videohub360-ad-label"><?php esc_html_e('Advertisement', 'videohub360'); ?></div>
                        <span id="videohub360-ad-skip-msg"></span>
                        <button class="videohub360-ad-skip-btn" id="videohub360-ad-skip-btn" type="button"><?php esc_html_e('Skip Ad', 'videohub360'); ?></button>
                        <div class="videohub360-ad-click-overlay" tabindex="0" role="button" aria-label="<?php esc_attr_e('Click to visit advertiser', 'videohub360'); ?>">
                            <div class="videohub360-ad-click-indicator">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="videohub360-ad-click-icon" aria-hidden="true">
                                    <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 12H7V7h2v5zm0-6H7V4h2v2z"/>
                                </svg>
                                <?php esc_html_e('Learn More', 'videohub360'); ?>
                            </div>
                        </div>
                        <video id="videohub360-ad-video" width="100%" height="auto" controls playsinline poster="<?php echo esc_url($poster); ?>">
                            <?php if (!empty($ad_video_url)): ?>
                            <source src="<?php echo esc_url($ad_video_url); ?>" type="video/mp4">
                            <?php endif; ?>
                            <?php esc_html_e('Your browser does not support the video tag.', 'videohub360'); ?>
                        </video>
                    </div>
                    <?php endif; ?>
                    <div id="videohub360-main-container" class="videohub360-hide">
                        <?php if ( $studio_playback && 'ready' === $studio_playback['status'] ) : ?>
                            <?php if ( 'embed_html' === $studio_playback['render_mode'] && ! empty( $studio_playback['embed_html'] ) ) : ?>
                                <div class="videohub360-custom-embed-container"><?php echo $studio_playback['embed_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by VH360 Studio. ?></div>
                            <?php elseif ( 'embed' === $studio_playback['render_mode'] && ! empty( $studio_playback['embed_url'] ) ) : ?>
                                <div class="videohub360-custom-embed-container"><iframe src="<?php echo esc_url( $studio_playback['embed_url'] ); ?>" allowfullscreen loading="lazy"></iframe></div>
                            <?php else : ?>
                                <video id="videohub360-main-video" width="100%" height="auto" controls playsinline poster="<?php echo esc_url( $studio_playback['poster_url'] ?: $poster ); ?>"><source src="<?php echo esc_url( $studio_playback['src'] ); ?>" type="<?php echo esc_attr( $studio_playback['mime_type'] ); ?>"><?php esc_html_e('Your browser does not support the video tag.', 'videohub360'); ?></video>
                            <?php endif; ?>
                        <?php elseif ( $studio_playback && in_array( $studio_playback['status'], array( 'pending', 'uploading', 'processing' ), true ) ) : ?>
                            <p class="videohub360-video-processing"><?php esc_html_e( 'Video is processing. Please check back soon.', 'videohub360' ); ?></p>
                        <?php elseif ( $studio_playback && 'failed' === $studio_playback['status'] ) : ?>
                            <p class="videohub360-video-processing-failed"><?php esc_html_e( 'Video processing could not be completed.', 'videohub360' ); ?></p>
                        <?php elseif ($custom_html): ?>
                            <div class="videohub360-custom-embed-container">
                                <?php 
                                // Output custom HTML with proper sanitization
                                // For users with unfiltered_html capability, output as-is (already saved securely)
                                // For others, use comprehensive whitelist that allows common embed codes
                                if (current_user_can('unfiltered_html')) {
                                    echo $custom_html;
                                } else {
                                    // Allow common embed elements (iframes, etc.) for video embeds
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
                                    $allowed_embed_html = apply_filters('videohub360_allowed_custom_embed_html', $allowed_embed_html, get_the_ID());
                                    echo wp_kses($custom_html, $allowed_embed_html);
                                }
                                ?>
                            </div>
                        <?php else: ?>
                            <video id="videohub360-main-video" width="100%" height="auto" controls playsinline poster="<?php echo esc_url($poster); ?>">
                                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                <?php esc_html_e('Your browser does not support the video tag.', 'videohub360'); ?>
                            </video>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Hidden ad data for JavaScript - only for regular videos (not custom HTML) and only when ads exist -->
                <?php if (!$custom_html && $has_any_ads): ?>
                <div class="videohub360-ad-hidden-data" hidden>
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
                            aria-pressed="<?php echo ($user_reaction === 'like') ? 'true' : 'false'; ?>"
                            <?php echo !is_user_logged_in() ? 'data-login-required="true"' : ''; ?>>
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
                        </svg>
                        <span class="vh360-reaction-count"><?php echo esc_html($reaction_counts['likes']); ?></span>
                    </button>
                    <button class="vh360-reaction-btn vh360-dislike-btn <?php echo ($user_reaction === 'dislike') ? 'active' : ''; ?>" 
                            data-video-id="<?php echo esc_attr(get_the_ID()); ?>" 
                            data-reaction="dislike"
                            aria-pressed="<?php echo ($user_reaction === 'dislike') ? 'true' : 'false'; ?>"
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
                    <?php esc_html_e('Save', 'videohub360'); ?>
                </button>
                
                <!-- Share Button -->
                <button class="videohub360-share-btn" id="videohub360-share-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.50-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    <?php esc_html_e('Share', 'videohub360'); ?>
                </button>
                
                <?php if ($chat_enabled && $chat_placement === 'popup'): ?>
                <!-- Chat Button -->
                <button class="videohub360-open-chat-btn" id="videohub360-open-chat-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,3C6.5,3 2,6.58 2,11C2.05,13.15 3.06,15.17 4.75,16.5C4.75,17.1 4.33,18.67 2,21C4.37,20.89 6.64,20 8.47,18.5C9.61,18.83 10.81,19 12,19C17.5,19 22,15.42 22,11C22,6.58 17.5,3 12,3M12,17C7.58,17 4,14.31 4,11C4,7.69 7.58,5 12,5C16.42,5 20,7.69 20,11C20,14.31 16.42,17 12,17Z"/>
                    </svg>
                    <?php esc_html_e('Chat', 'videohub360'); ?>
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
                                        <span id="vh360-viewer-count-meta">--</span> <?php esc_html_e('watching now', 'videohub360'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php printf(esc_html__('%s views', 'videohub360'), esc_html($views_display)); ?></span>
                                <?php if (!empty($livestream_fields['live_start_time'])): ?>
                                    <span class="videohub360-stream-duration" id="vh360-stream-started-meta" data-start="<?php echo esc_attr($livestream_fields['live_start_time']); ?>"></span>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($is_livestream_surface): ?>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php printf(esc_html__('%s views', 'videohub360'), esc_html($views_display)); ?></span>
                                <?php if (!empty($livestream_fields['live_start_time'])): ?>
                                    <span class="videohub360-stream-duration" id="vh360-stream-ended-meta" data-start="<?php echo esc_attr($livestream_fields['live_start_time']); ?>"></span>
                                <?php else: ?>
                                    <span class="videohub360-published-date"><?php echo esc_html( $published_date ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="videohub360-views-info">
                                <span class="videohub360-views-count"><?php printf(esc_html__('%s views', 'videohub360'), esc_html($views_display)); ?></span>
                                <span class="videohub360-published-date"><?php echo esc_html( $published_date ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="videohub360-meta-taxonomies">
                    <?php if (!empty($videohub360_categories) && !is_wp_error($videohub360_categories)): ?>
                        <span>
                            <strong><?php esc_html_e('Category:', 'videohub360'); ?></strong>
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
                        <?php
                        $series_label = __('Series:', 'videohub360');
                        if (
                            function_exists('videohub360_course_features_enabled') &&
                            videohub360_course_features_enabled() &&
                            function_exists('videohub360_get_lesson_course') &&
                            videohub360_get_lesson_course(get_the_ID()) &&
                            function_exists('videohub360_get_course_label')
                        ) {
                            $series_label = videohub360_get_course_label() . ':';
                        }
                        ?>
                        <span>
                            <strong><?php echo esc_html($series_label); ?></strong>
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
                            <strong><?php esc_html_e('Location:', 'videohub360'); ?></strong>
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
            // ---- Course / Lesson Navigation (additive block) -------------------------
            // Only shown when Course / Lesson Features are enabled and the current post
            // belongs to a videohub360_series term (i.e. is part of a course).
            if (
                function_exists('videohub360_course_features_enabled') &&
                videohub360_course_features_enabled() &&
                function_exists('videohub360_get_lesson_course') &&
                function_exists('videohub360_get_lesson_navigation')
            ) {
                $vh360_lesson_course = videohub360_get_lesson_course(get_the_ID());
                if ($vh360_lesson_course) {
                    $vh360_post_id    = get_the_ID();
                    $vh360_nav_data   = videohub360_get_lesson_navigation($vh360_post_id);
                    $vh360_nav_file   = VIDEOHUB360_PLUGIN_DIR . 'templates/course/lesson-navigation.php';
                    if (file_exists($vh360_nav_file)) {
                        include $vh360_nav_file;
                    }
                    unset($vh360_lesson_course, $vh360_post_id, $vh360_nav_data, $vh360_nav_file);
                }
            }
            // ---- End Course / Lesson Navigation --------------------------------------
            ?>

            <?php
            // ---- Mark Lesson Complete button -------------------------------------
            // Shown to logged-in users who have full course access (not just a free
            // preview) on lessons that belong to a course.
            if (
                is_user_logged_in()
                && ! current_user_can( 'manage_options' )
                && 'yes' !== get_post_meta( get_the_ID(), '_vh360_lesson_is_preview', true )
                && function_exists( 'videohub360_course_features_enabled' )
                && videohub360_course_features_enabled()
                && function_exists( 'videohub360_get_lesson_course' )
                && function_exists( 'vh360_user_can_access_course' )
            ) {
                $vh360_complete_course = videohub360_get_lesson_course( get_the_ID() );
                if (
                    $vh360_complete_course &&
                    vh360_user_can_access_course( get_current_user_id(), (int) $vh360_complete_course->term_id )
                ) {
                    ?>
                    <?php
                    $vh360_lesson_completed = function_exists( 'vh360_user_has_completed_lesson' )
                        && vh360_user_has_completed_lesson( get_current_user_id(), get_the_ID() );
                    ?>
                    <?php if ( $vh360_lesson_completed ) : ?>
                        <div class="vh360-lesson-complete-wrap">
                            <span class="vh360-btn vh360-btn-complete is-completed">
                                <?php esc_html_e( 'Completed', 'videohub360' ); ?>
                            </span>
                        </div>
                    <?php else : ?>
                        <div class="vh360-lesson-complete-wrap">
                            <form method="post" class="vh360-lesson-complete-form">
                                <?php wp_nonce_field( 'vh360_lesson_complete_' . get_the_ID(), 'vh360_lesson_complete_nonce' ); ?>
                                <input type="hidden" name="vh360_lesson_id" value="<?php echo esc_attr( get_the_ID() ); ?>" />
                                <button type="submit" name="vh360_mark_lesson_complete" value="1" class="vh360-btn vh360-btn-complete">
                                    <?php esc_html_e( 'Mark Lesson Complete', 'videohub360' ); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <?php
                }
                unset( $vh360_complete_course );
            }
            // ---- End Mark Lesson Complete ----------------------------------------
            ?>
            
            <?php
            // Render chat based on chat placement. Inline and popup modes are handled directly, while
            // sidebar mode will render inline only when the layout has no sidebar (i.e., full-width).
            if ($chat_enabled):
                if ($chat_placement === 'inline'): ?>
                    <!-- Inline Chat Section -->
                    <div class="videohub360-chat-section">
                        <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_live_moderate, $livestream_fields); ?>
                    </div>
                <?php elseif ($chat_placement === 'popup'): ?>
                    <!-- Popup Chat - render outside the main content flow -->
                    <?php echo videohub360_render_chat_container('popup', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_live_moderate, $livestream_fields); ?>
                <?php elseif ($chat_placement === 'sidebar' && $video_layout === 'full-width'): ?>
                    <!-- Sidebar placement fallback for full-width layout -->
                    <div class="videohub360-chat-section">
                        <?php echo videohub360_render_chat_container('inline', $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_live_moderate, $livestream_fields); ?>
                    </div>
                <?php endif;
            endif; ?>

            <?php
            // Show comments only when chat is disabled or not occupying the inline/sidebar location.
            if (!$chat_enabled || !in_array($chat_placement, array('inline', 'sidebar'), true)): ?>
                <div class="videohub360-comments-section">
                    <?php
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    } else {
                        echo '<p>' . esc_html__('Comments are closed.', 'videohub360') . '</p>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($video_layout !== 'full-width'): ?>
            <?php echo videohub360_render_single_video_sidebar(get_the_ID(), $chat_enabled, $chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_live_moderate, $livestream_fields); ?>
        <?php endif; // End if video layout is not full-width ?>
    </div>



    <?php echo videohub360_render_single_video_modals(get_the_ID(), $permalink, $title, $is_user_logged_in, $user_display_name, $user_login_url, $can_live_moderate); ?>

<?php
endwhile; endif;
get_footer();
?>
