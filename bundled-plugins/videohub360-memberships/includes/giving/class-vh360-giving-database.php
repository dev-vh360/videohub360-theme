<?php
/**
 * VideoHub360 Giving database installer.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Database {
    private static $instance = null;
    private $db_version = '1.3.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->check_database_version();
    }

    public function check_database_version() {
        if (version_compare(get_option('vh360_giving_db_version', '0'), $this->db_version, '<') || !self::tables_are_ready()) {
            self::create_tables();
        }
    }

    public static function get_funds_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_giving_funds';
    }

    public static function get_transactions_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_giving_transactions';
    }

    public static function get_recurring_table() {
        global $wpdb;
        return $wpdb->prefix . 'vh360_giving_recurring';
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $funds = self::get_funds_table();
        $transactions = self::get_transactions_table();
        $recurring = self::get_recurring_table();

        $funds_sql = "CREATE TABLE {$funds} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            fund_key varchar(100) NOT NULL,
            label varchar(190) NOT NULL,
            description text DEFAULT NULL,
            suggested_amounts varchar(255) DEFAULT NULL,
            default_amount decimal(12,2) DEFAULT NULL,
            enabled tinyint(1) NOT NULL DEFAULT 1,
            display_order int(11) NOT NULL DEFAULT 0,
            deleted_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY fund_key (fund_key),
            KEY enabled (enabled),
            KEY display_order (display_order),
            KEY deleted_at (deleted_at)
        ) {$charset_collate};";

        $transactions_sql = "CREATE TABLE {$transactions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            fund_id bigint(20) unsigned DEFAULT NULL,
            fund_key varchar(100) NOT NULL,
            fund_label varchar(190) NOT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'usd',
            status varchar(20) NOT NULL DEFAULT 'pending',
            gateway varchar(50) NOT NULL DEFAULT 'stripe',
            gateway_mode varchar(50) NOT NULL DEFAULT 'payment',
            gateway_transaction_id varchar(255) DEFAULT NULL,
            gateway_customer_id varchar(255) DEFAULT NULL,
            gateway_subscription_id varchar(255) DEFAULT NULL,
            gateway_event_id varchar(255) DEFAULT NULL,
            stripe_checkout_session_id varchar(255) DEFAULT NULL,
            stripe_payment_intent_id varchar(255) DEFAULT NULL,
            stripe_customer_id varchar(255) DEFAULT NULL,
            stripe_event_id varchar(255) DEFAULT NULL,
            stripe_invoice_id varchar(255) DEFAULT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            source varchar(50) NOT NULL DEFAULT 'dashboard',
            anonymous tinyint(1) NOT NULL DEFAULT 0,
            note text DEFAULT NULL,
            given_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY fund_id (fund_id),
            KEY fund_key (fund_key),
            KEY status (status),
            KEY source (source),
            KEY stripe_checkout_session_id (stripe_checkout_session_id),
            KEY stripe_event_id (stripe_event_id),
            KEY stripe_invoice_id (stripe_invoice_id),
            KEY stripe_subscription_id (stripe_subscription_id),
            KEY given_at (given_at)
        ) {$charset_collate};";

        $recurring_sql = "CREATE TABLE {$recurring} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            fund_id bigint(20) unsigned DEFAULT NULL,
            fund_key varchar(100) NOT NULL,
            fund_label varchar(190) NOT NULL,
            amount decimal(12,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'usd',
            giving_interval varchar(20) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'incomplete',
            gateway varchar(50) NOT NULL DEFAULT 'stripe',
            stripe_customer_id varchar(255) DEFAULT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            stripe_price_id varchar(255) DEFAULT NULL,
            source varchar(50) NOT NULL DEFAULT 'dashboard',
            anonymous tinyint(1) NOT NULL DEFAULT 0,
            note text DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            current_period_start datetime DEFAULT NULL,
            current_period_end datetime DEFAULT NULL,
            cancel_at_period_end tinyint(1) NOT NULL DEFAULT 0,
            canceled_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY fund_id (fund_id),
            KEY fund_key (fund_key),
            KEY status (status),
            KEY stripe_subscription_id (stripe_subscription_id),
            KEY source (source)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($funds_sql);
        dbDelta($transactions_sql);
        dbDelta($recurring_sql);
        self::migrate_reserved_interval_column();
        update_option('vh360_giving_db_version', '1.3.0');
    }

    public static function table_exists($table) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    public static function recurring_table_exists() {
        return self::table_exists(self::get_recurring_table());
    }

    public static function tables_are_ready() {
        foreach (array(self::get_funds_table(), self::get_transactions_table(), self::get_recurring_table()) as $table) {
            if (!self::table_exists($table)) {
                return false;
            }
        }
        $required = array('giving_interval','stripe_price_id','current_period_start','current_period_end','cancel_at_period_end','canceled_at');
        $columns = self::get_table_columns(self::get_recurring_table());
        foreach ($required as $column) {
            if (!in_array($column, $columns, true)) {
                return false;
            }
        }
        return true;
    }

    private static function get_table_columns($table) {
        global $wpdb;
        if (!self::table_exists($table)) {
            return array();
        }
        return (array) $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    }

    private static function migrate_reserved_interval_column() {
        global $wpdb;
        $table = self::get_recurring_table();
        if (!self::table_exists($table)) {
            return;
        }
        $columns = self::get_table_columns($table);
        if (in_array('interval', $columns, true) && in_array('giving_interval', $columns, true)) {
            $wpdb->query("UPDATE {$table} SET giving_interval = `interval` WHERE (giving_interval IS NULL OR giving_interval = '') AND `interval` IS NOT NULL AND `interval` <> ''");
        }
    }

}
