<?php
/**
 * The template for displaying category archives
 *
 * Uses the blog archive system for a first-class category browsing experience.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div id="primary" class="site-content vh360-blog-archive vh360-blog-archive-category">
    <main id="main" class="site-main">
        
        <?php
        // Blog header with category title and description
        get_template_part('template-parts/blog/blog-header');
        
        // Search, filters, and sort controls
        get_template_part('template-parts/blog/blog-controls');
        
        // Results container (posts grid + pagination)
        get_template_part('template-parts/blog/blog-results');
        ?>
        
    </main>
</div>

<?php
get_footer();
