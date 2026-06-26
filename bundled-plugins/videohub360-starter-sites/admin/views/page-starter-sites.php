<?php
/**
 * Starter Sites Admin Page View
 *
 * @package VideoHub360_Starter_Sites
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$system_status = VH360_Starter_Sites_Admin::get_system_status();
?>

<div class="wrap vh360-starter-sites-wrap">
    <h1><?php esc_html_e('Starter Sites', 'videohub360-starter-sites'); ?></h1>
    
    <p class="description">
        <?php esc_html_e('Import a complete demo site with content, settings, and configurations. Choose a demo below to get started.', 'videohub360-starter-sites'); ?>
    </p>
    
    <?php if (!$system_status['requirements_met']): ?>
        <div class="notice notice-error">
            <p><strong><?php esc_html_e('System Requirements Not Met', 'videohub360-starter-sites'); ?></strong></p>
            <ul>
                <?php foreach ($system_status['requirement_errors'] as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><?php esc_html_e('Please fix these issues before importing demos.', 'videohub360-starter-sites'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($is_importing): ?>
        <div class="notice notice-info">
            <p><strong><?php esc_html_e('Import In Progress', 'videohub360-starter-sites'); ?></strong></p>
            <p><?php esc_html_e('A demo import is currently in progress. Please wait for it to complete.', 'videohub360-starter-sites'); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- System Status -->
    <div class="vh360-ss-system-status">
        <h2><?php esc_html_e('System Status', 'videohub360-starter-sites'); ?></h2>
        <div class="vh360-ss-status-grid">
            <div class="vh360-ss-status-item">
                <span class="label"><?php esc_html_e('PHP Version:', 'videohub360-starter-sites'); ?></span>
                <span class="value"><?php echo esc_html($system_status['php_version']); ?></span>
            </div>
            <div class="vh360-ss-status-item">
                <span class="label"><?php esc_html_e('WordPress Version:', 'videohub360-starter-sites'); ?></span>
                <span class="value"><?php echo esc_html($system_status['wp_version']); ?></span>
            </div>
            <div class="vh360-ss-status-item">
                <span class="label"><?php esc_html_e('Theme Version:', 'videohub360-starter-sites'); ?></span>
                <span class="value"><?php echo esc_html($system_status['theme_version']); ?></span>
            </div>
            <div class="vh360-ss-status-item">
                <span class="label"><?php esc_html_e('Memory Limit:', 'videohub360-starter-sites'); ?></span>
                <span class="value"><?php echo esc_html($system_status['memory_limit']); ?></span>
            </div>
            <div class="vh360-ss-status-item">
                <span class="label"><?php esc_html_e('Elementor:', 'videohub360-starter-sites'); ?></span>
                <span class="value <?php echo $system_status['elementor_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $system_status['elementor_active'] ? esc_html($system_status['elementor_version']) : esc_html__('Not Active', 'videohub360-starter-sites'); ?>
                </span>
            </div>
        </div>
        <button type="button" id="vh360-ss-refresh-cache" class="button">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e('Refresh Demo List', 'videohub360-starter-sites'); ?>
        </button>
    </div>
    
    <!-- Demo Grid -->
    <div class="vh360-ss-demos-section">
        <h2><?php esc_html_e('Available Demos', 'videohub360-starter-sites'); ?></h2>
        
        <div id="vh360-ss-demos-loading" class="vh360-ss-loading" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php esc_html_e('Loading demos...', 'videohub360-starter-sites'); ?></p>
        </div>
        
        <div id="vh360-ss-demos-error" class="notice notice-error" style="display: none;">
            <p></p>
        </div>
        
        <div id="vh360-ss-demos-grid" class="vh360-ss-demos-grid">
            <!-- Demo cards will be inserted here by JavaScript -->
        </div>
    </div>
    
    <!-- Import Progress Panel -->
    <div id="vh360-ss-import-progress" class="vh360-ss-modal" style="display: none;">
        <div class="vh360-ss-modal-content">
            <?php include VH360_STARTER_SITES_ADMIN . 'views/panel-import-progress.php'; ?>
        </div>
    </div>
    
    <!-- Import Complete Panel -->
    <div id="vh360-ss-import-complete" class="vh360-ss-modal" style="display: none;">
        <div class="vh360-ss-modal-content">
            <?php include VH360_STARTER_SITES_ADMIN . 'views/panel-import-complete.php'; ?>
        </div>
    </div>
    
    <!-- Last Import Log -->
    <?php if ($last_log && !$is_importing): ?>
        <div class="vh360-ss-last-import">
            <h2><?php esc_html_e('Last Import', 'videohub360-starter-sites'); ?></h2>
            <div class="vh360-ss-log-summary">
                <div class="vh360-ss-log-info">
                    <p>
                        <strong><?php esc_html_e('Demo:', 'videohub360-starter-sites'); ?></strong>
                        <?php echo esc_html($last_log['demo_id']); ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e('Status:', 'videohub360-starter-sites'); ?></strong>
                        <span class="status-badge status-<?php echo $last_log['success'] ? 'success' : 'error'; ?>">
                            <?php echo $last_log['success'] ? esc_html__('Success', 'videohub360-starter-sites') : esc_html__('Failed', 'videohub360-starter-sites'); ?>
                        </span>
                    </p>
                    <p>
                        <strong><?php esc_html_e('Duration:', 'videohub360-starter-sites'); ?></strong>
                        <?php echo esc_html($last_log['duration']); ?> <?php esc_html_e('seconds', 'videohub360-starter-sites'); ?>
                    </p>
                    <?php if ($last_log['error_count'] > 0): ?>
                        <p>
                            <strong><?php esc_html_e('Errors:', 'videohub360-starter-sites'); ?></strong>
                            <?php echo esc_html($last_log['error_count']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <button type="button" id="vh360-ss-view-log" class="button">
                    <?php esc_html_e('View Full Log', 'videohub360-starter-sites'); ?>
                </button>
            </div>
            
            <div id="vh360-ss-log-details" class="vh360-ss-log-details" style="display: none;">
                <h3><?php esc_html_e('Import Log', 'videohub360-starter-sites'); ?></h3>
                <div class="vh360-ss-log-entries">
                    <?php if (!empty($last_log['entries'])): ?>
                        <?php foreach ($last_log['entries'] as $entry): ?>
                            <div class="vh360-ss-log-entry log-<?php echo esc_attr($entry['level']); ?>">
                                <span class="log-timestamp">[<?php echo esc_html($entry['timestamp']); ?>]</span>
                                <span class="log-level">[<?php echo esc_html(strtoupper($entry['level'])); ?>]</span>
                                <span class="log-message"><?php echo esc_html($entry['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Demo Card Template (JavaScript will use this) -->
<script type="text/template" id="vh360-ss-demo-card-template">
    <?php include VH360_STARTER_SITES_ADMIN . 'views/card-demo.php'; ?>
</script>
