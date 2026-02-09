<?php
/**
 * Typography Customizer Controls
 *
 * Adds font family, size, and line height controls to WordPress Customizer
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get available font choices
 *
 * @return array Array of font choices.
 */
function vh360_get_font_choices() {
    return array(
        'system'      => __('System Font Stack', 'videohub360-theme'),
        'Roboto'      => 'Roboto',
        'Open Sans'   => 'Open Sans',
        'Lato'        => 'Lato',
        'Montserrat'  => 'Montserrat',
        'Raleway'     => 'Raleway',
        'Poppins'     => 'Poppins',
        'Nunito'      => 'Nunito',
        'Playfair Display' => 'Playfair Display',
        'Merriweather' => 'Merriweather',
        'PT Sans'     => 'PT Sans',
        'Source Sans Pro' => 'Source Sans Pro',
    );
}

/**
 * Register typography customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_typography_controls($wp_customize) {
    // Add Typography Section
    $wp_customize->add_section('vh360_typography', array(
        'title'       => __('Typography', 'videohub360-theme'),
        'priority'    => 35,
        'description' => __('Customize fonts and typography settings.', 'videohub360-theme'),
    ));

    // Font choices
    $font_choices = vh360_get_font_choices();

    // Heading Font Family
    $wp_customize->add_setting('vh360_heading_font', array(
        'default'           => 'system',
        'sanitize_callback' => 'vh360_sanitize_font_choice',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_heading_font', array(
        'label'    => __('Heading Font', 'videohub360-theme'),
        'section'  => 'vh360_typography',
        'type'     => 'select',
        'choices'  => $font_choices,
    ));

    // Body Font Family
    $wp_customize->add_setting('vh360_body_font', array(
        'default'           => 'system',
        'sanitize_callback' => 'vh360_sanitize_font_choice',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_body_font', array(
        'label'    => __('Body Font', 'videohub360-theme'),
        'section'  => 'vh360_typography',
        'type'     => 'select',
        'choices'  => $font_choices,
    ));

    // Base Font Size
    $wp_customize->add_setting('vh360_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_font_size', array(
        'label'       => __('Base Font Size (px)', 'videohub360-theme'),
        'section'     => 'vh360_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));

    // Line Height
    $wp_customize->add_setting('vh360_line_height', array(
        'default'           => '1.6',
        'sanitize_callback' => 'vh360_sanitize_line_height',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_line_height', array(
        'label'       => __('Line Height', 'videohub360-theme'),
        'section'     => 'vh360_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1.0,
            'max'  => 2.5,
            'step' => 0.1,
        ),
    ));
}
add_action('customize_register', 'vh360_register_typography_controls');

/**
 * Sanitize font choice
 *
 * @param string $input Font choice.
 * @return string Sanitized font choice.
 */
function vh360_sanitize_font_choice($input) {
    $valid = array_keys(vh360_get_font_choices());
    return in_array($input, $valid, true) ? $input : 'system';
}

/**
 * Sanitize line height
 *
 * @param string $input Line height value.
 * @return string Sanitized line height.
 */
function vh360_sanitize_line_height($input) {
    $value = floatval($input);
    return ($value >= 1.0 && $value <= 2.5) ? strval($value) : '1.6';
}
