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
    return apply_filters('vh360_ss_registry_url', 'https://videohub360.com/registry.json');
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
 * Get plugin file path for known bundled plugins
 *
 * @param string $plugin_slug Plugin slug
 * @return string|false Plugin file path or false if not found
 */
function vh360_ss_get_plugin_file($plugin_slug) {
    // Explicit mapping for known VH360 bundled plugins
    // Note: videohub360-core.zip installs to videohub360/ directory
    $plugin_files = array(
        'videohub360' => 'videohub360/videohub360.php',
        'videohub360-core' => 'videohub360/videohub360.php',
        'videohub360-studio' => 'videohub360-studio/videohub360-studio.php',
        'videohub360-community' => 'videohub360-community/videohub360-community.php',
        'vh360-pwa-app' => 'vh360-pwa-app/vh360-pwa-app.php',
        'videohub360-memberships' => 'videohub360-memberships/videohub360-memberships.php',
        'videohub360-starter-sites' => 'videohub360-starter-sites/videohub360-starter-sites.php',
        'elementor' => 'elementor/elementor.php',
        'contact-form-7' => 'contact-form-7/wp-contact-form-7.php',
        'woocommerce' => 'woocommerce/woocommerce.php',
    );
    
    // Check if we have an explicit mapping
    if (isset($plugin_files[$plugin_slug])) {
        return $plugin_files[$plugin_slug];
    }
    
    // Fallback: try common patterns
    $patterns = array(
        $plugin_slug . '/' . $plugin_slug . '.php',
        $plugin_slug . '/plugin.php',
        $plugin_slug . '/index.php',
    );
    
    foreach ($patterns as $pattern) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $pattern)) {
            return $pattern;
        }
    }
    
    return false;
}


/**
 * Get platform-required plugin slugs for every Starter Site import.
 *
 * @return array Plugin slugs in dependency order.
 */
function vh360_ss_get_platform_required_plugins() {
    return array(
        'videohub360',
        'videohub360-studio',
    );
}

/**
 * Merge platform requirements with demo-declared requirements.
 *
 * @param array $demo_plugins Demo required plugin slugs.
 * @return array Effective required plugin slugs in install/activation order.
 */
function vh360_ss_get_effective_required_plugins( $demo_plugins ) {
    $plugins = array_merge(
        vh360_ss_get_platform_required_plugins(),
        (array) $demo_plugins
    );

    $plugins = array_map(
        'sanitize_key',
        $plugins
    );

    return array_values(
        array_unique(
            array_filter( $plugins )
        )
    );
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
    
    $plugin_file = vh360_ss_get_plugin_file($plugin_slug);
    
    if ($plugin_file && is_plugin_active($plugin_file)) {
        return true;
    }
    
    return false;
}

/**
 * Check if a plugin is installed but not active
 *
 * @param string $plugin_slug Plugin slug
 * @return bool True if installed but inactive
 */
function vh360_ss_is_plugin_installed($plugin_slug) {
    $plugin_file = vh360_ss_get_plugin_file($plugin_slug);
    
    if ($plugin_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        return true;
    }
    
    return false;
}

/**
 * Activate a plugin
 *
 * @param string $plugin_slug Plugin slug
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function vh360_ss_activate_plugin($plugin_slug) {
    if (!function_exists('activate_plugin')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $plugin_file = vh360_ss_get_plugin_file($plugin_slug);
    
    if (!$plugin_file) {
        return new WP_Error('plugin_not_found', sprintf(__('Plugin file not found for: %s', 'videohub360-starter-sites'), $plugin_slug));
    }
    
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        return new WP_Error('plugin_not_installed', sprintf(__('Plugin not installed: %s', 'videohub360-starter-sites'), $plugin_slug));
    }
    
    $result = activate_plugin($plugin_file);
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    return true;
}

/**
 * Get bundled plugin ZIP path if it exists
 *
 * @param string $plugin_slug Plugin slug
 * @return string|false Path to ZIP file or false if not found
 */
function vh360_ss_get_bundled_plugin_path($plugin_slug) {
    // Map plugin slugs to ZIP filenames
    $bundled_plugins = array(
        'videohub360' => 'videohub360-core.zip',
        'videohub360-core' => 'videohub360-core.zip',
        'videohub360-studio' => 'videohub360-studio.zip',
        'videohub360-community' => 'videohub360-community.zip',
        'vh360-pwa-app' => 'vh360-pwa-app.zip',
        'videohub360-memberships' => 'videohub360-memberships.zip',
        'videohub360-starter-sites' => 'videohub360-starter-sites.zip',
    );
    
    if (!isset($bundled_plugins[$plugin_slug])) {
        return false;
    }
    
    $zip_path = get_template_directory() . '/bundled-plugins/' . $bundled_plugins[$plugin_slug];
    
    if (file_exists($zip_path)) {
        return $zip_path;
    }
    
    return false;
}

