<?php
/**
 * Activity Tracker System
 *
 * Lightweight activity tracking system for community features.
 * Stores activities in wp_options table as JSON for simplicity.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Track a new activity
 *
 * @param int    $user_id The user ID performing the activity.
 * @param string $type    Activity type (video_upload, new_member, profile_update, milestone).
 * @param array  $content Activity content data.
 * @return bool True on success, false on failure.
 */
function vh360_track_activity($user_id, $type, $content = array()) {
    if (!$user_id || !$type) {
        return false;
    }
    
    // Get activity options
    $activity_options = get_option('vh360_activity_options', array());
    $activity_defaults = array(
        'enable_tracking' => true,
        'track_types' => array('video_upload', 'new_member', 'profile_update', 'milestone'),
    );
    $activity_options = wp_parse_args($activity_options, $activity_defaults);
    
    // Check if tracking is enabled
    if (!$activity_options['enable_tracking']) {
        return false;
    }
    
    // Check if this activity type should be tracked
    if (!in_array($type, $activity_options['track_types'])) {
        return false;
    }
    
    // Validate activity type
    $valid_types = array('video_upload', 'new_member', 'profile_update', 'milestone');
    if (!in_array($type, $valid_types)) {
        return false;
    }
    
    // Get existing activities
    $activities = get_option('vh360_activity_feed', array());
    if (!is_array($activities)) {
        $activities = array();
    }
    
    // Create new activity
    $activity = array(
        'id' => uniqid('activity_', true),
        'user_id' => absint($user_id),
        'type' => sanitize_text_field($type),
        'content' => array(
            'title' => isset($content['title']) ? sanitize_text_field($content['title']) : '',
            'link' => isset($content['link']) ? esc_url_raw($content['link']) : '',
            'meta' => isset($content['meta']) ? sanitize_text_field($content['meta']) : '',
        ),
        'timestamp' => current_time('timestamp'),
    );
    
    // Add to beginning of array (newest first)
    array_unshift($activities, $activity);
    
    // Keep only the most recent 100 activities
    $activities = array_slice($activities, 0, 100);
    
    // Update option
    $result = update_option('vh360_activity_feed', $activities);
    
    // Clear activity cache
    delete_transient('vh360_activities_cache');
    
    return $result;
}

/**
 * Get activities with optional filtering
 *
 * @param array $args Query arguments.
 * @return array Array of activities.
 */
function vh360_get_activities($args = array()) {
    // Get activity options for default limit
    $activity_options = get_option('vh360_activity_options', array());
    $default_per_page = isset($activity_options['per_page']) ? absint($activity_options['per_page']) : 20;
    
    $defaults = array(
        'type' => 'all',              // all, video_upload, new_member, profile_update, milestone
        'user_id' => 0,               // Filter by specific user
        'limit' => $default_per_page, // Number of activities to return
        'offset' => 0,                // Offset for pagination
        'use_cache' => true,          // Use transient cache
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Try to get from cache
    if ($args['use_cache']) {
        $cache_key = 'vh360_activities_' . md5(serialize($args));
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }
    }
    
    // Get all activities
    $activities = get_option('vh360_activity_feed', array());
    if (!is_array($activities)) {
        $activities = array();
    }
    
    // Filter by type
    if ($args['type'] !== 'all') {
        $activities = array_filter($activities, function($activity) use ($args) {
            return isset($activity['type']) && $activity['type'] === $args['type'];
        });
        // Re-index array
        $activities = array_values($activities);
    }
    
    // Filter by user ID
    if ($args['user_id'] > 0) {
        $activities = array_filter($activities, function($activity) use ($args) {
            return isset($activity['user_id']) && $activity['user_id'] === $args['user_id'];
        });
        // Re-index array
        $activities = array_values($activities);
    }
    
    // Apply offset and limit
    $activities = array_slice($activities, $args['offset'], $args['limit']);
    
    // Cache the results for 5 minutes
    if ($args['use_cache']) {
        set_transient($cache_key, $activities, 5 * MINUTE_IN_SECONDS);
    }
    
    return $activities;
}

/**
 * Delete old activities
 *
 * @param int $days Number of days to keep (default 90).
 * @return int Number of activities deleted.
 */
