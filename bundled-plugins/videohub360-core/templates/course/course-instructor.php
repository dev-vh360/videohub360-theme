<?php
/**
 * Course Instructor template part.
 *
 * Variables available in scope (set by taxonomy-videohub360_series.php):
 *   $instructor  WP_User|false – the course instructor.
 *
 * If $instructor is false or empty this template renders nothing.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $instructor ) || ! ( $instructor instanceof WP_User ) ) {
    return;
}

$instructor_label = function_exists( 'videohub360_get_instructor_label' )
    ? videohub360_get_instructor_label()
    : __( 'Instructor', 'videohub360' );

// Bio: prefer user description meta, fall back to empty.
$bio = get_user_meta( $instructor->ID, 'description', true );

// Author page URL.
$author_url = get_author_posts_url( $instructor->ID );
?>
<section class="vh360-course-instructor">

    <h2 class="vh360-course-section-heading"><?php echo esc_html( $instructor_label ); ?></h2>

    <div class="vh360-instructor-card">

        <div class="vh360-instructor-avatar">
            <a href="<?php echo esc_url( $author_url ); ?>" tabindex="-1" aria-hidden="true">
                <?php echo get_avatar( $instructor->ID, 80, '', esc_attr( $instructor->display_name ) ); ?>
            </a>
        </div>

        <div class="vh360-instructor-info">
            <a class="vh360-instructor-name" href="<?php echo esc_url( $author_url ); ?>">
                <?php echo esc_html( $instructor->display_name ); ?>
            </a>

            <?php if ( ! empty( $bio ) ) : ?>
                <div class="vh360-instructor-bio">
                    <?php echo wp_kses_post( wpautop( $bio ) ); ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- .vh360-instructor-card -->

</section><!-- .vh360-course-instructor -->
