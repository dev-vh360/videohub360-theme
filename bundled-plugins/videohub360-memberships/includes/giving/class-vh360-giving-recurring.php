<?php
/**
 * VideoHub360 recurring Giving records.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Recurring {
    public static function create($data) {
        global $wpdb;
        $table = VH360_Giving_Database::get_recurring_table();
        $now = current_time('mysql');
        $row = wp_parse_args($data, array(
            'status'               => 'incomplete',
            'gateway'              => 'stripe',
            'source'               => 'dashboard',
            'anonymous'            => 0,
            'cancel_at_period_end' => 0,
            'created_at'           => $now,
            'updated_at'           => $now,
        ));
        $wpdb->insert($table, $row);
        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        $table = VH360_Giving_Database::get_recurring_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", absint($id)));
    }

    public static function get_by_subscription($subscription_id) {
        global $wpdb;
        $table = VH360_Giving_Database::get_recurring_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE stripe_subscription_id = %s", sanitize_text_field($subscription_id)));
    }

    public static function update($id, $data) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        return $wpdb->update(VH360_Giving_Database::get_recurring_table(), $data, array('id' => absint($id)));
    }

    public static function update_status($id, $status, $extra = array()) {
        $data = array_merge(array('status' => sanitize_key($status)), $extra);
        return self::update($id, $data);
    }

    public static function for_user($user_id) {
        global $wpdb;
        $table = VH360_Giving_Database::get_recurring_table();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC", absint($user_id)));
    }

    public static function mark_canceled($id, $canceled_at = '') {
        return self::update_status($id, 'canceled', array(
            'cancel_at_period_end' => 0,
            'canceled_at'          => $canceled_at ?: current_time('mysql'),
        ));
    }
}
