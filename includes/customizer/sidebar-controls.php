<?php
/**
 * Customizer Sidebar Controls
 *
 * Global sidebar defaults for pages, posts, and archives.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register sidebar customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_sidebar_customizer_controls($wp_customize) {
    
    // Add Layout / Sidebar section
    $wp_customize->add_section('vh360_sidebar_settings', array(
        'title'       => __('Layout / Sidebar', 'videohub360-theme'),
        'priority'    => 35,
        'panel'       => 'vh360_global_design',
        'description' => __('Configure global sidebar defaults. These settings can be overridden on individual pages and posts.', 'videohub360-theme'),
    ));
    
    // Get selectable sidebars for dropdown
    $sidebars = vh360_get_selectable_sidebars();
    $sidebar_choices = array();
    foreach ($sidebars as $id => $name) {
        $sidebar_choices[$id] = $name;
    }
    
    // If no sidebars are registered, add a default
    if (empty($sidebar_choices)) {
        $sidebar_choices['sidebar-1'] = __('Primary Sidebar', 'videohub360-theme');
    }
    
    // Layout options for dropdowns
    $layout_choices = array(
        'none'  => __('No Sidebar', 'videohub360-theme'),
        'left'  => __('Left Sidebar', 'videohub360-theme'),
        'right' => __('Right Sidebar', 'videohub360-theme'),
    );
    
    // ======================================
    // PAGES SETTINGS
    // ======================================
    
    $wp_customize->add_setting('vh360_sidebar_layout_page', array(
        'default'           => 'right',
        'sanitize_callback' => 'vh360_sanitize_sidebar_layout',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_layout_page', array(
        'label'       => __('Pages: Sidebar Layout', 'videohub360-theme'),
        'description' => __('Choose the default sidebar layout for pages.', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $layout_choices,
        'priority'    => 10,
    ));
    
    $wp_customize->add_setting('vh360_sidebar_default_page', array(
        'default'           => 'page-sidebar',
        'sanitize_callback' => 'vh360_sanitize_sidebar_choice',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_default_page', array(
        'label'       => __('Pages: Default Sidebar', 'videohub360-theme'),
        'description' => __('Select which sidebar to display on pages.', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $sidebar_choices,
        'priority'    => 20,
    ));
    
    // ======================================
    // POSTS SETTINGS
    // ======================================
    
    $wp_customize->add_setting('vh360_sidebar_layout_post', array(
        'default'           => 'right',
        'sanitize_callback' => 'vh360_sanitize_sidebar_layout',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_layout_post', array(
        'label'       => __('Posts: Sidebar Layout', 'videohub360-theme'),
        'description' => __('Choose the default sidebar layout for blog posts.', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $layout_choices,
        'priority'    => 30,
    ));
    
    $wp_customize->add_setting('vh360_sidebar_default_post', array(
        'default'           => 'post-sidebar',
        'sanitize_callback' => 'vh360_sanitize_sidebar_choice',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_default_post', array(
        'label'       => __('Posts: Default Sidebar', 'videohub360-theme'),
        'description' => __('Select which sidebar to display on blog posts.', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $sidebar_choices,
        'priority'    => 40,
    ));
    
    // ======================================
    // PRODUCTS SETTINGS (WooCommerce)
    // ======================================
    
    if (class_exists('WooCommerce')) {
        $wp_customize->add_setting('vh360_sidebar_layout_product', array(
            'default'           => 'none',
            'sanitize_callback' => 'vh360_sanitize_sidebar_layout',
            'transport'         => 'refresh',
        ));
        
        $wp_customize->add_control('vh360_sidebar_layout_product', array(
            'label'       => __('Products: Sidebar Layout', 'videohub360-theme'),
            'description' => __('Choose the default sidebar layout for WooCommerce product pages.', 'videohub360-theme'),
            'section'     => 'vh360_sidebar_settings',
            'type'        => 'select',
            'choices'     => $layout_choices,
            'priority'    => 45,
        ));
        
        $wp_customize->add_setting('vh360_sidebar_default_product', array(
            'default'           => 'product-sidebar',
            'sanitize_callback' => 'vh360_sanitize_sidebar_choice',
            'transport'         => 'refresh',
        ));
        
        $wp_customize->add_control('vh360_sidebar_default_product', array(
            'label'       => __('Products: Default Sidebar', 'videohub360-theme'),
            'description' => __('Select which sidebar to display on WooCommerce product pages.', 'videohub360-theme'),
            'section'     => 'vh360_sidebar_settings',
            'type'        => 'select',
            'choices'     => $sidebar_choices,
            'priority'    => 46,
        ));
    }
    
    // ======================================
    // ARCHIVES SETTINGS
    // ======================================
    
    $wp_customize->add_setting('vh360_sidebar_layout_archive', array(
        'default'           => 'right',
        'sanitize_callback' => 'vh360_sanitize_sidebar_layout',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_layout_archive', array(
        'label'       => __('Archives: Sidebar Layout', 'videohub360-theme'),
        'description' => __('Choose the default sidebar layout for archive pages (categories, tags, date archives).', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $layout_choices,
        'priority'    => 50,
    ));
    
    $wp_customize->add_setting('vh360_sidebar_default_archive', array(
        'default'           => 'sidebar-1',
        'sanitize_callback' => 'vh360_sanitize_sidebar_choice',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_sidebar_default_archive', array(
        'label'       => __('Archives: Default Sidebar', 'videohub360-theme'),
        'description' => __('Select which sidebar to display on archive pages.', 'videohub360-theme'),
        'section'     => 'vh360_sidebar_settings',
        'type'        => 'select',
        'choices'     => $sidebar_choices,
        'priority'    => 60,
    ));
}
add_action('customize_register', 'vh360_register_sidebar_customizer_controls', 15);

/**
 * Sanitize sidebar layout choice.
 *
 * @param string $value Layout value
 * @return string Sanitized value
 */
function vh360_sanitize_sidebar_layout($value) {
    $valid = array('none', 'left', 'right');
    
    if (in_array($value, $valid, true)) {
        return $value;
    }
    
    return 'right';
}

/**
 * Sanitize sidebar choice.
 *
 * @param string $value Sidebar ID
 * @return string Sanitized value
 */
function vh360_sanitize_sidebar_choice($value) {
    $sidebars = vh360_get_selectable_sidebars();
    
    if (array_key_exists($value, $sidebars)) {
        return $value;
    }
    
    // Default to primary sidebar
    return 'sidebar-1';
}
