<?php
/**
 * Business Profile Contact Tab
 *
 * Displays contact information for business profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();

// Get contact meta
$contact_phone = get_user_meta($author_id, '_vh360_contact_phone', true);
$contact_email = get_user_meta($author_id, '_vh360_contact_email', true);
$booking_url = get_user_meta($author_id, '_vh360_booking_url', true);
$location = get_user_meta($author_id, '_vh360_location', true);
?>

<div class="vh360-business-contact">
    
    <h2><?php esc_html_e('Contact Information', 'videohub360-theme'); ?></h2>
    
    <?php if ($location) : ?>
        <div class="vh360-contact-item">
            <h3><?php esc_html_e('Location', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($location); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($contact_phone) : ?>
        <div class="vh360-contact-item">
            <h3><?php esc_html_e('Phone', 'videohub360-theme'); ?></h3>
            <p><a href="tel:<?php echo esc_attr($contact_phone); ?>"><?php echo esc_html($contact_phone); ?></a></p>
        </div>
    <?php endif; ?>
    
    <?php if ($contact_email) : ?>
        <div class="vh360-contact-item">
            <h3><?php esc_html_e('Email', 'videohub360-theme'); ?></h3>
            <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
        </div>
    <?php endif; ?>
    
    <?php if ($booking_url) : ?>
        <div class="vh360-contact-item">
            <h3><?php esc_html_e('Book an Appointment', 'videohub360-theme'); ?></h3>
            <p><a href="<?php echo esc_url($booking_url); ?>" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Schedule Online', 'videohub360-theme'); ?>
            </a></p>
        </div>
    <?php endif; ?>
    
    <?php if (!$location && !$contact_phone && !$contact_email && !$booking_url) : ?>
        <p><?php esc_html_e('No contact information available.', 'videohub360-theme'); ?></p>
    <?php endif; ?>
    
</div>
