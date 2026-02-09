<?php
/**
 * Export Package Generator
 * 
 * Generates ZIP files containing PWA data and metadata for iOS and Android wrapper creation.
 * This class does NOT build native apps - it only prepares and exports data for external tools.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Export_Package {
	
	/**
	 * Generate iOS wrapper pack and send as download.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function export_ios_pack() : bool {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}
		
		$temp_file = $this->create_temp_file();
		if ( ! $temp_file ) {
			return false;
		}
		
		$zip = new ZipArchive();
		if ( true !== $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			if ( file_exists( $temp_file ) ) {
				unlink( $temp_file );
			}
			return false;
		}
		
		// Add manifest.json
		$manifest = $this->get_manifest_json();
		$zip->addFromString( 'manifest.json', $manifest );
		
		// Add icons (iOS specific)
		$this->add_icons_to_zip( $zip, 'ios' );
		
		// Add iOS metadata JSON
		$metadata = $this->get_ios_metadata_json();
		$zip->addFromString( 'metadata-ios.json', $metadata );
		
		// Add consolidated store metadata
		$store_metadata = $this->get_consolidated_store_metadata( 'ios' );
		$zip->addFromString( 'store-metadata.json', $store_metadata );
		
		// Add Capacitor configuration template
		$capacitor_config = $this->get_capacitor_config_template();
		$zip->addFromString( 'capacitor.config.json', $capacitor_config );
		
		// Add README
		$readme = $this->get_ios_readme();
		$zip->addFromString( 'README-iOS.txt', $readme );
		
		$zip->close();
		
		// Send download
		$filename = 'vh360-ios-export-' . gmdate( 'Y-m-d' ) . '.zip';
		$this->send_download( $temp_file, $filename );
		
		// Clean up
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
		
		return true;
	}
	
	/**
	 * Generate Android wrapper pack and send as download.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function export_android_pack() : bool {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}
		
		$temp_file = $this->create_temp_file();
		if ( ! $temp_file ) {
			return false;
		}
		
		$zip = new ZipArchive();
		if ( true !== $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			if ( file_exists( $temp_file ) ) {
				unlink( $temp_file );
			}
			return false;
		}
		
		// Add manifest.json
		$manifest = $this->get_manifest_json();
		$zip->addFromString( 'manifest.json', $manifest );
		
		// Add icons (Android specific, includes maskable)
		$this->add_icons_to_zip( $zip, 'android' );
		
		// Add Android metadata JSON
		$metadata = $this->get_android_metadata_json();
		$zip->addFromString( 'metadata-android.json', $metadata );
		
		// Add consolidated store metadata
		$store_metadata = $this->get_consolidated_store_metadata( 'android' );
		$zip->addFromString( 'store-metadata.json', $store_metadata );
		
		// Add TWA/Bubblewrap configuration template
		$twa_config = $this->get_twa_config_template();
		$zip->addFromString( 'twa-config.json', $twa_config );
		
		// Add README
		$readme = $this->get_android_readme();
		$zip->addFromString( 'README-Android.txt', $readme );
		
		$zip->close();
		
		// Send download
		$filename = 'vh360-android-export-' . gmdate( 'Y-m-d' ) . '.zip';
		$this->send_download( $temp_file, $filename );
		
		// Clean up
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
		
		return true;
	}
	
	/**
	 * Create a temporary file for ZIP generation.
	 *
	 * @return string|false Path to temp file or false on failure.
	 */
	private function create_temp_file() {
		$temp_dir = get_temp_dir();
		$temp_file = tempnam( $temp_dir, 'vh360_pwa_export_' );
		
		if ( false === $temp_file ) {
			return false;
		}
		
		return $temp_file;
	}
	
	/**
	 * Get manifest JSON string.
	 *
	 * @return string JSON string.
	 */
	private function get_manifest_json() : string {
		$enhancer = new VH360_PWA_Manifest_Enhancer();
		$manifest = $enhancer->get_enhanced_manifest();
		
		return wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	
	/**
	 * Get iOS metadata JSON string.
	 *
	 * @return string JSON string.
	 */
	private function get_ios_metadata_json() : string {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$metadata = $metadata_handler->get_ios_metadata();
		
		// Add additional context
		$metadata['platform'] = 'ios';
		$metadata['export_date'] = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$metadata['site_url'] = home_url();
		
		return wp_json_encode( $metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	
	/**
	 * Get Android metadata JSON string.
	 *
	 * @return string JSON string.
	 */
	private function get_android_metadata_json() : string {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$metadata = $metadata_handler->get_android_metadata();
		
		// Add additional context
		$metadata['platform'] = 'android';
		$metadata['export_date'] = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$metadata['site_url'] = home_url();
		
		return wp_json_encode( $metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
	
	/**
	 * Add icons to ZIP archive.
	 * Uses locally generated icons when available, with security validation for any remote URLs.
	 *
	 * @param ZipArchive $zip ZIP archive object.
	 * @param string     $platform Platform identifier (ios, android, or all).
	 */
	private function add_icons_to_zip( ZipArchive $zip, string $platform = 'all' ) : void {
		$icon_generator = new VH360_PWA_Icon_Generator();
		$generated_icons = $icon_generator->get_generated_icons();
		$upload_dir = $icon_generator->get_upload_dir();
		
		// Add generated icons if available
		if ( ! empty( $generated_icons ) ) {
			// iOS icons
			if ( 'ios' === $platform || 'all' === $platform ) {
				if ( ! empty( $generated_icons['ios'] ) ) {
					foreach ( $generated_icons['ios'] as $size => $filename ) {
						$filepath = $upload_dir . '/' . $filename;
						if ( file_exists( $filepath ) ) {
							$zip->addFile( $filepath, 'icons/' . $filename );
						}
					}
				}
			}
			
			// Android icons
			if ( 'android' === $platform || 'all' === $platform ) {
				if ( ! empty( $generated_icons['android'] ) ) {
					foreach ( $generated_icons['android'] as $size => $filename ) {
						$filepath = $upload_dir . '/' . $filename;
						if ( file_exists( $filepath ) ) {
							$zip->addFile( $filepath, 'icons/' . $filename );
						}
					}
				}
				
				// Maskable icons for Android
				if ( ! empty( $generated_icons['maskable'] ) ) {
					foreach ( $generated_icons['maskable'] as $size => $filename ) {
						$filepath = $upload_dir . '/' . $filename;
						if ( file_exists( $filepath ) ) {
							$zip->addFile( $filepath, 'icons/' . $filename );
						}
					}
				}
			}
			
			return;
		}
		
		// Fallback to legacy icon handling (with security validation)
		$this->add_legacy_icons_to_zip( $zip );
	}
	
	/**
	 * Add legacy icons to ZIP (fallback for backward compatibility).
	 * Includes security validation for remote URLs.
	 *
	 * @param ZipArchive $zip ZIP archive object.
	 */
	private function add_legacy_icons_to_zip( ZipArchive $zip ) : void {
		$opts = vh360_pwa_get_options();
		
		$icons = array(
			'icon-192.png'          => $opts['icon_192'] ?? '',
			'icon-512.png'          => $opts['icon_512'] ?? '',
			'icon-maskable-192.png' => $opts['icon_maskable_192'] ?? '',
			'icon-maskable-512.png' => $opts['icon_maskable_512'] ?? '',
		);
		
		foreach ( $icons as $filename => $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			
			// Security validation
			if ( ! $this->is_valid_icon_url( $url ) ) {
				continue;
			}
			
			// Download icon
			$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
			if ( is_wp_error( $response ) ) {
				continue;
			}
			
			// Validate MIME type
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			if ( ! $this->is_valid_image_mime_type( $content_type ) ) {
				continue;
			}
			
			$body = wp_remote_retrieve_body( $response );
			if ( ! empty( $body ) ) {
				// Additional validation: check if body is actually an image
				$image_data = getimagesizefromstring( $body );
				if ( false !== $image_data && is_array( $image_data ) ) {
					$zip->addFromString( 'icons/' . $filename, $body );
				}
			}
		}
	}
	
	/**
	 * Validate icon URL for security.
	 *
	 * @param string $url URL to validate.
	 * @return bool True if valid.
	 */
	private function is_valid_icon_url( string $url ) : bool {
		// Must be a valid URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		
		// Only allow http and https
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}
		
		// Check file extension
		$path = parse_url( $url, PHP_URL_PATH );
		$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif' ), true ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Validate image MIME type.
	 *
	 * @param string $mime_type MIME type to validate.
	 * @return bool True if valid.
	 */
	private function is_valid_image_mime_type( string $mime_type ) : bool {
		$allowed_types = array(
			'image/png',
			'image/jpeg',
			'image/jpg',
			'image/gif',
		);
		
		return in_array( strtolower( $mime_type ), $allowed_types, true );
	}
	
	/**
	 * Get iOS README content.
	 *
	 * @return string README content.
	 */
	private function get_ios_readme() : string {
		$readme_path = VH360_PWA_APP_DIR . 'templates/export/README-iOS.txt';
		
		if ( file_exists( $readme_path ) ) {
			return file_get_contents( $readme_path );
		}
		
		// Fallback if template file doesn't exist
		return $this->get_default_ios_readme();
	}
	
	/**
	 * Get Android README content.
	 *
	 * @return string README content.
	 */
	private function get_android_readme() : string {
		$readme_path = VH360_PWA_APP_DIR . 'templates/export/README-Android.txt';
		
		if ( file_exists( $readme_path ) ) {
			return file_get_contents( $readme_path );
		}
		
		// Fallback if template file doesn't exist
		return $this->get_default_android_readme();
	}
	
	/**
	 * Get default iOS README content.
	 *
	 * @return string Default README content.
	 */
	private function get_default_ios_readme() : string {
		return "iOS Wrapper Export Pack\n\nThis package contains the necessary files to create an iOS app wrapper for your PWA.\n\nPlease see the template files for detailed instructions.";
	}
	
	/**
	 * Get default Android README content.
	 *
	 * @return string Default README content.
	 */
	private function get_default_android_readme() : string {
		return "Android Wrapper Export Pack\n\nThis package contains the necessary files to create an Android app wrapper for your PWA.\n\nPlease see the template files for detailed instructions.";
	}
	
	/**
	 * Send file as download to browser.
	 *
	 * @param string $file_path Path to file.
	 * @param string $filename Download filename.
	 */
	private function send_download( string $file_path, string $filename ) : void {
		if ( ! file_exists( $file_path ) ) {
			return;
		}
		
		// Clear all output buffers to prevent corrupted downloads
		// Add counter to prevent infinite loops
		$max_iterations = 100;
		$iterations = 0;
		while ( ob_get_level() > 0 && $iterations < $max_iterations ) {
			ob_end_clean();
			$iterations++;
		}
		
		// Set headers
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		
		// Send file
		readfile( $file_path );
		exit;
	}
	
	/**
	 * Get Capacitor configuration template for iOS wrapper.
	 *
	 * @return string JSON configuration template.
	 */
	private function get_capacitor_config_template() : string {
		$opts = vh360_pwa_get_options();
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$ios_meta = $metadata_handler->get_ios_metadata();
		
		$config = array(
			'appId'       => 'com.example.app',
			'appName'     => ! empty( $ios_meta['app_title'] ) ? $ios_meta['app_title'] : $opts['app_name'],
			'webDir'      => 'www',
			'bundledWebRuntime' => false,
			'server'      => array(
				'url'              => home_url( '/' ),
				'cleartext'        => ! is_ssl(),
				'androidScheme'    => 'https',
			),
			'ios'         => array(
				'contentInset'     => 'always',
			),
			'android'     => array(
				'buildOptions'     => array(
					'keystorePath'     => '',
					'keystoreAlias'    => '',
				),
			),
		);
		
		$json = wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		return false !== $json ? $json : '{}';
	}
	
	/**
	 * Get TWA/Bubblewrap configuration template for Android wrapper.
	 *
	 * @return string JSON configuration template.
	 */
	private function get_twa_config_template() : string {
		$opts = vh360_pwa_get_options();
		$metadata_handler = new VH360_PWA_Store_Metadata();
		$android_meta = $metadata_handler->get_android_metadata();
		
		$config = array(
			'packageId'        => 'com.example.app',
			'host'             => wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
			'name'             => ! empty( $android_meta['app_title'] ) ? $android_meta['app_title'] : $opts['app_name'],
			'launcherName'     => ! empty( $opts['short_name'] ) ? $opts['short_name'] : $opts['app_name'],
			'display'          => $opts['display'] ?? 'standalone',
			'themeColor'       => $opts['theme_color'] ?? '#000000',
			'backgroundColor'  => $opts['background_color'] ?? '#ffffff',
			'startUrl'         => $opts['start_url'] ?? '/',
			'iconUrl'          => '', // Will be filled from generated icons
			'maskableIconUrl'  => '', // Will be filled from generated icons
			'shortcuts'        => array(),
			'signingKey'       => array(
				'path'     => '/path/to/keystore.keystore',
				'alias'    => 'key-alias',
			),
		);
		
		// Add icon URLs if generated icons exist
		$icon_generator = new VH360_PWA_Icon_Generator();
		$generated = $icon_generator->get_generated_icons();
		$upload_url = $icon_generator->get_upload_url();
		
		if ( ! empty( $generated['android']['512'] ) ) {
			$config['iconUrl'] = $upload_url . '/' . $generated['android']['512'];
		}
		
		if ( ! empty( $generated['maskable']['512'] ) ) {
			$config['maskableIconUrl'] = $upload_url . '/' . $generated['maskable']['512'];
		}
		
		$json = wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		return false !== $json ? $json : '{}';
	}
	
	/**
	 * Get consolidated store metadata for listing.
	 *
	 * @param string $platform Platform ('ios' or 'android').
	 * @return string JSON metadata for store listing.
	 */
	private function get_consolidated_store_metadata( string $platform ) : string {
		$metadata_handler = new VH360_PWA_Store_Metadata();
		
		if ( 'ios' === $platform ) {
			$meta = $metadata_handler->get_ios_metadata();
		} else {
			$meta = $metadata_handler->get_android_metadata();
		}
		
		// Create a clean, complete metadata object
		$consolidated = array(
			'platform'          => $platform,
			'app_name'          => $meta['app_title'] ?? '',
			'short_description' => $meta['short_description'] ?? '',
			'full_description'  => $meta['full_description'] ?? '',
			'category'          => $meta['category'] ?? '',
			'keywords'          => $meta['keywords'] ?? '',
			'privacy_policy_url' => $meta['privacy_policy'] ?? '',
			'support_email'     => $meta['support_email'] ?? '',
			'exported_date'     => gmdate( 'Y-m-d H:i:s' ) . ' UTC',
		);
		
		// Add platform-specific fields
		if ( 'ios' === $platform && ! empty( $meta['app_store_id'] ) ) {
			$consolidated['app_store_id'] = $meta['app_store_id'];
		} elseif ( 'android' === $platform && ! empty( $meta['package_name'] ) ) {
			$consolidated['package_name'] = $meta['package_name'];
		}
		
		$json = wp_json_encode( $consolidated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		return false !== $json ? $json : '{}';
	}
}
