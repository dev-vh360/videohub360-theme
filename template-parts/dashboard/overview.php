<?php
/**
 * Dashboard Overview Tab
 *
 * Stats cards, quick actions, recent activity, and milestones.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();

$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
$stats = vh360_get_user_stats($current_user_id);

// Get recent videos
$recent_videos_args = array(
    'post_type' => array('videohub360', 'post'),
    'author' => $current_user_id,
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
);
$recent_videos = new WP_Query($recent_videos_args);

// Get recent activities
$activities = array();
if (function_exists('vh360_get_user_activities')) {
    $activities = vh360_get_user_activities($current_user_id, 5);
}
?>

<div class="vh360-dashboard-overview">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Dashboard Overview', 'videohub360-theme'); ?></h1>
    </div>

    <?php if ( ! $vh360_is_licensed ) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning vh360-license-softlock-notice">
            <?php echo esc_html__( 'Your VideoHub360 license is inactive. Activate your license to unlock creation features.', 'videohub360-theme' ); ?>
            <a href="<?php echo esc_url( $vh360_license_url ); ?>" style="margin-left:8px;">
                <?php esc_html_e( 'Activate License', 'videohub360-theme' ); ?>
            </a>
        </div>
    <?php endif; ?>

<!-- Statistics Widgets -->
    <div class="vh360-dashboard-widgets">
        
        <!-- Total Videos -->
        <div class="vh360-dashboard-widget">
            <div class="vh360-dashboard-widget-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--primary-color);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
            </div>
            <div class="vh360-dashboard-widget-label"><?php esc_html_e('Total Videos', 'videohub360-theme'); ?></div>
            <div class="vh360-dashboard-widget-value"><?php echo esc_html(vh360_format_number($stats['videos'])); ?></div>
        </div>
        
        <!-- Total Views -->
        <div class="vh360-dashboard-widget">
            <div class="vh360-dashboard-widget-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </div>
            <div class="vh360-dashboard-widget-label"><?php esc_html_e('Total Views', 'videohub360-theme'); ?></div>
            <div class="vh360-dashboard-widget-value"><?php echo esc_html(vh360_format_number($stats['views'])); ?></div>
        </div>
        
        <!-- Subscribers -->
        <div class="vh360-dashboard-widget">
            <div class="vh360-dashboard-widget-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="vh360-dashboard-widget-label"><?php esc_html_e('Followers', 'videohub360-theme'); ?></div>
            <div class="vh360-dashboard-widget-value"><?php echo esc_html(vh360_format_number($stats['followers'])); ?></div>
        </div>
        
        <!-- Likes -->
        <div class="vh360-dashboard-widget">
            <div class="vh360-dashboard-widget-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            </div>
            <div class="vh360-dashboard-widget-label"><?php esc_html_e('Total Likes', 'videohub360-theme'); ?></div>
            <div class="vh360-dashboard-widget-value"><?php echo esc_html(vh360_format_number($stats['likes'])); ?></div>
        </div>
        
    </div><!-- .vh360-dashboard-widgets -->
    
    <div class="vh360-dashboard-grid">
        
        <!-- Recent Videos -->
        <div class="vh360-dashboard-card">
            <div class="vh360-dashboard-card-header">
                <h2 class="vh360-dashboard-card-title"><?php esc_html_e('Recent Videos', 'videohub360-theme'); ?></h2>
                <a href="#videos" class="vh360-dashboard-tab" data-tab="videos"><?php esc_html_e('View All', 'videohub360-theme'); ?></a>
            </div>
            <div class="vh360-dashboard-card-body">
                <?php if ($recent_videos->have_posts()) : ?>
                    <div class="vh360-dashboard-video-list">
                        <?php while ($recent_videos->have_posts()) : $recent_videos->the_post(); ?>
                            <div class="vh360-dashboard-video-item">
                                <div class="vh360-dashboard-video-thumbnail">
                                    <?php
                                    $thumbnail = vh360_get_video_thumbnail(get_the_ID(), 'thumbnail');
                                    if ($thumbnail) :
                                    ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php else : ?>
                                        <div class="vh360-video-thumbnail-placeholder">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="vh360-dashboard-video-info">
                                    <h3 class="vh360-dashboard-video-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <div class="vh360-dashboard-video-meta">
                                        <span><?php echo esc_html(vh360_format_number(vh360_get_video_views(get_the_ID()))); ?> <?php esc_html_e('views', 'videohub360-theme'); ?></span>
                                        <span>•</span>
                                        <span><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> <?php esc_html_e('ago', 'videohub360-theme'); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                <?php else : ?>
                    <div class="vh360-dashboard-empty">
                        <div class="vh360-dashboard-empty-icon">📹</div>
                        <p class="vh360-dashboard-empty-title"><?php esc_html_e('No videos yet', 'videohub360-theme'); ?></p>
                        <p class="vh360-dashboard-empty-text"><?php esc_html_e('Upload your first video to get started!', 'videohub360-theme'); ?></p>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=videohub360')); ?>" class="vh360-dashboard-btn">
                            <?php esc_html_e('Upload Video', 'videohub360-theme'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- .vh360-dashboard-card -->
        
        <!-- Recent Activity -->
        <div class="vh360-dashboard-card">
            <div class="vh360-dashboard-card-header">
                <h2 class="vh360-dashboard-card-title"><?php esc_html_e('Recent Activity', 'videohub360-theme'); ?></h2>
                <a href="#activity" class="vh360-dashboard-tab" data-tab="activity"><?php esc_html_e('View All', 'videohub360-theme'); ?></a>
            </div>
            <div class="vh360-dashboard-card-body">
                <?php if (!empty($activities)) : ?>
                    <div class="vh360-activity-list">
                        <?php foreach ($activities as $activity) : ?>
                            <div class="vh360-activity-item">
                                <div class="vh360-activity-icon">
                                    <?php echo wp_kses_post(vh360_get_activity_icon($activity['type'])); ?>
                                </div>
                                <div class="vh360-activity-content">
                                    <p class="vh360-activity-text">
                                        <?php 
                                        // Handle activity content properly
                                        $content = $activity['content'];
                                        if (is_array($content)) {
                                            if ($activity['type'] === 'video_upload') {
                                                echo esc_html__('uploaded a new video:', 'videohub360-theme') . ' ';
                                                if (!empty($content['link'])) {
                                                    echo '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a>';
                                                } else {
                                                    echo esc_html($content['title']);
                                                }
                                            } else {
                                                echo esc_html($content['title'] ?? '');
                                            }
                                        } else {
                                            echo esc_html($content);
                                        }
                                        ?>
                                    </p>
                                    <span class="vh360-activity-time"><?php echo esc_html(vh360_format_activity_time($activity['timestamp'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="vh360-dashboard-empty">
                        <div class="vh360-dashboard-empty-icon">📊</div>
                        <p class="vh360-dashboard-empty-text"><?php esc_html_e('No recent activity to display.', 'videohub360-theme'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- .vh360-dashboard-card -->
        
    </div><!-- .vh360-dashboard-grid -->
    
    <!-- Bulletin Widget -->
    <?php if (vh360_has_unread_bulletins()) : ?>
        <?php get_template_part('template-parts/bulletin/widget'); ?>
    <?php endif; ?>
    
</div><!-- .vh360-dashboard-overview -->

<style>
/* Dashboard Grid Layout */
.vh360-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

