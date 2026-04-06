<?php
/**
 * Profile Navigation Template Part
 *
 * Global navigation component that appears below the profile header on ALL profile-related pages.
 * Provides tab-based navigation between different content types (posts, videos, followers, etc.).
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();
if (!$author_id) {
    return;
}

// Get base profile URL
$profile_url = get_author_posts_url($author_id);

// Get current tab from URL (sanitize and validate against whitelist)
$allowed_tabs = array('posts', 'photos', 'videos', 'events', 'followers', 'following', 'about');
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'posts';
if (!in_array($current_tab, $allowed_tabs, true)) {
    $current_tab = 'posts';
}

// Define navigation items
$nav_items = array(
    'posts' => array(
        'label' => __('Posts', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'posts', $profile_url),
        'active' => $current_tab === 'posts',
    ),
    'photos' => array(
        'label' => __('Photos', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'photos', $profile_url),
        'active' => $current_tab === 'photos',
    ),
    'videos' => array(
        'label' => __('Videos', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'videos', $profile_url),
        'active' => $current_tab === 'videos',
    ),
    'events' => array(
        'label' => __('Events', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'events', $profile_url),
        'active' => $current_tab === 'events',
    ),
    'followers' => array(
        'label' => __('Followers', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'followers', $profile_url),
        'active' => $current_tab === 'followers',
    ),
    'following' => array(
        'label' => __('Following', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'following', $profile_url),
        'active' => $current_tab === 'following',
    ),
    'about' => array(
        'label' => __('About', 'videohub360-theme'),
        'url' => add_query_arg('tab', 'about', $profile_url),
        'active' => $current_tab === 'about',
    ),
);
?>

<div class="vh360-profile-navigation">
    <div class="container">
        <nav class="vh360-profile-nav-tabs" role="navigation" aria-label="<?php esc_attr_e('Profile navigation', 'videohub360-theme'); ?>">
            <?php foreach ($nav_items as $key => $item) : ?>
                <?php if (empty($item['hide'])) : // Skip items marked as hidden ?>
                    <a href="<?php echo esc_url($item['url']); ?>" 
                       class="vh360-profile-nav-tab <?php echo $item['active'] ? 'active' : ''; ?>"
                       <?php echo $item['active'] ? 'aria-current="page"' : ''; ?>>
                        <?php echo esc_html($item['label']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
