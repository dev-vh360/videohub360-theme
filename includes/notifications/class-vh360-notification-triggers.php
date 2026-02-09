<?php
/**
 * VH360 Notification Triggers
 *
 * Hooks into existing theme actions to create notifications.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Notification_Triggers
 */
class VH360_Notification_Triggers {
    
    /**
     * Singleton instance
     *
     * @var VH360_Notification_Triggers
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Notification_Triggers
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Follow system notifications
        add_action('vh360_user_followed', array($this, 'on_user_followed'), 10, 2);
        
        // Post like notifications
        add_action('vh360_post_liked', array($this, 'on_post_liked'), 10, 2);
        
        // Comment notifications (native WordPress comments)
        add_action('comment_post', array($this, 'on_comment_posted'), 10, 3);
        
        // Comment notifications (custom AJAX comments)
        add_action('vh360_comment_created', array($this, 'on_custom_comment_created'), 10, 2);
        
        // We'll check for mentions when content is saved/created
        add_action('save_post_vh360_post', array($this, 'on_post_saved'), 10, 3);
    }
    
    /**
     * Handle user followed event
     *
     * @param int $follower_id User who followed
     * @param int $followed_id User who was followed
     */
    public function on_user_followed($follower_id, $followed_id) {
        vh360_create_notification(
            $followed_id,
            'follow',
            $follower_id,
            $follower_id,
            'user',
            ''
        );
    }
    
    /**
     * Handle post liked event
     *
     * @param int $post_id Post that was liked
     * @param int $user_id User who liked the post
     */
    public function on_post_liked($post_id, $user_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'vh360_post') {
            return;
        }
        
        // Notify post author
        vh360_create_notification(
            $post->post_author,
            'like',
            $user_id,
            $post_id,
            'post',
            ''
        );
    }
    
    /**
     * Handle comment posted event (native WordPress comments)
     *
     * @param int $comment_id Comment ID
     * @param int|string $comment_approved Comment approval status
     * @param array $commentdata Comment data array
     */
    public function on_comment_posted($comment_id, $comment_approved, $commentdata) {
        // Only process approved comments
        if ($comment_approved !== 1 && $comment_approved !== 'approve') {
            return;
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }
        
        $post = get_post($comment->comment_post_ID);
        
        // Only handle community post comments
        if (!$post || $post->post_type !== 'vh360_post') {
            return;
        }
        
        // Process comment notification (shared logic)
        $this->process_comment_notification($comment, $post);
    }
    
    /**
     * Handle custom comment created event (for AJAX comments)
     *
     * This is a simplified handler for the vh360_comment_created action that is fired
     * when comments are added via AJAX. It retrieves the comment and processes it
     * similar to on_comment_posted but without the approval check (already approved).
     *
     * @param int $comment_id Comment ID
     * @param int $post_id Post ID
     */
    public function on_custom_comment_created($comment_id, $post_id) {
        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }
        
        $post = get_post($post_id);
        
        // Only handle community post comments
        if (!$post || $post->post_type !== 'vh360_post') {
            return;
        }
        
        // Process comment notification
        $this->process_comment_notification($comment, $post);
    }
    
    /**
     * Process comment notification logic (shared by both AJAX and native comments)
     *
     * @param WP_Comment $comment Comment object
     * @param WP_Post $post Post object
     */
    private function process_comment_notification($comment, $post) {
        $commenter_id = (int) $comment->user_id;
        $reply_recipient_id = null;
        
        // If it's a reply to another comment
        if ($comment->comment_parent > 0) {
            $parent_comment = get_comment($comment->comment_parent);
            if ($parent_comment && $parent_comment->user_id) {
                // Don't notify if replying to own comment
                if ($commenter_id != $parent_comment->user_id) {
                    $reply_recipient_id = $parent_comment->user_id;
                    // Notify the parent comment author
                    vh360_create_notification(
                        $parent_comment->user_id,
                        'reply',
                        $commenter_id,
                        $comment->comment_ID,
                        'comment',
                        ''
                    );
                }
            }
        } else {
            // Top-level comment - notify post author
            // Don't notify if commenting on own post
            if ($commenter_id != $post->post_author) {
                vh360_create_notification(
                    $post->post_author,
                    'comment',
                    $commenter_id,
                    $post->ID,
                    'post',
                    ''
                );
            }
        }
        
        // Check for mentions in comment, but exclude the reply recipient to avoid duplicate notifications
        $this->check_mentions_in_content($comment->comment_content, $commenter_id, $comment->comment_ID, 'comment', $reply_recipient_id);
    }
    
    /**
     * Handle post saved event - check for mentions
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function on_post_saved($post_id, $post, $update) {
        // Only process published posts
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Check for mentions in post content
        $author_id = (int) $post->post_author;
        $this->check_mentions_in_content($post->post_content, $author_id, $post_id, 'post');
    }
    
    /**
     * Check for @mentions in content and create notifications
     *
     * @param string $content Content to check for mentions
     * @param int $actor_id User who created the content
     * @param int $object_id Post or comment ID
     * @param string $object_type 'post' or 'comment'
     * @param int $exclude_user_id Optional user ID to exclude from mention notifications (e.g., reply recipient)
     */
    private function check_mentions_in_content($content, $actor_id, $object_id, $object_type, $exclude_user_id = null) {
        if (empty($content)) {
            return;
        }
        
        // Pattern to match @username
        $pattern = '/@([A-Za-z0-9_\.]+)/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            $mentioned_usernames = array_unique($matches[1]);
            
            foreach ($mentioned_usernames as $username) {
                $user = get_user_by('login', $username);
                
                // Skip if: user not found, is the actor, or is the excluded user (reply recipient)
                if ($user && $user->ID !== $actor_id && $user->ID !== $exclude_user_id) {
                    vh360_create_notification(
                        $user->ID,
                        'mention',
                        $actor_id,
                        $object_id,
                        $object_type,
                        ''
                    );
                }
            }
        }
    }
}
