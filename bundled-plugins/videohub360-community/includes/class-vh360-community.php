<?php
/**
 * Main plugin class for VideoHub360 Community
 *
 * Handles plugin initialization, includes, and hooks.
 *
 * @package VH360_Community
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VH360_Community {
    
    /**
     * Plugin instance
     *
     * @var VH360_Community
     */
    private static $instance = null;
    
    /**
     * Get plugin instance (Singleton pattern)
     *
     * @return VH360_Community
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
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Include all class files
        require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/database.php';
        require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/class-vh360-comment-likes.php';
        require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/class-vh360-post-shares.php';
        require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/ajax-handlers.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register AJAX actions for logged-in users
        add_action('wp_ajax_vh360_toggle_comment_like', 'vh360_handle_comment_like_ajax');
        add_action('wp_ajax_vh360_increment_share', 'vh360_handle_share_increment_ajax');
        
        // Register share increment for non-logged-in users (comment likes require login)
        add_action('wp_ajax_nopriv_vh360_increment_share', 'vh360_handle_share_increment_ajax');
        
        // Check for database upgrades
        add_action('admin_init', array('VH360_Community_Database', 'maybe_upgrade'));
    }
    
    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Create database tables (this also sets the db version option)
        require_once VH360_COMMUNITY_PLUGIN_DIR . 'includes/database.php';
        VH360_Community_Database::create_tables();
        
        // Set activation flag
        add_option('vh360_community_activated', true);
    }
    
    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Cleanup transients/cache if needed
        delete_transient('vh360_community_cache');
    }
}
