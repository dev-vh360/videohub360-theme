<?php
/**
 * Template Name: Professional Registration
 *
 * Custom registration page for Professional accounts
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

// Check if registration is enabled
if (!get_option('users_can_register')) {
    wp_safe_redirect(home_url('/'));
    exit;
}

// Handle registration errors
$registration_error = null;
if (isset($_GET['registration']) && $_GET['registration'] === 'failed') {
    $error_code = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : 'unknown';
    $registration_error = $error_code;
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page register-page account-type-register-page professional-register-page">
        
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
                        <?php echo esc_html(get_theme_mod('vh360_professional_register_headline', __('Professional Registration', 'videohub360-theme'))); ?>
                    </h1>
                    
                    <p class="vh360-auth-description">
                        <?php echo esc_html(get_theme_mod('vh360_professional_register_description', __('Create your professional account and start showcasing your services.', 'videohub360-theme'))); ?>
                    </p>
                    
                    <div class="vh360-auth-benefits">
                        <h3 class="vh360-auth-benefits-title">
                            <?php echo esc_html(get_theme_mod('vh360_professional_register_benefits_heading', __('Professional Benefits:', 'videohub360-theme'))); ?>
                        </h3>
                        <ul class="vh360-auth-benefits-list">
                            <li>
                                <span class="vh360-auth-benefit-icon">✓</span>
                                <?php echo esc_html(get_theme_mod('vh360_professional_register_benefit_1', __('Business profile with services showcase', 'videohub360-theme'))); ?>
                            </li>
                            <li>
                                <span class="vh360-auth-benefit-icon">✓</span>
                                <?php echo esc_html(get_theme_mod('vh360_professional_register_benefit_2', __('Display credentials and specialties', 'videohub360-theme'))); ?>
                            </li>
                            <li>
                                <span class="vh360-auth-benefit-icon">✓</span>
                                <?php echo esc_html(get_theme_mod('vh360_professional_register_benefit_3', __('Contact information and booking options', 'videohub360-theme'))); ?>
                            </li>
                            <li>
                                <span class="vh360-auth-benefit-icon">✓</span>
                                <?php echo esc_html(get_theme_mod('vh360_professional_register_benefit_4', __('Share content and connect with clients', 'videohub360-theme'))); ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="vh360-auth-form-wrapper register-page">
                <div class="vh360-auth-form-content">
                    <h2 class="vh360-auth-form-title">
                        <?php echo esc_html(get_theme_mod('vh360_professional_register_button', __('Create Professional Account', 'videohub360-theme'))); ?>
                    </h2>
                    
                    <?php
                    // Display registration errors
                    if ($registration_error) {
                        $message = function_exists('vh360_get_registration_error_message')
                            ? vh360_get_registration_error_message($registration_error)
                            : (0 === strpos($registration_error, 'invite_')
                                ? __('This invite code is not valid. Please check your invite link or contact the person who invited you.', 'videohub360-theme')
                                : __('Registration failed. Please try again.', 'videohub360-theme'));
                        echo '<div class="vh360-auth-error">' . esc_html($message) . '</div>';
                    }
                    
                    // Preserve form values on error
                    $submitted_first_name = '';
                    $submitted_last_name  = '';
                    $submitted_username   = '';
                    $submitted_email      = '';
                    if ($registration_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $submitted_first_name = isset($_POST['vh360_first_name']) ? sanitize_text_field(wp_unslash($_POST['vh360_first_name'])) : '';
                        $submitted_last_name  = isset($_POST['vh360_last_name']) ? sanitize_text_field(wp_unslash($_POST['vh360_last_name'])) : '';
                        $submitted_username   = isset($_POST['vh360_username']) ? sanitize_user(wp_unslash($_POST['vh360_username'])) : '';
                        $submitted_email      = isset($_POST['vh360_email']) ? sanitize_email(wp_unslash($_POST['vh360_email'])) : '';
                    }
                    ?>
                    
                    <form method="post" action="" id="vh360-registerform" class="vh360-auth-form">
                        <?php wp_nonce_field('vh360_account_type_register', 'vh360_account_type_register_nonce'); ?>
                        <?php $vh360_bridge_args = function_exists('vh360_get_recurring_membership_bridge_args') ? vh360_get_recurring_membership_bridge_args() : array(); ?>
                        <?php if (!empty($vh360_bridge_args['vh360_plan'])) : ?><input type="hidden" name="vh360_plan" value="<?php echo esc_attr($vh360_bridge_args['vh360_plan']); ?>" /><?php endif; ?>
                        <?php if (!empty($vh360_bridge_args['redirect_to'])) : ?><input type="hidden" name="vh360_redirect_to" value="<?php echo esc_attr($vh360_bridge_args['redirect_to']); ?>" /><?php endif; ?>
                        
                        <!-- Hidden field to set account type -->
                        <input type="hidden" name="vh360_account_type" value="professional">
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-first-name">
                                <?php esc_html_e('First Name', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="vh360_first_name" 
                                id="vh360-first-name" 
                                class="vh360-auth-input" 
                                required 
                                value="<?php echo esc_attr($submitted_first_name); ?>"
                            >
                        </div>

                        <div class="vh360-auth-field">
                            <label for="vh360-last-name">
                                <?php esc_html_e('Last Name', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="vh360_last_name" 
                                id="vh360-last-name" 
                                class="vh360-auth-input" 
                                required 
                                value="<?php echo esc_attr($submitted_last_name); ?>"
                            >
                        </div>

                        <div class="vh360-auth-field">
                            <label for="vh360-username">
                                <?php esc_html_e('Username', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="vh360_username" 
                                id="vh360-username" 
                                class="vh360-auth-input" 
                                required 
                                value="<?php echo esc_attr($submitted_username); ?>"
                            >
                        </div>
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-email">
                                <?php esc_html_e('Email Address', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="vh360_email" 
                                id="vh360-email" 
                                class="vh360-auth-input" 
                                required 
                                value="<?php echo esc_attr($submitted_email); ?>"
                            >
                        </div>
                        
                        <div class="vh360-auth-field">
                            <label for="vh360-password">
                                <?php esc_html_e('Password', 'videohub360-theme'); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="password" 
                                name="vh360_password" 
                                id="vh360-password" 
                                class="vh360-auth-input" 
                                required
                            >
                            <small class="vh360-auth-hint">
                                <?php esc_html_e('Minimum 8 characters recommended', 'videohub360-theme'); ?>
                            </small>
                        </div>
                        
                        <?php get_template_part('template-parts/auth/invite-code-field', null, array('context' => 'professional')); ?>

                        <div class="vh360-auth-field vh360-auth-checkbox">
                            <label for="vh360-terms">
                                <input 
                                    type="checkbox" 
                                    name="vh360_terms" 
                                    id="vh360-terms" 
                                    required
                                >
                                <?php
                                printf(
                                    /* translators: %s: Privacy Policy link */
                                    esc_html__('I agree to the %s', 'videohub360-theme'),
                                    '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">' . esc_html__('Terms of Service and Privacy Policy', 'videohub360-theme') . '</a>'
                                );
                                ?>
                            </label>
                        </div>

                        <button type="submit" name="vh360_account_type_register_submit" class="vh360-auth-submit">
                            <?php echo esc_html(get_theme_mod('vh360_professional_register_button', __('Create Professional Account', 'videohub360-theme'))); ?>
                        </button>
                        
                        <div class="vh360-auth-links">
                            <span><?php esc_html_e('Already have an account?', 'videohub360-theme'); ?></span>
                            <a href="<?php echo esc_url((function_exists('vh360_append_recurring_membership_bridge_args') ? vh360_append_recurring_membership_bridge_args(vh360_get_login_page_url(), isset($vh360_bridge_args) ? $vh360_bridge_args : array()) : vh360_get_login_page_url())); ?>" class="vh360-auth-link">
                                <?php esc_html_e('Sign In', 'videohub360-theme'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        </div><!-- .vh360-auth-container -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
