<?php
if (!defined('ABSPATH')) { exit; }
$vh360_invite_context = isset($args['context']) ? sanitize_key($args['context']) : 'general';
if (!function_exists('vh360_invite_required_for_registration_context') || !vh360_invite_required_for_registration_context($vh360_invite_context)) {
    return;
}

$vh360_invite_code = '';
if (isset($_GET['invite'])) {
    $vh360_invite_code = sanitize_text_field(wp_unslash($_GET['invite']));
} elseif (isset($_POST['vh360_invite_code'])) {
    $vh360_invite_code = sanitize_text_field(wp_unslash($_POST['vh360_invite_code']));
}
?>
<div class="vh360-auth-field vh360-invite-code-field">
    <label for="vh360-invite-code-<?php echo esc_attr($vh360_invite_context); ?>">
        <?php esc_html_e('Invite Code', 'videohub360-theme'); ?>
        <span class="required">*</span>
    </label>
    <input type="text" name="vh360_invite_code" id="vh360-invite-code-<?php echo esc_attr($vh360_invite_context); ?>" class="vh360-auth-input" value="<?php echo esc_attr($vh360_invite_code); ?>" required>
    <small class="vh360-auth-hint"><?php esc_html_e('Invite codes are single-use and locked to the invited email address.', 'videohub360-theme'); ?></small>
</div>
