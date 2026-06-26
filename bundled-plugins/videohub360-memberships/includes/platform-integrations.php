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
 * Get cached membership options to avoid repeated database queries.
 *
 * @return array Membership options array
 */
function vh360_get_cached_membership_options() {
    static $cached_options = null;
    
    if ($cached_options === null) {
        $cached_options = get_option('vh360_membership_options', array());
    }
    
    return $cached_options;
}

/**
 * Dashboard Tab Visibility Integration
 *
 * Gates dashboard tabs based on membership access.
 * Also adds a "Membership" tab for subscription management.
 */
add_filter('vh360_dashboard_tabs_registry', 'vh360_memberships_gate_dashboard_tabs', 10, 1);
function vh360_memberships_gate_dashboard_tabs($tabs) {
    // Add Membership management tab
    $tabs['membership'] = array(
        'label' => __('Membership', 'videohub360-memberships'),
        'label_callback' => null,
        'show_callback' => function($user_id) {
            $options = get_option('vh360_membership_options', array());
            return !empty($options['enable_memberships']);
        },
        'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>',
        'content_callback' => function() {
            if (class_exists('VH360_Membership_Subscription_Management')) {
                $manager = VH360_Membership_Subscription_Management::get_instance();
                echo $manager->render_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode handles its own escaping
            }
        },
    );
    
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
 * These filters are now option-aware based on backend settings.
 */
add_filter('vh360_feature_live_rooms_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_live_rooms'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_direct_messages_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_direct_messages'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_activity_feed_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_activity_feed'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_members_directory_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_members_directory'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    // (non-members get limited results via query filter)
    return array('any');
});

add_filter('vh360_feature_appointments_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_appointments'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_push_notifications_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_push_notifications'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

/**
 * Define Feature Plan Requirements for Creation Actions
 *
 * These filters control membership requirements for frontend dashboard
 * content creation features.
 */
add_filter('vh360_feature_create_videos_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_create_videos'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_create_posts_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_create_posts'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_create_events_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_create_events'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_create_bulletins_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_create_bulletins'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

add_filter('vh360_feature_create_galleries_required_plans', function($plans) {
    $options = vh360_get_cached_membership_options();
    
    // If memberships disabled globally, return empty array (no restriction)
    if (empty($options['enable_memberships'])) {
        return array();
    }
    
    // If memberships enabled but this feature gate is off, return empty array
    if (empty($options['gate_create_galleries'])) {
        return array();
    }
    
    // Feature gate is on - require any active membership
    return array('any');
});

