<?php
/**
 * Template Name: Reset Password
 *
 * Custom reset password page template with branded design
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Redirect if already logged in
if (is_user_logged_in()) {
    $redirect_to = home_url('/dashboard/');
    if (!get_page_by_path('dashboard')) {
        $redirect_to = home_url('/');
    }
    wp_safe_redirect($redirect_to);
    exit;
}

// Get and validate reset key and login from URL
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$user_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Validate the reset key
$user = null;
$errors = array();

if (!empty($reset_key) && !empty($user_login)) {
    $user = check_password_reset_key($reset_key, $user_login);
    
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        if ($error_code === 'expired_key') {
            $errors[] = __('This password reset link has expired. Please request a new one.', 'videohub360-theme');
        } elseif ($error_code === 'invalid_key') {
            $errors[] = __('This password reset link is invalid. Please request a new one.', 'videohub360-theme');
        } else {
            $errors[] = __('This password reset link is not valid. Please request a new one.', 'videohub360-theme');
        }
        $user = null;
    }
} else {
    $errors[] = __('Missing password reset information. Please check your email link.', 'videohub360-theme');
}

get_header();

// Fetch customizable reset password content and appearance settings
$vh360_reset_password_headline    = get_theme_mod('vh360_reset_password_headline', __('Create New Password', 'videohub360-theme'));
$vh360_reset_password_description = get_theme_mod('vh360_reset_password_description', __('Enter a new password for your account. Make sure it\'s strong and secure.', 'videohub360-theme'));
// Gather features with icons and text; omit empty texts later
$vh360_reset_password_features = array(
    array(
        'text' => get_theme_mod('vh360_reset_password_feature_1', __('Secure Link', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_reset_password_icon_1', '🔒'),
    ),
    array(
        'text' => get_theme_mod('vh360_reset_password_feature_2', __('One-Time Use', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_reset_password_icon_2', '⏱️'),
    ),
    array(
        'text' => get_theme_mod('vh360_reset_password_feature_3', __('Instant Access', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_reset_password_icon_3', '✓'),
    ),
);
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page reset-password-page">
        
        <div class="vh360-auth-container">
            
            <!-- Left Side - Welcome Section -->
            <div class="vh360-auth-welcome">
                <div class="vh360-auth-welcome-content">
                    <?php if (has_custom_logo()) : ?>
                        <div class="vh360-auth-logo">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else : ?>
                        <h2 class="vh360-auth-site-title"><?php bloginfo('name'); ?></h2>
                    <?php endif; ?>
                    
                    <h1 class="vh360-auth-heading">
                        <?php echo esc_html($vh360_reset_password_headline); ?>
                    </h1>
                    
                    <p class="vh360-auth-description">
                        <?php echo esc_html($vh360_reset_password_description); ?>
                    </p>
                    
                    <div class="vh360-auth-features">
                        <?php foreach ($vh360_reset_password_features as $feature) :
                            $text = isset($feature['text']) ? $feature['text'] : '';
                            $icon = isset($feature['icon']) ? $feature['icon'] : '';
                            if (!empty($text)) : ?>
                            <div class="vh360-auth-feature">
                                <span class="vh360-auth-feature-icon"><?php echo esc_html($icon); ?></span>
                                <span class="vh360-auth-feature-text"><?php echo esc_html($text); ?></span>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Reset Password Form -->
            <div class="vh360-auth-form-wrapper reset-password-page">
                <div class="vh360-auth-form-content">
                    <h2 class="vh360-auth-form-title">
                        <?php esc_html_e('Set Your New Password', 'videohub360-theme'); ?>
                    </h2>
                    
                    <?php
                    // Check for success message
                    if (isset($_GET['password']) && $_GET['password'] === 'changed') {
                        echo '<div class="vh360-auth-success">';
                        esc_html_e('Your password has been reset successfully. You can now sign in with your new password.', 'videohub360-theme');
                        echo '</div>';
                        echo '<div class="vh360-auth-links" style="margin-top: 1rem;">';
                        echo '<a href="' . esc_url(vh360_get_login_page_url()) . '" class="vh360-auth-link">' . esc_html__('Sign In', 'videohub360-theme') . '</a>';
                        echo '</div>';
                    } elseif (!empty($errors)) {
                        // Display validation errors
                        foreach ($errors as $error) {
                            echo '<div class="vh360-auth-error">' . esc_html($error) . '</div>';
                        }
                        echo '<div class="vh360-auth-links" style="margin-top: 1rem;">';
                        echo '<a href="' . esc_url(vh360_get_lost_password_page_url()) . '" class="vh360-auth-link">' . esc_html__('Request New Reset Link', 'videohub360-theme') . '</a>';
                        echo '</div>';
                    } elseif (isset($_GET['error'])) {
                        // Check for form submission error
                        $error_code = sanitize_text_field($_GET['error']);
                        $error_messages = array(
                            'password_mismatch' => __('Passwords do not match. Please try again.', 'videohub360-theme'),
                            'password_too_short' => __('Password must be at least 8 characters long.', 'videohub360-theme'),
                            'nonce_failed' => __('Security check failed. Please try again.', 'videohub360-theme'),
                            'unknown' => __('An error occurred. Please try again.', 'videohub360-theme'),
                        );
                        
                        $message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : $error_messages['unknown'];
                        echo '<div class="vh360-auth-error">' . esc_html($message) . '</div>';
                    }
                    
                    // Show form only if we have a valid user
                    if ($user && !isset($_GET['password'])) :
                    ?>
                    
                    <p class="vh360-auth-hint">
                        <?php esc_html_e('Please enter your new password below. For security, use a password that is at least 8 characters long.', 'videohub360-theme'); ?>
                    </p>
                    
                    <form method="post" action="" id="vh360-resetpasswordform" class="vh360-auth-form">
                        <?php wp_nonce_field('vh360_reset_password', 'vh360_reset_password_nonce'); ?>
                        <input type="hidden" name="vh360_reset_key" value="<?php echo esc_attr($reset_key); ?>">
                        <input type="hidden" name="vh360_user_login" value="<?php echo esc_attr($user_login); ?>">
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-new-password">
                                <?php esc_html_e('New Password', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="vh360_new_password" 
                                id="vh360-new-password" 
                                class="vh360-auth-input" 
                                required 
                                autocomplete="new-password"
                            >
                            <small class="vh360-auth-hint">
                                <?php esc_html_e('Minimum 8 characters recommended', 'videohub360-theme'); ?>
                            </small>
                        </div>
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-confirm-password">
                                <?php esc_html_e('Confirm New Password', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="vh360_confirm_password" 
                                id="vh360-confirm-password" 
                                class="vh360-auth-input" 
                                required 
                                autocomplete="new-password"
                            >
                        </div>
                        
                        <button type="submit" name="vh360_reset_password_submit" class="vh360-auth-submit">
                            <?php esc_html_e('Reset Password', 'videohub360-theme'); ?>
                        </button>
                        
                        <div class="vh360-auth-links">
                            <a href="<?php echo esc_url(vh360_get_login_page_url()); ?>" class="vh360-auth-link">
                                <?php esc_html_e('Back to Sign In', 'videohub360-theme'); ?>
                            </a>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
            
        </div><!-- .vh360-auth-container -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
