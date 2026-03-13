<?php
/**
 * Blog Post Card Component
 *
 * Reusable post card for displaying in blog archive.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get post ID from args or global post
$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();

if (!$post_id) {
    return;
}

$post = get_post($post_id);

if (!$post || 'post' !== $post->post_type) {
    return;
}

// Get post data
$author_id = $post->post_author;
$author_name = get_the_author_meta('display_name', $author_id);
$author_url = get_author_posts_url($author_id);
$post_date = get_the_date('', $post_id);
$comment_count = get_comments_number($post_id);
$categories = get_the_category($post_id);
$excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 30);
?>

<article id="post-<?php echo esc_attr($post_id); ?>" class="vh360-blog-card">
    
    <!-- Featured Image -->
    <?php if (has_post_thumbnail($post_id)) : ?>
        <div class="vh360-blog-card-image">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo get_the_post_thumbnail($post_id, 'large', array('class' => 'vh360-blog-thumbnail')); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Content -->
    <div class="vh360-blog-card-content">
        
        <!-- Categories -->
        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
        <div class="vh360-blog-card-categories">
            <?php foreach (array_slice($categories, 0, 2) as $category) : ?>
                <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>" 
                   class="vh360-blog-category-tag">
                    <?php echo esc_html($category->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h2 class="vh360-blog-card-title">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>">
                <?php echo esc_html($post->post_title); ?>
            </a>
        </h2>
        
        <!-- Meta -->
        <div class="vh360-blog-card-meta">
            
            <!-- Author -->
            <span class="vh360-blog-card-meta-item vh360-blog-card-author">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <a href="<?php echo esc_url($author_url); ?>">
                    <?php echo esc_html($author_name); ?>
                </a>
            </span>
            
            <!-- Date -->
            <span class="vh360-blog-card-meta-item vh360-blog-card-date">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <time datetime="<?php echo esc_attr(get_the_date('c', $post_id)); ?>">
                    <?php echo esc_html($post_date); ?>
                </time>
            </span>
            
            <!-- Comments -->
            <?php if ($comment_count > 0) : ?>
            <span class="vh360-blog-card-meta-item vh360-blog-card-comments">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <a href="<?php echo esc_url(get_comments_link($post_id)); ?>">
                    <?php
                    printf(
                        _n('%s Comment', '%s Comments', $comment_count, 'videohub360-theme'),
                        number_format_i18n($comment_count)
                    );
                    ?>
                </a>
            </span>
            <?php endif; ?>
            
        </div>
        
        <!-- Excerpt -->
        <?php if (!empty($excerpt)) : ?>
        <div class="vh360-blog-card-excerpt">
            <?php echo wp_kses_post($excerpt); ?>
        </div>
        <?php endif; ?>
        
        <!-- Read More Link -->
        <div class="vh360-blog-card-footer">
            <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="vh360-blog-card-link">
                <?php esc_html_e('Read More', 'videohub360-theme'); ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </a>
        </div>
        
    </div>
    
</article>
