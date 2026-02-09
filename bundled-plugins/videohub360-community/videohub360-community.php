<?php
/**
 * Plugin Name: VideoHub360 Community
 * Plugin URI: https://videohub360.com
 * Description: Community engagement features for VideoHub360 - handles comment likes, share tracking, and activity data
 * Version: 1.0.0
 * Author: vh360
 * Author URI: https://videohub360.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vh360-community
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin constants
define('VH360_COMMUNITY_VERSION', '1.0.0');
define('VH360_COMMUNITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VH360_COMMUNITY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VH360_COMMUNITY_DB_VERSION', '1.0.0');

// Require main class
require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/class-vh360-community.php';

// Initialize plugin
function vh360_community_init() {
    VH360_Community::get_instance();
}
add_action('plugins_loaded', 'vh360_community_init');

// Activation hook
register_activation_hook(__FILE__, array('VH360_Community', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('VH360_Community', 'deactivate'));
