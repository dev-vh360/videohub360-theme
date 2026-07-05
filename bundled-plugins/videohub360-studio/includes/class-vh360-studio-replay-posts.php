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

        $post_id            = $this->resolve_replay_target_post_id( $job );
        $live_video_id      = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $is_live_conversion = $live_video_id && $post_id === $live_video_id;

        if ( $live_video_id && ! $post_id ) {
            return new WP_Error( 'vh360_studio_invalid_live_replay_target', __( 'The original livestream post is not available.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        if ( $is_live_conversion ) {
            $validated = $this->validate_live_replay_target( $post_id, $job );
            if ( is_wp_error( $validated ) ) {
                return $validated;
            }
        } elseif ( $post_id ) {
            $existing = get_post( $post_id );
            if ( ! $existing || 'videohub360' !== $existing->post_type ) {
                return new WP_Error( 'vh360_studio_invalid_replay_post', __( 'The selected replay post is not available.', 'videohub360-studio' ), array( 'status' => 404 ) );
            }

            if ( '' === trim( (string) $existing->post_title ) ) {
                $updated = wp_update_post( wp_slash( array( 'ID' => $post_id, 'post_title' => $this->replay_title( $job ) ) ), true );
                if ( is_wp_error( $updated ) ) {
                    return $updated;
                }
            }
        } else {
            $post_status = apply_filters( 'vh360_studio_replay_post_status', 'draft', $job, $publish_result );
            $post_data = array(
                'post_type'    => 'videohub360',
                'post_status'  => sanitize_key( $post_status ? $post_status : 'draft' ),
                'post_author'  => absint( $job['user_id'] ),
                'post_title'   => $this->replay_title( $job ),
                'post_content' => '',
            );

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
        update_post_meta( $post_id, '_vh360_agora_stream_live', 'no' );
        update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        update_post_meta( $post_id, '_vh360_studio_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_storage_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_wp_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_videopress_guid', $guid );
        update_post_meta( $post_id, '_vh360_studio_assembled_checksum', ! empty( $recording['assembled_checksum'] ) ? sanitize_text_field( $recording['assembled_checksum'] ) : '' );
        update_post_meta( $post_id, '_vh360_studio_replay_source_live_video_id', ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0 );
        update_post_meta( $post_id, '_vh360_studio_replay_published_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_vh360_studio_replay_ready', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_converted_live_to_replay', $is_live_conversion ? 'yes' : 'no' );

        if ( $poster_url ) {
            update_post_meta( $post_id, 'poster_url', $poster_url );
            update_post_meta( $post_id, '_vh360_studio_poster_url', $poster_url );
        }

        $custom_html = '';
        if ( 'local_media' === $provider && 'video/webm' === $this->recording_mime_type( $recording ) ) {
            $custom_html = $this->render_local_media_iframe( $playback_url );
        } elseif ( $guid ) {
            $custom_html = $this->render_videopress_embed_html( $guid );
        }

        if ( $custom_html ) {
            update_post_meta( $post_id, '_vh360_type', 'embed' );
            update_post_meta( $post_id, 'videohub360_custom_html', $custom_html );
        } else {
            update_post_meta( $post_id, '_vh360_type', 'video' );
            delete_post_meta( $post_id, 'videohub360_custom_html' );
        }

        if ( ! $is_live_conversion ) {
            $this->copy_source_taxonomies( $post_id, $live_video_id );
        }

        return array(
            'replay_video_id' => absint( $post_id ),
            'replay_url'      => get_permalink( $post_id ),
        );
    }

    private function resolve_replay_target_post_id( array $job ) {
        $live_video_id   = ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0;
        $replay_video_id = ! empty( $job['replay_video_id'] ) ? absint( $job['replay_video_id'] ) : 0;

        if ( $live_video_id && 'videohub360' === get_post_type( $live_video_id ) ) {
            return $live_video_id;
        }

        if ( $replay_video_id && 'videohub360' === get_post_type( $replay_video_id ) ) {
            return $replay_video_id;
        }

        return 0;
    }

    private function validate_live_replay_target( $post_id, array $job ) {
        $post = get_post( $post_id );
        if ( ! $post || 'videohub360' !== $post->post_type ) {
            return new WP_Error( 'vh360_studio_invalid_live_replay_target', __( 'The original livestream post is not available.', 'videohub360-studio' ), array( 'status' => 404 ) );
        }

        $job_user_id = ! empty( $job['user_id'] ) ? absint( $job['user_id'] ) : 0;
        if ( $job_user_id && absint( $post->post_author ) === $job_user_id ) {
            return true;
        }

        if ( current_user_can( 'edit_post', $post_id ) ) {
            return true;
        }

        return new WP_Error( 'vh360_studio_live_replay_target_forbidden', __( 'You are not allowed to update the original livestream post.', 'videohub360-studio' ), array( 'status' => 403 ) );
    }

    private function render_local_media_iframe( $playback_url ) {
        $playback_url = esc_url_raw( $playback_url );
        if ( ! $playback_url ) {
            return '';
        }

        $iframe = sprintf(
            '<iframe class="vh360-studio-local-media-embed" src="%s" title="%s" loading="lazy" allow="fullscreen" allowfullscreen style="width:100%%;aspect-ratio:16/9;border:0;"></iframe>',
            esc_url( $playback_url ),
            esc_attr__( 'Local Media replay', 'videohub360-studio' )
        );

        return wp_kses( $iframe, $this->allowed_local_media_embed_html() );
    }

    private function allowed_local_media_embed_html() {
        return apply_filters(
            'vh360_studio_allowed_local_media_embed_html',
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
            )
        );
    }

    private function recording_mime_type( array $recording ) {
        if ( empty( $recording['mime_type'] ) ) {
            return '';
        }

        $parts = explode( ';', (string) $recording['mime_type'] );
        return strtolower( sanitize_mime_type( trim( $parts[0] ) ) );
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
