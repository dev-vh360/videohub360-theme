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
            <h3><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_success_heading', __( 'Application Submitted', 'videohub360-affiliates' ) ) ); ?></h3>
            <p><?php echo esc_html($success); ?></p>
        </div>

    <?php else: ?>

        <div class="vh360-affiliate-hero-card">
            <p class="vh360-affiliate-eyebrow"><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_eyebrow', __( 'Partner Program', 'videohub360-affiliates' ) ) ); ?></p>
            <h2><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_heading', __( 'Become a VideoHub360 Affiliate', 'videohub360-affiliates' ) ) ); ?></h2>
            <p class="vh360-affiliate-hero-desc"><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_description', __( 'Earn commission when you refer new customers. Promote VideoHub360 with your referral link and track your clicks, referrals, and commissions from your dashboard.', 'videohub360-affiliates' ) ) ); ?></p>
            <div class="vh360-affiliate-benefits-grid">
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_benefit_1', __( 'Earn commission on eligible purchases', 'videohub360-affiliates' ) ) ); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_benefit_2', __( 'Track referrals from your dashboard', 'videohub360-affiliates' ) ) ); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_benefit_3', __( 'Simple referral link sharing', 'videohub360-affiliates' ) ) ); ?></span>
                </div>
                <div class="vh360-affiliate-benefit">
                    <span class="vh360-affiliate-benefit-icon">&#10003;</span>
                    <span><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_benefit_4', __( 'Manual payout records', 'videohub360-affiliates' ) ) ); ?></span>
                </div>
            </div>
        </div>

        <div class="vh360-affiliate-form-card">

            <?php if ($error): ?>
                <div class="vh360-affiliate-notice vh360-affiliate-notice--error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <h3><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_form_heading', __( 'Apply Now', 'videohub360-affiliates' ) ) ); ?></h3>

            <form method="post" class="vh360-affiliate-form">
                <?php wp_nonce_field('vh360_aff_registration', 'vh360_aff_reg_nonce'); ?>

                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payment-method"><?php esc_html_e('Preferred Payout Method', 'videohub360-affiliates'); ?></label>
                    <select id="vh360-payment-method" name="payment_method" required>
                        <?php foreach ( vh360_affiliates_get_enabled_payout_methods() as $method_key => $method_label ) : ?>
                            <option value="<?php echo esc_attr( $method_key ); ?>"><?php echo esc_html( $method_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="vh360-affiliate-field-hint"><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_payout_method_hint', __( 'Choose how you would prefer to receive affiliate payouts.', 'videohub360-affiliates' ) ) ); ?></span>
                </div>

                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payout-details"><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_payout_details_label', __( 'Payout Details', 'videohub360-affiliates' ) ) ); ?></label>
                    <input type="text" id="vh360-payout-details" name="payout_details"
                           value="<?php echo esc_attr($user->user_email); ?>" required>
                    <span class="vh360-affiliate-field-hint"><?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_payout_details_hint', __( 'Enter the email, phone number, $Cashtag, or instructions needed to send your payout using your selected method.', 'videohub360-affiliates' ) ) ); ?></span>
                </div>

                <?php if (!empty($settings['terms_page_url'])): ?>
                    <p class="vh360-affiliate-terms-notice">
                        <?php
                        $terms_link_text = vh360_affiliates_get_setting_text( 'registration_terms_link_text', __( 'Affiliate Terms', 'videohub360-affiliates' ) );
                        $terms_notice    = vh360_affiliates_get_setting_text( 'registration_terms_notice', __( 'By applying, you agree to our {terms_link}.', 'videohub360-affiliates' ) );

                        $terms_link = sprintf(
                            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                            esc_url( $settings['terms_page_url'] ),
                            esc_html( $terms_link_text )
                        );

                        $allowed_html = array(
                            'a' => array(
                                'href'   => array(),
                                'target' => array(),
                                'rel'    => array(),
                            ),
                        );

                        // Escape the notice text first (plain text from settings),
                        // then replace the {terms_link} placeholder with the safe
                        // pre-built link HTML. wp_kses() allows only the <a> tag.
                        $escaped_notice = esc_html( $terms_notice );
                        echo wp_kses(
                            str_replace( '{terms_link}', $terms_link, $escaped_notice ),
                            $allowed_html
                        );
                        ?>
                    </p>
                <?php endif; ?>

                <button type="submit" class="vh360-affiliate-button">
                    <?php echo esc_html( vh360_affiliates_get_setting_text( 'registration_submit_button', __( 'Submit Application', 'videohub360-affiliates' ) ) ); ?>
                </button>

            </form>

        </div>

    <?php endif; ?>

</div>

