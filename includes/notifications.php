<?php
/**
 * Notification System Loader
 *
 * Loads all notification system components.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load core system class
require_once VH360_THEME_DIR . '/includes/notifications/class-vh360-notification-system.php';

// Load helper functions
require_once VH360_THEME_DIR . '/includes/notifications/notification-functions.php';

// Load template functions
require_once VH360_THEME_DIR . '/includes/notifications/notification-template-functions.php';

// Load notification triggers
require_once VH360_THEME_DIR . '/includes/notifications/class-vh360-notification-triggers.php';

// Load AJAX handlers
require_once VH360_THEME_DIR . '/includes/notifications/class-vh360-notification-ajax.php';

// Load preferences manager
require_once VH360_THEME_DIR . '/includes/notifications/class-vh360-notification-preferences.php';

// Initialize system
VH360_Notification_System::get_instance();

// Initialize triggers
VH360_Notification_Triggers::get_instance();

// Initialize AJAX handlers
VH360_Notification_Ajax::get_instance();

// Initialize preferences manager
VH360_Notification_Preferences::get_instance();
