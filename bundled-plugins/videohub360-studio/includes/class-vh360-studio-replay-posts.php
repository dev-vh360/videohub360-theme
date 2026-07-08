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
        $publitio_file_id = ! empty( $publish_result['publitio_file_id'] ) ? sanitize_text_field( $publish_result['publitio_file_id'] ) : '';
        $provider_file_id = ! empty( $publish_result['provider_file_id'] ) ? sanitize_text_field( $publish_result['provider_file_id'] ) : '';
        $provider_embed_url = ! empty( $publish_result['provider_embed_url'] ) ? esc_url_raw( $publish_result['provider_embed_url'] ) : ( ! empty( $publish_result['embed_url'] ) ? esc_url_raw( $publish_result['embed_url'] ) : '' );
        $bunny_video_id = ! empty( $publish_result['bunny_video_id'] ) ? sanitize_text_field( $publish_result['bunny_video_id'] ) : $provider_file_id;
        $bunny_library_id = ! empty( $publish_result['bunny_library_id'] ) ? sanitize_text_field( $publish_result['bunny_library_id'] ) : '';
        $publitio_embed_url = ! empty( $publish_result['embed_url'] ) ? esc_url_raw( $publish_result['embed_url'] ) : '';
        $publitio_public_id = ! empty( $publish_result['public_id'] ) ? sanitize_text_field( $publish_result['public_id'] ) : '';
        $expected_public_id = ! empty( $publish_result['expected_public_id'] ) ? sanitize_text_field( $publish_result['expected_public_id'] ) : '';
        $actual_public_id = ! empty( $publish_result['actual_public_id'] ) ? sanitize_text_field( $publish_result['actual_public_id'] ) : $publitio_public_id;
        $provider_status = ! empty( $publish_result['provider_status'] ) ? sanitize_key( $publish_result['provider_status'] ) : ( ! empty( $publish_result['status'] ) ? sanitize_key( $publish_result['status'] ) : '' );
        $attachment_id = ! empty( $publish_result['attachment_id'] ) ? absint( $publish_result['attachment_id'] ) : 0;
        $provider      = sanitize_key( $job['storage_provider'] );
        if ( 'bunny_stream' === $provider ) {
            $provider_embed_url = $this->normalize_bunny_embed_url( $provider_embed_url, $bunny_library_id, $bunny_video_id );
            $publitio_embed_url = '';
        }

        update_post_meta( $post_id, 'video_url', $playback_url );
        update_post_meta( $post_id, '_vh360_is_live', $is_live_conversion ? 'yes' : 'no' );
        update_post_meta( $post_id, '_vh360_agora_stream_live', 'no' );
        update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_job_id', absint( $job['id'] ) );
        update_post_meta( $post_id, '_vh360_studio_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_storage_provider', $provider );
        update_post_meta( $post_id, '_vh360_studio_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_wp_attachment_id', $attachment_id );
        update_post_meta( $post_id, '_vh360_studio_videopress_guid', $guid );
        update_post_meta( $post_id, '_vh360_studio_publitio_file_id', $publitio_file_id );
        update_post_meta( $post_id, '_vh360_studio_provider_file_id', $provider_file_id );
        update_post_meta( $post_id, '_vh360_studio_provider_embed_url', $provider_embed_url );
        update_post_meta( $post_id, '_vh360_studio_bunny_video_id', $bunny_video_id );
        update_post_meta( $post_id, '_vh360_studio_bunny_library_id', $bunny_library_id );
        update_post_meta( $post_id, '_vh360_studio_bunny_embed_url', 'bunny_stream' === $provider ? $provider_embed_url : '' );
        update_post_meta( $post_id, '_vh360_studio_bunny_provider_status', 'bunny_stream' === $provider ? $provider_status : '' );
        update_post_meta( $post_id, '_vh360_studio_publitio_playback_url', $playback_url );
        update_post_meta( $post_id, '_vh360_studio_publitio_embed_url', $publitio_embed_url );
        update_post_meta( $post_id, '_vh360_studio_publitio_public_id', $actual_public_id );
        update_post_meta( $post_id, '_vh360_studio_publitio_expected_public_id', $expected_public_id );
        update_post_meta( $post_id, '_vh360_studio_publitio_actual_public_id', $actual_public_id );
        update_post_meta( $post_id, '_vh360_studio_publitio_public_id_matches', ! empty( $publish_result['public_id_matches'] ) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_vh360_studio_publitio_provider_status', $provider_status );
        update_post_meta( $post_id, '_vh360_studio_assembled_checksum', ! empty( $recording['assembled_checksum'] ) ? sanitize_text_field( $recording['assembled_checksum'] ) : '' );
        update_post_meta( $post_id, '_vh360_studio_replay_source_live_video_id', ! empty( $job['live_video_id'] ) ? absint( $job['live_video_id'] ) : 0 );
        update_post_meta( $post_id, '_vh360_studio_replay_published_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_vh360_studio_replay_ready', 'yes' );
        update_post_meta( $post_id, '_vh360_studio_replay_pending', 'no' );
        update_post_meta( $post_id, '_vh360_studio_replay_failed', 'no' );
        update_post_meta( $post_id, '_vh360_studio_replay_status', 'ready' );
        update_post_meta( $post_id, '_vh360_studio_converted_live_to_replay', $is_live_conversion ? 'yes' : 'no' );

        if ( $poster_url ) {
            update_post_meta( $post_id, 'poster_url', $poster_url );
            update_post_meta( $post_id, '_vh360_studio_poster_url', $poster_url );
            if ( ! has_post_thumbnail( $post_id ) && ! get_post_meta( $post_id, '_vh360_poster', true ) ) {
                update_post_meta( $post_id, '_vh360_poster', $poster_url );
            }
        }

        if ( $live_video_id && $live_video_id !== $post_id ) {
            $source_thumbnail_id = get_post_thumbnail_id( $live_video_id );
            if ( $source_thumbnail_id && ! has_post_thumbnail( $post_id ) ) {
                set_post_thumbnail( $post_id, $source_thumbnail_id );
            }
            $source_poster = get_post_meta( $live_video_id, '_vh360_poster', true );
            if ( $source_poster && ! get_post_meta( $post_id, '_vh360_poster', true ) ) {
                update_post_meta( $post_id, '_vh360_poster', esc_url_raw( $source_poster ) );
            }
        }

        $custom_html = '';
        if ( 'bunny_stream' === $provider && $provider_embed_url ) {
            $custom_html = $this->render_safe_iframe_embed( $provider_embed_url, __( 'Cloud replay', 'videohub360-studio' ) );
        } elseif ( 'publitio' === $provider && $publitio_embed_url ) {
            $custom_html = $this->render_safe_iframe_embed( $publitio_embed_url, __( 'Cloud replay', 'videohub360-studio' ) );
        } elseif ( $guid ) {
            $custom_html = $this->render_videopress_embed_html( $guid );
        }

        if ( 'local_media' === $provider ) {
            update_post_meta( $post_id, '_vh360_studio_replay_playback_type', 'direct_video' );
        } else {
            update_post_meta( $post_id, '_vh360_studio_replay_playback_type', $custom_html ? 'embed' : 'direct_video' );
        }

        if ( $custom_html ) {
            if ( 'bunny_stream' === $provider || ! $is_live_conversion ) {
                update_post_meta( $post_id, '_vh360_type', 'embed' );
            }
            update_post_meta( $post_id, 'videohub360_custom_html', $custom_html );
        } else {
            if ( ! $is_live_conversion ) {
                update_post_meta( $post_id, '_vh360_type', 'video' );
            }
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

    private function normalize_bunny_embed_url( $url, $library_id, $video_id ) {
        $url = esc_url_raw( $url );
        if ( $url && false === strpos( $url, 'b-cdn.net/embed/' ) ) { return $url; }
        $library_id = sanitize_text_field( $library_id );
        $video_id   = sanitize_text_field( $video_id );
        if ( ! $library_id || ! $video_id ) { return $url; }
        return esc_url_raw( 'https://player.mediadelivery.net/embed/' . rawurlencode( $library_id ) . '/' . rawurlencode( $video_id ) );
    }

    private function render_safe_iframe_embed( $url, $title ) {
        $url = esc_url_raw( $url );
        if ( ! $url ) {
            return '';
        }
        $html = sprintf(
            '<iframe class="vh360-studio-provider-embed" src="%s" title="%s" loading="lazy" allow="fullscreen; autoplay; encrypted-media; picture-in-picture" allowfullscreen style="width:100%%;aspect-ratio:16/9;border:0;"></iframe>',
            esc_url( $url ),
            esc_attr( $title )
        );
        return wp_kses( $html, $this->allowed_provider_iframe_html() );
    }

    private function allowed_provider_iframe_html() {
        return array(
            'iframe' => array(
                'src'             => true,
                'title'           => true,
                'loading'         => true,
                'allow'           => true,
                'allowfullscreen' => true,
                'style'           => true,
                'class'           => true,
            ),
        );
    }

    private function render_local_media_video( $playback_url, $poster_url = '', $mime_type = '' ) {
        $playback_url = esc_url_raw( $playback_url );
        $poster_url   = esc_url_raw( $poster_url );
        $mime_type    = in_array( $mime_type, array( 'video/mp4', 'video/webm' ), true ) ? $mime_type : '';
        if ( ! $playback_url ) {
            return '';
        }

        $poster_attr = $poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : '';
        $type_attr   = $mime_type ? ' type="' . esc_attr( $mime_type ) . '"' : '';
        $html        = sprintf(
            '<video class="vh360-studio-local-media-embed" controls playsinline preload="metadata"%1$s style="width:100%%;height:auto;aspect-ratio:16/9;background:#000;"><source src="%2$s"%3$s><a href="%2$s" target="_blank" rel="noopener noreferrer">%4$s</a></video>',
            $poster_attr,
            esc_url( $playback_url ),
            $type_attr,
            esc_html__( 'Open local replay file', 'videohub360-studio' )
        );

        return wp_kses( $html, $this->allowed_local_media_embed_html() );
    }

    private function allowed_local_media_embed_html() {
        return apply_filters(
            'vh360_studio_allowed_local_media_embed_html',
            array(
                'video'  => array_merge(
                    $this->global_embed_attributes(),
                    array(
                        'controls'    => true,
                        'playsinline' => true,
                        'poster'      => true,
                        'preload'     => true,
                        'style'       => true,
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
