<?php
/**
 * Helper Functions
 *
 * Utility helper functions for the theme.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get formatted video duration
 *
 * @param int $video_id The video post ID.
 * @return string Formatted duration (e.g., "5:23" or "1:05:30").
 */
function vh360_get_video_duration($video_id) {
    if (!$video_id) {
        return '';
    }

    // Check if plugin function exists
    if (function_exists('videohub360_get_video_duration')) {
        return videohub360_get_video_duration($video_id);
    }

    // Fallback: get from post meta
    $duration = get_post_meta($video_id, '_videohub360_duration', true);

    if (!$duration) {
        return '';
    }

    // Format duration from seconds
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    }

    return sprintf('%d:%02d', $minutes, $seconds);
}

/**
 * Get video view count
 *
 * @param int $video_id The video post ID.
 * @return int View count.
 */
function vh360_get_video_views($video_id) {
    if (!$video_id) {
        return 0;
    }

    // Check if plugin function exists
    if (function_exists('videohub360_get_video_views')) {
        return videohub360_get_video_views($video_id);
    }

    // Fallback: get from post meta
    $views = get_post_meta($video_id, '_videohub360_post_views_count', true);

    return $views ? absint($views) : 0;
}

/**
 * Check if current user can edit profile
 *
 * @param int $user_id The user ID to check.
 * @return bool True if user can edit profile, false otherwise.
 */
function vh360_user_can_edit_profile($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!is_user_logged_in()) {
        return false;
    }

    $current_user_id = get_current_user_id();

    // Users can edit their own profile
    if ($current_user_id === $user_id) {
        return true;
    }

    // Administrators can edit any profile
    if (current_user_can('manage_options')) {
        return true;
    }

    return false;
}

/**
 * Get user profile URL
 *
 * @param int $user_id The user ID.
 * @return string Profile URL.
 */
function vh360_get_profile_url($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return '';
    }

    // Check if plugin function exists
    if (function_exists('videohub360_get_profile_url')) {
        return videohub360_get_profile_url($user_id);
    }

    // Fallback: check for profile post type
    $args = array(
        'post_type' => 'vh360_profile',
        'meta_query' => array(
            array(
                'key' => '_vh360_profile_user_id',
                'value' => $user_id,
            ),
        ),
        'posts_per_page' => 1,
    );

    $profile_query = new WP_Query($args);

    if ($profile_query->have_posts()) {
        $profile_query->the_post();
        $url = get_permalink();
        wp_reset_postdata();
        return $url;
    }

    // Default fallback to author archive
    return get_author_posts_url($user_id);
}

/**
 * Format numbers with K, M, B suffixes
 *
 * @param int $number The number to format.
 * @return string Formatted number (e.g., "1K", "1.5M", "2B").
 */
function vh360_format_number($number) {
    $number = absint($number);

    if ($number < 1000) {
        return (string) $number;
    }

    if ($number < 1000000) {
        $formatted = round($number / 1000, 1);
        return $formatted . 'K';
    }

    if ($number < 1000000000) {
        $formatted = round($number / 1000000, 1);
        return $formatted . 'M';
    }

    $formatted = round($number / 1000000000, 1);
    return $formatted . 'B';
}

/**
 * Get user statistics
 *
 * @param int $user_id The user ID.
 * @return array Array of user statistics.
 */
function vh360_get_user_stats($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array(
            'videos' => 0,
            'content' => 0,
            'followers' => 0,
            'following' => 0,
            'views' => 0,
            'likes' => 0,
        );
    }

    // Check if plugin function exists
    if (function_exists('videohub360_get_user_stats')) {
        $plugin_stats = videohub360_get_user_stats($user_id);
        // Ensure content key exists for backwards compatibility
        if (!isset($plugin_stats['content'])) {
            $plugin_stats['content'] = vh360_get_user_content_count($user_id);
        }
        return $plugin_stats;
    }

    // Fallback: calculate stats
    $stats = array(
        'videos' => 0,
        'content' => 0,
        'followers' => 0,
        'following' => 0,
        'views' => 0,
        'likes' => 0,
    );

    // Count videos only (videohub360 post type)
    $stats['videos'] = vh360_get_user_video_count($user_id);
    
    // Count all dashboard content (videos + posts)
    $stats['content'] = vh360_get_user_content_count($user_id);

    // Calculate total views from all dashboard content
    // Use a transient to cache the expensive query
    $transient_key = 'vh360_user_views_' . $user_id;
    $total_views = get_transient($transient_key);

    if (false === $total_views) {
        $video_args = array(
            'post_type' => vh360_get_dashboard_content_types(),
            'author' => $user_id,
            'post_status' => 'publish',
            'posts_per_page' => 100, // Limit to 100 most recent items
            'fields' => 'ids', // Only get IDs for performance
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $video_ids = get_posts($video_args);
        $total_views = 0;
        foreach ($video_ids as $video_id) {
            $total_views += vh360_get_video_views($video_id);
        }

        // Cache for 1 hour
        set_transient($transient_key, $total_views, HOUR_IN_SECONDS);
    }

    $stats['views'] = $total_views;

    // Get follower count from user meta (updated to use the correct meta key)
    $followers = get_user_meta($user_id, 'vh360_followers_count', true);
    $stats['followers'] = $followers ? absint($followers) : 0;

    // Get following count from user meta (array of user IDs this user follows)
    if (function_exists('vh360_get_following_user_ids')) {
        $following_ids = vh360_get_following_user_ids($user_id);
        $stats['following'] = is_array($following_ids) ? count($following_ids) : 0;
    } else {
        $following = get_user_meta($user_id, 'vh360_following', true);
        $stats['following'] = is_array($following) ? count($following) : 0;
    }

    // Get likes count from user meta
    $likes = get_user_meta($user_id, '_vh360_likes_count', true);
    $stats['likes'] = $likes ? absint($likes) : 0;

    return $stats;
}

/**
 * Get video thumbnail URL with fallback
 *
 * @param int    $video_id The video post ID.
 * @param string $size     The image size.
 * @return string Thumbnail URL.
 */
