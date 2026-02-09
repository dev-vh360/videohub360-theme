<?php
/**
 * Template Name: Lost Password
 *
 * Custom lost password page template with branded design
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

get_header();

// Fetch customizable lost password content and appearance settings
$vh360_lost_password_headline    = get_theme_mod('vh360_lost_password_headline', __('Reset Your Password', 'videohub360-theme'));
$vh360_lost_password_description = get_theme_mod('vh360_lost_password_description', __('Enter your email address and we\'ll send you a link to reset your password.', 'videohub360-theme'));
// Gather features with icons and text; omit empty texts later
$vh360_lost_password_features = array(
    array(
        'text' => get_theme_mod('vh360_lost_password_feature_1', __('Quick Recovery', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_lost_password_icon_1', '🔐'),
    ),
    array(
        'text' => get_theme_mod('vh360_lost_password_feature_2', __('Secure Process', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_lost_password_icon_2', '✉️'),
    ),
    array(
        'text' => get_theme_mod('vh360_lost_password_feature_3', __('Easy Access', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_lost_password_icon_3', '✓'),
    ),
);
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page lost-password-page">
        
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
                        <?php echo esc_html($vh360_lost_password_headline); ?>
                    </h1>
                    
                    <p class="vh360-auth-description">
                        <?php echo esc_html($vh360_lost_password_description); ?>
                    </p>
                    
                    <div class="vh360-auth-features">
                        <?php foreach ($vh360_lost_password_features as $feature) :
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
            
            <!-- Right Side - Lost Password Form -->
            <div class="vh360-auth-form-wrapper lost-password-page">
                <div class="vh360-auth-form-content">
                    <h2 class="vh360-auth-form-title">
                        <?php esc_html_e('Forgot Your Password?', 'videohub360-theme'); ?>
                    </h2>
                    
                    <?php
                    // Check for success message
                    if (isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm') {
                        echo '<div class="vh360-auth-success">';
                        esc_html_e('Check your email for the confirmation link, then visit the login page.', 'videohub360-theme');
                        echo '</div>';
                    }
                    
                    // Check for error message
                    if (isset($_GET['error'])) {
                        $error_code = sanitize_text_field($_GET['error']);
                        $error_messages = array(
                            'empty_username' => __('Please enter your username or email address.', 'videohub360-theme'),
                            'invalid_email' => __('Please enter a valid email address.', 'videohub360-theme'),
                            'invalidcombo' => __('There is no account with that username or email address.', 'videohub360-theme'),
                            'nonce_failed' => __('Security check failed. Please try again.', 'videohub360-theme'),
                            'unknown' => __('An error occurred. Please try again.', 'videohub360-theme'),
                        );
                        
                        $message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : $error_messages['unknown'];
                        echo '<div class="vh360-auth-error">' . esc_html($message) . '</div>';
                    }
                    ?>
                    
                    <p class="vh360-auth-hint">
                        <?php esc_html_e('Enter your username or email address below and we\'ll send you a link to reset your password.', 'videohub360-theme'); ?>
                    </p>
                    
                    <form method="post" action="" id="vh360-lostpasswordform" class="vh360-auth-form">
                        <?php wp_nonce_field('vh360_lost_password', 'vh360_lost_password_nonce'); ?>
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-user-login">
                                <?php esc_html_e('Username or Email Address', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="vh360_user_login" 
                                id="vh360-user-login" 
                                class="vh360-auth-input" 
                                required
                            >
                        </div>
                        
                        <button type="submit" name="vh360_lost_password_submit" class="vh360-auth-submit">
                            <?php esc_html_e('Get New Password', 'videohub360-theme'); ?>
                        </button>
                        
                        <div class="vh360-auth-links">
                            <a href="<?php echo esc_url(vh360_get_login_page_url()); ?>" class="vh360-auth-link">
                                <?php esc_html_e('Back to Sign In', 'videohub360-theme'); ?>
                            </a>
                            <?php if (get_option('users_can_register')) : ?>
                                <span class="vh360-auth-separator">|</span>
                                <a href="<?php echo esc_url(vh360_get_register_page_url()); ?>" class="vh360-auth-link">
                                    <?php esc_html_e('Create Account', 'videohub360-theme'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
        </div><!-- .vh360-auth-container -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
