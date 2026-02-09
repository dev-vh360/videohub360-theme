<?php
/**
 * Notification Template Functions
 *
 * Display and rendering functions for notifications.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render notification bell icon HTML
 */
function vh360_render_notification_bell() {
    if (!is_user_logged_in()) {
        return;
    }
    
    get_template_part('template-parts/notifications/notification-bell');
}

/**
 * Render notification dropdown HTML
 */
function vh360_render_notification_dropdown() {
    if (!is_user_logged_in()) {
        return;
    }
    
    get_template_part('template-parts/notifications/notification-dropdown');
}

/**
 * Render a single notification item
 *
 * @param object $notification Notification object
 */
function vh360_render_notification_item($notification) {
    if (!$notification) {
        return;
    }
    
    $formatted = vh360_format_notification($notification);
    
    if (!$formatted) {
        return;
    }
    
    set_query_var('notification', $formatted);
    get_template_part('template-parts/notifications/notification-item');
}
