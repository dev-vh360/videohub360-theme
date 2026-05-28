<?php
/**
 * Event Helper Functions
 *
 * Utility functions for working with events.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get event kind (type).
 *
 * Returns the event kind: 'event' (normal event), 'availability' (bookable appointment slot), or 'block' (unavailable time).
 * Defaults to 'event' if not set or invalid.
 *
 * @param int $event_id Event post ID.
 * @return string Event kind: 'event', 'availability', or 'block'.
 */
function vh360_get_event_kind($event_id) {
    $kind = get_post_meta($event_id, '_vh360_event_kind', true);
    
    // Validate against allowed values
    $allowed_kinds = array('event', 'availability', 'block');
    
    if (empty($kind) || !in_array($kind, $allowed_kinds, true)) {
        return 'event'; // Default to normal event
    }
    
    return $kind;
}

/**
 * Check if an event overlaps with existing blocks or booked availability slots.
 *
 * @param int    $event_id      Event post ID to check.
 * @param int    $author_id     Author user ID.
 * @param string $start_date    Start date (Y-m-d).
 * @param string $start_time    Start time (H:i:s).
 * @param string $end_date      End date (Y-m-d).
 * @param string $end_time      End time (H:i:s).
 * @return array Array with 'has_overlap' boolean and 'message' string.
 */
function vh360_check_event_overlap($event_id, $author_id, $start_date, $start_time, $end_date, $end_time) {
    // Build start and end datetime strings
    $check_start = $start_date . ' ' . (!empty($start_time) ? $start_time : '00:00:00');
    $check_end = !empty($end_date) 
        ? $end_date . ' ' . (!empty($end_time) ? $end_time : '23:59:59') 
        : $start_date . ' ' . (!empty($start_time) && !empty($end_time) ? $end_time : '23:59:59');
    
    $check_start_ts = strtotime($check_start);
    $check_end_ts = strtotime($check_end);
    
    if ($check_start_ts === false || $check_end_ts === false) {
        return array(
            'has_overlap' => false,
            'message' => ''
        );
    }
    
    // Query for potential overlapping events by the same author
    $args = array(
        'post_type' => 'vh360_event',
        'post_status' => 'publish',
        'author' => $author_id,
        'posts_per_page' => -1,
        'post__not_in' => array($event_id), // Exclude the current event
        'meta_query' => array(
            'relation' => 'OR',
            // Get blocks
            array(
                'key' => '_vh360_event_kind',
                'value' => 'block',
                'compare' => '='
            ),
            // Get booked availability slots
            array(
                'relation' => 'AND',
                array(
                    'key' => '_vh360_event_kind',
                    'value' => 'availability',
                    'compare' => '='
                ),
                array(
                    'key' => '_vh360_event_rsvp_count',
                    'value' => 1,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                )
            )
        )
    );
    
    $existing_events = new WP_Query($args);
    
    if (!$existing_events->have_posts()) {
        return array(
            'has_overlap' => false,
            'message' => ''
        );
    }
    
    // Check each existing event for overlap
    while ($existing_events->have_posts()) {
        $existing_events->the_post();
        $existing_id = get_the_ID();
        
        $existing_start_date = get_post_meta($existing_id, '_vh360_event_start_date', true);
        $existing_start_time = get_post_meta($existing_id, '_vh360_event_start_time', true);
        $existing_end_date = get_post_meta($existing_id, '_vh360_event_end_date', true);
        $existing_end_time = get_post_meta($existing_id, '_vh360_event_end_time', true);
        
        if (empty($existing_start_date)) {
            continue;
        }
        
        $existing_start = $existing_start_date . ' ' . (!empty($existing_start_time) ? $existing_start_time : '00:00:00');
        $existing_end = !empty($existing_end_date) 
            ? $existing_end_date . ' ' . (!empty($existing_end_time) ? $existing_end_time : '23:59:59') 
            : $existing_start_date . ' ' . (!empty($existing_start_time) && !empty($existing_end_time) ? $existing_end_time : '23:59:59');
        
        $existing_start_ts = strtotime($existing_start);
        $existing_end_ts = strtotime($existing_end);
        
        if ($existing_start_ts === false || $existing_end_ts === false) {
            continue;
        }
        
        // Check for overlap: two time ranges overlap if one starts before the other ends
        $overlaps = ($check_start_ts < $existing_end_ts) && ($check_end_ts > $existing_start_ts);
        
        if ($overlaps) {
            $existing_kind = vh360_get_event_kind($existing_id);
            $message = $existing_kind === 'block' 
                ? __('This time slot overlaps with a blocked time period.', 'videohub360-theme')
                : __('This time slot overlaps with an already booked appointment.', 'videohub360-theme');
            
            wp_reset_postdata();
            return array(
                'has_overlap' => true,
                'message' => $message
            );
        }
    }
    
    wp_reset_postdata();
    
    return array(
        'has_overlap' => false,
        'message' => ''
    );
}

