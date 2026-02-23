<?php
/**
 * Business Profile Header
 *
 * Displays header section for business profiles (professional/organization)
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get business meta
$business_name = get_user_meta($author_id, '_vh360_business_name', true);
$business_type = get_user_meta($author_id, '_vh360_business_type', true);
$location = get_user_meta($author_id, '_vh360_location', true);

$display_name = $business_name ? $business_name : $author->display_name;

// Check if we should show message button
$current_user_id = get_current_user_id();
$show_message_button = false;
$is_owner = ($current_user_id === $author_id);

if (is_user_logged_in() && $current_user_id !== $author_id) {
    if (function_exists('vh360_is_dm_enabled') && function_exists('vh360_can_send_message')) {
        if (vh360_is_dm_enabled() && vh360_can_send_message($current_user_id, $author_id)) {
            $show_message_button = true;
        }
    }
}

// Get upcoming availability slots for booking (not regular events or blocks)
$upcoming_events = array();
$events_query = new WP_Query(array(
    'post_type' => 'vh360_event',
    'post_status' => 'publish',
    'author' => $author_id,
    'posts_per_page' => 10,
    'meta_key' => '_vh360_event_start_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_vh360_event_start_date',
            'value' => current_time('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        ),
        array(
            'key' => '_vh360_event_kind',
            'value' => 'availability',
            'compare' => '='
        )
    )
));

if ($events_query->have_posts()) {
    while ($events_query->have_posts()) {
        $events_query->the_post();
        $event_id = get_the_ID();
        $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
        $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
        $max_attendees = get_post_meta($event_id, '_vh360_event_max_attendees', true);
        $rsvp_count = get_post_meta($event_id, '_vh360_event_rsvp_count', true);
        
        // Check if slot is still available
        $is_full = false;
        if (!empty($max_attendees) && is_numeric($max_attendees)) {
            $max_attendees = absint($max_attendees);
            $rsvp_count = absint($rsvp_count);
            if ($max_attendees > 0 && $rsvp_count >= $max_attendees) {
                $is_full = true;
            }
        }
        
        $upcoming_events[] = array(
            'id' => $event_id,
            'title' => get_the_title(),
            'date' => $start_date,
            'time' => $start_time,
            'url' => get_permalink($event_id),
            'is_full' => $is_full
        );
    }
    wp_reset_postdata();
}
?>

<div class="vh360-business-header">
    <div class="container">
        <div class="vh360-business-header-content">
            
            <div class="vh360-business-avatar">
                <?php echo get_avatar($author_id, 150); ?>
            </div>
            
            <div class="vh360-business-info">
                <h1 class="vh360-business-name"><?php echo esc_html($display_name); ?></h1>
                
                <?php if ($business_type) : ?>
                    <p class="vh360-business-type"><?php echo esc_html($business_type); ?></p>
                <?php endif; ?>
                
                <?php if ($location) : ?>
                    <p class="vh360-business-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($show_message_button) : ?>
                    <div class="vh360-business-actions">
                        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'messages', 'user' => $author_id), home_url('/dashboard/'))); ?>" class="vh360-business-message-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <?php esc_html_e('Send Message', 'videohub360-theme'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
        
        <!-- Booking Section - Always show for Business profiles -->
        <div class="vh360-business-booking">
            <h2 class="vh360-business-booking-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php esc_html_e('Available Appointments', 'videohub360-theme'); ?>
            </h2>
            
            <?php if (!empty($upcoming_events)) : ?>
                <div class="vh360-business-booking-slots">
                    <?php foreach ($upcoming_events as $event) : ?>
                        <div class="vh360-booking-slot <?php echo $event['is_full'] ? 'full' : ''; ?>">
                            <div class="vh360-booking-slot-info">
                                <div class="vh360-booking-slot-date">
                                    <?php 
                                    if ($event['date']) {
                                        $timestamp = strtotime($event['date']);
                                        if ($timestamp !== false) {
                                            echo esc_html(date_i18n(get_option('date_format'), $timestamp));
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="vh360-booking-slot-time">
                                    <?php 
                                    if ($event['time']) {
                                        echo esc_html($event['time']);
                                    }
                                    ?>
                                </div>
                                <?php if (!empty($event['title'])) : ?>
                                    <div class="vh360-booking-slot-title"><?php echo esc_html($event['title']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="vh360-booking-slot-action">
                                <?php if ($event['is_full']) : ?>
                                    <span class="vh360-booking-slot-full"><?php esc_html_e('Booked', 'videohub360-theme'); ?></span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($event['url']); ?>" class="vh360-booking-slot-btn">
                                        <?php esc_html_e('View & Confirm', 'videohub360-theme'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="vh360-booking-info">
                        <?php esc_html_e('Click "Book Appointment" to see details and confirm your booking via RSVP.', 'videohub360-theme'); ?>
                    </p>
                </div>
            <?php else : ?>
                <div class="vh360-business-booking-empty">
                    <p><?php esc_html_e('No appointment slots are currently available.', 'videohub360-theme'); ?></p>
                    <?php if ($is_owner) : ?>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'events', home_url('/dashboard/'))); ?>" class="vh360-booking-manage-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <?php esc_html_e('Manage Availability', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
.vh360-business-actions {
    margin-top: 1rem;
}

.vh360-business-message-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color, #2563eb);
    color: #ffffff;
    text-decoration: none;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.vh360-business-message-btn:hover {
    background: var(--primary-hover, #1d4ed8);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.vh360-business-message-btn svg {
    flex-shrink: 0;
}

/* Booking Section */
.vh360-business-booking {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--bg-light, #f8f9fa);
    border-radius: 0.5rem;
}

