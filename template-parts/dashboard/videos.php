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

$vh360_my_items_label = $vh360_items_label;
$vh360_add_item_label = $vh360_is_lesson_context
    ? __( 'Add Lesson', 'videohub360-theme' )
    : __( 'Add Video', 'videohub360-theme' );


$vh360_is_licensed = ( function_exists('vh360_theme_is_license_valid') ? vh360_theme_is_license_valid() : ( function_exists('videohub360_license_is_valid') && videohub360_license_is_valid() ) );
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');
// Get filter parameters
$allowed_statuses = array( 'publish', 'draft', 'all' );
$status = isset( $_GET['video_status'] ) ? sanitize_key( wp_unslash( $_GET['video_status'] ) ) : 'publish';

if ( ! in_array( $status, $allowed_statuses, true ) ) {
    $status = 'publish';
}

$query_post_status = 'all' === $status ? array( 'publish', 'draft' ) : $status;
$search = isset( $_GET['video_search'] ) ? sanitize_text_field( wp_unslash( $_GET['video_search'] ) ) : '';
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

$vh360_status_filter_labels = array(
    'publish' => sprintf(
        /* translators: %s = plural content label */
        __( 'Published %s', 'videohub360-theme' ),
        $vh360_items_label
    ),
    'draft'   => sprintf(
        /* translators: %s = plural content label */
        __( 'Draft %s', 'videohub360-theme' ),
        $vh360_items_label
    ),
    'all'     => sprintf(
        /* translators: %s = plural content label */
        __( 'All %s', 'videohub360-theme' ),
        $vh360_items_label
    ),
);

$vh360_videos_base_url = function_exists( 'vh360_get_dashboard_tab_url' )
    ? vh360_get_dashboard_tab_url( 'videos' )
    : add_query_arg( 'tab', 'videos', remove_query_arg( array( 'video_status', 'paged' ) ) );

$vh360_status_filter_urls = array(
    'publish' => add_query_arg( 'video_status', 'publish', $vh360_videos_base_url ),
    'draft'   => add_query_arg( 'video_status', 'draft', $vh360_videos_base_url ),
    'all'     => add_query_arg( 'video_status', 'all', $vh360_videos_base_url ),
);

