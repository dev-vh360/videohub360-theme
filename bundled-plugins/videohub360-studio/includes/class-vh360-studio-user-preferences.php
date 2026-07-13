<?php
/**
 * Studio per-user preferences.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_User_Preferences {
    const OVERLAY_TOOLS_META_KEY = '_vh360_studio_enabled_overlay_modules';

    public static function allowed_overlay_modules() {
        return array( 'lower-thirds', 'bible', 'countdown' );
    }

    public static function sanitize_overlay_modules( $modules ) {
        if ( ! is_array( $modules ) ) {
            return array();
        }

        $allowed = self::allowed_overlay_modules();
        $seen    = array();

        foreach ( $modules as $module ) {
            $module = sanitize_key( $module );
            if ( in_array( $module, $allowed, true ) ) {
                $seen[ $module ] = true;
            }
        }

        return array_values(
            array_filter(
                $allowed,
                static function( $module ) use ( $seen ) {
                    return isset( $seen[ $module ] );
                }
            )
        );
    }

    public static function get_enabled_overlay_modules( $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return array();
        }

        $raw = get_user_meta( $user_id, self::OVERLAY_TOOLS_META_KEY, true );
        if ( '' === $raw ) {
            return array();
        }

        return self::sanitize_overlay_modules( $raw );
    }

    public static function save_enabled_overlay_modules( $user_id, $modules ) {
        $sanitized = self::sanitize_overlay_modules( $modules );
        update_user_meta( absint( $user_id ), self::OVERLAY_TOOLS_META_KEY, $sanitized );
        return $sanitized;
    }
}
