<?php
/**
 * Icon Generator
 * 
 * Generates a complete set of app icons from a master source image.
 * Supports iOS, Android, and maskable icon variants in all required sizes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Icon_Generator {
	private $asset_version = 0;
	private $source_hash = '';
	private $generated_at = 0;
	
	/**
	 * Get the upload directory for generated icons.
	 *
	 * @return string Path to icon upload directory.
	 */
	public function get_upload_dir() : string {
		$upload_dir = wp_upload_dir();
		$icon_dir = $upload_dir['basedir'] . '/vh360-pwa/icons';
		
		// Create directory if it doesn't exist
		if ( ! file_exists( $icon_dir ) ) {
			wp_mkdir_p( $icon_dir );
		}
		
		return $icon_dir;
	}
	
	/**
	 * Get the upload URL for generated icons.
	 *
	 * @return string URL to icon upload directory.
	 */
	public function get_upload_url() : string {
		$upload_dir = wp_upload_dir();
		return $upload_dir['baseurl'] . '/vh360-pwa/icons';
	}
	
	/**
	 * Check if image libraries are available.
	 *
	 * @return array Status of available libraries.
	 */
	public function check_requirements() : array {
		$has_imagick = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
		$has_gd = extension_loaded( 'gd' ) && function_exists( 'imagecreatefromstring' );
		
		return array(
			'imagick'   => $has_imagick,
			'gd'        => $has_gd,
			'available' => $has_imagick || $has_gd,
			'preferred' => $has_imagick ? 'imagick' : ( $has_gd ? 'gd' : 'none' ),
		);
	}
	
	/**
	 * Get all required icon sizes.
	 *
	 * @return array Array of icon sizes grouped by platform.
	 */
	public function get_required_sizes() : array {
		return array(
			'ios' => array(
				1024, // Marketing icon for App Store
				512,  // PWA manifest / high-res
				192,  // PWA manifest / standard
				180,  // iPhone (iOS 11-14)
				167,  // iPad Pro
				152,  // iPad, iPad mini
				120,  // iPhone (iOS 7-10)
			),
			'android' => array(
				512,  // High-res for Play Store
				192,  // XXXHDPI
				144,  // XXHDPI
				96,   // XHDPI
				72,   // HDPI
				48,   // MDPI
			),
			'maskable' => array(
				512,  // High-res maskable
				192,  // Standard maskable
			),
		);
	}
	
	/**
	 * Generate all icons from a master source file.
	 *
	 * @param string $source_file Path to source image file.
	 * @return array Result with success status and generated icons or errors.
	 */
	public function generate_all_icons( string $source_file ) : array {
		$requirements = $this->check_requirements();
		
		if ( ! $requirements['available'] ) {
			return array(
				'success' => false,
				'error'   => __( 'Neither Imagick nor GD library is available. Please install one to generate icons.', 'vh360-pwa-app' ),
			);
		}
		
		if ( ! file_exists( $source_file ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Source file does not exist.', 'vh360-pwa-app' ),
			);
		}
		
		$hash = is_readable( $source_file ) ? md5_file( $source_file ) : false;
		if ( false === $hash ) {
			return array(
				'success' => false,
				'error'   => __( 'Unable to hash the source icon file.', 'vh360-pwa-app' ),
			);
		}

		$this->asset_version = function_exists( 'vh360_pwa_get_asset_version' ) ? vh360_pwa_get_asset_version() : time();
		$this->source_hash   = substr( preg_replace( '/[^a-f0-9]/', '', strtolower( $hash ) ), 0, 12 );
		$this->generated_at  = time();

		$sizes = $this->get_required_sizes();
		$generated = array();
		$errors = array();
		
		// Generate iOS icons
		foreach ( $sizes['ios'] as $size ) {
			$result = $this->generate_icon( $source_file, $size, 'any' );
			if ( $result['success'] ) {
				$generated['ios'][ $size ] = $this->build_icon_metadata( 'ios', $size, 'any', $result );
			} else {
				$errors[] = sprintf(
					/* translators: 1: Icon size, 2: Error message */
					__( 'iOS icon %1$dx%1$d: %2$s', 'vh360-pwa-app' ),
					$size,
					$result['error']
				);
			}
		}
		
		// Generate Android icons
		foreach ( $sizes['android'] as $size ) {
			$result = $this->generate_icon( $source_file, $size, 'any' );
			if ( $result['success'] ) {
				$generated['android'][ $size ] = $this->build_icon_metadata( 'android', $size, 'any', $result );
			} else {
				$errors[] = sprintf(
					/* translators: 1: Icon size, 2: Error message */
					__( 'Android icon %1$dx%1$d: %2$s', 'vh360-pwa-app' ),
					$size,
					$result['error']
				);
			}
		}
		
		// Generate maskable icons
		foreach ( $sizes['maskable'] as $size ) {
			$result = $this->generate_icon( $source_file, $size, 'maskable' );
			if ( $result['success'] ) {
				$generated['maskable'][ $size ] = $this->build_icon_metadata( 'maskable', $size, 'maskable', $result );
			} else {
				$errors[] = sprintf(
					/* translators: 1: Icon size, 2: Error message */
					__( 'Maskable icon %1$dx%1$d: %2$s', 'vh360-pwa-app' ),
					$size,
					$result['error']
				);
			}
		}
		
		// Save master icon reference
		if ( ! empty( $generated ) ) {
			update_option( 'vh360_pwa_master_icon', $source_file );
			update_option( 'vh360_pwa_generated_icons', $generated );
		}
		
		return array(
			'success'   => empty( $errors ),
			'generated' => $generated,
			'errors'    => $errors,
		);
	}
	
	/**
	 * Generate a single icon.
	 *
	 * @param string $source_file Path to source image.
	 * @param int    $size Target size (width and height).
	 * @param string $purpose Icon purpose (any or maskable).
	 * @return array Result with success status and file path or error.
	 */
	private function generate_icon( string $source_file, int $size, string $purpose = 'any' ) : array {
		$requirements = $this->check_requirements();
		
		if ( 'imagick' === $requirements['preferred'] ) {
			return $this->generate_icon_imagick( $source_file, $size, $purpose );
		} elseif ( 'gd' === $requirements['preferred'] ) {
			return $this->generate_icon_gd( $source_file, $size, $purpose );
		}
		
		return array(
			'success' => false,
			'error'   => __( 'No image library available', 'vh360-pwa-app' ),
		);
	}
	
	/**
	 * Generate icon using Imagick.
	 *
	 * @param string $source_file Path to source image.
	 * @param int    $size Target size.
	 * @param string $purpose Icon purpose.
	 * @return array Result array.
	 */
	private function generate_icon_imagick( string $source_file, int $size, string $purpose ) : array {
		try {
			$imagick = new Imagick( $source_file );
			
			// Resize maintaining aspect ratio
			$imagick->resizeImage( $size, $size, Imagick::FILTER_LANCZOS, 1 );
			
			// Create canvas for exact size (in case aspect ratio was different)
			$canvas = new Imagick();
			$canvas->newImage( $size, $size, new ImagickPixel( 'transparent' ) );
			$canvas->setImageFormat( 'png' );
			
			// Composite resized image onto canvas (centered)
			$geometry = $imagick->getImageGeometry();
			$x = (int) ( ( $size - $geometry['width'] ) / 2 );
			$y = (int) ( ( $size - $geometry['height'] ) / 2 );
			$canvas->compositeImage( $imagick, Imagick::COMPOSITE_OVER, $x, $y );
			
			// Generate filename
			$filename = $this->get_icon_filename( $size, $purpose );
			$filepath = $this->get_upload_dir() . '/' . $filename;
			
			// Save file
			$canvas->writeImage( $filepath );
			$canvas->clear();
			$imagick->clear();
			
			return array(
				'success' => true,
				'file'    => $filename,
				'path'    => $filepath,
				'url'     => $this->get_upload_url() . '/' . $filename,
			);
			
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}
	
	/**
	 * Generate icon using GD.
	 *
	 * @param string $source_file Path to source image.
	 * @param int    $size Target size.
	 * @param string $purpose Icon purpose.
	 * @return array Result array.
	 */
	private function generate_icon_gd( string $source_file, int $size, string $purpose ) : array {
		$image_data = getimagesizefromstring( file_get_contents( $source_file ) );
		
		if ( false === $image_data ) {
			return array(
				'success' => false,
				'error'   => __( 'Invalid image file', 'vh360-pwa-app' ),
			);
		}
		
		// Create source image based on type
		$mime_type = $image_data['mime'];
		
		switch ( $mime_type ) {
			case 'image/png':
				$source = imagecreatefrompng( $source_file );
				break;
			case 'image/jpeg':
				$source = imagecreatefromjpeg( $source_file );
				break;
			case 'image/gif':
				$source = imagecreatefromgif( $source_file );
				break;
			default:
				return array(
					'success' => false,
					'error'   => __( 'Unsupported image type', 'vh360-pwa-app' ),
				);
		}
		
		if ( false === $source ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to create image from source', 'vh360-pwa-app' ),
			);
		}
		
		// Create destination image
		$destination = imagecreatetruecolor( $size, $size );
		
		// Enable alpha blending for transparency
		imagealphablending( $destination, false );
		imagesavealpha( $destination, true );
		$transparent = imagecolorallocatealpha( $destination, 0, 0, 0, 127 );
		imagefill( $destination, 0, 0, $transparent );
		imagealphablending( $destination, true );
		
		// Resize and copy
		imagecopyresampled(
			$destination,
			$source,
			0, 0, 0, 0,
			$size, $size,
			imagesx( $source ),
			imagesy( $source )
		);
		
		// Generate filename
		$filename = $this->get_icon_filename( $size, $purpose );
		$filepath = $this->get_upload_dir() . '/' . $filename;
		
		// Save as PNG
		$result = imagepng( $destination, $filepath, 9 );
		
		// Clean up
		imagedestroy( $source );
		imagedestroy( $destination );
		
		if ( ! $result ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to save image', 'vh360-pwa-app' ),
			);
		}
		
		return array(
			'success' => true,
			'file'    => $filename,
			'path'    => $filepath,
			'url'     => $this->get_upload_url() . '/' . $filename,
		);
	}
	
	/**
	 * Get icon filename based on size and purpose.
	 *
	 * @param int    $size Icon size.
	 * @param string $purpose Icon purpose.
	 * @return string Filename.
	 */
	private function get_icon_filename( int $size, string $purpose ) : string {
		$version = $this->asset_version > 0 ? $this->asset_version : ( function_exists( 'vh360_pwa_get_asset_version' ) ? vh360_pwa_get_asset_version() : time() );
		$hash    = $this->source_hash ? $this->source_hash : 'nohash';
		$prefix  = 'maskable' === $purpose ? 'vh360-icon-maskable' : 'vh360-icon';
		return sprintf( '%s-%d-%d-%s.png', $prefix, $size, $version, $hash );
	}

	private function build_icon_metadata( string $platform, int $size, string $purpose, array $result ) : array {
		$filename = (string) ( $result['file'] ?? '' );
		$path     = (string) ( $result['path'] ?? ( $this->get_upload_dir() . '/' . $filename ) );
		$url      = (string) ( $result['url'] ?? ( $this->get_upload_url() . '/' . $filename ) );
		return array(
			'size'          => $size,
			'platform'      => $platform,
			'purpose'       => $purpose,
			'filename'      => $filename,
			'file'          => $filename,
			'url'           => esc_url_raw( $url ),
			'path'          => $path,
			'asset_version' => $this->asset_version,
			'source_hash'   => $this->source_hash,
			'generated_at'  => $this->generated_at,
		);
	}
	
	/**
	 * Get all generated icons.
	 *
	 * @return array Array of generated icons grouped by platform.
	 */
	public function get_generated_icons() : array {
		$generated = get_option( 'vh360_pwa_generated_icons', array() );
		
		if ( ! is_array( $generated ) ) {
			return array(
				'ios'      => array(),
				'android'  => array(),
				'maskable' => array(),
			);
		}
		
		return $this->get_generated_icon_filenames( $generated );
	}

	public function get_generated_icon_metadata() : array {
		$generated = get_option( 'vh360_pwa_generated_icons', array() );
		return is_array( $generated ) ? $generated : array();
	}

	private function get_generated_icon_filenames( array $generated ) : array {
		$filenames = array( 'ios' => array(), 'android' => array(), 'maskable' => array() );
		foreach ( $filenames as $group => $empty ) {
			if ( empty( $generated[ $group ] ) || ! is_array( $generated[ $group ] ) ) {
				continue;
			}
			foreach ( $generated[ $group ] as $size => $record ) {
				$filenames[ $group ][ $size ] = is_array( $record ) ? (string) ( $record['filename'] ?? $record['file'] ?? '' ) : (string) $record;
			}
		}
		return $filenames;
	}
	
	/**
	 * Get master icon path.
	 *
	 * @return string|false Master icon path or false if not set.
	 */
	public function get_master_icon() {
		return get_option( 'vh360_pwa_master_icon', false );
	}
	
	/**
	 * Clear all generated icons.
	 *
	 * @return array Clear results including deleted count and errors.
	 */
	public function clear_generated_icons() : array {
		$upload_dir = $this->get_upload_dir();
		$deleted = 0;
		$errors = array();
		foreach ( array( 'vh360-icon-*.png', 'icon-*.png', 'icon-maskable-*.png' ) as $pattern ) {
			foreach ( glob( trailingslashit( $upload_dir ) . $pattern ) ?: array() as $filepath ) {
				if ( ! is_file( $filepath ) ) {
					continue;
				}
				if ( @unlink( $filepath ) ) {
					$deleted++;
				} else {
					$errors[] = basename( $filepath );
				}
			}
		}
		delete_option( 'vh360_pwa_generated_icons' );
		delete_option( 'vh360_pwa_master_icon' );
		return array( 'success' => empty( $errors ), 'deleted' => $deleted, 'errors' => $errors );
	}
}
