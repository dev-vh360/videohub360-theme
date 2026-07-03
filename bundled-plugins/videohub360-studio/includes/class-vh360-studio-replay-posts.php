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
    public function create_or_update_replay_post( array $job, array $publish_result, array $recording ) {
        return $this->create_or_update( $job, $publish_result, $recording );
    }

    public function create_or_update( array $job, array $publish_result, array $recording ) {
        if ( ! post_type_exists( 'videohub360' ) ) {
            return new WP_Error( 'vh360_studio_replay_post_type_missing', __( 'The VideoHub360 post type is not available.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $post_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;
        $post_status = apply_filters( 'vh360_studio_replay_post_status', 'draft', $job, $publish_result );
        $post_data = array(
            'post_type'    => 'videohub360',
            'post_status'  => sanitize_key( $post_status ? $post_status : 'draft' ),
            'post_author'  => absint( $job['user_id'] ),
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

        $playback_url  = ! empty( $publish_result['playback_url'] ) ? esc_url_raw( $publish_result['playback_url'] ) : '';
        $poster_url    = ! empty( $publish_result['poster_url'] ) ? esc_url_raw( $publish_result['poster_url'] ) : '';
        $guid          = ! empty( $publish_result['videopress_guid'] ) ? sanitize_text_field( $publish_result['videopress_guid'] ) : '';
        $attachment_id = ! empty( $publish_result['attachment_id'] ) ? absint( $publish_result['attachment_id'] ) : 0;
        $provider      = sanitize_key( $job['storage_provider'] );

        update_post_meta( $post_id, 'video_url', $playback_url );
        update_post_meta( $post_id, '_vh360_is_live', 'no' );
        update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
        update_post_meta( $post_id, '_vh360_type', $guid ? 'embed' : 'video' );
        update_post_meta( $post_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        update_post_meta( $post_id, '_vh360_studio_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_storage_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_wp_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_videopress_guid', $guid );
        update_post_meta( $post_id, '_vh360_studio_assembled_checksum', ! empty( $recording['assembled_checksum'] ) ? sanitize_text_field( $recording['assembled_checksum'] ) : '' );
        update_post_meta( $post_id, '_vh360_studio_replay_source_live_video_id', ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0 );
        update_post_meta( $post_id, '_vh360_studio_replay_created_at', current_time( 'mysql' ) );

        if ( $poster_url ) {
            update_post_meta( $post_id, 'poster_url', $poster_url );
            update_post_meta( $post_id, '_vh360_studio_poster_url', $poster_url );
        }

        $embed_html = $this->render_videopress_embed_html( $guid );
        if ( $embed_html ) {
            update_post_meta( $post_id, 'videohub360_custom_html', $embed_html );
        } else {
            delete_post_meta( $post_id, 'videohub360_custom_html' );
        }

        $this->copy_source_taxonomies( $post_id, ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0 );

        return array(
            'replay_video_id' => absint( $post_id ),
            'replay_url'      => get_permalink( $post_id ),
        );
    }

    private function render_videopress_embed_html( $guid ) {
        if ( ! $guid || ! preg_match( '/^[A-Za-z0-9_-]{8,}$/', $guid ) || ! function_exists( 'do_shortcode' ) ) {
            return '';
        }

        $shortcode = '[videopress ' . $guid . ']';
        $rendered  = do_shortcode( $shortcode );
        if ( ! is_string( $rendered ) || '' === trim( $rendered ) || trim( $rendered ) === $shortcode ) {
            return '';
        }

        $allowed_embed_html = apply_filters(
            'vh360_studio_allowed_videopress_embed_html',
            array(
                'iframe' => array(
                    'src'             => true,
                    'width'           => true,
                    'height'          => true,
                    'frameborder'     => true,
                    'allow'           => true,
                    'allowfullscreen' => true,
                    'title'           => true,
                    'loading'         => true,
                    'referrerpolicy'  => true,
                    'style'           => true,
                    'class'           => true,
                ),
                'div'    => $this->global_embed_attributes(),
                'video'  => array_merge(
                    $this->global_embed_attributes(),
                    array(
                        'src'         => true,
                        'controls'    => true,
                        'playsinline' => true,
                        'poster'      => true,
                        'preload'     => true,
                        'width'       => true,
                        'height'      => true,
                    )
                ),
                'source' => array(
                    'src'  => true,
                    'type' => true,
                ),
                'a'      => array(
                    'href'   => true,
                    'target' => true,
                    'rel'    => true,
                    'class'  => true,
                ),
            ),
            $guid,
            $rendered
        );

        return wp_kses( $rendered, $allowed_embed_html );
    }

    private function global_embed_attributes() {
        return array(
            'class' => true,
            'id'    => true,
            'style' => true,
            'data-*' => true,
        );
    }

    private function copy_source_taxonomies( $replay_post_id, $source_post_id ) {
        if ( ! $source_post_id || 'videohub360' !== get_post_type( $source_post_id ) ) {
            return;
        }

        foreach ( get_object_taxonomies( 'videohub360' ) as $taxonomy ) {
            $terms = wp_get_object_terms( $source_post_id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }
            wp_set_object_terms( $replay_post_id, array_map( 'absint', $terms ), $taxonomy, false );
        }
    }

    private function replay_title( array $job ) {
        if ( ! empty( $job['live_video_id'] ) ) {
            $source_title = get_the_title( absint( $job['live_video_id'] ) );
            if ( $source_title ) {
                return sprintf( __( 'Replay: %s', 'videohub360-studio' ), sanitize_text_field( $source_title ) );
            }
        }

        if ( ! empty( $job['room_id'] ) ) {
            return sprintf( __( 'Studio Replay: %s', 'videohub360-studio' ), sanitize_text_field( $job['room_id'] ) );
        }
        return sprintf( __( 'Studio Replay #%d', 'videohub360-studio' ), absint( $job['id'] ) );
    }
}
