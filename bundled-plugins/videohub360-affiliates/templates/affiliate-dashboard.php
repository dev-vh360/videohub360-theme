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
        <p class="vh360-aff-error"><?php echo esc_html($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="vh360-aff-success"><?php echo esc_html($success); ?></p>
    <?php endif; ?>

    <h2><?php esc_html_e('Your Affiliate Dashboard', 'videohub360-affiliates'); ?></h2>

    <!-- Overview -->
    <div class="vh360-aff-stats">
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Status', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo esc_html(vh360_affiliates_status_label($affiliate->status)); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Affiliate Code', 'videohub360-affiliates'); ?></span>
            <span class="value"><code><?php echo esc_html($affiliate->affiliate_code); ?></code></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Referral Link', 'videohub360-affiliates'); ?></span>
            <span class="value">
                <input type="text" id="vh360-referral-url" value="<?php echo esc_url($referral_url); ?>" readonly class="vh360-aff-url-input">
                <button type="button" class="vh360-aff-copy-btn" data-target="vh360-referral-url">
                    <?php esc_html_e('Copy', 'videohub360-affiliates'); ?>
                </button>
            </span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Total Visits', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo esc_html($visits); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Total Referrals', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo esc_html($refs); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Pending Commissions', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo wp_kses_post(wc_price($totals['pending'])); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Approved Commissions', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo wp_kses_post(wc_price($totals['approved'])); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Paid Commissions', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo wp_kses_post(wc_price($totals['paid'])); ?></span>
        </div>
        <div class="vh360-aff-stat">
            <span class="label"><?php esc_html_e('Rejected/Reversed', 'videohub360-affiliates'); ?></span>
            <span class="value"><?php echo wp_kses_post(wc_price($totals['rejected'] + $totals['reversed'])); ?></span>
        </div>
    </div>

    <!-- Recent Referrals -->
    <h3><?php esc_html_e('Recent Referrals', 'videohub360-affiliates'); ?></h3>
    <?php if ($referrals): ?>
        <table class="vh360-aff-table">
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
                        <td><?php echo esc_html(vh360_affiliates_status_label($ref->status)); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ref->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No referrals yet.', 'videohub360-affiliates'); ?></p>
    <?php endif; ?>

    <!-- Recent Commissions -->
    <h3><?php esc_html_e('Recent Commissions', 'videohub360-affiliates'); ?></h3>
    <?php if ($commissions): ?>
        <table class="vh360-aff-table">
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
                        <td><?php echo esc_html(vh360_affiliates_status_label($comm->status)); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($comm->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No commissions yet.', 'videohub360-affiliates'); ?></p>
    <?php endif; ?>

    <!-- Payout History -->
    <h3><?php esc_html_e('Payout History', 'videohub360-affiliates'); ?></h3>
    <?php if ($payouts): ?>
        <table class="vh360-aff-table">
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
    <?php else: ?>
        <p><?php esc_html_e('No payouts yet.', 'videohub360-affiliates'); ?></p>
    <?php endif; ?>

    <!-- Update Payment Email -->
    <h3><?php esc_html_e('Payment Email', 'videohub360-affiliates'); ?></h3>
    <form method="post" class="vh360-aff-form">
        <?php wp_nonce_field('vh360_aff_update_email', 'vh360_aff_email_nonce'); ?>
        <p>
            <label for="vh360-payment-email-update"><?php esc_html_e('Your payment email:', 'videohub360-affiliates'); ?></label><br>
            <input type="email" id="vh360-payment-email-update" name="payment_email"
                   value="<?php echo esc_attr($affiliate->payment_email ?? ''); ?>" required
                   class="vh360-aff-input">
        </p>
        <p>
            <button type="submit" class="vh360-aff-button">
                <?php esc_html_e('Update Payment Email', 'videohub360-affiliates'); ?>
            </button>
        </p>
    </form>

</div>
