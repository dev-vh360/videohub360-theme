<?php
/**
 * Dashboard Liked Videos Template
 *
 * Displays user's liked videos
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get liked video IDs
$liked_video_ids = VideoHub360_Video_Reactions::get_liked_videos($current_user_id, $paged, $per_page);
$total_count = VideoHub360_Video_Reactions::get_liked_videos_count($current_user_id);

?>

<div class="vh360-dashboard-section">
    <div class="vh360-dashboard-header">
        <h2><?php esc_html_e('Liked Videos', 'videohub360-theme'); ?></h2>
        <?php if ($total_count > 0) : ?>
            <span class="vh360-dashboard-count"><?php echo esc_html(number_format($total_count)); ?> <?php echo _n('video', 'videos', $total_count, 'videohub360-theme'); ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($liked_video_ids)) : ?>
        <div class="vh360-dashboard-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
            </svg>
            <h3><?php esc_html_e('No liked videos yet', 'videohub360-theme'); ?></h3>
            <p><?php esc_html_e('Videos you like will appear here', 'videohub360-theme'); ?></p>
        </div>
    <?php else : ?>
        <div class="vh360-video-grid">
            <?php
            foreach ($liked_video_ids as $video_id) :
                $video = get_post($video_id);
                if (!$video || $video->post_type !== 'videohub360') {
                    continue;
                }
                
                $video_url = get_post_meta($video_id, 'video_url', true);
                $views = get_post_meta($video_id, '_videohub360_post_views_count', true);
                $views = $views ? $views : 0;
                $thumbnail = get_the_post_thumbnail_url($video_id, 'medium');
                $author_id = $video->post_author;
                $author = get_userdata($author_id);
                $date = human_time_diff(get_the_time('U', $video_id), current_time('timestamp'));
                ?>
                <div class="vh360-video-card">
                    <a href="<?php echo esc_url(get_permalink($video_id)); ?>" class="vh360-video-thumbnail">
                        <?php if ($thumbnail) : ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($video_id)); ?>">
                        <?php else : ?>
                            <div class="vh360-no-thumbnail">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <span class="vh360-video-duration">
                            <?php
                            $duration = get_post_meta($video_id, '_vh360_video_duration', true);
                            if ($duration) {
                                echo esc_html($duration);
                            }
                            ?>
                        </span>
                    </a>
                    <div class="vh360-video-info">
                        <h3 class="vh360-video-title">
                            <a href="<?php echo esc_url(get_permalink($video_id)); ?>">
                                <?php echo esc_html(get_the_title($video_id)); ?>
                            </a>
                        </h3>
                        <div class="vh360-video-meta">
                            <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>" class="vh360-video-author">
                                <?php echo esc_html($author->display_name); ?>
                            </a>
                            <span class="vh360-video-stats">
                                <?php echo esc_html(number_format($views)); ?> <?php esc_html_e('views', 'videohub360-theme'); ?> • <?php echo esc_html($date); ?> <?php esc_html_e('ago', 'videohub360-theme'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php
        // Pagination
        $total_pages = ceil($total_count / $per_page);
        if ($total_pages > 1) :
            ?>
            <div class="vh360-pagination">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $paged,
                    'total' => $total_pages,
                    'prev_text' => __('&laquo; Previous', 'videohub360-theme'),
                    'next_text' => __('Next &raquo;', 'videohub360-theme'),
                ));
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
