<?php
/**
 * Main Starter Sites Plugin Class
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Starter_Sites {
    
    /**
     * Singleton instance
     *
     * @var VH360_Starter_Sites
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Starter_Sites
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
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Initialize AJAX handlers
        VH360_Demo_AJAX::get_instance();
        
        // Schedule cleanup of old temp files (daily)
        if (!wp_next_scheduled('vh360_ss_cleanup_temp_files')) {
            wp_schedule_event(time(), 'daily', 'vh360_ss_cleanup_temp_files');
        }
        
        add_action('vh360_ss_cleanup_temp_files', array($this, 'cleanup_temp_files'));
    }
    
    /**
     * Cleanup old temp files
     */
    public function cleanup_temp_files() {
        vh360_ss_cleanup_old_temp_files(86400); // Delete files older than 24 hours
    }
    
    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public static function get_version() {
        return VH360_STARTER_SITES_VERSION;
    }
}
