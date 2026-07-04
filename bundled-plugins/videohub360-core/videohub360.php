<?php
/*
Plugin Name: VideoHub360
Plugin URI: https://videohub360.com
Description: Complete video management platform with custom post types, live streaming capabilities, Elementor widget, and chat functionality for WordPress.
Version: 1.0.0
Author: VideoHub360
Author URI: https://videohub360.com
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: videohub360
Domain Path: /languages
Network: false
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('VIDEOHUB360_PLUGIN_FILE', __FILE__);
define('VIDEOHUB360_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIDEOHUB360_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIDEOHUB360_INCLUDES_DIR', VIDEOHUB360_PLUGIN_DIR . 'includes/');
define('VIDEOHUB360_ASSETS_URL', VIDEOHUB360_PLUGIN_URL . 'assets/');
define('VIDEOHUB360_TEMPLATES_DIR', VIDEOHUB360_PLUGIN_DIR . 'templates/');
define('VIDEOHUB360_VERSION', '1.0.0');


/**
 * Get a cache-busting version for a plugin-owned asset.
 *
 * @param string $relative_path Asset path relative to the plugin root.
 * @return string
 */
if (!function_exists('videohub360_asset_version')) {
    function videohub360_asset_version($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        $file_path = VIDEOHUB360_PLUGIN_DIR . $relative_path;

        if (file_exists($file_path)) {
            return VIDEOHUB360_VERSION . '-' . filemtime($file_path);
        }

        return VIDEOHUB360_VERSION;
    }
}

// Load renderer functions
require_once VIDEOHUB360_PLUGIN_DIR . 'includes/renderers/render-chat.php';
require_once VIDEOHUB360_PLUGIN_DIR . 'includes/renderers/render-livestream.php';
require_once VIDEOHUB360_PLUGIN_DIR . 'includes/renderers/render-single-video-sidebar.php';
require_once VIDEOHUB360_PLUGIN_DIR . 'includes/renderers/render-single-video-modals.php';
require_once VIDEOHUB360_PLUGIN_DIR . 'includes/class-videohub360-livestream-service.php';
add_action('init', array('VideoHub360_Livestream_Service', 'register_stale_cleanup'));

// Base URL of the licensing server (store site) running the VideoHub360 Licensing plugin.
// Replace this with your actual store URL before distributing, or override with the
// `videohub360_license_server_url` filter.
if (!defined('VIDEOHUB360_LICENSE_SERVER_URL')) {
    define('VIDEOHUB360_LICENSE_SERVER_URL', 'https://videohub360.com');
}

/**
 * Debug logging helper for VideoHub360 plugin
 * Only logs when WP_DEBUG is enabled
 *
 * @param string $message Log message
 * @param array $context Optional context data
 */
if (!function_exists('videohub360_debug_log')) {
    function videohub360_debug_log($message, $context = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !function_exists('error_log')) {
            return;
        }
        
        if (!empty($context)) {
            $message .= ': ' . print_r($context, true);
        }
        
        error_log($message);
    }
}

/**
 * Load the main plugin class
 */
function videohub360_init() {
    // Load the core class
    require_once VIDEOHUB360_PLUGIN_DIR . 'includes/class-videohub360-core.php';
    
    // Initialize the plugin
    VideoHub360_Core::get_instance();
}

/**
 * Plugin activation hook
 */
function videohub360_activate() {
    // Load the core class for activation
    require_once VIDEOHUB360_PLUGIN_DIR . 'includes/class-videohub360-core.php';
    
    // Run activation
    VideoHub360_Core::activate();
}

/**
 * Plugin deactivation hook  
 */
function videohub360_deactivate() {
    // Load the core class for deactivation
    require_once VIDEOHUB360_PLUGIN_DIR . 'includes/class-videohub360-core.php';
    
    // Run deactivation
    VideoHub360_Core::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'videohub360_activate');
register_deactivation_hook(__FILE__, 'videohub360_deactivate');

// Initialize the plugin
add_action('plugins_loaded', 'videohub360_init');

