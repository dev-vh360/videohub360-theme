<?php
/**
 * Template Name: Dashboard
 *
 * Frontend dashboard page template with sidebar navigation
 * and tabbed content for user management.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// User authentication is now handled by community-gate.php
// No inline redirect needed here

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <div class="vh360-dashboard">
            
            <!-- Sidebar Navigation -->
            <?php get_template_part('template-parts/dashboard/nav'); ?>
            
            <!-- Dashboard Content -->
            <div class="vh360-dashboard-content">
                
                <!-- Overview Tab -->
                <div id="overview" class="vh360-dashboard-tab-content active">
                    <?php get_template_part('template-parts/dashboard/overview'); ?>
                </div>
                
                <!-- Create Video Tab -->
                <div id="create-video" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/create-video'); ?>
                </div>
                
                <!-- Videos Tab -->
                <div id="videos" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/videos'); ?>
                </div>
                
                <!-- Live Rooms Tab -->
                <div id="live-rooms" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/live-rooms'); ?>
                </div>

                <!-- Go Live Tab -->
                <div id="go-live" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/go-live'); ?>
                </div>

                <!-- Create Post Tab -->
                <div id="create-post" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/create-post'); ?>
                </div>

                <!-- Profile Tab -->
                <div id="profile" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/profile'); ?>
                </div>
                
                <!-- Business Profile Tab (for professionals/organizations only) -->
                <?php
                $user_account_type = function_exists('vh360_get_user_account_type') ? vh360_get_user_account_type($current_user_id) : 'creator';
                if (in_array($user_account_type, array('professional', 'organization'), true)) :
                ?>
                <div id="business-profile" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/business-profile'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Galleries Tab -->
                <div id="galleries" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/gallery'); ?>
                </div>
                
                <!-- Events Tab -->
                <div id="events" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/events'); ?>
                </div>
                
                <!-- My Appointments Tab -->
                <div id="appointments" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/appointments'); ?>
                </div>
                
                <!-- Availability Tab -->
                <div id="availability" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/availability'); ?>
                </div>
                
                <!-- Bulletins Tab -->
                <div id="bulletins" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/bulletins'); ?>
                </div>
                
                <!-- Messages Tab -->
                <div id="messages" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/messages'); ?>
                </div>
                
                <!-- Notifications Tab -->
                <div id="notifications" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/notifications'); ?>
                </div>

                <!-- Push Notifications Tab (PWA App) -->
                <?php if ( current_user_can( 'vh360_send_push' ) ) : ?>
                    <div id="push-notifications" class="vh360-dashboard-tab-content">
                        <?php get_template_part('template-parts/dashboard/push-notifications'); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Liked Videos Tab -->
                <div id="liked-videos" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/liked-videos'); ?>
                </div>
                
                <!-- Playlists Tab -->
                <div id="playlists" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/playlists'); ?>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/settings'); ?>
                </div>
                
            </div><!-- .vh360-dashboard-content -->
            
        </div><!-- .vh360-dashboard -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