/**
 * Check if an event is upcoming.
 *
 * @param int $event_id Event post ID.
 * @return bool True if event is upcoming.
 */
function vh360_is_event_upcoming($event_id) {
    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
    
    if (empty($start_date)) {
        return false;
    }
    
    $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
    $datetime = $start_date . (!empty($start_time) ? ' ' . $start_time : ' 00:00:00');
    
    return strtotime($datetime) > current_time('timestamp');
}

/**
 * Check if an event is past.
 *
 * @param int $event_id Event post ID.
 * @return bool True if event is past.
 */
function vh360_is_event_past($event_id) {
    $end_date = get_post_meta($event_id, '_vh360_event_end_date', true);
    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
    
    // Use end date if available, otherwise use start date
    $date_to_check = !empty($end_date) ? $end_date : $start_date;
    
    if (empty($date_to_check)) {
        return false;
    }
    
    $time_to_check = '';
    if (!empty($end_date)) {
        $time_to_check = get_post_meta($event_id, '_vh360_event_end_time', true);
    } else {
        $time_to_check = get_post_meta($event_id, '_vh360_event_start_time', true);
    }
    
    $datetime = $date_to_check . (!empty($time_to_check) ? ' ' . $time_to_check : ' 23:59:59');
    
    return strtotime($datetime) < current_time('timestamp');
}

/**
 * Get event date range formatted.
 *
 * @param int $event_id Event post ID.
 * @return string Formatted date range.
 */
function vh360_get_event_date_range($event_id) {
    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
    $end_date = get_post_meta($event_id, '_vh360_event_end_date', true);
    $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
    $end_time = get_post_meta($event_id, '_vh360_event_end_time', true);
    
    if (empty($start_date)) {
        return '';
    }
    
    $format = get_option('date_format');
    $time_format = get_option('time_format');
    
    $output = date_i18n($format, strtotime($start_date));
    
    if (!empty($start_time)) {
        $output .= ' ' . date_i18n($time_format, strtotime($start_time));
    }
    
    if (!empty($end_date) && $end_date !== $start_date) {
        $output .= ' - ' . date_i18n($format, strtotime($end_date));
        if (!empty($end_time)) {
            $output .= ' ' . date_i18n($time_format, strtotime($end_time));
        }
    } elseif (!empty($end_time) && $end_time !== $start_time) {
        $output .= ' - ' . date_i18n($time_format, strtotime($end_time));
    }
    
    return $output;
}

/**
 * Get event location string.
 *
 * @param int $event_id Event post ID.
 * @return string Formatted location string.
 */
function vh360_get_event_location($event_id) {
    $location_type = get_post_meta($event_id, '_vh360_event_location_type', true);
    
    if ($location_type === 'online') {
        return __('Online Event', 'videohub360-theme');
    }
    
    $venue_name = get_post_meta($event_id, '_vh360_event_venue_name', true);
    $venue_city = get_post_meta($event_id, '_vh360_event_venue_city', true);
    $venue_state = get_post_meta($event_id, '_vh360_event_venue_state', true);
    
    $location_parts = array();
    
    if (!empty($venue_name)) {
        $location_parts[] = $venue_name;
    }
    
    if (!empty($venue_city)) {
        $location_parts[] = $venue_city;
    }
    
    if (!empty($venue_state)) {
        $location_parts[] = $venue_state;
    }
    
    if (empty($location_parts)) {
        return __('Physical Location', 'videohub360-theme');
    }
    
    return implode(', ', $location_parts);
}

