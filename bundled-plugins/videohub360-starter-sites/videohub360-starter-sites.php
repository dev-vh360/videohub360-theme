<?php
/*
Plugin Name: VideoHub360 Starter Sites
Plugin URI: https://videohub360.com
Description: One-click demo import system for VideoHub360 theme with manifest-driven package importing
Version: 1.0.0
Author: VideoHub360
Author URI: https://videohub360.com
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: videohub360-starter-sites
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VH360_STARTER_SITES_VERSION', '1.0.0');
define('VH360_STARTER_SITES_FILE', __FILE__);
define('VH360_STARTER_SITES_DIR', plugin_dir_path(__FILE__));
define('VH360_STARTER_SITES_URL', plugin_dir_url(__FILE__));
define('VH360_STARTER_SITES_INCLUDES', VH360_STARTER_SITES_DIR . 'includes/');
define('VH360_STARTER_SITES_ADMIN', VH360_STARTER_SITES_DIR . 'admin/');


/**
 * Get a cache-busting version for a plugin-owned asset.
 *
 * @param string $relative_path Asset path relative to the plugin root.
 * @return string
 */
if (!function_exists('vh360_starter_sites_asset_is_debug')) {
    function vh360_starter_sites_asset_is_debug() {
        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
    }
}

if (!function_exists('vh360_starter_sites_asset_path')) {
    function vh360_starter_sites_asset_path($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        return trailingslashit(VH360_STARTER_SITES_DIR) . $relative_path;
    }
}

if (!function_exists('vh360_starter_sites_asset_uri')) {
    function vh360_starter_sites_asset_uri($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        return trailingslashit(VH360_STARTER_SITES_URL) . $relative_path;
    }
}

if (!function_exists('vh360_starter_sites_asset_resolve')) {
    function vh360_starter_sites_asset_resolve($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        $selected = $relative_path;

        if (!vh360_starter_sites_asset_is_debug() && preg_match('/\.(css|js)$/', $relative_path)) {
            $min_relative = preg_replace('/\.(css|js)$/', '.min.$1', $relative_path);
            if ($min_relative && file_exists(vh360_starter_sites_asset_path($min_relative))) {
                $selected = $min_relative;
            }
        }

        $path = vh360_starter_sites_asset_path($selected);
        $version = file_exists($path) ? VH360_STARTER_SITES_VERSION . '-' . filemtime($path) : VH360_STARTER_SITES_VERSION;

        return array(
            'relative_path' => $selected,
            'path'          => $path,
            'url'           => vh360_starter_sites_asset_uri($selected),
            'version'       => $version,
        );
    }
}

if (!function_exists('vh360_starter_sites_asset_url')) {
    function vh360_starter_sites_asset_url($relative_path) {
        $asset = vh360_starter_sites_asset_resolve($relative_path);
        return $asset['url'];
    }
}

if (!function_exists('vh360_starter_sites_asset_version')) {
    function vh360_starter_sites_asset_version($relative_path) {
        $asset = vh360_starter_sites_asset_resolve($relative_path);
        return $asset['version'];
    }
}

/**
 * Check if VideoHub360 theme is active
 */
function vh360_starter_sites_check_theme() {
    $theme = wp_get_theme();
    $parent_theme = $theme->parent();
    
    // Get the actual theme object (parent if child theme)
    $active_theme = $parent_theme ? $parent_theme : $theme;
    
    // Check theme name (more reliable than slug)
    $theme_name = $active_theme->get('Name');
    $theme_template = $active_theme->get_template();
    
    // Accept if theme name contains "VideoHub360" or template contains "videohub360"
    if (
        stripos($theme_name, 'VideoHub360') !== false ||
        stripos($theme_template, 'videohub360') !== false ||
        function_exists('vh360_get_theme_version') // VH360-specific function exists
    ) {
        return true;
    }
    
    add_action('admin_notices', 'vh360_starter_sites_theme_notice');
    return false;
}

/**
 * Display admin notice if theme is not active
 */
function vh360_starter_sites_theme_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('VideoHub360 Starter Sites plugin requires the VideoHub360 theme to be active.', 'videohub360-starter-sites'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function vh360_starter_sites_init() {
    // Check if theme is active
    if (!vh360_starter_sites_check_theme()) {
        return;
    }
    
    // Load text domain
    load_plugin_textdomain('videohub360-starter-sites', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Load dependencies
    require_once VH360_STARTER_SITES_INCLUDES . 'helpers.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-logger.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-registry.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-downloader.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-importer.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-post-import.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-demo-ajax.php';
    require_once VH360_STARTER_SITES_INCLUDES . 'class-vh360-starter-sites.php';
    
    // Load admin UI
    if (is_admin()) {
        require_once VH360_STARTER_SITES_ADMIN . 'class-vh360-starter-sites-admin.php';
    }
    
    // Initialize main plugin class
    VH360_Starter_Sites::get_instance();
}
add_action('plugins_loaded', 'vh360_starter_sites_init');

/**
 * Activation hook
 */
function vh360_starter_sites_activate() {
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('VideoHub360 Starter Sites requires PHP 7.4 or higher.', 'videohub360-starter-sites'),
            esc_html__('Plugin Activation Error', 'videohub360-starter-sites'),
            array('back_link' => true)
        );
    }
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('VideoHub360 Starter Sites requires WordPress 5.0 or higher.', 'videohub360-starter-sites'),
            esc_html__('Plugin Activation Error', 'videohub360-starter-sites'),
            array('back_link' => true)
        );
    }
    
    // Create temp directory for imports if it doesn't exist
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/vh360-starter-sites-temp';
    
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
        
        // Add .htaccess to prevent direct access
        $htaccess = $temp_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'deny from all');
        }
        
        // Add index.php
        $index = $temp_dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
    }
}
register_activation_hook(__FILE__, 'vh360_starter_sites_activate');

/**
 * Deactivation hook
 */
function vh360_starter_sites_deactivate() {
    // Clean up temp directory
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/vh360-starter-sites-temp';
    
    if (file_exists($temp_dir)) {
        // Only remove temp files, not the directory itself
        $files = glob($temp_dir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
register_deactivation_hook(__FILE__, 'vh360_starter_sites_deactivate');
