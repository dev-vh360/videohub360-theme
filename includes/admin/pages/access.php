<?php
/**
 * Template Visibility Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Template Visibility Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

// Get current settings
$options = vh360_get_template_visibility_settings();

// Get targets from registry
$targets = vh360_get_access_control_targets();
?>

<div class="vh360-admin-settings">
    
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Template Visibility Control', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Control which community templates require users to be logged in. Pages that require login will redirect guest visitors to the login page.', 'videohub360-theme'); ?></p>
        
        <div class="notice notice-info inline">
            <p><?php esc_html_e('Note: Login, Register, Lost Password, and Reset Password pages are always public to prevent redirect loops.', 'videohub360-theme'); ?></p>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_access_settings'); ?>
        
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Page Template Access', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure access requirements for each community template below.', 'videohub360-theme'); ?></p>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ($targets as $key => $target) : ?>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html($target['label']); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php 
                                            /* translators: %s: template name */
                                            printf(esc_html__('Require login for %s', 'videohub360-theme'), esc_html($target['label'])); 
                                        ?></span>
                                    </legend>
                                    <label for="vh360_access_<?php echo esc_attr($key); ?>">
                                        <input 
                                            type="checkbox" 
                                            id="vh360_access_<?php echo esc_attr($key); ?>"
                                            name="vh360_access_options[<?php echo esc_attr($key); ?>]" 
                                            value="1" 
                                            <?php checked(!empty($options[$key]), true); ?>
                                        />
                                        <?php esc_html_e('Require login to access this template', 'videohub360-theme'); ?>
                                    </label>
                                    <?php if (isset($target['type']) && $target['type'] === 'page_template') : ?>
                                        <p class="description">
                                            <?php 
                                            /* translators: %s: template filename */
                                            printf(
                                                esc_html__('Template file: %s', 'videohub360-theme'), 
                                                '<code>' . esc_html($target['template']) . '</code>'
                                            ); 
                                            ?>
                                        </p>
                                    <?php elseif (isset($target['type']) && $target['type'] === 'author_archive') : ?>
                                        <p class="description">
                                            <?php esc_html_e('Applies to all author/profile archive pages.', 'videohub360-theme'); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Recommended Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('For the best balance between privacy and community discoverability:', 'videohub360-theme'); ?></p>
            <ul>
                <li><?php esc_html_e('Dashboard: Require login (account management page)', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Profile Edit: Require login (account management page)', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Members Directory: Public (lets visitors discover your community)', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Activity Feed: Require login or public based on your community style', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Public Profile Pages: Public (increases member visibility)', 'videohub360-theme'); ?></li>
            </ul>
        </div>
        
        <?php submit_button(__('Save Visibility Settings', 'videohub360-theme')); ?>
    </form>
</div>

<?php include VH360_THEME_DIR . '/includes/admin/partials/footer.php'; ?>
