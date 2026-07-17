<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push Manager
 * 
 * Central manager for push notifications.
 * Routes sends based on mode (provider/native/hybrid).
 */
class VH360_PWA_Push_Manager {
	/** @var array */
	private $adapters = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Adapters will be registered on init
	}

	/**
	 * Register an adapter
	 * 
	 * @param VH360_PWA_Push_Adapter_Interface $adapter
	 */
	public function register_adapter( VH360_PWA_Push_Adapter_Interface $adapter ) : void {
		$this->adapters[ $adapter->get_slug() ] = $adapter;
	}

	/**
	 * Get all registered adapters
	 * 
	 * @return array
	 */
	public function get_adapters() : array {
		return $this->adapters;
	}

	/**
	 * Get adapter by slug
	 * 
	 * @param string $slug
	 * @return VH360_PWA_Push_Adapter_Interface|null
	 */
	public function get_adapter( string $slug ) {
		return $this->adapters[ $slug ] ?? null;
	}

	/**
	 * Get push settings
	 * 
	 * @return array
	 */
	public function get_settings() : array {
		$defaults = array(
			'mode'             => 'provider', // provider | native | hybrid
			'active_provider'  => 'onesignal',
			'providers'        => array(
				'onesignal' => array(
					'app_id'              => '',
					'rest_api_key'        => '',
					'sw_mode'             => 'dedicated',
					'sw_scope'            => '/push/onesignal/',
					'default_click_url'   => home_url( '/' ),
					'default_icon_url'    => '',
					'auto_prompt'         => false,
					'auto_prompt_delay'   => 0,
					'auto_prompt_scroll'  => false,
					'auto_prompt_login'   => false,
				),
				'native' => array(
					'apns_enabled'          => false,
					'apns_key_id'           => '',
					'apns_team_id'          => '',
					'apns_bundle_id'        => '',
					'apns_environment'      => 'production',
					'apns_key_file'         => '',
					'fcm_enabled'           => false,
					'fcm_project_id'        => '',
					'fcm_server_key'        => '',
					'fcm_sender_id'         => '',
					'token_cleanup_days'    => 90,
					'rate_limit_per_minute' => 10,
					'rate_limit_per_hour'   => 60,
				),
			),
		);

		$settings = get_option( 'vh360_pwa_push_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Deep merge providers
		if ( isset( $settings['providers'] ) && is_array( $settings['providers'] ) ) {
			foreach ( $defaults['providers'] as $provider_key => $provider_defaults ) {
				if ( isset( $settings['providers'][ $provider_key ] ) && is_array( $settings['providers'][ $provider_key ] ) ) {
					$settings['providers'][ $provider_key ] = array_merge(
						$provider_defaults,
						$settings['providers'][ $provider_key ]
					);
				}
			}
		}

		$settings = array_merge( $defaults, $settings );
		if ( isset( $settings['providers']['onesignal']['sw_scope'] ) && '/' === $settings['providers']['onesignal']['sw_scope'] ) {
			$settings['providers']['onesignal']['sw_scope'] = '/push/onesignal/';
			$settings['providers']['onesignal']['sw_mode'] = 'dedicated';
		}

		return $settings;
	}

	/**
	 * Update push settings
	 * 
	 * @param array $new_settings
	 * @return bool
	 */
	public function update_settings( array $new_settings ) : bool {
		$current = $this->get_settings();
		$merged = array_merge( $current, $new_settings );
		$changed = $current !== $merged;
		$updated = update_option( 'vh360_pwa_push_settings', $merged );
		if ( $changed && function_exists( 'vh360_pwa_bump_asset_version' ) ) {
			vh360_pwa_bump_asset_version();
			if ( class_exists( 'VH360_PWA_Root_Files' ) ) {
				VH360_PWA_Root_Files::ensure_root_files();
			}
		}
		return $updated;
	}

	/**
	 * Validate current settings
	 * 
	 * @return array List of errors (empty if valid)
	 */
	public function validate_current_settings() : array {
		$settings = $this->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$errors = array();

		if ( 'provider' === $mode || 'hybrid' === $mode ) {
			$provider_slug = $settings['active_provider'] ?? '';
			if ( ! $provider_slug ) {
				$errors[] = __( 'No active provider selected.', 'vh360-pwa-app' );
			} else {
				$adapter = $this->get_adapter( $provider_slug );
				if ( ! $adapter ) {
					$errors[] = sprintf( __( 'Provider "%s" is not registered.', 'vh360-pwa-app' ), $provider_slug );
				} else {
					$provider_settings = $settings['providers'][ $provider_slug ] ?? array();
					$adapter_errors = $adapter->validate_settings( $provider_settings );
					$errors = array_merge( $errors, $adapter_errors );
				}
			}
		}

		if ( 'native' === $mode || 'hybrid' === $mode ) {
			$native_adapter = $this->get_adapter( 'native' );
			if ( ! $native_adapter ) {
				$errors[] = __( 'Native adapter is not registered.', 'vh360-pwa-app' );
			} else {
				$native_settings = $settings['providers']['native'] ?? array();
				$native_errors = $native_adapter->validate_settings( $native_settings );
				$errors = array_merge( $errors, $native_errors );
			}
		}

		return $errors;
	}

	/**
	 * Send notification
	 * 
	 * @param array $message Message data
	 * @param array $audience Audience targeting
	 * @return array Unified result
	 */
	public function send( array $message, array $audience = array() ) : array {
		$settings = $this->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$results = array();

		$timestamp = current_time( 'mysql' );

		// Provider mode
		if ( 'provider' === $mode || 'hybrid' === $mode ) {
			$provider_slug = $settings['active_provider'] ?? '';
			$adapter = $this->get_adapter( $provider_slug );
			
			if ( ! $adapter ) {
				$results['provider'] = array(
					'success'   => false,
					'error'     => sprintf( __( 'Provider "%s" not found.', 'vh360-pwa-app' ), $provider_slug ),
					'timestamp' => $timestamp,
				);
			} else {
				$provider_settings = $settings['providers'][ $provider_slug ] ?? array();
				$result = $adapter->send( $message, $audience, $provider_settings );
				$result['timestamp'] = $timestamp;
				$result['provider'] = $provider_slug;
				$results['provider'] = $result;
			}
		}

		// Native mode (Phase 2)
		if ( 'native' === $mode || 'hybrid' === $mode ) {
			$native_adapter = $this->get_adapter( 'native' );
			if ( ! $native_adapter ) {
				$results['native'] = array(
					'success'   => false,
					'error'     => __( 'Native adapter not found.', 'vh360-pwa-app' ),
					'timestamp' => $timestamp,
				);
			} else {
				$native_settings = $settings['providers']['native'] ?? array();
				$result = $native_adapter->send( $message, $audience, $native_settings );
				$result['timestamp'] = $timestamp;
				$results['native'] = $result;
				
				// Store last send info
				update_option( 'vh360_push_native_last_send', array(
					'timestamp' => time(),
					'sent'      => $result['sent_count'] ?? 0,
					'failed'    => $result['failed_count'] ?? 0,
				) );
			}
		}

		// Log the send
		$this->log_send( 'send', $message, $results );

		return $results;
	}

	/**
	 * Send test notification
	 * 
	 * @param array $message Message data
	 * @return array Unified result
	 */
	public function send_test( array $message ) : array {
		$settings = $this->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$results = array();

		$timestamp = current_time( 'mysql' );

		// Provider mode
		if ( 'provider' === $mode || 'hybrid' === $mode ) {
			$provider_slug = $settings['active_provider'] ?? '';
			$adapter = $this->get_adapter( $provider_slug );
			
			if ( ! $adapter ) {
				$results['provider'] = array(
					'success'   => false,
					'error'     => sprintf( __( 'Provider "%s" not found.', 'vh360-pwa-app' ), $provider_slug ),
					'timestamp' => $timestamp,
				);
			} else {
				$provider_settings = $settings['providers'][ $provider_slug ] ?? array();
				$result = $adapter->send_test( $message, $provider_settings );
				$result['timestamp'] = $timestamp;
				$result['provider'] = $provider_slug;
				$results['provider'] = $result;
			}
		}

		// Native mode (Phase 2)
		if ( 'native' === $mode || 'hybrid' === $mode ) {
			$native_adapter = $this->get_adapter( 'native' );
			if ( ! $native_adapter ) {
				$results['native'] = array(
					'success'   => false,
					'error'     => __( 'Native adapter not found.', 'vh360-pwa-app' ),
					'timestamp' => $timestamp,
				);
			} else {
				$native_settings = $settings['providers']['native'] ?? array();
				$result = $native_adapter->send_test( $message, $native_settings );
				$result['timestamp'] = $timestamp;
				$results['native'] = $result;
			}
		}

		// Log the test send
		$this->log_send( 'test_send', $message, $results );

		return $results;
	}

	/**
	 * Log a send action
	 * 
	 * @param string $action Action type (send, test_send, validate)
	 * @param array $message Message data
	 * @param array $results Results
	 */
	private function log_send( string $action, array $message, array $results ) : void {
		$logs = get_option( 'vh360_pwa_push_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		$log_entry = array(
			'timestamp'        => current_time( 'mysql' ),
			'action'           => $action,
			'sender_user_id'   => is_user_logged_in() ? get_current_user_id() : 0,
			'sender_name'      => is_user_logged_in() ? ( wp_get_current_user()->display_name ?? '' ) : '',
			'title'            => substr( $message['title'] ?? 'No title', 0, 100 ),
			'notification_id'  => wp_generate_uuid4(),
			'recipients_count' => 0,
			'delivered_count'  => 0,
			'failed_count'     => 0,
			'clicked_count'    => 0,
			'send_duration'    => 0,
			'results'          => array(),
		);

		foreach ( $results as $mode_key => $result ) {
			// Aggregate counts from provider responses
			if ( isset( $result['recipients'] ) ) {
				$log_entry['recipients_count'] += absint( $result['recipients'] );
			}
			if ( ! empty( $result['success'] ) ) {
				$log_entry['delivered_count'] += absint( $result['recipients'] ?? 1 );
			} else {
				$log_entry['failed_count']++;
			}

			$log_entry['results'][ $mode_key ] = array(
				'provider'            => $result['provider'] ?? $mode_key,
				'success'             => $result['success'] ?? false,
				'error'               => $result['error'] ?? null,
				'response_id'         => $result['response_id'] ?? null,
				'provider_response_id' => $result['response_id'] ?? null,
				'recipients'          => $result['recipients'] ?? 0,
			);
		}

		// Prepend (newest first)
		array_unshift( $logs, $log_entry );

		// Keep last 50 entries
		$logs = array_slice( $logs, 0, 50 );

		update_option( 'vh360_pwa_push_logs', $logs );
	}

	/**
	 * Add a log entry with extended metrics
	 * 
	 * @param array $log_data Log entry data
	 */
	public function add_log( array $log_data ) : void {
		$logs = get_option( 'vh360_pwa_push_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		// Ensure required fields with defaults
		$log_entry = array_merge(
			array(
				'timestamp'        => current_time( 'mysql' ),
				'action'           => 'custom',
				'title'            => '',
				'notification_id'  => wp_generate_uuid4(),
				'recipients_count' => 0,
				'delivered_count'  => 0,
				'failed_count'     => 0,
				'clicked_count'    => 0,
				'send_duration'    => 0,
				'results'          => array(),
			),
			$log_data
		);

		// Prepend (newest first)
		array_unshift( $logs, $log_entry );

		// Keep last 50 entries
		$logs = array_slice( $logs, 0, 50 );

		update_option( 'vh360_pwa_push_logs', $logs );
	}

	/**
	 * Get logs
	 * 
	 * @param int $limit Number of entries to return
	 * @return array
	 */
	public function get_logs( int $limit = 50 ) : array {
		$logs = get_option( 'vh360_pwa_push_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clear logs
	 */
	public function clear_logs() : bool {
		return delete_option( 'vh360_pwa_push_logs' );
	}

	/**
	 * Clear all push settings (for reset)
	 */
	public function reset_settings() : bool {
		$deleted_settings = delete_option( 'vh360_pwa_push_settings' );
		$deleted_logs     = delete_option( 'vh360_pwa_push_logs' );

		if ( function_exists( 'vh360_pwa_bump_asset_version' ) ) {
			vh360_pwa_bump_asset_version();
		}

		if ( class_exists( 'VH360_PWA_Root_Files' ) ) {
			VH360_PWA_Root_Files::ensure_root_files();
		}

		return $deleted_settings || $deleted_logs;
	}
}
