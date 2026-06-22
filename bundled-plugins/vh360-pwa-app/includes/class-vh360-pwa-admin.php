<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Admin {
	private $page_slug = 'vh360-pwa-app';

	public function register() : void {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_icon_actions' ) );
		add_action( 'admin_init', array( $this, 'handle_asset_regeneration' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'update_option_vh360_pwa_options', array( $this, 'maybe_flush_rewrite_on_option_update' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'theme_notice' ) );
		add_action( 'wp_ajax_vh360_pwa_health_check', array( $this, 'ajax_health_check' ) );
	}

	public function admin_menu() : void {
		add_menu_page(
			__( 'VH360 PWA & App', 'vh360-pwa-app' ),
			__( 'VH360 PWA & App', 'vh360-pwa-app' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' ),
			'dashicons-smartphone',
			58
		);
		
		// Add App Store submenu
		add_submenu_page(
			$this->page_slug,
			__( 'App Store Export', 'vh360-pwa-app' ),
			__( 'App Store', 'vh360-pwa-app' ),
			'manage_options',
			'vh360-pwa-app-store',
			array( $this, 'render_appstore_page' )
		);
	}


	public function handle_asset_regeneration() : void {
		if ( empty( $_POST['vh360_pwa_regenerate_assets'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'vh360_pwa_regenerate_assets' );

		$old_opts = vh360_pwa_get_options();
		$old_version = function_exists( 'vh360_pwa_get_asset_version' ) ? vh360_pwa_get_asset_version( $old_opts ) : 0;
		$new_version = function_exists( 'vh360_pwa_bump_asset_version' ) ? vh360_pwa_bump_asset_version() : time();

		if ( function_exists( 'vh360_pwa_clear_ios_startup_images' ) ) { vh360_pwa_clear_ios_startup_images(); }
		$startup_images = function_exists( 'vh360_pwa_generate_ios_startup_images' ) ? vh360_pwa_generate_ios_startup_images() : array();
		$startup_error = get_option( 'vh360_pwa_ios_startup_images_last_error', '' );
		$root_results = class_exists( 'VH360_PWA_Root_Files' ) ? VH360_PWA_Root_Files::ensure_root_files() : array();
		$purge_results = $this->purge_common_caches();

		update_option( 'vh360_pwa_last_regeneration', array(
			'old_version' => $old_version,
			'new_version' => $new_version,
			'generated_at' => time(),
			'startup_count' => is_array( $startup_images ) ? count( $startup_images ) : 0,
			'startup_images' => $startup_images,
			'startup_error' => is_string( $startup_error ) ? $startup_error : '',
			'root_results' => $root_results,
			'purge_results' => $purge_results,
		) );

		wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=tools&vh360_pwa_assets_regenerated=1' ) );
		exit;
	}

	private function purge_common_caches() : array {
		$results = array();
		if ( has_action( 'litespeed_purge_all' ) ) { do_action( 'litespeed_purge_all' ); $results['litespeed'] = 'attempted'; }
		if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); $results['wp_rocket'] = 'attempted'; }
		if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); $results['w3_total_cache'] = 'attempted'; }
		if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); $results['wp_super_cache'] = 'attempted'; }
		if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); $results['object_cache'] = 'attempted'; }
		if ( empty( $results ) ) { $results['none'] = 'no_supported_cache_plugin_detected'; }
		return $results;
	}

    /**
     * Render the Tools tab for resetting the install banner dismissal.
     *
     * @param array $opts Current options array (unused but kept for parity with other render methods).
     */
    private function render_tab_tools( array $opts ) : void {
        $base = home_url( '/' );

        $url_reset_banner = esc_url( add_query_arg( array( 'vh360_pwa_reset' => '1' ), $base ) );
        $url_clear_caches = esc_url( add_query_arg( array( 'vh360_pwa_tool' => 'clear_caches' ), $base ) );
        $url_unreg_sw     = esc_url( add_query_arg( array( 'vh360_pwa_tool' => 'unregister_sw' ), $base ) );
        $url_reset_all    = esc_url( add_query_arg( array( 'vh360_pwa_tool' => 'reset_device' ), $base ) );

        if ( ! empty( $_GET['vh360_pwa_assets_regenerated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'PWA assets regenerated and common caches purged where available.', 'vh360-pwa-app' ) . '</p></div>';
        }

        $asset_version = function_exists( 'vh360_pwa_get_asset_version' ) ? vh360_pwa_get_asset_version( $opts ) : 0;
        $versioned_manifest = function_exists( 'vh360_pwa_version_url' ) ? vh360_pwa_version_url( home_url( '/' . VH360_PWA_MANIFEST_SLUG ), $opts ) : home_url( '/' . VH360_PWA_MANIFEST_SLUG );
        $versioned_sw = function_exists( 'vh360_pwa_version_url' ) ? vh360_pwa_version_url( home_url( '/' . VH360_PWA_SW_SLUG ), $opts ) : home_url( '/' . VH360_PWA_SW_SLUG );
        $versioned_offline = function_exists( 'vh360_pwa_version_url' ) ? vh360_pwa_version_url( home_url( '/' . VH360_PWA_OFFLINE_SLUG ), $opts ) : home_url( '/' . VH360_PWA_OFFLINE_SLUG );
        $startup_images = function_exists( 'vh360_pwa_get_ios_startup_images' ) ? vh360_pwa_get_ios_startup_images() : array();
        $first_startup = ! empty( $startup_images[0]['href'] ) ? (string) $startup_images[0]['href'] : '';
        $last_regen = get_option( 'vh360_pwa_last_regeneration', array() );

        echo '<h2>' . esc_html__( 'Current PWA Asset Diagnostics', 'vh360-pwa-app' ) . '</h2>';
        echo '<table class="widefat striped" style="max-width:980px"><tbody>';
        echo '<tr><th>' . esc_html__( 'Asset version', 'vh360-pwa-app' ) . '</th><td><code>' . esc_html( (string) $asset_version ) . '</code></td></tr>';
        echo '<tr><th>' . esc_html__( 'Manifest URL', 'vh360-pwa-app' ) . '</th><td><a href="' . esc_url( $versioned_manifest ) . '" target="_blank" rel="noopener"><code>' . esc_html( $versioned_manifest ) . '</code></a></td></tr>';
        echo '<tr><th>' . esc_html__( 'Service worker URL', 'vh360-pwa-app' ) . '</th><td><a href="' . esc_url( $versioned_sw ) . '" target="_blank" rel="noopener"><code>' . esc_html( $versioned_sw ) . '</code></a></td></tr>';
        echo '<tr><th>' . esc_html__( 'Offline URL', 'vh360-pwa-app' ) . '</th><td><a href="' . esc_url( $versioned_offline ) . '" target="_blank" rel="noopener"><code>' . esc_html( $versioned_offline ) . '</code></a></td></tr>';
        echo '<tr><th>' . esc_html__( 'Generated iOS startup images', 'vh360-pwa-app' ) . '</th><td>' . esc_html( (string) count( $startup_images ) ) . ( $first_startup ? ' — <a href="' . esc_url( $first_startup ) . '" target="_blank" rel="noopener">' . esc_html__( 'View first image', 'vh360-pwa-app' ) . '</a>' : '' ) . '</td></tr>';
        echo '<tr><th>' . esc_html__( 'Last regeneration', 'vh360-pwa-app' ) . '</th><td>' . esc_html( ! empty( $last_regen['generated_at'] ) ? date_i18n( 'Y-m-d H:i:s', (int) $last_regen['generated_at'] ) : __( 'Never', 'vh360-pwa-app' ) ) . '</td></tr>';
        echo '</tbody></table>';

        if ( is_array( $last_regen ) && ! empty( $last_regen ) ) {
            echo '<h3>' . esc_html__( 'Last Regeneration Results', 'vh360-pwa-app' ) . '</h3>';
            echo '<pre style="max-width:980px;white-space:pre-wrap;background:#fff;border:1px solid #ccd0d4;padding:12px;">' . esc_html( wp_json_encode( $last_regen, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>';
        }
        echo '<p class="description" style="max-width:980px;">' . esc_html__( 'Some hosts/CDNs, including server-level cache layers, may require manual purge if the page HTML itself is cached. The versioned URLs above confirm whether VH360 generated fresh PWA assets. If an already-installed iPhone/iPad app still shows old launch assets, remove it from the Home Screen and add it again from Safari.', 'vh360-pwa-app' ) . '</p>';

        echo '<p>' . esc_html__( 'These tools run in your browser (per device) and can help resolve stale service worker or caching issues during setup and support.', 'vh360-pwa-app' ) . '</p>';

        echo '<div class="vh360-pwa-tools" style="display:flex;flex-wrap:wrap;gap:10px;margin:12px 0;">';
        printf( '<a href="%s" class="button" target="_blank">%s</a>', $url_clear_caches, esc_html__( 'Clear PWA Caches (This Device)', 'vh360-pwa-app' ) );
        printf( '<a href="%s" class="button" target="_blank">%s</a>', $url_unreg_sw, esc_html__( 'Unregister Service Worker (This Device)', 'vh360-pwa-app' ) );
        printf( '<a href="%s" class="button button-primary" target="_blank">%s</a>', $url_reset_all, esc_html__( 'Reset Everything (This Device)', 'vh360-pwa-app' ) );
        printf( '<a href="%s" class="button" target="_blank">%s</a>', $url_reset_banner, esc_html__( 'Reset Install Banner Dismissal', 'vh360-pwa-app' ) );
        echo '</div>';

        echo '<p class="description" style="max-width:820px;">' . esc_html__( 'Each button opens your site in a new tab with a one-time action. After the action runs, you can close that tab and return here.', 'vh360-pwa-app' ) . '</p>';

        echo '<hr><h2>' . esc_html__( 'PWA Asset Cache Busting', 'vh360-pwa-app' ) . '</h2>';
        echo '<p>' . esc_html__( 'Use this after changing splash screens, app icons, manifest identity, or launch-screen styling. It bumps PWA asset URLs, regenerates launch images, rewrites root files, and attempts common cache-plugin purges.', 'vh360-pwa-app' ) . '</p>';
        echo '<form method="post">';
        wp_nonce_field( 'vh360_pwa_regenerate_assets' );
        echo '<button type="submit" name="vh360_pwa_regenerate_assets" value="1" class="button button-primary">' . esc_html__( 'Regenerate PWA Assets', 'vh360-pwa-app' ) . '</button>';
        echo '</form>';
    }

	public function register_settings() : void {
		register_setting(
			'vh360_pwa_options_group',
			'vh360_pwa_options',
			array( $this, 'sanitize_options' )
		);
	}

	public function sanitize_options( $input ) : array {
		$input = is_array( $input ) ? $input : array();
		$current = vh360_pwa_get_options();
		$out = $current;

		$out['enabled'] = vh360_pwa_boolval( $input['enabled'] ?? 0 );
		if ( function_exists( 'vh360_pwa_is_licensed' ) && ! vh360_pwa_is_licensed() ) {
			$out['enabled'] = 0;
		}
$out['app_name'] = sanitize_text_field( $input['app_name'] ?? $current['app_name'] );
		$out['short_name'] = sanitize_text_field( $input['short_name'] ?? $current['short_name'] );
		$out['description'] = sanitize_text_field( $input['description'] ?? $current['description'] );

		$out['theme_color'] = sanitize_hex_color( $input['theme_color'] ?? $current['theme_color'] ) ?: $current['theme_color'];
		$out['background_color'] = sanitize_hex_color( $input['background_color'] ?? $current['background_color'] ) ?: $current['background_color'];
		$out['enable_pull_to_refresh'] = vh360_pwa_boolval( $input['enable_pull_to_refresh'] ?? 0 );
		unset( $out['show_refresh_button'], $out['refresh_label'] );
		$out['splash_enabled'] = vh360_pwa_boolval( $input['splash_enabled'] ?? 0 );
		$out['splash_background_color'] = sanitize_hex_color( $input['splash_background_color'] ?? $current['splash_background_color'] ) ?: $out['background_color'];
		$out['splash_logo'] = ! empty( $input['splash_logo'] ) ? esc_url_raw( (string) $input['splash_logo'] ) : '';
		$out['splash_title'] = sanitize_text_field( $input['splash_title'] ?? $current['splash_title'] );
		$out['splash_title_enabled'] = vh360_pwa_boolval( $input['splash_title_enabled'] ?? 0 );
		$out['splash_title_font_size'] = max( 18, min( 96, absint( $input['splash_title_font_size'] ?? $current['splash_title_font_size'] ) ) );
		$out['splash_title_color'] = sanitize_hex_color( $input['splash_title_color'] ?? $current['splash_title_color'] ) ?: '#ffffff';
		$out['splash_title_offset'] = max( 20, min( 200, absint( $input['splash_title_offset'] ?? $current['splash_title_offset'] ) ) );
		$out['display'] = in_array( (string) ( $input['display'] ?? '' ), array( 'standalone','fullscreen','minimal-ui','browser' ), true ) ? (string) $input['display'] : $current['display'];
		$out['orientation'] = in_array( (string) ( $input['orientation'] ?? '' ), array( 'any','portrait','portrait-primary','landscape' ), true ) ? (string) $input['orientation'] : $current['orientation'];

		
$start_url = trim( (string) ( $input['start_url'] ?? $current['start_url'] ) );
$scope     = trim( (string) ( $input['scope'] ?? $current['scope'] ) );

// Normalize to RELATIVE paths to avoid iOS/Android scope mismatches (www vs non-www, http vs https).
// Accept either a relative path ("/") or an absolute URL on this site, but store only the path+query+fragment.
$normalize_to_path = function( $value ) {
    $value = trim( (string) $value );
    if ( $value === '' ) {
        return '/';
    }
    // If it's a path already:
    if ( 0 === strpos( $value, '/' ) ) {
        return $value;
    }
    // If it's a full URL, extract path/query/fragment.
    $parts = wp_parse_url( $value );
    if ( is_array( $parts ) ) {
        $path = isset( $parts['path'] ) ? $parts['path'] : '/';
        $q    = isset( $parts['query'] ) ? ('?' . $parts['query']) : '';
        $h    = isset( $parts['fragment'] ) ? ('#' . $parts['fragment']) : '';
        if ( $path === '' ) { $path = '/'; }
        if ( 0 !== strpos( $path, '/' ) ) { $path = '/' . ltrim( $path, '/' ); }
        return $path . $q . $h;
    }
    return '/';
};

$out['start_url'] = $normalize_to_path( $start_url );
$out['scope']     = $normalize_to_path( $scope );

		$out['cache_strategy'] = in_array( (string) ( $input['cache_strategy'] ?? '' ), array( 'safe','balanced','aggressive' ), true ) ? (string) $input['cache_strategy'] : $current['cache_strategy'];
		$out['cache_version'] = sanitize_text_field( $input['cache_version'] ?? $current['cache_version'] );
		$out['precache_offline'] = vh360_pwa_boolval( $input['precache_offline'] ?? $current['precache_offline'] );
		$out['precache_home'] = vh360_pwa_boolval( $input['precache_home'] ?? $current['precache_home'] );
		$out['precache_urls'] = sanitize_textarea_field( $input['precache_urls'] ?? $current['precache_urls'] );

		$out['show_install_prompt'] = vh360_pwa_boolval( $input['show_install_prompt'] ?? $current['show_install_prompt'] );
		$out['install_prompt_text'] = sanitize_text_field( $input['install_prompt_text'] ?? $current['install_prompt_text'] );

		$out['show_install_banner'] = vh360_pwa_boolval( $input['show_install_banner'] ?? $current['show_install_banner'] );
		$out['install_banner_text'] = sanitize_text_field( $input['install_banner_text'] ?? $current['install_banner_text'] );
		$out['install_banner_dismiss_days'] = max( 1, min( 365, absint( $input['install_banner_dismiss_days'] ?? $current['install_banner_dismiss_days'] ) ) );
		$out['show_ios_onboarding'] = vh360_pwa_boolval( $input['show_ios_onboarding'] ?? $current['show_ios_onboarding'] );
		unset( $out['show_ios_reinstall_notice'], $out['ios_reinstall_notice_text'] );

		$out['debug_mode'] = vh360_pwa_boolval( $input['debug_mode'] ?? $current['debug_mode'] );

		// Icons: store as URLs.
		foreach ( array('icon_192','icon_512','icon_maskable_192','icon_maskable_512') as $k ) {
			$val = trim( (string) ( $input[ $k ] ?? $current[ $k ] ) );
			$out[ $k ] = $val ? esc_url_raw( $val ) : '';
		}
		
		$out['pwa_asset_version'] = ! empty( $current['pwa_asset_version'] ) ? absint( $current['pwa_asset_version'] ) : time();
		$asset_keys = array( 'splash_enabled', 'splash_logo', 'splash_background_color', 'splash_title', 'splash_title_enabled', 'splash_title_font_size', 'splash_title_color', 'splash_title_offset', 'icon_192', 'icon_512', 'icon_maskable_192', 'icon_maskable_512', 'app_name', 'short_name', 'theme_color', 'background_color', 'start_url', 'cache_version' );
		foreach ( $asset_keys as $asset_key ) {
			if ( ( $current[ $asset_key ] ?? null ) !== ( $out[ $asset_key ] ?? null ) ) {
				$out['pwa_asset_version'] = max( time(), absint( $out['pwa_asset_version'] ) + 1 );
				break;
			}
		}

		// Push Sender Roles: array of role keys that can send push notifications
		$out['push_sender_roles'] = array();
		if ( isset( $input['push_sender_roles'] ) && is_array( $input['push_sender_roles'] ) ) {
			// Validate that each role exists
			$all_roles = wp_roles()->roles;
			foreach ( $input['push_sender_roles'] as $role_key ) {
				$role_key = sanitize_key( $role_key );
				if ( isset( $all_roles[ $role_key ] ) ) {
					$out['push_sender_roles'][] = $role_key;
				}
			}
		}
		// Ensure administrator is always included
		if ( ! in_array( 'administrator', $out['push_sender_roles'], true ) ) {
			$out['push_sender_roles'][] = 'administrator';
		}

		return $out;
	}

	public function enqueue_admin_assets( string $hook ) : void {
		if ( false === strpos( $hook, $this->page_slug ) && false === strpos( $hook, 'vh360-pwa-app-store' ) ) {
			return;
		}

		// WP color picker for color fields.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'vh360-pwa-admin',
			VH360_PWA_APP_URL . 'assets/admin/pwa-admin.css',
			array( 'wp-color-picker' ),
			VH360_PWA_APP_VERSION
		);
		wp_enqueue_script(
			'vh360-pwa-admin',
			VH360_PWA_APP_URL . 'assets/admin/pwa-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			VH360_PWA_APP_VERSION,
			true
		);
		wp_localize_script(
			'vh360-pwa-admin',
			'VH360PWAAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vh360_pwa_admin' ),
			)
		);
		wp_enqueue_media();
		
		// Enqueue App Store admin assets on the App Store page
		if ( false !== strpos( $hook, 'vh360-pwa-app-store' ) ) {
			wp_enqueue_style(
				'vh360-pwa-appstore-admin',
				VH360_PWA_APP_URL . 'assets/admin/appstore-admin.css',
				array(),
				VH360_PWA_APP_VERSION
			);
			wp_enqueue_script(
				'vh360-pwa-appstore-admin',
				VH360_PWA_APP_URL . 'assets/admin/appstore-admin.js',
				array( 'jquery' ),
				VH360_PWA_APP_VERSION,
				true
			);
		}
	}

	public function theme_notice() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$status = get_option( 'vh360_pwa_root_manifest_write_status', array() );
		if ( is_array( $status ) && isset( $status['success'] ) && ! $status['success'] ) {
			$path = isset( $status['path'] ) ? (string) $status['path'] : 'vh360-manifest.json';
			echo '<div class="notice notice-error"><p><strong>VH360 PWA &amp; App:</strong> ' . esc_html( sprintf( __( 'The root manifest file could not be written at %s. WordPress will continue serving the dynamic manifest endpoint when rewrites are available, but please check file permissions so /vh360-manifest.json can be refreshed.', 'vh360-pwa-app' ), $path ) ) . '</p></div>';
		}
		if ( vh360_pwa_is_allowed_theme_active() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>VH360 PWA &amp; App:</strong> This plugin is designed for the Videohub360 Theme. Activate the Videohub360 Theme to enable PWA endpoints and frontend scripts.</p></div>';
	}
	
	/**
	 * Handle icon upload and generation actions.
	 */
	public function handle_icon_actions() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Handle master icon upload
		if ( isset( $_POST['vh360_pwa_upload_master_icon'] ) && isset( $_FILES['master_icon'] ) ) {
			check_admin_referer( 'vh360_pwa_upload_master_icon' );
			$this->handle_master_icon_upload();
			return;
		}
		
		// Handle icon generation
		if ( isset( $_POST['vh360_pwa_generate_icons'] ) || isset( $_POST['vh360_pwa_regenerate_icons_assets'] ) ) {
			check_admin_referer( 'vh360_pwa_generate_icons' );
			$this->handle_icon_generation();
			return;
		}

		if ( isset( $_POST['vh360_pwa_clear_generated_icons'] ) ) {
			check_admin_referer( 'vh360_pwa_clear_generated_icons' );
			$this->handle_clear_generated_icons();
			return;
		}
	}
	
	/**
	 * Handle master icon upload.
	 */
	private function handle_master_icon_upload() : void {
		if ( empty( $_FILES['master_icon']['tmp_name'] ) ) {
			add_settings_error(
				'vh360_pwa_options',
				'no_file',
				__( 'No file uploaded.', 'vh360-pwa-app' ),
				'error'
			);
			return;
		}
		
		// Validate file type
		$file_type = wp_check_filetype( $_FILES['master_icon']['name'] );
		$allowed_types = array( 'image/png', 'image/jpeg', 'image/jpg' );
		
		if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
			add_settings_error(
				'vh360_pwa_options',
				'invalid_type',
				__( 'Invalid file type. Please upload a PNG or JPEG image.', 'vh360-pwa-app' ),
				'error'
			);
			return;
		}
		
		// Use WordPress upload handler
		require_once ABSPATH . 'wp-admin/includes/file.php';
		
		$upload = wp_handle_upload(
			$_FILES['master_icon'],
			array( 'test_form' => false )
		);
		
		if ( isset( $upload['error'] ) ) {
			add_settings_error(
				'vh360_pwa_options',
				'upload_error',
				$upload['error'],
				'error'
			);
			return;
		}
		
		$hash = is_readable( $upload['file'] ) ? md5_file( $upload['file'] ) : false;
		update_option( 'vh360_pwa_master_icon_source', $upload['file'] );
		update_option( 'vh360_pwa_master_icon_uploaded_at', time() );
		update_option( 'vh360_pwa_master_icon_hash', false !== $hash ? $hash : '' );
		update_option( 'vh360_pwa_master_icon_hash_error', false !== $hash ? '' : __( 'Unable to hash uploaded master icon file.', 'vh360-pwa-app' ) );
		update_option( 'vh360_pwa_master_icon_basename', basename( $upload['file'] ) );
		update_option( 'vh360_pwa_master_icon_size_bytes', is_readable( $upload['file'] ) ? filesize( $upload['file'] ) : 0 );
		
		add_settings_error(
			'vh360_pwa_options',
			'upload_success',
			__( 'Master icon uploaded successfully. Click "Generate Icons" to create all sizes.', 'vh360-pwa-app' ),
			'success'
		);
		
		// Redirect to icons tab
		wp_safe_redirect( admin_url( 'admin.php?page=vh360-pwa-app&tab=icons&settings-updated=1' ) );
		exit;
	}
	
	/**
	 * Handle icon generation.
	 */
	private function handle_icon_generation() : void {
		$master_icon = get_option( 'vh360_pwa_master_icon_source' );
		if ( ! $master_icon ) {
			$this->record_icon_generation_failure( __( 'No master icon found. Please upload a master icon first.', 'vh360-pwa-app' ) );
			return;
		}
		if ( ! file_exists( $master_icon ) ) {
			$this->record_icon_generation_failure( __( 'The saved master icon path does not exist. Please upload the master icon again.', 'vh360-pwa-app' ) );
			return;
		}

		$icon_generator = new VH360_PWA_Icon_Generator();
		$requirements = $icon_generator->check_requirements();
		if ( ! $requirements['available'] ) {
			$this->record_icon_generation_failure( __( 'Neither Imagick nor GD library is available. Please install one to generate icons.', 'vh360-pwa-app' ), $master_icon );
			return;
		}

		$new_asset_version = function_exists( 'vh360_pwa_bump_asset_version' ) ? vh360_pwa_bump_asset_version() : time();
		$clear_results = $icon_generator->clear_generated_icons();
		$result = $icon_generator->generate_all_icons( $master_icon );
		$generated_count = $this->count_generated_icons( $result['generated'] ?? array() );
		$root_results = array();
		$startup_images = array();
		$purge_results = array();

		if ( ! empty( $result['generated'] ) ) {
			if ( function_exists( 'vh360_pwa_backfill_legacy_icons_from_generated' ) ) {
				vh360_pwa_backfill_legacy_icons_from_generated();
			}
			if ( function_exists( 'vh360_pwa_clear_ios_startup_images' ) ) {
				vh360_pwa_clear_ios_startup_images();
			}
			if ( function_exists( 'vh360_pwa_generate_ios_startup_images' ) ) {
				$startup_images = vh360_pwa_generate_ios_startup_images();
			}
			if ( class_exists( 'VH360_PWA_Root_Files' ) ) {
				$root_results = VH360_PWA_Root_Files::ensure_root_files();
			}
			$purge_results = $this->purge_common_caches();
			update_option( 'vh360_pwa_icons_generated_at', time() );
		}

		$status = ! empty( $result['success'] ) ? 'success' : ( $generated_count > 0 ? 'partial' : 'failure' );
		$diagnostics = array(
			'status'               => $status,
			'generated_at'         => time(),
			'master_icon_path'    => $master_icon,
			'master_icon_basename'=> basename( $master_icon ),
			'master_icon_hash'    => is_readable( $master_icon ) ? md5_file( $master_icon ) : '',
			'asset_version'       => $new_asset_version,
			'generated_icon_count'=> $generated_count,
			'generated_icons'     => $result['generated'] ?? array(),
			'deleted_old_icon_count' => is_array( $clear_results ) ? absint( $clear_results['deleted'] ?? 0 ) : 0,
			'clear_results'       => $clear_results,
			'root_results'        => $root_results,
			'startup_count'       => is_array( $startup_images ) ? count( $startup_images ) : 0,
			'startup_images'      => $startup_images,
			'cache_purge_results' => $purge_results,
			'errors'              => $result['errors'] ?? ( ! empty( $result['error'] ) ? array( $result['error'] ) : array() ),
		);
		update_option( 'vh360_pwa_last_icon_generation', $diagnostics );

		$readiness_checker = new VH360_PWA_Readiness_Checker();
		$readiness_checker->clear_cache();

		if ( 'success' === $status ) {
			add_settings_error( 'vh360_pwa_options', 'generation_success', __( 'All icons generated successfully with fresh versioned filenames. PWA assets were regenerated.', 'vh360-pwa-app' ), 'success' );
		} elseif ( 'partial' === $status ) {
			add_settings_error( 'vh360_pwa_options', 'generation_partial', __( 'Some icons generated, but one or more required sizes failed. Review diagnostics below.', 'vh360-pwa-app' ), 'warning' );
		} else {
			add_settings_error( 'vh360_pwa_options', 'generation_error', __( 'Icon generation failed. Review diagnostics below.', 'vh360-pwa-app' ), 'error' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=vh360-pwa-app&tab=icons&settings-updated=1' ) );
		exit;
	}

	private function record_icon_generation_failure( string $message, string $master_icon = '' ) : void {
		update_option( 'vh360_pwa_last_icon_generation', array( 'status' => 'failure', 'generated_at' => time(), 'master_icon_path' => $master_icon, 'errors' => array( $message ) ) );
		add_settings_error( 'vh360_pwa_options', 'generation_error', $message, 'error' );
	}

	private function count_generated_icons( array $generated ) : int {
		$count = 0;
		foreach ( array( 'ios', 'android', 'maskable' ) as $group ) {
			$count += ! empty( $generated[ $group ] ) && is_array( $generated[ $group ] ) ? count( $generated[ $group ] ) : 0;
		}
		return $count;
	}

	private function handle_clear_generated_icons() : void {
		$icon_generator = new VH360_PWA_Icon_Generator();
		$clear_results = $icon_generator->clear_generated_icons();
		$cleared_fields = $this->clear_legacy_generated_icon_fields();
		$new_asset_version = function_exists( 'vh360_pwa_bump_asset_version' ) ? vh360_pwa_bump_asset_version() : time();
		$root_results = class_exists( 'VH360_PWA_Root_Files' ) ? VH360_PWA_Root_Files::ensure_root_files() : array();
		update_option( 'vh360_pwa_last_icon_generation', array(
			'status'                   => 'cleared',
			'generated_at'             => time(),
			'asset_version'            => $new_asset_version,
			'deleted_old_icon_count'   => is_array( $clear_results ) ? absint( $clear_results['deleted'] ?? 0 ) : 0,
			'cleared_legacy_fields'    => $cleared_fields,
			'clear_results'            => $clear_results,
			'root_results'             => $root_results,
		) );
		add_settings_error( 'vh360_pwa_options', 'icons_cleared', __( 'Generated icons were cleared. The master icon was not deleted.', 'vh360-pwa-app' ), 'success' );
		wp_safe_redirect( admin_url( 'admin.php?page=vh360-pwa-app&tab=icons&settings-updated=1' ) );
		exit;
	}

	private function clear_legacy_generated_icon_fields() : array {
		$opts = vh360_pwa_get_options();
		$updates = array();
		foreach ( array( 'icon_192', 'icon_512', 'icon_maskable_192', 'icon_maskable_512' ) as $field ) {
			$value = isset( $opts[ $field ] ) ? (string) $opts[ $field ] : '';
			if ( '' !== $value && $this->is_generated_icon_url( $value ) ) {
				$updates[ $field ] = '';
			}
		}
		if ( ! empty( $updates ) ) {
			vh360_pwa_update_options( $updates );
		}
		return array_keys( $updates );
	}

	private function is_generated_icon_url( string $url ) : bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$basename = basename( $path );
		if ( false !== strpos( $path, '/vh360-pwa/icons/' ) ) {
			return true;
		}
		return (bool) preg_match( '/^(vh360-icon-(?:maskable-)?\d+-\d+-[a-f0-9]{8,12}|icon-maskable-\d+|icon-\d+)\.png$/i', $basename );
	}

	public function maybe_flush_rewrite_on_option_update( $old_value, $value, $option ) : void {
		if ( 'vh360_pwa_options' !== $option ) {
			return;
		}
		$old_enabled = is_array( $old_value ) ? ! empty( $old_value['enabled'] ) : false;
		$new_enabled = is_array( $value ) ? ! empty( $value['enabled'] ) : false;
		if ( $old_enabled !== $new_enabled ) {
			VH360_PWA_Endpoints::add_rewrite_rules();
			flush_rewrite_rules();
		}
		update_option( 'vh360_pwa_manifest_generated_at', time() );
		$splash_keys = array( 'pwa_asset_version', 'splash_enabled', 'splash_logo', 'splash_title', 'splash_title_enabled', 'splash_title_font_size', 'splash_title_color', 'splash_title_offset', 'splash_background_color', 'background_color' );
		$splash_changed = false;
		foreach ( $splash_keys as $splash_key ) {
			$old_setting = is_array( $old_value ) && array_key_exists( $splash_key, $old_value ) ? $old_value[ $splash_key ] : null;
			$new_setting = is_array( $value ) && array_key_exists( $splash_key, $value ) ? $value[ $splash_key ] : null;
			if ( $old_setting !== $new_setting ) {
				$splash_changed = true;
				break;
			}
		}
		if ( $splash_changed && function_exists( 'vh360_pwa_clear_ios_startup_images' ) ) { vh360_pwa_clear_ios_startup_images(); }
		if ( function_exists( 'vh360_pwa_generate_ios_startup_images' ) ) { vh360_pwa_generate_ios_startup_images(); }
		if ( class_exists( 'VH360_PWA_Root_Files' ) ) { VH360_PWA_Root_Files::ensure_root_files(); }
		if ( $splash_changed ) { $this->purge_common_caches(); }
	}

	public function render_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts = vh360_pwa_get_options();
		$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'general';
        $tabs = array(
            'general' => __( 'General', 'vh360-pwa-app' ),
            'icons'   => __( 'Icons', 'vh360-pwa-app' ),
            'caching' => __( 'Caching', 'vh360-pwa-app' ),
            'health'  => __( 'Health Check', 'vh360-pwa-app' ),
            'tools'   => __( 'Tools', 'vh360-pwa-app' ),
        );
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'general';
		}

		echo '<div class="wrap vh360-pwa-admin">';
		echo '<h1>VH360 PWA &amp; App</h1>';

		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			$active = ( $k === $tab ) ? ' nav-tab-active' : '';
			$url = esc_url( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=' . $k ) );
			echo '<a class="nav-tab' . $active . '" href="' . $url . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';

		// Icons and Tools tabs don't use the main settings form
		if ( 'icons' === $tab ) {
			$this->render_tab_icons( $opts );
		} elseif ( 'tools' === $tab ) {
			$this->render_tab_tools( $opts );
		} else {
			// Other tabs use the settings form
			echo '<form method="post" action="options.php">';
			settings_fields( 'vh360_pwa_options_group' );
			// No sections/fields API; render manually for full control.
			echo '<input type="hidden" name="vh360_pwa_options[_tab]" value="' . esc_attr( $tab ) . '">';

			switch ( $tab ) {
				case 'caching':
					$this->render_tab_caching( $opts );
					break;
				case 'health':
					$this->render_tab_health( $opts );
					break;
				case 'general':
				default:
					$this->render_tab_general( $opts );
					break;
			}

			submit_button( __( 'Save Settings', 'vh360-pwa-app' ) );
			echo '</form>';
		}
		
		echo '</div>';
	}

	private function render_tab_general( array $opts ) : void {
		$enabled = ! empty( $opts['enabled'] );
		
		$vh360_licensed = function_exists( 'vh360_pwa_is_licensed' ) ? vh360_pwa_is_licensed() : true;
		$vh360_disabled_attr = $vh360_licensed ? '' : ' disabled="disabled"';
		$vh360_locked_class = $vh360_licensed ? '' : ' vh360-pwa-locked';
$manifest_url = esc_url( vh360_pwa_endpoint_url( VH360_PWA_MANIFEST_SLUG ) );
		$sw_url = esc_url( vh360_pwa_endpoint_url( VH360_PWA_SW_SLUG ) );
		$offline_url = esc_url( vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG ) );

		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Enable PWA', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label class="' . esc_attr( 'vh360-pwa-toggle' . $vh360_locked_class ) . '"><input type="checkbox" name="vh360_pwa_options[enabled]" value="1" ' . checked( $enabled, true, false ) . $vh360_disabled_attr . '> ' . esc_html__( 'Enable PWA features', 'vh360-pwa-app' ) . '</label>';
			if ( ! $vh360_licensed ) { echo '<p class=\"description\" style=\"margin-top:6px;color:#b32d2e;\">' . esc_html__( 'PWA is disabled until your VideoHub360 license is activated.', 'vh360-pwa-app' ) . '</p>'; }
	echo '<p class="description">Endpoints: <code>/' . esc_html( VH360_PWA_MANIFEST_SLUG ) . '</code>, <code>/' . esc_html( VH360_PWA_SW_SLUG ) . '</code>, <code>/' . esc_html( VH360_PWA_OFFLINE_SLUG ) . '</code></p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'App Name', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[app_name]" value="' . esc_attr( (string) $opts['app_name'] ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Short Name', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[short_name]" value="' . esc_attr( (string) $opts['short_name'] ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Description', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[description]" value="' . esc_attr( (string) $opts['description'] ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Theme Color', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="vh360-color" name="vh360_pwa_options[theme_color]" value="' . esc_attr( (string) $opts['theme_color'] ) . '" data-default-color="#2563eb">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Background Color', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="vh360-color" name="vh360_pwa_options[background_color]" value="' . esc_attr( (string) $opts['background_color'] ) . '" data-default-color="#0f172a">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'App Experience', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[enable_pull_to_refresh]" value="1" ' . checked( ! empty( $opts['enable_pull_to_refresh'] ), true, false ) . '> ' . esc_html__( 'Enable pull-to-refresh in standalone app mode', 'vh360-pwa-app' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Launch Experience', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[splash_enabled]" value="1" ' . checked( ! empty( $opts['splash_enabled'] ), true, false ) . '> ' . esc_html__( 'Enable branded splash/launch screens', 'vh360-pwa-app' ) . '</label><br>';
		echo '<label>' . esc_html__( 'Splash background', 'vh360-pwa-app' ) . '<br><input type="text" class="vh360-color" name="vh360_pwa_options[splash_background_color]" value="' . esc_attr( (string) $opts['splash_background_color'] ) . '" data-default-color="#0f172a"></label><br>';
		echo '<label>' . esc_html__( 'Splash title', 'vh360-pwa-app' ) . '<br><input type="text" class="regular-text" name="vh360_pwa_options[splash_title]" value="' . esc_attr( (string) $opts['splash_title'] ) . '"></label><br>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[splash_title_enabled]" value="1" ' . checked( ! empty( $opts['splash_title_enabled'] ), true, false ) . '> ' . esc_html__( 'Show splash title in generated launch images', 'vh360-pwa-app' ) . '</label><br>';
		echo '<label>' . esc_html__( 'Splash title font size', 'vh360-pwa-app' ) . '<br><input type="number" min="18" max="96" name="vh360_pwa_options[splash_title_font_size]" value="' . esc_attr( (string) $opts['splash_title_font_size'] ) . '" style="width:90px"> px</label><br>';
		echo '<label>' . esc_html__( 'Splash title color', 'vh360-pwa-app' ) . '<br><input type="text" class="vh360-color" name="vh360_pwa_options[splash_title_color]" value="' . esc_attr( (string) $opts['splash_title_color'] ) . '" data-default-color="#ffffff"></label><br>';
		echo '<label>' . esc_html__( 'Splash title spacing below logo', 'vh360-pwa-app' ) . '<br><input type="number" min="20" max="200" name="vh360_pwa_options[splash_title_offset]" value="' . esc_attr( (string) $opts['splash_title_offset'] ) . '" style="width:90px"> px</label>';
		echo '<p class="description" style="max-width:760px;">' . esc_html__( 'PWA launch screens are cached aggressively by iOS and Android. Regenerate PWA Assets refreshes server-side files and URLs for new installs, Android/Chrome, and normal PWA assets, but it cannot force existing iPhone/iPad Home Screen apps to replace installed launch metadata. Existing iOS users may need to remove the app from the Home Screen and add it again from Safari after splash, icon, or app-name changes.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';
		$this->render_media_row( 'splash_logo', __( 'Splash Logo', 'vh360-pwa-app' ), (string) ( $opts['splash_logo'] ?? '' ) );

		echo '<tr><th scope="row">' . esc_html__( 'Display', 'vh360-pwa-app' ) . '</th><td>';
		$display_opts = array('standalone'=>'standalone','fullscreen'=>'fullscreen','minimal-ui'=>'minimal-ui','browser'=>'browser');
		echo '<select name="vh360_pwa_options[display]">';
		foreach ( $display_opts as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $opts['display'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Orientation', 'vh360-pwa-app' ) . '</th><td>';
		$ori_opts = array(
			'any'             => 'any',
			'portrait-primary' => 'portrait-primary (recommended)',
			'portrait'         => 'portrait',
			'landscape'        => 'landscape',
		);
		echo '<select name="vh360_pwa_options[orientation]">';
		foreach ( $ori_opts as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $opts['orientation'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Start URL', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[start_url]" value="' . esc_attr( (string) $opts['start_url'] ) . '">';
		echo '<p class="description">Same-origin only. You can enter a full URL or a relative path like <code>/</code>.</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Scope', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[scope]" value="' . esc_attr( (string) $opts['scope'] ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Install Prompt', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[show_install_prompt]" value="1" ' . checked( ! empty( $opts['show_install_prompt'] ), true, false ) . '> ' . esc_html__( 'Enable install prompt helper', 'vh360-pwa-app' ) . '</label>';
		echo '<p class="description">Adds a lightweight helper so you can show an install button via shortcode. Shortcode: <code>[vh360_pwa_install_button]</code>.</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Automatic Install Banner', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[show_install_banner]" value="1" ' . checked( ! empty( $opts['show_install_banner'] ), true, false ) . '> ' . esc_html__( 'Show a top bar banner for install (recommended)', 'vh360-pwa-app' ) . '</label>';
		echo '<p class="description">Displays a top bar banner on the frontend (when not installed). On iOS it shows Add to Home Screen instructions.</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Banner Text', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[install_banner_text]" value="' . esc_attr( (string) $opts['install_banner_text'] ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Banner Dismiss (days)', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="number" min="1" max="365" name="vh360_pwa_options[install_banner_dismiss_days]" value="' . esc_attr( (string) $opts['install_banner_dismiss_days'] ) . '" style="width:90px">';
		echo '<p class="description">If a user dismisses the banner, it will reappear after this many days.</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'iOS Onboarding', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[show_ios_onboarding]" value="1" ' . checked( ! empty( $opts['show_ios_onboarding'] ), true, false ) . '> ' . esc_html__( 'Show Add to Home Screen instructions on iOS', 'vh360-pwa-app' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Install Button Text', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[install_prompt_text]" value="' . esc_attr( (string) $opts['install_prompt_text'] ) . '">';
		echo '</td></tr>';
		
		echo '<tr><th scope="row">' . esc_html__( 'Push Notification Sender Roles', 'vh360-pwa-app' ) . '</th><td>';
		$all_roles = wp_roles()->roles;
		$opts_defaults = vh360_pwa_get_options();
		$selected_roles = isset( $opts['push_sender_roles'] ) && is_array( $opts['push_sender_roles'] ) ? $opts['push_sender_roles'] : $opts_defaults['push_sender_roles'];
		
		echo '<fieldset>';
		echo '<legend class="screen-reader-text">' . esc_html__( 'Select roles that can send push notifications', 'vh360-pwa-app' ) . '</legend>';
		
		// Administrator is always included - show as text with explanation
		if ( isset( $all_roles['administrator'] ) ) {
			$admin_name = isset( $all_roles['administrator']['name'] ) ? translate_user_role( $all_roles['administrator']['name'] ) : __( 'Administrator', 'vh360-pwa-app' );
			echo '<div class="vh360-pwa-admin-note">';
			echo '<strong>' . esc_html( $admin_name ) . '</strong> <span class="description">' . esc_html__( '(always included)', 'vh360-pwa-app' ) . '</span>';
			echo '<input type="hidden" name="vh360_pwa_options[push_sender_roles][]" value="administrator">';
			echo '</div>';
		}
		
		foreach ( $all_roles as $role_key => $role_data ) {
			// Skip administrator - already handled above
			if ( $role_key === 'administrator' ) {
				continue;
			}
			
			$role_name = isset( $role_data['name'] ) ? translate_user_role( $role_data['name'] ) : $role_key;
			$checked = in_array( $role_key, $selected_roles, true );
			$checked_attr = $checked ? ' checked="checked"' : '';
			
			echo '<label class="vh360-pwa-role-checkbox">';
			echo '<input type="checkbox" name="vh360_pwa_options[push_sender_roles][]" value="' . esc_attr( $role_key ) . '"' . $checked_attr . '> ';
			echo esc_html( $role_name );
			echo '</label>';
		}
		echo '</fieldset>';
		echo '<p class="description">' . esc_html__( 'Users with these roles can access the Push Notifications tab in the dashboard. Administrators are always included.', 'vh360-pwa-app' ) . '</p>';
		echo '<p class="description"><strong>' . esc_html__( 'Note:', 'vh360-pwa-app' ) . '</strong> ' . esc_html__( 'Saving these settings will update the vh360_send_push capability for all roles. Custom capabilities granted outside this interface will be preserved for roles not listed here.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';

		echo '<div class="vh360-pwa-endpoints">';
		echo '<h2>' . esc_html__( 'Quick Links', 'vh360-pwa-app' ) . '</h2>';
		echo '<ul>';
		echo '<li><a href="' . $manifest_url . '" target="_blank" rel="noopener">manifest.json</a></li>';
		echo '<li><a href="' . $sw_url . '" target="_blank" rel="noopener">service worker</a></li>';
		echo '<li><a href="' . $offline_url . '" target="_blank" rel="noopener">offline page</a></li>';
		echo '</ul>';
		echo '</div>';
	}

	private function render_tab_icons( array $opts ) : void {
		// Display any settings errors/messages
		settings_errors( 'vh360_pwa_options' );
		
		// Use the icon generator as the primary icon management interface
		$icon_generator = new VH360_PWA_Icon_Generator();
		$requirements = $icon_generator->check_requirements();
		$generated_icons = method_exists( $icon_generator, 'get_generated_icon_metadata' ) ? $icon_generator->get_generated_icon_metadata() : $icon_generator->get_generated_icons();
		$master_icon = get_option( 'vh360_pwa_master_icon_source' );
		$required_sizes = $icon_generator->get_required_sizes();
		
		// Include the icon generator template
		$template = VH360_PWA_APP_DIR . 'templates/admin/appstore-icons.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	private function render_media_row( string $key, string $label, string $value ) : void {
		$val = esc_attr( $value );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<div class="vh360-media-row">';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[' . esc_attr( $key ) . ']" value="' . $val . '" data-vh360-media-url>'; 
		echo '<button type="button" class="button vh360-media-upload" data-target="' . esc_attr( $key ) . '">' . esc_html__( 'Select', 'vh360-pwa-app' ) . '</button> ';
		echo '<button type="button" class="button vh360-media-clear" data-target="' . esc_attr( $key ) . '">' . esc_html__( 'Clear', 'vh360-pwa-app' ) . '</button>';
		if ( $value ) {
			echo '<div class="vh360-media-preview"><img src="' . esc_url( $value ) . '" alt="" style="max-width:96px;height:auto"></div>';
		}
		echo '</div>';
		echo '</td></tr>';
	}

	private function render_tab_caching( array $opts ) : void {
		echo '<p class="description">Caching is the #1 cause of WordPress PWA bugs. Start with <strong>Safe</strong> and only increase if you really need it.</p>';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Cache Strategy', 'vh360-pwa-app' ) . '</th><td>';
		$strats = array(
			'safe' => __( 'Safe (cache static assets only; network-first for pages)', 'vh360-pwa-app' ),
			'balanced' => __( 'Balanced (cache static assets; opportunistic caching for same-origin pages)', 'vh360-pwa-app' ),
			'aggressive' => __( 'Aggressive (not recommended for logged-in sites)', 'vh360-pwa-app' ),
		);
		echo '<select name="vh360_pwa_options[cache_strategy]">';
		foreach ( $strats as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( (string) $opts['cache_strategy'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Cache Version', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_pwa_options[cache_version]" value="' . esc_attr( (string) $opts['cache_version'] ) . '">';
		echo '<p class="description">Bump this when you want to force clients to drop old caches (e.g., change to <code>v2</code>).</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Precache Offline Page', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[precache_offline]" value="1" ' . checked( ! empty( $opts['precache_offline'] ), true, false ) . '> ' . esc_html__( 'Yes', 'vh360-pwa-app' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Precache Home Page', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_pwa_options[precache_home]" value="1" ' . checked( ! empty( $opts['precache_home'] ), true, false ) . '> ' . esc_html__( 'Yes (public home only)', 'vh360-pwa-app' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Additional Precache URLs', 'vh360-pwa-app' ) . '</th><td>';
		echo '<textarea name="vh360_pwa_options[precache_urls]" rows="6" class="large-text code" placeholder="/\n/offline\n/about">' . esc_textarea( (string) $opts['precache_urls'] ) . '</textarea>';
		echo '<p class="description">One URL per line. Same-origin only. Use relative paths (recommended).</p>';
		echo '</td></tr>';

		echo '</table>';
	}

	private function render_tab_health( array $opts ) : void {
		$manifest = esc_url( vh360_pwa_endpoint_url( VH360_PWA_MANIFEST_SLUG ) );
		$sw = esc_url( vh360_pwa_endpoint_url( VH360_PWA_SW_SLUG ) );
		$offline = esc_url( vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG ) );

		echo '<p>This runs a quick server-side check for your endpoints and common requirements.</p>';
		echo '<button type="button" class="button button-primary" id="vh360-pwa-run-health">Run Health Check</button>';
		echo '<div id="vh360-pwa-health-results" class="vh360-health-results" style="margin-top:12px;"></div>';
		echo '<hr>';
		echo '<h3>Endpoints</h3>';
		echo '<ul>';
		echo '<li><code>' . esc_html( $manifest ) . '</code></li>';
		echo '<li><code>' . esc_html( $sw ) . '</code></li>';
		echo '<li><code>' . esc_html( $offline ) . '</code></li>';
		echo '</ul>';
	}

	public function ajax_health_check() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
		}
		check_ajax_referer( 'vh360_pwa_admin', 'nonce' );

		$results = array();

		$results['theme_ok'] = vh360_pwa_is_allowed_theme_active();
		$results['https'] = is_ssl();

		$urls = array(
			'manifest' => vh360_pwa_endpoint_url( VH360_PWA_MANIFEST_SLUG ),
			'sw'       => vh360_pwa_endpoint_url( VH360_PWA_SW_SLUG ),
			'offline'  => vh360_pwa_endpoint_url( VH360_PWA_OFFLINE_SLUG ),
		);

		foreach ( $urls as $k => $url ) {
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
			if ( is_wp_error( $response ) ) {
				$results['endpoints'][ $k ] = array( 'ok' => false, 'error' => $response->get_error_message() );
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$ctype = (string) wp_remote_retrieve_header( $response, 'content-type' );
			$results['endpoints'][ $k ] = array(
				'ok' => ( 200 === $code ),
				'code' => $code,
				'content_type' => $ctype,
			);
		}

		$results['enabled'] = vh360_pwa_is_enabled();
		$results['options'] = array(
			'cache_strategy' => vh360_pwa_get_options()['cache_strategy'],
			'cache_version'  => vh360_pwa_get_options()['cache_version'],
		);

		wp_send_json_success( $results );
	}
	
	/**
	 * Render the App Store admin page.
	 * Delegates to the AppStore_Admin class.
	 */
	public function render_appstore_page() : void {
		$appstore = VH360_PWA_App::instance()->appstore_admin;
		if ( $appstore ) {
			$appstore->render_page();
		}
	}


}