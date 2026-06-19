<?php
/**
 * Frontend Subscription Management
 *
 * Provides user-facing subscription management through a shortcode
 * and AJAX-driven interface. Users can view their plan, billing status,
 * cancel, reactivate, and access the billing portal.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Subscription_Management {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Subscription_Management
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Subscription_Management
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
        // Register shortcode
        add_shortcode('vh360_membership_manage', array($this, 'render_shortcode'));
        
        // Enqueue frontend scripts when needed
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handler for membership data
        add_action('wp_ajax_vh360_get_membership_data', array($this, 'ajax_get_membership_data'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that need it.
        $has_manage_shortcode = is_singular() && has_shortcode((string) get_post_field('post_content', get_queried_object_id()), 'vh360_membership_manage');
        if (!is_user_logged_in() && !$has_manage_shortcode) {
            return;
        }
        
        wp_enqueue_script(
            'vh360-membership-manage',
            VH360_MEMBERSHIPS_URL . 'assets/js/membership-manage.js',
            array('jquery'),
            VH360_MEMBERSHIPS_VERSION,
            true
        );
        
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        
        wp_localize_script('vh360-membership-manage', 'vh360MembershipManage', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => array(
                'getMembership' => wp_create_nonce('vh360_get_membership_data'),
                'checkout' => wp_create_nonce('vh360_stripe_checkout'),
                'manage' => wp_create_nonce('vh360_stripe_manage'),
                'portal' => wp_create_nonce('vh360_stripe_portal'),
            ),
            'stripeEnabled' => $stripe->is_configured(),
            'portalEnabled' => $stripe->is_portal_enabled(),
            'selectedPlan' => function_exists('vh360_membership_get_selected_plan_from_request') ? vh360_membership_get_selected_plan_from_request() : '',
            'autoCheckout' => !empty($_GET['vh360_start_checkout']) && is_user_logged_in(),
            'i18n' => array(
                'loading' => __('Loading...', 'videohub360-memberships'),
                'noMembership' => __('No active membership', 'videohub360-memberships'),
                'confirmCancel' => __('Are you sure you want to cancel your subscription?', 'videohub360-memberships'),
                'error' => __('An error occurred. Please try again.', 'videohub360-memberships'),
            ),
        ));
    }
    
    /**
     * AJAX: Get current user's membership data
     */
    public function ajax_get_membership_data() {
        check_ajax_referer('vh360_get_membership_data', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Not logged in.', 'videohub360-memberships')));
        }
        
        $membership = vh360_get_active_membership($user_id);
        
        if (!$membership) {
            wp_send_json_success(array(
                'has_membership' => false,
            ));
        }
        
        $plans = VH360_Membership_Plans::get_plan_registry();
        $plan_label = $this->resolve_plan_display_label($plans, $membership->plan_key);
        
        $data = array(
            'has_membership'       => true,
            'membership_id'        => (int) $membership->id,
            'plan_key'             => $membership->plan_key,
            'plan_label'           => $plan_label,
            'status'               => $membership->status,
            'billing_mode'         => isset($membership->billing_mode) ? $membership->billing_mode : 'one_time',
            'subscription_status'  => isset($membership->subscription_status) ? $membership->subscription_status : null,
            'cancel_at_period_end' => isset($membership->cancel_at_period_end) ? (bool) $membership->cancel_at_period_end : false,
            'starts_at'            => $membership->starts_at,
            'expires_at'           => $membership->expires_at,
            'current_period_end'   => isset($membership->current_period_end) ? $membership->current_period_end : null,
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Render the membership management shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function get_dashboard_card_style_defaults() {
        return array(
            'subscription_card_bg_color' => '#ffffff',
            'subscription_card_border_color' => '#e0e0e0',
            'subscription_card_title_color' => '#333333',
            'subscription_card_price_color' => '#333333',
            'subscription_card_text_color' => '#666666',
            'subscription_card_button_bg_color' => '#0073aa',
            'subscription_card_button_text_color' => '#ffffff',
        );
    }

    public static function get_dashboard_card_style_attribute() {
        $options = get_option('vh360_membership_options', array());
        $options = is_array($options) ? $options : array();
        $map = array(
            'subscription_card_bg_color' => '--vh360-dashboard-card-bg',
            'subscription_card_border_color' => '--vh360-dashboard-card-border',
            'subscription_card_title_color' => '--vh360-dashboard-title-color',
            'subscription_card_price_color' => '--vh360-dashboard-price-color',
            'subscription_card_text_color' => '--vh360-dashboard-text-color',
            'subscription_card_button_bg_color' => '--vh360-dashboard-button-bg',
            'subscription_card_button_text_color' => '--vh360-dashboard-button-color',
        );
        $defaults = self::get_dashboard_card_style_defaults();
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

    public function render_shortcode($atts = array()) {
        $selected_plan = function_exists('vh360_membership_get_selected_plan_from_request') ? vh360_membership_get_selected_plan_from_request() : '';
        $user_id = get_current_user_id();
        if (!$user_id) {
            return $this->render_guest_recurring_signup($selected_plan);
        }

        $membership = vh360_get_active_membership($user_id);
        $is_recurring_stripe_member = function_exists('vh360_membership_is_recurring_stripe_membership') ? vh360_membership_is_recurring_stripe_membership($membership) : ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring');
        $plans = VH360_Membership_Plans::get_plan_registry();
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $options = get_option('vh360_membership_options', array());
        $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
        $support_url = isset($options['support_url']) ? $options['support_url'] : (isset($options['contact_url']) ? $options['contact_url'] : '');
        $woo_products = function_exists('vh360_get_upgrade_products_for_user') ? vh360_get_upgrade_products_for_user($user_id) : array();
        if (!$membership && function_exists('vh360_get_membership_products')) {
            $woo_products = $this->filter_dashboard_woocommerce_products(vh360_get_membership_products(), $plans);
        } else {
            $woo_products = $this->filter_dashboard_woocommerce_products($woo_products, $plans);
        }
        $recurring_plans = $this->get_dashboard_recurring_plans($plans, $membership, $selected_plan);
        $recurring_plan_groups = $this->group_dashboard_recurring_plans_by_interval($recurring_plans);
        $card_options = get_option('vh360_membership_options', array());
        $button_label = !empty($card_options['subscription_card_button_label']) ? $card_options['subscription_card_button_label'] : __('Subscribe', 'videohub360-memberships');
        $dashboard_style = self::get_dashboard_card_style_attribute();

        ob_start();
        ?>
        <div class="vh360-membership-management vh360-membership-account-center" id="vh360-membership-management" style="<?php echo esc_attr($dashboard_style); ?>">
            <?php if ($selected_plan && !empty($recurring_plans[$selected_plan])) : ?>
                <?php $selected_plan_action = $this->get_recurring_plan_action_state($user_id, $selected_plan, $recurring_plans[$selected_plan], $membership); ?>
                <?php if (!empty($selected_plan_action['notice_text'])) : ?>
                    <div class="vh360-membership-notice vh360-membership-notice-info">
                        <?php echo esc_html($selected_plan_action['notice_text']); ?>
                    </div>
                <?php endif; ?>
            <?php elseif (isset($_GET['vh360_plan'])) : ?>
                <div class="vh360-membership-notice vh360-membership-notice-warning">
                    <?php esc_html_e('The selected membership plan is no longer available. Please choose another plan.', 'videohub360-memberships'); ?>
                </div>
            <?php endif; ?>
            <section class="vh360-membership-section vh360-current-membership-section">
                <h3><?php echo ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring') ? esc_html__('Current Subscription', 'videohub360-memberships') : esc_html__('Current Membership', 'videohub360-memberships'); ?></h3>
                <?php if ($membership) :
                    $plan_label = $this->resolve_plan_display_label($plans, $membership->plan_key);
                    $is_recurring = isset($membership->billing_mode) && $membership->billing_mode === 'recurring';
                    $cancel_pending = !empty($membership->cancel_at_period_end);
                    $sub_status = isset($membership->subscription_status) ? $membership->subscription_status : '';
                    $provider = $is_recurring ? __('Payment provider', 'videohub360-memberships') : __('WooCommerce', 'videohub360-memberships');
                    $type_label = $is_recurring ? __('Recurring Subscription', 'videohub360-memberships') : (empty($membership->expires_at) ? __('Lifetime Access', 'videohub360-memberships') : __('Fixed-Term Access', 'videohub360-memberships'));
                ?>
                    <div class="vh360-membership-card vh360-current-plan-card">
                        <div class="vh360-membership-card-header">
                            <div>
                                <span class="vh360-plan-eyebrow"><?php esc_html_e('Current Plan', 'videohub360-memberships'); ?></span>
                                <h3><?php echo esc_html($plan_label); ?></h3>
                            </div>
                            <span class="vh360-membership-badge vh360-membership-badge-<?php echo esc_attr($membership->status); ?>"><?php echo esc_html(ucfirst($membership->status)); ?></span>
                        </div>
                        <div class="vh360-membership-card-body">
                            <div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Billing Type', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php echo esc_html($type_label); ?></span></div>
                            <div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Billing Source', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php echo esc_html($provider); ?></span></div>
                            <?php if ($is_recurring && $sub_status) : ?><div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Subscription Status', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value vh360-billing-status-<?php echo esc_attr($sub_status); ?>"><?php echo esc_html($this->format_subscription_status($sub_status)); ?></span></div><?php endif; ?>
                            <?php if ($is_recurring && !empty($membership->current_period_end)) : ?><div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php echo $cancel_pending ? esc_html__('Access Until', 'videohub360-memberships') : esc_html__('Next Billing Date', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->current_period_end))); ?></span></div>
                            <?php elseif (!$is_recurring && !empty($membership->expires_at)) : ?><div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Expiration Date', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->expires_at))); ?></span></div>
                            <?php else : ?><div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Expiration', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php esc_html_e('Never (Lifetime)', 'videohub360-memberships'); ?></span></div><?php endif; ?>
                            <?php if ($cancel_pending) : ?><div class="vh360-membership-notice vh360-membership-notice-warning"><?php esc_html_e('Your subscription is scheduled for cancellation at the end of the current billing period.', 'videohub360-memberships'); ?></div><?php endif; ?>
                            <?php if ($sub_status === 'past_due') : ?><div class="vh360-membership-notice vh360-membership-notice-error"><?php esc_html_e('Payment failed. Please update your payment method to keep your access.', 'videohub360-memberships'); ?></div><?php endif; ?>
                        </div>
                        <?php if ($is_recurring) : ?><div class="vh360-membership-card-actions">
                            <?php if ($stripe->is_portal_enabled()) : ?><button type="button" class="vh360-btn vh360-btn-primary vh360-open-portal"><?php esc_html_e('Manage Billing', 'videohub360-memberships'); ?></button><?php endif; ?>
                            <?php if ($cancel_pending) : ?><button type="button" class="vh360-btn vh360-btn-secondary vh360-reactivate-subscription" data-membership-id="<?php echo esc_attr($membership->id); ?>"><?php esc_html_e('Reactivate Subscription', 'videohub360-memberships'); ?></button><?php else : ?><button type="button" class="vh360-btn vh360-btn-danger vh360-cancel-subscription" data-membership-id="<?php echo esc_attr($membership->id); ?>"><?php esc_html_e('Cancel Subscription', 'videohub360-memberships'); ?></button><?php endif; ?>
                        </div><?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="vh360-membership-empty-state"><h4><?php esc_html_e('No active membership', 'videohub360-memberships'); ?></h4><p><?php esc_html_e('Choose an available membership plan below to unlock member access.', 'videohub360-memberships'); ?></p></div>
                <?php endif; ?>
            </section>

            <section class="vh360-membership-section vh360-upgrade-section">
                <h3><?php echo $membership ? esc_html__('Upgrade Your Membership', 'videohub360-memberships') : esc_html__('Available Plans', 'videohub360-memberships'); ?></h3>
                <?php if (!empty($woo_products)) : ?>
                    <h4 class="vh360-plan-group-title"><?php esc_html_e('One-Time / Fixed-Term Plans', 'videohub360-memberships'); ?></h4>
                    <div class="vh360-plan-card-grid">
                        <?php foreach ($woo_products as $product) : ?>
                            <?php
                                $woo_same_tier = $membership && isset($product['tier_level']) && (int) $product['tier_level'] === VH360_Membership_Plans::get_plan_tier($membership->plan_key);
                                $current_is_recurring = $membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring';
                                $product_is_lifetime = isset($product['duration_unit']) && $product['duration_unit'] === 'lifetime';
                                $woo_button_label = ($current_is_recurring && $product_is_lifetime) ? __('Switch to Lifetime Access', 'videohub360-memberships') : (!empty($product['action_label']) ? $product['action_label'] : (!$membership ? __('Join Now', 'videohub360-memberships') : ($woo_same_tier ? __('Change Plan', 'videohub360-memberships') : __('Upgrade', 'videohub360-memberships'))));
                            ?>
                            <article class="vh360-subscription-plan-card vh360-woocommerce-plan-card <?php echo !empty($product['featured']) ? 'is-featured' : ''; ?>">
                                <?php if (!empty($product['featured'])) : ?><span class="vh360-plan-pill"><?php esc_html_e('Recommended', 'videohub360-memberships'); ?></span><?php endif; ?>
                                <h4><?php echo esc_html($product['title']); ?></h4>
                                <div class="vh360-plan-type"><?php echo ($current_is_recurring && $product_is_lifetime) ? esc_html__('One-time lifetime upgrade', 'videohub360-memberships') : ($product['duration_unit'] === 'lifetime' ? esc_html__('Lifetime Access', 'videohub360-memberships') : esc_html__('Fixed-Term Access', 'videohub360-memberships')); ?></div>
                                <?php if (!empty($product['price_html'])) : ?><div class="vh360-plan-price"><?php echo wp_kses_post($product['price_html']); ?></div><?php endif; ?>
                                <?php if (!empty($product['short_description'])) : ?><div class="vh360-plan-description"><?php echo wp_kses_post(wpautop($product['short_description'])); ?></div><?php endif; ?>
                                <a class="vh360-btn vh360-btn-primary" href="<?php echo esc_url($product['checkout_url']); ?>"><?php echo esc_html($woo_button_label); ?></a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($recurring_plans) && $stripe->is_configured()) : ?>
                    <h4 class="vh360-plan-group-title"><?php echo $is_recurring_stripe_member ? esc_html__('Change Your Recurring Plan', 'videohub360-memberships') : esc_html__('Recurring Plans', 'videohub360-memberships'); ?></h4>
                    <?php if ($is_recurring_stripe_member) : ?>
                        <div class="vh360-membership-notice vh360-membership-notice-info">
                            <p><?php esc_html_e('Recurring subscription changes are managed securely through the Billing Portal. Open the portal to change your plan, update your payment method, view invoices, or manage your subscription.', 'videohub360-memberships'); ?></p>
                            <?php if ($stripe->is_portal_enabled()) : ?>
                                <button type="button" class="vh360-btn vh360-btn-primary vh360-open-portal"><?php esc_html_e('Manage Billing', 'videohub360-memberships'); ?></button>
                            <?php else : ?>
                                <p class="vh360-membership-muted"><?php esc_html_e('Billing portal access is not available right now. Please contact support to change your recurring plan.', 'videohub360-memberships'); ?></p>
                                <?php if ($support_url) : ?><a class="vh360-btn vh360-btn-secondary" href="<?php echo esc_url($support_url); ?>"><?php esc_html_e('Contact Support', 'videohub360-memberships'); ?></a><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php $this->render_dashboard_recurring_plan_groups($recurring_plan_groups, $plans, $membership, $selected_plan, $button_label, true); ?>
                <?php endif; ?>

                <?php if (empty($woo_products) && (empty($recurring_plans) || !$stripe->is_configured())) : ?>
                    <div class="vh360-membership-notice vh360-membership-notice-warning">
                        <?php echo $membership ? esc_html__('No upgrade options are available for your current membership right now.', 'videohub360-memberships') : esc_html__('No membership plans are available right now.', 'videohub360-memberships'); ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="vh360-membership-section vh360-billing-help-section">
                <h3><?php esc_html_e('Billing & Support', 'videohub360-memberships'); ?></h3>
                <div class="vh360-membership-card-actions">
                    <?php if ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring' && $stripe->is_portal_enabled()) : ?><button type="button" class="vh360-btn vh360-btn-primary vh360-open-portal"><?php esc_html_e('Manage Billing', 'videohub360-memberships'); ?></button><?php endif; ?>
                    <?php if ($pricing_url) : ?><a class="vh360-btn vh360-btn-secondary" href="<?php echo esc_url($pricing_url); ?>"><?php esc_html_e('View All Plans', 'videohub360-memberships'); ?></a><?php endif; ?>
                    <?php if ($support_url) : ?><a class="vh360-btn vh360-btn-secondary" href="<?php echo esc_url($support_url); ?>"><?php esc_html_e('Contact Support', 'videohub360-memberships'); ?></a><?php endif; ?>
                </div>
                <?php if ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring' && !$stripe->is_portal_enabled()) : ?><p class="vh360-membership-muted"><?php esc_html_e('Billing management is not configured. Contact support for billing changes.', 'videohub360-memberships'); ?></p><?php endif; ?>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render public recurring signup cards for logged-out visitors.
     *
     * @param string $selected_plan Selected plan key.
     * @return string
     */
    private function render_guest_recurring_signup($selected_plan = '') {
        $plans = VH360_Membership_Plans::get_plan_registry();
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        if (!$stripe->is_configured()) {
            return '<div class="vh360-membership-management"><div class="vh360-membership-notice vh360-membership-notice-warning">' . esc_html__('Recurring memberships are not available right now. Please contact support.', 'videohub360-memberships') . '</div>' . vh360_render_login_gate() . '</div>';
        }

        $recurring_plans = $this->get_dashboard_recurring_plans($plans, false, $selected_plan);
        $recurring_plan_groups = $this->group_dashboard_recurring_plans_by_interval($recurring_plans);
        $dashboard_style = self::get_dashboard_card_style_attribute();
        if (empty($recurring_plans)) {
            return vh360_render_login_gate();
        }

        ob_start();
        ?>
        <div class="vh360-membership-management vh360-membership-account-center" id="vh360-membership-management" style="<?php echo esc_attr($dashboard_style); ?>">
            <section class="vh360-membership-section vh360-guest-recurring-section">
                <div class="vh360-membership-empty-state">
                    <h3><?php esc_html_e('Create an account to start a recurring membership.', 'videohub360-memberships'); ?></h3>
                    <p><?php esc_html_e('Choose a recurring plan, create your account, then continue securely to payment.', 'videohub360-memberships'); ?></p>
                </div>
                <?php if (isset($_GET['vh360_plan']) && !$selected_plan) : ?>
                    <div class="vh360-membership-notice vh360-membership-notice-warning"><?php esc_html_e('The selected membership plan is no longer available. Please choose another plan.', 'videohub360-memberships'); ?></div>
                <?php endif; ?>
                <?php $this->render_dashboard_recurring_plan_groups($recurring_plan_groups, $plans, false, $selected_plan, __('Subscribe', 'videohub360-memberships'), true, true); ?>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Filter WooCommerce dashboard products by the membership plan dashboard visibility flag.
     *
     * @param array $products Membership product records.
     * @param array $plans    Full plan registry.
     * @return array
     */
    private function filter_dashboard_woocommerce_products($products, $plans) {
        return array_values(array_filter((array) $products, function($product) use ($plans) {
            if (empty($product['plan_key']) || empty($plans[$product['plan_key']])) {
                return false;
            }

            return !isset($plans[$product['plan_key']]['show_in_dashboard']) || !empty($plans[$product['plan_key']]['show_in_dashboard']);
        }));
    }

    /**
     * Render grouped recurring dashboard plan cards with a shared interval switcher.
     *
     * @param array        $groups       Grouped recurring plans.
     * @param array        $plans        Full plan registry.
     * @param object|false $membership   Current membership.
     * @param string       $selected_plan Selected plan key.
     * @param string       $button_label Default button label.
     * @param bool         $show_buttons Whether checkout buttons should be shown.
     * @param bool         $guest_links  Whether guest register/login links should be shown.
     */
    private function render_dashboard_recurring_plan_groups($groups, $plans, $membership, $selected_plan, $button_label, $show_buttons = true, $guest_links = false) {
        if (empty($groups)) {
            return;
        }

        $interval_labels = array(
            'monthly' => __('Monthly', 'videohub360-memberships'),
            'yearly'  => __('Yearly', 'videohub360-memberships'),
        );
        $available_intervals = array();
        foreach ($interval_labels as $interval => $label) {
            foreach ($groups as $group) {
                if (!empty($group['plans'][$interval])) {
                    $available_intervals[$interval] = $label;
                    break;
                }
            }
        }

        $active_interval = key($available_intervals);
        if ($selected_plan) {
            foreach ($groups as $group) {
                foreach ($group['plans'] as $interval => $interval_plans) {
                    if (isset($interval_plans[$selected_plan])) {
                        $active_interval = $interval;
                        break 2;
                    }
                }
            }
        }

        $show_tabs = count($available_intervals) > 1;
        ?>
        <div class="vh360-dashboard-recurring-plans" data-vh360-dashboard-plan-switcher>
            <?php if ($show_tabs) : ?>
                <div class="vh360-dashboard-plan-tabs" role="tablist" aria-label="<?php esc_attr_e('Recurring billing intervals', 'videohub360-memberships'); ?>">
                    <?php foreach ($available_intervals as $interval => $label) : $is_active = $interval === $active_interval; ?>
                        <button type="button" class="vh360-dashboard-plan-tab <?php echo $is_active ? 'is-active' : ''; ?>" role="tab" tabindex="<?php echo $is_active ? '0' : '-1'; ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" aria-controls="vh360-dashboard-plan-panel-<?php echo esc_attr($interval); ?>" data-vh360-dashboard-plan-tab="<?php echo esc_attr($interval); ?>"><?php echo esc_html($label); ?></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($available_intervals as $interval => $label) : $is_active = $interval === $active_interval; ?>
                <div id="vh360-dashboard-plan-panel-<?php echo esc_attr($interval); ?>" class="vh360-dashboard-plan-panel <?php echo $is_active ? 'is-active' : ''; ?>" role="tabpanel" <?php echo $is_active ? '' : 'hidden'; ?> data-vh360-dashboard-plan-panel="<?php echo esc_attr($interval); ?>">
                    <div class="vh360-plan-card-grid vh360-subscription-plans">
                        <?php foreach ($groups as $group) : ?>
                            <?php if (empty($group['plans'][$interval])) { continue; } ?>
                            <?php foreach ($group['plans'][$interval] as $key => $plan) : ?>
                                <?php $this->render_dashboard_recurring_plan_card($key, $plan, $plans, $membership, $selected_plan, $button_label, $show_buttons, $guest_links); ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render a single compact recurring dashboard plan card.
     */
    private function render_dashboard_recurring_plan_card($key, $plan, $plans, $membership, $selected_plan, $button_label, $show_buttons, $guest_links) {
        $user_id = get_current_user_id();
        $action_state = $this->get_recurring_plan_action_state($user_id, $key, $plan, $membership);
        $action_class = !empty($action_state['use_billing_portal']) ? 'vh360-manage-billing' : 'vh360-start-subscription';
        ?>
        <article class="vh360-subscription-plan-card vh360-dashboard-plan-group-card <?php echo !empty($plan['featured']) ? 'is-featured' : ''; ?> <?php echo ($selected_plan && $selected_plan === $key) ? 'is-selected' : ''; ?>">
            <?php if (!empty($plan['featured'])) : ?><span class="vh360-plan-pill"><?php esc_html_e('Recommended', 'videohub360-memberships'); ?></span><?php endif; ?>
            <h4><?php echo esc_html($this->resolve_plan_display_label($plans, $key)); ?></h4>
            <div class="vh360-plan-type"><?php esc_html_e('Recurring Billing', 'videohub360-memberships'); ?></div>
            <?php if (!empty($plan['display_price'])) : ?><div class="vh360-plan-price"><?php echo esc_html($plan['display_price']); ?></div><?php endif; ?>
            <?php if (!empty($plan['display_description'])) : ?><p class="vh360-plan-description"><?php echo esc_html($plan['display_description']); ?></p><?php endif; ?>
            <?php if (!empty($plan['display_features']) && is_array($plan['display_features'])) : ?><ul class="vh360-plan-features"><?php foreach ($plan['display_features'] as $feature) : ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if (!empty($plan['trial_days'])) : ?><p class="vh360-plan-trial"><?php printf(esc_html__('%d-day free trial', 'videohub360-memberships'), $plan['trial_days']); ?></p><?php endif; ?>
            <?php if ($guest_links) : ?>
                <a class="vh360-btn vh360-btn-primary" href="<?php echo esc_url(vh360_membership_get_recurring_register_url($key)); ?>"><?php esc_html_e('Create Account & Subscribe', 'videohub360-memberships'); ?></a>
                <a class="vh360-auth-link" href="<?php echo esc_url(vh360_membership_get_recurring_login_url($key)); ?>"><?php esc_html_e('Already have an account? Sign in', 'videohub360-memberships'); ?></a>
            <?php elseif ($show_buttons) : ?>
                <?php if ('portal_unavailable' === $action_state['action_type']) : ?>
                    <?php if (!empty($action_state['support_url'])) : ?>
                        <a class="vh360-btn vh360-btn-secondary" href="<?php echo esc_url($action_state['support_url']); ?>"><?php echo esc_html($action_state['button_label']); ?></a>
                    <?php else : ?>
                        <p class="vh360-membership-muted"><?php echo esc_html($action_state['notice_text']); ?></p>
                        <button type="button" class="vh360-btn vh360-btn-secondary" disabled><?php echo esc_html($action_state['button_label']); ?></button>
                    <?php endif; ?>
                <?php elseif (!empty($action_state['disabled'])) : ?>
                    <button type="button" class="vh360-btn vh360-btn-secondary" disabled><?php echo esc_html($action_state['button_label']); ?></button>
                <?php elseif (!empty($action_state['use_checkout']) || !empty($action_state['use_billing_portal'])) : ?>
                    <button type="button" class="vh360-btn vh360-btn-primary <?php echo esc_attr($action_class); ?>" data-plan-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($action_state['button_label']); ?></button>
                <?php endif; ?>
            <?php endif; ?>
        </article>
        <?php
    }


    /**
     * Determine the correct frontend action for a recurring dashboard plan.
     */
    private function get_recurring_plan_action_state($user_id, $plan_key, $plan, $membership = false) {
        $options = get_option('vh360_membership_options', array());
        $support_url = isset($options['support_url']) ? $options['support_url'] : (isset($options['contact_url']) ? $options['contact_url'] : '');
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $is_current_plan = $membership && isset($membership->billing_mode, $membership->plan_key) && 'recurring' === $membership->billing_mode && $membership->plan_key === $plan_key;
        $has_active_recurring = $membership && isset($membership->billing_mode) && 'recurring' === $membership->billing_mode;
        $is_checkout_ready = !empty($plan['stripe_price_id']) && (!isset($plan['enabled']) || !empty($plan['enabled'])) && $stripe->is_configured();

        $state = array(
            'action_type'        => 'unavailable',
            'button_label'       => __('Unavailable', 'videohub360-memberships'),
            'notice_text'        => '',
            'disabled'           => true,
            'use_checkout'       => false,
            'use_billing_portal' => false,
            'is_current_plan'    => $is_current_plan,
            'is_selected_plan'   => function_exists('vh360_membership_get_selected_plan_from_request') && vh360_membership_get_selected_plan_from_request() === $plan_key,
            'support_url'        => $support_url,
        );

        if (!$is_checkout_ready) {
            return $state;
        }

        if ($is_current_plan) {
            $state['action_type'] = 'current_plan';
            $state['button_label'] = __('Current Plan', 'videohub360-memberships');
            $state['notice_text'] = __('This is your current plan.', 'videohub360-memberships');
            return $state;
        }

        if ($has_active_recurring) {
            if (!$stripe->is_portal_enabled()) {
                $state['action_type'] = 'portal_unavailable';
                $state['button_label'] = $support_url ? __('Contact Support', 'videohub360-memberships') : __('Billing Unavailable', 'videohub360-memberships');
                $state['notice_text'] = __('Plan changes are currently unavailable from your account. Please contact support.', 'videohub360-memberships');
                return $state;
            }

            $state['action_type'] = 'switch_via_billing';
            $state['button_label'] = __('Change Plan', 'videohub360-memberships');
            $state['notice_text'] = __('You selected a different recurring plan. Use billing management to update your subscription.', 'videohub360-memberships');
            $state['disabled'] = false;
            $state['use_billing_portal'] = true;
            return $state;
        }

        $state['action_type'] = 'start_checkout';
        $state['button_label'] = __('Continue to Payment', 'videohub360-memberships');
        $state['notice_text'] = $membership ? __('Continue to payment to activate recurring billing for this plan.', 'videohub360-memberships') : __('Your account is ready. Continue to payment to activate your membership.', 'videohub360-memberships');
        $state['disabled'] = false;
        $state['use_checkout'] = true;
        return $state;
    }

    /**
     * Group eligible dashboard recurring plans by plan group and billing interval.
     *
     * @param array $recurring_plans Flat eligible recurring plans keyed by plan key.
     * @return array
     */
    private function group_dashboard_recurring_plans_by_interval($recurring_plans) {
        $groups = array();
        foreach ((array) $recurring_plans as $key => $plan) {
            $group_key = !empty($plan['plan_group']) ? $plan['plan_group'] : $key;
            $interval = !empty($plan['billing_interval']) ? $plan['billing_interval'] : 'monthly';
            if (!isset($groups[$group_key])) {
                $groups[$group_key] = array(
                    'label'    => !empty($plan['label']) ? $plan['label'] : $key,
                    'order'    => isset($plan['display_order']) ? (int) $plan['display_order'] : 999,
                    'featured' => !empty($plan['featured']) || !empty($plan['is_featured']),
                    'plans'    => array(),
                );
            }
            $groups[$group_key]['order'] = min($groups[$group_key]['order'], isset($plan['display_order']) ? (int) $plan['display_order'] : 999);
            $groups[$group_key]['featured'] = $groups[$group_key]['featured'] || !empty($plan['featured']) || !empty($plan['is_featured']);
            $groups[$group_key]['plans'][$interval][$key] = $plan;
        }

        uasort($groups, function($a, $b) {
            if ($a['order'] === $b['order']) {
                return strcasecmp($a['label'], $b['label']);
            }
            return $a['order'] <=> $b['order'];
        });

        return $groups;
    }

    /**
     * Get eligible recurring plans for the dashboard.
     *
     * @param array        $plans      Plan registry.
     * @param object|false $membership Current membership.
     * @return array
     */
    private function get_dashboard_recurring_plans($plans, $membership, $selected_plan = '') {
        $eligible = array();
        foreach ($plans as $key => $plan) {
            if (!function_exists('vh360_membership_plan_is_eligible_change')) {
                continue;
            }

            $is_recurring = isset($plan['billing_mode']) && $plan['billing_mode'] === 'recurring';
            if (!$is_recurring || empty($plan['stripe_price_id'])) {
                continue;
            }

            $is_selected_plan = $selected_plan && $selected_plan === $key;
            if (!$is_selected_plan && isset($plan['show_in_dashboard']) && !$plan['show_in_dashboard']) {
                continue;
            }

            $is_current_recurring_plan = $membership && isset($membership->billing_mode, $membership->plan_key) && 'recurring' === $membership->billing_mode && $membership->plan_key === $key;
            if (!$is_current_recurring_plan && !vh360_membership_plan_is_eligible_change($key, $plan, $membership)) {
                continue;
            }

            $plan['plan_key'] = $key;
            $plan['tier_level'] = VH360_Membership_Plans::get_plan_tier($key);
            $plan['display_order'] = isset($plan['display_order']) ? (int) $plan['display_order'] : 999;
            $plan['label'] = $this->resolve_plan_display_label($plans, $key);
            $eligible[$key] = $plan;
        }

        if (function_exists('vh360_sort_membership_plan_items')) {
            $sorted = vh360_sort_membership_plan_items(array_values($eligible));
            $eligible = array();
            foreach ($sorted as $plan) {
                if (!empty($plan['plan_key'])) {
                    $eligible[$plan['plan_key']] = $plan;
                }
            }
        }

        return $eligible;
    }

    /**
     * Resolve the effective display title for a plan.
     *
     * Uses display_label if set, then label, then the raw plan key.
     *
     * @param array  $plans    Full plan registry.
     * @param string $plan_key The plan key to look up.
     * @return string Resolved display title.
     */
    private function resolve_plan_display_label($plans, $plan_key) {
        if (!isset($plans[$plan_key])) {
            return sprintf(__('Legacy plan: %s', 'videohub360-memberships'), $plan_key);
        }
        $plan = $plans[$plan_key];
        if (!empty($plan['display_label'])) {
            return $plan['display_label'];
        }
        if (!empty($plan['label'])) {
            return $plan['label'];
        }
        return $plan_key;
    }

    /**
     * Format subscription status for display
     *
     * @param string $status Stripe subscription status
     * @return string Human-readable status
     */
    private function format_subscription_status($status) {
        $labels = array(
            'active'             => __('Active', 'videohub360-memberships'),
            'trialing'           => __('Trial', 'videohub360-memberships'),
            'past_due'           => __('Payment Failed', 'videohub360-memberships'),
            'unpaid'             => __('Unpaid', 'videohub360-memberships'),
            'incomplete'         => __('Incomplete', 'videohub360-memberships'),
            'incomplete_expired' => __('Expired', 'videohub360-memberships'),
            'canceled'           => __('Cancelled', 'videohub360-memberships'),
            'paused'             => __('Paused', 'videohub360-memberships'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    }
}
