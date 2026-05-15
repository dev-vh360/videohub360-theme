<?php
/**
 * Course Author – Lessons Tab
 *
 * Displays published videohub360 lessons by this instructor that belong to a
 * videohub360_series term.  Sorted by course, then module number, then lesson number.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id    = get_queried_object_id();
$course_label = function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( false ) : __( 'Course', 'videohub360-theme' );
$lesson_label = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' );

// Get paginated lessons for this author that are assigned to a series.
$paged = max( 1, (int) get_query_var( 'paged' ) );

$lessons_query = new WP_Query( array(
    'post_type'      => 'videohub360',
    'author'         => $author_id,
    'post_status'    => 'publish',
    'posts_per_page' => 20,
    'paged'          => $paged,
    'orderby'        => array( 'meta_value_num' => 'ASC', 'date' => 'DESC' ),
    'meta_key'       => '_vh360_lesson_module_number', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        array(
            'taxonomy' => 'videohub360_series',
            'operator' => 'EXISTS',
        ),
    ),
) );
?>

<div class="vh360-course-author-lessons" id="vh360-course-tab-lessons">

    <div class="vh360-course-author-section-header">
        <h2 class="vh360-course-author-section-title">
            <?php echo esc_html( $lesson_label ); ?>
            <?php if ( $lessons_query->found_posts ) : ?>
                <span class="vh360-course-author-section-count">(<?php echo esc_html( number_format_i18n( $lessons_query->found_posts ) ); ?>)</span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if ( $lessons_query->have_posts() ) : ?>
        <ul class="vh360-course-author-lessons-list">
            <?php while ( $lessons_query->have_posts() ) :
                $lessons_query->the_post();
                $lesson_id     = get_the_ID();
                $module_num    = (int) get_post_meta( $lesson_id, '_vh360_lesson_module_number', true );
                $lesson_num    = (int) get_post_meta( $lesson_id, '_vh360_lesson_number', true );
                $duration      = get_post_meta( $lesson_id, '_vh360_lesson_duration', true );
                $is_preview    = get_post_meta( $lesson_id, '_vh360_lesson_is_preview', true );

                // Course name via first assigned series term.
                $series_terms  = wp_get_post_terms( $lesson_id, 'videohub360_series', array( 'fields' => 'all' ) );
                $course_name   = ( ! is_wp_error( $series_terms ) && ! empty( $series_terms ) ) ? $series_terms[0]->name : '';
            ?>
                <li class="vh360-course-lesson-item">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="vh360-course-lesson-thumb">
                            <?php the_post_thumbnail( 'videohub360-video-thumb' ); ?>
                        </a>
                    <?php endif; ?>

                    <div class="vh360-course-lesson-info">
                        <?php if ( $course_name ) : ?>
                            <span class="vh360-course-lesson-course-name"><?php echo esc_html( $course_name ); ?></span>
                        <?php endif; ?>

                        <a href="<?php the_permalink(); ?>" class="vh360-course-lesson-title">
                            <?php the_title(); ?>
                        </a>

                        <div class="vh360-course-lesson-meta">
                            <?php if ( $module_num ) : ?>
                                <span class="vh360-course-lesson-module">
                                    <?php
                                    /* translators: %s: module number */
                                    printf( esc_html__( 'Module %s', 'videohub360-theme' ), esc_html( $module_num ) );
                                    ?>
                                </span>
                            <?php endif; ?>

                            <?php if ( $lesson_num ) : ?>
                                <span class="vh360-course-lesson-number">
                                    <?php
                                    /* translators: %s: lesson number */
                                    printf( esc_html__( '%s %d', 'videohub360-theme' ), esc_html( $course_label ), esc_html( $lesson_num ) );
                                    ?>
                                </span>
                            <?php endif; ?>

                            <?php if ( $duration ) : ?>
                                <span class="vh360-course-lesson-duration"><?php echo esc_html( $duration ); ?></span>
                            <?php endif; ?>

                            <?php if ( 'yes' === $is_preview ) : ?>
                                <span class="vh360-course-lesson-preview-badge"><?php esc_html_e( 'Free Preview', 'videohub360-theme' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>

        <!-- Pagination -->
        <?php if ( $lessons_query->max_num_pages > 1 ) : ?>
            <div class="vh360-course-author-pagination">
                <?php
                echo paginate_links( array(
                    'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                    'format'    => '?paged=%#%',
                    'current'   => $paged,
                    'total'     => $lessons_query->max_num_pages,
                    'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'videohub360-theme' ),
                    'next_text' => esc_html__( 'Next', 'videohub360-theme' ) . ' &raquo;',
                    'type'      => 'list',
                    'end_size'  => 2,
                    'mid_size'  => 2,
                ) );
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="vh360-course-author-empty-state">
            <div class="vh360-empty-icon">🎬</div>
            <h3 class="vh360-empty-title"><?php esc_html_e( 'No lessons yet', 'videohub360-theme' ); ?></h3>
            <p class="vh360-empty-description"><?php esc_html_e( 'This instructor hasn\'t published any lessons yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
