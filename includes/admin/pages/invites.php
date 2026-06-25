<?php
if (!defined('ABSPATH')) { exit; }
if (isset($_POST['vh360_admin_revoke_invite_submit'])) {
    check_admin_referer('vh360_admin_revoke_invite', 'vh360_admin_invite_nonce');
    if (function_exists('vh360_revoke_invite')) {
        vh360_revoke_invite(absint($_POST['invite_id']), get_current_user_id());
    }
    echo '<div class="notice notice-success"><p>' . esc_html__('Invite revoked.', 'videohub360-theme') . '</p></div>';
}
$options = function_exists('vh360_get_invite_options') ? vh360_get_invite_options() : array();
$status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
global $wpdb;
$table = function_exists('vh360_invites_table_name') ? vh360_invites_table_name() : $wpdb->prefix . 'vh360_invites';
$where = array('1=1'); $args = array();
if ($status) { $where[] = 'i.status = %s'; $args[] = $status; }
if ($search) { $like = '%' . $wpdb->esc_like($search) . '%'; $where[] = '(i.invited_email LIKE %s OR inviter.user_email LIKE %s OR inviter.display_name LIKE %s OR invited.user_email LIKE %s OR invited.display_name LIKE %s)'; $args = array_merge($args, array($like,$like,$like,$like,$like)); }
$sql = "SELECT i.*, inviter.display_name AS inviter_name, inviter.user_email AS inviter_email, invited.display_name AS invited_name, invited.user_email AS invited_user_email FROM {$table} i LEFT JOIN {$wpdb->users} inviter ON inviter.ID = i.inviter_user_id LEFT JOIN {$wpdb->users} invited ON invited.ID = i.invited_user_id WHERE " . implode(' AND ', $where) . ' ORDER BY i.created_at DESC LIMIT 200';
$invites = $args ? $wpdb->get_results($wpdb->prepare($sql, $args)) : $wpdb->get_results($sql);
?>
<div class="wrap">
    <h1><?php esc_html_e('VH360 Invites', 'videohub360-theme'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('vh360_invite_settings'); ?>
        <h2><?php esc_html_e('Invite Settings', 'videohub360-theme'); ?></h2>
        <table class="form-table"><tbody>
            <tr><th><?php esc_html_e('Invite-only registration', 'videohub360-theme'); ?></th><td><label><input type="checkbox" name="vh360_invite_options[invite_only_registration]" value="1" <?php checked(!empty($options['invite_only_registration'])); ?>> <?php esc_html_e('Require a valid invite code to register.', 'videohub360-theme'); ?></label></td></tr>
            <tr><th><label for="vh360-invite-expiration-days"><?php esc_html_e('Invite expiration days', 'videohub360-theme'); ?></label></th><td><input id="vh360-invite-expiration-days" type="number" min="0" name="vh360_invite_options[expiration_days]" value="<?php echo esc_attr($options['expiration_days']); ?>"><p class="description"><?php esc_html_e('Use 0 for no expiration.', 'videohub360-theme'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Who can create invites', 'videohub360-theme'); ?></th><td><select name="vh360_invite_options[creator_role]"><?php foreach (array('members'=>__('Logged-in members','videohub360-theme'),'approved_professionals'=>__('Approved professionals','videohub360-theme'),'instructors'=>__('Instructors','videohub360-theme'),'admins'=>__('Administrators only','videohub360-theme')) as $key=>$label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($options['creator_role'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
        </tbody></table>
        <?php submit_button(); ?>
    </form>
    <h2><?php esc_html_e('Invite Records', 'videohub360-theme'); ?></h2>
    <form method="get"><input type="hidden" name="page" value="vh360-theme-invites"><input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search email or user', 'videohub360-theme'); ?>"> <select name="status"><option value=""><?php esc_html_e('All statuses', 'videohub360-theme'); ?></option><?php foreach (array('pending','accepted','expired','revoked') as $st) : ?><option value="<?php echo esc_attr($st); ?>" <?php selected($status, $st); ?>><?php echo esc_html(ucfirst($st)); ?></option><?php endforeach; ?></select> <?php submit_button(__('Filter', 'videohub360-theme'), 'secondary', '', false); ?></form>
    <table class="widefat striped"><thead><tr><th><?php esc_html_e('Inviter', 'videohub360-theme'); ?></th><th><?php esc_html_e('Invited Email', 'videohub360-theme'); ?></th><th><?php esc_html_e('Invited User', 'videohub360-theme'); ?></th><th><?php esc_html_e('Status', 'videohub360-theme'); ?></th><th><?php esc_html_e('Created', 'videohub360-theme'); ?></th><th><?php esc_html_e('Expires', 'videohub360-theme'); ?></th><th><?php esc_html_e('Accepted', 'videohub360-theme'); ?></th><th><?php esc_html_e('Actions', 'videohub360-theme'); ?></th></tr></thead><tbody>
    <?php if ($invites) : foreach ($invites as $invite) : ?>
        <tr><td><?php echo esc_html(trim($invite->inviter_name . ' <' . $invite->inviter_email . '>')); ?></td><td><?php echo esc_html($invite->invited_email); ?></td><td><?php echo $invite->invited_user_id ? esc_html(trim($invite->invited_name . ' <' . $invite->invited_user_email . '>')) : '&mdash;'; ?></td><td><?php echo esc_html(ucfirst($invite->status)); ?></td><td><?php echo esc_html($invite->created_at); ?></td><td><?php echo esc_html($invite->expires_at ?: '—'); ?></td><td><?php echo esc_html($invite->accepted_at ?: '—'); ?></td><td><?php if ('pending' === $invite->status) : ?><form method="post"><?php wp_nonce_field('vh360_admin_revoke_invite', 'vh360_admin_invite_nonce'); ?><input type="hidden" name="invite_id" value="<?php echo esc_attr($invite->id); ?>"><?php submit_button(__('Revoke', 'videohub360-theme'), 'delete small', 'vh360_admin_revoke_invite_submit', false); ?></form><?php else : ?>&mdash;<?php endif; ?></td></tr>
    <?php endforeach; else : ?><tr><td colspan="8"><?php esc_html_e('No invites found.', 'videohub360-theme'); ?></td></tr><?php endif; ?>
    </tbody></table>
</div>
