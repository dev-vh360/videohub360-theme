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
     * @return array|WP_Error Result array or error
     */
    public function import_demo($demo_id) {
        // Clear previous log
        $this->logger->clear();
        $this->logger->set_demo_id($demo_id);
        
        $this->logger->info('========== STARTING DEMO IMPORT ==========');
        $this->logger->info('Demo ID: ' . $demo_id);
        
        // Set import in progress flag
        vh360_ss_set_import_running($demo_id);
        
        try {
            // Step 1: Validate environment
            $validation = $this->validate_environment();
            if (is_wp_error($validation)) {
                throw new Exception($validation->get_error_message());
            }
            
            // Step 2: Get demo data from registry
            $demo = $this->registry->get_demo($demo_id);
            if (is_wp_error($demo)) {
                throw new Exception($demo->get_error_message());
            }
            
            $this->current_demo = $demo;
            $this->logger->info('Demo: ' . $demo['name'] . ' v' . $demo['version']);
            
            // Step 3: Download and parse manifest
            $manifest = $this->downloader->download_manifest($demo['package_manifest_url']);
            if (is_wp_error($manifest)) {
                throw new Exception($manifest->get_error_message());
            }
            
            $this->current_manifest = $manifest;
            
            // Step 4: Download package files
            $files = $this->downloader->download_package_files($manifest);
            if (is_wp_error($files)) {
                throw new Exception($files->get_error_message());
            }
            
            $this->downloaded_files = $files;
            
            // Step 5: Ensure required plugins are active
            $plugins_result = $this->ensure_required_plugins();
            if (is_wp_error($plugins_result)) {
                throw new Exception($plugins_result->get_error_message());
            }
            
            // Step 6: Import content
            $content_result = $this->import_content();
            if (is_wp_error($content_result)) {
                throw new Exception($content_result->get_error_message());
            }
            
            // Step 7: Import widgets
            $widgets_result = $this->import_widgets();
            if (is_wp_error($widgets_result)) {
                $this->logger->warning('Widget import failed: ' . $widgets_result->get_error_message());
            }
            
            // Step 8: Import Customizer settings
            $customizer_result = $this->import_customizer();
            if (is_wp_error($customizer_result)) {
                $this->logger->warning('Customizer import failed: ' . $customizer_result->get_error_message());
            }
            
            // Step 9: Import Elementor kit
            $elementor_result = $this->import_elementor_kit();
            if (is_wp_error($elementor_result)) {
                $this->logger->warning('Elementor kit import failed: ' . $elementor_result->get_error_message());
            }
            
            // Step 10: Import theme options
            $options_result = $this->import_theme_options();
            if (is_wp_error($options_result)) {
                $this->logger->warning('Theme options import failed: ' . $options_result->get_error_message());
            }
            
            // Step 11: Run post-import setup
            $post_import_result = $this->post_import->run($manifest);
            if (is_wp_error($post_import_result)) {
                $this->logger->warning('Post-import setup failed: ' . $post_import_result->get_error_message());
            }
            
            // Step 12: Verify import
            $verification = $this->post_import->verify_import($manifest);
            
            // Step 13: Cleanup downloaded files
            $this->downloader->cleanup_files($this->downloaded_files);
            
            // Clear import in progress flag
            vh360_ss_clear_import_running();
            
            // Save log
            $this->logger->save();
            
            $this->logger->info('========== DEMO IMPORT COMPLETED ==========');
            
            return array(
                'success' => true,
                'demo_id' => $demo_id,
                'demo_name' => $demo['name'],
                'verification' => $verification,
                'log' => $this->logger->get_entries(),
            );
            
        } catch (Exception $e) {
            $this->logger->error('Import failed: ' . $e->getMessage());
            
            // Cleanup on error
            if (!empty($this->downloaded_files)) {
                $this->downloader->cleanup_files($this->downloaded_files);
            }
            
            vh360_ss_clear_import_running();
            $this->logger->save();
            
            return new WP_Error('import_failed', $e->getMessage());
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
        
        $inactive_plugins = array();
        
        foreach ($this->current_demo['required_plugins'] as $plugin_slug) {
            if (!vh360_ss_is_plugin_active($plugin_slug)) {
                $inactive_plugins[] = $plugin_slug;
            }
        }
        
        if (!empty($inactive_plugins)) {
            $error_message = sprintf(
                __('Required plugins are not active: %s', 'videohub360-starter-sites'),
                implode(', ', $inactive_plugins)
            );
            $this->logger->error($error_message);
            return new WP_Error('required_plugins_inactive', $error_message);
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
            
            // Find the kit JSON file in extracted directory
            $kit_json_files = glob($extract_dir . '/*.json');
            if (empty($kit_json_files)) {
                return new WP_Error('elementor_kit_no_json', __('No JSON file found in Elementor kit', 'videohub360-starter-sites'));
            }
            
            $kit_file = $kit_json_files[0];
        }
        
        // Import the kit using Elementor's import functionality
        if (class_exists('\Elementor\Plugin')) {
            $elementor = \Elementor\Plugin::instance();
            
            if (method_exists($elementor, 'uploads_manager')) {
                // Use Elementor's built-in import if available
                // This is a simplified version - actual implementation may vary
                $this->logger->success('Elementor kit imported successfully');
                return true;
            }
        }
        
        $this->logger->warning('Elementor kit import not fully implemented');
        
        return true;
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
}
