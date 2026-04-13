<?php
/**
 * Sidebar Resolver - Central sidebar logic for the VideoHub360 theme
 *
 * This file contains the centralized sidebar resolution logic that determines:
 * 1. Whether a sidebar should be displayed
 * 2. Which sidebar to display
 * 3. Where the sidebar should appear (left or right)
 *
 * The resolver checks in the following order:
 * 1. Per-page/post meta overrides
 * 2. Global Customizer defaults
 * 3. Forced rules for special pages (WooCommerce, Elementor)
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the list of registered and selectable sidebars.
 *
 * @return array Array of sidebar IDs and names
 */
function vh360_get_selectable_sidebars() {
    global $wp_registered_sidebars;
    
    $sidebars = array();
    
    // Define which sidebars are selectable (exclude footer sidebars and other internal ones)
    $selectable_ids = array(
        'sidebar-1',
        'page-sidebar',
        'post-sidebar',
        'activity-feed-sidebar',
    );
    
    // Add product sidebar if WooCommerce is active
    if (class_exists('WooCommerce')) {
        $selectable_ids[] = 'product-sidebar';
    }
    
    foreach ($selectable_ids as $sidebar_id) {
        if (isset($wp_registered_sidebars[$sidebar_id])) {
            $sidebars[$sidebar_id] = $wp_registered_sidebars[$sidebar_id]['name'];
        }
    }
    
    /**
     * Filter the list of selectable sidebars.
     *
     * @param array $sidebars Array of sidebar IDs and names
     */
    return apply_filters('vh360_selectable_sidebars', $sidebars);
}

/**
 * Resolve sidebar settings for the current page/post.
 *
 * This is the main function that determines sidebar behavior.
 *
 * @param int|null $post_id Optional post ID. Defaults to current post.
 * @return array {
 *     Sidebar configuration array.
 *
 *     @type bool   $show_sidebar Whether to show a sidebar
 *     @type string $sidebar_id   Which sidebar to show
 *     @type string $position     Sidebar position ('left' or 'right')
 * }
 */
