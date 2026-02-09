<?php
/**
 * Trending posts, authors and topics helpers for Videohub360.
 *
 * This file contains utility functions to calculate trending posts,
 * authors and topics for display in the activity feed sidebar. Trending
 * posts are determined based on the comment count for a post and the
 * date the post was created. Likes are stored on each post as an array
 * (meta key `vh360_likes`), so sorting by comment count provides a
 * reasonable measure of popularity. Only posts created within the past
 * two weeks are considered. Trending authors are ranked by their
 * follower counts (`vh360_followers_count` user meta). Trending topics
 * are calculated from the most popular post categories used by recent
 * community posts.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get trending community posts.
 *
 * @param int $limit Number of posts to return. Defaults to 3.
 * @return WP_Post[] Array of post objects.
 */
function vh360_get_trending_posts($limit = 3) {
    $args = [
        // Our community posts are stored as `vh360_post` post type.
        'post_type'      => 'vh360_post',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'date_query'     => [
            [
                'after' => '2 weeks ago',
            ],
        ],
        // Order by comment count to approximate post popularity. Likes are stored as
        // an array (vh360_likes) so we cannot sort by like count directly.
        'orderby'        => 'comment_count',
        'order'          => 'DESC',
    ];
    $query = new WP_Query($args);
    return $query->posts;
}

/**
 * Get trending topics based on category usage in recent posts.
 *
 * This helper returns the most popular categories used by the
 * `vh360_post` post type in the last two weeks. It orders categories
 * by the number of posts assigned to each category. Because WordPress
 * stores term counts globally, we derive the counts by performing a
 * query for recent posts and tallying their category assignments.
 *
 * @param int $limit Number of topics to return. Defaults to 5.
 * @return array Array of WP_Term objects.
 */
function vh360_get_trending_topics($limit = 5) {
    // Query recent community posts to gather their categories.
    $recent_posts = get_posts([
        'post_type'      => 'vh360_post',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'date_query'     => [
            [
                'after' => '2 weeks ago',
            ],
        ],
        'fields'         => 'ids',
    ]);

    $category_counts = [];
    foreach ($recent_posts as $post_id) {
        $cats = wp_get_post_terms($post_id, 'category');
        foreach ($cats as $cat) {
            if (!isset($category_counts[$cat->term_id])) {
                $category_counts[$cat->term_id] = 0;
            }
            $category_counts[$cat->term_id]++;
        }
    }

    // Sort categories by count descending.
    arsort($category_counts);
    $top_categories = array_slice($category_counts, 0, $limit, true);
    $terms = [];
    foreach (array_keys($top_categories) as $cat_id) {
        $term = get_term($cat_id, 'category');
        if ($term && !is_wp_error($term)) {
            // Attach the calculated count to the term object
            $term->trending_count = $category_counts[$cat_id];
            $terms[] = $term;
        }
    }
    return $terms;
}

/**
 * Get trending authors based on follower count.
 *
 * @param int $limit Number of authors to return. Defaults to 3.
 * @return array Array of WP_User objects.
 */
function vh360_get_trending_authors($limit = 3) {
    // Query WordPress users sorted by meta value.
    $args = [
        'number'     => $limit,
        'orderby'    => 'meta_value_num',
        'meta_key'   => 'vh360_followers_count',
        'order'      => 'DESC',
        'fields'     => 'all',
        'meta_query' => [
            [
                'key'     => 'vh360_followers_count',
                'value'   => 0,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ],
    ];
    $users = get_users($args);
    return $users;
}
