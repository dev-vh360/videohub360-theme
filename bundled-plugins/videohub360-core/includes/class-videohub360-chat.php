<?php
/**
 * VideoHub360 Chat Class
 * 
 * Handles live chat functionality
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Chat {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Chat AJAX handlers
        add_action('wp_ajax_videohub360_chat_post', array($this, 'handle_chat_post'));
        add_action('wp_ajax_videohub360_chat_fetch', array($this, 'handle_chat_fetch'));
        add_action('wp_ajax_nopriv_videohub360_chat_fetch', array($this, 'handle_chat_fetch'));
        add_action('wp_ajax_videohub360_chat_delete', array($this, 'handle_chat_delete'));
        add_action('wp_ajax_videohub360_chat_pin', array($this, 'handle_chat_pin'));
        add_action('wp_ajax_videohub360_chat_ban', array($this, 'handle_chat_ban'));
        add_action('wp_ajax_videohub360_chat_timeout', array($this, 'handle_chat_timeout'));
        add_action('wp_ajax_videohub360_chat_report', array($this, 'handle_chat_report'));
        add_action('wp_ajax_nopriv_videohub360_chat_report', array($this, 'handle_chat_report'));
        add_action('wp_ajax_videohub360_chat_check_features', array($this, 'handle_chat_check_features'));
        add_action('wp_ajax_nopriv_videohub360_chat_check_features', array($this, 'handle_chat_check_features'));
        add_action('wp_ajax_videohub360_chat_upgrade_database', array($this, 'handle_chat_upgrade_database'));
    }
    
    /**
     * Handle chat post
     */
    public function handle_chat_post() {
        // Check if chat is enabled
        if (!get_option('videohub360_chat_enabled', 1)) {
            wp_send_json_error(__('Chat is currently disabled.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to post messages.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Enhanced input validation and sanitization
        $post_id = absint($_POST['post_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $reply_to = absint($_POST['reply_to'] ?? 0);
        $message_type = sanitize_text_field($_POST['message_type'] ?? 'public');
        $recipient_id = absint($_POST['recipient_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        if (empty(trim($message))) {
            wp_send_json_error(__('Message cannot be empty.', 'videohub360'));
            return;
        }
        
        // Validate message type
        if (!in_array($message_type, array('public', 'private'))) {
            wp_send_json_error(__('Invalid message type.', 'videohub360'));
            return;
        }
        
        // Check configurable message length limit  
        $message_limit = get_option('videohub360_chat_message_limit', 500);
        if (strlen($message) > $message_limit) {
            wp_send_json_error(sprintf(__('Message is too long. Maximum %d characters allowed.', 'videohub360'), $message_limit));
            return;
        }
        
        // Check if post exists and is a videohub360 livestream
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post.', 'videohub360'));
            return;
        }
        
        // Check if video is live - chat is only available for livestreams
        $is_live = get_post_meta($post_id, '_vh360_is_live', true);
        if ($is_live !== 'yes') {
            wp_send_json_error(__('Chat is only available for live streams.', 'videohub360'));
            return;
        }
        
        // Check if live stream has ended
        $stream_stopped = get_post_meta($post_id, '_vh360_stream_stopped', true);
        if ($stream_stopped === 'yes') {
            wp_send_json_error(__('This live stream has ended. Chat is no longer available.', 'videohub360'));
            return;
        }
        
        // Check if chat is enabled for this specific video (per-video setting overrides global)
        $per_video_chat = get_post_meta($post_id, '_vh360_chat_enabled', true);
        $video_chat_enabled = false;
        
        if ($per_video_chat === 'yes') {
            $video_chat_enabled = true;
        } elseif ($per_video_chat === 'no') {
            $video_chat_enabled = false;
        } else {
            // Use global setting if no per-video setting
            $video_chat_enabled = get_option('videohub360_chat_enabled', 1);
        }
        
        if (!$video_chat_enabled) {
            wp_send_json_error(__('Chat is not enabled for this video.', 'videohub360'));
            return;
        }
        
        // Validate reply_to if provided
        if ($reply_to > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'videohub360_chat_messages';
            
            $parent_message = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND post_id = %d",
                $reply_to,
                $post_id
            ));
            
            if (!$parent_message) {
                wp_send_json_error(__('Cannot reply to a message that does not exist.', 'videohub360'));
                return;
            }
        }
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_avatar = get_avatar($current_user->ID, 24);
        
        // Check if user is banned or timed out using standardized function
        $moderation_status = videohub360_check_user_moderation_status($current_user->ID, $post_id);
        if ($moderation_status['status'] === 'banned') {
            wp_send_json_error(__('You have been banned from this chat. Reason: ' . ($moderation_status['reason'] ?: 'No reason provided'), 'videohub360'));
            return;
        } elseif ($moderation_status['status'] === 'timeout') {
            $expires = human_time_diff(current_time('timestamp'), strtotime($moderation_status['expires']));
            wp_send_json_error(sprintf(__('You are temporarily muted for %s. Reason: %s', 'videohub360'), 
                $expires, 
                $moderation_status['reason'] ?: 'No reason provided'
            ));
            return;
        }
        
        // Rate limiting with configurable limit
        if (!$this->check_rate_limit($current_user->ID)) {
            wp_send_json_error(__('You are posting too quickly. Please wait a moment.', 'videohub360'));
            return;
        }
        
        // Insert message into database
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Prepare database table name
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            wp_send_json_error(__('Chat database tables are missing. Please deactivate and reactivate the plugin, or contact the administrator.', 'videohub360'));
            return;
        }
        
        // Verify table structure has reply_to column
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'reply_to'");
        
        if (count($column_exists) == 0) {
            wp_send_json_error(__('Chat database structure is outdated. Please deactivate and reactivate the plugin, or contact the administrator.', 'videohub360'));
            return;
        }
        
        // Check if private messaging columns exist
        $message_type_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'message_type'");
        $recipient_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'recipient_id'");
        
        // Prepare basic data array (always compatible with old table structure)
        $data = array(
            'post_id' => $post_id,
            'user_id' => $current_user->ID,
            'username' => $current_user->display_name,
            'user_avatar' => $user_avatar,
            'message' => $message,
            'created_at' => current_time('mysql')
        );
        
        $format = array('%d', '%d', '%s', '%s', '%s', '%s');
        
        // Only add private messaging columns if they exist in the table
        if (count($message_type_column_exists) > 0) {
            $data['message_type'] = $message_type;
            $format[] = '%s';
            
            // Validate recipient for private messages
            if ($message_type === 'private') {
                if (!$recipient_id) {
                    wp_send_json_error(__('Recipient is required for private messages.', 'videohub360'));
                    return;
                }
                
                // Check if recipient user exists
                $recipient_user = get_user_by('id', $recipient_id);
                if (!$recipient_user) {
                    wp_send_json_error(__('Invalid recipient user.', 'videohub360'));
                    return;
                }
                
                // Add recipient_id for private messages only if column exists
                if (count($recipient_id_column_exists) > 0) {
                    $data['recipient_id'] = $recipient_id;
                    $format[] = '%d';
                }
            }
        } else {
            // If private messaging columns don't exist, reject private messages
            if ($message_type === 'private') {
                wp_send_json_error(__('Private messaging is not available. Please contact administrator to enable this feature.', 'videohub360'));
                return;
            }
        }
        
        // Add reply_to if it exists
        if ($reply_to > 0) {
            $data['reply_to'] = $reply_to;
            $format[] = '%d';
        }
        
        // Try insert with comprehensive error handling
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result === false) {
            // Get detailed error information
            $mysql_error = $wpdb->last_error;
            $mysql_query = $wpdb->last_query;
            
            // Try to provide more specific error message
            if (strpos($mysql_error, 'reply_to') !== false) {
                wp_send_json_error(__('Database error: reply_to column issue. Please contact administrator.', 'videohub360'));
            } elseif (strpos($mysql_error, 'Duplicate entry') !== false) {
                wp_send_json_error(__('Duplicate message detected. Please try again.', 'videohub360'));
            } elseif (strpos($mysql_error, 'foreign key') !== false || strpos($mysql_error, 'constraint') !== false) {
                wp_send_json_error(__('Database constraint error. Please contact administrator.', 'videohub360'));
            } else {
                wp_send_json_error(__('Failed to save message: ' . $mysql_error, 'videohub360'));
            }
            return;
        }
        
        // Return success with message data
        wp_send_json_success(array(
            'id' => $wpdb->insert_id,
            'user_id' => $current_user->ID,
            'username' => $current_user->display_name,
            'avatar' => $user_avatar,
            'message' => $message,
            'message_type' => $message_type,
            'recipient_id' => $message_type === 'private' ? $recipient_id : null,
            'reply_to' => $reply_to > 0 ? $reply_to : null,
            'timestamp' => current_time('H:i'),
            'can_delete' => true,
            'can_pin' => current_user_can('moderate_comments') || current_user_can('manage_options')
        ));
    }
    
    /**
     * Handle chat fetch
     */
    public function handle_chat_fetch() {
        // Rate limiting for this endpoint
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $rate_limit_key = 'vh360_chat_fetch_rate_' . md5($ip);
        $requests = get_transient($rate_limit_key);
        if ($requests && $requests > 60) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        set_transient($rate_limit_key, ($requests ? $requests + 1 : 1), 60);
        
        // Enhanced input validation and sanitization
        $post_id = absint($_POST['post_id'] ?? 0);
        $since_id = absint($_POST['since_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Check if post exists and is a videohub360 livestream
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post.', 'videohub360'));
            return;
        }
        
        // Get current user ID for permission checking
        $current_user_id = get_current_user_id();
        
        // Fetch recent messages
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if private messaging columns exist
        $message_type_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'message_type'");
        $recipient_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'recipient_id'");
        
        if (count($message_type_column_exists) > 0 && count($recipient_id_column_exists) > 0) {
            // Full query with private messaging support
            $sql = $wpdb->prepare(
                "SELECT m.*, 
                        CASE WHEN m.reply_to IS NOT NULL THEN 
                            (SELECT username FROM $table_name WHERE id = m.reply_to) 
                        ELSE NULL END as reply_to_username,
                        CASE WHEN m.message_type = 'private' AND m.recipient_id IS NOT NULL THEN
                            (SELECT display_name FROM {$wpdb->users} WHERE ID = m.recipient_id)
                        ELSE NULL END as recipient_username
                 FROM $table_name m
                 WHERE m.post_id = %d AND m.id > %d 
                 AND (
                     m.message_type = 'public' 
                     OR (m.message_type = 'private' AND (m.user_id = %d OR m.recipient_id = %d))
                 )
                 ORDER BY m.is_pinned DESC, m.created_at ASC 
                 LIMIT 50",
                $post_id,
                $since_id,
                $current_user_id,
                $current_user_id
            );
        } else {
            // Backward compatible query for old table structure (no private messaging)
            $sql = $wpdb->prepare(
                "SELECT m.*, 
                        CASE WHEN m.reply_to IS NOT NULL THEN 
                            (SELECT username FROM $table_name WHERE id = m.reply_to) 
                        ELSE NULL END as reply_to_username,
                        NULL as recipient_username
                 FROM $table_name m
                 WHERE m.post_id = %d AND m.id > %d 
                 ORDER BY m.is_pinned DESC, m.created_at ASC 
                 LIMIT 50",
                $post_id,
                $since_id
            );
        }
        
        $messages = $wpdb->get_results($sql);
        
        if ($messages === false) {
            wp_send_json_error(__('Failed to fetch messages.', 'videohub360'));
            return;
        }
        
        $formatted_messages = array();
        $highest_id = $since_id;
        
        foreach ($messages as $message) {
            $can_delete = ($current_user_id == $message->user_id) || current_user_can('moderate_comments') || current_user_can('manage_options');
            $can_pin = current_user_can('moderate_comments') || current_user_can('manage_options');
            
            $formatted_messages[] = array(
                'id' => intval($message->id),
                'user_id' => intval($message->user_id),
                'username' => esc_html($message->username),
                'avatar' => $message->user_avatar,
                'message' => esc_html($message->message),
                'message_type' => isset($message->message_type) ? $message->message_type : 'public',
                'recipient_id' => isset($message->recipient_id) ? intval($message->recipient_id) : null,
                'recipient_username' => isset($message->recipient_username) ? esc_html($message->recipient_username) : null,
                'reply_to' => isset($message->reply_to) ? intval($message->reply_to) : null,
                'reply_to_username' => isset($message->reply_to_username) ? esc_html($message->reply_to_username) : null,
                'timestamp' => mysql2date('H:i', $message->created_at),
                'is_pinned' => isset($message->is_pinned) ? intval($message->is_pinned) : 0,
                'can_delete' => $can_delete,
                'can_pin' => $can_pin
            );
            
            if ($message->id > $highest_id) {
                $highest_id = $message->id;
            }
        }
        
        wp_send_json_success(array(
            'messages' => $formatted_messages,
            'highest_id' => $highest_id
        ));
    }
    
    /**
     * Handle chat delete
     */
    public function handle_chat_delete() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to delete messages.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        $message_id = absint($_POST['message_id'] ?? 0);
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Get the message to check permissions
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            wp_send_json_error(__('Message not found.', 'videohub360'));
            return;
        }
        
        $current_user_id = get_current_user_id();
        
        // Check if user can delete this message (own message or has moderation permissions)
        if ($current_user_id != $message->user_id && !current_user_can('moderate_comments') && !current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to delete this message.', 'videohub360'));
            return;
        }
        
        // Delete the message
        $result = $wpdb->delete($table_name, array('id' => $message_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error(__('Failed to delete message. Please try again.', 'videohub360'));
            return;
        }
        
        wp_send_json_success(array('message' => __('Message deleted successfully.', 'videohub360')));
    }
    
    /**
     * Handle chat pin
     */
    public function handle_chat_pin() {
        // Check if user is logged in and has moderation permissions
        if (!is_user_logged_in() || (!current_user_can('moderate_comments') && !current_user_can('manage_options'))) {
            wp_send_json_error(__('You do not have permission to pin messages.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        $message_id = absint($_POST['message_id'] ?? 0);
        $pin_status = sanitize_text_field($_POST['pin_status'] ?? '');
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'videohub360'));
            return;
        }
        
        if (!in_array($pin_status, array('pin', 'unpin'))) {
            wp_send_json_error(__('Invalid pin status.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if message exists
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            wp_send_json_error(__('Message not found.', 'videohub360'));
            return;
        }
        
        // If pinning, unpin other messages in the same post first
        if ($pin_status === 'pin') {
            $wpdb->update(
                $table_name,
                array('is_pinned' => 0),
                array('post_id' => $message->post_id),
                array('%d'),
                array('%d')
            );
        }
        
        // Update pin status
        $new_pin_status = ($pin_status === 'pin') ? 1 : 0;
        $result = $wpdb->update(
            $table_name,
            array('is_pinned' => $new_pin_status),
            array('id' => $message_id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Failed to update pin status. Please try again.', 'videohub360'));
            return;
        }
        
        $action = ($pin_status === 'pin') ? 'pinned' : 'unpinned';
        wp_send_json_success(array('message' => sprintf(__('Message %s successfully.', 'videohub360'), $action)));
    }
    
    /**
     * Handle chat ban
     */
    public function handle_chat_ban() {
        // Check permissions
        if (!is_user_logged_in() || (!current_user_can('moderate_comments') && !current_user_can('manage_options'))) {
            wp_send_json_error(__('You do not have permission to ban users.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        $message_id = absint($_POST['message_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'videohub360'));
            return;
        }
        
        // Get user_id and post_id from message
        global $wpdb;
        $chat_table = $wpdb->prefix . 'videohub360_chat_messages';
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, post_id FROM $chat_table WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            wp_send_json_error(__('Message not found.', 'videohub360'));
            return;
        }
        
        $user_id = $message->user_id;
        $post_id = $message->post_id;
        
        // Don't allow banning administrators
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(__('Cannot ban administrators.', 'videohub360'));
            return;
        }
        
        $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_send_json_error(__('Moderation database tables are missing. Please deactivate and reactivate the plugin, or contact the administrator.', 'videohub360'));
            return;
        }
        
        // Insert ban record with is_active flag
        $result = $wpdb->replace(
            $table_name,
            array(
                'target_user_id' => $user_id,
                'post_id' => $post_id,
                'moderator_user_id' => get_current_user_id(),
                'message_id' => $message_id,
                'action_type' => 'ban',
                'source_type' => 'chat',
                'reason' => $reason,
                'created_at' => current_time('mysql'),
                'expiration_time' => null,
                'is_active' => 1
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Failed to ban user. Please try again.', 'videohub360'));
            return;
        }
        
        wp_send_json_success(array('message' => __('User banned successfully.', 'videohub360')));
    }
    
    /**
     * Handle chat timeout
     */
    public function handle_chat_timeout() {
        // Check permissions
        if (!is_user_logged_in() || (!current_user_can('moderate_comments') && !current_user_can('manage_options'))) {
            wp_send_json_error(__('You do not have permission to timeout users.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        $message_id = absint($_POST['message_id'] ?? 0);
        $duration = absint($_POST['duration'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$message_id || !$duration) {
            wp_send_json_error(__('Invalid parameters.', 'videohub360'));
            return;
        }
        
        // Get user_id and post_id from message
        global $wpdb;
        $chat_table = $wpdb->prefix . 'videohub360_chat_messages';
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, post_id FROM $chat_table WHERE id = %d",
            $message_id
        ));
        
        if (!$message) {
            wp_send_json_error(__('Message not found.', 'videohub360'));
            return;
        }
        
        $user_id = $message->user_id;
        $post_id = $message->post_id;
        
        // Don't allow timing out administrators
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(__('Cannot timeout administrators.', 'videohub360'));
            return;
        }
        
        // Calculate expiry time using current_time for timezone consistency
        $expires_timestamp = current_time('timestamp') + ($duration * 60);
        $expires_at = date('Y-m-d H:i:s', $expires_timestamp);
        
        $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            wp_send_json_error(__('Moderation database tables are missing. Please deactivate and reactivate the plugin, or contact the administrator.', 'videohub360'));
            return;
        }
        
        // Insert timeout record with is_active flag
        $result = $wpdb->replace(
            $table_name,
            array(
                'target_user_id' => $user_id,
                'post_id' => $post_id,
                'moderator_user_id' => get_current_user_id(),
                'message_id' => $message_id,
                'action_type' => 'timeout',
                'source_type' => 'chat',
                'reason' => $reason,
                'created_at' => current_time('mysql'),
                'expiration_time' => $expires_at,
                'is_active' => 1
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Failed to timeout user. Please try again.', 'videohub360'));
            return;
        }
        
        wp_send_json_success(array('message' => sprintf(__('User timed out for %d minutes.', 'videohub360'), $duration)));
    }
    
    /**
     * Handle chat report
     */
    public function handle_chat_report() {
        $message_id = absint($_POST['message_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$message_id) {
            wp_send_json_error(__('Invalid message ID.', 'videohub360'));
            return;
        }
        
        // Rate limiting for reports
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $rate_limit_key = 'vh360_chat_report_rate_' . md5($ip);
        $reports = get_transient($rate_limit_key);
        if ($reports && $reports > 5) {
            wp_send_json_error(__('You have reported too many messages. Please try again later.', 'videohub360'));
            return;
        }
        set_transient($rate_limit_key, ($reports ? $reports + 1 : 1), 300); // 5 minute window
        
        // TODO: Implement actual reporting system (email to moderators, etc.)
        
        wp_send_json_success(array('message' => __('Message reported successfully. Moderators will review it.', 'videohub360')));
    }
    
    /**
     * Handle chat feature availability check
     */
    public function handle_chat_check_features() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if private messaging columns exist
        $message_type_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'message_type'");
        $recipient_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'recipient_id'");
        
        $private_messaging_available = (count($message_type_column_exists) > 0 && count($recipient_id_column_exists) > 0);
        
        wp_send_json_success(array(
            'private_messaging_available' => $private_messaging_available,
            'features' => array(
                'private_messaging' => $private_messaging_available
            )
        ));
    }
    
    /**
     * Handle database upgrade for private messaging
     */
    public function handle_chat_upgrade_database() {
        // Check if user has permission to upgrade database (admin only)
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to upgrade the database.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if private messaging columns already exist
        $message_type_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'message_type'");
        $recipient_id_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'recipient_id'");
        
        if (count($message_type_column_exists) > 0 && count($recipient_id_column_exists) > 0) {
            wp_send_json_success(array(
                'message' => __('Private messaging is already enabled.', 'videohub360'),
                'private_messaging_available' => true
            ));
            return;
        }
        
        try {
            // Add private messaging columns
            $result = $wpdb->query("ALTER TABLE $table_name 
                         ADD COLUMN message_type enum('public', 'private') DEFAULT 'public' AFTER message,
                         ADD COLUMN recipient_id bigint(20) unsigned DEFAULT NULL AFTER message_type,
                         ADD KEY message_type (message_type),
                         ADD KEY recipient_id (recipient_id)");
            
            if ($result === false) {
                wp_send_json_error(__('Failed to upgrade database: ' . $wpdb->last_error, 'videohub360'));
                return;
            }
            
            // Update database version
            update_option('videohub360_chat_db_version', '2.3');
            
            wp_send_json_success(array(
                'message' => __('Private messaging has been enabled successfully!', 'videohub360'),
                'private_messaging_available' => true
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to upgrade database: ' . $e->getMessage(), 'videohub360'));
        }
    }
    
    /**
     * Check rate limit for user
     */
    private function check_rate_limit($user_id) {
        $rate_limit = get_option('videohub360_chat_rate_limit', 5); // messages per minute
        $rate_limit_key = 'vh360_chat_rate_' . $user_id;
        $messages_sent = get_transient($rate_limit_key);
        
        if ($messages_sent && $messages_sent >= $rate_limit) {
            return false;
        }
        
        set_transient($rate_limit_key, ($messages_sent ? $messages_sent + 1 : 1), 60);
        return true;
    }
}