function vh360_delete_old_activities($days = 90) {
    if ($days < 1) {
        return 0;
    }
    
    $activities = get_option('vh360_activity_feed', array());
    if (!is_array($activities) || empty($activities)) {
        return 0;
    }
    
    $cutoff_time = current_time('timestamp') - ($days * DAY_IN_SECONDS);
    $original_count = count($activities);
    
    // Filter out old activities
    $activities = array_filter($activities, function($activity) use ($cutoff_time) {
        return isset($activity['timestamp']) && $activity['timestamp'] >= $cutoff_time;
    });
    
    // Re-index array
    $activities = array_values($activities);
    
    // Update option
    update_option('vh360_activity_feed', $activities);
    
    // Clear cache
    delete_transient('vh360_activities_cache');
    
    return $original_count - count($activities);
}

/**
 * Get activity count
 *
 * @param string $type Activity type or 'all'.
 * @return int Activity count.
 */
function vh360_get_activity_count($type = 'all') {
    $activities = get_option('vh360_activity_feed', array());
    if (!is_array($activities)) {
        return 0;
    }
    
    if ($type === 'all') {
        return count($activities);
    }
    
    // Count specific type
    $count = 0;
    foreach ($activities as $activity) {
        if (isset($activity['type']) && $activity['type'] === $type) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Track video upload activity
 *
 * @param int $post_id The post ID.
 * @return void
 */
function vh360_track_video_upload($post_id) {
    // Get post object
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, array('videohub360', 'post'))) {
        return;
    }
    
    // Only track publish transitions
    if ($post->post_status !== 'publish') {
        return;
    }
    
    vh360_track_activity($post->post_author, 'video_upload', array(
        'title' => $post->post_title,
        'link' => get_permalink($post_id),
        'meta' => '',
    ));
}
add_action('publish_post', 'vh360_track_video_upload');
add_action('publish_videohub360', 'vh360_track_video_upload');

/**
 * Track new member registration
 *
 * @param int $user_id The user ID.
 * @return void
 */
function vh360_track_new_member($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    
    vh360_track_activity($user_id, 'new_member', array(
        'title' => sprintf(__('%s joined the community', 'videohub360-theme'), $user->display_name),
        'link' => vh360_get_profile_url($user_id),
        'meta' => '',
    ));
}
add_action('user_register', 'vh360_track_new_member');

/**
 * Track profile update
 *
 * @param int $user_id The user ID.
 * @return void
 */
function vh360_track_profile_update($user_id) {
    // Check if this is a significant update (not just login timestamp, etc.)
    // We'll use a transient to prevent duplicate tracking
    $transient_key = 'vh360_profile_update_' . $user_id;
    if (get_transient($transient_key)) {
        return; // Already tracked recently
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    
    vh360_track_activity($user_id, 'profile_update', array(
        'title' => sprintf(__('%s updated their profile', 'videohub360-theme'), $user->display_name),
        'link' => vh360_get_profile_url($user_id),
        'meta' => '',
    ));
    
    // Set transient to prevent duplicate tracking for 1 hour
    set_transient($transient_key, true, HOUR_IN_SECONDS);
}
add_action('profile_update', 'vh360_track_profile_update', 10, 1);

/**
 * Track video milestone
 *
 * @param int    $video_id   The video post ID.
 * @param int    $user_id    The user ID.
 * @param string $milestone  The milestone (e.g., '1000_views').
 * @return void
 */
function vh360_track_video_milestone($video_id, $user_id, $milestone) {
    $post = get_post($video_id);
    if (!$post) {
        return;
    }
    
    // Format milestone text
    $milestone_text = '';
    switch ($milestone) {
        case '1000_views':
            $milestone_text = '1K views';
            break;
        case '10000_views':
            $milestone_text = '10K views';
            break;
        case '100000_views':
            $milestone_text = '100K views';
            break;
        case '1000000_views':
            $milestone_text = '1M views';
            break;
        default:
            $milestone_text = $milestone;
    }
    
    vh360_track_activity($user_id, 'milestone', array(
        'title' => $post->post_title,
        'link' => get_permalink($video_id),
        'meta' => sprintf(__('reached %s', 'videohub360-theme'), $milestone_text),
    ));
}
add_action('vh360_video_milestone', 'vh360_track_video_milestone', 10, 3);

/**
 * Cleanup old activities (cron job)
 * Runs daily to delete activities older than 90 days
 */
function vh360_cleanup_old_activities() {
    vh360_delete_old_activities(90);
}
add_action('wp_scheduled_delete', 'vh360_cleanup_old_activities');
