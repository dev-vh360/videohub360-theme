<?php
/**
 * Direct Messages Notification Integration
 *
 * Integrates direct messaging with the notification system.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_DM_Notifications
 */
class VH360_DM_Notifications {
    
    /**
     * Singleton instance
     *
     * @var VH360_DM_Notifications
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_DM_Notifications
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
        add_action('vh360_message_sent', array($this, 'create_message_notification'), 10, 4);
    }
    
    /**
     * Create notification when a message is sent
     *
     * @param int $message_id Message ID
     * @param int $sender_id Sender user ID
     * @param int $recipient_id Recipient user ID
     * @param string $message Message content
     */
    public function create_message_notification($message_id, $sender_id, $recipient_id, $message) {
        // Check if vh360_create_notification function exists
        if (!function_exists('vh360_create_notification')) {
            return;
        }
        
        // Get sender information
        $sender = get_userdata($sender_id);
        if (!$sender) {
            return;
        }
        
        // Create message preview (first 50 characters)
        $message_preview = wp_trim_words($message, 10, '...');
        
        // Create notification content
        $content = sprintf(
            /* translators: 1: sender display name, 2: message preview */
            __('%1$s sent you a message: "%2$s"', 'videohub360-theme'),
            esc_html($sender->display_name),
            esc_html($message_preview)
        );
        
        // Create the notification
        vh360_create_notification(
            $recipient_id,
            'message',
            $sender_id,
            $message_id,
            'direct_message',
            $content
        );
    }
}

// Initialize the class
VH360_DM_Notifications::get_instance();
