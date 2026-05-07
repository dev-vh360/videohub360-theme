<?php
/**
 * Affiliate dashboard template.
 *
 * Variables available from VH360_Affiliates_Frontend:
 *   $affiliate    – affiliate row object
 *   $totals       – commission totals array (pending, approved, paid, rejected, reversed)
 *   $visits       – total visit count (int)
 *   $refs         – total referral count (int)
 *   $commissions  – recent commission rows
 *   $referrals    – recent referral rows
 *   $payouts      – payout rows
 *   $referral_url – full referral URL string
 *   $error        – error message string
 *   $success      – success message string
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;
?>
<div class="vh360-affiliate-dashboard">

    <?php if ($error): ?>
        <div class="vh360-affiliate-notice vh360-affiliate-notice--error"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="vh360-affiliate-notice vh360-affiliate-notice--success"><?php echo esc_html($success); ?></div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="vh360-affiliate-dashboard-header">
        <h2><?php esc_html_e('Affiliate Dashboard', 'videohub360-affiliates'); ?></h2>
        <p><?php esc_html_e('Your affiliate status, referral link, commissions, and payout history.', 'videohub360-affiliates'); ?></p>
    </div>

    <!-- Stats Grid -->
    <div class="vh360-affiliate-stats-grid">
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Clicks', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo esc_html($visits); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Total link visits', 'videohub360-affiliates'); ?></span>
        </div>
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Referrals', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo esc_html($refs); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Total referred orders', 'videohub360-affiliates'); ?></span>
        </div>
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Pending', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo wp_kses_post(wc_price($totals['pending'])); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Awaiting approval', 'videohub360-affiliates'); ?></span>
        </div>
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Approved', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo wp_kses_post(wc_price($totals['approved'])); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Ready to pay out', 'videohub360-affiliates'); ?></span>
        </div>
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Paid', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo wp_kses_post(wc_price($totals['paid'])); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Total paid out', 'videohub360-affiliates'); ?></span>
        </div>
        <div class="vh360-affiliate-stat-card">
            <span class="vh360-affiliate-stat-label"><?php esc_html_e('Rejected / Reversed', 'videohub360-affiliates'); ?></span>
            <span class="vh360-affiliate-stat-value"><?php echo wp_kses_post(wc_price($totals['rejected'] + $totals['reversed'])); ?></span>
            <span class="vh360-affiliate-stat-hint"><?php esc_html_e('Not eligible', 'videohub360-affiliates'); ?></span>
        </div>
    </div>

    <!-- Referral Link Card -->
    <div class="vh360-affiliate-card vh360-affiliate-referral-card">
        <h3><?php esc_html_e('Your Referral Link', 'videohub360-affiliates'); ?></h3>
        <div class="vh360-affiliate-referral-link-row">
            <input type="text" id="vh360-referral-url" value="<?php echo esc_url($referral_url); ?>" readonly
                   class="vh360-affiliate-referral-link-input">
            <button type="button" class="vh360-affiliate-button vh360-aff-copy-btn" data-target="vh360-referral-url">
                <?php esc_html_e('Copy Link', 'videohub360-affiliates'); ?>
            </button>
        </div>
        <span class="vh360-affiliate-field-hint"><?php esc_html_e('Share this link to start earning commissions.', 'videohub360-affiliates'); ?></span>
    </div>

    <!-- Dashboard Grid: Commission Summary + Payout Details -->
    <div class="vh360-affiliate-dashboard-grid">

        <div class="vh360-affiliate-card vh360-affiliate-commission-summary-card">
            <h3><?php esc_html_e('Commission Summary', 'videohub360-affiliates'); ?></h3>
            <div class="vh360-affiliate-table-wrap">
                <table class="vh360-affiliate-table vh360-affiliate-summary-table">
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Affiliate Code', 'videohub360-affiliates'); ?></td>
                            <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Status', 'videohub360-affiliates'); ?></td>
                            <td>
                                <span class="vh360-affiliate-status-badge vh360-affiliate-status-<?php echo esc_attr($affiliate->status); ?>">
                                    <?php echo esc_html(vh360_affiliates_status_label($affiliate->status)); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Commission Rate', 'videohub360-affiliates'); ?></td>
                            <td>
                                <?php if ($affiliate->commission_type === 'percentage'): ?>
                                    <?php echo esc_html($affiliate->commission_rate); ?>%
                                <?php else: ?>
                                    <?php echo wp_kses_post(wc_price($affiliate->commission_rate)); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="vh360-affiliate-card vh360-affiliate-payment-card">
            <h3><?php esc_html_e('Payout Preferences', 'videohub360-affiliates'); ?></h3>
            <p class="vh360-affiliate-field-hint"><?php esc_html_e('These details help the site owner pay your approved affiliate commissions manually.', 'videohub360-affiliates'); ?></p>
            <form method="post" class="vh360-affiliate-form">
                <?php wp_nonce_field('vh360_aff_update_payout', 'vh360_aff_payout_nonce'); ?>
                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payment-method-update"><?php esc_html_e('Preferred Payout Method', 'videohub360-affiliates'); ?></label>
                    <select id="vh360-payment-method-update" name="payment_method" required>
                        <?php
                        $current_method = $affiliate->payment_method ?? 'other';
                        $methods        = vh360_affiliates_get_enabled_payout_methods();

                        if ( $current_method && ! isset( $methods[ $current_method ] ) ) {
                            $methods = array(
                                $current_method => sprintf(
                                    /* translators: %s: payout method label */
                                    __( '%s (currently unavailable)', 'videohub360-affiliates' ),
                                    vh360_affiliates_get_payout_method_label( $current_method )
                                ),
                            ) + $methods;
                        }

                        foreach ($methods as $value => $label) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($value),
                                selected($value, $current_method, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </div>
                <div class="vh360-affiliate-form-group">
                    <label for="vh360-payout-details-update"><?php esc_html_e('Payout Details', 'videohub360-affiliates'); ?></label>
                    <input type="text" id="vh360-payout-details-update" name="payout_details"
                           value="<?php echo esc_attr($affiliate->payment_email ?? ''); ?>" required>
                    <span class="vh360-affiliate-field-hint"><?php esc_html_e('Enter the email, phone number, $Cashtag, or instructions needed to send your payout.', 'videohub360-affiliates'); ?></span>
                </div>
                <button type="submit" class="vh360-affiliate-button">
                    <?php esc_html_e('Save', 'videohub360-affiliates'); ?>
                </button>
            </form>
        </div>

    </div>

    <!-- Recent Referrals -->
    <div class="vh360-affiliate-table-card">
        <h3><?php esc_html_e('Recent Referrals', 'videohub360-affiliates'); ?></h3>
        <?php if ($referrals): ?>
            <div class="vh360-affiliate-table-wrap">
                <table class="vh360-affiliate-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Amount', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Status', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Date', 'videohub360-affiliates'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrals as $ref): ?>
                            <tr>
                                <td><?php echo $ref->order_id ? '#' . esc_html($ref->order_id) : '—'; ?></td>
                                <td><?php echo wp_kses_post(wc_price($ref->amount)); ?></td>
                                <td>
                                    <span class="vh360-affiliate-status-badge vh360-affiliate-status-<?php echo esc_attr($ref->status); ?>">
                                        <?php echo esc_html(vh360_affiliates_status_label($ref->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ref->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="vh360-affiliate-empty-state">
                <p class="vh360-affiliate-empty-state-title"><?php esc_html_e('No referrals yet.', 'videohub360-affiliates'); ?></p>
                <p><?php esc_html_e('Share your affiliate link to start tracking referred purchases.', 'videohub360-affiliates'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Commissions -->
    <div class="vh360-affiliate-table-card">
        <h3><?php esc_html_e('Recent Commissions', 'videohub360-affiliates'); ?></h3>
        <?php if ($commissions): ?>
            <div class="vh360-affiliate-table-wrap">
                <table class="vh360-affiliate-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Commission', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Status', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Date', 'videohub360-affiliates'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commissions as $comm): ?>
                            <tr>
                                <td><?php echo $comm->order_id ? '#' . esc_html($comm->order_id) : '—'; ?></td>
                                <td><?php echo wp_kses_post(wc_price($comm->commission_amount)); ?></td>
                                <td>
                                    <span class="vh360-affiliate-status-badge vh360-affiliate-status-<?php echo esc_attr($comm->status); ?>">
                                        <?php echo esc_html(vh360_affiliates_status_label($comm->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($comm->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="vh360-affiliate-empty-state">
                <p class="vh360-affiliate-empty-state-title"><?php esc_html_e('No commissions yet.', 'videohub360-affiliates'); ?></p>
                <p><?php esc_html_e('Commissions will appear here after referred orders are completed.', 'videohub360-affiliates'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payout History -->
    <div class="vh360-affiliate-table-card">
        <h3><?php esc_html_e('Payout History', 'videohub360-affiliates'); ?></h3>
        <?php if ($payouts): ?>
            <div class="vh360-affiliate-table-wrap">
                <table class="vh360-affiliate-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Amount', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Method', 'videohub360-affiliates'); ?></th>
                            <th><?php esc_html_e('Date', 'videohub360-affiliates'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payouts as $payout): ?>
                            <tr>
                                <td><?php echo wp_kses_post(wc_price($payout->amount)); ?></td>
                                <td><?php echo esc_html($payout->method ?? ''); ?></td>
                                <td><?php echo $payout->paid_at ? esc_html(date_i18n(get_option('date_format'), strtotime($payout->paid_at))) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="vh360-affiliate-empty-state">
                <p class="vh360-affiliate-empty-state-title"><?php esc_html_e('No payouts yet.', 'videohub360-affiliates'); ?></p>
                <p><?php esc_html_e('Your payout history will appear here once payments are recorded.', 'videohub360-affiliates'); ?></p>
            </div>
        <?php endif; ?>
    </div>

</div>

