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
        add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_mobile_orientation_lock_for_mobile_live' ), 100 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_live_room_recording_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shared_video_uploader' ) );
        add_filter( 'vh360_agora_host_controls_html', array( $this, 'render_live_room_record_control' ), 10, 4 );
        add_action( 'wp_footer', array( $this, 'render_live_room_recording_notice' ) );
    }



    private function recording_session_config( $post_id ) {
        if ( ! $post_id || 'videohub360' !== get_post_type( $post_id ) ) { return null; }
        $is_studio_interactive_post = 'yes' === get_post_meta( $post_id, '_vh360_studio_controlled_live', true ) && 'agora' === get_post_meta( $post_id, '_vh360_type', true ) && 'interactive' === get_post_meta( $post_id, '_vh360_agora_mode', true ) && '' === (string) get_post_meta( $post_id, '_vh360_appointment_event_id', true );
        $is_studio_interactive_live = $is_studio_interactive_post && 'yes' === get_post_meta( $post_id, '_vh360_is_live', true ) && 'yes' === get_post_meta( $post_id, '_vh360_agora_stream_live', true ) && 'yes' !== get_post_meta( $post_id, '_vh360_stream_stopped', true );
        $has_recoverable_studio_recording = $is_studio_interactive_post && $this->studio_interactive_recording_needs_assets( $post_id );
        if ( $is_studio_interactive_live || $has_recoverable_studio_recording ) {
            $preset = VH360_Studio_Quality_Presets::normalize( get_post_meta( $post_id, '_vh360_studio_quality_preset', true ) ?: VH360_Studio_Quality_Presets::DEFAULT_PRESET );
            return array( 'sessionKind' => 'studio_interactive', 'recordingPurpose' => 'studio_interactive', 'canRecord' => VH360_Studio_Permissions::current_user_can_record_studio_interactive_livestream( $post_id ), 'canStartNewRecording' => $is_studio_interactive_live, 'stateEndpoint' => '/broadcasts/' . $post_id . '/recording', 'createEndpoint' => '/broadcasts/' . $post_id . '/recordings', 'heartbeatEndpoint' => '/broadcasts/' . $post_id . '/recordings/{job_id}/heartbeat', 'recoverEndpoint' => '/broadcasts/' . $post_id . '/recordings/{job_id}/recover', 'recordButtonLabel' => __( 'Record Session', 'videohub360-studio' ), 'recordingLabel' => __( '● Recording ', 'videohub360-studio' ), 'qualityPreset' => $preset, 'qualityPresetSettings' => VH360_Studio_Quality_Presets::get_preset( $preset ) );
        }
        if ( 'live_room' !== get_post_meta( $post_id, '_vh360_context', true ) ) { return null; }
        $purpose = '' !== (string) get_post_meta( $post_id, '_vh360_appointment_event_id', true ) ? 'appointment_session' : 'ordinary_live_room';
        return array( 'sessionKind' => 'appointment_session' === $purpose ? 'appointment_session' : 'live_room', 'recordingPurpose' => $purpose, 'canRecord' => VH360_Studio_Permissions::current_user_can_record_live_room( $post_id ), 'stateEndpoint' => '/live-rooms/' . $post_id . '/recording', 'createEndpoint' => '/live-rooms/' . $post_id . '/recordings', 'heartbeatEndpoint' => '/live-rooms/' . $post_id . '/recordings/{job_id}/heartbeat', 'recoverEndpoint' => '/live-rooms/' . $post_id . '/recordings/{job_id}/recover', 'recordButtonLabel' => 'appointment_session' === $purpose ? __( 'Record Privately', 'videohub360-studio' ) : __( 'Record', 'videohub360-studio' ), 'recordingLabel' => 'appointment_session' === $purpose ? __( '● Private Recording ', 'videohub360-studio' ) : __( '● Recording ', 'videohub360-studio' ), 'qualityPreset' => VH360_Studio_Quality_Presets::DEFAULT_PRESET, 'qualityPresetSettings' => VH360_Studio_Quality_Presets::get_preset( VH360_Studio_Quality_Presets::DEFAULT_PRESET ) );
    }


    private function studio_interactive_recording_needs_assets( $post_id ) {
        if ( ! VH360_Studio_Permissions::current_user_can_record_studio_interactive_livestream( $post_id ) ) {
            return false;
        }
        global $wpdb;
        $table = VH360_Studio_Database::table_name();
        $job = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$table} WHERE source_type = 'livestream_video' AND capture_scope = 'interactive_composite' AND live_video_id = %d AND status IN ('created','recording','stopping','uploading','processing','failed') ORDER BY created_at DESC LIMIT 1", absint( $post_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $job ) {
            return true;
        }
        return 'yes' === get_post_meta( $post_id, '_vh360_studio_replay_pending', true ) || in_array( get_post_meta( $post_id, '_vh360_studio_replay_status', true ), array( 'finalization_failed', 'publishing_prepare_failed', 'publishing_start_failed' ), true );
    }

    public function enqueue_live_room_recording_assets() {
        if ( ! is_singular( 'videohub360' ) ) { return; }
        $post_id = get_queried_object_id();
        $session = $this->recording_session_config( $post_id );
        if ( ! $session ) { return; }
        $css = 'assets/css/studio-live-room-recorder.css';
        $indicator = 'assets/js/studio-live-room-recorder.js';
        $recorder_dependencies = array( 'vh360-studio-recording-client' );

        wp_enqueue_style( 'vh360-studio-live-room-recorder', VH360_STUDIO_PLUGIN_URL . $css, array(), $this->asset_version( $css ) );
        wp_enqueue_script( 'vh360-studio-recording-client', VH360_STUDIO_PLUGIN_URL . 'assets/js/studio-recording-client.js', array(), $this->asset_version( 'assets/js/studio-recording-client.js' ), true );

        if ( ! empty( $session['canRecord'] ) ) {
            wp_enqueue_script( 'vh360-studio-live-room-compositor', VH360_STUDIO_PLUGIN_URL . 'assets/js/studio-live-room-compositor.js', array(), $this->asset_version( 'assets/js/studio-live-room-compositor.js' ), true );
            wp_enqueue_script( 'vh360-studio-live-room-audio-mixer', VH360_STUDIO_PLUGIN_URL . 'assets/js/studio-live-room-audio-mixer.js', array(), $this->asset_version( 'assets/js/studio-live-room-audio-mixer.js' ), true );
            $recorder_dependencies[] = 'vh360-studio-live-room-compositor';
            $recorder_dependencies[] = 'vh360-studio-live-room-audio-mixer';
        }

        wp_enqueue_script( 'vh360-studio-live-room-recorder', VH360_STUDIO_PLUGIN_URL . $indicator, $recorder_dependencies, $this->asset_version( $indicator ), true );
        wp_localize_script( 'vh360-studio-live-room-recorder', 'vh360StudioLiveRoomRecorder', array_merge( array( 'restRoot' => esc_url_raw( rest_url( 'vh360-studio/v1' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'postId' => $post_id, 'desktopOnlyMessage' => __( 'Recording is available in supported desktop browsers.', 'videohub360-studio' ), 'appointmentPrivateMessage' => __( 'The recording will be saved to this device and will not be published as a replay or uploaded by VideoHub360.', 'videohub360-studio' ) ), $session ) );
    }

    public function render_live_room_record_control( $html, $post_id, $fields, $context ) {
        $session = $this->recording_session_config( $post_id );
        if ( $session && ! empty( $session['canRecord'] ) ) {
            $html .= '<button type="button" id="vh360-studio-live-room-record" class="vh360-agora-control-btn vh360-agora-control-btn-text vh360-studio-record-btn vh360-hidden" data-recording-purpose="' . esc_attr( $session['recordingPurpose'] ) . '">' . esc_html( $session['recordButtonLabel'] ) . '</button>';
        }
        return $html;
    }

    public function render_live_room_recording_notice() {
        if ( ! is_singular( 'videohub360' ) ) { return; }
        $post_id = get_queried_object_id();
        $session = $this->recording_session_config( $post_id );
        if ( ! $session ) { return; }
        $label = 'studio_interactive' === $session['recordingPurpose'] ? __( 'This interactive session is being recorded.', 'videohub360-studio' ) : ( 'appointment_session' === $session['recordingPurpose'] ? __( 'This appointment is being recorded.', 'videohub360-studio' ) : __( 'This Live Room is being recorded.', 'videohub360-studio' ) );
        echo '<div class="vh360-live-room-recording-notice vh360-studio-recording-indicator vh360-hidden" aria-live="polite" aria-label="' . esc_attr( $label ) . '"><span aria-hidden="true">●</span><span>REC</span></div>';
    }

    public function enqueue_shared_video_uploader() {
        $should_enqueue = is_page_template( 'template-dashboard.php' ) || is_page_template( 'templates/dashboard.php' ) || is_page_template( 'template-activity-feed.php' );
        $should_enqueue = (bool) apply_filters( 'vh360_studio_enqueue_video_uploader', $should_enqueue );
        if ( ! $should_enqueue ) { return; }
        $path = 'assets/js/studio-video-upload.js';
        wp_enqueue_script( 'vh360-studio-video-upload', VH360_STUDIO_PLUGIN_URL . $path, array(), $this->asset_version( $path ), true );
        wp_localize_script( 'vh360-studio-video-upload', 'vh360StudioVideoUpload', array( 'restRoot' => esc_url_raw( rest_url( 'vh360-studio/v1' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
    }

    public function enqueue_dashboard_assets() {
        if ( ! $this->is_studio_dashboard_tab() || ! VH360_Studio_Permissions::user_can_access_studio() ) {
            return;
        }

        if ( ! VH360_Studio_Permissions::license_is_valid() ) {
            $locked_css = 'assets/css/studio-license-lock.css';

            wp_enqueue_style(
                'vh360-studio-license-lock',
                VH360_STUDIO_PLUGIN_URL . $locked_css,
                array(),
                $this->asset_version( $locked_css )
            );

            return;
        }


        $mode = class_exists( 'VH360_Studio_Plugin' ) ? VH360_Studio_Plugin::resolve_studio_mode() : 'entry';
        if ( 'entry' === $mode ) {
            $css_path = 'assets/css/studio-entry-router.css';
            $js_path  = 'assets/js/studio-entry-router.js';
            wp_enqueue_style( 'vh360-studio-entry-router', VH360_STUDIO_PLUGIN_URL . $css_path, array(), $this->asset_version( $css_path ) );
            wp_enqueue_script( 'vh360-studio-entry-router', VH360_STUDIO_PLUGIN_URL . $js_path, array(), $this->asset_version( $js_path ), true );
            return;
        }
        if ( 'mobile' === $mode ) {
            $css_path          = 'assets/css/studio-mobile-live.css';
            $participants_path = 'assets/js/studio-mobile-participants.js';
            $js_path           = 'assets/js/studio-mobile-live.js';
            wp_enqueue_style( 'vh360-studio-mobile-live', VH360_STUDIO_PLUGIN_URL . $css_path, array(), $this->asset_version( $css_path ) );
            wp_enqueue_script( 'agora-rtc-sdk', 'https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js', array(), '4.20.0', true );
            wp_enqueue_script( 'vh360-agora-broadcaster', VIDEOHUB360_ASSETS_URL . 'js/agora-broadcaster.js', array( 'agora-rtc-sdk' ), videohub360_asset_version( 'assets/js/agora-broadcaster.js' ), true );
            wp_enqueue_script( 'vh360-studio-mobile-participants', VH360_STUDIO_PLUGIN_URL . $participants_path, array( 'vh360-agora-broadcaster', 'vh360-studio-recording-client' ), $this->asset_version( $participants_path ), true );
            wp_enqueue_script( 'vh360-studio-mobile-live', VH360_STUDIO_PLUGIN_URL . $js_path, array( 'vh360-studio-mobile-participants' ), $this->asset_version( $js_path ), true );
            wp_localize_script( 'vh360-studio-mobile-live', 'vh360StudioMobileLive', $this->mobile_localized_data() );
            wp_dequeue_style( 'vh360-mobile-orientation-lock' );
            return;
        }

        $css_path                = 'assets/css/studio-dashboard.css';
        $overlays_css_path       = 'assets/css/studio-overlays-workspace.css';
        $overlay_engine_css_path = 'assets/css/studio-overlay-engine.css';
        $lower_thirds_css_path   = 'assets/css/studio-lower-thirds.css';
        $countdown_css_path      = 'assets/css/studio-countdown.css';
        $bible_css_path          = 'assets/css/studio-bible.css';
        $js_path                 = 'assets/js/studio-dashboard.js';
        $overlays_workspace_path = 'assets/js/studio-overlays-workspace.js';
        $dock_layout_path         = 'assets/js/studio-dock-layout.js';
        $overlay_engine_path     = 'assets/js/studio-overlay-engine.js';
        $overlay_status_path     = 'assets/js/studio-overlay-status.js';
        $lower_thirds_path       = 'assets/js/studio-lower-thirds.js';
        $countdown_path          = 'assets/js/studio-countdown.js';
        $bible_path              = 'assets/js/studio-bible.js';

        wp_enqueue_style(
            'vh360-studio-dashboard',
            VH360_STUDIO_PLUGIN_URL . $css_path,
            array(),
            $this->asset_version( $css_path )
        );

        wp_enqueue_style(
            'vh360-studio-overlays-workspace',
            VH360_STUDIO_PLUGIN_URL . $overlays_css_path,
            array( 'vh360-studio-dashboard' ),
            $this->asset_version( $overlays_css_path )
        );

        wp_enqueue_style(
            'vh360-studio-overlay-engine',
            VH360_STUDIO_PLUGIN_URL . $overlay_engine_css_path,
            array( 'vh360-studio-overlays-workspace' ),
            $this->asset_version( $overlay_engine_css_path )
        );

        wp_enqueue_style(
            'vh360-studio-lower-thirds',
            VH360_STUDIO_PLUGIN_URL . $lower_thirds_css_path,
            array( 'vh360-studio-overlay-engine' ),
            $this->asset_version( $lower_thirds_css_path )
        );

        wp_enqueue_style(
            'vh360-studio-countdown',
            VH360_STUDIO_PLUGIN_URL . $countdown_css_path,
            array( 'vh360-studio-lower-thirds' ),
            $this->asset_version( $countdown_css_path )
        );

        wp_enqueue_style(
            'vh360-studio-bible',
            VH360_STUDIO_PLUGIN_URL . $bible_css_path,
            array( 'vh360-studio-countdown' ),
            $this->asset_version( $bible_css_path )
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
            'vh360-studio-recording-client',
            VH360_STUDIO_PLUGIN_URL . 'assets/js/studio-recording-client.js',
            array(),
            $this->asset_version( 'assets/js/studio-recording-client.js' ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-dashboard',
            VH360_STUDIO_PLUGIN_URL . $js_path,
            array( 'vh360-agora-broadcaster', 'vh360-studio-recording-client' ),
            $this->asset_version( $js_path ),
            true
        );

        wp_localize_script( 'vh360-studio-dashboard', 'vh360StudioDashboard', $this->localized_data() );

        wp_enqueue_script(
            'vh360-studio-overlay-engine',
            VH360_STUDIO_PLUGIN_URL . $overlay_engine_path,
            array( 'vh360-studio-dashboard' ),
            $this->asset_version( $overlay_engine_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-overlay-status',
            VH360_STUDIO_PLUGIN_URL . $overlay_status_path,
            array( 'vh360-studio-overlay-engine' ),
            $this->asset_version( $overlay_status_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-lower-thirds',
            VH360_STUDIO_PLUGIN_URL . $lower_thirds_path,
            array( 'vh360-studio-overlay-status' ),
            $this->asset_version( $lower_thirds_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-countdown',
            VH360_STUDIO_PLUGIN_URL . $countdown_path,
            array( 'vh360-studio-lower-thirds' ),
            $this->asset_version( $countdown_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-bible',
            VH360_STUDIO_PLUGIN_URL . $bible_path,
            array( 'vh360-studio-countdown' ),
            $this->asset_version( $bible_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-overlays-workspace',
            VH360_STUDIO_PLUGIN_URL . $overlays_workspace_path,
            array( 'vh360-studio-bible' ),
            $this->asset_version( $overlays_workspace_path ),
            true
        );

        wp_enqueue_script(
            'vh360-studio-dock-layout',
            VH360_STUDIO_PLUGIN_URL . $dock_layout_path,
            array( 'vh360-studio-overlays-workspace' ),
            $this->asset_version( $dock_layout_path ),
            true
        );
    }

    public function dequeue_mobile_orientation_lock_for_mobile_live() {
        if ( $this->is_studio_dashboard_tab() && class_exists( 'VH360_Studio_Plugin' ) && 'mobile' === VH360_Studio_Plugin::resolve_studio_mode() ) {
            wp_dequeue_style( 'vh360-mobile-orientation-lock' );
        }
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

    private function mobile_localized_data() {
        return array(
            'restRoot'          => esc_url_raw( rest_url( 'vh360-studio/v1' ) ),
            'nonce'             => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'identityNonce'     => wp_create_nonce( 'vh360_agora_identity' ),
            'currentUserId'     => get_current_user_id(),
            'mobileVideoConfig' => array(
                'facingMode'       => 'user',
                'encoderConfig'    => array( 'width' => 1280, 'height' => 720, 'frameRate' => 30, 'bitrateMin' => 800, 'bitrateMax' => 1800 ),
                'optimizationMode' => 'balanced',
            ),
            'mobileAudioConfig' => array(),
            'strings'           => array(
                'requestFailed'             => __( 'Request failed. Please try again.', 'videohub360-studio' ),
                'requestingPermissions'     => __( 'Requesting camera and microphone permissions…', 'videohub360-studio' ),
                'previewReady'              => __( 'Camera and microphone preview is ready.', 'videohub360-studio' ),
                'permissionFailed'          => __( 'Camera or microphone access failed. Check browser permissions and try again.', 'videohub360-studio' ),
                'cameraRequired'            => __( 'A working camera preview is required before going live.', 'videohub360-studio' ),
                'titleRequired'             => __( 'Enter a title before going live.', 'videohub360-studio' ),
                'passcodeRequired'          => __( 'Enter a host passcode or turn off passcode access.', 'videohub360-studio' ),
                'creatingBroadcast'         => __( 'Creating livestream…', 'videohub360-studio' ),
                'connectingLiveService'     => __( 'Connecting to the live service…', 'videohub360-studio' ),
                'liveStarted'               => __( 'You are live. Keep this browser open.', 'videohub360-studio' ),
                'startFailed'               => __( 'The livestream could not start. Devices and server state were cleaned up when possible.', 'videohub360-studio' ),
                'cleanupPending'            => __( 'A previous start attempt needs server cleanup before you can start another live.', 'videohub360-studio' ),
                'endConfirm'                => __( 'End this livestream?', 'videohub360-studio' ),
                'endingLive'                => __( 'Ending livestream…', 'videohub360-studio' ),
                'ended'                     => __( 'Livestream ended.', 'videohub360-studio' ),
                'endFailed'                 => __( 'The local stream stopped, but the server has not confirmed End Live. Retry End Live.', 'videohub360-studio' ),
                'connected'                 => __( 'Connected', 'videohub360-studio' ),
                'reconnecting'              => __( 'Reconnecting… keep this page open. End Live remains available.', 'videohub360-studio' ),
                'disconnected'              => __( 'Disconnected. Try to reconnect or end the livestream.', 'videohub360-studio' ),
                'tokenRenewalFailed'        => __( 'Live connection renewal failed. The app will retry.', 'videohub360-studio' ),
                'cameraSwitchFailed'        => __( 'Camera could not be switched.', 'videohub360-studio' ),
                'cameraNotConnected'        => __( 'Camera: not connected', 'videohub360-studio' ),
                'microphoneNotConnected'    => __( 'Microphone: not connected', 'videohub360-studio' ),
                'connectionNotLive'         => __( 'Connection: not live', 'videohub360-studio' ),
                'cameraConnected'           => __( 'Camera: connected', 'videohub360-studio' ),
                'microphoneConnected'       => __( 'Microphone: connected', 'videohub360-studio' ),
                'connectionConnected'       => __( 'Connection: connected', 'videohub360-studio' ),
                'connectionLabel'           => __( 'Connection: ', 'videohub360-studio' ),
                'audioToggleFailed'         => __( 'Microphone state could not be changed.', 'videohub360-studio' ),
                'videoToggleFailed'         => __( 'Camera state could not be changed.', 'videohub360-studio' ),
                'tokenRecoveryFailed'       => __( 'Live connection recovery failed. Keep this page open or end the livestream.', 'videohub360-studio' ),
                'localPreviewFailed'        => __( 'The livestream is active, but the local camera preview could not be displayed.', 'videohub360-studio' ),
                'trackEnded'                => __( 'A media device disconnected. Check camera and microphone permissions.', 'videohub360-studio' ),
                'muteMic'                   => __( 'Mute mic', 'videohub360-studio' ),
                'unmuteMic'                 => __( 'Unmute mic', 'videohub360-studio' ),
                'cameraOff'                 => __( 'Camera off', 'videohub360-studio' ),
                'cameraOn'                  => __( 'Camera on', 'videohub360-studio' ),
                'retryCameraMicrophone'     => __( 'Retry Camera and Microphone', 'videohub360-studio' ),
                'participants'              => __( 'Participants', 'videohub360-studio' ),
                'noParticipantsYet'         => __( 'No participants yet', 'videohub360-studio' ),
                'oneParticipant'            => __( 'One participant', 'videohub360-studio' ),
                'participantCount'          => __( '%d participants', 'videohub360-studio' ),
                'participant'               => __( 'Participant', 'videohub360-studio' ),
                'participantJoined'         => __( 'Participant joined', 'videohub360-studio' ),
                'participantLeft'           => __( 'Participant left', 'videohub360-studio' ),
                'microphoneMuted'           => __( 'Microphone muted', 'videohub360-studio' ),
                'enableParticipantAudio'    => __( 'Enable participant audio', 'videohub360-studio' ),
                'participantAudioFailed'    => __( 'Participant audio could not start.', 'videohub360-studio' ),
                'headphonesRecommended'     => __( 'Headphones are recommended when monitoring participant audio.', 'videohub360-studio' ),
                'participantVideoFailed'    => __( 'Interactive participant video could not be displayed.', 'videohub360-studio' ),
                'participantIdentityFailed' => __( 'Participant identity could not be resolved.', 'videohub360-studio' ),
                'you'                       => __( 'You', 'videohub360-studio' ),
                'closeParticipants'         => __( 'Close participants', 'videohub360-studio' ),
                'selectParticipant'         => __( 'Select participant', 'videohub360-studio' ),
            ),
        );
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
            'overlayTools'              => array(
                'allowedModules' => VH360_Studio_User_Preferences::allowed_overlay_modules(),
                'enabledModules' => VH360_Studio_User_Preferences::get_enabled_overlay_modules( get_current_user_id() ),
            ),
            'strings'                   => array(
                'ready'                  => __( 'Ready', 'videohub360-studio' ),
                'overlayToolsSaved'      => __( 'Overlay tools saved.', 'videohub360-studio' ),
                'overlayToolsSaveFailed' => __( 'Overlay tools could not be saved. Please try again.', 'videohub360-studio' ),
                'overlayToolsConfirmDisable' => __( 'One or more disabled overlay tools are active in Preview or Program. Their active overlays will be removed from Preview and Program. Continue?', 'videohub360-studio' ),
                'overlayToolsNoneEnabled' => __( 'No overlay tools enabled.', 'videohub360-studio' ),
                'lowerDockLayout'       => array(
                    'valueText' => __( '%s %d pixels, %s %d pixels', 'videohub360-studio' ),
                ),
                'browserUnsupported'     => __( 'This browser is missing required Studio features.', 'videohub360-studio' ),
                'cameraBlocked'          => __( 'Camera access was blocked. Check browser permissions and try again.', 'videohub360-studio' ),
                'microphoneBlocked'      => __( 'Microphone access was blocked. Check browser permissions and try again.', 'videohub360-studio' ),
                'screenUnsupported'      => __( 'Screen sharing is not supported in this browser.', 'videohub360-studio' ),
                'previewActive'          => __( 'Camera and microphone preview is active.', 'videohub360-studio' ),
                'screenPreviewActive'    => __( 'Screen-share preview is active.', 'videohub360-studio' ),
                'studioHiddenBackgroundWarning' => __( 'Studio is hidden. Browser background limits may reduce the Program frame rate.', 'videohub360-studio' ),
                'studioVisibilityRestored' => __( 'Studio media and Program output restored.', 'videohub360-studio' ),
                'audioContextResumeFailed' => __( 'Studio audio could not resume automatically. Interact with the Studio window and try again.', 'videohub360-studio' ),
                'cameraVisibilityRestartFailed' => __( 'A previously active camera could not restart after Studio became visible.', 'videohub360-studio' ),
                'audioVisibilityRestartFailed' => __( 'A previously active audio input could not restart after Studio became visible.', 'videohub360-studio' ),
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
                'configureAudioInput'  => __( 'Configure audio input', 'videohub360-studio' ),
                'audioInputSettingsTitle' => __( 'Audio Input Settings', 'videohub360-studio' ),
                'doneLabel'            => __( 'Done', 'videohub360-studio' ),
                'currentStatusLabel'   => __( 'Current status', 'videohub360-studio' ),
                'primaryAudioInputDescription' => __( 'Primary audio input', 'videohub360-studio' ),
                'secondaryAudioInputLabel' => __( 'Secondary audio input', 'videohub360-studio' ),
                'audioLevelLabel'      => __( 'Audio level', 'videohub360-studio' ),
                'clippingLabel'        => __( 'Clipping', 'videohub360-studio' ),
                'gainLabel'            => __( 'Gain', 'videohub360-studio' ),
                'hardwareMixerSubchannelsNotice' => __( 'Hardware mixer sub-channels are not separated by Studio', 'videohub360-studio' ),
                'masterOutputLabel'    => __( 'Master Output', 'videohub360-studio' ),
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
                'cameraOne'             => __( 'Camera 1', 'videohub360-studio' ),
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
                'cameraPlaybackNotReady' => __( 'Camera video is not ready yet.', 'videohub360-studio' ),
                'cameraPlaybackFailed' => __( 'Camera playback could not resume.', 'videohub360-studio' ),
                'protectedCameraSource' => __( 'This camera is currently in Program. Send another source to Program before changing it.', 'videohub360-studio' ),
                'removeCameraSourceConfirm' => __( 'Remove this video capture device source?', 'videohub360-studio' ),
                'configuredCameraSourcesReadinessLabel' => __( 'Configured camera sources', 'videohub360-studio' ),
                'chooseProgramSourceBeforeLive' => __( 'Choose a Program source before going live.', 'videohub360-studio' ),
                'chooseProgramSourceBeforeRecording' => __( 'Choose a Program source before recording.', 'videohub360-studio' ),
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

                'bible' => array(
                    'loadingTranslations' => __( 'Loading translations…', 'videohub360-studio' ),
                    'noneInstalled' => __( 'No Bible translations are installed.', 'videohub360-studio' ),
                    'loadingBooks' => __( 'Loading books…', 'videohub360-studio' ),
                    'loadingChapter' => __( 'Loading chapter…', 'videohub360-studio' ),
                    'resolving' => __( 'Resolving reference…', 'videohub360-studio' ),
                    'loaded' => __( 'Reference loaded.', 'videohub360-studio' ),
                    'resolveFailed' => __( 'Reference could not be resolved.', 'videohub360-studio' ),
                    'chooseTranslation' => __( 'Choose a translation.', 'videohub360-studio' ),
                    'choosePreview' => __( 'Choose a Preview source first.', 'videohub360-studio' ),
                    'chooseProgram' => __( 'Send a source to Program first.', 'videohub360-studio' ),
                    'chooseVerse' => __( 'Choose at least one verse.', 'videohub360-studio' ),
                    'staged' => __( 'Bible passage staged in Preview.', 'videohub360-studio' ),
                    'taken' => __( 'Bible passage taken to Program.', 'videohub360-studio' ),
                    'updated' => __( 'Program Bible passage updated.', 'videohub360-studio' ),
                    'hidden' => __( 'Bible passage hidden.', 'videohub360-studio' ),
                    'previewPage' => __( 'Preview page %1$s of %2$s.', 'videohub360-studio' ),
                    'programPage' => __( 'Program page %1$s of %2$s.', 'videohub360-studio' ),
                    'firstPage' => __( 'First page.', 'videohub360-studio' ),
                    'lastPage' => __( 'Last page.', 'videohub360-studio' ),
                    'saved' => __( 'Cue saved.', 'videohub360-studio' ),
                    'deleted' => __( 'Cue deleted.', 'videohub360-studio' ),
                    'saveFailed' => __( 'Cue could not be saved.', 'videohub360-studio' ),
                    'deleteFailed' => __( 'Cue could not be deleted.', 'videohub360-studio' ),
                    'datasetChanged' => __( 'Translation data has changed since this cue was saved.', 'videohub360-studio' ),
                    'translationMissing' => __( 'This translation is no longer installed.', 'videohub360-studio' ),
                    'lowerBandWarning' => __( 'The Bible lower band may overlap the active lower third.', 'videohub360-studio' ),
                    'fullPanelWarning' => __( 'The full-width Scripture panel may cover other overlays.', 'videohub360-studio' ),
                    'countdownCardWarning' => __( 'The Countdown may cover Scripture.', 'videohub360-studio' ),
                    'countdownLowerWarning' => __( 'The lower Bible band may overlap the active countdown.', 'videohub360-studio' ),
                    'unsaved' => __( 'Unsaved Scripture cue', 'videohub360-studio' ),
                    'crossChapterDirect' => __( 'Cross-chapter ranges must be edited in the Reference field.', 'videohub360-studio' ),
                ),

                'countdown' => array(
                    'loading'       => __( 'Loading countdown presets…', 'videohub360-studio' ),
                    'loaded'        => __( 'Countdown presets loaded.', 'videohub360-studio' ),
                    'loadFailed'    => __( 'Countdown presets could not be loaded.', 'videohub360-studio' ),
                    'choosePreview' => __( 'Choose a Preview source first.', 'videohub360-studio' ),
                    'chooseProgram' => __( 'Send a source to Program first.', 'videohub360-studio' ),
                    'invalidDuration' => __( 'Enter a valid duration.', 'videohub360-studio' ),
                    'futureTarget'  => __( 'Choose a future target date and time.', 'videohub360-studio' ),
                    'staged'        => __( 'Countdown staged in Preview.', 'videohub360-studio' ),
                    'taken'         => __( 'Countdown taken to Program.', 'videohub360-studio' ),
                    'started'       => __( 'Countdown started.', 'videohub360-studio' ),
                    'paused'        => __( 'Countdown paused.', 'videohub360-studio' ),
                    'resumed'       => __( 'Countdown resumed.', 'videohub360-studio' ),
                    'reset'         => __( 'Countdown reset.', 'videohub360-studio' ),
                    'updated'       => __( 'Program countdown updated.', 'videohub360-studio' ),
                    'hidden'        => __( 'Countdown hidden.', 'videohub360-studio' ),
                    'complete'      => __( 'Countdown complete.', 'videohub360-studio' ),
                    'message'       => __( 'End message displayed.', 'videohub360-studio' ),
                    'saved'         => __( 'Preset saved.', 'videohub360-studio' ),
                    'deleted'       => __( 'Preset deleted.', 'videohub360-studio' ),
                    'saveFailed'    => __( 'Preset could not be saved.', 'videohub360-studio' ),
                    'deleteFailed'  => __( 'Preset could not be deleted.', 'videohub360-studio' ),
                    'fullWarning'   => __( 'Full-screen countdown will cover other overlays.', 'videohub360-studio' ),
                    'lowerWarning'  => __( 'Lower-center countdown may overlap the active lower third.', 'videohub360-studio' ),
                    'timerResetNote'=> __( 'Timer changes take effect when Program is reset.', 'videohub360-studio' ),
                    'unsaved'       => __( 'Unsaved countdown', 'videohub360-studio' ),
                    'untitled'      => __( 'Untitled Countdown', 'videohub360-studio' ),
                    'confirmDelete' => __( 'Delete this preset?', 'videohub360-studio' ),
                    'stagedShort'   => __( 'Staged', 'videohub360-studio' ),
                    'notStaged'     => __( 'Not staged', 'videohub360-studio' ),
                    'ready'         => __( 'Ready', 'videohub360-studio' ),
                    'notLive'       => __( 'Not live', 'videohub360-studio' ),
                    'runtimeReady'  => __( 'Ready', 'videohub360-studio' ),
                    'runtimeRunning'=> __( 'Running', 'videohub360-studio' ),
                    'runtimePaused' => __( 'Paused', 'videohub360-studio' ),
                    'runtimeComplete'=> __( 'Complete', 'videohub360-studio' ),
                    'runtimeMessage'=> __( 'Message', 'videohub360-studio' ),
                    'defaultLabel'  => __( 'Service Begins In', 'videohub360-studio' ),
                    'defaultEndMessage' => __( 'Service Is Beginning', 'videohub360-studio' ),
                ),

                'overlayStatus' => array(
                    'tabPreview'      => __( 'overlay staged', 'videohub360-studio' ),
                    'tabProgram'      => __( 'live overlay active', 'videohub360-studio' ),
                ),

                'lowerThirds' => array(
                    'loading'          => __( 'Loading lower thirds…', 'videohub360-studio' ),
                    'loaded'           => __( 'Lower thirds loaded.', 'videohub360-studio' ),
                    'loadFailed'       => __( 'Lower thirds could not be loaded.', 'videohub360-studio' ),
                    'saved'            => __( 'Preset saved.', 'videohub360-studio' ),
                    'deleted'          => __( 'Preset deleted.', 'videohub360-studio' ),
                    'choosePreview'    => __( 'Choose a Preview source first.', 'videohub360-studio' ),
                    'chooseProgram'    => __( 'Send a source to Program first.', 'videohub360-studio' ),
                    'enterPrimary'     => __( 'Enter primary text.', 'videohub360-studio' ),
                    'staged'           => __( 'Lower third staged in Preview.', 'videohub360-studio' ),
                    'taken'            => __( 'Lower third taken to Program.', 'videohub360-studio' ),
                    'updated'          => __( 'Program lower third updated.', 'videohub360-studio' ),
                    'hidden'           => __( 'Lower third hidden.', 'videohub360-studio' ),
                    'cleared'          => __( 'All Program overlays cleared.', 'videohub360-studio' ),
                    'saveFailed'       => __( 'Preset could not be saved.', 'videohub360-studio' ),
                    'deleteFailed'     => __( 'Preset could not be deleted.', 'videohub360-studio' ),
                    'previewStaged'    => __( 'Lower third staged in Preview.', 'videohub360-studio' ),
                    'previewNotStaged' => __( 'Not staged', 'videohub360-studio' ),
                    'programLive'      => __( 'Lower third live.', 'videohub360-studio' ),
                    'programNotLive'   => __( 'Not live', 'videohub360-studio' ),
                    'unsaved'          => __( 'Unsaved lower third', 'videohub360-studio' ),
                    'untitled'         => __( 'Untitled Lower Third', 'videohub360-studio' ),
                    'confirmDelete'    => __( 'Delete this preset?', 'videohub360-studio' ),
                ),

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
