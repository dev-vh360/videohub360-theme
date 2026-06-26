<?php
/**
 * App Store Readiness Checker
 * 
 * This class performs validation checks to determine if a PWA is ready
 * for app store wrapper creation. It does NOT build native apps - it only
 * validates that the necessary assets and configuration are in place.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Readiness_Checker {
	
	/**
	 * Run all readiness checks and return results.
	 * Uses transients to cache results for 5 minutes.
	 *
	 * @return array Array of check results with overall readiness score.
	 */
	public function run_checks() : array {
		$cache_key = 'vh360_pwa_appstore_readiness';
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}
		
		$results = array(
			'checks' => array(
				'https'           => $this->check_https(),
				'manifest'        => $this->check_manifest(),
				'service_worker'  => $this->check_service_worker(),
				'icons'           => $this->check_icons(),
				'app_config'      => $this->check_app_config(),
				'privacy_policy'  => $this->check_privacy_policy(),
				'support_contact' => $this->check_support_contact(),
				'marketing_icon'  => $this->check_marketing_icon(),
				'full_icon_set'   => $this->check_full_icon_set(),
			),
		);
		
		// Calculate overall readiness
		$total = count( $results['checks'] );
		$passed = 0;
		foreach ( $results['checks'] as $check ) {
			if ( ! empty( $check['passed'] ) ) {
				$passed++;
			}
		}
		
		$results['overall'] = array(
			'passed'     => $passed,
			'total'      => $total,
			'percentage' => $total > 0 ? round( ( $passed / $total ) * 100 ) : 0,
			'ready'      => $passed === $total,
		);
		
		// Cache for 5 minutes
		set_transient( $cache_key, $results, 5 * MINUTE_IN_SECONDS );
		
		return $results;
	}
	
	/**
	 * Check if HTTPS is enabled.
	 * PWAs require HTTPS for service workers to function.
	 *
	 * @return array Check result.
	 */
	private function check_https() : array {
		$is_https = is_ssl();
		
		return array(
			'passed'  => $is_https,
			'message' => $is_https 
				? __( 'Site is running on HTTPS', 'vh360-pwa-app' )
				: __( 'HTTPS is required for PWAs', 'vh360-pwa-app' ),
		);
	}
	
	/**
	 * Check if manifest is reachable and valid.
	 *
	 * @return array Check result.
	 */
	private function check_manifest() : array {
		$url = vh360_pwa_endpoint_url( VH360_PWA_MANIFEST_SLUG );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'passed'  => false,
				'message' => sprintf( 
					/* translators: %s: Error message */
					__( 'Manifest not reachable: %s', 'vh360-pwa-app' ),
					$response->get_error_message()
				),
			);
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		$passed = ( 200 === $code );
		
		return array(
			'passed'  => $passed,
			'message' => $passed 
				? __( 'Manifest is reachable', 'vh360-pwa-app' )
				: sprintf(
					/* translators: %d: HTTP status code */
					__( 'Manifest returned HTTP %d', 'vh360-pwa-app' ),
					$code
				),
		);
	}
	
	/**
	 * Check if service worker is reachable.
	 *
	 * @return array Check result.
	 */
	private function check_service_worker() : array {
		$url = vh360_pwa_endpoint_url( VH360_PWA_SW_SLUG );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'passed'  => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Service worker not reachable: %s', 'vh360-pwa-app' ),
					$response->get_error_message()
				),
			);
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		$passed = ( 200 === $code );
		
		return array(
			'passed'  => $passed,
			'message' => $passed 
				? __( 'Service worker is reachable', 'vh360-pwa-app' )
				: sprintf(
					/* translators: %d: HTTP status code */
					__( 'Service worker returned HTTP %d', 'vh360-pwa-app' ),
					$code
				),
		);
	}
	
	/**
	 * Check if required icons are present.
	 * Checks for 192px and 512px icons (either manually uploaded or generated).
	 *
	 * @return array Check result.
	 */
	private function check_icons() : array {
		$opts = vh360_pwa_get_options();
		
		// Check manually uploaded icons
		$has_192 = ! empty( $opts['icon_192'] );
		$has_512 = ! empty( $opts['icon_512'] );
		
		// Also check for generated icons
		if ( ! $has_192 || ! $has_512 ) {
			$icon_generator = new VH360_PWA_Icon_Generator();
			$generated = $icon_generator->get_generated_icons();
			
			if ( ! $has_192 && ! empty( $generated['ios'][192] ) ) {
				$has_192 = true;
			}
			if ( ! $has_512 && ! empty( $generated['ios'][512] ) ) {
				$has_512 = true;
			}
		}
		
		$passed = $has_192 && $has_512;
		
		if ( $passed ) {
			$message = __( 'Required icons (192px, 512px) are configured', 'vh360-pwa-app' );
		} else {
			$missing = array();
			if ( ! $has_192 ) {
				$missing[] = '192px';
			}
			if ( ! $has_512 ) {
				$missing[] = '512px';
			}
			$message = sprintf(
				/* translators: %s: List of missing icon sizes */
				__( 'Missing required icons: %s', 'vh360-pwa-app' ),
				implode( ', ', $missing )
			);
		}
		
		return array(
			'passed'  => $passed,
			'message' => $message,
		);
	}
	
	/**
	 * Check if app name and display mode are configured.
	 *
	 * @return array Check result.
	 */
	private function check_app_config() : array {
		$opts = vh360_pwa_get_options();
		
		$has_name = ! empty( $opts['app_name'] ) && strlen( trim( (string) $opts['app_name'] ) ) > 0;
		$has_display = ! empty( $opts['display'] ) && in_array( 
			(string) $opts['display'], 
			array( 'standalone', 'fullscreen', 'minimal-ui' ),
			true
		);
		
		$passed = $has_name && $has_display;
		
		if ( $passed ) {
			$message = __( 'App name and display mode are configured', 'vh360-pwa-app' );
		} else {
			$issues = array();
			if ( ! $has_name ) {
				$issues[] = __( 'app name', 'vh360-pwa-app' );
			}
			if ( ! $has_display ) {
				$issues[] = __( 'proper display mode', 'vh360-pwa-app' );
			}
			$message = sprintf(
				/* translators: %s: List of missing configuration items */
				__( 'Missing or invalid: %s', 'vh360-pwa-app' ),
				implode( ', ', $issues )
			);
		}
		
		return array(
			'passed'  => $passed,
			'message' => $message,
		);
	}
	
	/**
	 * Check if privacy policy URL is configured.
	 * Required for app store submissions.
	 *
	 * @return array Check result.
	 */
	private function check_privacy_policy() : array {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$ios_meta = $metadata_handler->get_ios_metadata();
		$android_meta = $metadata_handler->get_android_metadata();
		
		$ios_privacy = ! empty( $ios_meta['privacy_policy'] ) && filter_var( $ios_meta['privacy_policy'], FILTER_VALIDATE_URL );
		$android_privacy = ! empty( $android_meta['privacy_policy'] ) && filter_var( $android_meta['privacy_policy'], FILTER_VALIDATE_URL );
		
		$passed = $ios_privacy || $android_privacy;
		
		return array(
			'passed'  => $passed,
			'message' => $passed
				? __( 'Privacy policy URL configured', 'vh360-pwa-app' )
				: __( 'Privacy policy URL required for store submission', 'vh360-pwa-app' ),
		);
	}
	
	/**
	 * Check if support contact is configured.
	 * Required for app store submissions.
	 *
	 * @return array Check result.
	 */
	private function check_support_contact() : array {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$ios_meta = $metadata_handler->get_ios_metadata();
		$android_meta = $metadata_handler->get_android_metadata();
		
		$ios_contact = ! empty( $ios_meta['support_email'] ) && is_email( $ios_meta['support_email'] );
		$android_contact = ! empty( $android_meta['support_email'] ) && is_email( $android_meta['support_email'] );
		
		$passed = $ios_contact || $android_contact;
		
		return array(
			'passed'  => $passed,
			'message' => $passed
				? __( 'Support contact configured', 'vh360-pwa-app' )
				: __( 'Support email required for store submission', 'vh360-pwa-app' ),
		);
	}
	
	/**
	 * Check if 1024×1024 marketing icon exists.
	 * Required for iOS App Store submissions.
	 *
	 * @return array Check result.
	 */
	private function check_marketing_icon() : array {
		$icon_generator = new VH360_PWA_Icon_Generator();
		$generated = $icon_generator->get_generated_icons();
		
		$has_marketing_icon = false;
		if ( ! empty( $generated['ios']['1024'] ) ) {
			$icon_path = $icon_generator->get_upload_dir() . '/' . $generated['ios']['1024'];
			$has_marketing_icon = file_exists( $icon_path );
		}
		
		return array(
			'passed'  => $has_marketing_icon,
			'message' => $has_marketing_icon
				? __( '1024×1024 marketing icon present', 'vh360-pwa-app' )
				: __( 'Generate icons to create 1024×1024 marketing icon', 'vh360-pwa-app' ),
		);
	}
	
	/**
	 * Check if full icon set is present for both platforms.
	 * Validates that all required iOS and Android icons exist.
	 *
	 * @return array Check result.
	 */
	private function check_full_icon_set() : array {
		$icon_generator = new VH360_PWA_Icon_Generator();
		$generated = $icon_generator->get_generated_icons();
		$upload_dir = $icon_generator->get_upload_dir();
		
		// Required iOS sizes
		$ios_required = array( 1024, 180, 167, 152, 120 );
		$ios_complete = true;
		
		if ( ! empty( $generated['ios'] ) ) {
			foreach ( $ios_required as $size ) {
				if ( empty( $generated['ios'][ $size ] ) || ! file_exists( $upload_dir . '/' . $generated['ios'][ $size ] ) ) {
					$ios_complete = false;
					break;
				}
			}
		} else {
			$ios_complete = false;
		}
		
		// Required Android sizes
		$android_required = array( 512, 192, 144, 96, 72, 48 );
		$android_complete = true;
		
		if ( ! empty( $generated['android'] ) ) {
			foreach ( $android_required as $size ) {
				if ( empty( $generated['android'][ $size ] ) || ! file_exists( $upload_dir . '/' . $generated['android'][ $size ] ) ) {
					$android_complete = false;
					break;
				}
			}
		} else {
			$android_complete = false;
		}
		
		// Required maskable icons
		$maskable_required = array( 512, 192 );
		$maskable_complete = true;
		
		if ( ! empty( $generated['maskable'] ) ) {
			foreach ( $maskable_required as $size ) {
				if ( empty( $generated['maskable'][ $size ] ) || ! file_exists( $upload_dir . '/' . $generated['maskable'][ $size ] ) ) {
					$maskable_complete = false;
					break;
				}
			}
		} else {
			$maskable_complete = false;
		}
		
		$passed = $ios_complete && $android_complete && $maskable_complete;
		
		if ( $passed ) {
			$message = __( 'Complete icon set for iOS and Android present', 'vh360-pwa-app' );
		} else {
			$missing = array();
			if ( ! $ios_complete ) {
				$missing[] = __( 'iOS icons', 'vh360-pwa-app' );
			}
			if ( ! $android_complete ) {
				$missing[] = __( 'Android icons', 'vh360-pwa-app' );
			}
			if ( ! $maskable_complete ) {
				$missing[] = __( 'maskable icons', 'vh360-pwa-app' );
			}
			$message = sprintf(
				/* translators: %s: List of missing icon sets */
				__( 'Generate full icon set (missing: %s)', 'vh360-pwa-app' ),
				implode( ', ', $missing )
			);
		}
		
		return array(
			'passed'  => $passed,
			'message' => $message,
		);
	}
	
	/**
	 * Clear the readiness check cache.
	 */
	public function clear_cache() : void {
		delete_transient( 'vh360_pwa_appstore_readiness' );
	}
}
