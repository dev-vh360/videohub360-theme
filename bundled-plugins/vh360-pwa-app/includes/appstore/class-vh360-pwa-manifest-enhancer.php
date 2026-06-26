<?php
/**
 * Manifest Enhancer
 * 
 * Provides additional manifest fields for app store readiness.
 * This class enhances the data structure only - it does NOT build native apps.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Manifest_Enhancer {
	
	/**
	 * Get enhanced manifest data suitable for app store wrappers.
	 * This includes all standard manifest fields plus store-specific enhancements.
	 *
	 * @return array Enhanced manifest data.
	 */
	public function get_enhanced_manifest() : array {
		$opts = vh360_pwa_get_options();
		
		// Start with base manifest structure from endpoints
		$manifest = array(
			'name'             => (string) $opts['app_name'],
			'short_name'       => (string) $opts['short_name'],
			'description'      => (string) $opts['description'],
			'start_url'        => (string) $opts['start_url'],
			'scope'            => (string) $opts['scope'],
			'display'          => (string) $opts['display'],
			'orientation'      => (string) $opts['orientation'],
			'theme_color'      => (string) $opts['theme_color'],
			'background_color' => (string) $opts['background_color'],
			'lang'             => (string) $opts['lang'],
		);
		
		// Add icons
		$manifest['icons'] = $this->get_icons( $opts );
		
		// Add optional store-ready fields
		$screenshots = $this->get_screenshots();
		if ( ! empty( $screenshots ) ) {
			$manifest['screenshots'] = $screenshots;
		}
		
		$categories = $this->get_categories();
		if ( ! empty( $categories ) ) {
			$manifest['categories'] = $categories;
		}
		
		$shortcuts = $this->get_shortcuts();
		if ( ! empty( $shortcuts ) ) {
			$manifest['shortcuts'] = $shortcuts;
		}
		
		$related_apps = $this->get_related_applications();
		if ( ! empty( $related_apps ) ) {
			$manifest['related_applications'] = $related_apps;
		}
		
		return $manifest;
	}
	
	/**
	 * Get all configured icons including maskable variants.
	 * Prefers locally generated icons over manually uploaded ones.
	 *
	 * @param array $opts Plugin options.
	 * @return array Array of icon objects.
	 */
	private function get_icons( array $opts ) : array {
		return function_exists( 'vh360_pwa_get_manifest_icons' ) ? vh360_pwa_get_manifest_icons() : array();
	}

	/**
	 * Get screenshots for app stores.
	 * Currently returns empty array - screenshots can be added via admin UI in future.
	 *
	 * @return array Array of screenshot objects.
	 */
	private function get_screenshots() : array {
		// Future enhancement: Add UI for uploading screenshots
		// For now, return empty array
		return array();
	}
	
	/**
	 * Get manifest categories.
	 *
	 * @return array Array of categories.
	 */
	private function get_categories() : array {
		$metadata = new VH360_PWA_Store_Metadata();
		$ios_data = $metadata->get_ios_metadata();
		$android_data = $metadata->get_android_metadata();
		
		$categories = array();
		
		// Use iOS category if set, otherwise Android
		if ( ! empty( $ios_data['category'] ) ) {
			$categories[] = $ios_data['category'];
		} elseif ( ! empty( $android_data['category'] ) ) {
			$categories[] = $android_data['category'];
		}
		
		return array_filter( array_unique( $categories ) );
	}
	
	/**
	 * Get app shortcuts.
	 * Currently returns empty array - shortcuts can be added in future.
	 *
	 * @return array Array of shortcut objects.
	 */
	private function get_shortcuts() : array {
		// Future enhancement: Add UI for configuring shortcuts
		// For now, return empty array
		return array();
	}
	
	/**
	 * Get related applications for app store linking.
	 * Only includes entries when configured.
	 *
	 * @return array Array of related application objects.
	 */
	private function get_related_applications() : array {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$ios_meta = $metadata_handler->get_ios_metadata();
		$android_meta = $metadata_handler->get_android_metadata();
		
		$related = array();
		
		// Add Android app if package name is configured
		if ( ! empty( $android_meta['package_name'] ) ) {
			$package_name = sanitize_text_field( $android_meta['package_name'] );
			$related[] = array(
				'platform' => 'play',
				'url'      => 'https://play.google.com/store/apps/details?id=' . urlencode( $package_name ),
				'id'       => $package_name,
			);
		}
		
		// Add iOS app if App Store ID is configured
		if ( ! empty( $ios_meta['app_store_id'] ) ) {
			// Validate that App Store ID contains only numbers
			$app_store_id = preg_replace( '/[^0-9]/', '', $ios_meta['app_store_id'] );
			if ( ! empty( $app_store_id ) ) {
				$related[] = array(
					'platform' => 'itunes',
					'url'      => 'https://apps.apple.com/app/id' . $app_store_id,
				);
			}
		}
		
		return $related;
	}
	
	/**
	 * Validate icon dimensions and file existence.
	 *
	 * @param string $icon_url URL of the icon to validate.
	 * @param int    $expected_width Expected width in pixels.
	 * @param int    $expected_height Expected height in pixels.
	 * @return array Validation result with 'valid' boolean and 'message' string.
	 */
	public function validate_icon( string $icon_url, int $expected_width, int $expected_height ) : array {
		if ( empty( $icon_url ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Icon URL is empty', 'vh360-pwa-app' ),
			);
		}
		
		// Try to get image dimensions
		$response = wp_remote_get( $icon_url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Could not fetch icon: %s', 'vh360-pwa-app' ),
					$response->get_error_message()
				),
			);
		}
		
		$body = wp_remote_retrieve_body( $response );
		
		// Suppress warnings for invalid image data, but handle the error properly
		$image_data = getimagesizefromstring( $body );
		
		if ( false === $image_data || ! is_array( $image_data ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid image file', 'vh360-pwa-app' ),
			);
		}
		
		list( $width, $height ) = $image_data;
		
		if ( $width !== $expected_width || $height !== $expected_height ) {
			return array(
				'valid'   => false,
				'message' => sprintf(
					/* translators: 1: Actual dimensions, 2: Expected dimensions */
					__( 'Icon dimensions are %1$s, expected %2$s', 'vh360-pwa-app' ),
					"{$width}x{$height}",
					"{$expected_width}x{$expected_height}"
				),
			);
		}
		
		return array(
			'valid'   => true,
			'message' => __( 'Icon is valid', 'vh360-pwa-app' ),
		);
	}
}
