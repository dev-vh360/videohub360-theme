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
        'title'       => __('Standard Register Page Content', 'videohub360-theme'),
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

    // ========================================
    // Registration Landing Section
    // ========================================

    /**
     * Registration Landing Page Content
     */
    $wp_customize->add_section('vh360_registration_landing_content', array(
        'title'       => __('Registration Landing Page', 'videohub360-theme'),
        'priority'    => 15,
        'description' => __('Customize the account-type selection page shown before users choose a registration form.', 'videohub360-theme'),
    ));

    // Landing Headline
    $wp_customize->add_setting('vh360_registration_landing_headline', array(
        'default'           => __('Choose Your Account Type', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_registration_landing_headline', array(
        'label'   => __('Landing Headline', 'videohub360-theme'),
        'section' => 'vh360_registration_landing_content',
        'type'    => 'text',
    ));

    // Landing Description
    $wp_customize->add_setting('vh360_registration_landing_description', array(
        'default'           => __('Select the registration path that best fits how you plan to use this platform.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_registration_landing_description', array(
        'label'   => __('Landing Description', 'videohub360-theme'),
        'section' => 'vh360_registration_landing_content',
        'type'    => 'textarea',
    ));

    // Registration path toggles
    $registration_landing_toggles = array(
        'professional' => __('Show Service Professional option', 'videohub360-theme'),
        'instructor'   => __('Show Instructor / Educator option', 'videohub360-theme'),
        'client'       => __('Show Client option', 'videohub360-theme'),
    );

    foreach ($registration_landing_toggles as $toggle_key => $toggle_label) {
        $setting_id = "vh360_registration_landing_show_{$toggle_key}";
        $wp_customize->add_setting($setting_id, array(
            'default'           => true,
            'sanitize_callback' => 'vh360_sanitize_checkbox',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control($setting_id, array(
            'label'   => $toggle_label,
            'section' => 'vh360_registration_landing_content',
            'type'    => 'checkbox',
        ));
    }

    // Registration landing card content
    $registration_landing_cards = array(
        'professional' => array(
            'title'       => __('Service Professional', 'videohub360-theme'),
            'description' => __('For healthcare providers, consultants, coaches, organizations, and service professionals who need a business profile, services, availability, and client booking.', 'videohub360-theme'),
            'features'    => array(
                1 => __('Business profile', 'videohub360-theme'),
                2 => __('Services and specialties', 'videohub360-theme'),
                3 => __('Availability and appointments', 'videohub360-theme'),
                4 => __('Client-focused tools', 'videohub360-theme'),
            ),
            'button'      => __('Sign Up as Service Professional', 'videohub360-theme'),
            'label'       => __('Service Professional', 'videohub360-theme'),
        ),
        'instructor' => array(
            'title'       => __('Instructor / Educator', 'videohub360-theme'),
            'description' => __('For course creators, teachers, coaches, and educators who want to build courses, publish lessons, and share learning content.', 'videohub360-theme'),
            'features'    => array(
                1 => __('Create and manage courses', 'videohub360-theme'),
                2 => __('Publish lessons and videos', 'videohub360-theme'),
                3 => __('Build an instructor profile', 'videohub360-theme'),
                4 => __('Share educational resources', 'videohub360-theme'),
            ),
            'button'      => __('Sign Up as Instructor', 'videohub360-theme'),
            'label'       => __('Instructor / Educator', 'videohub360-theme'),
        ),
        'client' => array(
            'title'       => __('Client', 'videohub360-theme'),
            'description' => __('For members, learners, customers, and clients who want to follow creators, book services, access content, and participate in the community.', 'videohub360-theme'),
            'features'    => array(
                1 => __('Follow profiles', 'videohub360-theme'),
                2 => __('Access member content', 'videohub360-theme'),
                3 => __('Book services when available', 'videohub360-theme'),
                4 => __('Join the community', 'videohub360-theme'),
            ),
            'button'      => __('Sign Up as Client', 'videohub360-theme'),
            'label'       => __('Client', 'videohub360-theme'),
        ),
    );

    foreach ($registration_landing_cards as $card_key => $card_defaults) {
        $wp_customize->add_setting("vh360_registration_{$card_key}_title", array(
            'default'           => $card_defaults['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_registration_{$card_key}_title", array(
            'label'   => sprintf(__('%s Card Title', 'videohub360-theme'), $card_defaults['label']),
            'section' => 'vh360_registration_landing_content',
            'type'    => 'text',
        ));

        $wp_customize->add_setting("vh360_registration_{$card_key}_description", array(
            'default'           => $card_defaults['description'],
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_registration_{$card_key}_description", array(
            'label'   => sprintf(__('%s Card Description', 'videohub360-theme'), $card_defaults['label']),
            'section' => 'vh360_registration_landing_content',
            'type'    => 'textarea',
        ));

        for ($i = 1; $i <= 4; $i++) {
            $wp_customize->add_setting("vh360_registration_{$card_key}_feature_{$i}", array(
                'default'           => $card_defaults['features'][$i],
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'postMessage',
            ));
            $wp_customize->add_control("vh360_registration_{$card_key}_feature_{$i}", array(
                'label'   => sprintf(__('%1$s Feature %2$d', 'videohub360-theme'), $card_defaults['label'], $i),
                'section' => 'vh360_registration_landing_content',
                'type'    => 'text',
            ));
        }

        $wp_customize->add_setting("vh360_registration_{$card_key}_button", array(
            'default'           => $card_defaults['button'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_registration_{$card_key}_button", array(
            'label'   => sprintf(__('%s Button Text', 'videohub360-theme'), $card_defaults['label']),
            'section' => 'vh360_registration_landing_content',
            'type'    => 'text',
        ));
    }

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
     * Instructor Registration Form Content
     */
    $wp_customize->add_section('vh360_instructor_register_content', array(
        'title'       => __('Instructor Registration Form', 'videohub360-theme'),
        'priority'    => 17,
        'description' => __('Customize content for the Instructor registration form.', 'videohub360-theme'),
    ));

    // Form Headline
    $wp_customize->add_setting('vh360_instructor_register_headline', array(
        'default'           => __('Instructor Registration', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_instructor_register_headline', array(
        'label'   => __('Form Headline', 'videohub360-theme'),
        'section' => 'vh360_instructor_register_content',
        'type'    => 'text',
    ));

    // Form Description
    $wp_customize->add_setting('vh360_instructor_register_description', array(
        'default'           => __('Create your instructor account and start building courses, lessons, and learning experiences.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_instructor_register_description', array(
        'label'   => __('Form Description', 'videohub360-theme'),
        'section' => 'vh360_instructor_register_content',
        'type'    => 'textarea',
    ));

    // Benefits Heading
    $wp_customize->add_setting('vh360_instructor_register_benefits_heading', array(
        'default'           => __('Instructor Benefits:', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_instructor_register_benefits_heading', array(
        'label'   => __('Benefits Heading', 'videohub360-theme'),
        'section' => 'vh360_instructor_register_content',
        'type'    => 'text',
    ));

    // Benefits (4 items)
    for ($i = 1; $i <= 4; $i++) {
        $defaults = array(
            1 => __('Create and manage courses', 'videohub360-theme'),
            2 => __('Publish lessons and learning content', 'videohub360-theme'),
            3 => __('Build an instructor profile', 'videohub360-theme'),
            4 => __('Share educational videos and resources', 'videohub360-theme'),
        );

        $wp_customize->add_setting("vh360_instructor_register_benefit_{$i}", array(
            'default'           => $defaults[$i],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));
        $wp_customize->add_control("vh360_instructor_register_benefit_{$i}", array(
            'label'   => sprintf(__('Benefit %d', 'videohub360-theme'), $i),
            'section' => 'vh360_instructor_register_content',
            'type'    => 'text',
        ));
    }

    // Button Text
    $wp_customize->add_setting('vh360_instructor_register_button', array(
        'default'           => __('Create Instructor Account', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_instructor_register_button', array(
        'label'   => __('Submit Button Text', 'videohub360-theme'),
        'section' => 'vh360_instructor_register_content',
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
