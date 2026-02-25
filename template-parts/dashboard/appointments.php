<?php
/**
 * Dashboard My Appointments Tab
 *
 * Shows appointments that the current user has booked with professionals.
 * Clients can view their upcoming appointments and join live sessions.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('You must be logged in to view appointments.', 'videohub360-theme') . '</p>';
    return;
}

$current_user_id = get_current_user_id();

// Query all appointments where this user is a participant (in RSVPs)
// We need to query all availability events and then filter by RSVP in PHP
$appointments_query = new WP_Query(array(
    'post_type' => 'vh360_event',
    'post_status' => 'publish',
    'posts_per_page' => 50,
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_vh360_event_kind',
            'value' => 'availability',
        ),
        array(
            'key' => '_vh360_event_start_date',
            'value' => date('Y-m-d', strtotime('-7 days')), // Show appointments from last 7 days
            'compare' => '>=',
            'type' => 'DATE',
        ),
        array(
            'key' => '_vh360_event_rsvp_count',
            'value' => 0,
            'compare' => '>',
            'type' => 'NUMERIC',
        ),
    ),
));

// Filter to only appointments where current user is in RSVP list
$user_appointments = array();
if ($appointments_query->have_posts()) {
    while ($appointments_query->have_posts()) {
        $appointments_query->the_post();
        $appointment_id = get_the_ID();
        $rsvps = get_post_meta($appointment_id, '_vh360_event_rsvps', true);
        
        // Check if current user is in the RSVP list
        if (is_array($rsvps)) {
            foreach ($rsvps as $rsvp) {
                if (isset($rsvp['user_id']) && (int) $rsvp['user_id'] === $current_user_id) {
                    $user_appointments[] = $appointment_id;
                    break;
                }
            }
        }
    }
    wp_reset_postdata();
}
?>

<div class="vh360-dashboard-section vh360-dashboard-appointments">
    <header class="vh360-dashboard-section-header">
        <h2 class="vh360-dashboard-section-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
                <circle cx="12" cy="13" r="3"></circle>
            </svg>
            <?php esc_html_e('My Appointments', 'videohub360-theme'); ?>
        </h2>
        <p class="vh360-dashboard-section-subtitle">
            <?php esc_html_e('View your booked appointments and join live sessions when they start.', 'videohub360-theme'); ?>
        </p>
    </header>

    <div class="vh360-dashboard-card">
        <?php if (!empty($user_appointments)) : ?>
            <div class="vh360-appointments-list">
                <?php 
                foreach ($user_appointments as $appointment_id) :
                    $start_date = get_post_meta($appointment_id, '_vh360_event_start_date', true);
                    $start_time = get_post_meta($appointment_id, '_vh360_event_start_time', true);
                    $end_date = get_post_meta($appointment_id, '_vh360_event_end_date', true);
                    $end_time = get_post_meta($appointment_id, '_vh360_event_end_time', true);
                    $live_room_id = get_post_meta($appointment_id, '_vh360_appointment_live_room_id', true);
                    
                    // Get professional info
                    $professional_id = get_post_field('post_author', $appointment_id);
                    $professional = get_userdata($professional_id);
                    $professional_name = $professional ? $professional->display_name : __('Unknown Professional', 'videohub360-theme');
                    
                    // Get Live Room status
                    $is_live = false;
                    $live_room_url = '';
                    $stream_stopped = false;
                    if ($live_room_id) {
                        $is_live = get_post_meta($live_room_id, '_vh360_is_live', true) === 'yes';
                        $stream_stopped = get_post_meta($live_room_id, '_vh360_stream_stopped', true) === 'yes';
                        $live_room_url = get_permalink($live_room_id);
                    }
                    
                    // Format date/time
                    $datetime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $start_date . ' ' . $start_time);
                    $formatted_date = $datetime_obj ? $datetime_obj->format(get_option('date_format')) : $start_date;
                    $formatted_time = $datetime_obj ? $datetime_obj->format(get_option('time_format')) : $start_time;
                    
                    // Check if appointment is in the past
                    $is_past = false;
                    if ($datetime_obj) {
                        $now = new DateTime('now', new DateTimeZone(wp_timezone_string()));
                        $is_past = $datetime_obj < $now;
                    }
                    
                    // Determine status
                    $status_class = 'offline';
                    $status_label = __('Scheduled', 'videohub360-theme');
                    if ($stream_stopped) {
                        $status_class = 'ended';
                        $status_label = __('Ended', 'videohub360-theme');
                    } elseif ($is_live) {
                        $status_class = 'live';
                        $status_label = __('Live Now', 'videohub360-theme');
                    } elseif ($is_past) {
                        $status_class = 'past';
                        $status_label = __('Past', 'videohub360-theme');
                    }
                ?>
                <div class="vh360-appointment-item">
                    <div class="vh360-appointment-info">
                        <div class="vh360-appointment-header">
                            <h4 class="vh360-appointment-title">
                                <?php esc_html_e('Appointment with', 'videohub360-theme'); ?> 
                                <?php echo esc_html($professional_name); ?>
                            </h4>
                            <span class="vh360-appointment-status vh360-status-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </div>
                        <div class="vh360-appointment-meta">
                            <span class="vh360-appointment-date">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo esc_html($formatted_date); ?>
                            </span>
                            <span class="vh360-appointment-time">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html($formatted_time); ?>
                            </span>
                        </div>
                        <?php if ($is_live) : ?>
                            <div class="vh360-appointment-live-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color: #16a34a;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                </svg>
                                <span><?php esc_html_e('The professional is live now! Click "Join Session" to enter.', 'videohub360-theme'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="vh360-appointment-actions">
                        <?php if ($live_room_id && $live_room_url) : ?>
                            <a href="<?php echo esc_url($live_room_url); ?>" 
                               class="vh360-dashboard-btn <?php echo $is_live ? 'vh360-dashboard-btn-primary vh360-pulse' : 'vh360-dashboard-btn-secondary'; ?>" 
                               target="_blank">
                                <?php if ($is_live) : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polygon points="10 8 16 12 10 16 10 8" fill="white"></polygon>
                                    </svg>
                                    <?php esc_html_e('Join Session', 'videohub360-theme'); ?>
                                <?php else : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                                    </svg>
                                    <?php esc_html_e('View Room', 'videohub360-theme'); ?>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="vh360-dashboard-empty">
                <div class="vh360-dashboard-empty-icon">📅</div>
                <p class="vh360-dashboard-empty-title">
                    <?php esc_html_e('No appointments yet', 'videohub360-theme'); ?>
                </p>
                <p class="vh360-dashboard-empty-text">
                    <?php esc_html_e('When you book appointments with professionals, they will appear here.', 'videohub360-theme'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.vh360-appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-appointment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #ffffff;
    transition: box-shadow 0.2s;
}

.vh360-appointment-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.vh360-appointment-info {
    flex: 1;
}

.vh360-appointment-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.vh360-appointment-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.vh360-appointment-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.vh360-status-live {
    background: #dcfce7;
    color: #166534;
}

.vh360-status-offline {
    background: #f3f4f6;
    color: #6b7280;
}

.vh360-status-ended,
.vh360-status-past {
    background: #fee2e2;
    color: #991b1b;
}

.vh360-appointment-meta {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    color: #6b7280;
    font-size: 0.875rem;
}

.vh360-appointment-date,
.vh360-appointment-time {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vh360-appointment-live-notice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.5rem;
    background: #dcfce7;
    border-radius: 0.375rem;
    color: #166534;
    font-size: 0.875rem;
    font-weight: 500;
}

.vh360-appointment-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.vh360-dashboard-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: none;
}

.vh360-dashboard-btn-primary {
    background: #2563eb;
    color: white;
}

.vh360-dashboard-btn-primary:hover {
    background: #1d4ed8;
    color: white;
}

.vh360-dashboard-btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.vh360-dashboard-btn-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

.vh360-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .8;
    }
}

@media (max-width: 768px) {
    .vh360-appointment-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .vh360-appointment-actions {
        width: 100%;
    }
    
    .vh360-appointment-actions .vh360-dashboard-btn {
        flex: 1;
    }
}
</style>
