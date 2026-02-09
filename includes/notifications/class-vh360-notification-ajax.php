<?php
/**
 * VH360 Notification AJAX Handlers
 *
 * Handles AJAX requests for notification operations.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Notification_Ajax
 */
class VH360_Notification_Ajax {
    
    /**
     * Singleton instance
     *
     * @var VH360_Notification_Ajax
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Notification_Ajax
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vh360_get_notification_count', array($this, 'get_notification_count'));
        add_action('wp_ajax_vh360_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_vh360_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_vh360_mark_all_notifications_read', array($this, 'mark_all_notifications_read'));
        add_action('wp_ajax_vh360_delete_notification', array($this, 'delete_notification'));
        
        // Dashboard-specific handlers
        add_action('wp_ajax_vh360_get_notifications_dashboard', array($this, 'get_notifications_dashboard'));
        add_action('wp_ajax_vh360_filter_notifications', array($this, 'filter_notifications'));
        add_action('wp_ajax_vh360_delete_read_notifications', array($this, 'delete_read_notifications'));
        add_action('wp_ajax_vh360_clear_all_notifications', array($this, 'clear_all_notifications'));
        add_action('wp_ajax_vh360_get_notification_stats', array($this, 'get_notification_stats'));
        
        // Preferences handlers
        add_action('wp_ajax_vh360_update_notification_preferences', array($this, 'update_notification_preferences'));
        add_action('wp_ajax_vh360_get_notification_preferences', array($this, 'get_notification_preferences'));
    }
    
    /**
     * Get unread notification count
     */
    public function get_notification_count() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $count = vh360_get_unread_notification_count($user_id);
        
