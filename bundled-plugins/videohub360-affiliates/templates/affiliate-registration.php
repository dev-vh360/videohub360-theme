<?php
/**
 * Affiliate registration template.
 *
 * Variables available:
 *   $error   (string) – validation/processing error message, or empty string
 *   $success (string) – success message after form submitted, or empty string
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

$settings = vh360_affiliates_get_settings();
$user     = wp_get_current_user();
?>
<div class="vh360-affiliate-registration">

    <?php if ($error): ?>
        <p class="vh360-aff-error"><?php echo esc_html($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="vh360-aff-success"><?php echo esc_html($success); ?></p>
    <?php else: ?>

        <h2><?php esc_html_e('Apply to Become an Affiliate', 'videohub360-affiliates'); ?></h2>

        <?php if (!empty($settings['terms_page_url'])): ?>
            <p><?php printf(
                /* translators: %s: terms page link */
                esc_html__('By applying, you agree to our %s.', 'videohub360-affiliates'),
                '<a href="' . esc_url($settings['terms_page_url']) . '" target="_blank">' . esc_html__('Affiliate Terms', 'videohub360-affiliates') . '</a>'
            ); ?></p>
        <?php endif; ?>

        <form method="post" class="vh360-aff-form">
            <?php wp_nonce_field('vh360_aff_registration', 'vh360_aff_reg_nonce'); ?>

            <p>
                <label for="vh360-payment-email"><?php esc_html_e('Payment Email', 'videohub360-affiliates'); ?></label><br>
                <input type="email" id="vh360-payment-email" name="payment_email"
                       value="<?php echo esc_attr($user->user_email); ?>" required
                       class="vh360-aff-input">
                <span class="description"><?php esc_html_e('This is where your payments will be sent.', 'videohub360-affiliates'); ?></span>
            </p>

            <p>
                <button type="submit" class="vh360-aff-button">
                    <?php esc_html_e('Submit Application', 'videohub360-affiliates'); ?>
                </button>
            </p>
        </form>

    <?php endif; ?>

</div>
