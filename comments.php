<?php
/**
 * The template for displaying comments
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load YouTube-style comment walker and form
require_once get_template_directory() . '/includes/comments/class-vh360-youtube-comment-walker.php';
require_once get_template_directory() . '/includes/comments/comment-form-youtube-style.php';

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area vh360-comments-section">

    <?php
    // You can start editing here -- including this comment!
    if (have_comments()) :
    ?>
        <h2 class="comments-title vh360-comments-title">
            <?php
            $comment_count = get_comments_number();
            if ('1' === $comment_count) {
                printf(
                    /* translators: 1: comment count */
                    esc_html__('%s Comment', 'videohub360-theme'),
                    number_format_i18n($comment_count)
                );
            } else {
                printf(
                    /* translators: 1: comment count */
                    esc_html__('%s Comments', 'videohub360-theme'),
                    number_format_i18n($comment_count)
                );
            }
            ?>
        </h2><!-- .comments-title -->

        <?php
        // Navigation above comments
        if (get_comment_pages_count() > 1 && get_option('page_comments')) :
        ?>
            <nav class="vh360-comment-navigation" role="navigation" aria-label="<?php esc_attr_e('Comments navigation', 'videohub360-theme'); ?>">
                <div class="nav-links">
                    <div class="nav-previous"><?php previous_comments_link(esc_html__('Older Comments', 'videohub360-theme')); ?></div>
                    <div class="nav-next"><?php next_comments_link(esc_html__('Newer Comments', 'videohub360-theme')); ?></div>
                </div>
            </nav>
        <?php endif; ?>

        <div class="vh360-comments-thread">
            <?php
            wp_list_comments(vh360_get_youtube_comment_list_args());
            ?>
        </div><!-- .vh360-comments-thread -->

        <?php
        // Navigation below comments
        if (get_comment_pages_count() > 1 && get_option('page_comments')) :
        ?>
            <nav class="vh360-comment-navigation" role="navigation" aria-label="<?php esc_attr_e('Comments navigation', 'videohub360-theme'); ?>">
                <div class="nav-links">
                    <div class="nav-previous"><?php previous_comments_link(esc_html__('Older Comments', 'videohub360-theme')); ?></div>
                    <div class="nav-next"><?php next_comments_link(esc_html__('Newer Comments', 'videohub360-theme')); ?></div>
                </div>
            </nav>
        <?php endif; ?>

        <?php
        // If comments are closed and there are comments, let's leave a little note, shall we?
        if (!comments_open()) :
        ?>
            <p class="no-comments vh360-no-comments"><?php esc_html_e('Comments are closed.', 'videohub360-theme'); ?></p>
        <?php
        endif;

    else :
        // No comments yet
        if (comments_open()) :
        ?>
            <p class="vh360-comments-empty"><?php esc_html_e('Be the first to comment.', 'videohub360-theme'); ?></p>
        <?php
        endif;
    endif; // Check for have_comments().

    // Output YouTube-style comment form
    if (comments_open()) {
        vh360_youtube_style_comment_form();
    }
    ?>

</div><!-- #comments -->
