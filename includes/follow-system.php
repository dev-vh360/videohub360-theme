<?php
/**
 * Follow system helpers for Videohub360.
 *
 * This file defines a simple follow/unfollow system built on top of
 * WordPress user meta. Each user has a meta key `vh360_following`
 * containing an array of user IDs they follow. When a user follows
 * another user, we also update a `vh360_followers_count` meta key
 * on the target user to keep track of follower counts for trending
 * authors. All follow modifications happen via AJAX for a smooth
 * experience.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get an array of user IDs the current user is following.
 *
 * @param int $user_id User ID to fetch following list for. Defaults to current user.
 * @return array List of user IDs.
 */
// Define the helper only if it doesn't already exist. This avoids
// fatal "Cannot redeclare" errors when the function is defined in
// multiple included files. This version returns an array of user IDs
// the specified user (or current user) is following based on the
// `vh360_following` user meta. All values are cast to integers.
if ( ! function_exists( 'vh360_get_following_user_ids' ) ) {
    function vh360_get_following_user_ids( $user_id = 0 ) {
        $user_id = $user_id ? $user_id : get_current_user_id();
        if ( ! $user_id ) {
            return [];
        }
        $following = get_user_meta( $user_id, 'vh360_following', true );
        if ( ! is_array( $following ) ) {
            $following = [];
        }
        // Cast to integers and remove empties.
        $following = array_filter( array_map( 'intval', $following ) );
        return $following;
    }
}

/**
 * Get an array of user IDs who follow a specific user.
 *
 * Since we store following relationships (each user has a list of who they follow),
 * this function queries all users' following lists to find who has this user_id in their list.
 *
 * @param int $user_id User ID to get followers for. Defaults to current user.
 * @return array List of user IDs.
 */
if ( ! function_exists( 'vh360_get_followers' ) ) {
    function vh360_get_followers( $user_id = 0 ) {
        $user_id = $user_id ? $user_id : get_current_user_id();
        if ( ! $user_id ) {
            return [];
        }
        
        global $wpdb;
        
        // Get all users who have the vh360_following meta key
        $results = $wpdb->get_results(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} 
            WHERE meta_key = 'vh360_following'",
            ARRAY_A
        );
        
        $followers = [];
        
        // Check each user's following list to see if they follow this user
        foreach ( $results as $row ) {
            $following_list = maybe_unserialize( $row['meta_value'] );
            
            if ( is_array( $following_list ) && in_array( (int) $user_id, array_map( 'intval', $following_list ), true ) ) {
                $followers[] = (int) $row['user_id'];
            }
        }
        
        return $followers;
    }
}

/**
 * Check if a user is following another user.
 *
 * @param int $target_user_id User ID to check.
 * @param int $user_id User doing the following. Defaults to current user.
 * @return bool
 */
function vh360_is_following($target_user_id, $user_id = 0) {
    $user_id = $user_id ? $user_id : get_current_user_id();
    if (!$user_id || $target_user_id == $user_id) {
        return false;
    }
    $following = vh360_get_following_user_ids($user_id);
    return in_array((int) $target_user_id, $following, true);
}

/**
 * Toggle follow status for a target user. If already following, unfollow;
 * otherwise follow. Updates both the following list on the current user
 * and the followers count on the target user.
 *
 * @param int $target_user_id Target user ID.
 * @return bool True on success, false otherwise.
 */
function vh360_toggle_follow($target_user_id) {
    $current_user_id = get_current_user_id();
    if (!$current_user_id || $current_user_id == $target_user_id) {
        return false;
    }
    $following = vh360_get_following_user_ids($current_user_id);
    $is_following = in_array((int) $target_user_id, $following, true);
    if ($is_following) {
        // Unfollow
        $following = array_diff($following, [(int) $target_user_id]);
        update_user_meta($current_user_id, 'vh360_following', array_values($following));
        // Decrease follower count on target
        $count = (int) get_user_meta($target_user_id, 'vh360_followers_count', true);
        $count = max($count - 1, 0);
        update_user_meta($target_user_id, 'vh360_followers_count', $count);
    } else {
        // Follow
        $following[] = (int) $target_user_id;
        $following = array_unique(array_values($following));
        update_user_meta($current_user_id, 'vh360_following', $following);
        // Increase follower count on target
        $count = (int) get_user_meta($target_user_id, 'vh360_followers_count', true);
        $count++;
        update_user_meta($target_user_id, 'vh360_followers_count', $count);
        
        // Trigger notification action
        do_action('vh360_user_followed', $current_user_id, $target_user_id);
    }
    return true;
}

/**
 * Render a follow/unfollow button for a given user.
 *
 * @param int $target_user_id User ID to render follow button for.
 * @param string $class Optional CSS classes for button.
 */
function vh360_follow_button($target_user_id, $class = '') {
    if (!is_user_logged_in()) {
        return;
    }
    $current_user_id = get_current_user_id();
    if ($current_user_id == $target_user_id) {
        return; // Do not show follow button on self.
    }
    $is_following = vh360_is_following($target_user_id, $current_user_id);
    $btn_text  = $is_following ? __('Unfollow', 'videohub360-theme') : __('Follow', 'videohub360-theme');
    $nonce     = wp_create_nonce('vh360_follow_user');
    $btn_class = $is_following ? 'vh360-unfollow-btn' : 'vh360-follow-btn';
    $btn_class = $class . ' ' . $btn_class;
    echo '<button class="' . esc_attr($btn_class) . '" data-target="' . esc_attr($target_user_id) . '" data-nonce="' . esc_attr($nonce) . '">' . esc_html($btn_text) . '</button>';
}

/**
 * AJAX handler to toggle follow.
 */
function vh360_ajax_toggle_follow() {
    check_ajax_referer('vh360_follow_user', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in.', 'videohub360-theme'));
    }
    $target_user_id = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;
    if (!$target_user_id) {
        wp_send_json_error(__('Invalid user.', 'videohub360-theme'));
    }
    $current_user_id = get_current_user_id();
    if ($current_user_id == $target_user_id) {
        wp_send_json_error(__('You cannot follow yourself.', 'videohub360-theme'));
    }
    $result = vh360_toggle_follow($target_user_id);
    $new_status = vh360_is_following($target_user_id, $current_user_id);
    if ($result) {
        wp_send_json_success(['following' => $new_status]);
    }
    wp_send_json_error(__('Could not update follow status.', 'videohub360-theme'));
}

// Register AJAX actions for logged in and non logged in.
add_action('wp_ajax_vh360_toggle_follow', 'vh360_ajax_toggle_follow');
add_action('wp_ajax_nopriv_vh360_toggle_follow', 'vh360_ajax_toggle_follow');
