<?php
/**
 * Affiliate registration template.
 *
 * Variables available:
 *   $error    (string) – validation/processing error message, or empty string
 *   $success  (string) – success message after form submitted, or empty string
 *   $settings (array)  – plugin settings
 *   $user     (WP_User) – current user
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

$settings = vh360_affiliates_get_settings();
$user     = wp_get_current_user();
?>
<div class="vh360-affiliate-page">

    <?php if ($success): ?>

        <div class="vh360-affiliate-status-card vh360-affiliate-status-active">
            <h3><?php esc_html_e('Application Submitted', 'videohub360-affiliates'); ?></h3>
            <p><?php echo esc_html($success); ?></p>
        </div>

    <?php else: ?>

        <div class="vh360-affiliate-hero-card">
            <p class="vh360-affiliate-eyebrow"><?php esc_html_e('Partner Program', 'videohub360-affiliates'); ?></p>
            <h2><?php esc_html_e('Become a VideoHub360 Affiliate', 'videohub360-affiliates'); ?></h2>
            <p class="vh360-affiliate-hero-desc"><?php esc_html_e('Earn commission when you refer new customers. Promote VideoHub360 with your referral link and track your clicks, referrals, and commissions from your dashboard.', 'videohub360-affiliates'); ?></p>
            <div class="vh360-affiliate-benefits-grid">
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php esc_html_e('Earn commission on eligible purchases', 'videohub360-affiliates'); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php esc_html_e('Track referrals from your dashboard', 'videohub360-affiliates'); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php esc_html_e('Simple referral link sharing', 'videohub360-affiliates'); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php esc_html_e('Manual payout records', 'videohub360-affiliates'); ?></span>
                </div>
            </div>
        </div>

        <div class="vh360-affiliate-form-card">

            <?php if ($error): ?>
                <div class="vh360-affiliate-notice vh360-affiliate-notice--error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <h3><?php esc_html_e('Apply Now', 'videohub360-affiliates'); ?></h3>

            <form method="post" class="vh360-affiliate-form">
                <?php wp_nonce_field('vh360_aff_registration', 'vh360_aff_reg_nonce'); ?>

                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payment-method"><?php esc_html_e('Preferred Payout Method', 'videohub360-affiliates'); ?></label>
                    <select id="vh360-payment-method" name="payment_method" required>
                        <?php foreach ( vh360_affiliates_get_enabled_payout_methods() as $method_key => $method_label ) : ?>
                            <option value="<?php echo esc_attr( $method_key ); ?>"><?php echo esc_html( $method_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="vh360-affiliate-field-hint"><?php esc_html_e('Choose how you would prefer to receive affiliate payouts.', 'videohub360-affiliates'); ?></span>
                </div>

                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payout-details"><?php esc_html_e('Payout Details', 'videohub360-affiliates'); ?></label>
                    <input type="text" id="vh360-payout-details" name="payout_details"
                           value="<?php echo esc_attr($user->user_email); ?>" required>
                    <span class="vh360-affiliate-field-hint"><?php esc_html_e('Enter the email, phone number, $Cashtag, or instructions needed to send your payout using your selected method.', 'videohub360-affiliates'); ?></span>
                </div>

                <?php if (!empty($settings['terms_page_url'])): ?>
                    <p class="vh360-affiliate-terms-notice">
                        <?php printf(
                            /* translators: %s: terms page link */
                            esc_html__('By applying, you agree to our %s.', 'videohub360-affiliates'),
                            '<a href="' . esc_url($settings['terms_page_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Affiliate Terms', 'videohub360-affiliates') . '</a>'
                        ); ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="vh360-affiliate-button">
                    <?php esc_html_e('Submit Application', 'videohub360-affiliates'); ?>
                </button>

            </form>

        </div>

    <?php endif; ?>

</div>

