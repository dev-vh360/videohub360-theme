<?php
/**
 * Paid members admin manager.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class VH360_Membership_Members_Admin {
    private static $instance = null;
    const CAPABILITY = 'manage_options';
    const NONCE_ACTION = 'vh360_membership_members_action';

    public static function get_instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_vh360_membership_sync', array($this, 'handle_sync'));
        add_action('admin_post_vh360_membership_extend', array($this, 'handle_extend'));
        add_action('admin_post_vh360_membership_cancel', array($this, 'handle_cancel'));
        add_action('admin_post_vh360_membership_expire', array($this, 'handle_expire'));
        add_action('admin_post_vh360_membership_reactivate', array($this, 'handle_reactivate'));
        add_action('admin_post_vh360_export_paid_members', array($this, 'handle_export'));
    }

    public static function get_admin_url($args = array()) {
        return add_query_arg(array_merge(array('page' => 'vh360-theme-memberships', 'tab' => 'paid-members'), $args), admin_url('admin.php'));
    }

    public function enqueue_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ('vh360-theme-memberships' !== $page) return;
        wp_enqueue_style('vh360-membership-members-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/membership-members.css', array(), VH360_MEMBERSHIPS_VERSION);
        wp_enqueue_script('vh360-membership-members-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/membership-members.js', array(), VH360_MEMBERSHIPS_VERSION, true);
    }

    public function render_manager($wrap = true) {
        if (!current_user_can(self::CAPABILITY)) {
            echo '<div class="notice notice-error inline"><p>' . esc_html__('You do not have permission to view paid members.', 'videohub360-memberships') . '</p></div>';
            return;
        }
        $table = new VH360_Membership_Members_List_Table($this);
        $table->prepare_items();
        if ($wrap) echo '<div class="wrap">';
        echo '<h2>' . esc_html__('Paid Members', 'videohub360-memberships') . '</h2>';
        $this->render_notice();
        $membership_id = isset($_GET['membership_id']) ? absint($_GET['membership_id']) : 0;
        if ($membership_id) $this->render_details($membership_id);
        echo '<form method="get" class="vh360-paid-members-form">';
        echo '<input type="hidden" name="page" value="vh360-theme-memberships" /><input type="hidden" name="tab" value="paid-members" />';
        $this->render_filters();
        $table->search_box(__('Search members', 'videohub360-memberships'), 'vh360-paid-members');
        $table->display();
        echo '</form>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="vh360-export-paid-members">';
        wp_nonce_field(self::NONCE_ACTION);
        echo '<input type="hidden" name="action" value="vh360_export_paid_members" />';
        foreach ($this->get_filters() as $key => $value) echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        submit_button(__('Export current filtered list to CSV', 'videohub360-memberships'), 'secondary', 'submit', false);
        echo '</form>';
        if ($wrap) echo '</div>';
    }

    private function render_notice() {
        $code = isset($_GET['vh360_members_notice']) ? sanitize_key(wp_unslash($_GET['vh360_members_notice'])) : '';
        if (!$code) return;
        $messages = array(
            'synced' => __('Membership synced from Stripe.', 'videohub360-memberships'), 'sync_failed' => __('Stripe sync failed or was unavailable.', 'videohub360-memberships'),
            'extended' => __('Membership extended.', 'videohub360-memberships'), 'cancelled' => __('Local membership access cancelled.', 'videohub360-memberships'),
            'expired' => __('Local membership access expired.', 'videohub360-memberships'), 'reactivated' => __('Local membership access reactivated.', 'videohub360-memberships'),
            'extend_failed' => __('Membership extension failed.', 'videohub360-memberships'), 'cancel_failed' => __('Local membership cancellation failed.', 'videohub360-memberships'),
            'expire_failed' => __('Local membership expiration failed.', 'videohub360-memberships'), 'reactivate_failed' => __('Local membership reactivation failed.', 'videohub360-memberships'),
            'export_failed' => __('CSV export failed.', 'videohub360-memberships'), 'skipped_recurring' => __('Recurring Stripe memberships were skipped for local-only mutation. Use Sync from Stripe.', 'videohub360-memberships'),
        );
        if (isset($messages[$code])) echo '<div class="notice notice-' . esc_attr(false !== strpos($code, 'failed') ? 'error' : 'success') . ' inline"><p>' . esc_html($messages[$code]) . '</p></div>';
    }

    private function render_filters() {
        $f = $this->get_filters(); $plans = $this->get_plans();
        echo '<div class="vh360-members-filters">';
        $this->select('status', array(''=>'All statuses','active'=>'Active','expired'=>'Expired','cancelled'=>'Cancelled','superseded'=>'Superseded','past_due'=>'Past due'), $f['status']);
        $plan_options = array('' => __('All plans', 'videohub360-memberships')); foreach ($plans as $key=>$plan) $plan_options[$key] = $this->plan_label($key);
        $this->select('plan_key', $plan_options, $f['plan_key']);
        $this->select('billing_mode', array(''=>'All billing modes','recurring'=>'Recurring','one_time'=>'One time','lifetime'=>'Lifetime/free'), $f['billing_mode']);
        $this->select('billing_provider', array(''=>'All providers','stripe'=>'Stripe','woocommerce'=>'WooCommerce','manual'=>'Manual/empty'), $f['billing_provider']);
        $this->select('cancel_pending', array(''=>'Cancel pending: all','yes'=>'Yes','no'=>'No'), $f['cancel_pending']);
        submit_button(__('Filter', 'videohub360-memberships'), 'secondary', 'filter_action', false);
        echo ' <a class="button" href="' . esc_url(self::get_admin_url()) . '">' . esc_html__('Reset', 'videohub360-memberships') . '</a></div>';
    }

    private function select($name, $options, $current) { echo '<select name="' . esc_attr($name) . '">'; foreach ($options as $value=>$label) echo '<option value="' . esc_attr($value) . '" ' . selected($current, $value, false) . '>' . esc_html($label) . '</option>'; echo '</select> '; }

    public function get_filters($source = null) {
        $source = null === $source ? $_GET : $source;
        return array(
            'status' => isset($source['status']) ? sanitize_key(wp_unslash($source['status'])) : '',
            'plan_key' => isset($source['plan_key']) ? sanitize_key(wp_unslash($source['plan_key'])) : '',
            'billing_mode' => isset($source['billing_mode']) ? sanitize_key(wp_unslash($source['billing_mode'])) : '',
            'billing_provider' => isset($source['billing_provider']) ? sanitize_key(wp_unslash($source['billing_provider'])) : '',
            'cancel_pending' => isset($source['cancel_pending']) ? sanitize_key(wp_unslash($source['cancel_pending'])) : '',
            's' => isset($source['s']) ? sanitize_text_field(wp_unslash($source['s'])) : '',
        );
    }

    public function get_plans() { return class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array(); }
    public function plan_label($key) { $p = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan($key) : false; return $p ? (!empty($p['name']) ? $p['name'] : (!empty($p['label']) ? $p['label'] : $key)) : $key; }
    public function is_recurring($m) { return 'recurring' === (string) $m->billing_mode || !empty($m->stripe_subscription_id); }
    public function is_stripe_recurring($m) { return $this->is_recurring($m) && 'stripe' === (string) $m->billing_provider && !empty($m->stripe_subscription_id); }
    public function order_link($order_id) { return ($order_id && function_exists('wc_get_order')) ? admin_url('post.php?post=' . absint($order_id) . '&action=edit') : ''; }

    public function build_query($count = false, $limit = 25, $offset = 0, $orderby = 'updated_at', $order = 'DESC', $source = null, $ids = array()) {
        global $wpdb; $table = VH360_Membership_Database::get_memberships_table(); $users = $wpdb->users;
        $where = array('1=1'); $f = $this->get_filters($source);
        foreach (array('plan_key','billing_mode') as $key) if ($f[$key] !== '') $where[] = $wpdb->prepare("m.$key = %s", $f[$key]);
        if ('past_due' === $f['status']) $where[] = $wpdb->prepare('m.subscription_status = %s', 'past_due'); elseif ($f['status'] !== '') $where[] = $wpdb->prepare('m.status = %s', $f['status']);
        if ($f['billing_provider'] === 'manual') $where[] = "(m.billing_provider = '' OR m.billing_provider IS NULL OR m.billing_provider = 'manual')"; elseif ($f['billing_provider'] !== '') $where[] = $wpdb->prepare('m.billing_provider = %s', $f['billing_provider']);
        if ('yes' === $f['cancel_pending']) $where[] = 'm.cancel_at_period_end = 1'; elseif ('no' === $f['cancel_pending']) $where[] = '(m.cancel_at_period_end = 0 OR m.cancel_at_period_end IS NULL)';
        if ($f['s'] !== '') { $like = '%' . $wpdb->esc_like($f['s']) . '%'; $num = absint($f['s']); $where[] = $wpdb->prepare('(u.user_email LIKE %s OR u.display_name LIKE %s OR u.user_login LIKE %s OR m.stripe_subscription_id LIKE %s OR m.stripe_customer_id LIKE %s OR m.stripe_price_id LIKE %s OR m.id = %d OR m.source_order_id = %d)', $like,$like,$like,$like,$like,$like,$num,$num); }
        if ($ids) { $ids = array_map('absint', $ids); $where[] = 'm.id IN (' . implode(',', $ids) . ')'; }
        $sql = $count ? 'SELECT COUNT(*)' : 'SELECT m.*, u.user_login, u.user_email, u.display_name';
        $sql .= " FROM {$table} m LEFT JOIN {$users} u ON u.ID = m.user_id WHERE " . implode(' AND ', $where);
        if (!$count) { $allowed = array('created_at','updated_at','starts_at','expires_at','current_period_end','status','plan_key','billing_mode'); $orderby = in_array($orderby, $allowed, true) ? $orderby : 'updated_at'; $order = 'ASC' === strtoupper($order) ? 'ASC' : 'DESC'; $sql .= " ORDER BY m.$orderby $order, m.created_at DESC" . $wpdb->prepare(' LIMIT %d OFFSET %d', $limit, $offset); }
        return $sql;
    }

    public function get_memberships($limit = 25, $offset = 0, $orderby = 'updated_at', $order = 'DESC', $source = null, $ids = array()) { global $wpdb; return $wpdb->get_results($this->build_query(false, $limit, $offset, $orderby, $order, $source, $ids)); }
    public function count_memberships($source = null, $ids = array()) { global $wpdb; return (int) $wpdb->get_var($this->build_query(true, 0, 0, 'updated_at', 'DESC', $source, $ids)); }
    public function get_membership($id) { global $wpdb; $t = VH360_Membership_Database::get_memberships_table(); $u = $wpdb->users; return $wpdb->get_row($wpdb->prepare("SELECT m.*, u.user_login, u.user_email, u.display_name FROM {$t} m LEFT JOIN {$u} u ON u.ID = m.user_id WHERE m.id=%d", $id)); }

    public function action_url($action, $id) { return wp_nonce_url(add_query_arg(array('action'=>$action, 'membership_id'=>absint($id)), admin_url('admin-post.php')), self::NONCE_ACTION); }
    private function redirect($notice) { wp_safe_redirect(self::get_admin_url(array('vh360_members_notice'=>$notice))); exit; }
    private function require_action() { if (!current_user_can(self::CAPABILITY)) wp_die(esc_html__('Permission denied.', 'videohub360-memberships')); check_admin_referer(self::NONCE_ACTION); return isset($_REQUEST['membership_id']) ? absint($_REQUEST['membership_id']) : 0; }
    public function handle_sync() { $id = $this->require_action(); $result = class_exists('VH360_Stripe_Sync') ? VH360_Stripe_Sync::get_instance()->sync_membership($id) : false; $ok = true === $result && !is_wp_error($result); $this->redirect($ok ? 'synced' : 'sync_failed'); }
    public function handle_extend() { $id = $this->require_action(); $d = isset($_POST['duration']) ? max(1, absint($_POST['duration'])) : 1; $u = isset($_POST['duration_unit']) ? sanitize_key(wp_unslash($_POST['duration_unit'])) : 'months'; $ok = class_exists('VH360_Membership_API') && VH360_Membership_API::get_instance()->extend_membership($id, $d, in_array($u, array('days','months','years','lifetime'), true) ? $u : 'months'); $this->redirect($ok ? 'extended' : 'extend_failed'); }
    public function handle_cancel() { $id = $this->require_action(); $m = $this->get_membership($id); if ($m && $this->is_stripe_recurring($m)) $this->redirect('skipped_recurring'); $ok = class_exists('VH360_Membership_API') && VH360_Membership_API::get_instance()->cancel_membership($id); $this->redirect($ok ? 'cancelled' : 'cancel_failed'); }
    public function handle_expire() { $id = $this->require_action(); $m = $this->get_membership($id); if ($m && $this->is_stripe_recurring($m)) $this->redirect('skipped_recurring'); $ok = class_exists('VH360_Membership_API') && VH360_Membership_API::get_instance()->expire_membership($id); $this->redirect($ok ? 'expired' : 'expire_failed'); }
    public function handle_reactivate() { $id = $this->require_action(); $m = $this->get_membership($id); if ($m && $this->is_stripe_recurring($m)) $this->redirect('skipped_recurring'); $ok = class_exists('VH360_Membership_API') && method_exists('VH360_Membership_API','get_instance') && method_exists(VH360_Membership_API::get_instance(), 'reactivate_membership') && VH360_Membership_API::get_instance()->reactivate_membership($id); $this->redirect($ok ? 'reactivated' : 'reactivate_failed'); }

    public function handle_export() {
        if (!current_user_can(self::CAPABILITY)) wp_die(esc_html__('Permission denied.', 'videohub360-memberships')); check_admin_referer(self::NONCE_ACTION);
        $ids = isset($_REQUEST['membership_ids']) ? array_map('absint', (array) wp_unslash($_REQUEST['membership_ids'])) : array();
        $this->stream_csv_export($_REQUEST, $ids);
    }

    public function stream_csv_export($source, $ids = array()) {
        nocache_headers(); header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename=vh360-paid-members-' . gmdate('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w'); $cols = array('membership_id','user_id','display_name','user_email','plan_key','plan_label','status','billing_mode','billing_provider','subscription_status','cancel_at_period_end','starts_at','expires_at','current_period_start','current_period_end','source_order_id','stripe_customer_id','stripe_subscription_id','stripe_price_id','created_at','updated_at','last_billing_sync_at'); fputcsv($out, $cols);
        $offset = 0; $limit = 500;
        do {
            $rows = $this->get_memberships($limit, $offset, 'updated_at', 'DESC', $source, $ids);
            foreach ($rows as $r) fputcsv($out, array($r->id,$r->user_id,$r->display_name,$r->user_email,$r->plan_key,$this->plan_label($r->plan_key),$r->status,$r->billing_mode,$r->billing_provider,$r->subscription_status,$r->cancel_at_period_end,$r->starts_at,$r->expires_at,$r->current_period_start,$r->current_period_end,$r->source_order_id,$r->stripe_customer_id,$r->stripe_subscription_id,$r->stripe_price_id,$r->created_at,$r->updated_at,$r->last_billing_sync_at));
            $offset += $limit;
        } while (count($rows) === $limit && empty($ids));
        fclose($out); exit;
    }

    private function render_details($id) {
        global $wpdb;
        $m = $this->get_membership($id);
        if (!$m) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('Membership not found.', 'videohub360-memberships') . '</p></div>';
            return;
        }

        $user_label = trim(($m->display_name ?: $m->user_login) . ' <' . $m->user_email . '> (#' . $m->user_id . ')');
        $user_value = $m->user_id ? '<a href="' . esc_url(get_edit_user_link($m->user_id)) . '">' . esc_html($user_label) . '</a>' : esc_html($user_label ?: __('Unknown user', 'videohub360-memberships'));
        $order_link = $this->order_link($m->source_order_id);
        $order_value = $m->source_order_id ? ($order_link ? '<a href="' . esc_url($order_link) . '">#' . esc_html($m->source_order_id) . '</a>' : esc_html('#' . $m->source_order_id)) : '—';
        $stripe_source = array_filter(array($m->stripe_customer_id ? 'Customer: ' . $m->stripe_customer_id : '', $m->stripe_subscription_id ? 'Subscription: ' . $m->stripe_subscription_id : '', $m->stripe_price_id ? 'Price: ' . $m->stripe_price_id : ''));

        echo '<div class="vh360-member-details"><h3>' . esc_html(sprintf(__('Membership #%d Details', 'videohub360-memberships'), $id)) . '</h3><div class="vh360-details-grid">';
        $this->detail_card('Membership ID', esc_html($m->id));
        $this->detail_card('User', $user_value, true);
        $this->detail_card('Plan', esc_html($this->plan_label($m->plan_key) . ' (' . $m->plan_key . ')'));
        $this->detail_card('Status', esc_html($m->status ?: '—'));
        $this->detail_card('Billing', esc_html(($m->billing_mode ?: '—') . ' / ' . ($m->billing_provider ?: 'local')));
        $this->detail_card('Source order', $order_value, true);
        $this->detail_card('Stripe source', esc_html($stripe_source ? implode(' | ', $stripe_source) : '—'));
        $this->detail_card('Subscription status', esc_html($m->subscription_status ?: '—'));
        $this->detail_card('Current period start', esc_html($m->current_period_start ?: '—'));
        $this->detail_card('Current period end', esc_html($m->current_period_end ?: '—'));
        $this->detail_card('Cancel at period end', esc_html(!empty($m->cancel_at_period_end) ? 'Yes' : 'No'));
        $this->detail_card('Cancelled at', esc_html($m->cancelled_at ?: '—'));
        $this->detail_card('Starts at', esc_html($m->starts_at ?: '—'));
        $this->detail_card('Expires at', esc_html($m->expires_at ?: 'Lifetime / No expiration'));
        $this->detail_card('Created at', esc_html($m->created_at ?: '—'));
        $this->detail_card('Updated at', esc_html($m->updated_at ?: '—'));
        $this->detail_card('Last billing sync', esc_html($m->last_billing_sync_at ?: '—'));
        echo '</div>';

        if (!$this->is_stripe_recurring($m)) {
            echo '<div id="extend-local-access" class="vh360-detail-action"><h4>' . esc_html__('Extend Local Access', 'videohub360-memberships') . '</h4><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="vh360_membership_extend"><input type="hidden" name="membership_id" value="' . esc_attr($m->id) . '">';
            wp_nonce_field(self::NONCE_ACTION);
            echo '<input type="number" name="duration" value="1" min="1"> <select name="duration_unit"><option value="months">' . esc_html__('months', 'videohub360-memberships') . '</option><option value="days">' . esc_html__('days', 'videohub360-memberships') . '</option><option value="years">' . esc_html__('years', 'videohub360-memberships') . '</option><option value="lifetime">' . esc_html__('lifetime', 'videohub360-memberships') . '</option></select> <button class="button button-secondary">' . esc_html__('Extend Membership', 'videohub360-memberships') . '</button></form></div>';
        }

        $events_table = VH360_Membership_Database::get_events_table();
        $events = $wpdb->get_results($wpdb->prepare("SELECT event_type, actor_id, event_data, created_at FROM {$events_table} WHERE membership_id=%d ORDER BY created_at DESC LIMIT 20", $id));
        echo '<h4>' . esc_html__('Recent Events', 'videohub360-memberships') . '</h4><table class="widefat vh360-events-table"><thead><tr><th>Type</th><th>Actor</th><th>Date</th><th>Summary</th></tr></thead><tbody>';
        if ($events) foreach ($events as $e) echo '<tr><td>' . esc_html($e->event_type) . '</td><td>' . esc_html($e->actor_id) . '</td><td>' . esc_html($e->created_at) . '</td><td><code>' . esc_html(wp_trim_words((string) $e->event_data, 20)) . '</code></td></tr>'; else echo '<tr><td colspan="4">' . esc_html__('No events found.', 'videohub360-memberships') . '</td></tr>';
        echo '</tbody></table><p><a class="button" href="' . esc_url(self::get_admin_url()) . '">' . esc_html__('Back to Paid Members', 'videohub360-memberships') . '</a></p></div>';
    }

    private function detail_card($label, $value, $is_html = false) {
        echo '<div class="vh360-detail-card"><strong>' . esc_html($label) . '</strong><span>' . ($is_html ? wp_kses_post($value) : $value) . '</span></div>';
    }
}

class VH360_Membership_Members_List_Table extends WP_List_Table {
    private $admin;
    public function __construct($admin) { parent::__construct(array('singular'=>'paid_member','plural'=>'paid_members','ajax'=>false)); $this->admin = $admin; }
    public function get_columns() { return array('cb'=>'<input type="checkbox" />','member'=>'Member','plan'=>'Plan','status'=>'Status','billing'=>'Billing','started'=>'Started','renews'=>'Renews / Expires','source'=>'Source','last_sync'=>'Last Sync','actions'=>'Actions'); }
    protected function get_sortable_columns() { return array('plan'=>array('plan_key',false),'status'=>array('status',false),'billing'=>array('billing_mode',false),'started'=>array('starts_at',false),'renews'=>array('current_period_end',false),'last_sync'=>array('updated_at',true)); }
    protected function get_bulk_actions() { return array('sync'=>'Sync selected recurring memberships','expire'=>'Expire selected local memberships','cancel'=>'Cancel selected local memberships','export'=>'Export selected to CSV'); }
    public function prepare_items() { $this->process_bulk_action(); $per = 25; $page = $this->get_pagenum(); $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'updated_at'; $order = isset($_GET['order']) ? sanitize_key(wp_unslash($_GET['order'])) : 'DESC'; $total = $this->admin->count_memberships(); $this->items = $this->admin->get_memberships($per, ($page-1)*$per, $orderby, $order); $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns()); $this->set_pagination_args(array('total_items'=>$total,'per_page'=>$per)); }
    public function process_bulk_action() {
        $action = $this->current_action();
        if (!$action || !in_array($action, array('sync','expire','cancel','export'), true)) return;
        check_admin_referer('bulk-' . $this->_args['plural']);
        if (!current_user_can(VH360_Membership_Members_Admin::CAPABILITY)) wp_die(esc_html__('Permission denied.', 'videohub360-memberships'));
        $ids = isset($_REQUEST['membership_ids']) ? array_map('absint', (array) wp_unslash($_REQUEST['membership_ids'])) : array();
        if ('export' === $action && $ids) {
            $this->admin->stream_csv_export($_REQUEST, $ids);
        }
        $notice = 'sync' === $action ? 'sync_failed' : ('expire' === $action ? 'expire_failed' : 'cancel_failed');
        foreach ($ids as $id) {
            $m = $this->admin->get_membership($id);
            if (!$m) continue;
            if ('sync' === $action && $this->admin->is_stripe_recurring($m) && class_exists('VH360_Stripe_Sync')) { $result = VH360_Stripe_Sync::get_instance()->sync_membership($id); if (true === $result && !is_wp_error($result)) $notice = 'sync_failed' === $notice ? 'synced' : $notice; else $notice = 'sync_failed'; }
            if ('expire' === $action && !$this->admin->is_stripe_recurring($m) && class_exists('VH360_Membership_API')) { $ok = VH360_Membership_API::get_instance()->expire_membership($id); $notice = $ok ? 'expired' : 'expire_failed'; }
            if ('cancel' === $action && !$this->admin->is_stripe_recurring($m) && class_exists('VH360_Membership_API')) { $ok = VH360_Membership_API::get_instance()->cancel_membership($id); $notice = $ok ? 'cancelled' : 'cancel_failed'; }
        }
        wp_safe_redirect(VH360_Membership_Members_Admin::get_admin_url(array('vh360_members_notice'=>$notice)));
        exit;
    }
    public function column_cb($item) { return '<input type="checkbox" name="membership_ids[]" value="' . esc_attr($item->id) . '" />'; }
    public function column_member($m) { $name = $m->display_name ?: $m->user_login ?: __('Unknown user', 'videohub360-memberships'); $url = get_edit_user_link($m->user_id); return '<strong><a href="' . esc_url($url) . '">' . esc_html($name) . '</a></strong><br><span class="vh360-muted">' . esc_html($m->user_email) . '<br>User ID: ' . esc_html($m->user_id) . '</span>'; }
    public function column_plan($m) { return esc_html($this->admin->plan_label($m->plan_key)) . '<br><code class="vh360-muted">' . esc_html($m->plan_key) . '</code>'; }
    public function column_status($m) { $out = '<span class="vh360-status-badge status-' . esc_attr($m->status) . '">' . esc_html(ucwords(str_replace('_',' ', $m->status))) . '</span>'; if ($this->admin->is_recurring($m) && $m->subscription_status) $out .= '<br><span class="vh360-billing-badge">Sub: ' . esc_html($m->subscription_status) . '</span>'; if ($m->cancel_at_period_end) $out .= '<br><span class="vh360-warning-badge">Cancels at period end</span>'; return $out; }
    public function column_billing($m) { $out = '<span class="vh360-billing-badge">' . esc_html($m->billing_mode ?: 'manual') . '</span> <span class="vh360-muted">' . esc_html($m->billing_provider ?: 'local') . '</span>'; if ($m->stripe_subscription_id) $out .= '<br><code class="vh360-copyable">' . esc_html($m->stripe_subscription_id) . '</code>'; return $out; }
    public function column_started($m) { return esc_html($m->starts_at ?: '—'); }
    public function column_renews($m) { $date = $this->admin->is_recurring($m) ? $m->current_period_end : $m->expires_at; return esc_html($date ?: ($this->admin->is_recurring($m) ? '—' : 'Lifetime / No expiration')); }
    public function column_source($m) { $link = $this->admin->order_link($m->source_order_id); if ($link) return '<a href="' . esc_url($link) . '">Order #' . esc_html($m->source_order_id) . '</a>'; if ($m->stripe_customer_id || $m->stripe_subscription_id) return '<span class="vh360-muted">Stripe customer/subscription</span>'; return '<span class="vh360-muted">Manual/local</span>'; }
    public function column_last_sync($m) { return esc_html($m->last_billing_sync_at ?: '—'); }
    public function column_actions($m) { $links = array('<a class="button button-small" href="' . esc_url(VH360_Membership_Members_Admin::get_admin_url(array('membership_id'=>$m->id))) . '">View Details</a>'); if ($this->admin->is_stripe_recurring($m) && class_exists('VH360_Stripe_Sync')) $links[] = '<a class="button button-small" href="' . esc_url($this->admin->action_url('vh360_membership_sync', $m->id)) . '">Sync from Stripe</a>'; if (!$this->admin->is_stripe_recurring($m)) { $links[] = '<a class="button button-small" href="' . esc_url(VH360_Membership_Members_Admin::get_admin_url(array('membership_id'=>$m->id))) . '#extend-local-access">Extend</a>'; $links[] = '<a class="button button-small vh360-confirm" data-confirm="Cancel local access?" href="' . esc_url($this->admin->action_url('vh360_membership_cancel', $m->id)) . '">Cancel local access</a>'; $links[] = '<a class="button button-small vh360-confirm" data-confirm="Expire local access?" href="' . esc_url($this->admin->action_url('vh360_membership_expire', $m->id)) . '">Expire</a>'; if ('active' !== $m->status) $links[] = '<a class="button button-small vh360-confirm" data-confirm="Reactivate local access?" href="' . esc_url($this->admin->action_url('vh360_membership_reactivate', $m->id)) . '">Reactivate local access</a>'; } else $links[] = '<span class="vh360-warning-badge">Stripe is source of truth</span>'; return implode(' ', $links); }
}
