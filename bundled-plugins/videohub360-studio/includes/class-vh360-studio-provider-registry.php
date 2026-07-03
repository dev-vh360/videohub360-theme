<?php
/**
 * Studio provider registry.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Provider_Registry {
    private $live_engines = array();
    private $recorders    = array();
    private $storage      = array();

    public function __construct() {
        $this->register_defaults();
    }

    private function register_defaults() {
        $this->register_live_engine( new VH360_Studio_Placeholder_Live_Engine_Provider( 'agora_browser', __( 'Agora / Browser Live Flow', 'videohub360-studio' ), true ) );
        $this->register_recording_provider( new VH360_Studio_Placeholder_Recording_Provider( 'browser_recording', __( 'Browser Recording', 'videohub360-studio' ), true ) );
        $this->register_storage_provider( new VH360_Studio_VideoPress_Provider() );
        $this->register_storage_provider( new VH360_Studio_Placeholder_Replay_Storage_Provider( 'publitio', __( 'Publitio', 'videohub360-studio' ), false ) );
        $this->register_storage_provider( new VH360_Studio_Placeholder_Replay_Storage_Provider( 'local_media', __( 'Local Media Fallback', 'videohub360-studio' ), true ) );
    }

    public function register_live_engine( VH360_Studio_Live_Engine_Provider $provider ) {
        $this->live_engines[ $provider->get_id() ] = $provider;
    }

    public function register_recording_provider( VH360_Studio_Recording_Provider $provider ) {
        $this->recorders[ $provider->get_id() ] = $provider;
    }

    public function register_storage_provider( VH360_Studio_Replay_Storage_Provider $provider ) {
        $this->storage[ $provider->get_id() ] = $provider;
    }

    public function get_live_engines() {
        return apply_filters( 'vh360_studio_live_engine_providers', $this->live_engines );
    }

    public function get_recording_providers() {
        return apply_filters( 'vh360_studio_recording_providers', $this->recorders );
    }

    public function get_storage_providers() {
        return apply_filters( 'vh360_studio_storage_providers', $this->storage );
    }

    public function get_storage_provider( $id ) {
        $providers = $this->get_storage_providers();
        $id = sanitize_key( $id );
        return isset( $providers[ $id ] ) ? $providers[ $id ] : null;
    }

    public function has_storage_provider( $id ) {
        $providers = $this->get_storage_providers();
        return isset( $providers[ $id ] );
    }
}
