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
            return new WP_Error( 'vh360_studio_videopress_upload_forbidden', __( 'Cloud replay publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        if ( ! $this->is_available() ) {
            return new WP_Error( 'vh360_studio_videopress_unavailable', __( 'Cloud replay storage is not available. Ask an administrator to check Studio replay settings.', 'videohub360-studio' ), array( 'status' => 501 ) );
        }

        if ( empty( $recording['path'] ) || ! is_readable( $recording['path'] ) || ! is_file( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_videopress_missing_file', __( 'The validated recording file is not available for cloud replay publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }

        $this->load_media_functions();

        return array(
            'provider_id'    => $this->get_id(),
            'provider_label' => $this->get_label(),
            'status'         => 'prepared',
            'message'        => __( 'Cloud replay storage is available. The recording can be handed off for processing.', 'videohub360-studio' ),
        );
    }

    public function publish_recording( array $job, array $recording ) {
        $prepared = $this->prepare_publish( $job, $recording );
        if ( is_wp_error( $prepared ) ) {
            return $prepared;
        }

        $existing_attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        if ( $existing_attachment_id && 'attachment' === get_post_type( $existing_attachment_id ) ) {
            $this->copy_source_thumbnail_to_attachment( $existing_attachment_id, $job );
            return $this->result_from_attachment( $existing_attachment_id, __( 'Existing media attachment checked for cloud replay processing.', 'videohub360-studio' ) );
        }

        $source = $recording['path'];
        $extension = 'video/mp4' === $recording['mime_type'] ? 'mp4' : 'webm';
        $filename  = $this->attachment_filename( $job, $extension );
        $tmp = wp_tempnam( $filename );
        if ( ! $tmp || ! copy( $source, $tmp ) ) {
            if ( $tmp ) {
                @unlink( $tmp );
            }
            return new WP_Error( 'vh360_studio_videopress_copy_failed', __( 'Unable to prepare the recording for cloud replay handoff.', 'videohub360-studio' ), array( 'status' => 500 ) );
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

        $this->copy_source_thumbnail_to_attachment( absint( $attachment_id ), $job );

        return $this->result_from_attachment( absint( $attachment_id ), __( 'Recording added for replay processing.', 'videohub360-studio' ) );
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
            'poster_url'         => $attachment_id ? $this->attachment_poster_url( $attachment_id ) : '',
            'videopress_guid'    => $guid,
            'replay_video_id'    => ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0,
            'message'            => $guid ? __( 'Cloud replay GUID detected.', 'videohub360-studio' ) : __( 'Waiting for cloud replay processing.', 'videohub360-studio' ),
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
            'poster_url'           => $this->attachment_poster_url( $attachment_id ),
            'videopress_guid'      => $guid,
            'videopress_shortcode' => $guid ? '[videopress ' . $guid . ']' : '',
            'message'              => $guid ? __( 'Recording published through cloud replay storage.', 'videohub360-studio' ) : $message,
        );
    }

    private function source_thumbnail_id( array $job ) {
        foreach ( array( 'live_video_id', 'replay_video_id' ) as $key ) {
            $post_id = ! empty( $job[ $key ] ) ? absint( $job[ $key ] ) : 0;
            if ( ! $post_id ) {
                continue;
            }
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            if ( $thumbnail_id && 'attachment' === get_post_type( $thumbnail_id ) && 0 === strpos( (string) get_post_mime_type( $thumbnail_id ), 'image/' ) ) {
                return absint( $thumbnail_id );
            }
        }
        return 0;
    }

    private function copy_source_thumbnail_to_attachment( $attachment_id, array $job ) {
        $attachment_id = absint( $attachment_id );
        $thumbnail_id = $this->source_thumbnail_id( $job );
        if ( ! $attachment_id || ! $thumbnail_id ) {
            return;
        }
        set_post_thumbnail( $attachment_id, $thumbnail_id );
        $poster_url = wp_get_attachment_image_url( $thumbnail_id, 'large' ) ?: wp_get_attachment_url( $thumbnail_id );
        update_post_meta( $attachment_id, '_vh360_studio_poster_attachment_id', $thumbnail_id );
        if ( $poster_url ) {
            update_post_meta( $attachment_id, '_vh360_studio_poster_url', esc_url_raw( $poster_url ) );
            update_post_meta( $attachment_id, '_vh360_poster', esc_url_raw( $poster_url ) );
        }
    }

    private function attachment_poster_url( $attachment_id ) {
        $attachment_id = absint( $attachment_id );
        $poster_id = get_post_thumbnail_id( $attachment_id );
        if ( $poster_id ) {
            return esc_url_raw( wp_get_attachment_image_url( $poster_id, 'large' ) ?: wp_get_attachment_url( $poster_id ) );
        }
        $poster_url = get_post_meta( $attachment_id, '_vh360_studio_poster_url', true );
        return $poster_url ? esc_url_raw( $poster_url ) : '';
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

    public function upload_file( array $file, array $asset = array() ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = media_handle_sideload( $file, 0 );
        if ( is_wp_error( $attachment_id ) ) { return $attachment_id; }
        return array( 'provider' => $this->get_id(), 'status' => 'ready', 'provider_asset_id' => (string) $attachment_id, 'wp_attachment_id' => absint( $attachment_id ), 'videopress_guid' => '', 'playback_url' => wp_get_attachment_url( $attachment_id ), 'embed_url' => '', 'poster_url' => wp_get_attachment_image_url( $attachment_id, 'large' ), 'mime_type' => get_post_mime_type( $attachment_id ), 'file_size' => absint( $file['size'] ?? 0 ), 'metadata' => array( 'server_relay_attachment_id' => absint( $attachment_id ) ), 'error_code' => '', 'error_message' => '' );
    }

    public function authorize_direct_upload( array $asset ) {
        return array( 'method' => 'server', 'field' => 'file' );
    }

    public function complete_direct_upload( array $asset, array $payload = array() ) {
        return $this->check_asset_status( $asset );
    }

    public function check_asset_status( array $asset ) {
        $ready = ! empty( $asset['playback_url'] ) || ! empty( $asset['embed_url'] ) || ! empty( $asset['wp_attachment_id'] );
        return array( 'provider' => $this->get_id(), 'status' => $ready ? 'ready' : ( ! empty( $asset['status'] ) ? sanitize_key( $asset['status'] ) : 'processing' ), 'provider_asset_id' => ! empty( $asset['provider_asset_id'] ) ? $asset['provider_asset_id'] : '', 'wp_attachment_id' => ! empty( $asset['wp_attachment_id'] ) ? absint( $asset['wp_attachment_id'] ) : 0, 'videopress_guid' => ! empty( $asset['videopress_guid'] ) ? $asset['videopress_guid'] : '', 'playback_url' => ! empty( $asset['playback_url'] ) ? $asset['playback_url'] : '', 'embed_url' => ! empty( $asset['embed_url'] ) ? $asset['embed_url'] : '', 'poster_url' => ! empty( $asset['poster_url'] ) ? $asset['poster_url'] : '', 'mime_type' => ! empty( $asset['mime_type'] ) ? $asset['mime_type'] : 'video/mp4', 'file_size' => ! empty( $asset['file_size'] ) ? absint( $asset['file_size'] ) : 0, 'metadata' => array(), 'error_code' => '', 'error_message' => '' );
    }

    public function resolve_playback( array $asset ) {
        return $this->check_asset_status( $asset );
    }

    public function delete_asset( array $asset ) {
        if ( ! empty( $asset['wp_attachment_id'] ) ) { wp_delete_attachment( absint( $asset['wp_attachment_id'] ), true ); }
        return true;
    }

}
