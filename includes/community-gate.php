<?php
/**
 * Community Access Gate
 * 
 * Redirects non-logged-in users from community/private templates to custom login page.
 * 
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Community template access control
 */
add_action('template_redirect', 'vh360_community_access_gate', 5);
function vh360_community_access_gate() {
    // Skip if user is logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Skip admin, AJAX, REST requests
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    
    // Skip auth pages (prevent redirect loops)
    if (vh360_is_auth_page()) {
        return;
    }
    
    // Check if current page requires authentication
    if (vh360_is_community_template()) {
        // Redirect to custom login with current URL as redirect target
        // Use WordPress functions to safely get current URL
        global $wp;
        $current_url = home_url($wp->request);
        
        // Add query string if present, using WordPress URL construction
        if (!empty($_SERVER['QUERY_STRING'])) {
            // Parse and validate query string to preserve URL integrity
            parse_str(wp_unslash($_SERVER['QUERY_STRING']), $query_params);
            if (!empty($query_params)) {
                $current_url = add_query_arg($query_params, $current_url);
            }
        }
        
        $login_url = vh360_get_login_page_url();
        $redirect_url = add_query_arg('redirect_to', urlencode($current_url), $login_url);
        
        wp_safe_redirect($redirect_url, 302);
        exit;
    }
}

/**
 * Check if current page is an auth page
 */
function vh360_is_auth_page() {
    global $post;
    
    if (!$post) {
        return false;
    }
    
    $auth_templates = array(
        'template-login.php',
        'template-register.php',
        'template-register-business.php',
        'template-register-professional.php',
        'template-register-client.php',
        'template-lost-password.php',
        'template-reset-password.php'
    );
    
    $page_template = get_page_template_slug($post->ID);
    
    return in_array($page_template, $auth_templates);
}

/**
 * Get access control targets registry
 * 
 * Returns array of all templates/pages that can have configurable access control.
 * This is the single source of truth for both admin UI and access gate logic.
 * 
 * @return array Associative array of access control targets with metadata
 * @since 1.3.0
 */
function vh360_get_access_control_targets() {
    $targets = array(
        'dashboard' => array(
            'label'    => __('Dashboard', 'videohub360-theme'),
            'type'     => 'page_template',
            'template' => 'template-dashboard.php',
            'default'  => 1,
        ),
        'profile_edit' => array(
            'label'    => __('Profile Edit', 'videohub360-theme'),
            'type'     => 'page_template',
            'template' => 'template-profile-edit.php',
            'default'  => 1,
        ),
        'members_directory' => array(
            'label'    => __('Members Directory', 'videohub360-theme'),
            'type'     => 'page_template',
            'template' => 'template-members-directory.php',
            'default'  => 0,
        ),
        'activity_feed' => array(
            'label'    => __('Activity Feed', 'videohub360-theme'),
            'type'     => 'page_template',
            'template' => 'template-activity-feed.php',
            'default'  => 1,
        ),
        'author_profiles' => array(
            'label'   => __('Public Profile Pages', 'videohub360-theme'),
            'type'    => 'author_archive',
            'default' => 0,
        ),
    );

    return apply_filters('vh360_access_control_targets', $targets);
}

/**
 * Get template visibility settings
 * 
 * Returns merged settings with defaults. Use this as the single source of truth
 * for template visibility checks throughout the theme.
 * 
 * @return array Associative array of template keys and their access requirements (1 = login required, 0 = public)
 * @since 1.3.0
 */
function vh360_get_template_visibility_settings() {
    $targets = vh360_get_access_control_targets();
    
    // Build defaults from registry
    $defaults = array();
    foreach ($targets as $key => $target) {
        $defaults[$key] = $target['default'] ?? 1;
    }

    $saved = get_option('vh360_access_options', array());

    return wp_parse_args($saved, $defaults);
}

/**
 * Check if a template requires login
 * 
 * Helper function to check if a specific template requires authentication.
 * 
 * @param string $key Template key from the access control registry
 * @return bool True if login is required, false if public
 * @since 1.3.0
 */
function vh360_template_requires_login($key) {
    $settings = vh360_get_template_visibility_settings();
    return !empty($settings[$key]);
}

/**
 * Check if current page is a community/private template
 */
function vh360_is_community_template() {
    global $post;
    
    $access = vh360_get_template_visibility_settings();
    
    // Author/profile pages
    if (is_author()) {
        return !empty($access['author_profiles']);
    }
    
    // Check page templates
    if ($post) {
        $page_template = get_page_template_slug($post->ID);
        
        $template_map = array(
            'template-dashboard.php'         => 'dashboard',
            'template-profile-edit.php'      => 'profile_edit',
            'template-members-directory.php' => 'members_directory',
            'template-activity-feed.php'     => 'activity_feed',
        );
        
        if (isset($template_map[$page_template])) {
            $setting_key = $template_map[$page_template];
            return !empty($access[$setting_key]);
        }
    }
    
    return apply_filters('vh360_is_community_template', false);
}
