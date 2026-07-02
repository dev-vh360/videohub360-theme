<?php
/**
 * Studio database schema management.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Database {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_studio_recording_jobs';
    }

    public static function maybe_install() {
        if ( get_option( 'vh360_studio_db_version' ) !== VH360_STUDIO_DB_VERSION ) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            room_id varchar(191) NOT NULL DEFAULT '',
            video_id bigint(20) unsigned DEFAULT NULL,
            status varchar(40) NOT NULL DEFAULT 'draft',
            live_provider varchar(80) NOT NULL DEFAULT 'agora_browser',
            recording_provider varchar(80) NOT NULL DEFAULT 'browser_recording',
            storage_provider varchar(80) NOT NULL DEFAULT 'local_media',
            quality_preset varchar(40) NOT NULL DEFAULT 'standard',
            external_id varchar(191) DEFAULT NULL,
            source_url text DEFAULT NULL,
            replay_url text DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY room_id (room_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );
        update_option( 'vh360_studio_db_version', VH360_STUDIO_DB_VERSION );
    }
}
