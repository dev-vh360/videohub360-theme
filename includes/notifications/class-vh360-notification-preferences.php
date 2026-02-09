<?php
/**
 * VH360 Notification Preferences Management Class
 *
 * Handles user notification preferences and checks before creating notifications.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Notification_Preferences
 */
class VH360_Notification_Preferences {
    
    /**
     * Singleton instance
     *
     * @var VH360_Notification_Preferences
     */
    private static $instance = null;
    
    /**
     * Default notification types
     *
     * @var array
     */
    private $notification_types = array(
        'follow',
        'like',
        'comment',
        'reply',
        'mention',
        'share',
        'message',
    );
    
    /**
     * Get singleton instance
     *
     * @return VH360_Notification_Preferences
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
        // Hook to check preferences before notification creation
        add_filter('vh360_should_create_notification', array($this, 'check_preferences'), 10, 3);
    }
    
    /**
     * Get default preferences
     *
     * @return array
     */
    public function get_default_preferences() {
        $defaults = array(
            'enabled' => true,
            'frequency' => 'all', // all, important_only, digest_only
            'types' => array(),
            'display' => array(
                'sound' => true,
                'desktop' => true,
                'max_keep' => 100,
            ),
        );
        
        // Enable all types by default
        foreach ($this->notification_types as $type) {
            $defaults['types'][$type] = true;
        }
        
        return $defaults;
    }
    
    /**
     * Get user preferences
     *
     * @param int $user_id User ID
     * @return array User notification preferences
     */
    public function get_preferences($user_id) {
        $preferences = get_user_meta($user_id, '_vh360_notification_preferences', true);
        
        if (empty($preferences) || !is_array($preferences)) {
            $preferences = $this->get_default_preferences();
        } else {
            // Merge with defaults to ensure all keys exist
            $preferences = wp_parse_args($preferences, $this->get_default_preferences());
        }
        
        return $preferences;
    }
    
    /**
     * Update user preferences
     *
     * @param int $user_id User ID
     * @param array $preferences Preferences array
     * @return bool Success status
     */
    public function update_preferences($user_id, $preferences) {
        // Validate preferences
        $validated = $this->validate_preferences($preferences);
        
        if (false === $validated) {
            return false;
        }
        
        // Update user meta
        $result = update_user_meta($user_id, '_vh360_notification_preferences', $validated);
        
        return false !== $result;
    }
    
    /**
     * Validate preferences
     *
     * @param array $preferences Preferences to validate
     * @return array|false Validated preferences or false on error
     */
    private function validate_preferences($preferences) {
        $defaults = $this->get_default_preferences();
        $validated = array();
        
        // Validate enabled
        $validated['enabled'] = isset($preferences['enabled']) ? (bool) $preferences['enabled'] : true;
        
        // Validate frequency
        $valid_frequencies = array('all', 'important_only', 'digest_only');
        $validated['frequency'] = isset($preferences['frequency']) && in_array($preferences['frequency'], $valid_frequencies)
            ? $preferences['frequency']
            : 'all';
        
        // Validate types
        $validated['types'] = array();
        if (isset($preferences['types']) && is_array($preferences['types'])) {
            foreach ($this->notification_types as $type) {
                $validated['types'][$type] = isset($preferences['types'][$type]) ? (bool) $preferences['types'][$type] : true;
            }
        } else {
            $validated['types'] = $defaults['types'];
        }
        
        // Validate display settings
        $validated['display'] = array();
        $validated['display']['sound'] = isset($preferences['display']['sound']) ? (bool) $preferences['display']['sound'] : true;
        $validated['display']['desktop'] = isset($preferences['display']['desktop']) ? (bool) $preferences['display']['desktop'] : true;
        $validated['display']['max_keep'] = isset($preferences['display']['max_keep']) ? absint($preferences['display']['max_keep']) : 100;
        
        // Ensure max_keep is within reasonable bounds
        if ($validated['display']['max_keep'] < 10) {
            $validated['display']['max_keep'] = 10;
        } elseif ($validated['display']['max_keep'] > 1000) {
            $validated['display']['max_keep'] = 1000;
        }
        
        return $validated;
    }
    
    /**
     * Check if notification should be created based on user preferences
     *
     * @param bool $should_create Current decision
     * @param int $user_id Recipient user ID
     * @param string $type Notification type
     * @return bool Whether notification should be created
     */
    public function check_preferences($should_create, $user_id, $type) {
        if (!$should_create) {
            return false;
        }
        
        $preferences = $this->get_preferences($user_id);
        
        // Check if notifications are globally disabled
        if (!$preferences['enabled']) {
            return false;
        }
        
        // Check if this type is enabled
        if (isset($preferences['types'][$type]) && !$preferences['types'][$type]) {
            return false;
        }
        
        // Check frequency setting
        if ($preferences['frequency'] === 'important_only') {
            // Only allow important notifications (follows, mentions, replies)
            $important_types = array('follow', 'mention', 'reply');
            if (!in_array($type, $important_types)) {
                return false;
            }
        } elseif ($preferences['frequency'] === 'digest_only') {
            // Digest only - no immediate notifications (would be handled by cron)
            return false;
        }
        
        return true;
    }
    
    /**
     * Get notification types
     *
     * @return array
     */
    public function get_notification_types() {
        return $this->notification_types;
    }
    
    /**
     * Check if user has reached max notifications
     *
     * @param int $user_id User ID
     * @return bool True if at max, false otherwise
     */
    public function has_reached_max_notifications($user_id) {
        $preferences = $this->get_preferences($user_id);
        $max_keep = $preferences['display']['max_keep'];
        
        $system = VH360_Notification_System::get_instance();
        $count = $system->get_total_count($user_id);
        
        return $count >= $max_keep;
    }
    
    /**
     * Clean up old notifications if max is reached
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function cleanup_old_notifications($user_id) {
        $preferences = $this->get_preferences($user_id);
        $max_keep = $preferences['display']['max_keep'];
        
        global $wpdb;
        $system = VH360_Notification_System::get_instance();
        $table_name = $system->get_table_name();
        
        // Get IDs of notifications to delete (read notifications beyond max_keep)
        $ids_to_delete = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table_name}
            WHERE user_id = %d AND is_read = 1
            ORDER BY created_at DESC
            LIMIT 18446744073709551615 OFFSET %d",
            $user_id,
            $max_keep
        ));
        
        if (empty($ids_to_delete)) {
            return true;
        }
        
        // Delete in batches of 100 to avoid table locks
        $chunks = array_chunk($ids_to_delete, 100);
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table_name} WHERE id IN ({$placeholders})",
                $chunk
            ));
        }
        
        return true;
    }
}

// Initialize the class
VH360_Notification_Preferences::get_instance();
