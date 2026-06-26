<?php
/**
 * VideoHub360 Giving admin screens.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Admin {
    private static $instance = null;
    const CAP = 'manage_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'menu'), 20);
        add_action('admin_post_vh360_giving_save_settings', array($this, 'save_settings'));
        add_action('admin_post_vh360_giving_save_fund', array($this, 'save_fund'));
        add_action('admin_post_vh360_giving_delete_fund', array($this, 'delete_fund'));
        add_action('admin_post_vh360_repair_giving_database', array($this, 'repair_database'));
        foreach (array('summary','transactions','fund_totals','recurring') as $report_type) {
            add_action('admin_post_vh360_giving_export_' . $report_type . '_report', array($this, 'export_' . $report_type . '_report'));
        }
        add_action('admin_enqueue_scripts', array($this, 'assets'));
    }

    public function menu() {
        add_submenu_page(
            'vh360-theme',
            __('Giving', 'videohub360-memberships'),
            __('Giving', 'videohub360-memberships'),
            self::CAP,
            'vh360-theme-giving',
            array($this, 'render')
        );
    }

    public function assets($hook) {
        if (false !== strpos((string) $hook, 'vh360-theme-giving')) {
            wp_enqueue_style('vh360-giving-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/giving-admin.css', array(), vh360_memberships_asset_version('assets/admin/giving-admin.css'));
            wp_enqueue_script('vh360-giving-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/giving-admin.js', array('jquery'), vh360_memberships_asset_version('assets/admin/giving-admin.js'), true);
        }
    }

    public function save_settings() {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('You do not have permission to manage Giving.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_giving_settings');

        $options = vh360_giving_default_options();
        foreach ($options as $key => $value) {
            $options[$key] = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : 0;
        }
        $options['enable_giving']    = !empty($_POST['enable_giving']) ? 1 : 0;
        $options['enable_anonymous'] = !empty($_POST['enable_anonymous']) ? 1 : 0;
        $options['enable_notes']     = !empty($_POST['enable_notes']) ? 1 : 0;

        update_option('vh360_giving_options', $options);
        wp_safe_redirect(admin_url('admin.php?page=vh360-theme-giving&tab=settings&updated=1'));
        exit;
    }

    public function save_fund() {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('You do not have permission to manage Giving funds.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_giving_fund');

        VH360_Giving_Funds::save_fund(wp_unslash($_POST));
        $this->set_notice('success', __('Giving fund saved.', 'videohub360-memberships'));
        wp_safe_redirect(admin_url('admin.php?page=vh360-theme-giving&tab=funds&updated=1'));
        exit;
    }

    public function delete_fund() {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('You do not have permission to delete Giving funds.', 'videohub360-memberships'));
        }

        $fund_id = isset($_POST['fund_id']) ? absint($_POST['fund_id']) : 0;
        check_admin_referer('vh360_giving_delete_fund_' . $fund_id);

        $result = VH360_Giving_Funds::delete_fund($fund_id);
        if (is_wp_error($result)) {
            $this->set_notice('error', $result->get_error_message());
        } elseif ('archived' === $result) {
            $this->set_notice('success', __('Giving fund archived because it has existing transactions. Historical giving records remain intact.', 'videohub360-memberships'));
        } else {
            $this->set_notice('success', __('Giving fund deleted.', 'videohub360-memberships'));
        }

        wp_safe_redirect(admin_url('admin.php?page=vh360-theme-giving&tab=funds'));
        exit;
    }


    public function repair_database() {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('You do not have permission to repair Giving tables.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_repair_giving_database');
        VH360_Giving_Database::create_tables();
        $this->set_notice(VH360_Giving_Database::tables_are_ready() ? 'success' : 'error', VH360_Giving_Database::tables_are_ready() ? __('Giving database tables repaired.', 'videohub360-memberships') : __('Giving database tables could not be fully repaired. Please check database permissions.', 'videohub360-memberships'));
        wp_safe_redirect(admin_url('admin.php?page=vh360-theme-giving&tab=settings'));
        exit;
    }

    private function set_notice($type, $message) {
        set_transient('vh360_giving_admin_notice', array(
            'type'    => sanitize_key($type),
            'message' => wp_kses_post($message),
        ), 60);
    }

    private function render_notice() {
        $notice = get_transient('vh360_giving_admin_notice');
        if (!$notice) {
            return;
        }
        delete_transient('vh360_giving_admin_notice');
        $class = 'notice-error';
        if (!empty($notice['type']) && 'success' === $notice['type']) {
            $class = 'notice-success';
        }
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
    }

    public function render() {
        $tab  = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        $tabs = array(
            'overview'     => __('Overview', 'videohub360-memberships'),
            'settings'     => __('Settings', 'videohub360-memberships'),
            'funds'        => __('Funds', 'videohub360-memberships'),
            'transactions' => __('Transactions', 'videohub360-memberships'),
            'reports'      => __('Reports', 'videohub360-memberships'),
        );
        ?>
        <div class="wrap vh360-giving-admin">
            <h1><?php esc_html_e('VideoHub360 Giving', 'videohub360-memberships'); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $key => $label) : ?>
                    <a class="nav-tab <?php echo esc_attr($tab === $key ? 'nav-tab-active' : ''); ?>" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=' . $key)); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
            <?php
            $this->render_notice();
            if ('settings' === $tab) {
                $this->settings();
            } elseif ('funds' === $tab) {
                $this->funds();
            } elseif ('transactions' === $tab) {
                $this->transactions();
            } elseif ('reports' === $tab) {
                $this->reports();
            } else {
                $this->overview();
            }
            ?>
        </div>
        <?php
    }

    private function overview() {
        $total = VH360_Giving_Transactions::total();
        ?>
        <h2><?php esc_html_e('Overview', 'videohub360-memberships'); ?></h2>
        <div class="vh360-giving-cards">
            <div class="card"><h3><?php esc_html_e('Total Giving', 'videohub360-memberships'); ?></h3><p class="big"><?php echo esc_html(vh360_giving_format_amount($total)); ?></p></div>
            <div class="card"><h3><?php esc_html_e('Status', 'videohub360-memberships'); ?></h3><p><?php echo esc_html(vh360_giving_is_enabled() ? __('Enabled', 'videohub360-memberships') : __('Disabled', 'videohub360-memberships')); ?></p></div>
        </div>
        <?php
        $this->reports();
    }

    private function settings() {
        $options = vh360_giving_options();
        $fields  = array(
            'dashboard_tab_label' => __('Frontend dashboard tab label', 'videohub360-memberships'),
            'default_currency'    => __('Default currency', 'videohub360-memberships'),
            'minimum_amount'      => __('Minimum giving amount', 'videohub360-memberships'),
            'suggested_amounts'   => __('Suggested amounts', 'videohub360-memberships'),
            'success_message'     => __('Success message', 'videohub360-memberships'),
            'cancel_message'      => __('Cancel message', 'videohub360-memberships'),
        );
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vh360_giving_save_settings">
            <?php wp_nonce_field('vh360_giving_settings'); ?>
            <table class="form-table">
                <tr><th><?php esc_html_e('Enable Giving', 'videohub360-memberships'); ?></th><td><label><input type="checkbox" name="enable_giving" value="1" <?php checked($options['enable_giving'], 1); ?>> <?php esc_html_e('Enable Giving', 'videohub360-memberships'); ?></label></td></tr>
                <?php foreach ($fields as $key => $label) : ?>
                    <tr><th><?php echo esc_html($label); ?></th><td><input class="regular-text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($options[$key]); ?>"></td></tr>
                <?php endforeach; ?>
                <tr><th><?php esc_html_e('Options', 'videohub360-memberships'); ?></th><td><label><input type="checkbox" name="enable_anonymous" value="1" <?php checked($options['enable_anonymous'], 1); ?>> <?php esc_html_e('Anonymous giving', 'videohub360-memberships'); ?></label><br><label><input type="checkbox" name="enable_notes" value="1" <?php checked($options['enable_notes'], 1); ?>> <?php esc_html_e('Donor notes', 'videohub360-memberships'); ?></label></td></tr>
            </table>
            <?php submit_button(__('Save Giving Settings', 'videohub360-memberships')); ?>
        </form>
        <?php $this->render_stripe_connection_panel(); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:1rem;">
            <input type="hidden" name="action" value="vh360_repair_giving_database">
            <?php wp_nonce_field('vh360_repair_giving_database'); ?>
            <?php submit_button(__('Repair Giving Database Tables', 'videohub360-memberships'), 'secondary', 'submit', false); ?>
        </form>
        <?php
    }


    private function render_stripe_connection_panel() {
        $stripe = class_exists('VH360_Stripe_Bootstrap') ? VH360_Stripe_Bootstrap::get_instance() : null;
        $mode = ($stripe && $stripe->is_test_mode()) ? __('Test Mode', 'videohub360-memberships') : __('Live Mode', 'videohub360-memberships');
        $keys_connected = $stripe && method_exists($stripe, 'has_payment_credentials') && $stripe->has_payment_credentials();
        $webhook_configured = $stripe && '' !== (string) $stripe->get_webhook_secret();
        $endpoint = home_url('/wp-json/vh360-memberships/v1/stripe-webhook');
        $stripe_settings_url = admin_url('admin.php?page=vh360-theme-memberships#stripe');
        $events = array('checkout.session.completed','invoice.paid','invoice.payment_failed','customer.subscription.updated','customer.subscription.deleted');
        ?>
        <div class="card vh360-giving-stripe-connection">
            <h2><?php esc_html_e('Stripe Connection', 'videohub360-memberships'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th><?php esc_html_e('Mode', 'videohub360-memberships'); ?></th><td><?php echo esc_html($mode); ?></td></tr>
                    <tr><th><?php esc_html_e('Payment keys', 'videohub360-memberships'); ?></th><td><?php echo esc_html($keys_connected ? __('Connected', 'videohub360-memberships') : __('Not Connected', 'videohub360-memberships')); ?></td></tr>
                    <tr><th><?php esc_html_e('Webhook signing secret', 'videohub360-memberships'); ?></th><td><?php echo esc_html($webhook_configured ? __('Configured', 'videohub360-memberships') : __('Missing', 'videohub360-memberships')); ?></td></tr>
                    <tr><th><?php esc_html_e('Webhook endpoint', 'videohub360-memberships'); ?></th><td><code><?php echo esc_html($endpoint); ?></code></td></tr>
                    <tr><th><?php esc_html_e('Required webhook events', 'videohub360-memberships'); ?></th><td><ul><?php foreach ($events as $event) : ?><li><code><?php echo esc_html($event); ?></code></li><?php endforeach; ?></ul></td></tr>
                </tbody>
            </table>
            <p><a class="button button-secondary" href="<?php echo esc_url($stripe_settings_url); ?>"><?php esc_html_e('Manage Stripe Settings', 'videohub360-memberships'); ?></a></p>
        </div>
        <?php
    }

    private function funds() {
        $funds   = VH360_Giving_Funds::get_funds(false);
        $edit_id = isset($_GET['edit_fund']) ? absint($_GET['edit_fund']) : 0;
        $editing = $edit_id ? VH360_Giving_Funds::get_fund($edit_id) : null;
        ?>
        <h2><?php esc_html_e('Funds', 'videohub360-memberships'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Order', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Label', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Key', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Suggested', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Enabled', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Actions', 'videohub360-memberships'); ?></th></tr></thead>
            <tbody>
                <?php if ($funds) : foreach ($funds as $fund) : ?>
                    <tr>
                        <td><?php echo esc_html($fund->display_order); ?></td>
                        <td><?php echo esc_html($fund->label); ?></td>
                        <td><code><?php echo esc_html($fund->fund_key); ?></code></td>
                        <td><?php echo esc_html($fund->suggested_amounts); ?></td>
                        <td><?php echo esc_html($fund->enabled ? __('Yes', 'videohub360-memberships') : __('No', 'videohub360-memberships')); ?></td>
                        <td class="vh360-giving-fund-actions">
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=funds&edit_fund=' . absint($fund->id))); ?>"><?php esc_html_e('Edit', 'videohub360-memberships'); ?></a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Delete this giving fund? Funds with transactions will be archived so history remains intact.', 'videohub360-memberships')); ?>');">
                                <input type="hidden" name="action" value="vh360_giving_delete_fund">
                                <input type="hidden" name="fund_id" value="<?php echo esc_attr($fund->id); ?>">
                                <?php wp_nonce_field('vh360_giving_delete_fund_' . absint($fund->id)); ?>
                                <?php submit_button(__('Delete', 'videohub360-memberships'), 'delete small', 'submit', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="6"><?php esc_html_e('No giving funds have been created yet. Add your first fund to start accepting gifts.', 'videohub360-memberships'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3><?php echo esc_html($editing ? __('Edit Fund', 'videohub360-memberships') : __('Add Fund', 'videohub360-memberships')); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="vh360_giving_save_fund">
            <input type="hidden" name="id" value="<?php echo esc_attr($editing ? $editing->id : 0); ?>">
            <?php wp_nonce_field('vh360_giving_fund'); ?>
            <table class="form-table">
                <tr><th><?php esc_html_e('Label', 'videohub360-memberships'); ?></th><td><input name="label" required value="<?php echo esc_attr($editing ? $editing->label : ''); ?>"></td></tr>
                <tr><th><?php esc_html_e('Fund key', 'videohub360-memberships'); ?></th><td><input name="fund_key" value="<?php echo esc_attr($editing ? $editing->fund_key : ''); ?>"></td></tr>
                <tr><th><?php esc_html_e('Description', 'videohub360-memberships'); ?></th><td><textarea name="description"><?php echo esc_textarea($editing ? $editing->description : ''); ?></textarea></td></tr>
                <tr><th><?php esc_html_e('Suggested amounts', 'videohub360-memberships'); ?></th><td><input name="suggested_amounts" value="<?php echo esc_attr($editing ? $editing->suggested_amounts : '10,25,50,100'); ?>"></td></tr>
                <tr><th><?php esc_html_e('Default amount', 'videohub360-memberships'); ?></th><td><input name="default_amount" type="number" step="0.01" value="<?php echo esc_attr($editing ? $editing->default_amount : ''); ?>"></td></tr>
                <tr><th><?php esc_html_e('Display order', 'videohub360-memberships'); ?></th><td><input name="display_order" type="number" value="<?php echo esc_attr($editing ? $editing->display_order : 0); ?>"></td></tr>
                <tr><th><?php esc_html_e('Enabled', 'videohub360-memberships'); ?></th><td><label><input name="enabled" type="checkbox" value="1" <?php checked(!$editing || !empty($editing->enabled)); ?>> <?php esc_html_e('Enable this fund on the frontend', 'videohub360-memberships'); ?></label></td></tr>
            </table>
            <?php submit_button($editing ? __('Update Fund', 'videohub360-memberships') : __('Add Fund', 'videohub360-memberships')); ?>
            <?php if ($editing) : ?><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=funds')); ?>"><?php esc_html_e('Cancel edit', 'videohub360-memberships'); ?></a><?php endif; ?>
        </form>
        <?php
    }

    private function transactions() {
        global $wpdb;
        $table       = VH360_Giving_Database::get_transactions_table();
        $funds       = VH360_Giving_Funds::get_funds(false);
        $status      = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $fund_id     = isset($_GET['fund_id']) ? absint($_GET['fund_id']) : 0;
        $date_from   = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to     = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
        $user_search = isset($_GET['user_search']) ? sanitize_text_field(wp_unslash($_GET['user_search'])) : '';
        $where       = array('1=1');
        $params      = array();

        if ($status) {
            $where[]  = 'status = %s';
            $params[] = $status;
        }
        if ($fund_id) {
            $where[]  = 'fund_id = %d';
            $params[] = $fund_id;
        }
        if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            $where[]  = 'created_at >= %s';
            $params[] = $date_from . ' 00:00:00';
        }
        if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            $where[]  = 'created_at <= %s';
            $params[] = $date_to . ' 23:59:59';
        }
        if ($user_search) {
            $user_ids = $this->find_user_ids($user_search);
            if (!empty($user_ids)) {
                $where[] = 'user_id IN (' . implode(',', array_map('absint', $user_ids)) . ')';
            } else {
                $where[] = '0=1';
            }
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';
        $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
        ?>
        <h2><?php esc_html_e('Transactions', 'videohub360-memberships'); ?></h2>
        <p><?php esc_html_e('MVP webhook handling marks successful Giving checkout sessions as paid. Failed session creation is marked failed before redirect; refunded status is reserved for a future refund webhook pass.', 'videohub360-memberships'); ?></p>
        <form method="get" class="vh360-giving-filters">
            <input type="hidden" name="page" value="vh360-theme-giving">
            <input type="hidden" name="tab" value="transactions">
            <select name="status"><option value=""><?php esc_html_e('All statuses', 'videohub360-memberships'); ?></option><?php foreach (array('pending', 'paid', 'failed', 'refunded') as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($status, $option); ?>><?php echo esc_html(ucfirst($option)); ?></option><?php endforeach; ?></select>
            <select name="fund_id"><option value="0"><?php esc_html_e('All funds', 'videohub360-memberships'); ?></option><?php foreach ($funds as $fund) : ?><option value="<?php echo esc_attr($fund->id); ?>" <?php selected($fund_id, $fund->id); ?>><?php echo esc_html($fund->label); ?></option><?php endforeach; ?></select>
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            <input type="search" name="user_search" value="<?php echo esc_attr($user_search); ?>" placeholder="<?php esc_attr_e('Member name, email, ID', 'videohub360-memberships'); ?>">
            <?php submit_button(__('Filter', 'videohub360-memberships'), 'secondary', '', false); ?>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=transactions')); ?>"><?php esc_html_e('Reset', 'videohub360-memberships'); ?></a>
        </form>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Donor', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Amount', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Fund', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Status', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Gateway', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Source', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Date', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Stripe IDs', 'videohub360-memberships'); ?></th></tr></thead>
            <tbody>
                <?php if ($rows) : foreach ($rows as $row) : $user = get_userdata($row->user_id); ?>
                    <tr><td><?php echo esc_html($user ? $user->display_name : '#' . $row->user_id); ?></td><td><?php echo esc_html(vh360_giving_format_amount($row->amount, $row->currency)); ?></td><td><?php echo esc_html($row->fund_label); ?></td><td><?php echo esc_html($row->status); ?></td><td><?php echo esc_html($row->gateway); ?></td><td><?php echo esc_html($row->source); ?></td><td><?php echo esc_html($row->created_at); ?></td><td><code><?php echo esc_html(trim($row->stripe_checkout_session_id . ' ' . $row->stripe_payment_intent_id)); ?></code></td></tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="8"><?php esc_html_e('No giving transactions match these filters.', 'videohub360-memberships'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private function reports() {
        global $wpdb;
        $filters = $this->get_report_filters();
        $funds = VH360_Giving_Funds::get_funds(false);
        $rows = $this->get_report_fund_totals($filters);
        $summary = $this->get_report_summary($filters);
        ?>
        <h2><?php esc_html_e('Reports', 'videohub360-memberships'); ?></h2>
        <form method="get" class="vh360-giving-filters">
            <input type="hidden" name="page" value="vh360-theme-giving"><input type="hidden" name="tab" value="reports">
            <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from']); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to']); ?>">
            <select name="fund_id"><option value="0"><?php esc_html_e('All funds', 'videohub360-memberships'); ?></option><?php foreach ($funds as $fund) : ?><option value="<?php echo esc_attr($fund->id); ?>" <?php selected($filters['fund_id'], $fund->id); ?>><?php echo esc_html($fund->label); ?></option><?php endforeach; ?></select>
            <select name="transaction_status"><option value=""><?php esc_html_e('All transaction statuses', 'videohub360-memberships'); ?></option><?php foreach (array('pending','paid','failed','refunded') as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['transaction_status'], $status); ?>><?php echo esc_html(ucfirst($status)); ?></option><?php endforeach; ?></select>
            <select name="recurring_status"><option value=""><?php esc_html_e('All recurring statuses', 'videohub360-memberships'); ?></option><?php foreach (array('incomplete','active','past_due','canceled') as $status) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($filters['recurring_status'], $status); ?>><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></option><?php endforeach; ?></select>
            <select name="gateway"><option value=""><?php esc_html_e('All gateways', 'videohub360-memberships'); ?></option><option value="stripe" <?php selected($filters['gateway'], 'stripe'); ?>>Stripe</option></select>
            <select name="source"><option value=""><?php esc_html_e('All sources', 'videohub360-memberships'); ?></option><option value="dashboard" <?php selected($filters['source'], 'dashboard'); ?>>Dashboard</option></select>
            <?php submit_button(__('Filter Reports', 'videohub360-memberships'), 'secondary', '', false); ?>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=reports')); ?>"><?php esc_html_e('Reset', 'videohub360-memberships'); ?></a>
        </form>
        <p><strong><?php esc_html_e('Total Giving:', 'videohub360-memberships'); ?></strong> <?php echo esc_html(vh360_giving_format_amount($summary['total_giving'])); ?> | <strong><?php esc_html_e('Gifts:', 'videohub360-memberships'); ?></strong> <?php echo esc_html($summary['number_of_gifts']); ?> | <strong><?php esc_html_e('Donors:', 'videohub360-memberships'); ?></strong> <?php echo esc_html($summary['number_of_donors']); ?></p>
        <p class="vh360-giving-report-downloads">
            <a class="button" href="<?php echo esc_url($this->report_export_url('summary', $filters)); ?>"><?php esc_html_e('Download Summary Report', 'videohub360-memberships'); ?></a>
            <a class="button" href="<?php echo esc_url($this->report_export_url('transactions', $filters)); ?>"><?php esc_html_e('Download Transactions Report', 'videohub360-memberships'); ?></a>
            <a class="button" href="<?php echo esc_url($this->report_export_url('fund_totals', $filters)); ?>"><?php esc_html_e('Download Fund Totals Report', 'videohub360-memberships'); ?></a>
            <a class="button" href="<?php echo esc_url($this->report_export_url('recurring', $filters)); ?>"><?php esc_html_e('Download Recurring Giving Report', 'videohub360-memberships'); ?></a>
        </p>
        <table class="widefat striped"><thead><tr><th><?php esc_html_e('Fund', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Total', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Gifts', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Donors', 'videohub360-memberships'); ?></th></tr></thead><tbody><?php if ($rows) : foreach ($rows as $row) : ?><tr><td><?php echo esc_html($row->fund_label); ?></td><td><?php echo esc_html(vh360_giving_format_amount($row->total_amount)); ?></td><td><?php echo esc_html($row->gift_count); ?></td><td><?php echo esc_html($row->donor_count); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4"><?php esc_html_e('No giving report data matches these filters.', 'videohub360-memberships'); ?></td></tr><?php endif; ?></tbody></table>
        <?php
    }

    private function get_report_filters() {
        return array(
            'date_from' => isset($_GET['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', sanitize_text_field(wp_unslash($_GET['date_from']))) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '',
            'date_to'   => isset($_GET['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', sanitize_text_field(wp_unslash($_GET['date_to']))) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '',
            'fund_id'   => isset($_GET['fund_id']) ? absint($_GET['fund_id']) : 0,
            'transaction_status' => isset($_GET['transaction_status']) ? sanitize_key(wp_unslash($_GET['transaction_status'])) : '',
            'recurring_status'    => isset($_GET['recurring_status']) ? sanitize_key(wp_unslash($_GET['recurring_status'])) : '',
            'gateway'             => isset($_GET['gateway']) ? sanitize_key(wp_unslash($_GET['gateway'])) : '',
            'source'    => isset($_GET['source']) ? sanitize_key(wp_unslash($_GET['source'])) : '',
        );
    }

    private function report_where($filters, $alias = '') {
        $p = $alias ? $alias . '.' : '';
        $where = array('1=1'); $params = array();
        if (!empty($filters['date_from'])) { $where[] = "COALESCE({$p}given_at, {$p}created_at) >= %s"; $params[] = $filters['date_from'] . ' 00:00:00'; }
        if (!empty($filters['date_to'])) { $where[] = "COALESCE({$p}given_at, {$p}created_at) <= %s"; $params[] = $filters['date_to'] . ' 23:59:59'; }
        if (!empty($filters['fund_id'])) { $where[] = "{$p}fund_id = %d"; $params[] = $filters['fund_id']; }
        if (!empty($filters['transaction_status'])) { $where[] = "{$p}status = %s"; $params[] = $filters['transaction_status']; }
        foreach (array('gateway','source') as $key) { if (!empty($filters[$key])) { $where[] = "{$p}{$key} = %s"; $params[] = $filters[$key]; } }
        return array(implode(' AND ', $where), $params);
    }

    private function report_export_url($type, $filters) {
        return wp_nonce_url(add_query_arg(array_merge(array('action' => 'vh360_giving_export_' . $type . '_report'), $filters), admin_url('admin-post.php')), 'vh360_giving_export_' . $type . '_report');
    }

    private function require_report_export($type) {
        if (!current_user_can(self::CAP)) wp_die(esc_html__('You do not have permission to export Giving reports.', 'videohub360-memberships'));
        check_admin_referer('vh360_giving_export_' . $type . '_report');
        return $this->get_report_filters();
    }

    private function stream_csv($filename, $headers, $rows) {
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=' . $filename); $out = fopen('php://output', 'w'); fputcsv($out, $headers); foreach ($rows as $row) fputcsv($out, $row); fclose($out); exit;
    }

    private function get_report_transactions($filters) {
        global $wpdb; $table = VH360_Giving_Database::get_transactions_table(); list($where, $params) = $this->report_where($filters); $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY COALESCE(given_at, created_at) DESC"; return $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
    }

    private function get_report_summary($filters) {
        $rows = $this->get_report_transactions($filters); $donors = array(); $total = $one = $rec = $failed = $refunded = $month = $year = 0; $paid_gift_count = 0; $now_month = gmdate('Y-m'); $now_year = gmdate('Y'); foreach ($rows as $r) { $amount = (float) $r->amount; $date = substr((string)($r->given_at ?: $r->created_at), 0, 10); if ('paid' === $r->status) { $paid_gift_count++; $total += $amount; if (0 === strpos($date, $now_month)) $month += $amount; if (0 === strpos($date, $now_year)) $year += $amount; if ('subscription' === $r->gateway_mode) $rec += $amount; else $one += $amount; $donors[(int) $r->user_id] = true; } elseif ('failed' === $r->status) $failed += $amount; elseif ('refunded' === $r->status) $refunded += $amount; } return array('date_range'=>($filters['date_from'] ?: 'All') . ' - ' . ($filters['date_to'] ?: 'All'),'total_giving'=>$total,'total_this_month'=>$month,'total_this_year'=>$year,'one_time'=>$one,'recurring'=>$rec,'failed'=>$failed,'refunded'=>$refunded,'number_of_gifts'=>$paid_gift_count,'number_of_donors'=>count($donors),'average_gift'=>$paid_gift_count?$total/$paid_gift_count:0); }

    private function get_report_fund_totals($filters) {
        global $wpdb; $table = VH360_Giving_Database::get_transactions_table(); list($where, $params) = $this->report_where($filters); $sql = "SELECT fund_label, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) total_amount, SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) gift_count, COUNT(DISTINCT CASE WHEN status='paid' THEN user_id ELSE NULL END) donor_count, SUM(CASE WHEN gateway_mode='payment' AND status='paid' THEN amount ELSE 0 END) one_time_total, SUM(CASE WHEN gateway_mode='subscription' AND status='paid' THEN amount ELSE 0 END) recurring_total, AVG(CASE WHEN status='paid' THEN amount ELSE NULL END) average_gift FROM {$table} WHERE {$where} GROUP BY fund_label ORDER BY total_amount DESC"; return $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
    }

    public function export_summary_report(){ $filters=$this->require_report_export('summary'); $s=$this->get_report_summary($filters); $this->stream_csv('giving-summary-report-' . gmdate('Y-m-d') . '.csv', array('Date Range','Total Giving','Total Giving This Month','Total Giving This Year','Total One-Time Giving','Total Recurring Giving','Total Failed','Total Refunded','Number of Gifts','Number of Donors','Average Gift'), array(array($s['date_range'],$s['total_giving'],$s['total_this_month'],$s['total_this_year'],$s['one_time'],$s['recurring'],$s['failed'],$s['refunded'],$s['number_of_gifts'],$s['number_of_donors'],$s['average_gift']))); }
    public function export_transactions_report(){ $filters=$this->require_report_export('transactions'); $out=array(); foreach($this->get_report_transactions($filters) as $r){ $u=get_userdata($r->user_id); $out[]=array($r->given_at?:$r->created_at,$u?$u->display_name:'',$u?$u->user_email:'',$r->fund_label,$r->amount,strtoupper($r->currency),$r->status,('subscription'===$r->gateway_mode?'Recurring':'One-time'),$r->gateway,$r->source,$r->stripe_payment_intent_id,$r->stripe_invoice_id,$r->stripe_subscription_id?:$r->gateway_subscription_id,$r->anonymous?'Yes':'No',$r->note); } $this->stream_csv('giving-transactions-report-' . gmdate('Y-m-d') . '.csv', array('Date','Donor Name','Donor Email','Fund','Amount','Currency','Status','Frequency','Gateway','Source','Stripe Payment Intent ID','Stripe Invoice ID','Stripe Subscription ID','Anonymous','Note'), $out); }
    public function export_fund_totals_report(){ $filters=$this->require_report_export('fund_totals'); $out=array(); foreach($this->get_report_fund_totals($filters) as $r){ $out[]=array($r->fund_label,$r->total_amount,$r->gift_count,$r->donor_count,$r->one_time_total,$r->recurring_total,$r->average_gift); } $this->stream_csv('giving-fund-totals-report-' . gmdate('Y-m-d') . '.csv', array('Fund','Total Amount','Number of Gifts','Number of Donors','One-Time Total','Recurring Total','Average Gift'), $out); }
    public function export_recurring_report(){ $filters=$this->require_report_export('recurring'); if(!VH360_Giving_Database::recurring_table_exists()){ $this->stream_csv('giving-recurring-report-' . gmdate('Y-m-d') . '.csv', array('Message'), array(array('Recurring Giving table is not available. Repair Giving database tables.'))); } global $wpdb; $table=VH360_Giving_Database::get_recurring_table(); $where=array('1=1'); $params=array(); if($filters['fund_id']){$where[]='fund_id=%d';$params[]=$filters['fund_id'];} if($filters['recurring_status']){$where[]='status=%s';$params[]=$filters['recurring_status'];} if($filters['gateway']){$where[]='gateway=%s';$params[]=$filters['gateway'];} if($filters['source']){$where[]='source=%s';$params[]=$filters['source'];} if($filters['date_from']){$where[]='COALESCE(started_at, created_at) >= %s';$params[]=$filters['date_from'].' 00:00:00';} if($filters['date_to']){$where[]='COALESCE(started_at, created_at) <= %s';$params[]=$filters['date_to'].' 23:59:59';} $sql="SELECT * FROM {$table} WHERE ".implode(' AND ',$where).' ORDER BY COALESCE(started_at, created_at) DESC'; $rows=$params?$wpdb->get_results($wpdb->prepare($sql,$params)):$wpdb->get_results($sql); $out=array(); foreach($rows as $r){$u=get_userdata($r->user_id);$out[]=array($u?$u->display_name:'',$u?$u->user_email:'',$r->fund_label,$r->amount,strtoupper($r->currency),ucfirst($r->giving_interval),$r->status,$r->started_at,$r->current_period_start,$r->current_period_end,$r->cancel_at_period_end?'Yes':'No',$r->canceled_at,$r->stripe_subscription_id);} $this->stream_csv('giving-recurring-report-' . gmdate('Y-m-d') . '.csv', array('Donor Name','Donor Email','Fund','Amount','Currency','Frequency','Status','Started Date','Current Period Start','Current Period End','Cancel At Period End','Canceled Date','Stripe Subscription ID'), $out); }

    private function find_user_ids($search) {
        if (is_numeric($search)) {
            return array(absint($search));
        }
        $query = new WP_User_Query(array(
            'search'         => '*' . esc_attr($search) . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'fields'         => 'ID',
            'number'         => 50,
        ));
        return array_map('absint', $query->get_results());
    }
}
