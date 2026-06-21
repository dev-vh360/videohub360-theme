<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return true if the Videohub360 Theme (or a child theme) is active.
 */
function vh360_pwa_is_allowed_theme_active() : bool {
	$theme = wp_get_theme();
	$td    = (string) $theme->get( 'TextDomain' );
	if ( $td === VH360_PWA_ALLOWED_THEME_TEXTDOMAIN ) {
		return true;
	}

	// Child theme: check parent template theme.
	$parent_slug = (string) $theme->get_template();
	if ( $parent_slug ) {
		$parent = wp_get_theme( $parent_slug );
		$ptd    = (string) $parent->get( 'TextDomain' );
		if ( $ptd === VH360_PWA_ALLOWED_THEME_TEXTDOMAIN ) {
			return true;
		}
	}
	return false;
}

/**
 * Get plugin options merged with defaults.
 */
function vh360_pwa_get_options() : array {
	$defaults = array(
		'enabled'              => 0,
		'app_name'             => get_bloginfo( 'name' ),
		'short_name'           => get_bloginfo( 'name' ),
		'description'          => get_bloginfo( 'description' ),
		'theme_color'          => '#2563eb',
		'background_color'     => '#0f172a',
		'display'              => 'standalone',
		// Prefer portrait-primary to avoid upside-down rotation where supported.
		'orientation'          => 'portrait-primary',
		'start_url'            => '/',
		'scope'                => '/',
		'lang'                 => get_bloginfo( 'language' ),

		'cache_strategy'       => 'safe', // safe | balanced | aggressive
		'cache_version'        => 'v1',
		'precache_offline'     => 1,
		'precache_home'        => 0,
		'precache_urls'        => '',

		'show_install_prompt'  => 1,
		'install_prompt_text'  => 'Install this app',
		'show_install_banner'  => 0,
		'install_banner_text'  => 'Install the VH360 App',
		'install_banner_dismiss_days' => 7,
		'show_ios_onboarding'  => 1,

		'enable_pull_to_refresh' => 1,
		'show_refresh_button'  => 1,
		'refresh_label'        => 'Refresh',
		'splash_enabled'       => 1,
		'splash_background_color' => '#0f172a',
		'splash_logo'          => '',
		'splash_title'         => get_bloginfo( 'name' ),

		'icon_192'             => '',
		'icon_512'             => '',
		'icon_maskable_192'    => '',
		'icon_maskable_512'    => '',
		'debug_mode'           => 0,
		
		// Push notification sender roles (array of role keys)
		'push_sender_roles'    => array( 'administrator', 'editor' ),
	);

	$opts = get_option( 'vh360_pwa_options', array() );
	if ( ! is_array( $opts ) ) {
		$opts = array();
	}
	$opts = wp_parse_args( $opts, $defaults );

	// Sanity: start_url/scope must be same-origin.
	$home = home_url( '/' );
	foreach ( array( 'start_url', 'scope' ) as $key ) {
		if ( empty( $opts[ $key ] ) || ! is_string( $opts[ $key ] ) ) {
			$opts[ $key ] = $home;
		}
		// If user entered a relative path, normalize.
		if ( 0 === strpos( $opts[ $key ], '/' ) ) {
			$opts[ $key ] = home_url( $opts[ $key ] );
		}
	}

	return $opts;
}

/**
 * Update options (merged with existing).
 */
function vh360_pwa_update_options( array $new ) : array {
	$current = vh360_pwa_get_options();
	$merged  = array_merge( $current, $new );
	update_option( 'vh360_pwa_options', $merged );
	return $merged;
}

function vh360_pwa_boolval( $v ) : int {
	return ( ! empty( $v ) && ( '1' === (string) $v || 1 === $v || true === $v || 'on' === (string) $v ) ) ? 1 : 0;
}

function vh360_pwa_endpoint_url( string $slug ) : string {
	return home_url( '/' . ltrim( $slug, '/' ) );
}

function vh360_pwa_is_enabled() : bool {
	$opts = vh360_pwa_get_options();
	return vh360_pwa_is_allowed_theme_active() && ! empty( $opts['enabled'] );
}