/* Video List Styles */
.vh360-dashboard-video-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-dashboard-video-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.vh360-dashboard-video-item:hover {
    background: var(--bg-light);
}

.vh360-dashboard-video-thumbnail {
    flex: 0 0 120px;
    height: 68px;
    border-radius: var(--border-radius);
    overflow: hidden;
    background: var(--bg-light);
}

.vh360-dashboard-video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vh360-dashboard-video-info {
    flex: 1;
    min-width: 0;
}

.vh360-dashboard-video-title {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    line-height: 1.3;
}

.vh360-dashboard-video-title a {
    color: var(--text-color);
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.vh360-dashboard-video-title a:hover {
    color: var(--primary-color);
}

.vh360-dashboard-video-meta {
    font-size: 0.75rem;
    color: var(--text-light);
    display: flex;
    gap: 0.5rem;
}

/* Activity List Styles */
.vh360-activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-activity-item {
    display: flex;
    gap: 1rem;
}

.vh360-activity-icon {
    flex: 0 0 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.vh360-activity-content {
    flex: 1;
    min-width: 0;
}

.vh360-activity-text {
    font-size: 0.875rem;
    margin: 0 0 0.25rem;
    color: var(--text-color);
}

.vh360-activity-time {
    font-size: 0.75rem;
    color: var(--text-light);
}

@media (max-width: 768px) {
    .vh360-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
