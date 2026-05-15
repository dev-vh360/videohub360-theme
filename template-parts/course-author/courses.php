<?php
/**
 * Course Author – Courses Tab
 *
 * Displays the instructor's courses as cards with featured image, title,
 * subtitle, level, duration, lesson count, and a link to the course page.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id    = get_queried_object_id();
$course_label = function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( true ) : __( 'Courses', 'videohub360-theme' );
$lesson_label = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' );

$courses = function_exists( 'vh360_get_user_courses' ) ? vh360_get_user_courses( $author_id ) : array();
?>

<div class="vh360-course-author-courses" id="vh360-course-tab-courses">

    <div class="vh360-course-author-section-header">
        <div class="vh360-course-author-section-heading">
            <span class="vh360-course-author-section-kicker"><?php esc_html_e( 'Learning Paths', 'videohub360-theme' ); ?></span>
            <h2 class="vh360-course-author-section-title">
                <?php echo esc_html( $course_label ); ?>
                <?php if ( ! empty( $courses ) ) : ?>
                    <span class="vh360-course-author-section-count"><?php echo esc_html( number_format_i18n( count( $courses ) ) ); ?></span>
                <?php endif; ?>
            </h2>
            <p class="vh360-course-author-section-description"><?php esc_html_e( 'Explore this instructor\'s available learning paths.', 'videohub360-theme' ); ?></p>
        </div>
    </div>

    <?php if ( ! empty( $courses ) ) : ?>
        <div class="vh360-course-author-courses-grid">
            <?php foreach ( $courses as $course ) :
                $term_id      = $course->term_id;
                $course_url   = get_term_link( $course, 'videohub360_series' );
                if ( is_wp_error( $course_url ) ) {
                    continue;
                }

                // Course meta.
                $featured_image_id = (int) get_term_meta( $term_id, '_vh360_course_featured_image_id', true );
                $subtitle          = get_term_meta( $term_id, '_vh360_course_subtitle', true );
                $level             = get_term_meta( $term_id, '_vh360_course_level', true );
                $duration          = get_term_meta( $term_id, '_vh360_course_duration', true );
                $membership        = get_term_meta( $term_id, '_vh360_course_required_membership', true );
                $lesson_count      = function_exists( 'vh360_get_course_lesson_count' ) ? vh360_get_course_lesson_count( $term_id ) : 0;

                // Thumbnail: prefer course featured image, fall back to first lesson thumbnail.
                if ( $featured_image_id ) {
                    $thumb_url = wp_get_attachment_image_url( $featured_image_id, 'videohub360-video-thumb' );
                } else {
                    $first_lesson = get_posts( array(
                        'post_type'      => 'videohub360',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'videohub360_series',
                                'field'    => 'term_id',
                                'terms'    => $term_id,
                            ),
                        ),
                    ) );
                    $thumb_url = ! empty( $first_lesson ) ? get_the_post_thumbnail_url( $first_lesson[0], 'videohub360-video-thumb' ) : '';
                }
            ?>
            <article class="vh360-course-card">
                    <a href="<?php echo esc_url( $course_url ); ?>" class="vh360-course-card-link">
                        <!-- Thumbnail -->
                        <div class="vh360-course-card-thumb">
                            <?php if ( $thumb_url ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $course->name ); ?>" loading="lazy">
                            <?php else : ?>
                                <div class="vh360-course-card-thumb-placeholder"></div>
                            <?php endif; ?>

                            <?php if ( $membership ) : ?>
                                <span class="vh360-course-card-badge"><?php echo esc_html( ucfirst( $membership ) ); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="vh360-course-card-body">
                            <h3 class="vh360-course-card-title"><?php echo esc_html( $course->name ); ?></h3>

                            <?php if ( $subtitle ) : ?>
                                <p class="vh360-course-card-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                            <?php endif; ?>

                            <div class="vh360-course-card-meta">
                                <?php if ( $level ) : ?>
                                    <span class="vh360-course-card-level"><?php echo esc_html( $level ); ?></span>
                                <?php endif; ?>
                                <?php if ( $duration ) : ?>
                                    <span class="vh360-course-card-duration"><?php echo esc_html( $duration ); ?></span>
                                <?php endif; ?>
                                <span class="vh360-course-card-lessons">
                                    <?php
                                    /* translators: %1$s: lesson count, %2$s: lessons label */
                                    printf( esc_html__( '%1$s %2$s', 'videohub360-theme' ), esc_html( number_format_i18n( $lesson_count ) ), esc_html( $lesson_label ) );
                                    ?>
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Footer: View Course CTA -->
                    <div class="vh360-course-card-footer">
                        <a href="<?php echo esc_url( $course_url ); ?>" class="vh360-course-card-button">
                            <?php
                            /* translators: %s: course label singular (e.g. "Course") */
                            printf( esc_html__( 'View %s', 'videohub360-theme' ), esc_html( function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( false ) : __( 'Course', 'videohub360-theme' ) ) );
                            ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <div class="vh360-course-author-empty-state">
            <div class="vh360-empty-icon">📚</div>
            <h3><?php esc_html_e( 'No courses yet', 'videohub360-theme' ); ?></h3>
            <p><?php esc_html_e( 'This instructor hasn\'t published any courses yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
