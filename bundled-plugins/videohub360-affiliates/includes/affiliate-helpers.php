<?php
/**
 * Shared helper functions for the VideoHub360 Affiliates plugin.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

/**
 * Return the plugin's saved settings with defaults merged in.
 *
 * @return array
 */
function vh360_affiliates_get_settings() {
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
        'exclude_existing'         => 0,
        'payout_instructions'      => '',
        'terms_page_url'           => '',
        'visit_retention_days'     => 180,
        'email_notifications'      => 1,
    );
    $saved = get_option('vh360_affiliates_settings', array());
    return wp_parse_args($saved, $defaults);
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

    while (true) {
        if (!in_array($attempt, $reserved, true) &&
            !VH360_Affiliates_Database::get_affiliate_by_code($attempt)) {
            return $attempt;
        }
        $attempt = $base . '-' . $suffix;
        $suffix++;

        // Safety guard: generate random suffix after too many collisions
        if ($suffix > 100) {
            $attempt = $base . '-' . substr(wp_generate_password(8, false), 0, 8);
        }
    }
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
    $headers = array('Content-Type: text/html; charset=UTF-8');
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
