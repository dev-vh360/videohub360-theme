<?php
/**
 * Members Directory Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Members Directory Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_members_options', array());
$defaults = array(
    'enable_directory' => true,
    'per_page' => 24,
    'default_sort' => 'newest',
    'enable_search' => true,
    'visible_roles' => array('subscriber', 'contributor', 'author', 'editor', 'administrator'),
);
$options = wp_parse_args($options, $defaults);
?>

<div class="vh360-admin-settings">
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_members_settings'); ?>
        
        <!-- Members Directory Toggle -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Members Directory', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable the members directory feature.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Directory', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[enable_directory]" value="1" <?php checked($options['enable_directory'], true); ?>>
                                <?php esc_html_e('Enable the searchable members directory page', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Display Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Display Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure how members are displayed in the directory.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Members Per Page', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_members_options[per_page]" value="<?php echo esc_attr($options['per_page']); ?>" min="6" max="100" class="small-text">
                            <p class="description"><?php esc_html_e('Number of members to display per page (6-100, recommended: 12, 24, or 48)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Default Sorting', 'videohub360-theme'); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="newest" <?php checked($options['default_sort'], 'newest'); ?>>
                                <?php esc_html_e('Newest First', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="oldest" <?php checked($options['default_sort'], 'oldest'); ?>>
                                <?php esc_html_e('Oldest First', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="active" <?php checked($options['default_sort'], 'active'); ?>>
                                <?php esc_html_e('Most Active', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="alphabetical" <?php checked($options['default_sort'], 'alphabetical'); ?>>
                                <?php esc_html_e('Alphabetical', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Default sorting order for the members directory', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Search Functionality', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[enable_search]" value="1" <?php checked($options['enable_search'], true); ?>>
                                <?php esc_html_e('Enable search box in members directory', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Role Filter Options -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Role Filter Options', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which user roles should be visible in the members directory.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Visible Roles', 'videohub360-theme'); ?></th>
                        <td>
                            <?php
                            global $wp_roles;
                            $all_roles = $wp_roles->roles;
                            
                            foreach ($all_roles as $role_key => $role_data) :
                                $checked = in_array($role_key, $options['visible_roles']);
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="vh360_members_options[visible_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked($checked, true); ?>>
                                    <?php echo esc_html($role_data['name']); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Select which user roles should appear in the members directory', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(); ?>
        
    </form>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
