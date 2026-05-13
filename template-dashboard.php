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

// Check if user is a pending professional
$account_type = function_exists('vh360_get_user_account_type') ? vh360_get_user_account_type($current_user_id) : 'creator';
$is_pending_professional = false;
if ($account_type === 'professional' && function_exists('vh360_is_professional_approved')) {
    $is_pending_professional = !vh360_is_professional_approved($current_user_id);
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <div class="vh360-dashboard">
            
            <!-- Sidebar Navigation -->
            <?php get_template_part('template-parts/dashboard/nav'); ?>
            
            <!-- Dashboard Content -->
            <div class="vh360-dashboard-content">
                
                <?php if ($is_pending_professional) : ?>
                <!-- Professional Approval Pending Notice -->
                <div class="vh360-dashboard-notice vh360-approval-pending-notice">
                    <div class="vh360-notice-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="vh360-notice-content">
                        <h3><?php esc_html_e('Professional Account Pending Approval', 'videohub360-theme'); ?></h3>
                        <p><?php esc_html_e('Your professional account is currently pending admin approval. You can complete your profile while you wait. Professional features such as event creation and appointment scheduling will be available once your account is approved.', 'videohub360-theme'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
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
                
                <!-- My Courses Tab -->
                <?php
                $vh360_course_features_enabled = function_exists('videohub360_course_features_enabled') && videohub360_course_features_enabled();
                $vh360_can_manage_courses = $vh360_course_features_enabled && (
                    function_exists('vh360_user_can_create_videos')
                        ? vh360_user_can_create_videos($current_user_id)
                        : (current_user_can('manage_options') || current_user_can('vh360_create_videos'))
                );

                if ( $vh360_can_manage_courses ) :
                ?>
                <div id="courses" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/courses'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Live Rooms Tab -->
                <div id="live-rooms" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/live-rooms'); ?>
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
                
                <!-- Membership Tab (shown when memberships are enabled) -->
                <?php
                $vh360_membership_opts = get_option( 'vh360_membership_options', array() );
                if ( ! empty( $vh360_membership_opts['enable_memberships'] ) ) :
                ?>
                <div id="membership" class="vh360-dashboard-tab-content">
                    <?php get_template_part('template-parts/dashboard/membership'); ?>
                </div>
                <?php endif; ?>
                
            </div><!-- .vh360-dashboard-content -->
            
        </div><!-- .vh360-dashboard -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
