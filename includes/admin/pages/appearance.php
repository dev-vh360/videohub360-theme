<?php
/**
 * Appearance Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Appearance Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_appearance_options', array());
$defaults = array(
    'enable_profiles' => true,
    'enable_bulletins' => true,
    'enable_activity' => true,
    'enable_members' => true,
    'enable_user_menu' => true,
    'custom_css' => '',
    'enable_minification' => false,
    'enable_lazy_loading' => true,
);
$options = wp_parse_args($options, $defaults);

// Display success message if preset was applied
if (isset($_GET['preset_applied']) && $_GET['preset_applied'] === 'success') {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Color preset applied successfully!', 'videohub360-theme') . '</p></div>';
}
?>

<div class="vh360-admin-settings">
    
    <!-- Color Presets -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Color Presets', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Apply a pre-made color scheme to your theme with one click. You can further customize colors in the Customizer.', 'videohub360-theme'); ?></p>
        
        <?php
        require_once VH360_THEME_DIR . '/includes/admin/color-presets.php';
        $presets = vh360_get_color_presets();
        $active_preset = vh360_get_active_preset();
        ?>
        
        <div class="vh360-preset-grid">
            <?php foreach ($presets as $preset_id => $preset) : ?>
                <div class="vh360-preset-card <?php echo ($preset_id === $active_preset) ? 'active' : ''; ?>">
                    <div class="vh360-preset-colors">
                        <span class="vh360-preset-color" style="background-color: <?php echo esc_attr($preset['colors']['vh360_primary_color']); ?>"></span>
                        <span class="vh360-preset-color" style="background-color: <?php echo esc_attr($preset['colors']['vh360_secondary_color']); ?>"></span>
                        <span class="vh360-preset-color" style="background-color: <?php echo esc_attr($preset['colors']['vh360_success_color']); ?>"></span>
                    </div>
                    <h3 class="vh360-preset-name"><?php echo esc_html($preset['name']); ?></h3>
                    <?php if ($preset_id === $active_preset) : ?>
                        <span class="vh360-preset-active-badge"><?php esc_html_e('Active', 'videohub360-theme'); ?></span>
                    <?php else : ?>
                        <form method="post" action="" style="margin: 0;">
                            <?php wp_nonce_field('vh360_apply_preset', 'vh360_preset_nonce'); ?>
                            <input type="hidden" name="vh360_apply_preset" value="<?php echo esc_attr($preset_id); ?>">
                            <button type="submit" class="button button-secondary vh360-preset-apply">
                                <?php esc_html_e('Apply', 'videohub360-theme'); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_appearance_settings'); ?>
        
        <!-- Customizer Link -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('WordPress Customizer', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Use the WordPress Customizer to manage colors, fonts, logo, and other visual settings.', 'videohub360-theme'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-customizer"></span>
                    <?php esc_html_e('Open Customizer', 'videohub360-theme'); ?>
                </a>
            </p>
        </div>
        
        <!-- Feature Toggles -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Theme Features', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable major theme features. Disabled features will not load their assets or functionality.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Profile System', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_profiles]" value="1" <?php checked($options['enable_profiles'], true); ?>>
                                <?php esc_html_e('Enable user profiles with custom fields and avatars', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Bulletin System', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_bulletins]" value="1" <?php checked($options['enable_bulletins'], true); ?>>
                                <?php esc_html_e('Enable bulletin board for announcements and updates', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Activity Tracking', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_activity]" value="1" <?php checked($options['enable_activity'], true); ?>>
                                <?php esc_html_e('Track and display user activities in activity feed', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Members Directory', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_members]" value="1" <?php checked($options['enable_members'], true); ?>>
                                <?php esc_html_e('Enable searchable members directory page', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('User Menu System', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_user_menu]" value="1" <?php checked($options['enable_user_menu'], true); ?>>
                                <?php esc_html_e('Enable customizable user dropdown menu in header', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Performance Options -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Performance Options', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure performance-related settings to optimize your site speed.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Asset Minification', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_minification]" value="1" <?php checked($options['enable_minification'], true); ?>>
                                <?php esc_html_e('Enable CSS and JS minification (requires file writing permissions)', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Lazy Loading', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_appearance_options[enable_lazy_loading]" value="1" <?php checked($options['enable_lazy_loading'], true); ?>>
                                <?php esc_html_e('Enable lazy loading for images (improves page load time)', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Custom CSS -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Custom CSS', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Add custom CSS to customize your theme appearance. This CSS will be loaded on all pages.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Custom CSS Code', 'videohub360-theme'); ?></th>
                        <td>
                            <textarea name="vh360_appearance_options[custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($options['custom_css']); ?></textarea>
                            <p class="description"><?php esc_html_e('Add your custom CSS here. Do not include <style> tags.', 'videohub360-theme'); ?></p>
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
