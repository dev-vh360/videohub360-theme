<?php
/**
 * Dashboard Notifications Tab
 *
 * Full notification list with filtering, pagination, and bulk actions.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$stats = vh360_get_notification_stats($current_user_id);
?>

<div class="vh360-dashboard-notifications">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Notifications', 'videohub360-theme'); ?></h1>
        <div class="vh360-dashboard-header-actions">
            <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-mark-all-read-btn" data-action="mark-all-read">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <?php esc_html_e('Mark All Read', 'videohub360-theme'); ?>
            </button>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="vh360-notification-stats">
        <div class="vh360-notification-stat">
            <span class="vh360-notification-stat-value"><?php echo esc_html($stats['total']); ?></span>
            <span class="vh360-notification-stat-label"><?php esc_html_e('Total', 'videohub360-theme'); ?></span>
        </div>
        <div class="vh360-notification-stat">
            <span class="vh360-notification-stat-value"><?php echo esc_html($stats['unread']); ?></span>
            <span class="vh360-notification-stat-label"><?php esc_html_e('Unread', 'videohub360-theme'); ?></span>
        </div>
        <div class="vh360-notification-stat">
            <span class="vh360-notification-stat-value"><?php echo esc_html($stats['read']); ?></span>
            <span class="vh360-notification-stat-label"><?php esc_html_e('Read', 'videohub360-theme'); ?></span>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="vh360-notification-filters">
        <div class="vh360-notification-filter-group">
            <label for="notification-type-filter" class="vh360-filter-label"><?php esc_html_e('Type:', 'videohub360-theme'); ?></label>
            <select id="notification-type-filter" class="vh360-filter-select">
                <option value=""><?php esc_html_e('All Types', 'videohub360-theme'); ?></option>
                <option value="follow"><?php esc_html_e('Follows', 'videohub360-theme'); ?></option>
                <option value="like"><?php esc_html_e('Likes', 'videohub360-theme'); ?></option>
                <option value="comment"><?php esc_html_e('Comments', 'videohub360-theme'); ?></option>
                <option value="mention"><?php esc_html_e('Mentions', 'videohub360-theme'); ?></option>
                <option value="reply"><?php esc_html_e('Replies', 'videohub360-theme'); ?></option>
                <option value="share"><?php esc_html_e('Shares', 'videohub360-theme'); ?></option>
            </select>
        </div>
        
        <div class="vh360-notification-filter-group">
            <label for="notification-date-filter" class="vh360-filter-label"><?php esc_html_e('Date:', 'videohub360-theme'); ?></label>
            <select id="notification-date-filter" class="vh360-filter-select">
                <option value=""><?php esc_html_e('All Time', 'videohub360-theme'); ?></option>
                <option value="today"><?php esc_html_e('Today', 'videohub360-theme'); ?></option>
                <option value="week"><?php esc_html_e('This Week', 'videohub360-theme'); ?></option>
                <option value="month"><?php esc_html_e('This Month', 'videohub360-theme'); ?></option>
            </select>
        </div>
        
        <div class="vh360-notification-filter-group">
            <label for="notification-status-filter" class="vh360-filter-label"><?php esc_html_e('Status:', 'videohub360-theme'); ?></label>
            <select id="notification-status-filter" class="vh360-filter-select">
                <option value=""><?php esc_html_e('All', 'videohub360-theme'); ?></option>
                <option value="unread"><?php esc_html_e('Unread', 'videohub360-theme'); ?></option>
                <option value="read"><?php esc_html_e('Read', 'videohub360-theme'); ?></option>
            </select>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="vh360-notification-bulk-actions">
        <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-dashboard-btn-sm" data-action="delete-read">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            <?php esc_html_e('Delete Read', 'videohub360-theme'); ?>
        </button>
        <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-dashboard-btn-sm" data-action="clear-all">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <?php esc_html_e('Clear All', 'videohub360-theme'); ?>
        </button>
    </div>
    
    <!-- Notification List -->
    <div class="vh360-notification-list-container">
        <div id="vh360-notification-list-dashboard" class="vh360-notification-list-dashboard">
            <!-- Notifications will be loaded here via AJAX -->
        </div>
        
        <!-- Loading State -->
        <div class="vh360-notification-loading" style="display: none;">
            <div class="vh360-spinner"></div>
            <p><?php esc_html_e('Loading notifications...', 'videohub360-theme'); ?></p>
        </div>
        
        <!-- Empty State -->
        <div class="vh360-notification-empty" style="display: none;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <h3><?php esc_html_e('No notifications', 'videohub360-theme'); ?></h3>
            <p><?php esc_html_e('You\'re all caught up! Check back later for new notifications.', 'videohub360-theme'); ?></p>
        </div>
        
        <!-- Error State -->
        <div class="vh360-notification-error" style="display: none;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h3><?php esc_html_e('Error loading notifications', 'videohub360-theme'); ?></h3>
            <p><?php esc_html_e('Something went wrong. Please try again.', 'videohub360-theme'); ?></p>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="vh360-notification-pagination">
        <button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary" id="vh360-load-more-notifications" style="display: none;">
            <?php esc_html_e('Load More', 'videohub360-theme'); ?>
        </button>
    </div>
    
</div><!-- .vh360-dashboard-notifications -->
