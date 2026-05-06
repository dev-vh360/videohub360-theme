<?php
/*
Plugin Name: VideoHub360 Affiliates
Plugin URI: https://videohub360.com
Description: First-party affiliate/referral tracking for WooCommerce and VideoHub360-powered sites.
Version: 1.0.0
Author: VideoHub360
Author URI: https://videohub360.com
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: videohub360-affiliates
Domain Path: /languages
Network: false
*/

if (!defined('ABSPATH')) exit;

// Plugin constants
define('VH360_AFFILIATES_FILE',    __FILE__);
define('VH360_AFFILIATES_DIR',     plugin_dir_path(__FILE__));
define('VH360_AFFILIATES_URL',     plugin_dir_url(__FILE__));
define('VH360_AFFILIATES_VERSION', '1.0.0');

// Load files needed before plugins_loaded (activation hooks)
require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-database.php';

/**
 * Main plugin class.
 */
class VH360_Affiliates_Plugin {

    /** @var VH360_Affiliates_Plugin|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // database is already required at file-load for activation hooks; require_once is safe
        require_once VH360_AFFILIATES_DIR . 'includes/affiliate-helpers.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-tracking.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-woocommerce.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-memberships.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-product-meta.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-admin.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-frontend.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-payouts.php';
        require_once VH360_AFFILIATES_DIR . 'includes/class-vh360-affiliates-privacy.php';
    }

    private function init_hooks() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Admin screens are always bootstrapped so admins can access the Settings page
        // to enable the program even if it is currently disabled.
        VH360_Affiliates_Admin::get_instance();

        $settings = get_option('vh360_affiliates_settings', array());
        if (empty($settings['enabled'])) {
            return;
        }

        VH360_Affiliates_Database::get_instance();
        VH360_Affiliates_Tracking::get_instance();
        VH360_Affiliates_WooCommerce::get_instance();
        VH360_Affiliates_Memberships::get_instance();
        VH360_Affiliates_Product_Meta::get_instance();
        VH360_Affiliates_Frontend::get_instance();
        VH360_Affiliates_Payouts::get_instance();
        VH360_Affiliates_Privacy::get_instance();

        load_plugin_textdomain('videohub360-affiliates', false, dirname(plugin_basename(__FILE__)) . '/languages');

        do_action('vh360_affiliates_init');
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('VideoHub360 Affiliates requires WooCommerce to be installed and activated.', 'videohub360-affiliates');
        echo '</p></div>';
    }
}

/**
 * Activation hook.
 */
function vh360_affiliates_activate() {
    VH360_Affiliates_Database::create_tables();

    // Assign custom capability to admins
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('vh360_manage_affiliates');
    }

    flush_rewrite_rules();
}

/**
 * Deactivation hook.
 */
function vh360_affiliates_deactivate() {
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'vh360_affiliates_activate');
register_deactivation_hook(__FILE__, 'vh360_affiliates_deactivate');

// Boot the plugin after all plugins are loaded
add_action('plugins_loaded', array('VH360_Affiliates_Plugin', 'get_instance'));
