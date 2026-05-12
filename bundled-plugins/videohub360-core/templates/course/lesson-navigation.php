<?php
/**
 * Lesson Navigation template part.
 *
 * Displays course context, previous/next lesson links, and a curriculum list
 * with the current lesson highlighted.
 *
 * Must be included from single-videohub360.php only when:
 *   - videohub360_course_features_enabled() returns true
 *   - The current post belongs to at least one videohub360_series term
 *
 * Variables available in scope (set by single-videohub360.php before include):
 *   $vh360_nav_data  array   – return value of videohub360_get_lesson_navigation().
 *   $vh360_post_id   int     – the current post ID.
 *
 * Navigation data keys:
 *   course    WP_Term|false
 *   prev_id   int|false
 *   next_id   int|false
 *   index     int   (1-based)
 *   total     int
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $vh360_nav_data ) || empty( $vh360_nav_data['course'] ) ) {
    return;
}

$nav_course     = $vh360_nav_data['course'];
$nav_prev_id    = ! empty( $vh360_nav_data['prev_id'] ) ? (int) $vh360_nav_data['prev_id'] : false;
$nav_next_id    = ! empty( $vh360_nav_data['next_id'] ) ? (int) $vh360_nav_data['next_id'] : false;
$nav_index      = ! empty( $vh360_nav_data['index'] )   ? (int) $vh360_nav_data['index']   : 0;
$nav_total      = ! empty( $vh360_nav_data['total'] )   ? (int) $vh360_nav_data['total']   : 0;
$nav_course_url = get_term_link( $nav_course );

$course_label = function_exists( 'videohub360_get_course_label' ) ? videohub360_get_course_label() : __( 'Course', 'videohub360' );
$lesson_label = function_exists( 'videohub360_get_lesson_label' ) ? videohub360_get_lesson_label() : __( 'Lesson', 'videohub360' );

// All lessons for the curriculum list.
$nav_lessons = function_exists( 'videohub360_get_course_lessons' )
    ? videohub360_get_course_lessons( $nav_course->term_id )
    : array();
?>
<div class="vh360-lesson-navigation vh360-lesson-context">

    <div class="vh360-lesson-nav-header">
        <a class="vh360-back-to-course" href="<?php echo esc_url( $nav_course_url ); ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
            <?php echo esc_html( $course_label ); ?>
        </a>

        <p class="vh360-lesson-nav-course-title">
            <?php echo esc_html( $nav_course->name ); ?>
        </p>

        <?php if ( $nav_index && $nav_total ) : ?>
            <p class="vh360-lesson-nav-position">
                <?php
                printf(
                    /* translators: 1: lesson label, 2: current position, 3: total lessons */
                    esc_html__( '%1$s %2$s of %3$s', 'videohub360' ),
                    esc_html( $lesson_label ),
                    esc_html( $nav_index ),
                    esc_html( $nav_total )
                );
                ?>
            </p>
        <?php endif; ?>
    </div><!-- .vh360-lesson-nav-header -->

    <div class="vh360-lesson-prev-next">
        <?php if ( $nav_prev_id ) : ?>
            <a class="vh360-lesson-nav-card vh360-lesson-prev" href="<?php echo esc_url( get_permalink( $nav_prev_id ) ); ?>">
                <span class="vh360-nav-label">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
                        <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/>
                    </svg>
                    <?php esc_html_e( 'Previous', 'videohub360' ); ?>
                </span>
                <span class="vh360-nav-title"><?php echo esc_html( get_the_title( $nav_prev_id ) ); ?></span>
            </a>
        <?php else : ?>
            <span class="vh360-lesson-nav-card vh360-lesson-prev vh360-nav-disabled" aria-disabled="true">
                <span class="vh360-nav-label">
                    <?php esc_html_e( 'Previous', 'videohub360' ); ?>
                </span>
            </span>
        <?php endif; ?>

        <?php if ( $nav_next_id ) : ?>
            <a class="vh360-lesson-nav-card vh360-lesson-next" href="<?php echo esc_url( get_permalink( $nav_next_id ) ); ?>">
                <span class="vh360-nav-label">
                    <?php esc_html_e( 'Next', 'videohub360' ); ?>
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
                        <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
                    </svg>
                </span>
                <span class="vh360-nav-title"><?php echo esc_html( get_the_title( $nav_next_id ) ); ?></span>
            </a>
        <?php else : ?>
            <span class="vh360-lesson-nav-card vh360-lesson-next vh360-nav-disabled" aria-disabled="true">
                <span class="vh360-nav-label">
                    <?php esc_html_e( 'Next', 'videohub360' ); ?>
                </span>
            </span>
        <?php endif; ?>
    </div><!-- .vh360-lesson-prev-next -->

    <?php if ( ! empty( $nav_lessons ) ) : ?>
    <div class="vh360-lesson-nav-curriculum">
        <h3 class="vh360-lesson-nav-curriculum-heading"><?php esc_html_e( 'Curriculum', 'videohub360' ); ?></h3>
        <ol class="vh360-lesson-nav-list">
            <?php
            $nav_counter = 0;
            foreach ( $nav_lessons as $nav_lesson ) :
                $nav_counter++;
                $is_current = ( (int) $nav_lesson->ID === (int) $vh360_post_id );
            ?>
            <li class="vh360-lesson-nav-item<?php echo $is_current ? ' vh360-course-lesson-current' : ''; ?>">
                <?php if ( $is_current ) : ?>
                    <span class="vh360-nav-item-inner vh360-nav-item-current" aria-current="page">
                        <span class="vh360-nav-num"><?php echo esc_html( sprintf( '%02d', $nav_counter ) ); ?></span>
                        <span class="vh360-nav-title"><?php echo esc_html( $nav_lesson->post_title ); ?></span>
                    </span>
                <?php else : ?>
                    <a class="vh360-nav-item-inner" href="<?php echo esc_url( get_permalink( $nav_lesson->ID ) ); ?>">
                        <span class="vh360-nav-num"><?php echo esc_html( sprintf( '%02d', $nav_counter ) ); ?></span>
                        <span class="vh360-nav-title"><?php echo esc_html( $nav_lesson->post_title ); ?></span>
                    </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div><!-- .vh360-lesson-nav-curriculum -->
    <?php endif; ?>

</div><!-- .vh360-lesson-navigation -->
