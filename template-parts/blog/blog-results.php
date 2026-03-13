<?php
/**
 * Blog Archive Results
 *
 * Results container with post grid and pagination.
 * Designed for AJAX replacement.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use global query by default (for initial page load)
global $wp_query;
$paged = max(1, get_query_var('paged'));
?>

<div class="vh360-container">
    <!-- Results container for AJAX replacement -->
    <div id="vh360-blog-results-container" class="vh360-blog-results-container">
        
        <?php if (have_posts()) : ?>
            
            <div class="vh360-blog-list">
                <?php 
                while (have_posts()) : 
                    the_post();
                    
                    get_template_part('template-parts/blog/blog-card', null, array(
                        'post_id' => get_the_ID(),
                    ));
                endwhile;
                ?>
            </div>
            
            <!-- Pagination -->
            <?php 
            get_template_part('template-parts/blog/blog-pagination', null, array(
                'query' => $wp_query,
            ));
            ?>
            
        <?php else : ?>
            
            <!-- Empty State -->
            <?php get_template_part('template-parts/blog/blog-empty'); ?>
            
        <?php endif; ?>
        
    </div>
</div>
