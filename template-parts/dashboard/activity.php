<?php
/**
 * Dashboard Activity Tab
 *
 * Personalized activity feed with filters.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();

// Get filter
$filter = isset($_GET['activity_filter']) ? sanitize_key($_GET['activity_filter']) : 'all';
$normalized_filter = vh360_normalize_dashboard_activity_filter($filter);
if ('all' === $normalized_filter && 'all' !== $filter) {
    $filter = 'all';
}
$activity_result = vh360_query_activities(array(
    'user_id' => $current_user_id,
    'type' => $normalized_filter,
    'limit' => 20,
    'offset' => 0,
));
$activities = $activity_result['items'];
?>

<div class="vh360-dashboard-activity">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('My Activity', 'videohub360-theme'); ?></h1>
    </div>
    
    <!-- Activity Filters -->
    <div class="vh360-activity-filters">
        <a href="<?php echo esc_url(add_query_arg('activity_filter', 'all')); ?>" 
           class="vh360-activity-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <?php esc_html_e('All Activity', 'videohub360-theme'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('activity_filter', 'videos')); ?>" 
           class="vh360-activity-filter-btn <?php echo $filter === 'videos' ? 'active' : ''; ?>">
            <?php esc_html_e('Videos', 'videohub360-theme'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('activity_filter', 'posts')); ?>"
           class="vh360-activity-filter-btn <?php echo $filter === 'posts' ? 'active' : ''; ?>">
            <?php esc_html_e('Posts', 'videohub360-theme'); ?>
        </a>
    </div>
    
    <!-- Activity Feed -->
    <?php if (!empty($activities)) : ?>
        <div class="vh360-activity-feed">
            <?php foreach ($activities as $activity) : ?>
                <?php echo vh360_get_dashboard_activity_item_html($activity); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer. ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Load More Button -->
        <?php if ($activity_result['has_more']) : ?>
        <div class="vh360-activity-load-more">
            <button class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-load-more-activity" 
                    data-offset="<?php echo esc_attr($activity_result['next_offset']); ?>"
                    data-filter="<?php echo esc_attr($filter); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_activity_nonce')); ?>">
                <?php esc_html_e('Load More', 'videohub360-theme'); ?>
            </button>
        </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="vh360-dashboard-empty">
            <div class="vh360-dashboard-empty-icon">📊</div>
            <p class="vh360-dashboard-empty-title"><?php esc_html_e('No activity yet', 'videohub360-theme'); ?></p>
            <p class="vh360-dashboard-empty-text">
                <?php esc_html_e('Your activity will appear here as you upload videos and interact with content.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php endif; ?>
    
</div><!-- .vh360-dashboard-activity -->

<style>
/* Activity Filters */
.vh360-activity-filters {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.vh360-activity-filter-btn {
    padding: 0.625rem 1.25rem;
    background: var(--bg-light);
    color: var(--text-color);
    text-decoration: none;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    transition: var(--transition);
}

.vh360-activity-filter-btn:hover {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
}

.vh360-activity-filter-btn.active {
    background: var(--primary-color);
    color: #ffffff;
}

/* Activity Feed */
.vh360-activity-feed {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.vh360-activity-feed-item {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
}

.vh360-activity-feed-item:last-child {
    border-bottom: none;
}

.vh360-activity-feed-item:hover {
    background: var(--bg-light);
}

.vh360-activity-feed-icon {
    flex: 0 0 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.vh360-activity-feed-icon .vh360-activity-icon__svg {
    width: 20px;
    height: 20px;
    display: block;
    flex: 0 0 auto;
}

.vh360-activity-feed-content {
    flex: 1;
    min-width: 0;
}

.vh360-activity-feed-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.vh360-activity-feed-header strong {
    font-weight: 600;
    color: var(--text-color);
}

.vh360-activity-feed-time {
    font-size: 0.75rem;
    color: var(--text-light);
    white-space: nowrap;
}

.vh360-activity-feed-body {
    color: var(--text-color);
    font-size: 0.875rem;
}

.vh360-activity-feed-body p {
    margin: 0;
}

.vh360-activity-feed-body a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.vh360-activity-feed-body a:hover {
    text-decoration: underline;
}

.vh360-activity-feed-body blockquote {
    margin: 0.5rem 0 0;
    padding: 0.75rem 1rem;
    background: var(--bg-light);
    border-left: 3px solid var(--primary-color);
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    font-size: 0.875rem;
    color: var(--text-light);
}

/* Load More */
.vh360-activity-load-more {
    margin-top: 2rem;
    text-align: center;
}

@media (max-width: 768px) {
    .vh360-activity-feed-item {
        padding: 1rem;
    }
    
    .vh360-activity-feed-icon {
        flex: 0 0 40px;
        height: 40px;
    }
}
</style>
