<?php
/**
 * Authentication URL Filters
 * 
 * Overrides WordPress default authentication URLs to use custom templates.
 * 
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter login URL to use custom login page
 */
add_filter('login_url', 'vh360_custom_login_url', 10, 3);
function vh360_custom_login_url($login_url, $redirect, $force_reauth) {
    $custom_login = vh360_get_login_page_url();
    
    if (!empty($redirect)) {
        $custom_login = add_query_arg('redirect_to', urlencode($redirect), $custom_login);
    }
    
    if ($force_reauth) {
        $custom_login = add_query_arg('reauth', '1', $custom_login);
    }
    
    return $custom_login;
}

/**
 * Filter registration URL to use custom register page
 */
add_filter('register_url', 'vh360_custom_register_url', 10, 1);
function vh360_custom_register_url($register_url) {
    return vh360_get_register_page_url();
}

/**
 * Filter lost password URL to use custom lost password page
 */
add_filter('lostpassword_url', 'vh360_custom_lostpassword_url', 10, 2);
function vh360_custom_lostpassword_url($lostpassword_url, $redirect) {
    $custom_url = vh360_get_lost_password_page_url();
    
    if (!empty($redirect)) {
        $custom_url = add_query_arg('redirect_to', urlencode($redirect), $custom_url);
    }
    
    return $custom_url;
}

/**
 * Filter password reset email message to use custom reset page
 */
add_filter('retrieve_password_message', 'vh360_custom_reset_email_message', 10, 4);
function vh360_custom_reset_email_message($message, $key, $user_login, $user_data) {
    $reset_url = vh360_get_reset_password_page_url();
    $reset_url = add_query_arg(array(
        'key' => $key,
        'login' => rawurlencode($user_login)
    ), $reset_url);
    
    $message = sprintf(
        __('Someone has requested a password reset for the following account:', 'videohub360-theme') . "\r\n\r\n"
        . network_home_url('/') . "\r\n\r\n"
        . sprintf(__('Username: %s', 'videohub360-theme'), $user_login) . "\r\n\r\n"
        . __('If this was a mistake, just ignore this email and nothing will happen.', 'videohub360-theme') . "\r\n\r\n"
        . __('To reset your password, visit the following address:', 'videohub360-theme') . "\r\n\r\n"
        . $reset_url . "\r\n"
    );
    
    return $message;
}

/**
 * Apply Customizer login redirect globally
 * 
 * Ensures login redirect setting works everywhere: 
 * - wp-login.php
 * - WooCommerce
 * - Other plugins
 * 
 * @since 1.4.0
 */
add_filter('login_redirect', 'vh360_apply_customizer_login_redirect', 10, 3);
function vh360_apply_customizer_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // If a specific redirect was requested (e.g., from gated page), respect it.
    //
    // IMPORTANT: also respect wp-admin redirects for administrators. Otherwise,
    // hitting /wp-admin/ (or Dashboard in the admin bar) may bounce users back
    // to the theme redirect destination.
    if (!empty($requested_redirect_to)) {
        $requested = esc_url_raw($requested_redirect_to);
        $is_admin_target = (strpos($requested, admin_url()) === 0) || (strpos($requested, site_url('/wp-admin')) === 0);

        if ($is_admin_target) {
            if (is_a($user, 'WP_User') && user_can($user, 'manage_options')) {
                return $redirect_to;
            }
            // Non-admins should not be redirected into wp-admin.
            // Fall through to Customizer redirect.
        } else {
            return $redirect_to;
        }
    }
    
    // Apply Customizer setting
    if (is_a($user, 'WP_User')) {
        return vh360_get_login_redirect_url($user->ID, $redirect_to);
    }
    
    return $redirect_to;
}
