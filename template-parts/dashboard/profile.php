<?php
/**
 * Dashboard Profile Tab
 *
 * Quick profile editor with cover upload and basic info.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

// Get user account type to determine if cover image should be shown
$account_type = vh360_get_user_account_type($current_user_id);
$is_business_mode_account = in_array($account_type, array('client', 'professional', 'organization'), true);

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vh360_edit_profile_nonce'])) {
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_edit_profile_nonce'], 'vh360_edit_profile_action')) {
        $error_message = __('Security check failed. Please try again.', 'videohub360-theme');
    } else {
        
        // Sanitize and prepare user data
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        $bio = isset($_POST['bio']) ? sanitize_textarea_field($_POST['bio']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        
        // Validate required fields
        if (empty($display_name)) {
            $error_message = __('Display name is required.', 'videohub360-theme');
        } else {
            
            // Update user data
            $user_data = array(
                'ID' => $current_user_id,
                'display_name' => $display_name,
                'user_url' => $website,
                'description' => $bio,
            );
            
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
            } else {
                
                // Update social links
                $social_fields = array('twitter', 'facebook', 'youtube', 'instagram');
                foreach ($social_fields as $field) {
                    $value = isset($_POST[$field]) ? esc_url_raw($_POST[$field]) : '';
                    update_user_meta($current_user_id, '_vh360_' . $field, $value);
                }
                
                // Handle profile picture upload (avatar)
                if (!empty($_FILES['profile_picture']['name'])) {
                    // Prepare crop data from hidden fields with sanitization
                    $crop_data = array();
                    if (isset($_POST['avatar_crop_x'], $_POST['avatar_crop_y'], $_POST['avatar_crop_width'], $_POST['avatar_crop_height'])) {
                        $crop_data = array(
                            'x'             => floatval($_POST['avatar_crop_x']),
                            'y'             => floatval($_POST['avatar_crop_y']),
                            'width'         => floatval($_POST['avatar_crop_width']),
                            'height'        => floatval($_POST['avatar_crop_height']),
                            'source_width'  => isset($_POST['avatar_source_width']) ? absint($_POST['avatar_source_width']) : null,
                            'source_height' => isset($_POST['avatar_source_height']) ? absint($_POST['avatar_source_height']) : null,
                        );
                    }

                    // Process avatar upload using centralized helper
                    $result = vh360_process_profile_avatar_upload($_FILES['profile_picture'], $current_user_id, $crop_data);

                    if ($result['success']) {
                        // Delete old avatar attachment if exists
                        $old_avatar_id = get_user_meta($current_user_id, 'vh360_profile_picture_id', true);
                        if ($old_avatar_id && $old_avatar_id !== $result['attachment_id']) {
                            wp_delete_attachment($old_avatar_id, true);
                        }

                        // Save new attachment ID to user meta
                        update_user_meta($current_user_id, 'vh360_profile_picture_id', $result['attachment_id']);
                    } else {
                        $error_message = $result['error'];
                    }
                }
                
                // Handle profile picture removal (requires same nonce as form submission)
                if (isset($_POST['remove_profile_picture']) && $_POST['remove_profile_picture'] === '1') {
                    delete_user_meta($current_user_id, 'vh360_profile_picture_id');
                }
                
                // Handle cover image upload (skip for business-mode accounts)
                if (!$is_business_mode_account && !empty($_FILES['cover_image']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                    
                    $upload_overrides = array('test_form' => false);
                    $upload = wp_handle_upload($_FILES['cover_image'], $upload_overrides);
                    
                    if (isset($upload['error'])) {
                        $error_message = $upload['error'];
                    } else {
                        // Create attachment
                        $attachment = array(
                            'post_mime_type' => $upload['type'],
                            'post_title' => sanitize_file_name(basename($upload['file'])),
                            'post_content' => '',
                            'post_status' => 'inherit',
                        );
                        
                        $attach_id = wp_insert_attachment($attachment, $upload['file']);
                        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        
                        // Update user meta
                        update_user_meta($current_user_id, '_vh360_cover_image', $attach_id);
                    }
                }
                
                if (empty($error_message)) {
                    $success_message = __('Profile updated successfully!', 'videohub360-theme');
                    // Refresh user object
                    $user = get_userdata($current_user_id);
                }
            }
        }
    }
}

// Get current values
$display_name = $user->display_name;
$bio = get_the_author_meta('description', $current_user_id);
$website = $user->user_url;
$cover_image = vh360_get_user_cover_image($current_user_id);
$social_links = vh360_get_user_social_links($current_user_id);

// Get profile picture (avatar)
$profile_picture_id = get_user_meta($current_user_id, 'vh360_profile_picture_id', true);
$profile_picture_url = $profile_picture_id ? wp_get_attachment_image_url($profile_picture_id, 'thumbnail') : '';
?>

<div class="vh360-dashboard-profile">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Edit Profile', 'videohub360-theme'); ?></h1>
        <div class="vh360-dashboard-actions">
            <a href="<?php echo esc_url(vh360_get_profile_url($current_user_id)); ?>" class="vh360-dashboard-btn vh360-dashboard-btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <?php esc_html_e('View Public Profile', 'videohub360-theme'); ?>
            </a>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if (!empty($success_message)) : ?>
        <div class="vh360-message vh360-message-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)) : ?>
        <div class="vh360-message vh360-message-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Profile Form -->
    <div class="vh360-dashboard-card">
        <form method="post" enctype="multipart/form-data" class="vh360-profile-form">
            <?php wp_nonce_field('vh360_edit_profile_action', 'vh360_edit_profile_nonce'); ?>
            
            <!-- Hidden fields for avatar crop coordinates -->
            <input type="hidden" name="avatar_crop_x" value="">
            <input type="hidden" name="avatar_crop_y" value="">
            <input type="hidden" name="avatar_crop_width" value="">
            <input type="hidden" name="avatar_crop_height" value="">
            <input type="hidden" name="avatar_source_width" value="">
            <input type="hidden" name="avatar_source_height" value="">
            
            <?php if (!$is_business_mode_account) : ?>
            <!-- Cover Image -->
            <div class="vh360-form-group">
                <label class="vh360-form-label"><?php esc_html_e('Cover Image', 'videohub360-theme'); ?></label>
                <div class="vh360-cover-upload">
                    <div class="vh360-cover-preview" style="background-image: url(<?php echo $cover_image ? esc_url($cover_image) : ''; ?>);">
                        <?php if (!$cover_image) : ?>
                            <div class="vh360-cover-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <p><?php esc_html_e('Upload Cover Image', 'videohub360-theme'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*" class="vh360-file-input">
                    <label for="cover_image" class="vh360-file-label">
                        <?php esc_html_e('Choose File', 'videohub360-theme'); ?>
                    </label>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Profile Picture -->
            <div class="vh360-form-group">
                <label class="vh360-form-label"><?php esc_html_e('Profile Picture', 'videohub360-theme'); ?></label>
                <div class="vh360-avatar-upload">
                    <?php if ($profile_picture_url) : ?>
                        <div class="vh360-avatar-preview">
                            <img src="<?php echo esc_url($profile_picture_url); ?>" alt="<?php esc_attr_e('Current profile picture', 'videohub360-theme'); ?>">
                        </div>
                    <?php else : ?>
                        <div class="vh360-avatar-preview vh360-avatar-placeholder">
                            <?php echo get_avatar($current_user_id, 100); ?>
                        </div>
                    <?php endif; ?>
                    <div class="vh360-avatar-controls">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="vh360-file-input">
                        <label for="profile_picture" class="vh360-file-label vh360-btn-secondary">
                            <?php esc_html_e('Upload New Picture', 'videohub360-theme'); ?>
                        </label>
                        <?php if ($profile_picture_url) : ?>
                            <button type="submit" name="remove_profile_picture" value="1" class="vh360-btn-link vh360-btn-remove">
                                <?php esc_html_e('Remove', 'videohub360-theme'); ?>
                            </button>
                        <?php endif; ?>
                        <p class="vh360-form-help">
                            <?php esc_html_e('Recommended: Square image, at least 300x300 pixels. JPG, PNG, or GIF.', 'videohub360-theme'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Display Name -->
            <div class="vh360-form-group">
                <label for="display_name" class="vh360-form-label"><?php esc_html_e('Display Name', 'videohub360-theme'); ?> *</label>
                <input type="text" name="display_name" id="display_name" class="vh360-form-input" value="<?php echo esc_attr($display_name); ?>" required>
            </div>
            
            <!-- Bio -->
            <div class="vh360-form-group">
                <label for="bio" class="vh360-form-label"><?php esc_html_e('Bio', 'videohub360-theme'); ?></label>
                <textarea name="bio" id="bio" class="vh360-form-textarea" rows="4" maxlength="500"><?php echo esc_textarea($bio); ?></textarea>
                <span class="vh360-form-help"><?php esc_html_e('Maximum 500 characters', 'videohub360-theme'); ?></span>
            </div>
            
            <!-- Website -->
            <div class="vh360-form-group">
                <label for="website" class="vh360-form-label"><?php esc_html_e('Website', 'videohub360-theme'); ?></label>
                <input type="url" name="website" id="website" class="vh360-form-input" value="<?php echo esc_url($website); ?>">
            </div>
            
            <!-- Social Links -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Social Media', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="twitter" class="vh360-form-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                        </svg>
                        <?php esc_html_e('Twitter', 'videohub360-theme'); ?>
                    </label>
                    <input type="url" name="twitter" id="twitter" class="vh360-form-input" value="<?php echo isset($social_links['twitter']) ? esc_url($social_links['twitter']) : ''; ?>" placeholder="https://twitter.com/username">
                </div>
                
                <div class="vh360-form-group">
                    <label for="facebook" class="vh360-form-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                        </svg>
                        <?php esc_html_e('Facebook', 'videohub360-theme'); ?>
                    </label>
                    <input type="url" name="facebook" id="facebook" class="vh360-form-input" value="<?php echo isset($social_links['facebook']) ? esc_url($social_links['facebook']) : ''; ?>" placeholder="https://facebook.com/username">
                </div>
                
                <div class="vh360-form-group">
                    <label for="youtube" class="vh360-form-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"></path>
                            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#fff"></polygon>
                        </svg>
                        <?php esc_html_e('YouTube', 'videohub360-theme'); ?>
                    </label>
                    <input type="url" name="youtube" id="youtube" class="vh360-form-input" value="<?php echo isset($social_links['youtube']) ? esc_url($social_links['youtube']) : ''; ?>" placeholder="https://youtube.com/channel/...">
                </div>
                
                <div class="vh360-form-group">
                    <label for="instagram" class="vh360-form-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                        <?php esc_html_e('Instagram', 'videohub360-theme'); ?>
                    </label>
                    <input type="url" name="instagram" id="instagram" class="vh360-form-input" value="<?php echo isset($social_links['instagram']) ? esc_url($social_links['instagram']) : ''; ?>" placeholder="https://instagram.com/username">
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="vh360-form-actions">
                <button type="submit" class="vh360-dashboard-btn">
                    <?php esc_html_e('Save Changes', 'videohub360-theme'); ?>
                </button>
            </div>
        </form>
    </div><!-- .vh360-dashboard-card -->
    
</div><!-- .vh360-dashboard-profile -->

<style>
/* Form Styles */
.vh360-form-group {
    margin-bottom: 1.5rem;
}

