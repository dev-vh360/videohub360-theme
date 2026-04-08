<?php
/**
 * Membership API Class
 *
 * Handles membership CRUD operations, event logging,
 * and subscription lifecycle management.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_API {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_API
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_API
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
        // API is ready
    }
    
    /**
     * Create membership (one-time / order-based)
     *
     * @param int $user_id User ID
     * @param string $plan_key Plan key
     * @param int $duration Duration value
     * @param string $duration_unit Duration unit (days, months, years, lifetime)
     * @param int $source_order_id Optional WooCommerce order ID
     * @return int|false Membership ID or false on failure
     */
    public function create_membership($user_id, $plan_key, $duration, $duration_unit, $source_order_id = null) {
        global $wpdb;
        
        // Calculate expiration date
        $starts_at = current_time('mysql');
        $expires_at = $this->calculate_expiration_date($duration, $duration_unit);
        
        // Insert membership
        $result = $wpdb->insert(
            VH360_Membership_Database::get_memberships_table(),
            array(
                'user_id' => $user_id,
                'plan_key' => $plan_key,
                'status' => 'active',
                'source_order_id' => $source_order_id,
                'starts_at' => $starts_at,
                'expires_at' => $expires_at,
                'billing_provider' => 'woocommerce',
                'billing_mode' => 'one_time',
                'created_at' => $starts_at,
                'updated_at' => $starts_at,
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        $membership_id = $wpdb->insert_id;
        
        // Log event
        $this->log_event($membership_id, 'granted', array(
            'plan_key' => $plan_key,
            'duration' => $duration,
            'duration_unit' => $duration_unit,
            'source_order_id' => $source_order_id,
        ), get_current_user_id());
        
        do_action('vh360_membership_created', $membership_id, $user_id, $plan_key);
        
        return $membership_id;
    }
    
    /**
     * Create a subscription-backed membership
     *
     * @param array $args {
     *     @type int    $user_id               WordPress user ID (required)
     *     @type string $plan_key              Plan key (required)
     *     @type string $stripe_customer_id    Stripe customer ID (required)
     *     @type string $stripe_subscription_id Stripe subscription ID (required)
     *     @type string $stripe_price_id       Stripe price ID
     *     @type string $subscription_status   Stripe subscription status
     *     @type string $current_period_start  Period start datetime
     *     @type string $current_period_end    Period end datetime
     *     @type int    $trial_days            Trial period in days
     * }
     * @return int|false Membership ID or false
     */
    public function create_subscription_membership($args) {
        global $wpdb;
        
        $defaults = array(
            'user_id'                => 0,
            'plan_key'               => '',
            'stripe_customer_id'     => '',
            'stripe_subscription_id' => '',
            'stripe_price_id'        => '',
            'subscription_status'    => 'active',
            'current_period_start'   => null,
            'current_period_end'     => null,
            'trial_days'             => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if (empty($args['user_id']) || empty($args['plan_key']) || empty($args['stripe_subscription_id'])) {
            return false;
        }
        
        $now = current_time('mysql');
        $starts_at = $args['current_period_start'] ? $args['current_period_start'] : $now;
        $expires_at = $args['current_period_end'] ? $args['current_period_end'] : null;
        
        // Map Stripe status to local VH360 status
        $local_status = $this->map_subscription_status_to_local($args['subscription_status']);
        
        $result = $wpdb->insert(
            VH360_Membership_Database::get_memberships_table(),
            array(
                'user_id'                => $args['user_id'],
                'plan_key'               => $args['plan_key'],
                'status'                 => $local_status,
                'billing_provider'       => 'stripe',
                'billing_mode'           => 'recurring',
                'stripe_customer_id'     => $args['stripe_customer_id'],
                'stripe_subscription_id' => $args['stripe_subscription_id'],
                'stripe_price_id'        => $args['stripe_price_id'],
                'subscription_status'    => $args['subscription_status'],
                'current_period_start'   => $args['current_period_start'],
                'current_period_end'     => $args['current_period_end'],
                'starts_at'              => $starts_at,
                'expires_at'             => $expires_at,
                'last_billing_sync_at'   => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        $membership_id = $wpdb->insert_id;
        
        $this->log_event($membership_id, 'subscription_created', array(
            'plan_key'               => $args['plan_key'],
            'stripe_subscription_id' => $args['stripe_subscription_id'],
            'subscription_status'    => $args['subscription_status'],
        ));
        
        do_action('vh360_subscription_membership_created', $membership_id, $args['user_id'], $args['plan_key']);
        
        return $membership_id;
    }
    
    /**
     * Upsert membership by Stripe subscription ID
     *
     * Creates or updates a membership based on the Stripe subscription ID.
     * This is the primary idempotent method for webhook processing.
     *
     * @param array $args Same as create_subscription_membership
     * @return int|false Membership ID or false
     */
    public function upsert_membership_by_subscription_id($args) {
        if (empty($args['stripe_subscription_id'])) {
            return false;
        }
        
        $existing = VH360_Membership_Database::get_membership_by_subscription_id($args['stripe_subscription_id']);
        
        if ($existing) {
            // Update existing record
            $this->update_subscription_state($existing->id, array(
                'subscription_status'  => isset($args['subscription_status']) ? $args['subscription_status'] : null,
                'current_period_start' => isset($args['current_period_start']) ? $args['current_period_start'] : null,
                'current_period_end'   => isset($args['current_period_end']) ? $args['current_period_end'] : null,
                'stripe_price_id'      => isset($args['stripe_price_id']) ? $args['stripe_price_id'] : null,
                'cancel_at_period_end' => isset($args['cancel_at_period_end']) ? $args['cancel_at_period_end'] : null,
            ));
            
            // Handle plan change
            if (!empty($args['plan_key']) && $args['plan_key'] !== $existing->plan_key) {
                $this->handle_plan_change($existing->id, $existing->plan_key, $args['plan_key']);
            }
            
            return (int) $existing->id;
        }
        
        // Create new
        return $this->create_subscription_membership($args);
    }
    
    /**
     * Update the external subscription state on a membership
     *
     * @param int $membership_id Membership ID
     * @param array $state_updates Key-value pairs to update
     * @return bool
     */
    public function update_subscription_state($membership_id, $state_updates) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $membership_id
        ));
        
        if (!$membership) {
            return false;
        }
        
        $data = array('updated_at' => current_time('mysql'));
        $formats = array('%s');
        
        // Subscription status
        if (isset($state_updates['subscription_status']) && $state_updates['subscription_status'] !== null) {
            $data['subscription_status'] = sanitize_text_field($state_updates['subscription_status']);
            $formats[] = '%s';
            
            // Map to local VH360 status
            $local_status = $this->map_subscription_status_to_local($state_updates['subscription_status']);
            $data['status'] = $local_status;
            $formats[] = '%s';
        }
        
        // Period dates
        if (isset($state_updates['current_period_start']) && $state_updates['current_period_start'] !== null) {
            $data['current_period_start'] = $state_updates['current_period_start'];
            $formats[] = '%s';
        }
        
        if (isset($state_updates['current_period_end']) && $state_updates['current_period_end'] !== null) {
            $data['current_period_end'] = $state_updates['current_period_end'];
            $data['expires_at'] = $state_updates['current_period_end'];
            $formats[] = '%s';
            $formats[] = '%s';
        }
        
        // Stripe price ID
        if (isset($state_updates['stripe_price_id']) && $state_updates['stripe_price_id'] !== null) {
            $data['stripe_price_id'] = sanitize_text_field($state_updates['stripe_price_id']);
            $formats[] = '%s';
        }
        
        // Cancel at period end
        if (isset($state_updates['cancel_at_period_end']) && $state_updates['cancel_at_period_end'] !== null) {
            $data['cancel_at_period_end'] = (int) (bool) $state_updates['cancel_at_period_end'];
            $formats[] = '%d';
        }
        
        // Last webhook event
        if (isset($state_updates['last_webhook_event_id'])) {
            $data['last_webhook_event_id'] = sanitize_text_field($state_updates['last_webhook_event_id']);
            $formats[] = '%s';
        }
        
        // Sync timestamp
        $data['last_billing_sync_at'] = current_time('mysql');
        $formats[] = '%s';
        
        $result = $wpdb->update($table, $data, array('id' => $membership_id), $formats, array('%d'));
        
        if ($result === false) {
            return false;
        }
        
        $this->log_event($membership_id, 'subscription_state_updated', $state_updates);
        
        do_action('vh360_subscription_state_updated', $membership_id, $state_updates);
        
        return true;
    }
    
    /**
     * Sync billing period dates from Stripe
     *
     * @param int $membership_id Membership ID
     * @param string $period_start Period start (Y-m-d H:i:s)
     * @param string $period_end Period end (Y-m-d H:i:s)
     * @return bool
     */
    public function sync_billing_period($membership_id, $period_start, $period_end) {
        return $this->update_subscription_state($membership_id, array(
            'current_period_start' => $period_start,
            'current_period_end'   => $period_end,
        ));
    }
    
    /**
     * Mark subscription as cancel-at-period-end
     *
     * @param int $membership_id Membership ID
     * @param bool $cancel Whether to cancel at period end
     * @return bool
     */
    public function mark_cancel_at_period_end($membership_id, $cancel = true) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $data = array(
            'cancel_at_period_end' => $cancel ? 1 : 0,
            'updated_at' => current_time('mysql'),
            'last_billing_sync_at' => current_time('mysql'),
        );
        
        if ($cancel) {
            $data['cancelled_at'] = current_time('mysql');
        } else {
            $data['cancelled_at'] = null;
        }
        
        $result = $wpdb->update($table, $data, array('id' => $membership_id));
        
        if ($result === false) {
            return false;
        }
        
        $event_type = $cancel ? 'cancel_at_period_end' : 'cancel_at_period_end_reverted';
        $this->log_event($membership_id, $event_type, array('cancel_at_period_end' => $cancel));
        
        do_action('vh360_subscription_cancel_at_period_end', $membership_id, $cancel);
        
        return true;
    }
    
    /**
     * Handle payment failure for a subscription
     *
     * @param int $membership_id Membership ID
     * @param string $stripe_status The Stripe subscription status (past_due, unpaid, incomplete)
     * @return bool
     */
    public function handle_payment_failure($membership_id, $stripe_status = 'past_due') {
        return $this->update_subscription_state($membership_id, array(
            'subscription_status' => $stripe_status,
        ));
    }
    
    /**
     * Handle successful renewal / payment recovery
     *
     * @param int $membership_id Membership ID
     * @param string $period_start New period start
     * @param string $period_end New period end
     * @return bool
     */
    public function handle_renewal_success($membership_id, $period_start, $period_end) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $membership_id));
        if (!$membership) {
            return false;
        }
        
        $result = $this->update_subscription_state($membership_id, array(
            'subscription_status'  => 'active',
            'current_period_start' => $period_start,
            'current_period_end'   => $period_end,
        ));
        
        if ($result) {
            // Clear renewal reminder flag
            delete_user_meta($membership->user_id, "_vh360_membership_reminder_sent_{$membership_id}");
            
            $this->log_event($membership_id, 'subscription_renewed', array(
                'period_start' => $period_start,
                'period_end'   => $period_end,
            ));
            
            do_action('vh360_subscription_renewed', $membership_id);
        }
        
        return $result;
    }
    
    /**
     * Terminate a subscription-backed membership
     *
     * Called when subscription is actually cancelled/deleted (not just scheduled for end-of-period).
     *
     * @param int $membership_id Membership ID
     * @return bool
     */
    public function terminate_subscription($membership_id) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $now = current_time('mysql');
        
        $result = $wpdb->update(
            $table,
            array(
                'status'              => 'cancelled',
                'subscription_status' => 'canceled',
                'cancelled_at'        => $now,
                'updated_at'          => $now,
                'last_billing_sync_at' => $now,
            ),
            array('id' => $membership_id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        $this->log_event($membership_id, 'subscription_terminated', array());
        
        do_action('vh360_subscription_terminated', $membership_id);
        
        return true;
    }
    
    /**
     * Handle plan change (upgrade/downgrade)
     *
     * @param int $membership_id Membership ID
     * @param string $old_plan_key Old plan key
     * @param string $new_plan_key New plan key
     * @return bool
     */
    public function handle_plan_change($membership_id, $old_plan_key, $new_plan_key) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $result = $wpdb->update(
            $table,
            array(
                'plan_key'   => $new_plan_key,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $membership_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        $this->log_event($membership_id, 'plan_changed', array(
            'old_plan' => $old_plan_key,
            'new_plan' => $new_plan_key,
        ));
        
        do_action('vh360_membership_plan_changed', $membership_id, $old_plan_key, $new_plan_key);
        
        return true;
    }
    
    /**
     * Map Stripe subscription status to local VH360 membership status
     *
     * @param string $stripe_status Stripe subscription status
     * @return string VH360 local status
     */
    public function map_subscription_status_to_local($stripe_status) {
        $map = array(
            'active'    => 'active',
            'trialing'  => 'active',
            'past_due'  => 'active',  // Grace: still active but billing failed
            'unpaid'    => 'expired',
            'incomplete' => 'pending',
            'incomplete_expired' => 'expired',
            'canceled'  => 'cancelled',
            'paused'    => 'expired',
        );
        
        return isset($map[$stripe_status]) ? $map[$stripe_status] : 'expired';
    }
    
    // ---------------------------------------------------------------
    // Original order-based lifecycle methods (preserved from Phase 1)
    // ---------------------------------------------------------------
    
    /**
     * Extend membership
     *
     * @param int $membership_id Membership ID
     * @param int $duration Duration value
     * @param string $duration_unit Duration unit
     * @return bool Success
     */
    public function extend_membership($membership_id, $duration, $duration_unit) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Get current membership
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $membership_id
        ));
        
        if (!$membership) {
            return false;
        }
        
        // Calculate new expiration from current expiration or now
        $base_date = $membership->expires_at && strtotime($membership->expires_at) > time() 
            ? $membership->expires_at 
            : current_time('mysql');
            
        $new_expires_at = $this->calculate_expiration_date($duration, $duration_unit, $base_date);
        
        // Update membership
        $result = $wpdb->update(
            $table,
            array(
                'expires_at' => $new_expires_at,
                'updated_at' => current_time('mysql'),
                'status' => 'active',
            ),
            array('id' => $membership_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        // Clear renewal reminder flag so future reminders can be sent
        delete_user_meta($membership->user_id, "_vh360_membership_reminder_sent_{$membership_id}");
        
        // Log event
        $this->log_event($membership_id, 'extended', array(
            'duration' => $duration,
            'duration_unit' => $duration_unit,
            'new_expires_at' => $new_expires_at,
        ), get_current_user_id());
        
        do_action('vh360_membership_extended', $membership_id);
        
        return true;
    }
    
    /**
     * Cancel membership
     *
     * @param int $membership_id Membership ID
     * @return bool Success
     */
    public function cancel_membership($membership_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            VH360_Membership_Database::get_memberships_table(),
            array(
                'status' => 'cancelled',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $membership_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        $this->log_event($membership_id, 'cancelled', array(), get_current_user_id());
        
        do_action('vh360_membership_cancelled', $membership_id);
        
        return true;
    }
    
    /**
     * Mark membership as expired
     *
     * @param int $membership_id Membership ID
     * @return bool Success
     */
    public function expire_membership($membership_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            VH360_Membership_Database::get_memberships_table(),
            array(
                'status' => 'expired',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $membership_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        $this->log_event($membership_id, 'expired', array(), 0);
        
        do_action('vh360_membership_expired', $membership_id);
        
        return true;
    }
    
    /**
     * Calculate expiration date
     *
     * @param int $duration Duration value
     * @param string $duration_unit Unit (days, months, years, lifetime)
     * @param string $base_date Base date (defaults to now)
     * @return string|null Expiration datetime or null for lifetime
     */
    private function calculate_expiration_date($duration, $duration_unit, $base_date = null) {
        if ($duration_unit === 'lifetime' || $duration === 0) {
            return null;
        }
        
        if (!$base_date) {
            $base_date = current_time('mysql');
        }
        
        $timestamp = strtotime($base_date);
        
        switch ($duration_unit) {
            case 'days':
                $timestamp = strtotime("+{$duration} days", $timestamp);
                break;
            case 'months':
                $timestamp = strtotime("+{$duration} months", $timestamp);
                break;
            case 'years':
                $timestamp = strtotime("+{$duration} years", $timestamp);
                break;
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Log membership event
     *
     * @param int $membership_id Membership ID
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @param int $actor_id User who triggered the event
     * @param string $stripe_event_id Optional Stripe event ID
     * @return bool Success
     */
    public function log_event($membership_id, $event_type, $event_data = array(), $actor_id = 0, $stripe_event_id = '') {
        global $wpdb;
        
        $insert_data = array(
            'membership_id' => $membership_id,
            'event_type' => $event_type,
            'event_data' => !empty($event_data) ? wp_json_encode($event_data) : null,
            'actor_id' => $actor_id,
            'stripe_event_id' => !empty($stripe_event_id) ? $stripe_event_id : null,
            'created_at' => current_time('mysql'),
        );
        
        $formats = array('%d', '%s', '%s', '%d', '%s', '%s');
        
        $result = $wpdb->insert(
            VH360_Membership_Database::get_events_table(),
            $insert_data,
            $formats
        );
        
        return $result !== false;
    }
}
