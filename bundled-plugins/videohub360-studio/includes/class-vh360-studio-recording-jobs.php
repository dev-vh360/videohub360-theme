<?php
/**
 * Recording job service.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Recording_Jobs {
    const STATUS_CREATED    = 'created';
    const STATUS_RECORDING  = 'recording';
    const STATUS_STOPPING   = 'stopping';
    const STATUS_UPLOADING  = 'uploading';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY      = 'ready';
    const STATUS_FAILED     = 'failed';
    const STATUS_CANCELLED  = 'cancelled';

    private $registry;

    public function __construct( VH360_Studio_Provider_Registry $registry ) {
        $this->registry = $registry;
    }

    public function allowed_statuses() {
        return array(
            self::STATUS_CREATED,
            self::STATUS_RECORDING,
            self::STATUS_STOPPING,
            self::STATUS_UPLOADING,
            self::STATUS_PROCESSING,
            self::STATUS_READY,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        );
    }

    public function transition_map() {
        return array(
            self::STATUS_CREATED    => array( self::STATUS_RECORDING, self::STATUS_CANCELLED ),
            self::STATUS_RECORDING  => array( self::STATUS_STOPPING, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_STOPPING   => array( self::STATUS_UPLOADING, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_UPLOADING  => array( self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_PROCESSING => array( self::STATUS_READY, self::STATUS_FAILED ),
            self::STATUS_READY      => array(),
            self::STATUS_FAILED     => array(),
            self::STATUS_CANCELLED  => array(),
        );
    }

    public function can_transition( $from_status, $to_status ) {
        if ( $from_status === $to_status ) {
            return true;
        }

        $map = $this->transition_map();
        return isset( $map[ $from_status ] ) && in_array( $to_status, $map[ $from_status ], true );
    }

    public function validate_payload( $data, $partial = false, $existing = null ) {
        $out = array();

        foreach ( array( 'source_type', 'recording_mode', 'quality_preset', 'storage_provider', 'status' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_key( wp_unslash( $data[ $key ] ) );
            }
        }

        foreach ( array( 'source_id', 'room_id', 'browser_session_id', 'videopress_guid', 'publitio_file_id', 'local_temp_path' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_text_field( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $data['mime_type'] ) ) {
            $out['mime_type'] = sanitize_mime_type( wp_unslash( $data['mime_type'] ) );
        }

        foreach ( array( 'playback_url', 'poster_url' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = esc_url_raw( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $data['error_message'] ) ) {
            $out['error_message'] = sanitize_textarea_field( wp_unslash( $data['error_message'] ) );
        }

        foreach ( array( 'live_video_id', 'duration_seconds', 'file_size', 'wp_attachment_id', 'retry_count' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = absint( $data[ $key ] );
            }
        }

        if ( isset( $data['videopress_processing_done'] ) ) {
            $out['videopress_processing_done'] = rest_sanitize_boolean( $data['videopress_processing_done'] ) ? 1 : 0;
        }

        foreach ( array( 'started_at', 'stopped_at', 'completed_at' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_text_field( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $out['status'] ) && ! in_array( $out['status'], $this->allowed_statuses(), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_status', __( 'Invalid recording job status.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( ! $partial && isset( $out['status'] ) && self::STATUS_CREATED !== $out['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_initial_status', __( 'New recording jobs must start in the created status.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( $existing && isset( $out['status'] ) && ! $this->can_transition( $existing['status'], $out['status'] ) ) {
            return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Invalid recording job status transition.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        if ( isset( $out['quality_preset'] ) && ! VH360_Studio_Quality_Presets::exists( $out['quality_preset'] ) ) {
            return new WP_Error( 'vh360_studio_invalid_quality_preset', __( 'Invalid quality preset.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( isset( $out['storage_provider'] ) && ! $this->registry->has_storage_provider( $out['storage_provider'] ) ) {
            return new WP_Error( 'vh360_studio_invalid_storage_provider', __( 'Invalid storage provider.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( ! $partial ) {
            $out = wp_parse_args(
                $out,
                array(
                    'source_type'     => 'live_room',
                    'source_id'       => '',
                    'room_id'         => '',
                    'recording_mode'  => 'browser',
                    'quality_preset'  => VH360_Studio_Quality_Presets::DEFAULT_PRESET,
                    'storage_provider' => 'videopress',
                    'status'          => self::STATUS_CREATED,
                )
            );
        }

        return $out;
    }

    public function create( $user_id, $data ) {
        global $wpdb;

        $data = $this->validate_payload( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $now = current_time( 'mysql' );
        $row = wp_parse_args(
            $data,
            array(
                'user_id'    => absint( $user_id ),
                'created_at' => $now,
                'updated_at' => $now,
            )
        );

        $wpdb->insert( VH360_Studio_Database::table_name(), $row );

        return $wpdb->insert_id ? $this->get( $wpdb->insert_id, $user_id ) : new WP_Error( 'vh360_studio_create_failed', __( 'Unable to create recording job.', 'videohub360-studio' ), array( 'status' => 500 ) );
    }

    public function get( $id, $user_id = 0 ) {
        global $wpdb;

        $sql  = 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' WHERE id = %d';
        $args = array( absint( $id ) );

        if ( $user_id && ! VH360_Studio_Permissions::current_user_can_manage_all_jobs() ) {
            $sql   .= ' AND user_id = %d';
            $args[] = absint( $user_id );
        }

        return $wpdb->get_row( $wpdb->prepare( $sql, $args ), ARRAY_A );
    }

    public function list( $user_id, $limit = 20 ) {
        global $wpdb;

        $limit = min( 100, max( 1, absint( $limit ) ) );

        if ( VH360_Studio_Permissions::current_user_can_manage_all_jobs() ) {
            return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' ORDER BY created_at DESC LIMIT %d', $limit ), ARRAY_A );
        }

        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT %d', absint( $user_id ), $limit ), ARRAY_A );
    }

    public function update( $id, $user_id, $data ) {
        global $wpdb;

        $existing = $this->get( $id, $user_id );
        if ( ! $existing ) {
            return new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        $data = $this->validate_payload( $data, true, $existing );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->update( VH360_Studio_Database::table_name(), $data, array( 'id' => absint( $id ) ) );

        return $this->get( $id, $user_id );
    }

    public function cancel( $id, $user_id ) {
        return $this->update( $id, $user_id, array( 'status' => self::STATUS_CANCELLED ) );
    }
}