        wp_send_json_success(array(
            'count' => $count
        ));
    }
    
    /**
     * Get notifications list
     */
    public function get_notifications() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 5;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        
        $notifications = vh360_get_notifications($user_id, array(
            'limit' => $limit,
            'offset' => $offset,
        ));
        
        // Format notifications for display
        $formatted = array();
        foreach ($notifications as $notification) {
            $formatted[] = vh360_format_notification($notification);
        }
        
        wp_send_json_success(array(
            'notifications' => $formatted
        ));
    }
    
    /**
     * Mark single notification as read
     */
    public function mark_notification_read() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $notification_id = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(array(
                'message' => __('Invalid notification ID.', 'videohub360-theme')
            ));
        }
        
        // Verify the notification belongs to the current user
        $system = VH360_Notification_System::get_instance();
        global $wpdb;
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$system->get_table_name()} WHERE id = %d",
            $notification_id
        ));
        
        if (!$notification || $notification->user_id != get_current_user_id()) {
            wp_send_json_error(array(
                'message' => __('Invalid notification.', 'videohub360-theme')
            ));
        }
        
        $result = vh360_mark_notification_read($notification_id);
        
        if ($result) {
            $count = vh360_get_unread_notification_count(get_current_user_id());
            wp_send_json_success(array(
                'message' => __('Notification marked as read.', 'videohub360-theme'),
                'count' => $count
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not mark notification as read.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function mark_all_notifications_read() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $result = vh360_mark_all_notifications_read($user_id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('All notifications marked as read.', 'videohub360-theme'),
                'count' => 0
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not mark all notifications as read.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Delete a notification
     */
    public function delete_notification() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $notification_id = isset($_POST['notification_id']) ? absint($_POST['notification_id']) : 0;
        
        if (!$notification_id) {
            wp_send_json_error(array(
                'message' => __('Invalid notification ID.', 'videohub360-theme')
            ));
        }
        
        // Verify the notification belongs to the current user
        $system = VH360_Notification_System::get_instance();
        global $wpdb;
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$system->get_table_name()} WHERE id = %d",
            $notification_id
        ));
        
        if (!$notification || $notification->user_id != get_current_user_id()) {
            wp_send_json_error(array(
                'message' => __('Invalid notification.', 'videohub360-theme')
            ));
        }
        
        $result = vh360_delete_notification($notification_id);
        
        if ($result) {
            $count = vh360_get_unread_notification_count(get_current_user_id());
            wp_send_json_success(array(
                'message' => __('Notification deleted.', 'videohub360-theme'),
                'count' => $count
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not delete notification.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Get notifications for dashboard (with pagination and filters)
     */
    public function get_notifications_dashboard() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Build query args
        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        );
        
        if (!empty($type)) {
            $args['type'] = $type;
        }
        
        if ($status === 'unread') {
            $args['is_read'] = 0;
        } elseif ($status === 'read') {
            $args['is_read'] = 1;
        }
        
        // Handle date filters
        if ($date === 'today') {
            $args['days'] = 1;
        } elseif ($date === 'week') {
            $args['days'] = 7;
        } elseif ($date === 'month') {
            $args['days'] = 30;
        }
        
        $notifications = vh360_get_notifications_filtered($user_id, $args);
        
        // Format notifications
        $formatted = array();
        foreach ($notifications as $notification) {
            $formatted_notification = vh360_format_notification($notification);
            // Add icon
            $formatted_notification['icon'] = vh360_get_notification_icon($notification->type);
            $formatted[] = $formatted_notification;
        }
        
        // Check if there are more
        $check_args = $args;
        $check_args['limit'] = 1;
        $check_args['offset'] = $page * $per_page;
        $has_more = count(vh360_get_notifications_filtered($user_id, $check_args)) > 0;
        
        wp_send_json_success(array(
            'notifications' => $formatted,
            'has_more' => $has_more,
            'page' => $page
        ));
    }
    
    /**
     * Filter notifications (alias for get_notifications_dashboard)
     */
    public function filter_notifications() {
        $this->get_notifications_dashboard();
    }
    
    /**
     * Delete read notifications
     */
    public function delete_read_notifications() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $result = vh360_delete_read_notifications($user_id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Read notifications deleted.', 'videohub360-theme'),
                'count' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not delete read notifications.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Clear all notifications
     */
    public function clear_all_notifications() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $result = vh360_clear_all_notifications($user_id);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('All notifications cleared.', 'videohub360-theme'),
                'count' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not clear all notifications.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Get notification statistics
     */
    public function get_notification_stats() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $stats = vh360_get_notification_stats($user_id);
        
        wp_send_json_success(array(
            'stats' => $stats
        ));
    }
    
    /**
     * Update notification preferences
     */
    public function update_notification_preferences() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        
        // Sanitize preferences data
        $raw_preferences = isset($_POST['preferences']) ? $_POST['preferences'] : array();
        
        // Basic sanitization before passing to preferences manager
        // Note: The preferences manager does additional validation
        $preferences = array();
        $preferences['enabled'] = isset($raw_preferences['enabled']) ? (bool) $raw_preferences['enabled'] : false;
        $preferences['frequency'] = isset($raw_preferences['frequency']) ? sanitize_text_field($raw_preferences['frequency']) : 'all';
        $preferences['types'] = isset($raw_preferences['types']) && is_array($raw_preferences['types']) ? array_map('sanitize_text_field', $raw_preferences['types']) : array();
        $preferences['display'] = isset($raw_preferences['display']) && is_array($raw_preferences['display']) ? $raw_preferences['display'] : array();
        
        if (isset($preferences['display']) && is_array($preferences['display'])) {
            $preferences['display']['sound'] = isset($raw_preferences['display']['sound']) ? (bool) $raw_preferences['display']['sound'] : false;
            $preferences['display']['desktop'] = isset($raw_preferences['display']['desktop']) ? (bool) $raw_preferences['display']['desktop'] : false;
            $preferences['display']['max_keep'] = isset($raw_preferences['display']['max_keep']) ? absint($raw_preferences['display']['max_keep']) : 100;
        }
        
        $prefs_manager = VH360_Notification_Preferences::get_instance();
        $result = $prefs_manager->update_preferences($user_id, $preferences);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Preferences updated successfully.', 'videohub360-theme'),
                'preferences' => $prefs_manager->get_preferences($user_id)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Could not update preferences.', 'videohub360-theme')
            ));
        }
    }
    
    /**
     * Get notification preferences
     */
    public function get_notification_preferences() {
        // Verify nonce
        if (!check_ajax_referer('vh360_notifications', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme')
            ));
        }
        
        $user_id = get_current_user_id();
        $prefs_manager = VH360_Notification_Preferences::get_instance();
        $preferences = $prefs_manager->get_preferences($user_id);
        
        wp_send_json_success(array(
            'preferences' => $preferences,
            'types' => $prefs_manager->get_notification_types()
        ));
    }
}
