<?php
/**
 * Auth Pages Customizer Controls (Consolidated)
 *
 * Unified color controls for all authentication pages:
 * Login, Register, Lost Password, Reset Password
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function vh360_register_auth_pages_controls($wp_customize) {
    
    // ==========================================
    // AUTH PAGES SECTION (Consolidated)
    // Panel assignment handled in customizer.php via vh360_customize_assign_panels
    // ==========================================
    $wp_customize->add_section('vh360_auth_pages_design', array(
        'title'       => __('Authentication Pages - Design', 'videohub360-theme'),
        'description' => __('Customize colors for Login, Register, Lost Password, and Reset Password pages. These pages share the same color scheme for consistency.', 'videohub360-theme'),
    ));

    // ==========================================
    // LAYOUT COLORS
    // ==========================================
    
    // Page Background (shared by all auth pages)
    $wp_customize->add_setting('vh360_auth_page_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_page_bg_color', array(
        'label'       => __('Page Background Color', 'videohub360-theme'),
        'section'     => 'vh360_auth_pages_design',
        'description' => __('Background color for all auth pages', 'videohub360-theme'),
    )));

    // Form Background (shared)
    $wp_customize->add_setting('vh360_auth_form_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_form_bg_color', array(
        'label'       => __('Form Background Color', 'videohub360-theme'),
        'section'     => 'vh360_auth_pages_design',
        'description' => __('Background color for auth form cards', 'videohub360-theme'),
    )));

    // ==========================================
    // WELCOME SECTION COLORS
    // ==========================================
    
    $wp_customize->add_setting('vh360_auth_welcome_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_welcome_bg_start', array(
        'label'   => __('Welcome Section Gradient Start', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_welcome_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_welcome_bg_end', array(
        'label'   => __('Welcome Section Gradient End', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_welcome_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_welcome_text_color', array(
        'label'   => __('Welcome Text Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    // ==========================================
    // FORM COLORS
    // ==========================================
    
    $wp_customize->add_setting('vh360_auth_form_title_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_form_title_color', array(
        'label'   => __('Form Title Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_label_color', array(
        'default'           => '#374151',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_label_color', array(
        'label'   => __('Label Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_input_border_color', array(
        'default'           => '#e5e7eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_input_border_color', array(
        'label'   => __('Input Border Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_input_focus_border_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_input_focus_border_color', array(
        'label'   => __('Input Focus Border Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    // ==========================================
    // BUTTON COLORS
    // ==========================================
    
    $wp_customize->add_setting('vh360_auth_button_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_button_bg_start', array(
        'label'   => __('Button Background Gradient Start', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_button_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_button_bg_end', array(
        'label'   => __('Button Background Gradient End', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_button_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_button_text_color', array(
        'label'   => __('Button Text Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    // ==========================================
    // LINK COLORS
    // ==========================================
    
    $wp_customize->add_setting('vh360_auth_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_link_color', array(
        'label'   => __('Link Color', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    // ==========================================
    // MESSAGE COLORS
    // ==========================================
    
    $wp_customize->add_setting('vh360_auth_error_bg_color', array(
        'default'           => '#fee',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_error_bg_color', array(
        'label'   => __('Error Message Background', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_error_text_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_error_text_color', array(
        'label'   => __('Error Message Text', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    $wp_customize->add_setting('vh360_auth_success_bg_color', array(
        'default'           => '#efe',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_auth_success_bg_color', array(
        'label'   => __('Success Message Background', 'videohub360-theme'),
        'section' => 'vh360_auth_pages_design',
    )));

    // ==========================================
    // LOGIN REDIRECT SETTINGS
    // ==========================================

    // Redirect Mode
    $wp_customize->add_setting('vh360_login_redirect_mode', array(
        'default'           => 'default',
        'sanitize_callback' => 'sanitize_key',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('vh360_login_redirect_mode', array(
        'label'       => __('Login Redirect Destination', 'videohub360-theme'),
        'description' => __('Choose where users are redirected after logging in. Gated pages always redirect back to the protected page.', 'videohub360-theme'),
        'section'     => 'vh360_auth_pages_design',
        'type'        => 'select',
        'choices'     => array(
            'default'   => __('Dashboard (current behavior)', 'videohub360-theme'),
            'activity'  => __('Activity Feed', 'videohub360-theme'),
            'profile'   => __('User Profile', 'videohub360-theme'),
            'home'      => __('Homepage', 'videohub360-theme'),
            'previous'  => __('Return to Previous Page', 'videohub360-theme'),
            'custom'    => __('Custom URL', 'videohub360-theme'),
        ),
    ));

    // Custom URL (only shown when mode = custom)
    $wp_customize->add_setting('vh360_login_redirect_custom_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('vh360_login_redirect_custom_url', array(
        'label'       => __('Custom Redirect URL', 'videohub360-theme'),
        'description' => __('Enter the full URL (e.g., https://example.com/welcome)', 'videohub360-theme'),
        'section'     => 'vh360_auth_pages_design',
        'type'        => 'url',
        'active_callback' => function() {
            return get_theme_mod('vh360_login_redirect_mode', 'default') === 'custom';
        },
    ));
}
add_action('customize_register', 'vh360_register_auth_pages_controls');
