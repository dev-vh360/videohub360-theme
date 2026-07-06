<?php
/**
 * Publitio replay storage provider.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Publitio_Provider implements VH360_Studio_Replay_Storage_Provider {
    const STATUS_READY      = 'publitio_ready';
    const STATUS_PROCESSING = 'publitio_processing';

    public function get_id() { return 'publitio'; }

    public function get_label() { return __( 'Publitio', 'videohub360-studio' ); }

    public function is_available() {
        $client = $this->client();
        $last   = sanitize_key( get_option( 'vh360_studio_publitio_last_status', '' ) );
        return (bool) apply_filters( 'vh360_studio_publitio_available', $client->has_credentials() && ( '' === $last || 'success' === $last ), $this );
    }

    public function supports_publish() {
        return $this->is_available() && current_user_can( 'upload_files' );
    }

    public function prepare_publish( array $job, array $recording ) {
        if ( 'publitio' !== sanitize_key( $job['storage_provider'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_wrong_provider', __( 'This recording job is not configured for Publitio publishing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_status', __( 'Publitio publishing requires a processing job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_publitio_upload_forbidden', __( 'Publitio publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! function_exists( 'wp_remote_request' ) ) {
            return new WP_Error( 'vh360_studio_publitio_http_unavailable', __( 'WordPress HTTP API is unavailable for Publitio publishing.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! $this->client()->has_credentials() ) {
            return new WP_Error( 'vh360_studio_publitio_credentials_missing', __( 'Publitio credentials are missing. Ask an administrator to configure Studio replay providers.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( empty( $recording['path'] ) || ! file_exists( $recording['path'] ) || ! is_file( $recording['path'] ) || ! is_readable( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file', __( 'The validated recording file is not available for Publitio publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        $chunks = new VH360_Studio_Recording_Chunks( new VH360_Studio_Recording_Jobs( VH360_Studio_Plugin::instance()->registry() ) );
        if ( ! $chunks->is_path_in_base_directory( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_path', __( 'The validated recording path is not allowed for Publitio publishing.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! in_array( $this->base_mime_type( $recording['mime_type'] ), array( 'video/mp4', 'video/webm' ), true ) ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_type', __( 'Publitio publishing supports MP4 and WebM recordings only.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        if ( empty( $recording['file_size'] ) || 0 >= absint( $recording['file_size'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_empty_file', __( 'Publitio publishing requires a non-empty recording file.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return array(
            'provider_id'      => $this->get_id(),
            'provider_label'   => $this->get_label(),
            'status'           => 'prepared',
            'supports_publish' => $this->supports_publish(),
            'message'          => __( 'Publitio is configured and ready to upload this replay.', 'videohub360-studio' ),
        );
    }

    public function publish_recording( array $job, array $recording ) {
        $prepared = $this->prepare_publish( $job, $recording );
        if ( is_wp_error( $prepared ) ) { return $prepared; }
        $params = $this->upload_params( $job, $recording );
        $result = $this->client()->create_file( $recording['path'], $params, $recording['mime_type'], basename( $recording['path'] ) );
        if ( is_wp_error( $result ) && 'vh360_studio_publitio_api_error' === $result->get_error_code() ) {
            $params['public_id'] = $params['public_id'] . '-' . wp_generate_password( 6, false, false );
            $result = $this->client()->create_file( $recording['path'], $params, $recording['mime_type'], basename( $recording['path'] ) );
        }
        if ( is_wp_error( $result ) ) { return $result; }
        return $this->result_from_response( $result, __( 'Recording uploaded to Publitio.', 'videohub360-studio' ) );
    }

    public function get_publish_status( array $job ) {
        $file_id = ! empty( $job['publitio_file_id'] ) ? sanitize_text_field( $job['publitio_file_id'] ) : '';
        if ( ! $file_id ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'pending', 'supports_publish'=>$this->supports_publish(), 'publitio_file_id'=>'', 'playback_url'=>'', 'poster_url'=>'', 'message'=>__( 'Publitio file has not been uploaded yet.', 'videohub360-studio' ) );
        }
        $result = $this->client()->show_file( $file_id );
        if ( is_wp_error( $result ) ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'failed', 'supports_publish'=>$this->supports_publish(), 'publitio_file_id'=>$file_id, 'playback_url'=>'', 'poster_url'=>'', 'message'=>$result->get_error_message() );
        }
        return $this->result_from_response( $result, __( 'Publitio replay status checked.', 'videohub360-studio' ) );
    }

    public function test_connection() { return $this->client()->list_files( 1 ); }

    public function supports_direct_browser_publish() {
        return 'direct_browser' === sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) ) && '' !== sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
    }

    public function verify_direct_upload_file( $file_id ) {
        $file_id = sanitize_text_field( $file_id );
        if ( '' === $file_id ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Publitio file ID is missing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $result = $this->client()->show_file( $file_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $verified = $this->result_from_response( $result, __( 'Publitio direct upload verified.', 'videohub360-studio' ) );
        if ( is_array( $verified ) ) {
            $verified['status']          = self::STATUS_READY === $verified['status'] ? 'publitio_direct_ready' : 'publitio_direct_processing';
            $verified['provider_status'] = $verified['status'];
        }
        return $verified;
    }

    private function client() { return new VH360_Studio_Publitio_Client(); }

    private function upload_params( array $job, array $recording ) {
        $title = $this->title( $job );
        $params = array(
            'title'           => $title,
            'description'     => $this->description( $job ),
            'tags'            => 'videohub360,studio,replay',
            'public_id'       => sanitize_title( 'vh360-studio-replay-' . absint( $job['id'] ) ),
            'privacy'         => 'private' === $this->privacy() ? '0' : '1',
            'option_download' => get_option( 'vh360_studio_publitio_option_download', '0' ) ? '1' : '0',
            'option_hls'      => get_option( 'vh360_studio_publitio_option_hls', '0' ) ? '1' : '0',
            'option_ad'       => '0',
        );
        $folder = sanitize_text_field( get_option( 'vh360_studio_publitio_folder', '' ) );
        if ( $folder ) { $params['folder'] = $folder; }
        return $params;
    }

    private function result_from_response( array $response, $message ) {
        $file = $this->find_file_payload( $response );
        $file_id = $this->first_scalar( $file, array( 'id', 'file_id' ) );
        if ( ! $file_id ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Publitio did not return a file ID.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        $player   = $this->player_payload( $file_id );
        $playback = $this->best_playback_url( $file );
        $embed    = $this->best_embed_url( $player ? $player : $file );
        $poster   = $this->best_poster_url( $file );
        $ready    = (bool) ( $playback || $embed );
        return array(
            'provider_id'          => $this->get_id(),
            'provider_label'       => $this->get_label(),
            'status'               => $ready ? self::STATUS_READY : self::STATUS_PROCESSING,
            'provider_status'      => $ready ? self::STATUS_READY : self::STATUS_PROCESSING,
            'publitio_file_id'     => sanitize_text_field( $file_id ),
            'public_id'            => sanitize_text_field( $this->first_scalar( $file, array( 'public_id' ) ) ),
            'file_size'            => absint( $this->first_scalar( $file, array( 'size', 'bytes', 'file_size' ) ) ),
            'playback_url'         => $playback,
            'poster_url'           => $poster,
            'embed_url'            => $embed,
            'supports_publish'     => $this->supports_publish(),
            'message'              => $ready ? $message : __( 'Publitio upload succeeded and is processing playback URLs.', 'videohub360-studio' ),
        );
    }

    private function find_file_payload( array $response ) {
        foreach ( array( 'file', 'data' ) as $key ) {
            if ( ! empty( $response[ $key ] ) && is_array( $response[ $key ] ) ) { return $response[ $key ]; }
        }
        return $response;
    }

    private function player_payload( $file_id ) {
        $player_id = sanitize_text_field( get_option( 'vh360_studio_publitio_player_id', '' ) );
        if ( ! $player_id ) {
            return array();
        }
        $params = array( 'player' => $player_id );
        $adtag  = sanitize_text_field( get_option( 'vh360_studio_publitio_adtag_id', '' ) );
        if ( $adtag ) {
            $params['adtag'] = $adtag;
        }
        $response = $this->client()->player_file( $file_id, $params );
        if ( is_wp_error( $response ) ) {
            return array();
        }
        return $this->find_file_payload( $response );
    }

    private function best_playback_url( array $file ) {
        foreach ( array( 'url_preview', 'url_download', 'url', 'secure_url', 'url_stream' ) as $key ) {
            if ( ! empty( $file[ $key ] ) && wp_http_validate_url( $file[ $key ] ) ) { return esc_url_raw( $file[ $key ] ); }
        }
        return '';
    }

    private function best_embed_url( array $file ) {
        foreach ( array( 'embed_url', 'url_embed', 'player_url' ) as $key ) {
            if ( ! empty( $file[ $key ] ) ) {
                $url = $this->normalize_url( $file[ $key ] );
                if ( $url ) { return $url; }
            }
        }
        foreach ( array( 'iframe_html', 'player_html', 'source_html' ) as $key ) {
            if ( ! empty( $file[ $key ] ) && is_string( $file[ $key ] ) && preg_match( '/src=["\']([^"\']+)["\']/i', $file[ $key ], $matches ) ) {
                $url = $this->normalize_url( $matches[1] );
                if ( $url ) { return $url; }
            }
        }
        return '';
    }

    private function normalize_url( $url ) {
        $url = trim( (string) $url );
        if ( 0 === strpos( $url, '//' ) ) {
            $url = 'https:' . $url;
        }
        return wp_http_validate_url( $url ) ? esc_url_raw( $url ) : '';
    }

    private function best_poster_url( array $file ) {
        foreach ( array( 'url_thumbnail', 'thumbnail_url', 'poster_url', 'url_poster' ) as $key ) {
            if ( ! empty( $file[ $key ] ) && wp_http_validate_url( $file[ $key ] ) ) { return esc_url_raw( $file[ $key ] ); }
        }
        return '';
    }

    private function first_scalar( array $data, array $keys ) {
        foreach ( $keys as $key ) {
            if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) && '' !== (string) $data[ $key ] ) { return (string) $data[ $key ]; }
        }
        return '';
    }

    private function privacy() {
        return 'private' === sanitize_key( get_option( 'vh360_studio_publitio_privacy', 'public' ) ) ? 'private' : 'public';
    }

    private function title( array $job ) {
        if ( ! empty( $job['live_video_id'] ) ) { $title = get_the_title( absint( $job['live_video_id'] ) ); if ( $title ) { return wp_strip_all_tags( $title ); } }
        return sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }

    private function description( array $job ) {
        if ( ! empty( $job['live_video_id'] ) ) { $post = get_post( absint( $job['live_video_id'] ) ); if ( $post ) { return wp_strip_all_tags( $post->post_content ); } }
        return '';
    }

    private function base_mime_type( $mime_type ) {
        $parts = explode( ';', (string) $mime_type );
        return strtolower( sanitize_mime_type( trim( $parts[0] ) ) );
    }
}
