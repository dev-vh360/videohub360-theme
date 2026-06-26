<?php
/**
 * App Store Metadata Storage
 * 
 * Manages storage and retrieval of app store metadata for iOS and Android.
 * This metadata is used for export only - it is NOT published to the frontend
 * or used to build native apps directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Store_Metadata {
	
	const OPTION_IOS = 'vh360_pwa_appstore_metadata_ios';
	const OPTION_ANDROID = 'vh360_pwa_appstore_metadata_android';
	
	/**
	 * Get iOS metadata with defaults.
	 *
	 * @return array iOS metadata.
	 */
	public function get_ios_metadata() : array {
		$defaults = $this->get_ios_defaults();
		$stored = get_option( self::OPTION_IOS, array() );
		
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		
		return wp_parse_args( $stored, $defaults );
	}
	
	/**
	 * Get Android metadata with defaults.
	 *
	 * @return array Android metadata.
	 */
	public function get_android_metadata() : array {
		$defaults = $this->get_android_defaults();
		$stored = get_option( self::OPTION_ANDROID, array() );
		
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		
		return wp_parse_args( $stored, $defaults );
	}
	
	/**
	 * Save iOS metadata.
	 *
	 * @param array $data iOS metadata to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_ios_metadata( array $data ) : bool {
		$sanitized = $this->sanitize_ios_metadata( $data );
		return update_option( self::OPTION_IOS, $sanitized );
	}
	
	/**
	 * Save Android metadata.
	 *
	 * @param array $data Android metadata to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_android_metadata( array $data ) : bool {
		$sanitized = $this->sanitize_android_metadata( $data );
		return update_option( self::OPTION_ANDROID, $sanitized );
	}
	
	/**
	 * Get default iOS metadata.
	 *
	 * @return array Default iOS metadata.
	 */
	private function get_ios_defaults() : array {
		return array(
			'app_title'         => get_bloginfo( 'name' ),
			'short_description' => get_bloginfo( 'description' ),
			'full_description'  => '',
			'category'          => '',
			'privacy_policy'    => get_privacy_policy_url(),
			'support_email'     => get_option( 'admin_email' ),
			'keywords'          => '',
			'app_store_id'      => '',
		);
	}
	
	/**
	 * Get default Android metadata.
	 *
	 * @return array Default Android metadata.
	 */
	private function get_android_defaults() : array {
		return array(
			'app_title'         => get_bloginfo( 'name' ),
			'short_description' => get_bloginfo( 'description' ),
			'full_description'  => '',
			'category'          => '',
			'privacy_policy'    => get_privacy_policy_url(),
			'support_email'     => get_option( 'admin_email' ),
			'keywords'          => '',
			'package_name'      => '',
		);
	}
	
	/**
	 * Sanitize iOS metadata input.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	private function sanitize_ios_metadata( array $input ) : array {
		$current = $this->get_ios_metadata();
		
		return array(
			'app_title'         => $this->sanitize_limited_text( $input['app_title'] ?? $current['app_title'], 30 ),
			'short_description' => $this->sanitize_limited_text( $input['short_description'] ?? $current['short_description'], 80 ),
			'full_description'  => $this->sanitize_limited_text( $input['full_description'] ?? $current['full_description'], 4000 ),
			'category'          => sanitize_text_field( $input['category'] ?? $current['category'] ),
			'privacy_policy'    => esc_url_raw( $input['privacy_policy'] ?? $current['privacy_policy'] ),
			'support_email'     => sanitize_email( $input['support_email'] ?? $current['support_email'] ),
			'keywords'          => sanitize_text_field( $input['keywords'] ?? $current['keywords'] ),
			'app_store_id'      => sanitize_text_field( $input['app_store_id'] ?? $current['app_store_id'] ),
		);
	}
	
	/**
	 * Sanitize Android metadata input.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	private function sanitize_android_metadata( array $input ) : array {
		$current = $this->get_android_metadata();
		
		return array(
			'app_title'         => $this->sanitize_limited_text( $input['app_title'] ?? $current['app_title'], 30 ),
			'short_description' => $this->sanitize_limited_text( $input['short_description'] ?? $current['short_description'], 80 ),
			'full_description'  => $this->sanitize_limited_text( $input['full_description'] ?? $current['full_description'], 4000 ),
			'category'          => sanitize_text_field( $input['category'] ?? $current['category'] ),
			'privacy_policy'    => esc_url_raw( $input['privacy_policy'] ?? $current['privacy_policy'] ),
			'support_email'     => sanitize_email( $input['support_email'] ?? $current['support_email'] ),
			'keywords'          => sanitize_text_field( $input['keywords'] ?? $current['keywords'] ),
			'package_name'      => sanitize_text_field( $input['package_name'] ?? $current['package_name'] ),
		);
	}
	
	/**
	 * Sanitize text with character limit.
	 *
	 * @param string $text Text to sanitize.
	 * @param int    $max_length Maximum character length.
	 * @return string Sanitized text.
	 */
	private function sanitize_limited_text( $text, int $max_length ) : string {
		$text = sanitize_text_field( (string) $text );
		
		// Use mb_strlen for proper UTF-8 character counting
		if ( mb_strlen( $text ) > $max_length ) {
			$text = mb_substr( $text, 0, $max_length );
		}
		
		return $text;
	}
	
	/**
	 * Get available app categories for both platforms.
	 *
	 * @return array Array of category options.
	 */
	public function get_categories() : array {
		return array(
			''              => __( '-- Select Category --', 'vh360-pwa-app' ),
			'business'      => __( 'Business', 'vh360-pwa-app' ),
			'education'     => __( 'Education', 'vh360-pwa-app' ),
			'entertainment' => __( 'Entertainment', 'vh360-pwa-app' ),
			'finance'       => __( 'Finance', 'vh360-pwa-app' ),
			'food'          => __( 'Food & Drink', 'vh360-pwa-app' ),
			'health'        => __( 'Health & Fitness', 'vh360-pwa-app' ),
			'lifestyle'     => __( 'Lifestyle', 'vh360-pwa-app' ),
			'music'         => __( 'Music', 'vh360-pwa-app' ),
			'news'          => __( 'News', 'vh360-pwa-app' ),
			'photo'         => __( 'Photo & Video', 'vh360-pwa-app' ),
			'productivity'  => __( 'Productivity', 'vh360-pwa-app' ),
			'shopping'      => __( 'Shopping', 'vh360-pwa-app' ),
			'social'        => __( 'Social Networking', 'vh360-pwa-app' ),
			'sports'        => __( 'Sports', 'vh360-pwa-app' ),
			'travel'        => __( 'Travel', 'vh360-pwa-app' ),
			'utilities'     => __( 'Utilities', 'vh360-pwa-app' ),
		);
	}
}
