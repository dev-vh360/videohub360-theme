<?php
/**
 * Profile Videos Template Part
 *
 * Displays user's videos in a grid layout with pagination and filtering.
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

// Get pagination
$paged = vh360_get_current_page();

// Get sort parameter
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'latest';

// Build query args - ONLY community posts with video attachments
$args = array(
    'author' => $author_id,
    'post_type' => 'vh360_post', // Only community posts, NOT videohub360 post type
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => 'vh360_post_media_type',
            'value' => 'video', // Must be a video
            'compare' => '=',
        ),
        array(
            'relation' => 'OR',
            array(
                'key' => 'vh360_post_media_id',
                'compare' => 'EXISTS', // Legacy media attachment.
            ),
            array(
                'key' => '_vh360_studio_video_asset_id',
                'compare' => 'EXISTS', // Managed Studio asset.
            ),
        ),
    ),
);

// Apply sorting
switch ($sort_by) {
    case 'views':
        // Community posts may not have view counts
        // Fall back to date sorting
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
    case 'oldest':
        $args['orderby'] = 'date';
        $args['order'] = 'ASC';
        break;
    default: // 'latest'
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
}

// Query user's videos
$user_videos = new WP_Query($args);
?>

<div class="vh360-profile-videos">
    <div class="vh360-profile-videos-header">
        <h2 class="vh360-profile-section-title"><?php esc_html_e('Videos', 'videohub360-theme'); ?></h2>
        
        <!-- Filter Options -->
        <?php if ($user_videos->have_posts()) : ?>
            <div class="vh360-profile-video-filters">
                <label for="vh360-video-sort" class="screen-reader-text"><?php esc_html_e('Sort videos by', 'videohub360-theme'); ?></label>
                <select id="vh360-video-sort" class="vh360-video-sort-select">
                    <option value="<?php echo esc_url(remove_query_arg('sort')); ?>" <?php selected($sort_by, 'latest'); ?>>
                        <?php esc_html_e('Latest', 'videohub360-theme'); ?>
                    </option>
                    <option value="<?php echo esc_url(add_query_arg('sort', 'views')); ?>" <?php selected($sort_by, 'views'); ?>>
                        <?php esc_html_e('Most Viewed', 'videohub360-theme'); ?>
                    </option>
                    <option value="<?php echo esc_url(add_query_arg('sort', 'oldest')); ?>" <?php selected($sort_by, 'oldest'); ?>>
                        <?php esc_html_e('Oldest', 'videohub360-theme'); ?>
                    </option>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($user_videos->have_posts()) : ?>
        <!-- Video Grid -->
        <div class="vh360-profile-video-grid">
            <?php
            while ($user_videos->have_posts()) :
                $user_videos->the_post();
                $post_obj = get_post();
                
                // Render community post with video
                // This will show the post content + video attachment
                vh360_render_community_post($post_obj, true);
            endwhile;
            wp_reset_postdata();
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($user_videos->max_num_pages > 1) : ?>
            <div class="vh360-profile-pagination">
                <?php
                // Build query args to preserve sort parameter
                $query_args = array();
                if (!empty($sort_by) && $sort_by !== 'latest') {
                    $query_args['sort'] = $sort_by;
                }
                
                // Get pagination arguments and display
                $pagination_args = vh360_get_author_pagination_args(
                    $author_id,
                    $paged,
                    $user_videos->max_num_pages,
                    $query_args
                );
                
                echo paginate_links($pagination_args);
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div class="vh360-profile-videos-empty">
            <div class="vh360-empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                </svg>
                <h3><?php esc_html_e('No videos yet', 'videohub360-theme'); ?></h3>
                <p>
                    <?php
                    if (vh360_user_can_edit_profile($author_id)) {
                        esc_html_e('Start creating and sharing your videos with the community!', 'videohub360-theme');
                    } else {
                        /* translators: Message shown when viewing another user's profile with no videos */
                        esc_html_e('This user has not uploaded any videos yet.', 'videohub360-theme');
                    }
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
