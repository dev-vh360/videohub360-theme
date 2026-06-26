<?php
/**
 * Elementor Support
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Early return if Elementor is not active to prevent errors.
// The function_exists check prevents calling an undefined function.
if ( ! function_exists( 'vh360_is_elementor_active' ) ) {
    return;
}

if ( ! vh360_is_elementor_active() ) {
    return;
}

/**
 * Register Elementor locations
 */
function videohub360_theme_register_elementor_locations($elementor_theme_manager) {
    $elementor_theme_manager->register_all_core_location();
}
add_action('elementor/theme/register_locations', 'videohub360_theme_register_elementor_locations');

/**
 * Add Elementor support
 */
add_theme_support('elementor');

/**
 * Support for Elementor Pro features
 */
add_theme_support('elementor-pro');

/**
 * Support for Elementor theme locations
 */
function videohub360_theme_elementor_theme_support() {
    // Add theme support for header and footer
    if (did_action('elementor/loaded')) {
        add_theme_support('elementor', array(
            'header' => true,
            'footer' => true,
            'single' => true,
            'archive' => true,
        ));
    }
}
add_action('after_setup_theme', 'videohub360_theme_elementor_theme_support');

/**
 * Note: Elementor widget settings are managed by Elementor itself.
 * Theme should not override default widget settings to avoid conflicts.
 * Users can configure video settings through Elementor's widget controls.
 */

/**
 * Add custom CSS for Elementor widgets
 */
function videohub360_theme_elementor_custom_css() {
    if (did_action('elementor/loaded')) {
        wp_add_inline_style('elementor-frontend', '
            .elementor-widget-video-playlist .elementor-video-playlist {
                max-width: 100%;
            }
            .elementor-video-wrapper {
                overflow: hidden;
                border-radius: var(--border-radius, 8px);
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'videohub360_theme_elementor_custom_css', 20);

/**
 * Elementor canvas template support
 */
function videohub360_theme_elementor_canvas_support() {
    if (is_page_template('elementor_canvas')) {
        remove_action('wp_head', 'wp_enqueue_scripts', 1);
        remove_action('wp_footer', 'wp_print_footer_scripts', 20);
    }
}
add_action('template_redirect', 'videohub360_theme_elementor_canvas_support');

/**
 * Add Elementor compatibility class to body
 */
function videohub360_theme_add_elementor_body_class($classes) {
    if (did_action('elementor/loaded')) {
        $classes[] = 'elementor-compatible';
        
        // Add class if using Elementor canvas template
        if (is_page_template('elementor_canvas')) {
            $classes[] = 'elementor-canvas';
        }
    }
    return $classes;
}
add_filter('body_class', 'videohub360_theme_add_elementor_body_class');

/**
 * Register custom Elementor widgets for Videohub360
 */
function videohub360_theme_register_elementor_widgets() {
    // Check if Elementor and Videohub360 are both active
    if (did_action('elementor/loaded') && class_exists('VideoHub360_Core')) {
        // Widgets are registered by the Videohub360 plugin itself
        // This hook is here for future custom widget additions
        do_action('videohub360_theme_elementor_widgets_registered');
    }
}
add_action('elementor/widgets/register', 'videohub360_theme_register_elementor_widgets');

/**
 * Enqueue Elementor preview styles
 */
function videohub360_theme_elementor_preview_styles() {
    // Use public API method to check if in preview mode
    if (did_action('elementor/loaded') && \Elementor\Plugin::$instance->preview->is_preview_mode()) {
        wp_enqueue_style(
            'videohub360-theme-elementor-preview',
            get_template_directory_uri() . '/assets/css/elementor-preview.css',
            array(),
            vh360_theme_asset_version('assets/css/elementor-preview.css')
        );
    }
}
add_action('elementor/preview/enqueue_styles', 'videohub360_theme_elementor_preview_styles');
