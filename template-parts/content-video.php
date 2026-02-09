<?php
/**
 * Template part for displaying video posts in grid
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get video metadata
$views = get_post_meta(get_the_ID(), '_videohub360_post_views_count', true);
$views = $views ? intval($views) : 0;
$views_display = number_format_i18n($views);

// Check if video is live
$is_live = get_post_meta(get_the_ID(), '_vh360_is_live', true);
$stream_stopped = get_post_meta(get_the_ID(), '_vh360_stream_stopped', true);
$live_badge = get_post_meta(get_the_ID(), '_vh360_live_badge', true);
$badge_text = get_post_meta(get_the_ID(), '_vh360_badge_text', true) ?: 'LIVE';
$badge_color = get_post_meta(get_the_ID(), '_vh360_badge_color', true) ?: '#e53935';
$show_live_badge = ($is_live === 'yes' && $stream_stopped !== 'yes' && $live_badge !== 'no');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('video-item'); ?>>
    <a href="<?php the_permalink(); ?>" class="video-thumbnail">
        <?php
        if (has_post_thumbnail()) {
            the_post_thumbnail('videohub360-video-thumb', array(
                'alt' => get_the_title(),
            ));
        } else {
            echo '<div class="no-thumbnail" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">📹</div>';
        }
        ?>
        
        <?php if ($show_live_badge) : ?>
            <span class="live-badge" data-badge-color="<?php echo esc_attr($badge_color); ?>">
                <?php echo esc_html($badge_text); ?>
            </span>
        <?php endif; ?>
        
        <span class="play-button" aria-label="<?php esc_attr_e('Play video', 'videohub360-theme'); ?>">
            <svg viewBox="0 0 60 60" width="60" height="60">
                <circle cx="30" cy="30" r="28" fill="rgba(0,0,0,0.6)"/>
                <polygon points="24,18 46,30 24,42" fill="white"/>
            </svg>
        </span>
    </a>

    <div class="video-info">
        <h3 class="video-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <div class="video-meta">
            <span class="video-date"><?php echo get_the_date(); ?></span>
            <?php if ($views > 0) : ?>
                <span class="video-views">
                    <?php
                    // Compact view count for large numbers
                    if ($views >= 1000000) {
                        echo number_format_i18n($views / 1000000, 1) . 'M';
                    } elseif ($views >= 1000) {
                        echo number_format_i18n($views / 1000, 1) . 'K';
                    } else {
                        echo $views_display;
                    }
                    echo ' ' . esc_html__('views', 'videohub360-theme');
                    ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</article><!-- #post-<?php the_ID(); ?> -->
