<?php
/**
 * Internal Bunny Stream API client.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Bunny_Stream_Client {
    const API_BASE = 'https://video.bunnycdn.com';

    private $library_id;
    private $api_key;

    public function __construct( $library_id = '', $api_key = '' ) {
        $this->library_id = sanitize_text_field( $library_id ? $library_id : get_option( 'vh360_studio_bunny_stream_library_id', '' ) );
        $this->api_key    = sanitize_text_field( $api_key ? $api_key : get_option( 'vh360_studio_bunny_stream_api_key', '' ) );
    }

    public function has_credentials() {
        return '' !== $this->library_id && '' !== $this->api_key;
    }

    public function create_video( $title, array $args = array() ) {
        $body = array( 'title' => sanitize_text_field( $title ) );
        $collection = ! empty( $args['collectionId'] ) ? sanitize_text_field( $args['collectionId'] ) : sanitize_text_field( get_option( 'vh360_studio_bunny_stream_collection_id', '' ) );
        if ( $collection ) {
            $body['collectionId'] = $collection;
        }
        $thumbnail_time = isset( $args['thumbnailTime'] ) ? absint( $args['thumbnailTime'] ) : absint( get_option( 'vh360_studio_bunny_stream_thumbnail_time', 0 ) );
        if ( $thumbnail_time ) {
            $body['thumbnailTime'] = $thumbnail_time;
        }
        return $this->request( 'POST', '/library/' . rawurlencode( $this->library_id ) . '/videos', $body );
    }

    public function upload_video( $video_id, $path, $mime_type ) {
        $video_id = sanitize_text_field( $video_id );
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'vh360_studio_bunny_stream_credentials_missing', __( 'Cloud replay credentials are required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( '' === $video_id || ! file_exists( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_missing_file', __( 'Cloud upload file is unavailable.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        if ( ! function_exists( 'curl_init' ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_curl_missing', __( 'Cloud upload failed before reaching the replay storage service.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        $handle = fopen( $path, 'rb' );
        if ( ! $handle ) {
            return new WP_Error( 'vh360_studio_bunny_stream_file_open_failed', __( 'Cloud upload file is unavailable.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        $url  = self::API_BASE . '/library/' . rawurlencode( $this->library_id ) . '/videos/' . rawurlencode( $video_id );
        $resolutions = $this->enabled_resolutions();
        if ( $resolutions ) {
            $url = add_query_arg( 'enabledResolutions', implode( ',', $resolutions ), $url );
        }
        $curl = curl_init( $url );
        if ( ! $curl ) {
            fclose( $handle );
            return new WP_Error( 'vh360_studio_bunny_stream_upload_failed', __( 'Cloud upload failed before reaching the replay storage service.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        curl_setopt_array( $curl, array(
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_INFILE         => $handle,
            CURLOPT_INFILESIZE     => filesize( $path ),
            CURLOPT_UPLOAD         => true,
            CURLOPT_HTTPHEADER     => array( 'AccessKey: ' . $this->api_key, 'Content-Type: application/octet-stream', 'Accept: application/json' ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) apply_filters( 'vh360_studio_bunny_stream_upload_timeout', 600 ),
        ) );
        $body  = curl_exec( $curl );
        $errno = curl_errno( $curl );
        $code  = absint( curl_getinfo( $curl, CURLINFO_RESPONSE_CODE ) );
        curl_close( $curl );
        fclose( $handle );
        if ( $errno ) {
            return new WP_Error( 'vh360_studio_bunny_stream_upload_failed', __( 'Cloud upload failed before reaching the replay storage service.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        return $this->parse_response_body( $body, $code );
    }

    public function delete_video( $video_id ) {
        return $this->request( 'DELETE', '/library/' . rawurlencode( $this->library_id ) . '/videos/' . rawurlencode( sanitize_text_field( $video_id ) ) );
    }

    public function get_video( $video_id ) {
        return $this->request( 'GET', '/library/' . rawurlencode( $this->library_id ) . '/videos/' . rawurlencode( sanitize_text_field( $video_id ) ) );
    }

    public function list_videos( $page = 1, $items_per_page = 1 ) {
        return $this->request( 'GET', '/library/' . rawurlencode( $this->library_id ) . '/videos', array( 'page' => max( 1, absint( $page ) ), 'itemsPerPage' => max( 1, min( 100, absint( $items_per_page ) ) ) ) );
    }

    public function embed_url( $video_id ) {
        $video_id = sanitize_text_field( $video_id );
        $library  = sanitize_text_field( $this->library_id );
        if ( ! $video_id || ! $library ) {
            return '';
        }
        $url = 'https://player.mediadelivery.net/embed/' . rawurlencode( $library ) . '/' . rawurlencode( $video_id );
        return esc_url_raw( $url );
    }

    public function thumbnail_url_from_video( array $video ) {
        foreach ( array( 'thumbnailUrl', 'thumbnailFileName' ) as $key ) {
            if ( ! empty( $video[ $key ] ) && is_scalar( $video[ $key ] ) ) {
                $value = (string) $video[ $key ];
                if ( wp_http_validate_url( $value ) ) {
                    return esc_url_raw( $value );
                }
            }
        }
        return '';
    }

    public function parse_response_body( $body, $code ) {
        $data = '' === (string) $body ? array() : json_decode( (string) $body, true );
        if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error( 'vh360_studio_bunny_stream_bad_json', __( 'Cloud replay storage returned an invalid response.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        if ( 200 > $code || 300 <= $code ) {
            return new WP_Error( 'vh360_studio_bunny_stream_api_error', $this->safe_error_message( $data, $code ), array( 'status' => 502 ) );
        }
        if ( is_array( $data ) && isset( $data['success'] ) && false === rest_sanitize_boolean( $data['success'] ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_api_error', sprintf( __( 'Cloud replay storage rejected the upload: %s', 'videohub360-studio' ), $this->safe_error_message( $data, $code ) ), array( 'status' => 502 ) );
        }
        return is_array( $data ) ? $data : array();
    }

    public function safe_error_message( $data, $code ) {
        $message = '';
        if ( is_array( $data ) ) {
            foreach ( array( 'message', 'Message', 'error', 'Error' ) as $key ) {
                if ( ! empty( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
                    $message = (string) $data[ $key ];
                    break;
                }
            }
        } elseif ( is_scalar( $data ) ) {
            $message = (string) $data;
        }
        if ( '' === $message ) {
            $message = sprintf( __( 'Cloud replay storage request failed with HTTP %d.', 'videohub360-studio' ), absint( $code ) );
        }
        return wp_html_excerpt( sanitize_text_field( $message ), 240, '…' );
    }

    private function request( $method, $path, array $params = array() ) {
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'vh360_studio_bunny_stream_credentials_missing', __( 'Cloud replay credentials are required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $method = strtoupper( sanitize_key( $method ) );
        $url    = self::API_BASE . '/' . ltrim( $path, '/' );
        $args   = array(
            'method'  => $method,
            'timeout' => (int) apply_filters( 'vh360_studio_bunny_stream_timeout', 45 ),
            'headers' => array( 'AccessKey' => $this->api_key, 'Accept' => 'application/json' ),
        );
        if ( 'GET' === $method ) {
            $url = add_query_arg( $params, $url );
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode( $params );
        }
        $response = wp_remote_request( esc_url_raw( $url ), $args );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'vh360_studio_bunny_stream_request_failed', __( 'Cloud replay storage request failed.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        return $this->parse_response_body( wp_remote_retrieve_body( $response ), absint( wp_remote_retrieve_response_code( $response ) ) );
    }

    private function enabled_resolutions() {
        $allowed = array( '240p', '360p', '480p', '720p', '1080p', '1440p', '2160p' );
        $raw = preg_split( '/[\s,]+/', strtolower( (string) get_option( 'vh360_studio_bunny_stream_enabled_resolutions', '' ) ) );
        return array_values( array_unique( array_intersect( array_filter( array_map( 'sanitize_text_field', $raw ) ), $allowed ) ) );
    }

}
