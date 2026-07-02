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

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs = $jobs;
    }

    public function register_routes() {
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
                    'args'                => $this->get_write_args( false ),
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
                    'args'                => array_merge( array( 'id' => $this->get_id_arg() ), $this->get_write_args( true ) ),
                ),
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

    private function get_write_args( $partial ) {
        return array(
            'source_type'                => array( 'required' => false, 'sanitize_callback' => 'sanitize_key' ),
            'source_id'                  => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'live_video_id'              => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'room_id'                    => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'recording_mode'             => array( 'required' => false, 'sanitize_callback' => 'sanitize_key' ),
            'quality_preset'             => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function( $value ) {
                    return VH360_Studio_Quality_Presets::exists( sanitize_key( $value ) );
                },
            ),
            'storage_provider'           => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => array( $this, 'validate_storage_provider' ),
            ),
            'status'                     => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function( $value ) {
                    return in_array( sanitize_key( $value ), $this->jobs->allowed_statuses(), true );
                },
            ),
            'browser_session_id'         => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'mime_type'                  => array( 'required' => false, 'sanitize_callback' => 'sanitize_mime_type' ),
            'duration_seconds'           => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'file_size'                  => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'local_temp_path'            => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'wp_attachment_id'           => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'videopress_guid'            => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'videopress_processing_done' => array( 'required' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
            'publitio_file_id'           => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'playback_url'               => array( 'required' => false, 'sanitize_callback' => 'esc_url_raw', 'validate_callback' => array( $this, 'validate_optional_url' ) ),
            'poster_url'                 => array( 'required' => false, 'sanitize_callback' => 'esc_url_raw', 'validate_callback' => array( $this, 'validate_optional_url' ) ),
            'error_message'              => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'retry_count'                => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'started_at'                 => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'stopped_at'                 => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'completed_at'               => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
        );
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

    public function list_jobs( WP_REST_Request $request ) {
        return rest_ensure_response( $this->jobs->list( get_current_user_id(), $request->get_param( 'per_page' ) ) );
    }

    public function create_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->jobs->create( get_current_user_id(), $request->get_params() ) );
    }

    public function get_job( WP_REST_Request $request ) {
        $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() );
        return $job ? rest_ensure_response( $job ) : new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) );
    }

    public function update_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->jobs->update( absint( $request['id'] ), get_current_user_id(), $request->get_params() ) );
    }

    public function cancel_job( WP_REST_Request $request ) {
        return rest_ensure_response( $this->jobs->cancel( absint( $request['id'] ), get_current_user_id() ) );
    }
}
