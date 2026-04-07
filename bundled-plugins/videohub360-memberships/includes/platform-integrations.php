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
 * Activity Feed Access Integration
 *
 * Checks membership before displaying activity feed content.
 */
add_action('template_redirect', 'vh360_memberships_check_activity_feed_access', 5);
function vh360_memberships_check_activity_feed_access() {
    // Only check on activity feed template
    if (!is_page_template('template-activity-feed.php')) {
        return;
    }
    
    if (!function_exists('vh360_can_access_membership_feature')) {
        return; // Membership system not active
    }
    
    $user_id = get_current_user_id();
    
    if (!vh360_can_access_membership_feature('activity_feed', $user_id)) {
        // Get options for redirect or message
        $options = get_option('vh360_membership_options', array());
        $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : home_url('/');
        
        // Display membership gate
        add_filter('the_content', 'vh360_memberships_activity_feed_gate_content', 999);
        
        // Remove the actual feed from rendering
        remove_action('vh360_activity_feed_content', 'vh360_render_activity_feed_content');
    }
}

/**
 * Render activity feed membership gate
 */
function vh360_memberships_activity_feed_gate_content($content) {
    if (!is_page_template('template-activity-feed.php')) {
        return $content;
    }
    
    $options = get_option('vh360_membership_options', array());
    $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : home_url('/');
    $custom_message = isset($options['locked_message']) ? $options['locked_message'] : '';
    
    ob_start();
    ?>
    <div class="vh360-membership-gate vh360-membership-upgrade-required" style="text-align: center; padding: 60px 20px;">
        <div class="vh360-membership-gate-content" style="max-width: 500px; margin: 0 auto;">
            <svg class="vh360-membership-gate-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 20px;">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
            </svg>
            <h3 style="font-size: 24px; margin-bottom: 15px;"><?php esc_html_e('Premium Feature', 'videohub360-memberships'); ?></h3>
            <?php if ($custom_message) : ?>
                <div class="vh360-membership-custom-message" style="margin-bottom: 20px;">
                    <?php echo wp_kses_post($custom_message); ?>
                </div>
            <?php else : ?>
                <p style="margin-bottom: 20px; color: #666;"><?php esc_html_e('The activity feed requires an active membership to access.', 'videohub360-memberships'); ?></p>
            <?php endif; ?>
            <?php if ($pricing_url) : ?>
                <a href="<?php echo esc_url($pricing_url); ?>" class="vh360-membership-gate-button" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px;">
                    <?php esc_html_e('View Membership Plans', 'videohub360-memberships'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Members Directory Integration
 *
 * Checks membership and optionally limits results for non-members.
 */
add_action('template_redirect', 'vh360_memberships_check_directory_access', 5);
function vh360_memberships_check_directory_access() {
    // Only check on members directory template
    if (!is_page_template('template-members-directory.php')) {
        return;
    }
    
    if (!function_exists('vh360_can_access_membership_feature')) {
        return; // Membership system not active
    }
    
    $user_id = get_current_user_id();
    
    // Check if members_directory requires membership
    if (!vh360_can_access_membership_feature('members_directory', $user_id)) {
        // Option 1: Block entirely (uncomment to enable)
        // add_filter('the_content', 'vh360_memberships_directory_gate_content', 999);
        
        // Option 2: Limit results (default)
        add_filter('vh360_members_directory_query_number', function($number) {
            return 5; // Show only 5 members
        });
        
        add_action('vh360_members_directory_after_results', 'vh360_memberships_directory_upgrade_notice');
    }
}

/**
 * Display upgrade notice after limited directory results
 */
function vh360_memberships_directory_upgrade_notice() {
    $options = get_option('vh360_membership_options', array());
    $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : home_url('/');
    ?>
    <div class="vh360-membership-upgrade-notice" style="text-align: center; padding: 40px 20px; border-top: 1px solid #ddd; margin-top: 30px;">
        <h4 style="font-size: 18px; margin-bottom: 10px;"><?php esc_html_e('View Full Directory', 'videohub360-memberships'); ?></h4>
        <p style="margin-bottom: 20px; color: #666;"><?php esc_html_e('Upgrade to view all members in the directory.', 'videohub360-memberships'); ?></p>
        <?php if ($pricing_url) : ?>
            <a href="<?php echo esc_url($pricing_url); ?>" class="button" style="display: inline-block; padding: 10px 25px;">
                <?php esc_html_e('Upgrade Now', 'videohub360-memberships'); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
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
 * Appointment Join Integration
 *
 * Checks membership before allowing users to join appointment rooms.
 */
add_filter('vh360_can_user_join_appointment_room', 'vh360_memberships_check_appointment_join', 10, 3);
function vh360_memberships_check_appointment_join($can_join, $live_room_id, $user_id) {
    if (!$can_join) {
        return false; // Already blocked by other checks
    }
    
    if (!function_exists('vh360_can_access_membership_feature')) {
        return $can_join; // Membership system not active
    }
    
    // Check if user has membership access to appointments
    if (!vh360_can_access_membership_feature('appointments', $user_id)) {
        return false;
    }
    
    return $can_join;
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
