<?php
/**
 * Footer Customizer Controls
 *
 * Adds comprehensive footer customization options to WordPress Customizer
 * including color controls and content controls with placeholder support.
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register footer customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_footer_controls($wp_customize) {
    // Add Footer Settings section (footer colors are now in the main Colors section)
    $wp_customize->add_section('vh360_footer_settings', array(
        'title'       => __('Footer Settings', 'videohub360-theme'),
        'priority'    => 35,
        'description' => __('Customize footer content. Changes preview in real-time.', 'videohub360-theme'),
    ));

    // Copyright Text
    $wp_customize->add_setting('vh360_footer_copyright_text', array(
        'default'           => '© {year} {site_name}. All rights reserved.',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_footer_copyright_text', array(
        'label'       => __('Copyright Text', 'videohub360-theme'),
        'section'     => 'vh360_footer_settings',
        'type'        => 'text',
        'description' => __('Use {year} for current year and {site_name} for site title.', 'videohub360-theme'),
    ));

    // Show Developed By
    $wp_customize->add_setting('vh360_footer_show_powered_by', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_footer_show_powered_by', array(
        'label'   => __('Show Developed By', 'videohub360-theme'),
        'section' => 'vh360_footer_settings',
        'type'    => 'checkbox',
    ));

    // Developed By Text
    $wp_customize->add_setting('vh360_footer_powered_by_text', array(
        'default'           => 'Developed by {videohub360}',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_footer_powered_by_text', array(
        'label'       => __('Developed By Text', 'videohub360-theme'),
        'section'     => 'vh360_footer_settings',
        'type'        => 'text',
        'description' => __('Use {videohub360} for Videohub360.com link.', 'videohub360-theme'),
    ));
}
add_action('customize_register', 'vh360_register_footer_controls');

/**
 * Process footer text placeholders
 *
 * Replaces placeholders in footer text with actual values.
 *
 * @param string $text Text with placeholders.
 * @return string Processed text with replaced placeholders.
 */
function vh360_process_footer_placeholders($text) {
    // Get current year (respects WordPress timezone settings)
    $year = current_time('Y');
    
    // Get site name
    $site_name = get_bloginfo('name');
    
    // WordPress link
    $wordpress_link = '<a href="' . esc_url('https://wordpress.org/') . '">WordPress</a>';
    
    // Videohub360 link
    $videohub360_link = '<a href="' . esc_url('https://videohub360.com') . '">Videohub360</a>';
    
    // Replace placeholders
    $text = str_replace('{year}', $year, $text);
    $text = str_replace('{site_name}', esc_html($site_name), $text);
    $text = str_replace('{wordpress}', $wordpress_link, $text);
    $text = str_replace('{videohub360}', $videohub360_link, $text);
    
    return $text;
}
