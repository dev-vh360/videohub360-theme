<?php
/**
 * AJAX handlers for VideoHub360 Community Plugin
 *
 * Handles AJAX requests for comment likes and share tracking.
 *
 * @package VH360_Community
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AJAX handler for toggling comment likes
 */
function vh360_handle_comment_like_ajax() {
    // Verify nonce - using same nonce as other activity feed actions for consistency
    check_ajax_referer('vh360_activity_nonce', 'nonce', true);
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to like comments.', 'vh360-community')));
    }
    
    // Get comment ID from request
    $comment_id = isset($_POST['comment_id']) ? absint($_POST['comment_id']) : 0;
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => __('Invalid comment ID.', 'vh360-community')));
    }
    
    // Verify comment exists
    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(array('message' => __('Comment not found.', 'vh360-community')));
    }
    
    // Toggle like
    $user_id = get_current_user_id();
    $result = VH360_Comment_Likes::toggle($comment_id, $user_id);
    
    wp_send_json_success($result);
}

/**
 * AJAX handler for incrementing share count
 */
function vh360_handle_share_increment_ajax() {
    // Verify nonce (dies on failure)
    check_ajax_referer('vh360_share', 'nonce', true);
    
    // Get post ID from request
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => __('Invalid post ID.', 'vh360-community')));
    }
    
    // Verify post exists
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(array('message' => __('Post not found.', 'vh360-community')));
    }
    
    // Increment share count
    $new_count = VH360_Post_Shares::increment($post_id);
    
    wp_send_json_success(array('count' => $new_count));
}
