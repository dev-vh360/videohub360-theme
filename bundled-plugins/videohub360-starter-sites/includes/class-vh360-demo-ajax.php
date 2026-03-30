<?php
/**
 * AJAX Handler for Starter Sites
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_AJAX {
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_AJAX
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_AJAX
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
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vh360_ss_fetch_demos', array($this, 'ajax_fetch_demos'));
        add_action('wp_ajax_vh360_ss_import_demo', array($this, 'ajax_import_demo'));
        add_action('wp_ajax_vh360_ss_get_import_status', array($this, 'ajax_get_import_status'));
        add_action('wp_ajax_vh360_ss_get_import_log', array($this, 'ajax_get_import_log'));
        add_action('wp_ajax_vh360_ss_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * AJAX: Fetch demos from registry
     */
    public function ajax_fetch_demos() {
        check_ajax_referer('vh360_ss_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this feature.', 'videohub360-starter-sites'),
            ));
        }
        
        $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';
        
        $registry = VH360_Demo_Registry::get_instance();
        $demos = $registry->fetch_demos($force_refresh);
        
        if (is_wp_error($demos)) {
            wp_send_json_error(array(
                'message' => $demos->get_error_message(),
            ));
        }
        
        wp_send_json_success(array(
            'demos' => array_values($demos),
        ));
    }
    
    /**
     * AJAX: Import demo
     */
    public function ajax_import_demo() {
        check_ajax_referer('vh360_ss_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to import demos.', 'videohub360-starter-sites'),
            ));
        }
        
        if (!isset($_POST['demo_id'])) {
            wp_send_json_error(array(
                'message' => __('Demo ID is required.', 'videohub360-starter-sites'),
            ));
        }
        
        $demo_id = sanitize_key($_POST['demo_id']);
        
        // Check if import is already running
        if (vh360_ss_is_import_running()) {
            wp_send_json_error(array(
                'message' => __('Another import is already in progress.', 'videohub360-starter-sites'),
            ));
        }
        
        // Increase time limit and memory limit
        set_time_limit(0);
        @ini_set('memory_limit', '512M');
        
        // Run import
        $importer = VH360_Demo_Importer::get_instance();
        $result = $importer->import_demo($demo_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'log' => VH360_Demo_Logger::get_last_log(),
            ));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get import status
     */
    public function ajax_get_import_status() {
        check_ajax_referer('vh360_ss_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this feature.', 'videohub360-starter-sites'),
            ));
        }
        
        $is_running = vh360_ss_is_import_running();
        $demo_id = $is_running ? get_transient('vh360_ss_import_in_progress') : false;
        
        wp_send_json_success(array(
            'is_running' => $is_running,
            'demo_id' => $demo_id,
        ));
    }
    
    /**
     * AJAX: Get import log
     */
    public function ajax_get_import_log() {
        check_ajax_referer('vh360_ss_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this feature.', 'videohub360-starter-sites'),
            ));
        }
        
        $log = VH360_Demo_Logger::get_last_log();
        
        if (!$log) {
            wp_send_json_error(array(
                'message' => __('No import log found.', 'videohub360-starter-sites'),
            ));
        }
        
        wp_send_json_success(array(
            'log' => $log,
        ));
    }
    
    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('vh360_ss_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to clear cache.', 'videohub360-starter-sites'),
            ));
        }
        
        $registry = VH360_Demo_Registry::get_instance();
        $registry->clear_cache();
        
        wp_send_json_success(array(
            'message' => __('Cache cleared successfully.', 'videohub360-starter-sites'),
        ));
    }
}
