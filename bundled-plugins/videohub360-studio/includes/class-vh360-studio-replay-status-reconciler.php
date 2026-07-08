<?php
/**
 * Server-side replay status reconciliation for cloud providers.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Replay_Status_Reconciler {
    const HOOK = 'vh360_studio_reconcile_replay_statuses';

    private $jobs;
    private $publisher;

    public function __construct( VH360_Studio_Recording_Jobs $jobs, VH360_Studio_Replay_Publisher $publisher ) {
        $this->jobs      = $jobs;
        $this->publisher = $publisher;
    }

    public static function add_interval( $schedules ) {
        if ( ! isset( $schedules['vh360_studio_ten_minutes'] ) ) {
            $schedules['vh360_studio_ten_minutes'] = array(
                'interval' => 10 * MINUTE_IN_SECONDS,
                'display'  => __( 'Every 10 minutes', 'videohub360-studio' ),
            );
        }

        return $schedules;
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'vh360_studio_ten_minutes', self::HOOK );
        }
    }

    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }

    public function run() {
        $jobs = $this->jobs->list_pending_provider_replay_jobs(
            (int) apply_filters( 'vh360_studio_replay_status_reconcile_limit', 10 ),
            (int) apply_filters( 'vh360_studio_replay_status_reconcile_hours', 24 )
        );

        foreach ( $jobs as $job ) {
            $this->reconcile_job( $job );
        }
    }

    private function reconcile_job( array $job ) {
        $status = $this->publisher->status( $job );
        if ( is_wp_error( $status ) ) {
            return;
        }

        if ( $this->response_is_ready( $status ) ) {
            $this->update_live_replay_lifecycle( $job, 'ready', 'no', 'yes', 'no' );
            return;
        }

        if ( $this->response_is_failed( $status ) ) {
            $message = ! empty( $status['message'] ) ? sanitize_textarea_field( $status['message'] ) : __( 'Replay provider processing failed.', 'videohub360-studio' );
            $failed = $this->jobs->mark_failed( $job['id'], 0, $message );
            $this->update_live_replay_lifecycle( is_wp_error( $failed ) ? $job : $failed, 'failed', 'no', 'no', 'yes' );
        }
    }

    private function response_is_ready( array $response ) {
        foreach ( array( 'job_status', 'status', 'publish_provider_status', 'provider_status', 'publish_status' ) as $key ) {
            if ( ! empty( $response[ $key ] ) && in_array( sanitize_key( (string) $response[ $key ] ), array( 'ready', 'bunny_stream_ready', 'publitio_ready', 'publitio_direct_ready', 'published' ), true ) ) {
                return true;
            }
        }

        return ! empty( $response['replay_video_id'] ) || ! empty( $response['replay_url'] );
    }

    private function response_is_failed( array $response ) {
        foreach ( array( 'publish_provider_status', 'provider_status', 'publish_status', 'status' ) as $key ) {
            if ( ! empty( $response[ $key ] ) && in_array( sanitize_key( (string) $response[ $key ] ), array( 'bunny_stream_failed', 'publitio_failed', 'upload_failed', 'failed', 'error' ), true ) ) {
                return true;
            }
        }

        return false;
    }

    private function update_live_replay_lifecycle( array $job, $status, $pending, $ready, $failed ) {
        $live_video_id = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        if ( ! $live_video_id || 'videohub360' !== get_post_type( $live_video_id ) ) {
            return;
        }

        if ( ! empty( $job['id'] ) ) {
            update_post_meta( $live_video_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        }
        update_post_meta( $live_video_id, '_vh360_studio_replay_pending', $pending );
        update_post_meta( $live_video_id, '_vh360_studio_replay_ready', $ready );
        update_post_meta( $live_video_id, '_vh360_studio_replay_failed', $failed );
        update_post_meta( $live_video_id, '_vh360_studio_replay_status', sanitize_key( $status ) );
    }
}
