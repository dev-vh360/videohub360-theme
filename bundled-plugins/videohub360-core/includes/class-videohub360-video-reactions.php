<?php
/**
 * VideoHub360 Video Reactions Class
 * 
 * Manages like/dislike reactions for videos
 * 
 * @since 2.7.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Video_Reactions {
    
    /**
     * Get reaction counts for a video
     * 
     * @param int $video_id Video post ID
     * @return array Array with 'likes' and 'dislikes' counts
     */
    public static function get_counts($video_id) {
        global $wpdb;
        
        $video_id = absint($video_id);
        if (!$video_id) {
            return array('likes' => 0, 'dislikes' => 0);
        }
        
        // Verify post type
        if (get_post_type($video_id) !== 'videohub360') {
            return array('likes' => 0, 'dislikes' => 0);
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        // Get counts for each reaction type
        $likes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE video_id = %d AND reaction = 'like'",
            $video_id
        ));
        
        $dislikes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE video_id = %d AND reaction = 'dislike'",
            $video_id
        ));
        
        return array(
            'likes' => intval($likes),
            'dislikes' => intval($dislikes)
        );
    }
    
    /**
     * Get user's reaction to a video
     * 
     * @param int $video_id Video post ID
     * @param int $user_id User ID
     * @return string|null 'like', 'dislike', or null if no reaction
     */
    public static function get_user_reaction($video_id, $user_id) {
        global $wpdb;
        
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$video_id || !$user_id) {
            return null;
        }
        
        // Verify post type
        if (get_post_type($video_id) !== 'videohub360') {
            return null;
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        $reaction = $wpdb->get_var($wpdb->prepare(
            "SELECT reaction FROM $table_name WHERE video_id = %d AND user_id = %d",
            $video_id,
            $user_id
        ));
        
        return $reaction;
    }
    
    /**
     * Set user's reaction to a video
     * 
     * @param int $video_id Video post ID
     * @param int $user_id User ID
     * @param string $reaction 'like' or 'dislike'
     * @return array Array with success status, updated counts, and user reaction
     */
    public static function set_reaction($video_id, $user_id, $reaction) {
        global $wpdb;
        
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$video_id || !$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid video or user ID.', 'videohub360')
            );
        }
        
        // Verify post type
        if (get_post_type($video_id) !== 'videohub360') {
            return array(
                'success' => false,
                'message' => __('Invalid video.', 'videohub360')
            );
        }
        
        // Validate reaction
        if (!in_array($reaction, array('like', 'dislike'))) {
            return array(
                'success' => false,
                'message' => __('Invalid reaction type.', 'videohub360')
            );
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        // Check if user has existing reaction
        $existing = self::get_user_reaction($video_id, $user_id);
        
        if ($existing === $reaction) {
            // User clicked same reaction - remove it
            $wpdb->delete(
                $table_name,
                array(
                    'video_id' => $video_id,
                    'user_id' => $user_id
                ),
                array('%d', '%d')
            );
            
            return array(
                'success' => true,
                'message' => __('Reaction removed.', 'videohub360'),
                'counts' => self::get_counts($video_id),
                'userReaction' => null
            );
        } elseif ($existing) {
            // User has different reaction - update it
            $wpdb->update(
                $table_name,
                array('reaction' => $reaction),
                array(
                    'video_id' => $video_id,
                    'user_id' => $user_id
                ),
                array('%s'),
                array('%d', '%d')
            );
        } else {
            // No existing reaction - insert new one
            $wpdb->insert(
                $table_name,
                array(
                    'video_id' => $video_id,
                    'user_id' => $user_id,
                    'reaction' => $reaction
                ),
                array('%d', '%d', '%s')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Reaction saved.', 'videohub360'),
            'counts' => self::get_counts($video_id),
            'userReaction' => $reaction
        );
    }
    
    /**
     * Clear user's reaction to a video
     * 
     * @param int $video_id Video post ID
     * @param int $user_id User ID
     * @return array Array with success status and updated counts
     */
    public static function clear_reaction($video_id, $user_id) {
        global $wpdb;
        
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$video_id || !$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid video or user ID.', 'videohub360')
            );
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        $wpdb->delete(
            $table_name,
            array(
                'video_id' => $video_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        return array(
            'success' => true,
            'message' => __('Reaction cleared.', 'videohub360'),
            'counts' => self::get_counts($video_id),
            'userReaction' => null
        );
    }
    
    /**
     * Get videos liked by a user
     * 
     * @param int $user_id User ID
     * @param int $page Page number for pagination (default: 1)
     * @param int $per_page Number of results per page (default: 20)
     * @return array Array of video IDs
     */
    public static function get_liked_videos($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $user_id = absint($user_id);
        $page = max(1, absint($page));
        $per_page = max(1, min(100, absint($per_page)));
        $offset = ($page - 1) * $per_page;
        
        if (!$user_id) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        $video_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT video_id FROM $table_name 
             WHERE user_id = %d AND reaction = 'like' 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        return array_map('intval', $video_ids);
    }
    
    /**
     * Get total count of liked videos for a user
     * 
     * @param int $user_id User ID
     * @return int Total count
     */
    public static function get_liked_videos_count($user_id) {
        global $wpdb;
        
        $user_id = absint($user_id);
        if (!$user_id) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND reaction = 'like'",
            $user_id
        ));
        
        return intval($count);
    }
}
