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
		'background_color'     => '#ffffff',
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
				vh360_pwa_add_manifest_icon( $icons, $seen, $upload_url . ltrim( basename( (string) $filename ), '/' ), "{$size}x{$size}", 'any' );
			}
		}

		if ( ! empty( $generated['maskable'] ) && is_array( $generated['maskable'] ) ) {
			foreach ( $generated['maskable'] as $size => $filename ) {
				$size = absint( $size );
				if ( $size < 1 || empty( $filename ) ) {
					continue;
				}
				vh360_pwa_add_manifest_icon( $icons, $seen, $upload_url . ltrim( basename( (string) $filename ), '/' ), "{$size}x{$size}", 'maskable' );
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
			$url = esc_url_raw( $upload_url . basename( (string) $generated[ $candidate[0] ][ $candidate[1] ] ) );
			if ( wp_http_validate_url( $url ) ) {
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
		$url = esc_url_raw( $base_url . basename( (string) $generated[ $source[0] ][ $source[1] ] ) );
		if ( $url && wp_http_validate_url( $url ) ) {
			$updates[ $option_key ] = $url;
		}
	}
	if ( ! empty( $updates ) ) {
		vh360_pwa_update_options( $updates );
	}
}