/**
 * Get event cost display string.
 *
 * @param int $event_id Event post ID.
 * @return string Formatted cost string.
 */
function vh360_get_event_cost_display($event_id) {
    $cost_type = get_post_meta($event_id, '_vh360_event_cost_type', true);
    $cost_amount = get_post_meta($event_id, '_vh360_event_cost_amount', true);
    
    if ($cost_type === 'free' || empty($cost_type)) {
        return __('Free', 'videohub360-theme');
    }
    
    if ($cost_type === 'donation') {
        if (!empty($cost_amount) && $cost_amount > 0) {
            return sprintf(__('Donation (Suggested: $%s)', 'videohub360-theme'), number_format($cost_amount, 2));
        }
        return __('Donation', 'videohub360-theme');
    }
    
    if ($cost_type === 'paid' && !empty($cost_amount)) {
        return '$' . number_format($cost_amount, 2);
    }
    
    return __('Paid', 'videohub360-theme');
}

/**
 * Check if event registration is open.
 *
 * @param int $event_id Event post ID.
 * @return bool True if registration is open.
 */
function vh360_is_event_registration_open($event_id) {
    $registration_required = get_post_meta($event_id, '_vh360_event_registration_required', true);
    
    if (!$registration_required) {
        return false;
    }
    
    $deadline = get_post_meta($event_id, '_vh360_event_registration_deadline', true);
    
    if (!empty($deadline)) {
        return strtotime($deadline) > current_time('timestamp');
    }
    
    // If no deadline, check if event hasn't started yet
    return vh360_is_event_upcoming($event_id);
}

/**
 * Get events query args.
 *
 * @param array $args Additional query arguments.
 * @return array Query arguments.
 */
function vh360_get_events_query_args($args = array()) {
    $defaults = array(
        'post_type'      => 'vh360_event',
        'post_status'    => 'publish',
        'posts_per_page' => get_theme_mod('vh360_events_per_page', 12),
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_key'       => '_vh360_event_start_date',
        'meta_type'      => 'DATE',
    );
    
    return wp_parse_args($args, $defaults);
}

/**
 * Get upcoming events.
 *
 * @param int $count Number of events to retrieve.
 * @return WP_Query Events query object.
 */
