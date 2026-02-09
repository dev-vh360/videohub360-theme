<?php
/**
 * Recommended users helper for Videohub360.
 *
 * This helper provides a list of suggested users for the current user to
 * follow. Suggestions are based on trending authors (users with a high
 * follower count) who the current user does not already follow. The
 * returned value is an array of `WP_User` objects and can be used to
 * populate a recommended profiles widget in the activity feed sidebar.
 *
 * @package Videohub360
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get recommended users to follow.
 *
 * This function retrieves a list of trending authors (see
 * `vh360_get_trending_authors()` in includes/trending.php) and filters
 * out the current user and users that the current user already
 * follows. The result is trimmed to the desired length.
 *
 * @param int $limit Number of users to return. Defaults to 5.
 * @return array Array of WP_User objects representing recommended users.
 */
function vh360_get_recommended_users($limit = 5) {
    $current_user = wp_get_current_user();
    if (!$current_user || !isset($current_user->ID) || 0 === $current_user->ID) {
        return [];
    }

    // Fetch the list of users the current user already follows.
    $following = get_user_meta($current_user->ID, 'vh360_following', true);
    if (!is_array($following)) {
        $following = [];
    }

    // Get a pool of trending authors from which to derive recommendations.
    // We request more users than needed to ensure we have enough after
    // filtering out those already followed.
    $candidate_authors = vh360_get_trending_authors($limit * 3);
    $recommended = [];

    foreach ($candidate_authors as $author) {
        // Skip the current user and users already followed.
        if ($author->ID === $current_user->ID || in_array($author->ID, $following, true)) {
            continue;
        }
        $recommended[] = $author;
        if (count($recommended) >= $limit) {
            break;
        }
    }
    return $recommended;
}

/**
 * Alias for vh360_get_recommended_users() to match the naming convention
 * used in the activity feed template.
 *
 * @param int $limit Number of users to return. Defaults to 5.
 * @return array Array of WP_User objects representing recommended users.
 */
function vh360_get_recommended_profiles($limit = 5) {
    return vh360_get_recommended_users($limit);
}