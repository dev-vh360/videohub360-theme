<?php
/**
 * Membership Cron Handler
 *
 * Handles scheduled tasks for membership expiration and maintenance.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Cron {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Cron
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Cron
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
        // Register cron hooks
        add_action('vh360_membership_check_expirations', array($this, 'check_expirations'));
        add_action('vh360_membership_send_renewal_reminders', array($this, 'send_renewal_reminders'));
    }
    
    /**
     * Schedule cron events
     */
    public static function schedule_events() {
        // Schedule expiration check (daily)
        if (!wp_next_scheduled('vh360_membership_check_expirations')) {
            wp_schedule_event(time(), 'daily', 'vh360_membership_check_expirations');
        }
        
        // Schedule renewal reminders (daily)
        if (!wp_next_scheduled('vh360_membership_send_renewal_reminders')) {
            wp_schedule_event(time(), 'daily', 'vh360_membership_send_renewal_reminders');
        }
    }
    
    /**
     * Unschedule cron events
     */
    public static function unschedule_events() {
        wp_clear_scheduled_hook('vh360_membership_check_expirations');
        wp_clear_scheduled_hook('vh360_membership_send_renewal_reminders');
    }
    
    /**
     * Check and expire memberships
     */
    public function check_expirations() {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Find expired memberships that are still marked as active
        $expired_memberships = $wpdb->get_results(
            "SELECT id FROM {$table} 
            WHERE status = 'active' 
            AND expires_at IS NOT NULL 
            AND expires_at <= NOW()"
        );
        
        $api = VH360_Membership_API::get_instance();
        
        foreach ($expired_memberships as $membership) {
            $api->expire_membership($membership->id);
        }
        
        do_action('vh360_membership_expirations_checked', count($expired_memberships));
    }
    
    /**
     * Send renewal reminders
     */
    public function send_renewal_reminders() {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Get reminder settings
        $options = get_option('vh360_membership_options', array());
        $reminder_days = isset($options['reminder_days']) ? absint($options['reminder_days']) : 7;
        
        if (!$reminder_days) {
            return;
        }
        
        // Find memberships expiring soon
        $expiring_soon = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE status = 'active' 
            AND expires_at IS NOT NULL 
            AND expires_at > NOW() 
            AND expires_at <= DATE_ADD(NOW(), INTERVAL %d DAY)",
            $reminder_days
        ));
        
        foreach ($expiring_soon as $membership) {
            // Check if reminder already sent
            $reminder_sent = get_user_meta(
                $membership->user_id,
                "_vh360_membership_reminder_sent_{$membership->id}",
                true
            );
            
            if ($reminder_sent) {
                continue;
            }
            
            // Send renewal reminder
            do_action('vh360_send_membership_renewal_reminder', $membership);
            
            // Mark reminder as sent
            update_user_meta(
                $membership->user_id,
                "_vh360_membership_reminder_sent_{$membership->id}",
                current_time('mysql')
            );
        }
        
        do_action('vh360_membership_renewal_reminders_sent', count($expiring_soon));
    }
}
