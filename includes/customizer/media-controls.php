<?php
/**
 * Customizer Media Controls
 *
 * Settings for media handling including YouTube playback behavior.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Media settings to the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_media_customize_register($wp_customize) {
    // Add Media Settings section
    $wp_customize->add_section('vh360_media_settings', array(
        'title'       => __('Media Settings', 'videohub360-theme'),
        'priority'    => 160,
        'description' => __('Configure how media content (like YouTube videos) is displayed in posts and comments.', 'videohub360-theme'),
    ));
    
    // YouTube Link Playback Setting
    $wp_customize->add_setting('vh360_youtube_playback', array(
        'default'           => 'inline',
        'sanitize_callback' => 'vh360_sanitize_youtube_playback',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_youtube_playback', array(
        'label'       => __('YouTube Link Playback', 'videohub360-theme'),
        'description' => __('Choose how YouTube links are displayed in posts and comments. "Inline Playable Embed" shows a thumbnail that plays the video inline when clicked. "External YouTube Link" opens YouTube in a new tab.', 'videohub360-theme'),
        'section'     => 'vh360_media_settings',
        'type'        => 'radio',
        'choices'     => array(
            'inline'   => __('Inline Playable Embed (Default, Privacy-Friendly)', 'videohub360-theme'),
            'external' => __('External YouTube Link', 'videohub360-theme'),
        ),
    ));
}
add_action('customize_register', 'vh360_media_customize_register');

/**
 * Sanitize YouTube playback setting.
 *
 * @param string $input The input value.
 * @return string Sanitized value.
 */
function vh360_sanitize_youtube_playback($input) {
    $valid = array('inline', 'external');
    if (in_array($input, $valid, true)) {
        return $input;
    }
    return 'inline'; // Default fallback
}
