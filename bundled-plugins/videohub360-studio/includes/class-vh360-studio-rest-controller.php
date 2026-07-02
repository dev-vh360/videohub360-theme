<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_REST_Controller {
    private $jobs;
    public function __construct( VH360_Studio_Recording_Jobs $jobs ) { $this->jobs = $jobs; }
    public function register_routes() {
        register_rest_route( 'vh360-studio/v1', '/jobs', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'list_jobs' ), 'permission_callback' => array( $this, 'permissions_check' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'create_job' ), 'permission_callback' => array( $this, 'permissions_check' ) ),
        ) );
        register_rest_route( 'vh360-studio/v1', '/jobs/(?P<id>\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'get_job' ), 'permission_callback' => array( $this, 'permissions_check' ) ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'update_job' ), 'permission_callback' => array( $this, 'permissions_check' ) ),
        ) );
        register_rest_route( 'vh360-studio/v1', '/jobs/(?P<id>\d+)/cancel', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'cancel_job' ), 'permission_callback' => array( $this, 'permissions_check' ) ) );
    }
    public function permissions_check( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) { return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Invalid REST nonce.', 'videohub360-studio' ), array( 'status' => 403 ) ); }
        return is_user_logged_in() && current_user_can( 'read' );
    }
    public function list_jobs( WP_REST_Request $request ) { return rest_ensure_response( $this->jobs->list( get_current_user_id(), $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20 ) ); }
    public function create_job( WP_REST_Request $request ) { return rest_ensure_response( $this->jobs->create( get_current_user_id(), $request->get_json_params() ? $request->get_json_params() : $request->get_params() ) ); }
    public function get_job( WP_REST_Request $request ) { $job = $this->jobs->get( absint( $request['id'] ), get_current_user_id() ); return $job ? rest_ensure_response( $job ) : new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
    public function update_job( WP_REST_Request $request ) { return rest_ensure_response( $this->jobs->update( absint( $request['id'] ), get_current_user_id(), $request->get_json_params() ? $request->get_json_params() : $request->get_params() ) ); }
    public function cancel_job( WP_REST_Request $request ) { return rest_ensure_response( $this->jobs->cancel( absint( $request['id'] ), get_current_user_id() ) ); }
}
