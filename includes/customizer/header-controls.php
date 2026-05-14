<?php
/**
 * Header Customizer Controls
 *
 * Provides customizer settings for:
 * 1. Main site header (navigation style, sticky header, icon controls)
 * 2. Page headers (visibility toggles and editable titles/descriptions
 *    for Activity Feed, Members Directory and Bulletins archive headers)
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!function_exists('vh360_sanitize_checkbox')) {
    /**
     * Sanitize a checkbox to ensure it returns either 1 or 0.
     *
     * @param mixed $checked The value of the checkbox.
     * @return int Sanitized value (1 or 0).
     */
    function vh360_sanitize_checkbox($checked) {
        return (isset($checked) && (true == $checked || '1' === $checked || 1 === $checked)) ? 1 : 0;
    }
}

/**
 * Sanitize navigation style (horizontal, hamburger, or community)
 *
 * @param string $input The input value.
 * @return string Sanitized value.
 */
function vh360_sanitize_nav_style($input) {
    $valid_values = array('horizontal', 'hamburger', 'community');
    return in_array($input, $valid_values, true) ? $input : 'horizontal';
}

/**
 * Sanitize icon order (comma-separated list)
 *
 * @param string $input The input value.
 * @return string Sanitized value.
 */
function vh360_sanitize_icon_order($input) {
    $valid_icons = array('search', 'cart', 'messages', 'notifications', 'user');
    $input_array = array_map('trim', explode(',', $input));
    $sanitized_array = array();
    
    foreach ($input_array as $icon) {
        if (in_array($icon, $valid_icons, true)) {
            $sanitized_array[] = $icon;
        }
    }
    
    return !empty($sanitized_array) ? implode(',', $sanitized_array) : 'search,cart,messages,notifications,user';
}

