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
 * When checking for a specific plan ($plan_key is set), this delegates
 * to the effective-membership precedence policy so that only the
 * highest-priority record controls plan-specific access.
 *
 * @param int $user_id User ID. Defaults to current user.
 * @param string $plan_key Optional plan key to check for specific plan.
 * @return bool
 */
function vh360_user_has_active_membership($user_id = 0, $plan_key = null) {
    // Check if memberships are globally enabled
    $options = get_option('vh360_membership_options', array());
    if (empty($options['enable_memberships'])) {
        return true; // When memberships disabled, all users have "access"
    }
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Plan-specific check: use effective membership precedence so that
    // superseded / lower-priority records cannot satisfy plan gates.
    if ($plan_key) {
        $effective = vh360_get_active_membership($user_id);
        if (!$effective) {
            return apply_filters('vh360_user_has_active_membership', false, $user_id, $plan_key);
        }
        $match = (isset($effective->plan_key) && $effective->plan_key === $plan_key);
        return apply_filters('vh360_user_has_active_membership', $match, $user_id, $plan_key);
    }
    
    // "Any active membership" check – keep the existing COUNT-based query
    // because precedence is irrelevant when we only need "at least one active".
    $grace_period_days = isset($options['grace_period_days']) ? absint($options['grace_period_days']) : 0;
    
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    // Build expiration check with grace period
    if ($grace_period_days > 0) {
        $expiration_check = $wpdb->prepare(
            "(expires_at IS NULL OR DATE_ADD(expires_at, INTERVAL %d DAY) > NOW())",
            $grace_period_days
        );
    } else {
        $expiration_check = "(expires_at IS NULL OR expires_at > NOW())";
    }
    
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} 
        WHERE user_id = %d 
        AND status = 'active'
        AND {$expiration_check}",
        $user_id
    );
    
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
    // Check if memberships are globally enabled
    $options = get_option('vh360_membership_options', array());
    if (empty($options['enable_memberships'])) {
        return true; // When memberships disabled, all features are accessible
    }
    
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
 * Applies precedence rules when multiple active memberships exist:
 * 1. Active recurring subscription (most recently synced)
 * 2. Active fixed-term / one-time membership (latest created)
 * 3. Superseded memberships are skipped
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
    
    // Get grace period setting
    $options = get_option('vh360_membership_options', array());
    $grace_period_days = isset($options['grace_period_days']) ? absint($options['grace_period_days']) : 0;
    
    // Use API precedence method if available
    if (class_exists('VH360_Membership_API')) {
        $api = VH360_Membership_API::get_instance();
        $membership = $api->get_effective_membership($user_id, $grace_period_days);
        
        return apply_filters('vh360_get_active_membership', $membership ? $membership : false, $user_id);
    }
    
    // Fallback: simple query (legacy path)
    global $wpdb;
    $table = VH360_Membership_Database::get_memberships_table();
    
    // Build expiration check with grace period
    if ($grace_period_days > 0) {
        $expiration_check = $wpdb->prepare(
            "(expires_at IS NULL OR DATE_ADD(expires_at, INTERVAL %d DAY) > NOW())",
            $grace_period_days
        );
    } else {
        $expiration_check = "(expires_at IS NULL OR expires_at > NOW())";
    }
    
    $membership = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} 
        WHERE user_id = %d 
        AND status = 'active'
        AND {$expiration_check}
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

/**
 * Check if membership is required for dashboard tab
 * 
 * Helper function to use in dashboard tab show_callback
 *
 * @param string $feature_key Feature identifier
 * @param int $user_id User ID. Defaults to current user.
 * @return bool True if user can access, false otherwise
 */
function vh360_membership_allows_dashboard_tab($feature_key, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if membership system is enabled
    if (!function_exists('vh360_can_access_membership_feature')) {
        return true; // If membership system not active, allow access
    }
    
    return vh360_can_access_membership_feature($feature_key, $user_id);
}

/**
 * Create membership-aware show_callback for dashboard tabs
 *
 * @param string $feature_key Feature identifier
 * @param callable|null $additional_check Optional additional check function
 * @return callable
 */
