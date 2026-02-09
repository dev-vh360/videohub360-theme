<?php
/**
 * Direct Messages Core Functions
 *
 * Core functionality for 1-on-1 direct messaging system.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create direct messages database table
 */
function vh360_create_dm_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sender_id bigint(20) NOT NULL,
        recipient_id bigint(20) NOT NULL,
        message_content text NOT NULL,
        created_at datetime NOT NULL,
        read_at datetime DEFAULT NULL,
        deleted_by_sender tinyint(1) NOT NULL DEFAULT 0,
        deleted_by_recipient tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY sender_id (sender_id),
        KEY recipient_id (recipient_id),
        KEY created_at (created_at),
        KEY recipient_read (recipient_id, read_at)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    
    update_option('vh360_dm_db_version', '1.0.0');
}

/**
 * Get DM settings
 *
 * @return array Settings array
 */
function vh360_get_dm_settings() {
    $defaults = array(
        'enable_dm' => true,
        'require_mutual_follow' => false,
        'char_limit' => 1000,
        'retention_days' => 0, // 0 = forever
    );
    
    $settings = get_option('vh360_dm_settings', array());
    return wp_parse_args($settings, $defaults);
}

/**
 * Check if direct messaging is enabled
 *
 * @return bool
 */
function vh360_is_dm_enabled() {
    $settings = vh360_get_dm_settings();
    return !empty($settings['enable_dm']);
}

/**
 * Check if user can send message to another user
 *
 * @param int $sender_id Sender user ID
 * @param int $recipient_id Recipient user ID
 * @return bool
 */
function vh360_can_send_message($sender_id, $recipient_id) {
    // Check if DM is enabled
    if (!vh360_is_dm_enabled()) {
        return false;
    }
    
    // Can't message yourself
    if ($sender_id == $recipient_id) {
        return false;
    }
    
    // Check if both users exist
    $sender = get_userdata($sender_id);
    $recipient = get_userdata($recipient_id);
    
    if (!$sender || !$recipient) {
        return false;
    }
    
    // Check mutual follow requirement
    $settings = vh360_get_dm_settings();
    if (!empty($settings['require_mutual_follow'])) {
        // Check if both users follow each other
        if (!function_exists('vh360_is_following')) {
            return true; // If follow system not available, allow messaging
        }
        
        // Check if sender follows recipient and vice versa
        $sender_follows_recipient = vh360_is_following($recipient_id, $sender_id);
        $recipient_follows_sender = vh360_is_following($sender_id, $recipient_id);
        
        if (!$sender_follows_recipient || !$recipient_follows_sender) {
            return false;
        }
    }
    
    return true;
}

/**
 * Check rate limit for sending messages
 *
 * @param int $user_id User ID
 * @return bool True if allowed, false if rate limited
 */
function vh360_check_dm_rate_limit($user_id) {
    $cache_key = 'vh360_dm_rate_limit_' . $user_id;
    $count = get_transient($cache_key);
    
    if ($count === false) {
        set_transient($cache_key, 1, MINUTE_IN_SECONDS);
        return true;
    }
    
    if ($count >= 10) {
        return false; // Rate limit: max 10 messages per minute
    }
    
    set_transient($cache_key, $count + 1, MINUTE_IN_SECONDS);
    return true;
}

/**
 * Send a direct message
 *
 * @param int $sender_id Sender user ID
 * @param int $recipient_id Recipient user ID
 * @param string $message Message content
 * @return int|false Message ID on success, false on failure
 */
function vh360_send_message($sender_id, $recipient_id, $message) {
    global $wpdb;
    
    // Check permissions
    if (!vh360_can_send_message($sender_id, $recipient_id)) {
        return false;
    }
    
    // Check rate limit
    if (!vh360_check_dm_rate_limit($sender_id)) {
        return false;
    }
    
    // Sanitize message content
    $message = wp_kses_post(trim($message));
    
    if (empty($message)) {
        return false;
    }
    
    // Check character limit
    $settings = vh360_get_dm_settings();
    $char_limit = isset($settings['char_limit']) ? absint($settings['char_limit']) : 1000;
    
    if (mb_strlen($message) > $char_limit) {
        return false;
    }
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'message_content' => $message,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s')
    );
    
    if ($result === false) {
        return false;
    }
    
    $message_id = $wpdb->insert_id;
    
    // Clear conversation cache
    delete_transient('vh360_dm_conversations_' . $sender_id);
    delete_transient('vh360_dm_conversations_' . $recipient_id);
    delete_transient('vh360_dm_unread_count_' . $recipient_id);
    
    // Trigger action for notifications
    do_action('vh360_message_sent', $message_id, $sender_id, $recipient_id, $message);
    
    return $message_id;
}

/**
 * Get conversation between two users
 *
 * @param int $user1_id First user ID
 * @param int $user2_id Second user ID
 * @param int $limit Number of messages to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of message objects
 */
function vh360_get_conversation($user1_id, $user2_id, $limit = 50, $offset = 0) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    $limit = absint($limit);
    $offset = absint($offset);
    
    // Get messages where neither user has deleted them
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
        WHERE (
            (sender_id = %d AND recipient_id = %d AND deleted_by_sender = 0)
            OR 
            (sender_id = %d AND recipient_id = %d AND deleted_by_recipient = 0)
        )
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d",
        $user1_id, $user2_id,
        $user2_id, $user1_id,
        $limit, $offset
    ));
    
    return array_reverse($messages); // Show oldest first
}

