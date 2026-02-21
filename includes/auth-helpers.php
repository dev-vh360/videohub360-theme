<?php
/**
 * Authentication Helper Functions
 *
 * Smart URL detection for login and registration pages
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get the URL for the login page
 * 
 * Finds the page using the Login template, or falls back to wp-login.php
 *
 * @return string The login page URL
 */
function vh360_get_login_page_url() {
    // Try to find a page with the login template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-login.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // If page not found, use slug-based URL as fallback
    return home_url('/login/');
}

/**
 * Get the URL for the registration page
 * 
 * Finds the page using the Register template, or falls back to wp-login.php?action=register
 *
 * @return string The registration page URL
 */
function vh360_get_register_page_url() {
    // Try to find a page with the register template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-register.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // If page not found, use slug-based URL as fallback
    return home_url('/register/');
}

/**
 * Handle custom registration form submission
 */
function vh360_handle_registration() {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if this is a registration form submission
    if (!isset($_POST['vh360_register_submit']) || !isset($_POST['vh360_register_nonce'])) {
        return;
    }
    
    // Get the current page URL safely
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url('/register/');
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_register_nonce'], 'vh360_registration')) {
        $error_code = 'nonce_failed';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Get form data
    $first_name = isset($_POST['vh360_first_name']) ? sanitize_text_field($_POST['vh360_first_name']) : '';
    $last_name  = isset($_POST['vh360_last_name']) ? sanitize_text_field($_POST['vh360_last_name']) : '';
    $username   = sanitize_user($_POST['vh360_username']);
    $email      = sanitize_email($_POST['vh360_email']);
    $password   = $_POST['vh360_password']; // Don't trim passwords - preserve intentional whitespace
    $terms_accepted = isset($_POST['vh360_terms']) && $_POST['vh360_terms'] === 'on';

    // Collect custom registration fields
    $custom_fields_values = array();
    for ($i = 1; $i <= 2; $i++) {
        $enabled = get_theme_mod("vh360_custom_field_{$i}_enable", false);
        if ($enabled) {
            $slug  = get_theme_mod("vh360_custom_field_{$i}_slug", '');
            $label = get_theme_mod("vh360_custom_field_{$i}_label", '');
            // Only process if slug is not empty
            if (!empty($slug)) {
                $input_name = 'vh360_custom_' . sanitize_title($slug);
                if (isset($_POST[$input_name])) {
                    $value = sanitize_text_field($_POST[$input_name]);
                    $custom_fields_values[] = array(
                        'slug'  => $slug,
                        'label' => $label,
                        'value' => $value,
                    );
                }
            }
        }
    }
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error_code = 'empty_fields';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate terms acceptance
    if (!$terms_accepted) {
        $error_code = 'terms_not_accepted';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        $error_code = 'password_too_short';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate email
    if (!is_email($email)) {
        $error_code = 'invalid_email';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Check if username exists
    if (username_exists($username)) {
        $error_code = 'username_exists';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Check if email exists
    if (email_exists($email)) {
        $error_code = 'email_exists';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Create the user
    $display_name = trim($first_name . ' ' . $last_name);
    if ('' === $display_name) {
        $display_name = $username;
    }

    $user_id = wp_insert_user(array(
        'user_login'   => $username,
        'user_email'   => $email,
        'user_pass'    => $password,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => $display_name,
        'role'         => 'subscriber',
    ));
    
    // Check for errors
    if (is_wp_error($user_id)) {
        $error_code = $user_id->get_error_code();
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }

    // Store custom field values as user meta
    if (!empty($custom_fields_values)) {
        foreach ($custom_fields_values as $field) {
            update_user_meta($user_id, $field['slug'], $field['value']);
        }
    }
    
    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Send registration notification if enabled
    if (get_theme_mod('vh360_registration_notify', false)) {
        $notification_email = get_theme_mod('vh360_registration_notify_email', get_option('admin_email'));
        $notification_email = sanitize_email($notification_email);
        if (!empty($notification_email)) {
            /* translators: %s: Site name */
            $subject = sprintf(__('[%s] New user registration', 'videohub360-theme'), get_bloginfo('name'));
            $message  = "";
            $message .= sprintf(__('A new user has registered on %s.', 'videohub360-theme'), get_bloginfo('name')) . "\n\n";
            $message .= __('Username:', 'videohub360-theme') . ' ' . $username . "\n";
            $message .= __('Email:', 'videohub360-theme') . ' ' . $email . "\n";
            // Append custom field data
            foreach ($custom_fields_values as $field) {
                $message .= $field['label'] . ': ' . $field['value'] . "\n";
            }
            wp_mail($notification_email, $subject, $message);
        }
    }
    
    // Redirect to dashboard or home
    $redirect_to = home_url('/dashboard/');
    if (!get_page_by_path('dashboard')) {
        $redirect_to = home_url('/');
    }
    
    wp_safe_redirect($redirect_to);
    exit;
}
add_action('template_redirect', 'vh360_handle_registration');

/**
 * Handle Business registration form submission (Professional/Client)
 */
function vh360_handle_business_registration() {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if this is a business registration form submission
    if (!isset($_POST['vh360_business_register_submit']) || !isset($_POST['vh360_business_register_nonce'])) {
        return;
    }
    
    // Get the current page URL safely
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url('/register-business/');
    }
    
    // Verify nonce
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_business_register_nonce'])), 'vh360_business_register')) {
        $error_code = 'nonce_failed';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Get and validate account type (whitelist)
    $account_type = isset($_POST['vh360_account_type']) ? sanitize_text_field(wp_unslash($_POST['vh360_account_type'])) : '';
    $valid_types = array('professional', 'client');
    
    if (!in_array($account_type, $valid_types, true)) {
        $error_code = 'invalid_account_type';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Get form data
    $first_name = isset($_POST['vh360_first_name']) ? sanitize_text_field(wp_unslash($_POST['vh360_first_name'])) : '';
    $last_name  = isset($_POST['vh360_last_name']) ? sanitize_text_field(wp_unslash($_POST['vh360_last_name'])) : '';
    $username   = isset($_POST['vh360_username']) ? sanitize_user(wp_unslash($_POST['vh360_username'])) : '';
    $email      = isset($_POST['vh360_email']) ? sanitize_email(wp_unslash($_POST['vh360_email'])) : '';
    $password   = isset($_POST['vh360_password']) ? $_POST['vh360_password'] : ''; // Don't trim passwords
    $terms_accepted = isset($_POST['vh360_terms']) && $_POST['vh360_terms'] === 'on';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error_code = 'empty_fields';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate terms acceptance
    if (!$terms_accepted) {
        $error_code = 'terms_not_accepted';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        $error_code = 'password_too_short';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Validate email
    if (!is_email($email)) {
        $error_code = 'invalid_email';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Check if username exists
    if (username_exists($username)) {
        $error_code = 'username_exists';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Check if email exists
    if (email_exists($email)) {
        $error_code = 'email_exists';
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }
    
    // Create the user
    $display_name = trim($first_name . ' ' . $last_name);
    if ('' === $display_name) {
        $display_name = $username;
    }

    $user_id = wp_insert_user(array(
        'user_login'   => $username,
        'user_email'   => $email,
        'user_pass'    => $password,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => $display_name,
        'role'         => 'subscriber',
    ));
    
    // Check for errors
    if (is_wp_error($user_id)) {
        $error_code = $user_id->get_error_code();
        wp_safe_redirect(add_query_arg(array('registration' => 'failed', 'error' => $error_code), $current_url));
        exit;
    }

    // Set account type meta
    update_user_meta($user_id, '_vh360_account_type', $account_type);
    
    // Set default profile visibility based on account type
    if ($account_type === 'professional') {
        update_user_meta($user_id, '_vh360_profile_visibility', 'public');
    } elseif ($account_type === 'client') {
        update_user_meta($user_id, '_vh360_profile_visibility', 'members');
    }
    
    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Send registration notification if enabled
    if (get_theme_mod('vh360_registration_notify', false)) {
        $notification_email = get_theme_mod('vh360_registration_notify_email', get_option('admin_email'));
        $notification_email = sanitize_email($notification_email);
        if (!empty($notification_email)) {
            /* translators: %s: Site name */
            $subject = sprintf(__('[%s] New %s registration', 'videohub360-theme'), get_bloginfo('name'), ucfirst($account_type));
            $message  = "";
            $message .= sprintf(__('A new %s has registered on %s.', 'videohub360-theme'), $account_type, get_bloginfo('name')) . "\n\n";
            $message .= __('Username:', 'videohub360-theme') . ' ' . $username . "\n";
            $message .= __('Email:', 'videohub360-theme') . ' ' . $email . "\n";
            $message .= __('Account Type:', 'videohub360-theme') . ' ' . ucfirst($account_type) . "\n";
            wp_mail($notification_email, $subject, $message);
        }
    }
    
    // Redirect based on account type
    if ($account_type === 'professional') {
        // Redirect to dashboard with business profile tab
        $redirect_to = home_url('/dashboard/');
        if (get_page_by_path('dashboard')) {
            $redirect_to = add_query_arg('tab', 'business-profile', $redirect_to);
        }
    } else {
        // Redirect to dashboard for clients
        $redirect_to = home_url('/dashboard/');
        if (!get_page_by_path('dashboard')) {
            $redirect_to = home_url('/');
        }
    }
    
    wp_safe_redirect($redirect_to);
    exit;
}
add_action('template_redirect', 'vh360_handle_business_registration');

/**
 * Get the URL for the professional registration page
 *
 * @return string The professional registration page URL
 */
function vh360_get_professional_register_url() {
    // Try to find a page with the professional register template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-register-professional.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // Fallback to slug-based URL
    return home_url('/register-professional/');
}

/**
 * Get the URL for the client registration page
 *
 * @return string The client registration page URL
 */
function vh360_get_client_register_url() {
    // Try to find a page with the client register template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-register-client.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // Fallback to slug-based URL
    return home_url('/register-client/');
}

/**
 * Get the URL for the business registration landing page
 *
 * @return string The business registration landing page URL
 */
function vh360_get_business_register_url() {
    // Try to find a page with the business register template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-register-business.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // Fallback to slug-based URL
    return home_url('/register-business/');
}

/**
 * Handle custom login form submission
 * 
 * Processes login attempts from the custom login template without touching wp-login.php.
 * This ensures all authentication happens within the custom branded template.
 * 
 * @since 1.0.0
 */
add_action('template_redirect', 'vh360_handle_custom_login');
function vh360_handle_custom_login() {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if this is a custom login form submission
    if (!isset($_POST['vh360_login_submit']) || !isset($_POST['vh360_login_nonce'])) {
        return;
    }
    
    // Get the current page URL safely
    $current_url = get_permalink();
    if (!$current_url) {
        $login_page = vh360_get_login_page_url();
        $current_url = $login_page ? $login_page : home_url('/login/');
    }
    
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['vh360_login_nonce'], 'vh360_custom_login')) {
        $error_url = add_query_arg('login', 'nonce_failed', $current_url);
        wp_safe_redirect($error_url);
        exit;
    }
    
    // Get form data
    $username = isset($_POST['vh360_username']) ? sanitize_text_field($_POST['vh360_username']) : '';
    $password = isset($_POST['vh360_password']) ? $_POST['vh360_password'] : '';
    $remember = isset($_POST['vh360_remember']) && $_POST['vh360_remember'] === '1';
    
    // Validate required fields
    if (empty($username) || empty($password)) {
        $error_url = add_query_arg('login', 'empty_fields', $current_url);
        if (!empty($username)) {
            $error_url = add_query_arg('username', urlencode($username), $error_url);
        }
        wp_safe_redirect($error_url);
        exit;
    }
    
    // Attempt authentication using WordPress's secure authentication
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    );
    
    /**
     * IMPORTANT: don't force a non-secure auth cookie.
     *
     * Passing `false` here can prevent WordPress from setting the secure
     * authentication cookie when the site/admin is running over HTTPS (or
     * FORCE_SSL_ADMIN is enabled). That can make the user appear logged-in on
     * the front end, but still be treated as logged-out in wp-admin.
     */
    $secure_cookie = ( function_exists( 'force_ssl_admin' ) && force_ssl_admin() ) || is_ssl();
    $user = wp_signon( $creds, $secure_cookie );
    
    // Check for authentication errors
    if (is_wp_error($user)) {
        // Login failed - redirect back with error and preserve username
        $error_url = add_query_arg(array(
            'login' => 'failed',
            'username' => urlencode($username),
        ), $current_url);
        wp_safe_redirect($error_url);
        exit;
    }
    
    // Login successful - determine redirect destination
    $redirect_to = isset($_POST['vh360_redirect_to']) ? esc_url_raw($_POST['vh360_redirect_to']) : '';
    
    // Check for redirect_to in URL (from gated pages or deep links)
    if (empty($redirect_to) && isset($_GET['redirect_to'])) {
        $redirect_to = esc_url_raw($_GET['redirect_to']);
    }
    
    // Default redirect if none specified - use Customizer setting
    if (empty($redirect_to)) {
        $redirect_to = vh360_get_login_redirect_url($user->ID);
    }
    
    // Validate redirect URL to prevent open redirect vulnerabilities
    $redirect_to = wp_validate_redirect($redirect_to, home_url());
    
    // Redirect to destination
    wp_safe_redirect($redirect_to);
    exit;
}

/**
 * Get the URL for the lost password page
 * 
 * Finds the page using the Lost Password template, or falls back to wp-login.php?action=lostpassword
 *
 * @return string The lost password page URL
 */
function vh360_get_lost_password_page_url() {
    // Try to find a page with the lost password template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-lost-password.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // If page not found, use slug-based URL as fallback
    return home_url('/lost-password/');
}

/**
 * Get the URL for the reset password page
 * 
 * Finds the page using the Reset Password template, or falls back to wp-login.php?action=rp
 *
 * @return string The reset password page URL
 */
function vh360_get_reset_password_page_url() {
    // Try to find a page with the reset password template
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-reset-password.php',
        'number' => 1,
    ));
    
    if (!empty($pages)) {
        return get_permalink($pages[0]->ID);
    }
    
    // If page not found, use slug-based URL as fallback
    return home_url('/reset-password/');
}

/**
 * Handle custom lost password form submission
 */
function vh360_handle_lost_password() {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if this is a lost password form submission
    if (!isset($_POST['vh360_lost_password_submit']) || !isset($_POST['vh360_lost_password_nonce'])) {
        return;
    }
    
    // Get the current page URL safely
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url('/lost-password/');
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_lost_password_nonce'], 'vh360_lost_password')) {
        $error_code = 'nonce_failed';
        wp_safe_redirect(add_query_arg(array('error' => $error_code), $current_url));
        exit;
    }
    
    // Get form data
    $user_login = isset($_POST['vh360_user_login']) ? sanitize_text_field($_POST['vh360_user_login']) : '';
    
    // Validate required field
    if (empty($user_login)) {
        $error_code = 'empty_username';
        wp_safe_redirect(add_query_arg(array('error' => $error_code), $current_url));
        exit;
    }
    
    // Use WordPress native retrieve_password function
    $errors = retrieve_password($user_login);
    
    if (is_wp_error($errors)) {
        $error_code = $errors->get_error_code();
        wp_safe_redirect(add_query_arg(array('error' => $error_code), $current_url));
        exit;
    }
    
    // Success - redirect with confirmation message
    wp_safe_redirect(add_query_arg(array('checkemail' => 'confirm'), $current_url));
    exit;
}
add_action('template_redirect', 'vh360_handle_lost_password');

/**
 * Handle custom reset password form submission
 */
function vh360_handle_reset_password() {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Only process if this is a reset password form submission
    if (!isset($_POST['vh360_reset_password_submit']) || !isset($_POST['vh360_reset_password_nonce'])) {
        return;
    }
    
    // Get the current page URL safely
    $current_url = get_permalink();
    if (!$current_url) {
        $current_url = home_url('/reset-password/');
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_reset_password_nonce'], 'vh360_reset_password')) {
        $error_code = 'nonce_failed';
        wp_safe_redirect(add_query_arg(array('error' => $error_code), $current_url));
        exit;
    }
    
    // Get form data
    $reset_key = isset($_POST['vh360_reset_key']) ? sanitize_text_field($_POST['vh360_reset_key']) : '';
    $user_login = isset($_POST['vh360_user_login']) ? sanitize_text_field($_POST['vh360_user_login']) : '';
    
    // Validate password length directly without storing in variable
    if (!isset($_POST['vh360_new_password']) || strlen($_POST['vh360_new_password']) < 8) {
        $error_code = 'password_too_short';
        $redirect_url = add_query_arg(array(
            'key' => urlencode($reset_key),
            'login' => urlencode($user_login),
            'error' => $error_code
        ), $current_url);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    // Validate passwords match directly without storing in variables
    if (!isset($_POST['vh360_confirm_password']) || $_POST['vh360_new_password'] !== $_POST['vh360_confirm_password']) {
        $error_code = 'password_mismatch';
        $redirect_url = add_query_arg(array(
            'key' => urlencode($reset_key),
            'login' => urlencode($user_login),
            'error' => $error_code
        ), $current_url);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    // Verify the reset key again
    $user = check_password_reset_key($reset_key, $user_login);
    
    if (is_wp_error($user)) {
        $error_code = 'invalid_key';
        $redirect_url = add_query_arg(array(
            'key' => urlencode($reset_key),
            'login' => urlencode($user_login),
            'error' => $error_code
        ), $current_url);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    // Reset the password using WordPress native function
    reset_password($user, $_POST['vh360_new_password']);
    
    // Success - redirect with success message
    wp_safe_redirect(add_query_arg(array('password' => 'changed'), $current_url));
    exit;
}
add_action('template_redirect', 'vh360_handle_reset_password');

/**
 * Filter password reset email subject to remove WordPress branding
 */
function vh360_custom_password_reset_subject($title, $user_login, $user_data) {
    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    /* translators: %s: Site name */
    return sprintf(__('[%s] Password Reset', 'videohub360-theme'), $site_name);
}
add_filter('retrieve_password_title', 'vh360_custom_password_reset_subject', 10, 3);

/**
 * Handle custom logout
 * 
 * Custom logout handler that bypasses wp-login.php completely to avoid WordPress branding.
 * Uses session-based security instead of nonces to prevent expiration issues.
 * 
 * Security: Only logged-in users can access this handler (checked via is_user_logged_in()).
 * This is sufficient security since logout is a non-destructive action that only ends
 * the user's own session. WordPress admin bar logout uses the same security model.
 * 
 * @since 1.0.0
 */
add_action('template_redirect', 'vh360_handle_custom_logout');
function vh360_handle_custom_logout() {
    // Early return if not a custom logout request
    if (!isset($_GET['vh360_logout'])) {
        return;
    }
    
    // Security check: Only allow logged-in users to logout
    // This is the primary security mechanism - the logged-in session cookie is
    // cryptographically secure and doesn't expire like nonces do
    if (!is_user_logged_in()) {
        // Not logged in, redirect to home
        wp_safe_redirect(home_url());
        exit;
    }
    
    // Get redirect URL and validate it to prevent open redirect vulnerabilities
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
    $redirect_to = wp_validate_redirect($redirect_to, home_url());
    
    // Perform logout
    wp_logout();
    
    // Redirect to validated URL (never shows WordPress branding)
    wp_safe_redirect($redirect_to);
    exit;
}

/**
 * Generate custom logout URL
 * 
 * Helper function to generate custom logout URLs that bypass wp-login.php completely
 * to avoid WordPress branding. Use this instead of wp_logout_url() to ensure the
 * custom logout handler is used.
 * 
 * No nonce is included because:
 * - The logout handler verifies the user is logged in (session-based security)
 * - Logout links never expire (no stale nonce issues)
 * - Logout is non-destructive (only ends the user's own session)
 * - This matches the security model of WordPress admin bar logout
 * 
 * @since 1.0.0
 * @param string $redirect_to Optional. URL to redirect to after logout. Default home URL.
 * @return string Custom logout URL
 */
function vh360_get_logout_url($redirect_to = '') {
    if (empty($redirect_to)) {
        $redirect_to = home_url('/');
    }
    
    $args = array(
        'vh360_logout' => '1',
        'redirect_to' => $redirect_to,
    );
    
    return add_query_arg($args, home_url());
}

/**
 * Filter wp_logout_url() to use our custom logout handler instead of wp-login.php
 * 
 * This ensures all logout links in the theme use our custom logout endpoint,
 * completely bypassing WordPress's wp-login.php confirmation screen.
 * 
 * Priority 999 ensures this runs after any other logout_url filters.
 */
add_filter('logout_url', 'vh360_custom_logout_url', 999, 2);
function vh360_custom_logout_url($logout_url, $redirect) {
    // Use our helper function for consistent URL generation
    return vh360_get_logout_url($redirect);
}

/**
 * Remove the default WordPress admin bar logout link and replace with custom one
 * 
 * This ensures the admin bar also uses our custom logout handler.
 */
add_action('admin_bar_menu', 'vh360_customize_admin_bar_logout', 999);
function vh360_customize_admin_bar_logout($wp_admin_bar) {
    // Remove the default logout menu item
    $wp_admin_bar->remove_node('logout');
    
    // Use our custom logout URL helper
    $logout_url = vh360_get_logout_url(home_url('/'));
    
    $wp_admin_bar->add_node(array(
        'parent' => 'user-actions',
        'id'     => 'logout',
        'title'  => __('Log Out'),
        'href'   => $logout_url,
    ));
}

/**
 * Get SVG icon for Sign In
 * 
 * Returns a safe, escaped SVG icon for the Sign In button
 * 
 * @param int $size Icon size (default: 20)
 * @return string Escaped SVG icon HTML
 */
function vh360_get_signin_icon($size = 20) {
    $svg = sprintf(
        '<svg class="hamburger-auth-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>',
        absint($size)
    );
    
    // Define allowed SVG tags and attributes for wp_kses
    $allowed_svg = array(
        'svg' => array(
            'class' => true,
            'width' => true,
            'height' => true,
            'viewBox' => true,
            'fill' => true,
            'xmlns' => true,
            'aria-hidden' => true,
        ),
        'path' => array(
            'd' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
        ),
    );
    
    return wp_kses($svg, $allowed_svg);
}

/**
 * Get SVG icon for Register/User Plus
 * 
 * Returns a safe, escaped SVG icon for the Register button
 * 
 * @param int $size Icon size (default: 20)
 * @return string Escaped SVG icon HTML
 */
function vh360_get_register_icon($size = 20) {
    $svg = sprintf(
        '<svg class="hamburger-auth-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M8 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM20 8v6M23 11h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>',
        absint($size)
    );
    
    // Define allowed SVG tags and attributes for wp_kses
    $allowed_svg = array(
        'svg' => array(
            'class' => true,
            'width' => true,
            'height' => true,
            'viewBox' => true,
            'fill' => true,
            'xmlns' => true,
            'aria-hidden' => true,
        ),
        'path' => array(
            'd' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
        ),
    );
    
    return wp_kses($svg, $allowed_svg);
}

/**
 * Get login page URL with redirect parameter
 * 
 * @param string $redirect_to URL to redirect to after login
 * @return string Login page URL with redirect parameter
 */
function vh360_get_login_page_url_with_redirect($redirect_to = '') {
    $login_url = vh360_get_login_page_url();
    
    if (!empty($redirect_to)) {
        $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
    }
    
    return $login_url;
}

/**
 * Get the login redirect URL based on Customizer settings
 * 
 * Determines where users should be redirected after login based on
 * the admin's Customizer configuration. Always validates URLs to prevent
 * open redirect vulnerabilities.
 * 
 * @since 1.4.0
 * @param int $user_id The ID of the logged-in user
 * @param string $fallback Optional fallback URL if mode resolves to nothing
 * @return string The validated redirect URL
 */
function vh360_get_login_redirect_url($user_id, $fallback = '') {
    $mode = get_theme_mod('vh360_login_redirect_mode', 'default');
    $redirect_url = '';
    
    switch ($mode) {
        case 'activity':
            // Find activity feed page using template
            $pages = get_pages(array(
                'meta_key' => '_wp_page_template',
                'meta_value' => 'template-activity-feed.php',
                'number' => 1,
            ));
            if (!empty($pages)) {
                $redirect_url = get_permalink($pages[0]->ID);
            } else {
                // Fallback to /activity/ slug
                $redirect_url = home_url('/activity/');
            }
            break;
            
        case 'profile':
            // Redirect to the logged-in user's profile
            $redirect_url = get_author_posts_url($user_id);
            break;
            
        case 'home':
            $redirect_url = home_url('/');
            break;
            
        case 'previous':
            // Get referrer with strict validation
            $referrer = wp_get_referer();
            if ($referrer) {
                // Parse referrer URL
                $referrer_parts = parse_url($referrer);
                $site_parts = parse_url(home_url());
                
                // Only allow if same domain AND not wp-admin
                if (
                    isset($referrer_parts['host']) && 
                    isset($site_parts['host']) &&
                    $referrer_parts['host'] === $site_parts['host'] &&
                    (! isset($referrer_parts['path']) || strpos($referrer_parts['path'], '/wp-admin') !== 0)
                ) {
                    $redirect_url = $referrer;
                }
            }
            break;
            
        case 'custom':
            $custom_url = get_theme_mod('vh360_login_redirect_custom_url', '');
            if (!empty($custom_url)) {
                $redirect_url = $custom_url;
            }
            break;
            
        case 'default':
        default: 
            // Original behavior: Dashboard or home
            $redirect_url = home_url('/dashboard/');
            if (! get_page_by_path('dashboard')) {
                $redirect_url = home_url('/');
            }
            break;
    }
    
    // If no URL was determined, use fallback
    if (empty($redirect_url) && ! empty($fallback)) {
        $redirect_url = $fallback;
    }
    
    // Final fallback to home
    if (empty($redirect_url)) {
        $redirect_url = home_url('/');
    }
    
    // Always validate to prevent open redirects
    $redirect_url = wp_validate_redirect($redirect_url, home_url('/'));
    
    return $redirect_url;
}
