<?php
/*
Plugin Name: VideoHub360 Memberships
Plugin URI: https://videohub360.com
Description: Native membership system for VideoHub360 with WooCommerce integration and Stripe recurring subscription support.
Version: 1.0.0
Author: VideoHub360
Author URI: https://videohub360.com
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: videohub360-memberships
Domain Path: /languages
Network: false
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('VH360_MEMBERSHIPS_FILE', __FILE__);
define('VH360_MEMBERSHIPS_DIR', plugin_dir_path(__FILE__));
define('VH360_MEMBERSHIPS_URL', plugin_dir_url(__FILE__));
define('VH360_MEMBERSHIPS_VERSION', '1.0.0');


/**
 * Get a cache-busting version for a plugin-owned asset.
 *
 * @param string $relative_path Asset path relative to the plugin root.
 * @return string
 */
if (!function_exists('vh360_memberships_asset_version')) {
    function vh360_memberships_asset_version($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        $file_path = VH360_MEMBERSHIPS_DIR . $relative_path;

        if (file_exists($file_path)) {
            return VH360_MEMBERSHIPS_VERSION . '-' . filemtime($file_path);
        }

        return VH360_MEMBERSHIPS_VERSION;
    }
}

// Load required classes for activation/deactivation hooks
require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-database.php';
require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-cron.php';
require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-database.php';

/**
 * Main plugin class
 */
class VH360_Memberships {
    
    /**
     * Singleton instance
     *
     * @var VH360_Memberships
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Memberships
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('after_switch_theme', array($this, 'create_tables'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load core classes
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-database.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-plans.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/admin/class-vh360-membership-plans-admin.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/admin/class-vh360-membership-members-admin.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-api.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-woocommerce.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-cron.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-frontend.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-content-gates.php';
        
        // Load Giving module
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-database.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-funds.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-transactions.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-recurring.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-checkout.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/class-vh360-giving-webhook.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/giving/giving-helpers.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/admin/class-vh360-giving-admin.php';

        // Load Stripe integration classes
        require_once VH360_MEMBERSHIPS_DIR . 'includes/stripe/class-vh360-stripe-bootstrap.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/stripe/class-vh360-stripe-checkout.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/stripe/class-vh360-stripe-webhook.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/stripe/class-vh360-stripe-sync.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/stripe/class-vh360-stripe-portal.php';
        
        // Load subscription management
        require_once VH360_MEMBERSHIPS_DIR . 'includes/class-vh360-membership-subscription-management.php';
        
        // Load helper functions
        require_once VH360_MEMBERSHIPS_DIR . 'includes/membership-helpers.php';
        require_once VH360_MEMBERSHIPS_DIR . 'includes/course-entitlement-helpers.php';
        
        // Load platform integrations
        require_once VH360_MEMBERSHIPS_DIR . 'includes/platform-integrations.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Membership plans are owned by this plugin and must be manageable even before checkout integrations are ready.
        VH360_Membership_Plans::get_instance();
        if (is_admin() && class_exists('VH360_Membership_Plans_Admin')) {
            VH360_Membership_Plans_Admin::get_instance();
        }
        if (is_admin() && class_exists('VH360_Membership_Members_Admin')) {
            VH360_Membership_Members_Admin::get_instance();
        }
        if (is_admin() && class_exists('VH360_Giving_Admin')) {
            VH360_Giving_Admin::get_instance();
        }
        if (class_exists('VH360_Giving_Database')) {
            VH360_Giving_Database::get_instance();
        }

        // Stripe credentials are shared by recurring memberships and one-time Giving payments.
        VH360_Stripe_Bootstrap::get_instance();
        VH360_Stripe_Webhook::get_instance();
        if (class_exists('VH360_Giving_Checkout')) {
            VH360_Giving_Checkout::get_instance();
        }

        // Check for WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize core components
        VH360_Membership_Database::get_instance();
        VH360_Membership_API::get_instance();
        VH360_Membership_WooCommerce::get_instance();
        VH360_Membership_Cron::get_instance();
        VH360_Membership_Frontend::get_instance();
        VH360_Membership_Content_Gates::get_instance();
        
        // Only initialize recurring membership checkout/portal if Stripe recurring billing is configured
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        if ($stripe->is_configured()) {
            VH360_Stripe_Checkout::get_instance();
            VH360_Stripe_Portal::get_instance();
            VH360_Stripe_Sync::get_instance();
        }
        
        // Initialize subscription management frontend
        VH360_Membership_Subscription_Management::get_instance();
        
        // Load textdomain
        load_plugin_textdomain('videohub360-memberships', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        do_action('vh360_memberships_init');
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        VH360_Membership_Database::create_tables();
        if (class_exists('VH360_Giving_Database')) {
            VH360_Giving_Database::create_tables();
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('WooCommerce is required for WooCommerce-based memberships, course purchases, and checkout features. Install and activate WooCommerce if you plan to use those features.', 'videohub360-memberships');
        echo '</p></div>';
    }
}

/**
 * Plugin activation hook
 */
function vh360_memberships_activate() {
    VH360_Membership_Database::create_tables();
    if (class_exists('VH360_Giving_Database')) {
        VH360_Giving_Database::create_tables();
    }
    VH360_Membership_Cron::schedule_events();
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function vh360_memberships_deactivate() {
    VH360_Membership_Cron::unschedule_events();
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'vh360_memberships_activate');
register_deactivation_hook(__FILE__, 'vh360_memberships_deactivate');

// Initialize the plugin
add_action('plugins_loaded', array('VH360_Memberships', 'get_instance'));
