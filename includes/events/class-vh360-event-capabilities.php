<?php
/**
 * Event Capabilities Management
 *
 * Handles user permissions for event creation and management.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Event_Capabilities
 *
 * Manages user capabilities for events.
 */
class VH360_Event_Capabilities {

    /**
     * Singleton instance.
     *
     * @var VH360_Event_Capabilities|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return VH360_Event_Capabilities
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_filter('map_meta_cap', array($this, 'map_event_meta_caps'), 10, 4);
    }

    /**
     * Map meta capabilities for events.
     *
     * @param array  $caps    Required capabilities.
     * @param string $cap     Capability being checked.
     * @param int    $user_id User ID.
     * @param array  $args    Additional arguments.
     * @return array Modified capabilities.
     */
    public function map_event_meta_caps($caps, $cap, $user_id, $args) {
        // Check for event-specific capabilities
        if ('edit_vh360_event' === $cap || 'delete_vh360_event' === $cap || 'read_vh360_event' === $cap) {
            $post = get_post($args[0]);
            
            if (!$post || 'vh360_event' !== $post->post_type) {
                return $caps;
            }
            
            $post_type = get_post_type_object($post->post_type);
            
            // Allow editing own events
            if ('edit_vh360_event' === $cap) {
                if ((int) $user_id === (int) $post->post_author) {
                    $caps = array($post_type->cap->edit_posts);
                } else {
                    $caps = array($post_type->cap->edit_others_posts);
                }
            }
            
            // Allow deleting own events
            if ('delete_vh360_event' === $cap) {
                if ((int) $user_id === (int) $post->post_author) {
                    $caps = array($post_type->cap->delete_posts);
                } else {
                    $caps = array($post_type->cap->delete_others_posts);
                }
            }
            
            // Allow reading published events
            if ('read_vh360_event' === $cap) {
                if ('publish' === $post->post_status) {
                    $caps = array('read');
                } elseif ((int) $user_id === (int) $post->post_author) {
                    $caps = array('read');
                } else {
                    $caps = array($post_type->cap->read_private_posts);
                }
            }
        }
        
        return $caps;
    }

    /**
     * Check if user can edit specific event.
     *
     * @param int $event_id Event post ID.
     * @param int $user_id  User ID. Defaults to current user.
     * @return bool True if user can edit event.
     */
    public static function can_edit_event($event_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $event = get_post($event_id);
        
        if (!$event || 'vh360_event' !== $event->post_type) {
            return false;
        }
        
        // Allow administrators
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Allow event author
        if ((int) $user_id === (int) $event->post_author) {
            return true;
        }
        
        // Allow users with edit_others_posts capability
        if (user_can($user_id, 'edit_others_posts')) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can delete specific event.
     *
     * @param int $event_id Event post ID.
     * @param int $user_id  User ID. Defaults to current user.
     * @return bool True if user can delete event.
     */
    public static function can_delete_event($event_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $event = get_post($event_id);
        
        if (!$event || 'vh360_event' !== $event->post_type) {
            return false;
        }
        
        // Allow administrators
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Allow event author
        if ((int) $user_id === (int) $event->post_author) {
            return true;
        }
        
        // Allow users with delete_others_posts capability
        if (user_can($user_id, 'delete_others_posts')) {
            return true;
        }
        
        return false;
    }
}

// Initialize the class
VH360_Event_Capabilities::get_instance();
