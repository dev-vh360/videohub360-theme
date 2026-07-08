<?php
/**
 * Plugin Name: VH360 Studio
 * Plugin URI: https://videohub360.com
 * Description: Optional VideoHub360 Studio foundation for live-session recording jobs, provider orchestration, and dashboard workflow integration.
 * Version: 0.1.0
 * Author: VideoHub360
 * Author URI: https://videohub360.com
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: videohub360-studio
 * Domain Path: /languages
 * Network: false
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VH360_STUDIO_VERSION', '0.1.0' );
define( 'VH360_STUDIO_PLUGIN_FILE', __FILE__ );
define( 'VH360_STUDIO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VH360_STUDIO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VH360_STUDIO_INCLUDES_DIR', VH360_STUDIO_PLUGIN_DIR . 'includes/' );
define( 'VH360_STUDIO_TEMPLATES_DIR', VH360_STUDIO_PLUGIN_DIR . 'templates/' );
define( 'VH360_STUDIO_DB_VERSION', '1.5.0' );

/**
 * Load translations.
 */
function vh360_studio_load_textdomain() {
    load_plugin_textdomain( 'videohub360-studio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'vh360_studio_load_textdomain' );


/**
 * Determine whether the current frontend request is for focused Studio Window mode.
 *
 * @return bool
 */
function vh360_studio_is_window_mode_request() {
    if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
        return false;
    }

    $window_mode = isset( $_GET['vh360_studio_window'] ) ? sanitize_text_field( wp_unslash( $_GET['vh360_studio_window'] ) ) : '';

    return '1' === $window_mode;
}

/**
 * Add the focused Studio Window body class before JavaScript runs.
 *
 * @param array $classes Existing body classes.
 * @return array
 */
function vh360_studio_window_body_class( $classes ) {
    if ( vh360_studio_is_window_mode_request() ) {
        $classes[] = 'vh360-studio-window-mode';
    }

    return $classes;
}
add_filter( 'body_class', 'vh360_studio_window_body_class' );

/**
 * Hide the WordPress admin bar in focused Studio Window mode.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function vh360_studio_window_show_admin_bar( $show ) {
    if ( vh360_studio_is_window_mode_request() ) {
        return false;
    }

    return $show;
}
add_filter( 'show_admin_bar', 'vh360_studio_window_show_admin_bar', 20 );

/**
 * Get the frontend Studio display name.
 *
 * @return string
 */
function vh360_studio_get_display_name() {
    $display_name = trim( (string) get_option( 'vh360_studio_display_name', '' ) );

    if ( '' === $display_name ) {
        $site_title = trim( (string) get_bloginfo( 'name' ) );
        $display_name = '' !== $site_title
            ? sprintf( __( '%s Studio', 'videohub360-studio' ), $site_title )
            : __( 'Studio', 'videohub360-studio' );
    }

    return apply_filters( 'vh360_studio_display_name', $display_name );
}

/**
 * Determine whether VideoHub360 Core is available.
 *
 * @return bool
 */
function vh360_studio_has_core_dependency() {
    return defined( 'VIDEOHUB360_VERSION' ) || post_type_exists( 'videohub360' );
}

/**
 * Show dependency notice without fatal errors.
 */
function vh360_studio_dependency_notice() {
    if ( current_user_can( 'activate_plugins' ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'VH360 Studio requires VideoHub360 Core. Studio is installed but will remain inactive until Core is active.', 'videohub360-studio' ) . '</p></div>';
    }
}

/**
 * Load plugin classes.
 */
function vh360_studio_load_files() {
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-database.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-permissions.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-quality-presets.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-recording-jobs.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-recording-chunks.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-recording-validator.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-recording-cleanup.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-replay-posts.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-replay-publisher.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-replay-status-reconciler.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-publitio-client.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-bunny-stream-client.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-assets.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-media-admin.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-live-engine-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-recording-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-replay-storage-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-placeholder-providers.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-videopress-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-publitio-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-bunny-stream-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-local-media-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-provider-registry.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-rest-controller.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-admin.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-plugin.php';
}

/**
 * Initialize Studio when Core is present.
 */
function vh360_studio_boot() {
    if ( ! vh360_studio_has_core_dependency() ) {
        add_action( 'admin_notices', 'vh360_studio_dependency_notice' );
        return;
    }

    vh360_studio_load_files();
    VH360_Studio_Plugin::instance();
}
add_action( 'init', 'vh360_studio_boot', 20 );

/**
 * Activation callback.
 */
function vh360_studio_activate() {
    vh360_studio_load_files();
    VH360_Studio_Database::install();
}
register_activation_hook( __FILE__, 'vh360_studio_activate' );

/**
 * Deactivation callback.
 */
function vh360_studio_deactivate() {
    vh360_studio_load_files();
    VH360_Studio_Recording_Cleanup::unschedule();
    if ( class_exists( 'VH360_Studio_Replay_Status_Reconciler' ) ) {
        VH360_Studio_Replay_Status_Reconciler::unschedule();
    }
}
register_deactivation_hook( __FILE__, 'vh360_studio_deactivate' );
