<?php
/**
 * VideoHub360 Frontend Class
 * 
 * Handles frontend display logic, asset enqueuing, and conditional loading
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Frontend {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'register_hero_assets'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_course_assets'));
        add_filter('template_include', array($this, 'template_include'));
        add_action('wp_head', array($this, 'add_viewport_meta'));
        
        // Register AJAX endpoints for built-in login form
        add_action('wp_ajax_nopriv_videohub360_builtin_login', array($this, 'handle_builtin_login'));
        add_action('wp_ajax_videohub360_builtin_login', array($this, 'handle_builtin_login'));
    }
    
    /**
     * Enqueue frontend assets with conditional loading
     */
    public function enqueue_frontend_assets() {
        // Only load on VideoHub360 pages or when shortcode/Elementor content is present.
        if (!$this->is_videohub360_page() && !$this->has_videohub360_shortcode()) {
            return;
        }

        $post_id = $this->get_current_post_id();
        $state = $this->get_livestream_state($post_id);
        $is_single_video = $this->is_single_video_context($post_id);
        $is_live = $is_single_video && $state['has_livestream_identity'];
        $is_selfhosted_or_api_live = $is_live && $this->is_selfhosted_or_api_livestream($post_id);
        $needs_single_player = $is_single_video;
        $needs_interactive_layout = $state['allow_agora_runtime'];
        $needs_chat = $state['chat_mode'] !== 'disabled';
        $needs_moderation = $this->has_moderation_enabled_surface($post_id);

        try {
            // Lightweight shared styles/scripts for VideoHub360 archives, taxonomy pages,
            // Elementor widgets, shortcodes, and single video pages.
            $this->enqueue_core_styles();
            $this->enqueue_core_scripts($needs_single_player);

            if ($needs_interactive_layout) {
                $this->enqueue_interactive_layout_assets();
            }

            if ($needs_single_player) {
                $this->enqueue_video_player_assets($is_selfhosted_or_api_live);
            }

            if ($state['allow_agora_runtime']) {
                $this->enqueue_livestream_assets($state['allow_agora_sdk']);
            }

            if ($needs_chat) {
                $this->enqueue_chat_assets();
            }

            if ($needs_moderation) {
                $this->enqueue_moderation_assets();
            }

            // Admin styles for logged-in users with admin bar.
            if (is_admin_bar_showing()) {
                $this->enqueue_admin_assets();
            }

        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
        }
    }

    /**
     * Enqueue core styles and scripts
     */
    private function enqueue_core_styles() {
        // Enqueue variables.css first - contains all CSS custom properties
        $variables_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/variables.css';
        $variables_css_url = VIDEOHUB360_ASSETS_URL . 'css/variables.css';
        $variables_css_version = file_exists($variables_css_path) ? filemtime($variables_css_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_style(
            'vh360-variables',
            $variables_css_url,
            array(),
            $variables_css_version
        );
        
        // Enqueue frontend CSS - depends on variables
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/frontend.css';
        $css_url = VIDEOHUB360_ASSETS_URL . 'css/frontend.css';
        $css_version = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_style(
            'vh360-frontend',
            $css_url,
            array('vh360-variables'),
            $css_version
        );
        
    }
    
    /**
     * Enqueue core scripts
     */
    private function enqueue_core_scripts($load_single_frontend = false) {
        $frontend_core_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/frontend-core.js';
        $frontend_core_js_url = VIDEOHUB360_ASSETS_URL . 'js/frontend-core.js';
        $frontend_core_js_version = file_exists($frontend_core_js_path) ? filemtime($frontend_core_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script(
            'vh360-frontend-core',
            $frontend_core_js_url,
            array('jquery'),
            $frontend_core_js_version,
            true
        );

        wp_localize_script('vh360-frontend-core', 'vh360Data', $this->get_localized_data());

        if (!$load_single_frontend) {
            return;
        }

        $js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/frontend.js';
        $js_url = VIDEOHUB360_ASSETS_URL . 'js/frontend.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script(
            'vh360-frontend',
            $js_url,
            array('vh360-frontend-core'),
            $js_version,
            true
        );

        wp_localize_script('vh360-frontend', 'vh360Data', $this->get_localized_data());

        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        wp_add_inline_script('vh360-frontend', 'window.__VH360_DEBUG = ' . ($debug_enabled ? 'true' : 'false') . ';', 'before');
    }

    /**
     * Enqueue Agora/interactive room layout, fullscreen, quality, and settings assets.
     */
    private function enqueue_interactive_layout_assets() {
        if (!is_singular('videohub360')) {
            return;
        }

        if (!wp_script_is('vh360-frontend', 'registered') && !wp_script_is('vh360-frontend', 'enqueued')) {
            $this->enqueue_core_scripts(true);
        }

        if (!wp_script_is('vh360-frontend', 'registered') && !wp_script_is('vh360-frontend', 'enqueued')) {
            return;
        }

        $multiview_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/multi-view-layouts.css';
        $multiview_css_url = VIDEOHUB360_ASSETS_URL . 'css/multi-view-layouts.css';
        $multiview_css_version = file_exists($multiview_css_path) ? filemtime($multiview_css_path) : VIDEOHUB360_VERSION;

        wp_enqueue_style('vh360-multi-view-layouts', $multiview_css_url, array('vh360-variables'), $multiview_css_version);

        $mobile_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/simplified-mobile-controls.css';
        $mobile_css_url = VIDEOHUB360_ASSETS_URL . 'css/simplified-mobile-controls.css';
        $mobile_css_version = file_exists($mobile_css_path) ? filemtime($mobile_css_path) : VIDEOHUB360_VERSION;

        wp_enqueue_style('vh360-simplified-mobile-controls', $mobile_css_url, array('vh360-multi-view-layouts'), $mobile_css_version);

        $mobile_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/simplified-mobile-controls.js';
        $mobile_js_url = VIDEOHUB360_ASSETS_URL . 'js/simplified-mobile-controls.js';
        $mobile_js_version = file_exists($mobile_js_path) ? filemtime($mobile_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script('vh360-simplified-mobile-controls', $mobile_js_url, array(), $mobile_js_version, true);

        $view_layout_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/view-layout-manager.js';
        $view_layout_js_url = VIDEOHUB360_ASSETS_URL . 'js/view-layout-manager.js';
        $view_layout_js_version = file_exists($view_layout_js_path) ? filemtime($view_layout_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script('vh360-view-layout-manager', $view_layout_js_url, array('vh360-simplified-mobile-controls'), $view_layout_js_version, true);

        $agora_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/frontend-agora.js';
        $agora_js_url = VIDEOHUB360_ASSETS_URL . 'js/frontend-agora.js';
        $agora_js_version = file_exists($agora_js_path) ? filemtime($agora_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script('vh360-frontend-agora', $agora_js_url, array('vh360-frontend', 'vh360-view-layout-manager'), $agora_js_version, true);

        $quality_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/video-quality-manager.js';
        $quality_js_url = VIDEOHUB360_ASSETS_URL . 'js/video-quality-manager.js';
        $quality_js_version = file_exists($quality_js_path) ? filemtime($quality_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script('vh360-video-quality-manager', $quality_js_url, array('vh360-frontend-agora'), $quality_js_version, true);

        $unified_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/unified-settings-manager.js';
        $unified_js_url = VIDEOHUB360_ASSETS_URL . 'js/unified-settings-manager.js';
        $unified_js_version = file_exists($unified_js_path) ? filemtime($unified_js_path) : VIDEOHUB360_VERSION;

        wp_enqueue_script('vh360-unified-settings-manager', $unified_js_url, array('vh360-video-quality-manager'), $unified_js_version, true);

        wp_localize_script('vh360-video-quality-manager', 'vh360QualityConfig', $this->get_quality_config());

        $post_id = $this->get_current_post_id();
        $unified_settings_config = array(
            'enabled' => true,
            'canModerate' => $this->user_can_moderate($post_id)
        );
        wp_localize_script('vh360-video-quality-manager', 'vh360UnifiedSettingsConfig', $unified_settings_config);
        wp_localize_script('vh360-unified-settings-manager', 'vh360UnifiedSettingsConfig', $unified_settings_config);
    }

    /**
     * Register hero banner assets for conditional loading
     */
    public function register_hero_assets() {
        $hero_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/hero.css';
        $hero_css_url = VIDEOHUB360_ASSETS_URL . 'css/hero.css';
        $hero_css_version = file_exists($hero_css_path) ? filemtime($hero_css_path) : VIDEOHUB360_VERSION;
        
        wp_register_style(
            'vh360-hero',
            $hero_css_url,
            array(),
            $hero_css_version
        );
        
        $hero_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/hero.js';
        $hero_js_url = VIDEOHUB360_ASSETS_URL . 'js/hero.js';
        $hero_js_version = file_exists($hero_js_path) ? filemtime($hero_js_path) : VIDEOHUB360_VERSION;
        
        wp_register_script(
            'vh360-hero',
            $hero_js_url,
            array(),
            $hero_js_version,
            true
        );
    }
    
    /**
     * Enqueue video player assets
     */
    private function enqueue_video_player_assets($load_videojs = false) {
        $js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/video-player.js';
        $js_url  = VIDEOHUB360_ASSETS_URL . 'js/video-player.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : VIDEOHUB360_VERSION;

        if ($load_videojs) {
            /*
             * Enqueue the Video.js library and stylesheet for HLS/DASH livestreams.
             * Standard uploaded and embedded videos use native/embed playback and do
             * not need this CDN payload.
             */
            wp_enqueue_style(
                'vh360-videojs',
                'https://vjs.zencdn.net/8.3.0/video-js.css',
                array(),
                '8.3.0'
            );

            wp_enqueue_script(
                'vh360-videojs',
                'https://vjs.zencdn.net/8.3.0/video.min.js',
                array(),
                '8.3.0',
                true
            );

            wp_add_inline_script(
                'vh360-videojs',
                'if (window.videojs) { try { videojs("vh360-livestream-video"); } catch (e) {} }'
            );
        }

        $dependencies = array('vh360-frontend');
        if ($load_videojs) {
            $dependencies[] = 'vh360-videojs';
        }

        wp_enqueue_script(
            'vh360-video-player',
            $js_url,
            $dependencies,
            $js_version,
            true
        );
        
        // Localize script for ad click tracking
        wp_localize_script(
            'vh360-video-player',
            'vh360Ajax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vh360_ad_click_nonce')
            )
        );
    }
    
    /**
     * Enqueue chat assets
     */
    private function enqueue_chat_assets() {
        // Chat CSS - depends on variables and frontend
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/chat.css';
        $css_url = VIDEOHUB360_ASSETS_URL . 'css/chat.css';
        $css_version = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_style(
            'vh360-chat',
            $css_url,
            array('vh360-variables', 'vh360-frontend'),
            $css_version
        );
        
        // Chat JS
        $js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/chat.js';
        $js_url = VIDEOHUB360_ASSETS_URL . 'js/chat.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_script(
            'vh360-chat',
            $js_url,
            array('vh360-frontend-core'),
            $js_version,
            true
        );
    }
    
    /**
     * Enqueue livestream assets
     */
    private function enqueue_livestream_assets($load_agora_sdk = false) {
        if (!is_singular('videohub360')) {
            return;
        }

        if (!wp_script_is('vh360-frontend', 'registered') && !wp_script_is('vh360-frontend', 'enqueued')) {
            $this->enqueue_core_scripts(true);
        }

        if ($load_agora_sdk) {
            wp_enqueue_script('agora-rtc-sdk', 'https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js', array(), '4.20.0', true);
        }
        
        // Livestream JS
        $js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/livestream.js';
        $js_url = VIDEOHUB360_ASSETS_URL . 'js/livestream.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : VIDEOHUB360_VERSION;
        
        $frontend_dependency = (wp_script_is('vh360-frontend', 'registered') || wp_script_is('vh360-frontend', 'enqueued')) ? 'vh360-frontend' : 'vh360-frontend-core';
        $livestream_dependencies = $load_agora_sdk
            ? array('agora-rtc-sdk', 'vh360-frontend-agora')
            : array($frontend_dependency);

        wp_enqueue_script(
            'vh360-livestream',
            $js_url,
            $livestream_dependencies,
            $js_version,
            true
        );
        
        // Localize Agora configuration if this is a livestream page
        global $post;
        if ($post && is_singular('videohub360')) {
            $livestream_fields = array(
                'type' => get_post_meta($post->ID, '_vh360_type', true),
                'agora_channel_name' => get_post_meta($post->ID, '_vh360_agora_channel_name', true),
                'agora_mode' => get_post_meta($post->ID, '_vh360_agora_mode', true) ?: 'interactive',
                'agora_everyone_is_host' => get_post_meta($post->ID, '_vh360_agora_everyone_is_host', true) ?: 'no',
                'host_passcode' => get_post_meta($post->ID, '_vh360_host_passcode', true),
            );
            
            $bootstrap_data = videohub360_get_livestream_bootstrap_data($post->ID, $livestream_fields);
            if ($bootstrap_data) {
                wp_localize_script('vh360-livestream', 'vh360Livestream', $bootstrap_data);
            }
        }
    }
    
    /**
     * Enqueue moderation styles only (for Agora/live posts where UI elements may appear)
     */
    private function enqueue_moderation_styles() {
        // Enqueue moderation CSS
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/moderation.css';
        $css_url  = VIDEOHUB360_ASSETS_URL . 'css/moderation.css';
        $css_ver  = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;

        wp_enqueue_style(
            'vh360-moderation',
            $css_url,
            array('vh360-variables', 'vh360-frontend'),
            $css_ver
        );
    }
    
    /**
     * Enqueue moderation assets (styles + scripts)
     */
    private function enqueue_moderation_assets() {
        // Enqueue styles if not already enqueued
        if (!wp_style_is('vh360-moderation', 'enqueued')) {
            $this->enqueue_moderation_styles();
        }
        
        // Enqueue moderation JS
        $js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/moderation.js';
        $js_url = VIDEOHUB360_ASSETS_URL . 'js/moderation.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_script(
            'vh360-moderation',
            $js_url,
            array('vh360-frontend'),
            $js_version,
            true
        );
    }
    
    /**
     * Enqueue admin assets
     */
    private function enqueue_admin_assets() {
        // Enqueue variables.css first for admin pages
        $variables_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/variables.css';
        $variables_css_url = VIDEOHUB360_ASSETS_URL . 'css/variables.css';
        $variables_css_version = file_exists($variables_css_path) ? filemtime($variables_css_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_style(
            'vh360-variables',
            $variables_css_url,
            array(),
            $variables_css_version
        );
        
        // Enqueue admin.css - depends on variables
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/admin.css';
        $css_url = VIDEOHUB360_ASSETS_URL . 'css/admin.css';
        $css_version = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;
        
        wp_enqueue_style(
            'vh360-admin',
            $css_url,
            array('vh360-variables'),
            $css_version
        );
    }
    
    /**
     * Get localized data for JavaScript
     */
    private function get_localized_data() {
        global $post;
        $current_user = wp_get_current_user();
        
        // Get post ID reliably
        $post_id = $this->get_current_post_id();
        $state = $this->get_livestream_state($post_id);
        
        $data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'postId' => $post_id,
            'userId' => get_current_user_id(),
            'isUserLoggedIn' => is_user_logged_in(),
            'chatNonce' => wp_create_nonce('videohub360_chat_nonce'),
            'chatMessageLimit' => intval(get_option('videohub360_chat_message_limit', 500)),
            'shareEmailNonce' => wp_create_nonce('videohub360_share_email_nonce'),
            'agoraTokenNonce' => wp_create_nonce('vh360_agora_token'),
            'endStreamNonce' => wp_create_nonce('vh360_end_stream'),
            'streamStatusNonce' => wp_create_nonce('vh360_agora_token'), // Use same nonce
            'moderationNonce' => wp_create_nonce('videohub360_chat_nonce'), // Use same nonce for consistency
            'userDisplayName' => $current_user->display_name,
            // Theme compatibility: Check if custom auth function exists before using it
            // This ensures the plugin works even if the theme is deactivated or replaced
            'userLoginUrl' => function_exists('vh360_get_login_page_url_with_redirect') 
                ? vh360_get_login_page_url_with_redirect(get_permalink())
                : wp_login_url(get_permalink()),
            'userLogoutUrl' => is_user_logged_in() ? wp_logout_url(get_permalink()) : '',
            'canModerate' => $this->user_can_moderate($post_id),
            'loginModalType' => get_option('videohub360_login_modal_type', 'redirect'),
            'loginModalShortcode' => get_option('videohub360_login_modal_shortcode', ''),
            'loginModalRedirectUrl' => get_option('videohub360_login_modal_redirect_url', ''),
            'loginModalJsFunction' => get_option('videohub360_login_modal_js_function', ''),
            'forceLoginEveryoneHost' => intval(get_option('videohub360_force_login_everyone_host', 1)),
            'usersCanRegister' => get_option('users_can_register'),
            'permalink' => $post ? get_permalink($post) : '',
            'title' => $post ? get_the_title($post) : '',
            'userAvatar' => is_user_logged_in() ? get_avatar($current_user->ID, 24) : '',
            'user_role' => is_user_logged_in() && isset($current_user->roles[0]) ? $current_user->roles[0] : '',
            'is_host' => is_user_logged_in() && videohub360_user_is_host(),
            'security' => array(
                'can_moderate' => videohub360_user_can_moderate(null, $post_id),
                'is_logged_in' => is_user_logged_in(),
                'user_id' => get_current_user_id(),
                'display_name' => $current_user->display_name
            ),
            'debug' => array(
                'version' => VIDEOHUB360_VERSION,
                'isLivePage' => $post ? (get_post_meta($post->ID, '_vh360_is_live', true) === 'yes') : false,
            ),
            // Video reactions nonce
            'videoReactionNonce' => wp_create_nonce('vh360_video_reaction'),
            // Playlist nonce
            'playlistNonce' => wp_create_nonce('vh360_playlist'),
            // Watch progress nonce
            'watchNonce' => wp_create_nonce('vh360_watch_progress_nonce')
        );

        $data['isStreamStopped'] = $state['is_stream_stopped'];
        $data['isStudioReplayReady'] = $state['is_studio_replay_ready'];
        $data['isEndedLivestreamReplay'] = $state['is_ended_livestream_replay'];
        $data['chatMode'] = $state['chat_mode'];
        $data['allowChatPolling'] = $state['allow_chat_polling'];
        $data['allowChatPosting'] = $state['allow_chat_posting'];
        $data['allowAgoraRuntime'] = $state['allow_agora_runtime'];
        $data['isLiveIdentity'] = $state['has_livestream_identity'];
        $data['isActiveLivestreamRuntime'] = $state['is_active_livestream_runtime'];
        
        // Add built-in login form strings
        $data['loginText'] = __('Log In', 'videohub360');
        $data['loadingText'] = __('Logging in...', 'videohub360');
        $data['networkErrorText'] = __('Network error. Please try again.', 'videohub360');
        
        // Add livestream offline/ended messages
        $data['livestreamMessages'] = array(
            'endedDefaultHtml'     => vh360_get_default_stream_ended_html(),
            'endedByModeratorHtml' => vh360_get_stream_ended_by_moderator_html(),
            'endedNeedsRestartHtml' => vh360_get_stream_ended_needs_restart_html(),
            'replayProcessingHtml' => vh360_get_stream_replay_processing_html(),
        );
        
        // Add livestream data if it's a live post
        if ($post && $state['allow_agora_runtime']) {
            $data = array_merge($data, $this->get_livestream_data($post->ID));
        }
        
        return $data;
    }

    /**
     * Get video quality configuration for JavaScript
     */
    private function get_quality_config() {
        global $post;
        
        // Get global settings
        $config = VideoHub360_Video_Quality::get_js_config();
        
        // Check for post-specific overrides if we're on a single video page
        if ($post && is_singular('videohub360')) {
            $override_quality = get_post_meta($post->ID, '_vh360_override_quality_settings', true) === 'yes';
            
            if ($override_quality) {
                $post_quality = get_post_meta($post->ID, '_vh360_video_quality', true);
                $post_mirror = get_post_meta($post->ID, '_vh360_video_mirror', true);
                
                if (!empty($post_quality)) {
                    $config['default_quality'] = $post_quality;
                }
                
                if (!empty($post_mirror)) {
                    $config['default_mirror'] = $post_mirror;
                }
            }
            
            // Filter available qualities based on user permissions and post settings
            $enable_4k = get_option('videohub360_enable_4k_streaming', 0);
            if (!$enable_4k) {
                // Remove 4K options if not enabled globally
                unset($config['presets']['4k']);
                unset($config['presets']['4k60']);
            }
        }
        
        return $config;
    }
    
    /**
     * Get livestream-related data
     */
    private function get_livestream_data($post_id) {
        
        return array(
            'agoraAppId' => get_option( 'vh360_agora_app_id', get_option( 'videohub360_agora_app_id', '' ) ),
            'agoraChannel' => get_post_meta($post_id, '_vh360_agora_channel_name', true),
            'agoraMode' => get_post_meta($post_id, '_vh360_agora_mode', true) ?: 'interactive',
            'allowEveryoneIsHost' => get_post_meta($post_id, '_vh360_agora_everyone_is_host', true) === 'yes',
            'streamLive' => get_post_meta($post_id, '_vh360_agora_stream_live', true) === 'yes',
            'liveStartTime' => get_post_meta($post_id, '_vh360_live_start_time', true),
            'studioControlled' => get_post_meta($post_id, '_vh360_studio_controlled_live', true) === 'yes',
            'studioJobId' => get_post_meta($post_id, '_vh360_studio_job_id', true),
            'studioReplayReady' => get_post_meta($post_id, '_vh360_studio_replay_ready', true) === 'yes',
            'studioReplayPending' => get_post_meta($post_id, '_vh360_studio_replay_pending', true) === 'yes',
            'studioReplayFailed' => get_post_meta($post_id, '_vh360_studio_replay_failed', true) === 'yes',
            'studioReplayStatus' => sanitize_key(get_post_meta($post_id, '_vh360_studio_replay_status', true)),
            'streamStopped' => get_post_meta($post_id, '_vh360_stream_stopped', true) === 'yes'
        );
    }

    /**
     * Build the current livestream/chat state for a post.
     */
    private function get_livestream_state($post_id = null) {
        if (!$post_id) {
            $post_id = $this->get_current_post_id();
        }

        $is_single_video_context = $this->is_single_video_context($post_id);
        $is_live_identity = $post_id && get_post_meta($post_id, '_vh360_is_live', true) === 'yes';
        $stream_stopped = $post_id && get_post_meta($post_id, '_vh360_stream_stopped', true) === 'yes';
        $studio_replay_ready = $post_id && get_post_meta($post_id, '_vh360_studio_replay_ready', true) === 'yes';
        $legacy_studio_replay = $post_id
            && get_post_meta($post_id, '_vh360_studio_converted_live_to_replay', true) === 'yes'
            && $studio_replay_ready
            && (int) get_post_meta($post_id, '_vh360_studio_replay_source_live_video_id', true) === (int) $post_id;

        $has_livestream_identity = $is_live_identity || $legacy_studio_replay;
        $is_ended_livestream_replay = $is_live_identity && $stream_stopped && $studio_replay_ready;
        $is_active_livestream_runtime = $has_livestream_identity && !$stream_stopped;
        $is_agora_identity = $post_id && get_post_meta($post_id, '_vh360_type', true) === 'agora';
        $allow_agora_runtime = $is_single_video_context && $is_active_livestream_runtime && $is_agora_identity;

        $chat_enabled = false;
        if ($has_livestream_identity) {
            $per_video_chat = get_post_meta($post_id, '_vh360_chat_enabled', true);
            if ($per_video_chat === 'yes') {
                $chat_enabled = true;
            } elseif ($per_video_chat === 'no') {
                $chat_enabled = false;
            } else {
                $chat_enabled = (bool) get_option('videohub360_chat_enabled', 1);
            }
        }

        $chat_mode = 'disabled';
        if ($chat_enabled) {
            $chat_mode = $stream_stopped ? 'archive' : 'active';
        }

        return array(
            'has_livestream_identity' => $has_livestream_identity,
            'is_stream_stopped' => $stream_stopped,
            'is_studio_replay_ready' => $studio_replay_ready,
            'is_ended_livestream_replay' => $is_ended_livestream_replay,
            'is_active_livestream_runtime' => $is_active_livestream_runtime,
            'allow_agora_runtime' => $allow_agora_runtime,
            'allow_agora_sdk' => $allow_agora_runtime,
            'allow_chat_polling' => $chat_enabled && !$stream_stopped,
            'allow_chat_posting' => $chat_enabled && !$stream_stopped,
            'chat_mode' => $chat_mode,
        );
    }
    
    /**
     * Handle template inclusion
     */
        public function template_include($template) {
        if (is_singular('videohub360')) {
            $post_id = get_queried_object_id();
            if ($post_id) {
                $context = get_post_meta($post_id, '_vh360_context', true);
                // For community Live Rooms, let the theme (and its templates) handle rendering.
                if ($context === 'live_room') {
                    return $template;
                }
            }

            $single_template = VIDEOHUB360_TEMPLATES_DIR . 'single-videohub360.php';
            if (file_exists($single_template)) {
                return $single_template;
            }
        }

        if (is_post_type_archive('videohub360')) {
            $archive_template = VIDEOHUB360_TEMPLATES_DIR . 'archive-videohub360.php';
            if (file_exists($archive_template)) {
                return $archive_template;
            }
        }

        // Course landing page: load taxonomy template when Course / Lesson Features are enabled.
        if (
            is_tax('videohub360_series') &&
            function_exists('videohub360_course_features_enabled') &&
            videohub360_course_features_enabled()
        ) {
            $course_template = VIDEOHUB360_TEMPLATES_DIR . 'taxonomy-videohub360_series.php';
            if (file_exists($course_template)) {
                return $course_template;
            }
        }

        return $template;
    }

    
    /**
     * Add viewport meta tag
     */
    public function add_viewport_meta() {
        if ($this->is_videohub360_page()) {
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        }
    }
    
    /**
     * Check if current page is a VideoHub360 page
     */
    private function is_videohub360_page() {
        return is_singular('videohub360') || is_post_type_archive('videohub360') || is_tax('videohub360_series');
    }

    /**
     * Enqueue course-mode CSS when Course / Lesson Features are enabled.
     *
     * Loaded only on:
     *   - videohub360_series taxonomy archive pages
     *   - single videohub360 posts that belong to at least one videohub360_series term
     */
    public function enqueue_course_assets() {
        $should_load_course_css = false;

        if (
            function_exists('videohub360_course_features_enabled') &&
            videohub360_course_features_enabled()
        ) {
            if (is_tax('videohub360_series')) {
                $should_load_course_css = true;
            } elseif (is_singular('videohub360')) {
                $post_id = get_queried_object_id();
                if ($post_id && has_term('', 'videohub360_series', $post_id)) {
                    $should_load_course_css = true;
                }
            }
        }

        if (!$should_load_course_css) {
            return;
        }

        $css_path    = VIDEOHUB360_PLUGIN_DIR . 'assets/css/course-mode.css';
        $css_url     = VIDEOHUB360_ASSETS_URL  . 'css/course-mode.css';
        $css_version = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;

        wp_enqueue_style(
            'vh360-course-mode',
            $css_url,
            array(),
            $css_version
        );
    }
    
    /**
     * Check if current page has VideoHub360 shortcode OR Elementor widget usage
     */
    private function has_videohub360_shortcode() {
        global $post;

        if (!$post) {
            return false;
        }

        // Classic editor / block content
        if (!empty($post->post_content)) {
            if (
                has_shortcode($post->post_content, 'videohub360_videos') ||
                has_shortcode($post->post_content, 'videohub360_hero') ||
                has_shortcode($post->post_content, 'videohub360')
            ) {
                return true;
            }
        }

        // Elementor pages store layout in post meta, not post_content
        $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
        if (is_string($elementor_data) && $elementor_data !== '') {
            // Your Elementor widgets are named:
            // - videohub360_videos
            // - videohub360_hero
            // Also catches shortcode widgets containing [videohub360...]
            if (
                strpos($elementor_data, 'videohub360_videos') !== false ||
                strpos($elementor_data, 'videohub360_hero') !== false ||
                strpos($elementor_data, '[videohub360') !== false
            ) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Check whether the current request is a single VideoHub360 video surface.
     */
    private function is_single_video_context($post_id = 0) {
        return $post_id && is_singular('videohub360');
    }

    /**
     * Check whether a livestream needs Video.js HLS/DASH support.
     */
    private function is_selfhosted_or_api_livestream($post_id = null) {
        if (!$post_id) {
            $post_id = $this->get_current_post_id();
        }

        if (!$post_id || !$this->is_live_post($post_id)) {
            return false;
        }

        $livestream_type = get_post_meta($post_id, '_vh360_type', true);
        return in_array($livestream_type, array('selfhosted', 'api'), true);
    }

    /**
     * Check whether the current surface renders live chat UI.
     */
    private function has_chat_enabled_surface($post_id = 0) {
        return $post_id && is_singular('videohub360') && $this->get_livestream_state($post_id)['chat_mode'] !== 'disabled';
    }

    /**
     * Check whether the current surface can expose moderation UI to this user.
     */
    private function has_moderation_enabled_surface($post_id = 0) {
        if (!$post_id || !is_singular('videohub360')) {
            return false;
        }

        $state = $this->get_livestream_state($post_id);

        return $state['is_active_livestream_runtime'] && $this->user_can_moderate($post_id);
    }

    /**
     * Check if post is a live post
     */
    private function is_live_post($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        return $this->get_livestream_state($post_id)['has_livestream_identity'];
    }

    /**
     * Check whether a Studio replay should still be treated as a stopped livestream surface.
     */
    private function is_studio_stopped_livestream_replay($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }

        return $this->get_livestream_state($post_id)['is_ended_livestream_replay'];
    }
    
    /**
     * Check if post is configured for Agora (regardless of live status)
     */
    private function is_agora_post($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        $livestream_type = get_post_meta($post_id, '_vh360_type', true);
        return $livestream_type === 'agora';
    }
    
    /**
     * Check if chat is enabled for a post (only for livestreams)
     */
    private function is_chat_enabled($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        // Special handling for Live Room context - always check chat settings regardless of live status
        // This ensures chat assets are enqueued even when stream is stopped/ended
        $context = get_post_meta($post_id, '_vh360_context', true);
        $is_live_room = ($context === 'live_room');
        
        // For Live Room pages, check if the post is marked as livestream OR if it's explicitly a Live Room
        // This allows chat to persist even after restart sets stream_stopped
        if (!$is_live_room && !$this->is_live_post($post_id)) {
            return false;
        }
        
        // Check per-video chat setting first
        $per_video_chat = get_post_meta($post_id, '_vh360_chat_enabled', true);
        
        if ($per_video_chat === 'yes') {
            return true;
        } elseif ($per_video_chat === 'no') {
            return false;
        } else {
            // Use global setting if no per-video setting
            return get_option('videohub360_chat_enabled', 1);
        }
    }
    
    /**
     * Get current post ID reliably
     * 
     * @return int Post ID or 0 if not in videohub360 context
     */
    private function get_current_post_id() {
        // Use queried object for singular pages
        if (is_singular('videohub360')) {
            return get_queried_object_id();
        }
        
        // Fall back to global $post if it's a videohub360 post
        global $post;
        if ($post && $post->post_type === 'videohub360') {
            return $post->ID;
        }
        
        return 0;
    }
    
    /**
     * Check if user can moderate
     */
    private function user_can_moderate($post_id = 0) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        if (current_user_can('manage_options') || current_user_can('moderate_comments')) {
            return true;
        }
        
        if ($post_id) {
            $post = get_post($post_id);
            
            if ($post && $post->post_type === 'videohub360') {
                
                if ((int) $post->post_author === (int) get_current_user_id()) {
                    return true;
                }
                
                if (current_user_can('edit_post', $post_id)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Build sidebar query based on configuration
     */
    public function build_sidebar_query($post_id, $config = null) {
        // Get sidebar configuration if not provided
        if ($config === null) {
            $config = get_post_meta($post_id, '_vh360_sidebar_config', true);
        }
        
        // Default configuration fallback
        $config = wp_parse_args($config, array(
            'enable_custom' => 'no',
            'category_filter' => '',
            'series_filter' => '',
            'location_filter' => '',
            'tag_filter' => '',
            'video_type_filter' => 'all',
            'num_videos' => 6,
            'order_by' => 'date',
            'order_direction' => 'DESC',
            'exclude_current' => 'yes',
            'include_posts' => '',
            'exclude_posts' => ''
        ));
        
        // If custom settings are not enabled, return default query
        if ($config['enable_custom'] !== 'yes') {
            return new WP_Query(array(
                'post_type' => 'videohub360',
                'posts_per_page' => 6,
                'post__not_in' => array($post_id),
                'orderby' => 'date',
                'order' => 'DESC',
                // Exclude Live Rooms from sidebar - they should only appear on dedicated Live Room pages
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_vh360_context',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => '_vh360_context',
                        'value' => 'live_room',
                        'compare' => '!='
                    )
                )
            ));
        }
        
        // Build custom query arguments
        $query_args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => max(1, min(30, intval($config['num_videos']))),
            'post_status' => 'publish',
        );
        
        // Handle ordering
        $order_by = sanitize_text_field($config['order_by']);
        if ($order_by === 'views') {
            $query_args['meta_key'] = '_videohub360_post_views_count';
            $query_args['orderby'] = 'meta_value_num';
        } elseif ($order_by === 'rand') {
            $query_args['orderby'] = 'rand';
        } else {
            $query_args['orderby'] = $order_by;
        }
        $query_args['order'] = strtoupper(sanitize_text_field($config['order_direction'])) === 'ASC' ? 'ASC' : 'DESC';
        
        // Handle taxonomy filters
        $tax_query = array();
        if (!empty($config['category_filter'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($config['category_filter']),
            );
        }
        if (!empty($config['series_filter'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_series',
                'field' => 'slug',
                'terms' => sanitize_text_field($config['series_filter']),
            );
        }
        if (!empty($config['location_filter'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_location',
                'field' => 'slug',
                'terms' => sanitize_text_field($config['location_filter']),
            );
        }
        if (!empty($config['tag_filter'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_tag',
                'field' => 'slug',
                'terms' => sanitize_text_field($config['tag_filter']),
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // Handle video type filtering
        $meta_query = array();
        
        // ALWAYS exclude Live Rooms from sidebar
        // Live Rooms are community-created livestreams with _vh360_context = 'live_room'
        // They should only appear on dedicated Live Room pages, not in video sidebars
        // This matches the pattern used throughout the codebase (widgets, dashboard, channel pages, etc.)
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => '_vh360_context',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_vh360_context',
                'value' => 'live_room',
                'compare' => '!='
            )
        );
        
        $video_type = sanitize_text_field($config['video_type_filter']);
        
        if ($video_type === 'live_only') {
            $meta_query[] = array(
                'key' => '_vh360_is_live',
                'value' => 'yes',
                'compare' => '='
            );
        } elseif ($video_type === 'regular_only') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_vh360_is_live',
                    'value' => 'no',
                    'compare' => '='
                ),
                array(
                    'key' => '_vh360_is_live',
                    'compare' => 'NOT EXISTS'
                )
            );
        } elseif ($video_type === 'embed_only') {
            $meta_query[] = array(
                'key' => 'videohub360_custom_html',
                'value' => '',
                'compare' => '!='
            );
        }
        
        // Always add meta_query since we now always exclude Live Rooms
        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }
        
        // Handle include/exclude posts
        if (!empty($config['include_posts'])) {
            $include_ids = array_map('intval', explode(',', $config['include_posts']));
            $include_ids = array_filter($include_ids); // Remove empty values
            if (!empty($include_ids)) {
                $query_args['post__in'] = $include_ids;
            }
        }
        
        $exclude_ids = array();
        if (!empty($config['exclude_posts'])) {
            $exclude_ids = array_map('intval', explode(',', $config['exclude_posts']));
            $exclude_ids = array_filter($exclude_ids); // Remove empty values
        }
        
        // Exclude current post if requested
        if ($config['exclude_current'] === 'yes') {
            $exclude_ids[] = $post_id;
        }
        
        if (!empty($exclude_ids)) {
            $query_args['post__not_in'] = array_unique($exclude_ids);
        }
        
        return new WP_Query($query_args);
    }
    
    /**
     * Handle built-in login form AJAX request
     * 
     * @since 2.0.0
     */
    public function handle_builtin_login() {
        // Verify nonce
        if (!isset($_POST['videohub360_login_nonce']) || !wp_verify_nonce($_POST['videohub360_login_nonce'], 'videohub360_login_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed. Please refresh the page and try again.', 'videohub360')
            ));
        }
        
        // Rate limiting - check attempts by IP
        $user_ip = $this->get_user_ip();
        $rate_limit_key = 'vh360_login_attempt_' . md5($user_ip);
        $attempts = get_transient($rate_limit_key);
        
        if ($attempts && $attempts >= 5) {
            wp_send_json_error(array(
                'message' => __('Too many login attempts. Please try again in 15 minutes.', 'videohub360')
            ));
        }
        
        // Sanitize and validate input
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : ''; // Don't sanitize password
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        
        // Validate required fields
        if (empty($username) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Please enter both username and password.', 'videohub360')
            ));
        }
        
        // Hook: Before login attempt
        do_action('videohub360_before_builtin_login', $username);
        
        // Build credentials array
        $credentials = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember
        );
        
        // Attempt login using WordPress core function
        $user = wp_signon($credentials, is_ssl());
        
        // Check if login was successful
        if (is_wp_error($user)) {
            // Increment rate limit counter
            $attempts = $attempts ? $attempts + 1 : 1;
            set_transient($rate_limit_key, $attempts, 15 * MINUTE_IN_SECONDS);
            
            // Hook: After failed login
            do_action('videohub360_after_builtin_login_failure', $username, $user);
            
            // Sanitize error message
            $error_message = wp_strip_all_tags($user->get_error_message());
            
            wp_send_json_error(array(
                'message' => $error_message
            ));
        }
        
        // Login successful - clear rate limit
        delete_transient($rate_limit_key);
        
        // Hook: After successful login
        do_action('videohub360_after_builtin_login_success', $user);
        
        // Get redirect URL
        $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url();
        
        // Apply WordPress login_redirect filter
        $redirect_to = apply_filters('login_redirect', $redirect_to, '', $user);
        
        // Send success response
        wp_send_json_success(array(
            'message'     => sprintf(__('Welcome back, %s!', 'videohub360'), $user->display_name),
            'redirect_to' => $redirect_to,
            'user_name'   => $user->display_name
        ));
    }
    
    /**
     * Get user IP address with fallbacks
     * 
     * @since 2.0.0
     * @return string Sanitized IP address
     */
    private function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain multiple IPs, get the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
}