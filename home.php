<?php
/**
 * Blog Archive Template (Posts Index)
 *
 * This is the main posts index template. In WordPress template hierarchy:
 * - front-page.php is the site homepage
 * - home.php is the posts index (blog page)
 *
 * This template is intentionally kept thin to serve as a controller,
 * with the real blog UI implemented in dedicated blog partials.
 *
 * @package Videohub360_Theme
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div id="primary" class="site-content vh360-blog-archive">
    <main id="main" class="site-main">
        
        <?php
        // Blog header with title and description
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
