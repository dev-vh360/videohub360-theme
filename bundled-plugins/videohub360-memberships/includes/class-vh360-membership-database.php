<?php
/**
 * Membership Database Handler
 *
 * Handles database table creation and basic database operations.
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
        }
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main memberships table
        $memberships_table = $wpdb->prefix . 'vh360_memberships';
        $memberships_sql = "CREATE TABLE {$memberships_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_key varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            source_order_id bigint(20) DEFAULT NULL,
            starts_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY plan_key (plan_key),
            KEY status (status),
            KEY expires_at (expires_at),
            KEY user_plan_status (user_id, plan_key, status)
        ) $charset_collate;";
        
        // Membership events table
        $events_table = $wpdb->prefix . 'vh360_membership_events';
        $events_sql = "CREATE TABLE {$events_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            membership_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data text DEFAULT NULL,
            actor_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY membership_id (membership_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($memberships_sql);
        dbDelta($events_sql);
        
        // Update database version
        update_option('vh360_memberships_db_version', '1.0.0');
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
}
