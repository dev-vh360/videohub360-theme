<?php
/**
 * Notification Helper Functions
 *
 * Public API functions for creating and managing notifications.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a notification
 *
 * @param int $user_id Recipient user ID
 * @param string $type Notification type (follow, like, comment, mention, reply)
 * @param int $actor_id Actor user ID (who triggered the notification)
 * @param int $object_id Related object ID (post_id, comment_id, etc.)
 * @param string $object_type Object type (post, comment, video, etc.)
 * @param string $content Optional notification message
 * @return int|false Notification ID on success, false on failure
 */
function vh360_create_notification($user_id, $type, $actor_id, $object_id, $object_type, $content = '') {
    $system = VH360_Notification_System::get_instance();
    return $system->create_notification($user_id, $type, $actor_id, $object_id, $object_type, $content);
}

/**
 * Get notifications for a user
 *
 * @param int $user_id User ID
 * @param array $args Optional query arguments
 * @return array Array of notification objects
 */
function vh360_get_notifications($user_id, $args = array()) {
    $system = VH360_Notification_System::get_instance();
    
    // Check transient cache first
    $cache_key = 'vh360_notifications_' . $user_id;
    $cached = get_transient($cache_key);
    
    if (false !== $cached && empty($args)) {
        return $cached;
    }
    
    $notifications = $system->get_notifications($user_id, $args);
    
    // Cache for 5 minutes if no custom args
    if (empty($args)) {
        set_transient($cache_key, $notifications, 5 * MINUTE_IN_SECONDS);
    }
    
    return $notifications;
}

/**
 * Get unread notification count for a user
 *
 * @param int $user_id User ID
 * @return int Count of unread notifications
 */
function vh360_get_unread_notification_count($user_id) {
    $system = VH360_Notification_System::get_instance();
    return $system->get_unread_count_cached($user_id);
}

/**
 * Mark notification as read
 *
 * @param int $notification_id Notification ID
 * @return bool Success status
 */
function vh360_mark_notification_read($notification_id) {
    $system = VH360_Notification_System::get_instance();
    return $system->mark_as_read($notification_id);
}

/**
 * Mark all notifications as read for a user
 *
 * @param int $user_id User ID
 * @return bool Success status
 */
function vh360_mark_all_notifications_read($user_id) {
    $system = VH360_Notification_System::get_instance();
    return $system->mark_all_as_read($user_id);
}

/**
 * Delete a notification
 *
 * @param int $notification_id Notification ID
 * @return bool Success status
 */
function vh360_delete_notification($notification_id) {
    $system = VH360_Notification_System::get_instance();
    return $system->delete_notification($notification_id);
}

/**
 * Format notification for display
 *
 * @param object $notification Notification object
 * @return array Formatted notification data
 */
function vh360_format_notification($notification) {
    if (!$notification) {
        return null;
    }
    
    $actor = get_userdata($notification->actor_id);
    $actor_name = $actor ? $actor->display_name : __('Someone', 'videohub360-theme');
    
    // Get profile URL if function exists, otherwise use author archive
    if (function_exists('vh360_get_profile_url')) {
        $actor_url = $actor ? vh360_get_profile_url($notification->actor_id) : '#';
    } else {
        $actor_url = $actor ? get_author_posts_url($notification->actor_id) : '#';
    }
    
    $actor_avatar = $actor ? get_avatar_url($notification->actor_id, array('size' => 40)) : '';
    
    $time_ago = human_time_diff(strtotime($notification->created_at), current_time('timestamp'));
    
    // Build notification message and link based on type
    $message = '';
    $link = '#';
    
    switch ($notification->type) {
        case 'follow':
            $message = sprintf(
                /* translators: %s: actor name */
                __('%s started following you', 'videohub360-theme'),
                '<strong>' . esc_html($actor_name) . '</strong>'
            );
            $link = $actor_url;
            break;
            
        case 'like':
            $post = get_post($notification->object_id);
            if ($post) {
                $message = sprintf(
                    /* translators: %s: actor name */
                    __('%s liked your post', 'videohub360-theme'),
                    '<strong>' . esc_html($actor_name) . '</strong>'
                );
                $link = get_permalink($post->ID);
            }
            break;
            
        case 'comment':
            $post = get_post($notification->object_id);
            if ($post) {
                $message = sprintf(
                    /* translators: %s: actor name */
                    __('%s commented on your post', 'videohub360-theme'),
                    '<strong>' . esc_html($actor_name) . '</strong>'
                );
                $link = get_permalink($post->ID);
            }
            break;
            
        case 'mention':
            if ($notification->object_type === 'post') {
                $post = get_post($notification->object_id);
                if ($post) {
                    $message = sprintf(
                        /* translators: %s: actor name */
                        __('%s mentioned you in a post', 'videohub360-theme'),
                        '<strong>' . esc_html($actor_name) . '</strong>'
                    );
                    $link = get_permalink($post->ID);
                }
            } elseif ($notification->object_type === 'comment') {
                $comment = get_comment($notification->object_id);
                if ($comment) {
                    $message = sprintf(
                        /* translators: %s: actor name */
                        __('%s mentioned you in a comment', 'videohub360-theme'),
                        '<strong>' . esc_html($actor_name) . '</strong>'
                    );
                    $link = get_permalink($comment->comment_post_ID);
                }
            }
            break;
            
        case 'reply':
            $comment = get_comment($notification->object_id);
            if ($comment) {
                $message = sprintf(
                    /* translators: %s: actor name */
                    __('%s replied to your comment', 'videohub360-theme'),
                    '<strong>' . esc_html($actor_name) . '</strong>'
                );
                $link = get_permalink($comment->comment_post_ID);
            }
            break;
            
        default:
            $message = !empty($notification->content) ? $notification->content : __('New notification', 'videohub360-theme');
            break;
    }
    
    return array(
        'id' => $notification->id,
        'message' => $message,
        'link' => $link,
        'actor_id' => $notification->actor_id,
        'actor_name' => $actor_name,
        'actor_avatar' => $actor_avatar,
        'actor_url' => $actor_url,
        'time_ago' => $time_ago,
        'is_read' => (bool) $notification->is_read,
        'created_at' => $notification->created_at,
        'type' => $notification->type,
    );
}

