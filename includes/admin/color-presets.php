<?php
/**
 * Color Presets Management
 *
 * Provides pre-made color schemes that can be applied with one click
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get available color presets
 *
 * @return array Array of color presets.
 */
function vh360_get_color_presets() {
    return array(
        'default' => array(
            'name'   => __('Default Blue', 'videohub360-theme'),
            'colors' => array(
                'vh360_primary_color'       => '#2563eb',
                'vh360_secondary_color'     => '#1e40af',
                'vh360_header_bg_color'     => '#667eea',
                'vh360_header_bg_color_end' => '#764ba2',
                'vh360_text_color'          => '#1f2937',
                'vh360_text_light_color'    => '#6b7280',
                'vh360_bg_color'            => '#ffffff',
                'vh360_bg_light_color'      => '#f9fafb',
                'vh360_border_color'        => '#e5e7eb',
                'vh360_success_color'       => '#10b981',
                'vh360_error_color'         => '#ef4444',
                'vh360_warning_color'       => '#f59e0b',
                'vh360_info_color'          => '#6366f1',
            ),
        ),
        'vibrant_red' => array(
            'name'   => __('Vibrant Red', 'videohub360-theme'),
            'colors' => array(
                'vh360_primary_color'       => '#dc2626',
                'vh360_secondary_color'     => '#b91c1c',
                'vh360_header_bg_color'     => '#dc2626',
                'vh360_header_bg_color_end' => '#991b1b',
                'vh360_text_color'          => '#1f2937',
                'vh360_text_light_color'    => '#6b7280',
                'vh360_bg_color'            => '#ffffff',
                'vh360_bg_light_color'      => '#fef2f2',
                'vh360_border_color'        => '#fee2e2',
                'vh360_success_color'       => '#10b981',
                'vh360_error_color'         => '#ef4444',
                'vh360_warning_color'       => '#f59e0b',
                'vh360_info_color'          => '#dc2626',
            ),
        ),
        'fresh_green' => array(
            'name'   => __('Fresh Green', 'videohub360-theme'),
            'colors' => array(
                'vh360_primary_color'       => '#059669',
                'vh360_secondary_color'     => '#047857',
                'vh360_header_bg_color'     => '#059669',
                'vh360_header_bg_color_end' => '#065f46',
                'vh360_text_color'          => '#1f2937',
                'vh360_text_light_color'    => '#6b7280',
                'vh360_bg_color'            => '#ffffff',
                'vh360_bg_light_color'      => '#f0fdf4',
                'vh360_border_color'        => '#d1fae5',
                'vh360_success_color'       => '#10b981',
                'vh360_error_color'         => '#ef4444',
                'vh360_warning_color'       => '#f59e0b',
                'vh360_info_color'          => '#059669',
            ),
        ),
        'royal_purple' => array(
            'name'   => __('Royal Purple', 'videohub360-theme'),
            'colors' => array(
                'vh360_primary_color'       => '#7c3aed',
                'vh360_secondary_color'     => '#6d28d9',
                'vh360_header_bg_color'     => '#7c3aed',
                'vh360_header_bg_color_end' => '#5b21b6',
                'vh360_text_color'          => '#1f2937',
                'vh360_text_light_color'    => '#6b7280',
                'vh360_bg_color'            => '#ffffff',
                'vh360_bg_light_color'      => '#faf5ff',
                'vh360_border_color'        => '#e9d5ff',
                'vh360_success_color'       => '#10b981',
                'vh360_error_color'         => '#ef4444',
                'vh360_warning_color'       => '#f59e0b',
                'vh360_info_color'          => '#7c3aed',
            ),
        ),
        'dark_mode' => array(
            'name'   => __('Dark Mode', 'videohub360-theme'),
            'colors' => array(
                'vh360_primary_color'       => '#3b82f6',
                'vh360_secondary_color'     => '#2563eb',
                'vh360_header_bg_color'     => '#1f2937',
                'vh360_header_bg_color_end' => '#111827',
                'vh360_text_color'          => '#f9fafb',
                'vh360_text_light_color'    => '#d1d5db',
                'vh360_bg_color'            => '#1f2937',
                'vh360_bg_light_color'      => '#111827',
                'vh360_border_color'        => '#374151',
                'vh360_success_color'       => '#10b981',
                'vh360_error_color'         => '#ef4444',
                'vh360_warning_color'       => '#f59e0b',
                'vh360_info_color'          => '#3b82f6',
            ),
        ),
    );
}

/**
 * Apply a color preset
 *
 * @param string $preset_id Preset identifier.
 * @return bool True on success, false on failure.
 */
function vh360_apply_color_preset($preset_id) {
    $presets = vh360_get_color_presets();
    
    if (!isset($presets[$preset_id])) {
        return false;
    }
    
    $preset = $presets[$preset_id];
    
    // Apply each color setting
    foreach ($preset['colors'] as $setting => $value) {
        set_theme_mod($setting, $value);
    }
    
    // Store the active preset
    set_theme_mod('vh360_active_preset', $preset_id);
    
    return true;
}

/**
 * Get the currently active preset
 *
 * @return string|null Active preset ID or null if none.
 */
function vh360_get_active_preset() {
    return get_theme_mod('vh360_active_preset', 'default');
}

/**
 * Handle preset application from admin
 */
function vh360_handle_preset_application() {
    // Check if this is a preset application request
    if (!isset($_POST['vh360_apply_preset'])) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['vh360_preset_nonce']) || !wp_verify_nonce($_POST['vh360_preset_nonce'], 'vh360_apply_preset')) {
        wp_die(__('Security check failed', 'videohub360-theme'));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'videohub360-theme'));
    }
    
    // Get preset ID
    $preset_id = sanitize_text_field($_POST['vh360_apply_preset']);
    
    // Apply preset
    $success = vh360_apply_color_preset($preset_id);
    
    // Redirect to appearance page with result
    $redirect_url = admin_url('admin.php?page=vh360-theme-appearance');
    $redirect_url = add_query_arg('preset_applied', $success ? 'success' : 'error', $redirect_url);
    
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_init', 'vh360_handle_preset_application');
