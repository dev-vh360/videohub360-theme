<?php
/**
 * Admin Dashboard Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Theme Dashboard', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

// Get statistics
$total_members = count_users();
$total_members_count = $total_members['total_users'];

$bulletins_count = wp_count_posts('vh360_bulletin');
$total_bulletins = isset($bulletins_count->publish) ? $bulletins_count->publish : 0;

$activities = get_option('vh360_activity_feed', array());
$recent_activities_count = is_array($activities) ? count($activities) : 0;

$appearance_options = get_option('vh360_appearance_options', array());
$active_features = 0;
if (isset($appearance_options['enable_profiles']) && $appearance_options['enable_profiles']) $active_features++;
if (isset($appearance_options['enable_bulletins']) && $appearance_options['enable_bulletins']) $active_features++;
if (isset($appearance_options['enable_activity']) && $appearance_options['enable_activity']) $active_features++;
if (isset($appearance_options['enable_members']) && $appearance_options['enable_members']) $active_features++;
if (isset($appearance_options['enable_user_menu']) && $appearance_options['enable_user_menu']) $active_features++;

// System status checks
$plugin_active = class_exists('VideoHub360_Core');
$permalink_structure = get_option('permalink_structure');
$permalink_ok = !empty($permalink_structure);

$required_pages = array('dashboard', 'profile-edit', 'members', 'activity', 'bulletins');
$pages_exist = true;
foreach ($required_pages as $slug) {
    if (!get_page_by_path($slug)) {
        $pages_exist = false;
        break;
    }
}
?>

<div class="vh360-admin-dashboard">
    
    <!-- Welcome Section -->
    <div class="vh360-welcome-section">
        <h2><?php esc_html_e('Welcome to Videohub360 Theme', 'videohub360-theme'); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %s: Theme version */
                esc_html__('You are running version %s. Manage all your theme settings from this centralized dashboard.', 'videohub360-theme'),
                '<strong>' . esc_html(VH360_THEME_VERSION) . '</strong>'
            );
            ?>
        </p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="vh360-stats-grid">
        <?php
        $icon = 'dashicons-admin-users';
        $label = __('Total Members', 'videohub360-theme');
        $value = number_format_i18n($total_members_count);
        $status = 'success';
        $link = admin_url('users.php');
        include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
        
        $icon = 'dashicons-megaphone';
        $label = __('Total Bulletins', 'videohub360-theme');
        $value = number_format_i18n($total_bulletins);
        $status = 'info';
        $link = admin_url('edit.php?post_type=vh360_bulletin');
        include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
        
        $icon = 'dashicons-chart-line';
        $label = __('Recent Activities', 'videohub360-theme');
        $value = number_format_i18n($recent_activities_count);
        $status = 'warning';
        $link = admin_url('admin.php?page=vh360-theme-activity');
        include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
        
        $icon = 'dashicons-star-filled';
        $label = __('Active Features', 'videohub360-theme');
        $value = number_format_i18n($active_features);
        $status = 'default';
        $link = admin_url('admin.php?page=vh360-theme-appearance');
        include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
        ?>
    </div>
    
    <!-- Two Column Layout -->
    <div class="vh360-admin-cols">
        
        <!-- Left Column -->
        <div class="vh360-admin-col-left">
            
            <!-- System Status -->
            <div class="vh360-admin-card">
                <h2><?php esc_html_e('System Status', 'videohub360-theme'); ?></h2>
                
                <div class="vh360-status-list">
                    <div class="vh360-status-item <?php echo $plugin_active ? 'status-ok' : 'status-warning'; ?>">
                        <span class="dashicons <?php echo $plugin_active ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                        <span class="vh360-status-label"><?php esc_html_e('Videohub360 Plugin', 'videohub360-theme'); ?></span>
                        <span class="vh360-status-value">
                            <?php echo $plugin_active ? esc_html__('Active', 'videohub360-theme') : esc_html__('Not Active', 'videohub360-theme'); ?>
                        </span>
                    </div>
                    
                    <div class="vh360-status-item <?php echo $pages_exist ? 'status-ok' : 'status-warning'; ?>">
                        <span class="dashicons <?php echo $pages_exist ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                        <span class="vh360-status-label"><?php esc_html_e('Required Pages', 'videohub360-theme'); ?></span>
                        <span class="vh360-status-value">
                            <?php echo $pages_exist ? esc_html__('Created', 'videohub360-theme') : esc_html__('Missing', 'videohub360-theme'); ?>
                        </span>
                    </div>
                    
                    <div class="vh360-status-item <?php echo $permalink_ok ? 'status-ok' : 'status-warning'; ?>">
                        <span class="dashicons <?php echo $permalink_ok ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                        <span class="vh360-status-label"><?php esc_html_e('Permalink Structure', 'videohub360-theme'); ?></span>
                        <span class="vh360-status-value">
                            <?php echo $permalink_ok ? esc_html__('Configured', 'videohub360-theme') : esc_html__('Not Set', 'videohub360-theme'); ?>
                        </span>
                    </div>
                    
                    <div class="vh360-status-item status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="vh360-status-label"><?php esc_html_e('Theme Version', 'videohub360-theme'); ?></span>
                        <span class="vh360-status-value"><?php echo esc_html(VH360_THEME_VERSION); ?></span>
                    </div>
                    
                    <div class="vh360-status-item status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="vh360-status-label"><?php esc_html_e('WordPress Version', 'videohub360-theme'); ?></span>
                        <span class="vh360-status-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="vh360-admin-card">
                <h2><?php esc_html_e('Quick Actions', 'videohub360-theme'); ?></h2>
                
                <div class="vh360-quick-actions">
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=vh360_bulletin')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php esc_html_e('View All Bulletins', 'videohub360-theme'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php esc_html_e('Manage Members', 'videohub360-theme'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-customizer"></span>
                        <?php esc_html_e('Customize Theme', 'videohub360-theme'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-templates')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e('Page Templates Guide', 'videohub360-theme'); ?>
                    </a>
                </div>
            </div>
            
        </div>
        
        <!-- Right Column -->
        <div class="vh360-admin-col-right">
            
            <!-- Recent Activity -->
            <div class="vh360-admin-card">
                <h2><?php esc_html_e('Recent Activity', 'videohub360-theme'); ?></h2>
                
                <?php if (!empty($activities)) : ?>
                    <div class="vh360-activity-feed">
                        <?php
                        $recent_activities = array_slice($activities, 0, 10);
                        foreach ($recent_activities as $activity) :
                            $user = get_userdata($activity['user_id']);
                            if (!$user) continue;
                            
                            $activity_icons = array(
                                'video_upload' => 'dashicons-video-alt3',
                                'new_member' => 'dashicons-admin-users',
                                'profile_update' => 'dashicons-admin-users',
                                'milestone' => 'dashicons-star-filled',
                            );
                            
                            $icon = isset($activity_icons[$activity['type']]) ? $activity_icons[$activity['type']] : 'dashicons-marker';
                            ?>
                            <div class="vh360-activity-item">
                                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                <div class="vh360-activity-content">
                                    <div class="vh360-activity-user">
                                        <?php echo esc_html($user->display_name); ?>
                                    </div>
                                    <div class="vh360-activity-action">
                                        <?php
                                        if (isset($activity['content']['title'])) {
                                            echo esc_html($activity['content']['title']);
                                        }
                                        ?>
                                    </div>
                                    <div class="vh360-activity-time">
                                        <?php
                                        if (isset($activity['timestamp'])) {
                                            echo esc_html(human_time_diff($activity['timestamp'], current_time('timestamp'))) . ' ' . esc_html__('ago', 'videohub360-theme');
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p style="margin-top: 15px;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-activity')); ?>">
                            <?php esc_html_e('View All Activities →', 'videohub360-theme'); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e('No recent activities found.', 'videohub360-theme'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Documentation Links -->
            <div class="vh360-admin-card">
                <h2><?php esc_html_e('Documentation & Support', 'videohub360-theme'); ?></h2>
                
                <ul class="vh360-links-list">
                    <li>
                        <a href="https://videohub360.com/docs/getting-started" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-book"></span>
                            <?php esc_html_e('Getting Started Guide', 'videohub360-theme'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://videohub360.com/docs/page-templates" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e('Page Templates Documentation', 'videohub360-theme'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://videohub360.com/docs/customization" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-admin-customizer"></span>
                            <?php esc_html_e('Customization Guide', 'videohub360-theme'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://videohub360.com/support" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-sos"></span>
                            <?php esc_html_e('Support Forum', 'videohub360-theme'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="https://videohub360.com/docs/troubleshooting" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Troubleshooting', 'videohub360-theme'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
        </div>
        
    </div>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
