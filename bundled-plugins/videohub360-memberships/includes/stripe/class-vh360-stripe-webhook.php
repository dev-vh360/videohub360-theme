<?php
/**
 * Stripe Webhook Handler
 *
 * Receives and processes Stripe webhook events.
 * Includes signature verification, event deduplication,
 * and idempotent membership state updates.
 *
 * @package VideoHub360_Memberships
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Stripe_Webhook {
    
    /**
     * Singleton instance
     *
     * @var VH360_Stripe_Webhook
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Stripe_Webhook
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
        // Register the webhook REST endpoint
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }
    
    /**
     * Register REST API webhook endpoint
     */
    public function register_webhook_endpoint() {
        register_rest_route('vh360-memberships/v1', '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Signature verification handles auth
        ));
    }
    
    /**
     * Handle incoming webhook
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $webhook_secret = $stripe->get_webhook_secret();
        
        // Get raw body
        $payload = $request->get_body();
        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) : '';
        
        // Verify signature
        if (!empty($webhook_secret)) {
            $verified = $this->verify_signature($payload, $sig_header, $webhook_secret);
            
            if (is_wp_error($verified)) {
                $this->log_webhook_error('signature_verification_failed', $verified->get_error_message());
                return new WP_REST_Response(array('error' => 'Invalid signature'), 400);
            }
        }
        
        // Parse payload
        $event = json_decode($payload, true);
        
        if (empty($event) || empty($event['id']) || empty($event['type'])) {
            $this->log_webhook_error('invalid_payload', 'Missing event ID or type');
            return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
        }
        
        $event_id = sanitize_text_field($event['id']);
        $event_type = sanitize_text_field($event['type']);
        
        // Deduplicate: check if event already processed
        if (VH360_Membership_Database::is_stripe_event_processed($event_id)) {
            return new WP_REST_Response(array('status' => 'already_processed'), 200);
        }
        
        // Process the event
        $result = $this->process_event($event_type, $event);
        
        if (is_wp_error($result)) {
            $this->log_webhook_error('processing_failed', $result->get_error_message(), $event_id);
            // Return 200 to prevent Stripe retries for logic errors
            return new WP_REST_Response(array('status' => 'error', 'message' => $result->get_error_message()), 200);
        }
        
        return new WP_REST_Response(array('status' => 'processed'), 200);
    }
    
    /**
     * Verify Stripe webhook signature
     *
     * @param string $payload   Raw request body
     * @param string $sig_header Stripe-Signature header
     * @param string $secret     Webhook signing secret
     * @return true|WP_Error
     */
    private function verify_signature($payload, $sig_header, $secret) {
        if (empty($sig_header)) {
            return new WP_Error('missing_signature', 'No Stripe signature header present.');
        }
        
        // Parse signature header
        $parts = array();
        foreach (explode(',', $sig_header) as $item) {
            $kv = explode('=', trim($item), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]][] = $kv[1];
            }
        }
        
        if (empty($parts['t']) || empty($parts['v1'])) {
            return new WP_Error('invalid_signature', 'Malformed signature header.');
        }
        
        $timestamp = $parts['t'][0];
        
        // Check timestamp tolerance (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return new WP_Error('timestamp_expired', 'Webhook timestamp outside tolerance window.');
        }
        
        $signed_payload = $timestamp . '.' . $payload;
        $expected_sig = hash_hmac('sha256', $signed_payload, $secret);
        
        foreach ($parts['v1'] as $sig) {
            if (hash_equals($expected_sig, $sig)) {
                return true;
            }
        }
        
        return new WP_Error('signature_mismatch', 'Signature verification failed.');
    }
    
    /**
     * Process a single Stripe event
     *
     * @param string $event_type Stripe event type
     * @param array  $event      Full event data
     * @return true|WP_Error
     */
    private function process_event($event_type, $event) {
        $object = isset($event['data']['object']) ? $event['data']['object'] : array();
        $event_id = isset($event['id']) ? $event['id'] : '';
        
        switch ($event_type) {
            case 'checkout.session.completed':
                return $this->handle_checkout_completed($object, $event_id);
                
            case 'customer.subscription.created':
                return $this->handle_subscription_created($object, $event_id);
                
            case 'customer.subscription.updated':
                return $this->handle_subscription_updated($object, $event_id);
                
            case 'customer.subscription.deleted':
                return $this->handle_subscription_deleted($object, $event_id);
                
            case 'invoice.paid':
                return $this->handle_invoice_paid($object, $event_id);
                
            case 'invoice.payment_failed':
                return $this->handle_invoice_payment_failed($object, $event_id);
                
            default:
                // Unknown event type — acknowledge but don't process
                return true;
        }
    }
    
    /**
     * Handle checkout.session.completed
     *
     * Creates the subscription-backed membership if it doesn't exist yet.
     */
    private function handle_checkout_completed($session, $event_id) {
        if (empty($session['subscription']) || empty($session['customer'])) {
            return true; // Not a subscription checkout
        }
        
        $user_id = $this->resolve_user_id($session);
        if (!$user_id) {
            return new WP_Error('no_user', 'Could not resolve WordPress user from checkout session.');
        }
        
        $plan_key = isset($session['metadata']['plan_key']) ? sanitize_text_field($session['metadata']['plan_key']) : '';
        
        if (empty($plan_key)) {
            // Try to resolve plan from subscription
            $stripe = VH360_Stripe_Bootstrap::get_instance();
            $subscription = $stripe->api_get('/v1/subscriptions/' . $session['subscription']);
            
            if (!is_wp_error($subscription)) {
                $plan_key = $this->resolve_plan_key_from_subscription($subscription);
            }
        }
        
        if (empty($plan_key)) {
            return new WP_Error('no_plan', 'Could not determine plan key from checkout session.');
        }
        
        // Store Stripe customer ID on user
        update_user_meta($user_id, '_vh360_stripe_customer_id', sanitize_text_field($session['customer']));
        
        $api = VH360_Membership_API::get_instance();
        $result = $api->upsert_membership_by_subscription_id(array(
            'user_id'                => $user_id,
            'plan_key'               => $plan_key,
            'stripe_customer_id'     => $session['customer'],
            'stripe_subscription_id' => $session['subscription'],
            'subscription_status'    => 'active',
        ));
        
        if ($result) {
            $api->log_event($result, 'stripe_checkout_completed', array(
                'session_id' => isset($session['id']) ? $session['id'] : '',
            ), 0, $event_id);
        }
        
        return $result ? true : new WP_Error('upsert_failed', 'Failed to create/update membership from checkout.');
    }
    
    /**
     * Handle customer.subscription.created
     */
    private function handle_subscription_created($subscription, $event_id) {
        $user_id = $this->resolve_user_id_from_customer($subscription['customer']);
        
        if (!$user_id) {
            // Will be resolved when checkout.session.completed fires
            return true;
        }
        
        $plan_key = $this->resolve_plan_key_from_subscription($subscription);
        $period_start = $this->timestamp_to_mysql(isset($subscription['current_period_start']) ? $subscription['current_period_start'] : 0);
        $period_end = $this->timestamp_to_mysql(isset($subscription['current_period_end']) ? $subscription['current_period_end'] : 0);
        $price_id = $this->get_price_id_from_subscription($subscription);
        
        $api = VH360_Membership_API::get_instance();
        $result = $api->upsert_membership_by_subscription_id(array(
            'user_id'                => $user_id,
            'plan_key'               => $plan_key ?: 'unknown',
            'stripe_customer_id'     => $subscription['customer'],
            'stripe_subscription_id' => $subscription['id'],
            'stripe_price_id'        => $price_id,
            'subscription_status'    => $subscription['status'],
            'current_period_start'   => $period_start,
            'current_period_end'     => $period_end,
        ));
        
        if ($result) {
            $api->log_event($result, 'stripe_subscription_created', array(
                'subscription_id' => $subscription['id'],
                'status'          => $subscription['status'],
            ), 0, $event_id);
        }
        
        return true;
    }
    
    /**
     * Handle customer.subscription.updated
     *
     * Covers renewals, plan changes, cancellation scheduling, reactivation, status changes.
     */
    private function handle_subscription_updated($subscription, $event_id) {
        $membership = VH360_Membership_Database::get_membership_by_subscription_id($subscription['id']);
        
        if (!$membership) {
            // Try to create if we can resolve the user
            return $this->handle_subscription_created($subscription, $event_id);
        }
        
        $api = VH360_Membership_API::get_instance();
        $price_id = $this->get_price_id_from_subscription($subscription);
        $period_start = $this->timestamp_to_mysql(isset($subscription['current_period_start']) ? $subscription['current_period_start'] : 0);
        $period_end = $this->timestamp_to_mysql(isset($subscription['current_period_end']) ? $subscription['current_period_end'] : 0);
        
        // Check for plan change
        $new_plan_key = $this->resolve_plan_key_from_subscription($subscription);
        
        $api->update_subscription_state($membership->id, array(
            'subscription_status'    => $subscription['status'],
            'current_period_start'   => $period_start,
            'current_period_end'     => $period_end,
            'stripe_price_id'        => $price_id,
            'cancel_at_period_end'   => !empty($subscription['cancel_at_period_end']),
            'last_webhook_event_id'  => $event_id,
        ));
        
        // Handle plan change if detected
        if ($new_plan_key && $new_plan_key !== $membership->plan_key) {
            $api->handle_plan_change($membership->id, $membership->plan_key, $new_plan_key);
        }
        
        $api->log_event($membership->id, 'stripe_subscription_updated', array(
            'status'             => $subscription['status'],
            'cancel_at_period_end' => !empty($subscription['cancel_at_period_end']),
        ), 0, $event_id);
        
        return true;
    }
    
    /**
     * Handle customer.subscription.deleted
     *
     * Subscription has been fully cancelled/deleted.
     */
    private function handle_subscription_deleted($subscription, $event_id) {
        $membership = VH360_Membership_Database::get_membership_by_subscription_id($subscription['id']);
        
        if (!$membership) {
            return true;
        }
        
        $api = VH360_Membership_API::get_instance();
        $api->terminate_subscription($membership->id);
        
        $api->log_event($membership->id, 'stripe_subscription_deleted', array(
            'subscription_id' => $subscription['id'],
        ), 0, $event_id);
        
        return true;
    }
    
    /**
     * Handle invoice.paid
     *
     * Updates billing period on successful renewal.
     */
    private function handle_invoice_paid($invoice, $event_id) {
        $subscription_id = isset($invoice['subscription']) ? $invoice['subscription'] : '';
        
        if (empty($subscription_id)) {
            return true;
        }
        
        $membership = VH360_Membership_Database::get_membership_by_subscription_id($subscription_id);
        
        if (!$membership) {
            return true;
        }
        
        $api = VH360_Membership_API::get_instance();
        
        $period_start = $this->timestamp_to_mysql(isset($invoice['period_start']) ? $invoice['period_start'] : 0);
        $period_end = $this->timestamp_to_mysql(isset($invoice['period_end']) ? $invoice['period_end'] : 0);
        
        // Fetch latest subscription data to get accurate period dates
        if (!empty($subscription_id)) {
            $stripe = VH360_Stripe_Bootstrap::get_instance();
            $sub_data = $stripe->api_get('/v1/subscriptions/' . $subscription_id);
            
            if (!is_wp_error($sub_data)) {
                $period_start = $this->timestamp_to_mysql(isset($sub_data['current_period_start']) ? $sub_data['current_period_start'] : 0);
                $period_end = $this->timestamp_to_mysql(isset($sub_data['current_period_end']) ? $sub_data['current_period_end'] : 0);
            }
        }
        
        $api->handle_renewal_success($membership->id, $period_start, $period_end);
        
        $api->log_event($membership->id, 'stripe_invoice_paid', array(
            'invoice_id' => isset($invoice['id']) ? $invoice['id'] : '',
            'amount_paid' => isset($invoice['amount_paid']) ? $invoice['amount_paid'] : 0,
        ), 0, $event_id);
        
        return true;
    }
    
    /**
     * Handle invoice.payment_failed
     */
    private function handle_invoice_payment_failed($invoice, $event_id) {
        $subscription_id = isset($invoice['subscription']) ? $invoice['subscription'] : '';
        
        if (empty($subscription_id)) {
            return true;
        }
        
        $membership = VH360_Membership_Database::get_membership_by_subscription_id($subscription_id);
        
        if (!$membership) {
            return true;
        }
        
        $api = VH360_Membership_API::get_instance();
        $api->handle_payment_failure($membership->id, 'past_due');
        
        $api->log_event($membership->id, 'stripe_payment_failed', array(
            'invoice_id' => isset($invoice['id']) ? $invoice['id'] : '',
        ), 0, $event_id);
        
        return true;
    }
    
    // ---------------------------------------------------------------
    // Helper methods
    // ---------------------------------------------------------------
    
    /**
     * Resolve WordPress user ID from checkout session or subscription metadata
     *
     * @param array $object Stripe object with metadata
     * @return int|false User ID or false
     */
    private function resolve_user_id($object) {
        // Check metadata
        if (!empty($object['metadata']['user_id'])) {
            $user_id = (int) $object['metadata']['user_id'];
            if (get_userdata($user_id)) {
                return $user_id;
            }
        }
        
        // Check client_reference_id
        if (!empty($object['client_reference_id'])) {
            $user_id = (int) $object['client_reference_id'];
            if (get_userdata($user_id)) {
                return $user_id;
            }
        }
        
        // Try to resolve from customer
        if (!empty($object['customer'])) {
            return $this->resolve_user_id_from_customer($object['customer']);
        }
        
        return false;
    }
    
    /**
     * Resolve WordPress user ID from Stripe customer ID
     *
     * @param string $customer_id Stripe customer ID
     * @return int|false
     */
    private function resolve_user_id_from_customer($customer_id) {
        if (empty($customer_id)) {
            return false;
        }
        
        // Look up by stored meta
        $users = get_users(array(
            'meta_key'   => '_vh360_stripe_customer_id',
            'meta_value' => $customer_id,
            'number'     => 1,
            'fields'     => 'ID',
        ));
        
        return !empty($users) ? (int) $users[0] : false;
    }
    
    /**
     * Resolve plan key from Stripe subscription
     *
     * @param array $subscription Stripe subscription object
     * @return string Plan key or empty string
     */
    private function resolve_plan_key_from_subscription($subscription) {
        // Check subscription metadata
        if (!empty($subscription['metadata']['plan_key'])) {
            return sanitize_text_field($subscription['metadata']['plan_key']);
        }
        
        // Try to match by price ID
        $price_id = $this->get_price_id_from_subscription($subscription);
        if ($price_id) {
            $plan_key = VH360_Membership_Plans::get_plan_key_by_stripe_price($price_id);
            if ($plan_key) {
                return $plan_key;
            }
        }
        
        return '';
    }
    
    /**
     * Get the Stripe price ID from a subscription object
     *
     * @param array $subscription Stripe subscription
     * @return string Price ID or empty string
     */
    private function get_price_id_from_subscription($subscription) {
        if (!empty($subscription['items']['data'][0]['price']['id'])) {
            return $subscription['items']['data'][0]['price']['id'];
        }
        
        if (!empty($subscription['plan']['id'])) {
            return $subscription['plan']['id'];
        }
        
        return '';
    }
    
    /**
     * Convert Unix timestamp to MySQL datetime
     *
     * @param int $timestamp Unix timestamp
     * @return string|null MySQL datetime or null
     */
    private function timestamp_to_mysql($timestamp) {
        if (empty($timestamp)) {
            return null;
        }
        
        return gmdate('Y-m-d H:i:s', (int) $timestamp);
    }
    
    /**
     * Log a webhook error
     *
     * @param string $error_type Error type identifier
     * @param string $message Error message
     * @param string $event_id Optional Stripe event ID
     */
    private function log_webhook_error($error_type, $message, $event_id = '') {
        $api = VH360_Membership_API::get_instance();
        $api->log_event(0, 'webhook_error', array(
            'error_type' => $error_type,
            'message'    => $message,
            'event_id'   => $event_id,
        ), 0, $event_id);
    }
}
