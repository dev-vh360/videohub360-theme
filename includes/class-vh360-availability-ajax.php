<?php
/**
 * Availability AJAX Handlers
 *
 * Handles AJAX requests for appointment booking functionality.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Availability_Ajax
 */
class VH360_Availability_Ajax {
    
    /**
     * Singleton instance.
     */
    private static $instance = null;
    
    /**
     * Get singleton instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        // Get available slots
        add_action('wp_ajax_vh360_get_professional_slots', array($this, 'get_professional_slots'));
        add_action('wp_ajax_nopriv_vh360_get_professional_slots', array($this, 'get_professional_slots'));
        
        // Book appointment
        add_action('wp_ajax_vh360_book_appointment_slot', array($this, 'book_appointment_slot'));
        
        // Save availability settings
        add_action('wp_ajax_vh360_save_availability_settings', array($this, 'save_availability_settings'));
    }
    
    /**
     * Get available appointment slots for a professional.
     */
    public function get_professional_slots() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }
        
        // Get parameters
        $professional_id = isset($_POST['professional_id']) ? absint($_POST['professional_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$professional_id || !$date) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'videohub360-theme')));
            return;
        }
        
        // Verify the user is a professional
        $account_type = vh360_get_user_account_type($professional_id);
        if (!in_array($account_type, array('professional', 'organization'), true)) {
            wp_send_json_error(array('message' => __('User is not a professional', 'videohub360-theme')));
            return;
        }
        
        // Generate slots for the requested date (and next few days for convenience)
        $start_date = $date;
        $end_date = date('Y-m-d', strtotime($date . ' +6 days'));
        
        $slots = vh360_get_open_appointment_slots($professional_id, $start_date, $end_date);
        
        wp_send_json_success(array(
            'slots' => $slots,
            'professional_id' => $professional_id,
        ));
    }
    
    /**
     * Book an appointment slot.
     */
    public function book_appointment_slot() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to book appointments', 'videohub360-theme')));
            return;
        }
        
        $client_id = get_current_user_id();
        $professional_id = isset($_POST['professional_id']) ? absint($_POST['professional_id']) : 0;
        $slot_datetime = isset($_POST['slot_datetime']) ? sanitize_text_field($_POST['slot_datetime']) : '';
        $slot_duration = isset($_POST['slot_duration']) ? absint($_POST['slot_duration']) : 30;
        
        if (!$professional_id || !$slot_datetime) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'videohub360-theme')));
            return;
        }
        
        // Can't book with yourself
        if ($client_id === $professional_id) {
            wp_send_json_error(array('message' => __('You cannot book appointments with yourself', 'videohub360-theme')));
            return;
        }
        
        // Parse slot datetime
        $settings = vh360_get_availability_settings($professional_id);
        $slot_start = DateTime::createFromFormat('Y-m-d H:i:s', $slot_datetime, new DateTimeZone($settings['timezone']));
        
        if (!$slot_start) {
            wp_send_json_error(array('message' => __('Invalid datetime format', 'videohub360-theme')));
            return;
        }
        
        $slot_end = clone $slot_start;
        $slot_end->modify('+' . $slot_duration . ' minutes');
        
        // Verify slot is still available (double-check server-side)
        $has_conflict = vh360_check_slot_conflict(
            $professional_id,
            $slot_start->format('Y-m-d'),
            $slot_start->format('H:i:s'),
            $slot_end->format('Y-m-d'),
            $slot_end->format('H:i:s')
        );
        
        if ($has_conflict) {
            wp_send_json_error(array('message' => __('Sorry, this slot is no longer available', 'videohub360-theme')));
            return;
        }
        
        // Create the appointment event
        $professional = get_userdata($professional_id);
        $client = get_userdata($client_id);
        
        $event_title = sprintf(
            __('Appointment: %s', 'videohub360-theme'),
            $client->display_name
        );
        
        $event_id = wp_insert_post(array(
            'post_title' => $event_title,
            'post_type' => 'vh360_event',
            'post_status' => 'publish',
            'post_author' => $professional_id,
        ));
        
        if (is_wp_error($event_id)) {
            wp_send_json_error(array('message' => __('Failed to create appointment', 'videohub360-theme')));
            return;
        }
        
        // Set event meta
        update_post_meta($event_id, '_vh360_event_kind', 'availability');
        update_post_meta($event_id, '_vh360_event_start_date', $slot_start->format('Y-m-d'));
        update_post_meta($event_id, '_vh360_event_start_time', $slot_start->format('H:i:s'));
        update_post_meta($event_id, '_vh360_event_end_date', $slot_end->format('Y-m-d'));
        update_post_meta($event_id, '_vh360_event_end_time', $slot_end->format('H:i:s'));
        update_post_meta($event_id, '_vh360_event_max_attendees', 1);
        update_post_meta($event_id, '_vh360_event_location_type', 'online');
        
        // Add client RSVP
        $rsvps = array(
            array(
                'user_id' => $client_id,
                'time' => current_time('mysql'),
            )
        );
        update_post_meta($event_id, '_vh360_event_rsvps', $rsvps);
        update_post_meta($event_id, '_vh360_event_rsvp_count', 1);
        
        // Send notification to professional
        if (function_exists('vh360_create_notification')) {
            vh360_create_notification(
                $professional_id,
                'appointment_booked',
                sprintf(
                    __('%s booked an appointment with you on %s at %s', 'videohub360-theme'),
                    $client->display_name,
                    $slot_start->format(get_option('date_format')),
                    $slot_start->format(get_option('time_format'))
                ),
                get_permalink($event_id)
            );
        }
        
        wp_send_json_success(array(
            'message' => __('Appointment booked successfully!', 'videohub360-theme'),
            'event_id' => $event_id,
            'event_url' => get_permalink($event_id),
            'professional_id' => $professional_id,
        ));
    }
    
    /**
     * Save availability settings.
     */
    public function save_availability_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'videohub360-theme')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Verify user is a professional
        $account_type = vh360_get_user_account_type($user_id);
        if (!in_array($account_type, array('professional', 'organization'), true)) {
            wp_send_json_error(array('message' => __('Only professionals can set availability', 'videohub360-theme')));
            return;
        }
        
        // Get settings from POST
        $settings = array();
        
        if (isset($_POST['timezone'])) {
            $settings['timezone'] = sanitize_text_field($_POST['timezone']);
        }
        
        if (isset($_POST['slot_minutes'])) {
            $settings['slot_minutes'] = absint($_POST['slot_minutes']);
        }
        
        if (isset($_POST['buffer_minutes'])) {
            $settings['buffer_minutes'] = absint($_POST['buffer_minutes']);
        }
        
        if (isset($_POST['weekly'])) {
            $settings['weekly'] = json_decode(stripslashes($_POST['weekly']), true);
        }
        
        // Save settings
        $result = vh360_save_availability_settings($user_id, $settings);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Availability settings saved successfully', 'videohub360-theme')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save settings', 'videohub360-theme')));
        }
    }
}

// Initialize
VH360_Availability_Ajax::get_instance();
