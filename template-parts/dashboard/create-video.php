<?php
/**
 * Dashboard Create Video Tab
 *
 * Comprehensive video upload form for creating regular videos
 * with full access to all VideoHub360 backend meta fields.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure only logged-in users access this tab
if (!is_user_logged_in()) {
    $vh360_login_context_is_lesson = function_exists('vh360_is_create_form_lesson_context') && vh360_is_create_form_lesson_context();
    echo '<p>' . esc_html($vh360_login_context_is_lesson ? __('You must be logged in to create lessons.', 'videohub360-theme') : __('You must be logged in to create videos.', 'videohub360-theme')) . '</p>';
    return;
}

$current_user_id = get_current_user_id();

// License soft-lock check
$vh360_is_licensed = true;
if (function_exists('vh360_theme_is_license_valid')) {
    $vh360_is_licensed = (bool) vh360_theme_is_license_valid();
} elseif (function_exists('videohub360_license_is_valid')) {
    $vh360_is_licensed = (bool) videohub360_license_is_valid();
}
$vh360_license_url = function_exists('vh360_theme_get_license_admin_url') ? vh360_theme_get_license_admin_url() : admin_url('admin.php?page=videohub360-license');

// Check user capability - use helper with administrator override
$can_create_videos = function_exists('vh360_user_can_create_videos')
    ? vh360_user_can_create_videos($current_user_id)
    : (current_user_can('manage_options') || current_user_can('vh360_create_videos'));

// Check if we're in edit mode
$edit_mode = false;
$edit_video_id = 0;
$video_data = array();

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_video_id = absint($_GET['edit']);
    $video_post = get_post($edit_video_id);
    
    // Verify user can edit this video
    if ($video_post && $video_post->post_type === 'videohub360' && $video_post->post_author == $current_user_id) {
        $edit_mode = true;
        
        // Load video data
        $video_data = array(
            'title' => $video_post->post_title,
            'content' => $video_post->post_content,
            'excerpt' => $video_post->post_excerpt,
            'status' => $video_post->post_status,
            'video_url' => get_post_meta($edit_video_id, 'video_url', true),
            'custom_html' => get_post_meta($edit_video_id, 'videohub360_custom_html', true),
            'ad_video_url' => get_post_meta($edit_video_id, 'ad_video_url', true),
            'midroll_ad_url' => get_post_meta($edit_video_id, 'midroll_ad_video_url', true),
            'midroll_timing' => get_post_meta($edit_video_id, 'midroll_ad_timing', true),
            'postroll_ad_url' => get_post_meta($edit_video_id, 'postroll_ad_video_url', true),
            'quality_override' => get_post_meta($edit_video_id, '_vh360_override_quality_settings', true),
            'video_quality' => get_post_meta($edit_video_id, '_vh360_video_quality', true),
            'video_mirror' => get_post_meta($edit_video_id, '_vh360_video_mirror', true),
            'poster_url' => get_post_meta($edit_video_id, '_vh360_poster_url', true),
            'featured_image_id' => get_post_thumbnail_id($edit_video_id),
            'featured_image_url' => get_the_post_thumbnail_url($edit_video_id, 'medium') ?: '',
        );
        
        // Get taxonomies
        $video_data['categories'] = wp_get_post_terms($edit_video_id, 'videohub360_category', array('fields' => 'ids'));
        $video_data['series'] = wp_get_post_terms($edit_video_id, 'videohub360_series', array('fields' => 'ids'));
        $video_data['locations'] = wp_get_post_terms($edit_video_id, 'videohub360_location', array('fields' => 'ids'));
        $tags = wp_get_post_terms($edit_video_id, 'post_tag', array('fields' => 'names'));
        $video_data['tags'] = !empty($tags) && !is_wp_error($tags) ? implode(', ', $tags) : '';
    }
}

// Course / Lesson feature detection
$vh360_course_features_enabled = function_exists('videohub360_course_features_enabled') && videohub360_course_features_enabled();
$vh360_is_course_mode = function_exists('vh360_get_author_template_mode')
    ? vh360_get_author_template_mode() === 'course'
    : get_theme_mod('vh360_author_template_mode', 'profile') === 'course';
$vh360_create_context_is_lesson = function_exists('vh360_is_create_form_lesson_context')
    ? vh360_is_create_form_lesson_context($current_user_id)
    : ($vh360_course_features_enabled && $vh360_is_course_mode);

$vh360_create_labels = array(
    'item' => $vh360_create_context_is_lesson ? __('Lesson', 'videohub360-theme') : __('Video', 'videohub360-theme'),
    'create_heading' => $vh360_create_context_is_lesson ? __('Create Lesson', 'videohub360-theme') : __('Create Video', 'videohub360-theme'),
    'edit_heading' => $vh360_create_context_is_lesson ? __('Edit Lesson', 'videohub360-theme') : __('Edit Video', 'videohub360-theme'),
    'title_label' => $vh360_create_context_is_lesson ? __('Lesson Title', 'videohub360-theme') : __('Video Title', 'videohub360-theme'),
    'title_placeholder' => $vh360_create_context_is_lesson ? __('Enter your lesson title', 'videohub360-theme') : __('Enter your video title', 'videohub360-theme'),
    'description_label' => $vh360_create_context_is_lesson ? __('Lesson Description', 'videohub360-theme') : __('Video Description', 'videohub360-theme'),
    'description_placeholder' => $vh360_create_context_is_lesson ? __('Describe your lesson content...', 'videohub360-theme') : __('Describe your video content...', 'videohub360-theme'),
    'excerpt_label' => $vh360_create_context_is_lesson ? __('Lesson Summary', 'videohub360-theme') : __('Video Excerpt (Short Description)', 'videohub360-theme'),
    'thumbnail_label' => $vh360_create_context_is_lesson ? __('Lesson Thumbnail', 'videohub360-theme') : __('Featured Image / Thumbnail', 'videohub360-theme'),
    'thumbnail_help' => $vh360_create_context_is_lesson ? __('Upload a thumbnail image for your lesson. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)', 'videohub360-theme') : __('Upload a thumbnail image for your video. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)', 'videohub360-theme'),
    'source_heading' => $vh360_create_context_is_lesson ? __('Lesson Video', 'videohub360-theme') : __('Video Source', 'videohub360-theme'),
    'source_type_label' => $vh360_create_context_is_lesson ? __('Choose Lesson Video Source', 'videohub360-theme') : __('Choose Video Source Type', 'videohub360-theme'),
    'url_option' => $vh360_create_context_is_lesson ? __('Lesson Video URL (Direct Link)', 'videohub360-theme') : __('Video URL (Direct Link)', 'videohub360-theme'),
    'upload_option' => $vh360_create_context_is_lesson ? __('Upload Lesson Video', 'videohub360-theme') : __('Upload Video File', 'videohub360-theme'),
    'url_label' => $vh360_create_context_is_lesson ? __('Lesson Video URL', 'videohub360-theme') : __('Video URL', 'videohub360-theme'),
    'file_label' => $vh360_create_context_is_lesson ? __('Lesson Video File', 'videohub360-theme') : __('Video File', 'videohub360-theme'),
    'choose_file' => $vh360_create_context_is_lesson ? __('Choose Lesson Video File', 'videohub360-theme') : __('Choose Video File', 'videohub360-theme'),
    'publish_button' => $vh360_create_context_is_lesson ? __('Publish Lesson', 'videohub360-theme') : __('Publish Video', 'videohub360-theme'),
    'update_button' => $vh360_create_context_is_lesson ? __('Update Lesson', 'videohub360-theme') : __('Update Video', 'videohub360-theme'),
    'save_draft_button' => __('Save Draft', 'videohub360-theme'),
    'move_to_draft_button' => __('Move to Draft', 'videohub360-theme'),
    'license_inactive_message' => $vh360_create_context_is_lesson ? __('Your VideoHub360 license is inactive. Activate your license to create lessons.', 'videohub360-theme') : __('Your VideoHub360 license is inactive. Activate your license to create videos.', 'videohub360-theme'),
    'permission_denied_message' => $vh360_create_context_is_lesson ? __('You do not have permission to create lessons.', 'videohub360-theme') : __('You do not have permission to create videos.', 'videohub360-theme'),
    'direct_url_help' => $vh360_create_context_is_lesson ? __('Direct link to your lesson video file (MP4, WebM, etc.)', 'videohub360-theme') : __('Direct link to your video file (MP4, WebM, etc.)', 'videohub360-theme'),
    'upload_file_help' => $vh360_create_context_is_lesson ? __('Upload a lesson video file from your computer. Maximum size: %d MB. Allowed formats: %s', 'videohub360-theme') : __('Upload a video file from your computer. Maximum size: %d MB. Allowed formats: %s', 'videohub360-theme'),
    'embed_help' => $vh360_create_context_is_lesson ? __('Paste embed code from YouTube, Vimeo, or other lesson video platforms.', 'videohub360-theme') : __('Paste embed code from YouTube, Vimeo, or other video platforms.', 'videohub360-theme'),
    'regular_mode_option' => $vh360_create_context_is_lesson ? __('No - Regular Lesson Mode', 'videohub360-theme') : __('No - Regular Video Mode', 'videohub360-theme'),
    'chat_placement_help' => $vh360_create_context_is_lesson ? __('Override the global chat placement setting for this specific lesson.', 'videohub360-theme') : __('Override the global chat placement setting for this specific video.', 'videohub360-theme'),
    'quality_help' => $vh360_create_context_is_lesson ? __('Enable custom quality and mirror settings for this lesson video', 'videohub360-theme') : __('Enable custom quality and mirror settings for this video', 'videohub360-theme'),
    'poster_help' => $vh360_create_context_is_lesson ? __('Direct URL to a custom lesson video poster image (alternative to lesson thumbnail)', 'videohub360-theme') : __('Direct URL to a custom video poster image (alternative to featured image)', 'videohub360-theme'),
    'lesson_details_help' => $vh360_create_context_is_lesson ? __('Use these fields when this lesson is part of a course or learning track.', 'videohub360-theme') : __('Use these fields when this video is part of a course or learning track.', 'videohub360-theme'),
    'chat_enabled_label' => $vh360_create_context_is_lesson ? __('Enable live chat for this lesson', 'videohub360-theme') : __('Enable live chat for this video', 'videohub360-theme'),
    'ad_preroll_help' => $vh360_create_context_is_lesson ? __('Ad to play before the lesson video starts', 'videohub360-theme') : __('Video to play before the main video starts', 'videohub360-theme'),
    'ad_midroll_help' => $vh360_create_context_is_lesson ? __('Ad to play during the lesson video', 'videohub360-theme') : __('Video to play during the main video', 'videohub360-theme'),
    'ad_postroll_help' => $vh360_create_context_is_lesson ? __('Ad to play after the lesson video ends', 'videohub360-theme') : __('Video to play after the main video ends', 'videohub360-theme'),
    'override_quality_label' => $vh360_create_context_is_lesson ? __('Override Lesson Video Quality Settings', 'videohub360-theme') : __('Override Video Quality Settings', 'videohub360-theme'),
    'video_quality_label' => $vh360_create_context_is_lesson ? __('Lesson Video Quality', 'videohub360-theme') : __('Video Quality', 'videohub360-theme'),
    'video_mirror_label' => $vh360_create_context_is_lesson ? __('Lesson Video Mirror', 'videohub360-theme') : __('Video Mirror', 'videohub360-theme'),
    'alternative_source_help' => $vh360_create_context_is_lesson ? __('Alternative lesson video source/CDN', 'videohub360-theme') : __('Alternative video source/CDN', 'videohub360-theme'),
);

$show_livestream_settings = function_exists('vh360_create_form_section_enabled') ? vh360_create_form_section_enabled('livestream_settings') : true;
if ($vh360_create_context_is_lesson && function_exists('vh360_create_form_section_enabled') && vh360_create_form_section_enabled('hide_livestream_in_course_mode')) {
    $show_livestream_settings = false;
}
$show_ad_settings = function_exists('vh360_create_form_section_enabled') ? vh360_create_form_section_enabled('ad_settings') : true;
$show_advanced_settings = function_exists('vh360_create_form_section_enabled') ? vh360_create_form_section_enabled('advanced_settings') : true;
$vh360_edit_post_status = $edit_mode && isset($video_data['status']) ? $video_data['status'] : '';
$vh360_is_published_edit = $edit_mode && 'publish' === $vh360_edit_post_status;

// Load lesson meta in edit mode when course features are enabled
if ( $edit_mode && $vh360_course_features_enabled ) {
    $video_data['lesson_module_title']  = get_post_meta( $edit_video_id, '_vh360_lesson_module_title', true );
    $video_data['lesson_module_number'] = get_post_meta( $edit_video_id, '_vh360_lesson_module_number', true );
    $video_data['lesson_number']        = get_post_meta( $edit_video_id, '_vh360_lesson_number', true );
    $video_data['lesson_duration']      = get_post_meta( $edit_video_id, '_vh360_lesson_duration', true );
    $video_data['lesson_resource_url']  = get_post_meta( $edit_video_id, '_vh360_lesson_resource_url', true );
    $video_data['lesson_resource_label']= get_post_meta( $edit_video_id, '_vh360_lesson_resource_label', true );
    $video_data['lesson_is_preview']    = get_post_meta( $edit_video_id, '_vh360_lesson_is_preview', true );
}

// Get taxonomies for form
$categories = get_terms(array(
    'taxonomy' => 'videohub360_category',
    'hide_empty' => false,
));

// When course features are enabled, non-admin users only see courses they can manage in the dropdown.
if ( $vh360_course_features_enabled && ! current_user_can( 'manage_options' ) ) {
    $series = get_terms(array(
        'taxonomy'   => 'videohub360_series',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => '_vh360_course_owner_user_id',
                'value'   => $current_user_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        ),
    ));

    // Prefer explicit owner meta for scalability; fall back only for legacy/imported courses.
    if ( ( is_wp_error( $series ) || empty( $series ) ) && function_exists( 'vh360_user_can_manage_course' ) ) {
        $all_series = get_terms(array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => false,
        ));

        $series = is_wp_error( $all_series ) ? array() : array_filter( $all_series, function( $course ) use ( $current_user_id ) {
            return vh360_user_can_manage_course( $current_user_id, $course->term_id );
        } );
    }
} else {
    $series = get_terms(array(
        'taxonomy' => 'videohub360_series',
        'hide_empty' => false,
    ));
}

if ( ! is_wp_error( $series ) && $vh360_course_features_enabled && ! current_user_can( 'manage_options' ) && function_exists( 'vh360_user_can_manage_course' ) ) {
    $series = array_filter( $series, function( $course ) use ( $current_user_id ) {
        return vh360_user_can_manage_course( $current_user_id, $course->term_id );
    } );
}

if ( ! is_wp_error( $series ) ) {
    $series = array_values( $series );
}

$locations = get_terms(array(
    'taxonomy' => 'videohub360_location',
    'hide_empty' => false,
));
?>

<div class="vh360-dashboard-create-video">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title">
            <?php echo esc_html($edit_mode ? $vh360_create_labels['edit_heading'] : $vh360_create_labels['create_heading']); ?>
        </h1>
    </div>

    <?php if (!$vh360_is_licensed) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-warning vh360-license-softlock-notice">
            <?php echo esc_html($vh360_create_labels['license_inactive_message']); ?>
            <a href="<?php echo esc_url($vh360_license_url); ?>" style="margin-left:8px;">
                <?php esc_html_e('Activate License', 'videohub360-theme'); ?>
            </a>
        </div>
    <?php endif; ?>

    <?php if (!$can_create_videos) : ?>
        <div class="vh360-dashboard-notice vh360-dashboard-notice-error">
            <?php echo esc_html($vh360_create_labels['permission_denied_message']); ?>
        </div>
    <?php else : ?>

    <!-- Video Creation Form -->
    <form method="post" enctype="multipart/form-data" class="vh360-create-video-form" id="vh360-create-video-form">
        <?php wp_nonce_field('vh360_create_video', 'vh360_create_video_nonce'); ?>
        <?php if ($edit_mode): ?>
            <input type="hidden" name="video_id" value="<?php echo esc_attr($edit_video_id); ?>">
        <?php endif; ?>
        
        <!-- Basic Information Section -->
        <div class="vh360-form-section">
            <h3 class="vh360-form-section-title"><?php esc_html_e('Basic Information', 'videohub360-theme'); ?></h3>
            
            <div class="vh360-form-field">
                <label for="vh360_video_title" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['title_label']); ?>
                    <span class="vh360-required">*</span>
                </label>
                <input 
                    type="text" 
                    id="vh360_video_title" 
                    name="vh360_video_title" 
                    class="vh360-input" 
                    required 
                    maxlength="200"
                    placeholder="<?php echo esc_attr($vh360_create_labels['title_placeholder']); ?>"
                    value="<?php echo esc_attr($video_data['title'] ?? ''); ?>"
                >
                <div class="vh360-character-count">
                    <span id="vh360-title-count">0</span>/200
                </div>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_video_description" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['description_label']); ?>
                </label>
                <textarea 
                    id="vh360_video_description" 
                    name="vh360_video_description" 
                    class="vh360-textarea" 
                    rows="6"
                    placeholder="<?php echo esc_attr($vh360_create_labels['description_placeholder']); ?>"
                ><?php echo esc_textarea($video_data['content'] ?? ''); ?></textarea>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_video_excerpt" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['excerpt_label']); ?>
                </label>
                <textarea 
                    id="vh360_video_excerpt" 
                    name="vh360_video_excerpt" 
                    class="vh360-textarea" 
                    rows="3"
                    maxlength="300"
                    placeholder="<?php esc_attr_e('Brief summary for previews and listings...', 'videohub360-theme'); ?>"
                ><?php echo esc_textarea($video_data['excerpt'] ?? ''); ?></textarea>
                <div class="vh360-character-count">
                    <span id="vh360-excerpt-count">0</span>/300
                </div>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_featured_image" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['thumbnail_label']); ?>
                </label>
                <input 
                    type="file" 
                    id="vh360_featured_image" 
                    name="vh360_featured_image" 
                    class="vh360-file-input" 
                    accept="image/jpeg,image/png,image/gif,image/webp" 
                    style="display: none;"
                >
                <button type="button" class="vh360-upload-button" id="vh360-upload-trigger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <?php esc_html_e('Choose Image', 'videohub360-theme'); ?>
                </button>
                <div id="vh360-image-preview" class="vh360-image-preview" style="<?php echo (!empty($video_data['featured_image_url']) ? '' : 'display: none;'); ?>">
                    <img src="<?php echo esc_url($video_data['featured_image_url'] ?? ''); ?>" alt="<?php esc_attr_e('Preview', 'videohub360-theme'); ?>" id="vh360-preview-img">
                    <button type="button" class="vh360-remove-image" id="vh360-remove-image" aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <p class="vh360-form-help">
                    <?php echo esc_html($vh360_create_labels['thumbnail_help']); ?>
                </p>
            </div>
        </div>

        <!-- Video Source Section -->
        <div class="vh360-form-section">
            <h3 class="vh360-form-section-title"><?php echo esc_html($vh360_create_labels['source_heading']); ?></h3>
            
            <?php
            // Get video upload settings
            $upload_settings = vh360_get_video_upload_settings();
            $upload_enabled = !empty($upload_settings['enable_video_upload']);
            
            // Determine current source type
            $current_source = 'url'; // default
            if ($edit_mode) {
                if (!empty($video_data['custom_html'])) {
                    $current_source = 'embed';
                } elseif (!empty($video_data['video_url'])) {
                    $current_source = 'url';
                }
            }
            ?>
            
            <!-- Source Type Selection -->
            <div class="vh360-form-field">
                <label class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['source_type_label']); ?>
                </label>
                <div class="vh360-radio-group">
                    <label class="vh360-radio-label">
                        <input 
                            type="radio" 
                            name="vh360_video_source_type" 
                            value="url" 
                            <?php checked($current_source, 'url'); ?>
                            class="vh360-source-type-radio"
                        >
                        <span><?php echo esc_html($vh360_create_labels['url_option']); ?></span>
                    </label>
                    
                    <?php if ($upload_enabled): ?>
                    <label class="vh360-radio-label">
                        <input 
                            type="radio" 
                            name="vh360_video_source_type" 
                            value="upload" 
                            class="vh360-source-type-radio"
                        >
                        <span><?php echo esc_html($vh360_create_labels['upload_option']); ?></span>
                    </label>
                    <?php endif; ?>
                    
                    <label class="vh360-radio-label">
                        <input 
                            type="radio" 
                            name="vh360_video_source_type" 
                            value="embed" 
                            <?php checked($current_source, 'embed'); ?>
                            class="vh360-source-type-radio"
                        >
                        <span><?php esc_html_e('Embed Code (YouTube, Vimeo, etc.)', 'videohub360-theme'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- Video URL Field -->
            <div class="vh360-form-field vh360-source-field" data-source="url" style="<?php echo $current_source === 'url' ? '' : 'display: none;'; ?>">
                <label for="vh360_video_url" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['url_label']); ?>
                </label>
                <input 
                    type="url" 
                    id="vh360_video_url" 
                    name="vh360_video_url" 
                    class="vh360-input"
                    placeholder="<?php esc_attr_e('https://example.com/video.mp4', 'videohub360-theme'); ?>"
                    value="<?php echo esc_attr($video_data['video_url'] ?? ''); ?>"
                >
                <p class="vh360-form-help">
                    <?php echo esc_html($vh360_create_labels['direct_url_help']); ?>
                </p>
            </div>
            
            <!-- Upload Video Field -->
            <?php if ($upload_enabled): ?>
            <div class="vh360-form-field vh360-source-field" data-source="upload" style="display: none;">
                <label for="vh360_video_file" class="vh360-form-label">
                    <?php echo esc_html($vh360_create_labels['file_label']); ?>
                </label>
                <input 
                    type="file" 
                    id="vh360_video_file" 
                    name="vh360_video_file" 
                    class="vh360-file-input" 
                    accept="<?php 
                        $formats = isset($upload_settings['allowed_formats']) ? $upload_settings['allowed_formats'] : 'mp4,webm,mov';
                        $formats_array = array_map('trim', explode(',', $formats));
                        // Sanitize each format to prevent XSS
                        $formats_array = array_map('sanitize_key', $formats_array);
                        $formats_array = array_filter($formats_array); // Remove empty values
                        echo esc_attr('video/' . implode(',video/', $formats_array));
                    ?>" 
                    style="display: none;"
                >
                <button type="button" class="vh360-upload-button" id="vh360-video-upload-trigger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <?php echo esc_html($vh360_create_labels['choose_file']); ?>
                </button>
                <div id="vh360-video-preview" class="vh360-file-preview" style="display: none;">
                    <div class="vh360-file-info">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        <div class="vh360-file-details">
                            <span class="vh360-file-name" id="vh360-video-file-name"></span>
                            <span class="vh360-file-size" id="vh360-video-file-size"></span>
                        </div>
                    </div>
                    <button type="button" class="vh360-remove-file" id="vh360-remove-video" aria-label="<?php esc_attr_e('Remove video', 'videohub360-theme'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div id="vh360-video-upload-progress" class="vh360-upload-progress" style="display: none;">
                    <div class="vh360-progress-bar">
                        <div class="vh360-progress-fill" id="vh360-video-progress-fill"></div>
                    </div>
                    <span class="vh360-progress-text" id="vh360-video-progress-text">0%</span>
                </div>
                <p class="vh360-form-help">
                    <?php
                    $max_size = isset($upload_settings['max_file_size']) ? $upload_settings['max_file_size'] : 500;
                    $formats = isset($upload_settings['allowed_formats']) ? $upload_settings['allowed_formats'] : 'mp4,webm,mov';
                    printf(
                        $vh360_create_labels['upload_file_help'],
                        $max_size,
                        $formats
                    );
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Embed Code Field -->
            <div class="vh360-form-field vh360-source-field" data-source="embed" style="<?php echo $current_source === 'embed' ? '' : 'display: none;'; ?>">
                <label for="vh360_custom_html" class="vh360-form-label">
                    <?php esc_html_e('Embed Code', 'videohub360-theme'); ?>
                </label>
                <textarea 
                    id="vh360_custom_html" 
                    name="vh360_custom_html" 
                    class="vh360-textarea" 
                    rows="4"
                    placeholder="<?php esc_attr_e('<iframe src=&quot;...&quot;></iframe> or YouTube/Vimeo embed code', 'videohub360-theme'); ?>"
                ><?php echo esc_textarea($video_data['custom_html'] ?? ''); ?></textarea>
                <p class="vh360-form-help">
                    <?php echo esc_html($vh360_create_labels['embed_help']); ?>
                </p>
            </div>
        </div>

        <?php if ($show_livestream_settings) : ?>
        <!-- Livestream Settings Section (Collapsible) -->
        <div class="vh360-form-section vh360-form-section-collapsible">
            <h3 class="vh360-form-section-title vh360-section-toggle">
                <span><?php esc_html_e('🔴 Livestream Settings (Optional)', 'videohub360-theme'); ?></span>
                <svg class="vh360-toggle-icon rotated" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </h3>
            <div class="vh360-section-content">
                
                <div class="vh360-form-help" style="background: #e7f3ff; padding: 12px; border-radius: 4px; border-left: 4px solid #2196F3; margin-bottom: 15px;">
                    <strong>💡 Quick Setup Guide:</strong><br>
                    1. Set "Currently Live Status" to "Yes" to enable livestream mode<br>
                    2. Choose your "Stream Source Type" (Agora.io recommended for interactive streaming)<br>
                    3. Configure source-specific settings below<br>
                    4. Save to enable livestream functionality
                </div>

                <!-- Usage Context (Hidden - Always "default") -->
                <input type="hidden" name="vh360_context" value="default">

                <div class="vh360-form-field">
                    <label for="vh360_is_live" class="vh360-form-label">
                        <?php esc_html_e('Currently Live Status', 'videohub360-theme'); ?>
                    </label>
                    <select id="vh360_is_live" name="vh360_is_live" class="vh360-select">
                        <option value="no" selected><?php echo esc_html($vh360_create_labels['regular_mode_option']); ?></option>
                        <option value="yes"><?php esc_html_e('Yes - Livestream Mode', 'videohub360-theme'); ?></option>
                    </select>
                    <p class="vh360-form-help">
                        <?php esc_html_e('When set to "Yes", this video will display livestream functionality instead of regular video player.', 'videohub360-theme'); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_type" class="vh360-form-label">
                        <?php esc_html_e('Stream Source Type', 'videohub360-theme'); ?>
                    </label>
                    <select id="vh360_type" name="vh360_type" class="vh360-select">
                        <option value="embed"><?php esc_html_e('Embed (YouTube Live, Twitch, etc.)', 'videohub360-theme'); ?></option>
                        <option value="selfhosted"><?php esc_html_e('Self-Hosted HLS/DASH', 'videohub360-theme'); ?></option>
                        <option value="api"><?php esc_html_e('Streaming API Platform', 'videohub360-theme'); ?></option>
                        <option value="agora" selected><?php esc_html_e('Agora.io WebRTC (Recommended for Interactive)', 'videohub360-theme'); ?></option>
                    </select>
                    <p class="vh360-form-help">
                        <strong><?php esc_html_e('Agora.io', 'videohub360-theme'); ?></strong> <?php esc_html_e('offers the best experience for interactive livestreams with audience participation, built-in chat, and real-time engagement.', 'videohub360-theme'); ?>
                    </p>
                </div>

                <!-- Stream Source Type Specific Fields -->
                <div id="vh360-stream-type-fields">
                    
                    <!-- Embed Fields -->
                    <div class="vh360-stream-type-field" data-type="embed" style="display: none;">
                        <div class="vh360-form-field">
                            <label for="vh360_embed_code" class="vh360-form-label">
                                <?php esc_html_e('Embed Code', 'videohub360-theme'); ?>
                            </label>
                            <textarea 
                                id="vh360_embed_code" 
                                name="vh360_embed_code" 
                                class="vh360-textarea" 
                                rows="4"
                                placeholder="<?php esc_attr_e('Paste your iframe/embed HTML here (YouTube Live, Twitch, etc.)', 'videohub360-theme'); ?>"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Self-Hosted Fields -->
                    <div class="vh360-stream-type-field" data-type="selfhosted" style="display: none;">
                        <div class="vh360-form-field">
                            <label for="vh360_stream_url" class="vh360-form-label">
                                <?php esc_html_e('Self-Hosted Stream URL (HLS/DASH)', 'videohub360-theme'); ?>
                            </label>
                            <input 
                                type="url" 
                                id="vh360_stream_url" 
                                name="vh360_stream_url" 
                                class="vh360-input"
                                placeholder="<?php esc_attr_e('https://example.com/stream.m3u8', 'videohub360-theme'); ?>"
                            >
                            <p class="vh360-form-help">
                                <?php esc_html_e('HLS (.m3u8) or DASH (.mpd) stream URL.', 'videohub360-theme'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- API Platform Fields -->
                    <div class="vh360-stream-type-field" data-type="api" style="display: none;">
                        <div class="vh360-form-field">
                            <label for="vh360_api_url" class="vh360-form-label">
                                <?php esc_html_e('API Playback URL (HLS/DASH)', 'videohub360-theme'); ?>
                            </label>
                            <input 
                                type="url" 
                                id="vh360_api_url" 
                                name="vh360_api_url" 
                                class="vh360-input"
                                placeholder="<?php esc_attr_e('https://api.example.com/stream.m3u8', 'videohub360-theme'); ?>"
                            >
                            <p class="vh360-form-help">
                                <?php esc_html_e('API-provided playback URL for platforms like Mux, AWS IVS.', 'videohub360-theme'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Agora Fields -->
                    <div class="vh360-stream-type-field" data-type="agora">
                        <div class="vh360-form-field">
                            <label for="vh360_agora_mode" class="vh360-form-label">
                                <?php esc_html_e('Streaming Mode', 'videohub360-theme'); ?>
                            </label>
                            <select id="vh360_agora_mode" name="vh360_agora_mode" class="vh360-select">
                                <option value="interactive"><?php esc_html_e('Interactive Mode', 'videohub360-theme'); ?></option>
                                <option value="broadcast"><?php esc_html_e('Broadcast Mode', 'videohub360-theme'); ?></option>
                            </select>
                            <p class="vh360-form-help">
                                <strong><?php esc_html_e('Interactive Mode:', 'videohub360-theme'); ?></strong> <?php esc_html_e('Allows audience members to request to join as hosts and interact in real-time.', 'videohub360-theme'); ?>
                            </p>
                        </div>

                        <div class="vh360-form-field">
                            <label for="vh360_agora_channel_name" class="vh360-form-label">
                                <?php esc_html_e('Channel Name', 'videohub360-theme'); ?>
                            </label>
                            <input 
                                type="text" 
                                id="vh360_agora_channel_name" 
                                name="vh360_agora_channel_name" 
                                class="vh360-input"
                                placeholder="<?php esc_attr_e('Leave blank to auto-generate a secure channel name', 'videohub360-theme'); ?>"
                            >
                            <p class="vh360-form-help">
                                <?php esc_html_e('The Agora channel name for this specific livestream. Leave blank to auto-generate. Use alphanumeric characters only if providing your own.', 'videohub360-theme'); ?>
                            </p>
                        </div>

                        <div class="vh360-form-field">
                            <label class="vh360-checkbox-label">
                                <input 
                                    type="checkbox" 
                                    id="vh360_agora_everyone_is_host" 
                                    name="vh360_agora_everyone_is_host" 
                                    value="yes"
                                >
                                <span><?php esc_html_e('Allow Everyone to be Host', 'videohub360-theme'); ?></span>
                            </label>
                            <p class="vh360-form-help">
                                <?php esc_html_e('When enabled, all viewers can directly join as hosts. Cannot be used with passcode requirement.', 'videohub360-theme'); ?>
                            </p>
                        </div>

                        <div class="vh360-form-field" style="border-left: 3px solid #0073aa; padding-left: 15px; margin-left: 10px;">
                            <h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php esc_html_e('Access Control', 'videohub360-theme'); ?></h4>
                            <label class="vh360-checkbox-label">
                                <input 
                                    type="checkbox" 
                                    id="vh360_require_passcode" 
                                    name="vh360_require_passcode" 
                                    value="yes"
                                >
                                <span><?php esc_html_e('Require Passcode To Join', 'videohub360-theme'); ?></span>
                            </label>
                            <p class="vh360-form-help">
                                <?php esc_html_e('When enabled, viewers must enter a passcode to join as presenters. Cannot be used with "Allow Everyone to be Host".', 'videohub360-theme'); ?>
                            </p>
                            <div id="vh360-passcode-field" style="margin-top: 10px; display: none;">
                                <label for="vh360_host_passcode" class="vh360-form-label">
                                    <?php esc_html_e('Host Passcode', 'videohub360-theme'); ?>
                                </label>
                                <input 
                                    type="password" 
                                    id="vh360_host_passcode" 
                                    name="vh360_host_passcode" 
                                    class="vh360-input"
                                    placeholder="<?php esc_attr_e('Enter passcode', 'videohub360-theme'); ?>"
                                    style="font-family: monospace; font-weight: bold;"
                                    autocomplete="new-password"
                                >
                                <p class="vh360-form-help">
                                    <?php esc_html_e('Viewers will need to enter this passcode to join as presenters.', 'videohub360-theme'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="vh360-form-field">
                    <label for="vh360_live_start_time" class="vh360-form-label">
                        <?php esc_html_e('Live Start Time', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="datetime-local" 
                        id="vh360_live_start_time" 
                        name="vh360_live_start_time" 
                        class="vh360-input"
                    >
                    <p class="vh360-form-help">
                        <?php esc_html_e('Set the date and time when the livestream started (for "Started streaming X minutes ago" display).', 'videohub360-theme'); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_offline_message" class="vh360-form-label">
                        <?php esc_html_e('Offline Message or Placeholder', 'videohub360-theme'); ?>
                    </label>
                    <textarea 
                        id="vh360_offline_message" 
                        name="vh360_offline_message" 
                        class="vh360-textarea" 
                        rows="3"
                        placeholder="<?php esc_attr_e('Stream will begin shortly...', 'videohub360-theme'); ?>"
                    ></textarea>
                    <p class="vh360-form-help">
                        <?php esc_html_e('Shown when livestream status is "No" or when livestream is offline. You can use text, HTML, or an image tag.', 'videohub360-theme'); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label class="vh360-checkbox-label">
                        <input 
                            type="checkbox" 
                            id="vh360_viewer_count" 
                            name="vh360_viewer_count" 
                            value="yes"
                        >
                        <span><?php esc_html_e('Show Viewer Count', 'videohub360-theme'); ?></span>
                    </label>
                </div>

                <div class="vh360-form-field">
                    <label class="vh360-checkbox-label">
                        <input 
                            type="checkbox" 
                            id="vh360_chat_enabled" 
                            name="vh360_chat_enabled" 
                            value="yes"
                        >
                        <span><?php echo esc_html($vh360_create_labels['chat_enabled_label']); ?></span>
                    </label>
                    <p class="vh360-form-help">
                        <?php esc_html_e('When checked, live chat will be available for this livestream regardless of global settings.', 'videohub360-theme'); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_chat_placement" class="vh360-form-label">
                        <?php esc_html_e('Chat Placement Override', 'videohub360-theme'); ?>
                    </label>
                    <select id="vh360_chat_placement" name="vh360_chat_placement" class="vh360-select">
                        <option value=""><?php esc_html_e('Use Global Default', 'videohub360-theme'); ?></option>
                        <option value="inline"><?php esc_html_e('Inline (replaces comments)', 'videohub360-theme'); ?></option>
                        <option value="popup"><?php esc_html_e('Popup (button opens overlay)', 'videohub360-theme'); ?></option>
                        <option value="sidebar"><?php esc_html_e('Sidebar (YouTube-style)', 'videohub360-theme'); ?></option>
                        <option value="off"><?php esc_html_e('Off (hide chat)', 'videohub360-theme'); ?></option>
                    </select>
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['chat_placement_help']); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label class="vh360-checkbox-label">
                        <input 
                            type="checkbox" 
                            id="vh360_live_badge" 
                            name="vh360_live_badge" 
                            value="yes"
                        >
                        <span><?php esc_html_e('Show Live Badge', 'videohub360-theme'); ?></span>
                    </label>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_badge_text" class="vh360-form-label">
                        <?php esc_html_e('Live Badge Text', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="vh360_badge_text" 
                        name="vh360_badge_text" 
                        class="vh360-input"
                        placeholder="<?php esc_attr_e('LIVE', 'videohub360-theme'); ?>"
                    >
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_badge_color" class="vh360-form-label">
                        <?php esc_html_e('Live Badge Color', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="color" 
                        id="vh360_badge_color" 
                        name="vh360_badge_color" 
                        class="vh360-input"
                        value="#e53935"
                    >
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Taxonomy Section -->
        <div class="vh360-form-section">
            <h3 class="vh360-form-section-title"><?php esc_html_e('Categories & Tags', 'videohub360-theme'); ?></h3>
            
            <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            <div class="vh360-form-field">
                <label class="vh360-form-label">
                    <?php esc_html_e('Categories', 'videohub360-theme'); ?>
                </label>
                <div class="vh360-checkbox-group">
                    <?php foreach ($categories as $category) : ?>
                        <label class="vh360-checkbox-label">
                            <input 
                                type="checkbox" 
                                name="vh360_categories[]" 
                                value="<?php echo esc_attr($category->term_id); ?>"
                                <?php checked(in_array($category->term_id, $video_data['categories'] ?? array())); ?>
                            >
                            <span><?php echo esc_html($category->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($series) && !is_wp_error($series)) : ?>
            <?php
            $series_label = esc_html__( 'Series', 'videohub360-theme' );
            $series_placeholder = esc_html__( 'Select a series...', 'videohub360-theme' );
            if ( $vh360_course_features_enabled && function_exists( 'videohub360_get_course_label' ) ) {
                $series_label       = sprintf(
                    /* translators: %s: Course label (e.g. "Course") */
                    esc_html__( '%s / Series', 'videohub360-theme' ),
                    videohub360_get_course_label()
                );
                $series_placeholder = esc_html__( 'Select a course or series...', 'videohub360-theme' );
            }
            ?>
            <div class="vh360-form-field">
                <label for="vh360_series" class="vh360-form-label">
                    <?php echo esc_html($series_label); ?>
                </label>
                <select id="vh360_series" name="vh360_series" class="vh360-select">
                    <option value=""><?php echo esc_html($series_placeholder); ?></option>
                    <?php 
                    $selected_series = !empty($video_data['series']) ? $video_data['series'][0] : 0;
                    foreach ($series as $serie) : 
                    ?>
                        <option value="<?php echo esc_attr($serie->term_id); ?>" <?php selected($selected_series, $serie->term_id); ?>>
                            <?php echo esc_html($serie->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($locations) && !is_wp_error($locations)) : ?>
            <div class="vh360-form-field">
                <label for="vh360_location" class="vh360-form-label">
                    <?php esc_html_e('Location', 'videohub360-theme'); ?>
                </label>
                <select id="vh360_location" name="vh360_location" class="vh360-select">
                    <option value=""><?php esc_html_e('Select a location...', 'videohub360-theme'); ?></option>
                    <?php 
                    $selected_location = !empty($video_data['locations']) ? $video_data['locations'][0] : 0;
                    foreach ($locations as $location) : 
                    ?>
                        <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($selected_location, $location->term_id); ?>>
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="vh360-form-field">
                <label for="vh360_tags" class="vh360-form-label">
                    <?php esc_html_e('Tags', 'videohub360-theme'); ?>
                </label>
                <input 
                    type="text" 
                    id="vh360_tags" 
                    name="vh360_tags" 
                    class="vh360-input"
                    placeholder="<?php esc_attr_e('Separate tags with commas: tutorial, beginner, how-to', 'videohub360-theme'); ?>"
                    value="<?php echo esc_attr($video_data['tags'] ?? ''); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e('Enter tags separated by commas', 'videohub360-theme'); ?>
                </p>
            </div>
        </div>

        <?php if ( $vh360_course_features_enabled ) : ?>
        <!-- Lesson Details Section -->
        <div class="vh360-form-section">
            <h3 class="vh360-form-section-title"><?php esc_html_e( 'Lesson Details', 'videohub360-theme' ); ?></h3>
            <p class="vh360-form-help" style="margin-bottom: 16px;">
                <?php echo esc_html($vh360_create_labels['lesson_details_help']); ?>
            </p>

            <div class="vh360-form-field">
                <label for="vh360_lesson_module_title" class="vh360-form-label">
                    <?php esc_html_e( 'Module Title', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="text"
                    id="vh360_lesson_module_title"
                    name="_vh360_lesson_module_title"
                    class="vh360-input"
                    placeholder="<?php esc_attr_e( 'e.g. Getting Started', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_module_title'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'The module or section this lesson belongs to.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_lesson_module_number" class="vh360-form-label">
                    <?php esc_html_e( 'Module Number', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="number"
                    id="vh360_lesson_module_number"
                    name="_vh360_lesson_module_number"
                    class="vh360-input"
                    min="0"
                    step="1"
                    placeholder="<?php esc_attr_e( '1', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_module_number'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Used to order modules within the course.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_lesson_number" class="vh360-form-label">
                    <?php esc_html_e( 'Lesson Number', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="number"
                    id="vh360_lesson_number"
                    name="_vh360_lesson_number"
                    class="vh360-input"
                    min="0"
                    step="1"
                    placeholder="<?php esc_attr_e( '1', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_number'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Used to order lessons within a module.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_lesson_duration" class="vh360-form-label">
                    <?php esc_html_e( 'Lesson Duration', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="text"
                    id="vh360_lesson_duration"
                    name="_vh360_lesson_duration"
                    class="vh360-input"
                    placeholder="<?php esc_attr_e( 'e.g. 12:30 or 45 min', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_duration'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Display duration shown on course pages.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_lesson_resource_url" class="vh360-form-label">
                    <?php esc_html_e( 'Resource URL', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="url"
                    id="vh360_lesson_resource_url"
                    name="_vh360_lesson_resource_url"
                    class="vh360-input"
                    placeholder="<?php esc_attr_e( 'https://example.com/resource.pdf', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_resource_url'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Optional downloadable resource or worksheet for this lesson.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label for="vh360_lesson_resource_label" class="vh360-form-label">
                    <?php esc_html_e( 'Resource Label', 'videohub360-theme' ); ?>
                </label>
                <input
                    type="text"
                    id="vh360_lesson_resource_label"
                    name="_vh360_lesson_resource_label"
                    class="vh360-input"
                    placeholder="<?php esc_attr_e( 'e.g. Download Worksheet', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( $video_data['lesson_resource_label'] ?? '' ); ?>"
                >
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Link text for the resource URL.', 'videohub360-theme' ); ?>
                </p>
            </div>

            <div class="vh360-form-field">
                <label class="vh360-checkbox-label">
                    <input
                        type="checkbox"
                        id="vh360_lesson_is_preview"
                        name="_vh360_lesson_is_preview"
                        value="yes"
                        <?php checked( ( $video_data['lesson_is_preview'] ?? '' ) === 'yes' ); ?>
                    >
                    <span><?php esc_html_e( 'Free Preview Lesson', 'videohub360-theme' ); ?></span>
                </label>
                <p class="vh360-form-help">
                    <?php esc_html_e( 'Allow non-members to preview this lesson for free.', 'videohub360-theme' ); ?>
                </p>
            </div>
        </div>
        <?php endif; // $vh360_course_features_enabled ?>

        <?php if ($show_ad_settings) : ?>
        <!-- Ad Settings Section (Collapsible) -->
        <div class="vh360-form-section vh360-form-section-collapsible">
            <h3 class="vh360-form-section-title vh360-section-toggle">
                <span><?php esc_html_e('Ad Settings (Optional)', 'videohub360-theme'); ?></span>
                <svg class="vh360-toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </h3>
            <div class="vh360-section-content" style="display: none;">
                
                <div class="vh360-form-field">
                    <label for="vh360_ad_video_url" class="vh360-form-label">
                        <?php esc_html_e('Pre-roll Ad Video URL', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="url" 
                        id="vh360_ad_video_url" 
                        name="vh360_ad_video_url" 
                        class="vh360-input"
                        placeholder="<?php esc_attr_e('https://example.com/pre-roll-ad.mp4', 'videohub360-theme'); ?>"
                        value="<?php echo esc_attr($video_data['ad_video_url'] ?? ''); ?>"
                    >
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['ad_preroll_help']); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_midroll_ad_video_url" class="vh360-form-label">
                        <?php esc_html_e('Mid-roll Ad Video URL', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="url" 
                        id="vh360_midroll_ad_video_url" 
                        name="vh360_midroll_ad_video_url" 
                        class="vh360-input"
                        placeholder="<?php esc_attr_e('https://example.com/mid-roll-ad.mp4', 'videohub360-theme'); ?>"
                        value="<?php echo esc_attr($video_data['midroll_ad_url'] ?? ''); ?>"
                    >
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['ad_midroll_help']); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_midroll_ad_timing" class="vh360-form-label">
                        <?php esc_html_e('Mid-roll Ad Timing (seconds)', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="number" 
                        id="vh360_midroll_ad_timing" 
                        name="vh360_midroll_ad_timing" 
                        class="vh360-input"
                        min="0"
                        step="1"
                        placeholder="<?php esc_attr_e('60', 'videohub360-theme'); ?>"
                        value="<?php echo esc_attr($video_data['midroll_timing'] ?? ''); ?>"
                    >
                    <p class="vh360-form-help">
                        <?php esc_html_e('Time in seconds when the mid-roll ad should play', 'videohub360-theme'); ?>
                    </p>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_postroll_ad_video_url" class="vh360-form-label">
                        <?php esc_html_e('Post-roll Ad Video URL', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="url" 
                        id="vh360_postroll_ad_video_url" 
                        name="vh360_postroll_ad_video_url" 
                        class="vh360-input"
                        placeholder="<?php esc_attr_e('https://example.com/post-roll-ad.mp4', 'videohub360-theme'); ?>"
                        value="<?php echo esc_attr($video_data['postroll_ad_url'] ?? ''); ?>"
                    >
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['ad_postroll_help']); ?>
                    </p>
                </div>

            </div>
        </div>

        <?php endif; ?>

        <?php if ($show_advanced_settings) : ?>
        <!-- Advanced Settings Section (Collapsible) -->
        <div class="vh360-form-section vh360-form-section-collapsible">
            <h3 class="vh360-form-section-title vh360-section-toggle">
                <span><?php esc_html_e('Advanced Settings (Optional)', 'videohub360-theme'); ?></span>
                <svg class="vh360-toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </h3>
            <div class="vh360-section-content" style="display: none;">
                
                <div class="vh360-form-field">
                    <label class="vh360-checkbox-label">
                        <input 
                            type="checkbox" 
                            id="vh360_override_quality" 
                            name="vh360_override_quality" 
                            value="yes"
                            <?php checked($video_data['quality_override'] ?? '', 'yes'); ?>
                        >
                        <span><?php echo esc_html($vh360_create_labels['override_quality_label']); ?></span>
                    </label>
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['quality_help']); ?>
                    </p>
                </div>

                <div id="vh360-quality-fields" style="<?php echo ($video_data['quality_override'] ?? '') === 'yes' ? '' : 'display: none;'; ?>">
                    <div class="vh360-form-field">
                        <label for="vh360_video_quality" class="vh360-form-label">
                            <?php echo esc_html($vh360_create_labels['video_quality_label']); ?>
                        </label>
                        <select id="vh360_video_quality" name="vh360_video_quality" class="vh360-select">
                            <option value="auto" <?php selected($video_data['video_quality'] ?? 'auto', 'auto'); ?>><?php esc_html_e('Auto', 'videohub360-theme'); ?></option>
                            <option value="1080p" <?php selected($video_data['video_quality'] ?? '', '1080p'); ?>><?php esc_html_e('1080p (Full HD)', 'videohub360-theme'); ?></option>
                            <option value="720p" <?php selected($video_data['video_quality'] ?? '', '720p'); ?>><?php esc_html_e('720p (HD)', 'videohub360-theme'); ?></option>
                            <option value="480p" <?php selected($video_data['video_quality'] ?? '', '480p'); ?>><?php esc_html_e('480p (SD)', 'videohub360-theme'); ?></option>
                            <option value="360p" <?php selected($video_data['video_quality'] ?? '', '360p'); ?>><?php esc_html_e('360p', 'videohub360-theme'); ?></option>
                        </select>
                    </div>

                    <div class="vh360-form-field">
                        <label for="vh360_video_mirror" class="vh360-form-label">
                            <?php echo esc_html($vh360_create_labels['video_mirror_label']); ?>
                        </label>
                        <select id="vh360_video_mirror" name="vh360_video_mirror" class="vh360-select">
                            <option value="" <?php selected($video_data['video_mirror'] ?? '', ''); ?>><?php esc_html_e('None', 'videohub360-theme'); ?></option>
                            <option value="mirror1" <?php selected($video_data['video_mirror'] ?? '', 'mirror1'); ?>><?php esc_html_e('Mirror 1', 'videohub360-theme'); ?></option>
                            <option value="mirror2" <?php selected($video_data['video_mirror'] ?? '', 'mirror2'); ?>><?php esc_html_e('Mirror 2', 'videohub360-theme'); ?></option>
                            <option value="mirror3" <?php selected($video_data['video_mirror'] ?? '', 'mirror3'); ?>><?php esc_html_e('Mirror 3', 'videohub360-theme'); ?></option>
                        </select>
                        <p class="vh360-form-help">
                            <?php echo esc_html($vh360_create_labels['alternative_source_help']); ?>
                        </p>
                    </div>
                </div>

                <div class="vh360-form-field">
                    <label for="vh360_poster_url" class="vh360-form-label">
                        <?php esc_html_e('Custom Poster/Thumbnail URL', 'videohub360-theme'); ?>
                    </label>
                    <input 
                        type="url" 
                        id="vh360_poster_url" 
                        name="vh360_poster_url" 
                        class="vh360-input"
                        placeholder="<?php esc_attr_e('https://example.com/poster.jpg', 'videohub360-theme'); ?>"
                        value="<?php echo esc_attr($video_data['poster_url'] ?? ''); ?>"
                    >
                    <p class="vh360-form-help">
                        <?php echo esc_html($vh360_create_labels['poster_help']); ?>
                    </p>
                </div>

            </div>
        </div>

        <?php endif; ?>

        <!-- Form Actions -->
        <div class="vh360-form-actions">
            <button 
                type="submit" 
                name="vh360_action" 
                value="publish" 
                class="vh360-dashboard-btn vh360-btn-primary" 
                id="vh360-publish-btn"
                <?php echo !$vh360_is_licensed ? 'disabled' : ''; ?>
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"></path>
                </svg>
                <?php echo esc_html($vh360_is_published_edit ? $vh360_create_labels['update_button'] : $vh360_create_labels['publish_button']); ?>
            </button>

            <button 
                type="submit" 
                name="vh360_action" 
                value="draft" 
                class="vh360-dashboard-btn vh360-btn-secondary" 
                id="vh360-draft-btn"
                <?php echo !$vh360_is_licensed ? 'disabled' : ''; ?>
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php echo esc_html($vh360_is_published_edit ? $vh360_create_labels['move_to_draft_button'] : $vh360_create_labels['save_draft_button']); ?>
            </button>

            <a
                href="<?php echo esc_url( function_exists( 'vh360_get_dashboard_tab_url' ) ? vh360_get_dashboard_tab_url( 'videos' ) : add_query_arg( 'tab', 'videos' ) ); ?>"
                class="vh360-dashboard-btn vh360-btn-cancel"
            >
                <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
            </a>
        </div>

        <!-- Success/Error Messages -->
        <div id="vh360-form-message" class="vh360-form-message" style="display: none;"></div>

    </form>

    <?php endif; ?>
    
</div><!-- .vh360-dashboard-create-video -->
