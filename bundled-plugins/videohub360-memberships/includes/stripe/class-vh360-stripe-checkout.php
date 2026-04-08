<?php
/**
 * Stripe Checkout Handler
 *
 * Creates Stripe Checkout Sessions for recurring subscription purchases,
 * manages customer creation, and handles return/cancel URLs.
 *
 * @package VideoHub360_Memberships
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Stripe_Checkout {
    
    /**
     * Singleton instance
     *
     * @var VH360_Stripe_Checkout
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Stripe_Checkout
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_vh360_stripe_create_checkout', array($this, 'ajax_create_checkout_session'));
    }
    
    /**
     * AJAX handler: Create a Stripe Checkout Session
     *
     * Expects POST with: plan_key
     * User must be logged in.
     */
    public function ajax_create_checkout_session() {
        // Verify nonce
        check_ajax_referer('vh360_stripe_checkout', 'nonce');
        
        // Must be logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in to subscribe.', 'videohub360-memberships')));
        }
        
        $plan_key = isset($_POST['plan_key']) ? sanitize_text_field($_POST['plan_key']) : '';
        
        if (empty($plan_key)) {
            wp_send_json_error(array('message' => __('No plan selected.', 'videohub360-memberships')));
        }
        
        // Get plan billing config
        $billing_config = VH360_Membership_Plans::get_plan_billing_config($plan_key);
        
        if (!$billing_config || $billing_config['billing_mode'] !== 'recurring' || empty($billing_config['stripe_price_id'])) {
            wp_send_json_error(array('message' => __('This plan is not configured for recurring billing.', 'videohub360-memberships')));
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        if (!$stripe->is_configured()) {
            wp_send_json_error(array('message' => __('Stripe is not configured.', 'videohub360-memberships')));
        }
        
        // Get or create Stripe customer
        $customer_id = $this->get_or_create_stripe_customer($user_id);
        
        if (is_wp_error($customer_id)) {
            wp_send_json_error(array('message' => $customer_id->get_error_message()));
        }
        
        // Build checkout session params
        $session_params = array(
            'mode'                 => 'subscription',
            'customer'             => $customer_id,
            'line_items[0][price]' => $billing_config['stripe_price_id'],
            'line_items[0][quantity]' => 1,
            'success_url'          => add_query_arg(array(
                'vh360_stripe_return' => '1',
                'session_id'          => '{CHECKOUT_SESSION_ID}',
            ), home_url()),
            'cancel_url'           => add_query_arg('vh360_stripe_cancel', '1', home_url()),
            'client_reference_id'  => $user_id,
            'metadata[user_id]'    => $user_id,
            'metadata[plan_key]'   => $plan_key,
            'subscription_data[metadata][user_id]'   => $user_id,
            'subscription_data[metadata][plan_key]'   => $plan_key,
        );
        
        // Add trial if configured
        if (!empty($billing_config['trial_days']) && $billing_config['trial_days'] > 0) {
            $session_params['subscription_data[trial_period_days]'] = $billing_config['trial_days'];
        }
        
        // Allow filtering
        $session_params = apply_filters('vh360_stripe_checkout_session_params', $session_params, $plan_key, $user_id);
        
        $session = $stripe->api_request('/v1/checkout/sessions', $session_params);
        
        if (is_wp_error($session)) {
            wp_send_json_error(array('message' => $session->get_error_message()));
        }
        
        if (empty($session['url'])) {
            wp_send_json_error(array('message' => __('Failed to create checkout session.', 'videohub360-memberships')));
        }
        
        wp_send_json_success(array('checkout_url' => $session['url']));
    }
    
    /**
     * Get or create a Stripe customer for a WordPress user
     *
     * @param int $user_id WordPress user ID
     * @return string|WP_Error Stripe customer ID
     */
    public function get_or_create_stripe_customer($user_id) {
        // Check for existing customer ID
        $existing_customer_id = get_user_meta($user_id, '_vh360_stripe_customer_id', true);
        
        if (!empty($existing_customer_id)) {
            return $existing_customer_id;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('User not found.', 'videohub360-memberships'));
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        $customer = $stripe->api_request('/v1/customers', array(
            'email'              => $user->user_email,
            'name'               => $user->display_name,
            'metadata[wp_user_id]' => $user_id,
        ));
        
        if (is_wp_error($customer)) {
            return $customer;
        }
        
        if (empty($customer['id'])) {
            return new WP_Error('stripe_error', __('Failed to create Stripe customer.', 'videohub360-memberships'));
        }
        
        // Store the customer ID
        update_user_meta($user_id, '_vh360_stripe_customer_id', $customer['id']);
        
        return $customer['id'];
    }
}
