<?php
/**
 * Membership API Class
 *
 * Handles membership CRUD operations and event logging.
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
     * Create membership
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
                'created_at' => $starts_at,
                'updated_at' => $starts_at,
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
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
     * @return bool Success
     */
    public function log_event($membership_id, $event_type, $event_data = array(), $actor_id = 0) {
        global $wpdb;
        
        $result = $wpdb->insert(
            VH360_Membership_Database::get_events_table(),
            array(
                'membership_id' => $membership_id,
                'event_type' => $event_type,
                'event_data' => !empty($event_data) ? json_encode($event_data) : null,
                'actor_id' => $actor_id,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }
}
