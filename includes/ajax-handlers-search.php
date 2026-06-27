<?php
/**
 * Advanced Search AJAX Handlers
 *
 * Handles AJAX requests for the advanced search functionality.
 * Searches across multiple content types: videos, members, events, galleries, bulletins, and community posts.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register AJAX handlers for advanced search
 */
add_action('wp_ajax_vh360_advanced_search', 'vh360_handle_advanced_search');
add_action('wp_ajax_nopriv_vh360_advanced_search', 'vh360_handle_advanced_search');

/**
 * Handle advanced search AJAX request
 *
 * Queries only registered/available post types to support modular installs
 * and reduce unnecessary database queries for content types not in use.
 */
function vh360_handle_advanced_search() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_search_nonce')) {
        wp_send_json_error(array(
            'message' => esc_html__('Security check failed.', 'videohub360-theme'),
        ));
    }
    
    // Get and sanitize parameters
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
    
    // Validate query length
    if (strlen($query) < 2) {
        wp_send_json_error(array(
            'message' => esc_html__('Please enter at least 2 characters.', 'videohub360-theme'),
        ));
    }
    
    // Get available search types based on registered post types
    $available_types = vh360_get_available_search_type_keys();
    
    // If a specific type is requested, validate it's available
    if ($type !== 'all' && !in_array($type, $available_types, true)) {
        // Log for debugging purposes
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('VH360 Search: Invalid search type requested: ' . $type . '. Defaulting to "all".');
        }
        // Treat invalid type as 'all' to prevent errors
        $type = 'all';
    }
    
    $results = array();
    
    // Search based on type, only querying available types
    switch ($type) {
        case 'videos':
            if (in_array('videos', $available_types, true)) {
                $results['videos'] = vh360_search_videos($query);
            }
            break;
        case 'members':
            if (in_array('members', $available_types, true)) {
                $results['members'] = vh360_search_members($query);
            }
            break;
        case 'events':
            if (in_array('events', $available_types, true)) {
                $results['events'] = vh360_search_events($query);
            }
            break;
        case 'galleries':
            if (in_array('galleries', $available_types, true)) {
                $results['galleries'] = vh360_search_galleries($query);
            }
            break;
        case 'bulletins':
            if (in_array('bulletins', $available_types, true)) {
                $results['bulletins'] = vh360_search_bulletins($query);
            }
            break;
        case 'posts':
            if (in_array('posts', $available_types, true)) {
                $results['posts'] = vh360_search_community_posts($query);
            }
            break;
        case 'all':
        default:
            // Only query available/registered types
            if (in_array('videos', $available_types, true)) {
                $results['videos'] = vh360_search_videos($query);
            }
            if (in_array('members', $available_types, true)) {
                $results['members'] = vh360_search_members($query);
            }
            if (in_array('events', $available_types, true)) {
                $results['events'] = vh360_search_events($query);
            }
            if (in_array('galleries', $available_types, true)) {
                $results['galleries'] = vh360_search_galleries($query);
            }
            if (in_array('bulletins', $available_types, true)) {
                $results['bulletins'] = vh360_search_bulletins($query);
            }
            if (in_array('posts', $available_types, true)) {
                $results['posts'] = vh360_search_community_posts($query);
            }
            break;
    }
    
    // Count total results
    $total = 0;
    foreach ($results as $items) {
        $total += count($items);
    }
    
    wp_send_json_success(array(
        'results' => $results,
        'total' => $total,
        'query' => $query,
    ));
}

/**
 * Search videos (videohub360 post type)
 *
 * @param string $query Search query
 * @return array Array of video results
 */
function vh360_search_videos($query) {
    $args = array(
        'post_type' => 'videohub360',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
        'orderby' => 'relevance',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            
            $post_id = get_the_ID();
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'videohub360-video-thumb');
            
            // Fallback to default data URL or empty if none exists
            if (!$thumbnail_url) {
                $thumbnail_url = '';
            }
            
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'thumbnail' => $thumbnail_url,
                'author' => get_the_author(),
                'author_id' => get_the_author_meta('ID'),
                'date' => get_the_date(),
                'views' => get_post_meta($post_id, '_videohub360_views', true) ?: 0,
                'type' => 'video',
            );
        }
        wp_reset_postdata();
    }
    
    return $results;
}

