<?php
/**
 * VideoHub360 License Gate (Client-side)
 *
 * Theme + plugins should call videohub360_license_is_valid() before allowing
 * premium functionality. This file intentionally contains only lightweight
 * helpers (no remote calls).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'videohub360_license_get_data' ) ) {
    function videohub360_license_get_data() : array {
        $data = get_option( 'videohub360_license_data', array() );
        return is_array( $data ) ? $data : array();
    }
}

if ( ! function_exists( 'videohub360_license_is_valid' ) ) {
    function videohub360_license_is_valid() : bool {
        $data = videohub360_license_get_data();
        return ( 'valid' === ( $data['status'] ?? '' ) );
    }
}

if ( ! function_exists( 'videohub360_license_locked_message' ) ) {
    function videohub360_license_locked_message() : string {
        return __( 'VideoHub360 is locked until you activate your license (VideoHub360 → License).', 'videohub360' );
    }
}

if ( ! function_exists( 'videohub360_license_admin_notice' ) ) {
    function videohub360_license_admin_notice() : void {
        if ( videohub360_license_is_valid() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $url = admin_url( 'admin.php?page=videohub360-license' );
        echo '<div class="notice notice-warning"><p>';
        echo esc_html( videohub360_license_locked_message() ) . ' ';
        echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Activate License', 'videohub360' ) . '</a>';
        echo '</p></div>';
    }
}

if ( ! function_exists( 'videohub360_gate_ajax_actions' ) ) {
    /**
     * Blocks premium AJAX actions when unlicensed.
     *
     * Runs on admin_init so it can short-circuit before the action callback.
     */
    function videohub360_gate_ajax_actions() : void {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return;
        }

        if ( videohub360_license_is_valid() ) {
            return;
        }

        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( '' === $action ) {
            return;
        }

        // Gate only premium *mutating* actions (leave read-only actions alone).
        $blocked = array(
            // Stream/admin controls
            'vh360_admin_stop_stream',
            'vh360_admin_restart_stream',
            'vh360_generate_agora_token',
            'vh360_set_stream_status',
            'vh360_end_stream',
            'vh360_stop_stream',
            'vh360_restart_stream',
            'vh360_remove_participant',
            // Moderation/admin actions
            'videohub360_ban_user',
            'videohub360_timeout_user',
            'videohub360_unban_user',
            'videohub360_remove_timeout',
            // Ads/admin analytics setters (keep tracking open)
            // add more here as needed
        );

        if ( in_array( $action, $blocked, true ) ) {
            wp_send_json_error(
                array(
                    'message' => videohub360_license_locked_message(),
                    'code'    => 'vh360_license_required',
                ),
                403
            );
        }
    }
}
