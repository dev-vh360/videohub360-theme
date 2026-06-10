<?php
/**
 * Author Course Template (Instructor / Learning Platform Style)
 *
 * Displays author pages in Course mode.  Two layouts inside one template:
 *   - Instructor layout  — for users who own, instruct, or have authored lessons.
 *   - Learner layout     — for regular users who do not teach courses.
 *
 * This file is a partial loaded by author.php.  It does NOT call get_header() or
 * get_footer() — the parent template handles those.
 *
 * Loaded when vh360_author_template_mode = 'course'.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If course features are disabled fall back to Channel template (closest match).
if ( ! function_exists( 'vh360_course_features_enabled' ) || ! vh360_course_features_enabled() ) {
    if ( locate_template( 'author-channel.php' ) ) {
        get_template_part( 'author', 'channel' );
    } else {
        get_template_part( 'author', 'profile' );
    }
    return;
}

$author_id = get_queried_object_id();
$author    = get_userdata( $author_id );

if ( ! $author ) {
    get_template_part( 'template-parts/content', 'none' );
    return;
}

// Determine whether this user is an instructor.
$is_instructor = function_exists( 'vh360_user_is_course_instructor' )
    && vh360_user_is_course_instructor( $author_id );

// -------------------------------------------------------------------------
//  Instructor layout
// -------------------------------------------------------------------------
if ( $is_instructor ) {

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'courses'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $valid_tabs  = array( 'courses', 'lessons', 'about', 'followers', 'following' );
    if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
        $current_tab = 'courses';
    }
    ?>

    <div id="primary" class="site-content">
        <div class="vh360-course-author-page vh360-course-author-page--instructor">

            <?php get_template_part( 'template-parts/course-author/header' ); ?>
            <?php get_template_part( 'template-parts/course-author/navigation' ); ?>

            <div class="container">
                <div class="vh360-course-author-content">

                    <?php if ( 'courses' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/courses' ); ?>

                    <?php elseif ( 'lessons' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/lessons' ); ?>

                    <?php elseif ( 'about' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/about' ); ?>

                    <?php elseif ( 'followers' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/followers' ); ?>

                    <?php elseif ( 'following' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/following' ); ?>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

<?php
// -------------------------------------------------------------------------
//  Learner / regular-user layout
// -------------------------------------------------------------------------
} else {

    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'about'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    // Determine whether the enrolled-courses tab is available.
    $show_enrolled_tab = function_exists( 'videohub360_course_features_enabled' )
        && videohub360_course_features_enabled()
        && function_exists( 'vh360_get_user_enrolled_courses' );

    $valid_tabs = array( 'about', 'activity' );
    if ( $show_enrolled_tab ) {
        $valid_tabs[] = 'enrolled';
    }
    if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
        $current_tab = 'about';
    }
    ?>

    <div id="primary" class="site-content">
        <div class="vh360-course-author-page vh360-course-author-page--learner">

            <?php get_template_part( 'template-parts/course-author/learner-header' ); ?>

            <div class="container">
                <div class="vh360-course-author-learner-nav">
                    <?php
                    $author_url   = get_author_posts_url( $author_id );
                    $learner_tabs = array(
                        'about'    => esc_html__( 'About', 'videohub360-theme' ),
                        'activity' => esc_html__( 'Activity', 'videohub360-theme' ),
                    );
                    if ( $show_enrolled_tab ) {
                        $learner_tabs['enrolled'] = esc_html__( 'Courses', 'videohub360-theme' );
                    }
                    ?>
                    <ul class="vh360-course-author-learner-tabs" role="tablist">
                        <?php foreach ( $learner_tabs as $tab_key => $tab_label ) : ?>
                            <li role="presentation">
                                <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $author_url ) ); ?>"
                                   class="vh360-course-author-learner-tab-link<?php echo ( $current_tab === $tab_key ) ? ' active' : ''; ?>"
                                   role="tab"
                                   aria-selected="<?php echo ( $current_tab === $tab_key ) ? 'true' : 'false'; ?>">
                                    <?php echo esc_html( $tab_label ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="vh360-course-author-learner-content">
                    <?php if ( 'about' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/learner-about' ); ?>
                    <?php elseif ( 'activity' === $current_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/learner-activity' ); ?>
                    <?php elseif ( 'enrolled' === $current_tab && $show_enrolled_tab ) : ?>
                        <?php get_template_part( 'template-parts/course-author/learner-enrolled' ); ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

<?php } ?>