.vh360-form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.vh360-form-input,
.vh360-form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
    color: var(--text-color);
    transition: var(--transition);
}

.vh360-form-input:focus,
.vh360-form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.vh360-form-textarea {
    resize: vertical;
    min-height: 100px;
}

.vh360-form-help {
    display: block;
    font-size: 0.75rem;
    color: var(--text-light);
    margin-top: 0.25rem;
}

.vh360-form-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.vh360-form-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1.5rem;
    color: var(--text-color);
}

/* Cover Upload */
.vh360-cover-upload {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-cover-preview {
    width: 100%;
    height: 200px;
    border-radius: var(--border-radius);
    background-size: cover;
    background-position: center;
    background-color: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed var(--border-color);
}

.vh360-cover-placeholder {
    text-align: center;
    color: var(--text-light);
}

.vh360-cover-placeholder p {
    margin: 0.5rem 0 0;
}

.vh360-file-input {
    display: none;
}

.vh360-file-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: var(--bg-light);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.vh360-file-label:hover {
    background: var(--bg-color);
    border-color: var(--primary-color);
}

/* Messages */
.vh360-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
}

.vh360-message-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid var(--success-color);
}

.vh360-message-error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--error-color);
    border: 1px solid var(--error-color);
}

/* Form Actions */
.vh360-form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

/* Checkbox Group */
.vh360-form-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.vh360-form-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: normal;
}
</style>
