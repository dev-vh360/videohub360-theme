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

<style>
/* Direct Messages Container */
.vh360-dm-container {
    display: flex;
    height: calc(100vh - 200px);
    min-height: 500px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Sidebar - Conversation List */
.vh360-dm-sidebar {
    width: 30%;
    min-width: 280px;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

.vh360-dm-sidebar-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vh360-dm-sidebar-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.vh360-dm-new-conversation-btn {
    background: #3b82f6;
    color: #fff;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}

.vh360-dm-new-conversation-btn:hover {
    background: #2563eb;
}

/* Search Container */
.vh360-dm-search-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.vh360-dm-search-header {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 0.5rem;
}

.vh360-dm-back-btn {
    background: transparent;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.vh360-dm-back-btn:hover {
    color: #111827;
}

.vh360-dm-search-input {
    flex: 1;
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.vh360-dm-search-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.vh360-dm-search-results {
    flex: 1;
    overflow-y: auto;
}

/* Conversation List */
.vh360-dm-conversation-list {
    flex: 1;
    overflow-y: auto;
}

.vh360-dm-conversation-item {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    display: flex;
    gap: 0.75rem;
    align-items: center;
    transition: background 0.2s;
}

.vh360-dm-conversation-item:hover {
    background: #f9fafb;
}

.vh360-dm-conversation-item.active {
    background: #eff6ff;
}

.vh360-dm-conversation-item.unread {
    background: #f0f9ff;
}

.vh360-dm-conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    flex-shrink: 0;
}

.vh360-dm-conversation-details {
    flex: 1;
    min-width: 0;
}

.vh360-dm-conversation-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.vh360-dm-conversation-username {
    font-weight: 600;
    font-size: 0.875rem;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vh360-dm-conversation-time {
    font-size: 0.75rem;
    color: #6b7280;
    flex-shrink: 0;
}

.vh360-dm-conversation-preview {
    font-size: 0.875rem;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.vh360-dm-conversation-item.unread .vh360-dm-conversation-preview {
    font-weight: 600;
    color: #111827;
}

.vh360-dm-unread-badge {
    background: #3b82f6;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    flex-shrink: 0;
}

/* Main - Conversation Area */
.vh360-dm-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    position: relative;
}

.vh360-dm-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    color: #9ca3af;
    padding: 2rem;
}

.vh360-dm-empty-state svg {
    margin-bottom: 1rem;
}

.vh360-dm-empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 0.5rem;
}

.vh360-dm-empty-state p {
    font-size: 0.875rem;
    color: #9ca3af;
    margin: 0;
}

.vh360-dm-conversation-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.vh360-dm-conversation-header {
    padding: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vh360-dm-conversation-user {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.vh360-dm-conversation-header .vh360-dm-conversation-avatar {
    width: 40px;
    height: 40px;
}

.vh360-dm-conversation-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.vh360-dm-delete-conversation-btn {
    background: transparent;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.vh360-dm-delete-conversation-btn:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* Messages Area */
.vh360-dm-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    position: relative;
}

.vh360-dm-messages {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vh360-dm-message {
    display: flex;
    gap: 0.75rem;
    max-width: 70%;
}

.vh360-dm-message.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.vh360-dm-message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
}

.vh360-dm-message-content {
    background: #f3f4f6;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
}

.vh360-dm-message.sent .vh360-dm-message-content {
    background: #3b82f6;
    color: #fff;
}

.vh360-dm-message-text {
    font-size: 0.875rem;
    line-height: 1.5;
    margin: 0;
}

.vh360-dm-message-time {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.vh360-dm-message.sent .vh360-dm-message-time {
    color: rgba(255, 255, 255, 0.7);
}

/* Compose Area */
.vh360-dm-compose-area {
    border-top: 1px solid #e5e7eb;
    padding: 1rem;
}

.vh360-dm-compose-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.vh360-dm-message-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    font-family: inherit;
    resize: none;
    max-height: 120px;
}

.vh360-dm-message-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.vh360-dm-compose-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vh360-dm-char-counter {
    font-size: 0.75rem;
    color: #6b7280;
}

.vh360-dm-char-counter.warning {
    color: #f59e0b;
}

.vh360-dm-char-counter.error {
    color: #dc2626;
}

.vh360-dm-send-btn {
    background: #3b82f6;
    color: #fff;
    border: none;
    padding: 0.5rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
}

.vh360-dm-send-btn:hover:not(:disabled) {
    background: #2563eb;
}

.vh360-dm-send-btn:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

/* Loading States */
.vh360-dm-loading,
.vh360-dm-messages-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6b7280;
}

.vh360-dm-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: vh360-dm-spin 0.8s linear infinite;
}

@keyframes vh360-dm-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .vh360-dm-container {
        flex-direction: column;
        height: auto;
        min-height: 600px;
    }
    
    .vh360-dm-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .vh360-dm-main {
        display: none;
    }
    
    .vh360-dm-main.active {
        display: flex;
    }
    
    .vh360-dm-sidebar.has-active-conversation {
        display: none;
    }
    
    .vh360-dm-message {
        max-width: 85%;
    }
}

/* Disabled State */
.vh360-dm-disabled {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}
</style>