/**
 * Get notifications with advanced filtering
 *
 * @param int $user_id User ID
 * @param array $args Query arguments
 * @return array Array of notification objects
 */
function vh360_get_notifications_filtered($user_id, $args = array()) {
    $system = VH360_Notification_System::get_instance();
    
    $defaults = array(
        'limit' => 20,
        'offset' => 0,
        'type' => null,
        'is_read' => null,
        'date_from' => null,
        'date_to' => null,
        'days' => null,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    return $system->get_notifications($user_id, $args);
}

/**
 * Get notification count by type
 *
 * @param int $user_id User ID
 * @param string $type Notification type
 * @return int Count of notifications
 */
function vh360_get_notification_count_by_type($user_id, $type) {
    global $wpdb;
    $system = VH360_Notification_System::get_instance();
    $table_name = $system->get_table_name();
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
        WHERE user_id = %d AND type = %s
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
        $user_id,
        $type
    ));
    
    return (int) $count;
}

/**
 * Delete read notifications for a user
 *
 * @param int $user_id User ID
 * @return bool|int Number of rows deleted or false on error
 */
function vh360_delete_read_notifications($user_id) {
    global $wpdb;
    $system = VH360_Notification_System::get_instance();
    $table_name = $system->get_table_name();
    
    $result = $wpdb->delete(
        $table_name,
        array(
            'user_id' => $user_id,
            'is_read' => 1,
        ),
        array('%d', '%d')
    );
    
    // Update cache
    if (false !== $result) {
        $system->update_unread_count_cache($user_id);
        delete_transient('vh360_notifications_' . $user_id);
    }
    
    return $result;
}

/**
 * Clear all notifications for a user
 *
 * @param int $user_id User ID
 * @return bool|int Number of rows deleted or false on error
 */
function vh360_clear_all_notifications($user_id) {
    global $wpdb;
    $system = VH360_Notification_System::get_instance();
    $table_name = $system->get_table_name();
    
    $result = $wpdb->delete(
        $table_name,
        array('user_id' => $user_id),
        array('%d')
    );
    
    // Update cache
    if (false !== $result) {
        $system->update_unread_count_cache($user_id);
        delete_transient('vh360_notifications_' . $user_id);
    }
    
    return $result;
}

/**
 * Get notification statistics for a user
 *
 * @param int $user_id User ID
 * @return array Statistics array
 */
function vh360_get_notification_stats($user_id) {
    global $wpdb;
    $system = VH360_Notification_System::get_instance();
    $table_name = $system->get_table_name();
    
    // Get total counts
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name}
        WHERE user_id = %d
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
        $user_id
    ));
    
    $unread = $system->get_unread_count($user_id);
    $read = $total - $unread;
    
    // Get counts by type
    $type_counts = $wpdb->get_results($wpdb->prepare(
        "SELECT type, COUNT(*) as count
        FROM {$table_name}
        WHERE user_id = %d
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY type",
        $user_id
    ), OBJECT_K);
    
    $stats = array(
        'total' => (int) $total,
        'unread' => (int) $unread,
        'read' => (int) $read,
        'by_type' => array(),
    );
    
    foreach ($type_counts as $type => $data) {
        $stats['by_type'][$type] = (int) $data->count;
    }
    
    return $stats;
}

/**
 * Get SVG icon for notification type
 *
 * @param string $type Notification type
 * @return string SVG icon markup
 */
function vh360_get_notification_icon($type) {
    $icons = array(
        'follow' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>',
        'like' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
        'comment' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
        'reply' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>',
        'mention' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path></svg>',
        'share' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>',
    );
    
    return isset($icons[$type]) ? $icons[$type] : $icons['comment'];
}
