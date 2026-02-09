<?php
/**
 * Channel Navigation Template Part
 *
 * Displays tab navigation for channel pages (Videos, Playlists, About).
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

// Get current tab from URL, default to 'videos'
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'videos';

// Build list of valid tabs
$valid_tabs = array('videos', 'followers', 'following', 'about');

// Check if channel has playlists
$has_playlists = vh360_channel_has_playlists($author_id);
if ($has_playlists) {
    $valid_tabs[] = 'playlists';
}

// Validate tab
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'videos';
}

// Get base author URL
$author_url = get_author_posts_url($author_id);
?>

<nav class="vh360-channel-navigation" role="navigation" aria-label="<?php esc_attr_e('Channel Navigation', 'videohub360-theme'); ?>">
    <div class="container">
        <ul class="vh360-channel-nav-tabs" role="tablist">
            
            <!-- Videos Tab (Always visible) -->
            <li class="vh360-channel-nav-item" role="presentation">
                <a href="<?php echo esc_url(add_query_arg('tab', 'videos', $author_url)); ?>" 
                   class="vh360-channel-nav-link<?php echo ('videos' === $current_tab) ? ' active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo ('videos' === $current_tab) ? 'true' : 'false'; ?>"
                   aria-controls="vh360-tab-videos">
                    <?php esc_html_e('Videos', 'videohub360-theme'); ?>
                </a>
            </li>
            
            <!-- Playlists Tab (Conditional - only if series exist) -->
            <?php if ($has_playlists) : ?>
            <li class="vh360-channel-nav-item" role="presentation">
                <a href="<?php echo esc_url(add_query_arg('tab', 'playlists', $author_url)); ?>" 
                   class="vh360-channel-nav-link<?php echo ('playlists' === $current_tab) ? ' active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo ('playlists' === $current_tab) ? 'true' : 'false'; ?>"
                   aria-controls="vh360-tab-playlists">
                    <?php esc_html_e('Playlists', 'videohub360-theme'); ?>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Followers Tab (Always visible) -->
            <li class="vh360-channel-nav-item" role="presentation">
                <a href="<?php echo esc_url(add_query_arg('tab', 'followers', $author_url)); ?>" 
                   class="vh360-channel-nav-link<?php echo ('followers' === $current_tab) ? ' active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo ('followers' === $current_tab) ? 'true' : 'false'; ?>"
                   aria-controls="vh360-tab-followers">
                    <?php esc_html_e('Followers', 'videohub360-theme'); ?>
                </a>
            </li>
            
            <!-- Following Tab (Always visible) -->
            <li class="vh360-channel-nav-item" role="presentation">
                <a href="<?php echo esc_url(add_query_arg('tab', 'following', $author_url)); ?>" 
                   class="vh360-channel-nav-link<?php echo ('following' === $current_tab) ? ' active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo ('following' === $current_tab) ? 'true' : 'false'; ?>"
                   aria-controls="vh360-tab-following">
                    <?php esc_html_e('Following', 'videohub360-theme'); ?>
                </a>
            </li>
            
            <!-- About Tab (Always visible) -->
            <li class="vh360-channel-nav-item" role="presentation">
                <a href="<?php echo esc_url(add_query_arg('tab', 'about', $author_url)); ?>" 
                   class="vh360-channel-nav-link<?php echo ('about' === $current_tab) ? ' active' : ''; ?>"
                   role="tab"
                   aria-selected="<?php echo ('about' === $current_tab) ? 'true' : 'false'; ?>"
                   aria-controls="vh360-tab-about">
                    <?php esc_html_e('About', 'videohub360-theme'); ?>
                </a>
            </li>
            
        </ul>
    </div>
</nav>
