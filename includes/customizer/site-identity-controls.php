<?php
/**
 * Site Identity Customizer Controls
 *
 * Adds additional controls to the Site Identity section for text logo styling
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitize integer allowing negative values
 *
 * @param mixed $value The value to sanitize.
 * @return int Sanitized integer value.
 */
function vh360_sanitize_integer($value) {
    return intval($value);
}

/**
 * Sanitize float/decimal value
 *
 * @param mixed $value The value to sanitize.
 * @return float Sanitized float value.
 */
function vh360_sanitize_float($value) {
    return floatval($value);
}

/**
 * Sanitize vertical alignment
 *
 * @param string $value The value to sanitize.
 * @return string Sanitized vertical alignment value.
 */
function vh360_sanitize_vertical_align($value) {
    $valid = array('flex-start', 'center', 'flex-end');
    return in_array($value, $valid, true) ? $value : 'center';
}

/**
 * Register site identity customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_site_identity_controls($wp_customize) {
    // Site title font size
    $wp_customize->add_setting('vh360_site_title_font_size', array(
        'default'           => 24,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_site_title_font_size', array(
        'label'       => __('Site Title Font Size', 'videohub360-theme'),
        'section'     => 'title_tagline',
        'type'        => 'number',
        'priority'    => 70,
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 72,
            'step' => 1,
        ),
        'description' => __('Set the font size for the site title when no logo is set (12-72px).', 'videohub360-theme'),
    ));

    // Site title color
    $wp_customize->add_setting('vh360_site_title_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_site_title_color', array(
        'label'       => __('Site Title Color', 'videohub360-theme'),
        'section'     => 'title_tagline',
        'priority'    => 71,
        'settings'    => 'vh360_site_title_color',
        'description' => __('Choose the color for the site title text.', 'videohub360-theme'),
    )));

    // Site title font weight
    $wp_customize->add_setting('vh360_site_title_font_weight', array(
        'default'           => '700',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_site_title_font_weight', array(
        'label'   => __('Site Title Font Weight', 'videohub360-theme'),
        'section' => 'title_tagline',
        'priority'    => 72,
        'type'    => 'select',
        'choices' => array(
            '400' => __('Normal (400)', 'videohub360-theme'),
            '500' => __('Medium (500)', 'videohub360-theme'),
            '600' => __('Semi-Bold (600)', 'videohub360-theme'),
            '700' => __('Bold (700)', 'videohub360-theme'),
            '800' => __('Extra Bold (800)', 'videohub360-theme'),
        ),
        'description' => __('Set the font weight for the site title.', 'videohub360-theme'),
    ));

    // Site title top margin
    $wp_customize->add_setting('vh360_site_title_top_margin', array(
        'default'           => 0,
        'sanitize_callback' => 'vh360_sanitize_integer',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_site_title_top_margin', array(
        'label'       => __('Site Title Top Margin (px)', 'videohub360-theme'),
        'section'     => 'title_tagline',
        'priority'    => 73,
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => -20,
            'max'  => 40,
            'step' => 1,
        ),
        'description' => __('Adjust vertical position of the site title. Use positive values to move down, negative to move up.', 'videohub360-theme'),
    ));

    // Site title line height
    $wp_customize->add_setting('vh360_site_title_line_height', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_float',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_site_title_line_height', array(
        'label'       => __('Site Title Line Height', 'videohub360-theme'),
        'section'     => 'title_tagline',
        'priority'    => 74,
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0.8,
            'max'  => 2.0,
            'step' => 0.1,
        ),
        'description' => __('Adjust the spacing height of the site title text.', 'videohub360-theme'),
    ));

    // Site title vertical alignment
    $wp_customize->add_setting('vh360_site_title_vertical_align', array(
        'default'           => 'center',
        'sanitize_callback' => 'vh360_sanitize_vertical_align',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_site_title_vertical_align', array(
        'label'   => __('Site Title Vertical Alignment', 'videohub360-theme'),
        'section' => 'title_tagline',
        'priority'    => 75,
        'type'    => 'select',
        'choices' => array(
            'flex-start' => __('Top', 'videohub360-theme'),
            'center'     => __('Center', 'videohub360-theme'),
            'flex-end'   => __('Bottom', 'videohub360-theme'),
        ),
        'description' => __('Align the site title within the header area.', 'videohub360-theme'),
    ));
}
add_action('customize_register', 'vh360_register_site_identity_controls');
