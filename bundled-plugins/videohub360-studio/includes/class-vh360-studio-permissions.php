<?php
/**
 * Studio permission helpers.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Permissions {
    /**
     * Determine whether a user may access Studio tools.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return bool
     */
    public static function user_can_access_studio( $user_id = null ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();

        if ( ! $user_id ) {
            return false;
        }

        if ( function_exists( 'vh360_user_can_host_live_rooms' ) ) {
            return (bool) vh360_user_can_host_live_rooms( $user_id );
        }

        return current_user_can( 'manage_options' ) || current_user_can( 'vh360_host_live_rooms' );
    }

    /**
     * Determine whether current user may manage all Studio jobs.
     *
     * @return bool
     */
    public static function current_user_can_manage_all_jobs() {
        return current_user_can( 'manage_options' );
    }
}
