<?php
/**
 * Internal Publitio API client.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Publitio_Client {
    const API_BASE = 'https://api.publit.io/v1';

    private $api_key;
    private $api_secret;

    public function __construct( $api_key = '', $api_secret = '' ) {
        $this->api_key    = sanitize_text_field( $api_key ? $api_key : get_option( 'vh360_studio_publitio_api_key', '' ) );
        $this->api_secret = (string) ( $api_secret ? $api_secret : get_option( 'vh360_studio_publitio_api_secret', '' ) );
    }

    public function has_credentials() {
        return '' !== $this->api_key && '' !== $this->api_secret;
    }

    public function list_files( $limit = 1 ) {
        return $this->request( 'GET', '/files/list', array( 'limit' => max( 1, min( 100, absint( $limit ) ) ) ) );
    }

    public function show_file( $file_id ) {
        $file_id = sanitize_text_field( $file_id );
        if ( '' === $file_id ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Publitio file ID is missing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $this->request( 'GET', '/files/show/' . rawurlencode( $file_id ) );
    }

    public function player_file( $file_id, array $params = array() ) {
        $file_id = sanitize_text_field( $file_id );
        if ( '' === $file_id ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file_id', __( 'Publitio file ID is missing.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return $this->request( 'GET', '/files/player/' . rawurlencode( $file_id ), $params );
    }

    public function create_file( $path, array $params = array(), $mime_type = '', $filename = '' ) {
        if ( ! file_exists( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
            return new WP_Error( 'vh360_studio_publitio_missing_file', __( 'Publitio upload file is unavailable.', 'videohub360-studio' ), array( 'status' => 410 ) );
        }
        if ( ! function_exists( 'curl_init' ) ) {
            return new WP_Error( 'vh360_studio_publitio_curl_missing', __( 'Publitio uploads require the PHP cURL extension.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! class_exists( 'CURLFile' ) ) {
            return new WP_Error( 'vh360_studio_publitio_curlfile_missing', __( 'Publitio uploads require CURLFile support on this server.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'vh360_studio_publitio_credentials_missing', __( 'Publitio API key and secret are required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }

        $mime_type = $mime_type ? sanitize_mime_type( $mime_type ) : 'application/octet-stream';
        $filename  = $filename ? sanitize_file_name( $filename ) : basename( $path );
        $url       = add_query_arg( array_merge( $this->auth_params(), $params ), self::API_BASE . '/files/create' );
        $curl      = curl_init( $url );
        if ( ! $curl ) {
            return new WP_Error( 'vh360_studio_publitio_curl_init_failed', __( 'Publitio upload failed before reaching the API.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        curl_setopt_array( $curl, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => array( 'file' => new CURLFile( $path, $mime_type, $filename ) ),
            CURLOPT_HTTPHEADER     => array( 'Accept: application/json' ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) apply_filters( 'vh360_studio_publitio_upload_timeout', 300 ),
        ) );

        $body       = curl_exec( $curl );
        $errno      = curl_errno( $curl );
        $curl_error = curl_error( $curl );
        $code       = absint( curl_getinfo( $curl, CURLINFO_RESPONSE_CODE ) );
        curl_close( $curl );

        if ( $errno ) {
            $message = CURLE_OPERATION_TIMEDOUT === $errno ? __( 'Publitio upload timed out.', 'videohub360-studio' ) : __( 'Publitio upload failed before reaching the API.', 'videohub360-studio' );
            if ( $curl_error ) {
                $message .= ' ' . wp_html_excerpt( sanitize_text_field( $curl_error ), 120, '…' );
            }
            return new WP_Error( 'vh360_studio_publitio_upload_transport_failed', $message, array( 'status' => 502 ) );
        }

        return $this->parse_response_body( $body, $code );
    }

    private function request( $method, $path, array $params = array(), $multipart = false ) {
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'vh360_studio_publitio_credentials_missing', __( 'Publitio API key and secret are required.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        if ( ! function_exists( 'wp_remote_request' ) ) {
            return new WP_Error( 'vh360_studio_publitio_http_unavailable', __( 'WordPress HTTP API is unavailable.', 'videohub360-studio' ), array( 'status' => 500 ) );
        }

        $auth   = $this->auth_params();
        $method = strtoupper( sanitize_key( $method ) );
        $url    = self::API_BASE . '/' . ltrim( $path, '/' );
        $args   = array(
            'method'  => $method,
            'timeout' => (int) apply_filters( 'vh360_studio_publitio_timeout', 45 ),
        );

        if ( 'GET' === $method ) {
            $url = add_query_arg( array_merge( $params, $auth ), $url );
        } else {
            $args['body'] = array_merge( $params, $auth );
            if ( ! $multipart ) {
                $args['headers'] = array( 'Accept' => 'application/json' );
            }
        }

        $response = wp_remote_request( esc_url_raw( $url ), $args );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'vh360_studio_publitio_network_failed', __( 'Publitio request failed. Check network connectivity and credentials.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }

        return $this->parse_response_body( wp_remote_retrieve_body( $response ), absint( wp_remote_retrieve_response_code( $response ) ) );
    }

    private function parse_response_body( $body, $code ) {
        $data = json_decode( (string) $body, true );
        if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
            return new WP_Error( 'vh360_studio_publitio_bad_json', __( 'Publitio returned an invalid JSON response.', 'videohub360-studio' ), array( 'status' => 502 ) );
        }
        if ( 200 > $code || 300 <= $code ) {
            return new WP_Error( 'vh360_studio_publitio_api_error', $this->safe_error_message( $data, $code ), array( 'status' => 502 ) );
        }
        if ( is_array( $data ) && isset( $data['success'] ) && false === rest_sanitize_boolean( $data['success'] ) ) {
            return new WP_Error( 'vh360_studio_publitio_api_error', sprintf( __( 'Publitio rejected the upload: %s', 'videohub360-studio' ), $this->safe_error_message( $data, $code ) ), array( 'status' => 502 ) );
        }
        return is_array( $data ) ? $data : array();
    }

    private function auth_params() {
        $timestamp = time();
        $nonce     = (string) wp_rand( 10000000, 99999999 );
        return array(
            'api_key'       => $this->api_key,
            'api_timestamp' => $timestamp,
            'api_nonce'     => $nonce,
            'api_signature' => sha1( $timestamp . $nonce . $this->api_secret ),
        );
    }

    private function safe_error_message( $data, $code ) {
        $message = $this->find_error_message( $data );
        if ( '' === $message ) {
            $message = sprintf( __( 'Publitio API request failed with HTTP %d.', 'videohub360-studio' ), absint( $code ) );
        }
        return wp_html_excerpt( sanitize_text_field( $message ), 240, '…' );
    }

    private function find_error_message( $data ) {
        if ( is_scalar( $data ) ) {
            return (string) $data;
        }
        if ( ! is_array( $data ) ) {
            return '';
        }
        foreach ( array( 'message', 'msg' ) as $key ) {
            if ( ! empty( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
                return (string) $data[ $key ];
            }
        }
        if ( ! empty( $data['error'] ) ) {
            if ( is_scalar( $data['error'] ) ) {
                return (string) $data['error'];
            }
            if ( is_array( $data['error'] ) && ! empty( $data['error']['message'] ) && is_scalar( $data['error']['message'] ) ) {
                return (string) $data['error']['message'];
            }
        }
        if ( isset( $data[0] ) ) {
            return $this->find_error_message( $data[0] );
        }
        return '';
    }
}
