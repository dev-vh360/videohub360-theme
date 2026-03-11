<?php
/**
 * Members Directory Meta Box
 *
 * Adds meta box controls for per-page directory audience overrides.
 * Only shown on pages using the template-members-directory.php template.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register members directory meta box for pages.
 */
function vh360_register_members_directory_meta_box() {
    add_meta_box(
        'vh360_members_directory_settings',
        __('Members Directory Settings', 'videohub360-theme'),
        'vh360_render_members_directory_meta_box',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'vh360_register_members_directory_meta_box');

/**
 * Render the members directory meta box.
 *
 * @param WP_Post $post Current post object
 */
function vh360_render_members_directory_meta_box($post) {
    // Only show for Members Directory template
    $template = get_page_template_slug($post->ID);
    if ($template !== 'template-members-directory.php') {
        echo '<p class="description">';
        esc_html_e('This meta box is only available when using the Members Directory template.', 'videohub360-theme');
        echo '</p>';
        return;
    }
    
    // Add nonce for security
    wp_nonce_field('vh360_members_directory_meta_box', 'vh360_members_directory_meta_box_nonce');
    
    // Get current values
    $audience_override = get_post_meta($post->ID, '_vh360_members_directory_audience_override', true);
    $approval_override = get_post_meta($post->ID, '_vh360_members_directory_require_approval_override', true);
    $account_types_override = get_post_meta($post->ID, '_vh360_members_directory_account_types_override', true);
    $show_card_stats_override = get_post_meta($post->ID, '_vh360_members_directory_show_card_stats_override', true);
    
    // Set defaults if empty
    if (empty($audience_override)) {
        $audience_override = 'inherit';
    }
    if ($approval_override === '') {
        $approval_override = 'inherit';
    }
    if (!is_array($account_types_override)) {
        $account_types_override = array();
    }
    if ($show_card_stats_override === '') {
        $show_card_stats_override = 'inherit';
    }
    
    // Get global settings for reference
    $global_options = get_option('vh360_members_options', array());
    $global_defaults = array(
        'directory_audience' => 'all_members',
        'professionals_account_types' => array('professional', 'organization'),
        'professionals_require_approval' => true,
        'show_card_stats' => true,
    );
    $global_options = wp_parse_args($global_options, $global_defaults);
    
    $global_audience_label = ($global_options['directory_audience'] === 'professionals_only') 
        ? __('Professionals Only', 'videohub360-theme') 
        : __('All Members', 'videohub360-theme');
    $global_approval_label = $global_options['professionals_require_approval'] 
        ? __('Yes', 'videohub360-theme') 
        : __('No', 'videohub360-theme');
    $global_card_stats_label = $global_options['show_card_stats'] 
        ? __('Show', 'videohub360-theme') 
        : __('Hide', 'videohub360-theme');
    ?>
    <div class="vh360-members-directory-meta-box">
        <p class="description">
            <?php esc_html_e('Override the global directory settings for this page. Set to "Inherit" to use global defaults.', 'videohub360-theme'); ?>
        </p>
        
        <!-- Audience Override -->
        <p>
            <label for="vh360_directory_audience_override">
                <strong><?php esc_html_e('Directory Audience', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <select name="vh360_directory_audience_override" id="vh360_directory_audience_override" class="widefat">
                <option value="inherit" <?php selected($audience_override, 'inherit'); ?>>
                    <?php
                    /* translators: %s: global audience setting */
                    echo esc_html(sprintf(__('Inherit Global (%s)', 'videohub360-theme'), $global_audience_label));
                    ?>
                </option>
                <option value="all_members" <?php selected($audience_override, 'all_members'); ?>>
                    <?php esc_html_e('All Members', 'videohub360-theme'); ?>
                </option>
                <option value="professionals_only" <?php selected($audience_override, 'professionals_only'); ?>>
                    <?php esc_html_e('Professionals Only', 'videohub360-theme'); ?>
                </option>
            </select>
        </p>
        
        <!-- Account Types Override -->
        <p>
            <label>
                <strong><?php esc_html_e('Professional Account Types', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <label style="display: block; margin-bottom: 6px;">
                <input type="checkbox" name="vh360_directory_account_types_override[]" value="professional" <?php checked(in_array('professional', $account_types_override)); ?>>
                <?php esc_html_e('Professional', 'videohub360-theme'); ?>
            </label>
            <label style="display: block; margin-bottom: 6px;">
                <input type="checkbox" name="vh360_directory_account_types_override[]" value="organization" <?php checked(in_array('organization', $account_types_override)); ?>>
                <?php esc_html_e('Organization', 'videohub360-theme'); ?>
            </label>
            <span class="description" style="display: block; margin-top: 4px; font-size: 11px;">
                <?php esc_html_e('Leave empty to inherit global settings', 'videohub360-theme'); ?>
            </span>
        </p>
        
        <!-- Approval Override -->
        <p>
            <label for="vh360_directory_approval_override">
                <strong><?php esc_html_e('Require Approval', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <select name="vh360_directory_approval_override" id="vh360_directory_approval_override" class="widefat">
                <option value="inherit" <?php selected($approval_override, 'inherit'); ?>>
                    <?php
                    /* translators: %s: global approval setting */
                    echo esc_html(sprintf(__('Inherit Global (%s)', 'videohub360-theme'), $global_approval_label));
                    ?>
                </option>
                <option value="1" <?php selected($approval_override, '1'); ?>>
                    <?php esc_html_e('Yes', 'videohub360-theme'); ?>
                </option>
                <option value="0" <?php selected($approval_override, '0'); ?>>
                    <?php esc_html_e('No', 'videohub360-theme'); ?>
                </option>
            </select>
        </p>
        
        <!-- Profile Card Stats Override -->
        <p>
            <label for="vh360_directory_show_card_stats_override">
                <strong><?php esc_html_e('Profile Card Stats', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <select name="vh360_directory_show_card_stats_override" id="vh360_directory_show_card_stats_override" class="widefat">
                <option value="inherit" <?php selected($show_card_stats_override, 'inherit'); ?>>
                    <?php
                    /* translators: %s: global card stats setting */
                    echo esc_html(sprintf(__('Inherit Global (%s)', 'videohub360-theme'), $global_card_stats_label));
                    ?>
                </option>
                <option value="1" <?php selected($show_card_stats_override, '1'); ?>>
                    <?php esc_html_e('Show', 'videohub360-theme'); ?>
                </option>
                <option value="0" <?php selected($show_card_stats_override, '0'); ?>>
                    <?php esc_html_e('Hide', 'videohub360-theme'); ?>
                </option>
            </select>
        </p>
        
        <p class="description" style="margin-top: 10px;">
            <em>
                <?php
                echo esc_html__('Global settings can be changed in ', 'videohub360-theme');
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-members')); ?>" target="_blank">
                    <?php esc_html_e('Members Directory Settings', 'videohub360-theme'); ?>
                </a>
            </em>
        </p>
        
        <style>
            .vh360-members-directory-meta-box .description {
                margin-bottom: 12px;
            }
            .vh360-members-directory-meta-box p:last-child {
                margin-bottom: 0;
            }
        </style>
    </div>
    <?php
}

/**
 * Save members directory meta box data.
 *
 * @param int $post_id Post ID
 */
function vh360_save_members_directory_meta_box($post_id) {
    // Check if nonce is set
    if (!isset($_POST['vh360_members_directory_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_members_directory_meta_box_nonce'], 'vh360_members_directory_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }
    
    // Only process for pages with Members Directory template
    $template = get_page_template_slug($post_id);
    if ($template !== 'template-members-directory.php') {
        return;
    }
    
    // Save audience override
    if (isset($_POST['vh360_directory_audience_override'])) {
        $audience = sanitize_text_field($_POST['vh360_directory_audience_override']);
        $valid_audiences = array('inherit', 'all_members', 'professionals_only');
        
        if (in_array($audience, $valid_audiences, true)) {
            update_post_meta($post_id, '_vh360_members_directory_audience_override', $audience);
        }
    }
    
    // Save approval override
    if (isset($_POST['vh360_directory_approval_override'])) {
        $approval = sanitize_text_field($_POST['vh360_directory_approval_override']);
        $valid_values = array('inherit', '0', '1');
        
        if (in_array($approval, $valid_values, true)) {
            update_post_meta($post_id, '_vh360_members_directory_require_approval_override', $approval);
        }
    }
    
    // Save show_card_stats override
    if (isset($_POST['vh360_directory_show_card_stats_override'])) {
        $show_card_stats = sanitize_text_field($_POST['vh360_directory_show_card_stats_override']);
        $valid_values = array('inherit', '0', '1');
        
        if (in_array($show_card_stats, $valid_values, true)) {
            update_post_meta($post_id, '_vh360_members_directory_show_card_stats_override', $show_card_stats);
        }
    }
    
    // Save account types override
    if (isset($_POST['vh360_directory_account_types_override']) && is_array($_POST['vh360_directory_account_types_override'])) {
        $allowed_types = array('professional', 'organization');
        $account_types = array_map('sanitize_text_field', $_POST['vh360_directory_account_types_override']);
        $account_types = array_intersect($account_types, $allowed_types);
        
        update_post_meta($post_id, '_vh360_members_directory_account_types_override', array_values($account_types));
    } else {
        // SECURITY: If no checkboxes are checked and audience is professionals_only,
        // don't save empty array - this would cause the fail-closed logic to kick in
        // Instead, just delete the override meta so it inherits from global
        $audience_value = isset($_POST['vh360_directory_audience_override']) 
            ? sanitize_text_field($_POST['vh360_directory_audience_override']) 
            : get_post_meta($post_id, '_vh360_members_directory_audience_override', true);
        
        if ($audience_value === 'professionals_only') {
            // Don't save empty array for professionals_only mode
            // This ensures global defaults are used
            delete_post_meta($post_id, '_vh360_members_directory_account_types_override');
        } else {
            // For other modes, empty array is acceptable
            update_post_meta($post_id, '_vh360_members_directory_account_types_override', array());
        }
    }
}
add_action('save_post', 'vh360_save_members_directory_meta_box');