function vh360_pwa_get_precache_urls( array $opts ) : array {
	$urls = array();

	if ( ! empty( $opts['precache_offline'] ) ) {
		$urls[] = vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG );
	}
	if ( ! empty( $opts['precache_home'] ) ) {
		$urls[] = home_url( '/' );
	}

	$raw = isset( $opts['precache_urls'] ) ? (string) $opts['precache_urls'] : '';
	if ( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			// Allow relative paths.
			if ( 0 === strpos( $line, '/' ) ) {
				$line = home_url( $line );
			}
			// Only same-origin.
			if ( 0 === strpos( $line, home_url() ) ) {
				$urls[] = $line;
			}
		}
	}

	return array_values( array_unique( $urls ) );
}



/**
 * Build the canonical manifest shared by dynamic endpoint and root file.
 */
function vh360_pwa_build_manifest( ?array $opts = null ) : array {
	$opts = is_array( $opts ) ? $opts : vh360_pwa_get_options();
	$background = ! empty( $opts['splash_enabled'] ) && ! empty( $opts['splash_background_color'] ) ? (string) $opts['splash_background_color'] : (string) $opts['background_color'];
	$scope = esc_url_raw( (string) $opts['scope'] );
	return array(
		'name'             => (string) $opts['app_name'],
		'short_name'       => (string) $opts['short_name'],
		'description'      => (string) $opts['description'],
		'start_url'        => esc_url_raw( (string) $opts['start_url'] ),
		'scope'            => $scope,
		'id'               => $scope ?: '/',
		'display'          => (string) $opts['display'],
		'orientation'      => (string) $opts['orientation'],
		'theme_color'      => (string) $opts['theme_color'],
		'background_color' => $background,
		'lang'             => (string) $opts['lang'],
		'icons'            => function_exists( 'vh360_pwa_get_manifest_icons' ) ? vh360_pwa_get_manifest_icons() : array(),
	);
}

/**
 * Build the canonical service worker payload and script.
 */
function vh360_pwa_build_sw_script( ?array $opts = null ) : string {
	$opts = is_array( $opts ) ? $opts : vh360_pwa_get_options();
	$cache_version = preg_replace( '/[^a-zA-Z0-9._-]/', '', (string) $opts['cache_version'] );
	if ( '' === $cache_version ) {
		$cache_version = 'v1';
	}
	$strategy = (string) $opts['cache_strategy'];
	if ( ! in_array( $strategy, array( 'safe', 'balanced', 'aggressive' ), true ) ) {
		$strategy = 'safe';
	}
	$onesignal = array();
	$push_settings = get_option( 'vh360_pwa_push_settings', array() );
	if ( is_array( $push_settings ) ) {
		$active_provider = (string) ( $push_settings['active_provider'] ?? 'onesignal' );
		$providers = $push_settings['providers'] ?? array();
		if ( isset( $providers[ $active_provider ] ) && is_array( $providers[ $active_provider ] ) && ! empty( $providers[ $active_provider ]['app_id'] ) ) {
			$sdk_version = defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ? VH360_PWA_ONESIGNAL_SDK_VERSION : 'v16';
			$onesignal = array( 'importUrl' => 'https://cdn.onesignal.com/sdks/web/' . $sdk_version . '/OneSignalSDK.sw.js' );
		}
	}
	$payload = array(
		'cacheVersion' => $cache_version,
		'strategy'     => $strategy,
		'precache'     => vh360_pwa_get_precache_urls( $opts ),
		'offlineUrl'   => vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG ),
		'homeOrigin'   => home_url(),
		'onesignal'    => $onesignal,
	);
	$template = file_exists( VH360_PWA_APP_DIR . 'templates/sw-template.js' ) ? file_get_contents( VH360_PWA_APP_DIR . 'templates/sw-template.js' ) : '';
	return "/* VH360 Managed File: vh360-sw.js */\n// VH360 Service Worker\nconst VH360_PWA = " . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";\n" . $template;
}

function vh360_pwa_get_ios_startup_images() : array {
	$opts = vh360_pwa_get_options();
	if ( empty( $opts['splash_enabled'] ) ) { return array(); }
	$generated = get_option( 'vh360_pwa_ios_startup_images', array() );
	return is_array( $generated ) ? $generated : array();
}

