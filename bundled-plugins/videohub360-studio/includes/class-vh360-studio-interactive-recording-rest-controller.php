<?php
/**
 * Studio-controlled livestream recording REST endpoints.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Interactive_Recording_REST_Controller {
    private $namespace = 'vh360-studio/v1';
    private $jobs;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs = $jobs;
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/broadcasts/(?P<video_id>\d+)/recordings', array(
            'methods' => 'POST', 'callback' => array( $this, 'create_recording' ), 'permission_callback' => array( $this, 'can_record' ),
            'args' => array( 'capture_scope' => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ) ),
        ) );
        register_rest_route( $this->namespace, '/broadcasts/(?P<video_id>\d+)/recording', array( 'methods' => 'GET', 'callback' => array( $this, 'get_recording_state' ), 'permission_callback' => array( $this, 'can_view_state' ) ) );
        register_rest_route( $this->namespace, '/broadcasts/(?P<video_id>\d+)/recordings/(?P<id>\d+)/heartbeat', array( 'methods' => 'POST', 'callback' => array( $this, 'heartbeat_recording' ), 'permission_callback' => array( $this, 'can_record' ) ) );
        register_rest_route( $this->namespace, '/broadcasts/(?P<video_id>\d+)/recordings/(?P<id>\d+)/recover', array( 'methods' => 'POST', 'callback' => array( $this, 'recover_interrupted_recording' ), 'permission_callback' => array( $this, 'can_record' ) ) );
        register_rest_route( $this->namespace, '/broadcasts/(?P<video_id>\d+)/recording/stop-request', array( 'methods' => 'POST', 'callback' => array( $this, 'request_stop' ), 'permission_callback' => array( $this, 'can_record' ) ) );
    }

    public function can_record( WP_REST_Request $request ) {
        $video_id = absint( $request['video_id'] );
        if ( 'program' === sanitize_key( $request->get_param( 'capture_scope' ) ) ) {
            $post = get_post( $video_id );
            $user_id = get_current_user_id();
            return $user_id && VH360_Studio_Permissions::user_can_access_studio( $user_id ) && VH360_Studio_Permissions::license_is_valid() && $post && 'videohub360' === $post->post_type && 'yes' === get_post_meta( $video_id, '_vh360_studio_controlled_live', true ) && ( absint( $post->post_author ) === $user_id || current_user_can( 'edit_post', $video_id ) || current_user_can( 'manage_options' ) );
        }
        return VH360_Studio_Permissions::current_user_can_record_studio_interactive_livestream( $video_id );
    }

    public function can_view_state( WP_REST_Request $request ) {
        $video_id = absint( $request['video_id'] );
        return $video_id && 'videohub360' === get_post_type( $video_id ) ? true : new WP_Error( 'vh360_studio_recording_state_not_found', __( 'Recording state not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
    }

    public function create_recording( WP_REST_Request $request ) {
        $video_id = absint( $request['video_id'] );
        $scope = sanitize_key( $request->get_param( 'capture_scope' ) );
        if ( ! in_array( $scope, $this->jobs->allowed_capture_scopes(), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_capture_scope', __( 'Invalid recording capture scope.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $valid = $this->validate_stream_ready( $video_id, $scope );
        if ( is_wp_error( $valid ) ) { return $valid; }

        $lock = 'vh360_studio_livestream_recording_create_lock_' . $video_id;
        $now = time();
        $locked = add_option( $lock, $now, '', 'no' );
        if ( ! $locked && $now - absint( get_option( $lock ) ) > 120 ) { delete_option( $lock ); $locked = add_option( $lock, $now, '', 'no' ); }
        if ( ! $locked ) { return new WP_Error( 'vh360_studio_recording_creation_locked', __( 'A livestream recording is already being started.', 'videohub360-studio' ), array( 'status' => 409 ) ); }

        try {
            $active = $this->jobs->find_active_livestream_job( $video_id );
            if ( $active ) { return $this->conflict_response( $active ); }
            $latest = $this->jobs->find_latest_livestream_job( $video_id );
            if ( 'yes' === get_post_meta( $video_id, '_vh360_studio_replay_ready', true ) || ( $latest && VH360_Studio_Recording_Jobs::STATUS_READY === sanitize_key( $latest['status'] ) ) ) {
                return new WP_Error( 'vh360_studio_recording_replay_exists', __( 'This livestream already has a canonical replay.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }
            $channel = get_post_meta( $video_id, '_vh360_agora_channel_name', true );
            $preset = VH360_Studio_Quality_Presets::normalize( get_post_meta( $video_id, '_vh360_studio_quality_preset', true ) ?: VH360_Studio_Quality_Presets::DEFAULT_PRESET );
            $provider = sanitize_key( get_post_meta( $video_id, '_vh360_studio_replay_storage_provider', true ) ?: VH360_Studio_Replay_Storage_Settings::resolve_default_provider() );
            $job = $this->jobs->create( get_current_user_id(), array( 'source_type' => 'livestream_video', 'source_id' => 'videohub360-' . $video_id, 'live_video_id' => $video_id, 'room_id' => $channel, 'recording_mode' => 'browser', 'capture_scope' => $scope, 'quality_preset' => $preset, 'storage_provider' => $provider ) );
            if ( is_wp_error( $job ) ) { return $job; }
            update_post_meta( $video_id, '_vh360_studio_recording_state', 'created' );
            update_post_meta( $video_id, '_vh360_studio_job_id', absint( $job['id'] ) );
            update_post_meta( $video_id, '_vh360_studio_replay_pending', 'yes' );
            update_post_meta( $video_id, '_vh360_studio_replay_ready', 'no' );
            update_post_meta( $video_id, '_vh360_studio_replay_failed', 'no' );
            update_post_meta( $video_id, '_vh360_studio_replay_status', 'created' );
            delete_post_meta( $video_id, '_vh360_studio_recording_stop_requested' );
            update_option( 'vh360_recording_heartbeat_' . absint( $job['id'] ), time(), false );
            return rest_ensure_response( array( 'recording_purpose' => 'studio_interactive', 'capture_scope' => $scope, 'publishing_mode' => 'provider_replay', 'job' => $this->prepare_job( $job ) ) );
        } finally { delete_option( $lock ); }
    }

    public function get_recording_state( WP_REST_Request $request ) {
        $video_id = absint( $request['video_id'] );
        $job      = $this->recording_job_for_state( $video_id );
        $state    = $job ? sanitize_key( $job['status'] ) : 'idle';
        $active   = $job && in_array( $state, $this->jobs->active_statuses(), true );
        $fresh    = $active ? $this->heartbeat_is_fresh( absint( $job['id'] ) ) : false;
        $can_manage = VH360_Studio_Permissions::current_user_can_record_studio_interactive_livestream( $video_id );

        $can_start_new_recording = 'yes' === get_post_meta( $video_id, '_vh360_studio_controlled_live', true )
            && 'agora' === get_post_meta( $video_id, '_vh360_type', true )
            && 'interactive' === get_post_meta( $video_id, '_vh360_agora_mode', true )
            && 'yes' === get_post_meta( $video_id, '_vh360_is_live', true )
            && 'yes' === get_post_meta( $video_id, '_vh360_agora_stream_live', true )
            && 'yes' !== get_post_meta( $video_id, '_vh360_stream_stopped', true );

        $response = array(
            'active'                  => (bool) $active,
            'state'                   => $state,
            'recording_active'        => $active && 'recording' === $state && $fresh,
            'replay_processing'       => $active && in_array( $state, array( 'stopping', 'uploading', 'processing' ), true ),
            'capture_scope'           => $job && ! empty( $job['capture_scope'] ) ? sanitize_key( $job['capture_scope'] ) : '',
            'can_start_new_recording' => (bool) $can_start_new_recording,
            'replay_pending'          => 'yes' === get_post_meta( $video_id, '_vh360_studio_replay_pending', true ),
            'replay_ready'            => 'yes' === get_post_meta( $video_id, '_vh360_studio_replay_ready', true ) || 'ready' === $state,
            'replay_failed'           => 'yes' === get_post_meta( $video_id, '_vh360_studio_replay_failed', true ) || 'failed' === $state,
        );

        if ( $can_manage ) {
            $failure_stage = sanitize_key( get_post_meta( $video_id, '_vh360_studio_replay_status', true ) );
            $response += array(
                'job_id'                 => $job ? absint( $job['id'] ) : 0,
                'started_at'              => $job && ! empty( $job['started_at'] ) ? $job['started_at'] : '',
                'started_by'              => $job ? absint( $job['user_id'] ) : 0,
                'heartbeat_fresh'         => (bool) $fresh,
                'owned_by_current_user'   => $job && absint( $job['user_id'] ) === get_current_user_id(),
                'recovery_available'      => (bool) ( $active && $job && $this->job_is_recoverable( $job ) && $this->heartbeat_is_stale( absint( $job['id'] ) ) ),
                'stop_requested'          => 'yes' === get_post_meta( $video_id, '_vh360_studio_recording_stop_requested', true ),
                'failure_stage'           => $failure_stage,
                'retryable'               => in_array( $failure_stage, array( 'finalization_failed', 'publishing_prepare_failed', 'publishing_start_failed' ), true ),
                'error_message'           => $job && ! empty( $job['error_message'] ) ? $job['error_message'] : '',
            );
        }

        return rest_ensure_response( $response );
    }

    public function heartbeat_recording( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        if ( ! $job || absint( $job['live_video_id'] ) !== absint( $request['video_id'] ) || 'livestream_video' !== sanitize_key( $job['source_type'] ) ) { return new WP_Error( 'vh360_studio_recording_heartbeat_not_found', __( 'Recording session not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        update_option( 'vh360_recording_heartbeat_' . absint( $job['id'] ), time(), false );
        $job = $this->jobs->touch( $job['id'], get_current_user_id() );
        return is_wp_error( $job ) ? $job : rest_ensure_response( array( 'job_id' => absint( $job['id'] ), 'updated_at' => $job['updated_at'], 'stop_requested' => 'yes' === get_post_meta( absint( $request['video_id'] ), '_vh360_studio_recording_stop_requested', true ) ) );
    }

    public function recover_interrupted_recording( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), 0 );
        $video_id = absint( $request['video_id'] );
        if ( ! $job || absint( $job['live_video_id'] ) !== $video_id || 'livestream_video' !== sanitize_key( $job['source_type'] ) ) { return new WP_Error( 'vh360_studio_interrupted_recording_not_found', __( 'Interrupted recording session not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( ! $this->job_is_recoverable( $job ) ) { return new WP_Error( 'vh360_studio_recording_not_recoverable', __( 'This recording is no longer in a browser-recoverable state.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        if ( $this->heartbeat_is_fresh( absint( $job['id'] ) ) ) { return new WP_Error( 'vh360_studio_recording_still_active', __( 'This recording is still active in another browser tab.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        $closed = $this->jobs->cancel( absint( $job['id'] ), 0 );
        if ( is_wp_error( $closed ) ) { return $closed; }
        delete_option( 'vh360_recording_heartbeat_' . absint( $job['id'] ) );
        update_post_meta( $video_id, '_vh360_studio_recording_state', 'cancelled' );
        update_post_meta( $video_id, '_vh360_studio_replay_pending', 'no' );
        update_post_meta( $video_id, '_vh360_studio_replay_ready', 'no' );
        update_post_meta( $video_id, '_vh360_studio_replay_failed', 'no' );
        update_post_meta( $video_id, '_vh360_studio_replay_status', 'cancelled' );
        delete_post_meta( $video_id, '_vh360_studio_recording_stop_requested' );
        return rest_ensure_response( array( 'job_id' => absint( $job['id'] ), 'state' => 'cancelled' ) );
    }

    public function request_stop( WP_REST_Request $request ) {
        $video_id = absint( $request['video_id'] );
        $job = $this->jobs->find_active_livestream_job( $video_id );
        if ( ! $job || 'interactive_composite' !== sanitize_key( $job['capture_scope'] ) || ! in_array( sanitize_key( $job['status'] ), array( 'created', 'recording' ), true ) ) {
            return new WP_Error( 'vh360_studio_recording_stop_not_available', __( 'There is no active Viewer recording to stop.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! $this->heartbeat_is_fresh( absint( $job['id'] ) ) ) {
            return new WP_Error( 'vh360_studio_recording_stop_not_fresh', __( 'The Viewer recording is not currently sending a fresh heartbeat.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        update_post_meta( $video_id, '_vh360_studio_recording_stop_requested', 'yes' );
        return rest_ensure_response( array( 'stop_requested' => true, 'job_id' => absint( $job['id'] ) ) );
    }

    private function validate_stream_ready( $video_id, $scope ) {
        if ( ! $video_id || 'videohub360' !== get_post_type( $video_id ) ) { return new WP_Error( 'vh360_studio_livestream_not_found', __( 'Livestream not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( 'program' === $scope ) {
            foreach ( array( '_vh360_studio_controlled_live' => 'yes', '_vh360_type' => 'agora', '_vh360_is_live' => 'yes', '_vh360_agora_stream_live' => 'yes' ) as $key => $expected ) { if ( $expected !== get_post_meta( $video_id, $key, true ) ) { return new WP_Error( 'vh360_studio_program_recording_not_live', __( 'Program recording is only available for an active Studio livestream.', 'videohub360-studio' ), array( 'status' => 409 ) ); } }
            if ( 'yes' === get_post_meta( $video_id, '_vh360_stream_stopped', true ) || ! get_post_meta( $video_id, '_vh360_agora_channel_name', true ) ) { return new WP_Error( 'vh360_studio_program_recording_not_live', __( 'Program recording is only available for an active Studio livestream.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
            return true;
        }
        foreach ( array( '_vh360_studio_controlled_live' => 'yes', '_vh360_type' => 'agora', '_vh360_agora_mode' => 'interactive', '_vh360_is_live' => 'yes', '_vh360_agora_stream_live' => 'yes' ) as $key => $expected ) { if ( $expected !== get_post_meta( $video_id, $key, true ) ) { return new WP_Error( 'vh360_studio_interactive_recording_not_allowed', __( 'Interactive session recording is only available for active Studio interactive livestreams.', 'videohub360-studio' ), array( 'status' => 409 ) ); } }
        if ( 'yes' === get_post_meta( $video_id, '_vh360_stream_stopped', true ) || '' !== (string) get_post_meta( $video_id, '_vh360_appointment_event_id', true ) ) { return new WP_Error( 'vh360_studio_interactive_recording_not_allowed', __( 'Interactive session recording is not available for this stream.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        if ( ! get_post_meta( $video_id, '_vh360_agora_channel_name', true ) ) { return new WP_Error( 'vh360_studio_livestream_missing_channel', __( 'The livestream does not have an Agora channel configured.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        return true;
    }

    private function conflict_response( array $job ) {
        return new WP_Error( 'vh360_studio_recording_already_active', __( 'This livestream already has an active recording.', 'videohub360-studio' ), array( 'status' => 409, 'capture_scope' => ! empty( $job['capture_scope'] ) ? sanitize_key( $job['capture_scope'] ) : '', 'state' => sanitize_key( $job['status'] ), 'job_id' => absint( $job['id'] ) ) );
    }

    /**
     * Resolve the job that should describe the livestream recording state.
     *
     * Active capture/upload jobs take priority. Once browser capture has ended,
     * retain the latest job so failed finalization/publishing can be retried and
     * ready/failed capture scope remains visible to Studio.
     *
     * @param int $video_id Livestream post ID.
     * @return array|null
     */
    private function recording_job_for_state( $video_id ) {
        $job = $this->jobs->find_active_livestream_job( $video_id );
        if ( $job ) {
            return $job;
        }

        $saved_job_id = absint( get_post_meta( $video_id, '_vh360_studio_job_id', true ) );
        if ( $saved_job_id ) {
            $saved = $this->jobs->get( $saved_job_id, 0 );
            if ( $saved && 'livestream_video' === sanitize_key( $saved['source_type'] ) && absint( $saved['live_video_id'] ) === absint( $video_id ) ) {
                return $saved;
            }
        }

        return $this->jobs->find_latest_livestream_job( $video_id );
    }

    private function heartbeat_is_fresh( $job_id ) { $beat = absint( get_option( 'vh360_recording_heartbeat_' . absint( $job_id ) ) ); return $beat && ( time() - $beat ) <= 90; }
    private function heartbeat_is_stale( $job_id ) { $beat = absint( get_option( 'vh360_recording_heartbeat_' . absint( $job_id ) ) ); return ! $beat || ( time() - $beat ) > 90; }

    private function job_is_recoverable( array $job ) {
        $status = sanitize_key( $job['status'] );
        if ( in_array( $status, array( 'created', 'recording' ), true ) ) {
            return true;
        }
        if ( 'stopping' !== $status ) {
            return false;
        }

        $video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $failure_stage = $video_id ? sanitize_key( get_post_meta( $video_id, '_vh360_studio_replay_status', true ) ) : '';

        // A stopping job with uploaded chunks and a retryable server-side
        // finalization/publishing failure must be preserved, not cleared as an
        // abandoned browser capture.
        return ! in_array( $failure_stage, array( 'finalization_failed', 'publishing_prepare_failed', 'publishing_start_failed' ), true );
    }

    private function prepare_job( array $job ) { unset( $job['local_temp_path'] ); return $job; }
}
