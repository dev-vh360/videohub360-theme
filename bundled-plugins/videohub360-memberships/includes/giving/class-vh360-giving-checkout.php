<?php
/**
 * VideoHub360 Giving Stripe Checkout.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Checkout {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_vh360_giving_create_checkout', array($this, 'ajax_create_checkout'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue() {
        if (function_exists('is_page_template') && is_page_template('template-dashboard.php') && vh360_giving_is_enabled()) {
            wp_enqueue_style('vh360-giving', VH360_MEMBERSHIPS_URL . 'assets/frontend/giving.css', array(), VH360_MEMBERSHIPS_VERSION);
            wp_enqueue_script('vh360-giving', VH360_MEMBERSHIPS_URL . 'assets/frontend/giving.js', array('jquery'), VH360_MEMBERSHIPS_VERSION, true);
            wp_localize_script('vh360-giving', 'vh360Giving', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('vh360_giving_checkout'),
            ));
        }
    }

    public function ajax_create_checkout() {
        check_ajax_referer('vh360_giving_checkout', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'videohub360-memberships')));
        }

        if (!vh360_giving_is_enabled()) {
            wp_send_json_error(array('message' => __('Giving is not enabled.', 'videohub360-memberships')));
        }

        $opts   = vh360_giving_options();
        $amount = isset($_POST['amount']) ? (float) wp_unslash($_POST['amount']) : 0;
        if ($amount < (float) $opts['minimum_amount']) {
            wp_send_json_error(array('message' => __('Please enter a valid giving amount.', 'videohub360-memberships')));
        }

        $fund_id = isset($_POST['fund_id']) ? absint($_POST['fund_id']) : 0;
        $fund    = VH360_Giving_Funds::get_fund($fund_id);
        if (!$fund || empty($fund->enabled)) {
            wp_send_json_error(array('message' => __('Please select an active giving fund.', 'videohub360-memberships')));
        }

        $stripe = VH360_Stripe_Bootstrap::get_instance();
        if (!method_exists($stripe, 'has_payment_credentials') || !$stripe->has_payment_credentials()) {
            wp_send_json_error(array('message' => __('Payment is not configured.', 'videohub360-memberships')));
        }

        $note      = !empty($opts['enable_notes']) && isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        $anonymous = !empty($opts['enable_anonymous']) && !empty($_POST['anonymous']) ? 1 : 0;
        $currency  = strtolower(sanitize_text_field($opts['default_currency']));

        $tx_id = VH360_Giving_Transactions::create(array(
            'user_id'    => $user_id,
            'fund_id'    => $fund->id,
            'fund_key'   => $fund->fund_key,
            'fund_label' => $fund->label,
            'amount'     => $amount,
            'currency'   => $currency,
            'note'       => $note,
            'anonymous'  => $anonymous,
            'source'     => 'dashboard',
        ));

        $customer = $this->get_or_create_customer($user_id);
        if (is_wp_error($customer)) {
            VH360_Giving_Transactions::update($tx_id, array('status' => 'failed'));
            wp_send_json_error(array('message' => $customer->get_error_message()));
        }

        $params = array(
            'mode'                                                   => 'payment',
            'customer'                                               => $customer,
            'line_items[0][price_data][currency]'                    => $currency,
            'line_items[0][price_data][product_data][name]'          => 'VideoHub360 Giving - ' . $fund->label,
            'line_items[0][price_data][unit_amount]'                 => (int) round($amount * 100),
            'line_items[0][quantity]'                                => 1,
            'success_url'                                            => add_query_arg(array('tab' => 'giving', 'vh360_giving_success' => '1', 'session_id' => '{CHECKOUT_SESSION_ID}'), home_url('/dashboard/')),
            'cancel_url'                                             => add_query_arg(array('tab' => 'giving', 'vh360_giving_cancel' => '1'), home_url('/dashboard/')),
            'client_reference_id'                                    => $user_id,
            'metadata[type]'                                         => 'giving',
            'metadata[user_id]'                                      => $user_id,
            'metadata[transaction_id]'                               => $tx_id,
            'metadata[fund_key]'                                     => $fund->fund_key,
            'metadata[source]'                                       => 'dashboard',
            'payment_intent_data[metadata][type]'                    => 'giving',
            'payment_intent_data[metadata][transaction_id]'          => $tx_id,
            'payment_intent_data[metadata][source]'                  => 'dashboard',
        );

        $session = $stripe->api_request('/v1/checkout/sessions', $params);
        if (is_wp_error($session)) {
            VH360_Giving_Transactions::update($tx_id, array('status' => 'failed'));
            wp_send_json_error(array('message' => $session->get_error_message()));
        }

        if (empty($session['url'])) {
            VH360_Giving_Transactions::update($tx_id, array(
                'status'                     => 'failed',
                'stripe_checkout_session_id' => sanitize_text_field($session['id'] ?? ''),
            ));
            wp_send_json_error(array('message' => __('Stripe did not return a checkout URL. Please try again.', 'videohub360-memberships')));
        }

        VH360_Giving_Transactions::update($tx_id, array(
            'stripe_checkout_session_id' => sanitize_text_field($session['id'] ?? ''),
            'stripe_customer_id'         => sanitize_text_field($customer),
            'gateway_customer_id'        => sanitize_text_field($customer),
        ));

        wp_send_json_success(array('checkout_url' => esc_url_raw($session['url'])));
    }

    private function get_or_create_customer($user_id) {
        if (class_exists('VH360_Stripe_Checkout')) {
            return VH360_Stripe_Checkout::get_instance()->get_or_create_stripe_customer($user_id);
        }
        return new WP_Error('stripe_checkout_missing', __('Stripe checkout is unavailable.', 'videohub360-memberships'));
    }
}
