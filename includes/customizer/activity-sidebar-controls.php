<?php
/**
 * Customizer Controls for Activity Feed Sidebar
 *
 * Adds settings to control sidebar widgets visibility and ad content.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register Activity Feed Sidebar Customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_activity_sidebar_customizer($wp_customize) {
    
    // Add Activity Feed Sidebar Section
    // Panel assignment handled in customizer.php via vh360_customize_assign_panels
    $wp_customize->add_section('vh360_activity_sidebar', array(
        'title'       => __('Activity Feed Sidebar', 'videohub360-theme'),
        'description' => __('Control the widgets that appear in the activity feed right sidebar.', 'videohub360-theme'),
    ));
    
    // Who to Follow Title
    $wp_customize->add_setting('vh360_who_to_follow_title', array(
        'default'           => __('Who to Follow', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_who_to_follow_title', array(
        'label'       => __('Who to Follow - Title', 'videohub360-theme'),
        'description' => __('Customize the widget title', 'videohub360-theme'),
        'section'     => 'vh360_activity_sidebar',
        'type'        => 'text',
        'priority'    => 10,
    ));
    
    // Show Recommended Users Setting
    $wp_customize->add_setting('vh360_show_recommended_users', array(
        'default'           => true,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_show_recommended_users', array(
        'label'       => __('Show Who to Follow', 'videohub360-theme'),
        'description' => __('Displays 5 users with the highest follower counts. Requires users with vh360_followers_count meta. Only shows to logged-in users.', 'videohub360-theme'),
        'section'     => 'vh360_activity_sidebar',
        'type'        => 'checkbox',
        'priority'    => 20,
    ));
    
    // Show Ad Space Setting
    $wp_customize->add_setting('vh360_show_activity_ad_space', array(
        'default'           => false,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_show_activity_ad_space', array(
        'label'       => __('Show Ad Space', 'videohub360-theme'),
        'description' => __('Enable ad space in the activity feed sidebar. Configure ad content via Appearance → Widgets → Activity Feed Ad Slot', 'videohub360-theme'),
        'section'     => 'vh360_activity_sidebar',
        'type'        => 'checkbox',
        'priority'    => 30,
    ));

    // Activity ad privacy classification.
    $wp_customize->add_setting('vh360_activity_ad_privacy_type', array(
        'default'           => 'contextual',
        'sanitize_callback' => 'vh360_sanitize_activity_ad_privacy_type',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('vh360_activity_ad_privacy_type', array(
        'label'       => __('Activity Ad Privacy Type', 'videohub360-theme'),
        'description' => __('Choose contextual for first-party/contextual creative. Choose personalized when the widget may include tracking, profiling, retargeting, pixels, or cross-site advertising.', 'videohub360-theme'),
        'section'     => 'vh360_activity_sidebar',
        'type'        => 'select',
        'choices'     => array(
            'contextual'   => __('Contextual', 'videohub360-theme'),
            'personalized' => __('Personalized', 'videohub360-theme'),
        ),
        'priority'    => 35,
    ));
}
add_action('customize_register', 'vh360_register_activity_sidebar_customizer');


/**
 * Sanitize Activity Feed ad privacy type.
 *
 * @param string $value Submitted value.
 * @return string
 */
function vh360_sanitize_activity_ad_privacy_type($value) {
    return in_array($value, array('contextual', 'personalized'), true) ? $value : 'contextual';
}
