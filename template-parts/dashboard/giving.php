<?php
if (!defined('ABSPATH')) exit;
$options = function_exists('vh360_giving_options') ? vh360_giving_options() : array();
$funds = class_exists('VH360_Giving_Funds') ? VH360_Giving_Funds::get_funds(true) : array();
$user_id = get_current_user_id();
$date_from = isset($_GET['giving_from']) ? sanitize_text_field(wp_unslash($_GET['giving_from'])) : '';
$date_to = isset($_GET['giving_to']) ? sanitize_text_field(wp_unslash($_GET['giving_to'])) : '';
$history = class_exists('VH360_Giving_Transactions') ? VH360_Giving_Transactions::for_user($user_id, 25, $date_from, $date_to) : array();
$recurring_table_ready = class_exists('VH360_Giving_Database') && VH360_Giving_Database::recurring_table_exists();
$recurring_gifts = ($recurring_table_ready && class_exists('VH360_Giving_Recurring')) ? VH360_Giving_Recurring::for_user($user_id) : array();
$currency = isset($options['default_currency']) ? $options['default_currency'] : 'usd';
$year_total = class_exists('VH360_Giving_Transactions') ? VH360_Giving_Transactions::total('user_id=' . absint($user_id) . " AND YEAR(given_at)=YEAR(CURDATE())") : 0;
$lifetime_total = class_exists('VH360_Giving_Transactions') ? VH360_Giving_Transactions::total('user_id=' . absint($user_id)) : 0;
$suggested = array_filter(array_map('trim', explode(',', isset($options['suggested_amounts']) ? $options['suggested_amounts'] : '10,25,50,100')));

