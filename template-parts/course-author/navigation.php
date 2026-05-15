<?php
/**
 * Course Author – Instructor Navigation
 *
 * Tab navigation for the instructor layout: Courses, Lessons, About,
 * Followers, Following.
 *
 * @package Videohub360_Theme
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$author_id   = get_queried_object_id();
$author      = get_userdata( $author_id );

if ( ! $author ) {
    return;
}

$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'courses'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$valid_tabs  = array( 'courses', 'lessons', 'about', 'followers', 'following' );
if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
    $current_tab = 'courses';
}

$author_url   = get_author_posts_url( $author_id );
$course_label = function_exists( 'vh360_get_course_label' ) ? vh360_get_course_label( true ) : __( 'Courses', 'videohub360-theme' );
$lesson_label = function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' );

$tabs = array(
    'courses'   => $course_label,
    'lessons'   => $lesson_label,
    'about'     => __( 'About', 'videohub360-theme' ),
    'followers' => __( 'Followers', 'videohub360-theme' ),
    'following' => __( 'Following', 'videohub360-theme' ),
);
?>

<nav class="vh360-course-author-nav" role="navigation" aria-label="<?php esc_attr_e( 'Instructor Navigation', 'videohub360-theme' ); ?>">
    <div class="container">
        <ul class="vh360-course-author-nav-tabs" role="tablist">
            <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                <li class="vh360-course-author-nav-item" role="presentation">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $author_url ) ); ?>"
                       class="vh360-course-author-nav-link<?php echo ( $current_tab === $tab_key ) ? ' active' : ''; ?>"
                       role="tab"
                       aria-selected="<?php echo ( $current_tab === $tab_key ) ? 'true' : 'false'; ?>"
                       aria-controls="vh360-course-tab-<?php echo esc_attr( $tab_key ); ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
