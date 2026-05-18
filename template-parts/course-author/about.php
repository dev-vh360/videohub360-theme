<?php
/**
 * Course Author – Instructor About Tab
 *
 * Shows instructor-focused sections: bio, website, social links, and
 * course/lesson stats.  Reuses existing user meta and course data —
 * does NOT add new instructor meta fields.
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

$description  = get_the_author_meta( 'description', $author_id );
$website      = $author->user_url;
$join_date    = vh360_get_user_join_date( $author_id, 'F j, Y' );
$social_links = function_exists( 'vh360_get_user_social_links' ) ? vh360_get_user_social_links( $author_id ) : array();

$courses      = function_exists( 'vh360_get_user_courses' ) ? vh360_get_user_courses( $author_id ) : array();
$course_count = count( $courses );

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

$instructor_label = function_exists( 'vh360_get_instructor_label' ) ? vh360_get_instructor_label() : __( 'Instructor', 'videohub360-theme' );
$course_label     = function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( true ) : __( 'Courses', 'videohub360-theme' );
$lesson_label     = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' );
?>

<div class="vh360-course-author-about" id="vh360-course-tab-about">

    <div class="vh360-course-author-section-header">
        <div class="vh360-course-author-section-heading">
            <span class="vh360-course-author-section-kicker"><?php echo esc_html( $instructor_label ); ?></span>
            <h2 class="vh360-course-author-section-title"><?php esc_html_e( 'About', 'videohub360-theme' ); ?></h2>
            <p class="vh360-course-author-section-description"><?php esc_html_e( 'Learn more about this instructor\'s background and teaching style.', 'videohub360-theme' ); ?></p>
        </div>
    </div>

    <div class="vh360-course-author-about-layout">

        <!-- Main: bio + links -->
        <div class="vh360-course-author-about-main">

            <div class="vh360-course-author-about-card">
                <h3 class="vh360-course-author-subsection-title"><?php esc_html_e( 'Biography', 'videohub360-theme' ); ?></h3>
                <?php if ( $description ) : ?>
                    <div class="vh360-course-author-bio-full">
                        <?php echo wpautop( wp_kses_post( $description ) ); ?>
                    </div>
                <?php else : ?>
                    <div class="vh360-course-author-bio-full vh360-course-author-bio-empty">
                        <p><?php esc_html_e( 'No bio yet.', 'videohub360-theme' ); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ( function_exists( 'vh360_render_public_profile_fields' ) ) : ?>
                    <?php vh360_render_public_profile_fields( $author_id ); ?>
                <?php endif; ?>
            </div>

            <!-- Links -->
            <?php if ( $website || ! empty( $social_links ) ) : ?>
                <div class="vh360-course-author-links-card">
                    <h3 class="vh360-course-author-subsection-title"><?php esc_html_e( 'Links', 'videohub360-theme' ); ?></h3>
                    <div class="vh360-course-author-social-links">
                        <?php foreach ( $social_links as $platform => $url ) :
                            if ( $url ) : ?>
                                <a href="<?php echo esc_url( $url ); ?>" class="vh360-course-author-social-link" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html( ucfirst( $platform ) ); ?>
                                </a>
                            <?php endif;
                        endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: stats -->
        <div class="vh360-course-author-about-sidebar">
            <div class="vh360-course-author-stats-card">
                <h3 class="vh360-course-author-subsection-title"><?php esc_html_e( 'Teaching Stats', 'videohub360-theme' ); ?></h3>
                <div class="vh360-course-author-stats-grid">

                    <div class="vh360-course-author-stat-tile">
                        <span class="vh360-course-author-stat-label"><?php echo esc_html( $course_label ); ?></span>
                        <span class="vh360-course-author-stat-value"><?php echo esc_html( number_format_i18n( $course_count ) ); ?></span>
                    </div>

                    <div class="vh360-course-author-stat-tile">
                        <span class="vh360-course-author-stat-label"><?php echo esc_html( $lesson_label ); ?></span>
                        <span class="vh360-course-author-stat-value"><?php echo esc_html( number_format_i18n( $lesson_count ) ); ?></span>
                    </div>

                    <div class="vh360-course-author-stat-tile">
                        <span class="vh360-course-author-stat-label"><?php esc_html_e( 'Member Since', 'videohub360-theme' ); ?></span>
                        <span class="vh360-course-author-stat-value vh360-course-author-stat-value--sm"><?php echo esc_html( $join_date ); ?></span>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>
