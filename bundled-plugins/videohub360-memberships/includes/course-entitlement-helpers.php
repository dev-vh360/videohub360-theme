<?php
/**
 * Course entitlement helpers.
 *
 * Dedicated access layer for individual course purchases. Course terms keep
 * their WooCommerce product link in term meta while ownership is stored in the
 * vh360_course_entitlements table.
 *
 * @package VideoHub360_Memberships
 * @since 1.1.0
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('vh360_get_course_product_id')) {
    function vh360_get_course_product_id($course_term_id) {
        return absint(get_term_meta(absint($course_term_id), '_vh360_course_product_id', true));
    }
}

if (!function_exists('vh360_get_course_purchase_mode')) {
    function vh360_get_course_purchase_mode($course_term_id) {
        $course_term_id = absint($course_term_id);
        $mode = sanitize_key((string) get_term_meta($course_term_id, '_vh360_course_purchase_mode', true));
        $allowed = array('none', 'product', 'membership', 'both');

        if (in_array($mode, $allowed, true)) {
            return $mode;
        }

        $product_id = vh360_get_course_product_id($course_term_id);
        $required_membership = function_exists('videohub360_get_course_required_membership')
            ? videohub360_get_course_required_membership($course_term_id)
            : get_term_meta($course_term_id, '_vh360_course_required_membership', true);

        if ($product_id > 0) {
            return 'product';
        }

        if (!empty($required_membership)) {
            return 'membership';
        }

        return 'none';
    }
}

if (!function_exists('vh360_user_has_course_entitlement')) {
    function vh360_user_has_course_entitlement($user_id, $course_term_id) {
        $user_id = absint($user_id ?: get_current_user_id());
        $course_term_id = absint($course_term_id);

        if (!$user_id || !$course_term_id || !class_exists('VH360_Membership_Database')) {
            return false;
        }

        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        global $wpdb;
        $table = VH360_Membership_Database::get_course_entitlements_table();

        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE user_id = %d
             AND course_term_id = %d
             AND status = 'active'
             AND (starts_at IS NULL OR starts_at <= NOW())
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id,
            $course_term_id
        ));

        return apply_filters('vh360_user_has_course_entitlement', $count > 0, $user_id, $course_term_id);
    }
}

if (!function_exists('vh360_user_can_access_course')) {
    function vh360_user_can_access_course($user_id, $course_term_id) {
        $user_id = absint($user_id ?: get_current_user_id());
        $course_term_id = absint($course_term_id);

        if (!$course_term_id) {
            return false;
        }

        if ($user_id && user_can($user_id, 'manage_options')) {
            return true;
        }

        $mode = vh360_get_course_purchase_mode($course_term_id);
        $required_membership = function_exists('videohub360_get_course_required_membership')
            ? videohub360_get_course_required_membership($course_term_id)
            : get_term_meta($course_term_id, '_vh360_course_required_membership', true);

        if ('none' === $mode) {
            return true;
        }

        $has_entitlement = $user_id ? vh360_user_has_course_entitlement($user_id, $course_term_id) : false;
        $has_membership = false;

        if ($user_id && !empty($required_membership)) {
            if ('any' === $required_membership) {
                $has_membership = function_exists('vh360_user_has_active_membership')
                    ? vh360_user_has_active_membership($user_id)
                    : false;
            } else {
                $has_membership = function_exists('vh360_user_has_active_membership')
                    ? vh360_user_has_active_membership($user_id, $required_membership)
                    : false;
            }
        }

        if ('product' === $mode) {
            return $has_entitlement;
        }

        if ('membership' === $mode) {
            return empty($required_membership) ? true : $has_membership;
        }

        if ('both' === $mode) {
            return $has_entitlement || (!empty($required_membership) && $has_membership);
        }

        return false;
    }
}

if (!function_exists('vh360_grant_course_entitlement')) {
    function vh360_grant_course_entitlement($user_id, $course_term_id, $product_id, $order_id, $duration = 0, $duration_unit = 'lifetime') {
        $user_id = absint($user_id);
        $course_term_id = absint($course_term_id);
        $product_id = absint($product_id);
        $order_id = absint($order_id);
        $duration = absint($duration);
        $duration_unit = sanitize_key($duration_unit ?: 'lifetime');

        if (!$user_id || !$course_term_id || !class_exists('VH360_Membership_Database')) {
            return false;
        }

        global $wpdb;
        $table = VH360_Membership_Database::get_course_entitlements_table();
        $now = current_time('mysql');

        if ($order_id) {
            $existing_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND course_term_id = %d AND source_order_id = %d LIMIT 1",
                $user_id,
                $course_term_id,
                $order_id
            ));

            if ($existing_id) {
                $wpdb->update(
                    $table,
                    array('status' => 'active', 'product_id' => $product_id ?: null, 'updated_at' => $now),
                    array('id' => $existing_id),
                    array('%s', '%d', '%s'),
                    array('%d')
                );
                return $existing_id;
            }
        }

        $expires_at = null;
        if ($duration > 0 && 'lifetime' !== $duration_unit) {
            $timestamp = current_time('timestamp');
            switch ($duration_unit) {
                case 'months':
                    $timestamp = strtotime('+' . $duration . ' months', $timestamp);
                    break;
                case 'years':
                    $timestamp = strtotime('+' . $duration . ' years', $timestamp);
                    break;
                default:
                    $timestamp = strtotime('+' . $duration . ' days', $timestamp);
                    break;
            }
            $expires_at = date('Y-m-d H:i:s', $timestamp);
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'course_term_id' => $course_term_id,
                'product_id' => $product_id ?: null,
                'source_order_id' => $order_id ?: null,
                'status' => 'active',
                'starts_at' => $now,
                'expires_at' => $expires_at,
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            return false;
        }

        do_action('vh360_course_entitlement_granted', $user_id, $course_term_id, $product_id, $order_id);

        return (int) $wpdb->insert_id;
    }
}

if (!function_exists('vh360_revoke_course_entitlements_for_order')) {
    function vh360_revoke_course_entitlements_for_order($order_id) {
        $order_id = absint($order_id);

        if (!$order_id || !class_exists('VH360_Membership_Database')) {
            return 0;
        }

        global $wpdb;
        $table = VH360_Membership_Database::get_course_entitlements_table();

        $updated = $wpdb->update(
            $table,
            array('status' => 'cancelled', 'updated_at' => current_time('mysql')),
            array('source_order_id' => $order_id, 'status' => 'active'),
            array('%s', '%s'),
            array('%d', '%s')
        );

        do_action('vh360_course_entitlements_revoked_for_order', $order_id, (int) $updated);

        return (int) $updated;
    }
}

if (!function_exists('vh360_get_course_purchase_url')) {
    function vh360_get_course_purchase_url($course_term_id) {
        $course_term_id = absint($course_term_id);
        $product_id = vh360_get_course_product_id($course_term_id);

        if (!$product_id || !function_exists('wc_get_product')) {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product || get_post_status($product_id) !== 'publish') {
            return '';
        }

        $options = get_option('vh360_membership_options', array());
        $destination = isset($options['course_purchase_destination']) ? sanitize_key($options['course_purchase_destination']) : 'product_page';
        if (!in_array($destination, array('product_page', 'add_to_cart'), true)) {
            $destination = 'product_page';
        }

        $destination = apply_filters('vh360_course_purchase_destination', $destination, $course_term_id, $product_id, $product);
        $destination = in_array($destination, array('product_page', 'add_to_cart'), true) ? $destination : 'product_page';

        if ('add_to_cart' === $destination) {
            $url = $product->add_to_cart_url();
            if (empty($url)) {
                $url = get_permalink($product_id);
            }
        } else {
            $url = get_permalink($product_id);
        }

        if (is_wp_error($url) || empty($url)) {
            $url = '';
        }

        return apply_filters('vh360_course_purchase_url', $url, $course_term_id, $product_id, $product, $destination);
    }
}

if (!function_exists('vh360_get_courses_for_product')) {
    function vh360_get_courses_for_product($product_id) {
        $product_id = absint($product_id);
        if (!$product_id || !taxonomy_exists('videohub360_series')) {
            return array();
        }

        $terms = get_terms(array(
            'taxonomy' => 'videohub360_series',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_vh360_course_product_id',
                    'value' => $product_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
        ));

        return is_wp_error($terms) ? array() : $terms;
    }
}
