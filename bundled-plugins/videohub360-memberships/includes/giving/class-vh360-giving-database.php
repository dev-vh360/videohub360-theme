<?php
/**
 * VideoHub360 Giving database installer.
 *
 * @package VideoHub360_Memberships
 */

if (!defined('ABSPATH')) exit;

class VH360_Giving_Database {
    private static $instance = null;
    private $db_version = '1.1.0';

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
        if (version_compare(get_option('vh360_giving_db_version', '0'), $this->db_version, '<')) {
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

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $funds = self::get_funds_table();
        $transactions = self::get_transactions_table();

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
            KEY given_at (given_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($funds_sql);
        dbDelta($transactions_sql);
        update_option('vh360_giving_db_version', '1.1.0');
    }
}
