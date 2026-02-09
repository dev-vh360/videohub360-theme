<?php
/**
 * The template for displaying archive pages
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

            <?php if (have_posts()) : ?>

                <header class="page-header">
                    <?php
                    the_archive_title('<h1 class="page-title">', '</h1>');
                    the_archive_description('<div class="archive-description">', '</div>');
                    ?>
                </header><!-- .page-header -->

                <?php
                // Check if this is a videohub360 archive
                if (is_post_type_archive('videohub360') || is_tax(array('videohub360_category', 'videohub360_series', 'videohub360_location'))) :
                ?>
                    <div class="video-grid">
                        <?php
                        while (have_posts()) :
                            the_post();
                            get_template_part('template-parts/content', 'video');
                        endwhile;
                        ?>
                    </div>
                <?php
                else :
                    // Standard blog archive
                    while (have_posts()) :
                        the_post();
                        get_template_part('template-parts/content', get_post_type());
                    endwhile;
                endif;

                // Pagination
                the_posts_pagination(array(
                    'mid_size'  => 2,
                    'prev_text' => __('&laquo; Previous', 'videohub360-theme'),
                    'next_text' => __('Next &raquo;', 'videohub360-theme'),
                ));

            else :

                get_template_part('template-parts/content', 'none');

            endif;
            ?>

        </main><!-- #main -->

        <?php if ($has_sidebar && 'right' === $sidebar_position) : ?>
            <?php get_sidebar(); ?>
        <?php endif; ?>

    </div><!-- .container -->
</div><!-- #primary -->

<?php
get_footer();
