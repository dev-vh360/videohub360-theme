<?php
/**
 * Message Icon Template
 *
 * Displays the message icon with unread count badge in header.
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

// Check if DM is enabled
if (!function_exists('vh360_is_dm_enabled') || !vh360_is_dm_enabled()) {
    return;
}

$user_id = get_current_user_id();
$unread_count = function_exists('vh360_get_unread_messages_count') ? vh360_get_unread_messages_count($user_id) : 0;

// Find dashboard page URL
$dashboard_page = get_pages(array(
    'meta_key' => '_wp_page_template',
    'meta_value' => 'template-dashboard.php',
    'number' => 1,
));

$messages_url = '#';
if (!empty($dashboard_page)) {
    $messages_url = add_query_arg('tab', 'messages', get_permalink($dashboard_page[0]->ID));
}
?>

<div class="vh360-message-icon">
    <a href="<?php echo esc_url($messages_url); ?>" class="vh360-message-icon-link" aria-label="<?php esc_attr_e('Messages', 'videohub360-theme'); ?>">
        <svg class="vh360-message-icon-svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php if ($unread_count > 0) : ?>
            <span class="vh360-message-badge" data-count="<?php echo esc_attr($unread_count); ?>">
                <?php echo esc_html($unread_count > 99 ? '99+' : $unread_count); ?>
            </span>
        <?php endif; ?>
    </a>
</div>

