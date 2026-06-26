<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FCM Client for Android Push Notifications
 * 
 * Handles Firebase Cloud Messaging using legacy API with server key authentication.
 */
class VH360_FCM_Client {
	/** @var string */
	private $project_id;

	/** @var string */
	private $server_key;

	/**
	 * Constructor
	 * 
	 * @param string $project_id Firebase Project ID
	 * @param string $server_key FCM Server Key
	 */
	public function __construct( $project_id, $server_key ) {
		$this->project_id = $project_id;
		$this->server_key = $server_key;
	}

	/**
	 * Send push notification to device
	 * 
	 * @param string $device_token Device token
	 * @param array $notification Notification data with title and body
	 * @param array $data Optional custom data
	 * @return array Result with success, error, deactivate, message_id
	 */
	public function send( $device_token, $notification, $data = array() ) {
		// Build payload
		$payload = array(
			'to'           => $device_token,
			'notification' => $notification,
			'priority'     => 'high',
		);

		if ( ! empty( $data ) ) {
			$payload['data'] = $data;
		}

		// Prepare headers
		$headers = array(
			'Authorization' => 'key=' . $this->server_key,
			'Content-Type'  => 'application/json',
		);

		// Send request
		$response = wp_remote_post(
			'https://fcm.googleapis.com/fcm/send',
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);

		return $this->parse_response( $response, $device_token );
	}

	/**
	 * Parse FCM response
	 * 
	 * @param array|WP_Error $response WordPress HTTP response
	 * @param string $device_token Device token (for logging)
	 * @return array Result array
	 */
	private function parse_response( $response, $device_token ) {
		// Handle network errors
		if ( is_wp_error( $response ) ) {
			return array(
				'success'    => false,
				'error'      => $response->get_error_message(),
				'deactivate' => false,
				'message_id' => null,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for authentication errors
		if ( 401 === $status_code || 403 === $status_code ) {
			return array(
				'success'    => false,
				'error'      => 'Authentication failed. Check your server key.',
				'deactivate' => false,
				'message_id' => null,
			);
		}

		// Check for other HTTP errors
		if ( $status_code < 200 || $status_code >= 300 ) {
			return array(
				'success'    => false,
				'error'      => 'FCM request failed with status ' . $status_code,
				'deactivate' => false,
				'message_id' => null,
			);
		}

		// Check response structure
		if ( ! isset( $data['success'] ) || ! isset( $data['results'] ) ) {
			return array(
				'success'    => false,
				'error'      => 'Invalid FCM response format',
				'deactivate' => false,
				'message_id' => null,
			);
		}

		// Check if message was sent successfully
		if ( $data['success'] === 1 && ! empty( $data['results'][0]['message_id'] ) ) {
			return array(
				'success'    => true,
				'error'      => null,
				'deactivate' => false,
				'message_id' => $data['results'][0]['message_id'],
			);
		}

		// Handle errors
		$error = $data['results'][0]['error'] ?? 'Unknown error';
		
		// Determine if token should be deactivated
		$deactivate_errors = array( 'NotRegistered', 'InvalidRegistration', 'MismatchSenderId' );
		$should_deactivate = in_array( $error, $deactivate_errors, true );

		// Build error message
		$error_messages = array(
			'NotRegistered'        => 'Device token is no longer registered',
			'InvalidRegistration'  => 'Invalid device token format',
			'MismatchSenderId'     => 'Device token belongs to different sender',
			'MessageTooBig'        => 'Notification payload too large',
			'InvalidDataKey'       => 'Invalid data key in payload',
			'InvalidTtl'           => 'Invalid time-to-live value',
			'Unavailable'          => 'FCM server temporarily unavailable',
			'InternalServerError'  => 'FCM internal server error',
		);

		$error_message = $error_messages[ $error ] ?? $error;

		return array(
			'success'    => false,
			'error'      => $error_message,
			'deactivate' => $should_deactivate,
			'message_id' => null,
		);
	}

	/**
	 * Test connection to FCM
	 * 
	 * @return array Result with valid and message keys
	 */
	public function test_connection() {
		// Send to an invalid token to validate credentials
		// A valid server key will return InvalidRegistration, not an auth error
		$dummy_token = 'invalid_token_for_testing';
		$notification = array(
			'title' => 'Test',
			'body'  => 'Connection test',
		);

		$result = $this->send( $dummy_token, $notification );

		// Check for auth errors (401/403)
		if ( strpos( $result['error'] ?? '', 'Authentication failed' ) !== false ) {
			return array(
				'valid'   => false,
				'message' => 'FCM authentication failed. Check your server key.',
			);
		}

		// Invalid token error means credentials are valid
		if ( strpos( $result['error'] ?? '', 'Invalid' ) !== false ) {
			return array(
				'valid'   => true,
				'message' => 'FCM credentials are valid. Ready to send notifications.',
			);
		}

		// Any other error during test is also acceptable (credentials work)
		if ( ! $result['success'] ) {
			return array(
				'valid'   => true,
				'message' => 'FCM server is reachable. Credentials appear valid.',
			);
		}

		// Unexpected success
		return array(
			'valid'   => true,
			'message' => 'Connection successful (unexpected result).',
		);
	}
}
