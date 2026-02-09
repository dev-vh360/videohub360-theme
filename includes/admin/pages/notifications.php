<?php
/**
 * Notification Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Notification Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_notification_options', array());
$defaults = array(
    'enable_system' => true,
    'polling_interval' => 30,
    'max_per_user' => 100,
    'retention_days' => 30,
    'enable_types' => array(
        'follow' => true,
        'like' => true,
        'comment' => true,
        'reply' => true,
        'mention' => true,
        'share' => true,
    ),
    'enable_caching' => true,
    'cleanup_schedule' => 'daily',
);
$options = wp_parse_args($options, $defaults);

// Get notification statistics
global $wpdb;
$system = VH360_Notification_System::get_instance();
$table_name = $system->get_table_name();
$total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
$unread_notifications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_read = 0");
?>

<div class="vh360-admin-settings">
    
    <!-- Statistics Card -->
    <div class="vh360-admin-card vh360-admin-stats">
        <h2><?php esc_html_e('Notification Statistics', 'videohub360-theme'); ?></h2>
        <div class="vh360-stats-grid">
            <div class="vh360-stat-item">
                <div class="vh360-stat-value"><?php echo esc_html(number_format_i18n($total_notifications)); ?></div>
                <div class="vh360-stat-label"><?php esc_html_e('Total Notifications', 'videohub360-theme'); ?></div>
            </div>
            <div class="vh360-stat-item">
                <div class="vh360-stat-value"><?php echo esc_html(number_format_i18n($unread_notifications)); ?></div>
                <div class="vh360-stat-label"><?php esc_html_e('Unread Notifications', 'videohub360-theme'); ?></div>
            </div>
            <div class="vh360-stat-item">
                <div class="vh360-stat-value"><?php echo esc_html($options['retention_days']); ?></div>
                <div class="vh360-stat-label"><?php esc_html_e('Retention Days', 'videohub360-theme'); ?></div>
            </div>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_notification_settings'); ?>
        
        <!-- System Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('System Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure the main notification system settings.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Notification System', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_system]" value="1" <?php checked($options['enable_system'], true); ?>>
                                <?php esc_html_e('Enable the in-app notification system globally', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When disabled, no new notifications will be created.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Polling Interval', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_notification_options[polling_interval]" value="<?php echo esc_attr($options['polling_interval']); ?>" min="10" max="300" class="small-text"> <?php esc_html_e('seconds', 'videohub360-theme'); ?>
                            <p class="description"><?php esc_html_e('How often to check for new notifications (minimum 10 seconds).', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Maximum per User', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_notification_options[max_per_user]" value="<?php echo esc_attr($options['max_per_user']); ?>" min="10" max="1000" class="small-text">
                            <p class="description"><?php esc_html_e('Maximum number of notifications to keep per user (10-1000).', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Retention Period', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_notification_options[retention_days]" value="<?php echo esc_attr($options['retention_days']); ?>" min="1" max="365" class="small-text"> <?php esc_html_e('days', 'videohub360-theme'); ?>
                            <p class="description"><?php esc_html_e('How long to keep notifications before automatic deletion (1-365 days).', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Notification Types -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Notification Types', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable specific notification types globally.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Follow Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][follow]" value="1" <?php checked(isset($options['enable_types']['follow']) ? $options['enable_types']['follow'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone follows them', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Like Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][like]" value="1" <?php checked(isset($options['enable_types']['like']) ? $options['enable_types']['like'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone likes their content', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Comment Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][comment]" value="1" <?php checked(isset($options['enable_types']['comment']) ? $options['enable_types']['comment'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone comments on their posts', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Reply Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][reply]" value="1" <?php checked(isset($options['enable_types']['reply']) ? $options['enable_types']['reply'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone replies to their comments', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Mention Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][mention]" value="1" <?php checked(isset($options['enable_types']['mention']) ? $options['enable_types']['mention'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone mentions them', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Share Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_types][share]" value="1" <?php checked(isset($options['enable_types']['share']) ? $options['enable_types']['share'] : true, true); ?>>
                                <?php esc_html_e('Notify users when someone shares their content', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Performance Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Performance Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Optimize notification system performance.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Caching', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_notification_options[enable_caching]" value="1" <?php checked($options['enable_caching'], true); ?>>
                                <?php esc_html_e('Cache notification counts and queries for better performance', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Recommended for sites with many users.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Cleanup Schedule', 'videohub360-theme'); ?></th>
                        <td>
                            <select name="vh360_notification_options[cleanup_schedule]">
                                <option value="hourly" <?php selected($options['cleanup_schedule'], 'hourly'); ?>><?php esc_html_e('Hourly', 'videohub360-theme'); ?></option>
                                <option value="twicedaily" <?php selected($options['cleanup_schedule'], 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'videohub360-theme'); ?></option>
                                <option value="daily" <?php selected($options['cleanup_schedule'], 'daily'); ?>><?php esc_html_e('Daily', 'videohub360-theme'); ?></option>
                                <option value="weekly" <?php selected($options['cleanup_schedule'], 'weekly'); ?>><?php esc_html_e('Weekly', 'videohub360-theme'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('How often to run automatic cleanup of old notifications.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Database Tools -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Database Tools', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Manage notification database and cleanup.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Manual Cleanup', 'videohub360-theme'); ?></th>
                        <td>
                            <button type="button" class="button button-secondary" id="vh360-manual-cleanup">
                                <?php esc_html_e('Run Cleanup Now', 'videohub360-theme'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Delete notifications older than the retention period.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Reset Notifications', 'videohub360-theme'); ?></th>
                        <td>
                            <button type="button" class="button button-secondary" id="vh360-reset-notifications" style="color: #dc3232;">
                                <?php esc_html_e('Delete All Notifications', 'videohub360-theme'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Permanently delete all notifications from the database. This cannot be undone.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
    </form>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Manual cleanup
    $('#vh360-manual-cleanup').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to run cleanup now?', 'videohub360-theme')); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Running...', 'videohub360-theme')); ?>');
        
        $.post(ajaxurl, {
            action: 'vh360_manual_notification_cleanup',
            nonce: '<?php echo esc_js(wp_create_nonce('vh360_admin_nonce')); ?>'
        }, function(response) {
            $btn.prop('disabled', false).text(originalText);
            alert(response.data.message || '<?php echo esc_js(__('Cleanup completed.', 'videohub360-theme')); ?>');
            location.reload();
        });
    });
    
    // Reset all notifications
    $('#vh360-reset-notifications').on('click', function() {
        if (!confirm('<?php echo esc_js(__('WARNING: This will permanently delete ALL notifications from the database. This action cannot be undone. Are you absolutely sure?', 'videohub360-theme')); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'videohub360-theme')); ?>');
        
        $.post(ajaxurl, {
            action: 'vh360_reset_all_notifications',
            nonce: '<?php echo esc_js(wp_create_nonce('vh360_admin_nonce')); ?>'
        }, function(response) {
            $btn.prop('disabled', false).text(originalText);
            alert(response.data.message || '<?php echo esc_js(__('All notifications deleted.', 'videohub360-theme')); ?>');
            location.reload();
        });
    });
});
</script>

<?php include VH360_THEME_DIR . '/includes/admin/partials/footer.php'; ?>
