<?php
/**
 * WooCommerce integration helpers.
 *
 * Goal: Keep WooCommerce checkout/payment flows intact while making new accounts
 * created during checkout align with theme expectations:
 *  - Ensure a sensible display name is set (fixes missing display name issues)
 *  - Respect the site's configured default role
 *  - Capture the theme's optional custom registration fields on checkout
 *  - Route account/login flows through the theme's auth templates when available
 *
 * This file is loaded only when WooCommerce is active.
 */

defined('ABSPATH') || exit;

/**
 * Check if cart contains any membership products.
 *
 * @return bool True if cart contains a membership product, false otherwise.
 */
function vh360_wc_cart_contains_membership() {
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    // Check if VH360_Membership_Plans class is available
    if (!class_exists('VH360_Membership_Plans')) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
        
        if (!$product_id) {
            continue;
        }

        // Use the membership mapping as the source of truth
        $mapping = VH360_Membership_Plans::get_product_membership_mapping($product_id);
        
        // Product is a membership product if mapping exists and has a non-empty plan_key
        if ($mapping && !empty($mapping['plan_key'])) {
            return true;
        }
    }

    return false;
}

/**
 * Build a display name from checkout fields.
 */
function vh360_wc_build_display_name_from_checkout() {
    $first = isset($_POST['billing_first_name']) ? sanitize_text_field(wp_unslash($_POST['billing_first_name'])) : '';
    $last  = isset($_POST['billing_last_name']) ? sanitize_text_field(wp_unslash($_POST['billing_last_name'])) : '';

    $display = trim($first . ' ' . $last);
    if ($display !== '') {
        return $display;
    }

    // Fall back to username if available.
    if (!empty($_POST['account_username'])) {
        return sanitize_user(wp_unslash($_POST['account_username']));
    }

    // Final fallback: email local part.
    if (!empty($_POST['billing_email'])) {
        $email = sanitize_email(wp_unslash($_POST['billing_email']));
        $parts = explode('@', $email);
        return sanitize_text_field($parts[0] ?? $email);
    }

    return '';
}

/**
 * Force account creation when cart contains membership products.
 * This prevents guest checkout for membership purchases.
 */
add_filter('woocommerce_checkout_registration_required', function ($registration_required) {
    // If cart contains a membership product, account creation is required
    if (vh360_wc_cart_contains_membership()) {
        return true;
    }
    
    return $registration_required;
}, 999);

/**
 * Disable guest checkout when cart contains membership products.
 */
add_filter('pre_option_woocommerce_enable_guest_checkout', function ($value) {
    // If cart contains a membership product, disable guest checkout
    if (vh360_wc_cart_contains_membership()) {
        return 'no';
    }
    
    return $value;
}, 999);

/**
 * Force account creation checkbox to be checked for membership products.
 */
add_filter('woocommerce_create_account_default_checked', function ($checked) {
    // If cart contains a membership product, pre-check the account creation box
    if (vh360_wc_cart_contains_membership()) {
        return true;
    }
    
    return $checked;
}, 999);

/**
 * Add optional theme registration fields to WooCommerce checkout when creating an account.
 */
add_filter('woocommerce_checkout_fields', function ($fields) {
    // Only add fields if the theme has them enabled.
    for ($i = 1; $i <= 2; $i++) {
        $enabled = get_theme_mod("vh360_custom_field_{$i}_enable", false);
        if (!$enabled) {
            continue;
        }

        $slug  = (string) get_theme_mod("vh360_custom_field_{$i}_slug", '');
        $label = (string) get_theme_mod("vh360_custom_field_{$i}_label", '');

        if ($slug === '') {
            continue;
        }

        $input_key = 'vh360_custom_' . sanitize_title($slug);
        $fields['account'][$input_key] = array(
            'type'        => 'text',
            'label'       => $label !== '' ? $label : $slug,
            'required'    => false,
            'class'       => array('form-row-wide'),
            'priority'    => 120 + $i,
            'autocomplete'=> 'off',
        );
    }

    return $fields;
}, 20);

/**
 * Validate required theme expectations on checkout when an account is being created.
 */