function vh360_pwa_generate_ios_startup_images() : array {
	$opts = vh360_pwa_get_options();
	if ( empty( $opts['splash_enabled'] ) || empty( $opts['splash_logo'] ) ) { return array(); }
	$sizes = array(
		'iphone-8-portrait'       => array( 750, 1334, '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
		'iphone-11-portrait'      => array( 828, 1792, '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
		'iphone-x-portrait'       => array( 1125, 2436, '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)' ),
		'iphone-12-portrait'      => array( 1170, 2532, '(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)' ),
		'iphone-14-portrait'      => array( 1179, 2556, '(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)' ),
		'iphone-pro-max-portrait' => array( 1284, 2778, '(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)' ),
		'iphone-15-pro-max-portrait' => array( 1290, 2796, '(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)' ),
		'ipad-portrait'           => array( 1536, 2048, '(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
		'ipad-pro-10-5-portrait'  => array( 1668, 2224, '(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
		'ipad-pro-11-portrait'    => array( 1668, 2388, '(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
		'ipad-pro-12-9-portrait'  => array( 2048, 2732, '(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)' ),
	);
	$uploads = wp_upload_dir();
	$dir = trailingslashit( $uploads['basedir'] ) . 'vh360-pwa/splash';
	$url = trailingslashit( $uploads['baseurl'] ) . 'vh360-pwa/splash';
	wp_mkdir_p( $dir );
	$logo_path = vh360_pwa_url_to_upload_path( $opts['splash_logo'] );
	if ( '' === $logo_path || ! file_exists( $logo_path ) || ! function_exists( 'imagecreatetruecolor' ) ) { return array(); }
	$logo_data = @file_get_contents( $logo_path );
	$logo_src = $logo_data ? @imagecreatefromstring( $logo_data ) : false;
	if ( ! $logo_src ) { return array(); }
	$bg = sanitize_hex_color( $opts['splash_background_color'] ?? $opts['background_color'] ) ?: '#0f172a';
	$title = trim( (string) ( $opts['splash_title'] ?? $opts['short_name'] ?? '' ) );
	$r=hexdec(substr($bg,1,2)); $g=hexdec(substr($bg,3,2)); $b=hexdec(substr($bg,5,2));
	$out = array();
	foreach ( $sizes as $key => $data ) {
		list($w,$h,$media) = $data; $img = imagecreatetruecolor($w,$h); $c=imagecolorallocate($img,$r,$g,$b); imagefill($img,0,0,$c);
		$lw=imagesx($logo_src); $lh=imagesy($logo_src); $target=(int)min($w*.32,$h*.18); $scale=$target/max($lw,$lh); $nw=(int)($lw*$scale); $nh=(int)($lh*$scale);
		$logo_x = (int) ( ( $w - $nw ) / 2 );
		$logo_y = (int) ( ( $h - $nh ) / 2 ) - ( '' !== $title ? (int) ( $h * 0.035 ) : 0 );
		imagecopyresampled($img,$logo_src,$logo_x,$logo_y,0,0,$nw,$nh,$lw,$lh);
		if ( '' !== $title ) {
			$font = 5;
			$max_chars = max( 12, (int) floor( $w / imagefontwidth( $font ) * 0.72 ) );
			$render_title = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, $max_chars ) : substr( $title, 0, $max_chars );
			$text_width = imagefontwidth( $font ) * strlen( $render_title );
			$text_x = (int) max( 0, ( $w - $text_width ) / 2 );
			$text_y = (int) min( $h - 60, $logo_y + $nh + max( 28, $h * 0.025 ) );
			$text_color = imagecolorallocate( $img, 255, 255, 255 );
			imagestring( $img, $font, $text_x, $text_y, $render_title, $text_color );
		}
		$file='vh360-startup-' . $key . '-' . md5($bg . $opts['splash_logo'] . $title) . '.png'; imagepng($img, trailingslashit($dir).$file); imagedestroy($img);
		$out[] = array('href'=>trailingslashit($url).$file,'media'=>$media);
	}
	imagedestroy($logo_src); update_option('vh360_pwa_ios_startup_images',$out); return $out;
}

/**
 * Validate and normalize a manifest icon entry.
 *
 * @param array $icon Raw icon data.
 * @return array|null Manifest-ready icon, or null when invalid.
 */
function vh360_pwa_normalize_manifest_icon( array $icon ) {
	$src     = isset( $icon['src'] ) ? esc_url_raw( (string) $icon['src'] ) : '';
	$sizes   = isset( $icon['sizes'] ) ? trim( (string) $icon['sizes'] ) : '';
	$type    = isset( $icon['type'] ) ? trim( (string) $icon['type'] ) : 'image/png';
	$purpose = isset( $icon['purpose'] ) ? trim( (string) $icon['purpose'] ) : 'any';

	if ( '' === $src || ! wp_http_validate_url( $src ) ) {
		return null;
	}
	if ( ! preg_match( '/^\d+x\d+$/', $sizes ) ) {
		return null;
	}
	if ( '' === $type ) {
		$type = 'image/png';
	}
	if ( ! in_array( $purpose, array( 'any', 'maskable', 'any maskable' ), true ) ) {
		return null;
	}

	return array(
		'src'     => $src,
		'sizes'   => $sizes,
		'type'    => $type,
		'purpose' => $purpose,
	);
}

/**
 * Add a validated, de-duplicated manifest icon to an icon list.
 */
function vh360_pwa_add_manifest_icon( array &$icons, array &$seen, $src, string $sizes, string $purpose = 'any', string $type = 'image/png' ) : void {
	$icon = vh360_pwa_normalize_manifest_icon(
		array(
			'src'     => $src,
			'sizes'   => $sizes,
			'type'    => $type,
			'purpose' => $purpose,
		)
	);
	if ( ! $icon ) {
		return;
	}

	$key = $icon['src'] . '|' . $icon['sizes'] . '|' . $icon['purpose'];
	if ( isset( $seen[ $key ] ) ) {
		return;
	}
	$seen[ $key ] = true;
	$icons[]      = $icon;
}


/**
 * Convert a URL in the WordPress uploads directory to a safe local path.
 *
 * @param string $url URL to resolve.
 * @return string Local path, or empty string when the URL is not in uploads.
 */
function vh360_pwa_url_to_upload_path( $url ) : string {
	$url = esc_url_raw( (string) $url );
	if ( '' === $url || ! wp_http_validate_url( $url ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return '';
	}

	$url_parts  = wp_parse_url( $url );
	$base_parts = wp_parse_url( $uploads['baseurl'] );
	if ( ! is_array( $url_parts ) || ! is_array( $base_parts ) ) {
		return '';
	}

	$url_host  = strtolower( (string) ( $url_parts['host'] ?? '' ) );
	$base_host = strtolower( (string) ( $base_parts['host'] ?? '' ) );
	if ( '' === $url_host || '' === $base_host || $url_host !== $base_host ) {
		return '';
	}

	$url_path  = '/' . ltrim( rawurldecode( (string) ( $url_parts['path'] ?? '' ) ), '/' );
	$base_path = '/' . trim( rawurldecode( (string) ( $base_parts['path'] ?? '' ) ), '/' );
	if ( '/' !== $base_path ) {
		$base_path = trailingslashit( $base_path );
	}

	if ( 0 !== strpos( trailingslashit( $url_path ), $base_path ) && 0 !== strpos( $url_path, $base_path ) ) {
		return '';
	}

	$relative = ltrim( substr( $url_path, strlen( rtrim( $base_path, '/' ) ) ), '/' );
	if ( '' === $relative || false !== strpos( $relative, "\0" ) || preg_match( '#(^|/)\.\.(/|$)#', $relative ) ) {
		return '';
	}

	$base_dir = wp_normalize_path( trailingslashit( $uploads['basedir'] ) );
	$path     = wp_normalize_path( $base_dir . $relative );
	if ( 0 !== strpos( $path, $base_dir ) ) {
		return '';
	}

	return $path;
}

/**
 * Determine whether a generated icon URL maps to an existing local uploads file.
 *
 * Generated icon records must point at actual local upload files; this intentionally
 * does not perform remote HTTP checks and does not inspect arbitrary external URLs.
 *
 * @param string $url Generated icon URL.
 */
function vh360_pwa_generated_icon_file_exists( $url ) : bool {
	$path = vh360_pwa_url_to_upload_path( $url );
	return '' !== $path && file_exists( $path );
}


/**
 * Build a generated icon URL from an option record value.
 *
 * The generator normally stores filenames, but this also safely handles URL-shaped
 * records so stale/unsafe migrated data can be detected instead of silently turned
 * into a local basename.
 */
function vh360_pwa_get_generated_icon_url_from_record( $record, string $base_url ) : string {
	$record = trim( (string) $record );
	if ( '' === $record ) {
		return '';
	}
	if ( wp_http_validate_url( $record ) ) {
		return esc_url_raw( $record );
	}
	if ( false !== strpos( $record, '/' ) || false !== strpos( $record, '\\' ) ) {
		$record = basename( $record );
	}
	return esc_url_raw( trailingslashit( $base_url ) . ltrim( $record, '/' ) );
}

/**
 * Return true when generated icon option records point at invalid or missing files.
 */
function vh360_pwa_has_stale_generated_icons() : bool {
	$generated = get_option( 'vh360_pwa_generated_icons', array() );
	if ( ! is_array( $generated ) || empty( $generated ) ) {
		return false;
	}

	$upload_dir = wp_upload_dir();
	$base_url   = trailingslashit( $upload_dir['baseurl'] . '/vh360-pwa/icons' );
	if ( class_exists( 'VH360_PWA_Icon_Generator' ) ) {
		$generator = new VH360_PWA_Icon_Generator();
		$base_url  = trailingslashit( $generator->get_upload_url() );
	}

	$has_records = false;
	foreach ( array( 'ios', 'android', 'maskable' ) as $group ) {
		if ( empty( $generated[ $group ] ) || ! is_array( $generated[ $group ] ) ) {
			continue;
		}
		foreach ( $generated[ $group ] as $size => $filename ) {
			$has_records = true;
			$size = absint( $size );
			if ( $size < 1 || empty( $filename ) ) {
				return true;
			}
			$url = vh360_pwa_get_generated_icon_url_from_record( $filename, $base_url );
			if ( ! wp_http_validate_url( $url ) || ! vh360_pwa_generated_icon_file_exists( $url ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Resolve all PWA manifest icons from the shared source of truth.
 *
 * Priority: generated icons, legacy/manual fields, WordPress Site Icon, empty array.
 */
function vh360_pwa_get_manifest_icons() : array {
	$icons = array();
	$seen  = array();

	$generated = get_option( 'vh360_pwa_generated_icons', array() );
	if ( is_array( $generated ) && ! empty( $generated ) ) {
		$upload_url = '';
		if ( class_exists( 'VH360_PWA_Icon_Generator' ) ) {
			$generator  = new VH360_PWA_Icon_Generator();
			$upload_url = trailingslashit( $generator->get_upload_url() );
		} else {
			$upload_dir = wp_upload_dir();
			$upload_url = trailingslashit( $upload_dir['baseurl'] . '/vh360-pwa/icons' );
		}

		foreach ( array( 'android', 'ios' ) as $group ) {
			if ( empty( $generated[ $group ] ) || ! is_array( $generated[ $group ] ) ) {
				continue;
			}
			foreach ( $generated[ $group ] as $size => $filename ) {
				$size = absint( $size );
				if ( $size < 1 || empty( $filename ) ) {
					continue;
				}
				$url = vh360_pwa_get_generated_icon_url_from_record( $filename, $upload_url );
				if ( ! vh360_pwa_generated_icon_file_exists( $url ) ) {
					continue;
				}
				vh360_pwa_add_manifest_icon( $icons, $seen, $url, "{$size}x{$size}", 'any' );
			}
		}

		if ( ! empty( $generated['maskable'] ) && is_array( $generated['maskable'] ) ) {
			foreach ( $generated['maskable'] as $size => $filename ) {
				$size = absint( $size );
				if ( $size < 1 || empty( $filename ) ) {
					continue;
				}
				$url = vh360_pwa_get_generated_icon_url_from_record( $filename, $upload_url );
				if ( ! vh360_pwa_generated_icon_file_exists( $url ) ) {
					continue;
				}
				vh360_pwa_add_manifest_icon( $icons, $seen, $url, "{$size}x{$size}", 'maskable' );
			}
		}
	}

	if ( ! empty( $icons ) ) {
		return $icons;
	}

	$opts = vh360_pwa_get_options();
	foreach ( array(
		array( 'key' => 'icon_192', 'sizes' => '192x192', 'purpose' => 'any' ),
		array( 'key' => 'icon_512', 'sizes' => '512x512', 'purpose' => 'any' ),
		array( 'key' => 'icon_maskable_192', 'sizes' => '192x192', 'purpose' => 'maskable' ),
		array( 'key' => 'icon_maskable_512', 'sizes' => '512x512', 'purpose' => 'maskable' ),
	) as $config ) {
		vh360_pwa_add_manifest_icon( $icons, $seen, $opts[ $config['key'] ] ?? '', $config['sizes'], $config['purpose'] );
	}

	if ( ! empty( $icons ) ) {
		return $icons;
	}

	if ( function_exists( 'get_site_icon_url' ) ) {
		$site_icon = get_site_icon_url( 512 );
		if ( $site_icon ) {
			vh360_pwa_add_manifest_icon( $icons, $seen, $site_icon, '512x512', 'any' );
		}
	}

	return $icons;
}

/**
 * Resolve the best available iOS apple-touch-icon URL.
 */
function vh360_pwa_get_apple_touch_icon_url() : string {
	$generated = get_option( 'vh360_pwa_generated_icons', array() );
	$upload_url = '';
	if ( is_array( $generated ) && class_exists( 'VH360_PWA_Icon_Generator' ) ) {
		$generator  = new VH360_PWA_Icon_Generator();
		$upload_url = trailingslashit( $generator->get_upload_url() );
	}
	foreach ( array( array( 'ios', 180 ), array( 'android', 192 ), array( 'ios', 192 ) ) as $candidate ) {
		if ( $upload_url && ! empty( $generated[ $candidate[0] ][ $candidate[1] ] ) ) {
			$url = vh360_pwa_get_generated_icon_url_from_record( $generated[ $candidate[0] ][ $candidate[1] ], $upload_url );
			if ( wp_http_validate_url( $url ) && vh360_pwa_generated_icon_file_exists( $url ) ) {
				return $url;
			}
		}
	}
	$opts = vh360_pwa_get_options();
	if ( ! empty( $opts['icon_192'] ) && wp_http_validate_url( $opts['icon_192'] ) ) {
		return esc_url_raw( $opts['icon_192'] );
	}
	$site_icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 180 ) : '';
	return ( $site_icon && wp_http_validate_url( $site_icon ) ) ? esc_url_raw( $site_icon ) : '';
}

/**
 * Backfill legacy icon option fields from generated icons without writing empty values.
 */
function vh360_pwa_backfill_legacy_icons_from_generated() : void {
	$generated = get_option( 'vh360_pwa_generated_icons', array() );
	if ( ! is_array( $generated ) || empty( $generated ) ) {
		return;
	}
	$generator  = class_exists( 'VH360_PWA_Icon_Generator' ) ? new VH360_PWA_Icon_Generator() : null;
	$upload_dir = wp_upload_dir();
	$base_url   = trailingslashit( $generator ? $generator->get_upload_url() : $upload_dir['baseurl'] . '/vh360-pwa/icons' );
	$opts       = vh360_pwa_get_options();
	$map        = array(
		'icon_192'          => array( 'android', 192 ),
		'icon_512'          => array( 'android', 512 ),
		'icon_maskable_192' => array( 'maskable', 192 ),
		'icon_maskable_512' => array( 'maskable', 512 ),
	);
	$updates = array();
	foreach ( $map as $option_key => $source ) {
		if ( empty( $generated[ $source[0] ][ $source[1] ] ) ) {
			continue;
		}
		$url = vh360_pwa_get_generated_icon_url_from_record( $generated[ $source[0] ][ $source[1] ], $base_url );
		if ( $url && wp_http_validate_url( $url ) ) {
			$updates[ $option_key ] = $url;
		}
	}
	if ( ! empty( $updates ) ) {
		vh360_pwa_update_options( $updates );
	}
}
