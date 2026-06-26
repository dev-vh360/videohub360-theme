<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove stored options.
delete_option( 'vh360_pwa_options' );
delete_option( 'vh360_pwa_push_settings' );
delete_option( 'vh360_pwa_push_logs' );

// Remove push tokens database table
$table_name = $wpdb->prefix . 'vh360_push_tokens';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Remove push tokens database version option
delete_option( 'vh360_pwa_push_tokens_db_version' );
