<?php
/**
 * Appointment Live Room Access Gate
 *
 * Restricts access to appointment Live Rooms to only the professional,
 * client, and administrators. This ensures privacy for private appointment sessions.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Gate access to appointment Live Rooms.
 * 
 * This function runs on template_redirect and checks if the current page
 * is an appointment Live Room. If so, it enforces access control:
 * - Must be logged in
 * - Must be the professional (post author), client, or an administrator
 * 
 * Non-logged-in users are redirected to login.
 * Logged-in non-members get a 404.
 */
function vh360_gate_appointment_live_room_access() {
    // Only check on singular videohub360 posts
    if (!is_singular('videohub360')) {
        return;
    }
    
    $post_id = get_queried_object_id();
    if (!$post_id) {
        return;
    }
    
    // Only gate if this is a Live Room
    $context = get_post_meta($post_id, '_vh360_context', true);
    if ($context !== 'live_room') {
        return;
    }
    
    // Only gate if this is an appointment room (has appointment event ID)
    $appointment_event_id = get_post_meta($post_id, '_vh360_appointment_event_id', true);
    if (!$appointment_event_id) {
        // Not an appointment room, allow normal access
        return;
    }
    
    // This is an appointment Live Room - enforce access control
    
    // If not logged in, redirect to login page with return URL
    if (!is_user_logged_in()) {
        if (function_exists('vh360_get_login_page_url_with_redirect')) {
            wp_redirect(vh360_get_login_page_url_with_redirect(get_permalink($post_id)));
            exit;
        }
        // Fallback if helper function not available
        wp_redirect(wp_login_url(get_permalink($post_id)));
        exit;
    }
    
    // User is logged in - check if they have access using timing helper
    $current_user_id = get_current_user_id();
    
    // Use centralized timing helper for access control
    if (function_exists('vh360_can_user_view_appointment_page')) {
        $can_view = vh360_can_user_view_appointment_page($post_id, $current_user_id);
        
        if (!$can_view) {
            // User is logged in but not authorized - return 404
            // We don't want to reveal that this room exists
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include(get_404_template());
            exit;
        }
        
        // User has permission to view the page
        return;
    }
    
    // Fallback to legacy access check if helper not loaded
    // Allow administrators
    if (current_user_can('manage_options')) {
        return;
    }
    
    // Allow post author (professional)
    $post = get_post($post_id);
    if ($post && (int) $post->post_author === (int) $current_user_id) {
        return;
    }
    
    // Allow the client who booked the appointment
    $client_id = get_post_meta($post_id, '_vh360_appointment_client_id', true);
    if ($client_id && (int) $client_id === (int) $current_user_id) {
        return;
    }
    
    // User is logged in but not authorized - return 404
    // We don't want to reveal that this room exists
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    include(get_404_template());
    exit;
}

// Hook into template_redirect with priority 10
add_action('template_redirect', 'vh360_gate_appointment_live_room_access', 10);
