<?php
/**
 * Author Profile Template (Social Media Style)
 *
 * This template displays author pages in Profile mode (social-first layout).
 * Shows user information, stats, bio, posts, videos, and social activity.
 * 
 * Note: This file is loaded when vh360_author_template_mode = 'profile'
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

// Get current tab from URL, default to 'posts'
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'posts';
$valid_tabs = array('posts', 'videos', 'about');
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'posts';
}
$profile_url = get_author_posts_url($author_id);
?>

<div id="primary" class="site-content">
    <div class="vh360-profile-page">
        
        <!-- Breadcrumbs -->
        <div class="container">
            <nav class="vh360-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'videohub360-theme'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'videohub360-theme'); ?></a>
                <span class="separator">&raquo;</span>
                <a href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"><?php esc_html_e('Profile', 'videohub360-theme'); ?></a>
                <span class="separator">&raquo;</span>
                <span class="current"><?php echo esc_html($author->display_name); ?></span>
            </nav>
        </div>

        <!-- Profile Header Section -->
        <?php get_template_part('template-parts/profile/header'); ?>

        <!-- Profile Navigation -->
        <?php get_template_part('template-parts/profile/navigation'); ?>

        <div class="container">
            <div class="vh360-profile-content">
                
                <!-- Desktop Two-Column Layout (hidden on mobile) -->
                <div class="vh360-profile-body vh360-profile-body--desktop">
                    <?php 
                    // Left sidebar: Static profile info
                    get_template_part('template-parts/profile/rail'); 
                    
                    // Right column: Activity feed
                    get_template_part('template-parts/profile/feed');
                    ?>
                </div>
                
                <!-- Mobile Tab-Based Layout (hidden on desktop) -->
                <div class="vh360-profile-mobile">
                    
                    <!-- Profile Stats Section -->
                    <?php get_template_part('template-parts/profile/stats'); ?>

                    <!-- Tab Content -->
                    <div class="vh360-profile-tab-panels">
                        <?php if ('posts' === $current_tab) : ?>
                            <!-- Profile Posts Section -->
                            <div class="vh360-profile-tab-content active" id="vh360-tab-posts" role="tabpanel">
                                <?php get_template_part('template-parts/profile/posts'); ?>
                            </div>
                        <?php elseif ('videos' === $current_tab) : ?>
                            <!-- Profile Videos Section -->
                            <div class="vh360-profile-tab-content active" id="vh360-tab-videos" role="tabpanel">
                                <?php get_template_part('template-parts/profile/videos'); ?>
                            </div>
                        <?php elseif ('about' === $current_tab) : ?>
                            <!-- Profile Intro Section -->
                            <div class="vh360-profile-tab-content active" id="vh360-tab-about" role="tabpanel">
                                <?php get_template_part('template-parts/profile/intro'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div><!-- .vh360-profile-mobile -->

            </div><!-- .vh360-profile-content -->
        </div><!-- .container -->

    </div><!-- .vh360-profile-page -->
</div><!-- #primary -->

<?php
get_footer();
