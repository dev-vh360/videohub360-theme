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
	public static function ensure_root_files() : void {
		// Avoid running during install/upgrade steps where ABSPATH may not be stable.
		if ( ! defined( 'ABSPATH' ) || ! is_string( ABSPATH ) ) {
			return;
		}

		$root = trailingslashit( ABSPATH );

		// Best-effort filesystem init.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_wp_filesystem
			WP_Filesystem();
		}

		self::maybe_write_manifest( $root );
		self::maybe_write_offline_page( $root );
		self::maybe_write_vh360_sw( $root );
		self::maybe_write_onesignal_workers( $root );
	}

	private static function maybe_write_manifest( string $root ) : void {
		$path = $root . 'vh360-manifest.json';
		
		// Get PWA options to use dynamic app name values
		$opts = function_exists( 'vh360_pwa_get_options' ) ? vh360_pwa_get_options() : array();
		
		// Use PWA app_name if set, fallback to site name
		$app_name = ! empty( $opts['app_name'] ) && is_string( $opts['app_name'] ) ? $opts['app_name'] : get_bloginfo( 'name' );
		
		// Use PWA short_name if set, fallback to app_name or site name
		$short_name = ! empty( $opts['short_name'] ) && is_string( $opts['short_name'] ) ? $opts['short_name'] : $app_name;
		
		// Ensure name and short_name are never empty
		if ( empty( $app_name ) || ! is_string( $app_name ) ) {
			$app_name = 'VideoHub360';
		}
		if ( empty( $short_name ) || ! is_string( $short_name ) ) {
			$short_name = 'VH360';
		}

		$manifest = array(
			'name'             => $app_name,
			'short_name'       => $short_name,
			'start_url'        => isset( $opts['start_url'] ) ? (string) $opts['start_url'] : '/',
			'scope'            => isset( $opts['scope'] ) ? (string) $opts['scope'] : '/',
			'display'          => isset( $opts['display'] ) ? (string) $opts['display'] : 'standalone',
			'background_color' => isset( $opts['background_color'] ) ? (string) $opts['background_color'] : '#000000',
			'theme_color'      => isset( $opts['theme_color'] ) ? (string) $opts['theme_color'] : '#000000',
			'icons'            => function_exists( 'vh360_pwa_get_manifest_icons' ) ? vh360_pwa_get_manifest_icons() : array(),
			'generated_by'     => 'VH360 PWA & App plugin',
			'generated_at'     => gmdate( 'c' ),
		);

		$written = self::write_file( $path, wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n" );
		update_option( 'vh360_pwa_root_manifest_write_status', array(
			'success'      => $written,
			'path'         => $path,
			'generated_at' => time(),
		) );
	}

	private static function maybe_write_offline_page( string $root ) : void {
		$path = $root . 'vh360-offline.html';
		if ( file_exists( $path ) ) {
			return;
		}

		$html = "<!doctype html>\n" .
			"<html lang=\"en\">\n" .
			"<head>\n" .
			"  <meta charset=\"utf-8\">\n" .
			"  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n" .
			"  <title>Offline</title>\n" .
			"  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;padding:40px;background:#0b0b0b;color:#fff}a{color:#8ab4f8}</style>\n" .
			"</head>\n" .
			"<body>\n" .
			"  <h1>You’re offline</h1>\n" .
			"  <p>Please check your connection and try again.</p>\n" .
			"</body>\n" .
			"</html>\n";

		self::write_file( $path, $html );
	}

	private static function maybe_write_vh360_sw( string $root ) : void {
		$path = $root . 'vh360-sw.js';
		if ( file_exists( $path ) ) {
			return;
		}

		// Minimal, safe SW that provides an offline fallback without aggressive caching.
		// This avoids introducing unexpected behavior while still enabling PWA install support.
		$js = "/* VH360 Managed File: vh360-sw.js */\n" .
			"self.addEventListener('install', function (event) {\n" .
			"  self.skipWaiting();\n" .
			"});\n" .
			"self.addEventListener('activate', function (event) {\n" .
			"  event.waitUntil(self.clients.claim());\n" .
			"});\n" .
			"self.addEventListener('fetch', function (event) {\n" .
			"  // Only provide an offline fallback for navigations.\n" .
			"  if (event.request && event.request.mode === 'navigate') {\n" .
			"    event.respondWith(\n" .
			"      fetch(event.request).catch(function () {\n" .
			"        return fetch('/vh360-offline.html', { cache: 'no-store' });\n" .
			"      })\n" .
			"    );\n" .
			"  }\n" .
			"});\n";

		self::write_file( $path, $js );
	}

	private static function maybe_write_onesignal_workers( string $root ) : void {
		// OneSignal requires both filenames at the site root. Use a managed wrapper that imports OneSignal's SW
		// and then imports VH360's SW for offline fallback.
		// Chrome requires some event handlers (notably 'message') to be registered during initial evaluation.
		// Add a harmless no-op handler before importing OneSignal to avoid warnings.
		$wrapper = "/* VH360 Managed File: OneSignal service worker wrapper */\n" .
			"self.addEventListener('message', function () {});\n" .
			"try {\n" .
			"  importScripts('https://cdn.onesignal.com/sdks/web/" . esc_js( VH360_PWA_ONESIGNAL_SDK_VERSION ) . "/OneSignalSDK.sw.js');\n" .
			"} catch (e) {}\n" .
			"try {\n" .
			"  importScripts('/vh360-sw.js');\n" .
			"} catch (e) {}\n";

		foreach ( array( 'OneSignalSDKWorker.js', 'OneSignalSDK.sw.js', 'OneSignalSDKUpdaterWorker.js' ) as $file ) {
			$path = $root . $file;

			// If missing, create.
			if ( ! file_exists( $path ) ) {
				self::write_file( $path, $wrapper );
				continue;
			}

			// If it's one of our managed files but was generated by an older version (e.g. missing the early
			// 'message' handler), repair it. Never overwrite non-managed/custom files.
			$existing = @file_get_contents( $path ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_get_contents
			if ( is_string( $existing )
				&& strpos( $existing, 'VH360 Managed File' ) !== false
				&& strpos( $existing, "addEventListener('message'" ) === false
			) {
				self::write_file( $path, $wrapper );
			}
		}
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
