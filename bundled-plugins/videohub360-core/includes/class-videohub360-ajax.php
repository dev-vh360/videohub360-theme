<?php
/**
 * VideoHub360 AJAX Class
 * 
 * Centralized AJAX handling
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Log debug message if WP_DEBUG is enabled
     * 
     * @param string $message Debug message
     * @param mixed $data Optional data to log
     */
    private function debug_log($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            videohub360_debug_log($message, $data !== null ? $data : array());
        }
    }
    
    /**
     * Check rate limit for AJAX actions
     * 
     * @param string $action The action being rate limited
     * @param int $limit Number of requests allowed per minute (default: 30)
     * @param int $user_id Optional user ID, falls back to current user or IP
     * @return bool True if within rate limit, false if exceeded
     */
    private function check_rate_limit($action, $limit = 30, $user_id = null) {
        // Get identifier for rate limiting
        if ($user_id) {
            $identifier = $user_id;
        } elseif (is_user_logged_in()) {
            $identifier = get_current_user_id();
        } else {
            $identifier = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        
        $key = "vh360_rate_limit_{$action}_{$identifier}";
        $attempts = get_transient($key);
        
        if ($attempts >= $limit) {
            return false;
        }
        
        set_transient($key, ($attempts + 1), 60); // 60 seconds window
        return true;
    }
    
    /**
     * Check if current user can manage a live room
     * 
     * User can manage if they are:
     * - An administrator (manage_options)
     * - Can edit the post (edit_post capability)
     * - Are the post author (creator)
     * 
     * @param int $post_id The post ID to check
     * @return bool True if user can manage, false otherwise
     */
    private function user_can_manage_live_room($post_id) {
        // Must be logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Get the post
        $post = get_post($post_id);
        if (empty($post)) {
            return false;
        }
        
        // Must be a videohub360 post
        if ($post->post_type !== 'videohub360') {
            return false;
        }
        
        // Check if user is the post author
        $is_owner = ((int) $post->post_author === (int) get_current_user_id());
        
        // Allow if: admin OR can edit the post OR is the post author
        return current_user_can('manage_options') 
            || current_user_can('edit_post', $post_id) 
            || $is_owner;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // === CORE AJAX HANDLERS ===
        add_action('wp_ajax_vh360_live_viewers', array($this, 'handle_live_viewers'));
        add_action('wp_ajax_nopriv_vh360_live_viewers', array($this, 'handle_live_viewers'));
        
        add_action('wp_ajax_vh360_live_viewers_batch', array($this, 'handle_live_viewers_batch'));
        add_action('wp_ajax_nopriv_vh360_live_viewers_batch', array($this, 'handle_live_viewers_batch'));
        
        add_action('wp_ajax_vh360_save_watch_progress', array($this, 'handle_save_watch_progress'));
        
        add_action('wp_ajax_videohub360_share_email', array($this, 'handle_share_email'));
        add_action('wp_ajax_nopriv_videohub360_share_email', array($this, 'handle_share_email'));
        
        // === AGORA/LIVESTREAM HANDLERS ===
        add_action('wp_ajax_vh360_generate_agora_token', array($this, 'handle_generate_agora_token'));
        add_action('wp_ajax_nopriv_vh360_generate_agora_token', array($this, 'handle_generate_agora_token'));
        
        add_action('wp_ajax_vh360_stream_status', array($this, 'handle_stream_status'));
        add_action('wp_ajax_nopriv_vh360_stream_status', array($this, 'handle_stream_status'));
        
        add_action('wp_ajax_vh360_set_stream_status', array($this, 'handle_set_stream_status'));
        
        add_action('wp_ajax_vh360_end_stream', array($this, 'handle_end_stream'));
        
        // Frontend stream controls (for Live Room owners/editors)
        add_action('wp_ajax_vh360_stop_stream', array($this, 'handle_stop_stream'));
        add_action('wp_ajax_vh360_restart_stream', array($this, 'handle_restart_stream'));
        
        add_action('wp_ajax_vh360_remove_participant', array($this, 'handle_remove_participant'));
        
        add_action('wp_ajax_vh360_get_stream_status', array($this, 'handle_get_stream_status'));
        add_action('wp_ajax_nopriv_vh360_get_stream_status', array($this, 'handle_get_stream_status'));
        
        // Display name lookup for Agora participants
        add_action('wp_ajax_videohub360_lookup_display_name', array($this, 'handle_lookup_display_name'));
        add_action('wp_ajax_nopriv_videohub360_lookup_display_name', array($this, 'handle_lookup_display_name'));
        
        // === MODERATION HANDLERS ===
        add_action('wp_ajax_videohub360_get_moderated_users', array($this, 'handle_get_moderated_users'));
        add_action('wp_ajax_videohub360_unban_user', array($this, 'handle_unban_user'));
        add_action('wp_ajax_videohub360_remove_timeout', array($this, 'handle_remove_timeout'));
        add_action('wp_ajax_videohub360_check_moderation_status', array($this, 'handle_check_moderation_status'));
        add_action('wp_ajax_nopriv_videohub360_check_moderation_status', array($this, 'handle_check_moderation_status'));
        add_action('wp_ajax_videohub360_check_recent_moderation_activity', array($this, 'handle_check_recent_moderation_activity'));
        add_action('wp_ajax_nopriv_videohub360_check_recent_moderation_activity', array($this, 'handle_check_recent_moderation_activity'));
        
        // Debug moderation status endpoint
        add_action('wp_ajax_videohub360_debug_moderation_status', array($this, 'handle_debug_moderation_status'));
        
        // === AD CLICK TRACKING HANDLER ===
        add_action('wp_ajax_vh360_track_ad_click', array($this, 'handle_track_ad_click'));
        add_action('wp_ajax_nopriv_vh360_track_ad_click', array($this, 'handle_track_ad_click'));
        
        // === VIDEO REACTIONS HANDLERS ===
        add_action('wp_ajax_vh360_set_video_reaction', array($this, 'handle_set_video_reaction'));
        add_action('wp_ajax_vh360_clear_video_reaction', array($this, 'handle_clear_video_reaction'));
        add_action('wp_ajax_nopriv_vh360_get_video_reaction_counts', array($this, 'handle_get_video_reaction_counts'));
        add_action('wp_ajax_vh360_get_video_reaction_counts', array($this, 'handle_get_video_reaction_counts'));
        
        // === PLAYLIST HANDLERS ===
        add_action('wp_ajax_vh360_create_playlist', array($this, 'handle_create_playlist'));
        add_action('wp_ajax_vh360_add_to_playlist', array($this, 'handle_add_to_playlist'));
        add_action('wp_ajax_vh360_remove_from_playlist', array($this, 'handle_remove_from_playlist'));
        add_action('wp_ajax_vh360_get_my_playlists', array($this, 'handle_get_my_playlists'));
        add_action('wp_ajax_vh360_get_playlist_contents', array($this, 'handle_get_playlist_contents'));
        add_action('wp_ajax_vh360_delete_playlist', array($this, 'handle_delete_playlist'));
        add_action('wp_ajax_vh360_get_playlists_with_video', array($this, 'handle_get_playlists_with_video'));
        
        // === DASHBOARD HANDLERS ===
        add_action('wp_ajax_vh360_get_chart_data', array($this, 'handle_get_chart_data'));
        add_action('wp_ajax_vh360_refresh_stats', array($this, 'handle_refresh_stats'));
    }
    
    /**
     * Handle live viewers AJAX
     */
    public function handle_live_viewers() {
        // Check capability - basic read permission required
        if (is_user_logged_in() && !current_user_can('read')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360'));
            return;
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit('live_viewers', 60)) { // 60 requests per minute for viewers count
            wp_send_json_error(__('Rate limit exceeded. Please wait.', 'videohub360'));
            return;
        }
        
        // Security nonce verification
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Input sanitization and validation
        $page_id = intval($_POST['page_id'] ?? 0);
        if (!$page_id) { 
            wp_send_json_error(__('Invalid page ID.', 'videohub360')); 
            return;
        }

        // Sanitize transient key and server variables
        $transient_key = 'vh360_live_viewers_' . absint($page_id);
        $now = time();
        $window = 60; // seconds to keep "active"
        $remote_addr = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $session_id = md5($remote_addr . '|' . $user_agent . '|' . session_id());

        $sessions = get_transient($transient_key);
        if (!is_array($sessions)) $sessions = array();
        
        // Remove expired sessions
        foreach ($sessions as $id => $timestamp) {
            if ($timestamp < ($now - $window)) unset($sessions[$id]);
        }
        
        // Add/update this session
        $sessions[$session_id] = $now;
        set_transient($transient_key, $sessions, $window);

        wp_send_json_success(array('count' => count($sessions)));
    }
    
    /**
     * Handle batch live viewers AJAX
     * Returns viewer counts for multiple pages at once (READ-ONLY)
     * 
     * Note: This endpoint only READS viewer counts, it does NOT record sessions.
     * Sessions are only recorded on the video player page via handle_live_viewers().
     * This prevents counting "page viewers" as "video watchers".
     * 
     * Reuses 'videohub360_chat_nonce' for consistency with single live_viewers endpoint
     * and to avoid requiring separate nonce in JavaScript
     */
    public function handle_live_viewers_batch() {
        // Check capability - basic read permission required
        if (is_user_logged_in() && !current_user_can('read')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360'));
            return;
        }
        
        // Rate limiting check - one check for the entire batch
        if (!$this->check_rate_limit('live_viewers_batch', 30)) { // 30 requests per minute for batch
            wp_send_json_error(__('Rate limit exceeded. Please wait.', 'videohub360'));
            return;
        }
        
        // Security nonce verification (reuse chat nonce)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Input sanitization and validation
        $page_ids = isset($_POST['page_ids']) ? $_POST['page_ids'] : array();
        if (!is_array($page_ids) || empty($page_ids)) {
            wp_send_json_error(__('Invalid page IDs.', 'videohub360'));
            return;
        }
        
        // Limit batch size to prevent abuse
        $page_ids = array_slice($page_ids, 0, 50);
        
        $now = time();
        $window = 60; // seconds to keep "active"
        $results = array();
        
        foreach ($page_ids as $page_id) {
            $page_id = intval($page_id);
            if (!$page_id) {
                continue;
            }
            
            $transient_key = 'vh360_live_viewers_' . absint($page_id);
            $sessions = get_transient($transient_key);
            
            if (!is_array($sessions)) {
                $sessions = array();
            }
            
            // Remove expired sessions (cleanup only, don't modify transient)
            foreach ($sessions as $id => $timestamp) {
                if ($timestamp < ($now - $window)) {
                    unset($sessions[$id]);
                }
            }
            
            // Return count only - do NOT record this visitor's session
            $results[$page_id] = count($sessions);
        }
        
        wp_send_json_success(array('counts' => $results));
    }
    
    /**
     * Handle save watch progress AJAX
     * Saves video watch progress for logged-in users
     */
    public function handle_save_watch_progress() {
        // Must be logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        // Check capability
        if (!current_user_can('read')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360'));
            return;
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit('save_watch_progress', 20)) { // 20 requests per minute
            wp_send_json_error(__('Rate limit exceeded. Please wait.', 'videohub360'));
            return;
        }
        
        // Security nonce verification
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_watch_progress_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Input sanitization and validation
        $post_id = intval($_POST['post_id'] ?? 0);
        $current_time = floatval($_POST['current_time'] ?? 0);
        $duration = floatval($_POST['duration'] ?? 0);
        
        if (!$post_id || $current_time < 0 || $duration <= 0) {
            wp_send_json_error(__('Invalid input data', 'videohub360'));
            return;
        }
        
        // Verify post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video', 'videohub360'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get existing progress data
        $progress_data = get_user_meta($user_id, 'vh360_watch_progress', true);
        if (!is_array($progress_data)) {
            $progress_data = array();
        }
        
        // Update this video's progress
        $progress_data[$post_id] = array(
            'time' => $current_time,
            'duration' => $duration,
            'updated' => time(),
        );
        
        // Sort by updated time (most recent first) and limit to 50 items
        uasort($progress_data, function($a, $b) {
            return $b['updated'] - $a['updated'];
        });
        $progress_data = array_slice($progress_data, 0, 50, true);
        
        // Save back to user meta
        update_user_meta($user_id, 'vh360_watch_progress', $progress_data);
        
        wp_send_json_success(array('message' => 'Progress saved'));
    }
    
    /**
     * Handle email sharing AJAX
     */
    public function handle_share_email() {
        // Allow guest users to share via email - security is handled by nonce verification and rate limiting
        // Only check for basic capability if user is logged in to prevent abuse by banned users
        if (is_user_logged_in() && !current_user_can('read')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360'));
            return;
        }
        
        // Rate limiting check - stricter for email sharing to prevent spam
        if (!$this->check_rate_limit('share_email', 5)) { // 5 emails per minute
            wp_send_json_error(__('Rate limit exceeded. Please wait before sending another email.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_share_email_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $to_email = sanitize_email($_POST['to_email'] ?? '');
        $from_name = sanitize_text_field($_POST['from_name'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (!$post_id || !$to_email || !is_email($to_email)) {
            wp_send_json_error(__('Invalid input data', 'videohub360'));
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post', 'videohub360'));
            return;
        }
        
        // Send email
        $subject = sprintf(__('%s shared a video with you: %s', 'videohub360'), $from_name, $post->post_title);
        $body = sprintf(
            __("%s\n\nWatch it here: %s", 'videohub360'),
            $message,
            get_permalink($post_id)
        );
        
        // Set the From name to site title instead of "WordPress"
        add_filter('wp_mail_from_name', function($name) {
            return get_bloginfo('name');
        });
        
        // Set the From email to noreply@domain
        add_filter('wp_mail_from', function($email) {
            $sitename = wp_parse_url(network_home_url(), PHP_URL_HOST);
            if (substr($sitename, 0, 4) === 'www.') {
                $sitename = substr($sitename, 4);
            }
            return 'noreply@' . $sitename;
        });
        
        $sent = wp_mail($to_email, $subject, $body);
        
        if ($sent) {
            wp_send_json_success(__('Email sent successfully', 'videohub360'));
        } else {
            wp_send_json_error(__('Failed to send email', 'videohub360'));
        }
    }
    
    /**
     * Handle Agora token generation AJAX
     */
    public function handle_generate_agora_token() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_agora_token')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Validate and sanitize inputs
        $post_id = absint($_POST['post_id'] ?? 0);
        $channel_name = sanitize_text_field($_POST['channel_name'] ?? '');
        $uid = absint($_POST['uid'] ?? 0);
        $role = sanitize_text_field($_POST['role'] ?? 'audience');
        
        // Get configuration
        $app_id = get_option('vh360_agora_app_id', '');
        $app_certificate = get_option('vh360_agora_app_certificate', '');
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        // Validate required parameters
        if (empty($app_id)) {
            wp_send_json_error('App ID not configured. Please configure Agora App ID in VideoHub360 Settings.');
            return;
        }
        
        if (empty($channel_name)) {
            wp_send_json_error('Missing required parameter: Channel Name');
            return;
        }
        
        // Validate channel name matches the post's stored channel name
        $stored_channel_name = get_post_meta($post_id, '_vh360_agora_channel_name', true);
        if ($stored_channel_name && $stored_channel_name !== $channel_name) {
            wp_send_json_error('Invalid channel name for this room');
            return;
        }
        
        // Enforce appointment room access control
        $appointment_event_id = get_post_meta($post_id, '_vh360_appointment_event_id', true);
        if ($appointment_event_id) {
            // This is an appointment Live Room - enforce strict membership
            
            // Must be logged in to join appointment rooms
            if (!is_user_logged_in()) {
                wp_send_json_error('You must be logged in to join this appointment session');
                return;
            }
            
            $current_user_id = get_current_user_id();
            
            // Check if user is authorized (professional, client, or admin)
            $is_admin = current_user_can('manage_options');
            $is_room_owner = ((int) $post->post_author === (int) $current_user_id);
            $client_id = get_post_meta($post_id, '_vh360_appointment_client_id', true);
            $is_client = ($client_id && (int) $client_id === (int) $current_user_id);
            
            if (!$is_admin && !$is_room_owner && !$is_client) {
                wp_send_json_error('You do not have permission to join this appointment session');
                return;
            }
        }
        
        // Check user moderation status before generating token
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $moderation_status = videohub360_check_user_moderation_status($current_user_id, $post_id);
            
            if ($moderation_status['status'] === 'banned') {
                wp_send_json_error('You have been banned from this stream. Reason: ' . ($moderation_status['reason'] ?: 'No reason provided'));
                return;
            }
            
            if ($moderation_status['status'] === 'timeout') {
                $expires = human_time_diff(current_time('timestamp'), strtotime($moderation_status['expires']));
                wp_send_json_error('You are temporarily restricted from joining for ' . $expires . '. Reason: ' . ($moderation_status['reason'] ?: 'No reason provided'));
                return;
            }
        }
        
        try {
            // Load Agora Token Builder library
            $library_path = VIDEOHUB360_PLUGIN_DIR . 'agora-token/src/RtcTokenBuilder.php';
            
            if (!file_exists($library_path)) {
                wp_send_json_error(__('Token generation library not found. Please contact support.', 'videohub360'));
                return;
            }
            
            require_once $library_path;
            
            // If no certificate is configured, return setup instructions
            if (empty($app_certificate)) {
                $setup_instructions = array(
                    'token' => '',
                    'app_id' => $app_id,
                    'channel' => $channel_name,
                    'uid' => $uid,
                    'role' => $role,
                    'message' => 'Token not generated - App Certificate not configured',
                    'setup_required' => true,
                    'instructions' => array(
                        '1. Go to VideoHub360 Settings in WordPress admin',
                        '2. Navigate to the "Agora.io Settings" section',
                        '3. Enter your Agora App Certificate (find it in Agora Console)',
                        '4. Save settings and try joining the livestream again'
                    ),
                    'testing_note' => 'For development/testing, you can use the channel without a token by leaving this field empty.',
                    'security_warning' => 'Production streams should always use tokens for security.'
                );
                
                wp_send_json_success($setup_instructions);
                return;
            }
            
            // Generate real Agora token using the Token Builder
            $privilegeExpiredTs = time() + 3600; // 1 hour expiry
            
            // Convert role to Agora role constants
            if ($role === 'host') {
                if (!defined('RtcTokenBuilder::RolePublisher')) {
                    wp_send_json_error(__('Token generation constants not available.', 'videohub360'));
                    return;
                }
                $role_int = RtcTokenBuilder::RolePublisher;
            } else {
                if (!defined('RtcTokenBuilder::RoleSubscriber')) {
                    wp_send_json_error(__('Token generation constants not available.', 'videohub360'));
                    return;
                }
                $role_int = RtcTokenBuilder::RoleSubscriber;
            }
            
            // Generate the token
            $token = RtcTokenBuilder::buildTokenWithUid($app_id, $app_certificate, $channel_name, $uid, $role_int, $privilegeExpiredTs);
            
            if (!$token) {
                wp_send_json_error(__('Failed to generate access token.', 'videohub360'));
                return;
            }
            
            
            wp_send_json_success(array(
                'token' => $token,
                'app_id' => $app_id,
                'channel' => $channel_name,
                'uid' => $uid,
                'role' => $role,
                'role_int' => $role_int,
                'expires_at' => $privilegeExpiredTs,
                'message' => 'Token generated successfully'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Token generation failed: ', 'videohub360') . $e->getMessage());
        }
    }
    
    /**
     * Handle stream status AJAX
     */
    public function handle_stream_status() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_agora_token')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Enhanced input validation and sanitization
        $post_id = absint($_POST['post_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'no');
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        // Check if user has permission to control stream
        if (!$this->user_can_manage_live_room($post_id)) {
            wp_send_json_error('Insufficient permissions to control stream');
            return;
        }
        
        // Validate status value
        if (!in_array($status, ['yes', 'no'])) {
            wp_send_json_error('Invalid status value');
            return;
        }
        
        try {
            // Capture old state before updating (for live room transition hooks)
            $old_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            
            // Check if stream has been explicitly stopped
            $stream_stopped = get_post_meta($post_id, '_vh360_stream_stopped', true);
            
            // If trying to start a stopped stream, prevent it unless explicitly restarted
            if ($status === 'yes' && $stream_stopped === 'yes') {
                wp_send_json_error(__('Stream has been ended. Please use the restart function to go live again.', 'videohub360'));
                return;
            }
            
            // Update the stream live status
            update_post_meta($post_id, '_vh360_agora_stream_live', $status);

            // If status is 'yes', also set live_start_time if not set
            if ($status === 'yes') {
                $existing = get_post_meta($post_id, '_vh360_live_start_time', true);
                if (empty($existing)) {
                    update_post_meta($post_id, '_vh360_live_start_time', current_time('mysql'));
                }
                update_post_meta($post_id, '_vh360_is_live', 'yes');
                delete_post_meta($post_id, '_vh360_stream_stopped');
            }
            if ($status === 'no') {
                update_post_meta($post_id, '_vh360_is_live', 'no');
                // Optionally clear _vh360_live_start_time if you want (not strictly required)
            }
            
            // Get the new _vh360_is_live value after updates
            $new_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            
            // Fire live room transition hooks
            $this->maybe_fire_live_room_transition($post_id, $old_is_live, $new_is_live);

            // Return live_start_time to JS
            $live_start_time = get_post_meta($post_id, '_vh360_live_start_time', true);

            wp_send_json_success(array(
                'message' => __('Stream status updated successfully', 'videohub360'),
                'status' => $status,
                'post_id' => $post_id,
                'live_start_time' => $live_start_time
            ));
            
        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
            wp_send_json_error(__('Failed to update stream status. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle set stream status AJAX (from original plugin)
     */
    public function handle_set_stream_status() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_agora_token')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Enhanced input validation and sanitization
        $post_id = absint($_POST['post_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'no');
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        // Check if user has permission to control stream
        if (!$this->user_can_manage_live_room($post_id)) {
            wp_send_json_error('Insufficient permissions to control stream');
            return;
        }
        
        // Validate status value
        if (!in_array($status, ['yes', 'no'])) {
            wp_send_json_error('Invalid status value');
            return;
        }
        
        try {
            // Capture old state before updating (for live room transition hooks)
            $old_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            
            // Check if stream has been explicitly stopped
            $stream_stopped = get_post_meta($post_id, '_vh360_stream_stopped', true);
            
            // If trying to start a stopped stream, prevent it unless explicitly restarted
            if ($status === 'yes' && $stream_stopped === 'yes') {
                wp_send_json_error(__('Stream has been ended. Please use the restart function to go live again.', 'videohub360'));
                return;
            }
            
            // Update the stream live status
            update_post_meta($post_id, '_vh360_agora_stream_live', $status);

            // If status is 'yes', also set live_start_time if not set
            if ($status === 'yes') {
                $existing = get_post_meta($post_id, '_vh360_live_start_time', true);
                if (empty($existing)) {
                    update_post_meta($post_id, '_vh360_live_start_time', current_time('mysql'));
                }
                update_post_meta($post_id, '_vh360_is_live', 'yes');
                delete_post_meta($post_id, '_vh360_stream_stopped');
            }
            if ($status === 'no') {
                update_post_meta($post_id, '_vh360_is_live', 'no');
                // Optionally clear _vh360_live_start_time if you want (not strictly required)
            }
            
            // Get the new _vh360_is_live value after updates
            $new_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            
            // Fire live room transition hooks
            $this->maybe_fire_live_room_transition($post_id, $old_is_live, $new_is_live);

            // Return live_start_time to JS
            $live_start_time = get_post_meta($post_id, '_vh360_live_start_time', true);

            wp_send_json_success(array(
                'message' => 'Stream status updated successfully',
                'post_id' => $post_id,
                'status' => $status,
                'updated_by' => get_current_user_id(),
                'timestamp' => current_time('mysql'),
                'live_start_time' => $live_start_time
            ));
            
        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
            wp_send_json_error(__('Failed to update stream status. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle get stream status AJAX
     */
    public function handle_get_stream_status() {
        // Enhanced input validation and sanitization
        $post_id = absint($_POST['post_id'] ?? 0);
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        try {
            // Get current stream live status
            $stream_live = get_post_meta($post_id, '_vh360_agora_stream_live', true);
            $is_live_status = get_post_meta($post_id, '_vh360_is_live', true);
            $live_start_time = get_post_meta($post_id, '_vh360_live_start_time', true);
            $stream_stopped = get_post_meta($post_id, '_vh360_stream_stopped', true);
            
            wp_send_json_success(array(
                'post_id' => $post_id,
                'stream_live' => $stream_live === 'yes',
                // is_live should reflect actual stream state, not just livestream mode enabled
                // Frontend JS poller checks is_live to determine if stream is currently broadcasting
                'is_live' => $stream_live === 'yes', 
                'live_start_time' => $live_start_time,
                'stream_stopped' => $stream_stopped === 'yes',
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
            wp_send_json_error(__('Failed to get stream status. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle chat post AJAX
     */
    public function handle_chat_post() {
        // Basic chat post handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat fetch AJAX
     */
    public function handle_chat_fetch() {
        // Basic chat fetch handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat delete AJAX
     */
    public function handle_chat_delete() {
        // Basic chat delete handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat pin AJAX
     */
    public function handle_chat_pin() {
        // Basic chat pin handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat ban AJAX
     */
    public function handle_chat_ban() {
        // Basic chat ban handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat timeout AJAX
     */
    public function handle_chat_timeout() {
        // Basic chat timeout handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle chat report AJAX
     */
    public function handle_chat_report() {
        // Basic chat report handler - can be enhanced later
        wp_send_json_error('Chat functionality not yet implemented in modular structure');
    }
    
    /**
     * Handle end stream AJAX (from original plugin)
     */
    public function handle_end_stream() {
        // Rate limiting check for admin functions
        if (!$this->check_rate_limit('end_stream', 10)) { // 10 requests per minute
            wp_send_json_error(__('Rate limit exceeded. Please wait.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_end_stream')) {
            wp_send_json_error(__('Invalid security token', 'videohub360'));
            return;
        }
        
        // Get post ID
        $post_id = intval($_POST['post_id'] ?? 0);
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post', 'videohub360'));
            return;
        }
        
        // Check if user has permission to end stream
        if (!$this->user_can_manage_live_room($post_id)) {
            wp_send_json_error(__('Insufficient permissions to end stream', 'videohub360'));
            return;
        }
        
        try {
            // Capture old state for Live Room transition hooks
            $old_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            $old_is_live = ($old_is_live === 'yes') ? 'yes' : 'no';

            // Mark stream as stopped (primary flag for ended streams)
            update_post_meta($post_id, '_vh360_stream_stopped', 'yes');
            
            // Stop the Agora stream for audience (removes "LIVE" badges and viewer count)
            update_post_meta($post_id, '_vh360_agora_stream_live', 'no');
            
            // KEEP _vh360_is_live = 'yes' to maintain livestream mode
            // This ensures the video displays the offline message instead of looking for a regular video file
            // The _vh360_stream_stopped flag is what indicates the stream has ended
            
            // KEEP _vh360_live_start_time for historical reference
            // This allows "Streamed X hours/days ago" displays and analytics
            
            // Fire Live Room transition hooks ONLY for Live Room posts (not regular livestreams)
            $context = get_post_meta($post_id, '_vh360_context', true);
            if ($context === 'live_room' && $old_is_live === 'yes') {
                // Live Rooms need special handling (community posts, profile badges, etc.)
                // Fire the ended hook directly since we're keeping the stream in livestream mode
                // but marking it as ended (for offline message display)
                do_action('vh360_live_room_ended', $post_id);
            }
            
            // Log the action
            videohub360_debug_log(sprintf(
                'VideoHub360: Stream ended for post %d (context: %s, stopped: yes, live_mode: preserved)',
                $post_id,
                $context ?: 'default'
            ));
            
            wp_send_json_success(array(
                'message' => __('Stream ended successfully', 'videohub360'),
                'post_id' => $post_id,
                'stream_stopped' => 'yes',
                'ended_by' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
            wp_send_json_error(__('Failed to end stream. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle remove participant AJAX (from original plugin)
     */
/**
 * Frontend: Stop stream (marks room offline + stopped).
 * Intended for Live Room owners/editors.
 */
public function handle_stop_stream() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $nonce   = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID.'));
    }

    if (!$this->user_can_manage_live_room($post_id)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    if (!$nonce || !wp_verify_nonce($nonce, 'vh360_stream_control')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    $context = get_post_meta($post_id, '_vh360_context', true);
    if ($context !== 'live_room') {
        wp_send_json_error(array('message' => 'Not a live room.'));
    }

    $old_is_live = get_post_meta($post_id, '_vh360_is_live', true);
    $old_is_live = ($old_is_live === 'yes') ? 'yes' : 'no';

    update_post_meta($post_id, '_vh360_stream_stopped', 'yes');
    // KEEP _vh360_is_live = 'yes' to maintain livestream status in backend
    // Don't set it to 'no' - this causes "No-regular video" to appear
    // The _vh360_stream_stopped flag is what indicates the stream has ended
    
    // Don't delete the start time - keep it for historical reference
    // delete_post_meta($post_id, '_vh360_live_start_time');

    if (method_exists($this, 'maybe_fire_live_room_transition')) {
        $this->maybe_fire_live_room_transition($post_id, $old_is_live, 'no');
    } else {
        if ($old_is_live === 'yes') {
            do_action('vh360_live_room_ended', $post_id);
        }
    }

    wp_send_json_success(array('message' => 'Stream stopped.'));
}

/**
 * Frontend: Restart stream (clears stopped flag; room remains offline until Start Live Stream).
 */
public function handle_restart_stream() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $nonce   = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID.'));
    }

    if (!$this->user_can_manage_live_room($post_id)) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    if (!$nonce || !wp_verify_nonce($nonce, 'vh360_stream_control')) {
        // Also try agoraTokenNonce as fallback for compatibility
        if (!wp_verify_nonce($nonce, 'vh360_agora_token')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
    }

    // Remove the context check - allow restart for any videohub360 post
    // This allows the restart button to work both on frontend Live Room and backend admin
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'videohub360') {
        wp_send_json_error(array('message' => 'Invalid post type.'));
    }

    // Clear the "stopped/ended" state
    update_post_meta($post_id, '_vh360_stream_stopped', 'no');
    
    // Mark broadcast as not actively live until host starts again
    update_post_meta($post_id, '_vh360_agora_stream_live', 'no');
    
    // Reset session timing for clean restart
    delete_post_meta($post_id, '_vh360_live_start_time');
    
    // IMPORTANT: _vh360_is_live is intentionally NOT modified here to preserve livestream mode
    // Previously this was set to 'no', which broke Live Room pages by switching them out of livestream mode
    // This ensures Live Room pages remain in livestream mode and chat assets stay enqueued

    wp_send_json_success(array('message' => 'Stream restart enabled.'));
}


    public function handle_remove_participant() {
        // Rate limiting check for moderation actions
        if (!$this->check_rate_limit('remove_participant', 20)) { // 20 requests per minute
            $this->debug_log('VideoHub360: Rate limit exceeded for remove_participant');
            wp_send_json_error(__('Rate limit exceeded. Please wait.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to moderate participants.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            $this->debug_log('VideoHub360: Nonce verification failed. Received nonce: ' . ($_POST['nonce'] ?? 'none'));
            $this->debug_log('VideoHub360: Expected nonce action: videohub360_chat_nonce');
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Validate and sanitize input - parse post_id early for permission check
        $post_id = absint($_POST['post_id'] ?? 0);
        $target_uid = absint($_POST['target_uid'] ?? 0);
        $target_user_id = absint($_POST['target_user_id'] ?? 0);
        $target_ip = sanitize_text_field($_POST['target_ip'] ?? '');
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        // Validate required parameters
        if (!$post_id || !$target_uid || !$action_type) {
            $this->debug_log(sprintf('VideoHub360: Missing required parameters. post_id=%d, target_uid=%d, action_type=%s', 
                $post_id, $target_uid, $action_type));
            wp_send_json_error(__('Missing required parameters.', 'videohub360'));
            return;
        }
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post.', 'videohub360'));
            return;
        }
        
        // Check moderation permissions with post-aware check
        $current_user_id = get_current_user_id();
        $can_moderate = $this->user_can_moderate($current_user_id, $post_id);
        if (!$can_moderate) {
            $this->debug_log('VideoHub360: User permission check failed for post ' . $post_id);
            wp_send_json_error(__('You do not have permission to moderate participants.', 'videohub360'));
            return;
        }
        
        // Debug: Log incoming request for troubleshooting
        $debug_data = array(
            'post_data' => $_POST,
            'user_logged_in' => is_user_logged_in(),
            'user_id' => $current_user_id,
            'can_moderate' => $can_moderate,
            'timestamp' => current_time('mysql')
        );
        
        // Enhanced debug logging for admins
        $this->debug_log('VideoHub360: handle_remove_participant called', $_POST);
        $this->debug_log('VideoHub360: User can moderate: ' . ($can_moderate ? 'yes' : 'no'));
        
        // If target IP not provided but we have UID, try to look it up from recent activity
        if (!$target_ip && $target_uid) {
            $target_ip = $this->get_user_ip_by_uid($target_uid, $post_id);
        }
        
        // Validate action type
        if (!in_array($action_type, ['kick', 'timeout', 'ban'], true)) {
            wp_send_json_error(__('Invalid moderation action.', 'videohub360'));
            return;
        }
        
        // Check if post uses Agora - allow moderation regardless of live status
        // since participants might be present even if stream status isn't properly updated
        $agora_mode = get_post_meta($post_id, '_vh360_agora_mode', true);
        
        // Only check if Agora is configured for this post
        // Default to 'interactive' if not set, as per frontend configuration
        if (empty($agora_mode)) {
            $agora_mode = 'interactive';
        }
        
        // Debug info for admins if needed
        $is_live = get_post_meta($post_id, '_vh360_is_live', true);
        $this->debug_log(sprintf('VideoHub360: Moderation - Post %d: is_live=%s, agora_mode=%s', 
            $post_id, $is_live, $agora_mode));
        
        // Prevent self-moderation
        if ($target_user_id && $target_user_id === $current_user_id) {
            wp_send_json_error(__('You cannot moderate yourself.', 'videohub360'));
            return;
        }
        
        // Prevent moderating other moderators/admins
        if ($target_user_id && $this->user_can_moderate($target_user_id, $post_id)) {
            wp_send_json_error(__('Cannot moderate other moderators or administrators.', 'videohub360'));
            return;
        }
        
        // For interactive mode, ensure only original host can moderate
        // But allow admins and users with moderate_comments capability
        if ($agora_mode === 'interactive' && 
            !current_user_can('manage_options') && 
            !current_user_can('edit_post', $post_id) && 
            !current_user_can('moderate_comments')) {
            wp_send_json_error(__('Only the original host or users with moderation permissions can moderate participants in interactive mode.', 'videohub360'));
            return;
        }
        
        try {
            // Prepare expiration time for timeout actions
            $expiration_time = null;
            if ($action_type === 'timeout') {
                // Default timeout duration of 5 minutes if not specified
                $timeout_duration = 5; // minutes
                $expiration_time = date('Y-m-d H:i:s', current_time('timestamp') + ($timeout_duration * 60));
            }
            
            // Save moderation action to database
            global $wpdb;
            $moderation_table = $wpdb->prefix . 'videohub360_moderation_actions';
            
            // Check if table exists before inserting
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$moderation_table'") == $moderation_table;
            if (!$table_exists) {
                // Force database table creation
                VideoHub360_Core::activate();
                
                // Check again
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$moderation_table'") == $moderation_table;
                if (!$table_exists) {
                    wp_send_json_error(__('Database error: Unable to create moderation table.', 'videohub360'));
                    return;
                }
            }
            
            // Log the action being attempted
            
            $result = $wpdb->insert(
                $moderation_table,
                array(
                    'post_id' => $post_id,
                    'target_user_id' => $target_user_id ?? 0,
                    'target_uid' => $target_uid,
                    'target_ip' => $target_ip ?: null,
                    'moderator_user_id' => get_current_user_id(),
                    'message_id' => null, // No message ID for participant moderation
                    'action_type' => $action_type,
                    'source_type' => 'agora', // Mark as Agora moderation
                    'reason' => $reason ?: "Participant {$action_type} via stream controls",
                    'expiration_time' => $expiration_time,
                    'created_at' => current_time('mysql'),
                    'is_active' => 1
                ),
                array('%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
            );
            
            if ($result === false) {
                $this->debug_log('VideoHub360: Database insert failed. Error: ' . $wpdb->last_error);
                $this->debug_log('VideoHub360: Insert data', array(
                    'post_id' => $post_id,
                    'target_uid' => $target_uid,
                    'target_user_id' => $target_user_id,
                    'action_type' => $action_type,
                    'source_type' => 'agora'
                ));
                wp_send_json_error(__('Failed to record moderation action: ' . $wpdb->last_error, 'videohub360'));
                return;
            }
            
            $this->debug_log('VideoHub360: Moderation action inserted successfully. ID: ' . $wpdb->insert_id);
            
            
            $moderation_id = $wpdb->insert_id;
            
            // Prepare real-time data for frontend notification
            $realtime_data = array(
                'action' => $action_type,
                'target_uid' => $target_uid,
                'target_user_id' => $target_user_id,
                'moderator_name' => wp_get_current_user()->display_name,
                'reason' => $reason,
                'moderation_id' => $moderation_id
            );
            
            if ($action_type === 'timeout' && $expiration_time) {
                $realtime_data['expiration_time'] = $expiration_time;
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Participant %s successfully.', 'videohub360'), 
                    $action_type === 'kick' ? 'kicked' : ($action_type . 'ed')),
                'action' => $action_type,
                'target_uid' => $target_uid,
                'post_id' => $post_id,
                'timestamp' => current_time('mysql'),
                'moderation_id' => $moderation_id,
                'realtime_data' => $realtime_data
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to process moderation action: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper method to check if user can moderate
     */
    private function user_can_moderate($user_id = null, $post_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // global moderators/admins
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'moderate_comments')) {
            return true;
        }
        
        // live room author/editor can moderate that room
        if ($post_id && get_post_type($post_id) === 'videohub360') {
            $post = get_post($post_id);
            // Check authorship first (direct ownership) before capability check
            // Both checks are kept because authorship is a direct right independent of capabilities
            if ($post && (int)$post->post_author === (int)$user_id) return true;
            if (user_can($user_id, 'edit_post', $post_id)) return true;
        }
        
        return false;
    }
    
    /**
     * Handle get moderated users AJAX
     */
    public function handle_get_moderated_users() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to access moderation tools.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Validate input - parse post_id early for permission check
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || get_post_type($post_id) !== 'videohub360') {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Check moderation permissions with post-aware check
        if (!$this->user_can_moderate(get_current_user_id(), $post_id)) {
            wp_send_json_error(__('You do not have permission to access moderation tools.', 'videohub360'));
            return;
        }
        
        
        global $wpdb;
        $moderation_table = $wpdb->prefix . 'videohub360_moderation_actions';
        $users_table = $wpdb->users;
        
        // Add debugging for current time
        $current_mysql_time = current_time('mysql');
        
        // Array to collect debug errors (for admin use only)
        $debug_errors = array();
        
        try {
            // Get banned users separated by source type
            $chat_banned_query = $wpdb->prepare(
                "SELECT m.*, u.display_name as target_display_name, moderator_user.display_name as moderator_display_name
                 FROM $moderation_table m
                 LEFT JOIN $users_table u ON m.target_user_id = u.ID
                 LEFT JOIN $users_table moderator_user ON m.moderator_user_id = moderator_user.ID
                 WHERE m.post_id = %d AND m.action_type = 'ban' AND m.is_active = 1 
                 AND (m.source_type = 'chat' OR m.source_type IS NULL)
                 ORDER BY m.created_at DESC",
                $post_id
            );
            
            $chat_banned_users = $wpdb->get_results($chat_banned_query);
            if ($wpdb->last_error) {
                $debug_errors[] = 'chat_banned_query: ' . $wpdb->last_error;
            }
            
            $agora_banned_query = $wpdb->prepare(
                "SELECT m.*, u.display_name as target_display_name, moderator_user.display_name as moderator_display_name
                 FROM $moderation_table m
                 LEFT JOIN $users_table u ON m.target_user_id = u.ID
                 LEFT JOIN $users_table moderator_user ON m.moderator_user_id = moderator_user.ID
                 WHERE m.post_id = %d AND m.action_type = 'ban' AND m.is_active = 1 
                 AND m.source_type = 'agora'
                 ORDER BY m.created_at DESC",
                $post_id
            );
            
            $agora_banned_users = $wpdb->get_results($agora_banned_query);
            if ($wpdb->last_error) {
                $debug_errors[] = 'agora_banned_query: ' . $wpdb->last_error;
            }
            
            // Get timed out users separated by source type (active timeouts only)
            $chat_timeout_query = $wpdb->prepare(
                "SELECT m.*, u.display_name as target_display_name, moderator_user.display_name as moderator_display_name
                 FROM $moderation_table m
                 LEFT JOIN $users_table u ON m.target_user_id = u.ID
                 LEFT JOIN $users_table moderator_user ON m.moderator_user_id = moderator_user.ID
                 WHERE m.post_id = %d AND m.action_type = 'timeout' AND m.is_active = 1
                 AND (m.source_type = 'chat' OR m.source_type IS NULL)
                 AND (m.expiration_time IS NULL OR m.expiration_time > %s)
                 ORDER BY m.created_at DESC",
                $post_id, $current_mysql_time
            );
            
            $chat_timed_out_users = $wpdb->get_results($chat_timeout_query);
            if ($wpdb->last_error) {
                $debug_errors[] = 'chat_timeout_query: ' . $wpdb->last_error;
            }
            
            $agora_timeout_query = $wpdb->prepare(
                "SELECT m.*, u.display_name as target_display_name, moderator_user.display_name as moderator_display_name
                 FROM $moderation_table m
                 LEFT JOIN $users_table u ON m.target_user_id = u.ID
                 LEFT JOIN $users_table moderator_user ON m.moderator_user_id = moderator_user.ID
                 WHERE m.post_id = %d AND m.action_type = 'timeout' AND m.is_active = 1
                 AND m.source_type = 'agora'
                 AND (m.expiration_time IS NULL OR m.expiration_time > %s)
                 ORDER BY m.created_at DESC",
                $post_id, $current_mysql_time
            );
            
            $agora_timed_out_users = $wpdb->get_results($agora_timeout_query);
            if ($wpdb->last_error) {
                $debug_errors[] = 'agora_timeout_query: ' . $wpdb->last_error;
            }
            
            // Debug logging for retrieved data
            
            if ($chat_banned_users) {
            }
            if ($chat_timed_out_users) {
            }
            
            // Helper function to format user data
            $format_user_data = function($users, $include_expiration = false) {
                $formatted = array();
                foreach ($users as $user) {
                    $data = array(
                        'id' => intval($user->id),
                        'target_user_id' => intval($user->target_user_id),
                        'username' => esc_html($user->target_display_name ?: 'Unknown User'),
                        'reason' => esc_html($user->reason ?: 'No reason provided'),
                        'moderator' => esc_html($user->moderator_display_name ?: 'Unknown Moderator'),
                        'moderator_id' => intval($user->moderator_user_id),
                        'date' => esc_html(mysql2date('M j, Y g:i A', $user->created_at)),
                        'created_at' => esc_html($user->created_at),
                        'source_type' => esc_html($user->source_type ?: 'chat')
                    );
                    
                    if ($include_expiration) {
                        $data['expiration'] = $user->expiration_time ? esc_html(mysql2date('M j, Y g:i A', $user->expiration_time)) : 'Permanent';
                        $data['expiration_time'] = esc_html($user->expiration_time);
                    }
                    
                    $formatted[] = $data;
                }
                return $formatted;
            };
            
            // Format the data
            $formatted_chat_banned = $format_user_data($chat_banned_users);
            $formatted_agora_banned = $format_user_data($agora_banned_users);
            $formatted_chat_timeouts = $format_user_data($chat_timed_out_users, true);
            $formatted_agora_timeouts = $format_user_data($agora_timed_out_users, true);
            
            // For backward compatibility, also include the original format
            $all_banned = array_merge($formatted_chat_banned, $formatted_agora_banned);
            $all_timeouts = array_merge($formatted_chat_timeouts, $formatted_agora_timeouts);
            
            $response = array(
                // New categorized format
                'chat_banned_users' => $formatted_chat_banned,
                'chat_timed_out_users' => $formatted_chat_timeouts,
                'agora_banned_users' => $formatted_agora_banned,
                'agora_timed_out_users' => $formatted_agora_timeouts,
                
                // Counts for each category
                'chat_banned_count' => count($formatted_chat_banned),
                'chat_timeout_count' => count($formatted_chat_timeouts),
                'agora_banned_count' => count($formatted_agora_banned),
                'agora_timeout_count' => count($formatted_agora_timeouts),
                
                // Legacy format for backward compatibility
                'banned_users' => $all_banned,
                'timed_out_users' => $all_timeouts,
                'total_banned' => count($all_banned),
                'total_timeouts' => count($all_timeouts)
            );
            
            // Add debug errors for admin users only (non-intrusive diagnostic)
            if (!empty($debug_errors) && current_user_can('manage_options')) {
                $response['debug_errors'] = $debug_errors;
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to fetch moderated users. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle unban user AJAX
     */
    public function handle_unban_user() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform moderation actions.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Validate input - parse post_id early for permission check
        $ban_id = intval($_POST['ban_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$ban_id || !$post_id) {
            wp_send_json_error(__('Invalid ban ID or post ID.', 'videohub360'));
            return;
        }
        
        if (get_post_type($post_id) !== 'videohub360') {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Check moderation permissions with post-aware check
        if (!$this->user_can_moderate(get_current_user_id(), $post_id)) {
            wp_send_json_error(__('You do not have permission to perform moderation actions.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $moderation_table = $wpdb->prefix . 'videohub360_moderation_actions';
        
        try {
            // Verify the ban exists and belongs to the specified post
            $ban = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $moderation_table WHERE id = %d AND post_id = %d AND action_type = 'ban' AND is_active = 1",
                $ban_id, $post_id
            ));
            
            if (!$ban) {
                wp_send_json_error(__('Ban record not found or already inactive.', 'videohub360'));
                return;
            }
            
            // Deactivate the ban
            $result = $wpdb->update(
                $moderation_table,
                array('is_active' => 0),
                array('id' => $ban_id),
                array('%d'),
                array('%d')
            );
            
            if ($result === false) {
                wp_send_json_error(__('Failed to remove ban. Please try again.', 'videohub360'));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('User has been unbanned successfully.', 'videohub360'),
                'ban_id' => $ban_id
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to remove ban. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle display name lookup for Agora participants
     */
    public function handle_lookup_display_name() {
        // This endpoint is available to both logged-in and guest users
        // since they need to see each other's display names in the stream
        
        // Validate input
        $uid = intval($_POST['uid'] ?? 0);
        $wordpress_user_id = intval($_POST['wordpress_user_id'] ?? 0);
        
        if (!$uid) {
            wp_send_json_error(__('Invalid UID provided.', 'videohub360'));
            return;
        }
        
        try {
            $user = null;
            $lookup_method = '';
            
            // First try with provided WordPress user ID if available
            if ($wordpress_user_id > 0) {
                $user = get_user_by('ID', $wordpress_user_id);
                $lookup_method = 'wordpress_user_id';
            }
            
            // If not found, try to find by UID in user meta (for direct Agora UID mapping)
            if (!$user || is_wp_error($user)) {
                global $wpdb;
                $user_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->usermeta} 
                     WHERE meta_key = 'vh360_agora_uid' AND meta_value = %s",
                    $uid
                ));
                
                if ($user_id) {
                    $user = get_user_by('ID', $user_id);
                    $lookup_method = 'agora_uid_meta';
                }
            }
            
            // If still not found, try using UID as WordPress user ID (legacy behavior)
            if (!$user || is_wp_error($user)) {
                $user = get_user_by('ID', $uid);
                $lookup_method = 'uid_as_user_id';
            }
            
            if ($user && !is_wp_error($user)) {
                // User found - return their display name
                wp_send_json_success(array(
                    'uid' => $uid,
                    'wordpress_user_id' => $user->ID,
                    'display_name' => $user->display_name,
                    'source' => $lookup_method
                ));
            } else {
                // User not found - this might be a guest or invalid UID
                wp_send_json_success(array(
                    'uid' => $uid,
                    'display_name' => "User {$uid}",
                    'source' => 'fallback'
                ));
            }
            
        } catch (Exception $e) {
            
            // Return fallback name instead of error to maintain stream functionality
            wp_send_json_success(array(
                'uid' => $uid,
                'display_name' => "User {$uid}",
                'source' => 'error_fallback'
            ));
        }
    }
    
    /**
     * Handle remove timeout AJAX
     */
    public function handle_remove_timeout() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to perform moderation actions.', 'videohub360'));
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'videohub360_chat_nonce')) {
            wp_send_json_error(__('Security check failed. Please refresh the page.', 'videohub360'));
            return;
        }
        
        // Validate input - parse post_id early for permission check
        $timeout_id = intval($_POST['timeout_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$timeout_id || !$post_id) {
            wp_send_json_error(__('Invalid timeout ID or post ID.', 'videohub360'));
            return;
        }
        
        if (get_post_type($post_id) !== 'videohub360') {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Check moderation permissions with post-aware check
        if (!$this->user_can_moderate(get_current_user_id(), $post_id)) {
            wp_send_json_error(__('You do not have permission to perform moderation actions.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $moderation_table = $wpdb->prefix . 'videohub360_moderation_actions';
        
        try {
            // Verify the timeout exists and belongs to the specified post
            $timeout = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $moderation_table WHERE id = %d AND post_id = %d AND action_type = 'timeout' AND is_active = 1",
                $timeout_id, $post_id
            ));
            
            if (!$timeout) {
                wp_send_json_error(__('Timeout record not found or already inactive.', 'videohub360'));
                return;
            }
            
            // Deactivate the timeout
            $result = $wpdb->update(
                $moderation_table,
                array('is_active' => 0),
                array('id' => $timeout_id),
                array('%d'),
                array('%d')
            );
            
            if ($result === false) {
                wp_send_json_error(__('Failed to remove timeout. Please try again.', 'videohub360'));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Timeout has been removed successfully.', 'videohub360'),
                'timeout_id' => $timeout_id
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to remove timeout. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle check moderation status AJAX
     */
    public function handle_check_moderation_status() {
        // Validate input
        $post_id = absint($_POST['post_id'] ?? 0);
        $user_id = absint($_POST['user_id'] ?? 0);
        $agora_uid = absint($_POST['agora_uid'] ?? 0);
        
        // Get client IP address
        $client_ip = $this->get_client_ip();
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(__('Invalid video post.', 'videohub360'));
            return;
        }
        
        // If no user_id provided, use current user (if logged in)
        if (!$user_id) {
            if (!is_user_logged_in()) {
                // For non-logged-in users, check by Agora UID and IP address
                if ($agora_uid || $client_ip) {
                    // Track the UID→IP mapping for future moderation actions
                    if ($agora_uid && $client_ip) {
                        $this->track_user_ip($agora_uid, $client_ip, $post_id);
                    }
                    
                    $moderation_status = videohub360_check_uid_moderation_status($agora_uid, $post_id, $client_ip);
                    
                    $response = array(
                        'status' => $moderation_status['status'],
                        'can_chat' => $moderation_status['status'] === 'allowed',
                        'can_join_stream' => $moderation_status['status'] === 'allowed'
                    );
                    
                    // Add additional details for banned/timed-out users
                    if ($moderation_status['status'] === 'banned') {
                        $response['reason'] = $moderation_status['reason'] ?: 'No reason provided';
                        $response['message'] = 'You have been banned from this stream. Reason: ' . $response['reason'];
                    } elseif ($moderation_status['status'] === 'timeout') {
                        $response['reason'] = $moderation_status['reason'] ?: 'No reason provided';
                        $response['expires'] = $moderation_status['expires'];
                        $expires = human_time_diff(current_time('timestamp'), strtotime($moderation_status['expires']));
                        $response['message'] = 'You are temporarily restricted for ' . $expires . '. Reason: ' . $response['reason'];
                    }
                    
                    wp_send_json_success($response);
                    return;
                }
                
                // If no Agora UID or IP provided, guest users are allowed by default
                wp_send_json_success(array(
                    'status' => 'allowed',
                    'can_chat' => true,
                    'can_join_stream' => true
                ));
                return;
            }
            $user_id = get_current_user_id();
        }
        
        try {
            // Get moderation status using the standardized function
            $moderation_status = videohub360_check_user_moderation_status($user_id, $post_id);
            
            $response = array(
                'status' => $moderation_status['status'],
                'can_chat' => $moderation_status['status'] === 'allowed',
                'can_join_stream' => $moderation_status['status'] === 'allowed'
            );
            
            // Add additional details for banned/timed-out users
            if ($moderation_status['status'] === 'banned') {
                $response['reason'] = $moderation_status['reason'] ?: 'No reason provided';
                $response['message'] = 'You have been banned from this stream. Reason: ' . $response['reason'];
            } elseif ($moderation_status['status'] === 'timeout') {
                $response['reason'] = $moderation_status['reason'] ?: 'No reason provided';
                $response['expires'] = $moderation_status['expires'];
                $expires = human_time_diff(current_time('timestamp'), strtotime($moderation_status['expires']));
                $response['message'] = 'You are temporarily restricted for ' . $expires . '. Reason: ' . $response['reason'];
            }
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to check moderation status. Please try again.', 'videohub360'));
        }
    }
    
    /**
     * Handle debug moderation status AJAX - for troubleshooting
     */
    public function handle_debug_moderation_status() {
        // Only for administrators  
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Access denied.', 'videohub360'));
            return;
        }
        
        $user_id = absint($_POST['user_id'] ?? 0);
        $post_id = absint($_POST['post_id'] ?? 0);
        
        if (!$user_id || !$post_id) {
            wp_send_json_error(__('Invalid user or post ID.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Get all moderation records for this user and post
        $all_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE target_user_id = %d AND post_id = %d ORDER BY created_at DESC",
            $user_id, $post_id
        ));
        
        // Check current moderation status
        $status = videohub360_check_user_moderation_status($user_id, $post_id);
        
        // Get current time info
        $current_mysql = current_time('mysql');
        $current_timestamp = current_time('timestamp');
        
        wp_send_json_success(array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'current_status' => $status,
            'current_mysql_time' => $current_mysql,
            'current_timestamp' => $current_timestamp,
            'all_moderation_records' => $all_records,
            'table_name' => $table_name,
            'table_exists' => ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name),
            'record_count' => count($all_records)
        ));
    }
    
    /**
     * Check for recent moderation activity in a stream
     * This endpoint allows users to detect if moderation actions occurred recently
     * even if they missed the real-time notifications
     */
    public function handle_check_recent_moderation_activity() {
        $post_id = absint($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID.', 'videohub360'));
            return;
        }
        
        // Validate post exists and is videohub360 type
        if (!get_post($post_id) || get_post_type($post_id) !== 'videohub360') {
            wp_send_json_error(__('Invalid post.', 'videohub360'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_success(array(
                'has_recent_activity' => false,
                'activity_count' => 0
            ));
            return;
        }
        
        try {
            // Check for moderation actions in the last 60 seconds
            $recent_activity = $wpdb->get_results($wpdb->prepare(
                "SELECT id, action_type, target_user_id, created_at 
                 FROM $table_name 
                 WHERE post_id = %d 
                 AND source_type = 'agora' 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
                 ORDER BY created_at DESC",
                $post_id
            ));
            
            wp_send_json_success(array(
                'has_recent_activity' => !empty($recent_activity),
                'activity_count' => count($recent_activity),
                'last_activity_time' => !empty($recent_activity) ? $recent_activity[0]->created_at : null
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to check recent moderation activity.', 'videohub360'));
        }
    }
    
    /**
     * Get client IP address reliably
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR'            // Direct connection
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle multiple IPs (X-Forwarded-For can have multiple)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
                
                // If private/reserved IP, still return it (for local testing)
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Track user IP by UID for guest users
     * Stores IP→UID mapping in transients for quick lookup
     */
    private function track_user_ip($uid, $ip, $post_id) {
        if (!$uid || !$ip) {
            return;
        }
        
        // Store UID→IP mapping in a transient (expires after 1 hour)
        $transient_key = 'vh360_uid_ip_' . $post_id . '_' . $uid;
        set_transient($transient_key, $ip, HOUR_IN_SECONDS);
    }
    
    /**
     * Get user IP by UID from tracked data
     * Looks up IP address for a given UID
     */
    private function get_user_ip_by_uid($uid, $post_id) {
        if (!$uid) {
            return '';
        }
        
        // Try to get from transient first
        $transient_key = 'vh360_uid_ip_' . $post_id . '_' . $uid;
        $ip = get_transient($transient_key);
        
        if ($ip) {
            return $ip;
        }
        
        // If not in transient, check database for existing records
        global $wpdb;
        $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT target_ip FROM $table_name 
             WHERE target_uid = %d AND post_id = %d AND target_ip IS NOT NULL
             ORDER BY created_at DESC LIMIT 1",
            $uid, $post_id
        ));
        
        return $result ?: '';
    }
    
    /**
     * Handle ad click tracking
     * 
     * @since 1.0.0
     */
    public function handle_track_ad_click() {
        // Rate limiting - allow up to 10 clicks per minute per user/IP
        if (!$this->check_rate_limit('ad_click', 10)) {
            // Silently fail for rate limit to not disrupt user experience
            wp_send_json_success(array('message' => 'Rate limit'));
            return;
        }
        
        // Get and validate input
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $ad_type = isset($_POST['ad_type']) ? sanitize_text_field($_POST['ad_type']) : '';
        
        // Validate post ID
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }
        
        // Validate ad type
        $valid_ad_types = array('preroll', 'midroll', 'postroll');
        if (!in_array($ad_type, $valid_ad_types, true)) {
            wp_send_json_error(array('message' => 'Invalid ad type'));
            return;
        }
        
        // Verify the post exists and is the correct type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error(array('message' => 'Invalid video post'));
            return;
        }
        
        // Check if tracking is enabled
        $tracking_enabled = get_option('vh360_ad_click_tracking_enabled', 0);
        if (!$tracking_enabled) {
            // Tracking disabled, return success but don't track
            wp_send_json_success(array('message' => 'Tracking disabled'));
            return;
        }
        
        // Collect tracking data
        $user_id = get_current_user_id();
        $timestamp = current_time('mysql');
        $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Store in post meta (simple approach for MVP)
        // Format: array of click events
        $meta_key = '_vh360_ad_clicks_' . $ad_type;
        $existing_clicks = get_post_meta($post_id, $meta_key, true);
        if (!is_array($existing_clicks)) {
            $existing_clicks = array();
        }
        
        // Add new click event
        $existing_clicks[] = array(
            'timestamp' => $timestamp,
            'user_id' => $user_id,
            'ip_hash' => hash('sha256', $user_ip), // Hash IP for privacy
            'user_agent' => substr($user_agent, 0, 200) // Limit length
        );
        
        // Keep only last 100 clicks to avoid meta bloat
        if (count($existing_clicks) > 100) {
            $existing_clicks = array_slice($existing_clicks, -100);
        }
        
        update_post_meta($post_id, $meta_key, $existing_clicks);
        
        // Also increment a simple counter for quick stats
        $counter_key = '_vh360_ad_clicks_count_' . $ad_type;
        $current_count = get_post_meta($post_id, $counter_key, true);
        $current_count = $current_count ? intval($current_count) : 0;
        update_post_meta($post_id, $counter_key, $current_count + 1);
        
        wp_send_json_success(array(
            'message' => 'Click tracked',
            'post_id' => $post_id,
            'ad_type' => $ad_type
        ));
    }
    
    /**
     * Handle get chart data AJAX request
     */
    public function handle_get_chart_data() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get views data for the last 7 days
        $chart_data = $this->get_views_chart_data(7);
        
        wp_send_json_success($chart_data);
    }
    
    /**
     * Handle refresh stats AJAX request
     */
    public function handle_refresh_stats() {
        // Verify nonce
        if (!check_ajax_referer('vh360_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get fresh statistics
        $stats = $this->get_fresh_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get views chart data for specified number of days
     * 
     * @param int $days Number of days to retrieve
     * @return array Chart data with labels and values
     */
    private function get_views_chart_data($days = 7) {
        global $wpdb;
        
        $labels = array();
        $views = array();
        
        // Generate data for each day
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));
            
            // For now, we'll use a simple count of posts modified on that day
            // In a production environment, you'd want to track actual daily views
            $day_start = $date . ' 00:00:00';
            $day_end = $date . ' 23:59:59';
            
            // Count posts modified on this day as a proxy for activity
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'videohub360' 
                 AND post_status = 'publish'
                 AND post_modified >= %s 
                 AND post_modified <= %s",
                $day_start,
                $day_end
            ));
            
            // For demonstration, use a deterministic formula based on post count
            // In production, you'd track actual daily views in a separate table
            // This provides consistent data for testing and initial setup
            $base_views = intval($count) * 15; // Base multiplier
            $day_offset = ($days - 1 - $i) * 5; // Slight variation by day
            $views[] = max(0, $base_views + $day_offset);
        }
        
        return array(
            'labels' => $labels,
            'views' => $views
        );
    }
    
    /**
     * Maybe fire live room transition hooks
     * Centralized method to handle live room state transitions
     * 
     * This method checks if a post is a live_room and fires appropriate hooks
     * when the live status transitions between offline and live states.
     * 
     * @param int $post_id The post ID
     * @param string $old_is_live The old live status value ('yes', 'no', or empty string)
     * @param string $new_is_live The new live status value ('yes', 'no', or empty string)
     * @return void
     */
    private function maybe_fire_live_room_transition($post_id, $old_is_live, $new_is_live) {
        // Get the context to check if this is a live room
        $context = get_post_meta($post_id, '_vh360_context', true);
        
        // Only process live room transitions (not regular videos)
        if ($context !== 'live_room') {
            return;
        }
        
        // Normalize states to 'yes' or 'no' for consistent comparison
        // Treats empty strings and any non-'yes' value as 'no'
        $normalized_old = ($old_is_live === 'yes') ? 'yes' : 'no';
        $normalized_new = ($new_is_live === 'yes') ? 'yes' : 'no';
        
        // Live room started: offline→live transition
        if ($normalized_old === 'no' && $normalized_new === 'yes') {
            do_action('vh360_live_room_started', $post_id);
        }
        
        // Failsafe: if the room is marked live already but no 'went live' community post exists yet,
        // fire the started hook once so the theme can create it.
        if ($normalized_old === 'yes' && $normalized_new === 'yes') {
            $went_live_post_id = get_post_meta($post_id, '_vh360_went_live_post_id', true);
            if (empty($went_live_post_id)) {
                do_action('vh360_live_room_started', $post_id);
            }
        }

        // Live room ended: live→offline transition
        if ($normalized_old === 'yes' && $normalized_new === 'no') {
            do_action('vh360_live_room_ended', $post_id);
        }
    }
    
    /**
     * Get fresh statistics
     * Delegates to the shared statistics calculation method
     * 
     * @return array Statistics data
     */
    private function get_fresh_stats() {
        // Use the shared statistics calculation from the admin class
        return VideoHub360_Admin::calculate_statistics();
    }
    
    /**
     * Handle set video reaction AJAX request
     */
    public function handle_set_video_reaction() {
        // Rate limiting - allow up to 10 reactions per 60 seconds
        if (!$this->check_rate_limit('set_video_reaction', 10)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_video_reaction')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to react to videos.', 'videohub360'));
            return;
        }
        
        $video_id = absint($_POST['video_id'] ?? 0);
        $reaction = sanitize_text_field($_POST['reaction'] ?? '');
        $user_id = get_current_user_id();
        
        if (!$video_id) {
            wp_send_json_error(__('Invalid video ID.', 'videohub360'));
            return;
        }
        
        if (!in_array($reaction, array('like', 'dislike'))) {
            wp_send_json_error(__('Invalid reaction type.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Video_Reactions::set_reaction($video_id, $user_id, $reaction);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle clear video reaction AJAX request
     */
    public function handle_clear_video_reaction() {
        // Rate limiting
        if (!$this->check_rate_limit('clear_video_reaction', 30)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_video_reaction')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $video_id = absint($_POST['video_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$video_id) {
            wp_send_json_error(__('Invalid video ID.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Video_Reactions::clear_reaction($video_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle get video reaction counts AJAX request
     */
    public function handle_get_video_reaction_counts() {
        $video_id = absint($_GET['video_id'] ?? 0);
        
        if (!$video_id) {
            wp_send_json_error(__('Invalid video ID.', 'videohub360'));
            return;
        }
        
        $counts = VideoHub360_Video_Reactions::get_counts($video_id);
        $user_reaction = null;
        
        if (is_user_logged_in()) {
            $user_reaction = VideoHub360_Video_Reactions::get_user_reaction($video_id, get_current_user_id());
        }
        
        wp_send_json_success(array(
            'counts' => $counts,
            'userReaction' => $user_reaction
        ));
    }
    
    /**
     * Handle create playlist AJAX request
     */
    public function handle_create_playlist() {
        // Rate limiting
        if (!$this->check_rate_limit('create_playlist', 10)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to create playlists.', 'videohub360'));
            return;
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $privacy = sanitize_text_field($_POST['privacy'] ?? 'private');
        $user_id = get_current_user_id();
        
        if (empty($title)) {
            wp_send_json_error(__('Playlist title is required.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Playlists::create_playlist($user_id, $title, $description, $privacy);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle add to playlist AJAX request
     */
    public function handle_add_to_playlist() {
        // Rate limiting
        if (!$this->check_rate_limit('add_to_playlist', 30)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $playlist_id = absint($_POST['playlist_id'] ?? 0);
        $video_id = absint($_POST['video_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$playlist_id || !$video_id) {
            wp_send_json_error(__('Invalid playlist or video ID.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Playlists::add_video($playlist_id, $video_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle remove from playlist AJAX request
     */
    public function handle_remove_from_playlist() {
        // Rate limiting
        if (!$this->check_rate_limit('remove_from_playlist', 30)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $playlist_id = absint($_POST['playlist_id'] ?? 0);
        $video_id = absint($_POST['video_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$playlist_id || !$video_id) {
            wp_send_json_error(__('Invalid playlist or video ID.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Playlists::remove_video($playlist_id, $video_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle get my playlists AJAX request
     */
    public function handle_get_my_playlists() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $user_id = get_current_user_id();
        $playlists = VideoHub360_Playlists::get_user_playlists($user_id);
        
        wp_send_json_success(array(
            'playlists' => $playlists
        ));
    }
    
    /**
     * Handle get playlist contents AJAX request
     */
    public function handle_get_playlist_contents() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $playlist_id = absint($_POST['playlist_id'] ?? 0);
        $page = absint($_POST['page'] ?? 1);
        $per_page = absint($_POST['per_page'] ?? 20);
        $user_id = get_current_user_id();
        
        if (!$playlist_id) {
            wp_send_json_error(__('Invalid playlist ID.', 'videohub360'));
            return;
        }
        
        // Verify ownership
        $playlist = VideoHub360_Playlists::get_playlist($playlist_id, $user_id);
        if (!$playlist) {
            wp_send_json_error(__('Playlist not found or access denied.', 'videohub360'));
            return;
        }
        
        $video_ids = VideoHub360_Playlists::get_playlist_videos($playlist_id, $page, $per_page);
        
        wp_send_json_success(array(
            'playlist' => $playlist,
            'video_ids' => $video_ids
        ));
    }
    
    /**
     * Handle delete playlist AJAX request
     */
    public function handle_delete_playlist() {
        // Rate limiting
        if (!$this->check_rate_limit('delete_playlist', 10)) {
            wp_send_json_error(__('Rate limit exceeded. Please try again later.', 'videohub360'));
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $playlist_id = absint($_POST['playlist_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$playlist_id) {
            wp_send_json_error(__('Invalid playlist ID.', 'videohub360'));
            return;
        }
        
        $result = VideoHub360_Playlists::delete_playlist($playlist_id, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Handle get playlists with video AJAX request
     */
    public function handle_get_playlists_with_video() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_playlist')) {
            wp_send_json_error(__('Security check failed.', 'videohub360'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'videohub360'));
            return;
        }
        
        $video_id = absint($_POST['video_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$video_id) {
            wp_send_json_error(__('Invalid video ID.', 'videohub360'));
            return;
        }
        
        $playlist_ids = VideoHub360_Playlists::get_playlists_with_video($video_id, $user_id);
        
        wp_send_json_success(array(
            'playlist_ids' => $playlist_ids
        ));
    }
}
