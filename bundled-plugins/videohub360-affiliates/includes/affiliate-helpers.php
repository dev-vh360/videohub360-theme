<?php
/**
 * Shared helper functions for the VideoHub360 Affiliates plugin.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

/**
 * Return all supported payout methods.
 *
 * @return array
 */
function vh360_affiliates_get_all_payout_methods() {
    return array(
        'paypal'        => __( 'PayPal', 'videohub360-affiliates' ),
        'zelle'         => __( 'Zelle', 'videohub360-affiliates' ),
        'cashapp'       => __( 'Cash App', 'videohub360-affiliates' ),
        'bank_transfer' => __( 'Bank Transfer', 'videohub360-affiliates' ),
        'other'         => __( 'Other', 'videohub360-affiliates' ),
    );
}

/**
 * Return default enabled payout methods.
 *
 * @return array
 */
function vh360_affiliates_get_default_enabled_payout_methods() {
    return array( 'paypal', 'zelle', 'cashapp', 'other' );
}

/**
 * Return admin-enabled payout methods.
 *
 * @return array
 */
function vh360_affiliates_get_enabled_payout_methods() {
    $settings    = vh360_affiliates_get_settings();
    $all_methods = vh360_affiliates_get_all_payout_methods();

    $enabled = isset( $settings['enabled_payout_methods'] ) && is_array( $settings['enabled_payout_methods'] )
        ? $settings['enabled_payout_methods']
        : vh360_affiliates_get_default_enabled_payout_methods();

    $enabled = array_values( array_intersect( $enabled, array_keys( $all_methods ) ) );

    if ( empty( $enabled ) ) {
        $enabled = array( 'other' );
    }

    $methods = array();

    foreach ( $enabled as $method_key ) {
        if ( isset( $all_methods[ $method_key ] ) ) {
            $methods[ $method_key ] = $all_methods[ $method_key ];
        }
    }

    return $methods;
}

/**
 * Check whether a payout method is enabled.
 *
 * @param string $method Payout method key.
 * @return bool
 */
function vh360_affiliates_is_payout_method_enabled( $method ) {
    $enabled = vh360_affiliates_get_enabled_payout_methods();
    return isset( $enabled[ $method ] );
}

/**
 * Get a safe payout method value, validating against enabled methods.
 *
 * @param string $method Submitted payout method.
 * @return string
 */
function vh360_affiliates_sanitize_payout_method( $method ) {
    $method  = sanitize_key( $method );
    $enabled = vh360_affiliates_get_enabled_payout_methods();

    if ( isset( $enabled[ $method ] ) ) {
        return $method;
    }

    $enabled_keys  = array_keys( $enabled );
    $first_enabled = ! empty( $enabled_keys ) ? reset( $enabled_keys ) : '';

    return $first_enabled ?: 'other';
}

/**
 * Get payout method label.
 *
 * @param string $method Payout method key.
 * @return string
 */
function vh360_affiliates_get_payout_method_label( $method ) {
    $all_methods = vh360_affiliates_get_all_payout_methods();
    return isset( $all_methods[ $method ] ) ? $all_methods[ $method ] : ucwords( str_replace( '_', ' ', $method ) );
}

/**
 * Return the plugin's saved settings with defaults merged in.
 *
 * @return array
 */
