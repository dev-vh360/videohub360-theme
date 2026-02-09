<?php
/**
 * Comment Likes functionality for VideoHub360 Community Plugin
 *
 * Handles like/unlike operations, counts, and user state tracking for comments.
 *
 * @package VH360_Community
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VH360_Comment_Likes {
    
    /**
     * Get like count for a comment
     * 
     * @param int $comment_id Comment ID
     * @return int Number of likes
     */
    public static function get_count($comment_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_comment_likes';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE comment_id = %d",
            $comment_id
        ));
        
        return (int) $count;
    }
    
    /**
     * Check if user has liked a comment
     * 
     * @param int $comment_id Comment ID
     * @param int $user_id User ID
     * @return bool True if liked, false otherwise
     */
    public static function user_has_liked($comment_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_comment_likes';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE comment_id = %d AND user_id = %d",
            $comment_id,
            $user_id
        ));
        
        return !empty($exists);
    }
    
    /**
     * Toggle like for a comment
     * 
     * @param int $comment_id Comment ID
     * @param int $user_id User ID
     * @return array ['liked' => bool, 'count' => int]
     */
    public static function toggle($comment_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_comment_likes';
        
        // Check if already liked
        $already_liked = self::user_has_liked($comment_id, $user_id);
        
        if ($already_liked) {
            // Unlike: delete row
            $wpdb->delete(
                $table,
                array(
                    'comment_id' => $comment_id,
                    'user_id' => $user_id,
                ),
                array('%d', '%d')
            );
            $liked = false;
        } else {
            // Like: insert row
            $wpdb->insert(
                $table,
                array(
                    'comment_id' => $comment_id,
                    'user_id' => $user_id,
                    'created_at' => current_time('mysql', true), // Use GMT for consistency with DB default
                ),
                array('%d', '%d', '%s')
            );
            $liked = true;
        }
        
        return array(
            'liked' => $liked,
            'count' => self::get_count($comment_id),
        );
    }
    
    /**
     * Get like counts for multiple comments at once (bulk query for performance)
     * 
     * @param array $comment_ids Array of comment IDs
     * @return array Associative array [comment_id => count]
     */
    public static function get_counts_for_comments($comment_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_comment_likes';
        
        if (empty($comment_ids)) {
            return array();
        }
        
        $comment_ids = array_map('absint', $comment_ids);
        $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        
        $query = "SELECT comment_id, COUNT(*) as like_count 
                  FROM $table 
                  WHERE comment_id IN ($placeholders) 
                  GROUP BY comment_id";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $comment_ids), ARRAY_A);
        
        $counts = array();
        foreach ($results as $row) {
            $counts[$row['comment_id']] = (int) $row['like_count'];
        }
        
        return $counts;
    }
    
    /**
     * Check which comments a user has liked (bulk query for performance)
     * 
     * @param array $comment_ids Array of comment IDs
     * @param int $user_id User ID
     * @return array Array of comment IDs that user has liked
     */
    public static function get_user_liked_comments($comment_ids, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vh360_comment_likes';
        
        if (empty($comment_ids) || !$user_id) {
            return array();
        }
        
        $comment_ids = array_map('absint', $comment_ids);
        $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
        
        $query = "SELECT comment_id 
                  FROM $table 
                  WHERE comment_id IN ($placeholders) AND user_id = %d";
        
        $params = array_merge($comment_ids, array($user_id));
        $results = $wpdb->get_col($wpdb->prepare($query, $params));
        
        return array_map('intval', $results);
    }
}
