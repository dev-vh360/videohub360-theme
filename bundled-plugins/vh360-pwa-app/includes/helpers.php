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