// Build query args
$args = array(
    'post_type' => 'videohub360',
    'author' => $current_user_id,
    'post_status' => $query_post_status,
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
?>

<div class="vh360-dashboard-videos">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php echo esc_html( $vh360_my_items_label ); ?></h1>
        <a href="<?php echo esc_url( function_exists( 'vh360_get_dashboard_tab_url' ) ? vh360_get_dashboard_tab_url( 'create-video' ) : add_query_arg( 'tab', 'create-video' ) ); ?>" class="vh360-dashboard-btn vh360-dashboard-tab <?php echo ! $vh360_is_licensed ? 'vh360-locked' : ''; ?>" data-tab="create-video" aria-disabled="<?php echo ! $vh360_is_licensed ? 'true' : 'false'; ?>" title="<?php echo ! $vh360_is_licensed ? esc_attr( $vh360_is_lesson_context ? __( 'Activate your license to add lessons.', 'videohub360-theme' ) : __( 'Activate your license to add videos.', 'videohub360-theme' ) ) : ''; ?>">
            <?php echo esc_html( $vh360_add_item_label ); ?>
        </a>
    </div>

    <div class="vh360-dashboard-filters">
        <div class="vh360-dashboard-filter-tabs vh360-dashboard-status-filters" aria-label="<?php echo esc_attr( sprintf( __( '%s status filters', 'videohub360-theme' ), $vh360_items_label ) ); ?>">
            <?php foreach ( $vh360_status_filter_labels as $filter_status => $filter_label ) : ?>
                <a
                    href="<?php echo esc_url( $vh360_status_filter_urls[ $filter_status ] ); ?>"
                    class="vh360-dashboard-filter-tab <?php echo esc_attr( $filter_status === $status ? 'active' : '' ); ?>"
                    <?php echo $filter_status === $status ? 'aria-current="page"' : ''; ?>
                >
                    <?php echo esc_html( $filter_label ); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <input type="hidden" id="vh360-video-status" value="<?php echo esc_attr( $status ); ?>">
        <input type="hidden" id="vh360-video-search" value="<?php echo esc_attr( $search ); ?>">
    </div>

    <?php if ( ! $vh360_is_licensed ) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning vh360-license-softlock-notice">
            <?php
            echo esc_html(
                $vh360_is_lesson_context
                    ? __( 'Your VideoHub360 license is inactive. Activate your license to create new lessons.', 'videohub360-theme' )
                    : __( 'Your VideoHub360 license is inactive. Activate your license to upload new videos.', 'videohub360-theme' )
            );
            ?>
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
                                    $vh360_post_status = get_post_status();
                                    if ( in_array( $vh360_post_status, array( 'publish', 'draft' ), true ) ) :
                                        $vh360_status_label = 'publish' === $vh360_post_status
                                            ? __( 'Published', 'videohub360-theme' )
                                            : __( 'Draft', 'videohub360-theme' );
                                    ?>
                                        <span class="vh360-video-status-badge vh360-video-status-badge-<?php echo esc_attr( $vh360_post_status ); ?>">
                                            <?php echo esc_html( $vh360_status_label ); ?>
                                        </span>
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
                            <a href="<?php echo esc_url( function_exists( 'vh360_get_dashboard_tab_url' ) ? vh360_get_dashboard_tab_url( 'create-video' ) : add_query_arg( 'tab', 'create-video' ) ); ?>" class="vh360-video-action vh360-edit-video" data-video-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Edit', 'videohub360-theme'); ?>">
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
                    if ( 'draft' === $status ) {
                        echo esc_html(
                            $vh360_is_lesson_context
                                ? __( 'You do not have any draft lessons yet.', 'videohub360-theme' )
                                : __( 'You do not have any draft videos yet.', 'videohub360-theme' )
                        );
                    } elseif ( 'all' === $status ) {
                        echo esc_html(
                            $vh360_is_lesson_context
                                ? __( 'You have not created any lessons yet.', 'videohub360-theme' )
                                : __( 'You have not created any videos yet.', 'videohub360-theme' )
                        );
                    } else {
                        echo esc_html(
                            $vh360_is_lesson_context
                                ? __( 'You have not published any lessons yet.', 'videohub360-theme' )
                                : __( 'You have not published any videos yet.', 'videohub360-theme' )
                        );
                    }
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
                            ? __( 'Add your first lesson to get started!', 'videohub360-theme' )
                            : __( 'Add your first video to get started!', 'videohub360-theme' )
                    );
                }
                ?>
            </p>
            <?php if (empty($search)) : ?>
                <a href="<?php echo esc_url( function_exists( 'vh360_get_dashboard_tab_url' ) ? vh360_get_dashboard_tab_url( 'create-video' ) : add_query_arg( 'tab', 'create-video' ) ); ?>" class="vh360-dashboard-btn vh360-dashboard-tab <?php echo !$vh360_is_licensed ? 'vh360-locked' : ''; ?>" data-tab="create-video" aria-disabled="<?php echo !$vh360_is_licensed ? 'true' : 'false'; ?>" title="<?php echo !$vh360_is_licensed ? esc_attr( $vh360_is_lesson_context ? __( 'Activate your license to add lessons.', 'videohub360-theme' ) : __( 'Activate your license to add videos.', 'videohub360-theme' ) ) : ''; ?>">
                    <?php echo esc_html(
                        $vh360_is_lesson_context
                            ? __( 'Add Lesson', 'videohub360-theme' )
                            : __( 'Add Video', 'videohub360-theme' )
                    ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
    
</div><!-- .vh360-dashboard-videos -->
