<?php
/**
 * Dashboard My Playlists Template
 *
 * Displays user's playlists with management options
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$playlists = VideoHub360_Playlists::get_user_playlists($current_user_id);

// Handle viewing a specific playlist
$viewing_playlist_id = isset($_GET['playlist_id']) ? absint($_GET['playlist_id']) : 0;
$viewing_playlist = null;
$playlist_videos = array();

if ($viewing_playlist_id) {
    $viewing_playlist = VideoHub360_Playlists::get_playlist($viewing_playlist_id, $current_user_id);
    if ($viewing_playlist) {
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $video_ids = VideoHub360_Playlists::get_playlist_videos($viewing_playlist_id, $paged, $per_page);
        foreach ($video_ids as $video_id) {
            $video = get_post($video_id);
            if ($video && $video->post_type === 'videohub360') {
                $playlist_videos[] = $video;
            }
        }
    }
}

?>

<div class="vh360-dashboard-section vh360-playlists-section">
    
    <?php if ($viewing_playlist) : ?>
        <!-- Viewing a specific playlist -->
        <div class="vh360-dashboard-header">
            <a href="?tab=playlists" class="vh360-back-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <?php esc_html_e('Back to Playlists', 'videohub360-theme'); ?>
            </a>
            <div class="vh360-playlist-header-info">
                <h2><?php echo esc_html($viewing_playlist['title']); ?></h2>
                <span class="vh360-playlist-count">
                    <?php echo esc_html($viewing_playlist['video_count']); ?> 
                    <?php echo _n('video', 'videos', $viewing_playlist['video_count'], 'videohub360-theme'); ?>
                </span>
            </div>
            <button class="vh360-btn vh360-btn-danger vh360-delete-playlist-btn" data-playlist-id="<?php echo esc_attr($viewing_playlist['id']); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
                <?php esc_html_e('Delete Playlist', 'videohub360-theme'); ?>
            </button>
        </div>

        <?php if (!empty($viewing_playlist['description'])) : ?>
            <div class="vh360-playlist-description">
                <p><?php echo esc_html($viewing_playlist['description']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($playlist_videos)) : ?>
            <div class="vh360-dashboard-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="9" x2="15" y2="9"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <h3><?php esc_html_e('This playlist is empty', 'videohub360-theme'); ?></h3>
                <p><?php esc_html_e('Add videos to this playlist by clicking the Save button on any video', 'videohub360-theme'); ?></p>
            </div>
        <?php else : ?>
            <div class="vh360-playlist-videos">
                <?php foreach ($playlist_videos as $index => $video) :
                    $video_id = $video->ID;
                    $thumbnail = get_the_post_thumbnail_url($video_id, 'medium');
                    $views = get_post_meta($video_id, '_videohub360_post_views_count', true);
                    $views = $views ? $views : 0;
                    $author = get_userdata($video->post_author);
                    $date = human_time_diff(get_the_time('U', $video_id), current_time('timestamp'));
                    ?>
                    <div class="vh360-playlist-video-item">
                        <span class="vh360-playlist-video-number"><?php echo esc_html($index + 1); ?></span>
                        <a href="<?php echo esc_url(get_permalink($video_id)); ?>" class="vh360-playlist-video-thumbnail">
                            <?php if ($thumbnail) : ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($video_id)); ?>">
                            <?php else : ?>
                                <div class="vh360-no-thumbnail">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="vh360-playlist-video-info">
                            <h4 class="vh360-playlist-video-title">
                                <a href="<?php echo esc_url(get_permalink($video_id)); ?>">
                                    <?php echo esc_html(get_the_title($video_id)); ?>
                                </a>
                            </h4>
                            <div class="vh360-playlist-video-meta">
                                <span><?php echo esc_html($author->display_name); ?></span>
                                <span><?php echo esc_html(number_format($views)); ?> <?php esc_html_e('views', 'videohub360-theme'); ?></span>
                            </div>
                        </div>
                        <button class="vh360-remove-from-playlist-btn" 
                                data-playlist-id="<?php echo esc_attr($viewing_playlist_id); ?>"
                                data-video-id="<?php echo esc_attr($video_id); ?>"
                                title="<?php esc_attr_e('Remove from playlist', 'videohub360-theme'); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Playlist list view -->
        <div class="vh360-dashboard-header">
            <h2><?php esc_html_e('My Playlists', 'videohub360-theme'); ?></h2>
            <?php if (!empty($playlists)) : ?>
                <span class="vh360-dashboard-count"><?php echo esc_html(count($playlists)); ?> <?php echo _n('playlist', 'playlists', count($playlists), 'videohub360-theme'); ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($playlists)) : ?>
            <div class="vh360-dashboard-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                </svg>
                <h3><?php esc_html_e('No playlists yet', 'videohub360-theme'); ?></h3>
                <p><?php esc_html_e('Create playlists to organize your favorite videos', 'videohub360-theme'); ?></p>
                <button class="vh360-btn vh360-btn-primary vh360-create-playlist-dashboard-btn">
                    <?php esc_html_e('Create Your First Playlist', 'videohub360-theme'); ?>
                </button>
            </div>
        <?php else : ?>
            <div class="vh360-playlists-grid">
                <?php foreach ($playlists as $playlist) :
                    $playlist_id = $playlist['id'];
                    $video_ids = VideoHub360_Playlists::get_playlist_videos($playlist_id, 1, 4);
                    ?>
                    <div class="vh360-playlist-card">
                        <a href="?tab=playlists&playlist_id=<?php echo esc_attr($playlist_id); ?>" class="vh360-playlist-card-thumbnail">
                            <?php if (!empty($video_ids)) :
                                $first_video_thumbnail = get_the_post_thumbnail_url($video_ids[0], 'medium');
                                if ($first_video_thumbnail) : ?>
                                    <img src="<?php echo esc_url($first_video_thumbnail); ?>" alt="<?php echo esc_attr($playlist['title']); ?>">
                                <?php else : ?>
                                    <div class="vh360-no-thumbnail">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="9" y1="9" x2="15" y2="9"></line>
                                            <line x1="9" y1="15" x2="15" y2="15"></line>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            <?php else : ?>
                                <div class="vh360-no-thumbnail">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="9" y1="9" x2="15" y2="9"></line>
                                        <line x1="9" y1="15" x2="15" y2="15"></line>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span class="vh360-playlist-card-overlay">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="9" y1="9" x2="15" y2="9" stroke="black" stroke-width="2"></line>
                                    <line x1="9" y1="15" x2="15" y2="15" stroke="black" stroke-width="2"></line>
                                </svg>
                                <span><?php echo esc_html($playlist['video_count']); ?> <?php echo _n('video', 'videos', $playlist['video_count'], 'videohub360-theme'); ?></span>
                            </span>
                        </a>
                        <div class="vh360-playlist-card-info">
                            <h3 class="vh360-playlist-card-title">
                                <a href="?tab=playlists&playlist_id=<?php echo esc_attr($playlist_id); ?>">
                                    <?php echo esc_html($playlist['title']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($playlist['description'])) : ?>
                                <p class="vh360-playlist-card-description">
                                    <?php echo esc_html(wp_trim_words($playlist['description'], 15)); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Create Playlist Modal (Dashboard Version) -->
<div class="vh360-create-playlist-modal-overlay" id="vh360-create-playlist-dashboard-modal" style="display: none;">
    <div class="vh360-playlist-modal">
        <div class="vh360-playlist-modal-header">
            <h3 class="vh360-playlist-modal-title"><?php esc_html_e('Create New Playlist', 'videohub360'); ?></h3>
            <button class="vh360-playlist-modal-close" id="vh360-create-playlist-dashboard-close">&times;</button>
        </div>
        <div class="vh360-playlist-modal-body">
            <input type="text" id="vh360-dashboard-playlist-title" class="vh360-playlist-input" placeholder="<?php esc_attr_e('Playlist title', 'videohub360-theme'); ?>" maxlength="255">
            <textarea id="vh360-dashboard-playlist-description" class="vh360-playlist-textarea" placeholder="<?php esc_attr_e('Description (optional)', 'videohub360-theme'); ?>" rows="3"></textarea>
            <div class="vh360-create-playlist-actions">
                <button class="vh360-btn vh360-btn-cancel" id="vh360-dashboard-cancel-playlist">
                    <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                </button>
                <button class="vh360-btn vh360-btn-primary" id="vh360-dashboard-submit-playlist">
                    <?php esc_html_e('Create Playlist', 'videohub360-theme'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
