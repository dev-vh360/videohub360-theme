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
            return new WP_Error( 'vh360_studio_publitio_wrong_provider', __( 'This recording job is not configured for this replay storage method.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_status', __( 'This replay storage method requires a processing job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_publitio_upload_forbidden', __( 'Replay publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( ! function_exists( 'wp_remote_request' ) ) {
            return new WP_Error( 'vh360_studio_publitio_http_unavailable', __( 'Replay publishing is unavailable because the HTTP API is not available.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! $this->client()->has_credentials() ) {
            return new WP_Error( 'vh360_studio_publitio_credentials_missing', __( 'Cloud replay storage is not configured. Ask an administrator to check Studio replay settings.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( empty( $recording['path'] ) || ! file_exists( $recording['path'] ) || ! is_file( $recording['path'] ) || ! is_readable( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file', __( 'The validated recording file is not available for replay publishing.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        $chunks = new VH360_Studio_Recording_Chunks( new VH360_Studio_Recording_Jobs( VH360_Studio_Plugin::instance()->registry() ) );
        if ( ! $chunks->is_path_in_base_directory( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_path', __( 'The validated recording path is not allowed for replay publishing.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! in_array( $this->base_mime_type( $recording['mime_type'] ), array( 'video/mp4', 'video/webm' ), true ) ) {
            return new WP_Error( 'vh360_studio_publitio_invalid_type', __( 'Replay publishing supports MP4 and WebM recordings only.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        if ( empty( $recording['file_size'] ) || 0 >= absint( $recording['file_size'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_empty_file', __( 'Replay publishing requires a non-empty recording file.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return array(
            'provider_id'      => $this->get_id(),
            'provider_label'   => $this->get_label(),
            'status'           => 'prepared',
            'supports_publish' => $this->supports_publish(),
            'message'          => __( 'Cloud replay storage is configured and ready to upload this replay.', 'videohub360-studio' ),
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
        return $this->result_from_response( $result, __( 'Recording uploaded to cloud replay storage.', 'videohub360-studio' ) );
    }

    public function get_publish_status( array $job ) {
        $file_id = ! empty( $job['publitio_file_id'] ) ? sanitize_text_field( $job['publitio_file_id'] ) : '';
        if ( ! $file_id ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'pending', 'supports_publish'=>$this->supports_publish(), 'publitio_file_id'=>'', 'playback_url'=>'', 'poster_url'=>'', 'message'=>__( 'Cloud replay file has not been uploaded yet.', 'videohub360-studio' ) );
        }
        $result = $this->client()->show_file( $file_id );
        if ( is_wp_error( $result ) ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'failed', 'supports_publish'=>$this->supports_publish(), 'publitio_file_id'=>$file_id, 'playback_url'=>'', 'poster_url'=>'', 'message'=>$result->get_error_message() );
        }
        return $this->result_from_response( $result, __( 'Cloud replay status checked.', 'videohub360-studio' ) );
    }

    public function test_connection() { return $this->client()->list_files( 1 ); }

    public function supports_direct_browser_publish() {
        return 'direct_browser' === sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) ) && '' !== sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
    }

    public function verify_direct_upload_file( $file_id ) {
        $file_id = sanitize_text_field( $file_id );
        if ( '' === $file_id ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Cloud upload file ID is missing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $result = $this->client()->show_file( $file_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $verified = $this->result_from_response( $result, __( 'Cloud upload verified.', 'videohub360-studio' ) );
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
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Cloud upload did not return a valid file reference.', 'videohub360-studio' ), array( 'status' => 502 ) );
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
            'mime_type'            => sanitize_mime_type( $this->first_scalar( $file, array( 'mime_type', 'content_type', 'type' ) ) ),
            'extension'            => sanitize_key( $this->first_scalar( $file, array( 'extension', 'ext' ) ) ),
            'title'                => sanitize_text_field( $this->first_scalar( $file, array( 'title', 'name' ) ) ),
            'folder'               => sanitize_text_field( $this->first_scalar( $file, array( 'folder', 'folder_id' ) ) ),
            'tags'                 => sanitize_text_field( $this->first_scalar( $file, array( 'tags' ) ) ),
            'playback_url'         => $playback,
            'poster_url'           => $poster,
            'embed_url'            => $embed,
            'supports_publish'     => $this->supports_publish(),
            'message'              => $ready ? $message : __( 'Cloud upload succeeded and is processing playback URLs.', 'videohub360-studio' ),
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

    public function upload_file( array $file, array $asset = array() ) {
        if ( ! $this->is_available() ) {
            return new WP_Error( 'vh360_studio_publitio_unavailable', __( 'Publitio storage is not configured or unavailable.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $params = array(
            'title'           => ! empty( $asset['original_filename'] ) ? sanitize_text_field( $asset['original_filename'] ) : sanitize_file_name( $file['name'] ),
            'description'     => '',
            'tags'            => 'videohub360,studio,video_asset',
            'public_id'       => sanitize_title( 'vh360-video-asset-' . ( ! empty( $asset['asset_uuid'] ) ? $asset['asset_uuid'] : wp_generate_uuid4() ) ),
            'privacy'         => 'private' === $this->privacy() ? '0' : '1',
            'option_download' => get_option( 'vh360_studio_publitio_option_download', '0' ) ? '1' : '0',
            'option_hls'      => get_option( 'vh360_studio_publitio_option_hls', '0' ) ? '1' : '0',
            'option_ad'       => '0',
        );
        $folder = sanitize_text_field( get_option( 'vh360_studio_publitio_folder', '' ) );
        if ( $folder ) { $params['folder'] = $folder; }
        $result = $this->client()->create_file( $file['tmp_name'], $params, $file['type'], $file['name'] );
        if ( is_wp_error( $result ) ) { return $result; }
        $normalized = $this->result_from_response( $result, __( 'Video uploaded to Publitio.', 'videohub360-studio' ) );
        if ( is_wp_error( $normalized ) ) { return $normalized; }
        return $this->asset_result( $normalized );
    }

    public function authorize_direct_upload( array $asset ) {
        if ( ! $this->supports_direct_browser_publish() ) {
            return array( 'method' => 'server', 'field' => 'file' );
        }
        $direct_max = absint( get_option( 'vh360_studio_publitio_direct_max_size', 314572800 ) );
        if ( $direct_max && ! empty( $asset['file_size'] ) && absint( $asset['file_size'] ) > $direct_max ) {
            return array( 'method' => 'server', 'field' => 'file' );
        }
        $preset = sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
        $public_id = sanitize_title( 'vh360-video-asset-' . ( ! empty( $asset['asset_uuid'] ) ? $asset['asset_uuid'] : wp_generate_uuid4() ) );
        $token = wp_generate_password( 40, false, false );
        set_transient( $this->asset_direct_upload_transient_key( $asset['asset_uuid'], $token ), array( 'asset_uuid' => $asset['asset_uuid'], 'user_id' => absint( $asset['user_id'] ), 'public_id' => $public_id, 'mime_type' => sanitize_mime_type( $asset['mime_type'] ), 'file_size' => absint( $asset['file_size'] ), 'expires' => time() + 30 * MINUTE_IN_SECONDS ), 30 * MINUTE_IN_SECONDS );
        return array(
            'method'    => 'direct',
            'httpMethod'=> 'POST',
            'url'       => 'https://api.publit.io/v1/files/create/' . rawurlencode( $preset ),
            'fields'    => array(
                'api_key'       => sanitize_text_field( get_option( 'vh360_studio_publitio_api_key', '' ) ),
                'upload_preset' => $preset,
                'public_id'     => $public_id,
                'privacy'       => 'private' === $this->privacy() ? '0' : '1',
            ),
            'fileField' => 'file',
            'direct_upload_token' => $token,
            'max_size' => $direct_max,
        );
    }

    public function complete_direct_upload( array $asset, array $payload = array() ) {
        $token = ! empty( $payload['direct_upload_token'] ) ? sanitize_text_field( $payload['direct_upload_token'] ) : '';
        $key = $this->asset_direct_upload_transient_key( $asset['asset_uuid'], $token );
        $expected = get_transient( $key );
        if ( ! is_array( $expected ) || empty( $expected['asset_uuid'] ) || $expected['asset_uuid'] !== $asset['asset_uuid'] || absint( $expected['user_id'] ) !== absint( $asset['user_id'] ) || time() > absint( $expected['expires'] ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_token_invalid', __( 'Direct upload token is invalid or expired.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        $file_id = '';
        $payloads = array( $payload );
        foreach ( array( 'file', 'data' ) as $container ) {
            if ( ! empty( $payload[ $container ] ) && is_array( $payload[ $container ] ) ) { $payloads[] = $payload[ $container ]; }
        }
        foreach ( $payloads as $candidate ) {
            foreach ( array( 'id', 'file_id', 'provider_asset_id', 'publitio_file_id' ) as $key ) {
                if ( ! empty( $candidate[ $key ] ) && is_scalar( $candidate[ $key ] ) ) { $file_id = sanitize_text_field( $candidate[ $key ] ); break 2; }
            }
        }
        $verified = $this->verify_direct_upload_file( $file_id );
        if ( is_wp_error( $verified ) ) { return $verified; }
        $valid = $this->validate_direct_asset_metadata( $verified, $expected );
        if ( is_wp_error( $valid ) ) { return $valid; }
        delete_transient( $key );
        return $this->asset_result( $verified );
    }

    public function check_asset_status( array $asset ) {
        $file_id = ! empty( $asset['provider_asset_id'] ) ? sanitize_text_field( $asset['provider_asset_id'] ) : '';
        if ( ! $file_id ) { return $this->asset_result( array( 'status' => 'pending' ) ); }
        $result = $this->client()->show_file( $file_id );
        if ( is_wp_error( $result ) ) { return array( 'provider' => $this->get_id(), 'status' => 'failed', 'provider_asset_id' => $file_id, 'error_code' => $result->get_error_code(), 'error_message' => $result->get_error_message(), 'metadata' => array() ); }
        $normalized = $this->result_from_response( $result, __( 'Publitio video status checked.', 'videohub360-studio' ) );
        if ( is_wp_error( $normalized ) ) { return array( 'provider' => $this->get_id(), 'status' => 'failed', 'provider_asset_id' => $file_id, 'error_code' => $normalized->get_error_code(), 'error_message' => $normalized->get_error_message(), 'metadata' => array() ); }
        return $this->asset_result( $normalized );
    }

    public function resolve_playback( array $asset ) { return $this->check_asset_status( $asset ); }

    public function delete_asset( array $asset ) {
        $file_id = ! empty( $asset['provider_asset_id'] ) ? sanitize_text_field( $asset['provider_asset_id'] ) : '';
        return $file_id ? $this->client()->delete_file( $file_id ) : true;
    }

    private function asset_result( array $result ) {
        $status = ! empty( $result['status'] ) && in_array( sanitize_key( $result['status'] ), array( self::STATUS_READY, 'publitio_direct_ready', 'ready', 'published' ), true ) ? 'ready' : ( ! empty( $result['status'] ) && false !== strpos( sanitize_key( $result['status'] ), 'fail' ) ? 'failed' : 'processing' );
        return array( 'provider' => $this->get_id(), 'status' => $status, 'provider_asset_id' => ! empty( $result['publitio_file_id'] ) ? sanitize_text_field( $result['publitio_file_id'] ) : ( ! empty( $result['provider_file_id'] ) ? sanitize_text_field( $result['provider_file_id'] ) : '' ), 'wp_attachment_id' => 0, 'videopress_guid' => '', 'playback_url' => ! empty( $result['playback_url'] ) ? esc_url_raw( $result['playback_url'] ) : '', 'embed_url' => ! empty( $result['embed_url'] ) ? esc_url_raw( $result['embed_url'] ) : '', 'poster_url' => ! empty( $result['poster_url'] ) ? esc_url_raw( $result['poster_url'] ) : '', 'mime_type' => ! empty( $result['mime_type'] ) ? sanitize_mime_type( $result['mime_type'] ) : 'video/mp4', 'file_size' => ! empty( $result['file_size'] ) ? absint( $result['file_size'] ) : 0, 'metadata' => $result, 'error_code' => '', 'error_message' => '' );
    }


    private function asset_direct_upload_transient_key( $asset_uuid, $token ) {
        return 'vh360_studio_asset_publitio_' . md5( sanitize_text_field( $asset_uuid ) . '|' . (string) $token );
    }

    private function validate_direct_asset_metadata( array $verified, array $expected ) {
        $expected_public_id = ! empty( $expected['public_id'] ) ? sanitize_title( $expected['public_id'] ) : '';
        $actual_public_id = ! empty( $verified['public_id'] ) ? sanitize_title( $verified['public_id'] ) : '';
        if ( $expected_public_id && $actual_public_id && $expected_public_id !== $actual_public_id ) {
            return new WP_Error( 'vh360_studio_direct_upload_public_id_mismatch', __( 'Cloud upload verification found an unexpected public ID.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $expected_size = ! empty( $expected['file_size'] ) ? absint( $expected['file_size'] ) : 0;
        $actual_size = ! empty( $verified['file_size'] ) ? absint( $verified['file_size'] ) : 0;
        if ( $expected_size && $actual_size ) {
            $allowed_delta = max( 1048576, (int) ceil( $expected_size * 0.05 ) );
            if ( abs( $actual_size - $expected_size ) > $allowed_delta ) {
                return new WP_Error( 'vh360_studio_direct_upload_size_mismatch', __( 'Cloud upload verification found a file size mismatch.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }
        }
        $mime = ! empty( $verified['mime_type'] ) ? $this->base_mime_type( $verified['mime_type'] ) : '';
        if ( $mime && 0 !== strpos( $mime, 'video/' ) ) {
            return new WP_Error( 'vh360_studio_direct_upload_invalid_verified_type', __( 'Cloud upload verification found a non-video file.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        return true;
    }

}
