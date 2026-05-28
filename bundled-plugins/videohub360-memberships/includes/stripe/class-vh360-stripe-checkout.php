<?php
/**
 * Stripe Checkout Handler
 *
 * Creates Stripe Checkout Sessions for recurring subscription purchases,
 * manages customer creation, and handles return/cancel URLs.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
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
        
        // Handle Stripe Checkout return/cancel redirects
        add_action('template_redirect', array($this, 'handle_checkout_return'));
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
            ), self::get_return_base_url()),
            'cancel_url'           => add_query_arg('vh360_stripe_cancel', '1', self::get_return_base_url()),
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
    
    /**
     * Handle Stripe Checkout return and cancel redirects
     *
     * Processes vh360_stripe_return and vh360_stripe_cancel query parameters
     * set by Stripe Checkout success_url and cancel_url.
     */
    public function handle_checkout_return() {
        // Handle successful checkout return
        if ( isset( $_GET['vh360_stripe_return'] ) && $_GET['vh360_stripe_return'] === '1' ) {
            $this->process_checkout_success();
            return;
        }
        
        // Handle cancelled checkout
        if ( isset( $_GET['vh360_stripe_cancel'] ) && $_GET['vh360_stripe_cancel'] === '1' ) {
            $this->process_checkout_cancel();
            return;
        }
    }
    
    /**
     * Process a successful Stripe Checkout return
     *
     * Verifies the checkout session, performs sync-safe reconciliation
     * if the webhook hasn't yet processed, and redirects to the dashboard.
     */
    private function process_checkout_success() {
        $user_id = get_current_user_id();
        
        if ( ! $user_id ) {
            // Not logged in — redirect to login
            wp_safe_redirect( wp_login_url( self::get_dashboard_membership_url() ) );
            exit;
        }
        
        // Sanitize session_id from Stripe
        $session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';
        
        if ( empty( $session_id ) ) {
            $this->set_return_notice( $user_id, 'error', __( 'Invalid checkout session.', 'videohub360-memberships' ) );
            wp_safe_redirect( self::get_dashboard_membership_url() );
            exit;
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        if ( ! $stripe->is_configured() ) {
            $this->set_return_notice( $user_id, 'error', __( 'Stripe is not configured.', 'videohub360-memberships' ) );
            wp_safe_redirect( self::get_dashboard_membership_url() );
            exit;
        }
        
        // Fetch the checkout session from Stripe to verify
        $session = $stripe->api_get( '/v1/checkout/sessions/' . $session_id );
        
        if ( is_wp_error( $session ) ) {
            $this->set_return_notice( $user_id, 'error', __( 'Could not verify checkout session.', 'videohub360-memberships' ) );
            wp_safe_redirect( self::get_dashboard_membership_url() );
            exit;
        }
        
        // Verify this session belongs to the current user
        $session_user_id = 0;
        if ( ! empty( $session['metadata']['user_id'] ) ) {
            $session_user_id = (int) $session['metadata']['user_id'];
        } elseif ( ! empty( $session['client_reference_id'] ) ) {
            $session_user_id = (int) $session['client_reference_id'];
        }
        
        if ( $session_user_id !== $user_id ) {
            $this->set_return_notice( $user_id, 'error', __( 'Checkout session does not match your account.', 'videohub360-memberships' ) );
            wp_safe_redirect( self::get_dashboard_membership_url() );
            exit;
        }
        
        // Check session payment/subscription status
        // A subscription checkout is valid if either payment is 'paid' OR a subscription_id
        // is present (covers trialing, free trials, etc. where payment_status may not be 'paid').
        $payment_status = isset( $session['payment_status'] ) ? $session['payment_status'] : '';
        $subscription_id = isset( $session['subscription'] ) ? $session['subscription'] : '';
        $session_status = isset( $session['status'] ) ? $session['status'] : '';
        
        if ( $session_status !== 'complete' && empty( $subscription_id ) ) {
            $this->set_return_notice( $user_id, 'warning', __( 'Your payment is still being processed. Your membership will be activated shortly.', 'videohub360-memberships' ) );
            wp_safe_redirect( self::get_dashboard_membership_url() );
            exit;
        }
        
        // Sync-safe reconciliation: check if webhook has already created the membership
        if ( ! empty( $subscription_id ) ) {
            $existing = VH360_Membership_Database::get_membership_by_subscription_id( $subscription_id );
            
            if ( ! $existing ) {
                // Webhook hasn't processed yet — perform sync-safe reconciliation
                $this->reconcile_checkout_session( $session, $user_id );
            }
        }
        
        $this->set_return_notice( $user_id, 'success', __( 'Your subscription is now active! Welcome to your new membership.', 'videohub360-memberships' ) );
        wp_safe_redirect( self::get_dashboard_membership_url() );
        exit;
    }
    
    /**
     * Process a cancelled Stripe Checkout return
     *
     * Redirects the user to the dashboard membership tab with an informational notice.
     */
    private function process_checkout_cancel() {
        $user_id = get_current_user_id();
        
        if ( $user_id ) {
            $this->set_return_notice( $user_id, 'warning', __( 'Checkout was cancelled. No subscription was created.', 'videohub360-memberships' ) );
        }
        
        wp_safe_redirect( self::get_dashboard_membership_url() );
        exit;
    }
    
    /**
     * Perform sync-safe reconciliation from a verified checkout session
     *
     * Called when the user returns from Stripe but the webhook hasn't yet
     * created the local membership record. Uses the session and subscription
     * data to safely create/update the membership.
     *
     * @param array $session Verified Stripe checkout session
     * @param int   $user_id WordPress user ID
     */
    private function reconcile_checkout_session( $session, $user_id ) {
        $subscription_id = isset( $session['subscription'] ) ? $session['subscription'] : '';
        $customer_id = isset( $session['customer'] ) ? $session['customer'] : '';
        $plan_key = isset( $session['metadata']['plan_key'] ) ? sanitize_text_field( $session['metadata']['plan_key'] ) : '';
        
        if ( empty( $subscription_id ) ) {
            return;
        }
        
        // Store customer ID on user
        if ( $customer_id ) {
            update_user_meta( $user_id, '_vh360_stripe_customer_id', sanitize_text_field( $customer_id ) );
        }
        
        // Fetch subscription details for period dates
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $sub_data = $stripe->api_get( '/v1/subscriptions/' . $subscription_id );
        
        $period_start = null;
        $period_end = null;
        $sub_status = 'active';
        $price_id = '';
        
        if ( ! is_wp_error( $sub_data ) ) {
            $sub_status = isset( $sub_data['status'] ) ? $sub_data['status'] : 'active';
            
            if ( ! empty( $sub_data['current_period_start'] ) ) {
                $period_start = gmdate( 'Y-m-d H:i:s', (int) $sub_data['current_period_start'] );
            }
            if ( ! empty( $sub_data['current_period_end'] ) ) {
                $period_end = gmdate( 'Y-m-d H:i:s', (int) $sub_data['current_period_end'] );
            }
            
            if ( ! empty( $sub_data['items']['data'][0]['price']['id'] ) ) {
                $price_id = $sub_data['items']['data'][0]['price']['id'];
            }
            
            // Resolve plan key from price if not in metadata
            if ( empty( $plan_key ) && $price_id ) {
                $resolved = VH360_Membership_Plans::get_plan_key_by_stripe_price( $price_id );
                if ( $resolved ) {
                    $plan_key = $resolved;
                }
            }
            
            // Also check subscription metadata
            if ( empty( $plan_key ) && ! empty( $sub_data['metadata']['plan_key'] ) ) {
                $plan_key = sanitize_text_field( $sub_data['metadata']['plan_key'] );
            }
        }
        
        if ( empty( $plan_key ) ) {
            return; // Cannot reconcile without knowing the plan
        }
        
        $api = VH360_Membership_API::get_instance();
        $result = $api->upsert_membership_by_subscription_id( array(
            'user_id'                => $user_id,
            'plan_key'               => $plan_key,
            'stripe_customer_id'     => $customer_id,
            'stripe_subscription_id' => $subscription_id,
            'stripe_price_id'        => $price_id,
            'subscription_status'    => $sub_status,
            'current_period_start'   => $period_start,
            'current_period_end'     => $period_end,
        ) );
        
        if ( $result ) {
            $api->log_event( $result, 'checkout_return_reconciled', array(
                'session_id'      => isset( $session['id'] ) ? $session['id'] : '',
                'subscription_id' => $subscription_id,
            ) );
        }
    }
    
    /**
     * Set a transient notice to display after redirect
     *
     * @param int    $user_id User ID
     * @param string $type    Notice type: success, warning, error
     * @param string $message Notice message
     */
    private function set_return_notice( $user_id, $type, $message ) {
        set_transient( 'vh360_stripe_return_notice_' . $user_id, array(
            'type'    => $type,
            'message' => $message,
        ), 300 ); // 5-minute expiry
    }
    
    /**
     * Get the base URL for Stripe return redirects
     *
     * Points to the dashboard page if available, otherwise home.
     *
     * @return string
     */
    private static function get_return_base_url() {
        $dashboard_url = function_exists( 'vh360_get_dashboard_page_url' )
            ? vh360_get_dashboard_page_url()
            : '';
        
        return ! empty( $dashboard_url ) ? $dashboard_url : home_url();
    }
    
    /**
     * Get the dashboard membership tab URL
     *
     * @return string
     */
    private static function get_dashboard_membership_url() {
        $dashboard_url = function_exists( 'vh360_get_dashboard_page_url' )
            ? vh360_get_dashboard_page_url()
            : '';
        
        if ( ! empty( $dashboard_url ) ) {
            return add_query_arg( 'tab', 'membership', $dashboard_url );
        }
        
        return home_url();
    }
}