/**
 * Check if a plugin is bundled with the theme
 *
 * @param string $plugin_slug Plugin slug
 * @return bool True if bundled
 */
function vh360_ss_is_bundled_plugin($plugin_slug) {
    return vh360_ss_get_bundled_plugin_path($plugin_slug) !== false;
}

/**
 * Install a bundled plugin from ZIP file
 *
 * @param string $plugin_slug Plugin slug
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function vh360_ss_install_bundled_plugin($plugin_slug) {
    $zip_path = vh360_ss_get_bundled_plugin_path($plugin_slug);
    
    if (!$zip_path) {
        return new WP_Error('not_bundled', sprintf(__('Plugin is not bundled: %s', 'videohub360-starter-sites'), $plugin_slug));
    }
    
    if (!file_exists($zip_path)) {
        return new WP_Error('zip_not_found', sprintf(__('Bundled plugin ZIP not found: %s', 'videohub360-starter-sites'), $zip_path));
    }
    
    // Load required WordPress classes
    if (!class_exists('Plugin_Upgrader')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }
    
    if (!class_exists('WP_Ajax_Upgrader_Skin')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
    }
    
    // Use WP_Ajax_Upgrader_Skin for silent installation
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);
    
    // Install the plugin
    $result = $upgrader->install($zip_path);
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    if (!$result) {
        return new WP_Error('install_failed', sprintf(__('Failed to install plugin: %s', 'videohub360-starter-sites'), $plugin_slug));
    }
    
    // Clear plugin cache
    wp_cache_delete('plugins', 'plugins');
    
    return true;
}

/**
 * Install a plugin from WordPress.org repository
 * 
 * Fetches plugin information from the WordPress.org API and installs it using
 * the standard WordPress Plugin_Upgrader class.
 *
 * @param string $plugin_slug Plugin slug (e.g., 'elementor', 'contact-form-7')
 * @return bool|WP_Error True on success, WP_Error on failure
 * 
 * Possible WP_Error codes:
 * - 'plugin_api_failed': Failed to retrieve plugin info from WordPress.org API
 * - 'install_failed': Plugin installation failed (upgrader returned false)
 * - Other error codes from Plugin_Upgrader::install() (e.g., 'folder_exists', 'incompatible_archive')
 */
function vh360_ss_install_repository_plugin($plugin_slug) {
    // Load required WordPress classes
    if (!class_exists('Plugin_Upgrader')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }
    
    if (!class_exists('WP_Ajax_Upgrader_Skin')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
    }
    
    if (!function_exists('plugins_api')) {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    }
    
    // Get plugin info from WordPress.org API
    // Exclude only non-essential fields to minimize API response size
    // Keep compatibility fields (requires, tested) for validation
    $api = plugins_api('plugin_information', array(
        'slug' => $plugin_slug,
        'fields' => array(
            'sections' => false,
            'short_description' => false,
            'description' => false,
            'rating' => false,
            'ratings' => false,
            'downloaded' => false,
            'last_updated' => false,
            'added' => false,
            'tags' => false,
            'homepage' => false,
            'donate_link' => false,
            'banners' => false,
            'icons' => false,
            // Keep: requires, tested, compatibility, download_link
        ),
    ));
    
    if (is_wp_error($api)) {
        return new WP_Error('plugin_api_failed', sprintf(__('Failed to retrieve plugin info from WordPress.org: %s', 'videohub360-starter-sites'), $api->get_error_message()));
    }
    
    // Use WP_Ajax_Upgrader_Skin for silent installation
    $skin = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);
    
    // Install the plugin
    $result = $upgrader->install($api->download_link);
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    if (!$result) {
        return new WP_Error('install_failed', sprintf(__('Failed to install plugin from WordPress.org: %s', 'videohub360-starter-sites'), $plugin_slug));
    }
    
    // Clear plugin cache
    wp_cache_delete('plugins', 'plugins');
    
    return true;
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
 * Returns a mapping of JSON group names to WordPress option names with their allowed keys.
 * The JSON structure uses simple group names (appearance, profile, etc.) while WordPress
 * stores them with the vh360_ prefix (vh360_appearance_options, etc.).
 *
 * @return array Allowed option keys mapping: JSON_group_name => [wp_option_name, [allowed_keys]]
 */
