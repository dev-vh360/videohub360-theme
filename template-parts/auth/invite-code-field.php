<?php
if (!defined('ABSPATH')) { exit; }
$vh360_invite_code = '';
if (isset($_GET['invite'])) {
    $vh360_invite_code = sanitize_text_field(wp_unslash($_GET['invite']));
} elseif (isset($_POST['vh360_invite_code'])) {
    $vh360_invite_code = sanitize_text_field(wp_unslash($_POST['vh360_invite_code']));
}
$vh360_invite_required = function_exists('vh360_invites_enabled') && vh360_invites_enabled();
?>
<div class="vh360-auth-field vh360-invite-code-field">
    <label for="vh360-invite-code">
        <?php esc_html_e('Invite Code', 'videohub360-theme'); ?>
        <?php if ($vh360_invite_required) : ?><span class="required">*</span><?php endif; ?>
    </label>
    <input type="text" name="vh360_invite_code" id="vh360-invite-code" class="vh360-auth-input" value="<?php echo esc_attr($vh360_invite_code); ?>" <?php echo $vh360_invite_required ? 'required' : ''; ?>>
    <small class="vh360-auth-hint"><?php esc_html_e('Invite codes are single-use and locked to the invited email address.', 'videohub360-theme'); ?></small>
</div>
