<?php
/**
 * Portable VideoHub360 Core settings.
 *
 * This is the single allowlist used by theme exports and both settings importers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VideoHub360_Portable_Settings {
	/**
	 * Return the closed portable-settings schema.
	 *
	 * @return array
	 */
	private static function schema() {
		return array(
			'videohub360_post_slug'                         => array( 'option' => 'videohub360_post_slug', 'default' => 'videohub360', 'type' => 'slug' ),
			'videohub360_category_slug'                     => array( 'option' => 'videohub360_category_slug', 'default' => 'videohub360-category', 'type' => 'slug' ),
			'videohub360_location_slug'                     => array( 'option' => 'videohub360_location_slug', 'default' => 'videohub360-location', 'type' => 'slug' ),
			'videohub360_series_slug'                       => array( 'option' => 'videohub360_series_slug', 'default' => 'videohub360-series', 'type' => 'slug' ),
			'videohub360_show_archive_header'               => array( 'option' => 'videohub360_show_archive_header', 'default' => 1, 'type' => 'bool' ),
			'videohub360_archive_title'                     => array( 'option' => 'videohub360_archive_title', 'default' => 'Archive', 'type' => 'text' ),
			'videohub360_show_archive_search'               => array( 'option' => 'videohub360_show_archive_search', 'default' => 1, 'type' => 'bool' ),
			'videohub360_show_category_filter'              => array( 'option' => 'videohub360_show_category_filter', 'default' => 1, 'type' => 'bool' ),
			'videohub360_show_series_filter'                => array( 'option' => 'videohub360_show_series_filter', 'default' => 1, 'type' => 'bool' ),
			'videohub360_show_location_filter'              => array( 'option' => 'videohub360_show_location_filter', 'default' => 1, 'type' => 'bool' ),
			'videohub360_category_label'                    => array( 'option' => 'videohub360_category_label', 'default' => 'Category', 'type' => 'text' ),
			'videohub360_series_label'                      => array( 'option' => 'videohub360_series_label', 'default' => 'Series', 'type' => 'text' ),
			'videohub360_location_label'                    => array( 'option' => 'videohub360_location_label', 'default' => 'Location', 'type' => 'text' ),
			'videohub360_single_video_layout_default'       => array( 'option' => 'videohub360_single_video_layout_default', 'default' => 'sidebar', 'type' => 'layout', 'allowed' => array( 'sidebar', 'full-width' ) ),
			'videohub360_course_lesson_layout_default'      => array( 'option' => 'videohub360_course_lesson_layout_default', 'default' => 'full-width', 'type' => 'layout', 'allowed' => array( 'inherit', 'sidebar', 'full-width' ) ),
			'videohub360_livestream_video_layout_default'   => array( 'option' => 'videohub360_livestream_video_layout_default', 'default' => 'full-width', 'type' => 'layout', 'allowed' => array( 'inherit', 'sidebar', 'full-width' ) ),
			'videohub360_chat_enabled'                      => array( 'option' => 'videohub360_chat_enabled', 'default' => 1, 'type' => 'bool' ),
			'videohub360_chat_placement'                    => array( 'option' => 'videohub360_chat_placement', 'default' => 'popup', 'type' => 'choice', 'allowed' => array( 'inline', 'popup', 'sidebar', 'off' ) ),
			'videohub360_chat_cleanup_days'                 => array( 'option' => 'videohub360_chat_cleanup_days', 'default' => 30, 'type' => 'integer', 'minimum' => 1 ),
			'videohub360_chat_rate_limit'                   => array( 'option' => 'videohub360_chat_rate_limit', 'default' => 5, 'type' => 'integer', 'minimum' => 1 ),
			'videohub360_chat_message_limit'                => array( 'option' => 'videohub360_chat_message_limit', 'default' => 500, 'type' => 'integer', 'minimum' => 10 ),
			'videohub360_force_login_everyone_host'         => array( 'option' => 'videohub360_force_login_everyone_host', 'default' => 1, 'type' => 'bool' ),
			'videohub360_login_modal_type'                  => array( 'option' => 'videohub360_login_modal_type', 'default' => 'default', 'type' => 'choice', 'allowed' => array( 'default', 'shortcode', 'redirect', 'javascript', 'builtin' ) ),
			'videohub360_login_modal_shortcode'             => array( 'option' => 'videohub360_login_modal_shortcode', 'default' => '', 'type' => 'text' ),
			'videohub360_login_modal_redirect_url'          => array( 'option' => 'videohub360_login_modal_redirect_url', 'default' => '', 'type' => 'redirect' ),
			'videohub360_login_modal_js_function'           => array( 'option' => 'videohub360_login_modal_js_function', 'default' => '', 'type' => 'text' ),
			'videohub360_default_quality'                   => array( 'option' => 'videohub360_default_quality', 'default' => 'high', 'type' => 'quality' ),
			'videohub360_default_mirror'                    => array( 'option' => 'videohub360_default_mirror', 'default' => 'disabled', 'type' => 'mirror' ),
			'videohub360_allow_quality_switching'           => array( 'option' => 'videohub360_allow_quality_switching', 'default' => 1, 'type' => 'bool' ),
			'videohub360_allow_mirror_control'              => array( 'option' => 'videohub360_allow_mirror_control', 'default' => 1, 'type' => 'bool' ),
			'videohub360_enable_4k_streaming'               => array( 'option' => 'videohub360_enable_4k_streaming', 'default' => 0, 'type' => 'bool' ),
			'videohub360_show_quality_badge'                => array( 'option' => 'videohub360_show_quality_badge', 'default' => 1, 'type' => 'bool' ),
			'vh360_default_stream_ended_html'               => array( 'option' => 'vh360_default_stream_ended_html', 'default' => '', 'type' => 'html' ),
			'vh360_default_live_room_offline_html'          => array( 'option' => 'vh360_default_live_room_offline_html', 'default' => '', 'type' => 'html' ),
			'vh360_stream_ended_by_moderator_html'          => array( 'option' => 'vh360_stream_ended_by_moderator_html', 'default' => '', 'type' => 'html' ),
			'vh360_stream_ended_needs_restart_html'         => array( 'option' => 'vh360_stream_ended_needs_restart_html', 'default' => '', 'type' => 'html' ),
			'vh360_stream_replay_processing_html'           => array( 'option' => 'vh360_stream_replay_processing_html', 'default' => '', 'type' => 'html' ),
			'vh360_default_stream_ended_icon'               => array( 'option' => 'vh360_default_stream_ended_icon', 'default' => '📴', 'type' => 'text' ),
			'vh360_default_live_room_offline_icon'          => array( 'option' => 'vh360_default_live_room_offline_icon', 'default' => '🔴', 'type' => 'text' ),
			'enable_course_features'                        => array( 'option' => 'videohub360_enable_course_features', 'default' => 0, 'type' => 'bool' ),
		);
	}

	/** Export all portable settings. */
	public static function export_settings() {
		$settings = array();
		foreach ( self::schema() as $key => $field ) {
			$value = get_option( $field['option'], $field['default'] );
			$settings[ $key ] = 'redirect' === $field['type'] ? self::export_redirect( $value ) : self::sanitize( $value, $field );
		}

		// A redirect without a portable same-site target must not select a broken mode.
		if ( 'redirect' === $settings['videohub360_login_modal_type'] && '' === $settings['videohub360_login_modal_redirect_url'] ) {
			$settings['videohub360_login_modal_type'] = 'default';
		}

		return $settings;
	}

	/**
	 * Import only keys that are present in the supplied group.
	 *
	 * @return array Counts and whether a rewrite slug changed.
	 */
	public static function import_settings( $settings ) {
		$result = array( 'imported' => 0, 'skipped' => 0, 'slugs_changed' => false );
		if ( ! is_array( $settings ) ) {
			return $result;
		}

		$schema = self::schema();
		foreach ( $settings as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				$result['skipped']++;
				continue;
			}
			$field = $schema[ $key ];
			$value = self::sanitize( $value, $field );
			$old_value = get_option( $field['option'], $field['default'] );
			update_option( $field['option'], $value );
			$result['imported']++;
			if ( 'slug' === $field['type'] && (string) $old_value !== (string) $value ) {
				$result['slugs_changed'] = true;
			}
		}

		// Older and hand-authored files may request redirect mode without a safe URL.
		if ( ( array_key_exists( 'videohub360_login_modal_type', $settings ) || array_key_exists( 'videohub360_login_modal_redirect_url', $settings ) ) && 'redirect' === get_option( 'videohub360_login_modal_type' ) ) {
			$redirect = array_key_exists( 'videohub360_login_modal_redirect_url', $settings ) ? get_option( 'videohub360_login_modal_redirect_url', '' ) : '';
			if ( '' === $redirect ) {
				update_option( 'videohub360_login_modal_type', 'default' );
			}
		}

		return $result;
	}

	private static function sanitize( $value, $field ) {
		switch ( $field['type'] ) {
			case 'bool': return empty( $value ) ? 0 : 1;
			case 'slug': return sanitize_title( $value );
			case 'text': return sanitize_text_field( $value );
			case 'html': return wp_kses_post( $value );
			case 'integer': return max( $field['minimum'], absint( $value ) );
			case 'choice':
				$value = sanitize_text_field( $value );
				return in_array( $value, $field['allowed'], true ) ? $value : $field['default'];
			case 'layout':
				return videohub360_sanitize_single_video_layout_value( $value, $field['allowed'], $field['default'] );
			case 'quality':
				$value = VideoHub360_Video_Quality::validate_quality( $value );
				return false !== $value ? $value : $field['default'];
			case 'mirror':
				$value = VideoHub360_Video_Quality::validate_mirror( $value );
				return false !== $value ? $value : $field['default'];
			case 'redirect': return self::import_redirect( $value );
		}
		return $field['default'];
	}

	/** Convert a same-site URL to a path relative to this WordPress installation. */
	private static function export_redirect( $url ) {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return '';
		}

		// Root-relative values are already independent of the source hostname/home path.
		if ( '/' === substr( $url, 0, 1 ) && 0 !== strpos( $url, '//' ) ) {
			return $url;
		}

		$source = wp_parse_url( home_url( '/' ) );
		$target = wp_parse_url( $url );
		if ( empty( $target['host'] ) || empty( $source['host'] ) || strtolower( $target['host'] ) !== strtolower( $source['host'] ) ) {
			return '';
		}

		$home_path   = isset( $source['path'] ) ? '/' . trim( $source['path'], '/' ) : '';
		$home_path   = '/' === $home_path ? '' : $home_path;
		$target_path = isset( $target['path'] ) ? '/' . ltrim( $target['path'], '/' ) : '/';

		// Same hostname is not enough: the target must belong to this home_url() path.
		if ( '' !== $home_path && $target_path !== $home_path && 0 !== strpos( $target_path, $home_path . '/' ) ) {
			return '';
		}

		$portable = '' === $home_path ? $target_path : substr( $target_path, strlen( $home_path ) );
		$portable = '' === $portable ? '/' : $portable;
		if ( isset( $target['query'] ) ) {
			$portable .= '?' . $target['query'];
		}
		if ( isset( $target['fragment'] ) ) {
			$portable .= '#' . $target['fragment'];
		}
		return $portable;
	}

	/** Rebuild only a host-free portable redirect against this installation. */
	private static function import_redirect( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value || '/' !== substr( $value, 0, 1 ) || 0 === strpos( $value, '//' ) || wp_parse_url( $value, PHP_URL_SCHEME ) || wp_parse_url( $value, PHP_URL_HOST ) ) {
			return '';
		}
		return esc_url_raw( home_url( '/' . ltrim( $value, '/' ) ) );
	}
}
