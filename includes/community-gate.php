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
        'template-lost-password.php',
        'template-reset-password.php'
    );
    
    $page_template = get_page_template_slug($post->ID);
    
    return in_array($page_template, $auth_templates);
}

/**
 * Check if current page is a community/private template
 */
function vh360_is_community_template() {
    global $post;
    
    // Author/profile pages
    if (is_author()) {
        return true;
    }
    
    // Check page templates
    if ($post) {
        $page_template = get_page_template_slug($post->ID);
        
        $community_templates = array(
            'template-dashboard.php',
            'template-activity-feed.php',
            'template-members-directory.php',
            'template-profile-edit.php',
        );
        
        if (in_array($page_template, $community_templates)) {
            return true;
        }
    }
    
    // Add custom post type checks if needed
    // Example: if (is_singular('vh360_bulletin')) return true;
    
    return apply_filters('vh360_is_community_template', false);
}
