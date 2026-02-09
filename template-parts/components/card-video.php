<?php
/**
 * Video Card Component
 *
 * Reusable video card component for displaying video thumbnails
 * with metadata in a consistent format across the theme.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get args with defaults
$args = wp_parse_args($args, array(
    'video_id' => get_the_ID(),
    'size' => 'medium', // small, medium, large
    'show_views' => true,
    'show_duration' => true,
    'show_date' => true,
    'show_author' => true,
));

$video_id = $args['video_id'];

if (!$video_id) {
    return;
}

// Get video data
$video_url = get_permalink($video_id);
$video_title = get_the_title($video_id);
$video_thumbnail = vh360_get_video_thumbnail($video_id, 'videohub360-video-thumb');
$video_views = vh360_get_video_views($video_id);
$video_duration = vh360_get_video_duration($video_id);
$video_date = get_the_date('', $video_id);
$author_id = get_post_field('post_author', $video_id);
$author_name = get_the_author_meta('display_name', $author_id);

// Size classes
$size_class = 'vh360-video-card-' . esc_attr($args['size']);
?>

<article class="vh360-video-card <?php echo esc_attr($size_class); ?>" data-video-id="<?php echo esc_attr($video_id); ?>">
    <a href="<?php echo esc_url($video_url); ?>" class="vh360-video-card-link">
        <div class="vh360-video-thumbnail">
            <div class="vh360-video-thumbnail-wrapper">
                <?php if ($video_thumbnail) : ?>
                    <img 
                        src="<?php echo esc_url($video_thumbnail); ?>" 
                        alt="<?php echo esc_attr($video_title); ?>"
                        loading="lazy"
                        class="vh360-video-thumbnail-image"
                    >
                <?php else : ?>
                    <div class="vh360-video-thumbnail-placeholder">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <?php if ($args['show_duration'] && $video_duration) : ?>
                    <span class="vh360-video-duration"><?php echo esc_html($video_duration); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vh360-video-card-body">
            <h3 class="vh360-video-title"><?php echo esc_html($video_title); ?></h3>
            
            <?php if ($args['show_author']) : ?>
                <div class="vh360-video-author">
                    <span class="vh360-video-author-name"><?php echo esc_html($author_name); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="vh360-video-meta">
                <?php if ($args['show_views']) : ?>
                    <span class="vh360-video-views">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <?php echo esc_html(vh360_format_number($video_views)); ?> views
                    </span>
                <?php endif; ?>
                
                <?php if ($args['show_date']) : ?>
                    <span class="vh360-video-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php echo esc_html($video_date); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </a>
</article>

<style>
/* Video Card Base Styles */
.vh360-video-card {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
}

.vh360-video-card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
}

.vh360-video-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.vh360-video-thumbnail {
    position: relative;
    overflow: hidden;
}

.vh360-video-thumbnail-wrapper {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    background: var(--bg-light);
}

.vh360-video-thumbnail-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.vh360-video-card:hover .vh360-video-thumbnail-image {
    transform: scale(1.05);
}

.vh360-video-thumbnail-placeholder {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
}

.vh360-video-duration {
    position: absolute;
    bottom: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 0, 0, 0.8);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
}

.vh360-video-card-body {
    padding: 1rem;
}

.vh360-video-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--text-color);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.vh360-video-author {
    font-size: 0.875rem;
    color: var(--text-light);
    margin-bottom: 0.5rem;
}

.vh360-video-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-light);
}

.vh360-video-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.vh360-video-meta svg {
    flex-shrink: 0;
}

/* Size Variants */
.vh360-video-card-small .vh360-video-card-body {
    padding: 0.75rem;
}

.vh360-video-card-small .vh360-video-title {
    font-size: 0.875rem;
}

.vh360-video-card-large .vh360-video-card-body {
    padding: 1.5rem;
}

.vh360-video-card-large .vh360-video-title {
    font-size: 1.25rem;
}
</style>