/**
 * Register header customizer controls
 *
 * Registers both main site header controls and page header controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_header_controls($wp_customize) {
    // Add Main Header Settings section
    $wp_customize->add_section('vh360_main_header_settings', array(
        'title'       => __('Site Header', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('Configure the main site header, navigation style, sticky behavior, and header icons.', 'videohub360-theme'),
    ));
    
    /* Navigation Style */
    $wp_customize->add_setting('vh360_nav_style', array(
        'default'           => 'horizontal',
        'sanitize_callback' => 'vh360_sanitize_nav_style',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_nav_style', array(
        'label'       => __('Navigation Style', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'radio',
        'choices'     => array(
            'horizontal' => __('Horizontal Navigation', 'videohub360-theme'),
            'hamburger'  => __('Hamburger Menu', 'videohub360-theme'),
            'community'  => __('Community Menu (Left Rail)', 'videohub360-theme'),
        ),
        'description' => __('Choose how the main navigation menu is displayed. Community Menu shows a persistent left sidebar on desktop.', 'videohub360-theme'),
    ));
    
    /* Sticky Header */
    $wp_customize->add_setting('vh360_sticky_header', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_sticky_header', array(
        'label'       => __('Sticky Header', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Keep the header fixed at the top when scrolling.', 'videohub360-theme'),
    ));
    
    /* Hide Header on Auth Pages */
    $wp_customize->add_setting('vh360_hide_header_on_auth_pages', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_hide_header_on_auth_pages', array(
        'label'       => __('Hide Header on Authentication Pages', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Hides header (search, icons, navigation) on Login, Register, Lost Password, and Reset Password pages for a focused authentication experience.', 'videohub360-theme'),
    ));
    
    /* Hide Footer on Auth Pages */
    $wp_customize->add_setting('vh360_hide_footer_on_auth_pages', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_hide_footer_on_auth_pages', array(
        'label'       => __('Hide Footer on Authentication Pages', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Hides footer on Login, Register, Lost Password, and Reset Password pages for a focused authentication experience.', 'videohub360-theme'),
    ));
    
    /* Header Icons Section Header */
    $wp_customize->add_setting('vh360_header_icons_heading', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        'vh360_header_icons_heading',
        array(
            'label'       => '<strong>' . __('Header Icons', 'videohub360-theme') . '</strong>',
            'section'     => 'vh360_main_header_settings',
            'type'        => 'hidden',
            'description' => __('Control which icons appear in the header.', 'videohub360-theme'),
        )
    ));
    
    /* Show Search Icon */
    $wp_customize->add_setting('vh360_show_search_icon', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_show_search_icon', array(
        'label'       => __('Show Centered Search Bar', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display YouTube-style centered search bar with live results.', 'videohub360-theme'),
    ));
    
    /* Group Search Results by Content Type */
    $wp_customize->add_setting('vh360_search_group_results', array(
        'default'           => true,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_search_group_results', array(
        'label'       => __('Group Search Results by Content Type', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('When disabled, search results display as one unified list with no category headings or filter tabs. Only content types available on your site will be shown.', 'videohub360-theme'),
    ));

    /* Include Members in Centered Search */
    $wp_customize->add_setting('vh360_search_include_members', array(
        'default'           => true,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control('vh360_search_include_members', array(
        'label'       => __('Include Members in Centered Search', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('When disabled, members/users will not appear in the Centered Search Bar results or filter tabs.', 'videohub360-theme'),
    ));

    /* Show Cart Icon */
    $wp_customize->add_setting('vh360_show_cart_icon', array(
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_show_cart_icon', array(
        'label'       => __('Show Cart Icon', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display WooCommerce shopping cart icon (only if WooCommerce is active).', 'videohub360-theme'),
    ));
    
    /* Show Messages Icon */
    $wp_customize->add_setting('vh360_show_messages_icon', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_show_messages_icon', array(
        'label'       => __('Show Messages Icon', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display messages icon (logged-in users only).', 'videohub360-theme'),
    ));
    
    /* Show Notifications Icon */
    $wp_customize->add_setting('vh360_show_notifications_icon', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_show_notifications_icon', array(
        'label'       => __('Show Notifications Icon', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display notifications bell icon (logged-in users only).', 'videohub360-theme'),
    ));
    
    /* Show User Menu */
    $wp_customize->add_setting('vh360_show_user_menu', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_show_user_menu', array(
        'label'       => __('Show User Menu', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display user avatar dropdown menu or sign in button.', 'videohub360-theme'),
    ));
    
    /* Show Sign In Button */
    $wp_customize->add_setting('header_show_signin_button', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('header_show_signin_button', array(
        'label'       => __('Show Sign In Button', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display sign in button in the header (when logged out).', 'videohub360-theme'),
    ));
    
    /* Show Register Button */
    $wp_customize->add_setting('header_show_register_button', array(
        'default'           => true,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('header_show_register_button', array(
        'label'       => __('Show Register Button', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'checkbox',
        'description' => __('Display register button in the header (when logged out).', 'videohub360-theme'),
    ));
    
    /* Icon Order */
    $wp_customize->add_setting('vh360_icon_order', array(
        'default'           => 'search,cart,messages,notifications,user',
        'sanitize_callback' => 'vh360_sanitize_icon_order',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('vh360_icon_order', array(
        'label'       => __('Icon Order', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'text',
        'description' => __('Comma-separated list: search,cart,messages,notifications,user', 'videohub360-theme'),
    ));
    
    /* Header Menu Typography Section Header */
    $wp_customize->add_setting('vh360_header_menu_typography_heading', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        'vh360_header_menu_typography_heading',
        array(
            'label'       => '<strong>' . __('Header Menu Typography', 'videohub360-theme') . '</strong>',
            'section'     => 'vh360_main_header_settings',
            'type'        => 'hidden',
            'description' => __('Customize font settings for the header navigation menu.', 'videohub360-theme'),
        )
    ));
    
    /* Header Menu Font Family */
    $wp_customize->add_setting('vh360_header_menu_font_family', array(
        'default'           => '',
        'sanitize_callback' => 'vh360_sanitize_header_menu_font_family',
        'transport'         => 'refresh',
    ));
    
    $font_choices = array_merge(
        array('' => __('Inherit (Body)', 'videohub360-theme')),
        vh360_get_font_choices()
    );
    $wp_customize->add_control('vh360_header_menu_font_family', array(
        'label'       => __('Font Family', 'videohub360-theme'),
        'description' => __('Optional font family for header menu. Leave as "Inherit" to use body font.', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'select',
        'choices'     => $font_choices,
    ));
    
    /* Header Menu Font Size */
    $wp_customize->add_setting('vh360_header_menu_font_size', array(
        'default'           => 16,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_header_menu_font_size', array(
        'label'       => __('Font Size (px)', 'videohub360-theme'),
        'description' => __('Font size for header menu items.', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));
    
    /* Header Menu Font Weight */
    $wp_customize->add_setting('vh360_header_menu_font_weight', array(
        'default'           => 500,
        'sanitize_callback' => 'vh360_sanitize_header_menu_font_weight',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_header_menu_font_weight', array(
        'label'       => __('Font Weight', 'videohub360-theme'),
        'description' => __('Font weight for header menu items.', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'select',
        'choices'     => array(
            400 => __('400 (Normal)', 'videohub360-theme'),
            500 => __('500 (Medium)', 'videohub360-theme'),
            600 => __('600 (Semi-Bold)', 'videohub360-theme'),
            700 => __('700 (Bold)', 'videohub360-theme'),
        ),
    ));
    
    /* Header Menu Text Transform */
    $wp_customize->add_setting('vh360_header_menu_text_transform', array(
        'default'           => 'none',
        'sanitize_callback' => 'vh360_sanitize_header_menu_text_transform',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_header_menu_text_transform', array(
        'label'       => __('Text Transform', 'videohub360-theme'),
        'description' => __('Text transformation for header menu items.', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'select',
        'choices'     => array(
            'none'       => __('None', 'videohub360-theme'),
            'uppercase'  => __('Uppercase', 'videohub360-theme'),
            'capitalize' => __('Capitalize', 'videohub360-theme'),
        ),
    ));
    
    /* Header Menu Letter Spacing */
    $wp_customize->add_setting('vh360_header_menu_letter_spacing', array(
        'default'           => 0,
        'sanitize_callback' => 'vh360_sanitize_header_menu_letter_spacing',
        'transport'         => 'refresh',
    ));
    
    $wp_customize->add_control('vh360_header_menu_letter_spacing', array(
        'label'       => __('Letter Spacing (px)', 'videohub360-theme'),
        'description' => __('Letter spacing for header menu items.', 'videohub360-theme'),
        'section'     => 'vh360_main_header_settings',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => -2,
            'max'  => 5,
            'step' => 0.1,
        ),
    ));
    
    // Add Header Action Colors section
    $wp_customize->add_section('vh360_header_action_colors', array(
        'title'       => __('Header Action Colors', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 31,
        'description' => __('Customize colors for the Sign In/Register buttons, notification bell, and message icon in the site header.', 'videohub360-theme'),
    ));

    /* --- Sign In Button --- */
    $wp_customize->add_setting('vh360_header_signin_bg_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_signin_bg_color',
        array(
            'label'   => __('Sign In: Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_signin_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_signin_text_color',
        array(
            'label'   => __('Sign In: Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_signin_hover_bg_color', array(
        'default'           => '#1e40af',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_signin_hover_bg_color',
        array(
            'label'   => __('Sign In: Hover Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_signin_hover_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_signin_hover_text_color',
        array(
            'label'   => __('Sign In: Hover Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    /* --- Register Button --- */
    $wp_customize->add_setting('vh360_header_register_text_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_register_text_color',
        array(
            'label'   => __('Register: Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_register_border_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_register_border_color',
        array(
            'label'   => __('Register: Border', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_register_hover_bg_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_register_hover_bg_color',
        array(
            'label'   => __('Register: Hover Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_register_hover_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_register_hover_text_color',
        array(
            'label'   => __('Register: Hover Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    /* --- Header Icons (bell & message) --- */
    $wp_customize->add_setting('vh360_header_icon_color', array(
        'default'           => '#1f2937',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_icon_color',
        array(
            'label'   => __('Icon: Normal Color', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_icon_hover_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_icon_hover_color',
        array(
            'label'   => __('Icon: Hover Color', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_icon_hover_bg_color', array(
        'default'           => '#f9fafb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_icon_hover_bg_color',
        array(
            'label'   => __('Icon: Hover Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    /* --- Notification Badge --- */
    $wp_customize->add_setting('vh360_header_notification_badge_bg_color', array(
        'default'           => '#ef4444',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_notification_badge_bg_color',
        array(
            'label'   => __('Notification Badge: Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_notification_badge_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_notification_badge_text_color',
        array(
            'label'   => __('Notification Badge: Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_notification_badge_border_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_notification_badge_border_color',
        array(
            'label'   => __('Notification Badge: Border', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    /* --- Message Badge --- */
    $wp_customize->add_setting('vh360_header_message_badge_bg_color', array(
        'default'           => '#3b82f6',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_message_badge_bg_color',
        array(
            'label'   => __('Message Badge: Background', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_message_badge_text_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_message_badge_text_color',
        array(
            'label'   => __('Message Badge: Text', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    $wp_customize->add_setting('vh360_header_message_badge_border_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control(
        $wp_customize,
        'vh360_header_message_badge_border_color',
        array(
            'label'   => __('Message Badge: Border', 'videohub360-theme'),
            'section' => 'vh360_header_action_colors',
        )
    ));

    // Add Page Header Settings section (existing content headers)
    $wp_customize->add_section('vh360_header_settings', array(
        'title'       => __('Template Headers', 'videohub360-theme'),
        'priority'    => 32,
        'description' => __('Control the visibility and text of headers on Activity Feed, Members Directory, Bulletins, Blog, Course Catalog, and archive pages.', 'videohub360-theme'),
    ));

    /* Activity Feed header settings */
    $wp_customize->add_setting('vh360_show_activity_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_activity_header', array(
        'label'    => __('Show Activity Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
    ));
    $wp_customize->add_setting('vh360_activity_header_title', array(
        'default'           => __('Community Activity', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_activity_header_title', array(
        'label'    => __('Activity Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_activity_header_description', array(
        'default'           => __('Stay up to date with what’s happening in the community', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_activity_header_description', array(
        'label'    => __('Activity Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));

    /* Members Directory header settings */
    $wp_customize->add_setting('vh360_show_members_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_members_header', array(
        'label'    => __('Show Members Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
    ));
    $wp_customize->add_setting('vh360_members_header_title', array(
        'default'           => __('Members Directory', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_members_header_title', array(
        'label'    => __('Members Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_members_header_description', array(
        'default'           => __('Discover and connect with our community members', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_members_header_description', array(
        'label'    => __('Members Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));

    /* Bulletins Archive header settings */
    $wp_customize->add_setting('vh360_show_bulletins_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_bulletins_header', array(
        'label'    => __('Show Bulletins Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
    ));
    $wp_customize->add_setting('vh360_bulletins_header_title', array(
        'default'           => __('Bulletins & Announcements', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_bulletins_header_title', array(
        'label'    => __('Bulletins Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_bulletins_header_description', array(
        'default'           => __('Stay updated with the latest news and important announcements.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_bulletins_header_description', array(
        'label'    => __('Bulletins Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));

    /* Blog Archive header settings */
    $wp_customize->add_setting('vh360_show_blog_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_blog_header', array(
        'label'    => __('Show Blog Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
    ));
    $wp_customize->add_setting('vh360_blog_header_title', array(
        'default'           => __('Blog', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_blog_header_title', array(
        'label'    => __('Blog Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_blog_header_description', array(
        'default'           => __('Discover articles, insights, and updates from our community', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_blog_header_description', array(
        'label'    => __('Blog Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));

    
    /* Live Room header settings */
    $wp_customize->add_setting('vh360_show_live_room_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_live_room_header', array(
        'label'    => __('Show Live Room Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
        'description' => __('Controls the header displayed on the community Live Room template.', 'videohub360-theme'),
    ));
    $wp_customize->add_setting('vh360_live_room_header_title', array(
        'default'           => __('Live Room', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_live_room_header_title', array(
        'label'    => __('Live Room Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_live_room_header_description', array(
        'default'           => __('Join live sessions and broadcasts from the community.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_live_room_header_description', array(
        'label'    => __('Live Room Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
/* Video Archive header settings */
    $wp_customize->add_setting('vh360_show_archive_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_archive_header', array(
        'label'    => __('Show Video Archive Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
        'description' => __('Controls the header displayed on the Videohub360 archive page.', 'videohub360-theme'),
    ));

    /* Events Archive header settings */
    $wp_customize->add_setting('vh360_show_events_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_events_header', array(
        'label'    => __('Show Events Header', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'checkbox',
    ));
    $wp_customize->add_setting('vh360_events_header_title', array(
        'default'           => __('Community Events', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_events_header_title', array(
        'label'    => __('Events Header Title', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));
    $wp_customize->add_setting('vh360_events_header_description', array(
        'default'           => __('Discover and join exciting events in our community', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_events_header_description', array(
        'label'    => __('Events Header Description', 'videohub360-theme'),
        'section'  => 'vh360_header_settings',
        'type'     => 'text',
    ));

    /* Events per page setting */
    $wp_customize->add_setting('vh360_events_per_page', array(
        'default'           => 12,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('vh360_events_per_page', array(
        'label'       => __('Events Per Page', 'videohub360-theme'),
        'section'     => 'vh360_header_settings',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 1,
            'max'  => 100,
            'step' => 1,
        ),
        'description' => __('Number of events to display per page on the events archive.', 'videohub360-theme'),
    ));

    /* Course Catalog header settings */
    $wp_customize->add_setting('vh360_show_course_catalog_header', array(
        'default'           => 1,
        'sanitize_callback' => 'vh360_sanitize_checkbox',
    ));
    $wp_customize->add_control('vh360_show_course_catalog_header', array(
        'label'       => __('Show Course Catalog Header', 'videohub360-theme'),
        'section'     => 'vh360_header_settings',
        'type'        => 'checkbox',
        'description' => __('Controls the header displayed on the Course Catalog page template.', 'videohub360-theme'),
    ));
    $wp_customize->add_setting('vh360_course_catalog_header_title', array(
        'default'           => __('Courses', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_course_catalog_header_title', array(
        'label'   => __('Course Catalog Header Title', 'videohub360-theme'),
        'section' => 'vh360_header_settings',
        'type'    => 'text',
    ));
    $wp_customize->add_setting('vh360_course_catalog_header_description', array(
        'default'           => __('Browse courses, lessons, and learning tracks available on this site.', 'videohub360-theme'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ));
    $wp_customize->add_control('vh360_course_catalog_header_description', array(
        'label'   => __('Course Catalog Header Description', 'videohub360-theme'),
        'section' => 'vh360_header_settings',
        'type'    => 'text',
    ));
}
add_action('customize_register', 'vh360_register_header_controls');
