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
        add_action('admin_menu', array($this, 'register_page'));
        add_action('admin_post_vh360_save_membership_plans', array($this, 'handle_save'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_page() {
        add_submenu_page(
            'tools.php',
            __('Membership Plans', 'videohub360-memberships'),
            __('Membership Plans', 'videohub360-memberships'),
            self::CAPABILITY,
            'vh360-membership-plans',
            array($this, 'render_page')
        );
    }

    public function enqueue_assets($hook) {
        if (false === strpos((string) $hook, 'vh360-membership-plans')) {
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
        $next = array();
        $errors = array();

        foreach ($submitted as $row_key => $raw_plan) {
            $row_key = sanitize_key($row_key);
            if ($delete && $delete === $row_key) {
                continue;
            }
            $raw_plan = $this->prepare_raw_plan($raw_plan, $row_key);
            $validation = VH360_Membership_Plans::validate_plan($raw_plan, $next, $row_key);
            if (is_wp_error($validation)) {
                $errors = array_merge($errors, $validation->get_error_messages());
                $raw_plan['is_enabled'] = false;
            }
            $plan = VH360_Membership_Plans::normalize_plan($raw_plan, $row_key);
            if (!empty($plan['id'])) {
                $next[$plan['id']] = $plan;
            }

            if ($duplicate && $duplicate === $row_key) {
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
            }
        }

        if (!empty($_POST['new_plan']) && is_array($_POST['new_plan'])) {
            $raw = $this->prepare_raw_plan(wp_unslash($_POST['new_plan']));
            if (!empty($raw['id']) || !empty($raw['name']) || !empty($raw['label'])) {
                $validation = VH360_Membership_Plans::validate_plan($raw, $next, '');
                if (is_wp_error($validation)) {
                    $errors = array_merge($errors, $validation->get_error_messages());
                } else {
                    $plan = VH360_Membership_Plans::normalize_plan($raw);
                    $next[$plan['id']] = $plan;
                }
            }
        }

        VH360_Membership_Plans::save_plans($next);
        set_transient('vh360_membership_plans_admin_notice', array(
            'type' => empty($errors) ? 'success' : 'warning',
            'messages' => empty($errors) ? array(__('Membership plans saved.', 'videohub360-memberships')) : $errors,
        ), 60);

        wp_safe_redirect(add_query_arg('page', 'vh360-membership-plans', admin_url('tools.php')));
        exit;
    }

    private function prepare_raw_plan($raw, $fallback_key = '') {
        $raw = is_array($raw) ? $raw : array();
        $raw['id'] = !empty($raw['id']) ? sanitize_key($raw['id']) : $fallback_key;
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
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }
        $plans = VH360_Membership_Plans::get_plan_registry();
        $notice = get_transient('vh360_membership_plans_admin_notice');
        delete_transient('vh360_membership_plans_admin_notice');
        ?>
        <div class="wrap vh360-membership-plans-admin">
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