function vh360_get_video_thumbnail($video_id, $size = 'videohub360-video-thumb') {
    if (!$video_id) {
        return '';
    }

    // Try to get featured image
    $thumbnail = get_the_post_thumbnail_url($video_id, $size);

    if ($thumbnail) {
        return $thumbnail;
    }

    // Fallback to plugin function if available
    if (function_exists('videohub360_get_video_thumbnail')) {
        return videohub360_get_video_thumbnail($video_id, $size);
    }

    // Ultimate fallback: return empty string (browser will show broken image or alt text)
    // Alternatively, you could create a data URI placeholder or check plugin for default
    return '';
}

/**
 * Check if a feature is enabled
 *
 * @param string $feature The feature name.
 * @return bool True if enabled, false otherwise.
 */
function vh360_is_feature_enabled($feature) {
    // Check theme options
    $enabled_features = get_option('vh360_enabled_features', array());

    if (is_array($enabled_features) && in_array($feature, $enabled_features)) {
        return true;
    }

    // Check if plugin function exists
    if (function_exists('videohub360_is_feature_enabled')) {
        return videohub360_is_feature_enabled($feature);
    }

    // Default features are enabled
    $default_features = array('profiles', 'dashboard', 'groups', 'bulletins', 'gallery');

    return in_array($feature, $default_features);
}

/**
 * Sanitize user input for profile fields
 *
 * @param string $input The input to sanitize.
 * @param string $type  The type of sanitization.
 * @return string Sanitized input.
 */
