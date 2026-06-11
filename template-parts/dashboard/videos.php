<?php
/**
 * Dashboard Videos Tab
 *
 * Video management grid with edit/delete actions, filters, and pagination.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();

$vh360_is_lesson_context = function_exists( 'vh360_dashboard_uses_lesson_labels' )
    && vh360_dashboard_uses_lesson_labels( $current_user_id );

$vh360_item_label = $vh360_is_lesson_context
    ? ( function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( false ) : __( 'Lesson', 'videohub360-theme' ) )
    : __( 'Video', 'videohub360-theme' );

$vh360_items_label = $vh360_is_lesson_context
    ? ( function_exists( 'vh360_get_lesson_label' ) ? vh360_get_lesson_label( true ) : __( 'Lessons', 'videohub360-theme' ) )
    : __( 'Videos', 'videohub360-theme' );

$vh360_my_items_label = sprintf(
    /* translators: %s = plural content label */
    __( 'My %s', 'videohub360-theme' ),
    $vh360_items_label
);


$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
// Get filter parameters
$status = isset($_GET['video_status']) ? sanitize_text_field($_GET['video_status']) : 'publish';
$search = isset($_GET['video_search']) ? sanitize_text_field($_GET['video_search']) : '';
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

// Build query args
$args = array(
    'post_type' => 'videohub360',
    'author' => $current_user_id,
    'post_status' => $status,
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_vh360_context',
            'compare' => 'NOT EXISTS', // Videos without context meta (regular videos)
        ),
        array(
            'key' => '_vh360_context',
            'value' => 'live_room',
            'compare' => '!=', // Exclude videos marked as live_room
        ),
    ),
);

if (!empty($search)) {
    $args['s'] = $search;
}

$videos_query = new WP_Query($args);
$total_videos = $videos_query->found_posts;

// Get video counts by status
$published_count = count_user_posts($current_user_id, 'videohub360', true);

// Get draft count with direct query for accuracy
global $wpdb;
$draft_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} 
     WHERE post_author = %d 
     AND post_type = 'videohub360' 
     AND post_status = 'draft'",
    $current_user_id
));
?>

<div class="vh360-dashboard-videos">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php echo esc_html( $vh360_my_items_label ); ?></h1>
    </div>

    <?php if ( ! $vh360_is_licensed ) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning vh360-license-softlock-notice">
            <?php echo esc_html__( 'Your VideoHub360 license is inactive. Activate your license to upload new videos.', 'videohub360-theme' ); ?>
            <a href="<?php echo esc_url( $vh360_license_url ); ?>" style="margin-left:8px;">
                <?php esc_html_e( 'Activate License', 'videohub360-theme' ); ?>
            </a>
        </div>
    <?php endif; ?>

<!-- Videos Grid -->
    <?php if ($videos_query->have_posts()) : ?>
        <div class="vh360-videos-grid">
            <?php while ($videos_query->have_posts()) : $videos_query->the_post(); ?>
                <div class="vh360-video-grid-item">
                    <article class="vh360-video-card" data-video-id="<?php the_ID(); ?>">
                        <a href="<?php the_permalink(); ?>" class="vh360-video-card-link">
                            <div class="vh360-video-thumbnail">
                                <div class="vh360-video-thumbnail-wrapper">
                                    <?php
                                    $thumbnail = vh360_get_video_thumbnail(get_the_ID(), 'videohub360-video-thumb');
                                    if ($thumbnail) :
                                    ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="vh360-video-thumbnail-placeholder">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $duration = vh360_get_video_duration(get_the_ID());
                                    if ($duration) :
                                    ?>
                                        <span class="vh360-video-duration"><?php echo esc_html($duration); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="vh360-video-card-body">
                                <h3 class="vh360-video-title"><?php the_title(); ?></h3>
                                
                                <div class="vh360-video-meta">
                                    <span class="vh360-video-views">
                                        <?php echo esc_html(vh360_format_number(vh360_get_video_views(get_the_ID()))); ?> <?php esc_html_e('views', 'videohub360-theme'); ?>
                                    </span>
                                    <span>•</span>
                                    <span class="vh360-video-date">
                                        <?php echo esc_html(get_the_date()); ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Video Actions -->
                        <div class="vh360-video-actions">
                            <a href="#create-video" class="vh360-video-action vh360-edit-video" data-video-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Edit', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <button 
                                class="vh360-video-action vh360-video-delete" 
                                data-video-id="<?php the_ID(); ?>"
                                data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_delete_video_' . get_the_ID())); ?>"
                                title="<?php esc_attr_e('Delete', 'videohub360-theme'); ?>"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </article>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($videos_query->max_num_pages > 1) : ?>
            <div class="vh360-dashboard-pagination">
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $videos_query->max_num_pages,
                    'prev_text' => '←',
                    'next_text' => '→',
                    'type' => 'plain',
                    'add_args' => array(),
                );
                
                // Preserve current filters
                if (!empty($status) && $status !== 'publish') {
                    $pagination_args['add_args']['video_status'] = $status;
                }
                if (!empty($search)) {
                    $pagination_args['add_args']['video_search'] = $search;
                }
                
                echo paginate_links($pagination_args);
                ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="vh360-dashboard-empty">
            <div class="vh360-dashboard-empty-icon">📹</div>
            <p class="vh360-dashboard-empty-title">
                <?php 
                if (!empty($search)) {
                    echo esc_html(
                        $vh360_is_lesson_context
                            ? __( 'No lessons found matching your search.', 'videohub360-theme' )
                            : __( 'No videos found matching your search.', 'videohub360-theme' )
                    );
                } else {
                    echo esc_html(
                        $vh360_is_lesson_context
                            ? __( 'No lessons yet', 'videohub360-theme' )
                            : __( 'No videos yet', 'videohub360-theme' )
                    );
                }
                ?>
            </p>
            <p class="vh360-dashboard-empty-text">
                <?php 
                if (!empty($search)) {
                    esc_html_e('Try adjusting your search terms.', 'videohub360-theme');
                } else {
                    echo esc_html(
                        $vh360_is_lesson_context
                            ? __( 'Create your first lesson to get started!', 'videohub360-theme' )
                            : __( 'Upload your first video to get started!', 'videohub360-theme' )
                    );
                }
                ?>
            </p>
            <?php if (empty($search)) : ?>
                <a href="#create-video" class="vh360-dashboard-btn vh360-dashboard-tab <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" data-tab="create-video" aria-disabled="<?php echo !$vh360_is_licensed ? 'true' : 'false'; ?>" title="<?php echo !$vh360_is_licensed ? esc_attr__('Activate your license to upload new videos.', 'videohub360-theme') : ''; ?>">
                    <?php echo esc_html(
                        $vh360_is_lesson_context
                            ? __( 'Create Lesson', 'videohub360-theme' )
                            : __( 'Upload Video', 'videohub360-theme' )
                    ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
    
</div><!-- .vh360-dashboard-videos -->
