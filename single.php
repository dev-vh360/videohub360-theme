<?php
/**
 * The template for displaying single posts and custom post types
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

                // Check if this is a videohub360 post type
                if (get_post_type() === 'videohub360') {
                    // Let the Videohub360 plugin handle its own template
                    // The plugin provides templates/single-videohub360.php
                    get_template_part('template-parts/content', 'videohub360-single');
                } else {
                    // Use default single post template
                    get_template_part('template-parts/content', 'single');
                }

                if (function_exists('vh360_render_inline_lead_capture')) {
                    vh360_render_inline_lead_capture(get_post_type() === 'videohub360' ? 'single_video' : 'single_post');
                }

                // Post navigation
                the_post_navigation(array(
                    'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous:', 'videohub360-theme') . '</span> <span class="nav-title">%title</span>',
                    'next_text' => '<span class="nav-subtitle">' . esc_html__('Next:', 'videohub360-theme') . '</span> <span class="nav-title">%title</span>',
                ));

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
