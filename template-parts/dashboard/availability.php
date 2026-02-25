<?php
/**
 * Dashboard Availability Settings
 *
 * Template for professionals to manage their appointment availability.
 *
 * @package Videohub360_Theme
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only for logged-in users
if (!is_user_logged_in()) {
    return;
}

$current_user_id = get_current_user_id();

// Only for professionals/organizations
$account_type = vh360_get_user_account_type($current_user_id);
if (!in_array($account_type, array('professional', 'organization'), true)) {
    return;
}

// Get current settings
$settings = vh360_get_availability_settings($current_user_id);
$days = array(
    'mon' => __('Monday', 'videohub360-theme'),
    'tue' => __('Tuesday', 'videohub360-theme'),
    'wed' => __('Wednesday', 'videohub360-theme'),
    'thu' => __('Thursday', 'videohub360-theme'),
    'fri' => __('Friday', 'videohub360-theme'),
    'sat' => __('Saturday', 'videohub360-theme'),
    'sun' => __('Sunday', 'videohub360-theme'),
);
?>

<div class="vh360-dashboard-availability">
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Availability Settings', 'videohub360-theme'); ?></h1>
        <p class="vh360-dashboard-description">
            <?php esc_html_e('Set your weekly availability schedule for appointment bookings. Clients will be able to book appointments during your available times.', 'videohub360-theme'); ?>
        </p>
    </div>
    
    <div class="vh360-dashboard-card">
        <form id="vh360-availability-form" class="vh360-form">
            
            <!-- General Settings -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('General Settings', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="slot_minutes" class="vh360-form-label">
                        <?php esc_html_e('Appointment Duration (minutes)', 'videohub360-theme'); ?>
                    </label>
                    <select id="slot_minutes" name="slot_minutes" class="vh360-form-control">
                        <option value="15" <?php selected($settings['slot_minutes'], 15); ?>>15 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="30" <?php selected($settings['slot_minutes'], 30); ?>>30 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="45" <?php selected($settings['slot_minutes'], 45); ?>>45 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="60" <?php selected($settings['slot_minutes'], 60); ?>>1 <?php esc_html_e('hour', 'videohub360-theme'); ?></option>
                        <option value="90" <?php selected($settings['slot_minutes'], 90); ?>>1.5 <?php esc_html_e('hours', 'videohub360-theme'); ?></option>
                        <option value="120" <?php selected($settings['slot_minutes'], 120); ?>>2 <?php esc_html_e('hours', 'videohub360-theme'); ?></option>
                    </select>
                </div>
                
                <div class="vh360-form-group">
                    <label for="buffer_minutes" class="vh360-form-label">
                        <?php esc_html_e('Buffer Time Between Appointments (minutes)', 'videohub360-theme'); ?>
                    </label>
                    <select id="buffer_minutes" name="buffer_minutes" class="vh360-form-control">
                        <option value="0" <?php selected($settings['buffer_minutes'], 0); ?>><?php esc_html_e('No buffer', 'videohub360-theme'); ?></option>
                        <option value="5" <?php selected($settings['buffer_minutes'], 5); ?>>5 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="10" <?php selected($settings['buffer_minutes'], 10); ?>>10 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="15" <?php selected($settings['buffer_minutes'], 15); ?>>15 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                        <option value="30" <?php selected($settings['buffer_minutes'], 30); ?>>30 <?php esc_html_e('minutes', 'videohub360-theme'); ?></option>
                    </select>
                    <small class="vh360-form-help">
                        <?php esc_html_e('Buffer time prevents back-to-back bookings and gives you a break between appointments.', 'videohub360-theme'); ?>
                    </small>
                </div>
            </div>
            
            <!-- Weekly Schedule -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Weekly Schedule', 'videohub360-theme'); ?></h3>
                <p class="vh360-form-help">
                    <?php esc_html_e('Set your available hours for each day of the week. You can add multiple time blocks per day.', 'videohub360-theme'); ?>
                </p>
                
                <div id="vh360-weekly-schedule" class="vh360-weekly-schedule">
                    <?php foreach ($days as $day_key => $day_label) : 
                        $day_slots = isset($settings['weekly'][$day_key]) ? $settings['weekly'][$day_key] : array();
                    ?>
                    <div class="vh360-day-schedule" data-day="<?php echo esc_attr($day_key); ?>">
                        <h4 class="vh360-day-label"><?php echo esc_html($day_label); ?></h4>
                        <div class="vh360-day-slots" data-day="<?php echo esc_attr($day_key); ?>">
                            <?php if (empty($day_slots)) : ?>
                                <div class="vh360-no-slots">
                                    <p><?php esc_html_e('No availability set', 'videohub360-theme'); ?></p>
                                </div>
                            <?php else : ?>
                                <?php foreach ($day_slots as $index => $slot) : ?>
                                    <div class="vh360-time-slot">
                                        <input type="time" class="vh360-time-input" name="<?php echo esc_attr($day_key); ?>_start[]" value="<?php echo esc_attr($slot['start']); ?>" required>
                                        <span class="vh360-time-separator">-</span>
                                        <input type="time" class="vh360-time-input" name="<?php echo esc_attr($day_key); ?>_end[]" value="<?php echo esc_attr($slot['end']); ?>" required>
                                        <button type="button" class="vh360-btn-remove-slot" data-day="<?php echo esc_attr($day_key); ?>">
                                            <?php esc_html_e('Remove', 'videohub360-theme'); ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="vh360-btn-add-slot vh360-btn-secondary" data-day="<?php echo esc_attr($day_key); ?>">
                            + <?php esc_html_e('Add Time Block', 'videohub360-theme'); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="vh360-form-actions">
                <button type="submit" class="vh360-btn vh360-btn-primary" id="vh360-save-availability">
                    <span class="vh360-btn-text"><?php esc_html_e('Save Availability', 'videohub360-theme'); ?></span>
                    <span class="vh360-btn-loading" style="display: none;">
                        <svg class="vh360-spinner" width="20" height="20" viewBox="0 0 50 50">
                            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Upcoming Appointments Section -->
    <?php
    // Query upcoming appointments for this professional
    $now = current_time('mysql');
    $appointments_query = new WP_Query(array(
        'post_type' => 'vh360_event',
        'author' => $current_user_id,
        'post_status' => 'publish',
        'posts_per_page' => 10,
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
                'value' => date('Y-m-d', strtotime($now)),
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
    
    if ($appointments_query->have_posts()) :
    ?>
    <div class="vh360-dashboard-card" style="margin-top: 2rem;">
        <h2 class="vh360-dashboard-card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <?php esc_html_e('Upcoming Appointments', 'videohub360-theme'); ?>
        </h2>
        <p class="vh360-form-help" style="margin-bottom: 1.5rem;">
            <?php esc_html_e('Manage your scheduled appointment sessions. Start sessions when ready to go live with your clients.', 'videohub360-theme'); ?>
        </p>
        
        <div class="vh360-appointments-list">
            <?php 
            while ($appointments_query->have_posts()) : 
                $appointments_query->the_post();
                $appointment_id = get_the_ID();
                $start_date = get_post_meta($appointment_id, '_vh360_event_start_date', true);
                $start_time = get_post_meta($appointment_id, '_vh360_event_start_time', true);
                $end_date = get_post_meta($appointment_id, '_vh360_event_end_date', true);
                $end_time = get_post_meta($appointment_id, '_vh360_event_end_time', true);
                $rsvps = get_post_meta($appointment_id, '_vh360_event_rsvps', true);
                $live_room_id = get_post_meta($appointment_id, '_vh360_appointment_live_room_id', true);
                
                // Get client info
                $client_name = __('Unknown Client', 'videohub360-theme');
                if (is_array($rsvps) && !empty($rsvps)) {
                    $client_id = $rsvps[0]['user_id'];
                    $client = get_userdata($client_id);
                    if ($client) {
                        $client_name = $client->display_name;
                    }
                }
                
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
                
                // Determine status
                $status_class = 'offline';
                $status_label = __('Offline', 'videohub360-theme');
                if ($stream_stopped) {
                    $status_class = 'ended';
                    $status_label = __('Ended', 'videohub360-theme');
                } elseif ($is_live) {
                    $status_class = 'live';
                    $status_label = __('Live Now', 'videohub360-theme');
                }
            ?>
            <div class="vh360-appointment-item" data-appointment-id="<?php echo esc_attr($appointment_id); ?>" data-live-room-id="<?php echo esc_attr($live_room_id); ?>">
                <div class="vh360-appointment-info">
                    <div class="vh360-appointment-header">
                        <h4 class="vh360-appointment-title"><?php echo esc_html($client_name); ?></h4>
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
                </div>
                <div class="vh360-appointment-actions">
                    <?php if ($live_room_id && $live_room_url) : ?>
                        <a href="<?php echo esc_url($live_room_url); ?>" class="vh360-dashboard-btn vh360-dashboard-btn-secondary" target="_blank">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                            </svg>
                            <?php esc_html_e('Open Room', 'videohub360-theme'); ?>
                        </a>
                        
                        <?php if (!$stream_stopped) : ?>
                            <?php if ($is_live) : ?>
                                <button class="vh360-dashboard-btn vh360-dashboard-btn-danger vh360-end-session-btn" 
                                        data-live-room-id="<?php echo esc_attr($live_room_id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('vh360_end_stream'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="5" y="5" width="14" height="14" rx="2" ry="2"></rect>
                                    </svg>
                                    <?php esc_html_e('End Session', 'videohub360-theme'); ?>
                                </button>
                            <?php else : ?>
                                <button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-start-session-btn" 
                                        data-live-room-id="<?php echo esc_attr($live_room_id); ?>"
                                        data-nonce="<?php echo wp_create_nonce('vh360_agora_token'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polygon points="10 8 16 12 10 16 10 8"></polygon>
                                    </svg>
                                    <?php esc_html_e('Start Session', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
            endwhile;
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.vh360-weekly-schedule {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.vh360-day-schedule {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
}

.vh360-day-label {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.vh360-day-slots {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.vh360-time-slot {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vh360-time-input {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.vh360-time-separator {
    color: #6b7280;
    font-weight: 500;
}

.vh360-btn-remove-slot {
    padding: 0.5rem 1rem;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.vh360-btn-remove-slot:hover {
    background: #dc2626;
}

.vh360-btn-add-slot {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.vh360-btn-add-slot:hover {
    background: #e5e7eb;
}

.vh360-no-slots {
    color: #6b7280;
    font-style: italic;
}

/* Appointments List Styles */
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

