<?php
/**
 * YouTube-Style Comment Form for WordPress Comments
 *
 * Provides a custom comment form that matches the activity feed styling.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Output YouTube-style comment form
 *
 * @param array $args   Optional. Default arguments and form fields to override.
 * @param int   $post_id Optional. Post ID. Default is the global post ID.
 */
function vh360_youtube_style_comment_form($args = array(), $post_id = null) {
    if (null === $post_id) {
        $post_id = get_the_ID();
    }
    
    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $user_identity = $user->exists() ? $user->display_name : '';
    
    $req = get_option('require_name_email');
    $html_req = ($req ? " required='required'" : '');
    $html5 = 'html5';
    
    // Login URL for the current page
    $login_url = vh360_get_login_page_url_with_redirect(get_permalink($post_id));
    
    // Login prompt for logged-out visitors
    $vh360_login_prompt = '';
    if (!is_user_logged_in()) {
        $vh360_login_prompt = sprintf(
            '<p class="vh360-comments-login-prompt"><a href="%s" class="vh360-comments-login-link">%s</a></p>',
            esc_url($login_url),
            esc_html__('Log in to comment?', 'videohub360-theme')
        );
    }
    
    // Custom fields matching activity feed style
    $fields = array(
        'author' => '<div class="vh360-comment-form-field">
            <label for="author">' . esc_html__('Name', 'videohub360-theme') . ($req ? ' <span class="required">*</span>' : '') . '</label>
            <input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" maxlength="245"' . $html_req . ' />
        </div>',
        
        'email' => '<div class="vh360-comment-form-field">
            <label for="email">' . esc_html__('Email', 'videohub360-theme') . ($req ? ' <span class="required">*</span>' : '') . '</label>
            <input id="email" name="email" ' . ($html5 ? 'type="email"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_email']) . '" size="30" maxlength="100" aria-describedby="email-notes"' . $html_req . ' />
        </div>',
        
        'url' => '<div class="vh360-comment-form-field">
            <label for="url">' . esc_html__('Website', 'videohub360-theme') . '</label>
            <input id="url" name="url" ' . ($html5 ? 'type="url"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_url']) . '" size="30" maxlength="200" />
        </div>',
    );
    
    // Remove fields for logged-in users
    if (is_user_logged_in()) {
        $fields = array();
    }
    
    // Comment field with YouTube-style textarea
    $comment_field = '<div class="vh360-comment-form">';
    
    if (is_user_logged_in()) {
        $comment_field .= '<div class="vh360-comment-avatar">';
        $comment_field .= get_avatar(get_current_user_id(), 40);
        $comment_field .= '</div>';
    }
    
    $comment_field .= '<div class="vh360-comment-input-wrapper">';
    $comment_field .= '<textarea id="comment" name="comment" class="vh360-comment-textarea" cols="45" rows="3" maxlength="65525" required="required" placeholder="' . esc_attr__('Add a comment...', 'videohub360-theme') . '" aria-label="' . esc_attr__('Comment text', 'videohub360-theme') . '"></textarea>';
    $comment_field .= '<button type="submit" class="vh360-comment-send-btn" aria-label="' . esc_attr__('Post comment', 'videohub360-theme') . '">';
    $comment_field .= '<span class="vh360-btn-text">' . esc_html__('Post', 'videohub360-theme') . '</span>';
    $comment_field .= '<span class="vh360-btn-spinner" role="status" aria-label="' . esc_attr__('Posting...', 'videohub360-theme') . '"></span>';
    $comment_field .= '</button>';
    $comment_field .= '</div>';
    $comment_field .= '</div>';
    
    // Default arguments
    $defaults = array(
        'fields' => $fields,
        'comment_field' => $comment_field,
        'must_log_in' => '<p class="must-log-in">' .
            sprintf(
                /* translators: %s: login URL */
                __('You must be <a href="%s">logged in</a> to post a comment.', 'videohub360-theme'),
                vh360_get_login_page_url_with_redirect(apply_filters('the_permalink', get_permalink($post_id)))
            ) . '</p>',
        'logged_in_as' => '',
        'comment_notes_before' => $vh360_login_prompt,
        'comment_notes_after' => '',
        'id_form' => 'commentform',
        'id_submit' => 'submit',
        'class_form' => 'comment-form vh360-wp-comment-form',
        'class_submit' => 'submit vh360-comment-submit-hidden',
        'name_submit' => 'submit',
        'title_reply' => __('Leave a Comment', 'videohub360-theme'),
        'title_reply_to' => __('Leave a Reply to %s', 'videohub360-theme'),
        'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
        'title_reply_after' => '</h3>',
        'cancel_reply_before' => ' <small>',
        'cancel_reply_after' => '</small>',
        'cancel_reply_link' => __('Cancel reply', 'videohub360-theme'),
        'label_submit' => __('Post Comment', 'videohub360-theme'),
        'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" style="display:none;" />',
        'submit_field' => '<div class="form-submit">%1$s %2$s</div>',
        'format' => 'html5',
    );
    
    // Merge with custom args
    $args = wp_parse_args($args, $defaults);
    
    // Output the form
    comment_form($args, $post_id);
}

/**
 * Get YouTube-style comment list args
 *
 * @return array Comment list arguments for wp_list_comments()
 */
function vh360_get_youtube_comment_list_args() {
    return array(
        'walker' => new VH360_YouTube_Comment_Walker(),
        'style' => 'div',
        'short_ping' => true,
        'avatar_size' => 40,
        'max_depth' => get_option('thread_comments_depth', 5),
    );
}
