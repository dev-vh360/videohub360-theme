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

    public static function chunks_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_studio_recording_chunks';
    }

    public static function bible_translations_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_studio_bible_translations';
    }

    public static function bible_verses_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_studio_bible_verses';
    }

    public static function bible_import_jobs_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_studio_bible_import_jobs';
    }

    public static function maybe_install() {
        if ( get_option( 'vh360_studio_db_version' ) !== VH360_STUDIO_DB_VERSION ) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name       = self::table_name();
        $chunks_table_name = self::chunks_table_name();
        $bible_translations_table = self::bible_translations_table_name();
        $bible_verses_table       = self::bible_verses_table_name();
        $bible_jobs_table         = self::bible_import_jobs_table_name();
        $charset_collate  = $wpdb->get_charset_collate();

        // Phase 1A has not shipped with production data. Replace the initial
        // unapproved scaffold schema cleanly if it was activated during review.
        if ( '1.0.0' === get_option( 'vh360_studio_db_version' ) ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "DROP TABLE IF EXISTS {$chunks_table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            source_type varchar(40) NOT NULL DEFAULT 'live_room',
            source_id varchar(191) NOT NULL DEFAULT '',
            live_video_id bigint(20) unsigned DEFAULT NULL,
            room_id varchar(191) NOT NULL DEFAULT '',
            recording_mode varchar(40) NOT NULL DEFAULT 'browser',
            capture_scope varchar(40) NOT NULL DEFAULT 'interactive_composite',
            quality_preset varchar(40) NOT NULL DEFAULT 'high_1080p',
            storage_provider varchar(80) NOT NULL DEFAULT 'videopress',
            status varchar(40) NOT NULL DEFAULT 'created',
            browser_session_id varchar(191) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            duration_seconds int(10) unsigned DEFAULT NULL,
            file_size bigint(20) unsigned DEFAULT NULL,
            expected_chunks int(10) unsigned DEFAULT NULL,
            received_chunks int(10) unsigned DEFAULT NULL,
            assembled_checksum varchar(64) DEFAULT NULL,
            assembled_at datetime DEFAULT NULL,
            temp_expires_at datetime DEFAULT NULL,
            publish_attempted_at datetime DEFAULT NULL,
            publish_provider_status varchar(80) DEFAULT NULL,
            replay_video_id bigint(20) unsigned DEFAULT NULL,
            published_at datetime DEFAULT NULL,
            local_temp_path text DEFAULT NULL,
            wp_attachment_id bigint(20) unsigned DEFAULT NULL,
            videopress_guid varchar(191) DEFAULT NULL,
            videopress_processing_done tinyint(1) NOT NULL DEFAULT 0,
            publitio_file_id varchar(191) DEFAULT NULL,
            provider_file_id varchar(191) DEFAULT NULL,
            provider_embed_url text DEFAULT NULL,
            provider_metadata longtext DEFAULT NULL,
            playback_url text DEFAULT NULL,
            poster_url text DEFAULT NULL,
            error_message text DEFAULT NULL,
            retry_count int(10) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            started_at datetime DEFAULT NULL,
            stopped_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY source_type (source_type),
            KEY source_id (source_id),
            KEY live_video_id (live_video_id),
            KEY replay_video_id (replay_video_id),
            KEY room_id (room_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );

        if ( ! get_option( 'vh360_studio_capture_scope_backfilled' ) ) {
            $wpdb->query( "UPDATE {$table_name} SET capture_scope = 'program' WHERE source_type IN ('studio_setup','livestream_video') AND (capture_scope = '' OR capture_scope = 'interactive_composite')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "UPDATE {$table_name} SET capture_scope = 'interactive_composite' WHERE source_type IN ('live_room','appointment_session') AND (capture_scope = '' OR capture_scope IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            update_option( 'vh360_studio_capture_scope_backfilled', 'yes', false );
        }

        $chunks_sql = "CREATE TABLE {$chunks_table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            browser_session_id varchar(191) NOT NULL,
            chunk_index int(10) unsigned NOT NULL,
            chunk_size bigint(20) unsigned NOT NULL DEFAULT 0,
            mime_type varchar(100) NOT NULL DEFAULT '',
            file_path text NOT NULL,
            checksum varchar(64) NOT NULL DEFAULT '',
            status varchar(40) NOT NULL DEFAULT 'received',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_session_chunk (job_id,browser_session_id,chunk_index),
            KEY job_id (job_id),
            KEY user_id (user_id),
            KEY browser_session_id (browser_session_id),
            KEY chunk_index (chunk_index),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta( $chunks_sql );

        $bible_translations_sql = "CREATE TABLE {$bible_translations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            translation_key varchar(64) NOT NULL,
            name varchar(191) NOT NULL,
            abbreviation varchar(40) NOT NULL,
            language varchar(80) NOT NULL DEFAULT 'en',
            source_name varchar(191) NOT NULL DEFAULT '',
            source_url text DEFAULT NULL,
            license_name varchar(191) NOT NULL DEFAULT '',
            license_url text DEFAULT NULL,
            attribution text DEFAULT NULL,
            attribution_required tinyint(1) NOT NULL DEFAULT 0,
            dataset_version varchar(100) NOT NULL DEFAULT '',
            source_hash varchar(64) NOT NULL DEFAULT '',
            status varchar(40) NOT NULL DEFAULT 'importing',
            verse_count int(10) unsigned NOT NULL DEFAULT 0,
            book_count int(10) unsigned NOT NULL DEFAULT 0,
            imported_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY translation_key (translation_key),
            KEY status (status),
            KEY language (language)
        ) {$charset_collate};";
        dbDelta( $bible_translations_sql );

        $bible_verses_sql = "CREATE TABLE {$bible_verses_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            translation_key varchar(64) NOT NULL,
            book_key varchar(8) NOT NULL,
            book_name varchar(100) NOT NULL,
            book_order int(10) unsigned NOT NULL,
            chapter_number int(10) unsigned NOT NULL,
            verse_number int(10) unsigned NOT NULL,
            verse_suffix varchar(8) NOT NULL DEFAULT '',
            verse_text longtext NOT NULL,
            verse_status varchar(20) NOT NULL DEFAULT 'present',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY translation_reference (translation_key,book_key,chapter_number,verse_number,verse_suffix),
            KEY translation_book_chapter (translation_key,book_order,chapter_number),
            KEY translation_book (translation_key,book_key)
        ) {$charset_collate};";
        dbDelta( $bible_verses_sql );

        $bible_jobs_sql = "CREATE TABLE {$bible_jobs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            translation_key varchar(64) NOT NULL,
            temporary_translation_key varchar(80) NOT NULL DEFAULT '',
            source_file text NOT NULL,
            source_hash varchar(64) NOT NULL DEFAULT '',
            status varchar(40) NOT NULL DEFAULT 'created',
            byte_offset bigint(20) unsigned NOT NULL DEFAULT 0,
            rows_processed int(10) unsigned NOT NULL DEFAULT 0,
            rows_imported int(10) unsigned NOT NULL DEFAULT 0,
            rows_omitted int(10) unsigned NOT NULL DEFAULT 0,
            rows_failed int(10) unsigned NOT NULL DEFAULT 0,
            book_count int(10) unsigned NOT NULL DEFAULT 0,
            error_message longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY translation_key (translation_key),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta( $bible_jobs_sql );

        update_option( 'vh360_studio_db_version', VH360_STUDIO_DB_VERSION );
    }
}
