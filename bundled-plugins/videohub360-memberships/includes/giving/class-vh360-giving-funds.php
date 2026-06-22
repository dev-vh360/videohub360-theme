<?php
/**
 * VideoHub360 Giving funds data access.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Funds {
    public static function get_funds($enabled_only = false, $include_deleted = false) {
        global $wpdb;
        $table = VH360_Giving_Database::get_funds_table();
        $where = array();

        if (!$include_deleted) {
            $where[] = 'deleted_at IS NULL';
        }
        if ($enabled_only) {
            $where[] = 'enabled = 1';
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return $wpdb->get_results("SELECT * FROM {$table} {$where_sql} ORDER BY display_order ASC, label ASC");
    }

    public static function get_fund($id, $include_deleted = false) {
        global $wpdb;
        $table = VH360_Giving_Database::get_funds_table();
        $deleted_sql = $include_deleted ? '' : ' AND deleted_at IS NULL';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d{$deleted_sql}", absint($id)));
    }

    public static function save_fund($data) {
        global $wpdb;
        $table = VH360_Giving_Database::get_funds_table();
        $now = current_time('mysql');
        $label = isset($data['label']) ? sanitize_text_field($data['label']) : '';
        $fund_key = !empty($data['fund_key']) ? sanitize_key($data['fund_key']) : sanitize_title($label);

        $row = array(
            'fund_key'          => $fund_key,
            'label'             => $label,
            'description'       => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'suggested_amounts' => isset($data['suggested_amounts']) ? sanitize_text_field($data['suggested_amounts']) : '',
            'default_amount'    => (isset($data['default_amount']) && '' !== $data['default_amount']) ? (float) $data['default_amount'] : null,
            'enabled'           => !empty($data['enabled']) ? 1 : 0,
            'display_order'     => isset($data['display_order']) ? absint($data['display_order']) : 0,
            'updated_at'        => $now,
        );

        if (!empty($data['id'])) {
            return $wpdb->update($table, $row, array('id' => absint($data['id'])));
        }

        $row['created_at'] = $now;
        $row['deleted_at'] = null;
        return $wpdb->insert($table, $row);
    }

    public static function delete_fund($fund_id) {
        global $wpdb;
        $fund_id = absint($fund_id);
        if (!$fund_id) {
            return new WP_Error('invalid_fund', __('Invalid giving fund.', 'videohub360-memberships'));
        }

        $fund = self::get_fund($fund_id, true);
        if (!$fund) {
            return new WP_Error('missing_fund', __('Giving fund not found.', 'videohub360-memberships'));
        }

        $transactions_table = VH360_Giving_Database::get_transactions_table();
        $transaction_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$transactions_table} WHERE fund_id = %d",
            $fund_id
        ));

        $funds_table = VH360_Giving_Database::get_funds_table();
        if ($transaction_count > 0) {
            $archived_key = substr($fund->fund_key . '-deleted-' . $fund_id, 0, 100);
            $updated = $wpdb->update(
                $funds_table,
                array(
                    'fund_key'   => $archived_key,
                    'enabled'    => 0,
                    'deleted_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ),
                array('id' => $fund_id)
            );
            return false === $updated ? new WP_Error('archive_failed', __('Could not archive giving fund.', 'videohub360-memberships')) : 'archived';
        }

        $deleted = $wpdb->delete($funds_table, array('id' => $fund_id));
        return false === $deleted ? new WP_Error('delete_failed', __('Could not delete giving fund.', 'videohub360-memberships')) : 'deleted';
    }
}
