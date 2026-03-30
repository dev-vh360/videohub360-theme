<?php
/**
 * Demo Importer Orchestrator
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_Importer {
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_Importer
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var VH360_Demo_Logger
     */
    private $logger;
    
    /**
     * Registry instance
     *
     * @var VH360_Demo_Registry
     */
    private $registry;
    
    /**
     * Downloader instance
     *
     * @var VH360_Demo_Downloader
     */
    private $downloader;
    
    /**
     * Post-import instance
     *
     * @var VH360_Demo_Post_Import
     */
    private $post_import;
    
    /**
     * Current demo being imported
     *
     * @var array
     */
    private $current_demo = array();
    
    /**
     * Current manifest
     *
     * @var array
     */
    private $current_manifest = array();
    
    /**
     * Downloaded files
     *
     * @var array
     */
    private $downloaded_files = array();
    
    /**
     * Extracted directories
     *
     * @var array
     */
    private $extracted_dirs = array();
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_Importer
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
        $this->logger = VH360_Demo_Logger::get_instance();
        $this->registry = VH360_Demo_Registry::get_instance();
        $this->downloader = VH360_Demo_Downloader::get_instance();
        $this->post_import = VH360_Demo_Post_Import::get_instance();
    }
    
    /**
     * Import a demo site
     *
     * @param string $demo_id Demo ID to import
     * @param callable $progress_callback Optional callback to track progress
     * @return array|WP_Error Result array or error
     */
    public function import_demo($demo_id, $progress_callback = null) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);
        
        // Helper closure to log each step with diagnostic information
        // Logs: step name, elapsed time, current memory, peak memory
        // Also invokes progress callback to track last successful step in AJAX handler
        $log_step = function($step_name) use ($start_time, $progress_callback) {
            $elapsed = microtime(true) - $start_time;
            $memory_current = memory_get_usage(true);
            $memory_peak = memory_get_peak_usage(true);
            
            $this->logger->info(sprintf(
                '[%s] Elapsed: %.2fs | Memory: %s | Peak: %s',
                $step_name,
                $elapsed,
                vh360_ss_format_bytes($memory_current),
                vh360_ss_format_bytes($memory_peak)
            ));
            
            if ($progress_callback && is_callable($progress_callback)) {
                call_user_func($progress_callback, $step_name);
            }
        };
        
        // Clear previous log
        $this->logger->clear();
        $this->logger->set_demo_id($demo_id);
        
        $this->logger->info('========== STARTING DEMO IMPORT ==========');
        $this->logger->info('Demo ID: ' . $demo_id);
        $log_step('Import initialized');
        
        try {
            // Step 1: Validate environment (BEFORE setting lock)
            $log_step('Validating environment');
            $validation = $this->validate_environment();
            if (is_wp_error($validation)) {
                throw new Exception($validation->get_error_message());
            }
            $log_step('Environment validation complete');
            
            // Set import in progress flag AFTER validation passes
            vh360_ss_set_import_running($demo_id);
            $log_step('Import lock acquired');
            
            // Step 2: Get demo data from registry
            $log_step('Fetching demo from registry');
            $demo = $this->registry->get_demo($demo_id);
            if (is_wp_error($demo)) {
                throw new Exception($demo->get_error_message());
            }
            
            $this->current_demo = $demo;
            $this->logger->info('Demo: ' . $demo['name'] . ' v' . $demo['version']);
            $log_step('Demo metadata loaded');
            
            // Step 3: Download and parse manifest
            $log_step('Downloading manifest');
            $manifest = $this->downloader->download_manifest($demo['package_manifest_url']);
            if (is_wp_error($manifest)) {
                throw new Exception($manifest->get_error_message());
            }
            
            $this->current_manifest = $manifest;
            $log_step('Manifest downloaded and parsed');
            
            // Step 4: Download package files
            $log_step('Downloading package files');
            $files = $this->downloader->download_package_files($manifest);
            if (is_wp_error($files)) {
                throw new Exception($files->get_error_message());
            }
            
            $this->downloaded_files = $files;
            $log_step('Package files downloaded');
            
            // Step 5: Ensure required plugins are active
            $log_step('Ensuring required plugins');
            $plugins_result = $this->ensure_required_plugins();
            if (is_wp_error($plugins_result)) {
                throw new Exception($plugins_result->get_error_message());
            }
            $log_step('Required plugins ensured');
            
            // Step 6: Import content
            $log_step('Importing content');
            $content_result = $this->import_content();
            if (is_wp_error($content_result)) {
                throw new Exception($content_result->get_error_message());
            }
            $log_step('Content import complete');
            
            // Step 7: Import widgets
            $log_step('Importing widgets');
            $widgets_result = $this->import_widgets();
            if (is_wp_error($widgets_result)) {
                $this->logger->warning('Widget import failed: ' . $widgets_result->get_error_message());
            }
            $log_step('Widgets import complete');
            
            // Step 8: Import Customizer settings
            $log_step('Importing customizer settings');
            $customizer_result = $this->import_customizer();
            if (is_wp_error($customizer_result)) {
                $this->logger->warning('Customizer import failed: ' . $customizer_result->get_error_message());
            }
            $log_step('Customizer import complete');
            
            // Step 9: Import Elementor kit
            $log_step('Importing Elementor kit');
            $elementor_result = $this->import_elementor_kit();
            if (is_wp_error($elementor_result)) {
                $this->logger->warning('Elementor kit import failed: ' . $elementor_result->get_error_message());
            }
            $log_step('Elementor kit import complete');
            
            // Step 10: Import theme options
            $log_step('Importing theme options');
            $options_result = $this->import_theme_options();
            if (is_wp_error($options_result)) {
                $this->logger->warning('Theme options import failed: ' . $options_result->get_error_message());
            }
            $log_step('Theme options import complete');
            
            // Step 11: Run post-import setup
            $log_step('Running post-import setup');
            $post_import_result = $this->post_import->run($manifest);
            if (is_wp_error($post_import_result)) {
                $this->logger->warning('Post-import setup failed: ' . $post_import_result->get_error_message());
            }
            $log_step('Post-import setup complete');
            
            // Step 12: Verify import
            $log_step('Verifying import');
            $verification = $this->post_import->verify_import($manifest);
            $log_step('Import verification complete');
            
            // Step 13: Cleanup downloaded files and extracted directories
            $log_step('Cleaning up temporary files');
            $this->cleanup_safely();
            $log_step('Cleanup complete');
            
            // Clear import in progress flag
            $log_step('Clearing import lock');
            vh360_ss_clear_import_running();
            $log_step('Import lock released');
            
            // Log completion BEFORE saving
            $elapsed_total = microtime(true) - $start_time;
            $this->logger->info(sprintf(
                '========== DEMO IMPORT COMPLETED (%.2fs) ==========',
                $elapsed_total
            ));
            $log_step('Logging completion message');
            
            // Save log AFTER all entries are written
            $log_step('Saving import log');
            $this->logger->save();
            $log_step('Import log saved');
            
            // Build response
            $log_step('Building response payload');
            $response = array(
                'success' => true,
                'demo_id' => $demo_id,
                'demo_name' => $demo['name'],
                'verification' => $verification,
                'log' => $this->logger->get_last_log(),
                'duration' => round($elapsed_total, 2),
            );
            $log_step('Response payload built');
            
            return $response;
            
        } catch (Throwable $t) {
            // Catch all throwables (Exception + PHP 7+ Error types)
            // Server-side logging always includes full details for support/debugging
            $this->logger->error('Import failed: ' . $t->getMessage());
            $this->logger->error('Error in file: ' . $t->getFile() . ' on line ' . $t->getLine());
            
            // Cleanup on error - use safe cleanup
            $log_step('Cleaning up after error');
            $this->cleanup_safely();
            
            vh360_ss_clear_import_running();
            $log_step('Import lock released after error');
            
            $this->logger->save();
            $log_step('Error log saved');
            
            // Return WP_Error with message only - AJAX handler gates detailed info behind WP_DEBUG
            return new WP_Error('import_failed', $t->getMessage());
        }
    }
    
    /**
     * Safe cleanup of temporary files and directories
     * Catches and logs errors instead of throwing them
     *
     * @return void
     */
    private function cleanup_safely() {
        try {
            if (!empty($this->downloaded_files)) {
                $this->downloader->cleanup_files($this->downloaded_files);
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to cleanup downloaded files: ' . $e->getMessage());
        }
        
        try {
            if (!empty($this->extracted_dirs)) {
                $this->cleanup_extracted_dirs();
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to cleanup extracted directories: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate environment before import
     *
     * @return bool|WP_Error True if valid, error otherwise
     */
    private function validate_environment() {
        $this->logger->info('Validating environment');
        
        // Check if another import is running
        if (vh360_ss_is_import_running()) {
            return new WP_Error('import_in_progress', __('Another import is already in progress', 'videohub360-starter-sites'));
        }
        
        // Check server requirements
        $requirements = vh360_ss_check_server_requirements();
        if (!$requirements['passed']) {
            $this->logger->error('Server requirements not met', $requirements['errors']);
            return new WP_Error('requirements_not_met', implode(' ', $requirements['errors']));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return new WP_Error('insufficient_permissions', __('You do not have permission to import demos', 'videohub360-starter-sites'));
        }
        
        // Check if WordPress Importer plugin is available
        if (!class_exists('WP_Import')) {
            // Try to load it
            $importer_path = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
            $import_path = ABSPATH . 'wp-admin/includes/import.php';
            
            if (file_exists($importer_path)) {
                require_once $importer_path;
            }
            
            if (file_exists($import_path)) {
                require_once $import_path;
            }
        }
        
        $this->logger->success('Environment validation passed');
        
        return true;
    }
    
    /**
     * Ensure required plugins are active
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function ensure_required_plugins() {
        $this->logger->info('Checking required plugins');
        
        if (empty($this->current_demo['required_plugins'])) {
            $this->logger->info('No required plugins specified');
            return true;
        }
        
        $missing_plugins = array();
        $activated_plugins = array();
        // Track plugins that were newly installed during this import and successfully activated
        $installed_plugins = array();
        
        foreach ($this->current_demo['required_plugins'] as $plugin_slug) {
            // Check if already active
            if (vh360_ss_is_plugin_active($plugin_slug)) {
                $this->logger->info(sprintf('Plugin already active: %s', $plugin_slug));
                continue;
            }
            
            // Check if installed but inactive
            if (vh360_ss_is_plugin_installed($plugin_slug)) {
                $this->logger->info(sprintf('Activating plugin: %s', $plugin_slug));
                $activation_result = vh360_ss_activate_plugin($plugin_slug);
                
                if (is_wp_error($activation_result)) {
                    $this->logger->error(sprintf('Failed to activate plugin %s: %s', $plugin_slug, $activation_result->get_error_message()));
                    $missing_plugins[] = $plugin_slug;
                } else {
                    $this->logger->success(sprintf('Activated plugin: %s', $plugin_slug));
                    $activated_plugins[] = $plugin_slug;
                }
            } else {
                // Plugin not installed - try to install from bundled ZIP or WordPress.org
                $install_result = null;
                
                if (vh360_ss_is_bundled_plugin($plugin_slug)) {
                    // Install from bundled ZIP
                    $this->logger->info(sprintf('Installing bundled plugin: %s', $plugin_slug));
                    $install_result = vh360_ss_install_bundled_plugin($plugin_slug);
                } else {
                    // Try to install from WordPress.org repository
                    $this->logger->info(sprintf('Installing plugin from WordPress.org: %s', $plugin_slug));
                    $install_result = vh360_ss_install_repository_plugin($plugin_slug);
                }
                
                if (is_wp_error($install_result)) {
                    $this->logger->error(sprintf('Failed to install plugin %s: %s', $plugin_slug, $install_result->get_error_message()));
                    $missing_plugins[] = $plugin_slug;
                } else {
                    $this->logger->success(sprintf('Installed plugin: %s', $plugin_slug));
                    
                    // Now activate the newly installed plugin
                    $this->logger->info(sprintf('Activating newly installed plugin: %s', $plugin_slug));
                    $activation_result = vh360_ss_activate_plugin($plugin_slug);
                    
                    if (is_wp_error($activation_result)) {
                        $this->logger->error(sprintf('Failed to activate plugin %s: %s', $plugin_slug, $activation_result->get_error_message()));
                        $missing_plugins[] = $plugin_slug;
                    } else {
                        $this->logger->success(sprintf('Activated plugin: %s', $plugin_slug));
                        // Only add to installed and activated lists after successful activation
                        $installed_plugins[] = $plugin_slug;
                        $activated_plugins[] = $plugin_slug;
                    }
                }
            }
        }
        
        if (!empty($missing_plugins)) {
            $error_message = sprintf(
                __('Required plugins are not available: %s. Please install and activate these plugins before importing.', 'videohub360-starter-sites'),
                implode(', ', $missing_plugins)
            );
            return new WP_Error('required_plugins_unavailable', $error_message);
        }
        
        if (!empty($installed_plugins)) {
            $this->logger->success(sprintf('Installed %d required plugins', count($installed_plugins)));
        }
        
        if (!empty($activated_plugins)) {
            $this->logger->success(sprintf('Activated %d required plugins', count($activated_plugins)));
        }
        
        $this->logger->success('All required plugins are active');
        
        return true;
    }
    
    /**
     * Import WordPress content from XML
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_content() {
        $this->logger->info('Importing content');
        
        if (!isset($this->downloaded_files['content'])) {
            $this->logger->info('No content file to import');
            return true;
        }
        
        $content_file = $this->downloaded_files['content'];
        
        if (!file_exists($content_file)) {
            return new WP_Error('content_file_missing', __('Content file not found', 'videohub360-starter-sites'));
        }
        
        // Load WordPress Importer
        if (!class_exists('WP_Import')) {
            $importer_file = VH360_STARTER_SITES_INCLUDES . 'wordpress-importer/wordpress-importer.php';
            
            if (!file_exists($importer_file)) {
                return new WP_Error('importer_not_found', __('WordPress Importer not found', 'videohub360-starter-sites'));
            }
            
            require_once $importer_file;
        }
        
        if (!class_exists('WP_Import')) {
            return new WP_Error('importer_class_not_found', __('WP_Import class not available', 'videohub360-starter-sites'));
        }
        
        // Run import
        ob_start();
        
        $importer = new WP_Import();
        $importer->fetch_attachments = true; // Import attachments
        
        $import_result = $importer->import($content_file);
        
        $output = ob_get_clean();
        
        if (is_wp_error($import_result)) {
            $this->logger->error('Content import failed: ' . $import_result->get_error_message());
            return $import_result;
        }
        
        $this->logger->success('Content imported successfully');
        
        return true;
    }
    
    /**
     * Import widgets
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_widgets() {
        $this->logger->info('Importing widgets');
        
        if (!isset($this->downloaded_files['widgets'])) {
            $this->logger->info('No widgets file to import');
            return true;
        }
        
        $widgets_file = $this->downloaded_files['widgets'];
        
        if (!file_exists($widgets_file)) {
            return new WP_Error('widgets_file_missing', __('Widgets file not found', 'videohub360-starter-sites'));
        }
        
        $data = file_get_contents($widgets_file);
        if (empty($data)) {
            return new WP_Error('widgets_file_empty', __('Widgets file is empty', 'videohub360-starter-sites'));
        }
        
        $import_result = $this->import_widgets_data($data);
        
        if (is_wp_error($import_result)) {
            return $import_result;
        }
        
        $this->logger->success('Widgets imported successfully');
        
        return true;
    }
    
    /**
     * Import widgets data
     *
     * @param string $data Widget data
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_widgets_data($data) {
        $widget_data = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('widgets_invalid_json', __('Invalid widgets JSON', 'videohub360-starter-sites'));
        }
        
        // Import widget settings
        if (isset($widget_data['widgets'])) {
            foreach ($widget_data['widgets'] as $widget_id => $widget_settings) {
                update_option($widget_id, $widget_settings);
            }
        }
        
        // Import sidebars widgets
        if (isset($widget_data['sidebars'])) {
            update_option('sidebars_widgets', $widget_data['sidebars']);
        }
        
        return true;
    }
    
    /**
     * Import Customizer settings
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_customizer() {
        $this->logger->info('Importing Customizer settings');
        
        if (!isset($this->downloaded_files['customizer'])) {
            $this->logger->info('No Customizer file to import');
            return true;
        }
        
        $customizer_file = $this->downloaded_files['customizer'];
        
        if (!file_exists($customizer_file)) {
            return new WP_Error('customizer_file_missing', __('Customizer file not found', 'videohub360-starter-sites'));
        }
        
        $data = file_get_contents($customizer_file);
        if (empty($data)) {
            return new WP_Error('customizer_file_empty', __('Customizer file is empty', 'videohub360-starter-sites'));
        }
        
        $customizer_data = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('customizer_invalid_json', __('Invalid Customizer JSON', 'videohub360-starter-sites'));
        }
        
        // Import theme mods
        if (isset($customizer_data['mods'])) {
            foreach ($customizer_data['mods'] as $key => $value) {
                set_theme_mod($key, $value);
            }
        }
        
        // Import options
        if (isset($customizer_data['options'])) {
            foreach ($customizer_data['options'] as $key => $value) {
                update_option($key, $value);
            }
        }
        
        $this->logger->success('Customizer settings imported successfully');
        
        return true;
    }
    
    /**
     * Normalize extracted Elementor kit directory
     * 
     * Removes system files and detects the actual kit root folder
     *
     * @param string $extract_dir Extraction directory
     * @return string|WP_Error Normalized root directory or error
     */
    private function normalize_elementor_kit_directory($extract_dir) {
        if (!is_dir($extract_dir)) {
            return new WP_Error('invalid_dir', __('Invalid extraction directory', 'videohub360-starter-sites'));
        }
        
        // Scan directory contents
        $items = scandir($extract_dir);
        if ($items === false) {
            return new WP_Error('scan_failed', __('Failed to scan extraction directory', 'videohub360-starter-sites'));
        }
        
        // Filter out system files and hidden files
        $filtered_items = array();
        foreach ($items as $item) {
            // Skip current/parent directory references
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            // Skip macOS metadata folder
            if ($item === '__MACOSX') {
                continue;
            }
            
            // Skip hidden files and .DS_Store
            if (strpos($item, '.') === 0) {
                continue;
            }
            
            $filtered_items[] = $item;
        }
        
        // If extraction produced a single subfolder, use that as root
        if (count($filtered_items) === 1 && is_dir($extract_dir . '/' . $filtered_items[0])) {
            $nested_dir = $extract_dir . '/' . $filtered_items[0];
            $this->logger->info('Detected nested Elementor kit directory: ' . basename($nested_dir));
            return $nested_dir;
        }
        
        // Otherwise, use the extraction directory itself
        return $extract_dir;
    }
    
    /**
     * Recursively discover all JSON files in a directory
     *
     * @param string $dir Directory to scan
     * @return array Array of JSON file paths
     */
    private function discover_json_files_recursive($dir) {
        $json_files = array();
        
        if (!is_dir($dir)) {
            return $json_files;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'json') {
                // Skip macOS metadata
                if (strpos($file->getPathname(), '__MACOSX') !== false) {
                    continue;
                }
                
                $json_files[] = $file->getPathname();
            }
        }
        
        return $json_files;
    }
    
    /**
     * Detect file roles in Elementor kit
     *
     * @param array $json_files Array of JSON file paths
     * @param string $kit_root Kit root directory
     * @return array Array with categorized files
     */
    private function detect_elementor_file_roles($json_files, $kit_root) {
        $categorized = array(
            'manifest' => null,
            'site_settings' => null,
            'templates' => array(),
            'content' => array(),
            'taxonomies' => array(),
            'other' => array()
        );
        
        foreach ($json_files as $file_path) {
            $basename = basename($file_path);
            $relative_path = str_replace($kit_root . '/', '', $file_path);
            
            // Detect file role by name and location
            if ($basename === 'manifest.json') {
                $categorized['manifest'] = $file_path;
            } elseif ($basename === 'site-settings.json') {
                $categorized['site_settings'] = $file_path;
            } elseif (strpos($basename, 'elementor-') === 0) {
                // Elementor template/document files
                $categorized['templates'][] = $file_path;
            } elseif (strpos($relative_path, 'content/') === 0) {
                $categorized['content'][] = $file_path;
            } elseif (strpos($relative_path, 'taxonomies/') === 0) {
                $categorized['taxonomies'][] = $file_path;
            } else {
                $categorized['other'][] = $file_path;
            }
        }
        
        return $categorized;
    }
    
    /**
     * Import Elementor kit
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_elementor_kit() {
        $this->logger->info('Importing Elementor kit');
        
        if (!isset($this->downloaded_files['elementor_kit'])) {
            $this->logger->info('No Elementor kit file to import');
            return true;
        }
        
        // Check if Elementor is active
        if (!vh360_ss_check_elementor()) {
            return new WP_Error('elementor_not_active', __('Elementor is not active', 'videohub360-starter-sites'));
        }
        
        $kit_file = $this->downloaded_files['elementor_kit'];
        
        if (!file_exists($kit_file)) {
            return new WP_Error('elementor_kit_missing', __('Elementor kit file not found', 'videohub360-starter-sites'));
        }
        
        // Extract ZIP if needed
        $kit_root = null;
        if (pathinfo($kit_file, PATHINFO_EXTENSION) === 'zip') {
            $extract_dir = vh360_ss_get_temp_dir() . '/elementor-kit-' . time();
            $extract_result = $this->downloader->extract_zip($kit_file, $extract_dir);
            
            if (is_wp_error($extract_result)) {
                return $extract_result;
            }
            
            // Track extracted directory for cleanup
            $this->extracted_dirs[] = $extract_dir;
            
            // Normalize the extraction directory (handle nested folders, ignore system files)
            $kit_root = $this->normalize_elementor_kit_directory($extract_dir);
            if (is_wp_error($kit_root)) {
                return $kit_root;
            }
            
            $this->logger->info('Elementor kit root: ' . basename($kit_root));
            
            // Recursively discover all JSON files
            $json_files = $this->discover_json_files_recursive($kit_root);
            
            if (empty($json_files)) {
                return new WP_Error('elementor_kit_no_json', sprintf(
                    __('No JSON files found in Elementor kit. Scanned directory: %s', 'videohub360-starter-sites'),
                    basename($kit_root)
                ));
            }
            
            $this->logger->info(sprintf('Found %d JSON files in Elementor kit', count($json_files)));
            
            // Detect file roles
            $categorized_files = $this->detect_elementor_file_roles($json_files, $kit_root);
            
            // Log what we found
            if ($categorized_files['manifest']) {
                $this->logger->info('Found manifest.json');
            } else {
                $this->logger->warning('No manifest.json found');
            }
            
            if ($categorized_files['site_settings']) {
                $this->logger->info('Found site-settings.json');
            } else {
                $this->logger->warning('No site-settings.json found');
            }
            
            if (!empty($categorized_files['templates'])) {
                $this->logger->info(sprintf('Found %d template files', count($categorized_files['templates'])));
            }
            
        } else {
            // Single JSON file (legacy support)
            $categorized_files = array(
                'manifest' => null,
                'site_settings' => $kit_file,
                'templates' => array(),
                'content' => array(),
                'taxonomies' => array(),
                'other' => array()
            );
        }
        
        // Import the kit using Elementor's import functionality
        if (class_exists('\Elementor\Plugin')) {
            try {
                // Check if Elementor instance is available
                if (!\Elementor\Plugin::$instance) {
                    $this->logger->warning('Elementor instance not fully initialized');
                }
                
                $imported_items = 0;
                
                // Step 1: Import site settings if available
                if ($categorized_files['site_settings'] && file_exists($categorized_files['site_settings'])) {
                    $result = $this->import_elementor_site_settings($categorized_files['site_settings']);
                    if ($result === true) {
                        $imported_items++;
                    } elseif (is_wp_error($result)) {
                        $this->logger->warning('Site settings import failed: ' . $result->get_error_message());
                    }
                }
                
                // Step 2: Import template files if available
                if (!empty($categorized_files['templates'])) {
                    foreach ($categorized_files['templates'] as $template_file) {
                        $result = $this->import_elementor_template_file($template_file);
                        if ($result === true) {
                            $imported_items++;
                        } elseif (is_wp_error($result)) {
                            $this->logger->warning('Template import failed for ' . basename($template_file) . ': ' . $result->get_error_message());
                        }
                    }
                }
                
                if ($imported_items > 0) {
                    $this->logger->success(sprintf('Elementor kit imported successfully (%d items)', $imported_items));
                    return true;
                } else {
                    $this->logger->warning('No Elementor kit items could be imported');
                    return new WP_Error('elementor_kit_no_items', __('No valid Elementor kit items found to import', 'videohub360-starter-sites'));
                }
                
            } catch (Exception $e) {
                $this->logger->error('Elementor kit import exception: ' . $e->getMessage());
                return new WP_Error('elementor_kit_import_failed', $e->getMessage());
            }
        }
        
        // If we get here, Elementor import failed
        return new WP_Error('elementor_kit_import_unavailable', __('Elementor import functionality not available', 'videohub360-starter-sites'));
    }
    
    /**
     * Import Elementor site settings from JSON file
     *
     * @param string $settings_file Path to site-settings.json
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_elementor_site_settings($settings_file) {
        if (!file_exists($settings_file)) {
            return new WP_Error('settings_not_found', __('Site settings file not found', 'videohub360-starter-sites'));
        }
        
        $settings_data = file_get_contents($settings_file);
        if (!$settings_data) {
            return new WP_Error('settings_read_failed', __('Failed to read site settings file', 'videohub360-starter-sites'));
        }
        
        $settings_json = json_decode($settings_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('settings_invalid_json', __('Invalid JSON in site settings file', 'videohub360-starter-sites'));
        }
        
        // Import settings based on Elementor's structure
        if (isset($settings_json['settings']) && is_array($settings_json['settings'])) {
            foreach ($settings_json['settings'] as $setting_key => $setting_value) {
                update_option($setting_key, $setting_value);
            }
            $this->logger->info('Imported Elementor site settings');
            return true;
        }
        
        // Alternative: Import as post meta for active kit
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance) {
            $elementor_plugin = \Elementor\Plugin::instance();
            if (isset($elementor_plugin->kits_manager)) {
                $active_kit_id = $elementor_plugin->kits_manager->get_active_id();
                if ($active_kit_id) {
                    // Store settings in active kit post meta
                    foreach ($settings_json as $key => $value) {
                        if ($key !== 'settings') {
                            update_post_meta($active_kit_id, '_elementor_' . $key, $value);
                        }
                    }
                    $this->logger->info('Imported Elementor site settings to active kit');
                    return true;
                }
            }
        }
        
        $this->logger->warning('Could not import site settings - no valid method available');
        return false;
    }
    
    /**
     * Import individual Elementor template file
     *
     * @param string $template_file Path to template JSON file
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_elementor_template_file($template_file) {
        if (!file_exists($template_file)) {
            return new WP_Error('template_not_found', __('Template file not found', 'videohub360-starter-sites'));
        }
        
        $template_data = file_get_contents($template_file);
        if (!$template_data) {
            return new WP_Error('template_read_failed', __('Failed to read template file', 'videohub360-starter-sites'));
        }
        
        $template_json = json_decode($template_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('template_invalid_json', __('Invalid JSON in template file', 'videohub360-starter-sites'));
        }
        
        // Try to import using Elementor's template manager
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance) {
            $elementor_plugin = \Elementor\Plugin::instance();
            
            if (isset($elementor_plugin->templates_manager) && method_exists($elementor_plugin->templates_manager, 'import_template')) {
                try {
                    // Prepare template data for import
                    $import_data = array(
                        'content' => $template_json,
                        'page_settings' => isset($template_json['page_settings']) ? $template_json['page_settings'] : array(),
                    );
                    
                    $result = $elementor_plugin->templates_manager->import_template($import_data);
                    
                    if ($result && !is_wp_error($result)) {
                        $this->logger->info('Imported template: ' . basename($template_file));
                        return true;
                    }
                } catch (Exception $e) {
                    $this->logger->warning('Template import exception for ' . basename($template_file) . ': ' . $e->getMessage());
                    return new WP_Error('template_import_exception', $e->getMessage());
                }
            }
        }
        
        // Fallback: Just log that we found the template but couldn't import
        $this->logger->info('Found template file but could not import: ' . basename($template_file));
        return false;
    }
    
    /**
     * Import theme options with allowlist
     *
     * @return bool|WP_Error True on success, error on failure
     */
    private function import_theme_options() {
        $this->logger->info('Importing theme options');
        
        if (!isset($this->downloaded_files['theme_options'])) {
            $this->logger->info('No theme options file to import');
            return true;
        }
        
        $options_file = $this->downloaded_files['theme_options'];
        
        if (!file_exists($options_file)) {
            return new WP_Error('options_file_missing', __('Theme options file not found', 'videohub360-starter-sites'));
        }
        
        $data = file_get_contents($options_file);
        if (empty($data)) {
            return new WP_Error('options_file_empty', __('Theme options file is empty', 'videohub360-starter-sites'));
        }
        
        $options_data = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('options_invalid_json', __('Invalid theme options JSON', 'videohub360-starter-sites'));
        }
        
        // Get allowed options
        $allowed_options = vh360_ss_get_allowed_theme_options();
        
        $imported_count = 0;
        
        foreach ($allowed_options as $option_name => $allowed_keys) {
            if (!isset($options_data[$option_name])) {
                continue;
            }
            
            $option_value = get_option($option_name, array());
            
            // Only import allowed keys
            foreach ($allowed_keys as $key) {
                if (isset($options_data[$option_name][$key])) {
                    $option_value[$key] = $options_data[$option_name][$key];
                }
            }
            
            update_option($option_name, $option_value);
            $imported_count++;
        }
        
        $this->logger->success(sprintf('Imported %d theme option groups', $imported_count));
        
        return true;
    }
    
    /**
     * Clean up extracted directories
     *
     * @return int Number of directories deleted
     */
    private function cleanup_extracted_dirs() {
        $deleted = 0;
        
        foreach ($this->extracted_dirs as $dir_path) {
            if (is_dir($dir_path)) {
                if ($this->recursive_rmdir($dir_path)) {
                    $deleted++;
                }
            }
        }
        
        if ($deleted > 0) {
            $this->logger->info(sprintf('Cleaned up %d extracted directories', $deleted));
        }
        
        return $deleted;
    }
    
    /**
     * Recursively delete a directory
     *
     * @param string $dir Directory path
     * @return bool True on success
     */
    private function recursive_rmdir($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursive_rmdir($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
}
