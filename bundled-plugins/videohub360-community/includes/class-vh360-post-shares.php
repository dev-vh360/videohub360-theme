<?php
/**
 * Post Shares functionality for VideoHub360 Community Plugin
 *
 * Handles share count tracking for posts using post meta.
 *
 * @package VH360_Community
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VH360_Post_Shares {
    
    /**
     * Get share count for a post
     * 
     * @param int $post_id Post ID
     * @return int Number of shares
     */
    public static function get_count($post_id) {
        $count = get_post_meta($post_id, 'vh360_share_count', true);
        return $count ? (int) $count : 0;
    }
    
    /**
     * Increment share count for a post
     * 
     * @param int $post_id Post ID
     * @return int New share count
     */
    public static function increment($post_id) {
        $current_count = self::get_count($post_id);
        $new_count = $current_count + 1;
        
        update_post_meta($post_id, 'vh360_share_count', $new_count);
        
        return $new_count;
    }
    
    /**
     * Get comment count for a post
     * 
     * @param int $post_id Post ID
     * @return int Number of approved comments
     */
    public static function get_comment_count($post_id) {
        // Use WordPress native function for consistency
        return (int) get_comments_number($post_id);
    }
}
