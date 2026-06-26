<?php
/**
 * Single-use, email-locked invite system.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

define('VH360_INVITES_DB_VERSION', '1.0.0');

function vh360_invites_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'vh360_invites';
}

function vh360_get_invite_options() {
    return wp_parse_args(get_option('vh360_invite_options', array()), array(
        'invite_only_registration' => 0,
        'expiration_days' => 14,
        'creator_role' => 'members',
        'required_registration_forms' => array('general', 'client', 'professional', 'instructor'),
    ));
}

function vh360_invites_enabled() {
    $options = vh360_get_invite_options();
    return !empty($options['invite_only_registration']);
}



function vh360_get_invite_registration_error_message($error_code) {
    $error_code = sanitize_key($error_code);
    $messages = array(
        'invite_required' => __('A valid invite is required to create an account.', 'videohub360-theme'),
        'invite_invalid' => __('This invite code is not valid. Please check your invite link or contact the person who invited you.', 'videohub360-theme'),
        'invite_expired' => __('This invite has expired. Please ask for a new invite.', 'videohub360-theme'),
        'invite_revoked' => __('This invite is no longer available. Please ask for a new invite.', 'videohub360-theme'),
        'invite_used' => __('This invite has already been used.', 'videohub360-theme'),
        'invite_email_mismatch' => __('This invite is not valid for the email address you entered. Please use the email address that received the invite.', 'videohub360-theme'),
        'invite_inviter_invalid' => __('This invite is no longer valid. Please ask for a new invite.', 'videohub360-theme'),
        'invite_acceptance_failed' => __('This invite could not be accepted. Please refresh the page and try again.', 'videohub360-theme'),
    );

    return isset($messages[$error_code]) ? $messages[$error_code] : __('This invite code is not valid. Please check your invite link or contact the person who invited you.', 'videohub360-theme');
}

function vh360_invite_required_for_registration_context($context = 'general') {
    if (!vh360_invites_enabled()) {
        return false;
    }

    $context = sanitize_key($context ? $context : 'general');
    $allowed_contexts = array('general', 'client', 'professional', 'instructor');
    if (!in_array($context, $allowed_contexts, true)) {
        $context = 'general';
    }

    $options = vh360_get_invite_options();
    $required_forms = isset($options['required_registration_forms']) && is_array($options['required_registration_forms'])
        ? array_map('sanitize_key', $options['required_registration_forms'])
        : array('general', 'client', 'professional', 'instructor');

    return in_array($context, $required_forms, true);
}

function vh360_invites_install_table() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $table = vh360_invites_table_name();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        code varchar(64) NOT NULL,
        inviter_user_id bigint(20) unsigned NOT NULL,
        invited_user_id bigint(20) unsigned DEFAULT NULL,
        invited_email varchar(190) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL,
        expires_at datetime DEFAULT NULL,
        accepted_at datetime DEFAULT NULL,
        revoked_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY code (code),
        KEY inviter_user_id (inviter_user_id),
        KEY invited_user_id (invited_user_id),
        KEY invited_email (invited_email),
        KEY status (status)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('vh360_invites_db_version', VH360_INVITES_DB_VERSION);
}
add_action('after_switch_theme', 'vh360_invites_install_table');
function vh360_invites_maybe_install_table() {
    if (get_option('vh360_invites_db_version') !== VH360_INVITES_DB_VERSION) {
        vh360_invites_install_table();
    }
}
add_action('init', 'vh360_invites_maybe_install_table', 5);

function vh360_generate_invite_code() {
    do {
        $code = wp_generate_password(32, false, false);
    } while (vh360_get_invite_by_code($code));
    return $code;
}

function vh360_normalize_invite_email($email) {
    return strtolower(sanitize_email($email));
}

function vh360_user_can_create_invites($user_id = 0) {
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    if (!$user_id) {
        return false;
    }
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    $options = vh360_get_invite_options();
    $role = isset($options['creator_role']) ? $options['creator_role'] : 'members';
    if ('admins' === $role) {
        return false;
    }
    if ('instructors' === $role) {
        $user = get_userdata($user_id);
        return $user && in_array('vh360_instructor', (array) $user->roles, true);
    }
    if ('approved_professionals' === $role) {
        return function_exists('vh360_is_professional_approved') && vh360_is_professional_approved($user_id);
    }
    return true;
}

function vh360_get_invite_expiration_datetime() {
    $options = vh360_get_invite_options();
    $days = max(0, absint($options['expiration_days']));
    return $days ? gmdate('Y-m-d H:i:s', time() + ($days * DAY_IN_SECONDS)) : null;
}

function vh360_create_invite($inviter_user_id, $invited_email) {
    global $wpdb;
    $inviter_user_id = absint($inviter_user_id);
    $invited_email = vh360_normalize_invite_email($invited_email);
    if (!$inviter_user_id || !get_user_by('id', $inviter_user_id) || !is_email($invited_email) || !vh360_user_can_create_invites($inviter_user_id)) {
        return new WP_Error('invalid_invite_request', __('Unable to create invite.', 'videohub360-theme'));
    }
    if (email_exists($invited_email)) {
        return new WP_Error('invite_email_exists', __('This email address already belongs to an existing account.', 'videohub360-theme'));
    }
    $code = vh360_generate_invite_code();
    $inserted = $wpdb->insert(vh360_invites_table_name(), array(
        'code' => $code,
        'inviter_user_id' => $inviter_user_id,
        'invited_email' => $invited_email,
        'status' => 'pending',
        'created_at' => current_time('mysql', true),
        'expires_at' => vh360_get_invite_expiration_datetime(),
    ), array('%s','%d','%s','%s','%s','%s'));
    if (!$inserted) {
        return new WP_Error('invite_not_created', __('Unable to create invite.', 'videohub360-theme'));
    }
    $invite = vh360_get_invite_by_code($code);
    vh360_send_invite_email($invite);
    return $invite;
}

function vh360_get_invite_by_code($code) {
    global $wpdb;
    $code = sanitize_text_field(wp_unslash($code));
    if ('' === $code) {
        return null;
    }
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . vh360_invites_table_name() . ' WHERE code = %s LIMIT 1', $code));
}

function vh360_get_invite($invite_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . vh360_invites_table_name() . ' WHERE id = %d LIMIT 1', absint($invite_id)));
}

function vh360_validate_invite_for_registration($code, $email) {
    global $wpdb;
    if (!vh360_invites_enabled()) {
        return true;
    }
    $code = sanitize_text_field(wp_unslash($code));
    if ('' === $code) {
        return new WP_Error('invite_required', __('Please enter your invite code.', 'videohub360-theme'));
    }
    $invite = vh360_get_invite_by_code($code);
    if (!$invite) {
        return new WP_Error('invite_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }
    if ('revoked' === $invite->status) {
        return new WP_Error('invite_revoked', __('This invite has been revoked.', 'videohub360-theme'));
    }
    if ('pending' !== $invite->status) {
        return new WP_Error('accepted' === $invite->status ? 'invite_used' : 'invite_expired', __('This invite cannot be used.', 'videohub360-theme'));
    }
    if (!empty($invite->accepted_at) || !empty($invite->invited_user_id)) {
        return new WP_Error('invite_used', __('This invite has already been used.', 'videohub360-theme'));
    }
    if (!empty($invite->expires_at) && strtotime($invite->expires_at . ' UTC') < time()) {
        $wpdb->update(vh360_invites_table_name(), array('status' => 'expired'), array('id' => absint($invite->id)), array('%s'), array('%d'));
        return new WP_Error('invite_expired', __('This invite has expired.', 'videohub360-theme'));
    }
    if (!get_user_by('id', absint($invite->inviter_user_id))) {
        return new WP_Error('invite_inviter_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }
    if (vh360_normalize_invite_email($email) !== vh360_normalize_invite_email($invite->invited_email)) {
        return new WP_Error('invite_email_mismatch', __('This invite is locked to a different email address.', 'videohub360-theme'));
    }
    return $invite;
}

function vh360_accept_invite($invite_or_id, $new_user_id, $email = '') {
    global $wpdb;

    if (is_object($invite_or_id)) {
        $invite = $invite_or_id;
    } elseif (is_numeric($invite_or_id)) {
        $invite = vh360_get_invite(absint($invite_or_id));
    } else {
        return new WP_Error('invite_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }

    if (!$invite || empty($invite->id)) {
        return new WP_Error('invite_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }

    $fresh_invite = vh360_get_invite(absint($invite->id));
    if (!$fresh_invite) {
        return new WP_Error('invite_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }

    if ('revoked' === $fresh_invite->status) {
        return new WP_Error('invite_revoked', __('This invite has been revoked.', 'videohub360-theme'));
    }

    if ('pending' !== $fresh_invite->status || !empty($fresh_invite->accepted_at) || !empty($fresh_invite->invited_user_id)) {
        return new WP_Error('invite_used', __('This invite has already been used.', 'videohub360-theme'));
    }

    if (!empty($fresh_invite->expires_at) && strtotime($fresh_invite->expires_at . ' UTC') < time()) {
        $wpdb->update(vh360_invites_table_name(), array('status' => 'expired'), array('id' => absint($fresh_invite->id)), array('%s'), array('%d'));
        return new WP_Error('invite_expired', __('This invite has expired.', 'videohub360-theme'));
    }

    if (!get_user_by('id', absint($fresh_invite->inviter_user_id))) {
        return new WP_Error('invite_inviter_invalid', __('Invalid invite code.', 'videohub360-theme'));
    }

    $new_user = get_user_by('id', absint($new_user_id));
    $registration_email = $email ? $email : ($new_user ? $new_user->user_email : '');
    if (!$new_user || vh360_normalize_invite_email($registration_email) !== vh360_normalize_invite_email($fresh_invite->invited_email)) {
        return new WP_Error('invite_email_mismatch', __('This invite is locked to a different email address.', 'videohub360-theme'));
    }

    $accepted_at = current_time('mysql', true);
    $updated = $wpdb->query($wpdb->prepare(
        'UPDATE ' . vh360_invites_table_name() . " SET status = %s, invited_user_id = %d, accepted_at = %s WHERE id = %d AND status = %s AND invited_user_id IS NULL AND accepted_at IS NULL",
        'accepted',
        absint($new_user_id),
        $accepted_at,
        absint($fresh_invite->id),
        'pending'
    ));

    if (!$updated) {
        return new WP_Error('invite_acceptance_failed', __('Unable to accept invite. Please request a new invite and try again.', 'videohub360-theme'));
    }

    update_user_meta($new_user_id, '_vh360_invited_by_user_id', absint($fresh_invite->inviter_user_id));
    update_user_meta($new_user_id, '_vh360_invite_code_used', sanitize_text_field($fresh_invite->code));
    update_user_meta($new_user_id, '_vh360_invite_id', absint($fresh_invite->id));
    vh360_send_invite_accepted_email($fresh_invite, $new_user_id);
    return true;
}

function vh360_get_user_invites($user_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . vh360_invites_table_name() . ' WHERE inviter_user_id = %d ORDER BY created_at DESC', absint($user_id)));
}

function vh360_get_user_inviter($user_id) {
    $inviter_id = absint(get_user_meta($user_id, '_vh360_invited_by_user_id', true));
    return $inviter_id ? get_user_by('id', $inviter_id) : false;
}

function vh360_get_invite_link($code) {
    return add_query_arg('invite', rawurlencode($code), function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : home_url('/register/'));
}

function vh360_revoke_invite($invite_id, $user_id = 0) {
    global $wpdb;
    $invite = vh360_get_invite($invite_id);
    $user_id = $user_id ? absint($user_id) : get_current_user_id();
    if (!$invite || 'pending' !== $invite->status) {
        return new WP_Error('invite_not_revokable', __('Only pending invites can be revoked.', 'videohub360-theme'));
    }
    if (!user_can($user_id, 'manage_options') && absint($invite->inviter_user_id) !== $user_id) {
        return new WP_Error('invite_forbidden', __('You cannot manage this invite.', 'videohub360-theme'));
    }
    return (bool) $wpdb->update(vh360_invites_table_name(), array('status' => 'revoked', 'revoked_at' => current_time('mysql', true)), array('id' => absint($invite->id)), array('%s','%s'), array('%d'));
}

function vh360_send_invite_email($invite) {
    if (!$invite) { return; }
    $subject = sprintf(__('[%s] You have been invited', 'videohub360-theme'), get_bloginfo('name'));
    $message = sprintf(__('You have been invited to join %1$s. This invite is only valid for %2$s.', 'videohub360-theme'), get_bloginfo('name'), $invite->invited_email) . "\n\n" . vh360_get_invite_link($invite->code);
    wp_mail($invite->invited_email, $subject, $message);
}

function vh360_send_invite_accepted_email($invite, $new_user_id) {
    $inviter = get_user_by('id', absint($invite->inviter_user_id));
    $new_user = get_user_by('id', absint($new_user_id));
    if (!$inviter || !$new_user) { return; }
    $subject = sprintf(__('[%s] Your invite was accepted', 'videohub360-theme'), get_bloginfo('name'));
    $message = sprintf(__('%s accepted your invite and registered.', 'videohub360-theme'), $new_user->user_email);
    wp_mail($inviter->user_email, $subject, $message);
}

function vh360_handle_invite_dashboard_actions() {
    if ('POST' !== $_SERVER['REQUEST_METHOD'] || !is_user_logged_in()) { return; }
    if (isset($_POST['vh360_create_invite_submit'])) {
        check_admin_referer('vh360_create_invite', 'vh360_invite_nonce');
        $email = isset($_POST['vh360_invited_email']) ? sanitize_email(wp_unslash($_POST['vh360_invited_email'])) : '';
        $result = vh360_create_invite(get_current_user_id(), $email);
        wp_safe_redirect(add_query_arg('invite_notice', is_wp_error($result) ? $result->get_error_code() : 'created', wp_get_referer()));
        exit;
    }
    if (isset($_POST['vh360_revoke_invite_submit'])) {
        check_admin_referer('vh360_revoke_invite', 'vh360_invite_nonce');
        $result = vh360_revoke_invite(isset($_POST['invite_id']) ? absint($_POST['invite_id']) : 0, get_current_user_id());
        wp_safe_redirect(add_query_arg('invite_notice', is_wp_error($result) ? $result->get_error_code() : 'revoked', wp_get_referer()));
        exit;
    }
}
add_action('template_redirect', 'vh360_handle_invite_dashboard_actions');

function vh360_admin_users_invited_by_column($columns) {
    $columns['vh360_invited_by'] = __('Invited By', 'videohub360-theme');
    return $columns;
}
add_filter('manage_users_columns', 'vh360_admin_users_invited_by_column');

function vh360_admin_users_invited_by_column_content($value, $column_name, $user_id) {
    if ('vh360_invited_by' !== $column_name) { return $value; }
    $inviter = vh360_get_user_inviter($user_id);
    return $inviter ? esc_html($inviter->display_name . ' <' . $inviter->user_email . '>') : '&mdash;';
}
add_filter('manage_users_custom_column', 'vh360_admin_users_invited_by_column_content', 10, 3);

function vh360_admin_profile_invite_info($user) {
    $inviter = vh360_get_user_inviter($user->ID);
    $invite_id = absint(get_user_meta($user->ID, '_vh360_invite_id', true));
    $invite = $invite_id ? vh360_get_invite($invite_id) : null;
    ?>
    <h2><?php esc_html_e('VH360 Invite Attribution', 'videohub360-theme'); ?></h2>
    <table class="form-table" role="presentation">
        <tr><th><?php esc_html_e('Invited By', 'videohub360-theme'); ?></th><td><?php echo $inviter ? esc_html($inviter->display_name . ' <' . $inviter->user_email . '>') : '&mdash;'; ?></td></tr>
        <tr><th><?php esc_html_e('Invite Code Used', 'videohub360-theme'); ?></th><td><?php echo esc_html(get_user_meta($user->ID, '_vh360_invite_code_used', true) ?: '—'); ?></td></tr>
        <tr><th><?php esc_html_e('Invite Accepted Date', 'videohub360-theme'); ?></th><td><?php echo $invite && $invite->accepted_at ? esc_html($invite->accepted_at) : '&mdash;'; ?></td></tr>
    </table>
    <?php
}
add_action('show_user_profile', 'vh360_admin_profile_invite_info');
add_action('edit_user_profile', 'vh360_admin_profile_invite_info');
