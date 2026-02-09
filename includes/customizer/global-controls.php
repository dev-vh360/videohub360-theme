<?php
/**
 * Global Customizer Controls
 *
 * Provides customizer settings for global/general theme options.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register global customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_global_controls($wp_customize) {
    // Add Global Settings section
    $wp_customize->add_section('vh360_global_settings', array(
        'title'       => __('Global Settings', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('General theme settings that apply globally across your site.', 'videohub360-theme'),
    ));
    
    // Mobile Orientation Lock Setting
    $wp_customize->add_setting('vh360_enable_orientation_lock', array(
        'default'           => 0,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    
    $wp_customize->add_control('vh360_enable_orientation_lock', array(
        'label'       => __('Force portrait mode on mobile (show rotate message in landscape)', 'videohub360-theme'),
        'description' => __('When enabled, mobile users in landscape orientation will see an overlay message asking them to rotate their device. When disabled, landscape mode works normally.', 'videohub360-theme'),
        'section'     => 'vh360_global_settings',
        'type'        => 'checkbox',
    ));
}
add_action('customize_register', 'vh360_register_global_controls');
