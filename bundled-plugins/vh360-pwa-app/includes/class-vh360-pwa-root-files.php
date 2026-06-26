<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage root-level files required by browsers for PWA + OneSignal.
 *
 * Why this exists:
 * - Browsers require service workers to be fetched from the scope they control (typically site root).
 * - Many hosts/CDNs do not forward WordPress rewrite endpoints consistently for SW/manifest requests.
 * - OneSignal specifically looks for /OneSignalSDKWorker.js and /OneSignalSDK.sw.js at the web root.
 *
 * This class ensures those files exist as real files so 404s do not return.
 */
final class VH360_PWA_Root_Files {
	/**
	 * Root-level filenames we manage.
	 */
	private const FILES = array(
		'vh360-manifest.json',
		'vh360-sw.js',
		'vh360-offline.html',
		'OneSignalSDKWorker.js',
		'OneSignalSDKUpdaterWorker.js',
		'OneSignalSDK.sw.js',
	);

	/**
	 * Ensure all required files exist.
	 */
	public static function ensure_root_files() : array {
		// Avoid running during install/upgrade steps where ABSPATH may not be stable.
		if ( ! defined( 'ABSPATH' ) || ! is_string( ABSPATH ) ) {
			return array( 'error' => 'ABSPATH unavailable' );
		}

		$root = trailingslashit( ABSPATH );

		// Best-effort filesystem init.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_wp_filesystem
			WP_Filesystem();
		}

		return self::regenerate_root_files( $root );
	}

	public static function regenerate_root_files( string $root = '' ) : array {
		$root = '' !== $root ? trailingslashit( $root ) : trailingslashit( ABSPATH );
		$results = array();
		$results['manifest'] = self::maybe_write_manifest( $root );
		$results['offline'] = self::maybe_write_offline_page( $root );
		$results['service_worker'] = self::maybe_write_vh360_sw( $root );
		$results['onesignal'] = self::maybe_write_onesignal_workers( $root );
		return $results;
	}

	private static function maybe_write_manifest( string $root ) : array {
		$path = $root . 'vh360-manifest.json';
		$manifest = vh360_pwa_build_manifest();
		$manifest['generated_at'] = gmdate( 'c' );
		$result = self::write_managed_file( $path, wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n", 'vh360-manifest.json', true );
		update_option( 'vh360_pwa_root_manifest_write_status', array( 'success' => ! empty( $result['success'] ), 'path' => $path, 'generated_at' => time(), 'reason' => $result['reason'] ?? '' ) );
		return $result;
	}

	private static function maybe_write_offline_page( string $root ) : array {
		$path = $root . 'vh360-offline.html';
		$opts = vh360_pwa_get_options();
		$app = esc_html( (string) $opts['app_name'] );
		$bg = esc_attr( (string) ( $opts['splash_background_color'] ?: $opts['background_color'] ) );
		$theme = esc_attr( (string) $opts['theme_color'] );
		$html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Offline</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;min-height:100vh;display:grid;place-items:center;padding:40px;background:' . $bg . ';color:#fff}main{max-width:520px;text-align:center}a{color:' . $theme . '}</style></head><body><!-- VH360 Managed File: vh360-offline.html --><main><h1>' . $app . ' is offline</h1><p>Please check your connection and try again.</p><p><a href="/">Return home</a></p></main></body></html>' . "\n";
		return self::write_managed_file( $path, $html, 'vh360-offline.html', true );
	}

	private static function maybe_write_vh360_sw( string $root ) : array {
		$path = $root . 'vh360-sw.js';
		return self::write_managed_file( $path, vh360_pwa_build_sw_script(), 'vh360-sw.js', true );
	}

	private static function maybe_write_onesignal_workers( string $root ) : array {
		// OneSignal requires both filenames at the site root. Use a managed wrapper that imports OneSignal's SW
		// and then imports VH360's SW for offline fallback.
		// Chrome requires some event handlers (notably 'message') to be registered during initial evaluation.
		// Add a harmless no-op handler before importing OneSignal to avoid warnings.
		$vh360_sw_url = '/vh360-sw.js?v=' . rawurlencode( (string) vh360_pwa_get_asset_version() );

		$wrapper = "/* VH360 Managed File: OneSignal service worker wrapper */\n" .
			"self.addEventListener('message', function () {});\n" .
			"try {\n" .
			"  importScripts('https://cdn.onesignal.com/sdks/web/" . esc_js( VH360_PWA_ONESIGNAL_SDK_VERSION ) . "/OneSignalSDK.sw.js');\n" .
			"} catch (e) {}\n" .
			"try {\n" .
			"  importScripts('" . esc_js( $vh360_sw_url ) . "');\n" .
			"} catch (e) {}\n";

		$results = array();
		foreach ( array( 'OneSignalSDKWorker.js', 'OneSignalSDK.sw.js', 'OneSignalSDKUpdaterWorker.js' ) as $file ) {
			$path = $root . $file;
			$results[ $file ] = self::write_managed_file( $path, $wrapper, $file, false );
		}
		return $results;
	}

	private static function is_managed_file_content( string $existing ) : bool {
		foreach ( array( 'VH360 Managed File', 'VH360 PWA & App plugin', '"generated_by": "VH360 PWA & App plugin"', '"vh360_managed": true', 'VH360 Service Worker', 'vh360-sw.js', 'vh360-offline.html' ) as $marker ) {
			if ( false !== strpos( $existing, $marker ) ) {
				return true;
			}
		}
		return false;
	}

	private static function write_managed_file( string $path, string $contents, string $marker, bool $force_vh360_file = false ) : array {
		$file = basename( $path );
		$result = array(
			'file'    => $file,
			'path'    => $path,
			'success' => false,
			'skipped' => false,
			'reason'  => 'unknown',
		);

		if ( file_exists( $path ) && ! $force_vh360_file ) {
			$existing = @file_get_contents( $path );
			if ( ! is_string( $existing ) || ! self::is_managed_file_content( $existing ) ) {
				$result['skipped'] = true;
				$result['reason'] = 'custom_file_protected';
				return $result;
			}
		}
		if ( 'vh360-manifest.json' !== $marker && false === strpos( $contents, 'VH360 Managed File' ) ) {
			$contents = '/* VH360 Managed File: ' . $marker . ' */' . "\n" . $contents;
		}
		$written = self::write_file( $path, $contents );
		$result['success'] = $written;
		$result['reason'] = $written ? 'written' : ( is_writable( dirname( $path ) ) ? 'filesystem_error' : 'not_writable' );
		return $result;
	}

	private static function write_file( string $path, string $contents ) : bool {
		global $wp_filesystem;

		// Prefer WP_Filesystem when available.
		if ( $wp_filesystem && is_object( $wp_filesystem ) && method_exists( $wp_filesystem, 'put_contents' ) ) {
			return (bool) $wp_filesystem->put_contents( $path, $contents, FS_CHMOD_FILE );
		}

		// Fallback.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		return false !== @file_put_contents( $path, $contents );
	}
}
