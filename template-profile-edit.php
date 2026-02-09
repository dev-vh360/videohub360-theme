<?php
/**
 * Template Name: Profile Edit
 *
 * Template for editing user profile from the frontend.
 * Users can edit their display name, bio, email, website, social links,
 * and upload cover images.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// User authentication is now handled by community-gate.php
// No inline redirect needed here

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

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
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $website = isset($_POST['website']) ? esc_url_raw($_POST['website']) : '';
        
        // Validate required fields
        if (empty($display_name)) {
            $error_message = __('Display name is required.', 'videohub360-theme');
        } elseif (!is_email($email)) {
            $error_message = __('Please enter a valid email address.', 'videohub360-theme');
        } else {
            
            // Update user data
            $user_data = array(
                'ID' => $current_user_id,
                'display_name' => $display_name,
                'user_email' => $email,
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
                
                // Handle cover image upload
                if (!empty($_FILES['cover_image']['name'])) {
                    // Validate file size first (max 5MB)
                    $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
                    $file_size = $_FILES['cover_image']['size'];
                    
                    if ($file_size > $max_file_size) {
                        $error_message = __('File size too large. Maximum size is 5MB.', 'videohub360-theme');
                    } else {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        require_once(ABSPATH . 'wp-admin/includes/media.php');
                        
                        // Set upload overrides to allow only images
                        $upload_overrides = array(
                            'test_form' => false,
                            'mimes' => array(
                                'jpg|jpeg|jpe' => 'image/jpeg',
                                'png' => 'image/png',
                                'gif' => 'image/gif',
                            )
                        );
                        
                        $upload = wp_handle_upload($_FILES['cover_image'], $upload_overrides);
                        
                        if (isset($upload['error'])) {
                            $error_message = $upload['error'];
                        } else {
                            // Verify file type using WordPress function
                            $filetype = wp_check_filetype($upload['file']);
                            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
                            
                            if (!in_array($filetype['type'], $allowed_types)) {
                                // Delete uploaded file if wrong type
                                @unlink($upload['file']);
                                $error_message = __('Invalid file type. Please upload a JPG, PNG, or GIF image.', 'videohub360-theme');
                            } else {
                                // Create attachment with sanitized filename
                                $filename = sanitize_file_name($_FILES['cover_image']['name']);
                                $attachment = array(
                                    'post_mime_type' => $filetype['type'],
                                    'post_title' => pathinfo($filename, PATHINFO_FILENAME),
                                    'post_content' => '',
                                    'post_status' => 'inherit'
                                );
                                
                                $attach_id = wp_insert_attachment($attachment, $upload['file']);
                                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                                wp_update_attachment_metadata($attach_id, $attach_data);
                                
                                // Save attachment ID to user meta
                                update_user_meta($current_user_id, '_vh360_cover_image', $attach_id);
                            }
                        }
                    }
                }
                

                // Handle profile picture upload (avatar)
                if (empty($error_message) && !empty($_FILES['profile_picture']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    require_once(ABSPATH . 'wp-admin/includes/media.php');

                    // Allow only common image types
                    $upload_overrides = array(
                        'test_form' => false,
                        'mimes'     => array(
                            'jpg|jpeg|jpe' => 'image/jpeg',
                            'png'         => 'image/png',
                            'gif'         => 'image/gif',
                        ),
                    );

                    $file    = $_FILES['profile_picture'];
                    $upload  = wp_handle_upload($file, $upload_overrides);

                    if (isset($upload['error'])) {
                        $error_message = $upload['error'];
                    } else {
                        $image_path = $upload['file'];

                        // Smart mode: crop to centered square and resize to 300x300.
                        $editor = wp_get_image_editor($image_path);
                        if (!is_wp_error($editor)) {
                            $size = $editor->get_size();
                            if (!empty($size['width']) && !empty($size['height'])) {
                                $min_side = min($size['width'], $size['height']);
                                $x        = max(0, ($size['width']  - $min_side) / 2);
                                $y        = max(0, ($size['height'] - $min_side) / 2);

                                // Crop to square, then resize down to 300x300.
                                $editor->crop($x, $y, $min_side, $min_side);
                                $editor->resize(300, 300, true);
                                $saved = $editor->save($image_path);

                                if (!is_wp_error($saved)) {
                                    $filetype = wp_check_filetype($image_path);
                                    $filename = sanitize_file_name($file['name']);

                                    $attachment = array(
                                        'post_mime_type' => $filetype['type'],
                                        'post_title'     => pathinfo($filename, PATHINFO_FILENAME),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit',
                                    );

                                    $attach_id   = wp_insert_attachment($attachment, $image_path);
                                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
                                    wp_update_attachment_metadata($attach_id, $attach_data);

                                    // Save attachment ID to user meta.
                                    update_user_meta($current_user_id, 'vh360_profile_picture_id', $attach_id);
                                }
                            }
                        }
                    }
                }

                if (empty($error_message)) {
                    $success_message = __('Profile updated successfully!', 'videohub360-theme');
                    
                    // Refresh user data
                    $user = get_userdata($current_user_id);
                }
            }
        }
    }
}

// Get current user data
$display_name = $user->display_name;
$bio = get_the_author_meta('description', $current_user_id);
$email = $user->user_email;
$website = $user->user_url;
$social_links = vh360_get_user_social_links($current_user_id);
$cover_image = vh360_get_user_cover_image($current_user_id);
$profile_avatar_id  = get_user_meta($current_user_id, 'vh360_profile_picture_id', true);
$profile_avatar_url = $profile_avatar_id ? wp_get_attachment_image_url($profile_avatar_id, 'thumbnail') : '';

get_header();
?>

<div id="primary" class="site-content">
    <div class="container">
        <main id="main" class="content-area vh360-profile-edit-page">
            
            <div class="vh360-profile-edit-header">
                <h1><?php esc_html_e('Edit Profile', 'videohub360-theme'); ?></h1>
                <a href="<?php echo esc_url(get_author_posts_url($current_user_id)); ?>" class="vh360-btn-secondary">
                    <?php esc_html_e('View Profile', 'videohub360-theme'); ?>
                </a>
            </div>

            <?php if ($success_message) : ?>
                <div class="vh360-message vh360-message-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php echo esc_html($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message) : ?>
                <div class="vh360-message vh360-message-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <?php echo esc_html($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="vh360-profile-edit-form">
                <?php wp_nonce_field('vh360_edit_profile_action', 'vh360_edit_profile_nonce'); ?>

                <div class="vh360-form-section">
                    <h2><?php esc_html_e('Basic Information', 'videohub360-theme'); ?></h2>

                    <div class="vh360-form-group">
                        <label for="display_name"><?php esc_html_e('Display Name', 'videohub360-theme'); ?> <span class="required">*</span></label>
                        <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($display_name); ?>" required>
                    </div>

                    <div class="vh360-form-group">
                        <label for="bio"><?php esc_html_e('Bio', 'videohub360-theme'); ?></label>
                        <textarea id="bio" name="bio" rows="5" placeholder="<?php esc_attr_e('Tell the community about yourself...', 'videohub360-theme'); ?>"><?php echo esc_textarea($bio); ?></textarea>
                    </div>

                    <div class="vh360-form-group">
                        <label for="email"><?php esc_html_e('Email', 'videohub360-theme'); ?> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" required>
                    </div>

                    <div class="vh360-form-group">
                        <label for="website"><?php esc_html_e('Website', 'videohub360-theme'); ?></label>
                        <input type="url" id="website" name="website" value="<?php echo esc_attr($website); ?>" placeholder="https://">
                    </div>
                </div>

                <div class="vh360-form-section">
                    <h2><?php esc_html_e('Social Links', 'videohub360-theme'); ?></h2>

                    <div class="vh360-form-group">
                        <label for="twitter"><?php esc_html_e('Twitter/X', 'videohub360-theme'); ?></label>
                        <input type="url" id="twitter" name="twitter" value="<?php echo esc_attr(isset($social_links['twitter']) ? $social_links['twitter'] : ''); ?>" placeholder="https://twitter.com/username">
                    </div>

                    <div class="vh360-form-group">
                        <label for="facebook"><?php esc_html_e('Facebook', 'videohub360-theme'); ?></label>
                        <input type="url" id="facebook" name="facebook" value="<?php echo esc_attr(isset($social_links['facebook']) ? $social_links['facebook'] : ''); ?>" placeholder="https://facebook.com/username">
                    </div>

                    <div class="vh360-form-group">
                        <label for="youtube"><?php esc_html_e('YouTube', 'videohub360-theme'); ?></label>
                        <input type="url" id="youtube" name="youtube" value="<?php echo esc_attr(isset($social_links['youtube']) ? $social_links['youtube'] : ''); ?>" placeholder="https://youtube.com/@username">
                    </div>

                    <div class="vh360-form-group">
                        <label for="instagram"><?php esc_html_e('Instagram', 'videohub360-theme'); ?></label>
                        <input type="url" id="instagram" name="instagram" value="<?php echo esc_attr(isset($social_links['instagram']) ? $social_links['instagram'] : ''); ?>" placeholder="https://instagram.com/username">
                    </div>
                </div>

                
                <div class="vh360-form-section">
                    <h2><?php esc_html_e('Profile Picture', 'videohub360-theme'); ?></h2>

                    <?php if (!empty($profile_avatar_url)) : ?>
                        <div class="vh360-avatar-preview">
                            <img src="<?php echo esc_url($profile_avatar_url); ?>" alt="<?php esc_attr_e('Current profile picture', 'videohub360-theme'); ?>">
                        </div>
                    <?php else : ?>
                        <p class="vh360-form-help">
                            <?php esc_html_e('You have not uploaded a profile picture yet. A default avatar will be used until you do.', 'videohub360-theme'); ?>
                        </p>
                    <?php endif; ?>

                    <div class="vh360-form-group">
                        <label for="profile_picture"><?php esc_html_e('Upload Profile Picture', 'videohub360-theme'); ?></label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <p class="vh360-form-help">
                            <?php esc_html_e('Recommended size: at least 300x300 pixels. JPG or PNG.', 'videohub360-theme'); ?>
                        </p>
                    </div>
                </div>

<div class="vh360-form-section">
                    <h2><?php esc_html_e('Cover Image', 'videohub360-theme'); ?></h2>

                    <?php if ($cover_image) : ?>
                        <div class="vh360-cover-preview">
                            <img src="<?php echo esc_url($cover_image); ?>" alt="<?php esc_attr_e('Current cover image', 'videohub360-theme'); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="vh360-form-group">
                        <label for="cover_image"><?php esc_html_e('Upload New Cover Image', 'videohub360-theme'); ?></label>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <p class="vh360-form-help"><?php esc_html_e('Recommended size: 1200x400 pixels. JPG, PNG, or GIF.', 'videohub360-theme'); ?></p>
                    </div>
                </div>

                <div class="vh360-form-actions">
                    <button type="submit" class="vh360-btn-primary">
                        <?php esc_html_e('Save Changes', 'videohub360-theme'); ?>
                    </button>
                    <a href="<?php echo esc_url(get_author_posts_url($current_user_id)); ?>" class="vh360-btn-secondary">
                        <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                    </a>
                </div>
            </form>

        </main><!-- #main -->
    </div><!-- .container -->
</div><!-- #primary -->

<style>
/* Profile edit page styles */
.vh360-profile-edit-page {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.vh360-profile-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.vh360-profile-edit-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-color);
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
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.vh360-message-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Form styles */
.vh360-profile-edit-form {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 2rem;
}

