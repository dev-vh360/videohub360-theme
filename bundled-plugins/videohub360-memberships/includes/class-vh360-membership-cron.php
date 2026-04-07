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
        
        // Register renewal reminder handler
        add_action('vh360_send_membership_renewal_reminder', array($this, 'send_renewal_reminder_email'), 10, 1);
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
    
    /**
     * Send renewal reminder email
     *
     * @param object $membership Membership object
     */
    public function send_renewal_reminder_email($membership) {
        // Get user
        $user = get_userdata($membership->user_id);
        if (!$user) {
            return;
        }
        
        // Get plan info
        $plans = VH360_Membership_Plans::get_plan_registry();
        $plan_label = isset($plans[$membership->plan_key]) ? $plans[$membership->plan_key]['label'] : $membership->plan_key;
        
        // Get pricing URL
        $options = get_option('vh360_membership_options', array());
        $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : home_url();
        
        // Format expiration date
        $expires_at = $membership->expires_at ? date_i18n(get_option('date_format'), strtotime($membership->expires_at)) : __('Never', 'videohub360-memberships');
        
        // Prepare email
        $to = $user->user_email;
        $subject = sprintf(
            __('[%s] Your membership is expiring soon', 'videohub360-memberships'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            __('Hi %s,', 'videohub360-memberships') . "\n\n" .
            __('Your %s membership will expire on %s.', 'videohub360-memberships') . "\n\n" .
            __('To continue enjoying premium access, please renew your membership:', 'videohub360-memberships') . "\n" .
            '%s' . "\n\n" .
            __('Thank you for being a member!', 'videohub360-memberships') . "\n\n" .
            __('- The %s Team', 'videohub360-memberships'),
            $user->display_name,
            $plan_label,
            $expires_at,
            $pricing_url,
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
    }
}
