<?php
/**
 * Reusable livestream service for default Agora livestream posts.
 *
 * @package VideoHub360
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VideoHub360_Livestream_Service {
    public function current_user_can_manage( $post_id ) {
        return current_user_can( 'edit_post', absint( $post_id ) ) || current_user_can( 'manage_options' );
    }

    public function generate_channel_name( $post_id = 0 ) {
        $suffix = function_exists( 'wp_generate_password' ) ? wp_generate_password( 20, false, false ) : uniqid( '', true );
        return 'vh360_' . absint( $post_id ) . '_' . preg_replace( '/[^A-Za-z0-9_]/', '', $suffix );
    }

    public function create_or_update_default_agora_livestream( $user_id, $data, $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( $post_id && ! $this->current_user_can_manage( $post_id ) ) {
            return new WP_Error( 'vh360_livestream_forbidden', __( 'You cannot manage this livestream.', 'videohub360' ), array( 'status' => 403 ) );
        }

        $title = sanitize_text_field( $data['title'] ?? '' );
        if ( '' === $title ) {
            $title = __( 'Untitled Livestream', 'videohub360' );
        }

        $postarr = array(
            'post_type'    => 'videohub360',
            'post_title'   => $title,
            'post_content' => wp_kses_post( $data['description'] ?? '' ),
            'post_status'  => 'publish',
            'post_author'  => absint( $user_id ),
        );

        if ( $post_id ) {
            $postarr['ID'] = $post_id;
            $saved_id = wp_update_post( $postarr, true );
        } else {
            $saved_id = wp_insert_post( $postarr, true );
        }

        if ( is_wp_error( $saved_id ) ) {
            return $saved_id;
        }

        $saved_id = absint( $saved_id );
        $channel  = sanitize_text_field( $data['channel_name'] ?? '' );
        if ( '' === $channel ) {
            $channel = get_post_meta( $saved_id, '_vh360_agora_channel_name', true );
        }
        if ( '' === $channel ) {
            $channel = $this->generate_channel_name( $saved_id );
        }
        $channel = preg_replace( '/[^A-Za-z0-9_\-]/', '', $channel );

        $mode = in_array( ( $data['agora_mode'] ?? 'broadcast' ), array( 'broadcast', 'interactive' ), true ) ? $data['agora_mode'] : 'broadcast';
        $everyone_is_host = ( 'interactive' === $mode && ! empty( $data['agora_everyone_is_host'] ) && 'yes' === $data['agora_everyone_is_host'] ) ? 'yes' : 'no';
        $require_passcode = ( 'interactive' === $mode && 'yes' !== $everyone_is_host && ! empty( $data['require_passcode'] ) && 'yes' === $data['require_passcode'] );

        $meta = array(
            '_vh360_context'                => 'default',
            '_vh360_is_live'                => 'yes',
            '_vh360_type'                   => 'agora',
            '_vh360_agora_channel_name'     => $channel,
            '_vh360_agora_mode'             => $mode,
            '_vh360_agora_stream_live'      => 'no',
            '_vh360_stream_stopped'         => 'no',
            '_vh360_viewer_count'           => ! empty( $data['viewer_count'] ) && 'yes' === $data['viewer_count'] ? 'yes' : 'no',
            '_vh360_chat_enabled'           => ! empty( $data['chat_enabled'] ) && 'yes' === $data['chat_enabled'] ? 'yes' : 'no',
            '_vh360_agora_everyone_is_host' => $everyone_is_host,
            '_vh360_live_badge'             => 'yes',
            '_vh360_badge_text'             => 'LIVE',
        );

        foreach ( $meta as $key => $value ) {
            update_post_meta( $saved_id, $key, $value );
        }

        if ( $require_passcode ) {
            $passcode = sanitize_text_field( $data['host_passcode'] ?? '' );
            if ( '' !== $passcode ) {
                update_post_meta( $saved_id, '_vh360_host_passcode', wp_hash_password( $passcode ) );
            }
        } else {
            update_post_meta( $saved_id, '_vh360_host_passcode', '' );
        }

        return $this->get_livestream_data( $saved_id );
    }

    public function prepare_agora_broadcast_data( $post_id, $user_id = 0 ) {
        $post_id = absint( $post_id );
        if ( ! $this->current_user_can_manage( $post_id ) ) {
            return new WP_Error( 'vh360_livestream_forbidden', __( 'You cannot host this livestream.', 'videohub360' ), array( 'status' => 403 ) );
        }
        $app_id = get_option( 'vh360_agora_app_id', get_option( 'videohub360_agora_app_id', '' ) );
        if ( '' === $app_id ) {
            return new WP_Error( 'vh360_livestream_missing_app_id', __( 'Agora App ID is not configured in VideoHub360 Core.', 'videohub360' ), array( 'status' => 400 ) );
        }
        $channel = get_post_meta( $post_id, '_vh360_agora_channel_name', true );
        if ( '' === $channel ) {
            $channel = $this->generate_channel_name( $post_id );
            update_post_meta( $post_id, '_vh360_agora_channel_name', $channel );
        }
        $uid = absint( $user_id ) ?: get_current_user_id();
        $uid = $uid ? $uid : wp_rand( 100000, 999999 );
        $token = '';
        $expires_at = time() + 3600;
        $certificate = get_option( 'vh360_agora_app_certificate', '' );
        if ( '' !== $certificate ) {
            require_once VIDEOHUB360_PLUGIN_DIR . 'agora-token/src/RtcTokenBuilder.php';
            $expires_at = time() + (int) apply_filters( 'vh360_agora_token_lifetime', 3600 );
            $token = RtcTokenBuilder::buildTokenWithUid( $app_id, $certificate, $channel, $uid, RtcTokenBuilder::RolePublisher, $expires_at );
        } elseif ( (bool) get_option( 'vh360_agora_require_tokens', 1 ) ) {
            return new WP_Error( 'vh360_livestream_missing_certificate', __( 'Agora App Certificate is required before Studio can broadcast.', 'videohub360' ), array( 'status' => 400 ) );
        }
        return array_merge( $this->get_livestream_data( $post_id ), array( 'appId' => $app_id, 'token' => $token, 'uid' => $uid, 'role' => 'host', 'expiresAt' => $expires_at ) );
    }

    public function mark_live( $post_id ) { update_post_meta( absint( $post_id ), '_vh360_agora_stream_live', 'yes' ); update_post_meta( absint( $post_id ), '_vh360_stream_stopped', 'no' ); }
    public function mark_ended( $post_id ) { update_post_meta( absint( $post_id ), '_vh360_agora_stream_live', 'no' ); update_post_meta( absint( $post_id ), '_vh360_stream_stopped', 'yes' ); }

    public function get_livestream_data( $post_id ) {
        return array(
            'videoId' => absint( $post_id ),
            'channelName' => get_post_meta( $post_id, '_vh360_agora_channel_name', true ),
            'mode' => get_post_meta( $post_id, '_vh360_agora_mode', true ) ?: 'broadcast',
            'viewerPermalink' => get_permalink( $post_id ),
            'streamLive' => 'yes' === get_post_meta( $post_id, '_vh360_agora_stream_live', true ),
        );
    }
}
