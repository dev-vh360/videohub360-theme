<?php
/**
 * Local Media replay storage provider.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Local_Media_Replay_Storage_Provider implements VH360_Studio_Replay_Storage_Provider {
    const STATUS_READY = 'local_media_ready';

    public function get_id() {
        return 'local_media';
    }

    public function get_label() {
        return __( 'Local Media Fallback', 'videohub360-studio' );
    }

    public function is_available() {
        return (bool) apply_filters( 'vh360_studio_local_media_available', current_user_can( 'upload_files' ) && $this->media_functions_available() && $this->uploads_directory_is_writable(), $this );
    }

    public function supports_publish() {
        return $this->is_available();
    }

    public function prepare_publish( array $job, array $recording ) {
        if ( 'local_media' !== sanitize_key( $job['storage_provider'] ) ) {
            return new WP_Error( 'vh360_studio_local_media_wrong_provider', __( 'This recording job is not configured for Local Media publishing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_local_media_invalid_status', __( 'Local Media publishing requires a processing job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_local_media_upload_forbidden', __( 'Local Media publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        if ( ! $this->uploads_directory_is_writable() ) {
            return new WP_Error( 'vh360_studio_local_media_unavailable', __( 'Local Media publishing is unavailable because the uploads directory is not writable.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        if ( empty( $recording['path'] ) || ! file_exists( $recording['path'] ) || ! is_file( $recording['path'] ) || ! is_readable( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_local_media_missing_file', __( 'The validated recording file is not available for Local Media publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }

        if ( ! in_array( $recording['mime_type'], array( 'video/webm', 'video/mp4' ), true ) ) {
            return new WP_Error( 'vh360_studio_local_media_invalid_type', __( 'Local Media publishing supports WebM and MP4 recordings only.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }

        if ( empty( $recording['file_size'] ) || 0 >= absint( $recording['file_size'] ) ) {
            return new WP_Error( 'vh360_studio_local_media_empty_file', __( 'Local Media publishing requires a non-empty recording file.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $this->load_media_functions();

        return array(
            'provider_id'    => $this->get_id(),
            'provider_label' => $this->get_label(),
            'status'         => 'prepared',
            'message'        => __( 'Local Media is ready to save this replay to the WordPress Media Library.', 'videohub360-studio' ),
        );
    }

    public function publish_recording( array $job, array $recording ) {
        $prepared = $this->prepare_publish( $job, $recording );
        if ( is_wp_error( $prepared ) ) {
            return $prepared;
        }

        $extension = 'video/mp4' === $recording['mime_type'] ? 'mp4' : 'webm';
        $filename  = $this->attachment_filename( $job, $extension );
        $tmp       = wp_tempnam( $filename );
        if ( ! $tmp || ! copy( $recording['path'], $tmp ) ) {
            if ( $tmp ) {
                @unlink( $tmp );
            }
            return new WP_Error( 'vh360_studio_local_media_copy_failed', __( 'Unable to prepare the recording for Media Library handoff.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $file = array(
            'name'     => $filename,
            'type'     => $recording['mime_type'],
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => absint( $recording['file_size'] ),
        );

        $post_id       = $this->resolve_attachment_parent_post_id( $job );
        $attachment_id = media_handle_sideload( $file, $post_id, $this->attachment_title( $job ) );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return $attachment_id;
        }

        $playback_url = wp_get_attachment_url( $attachment_id );
        if ( ! $playback_url ) {
            return new WP_Error( 'vh360_studio_local_media_missing_url', __( 'The Media Library attachment was created but no playback URL is available.', 'videohub360-studio' ), array( 'status' => 500, 'attachment_id' => absint( $attachment_id ) ) );
        }

        return array(
            'provider_id'                => $this->get_id(),
            'provider_label'             => $this->get_label(),
            'status'                     => self::STATUS_READY,
            'provider_status'            => self::STATUS_READY,
            'attachment_id'              => absint( $attachment_id ),
            'wp_attachment_id'           => absint( $attachment_id ),
            'playback_url'               => esc_url_raw( $playback_url ),
            'poster_url'                 => $this->poster_url( $attachment_id ),
            'videopress_guid'            => '',
            'videopress_processing_done' => 0,
            'embed_code'                 => '',
            'message'                    => __( 'Replay saved to the WordPress Media Library.', 'videohub360-studio' ),
        );
    }

    public function get_publish_status( array $job ) {
        $attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        if ( ! $attachment_id ) {
            return array( 'provider_id' => $this->get_id(), 'provider_label' => $this->get_label(), 'status' => 'pending', 'supports_publish' => $this->supports_publish(), 'attachment_id' => 0, 'playback_url' => '', 'message' => __( 'Local Media replay has not been published yet.', 'videohub360-studio' ) );
        }

        if ( 'attachment' !== get_post_type( $attachment_id ) ) {
            return array( 'provider_id' => $this->get_id(), 'provider_label' => $this->get_label(), 'status' => 'missing_attachment', 'supports_publish' => $this->supports_publish(), 'attachment_id' => $attachment_id, 'playback_url' => '', 'message' => __( 'The Local Media attachment no longer exists.', 'videohub360-studio' ) );
        }

        $playback_url = ! empty( $job['playback_url'] ) ? esc_url_raw( $job['playback_url'] ) : wp_get_attachment_url( $attachment_id );
        if ( ! $playback_url ) {
            return array( 'provider_id' => $this->get_id(), 'provider_label' => $this->get_label(), 'status' => 'failed', 'supports_publish' => $this->supports_publish(), 'attachment_id' => $attachment_id, 'playback_url' => '', 'message' => __( 'The Local Media attachment does not have a valid playback URL.', 'videohub360-studio' ) );
        }

        return array( 'provider_id' => $this->get_id(), 'provider_label' => $this->get_label(), 'status' => self::STATUS_READY, 'supports_publish' => $this->supports_publish(), 'attachment_id' => $attachment_id, 'playback_url' => $playback_url, 'replay_video_id' => ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0, 'message' => __( 'Local Media replay is ready.', 'videohub360-studio' ) );
    }

    private function media_functions_available() {
        $this->load_media_functions();
        return function_exists( 'media_handle_sideload' );
    }

    private function load_media_functions() {
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    private function uploads_directory_is_writable() {
        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) || empty( $uploads['path'] ) ) {
            return false;
        }
        if ( ! wp_mkdir_p( $uploads['path'] ) ) {
            return false;
        }
        return is_dir( $uploads['path'] ) && wp_is_writable( $uploads['path'] );
    }

    private function poster_url( $attachment_id ) {
        $poster_id = get_post_thumbnail_id( $attachment_id );
        return $poster_id ? esc_url_raw( wp_get_attachment_url( $poster_id ) ) : '';
    }


    private function resolve_attachment_parent_post_id( array $job ) {
        $live_video_id   = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $replay_video_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;

        if ( $live_video_id && 'videohub360' === get_post_type( $live_video_id ) ) {
            return $live_video_id;
        }

        if ( $replay_video_id && 'videohub360' === get_post_type( $replay_video_id ) ) {
            return $replay_video_id;
        }

        return 0;
    }

    private function attachment_title( array $job ) {
        $live_video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $title = $live_video_id ? get_the_title( $live_video_id ) : '';
        return $title ? wp_strip_all_tags( $title ) : sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }

    private function attachment_filename( array $job, $extension ) {
        $base = $this->attachment_title( $job );
        return sanitize_file_name( $base . '.' . sanitize_key( $extension ) );
    }
}
