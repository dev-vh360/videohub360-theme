<?php
/**
 * Related Courses template part.
 *
 * Shows other videohub360_series terms as course cards.
 *
 * Variables available in scope (set by taxonomy-videohub360_series.php):
 *   $term_id  int  – current course term ID (excluded from the list).
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$course_label_plural = function_exists( 'videohub360_get_course_label' )
    ? videohub360_get_course_label( true )
    : __( 'Courses', 'videohub360' );

$lesson_label_single = function_exists( 'videohub360_get_lesson_label' ) ? videohub360_get_lesson_label()       : __( 'Lesson', 'videohub360' );
$lesson_label_plural = function_exists( 'videohub360_get_lesson_label' ) ? videohub360_get_lesson_label( true ) : __( 'Lessons', 'videohub360' );

$related_courses = get_terms( array(
    'taxonomy'   => 'videohub360_series',
    'hide_empty' => true,
    'exclude'    => array( $term_id ),
    'number'     => 3,
) );

if ( empty( $related_courses ) || is_wp_error( $related_courses ) ) {
    return;
}
?>
<section class="vh360-course-related">

    <h2 class="vh360-course-section-heading">
        <?php
        /* translators: %s: plural course label */
        printf( esc_html__( 'More %s', 'videohub360' ), esc_html( $course_label_plural ) );
        ?>
    </h2>

    <div class="vh360-related-grid">
        <?php foreach ( $related_courses as $related ) :
            $rel_subtitle   = get_term_meta( $related->term_id, '_vh360_course_subtitle',           true );
            $rel_short_desc = get_term_meta( $related->term_id, '_vh360_course_short_description',  true );
            $rel_image_id   = get_term_meta( $related->term_id, '_vh360_course_featured_image_id',  true );
            $rel_term_url   = get_term_link( $related );

            // Lesson count.
            $rel_lessons = function_exists( 'videohub360_get_course_lessons' )
                ? videohub360_get_course_lessons( $related->term_id )
                : array();
            $rel_count = count( $rel_lessons );

            // Card description: prefer short_desc, then subtitle, then term description.
            $rel_desc = ! empty( $rel_short_desc )
                ? $rel_short_desc
                : ( ! empty( $rel_subtitle ) ? $rel_subtitle : $related->description );
        ?>
        <div class="vh360-related-course-card">

            <?php if ( ! empty( $rel_image_id ) && wp_attachment_is_image( $rel_image_id ) ) : ?>
                <a href="<?php echo esc_url( $rel_term_url ); ?>" class="vh360-related-course-img-wrap" tabindex="-1" aria-hidden="true">
                    <?php
                    echo wp_get_attachment_image(
                        $rel_image_id,
                        'medium',
                        false,
                        array(
                            'alt'   => esc_attr( $related->name ),
                            'class' => 'vh360-related-course-img',
                        )
                    );
                    ?>
                </a>
            <?php endif; ?>

            <div class="vh360-related-course-info">
                <h3 class="vh360-related-course-title">
                    <a href="<?php echo esc_url( $rel_term_url ); ?>"><?php echo esc_html( $related->name ); ?></a>
                </h3>

                <?php if ( ! empty( $rel_desc ) ) : ?>
                    <p class="vh360-related-course-desc"><?php echo esc_html( $rel_desc ); ?></p>
                <?php endif; ?>

                <?php if ( $rel_count > 0 ) : ?>
                    <p class="vh360-related-course-meta">
                        <?php
                        echo esc_html(
                            $rel_count . ' ' .
                            ( 1 === $rel_count ? $lesson_label_single : $lesson_label_plural )
                        );
                        ?>
                    </p>
                <?php endif; ?>

                <a href="<?php echo esc_url( $rel_term_url ); ?>" class="vh360-related-course-btn">
                    <?php
                    /* translators: %s: singular course label */
                    printf( esc_html__( 'View %s', 'videohub360' ), esc_html( function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label() : __( 'Course', 'videohub360' ) ) );
                    ?>
                </a>
            </div>

        </div><!-- .vh360-related-course-card -->
        <?php endforeach; ?>
    </div><!-- .vh360-related-grid -->

</section><!-- .vh360-course-related -->
