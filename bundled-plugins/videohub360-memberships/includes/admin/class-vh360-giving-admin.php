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
            wp_enqueue_style('vh360-giving-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/giving-admin.css', array(), VH360_MEMBERSHIPS_VERSION);
            wp_enqueue_script('vh360-giving-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/giving-admin.js', array('jquery'), VH360_MEMBERSHIPS_VERSION, true);
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
        wp_safe_redirect(admin_url('admin.php?page=vh360-theme-giving&tab=funds&updated=1'));
        exit;
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
                <?php foreach ($funds as $fund) : ?>
                    <tr>
                        <td><?php echo esc_html($fund->display_order); ?></td>
                        <td><?php echo esc_html($fund->label); ?></td>
                        <td><code><?php echo esc_html($fund->fund_key); ?></code></td>
                        <td><?php echo esc_html($fund->suggested_amounts); ?></td>
                        <td><?php echo esc_html($fund->enabled ? __('Yes', 'videohub360-memberships') : __('No', 'videohub360-memberships')); ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-giving&tab=funds&edit_fund=' . absint($fund->id))); ?>"><?php esc_html_e('Edit', 'videohub360-memberships'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
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
        $table = VH360_Giving_Database::get_transactions_table();
        $rows  = $wpdb->get_results("SELECT fund_label, SUM(amount) total FROM {$table} WHERE status='paid' GROUP BY fund_label ORDER BY total DESC");
        ?>
        <h2><?php esc_html_e('Reports', 'videohub360-memberships'); ?></h2>
        <p><?php echo esc_html(sprintf(__('Total this year: %1$s | Total this month: %2$s', 'videohub360-memberships'), vh360_giving_format_amount(VH360_Giving_Transactions::total("YEAR(given_at)=YEAR(CURDATE())")), vh360_giving_format_amount(VH360_Giving_Transactions::total("YEAR(given_at)=YEAR(CURDATE()) AND MONTH(given_at)=MONTH(CURDATE())")))); ?></p>
        <table class="widefat striped"><thead><tr><th><?php esc_html_e('Fund', 'videohub360-memberships'); ?></th><th><?php esc_html_e('Total', 'videohub360-memberships'); ?></th></tr></thead><tbody><?php foreach ($rows as $row) : ?><tr><td><?php echo esc_html($row->fund_label); ?></td><td><?php echo esc_html(vh360_giving_format_amount($row->total)); ?></td></tr><?php endforeach; ?></tbody></table>
        <?php
    }

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
