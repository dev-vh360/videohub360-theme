<?php
/**
 * Course Author – Instructor Header
 *
 * Shows the instructor hero: banner, avatar, display name, instructor badge,
 * bio, course/lesson counts, follow button, and CTA.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id    = get_queried_object_id();
$author       = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

$avatar_url   = vh360_get_user_avatar_url( $author_id, 150 );
$cover_image  = vh360_get_user_cover_image( $author_id );
$display_name = $author->display_name;
$description  = get_the_author_meta( 'description', $author_id );

// Counts
$courses      = function_exists( 'vh360_get_user_courses' ) ? vh360_get_user_courses( $author_id ) : array();
$course_count = count( $courses );

// Lesson count: all published videohub360 posts by this author in any series.
$lesson_count = (int) ( new WP_Query( array(
    'post_type'      => 'videohub360',
    'author'         => $author_id,
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'videohub360_series',
            'operator' => 'EXISTS',
        ),
    ),
    'no_found_rows'  => false,
    'fields'         => 'ids',
) ) )->found_posts;

$stats           = vh360_get_user_stats( $author_id );
$can_edit        = function_exists( 'vh360_user_can_edit_profile' ) ? vh360_user_can_edit_profile( $author_id ) : false;
$current_user_id = get_current_user_id();

// Labels
$instructor_label = function_exists( 'vh360_get_instructor_label' ) ? vh360_get_instructor_label() : __( 'Instructor', 'videohub360-theme' );
$course_label     = function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( true ) : __( 'Courses', 'videohub360-theme' );
$lesson_label     = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' );
?>

<div class="vh360-course-author-header">

    <!-- Banner -->
    <div class="vh360-course-author-banner">
        <?php if ( $cover_image ) : ?>
            <img src="<?php echo esc_url( $cover_image ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="vh360-course-author-banner-img">
        <?php else : ?>
            <div class="vh360-course-author-banner-placeholder"></div>
        <?php endif; ?>
    </div>

    <!-- Info bar -->
    <div class="vh360-course-author-info">
        <div class="container">
            <div class="vh360-course-author-info-inner">

                <!-- Avatar -->
                <div class="vh360-course-author-avatar">
                    <?php if ( $avatar_url ) : ?>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
                    <?php else : ?>
                        <img src="<?php echo esc_url( get_avatar_url( $author_id, array( 'size' => 150 ) ) ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div class="vh360-course-author-details">
                    <h1 class="vh360-course-author-name"><?php echo esc_html( $display_name ); ?></h1>
                    <span class="vh360-course-author-badge"><?php echo esc_html( $instructor_label ); ?></span>

                    <?php if ( $description ) : ?>
                        <p class="vh360-course-author-bio"><?php echo esc_html( wp_trim_words( $description, 25 ) ); ?></p>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="vh360-course-author-stats">
                        <span class="vh360-course-author-stat">
                            <strong><?php echo esc_html( number_format_i18n( $course_count ) ); ?></strong>
                            <?php echo esc_html( $course_label ); ?>
                        </span>
                        <span class="vh360-course-author-stat-sep">•</span>
                        <span class="vh360-course-author-stat">
                            <strong><?php echo esc_html( number_format_i18n( $lesson_count ) ); ?></strong>
                            <?php echo esc_html( $lesson_label ); ?>
                        </span>
                        <span class="vh360-course-author-stat-sep">•</span>
                        <span class="vh360-course-author-stat">
                            <strong><?php echo esc_html( number_format_i18n( $stats['followers'] ) ); ?></strong>
                            <?php echo esc_html( _n( 'Follower', 'Followers', $stats['followers'], 'videohub360-theme' ) ); ?>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="vh360-course-author-actions">
                    <?php if ( $can_edit ) :
                        $edit_url = function_exists( 'vh360_get_profile_edit_url' ) ? vh360_get_profile_edit_url( $author_id ) : home_url( '/dashboard/?tab=profile' );
                        ?>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="vh360-course-author-edit-btn">
                            <?php esc_html_e( 'Edit Profile', 'videohub360-theme' ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $current_user_id && $current_user_id !== $author_id && function_exists( 'vh360_follow_button' ) ) :
                        vh360_follow_button( $author_id, 'vh360-course-author-follow-btn' );
                    endif; ?>

                    <?php if ( ! empty( $courses ) ) :
                        $first_course     = reset( $courses );
                        $first_course_url = get_term_link( $first_course, 'videohub360_series' );
                        if ( ! is_wp_error( $first_course_url ) ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( 'tab', 'courses', get_author_posts_url( $author_id ) ) ); ?>" class="vh360-course-author-cta-btn">
                                <?php
                                /* translators: %s: courses label (e.g. "Courses") */
                                printf( esc_html__( 'View %s', 'videohub360-theme' ), esc_html( $course_label ) );
                                ?>
                            </a>
                        <?php endif;
                    endif; ?>
                </div>

            </div>
        </div>
    </div>

</div>
