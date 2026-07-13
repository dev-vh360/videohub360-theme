<?php
/**
 * Post-Import Setup Class
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_Post_Import {
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_Post_Import
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var VH360_Demo_Logger
     */
    private $logger;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_Post_Import
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
    }
    
    /**
     * Run all post-import setup tasks
     *
     * @param array $manifest Manifest data
     * @param array $imported_data Data from import process
     * @return bool|WP_Error True on success, error on failure
     */
    public function run($manifest, $imported_data = array()) {
        $this->logger->info('Starting post-import setup');
        
        // Step 1: Assign homepage
        $homepage_result = $this->assign_homepage($manifest);
        if (is_wp_error($homepage_result)) {
            $this->logger->warning('Homepage assignment failed: ' . $homepage_result->get_error_message());
        }
        
        // Step 2: Assign posts page
        $posts_page_result = $this->assign_posts_page($manifest);
        if (is_wp_error($posts_page_result)) {
            $this->logger->warning('Posts page assignment failed: ' . $posts_page_result->get_error_message());
        }
        
        // Step 3: Assign menu locations
        $menus_result = $this->assign_menus($manifest);
        if (is_wp_error($menus_result)) {
            $this->logger->warning('Menu assignment failed: ' . $menus_result->get_error_message());
        }
        
        // Step 4: Flush rewrite rules
        $this->flush_rewrite_rules();
        
        // Step 5: Clear caches and transients
        $this->clear_caches();
        
        // Step 6: Run VH360 initialization hooks
        $this->run_theme_init_hooks();
        
        $this->logger->success('Post-import setup completed');
        
        return true;
    }
    
    /**
     * Assign homepage
     *
     * @param array $manifest Manifest data
     * @return bool|WP_Error True on success, error on failure
     */
    private function assign_homepage($manifest) {
        if (!isset($manifest['post_import']['homepage'])) {
            return new WP_Error('no_homepage_config', __('No homepage configuration in manifest', 'videohub360-starter-sites'));
        }
        
        $homepage_config = $manifest['post_import']['homepage'];
        
        // Find page by slug or title
        $page = null;
        
        if (isset($homepage_config['slug'])) {
            $page = get_page_by_path($homepage_config['slug']);
        } elseif (isset($homepage_config['title'])) {
            $page = get_page_by_title($homepage_config['title']);
        }
        
        if (!$page) {
            return new WP_Error('homepage_not_found', __('Homepage page not found', 'videohub360-starter-sites'));
        }
        
        // Set as front page
        update_option('show_on_front', 'page');
        update_option('page_on_front', $page->ID);
        
        $this->logger->success(sprintf('Assigned homepage: %s (ID: %d)', $page->post_title, $page->ID));
        
        return true;
    }
    
    /**
     * Assign posts page
     *
     * @param array $manifest Manifest data
     * @return bool|WP_Error True on success, error on failure
     */
    private function assign_posts_page($manifest) {
        if (!isset($manifest['post_import']['posts_page'])) {
            // Posts page is optional
            return true;
        }
        
        $posts_page_config = $manifest['post_import']['posts_page'];
        
        // Find page by slug or title
        $page = null;
        
        if (isset($posts_page_config['slug'])) {
            $page = get_page_by_path($posts_page_config['slug']);
        } elseif (isset($posts_page_config['title'])) {
            $page = get_page_by_title($posts_page_config['title']);
        }
        
        if (!$page) {
            return new WP_Error('posts_page_not_found', __('Posts page not found', 'videohub360-starter-sites'));
        }
        
        // Set as posts page
        update_option('page_for_posts', $page->ID);
        
        $this->logger->success(sprintf('Assigned posts page: %s (ID: %d)', $page->post_title, $page->ID));
        
        return true;
    }
    
    /**
     * Assign menu locations
     *
     * @param array $manifest Manifest data
     * @return bool|WP_Error True on success, error on failure
     */
    private function assign_menus($manifest) {
        if (!isset($manifest['post_import']['menus']) || !is_array($manifest['post_import']['menus'])) {
            // Menus are optional
            return true;
        }
        
        $menus_config = $manifest['post_import']['menus'];
        $locations = get_theme_mod('nav_menu_locations', array());
        $assigned_count = 0;
        
        foreach ($menus_config as $location => $menu_name) {
            $menu = wp_get_nav_menu_object($menu_name);
            
            if (!$menu) {
                $this->logger->warning(sprintf('Menu not found: %s', $menu_name));
                continue;
            }
            
            $locations[$location] = $menu->term_id;
            $assigned_count++;
            
            $this->logger->info(sprintf('Assigned menu "%s" to location "%s"', $menu_name, $location));
        }
        
        if ($assigned_count > 0) {
            set_theme_mod('nav_menu_locations', $locations);
            $this->logger->success(sprintf('Assigned %d menu locations', $assigned_count));
        }
        
        return true;
    }
    
    /**
     * Flush rewrite rules
     */
    private function flush_rewrite_rules() {
        flush_rewrite_rules(false);
        $this->logger->info('Flushed rewrite rules');
    }
    
    /**
     * Clear caches and transients
     */
    private function clear_caches() {
        try {
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Clear theme-specific transients
            $transients = array(
                'vh360_ss_demos_cache',
                'vh360_ss_import_in_progress',
            );
            
            foreach ($transients as $transient) {
                delete_transient($transient);
            }
            
            // Clear Elementor cache if active and instance is available
            if (class_exists('\Elementor\Plugin') && 
                isset(\Elementor\Plugin::$instance) && 
                \Elementor\Plugin::$instance !== null &&
                isset(\Elementor\Plugin::$instance->files_manager)) {
                
                \Elementor\Plugin::$instance->files_manager->clear_cache();
                $this->logger->info('Cleared Elementor cache');
            } elseif (class_exists('\Elementor\Plugin')) {
                $this->logger->warning('Elementor is loaded but instance not available for cache clearing');
            }
            
            $this->logger->info('Cleared caches and transients');
            
        } catch (Exception $e) {
            // Cache clearing should never crash a successful import
            $this->logger->warning('Cache clearing failed: ' . $e->getMessage());
        } catch (Throwable $t) {
            // Catch PHP 7+ Error types (TypeError, ParseError, etc) not extending Exception
            $this->logger->warning('Cache clearing failed with error: ' . $t->getMessage());
        }
    }
    
    /**
     * Run VH360 theme initialization hooks
     */
    private function run_theme_init_hooks() {
        // Run theme-specific initialization if function exists
        if (function_exists('vh360_after_demo_import')) {
            do_action('vh360_after_demo_import');
            $this->logger->info('Ran vh360_after_demo_import action');
        }
        
        // Note: Demo imports rely on content.xml for menu creation and manifest.json 
        // for menu location assignment. Default menu creation (vh360_create_default_menus) 
        // should NOT run during demo import as it creates duplicate menus.
        // It only runs on fresh theme activation without demo content.
        
        // Ensure administrator capabilities are set
        if (function_exists('vh360_ensure_administrator_core_caps')) {
            vh360_ensure_administrator_core_caps();
            $this->logger->info('Ensured administrator capabilities');
        }
    }
    
    /**
     * Verify import completeness
     *
     * @param array $manifest Manifest data
     * @return array Array with 'success' boolean and 'issues' array
     */
    public function verify_import($manifest) {
        $issues = array();
        
        // Check if homepage is assigned
        if (get_option('show_on_front') !== 'page' || !get_option('page_on_front')) {
            $issues[] = __('Homepage not properly assigned', 'videohub360-starter-sites');
        }
        
        // Check if required plugins are active
        foreach (vh360_ss_get_effective_required_plugins($manifest['required_plugins'] ?? array()) as $plugin_slug) {
            if (!vh360_ss_is_plugin_active($plugin_slug)) {
                $issues[] = sprintf(__('Required plugin not active: %s', 'videohub360-starter-sites'), $plugin_slug);
            }
        }
        
        // Check if Elementor is active (if required)
        if (isset($manifest['requires_elementor']) && $manifest['requires_elementor']) {
            if (!vh360_ss_check_elementor()) {
                $issues[] = __('Elementor is not active or version is too old', 'videohub360-starter-sites');
            }
        }
        
        $success = empty($issues);
        
        if ($success) {
            $this->logger->success('Import verification passed');
        } else {
            $this->logger->warning('Import verification found issues', $issues);
        }
        
        return array(
            'success' => $success,
            'issues' => $issues,
        );
    }
}
