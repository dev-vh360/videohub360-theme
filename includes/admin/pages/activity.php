<?php
/**
 * Activity Feed Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Activity Feed Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_activity_options', array());
$defaults = array(
    'enable_tracking' => true,
    'track_types' => array('video_upload', 'new_member', 'profile_update', 'milestone'),
    'retention_days' => 30,
    'per_page' => 20,
);
$options = wp_parse_args($options, $defaults);

// Community uploads settings
$upload_options = get_option('vh360_community_uploads_options', array());
$upload_defaults = array(
    'enable_photos' => true,
    'enable_videos' => false,
    'photo_max_size' => 5,
    'video_max_size' => 50,
    'allowed_video_formats' => array('mp4'),
);
$upload_options = wp_parse_args($upload_options, $upload_defaults);

// Get activity statistics
$activities = get_option('vh360_activity_feed', array());
$total_activities = is_array($activities) ? count($activities) : 0;

$type_counts = array(
    'video_upload' => 0,
    'new_member' => 0,
    'profile_update' => 0,
    'milestone' => 0,
);

if (is_array($activities)) {
    foreach ($activities as $activity) {
        if (isset($activity['type']) && isset($type_counts[$activity['type']])) {
            $type_counts[$activity['type']]++;
        }
    }
}
?>

<div class="vh360-admin-settings">
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_activity_settings'); ?>
        
        <!-- Activity Tracking Toggle -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Activity Tracking', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable activity tracking across the site.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Tracking', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_activity_options[enable_tracking]" value="1" <?php checked($options['enable_tracking'], true); ?>>
                                <?php esc_html_e('Track and display user activities in the activity feed', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Activity Types -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Activity Types to Track', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which types of activities should be tracked and displayed.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Track Activity Types', 'videohub360-theme'); ?></th>
                        <td>
                            <?php
                            $activity_types = array(
                                'video_upload' => __('Video Uploads', 'videohub360-theme'),
                                'new_member' => __('New Member Registrations', 'videohub360-theme'),
                                'profile_update' => __('Profile Updates', 'videohub360-theme'),
                                'milestone' => __('Milestones', 'videohub360-theme'),
                            );
                            
                            foreach ($activity_types as $key => $label) :
                                $checked = in_array($key, $options['track_types']);
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="vh360_activity_options[track_types][]" value="<?php echo esc_attr($key); ?>" <?php checked($checked, true); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Select which activities to track and display in the activity feed.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Retention and Display -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Retention and Display Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure how long activities are stored and how many are displayed per page.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Activity Retention Period', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_activity_options[retention_days]" value="<?php echo esc_attr($options['retention_days']); ?>" min="7" max="365" class="small-text">
                            <span><?php esc_html_e('days', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Number of days to keep activities before they can be cleared (7-365 days)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Activities Per Page', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_activity_options[per_page]" value="<?php echo esc_attr($options['per_page']); ?>" min="5" max="100" class="small-text">
                            <p class="description"><?php esc_html_e('Number of activities to display per page (5-100)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Community Post Uploads -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Community Post Uploads', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure photo and video upload settings for community posts.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Photo Uploads', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_community_uploads_options[enable_photos]" value="1" <?php checked($upload_options['enable_photos'], true); ?>>
                                <?php esc_html_e('Allow users to upload photos to community posts', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Video Uploads', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_community_uploads_options[enable_videos]" value="1" <?php checked($upload_options['enable_videos'], true); ?>>
                                <?php esc_html_e('Allow users to upload videos to community posts', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max Photo Size', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_community_uploads_options[photo_max_size]" value="<?php echo esc_attr($upload_options['photo_max_size']); ?>" min="1" max="10" class="small-text">
                            <span><?php esc_html_e('MB', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Maximum file size for photo uploads (1-10 MB)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Max Video Size', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_community_uploads_options[video_max_size]" value="<?php echo esc_attr($upload_options['video_max_size']); ?>" min="1" max="100" class="small-text">
                            <span><?php esc_html_e('MB', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Maximum file size for video uploads (1-100 MB)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed Video Formats', 'videohub360-theme'); ?></th>
                        <td>
                            <?php
                            $video_formats = array(
                                'mp4' => __('MP4 (.mp4)', 'videohub360-theme'),
                                'webm' => __('WebM (.webm)', 'videohub360-theme'),
                                'ogv' => __('OGV (.ogv)', 'videohub360-theme'),
                            );
                            
                            foreach ($video_formats as $format_key => $format_label) :
                                $format_checked = in_array($format_key, $upload_options['allowed_video_formats'], true);
                                ?>
                                <label class="vh360-checkbox-inline">
                                    <input type="checkbox" name="vh360_community_uploads_options[allowed_video_formats][]" value="<?php echo esc_attr($format_key); ?>" <?php checked($format_checked, true); ?>>
                                    <?php echo esc_html($format_label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Select which video formats users can upload. At least one format must be selected.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(); ?>
        
    </form>
    
    <!-- Activity Statistics -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Activity Statistics', 'videohub360-theme'); ?></h2>
        
        <div class="vh360-stats-grid">
            <?php
            $icon = 'dashicons-chart-line';
            $label = __('Total Activities', 'videohub360-theme');
            $value = number_format_i18n($total_activities);
            $status = 'info';
            $link = '';
            include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
            
            $icon = 'dashicons-video-alt3';
            $label = __('Video Uploads', 'videohub360-theme');
            $value = number_format_i18n($type_counts['video_upload']);
            $status = 'default';
            $link = '';
            include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
            
            $icon = 'dashicons-admin-users';
            $label = __('New Members', 'videohub360-theme');
            $value = number_format_i18n($type_counts['new_member']);
            $status = 'success';
            $link = '';
            include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
            
            $icon = 'dashicons-star-filled';
            $label = __('Milestones', 'videohub360-theme');
            $value = number_format_i18n($type_counts['milestone']);
            $status = 'warning';
            $link = '';
            include VH360_THEME_DIR . '/includes/admin/partials/stats-card.php';
            ?>
        </div>
    </div>
    
    <!-- Clear Old Activities -->
    <div class="vh360-admin-card">
        <h2><?php esc_html_e('Clear Old Activities', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Remove activities older than the retention period to free up database space.', 'videohub360-theme'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('vh360_admin_action', 'vh360_admin_nonce'); ?>
            <input type="hidden" name="vh360_admin_action" value="clear_activities">
            <button type="submit" class="button button-secondary vh360-confirm-action" data-confirm="<?php esc_attr_e('Are you sure you want to clear old activities? This action cannot be undone.', 'videohub360-theme'); ?>">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear Old Activities', 'videohub360-theme'); ?>
            </button>
            <p class="description">
                <?php
                printf(
                    /* translators: %d: number of days */
                    esc_html__('This will remove activities older than %d days.', 'videohub360-theme'),
                    absint($options['retention_days'])
                );
                ?>
            </p>
        </form>
    </div>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
