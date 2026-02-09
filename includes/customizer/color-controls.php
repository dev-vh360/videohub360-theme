<?php
/**
 * Color Customizer Controls
 *
 * Comprehensive color controls for the theme organized in a single section
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register color customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_color_controls($wp_customize) {
    
    // ==========================================
    // MAIN COLORS SECTION
    // ==========================================
    $wp_customize->add_section('vh360_colors', array(
        'title'       => __('Colors', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('Customize all theme colors. Changes preview in real-time.', 'videohub360-theme'),
    ));

    // All color settings with defaults, labels, and descriptions
    $colors = array(
        // Brand Colors
        'vh360_primary_color' => array(
            'default'     => '#2563eb',
            'label'       => __('Primary Color', 'videohub360-theme'),
            'description' => __('Main brand color - buttons, links, active states', 'videohub360-theme'),
            'priority'    => 10,
        ),
        'vh360_secondary_color' => array(
            'default'     => '#1e40af',
            'label'       => __('Secondary Color', 'videohub360-theme'),
            'description' => __('Supporting color - hover states, secondary elements', 'videohub360-theme'),
            'priority'    => 20,
        ),
        'vh360_accent_color' => array(
            'default'     => '#f59e0b',
            'label'       => __('Accent Color', 'videohub360-theme'),
            'description' => __('Highlights and featured elements', 'videohub360-theme'),
            'priority'    => 30,
        ),
        
        // Text Colors
        'vh360_text_color' => array(
            'default'     => '#1f2937',
            'label'       => __('Main Text Color', 'videohub360-theme'),
            'description' => __('Body text, headings, main content', 'videohub360-theme'),
            'priority'    => 40,
        ),
        'vh360_text_light_color' => array(
            'default'     => '#6b7280',
            'label'       => __('Light Text Color', 'videohub360-theme'),
            'description' => __('Secondary text, metadata, captions', 'videohub360-theme'),
            'priority'    => 50,
        ),
        
        // Background Colors
        'vh360_bg_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Main Background', 'videohub360-theme'),
            'description' => __('Page background, card backgrounds', 'videohub360-theme'),
            'priority'    => 60,
        ),
        'vh360_bg_light_color' => array(
            'default'     => '#f9fafb',
            'label'       => __('Light Background', 'videohub360-theme'),
            'description' => __('Alternate sections, subtle backgrounds', 'videohub360-theme'),
            'priority'    => 70,
        ),
        'vh360_border_color' => array(
            'default'     => '#e5e7eb',
            'label'       => __('Border Color', 'videohub360-theme'),
            'description' => __('Dividers, card borders, input borders', 'videohub360-theme'),
            'priority'    => 80,
        ),
        
        // Page Header Colors
        'vh360_header_bg_color' => array(
            'default'     => '#667eea',
            'label'       => __('Page Header Background Start', 'videohub360-theme'),
            'description' => __('Template page headers - gradient start', 'videohub360-theme'),
            'priority'    => 90,
        ),
        'vh360_header_bg_color_end' => array(
            'default'     => '#764ba2',
            'label'       => __('Page Header Background End', 'videohub360-theme'),
            'description' => __('Template page headers - gradient end', 'videohub360-theme'),
            'priority'    => 100,
        ),
        
        // Navigation Colors
        'vh360_nav_link_color' => array(
            'default'     => '#1f2937',
            'label'       => __('Navigation Link Color', 'videohub360-theme'),
            'description' => __('Main navigation menu links', 'videohub360-theme'),
            'priority'    => 110,
        ),
        
        // Hamburger Menu Colors
        'vh360_hamburger_bg_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Hamburger Menu Background', 'videohub360-theme'),
            'description' => __('Mobile menu panel background', 'videohub360-theme'),
            'priority'    => 120,
        ),
        'vh360_hamburger_text_color' => array(
            'default'     => '#1f2937',
            'label'       => __('Hamburger Menu Text', 'videohub360-theme'),
            'description' => __('Mobile menu item text', 'videohub360-theme'),
            'priority'    => 130,
        ),
        'vh360_hamburger_hover_bg_color' => array(
            'default'     => '#f9fafb',
            'label'       => __('Hamburger Menu Hover BG', 'videohub360-theme'),
            'description' => __('Mobile menu hover background', 'videohub360-theme'),
            'priority'    => 140,
        ),
        'vh360_hamburger_active_color' => array(
            'default'     => '#2563eb',
            'label'       => __('Hamburger Menu Active', 'videohub360-theme'),
            'description' => __('Active menu item text color', 'videohub360-theme'),
            'priority'    => 150,
        ),
        'vh360_hamburger_icon_color' => array(
            'default'     => '#1f2937',
            'label'       => __('Hamburger Icon Color', 'videohub360-theme'),
            'description' => __('Menu toggle icon color', 'videohub360-theme'),
            'priority'    => 160,
        ),
        
        // Button Colors
        'vh360_button_bg_color' => array(
            'default'     => '#2563eb',
            'label'       => __('Button Background', 'videohub360-theme'),
            'description' => __('Primary buttons, submit buttons', 'videohub360-theme'),
            'priority'    => 170,
        ),
        'vh360_button_text_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Button Text', 'videohub360-theme'),
            'description' => __('Button text color', 'videohub360-theme'),
            'priority'    => 180,
        ),
        'vh360_button_hover_bg_color' => array(
            'default'     => '#1e40af',
            'label'       => __('Button Hover Background', 'videohub360-theme'),
            'description' => __('Button background on hover', 'videohub360-theme'),
            'priority'    => 190,
        ),
        'vh360_button_hover_text_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Button Hover Text', 'videohub360-theme'),
            'description' => __('Button text color on hover', 'videohub360-theme'),
            'priority'    => 200,
        ),
        
        // Status Colors
        'vh360_success_color' => array(
            'default'     => '#10b981',
            'label'       => __('Success Color', 'videohub360-theme'),
            'description' => __('Success messages, confirmations', 'videohub360-theme'),
            'priority'    => 210,
        ),
        'vh360_error_color' => array(
            'default'     => '#ef4444',
            'label'       => __('Error Color', 'videohub360-theme'),
            'description' => __('Error messages, validation errors', 'videohub360-theme'),
            'priority'    => 220,
        ),
        'vh360_warning_color' => array(
            'default'     => '#f59e0b',
            'label'       => __('Warning Color', 'videohub360-theme'),
            'description' => __('Warning messages, caution notices', 'videohub360-theme'),
            'priority'    => 230,
        ),
        'vh360_info_color' => array(
            'default'     => '#6366f1',
            'label'       => __('Info Color', 'videohub360-theme'),
            'description' => __('Info messages, notifications', 'videohub360-theme'),
            'priority'    => 240,
        ),
        
        // Footer Colors
        'vh360_footer_bg_color' => array(
            'default'     => '#1f2937',
            'label'       => __('Footer Background', 'videohub360-theme'),
            'description' => __('Background color of footer area', 'videohub360-theme'),
            'priority'    => 250,
        ),
        'vh360_footer_text_color' => array(
            'default'     => '#f9fafb',
            'label'       => __('Footer Text', 'videohub360-theme'),
            'description' => __('General text in footer', 'videohub360-theme'),
            'priority'    => 260,
        ),
        'vh360_footer_link_color' => array(
            'default'     => '#f9fafb',
            'label'       => __('Footer Link Color', 'videohub360-theme'),
            'description' => __('Color of footer navigation links', 'videohub360-theme'),
            'priority'    => 270,
        ),
        'vh360_footer_link_hover_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Footer Link Hover', 'videohub360-theme'),
            'description' => __('Color when hovering over footer links', 'videohub360-theme'),
            'priority'    => 280,
        ),
    );

    // Register each color control
    foreach ($colors as $setting_id => $args) {
        // Add setting
        $wp_customize->add_setting($setting_id, array(
            'default'           => $args['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        // Add control
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $setting_id, array(
            'label'       => $args['label'],
            'section'     => 'vh360_colors',
            'description' => $args['description'],
            'priority'    => $args['priority'],
        )));
    }
}
add_action('customize_register', 'vh360_register_color_controls');
