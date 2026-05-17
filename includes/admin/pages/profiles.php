<?php
/**
 * Profile Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Profile Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_profile_options', array());
$defaults = array(
    'enable_profiles' => true,
    'show_avatar' => true,
    'show_cover' => true,
    'show_social' => true,
    'show_stats' => true,
    'show_header_follow_button' => true,
    'social_platforms' => array('twitter', 'facebook', 'youtube', 'instagram'),
    'avatar_max_size' => 2, // MB
    'cover_max_size' => 5, // MB
);
$options = wp_parse_args($options, $defaults);
?>

<div class="vh360-admin-settings">
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_profile_settings'); ?>
        
        <!-- Profile Feature Toggle -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Profile System', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable the entire profile system.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Profiles', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[enable_profiles]" value="1" <?php checked($options['enable_profiles'], true); ?>>
                                <?php esc_html_e('Enable user profile pages and customization', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Profile Display Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Profile Display Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure what elements are displayed on user profile pages.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Profile Avatar', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[show_avatar]" value="1" <?php checked($options['show_avatar'], true); ?>>
                                <?php esc_html_e('Show user avatar on profile pages', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Cover Image', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[show_cover]" value="1" <?php checked($options['show_cover'], true); ?>>
                                <?php esc_html_e('Show cover image on profile pages', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Social Links', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[show_social]" value="1" <?php checked($options['show_social'], true); ?>>
                                <?php esc_html_e('Show social media links on profile pages', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Statistics', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[show_stats]" value="1" <?php checked($options['show_stats'], true); ?>>
                                <?php esc_html_e('Show user statistics (videos, followers, etc.)', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Follow Button', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[show_header_follow_button]" value="1" <?php checked($options['show_header_follow_button'], true); ?>>
                                <?php esc_html_e('Show follow button in profile headers (all profile types)', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, logged-in users will see a follow button on other users\' profile pages.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Social Media Platforms -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Social Media Platforms', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which social media platforms users can add to their profiles.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Available Platforms', 'videohub360-theme'); ?></th>
                        <td>
                            <?php
                            $platforms = array(
                                'twitter' => __('Twitter (X)', 'videohub360-theme'),
                                'facebook' => __('Facebook', 'videohub360-theme'),
                                'youtube' => __('YouTube', 'videohub360-theme'),
                                'instagram' => __('Instagram', 'videohub360-theme'),
                                'linkedin' => __('LinkedIn', 'videohub360-theme'),
                                'tiktok' => __('TikTok', 'videohub360-theme'),
                                'twitch' => __('Twitch', 'videohub360-theme'),
                            );
                            
                            foreach ($platforms as $key => $label) :
                                $checked = in_array($key, $options['social_platforms']);
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="vh360_profile_options[social_platforms][]" value="<?php echo esc_attr($key); ?>" <?php checked($checked, true); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Avatar Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Avatar Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure avatar upload and cropping settings.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Avatar Cropper', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_profile_options[enable_avatar_cropper]" value="1" <?php checked(isset($options['enable_avatar_cropper']) ? $options['enable_avatar_cropper'] : true, true); ?>>
                                <?php esc_html_e('Allow users to crop and reposition their avatar before uploading', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Maximum File Size', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_profile_options[avatar_max_size]" value="<?php echo esc_attr($options['avatar_max_size']); ?>" min="1" max="10" class="small-text">
                            <span><?php esc_html_e('MB', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Maximum file size for avatar uploads (1-10 MB)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Output Size', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_profile_options[avatar_output_size]" value="<?php echo esc_attr(isset($options['avatar_output_size']) ? $options['avatar_output_size'] : 300); ?>" min="100" max="1000" class="small-text">
                            <span><?php esc_html_e('pixels', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Final avatar dimensions (square). Default: 300x300 pixels.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Minimum Dimensions', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_profile_options[avatar_min_width]" value="<?php echo esc_attr(isset($options['avatar_min_width']) ? $options['avatar_min_width'] : 300); ?>" min="100" max="5000" class="small-text">
                            <span><?php esc_html_e('x', 'videohub360-theme'); ?></span>
                            <input type="number" name="vh360_profile_options[avatar_min_height]" value="<?php echo esc_attr(isset($options['avatar_min_height']) ? $options['avatar_min_height'] : 300); ?>" min="100" max="5000" class="small-text">
                            <span><?php esc_html_e('pixels', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Minimum width x height required for uploaded avatars.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Image Quality', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_profile_options[avatar_quality]" value="<?php echo esc_attr(isset($options['avatar_quality']) ? $options['avatar_quality'] : 90); ?>" min="1" max="100" class="small-text">
                            <span>%</span>
                            <p class="description"><?php esc_html_e('JPEG compression quality (1-100). Higher = better quality but larger file size. Default: 90', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed Formats', 'videohub360-theme'); ?></th>
                        <td>
                            <p><?php esc_html_e('JPG, PNG, GIF', 'videohub360-theme'); ?></p>
                            <p class="description"><?php esc_html_e('These formats are supported by default.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Cover Image Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Cover Image Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure cover image upload settings.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Maximum File Size', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_profile_options[cover_max_size]" value="<?php echo esc_attr($options['cover_max_size']); ?>" min="1" max="20" class="small-text">
                            <span><?php esc_html_e('MB', 'videohub360-theme'); ?></span>
                            <p class="description"><?php esc_html_e('Maximum file size for cover image uploads (1-20 MB)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Recommended Dimensions', 'videohub360-theme'); ?></th>
                        <td>
                            <p><?php esc_html_e('1200 x 400 pixels (3:1 ratio)', 'videohub360-theme'); ?></p>
                            <p class="description"><?php esc_html_e('Images will be automatically resized to fit.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed Formats', 'videohub360-theme'); ?></th>
                        <td>
                            <p><?php esc_html_e('JPG, PNG', 'videohub360-theme'); ?></p>
                            <p class="description"><?php esc_html_e('These formats are supported by default.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(); ?>
        
    </form>
    
    <?php
    // ----------------------------------------------------------------
    // Custom Profile Fields section (managed separately via AJAX)
    // ----------------------------------------------------------------
    if (class_exists('VH360_Profile_Fields')) :
        $manager       = VH360_Profile_Fields::get_instance();
        $builtin       = $manager->get_builtin_fields();
        $custom        = $manager->get_custom_fields();
        $all_types     = array(
            'text'     => __('Text', 'videohub360-theme'),
            'textarea' => __('Textarea', 'videohub360-theme'),
            'email'    => __('Email', 'videohub360-theme'),
            'url'      => __('URL', 'videohub360-theme'),
            'phone'    => __('Phone', 'videohub360-theme'),
            'number'   => __('Number', 'videohub360-theme'),
            'select'   => __('Select', 'videohub360-theme'),
            'checkbox' => __('Checkbox', 'videohub360-theme'),
        );
        $account_type_labels = array(
            'standard'     => __('Standard', 'videohub360-theme'),
            'client'       => __('Client', 'videohub360-theme'),
            'professional' => __('Professional', 'videohub360-theme'),
            'organization' => __('Organization', 'videohub360-theme'),
        );
    ?>
    
    <!-- Built-in Managed Fields -->
    <div class="vh360-admin-card" id="vh360-builtin-fields-section">
        <h2><?php esc_html_e('Built-in Managed Fields', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('These fields are built into the theme and cannot be deleted. You can configure their public visibility and status.', 'videohub360-theme'); ?></p>
        
        <table class="wp-list-table widefat fixed striped" id="vh360-builtin-fields-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Field', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Type', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Meta Key', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Account Types', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Show Publicly', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Status', 'videohub360-theme'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($builtin as $field_id => $field) : ?>
                <tr data-field-id="<?php echo esc_attr($field_id); ?>">
                    <td>
                        <strong><?php echo esc_html($field['label']); ?></strong>
                        <br><small style="color:#6b7280;"><?php esc_html_e('Built-in field — cannot be deleted', 'videohub360-theme'); ?></small>
                    </td>
                    <td><?php echo esc_html($all_types[$field['type']] ?? $field['type']); ?></td>
                    <td><code><?php echo esc_html($field['meta_key']); ?></code></td>
                    <td>
                        <?php
                        $at_labels = array_map(function($at) use ($account_type_labels) {
                            return $account_type_labels[$at] ?? $at;
                        }, $field['account_types']);
                        echo esc_html(implode(', ', $at_labels));
                        ?>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox"
                                class="vh360-builtin-show-public"
                                data-field-id="<?php echo esc_attr($field_id); ?>"
                                value="1"
                                <?php checked($field['show_on_public_about'], true); ?>>
                            <?php esc_html_e('Yes', 'videohub360-theme'); ?>
                        </label>
                    </td>
                    <td>
                        <select class="vh360-builtin-status" data-field-id="<?php echo esc_attr($field_id); ?>">
                            <option value="active"   <?php selected($field['status'], 'active'); ?>><?php esc_html_e('Active', 'videohub360-theme'); ?></option>
                            <option value="inactive" <?php selected($field['status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'videohub360-theme'); ?></option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top:1rem;">
            <button type="button" class="button button-primary" id="vh360-save-builtin-fields">
                <?php esc_html_e('Save Built-in Field Settings', 'videohub360-theme'); ?>
            </button>
            <span class="vh360-builtin-save-msg" style="display:none;margin-left:1rem;color:#10b981;"></span>
        </p>
    </div>
    
    <!-- Custom Profile Fields -->
    <div class="vh360-admin-card" id="vh360-custom-fields-section">
        <h2><?php esc_html_e('Custom Profile Fields', 'videohub360-theme'); ?></h2>
        <p><?php esc_html_e('Add custom profile fields that users can fill in. Custom fields are saved to meta keys prefixed with _vh360_custom_profile_.', 'videohub360-theme'); ?></p>
        
        <table class="wp-list-table widefat fixed striped" id="vh360-custom-fields-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Label / Key', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Type', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Account Types', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Edit Profile', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Show Publicly', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Status', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Actions', 'videohub360-theme'); ?></th>
                </tr>
            </thead>
            <tbody id="vh360-custom-fields-tbody">
            <?php if (empty($custom)) : ?>
                <tr id="vh360-no-custom-fields-row">
                    <td colspan="7" style="text-align:center;color:#6b7280;">
                        <?php esc_html_e('No custom fields have been added yet.', 'videohub360-theme'); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($custom as $field_id => $field) :
                    $at_labels = array_map(function($at) use ($account_type_labels) {
                        return $account_type_labels[$at] ?? $at;
                    }, (array) $field['account_types']);
                ?>
                <tr data-field-id="<?php echo esc_attr($field_id); ?>">
                    <td>
                        <strong><?php echo esc_html($field['label']); ?></strong>
                        <br><code style="font-size:0.75rem;"><?php echo esc_html($field['meta_key']); ?></code>
                    </td>
                    <td><?php echo esc_html($all_types[$field['type']] ?? $field['type']); ?></td>
                    <td><?php echo esc_html(implode(', ', $at_labels) ?: __('All', 'videohub360-theme')); ?></td>
                    <td><?php echo !empty($field['show_on_edit_profile']) ? esc_html__('Yes', 'videohub360-theme') : esc_html__('No', 'videohub360-theme'); ?></td>
                    <td><?php echo !empty($field['show_on_public_about']) ? esc_html__('Yes', 'videohub360-theme') : esc_html__('No', 'videohub360-theme'); ?></td>
                    <td><?php echo ('active' === $field['status']) ? esc_html__('Active', 'videohub360-theme') : esc_html__('Inactive', 'videohub360-theme'); ?></td>
                    <td>
                        <button type="button"
                            class="button button-small vh360-edit-custom-field"
                            data-field='<?php echo esc_attr(wp_json_encode($field)); ?>'>
                            <?php esc_html_e('Edit', 'videohub360-theme'); ?>
                        </button>
                        <button type="button"
                            class="button button-small vh360-delete-custom-field"
                            data-field-id="<?php echo esc_attr($field_id); ?>"
                            style="color:#ef4444;border-color:#ef4444;">
                            <?php esc_html_e('Delete', 'videohub360-theme'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        
        <p style="margin-top:1.25rem;">
            <button type="button" class="button button-secondary" id="vh360-toggle-add-field-form">
                <?php esc_html_e('+ Add New Field', 'videohub360-theme'); ?>
            </button>
        </p>
        
        <!-- Add / Edit Field Form -->
        <div id="vh360-field-form-wrap" style="display:none;margin-top:1.5rem;border-top:1px solid #e5e7eb;padding-top:1.5rem;">
            <h3 id="vh360-field-form-title"><?php esc_html_e('Add New Field', 'videohub360-theme'); ?></h3>
            
            <input type="hidden" id="vh360-field-id-hidden" value="">
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="vh360-field-label"><?php esc_html_e('Field Label', 'videohub360-theme'); ?> *</label></th>
                        <td>
                            <input type="text" id="vh360-field-label" class="regular-text" placeholder="<?php esc_attr_e('e.g., Home Address', 'videohub360-theme'); ?>">
                            <p class="description"><?php esc_html_e('The human-readable name shown to users.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-key"><?php esc_html_e('Field Key', 'videohub360-theme'); ?> *</label></th>
                        <td>
                            <input type="text" id="vh360-field-key" class="regular-text" placeholder="<?php esc_attr_e('e.g., home_address', 'videohub360-theme'); ?>">
                            <p class="description">
                                <?php esc_html_e('Lowercase letters, numbers, underscores only. Meta key will be:', 'videohub360-theme'); ?>
                                <code id="vh360-meta-key-preview">_vh360_custom_profile_</code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-type"><?php esc_html_e('Field Type', 'videohub360-theme'); ?></label></th>
                        <td>
                            <select id="vh360-field-type">
                                <?php foreach ($all_types as $type_val => $type_label) : ?>
                                    <option value="<?php echo esc_attr($type_val); ?>"><?php echo esc_html($type_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-placeholder"><?php esc_html_e('Placeholder', 'videohub360-theme'); ?></label></th>
                        <td>
                            <input type="text" id="vh360-field-placeholder" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-description"><?php esc_html_e('Description', 'videohub360-theme'); ?></label></th>
                        <td>
                            <input type="text" id="vh360-field-description" class="regular-text">
                            <p class="description"><?php esc_html_e('Helper text shown below the field in the edit form.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Account Types', 'videohub360-theme'); ?></th>
                        <td>
                            <?php foreach ($account_type_labels as $at_val => $at_label) : ?>
                                <label style="margin-right:1rem;">
                                    <input type="checkbox" class="vh360-field-account-type" value="<?php echo esc_attr($at_val); ?>">
                                    <?php echo esc_html($at_label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Leave all unchecked to apply to all account types.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show on Edit Profile', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vh360-field-show-edit" value="1">
                                <?php esc_html_e('Show this field in the Edit Profile tab', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show Publicly', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vh360-field-show-public" value="1">
                                <?php esc_html_e('Show this field in the public About section', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('User Visibility Toggle', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vh360-field-user-toggle" value="1">
                                <?php esc_html_e('Allow users to show/hide this field on their public profile', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-order"><?php esc_html_e('Display Order', 'videohub360-theme'); ?></label></th>
                        <td>
                            <input type="number" id="vh360-field-order" class="small-text" value="100" min="1">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="vh360-field-status"><?php esc_html_e('Status', 'videohub360-theme'); ?></label></th>
                        <td>
                            <select id="vh360-field-status">
                                <option value="active"><?php esc_html_e('Active', 'videohub360-theme'); ?></option>
                                <option value="inactive"><?php esc_html_e('Inactive', 'videohub360-theme'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Required', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vh360-field-required" value="1">
                                <?php esc_html_e('This field is required', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p>
                <button type="button" class="button button-primary" id="vh360-save-field-btn">
                    <?php esc_html_e('Save Field', 'videohub360-theme'); ?>
                </button>
                <button type="button" class="button button-secondary" id="vh360-cancel-field-btn" style="margin-left:0.5rem;">
                    <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                </button>
                <span id="vh360-field-save-msg" style="display:none;margin-left:1rem;"></span>
            </p>
        </div>
    </div>
    
    <script>
    (function($) {
        'use strict';
        
        var adminNonce = <?php echo wp_json_encode(wp_create_nonce('vh360_admin_nonce')); ?>;
        var ajaxUrl    = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        
        // ---- Field key auto-generation ----
        $('#vh360-field-label').on('input', function() {
            if (!$('#vh360-field-id-hidden').val()) {
                var key = $(this).val().toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
                $('#vh360-field-key').val(key);
                $('#vh360-meta-key-preview').text('_vh360_custom_profile_' + key);
            }
        });
        
        $('#vh360-field-key').on('input', function() {
            var key = $(this).val().toLowerCase().replace(/[^a-z0-9_]/g, '');
            $(this).val(key);
            $('#vh360-meta-key-preview').text('_vh360_custom_profile_' + key);
        });
        
        // ---- Show / hide add form ----
        $('#vh360-toggle-add-field-form').on('click', function() {
            resetFieldForm();
            $('#vh360-field-form-title').text(<?php echo wp_json_encode(__('Add New Field', 'videohub360-theme')); ?>);
            $('#vh360-field-form-wrap').slideToggle();
        });
        
        $('#vh360-cancel-field-btn').on('click', function() {
            $('#vh360-field-form-wrap').slideUp();
            resetFieldForm();
        });
        
        // ---- Edit a custom field ----
        $(document).on('click', '.vh360-edit-custom-field', function() {
            var field = $(this).data('field');
            if (!field) return;
            
            resetFieldForm();
            populateFieldForm(field);
            
            $('#vh360-field-form-title').text(<?php echo wp_json_encode(__('Edit Field', 'videohub360-theme')); ?>);
            $('#vh360-field-form-wrap').slideDown();
            $('html, body').animate({ scrollTop: $('#vh360-field-form-wrap').offset().top - 100 }, 300);
        });
        
        // ---- Delete a custom field ----
        $(document).on('click', '.vh360-delete-custom-field', function() {
            var fieldId = $(this).data('field-id');
            var $row    = $(this).closest('tr');
            
            if (!confirm(<?php echo wp_json_encode(__('Delete this field? Existing user data saved to this field will be preserved but the field will no longer appear on profile forms.', 'videohub360-theme')); ?>)) {
                return;
            }
            
            $.ajax({
                url:    ajaxUrl,
                method: 'POST',
                data: {
                    action:   'vh360_delete_custom_profile_field',
                    nonce:    adminNonce,
                    field_id: fieldId,
                },
                success: function(response) {
                    if (response.success) {
                        $row.remove();
                        if ($('#vh360-custom-fields-tbody tr').length === 0) {
                            $('#vh360-custom-fields-tbody').html(
                                '<tr id="vh360-no-custom-fields-row"><td colspan="7" style="text-align:center;color:#6b7280;">' +
                                <?php echo wp_json_encode(__('No custom fields have been added yet.', 'videohub360-theme')); ?> +
                                '</td></tr>'
                            );
                        }
                    } else {
                        alert(response.data || <?php echo wp_json_encode(__('Error deleting field.', 'videohub360-theme')); ?>);
                    }
                },
                error: function() {
                    alert(<?php echo wp_json_encode(__('Request failed. Please try again.', 'videohub360-theme')); ?>);
                }
            });
        });
        
        // ---- Save a custom field ----
        $('#vh360-save-field-btn').on('click', function() {
            var label   = $.trim($('#vh360-field-label').val());
            var fieldId = $.trim($('#vh360-field-key').val());
            
            if (!label) {
                showFieldMsg(<?php echo wp_json_encode(__('Field label is required.', 'videohub360-theme')); ?>, 'error');
                return;
            }
            if (!fieldId) {
                showFieldMsg(<?php echo wp_json_encode(__('Field key is required.', 'videohub360-theme')); ?>, 'error');
                return;
            }
            
            var accountTypes = [];
            $('.vh360-field-account-type:checked').each(function() {
                accountTypes.push($(this).val());
            });
            
            var fieldData = {
                field_id:                fieldId,
                label:                   label,
                type:                    $('#vh360-field-type').val(),
                placeholder:             $('#vh360-field-placeholder').val(),
                description:             $('#vh360-field-description').val(),
                account_types:           accountTypes,
                show_on_edit_profile:    $('#vh360-field-show-edit').is(':checked') ? '1' : '0',
                show_on_public_about:    $('#vh360-field-show-public').is(':checked') ? '1' : '0',
                allow_user_public_toggle: $('#vh360-field-user-toggle').is(':checked') ? '1' : '0',
                display_order:           $('#vh360-field-order').val(),
                status:                  $('#vh360-field-status').val(),
                required:                $('#vh360-field-required').is(':checked') ? '1' : '0',
            };
            
            $.ajax({
                url:    ajaxUrl,
                method: 'POST',
                data: {
                    action: 'vh360_save_custom_profile_field',
                    nonce:  adminNonce,
                    field:  fieldData,
                },
                success: function(response) {
                    if (response.success) {
                        showFieldMsg(response.data.message, 'success');
                        refreshCustomFieldsTable();
                        setTimeout(function() {
                            $('#vh360-field-form-wrap').slideUp();
                            resetFieldForm();
                        }, 1200);
                    } else {
                        showFieldMsg(response.data || <?php echo wp_json_encode(__('Error saving field.', 'videohub360-theme')); ?>, 'error');
                    }
                },
                error: function() {
                    showFieldMsg(<?php echo wp_json_encode(__('Request failed. Please try again.', 'videohub360-theme')); ?>, 'error');
                }
            });
        });
        
        // ---- Save built-in field settings ----
        $('#vh360-save-builtin-fields').on('click', function() {
            var settings = {};
            
            $('#vh360-builtin-fields-table tbody tr').each(function() {
                var fieldId = $(this).data('field-id');
                settings[fieldId] = {
                    show_on_public_about: $(this).find('.vh360-builtin-show-public').is(':checked') ? '1' : '0',
                    status:               $(this).find('.vh360-builtin-status').val(),
                };
            });
            
            $.ajax({
                url:    ajaxUrl,
                method: 'POST',
                data: {
                    action:   'vh360_save_builtin_field_settings',
                    nonce:    adminNonce,
                    settings: settings,
                },
                success: function(response) {
                    var $msg = $('.vh360-builtin-save-msg');
                    if (response.success) {
                        $msg.text(response.data.message).css('color', '#10b981').show();
                    } else {
                        $msg.text(response.data || <?php echo wp_json_encode(__('Error saving settings.', 'videohub360-theme')); ?>).css('color', '#ef4444').show();
                    }
                    setTimeout(function() { $msg.fadeOut(); }, 3000);
                },
                error: function() {
                    alert(<?php echo wp_json_encode(__('Request failed. Please try again.', 'videohub360-theme')); ?>);
                }
            });
        });
        
        // ---- Helpers ----
        function resetFieldForm() {
            $('#vh360-field-id-hidden').val('');
            $('#vh360-field-label').val('');
            $('#vh360-field-key').val('');
            $('#vh360-field-type').val('text');
            $('#vh360-field-placeholder').val('');
            $('#vh360-field-description').val('');
            $('.vh360-field-account-type').prop('checked', false);
            $('#vh360-field-show-edit').prop('checked', false);
            $('#vh360-field-show-public').prop('checked', false);
            $('#vh360-field-user-toggle').prop('checked', false);
            $('#vh360-field-order').val('100');
            $('#vh360-field-status').val('active');
            $('#vh360-field-required').prop('checked', false);
            $('#vh360-field-save-msg').hide();
            $('#vh360-meta-key-preview').text('_vh360_custom_profile_');
        }
        
        function populateFieldForm(field) {
            $('#vh360-field-id-hidden').val(field.field_id || '');
            $('#vh360-field-label').val(field.label || '');
            $('#vh360-field-key').val(field.field_id || '');
            $('#vh360-field-type').val(field.type || 'text');
            $('#vh360-field-placeholder').val(field.placeholder || '');
            $('#vh360-field-description').val(field.description || '');
            $('#vh360-field-show-edit').prop('checked', !!parseInt(field.show_on_edit_profile));
            $('#vh360-field-show-public').prop('checked', !!parseInt(field.show_on_public_about));
            $('#vh360-field-user-toggle').prop('checked', !!parseInt(field.allow_user_public_toggle));
            $('#vh360-field-order').val(field.display_order || 100);
            $('#vh360-field-status').val(field.status || 'active');
            $('#vh360-field-required').prop('checked', !!parseInt(field.required));
            $('#vh360-meta-key-preview').text(field.meta_key || '_vh360_custom_profile_' + (field.field_id || ''));
            
            if (field.account_types && field.account_types.length) {
                $.each(field.account_types, function(i, at) {
                    $('.vh360-field-account-type[value="' + at + '"]').prop('checked', true);
                });
            }
        }
        
        function showFieldMsg(msg, type) {
            var $el = $('#vh360-field-save-msg');
            $el.text(msg)
               .css('color', type === 'error' ? '#ef4444' : '#10b981')
               .show();
        }
        
        function refreshCustomFieldsTable() {
            // Reload page to show updated table (simpler than re-building DOM).
            location.reload();
        }
        
    })(jQuery);
    </script>
    
    <?php endif; ?>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
