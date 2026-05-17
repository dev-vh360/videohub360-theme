<?php
/**
 * Channel Header Template Part
 *
 * Displays YouTube-style channel header with banner, avatar, 
 * subscribe button, and channel stats.
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

// Get user data
$avatar_url = vh360_get_user_avatar_url($author_id, 150);
$cover_image = vh360_get_user_cover_image($author_id);
$display_name = $author->display_name;

// Get user stats
$stats = vh360_get_user_stats($author_id);

// Check if current user can edit this channel
$can_edit = function_exists('vh360_user_can_edit_profile') ? vh360_user_can_edit_profile($author_id) : false;
$current_user_id = get_current_user_id();
?>

<div class="vh360-channel-header">
    <!-- Cover/Banner Image -->
    <div class="vh360-channel-banner">
        <?php if ($cover_image) : ?>
            <img src="<?php echo esc_url($cover_image); ?>" alt="<?php echo esc_attr($display_name); ?> channel banner" class="vh360-channel-banner-img">
        <?php else : ?>
            <div class="vh360-channel-banner-placeholder"></div>
        <?php endif; ?>
    </div>

    <!-- Channel Info Section -->
    <div class="vh360-channel-info">
        <div class="container">
            <div class="vh360-channel-info-inner">
                
                <!-- Avatar -->
                <div class="vh360-channel-avatar">
                    <?php if ($avatar_url) : ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                    <?php else : ?>
                        <img src="<?php echo esc_url(get_avatar_url($author_id, array('size' => 150))); ?>" alt="<?php echo esc_attr($display_name); ?>">
                    <?php endif; ?>
                </div>

                <!-- Channel Details -->
                <div class="vh360-channel-details">
                    <h1 class="vh360-channel-name"><?php echo esc_html($display_name); ?></h1>
                    
                    <!-- Channel Stats -->
                    <div class="vh360-channel-stats">
                        <span class="vh360-channel-stat">
                            <strong><?php echo esc_html(number_format_i18n($stats['followers'])); ?></strong>
                            <?php echo esc_html(_n('Subscriber', 'Subscribers', $stats['followers'], 'videohub360-theme')); ?>
                        </span>
                        <span class="vh360-channel-stat-separator">•</span>
                        <span class="vh360-channel-stat">
                            <strong><?php echo esc_html(number_format_i18n($stats['following'])); ?></strong>
                            <?php echo esc_html(_n('Following', 'Following', $stats['following'], 'videohub360-theme')); ?>
                        </span>
                        <span class="vh360-channel-stat-separator">•</span>
                        <span class="vh360-channel-stat">
                            <strong><?php echo esc_html(number_format_i18n($stats['videos'])); ?></strong>
                            <?php echo esc_html(_n('Video', 'Videos', $stats['videos'], 'videohub360-theme')); ?>
                        </span>
                        <span class="vh360-channel-stat-separator">•</span>
                        <span class="vh360-channel-stat">
                            <strong><?php echo esc_html(number_format_i18n($stats['views'])); ?></strong>
                            <?php echo esc_html(_n('View', 'Views', $stats['views'], 'videohub360-theme')); ?>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="vh360-channel-actions">
                    <?php
                    // Show Edit/Customize button if user can edit this channel
                    if ($can_edit) :
                        $edit_url = function_exists( 'vh360_get_profile_edit_url' ) ? vh360_get_profile_edit_url( $author_id ) : home_url( '/dashboard/?tab=profile' );
                        ?>
                        <a href="<?php echo esc_url($edit_url); ?>" class="vh360-channel-customize-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <?php esc_html_e('Customize Channel', 'videohub360-theme'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show subscribe button if viewing another user's channel
                    if ($current_user_id && $current_user_id != $author_id && function_exists('vh360_follow_button')) :
                        vh360_follow_button($author_id, 'vh360-channel-subscribe-btn');
                    endif;
                    ?>
                </div>

            </div>
        </div>
    </div>
</div>
