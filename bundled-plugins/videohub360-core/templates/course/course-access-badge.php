<?php
/**
 * Course / Lesson Access Badge template part.
 *
 * Display the access requirement for a course or a single lesson.
 *
 * Variables available in scope when included from the parent template:
 *   $term_id  (int)  – course (series) term ID – used for course-level badge.
 *   $post_id  (int)  – lesson post ID         – used for lesson-level badge.
 *
 * Only one of $term_id / $post_id needs to be set; $post_id takes precedence.
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$badge_text  = '';
$badge_class = 'vh360-course-access-badge';

if ( ! empty( $post_id ) ) {

    // Lesson-level badge.
    $is_preview = get_post_meta( $post_id, '_vh360_lesson_is_preview', true );

    if ( 'yes' === $is_preview ) {
        $badge_text   = __( 'Free Preview', 'videohub360' );
        $badge_class .= ' vh360-badge-preview';
    } else {
        $plan = function_exists( 'videohub360_get_effective_lesson_required_membership' )
            ? videohub360_get_effective_lesson_required_membership( $post_id )
            : false;

        if ( ! $plan ) {
            $badge_text   = __( 'Free Access', 'videohub360' );
            $badge_class .= ' vh360-badge-free';
        } elseif ( 'any' === $plan ) {
            $badge_text   = __( 'Member Access', 'videohub360' );
            $badge_class .= ' vh360-badge-member';
        } else {
            $badge_text   = ucwords( str_replace( array( '-', '_' ), ' ', $plan ) );
            $badge_class .= ' vh360-badge-plan';
        }
    }
} elseif ( ! empty( $term_id ) ) {

    // Course-level badge.
    $plan = function_exists( 'videohub360_get_course_required_membership' )
        ? videohub360_get_course_required_membership( $term_id )
        : false;

    if ( ! $plan ) {
        $badge_text   = __( 'Free Access', 'videohub360' );
        $badge_class .= ' vh360-badge-free';
    } elseif ( 'any' === $plan ) {
        $badge_text   = __( 'Member Access', 'videohub360' );
        $badge_class .= ' vh360-badge-member';
    } else {
        $badge_text   = ucwords( str_replace( array( '-', '_' ), ' ', $plan ) );
        $badge_class .= ' vh360-badge-plan';
    }
}

if ( $badge_text ) : ?>
<span class="<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
<?php endif;