function vh360_sanitize_profile_input($input, $type = 'text') {
    switch ($type) {
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'html':
            return wp_kses_post($input);
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Sanitize embed code/custom HTML input
 * 
 * Uses capability-based filtering:
 * - Users with 'unfiltered_html' capability: no filtering (admin/editor)
 * - Regular users: filtered with whitelist that allows common embed elements
 *
 * Security notes:
 * - iframe 'src' attribute is allowed to support various embed providers (YouTube, Vimeo, etc.)
 * - Script tags are excluded to prevent XSS attacks
 * - Style attributes are excluded to prevent CSS injection
 * - WordPress's wp_kses() function provides additional URL and attribute validation
 *
 * @param string $html The HTML/embed code to sanitize.
 * @return string Sanitized HTML.
 */
function vh360_sanitize_embed_code($html) {
    // Users with unfiltered_html capability (admin/editor) can save as-is
    if (current_user_can('unfiltered_html')) {
        return $html;
    }
    
    // For regular users, use whitelist that allows common embed elements
    // Note: Script tags are intentionally excluded for security
    $allowed_embed_html = array(
        'iframe' => array(
            'src' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
            'allow' => true,
            'title' => true,
            'referrerpolicy' => true,
            'loading' => true,
            'class' => true,
            'id' => true,
            'name' => true,
            'scrolling' => true,
        ),
        'div' => array(
            'class' => true,
            'id' => true,
        ),
        'blockquote' => array(
            'class' => true,
            'cite' => true,
        ),
        'a' => array(
            'href' => true,
            'title' => true,
            'class' => true,
            'target' => true,
            'rel' => true,
        ),
        'p' => array(
            'class' => true,
        ),
        'span' => array(
            'class' => true,
        ),
        'br' => array(),
    );
    
    return wp_kses($html, $allowed_embed_html);
}


function vh360_get_dashboard_tab_url( $tab = 'profile' ) {
    $tab = sanitize_key( $tab );

    $dashboard_url = function_exists( 'vh360_get_dashboard_url' )
        ? vh360_get_dashboard_url()
        : home_url( '/dashboard/' );

    return add_query_arg( 'tab', $tab, $dashboard_url );
}

function vh360_get_profile_edit_url( $user_id = 0 ) {
    $user_id = $user_id ? absint( $user_id ) : absint( get_current_user_id() );

    if ( ! $user_id ) {
        return wp_login_url();
    }

    $account_type = function_exists( 'vh360_get_user_account_type' )
        ? vh360_get_user_account_type( $user_id )
        : get_user_meta( $user_id, '_vh360_account_type', true );

    $tab = in_array( $account_type, array( 'professional', 'organization' ), true )
        ? 'business-profile'
        : 'profile';

    return vh360_get_dashboard_tab_url( $tab );
}

/**
 * Get user avatar URL (not HTML)
 *
 * @param int    $user_id The user ID.
 * @param int    $size    Avatar size in pixels.
 * @return string Avatar URL or empty string if no avatar.
 */
function vh360_get_user_avatar_url($user_id, $size = 150) {
    $user_id = absint($user_id);
    if (!$user_id) {
        return '';
    }

    $attachment_id = absint(get_user_meta($user_id, 'vh360_profile_picture_id', true));
    if ($attachment_id) {
        $avatar_url = wp_get_attachment_image_url($attachment_id, array($size, $size));
        if ($avatar_url) {
            return $avatar_url;
        }
    }

    $legacy_avatar = get_user_meta($user_id, '_vh360_custom_avatar', true);
    if ($legacy_avatar) {
        if (is_numeric($legacy_avatar)) {
            $avatar_url = wp_get_attachment_image_url(absint($legacy_avatar), array($size, $size));
            if ($avatar_url) {
                return $avatar_url;
            }
        }

        if (filter_var($legacy_avatar, FILTER_VALIDATE_URL)) {
            return esc_url_raw($legacy_avatar);
        }
    }

    $avatar_url = get_avatar_url($user_id, array('size' => $size));

    return $avatar_url ? $avatar_url : '';
}

/**
 * Get user cover image
 *
 * @param int $user_id The user ID.
 * @return string|false Cover image URL or false if not set.
 */
function vh360_get_user_cover_image($user_id) {
    if (!$user_id) {
        return false;
    }

    $cover_image = get_user_meta($user_id, '_vh360_cover_image', true);

    if (!$cover_image) {
        return false;
    }

    // If it's an attachment ID, get the image URL
    if (is_numeric($cover_image)) {
        $image_url = wp_get_attachment_image_url($cover_image, 'full');
        if ($image_url) {
            return $image_url;
        }
    }

    // If it's a direct URL
    if (filter_var($cover_image, FILTER_VALIDATE_URL)) {
        return $cover_image;
    }

    return false;
}

/**
 * Get user bio/description
 *
 * @param int $user_id The user ID.
 * @return string User bio or empty string.
 */
function vh360_get_user_bio($user_id) {
    if (!$user_id) {
        return '';
    }

    $bio = get_the_author_meta('description', $user_id);

    return $bio ? sanitize_textarea_field($bio) : '';
}

/**
 * Get user registration/join date
 *
 * @param int    $user_id The user ID.
 * @param string $format  Date format ('relative' for time ago, or date format string).
 * @return string Formatted date.
 */
function vh360_get_user_join_date($user_id, $format = 'F Y') {
    if (!$user_id) {
        return '';
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return '';
    }

    $registered = $user->user_registered;

    if ($format === 'relative') {
        // Return relative time (e.g., "2 months ago")
        return sprintf(
            /* translators: %s: Time difference */
            esc_html__('Joined %s ago', 'videohub360-theme'),
            human_time_diff(strtotime($registered), current_time('timestamp'))
        );
    }

    // Return formatted date
    return date_i18n($format, strtotime($registered));
}

/**
 * Get dashboard content types
 *
 * Central definition of post types included in dashboard overview content counts.
 * This ensures consistency across overview widgets, recent content, and activity summaries.
 *
 * @return array Array of post type slugs.
 * @since 1.0.0
 */
function vh360_get_dashboard_content_types() {
    $content_types = array('videohub360', 'post');
    
    /**
     * Filter dashboard content types.
     *
     * @param array $content_types Array of post type slugs.
     * @since 1.0.0
     */
    return apply_filters('vh360_dashboard_content_types', $content_types);
}

/**
 * Get count of user's published videos (videohub360 post type only)
 *
 * @param int $user_id The user ID.
 * @return int Video count.
 */
function vh360_get_user_video_count($user_id) {
    if (!$user_id) {
        return 0;
    }

    // Count only videohub360 posts (true video content)
    $videohub360_count = count_user_posts($user_id, 'videohub360', true);

    return $videohub360_count;
}

/**
 * Get count of user's published dashboard content
 *
 * Counts all content types shown in dashboard overview (videos + posts).
 * This is distinct from vh360_get_user_video_count() which counts videos only.
 *
 * @param int $user_id The user ID.
 * @return int Content count.
 * @since 1.0.0
 */
function vh360_get_user_content_count($user_id) {
    if (!$user_id) {
        return 0;
    }

    $content_types = vh360_get_dashboard_content_types();
    $total_count = 0;

    foreach ($content_types as $post_type) {
        $total_count += count_user_posts($user_id, $post_type, true);
    }

    return $total_count;
}


/**
 * Get social platform registry.
 *
 * @return array
 */
function vh360_get_social_platform_registry() {
    return array(
        'website' => array(
            'label'       => __( 'Website Link', 'videohub360-theme' ),
            'meta_key'    => 'user_url',
            'placeholder' => 'https://example.com',
            'type'        => 'user_field',
        ),
        'twitter' => array(
            'label'       => __( 'Twitter (X)', 'videohub360-theme' ),
            'meta_key'    => '_vh360_twitter',
            'placeholder' => 'https://twitter.com/username',
        ),
        'facebook' => array(
            'label'       => __( 'Facebook', 'videohub360-theme' ),
            'meta_key'    => '_vh360_facebook',
            'placeholder' => 'https://facebook.com/username',
        ),
        'youtube' => array(
            'label'       => __( 'YouTube', 'videohub360-theme' ),
            'meta_key'    => '_vh360_youtube',
            'placeholder' => 'https://youtube.com/channel/...',
        ),
        'instagram' => array(
            'label'       => __( 'Instagram', 'videohub360-theme' ),
            'meta_key'    => '_vh360_instagram',
            'placeholder' => 'https://instagram.com/username',
        ),
        'linkedin' => array(
            'label'       => __( 'LinkedIn', 'videohub360-theme' ),
            'meta_key'    => '_vh360_linkedin',
            'placeholder' => 'https://linkedin.com/in/username',
        ),
        'tiktok' => array(
            'label'       => __( 'TikTok', 'videohub360-theme' ),
            'meta_key'    => '_vh360_tiktok',
            'placeholder' => 'https://tiktok.com/@username',
        ),
        'twitch' => array(
            'label'       => __( 'Twitch', 'videohub360-theme' ),
            'meta_key'    => '_vh360_twitch',
            'placeholder' => 'https://twitch.tv/username',
        ),
    );
}

/**
 * Get enabled social platforms based on profile settings.
 *
 * @return array
 */
function vh360_get_enabled_social_platforms() {
    $registry = function_exists( 'vh360_get_social_platform_registry' )
        ? vh360_get_social_platform_registry()
        : array();

    if ( empty( $registry ) ) {
        return array();
    }

    $options = get_option( 'vh360_profile_options', array() );

    $show_profile_links = isset( $options['show_social'] )
        ? (bool) $options['show_social']
        : true;

    if ( ! $show_profile_links ) {
        return array();
    }

    $enabled = isset( $options['social_platforms'] ) && is_array( $options['social_platforms'] )
        ? array_map( 'sanitize_key', $options['social_platforms'] )
        : array( 'website', 'twitter', 'facebook', 'youtube', 'instagram' );

    return array_intersect_key( $registry, array_flip( $enabled ) );
}

/**
 * Get user social media links
 *
 * @param int $user_id The user ID.
 * @return array Social media links array.
 */
function vh360_get_user_social_links($user_id, $include_disabled = false) {
    $user_id = absint($user_id);
    if (!$user_id) {
        return array();
    }

    $platforms = $include_disabled
        ? vh360_get_social_platform_registry()
        : vh360_get_enabled_social_platforms();

    $social_links = array();

    foreach ($platforms as $platform_key => $platform) {
        if ('website' === $platform_key) {
            $user = get_userdata($user_id);
            if ($user && !empty($user->user_url)) {
                $social_links[$platform_key] = esc_url_raw($user->user_url);
            }
            continue;
        }

        $meta_key = isset($platform['meta_key']) ? $platform['meta_key'] : '_vh360_' . $platform_key;
        $url = get_user_meta($user_id, $meta_key, true);

        if ($url) {
            $social_links[$platform_key] = esc_url_raw($url);
        }
    }

    return $social_links;
}

/**
 * Build WP_User_Query arguments for members directory
 * 
 * Internal query builder that constructs consistent WP_User_Query arguments
 * for both role-driven and account-type-driven audiences.
 * 
 * @param array $args Query arguments
 * @return array WP_User_Query arguments
 */
function vh360_build_members_directory_query_args($args = array()) {
    // Get members options for default values
    $members_options = get_option('vh360_members_options', array());
    $default_per_page = isset($members_options['per_page']) ? absint($members_options['per_page']) : 12;
    $visible_roles = isset($members_options['visible_roles']) && is_array($members_options['visible_roles'])
        ? $members_options['visible_roles']
        : array();

    $defaults = array(
        'audience' => 'all_members',                    // 'all_members' or 'professionals_only'
        'account_types' => array(),                     // For professionals_only mode
        'require_professional_approval' => true,        // For professionals_only mode
        'role' => '',                                   // For all_members mode (legacy)
        'role__in' => array(),                          // For all_members mode (legacy)
        'orderby' => 'registered',                      // registered, login, display_name, post_count
        'order' => 'DESC',                              // DESC or ASC
        'number' => $default_per_page,                  // Number of users per page
        'offset' => 0,                                  // Offset for pagination
        'search' => '',                                 // Search by display name or username
        'date_query' => array(),                        // Date query for registration
        'category' => '',                               // Member category slug
    );

    $args = wp_parse_args($args, $defaults);

    // Build base WP_User_Query args
    $query_args = array(
        'orderby' => $args['orderby'],
        'order' => $args['order'],
        'number' => absint($args['number']),
        'offset' => absint($args['offset']),
    );

    // Handle audience-based filtering
    if ($args['audience'] === 'professionals_only') {
        // Professionals-only mode: ignore role filters, use account type meta
        // SECURITY: Sanitize account_types to only allowed values
        $allowed_account_types = array('professional', 'organization');
        $sanitized_account_types = array();
        if (!empty($args['account_types']) && is_array($args['account_types'])) {
            $sanitized_account_types = array_intersect($args['account_types'], $allowed_account_types);
        }
        
        // SECURITY: Fail-closed enforcement - if account_types is empty, match nothing
        // This prevents data leaks when settings are misconfigured or payload is manipulated
        if (empty($sanitized_account_types)) {
            // Force an impossible meta_query that will match zero users
            $query_args['meta_query'] = array(
                array(
                    'key' => '_vh360_account_type',
                    'value' => '__none__',
                    'compare' => '=',
                ),
            );
        } else {
            // Valid account types present - apply normal filtering
            $query_args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => '_vh360_account_type',
                    'value' => $sanitized_account_types,
                    'compare' => 'IN',
                ),
            );
            
            // Add professional approval filter if required
            if ($args['require_professional_approval']) {
                // Show approved professionals or those without status (legacy accounts)
                // Three conditions are necessary because:
                // 1. value='approved' - explicitly approved professionals
                // 2. NOT EXISTS - meta key never set (legacy accounts)
                // 3. value='' - meta key exists but empty (some WP functions save empty strings)
                $query_args['meta_query'][] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_vh360_professional_status',
                        'value' => 'approved',
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_vh360_professional_status',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_vh360_professional_status',
                        'value' => '',
                        'compare' => '=',
                    ),
                );
            }
        }
    } else {
        // All members mode: use role filtering (existing behavior)
        if (!empty($args['role'])) {
            $query_args['role'] = sanitize_text_field($args['role']);
        } elseif (!empty($args['role__in']) && is_array($args['role__in'])) {
            $query_args['role__in'] = array_map('sanitize_text_field', $args['role__in']);
        } elseif (!empty($visible_roles)) {
            // Filter by visible roles from admin settings
            $query_args['role__in'] = array_map('sanitize_text_field', $visible_roles);
        }
    }

    // Add search
    if (!empty($args['search'])) {
        $query_args['search'] = '*' . sanitize_text_field($args['search']) . '*';
        $query_args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }

    // Add date query
    if (!empty($args['date_query'])) {
        $query_args['date_query'] = $args['date_query'];
    }

    // Add category filter
    if (!empty($args['category'])) {
        $category_slug = sanitize_title($args['category']);
        
        // Validate category
        $is_valid = function_exists('vh360_is_valid_member_category') 
            ? vh360_is_valid_member_category($category_slug)
            : false;
        
        // Initialize meta_query if not already set
        if (!isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array('relation' => 'AND');
        }
        
        if ($is_valid) {
            // Add category filter
            $query_args['meta_query'][] = array(
                'key' => '_vh360_member_category',
                'value' => $category_slug,
                'compare' => '=',
            );
        } else {
            // SECURITY: Fail-closed - force zero results for invalid category
            // This prevents the filter from appearing broken when an invalid category is submitted
            $query_args['meta_query'][] = array(
                'key' => '_vh360_member_category',
                'value' => '__invalid_category__',
                'compare' => '=',
            );
        }
    }

    return $query_args;
}

