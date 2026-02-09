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

		$opts = vh360_pwa_get_options();

		$icons = array();
		$add_icon = function( $src, $size, $purpose = 'any' ) use ( &$icons ) {
			$src = (string) $src;
			if ( ! $src ) {
				return;
			}
			$icons[] = array(
				'src'     => esc_url_raw( $src ),
				'sizes'   => $size,
				'type'    => 'image/png',
				'purpose' => $purpose,
			);
		};
		$add_icon( $opts['icon_192'], '192x192', 'any' );
		$add_icon( $opts['icon_512'], '512x512', 'any' );
		$add_icon( $opts['icon_maskable_192'], '192x192', 'maskable' );
		$add_icon( $opts['icon_maskable_512'], '512x512', 'maskable' );

		$manifest = array(
			'name'             => (string) $opts['app_name'],
			'short_name'       => (string) $opts['short_name'],
			'description'      => (string) $opts['description'],
			'start_url'        => esc_url_raw( $opts['start_url'] ),
			'scope'            => esc_url_raw( $opts['scope'] ),
			'display'          => (string) $opts['display'],
			'orientation'      => (string) $opts['orientation'],
			'theme_color'      => (string) $opts['theme_color'],
			'background_color' => (string) $opts['background_color'],
			'lang'             => (string) $opts['lang'],
			'icons'            => $icons,
		);

		echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function serve_sw( bool $enabled ) : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8', true );

		if ( ! $enabled ) {
			echo "// VH360 PWA disabled\nself.addEventListener('fetch', function(event){});\n";
			return;
		}

		$opts = vh360_pwa_get_options();
		$precache_urls = vh360_pwa_get_precache_urls( $opts );

		// Output SW JS.
		$cache_version = preg_replace( '/[^a-zA-Z0-9._-]/', '', (string) $opts['cache_version'] );
		if ( '' === $cache_version ) {
			$cache_version = 'v1';
		}
		$strategy = (string) $opts['cache_strategy'];
		if ( ! in_array( $strategy, array( 'safe', 'balanced', 'aggressive' ), true ) ) {
			$strategy = 'safe';
		}

		// If OneSignal (provider push) is configured, merge OneSignal's SW runtime
		// into our own service worker to avoid scope conflicts.
		$onesignal = array();
		$push_settings = get_option( 'vh360_pwa_push_settings', array() );
		if ( is_array( $push_settings ) ) {
			$active_provider = (string) ( $push_settings['active_provider'] ?? 'onesignal' );
			$providers = $push_settings['providers'] ?? array();
			if ( isset( $providers[ $active_provider ] ) && is_array( $providers[ $active_provider ] ) ) {
				$app_id = trim( (string) ( $providers[ $active_provider ]['app_id'] ?? '' ) );
				if ( $app_id ) {
					$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
					$onesignal = array(
						'importUrl' => 'https://cdn.onesignal.com/sdks/web/' . $sdk_version . '/OneSignalSDK.sw.js',
					);
				}
			}
		}

		$payload = array(
			'cacheVersion' => $cache_version,
			'strategy'     => $strategy,
			'precache'     => $precache_urls,
			'offlineUrl'   => vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG ),
			'homeOrigin'   => home_url(),
			'onesignal'    => $onesignal,
		);

		echo "// VH360 Service Worker\n";
		echo "const VH360_PWA = " . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";\n";

		// Inline service worker implementation.
		readfile( VH360_PWA_APP_DIR . 'templates/sw-template.js' );
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

	/**
	 * Serve OneSignal SDK Worker
	 */
	private function serve_onesignal_worker() : void {
		$this->no_cache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8', true );
		header( 'Service-Worker-Allowed: /', true );

		// Import OneSignal SDK from CDN
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

		// Import OneSignal SDK from CDN
		$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
		echo "importScripts('https://cdn.onesignal.com/sdks/web/" . esc_js( $sdk_version ) . "/OneSignalSDK.sw.js');\n";
	}
}
