<?php
/**
 * Database handler: table creation and schema migrations.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Database {

    /** @var VH360_Affiliates_Database|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Instance kept for potential future instance-level hooks.
    }

    /**
     * Run table creation if the stored DB version is older than current.
     * Called directly during plugin initialisation so it fires reliably
     * regardless of which action hook is active when the plugin loads.
     */
    public static function maybe_upgrade() {
        $current = get_option('vh360_affiliates_db_version', '0');
        if (version_compare($current, '1.0.0', '<')) {
            self::create_tables();
        }
        if (version_compare($current, '1.1.0', '<')) {
            self::migrate_1_1_0();
        }
    }

    /**
     * Create / upgrade all plugin tables using dbDelta().
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // -----------------------------------------------------------
        // Table 1: vh360_affiliates
        // -----------------------------------------------------------
        $t1 = $wpdb->prefix . 'vh360_affiliates';
        dbDelta("CREATE TABLE {$t1} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            affiliate_code VARCHAR(80) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            commission_rate DECIMAL(10,2) NOT NULL DEFAULT 20.00,
            payment_email VARCHAR(190) NULL,
            payment_method VARCHAR(40) NULL DEFAULT 'other',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY affiliate_code (affiliate_code)
        ) {$charset_collate};");

        // -----------------------------------------------------------
        // Table 2: vh360_affiliate_visits
        // -----------------------------------------------------------
        $t2 = $wpdb->prefix . 'vh360_affiliate_visits';
        dbDelta("CREATE TABLE {$t2} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL,
            affiliate_code VARCHAR(80) NOT NULL,
            landing_url TEXT NULL,
            referrer_url TEXT NULL,
            ip_hash VARCHAR(128) NULL,
            user_agent_hash VARCHAR(128) NULL,
            visitor_hash VARCHAR(128) NULL,
            created_at DATETIME NOT NULL,
            converted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id)
        ) {$charset_collate};");

        // -----------------------------------------------------------
        // Table 3: vh360_affiliate_referrals
        // -----------------------------------------------------------
        $t3 = $wpdb->prefix . 'vh360_affiliate_referrals';
        dbDelta("CREATE TABLE {$t3} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL,
            visit_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NULL,
            order_id BIGINT UNSIGNED NULL,
            order_item_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id)
        ) {$charset_collate};");

        // -----------------------------------------------------------
        // Table 4: vh360_affiliate_commissions
        // -----------------------------------------------------------
        $t4 = $wpdb->prefix . 'vh360_affiliate_commissions';
        dbDelta("CREATE TABLE {$t4} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL,
            referral_id BIGINT UNSIGNED NULL,
            order_id BIGINT UNSIGNED NULL,
            order_item_id BIGINT UNSIGNED NULL,
            product_id BIGINT UNSIGNED NULL,
            base_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            commission_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            reason TEXT NULL,
            created_at DATETIME NOT NULL,
            approved_at DATETIME NULL,
            rejected_at DATETIME NULL,
            paid_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY order_item_id (order_item_id),
            KEY order_id (order_id),
            KEY affiliate_id (affiliate_id)
        ) {$charset_collate};");

        // -----------------------------------------------------------
        // Table 5: vh360_affiliate_payouts
        // -----------------------------------------------------------
        $t5 = $wpdb->prefix . 'vh360_affiliate_payouts';
        dbDelta("CREATE TABLE {$t5} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            method VARCHAR(80) NULL,
            transaction_reference VARCHAR(190) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'paid',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            paid_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id)
        ) {$charset_collate};");

        update_option('vh360_affiliates_db_version', '1.0.0');
    }

    /**
     * Migration 1.1.0: add payment_method column to the affiliates table.
     */
    public static function migrate_1_1_0() {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliates';

        // Only add the column if it does not already exist.
        $col_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'payment_method'",
                DB_NAME,
                $table
            )
        );

        if (empty($col_exists)) {
            $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `payment_method` VARCHAR(40) NULL DEFAULT 'other' AFTER `payment_email`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
        }

        update_option('vh360_affiliates_db_version', '1.1.0');
    }

    // -----------------------------------------------------------
    // Helper query methods
    // -----------------------------------------------------------

    /** Get affiliate row by user ID. */
    public static function get_affiliate_by_user_id($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliates';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id)
        );
    }

    /** Get affiliate row by affiliate code. */
    public static function get_affiliate_by_code($code) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliates';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE affiliate_code = %s LIMIT 1", $code)
        );
    }

    /** Get affiliate row by ID. */
    public static function get_affiliate_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliates';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id)
        );
    }

    /** Insert a new affiliate record. Returns insert ID or false. */
    public static function insert_affiliate($data) {
        global $wpdb;
        $now = current_time('mysql');
        $row = array_merge($data, array(
            'created_at' => $now,
            'updated_at' => $now,
        ));
        // Let $wpdb->insert() infer formats to avoid column/format ordering mismatches.
        $result = $wpdb->insert($wpdb->prefix . 'vh360_affiliates', $row);
        return $result ? $wpdb->insert_id : false;
    }

    /** Update affiliate record. */
    public static function update_affiliate($id, $data) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        return $wpdb->update(
            $wpdb->prefix . 'vh360_affiliates',
            $data,
            array('id' => $id)
        );
    }

    /** Insert visit record. */
    public static function insert_visit($data) {
        global $wpdb;
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($wpdb->prefix . 'vh360_affiliate_visits', $data);
        return $result ? $wpdb->insert_id : false;
    }

    /** Mark visit as converted. */
    public static function mark_visit_converted($visit_id) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vh360_affiliate_visits',
            array('converted_at' => current_time('mysql')),
            array('id' => $visit_id)
        );
    }

    /** Insert referral record. */
    public static function insert_referral($data) {
        global $wpdb;
        $now = current_time('mysql');
        $data = array_merge(array('created_at' => $now, 'updated_at' => $now), $data);
        $result = $wpdb->insert($wpdb->prefix . 'vh360_affiliate_referrals', $data);
        return $result ? $wpdb->insert_id : false;
    }

    /** Update referral status. */
    public static function update_referral_status($referral_id, $status) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'vh360_affiliate_referrals',
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $referral_id)
        );
    }

    /** Get commission row by ID. */
    public static function get_commission_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_commissions';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id)
        );
    }

    /** Check if commission already exists for order_id + order_item_id (idempotency). */
    public static function commission_exists($order_id, $order_item_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_commissions';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND order_item_id = %d",
                $order_id,
                $order_item_id
            )
        );
        return $count > 0;
    }

    /** Insert commission record. */
    public static function insert_commission($data) {
        global $wpdb;
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($wpdb->prefix . 'vh360_affiliate_commissions', $data);
        return $result ? $wpdb->insert_id : false;
    }

    /** Update commission status. */
    public static function update_commission_status($commission_id, $status, $extra = array()) {
        global $wpdb;
        $update = array_merge(array('status' => $status), $extra);
        return $wpdb->update(
            $wpdb->prefix . 'vh360_affiliate_commissions',
            $update,
            array('id' => $commission_id)
        );
    }

    /** Get commissions by order_id. */
    public static function get_commissions_by_order($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_commissions';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d", $order_id)
        );
    }

    /** Insert payout record. */
    public static function insert_payout($data) {
        global $wpdb;
        $data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($wpdb->prefix . 'vh360_affiliate_payouts', $data);
        return $result ? $wpdb->insert_id : false;
    }

    /** Get commission totals for an affiliate (keyed by status). */
    public static function get_commission_totals($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_commissions';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, SUM(commission_amount) AS total FROM {$table}
                 WHERE affiliate_id = %d GROUP BY status",
                $affiliate_id
            )
        );
        $totals = array('pending' => 0, 'approved' => 0, 'paid' => 0, 'rejected' => 0, 'reversed' => 0);
        foreach ($rows as $row) {
            if (isset($totals[$row->status])) {
                $totals[$row->status] = (float) $row->total;
            }
        }
        return $totals;
    }

    /** Count visits for an affiliate. */
    public static function get_visit_count($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_visits';
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE affiliate_id = %d", $affiliate_id)
        );
    }

    /** Count referrals for an affiliate. */
    public static function get_referral_count($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_referrals';
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE affiliate_id = %d", $affiliate_id)
        );
    }

    /** Get recent commissions for an affiliate. */
    public static function get_recent_commissions($affiliate_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_commissions';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT %d",
                $affiliate_id,
                $limit
            )
        );
    }

    /** Get recent referrals for an affiliate. */
    public static function get_recent_referrals($affiliate_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_referrals';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT %d",
                $affiliate_id,
                $limit
            )
        );
    }

    /** Get payouts for an affiliate. */
    public static function get_payouts($affiliate_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_affiliate_payouts';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT %d",
                $affiliate_id,
                $limit
            )
        );
    }

    /** Delete unconverted visits older than the retention period. */
    public static function purge_old_visits($days = 180) {
        global $wpdb;
        $table  = $wpdb->prefix . 'vh360_affiliate_visits';
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table}
                 WHERE converted_at IS NULL AND created_at < %s",
                $cutoff
            )
        );
    }
}