/**
 * Get members list
 *
 * @param array $args Query arguments. Supports new keys:
 *   - audience: 'all_members' or 'professionals_only'
 *   - account_types: array of account type strings
 *   - require_professional_approval: boolean
 *   Plus legacy keys: role, orderby, order, number, offset, search, date_query
 * @return array Array of WP_User objects.
 */
function vh360_get_members($args = array()) {
    $query_args = vh360_build_members_directory_query_args($args);
    $user_query = new WP_User_Query($query_args);
    return $user_query->get_results();
}

/**
 * Get member count
 *
 * @param string|array $args Query arguments array or legacy single role string.
 * @return int Member count.
 */
function vh360_get_member_count($args = '') {
    // Backwards compatibility: if string is passed, treat as role
    if (is_string($args)) {
        if (!empty($args)) {
            $args = array('role' => $args);
        } else {
            $args = array();
        }
    }
    
    // Build query args without pagination
    $count_args = $args;
    $count_args['number'] = -1;
    $count_args['offset'] = 0;
    
    $query_args = vh360_build_members_directory_query_args($count_args);
    $query_args['fields'] = 'ID';
    
    $user_query = new WP_User_Query($query_args);
    return $user_query->get_total();
}

/**
 * Get total members count (alias for vh360_get_member_count with array args)
 *
 * @param array $args Query arguments.
 * @return int Member count.
 */
