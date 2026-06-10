<?php
/**
 * Business Booking Panel
 *
 * Displays compact appointment booking UI for Business Mode professional profiles.
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

$current_user_id = get_current_user_id();
$is_owner = $current_user_id && (int) $current_user_id === (int) $author_id;
?>

<div class="vh360-business-booking-panel" id="vh360-business-booking-panel">
    <div class="vh360-business-booking-panel-header">
        <h2><?php esc_html_e('Book an Appointment', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Choose a date and available time.', 'videohub360-theme'); ?></p>
    </div>

    <?php if ($is_owner) : ?>
        <div class="vh360-business-booking-owner">
            <p><?php esc_html_e('You are viewing your own profile.', 'videohub360-theme'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('tab', 'availability', home_url('/dashboard/'))); ?>" class="vh360-booking-manage-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php esc_html_e('Manage Your Availability', 'videohub360-theme'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="vh360-business-booking-picker">
            <div class="vh360-booking-date-picker-wrapper">
                <label for="vh360-booking-date-picker">
                    <?php esc_html_e('Select a date', 'videohub360-theme'); ?>
                </label>
                <input type="date"
                       id="vh360-booking-date-picker"
                       class="vh360-booking-date-input"
                       min="<?php echo esc_attr(current_time('Y-m-d')); ?>">
            </div>

            <div id="vh360-booking-messages" class="vh360-booking-messages"></div>

            <div id="vh360-booking-loading" class="vh360-booking-loading" style="display: none;">
                <svg class="vh360-spinner" width="32" height="32" viewBox="0 0 50 50" aria-hidden="true" focusable="false">
                    <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                </svg>
                <p><?php esc_html_e('Loading available times...', 'videohub360-theme'); ?></p>
            </div>

            <div id="vh360-booking-slots-container" class="vh360-booking-slots-container">
                <!-- Slots load here dynamically -->
            </div>
        </div>
    <?php endif; ?>
</div>
