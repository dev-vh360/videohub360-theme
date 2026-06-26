<?php
/**
 * Starter Sites Admin Page Handler
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Starter_Sites_Admin {
    
    /**
     * Singleton instance
     *
     * @var VH360_Starter_Sites_Admin
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Starter_Sites_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Add admin menu under VH360 Theme
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vh360-theme',
            __('Starter Sites', 'videohub360-starter-sites'),
            __('Starter Sites', 'videohub360-starter-sites'),
            'manage_options',
            'vh360-starter-sites',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only load on our admin page
        if ($hook !== 'vh360-theme_page_vh360-starter-sites') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'vh360-starter-sites-admin',
            vh360_starter_sites_asset_url('admin/assets/css/starter-sites-admin.css'),
            array(),
            vh360_starter_sites_asset_version('admin/assets/css/starter-sites-admin.css')
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'vh360-starter-sites-admin',
            vh360_starter_sites_asset_url('admin/assets/js/starter-sites-admin.js'),
            array('jquery'),
            vh360_starter_sites_asset_version('admin/assets/js/starter-sites-admin.js'),
            true
        );
        
        // Localize script
        wp_localize_script('vh360-starter-sites-admin', 'vh360StarterSites', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_ss_nonce'),
            'strings' => array(
                'confirmImport' => __('Are you sure you want to import this demo? This will import content, settings, and configurations. Existing content will not be removed.', 'videohub360-starter-sites'),
                'importing' => __('Importing...', 'videohub360-starter-sites'),
                'importSuccess' => __('Demo imported successfully!', 'videohub360-starter-sites'),
                'importError' => __('Import failed. Please check the log for details.', 'videohub360-starter-sites'),
                'loadingDemos' => __('Loading demos...', 'videohub360-starter-sites'),
                'noDemos' => __('No demos available.', 'videohub360-starter-sites'),
                'fetchError' => __('Failed to fetch demos. Please try again.', 'videohub360-starter-sites'),
            ),
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'videohub360-starter-sites'));
        }
        
        // Get import status
        $is_importing = vh360_ss_is_import_running();
        $last_log = VH360_Demo_Logger::get_last_log();
        
        // Include the view
        include VH360_STARTER_SITES_ADMIN . 'views/page-starter-sites.php';
    }
    
    /**
     * Get system status for display
     *
     * @return array System status information
     */
    public static function get_system_status() {
        $requirements = vh360_ss_check_server_requirements();
        
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'theme_version' => wp_get_theme()->get('Version'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'elementor_active' => vh360_ss_check_elementor(),
            'elementor_version' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'N/A',
            'requirements_met' => $requirements['passed'],
            'requirement_errors' => $requirements['errors'],
        );
    }
}

// Initialize admin
VH360_Starter_Sites_Admin::get_instance();
