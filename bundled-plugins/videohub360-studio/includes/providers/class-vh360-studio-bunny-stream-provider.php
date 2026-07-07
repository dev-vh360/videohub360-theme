<?php
/**
 * Bunny Stream replay storage provider.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VH360_Studio_Bunny_Stream_Provider implements VH360_Studio_Replay_Storage_Provider {
    const STATUS_PROCESSING = 'bunny_stream_processing';
    const STATUS_READY      = 'bunny_stream_ready';
    const STATUS_FAILED     = 'bunny_stream_failed';

    public function get_id() { return 'bunny_stream'; }
    public function get_label() { return __( 'Bunny Stream', 'videohub360-studio' ); }
    public function is_available() {
        $last = sanitize_key( get_option( 'vh360_studio_bunny_stream_last_status', '' ) );
        return (bool) apply_filters( 'vh360_studio_bunny_stream_available', '1' === get_option( 'vh360_studio_bunny_stream_enabled', '0' ) && $this->client()->has_credentials() && ( '' === $last || 'success' === $last ), $this );
    }
    public function supports_publish() { return $this->is_available() && current_user_can( 'upload_files' ); }

    public function prepare_publish( array $job, array $recording ) {
        if ( 'bunny_stream' !== sanitize_key( $job['storage_provider'] ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_wrong_provider', __( 'This recording job is not configured for this replay storage method.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_bunny_stream_invalid_status', __( 'This replay storage method requires a processing job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_upload_forbidden', __( 'Replay publishing requires permission to upload media.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }
        if ( '1' !== get_option( 'vh360_studio_bunny_stream_enabled', '0' ) || ! $this->client()->has_credentials() ) {
            return new WP_Error( 'vh360_studio_bunny_stream_credentials_missing', __( 'Cloud replay credentials are required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( empty( $recording['path'] ) || ! file_exists( $recording['path'] ) || ! is_file( $recording['path'] ) || ! is_readable( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_missing_file', __( 'Cloud upload file is unavailable.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        $chunks = new VH360_Studio_Recording_Chunks( new VH360_Studio_Recording_Jobs( VH360_Studio_Plugin::instance()->registry() ) );
        if ( ! $chunks->is_path_in_base_directory( $recording['path'] ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_invalid_path', __( 'The validated recording path is not allowed for replay publishing.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! in_array( $this->base_mime_type( $recording['mime_type'] ), array( 'video/mp4', 'video/webm' ), true ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_invalid_type', __( 'Replay publishing supports MP4 and WebM recordings only.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        if ( empty( $recording['file_size'] ) || 0 >= absint( $recording['file_size'] ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_empty_file', __( 'Replay publishing requires a non-empty recording file.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'prepared', 'supports_publish'=>$this->supports_publish(), 'message'=>__( 'Cloud replay storage is configured and ready.', 'videohub360-studio' ) );
    }

    public function publish_recording( array $job, array $recording ) {
        $prepared = $this->prepare_publish( $job, $recording );
        if ( is_wp_error( $prepared ) ) { return $prepared; }
        $client = $this->client();
        $created = $client->create_video( $this->title( $job ) );
        if ( is_wp_error( $created ) ) { return new WP_Error( 'vh360_studio_bunny_stream_create_failed', $created->get_error_message(), $created->get_error_data() ); }
        $video_id = ! empty( $created['guid'] ) ? sanitize_text_field( $created['guid'] ) : '';
        if ( ! $video_id ) { return new WP_Error( 'vh360_studio_bunny_stream_create_failed', __( 'Cloud replay storage returned an invalid response.', 'videohub360-studio' ), array( 'status' => 502 ) ); }
        $uploaded = $client->upload_video( $video_id, $recording['path'], $recording['mime_type'] );
        if ( is_wp_error( $uploaded ) ) { return new WP_Error( 'vh360_studio_bunny_stream_upload_failed', sprintf( __( 'Cloud replay storage rejected the upload: %s', 'videohub360-studio' ), $uploaded->get_error_message() ), $uploaded->get_error_data() ); }
        $video = $client->get_video( $video_id );
        if ( is_wp_error( $video ) ) { $video = $created; }
        return $this->result_from_video( is_array( $video ) ? $video : $created, $recording, __( 'Recording uploaded to cloud replay storage.', 'videohub360-studio' ), $video_id );
    }

    public function get_publish_status( array $job ) {
        $video_id = ! empty( $job['provider_file_id'] ) ? sanitize_text_field( $job['provider_file_id'] ) : '';
        if ( ! $video_id && ! empty( $job['provider_metadata'] ) ) {
            $meta = json_decode( (string) $job['provider_metadata'], true );
            $video_id = ! empty( $meta['bunny_video_id'] ) ? sanitize_text_field( $meta['bunny_video_id'] ) : '';
        }
        if ( ! $video_id ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>'pending', 'supports_publish'=>$this->supports_publish(), 'message'=>__( 'Cloud replay file has not been uploaded yet.', 'videohub360-studio' ) );
        }
        $video = $this->client()->get_video( $video_id );
        if ( is_wp_error( $video ) ) {
            return array( 'provider_id'=>$this->get_id(), 'provider_label'=>$this->get_label(), 'status'=>self::STATUS_FAILED, 'provider_status'=>self::STATUS_FAILED, 'provider_file_id'=>$video_id, 'message'=>$video->get_error_message() );
        }
        return $this->result_from_video( $video, array(), __( 'Cloud replay status checked.', 'videohub360-studio' ), $video_id );
    }

    public function test_connection() { return $this->client()->list_videos( 1, 1 ); }

    private function result_from_video( array $video, array $recording, $message, $fallback_id = '' ) {
        $video_id = ! empty( $video['guid'] ) ? sanitize_text_field( $video['guid'] ) : sanitize_text_field( $fallback_id );
        $status   = $this->map_status( $video );
        $embed    = $this->client()->embed_url( $video_id );
        $poster   = $this->client()->thumbnail_url_from_video( $video );
        $library  = sanitize_text_field( get_option( 'vh360_studio_bunny_stream_library_id', '' ) );
        return array(
            'provider_id'      => $this->get_id(), 'provider_label' => $this->get_label(), 'status' => $status, 'provider_status' => $status,
            'provider_file_id' => $video_id, 'bunny_video_id' => $video_id, 'bunny_library_id' => $library,
            'playback_url'     => $embed, 'embed_url' => $embed, 'poster_url' => $poster,
            'file_size'        => ! empty( $recording['file_size'] ) ? absint( $recording['file_size'] ) : 0,
            'mime_type'        => ! empty( $recording['mime_type'] ) ? sanitize_mime_type( $recording['mime_type'] ) : '',
            'title'            => ! empty( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '',
            'supports_publish' => $this->supports_publish(),
            'message'          => self::STATUS_READY === $status ? __( 'Replay published.', 'videohub360-studio' ) : ( self::STATUS_FAILED === $status ? __( 'Cloud upload failed.', 'videohub360-studio' ) : $message ),
        );
    }

    private function map_status( array $video ) {
        $raw = isset( $video['status'] ) ? $video['status'] : ( isset( $video['encodeProgress'] ) ? $video['encodeProgress'] : '' );
        $value = is_numeric( $raw ) ? (int) $raw : strtolower( sanitize_key( (string) $raw ) );
        if ( in_array( $value, array( 4, 'finished', 'ready', 'published' ), true ) ) { return self::STATUS_READY; }
        if ( in_array( $value, array( 5, 6, 'error', 'failed', 'upload_failed', 'upload-failed' ), true ) ) { return self::STATUS_FAILED; }
        return self::STATUS_PROCESSING;
    }

    private function client() { return new VH360_Studio_Bunny_Stream_Client(); }
    private function title( array $job ) { return ! empty( $job['live_video_id'] ) && get_the_title( absint( $job['live_video_id'] ) ) ? wp_strip_all_tags( get_the_title( absint( $job['live_video_id'] ) ) ) : sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) ); }
    private function base_mime_type( $mime_type ) { $parts = explode( ';', (string) $mime_type ); return strtolower( sanitize_mime_type( trim( $parts[0] ) ) ); }
}
