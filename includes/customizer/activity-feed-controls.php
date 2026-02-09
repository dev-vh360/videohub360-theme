<?php
/**
 * Activity Feed Customizer Controls
 *
 * Color controls for Activity Feed page elements
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function vh360_register_activity_feed_controls($wp_customize) {
    
    // ==========================================
    // ACTIVITY FEED SECTION
    // ==========================================
    $wp_customize->add_section('vh360_activity_feed_design', array(
        'title'       => __('Activity Feed', 'videohub360-theme'),
        'panel'       => 'vh360_components',
        'priority'    => 30,
        'description' => __('Customize colors for Activity Feed page elements. Leave blank to use global theme colors.', 'videohub360-theme'),
    ));

    // Feed Tab Default Color
    $wp_customize->add_setting('vh360_feed_tab_color', array(
        'default'           => '#65676b',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_feed_tab_color', array(
        'label'       => __('Feed Tab Color', 'videohub360-theme'),
        'section'     => 'vh360_activity_feed_design',
        'description' => __('Color for inactive feed tabs (My Feed/Explore)', 'videohub360-theme'),
    )));

    // Feed Tab Hover Color
    $wp_customize->add_setting('vh360_feed_tab_hover_color', array(
        'default'           => '#050505',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_feed_tab_hover_color', array(
        'label'       => __('Feed Tab Hover Color', 'videohub360-theme'),
        'section'     => 'vh360_activity_feed_design',
        'description' => __('Text color when hovering over feed tabs', 'videohub360-theme'),
    )));

    // Mention Color
    $wp_customize->add_setting('vh360_mention_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_mention_color', array(
        'label'       => __('Mention Color', 'videohub360-theme'),
        'section'     => 'vh360_activity_feed_design',
        'description' => __('Color for @mentions in posts and comments', 'videohub360-theme'),
    )));
}
add_action('customize_register', 'vh360_register_activity_feed_controls');
