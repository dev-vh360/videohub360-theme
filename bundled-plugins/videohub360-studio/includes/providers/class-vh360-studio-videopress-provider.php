<?php
/**
 * VideoPress replay storage provider.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_VideoPress_Provider implements VH360_Studio_Replay_Storage_Provider {
    public function get_id() {
        return 'videopress';
    }

    public function get_label() {
        return __( 'VideoPress', 'videohub360-studio' );
    }

    public function is_available() {
        return $this->has_videopress_integration();
    }

    public function supports_publish() {
        return $this->is_available();
    }

    public function prepare_publish( array $job, array $recording ) {
        if ( ! $this->supports_publish() ) {
            return new WP_Error( 'vh360_studio_videopress_unavailable', __( 'VideoPress publishing requires Jetpack/VideoPress to be active and available.', 'videohub360-studio' ), array( 'status' => 501 ) );
        }

        if ( empty( $recording['path'] ) || ! is_readable( $recording['path'] ) || ! is_file( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_videopress_missing_file', __( 'The validated recording file is not available for VideoPress publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }

        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        return array(
            'provider_id'    => $this->get_id(),
            'provider_label' => $this->get_label(),
            'status'         => 'prepared',
            'message'        => __( 'VideoPress is available. The recording can be handed off through the WordPress Media Library.', 'videohub360-studio' ),
        );
    }

    public function publish_recording( array $job, array $recording ) {
        $prepared = $this->prepare_publish( $job, $recording );
        if ( is_wp_error( $prepared ) ) {
            return $prepared;
        }

        $source = $recording['path'];
        $extension = 'video/mp4' === $recording['mime_type'] ? 'mp4' : 'webm';
        $tmp = wp_tempnam( 'vh360-studio-replay-' . absint( $job['id'] ) . '.' . $extension );
        if ( ! $tmp || ! copy( $source, $tmp ) ) {
            if ( $tmp ) {
                @unlink( $tmp );
            }
            return new WP_Error( 'vh360_studio_videopress_copy_failed', __( 'Unable to prepare the recording for Media Library handoff.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $file = array(
            'name'     => sanitize_file_name( 'vh360-studio-replay-' . absint( $job['id'] ) . '.' . $extension ),
            'type'     => $recording['mime_type'],
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => absint( $recording['file_size'] ),
        );

        $post_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;
        $attachment_id = media_handle_sideload( $file, $post_id, $this->attachment_title( $job ) );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return $attachment_id;
        }

        $guid = $this->detect_videopress_guid( $attachment_id );
        if ( ! $guid ) {
            return new WP_Error( 'vh360_studio_videopress_guid_missing', __( 'The recording was added to the Media Library, but VideoPress did not return a GUID. Publishing cannot be marked successful yet.', 'videohub360-studio' ), array( 'status' => 409, 'attachment_id' => absint( $attachment_id ) ) );
        }

        return array(
            'provider_id'        => $this->get_id(),
            'provider_label'     => $this->get_label(),
            'status'             => 'published',
            'attachment_id'      => absint( $attachment_id ),
            'playback_url'       => wp_get_attachment_url( $attachment_id ),
            'videopress_guid'    => $guid,
            'videopress_shortcode' => '[videopress ' . $guid . ']',
            'message'            => __( 'Recording published through VideoPress.', 'videohub360-studio' ),
        );
    }

    public function get_publish_status( array $job ) {
        $attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        $guid = ! empty( $job['videopress_guid'] ) ? sanitize_text_field( $job['videopress_guid'] ) : ( $attachment_id ? $this->detect_videopress_guid( $attachment_id ) : '' );

        return array(
            'provider_id'        => $this->get_id(),
            'provider_label'     => $this->get_label(),
            'status'             => $guid ? 'published' : ( ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : 'pending' ),
            'supports_publish'   => $this->supports_publish(),
            'attachment_id'      => $attachment_id,
            'playback_url'       => ! empty( $job['playback_url'] ) ? esc_url_raw( $job['playback_url'] ) : ( $attachment_id ? wp_get_attachment_url( $attachment_id ) : '' ),
            'videopress_guid'    => $guid,
            'replay_video_id'    => ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0,
        );
    }

    private function has_videopress_integration() {
        return class_exists( 'Jetpack' ) || class_exists( 'Automattic\\Jetpack\\VideoPress\\Initializer' ) || defined( 'JETPACK__VERSION' ) || defined( 'VIDEOPRESS__PLUGIN_DIR' ) || function_exists( 'videopress_shortcode_callback' );
    }

    private function attachment_title( array $job ) {
        return sprintf( __( 'VH360 Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }

    private function detect_videopress_guid( $attachment_id ) {
        foreach ( array( 'videopress_guid', '_videopress_guid', 'videopress_video_guid', '_videopress_video_guid', 'jetpack_videopress_guid', '_jetpack_videopress_guid' ) as $key ) {
            $value = get_post_meta( absint( $attachment_id ), $key, true );
            if ( is_string( $value ) && preg_match( '/^[A-Za-z0-9_-]{8,}$/', $value ) ) {
                return sanitize_text_field( $value );
            }
        }
        return '';
    }
}
