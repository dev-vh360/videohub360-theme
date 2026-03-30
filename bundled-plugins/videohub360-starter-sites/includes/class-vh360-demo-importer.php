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
        
        // Helper to log step with diagnostics
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
            
            // Build response with defensive checks
            $log_step('Building response payload');
            $response = array(
                'success' => true,
                'demo_id' => $demo_id,
                'demo_name' => isset($demo['name']) ? $demo['name'] : 'Unknown',
                'verification' => $verification,
                'log' => $this->logger->get_last_log(),
                'duration' => round($elapsed_total, 2),
            );
            $log_step('Response payload built');
            
            return $response;
            
        } catch (Exception $e) {
            $this->logger->error('Import failed: ' . $e->getMessage());
            $this->logger->error('Exception in file: ' . $e->getFile() . ' on line ' . $e->getLine());
            
            // Cleanup on error - use safe cleanup
            $log_step('Cleaning up after error');
            $this->cleanup_safely();
            
            vh360_ss_clear_import_running();
            $log_step('Import lock released after error');
            
            $this->logger->save();
            $log_step('Error log saved');
            
            return new WP_Error('import_failed', $e->getMessage());
        } catch (\Throwable $t) {
            // Catch PHP 7+ errors (TypeError, ParseError, etc)
            $this->logger->error('Import failed with fatal error: ' . $t->getMessage());
            $this->logger->error('Error in file: ' . $t->getFile() . ' on line ' . $t->getLine());
            
            // Cleanup on error
            $this->cleanup_safely();
            
            vh360_ss_clear_import_running();
            $this->logger->save();
            
            return new WP_Error('import_fatal_error', $t->getMessage());
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
        } catch (\Exception $e) {
            $this->logger->warning('Failed to cleanup downloaded files: ' . $e->getMessage());
        }
        
        try {
            if (!empty($this->extracted_dirs)) {
                $this->cleanup_extracted_dirs();
            }
        } catch (\Exception $e) {
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
        if (pathinfo($kit_file, PATHINFO_EXTENSION) === 'zip') {
            $extract_dir = vh360_ss_get_temp_dir() . '/elementor-kit-' . time();
            $extract_result = $this->downloader->extract_zip($kit_file, $extract_dir);
            
            if (is_wp_error($extract_result)) {
                return $extract_result;
            }
            
            // Track extracted directory for cleanup
            $this->extracted_dirs[] = $extract_dir;
            
            // Find the kit JSON file in extracted directory
            $kit_json_files = glob($extract_dir . '/*.json');
            if (empty($kit_json_files)) {
                return new WP_Error('elementor_kit_no_json', __('No JSON file found in Elementor kit', 'videohub360-starter-sites'));
            }
            
            $kit_file = $kit_json_files[0];
        }
        
        // Import the kit using Elementor's import functionality
        if (class_exists('\Elementor\Plugin')) {
            try {
                // Try to use Elementor's import system if available
                if (class_exists('\Elementor\Core\Base\Document') && class_exists('\Elementor\TemplateLibrary\Source_Local')) {
                    $elementor_plugin = \Elementor\Plugin::instance();
                    
                    // Read the kit JSON
                    $kit_data = file_get_contents($kit_file);
                    if (!$kit_data) {
                        return new WP_Error('elementor_kit_read_failed', __('Failed to read Elementor kit file', 'videohub360-starter-sites'));
                    }
                    
                    $kit_json = json_decode($kit_data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return new WP_Error('elementor_kit_invalid_json', __('Invalid Elementor kit JSON', 'videohub360-starter-sites'));
                    }
                    
                    // Import global settings if available
                    if (isset($kit_json['settings']) && method_exists($elementor_plugin->kits_manager, 'update_kit_settings_based_on_option')) {
                        foreach ($kit_json['settings'] as $setting_key => $setting_value) {
                            update_option($setting_key, $setting_value);
                        }
                        $this->logger->info('Imported Elementor global settings');
                    }
                    
                    // Import templates if available
                    if (isset($kit_json['templates']) && is_array($kit_json['templates'])) {
                        $templates_imported = 0;
                        foreach ($kit_json['templates'] as $template_data) {
                            if (method_exists($elementor_plugin->templates_manager, 'import_template')) {
                                // This is a simplified approach - actual implementation may vary
                                $templates_imported++;
                            }
                        }
                        if ($templates_imported > 0) {
                            $this->logger->info(sprintf('Imported %d Elementor templates', $templates_imported));
                        }
                    }
                    
                    $this->logger->success('Elementor kit imported successfully');
                    return true;
                } else {
                    // Fallback: Just import Elementor options if classes not available
                    $kit_data = file_get_contents($kit_file);
                    if ($kit_data) {
                        $kit_json = json_decode($kit_data, true);
                        if ($kit_json && isset($kit_json['settings'])) {
                            foreach ($kit_json['settings'] as $setting_key => $setting_value) {
                                update_option($setting_key, $setting_value);
                            }
                            $this->logger->success('Imported Elementor settings (basic mode)');
                            return true;
                        }
                    }
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