function vh360_affiliates_get_settings() {
    static $cached = null;

    if ( null !== $cached ) {
        return $cached;
    }

    $defaults = array(
        'enabled'                  => 0,
        'require_manual_approval'  => 1,
        'referral_query_var'       => 'ref',
        'cookie_duration'          => 30,
        'attribution_model'        => 'first_click',
        'default_commission_type'  => 'percentage',
        'default_commission_rate'  => 20.00,
        'commission_status'        => 'pending',
        'auto_approve_days'        => 0,
        'min_payout_amount'        => 50.00,
        'allow_self_referrals'     => 0,
        'payout_instructions'      => '',
        'terms_page_url'           => '',
        'visit_retention_days'     => 180,
        'email_notifications'      => 1,
        'email_from_name'          => '',
        'email_from_email'         => '',
        'email_reply_to'           => '',
        'enabled_payout_methods'            => vh360_affiliates_get_default_enabled_payout_methods(),

        // Registration page text.
        'registration_eyebrow'              => __( 'Partner Program', 'videohub360-affiliates' ),
        'registration_heading'              => __( 'Become a VideoHub360 Affiliate', 'videohub360-affiliates' ),
        'registration_description'          => __( 'Earn commission when you refer new customers. Promote VideoHub360 with your referral link and track your clicks, referrals, and commissions from your dashboard.', 'videohub360-affiliates' ),
        'registration_benefit_1'            => __( 'Earn commission on eligible purchases', 'videohub360-affiliates' ),
        'registration_benefit_2'            => __( 'Track referrals from your dashboard', 'videohub360-affiliates' ),
        'registration_benefit_3'            => __( 'Simple referral link sharing', 'videohub360-affiliates' ),
        'registration_benefit_4'            => __( 'Manual payout records', 'videohub360-affiliates' ),
        'registration_form_heading'         => __( 'Apply Now', 'videohub360-affiliates' ),
        'registration_payout_method_hint'   => __( 'Choose how you would prefer to receive affiliate payouts.', 'videohub360-affiliates' ),
        'registration_payout_details_label' => __( 'Payout Details', 'videohub360-affiliates' ),
        'registration_payout_details_hint'  => __( 'Enter the email, phone number, $Cashtag, or instructions needed to send your payout using your selected method.', 'videohub360-affiliates' ),
        'registration_submit_button'        => __( 'Submit Application', 'videohub360-affiliates' ),
        'registration_success_heading'      => __( 'Application Submitted', 'videohub360-affiliates' ),
        'registration_terms_link_text'      => __( 'Affiliate Terms', 'videohub360-affiliates' ),
        'registration_terms_notice'         => __( 'By applying, you agree to our {terms_link}.', 'videohub360-affiliates' ),
    );
    $saved    = get_option( 'vh360_affiliates_settings', array() );
    $cached   = wp_parse_args( $saved, $defaults );
    return $cached;
}

/**
 * Get a single setting text value, falling back to a provided default.
 *
 * @param string $key      Setting key.
 * @param string $fallback Fallback value if the setting is empty.
 * @return string
 */
function vh360_affiliates_get_setting_text( $key, $fallback = '' ) {
    $settings = vh360_affiliates_get_settings();

    if ( isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) && '' !== trim( $settings[ $key ] ) ) {
        return $settings[ $key ];
    }

    return $fallback;
}

/**
 * Generate a unique, lowercase affiliate code.
 *
 * @param string $base Optional base string (e.g. username).
 * @return string
 */
function vh360_affiliates_generate_code($base = '') {
    $reserved = array(
        'admin', 'administrator', 'login', 'logout', 'register',
        'checkout', 'cart', 'account', 'support', 'dashboard',
        'videohub360', 'vh360',
    );

    $base = strtolower(preg_replace('/[^a-z0-9-]/', '', $base));
    $base = $base ?: 'aff';
    $base = substr($base, 0, 40);

    $attempt = $base;
    $suffix  = 1;
    $max_attempts = 120;

    while ($suffix <= $max_attempts) {
        if (!in_array($attempt, $reserved, true) &&
            !VH360_Affiliates_Database::get_affiliate_by_code($attempt)) {
            return $attempt;
        }
        $attempt = ($suffix <= 100)
            ? $base . '-' . $suffix
            : $base . '-' . wp_generate_password(8, false);
        $suffix++;
    }

    // Final fallback: fully random code unlikely to collide
    return 'aff-' . wp_generate_password(12, false);
}

/**
 * Check if a given affiliate code is valid (exists and is active).
 *
 * @param string $code
 * @return object|false Affiliate row or false.
 */
