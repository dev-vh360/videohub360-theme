<?php
/**
 * Cleanup when plugin is uninstalled (deleted, not just deactivated)
 *
 * This file runs when the plugin is deleted through the WordPress admin.
 * It removes all database tables, options, and meta data created by the plugin.
 *
 * @package VH360_Community
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete database table
$table_name = $wpdb->prefix . 'vh360_comment_likes';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete options
delete_option('vh360_community_activated');
delete_option('vh360_community_db_version');

// Delete transients
delete_transient('vh360_community_cache');

// Delete all share count post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'vh360_share_count'");
