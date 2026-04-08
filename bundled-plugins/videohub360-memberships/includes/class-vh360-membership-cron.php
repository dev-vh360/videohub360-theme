<?php
/**
 * Membership Cron Handler
 *
 * Handles scheduled tasks for membership expiration and maintenance.
 * Distinguishes between fixed-term (one-time) and recurring (Stripe) memberships.
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
        add_action('vh360_send_membership_renewal_reminder', array($this, 'send_renewal_reminder_email'), 10, 2);
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
     *
     * For fixed-term (one_time) memberships: expire based on local expires_at + grace period.
     * For recurring memberships: only expire if subscription_status indicates cancellation
     * (active subscriptions should not be expired by cron even if local dates appear stale).
     */
    public function check_expirations() {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Get grace period setting
        $options = get_option('vh360_membership_options', array());
        $grace_period_days = isset($options['grace_period_days']) ? absint($options['grace_period_days']) : 0;
        
        // Build expiration check that respects grace period
        if ($grace_period_days > 0) {
            $expiration_condition = $wpdb->prepare(
                "AND DATE_ADD(expires_at, INTERVAL %d DAY) <= NOW()",
                $grace_period_days
            );
        } else {
            $expiration_condition = "AND expires_at <= NOW()";
        }
        
        // --- Fixed-term / one_time memberships ---
        // These expire based purely on the local expires_at date
        $expired_fixed = $wpdb->get_results(
            "SELECT id FROM {$table} 
            WHERE status = 'active' 
            AND billing_mode = 'one_time'
            AND expires_at IS NOT NULL 
            {$expiration_condition}"
        );
        
        $api = VH360_Membership_API::get_instance();
        
        foreach ($expired_fixed as $membership) {
            $api->expire_membership($membership->id);
        }
        
        // --- Recurring memberships ---
        // Only expire if the Stripe subscription status actually requires it.
        // Do NOT expire active Stripe subscriptions just because local dates appear stale
        // (dates may lag behind until the next webhook sync).
        $expired_recurring = $wpdb->get_results(
            "SELECT id FROM {$table} 
            WHERE status = 'active'
            AND billing_mode = 'recurring'
            AND subscription_status IN ('canceled', 'unpaid', 'incomplete_expired')
            AND expires_at IS NOT NULL
            {$expiration_condition}"
        );
        
        foreach ($expired_recurring as $membership) {
            $api->expire_membership($membership->id);
        }
        
        $total_expired = count($expired_fixed) + count($expired_recurring);
        
        do_action('vh360_membership_expirations_checked', $total_expired);
    }
    
    /**
     * Send renewal reminders
     *
     * For fixed-term memberships: send reminders before expiration (manual renewal needed).
     * For recurring memberships: only send reminders when user action is actually needed
     * (past_due payment, cancel_at_period_end). Do not send "renew now" emails
     * for auto-renewing subscriptions that are healthy.
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
        
        // --- Fixed-term memberships: standard renewal reminder ---
        $fixed_expiring = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE status = 'active' 
            AND billing_mode = 'one_time'
            AND expires_at IS NOT NULL 
            AND expires_at > NOW() 
            AND expires_at <= DATE_ADD(NOW(), INTERVAL %d DAY)",
            $reminder_days
        ));
        
        foreach ($fixed_expiring as $membership) {
            $this->maybe_send_reminder($membership, 'renewal');
        }
        
        // --- Recurring memberships: only when user action is needed ---
        
        // 1. Subscriptions with cancel_at_period_end: remind user access will end
        $cancel_pending = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE status = 'active' 
            AND billing_mode = 'recurring'
            AND cancel_at_period_end = 1
            AND current_period_end IS NOT NULL
            AND current_period_end > NOW()
            AND current_period_end <= DATE_ADD(NOW(), INTERVAL %d DAY)",
            $reminder_days
        ));
        
        foreach ($cancel_pending as $membership) {
            $this->maybe_send_reminder($membership, 'cancellation_pending');
        }
        
        // 2. Subscriptions with past_due status: remind user to update payment
        $past_due = $wpdb->get_results(
            "SELECT * FROM {$table} 
            WHERE status = 'active' 
            AND billing_mode = 'recurring'
            AND subscription_status = 'past_due'"
        );
        
        foreach ($past_due as $membership) {
            $this->maybe_send_reminder($membership, 'payment_failed');
        }
        
        do_action('vh360_membership_renewal_reminders_sent');
    }
    
    /**
     * Send a reminder if not already sent for this membership
     *
     * @param object $membership Membership row
     * @param string $reminder_type Type of reminder
     */
    private function maybe_send_reminder($membership, $reminder_type) {
        $meta_key = "_vh360_membership_reminder_sent_{$membership->id}";
        $reminder_sent = get_user_meta($membership->user_id, $meta_key, true);
        
        if ($reminder_sent) {
            return;
        }
        
        // Send reminder
        do_action('vh360_send_membership_renewal_reminder', $membership, $reminder_type);
        
        // Mark reminder as sent
        update_user_meta($membership->user_id, $meta_key, current_time('mysql'));
    }
    
    /**
     * Send renewal reminder email
     *
     * @param object $membership Membership object
     * @param string $reminder_type Reminder type (renewal, cancellation_pending, payment_failed)
     */
    public function send_renewal_reminder_email($membership, $reminder_type = 'renewal') {
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
        
        // Build email based on reminder type
        $to = $user->user_email;
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        switch ($reminder_type) {
            case 'cancellation_pending':
                $end_date = isset($membership->current_period_end) 
                    ? date_i18n(get_option('date_format'), strtotime($membership->current_period_end)) 
                    : __('soon', 'videohub360-memberships');
                    
                $subject = sprintf(
                    __('[%s] Your subscription is ending soon', 'videohub360-memberships'),
                    get_bloginfo('name')
                );
                
                $message = sprintf(
                    __('Hi %s,', 'videohub360-memberships') . "\n\n" .
                    __('Your %s subscription is scheduled to end on %s.', 'videohub360-memberships') . "\n\n" .
                    __('If you\'d like to continue your membership, you can reactivate from your account dashboard.', 'videohub360-memberships') . "\n\n" .
                    __('- The %s Team', 'videohub360-memberships'),
                    $user->display_name,
                    $plan_label,
                    $end_date,
                    get_bloginfo('name')
                );
                break;
                
            case 'payment_failed':
                $subject = sprintf(
                    __('[%s] Payment issue with your subscription', 'videohub360-memberships'),
                    get_bloginfo('name')
                );
                
                $message = sprintf(
                    __('Hi %s,', 'videohub360-memberships') . "\n\n" .
                    __('We were unable to process payment for your %s subscription.', 'videohub360-memberships') . "\n\n" .
                    __('Please update your payment method to avoid losing access:', 'videohub360-memberships') . "\n" .
                    '%s' . "\n\n" .
                    __('- The %s Team', 'videohub360-memberships'),
                    $user->display_name,
                    $plan_label,
                    home_url(),
                    get_bloginfo('name')
                );
                break;
                
            default: // 'renewal' — standard fixed-term reminder
                $expires_at = $membership->expires_at ? date_i18n(get_option('date_format'), strtotime($membership->expires_at)) : __('Never', 'videohub360-memberships');
                
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
                break;
        }
        
        // Send email
        wp_mail($to, $subject, $message, $headers);
    }
}
