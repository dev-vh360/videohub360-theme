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

    if (isset($_GET['course_ownership_repaired'])) {
        $repaired_count      = isset($_GET['repaired']) ? absint($_GET['repaired']) : 0;
        $already_valid_count = isset($_GET['already_valid']) ? absint($_GET['already_valid']) : 0;
        $skipped_count       = isset($_GET['skipped']) ? absint($_GET['skipped']) : 0;
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: 1: repaired count, 2: already valid count, 3: skipped count */
                    esc_html__('Course ownership repair complete. %1$d courses repaired, %2$d already valid, %3$d skipped.', 'videohub360-theme'),
                    $repaired_count,
                    $already_valid_count,
                    $skipped_count
                );
                ?>
            </p>
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