function vh360_ss_get_allowed_theme_options() {
    return apply_filters('vh360_ss_allowed_theme_options', array(
        // Appearance options
        'appearance' => array(
            'option_name' => 'vh360_appearance_options',
            'allowed_keys' => array(
                'enable_profiles',
                'enable_bulletins',
                'enable_activity',
                'enable_members',
                'enable_user_menu',
                'custom_css',
                'enable_minification',
                'enable_lazy_loading',
                'enable_high_quality_images',
                'site_layout',
                'content_width',
                'sidebar_position',
                'enable_breadcrumbs',
                'enable_back_to_top',
                'gallery_columns',
                'gallery_spacing',
            ),
        ),
        
        // Profile display options (non-sensitive)
        'profile' => array(
            'option_name' => 'vh360_profile_options',
            'allowed_keys' => array(
                // Core toggles
                'enable_profiles',
                'show_avatar',
                'show_cover',
                'show_social',
                'show_stats',
                'show_header_follow_button',
                
                // Avatar cropper
                'enable_avatar_cropper',
                
                // Social platforms
                'social_platforms',
                
                // File size limits
                'avatar_max_size',
                'cover_max_size',
                
                // Avatar processing settings
                'avatar_output_size',
                'avatar_min_width',
                'avatar_min_height',
                'avatar_quality',
                'avatar_allowed_types',
            ),
        ),
        
        // Activity feed display options
        'activity' => array(
            'option_name' => 'vh360_activity_options',
            'allowed_keys' => array(
                'enable_tracking',
                'track_types',
                'retention_days',
                'per_page',
                'items_per_page',
                'show_load_more',
                'enable_infinite_scroll',
                'show_activity_filters',
            ),
        ),
        
        // Members directory display options
        'members' => array(
            'option_name' => 'vh360_members_options',
            'allowed_keys' => array(
                // Core toggles
                'enable_directory',
                'enable_search',
                'show_card_stats',
                'show_card_follow_button',
                'enable_category_filter',
                
                // Display settings
                'per_page',
                'default_sort',
                
                // Visibility & access
                'directory_audience',
                'visible_roles',
                
                // Professional accounts
                'professionals_account_types',
                'professionals_require_approval',
                
                // Categories
                'member_categories',
            ),
        ),

        // Safe, portable membership configuration (excludes payment credentials and operational data)
        'membership' => array(
            'option_name' => 'vh360_membership_options',
            'allowed_keys' => array(
                'enable_memberships',
                'pricing_page_url',
                'support_url',
                'contact_url',
                'course_purchase_destination',
                'login_required',
                'locked_message',
                'reminder_days',
                'grace_period_days',
                'gate_live_rooms',
                'gate_create_videos',
                'gate_create_posts',
                'gate_create_events',
                'gate_create_bulletins',
                'gate_create_galleries',
                'gate_direct_messages',
                'gate_activity_feed',
                'gate_members_directory',
                'gate_appointments',
                'gate_push_notifications',
            ),
        ),
        
        // Advanced options
        'advanced' => array(
            'option_name' => 'vh360_advanced_options',
            'allowed_keys' => array(
                'debug_mode',
                'enable_logging',
                'show_deprecated',
                'enable_custom_css',
                'custom_css',
                'enable_custom_js',
                'custom_js',
                'enable_maintenance_mode',
                'maintenance_message',
            ),
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
    return false !== vh360_ss_get_import_lock();
}

/**
 * Get the current import lock, removing it when it has expired.
 *
 * The option is used instead of a transient value so that add_option() can be
 * used as the single atomic lock-acquisition operation. The expires_at value
 * provides the same stale-lock fallback as the previous one-hour transient.
 *
 * @return array|false Lock data, or false when no import is running.
 */
function vh360_ss_get_import_lock() {
    $lock = get_option('vh360_ss_import_in_progress', false);

    if (is_array($lock) && !empty($lock['expires_at'])) {
        if ((int) $lock['expires_at'] > time()) {
            return $lock;
        }

        delete_option('vh360_ss_import_in_progress');
    }

    // Respect locks created by an older plugin version during an upgrade.
    $legacy_demo_id = get_transient('vh360_ss_import_in_progress');
    if (false !== $legacy_demo_id) {
        return array(
            'demo_id'   => sanitize_key($legacy_demo_id),
            'request_id' => '',
            'started_at' => 0,
            'expires_at' => time() + 3600,
        );
    }

    return false;
}

/**
 * Atomically attempt to acquire the import lock.
 *
 * @param string $demo_id   Demo ID being imported.
 * @param string $request_id Unique ID for this intentional import request.
 * @return string One of acquired, already_running_same_request, or busy.
 */
function vh360_ss_acquire_import_lock($demo_id, $request_id) {
    $demo_id = sanitize_key($demo_id);
    $request_id = sanitize_text_field($request_id);
    $lock = vh360_ss_get_import_lock();

    if (false !== $lock) {
        if ($demo_id === $lock['demo_id'] && $request_id === $lock['request_id'] && '' !== $request_id) {
            return 'already_running_same_request';
        }

        return 'busy';
    }

    $started_at = time();
    $new_lock = array(
        'demo_id'    => $demo_id,
        'request_id' => $request_id,
        'started_at' => $started_at,
        'expires_at' => $started_at + 3600,
    );

    if (add_option('vh360_ss_import_in_progress', $new_lock, '', false)) {
        return 'acquired';
    }

    // Another request won the add_option() race. Identify its ownership.
    $lock = vh360_ss_get_import_lock();
    if (is_array($lock) && $demo_id === $lock['demo_id'] && $request_id === $lock['request_id'] && '' !== $request_id) {
        return 'already_running_same_request';
    }

    return 'busy';
}

/**
 * Release the import lock only when the caller owns it.
 *
 * @param string $request_id Request ID that acquired the lock.
 * @return bool True when the owned lock was released.
 */
function vh360_ss_release_import_lock($request_id) {
    $lock = get_option('vh360_ss_import_in_progress', false);

    if (!is_array($lock) || empty($lock['request_id']) || $lock['request_id'] !== $request_id) {
        return false;
    }

    return delete_option('vh360_ss_import_in_progress');
}

/**
 * Build the transient key for request-specific import recovery data.
 *
 * @param string $request_id Import request ID.
 * @return string Transient key.
 */
function vh360_ss_get_import_request_status_key($request_id) {
    return 'vh360_ss_import_request_' . hash('sha256', sanitize_text_field($request_id));
}

/**
 * Mark an import request as running.
 *
 * @param string $request_id Import request ID.
 * @param string $demo_id Demo ID.
 * @return bool True when the state was stored.
 */
function vh360_ss_set_import_request_running($request_id, $demo_id) {
    $now = time();

    return set_transient(
        vh360_ss_get_import_request_status_key($request_id),
        array(
            'request_id' => sanitize_text_field($request_id),
            'demo_id'    => sanitize_key($demo_id),
            'status'     => 'running',
            'started_at' => $now,
            'updated_at' => $now,
            'result'     => null,
        ),
        30 * MINUTE_IN_SECONDS
    );
}

/**
 * Store a terminal result for an import request without overwriting completion.
 *
 * @param string $request_id Import request ID.
 * @param string $demo_id Demo ID.
 * @param string $status completed or failed.
 * @param array  $result Existing AJAX result data.
 * @return bool True when the state was stored.
 */
function vh360_ss_set_import_request_terminal($request_id, $demo_id, $status, $result) {
    $existing = vh360_ss_get_import_request_status($request_id);

    if (is_array($existing) && 'completed' === $existing['status']) {
        return false;
    }

    $now = time();
    $started_at = is_array($existing) && !empty($existing['started_at']) ? (int) $existing['started_at'] : $now;

    return set_transient(
        vh360_ss_get_import_request_status_key($request_id),
        array(
            'request_id'   => sanitize_text_field($request_id),
            'demo_id'      => sanitize_key($demo_id),
            'status'       => $status,
            'started_at'   => $started_at,
            'completed_at' => $now,
            'updated_at'   => $now,
            'result'       => is_array($result) ? $result : array(),
        ),
        30 * MINUTE_IN_SECONDS
    );
}

/**
 * Mark an import request as completed.
 *
 * @param string $request_id Import request ID.
 * @param string $demo_id Demo ID.
 * @param array  $result Successful importer response.
 * @return bool True when stored.
 */
function vh360_ss_set_import_request_completed($request_id, $demo_id, $result) {
    return vh360_ss_set_import_request_terminal($request_id, $demo_id, 'completed', $result);
}

/**
 * Mark an import request as failed.
 *
 * @param string $request_id Import request ID.
 * @param string $demo_id Demo ID.
 * @param array  $result Sanitized error response.
 * @return bool True when stored.
 */
function vh360_ss_set_import_request_failed($request_id, $demo_id, $result) {
    return vh360_ss_set_import_request_terminal($request_id, $demo_id, 'failed', $result);
}

/**
 * Get recovery state for an exact import request.
 *
 * @param string $request_id Import request ID.
 * @return array|false Request state or false when it expired/does not exist.
 */
function vh360_ss_get_import_request_status($request_id) {
    return get_transient(vh360_ss_get_import_request_status_key($request_id));
}

/**
 * Delete recovery state for an import request.
 *
 * @param string $request_id Import request ID.
 * @return bool True when deleted.
 */
function vh360_ss_delete_import_request_status($request_id) {
    return delete_transient(vh360_ss_get_import_request_status_key($request_id));
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
