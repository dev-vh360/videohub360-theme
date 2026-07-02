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
define( 'VH360_STUDIO_DB_VERSION', '1.3.0' );

/**
 * Load translations.
 */
function vh360_studio_load_textdomain() {
    load_plugin_textdomain( 'videohub360-studio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'vh360_studio_load_textdomain' );

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
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-replay-publisher.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-assets.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-live-engine-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-recording-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/interface-vh360-studio-replay-storage-provider.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'providers/class-vh360-studio-placeholder-providers.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-provider-registry.php';
    require_once VH360_STUDIO_INCLUDES_DIR . 'class-vh360-studio-rest-controller.php';
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
}
register_deactivation_hook( __FILE__, 'vh360_studio_deactivate' );
