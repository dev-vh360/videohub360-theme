<?php
/**
 * Admin Notices
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display plugin dependency warning
 */
function vh360_admin_plugin_notice() {
    if (!class_exists('VideoHub360_Core')) {
        $screen = get_current_screen();
        
        // Show on theme admin pages
        if ($screen && strpos($screen->id, 'vh360-theme') !== false) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Videohub360 Plugin Not Active', 'videohub360-theme'); ?></strong><br>
                    <?php esc_html_e('Some theme features require the Videohub360 plugin to be installed and activated. Video management features will not be available until the plugin is active.', 'videohub360-theme'); ?>
                </p>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'vh360_admin_plugin_notice');

/**
 * Display required pages notice
 */
function vh360_admin_required_pages_notice() {
    $screen = get_current_screen();
    
    // Only show on dashboard page
    if (!$screen || $screen->id !== 'toplevel_page_vh360-theme') {
        return;
    }
    
    $required_pages = array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'template' => 'template-dashboard.php',
        ),
        'profile-edit' => array(
            'title' => 'Edit Profile',
            'template' => 'template-profile-edit.php',
        ),
        'members' => array(
            'title' => 'Members Directory',
            'template' => 'template-members-directory.php',
        ),
        'activity' => array(
            'title' => 'Activity Feed',
            'template' => 'template-activity-feed.php',
        ),
        'bulletins' => array(
            'title' => 'Bulletins',
            'template' => 'template-bulletins.php',
        ),
    );
    
    $missing_pages = array();
    
    foreach ($required_pages as $slug => $page_data) {
        $page = get_page_by_path($slug);
        if (!$page) {
            $missing_pages[] = $page_data['title'];
        }
    }
    
    if (!empty($missing_pages)) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Required Pages Missing', 'videohub360-theme'); ?></strong><br>
                <?php
                printf(
                    /* translators: %s: list of missing pages */
                    esc_html__('The following required pages are missing: %s. Visit the Page Templates guide to create them.', 'videohub360-theme'),
                    '<strong>' . esc_html(implode(', ', $missing_pages)) . '</strong>'
                );
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-templates')); ?>" class="button button-small" style="margin-left: 10px;">
                    <?php esc_html_e('View Templates Guide', 'videohub360-theme'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'vh360_admin_required_pages_notice');

/**
 * Display permalink structure warning
 */
function vh360_admin_permalink_notice() {
    $screen = get_current_screen();
    
    // Only show on theme admin pages
    if (!$screen || strpos($screen->id, 'vh360-theme') === false) {
        return;
    }
    
    $permalink_structure = get_option('permalink_structure');
    
    if (empty($permalink_structure)) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Permalink Structure Not Set', 'videohub360-theme'); ?></strong><br>
                <?php esc_html_e('The theme works best with pretty permalinks enabled. Please set your permalink structure to "Post name" or another format.', 'videohub360-theme'); ?>
                <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>" class="button button-small" style="margin-left: 10px;">
                    <?php esc_html_e('Permalink Settings', 'videohub360-theme'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'vh360_admin_permalink_notice');
