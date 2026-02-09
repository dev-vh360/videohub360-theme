<?php
/**
 * Dashboard Settings Tab
 *
 * Account, privacy, and notification settings.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$user = get_userdata($current_user_id);

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vh360_settings_nonce'])) {
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['vh360_settings_nonce'], 'vh360_settings_action')) {
        $error_message = __('Security check failed. Please try again.', 'videohub360-theme');
    } else {
        
        // Handle email change
        if (isset($_POST['user_email']) && !empty($_POST['user_email'])) {
            $new_email = sanitize_email($_POST['user_email']);
            if (!is_email($new_email)) {
                $error_message = __('Please enter a valid email address.', 'videohub360-theme');
            } elseif (email_exists($new_email) && $new_email !== $user->user_email) {
                $error_message = __('This email is already in use.', 'videohub360-theme');
            } else {
                wp_update_user(array(
                    'ID' => $current_user_id,
                    'user_email' => $new_email,
                ));
            }
        }
        
        // Handle password change
        if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $error_message = __('Passwords do not match.', 'videohub360-theme');
            } elseif (strlen($new_password) < 8) {
                $error_message = __('Password must be at least 8 characters long.', 'videohub360-theme');
            } else {
                wp_set_password($new_password, $current_user_id);
                $success_message = __('Password changed successfully. Please log in again.', 'videohub360-theme');
            }
        }
        
        // Handle privacy settings
        $privacy_settings = array(
            'profile_visibility' => isset($_POST['profile_visibility']) ? sanitize_text_field($_POST['profile_visibility']) : 'public',
            'show_email' => isset($_POST['show_email']) ? '1' : '0',
            'allow_comments' => isset($_POST['allow_comments']) ? '1' : '0',
            'allow_messages' => isset($_POST['allow_messages']) ? '1' : '0',
        );
        
        foreach ($privacy_settings as $key => $value) {
            update_user_meta($current_user_id, '_vh360_' . $key, $value);
        }
        
        // Handle notification settings
        $notification_settings = array(
            'email_new_comment' => isset($_POST['email_new_comment']) ? '1' : '0',
            'email_new_subscriber' => isset($_POST['email_new_subscriber']) ? '1' : '0',
            'email_new_like' => isset($_POST['email_new_like']) ? '1' : '0',
            'email_weekly_digest' => isset($_POST['email_weekly_digest']) ? '1' : '0',
        );
        
        foreach ($notification_settings as $key => $value) {
            update_user_meta($current_user_id, '_vh360_' . $key, $value);
        }
        
        if (empty($error_message) && empty($success_message)) {
            $success_message = __('Settings updated successfully!', 'videohub360-theme');
        }
        
        // Refresh user object
        $user = get_userdata($current_user_id);
    }
}

// Get current settings
$profile_visibility = get_user_meta($current_user_id, '_vh360_profile_visibility', true) ?: 'public';
$show_email = get_user_meta($current_user_id, '_vh360_show_email', true);
$allow_comments = get_user_meta($current_user_id, '_vh360_allow_comments', true);
$allow_messages = get_user_meta($current_user_id, '_vh360_allow_messages', true);

$email_new_comment = get_user_meta($current_user_id, '_vh360_email_new_comment', true);
$email_new_subscriber = get_user_meta($current_user_id, '_vh360_email_new_subscriber', true);
$email_new_like = get_user_meta($current_user_id, '_vh360_email_new_like', true);
$email_weekly_digest = get_user_meta($current_user_id, '_vh360_email_weekly_digest', true);
?>

<div class="vh360-dashboard-settings">
    
    <!-- Header -->
    <div class="vh360-dashboard-header">
        <h1 class="vh360-dashboard-title"><?php esc_html_e('Account Settings', 'videohub360-theme'); ?></h1>
    </div>
    
    <!-- Messages -->
    <?php if (!empty($success_message)) : ?>
        <div class="vh360-message vh360-message-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <?php echo esc_html($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)) : ?>
        <div class="vh360-message vh360-message-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Settings Form -->
    <div class="vh360-dashboard-card">
        <form method="post" class="vh360-settings-form">
            <?php wp_nonce_field('vh360_settings_action', 'vh360_settings_nonce'); ?>
            
            <!-- Account Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Account Information', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="user_email" class="vh360-form-label"><?php esc_html_e('Email Address', 'videohub360-theme'); ?></label>
                    <input type="email" name="user_email" id="user_email" class="vh360-form-input" value="<?php echo esc_attr($user->user_email); ?>">
                </div>
                
                <div class="vh360-form-group">
                    <label for="username" class="vh360-form-label"><?php esc_html_e('Username', 'videohub360-theme'); ?></label>
                    <input type="text" id="username" class="vh360-form-input" value="<?php echo esc_attr($user->user_login); ?>" disabled>
                    <span class="vh360-form-help"><?php esc_html_e('Username cannot be changed', 'videohub360-theme'); ?></span>
                </div>
            </div>
            
            <!-- Password Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Change Password', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="new_password" class="vh360-form-label"><?php esc_html_e('New Password', 'videohub360-theme'); ?></label>
                    <input type="password" name="new_password" id="new_password" class="vh360-form-input" autocomplete="new-password">
                    <span class="vh360-form-help"><?php esc_html_e('Minimum 8 characters', 'videohub360-theme'); ?></span>
                </div>
                
                <div class="vh360-form-group">
                    <label for="confirm_password" class="vh360-form-label"><?php esc_html_e('Confirm Password', 'videohub360-theme'); ?></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="vh360-form-input" autocomplete="new-password">
                </div>
            </div>
            
            <!-- Privacy Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Privacy Settings', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label for="profile_visibility" class="vh360-form-label"><?php esc_html_e('Profile Visibility', 'videohub360-theme'); ?></label>
                    <select name="profile_visibility" id="profile_visibility" class="vh360-form-input">
                        <option value="public" <?php selected($profile_visibility, 'public'); ?>><?php esc_html_e('Public', 'videohub360-theme'); ?></option>
                        <option value="members" <?php selected($profile_visibility, 'members'); ?>><?php esc_html_e('Members Only', 'videohub360-theme'); ?></option>
                        <option value="private" <?php selected($profile_visibility, 'private'); ?>><?php esc_html_e('Private', 'videohub360-theme'); ?></option>
                    </select>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="show_email" value="1" <?php checked($show_email, '1'); ?>>
                        <span><?php esc_html_e('Show email address on profile', 'videohub360-theme'); ?></span>
                    </label>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="allow_comments" value="1" <?php checked($allow_comments, '1'); ?>>
                        <span><?php esc_html_e('Allow comments on my videos', 'videohub360-theme'); ?></span>
                    </label>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="allow_messages" value="1" <?php checked($allow_messages, '1'); ?>>
                        <span><?php esc_html_e('Allow private messages', 'videohub360-theme'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- Notification Section -->
            <div class="vh360-form-section">
                <h3 class="vh360-form-section-title"><?php esc_html_e('Email Notifications', 'videohub360-theme'); ?></h3>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="email_new_comment" value="1" <?php checked($email_new_comment, '1'); ?>>
                        <span><?php esc_html_e('New comments on my videos', 'videohub360-theme'); ?></span>
                    </label>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="email_new_subscriber" value="1" <?php checked($email_new_subscriber, '1'); ?>>
                        <span><?php esc_html_e('New subscribers', 'videohub360-theme'); ?></span>
                    </label>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="email_new_like" value="1" <?php checked($email_new_like, '1'); ?>>
                        <span><?php esc_html_e('Likes on my videos', 'videohub360-theme'); ?></span>
                    </label>
                </div>
                
                <div class="vh360-form-group">
                    <label class="vh360-checkbox-label">
                        <input type="checkbox" name="email_weekly_digest" value="1" <?php checked($email_weekly_digest, '1'); ?>>
                        <span><?php esc_html_e('Weekly activity digest', 'videohub360-theme'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- In-App Notification Preferences -->
            <div class="vh360-form-section" id="vh360-notification-preferences">
                <h3 class="vh360-form-section-title"><?php esc_html_e('In-App Notification Preferences', 'videohub360-theme'); ?></h3>
                <p class="vh360-form-help"><?php esc_html_e('Control which in-app notifications you receive and how they are displayed.', 'videohub360-theme'); ?></p>
                
                <div class="vh360-notification-preferences-container">
                    <!-- Loading indicator -->
                    <div class="vh360-notification-preferences-loading">
                        <div class="vh360-spinner"></div>
                        <p><?php esc_html_e('Loading preferences...', 'videohub360-theme'); ?></p>
                    </div>
                    
                    <!-- Preferences will be loaded here via AJAX -->
                    <div class="vh360-notification-preferences-content" style="display: none;"></div>
                </div>
            </div>
            
            
            <!-- Push Notifications (Web Push via VH360 PWA & App plugin) -->
            <div class="vh360-dashboard-card vh360-push-notifications-card" id="vh360-push-settings">
                <div class="vh360-dashboard-card-header">
                    <h3 class="vh360-dashboard-card-title"><?php esc_html_e('Push Notifications', 'videohub360-theme'); ?></h3>
                </div>
                <div class="vh360-dashboard-card-body">
                    <p class="vh360-push-description">
                        <?php esc_html_e('Receive notifications on your device even when you are not actively using the site (requires browser/OS permission).', 'videohub360-theme'); ?>
                    </p>

                    <?php if ( function_exists('shortcode_exists') && shortcode_exists('vh360_push_subscribe') ) : ?>
                        <div class="vh360-push-subscribe-wrap">
                            <?php echo do_shortcode('[vh360_push_subscribe]'); ?>
                        </div>
                        <p class="vh360-push-ios-note">
                            <?php esc_html_e('On iPhone/iPad, web push works only when the site is added to your Home Screen and opened from the Home Screen icon.', 'videohub360-theme'); ?>
                        </p>
                    <?php else : ?>
                        <div class="vh360-push-unavailable">
                            <p>
                                <?php esc_html_e('Push notifications require the VideoHub360 PWA & App plugin to be installed and active.', 'videohub360-theme'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

<!-- Submit Button -->
            <div class="vh360-form-actions">
                <button type="submit" class="vh360-dashboard-btn">
                    <?php esc_html_e('Save Settings', 'videohub360-theme'); ?>
                </button>
            </div>
        </form>
    </div><!-- .vh360-dashboard-card -->
    
    <!-- Danger Zone -->
    <div class="vh360-dashboard-card vh360-danger-zone">
        <h3 class="vh360-danger-zone-title"><?php esc_html_e('Danger Zone', 'videohub360-theme'); ?></h3>
        <p class="vh360-danger-zone-text"><?php esc_html_e('Once you delete your account, there is no going back. Please be certain.', 'videohub360-theme'); ?></p>
        <button 
            class="vh360-dashboard-btn vh360-danger-btn vh360-account-delete-btn" 
            data-message="<?php esc_attr_e('Account deletion feature coming soon. Please contact support.', 'videohub360-theme'); ?>"
        >
            <?php esc_html_e('Delete Account', 'videohub360-theme'); ?>
        </button>
    </div>
    
</div><!-- .vh360-dashboard-settings -->

<style>
/* Checkbox Labels */
.vh360-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.vh360-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.vh360-checkbox-label:hover span {
    color: var(--primary-color);
}

/* Select Inputs */
select.vh360-form-input {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 12px;
    padding-right: 2.5rem;
    appearance: none;
}

/* Danger Zone */
.vh360-danger-zone {
    border: 2px solid var(--error-color);
    background: rgba(239, 68, 68, 0.05);
}

.vh360-danger-zone-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--error-color);
    margin: 0 0 0.5rem;
}

.vh360-danger-zone-text {
    color: var(--text-color);
    margin: 0 0 1rem;
    font-size: 0.875rem;
}

.vh360-danger-btn {
    background: var(--error-color);
}

.vh360-danger-btn:hover {
    background: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
</style>
