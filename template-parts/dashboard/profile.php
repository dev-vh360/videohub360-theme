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
                
                // Update business fields for professional/organization accounts
                $account_type = function_exists('vh360_get_user_account_type') ? vh360_get_user_account_type($current_user_id) : 'creator';
                if (in_array($account_type, array('professional', 'organization'), true)) {
                    // Text fields
                    if (isset($_POST['business_name'])) {
                        update_user_meta($current_user_id, '_vh360_business_name', sanitize_text_field(wp_unslash($_POST['business_name'])));
                    }
                    if (isset($_POST['business_type'])) {
                        update_user_meta($current_user_id, '_vh360_business_type', sanitize_text_field(wp_unslash($_POST['business_type'])));
                    }
                    if (isset($_POST['credentials'])) {
                        update_user_meta($current_user_id, '_vh360_credentials', sanitize_text_field(wp_unslash($_POST['credentials'])));
                    }
                    if (isset($_POST['location'])) {
                        update_user_meta($current_user_id, '_vh360_location', sanitize_text_field(wp_unslash($_POST['location'])));
                    }
                    if (isset($_POST['contact_phone'])) {
                        update_user_meta($current_user_id, '_vh360_contact_phone', sanitize_text_field(wp_unslash($_POST['contact_phone'])));
                    }
                    
                    // Textarea fields
                    if (isset($_POST['specialties'])) {
                        update_user_meta($current_user_id, '_vh360_specialties', sanitize_textarea_field(wp_unslash($_POST['specialties'])));
                    }
                    if (isset($_POST['pricing_info'])) {
                        update_user_meta($current_user_id, '_vh360_pricing_info', sanitize_textarea_field(wp_unslash($_POST['pricing_info'])));
                    }
                    if (isset($_POST['insurance_info'])) {
                        update_user_meta($current_user_id, '_vh360_insurance_info', sanitize_textarea_field(wp_unslash($_POST['insurance_info'])));
                    }
                    
                    // Email field
                    if (isset($_POST['contact_email'])) {
                        update_user_meta($current_user_id, '_vh360_contact_email', sanitize_email(wp_unslash($_POST['contact_email'])));
                    }
                    
                    // URL field
                    if (isset($_POST['booking_url'])) {
                        update_user_meta($current_user_id, '_vh360_booking_url', esc_url_raw(wp_unslash($_POST['booking_url'])));
                    }
                    
                    // Checkboxes
                    update_user_meta($current_user_id, '_vh360_telehealth', isset($_POST['telehealth']) ? '1' : '0');
                    update_user_meta($current_user_id, '_vh360_accepting_new_clients', isset($_POST['accepting_new_clients']) ? '1' : '0');
                }
                
                // Handle cover image upload
                if (!empty($_FILES['cover_image']['name'])) {
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

// Get business fields for professional/organization accounts
$account_type = function_exists('vh360_get_user_account_type') ? vh360_get_user_account_type($current_user_id) : 'creator';
$is_business_account = in_array($account_type, array('professional', 'organization'), true);

if ($is_business_account) {
    $business_name = get_user_meta($current_user_id, '_vh360_business_name', true);
    $business_type = get_user_meta($current_user_id, '_vh360_business_type', true);
    $credentials = get_user_meta($current_user_id, '_vh360_credentials', true);
    $specialties = get_user_meta($current_user_id, '_vh360_specialties', true);
    $location = get_user_meta($current_user_id, '_vh360_location', true);
    $telehealth = get_user_meta($current_user_id, '_vh360_telehealth', true);
    $accepting_clients = get_user_meta($current_user_id, '_vh360_accepting_new_clients', true);
    $booking_url = get_user_meta($current_user_id, '_vh360_booking_url', true);
    $contact_phone = get_user_meta($current_user_id, '_vh360_contact_phone', true);
    $contact_email = get_user_meta($current_user_id, '_vh360_contact_email', true);
    $pricing_info = get_user_meta($current_user_id, '_vh360_pricing_info', true);
    $insurance_info = get_user_meta($current_user_id, '_vh360_insurance_info', true);
}
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
            
            <?php if ($is_business_account) : ?>
            <!-- Business Information Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Business Information', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="business_name" class="vh360-form-label"><?php esc_html_e('Business Name', 'videohub360-theme'); ?></label>
                    <input type="text" name="business_name" id="business_name" class="vh360-form-input" value="<?php echo esc_attr($business_name); ?>" placeholder="<?php esc_attr_e('Your business or practice name', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="business_type" class="vh360-form-label"><?php esc_html_e('Business Type', 'videohub360-theme'); ?></label>
                    <input type="text" name="business_type" id="business_type" class="vh360-form-input" value="<?php echo esc_attr($business_type); ?>" placeholder="<?php esc_attr_e('e.g., Licensed Therapist, Consulting Firm', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="credentials" class="vh360-form-label"><?php esc_html_e('Credentials', 'videohub360-theme'); ?></label>
                    <input type="text" name="credentials" id="credentials" class="vh360-form-input" value="<?php echo esc_attr($credentials); ?>" placeholder="<?php esc_attr_e('Professional credentials, certifications, licenses', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="location" class="vh360-form-label"><?php esc_html_e('Location', 'videohub360-theme'); ?></label>
                    <input type="text" name="location" id="location" class="vh360-form-input" value="<?php echo esc_attr($location); ?>" placeholder="<?php esc_attr_e('City, State', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="specialties" class="vh360-form-label"><?php esc_html_e('Specialties', 'videohub360-theme'); ?></label>
                    <textarea name="specialties" id="specialties" rows="4" class="vh360-form-textarea" placeholder="<?php esc_attr_e('Describe your areas of expertise and specialization', 'videohub360-theme'); ?>"><?php echo esc_textarea($specialties); ?></textarea>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-form-label"><?php esc_html_e('Service Options', 'videohub360-theme'); ?></label>
                    <div class="vh360-form-checkbox-group">
                        <label class="vh360-form-checkbox-label">
                            <input type="checkbox" name="telehealth" value="1" <?php checked($telehealth, '1'); ?>>
                            <?php esc_html_e('Telehealth/Remote services available', 'videohub360-theme'); ?>
                        </label>
                        <label class="vh360-form-checkbox-label">
                            <input type="checkbox" name="accepting_new_clients" value="1" <?php checked($accepting_clients, '1'); ?>>
                            <?php esc_html_e('Currently accepting new clients', 'videohub360-theme'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Contact Information', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="contact_phone" class="vh360-form-label"><?php esc_html_e('Phone Number', 'videohub360-theme'); ?></label>
                    <input type="text" name="contact_phone" id="contact_phone" class="vh360-form-input" value="<?php echo esc_attr($contact_phone); ?>" placeholder="<?php esc_attr_e('Business phone number', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="contact_email" class="vh360-form-label"><?php esc_html_e('Contact Email', 'videohub360-theme'); ?></label>
                    <input type="email" name="contact_email" id="contact_email" class="vh360-form-input" value="<?php echo esc_attr($contact_email); ?>" placeholder="<?php esc_attr_e('Business contact email', 'videohub360-theme'); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="booking_url" class="vh360-form-label"><?php esc_html_e('Booking URL', 'videohub360-theme'); ?></label>
                    <input type="url" name="booking_url" id="booking_url" class="vh360-form-input" value="<?php echo esc_attr($booking_url); ?>" placeholder="<?php esc_attr_e('https://your-booking-site.com', 'videohub360-theme'); ?>">
                    <span class="vh360-form-help"><?php esc_html_e('URL for online booking or scheduling', 'videohub360-theme'); ?></span>
                </div>
            </div>
            
            <!-- Additional Information Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Additional Information', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="pricing_info" class="vh360-form-label"><?php esc_html_e('Pricing Information', 'videohub360-theme'); ?></label>
                    <textarea name="pricing_info" id="pricing_info" rows="4" class="vh360-form-textarea" placeholder="<?php esc_attr_e('Pricing details, rates, packages, etc.', 'videohub360-theme'); ?>"><?php echo esc_textarea($pricing_info); ?></textarea>
                </div>
                
                <div class="vh360-form-group">
                    <label for="insurance_info" class="vh360-form-label"><?php esc_html_e('Insurance Information', 'videohub360-theme'); ?></label>
                    <textarea name="insurance_info" id="insurance_info" rows="4" class="vh360-form-textarea" placeholder="<?php esc_attr_e('Insurance providers accepted', 'videohub360-theme'); ?>"><?php echo esc_textarea($insurance_info); ?></textarea>
                </div>
            </div>
            <?php endif; ?>
            
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
