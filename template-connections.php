<?php
/**
 * Template Name: Connections
 *
 * Displays a user's followers and following in a tabbed layout.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get user ID from query parameter
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();

// Validate user exists
$user = get_userdata($user_id);
if (!$user) {
    get_header();
    ?>
    <div id="primary" class="site-content">
        <div class="vh360-profile-page">
            <div class="container">
                <div class="vh360-error-message">
                    <h1><?php esc_html_e('User Not Found', 'videohub360-theme'); ?></h1>
                    <p><?php esc_html_e('The user you are looking for does not exist.', 'videohub360-theme'); ?></p>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="vh360-btn vh360-btn-primary">
                        <?php esc_html_e('Go Home', 'videohub360-theme'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Get active tab (default: followers)
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'followers';
if (!in_array($active_tab, array('followers', 'following'))) {
    $active_tab = 'followers';
}

// Get followers and following
$followers = function_exists('vh360_get_followers') ? vh360_get_followers($user_id) : array();
$following = function_exists('vh360_get_following_user_ids') ? vh360_get_following_user_ids($user_id) : array();

$followers_count = count($followers);
$following_count = count($following);

// Get user data
$display_name = $user->display_name;

// Get current page URL for tab links
$current_page_url = get_permalink();

get_header();
?>

<div id="primary" class="site-content">
    <div class="vh360-profile-page">
        
        <?php
        // Set the queried object to the user we're viewing connections for
        // This allows the profile header and navigation to work correctly
        global $wp_query;
        $original_queried_object = $wp_query->queried_object;
        $original_queried_object_id = $wp_query->queried_object_id;
        
        try {
            // Temporarily set the queried object to our user
            $wp_query->queried_object = $user;
            $wp_query->queried_object_id = $user_id;
            
            // Load profile header and navigation components
            get_template_part('template-parts/profile/header');
            get_template_part('template-parts/profile/navigation');
        } finally {
            // Always restore original queried object, even if template loading fails
            $wp_query->queried_object = $original_queried_object;
            $wp_query->queried_object_id = $original_queried_object_id;
        }
        ?>
        
        <div class="container">
            <div class="vh360-profile-content">
                
                <!-- Connections Header -->
                <div class="vh360-connections-header">
                    <h2 class="vh360-connections-title">
                        <?php 
                        /* translators: %s: User display name */
                        printf(esc_html__("%s's Connections", 'videohub360-theme'), esc_html($display_name)); 
                        ?>
                    </h2>
                    <p class="vh360-connections-summary">
                        <span><?php echo esc_html(number_format_i18n($followers_count)); ?> <?php esc_html_e('Followers', 'videohub360-theme'); ?></span>
                        <span class="vh360-connections-separator">·</span>
                        <span><?php echo esc_html(number_format_i18n($following_count)); ?> <?php esc_html_e('Following', 'videohub360-theme'); ?></span>
                    </p>
                </div>
                
                <!-- Tab Navigation -->
                <div class="vh360-connections-tabs">
                    <nav class="vh360-tabs-nav">
                        <a href="<?php echo esc_url(add_query_arg(array('user_id' => $user_id, 'tab' => 'followers'), $current_page_url)); ?>" 
                           class="vh360-tab-link <?php echo $active_tab === 'followers' ? 'active' : ''; ?>">
                            <?php esc_html_e('Followers', 'videohub360-theme'); ?>
                            <span class="vh360-tab-count"><?php echo esc_html($followers_count); ?></span>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(array('user_id' => $user_id, 'tab' => 'following'), $current_page_url)); ?>" 
                           class="vh360-tab-link <?php echo $active_tab === 'following' ? 'active' : ''; ?>">
                            <?php esc_html_e('Following', 'videohub360-theme'); ?>
                            <span class="vh360-tab-count"><?php echo esc_html($following_count); ?></span>
                        </a>
                    </nav>
                </div>
                
                <!-- Tab Content -->
                <?php if ($active_tab === 'followers') : ?>
                    <!-- Followers Tab -->
                    <div class="vh360-tab-panel active" id="followers-panel">
                        <?php if (!empty($followers)) : ?>
                            <div class="vh360-user-grid">
                                <?php foreach ($followers as $follower_id) : ?>
                                    <?php
                                    get_template_part('template-parts/components/card-user', null, array(
                                        'user_id' => $follower_id,
                                        'show_avatar' => true,
                                        'show_bio' => true,
                                        'show_follow_button' => true,
                                        'avatar_size' => 64,
                                    ));
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <!-- Empty State -->
                            <div class="vh360-empty-state">
                                <div class="vh360-empty-state-icon">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <h2 class="vh360-empty-state-title"><?php esc_html_e('No Followers Yet', 'videohub360-theme'); ?></h2>
                                <p class="vh360-empty-state-message"><?php esc_html_e("This user doesn't have any followers yet.", 'videohub360-theme'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php else : ?>
                    <!-- Following Tab -->
                    <div class="vh360-tab-panel active" id="following-panel">
                        <?php if (!empty($following)) : ?>
                            <div class="vh360-user-grid">
                                <?php foreach ($following as $following_id) : ?>
                                    <?php
                                    get_template_part('template-parts/components/card-user', null, array(
                                        'user_id' => $following_id,
                                        'show_avatar' => true,
                                        'show_bio' => true,
                                        'show_follow_button' => true,
                                        'avatar_size' => 64,
                                    ));
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <!-- Empty State -->
                            <div class="vh360-empty-state">
                                <div class="vh360-empty-state-icon">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <h2 class="vh360-empty-state-title"><?php esc_html_e('Not Following Anyone', 'videohub360-theme'); ?></h2>
                                <p class="vh360-empty-state-message"><?php esc_html_e("This user isn't following anyone yet.", 'videohub360-theme'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            </div><!-- .vh360-profile-content -->
        </div><!-- .container -->
        
    </div><!-- .vh360-profile-page -->
</div><!-- #primary -->

<?php get_footer(); ?>
