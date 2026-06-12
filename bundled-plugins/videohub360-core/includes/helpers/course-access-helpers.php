<?php
/**
 * Core course access helpers.
 *
 * Provides vh360_user_can_access_course() as a canonical, core-owned helper.
 * It intentionally calls vh360_user_has_course_entitlement() and
 * vh360_user_has_active_membership() only when those optional functions exist
 * (they are provided by the videohub360-memberships bundled plugin).
 *
 * The videohub360-memberships plugin defines its own version of this function
 * behind a function_exists() guard, so the core version (loaded earlier) wins.
 *
 * @package VideoHub360
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vh360_user_can_access_course' ) ) {
    /**
     * Determine whether a user may access a course.
     *
     * Access rules by purchase mode:
     *  - 'none'       → always accessible (public / free course).
     *  - 'product'    → user must hold an active course entitlement.
     *  - 'membership' → user must hold the required active membership.
     *  - 'both'       → entitlement OR active membership satisfies access.
     *
     * Admins (manage_options) and course managers always have access regardless of mode.
     *
     * @param int $user_id        WordPress user ID (0 = current user).
     * @param int $course_term_id videohub360_series term ID.
     * @return bool
     */
    function vh360_user_can_access_course( $user_id, $course_term_id ) {
        $user_id        = absint( $user_id ?: get_current_user_id() );
        $course_term_id = absint( $course_term_id );

        if ( ! $course_term_id ) {
            return false;
        }

        if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        // Course owners/managers should always be able to access their own course,
        // regardless of product or membership requirements.
        if (
            $user_id
            && function_exists( 'vh360_user_can_manage_course' )
            && vh360_user_can_manage_course( $user_id, $course_term_id )
        ) {
            return true;
        }

        // Resolve the purchase mode from core first, then memberships plugin, then
        // directly from term meta so restricted courses never fall back to 'none'
        // simply because the memberships plugin is inactive.
        if ( function_exists( 'videohub360_get_course_purchase_mode' ) ) {
            $mode = videohub360_get_course_purchase_mode( $course_term_id );
        } elseif ( function_exists( 'vh360_get_course_purchase_mode' ) ) {
            $mode = vh360_get_course_purchase_mode( $course_term_id );
        } else {
            $raw = sanitize_key( (string) get_term_meta( $course_term_id, '_vh360_course_purchase_mode', true ) );
            if ( in_array( $raw, array( 'none', 'product', 'membership', 'both' ), true ) ) {
                $mode = $raw;
            } elseif ( absint( get_term_meta( $course_term_id, '_vh360_course_product_id', true ) ) > 0 ) {
                $mode = 'product';
            } elseif ( get_term_meta( $course_term_id, '_vh360_course_required_membership', true ) ) {
                $mode = 'membership';
            } else {
                $mode = 'none';
            }
        }

        if ( 'none' === $mode ) {
            return true;
        }

        // Resolve the required membership slug/ID (used for 'membership' / 'both' modes).
        $required_membership = function_exists( 'videohub360_get_course_required_membership' )
            ? videohub360_get_course_required_membership( $course_term_id )
            : get_term_meta( $course_term_id, '_vh360_course_required_membership', true );

        // Check entitlement (product / both).
        $has_entitlement = false;
        if ( $user_id && function_exists( 'vh360_user_has_course_entitlement' ) ) {
            $has_entitlement = vh360_user_has_course_entitlement( $user_id, $course_term_id );
        }

        // Check membership (membership / both).
        $has_membership = false;
        if ( $user_id && ! empty( $required_membership ) && function_exists( 'vh360_user_has_active_membership' ) ) {
            if ( 'any' === $required_membership ) {
                $has_membership = vh360_user_has_active_membership( $user_id );
            } else {
                $has_membership = vh360_user_has_active_membership( $user_id, $required_membership );
            }
        }

        if ( 'product' === $mode ) {
            return $has_entitlement;
        }

        if ( 'membership' === $mode ) {
            return empty( $required_membership ) ? true : $has_membership;
        }

        if ( 'both' === $mode ) {
            return $has_entitlement || ( ! empty( $required_membership ) && $has_membership );
        }

        return false;
    }
}
