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
        if ('tools.php' !== $pagenow || !isset($_GET['page']) || 'vh360-membership-plans' !== sanitize_key(wp_unslash($_GET['page']))) {
            return;
        }
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    public function enqueue_assets($hook) {
        if (false === strpos((string) $hook, 'vh360-theme-memberships')) {
            return;
        }
        wp_enqueue_style('vh360-membership-plans-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/membership-plans.css', array(), VH360_MEMBERSHIPS_VERSION);
        wp_enqueue_script('vh360-membership-plans-admin', VH360_MEMBERSHIPS_URL . 'assets/admin/membership-plans.js', array(), VH360_MEMBERSHIPS_VERSION, true);
    }

    public function handle_save() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-memberships'));
        }
        check_admin_referer(self::NONCE_ACTION);

        $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
        $submitted = isset($_POST['plans']) && is_array($_POST['plans']) ? wp_unslash($_POST['plans']) : array();
        $delete = isset($_POST['delete_plan']) ? sanitize_key(wp_unslash($_POST['delete_plan'])) : '';
        $duplicate = isset($_POST['duplicate_plan']) ? sanitize_key(wp_unslash($_POST['duplicate_plan'])) : '';

        // Start from existing valid plans so failed identity validation cannot delete or overwrite records.
        $next = $plans;
        $messages = array();
        $has_errors = false;
        $submitted_key_counts = $this->get_submitted_key_counts($submitted, isset($_POST['new_plan']) && is_array($_POST['new_plan']) ? wp_unslash($_POST['new_plan']) : array());
        $accepted_keys = array();

        foreach ($submitted as $row_key => $raw_plan) {
            $row_key = sanitize_key($row_key);
            $raw_plan = $this->prepare_raw_plan($raw_plan, $row_key);
            $original_key = !empty($raw_plan['original_plan_key']) ? sanitize_key($raw_plan['original_plan_key']) : $row_key;

            if ($delete && $delete === $original_key) {
                unset($next[$original_key]);
                $messages[] = sprintf(__('The plan `%s` was deleted.', 'videohub360-memberships'), $original_key);
                continue;
            }

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
            $accepted_keys[$plan['id']] = true;

            if ($duplicate && $duplicate === $original_key) {
                $copy = $plan;
                $copy['id'] = $this->unique_copy_key($copy['id'], $next);
                $copy['plan_key'] = $copy['id'];
                $copy['name'] = sprintf(__('%s Copy', 'videohub360-memberships'), $copy['name']);
                $copy['display_order'] = absint($copy['display_order']) + 1;
                $copy['is_enabled'] = false;
                $copy['enabled'] = false;
                $copy['created_at'] = current_time('mysql');
                $copy['updated_at'] = current_time('mysql');
                $next[$copy['id']] = $copy;
                $messages[] = sprintf(__('A disabled duplicate was created with the unique plan key `%s`.', 'videohub360-memberships'), $copy['id']);
            }
        }

        if (!empty($_POST['new_plan']) && is_array($_POST['new_plan'])) {
            $raw = $this->prepare_raw_plan(wp_unslash($_POST['new_plan']));
            if (!empty($raw['id']) || !empty($raw['name']) || !empty($raw['label'])) {
                $identity_errors = $this->get_identity_errors($raw, $plans, $next, $submitted_key_counts, $accepted_keys, '', true);
                if (!empty($identity_errors)) {
                    $has_errors = true;
                    $messages = array_merge($messages, $identity_errors);
                } else {
                    $plan = VH360_Membership_Plans::normalize_plan($raw);
                    $soft_errors = $this->get_configuration_errors($raw);
                    if (!empty($soft_errors)) {
                        $has_errors = true;
                        $plan['is_enabled'] = false;
                        $plan['enabled'] = false;
                        $messages = array_merge($messages, $soft_errors);
                    }
                    $next[$plan['id']] = $plan;
                    $accepted_keys[$plan['id']] = true;
                }
            }
        }

        VH360_Membership_Plans::save_plans($next);
        if (empty($messages)) {
            $messages[] = __('Membership plans saved.', 'videohub360-memberships');
        }
        set_transient('vh360_membership_plans_admin_notice', array(
            'type' => $has_errors ? 'warning' : 'success',
            'messages' => $messages,
        ), 60);

        wp_safe_redirect(self::get_admin_url());
        exit;
    }

    private function get_submitted_key_counts($submitted, $new_plan) {
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
        if (is_array($new_plan) && !empty($new_plan['id'])) {
            $key = sanitize_key($new_plan['id']);
            if ($key) {
                $counts[$key] = isset($counts[$key]) ? $counts[$key] + 1 : 1;
            }
        }
        return $counts;
    }

    private function get_identity_errors($raw_plan, $existing_plans, $next_plans, $submitted_key_counts, $accepted_keys, $original_key = '', $is_new = false) {
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
            $errors[] = sprintf(__('The plan key `%s` was submitted more than once. The duplicate row `%s` was not saved.', 'videohub360-memberships'), $candidate_key, $row_label);
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
        $raw['is_enabled'] = !empty($raw['is_enabled']);
        $raw['is_featured'] = !empty($raw['is_featured']);
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

    public function render_page() {
        $this->render_manager(true);
    }

    public function render_manager($wrap = false) {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $plans = VH360_Membership_Plans::get_plan_registry();
        $notice = get_transient('vh360_membership_plans_admin_notice');
        delete_transient('vh360_membership_plans_admin_notice');
        ?>
        <?php if ($wrap) : ?><div class="wrap"><?php endif; ?>
        <div class="vh360-membership-plans-admin">
            <h1><?php esc_html_e('Membership Plans', 'videohub360-memberships'); ?></h1>
            <p><?php esc_html_e('Manage every membership plan from one central registry. These plans power pricing displays, checkout routing, product grants, and access tiers.', 'videohub360-memberships'); ?></p>
            <?php if ($notice && !empty($notice['messages'])) : ?>
                <div class="notice notice-<?php echo esc_attr($notice['type']); ?>"><ul><?php foreach ($notice['messages'] as $message) : ?><li><?php echo esc_html($message); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="hidden" name="action" value="vh360_save_membership_plans" />
                <?php foreach ($plans as $key => $plan) : $this->render_plan_card($key, $plan); endforeach; ?>
                <?php $this->render_plan_card('new_plan', $this->get_empty_plan(), true); ?>
                <?php submit_button(__('Save Membership Plans', 'videohub360-memberships')); ?>
            </form>
        </div>
        <?php if ($wrap) : ?></div><?php endif; ?>
        <?php
    }

    private function get_empty_plan() {
        return array('id'=>'','name'=>'','label'=>'','description'=>'','plan_group'=>'','billing_type'=>'recurring','billing_interval'=>'monthly','price'=>'','currency'=>'USD','compare_at_price'=>'','savings_text'=>'','stripe_price_id'=>'','woocommerce_product_id'=>0,'features'=>array(),'tier_level'=>0,'is_featured'=>false,'is_enabled'=>false,'display_order'=>999,'button_text'=>__('Choose Plan','videohub360-memberships'),'checkout_behavior'=>'stripe');
    }

    private function render_plan_card($key, $plan, $is_new = false) {
        $field = $is_new ? 'new_plan' : 'plans[' . $key . ']';
        $features = !empty($plan['features']) && is_array($plan['features']) ? implode("\n", $plan['features']) : '';
        ?>
        <section class="vh360-plan-card">
            <?php if (!$is_new) : ?><input type="hidden" name="<?php echo esc_attr($field); ?>[original_plan_key]" value="<?php echo esc_attr($key); ?>" /><?php endif; ?>
            <div class="vh360-plan-card__header">
                <div><h2 style="margin:0;"><?php echo $is_new ? esc_html__('Add New Plan', 'videohub360-memberships') : esc_html($plan['name']); ?></h2><?php if (!$is_new) : ?><code><?php echo esc_html($key); ?></code><?php endif; ?></div>
                <?php if (!$is_new) : ?><span class="vh360-plan-pill"><?php echo !empty($plan['is_enabled']) ? esc_html__('Enabled', 'videohub360-memberships') : esc_html__('Disabled', 'videohub360-memberships'); ?></span><?php endif; ?>
            </div>
            <div class="vh360-plan-card__body">
                <div class="vh360-plan-grid">
                    <?php $this->field($field, 'id', __('Plan Key', 'videohub360-memberships'), $plan['id'], __('Required lowercase slug. Used by gates, checkout, member records, and integrations.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'name', __('Plan Name', 'videohub360-memberships'), $plan['name'], __('Required internal/admin name.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'label', __('Display Label', 'videohub360-memberships'), $plan['label'], __('Name shown on pricing cards and dashboards.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'plan_group', __('Plan Group', 'videohub360-memberships'), $plan['plan_group'], __('Connects monthly/yearly versions of the same plan for aligned pricing cards.', 'videohub360-memberships')); ?>
                    <?php $this->select($field, 'billing_type', __('Billing Type', 'videohub360-memberships'), $plan['billing_type'], VH360_Membership_Plans::get_allowed_billing_types(), __('Controls checkout behavior.', 'videohub360-memberships')); ?>
                    <?php $this->select($field, 'billing_interval', __('Billing Interval', 'videohub360-memberships'), $plan['billing_interval'], VH360_Membership_Plans::get_allowed_billing_intervals(), __('Controls frontend tab placement.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'price', __('Price', 'videohub360-memberships'), $plan['price'], __('Numeric amount for paid plans. Use 0 or empty for free plans.', 'videohub360-memberships'), 'number', '0.01'); ?>
                    <?php $this->field($field, 'currency', __('Currency', 'videohub360-memberships'), $plan['currency'], __('ISO currency code used for display.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'stripe_price_id', __('Stripe Price ID', 'videohub360-memberships'), $plan['stripe_price_id'], __('Required for enabled recurring Stripe Checkout plans.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'woocommerce_product_id', __('WooCommerce Product ID', 'videohub360-memberships'), $plan['woocommerce_product_id'], __('Required for WooCommerce checkout plans and must be a published product.', 'videohub360-memberships'), 'number'); ?>
                    <?php $this->select($field, 'checkout_behavior', __('Checkout Behavior', 'videohub360-memberships'), $plan['checkout_behavior'], VH360_Membership_Plans::get_allowed_checkout_behaviors(), __('Determines whether buttons use Stripe, product pages, add-to-cart, or free activation.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'tier_level', __('Access Tier', 'videohub360-memberships'), $plan['tier_level'], __('Higher numbers satisfy higher-tier access checks.', 'videohub360-memberships'), 'number'); ?>
                    <?php $this->field($field, 'display_order', __('Display Order', 'videohub360-memberships'), $plan['display_order'], __('Controls admin and frontend ordering.', 'videohub360-memberships'), 'number'); ?>
                    <?php $this->field($field, 'button_text', __('Button Text', 'videohub360-memberships'), $plan['button_text'], __('Pricing card call-to-action text.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'compare_at_price', __('Compare At Price', 'videohub360-memberships'), $plan['compare_at_price'], __('Optional crossed-out price text.', 'videohub360-memberships')); ?>
                    <?php $this->field($field, 'savings_text', __('Savings Text', 'videohub360-memberships'), $plan['savings_text'], __('Optional savings badge text.', 'videohub360-memberships')); ?>
                </div>
                <div class="vh360-plan-grid" style="margin-top:16px;">
                    <?php $this->textarea($field, 'description', __('Description', 'videohub360-memberships'), $plan['description'], __('Short pricing card description.', 'videohub360-memberships'), 3); ?>
                    <?php $this->textarea($field, 'features', __('Features', 'videohub360-memberships'), $features, __('One plain-text feature per line.', 'videohub360-memberships'), 5); ?>
                </div>
                <p>
                    <label><input type="checkbox" name="<?php echo esc_attr($field); ?>[is_enabled]" value="1" <?php checked(!empty($plan['is_enabled'])); ?> /> <?php esc_html_e('Enabled', 'videohub360-memberships'); ?></label>
                    &nbsp; <label><input type="checkbox" name="<?php echo esc_attr($field); ?>[is_featured]" value="1" <?php checked(!empty($plan['is_featured'])); ?> /> <?php esc_html_e('Featured / Recommended', 'videohub360-memberships'); ?></label>
                </p>
                <?php if (!$is_new) : ?><div class="vh360-plan-actions"><button class="button" type="submit" name="duplicate_plan" value="<?php echo esc_attr($key); ?>"><?php esc_html_e('Duplicate', 'videohub360-memberships'); ?></button><button class="button button-link-delete" type="submit" name="delete_plan" value="<?php echo esc_attr($key); ?>" data-vh360-delete-plan data-confirm="<?php esc_attr_e('Delete this membership plan?', 'videohub360-memberships'); ?>"><?php esc_html_e('Delete', 'videohub360-memberships'); ?></button></div><?php endif; ?>
            </div>
        </section>
        <?php
    }

    private function field($field, $key, $label, $value, $description, $type = 'text', $step = '') {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><input type="<?php echo esc_attr($type); ?>" <?php echo $step ? 'step="' . esc_attr($step) . '"' : ''; ?> name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" /><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }

    private function textarea($field, $key, $label, $value, $description, $rows = 4) {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><textarea rows="<?php echo esc_attr($rows); ?>" name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($value); ?></textarea><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }

    private function select($field, $key, $label, $value, $options, $description) {
        ?><div class="vh360-plan-field"><label><?php echo esc_html($label); ?></label><select name="<?php echo esc_attr($field); ?>[<?php echo esc_attr($key); ?>]">
            <?php foreach ($options as $option) : ?><option value="<?php echo esc_attr($option); ?>" <?php selected($value, $option); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $option))); ?></option><?php endforeach; ?>
        </select><p class="description"><?php echo esc_html($description); ?></p></div><?php
    }
}
