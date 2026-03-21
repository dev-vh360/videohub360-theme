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
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
