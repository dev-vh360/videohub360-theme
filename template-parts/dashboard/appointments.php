<?php
/**
 * Dashboard Appointments Tab
 *
 * Shows appointments for both professionals (managing sessions) and clients (viewing bookings).
 * Professionals see appointments they need to manage with session controls.
 * Clients see their booked appointments with join buttons.
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
$user_account_type = vh360_get_user_account_type($current_user_id);
$is_professional = in_array($user_account_type, array('professional', 'organization'), true);

// Role-based routing
if ($is_professional) {
    // PROFESSIONAL VIEW: Query appointments as the provider
    $appointments_query = new WP_Query(array(
        'post_type' => 'vh360_event',
        'author' => $current_user_id,
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
                'value' => date('Y-m-d', strtotime('-7 days')), // Show from last 7 days
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
    
    $user_appointments = array();
    if ($appointments_query->have_posts()) {
        while ($appointments_query->have_posts()) {
            $appointments_query->the_post();
            $user_appointments[] = get_the_ID();
        }
        wp_reset_postdata();
    }
} else {
    // CLIENT VIEW: Query appointments where this user is in RSVPs
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
                'value' => date('Y-m-d', strtotime('-7 days')),
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
            <?php 
            if ($is_professional) {
                esc_html_e('Appointments', 'videohub360-theme');
            } else {
                esc_html_e('My Appointments', 'videohub360-theme');
            }
            ?>
        </h2>
        <p class="vh360-dashboard-section-subtitle">
            <?php 
            if ($is_professional) {
                esc_html_e('Manage your scheduled appointment sessions. Start and end sessions when meeting with clients.', 'videohub360-theme');
            } else {
                esc_html_e('View your booked appointments and join live sessions when they start.', 'videohub360-theme');
            }
            ?>
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
                    
                    if ($is_professional) {
                        // Professional view: show client name
                        $rsvps = get_post_meta($appointment_id, '_vh360_event_rsvps', true);
                        $participant_name = __('Unknown Client', 'videohub360-theme');
                        if (is_array($rsvps) && !empty($rsvps)) {
                            $client_id = $rsvps[0]['user_id'];
                            $client = get_userdata($client_id);
                            if ($client) {
                                $participant_name = $client->display_name;
                            }
                        }
                    } else {
                        // Client view: show professional name
                        $professional_id = get_post_field('post_author', $appointment_id);
                        $professional = get_userdata($professional_id);
                        $participant_name = $professional ? $professional->display_name : __('Unknown Professional', 'videohub360-theme');
                    }
                    
                    // Get Live Room status
                    $is_live = false;
                    $live_room_url = '';
                    $stream_stopped = false;
                    if ($live_room_id) {
                        // Check actual stream state for professionals, UI mode for clients
                        if ($is_professional) {
                            $is_live = get_post_meta($live_room_id, '_vh360_agora_stream_live', true) === 'yes';
                        } else {
                            $is_live = get_post_meta($live_room_id, '_vh360_is_live', true) === 'yes';
                        }
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
                    
                    // Get appointment session state using timing helper
                    $session_state = array(
                        'status' => 'scheduled',
                        'can_view_page' => true,
                        'can_generate_token' => false,
                        'message' => '',
                    );
                    
                    if (function_exists('vh360_get_appointment_session_state') && $live_room_id) {
                        $session_state = vh360_get_appointment_session_state($live_room_id, $current_user_id);
                    }
                    
                    // Determine status
                    $status_class = 'offline';
                    $status_label = $is_professional ? __('Offline', 'videohub360-theme') : __('Scheduled', 'videohub360-theme');
                    
                    if ($stream_stopped) {
                        $status_class = 'ended';
                        $status_label = __('Ended', 'videohub360-theme');
                    } elseif ($is_live) {
                        $status_class = 'live';
                        $status_label = __('Live Now', 'videohub360-theme');
                    } elseif ($session_state['status'] === 'too_early') {
                        $status_class = 'scheduled';
                        $status_label = __('Scheduled', 'videohub360-theme');
                    } elseif ($session_state['status'] === 'waiting_for_host') {
                        $status_class = 'waiting';
                        $status_label = __('Waiting', 'videohub360-theme');
                    } elseif (!$is_professional && $is_past) {
                        $status_class = 'past';
                        $status_label = __('Past', 'videohub360-theme');
                    }
                ?>
                <div class="vh360-appointment-item" data-appointment-id="<?php echo esc_attr($appointment_id); ?>" data-live-room-id="<?php echo esc_attr($live_room_id); ?>">
                    <div class="vh360-appointment-info">
                        <div class="vh360-appointment-header">
                            <h4 class="vh360-appointment-title">
                                <?php 
                                if ($is_professional) {
                                    echo esc_html($participant_name);
                                } else {
                                    esc_html_e('Appointment with', 'videohub360-theme');
                                    echo ' ' . esc_html($participant_name);
                                }
                                ?>
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
                        <?php if (!$is_professional && $is_live) : ?>
                            <div class="vh360-appointment-live-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="vh360-appointment-live-icon">
                                    <circle cx="12" cy="12" r="10"></circle>
                                </svg>
                                <span><?php esc_html_e('The professional is live now! Click "Join Session" to enter.', 'videohub360-theme'); ?></span>
                            </div>
                        <?php elseif (!$is_professional && $session_state['status'] === 'too_early') : ?>
                            <div class="vh360-appointment-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span><?php echo esc_html($session_state['message']); ?></span>
                            </div>
                        <?php elseif (!$is_professional && $session_state['status'] === 'waiting_for_host') : ?>
                            <div class="vh360-appointment-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <span><?php echo esc_html($session_state['message']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="vh360-appointment-actions">
                        <?php if ($live_room_id && $live_room_url) : ?>
                            <?php if ($is_professional) : ?>
                                <!-- Professional controls -->
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
                            <?php else : ?>
                                <!-- Client controls -->
                                <?php if ($session_state['status'] === 'too_early') : ?>
                                    <!-- Before early-join window -->
                                    <button class="vh360-dashboard-btn vh360-dashboard-btn-disabled" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <?php esc_html_e('Not Open Yet', 'videohub360-theme'); ?>
                                    </button>
                                <?php elseif ($session_state['status'] === 'waiting_for_host') : ?>
                                    <!-- Within early-join window but host hasn't started -->
                                    <button class="vh360-dashboard-btn vh360-dashboard-btn-disabled" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        <?php esc_html_e('Waiting for Professional', 'videohub360-theme'); ?>
                                    </button>
                                <?php elseif ($session_state['status'] === 'ended') : ?>
                                    <!-- Session has ended -->
                                    <button class="vh360-dashboard-btn vh360-dashboard-btn-disabled" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="5" y="5" width="14" height="14" rx="2" ry="2"></rect>
                                        </svg>
                                        <?php esc_html_e('Session Ended', 'videohub360-theme'); ?>
                                    </button>
                                <?php elseif ($is_live) : ?>
                                    <!-- Session is live - client can join -->
                                    <a href="<?php echo esc_url($live_room_url); ?>" 
                                       class="vh360-dashboard-btn vh360-dashboard-btn-primary vh360-pulse" 
                                       target="_blank">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polygon points="10 8 16 12 10 16 10 8" fill="white"></polygon>
                                        </svg>
                                        <?php esc_html_e('Join Session', 'videohub360-theme'); ?>
                                    </a>
                                <?php else : ?>
                                    <!-- Default: not live yet -->
                                    <button class="vh360-dashboard-btn vh360-dashboard-btn-disabled" disabled>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
                                        </svg>
                                        <?php esc_html_e('Scheduled', 'videohub360-theme'); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
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
                    <?php 
                    if ($is_professional) {
                        esc_html_e('When clients book appointments with you, they will appear here.', 'videohub360-theme');
                    } else {
                        esc_html_e('When you book appointments with professionals, they will appear here.', 'videohub360-theme');
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_professional) : ?>
<script>
jQuery(document).ready(function($) {
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
});
</script>
<?php endif; ?>

<style>
.vh360-appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-appointment-live-icon {
    color: var(--success-color, #16a34a);
}

.vh360-appointment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    border: 1px solid var(--border-1, #e5e7eb);
    border-radius: 0.5rem;
    background: var(--surface-1, #ffffff);
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
    color: var(--text-1, #1f2937);
}

.vh360-appointment-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.vh360-status-live {
    background: color-mix(in srgb, var(--success-color, #16a34a) 14%, var(--surface-1, #ffffff));
    color: var(--success-color, #16a34a);
}

.vh360-status-offline {
    background: var(--surface-3, #f3f4f6);
    color: var(--text-2, #6b7280);
}

.vh360-status-ended,
.vh360-status-past {
    background: color-mix(in srgb, var(--error-color, #dc2626) 12%, var(--surface-1, #ffffff));
    color: var(--error-color, #dc2626);
}

.vh360-appointment-meta {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    color: var(--text-2, #6b7280);
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
    background: color-mix(in srgb, var(--success-color, #16a34a) 14%, var(--surface-1, #ffffff));
    border-radius: 0.375rem;
    color: var(--success-color, #16a34a);
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
    background: var(--primary-color, #2563eb);
    color: white;
}

.vh360-dashboard-btn-primary:hover {
    background: var(--secondary-color, #1d4ed8);
    color: white;
}

.vh360-dashboard-btn-secondary {
    background: var(--surface-3, #f3f4f6);
    color: var(--text-1, #374151);
    border: 1px solid var(--border-1, #d1d5db);
}

.vh360-dashboard-btn-secondary:hover {
    background: var(--border-1, #e5e7eb);
    color: var(--text-1, #374151);
}

.vh360-dashboard-btn-danger {
    background: var(--error-color, #dc2626);
    color: white;
}

.vh360-dashboard-btn-danger:hover {
    background: color-mix(in srgb, var(--error-color, #dc2626) 85%, #000000);
    color: white;
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

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .vh360-appointment-live-icon {
    color: var(--success-color, #16a34a);
}

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