.vh360-business-booking-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--text-color, #1f2937);
}

.vh360-business-booking-slots {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vh360-booking-slot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #ffffff;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.vh360-booking-slot:hover:not(.full) {
    border-color: var(--primary-color, #2563eb);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.vh360-booking-slot.full {
    opacity: 0.6;
}

.vh360-booking-slot-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.vh360-booking-slot-date {
    font-weight: 600;
    color: var(--text-color, #1f2937);
    min-width: 120px;
}

.vh360-booking-slot-time {
    font-weight: 500;
    color: var(--text-light, #6b7280);
    min-width: 80px;
}

.vh360-booking-slot-title {
    color: var(--text-light, #6b7280);
    font-size: 0.875rem;
}

.vh360-booking-slot-action {
    flex-shrink: 0;
}

.vh360-booking-slot-btn {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: var(--primary-color, #2563eb);
    color: #ffffff;
    text-decoration: none;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.vh360-booking-slot-btn:hover {
    background: var(--primary-hover, #1d4ed8);
}

.vh360-booking-slot-full {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: var(--bg-color, #e5e7eb);
    color: var(--text-light, #6b7280);
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.vh360-booking-info {
    margin-top: 1rem;
    padding: 0.75rem;
    background: rgba(37, 99, 235, 0.05);
    border-left: 3px solid var(--primary-color, #2563eb);
    font-size: 0.875rem;
    color: var(--text-color, #1f2937);
}

.vh360-business-booking-empty {
    text-align: center;
    padding: 2rem 1rem;
}

.vh360-business-booking-empty p {
    margin: 0 0 1rem;
    color: var(--text-light, #6b7280);
}

.vh360-booking-manage-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color, #2563eb);
    color: #ffffff;
    text-decoration: none;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.vh360-booking-manage-link:hover {
    background: var(--primary-hover, #1d4ed8);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.vh360-booking-manage-link svg {
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .vh360-booking-slot {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .vh360-booking-slot-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        width: 100%;
    }
    
    .vh360-booking-slot-action {
        width: 100%;
    }
    
    .vh360-booking-slot-btn,
    .vh360-booking-slot-full {
        display: block;
        text-align: center;
        width: 100%;
    }
}
</style>
