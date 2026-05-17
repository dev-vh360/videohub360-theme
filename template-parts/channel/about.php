<?php
/**
 * Channel About Template Part
 *
 * Displays channel description, stats, and additional information.
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
$description = get_the_author_meta('description', $author_id);
$website = $author->user_url;
$join_date = vh360_get_user_join_date($author_id, 'F j, Y');

// Get user stats
$stats = vh360_get_user_stats($author_id);

// Get social links if available
$social_links = function_exists('vh360_get_user_social_links') ? vh360_get_user_social_links($author_id) : array();
?>

<div class="vh360-channel-about-section">
    
    <div class="vh360-channel-about-layout">
        
        <!-- Left Column: Description -->
        <div class="vh360-channel-about-main">
            <h2 class="vh360-channel-section-title"><?php esc_html_e('About', 'videohub360-theme'); ?></h2>
            
            <?php if (!empty($description)) : ?>
                <div class="vh360-channel-description">
                    <?php echo wpautop(wp_kses_post($description)); ?>
                </div>
            <?php else : ?>
                <div class="vh360-channel-description vh360-channel-description-empty">
                    <p><?php esc_html_e('No channel description yet.', 'videohub360-theme'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (function_exists('vh360_render_public_profile_fields')) : ?>
                <?php vh360_render_public_profile_fields($author_id); ?>
            <?php endif; ?>

            <!-- Links Section -->
            <?php if ($website || !empty($social_links)) : ?>
                <div class="vh360-channel-links">
                    <h3 class="vh360-channel-subsection-title"><?php esc_html_e('Links', 'videohub360-theme'); ?></h3>
                    
                    <ul class="vh360-channel-links-list">
                        <?php if ($website) : ?>
                            <li>
                                <a href="<?php echo esc_url($website); ?>" class="vh360-channel-link" target="_blank" rel="noopener noreferrer">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="2" y1="12" x2="22" y2="12"></line>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                    </svg>
                                    <span>
                                        <?php 
                                        $host = parse_url($website, PHP_URL_HOST);
                                        echo esc_html($host ? $host : $website);
                                        ?>
                                    </span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php foreach ($social_links as $platform => $url) : ?>
                            <?php if ($url) : ?>
                                <li>
                                    <a href="<?php echo esc_url($url); ?>" class="vh360-channel-link" target="_blank" rel="noopener noreferrer">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                        </svg>
                                        <span><?php echo esc_html(ucfirst($platform)); ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Stats & Info -->
        <div class="vh360-channel-about-sidebar">
            
            <!-- Channel Stats -->
            <div class="vh360-channel-stats-box">
                <h3 class="vh360-channel-subsection-title"><?php esc_html_e('Channel Stats', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-channel-stat-item">
                    <span class="vh360-channel-stat-label"><?php esc_html_e('Joined', 'videohub360-theme'); ?></span>
                    <span class="vh360-channel-stat-value"><?php echo esc_html($join_date); ?></span>
                </div>

                <div class="vh360-channel-stat-item">
                    <span class="vh360-channel-stat-label"><?php esc_html_e('Total Views', 'videohub360-theme'); ?></span>
                    <span class="vh360-channel-stat-value"><?php echo esc_html(number_format_i18n($stats['views'])); ?></span>
                </div>

                <div class="vh360-channel-stat-item">
                    <span class="vh360-channel-stat-label"><?php esc_html_e('Videos', 'videohub360-theme'); ?></span>
                    <span class="vh360-channel-stat-value"><?php echo esc_html(number_format_i18n($stats['videos'])); ?></span>
                </div>

                <div class="vh360-channel-stat-item">
                    <span class="vh360-channel-stat-label"><?php esc_html_e('Subscribers', 'videohub360-theme'); ?></span>
                    <span class="vh360-channel-stat-value"><?php echo esc_html(number_format_i18n($stats['followers'])); ?></span>
                </div>
            </div>

        </div>
        
    </div>

</div>
