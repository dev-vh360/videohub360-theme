<?php
/**
 * Studio overlays REST controller.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Overlays_REST_Controller {
    private $repository;

    public function __construct( VH360_Studio_Overlay_Repository $repository ) {
        $this->repository = $repository;
    }

    public function register_routes() {
        register_rest_route(
            'vh360-studio/v1',
            '/overlay-tools',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_overlay_tools' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_overlay_tools' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                    'args'                => array(
                        'enabled_modules' => array(
                            'type'    => 'array',
                            'items'   => array( 'type' => 'string' ),
                            'default' => array(),
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/overlays',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_items' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'type' => array(
                            'sanitize_callback' => 'sanitize_key',
                            'default'           => VH360_Studio_Overlay_Repository::TYPE_LOWER_THIRD,
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_item' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                ),
            )
        );

        register_rest_route(
            'vh360-studio/v1',
            '/overlays/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_item' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_item' ),
                    'permission_callback' => array( $this, 'licensed_permissions_check' ),
                ),
            )
        );
    }

    public function permissions_check() {
        return is_user_logged_in() && VH360_Studio_Permissions::user_can_access_studio();
    }

    public function licensed_permissions_check() {
        $access = $this->permissions_check();

        if ( is_wp_error( $access ) || true !== $access ) {
            return $access;
        }

        return VH360_Studio_Permissions::license_permission_result();
    }

    public function get_overlay_tools() {
        return rest_ensure_response( array( 'enabled_modules' => VH360_Studio_User_Preferences::get_enabled_overlay_modules( get_current_user_id() ) ) );
    }

    public function update_overlay_tools( WP_REST_Request $request ) {
        $params  = $request->get_json_params() ?: array();
        $modules = isset( $params['enabled_modules'] ) ? $params['enabled_modules'] : array();
        return rest_ensure_response( array( 'enabled_modules' => VH360_Studio_User_Preferences::save_enabled_overlay_modules( get_current_user_id(), $modules ) ) );
    }

    public function list_items( WP_REST_Request $request ) {
        $items = $this->repository->list( get_current_user_id(), $request->get_param( 'type' ) );
        return is_wp_error( $items ) ? $items : rest_ensure_response( $items );
    }

    public function create_item( WP_REST_Request $request ) {
        $item = $this->repository->create( get_current_user_id(), $request->get_json_params() ?: array() );
        return is_wp_error( $item ) ? $item : rest_ensure_response( $item );
    }

    public function update_item( WP_REST_Request $request ) {
        $item = $this->repository->update( absint( $request['id'] ), get_current_user_id(), $request->get_json_params() ?: array() );
        return is_wp_error( $item ) ? $item : rest_ensure_response( $item );
    }

    public function delete_item( WP_REST_Request $request ) {
        $deleted = $this->repository->delete( absint( $request['id'] ), get_current_user_id() );
        return is_wp_error( $deleted ) ? $deleted : rest_ensure_response( array( 'deleted' => true ) );
    }
}
