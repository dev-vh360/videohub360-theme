<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push REST API
 * 
 * Handles REST API endpoints for native push token management.
 */
class VH360_PWA_Push_REST_API {
	/** @var string */
	private $namespace = 'vh360-pwa/v1';

	/** @var VH360_PWA_Push_Token_Manager */
	private $token_manager;

	/**
	 * Constructor
	 * 
	 * @param VH360_PWA_Push_Token_Manager $token_manager
	 */
	public function __construct( $token_manager ) {
		$this->token_manager = $token_manager;
	}

	/**
	 * Register REST API routes
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		// Register token
		register_rest_route(
			$this->namespace,
			'/push/register-token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'register_token_endpoint' ),
				'permission_callback' => array( $this, 'permission_callback_public' ),
				'args'                => array(
					'device_token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'platform' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'ios', 'android' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'wrapper_type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'device_info' => array(
						'type' => 'object',
					),
					'app_version' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Update token
		register_rest_route(
			$this->namespace,
			'/push/update-token',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_token_endpoint' ),
				'permission_callback' => array( $this, 'permission_callback_public' ),
				'args'                => array(
					'device_token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'platform' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'ios', 'android' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Unregister token
		register_rest_route(
			$this->namespace,
			'/push/unregister-token',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'unregister_token_endpoint' ),
				'permission_callback' => array( $this, 'permission_callback_public' ),
				'args'                => array(
					'device_token' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'platform' => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => array( 'ios', 'android' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get my tokens (authenticated)
		register_rest_route(
			$this->namespace,
			'/push/my-tokens',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'my_tokens_endpoint' ),
				'permission_callback' => array( $this, 'permission_callback_authenticated' ),
			)
		);
	}

	/**
	 * Register token endpoint
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function register_token_endpoint( $request ) {
		// Rate limiting
		if ( ! $this->check_rate_limit( 'register', 10, 60 ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rate limit exceeded. Please try again later.', 'vh360-pwa-app' ),
				),
				429
			);
		}

		$device_token = $request->get_param( 'device_token' );
		$platform = $request->get_param( 'platform' );

		// Validate token format
		if ( ! $this->token_manager->validate_token_format( $device_token, $platform ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid token format for the specified platform.', 'vh360-pwa-app' ),
				),
				400
			);
		}

		// Prepare token data
		$data = array(
			'device_token' => $device_token,
			'platform'     => $platform,
		);

		// Add optional fields
		if ( $request->get_param( 'wrapper_type' ) ) {
			$data['wrapper_type'] = $request->get_param( 'wrapper_type' );
		}

		if ( $request->get_param( 'device_info' ) ) {
			$data['device_info'] = $request->get_param( 'device_info' );
		}

		if ( $request->get_param( 'app_version' ) ) {
			$data['app_version'] = $request->get_param( 'app_version' );
		}

		// Add user_id if logged in
		if ( is_user_logged_in() ) {
			$data['user_id'] = get_current_user_id();
		}

		// Register token
		$token_id = $this->token_manager->register_token( $data );

		if ( false === $token_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to register token.', 'vh360-pwa-app' ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'token_id' => $token_id,
				'message'  => __( 'Token registered successfully.', 'vh360-pwa-app' ),
			),
			200
		);
	}

	/**
	 * Update token endpoint
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function update_token_endpoint( $request ) {
		// Rate limiting
		if ( ! $this->check_rate_limit( 'update', 60, 3600 ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rate limit exceeded. Please try again later.', 'vh360-pwa-app' ),
				),
				429
			);
		}

		$device_token = $request->get_param( 'device_token' );
		$platform = $request->get_param( 'platform' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'vh360_push_tokens';

		// Find token
		$token = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE device_token = %s AND platform = %s LIMIT 1",
				$device_token,
				$platform
			)
		);

		if ( ! $token ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Token not found.', 'vh360-pwa-app' ),
				),
				404
			);
		}

		// Update last_active
		$updated = $this->token_manager->update_last_active( $token->id );

		if ( ! $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to update token.', 'vh360-pwa-app' ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success'     => true,
				'last_active' => current_time( 'mysql' ),
				'message'     => __( 'Token updated successfully.', 'vh360-pwa-app' ),
			),
			200
		);
	}

	/**
	 * Unregister token endpoint
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function unregister_token_endpoint( $request ) {
		$device_token = $request->get_param( 'device_token' );
		$platform = $request->get_param( 'platform' );

		// Deactivate token
		$deactivated = $this->token_manager->deactivate_token_by_string( $device_token, $platform );

		if ( ! $deactivated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to unregister token.', 'vh360-pwa-app' ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Token deactivated successfully.', 'vh360-pwa-app' ),
			),
			200
		);
	}

	/**
	 * My tokens endpoint (authenticated)
	 * 
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function my_tokens_endpoint( $request ) {
		$user_id = get_current_user_id();
		$tokens = $this->token_manager->get_user_tokens( $user_id );

		// Format tokens for response
		$formatted_tokens = array();
		foreach ( $tokens as $token ) {
			$formatted_tokens[] = array(
				'id'           => (int) $token->id,
				'platform'     => $token->platform,
				'wrapper_type' => $token->wrapper_type,
				'device_info'  => json_decode( $token->device_info ),
				'app_version'  => $token->app_version,
				'last_active'  => $token->last_active,
				'created_at'   => $token->created_at,
				'is_active'    => (bool) $token->is_active,
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'tokens'  => $formatted_tokens,
			),
			200
		);
	}

	/**
	 * Public permission callback (allows both logged-in and guest users)
	 * 
	 * @return bool
	 */
	public function permission_callback_public() {
		return true;
	}

	/**
	 * Authenticated permission callback
	 * 
	 * @return bool
	 */
	public function permission_callback_authenticated() {
		return is_user_logged_in();
	}

	/**
	 * Check rate limit
	 * 
	 * @param string $action Action name
	 * @param int $limit Max requests
	 * @param int $window Time window in seconds
	 * @return bool
	 */
	private function check_rate_limit( $action, $limit, $window ) {
		$ip = $this->get_client_ip();
		$transient_key = 'vh360_push_rate_limit_' . $ip . '_' . $action;

		$current = get_transient( $transient_key );

		if ( false === $current ) {
			// First request
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( $current >= $limit ) {
			// Rate limit exceeded
			return false;
		}

		// Increment counter
		set_transient( $transient_key, $current + 1, $window );
		return true;
	}

	/**
	 * Get client IP address
	 * 
	 * @return string
	 */
	private function get_client_ip() {
		// Use REMOTE_ADDR as it's the most reliable and cannot be spoofed
		// In production environments with reverse proxies/load balancers,
		// configure the proxy to set REMOTE_ADDR correctly
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Fallback to a default if somehow REMOTE_ADDR is not set
		if ( empty( $ip ) ) {
			$ip = '0.0.0.0';
		}

		return sanitize_text_field( $ip );
	}
}
