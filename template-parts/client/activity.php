<?php
/**
 * Client Profile Activity Tab
 *
 * Displays activity information for client profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();

// Query for user's recent activity (comments, posts if any)
$recent_comments = get_comments(array(
    'user_id' => $author_id,
    'number' => 10,
    'status' => 'approve',
));
?>

<div class="vh360-client-activity">
    
    <h2><?php esc_html_e('Recent Activity', 'videohub360-theme'); ?></h2>
    
    <?php if ($recent_comments) : ?>
        <div class="vh360-activity-list">
            <?php foreach ($recent_comments as $comment) : ?>
                <div class="vh360-activity-item">
                    <div class="vh360-activity-meta">
                        <time datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>">
                            <?php echo esc_html(human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp')) . ' ago'); ?>
                        </time>
                    </div>
                    <div class="vh360-activity-content">
                        <p>
                            <?php esc_html_e('Commented on', 'videohub360-theme'); ?>
                            <a href="<?php echo esc_url(get_comment_link($comment)); ?>">
                                <?php echo esc_html(get_the_title($comment->comment_post_ID)); ?>
                            </a>
                        </p>
                        <blockquote><?php echo esc_html(wp_trim_words($comment->comment_content, 20)); ?></blockquote>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No recent activity.', 'videohub360-theme'); ?></p>
    <?php endif; ?>
    
</div>
