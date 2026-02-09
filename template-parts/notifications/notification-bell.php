<?php
/**
 * Notification Bell Template
 *
 * Displays the notification bell icon with unread count badge.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    return;
}

$user_id = get_current_user_id();
$unread_count = vh360_get_unread_notification_count($user_id);
?>

<div class="vh360-notification-bell" id="vh360-notification-bell">
    <button type="button" class="vh360-notification-bell-btn" aria-label="<?php esc_attr_e('Notifications', 'videohub360-theme'); ?>" aria-expanded="false">
        <svg class="vh360-notification-bell-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php if ($unread_count > 0) : ?>
            <span class="vh360-notification-badge" data-count="<?php echo esc_attr($unread_count); ?>">
                <?php echo esc_html($unread_count > 99 ? '99+' : $unread_count); ?>
            </span>
        <?php endif; ?>
    </button>
    
    <?php get_template_part('template-parts/notifications/notification-dropdown'); ?>
</div>
