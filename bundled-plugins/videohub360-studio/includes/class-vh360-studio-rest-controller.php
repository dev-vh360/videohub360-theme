<?php
/**
 * Studio REST API controller.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_REST_Controller {
    private $jobs;
    private $chunks;
    private $validator;
    private $publisher;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs   = $jobs;
        $this->chunks = new VH360_Studio_Recording_Chunks( $jobs );
        $this->validator = new VH360_Studio_Recording_Validator( $this->chunks );
        $this->publisher = new VH360_Studio_Replay_Publisher( VH360_Studio_Plugin::instance()->registry(), $jobs, $this->validator, $this->chunks );
    }

    private function livestream_service() {
        return class_exists( 'VideoHub360_Livestream_Service' ) ? new VideoHub360_Livestream_Service() : null;
    }

    public function register_routes() {
        register_rest_route(
            'vh360-studio/v1',
            '/broadcasts',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_broadcast' ),
                'permission_callback' => array( $this, 'permissions_check' ),
            )
        );

        foreach ( array( 'prepare', 'started', 'heartbeat', 'end' ) as $broadcast_action ) {
            register_rest_route(
                'vh360-studio/v1',
                '/broadcasts/(?P<video_id>\d+)/' . $broadcast_action,
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'broadcast_' . $broadcast_action ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array( 'video_id' => $this->get_id_arg() ),
                )
            );
        }

        register_rest_route(
            'vh360-studio/v1',
            '/jobs',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_jobs' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => $this->get_list_args(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_job' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => $this->get_setup_create_args(),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_job' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array( 'id' => $this->get_id_arg() ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_job' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array_merge( array( 'id' => $this->get_id_arg() ), $this->get_update_args() ),
                ),
            )
        );



        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/start',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'start_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'        => $this->get_id_arg(),
                    'mime_type' => $this->get_mime_type_arg( true ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/chunks',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'upload_chunk' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'id'                 => $this->get_id_arg(),
                        'browser_session_id' => $this->get_browser_session_arg( true ),
                        'chunk_index'        => $this->get_non_negative_int_arg( true ),
                        'mime_type'          => $this->get_mime_type_arg( true ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_chunks' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'id'                 => $this->get_id_arg(),
                        'browser_session_id' => $this->get_browser_session_arg( false ),
                    ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/stop',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'stop_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'               => $this->get_id_arg(),
                    'duration_seconds' => $this->get_non_negative_int_arg( false ),
                ),
            )
        );
        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/recording/finalize',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'finalize_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array(
                    'id'              => $this->get_id_arg(),
                    'expected_chunks' => $this->get_positive_int_arg( true ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/prepare',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'prepare_publishing' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/publish',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'publish_recording' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/publishing/status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'publishing_status' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/jobs/(?P<id>\d+)/cancel',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'cancel_job' ),
                'permission_callback' => array( $this, 'permissions_check' ),
                'args'                => array( 'id' => $this->get_id_arg() ),
            )
        );
    }

    private function get_id_arg() {
        return array(
            'required'          => true,
            'validate_callback' => function( $value ) {
                return is_numeric( $value ) && 0 < absint( $value );
            },
            'sanitize_callback' => 'absint',
        );
    }



    private function get_mime_type_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'validate_callback' => function( $value ) {
                return $this->chunks->is_allowed_mime_type( $value );
            },
        );
    }

    private function get_browser_session_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => function( $value ) use ( $required ) {
                return ! $required && ( null === $value || '' === $value ) || ( is_string( $value ) && '' !== trim( $value ) && 191 >= strlen( $value ) );
            },
        );
    }

    private function get_non_negative_int_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $value ) use ( $required ) {
                return ! $required && ( null === $value || '' === $value ) || ( is_numeric( $value ) && 0 <= intval( $value ) );
            },
        );
    }

    private function get_positive_int_arg( $required ) {
        return array(
            'required'          => (bool) $required,
            'sanitize_callback' => 'absint',
            'validate_callback' => function( $value ) {
                return is_numeric( $value ) && 0 < intval( $value );
            },
        );
    }

    private function get_list_args() {
        return array(
            'per_page' => array(
                'default'           => 20,
                'validate_callback' => function( $value ) {
                    return is_numeric( $value ) && 1 <= absint( $value ) && 100 >= absint( $value );
                },
                'sanitize_callback' => 'absint',
            ),
        );
    }

    private function get_setup_create_args() {
        return array(
            'source_type'      => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_source_type' ),
            ),
            'source_id'        => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'live_video_id'    => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'room_id'          => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'recording_mode'   => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_recording_mode' ),
            ),
            'quality_preset'   => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function( $value ) {
                    return VH360_Studio_Quality_Presets::exists( sanitize_key( $value ) );
                },
            ),
            'storage_provider' => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_storage_provider' ),
            ),
        );
    }

    private function get_update_args() {
        return array_merge(
            $this->get_setup_create_args(),
            array(
                'status' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function( $value ) {
                        return in_array( sanitize_key( $value ), $this->jobs->allowed_statuses(), true );
                    },
                ),
            )
        );
    }

    private function setup_payload_from_request( WP_REST_Request $request ) {
        $payload = array();
        $allowed = array( 'source_type', 'source_id', 'live_video_id', 'room_id', 'recording_mode', 'quality_preset', 'storage_provider' );

        foreach ( $allowed as $key ) {
            if ( null !== $request->get_param( $key ) ) {
                $payload[ $key ] = $request->get_param( $key );
            }
        }

        $payload['status']         = VH360_Studio_Recording_Jobs::STATUS_CREATED;
        $payload['recording_mode'] = isset( $payload['recording_mode'] ) ? $payload['recording_mode'] : 'browser';
        $payload['source_type']    = isset( $payload['source_type'] ) ? $payload['source_type'] : 'studio_setup';

        return $payload;
    }

    private function update_payload_from_request( WP_REST_Request $request ) {
        $payload = array();
        $allowed = array( 'source_type', 'source_id', 'live_video_id', 'room_id', 'recording_mode', 'quality_preset', 'storage_provider' );

        foreach ( $allowed as $key ) {
            if ( null !== $request->get_param( $key ) ) {
                $payload[ $key ] = $request->get_param( $key );
            }
        }

        return $payload;
    }

    public function validate_source_type( $value ) {
        return in_array( sanitize_key( $value ), $this->jobs->allowed_source_types(), true );
    }

    public function validate_recording_mode( $value ) {
        return in_array( sanitize_key( $value ), $this->jobs->allowed_recording_modes(), true );
    }

    public function validate_storage_provider( $value ) {
        $registry = VH360_Studio_Plugin::instance()->registry();
        return $registry->has_storage_provider( sanitize_key( $value ) );
    }

    public function validate_optional_url( $value ) {
        return '' === $value || false !== wp_http_validate_url( $value );
    }

    public function permissions_check( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Invalid REST nonce.', 'videohub360-studio' ), array( 'status' => 403 ) );
        }

        return VH360_Studio_Permissions::user_can_access_studio( get_current_user_id() );
    }

    private function prepare_job_response( $job ) {
        if ( is_wp_error( $job ) ) {
            return $job;
        }
        if ( is_array( $job ) && array_key_exists( 'local_temp_path', $job ) ) {
            unset( $job['local_temp_path'] );
        }
        return $job;
    }

    private function broadcast_payload( WP_REST_Request $request ) {
        $mode = $request->get_param( 'agora_mode' ) ?: 'broadcast';
        $everyone = rest_sanitize_boolean( $request->get_param( 'agora_everyone_is_host' ) ) ? 'yes' : 'no';
        $require = rest_sanitize_boolean( $request->get_param( 'require_passcode' ) ) ? 'yes' : 'no';
        if ( 'broadcast' === $mode ) { $everyone = 'no'; $require = 'no'; }
        if ( 'yes' === $everyone ) { $require = 'no'; }
        return array(
            'title'                  => $request->get_param( 'title' ),
            'description'            => $request->get_param( 'description' ),
            'agora_mode'             => in_array( $mode, array( 'broadcast', 'interactive' ), true ) ? $mode : 'broadcast',
            'viewer_count'           => rest_sanitize_boolean( $request->get_param( 'viewer_count' ) ) ? 'yes' : 'no',
            'chat_enabled'           => rest_sanitize_boolean( $request->get_param( 'chat_enabled' ) ) ? 'yes' : 'no',
            'agora_everyone_is_host' => $everyone,
            'require_passcode'       => $require,
            'host_passcode'          => $request->get_param( 'host_passcode' ),
        );
    }

    public function create_broadcast( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $video_id = absint( $request->get_param( 'video_id' ) );
        $broadcast = $service->create_or_update_default_agora_livestream( get_current_user_id(), $this->broadcast_payload( $request ), $video_id );
        if ( is_wp_error( $broadcast ) ) { return $broadcast; }
        $job = $this->jobs->create( get_current_user_id(), array(
            'source_type'      => 'livestream_video',
            'live_video_id'    => absint( $broadcast['videoId'] ),
            'room_id'          => sanitize_text_field( $broadcast['channelName'] ),
            'recording_mode'   => 'browser',
            'quality_preset'   => sanitize_key( $request->get_param( 'quality_preset' ) ?: VH360_Studio_Quality_Presets::DEFAULT_PRESET ),
            'storage_provider' => sanitize_key( $request->get_param( 'storage_provider' ) ?: 'videopress' ),
        ) );
        return rest_ensure_response( array( 'broadcast' => $broadcast, 'job' => $this->prepare_job_response( $job ) ) );
    }

    public function broadcast_prepare( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        return rest_ensure_response( $service->prepare_agora_broadcast_data( absint( $request['video_id'] ), get_current_user_id() ) );
    }

    public function broadcast_started( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $service->mark_live( absint( $request['video_id'] ) );
        $job_id = absint( $request->get_param( 'job_id' ) );
        $job = null;
        if ( $job_id ) {
            $job = $this->jobs->update( $job_id, get_current_user_id(), array(
                'status'        => VH360_Studio_Recording_Jobs::STATUS_RECORDING,
                'live_video_id' => absint( $request['video_id'] ),
                'started_at'    => current_time( 'mysql' ),
            ) );
        }
        return rest_ensure_response( array( 'stream_live' => true, 'job' => $this->prepare_job_response( $job ) ) );
    }

    public function broadcast_heartbeat( WP_REST_Request $request ) {
        return rest_ensure_response( array( 'ok' => true, 'server_time' => current_time( 'mysql' ) ) );
    }

    public function broadcast_end( WP_REST_Request $request ) {
        $service = $this->livestream_service();
        if ( ! $service ) { return new WP_Error( 'vh360_studio_core_missing', __( 'VideoHub360 Core livestream service is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        $service->mark_ended( absint( $request['video_id'] ) );
        return rest_ensure_response( array( 'stream_live' => false ) );
    }

    public function list_jobs( WP_REST_Request $request ) {
        return rest_ensure_response( array_map( array( $this, 'prepare_job_response' ), $this->jobs->list( get_current_user_id(), $request->get_param( 'per_page' ) ) ) );
    }

    public function create_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->create( get_current_user_id(), $this->setup_payload_from_request( $request ) ) ) );
    }

    public function get_job( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        return $job ? rest_ensure_response( $this->prepare_job_response( $job ) ) : new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
    }

    public function update_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->update( absint( $request['id'] ), get_current_user_id(), $this->update_payload_from_request( $request ) ) ) );
    }



    public function start_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        if ( VH360_Studio_Recording_Jobs::STATUS_CREATED !== $job['status'] ) {
            return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only start from a created job.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $settings = $this->chunks->upload_settings();
        $mime_type = $this->chunks->base_mime_type( $request->get_param( 'mime_type' ) );
        if ( ! in_array( $mime_type, $settings['allowed_mime_types'], true ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_type', __( 'Recording MIME type is not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $session = wp_generate_uuid4();
        $job = $this->jobs->start_recording( $job['id'], get_current_user_id(), $session, $mime_type );
        if ( is_wp_error( $job ) ) { return $job; }
        return rest_ensure_response( array( 'job_id' => absint( $job['id'] ), 'browser_session_id' => $session, 'mime_type' => $mime_type, 'upload_settings' => $settings, 'status' => $job['status'] ) );
    }

    public function upload_chunk( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        if ( ! in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_RECORDING, VH360_Studio_Recording_Jobs::STATUS_STOPPING ), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_chunk_status', __( 'Chunks can only be uploaded while recording is active or stopping.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $session = sanitize_text_field( $request->get_param( 'browser_session_id' ) );
        if ( ! $session || $session !== $job['browser_session_id'] ) { return new WP_Error( 'vh360_studio_invalid_session', __( 'Invalid browser recording session.', 'videohub360-studio' ), array( 'status' => 403 ) ); }
        $chunk_index = $request->get_param( 'chunk_index' );
        if ( null === $chunk_index || ! is_numeric( $chunk_index ) || 0 > intval( $chunk_index ) ) { return new WP_Error( 'vh360_studio_invalid_chunk_index', __( 'Invalid recording chunk index.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $files = $request->get_file_params();
        if ( empty( $files['chunk'] ) ) { return new WP_Error( 'vh360_studio_missing_chunk', __( 'Missing uploaded recording chunk.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $summary = $this->chunks->store_uploaded_chunk( $job, $session, intval( $chunk_index ), $files['chunk'], $request->get_param( 'mime_type' ) );
        if ( is_wp_error( $summary ) ) { $this->jobs->mark_failed( $job['id'], get_current_user_id(), $summary->get_error_message() ); return $summary; }
        $summary['job_status'] = $job['status'];
        return rest_ensure_response( $summary );
    }

    public function list_chunks( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $session = sanitize_text_field( $request->get_param( 'browser_session_id' ) ? $request->get_param( 'browser_session_id' ) : $job['browser_session_id'] );
        $summary = $this->chunks->received_summary( $job['id'], $session );
        $summary['job_status'] = $job['status'];
        return rest_ensure_response( $summary );
    }

    public function stop_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        if ( VH360_Studio_Recording_Jobs::STATUS_RECORDING !== $job['status'] ) { return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only stop from the recording status.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->mark_stopping( $job['id'], get_current_user_id(), $request->get_param( 'duration_seconds' ) ) ) );
    }

    public function finalize_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        if ( VH360_Studio_Recording_Jobs::STATUS_STOPPING !== $job['status'] ) { return new WP_Error( 'vh360_studio_invalid_status_transition', __( 'Recording can only finalize from the stopping status.', 'videohub360-studio' ), array( 'status' => 409 ) ); }
        $uploading = $this->jobs->mark_uploading( $job['id'], get_current_user_id() );
        if ( is_wp_error( $uploading ) ) { return $uploading; }
        $expected_chunks = $request->get_param( 'expected_chunks' );
        $assembled = $this->chunks->assemble_chunks( $uploading, $job['browser_session_id'], $expected_chunks, $job['mime_type'] );
        if ( is_wp_error( $assembled ) ) { $this->jobs->mark_failed( $job['id'], get_current_user_id(), $assembled->get_error_message() ); return $assembled; }
        $summary = $this->chunks->received_summary( $job['id'], $job['browser_session_id'] );
        $recording = $this->validator->validate_assembled_recording( $uploading, $assembled, $summary, $expected_chunks );
        if ( is_wp_error( $recording ) ) { $this->jobs->mark_failed( $job['id'], get_current_user_id(), $recording->get_error_message() ); return $recording; }
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->mark_processing( $job['id'], get_current_user_id(), array( 'file_size' => absint( $recording['file_size'] ), 'local_temp_path' => $recording['path'], 'mime_type' => $recording['mime_type'], 'expected_chunks' => $recording['expected_chunks'], 'received_chunks' => $recording['received_chunks'], 'assembled_checksum' => $recording['assembled_checksum'], 'assembled_at' => $recording['assembled_at'], 'temp_expires_at' => $recording['temp_expires_at'] ) ) ) );
    }

    public function prepare_publishing( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $prepared = $this->publisher->prepare( $job );
        if ( is_wp_error( $prepared ) ) { return $prepared; }
        return rest_ensure_response( $prepared );
    }

    public function publish_recording( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        $published = $this->publisher->publish( $job );
        if ( is_wp_error( $published ) ) { return $published; }
        return rest_ensure_response( $published );
    }

    public function publishing_status( WP_REST_Request $request ) {
        $job = $this->chunks->validate_job_ownership( absint( $request['id'] ), get_current_user_id() );
        if ( is_wp_error( $job ) ) { return $job; }
        if ( ! in_array( $job['status'], array( VH360_Studio_Recording_Jobs::STATUS_PROCESSING, VH360_Studio_Recording_Jobs::STATUS_READY ), true ) ) {
            return new WP_Error( 'vh360_studio_invalid_publish_status', __( 'Publishing status is available for processing or ready jobs.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        $status = $this->publisher->status( $job );
        if ( is_wp_error( $status ) ) { return $status; }
        return rest_ensure_response( $status );
    }

    public function cancel_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->prepare_job_response( $this->jobs->cancel( absint( $request['id'] ), get_current_user_id() ) ) );
    }
}
