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

    $vh360_show_header  = (bool) get_theme_mod( 'vh360_show_course_catalog_header', 1 );
    $vh360_header_title = get_theme_mod( 'vh360_course_catalog_header_title', __( 'Courses', 'videohub360-theme' ) );
    $vh360_header_desc  = get_theme_mod( 'vh360_course_catalog_header_description', __( 'Browse courses, lessons, and learning tracks available on this site.', 'videohub360-theme' ) );
    $page_description   = get_the_content();
    ?>

    <div id="primary" class="content-area vh360-course-catalog-template-wrap <?php echo esc_attr( $vh360_show_header ? '' : 'vh360-template-header-off' ); ?>">
        <main id="main" class="site-main vh360-course-catalog-template">

            <!-- Page header -->
            <?php if ( $vh360_show_header ) : ?>
            <header class="vh360-course-catalog-template-header">
                <div class="vh360-container">
                    <h1 class="vh360-course-catalog-page-title">
                        <?php echo esc_html( $vh360_header_title ); ?>
                    </h1>
                    <?php if ( $vh360_header_desc ) : ?>
                    <p class="vh360-course-catalog-page-description">
                        <?php echo esc_html( $vh360_header_desc ); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </header>
            <?php endif; ?>

            <?php if (function_exists('vh360_render_inline_lead_capture')) : ?>
                <div class="vh360-container">
                    <?php vh360_render_inline_lead_capture('course_catalog'); ?>
                </div>
            <?php endif; ?>

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
