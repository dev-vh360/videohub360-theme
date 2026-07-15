<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Endpoints {
	public function register() : void {
		// Priority 1 ensures we run before most other plugins
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ), 1 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_serve_endpoint' ), 0 );
		
		// Force flush on theme/plugin changes
		add_action( 'after_switch_theme', array( __CLASS__, 'flush_rules' ) );
	}

	public static function add_rewrite_rules() : void {
		add_rewrite_rule( '^' . preg_quote( VH360_PWA_MANIFEST_SLUG, '#' ) . '$', 'index.php?vh360_pwa_endpoint=manifest', 'top' );
		add_rewrite_rule( '^' . preg_quote( VH360_PWA_SW_SLUG, '#' ) . '$', 'index.php?vh360_pwa_endpoint=sw', 'top' );
		add_rewrite_rule( '^' . preg_quote( VH360_PWA_OFFLINE_SLUG, '#' ) . '$', 'index.php?vh360_pwa_endpoint=offline', 'top' );
		add_rewrite_rule( '^' . preg_quote( VH360_PWA_LAUNCH_SHELL_SLUG, '#' ) . '$', 'index.php?vh360_pwa_endpoint=launch_shell', 'top' );
		
		// OneSignal service worker endpoints
		add_rewrite_rule( '^OneSignalSDKWorker\.js$', 'index.php?vh360_pwa_endpoint=onesignal_worker', 'top' );
		add_rewrite_rule( '^OneSignalSDKUpdaterWorker\.js$', 'index.php?vh360_pwa_endpoint=onesignal_updater', 'top' );
		// OneSignalSDK.sw.js is an alternative path that some OneSignal configurations use - maps to same endpoint
		add_rewrite_rule( '^OneSignalSDK\.sw\.js$', 'index.php?vh360_pwa_endpoint=onesignal_worker', 'top' );
	}
	
	/**
	 * Flush rewrite rules manually
	 */
	public static function flush_rules() : void {
		self::add_rewrite_rules();
		flush_rewrite_rules( false );
	}

	public function query_vars( array $vars ) : array {
		$vars[] = 'vh360_pwa_endpoint';
		return $vars;
	}

	public function maybe_serve_endpoint() : void {
		$endpoint = get_query_var( 'vh360_pwa_endpoint' );
		if ( empty( $endpoint ) ) {
			return;
		}

		// Endpoints should always work for diagnostics, but only return PWA content if enabled.
		$enabled = vh360_pwa_is_enabled();

		switch ( (string) $endpoint ) {
			case 'manifest':
				$this->serve_manifest( $enabled );
				break;
			case 'sw':
				$this->serve_sw( $enabled );
				break;
			case 'offline':
				$this->serve_offline( $enabled );
				break;
			case 'launch_shell':
				$this->serve_launch_shell( $enabled );
				break;
			case 'onesignal_worker':
				$this->serve_onesignal_worker();
				break;
			case 'onesignal_updater':
				$this->serve_onesignal_updater();
				break;
			default:
				return;
		}

		exit;
	}

	private function no_cache_headers() : void {
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}

	private function serve_manifest( bool $enabled ) : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/manifest+json; charset=utf-8', true );

		if ( ! $enabled ) {
			echo wp_json_encode(
				array(
					'disabled' => true,
					'message'  => 'VH360 PWA is disabled or the Videohub360 Theme is not active.',
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
			return;
		}

		$manifest = vh360_pwa_build_manifest();

		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function serve_sw( bool $enabled ) : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8', true );

		if ( ! $enabled ) {
			echo "// VH360 PWA disabled\nself.addEventListener('fetch', function(event){});\n";
			return;
		}

		echo vh360_pwa_build_sw_script();
	}

	private function serve_offline( bool $enabled ) : void {
		$this->no_cache_headers();
		header( 'Content-Type: text/html; charset=utf-8', true );

		if ( ! $enabled ) {
			echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VH360 PWA Disabled</title></head><body><h1>VH360 PWA is disabled.</h1></body></html>';
			return;
		}

		$opts = vh360_pwa_get_options();
		$app_name = esc_html( (string) $opts['app_name'] );
		$home = esc_url( home_url( '/' ) );
		include VH360_PWA_APP_DIR . 'templates/offline.php';
	}


	private function serve_launch_shell( bool $enabled ) : void {
		$this->no_cache_headers();
		header( 'Content-Type: text/html; charset=utf-8', true );

		if ( ! $enabled ) {
			echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VH360 PWA Disabled</title></head><body><h1>VH360 PWA is disabled.</h1></body></html>';
			return;
		}

		echo vh360_pwa_build_launch_shell();
	}

	/**
	 * Serve OneSignal SDK Worker
	 */
	private function serve_onesignal_worker() : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8', true );
		header( 'Service-Worker-Allowed: /', true );

		$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
		echo "importScripts('https://cdn.onesignal.com/sdks/web/" . esc_js( $sdk_version ) . "/OneSignalSDK.sw.js');\n";
	}

	/**
	 * Serve OneSignal SDK Updater Worker
	 */
	private function serve_onesignal_updater() : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8', true );
		header( 'Service-Worker-Allowed: /', true );

		$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
		echo "importScripts('https://cdn.onesignal.com/sdks/web/" . esc_js( $sdk_version ) . "/OneSignalSDK.sw.js');\n";
	}
}
