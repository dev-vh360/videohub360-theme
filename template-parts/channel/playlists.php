<?php
/**
 * Channel Playlists Template Part
 *
 * Displays channel playlists (series taxonomy) in a grid layout.
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

// Get all videos by this author (excluding live rooms)
$author_videos = get_posts(array(
    'author' => $author_id,
    'post_type' => 'videohub360',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => array(
        'relation' => 'OR',
        array('key' => '_vh360_context', 'compare' => 'NOT EXISTS'),
        array('key' => '_vh360_context', 'value' => 'live_room', 'compare' => '!=')
    )
));

// Get unique series terms from these videos
$series_terms = array();
if (!empty($author_videos)) {
    foreach ($author_videos as $video_id) {
        $terms = wp_get_post_terms($video_id, 'videohub360_series', array('fields' => 'all'));
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if (!isset($series_terms[$term->term_id])) {
                    $series_terms[$term->term_id] = array(
                        'term' => $term,
                        'video_ids' => array(),
                    );
                }
                $series_terms[$term->term_id]['video_ids'][] = $video_id;
            }
        }
    }
}
?>

<div class="vh360-channel-playlists-section">
    
    <div class="vh360-channel-section-header">
        <h2 class="vh360-channel-section-title"><?php esc_html_e('Playlists', 'videohub360-theme'); ?></h2>
    </div>

    <?php if (!empty($series_terms)) : ?>
        <!-- Playlists Grid -->
        <div class="vh360-playlists-grid">
            <?php foreach ($series_terms as $series_data) : 
                $term = $series_data['term'];
                $video_count = count($series_data['video_ids']);
                
                // Get first video's thumbnail as playlist thumbnail
                $first_video_id = reset($series_data['video_ids']);
                $thumbnail_url = get_the_post_thumbnail_url($first_video_id, 'videohub360-video-thumb');
                
                // Get series archive URL
                $series_url = get_term_link($term, 'videohub360_series');
                if (is_wp_error($series_url)) {
                    continue;
                }
            ?>
                <article class="vh360-playlist-card">
                    <a href="<?php echo esc_url($series_url); ?>" class="vh360-playlist-card-link">
                        
                        <!-- Playlist Thumbnail -->
                        <div class="vh360-playlist-thumbnail">
                            <?php if ($thumbnail_url) : ?>
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
                            <?php else : ?>
                                <div class="vh360-playlist-thumbnail-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Video Count Overlay -->
                            <div class="vh360-playlist-overlay">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <span class="vh360-playlist-count">
                                    <?php
                                    /* translators: %s: Number of videos */
                                    printf(esc_html(_n('%s video', '%s videos', $video_count, 'videohub360-theme')), number_format_i18n($video_count));
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Playlist Info -->
                        <div class="vh360-playlist-info">
                            <h3 class="vh360-playlist-title"><?php echo esc_html($term->name); ?></h3>
                            
                            <?php if (!empty($term->description)) : ?>
                                <p class="vh360-playlist-description">
                                    <?php echo esc_html(wp_trim_words($term->description, 15)); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <!-- Empty State -->
        <div class="vh360-channel-empty-state">
            <div class="vh360-empty-icon">📋</div>
            <h3 class="vh360-empty-title"><?php esc_html_e('No playlists yet', 'videohub360-theme'); ?></h3>
            <p class="vh360-empty-description">
                <?php esc_html_e('This channel hasn\'t created any playlists.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php endif; ?>

</div>
