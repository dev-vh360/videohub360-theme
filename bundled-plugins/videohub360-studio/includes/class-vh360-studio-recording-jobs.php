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
    const STATUS_PREPARING_DOWNLOAD = 'preparing_download';

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
            self::STATUS_PREPARING_DOWNLOAD,
        );
    }

    public function allowed_source_types() {
        return array( 'studio_setup', 'live_room', 'livestream_video', 'appointment_session' );
    }

    public function allowed_recording_modes() {
        return array( 'browser', 'local_private' );
    }

    public function transition_map() {
        return array(
            self::STATUS_CREATED    => array( self::STATUS_RECORDING, self::STATUS_CANCELLED ),
            self::STATUS_RECORDING  => array( self::STATUS_STOPPING, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_STOPPING   => array( self::STATUS_UPLOADING, self::STATUS_PREPARING_DOWNLOAD, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_UPLOADING  => array( self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_CANCELLED ),
            self::STATUS_PROCESSING => array( self::STATUS_READY, self::STATUS_FAILED ),
            self::STATUS_READY      => array(),
            self::STATUS_PREPARING_DOWNLOAD => array( self::STATUS_READY, self::STATUS_FAILED, self::STATUS_CANCELLED ),
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

    public function validate_payload( $data, $partial = false, $existing = null, $allow_invalid_transition = false ) {
        $out = array();

        foreach ( array( 'source_type', 'recording_mode', 'quality_preset', 'storage_provider', 'status', 'publish_provider_status' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_key( wp_unslash( $data[ $key ] ) );
            }
        }

        foreach ( array( 'source_id', 'room_id', 'browser_session_id', 'videopress_guid', 'publitio_file_id', 'provider_file_id', 'local_temp_path' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_text_field( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $data['mime_type'] ) ) {
            $out['mime_type'] = sanitize_mime_type( wp_unslash( $data['mime_type'] ) );
        }

        foreach ( array( 'playback_url', 'poster_url', 'provider_embed_url' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = esc_url_raw( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $data['error_message'] ) ) {
            $out['error_message'] = sanitize_textarea_field( wp_unslash( $data['error_message'] ) );
        }
        if ( isset( $data['provider_metadata'] ) ) {
            $metadata = wp_unslash( $data['provider_metadata'] );
            if ( is_array( $metadata ) ) {
                $out['provider_metadata'] = wp_json_encode( $metadata );
            } else {
                json_decode( (string) $metadata, true );
                $out['provider_metadata'] = JSON_ERROR_NONE === json_last_error() ? (string) $metadata : sanitize_textarea_field( $metadata );
            }
        }

        foreach ( array( 'live_video_id', 'duration_seconds', 'file_size', 'wp_attachment_id', 'retry_count', 'expected_chunks', 'received_chunks', 'replay_video_id' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = absint( $data[ $key ] );
            }
        }

        if ( isset( $data['videopress_processing_done'] ) ) {
            $out['videopress_processing_done'] = rest_sanitize_boolean( $data['videopress_processing_done'] ) ? 1 : 0;
        }

        if ( isset( $data['assembled_checksum'] ) ) {
            $checksum = strtolower( sanitize_text_field( wp_unslash( $data['assembled_checksum'] ) ) );
            if ( preg_match( '/^[a-f0-9]{64}$/', $checksum ) ) {
                $out['assembled_checksum'] = $checksum;
            }
        }

        foreach ( array( 'started_at', 'stopped_at', 'completed_at', 'assembled_at', 'temp_expires_at', 'publish_attempted_at', 'published_at' ) as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $out[ $key ] = sanitize_text_field( wp_unslash( $data[ $key ] ) );
            }
        }

        if ( isset( $out['status'] ) && ! in_array( $out['status'], $this->allowed_statuses(), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_status', __( 'Invalid recording job status.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( isset( $out['source_type'] ) && ! in_array( $out['source_type'], $this->allowed_source_types(), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_source_type', __( 'Invalid Studio source type.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( isset( $out['recording_mode'] ) && ! in_array( $out['recording_mode'], $this->allowed_recording_modes(), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_mode', __( 'Invalid Studio recording mode.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( ! $partial && isset( $out['status'] ) && self::STATUS_CREATED !== $out['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_initial_status', __( 'New recording jobs must start in the created status.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( $existing && isset( $out['status'] ) && ! $allow_invalid_transition && ! $this->can_transition( $existing['status'], $out['status'] ) ) {
            return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Invalid recording job status transition.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        if ( $existing ) {
            foreach ( array( 'source_type', 'source_id', 'live_video_id', 'room_id', 'recording_mode', 'quality_preset', 'storage_provider', 'user_id' ) as $immutable_key ) {
                if ( array_key_exists( $immutable_key, $out ) && array_key_exists( $immutable_key, $existing ) && (string) $out[ $immutable_key ] !== (string) $existing[ $immutable_key ] ) {
                    return new WP_Error( 'vh360_studio_immutable_recording_identity', __( 'Recording identity fields cannot be changed after creation.', 'videohub360-studio' ), array( 'status' => 400 ) );
                }
            }
        }

        if ( isset( $out['quality_preset'] ) ) {
            $out['quality_preset'] = VH360_Studio_Quality_Presets::normalize( $out['quality_preset'] );
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



    public function active_statuses() {
        return array( self::STATUS_CREATED, self::STATUS_RECORDING, self::STATUS_STOPPING, self::STATUS_UPLOADING, self::STATUS_PROCESSING );
    }

    public function find_active_live_room_job( $post_id ) {
        return $this->find_active_room_recording( $post_id, 'live_room', $this->active_statuses() );
    }

    public function find_active_appointment_recording( $post_id ) {
        return $this->find_active_room_recording( $post_id, 'appointment_session', array( self::STATUS_CREATED, self::STATUS_RECORDING, self::STATUS_STOPPING, self::STATUS_PREPARING_DOWNLOAD ) );
    }

    private function find_active_room_recording( $post_id, $source_type, array $statuses ) {
        global $wpdb;
        $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
        $args = array_merge( array( sanitize_key( $source_type ), absint( $post_id ) ), array_map( 'sanitize_key', $statuses ) );
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . " WHERE source_type = %s AND live_video_id = %d AND status IN ($placeholders) ORDER BY created_at DESC LIMIT 1", $args ), ARRAY_A );
    }

    public function create( $user_id, $data ) {
        global $wpdb;

        $data['status'] = self::STATUS_CREATED;
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



    public function mark_preparing_download( $id, $user_id, $duration_seconds = 0, $mime_type = '' ) {
        $data = array(
            'status'           => self::STATUS_PREPARING_DOWNLOAD,
            'stopped_at'       => current_time( 'mysql' ),
            'duration_seconds' => absint( $duration_seconds ),
        );
        if ( $mime_type ) {
            $data['mime_type'] = sanitize_mime_type( $mime_type );
        }
        return $this->update( $id, $user_id, $data );
    }

    public function mark_local_private_ready( $id, $user_id, $duration_seconds = 0, $mime_type = '' ) {
        $data = array(
            'status'           => self::STATUS_READY,
            'completed_at'     => current_time( 'mysql' ),
            'duration_seconds' => absint( $duration_seconds ),
        );
        if ( $mime_type ) {
            $data['mime_type'] = sanitize_mime_type( $mime_type );
        }
        return $this->update( $id, $user_id, $data );
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

    private function update_record( $id, $user_id, $data, $allow_invalid_transition = false ) {
        global $wpdb;

        $existing = $this->get( $id, $user_id );
        if ( ! $existing ) {
            return new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        $data = $this->validate_payload( $data, true, $existing, $allow_invalid_transition );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->update( VH360_Studio_Database::table_name(), $data, array( 'id' => absint( $id ) ) );

        return $this->get( $id, $user_id );
    }

    public function update( $id, $user_id, $data ) {
        return $this->update_record( $id, $user_id, $data );
    }

    public function start_recording( $id, $user_id, $browser_session_id, $mime_type ) {
        return $this->update( $id, $user_id, array(
            'status'             => self::STATUS_RECORDING,
            'browser_session_id' => sanitize_text_field( $browser_session_id ),
            'mime_type'          => sanitize_mime_type( $mime_type ),
            'started_at'         => current_time( 'mysql' ),
            'error_message'      => '',
        ) );
    }


    public function list_pending_provider_replay_jobs( $limit = 10, $hours = 24 ) {
        global $wpdb;

        $limit = min( 25, max( 1, absint( $limit ) ) );
        $hours = min( 72, max( 1, absint( $hours ) ) );
        $since = gmdate( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );
        $table = VH360_Studio_Database::table_name();
        $postmeta = $wpdb->postmeta;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT j.* FROM {$table} j
                INNER JOIN {$postmeta} pending ON pending.post_id = j.live_video_id AND pending.meta_key = '_vh360_studio_replay_pending' AND pending.meta_value = 'yes'
                LEFT JOIN {$postmeta} ready ON ready.post_id = j.live_video_id AND ready.meta_key = '_vh360_studio_replay_ready'
                LEFT JOIN {$postmeta} failed ON failed.post_id = j.live_video_id AND failed.meta_key = '_vh360_studio_replay_failed'
                WHERE j.status IN (%s, %s)
                    AND j.storage_provider IN ('bunny_stream', 'publitio', 'videopress')
                    AND j.live_video_id > 0
                    AND j.updated_at >= %s
                    AND (ready.meta_value IS NULL OR ready.meta_value != 'yes')
                    AND (failed.meta_value IS NULL OR failed.meta_value != 'yes')
                ORDER BY j.updated_at ASC
                LIMIT %d",
                self::STATUS_PROCESSING,
                self::STATUS_READY,
                $since,
                $limit
            ),
            ARRAY_A
        );
    }

    public function mark_stopping( $id, $user_id, $duration_seconds = null ) {
        $data = array( 'status' => self::STATUS_STOPPING, 'stopped_at' => current_time( 'mysql' ) );
        if ( null !== $duration_seconds ) { $data['duration_seconds'] = absint( $duration_seconds ); }
        return $this->update( $id, $user_id, $data );
    }

    public function mark_uploading( $id, $user_id ) {
        return $this->update( $id, $user_id, array( 'status' => self::STATUS_UPLOADING ) );
    }

    public function mark_finalize_retryable( $id, $user_id, $message = '' ) {
        return $this->update_record(
            $id,
            $user_id,
            array(
                'status'        => self::STATUS_STOPPING,
                'error_message' => sanitize_textarea_field( $message ),
            ),
            true
        );
    }

    public function mark_processing( $id, $user_id, $data = array() ) {
        $data['status'] = self::STATUS_PROCESSING;
        return $this->update( $id, $user_id, $data );
    }

    public function mark_ready( $id, $user_id, $data = array() ) {
        $data['status']       = self::STATUS_READY;
        $data['published_at'] = isset( $data['published_at'] ) ? $data['published_at'] : current_time( 'mysql' );
        $data['completed_at'] = isset( $data['completed_at'] ) ? $data['completed_at'] : current_time( 'mysql' );
        return $this->update( $id, $user_id, $data );
    }

    public function mark_failed( $id, $user_id, $message ) {
        return $this->update( $id, $user_id, array( 'status' => self::STATUS_FAILED, 'error_message' => sanitize_textarea_field( $message ) ) );
    }

    public function cancel( $id, $user_id ) {
        $job = $this->update( $id, $user_id, array( 'status' => self::STATUS_CANCELLED ) );
        if ( ! is_wp_error( $job ) && class_exists( 'VH360_Studio_Recording_Chunks' ) ) {
            ( new VH360_Studio_Recording_Chunks( $this ) )->delete_job_chunks( $id );
        }
        return $job;
    }
}
