<?php
/**
 * YouTube-Style Comment Walker for WordPress Comments
 *
 * Renders native WordPress comments with the same DOM structure and styling
 * as the activity/community comment system, providing a consistent YouTube-style UI.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Walker for WordPress Comments
 *
 * Extends Walker_Comment to output YouTube-style comment markup that matches
 * the activity feed comment structure, including:
 * - Facebook-style comment bubbles
 * - Like/Reply actions
 * - Nested replies with toggle buttons
 * - Kebab menus for edit/delete
 * - Accessibility features (ARIA labels, semantic HTML)
 */
class VH360_YouTube_Comment_Walker extends Walker_Comment {
    
    /**
     * Cached like data for bulk loading
     *
     * @var array
     */
    private static $like_counts = array();
    
    /**
     * Cached user liked comments
     *
     * @var array
     */
    private static $user_liked_comments = array();
    
    /**
     * Flag to track if like data has been loaded
     *
     * @var bool
     */
    private static $likes_loaded = false;
    
    /**
     * Start the list before the elements are added.
     *
     * @param string $output Used to append additional content. Passed by reference.
     * @param int    $depth  Depth of the comment.
     * @param array  $args   An array of arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = array()) {
        $GLOBALS['comment_depth'] = $depth + 1;
        
        // For nested replies, wrap in replies container
        $output .= '<div class="vh360-replies-list vh360-replies-list--hidden" role="list">';
    }
    
    /**
     * End the list of items after the elements are added.
     *
     * @param string $output Used to append additional content. Passed by reference.
     * @param int    $depth  Depth of the comment.
     * @param array  $args   An array of arguments.
     */
    public function end_lvl(&$output, $depth = 0, $args = array()) {
        $GLOBALS['comment_depth'] = $depth + 1;
        
        $output .= '</div><!-- .vh360-replies-list -->';
    }
    
    /**
     * Start the element output.
     *
     * @param string     $output  Used to append additional content. Passed by reference.
     * @param WP_Comment $comment Comment data object.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     * @param int        $id      Current comment ID.
     */
    public function start_el(&$output, $comment, $depth = 0, $args = array(), $id = 0) {
        $depth++;
        $GLOBALS['comment_depth'] = $depth;
        $GLOBALS['comment'] = $comment;
        
        // Bulk load like data once (loads for ALL comments including nested replies)
        // We check depth === 1 to ensure it only runs once on the first top-level comment
        if (!self::$likes_loaded && $depth === 1) {
            $this->bulk_load_like_data($args);
            self::$likes_loaded = true;
        }
        
        $comment_class = ($depth > 1) ? 'vh360-comment-item vh360-comment-reply' : 'vh360-comment-item';
        
        $output .= '<div id="comment-' . get_comment_ID() . '" class="' . esc_attr($comment_class) . '" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
        $output .= $this->render_comment_content($comment, $depth, $args);
    }
    
    /**
     * End the element output.
     *
     * @param string     $output  Used to append additional content. Passed by reference.
     * @param WP_Comment $comment Comment data object.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     */
    public function end_el(&$output, $comment, $depth = 0, $args = array()) {
        $output .= '</div><!-- .vh360-comment-item -->';
    }
    
