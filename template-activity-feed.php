<?php
/**
 * Template Name: Activity Feed
 *
 * Displays the community feed and composer in a unified stream. This template replaces
 * the previous separate composer and feed blocks and removes the legacy activity
 * filters and stream.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get activity options from admin settings
$activity_options = get_option('vh360_activity_options', array());
$activity_defaults = array(
    'enable_tracking' => true,
    'track_types' => array('video_upload', 'new_member', 'profile_update', 'milestone'),
    'retention_days' => 30,
    'per_page' => 20,
);
$activity_options = wp_parse_args($activity_options, $activity_defaults);

// Check if activity tracking is enabled
if (!$activity_options['enable_tracking']) {
    // Redirect to home if activity tracking is disabled
    wp_safe_redirect(home_url('/'), 302);
    exit;
}

$per_page    = absint($activity_options['per_page']);
$track_types = $activity_options['track_types'];

get_header();

// Header visibility and content from customizer
$vh360_show_header       = (bool) get_theme_mod('vh360_show_activity_header', 1);
$vh360_show_header_stats = (bool) get_theme_mod('vh360_show_activity_header_stats', true);
$vh360_header_title      = get_theme_mod('vh360_activity_header_title', __('Community Activity', 'videohub360-theme'));
$vh360_header_desc       = get_theme_mod('vh360_activity_header_description', __('Stay up to date with what’s happening in the community', 'videohub360-theme'));
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-activity-feed">

        <!-- Page Header -->
        <?php if ($vh360_show_header) : ?>
        <header class="vh360-activity-header">
            <div class="vh360-container">
                <h1 class="vh360-activity-title">
                    <?php echo esc_html($vh360_header_title); ?>
                </h1>
                <p class="vh360-activity-description">
                    <?php echo esc_html($vh360_header_desc); ?>
                </p>

                <!-- Activity Stats -->
                <?php if ($vh360_show_header_stats) : ?>
                <div class="vh360-activity-stats">
                    <div class="vh360-activity-stat">
                        <span class="vh360-stat-value"><?php echo esc_html(number_format_i18n(vh360_get_activity_count())); ?></span>
                        <span class="vh360-stat-label"><?php esc_html_e('Activities', 'videohub360-theme'); ?></span>
                    </div>
                    <div class="vh360-activity-stat">
                        <span class="vh360-stat-value"><?php echo esc_html(number_format_i18n(vh360_get_activity_count('video_upload'))); ?></span>
                        <span class="vh360-stat-label"><?php esc_html_e('Videos', 'videohub360-theme'); ?></span>
                    </div>
                    <div class="vh360-activity-stat">
                        <span class="vh360-stat-value"><?php echo esc_html(number_format_i18n(vh360_get_activity_count('new_member'))); ?></span>
                        <span class="vh360-stat-label"><?php esc_html_e('New Members', 'videohub360-theme'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>
        <?php endif; ?>

        <!-- Membership Check -->
        <?php
        // Check if activity feed requires membership
        if (function_exists('vh360_can_access_membership_feature') && !vh360_can_access_membership_feature('activity_feed', get_current_user_id())) {
            ?>
            <div class="vh360-container">
                <?php echo vh360_render_membership_gate(); ?>
            </div>
            <?php
            get_footer();
            exit;
        }
        ?>

        <!-- Unified Community Feed (Composer + Posts + Sidebar) -->
        <div class="vh360-community-feed">
            <div class="vh360-container">
            <?php
            /*
             * Determine which feed to display. The "feed" query parameter
             * allows visitors to switch between their personalised feed
             * (posts from people they follow) and the global explore feed.
             * Default to "explore" so new users and users who don't follow anyone see content.
             * Users can still click "My Feed" tab to see only posts from people they follow.
             */
            $current_feed = ( isset($_GET['feed_view']) && $_GET['feed_view'] === 'my-feed' ) ? 'my-feed' : 'explore';

            // Build arguments for retrieving community posts.
            $feed_args = array(
                'posts_per_page' => $per_page,
                'paged'          => 1,
            );
            if ( 'my-feed' === $current_feed ) {
                // Restrict posts to authors the current user follows when
                // displaying the personalised feed. Users who are not
                // logged in or not following anyone will simply see all
                // posts.
                $feed_args['following_only'] = true;
            }
            $community_posts = vh360_get_community_posts( $feed_args );

            ?>

            <div class="vh360-activity-layout">
                <div class="vh360-activity-main">
                <!-- Feed tab navigation -->
                <div class="vh360-feed-tabs">
                    <a href="<?php echo esc_url( add_query_arg( 'feed_view', 'my-feed' ) ); ?>" class="vh360-feed-tab <?php echo ( 'my-feed' === $current_feed ) ? 'active' : ''; ?>">
                        <?php esc_html_e( 'My Feed', 'videohub360-theme' ); ?>
                    </a>
                    <a href="<?php echo esc_url( remove_query_arg( array( 'feed_view', 'paged' ) ) ); ?>" class="vh360-feed-tab <?php echo ( 'explore' === $current_feed ) ? 'active' : ''; ?>">
                        <?php esc_html_e( 'Explore', 'videohub360-theme' ); ?>
                    </a>
                </div>
                
                <div class="vh360-feed-main">
                    <?php if ( is_user_logged_in() ) :
                        // Get upload settings for the composer
                        $upload_settings = vh360_get_community_upload_settings();
                        $photos_enabled = ! empty( $upload_settings['enable_photos'] );
                        $videos_enabled = ! empty( $upload_settings['enable_videos'] );

                        // Check for upload error messages
                        $upload_error = get_transient( 'vh360_upload_error_' . get_current_user_id() );
                        if ( $upload_error ) {
                            delete_transient( 'vh360_upload_error_' . get_current_user_id() );
                        }

                        // Build accept attribute based on settings
                        $accept_types = array();
                        if ( $photos_enabled ) {
                            $accept_types[] = 'image/*';
                        }
                        if ( $videos_enabled ) {
                            $accept_types[] = 'video/*';
                        }
                        $accept_attr = implode( ',', $accept_types );

                        // Determine label text
                        $media_label_text = '';
                        if ( $photos_enabled && $videos_enabled ) {
                            $media_label_text = __( 'Photo/Video', 'videohub360-theme' );
                        } elseif ( $photos_enabled ) {
                            $media_label_text = __( 'Photo', 'videohub360-theme' );
                        } elseif ( $videos_enabled ) {
                            $media_label_text = __( 'Video', 'videohub360-theme' );
                        }
                    ?>
                        <!-- Composer as first feed item -->
                        <article class="vh360-community-post vh360-community-post--composer">
                            <div class="vh360-community-avatar">
                                <?php echo get_avatar( get_current_user_id(), 40 ); ?>
                            </div>
                            <div class="vh360-community-content">
                                <?php if ( $upload_error ) : ?>
                                    <div class="vh360-upload-error" role="alert">
                                        <?php echo esc_html( $upload_error ); ?>
                                    </div>
                                <?php endif; ?>
                                <form id="vh360-post-form" class="vh360-post-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <?php wp_nonce_field( 'vh360_create_post', 'vh360_post_nonce' ); ?>
                                    <input type="hidden" name="action" value="vh360_create_post">

                                    <div class="vh360-composer-input-row">
                                        <textarea name="vh360_post_content"
                                                  class="vh360-post-textarea"
                                                  placeholder="<?php esc_attr_e( 'What\'s on your mind?', 'videohub360-theme' ); ?>"></textarea>
                                    </div>

                                    <?php if ( $photos_enabled || $videos_enabled ) : ?>
                                    <div class="vh360-upload-preview" style="display: none;">
                                        <div class="vh360-preview-image"></div>
                                        <button type="button" class="vh360-remove-upload" aria-label="<?php esc_attr_e( 'Remove upload', 'videohub360-theme' ); ?>">&times;</button>
                                        <span class="vh360-file-name"></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="vh360-post-actions vh360-composer-actions">
                                        <?php if ( $photos_enabled || $videos_enabled ) : ?>
                                        <label class="vh360-post-media-label">
                                            <input type="file"
                                                   name="vh360_post_media"
                                                   id="vh360-post-media"
                                                   accept="<?php echo esc_attr( $accept_attr ); ?>"
                                                   data-max-photo-size="<?php echo esc_attr( absint( $upload_settings['photo_max_size'] ) * 1024 * 1024 ); ?>"
                                                   data-max-video-size="<?php echo esc_attr( absint( $upload_settings['video_max_size'] ) * 1024 * 1024 ); ?>"
                                                   class="vh360-media-upload" />
                                            <span><?php echo esc_html( $media_label_text ); ?></span>
                                        </label>
                                        <?php endif; ?>
                                        <button type="submit" class="vh360-post-submit">
                                            <?php esc_html_e( 'Post', 'videohub360-theme' ); ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    <?php endif; ?>

                    <?php
                    // Render the list of posts or display an empty message.
                    if ( ! empty( $community_posts ) ) {
                        foreach ( $community_posts as $community_post ) {
                            vh360_render_community_post( $community_post );
                        }
                    } else {
                        echo '<p class="vh360-empty-community-feed">' . esc_html__( 'No posts yet. Be the first to share an update!', 'videohub360-theme' ) . '</p>';
                    }
                    ?>
                </div>

                <!-- Right Sidebar containing trending topics, trending posts and recommended profiles -->
                </div> <!-- /.vh360-activity-main -->
                
                <aside class="vh360-activity-sidebar vh360-activity-sidebar--right">
                    <?php get_template_part('template-parts/activity/sidebar-right'); ?>
                </aside>
            </div> <!-- /.vh360-activity-layout -->
            </div> <!-- /.vh360-container -->
        </div> <!-- /.vh360-community-feed -->

        <!-- Mobile Compose FAB (Floating Action Button) -->
        <?php if ( is_user_logged_in() ) : ?>
        <button class="vh360-mobile-compose-fab" aria-label="<?php esc_attr_e( 'Create new post', 'videohub360-theme' ); ?>">
            +
        </button>
        
        <!-- Mobile Compose Modal -->
        <div class="vh360-mobile-compose-modal">
            <div class="vh360-mobile-compose-sheet">
                <div class="vh360-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;"><?php esc_html_e( 'Create Post', 'videohub360-theme' ); ?></h3>
                    <button class="vh360-modal-close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" aria-label="<?php esc_attr_e( 'Close', 'videohub360-theme' ); ?>">&times;</button>
                </div>
                <?php
                // Get upload settings for the mobile composer
                $upload_settings = vh360_get_community_upload_settings();
                $photos_enabled = ! empty( $upload_settings['enable_photos'] );
                $videos_enabled = ! empty( $upload_settings['enable_videos'] );

                // Build accept attribute based on settings
                $accept_types = array();
                if ( $photos_enabled ) {
                    $accept_types[] = 'image/*';
                }
                if ( $videos_enabled ) {
                    $accept_types[] = 'video/*';
                }
                $accept_attr = implode( ',', $accept_types );

                // Determine label text
                $media_label_text = '';
                if ( $photos_enabled && $videos_enabled ) {
                    $media_label_text = __( 'Photo/Video', 'videohub360-theme' );
                } elseif ( $photos_enabled ) {
                    $media_label_text = __( 'Photo', 'videohub360-theme' );
                } elseif ( $videos_enabled ) {
                    $media_label_text = __( 'Video', 'videohub360-theme' );
                }
                ?>
                <article class="vh360-community-post vh360-community-post--composer" style="display: flex !important;">
                    <div class="vh360-community-avatar">
                        <?php echo get_avatar( get_current_user_id(), 40 ); ?>
                    </div>
                    <div class="vh360-community-content">
                        <form id="vh360-mobile-post-form" class="vh360-post-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'vh360_create_post', 'vh360_post_nonce' ); ?>
                            <input type="hidden" name="action" value="vh360_create_post">

                            <div class="vh360-composer-input-row">
                                <textarea name="vh360_post_content"
                                          class="vh360-post-textarea"
                                          placeholder="<?php esc_attr_e( 'What\'s on your mind?', 'videohub360-theme' ); ?>"></textarea>
                            </div>

                            <?php if ( $photos_enabled || $videos_enabled ) : ?>
                            <div class="vh360-upload-preview" style="display: none;">
                                <div class="vh360-preview-image"></div>
                                <button type="button" class="vh360-remove-upload" aria-label="<?php esc_attr_e( 'Remove upload', 'videohub360-theme' ); ?>">&times;</button>
                                <span class="vh360-file-name"></span>
                            </div>
                            <?php endif; ?>

                            <div class="vh360-post-actions vh360-composer-actions">
                                <?php if ( $photos_enabled || $videos_enabled ) : ?>
                                <label class="vh360-post-media-label">
                                    <input type="file"
                                           name="vh360_post_media"
                                           id="vh360-mobile-post-media"
                                           accept="<?php echo esc_attr( $accept_attr ); ?>"
                                           data-max-photo-size="<?php echo esc_attr( absint( $upload_settings['photo_max_size'] ) * 1024 * 1024 ); ?>"
                                           data-max-video-size="<?php echo esc_attr( absint( $upload_settings['video_max_size'] ) * 1024 * 1024 ); ?>"
                                           class="vh360-media-upload" />
                                    <span><?php echo esc_html( $media_label_text ); ?></span>
                                </label>
                                <?php endif; ?>
                                <button type="submit" class="vh360-post-submit">
                                    <?php esc_html_e( 'Post', 'videohub360-theme' ); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </article>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php
get_footer();