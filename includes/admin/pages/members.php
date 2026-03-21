<?php
/**
 * Members Directory Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_title = __('Members Directory Settings', 'videohub360-theme');
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option('vh360_members_options', array());
$defaults = array(
    'enable_directory' => true,
    'per_page' => 24,
    'default_sort' => 'newest',
    'enable_search' => true,
    'visible_roles' => array('subscriber', 'contributor', 'author', 'editor', 'administrator'),
    'directory_audience' => 'all_members',
    'professionals_account_types' => array('professional', 'organization'),
    'professionals_require_approval' => true,
    'show_card_stats' => true,
    'show_card_follow_button' => true,
);
$options = wp_parse_args($options, $defaults);
?>

<div class="vh360-admin-settings">
    
    <form method="post" action="options.php">
        <?php settings_fields('vh360_members_settings'); ?>
        
        <!-- Members Directory Toggle -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Members Directory', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Enable or disable the members directory feature.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Directory', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[enable_directory]" value="1" <?php checked($options['enable_directory'], true); ?>>
                                <?php esc_html_e('Enable the searchable members directory page', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Display Settings -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Display Settings', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure how members are displayed in the directory.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Members Per Page', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" name="vh360_members_options[per_page]" value="<?php echo esc_attr($options['per_page']); ?>" min="6" max="100" class="small-text">
                            <p class="description"><?php esc_html_e('Number of members to display per page (6-100, recommended: 12, 24, or 48)', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Default Sorting', 'videohub360-theme'); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="newest" <?php checked($options['default_sort'], 'newest'); ?>>
                                <?php esc_html_e('Newest First', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="oldest" <?php checked($options['default_sort'], 'oldest'); ?>>
                                <?php esc_html_e('Oldest First', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="active" <?php checked($options['default_sort'], 'active'); ?>>
                                <?php esc_html_e('Most Active', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[default_sort]" value="alphabetical" <?php checked($options['default_sort'], 'alphabetical'); ?>>
                                <?php esc_html_e('Alphabetical', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Default sorting order for the members directory', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Search Functionality', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[enable_search]" value="1" <?php checked($options['enable_search'], true); ?>>
                                <?php esc_html_e('Enable search box in members directory', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show Profile Card Stats', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[show_card_stats]" value="1" <?php checked($options['show_card_stats'], true); ?>>
                                <?php esc_html_e('Show statistics on member cards (videos, followers, views)', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Show Follow Button', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[show_card_follow_button]" value="1" <?php checked($options['show_card_follow_button'], true); ?>>
                                <?php esc_html_e('Show follow button on member cards in the directory', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, logged-in users will see a follow button on member cards. Individual pages can override this setting.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Directory Audience -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Directory Audience', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Configure whether the directory shows all community members or only professionals. Individual pages can override these defaults.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Directory Audience', 'videohub360-theme'); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[directory_audience]" value="all_members" <?php checked($options['directory_audience'], 'all_members'); ?>>
                                <?php esc_html_e('All Members', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="vh360_members_options[directory_audience]" value="professionals_only" <?php checked($options['directory_audience'], 'professionals_only'); ?>>
                                <?php esc_html_e('Professionals Only', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Choose whether the directory shows all members or only approved professionals', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Professional Account Types', 'videohub360-theme'); ?></th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="vh360_members_options[professionals_account_types][]" value="professional" <?php checked(in_array('professional', $options['professionals_account_types'])); ?>>
                                <?php esc_html_e('Professional', 'videohub360-theme'); ?>
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="vh360_members_options[professionals_account_types][]" value="organization" <?php checked(in_array('organization', $options['professionals_account_types'])); ?>>
                                <?php esc_html_e('Organization', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When showing professionals only, include these account types', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Require Approval', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[professionals_require_approval]" value="1" <?php checked($options['professionals_require_approval'], true); ?>>
                                <?php esc_html_e('Only show approved professionals in professionals-only directories', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, only approved professionals will appear. Legacy accounts without status are considered approved.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Role Filter Options -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Role Filter Options', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Select which user roles should be visible in the members directory.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Visible Roles', 'videohub360-theme'); ?></th>
                        <td>
                            <?php
                            global $wp_roles;
                            $all_roles = $wp_roles->roles;
                            
                            foreach ($all_roles as $role_key => $role_data) :
                                $checked = in_array($role_key, $options['visible_roles']);
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="vh360_members_options[visible_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked($checked, true); ?>>
                                    <?php echo esc_html($role_data['name']); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Select which user roles should appear in the members directory', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Member Categories -->
        <div class="vh360-admin-card">
            <h2><?php esc_html_e('Member Categories', 'videohub360-theme'); ?></h2>
            <p><?php esc_html_e('Define categories that can be assigned to members for filtering in the directory. Categories are assigned to individual members through their profile edit screen.', 'videohub360-theme'); ?></p>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Category Filter', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_members_options[enable_category_filter]" value="1" <?php checked(!empty($options['enable_category_filter']), true); ?>>
                                <?php esc_html_e('Enable category filtering in members directory', 'videohub360-theme'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Allow users to filter members by category in the directory', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Categories', 'videohub360-theme'); ?></th>
                        <td>
                            <div id="vh360-member-categories-list">
                                <?php
                                $categories = isset($options['member_categories']) && is_array($options['member_categories'])
                                    ? $options['member_categories']
                                    : array();
                                
                                if (empty($categories)) {
                                    // Add one empty row by default
                                    $categories = array(
                                        array('slug' => '', 'label' => '', 'enabled' => true, 'sort_order' => 0)
                                    );
                                }
                                
                                foreach ($categories as $index => $category) :
                                    $slug = isset($category['slug']) ? $category['slug'] : '';
                                    $label = isset($category['label']) ? $category['label'] : '';
                                    $enabled = isset($category['enabled']) ? $category['enabled'] : true;
                                    $sort_order = isset($category['sort_order']) ? $category['sort_order'] : $index;
                                ?>
                                <div class="vh360-category-row" style="margin-bottom: 12px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                                    <div style="display: grid; grid-template-columns: 200px 1fr 80px 80px auto; gap: 12px; align-items: center;">
                                        <input 
                                            type="text" 
                                            name="vh360_members_options[member_categories][<?php echo esc_attr($index); ?>][slug]" 
                                            value="<?php echo esc_attr($slug); ?>" 
                                            placeholder="<?php esc_attr_e('Slug (e.g., therapist)', 'videohub360-theme'); ?>"
                                            class="regular-text"
                                        >
                                        <input 
                                            type="text" 
                                            name="vh360_members_options[member_categories][<?php echo esc_attr($index); ?>][label]" 
                                            value="<?php echo esc_attr($label); ?>" 
                                            placeholder="<?php esc_attr_e('Label (e.g., Therapist)', 'videohub360-theme'); ?>"
                                            class="regular-text"
                                        >
                                        <label style="display: flex; align-items: center; gap: 4px;">
                                            <input 
                                                type="checkbox" 
                                                name="vh360_members_options[member_categories][<?php echo esc_attr($index); ?>][enabled]" 
                                                value="1" 
                                                <?php checked($enabled, true); ?>
                                            >
                                            <span><?php esc_html_e('Enabled', 'videohub360-theme'); ?></span>
                                        </label>
                                        <input 
                                            type="number" 
                                            name="vh360_members_options[member_categories][<?php echo esc_attr($index); ?>][sort_order]" 
                                            value="<?php echo esc_attr($sort_order); ?>" 
                                            placeholder="<?php esc_attr_e('Order', 'videohub360-theme'); ?>"
                                            class="small-text"
                                            min="0"
                                        >
                                        <button 
                                            type="button" 
                                            class="button vh360-remove-category" 
                                            style="color: #b32d2e;"
                                        >
                                            <?php esc_html_e('Remove', 'videohub360-theme'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="vh360-add-category" class="button" style="margin-top: 12px;">
                                <?php esc_html_e('Add Category', 'videohub360-theme'); ?>
                            </button>
                            <p class="description" style="margin-top: 8px;">
                                <?php esc_html_e('Define member categories. Slug should be lowercase, alphanumeric with hyphens. Examples: therapist, doctor, lawyer, consultant, coach.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <script type="text/javascript">
        (function($) {
            let categoryIndex = <?php echo intval(count($categories)); ?>;
            
            $('#vh360-add-category').on('click', function() {
                const newRow = `
                    <div class="vh360-category-row" style="margin-bottom: 12px; padding: 12px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <div style="display: grid; grid-template-columns: 200px 1fr 80px 80px auto; gap: 12px; align-items: center;">
                            <input 
                                type="text" 
                                name="vh360_members_options[member_categories][${categoryIndex}][slug]" 
                                value="" 
                                placeholder="<?php esc_attr_e('Slug (e.g., therapist)', 'videohub360-theme'); ?>"
                                class="regular-text"
                            >
                            <input 
                                type="text" 
                                name="vh360_members_options[member_categories][${categoryIndex}][label]" 
                                value="" 
                                placeholder="<?php esc_attr_e('Label (e.g., Therapist)', 'videohub360-theme'); ?>"
                                class="regular-text"
                            >
                            <label style="display: flex; align-items: center; gap: 4px;">
                                <input 
                                    type="checkbox" 
                                    name="vh360_members_options[member_categories][${categoryIndex}][enabled]" 
                                    value="1" 
                                    checked
                                >
                                <span><?php esc_html_e('Enabled', 'videohub360-theme'); ?></span>
                            </label>
                            <input 
                                type="number" 
                                name="vh360_members_options[member_categories][${categoryIndex}][sort_order]" 
                                value="${categoryIndex}" 
                                placeholder="<?php esc_attr_e('Order', 'videohub360-theme'); ?>"
                                class="small-text"
                                min="0"
                            >
                            <button 
                                type="button" 
                                class="button vh360-remove-category" 
                                style="color: #b32d2e;"
                            >
                                <?php esc_html_e('Remove', 'videohub360-theme'); ?>
                            </button>
                        </div>
                    </div>
                `;
                
                $('#vh360-member-categories-list').append(newRow);
                categoryIndex++;
            });
            
            $(document).on('click', '.vh360-remove-category', function() {
                $(this).closest('.vh360-category-row').remove();
            });
        })(jQuery);
        </script>
        
        <?php submit_button(); ?>
        
    </form>
    
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