function vh360_get_members_total($args = array()) {
    return vh360_get_member_count($args);
}

/**
 * Get member categories from admin settings
 *
 * @param bool $enabled_only Whether to return only enabled categories. Default true.
 * @return array Array of category objects with slug, label, enabled, and sort_order.
 */
function vh360_get_member_categories($enabled_only = true) {
    $members_options = get_option('vh360_members_options', array());
    $categories = isset($members_options['member_categories']) && is_array($members_options['member_categories'])
        ? $members_options['member_categories']
        : array();
    
    if ($enabled_only) {
        $categories = array_filter($categories, function($cat) {
            return !empty($cat['enabled']);
        });
    }
    
    return $categories;
}

/**
 * Get member category choices as key => label array for form fields
 *
 * @return array Array of slug => label pairs.
 */
function vh360_get_member_category_choices() {
    $categories = vh360_get_member_categories(true);
    $choices = array();
    
    foreach ($categories as $category) {
        if (!empty($category['slug']) && !empty($category['label'])) {
            $choices[$category['slug']] = $category['label'];
        }
    }
    
    return $choices;
}

/**
 * Get member category label by slug
 *
 * @param string $slug Category slug.
 * @return string Category label or empty string if not found.
 */
function vh360_get_member_category_label($slug) {
    if (empty($slug)) {
        return '';
    }
    
    $categories = vh360_get_member_categories(false); // Get all categories, even disabled
    
    foreach ($categories as $category) {
        if (isset($category['slug']) && $category['slug'] === $slug) {
            return isset($category['label']) ? $category['label'] : '';
        }
    }
    
    return '';
}

/**
 * Check if a member category slug is valid
 *
 * @param string $slug Category slug to validate.
 * @return bool True if valid, false otherwise.
 */
function vh360_is_valid_member_category($slug) {
    if (empty($slug)) {
        return false;
    }
    
    $categories = vh360_get_member_categories(true); // Only enabled categories
    
    foreach ($categories as $category) {
        if (isset($category['slug']) && $category['slug'] === $slug) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format activity timestamp
 *
 * @param int $timestamp Unix timestamp.
 * @return string Human-readable time difference.
 */
function vh360_format_activity_time($timestamp) {
    if (!$timestamp) {
        return '';
    }

    $time_diff = human_time_diff($timestamp, current_time('timestamp'));

    return sprintf(
        /* translators: %s: Time difference */
        esc_html__('%s ago', 'videohub360-theme'),
        $time_diff
    );
}

/**
 * Format activity content with link
 *
 * Renders activity title with optional link, avoiding code duplication.
 * Returns escaped HTML safe for direct output.
 *
 * @param array  $content Activity content array with 'title' and optionally 'link'.
 * @param string $prefix  Text to display before the title/link (e.g., "uploaded a new video:").
 * @return string Escaped HTML string safe for output.
 * @since 1.0.0
 */
function vh360_format_activity_content_link($content, $prefix = '') {
    if (!is_array($content)) {
        return esc_html($content);
    }
    
    $output = '';
    if ($prefix) {
        $output .= esc_html($prefix) . ' ';
    }
    
    if (!empty($content['link'])) {
        $output .= '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a>';
    } else {
        $output .= esc_html($content['title']);
    }
    
    return $output;
}

/**
 * Get activity type icon
 *
 * @param string $type Activity type.
 * @return string SVG icon HTML.
 */
function vh360_get_activity_icon($type) {
    $icons = array(
        'video_upload' => '<svg class="vh360-activity-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>',
        'post_publish' => '<svg class="vh360-activity-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
        'new_member' => '<svg class="vh360-activity-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>',
        'profile_update' => '<svg class="vh360-activity-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        'milestone' => '<svg class="vh360-activity-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>',
    );

    return isset($icons[$type]) ? $icons[$type] : '';
}

/**
 * Check if user is active (posted in last 30 days)
 *
 * @param int $user_id The user ID.
 * @return bool True if active, false otherwise.
 */
function vh360_is_user_active($user_id) {
    if (!$user_id) {
        return false;
    }

    // Check for recent posts
    $args = array(
        'author' => $user_id,
        'post_type' => vh360_get_dashboard_content_types(),
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'date_query' => array(
            array(
                'after' => '30 days ago',
            ),
        ),
    );

    $query = new WP_Query($args);

    return $query->have_posts();
}

/**
 * Get user activities
 *
 * @param int    $user_id The user ID.
 * @param int    $limit   Number of activities to return.
 * @param string $type    Filter by activity type (all, videos, comments, likes).
 * @return array Array of activity items.
 */
function vh360_get_user_activities($user_id, $limit = 20, $type = 'all') {
    if (!$user_id) {
        return array();
    }

    // Check if plugin function exists
    if (function_exists('videohub360_get_user_activities')) {
        return videohub360_get_user_activities($user_id, $limit, $type);
    }

    // Fallback: build activities from posts
    $activities = array();

    $args = array(
        'author' => $user_id,
        'post_type' => vh360_get_dashboard_content_types(),
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Set activity type based on actual post type
            $post_type = get_post_type();
            $activity_type = 'video_upload'; // default for videohub360
            if ($post_type === 'post') {
                $activity_type = 'post_publish';
            }
            
            $activities[] = array(
                'id' => get_the_ID(),
                'type' => $activity_type,
                'user_id' => $user_id,
                'timestamp' => get_the_time('U'),
                'content' => array(
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'post_type' => $post_type,
                ),
            );
        }
        wp_reset_postdata();
    }

    return $activities;
}

/**
 * Check if user can delete video
 *
 * @param int $video_id The video post ID.
 * @param int $user_id  The user ID (default: current user).
 * @return bool True if user can delete video, false otherwise.
 */
function vh360_user_can_delete_video($video_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$video_id) {
        return false;
    }

    // Get video author
    $video_author = get_post_field('post_author', $video_id);

    // User can delete their own videos
    if ($video_author == $user_id) {
        return true;
    }

    // Admins can delete any video
    if (current_user_can('delete_posts')) {
        return true;
    }

    return false;
}

