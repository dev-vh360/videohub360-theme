<?php
if (!defined('ABSPATH')) { exit; }
$user_id = get_current_user_id();
if (!function_exists('vh360_user_can_create_invites') || !vh360_user_can_create_invites($user_id)) {
    echo '<p>' . esc_html__('You do not have permission to create invites.', 'videohub360-theme') . '</p>';
    return;
}
$invites = function_exists('vh360_get_user_invites') ? vh360_get_user_invites($user_id) : array();
$notice = isset($_GET['invite_notice']) ? sanitize_key(wp_unslash($_GET['invite_notice'])) : '';
?>
<div class="vh360-dashboard-section vh360-invites-dashboard">
    <h2><?php esc_html_e('Invites', 'videohub360-theme'); ?></h2>
    <?php if ($notice) : ?><div class="vh360-dashboard-notice"><p><?php echo esc_html('created' === $notice ? __('Invite created and email sent.', 'videohub360-theme') : ('revoked' === $notice ? __('Invite revoked.', 'videohub360-theme') : __('Invite action could not be completed.', 'videohub360-theme'))); ?></p></div><?php endif; ?>
    <form method="post" class="vh360-invite-create-form">
        <?php wp_nonce_field('vh360_create_invite', 'vh360_invite_nonce'); ?>
        <label for="vh360-invited-email"><?php esc_html_e('Invite email address', 'videohub360-theme'); ?></label>
        <input type="email" id="vh360-invited-email" name="vh360_invited_email" required>
        <button type="submit" name="vh360_create_invite_submit" class="button"><?php esc_html_e('Create Invite', 'videohub360-theme'); ?></button>
    </form>
    <table class="vh360-invites-table">
        <thead><tr><th><?php esc_html_e('Email', 'videohub360-theme'); ?></th><th><?php esc_html_e('Status', 'videohub360-theme'); ?></th><th><?php esc_html_e('Invite Link', 'videohub360-theme'); ?></th><th><?php esc_html_e('Created', 'videohub360-theme'); ?></th><th><?php esc_html_e('Actions', 'videohub360-theme'); ?></th></tr></thead>
        <tbody>
        <?php if ($invites) : foreach ($invites as $invite) : ?>
            <tr>
                <td><?php echo esc_html($invite->invited_email); ?></td>
                <td><?php echo esc_html(ucfirst($invite->status)); ?></td>
                <td><input type="text" readonly value="<?php echo esc_attr(vh360_get_invite_link($invite->code)); ?>" onfocus="this.select();"></td>
                <td><?php echo esc_html($invite->created_at); ?></td>
                <td><?php if ('pending' === $invite->status) : ?><form method="post"><?php wp_nonce_field('vh360_revoke_invite', 'vh360_invite_nonce'); ?><input type="hidden" name="invite_id" value="<?php echo esc_attr($invite->id); ?>"><button type="submit" name="vh360_revoke_invite_submit" class="button button-link-delete"><?php esc_html_e('Revoke', 'videohub360-theme'); ?></button></form><?php else : ?>&mdash;<?php endif; ?></td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="5"><?php esc_html_e('No invites yet.', 'videohub360-theme'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
