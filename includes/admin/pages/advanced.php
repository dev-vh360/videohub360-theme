<?php
/**
 * Advanced Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Advanced Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_advanced_options', array());
$defaults = array(
    'debug_mode' => false,
    'enable_logging' => false,
    'show_deprecated' => false,
    'transient_expiration' => 3600,
);
$options = wp_parse_args($options, $defaults);
?>

<div class="vh360-admin-settings">
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_advanced_settings'); ?>
        
        <!-- Debug Mode -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Debug Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable debugging features for development and troubleshooting. These should be disabled in production.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Debug Mode', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_advanced_options[debug_mode]" value="1" <?php checked($options['debug_mode'], true); ?>>
                                <?php esc_html_e('Enable debug mode (shows additional information for troubleshooting)', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Error Logging', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_advanced_options[enable_logging]" value="1" <?php checked($options['enable_logging'], true); ?>>
                                <?php esc_html_e('Enable error logging for theme functions', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show Deprecated Notices', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_advanced_options[show_deprecated]" value="1" <?php checked($options['show_deprecated'], true); ?>>
                                <?php esc_html_e('Show notices for deprecated theme functions', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Cache Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Cache Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure caching behavior for theme data.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Transient Expiration', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_advanced_options[transient_expiration]" value="<?php echo esc_attr($options['transient_expiration']); ?>" min="300" max="86400" class="regular-text">
                            <span><?php esc_html_e('seconds', 'videohub360-theme'); ?></span>
                            <p class="description">
                                <?php esc_html_e('How long to cache theme data (300-86400 seconds). Default: 3600 (1 hour)', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(); ?>
        
    </form>
    
    <!-- Clear Cache -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Clear Theme Cache', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Clear all theme transients and cached data. Use this if you\'re experiencing issues with outdated data.', 'videohub360-theme'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('vh360_admin_action', 'vh360_admin_nonce'); ?>
            <input type="hidden" name="vh360_admin_action" value="clear_cache">
            <button type="submit" class="button button-secondary vh360-confirm-action" data-confirm="<?php esc_attr_e('Are you sure you want to clear all theme cache?', 'videohub360-theme'); ?>">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Clear All Theme Cache', 'videohub360-theme'); ?>
            </button>
        </form>
    </div>
    
    <!-- Import/Export Theme Options -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Import/Export Theme Options', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Export or import theme admin option groups (Appearance, Profile, Activity, Members, Advanced, Access, Paid Memberships). This does not include WordPress Customizer settings.', 'videohub360-theme'); ?></p>
        
        <h3><?php esc_html_e('Export Theme Options', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('Click the button below to export theme admin option groups as a JSON file.', 'videohub360-theme'); ?></p>
        <button type="button" class="button button-secondary" id="vh360-export-settings">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export Theme Options JSON', 'videohub360-theme'); ?>
        </button>
        
        <hr style="margin: 30px 0;">
        
        <h3><?php esc_html_e('Import Theme Options', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('Upload a theme options JSON file to import admin option groups. This will overwrite your current theme option settings.', 'videohub360-theme'); ?></p>
        <form method="post" enctype="multipart/form-data" id="vh360-import-form">
            <?php wp_nonce_field('vh360_import_settings', 'vh360_import_nonce'); ?>
            <input type="file" name="vh360_import_file" accept=".json" required>
            <button type="submit" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('Import Theme Options JSON', 'videohub360-theme'); ?>
            </button>
        </form>
    </div>
    
    <!-- Import/Export Customizer Settings -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Import/Export Customizer Settings', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Export or import WordPress Customizer settings. This includes all theme modifications stored via the WordPress Customizer.', 'videohub360-theme'); ?></p>
        
        <h3><?php esc_html_e('Export Customizer Settings', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('Click the button below to export WordPress Customizer settings as customizer.json.', 'videohub360-theme'); ?></p>
        <button type="button" class="button button-secondary" id="vh360-export-customizer">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export Customizer JSON', 'videohub360-theme'); ?>
        </button>
        
        <hr style="margin: 30px 0;">
        
        <h3><?php esc_html_e('Import Customizer Settings', 'videohub360-theme'); ?></h3>
        <p><?php esc_html_e('Upload a customizer.json file to import Customizer settings. This will overwrite your current Customizer configuration.', 'videohub360-theme'); ?></p>
        <form method="post" enctype="multipart/form-data" id="vh360-import-customizer-form">
            <?php wp_nonce_field('vh360_import_customizer', 'vh360_import_customizer_nonce'); ?>
            <input type="file" name="vh360_import_customizer_file" accept=".json" required>
            <button type="submit" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('Import Customizer JSON', 'videohub360-theme'); ?>
            </button>
        </form>
    </div>
    
    <!-- Reset Settings -->
    <div class="vh360-admin-card vh360-danger-zone">
        <h2><?php esc_html_e('Danger Zone', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('These actions are destructive and cannot be undone. Use with caution.', 'videohub360-theme'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('vh360_admin_action', 'vh360_admin_nonce'); ?>
            <input type="hidden" name="vh360_admin_action" value="reset_settings">
            <button type="submit" class="button button-danger vh360-confirm-action" data-confirm="<?php esc_attr_e('Are you sure you want to reset ALL theme settings to defaults? This action cannot be undone and will affect all theme configuration.', 'videohub360-theme'); ?>">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e('Reset All Theme Settings', 'videohub360-theme'); ?>
            </button>
            <p class="description">
                <?php esc_html_e('This will reset all theme settings to their default values. Your pages and content will not be affected.', 'videohub360-theme'); ?>
            </p>
        </form>
    </div>
    
    <!-- Database Optimization -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Database Information', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Theme database usage and optimization options.', 'videohub360-theme'); ?></p>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Option Name', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Size', 'videohub360-theme'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $theme_options = array(
                    'vh360_appearance_options',
                    'vh360_profile_options',
                    'vh360_activity_options',
                    'vh360_members_options',
                    'vh360_advanced_options',
                    'vh360_activity_feed',
                );
                
                foreach ($theme_options as $option_name) {
                    $option_value = get_option($option_name, '');
                    $size = strlen(maybe_serialize($option_value));
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($option_name); ?></code></td>
                        <td><?php echo esc_html(size_format($size, 2)); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Export theme options
    $('#vh360-export-settings').on('click', function() {
        var settings = {
            appearance: <?php echo wp_json_encode(get_option('vh360_appearance_options', array())); ?>,
            profile: <?php echo wp_json_encode(get_option('vh360_profile_options', array())); ?>,
            activity: <?php echo wp_json_encode(get_option('vh360_activity_options', array())); ?>,
            members: <?php echo wp_json_encode(get_option('vh360_members_options', array())); ?>,
            advanced: <?php echo wp_json_encode(get_option('vh360_advanced_options', array())); ?>,
            access: <?php echo wp_json_encode(get_option('vh360_access_options', array())); ?>,
            membership: <?php echo wp_json_encode(get_option('vh360_membership_options', array())); ?>,
            videohub360_core: {
                enable_course_features: <?php echo wp_json_encode( (int) get_option( 'videohub360_enable_course_features', 0 ) ); ?>
            },
        };
        
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "vh360-theme-settings-" + Date.now() + ".json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });
    
    // Import theme options - use nonce from localized script
    $('#vh360-import-form').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $(this).find('input[type="file"]')[0];
        if (fileInput.files.length === 0) {
            alert('<?php esc_html_e('Please select a file to import.', 'videohub360-theme'); ?>');
            return;
        }
        
        var file = fileInput.files[0];
        var reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                var settings = JSON.parse(e.target.result);
                
                // Confirm import
                if (!confirm('<?php esc_html_e('Are you sure you want to import these settings? This will overwrite your current configuration.', 'videohub360-theme'); ?>')) {
                    return;
                }
                
                // Send to server via AJAX - use nonce from localized data
                $.post(vh360Admin.ajaxUrl, {
                    action: 'vh360_import_settings',
                    nonce: vh360Admin.importNonce,
                    settings: settings
                }, function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Settings imported successfully!', 'videohub360-theme'); ?>');
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Error importing settings: ', 'videohub360-theme'); ?>' + response.data);
                    }
                });
                
            } catch (error) {
                alert('<?php esc_html_e('Invalid JSON file. Please make sure you are uploading a valid settings export file.', 'videohub360-theme'); ?>');
            }
        };
        
        reader.readAsText(file);
    });
    
    // Export Customizer settings
    $('#vh360-export-customizer').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        
        $.post(vh360Admin.ajaxUrl, {
            action: 'vh360_export_customizer',
            nonce: vh360Admin.nonce
        }, function(response) {
            button.prop('disabled', false);
            
            if (response.success) {
                // Create download
                var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data, null, 2));
                var downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", "customizer.json");
                document.body.appendChild(downloadAnchorNode);
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            } else {
                alert('<?php esc_html_e('Error exporting Customizer settings: ', 'videohub360-theme'); ?>' + response.data);
            }
        }).fail(function() {
            button.prop('disabled', false);
            alert('<?php esc_html_e('Failed to export Customizer settings.', 'videohub360-theme'); ?>');
        });
    });
    
    // Import Customizer settings
    $('#vh360-import-customizer-form').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $(this).find('input[type="file"]')[0];
        if (fileInput.files.length === 0) {
            alert('<?php esc_html_e('Please select a file to import.', 'videohub360-theme'); ?>');
            return;
        }
        
        var file = fileInput.files[0];
        var reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                var customizerData = JSON.parse(e.target.result);
                
                // Confirm import
                if (!confirm('<?php esc_html_e('Are you sure you want to import Customizer settings? This will overwrite your current Customizer configuration.', 'videohub360-theme'); ?>')) {
                    return;
                }
                
                // Send to server via AJAX
                $.post(vh360Admin.ajaxUrl, {
                    action: 'vh360_import_customizer',
                    nonce: vh360Admin.customizerImportNonce,
                    customizer_data: customizerData
                }, function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Customizer settings imported successfully!', 'videohub360-theme'); ?>');
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Error importing Customizer settings: ', 'videohub360-theme'); ?>' + response.data);
                    }
                });
                
            } catch (error) {
                alert('<?php esc_html_e('Invalid JSON file. Please make sure you are uploading a valid customizer.json file.', 'videohub360-theme'); ?>');
            }
        };
        
        reader.readAsText(file);
    });
});
</script>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