.vh360-form-section {
    margin-bottom: 2rem;
}

.vh360-form-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-color);
    margin: 0 0 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.vh360-form-group {
    margin-bottom: 1.5rem;
}

.vh360-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

.vh360-form-group .required {
    color: #ef4444;
}

.vh360-form-group input[type="text"],
.vh360-form-group input[type="email"],
.vh360-form-group input[type="url"],
.vh360-form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
    color: var(--text-color);
    background: var(--bg-color);
    transition: var(--transition);
}

.vh360-form-group input:focus,
.vh360-form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.vh360-form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.vh360-form-help {
    font-size: 0.875rem;
    color: var(--text-light);
    margin: 0.5rem 0 0;
}

/* Cover image preview */
.vh360-cover-preview {
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    overflow: hidden;
    max-width: 100%;
}

.vh360-cover-preview img {
    width: 100%;
    height: auto;
    max-height: 200px;
    object-fit: cover;
}


.vh360-avatar-preview {
    margin-bottom: 1rem;
}

.vh360-avatar-preview img {
    width: 96px;
    height: 96px;
    border-radius: 999px;
    object-fit: cover;
    border: 2px solid var(--border-color);
}


/* Form actions */
.vh360-form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

/* Buttons */
.vh360-btn-primary,
.vh360-btn-secondary {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: var(--transition);
}

.vh360-btn-primary {
    background: var(--primary-color);
    color: #ffffff;
}

.vh360-btn-primary:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.vh360-btn-secondary {
    background: var(--bg-light);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.vh360-btn-secondary:hover {
    background: var(--border-color);
}

@media (max-width: 768px) {
    .vh360-profile-edit-page {
        margin: 1rem auto;
    }
    
    .vh360-profile-edit-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .vh360-profile-edit-form {
        padding: 1.5rem;
    }
    
    .vh360-form-actions {
        flex-direction: column;
    }
    
    .vh360-btn-primary,
    .vh360-btn-secondary {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php
get_footer();
