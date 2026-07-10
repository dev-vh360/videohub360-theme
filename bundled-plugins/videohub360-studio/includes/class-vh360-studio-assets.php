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
                'uploadingChunk'        => __( 'Recording stopped. Uploading remaining replay data.', 'videohub360-studio' ),
                'uploadRetry'           => __( 'Retrying upload…', 'videohub360-studio' ),
                'finalizing'            => __( 'Preparing replay…', 'videohub360-studio' ),
                'recordingSaved'        => __( 'Recording saved. Prepare replay when ready.', 'videohub360-studio' ),
                'chunkUploadFailed'     => __( 'Some chunks failed to upload. Retry failed chunks before preparing the replay.', 'videohub360-studio' ),
                'recorderUnavailable'   => __( 'Browser recorder is unavailable.', 'videohub360-studio' ),
                'recordingCancelled'    => __( 'Recording cancelled.', 'videohub360-studio' ),
                'publishReplay'         => __( 'Publish replay', 'videohub360-studio' ),
                'publishingReplay'      => __( 'Publishing replay. This may take a moment.', 'videohub360-studio' ),
                'publishStatusChecking' => __( 'Checking publishing status…', 'videohub360-studio' ),
                'publishComplete'       => __( 'Replay published.', 'videohub360-studio' ),
                'publishProcessing'     => __( 'Replay uploaded. Waiting for replay processing.', 'videohub360-studio' ),
                'publishPollingTimeout'  => __( 'Replay is still processing. You can check status again later.', 'videohub360-studio' ),
                'publishFailed'         => __( 'Replay publishing failed. Please try again.', 'videohub360-studio' ),
                'publitioDirectUploading' => __( 'Uploading through fast cloud upload…', 'videohub360-studio' ),
                'publitioDirectVerifying' => __( 'Cloud upload complete. Verifying replay…', 'videohub360-studio' ),
                'publitioDirectFallback' => __( 'Fast cloud upload failed. Using server relay fallback.', 'videohub360-studio' ),
                'goLive'                => __( 'Go Live', 'videohub360-studio' ),
                'goingLive'             => __( 'Starting the live connection and publishing the Program output…', 'videohub360-studio' ),
                'liveStarted'           => __( 'Live broadcast started.', 'videohub360-studio' ),
                'liveEnded'             => __( 'Live broadcast ended.', 'videohub360-studio' ),
                'broadcastFailed'       => __( 'Broadcast could not start. Check live connection settings and device permissions.', 'videohub360-studio' ),
                'liveMicControlsAfterConnect' => __( 'Live microphone controls are available after the broadcast connects.', 'videohub360-studio' ),
                'liveAudioControlsUnavailable' => __( 'Live audio controls are unavailable for this broadcast session.', 'videohub360-studio' ),
                'liveMicrophoneTrackUnavailable' => __( 'Live microphone track is unavailable.', 'videohub360-studio' ),
                'liveMicrophoneMuted' => __( 'Live microphone muted.', 'videohub360-studio' ),
                'liveMicrophoneUnmuted' => __( 'Live microphone unmuted.', 'videohub360-studio' ),
                'liveMicrophoneUpdateFailed' => __( 'Live microphone could not be updated. Check that the microphone track is available.', 'videohub360-studio' ),
                'liveMicrophoneDisconnected' => __( 'Microphone input ended or was disconnected. Live audio may be unavailable.', 'videohub360-studio' ),
                'liveConnectionState' => __( 'Live connection: {state}{reason}', 'videohub360-studio' ),
                'addAudioInput'         => __( 'Add Audio Input', 'videohub360-studio' ),
                'primaryAudioInputBadge' => __( 'Primary', 'videohub360-studio' ),
                'primaryAudioInputLabel' => __( 'Mic/Aux', 'videohub360-studio' ),
                'removeAudioInput'      => __( 'Remove', 'videohub360-studio' ),
                'removeAudioInputLabel' => __( 'Remove', 'videohub360-studio' ),
                'muteAudioInputAction'  => __( 'Mute', 'videohub360-studio' ),
                'unmuteAudioInputAction' => __( 'Unmute', 'videohub360-studio' ),
                'defaultMicrophone'     => __( 'Default microphone', 'videohub360-studio' ),
                'microphoneFallbackLabel' => __( 'Microphone', 'videohub360-studio' ),
                'savedMicrophoneLabel'  => __( 'Saved microphone', 'videohub360-studio' ),
                'audioInputFallbackLabel' => __( 'Audio Input', 'videohub360-studio' ),
                'audioInputNameLabel'   => __( 'name', 'videohub360-studio' ),
                'audioInputDeviceLabel' => __( 'device', 'videohub360-studio' ),
                'audioInputGainLabel'   => __( 'gain', 'videohub360-studio' ),
                'audioInputLevelLabel'  => __( 'level', 'videohub360-studio' ),
                'audioInputStatusLabel' => __( 'status', 'videohub360-studio' ),
                'audioStatusOff'        => __( 'Off', 'videohub360-studio' ),
                'audioStatusConnecting' => __( 'Connecting', 'videohub360-studio' ),
                'audioStatusActive'     => __( 'Active', 'videohub360-studio' ),
                'audioStatusMuted'      => __( 'Muted', 'videohub360-studio' ),
                'audioStatusUnavailable' => __( 'Unavailable', 'videohub360-studio' ),
                'audioStatusDisconnected' => __( 'Disconnected', 'videohub360-studio' ),
                'audioStatusPermissionRequired' => __( 'Permission required', 'videohub360-studio' ),
                'audioStatusError'      => __( 'Error', 'videohub360-studio' ),
                'audioStatusRemoved'    => __( 'Removed', 'videohub360-studio' ),
                'noMicrophoneInputsActive' => __( 'No microphone inputs are active. Studio will continue without microphone audio.', 'videohub360-studio' ),
                'noMicrophoneInputsActiveShort' => __( 'No microphone inputs active.', 'videohub360-studio' ),
                'primaryMicAlreadyActive' => __( 'Primary microphone is already active. Watch the Mic/Aux meter for live levels.', 'videohub360-studio' ),
                'selectedAudioDeviceUnavailable' => __( 'Selected audio device is unavailable. Choose another device for this input.', 'videohub360-studio' ),
                'duplicateMicrophoneSelection' => __( 'Duplicate microphone selection warning.', 'videohub360-studio' ),
                'deviceAlsoSelectedPrefix' => __( 'Also selected on', 'videohub360-studio' ),
                'audioInputLimitReached' => __( 'Studio supports up to 8 audio inputs in this phase.', 'videohub360-studio' ),
                'audioInputAdded'       => __( 'Audio input added.', 'videohub360-studio' ),
                'audioInputRemoved'     => __( 'Audio input removed.', 'videohub360-studio' ),
                'oneAudioInputUnavailable' => __( 'One saved audio input is unavailable.', 'videohub360-studio' ),
                'audioInputDisconnected' => __( 'Audio input disconnected.', 'videohub360-studio' ),
                'audioInputDisconnectedDetail' => __( 'This audio device disconnected. Reconnect it or choose another device.', 'videohub360-studio' ),
                'primaryMicrophoneFallback' => __( 'The selected microphone is no longer available. Studio will retry with the default microphone…', 'videohub360-studio' ),
                'audioInputsActiveSummary' => __( '{active} audio input(s) active.', 'videohub360-studio' ),
                'audioInputsUnavailableSummary' => __( '{count} audio input(s) unavailable.', 'videohub360-studio' ),
                'audioInputsOffSummary' => __( '{count} audio input(s) off.', 'videohub360-studio' ),
                'livePartialAudioInputs' => __( 'Live will start with {active} audio input(s). {failed} configured input(s) are unavailable.', 'videohub360-studio' ),
                'recordingPartialAudioInputs' => __( 'Recording will start with {active} audio input(s). {failed} configured input(s) are unavailable.', 'videohub360-studio' ),
                'cameraSummaryLabel'    => __( 'Camera', 'videohub360-studio' ),
                'cameraOnlyLabel'       => __( 'Camera Only', 'videohub360-studio' ),
                'addVideoCaptureDevice' => __( 'Add Video Capture Device', 'videohub360-studio' ),
                'cameraSourceDefaultName' => __( 'Camera {number}', 'videohub360-studio' ),
                'chooseVideoDevice'     => __( 'Choose video device', 'videohub360-studio' ),
                'cameraSourceAdded'     => __( 'Camera source added.', 'videohub360-studio' ),
                'cameraSourceRemoved'   => __( 'Camera source removed.', 'videohub360-studio' ),
                'cameraSourceDisconnected' => __( 'Camera source disconnected.', 'videohub360-studio' ),
                'selectedCameraUnavailable' => __( 'Selected camera is unavailable. Choose another device for this source.', 'videohub360-studio' ),
                'deviceAlreadyAssigned' => __( 'Device already assigned.', 'videohub360-studio' ),
                'noUnassignedCamerasAvailable' => __( 'No unassigned cameras available.', 'videohub360-studio' ),
                'primaryCameraCannotBeRemoved' => __( 'Primary camera cannot be removed.', 'videohub360-studio' ),
                'cameraSourceStatusActive' => __( 'active', 'videohub360-studio' ),
                'cameraSourceStatusOff' => __( 'off', 'videohub360-studio' ),
                'cameraSourceStatusConnecting' => __( 'connecting', 'videohub360-studio' ),
                'cameraSourceStatusUnavailable' => __( 'unavailable', 'videohub360-studio' ),
                'cameraSourceStatusDisconnected' => __( 'disconnected', 'videohub360-studio' ),
                'cameraSourceStatusError' => __( 'error', 'videohub360-studio' ),
                'cameraSourcesActiveSummary' => __( '{active} camera source(s) active.', 'videohub360-studio' ),
                'cameraSourcesUnavailableSummary' => __( '{count} camera source(s) unavailable.', 'videohub360-studio' ),
                'duplicateCameraSelection' => __( 'Duplicate camera assignment warning.', 'videohub360-studio' ),
                'oneCameraSourceUnavailable' => __( 'One saved camera source is unavailable.', 'videohub360-studio' ),
                'savedCameraLabel'      => __( 'Saved camera', 'videohub360-studio' ),
                'cameraSourceAlreadyActive' => __( 'Camera source is already active.', 'videohub360-studio' ),
                'protectedCameraSource' => __( 'This camera is currently in Program. Send another source to Program before changing it.', 'videohub360-studio' ),
                'removeCameraSourceConfirm' => __( 'Remove this video capture device source?', 'videohub360-studio' ),
                'configuredCameraSourcesReadinessLabel' => __( 'Configured camera sources', 'videohub360-studio' ),
                'chooseProgramSourceBeforeLive' => __( 'Choose a Program source before going live.', 'videohub360-studio' ),
                'primaryMicrophoneSummaryLabel' => __( 'Primary microphone', 'videohub360-studio' ),
                'cameraSelectedReadinessLabel' => __( 'Camera selected', 'videohub360-studio' ),
                'primaryMicrophoneSelectedReadinessLabel' => __( 'Primary microphone selected', 'videohub360-studio' ),
                'configuredAudioInputsReadinessLabel' => __( 'Configured audio inputs', 'videohub360-studio' ),
                'microphoneTestingUnavailable' => __( 'Microphone testing is unavailable in this browser.', 'videohub360-studio' ),
                'stopLiveRecordingBeforeMicTest' => __( 'Stop live/recording before testing a different microphone.', 'videohub360-studio' ),
                'primaryMicrophoneMissing' => __( 'Primary microphone input is missing. Refresh Studio and try again.', 'videohub360-studio' ),
                'microphoneTestAlreadyRunning' => __( 'Microphone test is already running.', 'videohub360-studio' ),
                'testingSelectedMicrophone' => __( 'Testing selected microphone. Watch the Mic/Aux meter…', 'videohub360-studio' ),
                'microphoneTestStarted' => __( 'Microphone test started: {device}.', 'videohub360-studio' ),
                'microphonePermissionNoDevices' => __( 'Microphone permission was requested, but the browser still reports zero microphones. Check OS privacy settings, USB/audio hardware, and browser device permissions.', 'videohub360-studio' ),
                'microphoneCaptureUnavailable' => __( 'Microphone capture is unavailable in this browser.', 'videohub360-studio' ),
                'deviceRefreshUnavailable' => __( 'Device refresh is unavailable in this browser.', 'videohub360-studio' ),
                'savedCameraUnavailable' => __( 'Your saved camera is no longer connected. Studio selected the default camera.', 'videohub360-studio' ),
                'devicesRefreshedLiveUnchanged' => __( 'Devices refreshed. Current live devices were not changed.', 'videohub360-studio' ),
                'deviceChangeNewCamera' => __( 'Device change detected. Cameras and microphones refreshed. New camera detected. Choose it from Sources.', 'videohub360-studio' ),
                'noCamerasDetectedDetail' => __( 'No cameras detected. Plug in a camera, grant browser permission, then refresh devices.', 'videohub360-studio' ),
                'camerasDetectedNoMicrophones' => __( '{cameraLabel} detected. No microphones detected.', 'videohub360-studio' ),
                'devicesDetected' => __( '{cameraLabel} and {micLabel} detected.', 'videohub360-studio' ),
                'devicesDetectedAfterChange' => __( '{cameraLabel} and {micLabel} detected after a device change.', 'videohub360-studio' ),
                'refreshingDevices' => __( 'Refreshing cameras and microphones…', 'videohub360-studio' ),
                'deviceChangeRefreshing' => __( 'Device change detected. Refreshing cameras and microphones…', 'videohub360-studio' ),
                'oneCameraDetected' => __( '1 camera', 'videohub360-studio' ),
                'multipleCamerasDetected' => __( '{count} cameras', 'videohub360-studio' ),
                'oneMicrophoneDetected' => __( '1 microphone', 'videohub360-studio' ),
                'multipleMicrophonesDetected' => __( '{count} microphones', 'videohub360-studio' ),
                'screenShareLabel'     => __( 'Screen Share', 'videohub360-studio' ),
                'mediaAssetLabel'      => __( 'Media/Asset', 'videohub360-studio' ),
                'cameraTestingUnavailable' => __( 'Camera testing is unavailable in this browser.', 'videohub360-studio' ),
                'stopLiveRecordingBeforeCameraTest' => __( 'Stop live/recording before testing a different camera.', 'videohub360-studio' ),
                'testingSelectedCamera' => __( 'Testing selected camera…', 'videohub360-studio' ),
                'cameraTestPassed'     => __( 'Camera test passed: {device}.', 'videohub360-studio' ),
                'defaultCamera'        => __( 'default camera', 'videohub360-studio' ),
                'selectedCameraUnavailableRetry' => __( 'The selected device is no longer available. Studio will retry with the default camera…', 'videohub360-studio' ),
                'devicesCouldNotBeRefreshed' => __( 'Devices could not be refreshed.', 'videohub360-studio' ),
                'deviceUnavailableOption' => __( '{device} unavailable', 'videohub360-studio' ),
                'deviceFallbackOption' => __( '{device} {index}', 'videohub360-studio' ),
                'deviceStateOption'    => __( '{device} ({status})', 'videohub360-studio' ),
                'chooseAudioDevice'    => __( 'Choose audio device', 'videohub360-studio' ),
                'permissionStatePrompt' => __( 'Prompt', 'videohub360-studio' ),
                'permissionStateAllowed' => __( 'Allowed', 'videohub360-studio' ),
                'permissionStateBlocked' => __( 'Blocked', 'videohub360-studio' ),
                'cameraPermissionReadinessLabel' => __( 'Camera permission', 'videohub360-studio' ),
                'microphonePermissionReadinessLabel' => __( 'Microphone permission', 'videohub360-studio' ),
                'cameraMicrophonePermissionBlocked' => __( 'Camera or microphone permission was blocked. Allow access in your browser site settings and macOS Privacy & Security, then refresh devices.', 'videohub360-studio' ),
                'noMatchingCameraMicrophone' => __( 'No matching camera or microphone was found. Check the USB connection and click Refresh Devices.', 'videohub360-studio' ),
                'cameraMicrophoneAlreadyInUse' => __( 'The camera or microphone is already in use by another app. Close OBSBOT Center, Zoom, FaceTime, OBS, or other camera apps, then try again.', 'videohub360-studio' ),
                'selectedDeviceUnavailableRetryDefault' => __( 'The selected device is no longer available. Studio will retry with the default device.', 'videohub360-studio' ),
                'readinessHttpsRequired' => __( 'Use HTTPS or localhost for Studio recording.', 'videohub360-studio' ),
                'readinessCameraMicrophoneUnavailable' => __( 'Camera or microphone access is unavailable. Allow browser permissions, then refresh.', 'videohub360-studio' ),
                'readinessScreenShareUnavailable' => __( 'Screen sharing is unavailable in this browser.', 'videohub360-studio' ),
                'readinessRecordingUnsupported' => __( 'This browser does not support recording. Try Chrome, Edge, or Safari.', 'videohub360-studio' ),
                'readinessCanvasUnsupported' => __( 'Program canvas recording is unsupported in this browser.', 'videohub360-studio' ),
                'readinessNoRecordingFormat' => __( 'No supported recording format was detected.', 'videohub360-studio' ),
                'readinessNeedsAttention' => __( 'Studio needs attention.', 'videohub360-studio' ),
                'readinessReadyToGoLive' => __( 'Ready to go live.', 'videohub360-studio' ),
                'readinessResolveItems' => __( 'Resolve the items below, then refresh Studio if needed.', 'videohub360-studio' ),
                'readinessAllSupported' => __( 'Camera, microphone, screen share, and recording are supported.', 'videohub360-studio' ),
                'formatsFallbackLabel' => __( 'Formats', 'videohub360-studio' ),
                'recordingFormatLabel' => __( 'Recording format', 'videohub360-studio' ),
                'largeRecordingSplit' => __( 'Large recording data was split into smaller upload chunks.', 'videohub360-studio' ),
                'retryingServerStopConfirmation' => __( 'Retrying server stop confirmation…', 'videohub360-studio' ),
                'recordingStoppedFastCloudReady' => __( 'Recording stopped. Ready for fast cloud upload.', 'videohub360-studio' ),
                'confirmServerStopBeforeReplay' => __( 'Confirm server stop before preparing the replay.', 'videohub360-studio' ),
                'retryFailedChunksBeforeReplay' => __( 'Retry failed chunks during this session before preparing the replay.', 'videohub360-studio' ),
                'noRecordingChunksCaptured' => __( 'No recording chunks were captured. Start a new recording and try again.', 'videohub360-studio' ),
                'replayPreparedReadyToPublish' => __( 'Replay prepared. You can publish it now.', 'videohub360-studio' ),

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
