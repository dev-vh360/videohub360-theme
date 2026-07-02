<?php
/** Safe temporary recording cleanup. @package VH360_Studio */
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Recording_Cleanup {
    const HOOK = 'vh360_studio_cleanup_recordings';
    private $jobs; private $chunks;
    public function __construct( VH360_Studio_Recording_Jobs $jobs ) { $this->jobs = $jobs; $this->chunks = new VH360_Studio_Recording_Chunks( $jobs ); }
    public static function schedule() { if ( ! wp_next_scheduled( self::HOOK ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::HOOK ); } }
    public static function unschedule() { $t = wp_next_scheduled( self::HOOK ); if ( $t ) { wp_unschedule_event( $t, self::HOOK ); } }
    public function run() {
        global $wpdb; $now = current_time( 'timestamp' );
        $rows = $wpdb->get_results( 'SELECT * FROM ' . VH360_Studio_Database::table_name() . " WHERE status IN ('created','recording','stopping','uploading','cancelled','failed','processing') LIMIT 200", ARRAY_A );
        foreach ( $rows as $job ) {
            $created = strtotime( $job['created_at'] . ' UTC' );
            if ( 'created' === $job['status'] && $created < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_abandoned_job_retention_hours', 24 ) ) ) { $this->jobs->update( $job['id'], 0, array( 'status'=>'cancelled' ) ); $this->delete_temp_for_job( $job ); continue; }
            if ( in_array( $job['status'], array( 'recording', 'stopping', 'uploading' ), true ) && $created < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_abandoned_job_retention_hours', 24 ) ) ) { $this->jobs->mark_failed( $job['id'], 0, __( 'Recording abandoned before finalization.', 'videohub360-studio' ) ); $this->delete_temp_for_job( $job ); continue; }
            if ( 'cancelled' === $job['status'] && $created < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_cancelled_job_retention_hours', 6 ) ) ) { $this->delete_temp_for_job( $job ); }
            if ( 'failed' === $job['status'] && $created < $now - DAY_IN_SECONDS * absint( apply_filters( 'vh360_studio_failed_job_retention_days', 7 ) ) ) { $this->delete_temp_for_job( $job ); }
            if ( 'processing' === $job['status'] && ! empty( $job['temp_expires_at'] ) && strtotime( $job['temp_expires_at'] . ' UTC' ) < $now ) { $this->delete_temp_for_job( $job ); }
        }
    }
    public function delete_temp_for_job( array $job ) { $this->chunks->delete_job_chunks( $job['id'] ); }
}
