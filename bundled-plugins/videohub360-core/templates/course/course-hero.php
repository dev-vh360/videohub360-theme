<?php
/**
 * Course Hero template part.
 *
 * Variables available in scope (set by taxonomy-videohub360_series.php):
 *   $term           WP_Term  – the course series term.
 *   $term_id        int      – $term->term_id (convenience alias).
 *   $subtitle       string   – course subtitle meta.
 *   $short_desc     string   – short description meta.
 *   $level          string   – course level key (beginner/intermediate/advanced/all).
 *   $duration       string   – course duration string meta.
 *   $featured_image int      – attachment ID for course image.
 *   $lessons        array    – WP_Post[] course lessons.
 *   $instructor     WP_User|false
 *   $cta_text       string   – CTA button label.
 *   $cta_url        string   – CTA button URL.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$lesson_count        = is_array( $lessons ) ? count( $lessons ) : 0;
$course_label        = function_exists( 'videohub360_get_course_label' )    ? videohub360_get_course_label()           : __( 'Course', 'videohub360' );
$lesson_label_single = function_exists( 'videohub360_get_lesson_label' )    ? videohub360_get_lesson_label()           : __( 'Lesson', 'videohub360' );
$lesson_label_plural = function_exists( 'videohub360_get_lesson_label' )    ? videohub360_get_lesson_label( true )     : __( 'Lessons', 'videohub360' );
$instructor_label    = function_exists( 'videohub360_get_instructor_label' ) ? videohub360_get_instructor_label()      : __( 'Instructor', 'videohub360' );

$level_labels = array(
    'beginner'     => __( 'Beginner',     'videohub360' ),
    'intermediate' => __( 'Intermediate', 'videohub360' ),
    'advanced'     => __( 'Advanced',     'videohub360' ),
    'all'          => __( 'All Levels',   'videohub360' ),
);
$level_display = ( ! empty( $level ) && isset( $level_labels[ $level ] ) ) ? $level_labels[ $level ] : esc_html( $level );

$hero_desc = ! empty( $short_desc ) ? $short_desc : ( ! empty( $term->description ) ? $term->description : '' );
?>
<section class="vh360-course-hero">
    <div class="vh360-course-hero-inner">

        <div class="vh360-course-hero-content">

            <p class="vh360-course-label"><?php echo esc_html( $course_label ); ?></p>

            <h1 class="vh360-course-title"><?php echo esc_html( $term->name ); ?></h1>

            <?php if ( ! empty( $subtitle ) ) : ?>
                <p class="vh360-course-subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $hero_desc ) ) : ?>
                <div class="vh360-course-description"><?php echo wp_kses_post( $hero_desc ); ?></div>
            <?php endif; ?>

            <div class="vh360-course-meta">
                <?php if ( $level_display ) : ?>
                    <span class="vh360-course-meta-item vh360-course-level">
                        <?php echo esc_html( $level_display ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( ! empty( $duration ) ) : ?>
                    <span class="vh360-course-meta-item vh360-course-duration">
                        <?php echo esc_html( $duration ); ?>
                    </span>
                <?php elseif ( $lesson_count > 0 ) : ?>
                    <span class="vh360-course-meta-item vh360-course-lesson-count">
                        <?php
                        echo esc_html(
                            $lesson_count . ' ' .
                            ( 1 === $lesson_count ? $lesson_label_single : $lesson_label_plural )
                        );
                        ?>
                    </span>
                <?php endif; ?>

                <?php if ( $instructor ) : ?>
                    <span class="vh360-course-meta-item vh360-course-instructor-name">
                        <?php
                        echo esc_html( $instructor_label . ': ' . $instructor->display_name );
                        ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php
            // Access badge – reuse the dedicated template part.
            include VIDEOHUB360_PLUGIN_DIR . 'templates/course/course-access-badge.php';
            ?>

            <?php if ( ! empty( $cta_url ) ) : ?>
                <div class="vh360-course-cta">
                    <?php
                    // For logged-in non-admin users who already have access, submit a
                    // POST form so the enrollment handler fires before the first lesson.
                    // Admins skip enrollment and use the direct CTA link instead.
                    $cta_via_form = is_user_logged_in()
                        && ! current_user_can( 'manage_options' )
                        && isset( $user_has_access ) && $user_has_access
                        && isset( $term_id ) && $term_id;
                    if ( $cta_via_form ) :
                    ?>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'vh360_start_course_' . $term_id, 'vh360_start_course_nonce' ); ?>
                        <input type="hidden" name="vh360_start_course" value="1" />
                        <input type="hidden" name="vh360_course_term_id" value="<?php echo absint( $term_id ); ?>" />
                        <button type="submit" class="vh360-course-cta-btn">
                            <?php echo esc_html( $cta_text ); ?>
                        </button>
                    </form>
                    <?php else : ?>
                    <a href="<?php echo esc_url( $cta_url ); ?>" class="vh360-course-cta-btn">
                        <?php echo esc_html( $cta_text ); ?>
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div><!-- .vh360-course-hero-content -->

        <?php if ( ! empty( $featured_image ) && wp_attachment_is_image( $featured_image ) ) : ?>
            <div class="vh360-course-image">
                <?php
                echo wp_get_attachment_image(
                    $featured_image,
                    'large',
                    false,
                    array(
                        'alt'   => esc_attr( $term->name ),
                        'class' => 'vh360-course-img',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

    </div><!-- .vh360-course-hero-inner -->
</section><!-- .vh360-course-hero -->
