<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Recording_Jobs {
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_RECORDING = 'recording';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    private $registry;
    public function __construct( VH360_Studio_Provider_Registry $registry ) { $this->registry = $registry; }
    public function allowed_statuses() { return array( self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_RECORDING, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED ); }
    public function validate_payload( $data, $partial = false ) {
        $out = array();
        if ( isset( $data['room_id'] ) ) { $out['room_id'] = sanitize_text_field( wp_unslash( $data['room_id'] ) ); } elseif ( ! $partial ) { $out['room_id'] = ''; }
        if ( isset( $data['video_id'] ) ) { $out['video_id'] = absint( $data['video_id'] ); }
        if ( isset( $data['status'] ) && in_array( sanitize_key( $data['status'] ), $this->allowed_statuses(), true ) ) { $out['status'] = sanitize_key( $data['status'] ); }
        foreach ( array( 'live_provider', 'recording_provider', 'storage_provider', 'quality_preset' ) as $key ) {
            if ( isset( $data[ $key ] ) ) { $out[ $key ] = sanitize_key( $data[ $key ] ); }
        }
        foreach ( array( 'external_id', 'source_url', 'replay_url' ) as $key ) {
            if ( isset( $data[ $key ] ) ) { $out[ $key ] = 'external_id' === $key ? sanitize_text_field( wp_unslash( $data[ $key ] ) ) : esc_url_raw( wp_unslash( $data[ $key ] ) ); }
        }
        if ( isset( $data['metadata'] ) ) { $out['metadata'] = wp_json_encode( $this->sanitize_metadata( $data['metadata'] ) ); }
        foreach ( array( 'scheduled_at', 'started_at', 'completed_at', 'cancelled_at' ) as $key ) {
            if ( isset( $data[ $key ] ) ) { $out[ $key ] = sanitize_text_field( wp_unslash( $data[ $key ] ) ); }
        }
        if ( isset( $out['recording_provider'] ) && ! $this->registry->has_recording_provider( $out['recording_provider'] ) ) { return new WP_Error( 'vh360_studio_invalid_recording_provider', __( 'Invalid recording provider.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        if ( isset( $out['storage_provider'] ) && ! $this->registry->has_storage_provider( $out['storage_provider'] ) ) { return new WP_Error( 'vh360_studio_invalid_storage_provider', __( 'Invalid storage provider.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        return $out;
    }
    private function sanitize_metadata( $value ) {
        if ( is_array( $value ) ) { return array_map( array( $this, 'sanitize_metadata' ), $value ); }
        return is_scalar( $value ) ? sanitize_text_field( wp_unslash( (string) $value ) ) : '';
    }
    public function create( $user_id, $data ) {
        global $wpdb;
        $data = $this->validate_payload( $data );
        if ( is_wp_error( $data ) ) { return $data; }
        $now = current_time( 'mysql' );
        $row = wp_parse_args( $data, array( 'user_id' => absint( $user_id ), 'status' => self::STATUS_DRAFT, 'live_provider' => 'agora_browser', 'recording_provider' => 'browser_recording', 'storage_provider' => 'local_media', 'quality_preset' => 'standard', 'created_at' => $now, 'updated_at' => $now ) );
        $wpdb->insert( VH360_Studio_Database::table_name(), $row );
        return $wpdb->insert_id ? $this->get( $wpdb->insert_id, $user_id ) : new WP_Error( 'vh360_studio_create_failed', __( 'Unable to create recording job.', 'videohub360-studio' ), array( 'status' => 500 ) );
    }
    public function get( $id, $user_id = 0 ) {
        global $wpdb;
        $sql = 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' WHERE id = %d';
        $args = array( absint( $id ) );
        if ( $user_id && ! current_user_can( 'manage_options' ) ) { $sql .= ' AND user_id = %d'; $args[] = absint( $user_id ); }
        return $wpdb->get_row( $wpdb->prepare( $sql, $args ), ARRAY_A );
    }
    public function list( $user_id, $limit = 20 ) {
        global $wpdb;
        $limit = min( 100, max( 1, absint( $limit ) ) );
        if ( current_user_can( 'manage_options' ) ) { return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' ORDER BY created_at DESC LIMIT %d', $limit ), ARRAY_A ); }
        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT %d', absint( $user_id ), $limit ), ARRAY_A );
    }
    public function update( $id, $user_id, $data ) {
        global $wpdb;
        if ( ! $this->get( $id, $user_id ) ) { return new WP_Error( 'vh360_studio_not_found', __( 'Recording job not found.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        $data = $this->validate_payload( $data, true );
        if ( is_wp_error( $data ) ) { return $data; }
        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->update( VH360_Studio_Database::table_name(), $data, array( 'id' => absint( $id ) ) );
        return $this->get( $id, $user_id );
    }
    public function cancel( $id, $user_id ) { return $this->update( $id, $user_id, array( 'status' => self::STATUS_CANCELLED, 'cancelled_at' => current_time( 'mysql' ) ) ); }
}
