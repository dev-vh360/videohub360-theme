<?php
/**
 * Bulletin System
 *
 * Registers bulletin custom post type and handles bulletin functionality.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register Bulletin Custom Post Type
 */
function vh360_register_bulletin_post_type() {
    $labels = array(
        'name'                  => _x('Bulletins', 'Post Type General Name', 'videohub360-theme'),
        'singular_name'         => _x('Bulletin', 'Post Type Singular Name', 'videohub360-theme'),
        'menu_name'             => __('Bulletins', 'videohub360-theme'),
        'name_admin_bar'        => __('Bulletin', 'videohub360-theme'),
        'archives'              => __('Bulletin Archives', 'videohub360-theme'),
        'attributes'            => __('Bulletin Attributes', 'videohub360-theme'),
        'parent_item_colon'     => __('Parent Bulletin:', 'videohub360-theme'),
        'all_items'             => __('All Bulletins', 'videohub360-theme'),
        'add_new_item'          => __('Add New Bulletin', 'videohub360-theme'),
        'add_new'               => __('Add New', 'videohub360-theme'),
        'new_item'              => __('New Bulletin', 'videohub360-theme'),
        'edit_item'             => __('Edit Bulletin', 'videohub360-theme'),
        'update_item'           => __('Update Bulletin', 'videohub360-theme'),
        'view_item'             => __('View Bulletin', 'videohub360-theme'),
        'view_items'            => __('View Bulletins', 'videohub360-theme'),
        'search_items'          => __('Search Bulletin', 'videohub360-theme'),
        'not_found'             => __('Not found', 'videohub360-theme'),
        'not_found_in_trash'    => __('Not found in Trash', 'videohub360-theme'),
        'featured_image'        => __('Featured Image', 'videohub360-theme'),
        'set_featured_image'    => __('Set featured image', 'videohub360-theme'),
        'remove_featured_image' => __('Remove featured image', 'videohub360-theme'),
        'use_featured_image'    => __('Use as featured image', 'videohub360-theme'),
        'insert_into_item'      => __('Insert into bulletin', 'videohub360-theme'),
        'uploaded_to_this_item' => __('Uploaded to this bulletin', 'videohub360-theme'),
        'items_list'            => __('Bulletins list', 'videohub360-theme'),
        'items_list_navigation' => __('Bulletins list navigation', 'videohub360-theme'),
        'filter_items_list'     => __('Filter bulletins list', 'videohub360-theme'),
    );

    $args = array(
        'label'                 => __('Bulletin', 'videohub360-theme'),
        'description'           => __('Site bulletins and announcements', 'videohub360-theme'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-megaphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'rewrite'               => array('slug' => 'bulletins'),
        'show_in_rest'          => true,
    );

    register_post_type('vh360_bulletin', $args);
}
add_action('init', 'vh360_register_bulletin_post_type', 0);

/**
 * Register Bulletin Category Taxonomy
 */
function vh360_register_bulletin_taxonomy() {
    $labels = array(
        'name'                       => _x('Bulletin Categories', 'Taxonomy General Name', 'videohub360-theme'),
        'singular_name'              => _x('Bulletin Category', 'Taxonomy Singular Name', 'videohub360-theme'),
        'menu_name'                  => __('Categories', 'videohub360-theme'),
        'all_items'                  => __('All Categories', 'videohub360-theme'),
        'parent_item'                => __('Parent Category', 'videohub360-theme'),
        'parent_item_colon'          => __('Parent Category:', 'videohub360-theme'),
        'new_item_name'              => __('New Category Name', 'videohub360-theme'),
        'add_new_item'               => __('Add New Category', 'videohub360-theme'),
        'edit_item'                  => __('Edit Category', 'videohub360-theme'),
        'update_item'                => __('Update Category', 'videohub360-theme'),
        'view_item'                  => __('View Category', 'videohub360-theme'),
        'separate_items_with_commas' => __('Separate categories with commas', 'videohub360-theme'),
        'add_or_remove_items'        => __('Add or remove categories', 'videohub360-theme'),
        'choose_from_most_used'      => __('Choose from the most used', 'videohub360-theme'),
        'popular_items'              => __('Popular Categories', 'videohub360-theme'),
        'search_items'               => __('Search Categories', 'videohub360-theme'),
        'not_found'                  => __('Not Found', 'videohub360-theme'),
        'no_terms'                   => __('No categories', 'videohub360-theme'),
        'items_list'                 => __('Categories list', 'videohub360-theme'),
        'items_list_navigation'      => __('Categories list navigation', 'videohub360-theme'),
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => false,
        'show_in_rest'               => true,
    );

    register_taxonomy('vh360_bulletin_category', array('vh360_bulletin'), $args);
}
add_action('init', 'vh360_register_bulletin_taxonomy', 0);

/**
 * Add bulletin capabilities to roles
 * Note: Using 'post' capability_type so standard WordPress permissions work
 * This function kept for backwards compatibility but capabilities handled by WordPress core
 */
function vh360_add_bulletin_capabilities() {
    // Capabilities now handled by 'post' capability_type
    // This ensures administrators and editors have full access using their existing permissions
}

/**
 * Add custom row actions to bulletin list table
 * Note: With 'post' capability_type, WordPress core handles row actions automatically
 * This function is kept for backwards compatibility
 */
function vh360_bulletin_row_actions($actions, $post) {
    if ($post->post_type === 'vh360_bulletin') {
        // WordPress core now handles Edit, Quick Edit, Trash actions automatically
        // Just ensure View action is present
        if (!isset($actions['view'])) {
            $actions['view'] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                get_permalink($post->ID),
                __('View', 'videohub360-theme')
            );
        }
    }
    
    return $actions;
}
add_filter('post_row_actions', 'vh360_bulletin_row_actions', 10, 2);

