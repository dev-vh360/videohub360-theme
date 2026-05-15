<?php
/**
 * Course Author – Lessons Tab
 *
 * Displays published videohub360 lessons by this instructor grouped by
 * course, then ordered by module number, then lesson number within each course.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id             = get_queried_object_id();
$lesson_label_plural   = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true )  : __( 'Lessons', 'videohub360-theme' );
$lesson_label_singular = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( false ) : __( 'Lesson', 'videohub360-theme' );

// Retrieve instructor's courses (ordered by _vh360_course_order, then name).
$courses = function_exists( 'vh360_get_user_courses' ) ? vh360_get_user_courses( $author_id ) : array();

// Build a grouped structure: course → lessons authored by this instructor.
$grouped_lessons  = array();  // [ term_id => [ 'course' => WP_Term, 'lessons' => [ post_id, … ] ] ]
$total_lesson_count = 0;

foreach ( $courses as $course ) {
    // Get all lessons for this course, sorted by module/lesson number if the plugin helper exists.
    if ( function_exists( 'videohub360_get_course_lessons' ) ) {
        $course_lesson_ids = videohub360_get_course_lessons( $course->term_id );
        if ( ! is_array( $course_lesson_ids ) ) {
            $course_lesson_ids = array();
        }
    } else {
        // Fallback: direct query ordered by module number.  Post IDs are returned
        // here, so we follow up with a usort by module → lesson → date so the
        // ordering matches what videohub360_get_course_lessons() would produce.
        $course_lesson_ids = get_posts( array(
            'post_type'      => 'videohub360',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => array( 'meta_value_num' => 'ASC', 'date' => 'ASC' ),
            'meta_key'       => '_vh360_lesson_module_number', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                array(
                    'taxonomy' => 'videohub360_series',
                    'field'    => 'term_id',
                    'terms'    => $course->term_id,
                ),
            ),
        ) );

        // Sort by module number → lesson number → post date (all ascending).
        usort( $course_lesson_ids, function( $a, $b ) {
            $mod_a    = (int) get_post_meta( $a, '_vh360_lesson_module_number', true );
            $mod_b    = (int) get_post_meta( $b, '_vh360_lesson_module_number', true );
            if ( $mod_a !== $mod_b ) {
                return $mod_a - $mod_b;
            }

            $num_a = (int) get_post_meta( $a, '_vh360_lesson_number', true );
            $num_b = (int) get_post_meta( $b, '_vh360_lesson_number', true );
            if ( $num_a !== $num_b ) {
                return $num_a - $num_b;
            }

            return strtotime( get_post_field( 'post_date', $a ) ) - strtotime( get_post_field( 'post_date', $b ) );
        } );
    }

    // Only keep lessons authored by this instructor.
    // Normalize each item: videohub360_get_course_lessons() may return WP_Post
    // objects or plain post IDs depending on the plugin version.
    $instructor_lessons = array();
    foreach ( $course_lesson_ids as $lesson ) {
        if ( $lesson instanceof WP_Post ) {
            $lesson_id = (int) $lesson->ID;
        } else {
            $lesson_id = absint( $lesson );
        }

        if ( ! $lesson_id ) {
            continue;
        }

        if ( (int) get_post_field( 'post_author', $lesson_id ) === (int) $author_id ) {
            $instructor_lessons[] = $lesson_id;
        }
    }

    if ( ! empty( $instructor_lessons ) ) {
        $grouped_lessons[ $course->term_id ] = array(
            'course'  => $course,
            'lessons' => $instructor_lessons,
        );
        $total_lesson_count += count( $instructor_lessons );
    }
}
?>

<div class="vh360-course-author-lessons" id="vh360-course-tab-lessons">

    <div class="vh360-course-author-section-header">
        <div class="vh360-course-author-section-heading">
            <span class="vh360-course-author-section-kicker"><?php esc_html_e( 'Curriculum', 'videohub360-theme' ); ?></span>
            <h2 class="vh360-course-author-section-title">
                <?php echo esc_html( $lesson_label_plural ); ?>
                <?php if ( $total_lesson_count ) : ?>
                    <span class="vh360-course-author-section-count"><?php echo esc_html( number_format_i18n( $total_lesson_count ) ); ?></span>
                <?php endif; ?>
            </h2>
            <p class="vh360-course-author-section-description"><?php esc_html_e( 'Browse lessons organized by course curriculum.', 'videohub360-theme' ); ?></p>
        </div>
    </div>

    <?php if ( ! empty( $grouped_lessons ) ) : ?>

        <div class="vh360-course-author-lesson-groups">
        <?php foreach ( $grouped_lessons as $group ) :
            $course = $group['course'];
            $lesson_ids = $group['lessons'];
            $group_count = count( $lesson_ids );
        ?>
            <div class="vh360-course-author-lesson-group">
                <div class="vh360-course-author-lesson-group-header">
                    <h3 class="vh360-course-author-lesson-group-title">
                        <a href="<?php echo esc_url( get_term_link( $course, 'videohub360_series' ) ); ?>">
                            <?php echo esc_html( $course->name ); ?>
                        </a>
                    </h3>
                    <span class="vh360-course-author-lesson-group-count">
                        <?php
                        /* translators: %1$s: count, %2$s: lessons label */
                        printf( esc_html__( '%1$s %2$s', 'videohub360-theme' ), esc_html( number_format_i18n( $group_count ) ), esc_html( $lesson_label_plural ) );
                        ?>
                    </span>
                </div>

                <ul class="vh360-course-author-lesson-list">
                    <?php foreach ( $lesson_ids as $lesson_id ) :
                        $module_num = (int) get_post_meta( $lesson_id, '_vh360_lesson_module_number', true );
                        $lesson_num = (int) get_post_meta( $lesson_id, '_vh360_lesson_number', true );
                        $duration   = get_post_meta( $lesson_id, '_vh360_lesson_duration', true );
                        $is_preview = get_post_meta( $lesson_id, '_vh360_lesson_is_preview', true );
                        $thumb_url  = get_the_post_thumbnail_url( $lesson_id, 'videohub360-video-thumb' );
                        $permalink  = get_permalink( $lesson_id );
                        $title      = get_the_title( $lesson_id );
                    ?>
                        <li>
                            <a href="<?php echo esc_url( $permalink ); ?>" class="vh360-course-author-lesson-item">
                                <!-- Thumbnail / number -->
                                <div class="vh360-course-author-lesson-thumb">
                                    <?php if ( $thumb_url ) : ?>
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="vh360-course-author-lesson-thumb-placeholder">
                                            <?php if ( $lesson_num ) : ?>
                                                <?php echo esc_html( $lesson_num ); ?>
                                            <?php else : ?>
                                                ▶
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Content -->
                                <div class="vh360-course-author-lesson-content">
                                    <span class="vh360-course-author-lesson-title"><?php echo esc_html( $title ); ?></span>
                                    <div class="vh360-course-author-lesson-meta">
                                        <?php if ( $module_num ) : ?>
                                            <span>
                                                <?php
                                                /* translators: %s: module number */
                                                printf( esc_html__( 'Module %s', 'videohub360-theme' ), esc_html( $module_num ) );
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ( $lesson_num ) : ?>
                                            <span>
                                                <?php
                                                /* translators: %1$s: lesson label (e.g. "Lesson"), %2$d: lesson number */
                                                printf( esc_html__( '%1$s %2$d', 'videohub360-theme' ), esc_html( $lesson_label_singular ), absint( $lesson_num ) );
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ( 'yes' === $is_preview ) : ?>
                                            <span class="vh360-course-author-preview-badge"><?php esc_html_e( 'Free Preview', 'videohub360-theme' ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <?php if ( $duration ) : ?>
                                    <span class="vh360-course-author-lesson-duration"><?php echo esc_html( $duration ); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        </div>

    <?php else : ?>
        <div class="vh360-course-author-empty-state">
            <div class="vh360-empty-icon">🎬</div>
            <h3><?php esc_html_e( 'No lessons yet', 'videohub360-theme' ); ?></h3>
            <p><?php esc_html_e( 'This instructor hasn\'t published any lessons yet.', 'videohub360-theme' ); ?></p>
        </div>
    <?php endif; ?>

</div>
