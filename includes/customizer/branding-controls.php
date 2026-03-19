<?php
/**
 * Branding Customizer Controls
 *
 * Adds options for controlling branding elements such as logo sizing.
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register branding customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_branding_controls($wp_customize) {
    // Branding Section
    $wp_customize->add_section('vh360_branding', array(
        'title'       => __('Logo Settings', 'videohub360-theme'),
        'priority'    => 25,
        'description' => __('Customize logo display and sizing.', 'videohub360-theme'),
    ));

    // Logo maximum width setting
    $wp_customize->add_setting('vh360_logo_max_width', array(
        'default'           => 220,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_logo_max_width', array(
        'label'       => __('Logo Maximum Width (px)', 'videohub360-theme'),
        'section'     => 'vh360_branding',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 50,
            'max'  => 600,
            'step' => 10,
        ),
    ));
}
add_action('customize_register', 'vh360_register_branding_controls');
