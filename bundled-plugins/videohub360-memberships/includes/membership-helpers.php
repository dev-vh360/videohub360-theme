<?php
/**
 * Membership Helper Functions
 *
 * Centralized API for membership queries and checks.
 * All theme code should use these helpers instead of direct database queries.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Get user memberships
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $status Filter by status (active, expired, cancelled). Default null = all.
 * @return array Array of membership objects
 */
function vh360_get_user_memberships($user_id = 0, $status = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE user_id = %d",
        $user_id
    );
    
    if ($status) {
        $sql .= $wpdb->prepare(" AND status = %s", $status);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $results = $wpdb->get_results($sql);
    
    return apply_filters('vh360_get_user_memberships', $results, $user_id, $status);
}

/**
 * Check if user has active membership
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $plan_key Optional plan key to check for specific plan.
 * @return bool
 */
function vh360_user_has_active_membership($user_id = 0, $plan_key = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} 
        WHERE user_id = %d 
        AND status = 'active'
        AND (expires_at IS NULL OR expires_at > NOW())",
        $user_id
    );
    
    if ($plan_key) {
        $sql .= $wpdb->prepare(" AND plan_key = %s", $plan_key);
    }
    
    $count = (int) $wpdb->get_var($sql);
    
    return apply_filters('vh360_user_has_active_membership', $count > 0, $user_id, $plan_key);
}

/**
 * Check if user has specific membership plan
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $plan_key Plan key to check
 * @return bool
 */
function vh360_user_has_membership_plan($user_id = 0, $plan_key = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id || !$plan_key) {
        return false;
    }
    
    return vh360_user_has_active_membership($user_id, $plan_key);
}

/**
 * Get user membership status
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $plan_key Optional plan key
 * @return string|false Status string or false if no membership
 */
function vh360_get_user_membership_status($user_id = 0, $plan_key = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    $sql = $wpdb->prepare(
        "SELECT status FROM {$table} WHERE user_id = %d",
        $user_id
    );
    
    if ($plan_key) {
        $sql .= $wpdb->prepare(" AND plan_key = %s", $plan_key);
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 1";
    
    $status = $wpdb->get_var($sql);
    
    return apply_filters('vh360_get_user_membership_status', $status, $user_id, $plan_key);
}

/**
 * Check if user can access membership feature
 *
 * @param string $feature_key Feature identifier
 * @param int $user_id User ID. Defaults to current user.
 * @return bool
 */
function vh360_can_access_membership_feature($feature_key, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admins always have access
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check if feature requires membership
    $required_plans = apply_filters("vh360_feature_{$feature_key}_required_plans", array());
    
    if (empty($required_plans)) {
        // Feature doesn't require membership
        return true;
    }
    
    // Check if user has any of the required plans
    foreach ($required_plans as $plan_key) {
        if (vh360_user_has_membership_plan($user_id, $plan_key)) {
            return true;
        }
    }
    
    // Check if user has any active membership (for features requiring any paid plan)
    if (in_array('any', $required_plans, true)) {
        return vh360_user_has_active_membership($user_id);
    }
    
    return apply_filters('vh360_can_access_membership_feature', false, $feature_key, $user_id);
}

/**
 * Check if post requires membership
 *
 * @param int $post_id Post ID
 * @return bool|string False if no membership required, plan key if required
 */
function vh360_post_requires_membership($post_id) {
    $required_plan = get_post_meta($post_id, '_vh360_membership_required', true);
    
    if (empty($required_plan)) {
        return false;
    }
    
    return apply_filters('vh360_post_requires_membership', $required_plan, $post_id);
}

/**
 * Get active membership for user
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return object|false Membership object or false if no active membership
 */
function vh360_get_active_membership($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    $membership = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} 
        WHERE user_id = %d 
        AND status = 'active'
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
        LIMIT 1",
        $user_id
    ));
    
    return apply_filters('vh360_get_active_membership', $membership, $user_id);
}

/**
 * Get membership expiration date
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $plan_key Optional plan key
 * @return string|false Expiration date or false
 */
function vh360_get_membership_expiration($user_id = 0, $plan_key = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    $sql = $wpdb->prepare(
        "SELECT expires_at FROM {$table} 
        WHERE user_id = %d 
        AND status = 'active'",
        $user_id
    );
    
    if ($plan_key) {
        $sql .= $wpdb->prepare(" AND plan_key = %s", $plan_key);
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 1";
    
    $expires_at = $wpdb->get_var($sql);
    
    return apply_filters('vh360_get_membership_expiration', $expires_at, $user_id, $plan_key);
}
