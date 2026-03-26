<?php
/**
 * Helper Functions for Starter Sites
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get temp directory for demo imports
 *
 * @return string Full path to temp directory
 */
function vh360_ss_get_temp_dir() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/vh360-starter-sites-temp';
}

/**
 * Get demo registry URL
 *
 * @return string Registry URL
 */
function vh360_ss_get_registry_url() {
    return apply_filters('vh360_ss_registry_url', 'https://demos.videohub360.com/registry.json');
}

/**
 * Check if Elementor is active and meets minimum version
 *
 * @param string $min_version Minimum required version
 * @return bool True if requirements met
 */
function vh360_ss_check_elementor($min_version = '3.0.0') {
    if (!defined('ELEMENTOR_VERSION')) {
        return false;
    }
    
    return version_compare(ELEMENTOR_VERSION, $min_version, '>=');
}

/**
 * Check if a plugin is active
 *
 * @param string $plugin_slug Plugin slug
 * @return bool True if active
 */
function vh360_ss_is_plugin_active($plugin_slug) {
    if (!function_exists('is_plugin_active')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Check common plugin file patterns
    $plugin_files = array(
        $plugin_slug . '/' . $plugin_slug . '.php',
        $plugin_slug . '/plugin.php',
        $plugin_slug . '/index.php',
    );
    
    foreach ($plugin_files as $plugin_file) {
        if (is_plugin_active($plugin_file)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format file size for display
 *
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function vh360_ss_format_bytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Clean up temp files older than specified time
 *
 * @param int $max_age Maximum age in seconds (default 24 hours)
 * @return int Number of files deleted
 */
function vh360_ss_cleanup_old_temp_files($max_age = 86400) {
    $temp_dir = vh360_ss_get_temp_dir();
    $deleted = 0;
    
    if (!is_dir($temp_dir)) {
        return $deleted;
    }
    
    $files = glob($temp_dir . '/*');
    if (!$files) {
        return $deleted;
    }
    
    $current_time = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $file_age = $current_time - filemtime($file);
            if ($file_age > $max_age) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
    }
    
    return $deleted;
}

/**
 * Get allowed theme options for import
 * Only these options will be imported from demo packages
 *
 * @return array Allowed option keys
 */
function vh360_ss_get_allowed_theme_options() {
    return apply_filters('vh360_ss_allowed_theme_options', array(
        // Appearance options
        'vh360_appearance_options' => array(
            'site_layout',
            'content_width',
            'sidebar_position',
            'enable_breadcrumbs',
            'enable_back_to_top',
            'gallery_columns',
            'gallery_spacing',
        ),
        
        // Profile display options (non-sensitive)
        'vh360_profile_options' => array(
            'show_profile_stats',
            'show_follow_button',
            'show_channel_link',
            'profile_layout',
        ),
        
        // Activity feed display options
        'vh360_activity_options' => array(
            'items_per_page',
            'show_load_more',
            'enable_infinite_scroll',
            'show_activity_filters',
        ),
        
        // Members directory display options
        'vh360_members_options' => array(
            'members_per_page',
            'default_sort',
            'show_search',
            'show_filters',
        ),
    ));
}

/**
 * Sanitize demo ID
 *
 * @param string $demo_id Demo ID
 * @return string Sanitized demo ID
 */
function vh360_ss_sanitize_demo_id($demo_id) {
    return sanitize_key($demo_id);
}

/**
 * Check if import is in progress
 *
 * @return bool True if import is running
 */
function vh360_ss_is_import_running() {
    return get_transient('vh360_ss_import_in_progress') !== false;
}

/**
 * Set import in progress flag
 *
 * @param string $demo_id Demo ID being imported
 * @return bool True on success
 */
function vh360_ss_set_import_running($demo_id) {
    return set_transient('vh360_ss_import_in_progress', $demo_id, 3600); // 1 hour max
}

/**
 * Clear import in progress flag
 *
 * @return bool True on success
 */
function vh360_ss_clear_import_running() {
    return delete_transient('vh360_ss_import_in_progress');
}

/**
 * Get PHP memory limit in bytes
 *
 * @return int Memory limit in bytes
 */
function vh360_ss_get_memory_limit() {
    $memory_limit = ini_get('memory_limit');
    
    if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
        if ($matches[2] == 'M') {
            return $matches[1] * 1024 * 1024;
        } elseif ($matches[2] == 'K') {
            return $matches[1] * 1024;
        } elseif ($matches[2] == 'G') {
            return $matches[1] * 1024 * 1024 * 1024;
        }
    }
    
    return intval($memory_limit);
}

/**
 * Check if server meets minimum requirements for import
 *
 * @return array Array with 'passed' boolean and 'errors' array
 */
function vh360_ss_check_server_requirements() {
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('PHP version 7.4 or higher is required. Current version: %s', 'videohub360-starter-sites'),
            PHP_VERSION
        );
    }
    
    // Check memory limit
    $memory_limit = vh360_ss_get_memory_limit();
    $min_memory = 256 * 1024 * 1024; // 256MB
    
    if ($memory_limit > 0 && $memory_limit < $min_memory) {
        $errors[] = sprintf(
            __('PHP memory limit should be at least 256MB. Current limit: %s', 'videohub360-starter-sites'),
            vh360_ss_format_bytes($memory_limit)
        );
    }
    
    // Check max execution time
    $max_execution_time = ini_get('max_execution_time');
    if ($max_execution_time > 0 && $max_execution_time < 300) {
        $errors[] = sprintf(
            __('PHP max execution time should be at least 300 seconds. Current limit: %d seconds', 'videohub360-starter-sites'),
            $max_execution_time
        );
    }
    
    // Check if temp directory is writable
    $temp_dir = vh360_ss_get_temp_dir();
    if (!is_writable($temp_dir)) {
        $errors[] = sprintf(
            __('Temp directory is not writable: %s', 'videohub360-starter-sites'),
            $temp_dir
        );
    }
    
    // Check if WP filesystem is available
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    if (!WP_Filesystem()) {
        $errors[] = __('WordPress filesystem could not be initialized', 'videohub360-starter-sites');
    }
    
    return array(
        'passed' => empty($errors),
        'errors' => $errors,
    );
}
