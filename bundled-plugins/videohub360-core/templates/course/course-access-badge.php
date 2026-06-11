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

$resolve_course_badge = static function( $course_term_id ) use ( &$badge_class ) {
    $mode = function_exists( 'videohub360_get_course_purchase_mode' ) ? videohub360_get_course_purchase_mode( $course_term_id ) : 'none';
    $plan = function_exists( 'videohub360_get_course_required_membership' ) ? videohub360_get_course_required_membership( $course_term_id ) : false;

    if ( is_user_logged_in() ) {
        $uid = get_current_user_id();
        if ( function_exists( 'vh360_user_is_enrolled_in_course' ) && vh360_user_is_enrolled_in_course( $uid, $course_term_id ) ) {
            $badge_class .= ' vh360-badge-owned';
            return __( 'Enrolled', 'videohub360' );
        }
        // Legacy fallback: entitlement exists but no enrollment row yet (pre-enrollment-model data).
        // Show "Access Owned" rather than "Enrolled" – run the backfill tool to create the row.
        if ( function_exists( 'vh360_user_has_course_entitlement' ) && vh360_user_has_course_entitlement( $uid, $course_term_id ) ) {
            $badge_class .= ' vh360-badge-owned';
            return __( 'Access Owned', 'videohub360' );
        }
    }

    if ( in_array( $mode, array( 'product', 'both' ), true ) ) {
        $badge_class .= ' vh360-badge-paid';
        return __( 'Paid Course', 'videohub360' );
    }

    if ( ! $plan || 'none' === $mode ) {
        $badge_class .= ' vh360-badge-free';
        return __( 'Free Access', 'videohub360' );
    }

    $badge_class .= ' vh360-badge-member';
    return __( 'Member Access', 'videohub360' );
};

if ( ! empty( $post_id ) ) {

    // Lesson-level badge.
    $is_preview = get_post_meta( $post_id, '_vh360_lesson_is_preview', true );

    if ( 'yes' === $is_preview ) {
        $badge_text   = __( 'Free Preview', 'videohub360' );
        $badge_class .= ' vh360-badge-preview';
    } else {
        $lesson_plan = get_post_meta( $post_id, '_vh360_membership_required', true );

        if ( ! empty( $lesson_plan ) ) {
            $badge_text   = __( 'Member Access', 'videohub360' );
            $badge_class .= ' vh360-badge-member';
        } else {
            $course = function_exists( 'videohub360_get_lesson_course' ) ? videohub360_get_lesson_course( $post_id ) : false;
            if ( $course ) {
                $badge_text = $resolve_course_badge( $course->term_id );
            } else {
                $badge_text   = __( 'Free Access', 'videohub360' );
                $badge_class .= ' vh360-badge-free';
            }
        }
    }
} elseif ( ! empty( $term_id ) ) {
    $badge_text = $resolve_course_badge( $term_id );
}

if ( $badge_text ) : ?>
<span class="<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span>
<?php endif; ?>
