<?php
/**
 * Author Channel Template (YouTube-style)
 *
 * Displays author pages in Channel mode (video-first layout).
 * Shows videos in a grid with playlists and channel information.
 * 
 * Note: This file is loaded when vh360_author_template_mode = 'channel'
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    get_template_part('template-parts/content', 'none');
    return;
}

// Get current tab from URL, default to 'videos'
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'videos';

// Build list of valid tabs
$valid_tabs = array('videos', 'followers', 'following', 'about');

// Only add playlists tab if the channel has playlists
if (vh360_channel_has_playlists($author_id)) {
    $valid_tabs[] = 'playlists';
}

// Validate tab
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'videos';
}
?>

<div id="primary" class="site-content">
    <div class="vh360-channel-page">
        
        <?php get_template_part('template-parts/channel/header'); ?>
        <?php get_template_part('template-parts/channel/navigation'); ?>
        
        <div class="container">
            <div class="vh360-channel-content">
                
                <?php if ('videos' === $current_tab) : ?>
                    <?php get_template_part('template-parts/channel/videos-grid'); ?>
                    
                <?php elseif ('playlists' === $current_tab) : ?>
                    <?php get_template_part('template-parts/channel/playlists'); ?>
                    
                <?php elseif ('followers' === $current_tab) : ?>
                    <?php get_template_part('template-parts/channel/followers'); ?>
                    
                <?php elseif ('following' === $current_tab) : ?>
                    <?php get_template_part('template-parts/channel/following'); ?>
                    
                <?php elseif ('about' === $current_tab) : ?>
                    <?php get_template_part('template-parts/channel/about'); ?>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
</div>
