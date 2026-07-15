<?php
/**
 * Activity Feed Right Sidebar
 *
 * Displays who to follow, ad space, and custom widgets.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get customizer settings
$show_recommended_users = get_theme_mod('vh360_show_recommended_users', true);
$show_ad_space = get_theme_mod('vh360_show_activity_ad_space', false);
$activity_ad_privacy_type = get_theme_mod('vh360_activity_ad_privacy_type', 'contextual');

// Get widget titles
$who_to_follow_title = get_theme_mod('vh360_who_to_follow_title', __('Who to Follow', 'videohub360-theme'));

// Get data for widgets
$recommended_users = function_exists('vh360_get_recommended_profiles') ? vh360_get_recommended_profiles(5) : array();
?>

<div class="vh360-sidebar-widgets">

    <?php if ($show_ad_space) : ?>
    <!-- Ad Slot Widget Area -->
    <?php if (is_active_sidebar('activity-feed-ad')) : ?>
        <?php if ('personalized' === $activity_ad_privacy_type && function_exists('videohub360_has_service_consent') && !videohub360_has_service_consent('activity-feed-ad-slot')) : ?>
            <div class="vh360-sidebar-widget vh360-ad-slot vh360-ad-slot--blocked" data-vh360-activity-ad-slot data-vh360-activity-ad-blocked>
                <div class="vh360-widget-content">
                    <p><?php esc_html_e('This advertising space is available after advertising privacy choices are enabled.', 'videohub360-theme'); ?></p>
                    <button type="button" class="vh360-consent-open"><?php esc_html_e('Privacy Choices', 'videohub360-theme'); ?></button>
                </div>
            </div>
        <?php else : ?>
            <div data-vh360-activity-ad-slot>
                <?php dynamic_sidebar('activity-feed-ad'); ?>
            </div>
        <?php endif; ?>
    <?php elseif (current_user_can('manage_options')) : ?>
        <!-- Admin-only placeholder when widget area is empty -->
        <div class="vh360-sidebar-widget vh360-ad-slot">
            <div class="vh360-widget-content">
                <div class="vh360-ad-placeholder">
                    <p><?php esc_html_e('Use 300×250 or 300×600 creatives. Sidebar supports 300px width.', 'videohub360-theme'); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($show_recommended_users) : ?>
    <!-- Who to Follow Widget -->
    <div class="vh360-sidebar-widget vh360-who-to-follow-widget">
        <h3 class="vh360-widget-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <?php echo esc_html($who_to_follow_title); ?>
        </h3>
        <div class="vh360-widget-content">
            <?php if (!empty($recommended_users)) : ?>
                <?php foreach ($recommended_users as $user) : 
                    $user_id = $user->ID;
                    $display_name = $user->display_name;
                    $user_login = $user->user_login;
                    
                    // Get user profile URL
                    $profile_url = get_author_posts_url($user_id);
                    
                    // Get user bio
                    $bio = get_user_meta($user_id, 'description', true);
                    if ($bio) {
                        $bio_words = explode(' ', $bio);
                        if (count($bio_words) > 10) {
                            $bio = implode(' ', array_slice($bio_words, 0, 10)) . '...';
                        }
                    }
                    
                    // Check if current user is following this user
                    $is_following = false;
                    if (is_user_logged_in()) {
                        $current_following = get_user_meta(get_current_user_id(), 'vh360_following', true);
                        if (is_array($current_following)) {
                            $is_following = in_array($user_id, $current_following);
                        }
                    }
                ?>
                <div class="vh360-follow-user-item">
                    <a href="<?php echo esc_url($profile_url); ?>" class="vh360-user-avatar">
                        <?php echo get_avatar($user_id, 48); ?>
                    </a>
                    <div class="vh360-user-info">
                        <div class="vh360-user-identity">
                            <a href="<?php echo esc_url($profile_url); ?>" class="vh360-user-name"><?php echo esc_html($display_name); ?></a>
                            <div class="vh360-user-username">@<?php echo esc_html($user_login); ?></div>
                        </div>
                        <?php if ($bio) : ?>
                        <div class="vh360-user-bio"><?php echo esc_html($bio); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (is_user_logged_in() && get_current_user_id() != $user_id) : 
                        $nonce = wp_create_nonce('vh360_follow_user');
                    ?>
                    <button class="vh360-follow-btn <?php echo $is_following ? 'vh360-follow-btn--following vh360-unfollow-btn' : ''; ?>" 
                            data-target="<?php echo esc_attr($user_id); ?>"
                            data-nonce="<?php echo esc_attr($nonce); ?>">
                        <?php echo $is_following ? esc_html__('Following', 'videohub360-theme') : esc_html__('Follow', 'videohub360-theme'); ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <?php
                // Get members directory URL - check for common members page slugs
                $members_url = home_url('/members/');
                $members_page = get_page_by_path('members');
                if ($members_page) {
                    $members_url = get_permalink($members_page->ID);
                }
                ?>
                <a href="<?php echo esc_url($members_url); ?>" class="vh360-show-more-link">
                    <?php esc_html_e('Show more', 'videohub360-theme'); ?>
                </a>
            <?php else : ?>
                <div class="vh360-widget-empty-state">
                    <?php if (!is_user_logged_in()) : ?>
                        <p><?php esc_html_e('Please log in to see recommendations.', 'videohub360-theme'); ?></p>
                    <?php else : ?>
                        <p><?php esc_html_e('No users to recommend yet.', 'videohub360-theme'); ?></p>
                        <?php if (current_user_can('manage_options')) : ?>
                            <p class="vh360-admin-hint"><small><?php esc_html_e('To display users: Users need vh360_followers_count user meta with values greater than 0.', 'videohub360-theme'); ?></small></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Custom WordPress Widgets Area
    if (is_active_sidebar('activity-feed-sidebar')) :
        dynamic_sidebar('activity-feed-sidebar');
    endif;
    ?>

    <!-- Sidebar Footer -->
    <div class="vh360-sidebar-footer">
        <nav class="vh360-footer-links">
            <?php
            // Get footer pages dynamically
            $footer_pages = array(
                'about' => __('About', 'videohub360-theme'),
                'help' => __('Help', 'videohub360-theme'),
                'privacy' => __('Privacy', 'videohub360-theme'),
                'terms' => __('Terms', 'videohub360-theme'),
            );
            
            $links = array();
            foreach ($footer_pages as $slug => $label) {
                $page = get_page_by_path($slug);
                if ($page) {
                    // Page exists, use its permalink
                    $url = get_permalink($page->ID);
                    $links[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
                }
                // If page doesn't exist, skip the link entirely
            }
            
            // Display links if any exist
            if (!empty($links)) {
                echo implode(' <span class="vh360-separator">·</span> ', $links);
            }
            ?>
        </nav>
    </div>

</div>
