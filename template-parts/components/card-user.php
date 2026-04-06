<?php
/**
 * User Card Component
 *
 * Reusable user card component for displaying users in follower/following lists,
 * member directories, and other user listing contexts. Shows avatar, username,
 * bio excerpt, and follow button.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get args with defaults
$args = wp_parse_args($args, array(
    'user_id' => 0,
    'show_avatar' => true,
    'show_bio' => true,
    'show_follow_button' => true,
    'avatar_size' => 64,
));

$user_id = $args['user_id'];

if (!$user_id) {
    return;
}

// Get user data
$user = get_userdata($user_id);
if (!$user) {
    return;
}

$profile_url = vh360_get_profile_url($user_id);
$user_name = $user->display_name;
$user_bio = get_user_meta($user_id, 'description', true);
$current_user_id = get_current_user_id();
?>

<article class="vh360-user-card" data-user-id="<?php echo esc_attr($user_id); ?>">
    <div class="vh360-user-card-content">
        <?php if ($args['show_avatar']) : ?>
            <div class="vh360-user-card-avatar">
                <a href="<?php echo esc_url($profile_url); ?>">
                    <?php echo get_avatar($user_id, $args['avatar_size'], '', $user_name, array('class' => 'vh360-user-avatar-image')); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="vh360-user-card-body">
            <h3 class="vh360-user-card-name">
                <a href="<?php echo esc_url($profile_url); ?>"><?php echo esc_html($user_name); ?></a>
            </h3>
            
            <p class="vh360-user-card-username">@<?php echo esc_html($user->user_login); ?></p>
            
            <?php if ($args['show_bio'] && $user_bio) : ?>
                <p class="vh360-user-card-bio"><?php echo esc_html(wp_trim_words($user_bio, 20, '...')); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($args['show_follow_button'] && is_user_logged_in() && $current_user_id !== $user_id) : ?>
            <div class="vh360-user-card-actions">
                <?php vh360_follow_button($user_id, 'vh360-user-card-follow-btn'); ?>
            </div>
        <?php endif; ?>
    </div>
</article>
