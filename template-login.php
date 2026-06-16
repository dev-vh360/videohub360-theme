<?php
/**
 * Template Name: Login
 *
 * Custom login page template with branded design
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Redirect if already logged in
if (is_user_logged_in()) {
    $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
    if (empty($redirect_to) || (function_exists('vh360_is_invalid_login_redirect_target') && vh360_is_invalid_login_redirect_target($redirect_to, 'post_login', wp_get_current_user()))) {
        $redirect_to = vh360_get_login_redirect_url(get_current_user_id());
    }
    wp_safe_redirect(wp_validate_redirect($redirect_to, home_url('/')));
    exit;
}

get_header();

// Fetch customizable login content and appearance settings
$vh360_login_headline    = get_theme_mod('vh360_login_headline', __('Welcome Back!', 'videohub360-theme'));
$vh360_login_description = get_theme_mod('vh360_login_description', __('Sign in to continue to your video platform and connect with your community.', 'videohub360-theme'));
// Gather features with icons and text; omit empty texts later
$vh360_login_features = array(
    array(
        'text' => get_theme_mod('vh360_login_feature_1', __('Watch Videos', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_login_icon_1', '📹'),
    ),
    array(
        'text' => get_theme_mod('vh360_login_feature_2', __('Engage & Comment', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_login_icon_2', '💬'),
    ),
    array(
        'text' => get_theme_mod('vh360_login_feature_3', __('Connect with Others', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_login_icon_3', '🌐'),
    ),
);
// Toggle visibility for form elements
$vh360_login_show_remember        = (bool) get_theme_mod('vh360_login_show_remember', 1);
$vh360_login_show_forgot_password = (bool) get_theme_mod('vh360_login_show_forgot_password', 1);
$vh360_login_show_create_account  = (bool) get_theme_mod('vh360_login_show_create_account', 1);
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page login-page">
        
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
                        <?php echo esc_html($vh360_login_headline); ?>
                    </h1>
                    
                    <p class="vh360-auth-description">
                        <?php echo esc_html($vh360_login_description); ?>
                    </p>
                    
                    <div class="vh360-auth-features">
                        <?php foreach ($vh360_login_features as $feature) :
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
            
            <!-- Right Side - Login Form -->
            <div class="vh360-auth-form-wrapper login-page">
                <div class="vh360-auth-form-content">
                    <h2 class="vh360-auth-form-title">
                        <?php esc_html_e('Sign In', 'videohub360-theme'); ?>
                    </h2>
                    
                    <?php
                    // Check for login errors and get preserved username
                    $login_error = isset($_GET['login']) ? $_GET['login'] : '';
                    $username_value = isset($_GET['username']) ? esc_attr($_GET['username']) : '';
                    
                    // Display appropriate error message based on error type
                    if ($login_error === 'failed') {
                        echo '<div class="vh360-auth-error">';
                        esc_html_e('Invalid username or password. Please try again.', 'videohub360-theme');
                        echo '</div>';
                    } elseif ($login_error === 'empty_fields') {
                        echo '<div class="vh360-auth-error">';
                        esc_html_e('Please enter both username and password.', 'videohub360-theme');
                        echo '</div>';
                    } elseif ($login_error === 'nonce_failed') {
                        echo '<div class="vh360-auth-error">';
                        esc_html_e('Security check failed. Please try again.', 'videohub360-theme');
                        echo '</div>';
                    }
                    
                    // Check for logout message
                    if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
                        echo '<div class="vh360-auth-success">';
                        esc_html_e('You have been logged out successfully.', 'videohub360-theme');
                        echo '</div>';
                    }
                    
                    // Get redirect URL (preserve from query string if present for gated pages)
                    $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
                    if (function_exists('vh360_is_invalid_login_redirect_target') && vh360_is_invalid_login_redirect_target($redirect_to, 'preserve')) {
                        $redirect_to = '';
                    }
                    $redirect_to = $redirect_to ? wp_validate_redirect($redirect_to, '') : '';
                    // Don't set a default here - redirect_to parameter takes priority over
                    // Customizer settings to ensure gated page functionality works correctly
                    ?>
                    
                    <!-- Custom Login Form -->
                    <form name="vh360loginform" id="vh360-loginform" action="<?php echo esc_url(get_permalink()); ?>" method="post">
                        <?php wp_nonce_field('vh360_custom_login', 'vh360_login_nonce'); ?>
                        <?php if (! empty($redirect_to)) : ?>
                            <input type="hidden" name="vh360_redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
                        <?php endif; ?>
                        
                        <p class="login-username">
                            <label for="vh360-user-login"><?php esc_html_e('Username or Email', 'videohub360-theme'); ?></label>
                            <input type="text" name="vh360_username" id="vh360-user-login" class="input" value="<?php echo $username_value; ?>" size="20" autocapitalize="off" required />
                        </p>
                        
                        <p class="login-password">
                            <label for="vh360-user-pass"><?php esc_html_e('Password', 'videohub360-theme'); ?></label>
                            <input type="password" name="vh360_password" id="vh360-user-pass" class="input" value="" size="20" required />
                        </p>
                        
                        <?php if ($vh360_login_show_remember) : ?>
                        <p class="login-remember">
                            <label>
                                <input name="vh360_remember" type="checkbox" id="vh360-rememberme" value="1" checked="checked" />
                                <?php esc_html_e('Remember Me', 'videohub360-theme'); ?>
                            </label>
                        </p>
                        <?php endif; ?>
                        
                        <p class="login-submit">
                            <input type="submit" name="vh360_login_submit" id="vh360-wp-submit" class="button button-primary" value="<?php esc_attr_e('Sign In', 'videohub360-theme'); ?>" />
                        </p>
                    </form>
                    
                    <div class="vh360-auth-links">
                        <?php
                        $links_output = array();
                        if ($vh360_login_show_forgot_password) {
                            $links_output[] = '<a href="' . esc_url(vh360_get_lost_password_page_url()) . '" class="vh360-auth-link">' . esc_html__('Forgot Password?', 'videohub360-theme') . '</a>';
                        }
                        if ($vh360_login_show_create_account && get_option('users_can_register')) {
                            $links_output[] = '<a href="' . esc_url(vh360_get_register_page_url()) . '" class="vh360-auth-link">' . esc_html__('Create Account', 'videohub360-theme') . '</a>';
                        }
                        echo implode(' <span class="vh360-auth-separator">|</span> ', $links_output);
                        ?>
                    </div>
                </div>
            </div>
            
        </div><!-- .vh360-auth-container -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
