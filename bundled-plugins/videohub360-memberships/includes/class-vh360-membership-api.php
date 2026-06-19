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
        
        // No existing subscription record — check for active fixed-term memberships
        // and handle the transition properly
        if (!empty($args['user_id'])) {
            global $wpdb;
            $table = VH360_Membership_Database::get_memberships_table();
            
            $has_active_fixed = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND billing_mode = 'one_time' AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())",
                $args['user_id']
            ));
            
            if ($has_active_fixed > 0) {
                return $this->handle_fixed_to_recurring_transition($args['user_id'], $args['plan_key'], $args);
            }
        }
        
        // Create new subscription membership
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
     * Implements explicit transition rules for recurring subscription changes.
     * Detects upgrade vs downgrade direction, handles tier hierarchy,
     * and manages the resulting access state.
     *
     * @param int $membership_id Membership ID
     * @param string $old_plan_key Old plan key
     * @param string $new_plan_key New plan key
     * @return bool
     */
    public function handle_plan_change($membership_id, $old_plan_key, $new_plan_key) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        $membership = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $membership_id));
        if (!$membership) {
            return false;
        }
        
        // Determine transition direction
        $direction = $this->determine_plan_transition_direction($old_plan_key, $new_plan_key);
        
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
        
        // On upgrade, clear any cancel_at_period_end from the old subscription
        if ($direction === 'upgrade' && $membership->cancel_at_period_end) {
            $wpdb->update(
                $table,
                array(
                    'cancel_at_period_end' => 0,
                    'cancelled_at'         => null,
                ),
                array('id' => $membership_id)
            );
        }
        
        $this->log_event($membership_id, 'plan_changed', array(
            'old_plan'  => $old_plan_key,
            'new_plan'  => $new_plan_key,
            'direction' => $direction,
        ));
        
        do_action('vh360_membership_plan_changed', $membership_id, $old_plan_key, $new_plan_key, $direction);
        
        return true;
    }
    
    /**
     * Handle transition from a fixed-term membership to a recurring subscription
     *
     * When a user with an active fixed-term membership starts a recurring subscription,
     * the recurring membership is created separately. The fixed-term membership is marked
     * as superseded so the access helpers know to prefer the recurring one.
     *
     * @param int    $user_id     WordPress user ID
     * @param string $new_plan_key New recurring plan key
     * @param array  $subscription_args Subscription membership args for create_subscription_membership()
     * @return int|false New membership ID or false
     */
    public function handle_fixed_to_recurring_transition($user_id, $new_plan_key, $subscription_args) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Find existing active fixed-term memberships for this user
        $fixed_memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND billing_mode = 'one_time' AND status = 'active'",
            $user_id
        ));
        
        // Create the new recurring membership
        $new_membership_id = $this->create_subscription_membership($subscription_args);
        
        if (!$new_membership_id) {
            return false;
        }
        
        // Mark overlapping fixed-term memberships as superseded
        foreach ($fixed_memberships as $fixed) {
            $this->log_event($fixed->id, 'superseded_by_recurring', array(
                'new_membership_id' => $new_membership_id,
                'new_plan_key'      => $new_plan_key,
            ));
            
            $wpdb->update(
                $table,
                array(
                    'status'     => 'superseded',
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $fixed->id),
                array('%s', '%s'),
                array('%d')
            );
        }
        
        $this->log_event($new_membership_id, 'transitioned_from_fixed', array(
            'superseded_ids' => wp_list_pluck($fixed_memberships, 'id'),
        ));
        
        return $new_membership_id;
    }
    
    /**
     * Get the effective (highest-priority) membership for a user
     *
     * When a user has multiple active membership records, this applies
     * precedence rules to determine which one controls access.
     *
     * Precedence order:
     * 1. Active recurring subscription (most recently synced)
     * 2. Active fixed-term / one-time membership (latest created)
     * 3. Superseded memberships are skipped
     *
     * @param int    $user_id         WordPress user ID
     * @param int    $grace_period_days Grace period in days
     * @return object|null Membership row or null
     */
    public function get_effective_membership($user_id, $grace_period_days = 0) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Build expiration check with grace period
        if ($grace_period_days > 0) {
            $expiration_check = $wpdb->prepare(
                "(expires_at IS NULL OR DATE_ADD(expires_at, INTERVAL %d DAY) > NOW())",
                $grace_period_days
            );
        } else {
            $expiration_check = "(expires_at IS NULL OR expires_at > NOW())";
        }
        
        // First try: active recurring subscription (highest priority)
        $recurring = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE user_id = %d
            AND status = 'active'
            AND billing_mode = 'recurring'
            AND {$expiration_check}
            ORDER BY last_billing_sync_at DESC, created_at DESC
            LIMIT 1",
            $user_id
        ));
        
        if ($recurring) {
            return $recurring;
        }
        
        // Second try: active one-time or lifetime membership.
        // Prefer the highest configured tier, then latest expiration, then latest creation date.
        $fixed_memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE user_id = %d
            AND status = 'active'
            AND billing_mode != 'recurring'
            AND {$expiration_check}
            ORDER BY created_at DESC",
            $user_id
        ));

        if (empty($fixed_memberships)) {
            return null;
        }

        usort($fixed_memberships, function($a, $b) {
            $tier_a = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_tier($a->plan_key) : 0;
            $tier_b = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_tier($b->plan_key) : 0;
            if ($tier_a !== $tier_b) {
                return $tier_b <=> $tier_a;
            }
            $expires_a = empty($a->expires_at) ? PHP_INT_MAX : strtotime($a->expires_at);
            $expires_b = empty($b->expires_at) ? PHP_INT_MAX : strtotime($b->expires_at);
            if ($expires_a !== $expires_b) {
                return $expires_b <=> $expires_a;
            }
            return strtotime($b->created_at) <=> strtotime($a->created_at);
        });

        return reset($fixed_memberships);
    }
    
    /**
     * Determine the transition direction between two plan keys
     *
     * Uses a tier hierarchy to classify changes as upgrade, downgrade, or lateral.
     *
     * @param string $old_plan Old plan key
     * @param string $new_plan New plan key
     * @return string 'upgrade', 'downgrade', or 'lateral'
     */
    private function determine_plan_transition_direction($old_plan, $new_plan) {
        $tiers = $this->get_plan_tier_hierarchy();
        
        $old_tier = isset($tiers[$old_plan]) ? $tiers[$old_plan] : 0;
        $new_tier = isset($tiers[$new_plan]) ? $tiers[$new_plan] : 0;
        
        if ($new_tier > $old_tier) {
            return 'upgrade';
        } elseif ($new_tier < $old_tier) {
            return 'downgrade';
        }
        
        return 'lateral';
    }
    
    /**
     * Get plan tier hierarchy
     *
     * Returns a numeric tier level for each plan key.
     * Higher numbers = higher tier. Filterable for customization.
     *
     * @return array plan_key => tier_level
     */
    private function get_plan_tier_hierarchy() {
        $hierarchy = array();
        if (class_exists('VH360_Membership_Plans')) {
            foreach (VH360_Membership_Plans::get_plan_registry() as $key => $plan) {
                $hierarchy[$key] = isset($plan['tier_level']) ? (int) $plan['tier_level'] : VH360_Membership_Plans::get_plan_tier($key);
            }
        }

        
        return apply_filters('vh360_plan_tier_hierarchy', $hierarchy);
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
     * Reactivate local membership access.
     *
     * @param int $membership_id Membership ID.
     * @param array $args Optional args. Supports expires_at.
     * @return bool Success.
     */
    public function reactivate_membership($membership_id, $args = array()) {
        global $wpdb;

        $data = array(
            'status' => 'active',
            'updated_at' => current_time('mysql'),
        );
        $formats = array('%s', '%s');

        if (array_key_exists('expires_at', $args)) {
            $data['expires_at'] = $args['expires_at'] ? sanitize_text_field($args['expires_at']) : null;
            $formats[] = '%s';
        }

        $result = $wpdb->update(
            VH360_Membership_Database::get_memberships_table(),
            $data,
            array('id' => absint($membership_id)),
            $formats,
            array('%d')
        );

        if ($result === false) {
            return false;
        }

        $this->log_event($membership_id, 'reactivated', array('expires_at' => isset($data['expires_at']) ? $data['expires_at'] : null), get_current_user_id());
        do_action('vh360_membership_reactivated', $membership_id, $args);

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
