<?php
/**
 * Channel Videos Grid Template Part
 *
 * Displays channel videos in a grid layout with sorting options.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get sorting parameter
$sort = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'latest';
$valid_sorts = array('latest', 'views', 'oldest');
if (!in_array($sort, $valid_sorts, true)) {
    $sort = 'latest';
}

// Get current page for pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Build query args
$args = array(
    'post_type' => 'videohub360',
    'author' => $author_id,
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'meta_query' => array(
        'relation' => 'OR',
        array('key' => '_vh360_context', 'compare' => 'NOT EXISTS'),
        array('key' => '_vh360_context', 'value' => 'live_room', 'compare' => '!=')
    )
);

// Apply sorting
switch ($sort) {
    case 'views':
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_videohub360_post_views_count';
        $args['order'] = 'DESC';
        break;
    case 'oldest':
        $args['orderby'] = 'date';
        $args['order'] = 'ASC';
        break;
    case 'latest':
    default:
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        break;
}

// Execute query
$videos_query = new WP_Query($args);

// Get base author URL for sorting links
$author_url = add_query_arg('tab', 'videos', get_author_posts_url($author_id));
?>

<div class="vh360-channel-videos-section">
    
    <!-- Sorting Controls -->
    <div class="vh360-channel-videos-header">
        <h2 class="vh360-channel-section-title"><?php esc_html_e('Videos', 'videohub360-theme'); ?></h2>
        
        <div class="vh360-channel-sort">
            <label for="vh360-video-sort" class="vh360-channel-sort-label">
                <?php esc_html_e('Sort by:', 'videohub360-theme'); ?>
            </label>
            <select id="vh360-video-sort" class="vh360-channel-sort-select" data-author-url="<?php echo esc_url($author_url); ?>">
                <option value="latest" <?php selected($sort, 'latest'); ?>>
                    <?php esc_html_e('Latest', 'videohub360-theme'); ?>
                </option>
                <option value="views" <?php selected($sort, 'views'); ?>>
                    <?php esc_html_e('Most Viewed', 'videohub360-theme'); ?>
                </option>
                <option value="oldest" <?php selected($sort, 'oldest'); ?>>
                    <?php esc_html_e('Oldest', 'videohub360-theme'); ?>
                </option>
            </select>
            <script>
            (function() {
                var select = document.getElementById('vh360-video-sort');
                if (select) {
                    select.addEventListener('change', function() {
                        var baseUrl = this.getAttribute('data-author-url');
                        window.location.href = baseUrl + '&sort=' + this.value;
                    });
                }
            })();
            </script>
        </div>
    </div>

    <?php if ($videos_query->have_posts()) : ?>
        <!-- Videos Grid -->
        <div class="vh360-videos-grid">
            <?php while ($videos_query->have_posts()) : $videos_query->the_post(); ?>
                <article class="vh360-video-card">
                    <a href="<?php the_permalink(); ?>" class="vh360-video-card-link">
                        
                        <!-- Thumbnail -->
                        <div class="vh360-video-thumbnail">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('videohub360-video-thumb'); ?>
                            <?php else : ?>
                                <div class="vh360-video-thumbnail-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Duration Badge -->
                            <?php
                            $duration = get_post_meta(get_the_ID(), '_videohub360_duration', true);
                            if ($duration) :
                                $formatted_duration = vh360_get_video_duration(get_the_ID());
                                if ($formatted_duration) :
                            ?>
                                <span class="vh360-video-duration"><?php echo esc_html($formatted_duration); ?></span>
                            <?php
                                endif;
                            endif;
                            ?>
                        </div>

                        <!-- Video Info -->
                        <div class="vh360-video-info">
                            <h3 class="vh360-video-title"><?php the_title(); ?></h3>
                            
                            <div class="vh360-video-meta">
                                <?php
                                $views = vh360_get_video_views(get_the_ID());
                                $time_ago = human_time_diff(get_the_time('U'), current_time('timestamp'));
                                ?>
                                <span class="vh360-video-views">
                                    <?php
                                    /* translators: %s: Number of views */
                                    printf(esc_html(_n('%s view', '%s views', $views, 'videohub360-theme')), number_format_i18n($views));
                                    ?>
                                </span>
                                <span class="vh360-video-meta-separator">•</span>
                                <span class="vh360-video-date">
                                    <?php
                                    /* translators: %s: Time ago */
                                    printf(esc_html__('%s ago', 'videohub360-theme'), $time_ago);
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($videos_query->max_num_pages > 1) : ?>
            <div class="vh360-channel-pagination">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $videos_query->max_num_pages,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'videohub360-theme'),
                    'next_text' => esc_html__('Next', 'videohub360-theme') . ' &raquo;',
                    'type' => 'list',
                    'end_size' => 2,
                    'mid_size' => 2,
                ));
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div class="vh360-channel-empty-state">
            <div class="vh360-empty-icon">📹</div>
            <h3 class="vh360-empty-title"><?php esc_html_e('No videos yet', 'videohub360-theme'); ?></h3>
            <p class="vh360-empty-description">
                <?php esc_html_e('This channel hasn\'t uploaded any videos.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

</div>
