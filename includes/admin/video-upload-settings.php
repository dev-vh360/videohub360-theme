<?php
/**
 * Add Video Form Settings Page
 *
 * Admin settings page for controlling frontend Add Video form sections and video uploads.
 * Located at VH360 Theme → Add Video / Lesson.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Video Form Settings submenu under VH360 Theme
 */
function vh360_video_upload_add_admin_menu() {
    add_submenu_page(
        'vh360-theme',
        __('Add Video / Lesson Settings', 'videohub360-theme'),
        __('Add Video Form', 'videohub360-theme'),
        'manage_options',
        'vh360-video-upload-settings',
        'vh360_video_upload_settings_page'
    );
}
add_action('admin_menu', 'vh360_video_upload_add_admin_menu');

/**
 * Register settings
 */
function vh360_video_upload_settings_init() {
    register_setting(
        'vh360_video_upload',
        'vh360_video_upload_options',
        array(
            'sanitize_callback' => 'vh360_video_upload_sanitize_options',
        )
    );

    register_setting(
        'vh360_video_upload',
        'vh360_create_form_options',
        array(
            'sanitize_callback' => 'vh360_create_form_sanitize_options',
        )
    );

    add_settings_section(
        'vh360_create_form_sections',
        __('Add Video Form Sections', 'videohub360-theme'),
        'vh360_create_form_sections_callback',
        'vh360_video_upload'
    );

    add_settings_field(
        'show_ad_settings',
        __('Show Ad Settings', 'videohub360-theme'),
        'vh360_create_form_show_ads_render',
        'vh360_video_upload',
        'vh360_create_form_sections'
    );

    add_settings_field(
        'show_advanced_settings',
        __('Show Advanced Settings', 'videohub360-theme'),
        'vh360_create_form_show_advanced_render',
        'vh360_video_upload',
        'vh360_create_form_sections'
    );

    add_settings_section(
        'vh360_video_upload_section',
        __('Video Upload Settings', 'videohub360-theme'),
        'vh360_video_upload_section_callback',
        'vh360_video_upload'
    );

    add_settings_field(
        'enable_video_upload',
        __('Enable Video Upload', 'videohub360-theme'),
        'vh360_video_upload_enable_render',
        'vh360_video_upload',
        'vh360_video_upload_section'
    );

    add_settings_field(
        'max_file_size',
        __('Maximum File Size (MB)', 'videohub360-theme'),
        'vh360_video_upload_max_size_render',
        'vh360_video_upload',
        'vh360_video_upload_section'
    );

    add_settings_field(
        'allowed_formats',
        __('Allowed Video Formats', 'videohub360-theme'),
        'vh360_video_upload_formats_render',
        'vh360_video_upload',
        'vh360_video_upload_section'
    );
}
add_action('admin_init', 'vh360_video_upload_settings_init');


/**
 * Sanitize Add Video form section settings
 */
function vh360_create_form_sanitize_options($input) {
    return array(
        'show_ad_settings' => !empty($input['show_ad_settings']) ? 1 : 0,
        'show_advanced_settings' => !empty($input['show_advanced_settings']) ? 1 : 0,
    );
}

/**
 * Add Video form sections callback
 */
function vh360_create_form_sections_callback() {
    ?>
    <p><?php esc_html_e('Control whether Ad Settings and Advanced Settings appear in the frontend Add Video / Lesson form.', 'videohub360-theme'); ?></p>
    <?php
}

function vh360_create_form_show_ads_render() {
    $options = vh360_get_create_form_options();
    ?>
    <label>
        <input type="checkbox" name="vh360_create_form_options[show_ad_settings]" value="1" <?php checked($options['show_ad_settings'], 1); ?>>
        <?php esc_html_e('Display the Ad Settings section in the frontend Add Video / Lesson form.', 'videohub360-theme'); ?>
    </label>
    <?php
}

function vh360_create_form_show_advanced_render() {
    $options = vh360_get_create_form_options();
    ?>
    <label>
        <input type="checkbox" name="vh360_create_form_options[show_advanced_settings]" value="1" <?php checked($options['show_advanced_settings'], 1); ?>>
        <?php esc_html_e('Display the Advanced Settings section in the frontend Add Video / Lesson form.', 'videohub360-theme'); ?>
    </label>
    <?php
}

/**
 * Sanitize settings
 */
