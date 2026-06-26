<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * APNs Client for iOS Push Notifications
 * 
 * Handles Apple Push Notification service communication using HTTP/2 API with JWT authentication.
 */
class VH360_APNs_Client {
	/** @var string */
	private $key_id;

	/** @var string */
	private $team_id;

	/** @var string */
	private $bundle_id;

	/** @var string */
	private $private_key;

	/** @var string */
	private $environment;

	/**
	 * Constructor
	 * 
	 * @param string $key_id APNs Key ID
	 * @param string $team_id Team ID
	 * @param string $bundle_id App Bundle ID
	 * @param string $private_key_pem Private key in PEM format
	 * @param string $environment 'production' or 'sandbox'
	 */
	public function __construct( $key_id, $team_id, $bundle_id, $private_key_pem, $environment = 'production' ) {
		$this->key_id = $key_id;
		$this->team_id = $team_id;
		$this->bundle_id = $bundle_id;
		$this->private_key = $private_key_pem;
		$this->environment = $environment;
	}

	/**
	 * Generate JWT token for APNs authentication
	 * 
	 * @return string|false JWT token or false on failure
	 */
	private function generate_jwt() {
		// Check transient cache first (1 hour TTL)
		$cache_key = 'vh360_apns_jwt_' . $this->team_id;
		$cached_jwt = get_transient( $cache_key );
		if ( $cached_jwt ) {
			return $cached_jwt;
		}

		// JWT Header
		$header = array(
			'alg' => 'ES256',
			'kid' => $this->key_id,
		);

		// JWT Payload
		$payload = array(
			'iss' => $this->team_id,
			'iat' => time(),
		);

		// Encode header and payload
		$header_encoded = $this->base64_url_encode( wp_json_encode( $header ) );
		$payload_encoded = $this->base64_url_encode( wp_json_encode( $payload ) );
		$data = $header_encoded . '.' . $payload_encoded;

		// Sign with ES256
		$signature = $this->sign_es256( $data );
		if ( false === $signature ) {
			return false;
		}

		$jwt = $data . '.' . $signature;

		// Cache for 1 hour (tokens are valid for 1 hour)
		set_transient( $cache_key, $jwt, 3600 );

		return $jwt;
	}

	/**
	 * Sign data with ES256 (ECDSA with SHA-256)
	 * 
	 * @param string $data Data to sign
	 * @return string|false Base64 URL encoded signature or false on failure
	 */
	private function sign_es256( $data ) {
		// Load private key
		$key = openssl_pkey_get_private( $this->private_key );
		if ( false === $key ) {
			vh360_pwa_debug_log( 'VH360 APNs: Failed to load private key: ' . openssl_error_string() );
			return false;
		}

		// Sign the data
		$success = openssl_sign( $data, $signature, $key, OPENSSL_ALGO_SHA256 );
		openssl_free_key( $key );

		if ( ! $success ) {
			vh360_pwa_debug_log( 'VH360 APNs: Failed to sign data: ' . openssl_error_string() );
			return false;
		}

		// Convert DER signature to IEEE P1363 format (required by APNs)
		$signature = $this->der_to_p1363( $signature );
		if ( false === $signature ) {
			return false;
		}

		return $this->base64_url_encode( $signature );
	}

