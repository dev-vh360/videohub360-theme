<?php
/**
 * Chat Container Renderer
 *
 * Renders the chat UI container for livestream pages.
 * Extracted from templates/single-videohub360.php as part of Phase 1 refactoring
 * to eliminate code duplication between theme and core plugin.
 *
 * @package VideoHub360
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('videohub360_render_chat_container')) {
    function videohub360_render_chat_container($chat_placement, $is_user_logged_in, $user_avatar, $user_display_name, $user_logout_url, $can_moderate, $livestream_fields) {
        $chat_html = '';
        
        if ($chat_placement === 'popup') {
            // Popup chat - rendered as modal overlay
            $chat_html .= '<div id="vh360-chat-popup" class="vh360-chat-popup is-hidden" aria-hidden="true">';
            $chat_html .= '<div class="vh360-chat-popup-content">';
        } else {
            // Inline chat - rendered in content area
            $chat_html .= '<div id="vh360-chat-inline" class="vh360-chat-inline">';
        }
        
        $chat_html .= '<div class="videohub360-live-chat">';
        $chat_html .= '<div class="videohub360-chat-header">';
        $chat_html .= '<h2>' . (($livestream_fields['is_live'] === 'yes') ? 'Live Chat' : 'Chat') . '</h2>';
        $chat_html .= '<div class="videohub360-chat-header-buttons">';
        if ($can_moderate) {
            $chat_html .= '<button type="button" class="videohub360-moderation-panel-btn" id="vh360-moderation-panel-btn" title="Moderation Panel">';
            $chat_html .= '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">';
            $chat_html .= '<path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>';
            $chat_html .= '</svg>';
            $chat_html .= '</button>';
        }
        if ($chat_placement === 'popup') {
            $chat_html .= '<button type="button" class="videohub360-chat-close-btn" id="vh360-chat-close-btn" title="Close Chat" aria-label="Close Chat">&times;</button>';
        }
        $chat_html .= '</div>';
        $chat_html .= '</div>';
        $chat_html .= '<div class="videohub360-chat-messages" id="vh360-chat-messages"></div>';
        $chat_html .= '<div class="videohub360-chat-input-container">';
        if (!$is_user_logged_in) {
            $chat_html .= '<div class="videohub360-chat-login-prompt">';
            $chat_html .= '<p>Please log in to participate in the chat</p>';
            $chat_html .= '<button type="button" class="videohub360-chat-login-btn videohub360-chat-login-trigger">Log In to Chat</button>';
            $chat_html .= '</div>';
        }
        $chat_html .= '<form class="videohub360-chat-form" id="vh360-chat-form" action="javascript:void(0);" onsubmit="return false;">';
        $chat_html .= '<input type="text" class="videohub360-chat-input" id="vh360-chat-input" placeholder="' . ($is_user_logged_in ? 'Type a message...' : 'Please log in to chat') . '" maxlength="' . intval(get_option('videohub360_chat_message_limit', 500)) . '" ' . (!$is_user_logged_in ? 'disabled' : '') . '>';
        $chat_html .= '<button type="button" class="videohub360-chat-send-btn" id="vh360-chat-send-btn" title="Send message" aria-label="Send message" ' . (!$is_user_logged_in ? 'disabled' : '') . '>➤</button>';
        $chat_html .= '<button type="button" class="videohub360-emoji-btn" id="vh360-chat-emoji-btn" title="Add emoji" aria-label="Add emoji" ' . (!$is_user_logged_in ? 'disabled' : '') . '>😊</button>';
        $chat_html .= '</form>';
        $chat_html .= '<div class="videohub360-chat-user-info ' . ($is_user_logged_in ? 'logged-in' : '') . '" id="vh360-chat-user-info">';
        if ($is_user_logged_in) {
            $chat_html .= $user_avatar;
            $chat_html .= '<span class="videohub360-current-user-name">' . esc_html($user_display_name) . '</span>';
            $chat_html .= '<button type="button" class="videohub360-chat-logout-btn" data-logout-url="' . esc_url($user_logout_url) . '">Logout</button>';
        } else {
            $chat_html .= '<span>👤 Not logged in</span>';
        }
        $chat_html .= '</div>';
        $chat_html .= '</div>';
        $chat_html .= '</div>';
        
        if ($chat_placement === 'popup') {
            $chat_html .= '</div>';
        }
        $chat_html .= '</div>';
        
        return $chat_html;
    }
}
