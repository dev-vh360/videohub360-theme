<?php
/**
 * Videohub360 replay post creation service.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Replay_Posts {
    public function create_or_update( array $job, array $publish_result, array $recording ) {
        if ( ! post_type_exists( 'videohub360' ) ) {
            return new WP_Error( 'vh360_studio_replay_post_type_missing', __( 'The VideoHub360 post type is not available.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $post_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;
        $post_data = array(
            'post_type'    => 'videohub360',
            'post_status'  => 'publish',
            'post_title'   => $this->replay_title( $job ),
            'post_content' => '',
        );

        if ( $post_id && 'videohub360' === get_post_type( $post_id ) ) {
            $post_data['ID'] = $post_id;
            $updated = wp_update_post( wp_slash( $post_data ), true );
            if ( is_wp_error( $updated ) ) {
                return $updated;
            }
        } else {
            $post_id = wp_insert_post( wp_slash( $post_data ), true );
            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
        }

        $playback_url = ! empty( $publish_result['playback_url'] ) ? esc_url_raw( $publish_result['playback_url'] ) : '';
        $guid         = ! empty( $publish_result['videopress_guid'] ) ? sanitize_text_field( $publish_result['videopress_guid'] ) : '';
        $attachment_id = ! empty( $publish_result['attachment_id'] ) ? absint( $publish_result['attachment_id'] ) : 0;

        update_post_meta( $post_id, 'video_url', $playback_url );
        update_post_meta( $post_id, '_vh360_is_live', 'no' );
        update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        update_post_meta( $post_id, '_vh360_studio_provider', sanitize_key( $job['storage_provider'] ) );
        update_post_meta( $post_id, '_vh360_studio_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_videopress_guid', $guid );
        update_post_meta( $post_id, '_vh360_studio_assembled_checksum', sanitize_text_field( $recording['assembled_checksum'] ) );

        if ( $guid && preg_match( '/^[A-Za-z0-9_-]{8,}$/', $guid ) ) {
            update_post_meta( $post_id, 'videohub360_custom_html', '[videopress ' . $guid . ']' );
        } else {
            delete_post_meta( $post_id, 'videohub360_custom_html' );
        }

        return array(
            'replay_video_id' => absint( $post_id ),
            'replay_url'      => get_permalink( $post_id ),
        );
    }

    private function replay_title( array $job ) {
        if ( ! empty( $job['room_id'] ) ) {
            return sprintf( __( 'Studio Replay: %s', 'videohub360-studio' ), sanitize_text_field( $job['room_id'] ) );
        }
        return sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }
}
