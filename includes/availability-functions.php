<?php
/**
 * Availability Functions
 *
 * Functions for managing professional availability schedules and appointment booking.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get professional's availability settings.
 *
 * @param int $user_id Professional user ID.
 * @return array Availability settings with defaults.
 */
function vh360_get_availability_settings($user_id) {
    $defaults = array(
        'timezone' => wp_timezone_string(),
        'slot_minutes' => 30,
        'buffer_minutes' => 0,
        'weekly' => array(
            'mon' => array(),
            'tue' => array(),
            'wed' => array(),
            'thu' => array(),
            'fri' => array(),
            'sat' => array(),
            'sun' => array(),
        ),
    );
    
    $timezone = get_user_meta($user_id, '_vh360_availability_timezone', true);
    $slot_minutes = get_user_meta($user_id, '_vh360_availability_slot_minutes', true);
    $buffer_minutes = get_user_meta($user_id, '_vh360_availability_buffer_minutes', true);
    $weekly = get_user_meta($user_id, '_vh360_availability_weekly', true);
    
    return array(
        'timezone' => !empty($timezone) ? $timezone : $defaults['timezone'],
        'slot_minutes' => !empty($slot_minutes) ? absint($slot_minutes) : $defaults['slot_minutes'],
        'buffer_minutes' => !empty($buffer_minutes) ? absint($buffer_minutes) : $defaults['buffer_minutes'],
        'weekly' => is_array($weekly) ? $weekly : $defaults['weekly'],
    );
}

/**
 * Save professional's availability settings.
 *
 * @param int   $user_id  Professional user ID.
 * @param array $settings Availability settings.
 * @return bool True on success.
 */
function vh360_save_availability_settings($user_id, $settings) {
    if (empty($user_id)) {
        return false;
    }
    
    // Sanitize and save timezone
    if (isset($settings['timezone'])) {
        $timezone = sanitize_text_field($settings['timezone']);
        update_user_meta($user_id, '_vh360_availability_timezone', $timezone);
    }
    
    // Sanitize and save slot minutes
    if (isset($settings['slot_minutes'])) {
        $slot_minutes = absint($settings['slot_minutes']);
        if ($slot_minutes >= 15 && $slot_minutes <= 240) {
            update_user_meta($user_id, '_vh360_availability_slot_minutes', $slot_minutes);
        }
    }
    
    // Sanitize and save buffer minutes
    if (isset($settings['buffer_minutes'])) {
        $buffer_minutes = absint($settings['buffer_minutes']);
        if ($buffer_minutes >= 0 && $buffer_minutes <= 60) {
            update_user_meta($user_id, '_vh360_availability_buffer_minutes', $buffer_minutes);
        }
    }
    
    // Sanitize and save weekly availability
    if (isset($settings['weekly']) && is_array($settings['weekly'])) {
        $weekly = array();
        $valid_days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
        
        foreach ($valid_days as $day) {
            if (isset($settings['weekly'][$day]) && is_array($settings['weekly'][$day])) {
                $weekly[$day] = array();
                foreach ($settings['weekly'][$day] as $slot) {
                    if (isset($slot['start']) && isset($slot['end'])) {
                        $weekly[$day][] = array(
                            'start' => sanitize_text_field($slot['start']),
                            'end' => sanitize_text_field($slot['end']),
                        );
                    }
                }
            } else {
                $weekly[$day] = array();
            }
        }
        
        update_user_meta($user_id, '_vh360_availability_weekly', $weekly);
    }
    
    return true;
}

/**
 * Generate open appointment slots for a professional within a date range.
 *
 * @param int    $professional_id Professional user ID.
 * @param string $range_start     Start date (Y-m-d).
 * @param string $range_end       End date (Y-m-d).
 * @return array Array of open slots with 'datetime', 'start', 'end' keys.
 */
function vh360_get_open_appointment_slots($professional_id, $range_start, $range_end) {
    $settings = vh360_get_availability_settings($professional_id);
    $open_slots = array();
    
    // Parse dates
    $start_date = new DateTime($range_start, new DateTimeZone($settings['timezone']));
    $end_date = new DateTime($range_end, new DateTimeZone($settings['timezone']));
    
    // Iterate through each day in range
    $current_date = clone $start_date;
    while ($current_date <= $end_date) {
        $day_of_week = strtolower($current_date->format('D')); // mon, tue, etc.
        
        // Check if professional has availability for this day
        if (!empty($settings['weekly'][$day_of_week])) {
            foreach ($settings['weekly'][$day_of_week] as $time_block) {
                // Generate slots within this time block
                $block_start = DateTime::createFromFormat('Y-m-d H:i', $current_date->format('Y-m-d') . ' ' . $time_block['start'], new DateTimeZone($settings['timezone']));
                $block_end = DateTime::createFromFormat('Y-m-d H:i', $current_date->format('Y-m-d') . ' ' . $time_block['end'], new DateTimeZone($settings['timezone']));
                
                if (!$block_start || !$block_end) {
                    continue;
                }
                
                $slot_start = clone $block_start;
                while ($slot_start < $block_end) {
                    $slot_end = clone $slot_start;
                    $slot_end->modify('+' . $settings['slot_minutes'] . ' minutes');
                    
                    // Don't include slots that extend past the block end
                    if ($slot_end > $block_end) {
                        break;
                    }
                    
                    // Check if slot is in the past
                    $now = new DateTime('now', new DateTimeZone($settings['timezone']));
                    if ($slot_start <= $now) {
                        $slot_start->modify('+' . $settings['slot_minutes'] . ' minutes');
                        continue;
                    }
                    
                    // Check for conflicts
                    $has_conflict = vh360_check_slot_conflict(
                        $professional_id,
                        $slot_start->format('Y-m-d'),
                        $slot_start->format('H:i:s'),
                        $slot_end->format('Y-m-d'),
                        $slot_end->format('H:i:s')
                    );
                    
                    if (!$has_conflict) {
                        $open_slots[] = array(
                            'datetime' => $slot_start->format('Y-m-d H:i:s'),
                            'start' => $slot_start->format('H:i'),
                            'end' => $slot_end->format('H:i'),
                            'date' => $slot_start->format('Y-m-d'),
                        );
                    }
                    
                    // Move to next slot (including buffer time)
                    $slot_start->modify('+' . ($settings['slot_minutes'] + $settings['buffer_minutes']) . ' minutes');
                }
            }
        }
        
        $current_date->modify('+1 day');
    }
    
    return $open_slots;
}

/**
 * Check if a time slot conflicts with existing blocks or bookings.
 *
 * @param int    $professional_id Professional user ID.
 * @param string $start_date      Start date (Y-m-d).
 * @param string $start_time      Start time (H:i:s).
 * @param string $end_date        End date (Y-m-d).
 * @param string $end_time        End time (H:i:s).
 * @return bool True if conflict exists.
 */
function vh360_check_slot_conflict($professional_id, $start_date, $start_time, $end_date, $end_time) {
    // Use existing overlap check function
    if (function_exists('vh360_check_event_overlap')) {
        $overlap_result = vh360_check_event_overlap(0, $professional_id, $start_date, $start_time, $end_date, $end_time);
        return $overlap_result['has_overlap'];
    }
    
    return false;
}
