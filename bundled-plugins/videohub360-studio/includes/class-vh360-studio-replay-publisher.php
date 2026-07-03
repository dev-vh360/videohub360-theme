<?php
/**
 * Provider-neutral replay publishing bridge.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Replay_Publisher {
    private $registry;
    private $jobs;
    private $validator;
    private $chunks;
    private $replay_posts;

    public function __construct( VH360_Studio_Provider_Registry $registry, VH360_Studio_Recording_Jobs $jobs, VH360_Studio_Recording_Validator $validator, VH360_Studio_Recording_Chunks $chunks, VH360_Studio_Replay_Posts $replay_posts = null ) {
        $this->registry     = $registry;
        $this->jobs         = $jobs;
        $this->validator    = $validator;
        $this->chunks       = $chunks;
        $this->replay_posts = $replay_posts ? $replay_posts : new VH360_Studio_Replay_Posts();
    }

    public function prepare( array $job ) {
        $provider = $this->get_provider( $job );
        if ( is_wp_error( $provider ) ) {
            return $provider;
        }

        $recording = $this->build_recording_payload( $job );
        if ( is_wp_error( $recording ) ) {
            return $recording;
        }

        $prepared = $provider->prepare_publish( $job, $recording );
        $status   = is_wp_error( $prepared ) ? 'not_implemented' : 'prepared';
        $this->jobs->update( $job['id'], 0, array( 'publish_attempted_at' => current_time( 'mysql' ), 'publish_provider_status' => $status ) );

        return array(
            'provider_id'             => $provider->get_id(),
            'provider_label'          => $provider->get_label(),
            'job_status'              => $job['status'],
            'file_size'               => $recording['file_size'],
            'mime_type'               => $recording['mime_type'],
            'assembled_checksum'      => $recording['assembled_checksum'],
            'publish_provider_status' => $status,
            'message'                 => is_wp_error( $prepared ) ? $prepared->get_error_message() : __( 'Replay publishing is prepared.', 'videohub360-studio' ),
        );
    }

    public function publish( array $job ) {
        $provider = $this->get_provider( $job );
        if ( is_wp_error( $provider ) ) {
            return $provider;
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_publish_status', __( 'Replay publishing requires a processing job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        $recording = $this->build_recording_payload( $job );
        if ( is_wp_error( $recording ) ) {
            return $recording;
        }

        $published = $provider->publish_recording( $job, $recording );
        if ( is_wp_error( $published ) ) {
            $status = 'publish_failed';
            $data = array( 'publish_attempted_at' => current_time( 'mysql' ), 'publish_provider_status' => $status );
            $error_data = $published->get_error_data();
            if ( is_array( $error_data ) && ! empty( $error_data['attachment_id'] ) ) {
                $data['wp_attachment_id'] = absint( $error_data['attachment_id'] );
            }
            $this->jobs->update( $job['id'], 0, $data );
            return $published;
        }

        $replay = $this->replay_posts->create_or_update( $job, $published, $recording );
        if ( is_wp_error( $replay ) ) {
            $this->jobs->update( $job['id'], 0, array( 'publish_attempted_at' => current_time( 'mysql' ), 'publish_provider_status' => 'replay_post_failed' ) );
            return $replay;
        }

        $ready = $this->jobs->mark_ready( $job['id'], 0, array(
            'wp_attachment_id'        => absint( $published['attachment_id'] ),
            'videopress_guid'         => sanitize_text_field( $published['videopress_guid'] ),
            'playback_url'            => esc_url_raw( $published['playback_url'] ),
            'replay_video_id'         => absint( $replay['replay_video_id'] ),
            'publish_attempted_at'    => current_time( 'mysql' ),
            'publish_provider_status' => 'published',
            'error_message'           => '',
        ) );
        if ( is_wp_error( $ready ) ) {
            return $ready;
        }

        return array(
            'provider_id'             => $provider->get_id(),
            'provider_label'          => $provider->get_label(),
            'job_status'              => $ready['status'],
            'file_size'               => absint( $ready['file_size'] ),
            'mime_type'               => $ready['mime_type'],
            'assembled_checksum'      => $ready['assembled_checksum'],
            'publish_provider_status' => $ready['publish_provider_status'],
            'attachment_id'           => absint( $ready['wp_attachment_id'] ),
            'videopress_guid'         => $ready['videopress_guid'],
            'playback_url'            => $ready['playback_url'],
            'replay_video_id'         => absint( $ready['replay_video_id'] ),
            'replay_url'              => $replay['replay_url'],
            'message'                 => __( 'Replay published and VideoHub360 replay post created.', 'videohub360-studio' ),
        );
    }

    public function status( array $job ) {
        $provider = $this->get_provider( $job, false );
        if ( is_wp_error( $provider ) ) {
            return $provider;
        }
        $status = $provider->get_publish_status( $job );
        $status['job_status'] = $job['status'];
        $status['publish_provider_status'] = ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : '';
        $status['replay_url'] = ! empty( $job['replay_video_id'] ) ? get_permalink( absint( $job['replay_video_id'] ) ) : '';
        return $status;
    }

    private function get_provider( array $job, $require_processing = true ) {
        if ( $require_processing && VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_publish_status', __( 'Publishing can only be prepared for processing jobs.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $provider = $this->registry->get_storage_provider( sanitize_key( $job['storage_provider'] ) );
        if ( ! $provider ) {
            return new WP_Error( 'vh360_studio_invalid_storage_provider', __( 'Invalid storage provider.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $provider;
    }

    private function build_recording_payload( array $job ) {
        $recording = array(
            'path'               => isset( $job['local_temp_path'] ) ? $job['local_temp_path'] : '',
            'file_size'          => absint( $job['file_size'] ),
            'mime_type'          => isset( $job['mime_type'] ) ? sanitize_mime_type( $job['mime_type'] ) : '',
            'assembled_checksum' => isset( $job['assembled_checksum'] ) ? sanitize_text_field( $job['assembled_checksum'] ) : '',
            'duration_seconds'   => absint( $job['duration_seconds'] ),
            'source_type'        => sanitize_key( $job['source_type'] ),
            'source_id'          => sanitize_text_field( $job['source_id'] ),
            'live_video_id'      => absint( $job['live_video_id'] ),
            'room_id'            => sanitize_text_field( $job['room_id'] ),
        );

        if ( empty( $recording['path'] ) || ! $this->chunks->is_path_in_base_directory( $recording['path'] ) || ! file_exists( $recording['path'] ) || ! is_file( $recording['path'] ) || ! is_readable( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_recording_missing', __( 'Validated temporary recording is no longer available.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        if ( absint( filesize( $recording['path'] ) ) !== $recording['file_size'] ) {
            return new WP_Error( 'vh360_studio_recording_size_changed', __( 'Validated temporary recording size no longer matches.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! empty( $recording['assembled_checksum'] ) && hash_file( 'sha256', $recording['path'] ) !== $recording['assembled_checksum'] ) {
            return new WP_Error( 'vh360_studio_recording_integrity_failed', __( 'Validated temporary recording checksum no longer matches.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        return $recording;
    }
}
