<?php
/**
 * Course Author – Learner Enrolled Courses Tab
 *
 * Shows the public list of courses a learner is enrolled in.
 * Only enrolled courses with the 'active' or 'completed' status are listed;
 * access_lost / cancelled courses are intentionally excluded from the
 * public profile to protect privacy.
 *
 * The enrollment model records learning activity only – it does NOT
 * grant course access.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id = get_queried_object_id();
$author    = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

if ( ! function_exists( 'vh360_get_user_enrolled_courses' ) ) {
    return;
}

// Show only public-facing statuses on profile pages.
$enrollments = vh360_get_user_enrolled_courses( $author_id, array(
    'limit'   => 50,
    'orderby' => 'last_activity_at',
    'order'   => 'DESC',
) );

// Filter to publicly-shareable statuses.
$public_statuses = array( 'active', 'completed' );
$enrollments     = array_filter( $enrollments, static function( $e ) use ( $public_statuses ) {
    return in_array( $e->status, $public_statuses, true );
} );

$course_label_plural = function_exists( 'videohub360_get_course_label' )
    ? videohub360_get_course_label( true )
    : __( 'Courses', 'videohub360-theme' );
?>

<div class="vh360-course-author-learner-enrolled">

    <h2 class="vh360-course-author-section-title">
        <?php echo esc_html( $course_label_plural ); ?>
    </h2>

    <?php if ( empty( $enrollments ) ) : ?>

        <div class="vh360-course-author-empty-state">
            <p>
                <?php
                printf(
                    /* translators: %s = user display name */
                    esc_html__( '%s hasn\'t started any courses yet.', 'videohub360-theme' ),
                    esc_html( $author->display_name )
                );
                ?>
            </p>
        </div>

    <?php else : ?>

        <div class="vh360-course-author-enrolled-grid">
            <?php foreach ( $enrollments as $enrollment ) :
                $course_term_id = (int) $enrollment->course_term_id;
                $term           = get_term( $course_term_id, 'videohub360_series' );

                if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                }

                $course_url    = get_term_link( $term );
                $thumbnail_id  = (int) get_term_meta( $course_term_id, '_vh360_course_featured_image_id', true );
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';
                $status        = sanitize_key( $enrollment->status );
                $progress      = (float) $enrollment->progress_percent;

                $instructor = function_exists( 'videohub360_get_course_instructor' )
                    ? videohub360_get_course_instructor( $course_term_id )
                    : false;
            ?>
            <div class="vh360-course-author-enrolled-card">

                <?php if ( $thumbnail_url ) : ?>
                    <a href="<?php echo esc_url( $course_url ); ?>" class="vh360-course-author-enrolled-card__thumb-link" tabindex="-1" aria-hidden="true">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>"
                             alt="<?php echo esc_attr( $term->name ); ?>"
                             loading="lazy"
                             class="vh360-course-author-enrolled-card__thumb" />
                    </a>
                <?php endif; ?>

                <div class="vh360-course-author-enrolled-card__body">
                    <h3 class="vh360-course-author-enrolled-card__title">
                        <a href="<?php echo esc_url( $course_url ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </h3>

                    <?php if ( $instructor ) : ?>
                        <p class="vh360-course-author-enrolled-card__instructor">
                            <?php
                            printf(
                                /* translators: %s = instructor display name */
                                esc_html__( 'by %s', 'videohub360-theme' ),
                                esc_html( $instructor->display_name ?? '' )
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( 'completed' === $status ) : ?>
                        <span class="vh360-badge-completed"><?php esc_html_e( 'Completed', 'videohub360-theme' ); ?></span>
                    <?php elseif ( $progress > 0 ) : ?>
                        <span class="vh360-progress-inline">
                            <?php echo esc_html( round( $progress ) ); ?>%
                            <?php esc_html_e( 'complete', 'videohub360-theme' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="vh360-badge-enrolled"><?php esc_html_e( 'Enrolled', 'videohub360-theme' ); ?></span>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div><!-- .vh360-course-author-learner-enrolled -->
