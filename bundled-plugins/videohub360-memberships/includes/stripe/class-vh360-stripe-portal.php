<?php
/**
 * Stripe Customer Portal Service
 *
 * Provides Stripe Billing Portal session creation for frontend
 * subscription management (update payment method, view invoices, etc.).
 *
 * @package VideoHub360_Memberships
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Stripe_Portal {
    
    /**
     * Singleton instance
     *
     * @var VH360_Stripe_Portal
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Stripe_Portal
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
        add_action('wp_ajax_vh360_stripe_portal', array($this, 'ajax_create_portal_session'));
        add_action('wp_ajax_vh360_stripe_cancel_subscription', array($this, 'ajax_cancel_subscription'));
        add_action('wp_ajax_vh360_stripe_reactivate_subscription', array($this, 'ajax_reactivate_subscription'));
    }
    
    /**
     * AJAX: Create a Stripe Billing Portal session
     */
    public function ajax_create_portal_session() {
        check_ajax_referer('vh360_stripe_portal', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'videohub360-memberships')));
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        if (!$stripe->is_portal_enabled() || !$stripe->is_configured()) {
            wp_send_json_error(array('message' => __('Billing portal is not available.', 'videohub360-memberships')));
        }
        
        $customer_id = get_user_meta($user_id, '_vh360_stripe_customer_id', true);
        
        if (empty($customer_id)) {
            wp_send_json_error(array('message' => __('No billing account found.', 'videohub360-memberships')));
        }
        
        $portal_return_url = home_url();
        if ( function_exists( 'vh360_get_dashboard_page_url' ) ) {
            $dashboard_url = vh360_get_dashboard_page_url();
            if ( $dashboard_url ) {
                $portal_return_url = add_query_arg( 'tab', 'membership', $dashboard_url );
            }
        }
        
        $result = $stripe->api_request('/v1/billing_portal/sessions', array(
            'customer'   => $customer_id,
            'return_url' => $portal_return_url,
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        if (empty($result['url'])) {
            wp_send_json_error(array('message' => __('Could not create portal session.', 'videohub360-memberships')));
        }
        
        wp_send_json_success(array('portal_url' => $result['url']));
    }
    
    /**
     * AJAX: Cancel a subscription
     */
    public function ajax_cancel_subscription() {
        check_ajax_referer('vh360_stripe_manage', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'videohub360-memberships')));
        }
        
        $membership_id = isset($_POST['membership_id']) ? absint($_POST['membership_id']) : 0;
        if (!$membership_id) {
            wp_send_json_error(array('message' => __('Invalid membership.', 'videohub360-memberships')));
        }
        
        // Verify ownership
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
            $membership_id,
            $user_id
        ));
        
        if (!$membership || $membership->billing_mode !== 'recurring' || empty($membership->stripe_subscription_id)) {
            wp_send_json_error(array('message' => __('Subscription not found.', 'videohub360-memberships')));
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $behavior = $stripe->get_cancellation_behavior();
        
        if ($behavior === 'at_period_end') {
            // Cancel at end of period
            $result = $stripe->api_request('/v1/subscriptions/' . $membership->stripe_subscription_id, array(
                'cancel_at_period_end' => 'true',
            ));
        } else {
            // Immediate cancellation
            $result = $stripe->api_delete('/v1/subscriptions/' . $membership->stripe_subscription_id);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Update local record
        $api = VH360_Membership_API::get_instance();
        
        if ($behavior === 'at_period_end') {
            $api->mark_cancel_at_period_end($membership_id, true);
        } else {
            $api->terminate_subscription($membership_id);
        }
        
        wp_send_json_success(array('message' => __('Subscription cancelled.', 'videohub360-memberships')));
    }
    
    /**
     * AJAX: Reactivate a subscription (undo cancel-at-period-end)
     */
    public function ajax_reactivate_subscription() {
        check_ajax_referer('vh360_stripe_manage', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'videohub360-memberships')));
        }
        
        $membership_id = isset($_POST['membership_id']) ? absint($_POST['membership_id']) : 0;
        if (!$membership_id) {
            wp_send_json_error(array('message' => __('Invalid membership.', 'videohub360-memberships')));
        }
        
        // Verify ownership
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
            $membership_id,
            $user_id
        ));
        
        if (!$membership || $membership->billing_mode !== 'recurring' || empty($membership->stripe_subscription_id)) {
            wp_send_json_error(array('message' => __('Subscription not found.', 'videohub360-memberships')));
        }
        
        if (!$membership->cancel_at_period_end) {
            wp_send_json_error(array('message' => __('Subscription is not scheduled for cancellation.', 'videohub360-memberships')));
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        $result = $stripe->api_request('/v1/subscriptions/' . $membership->stripe_subscription_id, array(
            'cancel_at_period_end' => 'false',
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Update local record
        $api = VH360_Membership_API::get_instance();
        $api->mark_cancel_at_period_end($membership_id, false);
        
        wp_send_json_success(array('message' => __('Subscription reactivated.', 'videohub360-memberships')));
    }
}
