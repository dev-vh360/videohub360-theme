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
                
                <?php
                $active_tab = function_exists('vh360_get_current_dashboard_tab')
                    ? vh360_get_current_dashboard_tab()
                    : 'overview';

                $dashboard_tab_templates = array(
                    'overview'           => 'overview',
                    'create-video'       => 'create-video',
                    'videos'             => 'videos',
                    'courses'            => 'courses',
                    'learning'           => 'learning',
                    'live-rooms'         => 'live-rooms',
                    'create-post'        => 'create-post',
                    'profile'            => 'profile',
                    'business-profile'   => 'business-profile',
                    'galleries'          => 'gallery',
                    'events'             => 'events',
                    'appointments'       => 'appointments',
                    'availability'       => 'availability',
                    'bulletins'          => 'bulletins',
                    'messages'           => 'messages',
                    'notifications'      => 'notifications',
                    'push-notifications' => 'push-notifications',
                    'liked-videos'       => 'liked-videos',
                    'playlists'          => 'playlists',
                    'invites'            => 'invites',
                    'settings'           => 'settings',
                    'giving'             => 'giving',
                    'membership'         => 'membership',
                );

                $dashboard_tabs_registry = function_exists('vh360_get_dashboard_tabs_registry')
                    ? vh360_get_dashboard_tabs_registry($current_user_id)
                    : array();

                if (!isset($dashboard_tabs_registry[$active_tab]) && !isset($dashboard_tab_templates[$active_tab])) {
                    $active_tab = 'overview';
                }

                $active_tab_config = isset($dashboard_tabs_registry[$active_tab]) ? $dashboard_tabs_registry[$active_tab] : array();
                if (empty($active_tab_config['content_callback']) && !isset($dashboard_tab_templates[$active_tab])) {
                    $active_tab = 'overview';
                    $active_tab_config = isset($dashboard_tabs_registry[$active_tab]) ? $dashboard_tabs_registry[$active_tab] : array();
                }
                ?>
                <div id="<?php echo esc_attr($active_tab); ?>" class="vh360-dashboard-tab-content active">
                    <?php
                    if (!empty($active_tab_config['content_callback']) && is_callable($active_tab_config['content_callback'])) {
                        call_user_func($active_tab_config['content_callback'], $current_user_id);
                    } elseif (isset($dashboard_tab_templates[$active_tab])) {
                        get_template_part('template-parts/dashboard/' . $dashboard_tab_templates[$active_tab]);
                    } else {
                        get_template_part('template-parts/dashboard/overview');
                    }
                    ?>
                </div>
                
            </div><!-- .vh360-dashboard-content -->
            
        </div><!-- .vh360-dashboard -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
