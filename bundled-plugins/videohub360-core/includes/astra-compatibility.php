<?php
/**
 * Astra Theme Compatibility for VideoHub360
 * 
 * Outputs archive header before Astra's container structure
 * to achieve full-width display
 * 
 * @package VideoHub360
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detect if Astra theme is active
 * 
 * @return bool
 */
function videohub360_is_astra_theme() {
    $theme = wp_get_theme();
    return ('Astra' === $theme->name || 'Astra' === $theme->parent_theme);
}

/**
 * Hook into Astra to output header early (before container)
 */
if (videohub360_is_astra_theme()) {
    add_action('astra_content_before', 'videohub360_astra_archive_header', 5);
}

/**
 * Output archive header before Astra's content container
 * Ensures full-width display outside of Astra's constrained wrapper
 */
function videohub360_astra_archive_header() {
    // Only run on videohub360 archive page
    if (!is_post_type_archive('videohub360')) {
        return;
    }
    
    // Check if header should be shown
    $show_header = function_exists('videohub360_show_archive_header') ? videohub360_show_archive_header() : true;
    
    if (!$show_header) {
        return;
    }
    
    // Output header before Astra's container
    ?>
    <div class="videohub360-archive-header">
        <h1 class="videohub360-archive-title">
            <?php echo esc_html( function_exists('videohub360_get_archive_title') ? videohub360_get_archive_title() : __('Archive', 'videohub360') ); ?>
        </h1>
    </div>
    <?php
}
