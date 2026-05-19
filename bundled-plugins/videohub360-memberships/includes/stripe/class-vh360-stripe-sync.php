<?php
/**
 * Stripe Sync Service
 *
 * Provides on-demand synchronization between Stripe subscription state
 * and local VH360 membership records. Used for manual re-syncs and
 * recovery from missed webhooks.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Stripe_Sync {
    
    /**
     * Singleton instance
     *
     * @var VH360_Stripe_Sync
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Stripe_Sync
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
        // Nothing to hook automatically
    }
    
    /**
     * Sync a single membership from Stripe
     *
     * Fetches current subscription data from Stripe and updates the local record.
     *
     * @param int $membership_id Local membership ID
     * @return bool|WP_Error
     */
    public function sync_membership($membership_id) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $membership_id));
        
        if (!$membership || $membership->billing_mode !== 'recurring') {
            return new WP_Error('not_recurring', 'Membership is not a recurring subscription.');
        }
        
        if (empty($membership->stripe_subscription_id)) {
            return new WP_Error('no_subscription_id', 'Membership has no Stripe subscription ID.');
        }
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        if (!$stripe->is_configured()) {
            return new WP_Error('stripe_not_configured', 'Stripe is not configured.');
        }
        
        $subscription = $stripe->api_get('/v1/subscriptions/' . $membership->stripe_subscription_id);
        
        if (is_wp_error($subscription)) {
            return $subscription;
        }
        
        $api = VH360_Membership_API::get_instance();
        
        $period_start = !empty($subscription['current_period_start'])
            ? gmdate('Y-m-d H:i:s', $subscription['current_period_start'])
            : null;
        $period_end = !empty($subscription['current_period_end'])
            ? gmdate('Y-m-d H:i:s', $subscription['current_period_end'])
            : null;
        
        $api->update_subscription_state($membership_id, array(
            'subscription_status'  => $subscription['status'],
            'current_period_start' => $period_start,
            'current_period_end'   => $period_end,
            'cancel_at_period_end' => !empty($subscription['cancel_at_period_end']),
        ));
        
        // Check for plan change
        $price_id = '';
        if (!empty($subscription['items']['data'][0]['price']['id'])) {
            $price_id = $subscription['items']['data'][0]['price']['id'];
        }
        
        if ($price_id) {
            $plan_key = VH360_Membership_Plans::get_plan_key_by_stripe_price($price_id);
            if ($plan_key && $plan_key !== $membership->plan_key) {
                $api->handle_plan_change($membership_id, $membership->plan_key, $plan_key);
            }
        }
        
        $api->log_event($membership_id, 'manual_sync', array(
            'status' => $subscription['status'],
        ));
        
        return true;
    }
    
    /**
     * Sync all recurring memberships for a user
     *
     * @param int $user_id WordPress user ID
     * @return array Results for each membership
     */
    public function sync_user_memberships($user_id) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND billing_mode = 'recurring' AND stripe_subscription_id IS NOT NULL",
            $user_id
        ));
        
        $results = array();
        
        foreach ($memberships as $membership) {
            $results[$membership->id] = $this->sync_membership($membership->id);
        }
        
        return $results;
    }
}
