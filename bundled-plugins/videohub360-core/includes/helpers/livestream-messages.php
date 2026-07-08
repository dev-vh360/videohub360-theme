<?php
/**
 * Livestream Messages Helper Functions
 * 
 * Helper functions for retrieving customizable offline/ended messages
 * for livestreams and live rooms with proper fallback hierarchy
 * 
 * @package VideoHub360
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Get the default "stream ended" HTML for single video pages
 * 
 * Priority:
 * 1. Admin-defined HTML option
 * 2. Build from icon + default title/text
 * 3. Hardcoded fallback
 * 
 * @return string HTML for stream ended message
 */
function vh360_get_default_stream_ended_html() {
    $html = get_option('vh360_default_stream_ended_html', '');
    
    if (!empty($html)) {
        return $html;
    }
    
    // Build from icon if HTML not provided
    $icon = get_option('vh360_default_stream_ended_icon', '📴');
    
    return sprintf(
        '<div class="vh360-stream-ended-content">
            <div class="vh360-stream-ended-icon">%s</div>
            <h3 class="vh360-stream-ended-title">Stream Ended</h3>
            <p class="vh360-stream-ended-text">This livestream has ended.</p>
        </div>',
        esc_html($icon)
    );
}

/**
 * Get the default "live room offline" HTML
 * 
 * Priority:
 * 1. Admin-defined HTML option
 * 2. Build from icon + default title/text
 * 3. Hardcoded fallback
 * 
 * @return string HTML for live room offline message
 */
function vh360_get_default_live_room_offline_html() {
    $html = get_option('vh360_default_live_room_offline_html', '');
    
    if (!empty($html)) {
        return $html;
    }
    
    // Build from icon if HTML not provided
    $icon = get_option('vh360_default_live_room_offline_icon', '🔴');
    
    return sprintf(
        '<div class="vh360-live-room-offline-content">
            <div class="vh360-stream-ended-icon">%s</div>
            <h3>This Live Room is not currently live.</h3>
        </div>',
        esc_html($icon)
    );
}

/**
 * Get the "ended by moderator" HTML for JS overlays
 * 
 * @return string HTML for moderator ended message
 */
function vh360_get_stream_ended_by_moderator_html() {
    $html = get_option('vh360_stream_ended_by_moderator_html', '');
    
    if (!empty($html)) {
        return $html;
    }
    
    // Hardcoded fallback (current behavior)
    return '<div class="vh360-stream-ended-content">
        <div class="vh360-stream-ended-icon">🛑</div>
        <h3 class="vh360-stream-ended-title">Stream Ended</h3>
        <p class="vh360-stream-ended-text">The livestream has been ended by the moderator.</p>
    </div>';
}

/**
 * Get the "needs restart" HTML for JS overlays
 * 
 * @return string HTML for needs restart message
 */
function vh360_get_stream_ended_needs_restart_html() {
    $html = get_option('vh360_stream_ended_needs_restart_html', '');
    
    if (!empty($html)) {
        return $html;
    }
    
    // Hardcoded fallback (current behavior)
    return '<div class="vh360-stream-ended-content">
        <div class="vh360-stream-ended-icon">📴</div>
        <h3 class="vh360-stream-ended-title">Stream Ended</h3>
        <p class="vh360-stream-ended-text">This stream has ended. The host needs to restart it to go live again.</p>
    </div>';
}


/**
 * Get the Studio replay processing HTML for ended Studio livestreams.
 *
 * @return string HTML for replay processing message
 */
function vh360_get_stream_replay_processing_html() {
    $html = get_option('vh360_stream_replay_processing_html', '');

    if (!empty($html)) {
        return $html;
    }

    return '<div class="vh360-stream-ended-content">
        <div class="vh360-stream-ended-icon">📴</div>
        <h3 class="vh360-stream-ended-title">Stream Ended</h3>
        <p class="vh360-stream-ended-text">Thanks for watching. The replay is being prepared and will appear here when it is ready.</p>
    </div>';
}

/**
 * Get offline message HTML (context-aware)
 * 
 * @param int|null $post_id Post ID (defaults to current post)
 * @param string $context 'stream_ended' or 'live_room_offline'
 * @return string HTML for the offline message
 */
function vh360_get_offline_message_html($post_id = null, $context = 'stream_ended') {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Priority 1: Per-post override
    $per_post = get_post_meta($post_id, '_vh360_offline_message', true);
    if (!empty($per_post)) {
        return $per_post;
    }
    
    // Priority 2: Global default (context-aware)
    if ($context === 'live_room_offline') {
        return vh360_get_default_live_room_offline_html();
    }
    
    return vh360_get_default_stream_ended_html();
}

/**
 * Echo offline message HTML (context-aware)
 * 
 * @param int|null $post_id Post ID (defaults to current post)
 * @param string $context 'stream_ended' or 'live_room_offline'
 */
function vh360_the_offline_message($post_id = null, $context = 'stream_ended') {
    echo wp_kses_post(vh360_get_offline_message_html($post_id, $context));
}
