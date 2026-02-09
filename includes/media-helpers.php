<?php
/**
 * Media Helpers
 *
 * Centralized media handling functions for the Videohub360 Theme.
 * Provides YouTube URL detection and video ID extraction.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Detect if a URL is a YouTube link and return the video ID.
 *
 * Supports multiple YouTube URL formats:
 * - youtube.com/watch?v=VIDEO_ID
 * - youtu.be/VIDEO_ID
 * - youtube.com/shorts/VIDEO_ID
 *
 * @param string $url The URL to check.
 * @return string|false Video ID if valid YouTube URL, false otherwise.
 */
function vh360_get_youtube_video_id_from_url($url) {
    if (empty($url)) {
        return false;
    }
    
    $parsed = wp_parse_url($url);
    if (empty($parsed['host'])) {
        return false;
    }
    
    $host = strtolower($parsed['host']);
    
    // Remove 'www.' prefix if present
    $host = preg_replace('/^www\./', '', $host);
    
    // Handle youtube.com/watch?v=VIDEO_ID
    if (in_array($host, array('youtube.com', 'm.youtube.com'), true)) {
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query_vars);
            if (!empty($query_vars['v'])) {
                // Validate video ID (alphanumeric, dash, underscore, 10-12 chars typically)
                $video_id = $query_vars['v'];
                if (preg_match('/^[a-zA-Z0-9_-]{10,12}$/', $video_id)) {
                    return $video_id;
                }
            }
        }
        
        // Handle youtube.com/shorts/VIDEO_ID
        if (!empty($parsed['path']) && preg_match('#^/shorts/([a-zA-Z0-9_-]{10,12})#', $parsed['path'], $matches)) {
            return $matches[1];
        }
    }
    
    // Handle youtu.be/VIDEO_ID
    if ($host === 'youtu.be' && !empty($parsed['path'])) {
        $path = trim($parsed['path'], '/');
        if (preg_match('/^[a-zA-Z0-9_-]{10,12}$/', $path)) {
            return $path;
        }
    }
    
    return false;
}

/**
 * Get YouTube embed URL for a video ID.
 *
 * Uses youtube-nocookie.com for privacy-friendly embeds.
 *
 * @param string $video_id YouTube video ID.
 * @param array  $params   Additional embed parameters.
 * @return string Embed URL.
 */
function vh360_get_youtube_embed_url($video_id, $params = array()) {
    $default_params = array(
        'autoplay' => 1,
        'rel'      => 0,
    );
    
    $params = wp_parse_args($params, $default_params);
    $query_string = http_build_query($params);
    
    return sprintf(
        'https://www.youtube-nocookie.com/embed/%s?%s',
        esc_attr($video_id),
        $query_string
    );
}

/**
 * Get YouTube thumbnail URL for a video ID.
 *
 * @param string $video_id YouTube video ID.
 * @param string $quality  Thumbnail quality (default, hqdefault, mqdefault, sddefault, maxresdefault).
 * @return string Thumbnail URL.
 */
function vh360_get_youtube_thumbnail_url($video_id, $quality = 'hqdefault') {
    $allowed_qualities = array('default', 'hqdefault', 'mqdefault', 'sddefault', 'maxresdefault');
    if (!in_array($quality, $allowed_qualities, true)) {
        $quality = 'hqdefault';
    }
    
    return sprintf(
        'https://img.youtube.com/vi/%s/%s.jpg',
        esc_attr($video_id),
        esc_attr($quality)
    );
}
