<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Video_Upload_REST_Controller {
    private $service;
    public function __construct( VH360_Studio_Video_Storage_Service $service ) { $this->service = $service; }
    public function register_routes() {
        register_rest_route( 'vh360-studio/v1', '/video-assets', array( 'methods' => 'POST', 'callback' => array( $this, 'create' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
        register_rest_route( 'vh360-studio/v1', '/video-assets/(?P<uuid>[a-f0-9-]+)', array( 'methods' => 'GET', 'callback' => array( $this, 'get' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
        register_rest_route( 'vh360-studio/v1', '/video-assets/(?P<uuid>[a-f0-9-]+)/upload', array( 'methods' => 'POST', 'callback' => array( $this, 'upload' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
        register_rest_route( 'vh360-studio/v1', '/video-assets/(?P<uuid>[a-f0-9-]+)/complete', array( 'methods' => 'POST', 'callback' => array( $this, 'complete' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
        register_rest_route( 'vh360-studio/v1', '/video-assets/(?P<uuid>[a-f0-9-]+)/retry', array( 'methods' => 'POST', 'callback' => array( $this, 'retry' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
        register_rest_route( 'vh360-studio/v1', '/video-assets/(?P<uuid>[a-f0-9-]+)', array( 'methods' => 'DELETE', 'callback' => array( $this, 'delete' ), 'permission_callback' => array( $this, 'can_upload' ) ) );
    }
    public function can_upload() { return is_user_logged_in(); }
    public function create( WP_REST_Request $request ) { $asset = $this->service->create_asset( get_current_user_id(), array( 'context' => $request['context'], 'filename' => $request['filename'], 'mime_type' => $request['mime_type'], 'file_size' => $request['file_size'] ) ); return is_wp_error( $asset ) ? $asset : rest_ensure_response( $this->service->prepare_response( $asset ) ); }
    public function get( WP_REST_Request $request ) { $asset = $this->service->get_asset_by_uuid( $request['uuid'] ); if ( ! $asset ) { return new WP_Error( 'not_found', __( 'Video asset not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); } if ( ! current_user_can( 'manage_options' ) && absint( $asset['user_id'] ) !== get_current_user_id() ) { return new WP_Error( 'forbidden', __( 'Video asset unavailable.', 'videohub360-studio' ), array( 'status' => 403 ) ); } return rest_ensure_response( $this->service->prepare_response( $this->service->refresh_status( $asset ) ) ); }
    public function upload( WP_REST_Request $request ) { $files = $request->get_file_params(); $asset = $this->service->upload_asset( $request['uuid'], isset( $files['file'] ) ? $files['file'] : array() ); return is_wp_error( $asset ) ? $asset : rest_ensure_response( $this->service->prepare_response( $asset ) ); }
    public function complete( WP_REST_Request $request ) { $asset = $this->service->complete_direct_upload( $request['uuid'], $request->get_json_params() ?: array() ); return is_wp_error( $asset ) ? $asset : rest_ensure_response( $this->service->prepare_response( $asset ) ); }
    public function retry( WP_REST_Request $request ) { $asset = $this->service->retry( $request['uuid'] ); return is_wp_error( $asset ) ? $asset : rest_ensure_response( $this->service->prepare_response( $asset ) ); }
    public function delete( WP_REST_Request $request ) { $deleted = $this->service->cancel_or_delete( $request['uuid'] ); return is_wp_error( $deleted ) ? $deleted : rest_ensure_response( array( 'deleted' => true ) ); }
}
