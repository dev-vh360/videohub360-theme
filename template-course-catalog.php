<?php
/**
 * Template Name: Course Catalog
 * Description: Displays a searchable catalog of VideoHub360 courses.
 *
 * @package Videohub360
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $page_title       = get_the_title();
    $page_description = get_the_content();
    ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main vh360-course-catalog-template">

            <!-- Page header -->
            <header class="vh360-course-catalog-template-header">
                <div class="vh360-container">
                    <p class="vh360-course-catalog-eyebrow">
                        <?php esc_html_e( 'Learning Catalog', 'videohub360' ); ?>
                    </p>
                    <h1 class="vh360-course-catalog-page-title">
                        <?php echo esc_html( $page_title ); ?>
                    </h1>
                    <?php if ( has_excerpt() ) : ?>
                    <p class="vh360-course-catalog-page-description">
                        <?php echo esc_html( get_the_excerpt() ); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Optional page content (editor text above the catalog) -->
            <?php if ( $page_description ) : ?>
            <div class="vh360-container vh360-course-catalog-intro">
                <?php the_content(); ?>
            </div>
            <?php endif; ?>

            <!-- Course Catalog -->
            <div class="vh360-container vh360-course-catalog-template-content">
                <?php
                if ( function_exists( 'videohub360_course_features_enabled' ) && videohub360_course_features_enabled() ) {
                    echo do_shortcode( '[vh360_course_catalog limit="100" show_search="yes" show_filters="yes" show_sort="yes" show_result_count="yes"]' );
                } else {
                    ?>
                    <div class="vh360-course-catalog-missing">
                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                            <p><?php esc_html_e( 'Course / Lesson Features must be enabled in VideoHub360 settings to display the Course Catalog.', 'videohub360' ); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>
            </div>

        </main><!-- #main -->
    </div><!-- #primary -->

    <?php
endwhile;

get_footer();
