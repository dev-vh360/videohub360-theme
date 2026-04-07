<?php
/**
 * Direct Messages AJAX Handlers
 *
 * Handles all AJAX requests for direct messaging system.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_DM_Ajax
 */
class VH360_DM_Ajax {
    
    /**
     * Singleton instance
     *
     * @var VH360_DM_Ajax
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_DM_Ajax
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
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vh360_send_dm', array($this, 'send_message'));
        add_action('wp_ajax_vh360_load_conversation', array($this, 'load_conversation'));
        add_action('wp_ajax_vh360_load_conversations', array($this, 'load_conversations'));
        add_action('wp_ajax_vh360_mark_dm_read', array($this, 'mark_read'));
        add_action('wp_ajax_vh360_delete_conversation', array($this, 'delete_conversation'));
        add_action('wp_ajax_vh360_check_new_dm', array($this, 'check_new_messages'));
        add_action('wp_ajax_vh360_search_users_dm', array($this, 'search_users'));
    }
    
    /**
     * Send a direct message
     */
    public function send_message() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to send messages.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $recipient_id = isset($_POST['recipient_id']) ? absint($_POST['recipient_id']) : 0;
        
        // Fire action for membership check (and other integrations)
        do_action('vh360_dm_before_send_message', $current_user_id, $recipient_id);
        
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        
        if (!$recipient_id || empty($message)) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'videohub360-theme'),
            ));
        }
        
        // Check permissions
        if (!vh360_can_send_message($current_user_id, $recipient_id)) {
            wp_send_json_error(array(
                'message' => __('You cannot send messages to this user.', 'videohub360-theme'),
            ));
        }
        
        // Send message
        $message_id = vh360_send_message($current_user_id, $recipient_id, $message);
        
        if (!$message_id) {
            wp_send_json_error(array(
                'message' => __('Failed to send message. You may be sending too many messages.', 'videohub360-theme'),
            ));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vh360_direct_messages';
        $message_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $message_id
        ));
        
        wp_send_json_success(array(
            'message' => __('Message sent successfully.', 'videohub360-theme'),
            'message_data' => array(
                'id' => $message_data->id,
                'sender_id' => $message_data->sender_id,
                'recipient_id' => $message_data->recipient_id,
                'message_content' => wp_kses_post($message_data->message_content),
                'created_at' => $message_data->created_at,
                'is_sender' => true,
            ),
        ));
    }
    
    /**
     * Load conversation between two users
     */
    public function load_conversation() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $other_user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        
        if (!$other_user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'videohub360-theme'),
            ));
        }
        
        // Get conversation
        $messages = vh360_get_conversation($current_user_id, $other_user_id, $limit, $offset);
        
        // Get other user data
        $other_user = get_userdata($other_user_id);
        
        if (!$other_user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'videohub360-theme'),
            ));
        }
        
        // Format messages
        $formatted_messages = array();
        foreach ($messages as $msg) {
            $formatted_messages[] = array(
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'recipient_id' => $msg->recipient_id,
                'message_content' => wp_kses_post($msg->message_content),
                'created_at' => $msg->created_at,
                'read_at' => $msg->read_at,
                'is_sender' => ($msg->sender_id == $current_user_id),
            );
        }
        
        // Mark messages as read
        vh360_mark_messages_read($current_user_id, $other_user_id);
        
        wp_send_json_success(array(
            'messages' => $formatted_messages,
            'other_user' => array(
                'id' => $other_user->ID,
                'display_name' => $other_user->display_name,
                'avatar_url' => get_avatar_url($other_user->ID, array('size' => 50)),
            ),
            'can_send' => vh360_can_send_message($current_user_id, $other_user_id),
        ));
    }
    
    /**
     * Load conversation list
     */
    public function load_conversations() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
        
        // Get conversations
        $conversations = vh360_get_user_conversations($current_user_id, $limit);
        
        // Format conversations
        $formatted = array();
        foreach ($conversations as $conv) {
            if (!$conv->user) {
                continue;
            }
            
            $formatted[] = array(
                'user_id' => $conv->other_user_id,
                'display_name' => $conv->user->display_name,
                'avatar_url' => get_avatar_url($conv->user->ID, array('size' => 50)),
                'last_message' => wp_kses_post($conv->last_message),
                'last_message_time' => $conv->last_message_time,
                'unread_count' => absint($conv->unread_count),
            );
        }
        
        wp_send_json_success(array(
            'conversations' => $formatted,
            'total_unread' => vh360_get_unread_messages_count($current_user_id),
        ));
    }
    
    /**
     * Mark conversation as read
     */
    public function mark_read() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $other_user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        if (!$other_user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'videohub360-theme'),
            ));
        }
        
        $result = vh360_mark_messages_read($current_user_id, $other_user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Messages marked as read.', 'videohub360-theme'),
                'unread_count' => vh360_get_unread_messages_count($current_user_id),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to mark messages as read.', 'videohub360-theme'),
            ));
        }
    }
    
    /**
     * Delete conversation
     */
    public function delete_conversation() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $other_user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        if (!$other_user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'videohub360-theme'),
            ));
        }
        
        $result = vh360_delete_conversation($current_user_id, $other_user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Conversation deleted.', 'videohub360-theme'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete conversation.', 'videohub360-theme'),
            ));
        }
    }
    
    /**
     * Check for new messages
     */
    public function check_new_messages() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $last_check = isset($_POST['last_check']) ? sanitize_text_field($_POST['last_check']) : '';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vh360_direct_messages';
        
        // Get new messages since last check
        $new_messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE recipient_id = %d 
            AND created_at > %s
            AND deleted_by_recipient = 0
            ORDER BY created_at ASC",
            $current_user_id,
            $last_check
        ));
        
        $formatted_messages = array();
        foreach ($new_messages as $msg) {
            $sender = get_userdata($msg->sender_id);
            $formatted_messages[] = array(
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $sender ? $sender->display_name : '',
                'message_content' => wp_kses_post($msg->message_content),
                'created_at' => $msg->created_at,
            );
        }
        
        wp_send_json_success(array(
            'new_messages' => $formatted_messages,
            'unread_count' => vh360_get_unread_messages_count($current_user_id),
        ));
    }
    
    /**
     * Search users to message
     */
    public function search_users() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in.', 'videohub360-theme'),
            ));
        }
        
        $current_user_id = get_current_user_id();
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (strlen($search) < 2) {
            wp_send_json_success(array('users' => array()));
        }
        
        // Search users by display name or username
        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'display_name'),
            'number' => 10,
            'exclude' => array($current_user_id),
        ));
        
        $formatted_users = array();
        foreach ($users as $user) {
            // Check if can message this user
            if (vh360_can_send_message($current_user_id, $user->ID)) {
                $formatted_users[] = array(
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'username' => $user->user_login,
                    'avatar_url' => get_avatar_url($user->ID, array('size' => 50)),
                );
            }
        }
        
        wp_send_json_success(array(
            'users' => $formatted_users,
        ));
    }
}

// Initialize the class
VH360_DM_Ajax::get_instance();
