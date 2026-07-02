<?php
/**
 * Recording chunk storage service.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Recording_Chunks {
    const STATUS_RECEIVED = 'received';

    private $jobs;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs = $jobs;
    }

    public function upload_settings() {
        return array(
            'max_chunk_size'           => (int) apply_filters( 'vh360_studio_max_chunk_size', 8 * 1024 * 1024 ),
            'max_total_recording_size' => (int) apply_filters( 'vh360_studio_max_total_recording_size', 512 * 1024 * 1024 ),
            'preferred_chunk_duration' => (int) apply_filters( 'vh360_studio_chunk_time_slice', 5000 ),
            'allowed_mime_types'       => (array) apply_filters( 'vh360_studio_allowed_recording_mime_types', array( 'video/webm', 'video/mp4' ) ),
            'allowed_extensions'       => (array) apply_filters( 'vh360_studio_allowed_recording_extensions', array( 'webm', 'mp4' ) ),
        );
    }

    public function base_mime_type( $mime_type ) {
        $parts = explode( ';', (string) $mime_type );
        return strtolower( sanitize_mime_type( trim( $parts[0] ) ) );
    }

    public function is_allowed_mime_type( $mime_type ) {
        return in_array( $this->base_mime_type( $mime_type ), $this->upload_settings()['allowed_mime_types'], true );
    }

    public function validate_job_ownership( $job_id, $user_id ) {
        $job = $this->jobs->get( $job_id, $user_id );
        if ( ! $job ) {
            return new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        return $job;
    }

    public function received_summary( $job_id, $browser_session_id = '' ) {
        global $wpdb;
        $args = array( absint( $job_id ) );
        $sql  = 'SELECT chunk_index, chunk_size FROM ' . VH360_Studio_Database::chunks_table_name() . ' WHERE job_id = %d AND status = %s';
        $args[] = self::STATUS_RECEIVED;
        if ( '' !== $browser_session_id ) {
            $sql .= ' AND browser_session_id = %s';
            $args[] = sanitize_text_field( $browser_session_id );
        }
        $sql .= ' ORDER BY chunk_index ASC';
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
        $indexes = array();
        $bytes = 0;
        foreach ( $rows as $row ) {
            $indexes[] = absint( $row['chunk_index'] );
            $bytes += absint( $row['chunk_size'] );
        }
        return array( 'received_chunk_indexes' => $indexes, 'received_bytes' => $bytes, 'chunk_count' => count( $indexes ) );
    }

    public function store_uploaded_chunk( $job, $browser_session_id, $chunk_index, $file, $declared_mime_type ) {
        $settings = $this->upload_settings();
        $browser_session_id = sanitize_text_field( $browser_session_id );
        $chunk_index = absint( $chunk_index );
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'vh360_studio_missing_chunk', __( 'Missing uploaded recording chunk.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $size = filesize( $file['tmp_name'] );
        if ( false === $size || 0 >= $size || $size > $settings['max_chunk_size'] ) {
            return new WP_Error( 'vh360_studio_chunk_too_large', __( 'Recording chunk size is not allowed.', 'videohub360-studio' ), array( 'status' => 413 ) );
        }
        $base_mime = $this->base_mime_type( $declared_mime_type ? $declared_mime_type : ( isset( $file['type'] ) ? $file['type'] : '' ) );
        if ( ! in_array( $base_mime, $settings['allowed_mime_types'], true ) ) {
            return new WP_Error( 'vh360_studio_invalid_chunk_type', __( 'Recording chunk type is not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $summary = $this->received_summary( $job['id'], $browser_session_id );
        $existing_chunk_size = $this->get_existing_chunk_size( $job['id'], $browser_session_id, $chunk_index );
        if ( max( 0, $summary['received_bytes'] - $existing_chunk_size ) + $size > $settings['max_total_recording_size'] ) {
            return new WP_Error( 'vh360_studio_recording_too_large', __( 'Recording exceeds the allowed size.', 'videohub360-studio' ), array( 'status' => 413 ) );
        }
        $dir = $this->chunk_directory( $job['id'], $browser_session_id );
        if ( is_wp_error( $dir ) ) { return $dir; }
        $ext = 'video/mp4' === $base_mime ? 'mp4' : 'webm';
        $path = trailingslashit( $dir ) . 'chunk-' . $chunk_index . '.' . $ext;
        if ( ! @move_uploaded_file( $file['tmp_name'], $path ) ) {
            return new WP_Error( 'vh360_studio_chunk_store_failed', __( 'Unable to store recording chunk.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        $type_check = $this->validate_stored_file_type( $path, $base_mime );
        if ( is_wp_error( $type_check ) ) {
            @unlink( $path );
            return $type_check;
        }

        $checksum = hash_file( 'sha256', $path );
        $this->upsert_chunk( $job, $browser_session_id, $chunk_index, $size, $base_mime, $path, $checksum );
        return $this->received_summary( $job['id'], $browser_session_id );
    }

    public function assemble_chunks( $job, $browser_session_id, $expected_chunks, $mime_type ) {
        $summary = $this->received_summary( $job['id'], $browser_session_id );
        $expected_chunks = absint( $expected_chunks );
        if ( 1 > $expected_chunks || $summary['chunk_count'] !== $expected_chunks ) {
            return new WP_Error( 'vh360_studio_missing_chunks', __( 'Not all recording chunks have been received.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        for ( $i = 0; $i < $expected_chunks; $i++ ) {
            if ( ! in_array( $i, $summary['received_chunk_indexes'], true ) ) {
                return new WP_Error( 'vh360_studio_missing_chunks', __( 'Recording chunks are incomplete.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }
        }
        $base_mime = $this->base_mime_type( $mime_type );
        if ( ! $this->is_allowed_mime_type( $base_mime ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_type', __( 'Recording MIME type is not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $ext = 'video/mp4' === $base_mime ? 'mp4' : 'webm';
        $dir = $this->chunk_directory( $job['id'], $browser_session_id );
        if ( is_wp_error( $dir ) ) { return $dir; }
        $assembled = trailingslashit( dirname( $dir ) ) . 'recording-' . absint( $job['id'] ) . '-' . sanitize_file_name( $browser_session_id ) . '.' . $ext;
        $out = @fopen( $assembled, 'wb' );
        if ( ! $out ) { return new WP_Error( 'vh360_studio_assembly_failed', __( 'Unable to assemble recording.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        for ( $i = 0; $i < $expected_chunks; $i++ ) {
            $path = $this->get_chunk_path( $job['id'], $browser_session_id, $i );
            if ( ! $path || ! file_exists( $path ) ) { fclose( $out ); return new WP_Error( 'vh360_studio_missing_chunks', __( 'Recording chunks are incomplete.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
            $in = fopen( $path, 'rb' );
            stream_copy_to_stream( $in, $out );
            fclose( $in );
        }
        fclose( $out );
        return array( 'path' => $assembled, 'file_size' => filesize( $assembled ), 'mime_type' => $base_mime );
    }

    public function delete_job_chunks( $job_id, $browser_session_id = '' ) {
        global $wpdb;
        $job_dir = $this->base_directory() . '/' . absint( $job_id );
        if ( is_dir( $job_dir ) ) { $this->delete_directory( $job_dir ); }
        $wpdb->delete( VH360_Studio_Database::chunks_table_name(), array( 'job_id' => absint( $job_id ) ) );
    }

    private function get_existing_chunk_size( $job_id, $session, $index ) {
        global $wpdb;
        return absint( $wpdb->get_var( $wpdb->prepare( 'SELECT chunk_size FROM ' . VH360_Studio_Database::chunks_table_name() . ' WHERE job_id = %d AND browser_session_id = %s AND chunk_index = %d AND status = %s', absint( $job_id ), sanitize_text_field( $session ), absint( $index ), self::STATUS_RECEIVED ) ) );
    }

    private function validate_stored_file_type( $path, $expected_mime ) {
        $extension = 'video/mp4' === $expected_mime ? 'mp4' : 'webm';
        $filename  = basename( $path );
        $check     = wp_check_filetype_and_ext( $path, $filename, array( 'webm' => 'video/webm', 'mp4' => 'video/mp4' ) );

        if ( ! empty( $check['ext'] ) && $extension !== $check['ext'] ) {
            return new WP_Error( 'vh360_studio_invalid_chunk_type', __( 'Recording chunk extension does not match the selected recording type.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        if ( ! empty( $check['type'] ) && $expected_mime !== $this->base_mime_type( $check['type'] ) ) {
            return new WP_Error( 'vh360_studio_invalid_chunk_type', __( 'Recording chunk MIME type does not match the selected recording type.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo ) {
                $detected = $this->base_mime_type( finfo_file( $finfo, $path ) );
                finfo_close( $finfo );
                if ( $detected && 'application/octet-stream' !== $detected && $expected_mime !== $detected ) {
                    return new WP_Error( 'vh360_studio_invalid_chunk_type', __( 'Recording chunk contents do not match the selected recording type.', 'videohub360-studio' ), array( 'status' => 415 ) );
                }
            }
        }

        return true;
    }

    private function upsert_chunk( $job, $session, $index, $size, $mime, $path, $checksum ) {
        global $wpdb;
        $now = current_time( 'mysql' );
        $wpdb->replace( VH360_Studio_Database::chunks_table_name(), array( 'job_id'=>absint($job['id']), 'user_id'=>absint($job['user_id']), 'browser_session_id'=>$session, 'chunk_index'=>$index, 'chunk_size'=>$size, 'mime_type'=>$mime, 'file_path'=>$path, 'checksum'=>$checksum, 'status'=>self::STATUS_RECEIVED, 'created_at'=>$now, 'updated_at'=>$now ) );
    }

    private function get_chunk_path( $job_id, $session, $index ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( 'SELECT file_path FROM ' . VH360_Studio_Database::chunks_table_name() . ' WHERE job_id = %d AND browser_session_id = %s AND chunk_index = %d AND status = %s', absint( $job_id ), sanitize_text_field( $session ), absint( $index ), self::STATUS_RECEIVED ) );
    }

    private function base_directory() {
        $uploads = wp_upload_dir();
        return trailingslashit( $uploads['basedir'] ) . 'vh360-studio-recordings/tmp';
    }

    private function chunk_directory( $job_id, $session ) {
        $dir = $this->base_directory() . '/' . absint( $job_id ) . '/' . sanitize_file_name( $session );
        if ( ! wp_mkdir_p( $dir ) ) { return new WP_Error( 'vh360_studio_storage_unavailable', __( 'Recording storage is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        foreach ( array( $this->base_directory(), dirname( $dir ), $dir ) as $hardening_dir ) {
            if ( is_dir( $hardening_dir ) ) {
                if ( ! file_exists( $hardening_dir . '/index.php' ) ) { file_put_contents( $hardening_dir . '/index.php', "<?php\n// Silence is golden.\n" ); }
                if ( ! file_exists( $hardening_dir . '/.htaccess' ) ) { file_put_contents( $hardening_dir . '/.htaccess', "Require all denied\nDeny from all\n" ); }
            }
        }
        return $dir;
    }

    private function delete_directory( $dir ) {
        foreach ( glob( trailingslashit( $dir ) . '*' ) as $file ) { is_dir( $file ) ? $this->delete_directory( $file ) : @unlink( $file ); }
        @rmdir( $dir );
    }
}
