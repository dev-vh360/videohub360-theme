<?php
/**
 * Safe temporary recording cleanup.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Recording_Cleanup {
    const HOOK = 'vh360_studio_cleanup_recordings';

    private $jobs;
    private $chunks;

    public function __construct( VH360_Studio_Recording_Jobs $jobs ) {
        $this->jobs   = $jobs;
        $this->chunks = new VH360_Studio_Recording_Chunks( $jobs );
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
        }
    }

    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }

    public function run() {
        global $wpdb;

        $now  = current_time( 'timestamp' );
        $rows = $wpdb->get_results( 'SELECT j.* FROM ' . VH360_Studio_Database::table_name() . ' j WHERE j.status IN (\'created\',\'recording\',\'stopping\',\'uploading\',\'preparing_download\',\'processing\') OR (j.status IN (\'cancelled\',\'failed\',\'ready\') AND ((j.local_temp_path IS NOT NULL AND j.local_temp_path != \'\') OR EXISTS (SELECT 1 FROM ' . VH360_Studio_Database::chunks_table_name() . ' c WHERE c.job_id = j.id LIMIT 1))) ORDER BY CASE WHEN j.status IN (\'failed\',\'cancelled\',\'ready\') THEN 1 ELSE 0 END ASC, j.updated_at ASC LIMIT 200', ARRAY_A );

        foreach ( $rows as $job ) {
            $created = $this->mysql_timestamp( $job['created_at'] );
            $updated = $this->mysql_timestamp( $job['updated_at'] );

            if ( 'created' === $job['status'] && $created < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_abandoned_job_retention_hours', 24 ) ) ) {
                $cancelled = $this->jobs->update( $job['id'], 0, array( 'status' => 'cancelled' ) );
                $this->sync_abandoned_room_metadata( is_wp_error( $cancelled ) ? $job : $cancelled, 'cancelled' );
                $this->delete_temp_for_job( $job );
                continue;
            }

            if ( in_array( $job['status'], array( 'recording', 'stopping', 'uploading', 'preparing_download' ), true ) && $updated < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_abandoned_job_retention_hours', 24 ) ) ) {
                $failed = $this->jobs->mark_failed( $job['id'], 0, __( 'Recording abandoned before finalization.', 'videohub360-studio' ) );
                $this->sync_abandoned_room_metadata( is_wp_error( $failed ) ? $job : $failed, 'failed' );
                $this->delete_temp_for_job( $job );
                continue;
            }

            if ( 'cancelled' === $job['status'] && $updated < $now - HOUR_IN_SECONDS * absint( apply_filters( 'vh360_studio_cancelled_job_retention_hours', 6 ) ) ) {
                $this->delete_temp_for_job( $job );
            }

            if ( 'failed' === $job['status'] && $updated < $now - DAY_IN_SECONDS * absint( apply_filters( 'vh360_studio_failed_job_retention_days', 7 ) ) ) {
                $this->delete_temp_for_job( $job );
            }

            if ( in_array( $job['status'], array( 'processing', 'ready' ), true ) ) {
                if ( $this->job_has_safe_handoff( $job ) ) {
                    $this->delete_temp_for_job( $job );
                    continue;
                }

                if ( 'processing' === $job['status'] && ! empty( $job['temp_expires_at'] ) && $this->mysql_timestamp( $job['temp_expires_at'] ) < $now ) {
                    $failed = $this->jobs->update(
                        $job['id'],
                        0,
                        array(
                            'status'                  => VH360_Studio_Recording_Jobs::STATUS_FAILED,
                            'publish_provider_status' => 'expired',
                            'error_message'           => __( 'Temporary recording expired before replay publishing completed.', 'videohub360-studio' ),
                        )
                    );
                    $this->sync_abandoned_room_metadata( is_wp_error( $failed ) ? $job : $failed, 'failed' );
                    $this->delete_temp_for_job( $job );
                }
            }
        }
    }

    private function sync_abandoned_room_metadata( array $job, $status ) {
        $post_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        if ( ! $post_id ) { return; }
        if ( 'appointment_session' === sanitize_key( $job['source_type'] ) ) {
            delete_post_meta( $post_id, '_vh360_appointment_recording_state' );
            delete_post_meta( $post_id, '_vh360_appointment_recording_started_at' );
            delete_post_meta( $post_id, '_vh360_appointment_recording_user_id' );
            return;
        }
        if ( 'live_room' === sanitize_key( $job['source_type'] ) ) {
            update_post_meta( $post_id, '_vh360_live_room_recording_state', sanitize_key( $status ) );
            update_post_meta( $post_id, '_vh360_studio_replay_pending', 'no' );
            update_post_meta( $post_id, '_vh360_studio_replay_failed', 'failed' === $status ? 'yes' : 'no' );
            update_post_meta( $post_id, '_vh360_studio_replay_status', sanitize_key( $status ) );
        }
    }

    public function delete_temp_for_job( array $job ) {
        delete_option( 'vh360_recording_heartbeat_' . absint( $job['id'] ) );
        $this->chunks->delete_job_chunks( $job['id'] );
        if ( ! empty( $job['local_temp_path'] ) ) {
            $this->jobs->update( $job['id'], 0, array( 'local_temp_path' => '', 'temp_expires_at' => '' ) );
        }
    }

    private function job_has_safe_handoff( array $job ) {
        $provider_status  = ! empty( $job['publish_provider_status'] ) ? sanitize_key( $job['publish_provider_status'] ) : '';
        $storage_provider = ! empty( $job['storage_provider'] ) ? sanitize_key( $job['storage_provider'] ) : '';
        $wp_attachment_id = ! empty( $job['wp_attachment_id'] ) ? absint( $job['wp_attachment_id'] ) : 0;
        $videopress_guid  = ! empty( $job['videopress_guid'] ) ? sanitize_text_field( $job['videopress_guid'] ) : '';
        $publitio_file_id = ! empty( $job['publitio_file_id'] ) ? sanitize_text_field( $job['publitio_file_id'] ) : '';
        $replay_video_id  = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;

        if ( 'videopress' === $storage_provider ) {
            return ( $wp_attachment_id && $videopress_guid ) || ( $wp_attachment_id && in_array( $provider_status, array( 'media_attached_waiting_videopress', 'published', 'ready' ), true ) );
        }

        if ( 'publitio' === $storage_provider ) {
            return $publitio_file_id && in_array( $provider_status, array( 'publitio_processing', 'publitio_ready', 'publitio_direct_processing', 'publitio_direct_ready', 'published', 'ready' ), true );
        }

        if ( 'local_media' === $storage_provider ) {
            return $wp_attachment_id && in_array( $provider_status, array( 'local_media_ready', 'published', 'ready' ), true );
        }

        return ( $wp_attachment_id && in_array( $provider_status, array( 'media_attached_waiting_videopress', 'local_media_ready', 'published', 'ready' ), true ) )
            || ( $publitio_file_id && in_array( $provider_status, array( 'publitio_processing', 'publitio_ready', 'publitio_direct_processing', 'publitio_direct_ready', 'published', 'ready' ), true ) )
            || ( $replay_video_id && in_array( $provider_status, array( 'published', 'ready' ), true ) );
    }

    private function mysql_timestamp( $value ) {
        $timestamp = strtotime( $value . ' UTC' );
        return $timestamp ? $timestamp : 0;
    }
}