/**
 * Search members (users)
 *
 * @param string $query Search query
 * @return array Array of member results
 */
function vh360_search_members($query) {
    $args = array(
        'search' => '*' . $query . '*',
        'search_columns' => array('user_login', 'user_nicename', 'display_name'),
        'number' => 5,
        'orderby' => 'display_name',
        'order' => 'ASC',
        'count_total' => false,
    );
    
    $user_query = new WP_User_Query($args);
    $results = array();
    
    if (!empty($user_query->results)) {
        foreach ($user_query->results as $user) {
            $avatar_url = get_avatar_url($user->ID, array('size' => 72));
            
            $results[] = array(
                'id' => $user->ID,
                'title' => $user->display_name,
                'url' => get_author_posts_url($user->ID),
                'avatar' => $avatar_url,
                'username' => $user->user_login,
                'role' => !empty($user->roles) ? ucfirst($user->roles[0]) : 'Member',
                'type' => 'member',
            );
        }
    }
    
    return $results;
}

/**
 * Search events (vh360_event post type)
 *
 * @param string $query Search query
 * @return array Array of event results
 */
function vh360_search_events($query) {
    $args = array(
        'post_type' => 'vh360_event',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
        'orderby' => 'relevance',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            
            $post_id = get_the_ID();
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            
            if (!$thumbnail_url) {
                $thumbnail_url = '';
            }
            
            $event_date = get_post_meta($post_id, '_vh360_event_date', true);
            $event_location = get_post_meta($post_id, '_vh360_event_location', true);
            
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'thumbnail' => $thumbnail_url,
                'date' => $event_date ? date_i18n(get_option('date_format'), strtotime($event_date)) : '',
                'location' => $event_location,
                'type' => 'event',
            );
        }
        wp_reset_postdata();
    }
    
    return $results;
}

/**
 * Search galleries (vh360_gallery post type)
 *
 * @param string $query Search query
 * @return array Array of gallery results
 */
function vh360_search_galleries($query) {
    $args = array(
        'post_type' => 'vh360_gallery',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            
            $post_id = get_the_ID();
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            
            if (!$thumbnail_url) {
                $thumbnail_url = '';
            }
            
            $image_count = get_post_meta($post_id, '_vh360_gallery_image_count', true) ?: 0;
            
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'thumbnail' => $thumbnail_url,
                'author' => get_the_author(),
                'author_id' => get_the_author_meta('ID'),
                'date' => get_the_date(),
                'image_count' => $image_count,
                'type' => 'gallery',
            );
        }
        wp_reset_postdata();
    }
    
    return $results;
}

/**
 * Search bulletins (vh360_bulletin post type)
 *
 * @param string $query Search query
 * @return array Array of bulletin results
 */
function vh360_search_bulletins($query) {
    $args = array(
        'post_type' => 'vh360_bulletin',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            
            $post_id = get_the_ID();
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            
            if (!$thumbnail_url) {
                $thumbnail_url = '';
            }
            
            $priority = get_post_meta($post_id, '_vh360_bulletin_priority', true);
            
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'thumbnail' => $thumbnail_url,
                'date' => get_the_date(),
                'priority' => $priority,
                'excerpt' => wp_trim_words(get_the_excerpt(), 15),
                'type' => 'bulletin',
            );
        }
        wp_reset_postdata();
    }
    
    return $results;
}

/**
 * Search community posts (vh360_post post type)
 *
 * @param string $query Search query
 * @return array Array of community post results
 */
function vh360_search_community_posts($query) {
    $args = array(
        'post_type' => 'vh360_post',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'update_post_term_cache' => false,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            
            $post_id = get_the_ID();
            $author_id = get_the_author_meta('ID');
            $avatar_url = get_avatar_url($author_id, array('size' => 72));
            
            $results[] = array(
                'id' => $post_id,
                'title' => get_the_title() ?: wp_trim_words(get_the_content(), 10),
                'url' => get_permalink(),
                'author' => get_the_author(),
                'author_id' => $author_id,
                'avatar' => $avatar_url,
                'date' => get_the_date(),
                'excerpt' => wp_trim_words(get_the_content(), 20),
                'type' => 'post',
            );
        }
        wp_reset_postdata();
    }
    
    return $results;
}
