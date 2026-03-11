<?php
/**
 * Profile Avatar Processing Functions
 *
 * Centralized avatar upload and cropping logic for profile editing interfaces.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process profile avatar upload with cropping
 *
 * Handles avatar upload, validation, EXIF correction, cropping, and resizing.
 * Creates WordPress attachment and returns attachment ID.
 *
 * @param array $file      The $_FILES array for the uploaded file.
 * @param int   $user_id   The user ID for whom the avatar is being uploaded.
 * @param array $crop_data Array containing crop coordinates:
 *                         - 'x' (float) X coordinate of crop origin
 *                         - 'y' (float) Y coordinate of crop origin
 *                         - 'width' (float) Width of crop area
 *                         - 'height' (float) Height of crop area
 *                         - 'source_width' (int) Original image width
 *                         - 'source_height' (int) Original image height
 *
 * @return array {
 *     Result array with success status and data or error message.
 *
 *     @type bool   $success      Whether the operation was successful.
 *     @type int    $attachment_id Attachment ID on success.
 *     @type string $error        Error message on failure.
 * }
 */
function vh360_process_profile_avatar_upload($file, $user_id, $crop_data = array()) {
    // Validate user ID
    if (!$user_id || !get_userdata($user_id)) {
        return array(
            'success' => false,
            'error'   => __('Invalid user ID.', 'videohub360-theme')
        );
    }

    // Get avatar settings
    $options = get_option('vh360_profile_options', array());
    $avatar_max_size = isset($options['avatar_max_size']) ? absint($options['avatar_max_size']) : 2;
    $avatar_output_size = isset($options['avatar_output_size']) ? absint($options['avatar_output_size']) : 300;
    $avatar_min_width = isset($options['avatar_min_width']) ? absint($options['avatar_min_width']) : 300;
    $avatar_min_height = isset($options['avatar_min_height']) ? absint($options['avatar_min_height']) : 300;
    $avatar_quality = isset($options['avatar_quality']) ? absint($options['avatar_quality']) : 90;
    $avatar_allowed_types = isset($options['avatar_allowed_types']) && is_array($options['avatar_allowed_types']) 
        ? $options['avatar_allowed_types'] 
        : array('image/jpeg', 'image/png', 'image/gif');

    // Validate file exists
    if (empty($file['name']) || empty($file['tmp_name'])) {
        return array(
            'success' => false,
            'error'   => __('No file uploaded.', 'videohub360-theme')
        );
    }

    // Validate file size
    $max_size_bytes = $avatar_max_size * 1024 * 1024;
    if ($file['size'] > $max_size_bytes) {
        return array(
            'success' => false,
            'error'   => sprintf(
                /* translators: %d: Maximum file size in MB */
                __('File size exceeds maximum allowed size of %d MB.', 'videohub360-theme'),
                $avatar_max_size
            )
        );
    }

    // Load required WordPress files
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Upload file with MIME type restrictions
    // Note: This is the first layer of validation. The wp_check_filetype_and_ext
    // below provides a second layer that validates both extension and file contents,
    // and respects the admin-configured avatar_allowed_types setting.
    $upload_overrides = array(
        'test_form' => false,
        'mimes'     => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
        ),
    );

    $upload = wp_handle_upload($file, $upload_overrides);

    if (isset($upload['error'])) {
        return array(
            'success' => false,
            'error'   => $upload['error']
        );
    }

    $image_path = $upload['file'];

    // Validate MIME type securely using both extension and file contents
    // This provides defense-in-depth and respects admin-configured allowed types
    $file_check = wp_check_filetype_and_ext($image_path, $file['name']);
    
    if (!$file_check['type'] || !in_array($file_check['type'], $avatar_allowed_types)) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Invalid file type. Please upload a JPG, PNG, or GIF image.', 'videohub360-theme')
        );
    }

    // Initialize image editor
    $editor = wp_get_image_editor($image_path);
    if (is_wp_error($editor)) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Unable to process image.', 'videohub360-theme')
        );
    }

    // Correct EXIF orientation before processing
    $editor->maybe_exif_rotate();

    // Get image dimensions after EXIF correction
    $size = $editor->get_size();
    if (empty($size['width']) || empty($size['height'])) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Unable to read image dimensions.', 'videohub360-theme')
        );
    }

    // Validate minimum dimensions
    if ($size['width'] < $avatar_min_width || $size['height'] < $avatar_min_height) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => sprintf(
                /* translators: 1: minimum width, 2: minimum height */
                __('Image dimensions are too small. Minimum size is %1$dx%2$d pixels.', 'videohub360-theme'),
                $avatar_min_width,
                $avatar_min_height
            )
        );
    }

    // Process crop coordinates if provided
    if (!empty($crop_data) && isset($crop_data['x'], $crop_data['y'], $crop_data['width'], $crop_data['height'])) {
        // Validate crop coordinates
        $crop_x = max(0, (float) $crop_data['x']);
        $crop_y = max(0, (float) $crop_data['y']);
        $crop_width = max(1, (float) $crop_data['width']);
        $crop_height = max(1, (float) $crop_data['height']);

        // Ensure crop area doesn't exceed image bounds
        $crop_x = min($crop_x, $size['width'] - 1);
        $crop_y = min($crop_y, $size['height'] - 1);
        $crop_width = min($crop_width, $size['width'] - $crop_x);
        $crop_height = min($crop_height, $size['height'] - $crop_y);

        // Apply crop
        $crop_result = $editor->crop($crop_x, $crop_y, $crop_width, $crop_height);
        if (is_wp_error($crop_result)) {
            @unlink($image_path);
            return array(
                'success' => false,
                'error'   => __('Failed to crop image.', 'videohub360-theme')
            );
        }
    } else {
        // Fallback: auto-crop to centered square if no crop data provided
        $min_side = min($size['width'], $size['height']);
        $x = max(0, ($size['width'] - $min_side) / 2);
        $y = max(0, ($size['height'] - $min_side) / 2);

        $crop_result = $editor->crop($x, $y, $min_side, $min_side);
        if (is_wp_error($crop_result)) {
            @unlink($image_path);
            return array(
                'success' => false,
                'error'   => __('Failed to crop image.', 'videohub360-theme')
            );
        }
    }

    // Resize to avatar output size
    $resize_result = $editor->resize($avatar_output_size, $avatar_output_size, true);
    if (is_wp_error($resize_result)) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Failed to resize image.', 'videohub360-theme')
        );
    }

    // Set quality for JPEG images
    $editor->set_quality($avatar_quality);

    // Save processed image
    $saved = $editor->save($image_path);
    if (is_wp_error($saved)) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Failed to save processed image.', 'videohub360-theme')
        );
    }

    // Create WordPress attachment
    $filetype = wp_check_filetype($image_path);
    $filename = sanitize_file_name($file['name']);

    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => pathinfo($filename, PATHINFO_FILENAME),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_author'    => $user_id,
    );

    $attach_id = wp_insert_attachment($attachment, $image_path);
    if (is_wp_error($attach_id) || !$attach_id) {
        @unlink($image_path);
        return array(
            'success' => false,
            'error'   => __('Failed to create attachment.', 'videohub360-theme')
        );
    }

    // Generate attachment metadata
    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Success
    return array(
        'success'       => true,
        'attachment_id' => $attach_id,
    );
}

/**
 * Get avatar cropping settings
 *
 * Returns the current avatar configuration settings.
 *
 * @return array Avatar settings.
 */
function vh360_get_avatar_settings() {
    $options = get_option('vh360_profile_options', array());
    
    $defaults = array(
        'enable_avatar_cropper' => true,
        'avatar_output_size'    => 300,
        'avatar_min_width'      => 300,
        'avatar_min_height'     => 300,
        'avatar_quality'        => 90,
        'avatar_max_size'       => 2,
        'avatar_allowed_types'  => array('image/jpeg', 'image/png', 'image/gif'),
    );

    return wp_parse_args($options, $defaults);
}
