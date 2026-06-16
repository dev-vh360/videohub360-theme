<?php
/**
 * Frontend Subscription Management
 *
 * Provides user-facing subscription management through a shortcode
 * and AJAX-driven interface. Users can view their plan, billing status,
 * cancel, reactivate, and access the Stripe billing portal.
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
        // Only enqueue on pages that need it
        if (!is_user_logged_in()) {
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
    public function render_shortcode($atts = array()) {
        if (!is_user_logged_in()) {
            return vh360_render_login_gate();
        }

        $user_id = get_current_user_id();
        $membership = vh360_get_active_membership($user_id);
        $is_recurring_stripe_member = function_exists('vh360_membership_is_recurring_stripe_membership') ? vh360_membership_is_recurring_stripe_membership($membership) : ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring');
        $plans = VH360_Membership_Plans::get_plan_registry();
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $options = get_option('vh360_membership_options', array());
        $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
        $support_url = isset($options['support_url']) ? $options['support_url'] : (isset($options['contact_url']) ? $options['contact_url'] : '');
        $woo_products = function_exists('vh360_get_upgrade_products_for_user') ? vh360_get_upgrade_products_for_user($user_id) : array();
        if (!$membership && function_exists('vh360_get_membership_products')) {
            $woo_products = vh360_get_membership_products();
        }
        $recurring_plans = $this->get_dashboard_recurring_plans($plans, $membership);
        $card_options = get_option('vh360_membership_options', array());
        $button_label = !empty($card_options['subscription_card_button_label']) ? $card_options['subscription_card_button_label'] : __('Subscribe', 'videohub360-memberships');

        ob_start();
        ?>
        <div class="vh360-membership-management vh360-membership-account-center" id="vh360-membership-management">
            <section class="vh360-membership-section vh360-current-membership-section">
                <h3><?php echo ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring') ? esc_html__('Current Subscription', 'videohub360-memberships') : esc_html__('Current Membership', 'videohub360-memberships'); ?></h3>
                <?php if ($membership) :
                    $plan_label = $this->resolve_plan_display_label($plans, $membership->plan_key);
                    $is_recurring = isset($membership->billing_mode) && $membership->billing_mode === 'recurring';
                    $cancel_pending = !empty($membership->cancel_at_period_end);
                    $sub_status = isset($membership->subscription_status) ? $membership->subscription_status : '';
                    $provider = $is_recurring ? __('Stripe', 'videohub360-memberships') : __('WooCommerce', 'videohub360-memberships');
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
                            <div class="vh360-membership-detail"><span class="vh360-membership-detail-label"><?php esc_html_e('Billing Provider', 'videohub360-memberships'); ?></span><span class="vh360-membership-detail-value"><?php echo esc_html($provider); ?></span></div>
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
                    <div class="vh360-membership-empty-state"><h4><?php esc_html_e('No active membership', 'videohub360-memberships'); ?></h4><p><?php esc_html_e('Choose a fixed-term, lifetime, or recurring plan below to unlock member access.', 'videohub360-memberships'); ?></p></div>
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
                    <h4 class="vh360-plan-group-title"><?php echo $is_recurring_stripe_member ? esc_html__('Change Your Recurring Plan', 'videohub360-memberships') : esc_html__('Recurring Stripe Plans', 'videohub360-memberships'); ?></h4>
                    <?php if ($is_recurring_stripe_member) : ?>
                        <div class="vh360-membership-notice vh360-membership-notice-info">
                            <p><?php esc_html_e('Recurring subscription changes are managed securely through the Stripe Billing Portal. Open the portal to change your plan, update your payment method, view invoices, or manage your subscription.', 'videohub360-memberships'); ?></p>
                            <?php if ($stripe->is_portal_enabled()) : ?>
                                <button type="button" class="vh360-btn vh360-btn-primary vh360-open-portal"><?php esc_html_e('Manage Billing', 'videohub360-memberships'); ?></button>
                            <?php else : ?>
                                <p class="vh360-membership-muted"><?php esc_html_e('Billing portal access is not available right now. Please contact support to change your recurring plan.', 'videohub360-memberships'); ?></p>
                                <?php if ($support_url) : ?><a class="vh360-btn vh360-btn-secondary" href="<?php echo esc_url($support_url); ?>"><?php esc_html_e('Contact Support', 'videohub360-memberships'); ?></a><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="vh360-plan-card-grid vh360-subscription-plans">
                        <?php foreach ($recurring_plans as $key => $plan) : ?>
                            <?php
                                $stripe_same_tier = $membership && VH360_Membership_Plans::get_plan_tier($key) === VH360_Membership_Plans::get_plan_tier($membership->plan_key);
                                $stripe_button_label = !$membership ? $button_label : ($stripe_same_tier ? __('Switch Billing Term', 'videohub360-memberships') : __('Switch to recurring billing', 'videohub360-memberships'));
                            ?>
                            <article class="vh360-subscription-plan-card <?php echo !empty($plan['featured']) ? 'is-featured' : ''; ?>">
                                <?php if (!empty($plan['featured'])) : ?><span class="vh360-plan-pill"><?php esc_html_e('Recommended', 'videohub360-memberships'); ?></span><?php endif; ?>
                                <h4><?php echo esc_html($this->resolve_plan_display_label($plans, $key)); ?></h4>
                                <div class="vh360-plan-type"><?php esc_html_e('Recurring Billing', 'videohub360-memberships'); ?></div>
                                <?php if (!empty($plan['display_price'])) : ?><div class="vh360-plan-price"><?php echo esc_html($plan['display_price']); ?></div><?php endif; ?>
                                <?php if (!empty($plan['display_description'])) : ?><p class="vh360-plan-description"><?php echo esc_html($plan['display_description']); ?></p><?php endif; ?>
                                <?php if (!empty($plan['display_features']) && is_array($plan['display_features'])) : ?><ul class="vh360-plan-features"><?php foreach ($plan['display_features'] as $feature) : ?><li><?php echo esc_html($feature); ?></li><?php endforeach; ?></ul><?php endif; ?>
                                <?php if (!empty($plan['trial_days'])) : ?><p class="vh360-plan-trial"><?php printf(esc_html__('%d-day free trial', 'videohub360-memberships'), $plan['trial_days']); ?></p><?php endif; ?>
                                <?php if (!$is_recurring_stripe_member) : ?>
                                    <button type="button" class="vh360-btn vh360-btn-primary vh360-start-subscription" data-plan-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($stripe_button_label); ?></button>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
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
                <?php if ($membership && isset($membership->billing_mode) && $membership->billing_mode === 'recurring' && !$stripe->is_portal_enabled()) : ?><p class="vh360-membership-muted"><?php esc_html_e('Stripe billing portal is not configured. Contact support for billing changes.', 'videohub360-memberships'); ?></p><?php endif; ?>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get eligible recurring plans for the dashboard.
     *
     * @param array        $plans      Plan registry.
     * @param object|false $membership Current membership.
     * @return array
     */
    private function get_dashboard_recurring_plans($plans, $membership) {
        $eligible = array();
        foreach ($plans as $key => $plan) {
            if (!function_exists('vh360_membership_plan_is_eligible_change')) {
                continue;
            }

            $is_recurring = isset($plan['billing_mode']) && $plan['billing_mode'] === 'recurring';
            if (!$is_recurring || empty($plan['stripe_price_id'])) {
                continue;
            }

            if (!vh360_membership_plan_is_eligible_change($key, $plan, $membership)) {
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
            return $plan_key;
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
