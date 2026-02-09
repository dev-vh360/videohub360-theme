<?php
/**
 * Profile Posts Template Part
 *
 * Displays the post composer (when viewing own profile) and a feed of
 * community posts by the profile user.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();

if (!$author_id) {
    return;
}

$author = get_userdata($author_id);
if (!$author) {
    return;
}

// Check if current user is viewing their own profile
$is_own_profile = is_user_logged_in() && get_current_user_id() === $author_id;

// Get pagination
$paged = vh360_get_current_page();
$posts_per_page = 20;

// Query community posts by this author
$args = array(
    'post_type'      => 'vh360_post',
    'post_status'    => 'publish',
    'author'         => $author_id,
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$profile_posts = new WP_Query($args);
?>

<div class="vh360-profile-posts">
    <h2 class="vh360-profile-section-title"><?php esc_html_e('Posts', 'videohub360-theme'); ?></h2>

    <!-- Posts Feed -->
    <div class="vh360-profile-posts-feed">
        <?php
        if ($profile_posts->have_posts()) :
            while ($profile_posts->have_posts()) :
                $profile_posts->the_post();
                $post_obj = get_post();
                vh360_render_community_post($post_obj, true);
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <div class="vh360-profile-posts-empty">
                <div class="vh360-empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <h3><?php esc_html_e('No posts yet', 'videohub360-theme'); ?></h3>
                    <p>
                        <?php
                        if ($is_own_profile) {
                            esc_html_e('Share your first post with the community!', 'videohub360-theme');
                        } else {
                            /* translators: %s: User's display name */
                            printf(esc_html__('%s hasn\'t posted anything yet.', 'videohub360-theme'), esc_html($author->display_name));
                        }
                        ?>
                    </p>
                </div>
            </div>
            <?php
        endif;
        ?>
    </div>

    <!-- Pagination -->
    <?php if ($profile_posts->max_num_pages > 1) : ?>
        <div class="vh360-profile-pagination">
            <?php
            // Get pagination arguments and display
            $pagination_args = vh360_get_author_pagination_args(
                $author_id,
                $paged,
                $profile_posts->max_num_pages
            );
            
            echo paginate_links($pagination_args);
            ?>
        </div>
    <?php endif; ?>
</div>