$notice_message = '';
$notice_class = '';
if (!empty($_GET['vh360_giving_success'])) {
    $notice_message = isset($options['success_message']) ? $options['success_message'] : __('Thank you for your gift.', 'videohub360-memberships');
    $notice_class = 'vh360-giving-notice-success';
} elseif (!empty($_GET['vh360_giving_cancel'])) {
    $notice_message = isset($options['cancel_message']) ? $options['cancel_message'] : __('Your giving checkout was canceled.', 'videohub360-memberships');
    $notice_class = 'vh360-giving-notice-cancel';
}
?>
<div class="vh360-dashboard-section vh360-giving-dashboard">
    <div class="vh360-dashboard-section-header">
        <h2><?php echo esc_html(isset($options['dashboard_tab_label']) && $options['dashboard_tab_label'] ? $options['dashboard_tab_label'] : __('My Giving', 'videohub360-memberships')); ?></h2>
        <p><?php esc_html_e('Give securely and review your giving history over time.', 'videohub360-memberships'); ?></p>
    </div>
    <?php if ($notice_message) : ?>
        <div class="vh360-giving-notice <?php echo esc_attr($notice_class); ?>" data-vh360-giving-return-notice="1">
            <span class="vh360-giving-notice-message"><?php echo esc_html($notice_message); ?></span>
            <button type="button" class="vh360-giving-notice-dismiss" aria-label="<?php esc_attr_e('Dismiss notice', 'videohub360-memberships'); ?>">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (!$recurring_table_ready) : ?>
        <div class="vh360-giving-notice vh360-giving-notice-cancel">
            <?php echo esc_html(current_user_can('manage_options') ? __('Giving database tables need repair. Go to VH360 Theme → Giving → Settings and click Repair Giving Database Tables.', 'videohub360-memberships') : __('Giving is temporarily unavailable. Please try again later.', 'videohub360-memberships')); ?>
        </div>
    <?php endif; ?>

    <div class="vh360-giving-summary-cards">
        <div class="vh360-dashboard-card vh360-giving-stat-card">
            <h3><?php esc_html_e('Total Given This Year', 'videohub360-memberships'); ?></h3>
            <strong><?php echo esc_html(vh360_giving_format_amount($year_total, $currency)); ?></strong>
        </div>
        <div class="vh360-dashboard-card vh360-giving-stat-card">
            <h3><?php esc_html_e('Lifetime Total', 'videohub360-memberships'); ?></h3>
            <strong><?php echo esc_html(vh360_giving_format_amount($lifetime_total, $currency)); ?></strong>
        </div>
    </div>

    <div class="vh360-giving-layout">
        <div class="vh360-giving-main">
            <div class="vh360-dashboard-card vh360-giving-form-card">
                <h3><?php esc_html_e('Make a Gift', 'videohub360-memberships'); ?></h3>
                <p class="vh360-giving-card-intro"><?php esc_html_e('Choose a fund, amount, and frequency to continue to secure checkout.', 'videohub360-memberships'); ?></p>
                <?php if (empty($funds)) : ?>
                    <p class="vh360-giving-empty"><?php esc_html_e('No giving funds are available yet. Please contact the site administrator.', 'videohub360-memberships'); ?></p>
                <?php else : ?>
                    <form id="vh360-giving-form" class="vh360-giving-form">
                        <div class="vh360-giving-form-grid">
                            <label class="vh360-giving-field"><?php esc_html_e('Fund', 'videohub360-memberships'); ?><select name="fund_id" required><?php foreach ($funds as $fund) : ?><option value="<?php echo esc_attr($fund->id); ?>"><?php echo esc_html($fund->label); ?></option><?php endforeach; ?></select></label>
                            <label class="vh360-giving-field"><?php esc_html_e('Frequency', 'videohub360-memberships'); ?><select name="frequency" required><option value="one_time"><?php esc_html_e('One-time', 'videohub360-memberships'); ?></option><option value="weekly"><?php esc_html_e('Weekly', 'videohub360-memberships'); ?></option><option value="monthly"><?php esc_html_e('Monthly', 'videohub360-memberships'); ?></option></select></label>
                            <label class="vh360-giving-field vh360-giving-field-full"><?php esc_html_e('Custom Amount', 'videohub360-memberships'); ?><input type="number" min="<?php echo esc_attr($options['minimum_amount']); ?>" step="0.01" name="amount" required></label>
                            <div class="vh360-giving-field vh360-giving-field-full"><span class="vh360-giving-field-label"><?php esc_html_e('Suggested Amounts', 'videohub360-memberships'); ?></span><div class="vh360-giving-amounts"><?php foreach ($suggested as $amount) : ?><button type="button" class="vh360-giving-amount" data-amount="<?php echo esc_attr($amount); ?>"><?php echo esc_html(vh360_giving_format_amount($amount, $currency)); ?></button><?php endforeach; ?></div></div>
                            <?php if (!empty($options['enable_anonymous'])) : ?><label class="vh360-giving-field vh360-giving-field-full vh360-giving-check"><input type="checkbox" name="anonymous" value="1"> <?php esc_html_e('Give anonymously in public reports', 'videohub360-memberships'); ?></label><?php endif; ?>
                            <?php if (!empty($options['enable_notes'])) : ?><label class="vh360-giving-field vh360-giving-field-full"><?php esc_html_e('Note (optional)', 'videohub360-memberships'); ?><textarea name="note" rows="3"></textarea></label><?php endif; ?>
                        </div>
                        <div class="vh360-giving-submit-row"><button type="submit" class="button vh360-button-primary"><?php esc_html_e('Continue to Secure Checkout', 'videohub360-memberships'); ?></button></div>
                        <div class="vh360-giving-message" aria-live="polite"></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="vh360-giving-side">
            <div class="vh360-dashboard-card vh360-giving-recurring-card">
                <h3><?php esc_html_e('Recurring Giving', 'videohub360-memberships'); ?></h3>
                <p class="vh360-giving-card-intro"><?php esc_html_e('Manage your active weekly and monthly gifts.', 'videohub360-memberships'); ?></p>
                <div class="vh360-giving-recurring-list">
                    <?php if ($recurring_gifts) : ?>
                        <?php foreach ($recurring_gifts as $gift) : ?>
                            <div class="vh360-giving-recurring-item">
                                <div class="vh360-giving-recurring-item-header">
                                    <div class="vh360-giving-recurring-item-title"><?php echo esc_html($gift->fund_label); ?></div>
                                    <div class="vh360-giving-recurring-item-amount"><?php echo esc_html(vh360_giving_format_amount($gift->amount, $gift->currency)); ?></div>
                                </div>
                                <dl class="vh360-giving-recurring-meta">
                                    <div><dt><?php esc_html_e('Frequency', 'videohub360-memberships'); ?></dt><dd><?php echo esc_html(ucfirst($gift->giving_interval)); ?></dd></div>
                                    <div><dt><?php esc_html_e('Status', 'videohub360-memberships'); ?></dt><dd><?php echo esc_html(ucfirst(str_replace('_',' ', $gift->status))); ?><?php if (!empty($gift->cancel_at_period_end)) : ?> (<?php esc_html_e('canceling', 'videohub360-memberships'); ?>)<?php endif; ?></dd></div>
                                    <div><dt><?php esc_html_e('Started', 'videohub360-memberships'); ?></dt><dd><?php echo esc_html($gift->started_at ?: $gift->created_at); ?></dd></div>
                                    <div><dt><?php esc_html_e('Current Period End', 'videohub360-memberships'); ?></dt><dd><?php echo esc_html($gift->current_period_end ?: '—'); ?></dd></div>
                                </dl>
                                <?php if (!in_array($gift->status, array('canceled'), true) && empty($gift->cancel_at_period_end)) : ?>
                                    <div class="vh360-giving-recurring-actions"><button type="button" class="button vh360-giving-cancel-recurring" data-recurring-id="<?php echo esc_attr($gift->id); ?>"><?php esc_html_e('Cancel Recurring Gift', 'videohub360-memberships'); ?></button></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="vh360-giving-empty"><?php esc_html_e('No recurring gifts yet.', 'videohub360-memberships'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="vh360-dashboard-card vh360-giving-history-card">
        <h3><?php esc_html_e('Giving History', 'videohub360-memberships'); ?></h3>
        <p class="vh360-giving-card-intro"><?php esc_html_e('Review your completed gifts and download your records.', 'videohub360-memberships'); ?></p>
        <form method="get" class="vh360-giving-history-filters"><input type="hidden" name="tab" value="giving"><label class="vh360-giving-filter-field"><?php esc_html_e('From', 'videohub360-memberships'); ?><input type="date" name="giving_from" value="<?php echo esc_attr($date_from); ?>"></label><label class="vh360-giving-filter-field"><?php esc_html_e('To', 'videohub360-memberships'); ?><input type="date" name="giving_to" value="<?php echo esc_attr($date_to); ?>"></label><div class="vh360-giving-filter-actions"><button type="submit" class="button"><?php esc_html_e('Filter', 'videohub360-memberships'); ?></button><a class="button" href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'vh360_download_giving_history','date_from'=>$date_from,'date_to'=>$date_to), admin_url('admin-post.php')), 'vh360_download_giving_history')); ?>"><?php esc_html_e('Download Giving History', 'videohub360-memberships'); ?></a></div></form>
        <div class="vh360-giving-table-wrap"><table class="vh360-giving-history"><thead><tr><th><?php esc_html_e('Date','videohub360-memberships'); ?></th><th><?php esc_html_e('Fund','videohub360-memberships'); ?></th><th><?php esc_html_e('Amount','videohub360-memberships'); ?></th><th><?php esc_html_e('Status','videohub360-memberships'); ?></th></tr></thead><tbody><?php if ($history) : foreach ($history as $gift) : ?><tr><td data-label="<?php esc_attr_e('Date','videohub360-memberships'); ?>"><?php echo esc_html($gift->given_at ?: $gift->created_at); ?></td><td data-label="<?php esc_attr_e('Fund','videohub360-memberships'); ?>"><?php echo esc_html($gift->fund_label); ?></td><td data-label="<?php esc_attr_e('Amount','videohub360-memberships'); ?>"><?php echo esc_html(vh360_giving_format_amount($gift->amount, $gift->currency)); ?></td><td data-label="<?php esc_attr_e('Status','videohub360-memberships'); ?>"><?php echo esc_html(ucfirst($gift->status)); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e('No giving history yet.', 'videohub360-memberships'); ?></td></tr><?php endif; ?></tbody></table></div>
    </div>
</div>
