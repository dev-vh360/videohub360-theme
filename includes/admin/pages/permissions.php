<?php
/**
 * Permissions Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Permissions Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_permissions_options', array());
$defaults = array(
    'create_posts_roles' => array('administrator'),
    'create_videos_roles' => array('administrator'),
    'host_live_roles' => array('administrator'),
    'create_events_roles' => array('administrator', 'editor', 'author'),
    'create_bulletins_roles' => array('administrator', 'editor', 'author', 'contributor'),
    'bulletin_banner_roles' => array('administrator', 'editor'),
    'create_galleries_roles' => array('administrator', 'editor', 'author', 'contributor'),
    'publish_galleries_roles' => array('administrator', 'editor', 'author'),
    'upload_media_roles' => array('administrator', 'editor', 'author'),
);
$options = wp_parse_args($options, $defaults);

// Get all roles
$all_roles = wp_roles()->roles;

/**
 * Helper function to render role checkboxes for a permission
 */
function vh360_render_permission_roles($permission_key, $selected_roles, $all_roles) {
    // Administrator is always included
    if (isset($all_roles['administrator'])) {
        $admin_name = isset($all_roles['administrator']['name']) 
            ? translate_user_role($all_roles['administrator']['name']) 
            : __('Administrator', 'videohub360-theme');
        echo '<div style="margin-bottom: 10px;">';
        echo '<strong>' . esc_html($admin_name) . '</strong> ';
        echo '<span class="description">' . esc_html__('(always included)', 'videohub360-theme') . '</span>';
        echo '<input type="hidden" name="vh360_permissions_options[' . esc_attr($permission_key) . '][]" value="administrator">';
        echo '</div>';
    }
    
    // Render checkboxes for other roles
    foreach ($all_roles as $role_key => $role_data) {
        if ($role_key === 'administrator') {
            continue;
        }
        
        $role_name = isset($role_data['name']) ? translate_user_role($role_data['name']) : $role_key;
        $checked = in_array($role_key, $selected_roles, true);
        
        echo '<label style="display: block; margin: 5px 0;">';
        echo '<input type="checkbox" name="vh360_permissions_options[' . esc_attr($permission_key) . '][]" value="' . esc_attr($role_key) . '" ' . checked($checked, true, false) . '> ';
        echo esc_html($role_name);
        echo '</label>';
    }
}
?>

<div class="vh360-admin-settings">
    
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Role-Based Permissions', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Configure which user roles can access specific features. Select the roles that should have permission to create posts, create videos, and host live rooms.', 'videohub360-theme'); ?></p>
        
        <div class="notice notice-info inline">
            <p><?php esc_html_e('Note: Administrators always have all permissions and cannot be removed.', 'videohub360-theme'); ?></p>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_permissions_settings'); ?>
        
        <!-- Create Posts Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Create Posts Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can create posts from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can create posts', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('create_posts_roles', $options['create_posts_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Create Videos Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Create Videos Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can create videos from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can create videos', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('create_videos_roles', $options['create_videos_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Host Live Rooms Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Host Live Rooms Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can host live rooms from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can host live rooms', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('host_live_roles', $options['host_live_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Create Events Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Create Events Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can create events from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can create events', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('create_events_roles', $options['create_events_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Create Bulletins Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Create Bulletins Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can create bulletins from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can create bulletins', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('create_bulletins_roles', $options['create_bulletins_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Create Galleries Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Create Galleries Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can create galleries from the dashboard.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can create galleries', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('create_galleries_roles', $options['create_galleries_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Publish Galleries Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Publish Galleries Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can publish galleries (contributors can create but not publish).', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can publish galleries', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('publish_galleries_roles', $options['publish_galleries_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Upload Images / Media Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Upload Images / Media Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Roles that can create Bulletins or Galleries automatically receive upload permission. Use this to grant uploads to additional roles.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can upload media', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('upload_media_roles', $options['upload_media_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <!-- Manage Bulletin Banners Permission -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Manage Bulletin Banners Permission', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which roles can enable site-wide bulletin banners from the frontend dashboard. This is a high-impact feature that displays banners to all users. Only trusted roles should have this permission.', 'videohub360-theme'); ?></p>
            
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Roles that can manage bulletin banners', 'videohub360-theme'); ?></legend>
                <?php vh360_render_permission_roles('bulletin_banner_roles', $options['bulletin_banner_roles'], $all_roles); ?>
            </fieldset>
        </div>
        
        <?php submit_button(__('Save Permissions', 'videohub360-theme')); ?>
    </form>
</div>

<?php include VH360_THEME_DIR . '/includes/admin/partials/footer.php'; ?>
