<?php
/**
 * Admin Page Header
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_screen = get_current_screen();
$page_title = isset($page_title) ? $page_title : get_admin_page_title();
?>

<div class="wrap vh360-admin-wrap">
    <h1 class="vh360-admin-title">
        <span class="dashicons dashicons-admin-appearance"></span>
        <?php echo esc_html($page_title); ?>
    </h1>
    
    <?php
    // Display success messages
    if (isset($_GET['settings-updated']) && sanitize_text_field($_GET['settings-updated']) === 'true') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'videohub360-theme'); ?></p>
        </div>
        <?php
    }
    
    if (isset($_GET['cache_cleared'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Theme cache cleared successfully.', 'videohub360-theme'); ?></p>
        </div>
        <?php
    }
    
    if (isset($_GET['activities_cleared'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Old activities cleared successfully.', 'videohub360-theme'); ?></p>
        </div>
        <?php
    }
    
    if (isset($_GET['settings_reset'])) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('All settings have been reset to defaults.', 'videohub360-theme'); ?></p>
        </div>
        <?php
    }
    ?>
    
    <div class="vh360-admin-content">
