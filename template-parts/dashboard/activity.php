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
$filter = isset($_GET['activity_filter']) ? sanitize_text_field($_GET['activity_filter']) : 'all';

// Get activities (implement based on available functions)
$activities = array();
if (function_exists('vh360_get_user_activities')) {
    $activities = vh360_get_user_activities($current_user_id, 20, $filter);
} else {
    // Fallback: Get recent posts as activities
    $args = array(
        'author' => $current_user_id,
        'post_type' => array('videohub360', 'post'),
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $activities[] = array(
                'id' => get_the_ID(),
                'type' => 'video_upload',
                'user_id' => $current_user_id,
                'timestamp' => get_the_time('U'),
                'content' => array(
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                ),
            );
        }
        wp_reset_postdata();
    }
}
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
        <a href="<?php echo esc_url(add_query_arg('activity_filter', 'comments')); ?>" 
           class="vh360-activity-filter-btn <?php echo $filter === 'comments' ? 'active' : ''; ?>">
            <?php esc_html_e('Comments', 'videohub360-theme'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('activity_filter', 'likes')); ?>" 
           class="vh360-activity-filter-btn <?php echo $filter === 'likes' ? 'active' : ''; ?>">
            <?php esc_html_e('Likes', 'videohub360-theme'); ?>
        </a>
    </div>
    
    <!-- Activity Feed -->
    <?php if (!empty($activities)) : ?>
        <div class="vh360-activity-feed">
            <?php foreach ($activities as $activity) : ?>
                <div class="vh360-activity-feed-item" data-activity-id="<?php echo esc_attr($activity['id']); ?>">
                    <div class="vh360-activity-feed-icon">
                        <?php echo wp_kses_post(vh360_get_activity_icon($activity['type'])); ?>
                    </div>
                    
                    <div class="vh360-activity-feed-content">
                        <div class="vh360-activity-feed-header">
                            <strong><?php esc_html_e('You', 'videohub360-theme'); ?></strong>
                            <span class="vh360-activity-feed-time">
                                <?php echo esc_html(vh360_format_activity_time($activity['timestamp'])); ?>
                            </span>
                        </div>
                        
                        <div class="vh360-activity-feed-body">
                            <?php
                            $content = $activity['content'];
                            switch ($activity['type']) {
                                case 'video_upload':
                                    echo '<p>';
                                    echo esc_html__('uploaded a new video:', 'videohub360-theme') . ' ';
                                    if (!empty($content['link'])) {
                                        echo '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a>';
                                    } else {
                                        echo esc_html($content['title']);
                                    }
                                    echo '</p>';
                                    break;
                                    
                                case 'comment':
                                    echo '<p>';
                                    echo esc_html__('commented on', 'videohub360-theme') . ' ';
                                    if (!empty($content['link'])) {
                                        echo '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a>';
                                    }
                                    echo '</p>';
                                    if (!empty($content['text'])) {
                                        echo '<blockquote>' . wp_kses_post(wp_trim_words($content['text'], 20)) . '</blockquote>';
                                    }
                                    break;
                                    
                                case 'like':
                                    echo '<p>';
                                    echo esc_html__('liked', 'videohub360-theme') . ' ';
                                    if (!empty($content['link'])) {
                                        echo '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a>';
                                    }
                                    echo '</p>';
                                    break;
                                    
                                case 'profile_update':
                                    echo '<p>' . esc_html__('updated their profile', 'videohub360-theme') . '</p>';
                                    break;
                                    
                                case 'milestone':
                                    echo '<p>';
                                    if (!empty($content['title'])) {
                                        echo esc_html($content['title']);
                                    }
                                    if (!empty($content['meta'])) {
                                        echo ' - ' . esc_html($content['meta']);
                                    }
                                    echo '</p>';
                                    break;
                                    
                                default:
                                    if (is_string($content)) {
                                        echo '<p>' . esc_html($content) . '</p>';
                                    }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Load More Button -->
        <div class="vh360-activity-load-more">
            <button class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-load-more-activity" 
                    data-offset="<?php echo esc_attr(count($activities)); ?>"
                    data-filter="<?php echo esc_attr($filter); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_activity_nonce')); ?>">
                <?php esc_html_e('Load More', 'videohub360-theme'); ?>
            </button>
        </div>
        
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
