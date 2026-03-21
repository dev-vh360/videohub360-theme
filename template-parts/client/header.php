<?php
/**
 * Client Profile Header
 *
 * Displays header section for client profiles
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

// Check if we should show follow button
$current_user_id = get_current_user_id();
$show_follow_button = false;

if (is_user_logged_in() && $current_user_id !== $author_id) {
    if (function_exists('vh360_follow_button') && !empty($profile_options['show_header_follow_button'])) {
        $show_follow_button = true;
    }
}
?>

<div class="vh360-client-header">
    <div class="vh360-client-header-content">
        
        <div class="vh360-client-avatar">
            <?php echo get_avatar($author_id, 120); ?>
        </div>
        
        <div class="vh360-client-info">
            <h1 class="vh360-client-name"><?php echo esc_html($author->display_name); ?></h1>
            <p class="vh360-client-type"><?php esc_html_e('Client', 'videohub360-theme'); ?></p>
            
            <?php if ($show_follow_button) : ?>
                <div class="vh360-client-actions">
                    <?php vh360_follow_button($author_id, 'vh360-client-follow-btn'); ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>