function vh360_get_upcoming_events($count = 5) {
    $args = vh360_get_events_query_args(array(
        'posts_per_page' => $count,
        'meta_query'     => array(
            array(
                'key'     => '_vh360_event_start_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    ));
    
    return new WP_Query($args);
}

/**
 * Generate .ics file content for an event.
 *
 * @param int $event_id Event post ID.
 * @return string ICS file content.
 */
function vh360_generate_event_ics($event_id) {
    $event = get_post($event_id);
    
    if (!$event || $event->post_type !== 'vh360_event') {
        return '';
    }
    
    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
    $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
    $end_date = get_post_meta($event_id, '_vh360_event_end_date', true);
    $end_time = get_post_meta($event_id, '_vh360_event_end_time', true);
    
    // Validate start date exists
    if (empty($start_date)) {
        return '';
    }
    
    try {
        // Format dates for ICS using WordPress timezone
        $wp_timezone = wp_timezone();
        
        $start_datetime_obj = new DateTime($start_date . ' ' . (!empty($start_time) ? $start_time : '00:00:00'), $wp_timezone);
        $start_datetime = $start_datetime_obj->format('Ymd\THis');
        
        if (!empty($end_date)) {
            $end_datetime_obj = new DateTime($end_date . ' ' . (!empty($end_time) ? $end_time : '23:59:59'), $wp_timezone);
            $end_datetime = $end_datetime_obj->format('Ymd\THis');
        } else {
            // Default to 1 hour duration
            $end_datetime_obj = clone $start_datetime_obj;
            $end_datetime_obj->modify('+1 hour');
            $end_datetime = $end_datetime_obj->format('Ymd\THis');
        }
    } catch (Exception $e) {
        // Log error for debugging
        vh360_debug_log('VH360 Event ICS Generation Error: ' . $e->getMessage() . ' for event ID: ' . $event_id);
        // If date parsing fails, return empty
        return '';
    }
    
    $location = vh360_get_event_location($event_id);
    $description = wp_strip_all_tags($event->post_content);
    
    // Escape ICS text according to RFC 5545
    // Escape backslashes first, then commas and semicolons, then convert newlines
    $title_escaped = str_replace('\\', '\\\\', $event->post_title);
    $title_escaped = str_replace(',', '\\,', $title_escaped);
    $title_escaped = str_replace(';', '\\;', $title_escaped);
    $title_escaped = str_replace("\n", '\\n', $title_escaped);
    $title_escaped = str_replace("\r", '', $title_escaped);
    
    $description_escaped = str_replace('\\', '\\\\', $description);
    $description_escaped = str_replace(',', '\\,', $description_escaped);
    $description_escaped = str_replace(';', '\\;', $description_escaped);
    $description_escaped = str_replace("\n", '\\n', $description_escaped);
    $description_escaped = str_replace("\r", '', $description_escaped);
    
    $location_escaped = str_replace('\\', '\\\\', $location);
    $location_escaped = str_replace(',', '\\,', $location_escaped);
    $location_escaped = str_replace(';', '\\;', $location_escaped);
    $location_escaped = str_replace("\n", '\\n', $location_escaped);
    $location_escaped = str_replace("\r", '', $location_escaped);
    
    // Build ICS content
    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//Videohub360//Events//EN\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $event_id . "@" . parse_url(home_url(), PHP_URL_HOST) . "\r\n";
    $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    $ics .= "DTSTART:" . $start_datetime . "\r\n";
    $ics .= "DTEND:" . $end_datetime . "\r\n";
    $ics .= "SUMMARY:" . $title_escaped . "\r\n";
    $ics .= "DESCRIPTION:" . $description_escaped . "\r\n";
    $ics .= "LOCATION:" . $location_escaped . "\r\n";
    $ics .= "URL:" . get_permalink($event_id) . "\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";
    
    return $ics;
}

/**
 * Get event status badge HTML.
 *
 * @param int $event_id Event post ID.
 * @return string HTML for status badge.
 */
function vh360_get_event_status_badge($event_id) {
    $status = get_post_meta($event_id, '_vh360_event_status', true);
    
    if (empty($status)) {
        $status = 'scheduled';
    }
    
    $status_labels = array(
        'scheduled' => __('Scheduled', 'videohub360-theme'),
        'cancelled' => __('Cancelled', 'videohub360-theme'),
        'postponed' => __('Postponed', 'videohub360-theme'),
        'completed' => __('Completed', 'videohub360-theme'),
    );
    
    $status_classes = array(
        'scheduled' => 'vh360-event-status-scheduled',
        'cancelled' => 'vh360-event-status-cancelled',
        'postponed' => 'vh360-event-status-postponed',
        'completed' => 'vh360-event-status-completed',
    );
    
    if (!isset($status_labels[$status])) {
        return '';
    }
    
    return sprintf(
        '<span class="vh360-event-status-badge %s">%s</span>',
        esc_attr($status_classes[$status]),
        esc_html($status_labels[$status])
    );
}

/**
 * Sanitize an array (or comma-separated string) of event gallery image attachment IDs.
 *
 * Validates that each ID is a real image attachment, removes duplicates, and
 * limits the result to a maximum of 5 IDs.
 *
 * @param array|string $raw_ids Raw array or comma-separated string of attachment IDs.
 * @return int[] Sanitized array of attachment IDs (max 5).
 */
function vh360_sanitize_event_gallery_image_ids( $raw_ids ) {
    if ( is_string( $raw_ids ) ) {
    $raw_ids = explode( ',', $raw_ids );
    }

    if ( ! is_array( $raw_ids ) ) {
    return array();
    }

    $ids = array();

    foreach ( $raw_ids as $id ) {
    $id = absint( $id );

    if ( ! $id || in_array( $id, $ids, true ) ) {
        continue;
    }

    if ( 'attachment' !== get_post_type( $id ) ) {
        continue;
    }

    $mime_type = get_post_mime_type( $id );

    if ( ! $mime_type || 0 !== strpos( (string) $mime_type, 'image/' ) ) {
        continue;
    }

    $ids[] = $id;

    if ( count( $ids ) >= 5 ) {
        break;
    }
    }

    return $ids;
}
