<?php
/**
 * The sidebar template
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use the sidebar resolver to determine what to display
$sidebar_config = vh360_resolve_sidebar();

// Don't display sidebar if not needed
if (!$sidebar_config['show_sidebar']) {
    return;
}

// Get the sidebar ID to display
$sidebar_id = $sidebar_config['sidebar_id'];

// Check if the sidebar is active
if (!is_active_sidebar($sidebar_id)) {
    return;
}
?>

<aside id="secondary" class="widget-area sidebar-<?php echo esc_attr($sidebar_config['position']); ?>">
    <?php dynamic_sidebar($sidebar_id); ?>
</aside><!-- #secondary -->
