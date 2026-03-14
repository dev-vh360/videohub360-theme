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

// Header visibility and content from customizer
$vh360_show_header  = (bool) get_theme_mod('vh360_show_blog_header', true);
$vh360_header_title = get_theme_mod('vh360_blog_header_title', __('Blog', 'videohub360-theme'));
$vh360_header_desc  = get_theme_mod('vh360_blog_header_description', __('Discover articles, insights, and updates from our community', 'videohub360-theme'));
?>

<div id="primary" class="content-area vh360-blog-archive <?php echo $vh360_show_header ? '' : 'vh360-template-header-off'; ?>">
    <main id="main" class="site-main">
        
        <?php
        // Blog header with title and description
        get_template_part('template-parts/blog/blog-header', null, array(
            'show_header' => $vh360_show_header,
            'header_title' => $vh360_header_title,
            'header_desc' => $vh360_header_desc,
        ));
        
        // Search, filters, and sort controls
        get_template_part('template-parts/blog/blog-controls');
        
        // Results container (posts grid + pagination)
        get_template_part('template-parts/blog/blog-results');
        ?>
        
    </main>
</div>

<?php
get_footer();
