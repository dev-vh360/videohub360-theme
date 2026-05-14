<?php
/**
 * Search Helper Functions
 *
 * Provides centralized functions for search functionality, including
 * detecting available content types based on registered post types.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get available search types based on registered post types
 *
 * Returns an ordered array of content types that should be searchable.
 * Only includes types where the post type is registered (or Members which is always available).
 * This ensures that unused modules (e.g., Bulletins, Galleries) don't appear in search UI.
 *
 * @return array Ordered array of available search types with their keys and labels
 */
function vh360_get_available_search_types() {
    $types = array();
    
    // Videos (videohub360) - primary content type
    if (post_type_exists('videohub360')) {
        $types['videos'] = array(
            'key' => 'videos',
            'post_type' => 'videohub360',
            'label' => __('Videos', 'videohub360-theme'),
        );
    }
    
    // Members - optional WordPress user search controlled by Customizer
    if (get_theme_mod('vh360_search_include_members', true)) {
        $types['members'] = array(
            'key'       => 'members',
            'post_type' => 'user', // Special identifier for user-based search (not a WordPress post type)
            'label'     => __('Members', 'videohub360-theme'),
        );
    }
    
    // Events (vh360_event)
    if (post_type_exists('vh360_event')) {
        $types['events'] = array(
            'key' => 'events',
            'post_type' => 'vh360_event',
            'label' => __('Events', 'videohub360-theme'),
        );
    }
    
    // Galleries (vh360_gallery)
    if (post_type_exists('vh360_gallery')) {
        $types['galleries'] = array(
            'key' => 'galleries',
            'post_type' => 'vh360_gallery',
            'label' => __('Galleries', 'videohub360-theme'),
        );
    }
    
    // Bulletins (vh360_bulletin)
    if (post_type_exists('vh360_bulletin')) {
        $types['bulletins'] = array(
            'key' => 'bulletins',
            'post_type' => 'vh360_bulletin',
            'label' => __('Bulletins', 'videohub360-theme'),
        );
    }
    
    // Community Posts (vh360_post)
    if (post_type_exists('vh360_post')) {
        $types['posts'] = array(
            'key' => 'posts',
            'post_type' => 'vh360_post',
            'label' => __('Posts', 'videohub360-theme'),
        );
    }
    
    /**
     * Filter available search types
     *
     * Allows plugins/child themes to modify which content types are searchable.
     *
     * @param array $types Array of available search types
     */
    return apply_filters('vh360_available_search_types', $types);
}

/**
 * Get array of available search type keys
 *
 * Helper function to get just the keys (videos, members, events, etc.)
 * for use in queries and conditionals.
 *
 * @return array Array of type keys
 */
function vh360_get_available_search_type_keys() {
    return array_keys(vh360_get_available_search_types());
}

/**
 * Check if a search type is available
 *
 * @param string $type The type key to check (e.g., 'videos', 'events')
 * @return bool True if the type is available, false otherwise
 */
function vh360_is_search_type_available($type) {
    $available_types = vh360_get_available_search_type_keys();
    return in_array($type, $available_types, true);
}
