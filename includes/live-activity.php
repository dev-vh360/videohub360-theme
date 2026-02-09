<?php
/**
 * Live Activity Handlers
 *
 * Handles the creation and management of community posts when users
 * start/end Live Rooms. Creates posts in the Activity feed and updates
 * them when the stream ends.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Create a community post when a live room starts.
 *
 * This function is idempotent - it won't create duplicate posts if called
 * multiple times for the same live session.
 *
 * @param int $live_room_id The live room post ID.
 */
function vh360_create_went_live_post($live_room_id) {
    if (!$live_room_id) {
        return;
    }

    // Get the live room post author
    $author_id = get_post_field('post_author', $live_room_id);
    if (!$author_id) {
        return;
    }

    // Get the live room permalink and title
    $permalink = get_permalink($live_room_id);

    // Idempotency check: Check if we already created a community post for this live session
    $existing_post_id = get_post_meta($live_room_id, '_vh360_went_live_post_id', true);

    if ($existing_post_id && get_post_status($existing_post_id) === 'publish') {
        // Post already exists, just ensure it's marked as live
        update_post_meta($existing_post_id, 'vh360_live_status', 'live');
        return; // Don't create duplicate
    }

    // Create the community post content
    $post_content = apply_filters(
        'vh360_went_live_text',
        "I'm live now! Join me: $permalink",
        $live_room_id
    );

    // Create the community post
    $new_post_id = wp_insert_post([
        'post_type'    => 'vh360_post',
        'post_author'  => $author_id,
        'post_status'  => 'publish',
        'post_content' => $post_content,
        'post_title'   => '', // Community posts typically don't have titles
    ]);

    if ($new_post_id && !is_wp_error($new_post_id)) {
        // Create bidirectional mapping
        update_post_meta($live_room_id, '_vh360_went_live_post_id', $new_post_id);
        update_post_meta($new_post_id, 'vh360_live_room_id', $live_room_id);
        update_post_meta($new_post_id, 'vh360_live_status', 'live');
        update_post_meta($new_post_id, 'vh360_live_start_time', time());
    }
}
add_action('vh360_live_room_started', 'vh360_create_went_live_post', 10, 1);

/**
 * Update the community post when a live room ends.
 *
 * @param int $live_room_id The live room post ID.
 */
function vh360_mark_went_live_post_ended($live_room_id) {
    if (!$live_room_id) {
        return;
    }

    // Find the associated community post
    $post_id = get_post_meta($live_room_id, '_vh360_went_live_post_id', true);

    if (!$post_id || get_post_status($post_id) !== 'publish') {
        return; // No community post to update
    }

    // Get the permalink (keep it for the link preview)
    $permalink = get_permalink($live_room_id);

    // Update the content to "ended" state
    $ended_text = apply_filters(
        'vh360_live_ended_text',
        "Live ended. Watch the replay: $permalink",
        $live_room_id
    );

    wp_update_post([
        'ID'           => $post_id,
        'post_content' => $ended_text,
    ]);

    // Update status meta
    update_post_meta($post_id, 'vh360_live_status', 'ended');
    update_post_meta($post_id, 'vh360_live_end_time', time());
}
add_action('vh360_live_room_ended', 'vh360_mark_went_live_post_ended', 10, 1);
