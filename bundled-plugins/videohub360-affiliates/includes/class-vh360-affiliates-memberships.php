<?php
/**
 * Optional VideoHub360 Memberships integration.
 *
 * Gracefully detects the Memberships plugin and adds affiliate metadata to
 * Stripe checkout session params for future-proofing.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Memberships {

    /** @var VH360_Affiliates_Memberships|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only hook in if the Memberships plugin is active
        add_action('plugins_loaded', array($this, 'maybe_init'), 20);
    }

    /**
     * Wire up integration hooks only if the Memberships plugin is present.
     */
    public function maybe_init() {
        if (!class_exists('VH360_Memberships')) {
            return;
        }

        // Add affiliate metadata to Stripe checkout session when a referral cookie exists
        add_filter('vh360_stripe_checkout_session_params', array($this, 'inject_stripe_metadata'), 10, 2);

        // Listen for membership granted/revoked hooks for optional logging
        add_action('vh360_membership_granted_from_order', array($this, 'on_membership_granted'), 10, 2);
        add_action('vh360_membership_revoked_from_order',  array($this, 'on_membership_revoked'),  10, 2);
    }

    /**
     * Inject affiliate tracking data into Stripe session metadata.
     *
     * @param array $params  Stripe session params.
     * @param array $context Additional context from Memberships plugin.
     * @return array
     */
    public function inject_stripe_metadata($params, $context = array()) {
        $attribution = VH360_Affiliates_Tracking::get_cookie_attribution();
        if (!$attribution) {
            return $params;
        }

        if (!isset($params['metadata'])) {
            $params['metadata'] = array();
        }

        $params['metadata']['vh360_affiliate_id']       = $attribution['affiliate_id'];
        $params['metadata']['vh360_affiliate_code']     = $attribution['affiliate_code'];
        $params['metadata']['vh360_affiliate_visit_id'] = $attribution['visit_id'];

        return $params;
    }

    /**
     * Log when a membership is granted via an order attributed to an affiliate.
     *
     * @param int $user_id
     * @param int $order_id
     */
    public function on_membership_granted($user_id, $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $aff_id = (int) $order->get_meta('_vh360_affiliate_id');
        if (!$aff_id) {
            return;
        }
        // Log for audit purposes only – no recurring commission in v1
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("VH360 Affiliates: Membership granted for user {$user_id} via order {$order_id} attributed to affiliate {$aff_id}.");
        }
    }

    /**
     * Log when a membership is revoked for an order attributed to an affiliate.
     *
     * @param int $user_id
     * @param int $order_id
     */
    public function on_membership_revoked($user_id, $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $aff_id = (int) $order->get_meta('_vh360_affiliate_id');
        if (!$aff_id) {
            return;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("VH360 Affiliates: Membership revoked for user {$user_id} via order {$order_id} attributed to affiliate {$aff_id}.");
        }
    }
}
