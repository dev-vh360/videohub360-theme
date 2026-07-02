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

        wp_enqueue_script(
            'vh360-studio-dashboard',
            VH360_STUDIO_PLUGIN_URL . $js_path,
            array(),
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
            'storageProviders'          => $this->get_storage_provider_data(),
            'recommendedStorageProvider' => 'videopress',
            'uploadSettings'             => class_exists( 'VH360_Studio_Recording_Chunks' ) ? ( new VH360_Studio_Recording_Chunks( VH360_Studio_Plugin::instance()->jobs() ) )->upload_settings() : array(),
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
                'recordingActive'       => __( 'Recording active. Chunks are uploading to WordPress.', 'videohub360-studio' ),
                'uploadingChunk'        => __( 'Uploading recording chunk…', 'videohub360-studio' ),
                'uploadRetry'           => __( 'Retrying failed chunks…', 'videohub360-studio' ),
                'finalizing'            => __( 'Finalizing recording…', 'videohub360-studio' ),
                'recordingSaved'        => __( 'Recording saved for processing.', 'videohub360-studio' ),
                'chunkUploadFailed'     => __( 'A recording chunk upload failed. Retry before finalizing.', 'videohub360-studio' ),
                'recorderUnavailable'   => __( 'Browser recorder is unavailable.', 'videohub360-studio' ),
                'recordingCancelled'    => __( 'Recording cancelled.', 'videohub360-studio' ),
            ),
            'supportLabels'             => array(
                'mediaDevices'     => __( 'Media devices API', 'videohub360-studio' ),
                'getUserMedia'     => __( 'Camera and microphone access', 'videohub360-studio' ),
                'enumerateDevices' => __( 'Device selection', 'videohub360-studio' ),
                'getDisplayMedia'  => __( 'Screen-share preview', 'videohub360-studio' ),
                'mediaRecorder'    => __( 'Browser recorder support', 'videohub360-studio' ),
                'secureContext'    => __( 'Secure browser context', 'videohub360-studio' ),
                'mimeTypes'        => __( 'Supported recording formats', 'videohub360-studio' ),
            ),
        );
    }

    private function get_storage_provider_data() {
        $providers = array();

        foreach ( $this->registry->get_storage_providers() as $id => $provider ) {
            $providers[ $id ] = array(
                'id'          => $provider->get_id(),
                'label'       => $provider->get_label(),
                'available'   => $provider->is_available(),
                'recommended' => 'videopress' === $id,
                'fallback'    => 'local_media' === $id,
            );
        }

        return $providers;
    }
}
