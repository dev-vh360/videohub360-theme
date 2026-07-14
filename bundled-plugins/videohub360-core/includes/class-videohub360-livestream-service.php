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
    const STALE_CLEANUP_HOOK = 'vh360_studio_stale_broadcast_cleanup';
    public function current_user_can_manage( $post_id, $user_id = 0 ) {
        $post_id = absint( $post_id );
        $user_id = absint( $user_id ) ?: get_current_user_id();
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return false;
        }

        return current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $post_id ) || ( $user_id && (int) $post->post_author === (int) $user_id );
    }

    public function validate_agora_livestream_for_management( $post_id, $user_id = 0 ) {
        $post_id = absint( $post_id );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'vh360_livestream_not_found', __( 'Livestream not found.', 'videohub360' ), array( 'status' => 404 ) );
        }

        if ( 'videohub360' !== $post->post_type ) {
            return new WP_Error( 'vh360_livestream_invalid_post_type', __( 'Invalid livestream post type.', 'videohub360' ), array( 'status' => 400 ) );
        }

        if ( 'agora' !== get_post_meta( $post_id, '_vh360_type', true ) || 'yes' !== get_post_meta( $post_id, '_vh360_is_live', true ) ) {
            return new WP_Error( 'vh360_livestream_not_agora', __( 'This post is not an Agora livestream.', 'videohub360' ), array( 'status' => 400 ) );
        }

        if ( ! $this->current_user_can_manage( $post_id, $user_id ) ) {
            return new WP_Error( 'vh360_livestream_forbidden', __( 'You cannot manage this livestream.', 'videohub360' ), array( 'status' => 403 ) );
        }

        return $post;
    }

    public function generate_channel_name( $post_id = 0 ) {
        $suffix = function_exists( 'wp_generate_password' ) ? wp_generate_password( 20, false, false ) : uniqid( '', true );
        return 'vh360_' . absint( $post_id ) . '_' . preg_replace( '/[^A-Za-z0-9_]/', '', $suffix );
    }

    public function create_or_update_default_agora_livestream( $user_id, $data, $post_id = 0 ) {
        $post_id = absint( $post_id );
        if ( $post_id ) {
            $validated_post = get_post( $post_id );
            if ( ! $validated_post || 'videohub360' !== $validated_post->post_type ) {
                return new WP_Error( 'vh360_livestream_invalid_post', __( 'Invalid livestream post.', 'videohub360' ), array( 'status' => 400 ) );
            }
            if ( ! $this->current_user_can_manage( $post_id, $user_id ) ) {
                return new WP_Error( 'vh360_livestream_forbidden', __( 'You cannot manage this livestream.', 'videohub360' ), array( 'status' => 403 ) );
            }
        }

        $title = sanitize_text_field( $data['title'] ?? '' );
        if ( '' === $title ) {
            $title = __( 'Untitled Livestream', 'videohub360' );
        }

        $requested_mode = in_array( ( $data['agora_mode'] ?? 'broadcast' ), array( 'broadcast', 'interactive' ), true ) ? $data['agora_mode'] : 'broadcast';
        $requested_everyone_is_host = ( 'interactive' === $requested_mode && ! empty( $data['agora_everyone_is_host'] ) && 'yes' === $data['agora_everyone_is_host'] ) ? 'yes' : 'no';
        $requested_require_passcode = ( 'interactive' === $requested_mode && 'yes' !== $requested_everyone_is_host && ! empty( $data['require_passcode'] ) && 'yes' === $data['require_passcode'] );
        if ( $requested_require_passcode && '' === sanitize_text_field( $data['host_passcode'] ?? '' ) && ( ! $post_id || '' === get_post_meta( $post_id, '_vh360_host_passcode', true ) ) ) {
            return new WP_Error( 'vh360_livestream_passcode_required', __( 'A host passcode is required when passcode access is enabled.', 'videohub360' ), array( 'status' => 400 ) );
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

        $mode = $requested_mode;
        $everyone_is_host = $requested_everyone_is_host;
        $require_passcode = $requested_require_passcode;

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

        $featured_image_id = ! empty( $data['featured_image_id'] ) ? absint( $data['featured_image_id'] ) : 0;
        if ( $featured_image_id && 'attachment' === get_post_type( $featured_image_id ) && 0 === strpos( (string) get_post_mime_type( $featured_image_id ), 'image/' ) ) {
            set_post_thumbnail( $saved_id, $featured_image_id );
            $poster_url = wp_get_attachment_image_url( $featured_image_id, 'large' );
            if ( $poster_url ) {
                update_post_meta( $saved_id, '_vh360_poster', esc_url_raw( $poster_url ) );
            }
        } elseif ( ! empty( $data['clear_featured_image'] ) && $this->current_user_can_manage( $saved_id, $user_id ) ) {
            delete_post_thumbnail( $saved_id );
            delete_post_meta( $saved_id, '_vh360_poster' );
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
        $validated = $this->validate_agora_livestream_for_management( $post_id, $user_id );
        if ( is_wp_error( $validated ) ) {
            return $validated;
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
        $is_studio_controlled = 'yes' === get_post_meta( $post_id, '_vh360_studio_controlled_live', true );
        if ( $is_studio_controlled ) {
            $uid = absint( get_post_meta( $post_id, '_vh360_studio_host_agora_uid', true ) );
            if ( ! $uid ) {
                $uid = wp_rand( 100000000, 999999999 );
                update_post_meta( $post_id, '_vh360_studio_host_agora_uid', $uid );
            }
        } else {
            $uid = absint( $user_id ) ?: get_current_user_id();
            $uid = $uid ? $uid : wp_rand( 100000, 999999 );
        }
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
        if ( class_exists( 'VideoHub360_Agora_Participant_Registry' ) ) {
            $studio_user_id = absint( get_post_meta( $post_id, '_vh360_studio_host_user_id', true ) ) ?: absint( $user_id ) ?: get_current_user_id();
            VideoHub360_Agora_Participant_Registry::register( array(
                'post_id' => $post_id,
                'channel_name' => $channel,
                'agora_uid' => $uid,
                'wordpress_user_id' => $studio_user_id,
                'display_name' => $studio_user_id ? get_the_author_meta( 'display_name', $studio_user_id ) : __( 'Host', 'videohub360' ),
                'avatar_url' => $studio_user_id ? get_avatar_url( $studio_user_id ) : '',
                'is_guest' => 0,
                'is_studio_host' => $is_studio_controlled ? 1 : 0,
                'is_original_host' => 1,
                'lifetime' => max( HOUR_IN_SECONDS, absint( $expires_at - time() ) ),
            ) );
        }

        $agora_mode = get_post_meta( $post_id, '_vh360_agora_mode', true ) ?: 'broadcast';
        $client_mode = 'interactive' === $agora_mode ? 'rtc' : 'live';
        return array_merge( $this->get_livestream_data( $post_id ), array( 'appId' => $app_id, 'token' => $token, 'uid' => $uid, 'role' => 'host', 'expiresAt' => $expires_at, 'clientMode' => $client_mode ) );
    }

    public function mark_live( $post_id, $user_id = 0 ) {
        $post_id   = absint( $post_id );
        $validated = $this->validate_agora_livestream_for_management( $post_id, $user_id );
        if ( is_wp_error( $validated ) ) {
            return $validated;
        }

        $old_is_live = get_post_meta( $post_id, '_vh360_is_live', true ) === 'yes' ? 'yes' : 'no';

        update_post_meta( $post_id, '_vh360_agora_stream_live', 'yes' );
        update_post_meta( $post_id, '_vh360_is_live', 'yes' );
        delete_post_meta( $post_id, '_vh360_stream_stopped' );

        if ( '' === get_post_meta( $post_id, '_vh360_live_start_time', true ) ) {
            update_post_meta( $post_id, '_vh360_live_start_time', current_time( 'mysql' ) );
        }
        update_post_meta( $post_id, '_vh360_studio_last_heartbeat_at', current_time( 'mysql' ) );

        if ( 'live_room' === get_post_meta( $post_id, '_vh360_context', true ) && 'yes' !== $old_is_live ) {
            do_action( 'vh360_live_room_started', $post_id );
        }

        if ( function_exists( 'videohub360_debug_log' ) ) {
            videohub360_debug_log( 'Studio Agora livestream marked live', array( 'post_id' => $post_id, 'user_id' => absint( $user_id ) ?: get_current_user_id() ) );
        }

        return $this->get_livestream_data( $post_id );
    }

    public function mark_ended( $post_id, $user_id = 0 ) {
        $post_id   = absint( $post_id );
        $validated = $this->validate_agora_livestream_for_management( $post_id, $user_id );
        if ( is_wp_error( $validated ) ) {
            return $validated;
        }

        $old_is_live = get_post_meta( $post_id, '_vh360_is_live', true ) === 'yes' ? 'yes' : 'no';

        update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
        update_post_meta( $post_id, '_vh360_agora_stream_live', 'no' );
        update_post_meta( $post_id, '_vh360_is_live', 'yes' );

        if ( 'live_room' === get_post_meta( $post_id, '_vh360_context', true ) && 'yes' === $old_is_live ) {
            do_action( 'vh360_live_room_ended', $post_id );
        }

        if ( function_exists( 'videohub360_debug_log' ) ) {
            videohub360_debug_log( 'Studio Agora livestream marked ended', array( 'post_id' => $post_id, 'user_id' => absint( $user_id ) ?: get_current_user_id() ) );
        }

        return $this->get_livestream_data( $post_id );
    }


    public function update_studio_heartbeat( $post_id, $user_id = 0 ) {
        $post_id   = absint( $post_id );
        $validated = $this->validate_agora_livestream_for_management( $post_id, $user_id );
        if ( is_wp_error( $validated ) ) {
            return $validated;
        }

        update_post_meta( $post_id, '_vh360_studio_last_heartbeat_at', current_time( 'mysql' ) );
        return $this->get_livestream_data( $post_id );
    }

    public static function register_stale_cleanup() {
        add_action( self::STALE_CLEANUP_HOOK, array( __CLASS__, 'cleanup_stale_studio_broadcasts' ) );
        if ( ! wp_next_scheduled( self::STALE_CLEANUP_HOOK ) ) {
            wp_schedule_event( time() + 300, 'hourly', self::STALE_CLEANUP_HOOK );
        }
    }

    public static function cleanup_stale_studio_broadcasts( $timeout_seconds = null ) {
        $timeout_seconds = null === $timeout_seconds ? (int) apply_filters( 'vh360_studio_broadcast_stale_timeout', 120 ) : absint( $timeout_seconds );
        $cutoff          = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - max( 60, $timeout_seconds ) );

        $query = new WP_Query(
            array(
                'post_type'      => 'videohub360',
                'post_status'    => 'any',
                'fields'         => 'ids',
                'posts_per_page' => 20,
                'no_found_rows'  => true,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array( 'key' => '_vh360_type', 'value' => 'agora' ),
                    array( 'key' => '_vh360_agora_stream_live', 'value' => 'yes' ),
                    array( 'key' => '_vh360_studio_last_heartbeat_at', 'value' => $cutoff, 'compare' => '<', 'type' => 'DATETIME' ),
                ),
            )
        );

        foreach ( $query->posts as $post_id ) {
            update_post_meta( $post_id, '_vh360_stream_stopped', 'yes' );
            update_post_meta( $post_id, '_vh360_agora_stream_live', 'no' );
            update_post_meta( $post_id, '_vh360_is_live', 'yes' );
            if ( function_exists( 'videohub360_debug_log' ) ) {
                videohub360_debug_log( 'Studio Agora livestream marked ended by stale heartbeat cleanup', array( 'post_id' => absint( $post_id ) ) );
            }
        }

        return count( $query->posts );
    }

    public function get_livestream_data( $post_id ) {
        $featured_image_id = get_post_thumbnail_id( $post_id );
        return array(
            'videoId' => absint( $post_id ),
            'channelName' => get_post_meta( $post_id, '_vh360_agora_channel_name', true ),
            'mode' => get_post_meta( $post_id, '_vh360_agora_mode', true ) ?: 'broadcast',
            'viewerPermalink' => get_permalink( $post_id ),
            'streamLive' => 'yes' === get_post_meta( $post_id, '_vh360_agora_stream_live', true ),
            'clientMode' => 'interactive' === ( get_post_meta( $post_id, '_vh360_agora_mode', true ) ?: 'broadcast' ) ? 'rtc' : 'live',
            'featuredImageId' => absint( $featured_image_id ),
            'featuredImageUrl' => $featured_image_id ? ( wp_get_attachment_image_url( $featured_image_id, 'large' ) ?: wp_get_attachment_url( $featured_image_id ) ) : '',
        );
    }
}
