<?php
/**
 * Profile Left Sidebar Rail
 *
 * Static profile information sidebar for desktop two-column layout.
 * Contains bio, stats, photos preview, and social links.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = isset( $author_id ) ? absint( $author_id ) : absint( get_queried_object_id() );

if ( ! $author_id ) {
    return;
}

$author = get_userdata($author_id);
if (!$author) {
    return;
}

// Get user data
$bio = vh360_get_user_bio($author_id);
$social_links = vh360_get_user_social_links($author_id);
$stats = vh360_get_user_stats($author_id);
$video_count = isset($stats['videos']) ? $stats['videos'] : 0;
$total_views = isset($stats['views']) ? $stats['views'] : 0;
$followers = isset($stats['followers']) ? $stats['followers'] : 0;
$following = isset($stats['following']) ? $stats['following'] : 0;

// Get author URL for follower/following links
$author_url = get_author_posts_url($author_id);

// Get profile options
$profile_options = get_option('vh360_profile_options', array());
$profile_defaults = array(
    'enable_profiles' => true,
    'show_social' => true,
);
$profile_options = wp_parse_args($profile_options, $profile_defaults);
?>

<aside class="vh360-profile-rail">
    
    <!-- Intro Card -->
    <div class="vh360-profile-card vh360-profile-intro-card">
        <h3 class="vh360-profile-card-title"><?php esc_html_e('Intro', 'videohub360-theme'); ?></h3>
        
        <?php if (!empty($bio)) : ?>
            <div class="vh360-profile-card-content">
                <?php echo wp_kses_post(wpautop($bio)); ?>
            </div>
        <?php endif; ?>
        
        <div class="vh360-profile-meta-list">
            <?php if (!empty($social_links)) : ?>
                
                <?php foreach ($social_links as $platform => $url) : ?>
                    <?php if ($url) : ?>
                        <div class="vh360-profile-meta-item">
                            <span><?php echo esc_html(ucfirst($platform)); ?>:</span>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                                <?php 
                                $parsed_url = parse_url($url);
                                if (isset($parsed_url['path']) && !empty($parsed_url['path'])) {
                                    echo esc_html(basename($parsed_url['path']));
                                } else {
                                    echo esc_html(ucfirst($platform));
                                }
                                ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Stats Card -->
    <div class="vh360-profile-card vh360-profile-stats-card">
        <h3 class="vh360-profile-card-title"><?php esc_html_e('Stats', 'videohub360-theme'); ?></h3>
        <div class="vh360-profile-card-stats">
            <div class="vh360-profile-card-stat-item">
                <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($video_count)); ?></span>
                <span class="vh360-profile-card-stat-label"><?php echo esc_html(_n('Video', 'Videos', $video_count, 'videohub360-theme')); ?></span>
            </div>
            <div class="vh360-profile-card-stat-item">
                <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($total_views)); ?></span>
                <span class="vh360-profile-card-stat-label"><?php echo esc_html(_n('View', 'Views', $total_views, 'videohub360-theme')); ?></span>
            </div>
            <a href="<?php echo esc_url(add_query_arg('tab', 'followers', $author_url)); ?>" class="vh360-profile-card-stat-item vh360-profile-card-stat-item--link">
                <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($followers)); ?></span>
                <span class="vh360-profile-card-stat-label"><?php echo esc_html(_n('Follower', 'Followers', $followers, 'videohub360-theme')); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'following', $author_url)); ?>" class="vh360-profile-card-stat-item vh360-profile-card-stat-item--link">
                <span class="vh360-profile-card-stat-value"><?php echo esc_html(vh360_format_number($following)); ?></span>
                <span class="vh360-profile-card-stat-label"><?php esc_html_e('Following', 'videohub360-theme'); ?></span>
            </a>
        </div>
    </div>
    
    <!-- Photos Preview Card -->
    <?php
    // Query recent photos from community posts
    $photos_query = new WP_Query(array(
        'post_type'      => 'vh360_post',
        'post_status'    => 'publish',
        'author'         => $author_id,
        'posts_per_page' => 6,
        'meta_query'     => array(
            array(
                'key'     => 'vh360_post_media_type',
                'value'   => 'image',
                'compare' => '=',
            ),
        ),
    ));
    
    if ($photos_query->have_posts()) :
    ?>
        <div class="vh360-profile-card vh360-profile-photos-card">
            <div class="vh360-profile-card-header">
                <h3 class="vh360-profile-card-title"><?php esc_html_e('Photos', 'videohub360-theme'); ?></h3>
                <a href="<?php echo esc_url(add_query_arg('tab', 'posts', get_author_posts_url($author_id))); ?>" class="vh360-profile-card-link">
                    <?php esc_html_e('See All', 'videohub360-theme'); ?>
                </a>
            </div>
            <div class="vh360-profile-photos-grid">
                <?php
                while ($photos_query->have_posts()) :
                    $photos_query->the_post();
                    $media_url = get_post_meta(get_the_ID(), 'vh360_post_media_url', true);
                    if ($media_url) :
                ?>
                    <a href="<?php echo esc_url(get_permalink()); ?>" class="vh360-profile-photo-thumb">
                        <img src="<?php echo esc_url($media_url); ?>" alt="" loading="lazy">
                    </a>
                <?php
                    endif;
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    <?php
    endif;
    ?>
    
</aside>
