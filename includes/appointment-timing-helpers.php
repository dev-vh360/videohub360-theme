<?php
/**
 * Appointment Timing Helpers
 *
 * Centralized appointment session timing and access control logic.
 * Determines when users can access appointment rooms based on role and scheduled time.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get appointment session access state for a user.
 *
 * This is the single source of truth for appointment room access timing.
 * Use this helper in:
 * - appointment-live-room-gate.php (page access)
 * - class-videohub360-ajax.php (token generation)
 * - appointments.php (dashboard UI)
 * - render-livestream.php (room overlay)
 *
 * @param int $live_room_id The videohub360 post ID of the appointment room
 * @param int $user_id The user ID to check access for
 * @return array {
 *     Structured access state information
 *
 *     @type string $user_role         'professional'|'client'|'admin'|'none'
 *     @type bool   $is_authorized     Whether user has any access to this appointment
 *     @type bool   $can_view_page     Whether user can access the room page
 *     @type bool   $can_generate_token Whether user can generate Agora join token
 *     @type string $status            Current appointment status for UI
 *     @type string $message           Message to display to user
 *     @type array  $appointment_data  Raw appointment timing data
 * }
 */
function vh360_get_appointment_session_state($live_room_id, $user_id) {
    $result = array(
        'user_role' => 'none',
        'is_authorized' => false,
        'can_view_page' => false,
        'can_generate_token' => false,
        'status' => 'unauthorized',
        'message' => __('You do not have access to this appointment.', 'videohub360-theme'),
        'appointment_data' => array(),
    );
    
    // Verify this is an appointment room
    $appointment_event_id = get_post_meta($live_room_id, '_vh360_appointment_event_id', true);
    if (!$appointment_event_id) {
        // Not an appointment room - return defaults
        $result['status'] = 'not_appointment';
        $result['message'] = __('This is not an appointment room.', 'videohub360-theme');
        return $result;
    }
    
    // Get room post
    $room_post = get_post($live_room_id);
    if (!$room_post) {
        $result['status'] = 'invalid';
        $result['message'] = __('Invalid appointment room.', 'videohub360-theme');
        return $result;
    }
    
    // Get appointment event
    $event_post = get_post($appointment_event_id);
    if (!$event_post) {
        $result['status'] = 'invalid';
        $result['message'] = __('Invalid appointment event.', 'videohub360-theme');
        return $result;
    }
    
    // Get appointment participants
    $professional_id = (int) $room_post->post_author;
    $client_id = (int) get_post_meta($live_room_id, '_vh360_appointment_client_id', true);
    
    // Determine user role
    $is_admin = user_can($user_id, 'manage_options');
    $is_professional = ((int) $user_id === $professional_id);
    $is_client = ((int) $user_id === $client_id);
    
    if ($is_admin) {
        $result['user_role'] = 'admin';
        $result['is_authorized'] = true;
    } elseif ($is_professional) {
        $result['user_role'] = 'professional';
        $result['is_authorized'] = true;
    } elseif ($is_client) {
        $result['user_role'] = 'client';
        $result['is_authorized'] = true;
    } else {
        // User is not part of this appointment
        return $result;
    }
    
    // Get appointment timing
    $start_date = get_post_meta($appointment_event_id, '_vh360_event_start_date', true);
    $start_time = get_post_meta($appointment_event_id, '_vh360_event_start_time', true);
    $end_date = get_post_meta($appointment_event_id, '_vh360_event_end_date', true);
    $end_time = get_post_meta($appointment_event_id, '_vh360_event_end_time', true);
    
    if (empty($start_date) || empty($start_time)) {
        $result['status'] = 'invalid';
        $result['message'] = __('Appointment timing not configured.', 'videohub360-theme');
        return $result;
    }
    
    // Parse appointment times
    $timezone = wp_timezone();
    try {
        $start_datetime = new DateTime($start_date . ' ' . $start_time, $timezone);
        $end_datetime = !empty($end_date) && !empty($end_time) 
            ? new DateTime($end_date . ' ' . $end_time, $timezone)
            : clone $start_datetime;
        
        // If no end time, assume 1 hour duration
        if (empty($end_date) || empty($end_time)) {
            $end_datetime->modify('+1 hour');
        }
    } catch (Exception $e) {
        $result['status'] = 'invalid';
        $result['message'] = __('Invalid appointment date/time.', 'videohub360-theme');
        return $result;
    }
    
    $current_datetime = new DateTime('now', $timezone);
    
    // Calculate early-join window (configurable, default 10 minutes)
    $early_join_minutes = apply_filters('vh360_appointment_early_join_minutes', 10);
    $early_join_datetime = clone $start_datetime;
    $early_join_datetime->modify('-' . $early_join_minutes . ' minutes');
    
    // Get session state
    $stream_live = get_post_meta($live_room_id, '_vh360_agora_stream_live', true);
    $stream_stopped = get_post_meta($live_room_id, '_vh360_stream_stopped', true);
    $is_live = ($stream_live === 'yes');
    $is_ended = ($stream_stopped === 'yes');
    
    // Store raw appointment data
    $result['appointment_data'] = array(
        'start_datetime' => $start_datetime,
        'end_datetime' => $end_datetime,
        'early_join_datetime' => $early_join_datetime,
        'current_datetime' => $current_datetime,
        'is_live' => $is_live,
        'is_ended' => $is_ended,
        'professional_id' => $professional_id,
        'client_id' => $client_id,
    );
    
    // Determine access based on role and time
    
    // Admin always has full access
    if ($is_admin) {
        $result['can_view_page'] = true;
        $result['can_generate_token'] = true;
        
        if ($is_ended) {
            $result['status'] = 'ended';
            $result['message'] = __('Session has ended.', 'videohub360-theme');
        } elseif ($is_live) {
            $result['status'] = 'active';
            $result['message'] = __('Session is live.', 'videohub360-theme');
        } elseif ($current_datetime < $start_datetime) {
            $result['status'] = 'scheduled';
            $result['message'] = sprintf(
                __('Session scheduled for %s', 'videohub360-theme'),
                $start_datetime->format(get_option('date_format') . ' ' . get_option('time_format'))
            );
        } else {
            $result['status'] = 'ready';
            $result['message'] = __('Session ready to start.', 'videohub360-theme');
        }
        
        return $result;
    }
    
    // Professional can access early and start session
    if ($is_professional) {
        // Professional can always view the page
        $result['can_view_page'] = true;
        
        if ($is_ended) {
            $result['status'] = 'ended';
            $result['message'] = __('Session has ended.', 'videohub360-theme');
            $result['can_generate_token'] = false;
        } elseif ($is_live) {
            $result['status'] = 'active';
            $result['message'] = __('Session is live.', 'videohub360-theme');
            $result['can_generate_token'] = true;
        } elseif ($current_datetime >= $end_datetime) {
            $result['status'] = 'past';
            $result['message'] = __('Appointment time has passed.', 'videohub360-theme');
            $result['can_generate_token'] = true; // Allow late start
        } else {
            // Before or during appointment window - professional can always join
            $result['status'] = 'ready';
            $result['message'] = sprintf(
                __('Session scheduled for %s', 'videohub360-theme'),
                $start_datetime->format(get_option('date_format') . ' ' . get_option('time_format'))
            );
            $result['can_generate_token'] = true;
        }
        
        return $result;
    }
    
    // Client has time-restricted access
    if ($is_client) {
        if ($is_ended) {
            $result['can_view_page'] = true;
            $result['can_generate_token'] = false;
            $result['status'] = 'ended';
            $result['message'] = __('This session has ended.', 'videohub360-theme');
        } elseif ($current_datetime < $early_join_datetime) {
            // Before early-join window
            $result['can_view_page'] = true;
            $result['can_generate_token'] = false;
            $result['status'] = 'too_early';
            $result['message'] = sprintf(
                __('This session opens %d minutes before the scheduled start time. Session starts at %s.', 'videohub360-theme'),
                $early_join_minutes,
                $start_datetime->format(get_option('time_format')) . ', ' . $start_datetime->format(get_option('date_format'))
            );
        } elseif (!$is_live) {
            // Within early-join window but host hasn't started
            $result['can_view_page'] = true;
            $result['can_generate_token'] = false;
            $result['status'] = 'waiting_for_host';
            $result['message'] = __('Waiting for the professional to start the session.', 'videohub360-theme');
        } else {
            // Session is live - client can join
            $result['can_view_page'] = true;
            $result['can_generate_token'] = true;
            $result['status'] = 'active';
            $result['message'] = __('Session is live. Click to join.', 'videohub360-theme');
        }
        
        return $result;
    }
    
    return $result;
}

/**
 * Check if a user can access an appointment room page.
 *
 * Simplified wrapper for use in template_redirect gate.
 *
 * @param int $live_room_id The videohub360 post ID
 * @param int $user_id The user ID to check
 * @return bool Whether the user can view the page
 */
function vh360_can_user_view_appointment_page($live_room_id, $user_id) {
    $state = vh360_get_appointment_session_state($live_room_id, $user_id);
    return $state['can_view_page'];
}

/**
 * Check if a user can generate an Agora token for an appointment room.
 *
 * Simplified wrapper for use in AJAX token generation.
 *
 * @param int $live_room_id The videohub360 post ID
 * @param int $user_id The user ID to check
 * @return array {
 *     @type bool   $can_join Whether the user can join
 *     @type string $message  Error message if cannot join
 * }
 */
function vh360_can_user_join_appointment_room($live_room_id, $user_id) {
    $state = vh360_get_appointment_session_state($live_room_id, $user_id);
    
    return array(
        'can_join' => $state['can_generate_token'],
        'message' => $state['message'],
        'status' => $state['status'],
    );
}
