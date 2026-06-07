<?php
/**
 * Livestream Player Renderer
 *
 * Renders the livestream player for various streaming types (embed, self-hosted, API, Agora).
 * Extracted from templates/single-videohub360.php as part of Phase 1 refactoring
 * to eliminate code duplication between theme and core plugin.
 *
 * @package VideoHub360
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('videohub360_render_livestream')) {
    function videohub360_render_livestream($fields, $chat_enabled = false, $chat_placement = 'inline', $is_user_logged_in = false, $user_avatar = '', $user_display_name = '', $user_logout_url = '', $hide_settings = false) {
        $player_html = '<div class="vh360-livestream-player-wrap">';
        
        // Add interactive class for Agora Interactive Mode
        $iframe_class = 'vh360-livestream-iframe';
        if ($fields['type'] === 'agora' && $fields['agora_mode'] === 'interactive') {
            $iframe_class .= ' vh360-agora-interactive-container';
        }
        
        $player_html .= '<div class="' . $iframe_class . '">';
        if ($fields['type'] === 'embed' && !empty($fields['embed_code'])) {
            // Sanitize custom embed code to avoid XSS. Allow only safe iframe attributes.
            $embed_code_raw = $fields['embed_code'];
            if (strpos($embed_code_raw, 'facebook.com') !== false) {
                // Facebook embed code can include additional markup; allow it but sanitize with wp_kses_post.
                $player_html .= wp_kses_post($embed_code_raw);
            } else {
                // Remove explicit width and height attributes from the iframe
                // This allows the CSS to control the responsive sizing
                $embed_code = preg_replace(
                    '/\s+(width|height)\s*=\s*["\']?[^"\'\s>]+["\']?/i',
                    '',
                    $embed_code_raw
                );
                // Ensure width and height attributes are set to 100%
                if (stripos($embed_code, 'width=') === false) {
                    $embed_code = preg_replace('/<iframe/i', '<iframe width="100%"', $embed_code);
                }
                if (stripos($embed_code, 'height=') === false) {
                    $embed_code = preg_replace('/<iframe/i', '<iframe height="100%"', $embed_code);
                }
                // Sanitize embed code using wp_kses with iframe support
                // Define allowed iframe attributes for video embeds (YouTube, Vimeo, Twitch, etc.)
                $allowed_iframe_html = array(
                    'iframe' => array(
                        'src' => true,
                        'width' => true,
                        'height' => true,
                        'frameborder' => true,
                        'allowfullscreen' => true,
                        'allow' => true,
                        'title' => true,
                        'referrerpolicy' => true,
                        'loading' => true,
                        'style' => true,
                        'class' => true,
                        'id' => true,
                        'name' => true,
                        'scrolling' => true,
                        'sandbox' => true,
                        'parent' => true,  // Required for Twitch embeds
                    ),
                );
                $player_html .= wp_kses($embed_code, $allowed_iframe_html);
            }
        } elseif ($fields['type'] === 'selfhosted' && !empty($fields['stream_url'])) {
            $player_html .= '<video id="vh360-livestream-video" class="video-js vjs-default-skin" controls autoplay playsinline poster="' . esc_attr($fields['poster']) . '">';
            $player_html .= '<source src="' . esc_url($fields['stream_url']) . '" type="application/x-mpegURL">';
            $player_html .= '</video>';
            // Video.js is now enqueued via WordPress in VideoHub360_Frontend.  The
            // inline <script> and <link> tags have been removed to comply with
            // best practices.
        } elseif ($fields['type'] === 'api' && !empty($fields['api_url'])) {
            $player_html .= '<video id="vh360-livestream-video" class="video-js vjs-default-skin" controls autoplay playsinline poster="' . esc_attr($fields['poster']) . '">';
            $player_html .= '<source src="' . esc_url($fields['api_url']) . '" type="application/x-mpegURL">';
            $player_html .= '</video>';
            // Video.js is now enqueued via WordPress in VideoHub360_Frontend.  The
            // inline <script> and <link> tags have been removed to comply with
            // best practices.
        } elseif ($fields['type'] === 'agora' && !empty($fields['agora_channel_name'])) {
            // Get global App ID from settings
            $global_app_id = get_option('vh360_agora_app_id', '');

            if (empty($global_app_id)) {
                if (current_user_can('manage_options')) {
                    $player_html .= '<div class="vh360-error-message">';
                    $player_html .= '<h3 class="vh360-error-title">' . esc_html__('🔴 Livestream Not Available', 'videohub360') . '</h3>';
                    $player_html .= '<p class="vh360-error-text">' . esc_html__('Agora App ID is not configured.', 'videohub360') . '</p>';
                    $player_html .= '<p class="vh360-error-hint">' . esc_html__('Please configure the global Agora App ID in VideoHub360 Settings.', 'videohub360') . '</p>';
                    $player_html .= '</div>';
                } elseif (!empty($fields['offline_message'])) {
                    $player_html .= '<div class="vh360-offline-message">' . wp_kses_post($fields['offline_message']) . '</div>';
                } else {
                    $player_html .= '<div class="vh360-offline-message">';
                    $player_html .= '<p>' . esc_html__('This livestream is currently unavailable.', 'videohub360') . '</p>';
                    $player_html .= '</div>';
                }
            } else {

            // Get the post to check authorship
            $post = get_post(get_the_ID());
            $is_owner = false;
            if ($post && is_user_logged_in()) {
                $is_owner = ((int) $post->post_author === (int) get_current_user_id());
            }

            // Enhanced host detection - check if user can manage this live room
            // User can manage if: admin OR can edit_post OR is post_author
            $is_original_host = current_user_can('manage_options') 
                || current_user_can('edit_post', get_the_ID()) 
                || $is_owner;
            $can_moderate = $is_original_host || current_user_can('moderate_comments') || current_user_can('manage_options');
            $is_logged_in = is_user_logged_in();

            // Debug info — intentionally omits sensitive data (user roles, capabilities).
            $current_user = wp_get_current_user();
            $debug_info = array(
                'agora_mode' => $fields['agora_mode'],
                'everyone_is_host' => $fields['agora_everyone_is_host']
            );

            // Determine role based on capabilities and mode
            $role = 'audience';
            if ($fields['agora_mode'] === 'broadcast') {
                // In broadcast mode, admins and original host get host role
                $role = ($is_original_host || current_user_can('manage_options')) ? 'host' : 'audience';
            } else {
                // Interactive mode - check if everyone should be host
                if ($fields['agora_everyone_is_host'] === 'yes') {
                    $role = 'host'; // All users join as host (Zoom-style)
                } else {
                    $role = $is_original_host ? 'host' : 'audience'; // Enhanced host detection
                }
            }

            // Add final role to debug info
            $debug_info['final_role'] = $role;
            $debug_info['is_original_host'] = $is_original_host;

            $agora_mode = $fields['agora_mode'] === 'broadcast' ? 'live' : 'rtc';
            $agora_class = ($fields['agora_mode'] === 'interactive') ? ' vh360-agora-interactive' : ' vh360-agora-broadcast';
            $player_html .= '<div id="vh360-agora-player" class="vh360-agora-player' . $agora_class . '">';
            // Mode indicator removed as requested - no longer shows "Interactive Mode" or "Broadcast Mode" labels

            // Button overlay: Host sees Start Live Stream, audience sees waiting message
            // For appointment rooms, use appointment-specific messaging
            $post_id = get_the_ID();
            $appointment_event_id = get_post_meta($post_id, '_vh360_appointment_event_id', true);
            $is_appointment = !empty($appointment_event_id);
            
            $player_html .= '<div id="vh360-join-livestream-overlay" class="vh360-join-livestream-overlay">';
$player_html .= '<div class="vh360-overlay-content">';
$player_html .= '<div class="vh360-overlay-icon">🔴</div>';

if ($is_appointment && function_exists('vh360_get_appointment_session_state')) {
    // Appointment room - use timing-aware messaging
    $current_user_id = get_current_user_id();
    $session_state = vh360_get_appointment_session_state($post_id, $current_user_id);
    
    if ($is_original_host) {
        // Professional view
        if ($session_state['status'] === 'ended') {
            $player_html .= '<h3 class="vh360-overlay-title">Session Ended</h3>';
            $player_html .= '<p class="vh360-overlay-description">This appointment session has been completed.</p>';
        } else {
            $appointment_data = $session_state['appointment_data'];
            if (!empty($appointment_data['start_datetime'])) {
                $start_datetime = $appointment_data['start_datetime'];
                $player_html .= '<h3 class="vh360-overlay-title">Appointment Session</h3>';
                $player_html .= '<p class="vh360-overlay-description">Scheduled for ' . $start_datetime->format(get_option('time_format')) . ', ' . $start_datetime->format(get_option('date_format')) . '</p>';
            } else {
                $player_html .= '<h3 class="vh360-overlay-title">Appointment Session</h3>';
                $player_html .= '<p class="vh360-overlay-description">Start the session when ready.</p>';
            }
            $player_html .= '<button id="vh360-join-livestream-btn" class="vh360-overlay-btn">Start Session</button>';
            $player_html .= '<div class="vh360-overlay-hint">You can enter early to prepare</div>';
        }
    } else {
        // Client view
        if ($session_state['status'] === 'too_early') {
            $player_html .= '<h3 class="vh360-overlay-title">Session Not Open Yet</h3>';
            $player_html .= '<p class="vh360-overlay-description">' . esc_html($session_state['message']) . '</p>';
        } elseif ($session_state['status'] === 'waiting_for_host') {
            $player_html .= '<h3 class="vh360-overlay-title">Waiting for Professional</h3>';
            $player_html .= '<p class="vh360-overlay-description">The professional will start the session shortly.</p>';
        } elseif ($session_state['status'] === 'ended') {
            $player_html .= '<h3 class="vh360-overlay-title">Session Ended</h3>';
            $player_html .= '<p class="vh360-overlay-description">This appointment session has been completed.</p>';
        } elseif ($session_state['status'] === 'active' && $session_state['can_generate_token']) {
            $player_html .= '<h3 class="vh360-overlay-title">Join Session</h3>';
            $player_html .= '<p class="vh360-overlay-description">The professional is ready for you.</p>';
            $player_html .= '<button id="vh360-join-livestream-btn" class="vh360-overlay-btn">Join Session</button>';
        } else {
            $player_html .= '<div class="vh360-overlay-waiting">Waiting for the professional to start the session</div>';
        }
    }
} elseif ($is_original_host) {
    // Regular live room - original behavior for host
    $player_html .= '<h3 class="vh360-overlay-title">Live Stream Control</h3>';
    $player_html .= '<p class="vh360-overlay-description">Start the live stream for your viewers.</p>';
    $player_html .= '<button id="vh360-join-livestream-btn" class="vh360-overlay-btn">Start Live Stream</button>';
    $player_html .= '<div class="vh360-overlay-hint">Click to start the live stream</div>';
} else {
    // Regular live room - check if everyone can join as host in interactive mode (Zoom-style)
    if ($fields['agora_everyone_is_host'] === 'yes' && $fields['agora_mode'] === 'interactive') {
        $player_html .= '<h3 class="vh360-overlay-title">Join Live Stream</h3>';
        $player_html .= '<p class="vh360-overlay-description">Click to join as host</p>';
        $player_html .= '<button id="vh360-join-livestream-btn" class="vh360-overlay-btn">Join Livestream</button>';
        $player_html .= '<div class="vh360-overlay-hint">You can join immediately as a host</div>';
    } else {
        $player_html .= '<div class="vh360-overlay-waiting">Waiting for the host to start the live stream</div>';
    }
}
$player_html .= '</div>';
$player_html .= '</div>';

            $player_html .= '<div id="vh360-agora-local-player"></div>';
            $player_html .= '<div id="vh360-agora-remote-players" class="vh360-remote-players-initial"></div>';

            $player_html .= '<div id="vh360-agora-controls" class="vh360-mobile-controls-simple">';
            
            // All buttons in single container for simplified mobile controls
            $player_html .= '<button id="vh360-agora-mute-audio" class="vh360-agora-control-btn vh360-agora-control-btn-text vh360-hidden">🎤 Mute</button>';
            $player_html .= '<button id="vh360-agora-mute-video" class="vh360-agora-control-btn vh360-agora-control-btn-text vh360-hidden">📹 Camera</button>';
            
            // Show join presenter button for non-moderators in interactive mode when either:
            // 1. Host passcode is set, OR 
            // 2. "Allow Everyone to be Host" is enabled
            if (!$can_moderate && $fields['agora_mode'] === 'interactive' && 
                (!empty($fields['host_passcode']) || $fields['agora_everyone_is_host'] === 'yes')) {
                $player_html .= '<button id="vh360-agora-join-presenter" class="vh360-agora-control-btn vh360-agora-control-btn-text vh360-hidden">🎭 Go Live</button>';
            }
            
            // Spacer to push right-aligned buttons to the right
            $player_html .= '<div class="vh360-controls-spacer"></div>';
            
            // REORDERED RIGHT-SIDE BUTTONS:
            // 3rd to last: Leave button (with door icon)
            $player_html .= '<button id="vh360-agora-leave" class="vh360-agora-control-btn vh360-agora-control-btn-icon vh360-agora-btn-leave" title="Leave">';
            $player_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
            // Clear door icon - represents leaving/exit
            $player_html .= '<path d="M14,6V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14A2,2 0 0,1 16,4V6H14M20.5,11L18.5,9V10H10V14H18.5V15L20.5,13Z"/>';
            $player_html .= '</svg>';
            $player_html .= '</button>';
            

            // End stream button (if applicable, between leave and fullscreen)
            if ($is_original_host) {
                $player_html .= '<button id="vh360-agora-end-stream" class="vh360-agora-control-btn vh360-agora-control-btn-text vh360-agora-btn-end">End Stream</button>';
            }
            
            // 2nd to last: Fullscreen button (with fullscreen icon, no text)
            $player_html .= '<button id="vh360-agora-fullscreen-btn" class="vh360-agora-control-btn vh360-agora-control-btn-icon" title="Toggle fullscreen">';
            $player_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
            $player_html .= '<path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>';
            $player_html .= '</svg>';
            $player_html .= '</button>';
            
            // Last: Unified Settings/Gear button (replaces moderation panel button)
            // Skip settings button if hide_settings is true (e.g., for Live Room context)
            if (!$hide_settings) {
                if ($can_moderate) {
                    $player_html .= '<button type="button" id="vh360-agora-settings-btn" class="vh360-agora-control-btn vh360-agora-control-btn-icon" title="Settings">';
                    $player_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
                    $player_html .= '<path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>';
                    $player_html .= '</svg>';
                    $player_html .= '</button>';
                } else {
                    // For non-moderators, still show settings but only for quality/mirror if video quality manager is available
                    $player_html .= '<button type="button" id="vh360-agora-settings-btn" class="vh360-agora-control-btn vh360-agora-control-btn-icon" title="Video Settings">';
                    $player_html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
                    $player_html .= '<path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>';
                    $player_html .= '</svg>';
                    $player_html .= '</button>';
                }
            }
            
            $player_html .= '</div>';
            
            $player_html .= '</div>';
            }
        } else {
            if ($fields['type'] === 'agora' && empty($fields['agora_channel_name'])) {
                if (!empty($fields['offline_message'])) {
                    $player_html .= '<div class="vh360-offline-message">' . wp_kses_post($fields['offline_message']) . '</div>';
                } else {
                    $player_html .= '<div class="vh360-error-message">';
                    $player_html .= '<h3 class="vh360-error-title">🔴 Livestream Not Available</h3>';
                    $player_html .= '<p class="vh360-error-text">This Agora livestream is not properly configured.</p>';
                    $player_html .= '<p class="vh360-error-hint">Missing: Channel Name</p>';
                    $player_html .= '</div>';
                }
            } else {
                $player_html .= '<div class="vh360-error-message">No livestream configured for this video.</div>';
            }
        }
        $player_html .= '</div>';
        $player_html .= '</div>';
        return $player_html;
    }
}
