<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push Adapter Interface
 * 
 * All push providers must implement this interface.
 * 
 * The adapter pattern allows the Push Manager to work with any push provider
 * without knowing implementation details. New providers can be added by:
 * 1. Creating a class that implements this interface
 * 2. Registering it with the Push Manager
 * 3. The admin UI will automatically generate settings fields
 * 
 * Example usage:
 * ```php
 * class MyProvider_Adapter implements VH360_PWA_Push_Adapter_Interface {
 *     public function get_slug() : string {
 *         return 'myprovider';
 *     }
 *     // ... implement other methods
 * }
 * 
 * $push_manager = VH360_PWA_App::instance()->push_manager;
 * $adapter = new MyProvider_Adapter();
 * $push_manager->register_adapter( $adapter );
 * ```
 */
interface VH360_PWA_Push_Adapter_Interface {
	/**
	 * Get adapter slug (e.g., "onesignal")
	 */
	public function get_slug() : string;

	/**
	 * Get adapter label (e.g., "OneSignal")
	 */
	public function get_label() : string;

	/**
	 * Get settings fields for the Setup UI
	 * 
	 * @return array Array of field definitions
	 */
	public function get_settings_fields() : array;

	/**
	 * Validate settings
	 * 
	 * @param array $settings Provider settings
	 * @return array List of actionable error messages (empty if valid)
	 */
	public function validate_settings( array $settings ) : array;

	/**
	 * Enqueue frontend SDK scripts
	 * 
	 * @param array $settings Provider settings
	 */
	public function enqueue_frontend_sdk( array $settings ) : void;

	/**
	 * Get frontend bootstrap configuration (safe for JS)
	 * 
	 * @param array $settings Provider settings
	 * @return array Safe public config (no secrets)
	 */
	public function get_frontend_bootstrap( array $settings ) : array;

	/**
	 * Send notification
	 * 
	 * @param array $message Message data (title, body, click_url, etc.)
	 * @param array $audience Audience targeting data
	 * @param array $settings Provider settings
	 * @return array Delivery result (success, response, timestamp, etc.)
	 */
	public function send( array $message, array $audience, array $settings ) : array;

	/**
	 * Send test notification
	 * 
	 * @param array $message Message data
	 * @param array $settings Provider settings
	 * @return array Delivery result
	 */
	public function send_test( array $message, array $settings ) : array;

	/**
	 * Get adapter capabilities
	 * 
	 * @return array Capability flags (supports_image, supports_segments, etc.)
	 */
	public function capabilities() : array;
}
