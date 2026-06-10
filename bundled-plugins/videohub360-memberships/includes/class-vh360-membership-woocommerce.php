<?php
/**
 * WooCommerce Integration
 *
 * Handles WooCommerce order processing and membership granting.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_WooCommerce {

    /**
     * Singleton instance
     *
     * @var VH360_Membership_WooCommerce
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return VH360_Membership_WooCommerce
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
        // Hook into order completion
        add_action('woocommerce_order_status_completed', array($this, 'process_order'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'process_order'), 10, 1);

        // Hook into order cancellation/refund
        add_action('woocommerce_order_status_refunded', array($this, 'revoke_order_access'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'revoke_order_access'), 10, 1);
    }

    /**
     * Process order for membership grants
     *
     * @param int $order_id Order ID
     */
    public function process_order($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        $membership_granted = false;
        $course_access_granted = false;
        $membership_processed = (bool) get_post_meta($order_id, '_vh360_membership_processed', true);
        $course_processed = (bool) get_post_meta($order_id, '_vh360_course_entitlements_processed', true);

        // Check each item for membership mappings and linked course products.
        foreach ($order->get_items() as $item) {
            $product_ids = array_filter(array_unique(array_map('absint', array(
                $item->get_product_id(),
                method_exists($item, 'get_variation_id') ? $item->get_variation_id() : 0,
            ))));

            foreach ($product_ids as $product_id) {
                if (!$membership_processed) {
                    // Get membership mapping.
                    $mapping = VH360_Membership_Plans::get_product_membership_mapping($product_id);

                    if ($mapping && !empty($mapping['plan_key'])) {
                        // Process membership grant/extend.
                        $this->grant_membership_from_product(
                            $user_id,
                            $mapping,
                            $order_id
                        );

                        $membership_granted = true;
                    }
                }

                if (!$course_processed && function_exists('vh360_get_courses_for_product') && function_exists('vh360_grant_course_entitlement')) {
                    $courses = vh360_get_courses_for_product($product_id);

                    foreach ($courses as $course) {
                        $granted = vh360_grant_course_entitlement($user_id, $course->term_id, $product_id, $order_id);
                        if ($granted) {
                            $course_access_granted = true;
                        }
                    }
                }
            }
        }

        if ($membership_granted) {
            // Mark order as processed.
            update_post_meta($order_id, '_vh360_membership_processed', current_time('mysql'));

            // Add order note.
            $order->add_order_note(__('VH360 membership granted/extended.', 'videohub360-memberships'));
        }

        if ($course_access_granted) {
            update_post_meta($order_id, '_vh360_course_entitlements_processed', current_time('mysql'));
            $order->add_order_note(__('VH360 course access granted.', 'videohub360-memberships'));
        }
    }

    /**
     * Grant membership from product purchase
     *
     * @param int $user_id User ID
     * @param array $mapping Membership mapping data
     * @param int $order_id Order ID
     */
    private function grant_membership_from_product($user_id, $mapping, $order_id) {
        $api = VH360_Membership_API::get_instance();

        $plan_key = $mapping['plan_key'];
        $duration = $mapping['duration'];
        $duration_unit = $mapping['duration_unit'];
        $grant_type = $mapping['grant_type'];

        // Check if user already has this plan
        $existing_memberships = vh360_get_user_memberships($user_id, 'active');
        $existing_membership_id = null;

        foreach ($existing_memberships as $membership) {
            if ($membership->plan_key === $plan_key) {
                $existing_membership_id = $membership->id;
                break;
            }
        }

        if ($grant_type === 'extend' && $existing_membership_id) {
            // Extend existing membership
            $api->extend_membership($existing_membership_id, $duration, $duration_unit);
        } else {
            // Grant new membership
            $api->create_membership($user_id, $plan_key, $duration, $duration_unit, $order_id);
        }

        do_action('vh360_membership_granted_from_order', $user_id, $plan_key, $order_id);
    }

    /**
     * Revoke memberships for refunded/cancelled orders
     *
     * @param int $order_id Order ID
     */
    public function revoke_order_memberships($order_id) {
        // Check if already processed
        if (get_post_meta($order_id, '_vh360_membership_revoked', true)) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        // Find memberships tied to this order
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();

        $memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE source_order_id = %d",
            $order_id
        ));

        if (!$memberships) {
            update_post_meta($order_id, '_vh360_membership_revoked', current_time('mysql'));
            return;
        }

        $api = VH360_Membership_API::get_instance();

        foreach ($memberships as $membership) {
            // Only revoke if still active
            if ($membership->status === 'active') {
                // Cancel the membership
                $wpdb->update(
                    $table,
                    array(
                        'status' => 'cancelled',
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $membership->id),
                    array('%s', '%s'),
                    array('%d')
                );

                // Log event
                $events_table = VH360_Membership_Database::get_events_table();
                $wpdb->insert(
                    $events_table,
                    array(
                        'membership_id' => $membership->id,
                        'event_type' => 'cancelled',
                        'event_data' => wp_json_encode(array(
                            'reason' => 'order_refunded_cancelled',
                            'order_id' => $order_id
                        )),
                        'actor_id' => null,
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%d', '%s')
                );
            }
        }

        // Mark as revoked
        update_post_meta($order_id, '_vh360_membership_revoked', current_time('mysql'));

        // Add order note
        $order->add_order_note(__('VH360 memberships cancelled due to order status change.', 'videohub360-memberships'));

        do_action('vh360_membership_revoked_from_order', $user_id, $order_id);
    }

    /**
     * Revoke all VH360 access granted by an order.
     *
     * @param int $order_id Order ID
     */
    public function revoke_order_access($order_id) {
        $this->revoke_order_memberships($order_id);
        $this->revoke_order_course_entitlements($order_id);
    }

    /**
     * Revoke course entitlements for refunded/cancelled orders.
     *
     * @param int $order_id Order ID
     */
    private function revoke_order_course_entitlements($order_id) {
        if (get_post_meta($order_id, '_vh360_course_entitlements_revoked', true)) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order || !function_exists('vh360_revoke_course_entitlements_for_order')) {
            return;
        }

        $revoked = vh360_revoke_course_entitlements_for_order($order_id);

        if ($revoked > 0) {
            update_post_meta($order_id, '_vh360_course_entitlements_revoked', current_time('mysql'));
            $order->add_order_note(__('VH360 course access revoked due to order status change.', 'videohub360-memberships'));
        }
    }

}
