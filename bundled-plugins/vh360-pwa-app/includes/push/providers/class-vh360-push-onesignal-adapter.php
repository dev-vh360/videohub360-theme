<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OneSignal Push Adapter
 * 
 * Implements OneSignal Web Push integration.
 */
class VH360_Push_OneSignal_Adapter implements VH360_PWA_Push_Adapter_Interface {

	/**
	 * Default segment for sending to all subscribers
	 */
	const DEFAULT_SEGMENT = 'All';

	/**
	 * Get adapter slug
	 */
	public function get_slug() : string {
		return 'onesignal';
	}

	/**
	 * Get adapter label
	 */
	public function get_label() : string {
		return 'OneSignal';
	}

	/**
	 * Get settings fields for the Setup UI
	 */
	public function get_settings_fields() : array {
		return array(
			array(
				'key'         => 'app_id',
				'label'       => __( 'OneSignal App ID', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => true,
				'description' => __( 'Your OneSignal App ID from the Keys & IDs page.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'rest_api_key',
				'label'       => __( 'OneSignal REST API Key', 'vh360-pwa-app' ),
				'type'        => 'password',
				'required'    => true,
				'description' => __( 'Your OneSignal REST API Key from the Keys & IDs page. This key is stored securely and never exposed to the browser.', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'default_click_url',
				'label'       => __( 'Default Click URL', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Default URL when a notification is clicked (optional).', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'default_icon_url',
				'label'       => __( 'Default Icon URL', 'vh360-pwa-app' ),
				'type'        => 'text',
				'required'    => false,
				'description' => __( 'Default icon URL for notifications (optional).', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'auto_prompt',
				'label'       => __( 'Auto-prompt on page load', 'vh360-pwa-app' ),
				'type'        => 'checkbox',
				'required'    => false,
				'description' => __( 'Automatically prompt users for notification permission (not recommended).', 'vh360-pwa-app' ),
			),
			array(
				'key'         => 'auto_prompt_delay',
				'label'       => __( 'Auto-prompt delay (seconds)', 'vh360-pwa-app' ),
				'type'        => 'number',
				'required'    => false,
				'description' => __( 'Delay before showing auto-prompt (if enabled).', 'vh360-pwa-app' ),
			),
		);
	}

	/**
	 * Validate settings
	 */
	public function validate_settings( array $settings ) : array {
		$errors = array();

		if ( empty( $settings['app_id'] ) ) {
			$errors[] = __( 'OneSignal App ID is required.', 'vh360-pwa-app' );
		} elseif ( ! preg_match( '/^[a-f0-9-]{36}$/i', trim( $settings['app_id'] ) ) ) {
			$errors[] = __( 'OneSignal App ID format is invalid. It should be a UUID (e.g., 12345678-1234-1234-1234-123456789abc).', 'vh360-pwa-app' );
		}

		if ( empty( $settings['rest_api_key'] ) ) {
			$errors[] = __( 'OneSignal REST API Key is required.', 'vh360-pwa-app' );
		} elseif ( strlen( trim( $settings['rest_api_key'] ) ) < 30 ) {
			$errors[] = __( 'OneSignal REST API Key appears to be too short. Please check that you entered the complete key.', 'vh360-pwa-app' );
		}

		return $errors;
	}

	/**
	 * Enqueue frontend SDK scripts
	 */
	public function enqueue_frontend_sdk( array $settings ) : void {
		// Validate before enqueueing
		$errors = $this->validate_settings( $settings );
		if ( ! empty( $errors ) ) {
			return; // Don't load SDK if config is invalid
		}

		$has_preferences_consent = ! function_exists('videohub360_has_consent') || videohub360_has_consent('preferences');

		// OneSignal SDK
		$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
		if ( $has_preferences_consent ) {
		wp_enqueue_script(
			'onesignal-sdk',
			'https://cdn.onesignal.com/sdks/web/' . $sdk_version . '/OneSignalSDK.page.js',
			array(),
			null,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		}

		// Our initialization script
		wp_enqueue_script(
			'vh360-pwa-push-public',
			VH360_PWA_APP_URL . 'assets/public/push-public.js',
			$has_preferences_consent ? array( 'onesignal-sdk' ) : array(),
			vh360_pwa_app_asset_version('assets/public/push-public.js'),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Get frontend bootstrap configuration (safe for JS)
	 */
	public function get_frontend_bootstrap( array $settings ) : array {
		// Never expose REST API key to frontend
		// Use the VH360 service worker (vh360-sw.js) as the single SW for scope '/'
		// and merge OneSignal into it. This prevents SW scope conflicts.
		return array(
			'provider'         => 'onesignal',
			'appId'            => $settings['app_id'] ?? '',
			'swPath'           => '/' . VH360_PWA_SW_SLUG,
			'swUpdaterPath'    => '/' . VH360_PWA_SW_SLUG,
			'swScope'          => $settings['sw_scope'] ?? '/',
			'autoPrompt'       => ! empty( $settings['auto_prompt'] ),
			'autoPromptDelay'  => absint( $settings['auto_prompt_delay'] ?? 0 ),
			'autoPromptScroll' => ! empty( $settings['auto_prompt_scroll'] ),
			'autoPromptLogin'  => ! empty( $settings['auto_prompt_login'] ),
		);
	}

	/**
	 * Send notification
	 */
	public function send( array $message, array $audience, array $settings ) : array {
		$app_id = trim( $settings['app_id'] ?? '' );
		$api_key = trim( $settings['rest_api_key'] ?? '' );

		if ( ! $app_id || ! $api_key ) {
			return array(
				'success' => false,
				'error'   => __( 'OneSignal App ID or REST API Key is missing.', 'vh360-pwa-app' ),
			);
		}

		// Build notification payload
		$payload = array(
			'app_id'   => $app_id,
			'headings' => array( 'en' => $message['title'] ?? 'Notification' ),
			'contents' => array( 'en' => $message['body'] ?? '' ),
		);

		// Click URL
		if ( ! empty( $message['click_url'] ) ) {
			$payload['url'] = esc_url_raw( $message['click_url'] );
		} elseif ( ! empty( $settings['default_click_url'] ) ) {
			$payload['url'] = esc_url_raw( $settings['default_click_url'] );
		}

		// Icon
		if ( ! empty( $message['icon_url'] ) ) {
			$payload['large_icon'] = esc_url_raw( $message['icon_url'] );
		} elseif ( ! empty( $settings['default_icon_url'] ) ) {
			$payload['large_icon'] = esc_url_raw( $settings['default_icon_url'] );
		}

		// Audience targeting
		if (!empty($audience['user_ids']) && is_array($audience['user_ids'])) {
			// Target specific WordPress users by external user ID
			$payload['include_external_user_ids'] = array_map('strval', $audience['user_ids']);
		} else {
			// Fallback: broadcast to all subscribers
			$payload['included_segments'] = array( self::DEFAULT_SEGMENT );
		}

		// Send via OneSignal API
		$response = wp_remote_post(
			'https://onesignal.com/api/v1/notifications',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $api_key,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 === $code ) {
			return array(
				'success'     => true,
				'response_id' => $data['id'] ?? null,
				'recipients'  => $data['recipients'] ?? 0,
				'response'    => $data,
			);
		}

		// Error
		$error_message = __( 'OneSignal API error', 'vh360-pwa-app' );
		if ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
			$error_message .= ': ' . implode( ', ', $data['errors'] );
		} elseif ( ! empty( $body ) ) {
			$error_message .= ': ' . $body;
		}

		return array(
			'success'  => false,
			'error'    => $error_message,
			'http_code' => $code,
			'response' => $data,
		);
	}

	/**
	 * Send test notification
	 */
	public function send_test( array $message, array $settings ) : array {
		// For Phase 1, test sends to "All subscribers"
		// OneSignal doesn't have a separate test mode in Phase 1
		return $this->send( $message, array( 'all' => true ), $settings );
	}

	/**
	 * Get adapter capabilities
	 */
	public function capabilities() : array {
		return array(
			'supports_image'    => true,
			'supports_segments' => true,
			'supports_tags'     => true,
			'supports_test'     => true,
		);
	}

	/**
	 * Get subscriber count from OneSignal API
	 * 
	 * @param array $settings Provider settings containing:
	 *                        - app_id (string): OneSignal App ID
	 *                        - rest_api_key (string): OneSignal REST API Key
	 * @return array Result array containing:
	 *               - success (bool): Whether the API call succeeded
	 *               - count (int): Number of subscribers (only if success=true)
	 *               - error (string): Error message (only if success=false)
	 */
	public function get_subscriber_count( array $settings ) : array {
		// Check cache first (5 minute TTL)
		$cache_key = 'vh360_push_subscriber_count';
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$app_id = trim( $settings['app_id'] ?? '' );
		$api_key = trim( $settings['rest_api_key'] ?? '' );

		if ( ! $app_id || ! $api_key ) {
			return array(
				'success' => false,
				'error'   => __( 'OneSignal App ID or REST API Key is missing.', 'vh360-pwa-app' ),
			);
		}

		// Fetch app details from OneSignal API
		$response = wp_remote_get(
			'https://onesignal.com/api/v1/apps/' . $app_id,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $api_key,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			$result = array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( 200 === $code && isset( $data['players'] ) ) {
				$result = array(
					'success' => true,
					'count'   => absint( $data['players'] ),
				);
			} else {
				$error_message = __( 'Failed to fetch subscriber count', 'vh360-pwa-app' );
				if ( isset( $data['errors'] ) && is_array( $data['errors'] ) ) {
					$error_message .= ': ' . implode( ', ', $data['errors'] );
				}
				$result = array(
					'success'  => false,
					'error'    => $error_message,
					'http_code' => $code,
				);
			}
		}

		// Cache successful results for 5 minutes
		if ( ! empty( $result['success'] ) ) {
			set_transient( $cache_key, $result, 300 );
		}

		return $result;
	}
}