/**
 * Get bulletins with optional filters
 *
 * @param array $args Query arguments
 * @return array Array of bulletin posts
 */
function vh360_get_bulletins($args = array()) {
    $defaults = array(
        'post_type' => 'vh360_bulletin',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num date',
        'order' => 'DESC',
        'meta_key' => '_vh360_bulletin_sticky',
    );

    $args = wp_parse_args($args, $defaults);

    $bulletins = get_posts($args);

    return $bulletins;
}

/**
 * Check if user has unread bulletins
 *
 * @param int $user_id User ID (default current user)
 * @return bool True if has unread
 */
function vh360_has_unread_bulletins($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $count = vh360_get_unread_bulletin_count($user_id);

    return $count > 0;
}

/**
 * Get count of unread bulletins for user
 *
 * @param int $user_id User ID (default current user)
 * @return int Count of unread bulletins
 */
function vh360_get_unread_bulletin_count($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 0;
    }

    // Get read and dismissed bulletins
    $read_bulletins = get_user_meta($user_id, '_vh360_read_bulletins', true);
    $dismissed_bulletins = get_user_meta($user_id, '_vh360_dismissed_bulletins', true);

    if (!is_array($read_bulletins)) {
        $read_bulletins = array();
    }
    if (!is_array($dismissed_bulletins)) {
        $dismissed_bulletins = array();
    }

    // Combine read and dismissed
    $exclude_ids = array_merge($read_bulletins, $dismissed_bulletins);

    // Build efficient query args
    $args = array(
        'post_type' => 'vh360_bulletin',
        'post_status' => 'publish',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_vh360_bulletin_expiry_date',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_vh360_bulletin_expiry_date',
                'value' => current_time('timestamp'),
                'compare' => '>=',
                'type' => 'NUMERIC'
            )
        )
    );

    // Exclude already read/dismissed
    if (!empty($exclude_ids)) {
        $args['post__not_in'] = $exclude_ids;
    }

    $bulletin_ids = get_posts($args);

    // Filter by user visibility
    $unread_count = 0;
    foreach ($bulletin_ids as $bulletin_id) {
        if (vh360_can_user_see_bulletin($bulletin_id, $user_id)) {
            $unread_count++;
        }
    }

    return $unread_count;
}

/**
 * Check if bulletin is read by user
 *
 * @param int $bulletin_id Bulletin post ID
 * @param int $user_id User ID (default current user)
 * @return bool True if read
 */
function vh360_is_bulletin_read($bulletin_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$bulletin_id) {
        return false;
    }

    $read_bulletins = get_user_meta($user_id, '_vh360_read_bulletins', true);

    if (!is_array($read_bulletins)) {
        $read_bulletins = array();
    }

    return in_array($bulletin_id, $read_bulletins);
}

/**
 * Mark bulletin as read for user
 *
 * @param int $bulletin_id Bulletin post ID
 * @param int $user_id User ID (default current user)
 * @return bool Success
 */
function vh360_mark_bulletin_read($bulletin_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$bulletin_id) {
        return false;
    }

    $read_bulletins = get_user_meta($user_id, '_vh360_read_bulletins', true);

    if (!is_array($read_bulletins)) {
        $read_bulletins = array();
    }

    if (!in_array($bulletin_id, $read_bulletins)) {
        $read_bulletins[] = $bulletin_id;
        return update_user_meta($user_id, '_vh360_read_bulletins', array_unique($read_bulletins));
    }

    return true;
}

/**
 * Dismiss bulletin for user (hide permanently)
 *
 * @param int $bulletin_id Bulletin post ID
 * @param int $user_id User ID (default current user)
 * @return bool Success
 */
function vh360_dismiss_bulletin($bulletin_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$bulletin_id) {
        return false;
    }

    // Also mark as read when dismissing
    vh360_mark_bulletin_read($bulletin_id, $user_id);

    $dismissed_bulletins = get_user_meta($user_id, '_vh360_dismissed_bulletins', true);

    if (!is_array($dismissed_bulletins)) {
        $dismissed_bulletins = array();
    }

    if (!in_array($bulletin_id, $dismissed_bulletins)) {
        $dismissed_bulletins[] = $bulletin_id;
        return update_user_meta($user_id, '_vh360_dismissed_bulletins', array_unique($dismissed_bulletins));
    }

    return true;
}

/**
 * Check if bulletin is expired
 *
 * @param int $bulletin_id Bulletin post ID
 * @return bool True if expired
 */
function vh360_is_bulletin_expired($bulletin_id) {
    if (!$bulletin_id) {
        return false;
    }

    $expiry_date = get_post_meta($bulletin_id, '_vh360_bulletin_expiry_date', true);

    if (!$expiry_date) {
        return false;
    }

    return $expiry_date < current_time('timestamp');
}

/**
 * Get active bulletins (not expired, not dismissed by user)
 *
 * @param string $type Filter by type (site_wide, urgent, etc)
 * @param int $user_id User ID for dismissed check
 * @return array Active bulletins
 */
