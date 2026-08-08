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
        // The handler will only execute if $shutdown_handler_registered remains true
        // It's set to false after successful completion to prevent double-execution
        $shutdown_handler_registered = false;
        $last_import_step = 'AJAX handler entered';
        $request_id = '';
        $demo_id = '';
        $owns_import_lock = false;
        
        register_shutdown_function(function() use (&$last_import_step, &$shutdown_handler_registered, &$request_id, &$demo_id, &$owns_import_lock) {
            if (!$shutdown_handler_registered) {
                return;
            }
            
            $error = error_get_last();
            if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
                if ($owns_import_lock) {
                    $request_status = vh360_ss_get_import_request_status($request_id);
                    if (!is_array($request_status) || !in_array($request_status['status'], array('completed', 'failed'), true)) {
                        vh360_ss_set_import_request_failed($request_id, $demo_id, array(
                            'message'    => __('A fatal error occurred during import. Please check the import log for details.', 'videohub360-starter-sites'),
                            'error_code' => 'fatal_import_error',
                            'log'        => VH360_Demo_Logger::get_last_log(),
                        ));
                    }
                    vh360_ss_release_import_lock($request_id);
                    $owns_import_lock = false;
                }
                // Fatal error occurred - send structured error response
                if (!headers_sent()) {
                    status_header(200); // Override 500
                    header('Content-Type: application/json; charset=utf-8');
                    
                    // Build response with diagnostics gated behind WP_DEBUG
                    $response_data = array(
                        'message' => __('A fatal error occurred during import. Please check the debug log for details.', 'videohub360-starter-sites'),
                        'log' => VH360_Demo_Logger::get_last_log(),
                    );
                    
                    // Add detailed diagnostics only in debug mode
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        $response_data['message'] = sprintf(
                            'Fatal error during import: %s in %s on line %d',
                            $error['message'],
                            $error['file'],
                            $error['line']
                        );
                        $response_data['last_step'] = $last_import_step;
                        $response_data['error_type'] = 'fatal';
                        $response_data['error_details'] = $error;
                        $response_data['memory_peak'] = memory_get_peak_usage(true);
                    }
                    
                    $response = array(
                        'success' => false,
                        'data' => $response_data,
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

            if (empty($_POST['import_request_id'])) {
                wp_send_json_error(array(
                    'message' => __('Import request ID is required.', 'videohub360-starter-sites'),
                ));
            }

            $request_id = sanitize_text_field(wp_unslash($_POST['import_request_id']));
            if (strlen($request_id) > 100) {
                wp_send_json_error(array(
                    'message' => __('Invalid import request ID.', 'videohub360-starter-sites'),
                ));
            }

            // Make replays idempotent even after the active lock was released.
            $existing_request = vh360_ss_get_import_request_status($request_id);
            if (is_array($existing_request) && $demo_id === $existing_request['demo_id']) {
                if ('completed' === $existing_request['status']) {
                    wp_send_json_success($existing_request['result']);
                }

                if ('failed' === $existing_request['status']) {
                    wp_send_json_error($existing_request['result']);
                }

                if ('running' === $existing_request['status']) {
                    wp_send_json_error(array(
                        'message'    => __('This import request is already running.', 'videohub360-starter-sites'),
                        'error_code' => 'already_running_same_request',
                    ));
                }
            }
            
            $last_import_step = 'Acquiring import lock';
            $lock_result = vh360_ss_acquire_import_lock($demo_id, $request_id);
            if ('already_running_same_request' === $lock_result) {
                wp_send_json_error(array(
                    'message' => __('This import request is already running.', 'videohub360-starter-sites'),
                    'error_code' => 'already_running_same_request',
                ));
            }

            if ('acquired' !== $lock_result) {
                wp_send_json_error(array(
                    'message' => __('Another import is already in progress.', 'videohub360-starter-sites'),
                    'error_code' => 'import_in_progress',
                ));
            }

            $owns_import_lock = true;
            vh360_ss_set_import_request_running($request_id, $demo_id);
            
            // Increase time limit and memory limit
            $last_import_step = 'Setting time and memory limits';
            set_time_limit(0);
            @ini_set('memory_limit', '512M');
            
            // Run import
            $last_import_step = 'Calling importer->import_demo()';
            $importer = VH360_Demo_Importer::get_instance();
            $result = $importer->import_demo($demo_id, function($step_name) use (&$last_import_step) {
                $last_import_step = $step_name;
            });
            
            $last_import_step = 'Import completed, checking result';
            
            if (is_wp_error($result)) {
                $last_import_step = 'Import returned WP_Error';
                
                // Build error response with diagnostics gated behind WP_DEBUG
                $error_data = array(
                    'message' => $result->get_error_message(),
                    'error_code' => $result->get_error_code(),
                    'log' => VH360_Demo_Logger::get_last_log(),
                );
                
                // Add detailed diagnostics only in debug mode
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $error_data['last_step'] = $last_import_step;
                }

                vh360_ss_set_import_request_failed($request_id, $demo_id, $error_data);
                $response = array('success' => false, 'data' => $error_data);
            } else {
                $last_import_step = 'Preparing JSON success response';

                // Add diagnostics to success response only in debug mode
                if (is_array($result) && defined('WP_DEBUG') && WP_DEBUG) {
                    $result['diagnostics'] = array(
                        'memory_peak' => memory_get_peak_usage(true),
                        'memory_current' => memory_get_usage(true),
                        'last_step' => $last_import_step,
                    );
                }

                $last_import_step = 'Sending JSON success response';
                vh360_ss_set_import_request_completed($request_id, $demo_id, $result);
                $response = array('success' => true, 'data' => $result);
            }
            
        } catch (Throwable $e) {
            // Catch all throwables (Exception + Error in PHP 7+)
            $last_import_step = 'Caught exception: ' . $e->getMessage();
            
            // Build error response with diagnostics gated behind WP_DEBUG
            $error_data = array(
                'message' => sprintf(
                    __('Import failed: %s', 'videohub360-starter-sites'),
                    $e->getMessage()
                ),
                'log' => VH360_Demo_Logger::get_last_log(),
            );
            
            // Add detailed diagnostics only in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_data['error_type'] = 'exception';
                $error_data['error_class'] = get_class($e);
                $error_data['file'] = $e->getFile();
                $error_data['line'] = $e->getLine();
                $error_data['trace'] = $e->getTraceAsString();
                $error_data['last_step'] = $last_import_step;
                $error_data['memory_peak'] = memory_get_peak_usage(true);
            }

            if ($owns_import_lock) {
                $recovery_error = array_intersect_key($error_data, array_flip(array(
                    'message',
                    'error_code',
                    'log',
                    'last_step',
                    'error_type',
                    'memory_peak',
                )));
                vh360_ss_set_import_request_failed($request_id, $demo_id, $recovery_error);
            }
            $response = array('success' => false, 'data' => $error_data);
        } finally {
            if ($owns_import_lock) {
                vh360_ss_release_import_lock($request_id);
                $owns_import_lock = false;
            }
        }
        
        $shutdown_handler_registered = false;
        wp_send_json($response);
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
        
        if (empty($_POST['import_request_id'])) {
            wp_send_json_error(array(
                'message' => __('Import request ID is required.', 'videohub360-starter-sites'),
            ));
        }

        $request_id = sanitize_text_field(wp_unslash($_POST['import_request_id']));
        $demo_id = isset($_POST['demo_id']) ? sanitize_key(wp_unslash($_POST['demo_id'])) : '';
        if (strlen($request_id) > 100) {
            wp_send_json_error(array(
                'message' => __('Invalid import request ID.', 'videohub360-starter-sites'),
            ));
        }

        $request_status = vh360_ss_get_import_request_status($request_id);
        if (!is_array($request_status) || ('' !== $demo_id && $demo_id !== $request_status['demo_id'])) {
            wp_send_json_success(array(
                'request_id' => $request_id,
                'demo_id'    => $demo_id,
                'status'     => 'not_found',
            ));
        }

        wp_send_json_success($request_status);
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
