<?php
/**
 * Single Notification Item Template
 *
 * Displays a single notification in the list.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$notification = get_query_var('notification');

if (!$notification) {
    return;
}

$read_class = $notification['is_read'] ? 'vh360-notification-item--read' : 'vh360-notification-item--unread';
?>

<div class="vh360-notification-item <?php echo esc_attr($read_class); ?>" data-notification-id="<?php echo esc_attr($notification['id']); ?>">
    <a href="<?php echo esc_url($notification['link']); ?>" class="vh360-notification-item-link">
        <div class="vh360-notification-item-avatar">
            <?php if (!empty($notification['actor_avatar'])) : ?>
                <img src="<?php echo esc_url($notification['actor_avatar']); ?>" alt="<?php echo esc_attr($notification['actor_name']); ?>" width="40" height="40">
            <?php else : ?>
                <?php echo get_avatar($notification['actor_id'], 40); ?>
            <?php endif; ?>
        </div>
        
        <div class="vh360-notification-item-content">
            <div class="vh360-notification-item-message">
                <?php echo wp_kses_post($notification['message']); ?>
            </div>
            <div class="vh360-notification-item-time">
                <?php 
                printf(
                    /* translators: %s: time ago */
                    esc_html__('%s ago', 'videohub360-theme'),
                    esc_html($notification['time_ago'])
                );
                ?>
            </div>
        </div>
        
        <?php if (!$notification['is_read']) : ?>
            <div class="vh360-notification-item-indicator" aria-label="<?php esc_attr_e('Unread', 'videohub360-theme'); ?>"></div>
        <?php endif; ?>
    </a>
</div>
