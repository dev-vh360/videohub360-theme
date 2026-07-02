<?php
/** Provider-neutral replay publishing bridge. @package VH360_Studio */
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Replay_Publisher {
    private $registry; private $jobs; private $validator;
    public function __construct( VH360_Studio_Provider_Registry $registry, VH360_Studio_Recording_Jobs $jobs, VH360_Studio_Recording_Validator $validator ) { $this->registry=$registry; $this->jobs=$jobs; $this->validator=$validator; }
    public function prepare( array $job ) {
        if ( VH360_Studio_Recording_Jobs::STATUS_PROCESSING !== $job['status'] ) { return new WP_Error( 'vh360_studio_invalid_publish_status', __( 'Publishing can only be prepared for processing jobs.', 'videohub360-studio' ), array( 'status'=>409 ) ); }
        $providers = $this->registry->get_storage_providers(); $provider_id = sanitize_key( $job['storage_provider'] );
        if ( empty( $providers[ $provider_id ] ) ) { return new WP_Error( 'vh360_studio_invalid_storage_provider', __( 'Invalid storage provider.', 'videohub360-studio' ), array( 'status'=>400 ) ); }
        $recording = array( 'path'=>$job['local_temp_path'], 'file_size'=>absint($job['file_size']), 'mime_type'=>$job['mime_type'], 'assembled_checksum'=>$job['assembled_checksum'], 'duration_seconds'=>absint($job['duration_seconds']), 'source_type'=>$job['source_type'], 'source_id'=>$job['source_id'], 'live_video_id'=>absint($job['live_video_id']), 'room_id'=>$job['room_id'] );
        if ( empty( $recording['path'] ) || ! file_exists( $recording['path'] ) ) { return new WP_Error( 'vh360_studio_recording_missing', __( 'Validated temporary recording is no longer available.', 'videohub360-studio' ), array( 'status'=>410 ) ); }
        if ( ! empty( $recording['assembled_checksum'] ) && hash_file( 'sha256', $recording['path'] ) !== $recording['assembled_checksum'] ) { return new WP_Error( 'vh360_studio_recording_integrity_failed', __( 'Validated temporary recording checksum no longer matches.', 'videohub360-studio' ), array( 'status'=>409 ) ); }
        $prepared = $providers[ $provider_id ]->prepare_publish( $job, $recording );
        $status = is_wp_error( $prepared ) ? 'not_implemented' : 'prepared';
        $this->jobs->update( $job['id'], 0, array( 'publish_attempted_at'=>current_time('mysql'), 'publish_provider_status'=>$status ) );
        return array( 'provider_id'=>$provider_id, 'provider_label'=>$providers[$provider_id]->get_label(), 'job_status'=>$job['status'], 'file_size'=>$recording['file_size'], 'mime_type'=>$recording['mime_type'], 'assembled_checksum'=>$recording['assembled_checksum'], 'publish_provider_status'=>$status, 'message'=> is_wp_error($prepared) ? $prepared->get_error_message() : __( 'Replay publishing bridge prepared.', 'videohub360-studio' ) );
    }
}