/**
 * Get all conversations for a user with last message and unread count
 *
 * @param int $user_id User ID
 * @param int $limit Number of conversations to retrieve
 * @return array Array of conversation objects
 */
function vh360_get_user_conversations($user_id, $limit = 50) {
    global $wpdb;
    
    // Check cache
    $cache_key = 'vh360_dm_conversations_' . $user_id;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    $limit = absint($limit);
    
    // Get all unique conversation partners with last message
    $conversations = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            CASE 
                WHEN sender_id = %d THEN recipient_id 
                ELSE sender_id 
            END AS other_user_id,
            MAX(created_at) AS last_message_time,
            (SELECT message_content 
             FROM {$table_name} AS dm2 
             WHERE (
                 (dm2.sender_id = %d AND dm2.recipient_id = other_user_id AND dm2.deleted_by_sender = 0)
                 OR 
                 (dm2.recipient_id = %d AND dm2.sender_id = other_user_id AND dm2.deleted_by_recipient = 0)
             )
             ORDER BY dm2.created_at DESC 
             LIMIT 1
            ) AS last_message,
            (SELECT COUNT(*) 
             FROM {$table_name} AS dm3 
             WHERE dm3.recipient_id = %d 
             AND dm3.sender_id = other_user_id
             AND dm3.read_at IS NULL
             AND dm3.deleted_by_recipient = 0
            ) AS unread_count
        FROM {$table_name}
        WHERE (
            (sender_id = %d AND deleted_by_sender = 0)
            OR 
            (recipient_id = %d AND deleted_by_recipient = 0)
        )
        GROUP BY other_user_id
        ORDER BY last_message_time DESC
        LIMIT %d",
        $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $limit
    ));
    
    // Add user data to each conversation
    foreach ($conversations as &$conversation) {
        $conversation->user = get_userdata($conversation->other_user_id);
    }
    
    // Cache for 1 minute
    set_transient($cache_key, $conversations, MINUTE_IN_SECONDS);
    
    return $conversations;
}

/**
 * Mark all messages in a conversation as read
 *
 * @param int $user_id Current user ID (recipient)
 * @param int $other_user_id Other user ID (sender)
 * @return bool Success status
 */
function vh360_mark_messages_read($user_id, $other_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} 
        SET read_at = %s 
        WHERE recipient_id = %d 
        AND sender_id = %d 
        AND read_at IS NULL
        AND deleted_by_recipient = 0",
        current_time('mysql'),
        $user_id,
        $other_user_id
    ));
    
    // Clear cache
    delete_transient('vh360_dm_conversations_' . $user_id);
    delete_transient('vh360_dm_unread_count_' . $user_id);
    
    return $result !== false;
}

/**
 * Get total unread message count for a user
 *
 * @param int $user_id User ID
 * @return int Unread message count
 */
function vh360_get_unread_messages_count($user_id) {
    global $wpdb;
    
    // Check cache
    $cache_key = 'vh360_dm_unread_count_' . $user_id;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
        FROM {$table_name} 
        WHERE recipient_id = %d 
        AND read_at IS NULL
        AND deleted_by_recipient = 0",
        $user_id
    ));
    
    $count = absint($count);
    
    // Cache for 1 minute
    set_transient($cache_key, $count, MINUTE_IN_SECONDS);
    
    return $count;
}

/**
 * Soft delete a conversation for a user
 *
 * @param int $user_id User ID
 * @param int $other_user_id Other user ID
 * @return bool Success status
 */
function vh360_delete_conversation($user_id, $other_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    
    // Mark as deleted for sent messages
    $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} 
        SET deleted_by_sender = 1 
        WHERE sender_id = %d 
        AND recipient_id = %d",
        $user_id,
        $other_user_id
    ));
    
    // Mark as deleted for received messages
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE {$table_name} 
        SET deleted_by_recipient = 1 
        WHERE recipient_id = %d 
        AND sender_id = %d",
        $user_id,
        $other_user_id
    ));
    
    // Clear cache
    delete_transient('vh360_dm_conversations_' . $user_id);
    delete_transient('vh360_dm_unread_count_' . $user_id);
    
    return $result !== false;
}

/**
 * Get URL to message a specific user
 *
 * @param int $user_id User ID to message
 * @return string URL to dashboard messages with user parameter
 */
function vh360_get_dm_url($user_id) {
    // Find dashboard page
    $dashboard_page = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'template-dashboard.php',
        'number' => 1,
    ));
    
    if (empty($dashboard_page)) {
        return home_url();
    }
    
    $url = get_permalink($dashboard_page[0]->ID);
    return add_query_arg(array(
        'tab' => 'messages',
        'user' => $user_id,
    ), $url);
}

/**
 * Cleanup old messages based on retention setting
 */
function vh360_cleanup_old_messages() {
    global $wpdb;
    
    $settings = vh360_get_dm_settings();
    $retention_days = isset($settings['retention_days']) ? absint($settings['retention_days']) : 0;
    
    // If 0, keep messages forever
    if ($retention_days === 0) {
        return;
    }
    
    $table_name = $wpdb->prefix . 'vh360_direct_messages';
    
    // Delete messages older than retention period that are deleted by both parties
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        AND (deleted_by_sender = 1 OR deleted_by_recipient = 1)",
        $retention_days
    ));
}
