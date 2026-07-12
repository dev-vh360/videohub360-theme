<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Bible_Importer {
    const MIN_VERSES = 25000;

    public function create_job( $user_id, $file, $meta ) {
        if ( empty( $meta['permissionConfirmed'] ) ) { return new WP_Error( 'vh360_bible_permission_required', __( 'Confirm that you have permission to use this file.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        foreach ( array( 'name' => array( 'name', 191 ), 'sourceName' => array( 'source_name', 191 ), 'licenseName' => array( 'license_name', 191 ) ) as $input_key => $limit ) { $value = sanitize_text_field( isset( $meta[ $input_key ] ) ? $meta[ $input_key ] : '' ); if ( $this->text_length( $value ) > $limit[1] ) { return new WP_Error( 'vh360_bible_import_metadata_too_long', __( 'Bible translation metadata exceeds the supported length.', 'videohub360-studio' ), array( 'status' => 400, 'field' => $limit[0] ) ); } }
        $clean_meta = $this->sanitize_meta( $meta );
        $required_clean = array( 'translation_key', 'name', 'abbreviation', 'language', 'source_name', 'license_name', 'dataset_version' );
        foreach ( $required_clean as $k ) { if ( '' === trim( (string) $clean_meta[ $k ] ) ) { return new WP_Error( 'vh360_bible_import_metadata_required', __( 'Bible translation metadata is required.', 'videohub360-studio' ), array( 'status' => 400 ) ); } }
        if ( ! empty( $clean_meta['attribution_required'] ) && '' === trim( (string) $clean_meta['attribution'] ) ) { return new WP_Error( 'vh360_bible_attribution_required', __( 'Attribution text is required when attribution is required by the license.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        if ( $this->text_length( $clean_meta['attribution'] ) > 500 ) { return new WP_Error( 'vh360_bible_attribution_too_long', __( 'Attribution text must be 500 characters or fewer.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) { return new WP_Error( 'vh360_bible_upload_required', __( 'CSV file is required.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $ext = strtolower( pathinfo( isset( $file['name'] ) ? $file['name'] : '', PATHINFO_EXTENSION ) );
        if ( 'csv' !== $ext ) { return new WP_Error( 'vh360_bible_upload_type', __( 'Upload a CSV file.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $uploads = wp_upload_dir(); $dir = trailingslashit( $uploads['basedir'] ) . 'vh360-studio-bible-private'; wp_mkdir_p( $dir ); if ( ! file_exists( trailingslashit( $dir ) . '.htaccess' ) ) { file_put_contents( trailingslashit( $dir ) . '.htaccess', "Deny from all\n" ); } if ( ! file_exists( trailingslashit( $dir ) . 'index.html' ) ) { file_put_contents( trailingslashit( $dir ) . 'index.html', '' ); }
        $dest = $dir . '/' . wp_unique_filename( $dir, 'vh360-bible-' . wp_generate_uuid4() . '.csv' );
        if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) { return new WP_Error( 'vh360_bible_upload_failed', __( 'CSV upload failed.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $header_error = $this->validate_header( $dest ); if ( is_wp_error( $header_error ) ) { wp_delete_file( $dest ); return $header_error; }
        global $wpdb; $jobs = VH360_Studio_Database::bible_import_jobs_table_name(); $now = current_time( 'mysql' ); $key = sanitize_key( $meta['translationKey'] ); if ( '' === $key || strlen( $key ) > 64 || 0 === strpos( $key, '__vh360_' ) ) { wp_delete_file( $dest ); return new WP_Error( 'vh360_bible_translation_key_invalid', __( 'Translation key is invalid or reserved.', 'videohub360-studio' ), array( 'status' => 400 ) ); } $hash = hash_file( 'sha256', $dest );
        $payload = array( 'meta' => $clean_meta, 'errors' => array() );
        $ok = $wpdb->insert( $jobs, array( 'user_id' => absint( $user_id ), 'translation_key' => $key, 'source_file' => $dest, 'source_hash' => $hash, 'status' => 'created', 'created_at' => $now, 'updated_at' => $now, 'error_message' => wp_json_encode( $payload ) ) );
        if ( false === $ok ) { wp_delete_file( $dest ); return new WP_Error( 'vh360_bible_import_create_failed', __( 'Import job could not be created.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $id = (int) $wpdb->insert_id; if ( false === $wpdb->update( $jobs, array( 'temporary_translation_key' => '__vh360_import_' . $id ), array( 'id' => $id ) ) ) { wp_delete_file( $dest ); $wpdb->delete( $jobs, array( 'id' => $id ) ); return new WP_Error( 'vh360_bible_import_create_failed', __( 'Import job could not be created.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        return $this->get_job( $id );
    }

    private function sanitize_meta( $m ) {
        return array( 'translation_key' => $this->limit_text( sanitize_key( isset( $m['translationKey'] ) ? $m['translationKey'] : '' ), 64 ), 'name' => $this->limit_text( sanitize_text_field( isset( $m['name'] ) ? $m['name'] : '' ), 191 ), 'abbreviation' => $this->limit_text( sanitize_text_field( isset( $m['abbreviation'] ) ? $m['abbreviation'] : '' ), 40 ), 'language' => $this->limit_text( sanitize_text_field( isset( $m['language'] ) ? $m['language'] : '' ), 40 ), 'source_name' => $this->limit_text( sanitize_text_field( isset( $m['sourceName'] ) ? $m['sourceName'] : '' ), 191 ), 'source_url' => esc_url_raw( isset( $m['sourceUrl'] ) ? $m['sourceUrl'] : '' ), 'license_name' => $this->limit_text( sanitize_text_field( isset( $m['licenseName'] ) ? $m['licenseName'] : '' ), 191 ), 'license_url' => esc_url_raw( isset( $m['licenseUrl'] ) ? $m['licenseUrl'] : '' ), 'attribution' => sanitize_textarea_field( isset( $m['attribution'] ) ? $m['attribution'] : '' ), 'attribution_required' => ! empty( $m['attributionRequired'] ) ? 1 : 0, 'dataset_version' => $this->limit_text( sanitize_text_field( isset( $m['datasetVersion'] ) ? $m['datasetVersion'] : '' ), 100 ) );
    }

    private function limit_text( $value, $length ) { return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length, 'UTF-8' ) : preg_replace( '/^(.{0,' . absint( $length ) . '}).*$/us', '$1', $value ); }

    private function text_length( $value ) { return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value, 'UTF-8' ) : preg_match_all( '/./us', (string) $value, $m ); }

    private function validate_header( $file ) {
        $fh = fopen( $file, 'r' ); if ( ! $fh ) { return new WP_Error( 'vh360_bible_import_file_missing', __( 'Import file missing.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $header = fgetcsv( $fh ); fclose( $fh ); if ( ! $header || count( $header ) !== 4 ) { return new WP_Error( 'vh360_bible_import_header_invalid', __( 'CSV header must be Book,Chapter,Verse,Text.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
        $expected = array( 'book', 'chapter', 'verse', 'text' );
        foreach ( $expected as $i => $name ) { if ( strtolower( trim( $header[ $i ] ) ) !== $name ) { return new WP_Error( 'vh360_bible_import_header_invalid', __( 'CSV header must be Book,Chapter,Verse,Text.', 'videohub360-studio' ), array( 'status' => 400 ) ); } }
        return true;
    }

    public function list_jobs() { global $wpdb; $j = VH360_Studio_Database::bible_import_jobs_table_name(); return $wpdb->get_results( "SELECT * FROM {$j} ORDER BY id DESC LIMIT 25", ARRAY_A ); }

    public function get_job( $id ) { global $wpdb; $j = VH360_Studio_Database::bible_import_jobs_table_name(); return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$j} WHERE id=%d", absint( $id ) ), ARRAY_A ); }

    public function cancel_job( $id ) {
        $lock = $this->acquire_job_lock( $id ); if ( is_wp_error( $lock ) ) { return $lock; }
        global $wpdb; $job = $this->get_job( $id );
        if ( ! $job ) { $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_missing', __( 'Import job not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( ! in_array( $job['status'], array( 'created', 'validating', 'importing', 'promoting' ), true ) ) { $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_state', __( 'Import job cannot be cancelled in its current state.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $cleanup = $this->cleanup_staging( $job, true );
        if ( is_wp_error( $cleanup ) ) { $this->release_job_lock( $id ); return $cleanup; }
        $updated = $wpdb->update( VH360_Studio_Database::bible_import_jobs_table_name(), array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $id ) ) );
        $this->release_job_lock( $id );
        return false === $updated ? new WP_Error( 'vh360_bible_import_cancel_failed', __( 'Import job could not be cancelled.', 'videohub360-studio' ), array( 'status' => 500 ) ) : true;
    }

    public function clean_failed_job( $id ) {
        $lock = $this->acquire_job_lock( $id ); if ( is_wp_error( $lock ) ) { return $lock; }
        global $wpdb; $job = $this->get_job( $id );
        if ( ! $job ) { $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_missing', __( 'Import job not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( ! in_array( $job['status'], array( 'failed', 'cancelled' ), true ) ) { $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_state', __( 'Import job cannot be cleaned in its current state.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $cleanup = $this->cleanup_staging( $job, true );
        if ( is_wp_error( $cleanup ) ) { $this->release_job_lock( $id ); return $cleanup; }
        $deleted = $wpdb->delete( VH360_Studio_Database::bible_import_jobs_table_name(), array( 'id' => absint( $id ) ) );
        $this->release_job_lock( $id );
        return false === $deleted ? new WP_Error( 'vh360_bible_import_clean_failed', __( 'Import job could not be cleaned.', 'videohub360-studio' ), array( 'status' => 500 ) ) : true;
    }

    public function process_batch( $id, $limit = null ) {
        global $wpdb; $job = $this->get_job( $id ); if ( ! $job ) { return new WP_Error( 'vh360_bible_import_missing', __( 'Import job not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( ! in_array( $job['status'], array( 'created', 'validating', 'importing' ), true ) ) { return new WP_Error( 'vh360_bible_import_state', __( 'Import job cannot be processed in its current state.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $lock = $this->acquire_job_lock( $id ); if ( is_wp_error( $lock ) ) { return $lock; }
        $limit = min( 1000, max( 1, absint( $limit ? $limit : apply_filters( 'vh360_studio_bible_import_batch_size', 500 ) ) ) );
        $fh = fopen( $job['source_file'], 'r' ); if ( ! $fh ) { $this->release_job_lock( $id ); return $this->fail_job( $job, __( 'Import file missing.', 'videohub360-studio' ), true ); }
        if ( false === $wpdb->query( 'START TRANSACTION' ) ) { fclose( $fh ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_transaction_failed', __( 'Could not start import transaction.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $job = $this->get_job( $id ); if ( ! $job || ! in_array( $job['status'], array( 'created', 'validating', 'importing' ), true ) ) { $wpdb->query( 'ROLLBACK' ); fclose( $fh ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_state', __( 'Import job cannot be processed in its current state.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        $verses = VH360_Studio_Database::bible_verses_table_name(); $jobs = VH360_Studio_Database::bible_import_jobs_table_name(); $now = current_time( 'mysql' ); $processed = $imported = $omitted = $failed = 0; $errors = array(); $offset = (int) $job['byte_offset'];
        if ( $offset > 0 ) { fseek( $fh, $offset ); } else { fgetcsv( $fh ); if ( false === $wpdb->update( $jobs, array( 'status' => 'importing', 'updated_at' => $now ), array( 'id' => $id ) ) ) { $wpdb->query( 'ROLLBACK' ); fclose( $fh ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_progress_failed', __( 'Import progress could not be saved.', 'videohub360-studio' ), array( 'status' => 500 ) ); } }
        while ( $processed < $limit && ( $row = fgetcsv( $fh ) ) !== false ) {
            $processed++; $row_num = (int) $job['rows_processed'] + $processed + 1;
            $valid = $this->validate_row( $row, $row_num );
            if ( is_wp_error( $valid ) ) { $failed++; $errors[] = $valid->get_error_message(); continue; }
            $status = trim( $valid['verse_text'] ) === '' ? 'omitted' : 'present';
            $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$verses} WHERE translation_key=%s AND book_key=%s AND chapter_number=%d AND verse_number=%d AND verse_suffix=%s", $job['temporary_translation_key'], $valid['book_key'], $valid['chapter_number'], $valid['verse_number'], $valid['verse_suffix'] ) );
            if ( $exists ) { $failed++; $errors[] = sprintf( __( 'Duplicate reference at row %d.', 'videohub360-studio' ), $row_num ); continue; }
            $ok = $wpdb->insert( $verses, array_merge( $valid, array( 'translation_key' => $job['temporary_translation_key'], 'verse_status' => $status, 'created_at' => $now, 'updated_at' => $now ) ) );
            if ( false === $ok ) { $wpdb->query( 'ROLLBACK' ); fclose( $fh ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_insert_failed', sprintf( __( 'Database insert failed at row %d.', 'videohub360-studio' ), $row_num ), array( 'status' => 500 ) ); } elseif ( 'omitted' === $status ) { $omitted++; } else { $imported++; }
        }
        $eof = feof( $fh ); $byte = ftell( $fh ); fclose( $fh );
        $this->append_errors( $job, $errors );
        $progress_updated = $wpdb->update( $jobs, array( 'status' => $eof ? ( ( (int) $job['rows_failed'] + $failed ) > 0 ? 'failed' : 'validating' ) : 'importing', 'byte_offset' => $byte, 'rows_processed' => (int) $job['rows_processed'] + $processed, 'rows_imported' => (int) $job['rows_imported'] + $imported, 'rows_omitted' => (int) $job['rows_omitted'] + $omitted, 'rows_failed' => (int) $job['rows_failed'] + $failed, 'updated_at' => $now ), array( 'id' => $id ) );
        if ( false === $progress_updated ) { $wpdb->query( 'ROLLBACK' ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_progress_failed', __( 'Import progress could not be saved.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        if ( false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_commit_failed', __( 'Import checkpoint could not be committed.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $job = $this->get_job( $id );
        if ( $eof && ( $failed || (int) $job['rows_failed'] > 0 ) ) { $cleanup = $this->cleanup_staging( $job, false ); if ( is_wp_error( $cleanup ) ) { $this->release_job_lock( $id ); return $cleanup; } if ( file_exists( $job['source_file'] ) ) { wp_delete_file( $job['source_file'] ); } if ( file_exists( $job['source_file'] ) ) { $this->release_job_lock( $id ); return new WP_Error( 'vh360_bible_import_cleanup_failed', __( 'Import source file could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) ); } $this->release_job_lock( $id ); return $job; }
        if ( $eof ) { $result = $this->promote( $id ); $this->release_job_lock( $id ); return $result; }
        $this->release_job_lock( $id ); return $job;
    }


    private function acquire_job_lock( $id ) {
        global $wpdb; $name = 'vh360_bible_import_' . absint( $id );
        $locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) );
        return 1 === $locked ? true : new WP_Error( 'vh360_bible_import_locked', __( 'Import job is already being processed.', 'videohub360-studio' ), array( 'status' => 409 ) );
    }

    private function release_job_lock( $id ) {
        global $wpdb; $name = 'vh360_bible_import_' . absint( $id );
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
    }

    private function validate_row( $row, $row_num ) {
        if ( count( $row ) !== 4 ) { return new WP_Error( 'vh360_bible_row_invalid', sprintf( __( 'Row %d must contain exactly four CSV columns.', 'videohub360-studio' ), $row_num ) ); }
        $book = VH360_Studio_Bible_Books::normalize_name( $row[0] ); $chap = absint( $row[1] ); preg_match( '/^(\d+)([a-z]?)$/i', trim( $row[2] ), $vm ); $verse = $vm ? absint( $vm[1] ) : 0; $suffix = $vm ? strtolower( $vm[2] ) : '';
        $raw_text = (string) $row[3]; $text = wp_check_invalid_utf8( $raw_text );
        if ( '' !== $raw_text && '' === $text ) { return new WP_Error( 'vh360_bible_row_invalid_utf8', sprintf( __( 'Invalid UTF-8 text at row %d.', 'videohub360-studio' ), $row_num ) ); }
        if ( ! $book ) { return new WP_Error( 'vh360_bible_book_unknown', sprintf( __( 'Unrecognized book at row %d.', 'videohub360-studio' ), $row_num ) ); }
        if ( $chap < 1 || $verse < 1 || strlen( $text ) > 20000 ) { return new WP_Error( 'vh360_bible_row_invalid', sprintf( __( 'Invalid chapter, verse, or text length at row %d.', 'videohub360-studio' ), $row_num ) ); }
        return array( 'book_key' => $book['key'], 'book_name' => $book['default_name'], 'book_order' => $book['order'], 'chapter_number' => $chap, 'verse_number' => $verse, 'verse_suffix' => $suffix, 'verse_text' => $text );
    }

    private function promote( $id ) {
        global $wpdb; $job = $this->get_job( $id ); if ( false === $wpdb->update( VH360_Studio_Database::bible_import_jobs_table_name(), array( 'status' => 'promoting', 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id, 'status' => 'validating' ) ) || 1 !== (int) $wpdb->rows_affected ) { return new WP_Error( 'vh360_bible_import_state', __( 'Import job cannot be promoted in its current state.', 'videohub360-studio' ), array( 'status' => 409 ) ); } $job = $this->get_job( $id ); $v = VH360_Studio_Database::bible_verses_table_name(); $t = VH360_Studio_Database::bible_translations_table_name(); $jobs = VH360_Studio_Database::bible_import_jobs_table_name(); $now = current_time( 'mysql' );
        $payload = json_decode( $job['error_message'], true ); $meta = isset( $payload['meta'] ) ? $payload['meta'] : array();
        $book_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT book_key) FROM {$v} WHERE translation_key=%s", $job['temporary_translation_key'] ) );
        $verse_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE translation_key=%s AND verse_status='present'", $job['temporary_translation_key'] ) );
        $min_verses = max( 1, absint( apply_filters( 'vh360_studio_bible_min_present_verses', self::MIN_VERSES, $job ) ) );
        if ( $book_count < 66 || $verse_count < $min_verses ) { return $this->fail_job( $job, __( 'Import did not contain a complete 66-book translation.', 'videohub360-studio' ), true ); }
        if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return $this->fail_job( $job, __( 'Could not start database transaction.', 'videohub360-studio' ), false ); }
        $old_key = $job['translation_key']; $backup_key = '__vh360_replace_' . absint( $id );
        $had_old = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE translation_key=%s", $old_key ) );
        if ( $had_old && false === $wpdb->update( $v, array( 'translation_key' => $backup_key ), array( 'translation_key' => $old_key ) ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not prepare existing translation for replacement.', 'videohub360-studio' ), false ); }
        if ( false === $wpdb->update( $v, array( 'translation_key' => $old_key ), array( 'translation_key' => $job['temporary_translation_key'] ) ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not promote imported verses.', 'videohub360-studio' ), false ); }
        $meta = array_merge( $meta, array( 'status' => 'installed', 'source_hash' => $job['source_hash'], 'verse_count' => $verse_count, 'book_count' => $book_count, 'imported_at' => $now, 'created_at' => $now, 'updated_at' => $now ) );
        if ( false === $wpdb->replace( $t, $meta ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not save translation metadata.', 'videohub360-studio' ), false ); }
        if ( false === $wpdb->update( $jobs, array( 'status' => 'completed', 'completed_at' => $now, 'updated_at' => $now, 'book_count' => $book_count ), array( 'id' => $id ) ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not complete import job.', 'videohub360-studio' ), false ); }
        if ( false === $wpdb->delete( $v, array( 'translation_key' => $backup_key ) ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not clean up replaced translation rows.', 'videohub360-studio' ), false ); } if ( false === $wpdb->query( 'COMMIT' ) ) { $wpdb->query( 'ROLLBACK' ); return $this->fail_job( $job, __( 'Could not commit Bible import transaction.', 'videohub360-studio' ), false ); }
        if ( file_exists( $job['source_file'] ) ) { wp_delete_file( $job['source_file'] ); }
        VH360_Studio_Bible_Repository::clear_translation_cache( $old_key ); return $this->get_job( $id );
    }

    private function fail_job( $job, $message, $cleanup ) { $this->append_errors( $job, array( $message ) ); if ( $cleanup ) { $cleanup_result = $this->cleanup_staging( $job, true ); if ( is_wp_error( $cleanup_result ) ) { error_log( $cleanup_result->get_error_message() ); } } global $wpdb; $updated = $wpdb->update( VH360_Studio_Database::bible_import_jobs_table_name(), array( 'status' => 'failed', 'error_message' => $this->with_error( $job, $message ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $job['id'] ) ) ); if ( false === $updated ) { error_log( 'VH360 Bible import failure status could not be saved for job ' . absint( $job['id'] ) ); } return $this->get_job( $job['id'] ); }
    private function cleanup_staging( $job, $delete_file ) { global $wpdb; $deleted = $wpdb->delete( VH360_Studio_Database::bible_verses_table_name(), array( 'translation_key' => $job['temporary_translation_key'] ) ); if ( false === $deleted ) { return new WP_Error( 'vh360_bible_import_cleanup_failed', __( 'Import staging data could not be cleaned.', 'videohub360-studio' ), array( 'status' => 500 ) ); } if ( $delete_file && ! empty( $job['source_file'] ) && file_exists( $job['source_file'] ) ) { wp_delete_file( $job['source_file'] ); } if ( $delete_file && ! empty( $job['source_file'] ) && file_exists( $job['source_file'] ) ) { return new WP_Error( 'vh360_bible_import_cleanup_failed', __( 'Import source file could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) ); } return true; }
    private function append_errors( $job, $errors ) { if ( empty( $errors ) ) { return true; } global $wpdb; $updated = $wpdb->update( VH360_Studio_Database::bible_import_jobs_table_name(), array( 'error_message' => $this->with_error( $job, $errors ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $job['id'] ) ) ); if ( false === $updated ) { error_log( 'VH360 Bible import errors could not be saved for job ' . absint( $job['id'] ) ); return false; } return true; }
    private function with_error( $job, $errors ) { $payload = json_decode( $job['error_message'], true ); if ( ! is_array( $payload ) ) { $payload = array( 'meta' => array(), 'errors' => array() ); } $payload['errors'] = array_slice( array_merge( isset( $payload['errors'] ) ? (array) $payload['errors'] : array(), (array) $errors ), -100 ); return wp_json_encode( $payload ); }
}