/**
 * Add meta box for bulletin settings
 */
function vh360_add_bulletin_meta_boxes() {
    add_meta_box(
        'vh360_bulletin_settings',
        __('Bulletin Settings', 'videohub360-theme'),
        'vh360_render_bulletin_settings_meta_box',
        'vh360_bulletin',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'vh360_add_bulletin_meta_boxes');

/**
 * Render bulletin settings meta box
 */
function vh360_render_bulletin_settings_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('vh360_save_bulletin_meta', 'vh360_bulletin_meta_nonce');
    
    // Get existing values
    $priority = get_post_meta($post->ID, '_vh360_bulletin_priority', true);
    $type = get_post_meta($post->ID, '_vh360_bulletin_type', true);
    $target = get_post_meta($post->ID, '_vh360_bulletin_target', true);
    $display_type = get_post_meta($post->ID, '_vh360_bulletin_display_type', true);
    $expiry_date = get_post_meta($post->ID, '_vh360_bulletin_expiry_date', true);
    $sticky = get_post_meta($post->ID, '_vh360_bulletin_sticky', true);
    $dismissible = get_post_meta($post->ID, '_vh360_bulletin_dismissible', true);
    
    // Set defaults
    $priority = $priority ? $priority : 'normal';
    $type = $type ? $type : 'site_wide';
    $display_type = $display_type ? $display_type : 'info';
    $dismissible = $dismissible !== '' ? $dismissible : '1';
    ?>
    
    <div class="vh360-bulletin-meta-field">
        <label for="vh360_bulletin_priority"><strong><?php esc_html_e('Priority', 'videohub360-theme'); ?></strong></label>
        <select name="vh360_bulletin_priority" id="vh360_bulletin_priority" style="width: 100%;">
            <option value="normal" <?php selected($priority, 'normal'); ?>><?php esc_html_e('Normal', 'videohub360-theme'); ?></option>
            <option value="important" <?php selected($priority, 'important'); ?>><?php esc_html_e('Important', 'videohub360-theme'); ?></option>
            <option value="urgent" <?php selected($priority, 'urgent'); ?>><?php esc_html_e('Urgent', 'videohub360-theme'); ?></option>
        </select>
    </div>
    
    <div class="vh360-bulletin-meta-field" style="margin-top: 15px;">
        <label for="vh360_bulletin_display_type"><strong><?php esc_html_e('Category', 'videohub360-theme'); ?></strong></label>
        <select name="vh360_bulletin_display_type" id="vh360_bulletin_display_type" style="width: 100%;">
            <option value="announcement" <?php selected($display_type, 'announcement'); ?>><?php esc_html_e('Announcement', 'videohub360-theme'); ?></option>
            <option value="alert" <?php selected($display_type, 'alert'); ?>><?php esc_html_e('Alert', 'videohub360-theme'); ?></option>
            <option value="update" <?php selected($display_type, 'update'); ?>><?php esc_html_e('Update', 'videohub360-theme'); ?></option>
            <option value="info" <?php selected($display_type, 'info'); ?>><?php esc_html_e('Info', 'videohub360-theme'); ?></option>
        </select>
    </div>
    
    <div class="vh360-bulletin-meta-field" style="margin-top: 15px;">
        <label for="vh360_bulletin_type"><strong><?php esc_html_e('Audience', 'videohub360-theme'); ?></strong></label>
        <select name="vh360_bulletin_type" id="vh360_bulletin_type" style="width: 100%;">
            <option value="site_wide" <?php selected($type, 'site_wide'); ?>><?php esc_html_e('Site-wide', 'videohub360-theme'); ?></option>
            <option value="role" <?php selected($type, 'role'); ?>><?php esc_html_e('Role', 'videohub360-theme'); ?></option>
            <option value="user" <?php selected($type, 'user'); ?>><?php esc_html_e('Specific User', 'videohub360-theme'); ?></option>
            <!-- Group option disabled until groups feature is implemented -->
            <!-- <option value="group" <?php selected($type, 'group'); ?>><?php esc_html_e('Group', 'videohub360-theme'); ?></option> -->
        </select>
    </div>
    
    <div class="vh360-bulletin-meta-field" id="vh360_bulletin_target_field" style="margin-top: 15px; <?php echo ($type === 'site_wide') ? 'display: none;' : ''; ?>">
        <label for="vh360_bulletin_target"><strong><?php esc_html_e('Target', 'videohub360-theme'); ?></strong></label>
        <input type="text" name="vh360_bulletin_target" id="vh360_bulletin_target" value="<?php echo esc_attr($target); ?>" style="width: 100%;" />
        <p class="description" id="target_description">
            <?php 
            if ($type === 'role') {
                esc_html_e('Enter role slug (e.g., subscriber, editor, author)', 'videohub360-theme');
            } else {
                esc_html_e('Enter numeric user ID', 'videohub360-theme');
            }
            ?>
        </p>
    </div>
    
    <div class="vh360-bulletin-meta-field" style="margin-top: 15px;">
        <label for="vh360_bulletin_expiry_date"><strong><?php esc_html_e('Expiry Date (Optional)', 'videohub360-theme'); ?></strong></label>
        <input type="datetime-local" name="vh360_bulletin_expiry_date" id="vh360_bulletin_expiry_date" 
               value="<?php echo $expiry_date ? esc_attr(wp_date('Y-m-d\TH:i', $expiry_date)) : ''; ?>" style="width: 100%;" />
        <p class="description"><?php esc_html_e('Leave empty for no expiration', 'videohub360-theme'); ?></p>
    </div>
    
    <div class="vh360-bulletin-meta-field" style="margin-top: 15px;">
        <label>
            <input type="checkbox" name="vh360_bulletin_sticky" id="vh360_bulletin_sticky" value="1" <?php checked($sticky, '1'); ?> />
            <strong><?php esc_html_e('Sticky (Always show first)', 'videohub360-theme'); ?></strong>
        </label>
    </div>
    
    <div class="vh360-bulletin-meta-field" style="margin-top: 15px;">
        <label>
            <input type="checkbox" name="vh360_bulletin_dismissible" id="vh360_bulletin_dismissible" value="1" <?php checked($dismissible, '1'); ?> />
            <strong><?php esc_html_e('Dismissible (Users can dismiss)', 'videohub360-theme'); ?></strong>
        </label>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#vh360_bulletin_type').on('change', function() {
            var type = $(this).val();
            var targetField = $('#vh360_bulletin_target_field');
            var targetDesc = $('#target_description');
            
            if (type === 'site_wide') {
                targetField.hide();
            } else {
                targetField.show();
                if (type === 'role') {
                    targetDesc.text('<?php echo esc_js(__('Enter role slug (e.g., subscriber, editor, author)', 'videohub360-theme')); ?>');
                } else if (type === 'user') {
                    targetDesc.text('<?php echo esc_js(__('Enter numeric user ID', 'videohub360-theme')); ?>');
                }
            }
        });
    });
    </script>
    
    <style>
    .vh360-bulletin-meta-field {
        margin-bottom: 10px;
    }
    .vh360-bulletin-meta-field label {
        display: block;
        margin-bottom: 5px;
    }
    </style>
    <?php
}

