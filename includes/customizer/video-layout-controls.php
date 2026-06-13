<?php
/**
 * Customizer VideoHub360 single video layout controls.
 *
 * Exposes core plugin option-backed layout defaults in the Customizer so the
 * plugin admin settings and Customizer remain synchronized.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine whether VideoHub360 Core appears active enough to expose controls.
 *
 * @return bool
 */
function vh360_customizer_video_layout_core_active() {
    return defined('VIDEOHUB360_VERSION')
        || function_exists('videohub360_get_single_video_layout')
        || post_type_exists('videohub360');
}

/**
 * Sanitize a VideoHub360 single video layout setting for the Customizer.
 *
 * Uses the core plugin sanitizer when available, with a local fallback so the
 * Customizer never fatals if VideoHub360 Core is inactive or loads later.
 *
 * @param string $value   Raw setting value.
 * @param array  $allowed Allowed values.
 * @param string $default Default fallback value.
 * @return string
 */
function vh360_sanitize_video_layout_option_value($value, $allowed, $default) {
    if (function_exists('videohub360_sanitize_single_video_layout_value')) {
        return videohub360_sanitize_single_video_layout_value($value, $allowed, $default);
    }

    $value = sanitize_key((string) $value);

    if (in_array($value, $allowed, true)) {
        return $value;
    }

    return in_array($default, $allowed, true) ? $default : 'sidebar';
}

/**
 * Sanitize the normal single video layout default.
 *
 * @param string $value Raw setting value.
 * @return string
 */
function vh360_sanitize_single_video_layout_default($value) {
    return vh360_sanitize_video_layout_option_value($value, array('sidebar', 'full-width'), 'sidebar');
}

/**
 * Sanitize the course lesson layout default.
 *
 * @param string $value Raw setting value.
 * @return string
 */
function vh360_sanitize_course_lesson_layout_default($value) {
    return vh360_sanitize_video_layout_option_value($value, array('inherit', 'sidebar', 'full-width'), 'full-width');
}

/**
 * Sanitize the livestream video layout default.
 *
 * @param string $value Raw setting value.
 * @return string
 */
function vh360_sanitize_livestream_video_layout_default($value) {
    return vh360_sanitize_video_layout_option_value($value, array('inherit', 'sidebar', 'full-width'), 'full-width');
}

/**
 * Register VideoHub360 single video layout option-backed Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 * @return void
 */
function vh360_register_video_layout_customizer_controls($wp_customize) {
    if (!vh360_customizer_video_layout_core_active()) {
        return;
    }

    $wp_customize->add_section('vh360_single_video_layout', array(
        'title'       => __('VideoHub360 Single Video Layout', 'videohub360-theme'),
        'description' => __('Controls the default layout for VideoHub360 single video pages. These settings control the internal VideoHub360 video sidebar layout, not the WordPress theme widget sidebar. Individual videos can override these defaults from the Sidebar Configuration panel.', 'videohub360-theme'),
        'priority'    => 36,
        'panel'       => 'vh360_global_design',
    ));

    $wp_customize->add_setting('videohub360_single_video_layout_default', array(
        'default'           => 'sidebar',
        'type'              => 'option',
        'sanitize_callback' => 'vh360_sanitize_single_video_layout_default',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'videohub360_single_video_layout_default', array(
        'label'       => __('Single Video Layout', 'videohub360-theme'),
        'description' => __('Default layout for normal VideoHub360 single video pages.', 'videohub360-theme'),
        'section'     => 'vh360_single_video_layout',
        'type'        => 'select',
        'choices'     => array(
            'sidebar'    => __('Sidebar Layout', 'videohub360-theme'),
            'full-width' => __('Full Width Layout', 'videohub360-theme'),
        ),
        'priority'    => 10,
    )));

    $wp_customize->add_setting('videohub360_course_lesson_layout_default', array(
        'default'           => 'full-width',
        'type'              => 'option',
        'sanitize_callback' => 'vh360_sanitize_course_lesson_layout_default',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'videohub360_course_lesson_layout_default', array(
        'label'       => __('Course Lesson Layout', 'videohub360-theme'),
        'description' => __('Default layout for VideoHub360 course lessons. Choose full width for a focused learning experience.', 'videohub360-theme'),
        'section'     => 'vh360_single_video_layout',
        'type'        => 'select',
        'choices'     => array(
            'inherit'    => __('Inherit Single Video Layout', 'videohub360-theme'),
            'sidebar'    => __('Sidebar Layout', 'videohub360-theme'),
            'full-width' => __('Full Width Layout', 'videohub360-theme'),
        ),
        'priority'    => 20,
    )));

    $wp_customize->add_setting('videohub360_livestream_video_layout_default', array(
        'default'           => 'full-width',
        'type'              => 'option',
        'sanitize_callback' => 'vh360_sanitize_livestream_video_layout_default',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'videohub360_livestream_video_layout_default', array(
        'label'       => __('Livestream Video Layout', 'videohub360-theme'),
        'description' => __('Default layout for VideoHub360 livestream videos and live course sessions.', 'videohub360-theme'),
        'section'     => 'vh360_single_video_layout',
        'type'        => 'select',
        'choices'     => array(
            'inherit'    => __('Inherit Single Video Layout', 'videohub360-theme'),
            'sidebar'    => __('Sidebar Layout', 'videohub360-theme'),
            'full-width' => __('Full Width Layout', 'videohub360-theme'),
        ),
        'priority'    => 30,
    )));
}
add_action('customize_register', 'vh360_register_video_layout_customizer_controls', 15);