.vh360-status-ended {
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

.vh360-appointment-date svg,
.vh360-appointment-time svg {
    flex-shrink: 0;
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

.vh360-dashboard-btn svg {
    flex-shrink: 0;
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

.vh360-dashboard-btn-danger {
    background: #ef4444;
    color: white;
}

.vh360-dashboard-btn-danger:hover {
    background: #dc2626;
}

.vh360-dashboard-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.vh360-spinner {
    animation: spin 1s linear infinite;
}
</style>

<script>
(function($) {
    'use strict';
    
    // Add time slot
    $(document).on('click', '.vh360-btn-add-slot', function() {
        var day = $(this).data('day');
        var $container = $('.vh360-day-slots[data-day="' + day + '"]');
        
        // Remove "no slots" message if exists
        $container.find('.vh360-no-slots').remove();
        
        // Add new slot
        var $slot = $('<div class="vh360-time-slot"></div>');
        $slot.append('<input type="time" class="vh360-time-input" name="' + day + '_start[]" value="09:00" required>');
        $slot.append('<span class="vh360-time-separator">-</span>');
        $slot.append('<input type="time" class="vh360-time-input" name="' + day + '_end[]" value="17:00" required>');
        $slot.append('<button type="button" class="vh360-btn-remove-slot" data-day="' + day + '">' + '<?php esc_html_e("Remove", "videohub360-theme"); ?>' + '</button>');
        
        $container.append($slot);
    });
    
    // Remove time slot
    $(document).on('click', '.vh360-btn-remove-slot', function() {
        var $slot = $(this).closest('.vh360-time-slot');
        var day = $(this).data('day');
        var $container = $('.vh360-day-slots[data-day="' + day + '"]');
        
        $slot.remove();
        
        // Show "no slots" message if no slots left
        if ($container.find('.vh360-time-slot').length === 0) {
            $container.append('<div class="vh360-no-slots"><p><?php esc_html_e("No availability set", "videohub360-theme"); ?></p></div>');
        }
    });
    
    // Submit form
    $('#vh360-availability-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#vh360-save-availability');
        
        // Collect weekly data
        var weekly = {
            mon: [],
            tue: [],
            wed: [],
            thu: [],
            fri: [],
            sat: [],
            sun: []
        };
        
        $('.vh360-time-slot').each(function() {
            var $slot = $(this);
            var day = $slot.closest('.vh360-day-slots').data('day');
            var start = $slot.find('input[name="' + day + '_start[]"]').val();
            var end = $slot.find('input[name="' + day + '_end[]"]').val();
            
            if (start && end) {
                weekly[day].push({ start: start, end: end });
            }
        });
        
        // Show loading
        $submitBtn.prop('disabled', true);
        $submitBtn.find('.vh360-btn-text').hide();
        $submitBtn.find('.vh360-btn-loading').show();
        
        $.ajax({
            url: vh360Ajax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vh360_save_availability_settings',
                nonce: vh360Ajax.nonce,
                slot_minutes: $('#slot_minutes').val(),
                buffer_minutes: $('#buffer_minutes').val(),
                weekly: JSON.stringify(weekly)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message || '<?php esc_html_e("Error saving settings", "videohub360-theme"); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e("Network error", "videohub360-theme"); ?>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.vh360-btn-text').show();
                $submitBtn.find('.vh360-btn-loading').hide();
            }
        });
    });
    
    // Handle Start Session button
    $(document).on('click', '.vh360-start-session-btn', function() {
        var $btn = $(this);
        var liveRoomId = $btn.data('live-room-id');
        var nonce = $btn.data('nonce');
        
        if (!liveRoomId) {
            alert('<?php esc_html_e("Error: Live Room ID not found", "videohub360-theme"); ?>');
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<svg class="vh360-spinner" width="16" height="16" viewBox="0 0 50 50" style="animation: spin 1s linear infinite;"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5"></circle></svg> <?php esc_html_e("Starting...", "videohub360-theme"); ?>');
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'vh360_set_stream_status',
                post_id: liveRoomId,
                status: 'yes',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to show "Live" status
                    var $appointmentItem = $btn.closest('.vh360-appointment-item');
                    $appointmentItem.find('.vh360-appointment-status')
                        .removeClass('vh360-status-offline')
                        .addClass('vh360-status-live')
                        .text('<?php esc_html_e("Live Now", "videohub360-theme"); ?>');
                    
                    // Replace Start button with End button (with correct nonce for vh360_end_stream)
                    var endNonce = '<?php echo wp_create_nonce('vh360_end_stream'); ?>';
                    $btn.replaceWith(
                        '<button class="vh360-dashboard-btn vh360-dashboard-btn-danger vh360-end-session-btn" ' +
                        'data-live-room-id="' + liveRoomId + '" ' +
                        'data-nonce="' + endNonce + '">' +
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                        '<rect x="5" y="5" width="14" height="14" rx="2" ry="2"></rect>' +
                        '</svg> ' +
                        '<?php esc_html_e("End Session", "videohub360-theme"); ?>' +
                        '</button>'
                    );
                    
                    // Show success message
                    alert('<?php esc_html_e("Session started successfully! You are now live.", "videohub360-theme"); ?>');
                } else {
                    alert(response.data || '<?php esc_html_e("Error starting session", "videohub360-theme"); ?>');
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                }
            },
            error: function() {
                alert('<?php esc_html_e("Network error. Please try again.", "videohub360-theme"); ?>');
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    });
    
    // Handle End Session button
    $(document).on('click', '.vh360-end-session-btn', function() {
        if (!confirm('<?php esc_html_e("Are you sure you want to end this session?", "videohub360-theme"); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var liveRoomId = $btn.data('live-room-id');
        var nonce = $btn.data('nonce');
        
        if (!liveRoomId) {
            alert('<?php esc_html_e("Error: Live Room ID not found", "videohub360-theme"); ?>');
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<svg class="vh360-spinner" width="16" height="16" viewBox="0 0 50 50" style="animation: spin 1s linear infinite;"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="5"></circle></svg> <?php esc_html_e("Ending...", "videohub360-theme"); ?>');
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'vh360_end_stream',
                post_id: liveRoomId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to show "Offline" status
                    var $appointmentItem = $btn.closest('.vh360-appointment-item');
                    $appointmentItem.find('.vh360-appointment-status')
                        .removeClass('vh360-status-live')
                        .addClass('vh360-status-offline')
                        .text('<?php esc_html_e("Offline", "videohub360-theme"); ?>');
                    
                    // Replace End button with Start button (with correct nonce for vh360_set_stream_status)
                    var startNonce = '<?php echo wp_create_nonce('vh360_agora_token'); ?>';
                    $btn.replaceWith(
                        '<button class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-start-session-btn" ' +
                        'data-live-room-id="' + liveRoomId + '" ' +
                        'data-nonce="' + startNonce + '">' +
                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                        '<circle cx="12" cy="12" r="10"></circle>' +
                        '<polygon points="10 8 16 12 10 16 10 8"></polygon>' +
                        '</svg> ' +
                        '<?php esc_html_e("Start Session", "videohub360-theme"); ?>' +
                        '</button>'
                    );
                    
                    // Show success message
                    alert('<?php esc_html_e("Session ended successfully.", "videohub360-theme"); ?>');
                } else {
                    alert(response.data || '<?php esc_html_e("Error ending session", "videohub360-theme"); ?>');
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                }
            },
            error: function() {
                alert('<?php esc_html_e("Network error. Please try again.", "videohub360-theme"); ?>');
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    });
    
})(jQuery);
</script>
<?php
