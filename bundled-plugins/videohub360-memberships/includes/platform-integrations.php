<?php
/**
 * Platform Feature Integrations
 *
 * Example implementations showing how to integrate membership checks
 * with various VH360 platform features.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Example: Add membership check to dashboard tab visibility
 *
 * To gate a dashboard tab, add a filter that modifies the registry:
 */
add_filter('vh360_get_dashboard_tabs_registry', 'vh360_memberships_gate_dashboard_tabs', 10, 1);
function vh360_memberships_gate_dashboard_tabs($tabs) {
    // Example: Gate "Live Rooms" tab to premium members only
    if (isset($tabs['live-rooms'])) {
        $tabs['live-rooms']['show_callback'] = vh360_membership_show_callback(
            'live_rooms',
            '__return_true' // Additional check if needed
        );
    }
    
    // Example: Gate "Push Notifications" to premium members with capability
    if (isset($tabs['push-notifications'])) {
        $tabs['push-notifications']['show_callback'] = vh360_membership_show_callback(
            'push_notifications',
            function($user_id) {
                return current_user_can('vh360_send_push');
            }
        );
    }
    
    return $tabs;
}

/**
 * Example: Add membership check to live room creation
 *
 * Hook into room creation to verify membership
 */
add_action('vh360_before_create_live_room', 'vh360_memberships_check_live_room_creation', 10, 1);
function vh360_memberships_check_live_room_creation($user_id) {
    if (!vh360_can_access_membership_feature('live_rooms', $user_id)) {
        wp_send_json_error(array(
            'message' => __('Live rooms require an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Example: Add membership check to joining live sessions
 *
 * Hook into session join to verify membership
 */
add_action('vh360_before_join_live_session', 'vh360_memberships_check_live_session_join', 10, 2);
function vh360_memberships_check_live_session_join($user_id, $session_id) {
    if (!vh360_can_access_membership_feature('live_sessions', $user_id)) {
        wp_send_json_error(array(
            'message' => __('Joining live sessions requires an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Example: Add membership check to direct message sending
 *
 * Hook into message sending to verify membership
 */
add_action('vh360_before_send_direct_message', 'vh360_memberships_check_direct_message', 10, 2);
function vh360_memberships_check_direct_message($sender_id, $recipient_id) {
    if (!vh360_can_access_membership_feature('direct_messages', $sender_id)) {
        wp_send_json_error(array(
            'message' => __('Sending messages requires an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Example: Add membership check to appointment creation
 *
 * Hook into appointment creation to verify membership
 */
add_action('vh360_before_create_appointment', 'vh360_memberships_check_appointment_creation', 10, 1);
function vh360_memberships_check_appointment_creation($user_id) {
    if (!vh360_can_access_membership_feature('appointments', $user_id)) {
        wp_send_json_error(array(
            'message' => __('Creating appointments requires an active membership.', 'videohub360-memberships')
        ));
    }
}

/**
 * Example: Add membership check to activity feed access
 *
 * Filter activity feed query to restrict access
 */
add_filter('vh360_activity_feed_query_args', 'vh360_memberships_filter_activity_feed', 10, 2);
function vh360_memberships_filter_activity_feed($args, $user_id) {
    if (!vh360_can_access_membership_feature('activity_feed', $user_id)) {
        // Return empty results or show only public activities
        $args['post__in'] = array(0); // No results
    }
    
    return $args;
}

/**
 * Example: Add membership check to members directory access
 *
 * Filter directory query to restrict access
 */
add_filter('vh360_members_directory_query_args', 'vh360_memberships_filter_members_directory', 10, 2);
function vh360_memberships_filter_members_directory($args, $user_id) {
    if (!vh360_can_access_membership_feature('members_directory', $user_id)) {
        // Limit results for non-members
        $args['number'] = 5; // Show only 5 members
        // Or return empty: $args['include'] = array(0);
    }
    
    return $args;
}

/**
 * Define which features require membership plans
 *
 * Use filters to specify which plans grant access to which features
 */
add_filter('vh360_feature_live_rooms_required_plans', function($plans) {
    return array('pro_monthly', 'pro_yearly', 'any'); // Any premium plan
});

add_filter('vh360_feature_live_sessions_required_plans', function($plans) {
    return array('pro_monthly', 'pro_yearly', 'any');
});

add_filter('vh360_feature_direct_messages_required_plans', function($plans) {
    return array('any'); // Any paid membership
});

add_filter('vh360_feature_appointments_required_plans', function($plans) {
    return array('pro_yearly'); // Only yearly plan
});

add_filter('vh360_feature_activity_feed_required_plans', function($plans) {
    return array('any'); // Any paid membership
});

add_filter('vh360_feature_members_directory_required_plans', function($plans) {
    return array(); // No restriction, but limits apply in query filter
});

add_filter('vh360_feature_push_notifications_required_plans', function($plans) {
    return array('pro_monthly', 'pro_yearly');
});

/**
 * Note: These are EXAMPLE integrations showing the pattern.
 * 
 * To actually implement these, you would need to:
 * 1. Add the appropriate action/filter hooks in the feature code
 * 2. Uncomment or activate the specific integrations needed
 * 3. Adjust plan keys to match your actual membership plans
 * 
 * The membership system is ready to integrate - just add the checks
 * at the appropriate points in your platform features.
 */