// Load plugin textdomain for internationalization
add_action('plugins_loaded', function() {
    load_plugin_textdomain('videohub360', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

// Theme compatibility
require_once plugin_dir_path(__FILE__) . 'includes/astra-compatibility.php';

/**
 * Global helper function to build sidebar query
 */
if (!function_exists('videohub360_build_sidebar_query')) {
    function videohub360_build_sidebar_query($post_id) {
        // Try to get the frontend component first
        $core = VideoHub360_Core::get_instance();
        if ($core && isset($core->components['frontend'])) {
            return $core->components['frontend']->build_sidebar_query($post_id);
        }
        
        // Fallback: simplified default query if frontend component not available
        return new WP_Query(array(
            'post_type' => 'videohub360',
            'posts_per_page' => 6,
            'post__not_in' => array($post_id),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }
}

// Global helper function to get sidebar title
if (!function_exists('videohub360_get_sidebar_title')) {
    function videohub360_get_sidebar_title($post_id) {
        $config = get_post_meta($post_id, '_vh360_sidebar_config', true);
        
        // If custom sidebar is enabled and has a custom title
        if (!empty($config) && $config['enable_custom'] === 'yes' && !empty($config['custom_title'])) {
            return esc_html($config['custom_title']);
        }
        
        // Default fallback
        return 'Latest Videos';
    }
}


/**
 * Sanitize VideoHub360 single video layout option values.
 *
 * @param string $value   Raw value.
 * @param array  $allowed Allowed values.
 * @param string $default Default fallback.
 * @return string
 */
if (!function_exists('videohub360_sanitize_single_video_layout_value')) {
    function videohub360_sanitize_single_video_layout_value($value, $allowed = array('sidebar', 'full-width'), $default = 'sidebar') {
        $value = sanitize_key((string) $value);

        if (in_array($value, $allowed, true)) {
            return $value;
        }

        return in_array($default, $allowed, true) ? $default : 'sidebar';
    }
}

/**
 * Resolve the internal VideoHub360 single video layout.
 *
 * Priority order:
 * 1. Per-video override.
 * 2. Course lesson default.
 * 3. Livestream default.
 * 4. Global single video default.
 * 5. Safety fallback.
 *
 * @param int $post_id Optional videohub360 post ID. Defaults to current post.
 * @return string Either 'sidebar' or 'full-width'.
 */
if (!function_exists('videohub360_get_single_video_layout')) {
    function videohub360_get_single_video_layout($post_id = 0) {
        $post_id = absint($post_id ?: get_the_ID());

        if (!$post_id || get_post_type($post_id) !== 'videohub360') {
            return 'sidebar';
        }

        $sidebar_config = get_post_meta($post_id, '_vh360_sidebar_config', true);
        $per_video_layout = '';

        if (is_array($sidebar_config) && isset($sidebar_config['video_layout'])) {
            $per_video_layout = sanitize_key((string) $sidebar_config['video_layout']);
        }

        if (in_array($per_video_layout, array('sidebar', 'full-width'), true)) {
            return $per_video_layout;
        }

        $belongs_to_course = false;
        if (taxonomy_exists('videohub360_series')) {
            $terms = get_the_terms($post_id, 'videohub360_series');
            $belongs_to_course = !empty($terms) && !is_wp_error($terms);
        }

        if (!$belongs_to_course && function_exists('videohub360_get_lesson_course')) {
            $belongs_to_course = (bool) videohub360_get_lesson_course($post_id);
        }

        if ($belongs_to_course) {
            $course_layout = videohub360_sanitize_single_video_layout_value(
                get_option('videohub360_course_lesson_layout_default', 'full-width'),
                array('inherit', 'sidebar', 'full-width'),
                'full-width'
            );

            if (in_array($course_layout, array('sidebar', 'full-width'), true)) {
                return $course_layout;
            }
        }

        $is_livestream = get_post_meta($post_id, '_vh360_is_live', true) === 'yes'
            || get_post_meta($post_id, '_vh360_context', true) === 'live_room';

        if ($is_livestream) {
            $livestream_layout = videohub360_sanitize_single_video_layout_value(
                get_option('videohub360_livestream_video_layout_default', 'full-width'),
                array('inherit', 'sidebar', 'full-width'),
                'full-width'
            );

            if (in_array($livestream_layout, array('sidebar', 'full-width'), true)) {
                return $livestream_layout;
            }
        }

        return videohub360_sanitize_single_video_layout_value(
            get_option('videohub360_single_video_layout_default', 'sidebar'),
            array('sidebar', 'full-width'),
            'sidebar'
        );
    }
}

/**
 * Get profile URL for a user
 * Checks for custom profile post type, otherwise uses author posts URL
 * 
 * Note: Theme has vh360_get_profile_url() which calls this function as fallback,
 * so we must NOT call the theme function to avoid infinite recursion.
 * 
 * @param int $user_id User ID
 * @return string Profile URL
 */
if (!function_exists('videohub360_get_profile_url')) {
    function videohub360_get_profile_url($user_id) {
        if (!$user_id) {
            return '';
        }
        
        // Check for vh360_profile post type (custom profile pages)
        $args = array(
            'post_type' => 'vh360_profile',
            'meta_query' => array(
                array(
                    'key' => '_vh360_profile_user_id',
                    'value' => $user_id,
                ),
            ),
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        $profile_query = new WP_Query($args);
        
        if ($profile_query->have_posts()) {
            $profile_query->the_post();
            $url = get_permalink();
            wp_reset_postdata();
            return $url;
        }
        
        // Fallback to WordPress author posts URL
        return get_author_posts_url($user_id);
    }
}

/**
 * Get avatar URL for a user
 * Uses theme function if available for custom avatar support, otherwise uses get_avatar_url
 * 
 * Theme Integration:
 * If the active theme provides vh360_get_user_avatar_url($user_id, $size), it will be used.
 * The theme function checks for custom uploaded avatars before falling back to WordPress/Gravatar.
 * 
 * @param int $user_id User ID
 * @param int $size Avatar size in pixels
 * @return string Avatar URL
 */
if (!function_exists('videohub360_get_avatar_url')) {
    function videohub360_get_avatar_url($user_id, $size = 48) {
        if (!$user_id) {
            return '';
        }
        
        // Check if theme function exists (safe - theme function doesn't call back)
        if (function_exists('vh360_get_user_avatar_url')) {
            return vh360_get_user_avatar_url($user_id, $size);
        }
        
        // Fallback to WordPress get_avatar_url
        return get_avatar_url($user_id, array('size' => $size));
    }
}

/**
 * Render author badge component
 * Displays author avatar, name, and optional username with link to profile
 * 
 * Note: This function handles all output escaping internally using esc_url(), 
 * esc_attr(), and esc_html(). The returned HTML is safe to echo directly.
 * 
 * @param int $post_id Post ID
 * @param array $args Optional arguments:
 *   - show_avatar (bool): Show avatar image, default true
 *   - show_username (bool): Show @username, default true
 *   - avatar_size (int): Avatar size in pixels, default 44
 *   - variant (string): Display variant (default|compact|name_only), default 'default'
 *   - link (bool): Make it a clickable link, default true
 * @return string HTML markup for author badge
 */
if (!function_exists('videohub360_render_author_badge')) {
    function videohub360_render_author_badge($post_id, $args = array()) {
        // Get post author
        $user_id = (int) get_post_field('post_author', $post_id);
        
        // Return empty if no author
        if (!$user_id) {
            return '';
        }
        
        // Parse arguments with defaults
        $defaults = array(
            'show_avatar' => true,
            'show_username' => true,
            'avatar_size' => 44,
            'variant' => 'default',
            'link' => true,
        );
        $args = wp_parse_args($args, $defaults);
        
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }
        
        $display_name = $user->display_name;
        $username = $user->user_login;
        $profile_url = videohub360_get_profile_url($user_id);
        $avatar_url = videohub360_get_avatar_url($user_id, $args['avatar_size']);
        
        // Build variant class
        $variant_class = '';
        if ($args['variant'] !== 'default') {
            $variant_class = ' vh360-author-row--' . sanitize_html_class($args['variant']);
        }
        
        // Start output buffering
        ob_start();
        
        // Determine tag type
        $tag = $args['link'] ? 'a' : 'div';
        $href_attr = $args['link'] ? ' href="' . esc_url($profile_url) . '"' : '';
        
        ?>
        <<?php echo $tag; ?> class="vh360-author-row<?php echo esc_attr($variant_class); ?>"<?php echo $href_attr; ?>>
            <?php if ($args['show_avatar'] && $args['variant'] !== 'name_only'): ?>
                <img src="<?php echo esc_url($avatar_url); ?>" 
                     alt="<?php echo esc_attr($display_name); ?>" 
                     class="vh360-author-avatar" 
                     width="<?php echo esc_attr($args['avatar_size']); ?>" 
                     height="<?php echo esc_attr($args['avatar_size']); ?>">
            <?php endif; ?>
            <span class="vh360-author-info">
                <span class="vh360-author-name"><?php echo esc_html($display_name); ?></span>
                <?php if ($args['show_username'] && $args['variant'] !== 'name_only'): ?>
                    <span class="vh360-author-username">@<?php echo esc_html($username); ?></span>
                <?php endif; ?>
            </span>
        </<?php echo $tag; ?>>
        <?php
        
        return ob_get_clean();
    }
}

/**
 * Get livestream bootstrap data for JavaScript
 * 
 * Prepares configuration data for Agora livestreams to be localized to JavaScript
 * 
 * @param int $post_id Post ID
 * @param array $fields Livestream fields from post meta
 * @return array|null Configuration array or null if not an Agora livestream
 */

if (!function_exists('videohub360_get_or_create_guest_agora_uid')) {
    function videohub360_get_or_create_guest_agora_uid() {
        $cookie_name = 'vh360_guest_agora_uid';
        $min_uid = 1000000000;
        $max_uid = 2147483647;

        if (isset($_COOKIE[$cookie_name])) {
            $existing_uid = absint(wp_unslash($_COOKIE[$cookie_name]));
            if ($existing_uid >= $min_uid && $existing_uid <= $max_uid) {
                return $existing_uid;
            }
        }

        try {
            $guest_uid = random_int($min_uid, $max_uid);
        } catch (Exception $e) {
            $guest_uid = $min_uid + wp_rand(1, ($max_uid - $min_uid));
        }

        if (!headers_sent()) {
            $cookie_options = array(
                'expires'  => time() + MONTH_IN_SECONDS,
                'path'     => COOKIEPATH ? COOKIEPATH : '/',
                'secure'   => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            );

            if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
                $cookie_options['domain'] = COOKIE_DOMAIN;
            }

            setcookie($cookie_name, (string) $guest_uid, $cookie_options);
            $_COOKIE[$cookie_name] = (string) $guest_uid;
        }

        return $guest_uid;
    }
}

if (!function_exists('videohub360_get_livestream_bootstrap_data')) {
    function videohub360_get_livestream_bootstrap_data($post_id, $fields) {
        // Only return data for Agora livestreams
        if (empty($fields['type']) || $fields['type'] !== 'agora' || empty($fields['agora_channel_name'])) {
            return null;
        }
        
        // Get global App ID
        $global_app_id = get_option('vh360_agora_app_id', '');
        if (empty($global_app_id)) {
            return null;
        }
        
        // Get the post to check authorship
        $post = get_post($post_id);
        $is_owner = false;
        if ($post && is_user_logged_in()) {
            $is_owner = ((int) $post->post_author === (int) get_current_user_id());
        }
        
        // Determine user role and capabilities
        // User can manage if: admin OR can edit_post OR is post_author
        $is_original_host = current_user_can('manage_options') 
            || current_user_can('edit_post', $post_id) 
            || $is_owner;
        $can_moderate = $is_original_host || current_user_can('moderate_comments') || current_user_can('manage_options');
        $is_studio_controlled = 'yes' === get_post_meta($post_id, '_vh360_studio_controlled_live', true);
        $is_logged_in = is_user_logged_in();
        
        $current_user = wp_get_current_user();
        $user_display_name = $is_logged_in ? $current_user->display_name : 'Guest_' . substr(md5(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) . sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))), 0, 6);
        $user_id = $is_logged_in ? $current_user->ID : 0;
        $viewer_user_id = $user_id;
        $viewer_display_name = $viewer_user_id ? get_the_author_meta('display_name', $viewer_user_id) : __('Guest', 'videohub360');
        if (!$viewer_display_name) {
            $viewer_display_name = $user_display_name;
        }
        $studio_host_agora_uid = $is_studio_controlled ? absint(get_post_meta($post_id, '_vh360_studio_host_agora_uid', true)) : 0;
        $studio_host_user_id = $is_studio_controlled ? absint(get_post_meta($post_id, '_vh360_studio_host_user_id', true)) : 0;
        $studio_host_display_name = $studio_host_user_id ? get_the_author_meta('display_name', $studio_host_user_id) : '';
        $viewer_avatar_url = $viewer_user_id ? get_avatar_url($viewer_user_id) : '';
        $studio_host_avatar_url = $studio_host_user_id ? get_avatar_url($studio_host_user_id) : '';
        $agora_uid = $is_studio_controlled ? videohub360_get_or_create_guest_agora_uid() : ($is_logged_in ? $user_id : videohub360_get_or_create_guest_agora_uid());
        
        // Determine role
        $role = 'audience';
        if ($fields['agora_mode'] === 'broadcast') {
            $role = ($is_original_host || current_user_can('manage_options')) ? 'host' : 'audience';
        } else {
            if ($fields['agora_everyone_is_host'] === 'yes') {
                $role = 'host';
            } else {
                $role = $is_original_host ? 'host' : 'audience';
            }
        }
        
        if ($is_studio_controlled) {
            $role = 'audience';
        }
        $agora_mode = $fields['agora_mode'] === 'broadcast' ? 'live' : 'rtc';
        
        // Check if this is an appointment room
        $appointment_event_id = get_post_meta($post_id, '_vh360_appointment_event_id', true);
        $is_appointment = !empty($appointment_event_id);
        
        // Get appointment session state if this is an appointment
        $appointment_context = array(
            'isAppointment' => false,
            'userRole' => null,
            'status' => null,
            'canJoin' => false,
            'canPublish' => false,
            'message' => ''
        );
        
        if ($is_appointment && function_exists('vh360_get_appointment_session_state')) {
            $session_state = vh360_get_appointment_session_state($post_id, $user_id);
            
            // Determine if this user can publish in the appointment
            // Professional (room owner) can always publish
            // Client can publish during active sessions ('active' status)
            $can_publish = false;
            if ($session_state['user_role'] === 'professional') {
                $can_publish = true; // Professional can always publish
            } elseif ($session_state['user_role'] === 'client' && $session_state['status'] === 'active') {
                $can_publish = true; // Client can publish when session is active
            }
            
            $appointment_context = array(
                'isAppointment' => true,
                'userRole' => $session_state['user_role'],
                'status' => $session_state['status'],
                'canJoin' => $session_state['can_generate_token'],
                'canPublish' => $can_publish,
                'message' => $session_state['message']
            );
        }
        
        // Security context
        $security_context = array(
            'can_promote_users' => $is_original_host,
            'can_moderate' => $can_moderate,
            'user_id' => $user_id,
            'is_logged_in' => $is_logged_in,
            'display_name' => $is_studio_controlled ? $viewer_display_name : $user_display_name,
            'avatar_url' => $is_studio_controlled ? $viewer_avatar_url : ($user_id ? get_avatar_url($user_id) : ''),
            'is_original_host' => $is_original_host
        );
        
        // Debug info — intentionally omits sensitive data (roles, capabilities, passcode).
        $debug_info = array(
            'agora_mode' => $fields['agora_mode'],
            'everyone_is_host' => $fields['agora_everyone_is_host'],
            'final_role' => $role,
            'is_original_host' => $is_original_host,
            'is_appointment' => $is_appointment,
            'appointment_status' => $appointment_context['status']
        );
        
        return array(
            'appId' => $global_app_id,
            'channelName' => $fields['agora_channel_name'],
            'token' => null, // Tokens generated dynamically
            'role' => $role,
            'mode' => $agora_mode,
            'agoraMode' => $fields['agora_mode'],
            'uid' => $agora_uid,
            'isHost' => ($role === 'host'),
            'isOriginalHost' => $is_studio_controlled ? false : $is_original_host,
            'studioControlled' => $is_studio_controlled,
            'canModerate' => $can_moderate,
            'allowEveryoneIsHost' => ($fields['agora_everyone_is_host'] === 'yes'),
            // hostPasscodeRequired: safe boolean — never expose the actual passcode value.
            'hostPasscodeRequired' => !empty($fields['host_passcode']),
            'requireAgoraTokens' => (bool) get_option('vh360_agora_require_tokens', 1),
            'displayName' => $is_studio_controlled ? $viewer_display_name : $user_display_name,
            'userId' => $viewer_user_id,
            'viewerUserId' => $viewer_user_id,
            'viewerDisplayName' => $viewer_display_name,
            'viewerAvatar' => $viewer_avatar_url,
            'viewerAvatarUrl' => $viewer_avatar_url,
            'avatarUrl' => $is_studio_controlled ? $viewer_avatar_url : ($user_id ? get_avatar_url($user_id) : ''),
            'studioHostUid' => $studio_host_agora_uid,
            'studioHostUserId' => $studio_host_user_id,
            'studioHostDisplayName' => $studio_host_display_name,
            'studioHostAvatar' => $studio_host_avatar_url,
            'studioHostAvatarUrl' => $studio_host_avatar_url,
            'security' => $security_context,
            'debugInfo' => $debug_info,
            'appointment' => $appointment_context
        );
    }
}
