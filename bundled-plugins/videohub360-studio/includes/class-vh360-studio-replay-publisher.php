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

        $target_validation = $this->validate_replay_target_for_publish( $job );
        if ( is_wp_error( $target_validation ) ) {
            return $target_validation;
        }

        $published = $provider->publish_recording( $job, $recording );
        if ( is_wp_error( $published ) ) {
            $this->jobs->update( $job['id'], 0, array( 'publish_attempted_at' => current_time( 'mysql' ), 'publish_provider_status' => 'publish_failed' ) );
            return $published;
        }

        if ( isset( $published['status'] ) && ! in_array( $published['status'], array( 'published', 'local_media_ready' ), true ) ) {
            $updated = $this->jobs->update( $job['id'], 0, array(
                'wp_attachment_id'        => ! empty( $published['attachment_id'] ) ? absint( $published['attachment_id'] ) : 0,
                'playback_url'            => ! empty( $published['playback_url'] ) ? esc_url_raw( $published['playback_url'] ) : '',
                'publish_attempted_at'    => current_time( 'mysql' ),
                'publish_provider_status' => sanitize_key( $published['status'] ),
                'error_message'           => '',
            ) );
            if ( is_wp_error( $updated ) ) {
                return $updated;
            }

            return array(
                'provider_id'             => $provider->get_id(),
                'provider_label'          => $provider->get_label(),
                'job_status'              => $updated['status'],
                'file_size'               => absint( $updated['file_size'] ),
                'mime_type'               => $updated['mime_type'],
                'assembled_checksum'      => $updated['assembled_checksum'],
                'publish_provider_status' => $updated['publish_provider_status'],
                'attachment_id'           => absint( $updated['wp_attachment_id'] ),
                'playback_url'            => $updated['playback_url'],
                'replay_video_id'         => ! empty( $updated['replay_video_id'] ) ? absint( $updated['replay_video_id'] ) : 0,
                'message'                 => ! empty( $published['message'] ) ? $published['message'] : __( 'Recording attached to media. Waiting for VideoPress processing.', 'videohub360-studio' ),
            );
        }

        return $this->complete_published_job( $job, $published, $recording, $provider );
    }

    public function status( array $job ) {
        $provider = $this->get_provider( $job, false );
        if ( is_wp_error( $provider ) ) {
            return $provider;
        }

        $status = $provider->get_publish_status( $job );
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING === $job['status'] && ! empty( $status['videopress_guid'] ) && ! empty( $status['attachment_id'] ) ) {
            $recording = $this->build_recording_payload_for_status( $job );
            $completed = $this->complete_published_job(
                $job,
                array(
                    'attachment_id'    => absint( $status['attachment_id'] ),
                    'playback_url'      => ! empty( $status['playback_url'] ) ? esc_url_raw( $status['playback_url'] ) : '',
                    'videopress_guid'  => sanitize_text_field( $status['videopress_guid'] ),
                    'poster_url'        => ! empty( $status['poster_url'] ) ? esc_url_raw( $status['poster_url'] ) : '',
                ),
                $recording,
                $provider
            );
            if ( ! is_wp_error( $completed ) ) {
                return $completed;
            }
        }

        $status['job_status'] = $job['status'];
        $status['publish_provider_status'] = ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : '';
        $replay_post_id = $this->resolve_replay_url_post_id( $job );
        $status['replay_url'] = $replay_post_id ? get_permalink( $replay_post_id ) : '';
        return $status;
    }

    private function complete_published_job( array $job, array $published, array $recording, VH360_Studio_Replay_Storage_Provider $provider ) {
        $replay = $this->replay_posts->create_or_update_replay_post( $job, $published, $recording );
        if ( is_wp_error( $replay ) ) {
            $this->jobs->update( $job['id'], 0, array( 'publish_attempted_at' => current_time( 'mysql' ), 'publish_provider_status' => 'replay_post_failed' ) );
            return $replay;
        }

        $ready = $this->jobs->mark_ready( $job['id'], 0, array(
            'wp_attachment_id'        => absint( $published['attachment_id'] ),
            'videopress_guid'         => sanitize_text_field( $published['videopress_guid'] ),
            'videopress_processing_done' => isset( $published['videopress_processing_done'] ) ? absint( $published['videopress_processing_done'] ) : 1,
            'playback_url'            => ! empty( $published['playback_url'] ) ? esc_url_raw( $published['playback_url'] ) : '',
            'poster_url'              => ! empty( $published['poster_url'] ) ? esc_url_raw( $published['poster_url'] ) : '',
            'replay_video_id'         => absint( $replay['replay_video_id'] ),
            'publish_attempted_at'    => current_time( 'mysql' ),
            'publish_provider_status' => ! empty( $published['provider_status'] ) ? sanitize_key( $published['provider_status'] ) : ( ! empty( $published['status'] ) ? sanitize_key( $published['status'] ) : 'published' ),
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
            'message'                 => $this->published_message( $job, $ready, $published ),
        );
    }


    private function resolve_replay_url_post_id( array $job ) {
        $replay_video_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;
        if ( $replay_video_id && 'videohub360' === get_post_type( $replay_video_id ) ) {
            return $replay_video_id;
        }

        $live_video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        if ( $live_video_id && 'videohub360' === get_post_type( $live_video_id ) ) {
            $converted = get_post_meta( $live_video_id, '_vh360_studio_converted_live_to_replay', true );
            $ready     = get_post_meta( $live_video_id, '_vh360_studio_replay_ready', true );
            if ( 'yes' === $converted || 'yes' === $ready || ! empty( $job['playback_url'] ) ) {
                return $live_video_id;
            }
        }

        return 0;
    }

    private function published_message( array $job, array $ready, array $published ) {
        $live_video_id   = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $replay_video_id = ! empty( $ready['replay_video_id'] ) ? absint( $ready['replay_video_id'] ) : 0;

        if ( $live_video_id && $replay_video_id === $live_video_id ) {
            return __( 'Replay published and original livestream post updated.', 'videohub360-studio' );
        }

        return __( 'Replay published and VideoHub360 replay post created.', 'videohub360-studio' );
    }


    private function validate_replay_target_for_publish( array $job ) {
        $live_video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        if ( ! $live_video_id ) {
            return true;
        }

        $post = get_post( $live_video_id );
        if ( ! $post || 'videohub360' !== $post->post_type ) {
            return new WP_Error( 'vh360_studio_invalid_live_replay_target', __( 'The original livestream post is not available.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        $job_user_id = ! empty( $job['user_id'] ) ? absint( $job['user_id'] ) : 0;
        if ( $job_user_id && absint( $post->post_author ) === $job_user_id ) {
            return true;
        }

        if ( current_user_can( 'edit_post', $live_video_id ) ) {
            return true;
        }

        return new WP_Error( 'vh360_studio_live_replay_target_forbidden', __( 'You are not allowed to update the original livestream post.', 'videohub360-studio' ), array( 'status' => 403 ) );
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
        $recording = $this->build_recording_payload_for_status( $job );

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

    private function build_recording_payload_for_status( array $job ) {
        return array(
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
    }
}
