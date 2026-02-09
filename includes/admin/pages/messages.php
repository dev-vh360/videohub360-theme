<?php
/**
 * Direct Messages Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$options = get_option('vh360_dm_settings', array(
    'enable_dm' => true,
    'require_mutual_follow' => false,
    'char_limit' => 1000,
    'retention_days' => 0,
));

// Get message count
global $wpdb;
$table_name = $wpdb->prefix . 'vh360_direct_messages';
$total_messages = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
$total_conversations = $wpdb->get_var("SELECT COUNT(DISTINCT CONCAT(LEAST(sender_id, recipient_id), '-', GREATEST(sender_id, recipient_id))) FROM {$table_name}");
?>

<div class="wrap vh360-admin-wrap">
    <h1><?php esc_html_e('Direct Messages Settings', 'videohub360-theme'); ?></h1>
    
    <div class="vh360-admin-header">
        <p class="description">
            <?php esc_html_e('Configure the direct messaging system for your site. Users can send private 1-on-1 messages to each other through the dashboard.', 'videohub360-theme'); ?>
        </p>
    </div>
    
    <!-- Stats Cards -->
    <div class="vh360-admin-stats">
        <div class="vh360-admin-stat-card">
            <div class="vh360-stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="vh360-stat-content">
                <h3><?php echo number_format_i18n($total_messages); ?></h3>
                <p><?php esc_html_e('Total Messages', 'videohub360-theme'); ?></p>
            </div>
        </div>
        
        <div class="vh360-admin-stat-card">
            <div class="vh360-stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="vh360-stat-content">
                <h3><?php echo number_format_i18n($total_conversations); ?></h3>
                <p><?php esc_html_e('Active Conversations', 'videohub360-theme'); ?></p>
            </div>
        </div>
        
        <div class="vh360-admin-stat-card">
            <div class="vh360-stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="vh360-stat-content">
                <h3><?php echo esc_html($options['enable_dm'] ? __('Enabled', 'videohub360-theme') : __('Disabled', 'videohub360-theme')); ?></h3>
                <p><?php esc_html_e('System Status', 'videohub360-theme'); ?></p>
            </div>
        </div>
    </div>
    
    <form method="post" action="options.php" class="vh360-admin-form">
        <?php
        settings_fields('vh360_dm_settings');
        ?>
        
        <div class="vh360-admin-sections">
            
            <!-- General Settings -->
            <div class="vh360-admin-section">
                <h2><?php esc_html_e('General Settings', 'videohub360-theme'); ?></h2>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="enable_dm">
                                    <?php esc_html_e('Enable Direct Messaging', 'videohub360-theme'); ?>
                                </label>
                            </th>
                            <td>
                                <label class="vh360-toggle">
                                    <input type="checkbox" 
                                           id="enable_dm" 
                                           name="vh360_dm_settings[enable_dm]" 
                                           value="1" 
                                           <?php checked($options['enable_dm'], true); ?>>
                                    <span class="vh360-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Enable or disable the direct messaging system site-wide.', 'videohub360-theme'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="require_mutual_follow">
                                    <?php esc_html_e('Require Mutual Following', 'videohub360-theme'); ?>
                                </label>
                            </th>
                            <td>
                                <label class="vh360-toggle">
                                    <input type="checkbox" 
                                           id="require_mutual_follow" 
                                           name="vh360_dm_settings[require_mutual_follow]" 
                                           value="1" 
                                           <?php checked($options['require_mutual_follow'], true); ?>>
                                    <span class="vh360-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('If enabled, users can only message each other if they both follow each other.', 'videohub360-theme'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="char_limit">
                                    <?php esc_html_e('Message Character Limit', 'videohub360-theme'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="char_limit" 
                                       name="vh360_dm_settings[char_limit]" 
                                       value="<?php echo esc_attr($options['char_limit']); ?>" 
                                       min="100" 
                                       max="5000" 
                                       class="small-text">
                                <p class="description">
                                    <?php esc_html_e('Maximum number of characters allowed per message (100-5000).', 'videohub360-theme'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="retention_days">
                                    <?php esc_html_e('Message Retention Period', 'videohub360-theme'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="retention_days" 
                                       name="vh360_dm_settings[retention_days]" 
                                       value="<?php echo esc_attr($options['retention_days']); ?>" 
                                       min="0" 
                                       class="small-text">
                                <span><?php esc_html_e('days', 'videohub360-theme'); ?></span>
                                <p class="description">
                                    <?php esc_html_e('Number of days to keep old messages. Set to 0 to keep messages forever. Only deleted messages are affected.', 'videohub360-theme'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Security & Performance -->
            <div class="vh360-admin-section">
                <h2><?php esc_html_e('Security & Performance', 'videohub360-theme'); ?></h2>
                
                <div class="vh360-info-box">
                    <h3><?php esc_html_e('Built-in Security Features', 'videohub360-theme'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Rate limiting: Maximum 10 messages per minute per user', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Nonce verification on all AJAX requests', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Content sanitization with wp_kses_post()', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Users can only access their own messages', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Database indexes for optimal performance', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Conversation list cached for 1 minute', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Polling only when browser tab is active', 'videohub360-theme'); ?></li>
                    </ul>
                </div>
            </div>
            
            <!-- Usage Information -->
            <div class="vh360-admin-section">
                <h2><?php esc_html_e('Usage Information', 'videohub360-theme'); ?></h2>
                
                <div class="vh360-info-box">
                    <h3><?php esc_html_e('How to Access Direct Messages', 'videohub360-theme'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Users can access messages from the dashboard via the "Messages" tab', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('A message icon appears in the header with unread message count', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Users can click "Message" button on other users\' profiles to start conversations', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Messages are checked for updates every 10 seconds', 'videohub360-theme'); ?></li>
                        <li><?php esc_html_e('Users receive notifications when they get new messages', 'videohub360-theme'); ?></li>
                    </ul>
                </div>
            </div>
            
        </div>
        
        <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
    </form>
    
</div>

<style>
.vh360-info-box {
    background: #f0f9ff;
    border-left: 4px solid #3b82f6;
    padding: 1rem 1.5rem;
    margin-top: 1rem;
}

.vh360-info-box h3 {
    margin-top: 0;
    font-size: 1rem;
    color: #1e40af;
}

.vh360-info-box ul {
    margin: 0.5rem 0 0;
    padding-left: 1.5rem;
}

.vh360-info-box li {
    margin: 0.5rem 0;
    color: #1e40af;
}
</style>
