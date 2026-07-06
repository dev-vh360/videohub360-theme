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
    const STATUS_ATTACHED_WAITING = 'media_attached_waiting_videopress';

    public function get_id() {
        return 'videopress';
    }

    public function get_label() {
        return __( 'VideoPress', 'videohub360-studio' );
    }

    public function is_available() {
        return (bool) apply_filters( 'vh360_studio_videopress_available', $this->has_videopress_integration(), $this );
    }

    public function supports_publish() {
        return $this->is_available() && current_user_can( 'upload_files' );
    }

    public function prepare_publish( array $job, array $recording ) {
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_videopress_upload_forbidden', __( 'VideoPress publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        if ( ! $this->is_available() ) {
            return new WP_Error( 'vh360_studio_videopress_unavailable', __( 'VideoPress publishing requires Jetpack/VideoPress to be active and available.', 'videohub360-studio' ), array( 'status' => 501 ) );
        }

        if ( empty( $recording['path'] ) || ! is_readable( $recording['path'] ) || ! is_file( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_videopress_missing_file', __( 'The validated recording file is not available for VideoPress publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }

        $this->load_media_functions();

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

        $existing_attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        if ( $existing_attachment_id && 'attachment' === get_post_type( $existing_attachment_id ) ) {
            return $this->result_from_attachment( $existing_attachment_id, __( 'Existing Media Library attachment checked for VideoPress processing.', 'videohub360-studio' ) );
        }

        $source = $recording['path'];
        $extension = 'video/mp4' === $recording['mime_type'] ? 'mp4' : 'webm';
        $filename  = $this->attachment_filename( $job, $extension );
        $tmp = wp_tempnam( $filename );
        if ( ! $tmp || ! copy( $source, $tmp ) ) {
            if ( $tmp ) {
                @unlink( $tmp );
            }
            return new WP_Error( 'vh360_studio_videopress_copy_failed', __( 'Unable to prepare the recording for Media Library handoff.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $file = array(
            'name'     => $filename,
            'type'     => $recording['mime_type'],
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => absint( $recording['file_size'] ),
        );

        $post_id = $this->resolve_attachment_parent_post_id( $job );
        $attachment_id = media_handle_sideload( $file, $post_id, $this->attachment_title( $job ) );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return $attachment_id;
        }

        return $this->result_from_attachment( absint( $attachment_id ), __( 'Recording added to the Media Library. Waiting for VideoPress to return a GUID.', 'videohub360-studio' ) );
    }

    public function get_publish_status( array $job ) {
        $attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        $guid = ! empty( $job['videopress_guid'] ) ? sanitize_text_field( $job['videopress_guid'] ) : ( $attachment_id ? $this->detect_videopress_guid( $attachment_id ) : '' );
        $playback_url = ! empty( $job['playback_url'] ) ? esc_url_raw( $job['playback_url'] ) : ( $attachment_id ? wp_get_attachment_url( $attachment_id ) : '' );

        return array(
            'provider_id'        => $this->get_id(),
            'provider_label'     => $this->get_label(),
            'status'             => $guid ? 'published' : ( $attachment_id ? self::STATUS_ATTACHED_WAITING : ( ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : 'pending' ) ),
            'supports_publish'   => $this->supports_publish(),
            'attachment_id'      => $attachment_id,
            'playback_url'       => $playback_url,
            'videopress_guid'    => $guid,
            'replay_video_id'    => ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0,
            'message'            => $guid ? __( 'VideoPress GUID detected.', 'videohub360-studio' ) : __( 'Waiting for VideoPress processing to provide a GUID.', 'videohub360-studio' ),
        );
    }

    private function result_from_attachment( $attachment_id, $message ) {
        $guid = $this->detect_videopress_guid( $attachment_id );
        $playback_url = wp_get_attachment_url( $attachment_id );

        return array(
            'provider_id'          => $this->get_id(),
            'provider_label'       => $this->get_label(),
            'status'               => $guid ? 'published' : self::STATUS_ATTACHED_WAITING,
            'attachment_id'        => absint( $attachment_id ),
            'playback_url'         => $playback_url ? esc_url_raw( $playback_url ) : '',
            'videopress_guid'      => $guid,
            'videopress_shortcode' => $guid ? '[videopress ' . $guid . ']' : '',
            'message'              => $guid ? __( 'Recording published through VideoPress.', 'videohub360-studio' ) : $message,
        );
    }

    private function has_videopress_integration() {
        $active_plugins = (array) get_option( 'active_plugins', array() );
        $network_plugins = is_multisite() ? array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) : array();
        $plugins = array_merge( $active_plugins, $network_plugins );
        $has_plugin = (bool) array_filter(
            $plugins,
            function( $plugin ) {
                return in_array( $plugin, array( 'jetpack/jetpack.php', 'jetpack-videopress/jetpack-videopress.php', 'videopress/videopress.php' ), true );
            }
        );

        return $has_plugin || class_exists( 'Jetpack' ) || class_exists( 'Automattic\\Jetpack\\VideoPress\\Initializer' ) || defined( 'JETPACK__VERSION' ) || defined( 'VIDEOPRESS__PLUGIN_DIR' ) || function_exists( 'videopress_shortcode_callback' );
    }

    private function load_media_functions() {
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
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

    private function detect_videopress_guid( $attachment_id ) {
        foreach ( array( 'videopress_guid', '_videopress_guid', 'videopress_video_guid', '_videopress_video_guid', 'jetpack_videopress_guid', '_jetpack_videopress_guid' ) as $key ) {
            $value = get_post_meta( absint( $attachment_id ), $key, true );
            $guid = $this->normalize_guid( $value );
            if ( $guid ) {
                return $guid;
            }
        }

        $metadata = wp_get_attachment_metadata( absint( $attachment_id ) );
        return $this->find_guid_in_metadata( $metadata );
    }

    private function find_guid_in_metadata( $metadata ) {
        if ( is_string( $metadata ) ) {
            return $this->normalize_guid( $metadata );
        }

        if ( ! is_array( $metadata ) ) {
            return '';
        }

        foreach ( $metadata as $key => $value ) {
            if ( is_string( $key ) && false !== stripos( $key, 'videopress' ) ) {
                $guid = $this->normalize_guid( $value );
                if ( $guid ) {
                    return $guid;
                }
            }

            if ( is_array( $value ) ) {
                $guid = $this->find_guid_in_metadata( $value );
                if ( $guid ) {
                    return $guid;
                }
                continue;
            }

            $guid = $this->normalize_guid( $value );
            if ( $guid && ( is_string( $key ) && false !== stripos( $key, 'guid' ) ) ) {
                return $guid;
            }
        }

        return '';
    }

    private function normalize_guid( $value ) {
        if ( is_array( $value ) ) {
            foreach ( array( 'guid', 'videopress_guid', 'video_guid', 'id' ) as $key ) {
                if ( isset( $value[ $key ] ) ) {
                    $guid = $this->normalize_guid( $value[ $key ] );
                    if ( $guid ) {
                        return $guid;
                    }
                }
            }
            return '';
        }

        if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
            return '';
        }

        $value = trim( (string) $value );
        if ( preg_match( '/^[A-Za-z0-9_-]{8,}$/', $value ) ) {
            return sanitize_text_field( $value );
        }

        if ( preg_match( '/videopress(?:\.com)?\/(?:v\/)?([A-Za-z0-9_-]{8,})/i', $value, $matches ) ) {
            return sanitize_text_field( $matches[1] );
        }

        return '';
    }
}
