<?php
/**
 * Business Profile Services Tab
 *
 * Displays services and offerings for business profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();

// Get business meta
$specialties = get_user_meta($author_id, '_vh360_specialties', true);
$credentials = get_user_meta($author_id, '_vh360_credentials', true);
$telehealth = get_user_meta($author_id, '_vh360_telehealth', true);
$accepting_clients = get_user_meta($author_id, '_vh360_accepting_new_clients', true);
$pricing_info = get_user_meta($author_id, '_vh360_pricing_info', true);
$insurance_info = get_user_meta($author_id, '_vh360_insurance_info', true);
?>

<div class="vh360-business-services">
    
    <h2><?php esc_html_e('Services & Specialties', 'videohub360-theme'); ?></h2>
    
    <?php if ($credentials) : ?>
        <div class="vh360-business-credentials">
            <h3><?php esc_html_e('Credentials', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($credentials); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($specialties) : ?>
        <div class="vh360-business-specialties">
            <h3><?php esc_html_e('Specialties', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($specialties); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($telehealth || $accepting_clients) : ?>
        <div class="vh360-business-availability">
            <h3><?php esc_html_e('Availability', 'videohub360-theme'); ?></h3>
            <ul>
                <?php if ($telehealth) : ?>
                    <li><?php esc_html_e('Telehealth services available', 'videohub360-theme'); ?></li>
                <?php endif; ?>
                <?php if ($accepting_clients) : ?>
                    <li><?php esc_html_e('Currently accepting new clients', 'videohub360-theme'); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($pricing_info) : ?>
        <div class="vh360-business-pricing">
            <h3><?php esc_html_e('Pricing Information', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($pricing_info); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($insurance_info) : ?>
        <div class="vh360-business-insurance">
            <h3><?php esc_html_e('Insurance', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($insurance_info); ?></p>
        </div>
    <?php endif; ?>
    
</div>
