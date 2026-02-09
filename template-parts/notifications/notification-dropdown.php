<?php
/**
 * Notification Dropdown Template
 *
 * Displays the notification dropdown with recent notifications.
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
$notifications = vh360_get_notifications($user_id, array('limit' => 5));
?>

<div class="vh360-notification-dropdown" id="vh360-notification-dropdown" style="display: none;">
    <div class="vh360-notification-dropdown-header">
        <h3 class="vh360-notification-dropdown-title"><?php esc_html_e('Notifications', 'videohub360-theme'); ?></h3>
        <?php if (!empty($notifications)) : ?>
            <button type="button" class="vh360-notification-mark-all-read" id="vh360-mark-all-read">
                <?php esc_html_e('Mark all as read', 'videohub360-theme'); ?>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="vh360-notification-dropdown-body" id="vh360-notification-list">
        <?php if (!empty($notifications)) : ?>
            <?php foreach ($notifications as $notification) : ?>
                <?php vh360_render_notification_item($notification); ?>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="vh360-notification-empty">
                <svg class="vh360-notification-empty-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p><?php esc_html_e('No notifications yet', 'videohub360-theme'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="vh360-notification-dropdown-footer">
        <a href="<?php echo esc_url(home_url('/dashboard/?tab=notifications')); ?>" class="vh360-notification-view-all">
            <?php esc_html_e('View all notifications', 'videohub360-theme'); ?>
        </a>
    </div>
</div>
