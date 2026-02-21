<?php
/**
 * WP-Login Redirect Handler
 * 
 * Redirects all wp-login.php requests to custom authentication pages.
 * Includes admin escape hatch for troubleshooting.
 * 
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirect wp-login.php to custom auth pages
 */
add_action('login_init', 'vh360_redirect_wp_login');
function vh360_redirect_wp_login() {
    // Allow POST requests to proceed - these are actual login form submissions
    // that need to be processed by WordPress's built-in authentication in wp-login.php
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }
    
    // Admin escape hatch - more secure check with capability only for logged-in admins
    // Only allow admins to bypass when already logged in
    if (current_user_can('manage_options')) {
        return;
    }
    
    // Additional escape hatch with nonce for emergency access (rarely needed)
    // Use custom parameter name to avoid confusion with WP core
    if (isset($_GET['vh360_bypass']) && isset($_GET['vh360_nonce'])) {
        if (wp_verify_nonce($_GET['vh360_nonce'], 'vh360_emergency_login')) {
            return;
        }
    }
    
    // Get the action
    $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : 'login';
    
    // Determine redirect destination based on action
    switch ($action) {
        case 'register':
            $redirect_url = vh360_get_register_page_url();
            break;
            
        case 'lostpassword':
        case 'retrievepassword':
            $redirect_url = vh360_get_lost_password_page_url();
            break;
            
        case 'rp':
        case 'resetpass':
            // Preserve key and login for password reset
            $redirect_url = vh360_get_reset_password_page_url();
            if (isset($_GET['key']) && isset($_GET['login'])) {
                // Properly unslash and sanitize
                $key = sanitize_text_field(wp_unslash($_GET['key']));
                $login = sanitize_text_field(wp_unslash($_GET['login']));
                
                // WordPress password reset keys are 20 chars, alphanumeric
                // More permissive pattern to match WordPress actual format
                if (strlen($key) >= 10 && preg_match('/^[a-zA-Z0-9\-_]+$/', $key)) {
                    $redirect_url = add_query_arg(array(
                        'key' => $key,
                        'login' => $login
                    ), $redirect_url);
                }
            }
            break;
            
        case 'logout':
            // Use custom logout handler
            $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : home_url();
            wp_logout();
            wp_safe_redirect($redirect_to);
            exit;
            break;
            
        case 'login':
        default:
            $redirect_url = vh360_get_login_page_url();
            
            // Preserve redirect_to parameter with proper sanitization
            if (isset($_REQUEST['redirect_to'])) {
                $redirect_to_safe = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
                $redirect_url = add_query_arg('redirect_to', urlencode($redirect_to_safe), $redirect_url);
            }
            
            // Preserve reauth parameter
            if (isset($_REQUEST['reauth'])) {
                $redirect_url = add_query_arg('reauth', '1', $redirect_url);
            }
            break;
    }
    
    wp_safe_redirect($redirect_url, 302);
    exit;
}

/**
 * Redirect wp-admin for logged-out users to custom login
 */
add_action('admin_init', 'vh360_redirect_admin_to_custom_login');
function vh360_redirect_admin_to_custom_login() {
    // Only for non-logged-in users
    if (is_user_logged_in()) {
        return;
    }
    
    // Skip AJAX and REST
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    
    // Redirect to custom login with wp-admin as redirect target
    $login_url = vh360_get_login_page_url();
    $redirect_url = add_query_arg('redirect_to', urlencode(admin_url()), $login_url);
    
    wp_safe_redirect($redirect_url, 302);
    exit;
}

/**
 * Block wp-admin access for non-admin users
 * 
 * Redirects logged-in subscribers to the front-end dashboard
 */
add_action('admin_init', 'vh360_block_admin_for_non_admins');
function vh360_block_admin_for_non_admins() {
    // Only for logged-in users
    if (!is_user_logged_in()) {
        return;
    }
    
    // Allow admins to access wp-admin
    if (current_user_can('manage_options')) {
        return;
    }
    
    // Skip AJAX and REST
    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    
    // Redirect to front-end dashboard
    $dashboard_url = home_url('/dashboard/');
    if (!get_page_by_path('dashboard')) {
        $dashboard_url = home_url('/');
    }
    
    wp_safe_redirect($dashboard_url, 302);
    exit;
}

/**
 * Hide admin bar for non-admin users
 */
add_action('after_setup_theme', 'vh360_hide_admin_bar_for_non_admins');
function vh360_hide_admin_bar_for_non_admins() {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
}
