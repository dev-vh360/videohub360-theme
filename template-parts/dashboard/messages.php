<?php
/**
 * Dashboard Messages Tab
 *
 * Two-column layout for direct messaging.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if direct messaging is enabled
if (!vh360_is_dm_enabled()) {
    echo '<div class="vh360-dm-disabled">';
    echo '<p>' . esc_html__('Direct messaging is currently disabled.', 'videohub360-theme') . '</p>';
    echo '</div>';
    return;
}

$current_user_id = get_current_user_id();
$settings = vh360_get_dm_settings();
$char_limit = isset($settings['char_limit']) ? absint($settings['char_limit']) : 1000;
?>

<div class="vh360-dm-container">
    
    <!-- Left Column: Conversation List -->
    <div class="vh360-dm-sidebar">
        <div class="vh360-dm-sidebar-header">
            <h3><?php esc_html_e('Messages', 'videohub360-theme'); ?></h3>
            <button class="vh360-dm-new-conversation-btn" title="<?php esc_attr_e('New Conversation', 'videohub360-theme'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </div>
        
        <!-- Search Users (Hidden by default) -->
        <div class="vh360-dm-search-container" style="display: none;">
            <div class="vh360-dm-search-header">
                <button class="vh360-dm-back-btn" title="<?php esc_attr_e('Back', 'videohub360-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </button>
                <input type="text" class="vh360-dm-search-input" placeholder="<?php esc_attr_e('Search users...', 'videohub360-theme'); ?>">
            </div>
            <div class="vh360-dm-search-results"></div>
        </div>
        
        <!-- Conversation List -->
        <div class="vh360-dm-conversation-list">
            <div class="vh360-dm-loading">
                <div class="vh360-dm-spinner"></div>
                <p><?php esc_html_e('Loading conversations...', 'videohub360-theme'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Active Conversation -->
    <div class="vh360-dm-main">
        <div class="vh360-dm-empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <h3><?php esc_html_e('Select a conversation', 'videohub360-theme'); ?></h3>
            <p><?php esc_html_e('Choose a conversation from the left to view messages', 'videohub360-theme'); ?></p>
        </div>
        
        <div class="vh360-dm-conversation-container" style="display: none;">
            <!-- Conversation Header -->
            <div class="vh360-dm-conversation-header">
                <div class="vh360-dm-conversation-user">
                    <img class="vh360-dm-conversation-avatar" src="" alt="">
                    <div class="vh360-dm-conversation-info">
                        <h4 class="vh360-dm-conversation-name"></h4>
                    </div>
                </div>
                <button class="vh360-dm-delete-conversation-btn" title="<?php esc_attr_e('Delete Conversation', 'videohub360-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Messages Area -->
            <div class="vh360-dm-messages-area">
                <div class="vh360-dm-messages-loading">
                    <div class="vh360-dm-spinner"></div>
                </div>
                <div class="vh360-dm-messages"></div>
            </div>
            
            <!-- Compose Area -->
            <div class="vh360-dm-compose-area">
                <div class="vh360-dm-compose-form">
                    <textarea 
                        class="vh360-dm-message-input" 
                        placeholder="<?php esc_attr_e('Type a message...', 'videohub360-theme'); ?>"
                        maxlength="<?php echo esc_attr($char_limit); ?>"
                        rows="1"
                    ></textarea>
                    <div class="vh360-dm-compose-footer">
                        <div class="vh360-dm-char-counter">
                            <span class="vh360-dm-char-count">0</span> / <?php echo esc_html($char_limit); ?>
                        </div>
                        <button class="vh360-dm-send-btn" disabled>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                            <span><?php esc_html_e('Send', 'videohub360-theme'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
