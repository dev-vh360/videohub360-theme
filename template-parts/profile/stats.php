<?php
/**
 * Profile Stats Template Part
 *
 * Displays user statistics including video count, total views, and subscribers.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get profile options
$profile_options = get_option('vh360_profile_options', array());
$profile_defaults = array(
    'enable_profiles' => true,
    'show_stats' => true,
);
$profile_options = wp_parse_args($profile_options, $profile_defaults);

// Check if stats are enabled
if (!$profile_options['show_stats']) {
    return;
}

// Get the author being displayed
$author_id = get_queried_object_id();

if (!$author_id) {
    return;
}

// Get user stats
$stats = vh360_get_user_stats($author_id);
$video_count = isset($stats['videos']) ? $stats['videos'] : 0;
$total_views = isset($stats['views']) ? $stats['views'] : 0;
$followers = isset($stats['followers']) ? $stats['followers'] : 0;
$following = isset($stats['following']) ? $stats['following'] : 0;
$likes = isset($stats['likes']) ? $stats['likes'] : 0;

// Get author URL for follower/following links
$author_url = get_author_posts_url($author_id);
?>

<div class="vh360-profile-stats">
    <!-- Videos Count -->
    <div class="vh360-profile-stat">
        <span class="vh360-profile-stat-value"><?php echo esc_html(vh360_format_number($video_count)); ?></span>
        <span class="vh360-profile-stat-label">
            <?php echo esc_html(_n('Video', 'Videos', $video_count, 'videohub360-theme')); ?>
        </span>
    </div>

    <!-- Total Views -->
    <div class="vh360-profile-stat">
        <span class="vh360-profile-stat-value"><?php echo esc_html(vh360_format_number($total_views)); ?></span>
        <span class="vh360-profile-stat-label">
            <?php echo esc_html(_n('View', 'Views', $total_views, 'videohub360-theme')); ?>
        </span>
    </div>

    <!-- Followers (clickable, links to author page followers tab) -->
    <a href="<?php echo esc_url(add_query_arg('tab', 'followers', $author_url)); ?>" 
       class="vh360-profile-stat vh360-profile-stat--link">
        <span class="vh360-profile-stat-value"><?php echo esc_html(vh360_format_number($followers)); ?></span>
        <span class="vh360-profile-stat-label">
            <?php echo esc_html(_n('Follower', 'Followers', $followers, 'videohub360-theme')); ?>
        </span>
    </a>

    <!-- Following (clickable, links to author page following tab) -->
    <a href="<?php echo esc_url(add_query_arg('tab', 'following', $author_url)); ?>" 
       class="vh360-profile-stat vh360-profile-stat--link">
        <span class="vh360-profile-stat-value"><?php echo esc_html(vh360_format_number($following)); ?></span>
        <span class="vh360-profile-stat-label">
            <?php esc_html_e('Following', 'videohub360-theme'); ?>
        </span>
    </a>

    <!-- Likes -->
    <?php if ($likes > 0) : ?>
        <div class="vh360-profile-stat">
            <span class="vh360-profile-stat-value"><?php echo esc_html(vh360_format_number($likes)); ?></span>
            <span class="vh360-profile-stat-label">
                <?php echo esc_html(_n('Like', 'Likes', $likes, 'videohub360-theme')); ?>
            </span>
        </div>
    <?php endif; ?>
</div>
