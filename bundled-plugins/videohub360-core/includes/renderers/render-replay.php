<?php
/**
 * Shared Studio replay rendering helpers.
 *
 * @package VideoHub360
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function videohub360_get_studio_replay_state( $post_id ) {
    $post_id = absint( $post_id );
    $provider = get_post_meta( $post_id, '_vh360_studio_storage_provider', true );
    if ( ! $provider ) {
        $provider = get_post_meta( $post_id, '_vh360_studio_provider', true );
    }
    return array(
        'ready'       => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_ready', true ) || 'yes' === get_post_meta( $post_id, '_vh360_live_room_has_replay', true ),
        'pending'     => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_pending', true ),
        'failed'      => 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_failed', true ),
        'status'      => sanitize_key( get_post_meta( $post_id, '_vh360_studio_replay_status', true ) ),
        'provider'    => sanitize_key( $provider ),
        'playback_url'=> get_post_meta( $post_id, 'video_url', true ),
        'embed_html'  => get_post_meta( $post_id, 'videohub360_custom_html', true ),
        'poster_url'  => get_post_meta( $post_id, '_vh360_studio_poster_url', true ) ?: get_post_meta( $post_id, 'poster_url', true ),
    );
}


function videohub360_kses_studio_replay_embed( $html ) {
    $allowed = wp_kses_allowed_html( 'post' );
    $allowed['iframe'] = array(
        'src'             => true,
        'title'           => true,
        'loading'         => true,
        'allow'           => true,
        'allowfullscreen' => true,
        'style'           => true,
        'class'           => true,
        'width'           => true,
        'height'          => true,
        'frameborder'     => true,
        'referrerpolicy'  => true,
    );
    return wp_kses( $html, $allowed );
}

function videohub360_render_studio_replay( $post_id ) {
    $state = videohub360_get_studio_replay_state( $post_id );
    if ( ! $state['ready'] ) {
        if ( $state['pending'] ) {
            return '<div class="vh360-studio-replay-state vh360-studio-replay-processing">' . esc_html__( 'Replay is processing. Please check back soon.', 'videohub360' ) . '</div>';
        }
        if ( $state['failed'] ) {
            return '<div class="vh360-studio-replay-state vh360-studio-replay-failed">' . esc_html__( 'Replay processing failed.', 'videohub360' ) . '</div>';
        }
        return '';
    }
    if ( ! empty( $state['embed_html'] ) ) {
        return '<div class="vh360-studio-replay-player vh360-studio-replay-provider-' . esc_attr( $state['provider'] ) . '">' . videohub360_kses_studio_replay_embed( $state['embed_html'] ) . '</div>';
    }
    if ( ! empty( $state['playback_url'] ) ) {
        return wp_video_shortcode( array( 'src' => esc_url( $state['playback_url'] ), 'poster' => esc_url( $state['poster_url'] ) ) );
    }
    return '';
}
