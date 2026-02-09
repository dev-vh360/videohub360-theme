<?php
/**
 * VH360 Notification System Core Class
 *
 * Handles database table creation and core notification methods.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Notification_System
 */
class VH360_Notification_System {
    
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Database version
     *
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Singleton instance
     *
     * @var VH360_Notification_System
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Notification_System
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vh360_notifications';
        
        // Hook to create table on theme activation
        add_action('after_switch_theme', array($this, 'create_table'));
        
        // Schedule cleanup cron job
        add_action('after_switch_theme', array($this, 'schedule_cleanup'));
        add_action('switch_theme', array($this, 'unschedule_cleanup'));
        
        // Hook cleanup action
        add_action('vh360_notification_cleanup', array($this, 'cleanup_old_notifications'));
    }
    
    /**
     * Create notifications database table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            actor_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            object_id bigint(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            content text NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Store database version
        update_option('vh360_notifications_db_version', $this->db_version);
    }
    
    /**
     * Get table name
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }
    
    /**
     * Create a notification
     *
     * @param int $user_id Recipient user ID
     * @param string $type Notification type
     * @param int $actor_id Actor user ID (who triggered the notification)
     * @param int $object_id Related object ID
     * @param string $object_type Object type (post, comment, etc.)
     * @param string $content Notification content/message
     * @return int|false Notification ID on success, false on failure
     */
    public function create_notification($user_id, $type, $actor_id, $object_id, $object_type, $content = '') {
        global $wpdb;
        
        // Don't create notification if user is notifying themselves
        if ($user_id == $actor_id) {
            return false;
        }
        
        // Check if notification should be created based on user preferences
        $should_create = apply_filters('vh360_should_create_notification', true, $user_id, $type);
        if (!$should_create) {
            return false;
        }
        
        // Check if similar notification already exists (within last 24 hours)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
            WHERE user_id = %d 
            AND type = %s 
            AND actor_id = %d 
            AND object_id = %d 
            AND object_type = %s 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            LIMIT 1",
            $user_id,
            $type,
            $actor_id,
            $object_id,
            $object_type
        ));
        
        if ($existing) {
            return false; // Don't create duplicate
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'actor_id' => $actor_id,
                'type' => $type,
                'object_id' => $object_id,
                'object_type' => $object_type,
                'content' => $content,
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            $notification_id = $wpdb->insert_id;
            
            // Update unread count cache
            $this->update_unread_count_cache($user_id);
            
            // Fire action hook for integrations (e.g., push notifications)
            do_action('vh360_notification_created', $notification_id, array(
                'user_id'     => $user_id,
                'type'        => $type,
                'actor_id'    => $actor_id,
                'object_id'   => $object_id,
                'object_type' => $object_type,
                'content'     => $content,
                'created_at'  => current_time('mysql'),
            ));
            
            return $notification_id;
        }
        
        return false;
    }
    
    /**
     * Get notifications for a user
     *
     * @param int $user_id User ID
     * @param array $args Query arguments
     * @return array Array of notification objects
     */
    public function get_notifications($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'is_read' => null,
            'type' => null,
            'days' => 30, // Only get notifications from last 30 days by default
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array("user_id = %d");
        $where_values = array($user_id);
        
        if (null !== $args['is_read']) {
            $where[] = "is_read = %d";
            $where_values[] = $args['is_read'] ? 1 : 0;
        }
        
        if (!empty($args['type'])) {
            $where[] = "type = %s";
            $where_values[] = $args['type'];
        }
        
        if ($args['days'] > 0) {
            $where[] = "created_at > DATE_SUB(NOW(), INTERVAL %d DAY)";
            $where_values[] = $args['days'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->table_name} 
                WHERE {$where_sql} 
                ORDER BY created_at DESC 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        $notifications = $wpdb->get_results(
            $wpdb->prepare($sql, $where_values)
        );
        
        return $notifications ? $notifications : array();
    }
    
    /**
     * Get unread notification count for a user
     *
     * @param int $user_id User ID
     * @return int Count of unread notifications
     */
    public function get_unread_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE user_id = %d 
            AND is_read = 0 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $user_id
        ));
        
        return (int) $count;
    }
    
    /**
     * Mark notification as read
     *
     * @param int $notification_id Notification ID
     * @return bool Success status
     */
    public function mark_as_read($notification_id) {
        global $wpdb;
        
        // Get notification to update cache for user
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$this->table_name} WHERE id = %d",
            $notification_id
        ));
        
        if (!$notification) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ),
            array('id' => $notification_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $this->update_unread_count_cache($notification->user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark all notifications as read for a user
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function mark_all_as_read($user_id) {
        global $wpdb;
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
            SET is_read = 1, read_at = %s 
            WHERE user_id = %d AND is_read = 0",
            current_time('mysql'),
            $user_id
        ));
        
        if ($result !== false) {
            $this->update_unread_count_cache($user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete a notification
     *
     * @param int $notification_id Notification ID
     * @return bool Success status
     */
    public function delete_notification($notification_id) {
        global $wpdb;
        
        // Get notification to update cache for user
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$this->table_name} WHERE id = %d",
            $notification_id
        ));
        
        if (!$notification) {
            return false;
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $notification_id),
            array('%d')
        );
        
        if ($result) {
            $this->update_unread_count_cache($notification->user_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update unread count cache for a user
     *
     * @param int $user_id User ID
     */
    private function update_unread_count_cache($user_id) {
        $count = $this->get_unread_count($user_id);
        update_user_meta($user_id, 'vh360_unread_notification_count', $count);
        
        // Delete transient cache
        delete_transient('vh360_notifications_' . $user_id);
    }
    
    /**
     * Get unread count from cache
     *
     * @param int $user_id User ID
     * @return int Unread count
     */
    public function get_unread_count_cached($user_id) {
        $count = get_user_meta($user_id, 'vh360_unread_notification_count', true);
        
        if (false === $count || '' === $count) {
            $count = $this->get_unread_count($user_id);
            update_user_meta($user_id, 'vh360_unread_notification_count', $count);
        }
        
        return (int) $count;
    }
    
    /**
     * Get total notification count for a user
     *
     * @param int $user_id User ID
     * @return int Total count
     */
    public function get_total_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
            WHERE user_id = %d",
            $user_id
        ));
        
        return (int) $count;
    }
    
    /**
     * Schedule cleanup cron job
     */
    public function schedule_cleanup() {
        // Get schedule from settings
        $options = get_option('vh360_notification_options', array());
        $schedule = isset($options['cleanup_schedule']) ? $options['cleanup_schedule'] : 'daily';
        
        // Clear any existing schedule
        $this->unschedule_cleanup();
        
        // Schedule new cron job
        if (!wp_next_scheduled('vh360_notification_cleanup')) {
            wp_schedule_event(time(), $schedule, 'vh360_notification_cleanup');
        }
    }
    
    /**
     * Unschedule cleanup cron job
     */
    public function unschedule_cleanup() {
        $timestamp = wp_next_scheduled('vh360_notification_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vh360_notification_cleanup');
        }
    }
    
    /**
     * Cleanup old notifications (cron callback)
     */
    public function cleanup_old_notifications() {
        global $wpdb;
        
        // Get retention days from settings
        $options = get_option('vh360_notification_options', array());
        $retention_days = isset($options['retention_days']) ? absint($options['retention_days']) : 30;
        
        // Delete old notifications in batches to avoid table locks
        $batch_size = 1000;
        $total_deleted = 0;
        
        do {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY) 
                LIMIT %d",
                $retention_days,
                $batch_size
            ));
            
            if ($deleted > 0) {
                $total_deleted += $deleted;
                // Brief pause between batches to reduce load
                usleep(100000); // 0.1 seconds
            }
        } while ($deleted === $batch_size);
        
        // Log cleanup (optional)
        if (function_exists('error_log') && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'VH360 Notification cleanup completed - deleted %d notifications older than %d days',
                $total_deleted,
                $retention_days
            ));
        }
    }
}
