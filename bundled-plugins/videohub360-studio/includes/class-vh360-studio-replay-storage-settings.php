<?php
/**
 * Server-owned replay storage settings resolver.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Replay_Storage_Settings {
    public static function resolve_default_provider() {
        $registry  = VH360_Studio_Plugin::instance()->registry();
        $raw_saved = get_option( 'vh360_studio_default_replay_storage_provider', '' );
        $saved     = self::normalize_provider_id( $raw_saved );

        if ( $saved && self::provider_is_available( $registry, $saved ) ) {
            return $saved;
        }

        foreach ( array( 'videopress', 'publitio', 'bunny_stream', 'local_media' ) as $provider_id ) {
            if ( self::provider_is_available( $registry, $provider_id ) ) {
                return $provider_id;
            }
        }

        return 'videopress';
    }

    private static function provider_is_available( VH360_Studio_Provider_Registry $registry, $provider_id ) {
        $provider = $registry->get_storage_provider( sanitize_key( $provider_id ) );
        return $provider && $provider->is_available();
    }

    private static function normalize_provider_id( $provider ) {
        $provider = sanitize_key( $provider );
        return 'local' === $provider ? 'local_media' : $provider;
    }
}
