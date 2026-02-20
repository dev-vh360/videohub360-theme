<?php
/**
 * Business Profile Content Tab
 *
 * Displays content (posts, videos) for business profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();

// Query for user's content
$content_query = new WP_Query(array(
    'author' => $author_id,
    'post_type' => array('post', 'video'),
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
));
?>

<div class="vh360-business-content">
    
    <h2><?php esc_html_e('Content', 'videohub360-theme'); ?></h2>
    
    <?php if ($content_query->have_posts()) : ?>
        <div class="vh360-content-grid">
            <?php
            while ($content_query->have_posts()) {
                $content_query->the_post();
                get_template_part('template-parts/content', get_post_type());
            }
            wp_reset_postdata();
            ?>
        </div>
        
        <?php
        // Pagination
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => __('&laquo; Previous', 'videohub360-theme'),
            'next_text' => __('Next &raquo;', 'videohub360-theme'),
        ));
        ?>
    <?php else : ?>
        <p><?php esc_html_e('No content available.', 'videohub360-theme'); ?></p>
    <?php endif; ?>
    
</div>
