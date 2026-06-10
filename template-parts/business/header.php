<?php
/**
 * Business Profile Header
 *
 * Displays header section for business profiles (professional/organization)
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get profile options
$profile_options = get_option('vh360_profile_options', array());
$profile_defaults = array(
    'show_header_follow_button' => true,
);
$profile_options = wp_parse_args($profile_options, $profile_defaults);

// Get business meta
$business_name = get_user_meta($author_id, '_vh360_business_name', true);
$business_type = get_user_meta($author_id, '_vh360_business_type', true);
$location = get_user_meta($author_id, '_vh360_location', true);

$display_name = $business_name ? $business_name : $author->display_name;

// Check if we should show message button
$current_user_id = get_current_user_id();
$show_message_button = false;
$show_follow_button = false;
$is_owner = ($current_user_id === $author_id);

if (is_user_logged_in() && $current_user_id !== $author_id) {
    // Check message button availability
    if (function_exists('vh360_is_dm_enabled') && function_exists('vh360_can_send_message')) {
        if (vh360_is_dm_enabled() && vh360_can_send_message($current_user_id, $author_id)) {
            $show_message_button = true;
        }
    }
    
    // Check follow button availability
    if (function_exists('vh360_follow_button') && !empty($profile_options['show_header_follow_button'])) {
        $show_follow_button = true;
    }
}
?>

<div class="vh360-business-header vh360-business-header--modern">
    <div class="vh360-business-cover" aria-hidden="true"></div>

    <div class="vh360-business-profile-bar">
        <div class="vh360-business-avatar">
            <?php echo get_avatar($author_id, 150); ?>
        </div>

        <div class="vh360-business-info">
            <h1 class="vh360-business-name"><?php echo esc_html($display_name); ?></h1>

            <?php if ($business_type && (!function_exists('vh360_profile_field_is_public') || vh360_profile_field_is_public('business_type', $author_id))) : ?>
                <p class="vh360-business-type"><?php echo esc_html($business_type); ?></p>
            <?php endif; ?>

            <?php if ($location && (!function_exists('vh360_profile_field_is_public') || vh360_profile_field_is_public('location', $author_id))) : ?>
                <p class="vh360-business-location">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($location); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="vh360-business-actions">
            <a href="#vh360-business-booking-panel" class="vh360-business-book-cta">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php esc_html_e('Book Appointment', 'videohub360-theme'); ?>
            </a>

            <?php if ($show_message_button) : ?>
                <a href="<?php echo esc_url(add_query_arg(array('tab' => 'messages', 'user' => $author_id), home_url('/dashboard/'))); ?>" class="vh360-business-message-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <?php esc_html_e('Send Message', 'videohub360-theme'); ?>
                </a>
            <?php endif; ?>

            <?php if ($show_follow_button) : ?>
                <?php vh360_follow_button($author_id, 'vh360-business-follow-btn'); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
