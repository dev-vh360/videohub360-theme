<?php
/**
 * Form Content Customizer Controls
 *
 * Allows editing of login and register page content through WordPress Customizer
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fallback sanitize checkbox function. Header controls file defines this as well, but in case
// the order of includes has not loaded it yet, define it here conditionally.
if (!function_exists('vh360_sanitize_checkbox')) {
    /**
     * Sanitize a checkbox to return 1 or 0.
     *
     * @param mixed $checked The checkbox value.
     * @return int Sanitized value (1 or 0).
     */
    function vh360_sanitize_checkbox($checked) {
        return (isset($checked) && (true == $checked || '1' === $checked || 1 === $checked)) ? 1 : 0;
    }
}

/**
 * Register form content customizer controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_form_content_controls($wp_customize) {
    // Add Login Page Section
    $wp_customize->add_section('vh360_login_content', array(
        'title'       => __('Login Page Content', 'videohub360-theme'),
        'priority'    => 10,
        'description' => __('Customize text and features shown on the login page. Use {site_name} as a placeholder for your site name.', 'videohub360-theme'),
    ));

    // Login Headline
    $wp_customize->add_setting('vh360_login_headline', array(
        'default'           => __('Welcome Back!', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_login_headline', array(
        'label'   => __('Login Headline', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
    ));

    // Login Description
    $wp_customize->add_setting('vh360_login_description', array(
        'default'           => __('Sign in to continue to your video platform and connect with your community.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_login_description', array(
        'label'   => __('Login Description', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'textarea',
    ));

    // Login Feature 1
    $wp_customize->add_setting('vh360_login_feature_1', array(
        'default'           => __('Watch Videos', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_login_feature_1', array(
        'label'   => __('Feature 1', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
    ));

    // Login Feature 2
    $wp_customize->add_setting('vh360_login_feature_2', array(
        'default'           => __('Engage & Comment', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_login_feature_2', array(
        'label'   => __('Feature 2', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
    ));

    // Login Feature 3
    $wp_customize->add_setting('vh360_login_feature_3', array(
        'default'           => __('Connect with Others', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_login_feature_3', array(
        'label'   => __('Feature 3', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
    ));

    /*
     * Login Feature Icons
     * Allow site owners to pick an emoji or icon text for each feature.
     */
    $wp_customize->add_setting('vh360_login_icon_1', array(
        'default'           => '📹',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_login_icon_1', array(
        'label'   => __('Feature 1 Icon', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 1.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_login_icon_2', array(
        'default'           => '💬',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_login_icon_2', array(
        'label'   => __('Feature 2 Icon', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 2.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_login_icon_3', array(
        'default'           => '🌐',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_login_icon_3', array(
        'label'   => __('Feature 3 Icon', 'videohub360-theme'),
        'section' => 'vh360_login_content',
        'type'    => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 3.', 'videohub360-theme'),
    ));

    // Add Register Page Section
    $wp_customize->add_section('vh360_register_content', array(
        'title'       => __('Register Page Content', 'videohub360-theme'),
        'priority'    => 20,
        'description' => __('Customize text and benefits shown on the registration page. Use {site_name} as a placeholder for your site name.', 'videohub360-theme'),
    ));

    // Register Headline
    $wp_customize->add_setting('vh360_register_headline', array(
        'default'           => __('Join {site_name}', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_headline', array(
        'label'   => __('Register Headline', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'text',
    ));

    // Register Description
    $wp_customize->add_setting('vh360_register_description', array(
        'default'           => __('Create your account and start your video journey today!', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_description', array(
        'label'   => __('Register Description', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'textarea',
    ));

    // Register Benefit 1
    $wp_customize->add_setting('vh360_register_benefit_1', array(
        'default'           => __('Upload and share your videos', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_benefit_1', array(
        'label'   => __('Benefit 1', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'text',
    ));

    // Register Benefit 2
    $wp_customize->add_setting('vh360_register_benefit_2', array(
        'default'           => __('Comment and engage with content', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_benefit_2', array(
        'label'   => __('Benefit 2', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'text',
    ));

    // Register Benefit 3
    $wp_customize->add_setting('vh360_register_benefit_3', array(
        'default'           => __('Connect with other members', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_benefit_3', array(
        'label'   => __('Benefit 3', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'text',
    ));

    // Register Benefit 4
    $wp_customize->add_setting('vh360_register_benefit_4', array(
        'default'           => __('Build your profile and community', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_register_benefit_4', array(
        'label'   => __('Benefit 4', 'videohub360-theme'),
        'section' => 'vh360_register_content',
        'type'    => 'text',
    ));

    /*
     * Register Benefit Icons
     * Allows the admin to specify icons for each registration benefit.
     */
    $wp_customize->add_setting('vh360_register_icon_1', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_register_icon_1', array(
        'label'       => __('Benefit 1 Icon', 'videohub360-theme'),
        'section'     => 'vh360_register_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Benefit 1.', 'videohub360-theme'),
    ));
    $wp_customize->add_setting('vh360_register_icon_2', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_register_icon_2', array(
        'label'       => __('Benefit 2 Icon', 'videohub360-theme'),
        'section'     => 'vh360_register_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Benefit 2.', 'videohub360-theme'),
    ));
    $wp_customize->add_setting('vh360_register_icon_3', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_register_icon_3', array(
        'label'       => __('Benefit 3 Icon', 'videohub360-theme'),
        'section'     => 'vh360_register_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Benefit 3.', 'videohub360-theme'),
    ));
    $wp_customize->add_setting('vh360_register_icon_4', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_register_icon_4', array(
        'label'       => __('Benefit 4 Icon', 'videohub360-theme'),
        'section'     => 'vh360_register_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Benefit 4.', 'videohub360-theme'),
    ));

    /*
     * Login Design Section
     * Provides color and visibility options for the login form and page.
     */
    $wp_customize->add_section('vh360_login_design', array(
        'title'       => __('Login Page Design', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('Customize the appearance of the login form and page.', 'videohub360-theme'),
    ));
    // Login page background color
    $wp_customize->add_setting('vh360_login_page_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_page_bg_color', array(
        'label'    => __('Login Page Background Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_page_bg_color',
    )));
    // Login form background color
    $wp_customize->add_setting('vh360_login_form_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_form_bg_color', array(
        'label'    => __('Login Form Background Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_form_bg_color',
    )));
    
    // Welcome background gradient start color
    $wp_customize->add_setting('vh360_login_welcome_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_welcome_bg_start', array(
        'label'    => __('Welcome Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_welcome_bg_start',
    )));
    
    // Welcome background gradient end color
    $wp_customize->add_setting('vh360_login_welcome_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_welcome_bg_end', array(
        'label'    => __('Welcome Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_welcome_bg_end',
    )));
    
    // Welcome text color
    $wp_customize->add_setting('vh360_login_welcome_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_welcome_text_color', array(
        'label'    => __('Welcome Text Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_welcome_text_color',
    )));
    
    // Welcome heading color
    $wp_customize->add_setting('vh360_login_welcome_heading_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_welcome_heading_color', array(
        'label'    => __('Welcome Heading Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_welcome_heading_color',
    )));
    
    // Welcome description color
    $wp_customize->add_setting('vh360_login_welcome_description_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_welcome_description_color', array(
        'label'    => __('Welcome Description Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_welcome_description_color',
    )));
    
    // Form title color
    $wp_customize->add_setting('vh360_login_form_title_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_form_title_color', array(
        'label'    => __('Form Title Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_form_title_color',
    )));
    
    // Label color
    $wp_customize->add_setting('vh360_login_label_color', array(
        'default'           => '#374151',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_label_color', array(
        'label'    => __('Label Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_label_color',
    )));
    
    // Input border color
    $wp_customize->add_setting('vh360_login_input_border_color', array(
        'default'           => '#e5e7eb',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_input_border_color', array(
        'label'    => __('Input Border Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_input_border_color',
    )));
    
    // Input focus border color
    $wp_customize->add_setting('vh360_login_input_focus_border_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_input_focus_border_color', array(
        'label'    => __('Input Focus Border Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_input_focus_border_color',
    )));
    
    // Input text color
    $wp_customize->add_setting('vh360_login_input_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_input_text_color', array(
        'label'    => __('Input Text Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_input_text_color',
    )));
    
    // Input background color
    $wp_customize->add_setting('vh360_login_input_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_input_bg_color', array(
        'label'    => __('Input Background Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_input_bg_color',
    )));
    
    // Button background gradient start
    $wp_customize->add_setting('vh360_login_button_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_button_bg_start', array(
        'label'    => __('Button Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_button_bg_start',
    )));
    
    // Button background gradient end
    $wp_customize->add_setting('vh360_login_button_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_button_bg_end', array(
        'label'    => __('Button Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_button_bg_end',
    )));
    
    // Button text color
    $wp_customize->add_setting('vh360_login_button_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_button_text_color', array(
        'label'    => __('Button Text Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_button_text_color',
    )));
    
    // Link color
    $wp_customize->add_setting('vh360_login_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_link_color', array(
        'label'    => __('Link Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_link_color',
    )));
    
    // Link hover color
    $wp_customize->add_setting('vh360_login_link_hover_color', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_link_hover_color', array(
        'label'    => __('Link Hover Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_link_hover_color',
    )));
    
    // Error message background
    $wp_customize->add_setting('vh360_login_error_bg_color', array(
        'default'           => '#fee',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_error_bg_color', array(
        'label'    => __('Error Message Background', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_error_bg_color',
    )));
    
    // Error message text
    $wp_customize->add_setting('vh360_login_error_text_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_error_text_color', array(
        'label'    => __('Error Message Text', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_error_text_color',
    )));
    
    // Error message border
    $wp_customize->add_setting('vh360_login_error_border_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_error_border_color', array(
        'label'    => __('Error Message Border', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_error_border_color',
    )));
    
    // Success message background
    $wp_customize->add_setting('vh360_login_success_bg_color', array(
        'default'           => '#efe',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_success_bg_color', array(
        'label'    => __('Success Message Background', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_success_bg_color',
    )));
    
    // Success message text
    $wp_customize->add_setting('vh360_login_success_text_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_success_text_color', array(
        'label'    => __('Success Message Text', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_success_text_color',
    )));
    
    // Success message border
    $wp_customize->add_setting('vh360_login_success_border_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_success_border_color', array(
        'label'    => __('Success Message Border', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_success_border_color',
    )));
    
    // Secondary text color
    $wp_customize->add_setting('vh360_login_secondary_text_color', array(
        'default'           => '#6b7280',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_secondary_text_color', array(
        'label'    => __('Secondary Text Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_secondary_text_color',
    )));
    
    // Required asterisk color
    $wp_customize->add_setting('vh360_login_required_color', array(
        'default'           => '#dc2626',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_login_required_color', array(
        'label'    => __('Required Asterisk Color', 'videohub360-theme'),
        'section'  => 'vh360_login_design',
        'settings' => 'vh360_login_required_color',
    )));
    
    // Show Remember Me
    $wp_customize->add_setting('vh360_login_show_remember', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_login_show_remember', array(
        'label'   => __('Show "Remember Me" checkbox', 'videohub360-theme'),
        'section' => 'vh360_login_design',
        'type'    => 'checkbox',
    ));
    // Show Forgot Password link
    $wp_customize->add_setting('vh360_login_show_forgot_password', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_login_show_forgot_password', array(
        'label'   => __('Show "Forgot Password?" link', 'videohub360-theme'),
        'section' => 'vh360_login_design',
        'type'    => 'checkbox',
    ));
    // Show Create Account link
    $wp_customize->add_setting('vh360_login_show_create_account', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_login_show_create_account', array(
        'label'   => __('Show "Create Account" link', 'videohub360-theme'),
        'section' => 'vh360_login_design',
        'type'    => 'checkbox',
    ));

    /*
     * Register Design Section
     * Provides color options for the registration form and page.
     */
    $wp_customize->add_section('vh360_register_design', array(
        'title'       => __('Register Page Design', 'videohub360-theme'),
        'priority'    => 40,
        'description' => __('Customize the appearance of the registration form and page.', 'videohub360-theme'),
    ));
    // Register page background color
    $wp_customize->add_setting('vh360_register_page_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_page_bg_color', array(
        'label'    => __('Register Page Background Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_page_bg_color',
    )));
    // Register form background color
    $wp_customize->add_setting('vh360_register_form_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_form_bg_color', array(
        'label'    => __('Register Form Background Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_form_bg_color',
    )));
    
    // Welcome background gradient start color
    $wp_customize->add_setting('vh360_register_welcome_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_welcome_bg_start', array(
        'label'    => __('Welcome Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_welcome_bg_start',
    )));
    
    // Welcome background gradient end color
    $wp_customize->add_setting('vh360_register_welcome_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_welcome_bg_end', array(
        'label'    => __('Welcome Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_welcome_bg_end',
    )));
    
    // Welcome text color
    $wp_customize->add_setting('vh360_register_welcome_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_welcome_text_color', array(
        'label'    => __('Welcome Text Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_welcome_text_color',
    )));
    
    // Welcome heading color
    $wp_customize->add_setting('vh360_register_welcome_heading_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_welcome_heading_color', array(
        'label'    => __('Welcome Heading Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_welcome_heading_color',
    )));
    
    // Welcome description color
    $wp_customize->add_setting('vh360_register_welcome_description_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_welcome_description_color', array(
        'label'    => __('Welcome Description Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_welcome_description_color',
    )));
    
    // Form title color
    $wp_customize->add_setting('vh360_register_form_title_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_form_title_color', array(
        'label'    => __('Form Title Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_form_title_color',
    )));
    
    // Label color
    $wp_customize->add_setting('vh360_register_label_color', array(
        'default'           => '#374151',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_label_color', array(
        'label'    => __('Label Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_label_color',
    )));
    
    // Input border color
    $wp_customize->add_setting('vh360_register_input_border_color', array(
        'default'           => '#e5e7eb',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_input_border_color', array(
        'label'    => __('Input Border Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_input_border_color',
    )));
    
    // Input focus border color
    $wp_customize->add_setting('vh360_register_input_focus_border_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_input_focus_border_color', array(
        'label'    => __('Input Focus Border Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_input_focus_border_color',
    )));
    
    // Input text color
    $wp_customize->add_setting('vh360_register_input_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_input_text_color', array(
        'label'    => __('Input Text Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_input_text_color',
    )));
    
    // Input background color
    $wp_customize->add_setting('vh360_register_input_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_input_bg_color', array(
        'label'    => __('Input Background Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_input_bg_color',
    )));
    
    // Button background gradient start
    $wp_customize->add_setting('vh360_register_button_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_button_bg_start', array(
        'label'    => __('Button Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_button_bg_start',
    )));
    
    // Button background gradient end
    $wp_customize->add_setting('vh360_register_button_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_button_bg_end', array(
        'label'    => __('Button Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_button_bg_end',
    )));
    
    // Button text color
    $wp_customize->add_setting('vh360_register_button_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_button_text_color', array(
        'label'    => __('Button Text Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_button_text_color',
    )));
    
    // Link color
    $wp_customize->add_setting('vh360_register_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_link_color', array(
        'label'    => __('Link Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_link_color',
    )));
    
    // Link hover color
    $wp_customize->add_setting('vh360_register_link_hover_color', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_link_hover_color', array(
        'label'    => __('Link Hover Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_link_hover_color',
    )));
    
    // Error message background
    $wp_customize->add_setting('vh360_register_error_bg_color', array(
        'default'           => '#fee',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_error_bg_color', array(
        'label'    => __('Error Message Background', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_error_bg_color',
    )));
    
    // Error message text
    $wp_customize->add_setting('vh360_register_error_text_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_error_text_color', array(
        'label'    => __('Error Message Text', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_error_text_color',
    )));
    
    // Error message border
    $wp_customize->add_setting('vh360_register_error_border_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_error_border_color', array(
        'label'    => __('Error Message Border', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_error_border_color',
    )));
    
    // Success message background
    $wp_customize->add_setting('vh360_register_success_bg_color', array(
        'default'           => '#efe',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_success_bg_color', array(
        'label'    => __('Success Message Background', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_success_bg_color',
    )));
    
    // Success message text
    $wp_customize->add_setting('vh360_register_success_text_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_success_text_color', array(
        'label'    => __('Success Message Text', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_success_text_color',
    )));
    
    // Success message border
    $wp_customize->add_setting('vh360_register_success_border_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_success_border_color', array(
        'label'    => __('Success Message Border', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_success_border_color',
    )));
    
    // Secondary text color
    $wp_customize->add_setting('vh360_register_secondary_text_color', array(
        'default'           => '#6b7280',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_secondary_text_color', array(
        'label'    => __('Secondary Text Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_secondary_text_color',
    )));
    
    // Required asterisk color
    $wp_customize->add_setting('vh360_register_required_color', array(
        'default'           => '#dc2626',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_register_required_color', array(
        'label'    => __('Required Asterisk Color', 'videohub360-theme'),
        'section'  => 'vh360_register_design',
        'settings' => 'vh360_register_required_color',
    )));

    /*
     * Custom Registration Fields Section
     * Allows admins to add up to two extra fields to the registration form.
     */
    $wp_customize->add_section('vh360_registration_fields', array(
        'title'       => __('Registration Form Fields', 'videohub360-theme'),
        'priority'    => 60,
        'description' => __('Add additional fields to the registration form. Each field requires a label and unique slug.', 'videohub360-theme'),
    ));
    // Loop for two custom fields
    for ($i = 1; $i <= 2; $i++) {
        // Enable toggle
        $wp_customize->add_setting("vh360_custom_field_{$i}_enable", array(
            'default'           => 0,
            'sanitize_callback' => 'vh360_sanitize_checkbox',
        ));
        $wp_customize->add_control("vh360_custom_field_{$i}_enable", array(
            'label'   => sprintf(__('Enable Custom Field %d', 'videohub360-theme'), $i),
            'section' => 'vh360_registration_fields',
            'type'    => 'checkbox',
        ));
        // Field label
        $wp_customize->add_setting("vh360_custom_field_{$i}_label", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("vh360_custom_field_{$i}_label", array(
            'label'       => sprintf(__('Custom Field %d Label', 'videohub360-theme'), $i),
            'section'     => 'vh360_registration_fields',
            'type'        => 'text',
            'description' => __('The label shown for this field in the registration form.', 'videohub360-theme'),
        ));
        // Field slug
        $wp_customize->add_setting("vh360_custom_field_{$i}_slug", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_title',
        ));
        $wp_customize->add_control("vh360_custom_field_{$i}_slug", array(
            'label'       => sprintf(__('Custom Field %d Slug', 'videohub360-theme'), $i),
            'section'     => 'vh360_registration_fields',
            'type'        => 'text',
            'description' => __('A unique key (no spaces) used to store this field as user meta.', 'videohub360-theme'),
        ));
    }

    /*
     * Registration Notifications Section
     * Allows admins to receive email notifications when new users register.
     */
    $wp_customize->add_section('vh360_registration_notifications', array(
        'title'       => __('Registration Notifications', 'videohub360-theme'),
        'priority'    => 65,
        'description' => __('Configure notifications sent when a new user registers.', 'videohub360-theme'),
    ));
    // Toggle for notifications
    $wp_customize->add_setting('vh360_registration_notify', array(
        'default'           => 0,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_registration_notify', array(
        'label'   => __('Send registration notifications', 'videohub360-theme'),
        'section' => 'vh360_registration_notifications',
        'type'    => 'checkbox',
    ));
    // Notification email
    $wp_customize->add_setting('vh360_registration_notify_email', array(
        'default'           => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('vh360_registration_notify_email', array(
        'label'       => __('Notification Email', 'videohub360-theme'),
        'section'     => 'vh360_registration_notifications',
        'type'        => 'text',
        'description' => __('Enter the email address where registration notifications should be sent.', 'videohub360-theme'),
    ));

    /*
     * Lost Password Page Content Section
     * Allows customization of the lost password page content.
     */
    $wp_customize->add_section('vh360_lost_password_content', array(
        'title'       => __('Lost Password Page Content', 'videohub360-theme'),
        'priority'    => 50,
        'description' => __('Customize text and features shown on the lost password page.', 'videohub360-theme'),
    ));

    // Lost Password Headline
    $wp_customize->add_setting('vh360_lost_password_headline', array(
        'default'           => __('Reset Your Password', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_headline', array(
        'label'   => __('Lost Password Headline', 'videohub360-theme'),
        'section' => 'vh360_lost_password_content',
        'type'    => 'text',
    ));

    // Lost Password Description
    $wp_customize->add_setting('vh360_lost_password_description', array(
        'default'           => __('Enter your email address and we\'ll send you a link to reset your password.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_description', array(
        'label'   => __('Lost Password Description', 'videohub360-theme'),
        'section' => 'vh360_lost_password_content',
        'type'    => 'textarea',
    ));

    // Lost Password Feature 1
    $wp_customize->add_setting('vh360_lost_password_feature_1', array(
        'default'           => __('Quick Recovery', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_feature_1', array(
        'label'   => __('Feature 1', 'videohub360-theme'),
        'section' => 'vh360_lost_password_content',
        'type'    => 'text',
    ));

    // Lost Password Feature 2
    $wp_customize->add_setting('vh360_lost_password_feature_2', array(
        'default'           => __('Secure Process', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_feature_2', array(
        'label'   => __('Feature 2', 'videohub360-theme'),
        'section' => 'vh360_lost_password_content',
        'type'    => 'text',
    ));

    // Lost Password Feature 3
    $wp_customize->add_setting('vh360_lost_password_feature_3', array(
        'default'           => __('Easy Access', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_feature_3', array(
        'label'   => __('Feature 3', 'videohub360-theme'),
        'section' => 'vh360_lost_password_content',
        'type'    => 'text',
    ));

    // Lost Password Feature Icons
    $wp_customize->add_setting('vh360_lost_password_icon_1', array(
        'default'           => '🔐',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_icon_1', array(
        'label'       => __('Feature 1 Icon', 'videohub360-theme'),
        'section'     => 'vh360_lost_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 1.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_lost_password_icon_2', array(
        'default'           => '✉️',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_icon_2', array(
        'label'       => __('Feature 2 Icon', 'videohub360-theme'),
        'section'     => 'vh360_lost_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 2.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_lost_password_icon_3', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_lost_password_icon_3', array(
        'label'       => __('Feature 3 Icon', 'videohub360-theme'),
        'section'     => 'vh360_lost_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 3.', 'videohub360-theme'),
    ));

    /*
     * Reset Password Page Content Section
     * Allows customization of the reset password page content.
     */
    $wp_customize->add_section('vh360_reset_password_content', array(
        'title'       => __('Reset Password Page Content', 'videohub360-theme'),
        'priority'    => 60,
        'description' => __('Customize text and features shown on the reset password page.', 'videohub360-theme'),
    ));

    // Reset Password Headline
    $wp_customize->add_setting('vh360_reset_password_headline', array(
        'default'           => __('Create New Password', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_headline', array(
        'label'   => __('Reset Password Headline', 'videohub360-theme'),
        'section' => 'vh360_reset_password_content',
        'type'    => 'text',
    ));

    // Reset Password Description
    $wp_customize->add_setting('vh360_reset_password_description', array(
        'default'           => __('Enter a new password for your account. Make sure it\'s strong and secure.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_description', array(
        'label'   => __('Reset Password Description', 'videohub360-theme'),
        'section' => 'vh360_reset_password_content',
        'type'    => 'textarea',
    ));

    // Reset Password Feature 1
    $wp_customize->add_setting('vh360_reset_password_feature_1', array(
        'default'           => __('Secure Link', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_feature_1', array(
        'label'   => __('Feature 1', 'videohub360-theme'),
        'section' => 'vh360_reset_password_content',
        'type'    => 'text',
    ));

    // Reset Password Feature 2
    $wp_customize->add_setting('vh360_reset_password_feature_2', array(
        'default'           => __('One-Time Use', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_feature_2', array(
        'label'   => __('Feature 2', 'videohub360-theme'),
        'section' => 'vh360_reset_password_content',
        'type'    => 'text',
    ));

    // Reset Password Feature 3
    $wp_customize->add_setting('vh360_reset_password_feature_3', array(
        'default'           => __('Instant Access', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_feature_3', array(
        'label'   => __('Feature 3', 'videohub360-theme'),
        'section' => 'vh360_reset_password_content',
        'type'    => 'text',
    ));

    // Reset Password Feature Icons
    $wp_customize->add_setting('vh360_reset_password_icon_1', array(
        'default'           => '🔒',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_icon_1', array(
        'label'       => __('Feature 1 Icon', 'videohub360-theme'),
        'section'     => 'vh360_reset_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 1.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_reset_password_icon_2', array(
        'default'           => '⏱️',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_icon_2', array(
        'label'       => __('Feature 2 Icon', 'videohub360-theme'),
        'section'     => 'vh360_reset_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 2.', 'videohub360-theme'),
    ));

    $wp_customize->add_setting('vh360_reset_password_icon_3', array(
        'default'           => '✓',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_reset_password_icon_3', array(
        'label'       => __('Feature 3 Icon', 'videohub360-theme'),
        'section'     => 'vh360_reset_password_content',
        'type'        => 'text',
        'description' => __('Enter an emoji or text to display as the icon for Feature 3.', 'videohub360-theme'),
    ));

    /*
     * Lost Password Design Section
     * Provides color and visibility options for the lost password form and page.
     */
    $wp_customize->add_section('vh360_lost_password_design', array(
        'title'       => __('Lost Password Page Design', 'videohub360-theme'),
        'priority'    => 70,
        'description' => __('Customize the appearance of the lost password form and page.', 'videohub360-theme'),
    ));

    // Add color controls for lost password page (matching login/register structure)
    // Page background color
    $wp_customize->add_setting('vh360_lost_password_page_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_page_bg_color', array(
        'label'    => __('Page Background Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_page_bg_color',
    )));

    // Form background color
    $wp_customize->add_setting('vh360_lost_password_form_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_form_bg_color', array(
        'label'    => __('Form Background Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_form_bg_color',
    )));

    // Welcome gradient colors
    $wp_customize->add_setting('vh360_lost_password_welcome_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_welcome_bg_start', array(
        'label'    => __('Welcome Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_welcome_bg_start',
    )));

    $wp_customize->add_setting('vh360_lost_password_welcome_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_welcome_bg_end', array(
        'label'    => __('Welcome Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_welcome_bg_end',
    )));

    // Welcome text colors
    $wp_customize->add_setting('vh360_lost_password_welcome_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_welcome_text_color', array(
        'label'    => __('Welcome Text Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_welcome_text_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_welcome_heading_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_welcome_heading_color', array(
        'label'    => __('Welcome Heading Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_welcome_heading_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_welcome_description_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_welcome_description_color', array(
        'label'    => __('Welcome Description Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_welcome_description_color',
    )));

    // Form colors
    $wp_customize->add_setting('vh360_lost_password_form_title_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_form_title_color', array(
        'label'    => __('Form Title Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_form_title_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_label_color', array(
        'default'           => '#374151',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_label_color', array(
        'label'    => __('Label Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_label_color',
    )));

    // Input colors
    $wp_customize->add_setting('vh360_lost_password_input_border_color', array(
        'default'           => '#e5e7eb',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_input_border_color', array(
        'label'    => __('Input Border Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_input_border_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_input_focus_border_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_input_focus_border_color', array(
        'label'    => __('Input Focus Border Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_input_focus_border_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_input_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_input_text_color', array(
        'label'    => __('Input Text Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_input_text_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_input_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_input_bg_color', array(
        'label'    => __('Input Background Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_input_bg_color',
    )));

    // Button colors
    $wp_customize->add_setting('vh360_lost_password_button_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_button_bg_start', array(
        'label'    => __('Button Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_button_bg_start',
    )));

    $wp_customize->add_setting('vh360_lost_password_button_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_button_bg_end', array(
        'label'    => __('Button Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_button_bg_end',
    )));

    $wp_customize->add_setting('vh360_lost_password_button_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_button_text_color', array(
        'label'    => __('Button Text Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_button_text_color',
    )));

    // Link colors
    $wp_customize->add_setting('vh360_lost_password_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_link_color', array(
        'label'    => __('Link Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_link_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_link_hover_color', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_link_hover_color', array(
        'label'    => __('Link Hover Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_link_hover_color',
    )));

    // Error message colors
    $wp_customize->add_setting('vh360_lost_password_error_bg_color', array(
        'default'           => '#fee',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_error_bg_color', array(
        'label'    => __('Error Message Background', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_error_bg_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_error_text_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_error_text_color', array(
        'label'    => __('Error Message Text', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_error_text_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_error_border_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_error_border_color', array(
        'label'    => __('Error Message Border', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_error_border_color',
    )));

    // Success message colors
    $wp_customize->add_setting('vh360_lost_password_success_bg_color', array(
        'default'           => '#efe',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_success_bg_color', array(
        'label'    => __('Success Message Background', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_success_bg_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_success_text_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_success_text_color', array(
        'label'    => __('Success Message Text', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_success_text_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_success_border_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_success_border_color', array(
        'label'    => __('Success Message Border', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_success_border_color',
    )));

    // Secondary text and required colors
    $wp_customize->add_setting('vh360_lost_password_secondary_text_color', array(
        'default'           => '#6b7280',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_secondary_text_color', array(
        'label'    => __('Secondary Text Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_secondary_text_color',
    )));

    $wp_customize->add_setting('vh360_lost_password_required_color', array(
        'default'           => '#dc2626',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_lost_password_required_color', array(
        'label'    => __('Required Asterisk Color', 'videohub360-theme'),
        'section'  => 'vh360_lost_password_design',
        'settings' => 'vh360_lost_password_required_color',
    )));

    /*
     * Reset Password Design Section
     * Provides color and visibility options for the reset password form and page.
     */
    $wp_customize->add_section('vh360_reset_password_design', array(
        'title'       => __('Reset Password Page Design', 'videohub360-theme'),
        'priority'    => 80,
        'description' => __('Customize the appearance of the reset password form and page.', 'videohub360-theme'),
    ));

    // Add color controls for reset password page (matching lost password structure)
    // Page background color
    $wp_customize->add_setting('vh360_reset_password_page_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_page_bg_color', array(
        'label'    => __('Page Background Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_page_bg_color',
    )));

    // Form background color
    $wp_customize->add_setting('vh360_reset_password_form_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_form_bg_color', array(
        'label'    => __('Form Background Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_form_bg_color',
    )));

    // Welcome gradient colors
    $wp_customize->add_setting('vh360_reset_password_welcome_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_welcome_bg_start', array(
        'label'    => __('Welcome Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_welcome_bg_start',
    )));

    $wp_customize->add_setting('vh360_reset_password_welcome_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_welcome_bg_end', array(
        'label'    => __('Welcome Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_welcome_bg_end',
    )));

    // Welcome text colors
    $wp_customize->add_setting('vh360_reset_password_welcome_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_welcome_text_color', array(
        'label'    => __('Welcome Text Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_welcome_text_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_welcome_heading_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_welcome_heading_color', array(
        'label'    => __('Welcome Heading Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_welcome_heading_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_welcome_description_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_welcome_description_color', array(
        'label'    => __('Welcome Description Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_welcome_description_color',
    )));

    // Form colors
    $wp_customize->add_setting('vh360_reset_password_form_title_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_form_title_color', array(
        'label'    => __('Form Title Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_form_title_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_label_color', array(
        'default'           => '#374151',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_label_color', array(
        'label'    => __('Label Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_label_color',
    )));

    // Input colors
    $wp_customize->add_setting('vh360_reset_password_input_border_color', array(
        'default'           => '#e5e7eb',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_input_border_color', array(
        'label'    => __('Input Border Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_input_border_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_input_focus_border_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_input_focus_border_color', array(
        'label'    => __('Input Focus Border Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_input_focus_border_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_input_text_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_input_text_color', array(
        'label'    => __('Input Text Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_input_text_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_input_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_input_bg_color', array(
        'label'    => __('Input Background Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_input_bg_color',
    )));

    // Button colors
    $wp_customize->add_setting('vh360_reset_password_button_bg_start', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_button_bg_start', array(
        'label'    => __('Button Background Gradient Start', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_button_bg_start',
    )));

    $wp_customize->add_setting('vh360_reset_password_button_bg_end', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_button_bg_end', array(
        'label'    => __('Button Background Gradient End', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_button_bg_end',
    )));

    $wp_customize->add_setting('vh360_reset_password_button_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_button_text_color', array(
        'label'    => __('Button Text Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_button_text_color',
    )));

    // Link colors
    $wp_customize->add_setting('vh360_reset_password_link_color', array(
        'default'           => '#667eea',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_link_color', array(
        'label'    => __('Link Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_link_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_link_hover_color', array(
        'default'           => '#764ba2',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_link_hover_color', array(
        'label'    => __('Link Hover Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_link_hover_color',
    )));

    // Error message colors
    $wp_customize->add_setting('vh360_reset_password_error_bg_color', array(
        'default'           => '#fee',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_error_bg_color', array(
        'label'    => __('Error Message Background', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_error_bg_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_error_text_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_error_text_color', array(
        'label'    => __('Error Message Text', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_error_text_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_error_border_color', array(
        'default'           => '#c00',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_error_border_color', array(
        'label'    => __('Error Message Border', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_error_border_color',
    )));

    // Success message colors
    $wp_customize->add_setting('vh360_reset_password_success_bg_color', array(
        'default'           => '#efe',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_success_bg_color', array(
        'label'    => __('Success Message Background', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_success_bg_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_success_text_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_success_text_color', array(
        'label'    => __('Success Message Text', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_success_text_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_success_border_color', array(
        'default'           => '#070',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_success_border_color', array(
        'label'    => __('Success Message Border', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_success_border_color',
    )));

    // Secondary text and required colors
    $wp_customize->add_setting('vh360_reset_password_secondary_text_color', array(
        'default'           => '#6b7280',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_secondary_text_color', array(
        'label'    => __('Secondary Text Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_secondary_text_color',
    )));

    $wp_customize->add_setting('vh360_reset_password_required_color', array(
        'default'           => '#dc2626',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_reset_password_required_color', array(
        'label'    => __('Required Asterisk Color', 'videohub360-theme'),
        'section'  => 'vh360_reset_password_design',
        'settings' => 'vh360_reset_password_required_color',
    )));
    
    
    // ========================================
    // Business Registration Sections
    // ========================================
    
    /**
     * Business Registration Landing Page Content
     */
    $wp_customize->add_section('vh360_business_landing_content', array(
        'title'       => __('Business Registration Landing', 'videohub360-theme'),
        'priority'    => 15,
        'description' => __('Customize content for the Business registration landing page (choice between Professional and Client).', 'videohub360-theme'),
    ));
    
    // Landing Headline
    $wp_customize->add_setting('vh360_business_landing_headline', array(
        'default'           => __('Join as a Business Professional or Client', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_landing_headline', array(
        'label'   => __('Landing Headline', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Landing Description
    $wp_customize->add_setting('vh360_business_landing_description', array(
        'default'           => __('Choose the account type that best fits your needs', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_landing_description', array(
        'label'   => __('Landing Description', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'textarea',
    ));
    
    // Professional Card Title
    $wp_customize->add_setting('vh360_business_professional_title', array(
        'default'           => __('Professional', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_professional_title', array(
        'label'   => __('Professional Card Title', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Professional Card Description
    $wp_customize->add_setting('vh360_business_professional_description', array(
        'default'           => __('For therapists, consultants, coaches, and service providers looking to showcase their business and connect with clients.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_professional_description', array(
        'label'   => __('Professional Card Description', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'textarea',
    ));
    
    // Professional Features (4 items)
    for ($i = 1; $i <= 4; $i++) {
        $defaults = array(
            1 => __('Business profile with services', 'videohub360-theme'),
            2 => __('Display credentials & specialties', 'videohub360-theme'),
            3 => __('Contact information & booking', 'videohub360-theme'),
            4 => __('Share content & resources', 'videohub360-theme'),
        );
        
        $wp_customize->add_setting("vh360_business_professional_feature_{$i}", array(
            'default'           => $defaults[$i],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_business_professional_feature_{$i}", array(
            'label'   => sprintf(__('Professional Feature %d', 'videohub360-theme'), $i),
            'section' => 'vh360_business_landing_content',
            'type'    => 'text',
        ));
    }
    
    // Professional Button Text
    $wp_customize->add_setting('vh360_business_professional_button', array(
        'default'           => __('Sign Up as Professional', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_professional_button', array(
        'label'   => __('Professional Button Text', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Client Card Title
    $wp_customize->add_setting('vh360_business_client_title', array(
        'default'           => __('Client', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_client_title', array(
        'label'   => __('Client Card Title', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Client Card Description
    $wp_customize->add_setting('vh360_business_client_description', array(
        'default'           => __('For individuals seeking services, engaging with content, and connecting with professionals.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_client_description', array(
        'label'   => __('Client Card Description', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'textarea',
    ));
    
    // Client Features (4 items)
    for ($i = 1; $i <= 4; $i++) {
        $defaults = array(
            1 => __('Simple profile setup', 'videohub360-theme'),
            2 => __('Connect with professionals', 'videohub360-theme'),
            3 => __('Engage with content', 'videohub360-theme'),
            4 => __('Privacy-focused experience', 'videohub360-theme'),
        );
        
        $wp_customize->add_setting("vh360_business_client_feature_{$i}", array(
            'default'           => $defaults[$i],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_business_client_feature_{$i}", array(
            'label'   => sprintf(__('Client Feature %d', 'videohub360-theme'), $i),
            'section' => 'vh360_business_landing_content',
            'type'    => 'text',
        ));
    }
    
    // Client Button Text
    $wp_customize->add_setting('vh360_business_client_button', array(
        'default'           => __('Sign Up as Client', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_client_button', array(
        'label'   => __('Client Button Text', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Footer Text
    $wp_customize->add_setting('vh360_business_landing_footer_text', array(
        'default'           => __('Looking for a different account type?', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_landing_footer_text', array(
        'label'   => __('Footer Alternative Text', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    // Footer Link Text
    $wp_customize->add_setting('vh360_business_landing_footer_link', array(
        'default'           => __('Standard Registration', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_business_landing_footer_link', array(
        'label'   => __('Footer Alternative Link Text', 'videohub360-theme'),
        'section' => 'vh360_business_landing_content',
        'type'    => 'text',
    ));
    
    
    /**
     * Professional Registration Form Content
     */
    $wp_customize->add_section('vh360_professional_register_content', array(
        'title'       => __('Professional Registration Form', 'videohub360-theme'),
        'priority'    => 16,
        'description' => __('Customize content for the Professional registration form.', 'videohub360-theme'),
    ));
    
    // Form Headline
    $wp_customize->add_setting('vh360_professional_register_headline', array(
        'default'           => __('Professional Registration', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_professional_register_headline', array(
        'label'   => __('Form Headline', 'videohub360-theme'),
        'section' => 'vh360_professional_register_content',
        'type'    => 'text',
    ));
    
    // Form Description
    $wp_customize->add_setting('vh360_professional_register_description', array(
        'default'           => __('Create your professional account and start showcasing your services.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_professional_register_description', array(
        'label'   => __('Form Description', 'videohub360-theme'),
        'section' => 'vh360_professional_register_content',
        'type'    => 'textarea',
    ));
    
    // Benefits Heading
    $wp_customize->add_setting('vh360_professional_register_benefits_heading', array(
        'default'           => __('Professional Benefits:', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_professional_register_benefits_heading', array(
        'label'   => __('Benefits Heading', 'videohub360-theme'),
        'section' => 'vh360_professional_register_content',
        'type'    => 'text',
    ));
    
    // Benefits (4 items)
    for ($i = 1; $i <= 4; $i++) {
        $defaults = array(
            1 => __('Business profile with services showcase', 'videohub360-theme'),
            2 => __('Display credentials and specialties', 'videohub360-theme'),
            3 => __('Contact information and booking options', 'videohub360-theme'),
            4 => __('Share content and connect with clients', 'videohub360-theme'),
        );
        
        $wp_customize->add_setting("vh360_professional_register_benefit_{$i}", array(
            'default'           => $defaults[$i],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_professional_register_benefit_{$i}", array(
            'label'   => sprintf(__('Benefit %d', 'videohub360-theme'), $i),
            'section' => 'vh360_professional_register_content',
            'type'    => 'text',
        ));
    }
    
    // Button Text
    $wp_customize->add_setting('vh360_professional_register_button', array(
        'default'           => __('Create Professional Account', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_professional_register_button', array(
        'label'   => __('Submit Button Text', 'videohub360-theme'),
        'section' => 'vh360_professional_register_content',
        'type'    => 'text',
    ));
    
    
    /**
     * Client Registration Form Content
     */
    $wp_customize->add_section('vh360_client_register_content', array(
        'title'       => __('Client Registration Form', 'videohub360-theme'),
        'priority'    => 17,
        'description' => __('Customize content for the Client registration form.', 'videohub360-theme'),
    ));
    
    // Form Headline
    $wp_customize->add_setting('vh360_client_register_headline', array(
        'default'           => __('Client Registration', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_client_register_headline', array(
        'label'   => __('Form Headline', 'videohub360-theme'),
        'section' => 'vh360_client_register_content',
        'type'    => 'text',
    ));
    
    // Form Description
    $wp_customize->add_setting('vh360_client_register_description', array(
        'default'           => __('Create your client account to connect with professionals and engage with content.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_client_register_description', array(
        'label'   => __('Form Description', 'videohub360-theme'),
        'section' => 'vh360_client_register_content',
        'type'    => 'textarea',
    ));
    
    // Benefits Heading
    $wp_customize->add_setting('vh360_client_register_benefits_heading', array(
        'default'           => __('Client Benefits:', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_client_register_benefits_heading', array(
        'label'   => __('Benefits Heading', 'videohub360-theme'),
        'section' => 'vh360_client_register_content',
        'type'    => 'text',
    ));
    
    // Benefits (4 items)
    for ($i = 1; $i <= 4; $i++) {
        $defaults = array(
            1 => __('Simple and private profile', 'videohub360-theme'),
            2 => __('Connect with professionals', 'videohub360-theme'),
            3 => __('Access content and resources', 'videohub360-theme'),
            4 => __('Privacy-focused member experience', 'videohub360-theme'),
        );
        
        $wp_customize->add_setting("vh360_client_register_benefit_{$i}", array(
            'default'           => $defaults[$i],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_client_register_benefit_{$i}", array(
            'label'   => sprintf(__('Benefit %d', 'videohub360-theme'), $i),
            'section' => 'vh360_client_register_content',
            'type'    => 'text',
        ));
    }
    
    // Button Text
    $wp_customize->add_setting('vh360_client_register_button', array(
        'default'           => __('Create Client Account', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_client_register_button', array(
        'label'   => __('Submit Button Text', 'videohub360-theme'),
        'section' => 'vh360_client_register_content',
        'type'    => 'text',
    ));
}
add_action('customize_register', 'vh360_register_form_content_controls');