function vh360_affiliates_get_active_affiliate($code) {
    $affiliate = VH360_Affiliates_Database::get_affiliate_by_code(sanitize_text_field($code));
    if (!$affiliate || $affiliate->status !== 'active') {
        return false;
    }
    return $affiliate;
}

/**
 * Return the referral query variable name from settings.
 *
 * @return string
 */
function vh360_affiliates_query_var() {
    $settings = vh360_affiliates_get_settings();
    return sanitize_key($settings['referral_query_var'] ?: 'ref');
}

/**
 * Hash a value (IP, user-agent) for storage – no raw PII kept.
 *
 * @param string $value
 * @return string
 */
function vh360_affiliates_hash($value) {
    return hash('sha256', $value . wp_salt('auth'));
}

/**
 * Build a referral URL for a given affiliate code and optional base URL.
 *
 * @param string $code
 * @param string $url  Defaults to home URL.
 * @return string
 */
function vh360_affiliates_build_referral_url($code, $url = '') {
    if (empty($url)) {
        $url = home_url('/');
    }
    return add_query_arg(vh360_affiliates_query_var(), rawurlencode($code), $url);
}

/**
 * Return the default From name for affiliate notification emails.
 *
 * @return string
 */
function vh360_affiliates_default_from_name() {
    $name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    $name = wp_strip_all_tags( $name );
    $name = preg_replace( '/[\r\n]+/', '', $name );

    return $name ?: 'VideoHub360';
}

/**
 * Return the default From email address for affiliate notification emails.
 *
 * Uses noreply@<site-domain> so the sending domain matches the site,
 * improving deliverability. Falls back to admin_email if the domain
 * cannot be determined.
 *
 * @return string
 */
function vh360_affiliates_default_from_email() {
    $host = wp_parse_url( home_url(), PHP_URL_HOST );

    if ( empty( $host ) ) {
        return sanitize_email( get_option( 'admin_email' ) );
    }

    $host  = strtolower( preg_replace( '/^www\./i', '', $host ) );
    $email = 'noreply@' . $host;

    if ( ! is_email( $email ) ) {
        return sanitize_email( get_option( 'admin_email' ) );
    }

    return $email;
}

/**
 * Return the default Reply-To email address for affiliate notification emails.
 *
 * @return string
 */
function vh360_affiliates_default_reply_to_email() {
    $admin_email = sanitize_email( get_option( 'admin_email' ) );

    if ( is_email( $admin_email ) ) {
        return $admin_email;
    }

    return vh360_affiliates_default_from_email();
}

/**
 * Send an email notification from the plugin.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 */
function vh360_affiliates_send_email($to, $subject, $message) {
    $settings = vh360_affiliates_get_settings();
    if (empty($settings['email_notifications'])) {
        return;
    }

    $from_name  = !empty($settings['email_from_name'])  ? $settings['email_from_name']  : vh360_affiliates_default_from_name();
    $from_email = !empty($settings['email_from_email']) ? $settings['email_from_email'] : vh360_affiliates_default_from_email();
    $reply_to   = !empty($settings['email_reply_to'])   ? $settings['email_reply_to']   : vh360_affiliates_default_reply_to_email();

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $reply_to,
    );
    wp_mail($to, $subject, nl2br(esc_html($message)), $headers);
}

/**
 * Return a human-readable label for a status string.
 *
 * @param string $status
 * @return string
 */
function vh360_affiliates_status_label($status) {
    $labels = array(
        'pending'   => __('Pending', 'videohub360-affiliates'),
        'active'    => __('Active', 'videohub360-affiliates'),
        'rejected'  => __('Rejected', 'videohub360-affiliates'),
        'suspended' => __('Suspended', 'videohub360-affiliates'),
        'approved'  => __('Approved', 'videohub360-affiliates'),
        'paid'      => __('Paid', 'videohub360-affiliates'),
        'reversed'  => __('Reversed', 'videohub360-affiliates'),
    );
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}
