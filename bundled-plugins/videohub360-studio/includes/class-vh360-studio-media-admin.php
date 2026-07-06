<?php
/**
 * Studio Media Library admin thumbnail integration.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Media_Admin {
    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), 10, 3 );
        add_filter( 'wp_get_attachment_image_src', array( $this, 'attachment_image_src' ), 10, 4 );
    }

    public function prepare_attachment_for_js( $response, $attachment, $meta ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $attachment_id = $this->attachment_id_from_value( $attachment );
        if ( ! $this->is_studio_replay_video_attachment( $attachment_id ) ) {
            return $response;
        }

        $poster = $this->studio_poster_image( $attachment_id, 'thumbnail' );
        if ( empty( $poster['url'] ) ) {
            return $response;
        }

        $response['icon']  = $poster['url'];
        $response['image'] = $poster;
        $response['thumb'] = $poster['url'];

        if ( empty( $response['sizes'] ) || ! is_array( $response['sizes'] ) ) {
            $response['sizes'] = array();
        }
        $response['sizes']['thumbnail'] = $poster;
        $response['vh360StudioPoster'] = true;

        return $response;
    }

    public function attachment_image_src( $image, $attachment_id, $size, $icon ) {
        if ( ! is_admin() || ! $icon || ! $this->is_studio_replay_video_attachment( $attachment_id ) ) {
            return $image;
        }

        $poster = $this->studio_poster_image( $attachment_id, $size );
        if ( empty( $poster['url'] ) ) {
            return $image;
        }

        return array( $poster['url'], $poster['width'], $poster['height'], false );
    }

    private function is_studio_replay_video_attachment( $attachment_id ) {
        $attachment_id = absint( $attachment_id );
        if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
            return false;
        }

        if ( 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'video/' ) ) {
            return false;
        }

        return (bool) ( get_post_meta( $attachment_id, '_vh360_studio_poster_attachment_id', true ) || get_post_meta( $attachment_id, '_vh360_studio_poster_url', true ) || get_post_meta( $attachment_id, '_vh360_poster', true ) );
    }

    private function studio_poster_image( $attachment_id, $size = 'thumbnail' ) {
        $attachment_id = absint( $attachment_id );
        $fallback_size = is_array( $size ) ? 'thumbnail' : $size;
        $poster_id = absint( get_post_meta( $attachment_id, '_vh360_studio_poster_attachment_id', true ) );

        if ( $poster_id && 'attachment' === get_post_type( $poster_id ) && 0 === strpos( (string) get_post_mime_type( $poster_id ), 'image/' ) ) {
            $src = wp_get_attachment_image_src( $poster_id, $size );
            if ( ! $src ) {
                $src = wp_get_attachment_image_src( $poster_id, $fallback_size );
            }
            if ( $src ) {
                return array(
                    'url'         => esc_url_raw( $src[0] ),
                    'width'       => absint( $src[1] ),
                    'height'      => absint( $src[2] ),
                    'orientation' => absint( $src[2] ) > absint( $src[1] ) ? 'portrait' : 'landscape',
                );
            }
        }

        foreach ( array( '_vh360_studio_poster_url', '_vh360_poster' ) as $meta_key ) {
            $poster_url = get_post_meta( $attachment_id, $meta_key, true );
            if ( $poster_url && $this->is_safe_poster_url( $poster_url ) ) {
                return array(
                    'url'         => esc_url_raw( $poster_url ),
                    'width'       => 150,
                    'height'      => 150,
                    'orientation' => 'landscape',
                );
            }
        }

        return array();
    }

    private function is_safe_poster_url( $url ) {
        $url = esc_url_raw( $url );
        return $url && ( 0 === strpos( $url, 'http://' ) || 0 === strpos( $url, 'https://' ) || 0 === strpos( $url, '/' ) );
    }

    private function attachment_id_from_value( $attachment ) {
        if ( $attachment instanceof WP_Post ) {
            return absint( $attachment->ID );
        }

        if ( is_numeric( $attachment ) ) {
            return absint( $attachment );
        }

        return 0;
    }
}
