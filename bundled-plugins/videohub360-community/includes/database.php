<?php
/**
 * Database management for VideoHub360 Community Plugin
 *
 * Handles table creation, upgrades, and versioning.
 *
 * @package VH360_Community
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VH360_Community_Database {
    
    /**
     * Create database tables for the plugin
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vh360_comment_likes';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comment_user (comment_id, user_id),
            KEY comment_id (comment_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update version option
        update_option('vh360_community_db_version', VH360_COMMUNITY_DB_VERSION);
    }
    
    /**
     * Check if database needs upgrading and upgrade if necessary
     */
    public static function maybe_upgrade() {
        $installed_version = get_option('vh360_community_db_version', '0.0.0');
        
        if (version_compare($installed_version, VH360_COMMUNITY_DB_VERSION, '<')) {
            self::create_tables();
        }
    }
}
