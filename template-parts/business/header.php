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

if (is_user_logged_in() && $current_user_id !== $author_id) {
    if (function_exists('vh360_is_dm_enabled') && function_exists('vh360_can_send_message')) {
        if (vh360_is_dm_enabled() && vh360_can_send_message($current_user_id, $author_id)) {
            $show_message_button = true;
        }
    }
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
</style>
