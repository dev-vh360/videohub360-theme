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
?>

<div class="vh360-business-header">
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
    
    <!-- Booking Section - Dynamic Appointment Picker -->
    <div class="vh360-business-booking">
        <button type="button" 
                class="vh360-business-booking-toggle" 
                id="vh360-booking-toggle"
                aria-expanded="false"
                aria-controls="vh360-booking-content">
            <span class="vh360-booking-toggle-text">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?php esc_html_e('Book an Appointment', 'videohub360-theme'); ?>
            </span>
            <svg class="vh360-booking-toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>
        
        <div class="vh360-business-booking-content" id="vh360-booking-content" style="display: none;">
        <?php if ($is_owner) : ?>
            <!-- Owner View: Manage Availability Link -->
            <div class="vh360-business-booking-owner">
                <p><?php esc_html_e('You are viewing your own profile.', 'videohub360-theme'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('tab', 'availability', home_url('/dashboard/'))); ?>" class="vh360-booking-manage-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php esc_html_e('Manage Your Availability', 'videohub360-theme'); ?>
                </a>
            </div>
        <?php else : ?>
            <!-- Client View: Dynamic Booking Interface -->
            <div class="vh360-business-booking-picker">
                <div class="vh360-booking-date-picker-wrapper">
                    <label for="vh360-booking-date-picker">
                        <?php esc_html_e('Select a date:', 'videohub360-theme'); ?>
                    </label>
                    <input type="date" 
                           id="vh360-booking-date-picker" 
                           class="vh360-booking-date-input"
                           min="<?php echo esc_attr(current_time('Y-m-d')); ?>">
                </div>
                
                <div id="vh360-booking-messages" class="vh360-booking-messages"></div>
                
                <div id="vh360-booking-loading" class="vh360-booking-loading" style="display: none;">
                    <svg class="vh360-spinner" width="40" height="40" viewBox="0 0 50 50">
                        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                    </svg>
                    <p><?php esc_html_e('Loading available times...', 'videohub360-theme'); ?></p>
                </div>
                
                <div id="vh360-booking-slots-container" class="vh360-booking-slots-container">
                    <!-- Slots will be loaded here dynamically -->
                </div>
            </div>
        <?php endif; ?>
        </div><!-- .vh360-business-booking-content -->
    </div>
    
</div>
