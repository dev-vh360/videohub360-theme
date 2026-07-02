<?php
/**
 * Recording validation service.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VH360_Studio_Recording_Validator {
    private $chunks;

    public function __construct( VH360_Studio_Recording_Chunks $chunks ) { $this->chunks = $chunks; }

    public function validate_assembled_recording( array $job, array $assembled, array $summary, $expected_chunks ) {
        $expected_chunks = absint( $expected_chunks );
        $path = isset( $assembled['path'] ) ? $assembled['path'] : '';
        if ( ! $path || ! file_exists( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_file', __( 'Assembled recording file is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! $this->chunks->is_path_in_base_directory( $path ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_path', __( 'Assembled recording path is not allowed.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        $file_size = filesize( $path );
        $max_size = (int) apply_filters( 'vh360_studio_max_total_recording_size', 512 * 1024 * 1024 );
        if ( false === $file_size || 0 >= $file_size || $file_size > $max_size ) {
            return new WP_Error( 'vh360_studio_invalid_recording_size', __( 'Assembled recording size is not allowed.', 'videohub360-studio' ), array( 'status' => 413 ) );
        }
        if ( absint( $summary['received_bytes'] ) !== absint( $file_size ) || absint( $summary['chunk_count'] ) !== $expected_chunks ) {
            return new WP_Error( 'vh360_studio_recording_summary_mismatch', __( 'Assembled recording does not match received chunks.', 'videohub360-studio' ), array( 'status' => 409 ) );
        }
        for ( $i = 0; $i < $expected_chunks; $i++ ) {
            if ( ! in_array( $i, array_map( 'absint', $summary['received_chunk_indexes'] ), true ) ) {
                return new WP_Error( 'vh360_studio_missing_chunks', __( 'Recording chunks are incomplete.', 'videohub360-studio' ), array( 'status' => 409 ) );
            }
        }
        $mime_type = $this->chunks->base_mime_type( isset( $assembled['mime_type'] ) ? $assembled['mime_type'] : $job['mime_type'] );
        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        if ( ! in_array( $mime_type, array( 'video/webm', 'video/mp4' ), true ) || ( 'video/webm' === $mime_type && 'webm' !== $ext ) || ( 'video/mp4' === $mime_type && 'mp4' !== $ext ) ) {
            return new WP_Error( 'vh360_studio_invalid_recording_type', __( 'Assembled recording type is not allowed.', 'videohub360-studio' ), array( 'status' => 415 ) );
        }
        $checksum = hash_file( 'sha256', $path );
        if ( ! $checksum ) {
            return new WP_Error( 'vh360_studio_checksum_failed', __( 'Unable to checksum assembled recording.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        return array( 'path'=>$path, 'file_size'=>absint($file_size), 'mime_type'=>$mime_type, 'assembled_checksum'=>$checksum, 'assembled_at'=>current_time('mysql'), 'temp_expires_at'=>gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) + DAY_IN_SECONDS * absint( apply_filters( 'vh360_studio_temp_recording_retention_days', 3 ) ) ), 'expected_chunks'=>$expected_chunks, 'received_chunks'=>absint($summary['chunk_count']) );
    }
}
