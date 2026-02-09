<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div id="primary" class="site-content">
    <div class="container">
        <main id="main" class="content-area">

            <section class="error-404 not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php esc_html_e('Oops! That page can&rsquo;t be found.', 'videohub360-theme'); ?></h1>
                </header><!-- .page-header -->

                <div class="page-content">
                    <p><?php esc_html_e('It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'videohub360-theme'); ?></p>

                    <?php
                    get_search_form();

                    // Show recent videos if the Videohub360 plugin is active
                    if (post_type_exists('videohub360')) :
                        $recent_videos = new WP_Query(array(
                            'post_type'      => 'videohub360',
                            'posts_per_page' => 6,
                            'post_status'    => 'publish',
                        ));

                        if ($recent_videos->have_posts()) :
                    ?>
                            <div class="widget widget_recent_entries">
                                <h2 class="widget-title"><?php esc_html_e('Recent Videos', 'videohub360-theme'); ?></h2>
                                <div class="video-grid">
                                    <?php
                                    while ($recent_videos->have_posts()) :
                                        $recent_videos->the_post();
                                        get_template_part('template-parts/content', 'video');
                                    endwhile;
                                    wp_reset_postdata();
                                    ?>
                                </div>
                            </div>
                    <?php
                        endif;
                    endif;

                    // Show categories if available
                    $categories = get_categories(array(
                        'numberposts' => 10,
                    ));

                    if ($categories) :
                    ?>
                        <div class="widget widget_categories">
                            <h2 class="widget-title"><?php esc_html_e('Most Used Categories', 'videohub360-theme'); ?></h2>
                            <ul>
                                <?php
                                foreach ($categories as $category) {
                                    echo '<li><a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div><!-- .widget -->
                    <?php endif; ?>

                </div><!-- .page-content -->
            </section><!-- .error-404 -->

        </main><!-- #main -->
    </div><!-- .container -->
</div><!-- #primary -->

<?php
get_footer();