	/**
	 * Convert DER signature to IEEE P1363 format
	 * 
	 * @param string $der_signature DER encoded signature
	 * @return string|false P1363 signature or false on failure
	 */
	private function der_to_p1363( $der_signature ) {
		// For ES256, we need to extract r and s from DER format
		// DER format: 0x30 [total-length] 0x02 [r-length] [r] 0x02 [s-length] [s]
		
		$offset = 0;
		$length = strlen( $der_signature );
		
		// Check sequence tag
		if ( $length < 2 || ord( $der_signature[0] ) !== 0x30 ) {
			return false;
		}
		
		$offset = 2; // Skip sequence tag and length
		
		// Extract r
		if ( $offset >= $length || ord( $der_signature[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$offset++;
		
		$r_length = ord( $der_signature[ $offset ] );
		$offset++;
		
		$r = substr( $der_signature, $offset, $r_length );
		$offset += $r_length;
		
		// Extract s
		if ( $offset >= $length || ord( $der_signature[ $offset ] ) !== 0x02 ) {
			return false;
		}
		$offset++;
		
		$s_length = ord( $der_signature[ $offset ] );
		$offset++;
		
		$s = substr( $der_signature, $offset, $s_length );
		
		// Remove leading zeros if present
		$r = ltrim( $r, "\x00" );
		$s = ltrim( $s, "\x00" );
		
		// Pad to 32 bytes for ES256
		$r = str_pad( $r, 32, "\x00", STR_PAD_LEFT );
		$s = str_pad( $s, 32, "\x00", STR_PAD_LEFT );
		
		return $r . $s;
	}

	/**
	 * Base64 URL encode (without padding)
	 * 
	 * @param string $data Data to encode
	 * @return string Encoded data
	 */
	private function base64_url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Send push notification to device
	 * 
	 * @param string $device_token Device token
	 * @param array $payload Notification payload
	 * @return array Result with success, error, status_code, deactivate, apns_id
	 */
	public function send( $device_token, $payload ) {
		// Generate JWT
		$jwt = $this->generate_jwt();
		if ( false === $jwt ) {
			return array(
				'success'     => false,
				'error'       => 'Failed to generate JWT token',
				'status_code' => 0,
				'deactivate'  => false,
				'apns_id'     => null,
			);
		}

		// Build URL
		$url = $this->get_api_url() . '/3/device/' . $device_token;

		// Prepare headers
		$headers = array(
			'authorization'   => 'bearer ' . $jwt,
			'apns-topic'      => $this->bundle_id,
			'apns-push-type'  => 'alert',
			'apns-priority'   => '10',
			'apns-expiration' => '0',
			'content-type'    => 'application/json',
		);

		// Send request
		$response = wp_remote_post(
			$url,
			array(
				'headers'     => $headers,
				'body'        => wp_json_encode( $payload ),
				'timeout'     => 30,
				'httpversion' => '1.1', // WordPress doesn't support HTTP/2 via wp_remote_post
			)
		);

		return $this->parse_response( $response, $device_token );
	}

	/**
	 * Parse APNs response
	 * 
	 * @param array|WP_Error $response WordPress HTTP response
	 * @param string $device_token Device token (for logging)
	 * @return array Result array
	 */
	private function parse_response( $response, $device_token ) {
		// Handle network errors
		if ( is_wp_error( $response ) ) {
			return array(
				'success'     => false,
				'error'       => $response->get_error_message(),
				'status_code' => 0,
				'deactivate'  => false,
				'apns_id'     => null,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$headers = wp_remote_retrieve_headers( $response );
		$apns_id = $headers['apns-id'] ?? null;

		// Success
		if ( 200 === $status_code ) {
			return array(
				'success'     => true,
				'error'       => null,
				'status_code' => $status_code,
				'deactivate'  => false,
				'apns_id'     => $apns_id,
			);
		}

		// Parse error response
		$error_data = json_decode( $body, true );
		$reason = $error_data['reason'] ?? 'Unknown error';

		// Determine if token should be deactivated
		$deactivate_reasons = array( 'BadDeviceToken', 'DeviceTokenNotForTopic', 'Unregistered' );
		$should_deactivate = in_array( $reason, $deactivate_reasons, true );

		// Never deactivate on credential errors (403)
		if ( 403 === $status_code ) {
			$should_deactivate = false;
		}

		// Build error message
		$error_messages = array(
			400 => 'Bad request',
			403 => 'Certificate or token error (check credentials)',
			410 => 'Device token is no longer active',
			413 => 'Notification payload too large',
			429 => 'Rate limit exceeded',
			500 => 'APNs server error',
			503 => 'APNs server unavailable',
		);

		$error_message = $error_messages[ $status_code ] ?? 'Unknown error';
		$error_message .= ' - Reason: ' . $reason;

		return array(
			'success'     => false,
			'error'       => $error_message,
			'status_code' => $status_code,
			'deactivate'  => $should_deactivate,
			'apns_id'     => $apns_id,
		);
	}

	/**
	 * Get APNs API URL based on environment
	 * 
	 * @return string API URL
	 */
	private function get_api_url() {
		if ( 'sandbox' === $this->environment ) {
			return 'https://api.sandbox.push.apple.com';
		}
		return 'https://api.push.apple.com';
	}

	/**
	 * Test connection to APNs
	 * 
	 * @return array Result with valid and message keys
	 */
	public function test_connection() {
		// Try to generate JWT first
		$jwt = $this->generate_jwt();
		if ( false === $jwt ) {
			return array(
				'valid'   => false,
				'message' => 'Failed to generate JWT token. Check your private key format.',
			);
		}

		// Send to a dummy token to validate credentials
		$dummy_token = str_repeat( '0', 64 );
		$payload = array(
			'aps' => array(
				'alert' => array(
					'title' => 'Test',
					'body'  => 'Connection test',
				),
			),
		);

		$result = $this->send( $dummy_token, $payload );

		// We expect this to fail with BadDeviceToken, which means credentials are valid
		if ( $result['status_code'] === 400 && strpos( $result['error'], 'BadDeviceToken' ) !== false ) {
			return array(
				'valid'   => true,
				'message' => 'APNs credentials are valid. Ready to send notifications.',
			);
		}

		// 403 means credential error
		if ( $result['status_code'] === 403 ) {
			return array(
				'valid'   => false,
				'message' => 'APNs authentication failed. Check your Key ID, Team ID, and Private Key.',
			);
		}

		// Other errors
		if ( ! $result['success'] ) {
			return array(
				'valid'   => false,
				'message' => 'Connection test failed: ' . $result['error'],
			);
		}

		// Unexpected success (dummy token should always fail)
		return array(
			'valid'   => true,
			'message' => 'Connection successful (unexpected result).',
		);
	}
}