function vh360_membership_show_callback($feature_key, $additional_check = null) {
    return function($user_id) use ($feature_key, $additional_check) {
        // First check membership
        if (!vh360_membership_allows_dashboard_tab($feature_key, $user_id)) {
            return false;
        }
        
        // Then check additional conditions if provided
        if ($additional_check && is_callable($additional_check)) {
            return call_user_func($additional_check, $user_id);
        }
        
        return true;
    };
}

/**
 * Render centralized membership gate
 * 
 * This is the single source of truth for all membership gate rendering across the platform.
 * It respects login_required and locked_message settings consistently.
 * 
 * @param array $context Optional context array with 'required_plan' key
 * @return string HTML for membership gate
 * @since 1.0.0
 */
function vh360_render_membership_gate($context = array()) {
    // Get membership options
    $options = get_option('vh360_membership_options', array());
    $login_required = isset($options['login_required']) ? $options['login_required'] : true;
    $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
    $custom_message = isset($options['locked_message']) ? $options['locked_message'] : '';
    
    // Get required plan from context
    $required_plan = isset($context['required_plan']) ? $context['required_plan'] : '';
    
    // Determine which gate to show
    $user_id = get_current_user_id();
    
    // If user is not logged in and login is required, show login gate
    if (!$user_id && $login_required) {
        return vh360_render_login_gate($custom_message, $pricing_url);
    }
    
    // Otherwise, show upgrade gate (for logged-in users or when login not required)
    return vh360_render_upgrade_gate($required_plan, $custom_message, $pricing_url);
}

/**
 * Render login required gate
 * 
 * @param string $custom_message Custom message from settings
 * @param string $pricing_url Pricing page URL
 * @return string HTML for login gate
 * @since 1.0.0
 */
function vh360_render_login_gate($custom_message = '', $pricing_url = '') {
    // Get options if not provided
    if (empty($custom_message) || empty($pricing_url)) {
        $options = get_option('vh360_membership_options', array());
        if (empty($pricing_url)) {
            $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
        }
        if (empty($custom_message)) {
            $custom_message = isset($options['locked_message']) ? $options['locked_message'] : '';
        }
    }
    
    $login_url = function_exists('vh360_get_login_page_url') 
        ? vh360_get_login_page_url() 
        : wp_login_url(get_permalink());
        
    ob_start();
    ?>
    <div class="vh360-membership-gate vh360-membership-login-required">
        <div class="vh360-membership-gate-content">
            <svg class="vh360-membership-gate-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <h3><?php esc_html_e('Login Required', 'videohub360-memberships'); ?></h3>
            <?php if ($custom_message) : ?>
                <div class="vh360-membership-custom-message">
                    <?php echo wp_kses_post($custom_message); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('Please log in to access this content.', 'videohub360-memberships'); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($login_url); ?>" class="vh360-membership-gate-button">
                <?php esc_html_e('Log In', 'videohub360-memberships'); ?>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render upgrade required gate
 * 
 * @param string $required_plan Required plan key
 * @param string $custom_message Custom message from settings
 * @param string $pricing_url Pricing page URL
 * @return string HTML for upgrade gate
 * @since 1.0.0
 */
function vh360_render_upgrade_gate($required_plan = '', $custom_message = '', $pricing_url = '') {
    // Get options if not provided
    if (empty($custom_message) || empty($pricing_url)) {
        $options = get_option('vh360_membership_options', array());
        if (empty($pricing_url)) {
            $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
        }
        if (empty($custom_message)) {
            $custom_message = isset($options['locked_message']) ? $options['locked_message'] : '';
        }
    }
    
    ob_start();
    ?>
    <div class="vh360-membership-gate vh360-membership-upgrade-required">
        <div class="vh360-membership-gate-content">
            <svg class="vh360-membership-gate-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
            </svg>
            <h3><?php esc_html_e('Premium Content', 'videohub360-memberships'); ?></h3>
            <?php if ($custom_message) : ?>
                <div class="vh360-membership-custom-message">
                    <?php echo wp_kses_post($custom_message); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('This content requires an active membership to access.', 'videohub360-memberships'); ?></p>
            <?php endif; ?>
            <?php if ($pricing_url) : ?>
                <a href="<?php echo esc_url($pricing_url); ?>" class="vh360-membership-gate-button">
                    <?php esc_html_e('View Plans', 'videohub360-memberships'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
