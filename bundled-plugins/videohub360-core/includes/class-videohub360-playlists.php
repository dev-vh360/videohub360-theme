<?php
/**
 * VideoHub360 Playlists Class
 * 
 * Manages user playlists and playlist items
 * 
 * @since 2.7.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Playlists {
    
    /**
     * Create a new playlist
     * 
     * @param int $user_id User ID
     * @param string $title Playlist title
     * @param string $description Playlist description (optional)
     * @param string $privacy Privacy setting (private, unlisted, public)
     * @return array Array with success status and playlist data
     */
    public static function create_playlist($user_id, $title, $description = '', $privacy = 'private') {
        global $wpdb;
        
        $user_id = absint($user_id);
        $title = sanitize_text_field($title);
        $description = wp_kses_post($description);
        $privacy = in_array($privacy, array('private', 'unlisted', 'public')) ? $privacy : 'private';
        
        if (!$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid user ID.', 'videohub360')
            );
        }
        
        if (empty($title)) {
            return array(
                'success' => false,
                'message' => __('Playlist title is required.', 'videohub360')
            );
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlists';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'privacy' => $privacy
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to create playlist.', 'videohub360')
            );
        }
        
        $playlist_id = $wpdb->insert_id;
        
        return array(
            'success' => true,
            'message' => __('Playlist created successfully.', 'videohub360'),
            'playlist' => array(
                'id' => $playlist_id,
                'title' => $title,
                'description' => $description,
                'privacy' => $privacy
            )
        );
    }
    
    /**
     * Get user's playlists
     * 
     * @param int $user_id User ID
     * @return array Array of playlists
     */
    public static function get_user_playlists($user_id) {
        global $wpdb;
        
        $user_id = absint($user_id);
        if (!$user_id) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlists';
        
        $playlists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);
        
        // Add video count to each playlist
        foreach ($playlists as &$playlist) {
            $playlist['video_count'] = self::get_playlist_video_count($playlist['id']);
        }
        
        return $playlists;
    }
    
    /**
     * Get playlist by ID
     * 
     * @param int $playlist_id Playlist ID
     * @param int $user_id Optional user ID for ownership check
     * @return array|null Playlist data or null if not found
     */
    public static function get_playlist($playlist_id, $user_id = null) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        if (!$playlist_id) {
            return null;
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlists';
        
        $playlist = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $playlist_id
        ), ARRAY_A);
        
        if (!$playlist) {
            return null;
        }
        
        // Check ownership if user_id provided
        if ($user_id !== null && intval($playlist['user_id']) !== absint($user_id)) {
            return null;
        }
        
        // Add video count
        $playlist['video_count'] = self::get_playlist_video_count($playlist_id);
        
        return $playlist;
    }
    
    /**
     * Add video to playlist
     * 
     * @param int $playlist_id Playlist ID
     * @param int $video_id Video post ID
     * @param int $user_id User ID (for ownership check)
     * @return array Array with success status
     */
    public static function add_video($playlist_id, $video_id, $user_id) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$playlist_id || !$video_id || !$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid playlist, video, or user ID.', 'videohub360')
            );
        }
        
        // Verify playlist ownership
        $playlist = self::get_playlist($playlist_id, $user_id);
        if (!$playlist) {
            return array(
                'success' => false,
                'message' => __('Playlist not found or access denied.', 'videohub360')
            );
        }
        
        // Verify video exists and is videohub360 post type
        if (get_post_type($video_id) !== 'videohub360') {
            return array(
                'success' => false,
                'message' => __('Invalid video.', 'videohub360')
            );
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        // Check if video already in playlist
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE playlist_id = %d AND video_id = %d",
            $playlist_id,
            $video_id
        ));
        
        if ($exists) {
            return array(
                'success' => false,
                'message' => __('Video already in playlist.', 'videohub360')
            );
        }
        
        // Get next position
        $max_position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM $table_name WHERE playlist_id = %d",
            $playlist_id
        ));
        $position = intval($max_position) + 1;
        
        // Insert video into playlist
        $result = $wpdb->insert(
            $table_name,
            array(
                'playlist_id' => $playlist_id,
                'video_id' => $video_id,
                'position' => $position
            ),
            array('%d', '%d', '%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to add video to playlist.', 'videohub360')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Video added to playlist.', 'videohub360')
        );
    }
    
    /**
     * Remove video from playlist
     * 
     * @param int $playlist_id Playlist ID
     * @param int $video_id Video post ID
     * @param int $user_id User ID (for ownership check)
     * @return array Array with success status
     */
    public static function remove_video($playlist_id, $video_id, $user_id) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$playlist_id || !$video_id || !$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid playlist, video, or user ID.', 'videohub360')
            );
        }
        
        // Verify playlist ownership
        $playlist = self::get_playlist($playlist_id, $user_id);
        if (!$playlist) {
            return array(
                'success' => false,
                'message' => __('Playlist not found or access denied.', 'videohub360')
            );
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'playlist_id' => $playlist_id,
                'video_id' => $video_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to remove video from playlist.', 'videohub360')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Video removed from playlist.', 'videohub360')
        );
    }
    
    /**
     * Get playlist videos
     * 
     * @param int $playlist_id Playlist ID
     * @param int $page Page number for pagination (default: 1)
     * @param int $per_page Number of results per page (default: 20)
     * @return array Array of video IDs
     */
    public static function get_playlist_videos($playlist_id, $page = 1, $per_page = 20) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        $page = max(1, absint($page));
        $per_page = max(1, min(100, absint($per_page)));
        $offset = ($page - 1) * $per_page;
        
        if (!$playlist_id) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        $video_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT video_id FROM $table_name 
             WHERE playlist_id = %d 
             ORDER BY position ASC 
             LIMIT %d OFFSET %d",
            $playlist_id,
            $per_page,
            $offset
        ));
        
        return array_map('intval', $video_ids);
    }
    
    /**
     * Get count of videos in playlist
     * 
     * @param int $playlist_id Playlist ID
     * @return int Video count
     */
    public static function get_playlist_video_count($playlist_id) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        if (!$playlist_id) {
            return 0;
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE playlist_id = %d",
            $playlist_id
        ));
        
        return intval($count);
    }
    
    /**
     * Delete playlist
     * 
     * @param int $playlist_id Playlist ID
     * @param int $user_id User ID (for ownership check)
     * @return array Array with success status
     */
    public static function delete_playlist($playlist_id, $user_id) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        $user_id = absint($user_id);
        
        if (!$playlist_id || !$user_id) {
            return array(
                'success' => false,
                'message' => __('Invalid playlist or user ID.', 'videohub360')
            );
        }
        
        // Verify playlist ownership
        $playlist = self::get_playlist($playlist_id, $user_id);
        if (!$playlist) {
            return array(
                'success' => false,
                'message' => __('Playlist not found or access denied.', 'videohub360')
            );
        }
        
        // Delete playlist items first
        $items_table = $wpdb->prefix . 'vh360_playlist_items';
        $wpdb->delete($items_table, array('playlist_id' => $playlist_id), array('%d'));
        
        // Delete playlist
        $playlists_table = $wpdb->prefix . 'vh360_playlists';
        $result = $wpdb->delete(
            $playlists_table,
            array('id' => $playlist_id),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to delete playlist.', 'videohub360')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Playlist deleted successfully.', 'videohub360')
        );
    }
    
    /**
     * Check if video is in a specific playlist
     * 
     * @param int $playlist_id Playlist ID
     * @param int $video_id Video post ID
     * @return bool True if video is in playlist
     */
    public static function is_video_in_playlist($playlist_id, $video_id) {
        global $wpdb;
        
        $playlist_id = absint($playlist_id);
        $video_id = absint($video_id);
        
        if (!$playlist_id || !$video_id) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE playlist_id = %d AND video_id = %d",
            $playlist_id,
            $video_id
        ));
        
        return (bool) $exists;
    }
    
    /**
     * Get playlists containing a specific video
     * 
     * @param int $video_id Video post ID
     * @param int $user_id User ID
     * @return array Array of playlist IDs
     */
    public static function get_playlists_with_video($video_id, $user_id) {
        global $wpdb;
        
        $video_id = absint($video_id);
        $user_id = absint($user_id);
        
        if (!$video_id || !$user_id) {
            return array();
        }
        
        $items_table = $wpdb->prefix . 'vh360_playlist_items';
        $playlists_table = $wpdb->prefix . 'vh360_playlists';
        
        $playlist_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT p.id FROM $playlists_table p 
             INNER JOIN $items_table pi ON p.id = pi.playlist_id 
             WHERE pi.video_id = %d AND p.user_id = %d",
            $video_id,
            $user_id
        ));
        
        return array_map('intval', $playlist_ids);
    }
}
