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
        // Register shutdown handler to catch fatal errors
        $shutdown_handler_registered = false;
        $last_import_step = 'AJAX handler entered';
        
        register_shutdown_function(function() use (&$last_import_step, &$shutdown_handler_registered) {
            if (!$shutdown_handler_registered) {
                return;
            }
            
            $error = error_get_last();
            if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
                // Fatal error occurred - send structured error response
                if (!headers_sent()) {
                    status_header(200); // Override 500
                    header('Content-Type: application/json; charset=utf-8');
                    
                    $response = array(
                        'success' => false,
                        'data' => array(
                            'message' => sprintf(
                                'Fatal error during import: %s in %s on line %d',
                                $error['message'],
                                $error['file'],
                                $error['line']
                            ),
                            'last_step' => $last_import_step,
                            'error_type' => 'fatal',
                            'error_details' => $error,
                            'memory_peak' => memory_get_peak_usage(true),
                            'log' => VH360_Demo_Logger::get_last_log(),
                        ),
                    );
                    
                    echo json_encode($response);
                    die();
                }
            }
        });
        
        $shutdown_handler_registered = true;
        
        try {
            $last_import_step = 'Checking nonce';
            check_ajax_referer('vh360_ss_nonce', 'nonce');
            
            $last_import_step = 'Checking permissions';
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to import demos.', 'videohub360-starter-sites'),
                ));
            }
            
            $last_import_step = 'Validating demo_id parameter';
            if (!isset($_POST['demo_id'])) {
                wp_send_json_error(array(
                    'message' => __('Demo ID is required.', 'videohub360-starter-sites'),
                ));
            }
            
            $demo_id = sanitize_key($_POST['demo_id']);
            $last_import_step = 'Sanitized demo_id: ' . $demo_id;
            
            // Check if import is already running
            $last_import_step = 'Checking for concurrent imports';
            if (vh360_ss_is_import_running()) {
                wp_send_json_error(array(
                    'message' => __('Another import is already in progress.', 'videohub360-starter-sites'),
                ));
            }
            
            // Increase time limit and memory limit
            $last_import_step = 'Setting time and memory limits';
            set_time_limit(0);
            @ini_set('memory_limit', '512M');
            
            // Run import
            $last_import_step = 'Calling importer->import_demo()';
            $importer = VH360_Demo_Importer::get_instance();
            $result = $importer->import_demo($demo_id, function($step) use (&$last_import_step) {
                $last_import_step = $step;
            });
            
            $last_import_step = 'Import completed, checking result';
            
            if (is_wp_error($result)) {
                $last_import_step = 'Import returned WP_Error';
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'error_code' => $result->get_error_code(),
                    'log' => VH360_Demo_Logger::get_last_log(),
                    'last_step' => $last_import_step,
                ));
            }
            
            $last_import_step = 'Preparing JSON success response';
            
            // Add diagnostics to success response
            if (is_array($result)) {
                $result['diagnostics'] = array(
                    'memory_peak' => memory_get_peak_usage(true),
                    'memory_current' => memory_get_usage(true),
                    'last_step' => $last_import_step,
                );
            }
            
            $last_import_step = 'Sending JSON success response';
            wp_send_json_success($result);
            
        } catch (\Throwable $e) {
            // Catch all throwables (Exception + Error in PHP 7+)
            $last_import_step = 'Caught exception: ' . $e->getMessage();
            
            wp_send_json_error(array(
                'message' => 'Import failed with exception: ' . $e->getMessage(),
                'error_type' => 'exception',
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'last_step' => $last_import_step,
                'memory_peak' => memory_get_peak_usage(true),
                'log' => VH360_Demo_Logger::get_last_log(),
            ));
        }
        
        $shutdown_handler_registered = false;
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
