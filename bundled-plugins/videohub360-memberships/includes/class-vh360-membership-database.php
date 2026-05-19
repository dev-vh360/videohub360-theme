<?php
/**
 * Membership Database Handler
 *
 * Handles database table creation, migrations, and basic database operations.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Database {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Database
     */
    private static $instance = null;
    
    /**
     * Database version
     *
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Database
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
        // Hook to check database version
        add_action('plugins_loaded', array($this, 'check_database_version'));
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_database_version() {
        $current_version = get_option('vh360_memberships_db_version', '0');
        
        if (version_compare($current_version, $this->db_version, '<')) {
            self::create_tables();
            
            // Run migration from 1.x to 2.0 if upgrading
            if (version_compare($current_version, '1.0.0', '<') && version_compare($current_version, '0', '>')) {
                self::migrate_to_v2();
            }
        }
    }
    
    /**
     * Create database tables
     *
     * Uses dbDelta for safe creation and column additions.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main memberships table with recurring subscription columns
        $memberships_table = $wpdb->prefix . 'vh360_memberships';
        $memberships_sql = "CREATE TABLE {$memberships_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_key varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            source_order_id bigint(20) DEFAULT NULL,
            starts_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            billing_provider varchar(50) DEFAULT NULL,
            billing_mode varchar(20) DEFAULT 'one_time',
            stripe_customer_id varchar(255) DEFAULT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            subscription_status varchar(50) DEFAULT NULL,
            current_period_start datetime DEFAULT NULL,
            current_period_end datetime DEFAULT NULL,
            cancel_at_period_end tinyint(1) NOT NULL DEFAULT 0,
            cancelled_at datetime DEFAULT NULL,
            last_billing_sync_at datetime DEFAULT NULL,
            last_webhook_event_id varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY plan_key (plan_key),
            KEY status (status),
            KEY expires_at (expires_at),
            KEY user_plan_status (user_id, plan_key, status),
            KEY stripe_subscription_id (stripe_subscription_id),
            KEY stripe_customer_id (stripe_customer_id),
            KEY billing_mode (billing_mode),
            KEY subscription_status (subscription_status)
        ) $charset_collate;";
        
        // Membership events table
        $events_table = $wpdb->prefix . 'vh360_membership_events';
        $events_sql = "CREATE TABLE {$events_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            membership_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data text DEFAULT NULL,
            actor_id bigint(20) DEFAULT NULL,
            stripe_event_id varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY membership_id (membership_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY stripe_event_id (stripe_event_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($memberships_sql);
        dbDelta($events_sql);
        
        // Update database version
        update_option('vh360_memberships_db_version', '1.0.0');
    }
    
    /**
     * Migrate existing data from v1 to v2
     *
     * Sets billing_mode to 'one_time' for all existing records that predate
     * the recurring subscription columns.
     */
    private static function migrate_to_v2() {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_memberships';
        
        // Set all existing memberships to one_time billing mode
        $wpdb->query(
            "UPDATE {$table} SET billing_mode = 'one_time', billing_provider = 'woocommerce' WHERE billing_mode IS NULL OR billing_mode = ''"
        );
    }
    
    /**
     * Get memberships table name
     *
     * @return string
     */
    public static function get_memberships_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_memberships';
    }
    
    /**
     * Get events table name
     *
     * @return string
     */
    public static function get_events_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_membership_events';
    }
    
    /**
     * Check if a Stripe event has already been processed
     *
     * @param string $stripe_event_id Stripe event ID
     * @return bool True if already processed
     */
    public static function is_stripe_event_processed($stripe_event_id) {
        global $wpdb;
        $table = self::get_events_table();
        
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE stripe_event_id = %s",
            $stripe_event_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get membership by Stripe subscription ID
     *
     * @param string $subscription_id Stripe subscription ID
     * @return object|null Membership row or null
     */
    public static function get_membership_by_subscription_id($subscription_id) {
        global $wpdb;
        $table = self::get_memberships_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE stripe_subscription_id = %s ORDER BY created_at DESC LIMIT 1",
            $subscription_id
        ));
    }
    
    /**
     * Get membership by Stripe customer ID and plan
     *
     * @param string $customer_id Stripe customer ID
     * @param string $plan_key Plan key
     * @return object|null Membership row or null
     */
    public static function get_membership_by_customer_and_plan($customer_id, $plan_key) {
        global $wpdb;
        $table = self::get_memberships_table();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE stripe_customer_id = %s AND plan_key = %s ORDER BY created_at DESC LIMIT 1",
            $customer_id,
            $plan_key
        ));
    }
}
