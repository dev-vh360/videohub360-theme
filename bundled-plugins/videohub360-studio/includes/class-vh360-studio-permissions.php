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
     * Determine whether the shared VideoHub360 license is valid.
     *
     * @return bool
     */
    public static function license_is_valid() {
        return function_exists( 'videohub360_license_is_valid' ) && videohub360_license_is_valid();
    }

    /**
     * Determine whether a user may use Studio production features.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return bool
     */
    public static function user_can_use_studio( $user_id = null ) {
        return self::user_can_access_studio( $user_id ) && self::license_is_valid();
    }

    /**
     * Get the shared license-required message.
     *
     * @return string
     */
    public static function license_required_message() {
        return __( 'An active VideoHub360 license is required to use Studio.', 'videohub360-studio' );
    }

    /**
     * Get the shared license-required REST error.
     *
     * @return WP_Error
     */
    public static function license_required_error() {
        return new WP_Error(
            'vh360_license_required',
            self::license_required_message(),
            array( 'status' => 403 )
        );
    }

    /**
     * Return true when licensed, or a shared REST error when locked.
     *
     * @return true|WP_Error
     */
    public static function license_permission_result() {
        return self::license_is_valid() ? true : self::license_required_error();
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
