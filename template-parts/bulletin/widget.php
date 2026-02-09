<?php
/**
 * Dashboard Bulletin Widget
 *
 * Shows latest unread bulletins in dashboard overview.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

if (!$user_id) {
    return;
}

// Get read bulletins to exclude
$read_bulletins = get_user_meta($user_id, '_vh360_read_bulletins', true);
if (!is_array($read_bulletins)) {
    $read_bulletins = array();
}

// Get dismissed bulletins to exclude
$dismissed_bulletins = get_user_meta($user_id, '_vh360_dismissed_bulletins', true);
if (!is_array($dismissed_bulletins)) {
    $dismissed_bulletins = array();
}

// Combine for exclusion
$exclude_ids = array_merge($read_bulletins, $dismissed_bulletins);

// Query for unread bulletins directly (simplified query)
$args = array(
    'post_type' => 'vh360_bulletin',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'orderby' => 'date',
    'order' => 'DESC'
);

if (!empty($exclude_ids)) {
    $args['post__not_in'] = $exclude_ids;
}

$all_bulletins = get_posts($args);

// Filter by expiry and visibility
$unread_bulletins = array();
foreach ($all_bulletins as $bulletin) {
    // Skip if expired
    $expiry_date = get_post_meta($bulletin->ID, '_vh360_bulletin_expiry_date', true);
    if ($expiry_date && $expiry_date < current_time('timestamp')) {
        continue;
    }
    
    // Check if user can see this bulletin
    if (vh360_can_user_see_bulletin($bulletin->ID, $user_id)) {
        $unread_bulletins[] = $bulletin;
    }
}

if (empty($unread_bulletins)) {
    return;
}

$total_unread = vh360_get_unread_bulletin_count($user_id);

?>

<div class="vh360-dashboard-card vh360-dashboard-bulletins">
    <div class="vh360-dashboard-card-header">
        <div class="vh360-dashboard-card-title-wrapper">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <h2 class="vh360-dashboard-card-title">
                <?php esc_html_e('Bulletins', 'videohub360-theme'); ?>
                <?php if ($total_unread > 0) : ?>
                    <span class="vh360-bulletin-badge"><?php echo esc_html($total_unread); ?></span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="vh360-dashboard-card-actions">
            <?php if ($total_unread > 3) : ?>
                <a href="<?php echo esc_url(get_post_type_archive_link('vh360_bulletin')); ?>" 
                   class="vh360-dashboard-link">
                    <?php esc_html_e('View All', 'videohub360-theme'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="vh360-dashboard-card-body">
        <div class="vh360-bulletins-widget-list">
            <?php foreach ($unread_bulletins as $bulletin) : ?>
                <?php 
                get_template_part('template-parts/bulletin/card', null, array(
                    'bulletin_id' => $bulletin->ID,
                    'show_actions' => true,
                    'compact' => true
                )); 
                ?>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_unread > 0) : ?>
            <div class="vh360-bulletins-widget-footer">
                <a href="<?php echo esc_url(get_post_type_archive_link('vh360_bulletin')); ?>" 
                   class="vh360-dashboard-btn vh360-btn-outline">
                    <?php esc_html_e('View All Bulletins', 'videohub360-theme'); ?>
                </a>
                <?php if ($total_unread > 1) : ?>
                    <button class="vh360-bulletin-mark-all-read vh360-dashboard-btn vh360-btn-text">
                        <?php esc_html_e('Mark All as Read', 'videohub360-theme'); ?>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Bulletin Widget Styles */
.vh360-dashboard-bulletins {
    margin-top: 2rem;
}

.vh360-dashboard-card-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vh360-dashboard-card-title-wrapper svg {
    color: #f59e0b;
}

.vh360-bulletin-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 10px;
    margin-left: 0.5rem;
}

.vh360-bulletins-widget-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-bulletins-widget-footer {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
}

.vh360-btn-outline {
    flex: 1;
    text-align: center;
    border: 1px solid var(--primary-color, #3b82f6);
    background: transparent;
    color: var(--primary-color, #3b82f6);
}

.vh360-btn-outline:hover {
    background: var(--primary-color, #3b82f6);
    color: white;
}

.vh360-btn-text {
    background: transparent;
    color: var(--text-light, #6b7280);
    border: none;
    padding: 0.5rem 1rem;
}

.vh360-btn-text:hover {
    color: var(--text-color, #1f2937);
}

/* Compact bulletin cards in widget */
.vh360-bulletin-compact {
    padding: 1rem;
}

.vh360-bulletin-compact .vh360-bulletin-title {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.vh360-bulletin-compact .vh360-bulletin-footer {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .vh360-bulletins-widget-footer {
        flex-direction: column;
    }
}
</style>
