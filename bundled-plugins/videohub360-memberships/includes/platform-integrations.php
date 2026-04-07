<?php
/**
 * Platform Feature Integrations
 *
 * Production integrations for membership checks across VH360 platform features.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Dashboard Tab Visibility Integration
 *
 * Gates dashboard tabs based on membership access.
 */
add_filter('vh360_dashboard_tabs_registry', 'vh360_memberships_gate_dashboard_tabs', 10, 1);
function vh360_memberships_gate_dashboard_tabs($tabs) {
    // Gate "Live Rooms" tab to members with live_rooms feature
    if (isset($tabs['live-rooms'])) {
        $tabs['live-rooms']['show_callback'] = function($user_id) {
            if (!function_exists('vh360_can_access_membership_feature')) {
                return true; // Membership system not active
            }
            return vh360_can_access_membership_feature('live_rooms', $user_id);
        };
    }
    
    // Gate "Push Notifications" to members with push notifications AND capability
    if (isset($tabs['push-notifications'])) {
        $original_callback = isset($tabs['push-notifications']['show_callback']) 
            ? $tabs['push-notifications']['show_callback'] 
            : '__return_true';
            
        $tabs['push-notifications']['show_callback'] = function($user_id) use ($original_callback) {
            // First check original capability requirement
            if (is_callable($original_callback)) {
                if (!call_user_func($original_callback, $user_id)) {
                    return false;
                }
            }
            
            // Then check membership
            if (!function_exists('vh360_can_access_membership_feature')) {
                return true; // Membership system not active
            }
            return vh360_can_access_membership_feature('push_notifications', $user_id);
        };
    }
    
    return $tabs;
}

/**
 * Direct Messaging Integration
 *
 * Checks membership before allowing message sending.
 */
add_action('vh360_dm_before_send_message', 'vh360_memberships_check_direct_message', 10, 2);
function vh360_memberships_check_direct_message($sender_id, $recipient_id) {
    if (!function_exists('vh360_can_access_membership_feature')) {
        return; // Membership system not active
    }
    
    if (!vh360_can_access_membership_feature('direct_messages', $sender_id)) {
        wp_send_json_error(array(
            'message' => __('Sending direct messages requires an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Live Room Creation Integration
 *
 * Checks membership before allowing live room creation via availability system.
 */
add_action('vh360_before_create_live_room_post', 'vh360_memberships_check_live_room_creation', 10, 1);
function vh360_memberships_check_live_room_creation($user_id) {
    if (!function_exists('vh360_can_access_membership_feature')) {
        return; // Membership system not active
    }
    
    if (!vh360_can_access_membership_feature('live_rooms', $user_id)) {
        wp_send_json_error(array(
            'message' => __('Creating live rooms requires an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Define Feature Plan Requirements
 *
 * Specify which membership plans grant access to which features.
 */
add_filter('vh360_feature_live_rooms_required_plans', function($plans) {
    // Allow any active membership to access live rooms
    return array('any');
});

add_filter('vh360_feature_direct_messages_required_plans', function($plans) {
    // Allow any active membership for direct messages
    return array('any');
});

add_filter('vh360_feature_activity_feed_required_plans', function($plans) {
    // Allow any active membership for activity feed
    return array('any');
});

add_filter('vh360_feature_members_directory_required_plans', function($plans) {
    // Allow any active membership for full directory access
    // (non-members get limited results via query filter)
    return array('any');
});

add_filter('vh360_feature_appointments_required_plans', function($plans) {
    // Allow any active membership for appointments
    return array('any');
});

add_filter('vh360_feature_push_notifications_required_plans', function($plans) {
    // Allow any active membership for push notifications
    return array('any');
});
