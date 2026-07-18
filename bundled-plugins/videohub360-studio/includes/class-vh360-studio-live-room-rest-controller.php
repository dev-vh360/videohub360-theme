<?php
/**
 * Live Room recording REST endpoints.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VH360_Studio_Live_Room_REST_Controller {
    private $namespace = 'vh360-studio/v1';
    private $jobs;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) { $this->jobs = $jobs; }

    public function register_routes() {
        register_rest_route( $this->namespace, '/live-rooms/(?P<post_id>\d+)/recordings', array(
            'methods' => 'POST', 'callback' => array( $this, 'create_recording' ), 'permission_callback' => array( $this, 'can_record' ),
        ) );
        register_rest_route( $this->namespace, '/live-rooms/(?P<post_id>\d+)/recording', array(
            'methods' => 'GET', 'callback' => array( $this, 'get_recording_state' ), 'permission_callback' => '__return_true',
        ) );
    }

    public function can_record( WP_REST_Request $request ) { return VH360_Studio_Permissions::current_user_can_record_live_room( absint( $request['post_id'] ) ); }

    public function get_recording_state( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        $purpose = $this->recording_purpose( $post_id );
        $job = 'appointment_session' === $purpose ? $this->jobs->find_active_appointment_recording( $post_id ) : $this->jobs->find_active_live_room_job( $post_id );
        return rest_ensure_response( array(
            'post_id' => $post_id,
            'recording_purpose' => $purpose,
            'active' => (bool) $job,
            'state' => $job ? sanitize_key( $job['status'] ) : 'idle',
            'started_at' => $job && ! empty( $job['started_at'] ) ? $job['started_at'] : get_post_meta( $post_id, 'appointment_session' === $purpose ? '_vh360_appointment_recording_started_at' : '_vh360_live_room_recording_started_at', true ),
        ) );
    }

    public function create_recording( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        $purpose = $this->recording_purpose( $post_id );
        $lock = 'vh360_recording_create_lock_' . $post_id;
        $now = time();
        $locked = add_option( $lock, $now, '', 'no' );
        if ( ! $locked ) {
            $created = absint( get_option( $lock ) );
            if ( $created && ( $now - $created ) > 120 ) { delete_option( $lock ); $locked = add_option( $lock, $now, '', 'no' ); }
        }
        if ( ! $locked ) { return new WP_Error( 'vh360_recording_creation_locked', __( 'A recording is already being started for this room.', 'videohub360-studio' ), array( 'status' => 409 ) ); }

        try {
            $active = 'appointment_session' === $purpose ? $this->jobs->find_active_appointment_recording( $post_id ) : $this->jobs->find_active_live_room_job( $post_id );
            if ( $active ) { return new WP_Error( 'vh360_recording_already_active', __( 'This room already has an active recording.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
            $channel = get_post_meta( $post_id, '_vh360_agora_channel_name', true );
            if ( ! $channel ) { $channel = 'live-room-' . $post_id; }
            if ( 'appointment_session' === $purpose ) {
                $session = wp_generate_uuid4();
                $job = $this->jobs->create( get_current_user_id(), array( 'source_type' => 'appointment_session', 'source_id' => 'appointment-' . $post_id . '-' . $session, 'live_video_id' => $post_id, 'room_id' => $channel, 'recording_mode' => 'local_private', 'quality_preset' => VH360_Studio_Quality_Presets::DEFAULT_PRESET, 'storage_provider' => VH360_Studio_Replay_Storage_Settings::resolve_default_provider() ) );
                update_post_meta( $post_id, '_vh360_appointment_recording_state', 'recording' );
                update_post_meta( $post_id, '_vh360_appointment_recording_started_at', current_time( 'mysql' ) );
                update_post_meta( $post_id, '_vh360_appointment_recording_user_id', get_current_user_id() );
                return rest_ensure_response( array( 'recording_purpose' => 'appointment_session', 'publishing_mode' => 'local_private', 'post_id' => $post_id, 'appointment_event_id' => get_post_meta( $post_id, '_vh360_appointment_event_id', true ), 'quality_preset' => VH360_Studio_Quality_Presets::DEFAULT_PRESET, 'session_token' => wp_create_nonce( 'vh360_appointment_recording_' . $post_id . '_' . get_current_user_id() . '_' . $session ), 'started_by' => get_current_user_id(), 'job' => is_wp_error( $job ) ? null : $job ) );
            }
            $job = $this->jobs->create( get_current_user_id(), array( 'source_type' => 'live_room', 'source_id' => 'live-room-' . $post_id . '-' . wp_generate_uuid4(), 'live_video_id' => $post_id, 'room_id' => $channel, 'recording_mode' => 'browser', 'quality_preset' => VH360_Studio_Quality_Presets::DEFAULT_PRESET, 'storage_provider' => VH360_Studio_Replay_Storage_Settings::resolve_default_provider() ) );
            if ( is_wp_error( $job ) ) { return $job; }
            update_post_meta( $post_id, '_vh360_live_room_recording_state', 'created' ); update_post_meta( $post_id, '_vh360_live_room_recording_job_id', absint( $job['id'] ) ); update_post_meta( $post_id, '_vh360_live_room_recording_started_at', current_time( 'mysql' ) ); update_post_meta( $post_id, '_vh360_live_room_recording_user_id', get_current_user_id() );
            return rest_ensure_response( array( 'recording_purpose' => 'ordinary_live_room', 'publishing_mode' => 'provider_replay', 'job' => $job ) );
        } finally { delete_option( $lock ); }
    }

    private function recording_purpose( $post_id ) { return '' !== (string) get_post_meta( $post_id, '_vh360_appointment_event_id', true ) ? 'appointment_session' : 'ordinary_live_room'; }
}
