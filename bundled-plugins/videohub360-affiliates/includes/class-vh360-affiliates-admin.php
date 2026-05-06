<?php
/**
 * Admin management screens and settings.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Admin {

    /** @var VH360_Affiliates_Admin|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu',            array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init',            array($this, 'handle_actions'));
        add_action('admin_init',            array($this, 'register_settings'));
    }

    // -----------------------------------------------------------------------
    // Menus
    // -----------------------------------------------------------------------

    public function register_menus() {
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';

        add_menu_page(
            __('VH360 Affiliates', 'videohub360-affiliates'),
            __('VH360 Affiliates', 'videohub360-affiliates'),
            $cap,
            'vh360-affiliates',
            array($this, 'page_affiliates'),
            'dashicons-groups',
            56
        );

        add_submenu_page('vh360-affiliates', __('Affiliates',   'videohub360-affiliates'), __('Affiliates',   'videohub360-affiliates'), $cap, 'vh360-affiliates',             array($this, 'page_affiliates'));
        add_submenu_page('vh360-affiliates', __('Visits',       'videohub360-affiliates'), __('Visits',       'videohub360-affiliates'), $cap, 'vh360-affiliates-visits',      array($this, 'page_visits'));
        add_submenu_page('vh360-affiliates', __('Referrals',    'videohub360-affiliates'), __('Referrals',    'videohub360-affiliates'), $cap, 'vh360-affiliates-referrals',   array($this, 'page_referrals'));
        add_submenu_page('vh360-affiliates', __('Commissions',  'videohub360-affiliates'), __('Commissions',  'videohub360-affiliates'), $cap, 'vh360-affiliates-commissions', array($this, 'page_commissions'));
        add_submenu_page('vh360-affiliates', __('Payouts',      'videohub360-affiliates'), __('Payouts',      'videohub360-affiliates'), $cap, 'vh360-affiliates-payouts',     array($this, 'page_payouts'));
        add_submenu_page('vh360-affiliates', __('Settings',     'videohub360-affiliates'), __('Settings',     'videohub360-affiliates'), $cap, 'vh360-affiliates-settings',    array($this, 'page_settings'));
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'vh360-affiliates') === false) {
            return;
        }
        wp_enqueue_style('vh360-affiliates-admin', VH360_AFFILIATES_URL . 'assets/css/admin.css', array(), VH360_AFFILIATES_VERSION);
        wp_enqueue_script('vh360-affiliates-admin', VH360_AFFILIATES_URL . 'assets/js/admin.js', array('jquery'), VH360_AFFILIATES_VERSION, true);
        wp_localize_script('vh360-affiliates-admin', 'vh360Affiliates', array(
            'nonce' => wp_create_nonce('vh360_affiliates_admin'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ));
    }

    // -----------------------------------------------------------------------
    // Settings API
    // -----------------------------------------------------------------------

    public function register_settings() {
        register_setting('vh360_affiliates_settings_group', 'vh360_affiliates_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    public function sanitize_settings($input) {
        $output = array();
        $output['enabled']                 = !empty($input['enabled']) ? 1 : 0;
        $output['require_manual_approval'] = !empty($input['require_manual_approval']) ? 1 : 0;
        $output['referral_query_var']      = sanitize_key($input['referral_query_var'] ?? 'ref') ?: 'ref';
        $output['cookie_duration']         = absint($input['cookie_duration'] ?? 30);
        $output['attribution_model']       = in_array($input['attribution_model'] ?? '', array('first_click', 'last_click'), true) ? $input['attribution_model'] : 'first_click';
        $output['default_commission_type'] = in_array($input['default_commission_type'] ?? '', array('percentage', 'flat'), true) ? $input['default_commission_type'] : 'percentage';
        $output['default_commission_rate'] = (float) ($input['default_commission_rate'] ?? 20);
        $output['commission_status']       = in_array($input['commission_status'] ?? '', array('pending', 'approved'), true) ? $input['commission_status'] : 'pending';
        $output['auto_approve_days']       = absint($input['auto_approve_days'] ?? 0);
        $output['min_payout_amount']       = (float) ($input['min_payout_amount'] ?? 50);
        $output['allow_self_referrals']    = !empty($input['allow_self_referrals']) ? 1 : 0;
        $output['payout_instructions']     = wp_kses_post($input['payout_instructions'] ?? '');
        $output['terms_page_url']          = esc_url_raw($input['terms_page_url'] ?? '');
        $output['visit_retention_days']    = absint($input['visit_retention_days'] ?? 180);
        $output['email_notifications']     = !empty($input['email_notifications']) ? 1 : 0;

        // Email sender overrides.
        $from_name  = sanitize_text_field($input['email_from_name'] ?? '');
        $from_name  = preg_replace('/[\r\n]+/', '', $from_name);
        $output['email_from_name']  = $from_name;
        $output['email_from_email'] = sanitize_email($input['email_from_email'] ?? '');
        $output['email_reply_to']   = sanitize_email($input['email_reply_to'] ?? '');

        return $output;
    }

    // -----------------------------------------------------------------------
    // Admin action handler (approve/reject/suspend etc.)
    // -----------------------------------------------------------------------

    public function handle_actions() {
        if (!isset($_GET['vh360_aff_action']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            return;
        }

        $action = sanitize_key(wp_unslash($_GET['vh360_aff_action']));
        $nonce  = sanitize_text_field(wp_unslash($_GET['_wpnonce']));

        if (!wp_verify_nonce($nonce, 'vh360_aff_action')) {
            wp_die(esc_html__('Security check failed.', 'videohub360-affiliates'));
        }

        $affiliate_id  = isset($_GET['affiliate_id'])  ? (int) wp_unslash($_GET['affiliate_id'])  : 0;
        $commission_id = isset($_GET['commission_id']) ? (int) wp_unslash($_GET['commission_id']) : 0;

        switch ($action) {
            case 'approve_affiliate':
                $this->approve_affiliate($affiliate_id);
                break;
            case 'reject_affiliate':
                $this->change_affiliate_status($affiliate_id, 'rejected');
                break;
            case 'suspend_affiliate':
                $this->change_affiliate_status($affiliate_id, 'suspended');
                break;
            case 'restore_affiliate':
                $this->change_affiliate_status($affiliate_id, 'active');
                break;
            case 'save_affiliate':
                $this->save_affiliate_edit($affiliate_id);
                // After save, redirect back to edit page (not list) so admin can review
                wp_safe_redirect(admin_url('admin.php?page=vh360-affiliates&action=edit&affiliate_id=' . $affiliate_id . '&updated=1'));
                exit;
            case 'approve_commission':
                $this->approve_commission($commission_id);
                break;
            case 'reject_commission':
                $this->change_commission_status($commission_id, 'rejected');
                break;
            case 'reverse_commission':
                $this->change_commission_status($commission_id, 'reversed');
                break;
        }

        // Redirect back
        $redirect = remove_query_arg(array('vh360_aff_action', '_wpnonce', 'affiliate_id', 'commission_id'));
        wp_safe_redirect($redirect);
        exit;
    }

    // -----------------------------------------------------------------------
    // Affiliate status helpers
    // -----------------------------------------------------------------------

    private function approve_affiliate($affiliate_id) {
        if (!$affiliate_id) {
            return;
        }
        VH360_Affiliates_Database::update_affiliate($affiliate_id, array('status' => 'active'));

        $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
        if ($affiliate) {
            $user = get_userdata($affiliate->user_id);
            if ($user) {
                vh360_affiliates_send_email(
                    $user->user_email,
                    __('Your affiliate application has been approved', 'videohub360-affiliates'),
                    sprintf(
                        /* translators: %s: affiliate code */
                        __("Congratulations! Your affiliate application has been approved.\n\nYour affiliate code: %s\n\nYou can now log in and view your affiliate dashboard.", 'videohub360-affiliates'),
                        $affiliate->affiliate_code
                    )
                );
            }
        }
    }

    private function change_affiliate_status($affiliate_id, $status) {
        if (!$affiliate_id) {
            return;
        }
        VH360_Affiliates_Database::update_affiliate($affiliate_id, array('status' => $status));

        if ($status === 'rejected') {
            $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
            if ($affiliate) {
                $user = get_userdata($affiliate->user_id);
                if ($user) {
                    vh360_affiliates_send_email(
                        $user->user_email,
                        __('Your affiliate application status has changed', 'videohub360-affiliates'),
                        __("We regret to inform you that your affiliate application has been reviewed and was not approved at this time.", 'videohub360-affiliates')
                    );
                }
            }
        }
    }

    private function approve_commission($commission_id) {
        if (!$commission_id) {
            return;
        }
        VH360_Affiliates_Database::update_commission_status(
            $commission_id,
            'approved',
            array('approved_at' => current_time('mysql'))
        );
        $this->sync_referral_status($commission_id, 'approved');
    }

    private function change_commission_status($commission_id, $status) {
        if (!$commission_id) {
            return;
        }
        $extra = array();
        if ($status === 'rejected') {
            $extra['rejected_at'] = current_time('mysql');
        }
        VH360_Affiliates_Database::update_commission_status($commission_id, $status, $extra);
        $this->sync_referral_status($commission_id, $status);
    }

    /**
     * Sync the referral row status to match a commission status change.
     *
     * @param int    $commission_id
     * @param string $status
     */
    private function sync_referral_status($commission_id, $status) {
        $commission = VH360_Affiliates_Database::get_commission_by_id($commission_id);
        if ($commission && !empty($commission->referral_id)) {
            VH360_Affiliates_Database::update_referral_status($commission->referral_id, $status);
        }
    }

    // -----------------------------------------------------------------------
    // Page renderers
    // -----------------------------------------------------------------------

    public function page_affiliates() {
        global $wpdb;
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        // Show edit form when requested
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        if ($action === 'edit' && isset($_GET['affiliate_id'])) {
            $this->page_edit_affiliate((int) $_GET['affiliate_id']);
            return;
        }

        $table      = $wpdb->prefix . 'vh360_affiliates';
        $affiliates = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Affiliates', 'videohub360-affiliates') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        foreach (array(__('User'), __('Email'), __('Code'), __('Status'), __('Rate'), __('Visits'), __('Referrals'), __('Pending'), __('Approved'), __('Paid'), __('Created'), __('Actions')) as $col) {
            echo '<th>' . esc_html($col) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ($affiliates) {
            foreach ($affiliates as $aff) {
                $user    = get_userdata($aff->user_id);
                $totals  = VH360_Affiliates_Database::get_commission_totals($aff->id);
                $visits  = VH360_Affiliates_Database::get_visit_count($aff->id);
                $refs    = VH360_Affiliates_Database::get_referral_count($aff->id);
                $actions = $this->build_affiliate_actions($aff);

                echo '<tr>';
                echo '<td>' . esc_html($user ? $user->display_name : '#' . $aff->user_id) . '</td>';
                echo '<td>' . esc_html($user ? $user->user_email : '') . '</td>';
                echo '<td><code>' . esc_html($aff->affiliate_code) . '</code></td>';
                echo '<td>' . esc_html(vh360_affiliates_status_label($aff->status)) . '</td>';
                echo '<td>' . wp_kses_post($this->format_commission_rate_display($aff->commission_type, $aff->commission_rate)) . '</td>';
                echo '<td>' . esc_html($visits) . '</td>';
                echo '<td>' . esc_html($refs) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($totals['pending'])) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($totals['approved'])) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($totals['paid'])) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($aff->created_at))) . '</td>';
                echo '<td>' . $actions . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped inside build_affiliate_actions
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="12">' . esc_html__('No affiliates found.', 'videohub360-affiliates') . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    private function build_affiliate_actions($aff) {
        $base   = admin_url('admin.php?page=vh360-affiliates');
        $nonce  = wp_create_nonce('vh360_aff_action');
        $links  = array();

        if ($aff->status === 'pending') {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'approve_affiliate', 'affiliate_id' => $aff->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Approve', 'videohub360-affiliates') . '</a>';
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'reject_affiliate', 'affiliate_id' => $aff->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Reject', 'videohub360-affiliates') . '</a>';
        }
        if ($aff->status === 'active') {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'suspend_affiliate', 'affiliate_id' => $aff->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Suspend', 'videohub360-affiliates') . '</a>';
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'reject_affiliate', 'affiliate_id' => $aff->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Reject', 'videohub360-affiliates') . '</a>';
        }
        if (in_array($aff->status, array('rejected', 'suspended'), true)) {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'restore_affiliate', 'affiliate_id' => $aff->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Restore', 'videohub360-affiliates') . '</a>';
        }
        $links[] = '<a href="' . esc_url(admin_url('user-edit.php?user_id=' . $aff->user_id)) . '">' . esc_html__('Edit User', 'videohub360-affiliates') . '</a>';
        $links[] = '<a href="' . esc_url(admin_url('admin.php?page=vh360-affiliates&action=edit&affiliate_id=' . $aff->id)) . '">' . esc_html__('Edit Affiliate', 'videohub360-affiliates') . '</a>';

        return implode(' | ', $links);
    }

    // -----------------------------------------------------------------------
    // Affiliate edit form
    // -----------------------------------------------------------------------

    /**
     * Render the affiliate edit form.
     *
     * @param int $affiliate_id
     */
    private function page_edit_affiliate($affiliate_id) {
        $aff = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
        if (!$aff) {
            echo '<div class="wrap"><p>' . esc_html__('Affiliate not found.', 'videohub360-affiliates') . '</p></div>';
            return;
        }

        $nonce    = wp_create_nonce('vh360_aff_action');
        $updated  = isset($_GET['updated']) && $_GET['updated'] === '1';
        $list_url = admin_url('admin.php?page=vh360-affiliates');
        $form_url = add_query_arg(array(
            'vh360_aff_action' => 'save_affiliate',
            'affiliate_id'     => $aff->id,
            '_wpnonce'         => $nonce,
        ), admin_url('admin.php?page=vh360-affiliates'));

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Edit Affiliate', 'videohub360-affiliates'); ?></h1>
            <p><a href="<?php echo esc_url($list_url); ?>">&larr; <?php esc_html_e('Back to Affiliates', 'videohub360-affiliates'); ?></a></p>
            <?php if ($updated) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Affiliate updated.', 'videohub360-affiliates'); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url($form_url); ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="vh360_aff_code"><?php esc_html_e('Affiliate Code', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <input type="text" id="vh360_aff_code" name="vh360_aff_code"
                                value="<?php echo esc_attr($aff->affiliate_code); ?>"
                                class="regular-text" maxlength="80" pattern="[a-z0-9\-]+"
                                title="<?php esc_attr_e('Lowercase letters, numbers and dashes only.', 'videohub360-affiliates'); ?>">
                            <p class="description"><?php esc_html_e('Lowercase letters, numbers, and dashes only. Must be unique.', 'videohub360-affiliates'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360_aff_status"><?php esc_html_e('Status', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <select id="vh360_aff_status" name="vh360_aff_status">
                                <?php foreach (array('pending', 'active', 'rejected', 'suspended') as $s) : ?>
                                    <option value="<?php echo esc_attr($s); ?>" <?php selected($s, $aff->status); ?>><?php echo esc_html(vh360_affiliates_status_label($s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360_aff_comm_type"><?php esc_html_e('Commission Type', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <select id="vh360_aff_comm_type" name="vh360_aff_comm_type">
                                <option value="percentage" <?php selected('percentage', $aff->commission_type); ?>><?php esc_html_e('Percentage (%)', 'videohub360-affiliates'); ?></option>
                                <option value="flat"       <?php selected('flat',       $aff->commission_type); ?>><?php esc_html_e('Flat Amount',    'videohub360-affiliates'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360_aff_comm_rate"><?php esc_html_e('Commission Rate', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <input type="number" id="vh360_aff_comm_rate" name="vh360_aff_comm_rate"
                                value="<?php echo esc_attr($aff->commission_rate); ?>"
                                step="0.01" min="0" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360_aff_payment_email"><?php esc_html_e('Payment Email', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <input type="email" id="vh360_aff_payment_email" name="vh360_aff_payment_email"
                                value="<?php echo esc_attr($aff->payment_email ?? ''); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360_aff_notes"><?php esc_html_e('Internal Notes', 'videohub360-affiliates'); ?></label></th>
                        <td>
                            <textarea id="vh360_aff_notes" name="vh360_aff_notes" rows="5" class="large-text"><?php echo esc_textarea($aff->notes ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Affiliate', 'videohub360-affiliates')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Process the affiliate edit form POST.
     *
     * @param int $affiliate_id
     */
    private function save_affiliate_edit($affiliate_id) {
        if (!$affiliate_id) {
            return;
        }

        $existing = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
        if (!$existing) {
            return;
        }

        // Sanitize and validate code
        $new_code = strtolower(sanitize_title(wp_unslash($_POST['vh360_aff_code'] ?? '')));
        $new_code = preg_replace('/[^a-z0-9\-]/', '', $new_code);
        $new_code = substr($new_code, 0, 80);

        if (empty($new_code)) {
            $new_code = $existing->affiliate_code;
        }

        // Reserved words check
        $reserved = array('admin', 'administrator', 'login', 'logout', 'register', 'checkout', 'cart', 'account', 'support', 'dashboard', 'videohub360', 'vh360');
        if (in_array($new_code, $reserved, true)) {
            $new_code = $existing->affiliate_code; // revert to original if reserved
        }

        // Uniqueness: if code changed, ensure no other affiliate uses it
        if ($new_code !== $existing->affiliate_code) {
            $conflict = VH360_Affiliates_Database::get_affiliate_by_code($new_code);
            if ($conflict) {
                $new_code = $existing->affiliate_code; // revert on conflict
            }
        }

        $status = sanitize_key(wp_unslash($_POST['vh360_aff_status'] ?? 'pending'));
        if (!in_array($status, array('pending', 'active', 'rejected', 'suspended'), true)) {
            $status = 'pending';
        }

        $comm_type = sanitize_key(wp_unslash($_POST['vh360_aff_comm_type'] ?? 'percentage'));
        if (!in_array($comm_type, array('percentage', 'flat'), true)) {
            $comm_type = 'percentage';
        }

        $comm_rate     = max(0, (float) wp_unslash($_POST['vh360_aff_comm_rate'] ?? 0));
        $payment_email = sanitize_email(wp_unslash($_POST['vh360_aff_payment_email'] ?? ''));
        $notes         = sanitize_textarea_field(wp_unslash($_POST['vh360_aff_notes'] ?? ''));

        VH360_Affiliates_Database::update_affiliate($affiliate_id, array(
            'affiliate_code'  => $new_code,
            'status'          => $status,
            'commission_type' => $comm_type,
            'commission_rate' => $comm_rate,
            'payment_email'   => $payment_email ?: null,
            'notes'           => $notes,
        ));
    }

    public function page_visits() {
        global $wpdb;
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        $table  = $wpdb->prefix . 'vh360_affiliate_visits';
        $visits = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200");

        echo '<div class="wrap"><h1>' . esc_html__('Referral Visits', 'videohub360-affiliates') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__('Affiliate', 'videohub360-affiliates') . '</th><th>' . esc_html__('Landing URL', 'videohub360-affiliates') . '</th><th>' . esc_html__('Referrer URL', 'videohub360-affiliates') . '</th><th>' . esc_html__('Created', 'videohub360-affiliates') . '</th><th>' . esc_html__('Converted', 'videohub360-affiliates') . '</th></tr></thead><tbody>';

        if ($visits) {
            foreach ($visits as $v) {
                $aff = VH360_Affiliates_Database::get_affiliate_by_id($v->affiliate_id);
                echo '<tr>';
                echo '<td>' . esc_html($aff ? $aff->affiliate_code : '#' . $v->affiliate_id) . '</td>';
                echo '<td>' . esc_html($v->landing_url) . '</td>';
                echo '<td>' . esc_html($v->referrer_url) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($v->created_at))) . '</td>';
                echo '<td>' . ($v->converted_at ? esc_html(date_i18n(get_option('date_format'), strtotime($v->converted_at))) : '—') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('No visits found.', 'videohub360-affiliates') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function page_referrals() {
        global $wpdb;
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        $table     = $wpdb->prefix . 'vh360_affiliate_referrals';
        $referrals = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200");

        echo '<div class="wrap"><h1>' . esc_html__('Referrals', 'videohub360-affiliates') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        foreach (array(__('Affiliate'), __('Order'), __('Product'), __('Customer'), __('Amount'), __('Currency'), __('Status'), __('Created')) as $col) {
            echo '<th>' . esc_html($col) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ($referrals) {
            foreach ($referrals as $r) {
                $aff     = VH360_Affiliates_Database::get_affiliate_by_id($r->affiliate_id);
                $product = $r->product_id ? get_the_title($r->product_id) : '—';
                $cust    = $r->user_id ? get_userdata($r->user_id) : null;
                echo '<tr>';
                echo '<td>' . esc_html($aff ? $aff->affiliate_code : '#' . $r->affiliate_id) . '</td>';
                echo '<td>' . ($r->order_id ? '<a href="' . esc_url(admin_url('post.php?post=' . $r->order_id . '&action=edit')) . '">#' . esc_html($r->order_id) . '</a>' : '—') . '</td>';
                echo '<td>' . esc_html($product) . '</td>';
                echo '<td>' . esc_html($cust ? $cust->user_email : '—') . '</td>';
                echo '<td>' . wp_kses_post(wc_price($r->amount)) . '</td>';
                echo '<td>' . esc_html($r->currency) . '</td>';
                echo '<td>' . esc_html(vh360_affiliates_status_label($r->status)) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($r->created_at))) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">' . esc_html__('No referrals found.', 'videohub360-affiliates') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function page_commissions() {
        global $wpdb;
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        $table       = $wpdb->prefix . 'vh360_affiliate_commissions';
        $commissions = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200");
        $base        = admin_url('admin.php?page=vh360-affiliates-commissions');
        $nonce       = wp_create_nonce('vh360_aff_action');

        echo '<div class="wrap"><h1>' . esc_html__('Commissions', 'videohub360-affiliates') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        foreach (array(__('Affiliate'), __('Order'), __('Product'), __('Base'), __('Rate'), __('Commission'), __('Currency'), __('Status'), __('Reason'), __('Created'), __('Actions')) as $col) {
            echo '<th>' . esc_html($col) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ($commissions) {
            foreach ($commissions as $c) {
                $aff     = VH360_Affiliates_Database::get_affiliate_by_id($c->affiliate_id);
                $product = $c->product_id ? get_the_title($c->product_id) : '—';
                $actions = $this->build_commission_actions($c, $base, $nonce);
                echo '<tr>';
                echo '<td>' . esc_html($aff ? $aff->affiliate_code : '#' . $c->affiliate_id) . '</td>';
                echo '<td>' . ($c->order_id ? '<a href="' . esc_url(admin_url('post.php?post=' . $c->order_id . '&action=edit')) . '">#' . esc_html($c->order_id) . '</a>' : '—') . '</td>';
                echo '<td>' . esc_html($product) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($c->base_amount)) . '</td>';
                echo '<td>' . wp_kses_post($this->format_commission_rate_display($c->commission_type, $c->commission_rate)) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($c->commission_amount)) . '</td>';
                echo '<td>' . esc_html($c->currency) . '</td>';
                echo '<td>' . esc_html(vh360_affiliates_status_label($c->status)) . '</td>';
                echo '<td>' . esc_html($c->reason ?? '') . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($c->created_at))) . '</td>';
                echo '<td>' . $actions . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="11">' . esc_html__('No commissions found.', 'videohub360-affiliates') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function format_commission_rate_display($commission_type, $commission_rate) {
        $rate = (float) $commission_rate;
        if ('flat' === $commission_type) {
            if (function_exists('wc_price')) {
                return wp_kses_post(wc_price($rate));
            }
            return esc_html('$' . number_format_i18n($rate, 2));
        }
        return esc_html(number_format_i18n($rate, 2) . '%');
    }

    private function build_commission_actions($c, $base, $nonce) {
        $links = array();
        if ($c->status === 'pending') {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'approve_commission', 'commission_id' => $c->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Approve', 'videohub360-affiliates') . '</a>';
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'reject_commission',  'commission_id' => $c->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Reject',  'videohub360-affiliates') . '</a>';
        }
        if ($c->status === 'approved') {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'reject_commission', 'commission_id' => $c->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Reject', 'videohub360-affiliates') . '</a>';
        }
        if ($c->status === 'paid') {
            $links[] = '<a href="' . esc_url(add_query_arg(array('vh360_aff_action' => 'reverse_commission', 'commission_id' => $c->id, '_wpnonce' => $nonce), $base)) . '">' . esc_html__('Reverse', 'videohub360-affiliates') . '</a>';
        }
        return implode(' | ', $links);
    }

    public function page_payouts() {
        global $wpdb;
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        // Handle payout form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vh360_create_payout_nonce'])) {
            $this->handle_payout_form();
        }

        // List approved commissions ready for payout
        $comm_table = $wpdb->prefix . 'vh360_affiliate_commissions';
        $approved   = $wpdb->get_results("SELECT * FROM {$comm_table} WHERE status = 'approved' ORDER BY created_at DESC");

        $payouts_table = $wpdb->prefix . 'vh360_affiliate_payouts';
        $payouts       = $wpdb->get_results("SELECT * FROM {$payouts_table} ORDER BY created_at DESC LIMIT 100");

        echo '<div class="wrap"><h1>' . esc_html__('Payouts', 'videohub360-affiliates') . '</h1>';

        // Payout creation form
        if ($approved) {
            echo '<form method="post">';
            wp_nonce_field('vh360_create_payout', 'vh360_create_payout_nonce');
            echo '<h2>' . esc_html__('Approved Commissions Ready for Payout', 'videohub360-affiliates') . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            echo '<th><input type="checkbox" id="vh360-select-all"></th>';
            foreach (array(__('Affiliate'), __('Order'), __('Amount'), __('Currency'), __('Created')) as $col) {
                echo '<th>' . esc_html($col) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ($approved as $c) {
                $aff = VH360_Affiliates_Database::get_affiliate_by_id($c->affiliate_id);
                echo '<tr>';
                echo '<td><input type="checkbox" name="commission_ids[]" value="' . esc_attr($c->id) . '"></td>';
                echo '<td>' . esc_html($aff ? $aff->affiliate_code : '#' . $c->affiliate_id) . '</td>';
                echo '<td>' . ($c->order_id ? '#' . esc_html($c->order_id) : '—') . '</td>';
                echo '<td>' . wp_kses_post(wc_price($c->commission_amount)) . '</td>';
                echo '<td>' . esc_html($c->currency) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($c->created_at))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '<p>';
            echo '<label>' . esc_html__('Method:', 'videohub360-affiliates') . ' <input type="text" name="payout_method" placeholder="e.g. PayPal, Bank Transfer"></label> ';
            echo '<label>' . esc_html__('Reference:', 'videohub360-affiliates') . ' <input type="text" name="payout_reference" placeholder="Transaction ID"></label> ';
            echo '</p><p>';
            echo '<label>' . esc_html__('Notes:', 'videohub360-affiliates') . '<br><textarea name="payout_notes" rows="3" cols="50"></textarea></label>';
            echo '</p>';
            echo '<input type="submit" class="button button-primary" value="' . esc_attr__('Mark Selected as Paid', 'videohub360-affiliates') . '">';
            echo '</form>';
        } else {
            echo '<p>' . esc_html__('No approved commissions are ready for payout.', 'videohub360-affiliates') . '</p>';
        }

        // Payout history
        echo '<h2>' . esc_html__('Payout History', 'videohub360-affiliates') . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        foreach (array(__('Affiliate'), __('Amount'), __('Currency'), __('Method'), __('Reference'), __('Notes'), __('Paid At')) as $col) {
            echo '<th>' . esc_html($col) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ($payouts) {
            foreach ($payouts as $p) {
                $aff = VH360_Affiliates_Database::get_affiliate_by_id($p->affiliate_id);
                echo '<tr>';
                echo '<td>' . esc_html($aff ? $aff->affiliate_code : '#' . $p->affiliate_id) . '</td>';
                echo '<td>' . wp_kses_post(wc_price($p->amount)) . '</td>';
                echo '<td>' . esc_html($p->currency) . '</td>';
                echo '<td>' . esc_html($p->method ?? '') . '</td>';
                echo '<td>' . esc_html($p->transaction_reference ?? '') . '</td>';
                echo '<td>' . esc_html($p->notes ?? '') . '</td>';
                echo '<td>' . ($p->paid_at ? esc_html(date_i18n(get_option('date_format'), strtotime($p->paid_at))) : '—') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">' . esc_html__('No payouts recorded.', 'videohub360-affiliates') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    private function handle_payout_form() {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_create_payout_nonce'] ?? '')), 'vh360_create_payout')) {
            wp_die(esc_html__('Security check failed.', 'videohub360-affiliates'));
        }

        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            return;
        }

        $commission_ids = isset($_POST['commission_ids']) ? array_map('intval', (array) wp_unslash($_POST['commission_ids'])) : array();
        if (empty($commission_ids)) {
            return;
        }

        $method    = sanitize_text_field(wp_unslash($_POST['payout_method'] ?? ''));
        $reference = sanitize_text_field(wp_unslash($_POST['payout_reference'] ?? ''));
        $notes     = sanitize_textarea_field(wp_unslash($_POST['payout_notes'] ?? ''));

        // Group commissions by affiliate
        $by_affiliate = array();
        foreach ($commission_ids as $cid) {
            global $wpdb;
            $comm_table = $wpdb->prefix . 'vh360_affiliate_commissions';
            $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$comm_table} WHERE id = %d AND status = 'approved'", $cid));
            if ($c) {
                $by_affiliate[$c->affiliate_id][] = $c;
            }
        }

        foreach ($by_affiliate as $affiliate_id => $commissions) {
            $total    = array_sum(array_column($commissions, 'commission_amount'));
            $currency = $commissions[0]->currency;

            // Create payout record
            VH360_Affiliates_Database::insert_payout(array(
                'affiliate_id'          => $affiliate_id,
                'amount'                => round($total, 2),
                'currency'              => $currency,
                'method'                => $method,
                'transaction_reference' => $reference,
                'status'                => 'paid',
                'notes'                 => $notes,
                'paid_at'               => current_time('mysql'),
            ));

            // Mark commissions as paid and sync referral status
            foreach ($commissions as $c) {
                VH360_Affiliates_Database::update_commission_status($c->id, 'paid', array('paid_at' => current_time('mysql')));
                if (!empty($c->referral_id)) {
                    VH360_Affiliates_Database::update_referral_status($c->referral_id, 'paid');
                }
            }

            // Notify affiliate
            $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
            if ($affiliate) {
                $user = get_userdata($affiliate->user_id);
                if ($user) {
                    vh360_affiliates_send_email(
                        $user->user_email,
                        __('Commission payment processed', 'videohub360-affiliates'),
                        sprintf(
                            /* translators: %s: amount */
                            __("A commission payment of %s has been processed for your affiliate account.", 'videohub360-affiliates'),
                            wc_price($total)
                        )
                    );
                }
            }
        }
    }

    public function page_settings() {
        $cap = current_user_can('vh360_manage_affiliates') ? 'vh360_manage_affiliates' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Access denied.', 'videohub360-affiliates'));
        }

        $s = vh360_affiliates_get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VideoHub360 Affiliates — Settings', 'videohub360-affiliates'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('vh360_affiliates_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Enable Affiliate Program', 'videohub360-affiliates'); ?></th>
                        <td><input type="checkbox" name="vh360_affiliates_settings[enabled]" value="1" <?php checked(1, $s['enabled']); ?>></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Require Manual Approval', 'videohub360-affiliates'); ?></th>
                        <td><input type="checkbox" name="vh360_affiliates_settings[require_manual_approval]" value="1" <?php checked(1, $s['require_manual_approval']); ?>></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Referral Query Variable', 'videohub360-affiliates'); ?></th>
                        <td><input type="text" name="vh360_affiliates_settings[referral_query_var]" value="<?php echo esc_attr($s['referral_query_var']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Cookie Duration (days)', 'videohub360-affiliates'); ?></th>
                        <td><input type="number" name="vh360_affiliates_settings[cookie_duration]" value="<?php echo esc_attr($s['cookie_duration']); ?>" min="1" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Attribution Model', 'videohub360-affiliates'); ?></th>
                        <td>
                            <select name="vh360_affiliates_settings[attribution_model]">
                                <option value="first_click" <?php selected('first_click', $s['attribution_model']); ?>><?php esc_html_e('First Click', 'videohub360-affiliates'); ?></option>
                                <option value="last_click"  <?php selected('last_click',  $s['attribution_model']); ?>><?php esc_html_e('Last Click',  'videohub360-affiliates'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Default Commission Type', 'videohub360-affiliates'); ?></th>
                        <td>
                            <select name="vh360_affiliates_settings[default_commission_type]">
                                <option value="percentage" <?php selected('percentage', $s['default_commission_type']); ?>><?php esc_html_e('Percentage (%)', 'videohub360-affiliates'); ?></option>
                                <option value="flat"       <?php selected('flat',       $s['default_commission_type']); ?>><?php esc_html_e('Flat Amount',    'videohub360-affiliates'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Default Commission Rate', 'videohub360-affiliates'); ?></th>
                        <td><input type="number" name="vh360_affiliates_settings[default_commission_rate]" value="<?php echo esc_attr($s['default_commission_rate']); ?>" step="0.01" min="0" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Commission Status After Sale', 'videohub360-affiliates'); ?></th>
                        <td>
                            <select name="vh360_affiliates_settings[commission_status]">
                                <option value="pending"  <?php selected('pending',  $s['commission_status']); ?>><?php esc_html_e('Pending',  'videohub360-affiliates'); ?></option>
                                <option value="approved" <?php selected('approved', $s['commission_status']); ?>><?php esc_html_e('Approved', 'videohub360-affiliates'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Auto-approve commissions after (days, 0=disabled)', 'videohub360-affiliates'); ?></th>
                        <td><input type="number" name="vh360_affiliates_settings[auto_approve_days]" value="<?php echo esc_attr($s['auto_approve_days']); ?>" min="0" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Minimum Payout Amount', 'videohub360-affiliates'); ?></th>
                        <td><input type="number" name="vh360_affiliates_settings[min_payout_amount]" value="<?php echo esc_attr($s['min_payout_amount']); ?>" step="0.01" min="0" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Allow Self-referrals', 'videohub360-affiliates'); ?></th>
                        <td><input type="checkbox" name="vh360_affiliates_settings[allow_self_referrals]" value="1" <?php checked(1, $s['allow_self_referrals']); ?>></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Payout Instructions', 'videohub360-affiliates'); ?></th>
                        <td><textarea name="vh360_affiliates_settings[payout_instructions]" rows="4" class="large-text"><?php echo esc_textarea($s['payout_instructions']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Affiliate Terms Page URL', 'videohub360-affiliates'); ?></th>
                        <td><input type="url" name="vh360_affiliates_settings[terms_page_url]" value="<?php echo esc_url($s['terms_page_url']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Visit Log Retention (days)', 'videohub360-affiliates'); ?></th>
                        <td><input type="number" name="vh360_affiliates_settings[visit_retention_days]" value="<?php echo esc_attr($s['visit_retention_days']); ?>" min="1" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Email Notifications', 'videohub360-affiliates'); ?></th>
                        <td><input type="checkbox" name="vh360_affiliates_settings[email_notifications]" value="1" <?php checked(1, $s['email_notifications']); ?>></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Email From Name', 'videohub360-affiliates'); ?></th>
                        <td>
                            <input type="text" name="vh360_affiliates_settings[email_from_name]"
                                value="<?php echo esc_attr($s['email_from_name']); ?>"
                                placeholder="<?php echo esc_attr(vh360_affiliates_default_from_name()); ?>"
                                class="regular-text">
                            <p class="description"><?php esc_html_e('Leave blank to use the site title.', 'videohub360-affiliates'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Email From Address', 'videohub360-affiliates'); ?></th>
                        <td>
                            <input type="email" name="vh360_affiliates_settings[email_from_email]"
                                value="<?php echo esc_attr($s['email_from_email']); ?>"
                                placeholder="<?php echo esc_attr(vh360_affiliates_default_from_email()); ?>"
                                class="regular-text">
                            <p class="description"><?php esc_html_e('Leave blank to use noreply@your-domain.com. Must be an authorized sender for your domain.', 'videohub360-affiliates'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Email Reply-To Address', 'videohub360-affiliates'); ?></th>
                        <td>
                            <input type="email" name="vh360_affiliates_settings[email_reply_to]"
                                value="<?php echo esc_attr($s['email_reply_to']); ?>"
                                placeholder="<?php echo esc_attr(vh360_affiliates_default_reply_to_email()); ?>"
                                class="regular-text">
                            <p class="description"><?php esc_html_e('Leave blank to use the WordPress admin email.', 'videohub360-affiliates'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
