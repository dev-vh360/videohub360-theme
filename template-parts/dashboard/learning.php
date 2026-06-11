<?php
/**
 * Dashboard Tab: My Learning
 *
 * Renders the learner's course library – courses they are actively enrolled in,
 * including progress, last activity, and quick-access links.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    echo '<p>' . esc_html__( 'You must be logged in to view your learning.', 'videohub360-theme' ) . '</p>';
    return;
}

if ( ! function_exists( 'videohub360_course_features_enabled' ) || ! videohub360_course_features_enabled() ) {
    echo '<p>' . esc_html__( 'Course features are not enabled.', 'videohub360-theme' ) . '</p>';
    return;
}

if ( ! function_exists( 'vh360_get_user_enrolled_courses' ) ) {
    echo '<p>' . esc_html__( 'Enrollment features are not available.', 'videohub360-theme' ) . '</p>';
    return;
}

$current_user_id = get_current_user_id();
$enrollments     = vh360_get_user_enrolled_courses( $current_user_id, array(
    'limit'   => 50,
    'orderby' => 'last_activity_at',
    'order'   => 'DESC',
) );

$course_label_plural = function_exists( 'videohub360_get_course_label' )
    ? videohub360_get_course_label( true )
    : __( 'Courses', 'videohub360-theme' );

$catalog_url = function_exists( 'vh360_get_course_catalog_url' )
    ? vh360_get_course_catalog_url()
    : get_permalink( get_option( 'vh360_course_catalog_page_id' ) );
?>

<div class="vh360-my-learning">

    <div class="vh360-dashboard-section-header">
        <h2 class="vh360-dashboard-section-title">
            <?php
            printf(
                /* translators: %s = plural course label e.g. "Courses" */
                esc_html__( 'My Learning', 'videohub360-theme' )
            );
            ?>
        </h2>
        <?php if ( $catalog_url ) : ?>
            <a href="<?php echo esc_url( $catalog_url ); ?>" class="vh360-btn vh360-btn-secondary vh360-btn-sm">
                <?php
                printf(
                    /* translators: %s = plural label e.g. "Courses" */
                    esc_html__( 'Browse %s', 'videohub360-theme' ),
                    esc_html( $course_label_plural )
                );
                ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ( empty( $enrollments ) ) : ?>

        <div class="vh360-empty-state">
            <div class="vh360-empty-state-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <h3 class="vh360-empty-state-title">
                <?php
                printf(
                    /* translators: %s = plural label e.g. "Courses" */
                    esc_html__( 'You haven\'t started any %s yet.', 'videohub360-theme' ),
                    esc_html( strtolower( $course_label_plural ) )
                );
                ?>
            </h3>
            <?php if ( $catalog_url ) : ?>
                <a href="<?php echo esc_url( $catalog_url ); ?>" class="vh360-btn vh360-btn-primary">
                    <?php
                    printf(
                        /* translators: %s = plural label */
                        esc_html__( 'Explore %s', 'videohub360-theme' ),
                        esc_html( $course_label_plural )
                    );
                    ?>
                </a>
            <?php endif; ?>
        </div>

    <?php else : ?>

        <div class="vh360-learning-grid">
            <?php foreach ( $enrollments as $enrollment ) :
                $course_term_id = (int) $enrollment->course_term_id;
                $term           = get_term( $course_term_id, 'videohub360_series' );

                if ( ! $term || is_wp_error( $term ) ) {
                    continue;
                }

                $course_url      = get_term_link( $term );
                $thumbnail_id    = (int) get_term_meta( $course_term_id, '_vh360_course_featured_image_id', true );
                $thumbnail_url   = $thumbnail_id
                    ? wp_get_attachment_image_url( $thumbnail_id, 'medium' )
                    : '';
                $progress        = (float) $enrollment->progress_percent;
                $status          = sanitize_key( $enrollment->status );
                $last_activity   = $enrollment->last_activity_at;
                $enrolled_at     = $enrollment->enrolled_at;
                $last_lesson_id  = (int) $enrollment->last_lesson_id;
                $first_lesson_id = (int) $enrollment->first_lesson_id;

                // Determine CTA.
                $has_access = function_exists( 'vh360_user_can_access_course' )
                    ? vh360_user_can_access_course( $current_user_id, $course_term_id )
                    : false;

                if ( 'access_lost' === $status || ! $has_access ) {
                    $cta_label   = __( 'Access Required', 'videohub360-theme' );
                    $cta_url     = $course_url;
                    $cta_class   = 'vh360-btn-warning';
                } elseif ( $progress > 0 && $last_lesson_id ) {
                    $cta_label = __( 'Continue Learning', 'videohub360-theme' );
                    $cta_url   = get_permalink( $last_lesson_id ) ?: $course_url;
                    $cta_class = 'vh360-btn-primary';
                } elseif ( $first_lesson_id ) {
                    $cta_label = __( 'Start Learning', 'videohub360-theme' );
                    $cta_url   = get_permalink( $first_lesson_id ) ?: $course_url;
                    $cta_class = 'vh360-btn-primary';
                } else {
                    $cta_label = __( 'View Course', 'videohub360-theme' );
                    $cta_url   = $course_url;
                    $cta_class = 'vh360-btn-secondary';
                }

                // Instructor.
                $instructor = function_exists( 'videohub360_get_course_instructor' )
                    ? videohub360_get_course_instructor( $course_term_id )
                    : false;

                // Last activity label.
                $activity_label = '';
                if ( $last_activity && '0000-00-00 00:00:00' !== $last_activity ) {
                    $activity_label = sprintf(
                        /* translators: %s = human-readable time difference */
                        __( 'Last activity %s ago', 'videohub360-theme' ),
                        human_time_diff( strtotime( $last_activity ), current_time( 'timestamp' ) )
                    );
                } elseif ( $enrolled_at && '0000-00-00 00:00:00' !== $enrolled_at ) {
                    $activity_label = sprintf(
                        /* translators: %s = human-readable time difference */
                        __( 'Enrolled %s ago', 'videohub360-theme' ),
                        human_time_diff( strtotime( $enrolled_at ), current_time( 'timestamp' ) )
                    );
                }

                $status_badge_map = array(
                    'active'      => '',
                    'completed'   => __( 'Completed', 'videohub360-theme' ),
                    'archived'    => __( 'Archived', 'videohub360-theme' ),
                    'cancelled'   => __( 'Cancelled', 'videohub360-theme' ),
                    'access_lost' => __( 'Access Required', 'videohub360-theme' ),
                );
                $status_badge = isset( $status_badge_map[ $status ] ) ? $status_badge_map[ $status ] : '';
            ?>
            <div class="vh360-learning-card vh360-learning-card--<?php echo esc_attr( $status ); ?>">

                <?php if ( $thumbnail_url ) : ?>
                <a href="<?php echo esc_url( $course_url ); ?>" class="vh360-learning-card__thumb-link" tabindex="-1" aria-hidden="true">
                    <div class="vh360-learning-card__thumb">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>"
                             alt="<?php echo esc_attr( $term->name ); ?>"
                             loading="lazy" />
                        <?php if ( $status_badge ) : ?>
                            <span class="vh360-learning-card__status-badge vh360-learning-card__status-badge--<?php echo esc_attr( $status ); ?>">
                                <?php echo esc_html( $status_badge ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endif; ?>

                <div class="vh360-learning-card__body">
                    <h3 class="vh360-learning-card__title">
                        <a href="<?php echo esc_url( $course_url ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </h3>

                    <?php if ( $instructor ) : ?>
                        <p class="vh360-learning-card__instructor">
                            <?php
                            printf(
                                /* translators: %s = instructor display name */
                                esc_html__( 'by %s', 'videohub360-theme' ),
                                esc_html( $instructor->display_name ?? '' )
                            );
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( 'access_lost' !== $status ) : ?>
                    <div class="vh360-learning-card__progress-wrap" aria-label="<?php echo esc_attr( sprintf( __( 'Progress: %s%%', 'videohub360-theme' ), round( $progress ) ) ); ?>">
                        <div class="vh360-progress-bar">
                            <div class="vh360-progress-bar__fill"
                                 style="width: <?php echo esc_attr( min( 100, max( 0, $progress ) ) ); ?>%"
                                 role="progressbar"
                                 aria-valuenow="<?php echo esc_attr( round( $progress ) ); ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100"></div>
                        </div>
                        <span class="vh360-progress-bar__label">
                            <?php echo esc_html( round( $progress ) ); ?>%
                            <?php if ( $enrollment->completed_lessons > 0 ) : ?>
                                &mdash;
                                <?php
                                printf(
                                    /* translators: 1: completed count 2: total count */
                                    esc_html__( '%1$d of %2$d lessons', 'videohub360-theme' ),
                                    (int) $enrollment->completed_lessons,
                                    (int) $enrollment->lesson_count
                                );
                                ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ( $activity_label ) : ?>
                        <p class="vh360-learning-card__activity"><?php echo esc_html( $activity_label ); ?></p>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( $cta_url ); ?>"
                       class="vh360-btn <?php echo esc_attr( $cta_class ); ?> vh360-btn-sm vh360-learning-card__cta">
                        <?php echo esc_html( $cta_label ); ?>
                    </a>
                </div>

            </div>
            <?php endforeach; ?>
        </div><!-- .vh360-learning-grid -->

    <?php endif; ?>

</div><!-- .vh360-my-learning -->