function vh360_get_active_bulletins($type = 'all', $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $args = array(
        'post_type' => 'vh360_bulletin',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num date',
        'order' => 'DESC',
        'meta_key' => '_vh360_bulletin_sticky',
    );

    // Filter by priority if type is urgent or important
    if ($type === 'urgent' || $type === 'important') {
        $args['meta_query'] = array(
            array(
                'key' => '_vh360_bulletin_priority',
                'value' => $type,
                'compare' => '='
            )
        );
    }

    $bulletins = get_posts($args);
    $active_bulletins = array();

    // Get user's dismissed bulletins
    $dismissed_bulletins = array();
    if ($user_id) {
        $dismissed_bulletins = get_user_meta($user_id, '_vh360_dismissed_bulletins', true);
        if (!is_array($dismissed_bulletins)) {
            $dismissed_bulletins = array();
        }
    }

    foreach ($bulletins as $bulletin) {
        // Skip if expired
        if (vh360_is_bulletin_expired($bulletin->ID)) {
            continue;
        }

        // Skip if dismissed by user
        if ($user_id && in_array($bulletin->ID, $dismissed_bulletins)) {
            continue;
        }

        // Check if user can see this bulletin
        if ($user_id && !vh360_can_user_see_bulletin($bulletin->ID, $user_id)) {
            continue;
        }

        // When filtering for urgent bulletins (banner display), check show_banner flag
        if ($type === 'urgent') {
            $show_banner = get_post_meta($bulletin->ID, '_vh360_bulletin_show_banner', true);
            $bulletin_type = get_post_meta($bulletin->ID, '_vh360_bulletin_type', true);
            $priority = get_post_meta($bulletin->ID, '_vh360_bulletin_priority', true);

            // Backward compatibility: if show_banner meta doesn't exist,
            // treat as enabled for legacy urgent + site_wide bulletins
            if ($show_banner === '') {
                // Legacy bulletin - only show as banner if urgent AND site_wide
                if ($priority === 'urgent' && $bulletin_type === 'site_wide') {
                    // Allow this legacy bulletin to show as banner
                } else {
                    // Skip non-urgent or non-site_wide legacy bulletins
                    continue;
                }
            } elseif ($show_banner !== '1') {
                // Explicitly disabled, skip this bulletin
                continue;
            }
            // If show_banner === '1', bulletin is explicitly enabled for banner display
        }

        $active_bulletins[] = $bulletin;
    }

    return $active_bulletins;
}

/**
 * Get urgent bulletins for banner display
 *
 * @return array Urgent, non-expired bulletins
 */
function vh360_get_urgent_bulletins() {
    $user_id = get_current_user_id();

    return vh360_get_active_bulletins('urgent', $user_id);
}

/**
 * Check if user can see bulletin (based on targeting)
 *
 * @param int $bulletin_id Bulletin post ID
 * @param int $user_id User ID (default current user)
 * @return bool True if user can see
 */
function vh360_can_user_see_bulletin($bulletin_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$bulletin_id) {
        return false;
    }

    $type = get_post_meta($bulletin_id, '_vh360_bulletin_type', true);
    $target = get_post_meta($bulletin_id, '_vh360_bulletin_target', true);

    // Site-wide bulletins are visible to everyone
    if ($type === 'site_wide' || !$type) {
        return true;
    }

    // Not logged in users can only see site-wide bulletins
    if (!$user_id) {
        return false;
    }

    // Role-based bulletins
    if ($type === 'role' && $target) {
        $user = get_userdata($user_id);
        if ($user && in_array($target, $user->roles)) {
            return true;
        }
        return false;
    }

    // User-specific bulletins
    if ($type === 'user' && $target) {
        return absint($target) === $user_id;
    }

    // Group-based bulletins (reserved for future implementation when groups feature is added)
    if ($type === 'group' && $target) {
        // Check if user is member of group
        // This will be implemented when the groups feature is added to the theme
        $user_groups = get_user_meta($user_id, '_vh360_joined_groups', true);
        if (is_array($user_groups) && in_array(absint($target), $user_groups)) {
            return true;
        }
        return false;
    }

    return false;
}

/**
 * Get bulletin priority label
 *
 * @param int $bulletin_id Bulletin post ID
 * @return string Priority (normal|important|urgent)
 */
function vh360_get_bulletin_priority($bulletin_id) {
    if (!$bulletin_id) {
        return 'normal';
    }

    $priority = get_post_meta($bulletin_id, '_vh360_bulletin_priority', true);

    return $priority ? $priority : 'normal';
}

/**
 * Get bulletin type
 *
 * @param int $bulletin_id Bulletin post ID
 * @return string Type (site_wide|group|role|user)
 */
function vh360_get_bulletin_type($bulletin_id) {
    if (!$bulletin_id) {
        return 'site_wide';
    }

    $type = get_post_meta($bulletin_id, '_vh360_bulletin_type', true);

    return $type ? $type : 'site_wide';
}

/**
 * Get author template mode (profile or channel)
 *
 * @return string 'profile' or 'channel'
 */
function vh360_get_author_template_mode() {
    return get_theme_mod('vh360_author_template_mode', 'profile');
}

/**
 * Check if channel has playlists (series)
 *
 * @param int $author_id Author ID
 * @return bool True if author has videos with series taxonomy
 */
function vh360_channel_has_playlists($author_id) {
    if (!$author_id) {
        return false;
    }

    $videos = get_posts(array(
        'author' => $author_id,
        'post_type' => 'videohub360',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'videohub360_series',
                'operator' => 'EXISTS'
            )
        ),
        'meta_query' => array(
            'relation' => 'OR',
            array('key' => '_vh360_context', 'compare' => 'NOT EXISTS'),
            array('key' => '_vh360_context', 'value' => 'live_room', 'compare' => '!=')
        )
    ));

    return !empty($videos);
}

/**
 * Check if Community Menu should be displayed
 *
 * Implements layered detection logic for Community Menu visibility:
 * - Layer 1: Navigation style set to 'community'
 * - Layer 2: Logged-in status (only if Customizer requires it)
 * - Layer 3: Template exclusions based on settings
 * - Layer 4: WooCommerce pages (if active)
 * - Layer 5: WordPress core login
 * - Layer 6: Custom page IDs (future-proofing)
 *
 * @return bool True if Community Menu should be shown, false otherwise.
 */
