<?php
/**
 * The template for displaying all pages
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get sidebar configuration
$sidebar_config = vh360_resolve_sidebar();
$has_sidebar = $sidebar_config['show_sidebar'];
$sidebar_position = $sidebar_config['position'];

// Determine container class
$container_class = 'container';
if ($has_sidebar) {
    $container_class .= ' has-sidebar sidebar-' . esc_attr($sidebar_position);
} else {
    $container_class .= ' no-sidebar';
}
?>

<div id="primary" class="site-content">
    <div class="<?php echo esc_attr($container_class); ?>">
        
        <?php if ($has_sidebar && 'left' === $sidebar_position) : ?>
            <?php get_sidebar(); ?>
        <?php endif; ?>
        
        <main id="main" class="content-area">

            <?php
            while (have_posts()) :
                the_post();

                get_template_part('template-parts/content', 'page');

                // If comments are open or we have at least one comment, load up the comment template.
                if (comments_open() || get_comments_number()) :
                    comments_template();
                endif;

            endwhile; // End of the loop.
            ?>

        </main><!-- #main -->

        <?php if ($has_sidebar && 'right' === $sidebar_position) : ?>
            <?php get_sidebar(); ?>
        <?php endif; ?>

    </div><!-- .container -->
</div><!-- #primary -->

<?php
get_footer();