/**
 * Save bulletin meta data
 */
function vh360_save_bulletin_meta($post_id) {
    // Check nonce
    if (!isset($_POST['vh360_bulletin_meta_nonce']) || 
        !wp_verify_nonce($_POST['vh360_bulletin_meta_nonce'], 'vh360_save_bulletin_meta')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save priority (default to normal if not set)
    if (isset($_POST['vh360_bulletin_priority'])) {
        $priority = sanitize_text_field($_POST['vh360_bulletin_priority']);
        if (in_array($priority, array('normal', 'important', 'urgent'))) {
            update_post_meta($post_id, '_vh360_bulletin_priority', $priority);
        }
    } else {
        // Set default priority to normal if not specified
        update_post_meta($post_id, '_vh360_bulletin_priority', 'normal');
    }
    
    // Save display type / category (default to info if not set)
    if (isset($_POST['vh360_bulletin_display_type'])) {
        $display_type = sanitize_text_field($_POST['vh360_bulletin_display_type']);
        if (in_array($display_type, array('announcement', 'alert', 'update', 'info'))) {
            update_post_meta($post_id, '_vh360_bulletin_display_type', $display_type);
        }
    } else {
        // Set default display type to info if not specified
        update_post_meta($post_id, '_vh360_bulletin_display_type', 'info');
    }
    
    // Save type (default to site_wide if not set)
    if (isset($_POST['vh360_bulletin_type'])) {
        $type = sanitize_text_field($_POST['vh360_bulletin_type']);
        if (in_array($type, array('site_wide', 'group', 'role', 'user'))) {
            update_post_meta($post_id, '_vh360_bulletin_type', $type);
        }
    } else {
        // Set default type to site_wide if not specified
        update_post_meta($post_id, '_vh360_bulletin_type', 'site_wide');
    }
    
    // Save target
    if (isset($_POST['vh360_bulletin_target'])) {
        $target = sanitize_text_field($_POST['vh360_bulletin_target']);
        update_post_meta($post_id, '_vh360_bulletin_target', $target);
    }
    
    // Save expiry date
    if (isset($_POST['vh360_bulletin_expiry_date']) && !empty($_POST['vh360_bulletin_expiry_date'])) {
        $expiry_date = strtotime(sanitize_text_field($_POST['vh360_bulletin_expiry_date']));
        update_post_meta($post_id, '_vh360_bulletin_expiry_date', $expiry_date);
    } else {
        delete_post_meta($post_id, '_vh360_bulletin_expiry_date');
    }
    
    // Save sticky
    $sticky = isset($_POST['vh360_bulletin_sticky']) ? '1' : '0';
    update_post_meta($post_id, '_vh360_bulletin_sticky', $sticky);
    
    // Save dismissible
    $dismissible = isset($_POST['vh360_bulletin_dismissible']) ? '1' : '0';
    update_post_meta($post_id, '_vh360_bulletin_dismissible', $dismissible);
}
add_action('save_post_vh360_bulletin', 'vh360_save_bulletin_meta');

/**
 * Set default meta values for new bulletins
 */
function vh360_set_default_bulletin_meta($post_id, $post, $update) {
    // Only run for bulletins
    if ($post->post_type !== 'vh360_bulletin') {
        return;
    }
    
    // Only run for new bulletins (not updates)
    if ($update) {
        return;
    }
    
    // Set default values if not already set
    if (!get_post_meta($post_id, '_vh360_bulletin_priority', true)) {
        update_post_meta($post_id, '_vh360_bulletin_priority', 'normal');
    }
    
    if (!get_post_meta($post_id, '_vh360_bulletin_type', true)) {
        update_post_meta($post_id, '_vh360_bulletin_type', 'site_wide');
    }
    
    if (!get_post_meta($post_id, '_vh360_bulletin_dismissible', true)) {
        update_post_meta($post_id, '_vh360_bulletin_dismissible', '1');
    }
}
add_action('wp_insert_post', 'vh360_set_default_bulletin_meta', 10, 3);

/**
 * Auto-cleanup expired bulletins (runs on wp_scheduled_delete hook)
 * Processes in batches to prevent timeout issues
 */
function vh360_cleanup_expired_bulletins() {
    // Process a maximum of 50 bulletins per run
    $args = array(
        'post_type' => 'vh360_bulletin',
        'posts_per_page' => 50,
        'post_status' => 'publish',
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_vh360_bulletin_expiry_date',
                'value' => current_time('timestamp'),
                'compare' => '<',
                'type' => 'NUMERIC'
            )
        )
    );
    
    $expired = get_posts($args);
    foreach ($expired as $bulletin_id) {
        wp_trash_post($bulletin_id);
    }
}
add_action('wp_scheduled_delete', 'vh360_cleanup_expired_bulletins');