    /**
     * Bulk load like data for all comments to avoid N+1 queries
     *
     * @param array $args Walker arguments containing all comments
     */
    private function bulk_load_like_data($args) {
        // Only load if comment likes feature is available
        if (!class_exists('VH360_Comment_Likes')) {
            return;
        }
        
        // Extract all comment IDs from the tree (including nested)
        $all_comment_ids = array();
        
        if (isset($args['walker'])) {
            // Get comments from the global comments array
            global $wp_query;
            if (isset($wp_query->comments) && is_array($wp_query->comments)) {
                foreach ($wp_query->comments as $comment) {
                    $all_comment_ids[] = $comment->comment_ID;
                }
            }
        }
        
        if (empty($all_comment_ids)) {
            return;
        }
        
        // Bulk fetch like counts
        self::$like_counts = VH360_Comment_Likes::get_counts_for_comments($all_comment_ids);
        
        // Bulk fetch user's liked comments if logged in
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            self::$user_liked_comments = VH360_Comment_Likes::get_user_liked_comments($all_comment_ids, $current_user_id);
        }
    }
    
    /**
     * Render comment content with YouTube-style markup
     *
     * @param WP_Comment $comment Comment data object.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     * @return string Comment HTML markup.
     */
    private function render_comment_content($comment, $depth, $args) {
        $current_user_id = get_current_user_id();
        
        // Determine avatar size based on depth
        $avatar_size = ($depth > 1) ? 32 : 40;
        
        // Get author information
        $comment_author = get_comment_author($comment);
        $comment_author_url = get_comment_author_url($comment);
        $comment_author_email = get_comment_author_email($comment);
        
        // Permissions
        $is_comment_author = is_user_logged_in() && ((int) $current_user_id === (int) $comment->user_id);
        $can_moderate = current_user_can('moderate_comments');
        $can_edit_comment = is_user_logged_in() && ($is_comment_author || $can_moderate);
        $can_delete_comment = $can_edit_comment;
        
        // Get like data from bulk-loaded arrays
        $like_count = isset(self::$like_counts[$comment->comment_ID]) ? self::$like_counts[$comment->comment_ID] : 0;
        $user_has_liked = in_array($comment->comment_ID, self::$user_liked_comments);
        $liked_class = $user_has_liked ? 'vh360-liked' : '';
        
        // Check if comment is approved
        $comment_approved_class = ('0' == $comment->comment_approved) ? ' comment-awaiting-moderation' : '';
        
        ob_start();
        ?>
        <div class="vh360-comment-row">
            <!-- Avatar Column -->
            <div class="vh360-comment-avatar">
                <?php
                if ($comment->user_id) {
                    echo get_avatar($comment->user_id, $avatar_size, '', esc_attr($comment_author));
                } else {
                    echo get_avatar($comment_author_email, $avatar_size, '', esc_attr($comment_author));
                }
                ?>
            </div>
            
            <!-- Content Column -->
            <div class="vh360-comment-main">
                <!-- Header Row (OUTSIDE bubble) - Name + Kebab -->
                <div class="vh360-comment-header">
                    <?php if ($comment_author_url && $comment->user_id) : ?>
                        <a href="<?php echo esc_url($comment_author_url); ?>" class="vh360-comment-author" data-user-id="<?php echo esc_attr($comment->user_id); ?>">
                            <?php echo esc_html($comment_author); ?>
                        </a>
                    <?php else : ?>
                        <strong class="vh360-comment-author">
                            <?php echo esc_html($comment_author); ?>
                        </strong>
                    <?php endif; ?>
                    
                    <?php if ($can_edit_comment || $can_delete_comment) : ?>
                        <div class="vh360-comment-actions-menu-wrapper">
                            <button type="button"
                                    class="vh360-kebab-toggle"
                                    aria-label="<?php esc_attr_e('Comment options', 'videohub360-theme'); ?>"
                                    aria-haspopup="true"
                                    aria-expanded="false">
                                <span class="vh360-kebab-dot"></span>
                                <span class="vh360-kebab-dot"></span>
                                <span class="vh360-kebab-dot"></span>
                            </button>
                            <!-- Menu dropdown (handled by JS) -->
                            <div class="vh360-actions-menu vh360-actions-menu--hidden" role="menu">
                                <?php if ($can_edit_comment && current_user_can('edit_comment', $comment->comment_ID)) : ?>
                                    <button type="button"
                                            class="vh360-actions-menu-item vh360-wp-comment-edit-btn"
                                            data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                            role="menuitem">
                                        <?php esc_html_e('Edit', 'videohub360-theme'); ?>
                                    </button>
                                <?php endif; ?>
                                <?php if ($can_delete_comment) : ?>
                                    <button type="button"
                                            class="vh360-actions-menu-item vh360-wp-comment-delete-btn"
                                            data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                            role="menuitem">
                                        <?php esc_html_e('Delete', 'videohub360-theme'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Comment Bubble (text ONLY) -->
                <div class="vh360-comment-bubble<?php echo esc_attr($comment_approved_class); ?>">
                    <div class="vh360-comment-text">
                        <?php if ('0' == $comment->comment_approved) : ?>
                            <em class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'videohub360-theme'); ?></em>
                            <br>
                        <?php endif; ?>
                        <?php comment_text($comment); ?>
                    </div>
                </div>
                
                <!-- Actions Row (UNDER bubble) -->
                <div class="vh360-comment-actions">
                    <span class="vh360-comment-time">
                        <a href="<?php echo esc_url(get_comment_link($comment)); ?>" class="vh360-comment-time-link">
                            <?php 
                            /* translators: %s: time ago */
                            printf(
                                esc_html_x('%s ago', 'comment time', 'videohub360-theme'),
                                human_time_diff(get_comment_time('U'), current_time('timestamp'))
                            );
                            ?>
                        </a>
                    </span>
                    
                    <?php if (is_user_logged_in() && class_exists('VH360_Comment_Likes')) : ?>
                        <span class="vh360-action-separator" aria-hidden="true">•</span>
                        <button class="vh360-action-like vh360-wp-comment-like <?php echo esc_attr($liked_class); ?>" 
                                data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                aria-label="<?php esc_attr_e('Like this comment', 'videohub360-theme'); ?>"
                                aria-pressed="<?php echo $user_has_liked ? 'true' : 'false'; ?>">
                            <?php esc_html_e('Like', 'videohub360-theme'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (is_user_logged_in() && comments_open($comment->comment_post_ID)) : ?>
                        <span class="vh360-action-separator" aria-hidden="true">•</span>
                        <button class="vh360-action-reply vh360-wp-comment-reply" 
                                data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                data-post-id="<?php echo esc_attr($comment->comment_post_ID); ?>"
                                data-respond-id="respond"
                                data-reply-to="<?php echo esc_attr($comment->comment_ID); ?>"
                                aria-label="<?php 
                                    /* translators: %s: comment author name */
                                    printf(
                                        esc_attr__('Reply to %s', 'videohub360-theme'),
                                        esc_attr($comment_author)
                                    );
                                ?>">
                            <?php esc_html_e('Reply', 'videohub360-theme'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($like_count > 0) : ?>
                        <span class="vh360-like-count" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>" aria-live="polite">
                            <?php 
                            echo esc_html(
                                sprintf(
                                    _n('%d like', '%d likes', $like_count, 'videohub360-theme'),
                                    $like_count
                                )
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php
        // Add reply toggle button if this comment has children
        if ($depth === 1 && $this->has_children) {
            $reply_count = $this->count_children($comment->comment_ID);
            if ($reply_count > 0) {
                ?>
                <div class="vh360-comment-replies-toggle-wrapper">
                    <button type="button" 
                            class="vh360-toggle-replies vh360-wp-toggle-replies" 
                            data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                            aria-expanded="false"
                            aria-controls="replies-<?php echo esc_attr($comment->comment_ID); ?>">
                        <svg class="vh360-toggle-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php 
                        printf(
                            _n('View %d reply', 'View %d replies', $reply_count, 'videohub360-theme'),
                            $reply_count
                        );
                        ?>
                    </button>
                </div>
                <?php
            }
        }
        
        return ob_get_clean();
    }
    
    /**
     * Count direct children of a comment
     *
     * @param int $comment_id Comment ID.
     * @return int Number of child comments.
     */
    private function count_children($comment_id) {
        global $wp_query;
        
        $count = 0;
        if (isset($wp_query->comments) && is_array($wp_query->comments)) {
            foreach ($wp_query->comments as $comment) {
                if ($comment->comment_parent === $comment_id) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Output a comment in the HTML5 format.
     *
     * @param WP_Comment $comment Comment to display.
     * @param int        $depth   Depth of the current comment.
     * @param array      $args    An array of arguments.
     */
    protected function html5_comment($comment, $depth, $args) {
        // This method is called by default WordPress, but we override start_el
        // so this won't be used. Keeping for compatibility.
    }
}
