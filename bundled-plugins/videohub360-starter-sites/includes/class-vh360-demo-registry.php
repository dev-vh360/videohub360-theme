<?php
/**
 * Demo Registry Consumer
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Demo_Registry {
    
    /**
     * Cache expiration time in seconds (12 hours)
     */
    const CACHE_EXPIRATION = 43200;
    
    /**
     * Transient key for cached demos
     */
    const CACHE_KEY = 'vh360_ss_demos_cache';
    
    /**
     * Singleton instance
     *
     * @var VH360_Demo_Registry
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Demo_Registry
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
        // Empty constructor
    }
    
    /**
     * Fetch demos from remote registry
     *
     * @param bool $force_refresh Force refresh cache
     * @return array|WP_Error Array of demos or WP_Error on failure
     */
    public function fetch_demos($force_refresh = false) {
        $logger = VH360_Demo_Logger::get_instance();
        
        // Check cache first unless force refresh
        if (!$force_refresh) {
            $cached = get_transient(self::CACHE_KEY);
            if ($cached !== false) {
                $logger->info('Loaded demos from cache');
                return $cached;
            }
        }
        
        $logger->info('Fetching demos from remote registry');
        
        $registry_url = vh360_ss_get_registry_url();
        
        // Fetch from remote
        $response = wp_remote_get($registry_url, array(
            'timeout' => 15,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            $error_message = sprintf(
                __('Failed to fetch demo registry: %s', 'videohub360-starter-sites'),
                $response->get_error_message()
            );
            $logger->error($error_message);
            return new WP_Error('registry_fetch_failed', $error_message);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = sprintf(
                __('Registry returned non-200 status code: %d', 'videohub360-starter-sites'),
                $response_code
            );
            $logger->error($error_message);
            return new WP_Error('registry_bad_status', $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            $error_message = __('Registry returned empty response', 'videohub360-starter-sites');
            $logger->error($error_message);
            return new WP_Error('registry_empty_response', $error_message);
        }
        
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = sprintf(
                __('Failed to parse registry JSON: %s', 'videohub360-starter-sites'),
                json_last_error_msg()
            );
            $logger->error($error_message);
            return new WP_Error('registry_invalid_json', $error_message);
        }
        
        // Validate registry structure
        $validation = $this->validate_registry_data($data);
        if (is_wp_error($validation)) {
            $logger->error('Registry validation failed: ' . $validation->get_error_message());
            return $validation;
        }
        
        $demos = isset($data['demos']) ? $data['demos'] : array();
        
        // Normalize each demo
        $normalized_demos = array();
        foreach ($demos as $demo) {
            $normalized = $this->normalize_demo($demo);
            if (!is_wp_error($normalized)) {
                $normalized_demos[$normalized['id']] = $normalized;
            } else {
                $logger->warning('Skipped invalid demo: ' . $normalized->get_error_message(), $demo);
            }
        }
        
        if (empty($normalized_demos)) {
            $error_message = __('No valid demos found in registry', 'videohub360-starter-sites');
            $logger->error($error_message);
            return new WP_Error('registry_no_demos', $error_message);
        }
        
        // Cache the results
        set_transient(self::CACHE_KEY, $normalized_demos, self::CACHE_EXPIRATION);
        
        $logger->success(sprintf('Successfully fetched %d demos from registry', count($normalized_demos)));
        
        return $normalized_demos;
    }
    
    /**
     * Validate registry data structure
     *
     * @param array $data Registry data
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    private function validate_registry_data($data) {
        if (!is_array($data)) {
            return new WP_Error('invalid_structure', __('Registry data is not an array', 'videohub360-starter-sites'));
        }
        
        if (!isset($data['demos']) || !is_array($data['demos'])) {
            return new WP_Error('missing_demos', __('Registry missing demos array', 'videohub360-starter-sites'));
        }
        
        return true;
    }
    
    /**
     * Normalize a demo entry
     *
     * @param array $demo Raw demo data
     * @return array|WP_Error Normalized demo or error
     */
    private function normalize_demo($demo) {
        // Required fields
        $required_fields = array('id', 'name', 'package_manifest_url');
        
        foreach ($required_fields as $field) {
            if (empty($demo[$field])) {
                return new WP_Error('missing_field', sprintf(__('Demo missing required field: %s', 'videohub360-starter-sites'), $field));
            }
        }
        
        // Normalize demo structure
        $normalized = array(
            'id' => sanitize_key($demo['id']),
            'name' => sanitize_text_field($demo['name']),
            'label' => isset($demo['label']) ? sanitize_text_field($demo['label']) : sanitize_text_field($demo['name']),
            'description' => isset($demo['description']) ? wp_kses_post($demo['description']) : '',
            'version' => isset($demo['version']) ? sanitize_text_field($demo['version']) : '1.0.0',
            'thumbnail' => isset($demo['thumbnail']) ? esc_url_raw($demo['thumbnail']) : '',
            'preview_url' => isset($demo['preview_url']) ? esc_url_raw($demo['preview_url']) : '',
            'package_manifest_url' => esc_url_raw($demo['package_manifest_url']),
            'required_plugins' => isset($demo['required_plugins']) && is_array($demo['required_plugins']) ? array_map('sanitize_text_field', $demo['required_plugins']) : array(),
            'recommended_plugins' => isset($demo['recommended_plugins']) && is_array($demo['recommended_plugins']) ? array_map('sanitize_text_field', $demo['recommended_plugins']) : array(),
            'min_theme_version' => isset($demo['min_theme_version']) ? sanitize_text_field($demo['min_theme_version']) : '1.0.0',
            'category' => isset($demo['category']) ? sanitize_text_field($demo['category']) : 'general',
            'tags' => isset($demo['tags']) && is_array($demo['tags']) ? array_map('sanitize_text_field', $demo['tags']) : array(),
        );
        
        return $normalized;
    }
    
    /**
     * Get a specific demo by ID
     *
     * @param string $demo_id Demo ID
     * @return array|WP_Error Demo data or error
     */
    public function get_demo($demo_id) {
        $demos = $this->fetch_demos();
        
        if (is_wp_error($demos)) {
            return $demos;
        }
        
        $demo_id = sanitize_key($demo_id);
        
        if (!isset($demos[$demo_id])) {
            return new WP_Error('demo_not_found', __('Demo not found', 'videohub360-starter-sites'));
        }
        
        return $demos[$demo_id];
    }
    
    /**
     * Clear cached demos
     *
     * @return bool True on success
     */
    public function clear_cache() {
        return delete_transient(self::CACHE_KEY);
    }
    
    /**
     * Check if a demo is compatible with current environment
     *
     * @param array $demo Demo data
     * @return array Array with 'compatible' boolean and 'issues' array
     */
    public function check_compatibility($demo) {
        $issues = array();
        
        // Check theme version
        $theme = wp_get_theme();
        if (version_compare($theme->get('Version'), $demo['min_theme_version'], '<')) {
            $issues[] = sprintf(
                __('Theme version %s or higher is required. Current version: %s', 'videohub360-starter-sites'),
                $demo['min_theme_version'],
                $theme->get('Version')
            );
        }
        
        // Check required plugins
        foreach ($demo['required_plugins'] as $plugin_slug) {
            if (!vh360_ss_is_plugin_active($plugin_slug)) {
                $issues[] = sprintf(
                    __('Required plugin not active: %s', 'videohub360-starter-sites'),
                    $plugin_slug
                );
            }
        }
        
        return array(
            'compatible' => empty($issues),
            'issues' => $issues,
        );
    }
}