add_action('woocommerce_checkout_process', function () {
    // Only validate when Woo is creating an account.
    // Note: WooCommerce nonce is validated before this hook fires.
    $creating_account = isset($_POST['createaccount']) ? (bool) $_POST['createaccount'] : false;
    if (!$creating_account) {
        return;
    }

    $first = isset($_POST['billing_first_name']) ? trim(sanitize_text_field(wp_unslash($_POST['billing_first_name']))) : '';
    $last  = isset($_POST['billing_last_name']) ? trim(sanitize_text_field(wp_unslash($_POST['billing_last_name']))) : '';

    // Theme registration requires first + last name.
    if ($first === '' || $last === '') {
        wc_add_notice(__('Please enter your first and last name to create an account.', 'videohub360-theme'), 'error');
    }
}, 20);

/**
 * Hard validation guard: Prevent membership checkout without account creation.
 * 
 * This ensures that any cart containing a membership product cannot complete
 * checkout unless the customer is logged in OR an account will be created.
 */
add_action('woocommerce_checkout_process', function () {
    // Only check for membership products
    if (!vh360_wc_cart_contains_membership()) {
        return;
    }

    // If user is already logged in, they have an account - allow checkout
    if (is_user_logged_in()) {
        return;
    }

    // Check if WooCommerce is creating an account for this order
    // Note: WooCommerce nonce is validated before this hook fires.
    $creating_account = isset($_POST['createaccount']) ? (bool) $_POST['createaccount'] : false;
    
    if (!$creating_account) {
        // This should not happen due to the filters above, but guard against it anyway
        wc_add_notice(
            __('Membership products require a user account. Please create an account to continue.', 'videohub360-theme'),
            'error'
        );
    }
}, 999);

/**
 * Store the theme's custom registration fields as user meta (same keys used by theme registration).
 */
add_action('woocommerce_checkout_update_user_meta', function ($customer_id, $posted) {
    for ($i = 1; $i <= 2; $i++) {
        $enabled = get_theme_mod("vh360_custom_field_{$i}_enable", false);
        if (!$enabled) {
            continue;
        }

        $slug = (string) get_theme_mod("vh360_custom_field_{$i}_slug", '');
        if ($slug === '') {
            continue;
        }

        $input_key = 'vh360_custom_' . sanitize_title($slug);
        if (!isset($_POST[$input_key])) {
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$input_key]));
        update_user_meta($customer_id, $slug, $value);
    }
}, 20, 2);

/**
 * After WooCommerce creates a customer, set display name and align role with the site's default.
 */
add_action('woocommerce_created_customer', function ($customer_id) {
    $user = get_user_by('id', $customer_id);
    if (!$user) {
        return;
    }

    // Set display name if missing/empty.
    $display = trim((string) $user->display_name);
    if ($display === '') {
        $new_display = vh360_wc_build_display_name_from_checkout();
        if ($new_display !== '') {
            wp_update_user(array(
                'ID'           => $customer_id,
                'display_name' => $new_display,
                'nickname'     => $new_display,
            ));
        }
    }

    // Respect the site's default role (Appearance/Settings → New User Default Role).
    $default_role = (string) get_option('default_role', 'subscriber');
    if ($default_role === '') {
        $default_role = 'subscriber';
    }

    // Only adjust role if Woo set it to customer (common case).
    if (in_array('customer', (array) $user->roles, true) && $default_role !== 'customer') {
        $wp_user = new WP_User($customer_id);
        $wp_user->set_role($default_role);
    }
}, 20);

/**
 * Route Woo "My Account" login/register to the theme's auth pages when available.
 */
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    if (!function_exists('vh360_get_login_page_url')) {
        return;
    }

    // If user hits My Account while logged out, send them to theme login with redirect back.
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        $login_url = vh360_get_login_page_url();
        $redirect  = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($redirect), $login_url));
        exit;
    }
}, 20);

/**
 * Replace checkout login prompt with a link to theme login (prevents duplicate login UI).
 */
add_action('init', function () {
    if (!function_exists('vh360_get_login_page_url')) {
        return;
    }

    // Remove Woo's default inline login form at checkout.
    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);

    // Add a simple notice + link back to the theme login page.
    add_action('woocommerce_before_checkout_form', function () {
        if (is_user_logged_in()) {
            return;
        }

        $login_url = vh360_get_login_page_url();
        $redirect  = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $login_url = add_query_arg('redirect_to', rawurlencode($redirect), $login_url);

        echo '<div class="woocommerce-info">';
        echo wp_kses_post(sprintf(
            /* translators: %s: login URL */
            __('Already have an account? <a href="%s">Log in here</a>.', 'videohub360-theme'),
            esc_url($login_url)
        ));
        echo '</div>';
    }, 10);
});
