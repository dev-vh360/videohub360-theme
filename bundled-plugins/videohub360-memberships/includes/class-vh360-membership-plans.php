<?php
/**
 * Membership Plans Registry
 *
 * Central registry for membership plan definitions, WooCommerce product mapping,
 * and recurring billing configuration.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Plans {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Plans
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Plans
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add meta boxes to products
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('save_post', array($this, 'save_product_meta'), 10, 2);
        add_action('admin_menu', array($this, 'add_repair_tool_page'));
        add_action('admin_post_vh360_repair_course_entitlements', array($this, 'handle_repair_course_entitlements'));
        add_action('admin_notices', array($this, 'render_admin_notices'));
        add_shortcode('vh360_pricing_toggle', array($this, 'render_pricing_toggle_shortcode'));
        add_action('admin_post_vh360_activate_free_plan', array($this, 'handle_activate_free_plan'));
    }
    


    /** Render dynamic, plan-group-aware pricing toggle from central plans. */
    public function render_pricing_toggle_shortcode($atts = array()) {
        wp_enqueue_style('vh360-pricing-toggle', VH360_MEMBERSHIPS_URL . 'assets/frontend/pricing-toggle.css', array(), VH360_MEMBERSHIPS_VERSION);
        wp_enqueue_script('vh360-pricing-toggle', VH360_MEMBERSHIPS_URL . 'assets/frontend/pricing-toggle.js', array(), VH360_MEMBERSHIPS_VERSION, true);

        $atts = shortcode_atts(array(
            'interval'      => '',
            'groups'        => '',
            'show_lifetime' => 'true',
            'show_free'     => 'true',
            'featured'      => '',
            'columns'       => '3',
            'button_style'  => 'primary',
        ), $atts, 'vh360_pricing_toggle');

        $plans = self::get_enabled_plans();
        $admin_view = current_user_can('manage_options');

        if ($atts['groups']) {
            $allowed = array_map('sanitize_key', array_map('trim', explode(',', $atts['groups'])));
            $plans = array_filter($plans, function($plan) use ($allowed) { return in_array($plan['plan_group'], $allowed, true); });
        }
        if ($atts['interval']) {
            $interval = sanitize_key($atts['interval']);
            $plans = array_filter($plans, function($plan) use ($interval) { return $plan['billing_interval'] === $interval; });
        }
        if ('true' !== $atts['show_lifetime']) {
            $plans = array_filter($plans, function($plan) { return !in_array($plan['billing_interval'], array('lifetime', 'one_time'), true); });
        }
        if ('true' !== $atts['show_free']) {
            $plans = array_filter($plans, function($plan) { return 'free' !== $plan['billing_type'] && 'free' !== $plan['billing_interval']; });
        }
        if ('true' === $atts['featured']) {
            $plans = array_filter($plans, function($plan) { return !empty($plan['is_featured']); });
        }

        $plans = array_filter($plans, function($plan) use ($admin_view) {
            return $admin_view || self::plan_is_frontend_ready($plan);
        });

        if (empty($plans)) {
            return $admin_view
                ? '<div class="vh360-pricing-empty">' . esc_html__('No enabled, checkout-ready membership plans are configured.', 'videohub360-memberships') . '</div>'
                : '<div class="vh360-pricing-empty">' . esc_html__('Membership plans are currently unavailable.', 'videohub360-memberships') . '</div>';
        }

        $interval_labels = array(
            'free'     => __('Free', 'videohub360-memberships'),
            'monthly'  => __('Monthly', 'videohub360-memberships'),
            'yearly'   => __('Yearly', 'videohub360-memberships'),
            'lifetime' => __('Lifetime', 'videohub360-memberships'),
            'one_time' => __('One-Time', 'videohub360-memberships'),
        );
        $groups = array();
        foreach ($plans as $key => $plan) {
            $group = $plan['plan_group'] ? $plan['plan_group'] : $key;
            if (!isset($groups[$group])) {
                $groups[$group] = array('order' => (int) $plan['display_order'], 'plans' => array());
            }
            $groups[$group]['order'] = min($groups[$group]['order'], (int) $plan['display_order']);
            $groups[$group]['plans'][$plan['billing_interval']][$key] = $plan;
        }
        uasort($groups, function($a, $b) { return $a['order'] <=> $b['order']; });

        $available_intervals = array();
        foreach ($interval_labels as $interval => $label) {
            foreach ($groups as $group) {
                if (!empty($group['plans'][$interval])) {
                    $available_intervals[$interval] = $label;
                    break;
                }
            }
        }

        $style_vars = self::get_pricing_style_attribute();

        ob_start(); ?>
        <section class="vh360-pricing-toggle" data-columns="<?php echo esc_attr(max(1, absint($atts['columns']))); ?>" style="<?php echo esc_attr($style_vars); ?>">
            <div class="vh360-pricing-tabs" role="tablist" aria-label="<?php esc_attr_e('Membership billing intervals', 'videohub360-memberships'); ?>">
                <?php $first = true; foreach ($available_intervals as $interval => $label) : ?>
                    <button type="button" class="vh360-pricing-tab <?php echo $first ? 'is-active' : ''; ?>" role="tab" tabindex="<?php echo $first ? '0' : '-1'; ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="vh360-pricing-<?php echo esc_attr($interval); ?>" data-vh360-pricing-tab><?php echo esc_html($label); ?></button>
                <?php $first = false; endforeach; ?>
            </div>
            <?php $first = true; foreach ($available_intervals as $interval => $label) : ?>
                <div id="vh360-pricing-<?php echo esc_attr($interval); ?>" class="vh360-pricing-panel <?php echo $first ? 'is-active' : ''; ?>" role="tabpanel" <?php echo $first ? '' : 'hidden'; ?>>
                    <div class="vh360-pricing-grid">
                        <?php foreach ($groups as $group_key => $group) : if (empty($group['plans'][$interval])) continue; foreach ($group['plans'][$interval] as $key => $plan) : echo $this->render_pricing_card($plan, $atts['button_style'], $admin_view); endforeach; endforeach; ?>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>
        </section>
        <?php return ob_get_clean();
    }

    public static function get_pricing_style_defaults() {
        return array(
            'pricing_card_background_color' => '#ffffff',
            'pricing_card_border_color' => '#e5e7eb',
            'pricing_card_text_color' => '#4b5563',
            'pricing_card_title_color' => '#111827',
            'pricing_card_price_color' => '#111827',
            'pricing_card_description_color' => '#6b7280',
            'pricing_card_feature_text_color' => '#4b5563',
            'pricing_card_button_background_color' => '#2563eb',
            'pricing_card_button_text_color' => '#ffffff',
            'pricing_card_button_hover_background_color' => '#1d4ed8',
            'pricing_card_featured_border_color' => '#2563eb',
            'pricing_card_featured_badge_background_color' => '#dbeafe',
            'pricing_card_featured_badge_text_color' => '#1d4ed8',
            'pricing_toggle_active_background_color' => '#2563eb',
            'pricing_toggle_active_text_color' => '#ffffff',
            'pricing_toggle_inactive_background_color' => '#ffffff',
            'pricing_toggle_inactive_text_color' => '#1f2937',
        );
    }

    private static function get_pricing_style_attribute() {
        $options = get_option('vh360_membership_options', array());
        $options = is_array($options) ? $options : array();
        $map = array(
            'pricing_card_background_color' => '--vh360-pricing-card-bg',
            'pricing_card_border_color' => '--vh360-pricing-card-border',
            'pricing_card_text_color' => '--vh360-pricing-text-color',
            'pricing_card_title_color' => '--vh360-pricing-title-color',
            'pricing_card_price_color' => '--vh360-pricing-price-color',
            'pricing_card_description_color' => '--vh360-pricing-description-color',
            'pricing_card_feature_text_color' => '--vh360-pricing-feature-color',
            'pricing_card_button_background_color' => '--vh360-pricing-button-bg',
            'pricing_card_button_text_color' => '--vh360-pricing-button-color',
            'pricing_card_button_hover_background_color' => '--vh360-pricing-button-hover-bg',
            'pricing_card_featured_border_color' => '--vh360-pricing-featured-border',
            'pricing_card_featured_badge_background_color' => '--vh360-pricing-badge-bg',
            'pricing_card_featured_badge_text_color' => '--vh360-pricing-badge-color',
            'pricing_toggle_active_background_color' => '--vh360-pricing-tab-active-bg',
            'pricing_toggle_active_text_color' => '--vh360-pricing-tab-active-color',
            'pricing_toggle_inactive_background_color' => '--vh360-pricing-tab-bg',
            'pricing_toggle_inactive_text_color' => '--vh360-pricing-tab-color',
        );
        $defaults = self::get_pricing_style_defaults();
        $styles = array();
        foreach ($map as $option_key => $css_var) {
            $value = !empty($options[$option_key]) ? sanitize_hex_color($options[$option_key]) : '';
            if (!$value) {
                $value = $defaults[$option_key];
            }
            $styles[] = $css_var . ': ' . $value;
        }
        return implode('; ', $styles) . ';';
    }

    private function render_pricing_card($plan, $button_style, $admin_view = false) {
        $url = self::get_plan_button_url($plan['id']);
        if (!$url && !$admin_view) {
            return '';
        }
        ob_start(); ?>
        <article class="vh360-pricing-card <?php echo !empty($plan['is_featured']) ? 'is-featured' : ''; ?>">
            <?php if (!empty($plan['is_featured'])) : ?><div class="vh360-pricing-badge"><?php esc_html_e('Recommended', 'videohub360-memberships'); ?></div><?php endif; ?>
            <h3 class="vh360-pricing-card-title"><?php echo esc_html($plan['label']); ?></h3>
            <?php if ($plan['description']) : ?><p class="vh360-pricing-description"><?php echo esc_html($plan['description']); ?></p><?php endif; ?>
            <div class="vh360-pricing-price"><?php echo esc_html($plan['display_price']); ?></div>
            <?php if ($plan['compare_at_price']) : ?><div class="vh360-pricing-compare"><?php echo esc_html($plan['compare_at_price']); ?></div><?php endif; ?>
            <?php if ($plan['savings_text']) : ?><div class="vh360-pricing-savings"><?php echo esc_html($plan['savings_text']); ?></div><?php endif; ?>
            <?php if ($plan['features']) : ?><ul class="vh360-pricing-features"><?php foreach ($plan['features'] as $feature) : ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($url) : ?><a class="vh360-pricing-button vh360-button-<?php echo esc_attr(sanitize_html_class($button_style)); ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($plan['button_text']); ?></a><?php elseif ($admin_view) : ?><p class="vh360-plan-warning"><?php esc_html_e('This plan is enabled but needs checkout settings before visitors can select it.', 'videohub360-memberships'); ?></p><?php endif; ?>
        </article>
        <?php return ob_get_clean();
    }

    public static function get_plan_button_url($plan_key) {
        $plan = self::get_plan($plan_key);
        if (!$plan || empty($plan['is_enabled']) || !self::plan_is_frontend_ready($plan)) {
            return '';
        }
        if ('free' === $plan['billing_type']) {
            $url = is_user_logged_in() ? wp_nonce_url(admin_url('admin-post.php?action=vh360_activate_free_plan&plan=' . rawurlencode($plan['id'])), 'vh360_activate_free_plan_' . $plan['id']) : (function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url());
            return add_query_arg('vh360_plan', $plan['id'], $url);
        }
        if (in_array($plan['checkout_behavior'], array('woocommerce', 'product_page', 'add_to_cart'), true)) {
            $product_id = self::get_product_id_for_plan($plan['id']);
            if (!$product_id) {
                return '';
            }
            if ('add_to_cart' === $plan['checkout_behavior'] && function_exists('wc_get_cart_url')) {
                return add_query_arg('add-to-cart', $product_id, wc_get_cart_url());
            }
            return get_permalink($product_id);
        }
        if ('recurring' === $plan['billing_type'] && 'stripe' === $plan['checkout_behavior']) {
            if (is_user_logged_in() && function_exists('vh360_membership_get_dashboard_plan_url')) {
                return vh360_membership_get_dashboard_plan_url($plan['id'], true);
            }
            if (function_exists('vh360_membership_get_recurring_register_url')) {
                return vh360_membership_get_recurring_register_url($plan['id']);
            }
            return add_query_arg('vh360_plan', $plan['id'], function_exists('vh360_get_register_page_url') ? vh360_get_register_page_url() : wp_registration_url());
        }
        return '';
    }

    public static function plan_is_frontend_ready($plan) {
        $plan = self::normalize_plan($plan);
        if (empty($plan['is_enabled'])) {
            return false;
        }
        if ('free' === $plan['billing_type']) {
            return true;
        }
        if ('recurring' === $plan['billing_type'] || 'stripe' === $plan['checkout_behavior']) {
            return 'stripe' === $plan['checkout_behavior'] && !empty($plan['stripe_price_id']);
        }
        if (in_array($plan['checkout_behavior'], array('woocommerce', 'product_page', 'add_to_cart'), true)) {
            $product_id = self::get_product_id_for_plan($plan['id']);
            return $product_id && self::is_valid_woocommerce_product($product_id);
        }
        return false;
    }

    public function handle_activate_free_plan() {
        $plan_key = isset($_GET['plan']) ? sanitize_key(wp_unslash($_GET['plan'])) : '';
        if (!is_user_logged_in() || !$plan_key || !wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'vh360_activate_free_plan_' . $plan_key)) {
            wp_die(esc_html__('Invalid free plan activation request.', 'videohub360-memberships'));
        }
        $plan = self::get_plan($plan_key);
        if (!$plan || 'free' !== $plan['billing_type']) {
            wp_die(esc_html__('This plan cannot be activated for free.', 'videohub360-memberships'));
        }
        VH360_Membership_API::get_instance()->create_membership(get_current_user_id(), $plan_key, 0, 'lifetime', null);
        wp_safe_redirect(function_exists('vh360_get_dashboard_page_url') ? vh360_get_dashboard_page_url() : home_url('/'));
        exit;
    }

    /**
     * Render saved admin notices for product mapping validation.
     */
    public function render_admin_notices() {
        $notice = get_transient('vh360_membership_mapping_notice');
        if (!$notice) {
            return;
        }
        delete_transient('vh360_membership_mapping_notice');
        ?>
        <div class="notice notice-warning is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
        <?php
    }

    /**
     * Option name for custom membership plans.
     */
    const PLANS_OPTION = 'vh360_membership_plans';

    public static function get_allowed_billing_types() {
        return array('recurring', 'one_time', 'lifetime', 'free');
    }

    public static function get_allowed_billing_intervals() {
        return array('monthly', 'yearly', 'lifetime', 'one_time', 'free');
    }

    public static function get_allowed_checkout_behaviors() {
        return array('stripe', 'woocommerce', 'product_page', 'add_to_cart', 'free');
    }

    public static function is_valid_woocommerce_product($product_id) {
        $product_id = absint($product_id);
        if (!$product_id || 'product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id)) {
            return false;
        }
        return !function_exists('wc_get_product') || (bool) wc_get_product($product_id);
    }

    public static function validate_plan($plan, $existing_plans = array(), $current_key = '') {
        $errors = new WP_Error();
        $raw_id = isset($plan['id']) ? (string) $plan['id'] : '';
        $id = sanitize_key($raw_id);
        $name = isset($plan['name']) ? trim((string) $plan['name']) : '';
        $label = isset($plan['label']) ? trim((string) $plan['label']) : '';
        $billing_type = isset($plan['billing_type']) ? sanitize_key($plan['billing_type']) : '';
        $billing_interval = isset($plan['billing_interval']) ? sanitize_key($plan['billing_interval']) : '';
        $checkout_behavior = isset($plan['checkout_behavior']) ? sanitize_key($plan['checkout_behavior']) : '';
        $is_enabled = !empty($plan['is_enabled']);
        $price = isset($plan['price']) ? trim((string) $plan['price']) : '';
        $plan_label = $name ?: ($label ?: ($id ?: __('New plan', 'videohub360-memberships')));

        if (!$id) {
            $errors->add('plan_key_required', sprintf(__('Plan key is required for %s.', 'videohub360-memberships'), $plan_label));
        } elseif ($raw_id !== $id || !preg_match('/^[a-z0-9_]+$/', $id)) {
            $errors->add('plan_key_format', sprintf(__('Plan key "%s" must be a lowercase slug using letters, numbers, and underscores.', 'videohub360-memberships'), $raw_id));
        } elseif ($id !== $current_key && isset($existing_plans[$id])) {
            $errors->add('plan_key_unique', sprintf(__('Plan key "%s" is already in use.', 'videohub360-memberships'), $id));
        }

        if (!$name && !$label) {
            $errors->add('plan_name_required', sprintf(__('Plan name or display label is required for %s.', 'videohub360-memberships'), $plan_label));
        }
        if (!in_array($billing_type, self::get_allowed_billing_types(), true)) {
            $errors->add('billing_type_invalid', sprintf(__('Billing type is invalid for %s.', 'videohub360-memberships'), $plan_label));
        }
        if (!in_array($billing_interval, self::get_allowed_billing_intervals(), true)) {
            $errors->add('billing_interval_invalid', sprintf(__('Billing interval is invalid for %s.', 'videohub360-memberships'), $plan_label));
        }
        if (!in_array($checkout_behavior, self::get_allowed_checkout_behaviors(), true)) {
            $errors->add('checkout_behavior_invalid', sprintf(__('Checkout behavior is invalid for %s.', 'videohub360-memberships'), $plan_label));
        }
        if ('free' === $billing_type) {
            if ($price !== '' && (float) $price > 0) {
                $errors->add('free_price_invalid', sprintf(__('Free plan %s must have an empty or zero price.', 'videohub360-memberships'), $plan_label));
            }
        } elseif ($price === '' || !is_numeric($price)) {
            $errors->add('paid_price_invalid', sprintf(__('Paid plan %s requires a numeric price.', 'videohub360-memberships'), $plan_label));
        }
        if (isset($plan['tier_level']) && $plan['tier_level'] !== '' && !is_numeric($plan['tier_level'])) {
            $errors->add('tier_invalid', sprintf(__('Access tier must be numeric for %s.', 'videohub360-memberships'), $plan_label));
        }
        if (isset($plan['display_order']) && $plan['display_order'] !== '' && !is_numeric($plan['display_order'])) {
            $errors->add('order_invalid', sprintf(__('Display order must be numeric for %s.', 'videohub360-memberships'), $plan_label));
        }
        if ($is_enabled && 'recurring' === $billing_type && 'stripe' === $checkout_behavior && empty($plan['stripe_price_id'])) {
            $errors->add('stripe_required', sprintf(__('Enabled recurring Stripe plan %s requires a Stripe Price ID.', 'videohub360-memberships'), $plan_label));
        }
        if ($is_enabled && in_array($checkout_behavior, array('woocommerce', 'product_page', 'add_to_cart'), true)) {
            $product_id = isset($plan['woocommerce_product_id']) ? absint($plan['woocommerce_product_id']) : 0;
            if (!self::is_valid_woocommerce_product($product_id)) {
                $errors->add('product_invalid', sprintf(__('Enabled WooCommerce plan %s requires a valid published product ID.', 'videohub360-memberships'), $plan_label));
            }
        }
        if ($is_enabled && 'recurring' === $billing_type && in_array($billing_interval, array('monthly', 'yearly'), true) && empty($plan['plan_group'])) {
            $errors->add('plan_group_required', sprintf(__('Recurring monthly/yearly plan %s requires a Plan Group.', 'videohub360-memberships'), $plan_label));
        }

        return $errors->has_errors() ? $errors : true;
    }

    /**
     * Get the central custom plan registry.
     *
     * @return array
     */
    public static function get_plan_registry() {
        $plans = get_option(self::PLANS_OPTION, array());
        $plans = is_array($plans) ? $plans : array();

        foreach ($plans as $key => $plan) {
            $plan = self::normalize_plan($plan, $key);
            if (empty($plan['id'])) {
                unset($plans[$key]);
                continue;
            }
            unset($plans[$key]);
            $plans[$plan['id']] = $plan;
        }

        uasort($plans, array(__CLASS__, 'sort_plans'));
        return apply_filters('vh360_membership_plans', $plans);
    }

    /** Create optional editable sample plans only when an administrator explicitly requests them. */
    public static function maybe_seed_default_plans() {
        $existing = get_option(self::PLANS_OPTION, null);
        if (is_array($existing) && !empty($existing)) {
            return;
        }

        $now = current_time('mysql');
        $defaults = array(
            'free_fan' => array('name'=>'Free Fan','label'=>'Free Fan','description'=>'Start watching free member content.','plan_group'=>'fan','billing_type'=>'free','billing_interval'=>'free','price'=>'0','currency'=>'USD','features'=>array('Free community access','Public videos','Basic profile'),'tier_level'=>0,'is_featured'=>false,'is_enabled'=>false,'display_order'=>10,'button_text'=>'Join Free','checkout_behavior'=>'free'),
            'creator_monthly' => array('name'=>'Creator Monthly','label'=>'Creator','description'=>'Monthly access for creators.','plan_group'=>'creator','billing_type'=>'recurring','billing_interval'=>'monthly','price'=>'19','currency'=>'USD','features'=>array('Creator tools','Member content','Community access'),'tier_level'=>10,'is_featured'=>false,'is_enabled'=>false,'display_order'=>20,'button_text'=>'Start Monthly','checkout_behavior'=>'stripe'),
            'creator_yearly' => array('name'=>'Creator Yearly','label'=>'Creator','description'=>'Annual creator access.','plan_group'=>'creator','billing_type'=>'recurring','billing_interval'=>'yearly','price'=>'190','currency'=>'USD','savings_text'=>'Save 17%','features'=>array('Creator tools','Member content','Community access'),'tier_level'=>10,'is_featured'=>false,'is_enabled'=>false,'display_order'=>30,'button_text'=>'Start Yearly','checkout_behavior'=>'stripe'),
            'pro_monthly' => array('name'=>'Pro Monthly','label'=>'Pro','description'=>'Monthly access for professionals.','plan_group'=>'pro','billing_type'=>'recurring','billing_interval'=>'monthly','price'=>'39','currency'=>'USD','features'=>array('All creator features','Advanced tools','Priority support'),'tier_level'=>20,'is_featured'=>true,'is_enabled'=>false,'display_order'=>40,'button_text'=>'Go Pro','checkout_behavior'=>'stripe'),
            'pro_yearly' => array('name'=>'Pro Yearly','label'=>'Pro','description'=>'Annual pro access.','plan_group'=>'pro','billing_type'=>'recurring','billing_interval'=>'yearly','price'=>'390','currency'=>'USD','savings_text'=>'Save 17%','features'=>array('All creator features','Advanced tools','Priority support'),'tier_level'=>20,'is_featured'=>true,'is_enabled'=>false,'display_order'=>50,'button_text'=>'Go Pro Yearly','checkout_behavior'=>'stripe'),
            'lifetime' => array('name'=>'Lifetime','label'=>'Lifetime','description'=>'One payment for lifetime access.','plan_group'=>'lifetime','billing_type'=>'lifetime','billing_interval'=>'lifetime','price'=>'499','currency'=>'USD','features'=>array('Lifetime membership','All pro features','No renewal'),'tier_level'=>30,'is_featured'=>false,'is_enabled'=>false,'display_order'=>60,'button_text'=>'Get Lifetime','checkout_behavior'=>'woocommerce'),
        );
        foreach ($defaults as $key => $plan) {
            $defaults[$key]['id'] = $key;
            $defaults[$key]['plan_key'] = $key;
            $defaults[$key]['compare_at_price'] = '';
            $defaults[$key]['stripe_price_id'] = '';
            $defaults[$key]['woocommerce_product_id'] = 0;
            $defaults[$key]['created_at'] = $now;
            $defaults[$key]['updated_at'] = $now;
        }
        update_option(self::PLANS_OPTION, $defaults, false);
    }

    public static function sort_plans($a, $b) {
        $order = (int) $a['display_order'] <=> (int) $b['display_order'];
        return $order ?: strcasecmp($a['name'], $b['name']);
    }

    public static function normalize_plan($plan, $fallback_key = '') {
        $plan = is_array($plan) ? $plan : array();
        $id = !empty($plan['id']) ? $plan['id'] : (!empty($plan['plan_key']) ? $plan['plan_key'] : $fallback_key);
        $id = sanitize_key($id);
        $billing_interval = isset($plan['billing_interval']) ? sanitize_key($plan['billing_interval']) : (isset($plan['duration_unit']) && 'lifetime' === $plan['duration_unit'] ? 'lifetime' : 'monthly');
        if (isset($plan['billing_mode']) && empty($plan['billing_type'])) {
            $plan['billing_type'] = $plan['billing_mode'];
        }
        $billing_type = isset($plan['billing_type']) ? sanitize_key($plan['billing_type']) : 'one_time';
        $allowed_intervals = array('monthly','yearly','lifetime','one_time','free');
        $allowed_types = array('recurring','one_time','lifetime','free');
        $features = isset($plan['features']) ? $plan['features'] : (isset($plan['display_features']) ? $plan['display_features'] : array());
        if (!is_array($features)) {
            $features = preg_split('/\r\n|\r|\n/', (string) $features);
        }
        $name = isset($plan['name']) ? $plan['name'] : (isset($plan['label']) ? $plan['label'] : $id);
        $label = isset($plan['label']) ? $plan['label'] : $name;
        $enabled = array_key_exists('is_enabled', $plan) ? (bool) $plan['is_enabled'] : (!array_key_exists('enabled', $plan) || (bool) $plan['enabled']);
        $featured = array_key_exists('is_featured', $plan) ? (bool) $plan['is_featured'] : (!empty($plan['featured']));
        return array(
            'id'=>$id,'plan_key'=>$id,'name'=>sanitize_text_field($name),'label'=>sanitize_text_field($label),
            'description'=>isset($plan['description']) ? sanitize_textarea_field($plan['description']) : (isset($plan['display_description']) ? sanitize_textarea_field($plan['display_description']) : ''),
            'plan_group'=>isset($plan['plan_group']) ? sanitize_key($plan['plan_group']) : $id,
            'billing_type'=>in_array($billing_type,$allowed_types,true)?$billing_type:'one_time',
            'billing_interval'=>in_array($billing_interval,$allowed_intervals,true)?$billing_interval:'monthly',
            'price'=>isset($plan['price']) && $plan['price'] !== '' ? (string) (float) $plan['price'] : '',
            'currency'=>isset($plan['currency']) ? strtoupper(sanitize_text_field($plan['currency'])) : 'USD',
            'compare_at_price'=>isset($plan['compare_at_price']) ? sanitize_text_field($plan['compare_at_price']) : '',
            'savings_text'=>isset($plan['savings_text']) ? sanitize_text_field($plan['savings_text']) : '',
            'stripe_price_id'=>isset($plan['stripe_price_id']) ? sanitize_text_field($plan['stripe_price_id']) : '',
            'woocommerce_product_id'=>isset($plan['woocommerce_product_id']) ? absint($plan['woocommerce_product_id']) : 0,
            'features'=>array_values(array_filter(array_map('sanitize_text_field',$features))),
            'tier_level'=>isset($plan['tier_level']) ? absint($plan['tier_level']) : 0,
            'is_featured'=>$featured,'is_enabled'=>$enabled,
            'display_order'=>isset($plan['display_order']) ? absint($plan['display_order']) : 999,
            'button_text'=>isset($plan['button_text']) ? sanitize_text_field($plan['button_text']) : __('Choose Plan','videohub360-memberships'),
            'checkout_behavior'=>self::normalize_checkout_behavior(isset($plan['checkout_behavior']) ? sanitize_key($plan['checkout_behavior']) : '', $billing_type),
            'created_at'=>isset($plan['created_at']) ? sanitize_text_field($plan['created_at']) : current_time('mysql'),
            'updated_at'=>isset($plan['updated_at']) ? sanitize_text_field($plan['updated_at']) : current_time('mysql'),
            // Compatibility aliases for existing membership queries.
            'enabled'=>$enabled,'featured'=>$featured,'billing_mode'=>$billing_type === 'recurring' ? 'recurring' : 'one_time',
            'display_label'=>sanitize_text_field($label),'display_price'=>self::format_price_text($plan),'display_description'=>isset($plan['description']) ? sanitize_textarea_field($plan['description']) : '',
            'display_features'=>array_values(array_filter(array_map('sanitize_text_field',$features))),
            'duration'=>self::interval_duration($billing_interval),'duration_unit'=>self::interval_duration_unit($billing_interval),
            'auto_renew'=>$billing_type === 'recurring','trial_days'=>isset($plan['trial_days']) ? absint($plan['trial_days']) : 0,
            'upgrade_eligible'=>array_key_exists('upgrade_eligible',$plan)?(bool)$plan['upgrade_eligible']:true,
            'downgrade_eligible'=>!empty($plan['downgrade_eligible']),
        );
    }

    private static function normalize_checkout_behavior($behavior, $billing_type) {
        if (in_array($behavior, self::get_allowed_checkout_behaviors(), true)) {
            return $behavior;
        }
        return $billing_type === 'recurring' ? 'stripe' : ($billing_type === 'free' ? 'free' : 'woocommerce');
    }

    private static function format_price_text($plan) {
        if (!empty($plan['display_price'])) return sanitize_text_field($plan['display_price']);
        if (isset($plan['billing_type']) && 'free' === $plan['billing_type']) return __('Free','videohub360-memberships');
        if (!isset($plan['price']) || $plan['price'] === '') return '';
        $suffix = !empty($plan['billing_interval']) && in_array($plan['billing_interval'], array('monthly','yearly'), true) ? '/' . ('monthly' === $plan['billing_interval'] ? __('mo','videohub360-memberships') : __('yr','videohub360-memberships')) : '';
        return sprintf('%s%s%s', '$', rtrim(rtrim(number_format((float)$plan['price'], 2), '0'), '.'), $suffix);
    }
    private static function interval_duration($interval) { return 'yearly' === $interval ? 1 : ('monthly' === $interval ? 1 : 0); }
    private static function interval_duration_unit($interval) { return 'yearly' === $interval ? 'years' : ('monthly' === $interval ? 'months' : ('lifetime' === $interval ? 'lifetime' : 'days')); }

    public static function get_plan($plan_key) { $plans = self::get_plan_registry(); $plan_key = sanitize_key($plan_key); return isset($plans[$plan_key]) ? $plans[$plan_key] : false; }
    public static function get_plan_billing_config($plan_key) { $plan = self::get_plan($plan_key); return $plan ? array('billing_mode'=>$plan['billing_mode'],'billing_type'=>$plan['billing_type'],'billing_interval'=>$plan['billing_interval'],'stripe_price_id'=>$plan['stripe_price_id'],'auto_renew'=>$plan['auto_renew'],'trial_days'=>$plan['trial_days']) : false; }
    public static function get_recurring_plans() { return array_filter(self::get_plan_registry(), function($p){ return 'recurring' === $p['billing_type']; }); }
    public static function get_enabled_plans() { return array_filter(self::get_plan_registry(), function($p){ return !empty($p['is_enabled']); }); }
    public static function get_plan_tier($plan_key) { $p = self::get_plan($plan_key); return $p ? (int)$p['tier_level'] : 0; }
    private static function get_default_plan_tier($plan_key) { return self::get_plan_tier($plan_key); }
    public static function is_woocommerce_eligible_plan($plan) { return !isset($plan['billing_type']) || !in_array($plan['billing_type'], array('recurring','free'), true); }
    public static function get_plan_key_by_stripe_price($stripe_price_id) { foreach (self::get_plan_registry() as $key=>$plan) { if (!empty($plan['stripe_price_id']) && $plan['stripe_price_id'] === $stripe_price_id) return $key; } return false; }
    public static function save_plan_config($config) { return self::save_plans($config); }
    public static function save_plans($plans) { $sanitized = array(); foreach ((array)$plans as $key=>$plan) { $plan['id'] = !empty($plan['id']) ? $plan['id'] : $key; $p = self::normalize_plan($plan, $key); if ($p['id']) $sanitized[$p['id']] = $p; } return update_option(self::PLANS_OPTION, $sanitized, false); }
    public static function save_plan($plan_data) { $plans = self::get_plan_registry(); $plan = self::normalize_plan($plan_data); if (!$plan['id']) return false; $validation = self::validate_plan($plan, $plans, isset($plan_data['id']) ? sanitize_key($plan_data['id']) : ''); if (is_wp_error($validation)) return $validation; $plans[$plan['id']] = $plan; return self::save_plans($plans); }
    public static function delete_plan($plan_key) { $plans = self::get_plan_registry(); $plan_key = sanitize_key($plan_key); unset($plans[$plan_key]); return self::save_plans($plans); }
    public static function get_plans_by_interval($interval) { $interval=sanitize_key($interval); return array_filter(self::get_plan_registry(), function($p) use ($interval){ return $p['billing_interval']===$interval; }); }
    public static function get_plans_by_group() { $groups=array(); foreach(self::get_plan_registry() as $key=>$plan){ $groups[$plan['plan_group']][$key]=$plan; } return $groups; }
    public static function get_featured_plan() { foreach(self::get_enabled_plans() as $p){ if(!empty($p['is_featured'])) return $p; } return false; }
    public static function get_plan_by_product_id($product_id) { $product_id = absint($product_id); foreach (self::get_plan_registry() as $plan) { if (!empty($plan['woocommerce_product_id']) && absint($plan['woocommerce_product_id']) === $product_id) return $plan; } return false; }
    public static function get_product_id_for_plan($plan_key) { $plan = self::get_plan($plan_key); return $plan && !empty($plan['woocommerce_product_id']) ? absint($plan['woocommerce_product_id']) : 0; }
    public static function get_woocommerce_product_plan_map() { $map = array(); foreach (self::get_plan_registry() as $key => $plan) { if (!empty($plan['woocommerce_product_id'])) $map[absint($plan['woocommerce_product_id'])] = $key; } return $map; }

    /**
     * Get membership mapping for a product
     *
     * @param int $product_id Product ID
     * @return array|false Membership mapping data or false if not set
     */
    public static function get_product_membership_mapping($product_id) {
        $registry_plan = self::get_plan_by_product_id($product_id);
        $plan_key = $registry_plan ? $registry_plan['id'] : '';

        // Product-side meta remains a fallback only; central Membership Plans mapping has priority.
        if (empty($plan_key)) {
            $plan_key = sanitize_key(get_post_meta($product_id, '_vh360_membership_plan', true));
            if (empty($plan_key)) {
                return false;
            }

            $registry_plan = self::get_plan($plan_key);
            if (!$registry_plan) {
                return false;
            }
        }

        if (empty($plan_key) || !$registry_plan) {
            return false;
        }

        $duration = (int) get_post_meta($product_id, '_vh360_membership_duration', true);
        $duration_unit = get_post_meta($product_id, '_vh360_membership_duration_unit', true);
        $grant_type = get_post_meta($product_id, '_vh360_membership_grant_type', true);

        if (!$duration && $registry_plan) {
            $duration = (int) $registry_plan['duration'];
        }
        if (empty($duration_unit) && $registry_plan) {
            $duration_unit = $registry_plan['duration_unit'];
        }
        
        if (empty($duration_unit)) {
            $duration_unit = 'days';
        }
        
        if (empty($grant_type)) {
            $grant_type = 'grant';
        }
        
        return array(
            'plan_key' => $plan_key,
            'duration' => $duration,
            'duration_unit' => $duration_unit,
            'grant_type' => $grant_type,
        );
    }
    
    /**
     * Add meta box to products
     */
    public function add_product_meta_box() {
        add_meta_box(
            'vh360_membership_mapping',
            __('VH360 Membership Mapping', 'videohub360-memberships'),
            array($this, 'render_meta_box'),
            'product',
            'side',
            'default'
        );

        add_meta_box(
            'vh360_course_access_mapping',
            __('VH360 Course Access', 'videohub360-memberships'),
            array($this, 'render_course_access_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box($post) {
        wp_nonce_field('vh360_membership_mapping', 'vh360_membership_mapping_nonce');
        
        $plan_key = get_post_meta($post->ID, '_vh360_membership_plan', true);
        $duration = get_post_meta($post->ID, '_vh360_membership_duration', true);
        $duration_unit = get_post_meta($post->ID, '_vh360_membership_duration_unit', true);
        $grant_type = get_post_meta($post->ID, '_vh360_membership_grant_type', true);
        
        if (empty($duration_unit)) {
            $duration_unit = 'days';
        }
        
        if (empty($grant_type)) {
            $grant_type = 'grant';
        }
        
        $plans = self::get_plan_registry();
        $registry_plan_for_product = self::get_plan_by_product_id($post->ID);
        $stale_product_plan_key = ($plan_key && !$registry_plan_for_product && empty($plans[$plan_key])) ? sanitize_key($plan_key) : '';
        
        ?>
        <div class="vh360-membership-mapping">
            <?php if ($stale_product_plan_key) : ?>
                <p class="description" style="color:#b32d2e;"><strong><?php esc_html_e('Outdated mapping:', 'videohub360-memberships'); ?></strong> <?php esc_html_e('This product has a fallback membership plan mapping that no longer exists. Manage product-plan assignments from VH360 Theme → Paid Memberships → Membership Plans.', 'videohub360-memberships'); ?></p>
            <?php endif; ?>
            <?php if ($registry_plan_for_product) : ?>
                <p class="description"><strong><?php esc_html_e('Plan Manager:', 'videohub360-memberships'); ?></strong> <?php printf(esc_html__('This product is assigned to %s in the Membership Plans Manager. That central assignment has priority over the fallback product mapping below.', 'videohub360-memberships'), esc_html($registry_plan_for_product['label'])); ?></p>
                <?php if (class_exists('VH360_Membership_Plans_Admin')) : ?>
                    <p class="description"><a href="<?php echo esc_url(VH360_Membership_Plans_Admin::get_admin_url()); ?>"><?php esc_html_e('Manage this relationship in VH360 Theme → Paid Memberships → Membership Plans.', 'videohub360-memberships'); ?></a></p>
                <?php endif; ?>
            <?php endif; ?>
            <p>
                <label for="vh360_membership_plan">
                    <strong><?php esc_html_e('Fallback Membership Plan:', 'videohub360-memberships'); ?></strong>
                </label>
                <select name="vh360_membership_plan" id="vh360_membership_plan" style="width: 100%;">
                    <option value=""><?php esc_html_e('None (Regular Product)', 'videohub360-memberships'); ?></option>
                    <?php foreach ($plans as $key => $plan) : ?>
                        <?php
                        $is_recurring_plan = isset($plan['billing_mode']) && $plan['billing_mode'] === 'recurring';
                        $allow_recurring_mapping = $is_recurring_plan ? (bool) apply_filters('vh360_allow_recurring_plan_woocommerce_mapping', false, $key, $post->ID) : true;
                        if ($is_recurring_plan && !$allow_recurring_mapping) {
                            continue;
                        }
                        ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($plan_key, $key); ?>>
                            <?php echo esc_html($plan['label']); ?><?php echo $is_recurring_plan ? esc_html__(' (Recurring - override enabled)', 'videohub360-memberships') : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label for="vh360_membership_duration">
                    <strong><?php esc_html_e('Duration:', 'videohub360-memberships'); ?></strong>
                </label>
                <input type="number" name="vh360_membership_duration" id="vh360_membership_duration" value="<?php echo esc_attr($duration); ?>" min="0" style="width: 70px;"/>
                <select name="vh360_membership_duration_unit" id="vh360_membership_duration_unit">
                    <option value="days" <?php selected($duration_unit, 'days'); ?>><?php esc_html_e('Days', 'videohub360-memberships'); ?></option>
                    <option value="months" <?php selected($duration_unit, 'months'); ?>><?php esc_html_e('Months', 'videohub360-memberships'); ?></option>
                    <option value="years" <?php selected($duration_unit, 'years'); ?>><?php esc_html_e('Years', 'videohub360-memberships'); ?></option>
                    <option value="lifetime" <?php selected($duration_unit, 'lifetime'); ?>><?php esc_html_e('Lifetime', 'videohub360-memberships'); ?></option>
                </select>
            </p>
            
            <p>
                <label for="vh360_membership_grant_type">
                    <strong><?php esc_html_e('Grant Type:', 'videohub360-memberships'); ?></strong>
                </label>
                <select name="vh360_membership_grant_type" id="vh360_membership_grant_type" style="width: 100%;">
                    <option value="grant" <?php selected($grant_type, 'grant'); ?>><?php esc_html_e('Grant New', 'videohub360-memberships'); ?></option>
                    <option value="extend" <?php selected($grant_type, 'extend'); ?>><?php esc_html_e('Extend Existing', 'videohub360-memberships'); ?></option>
                </select>
            </p>
            
            <?php if ($plan_key && isset($plans[$plan_key]['billing_mode']) && $plans[$plan_key]['billing_mode'] === 'recurring') : ?>
                <p class="description" style="color:#b32d2e;"><strong><?php esc_html_e('Warning:', 'videohub360-memberships'); ?></strong> <?php esc_html_e('This product is mapped to a recurring Stripe plan. Recurring plans should use Stripe Checkout, so choose a one-time/lifetime plan or clear the mapping before saving.', 'videohub360-memberships'); ?></p>
            <?php endif; ?>
            <p class="description">
                <?php esc_html_e('When no central Membership Plans assignment exists for this product, the fallback mapping below can grant or extend a WooCommerce-based membership plan.', 'videohub360-memberships'); ?>
            </p>
            <p class="description">
                <?php esc_html_e('Recurring membership plans are configured in Membership Plans with Stripe Price IDs and should not be mapped to WooCommerce products.', 'videohub360-memberships'); ?>
            </p>
        </div>
        <?php
    }
    


    /**
     * Render read-only linked course access meta box.
     *
     * @param WP_Post $post Product post.
     */
    public function render_course_access_meta_box($post) {
        $courses = function_exists('vh360_get_courses_for_product') ? vh360_get_courses_for_product($post->ID) : array();
        ?>
        <div class="vh360-course-access-mapping">
            <p class="description">
                <?php esc_html_e('Course links are managed from the course editor. This product currently grants access to:', 'videohub360-memberships'); ?>
            </p>
            <?php if (!empty($courses)) : ?>
                <ul>
                    <?php foreach ($courses as $course) : ?>
                        <li><?php echo esc_html($course->name); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><em><?php esc_html_e('No courses are linked to this product.', 'videohub360-memberships'); ?></em></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_product_meta($post_id, $post) {
        // Check if this is a product
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['vh360_membership_mapping_nonce']) || !wp_verify_nonce($_POST['vh360_membership_mapping_nonce'], 'vh360_membership_mapping')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save plan key with server-side recurring-plan protection.
        if (isset($_POST['vh360_membership_plan'])) {
            $selected_plan = sanitize_text_field(wp_unslash($_POST['vh360_membership_plan']));
            $plans = self::get_plan_registry();
            $previous_plan = get_post_meta($post_id, '_vh360_membership_plan', true);
            $is_recurring_plan = isset($plans[$selected_plan]['billing_mode']) && $plans[$selected_plan]['billing_mode'] === 'recurring';
            $allow_recurring_mapping = (bool) apply_filters('vh360_allow_recurring_plan_woocommerce_mapping', false, $selected_plan, $post_id);

            if ($selected_plan && $is_recurring_plan && !$allow_recurring_mapping) {
                if ($previous_plan && (!isset($plans[$previous_plan]['billing_mode']) || $plans[$previous_plan]['billing_mode'] !== 'recurring')) {
                    update_post_meta($post_id, '_vh360_membership_plan', $previous_plan);
                } else {
                    delete_post_meta($post_id, '_vh360_membership_plan');
                }

                set_transient(
                    'vh360_membership_mapping_notice',
                    __('Recurring membership plans should be sold through Stripe and were not saved as a WooCommerce product mapping.', 'videohub360-memberships'),
                    60
                );
            } else {
                update_post_meta($post_id, '_vh360_membership_plan', $selected_plan);
            }
        }
        
        // Save duration
        if (isset($_POST['vh360_membership_duration'])) {
            update_post_meta($post_id, '_vh360_membership_duration', absint($_POST['vh360_membership_duration']));
        }
        
        // Save duration unit
        if (isset($_POST['vh360_membership_duration_unit'])) {
            update_post_meta($post_id, '_vh360_membership_duration_unit', sanitize_text_field($_POST['vh360_membership_duration_unit']));
        }
        
        // Save grant type
        if (isset($_POST['vh360_membership_grant_type'])) {
            update_post_meta($post_id, '_vh360_membership_grant_type', sanitize_text_field($_POST['vh360_membership_grant_type']));
        }
    }

    /**
     * Add repair utility page.
     */
    public function add_repair_tool_page() {
        add_submenu_page(
            'tools.php',
            __('Repair Course Entitlements', 'videohub360-memberships'),
            __('Repair Course Entitlements', 'videohub360-memberships'),
            'manage_options',
            'vh360-repair-course-entitlements',
            array($this, 'render_repair_tool_page')
        );
    }

    /**
     * Render repair utility page.
     */
    public function render_repair_tool_page() {
        $created = isset($_GET['vh360_created']) ? absint($_GET['vh360_created']) : null;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Repair Course Entitlements', 'videohub360-memberships'); ?></h1>
            <?php if (null !== $created) : ?>
                <div class="notice notice-success"><p>
                    <?php printf(esc_html__('%d missing course entitlement(s) created.', 'videohub360-memberships'), $created); ?>
                </p></div>
            <?php endif; ?>
            <p><?php esc_html_e('Scan completed and processing WooCommerce orders for products linked to courses and create any missing course entitlement rows.', 'videohub360-memberships'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('vh360_repair_course_entitlements'); ?>
                <input type="hidden" name="action" value="vh360_repair_course_entitlements">
                <?php submit_button(__('Repair Course Entitlements', 'videohub360-memberships')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle repair utility submission.
     */
    public function handle_repair_course_entitlements() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to repair course entitlements.', 'videohub360-memberships'));
        }

        check_admin_referer('vh360_repair_course_entitlements');

        $created = $this->repair_course_entitlements();

        wp_safe_redirect(add_query_arg(
            array('page' => 'vh360-repair-course-entitlements', 'vh360_created' => $created),
            admin_url('tools.php')
        ));
        exit;
    }

    /**
     * Create missing course entitlements from historical paid orders.
     *
     * @return int Number of created rows.
     */
    private function repair_course_entitlements() {
        if (!function_exists('wc_get_orders') || !function_exists('vh360_get_courses_for_product') || !function_exists('vh360_grant_course_entitlement')) {
            return 0;
        }

        $orders = wc_get_orders(array(
            'status' => array('completed', 'processing'),
            'limit' => -1,
            'return' => 'objects',
        ));

        $created = 0;

        foreach ($orders as $order) {
            $user_id = $order->get_user_id();
            if (!$user_id) {
                continue;
            }

            foreach ($order->get_items() as $item) {
                $product_ids = array_filter(array_unique(array_map('absint', array(
                    $item->get_product_id(),
                    method_exists($item, 'get_variation_id') ? $item->get_variation_id() : 0,
                ))));

                foreach ($product_ids as $product_id) {
                    foreach (vh360_get_courses_for_product($product_id) as $course) {
                        if (!class_exists('VH360_Membership_Database')) {
                            continue;
                        }

                        global $wpdb;
                        $table = VH360_Membership_Database::get_course_entitlements_table();
                        $exists = (int) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND course_term_id = %d AND source_order_id = %d",
                            $user_id,
                            $course->term_id,
                            $order->get_id()
                        ));

                        if ($exists) {
                            continue;
                        }

                        $result = vh360_grant_course_entitlement($user_id, $course->term_id, $product_id, $order->get_id());
                        if ($result) {
                            $created++;
                        }
                    }
                }
            }
        }

        return $created;
    }

}