function vh360_show_community_menu() {
    // Layer 1: Check if Navigation Style is set to 'community'
    $nav_style = get_theme_mod('vh360_nav_style', 'horizontal');
    if ($nav_style !== 'community') {
        return false;
    }

    // Layer 2: Check logged-in status if Customizer requires it
    $show_to_logged_out = get_theme_mod('vh360_community_menu_logged_out', 0);
    if (!$show_to_logged_out && !is_user_logged_in()) {
        return false;
    }

    // Layer 3: Template exclusions based on Customizer settings

    // Check Dashboard template exclusion
    if (get_theme_mod('vh360_community_menu_hide_dashboard', 1)) {
        if (is_page_template('template-dashboard.php') || is_page_template('templates/dashboard.php')) {
            return false;
        }
    }

    // Check Auth pages exclusion
    if (get_theme_mod('vh360_community_menu_hide_auth', 1)) {
        // Custom auth templates
        $auth_templates = array(
            'template-login.php',
            'template-register.php',
            'template-lost-password.php',
            'template-reset-password.php',
        );

        foreach ($auth_templates as $template) {
            if (is_page_template($template)) {
                return false;
            }
        }
    }

    // Layer 4: WooCommerce account pages (if WooCommerce is active)
    if (function_exists('is_account_page') && is_account_page()) {
        return false;
    }

    // Layer 5: WordPress core login page
    // Check if we're on wp-login.php
    if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') {
        return false;
    }

    // Additional check for wp-login.php via PHP_SELF
    if (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], 'wp-login.php') !== false) {
        return false;
    }

    // Layer 6: Custom page IDs (future-proofing)
    // This allows site admins to exclude specific pages via filter
    $excluded_page_ids = apply_filters('vh360_community_menu_excluded_pages', array());
    if (is_page() && in_array(get_the_ID(), $excluded_page_ids)) {
        return false;
    }

    return true;
}

/**
 * Get pagination arguments for author/profile pages
 *
 * Builds proper pagination arguments for use with paginate_links() on author archive pages.
 * Handles both pretty permalinks and plain permalink structures.
 *
 * @param int   $author_id      The author ID.
 * @param int   $current_page   The current page number.
 * @param int   $max_pages      Maximum number of pages.
 * @param array $query_args     Optional. Additional query args to preserve (e.g., filter, sort).
 * @return array Pagination arguments for paginate_links().
 * @since 1.0.0
 */
function vh360_get_author_pagination_args($author_id, $current_page, $max_pages, $query_args = array()) {
    // For custom WP_Query on author archives, we need to paginate the CURRENT URL
    // not rebuild from get_author_posts_url() which can cause 404s
    // Using get_pagenum_link() ensures we work with WordPress's rewrite rules
    // Note: We don't set 'format' - let WordPress use the correct structure from get_pagenum_link()

    $big = 999999999; // Unlikely page number

    $pagination_args = array(
        'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'current'   => $current_page,
        'total'     => $max_pages,
        'prev_text' => '&laquo; ' . esc_html__('Previous', 'videohub360-theme'),
        'next_text' => esc_html__('Next', 'videohub360-theme') . ' &raquo;',
        'type'      => 'list',
        'end_size'  => 3,
        'mid_size'  => 2,
    );

    // Preserve current profile UI state (tabs/filters/sort) unless explicitly overridden.
    // This ensures pagination links keep users in the same profile view.
    $preserve = array('tab', 'filter', 'sort');
    foreach ($preserve as $key) {
        if (!isset($query_args[$key]) && isset($_GET[$key])) {
            $val = sanitize_key(wp_unslash($_GET[$key]));
            if ($val !== '') {
                $query_args[$key] = $val;
            }
        }
    }

    // Add query args if provided
    if (!empty($query_args)) {
        $pagination_args['add_args'] = $query_args;
    }

    return $pagination_args;
}

/**
 * Get current page number for pagination
 *
 * Retrieves the current page number from query vars, checking both 'paged' and 'page'.
 * Always returns at least 1 (for the first page).
 *
 * @return int Current page number (minimum 1).
 * @since 1.0.0
 */
function vh360_get_current_page() {
    return max(1, absint(get_query_var('paged') ? get_query_var('paged') : get_query_var('page')));
}

/**
 * Check if Community Menu should be forced into compact mode
 *
 * Returns true for Single Video pages and Live Room pages where we want
 * the Community Menu to default to icons-only mode to maximize video/player width.
 *
 * Both page types are served as is_singular('videohub360'). Live Room pages are
 * identified by post meta _vh360_context === 'live_room' but the template switch
 * happens via single_template filter, so they're still singular videohub360 posts.
 *
 * @return bool True if compact mode should be forced, false otherwise.
 * @since 1.0.0
 */
function vh360_force_compact_community_menu() {
    return is_singular('videohub360');
}

/**
 * Get Dashboard Page URL
 *
 * Discovers the actual dashboard page by querying for template-dashboard.php.
 * This ensures menus and fallbacks use the real URL, even if the page slug differs.
 *
 * @return string Dashboard page URL.
 * @since 1.0.0
 */
function vh360_get_dashboard_page_url() {
    $dashboard_page = get_pages(
        array(
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'template-dashboard.php',
            'number'     => 1,
        )
    );

    if ( ! empty( $dashboard_page ) ) {
        return get_permalink( $dashboard_page[0]->ID );
    }

    return home_url( '/dashboard/' );
}

/**
 * Get Activity Feed Page URL
 *
 * Discovers the actual activity feed page by querying for template-activity-feed.php.
 *
 * @return string Activity feed page URL.
 * @since 1.0.0
 */
function vh360_get_activity_page_url() {
    $activity_page = get_pages(
        array(
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'template-activity-feed.php',
            'number'     => 1,
        )
    );

    if ( ! empty( $activity_page ) ) {
        return get_permalink( $activity_page[0]->ID );
    }

    return home_url( '/activity/' );
}

/**
 * Get Members Directory Page URL
 *
 * Discovers the members page by slug 'members'.
 *
 * @return string Members page URL.
 * @since 1.0.0
 */
function vh360_get_members_page_url() {
    $members_page = get_page_by_path( 'members' );

    if ( $members_page ) {
        return get_permalink( $members_page->ID );
    }

    return home_url( '/members/' );
}
