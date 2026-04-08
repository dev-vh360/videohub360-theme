<?php
/**
 * Frontend Subscription Management
 *
 * Provides user-facing subscription management through a shortcode
 * and AJAX-driven interface. Users can view their plan, billing status,
 * cancel, reactivate, and access the Stripe billing portal.
 *
 * @package VideoHub360_Memberships
 * @since 2.0.0
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
        $plan_label = isset($plans[$membership->plan_key]) ? $plans[$membership->plan_key]['label'] : $membership->plan_key;
        
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
        $plans = VH360_Membership_Plans::get_plan_registry();
        $stripe = VH360_Stripe_Bootstrap::get_instance();
        $recurring_plans = VH360_Membership_Plans::get_recurring_plans();
        
        ob_start();
        ?>
        <div class="vh360-membership-management" id="vh360-membership-management">
            
            <?php if ($membership) : 
                $plan_label = isset($plans[$membership->plan_key]) ? $plans[$membership->plan_key]['label'] : $membership->plan_key;
                $is_recurring = isset($membership->billing_mode) && $membership->billing_mode === 'recurring';
                $cancel_pending = isset($membership->cancel_at_period_end) && $membership->cancel_at_period_end;
                $sub_status = isset($membership->subscription_status) ? $membership->subscription_status : '';
            ?>
                <div class="vh360-membership-card">
                    <div class="vh360-membership-card-header">
                        <h3><?php echo esc_html($plan_label); ?></h3>
                        <span class="vh360-membership-badge vh360-membership-badge-<?php echo esc_attr($membership->status); ?>">
                            <?php echo esc_html(ucfirst($membership->status)); ?>
                        </span>
                    </div>
                    
                    <div class="vh360-membership-card-body">
                        <div class="vh360-membership-detail">
                            <span class="vh360-membership-detail-label"><?php esc_html_e('Type', 'videohub360-memberships'); ?></span>
                            <span class="vh360-membership-detail-value">
                                <?php echo $is_recurring ? esc_html__('Recurring Subscription', 'videohub360-memberships') : esc_html__('Fixed-Term', 'videohub360-memberships'); ?>
                            </span>
                        </div>
                        
                        <?php if ($is_recurring && $sub_status) : ?>
                        <div class="vh360-membership-detail">
                            <span class="vh360-membership-detail-label"><?php esc_html_e('Billing Status', 'videohub360-memberships'); ?></span>
                            <span class="vh360-membership-detail-value vh360-billing-status-<?php echo esc_attr($sub_status); ?>">
                                <?php echo esc_html($this->format_subscription_status($sub_status)); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($is_recurring && !empty($membership->current_period_end)) : ?>
                        <div class="vh360-membership-detail">
                            <span class="vh360-membership-detail-label">
                                <?php echo $cancel_pending ? esc_html__('Access Until', 'videohub360-memberships') : esc_html__('Next Billing Date', 'videohub360-memberships'); ?>
                            </span>
                            <span class="vh360-membership-detail-value">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->current_period_end))); ?>
                            </span>
                        </div>
                        <?php elseif (!$is_recurring && $membership->expires_at) : ?>
                        <div class="vh360-membership-detail">
                            <span class="vh360-membership-detail-label"><?php esc_html_e('Expires', 'videohub360-memberships'); ?></span>
                            <span class="vh360-membership-detail-value">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($membership->expires_at))); ?>
                            </span>
                        </div>
                        <?php elseif (!$membership->expires_at) : ?>
                        <div class="vh360-membership-detail">
                            <span class="vh360-membership-detail-label"><?php esc_html_e('Expires', 'videohub360-memberships'); ?></span>
                            <span class="vh360-membership-detail-value"><?php esc_html_e('Never (Lifetime)', 'videohub360-memberships'); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cancel_pending) : ?>
                        <div class="vh360-membership-notice vh360-membership-notice-warning">
                            <?php esc_html_e('Your subscription is scheduled for cancellation at the end of the current billing period.', 'videohub360-memberships'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($sub_status === 'past_due') : ?>
                        <div class="vh360-membership-notice vh360-membership-notice-error">
                            <?php esc_html_e('Payment failed. Please update your payment method to keep your access.', 'videohub360-memberships'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vh360-membership-card-actions">
                        <?php if ($is_recurring) : ?>
                            <?php if ($cancel_pending) : ?>
                                <button type="button" 
                                        class="vh360-btn vh360-btn-primary vh360-reactivate-subscription" 
                                        data-membership-id="<?php echo esc_attr($membership->id); ?>">
                                    <?php esc_html_e('Reactivate Subscription', 'videohub360-memberships'); ?>
                                </button>
                            <?php else : ?>
                                <button type="button" 
                                        class="vh360-btn vh360-btn-danger vh360-cancel-subscription" 
                                        data-membership-id="<?php echo esc_attr($membership->id); ?>">
                                    <?php esc_html_e('Cancel Subscription', 'videohub360-memberships'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($stripe->is_portal_enabled()) : ?>
                                <button type="button" class="vh360-btn vh360-btn-secondary vh360-open-portal">
                                    <?php esc_html_e('Manage Billing', 'videohub360-memberships'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php else : ?>
                <div class="vh360-no-membership">
                    <p><?php esc_html_e('You do not have an active membership.', 'videohub360-memberships'); ?></p>
                    
                    <?php if (!empty($recurring_plans) && $stripe->is_configured()) : 
                        $card_options = get_option('vh360_membership_options', array());
                        $card_style_props = array();
                        $style_map = array(
                            'subscription_card_bg_color'          => '--vh360-card-bg',
                            'subscription_card_border_color'      => '--vh360-card-border',
                            'subscription_card_title_color'       => '--vh360-card-title',
                            'subscription_card_price_color'       => '--vh360-card-price',
                            'subscription_card_text_color'        => '--vh360-card-text',
                            'subscription_card_button_bg_color'   => '--vh360-card-btn-bg',
                            'subscription_card_button_text_color' => '--vh360-card-btn-text',
                        );
                        foreach ($style_map as $option_key => $css_var) {
                            if (!empty($card_options[$option_key])) {
                                $color = sanitize_hex_color($card_options[$option_key]);
                                if ($color) {
                                    $card_style_props[] = $css_var . ':' . $color;
                                }
                            }
                        }
                        $card_style_attr = !empty($card_style_props) ? ' style="' . esc_attr(implode(';', $card_style_props)) . '"' : '';
                        $button_label = !empty($card_options['subscription_card_button_label']) ? $card_options['subscription_card_button_label'] : __('Subscribe', 'videohub360-memberships');
                    ?>
                        <h4><?php esc_html_e('Available Plans', 'videohub360-memberships'); ?></h4>
                        <div class="vh360-subscription-plans"<?php echo $card_style_attr; ?>>
                            <?php foreach ($recurring_plans as $key => $plan) : 
                                $plan_title = !empty($plan['display_label']) ? $plan['display_label'] : $plan['label'];
                                $plan_price = isset($plan['display_price']) ? $plan['display_price'] : '';
                                $plan_desc = isset($plan['display_description']) ? $plan['display_description'] : '';
                                $plan_features = isset($plan['display_features']) && is_array($plan['display_features']) ? $plan['display_features'] : array();
                            ?>
                                <div class="vh360-subscription-plan-card">
                                    <h4><?php echo esc_html($plan_title); ?></h4>
                                    <?php if (!empty($plan_price)) : ?>
                                        <div class="vh360-plan-price"><?php echo esc_html($plan_price); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($plan_desc)) : ?>
                                        <p class="vh360-plan-description"><?php echo esc_html($plan_desc); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($plan_features)) : ?>
                                        <ul class="vh360-plan-features">
                                            <?php foreach ($plan_features as $feature) : ?>
                                                <li><?php echo esc_html($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <?php if (!empty($plan['trial_days'])) : ?>
                                        <p class="vh360-plan-trial"><?php printf(esc_html__('%d-day free trial', 'videohub360-memberships'), $plan['trial_days']); ?></p>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="vh360-btn vh360-btn-primary vh360-start-subscription" 
                                            data-plan-key="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($button_label); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $options = get_option('vh360_membership_options', array());
                    $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
                    if ($pricing_url) : ?>
                        <p><a href="<?php echo esc_url($pricing_url); ?>" class="vh360-btn vh360-btn-secondary"><?php esc_html_e('View All Plans', 'videohub360-memberships'); ?></a></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
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
