<?php
/**
 * WooCommerce integration: order attribution and commission creation.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_WooCommerce {

    /** @var VH360_Affiliates_WooCommerce|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Attribution: store affiliate data on order creation
        add_action('woocommerce_checkout_create_order',           array($this, 'attach_attribution_to_order'),     10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'attach_attribution_to_line_item'), 10, 4);

        // Commission creation on processing / completed
        add_action('woocommerce_order_status_processing', array($this, 'create_commissions'), 10, 1);
        add_action('woocommerce_order_status_completed',  array($this, 'create_commissions'), 10, 1);

        // Refund / cancellation reversals
        add_action('woocommerce_order_status_cancelled', array($this, 'handle_cancellation'), 10, 1);
        add_action('woocommerce_order_status_failed',    array($this, 'handle_failed'),       10, 1);
        add_action('woocommerce_order_status_refunded',  array($this, 'handle_refunded'),     10, 1);
        add_action('woocommerce_order_refunded',         array($this, 'handle_partial_refund'), 10, 2);
    }

    // -----------------------------------------------------------------------
    // Attribution
    // -----------------------------------------------------------------------

    /**
     * Store affiliate attribution in order meta when order is created.
     *
     * @param WC_Order $order
     * @param array    $data
     */
    public function attach_attribution_to_order($order, $data) {
        $attribution = VH360_Affiliates_Tracking::get_cookie_attribution();
        if (!$attribution) {
            return;
        }

        $settings  = vh360_affiliates_get_settings();
        $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($attribution['affiliate_id']);

        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        // Block self-referral
        if (empty($settings['allow_self_referrals'])) {
            $order_user_id = (int) $order->get_user_id();
            if ($order_user_id && (int) $affiliate->user_id === $order_user_id) {
                return;
            }
        }

        $order->update_meta_data('_vh360_affiliate_id',           (int) $affiliate->id);
        $order->update_meta_data('_vh360_affiliate_code',         sanitize_text_field($affiliate->affiliate_code));
        $order->update_meta_data('_vh360_affiliate_visit_id',     (int) $attribution['visit_id']);
        $order->update_meta_data('_vh360_affiliate_attributed_at', current_time('mysql'));

        $order->add_order_note(
            sprintf(
                /* translators: %s: affiliate code */
                __('VH360 affiliate attribution recorded: affiliate %s.', 'videohub360-affiliates'),
                esc_html($affiliate->affiliate_code)
            )
        );
    }

    /**
     * Store affiliate data on individual order line items.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    public function attach_attribution_to_line_item($item, $cart_item_key, $values, $order) {
        $aff_id   = (int) $order->get_meta('_vh360_affiliate_id');
        $visit_id = (int) $order->get_meta('_vh360_affiliate_visit_id');

        if (!$aff_id) {
            return;
        }

        $item->update_meta_data('_vh360_affiliate_id',       $aff_id);
        $item->update_meta_data('_vh360_affiliate_visit_id', $visit_id);
    }

    // -----------------------------------------------------------------------
    // Commission creation
    // -----------------------------------------------------------------------

    /**
     * Create commissions for an order (idempotent).
     *
     * @param int $order_id
     */
    public function create_commissions($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $aff_id = (int) $order->get_meta('_vh360_affiliate_id');
        if (!$aff_id) {
            return;
        }

        $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($aff_id);
        if (!$affiliate || $affiliate->status !== 'active') {
            return;
        }

        $settings = vh360_affiliates_get_settings();

        // Block self-referral
        if (empty($settings['allow_self_referrals'])) {
            $order_user_id = (int) $order->get_user_id();
            if ($order_user_id && (int) $affiliate->user_id === $order_user_id) {
                return;
            }
        }

        $visit_id = (int) $order->get_meta('_vh360_affiliate_visit_id');
        $currency = $order->get_currency();
        $created  = false;

        foreach ($order->get_items() as $item_id => $item) {
            /** @var WC_Order_Item_Product $item */
            if (!($item instanceof WC_Order_Item_Product)) {
                continue;
            }

            $product_id = (int) $item->get_product_id();
            $variation_id = (int) $item->get_variation_id();

            // Idempotency check
            if (VH360_Affiliates_Database::commission_exists($order_id, $item_id)) {
                continue;
            }

            // Skip excluded products
            if ($this->is_product_excluded($product_id, $variation_id)) {
                continue;
            }

            // Calculate base: item subtotal (after discounts, excl. tax)
            $base_amount = (float) $item->get_subtotal();
            if ($base_amount <= 0) {
                continue;
            }

            // Determine commission type/rate
            list($comm_type, $comm_rate) = $this->get_product_commission($product_id, $variation_id, $affiliate, $settings);

            if ($comm_type === 'percentage') {
                $comm_amount = $base_amount * $comm_rate / 100;
            } else {
                // flat: rate per line item
                $comm_amount = (float) $comm_rate;
            }

            if ($comm_amount <= 0) {
                continue;
            }

            // Insert referral row
            $referral_id = VH360_Affiliates_Database::insert_referral(array(
                'affiliate_id'  => $aff_id,
                'visit_id'      => $visit_id ?: null,
                'user_id'       => $order->get_user_id() ?: null,
                'order_id'      => $order_id,
                'order_item_id' => $item_id,
                'product_id'    => $product_id,
                'amount'        => $base_amount,
                'currency'      => $currency,
                'status'        => 'pending',
            ));

            // Insert commission row
            VH360_Affiliates_Database::insert_commission(array(
                'affiliate_id'    => $aff_id,
                'referral_id'     => $referral_id ?: null,
                'order_id'        => $order_id,
                'order_item_id'   => $item_id,
                'product_id'      => $product_id,
                'base_amount'     => $base_amount,
                'commission_type' => $comm_type,
                'commission_rate' => $comm_rate,
                'commission_amount' => round($comm_amount, 2),
                'currency'        => $currency,
                'status'          => $settings['commission_status'] ?? 'pending',
            ));

            $created = true;
        }

        if ($created) {
            // Mark visit as converted
            if ($visit_id) {
                VH360_Affiliates_Database::mark_visit_converted($visit_id);
            }

            $order->add_order_note(
                __('VH360 affiliate commissions created (pending).', 'videohub360-affiliates')
            );

            // Send commission created email
            $aff_user = get_userdata($affiliate->user_id);
            if ($aff_user) {
                vh360_affiliates_send_email(
                    $aff_user->user_email,
                    __('New commission pending', 'videohub360-affiliates'),
                    sprintf(
                        /* translators: %s: order ID */
                        __("A new commission has been created for order #%s. It is pending review.", 'videohub360-affiliates'),
                        $order_id
                    )
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Cancellation / refund handlers
    // -----------------------------------------------------------------------

    /**
     * Reject pending commissions when order is cancelled.
     *
     * @param int $order_id
     */
    public function handle_cancellation($order_id) {
        $this->reject_pending_commissions(
            $order_id,
            __('VH360 affiliate commission rejected because order was cancelled.', 'videohub360-affiliates'),
            'cancelled'
        );
    }

    /**
     * Reject pending commissions when order fails.
     *
     * @param int $order_id
     */
    public function handle_failed($order_id) {
        $this->reject_pending_commissions(
            $order_id,
            __('VH360 affiliate commission rejected because order failed.', 'videohub360-affiliates'),
            'failed'
        );
    }

    /**
     * Reverse/reject commissions when order is fully refunded.
     *
     * @param int $order_id
     */
    public function handle_refunded($order_id) {
        $order       = wc_get_order($order_id);
        $commissions = VH360_Affiliates_Database::get_commissions_by_order($order_id);

        if (empty($commissions)) {
            return;
        }

        foreach ($commissions as $commission) {
            if ($commission->status === 'paid') {
                VH360_Affiliates_Database::update_commission_status(
                    $commission->id,
                    'reversed',
                    array('reason' => 'order_fully_refunded')
                );
                // Update associated referral
                if ($commission->referral_id) {
                    VH360_Affiliates_Database::update_referral_status($commission->referral_id, 'reversed');
                }
            } elseif (in_array($commission->status, array('pending', 'approved'), true)) {
                VH360_Affiliates_Database::update_commission_status(
                    $commission->id,
                    'rejected',
                    array('reason' => 'order_fully_refunded')
                );
                if ($commission->referral_id) {
                    VH360_Affiliates_Database::update_referral_status($commission->referral_id, 'rejected');
                }
            }
        }

        if ($order) {
            $order->add_order_note(
                __('VH360 affiliate commission reversed because order was fully refunded.', 'videohub360-affiliates')
            );
        }
    }

    /**
     * Flag commissions for manual review on partial refunds.
     *
     * @param int $order_id
     * @param int $refund_id
     */
    public function handle_partial_refund($order_id, $refund_id) {
        // Check if fully refunded
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Only act if NOT fully refunded (full refund is handled by woocommerce_order_status_refunded)
        $total    = (float) $order->get_total();
        $refunded = (float) $order->get_total_refunded();

        if ($refunded >= $total) {
            return; // Fully refunded – handled elsewhere
        }

        $commissions = VH360_Affiliates_Database::get_commissions_by_order($order_id);
        if (empty($commissions)) {
            return;
        }

        $order->add_order_note(
            __('VH360 affiliate commission requires manual review because order was partially refunded.', 'videohub360-affiliates')
        );
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Reject all pending/approved commissions for an order.
     *
     * @param int    $order_id
     * @param string $note
     * @param string $reason
     */
    private function reject_pending_commissions($order_id, $note, $reason) {
        $order       = wc_get_order($order_id);
        $commissions = VH360_Affiliates_Database::get_commissions_by_order($order_id);

        if (empty($commissions)) {
            return;
        }

        foreach ($commissions as $commission) {
            if (in_array($commission->status, array('pending', 'approved'), true)) {
                VH360_Affiliates_Database::update_commission_status(
                    $commission->id,
                    'rejected',
                    array('reason' => $reason)
                );
                if ($commission->referral_id) {
                    VH360_Affiliates_Database::update_referral_status($commission->referral_id, 'rejected');
                }
            }
        }

        if ($order) {
            $order->add_order_note($note);
        }
    }

    /**
     * Check whether a product is excluded from affiliate commissions.
     *
     * @param int $product_id
     * @param int $variation_id
     * @return bool
     */
    private function is_product_excluded($product_id, $variation_id = 0) {
        if ($variation_id) {
            $var_exclude = get_post_meta($variation_id, '_vh360_affiliate_exclude', true);
            if ($var_exclude !== '') {
                return (bool) $var_exclude;
            }
        }
        return (bool) get_post_meta($product_id, '_vh360_affiliate_exclude', true);
    }

    /**
     * Determine commission type and rate for a product line item.
     *
     * Priority: variation override → product override → affiliate override → global default.
     *
     * @param int    $product_id
     * @param int    $variation_id
     * @param object $affiliate
     * @param array  $settings
     * @return array [type, rate]
     */
    private function get_product_commission($product_id, $variation_id, $affiliate, $settings) {
        $source_id = $product_id;

        // Try variation first
        if ($variation_id) {
            $var_enabled    = get_post_meta($variation_id, '_vh360_affiliate_enabled', true);
            $var_use_global = get_post_meta($variation_id, '_vh360_affiliate_use_global', true);
            $var_type       = get_post_meta($variation_id, '_vh360_affiliate_commission_type', true);
            $var_rate       = get_post_meta($variation_id, '_vh360_affiliate_commission_rate', true);

            if ($var_enabled === '1' && $var_use_global !== '1' && $var_type && $var_rate !== '') {
                return array($var_type, (float) $var_rate);
            }
            if ($var_enabled === '1' && $var_use_global === '1') {
                $source_id = 0; // fall through to global
            }
        }

        if ($source_id) {
            $enabled    = get_post_meta($product_id, '_vh360_affiliate_enabled', true);
            $use_global = get_post_meta($product_id, '_vh360_affiliate_use_global', true);
            $type       = get_post_meta($product_id, '_vh360_affiliate_commission_type', true);
            $rate       = get_post_meta($product_id, '_vh360_affiliate_commission_rate', true);

            if ($enabled === '1' && $use_global !== '1' && $type && $rate !== '') {
                return array($type, (float) $rate);
            }
        }

        // Affiliate-level override
        if ($affiliate->commission_rate !== null && $affiliate->commission_type) {
            return array($affiliate->commission_type, (float) $affiliate->commission_rate);
        }

        // Global default
        return array(
            $settings['default_commission_type'] ?? 'percentage',
            (float) ($settings['default_commission_rate'] ?? 20),
        );
    }
}
