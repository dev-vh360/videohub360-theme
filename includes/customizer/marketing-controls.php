<?php
/**
 * Marketing / Lead Capture Customizer controls.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('vh360_sanitize_checkbox')) {
    function vh360_sanitize_checkbox($checked) {
        return (isset($checked) && true == $checked) ? 1 : 0;
    }
}

if (!function_exists('vh360_sanitize_lead_capture_display_mode')) {
    function vh360_sanitize_lead_capture_display_mode($value) {
        $allowed = array('inline', 'popup', 'floating_button', 'footer_banner');
        return in_array($value, $allowed, true) ? $value : 'inline';
    }
}

if (!function_exists('vh360_sanitize_lead_capture_form_source')) {
    function vh360_sanitize_lead_capture_form_source($value) {
        $allowed = array('shortcode', 'embed_html');
        return in_array($value, $allowed, true) ? $value : 'shortcode';
    }
}

if (!function_exists('vh360_sanitize_lead_capture_shortcode')) {
    function vh360_sanitize_lead_capture_shortcode($value) {
        return trim(sanitize_textarea_field($value));
    }
}

if (!function_exists('vh360_sanitize_lead_capture_embed_html')) {
    function vh360_sanitize_lead_capture_embed_html($value) {
        if (function_exists('vh360_lead_capture_allowed_html')) {
            return wp_kses($value, vh360_lead_capture_allowed_html());
        }

        return wp_kses_post($value);
    }
}

/**
 * Register Marketing / Lead Capture Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function vh360_register_marketing_controls($wp_customize) {
    $wp_customize->add_panel('vh360_marketing', array(
        'title'       => __('Marketing', 'videohub360-theme'),
        'priority'    => 65,
        'description' => __('Marketing display integrations such as newsletter and lead capture CTAs.', 'videohub360-theme'),
    ));

    $wp_customize->add_section('vh360_marketing_lead_capture', array(
        'title'       => __('Lead Capture', 'videohub360-theme'),
        'panel'       => 'vh360_marketing',
        'priority'    => 10,
        'description' => __('Display a styled newsletter or marketing signup CTA using a shortcode or safe embed HTML. Subscriber storage, list management, and email sending are handled by your form plugin or email marketing provider.', 'videohub360-theme'),
    ));

    $settings = array(
        'vh360_lead_capture_enabled' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_display_mode' => array('default' => 'inline', 'sanitize' => 'vh360_sanitize_lead_capture_display_mode'),
        'vh360_lead_capture_form_source' => array('default' => 'shortcode', 'sanitize' => 'vh360_sanitize_lead_capture_form_source'),
        'vh360_lead_capture_shortcode' => array('default' => '', 'sanitize' => 'vh360_sanitize_lead_capture_shortcode'),
        'vh360_lead_capture_embed_html' => array('default' => '', 'sanitize' => 'vh360_sanitize_lead_capture_embed_html'),
        'vh360_lead_capture_headline' => array('default' => __('Stay connected', 'videohub360-theme'), 'sanitize' => 'sanitize_text_field'),
        'vh360_lead_capture_description' => array('default' => __('Get updates, announcements, and new content delivered to your inbox.', 'videohub360-theme'), 'sanitize' => 'sanitize_textarea_field'),
        'vh360_lead_capture_button_text' => array('default' => __('Sign up', 'videohub360-theme'), 'sanitize' => 'sanitize_text_field'),
        'vh360_lead_capture_consent_text' => array('default' => __('By signing up, you agree to receive updates and marketing emails.', 'videohub360-theme'), 'sanitize' => 'sanitize_textarea_field'),
        'vh360_lead_capture_success_message' => array('default' => __('Thanks for signing up.', 'videohub360-theme'), 'sanitize' => 'sanitize_text_field'),
        'vh360_lead_capture_hide_logged_in' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_hide_after_dismiss' => array('default' => 1, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_frequency_days' => array('default' => 7, 'sanitize' => 'absint'),
        'vh360_lead_capture_popup_delay' => array('default' => 5, 'sanitize' => 'absint'),
        'vh360_lead_capture_show_homepage' => array('default' => 1, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_activity_feed' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_members_directory' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_course_catalog' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_single_video' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_single_post' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_shop' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_product' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_show_all_site' => array('default' => 0, 'sanitize' => 'vh360_sanitize_checkbox'),
        'vh360_lead_capture_excluded_pages' => array('default' => '', 'sanitize' => 'sanitize_text_field'),
    );

    foreach ($settings as $setting_id => $args) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $args['default'],
            'sanitize_callback' => $args['sanitize'],
        ));
    }

    $wp_customize->add_control('vh360_lead_capture_enabled', array(
        'label'   => __('Enable Lead Capture CTA', 'videohub360-theme'),
        'section' => 'vh360_marketing_lead_capture',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_control('vh360_lead_capture_display_mode', array(
        'label'   => __('Display Mode', 'videohub360-theme'),
        'section' => 'vh360_marketing_lead_capture',
        'type'    => 'select',
        'choices' => array(
            'inline'          => __('Inline block', 'videohub360-theme'),
            'popup'           => __('Popup modal', 'videohub360-theme'),
            'floating_button' => __('Floating button', 'videohub360-theme'),
            'footer_banner'   => __('Footer banner', 'videohub360-theme'),
        ),
    ));

    $wp_customize->add_control('vh360_lead_capture_form_source', array(
        'label'   => __('Form Source', 'videohub360-theme'),
        'section' => 'vh360_marketing_lead_capture',
        'type'    => 'select',
        'choices' => array(
            'shortcode'  => __('Shortcode', 'videohub360-theme'),
            'embed_html' => __('Safe embed HTML', 'videohub360-theme'),
        ),
    ));

    $wp_customize->add_control('vh360_lead_capture_shortcode', array(
        'label'       => __('Form Shortcode', 'videohub360-theme'),
        'description' => __('Paste a Contact Form 7, Mailchimp, MC4WP, Brevo, ConvertKit, or other provider shortcode.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'textarea',
    ));

    $wp_customize->add_control('vh360_lead_capture_embed_html', array(
        'label'       => __('Safe Embed HTML', 'videohub360-theme'),
        'description' => __('Paste trusted form/embed HTML. Script tags are stripped for security in this version.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'textarea',
    ));

    $text_controls = array(
        'vh360_lead_capture_headline' => __('Headline', 'videohub360-theme'),
        'vh360_lead_capture_description' => __('Description', 'videohub360-theme'),
        'vh360_lead_capture_button_text' => __('Floating Button Text', 'videohub360-theme'),
        'vh360_lead_capture_consent_text' => __('Consent Text', 'videohub360-theme'),
        'vh360_lead_capture_success_message' => __('Success Message', 'videohub360-theme'),
    );

    foreach ($text_controls as $control_id => $label) {
        $wp_customize->add_control($control_id, array(
            'label'   => $label,
            'section' => 'vh360_marketing_lead_capture',
            'type'    => in_array($control_id, array('vh360_lead_capture_description', 'vh360_lead_capture_consent_text'), true) ? 'textarea' : 'text',
        ));
    }

    $wp_customize->add_control('vh360_lead_capture_hide_logged_in', array(
        'label'   => __('Hide for logged-in users', 'videohub360-theme'),
        'section' => 'vh360_marketing_lead_capture',
        'type'    => 'checkbox',
    ));

    $wp_customize->add_control('vh360_lead_capture_hide_after_dismiss', array(
        'label'       => __('Hide after dismissal', 'videohub360-theme'),
        'description' => __('When enabled, dismissed popups, floating CTAs, and footer banners remain hidden until the frequency period expires.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'checkbox',
    ));

    $wp_customize->add_control('vh360_lead_capture_frequency_days', array(
        'label'       => __('Dismissal Frequency (days)', 'videohub360-theme'),
        'description' => __('Number of days before showing the CTA again after dismissal.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'number',
        'input_attrs' => array('min' => 0, 'step' => 1),
    ));

    $wp_customize->add_control('vh360_lead_capture_popup_delay', array(
        'label'       => __('Popup Delay (seconds)', 'videohub360-theme'),
        'description' => __('Delay in seconds before popup appears.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'number',
        'input_attrs' => array('min' => 0, 'step' => 1),
    ));

    $location_controls = array(
        'vh360_lead_capture_show_homepage' => __('Homepage', 'videohub360-theme'),
        'vh360_lead_capture_show_activity_feed' => __('Activity Feed', 'videohub360-theme'),
        'vh360_lead_capture_show_members_directory' => __('Members Directory', 'videohub360-theme'),
        'vh360_lead_capture_show_course_catalog' => __('Course Catalog', 'videohub360-theme'),
        'vh360_lead_capture_show_single_video' => __('Single Video', 'videohub360-theme'),
        'vh360_lead_capture_show_single_post' => __('Single Blog Post', 'videohub360-theme'),
        'vh360_lead_capture_show_shop' => __('WooCommerce Shop', 'videohub360-theme'),
        'vh360_lead_capture_show_product' => __('WooCommerce Product', 'videohub360-theme'),
        'vh360_lead_capture_show_all_site' => __('All site', 'videohub360-theme'),
    );

    foreach ($location_controls as $control_id => $label) {
        $wp_customize->add_control($control_id, array(
            'label'   => $label,
            'section' => 'vh360_marketing_lead_capture',
            'type'    => 'checkbox',
        ));
    }

    $wp_customize->add_control('vh360_lead_capture_excluded_pages', array(
        'label'       => __('Excluded Pages', 'videohub360-theme'),
        'description' => __('Comma-separated page IDs or slugs where the lead capture CTA should not display.', 'videohub360-theme'),
        'section'     => 'vh360_marketing_lead_capture',
        'type'        => 'text',
    ));
}
add_action('customize_register', 'vh360_register_marketing_controls');
