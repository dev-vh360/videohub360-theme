<?php
/**
 * Safe, installation-independent PWA settings import/export.
 *
 * This is deliberately the only portable PWA schema. Credentials, generated
 * files, authorization policy, runtime state, and store metadata are excluded
 * by construction because this class reads only the closed list below.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Portable_Settings {
	private static $importing = false;

	private static function schema() : array {
		return array(
			'enabled' => 'bool', 'app_name' => 'text', 'short_name' => 'text', 'description' => 'text',
			'theme_color' => 'color', 'background_color' => 'color', 'enable_pull_to_refresh' => 'bool',
			'splash_enabled' => 'bool', 'splash_background_color' => 'color', 'splash_title' => 'text',
			'splash_title_enabled' => 'bool', 'splash_title_font_size' => 'font_size',
			'splash_title_color' => 'color', 'splash_title_offset' => 'title_offset',
			'display' => 'display', 'orientation' => 'orientation', 'start_url' => 'path', 'scope' => 'path',
			'show_install_prompt' => 'bool', 'install_prompt_text' => 'text', 'show_install_banner' => 'bool',
			'install_banner_text' => 'text', 'install_banner_dismiss_days' => 'dismiss_days',
			'show_ios_onboarding' => 'bool', 'fast_launch_enabled' => 'bool', 'launch_mode' => 'launch_mode',
			'launch_shell_max_ms' => 'launch_wait', 'precache_offline' => 'bool', 'precache_home' => 'bool',
			'precache_urls' => 'precache',
		);
	}

	public static function is_importing() : bool {
		return self::$importing;
	}

	public static function export_settings() : array {
		$raw = get_option( 'vh360_pwa_options', array() );
		$raw = is_array( $raw ) ? $raw : array();
		$resolved = vh360_pwa_get_options();
		$options = array();
		foreach ( self::schema() as $key => $type ) {
			$value = array_key_exists( $key, $raw ) ? $raw[ $key ] : ( $resolved[ $key ] ?? null );
			$valid = true;
			$options[ $key ] = self::sanitize_value( $key, $type, $value, $resolved, $valid );
		}

		$assets = array();
		if ( ! empty( $raw['splash_logo'] ) && self::is_transferable_image_url( (string) $raw['splash_logo'] ) ) {
			$assets['splash_logo'] = array( 'source_url' => esc_url_raw( (string) $raw['splash_logo'] ) );
		}
		$master = (string) get_option( 'vh360_pwa_master_icon_source', '' );
		$descriptor = self::master_icon_descriptor( $master );
		if ( $descriptor ) {
			$assets['master_icon'] = $descriptor;
		}
		return array( 'options' => $options, 'assets' => $assets );
	}

	public static function import_settings( $data, $context = array() ) : array {
		$result = array( 'imported' => 0, 'skipped' => 0, 'assets_imported' => 0, 'assets_skipped' => 0, 'warnings' => array(), 'license_disabled' => false );
		if ( ! is_array( $data ) ) {
			return $result;
		}
		$incoming = isset( $data['options'] ) && is_array( $data['options'] ) ? $data['options'] : array();
		$assets = isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array();
		$old_raw = get_option( 'vh360_pwa_options', array() );
		$old_raw = is_array( $old_raw ) ? $old_raw : array();
		$defaults = vh360_pwa_get_options();
		$new = $old_raw;
		foreach ( self::schema() as $key => $type ) {
			if ( ! array_key_exists( $key, $incoming ) ) {
				continue;
			}
			$valid = true;
			$value = self::sanitize_value( $key, $type, $incoming[ $key ], $defaults, $valid );
			if ( ! $valid ) {
				$result['skipped']++;
				continue;
			}
			if ( 'enabled' === $key && $value && ! vh360_pwa_is_licensed() ) {
				$value = 0;
				$result['license_disabled'] = true;
				$result['warnings'][] = __( 'PWA enablement was skipped because an active VideoHub360 license is required.', 'vh360-pwa-app' );
			}
			$new[ $key ] = $value;
			$result['imported']++;
		}

		$url_remap = ! empty( $context['url_remap'] ) && is_array( $context['url_remap'] ) ? $context['url_remap'] : array();
		if ( isset( $assets['splash_logo']['source_url'] ) ) {
			$url = self::import_splash_logo( (string) $assets['splash_logo']['source_url'], $url_remap );
			if ( $url ) {
				$new['splash_logo'] = $url;
				$result['assets_imported']++;
			} else {
				$result['assets_skipped']++;
				$result['warnings'][] = __( 'The portable PWA splash logo could not be transferred.', 'vh360-pwa-app' );
			}
		}

		$master_path = '';
		if ( isset( $assets['master_icon']['source_url'] ) ) {
			$master_path = self::import_master_icon( $assets['master_icon'] );
			if ( $master_path ) {
				$result['assets_imported']++;
			} else {
				$result['assets_skipped']++;
				$result['warnings'][] = __( 'The portable PWA master icon could not be transferred.', 'vh360-pwa-app' );
			}
		}

		$portable_changed = $old_raw !== $new || '' !== $master_path;
		$final_options = $old_raw;
		self::$importing = true;
		try {
			update_option( 'vh360_pwa_options', $new );
			if ( $portable_changed && function_exists( 'vh360_pwa_bump_asset_version' ) ) {
				vh360_pwa_bump_asset_version();
			}
			if ( $master_path ) {
				self::regenerate_icons( $master_path, $result );
			}
			$final_options = get_option( 'vh360_pwa_options', array() );
			$final_options = is_array( $final_options ) ? $final_options : array();
		} finally {
			self::$importing = false;
		}
		if ( $portable_changed && class_exists( 'VH360_PWA_Admin' ) ) {
			$flush = empty( $context['starter_sites'] );
			VH360_PWA_Admin::refresh_after_options_update( $old_raw, $final_options, $flush );
		}
		return $result;
	}

	private static function sanitize_value( string $key, string $type, $value, array $defaults, bool &$valid ) {
		$valid = true;
		switch ( $type ) {
			case 'bool': return vh360_pwa_boolval( $value );
			case 'text': return sanitize_text_field( $value );
			case 'color':
				$fallback = 'splash_background_color' === $key ? ( $defaults['background_color'] ?? '#0f172a' ) : ( 'splash_title_color' === $key ? '#ffffff' : ( $defaults[ $key ] ?? '' ) );
				return sanitize_hex_color( $value ) ?: $fallback;
			case 'font_size': return max( 18, min( 96, absint( $value ) ) );
			case 'title_offset': return max( 20, min( 200, absint( $value ) ) );
			case 'dismiss_days': return max( 1, min( 365, absint( $value ) ) );
			case 'launch_wait': return max( 500, min( 1200, absint( $value ) ) );
			case 'display': $allowed = array( 'standalone', 'fullscreen', 'minimal-ui', 'browser' ); break;
			case 'orientation': $allowed = array( 'any', 'portrait', 'portrait-primary', 'landscape' ); break;
			case 'launch_mode': $allowed = array( 'shell', 'cached_start' ); break;
			case 'path': return self::portable_path( (string) $value );
			case 'precache': return self::portable_precache( (string) $value );
			default: $valid = false; return null;
		}
		$value = (string) $value;
		if ( ! in_array( $value, $allowed, true ) ) {
			$valid = false;
		}
		return $value;
	}

	private static function portable_path( string $value ) : string {
		$value = trim( $value );
		if ( '' === $value ) return '/';
		$parts = wp_parse_url( $value );
		if ( false === $parts || ! is_array( $parts ) ) return '/';
		if ( isset( $parts['host'] ) && ( ! self::same_home_origin( $parts ) || ! self::path_is_within_home( (string) ( $parts['path'] ?? '/' ) ) ) ) return '/';
		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
		if ( isset( $parts['host'] ) ) $path = self::remove_home_path( $path );
		$path = '/' . ltrim( $path, '/' );
		return $path . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' ) . ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
	}

	private static function portable_precache( string $value ) : string {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) continue;
			$parts = wp_parse_url( $line );
			if ( false === $parts || ! is_array( $parts ) ) continue;
			if ( isset( $parts['host'] ) && ( ! self::same_home_origin( $parts ) || ! self::path_is_within_home( (string) ( $parts['path'] ?? '/' ) ) ) ) continue;
			$portable = self::portable_path( $line );
			if ( vh360_pwa_url_is_public_cache_candidate( home_url( $portable ) ) ) $out[] = $portable;
		}
		return implode( "\n", array_values( array_unique( $out ) ) );
	}

	private static function same_home_origin( array $parts ) : bool {
		$home = wp_parse_url( home_url() );
		if ( ! is_array( $home ) || empty( $home['host'] ) || empty( $parts['host'] ) ) return false;
		$port = $parts['port'] ?? ( ( $parts['scheme'] ?? 'https' ) === 'https' ? 443 : 80 );
		$home_port = $home['port'] ?? ( ( $home['scheme'] ?? 'https' ) === 'https' ? 443 : 80 );
		return strtolower( $parts['host'] ) === strtolower( $home['host'] ) && $port === $home_port;
	}

	private static function remove_home_path( string $path ) : string {
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( $home_path && ( $path === $home_path || 0 === strpos( $path, $home_path . '/' ) ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}
		return $path ?: '/';
	}

	private static function path_is_within_home( string $path ) : bool {
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( '' === $home_path ) {
			return true;
		}
		return $path === $home_path || 0 === strpos( $path, $home_path . '/' );
	}

	private static function master_icon_descriptor( string $path ) : array {
		if ( ! $path || ! is_readable( $path ) ) return array();
		$uploads = wp_upload_dir();
		$real = realpath( $path ); $base = realpath( $uploads['basedir'] );
		if ( ! $real || ! $base || 0 !== strpos( $real, trailingslashit( $base ) ) ) return array();
		$mime = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $real ) : '';
		if ( ! in_array( $mime, array( 'image/png', 'image/jpeg' ), true ) ) return array();
		$url = trailingslashit( $uploads['baseurl'] ) . str_replace( DIRECTORY_SEPARATOR, '/', substr( $real, strlen( trailingslashit( $base ) ) ) );
		return array( 'source_url' => esc_url_raw( $url ), 'basename' => sanitize_file_name( basename( $real ) ), 'mime_type' => $mime );
	}

	private static function is_transferable_image_url( string $url ) : bool {
		$type = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		return in_array( $type['type'] ?? '', array( 'image/png', 'image/jpeg', 'image/gif', 'image/webp' ), true );
	}

	private static function import_splash_logo( string $source, array $remap ) : string {
		if ( isset( $remap[ $source ] ) ) {
			$mapped = (string) $remap[ $source ];
			if ( wp_http_validate_url( $mapped ) && self::is_transferable_image_url( $mapped ) ) {
				return esc_url_raw( $mapped );
			}
		}
		$file = self::sideload( $source, array( 'image/png', 'image/jpeg', 'image/gif', 'image/webp' ) );
		if ( ! $file ) return '';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$name = sanitize_file_name( basename( (string) wp_parse_url( $source, PHP_URL_PATH ) ) );
		$id = media_handle_sideload( array( 'name' => $name ?: 'pwa-splash-logo.png', 'tmp_name' => $file ), 0 );
		if ( is_wp_error( $id ) ) { @unlink( $file ); return ''; }
		return (string) wp_get_attachment_url( $id );
	}

	private static function import_master_icon( array $descriptor ) : string {
		$source = (string) $descriptor['source_url'];
		$tmp = self::sideload( $source, array( 'image/png', 'image/jpeg' ) );
		if ( ! $tmp ) return '';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$name = ! empty( $descriptor['basename'] ) ? sanitize_file_name( $descriptor['basename'] ) : sanitize_file_name( basename( (string) wp_parse_url( $source, PHP_URL_PATH ) ) );
		if ( ! preg_match( '/\.(png|jpe?g)$/i', $name ) ) {
			$name = ( 'image/jpeg' === wp_get_image_mime( $tmp ) ? 'pwa-master-icon.jpg' : 'pwa-master-icon.png' );
		}
		$handled = wp_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), array( 'test_form' => false ) );
		if ( ! empty( $handled['error'] ) || empty( $handled['file'] ) ) { @unlink( $tmp ); return ''; }
		$path = $handled['file']; $hash = is_readable( $path ) ? md5_file( $path ) : false;
		update_option( 'vh360_pwa_master_icon_source', $path );
		update_option( 'vh360_pwa_master_icon_uploaded_at', time() );
		update_option( 'vh360_pwa_master_icon_hash', false !== $hash ? $hash : '' );
		update_option( 'vh360_pwa_master_icon_hash_error', false !== $hash ? '' : __( 'Unable to hash imported master icon file.', 'vh360-pwa-app' ) );
		update_option( 'vh360_pwa_master_icon_basename', basename( $path ) );
		update_option( 'vh360_pwa_master_icon_size_bytes', is_readable( $path ) ? filesize( $path ) : 0 );
		return $path;
	}

	private static function sideload( string $url, array $allowed_mimes ) : string {
		if ( ! wp_http_validate_url( $url ) ) return '';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) return '';
		$mime = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $tmp ) : '';
		if ( ! in_array( $mime, $allowed_mimes, true ) ) { @unlink( $tmp ); return ''; }
		return $tmp;
	}

	private static function regenerate_icons( string $path, array &$result ) : void {
		if ( ! class_exists( 'VH360_PWA_Icon_Generator' ) ) return;
		$generator = new VH360_PWA_Icon_Generator();
		$requirements = $generator->check_requirements();
		if ( empty( $requirements['available'] ) ) { $result['warnings'][] = __( 'The master icon was imported, but this server cannot generate PWA icons.', 'vh360-pwa-app' ); return; }
		$generator->clear_generated_icons();
		$generated = $generator->generate_all_icons( $path );
		if ( ! empty( $generated['generated'] ) && function_exists( 'vh360_pwa_backfill_legacy_icons_from_generated' ) ) vh360_pwa_backfill_legacy_icons_from_generated();
		if ( empty( $generated['success'] ) ) $result['warnings'][] = __( 'One or more destination PWA icons could not be generated.', 'vh360-pwa-app' );
	}
}
