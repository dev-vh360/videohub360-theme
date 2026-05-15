<?php
/**
 * Customizer: Author Template Mode Controls
 *
 * Adds a setting to choose between Profile (social-first) and Channel (video-first) 
 * modes for author pages.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register author template mode customizer settings
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_author_template_controls($wp_customize) {
    
    // Add section for Author Template Mode
    // Panel assignment handled in customizer.php via vh360_customize_assign_panels
    $wp_customize->add_section('vh360_author_template', array(
        'title'       => esc_html__('Author Template Mode', 'videohub360-theme'),
        'description' => esc_html__('Choose how author pages display site-wide. Profile mode is social-first, Channel mode is video-first, Business mode is professional/service-first, and Course mode is instructor/learning-first.', 'videohub360-theme'),
        'capability'  => 'edit_theme_options',
    ));
    
    // Setting: Author Template Mode
    $wp_customize->add_setting('vh360_author_template_mode', array(
        'default'           => 'profile',
        'sanitize_callback' => 'vh360_sanitize_author_template_mode',
        'transport'         => 'refresh',
        'capability'        => 'edit_theme_options',
    ));
    
    // Control: Author Template Mode (Radio buttons)
    $wp_customize->add_control('vh360_author_template_mode', array(
        'label'       => esc_html__('Author Page Display Mode', 'videohub360-theme'),
        'description' => esc_html__('Select how author/profile pages should be displayed across your site for creator accounts.', 'videohub360-theme'),
        'section'     => 'vh360_author_template',
        'type'        => 'radio',
        'choices'     => array(
            'profile' => esc_html__('Profile Mode (Social Media Style) - Shows posts, videos, photos, events, and social activity', 'videohub360-theme'),
            'channel' => esc_html__('Channel Mode (YouTube Style) - Shows videos only with grid layout and playlists', 'videohub360-theme'),
            'business' => esc_html__('Business Mode (Professional Style) - Shows services, credentials, and contact information', 'videohub360-theme'),
            'course' => esc_html__('Course Mode (Instructor / Learning Platform Style) - Shows courses, lessons, instructor bio, and learning-focused profile sections', 'videohub360-theme'),
        ),
        'priority'    => 10,
    ));
    
}
add_action('customize_register', 'vh360_register_author_template_controls');

/**
 * Sanitize author template mode setting
 *
 * @param string $input The input value.
 * @return string Sanitized value.
 */
function vh360_sanitize_author_template_mode($input) {
    $valid = array('profile', 'channel', 'business', 'course');
    
    if (in_array($input, $valid, true)) {
        return $input;
    }
    
    return 'profile'; // Default fallback
}
