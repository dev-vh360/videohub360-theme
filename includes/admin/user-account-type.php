<?php
/**
 * User Account Type and Business Profile Fields
 *
 * Adds account type selector and business profile fields to WordPress user profile edit screen.
 * Includes secure save with nonce verification and capability checks.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Display account type and business fields on user profile edit screen
 *
 * @param WP_User $user The user object being edited
 */
function vh360_display_account_type_fields($user) {
    // Check if current user has permission to edit this user
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }
    
    // Get current values
    $account_type = get_user_meta($user->ID, '_vh360_account_type', true);
    if (!$account_type) {
        $account_type = 'creator';
    }
    
    $business_name = get_user_meta($user->ID, '_vh360_business_name', true);
    $business_type = get_user_meta($user->ID, '_vh360_business_type', true);
    $credentials = get_user_meta($user->ID, '_vh360_credentials', true);
    $specialties = get_user_meta($user->ID, '_vh360_specialties', true);
    $location = get_user_meta($user->ID, '_vh360_location', true);
    $telehealth = get_user_meta($user->ID, '_vh360_telehealth', true);
    $accepting_clients = get_user_meta($user->ID, '_vh360_accepting_new_clients', true);
    $booking_url = get_user_meta($user->ID, '_vh360_booking_url', true);
    $contact_phone = get_user_meta($user->ID, '_vh360_contact_phone', true);
    $contact_email = get_user_meta($user->ID, '_vh360_contact_email', true);
    $pricing_info = get_user_meta($user->ID, '_vh360_pricing_info', true);
    $insurance_info = get_user_meta($user->ID, '_vh360_insurance_info', true);
    
    ?>
    <h2><?php esc_html_e('VideoHub360 Account Settings', 'videohub360-theme'); ?></h2>
    
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="vh360_account_type"><?php esc_html_e('Account Type', 'videohub360-theme'); ?></label>
            </th>
            <td>
                <select name="vh360_account_type" id="vh360_account_type">
                    <option value="creator" <?php selected($account_type, 'creator'); ?>>
                        <?php esc_html_e('Creator (Content Creator)', 'videohub360-theme'); ?>
                    </option>
                    <option value="professional" <?php selected($account_type, 'professional'); ?>>
                        <?php esc_html_e('Professional (Individual Professional)', 'videohub360-theme'); ?>
                    </option>
                    <option value="organization" <?php selected($account_type, 'organization'); ?>>
                        <?php esc_html_e('Organization (Business/Company)', 'videohub360-theme'); ?>
                    </option>
                    <option value="client" <?php selected($account_type, 'client'); ?>>
                        <?php esc_html_e('Client (Service Consumer)', 'videohub360-theme'); ?>
                    </option>
                </select>
                <p class="description">
                    <?php esc_html_e('Select the account type. Professionals and Organizations will display a business profile. Clients will have a minimal profile. Creators use the standard profile/channel view.', 'videohub360-theme'); ?>
                </p>
                <?php wp_nonce_field('vh360_account_type_save', 'vh360_account_type_nonce'); ?>
            </td>
        </tr>
    </table>
    
    <div id="vh360-business-fields" style="display: <?php echo (in_array($account_type, array('professional', 'organization'), true)) ? 'block' : 'none'; ?>;">
        <h3><?php esc_html_e('Business Profile Information', 'videohub360-theme'); ?></h3>
        <p class="description"><?php esc_html_e('These fields are only displayed for Professional and Organization account types.', 'videohub360-theme'); ?></p>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="vh360_business_name"><?php esc_html_e('Business Name', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="text" name="vh360_business_name" id="vh360_business_name" 
                           value="<?php echo esc_attr($business_name); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Official business or practice name', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_business_type"><?php esc_html_e('Business Type', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="text" name="vh360_business_type" id="vh360_business_type" 
                           value="<?php echo esc_attr($business_type); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('e.g., Licensed Therapist, Consulting Firm, etc.', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_credentials"><?php esc_html_e('Credentials', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="text" name="vh360_credentials" id="vh360_credentials" 
                           value="<?php echo esc_attr($credentials); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Professional credentials, certifications, licenses', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_specialties"><?php esc_html_e('Specialties', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <textarea name="vh360_specialties" id="vh360_specialties" rows="4" class="large-text"><?php echo esc_textarea($specialties); ?></textarea>
                    <p class="description"><?php esc_html_e('Areas of expertise or specialization', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_location"><?php esc_html_e('Location', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="text" name="vh360_location" id="vh360_location" 
                           value="<?php echo esc_attr($location); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Business location (city, state, etc.)', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Service Options', 'videohub360-theme'); ?></th>
                <td>
                    <fieldset>
                        <label for="vh360_telehealth">
                            <input type="checkbox" name="vh360_telehealth" id="vh360_telehealth" value="1" 
                                   <?php checked($telehealth, '1'); ?>>
                            <?php esc_html_e('Telehealth/Remote services available', 'videohub360-theme'); ?>
                        </label>
                        <br>
                        <label for="vh360_accepting_new_clients">
                            <input type="checkbox" name="vh360_accepting_new_clients" id="vh360_accepting_new_clients" value="1" 
                                   <?php checked($accepting_clients, '1'); ?>>
                            <?php esc_html_e('Currently accepting new clients', 'videohub360-theme'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_booking_url"><?php esc_html_e('Booking URL', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="url" name="vh360_booking_url" id="vh360_booking_url" 
                           value="<?php echo esc_attr($booking_url); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('URL for online booking/scheduling', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_contact_phone"><?php esc_html_e('Contact Phone', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="text" name="vh360_contact_phone" id="vh360_contact_phone" 
                           value="<?php echo esc_attr($contact_phone); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Business phone number', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_contact_email"><?php esc_html_e('Contact Email', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <input type="email" name="vh360_contact_email" id="vh360_contact_email" 
                           value="<?php echo esc_attr($contact_email); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Business contact email', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_pricing_info"><?php esc_html_e('Pricing Information', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <textarea name="vh360_pricing_info" id="vh360_pricing_info" rows="4" class="large-text"><?php echo esc_textarea($pricing_info); ?></textarea>
                    <p class="description"><?php esc_html_e('Pricing details (optional)', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="vh360_insurance_info"><?php esc_html_e('Insurance Information', 'videohub360-theme'); ?></label>
                </th>
                <td>
                    <textarea name="vh360_insurance_info" id="vh360_insurance_info" rows="4" class="large-text"><?php echo esc_textarea($insurance_info); ?></textarea>
                    <p class="description"><?php esc_html_e('Insurance accepted (optional)', 'videohub360-theme'); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#vh360_account_type').on('change', function() {
            var accountType = $(this).val();
            if (accountType === 'professional' || accountType === 'organization') {
                $('#vh360-business-fields').slideDown();
            } else {
                $('#vh360-business-fields').slideUp();
            }
        });
    });
    </script>
    <?php
}
add_action('show_user_profile', 'vh360_display_account_type_fields');
add_action('edit_user_profile', 'vh360_display_account_type_fields');

/**
 * Save account type and business profile fields
 *
 * @param int $user_id User ID being saved
 */
function vh360_save_account_type_fields($user_id) {
    // Verify nonce
    if (!isset($_POST['vh360_account_type_nonce']) || 
        !wp_verify_nonce($_POST['vh360_account_type_nonce'], 'vh360_account_type_save')) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options') || !current_user_can('edit_user', $user_id)) {
        return;
    }
    
    // Save account type (whitelist validation)
    if (isset($_POST['vh360_account_type'])) {
        $account_type = sanitize_text_field(wp_unslash($_POST['vh360_account_type']));
        $valid_types = array('creator', 'professional', 'client', 'organization');
        
        if (in_array($account_type, $valid_types, true)) {
            update_user_meta($user_id, '_vh360_account_type', $account_type);
        }
    }
    
    // Save business fields
    if (isset($_POST['vh360_business_name'])) {
        update_user_meta($user_id, '_vh360_business_name', 
            sanitize_text_field(wp_unslash($_POST['vh360_business_name'])));
    }
    
    if (isset($_POST['vh360_business_type'])) {
        update_user_meta($user_id, '_vh360_business_type', 
            sanitize_text_field(wp_unslash($_POST['vh360_business_type'])));
    }
    
    if (isset($_POST['vh360_credentials'])) {
        update_user_meta($user_id, '_vh360_credentials', 
            sanitize_text_field(wp_unslash($_POST['vh360_credentials'])));
    }
    
    if (isset($_POST['vh360_specialties'])) {
        update_user_meta($user_id, '_vh360_specialties', 
            sanitize_textarea_field(wp_unslash($_POST['vh360_specialties'])));
    }
    
    if (isset($_POST['vh360_location'])) {
        update_user_meta($user_id, '_vh360_location', 
            sanitize_text_field(wp_unslash($_POST['vh360_location'])));
    }
    
    // Checkboxes
    update_user_meta($user_id, '_vh360_telehealth', 
        isset($_POST['vh360_telehealth']) ? '1' : '0');
    
    update_user_meta($user_id, '_vh360_accepting_new_clients', 
        isset($_POST['vh360_accepting_new_clients']) ? '1' : '0');
    
    // URL fields
    if (isset($_POST['vh360_booking_url'])) {
        update_user_meta($user_id, '_vh360_booking_url', 
            esc_url_raw(wp_unslash($_POST['vh360_booking_url'])));
    }
    
    // Contact fields
    if (isset($_POST['vh360_contact_phone'])) {
        update_user_meta($user_id, '_vh360_contact_phone', 
            sanitize_text_field(wp_unslash($_POST['vh360_contact_phone'])));
    }
    
    if (isset($_POST['vh360_contact_email'])) {
        update_user_meta($user_id, '_vh360_contact_email', 
            sanitize_email(wp_unslash($_POST['vh360_contact_email'])));
    }
    
    // Textarea fields
    if (isset($_POST['vh360_pricing_info'])) {
        update_user_meta($user_id, '_vh360_pricing_info', 
            sanitize_textarea_field(wp_unslash($_POST['vh360_pricing_info'])));
    }
    
    if (isset($_POST['vh360_insurance_info'])) {
        update_user_meta($user_id, '_vh360_insurance_info', 
            sanitize_textarea_field(wp_unslash($_POST['vh360_insurance_info'])));
    }
}
add_action('personal_options_update', 'vh360_save_account_type_fields');
add_action('edit_user_profile_update', 'vh360_save_account_type_fields');