function vh360_video_upload_sanitize_options($input) {
    $sanitized = array();

    // Enable video upload
    $sanitized['enable_video_upload'] = !empty($input['enable_video_upload']) ? 1 : 0;

    // Max file size (10 MB - 2048 MB)
    $max_size = isset($input['max_file_size']) ? absint($input['max_file_size']) : 500;
    $max_size = max(10, min(2048, $max_size));
    
    // Check against server limits
    $server_max = vh360_get_server_max_upload_size();
    if ($max_size > $server_max) {
        add_settings_error(
            'vh360_video_upload_options',
            'max_file_size_exceeds_server',
            sprintf(
                __('Maximum file size has been adjusted to %d MB to match your server limit.', 'videohub360-theme'),
                $server_max
            ),
            'warning'
        );
        $max_size = $server_max;
    }
    
    $sanitized['max_file_size'] = $max_size;

    // Allowed formats - use whitelist of common video formats
    $valid_formats = array('mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'wmv', 'mpeg', 'mpg', 'ogv', 'm4v', '3gp');
    
    $formats = isset($input['allowed_formats']) ? sanitize_text_field($input['allowed_formats']) : 'mp4,webm,mov';
    $formats_array = array_map('trim', explode(',', strtolower($formats)));
    
    // Filter to only allow valid video formats
    $formats_array = array_filter($formats_array, function($format) use ($valid_formats) {
        return in_array($format, $valid_formats, true);
    });
    
    // If no valid formats, use default
    if (empty($formats_array)) {
        $formats_array = array('mp4', 'webm', 'mov');
    }
    
    $sanitized['allowed_formats'] = implode(',', $formats_array);

    return $sanitized;
}

/**
 * Section callback
 */
function vh360_video_upload_section_callback() {
    ?>
    <p><?php esc_html_e('Configure video file upload settings for the frontend Add Video / Lesson form. This controls MAIN video uploads (videohub360 post type), not Activity Feed videos.', 'videohub360-theme'); ?></p>
    <div class="notice notice-info inline">
        <p>
            <strong><?php esc_html_e('Server Information:', 'videohub360-theme'); ?></strong><br>
            <?php
            $server_max = vh360_get_server_max_upload_size();
            $post_max = vh360_get_server_post_max_size();
            printf(
                __('PHP Upload Limit: %d MB | PHP Post Limit: %d MB', 'videohub360-theme'),
                $server_max,
                $post_max
            );
            ?>
        </p>
        <?php if ($server_max < 500): ?>
        <p>
            <em><?php esc_html_e('Note: Your server upload limit is lower than the recommended 500 MB. You may need to increase PHP upload_max_filesize and post_max_size in your php.ini or contact your hosting provider.', 'videohub360-theme'); ?></em>
        </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Enable video upload field
 */
function vh360_video_upload_enable_render() {
    $options = get_option('vh360_video_upload_options', array());
    $enabled = isset($options['enable_video_upload']) ? $options['enable_video_upload'] : 1;
    ?>
    <label>
        <input type="checkbox" name="vh360_video_upload_options[enable_video_upload]" value="1" <?php checked($enabled, 1); ?>>
        <?php esc_html_e('Allow users to upload video files directly from the Add Video / Lesson form', 'videohub360-theme'); ?>
    </label>
    <p class="description">
        <?php esc_html_e('When enabled, users will see an upload option in the Video Source / Lesson Video section.', 'videohub360-theme'); ?>
    </p>
    <?php
}

/**
 * Max file size field
 */
function vh360_video_upload_max_size_render() {
    $options = get_option('vh360_video_upload_options', array());
    $max_size = isset($options['max_file_size']) ? $options['max_file_size'] : 500;
    $server_max = vh360_get_server_max_upload_size();
    ?>
    <input type="number" name="vh360_video_upload_options[max_file_size]" value="<?php echo esc_attr($max_size); ?>" min="10" max="2048" step="1" class="regular-text">
    <p class="description">
        <?php
        printf(
            __('Maximum video file size in megabytes (10-2048 MB). Server limit: %d MB', 'videohub360-theme'),
            $server_max
        );
        ?>
    </p>
    <?php
}

/**
 * Allowed formats field
 */
function vh360_video_upload_formats_render() {
    $options = get_option('vh360_video_upload_options', array());
    $formats = isset($options['allowed_formats']) ? $options['allowed_formats'] : 'mp4,webm,mov';
    ?>
    <input type="text" name="vh360_video_upload_options[allowed_formats]" value="<?php echo esc_attr($formats); ?>" class="regular-text">
    <p class="description">
        <?php esc_html_e('Comma-separated list of allowed video file extensions. Common formats: mp4, webm, mov, avi, mkv, flv', 'videohub360-theme'); ?>
    </p>
    <?php
}

/**
 * Settings page HTML
 */
function vh360_video_upload_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if settings were saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'vh360_video_upload_messages',
            'vh360_video_upload_message',
            __('Settings Saved', 'videohub360-theme'),
            'success'
        );
    }

    settings_errors('vh360_video_upload_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('vh360_video_upload');
            do_settings_sections('vh360_video_upload');
            submit_button(__('Save Settings', 'videohub360-theme'));
            ?>
        </form>

        <hr>

        <div class="vh360-help-section">
            <h2><?php esc_html_e('How It Works', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('When video upload is enabled:', 'videohub360-theme'); ?></p>
            <ol>
                <li><?php esc_html_e('Users will see three options in the "Video Source" section: Video URL, Upload Video File, and Embed Code', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Selecting "Upload Video File" reveals a file picker with upload button', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('After selecting a video file, a progress bar shows upload progress', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('On successful upload, the video URL field is automatically populated', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Uploaded videos are stored in the WordPress Media Library', 'videohub360-theme'); ?></li>
            </ol>

            <h3><?php esc_html_e('Important Notes', 'videohub360-theme'); ?></h3>
            <ul>
                <li><?php esc_html_e('This feature requires users to have the "upload_files" capability', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('File size and format restrictions are enforced on both client and server side', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('These settings control MAIN video uploads (videohub360 post type), not Activity Feed videos', 'videohub360-theme'); ?></li>
                <li><?php esc_html_e('Server PHP settings (upload_max_filesize, post_max_size) must be configured appropriately', 'videohub360-theme'); ?></li>
            </ul>
        </div>
    </div>

    <style>
        .vh360-help-section {
            max-width: 800px;
            margin-top: 2rem;
        }
        .vh360-help-section h2 {
            margin-top: 0;
        }
        .vh360-help-section ol,
        .vh360-help-section ul {
            line-height: 1.8;
        }
    </style>
    <?php
}

/**
 * Get server max upload size in MB
 */
function vh360_get_server_max_upload_size() {
    $max_upload = wp_max_upload_size();
    return floor($max_upload / 1024 / 1024);
}

/**
 * Get server post max size in MB
 */
function vh360_get_server_post_max_size() {
    $post_max = ini_get('post_max_size');
    return vh360_parse_size($post_max);
}

/**
 * Parse size string to MB
 */
function vh360_parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])) / 1024 / 1024);
    }
    return round($size / 1024 / 1024);
}
