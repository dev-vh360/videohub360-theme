<?php
/**
 * Membership Plans admin manager.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Plans_Admin {
    private static $instance = null;
    const CAPABILITY = 'manage_options';
    const NONCE_ACTION = 'vh360_membership_plans_save';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'redirect_tools_page'));
        add_action('admin_post_vh360_save_membership_plans', array($this, 'handle_save'));
        add_action('admin_post_vh360_delete_membership_plan', array($this, 'handle_delete'));
        add_action('admin_post_vh360_create_membership_sample_plans', array($this, 'handle_create_sample_plans'));
        add_action('admin_post_vh360_cleanup_membership_sample_plans', array($this, 'handle_cleanup_sample_plans'));
        add_action('admin_post_vh360_remap_membership_plan_key', array($this, 'handle_remap_membership_plan_key'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public static function get_admin_url() {
        return add_query_arg(array(
            'page' => 'vh360-theme-memberships',
            'tab'  => 'membership-plans',
        ), admin_url('admin.php'));
    }

    public function redirect_tools_page() {
        global $pagenow;
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $is_old_tools_page = 'tools.php' === $pagenow && 'vh360-membership-plans' === $page;
        $is_old_tab_slug = 'admin.php' === $pagenow && 'vh360-theme-memberships' === $page && 'plan-mapping' === $tab;

        if (!$is_old_tools_page && !$is_old_tab_slug) {
            return;
        }
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    public function enqueue_assets($hook) {
        $is_paid_memberships_page = false !== strpos((string) $hook, 'vh360-theme-memberships');
        $current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

        if (!$is_paid_memberships_page || 'membership-plans' !== $current_tab) {
            return;
        }
        wp_enqueue_style('vh360-membership-plans-admin', vh360_memberships_asset_url('assets/admin/membership-plans.css'), array(), vh360_memberships_asset_version('assets/admin/membership-plans.css'));
        wp_enqueue_script('vh360-membership-plans-admin', vh360_memberships_asset_url('assets/admin/membership-plans.js'), array(), vh360_memberships_asset_version('assets/admin/membership-plans.js'), true);
    }

    public function handle_delete() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }

        $plan_key = isset($_POST['plan_key']) ? sanitize_key(wp_unslash($_POST['plan_key'])) : '';
        if (!$plan_key) {
            $this->set_admin_notice('warning', array(__('No membership plan was selected for deletion.', 'videohub360-memberships')));
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        check_admin_referer('vh360_delete_membership_plan_' . $plan_key);

        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        if (isset($plans[$plan_key])) {
            unset($plans[$plan_key]);
            VH360_Membership_Plans::save_plans($plans);
            $this->set_admin_notice('success', array(sprintf(__('The plan `%s` was deleted.', 'videohub360-memberships'), $plan_key)));
        } else {
            $this->set_admin_notice('warning', array(sprintf(__('The plan `%s` could not be found. No plan was deleted.', 'videohub360-memberships'), $plan_key)));
        }

        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    public function handle_save() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        check_admin_referer(self::NONCE_ACTION);

        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        $delete = isset($_POST['delete_plan']) ? sanitize_key(wp_unslash($_POST['delete_plan'])) : '';
        $duplicate = isset($_POST['duplicate_plan']) ? sanitize_key(wp_unslash($_POST['duplicate_plan'])) : '';

        // Fallback only. Normal delete buttons use vh360_delete_membership_plan and do not submit the full save form.
        if ($delete) {
            if (isset($plans[$delete])) {
                unset($plans[$delete]);
                VH360_Membership_Plans::save_plans($plans);
                $this->set_admin_notice('success', array(sprintf(__('The plan `%s` was deleted.', 'videohub360-memberships'), $delete)));
            } else {
                $this->set_admin_notice('warning', array(sprintf(__('The plan `%s` could not be found. No plan was deleted.', 'videohub360-memberships'), $delete)));
            }
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        if ($duplicate) {
            if (isset($plans[$duplicate])) {
                $copy = $plans[$duplicate];
                $copy['id'] = $this->unique_copy_key($copy['id'], $plans);
                $copy['plan_key'] = $copy['id'];
                $copy['name'] = sprintf(__('%s Copy', 'videohub360-memberships'), $copy['name']);
                $copy['display_order'] = absint($copy['display_order']) + 1;
                $copy['is_enabled'] = false;
                $copy['enabled'] = false;
                $copy['created_at'] = current_time('mysql');
                $copy['updated_at'] = current_time('mysql');
                $plans[$copy['id']] = $copy;
                VH360_Membership_Plans::save_plans($plans);
                $this->set_admin_notice('success', array(sprintf(__('A disabled duplicate was created with the unique plan key `%s`.', 'videohub360-memberships'), $copy['id'])));
            } else {
                $this->set_admin_notice('warning', array(sprintf(__('The plan `%s` could not be found. No duplicate was created.', 'videohub360-memberships'), $duplicate)));
            }
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        $submitted = isset($_POST['plans']) && is_array($_POST['plans']) ? wp_unslash($_POST['plans']) : array();
        $new_plans = isset($_POST['new_plans']) && is_array($_POST['new_plans']) ? wp_unslash($_POST['new_plans']) : array();

        // Start from existing valid plans so failed identity validation cannot delete or overwrite records.
        $next = $plans;
        $messages = array();
        $has_errors = false;
        $submitted_key_counts = $this->get_submitted_key_counts($submitted, $new_plans);
        $accepted_keys = array();

        foreach ($submitted as $row_key => $raw_plan) {
            $row_key = sanitize_key($row_key);
            $raw_plan = $this->prepare_raw_plan($raw_plan, $row_key);
            $original_key = !empty($raw_plan['original_plan_key']) ? sanitize_key($raw_plan['original_plan_key']) : $row_key;

            $identity_errors = $this->get_identity_errors($raw_plan, $plans, $next, $submitted_key_counts, $accepted_keys, $original_key, false);
            if (!empty($identity_errors)) {
                $has_errors = true;
                $messages = array_merge($messages, $identity_errors);
                if (isset($plans[$original_key])) {
                    $next[$original_key] = $plans[$original_key];
                }
                continue;
            }

            $plan = VH360_Membership_Plans::normalize_plan($raw_plan, $original_key);
            $soft_errors = $this->get_configuration_errors($raw_plan);
            if (!empty($soft_errors)) {
                $has_errors = true;
                $plan['is_enabled'] = false;
                $plan['enabled'] = false;
                $messages = array_merge($messages, $soft_errors);
            }

            if ($original_key && $original_key !== $plan['id']) {
                unset($next[$original_key]);
            }
            $next[$plan['id']] = $plan;
            $accepted_keys[$plan['id']] = array('duplicate_reported' => false);
        }

        foreach ((array) $new_plans as $raw) {
            $raw = $this->prepare_raw_plan($raw);
            if (empty($raw['id']) && empty($raw['name']) && empty($raw['label'])) {
                continue;
            }
            $identity_errors = $this->get_identity_errors($raw, $plans, $next, $submitted_key_counts, $accepted_keys, '', true);
            if (!empty($identity_errors)) {
                $has_errors = true;
                $messages = array_merge($messages, $identity_errors);
                continue;
            }
            $plan = VH360_Membership_Plans::normalize_plan($raw);
            $soft_errors = $this->get_configuration_errors($raw);
            if (!empty($soft_errors)) {
                $has_errors = true;
                $plan['is_enabled'] = false;
                $plan['enabled'] = false;
                $messages = array_merge($messages, $soft_errors);
            }
            $next[$plan['id']] = $plan;
            $accepted_keys[$plan['id']] = array('duplicate_reported' => false);
        }

        VH360_Membership_Plans::save_plans($next);
        if (empty($messages)) {
            $messages[] = __('Membership plans saved.', 'videohub360-memberships');
        }
        $this->set_admin_notice($has_errors ? 'warning' : 'success', $messages);

        wp_safe_redirect(self::get_admin_url());
        exit;
    }


    private function set_admin_notice($type, $messages) {
        set_transient('vh360_membership_plans_admin_notice', array(
            'type' => $type,
            'messages' => (array) $messages,
        ), 60);
    }

    private function get_submitted_key_counts($submitted, $new_plans) {
        $counts = array();
        foreach ((array) $submitted as $raw_plan) {
            if (!is_array($raw_plan) || empty($raw_plan['id'])) {
                continue;
            }
            $key = sanitize_key($raw_plan['id']);
            if ($key) {
                $counts[$key] = isset($counts[$key]) ? $counts[$key] + 1 : 1;
            }
        }
        foreach ((array) $new_plans as $new_plan) {
            if (!is_array($new_plan) || empty($new_plan['id'])) {
                continue;
            }
            $key = sanitize_key($new_plan['id']);
            if ($key) {
                $counts[$key] = isset($counts[$key]) ? $counts[$key] + 1 : 1;
            }
        }
        return $counts;
    }

    private function get_identity_errors($raw_plan, $existing_plans, $next_plans, $submitted_key_counts, &$accepted_keys, $original_key = '', $is_new = false) {
        $errors = array();
        $raw_id = isset($raw_plan['id']) ? trim((string) $raw_plan['id']) : '';
        $candidate_key = sanitize_key($raw_id);
        $row_label = $this->get_row_label($raw_plan, $original_key, $is_new);

        if ('' === $raw_id) {
            $errors[] = $is_new
                ? __('A new plan row was skipped because it did not include a plan key.', 'videohub360-memberships')
                : sprintf(__('The row for `%s` was not saved because the plan key is missing. The existing plan was preserved.', 'videohub360-memberships'), $original_key);
            return $errors;
        }

        if ($raw_id !== $candidate_key || !preg_match('/^[a-z0-9_]+$/', $candidate_key)) {
            $errors[] = sprintf(__('The plan key `%s` is invalid. Use lowercase letters, numbers, and underscores only. That row was not saved.', 'videohub360-memberships'), $raw_id);
            return $errors;
        }

        if (!empty($submitted_key_counts[$candidate_key]) && $submitted_key_counts[$candidate_key] > 1 && !empty($accepted_keys[$candidate_key])) {
            if (empty($accepted_keys[$candidate_key]['duplicate_reported'])) {
                $errors[] = sprintf(__('The plan key `%s` was submitted more than once. Duplicate rows for that key were not saved.', 'videohub360-memberships'), $candidate_key);
                $accepted_keys[$candidate_key]['duplicate_reported'] = true;
            }
            return $errors;
        }

        if (isset($next_plans[$candidate_key]) && $candidate_key !== $original_key && !isset($accepted_keys[$candidate_key])) {
            $errors[] = sprintf(__('The plan key `%s` is already used by another saved plan. The row `%s` was not saved.', 'videohub360-memberships'), $candidate_key, $row_label);
            return $errors;
        }

        if (isset($accepted_keys[$candidate_key]) && $candidate_key !== $original_key) {
            $errors[] = sprintf(__('The plan key `%s` was already accepted from another submitted row. The row `%s` was not saved.', 'videohub360-memberships'), $candidate_key, $row_label);
            return $errors;
        }

        if (!$is_new && $original_key && !isset($existing_plans[$original_key])) {
            $errors[] = sprintf(__('The original plan key `%s` could not be found. The row `%s` was not saved.', 'videohub360-memberships'), $original_key, $row_label);
        }

        return $errors;
    }

    private function get_configuration_errors($raw_plan) {
        $messages = array();
        $validation = VH360_Membership_Plans::validate_plan($raw_plan, array(), isset($raw_plan['id']) ? sanitize_key($raw_plan['id']) : '');
        if (!is_wp_error($validation)) {
            return $messages;
        }

        $identity_codes = array('plan_key_required', 'plan_key_format', 'plan_key_unique');
        foreach ($validation->errors as $code => $errors) {
            if (in_array($code, $identity_codes, true)) {
                continue;
            }
            foreach ($errors as $message) {
                $messages[] = sprintf(__('%s The plan was saved as disabled until this is fixed.', 'videohub360-memberships'), $message);
            }
        }
        return $messages;
    }

    private function get_row_label($raw_plan, $original_key = '', $is_new = false) {
        if (!empty($raw_plan['name'])) {
            return sanitize_text_field($raw_plan['name']);
        }
        if (!empty($raw_plan['label'])) {
            return sanitize_text_field($raw_plan['label']);
        }
        if ($original_key) {
            return $original_key;
        }
        return $is_new ? __('new plan', 'videohub360-memberships') : __('submitted plan', 'videohub360-memberships');
    }

    private function prepare_raw_plan($raw, $fallback_key = '') {
        $raw = is_array($raw) ? $raw : array();
        $raw['id'] = isset($raw['id']) ? trim((string) $raw['id']) : $fallback_key;
        $raw['original_plan_key'] = isset($raw['original_plan_key']) ? sanitize_key($raw['original_plan_key']) : $fallback_key;
        if (isset($raw['features']) && is_string($raw['features'])) {
            $raw['features'] = preg_split('/\r\n|\r|\n/', $raw['features']);
        }
        if (!isset($raw['access_features']) || !is_array($raw['access_features'])) {
            $raw['access_features'] = array();
        }
        $raw['is_enabled'] = !empty($raw['is_enabled']);
        $raw['is_featured'] = !empty($raw['is_featured']);
        $raw['show_on_pricing'] = !empty($raw['show_on_pricing']);
        $raw['show_in_dashboard'] = !empty($raw['show_in_dashboard']);
        return $raw;
    }

    private function unique_copy_key($base, $plans) {
        $base = sanitize_key($base . '_copy');
        $key = $base;
        $i = 2;
        while (isset($plans[$key])) {
            $key = $base . '_' . $i;
            $i++;
        }
        return $key;
    }


    public function handle_create_sample_plans() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_create_membership_sample_plans');
        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        if (!empty($plans)) {
            $notice = array(
                'type' => 'warning',
                'messages' => array(__('Sample plans were not created because membership plans already exist.', 'videohub360-memberships')),
            );
        } else {
            VH360_Membership_Plans::maybe_seed_default_plans();
            $notice = array(
                'type' => 'success',
                'messages' => array(__('Disabled sample plans were created. Edit the checkout settings and enable only the plans you want to publish.', 'videohub360-memberships')),
            );
        }
        set_transient('vh360_membership_plans_admin_notice', $notice, 60);
        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    public function handle_cleanup_sample_plans() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_cleanup_membership_sample_plans');

        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        $sample_keys = class_exists('VH360_Membership_Plans') && method_exists('VH360_Membership_Plans', 'get_default_sample_plan_keys')
            ? VH360_Membership_Plans::get_default_sample_plan_keys()
            : array();
        $removed_plans = 0;

        foreach ($sample_keys as $sample_key) {
            $sample_key = sanitize_key($sample_key);
            if ($sample_key && isset($plans[$sample_key])) {
                unset($plans[$sample_key]);
                $removed_plans++;
            }
        }

        if (class_exists('VH360_Membership_Plans')) {
            VH360_Membership_Plans::save_plans($plans);
        }

        $valid_plan_keys = array_fill_keys(array_keys($plans), true);
        $mapping_result = $this->cleanup_stale_woocommerce_plan_mappings($valid_plan_keys);
        $this->clear_membership_plan_caches();

        $messages = array();
        if ($removed_plans || $mapping_result['cleared']) {
            if ($mapping_result['skipped']) {
                $messages[] = sprintf(
                    __('Sample plan cleanup complete. Removed %1$d sample plans. WooCommerce was not active, so product mapping cleanup was skipped.', 'videohub360-memberships'),
                    $removed_plans
                );
            } else {
                $messages[] = sprintf(
                    __('Sample plan cleanup complete. Removed %1$d sample plans and cleared %2$d stale WooCommerce product mappings.', 'videohub360-memberships'),
                    $removed_plans,
                    $mapping_result['cleared']
                );
            }
        } else {
            $messages[] = $mapping_result['skipped']
                ? __('No sample plans were found. WooCommerce was not active, so product mapping cleanup was skipped.', 'videohub360-memberships')
                : __('No sample plans or stale product mappings were found.', 'videohub360-memberships');
        }
        $messages[] = __('If old frontend output still appears, clear any active object cache or page cache plugin.', 'videohub360-memberships');

        $this->set_admin_notice('success', $messages);
        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    private function cleanup_stale_woocommerce_plan_mappings($valid_plan_keys) {
        if (!function_exists('wc_get_product')) {
            return array('cleared' => 0, 'skipped' => true);
        }

        $product_ids = get_posts(array(
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_vh360_membership_plan',
                    'value'   => '',
                    'compare' => '!=',
                ),
            ),
        ));

        $cleared = 0;
        foreach ($product_ids as $product_id) {
            $mapped_plan_key = sanitize_key(get_post_meta($product_id, '_vh360_membership_plan', true));
            if (!$mapped_plan_key || isset($valid_plan_keys[$mapped_plan_key])) {
                continue;
            }
            delete_post_meta($product_id, '_vh360_membership_plan');
            $cleared++;
        }

        return array('cleared' => $cleared, 'skipped' => false);
    }

    private function clear_membership_plan_caches() {
        wp_cache_delete('vh360_membership_plans', 'options');
        wp_cache_delete('alloptions', 'options');
        delete_transient('vh360_membership_plans_admin_notice');
    }

    public function handle_remap_membership_plan_key() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        check_admin_referer('vh360_remap_membership_plan_key');

        $old_plan_key = isset($_POST['old_plan_key']) ? sanitize_key(wp_unslash($_POST['old_plan_key'])) : '';
        $new_plan_key = isset($_POST['new_plan_key']) ? sanitize_key(wp_unslash($_POST['new_plan_key'])) : '';
        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        $errors = array();

        if (!$old_plan_key) {
            $errors[] = __('Please enter the old plan key.', 'videohub360-memberships');
        }
        if (!$new_plan_key) {
            $errors[] = __('Please choose a valid new plan key.', 'videohub360-memberships');
        } elseif (empty($plans[$new_plan_key])) {
            $errors[] = __('The selected new plan does not exist.', 'videohub360-memberships');
        }
        if ($old_plan_key && $new_plan_key && $old_plan_key === $new_plan_key) {
            $errors[] = __('The old and new plan keys must be different.', 'videohub360-memberships');
        }

        if (!empty($errors)) {
            $this->set_admin_notice('error', $errors);
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        global $wpdb;
        $table = $this->get_memberships_table_name();
        $affected_memberships = $wpdb->get_results($wpdb->prepare("SELECT id, user_id FROM {$table} WHERE plan_key = %s", $old_plan_key));
        if (empty($affected_memberships)) {
            $this->set_admin_notice('warning', array(sprintf(__('No membership records were found using `%s`.', 'videohub360-memberships'), $old_plan_key)));
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        $updated = $wpdb->update(
            $table,
            array(
                'plan_key'   => $new_plan_key,
                'updated_at' => current_time('mysql'),
            ),
            array('plan_key' => $old_plan_key),
            array('%s', '%s'),
            array('%s')
        );

        if (false === $updated) {
            $message = __('Could not remap membership plan keys. Please check the database error log.', 'videohub360-memberships');
            if (defined('WP_DEBUG') && WP_DEBUG && !empty($wpdb->last_error)) {
                $message .= ' ' . sprintf(__('Database error: %s', 'videohub360-memberships'), $wpdb->last_error);
            }
            $this->set_admin_notice('error', array($message));
            wp_safe_redirect(self::get_admin_url());
            exit;
        }

        $this->log_plan_key_remap_events($affected_memberships, $old_plan_key, $new_plan_key);
        $this->clear_membership_user_caches($affected_memberships);

        $this->set_admin_notice('success', array(sprintf(
            __('Remapped %1$d membership record(s) from `%2$s` to `%3$s`.', 'videohub360-memberships'),
            (int) $updated,
            $old_plan_key,
            $new_plan_key
        )));
        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    private function get_memberships_table_name() {
        global $wpdb;
        return class_exists('VH360_Membership_Database') && method_exists('VH360_Membership_Database', 'get_memberships_table')
            ? VH360_Membership_Database::get_memberships_table()
            : $wpdb->prefix . 'vh360_memberships';
    }

    private function log_plan_key_remap_events($memberships, $old_plan_key, $new_plan_key) {
        $api = class_exists('VH360_Membership_API') ? VH360_Membership_API::get_instance() : null;
        if (!$api || !method_exists($api, 'log_event')) {
            return;
        }

        $admin_user_id = get_current_user_id();
        foreach ((array) $memberships as $membership) {
            $api->log_event(absint($membership->id), 'plan_key_remapped', array(
                'message'       => sprintf('Plan key remapped by admin from %s to %s.', $old_plan_key, $new_plan_key),
                'old_plan_key'  => $old_plan_key,
                'new_plan_key'  => $new_plan_key,
                'admin_user_id' => $admin_user_id,
            ), $admin_user_id);
        }
    }

    private function clear_membership_user_caches($memberships) {
        $user_ids = array();
        foreach ((array) $memberships as $membership) {
            $user_id = isset($membership->user_id) ? absint($membership->user_id) : 0;
            if ($user_id) {
                $user_ids[$user_id] = true;
            }
        }
        foreach (array_keys($user_ids) as $user_id) {
            clean_user_cache($user_id);
        }
    }

    private function get_missing_membership_plan_key_counts($plans) {
        global $wpdb;
        $table = $this->get_memberships_table_name();
        $rows = $wpdb->get_results("SELECT plan_key, COUNT(*) AS record_count FROM {$table} GROUP BY plan_key");
        if (empty($rows)) {
            return array();
        }

        $missing = array();
        foreach ($rows as $row) {
            $plan_key = sanitize_key($row->plan_key);
            if ($plan_key && empty($plans[$plan_key])) {
                $missing[$plan_key] = absint($row->record_count);
            }
        }
        return $missing;
    }

    public function render_page() {
        $this->render_manager(true);
    }

    public function render_manager($wrap = false) {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $plans = VH360_Membership_Plans::get_plan_registry();
        $missing_plan_key_counts = $this->get_missing_membership_plan_key_counts($plans);
        $notice = get_transient('vh360_membership_plans_admin_notice');
        delete_transient('vh360_membership_plans_admin_notice');
        ?>
        <?php if ($wrap) : ?><div class="wrap"><?php endif; ?>
        <div class="vh360-membership-plans-admin">
            <div class="vh360-plans-toolbar">
                <div>
                    <h1><?php esc_html_e('Membership Plans', 'videohub360-memberships'); ?></h1>
                    <p><?php esc_html_e('Create and manage the plans shown on pricing pages and used for Stripe or WooCommerce checkout.', 'videohub360-memberships'); ?></p>
                </div>
                <button type="button" class="button button-primary" data-vh360-add-plan><?php esc_html_e('+ Add New Plan', 'videohub360-memberships'); ?></button>
            </div>
            <?php if ($notice && !empty($notice['messages'])) : ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?>"><ul><?php foreach ($notice['messages'] as $message) : ?><li><?php echo esc_html($message); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php if (!empty($missing_plan_key_counts)) : ?>
                <div class="notice notice-warning"><p><?php printf(esc_html__('%d membership record(s) reference plan keys that no longer exist. Use “Remap Existing Membership Plan Keys” to repair them.', 'videohub360-memberships'), array_sum($missing_plan_key_counts)); ?></p></div>
            <?php endif; ?>
            <?php if (empty($plans)) : ?>
                <div class="vh360-plans-empty-state" data-vh360-empty-state>
                    <h2><?php esc_html_e('No membership plans yet.', 'videohub360-memberships'); ?></h2>
                    <p><?php esc_html_e('Membership plans control what appears on your pricing page and how users subscribe through Stripe or purchase one-time/lifetime access through WooCommerce.', 'videohub360-memberships'); ?></p>
                    <p><?php esc_html_e('Create your first membership plan to get started.', 'videohub360-memberships'); ?></p>
                    <button type="button" class="button button-primary" data-vh360-add-plan><?php esc_html_e('Add New Plan', 'videohub360-memberships'); ?></button>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vh360-sample-plans-form">
                        <?php wp_nonce_field('vh360_create_membership_sample_plans'); ?>
                        <input type="hidden" name="action" value="vh360_create_membership_sample_plans" />
                        <p class="description"><?php esc_html_e('Optional: create disabled sample plans to see how monthly, yearly, free, and lifetime plans can be configured. You can edit or delete them.', 'videohub360-memberships'); ?></p>
                        <?php submit_button(__('Create Sample Plans', 'videohub360-memberships'), 'secondary', 'submit', false); ?>
                    </form>
                </div>
            <?php endif; ?>
            <?php $this->render_cleanup_sample_plans_tool(); ?>
            <?php $this->render_remap_membership_plan_keys_tool($plans, $missing_plan_key_counts); ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vh360-plans-form">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="action" value="vh360_save_membership_plans" />
                <div class="vh360-plan-list" data-vh360-plan-list>
                    <?php foreach ($plans as $key => $plan) : $this->render_plan_card($key, $plan); endforeach; ?>
                </div>
                <div class="vh360-membership-plan-actions">
                    <button type="button" class="button vh360-add-plan-button" data-vh360-add-plan><?php esc_html_e('+ Add New Plan', 'videohub360-memberships'); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Membership Plans', 'videohub360-memberships'); ?></button>
                </div>
            </form>
            <?php foreach ($plans as $key => $plan) : $this->render_delete_form($key); endforeach; ?>
            <template id="vh360-new-plan-template">
                <?php $this->render_plan_card('__NEW_PLAN_INDEX__', $this->get_empty_plan(), true); ?>
            </template>
        </div>
        <?php if ($wrap) : ?></div><?php endif; ?>
        <?php
    }

    private function render_cleanup_sample_plans_tool() {
        $sample_keys = class_exists('VH360_Membership_Plans') && method_exists('VH360_Membership_Plans', 'get_default_sample_plan_keys')
            ? VH360_Membership_Plans::get_default_sample_plan_keys()
            : array();
        ?>
        <div class="vh360-maintenance-card">
            <h2><?php esc_html_e('Cleanup Sample Plans / Stale Plan Data', 'videohub360-memberships'); ?></h2>
            <p><?php esc_html_e('Removes default sample membership plans and clears WooCommerce product mappings that point to deleted or missing plans. Use this if old demo plans still appear on the frontend Membership dashboard after deletion.', 'videohub360-memberships'); ?></p>
            <p class="description"><strong><?php esc_html_e('Warning:', 'videohub360-memberships'); ?></strong> <?php esc_html_e('This removes plans using the default sample plan keys. Do not run this if you intentionally use those sample keys for real live plans.', 'videohub360-memberships'); ?></p>
            <?php if (!empty($sample_keys)) : ?>
                <p class="description"><?php printf(esc_html__('Default sample keys: %s', 'videohub360-memberships'), esc_html(implode(', ', $sample_keys))); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('vh360_cleanup_membership_sample_plans'); ?>
                <input type="hidden" name="action" value="vh360_cleanup_membership_sample_plans" />
                <?php submit_button(__('Clean Up Sample Plans & Stale Mappings', 'videohub360-memberships'), 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    private function render_remap_membership_plan_keys_tool($plans, $missing_plan_key_counts = array()) {
        ?>
        <div class="vh360-maintenance-card">
            <h2><?php esc_html_e('Remap Existing Membership Plan Keys', 'videohub360-memberships'); ?></h2>
            <p><?php esc_html_e('Use this repair tool when existing membership records point to an old plan key that no longer exists in the current plan registry. This updates active and historical membership records from one plan key to another valid plan key. Plan keys should normally be treated as permanent IDs.', 'videohub360-memberships'); ?></p>
            <p class="description"><?php esc_html_e('This updates local Videohub360 membership records only. It does not modify Stripe subscriptions.', 'videohub360-memberships'); ?></p>
            <?php if (!empty($missing_plan_key_counts)) : ?>
                <p class="description"><strong><?php esc_html_e('Missing plan keys currently found:', 'videohub360-memberships'); ?></strong> <?php echo esc_html($this->format_missing_plan_key_counts($missing_plan_key_counts)); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('vh360_remap_membership_plan_key'); ?>
                <input type="hidden" name="action" value="vh360_remap_membership_plan_key" />
                <p>
                    <label for="vh360_old_plan_key"><strong><?php esc_html_e('Old plan key', 'videohub360-memberships'); ?></strong></label><br />
                    <input type="text" id="vh360_old_plan_key" name="old_plan_key" class="regular-text" value="" placeholder="<?php esc_attr_e('pro_monthly', 'videohub360-memberships'); ?>" />
                </p>
                <p>
                    <label for="vh360_new_plan_key"><strong><?php esc_html_e('New plan key', 'videohub360-memberships'); ?></strong></label><br />
                    <select id="vh360_new_plan_key" name="new_plan_key">
                        <option value=""><?php esc_html_e('Choose a current plan…', 'videohub360-memberships'); ?></option>
                        <?php foreach ($plans as $key => $plan) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html(sprintf('%s (%s)', !empty($plan['label']) ? $plan['label'] : $key, $key)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <?php submit_button(__('Remap Membership Plan Keys', 'videohub360-memberships'), 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    private function format_missing_plan_key_counts($missing_plan_key_counts) {
        $parts = array();
        foreach ((array) $missing_plan_key_counts as $plan_key => $count) {
            $parts[] = sprintf('%s (%d)', $plan_key, $count);
        }
        return implode(', ', $parts);
    }

    private function get_empty_plan() {
        return array('id'=>'','name'=>'','label'=>'','description'=>'','plan_group'=>'','billing_type'=>'recurring','billing_interval'=>'monthly','price'=>'','currency'=>'USD','compare_at_price'=>'','savings_text'=>'','stripe_price_id'=>'','woocommerce_product_id'=>0,'features'=>array(),'access_features'=>array(),'access_features_configured'=>true,'tier_level'=>0,'show_on_pricing'=>true,'show_in_dashboard'=>true,'is_featured'=>false,'is_enabled'=>false,'display_order'=>999,'button_text'=>__('Choose Plan','videohub360-memberships'),'checkout_behavior'=>'stripe');
    }

    private function render_plan_card($key, $plan, $is_new = false) {
        $field = $is_new ? 'new_plans[' . $key . ']' : 'plans[' . $key . ']';
        $features = !empty($plan['features']) && is_array($plan['features']) ? implode("\n", $plan['features']) : '';
        $access_features = !empty($plan['access_features']) && is_array($plan['access_features']) ? $plan['access_features'] : array();
        $title = $is_new ? __('New Membership Plan', 'videohub360-memberships') : (!empty($plan['name']) ? $plan['name'] : $key);
        ?>
        <section class="vh360-plan-card" data-vh360-plan-card>
            <?php if (!$is_new) : ?><input type="hidden" name="<?php echo esc_attr($field); ?>[original_plan_key]" value="<?php echo esc_attr($key); ?>" /><?php endif; ?>
            <div class="vh360-plan-card__header">
                <div>
                    <h2><?php echo esc_html($title); ?></h2>
                    <?php if (!$is_new) : ?><code><?php echo esc_html($key); ?></code><?php endif; ?>
                </div>
                <div class="vh360-plan-card__header-actions">
                    <?php if ($is_new) : ?>
                        <button type="button" class="button button-link-delete" data-vh360-remove-new-plan><?php esc_html_e('Remove', 'videohub360-memberships'); ?></button>
                    <?php else : ?>
                        <span class="vh360-plan-pill"><?php echo !empty($plan['is_enabled']) ? esc_html__('Enabled', 'videohub360-memberships') : esc_html__('Disabled', 'videohub360-memberships'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="vh360-plan-card__body">
                <div class="vh360-plan-section vh360-plan-section--primary">
                    <h3><?php esc_html_e('Plan Basics', 'videohub360-memberships'); ?></h3>
                    <div class="vh360-plan-grid">
                        <?php $this->field($field, 'name', __('Plan Name', 'videohub360-memberships'), $plan['name'], __('Required. Used in admin and as the default public label.', 'videohub360-memberships'), 'text', '', 'data-vh360-plan-name'); ?>
                        <?php $this->field($field, 'price', __('Price', 'videohub360-memberships'), $plan['price'], __('Numeric amount for paid plans. Use 0 or empty for free plans.', 'videohub360-memberships'), 'number', '0.01'); ?>
                        <?php $this->select($field, 'billing_type', __('Billing Type', 'videohub360-memberships'), $plan['billing_type'], VH360_Membership_Plans::get_allowed_billing_types(), __('Controls whether checkout is recurring, one-time/lifetime, or free.', 'videohub360-memberships'), 'data-vh360-billing-type'); ?>
                        <?php $this->select($field, 'billing_interval', __('Billing Interval', 'videohub360-memberships'), $plan['billing_interval'], VH360_Membership_Plans::get_allowed_billing_intervals(), __('Controls frontend tab placement.', 'videohub360-memberships'), 'data-vh360-billing-interval'); ?>
                    </div>
                    <div class="vh360-plan-grid vh360-plan-grid--wide">
                        <?php $this->textarea($field, 'features', __('Features', 'videohub360-memberships'), $features, __('One plain-text feature per line.', 'videohub360-memberships'), 5); ?>
                        <div class="vh360-plan-field vh360-plan-toggle-field"><label><input type="checkbox" name="<?php echo esc_attr($field); ?>[is_enabled]" value="1" <?php checked(!empty($plan['is_enabled'])); ?> /> <?php esc_html_e('Enabled', 'videohub360-memberships'); ?></label><p class="description"><?php esc_html_e('Only enabled, checkout-ready plans appear to visitors.', 'videohub360-memberships'); ?></p></div>
                    </div>
                </div>


                <div class="vh360-plan-section vh360-plan-section--access">
                    <h3><?php esc_html_e('Feature Access', 'videohub360-memberships'); ?></h3>
                    <p class="description"><?php esc_html_e('Choose which Videohub360 features this plan unlocks. These settings control actual membership access and are separate from the public pricing-card feature text.', 'videohub360-memberships'); ?></p>
                    <input type="hidden" name="<?php echo esc_attr($field); ?>[access_features_configured]" value="1" />
                    <div class="vh360-feature-access-grid">
                        <?php foreach (VH360_Membership_Plans::get_feature_access_options() as $feature_key => $feature_label) : ?>
                            <label class="vh360-feature-access-option"><input type="checkbox" name="<?php echo esc_attr($field); ?>[access_features][]" value="<?php echo esc_attr($feature_key); ?>" <?php checked(in_array($feature_key, $access_features, true)); ?> /> <?php echo esc_html($feature_label); ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php $show_free_access_warning = 'free' === $plan['billing_type'] && array_intersect($access_features, array('create_videos', 'live_rooms', 'appointments', 'push_notifications', 'direct_messages')); ?>
                    <p class="vh360-plan-access-warning <?php echo $show_free_access_warning ? '' : 'is-hidden'; ?>" data-vh360-free-access-warning><?php esc_html_e('This is a free plan with advanced access enabled. Confirm this is intentional before using it publicly.', 'videohub360-memberships'); ?></p>
                </div>

                <div class="vh360-plan-section vh360-plan-section--checkout">
                    <h3><?php esc_html_e('Checkout Settings', 'videohub360-memberships'); ?></h3>
                    <div class="vh360-plan-grid">
                        <div data-vh360-show-for="recurring">
                            <?php $this->field($field, 'stripe_price_id', __('Stripe Price ID', 'videohub360-memberships'), $plan['stripe_price_id'], __('Required for enabled recurring Stripe Checkout plans.', 'videohub360-memberships')); ?>
                        </div>
                        <div data-vh360-show-for="woocommerce">
                            <?php $this->field($field, 'woocommerce_product_id', __('WooCommerce Product ID', 'videohub360-memberships'), $plan['woocommerce_product_id'], __('Required for WooCommerce checkout plans and must be a published product.', 'videohub360-memberships'), 'number'); ?>
                        </div>
                        <div data-vh360-show-for="woocommerce">
                            <?php $this->select($field, 'checkout_behavior', __('Checkout Behavior', 'videohub360-memberships'), $plan['checkout_behavior'], VH360_Membership_Plans::get_allowed_checkout_behaviors(), __('Determines whether buttons use a product page or add-to-cart for WooCommerce plans.', 'videohub360-memberships'), 'data-vh360-checkout-behavior'); ?>
                        </div>
                    </div>
                </div>

                <div class="vh360-plan-section vh360-plan-section--display">
                    <h3><?php esc_html_e('Pricing Display', 'videohub360-memberships'); ?></h3>
                    <div class="vh360-plan-grid">
                        <?php $this->field($field, 'label', __('Display Label', 'videohub360-memberships'), $plan['label'], __('Name shown on pricing cards and dashboards.', 'videohub360-memberships')); ?>
                        <?php $this->field($field, 'button_text', __('Button Text', 'videohub360-memberships'), $plan['button_text'], __('Pricing card call-to-action text.', 'videohub360-memberships')); ?>
                        <?php $this->field($field, 'compare_at_price', __('Compare At Price', 'videohub360-memberships'), $plan['compare_at_price'], __('Optional crossed-out price text.', 'videohub360-memberships')); ?>
                        <?php $this->field($field, 'savings_text', __('Savings Text', 'videohub360-memberships'), $plan['savings_text'], __('Optional savings badge text.', 'videohub360-memberships')); ?>
                    </div>
                    <div class="vh360-plan-grid vh360-plan-grid--wide">
                        <?php $this->textarea($field, 'description', __('Description', 'videohub360-memberships'), $plan['description'], __('Short pricing card description.', 'videohub360-memberships'), 3); ?>
                        <div class="vh360-plan-field vh360-plan-toggle-field"><label><input type="checkbox" name="<?php echo esc_attr($field); ?>[is_featured]" value="1" <?php checked(!empty($plan['is_featured'])); ?> /> <?php esc_html_e('Featured / Recommended', 'videohub360-memberships'); ?></label><p class="description"><?php esc_html_e('Adds a recommendation badge to pricing displays.', 'videohub360-memberships'); ?></p></div>
                    </div>
                    <div class="vh360-plan-grid vh360-plan-grid--wide">
                        <div class="vh360-plan-field vh360-plan-toggle-field"><strong><?php esc_html_e('Display Locations', 'videohub360-memberships'); ?></strong><label><input type="checkbox" name="<?php echo esc_attr($field); ?>[show_on_pricing]" value="1" <?php checked(!empty($plan['show_on_pricing'])); ?> /> <?php esc_html_e('Show on public pricing pages', 'videohub360-memberships'); ?></label><label><input type="checkbox" name="<?php echo esc_attr($field); ?>[show_in_dashboard]" value="1" <?php checked(!empty($plan['show_in_dashboard'])); ?> /> <?php esc_html_e('Show in Membership dashboard tab', 'videohub360-memberships'); ?></label><p class="description"><?php esc_html_e('Choose where this plan appears. Disabling a display location does not disable the plan itself or break existing memberships.', 'videohub360-memberships'); ?></p></div>
                    </div>
                </div>

                <details class="vh360-plan-section vh360-plan-advanced">
                    <summary><?php esc_html_e('Advanced Settings', 'videohub360-memberships'); ?></summary>
                    <div class="vh360-plan-grid">
                        <?php $this->field($field, 'id', __('Plan Key', 'videohub360-memberships'), $plan['id'], __('Stable internal identifier. It is auto-generated from the plan name and should not be changed after memberships exist.', 'videohub360-memberships'), 'text', '', 'data-vh360-plan-key'); ?>
                        <?php $this->field($field, 'plan_group', __('Plan Group', 'videohub360-memberships'), $plan['plan_group'], __('Connects monthly/yearly versions of the same plan for aligned pricing cards.', 'videohub360-memberships'), 'text', '', 'data-vh360-plan-group'); ?>
                        <?php $this->field($field, 'tier_level', __('Access Tier', 'videohub360-memberships'), $plan['tier_level'], __('Higher numbers satisfy higher-tier access checks.', 'videohub360-memberships'), 'number'); ?>
                        <?php $this->field($field, 'display_order', __('Display Order', 'videohub360-memberships'), $plan['display_order'], __('Controls admin and frontend ordering.', 'videohub360-memberships'), 'number'); ?>
                        <?php $this->field($field, 'currency', __('Currency', 'videohub360-memberships'), $plan['currency'], __('ISO currency code used for display.', 'videohub360-memberships')); ?>
                    </div>
                </details>

                <?php if (!$is_new) : ?><div class="vh360-plan-actions"><button class="button" type="submit" name="duplicate_plan" value="<?php echo esc_attr($key); ?>"><?php esc_html_e('Duplicate', 'videohub360-memberships'); ?></button><button class="button button-link-delete" type="submit" form="vh360-delete-plan-<?php echo esc_attr($key); ?>" data-vh360-delete-plan data-confirm="<?php esc_attr_e('Delete this membership plan?', 'videohub360-memberships'); ?>"><?php esc_html_e('Delete', 'videohub360-memberships'); ?></button></div><?php endif; ?>
            </div>
        </section>
        <?php
    }


    private function render_delete_form($key) {
        ?>
        <form id="vh360-delete-plan-<?php echo esc_attr($key); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="vh360-delete-plan-form">
            <?php wp_nonce_field('vh360_delete_membership_plan_' . $key); ?>
            <input type="hidden" name="action" value="vh360_delete_membership_plan" />
            <input type="hidden" name="plan_key" value="<?php echo esc_attr($key); ?>" />
        </form>
        <?php
    }

    private function field($field, $key, $label, $value, $description, $type = 'text', $step = '', $attrs = '') {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><input type="<?php echo esc_attr($type); ?>" <?php echo $step ? 'step="' . esc_attr($step) . '"' : ''; ?> name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" <?php echo $attrs; ?> /><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }

    private function textarea($field, $key, $label, $value, $description, $rows = 4) {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><textarea rows="<?php echo esc_attr($rows); ?>" name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($value); ?></textarea><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }

    private function select($field, $key, $label, $value, $options, $description, $attrs = '') {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><select name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]" <?php echo $attrs; ?>>
            <?php foreach ($options as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($value, $option); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $option))); ?></option><?php endforeach; ?>
        </select><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }
}
