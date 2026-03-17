<?php
/**
 * Dashboard Business Profile Editor
 *
 * Front-end editor for Business profile fields
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();

// Check if user has permission (professional or organization only)
$account_type = vh360_get_user_account_type($current_user_id);
if (!in_array($account_type, array('professional', 'organization'), true)) {
    echo '<p>' . esc_html__('This section is only available for professional and organization accounts.', 'videohub360-theme') . '</p>';
    return;
}

// Get current values
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

// Check for success message
$success = isset($_GET['business_profile_updated']) && $_GET['business_profile_updated'] === 'success';
?>

<div class="vh360-dashboard-section">
    <div class="vh360-dashboard-header">
        <h2 class="vh360-dashboard-title"><?php esc_html_e('Business Profile', 'videohub360-theme'); ?></h2>
        <p class="vh360-dashboard-description">
            <?php esc_html_e('Manage your business information displayed on your public profile.', 'videohub360-theme'); ?>
        </p>
    </div>
    
    <?php if ($success) : ?>
        <div class="vh360-dashboard-success-message">
            <?php esc_html_e('Business profile updated successfully!', 'videohub360-theme'); ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="vh360-dashboard-form vh360-business-profile-form">
        <?php wp_nonce_field('vh360_save_business_profile', 'vh360_save_business_profile_nonce'); ?>
        
        <div class="vh360-dashboard-form-section">
            <h3><?php esc_html_e('Business Information', 'videohub360-theme'); ?></h3>
            
            <div class="vh360-dashboard-form-group">
                <label for="business_name"><?php esc_html_e('Business Name', 'videohub360-theme'); ?></label>
                <input 
                    type="text" 
                    name="business_name" 
                    id="business_name" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($business_name); ?>"
                    placeholder="<?php esc_attr_e('Your business or practice name', 'videohub360-theme'); ?>"
                >
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="business_type"><?php esc_html_e('Business Type', 'videohub360-theme'); ?></label>
                <input 
                    type="text" 
                    name="business_type" 
                    id="business_type" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($business_type); ?>"
                    placeholder="<?php esc_attr_e('e.g., Licensed Therapist, Consulting Firm', 'videohub360-theme'); ?>"
                >
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="credentials"><?php esc_html_e('Credentials', 'videohub360-theme'); ?></label>
                <input 
                    type="text" 
                    name="credentials" 
                    id="credentials" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($credentials); ?>"
                    placeholder="<?php esc_attr_e('Professional credentials, certifications, licenses', 'videohub360-theme'); ?>"
                >
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="location"><?php esc_html_e('Location', 'videohub360-theme'); ?></label>
                <input 
                    type="text" 
                    name="location" 
                    id="location" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($location); ?>"
                    placeholder="<?php esc_attr_e('City, State', 'videohub360-theme'); ?>"
                >
            </div>
            
            <?php
            // Display member category if enabled
            $members_options = get_option('vh360_members_options', array());
            if (!empty($members_options['enable_category_filter'])) :
                $member_category = get_user_meta($current_user_id, '_vh360_member_category', true);
                $category_choices = function_exists('vh360_get_member_category_choices') 
                    ? vh360_get_member_category_choices() 
                    : array();
                
                if (!empty($category_choices)) :
            ?>
            <div class="vh360-dashboard-form-group">
                <label for="member_category"><?php esc_html_e('Professional Category', 'videohub360-theme'); ?></label>
                <select 
                    name="member_category" 
                    id="member_category" 
                    class="vh360-dashboard-select"
                >
                    <option value=""><?php esc_html_e('Select a category', 'videohub360-theme'); ?></option>
                    <?php foreach ($category_choices as $slug => $label) : ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($member_category, $slug); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="vh360-dashboard-form-help">
                    <?php esc_html_e('This category helps members find you in the directory.', 'videohub360-theme'); ?>
                </small>
            </div>
            <?php endif; endif; ?>
        </div>
        
        <div class="vh360-dashboard-form-section">
            <h3><?php esc_html_e('Services & Specialties', 'videohub360-theme'); ?></h3>
            
            <div class="vh360-dashboard-form-group">
                <label for="specialties"><?php esc_html_e('Specialties', 'videohub360-theme'); ?></label>
                <textarea 
                    name="specialties" 
                    id="specialties" 
                    rows="4" 
                    class="vh360-dashboard-textarea"
                    placeholder="<?php esc_attr_e('Describe your areas of expertise and specialization', 'videohub360-theme'); ?>"
                ><?php echo esc_textarea($specialties); ?></textarea>
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label><?php esc_html_e('Service Options', 'videohub360-theme'); ?></label>
                <div class="vh360-dashboard-checkbox-group">
                    <label class="vh360-dashboard-checkbox-label">
                        <input 
                            type="checkbox" 
                            name="telehealth" 
                            value="1" 
                            <?php checked($telehealth, '1'); ?>
                        >
                        <?php esc_html_e('Telehealth/Remote services available', 'videohub360-theme'); ?>
                    </label>
                    <label class="vh360-dashboard-checkbox-label">
                        <input 
                            type="checkbox" 
                            name="accepting_new_clients" 
                            value="1" 
                            <?php checked($accepting_clients, '1'); ?>
                        >
                        <?php esc_html_e('Currently accepting new clients', 'videohub360-theme'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="vh360-dashboard-form-section">
            <h3><?php esc_html_e('Contact Information', 'videohub360-theme'); ?></h3>
            
            <div class="vh360-dashboard-form-group">
                <label for="contact_phone"><?php esc_html_e('Phone Number', 'videohub360-theme'); ?></label>
                <input 
                    type="text" 
                    name="contact_phone" 
                    id="contact_phone" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($contact_phone); ?>"
                    placeholder="<?php esc_attr_e('Business phone number', 'videohub360-theme'); ?>"
                >
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="contact_email"><?php esc_html_e('Contact Email', 'videohub360-theme'); ?></label>
                <input 
                    type="email" 
                    name="contact_email" 
                    id="contact_email" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($contact_email); ?>"
                    placeholder="<?php esc_attr_e('Business contact email', 'videohub360-theme'); ?>"
                >
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="booking_url"><?php esc_html_e('Booking URL', 'videohub360-theme'); ?></label>
                <input 
                    type="url" 
                    name="booking_url" 
                    id="booking_url" 
                    class="vh360-dashboard-input" 
                    value="<?php echo esc_attr($booking_url); ?>"
                    placeholder="<?php esc_attr_e('https://your-booking-site.com', 'videohub360-theme'); ?>"
                >
                <p class="vh360-dashboard-field-description">
                    <?php esc_html_e('URL for online booking or scheduling', 'videohub360-theme'); ?>
                </p>
            </div>
        </div>
        
        <div class="vh360-dashboard-form-section">
            <h3><?php esc_html_e('Additional Information (Optional)', 'videohub360-theme'); ?></h3>
            
            <div class="vh360-dashboard-form-group">
                <label for="pricing_info"><?php esc_html_e('Pricing Information', 'videohub360-theme'); ?></label>
                <textarea 
                    name="pricing_info" 
                    id="pricing_info" 
                    rows="4" 
                    class="vh360-dashboard-textarea"
                    placeholder="<?php esc_attr_e('Pricing details, rates, packages, etc.', 'videohub360-theme'); ?>"
                ><?php echo esc_textarea($pricing_info); ?></textarea>
            </div>
            
            <div class="vh360-dashboard-form-group">
                <label for="insurance_info"><?php esc_html_e('Insurance Information', 'videohub360-theme'); ?></label>
                <textarea 
                    name="insurance_info" 
                    id="insurance_info" 
                    rows="4" 
                    class="vh360-dashboard-textarea"
                    placeholder="<?php esc_attr_e('Insurance providers accepted', 'videohub360-theme'); ?>"
                ><?php echo esc_textarea($insurance_info); ?></textarea>
            </div>
        </div>
        
        <div class="vh360-dashboard-form-actions">
            <button type="submit" name="vh360_save_business_profile_submit" class="vh360-dashboard-button vh360-button-primary">
                <?php esc_html_e('Save Business Profile', 'videohub360-theme'); ?>
            </button>
        </div>
    </form>
</div>
