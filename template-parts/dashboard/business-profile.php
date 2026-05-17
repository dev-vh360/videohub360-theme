<?php
/**
 * Dashboard Business Profile Editor
 *
 * Front-end editor for Business profile fields, now rendered via the centralized
 * VH360 Profile Fields Manager. Hard-coded field HTML is replaced by
 * vh360_render_profile_fields() which reads all built-in business fields from
 * the field registry. Existing meta keys and saved user data are fully preserved.
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
        
        <!-- Business profile fields rendered via the centralized Profile Fields Manager -->
        <?php if (function_exists('vh360_render_profile_fields')) : ?>
            <?php vh360_render_profile_fields($current_user_id, 'business_edit'); ?>
        <?php endif; ?>
        
        <!-- Member category (system field — managed outside the profile fields manager) -->
        <?php
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
        
        <div class="vh360-dashboard-form-actions">
            <button type="submit" name="vh360_save_business_profile_submit" class="vh360-dashboard-button vh360-button-primary">
                <?php esc_html_e('Save Business Profile', 'videohub360-theme'); ?>
            </button>
        </div>
    </form>
</div>

