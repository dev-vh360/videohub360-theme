<?php
/**
 * Live Room recording REST endpoints.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Live_Room_REST_Controller {
    private $namespace = 'vh360-studio/v1';
    private $jobs;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs = $jobs;
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/live-rooms/(?P<post_id>\d+)/recordings',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_recording' ),
                'permission_callback' => array( $this, 'can_record' ),
            )
        );
        register_rest_route(
            $this->namespace,
            '/live-rooms/(?P<post_id>\d+)/recording',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_recording_state' ),
                'permission_callback' => array( $this, 'can_view_recording_state' ),
            )
        );
        register_rest_route(
            $this->namespace,
            '/live-rooms/(?P<post_id>\d+)/recordings/(?P<id>\d+)/local-private',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'update_local_private_recording' ),
                'permission_callback' => array( $this, 'can_record' ),
            )
        );
    }

    public function can_record( WP_REST_Request $request ) {
        return VH360_Studio_Permissions::current_user_can_record_live_room( absint( $request['post_id'] ) );
    }

    public function can_view_recording_state( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        if ( ! $post_id || 'videohub360' !== get_post_type( $post_id ) ) {
            return new WP_Error( 'vh360_recording_state_not_found', __( 'Recording state not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        if ( 'appointment_session' === $this->recording_purpose( $post_id ) ) {
            $user_id = get_current_user_id();
            if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $post_id ) || VH360_Studio_Permissions::current_user_can_record_live_room( $post_id ) ) {
                return true;
            }
            if ( $user_id && function_exists( 'vh360_can_user_view_appointment_page' ) && vh360_can_user_view_appointment_page( $post_id, $user_id ) ) {
                return true;
            }
            return new WP_Error( 'vh360_recording_state_not_found', __( 'Recording state not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        if ( VH360_Studio_Permissions::current_user_can_record_live_room( $post_id ) || $this->current_user_can_view_room_state( $post_id ) ) {
            return true;
        }
        return new WP_Error( 'vh360_recording_state_forbidden', __( 'You are not allowed to view this recording state.', 'videohub360-studio' ), array( 'status' => 403 ) );
    }

    public function get_recording_state( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        $purpose = $this->recording_purpose( $post_id );
        $job     = 'appointment_session' === $purpose ? $this->jobs->find_active_appointment_recording( $post_id ) : $this->jobs->find_active_live_room_job( $post_id );

        $state       = $job ? sanitize_key( $job['status'] ) : 'idle';
        $can_manage  = VH360_Studio_Permissions::current_user_can_record_live_room( $post_id ) || current_user_can( 'edit_post', $post_id ) || current_user_can( 'manage_options' );
        $response = array(
            'post_id'             => $post_id,
            'recording_purpose'   => $purpose,
            'active'              => (bool) $job,
            'job_active'          => (bool) $job,
            'recording_active'    => in_array( $state, array( 'created', 'recording', 'stopping' ), true ),
            'replay_processing'   => in_array( $state, array( 'uploading', 'processing' ), true ),
            'state'               => $state,
            'started_at'          => $job && ! empty( $job['started_at'] ) ? $job['started_at'] : get_post_meta( $post_id, 'appointment_session' === $purpose ? '_vh360_appointment_recording_started_at' : '_vh360_live_room_recording_started_at', true ),
        );

        if ( $can_manage ) {
            $response['job_id']             = $job ? absint( $job['id'] ) : 0;
            $response['recovery_available'] = (bool) $job;
        }


        if ( 'ordinary_live_room' === $purpose ) {
            $response['replay_pending'] = 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_pending', true );
            $response['replay_ready']   = 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_ready', true ) || 'yes' === get_post_meta( $post_id, '_vh360_live_room_has_replay', true );
            $response['replay_failed']  = 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_failed', true );
        }

        return rest_ensure_response( $response );
    }

    public function create_recording( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        $purpose = $this->recording_purpose( $post_id );
        $valid   = $this->validate_room_ready_for_recording( $post_id, $purpose );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $lock   = 'vh360_recording_create_lock_' . $post_id;
        $now    = time();
        $locked = add_option( $lock, $now, '', 'no' );
        if ( ! $locked ) {
            $created = absint( get_option( $lock ) );
            if ( $created && ( $now - $created ) > 120 ) {
                delete_option( $lock );
                $locked = add_option( $lock, $now, '', 'no' );
            }
        }
        if ( ! $locked ) {
            return new WP_Error( 'vh360_recording_creation_locked', __( 'A recording is already being started for this room.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }

        try {
            $active = 'appointment_session' === $purpose ? $this->jobs->find_active_appointment_recording( $post_id ) : $this->jobs->find_active_live_room_job( $post_id );
            if ( $active ) {
                return new WP_Error( 'vh360_recording_already_active', __( 'This room already has an active recording.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }

            $channel = get_post_meta( $post_id, '_vh360_agora_channel_name', true );
            if ( 'appointment_session' === $purpose ) {
                return $this->create_appointment_session( $post_id, $channel );
            }

            return $this->create_ordinary_live_room_job( $post_id, $channel );
        } finally {
            delete_option( $lock );
        }
    }

    public function update_local_private_recording( WP_REST_Request $request ) {
        $post_id = absint( $request['post_id'] );
        $job     = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        if ( ! $job || 'appointment_session' !== sanitize_key( $job['source_type'] ) || absint( $job['live_video_id'] ) !== $post_id || 'local_private' !== sanitize_key( $job['recording_mode'] ) ) {
            return new WP_Error( 'vh360_private_recording_not_found', __( 'Private appointment recording session not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        $state    = sanitize_key( $request->get_param( 'state' ) );
        $duration = absint( $request->get_param( 'duration_seconds' ) );
        $mime     = sanitize_mime_type( $request->get_param( 'mime_type' ) );
        if ( 'preparing_download' === $state ) {
            if ( VH360_Studio_Recording_Jobs::STATUS_RECORDING === $job['status'] ) {
                $job = $this->jobs->mark_stopping( $job['id'], get_current_user_id(), $duration );
                if ( is_wp_error( $job ) ) {
                    return $job;
                }
            }
            $job = $this->jobs->mark_preparing_download( $job['id'], get_current_user_id(), $duration, $mime );
        } elseif ( 'download_ready' === $state ) {
            $job = $this->jobs->mark_local_private_ready( $job['id'], get_current_user_id(), $duration, $mime );
        } elseif ( 'failed' === $state ) {
            $job = $this->jobs->mark_failed( $job['id'], get_current_user_id(), sanitize_textarea_field( $request->get_param( 'error_message' ) ) );
        } else {
            return new WP_Error( 'vh360_private_recording_invalid_state', __( 'Invalid private appointment recording state.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        if ( is_wp_error( $job ) ) {
            return $job;
        }

        if ( in_array( $state, array( 'download_ready', 'failed' ), true ) ) {
            $this->clear_appointment_recording_state( $post_id );
        }

        return rest_ensure_response( array( 'job_id' => absint( $job['id'] ), 'state' => sanitize_key( $job['status'] ) ) );
    }


    private function current_user_can_view_room_state( $post_id ) {
        $user_id = get_current_user_id();
        if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }
        $can_view = ( 'publish' === get_post_status( $post_id ) && ! post_password_required( $post_id ) );
        if ( $can_view && function_exists( 'videohub360_course_features_enabled' ) && function_exists( 'videohub360_user_can_access_lesson' ) && videohub360_course_features_enabled() ) {
            $can_view = videohub360_user_can_access_lesson( $post_id, $user_id );
        }
        if ( $can_view && function_exists( 'vh360_post_requires_membership' ) ) {
            $required_plan = vh360_post_requires_membership( $post_id );
            if ( $required_plan ) {
                $can_view = $user_id && ( 'any' === $required_plan ? ( function_exists( 'vh360_user_has_active_membership' ) && vh360_user_has_active_membership( $user_id ) ) : ( function_exists( 'vh360_user_has_membership_plan' ) && vh360_user_has_membership_plan( $user_id, $required_plan ) ) );
            }
        }
        return (bool) apply_filters( 'vh360_live_room_recording_state_can_view', $can_view, $post_id, $user_id );
    }

    private function create_appointment_session( $post_id, $channel ) {
        $session = wp_generate_uuid4();
        $job     = $this->jobs->create(
            get_current_user_id(),
            array(
                'source_type'      => 'appointment_session',
                'source_id'        => 'appointment-' . $post_id . '-' . $session,
                'live_video_id'    => $post_id,
                'room_id'          => $channel,
                'recording_mode'   => 'local_private',
                'quality_preset'   => VH360_Studio_Quality_Presets::DEFAULT_PRESET,
                'storage_provider' => VH360_Studio_Replay_Storage_Settings::resolve_default_provider(),
            )
        );
        if ( is_wp_error( $job ) ) {
            return $job;
        }
        $job = $this->jobs->start_recording( $job['id'], get_current_user_id(), $session, '' );
        if ( is_wp_error( $job ) ) {
            return $job;
        }

        update_post_meta( $post_id, '_vh360_appointment_recording_state', 'recording' );
        update_post_meta( $post_id, '_vh360_appointment_recording_started_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_vh360_appointment_recording_user_id', get_current_user_id() );

        return rest_ensure_response(
            array(
                'recording_purpose'   => 'appointment_session',
                'publishing_mode'      => 'local_private',
                'post_id'              => $post_id,
                'appointment_event_id' => get_post_meta( $post_id, '_vh360_appointment_event_id', true ),
                'quality_preset'       => VH360_Studio_Quality_Presets::DEFAULT_PRESET,
                'started_by'           => get_current_user_id(),
                'job'                  => $job,
            )
        );
    }

    private function create_ordinary_live_room_job( $post_id, $channel ) {
        $job = $this->jobs->create(
            get_current_user_id(),
            array(
                'source_type'      => 'live_room',
                'source_id'        => 'live-room-' . $post_id . '-' . wp_generate_uuid4(),
                'live_video_id'    => $post_id,
                'room_id'          => $channel,
                'recording_mode'   => 'browser',
                'quality_preset'   => VH360_Studio_Quality_Presets::DEFAULT_PRESET,
                'storage_provider' => VH360_Studio_Replay_Storage_Settings::resolve_default_provider(),
            )
        );
        if ( is_wp_error( $job ) ) {
            return $job;
        }

        update_post_meta( $post_id, '_vh360_live_room_recording_state', 'created' );
        update_post_meta( $post_id, '_vh360_live_room_recording_job_id', absint( $job['id'] ) );
        update_post_meta( $post_id, '_vh360_live_room_recording_started_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_vh360_live_room_recording_user_id', get_current_user_id() );
        update_post_meta( $post_id, '_vh360_studio_replay_pending', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_replay_ready', 'no' );
        update_post_meta( $post_id, '_vh360_studio_replay_failed', 'no' );
        update_post_meta( $post_id, '_vh360_studio_replay_status', 'created' );

        return rest_ensure_response( array( 'recording_purpose' => 'ordinary_live_room', 'publishing_mode' => 'provider_replay', 'job' => $job ) );
    }

    private function validate_room_ready_for_recording( $post_id, $purpose ) {
        if ( ! $post_id || 'videohub360' !== get_post_type( $post_id ) ) {
            return new WP_Error( 'vh360_recording_room_not_found', __( 'Live Room not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }
        if ( 'yes' !== get_post_meta( $post_id, '_vh360_is_live', true ) || 'yes' !== get_post_meta( $post_id, '_vh360_agora_stream_live', true ) || 'yes' === get_post_meta( $post_id, '_vh360_stream_stopped', true ) ) {
            return new WP_Error( 'vh360_recording_room_not_live', __( 'Recordings can only be started while the Live Room is active.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        if ( ! get_post_meta( $post_id, '_vh360_agora_channel_name', true ) ) {
            return new WP_Error( 'vh360_recording_room_missing_channel', __( 'The Live Room does not have an Agora channel configured.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( 'ordinary_live_room' === $purpose && ( 'yes' === get_post_meta( $post_id, '_vh360_live_room_has_replay', true ) || 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_ready', true ) ) ) {
            return new WP_Error( 'vh360_recording_room_replay_exists', __( 'This Live Room already has a published replay.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        return true;
    }

    private function clear_appointment_recording_state( $post_id ) {
        delete_post_meta( $post_id, '_vh360_appointment_recording_state' );
        delete_post_meta( $post_id, '_vh360_appointment_recording_started_at' );
        delete_post_meta( $post_id, '_vh360_appointment_recording_user_id' );
    }

    private function recording_purpose( $post_id ) {
        return '' !== (string) get_post_meta( $post_id, '_vh360_appointment_event_id', true ) ? 'appointment_session' : 'ordinary_live_room';
    }
}
