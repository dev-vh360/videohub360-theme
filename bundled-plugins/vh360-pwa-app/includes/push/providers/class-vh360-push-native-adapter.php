<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native Push Adapter
 * 
 * Integrates APNs (iOS) and FCM (Android) with the push notification system.
 */
class VH360_Push_Native_Adapter implements VH360_PWA_Push_Adapter_Interface {

	/**
	 * Get adapter slug
	 */
	public function get_slug() : string {
		return 'native';
	}

	/**
	 * Get adapter label
	 */
	public function get_label() : string {
		return 'Native Push (APNs + FCM)';
	}

	/**
	 * Get settings fields for the Setup UI
	 */
	public function get_settings_fields() : array {
		return array(
			// APNs Settings
			array(
				'key'         => 'apns_enabled',
				'label'       => __( 'Enable APNs (iOS)', 'vh360-pwa-app' ),
				'type'        => 'checkbox',
				'required'    => false,
				'description' => __( 'Enable Apple Push Notification service for iOS devices.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'apns_key_id',
				'label'       => __( 'APNs Key ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Your APNs Key ID (10-character string).', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'apns_team_id',
				'label'       => __( 'APNs Team ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Your Apple Developer Team ID.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'apns_bundle_id',
				'label'       => __( 'App Bundle ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Your iOS app bundle identifier (e.g., com.example.app).', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'apns_environment',
				'label'       => __( 'APNs Environment', 'vh360-pwa-app' ),
				'type'        => 'select',
				'options'     => array(
					'production' => __( 'Production', 'vh360-pwa-app' ),
					'sandbox'    => __( 'Sandbox', 'vh360-pwa-app' ),
				),
				'required'    => false,
				'description' => __( 'Use sandbox for development, production for live apps.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'apns_key_file',
				'label'       => __( 'APNs Private Key (.p8)', 'vh360-pwa-app' ),
				'type'        => 'file',
				'required'    => false,
				'description' => __( 'Upload your APNs .p8 private key file. The key will be encrypted and stored securely.', 'vh360-pwa-app' ),
			),
			// FCM Settings
			array(
				'key'         => 'fcm_enabled',
				'label'       => __( 'Enable FCM (Android)', 'vh360-pwa-app' ),
				'type'        => 'checkbox',
				'required'    => false,
				'description' => __( 'Enable Firebase Cloud Messaging for Android devices.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'fcm_project_id',
				'label'       => __( 'FCM Project ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Your Firebase project ID.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'fcm_server_key',
				'label'       => __( 'FCM Server Key', 'vh360-pwa-app' ),
				'type'        => 'password',
				'required'    => false,
				'description' => __( 'Your FCM legacy server key. This key is stored securely.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'fcm_sender_id',
				'label'       => __( 'FCM Sender ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Your FCM sender ID (Project Number).', 'vh360-pwa-app' ),
			),
		);
	}

	/**
	 * Validate settings
	 */
	public function validate_settings( array $settings ) : array {
		$errors = array();

		// Validate APNs if enabled
		if ( ! empty( $settings['apns_enabled'] ) ) {
			if ( empty( $settings['apns_key_id'] ) ) {
				$errors[] = __( 'APNs Key ID is required when APNs is enabled.', 'vh360-pwa-app' );
			}
			if ( empty( $settings['apns_team_id'] ) ) {
				$errors[] = __( 'APNs Team ID is required when APNs is enabled.', 'vh360-pwa-app' );
			}
			if ( empty( $settings['apns_bundle_id'] ) ) {
				$errors[] = __( 'App Bundle ID is required when APNs is enabled.', 'vh360-pwa-app' );
			}
			if ( empty( $settings['apns_key_file'] ) ) {
				$errors[] = __( 'APNs Private Key file is required when APNs is enabled.', 'vh360-pwa-app' );
			}
		}

		// Validate FCM if enabled
		if ( ! empty( $settings['fcm_enabled'] ) ) {
			if ( empty( $settings['fcm_project_id'] ) ) {
				$errors[] = __( 'FCM Project ID is required when FCM is enabled.', 'vh360-pwa-app' );
			}
			if ( empty( $settings['fcm_server_key'] ) ) {
				$errors[] = __( 'FCM Server Key is required when FCM is enabled.', 'vh360-pwa-app' );
			}
		}

		return $errors;
	}

	/**
	 * Enqueue frontend SDK scripts (not used for native push)
	 */
	public function enqueue_frontend_sdk( array $settings ) : void {
		// Native push is handled by mobile apps, no frontend SDK needed
	}

	/**
	 * Get frontend bootstrap configuration (not used for native push)
	 */
	public function get_frontend_bootstrap( array $settings ) : array {
		// Native push is handled by mobile apps, no frontend config needed
		return array();
	}

	/**
	 * Send notification
	 */
	public function send( array $message, array $audience, array $settings ) : array {
		$start_time = microtime( true );
		
		// Get token manager
		$token_manager = VH360_PWA_App::instance()->token_manager;
		if ( ! $token_manager ) {
			return array(
				'success'       => false,
				'error'         => 'Token manager not available',
				'sent_count'    => 0,
				'failed_count'  => 0,
				'ios_results'   => array(),
				'android_results' => array(),
			);
		}

		// Get tokens (filtered by user if audience specified)
		$token_args = array( 'active_only' => true );

		if (!empty($audience['user_ids']) && is_array($audience['user_ids'])) {
			// Target specific users - collect tokens for each user
			$all_tokens = array();
			foreach ($audience['user_ids'] as $user_id) {
				$user_tokens = $token_manager->get_tokens(array(
					'user_id'     => $user_id,
					'active_only' => true,
				));
				$all_tokens = array_merge($all_tokens, $user_tokens);
			}
		} else {
			// Fallback: get all active tokens
			$all_tokens = $token_manager->get_tokens($token_args);
		}
		
		$ios_results = array();
		$android_results = array();
		$sent_count = 0;
		$failed_count = 0;

		// MVP: Send to each token in a simple loop (no batching/queuing)
		// For production with many devices, implement background processing
		// to avoid timeout issues and improve performance
		foreach ( $all_tokens as $token_obj ) {
			$result = $this->send_to_device( $token_obj->device_token, $token_obj->platform, $message, $settings );
			
			if ( $result['success'] ) {
				$sent_count++;
			} else {
				$failed_count++;
			}

			// Deactivate token if needed
			if ( ! empty( $result['deactivate'] ) ) {
				$token_manager->deactivate_token( $token_obj->id );
			}

			// Store result
			if ( $token_obj->platform === 'ios' ) {
				$ios_results[] = $result;
			} else {
				$android_results[] = $result;
			}
		}

		$duration = microtime( true ) - $start_time;

		return array(
			'success'         => $sent_count > 0,
			'sent_count'      => $sent_count,
			'failed_count'    => $failed_count,
			'ios_results'     => $ios_results,
			'android_results' => $android_results,
			'duration'        => $duration,
			'recipients'      => count( $all_tokens ),
		);
	}

	/**
	 * Send to specific device (public for testing)
	 */
	public function send_to_device( $token, $platform, $message, $settings ) {
		if ( 'ios' === $platform ) {
			return $this->send_to_ios( $token, $message, $settings );
		} elseif ( 'android' === $platform ) {
			return $this->send_to_android( $token, $message, $settings );
		}

		return array(
			'success'    => false,
			'error'      => 'Unknown platform: ' . $platform,
			'deactivate' => false,
		);
	}

	/**
	 * Send to iOS device
	 */
	private function send_to_ios( $token, $message, $settings ) {
		if ( empty( $settings['apns_enabled'] ) ) {
			return array(
				'success'    => false,
				'error'      => 'APNs is not enabled',
				'deactivate' => false,
			);
		}

		// Decrypt private key
		$private_key = $this->decrypt_apns_key( $settings['apns_key_file'] );
		if ( false === $private_key ) {
			return array(
				'success'    => false,
				'error'      => 'Failed to decrypt APNs private key',
				'deactivate' => false,
			);
		}

		// Create APNs client
		$client = new VH360_APNs_Client(
			$settings['apns_key_id'],
			$settings['apns_team_id'],
			$settings['apns_bundle_id'],
			$private_key,
			$settings['apns_environment'] ?? 'production'
		);

		// Build payload
		$payload = array(
			'aps' => array(
				'alert' => array(
					'title' => $message['title'] ?? '',
					'body'  => $message['body'] ?? '',
				),
				'sound' => 'default',
			),
		);

		// Add click URL if provided
		if ( ! empty( $message['click_url'] ) ) {
			$payload['url'] = $message['click_url'];
		}

		// Send
		return $client->send( $token, $payload );
	}

	/**
	 * Send to Android device
	 */
	private function send_to_android( $token, $message, $settings ) {
		if ( empty( $settings['fcm_enabled'] ) ) {
			return array(
				'success'    => false,
				'error'      => 'FCM is not enabled',
				'deactivate' => false,
			);
		}

		// Create FCM client
		$client = new VH360_FCM_Client(
			$settings['fcm_project_id'],
			$settings['fcm_server_key']
		);

		// Build notification
		$notification = array(
			'title' => $message['title'] ?? '',
			'body'  => $message['body'] ?? '',
		);

		// Build data
		$data = array();
		if ( ! empty( $message['click_url'] ) ) {
			$data['url'] = $message['click_url'];
		}

		// Send
		return $client->send( $token, $notification, $data );
	}

	/**
	 * Encrypt APNs private key
	 */
	public function encrypt_apns_key( $key_contents ) {
		$method = 'AES-256-CBC';
		
		// Use a deterministic but secure key derivation from WordPress salts
		// This allows us to decrypt later without storing the key separately
		$key = hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
		
		// Generate a random IV for this encryption
		$iv = openssl_random_pseudo_bytes( 16 );
		
		// Encrypt the data
		$encrypted = openssl_encrypt( $key_contents, $method, $key, OPENSSL_RAW_DATA, $iv );
		
		// Prepend IV to encrypted data (it's safe to store IV with encrypted data)
		$encrypted_with_iv = $iv . $encrypted;
		
		return base64_encode( $encrypted_with_iv );
	}

	/**
	 * Decrypt APNs private key
	 */
	public function decrypt_apns_key( $encrypted_data ) {
		if ( empty( $encrypted_data ) ) {
			return false;
		}

		$method = 'AES-256-CBC';
		
		// Use the same deterministic key derivation
		$key = hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
		
		// Decode from base64
		$decoded = base64_decode( $encrypted_data );
		if ( false === $decoded ) {
			return false;
		}
		
		// Extract IV from the beginning (first 16 bytes)
		$iv = substr( $decoded, 0, 16 );
		$encrypted = substr( $decoded, 16 );
		
		// Decrypt
		$decrypted = openssl_decrypt( $encrypted, $method, $key, OPENSSL_RAW_DATA, $iv );
		return $decrypted;
	}

	/**
	 * Validate APNs credentials
	 */
	public function validate_apns_credentials( $settings ) {
		if ( empty( $settings['apns_key_file'] ) ) {
			return array(
				'valid'   => false,
				'message' => 'No APNs private key uploaded.',
			);
		}

		$private_key = $this->decrypt_apns_key( $settings['apns_key_file'] );
		if ( false === $private_key ) {
			return array(
				'valid'   => false,
				'message' => 'Failed to decrypt APNs private key.',
			);
		}

		$client = new VH360_APNs_Client(
			$settings['apns_key_id'],
			$settings['apns_team_id'],
			$settings['apns_bundle_id'],
			$private_key,
			$settings['apns_environment'] ?? 'production'
		);

		return $client->test_connection();
	}

	/**
	 * Validate FCM credentials
	 */
	public function validate_fcm_credentials( $settings ) {
		if ( empty( $settings['fcm_server_key'] ) ) {
			return array(
				'valid'   => false,
				'message' => 'No FCM server key provided.',
			);
		}

		$client = new VH360_FCM_Client(
			$settings['fcm_project_id'],
			$settings['fcm_server_key']
		);

		return $client->test_connection();
	}

	/**
	 * Send test notification
	 */
	public function send_test( array $message, array $settings ) : array {
		// For MVP, send_test is same as send
		return $this->send( $message, array(), $settings );
	}

	/**
	 * Get adapter capabilities
	 */
	public function capabilities() : array {
		return array(
			'supports_test'     => true,
			'supports_image'    => false,
			'supports_segments' => false,
			'supports_schedule' => false,
			'supports_tracking' => false,
		);
	}
}