/**
 * Add custom columns to bulletin admin list
 */
function vh360_bulletin_admin_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['priority'] = __('Priority', 'videohub360-theme');
            $new_columns['type'] = __('Type', 'videohub360-theme');
            $new_columns['expiry'] = __('Expires', 'videohub360-theme');
        }
    }
    
    return $new_columns;
}
add_filter('manage_vh360_bulletin_posts_columns', 'vh360_bulletin_admin_columns');

/**
 * Display custom column values in bulletin admin list
 */
function vh360_bulletin_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'priority':
            $priority = get_post_meta($post_id, '_vh360_bulletin_priority', true);
            $priority = $priority ? $priority : 'normal';
            $colors = array(
                'normal' => '#3b82f6',
                'important' => '#f59e0b',
                'urgent' => '#ef4444'
            );
            $color = isset($colors[$priority]) ? $colors[$priority] : $colors['normal'];
            echo '<span style="display: inline-block; padding: 4px 8px; border-radius: 4px; background: ' . esc_attr($color) . '; color: white; font-size: 11px; font-weight: 600; text-transform: uppercase;">' . esc_html($priority) . '</span>';
            break;
            
        case 'type':
            $type = get_post_meta($post_id, '_vh360_bulletin_type', true);
            $type = $type ? $type : 'site_wide';
            echo '<span style="display: inline-block; padding: 4px 8px; border-radius: 4px; background: #e5e7eb; color: #374151; font-size: 11px; font-weight: 500;">' . esc_html(ucwords(str_replace('_', ' ', $type))) . '</span>';
            break;
            
        case 'expiry':
            $expiry = get_post_meta($post_id, '_vh360_bulletin_expiry_date', true);
            if ($expiry) {
                $is_expired = $expiry < current_time('timestamp');
                $color = $is_expired ? '#ef4444' : '#6b7280';
                echo '<span style="color: ' . esc_attr($color) . ';">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $expiry)) . '</span>';
                if ($is_expired) {
                    echo '<br><small style="color: #ef4444;">' . esc_html__('Expired', 'videohub360-theme') . '</small>';
                }
            } else {
                echo '<span style="color: #6b7280;">' . esc_html__('Never', 'videohub360-theme') . '</span>';
            }
            break;
    }
}
add_action('manage_vh360_bulletin_posts_custom_column', 'vh360_bulletin_admin_column_content', 10, 2);
