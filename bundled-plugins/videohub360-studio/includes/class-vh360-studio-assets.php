<?php
/**
 * Studio dashboard frontend assets.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Assets {
    private $registry;

    public function __construct( VH360_Studio_Provider_Registry $registry ) {
        $this->registry = $registry;
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_dashboard_assets' ) );
    }

    public function enqueue_dashboard_assets() {
        if ( ! $this->is_studio_dashboard_tab() || ! VH360_Studio_Permissions::user_can_access_studio() ) {
            return;
        }

        $css_path = 'assets/css/studio-dashboard.css';
        $js_path  = 'assets/js/studio-dashboard.js';

        wp_enqueue_style(
            'vh360-studio-dashboard',
            VH360_STUDIO_PLUGIN_URL . $css_path,
            array(),
            $this->asset_version( $css_path )
        );

        wp_enqueue_script( 'agora-rtc-sdk', 'https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js', array(), '4.20.0', true );

        wp_enqueue_script(
            'vh360-agora-broadcaster',
            VIDEOHUB360_ASSETS_URL . 'js/agora-broadcaster.js',
            array( 'agora-rtc-sdk' ),
            videohub360_asset_version( 'assets/js/agora-broadcaster.js' ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-dashboard',
            VH360_STUDIO_PLUGIN_URL . $js_path,
            array( 'vh360-agora-broadcaster' ),
            $this->asset_version( $js_path ),
            true
        );

        wp_localize_script( 'vh360-studio-dashboard', 'vh360StudioDashboard', $this->localized_data() );
    }

    private function is_studio_dashboard_tab() {
        $active_tab = function_exists( 'vh360_get_current_dashboard_tab' ) ? vh360_get_current_dashboard_tab() : '';

        if ( 'studio' === $active_tab ) {
            return true;
        }

        return isset( $_GET['tab'] ) && 'studio' === sanitize_key( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    private function asset_version( $relative_path ) {
        $file_path = VH360_STUDIO_PLUGIN_DIR . ltrim( $relative_path, '/' );
        return file_exists( $file_path ) ? VH360_STUDIO_VERSION . '-' . filemtime( $file_path ) : VH360_STUDIO_VERSION;
    }

    private function localized_data() {
        return array(
            'restRoot'                  => esc_url_raw( rest_url( 'vh360-studio/v1' ) ),
            'nonce'                     => wp_create_nonce( 'wp_rest' ),
            'qualityPresets'            => VH360_Studio_Quality_Presets::get_presets(),
            'defaultQualityPreset'      => VH360_Studio_Quality_Presets::DEFAULT_PRESET,
            'uploadSettings'             => class_exists( 'VH360_Studio_Recording_Chunks' ) ? ( new VH360_Studio_Recording_Chunks( VH360_Studio_Plugin::instance()->jobs() ) )->upload_settings() : array(),
            'publitioDirectUpload'      => $this->publitio_direct_upload_config(),
            'currentUserId'             => get_current_user_id(),
            'strings'                   => array(
                'ready'                  => __( 'Ready', 'videohub360-studio' ),
                'browserUnsupported'     => __( 'This browser is missing required Studio features.', 'videohub360-studio' ),
                'cameraBlocked'          => __( 'Camera access was blocked. Check browser permissions and try again.', 'videohub360-studio' ),
                'microphoneBlocked'      => __( 'Microphone access was blocked. Check browser permissions and try again.', 'videohub360-studio' ),
                'screenUnsupported'      => __( 'Screen sharing is not supported in this browser.', 'videohub360-studio' ),
                'previewActive'          => __( 'Camera and microphone preview is active.', 'videohub360-studio' ),
                'screenPreviewActive'    => __( 'Screen-share preview is active.', 'videohub360-studio' ),
                'setupJobCreated'        => __( 'Setup job created. Recording has not started yet.', 'videohub360-studio' ),
                'jobCreationFailed'      => __( 'Setup job could not be created. Please try again.', 'videohub360-studio' ),
                'permissionDenied'       => __( 'Permission was denied. Please allow access in your browser.', 'videohub360-studio' ),
                'noCameraFound'          => __( 'No camera was found.', 'videohub360-studio' ),
                'noMicrophoneFound'      => __( 'No microphone was found.', 'videohub360-studio' ),
                'insecureContext'        => __( 'Studio previews require HTTPS or localhost.', 'videohub360-studio' ),
                'screenCancelled'        => __( 'Screen sharing was cancelled.', 'videohub360-studio' ),
                'checking'               => __( 'Checking browser support…', 'videohub360-studio' ),
                'supported'              => __( 'Supported', 'videohub360-studio' ),
                'notSupported'           => __( 'Not supported', 'videohub360-studio' ),
                'startRecording'        => __( 'Start recording', 'videohub360-studio' ),
                'stopRecording'         => __( 'Stop recording', 'videohub360-studio' ),
                'recordingActive'       => __( 'Recording active.', 'videohub360-studio' ),
                'uploadingChunk'        => __( 'Recording stopped. Uploading remaining chunks to WordPress.', 'videohub360-studio' ),
                'uploadRetry'           => __( 'Retrying upload…', 'videohub360-studio' ),
                'finalizing'            => __( 'Preparing replay…', 'videohub360-studio' ),
                'recordingSaved'        => __( 'Recording saved. Prepare replay when ready.', 'videohub360-studio' ),
                'chunkUploadFailed'     => __( 'Some chunks failed to upload. Retry failed chunks before preparing the replay.', 'videohub360-studio' ),
                'recorderUnavailable'   => __( 'Browser recorder is unavailable.', 'videohub360-studio' ),
                'recordingCancelled'    => __( 'Recording cancelled.', 'videohub360-studio' ),
                'publishReplay'         => __( 'Publish replay', 'videohub360-studio' ),
                'publishingReplay'      => __( 'Uploading replay to Publitio. This may take a moment.', 'videohub360-studio' ),
                'publishStatusChecking' => __( 'Checking publishing status…', 'videohub360-studio' ),
                'publishComplete'       => __( 'Replay published.', 'videohub360-studio' ),
                'publishProcessing'     => __( 'Replay uploaded to Publitio. Waiting for processing.', 'videohub360-studio' ),
                'publishFailed'         => __( 'Replay publishing failed. Please try again.', 'videohub360-studio' ),
                'publitioDirectUploading' => __( 'Uploading directly to Publitio…', 'videohub360-studio' ),
                'publitioDirectVerifying' => __( 'Publitio upload complete. Verifying replay…', 'videohub360-studio' ),
                'publitioDirectFallback' => __( 'Direct upload failed. Using server relay fallback.', 'videohub360-studio' ),
                'goLive'                => __( 'Go Live', 'videohub360-studio' ),
                'goingLive'             => __( 'Joining Agora and publishing the Program output…', 'videohub360-studio' ),
                'liveStarted'           => __( 'Live broadcast started.', 'videohub360-studio' ),
                'liveEnded'             => __( 'Live broadcast ended.', 'videohub360-studio' ),
                'broadcastFailed'       => __( 'Broadcast could not start. Check Agora settings and device permissions.', 'videohub360-studio' ),

            ),
            'supportLabels'             => array(
                'mediaDevices'     => __( 'Media devices API', 'videohub360-studio' ),
                'getUserMedia'     => __( 'Camera and microphone access', 'videohub360-studio' ),
                'enumerateDevices' => __( 'Device selection', 'videohub360-studio' ),
                'getDisplayMedia'  => __( 'Screen-share preview', 'videohub360-studio' ),
                'mediaRecorder'    => __( 'Browser recorder support', 'videohub360-studio' ),
                'secureContext'    => __( 'Secure browser context', 'videohub360-studio' ),
                'mimeTypes'        => __( 'Supported recording formats', 'videohub360-studio' ),
                'canvasCapture'    => __( 'Program canvas output', 'videohub360-studio' ),
                'canvasContext'    => __( 'Canvas drawing support', 'videohub360-studio' ),
                'clipboardCopy'    => __( 'Clipboard copy support', 'videohub360-studio' ),
            ),
        );
    }

    private function publitio_direct_upload_config() {
        $mode     = sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) );
        $preset   = sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
        $max_size = absint( get_option( 'vh360_studio_publitio_direct_max_size', 314572800 ) );

        return array(
            'enabled'            => 'direct_browser' === $mode && '' !== $preset,
            'upload_mode'        => $mode,
            'upload_preset_id'   => $preset,
            'upload_url_base'    => 'https://api.publit.io/v1/files/create/',
            'allowed_mime_types' => array( 'video/mp4', 'video/webm' ),
            'max_size'           => $max_size ? $max_size : 314572800,
        );
    }

}
