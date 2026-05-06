<?php
/**
 * Manual payout tracking and auto-approve cron.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Payouts {

    /** @var VH360_Affiliates_Payouts|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('vh360_affiliates_daily_cron',    array($this, 'run_auto_approve'));
        add_action('vh360_affiliates_cleanup_cron',  array($this, 'run_visit_cleanup'));

        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('vh360_affiliates_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'vh360_affiliates_daily_cron');
        }
        if (!wp_next_scheduled('vh360_affiliates_cleanup_cron')) {
            wp_schedule_event(time(), 'weekly', 'vh360_affiliates_cleanup_cron');
        }
    }

    /**
     * Auto-approve pending commissions that are older than the configured threshold.
     */
    public function run_auto_approve() {
        $settings = vh360_affiliates_get_settings();
        $days     = (int) ($settings['auto_approve_days'] ?? 0);

        if ($days <= 0) {
            return;
        }

        global $wpdb;
        $table  = $wpdb->prefix . 'vh360_affiliate_commissions';
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        $pending = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE status = 'pending' AND created_at <= %s",
                $cutoff
            )
        );

        foreach ($pending as $row) {
            VH360_Affiliates_Database::update_commission_status(
                $row->id,
                'approved',
                array('approved_at' => current_time('mysql'))
            );
            // Keep the linked referral status in sync.
            $commission = VH360_Affiliates_Database::get_commission_by_id($row->id);
            if ($commission && !empty($commission->referral_id)) {
                VH360_Affiliates_Database::update_referral_status($commission->referral_id, 'approved');
            }
        }
    }

    /**
     * Purge old unconverted visits per retention setting.
     */
    public function run_visit_cleanup() {
        $settings = vh360_affiliates_get_settings();
        $days     = (int) ($settings['visit_retention_days'] ?? 180);
        VH360_Affiliates_Database::purge_old_visits($days);
    }

    /**
     * Unschedule cron events on plugin deactivation.
     */
    public static function unschedule() {
        wp_clear_scheduled_hook('vh360_affiliates_daily_cron');
        wp_clear_scheduled_hook('vh360_affiliates_cleanup_cron');
    }
}
