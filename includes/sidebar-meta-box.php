<?php
/**
 * Per-Page Sidebar Meta Box
 *
 * Adds meta box controls for per-page/post sidebar overrides.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register sidebar meta box for pages and posts.
 */
function vh360_register_sidebar_meta_box() {
    $post_types = array('page', 'post');
    
    // Add product post type if WooCommerce is active
    if (class_exists('WooCommerce')) {
        $post_types[] = 'product';
    }
    
    /**
     * Filter post types that support sidebar meta box.
     *
     * @param array $post_types Array of post type slugs
     */
    $post_types = apply_filters('vh360_sidebar_meta_box_post_types', $post_types);
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'vh360_sidebar_settings',
            __('Sidebar Settings', 'videohub360-theme'),
            'vh360_render_sidebar_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'vh360_register_sidebar_meta_box');

/**
 * Render the sidebar meta box.
 *
 * @param WP_Post $post Current post object
 */
function vh360_render_sidebar_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('vh360_sidebar_meta_box', 'vh360_sidebar_meta_box_nonce');
    
    // Get current values
    $layout_value = get_post_meta($post->ID, '_vh360_sidebar_layout', true);
    $sidebar_value = get_post_meta($post->ID, '_vh360_sidebar_choice', true);
    
    // Set defaults if empty
    if (empty($layout_value)) {
        $layout_value = 'inherit';
    }
    if (empty($sidebar_value)) {
        $sidebar_value = 'inherit';
    }
    
    // Get selectable sidebars
    $sidebars = vh360_get_selectable_sidebars();
    
    // Get global defaults for reference
    $content_type = vh360_get_content_type_for_post($post);
    $global_layout = get_theme_mod("vh360_sidebar_layout_{$content_type}", 'right');
    $global_sidebar = get_theme_mod("vh360_sidebar_default_{$content_type}", 'sidebar-1');
    $global_sidebar_name = isset($sidebars[$global_sidebar]) ? $sidebars[$global_sidebar] : __('Primary Sidebar', 'videohub360-theme');
    
    ?>
    <div class="vh360-sidebar-meta-box">
        <p class="description">
            <?php esc_html_e('Override the global sidebar settings for this page.', 'videohub360-theme'); ?>
        </p>
        
        <!-- Sidebar Layout -->
        <p>
            <label for="vh360_sidebar_layout">
                <strong><?php esc_html_e('Sidebar Layout', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <select name="vh360_sidebar_layout" id="vh360_sidebar_layout" class="widefat">
                <option value="inherit" <?php selected($layout_value, 'inherit'); ?>>
                    <?php
                    /* translators: %s: global layout setting */
                    echo esc_html(sprintf(__('Inherit Global (%s)', 'videohub360-theme'), vh360_format_layout_label($global_layout)));
                    ?>
                </option>
                <option value="none" <?php selected($layout_value, 'none'); ?>>
                    <?php esc_html_e('No Sidebar', 'videohub360-theme'); ?>
                </option>
                <option value="left" <?php selected($layout_value, 'left'); ?>>
                    <?php esc_html_e('Left Sidebar', 'videohub360-theme'); ?>
                </option>
                <option value="right" <?php selected($layout_value, 'right'); ?>>
                    <?php esc_html_e('Right Sidebar', 'videohub360-theme'); ?>
                </option>
            </select>
        </p>
        
        <!-- Sidebar Selection -->
        <p>
            <label for="vh360_sidebar_choice">
                <strong><?php esc_html_e('Sidebar Selection', 'videohub360-theme'); ?></strong>
            </label>
        </p>
        <p>
            <select name="vh360_sidebar_choice" id="vh360_sidebar_choice" class="widefat">
                <option value="inherit" <?php selected($sidebar_value, 'inherit'); ?>>
                    <?php
                    /* translators: %s: global sidebar name */
                    echo esc_html(sprintf(__('Inherit Global (%s)', 'videohub360-theme'), $global_sidebar_name));
                    ?>
                </option>
                <?php foreach ($sidebars as $sidebar_id => $sidebar_name) : ?>
                    <option value="<?php echo esc_attr($sidebar_id); ?>" <?php selected($sidebar_value, $sidebar_id); ?>>
                        <?php echo esc_html($sidebar_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p class="description" style="margin-top: 10px;">
            <em>
                <?php
                echo esc_html__('Global settings can be changed in ', 'videohub360-theme');
                ?>
                <a href="<?php echo esc_url(admin_url('customize.php?autofocus[section]=vh360_sidebar_settings')); ?>" target="_blank">
                    <?php esc_html_e('Appearance → Customize → Layout / Sidebar', 'videohub360-theme'); ?>
                </a>
            </em>
        </p>
        
        <style>
            .vh360-sidebar-meta-box .description {
                margin-bottom: 12px;
            }
            .vh360-sidebar-meta-box p:last-child {
                margin-bottom: 0;
            }
        </style>
    </div>
    <?php
}

/**
 * Save sidebar meta box data.
 *
 * @param int $post_id Post ID
 */
function vh360_save_sidebar_meta_box($post_id) {
    // Check if nonce is set
    if (!isset($_POST['vh360_sidebar_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_sidebar_meta_box_nonce'], 'vh360_sidebar_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    $post_type = get_post_type($post_id);
    if ('page' === $post_type) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    // Save sidebar layout
    if (isset($_POST['vh360_sidebar_layout'])) {
        $layout = sanitize_text_field($_POST['vh360_sidebar_layout']);
        $valid_layouts = array('inherit', 'none', 'left', 'right');
        
        if (in_array($layout, $valid_layouts, true)) {
            update_post_meta($post_id, '_vh360_sidebar_layout', $layout);
        }
    }
    
    // Save sidebar choice
    if (isset($_POST['vh360_sidebar_choice'])) {
        $choice = sanitize_text_field($_POST['vh360_sidebar_choice']);
        
        // Validate against registered sidebars
        if ('inherit' === $choice) {
            update_post_meta($post_id, '_vh360_sidebar_choice', $choice);
        } else {
            $sidebars = vh360_get_selectable_sidebars();
            if (array_key_exists($choice, $sidebars)) {
                update_post_meta($post_id, '_vh360_sidebar_choice', $choice);
            }
        }
    }
}
add_action('save_post', 'vh360_save_sidebar_meta_box');

/**
 * Get content type for a specific post (for meta box display).
 *
 * @param WP_Post $post Post object
 * @return string Content type
 */
function vh360_get_content_type_for_post($post) {
    // Check for WooCommerce product
    if ('product' === $post->post_type) {
        return 'product';
    }
    
    if ('page' === $post->post_type) {
        return 'page';
    }
    
    if ('post' === $post->post_type) {
        return 'post';
    }
    
    return 'page';
}

/**
 * Format layout label for display.
 *
 * @param string $layout Layout value
 * @return string Formatted label
 */
function vh360_format_layout_label($layout) {
    $labels = array(
        'none'  => __('No Sidebar', 'videohub360-theme'),
        'left'  => __('Left Sidebar', 'videohub360-theme'),
        'right' => __('Right Sidebar', 'videohub360-theme'),
    );
    
    return isset($labels[$layout]) ? $labels[$layout] : $layout;
}