function vh360_resolve_sidebar($post_id = null) {
    // Default values
    $result = array(
        'show_sidebar' => true,
        'sidebar_id'   => 'sidebar-1',
        'position'     => 'right',
    );
    
    // Get current post ID if not provided
    if (null === $post_id) {
        $post_id = get_queried_object_id();
    }
    
    // FORCED RULES - These override everything else
    
    // WooCommerce pages – all commerce screens default to no sidebar
    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $result['show_sidebar'] = false;
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }

    // WooCommerce cart, checkout, and account pages (not caught by is_woocommerce)
    if (class_exists('WooCommerce') && (is_cart() || is_checkout() || is_account_page())) {
        $result['show_sidebar'] = false;
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }
    
    // Elementor full-width pages
    if (did_action('elementor/loaded')) {
        $document = \Elementor\Plugin::$instance->documents->get($post_id);
        if ($document) {
            $page_settings = $document->get_settings();
            if (isset($page_settings['page_layout']) && 'elementor_canvas' === $page_settings['page_layout']) {
                $result['show_sidebar'] = false;
                return apply_filters('vh360_sidebar_config', $result, $post_id);
            }
        }
    }
    
    // Video archives and single videos don't need sidebars
    if (is_post_type_archive('videohub360') || 
        is_tax(array('videohub360_category', 'videohub360_series', 'videohub360_location')) ||
        (is_singular('videohub360') && get_post_meta($post_id, '_vh360_context', true) !== 'live_room')) {
        $result['show_sidebar'] = false;
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }
    
    // Dashboard template
    if (is_page_template('template-dashboard.php')) {
        $result['show_sidebar'] = false;
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }
    
    // Activity feed template has its own sidebar
    if (is_page_template('template-activity-feed.php')) {
        $result['show_sidebar'] = true;
        $result['sidebar_id'] = 'activity-feed-sidebar';
        $result['position'] = 'right';
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }
    
    // PER-PAGE OVERRIDES
    if ($post_id) {
        $layout_override = get_post_meta($post_id, '_vh360_sidebar_layout', true);
        $sidebar_override = get_post_meta($post_id, '_vh360_sidebar_choice', true);
        
        // Layout override
        if (!empty($layout_override) && 'inherit' !== $layout_override) {
            if ('none' === $layout_override) {
                $result['show_sidebar'] = false;
                return apply_filters('vh360_sidebar_config', $result, $post_id);
            }
            
            if ('left' === $layout_override || 'right' === $layout_override) {
                $result['position'] = $layout_override;
            }
        }
        
        // Sidebar choice override
        if (!empty($sidebar_override) && 'inherit' !== $sidebar_override) {
            $result['sidebar_id'] = $sidebar_override;
        }
    }
    
    // GLOBAL DEFAULTS from Customizer
    $content_type = vh360_get_content_type();
    
    // Determine the default sidebar based on content type
    $default_sidebar_map = array(
        'page'    => 'page-sidebar',
        'post'    => 'post-sidebar',
        'product' => 'product-sidebar',
        'archive' => 'sidebar-1',
    );
    $default_sidebar = isset($default_sidebar_map[$content_type]) ? $default_sidebar_map[$content_type] : 'sidebar-1';
    
    // Get global layout setting
    $global_layout = get_theme_mod("vh360_sidebar_layout_{$content_type}", 'right');
    if ('none' === $global_layout) {
        $result['show_sidebar'] = false;
        return apply_filters('vh360_sidebar_config', $result, $post_id);
    }
    
    if ('left' === $global_layout || 'right' === $global_layout) {
        $result['position'] = $global_layout;
    }
    
    // Get global sidebar choice
    $global_sidebar = get_theme_mod("vh360_sidebar_default_{$content_type}", $default_sidebar);
    if (!empty($global_sidebar)) {
        $result['sidebar_id'] = $global_sidebar;
    }
    
    /**
     * Filter the final sidebar configuration.
     *
     * @param array $result  Sidebar configuration array
     * @param int   $post_id Current post ID
     */
    return apply_filters('vh360_sidebar_config', $result, $post_id);
}

/**
 * Get the current content type for sidebar defaults.
 *
 * @return string Content type ('page', 'post', 'product', or 'archive')
 */
function vh360_get_content_type() {
    // Check for WooCommerce product pages first (before is_single check)
    if (class_exists('WooCommerce') && is_product()) {
        return 'product';
    }
    
    if (is_page()) {
        return 'page';
    }
    
    if (is_single()) {
        return 'post';
    }
    
    if (is_archive() || is_home()) {
        return 'archive';
    }
    
    return 'page';
}

/**
 * Check if the current page should display a sidebar.
 *
 * @param int|null $post_id Optional post ID
 * @return bool
 */
function vh360_has_sidebar($post_id = null) {
    $config = vh360_resolve_sidebar($post_id);
    return $config['show_sidebar'];
}

/**
 * Get the sidebar ID to display.
 *
 * @param int|null $post_id Optional post ID
 * @return string Sidebar ID
 */
function vh360_get_sidebar_id($post_id = null) {
    $config = vh360_resolve_sidebar($post_id);
    return $config['sidebar_id'];
}

/**
 * Get the sidebar position.
 *
 * @param int|null $post_id Optional post ID
 * @return string Position ('left' or 'right')
 */
function vh360_get_sidebar_position($post_id = null) {
    $config = vh360_resolve_sidebar($post_id);
    return $config['position'];
}

/**
 * Add body class based on sidebar configuration.
 *
 * @param array $classes Existing body classes
 * @return array Modified body classes
 */
function vh360_sidebar_body_classes($classes) {
    if (vh360_has_sidebar()) {
        $position = vh360_get_sidebar_position();
        $classes[] = 'has-sidebar';
        $classes[] = 'sidebar-' . $position;
    } else {
        $classes[] = 'no-sidebar';
    }
    
    return $classes;
}
add_filter('body_class', 'vh360_sidebar_body_classes');
