<?php
/**
 * Profile Navigation Template Part
 *
 * Global navigation component that appears below the profile header on ALL profile-related pages.
 * Provides navigation between different content filters and the connections page.
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

// Get current filter from URL (sanitize and validate against whitelist)
$allowed_filters = array('all', 'photos', 'videos', 'bulletins', 'events');
$current_filter = isset($_GET['filter']) ? sanitize_text_field(wp_unslash($_GET['filter'])) : 'all';
if (!in_array($current_filter, $allowed_filters, true)) {
    $current_filter = 'all';
}

// Check if we're on the connections page
// Determine page type based on multiple reliable indicators
$is_connections_page = false;

// Method 1: Check if this is a page with connections template
if (is_page()) {
    $page_template_slug = get_page_template_slug();
    if ($page_template_slug === 'template-connections.php') {
        $is_connections_page = true;
    }
}

// Method 2: Check global $template as fallback
if (!$is_connections_page) {
    global $template;
    if (isset($template) && basename($template) === 'template-connections.php') {
        $is_connections_page = true;
    }
}

// Find connections page with persistent caching (transients)
$connections_page_cache_key = 'vh360_connections_page_id';
$connections_page_id = get_transient($connections_page_cache_key);

if (false === $connections_page_id) {
    // Use get_posts for better performance than get_pages
    $connections_page = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => 1,
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-connections.php',
        'no_found_rows' => true, // Skip counting total rows for performance
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));
    
    $connections_page_id = !empty($connections_page) ? $connections_page[0]->ID : 0;
    // Cache for 1 hour using transients for persistent caching
    set_transient($connections_page_cache_key, $connections_page_id, HOUR_IN_SECONDS);
}

// Build connections URL (hide link if no connections page exists)
$connections_url = '';
if ($connections_page_id) {
    $connections_url = add_query_arg('user_id', $author_id, get_permalink($connections_page_id));
}

// Define navigation items
$nav_items = array(
    'all_posts' => array(
        'label' => __('All Posts', 'videohub360-theme'),
        'url' => $profile_url,
        'active' => !$is_connections_page && $current_filter === 'all',
    ),
    'photos' => array(
        'label' => __('Photos', 'videohub360-theme'),
        'url' => add_query_arg('filter', 'photos', $profile_url),
        'active' => !$is_connections_page && $current_filter === 'photos',
    ),
    'videos' => array(
        'label' => __('Videos', 'videohub360-theme'),
        'url' => add_query_arg('filter', 'videos', $profile_url),
        'active' => !$is_connections_page && $current_filter === 'videos',
    ),
    'bulletins' => array(
        'label' => __('Bulletins', 'videohub360-theme'),
        'url' => add_query_arg('filter', 'bulletins', $profile_url),
        'active' => !$is_connections_page && $current_filter === 'bulletins',
    ),
    'events' => array(
        'label' => __('Events', 'videohub360-theme'),
        'url' => add_query_arg('filter', 'events', $profile_url),
        'active' => !$is_connections_page && $current_filter === 'events',
    ),
    'connections' => array(
        'label' => __('Connections', 'videohub360-theme'),
        'url' => $connections_url,
        'active' => $is_connections_page,
        'hide' => empty($connections_url), // Hide if no connections page exists
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
