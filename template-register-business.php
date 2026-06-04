<?php
/**
 * Template Name: Business Registration Landing
 *
 * Landing page for Business registration with two paths: Professional or Client
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

// Handle single-path mode redirects
$landing_mode = get_theme_mod('vh360_business_landing_mode', 'both');

if ($landing_mode === 'professional_only') {
    wp_safe_redirect(vh360_get_professional_register_url());
    exit;
}

if ($landing_mode === 'client_only') {
    wp_safe_redirect(vh360_get_client_register_url());
    exit;
}

get_header();

// Get URLs for the registration forms
$professional_url = vh360_get_professional_register_url();
$client_url = vh360_get_client_register_url();

// Cards rendering logic for 'both' mode
$show_professional = ($landing_mode === 'both' || $landing_mode === 'professional_only');
$show_client = ($landing_mode === 'both' || $landing_mode === 'client_only');
$single_card_mode = ($landing_mode !== 'both');
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page business-register-page">
        
        <div class="vh360-auth-container vh360-business-choice-container">
            
            <!-- Header Section -->
            <div class="vh360-business-choice-header">
                <?php if (has_custom_logo()) : ?>
                    <div class="vh360-auth-logo">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else : ?>
                    <h2 class="vh360-auth-site-title"><?php bloginfo('name'); ?></h2>
                <?php endif; ?>
                
                <h1 class="vh360-business-choice-title">
                    <?php echo esc_html(get_theme_mod('vh360_business_landing_headline', __('Join as a Business Professional or Client', 'videohub360-theme'))); ?>
                </h1>
                
                <p class="vh360-business-choice-description">
                    <?php echo esc_html(get_theme_mod('vh360_business_landing_description', __('Choose the account type that best fits your needs', 'videohub360-theme'))); ?>
                </p>
            </div>
            
            <!-- Choice Cards -->
            <div class="vh360-business-choice-cards<?php echo $single_card_mode ? ' vh360-business-choice-cards--single' : ''; ?>">
                
                <?php if ($show_professional) : ?>
                <!-- Professional Card -->
                <div class="vh360-business-choice-card">
                    <div class="vh360-business-choice-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h2 class="vh360-business-choice-card-title">
                        <?php echo esc_html(get_theme_mod('vh360_business_professional_title', __('Service Professional', 'videohub360-theme'))); ?>
                    </h2>
                    <p class="vh360-business-choice-card-description">
                        <?php echo esc_html(get_theme_mod('vh360_business_professional_description', __('For therapists, consultants, coaches, healthcare providers, and service professionals who need a business profile, services, availability, and client booking.', 'videohub360-theme'))); ?>
                    </p>
                    <ul class="vh360-business-choice-features">
                        <li><?php echo esc_html(get_theme_mod('vh360_business_professional_feature_1', __('Business profile with services', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_professional_feature_2', __('Display credentials & specialties', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_professional_feature_3', __('Contact information & booking', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_professional_feature_4', __('Share content & resources', 'videohub360-theme'))); ?></li>
                    </ul>
                    <a href="<?php echo esc_url($professional_url); ?>" class="vh360-business-choice-button vh360-button-primary">
                        <?php echo esc_html(get_theme_mod('vh360_business_professional_button', __('Sign Up as Service Professional', 'videohub360-theme'))); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($show_client) : ?>
                <!-- Client Card -->
                <div class="vh360-business-choice-card">
                    <div class="vh360-business-choice-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h2 class="vh360-business-choice-card-title">
                        <?php echo esc_html(get_theme_mod('vh360_business_client_title', __('Client', 'videohub360-theme'))); ?>
                    </h2>
                    <p class="vh360-business-choice-card-description">
                        <?php echo esc_html(get_theme_mod('vh360_business_client_description', __('For individuals seeking services, engaging with content, and connecting with professionals.', 'videohub360-theme'))); ?>
                    </p>
                    <ul class="vh360-business-choice-features">
                        <li><?php echo esc_html(get_theme_mod('vh360_business_client_feature_1', __('Simple profile setup', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_client_feature_2', __('Connect with professionals', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_client_feature_3', __('Engage with content', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_business_client_feature_4', __('Privacy-focused experience', 'videohub360-theme'))); ?></li>
                    </ul>
                    <a href="<?php echo esc_url($client_url); ?>" class="vh360-business-choice-button vh360-button-secondary">
                        <?php echo esc_html(get_theme_mod('vh360_business_client_button', __('Sign Up as Client', 'videohub360-theme'))); ?>
                    </a>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Alternative Link -->
            <div class="vh360-auth-links vh360-business-choice-footer">
                <span><?php echo esc_html(get_theme_mod('vh360_business_landing_footer_text', __('Looking for a different account type?', 'videohub360-theme'))); ?></span>
                <a href="<?php echo esc_url(vh360_get_register_page_url()); ?>" class="vh360-auth-link">
                    <?php echo esc_html(get_theme_mod('vh360_business_landing_footer_link', __('Standard Registration', 'videohub360-theme'))); ?>
                </a>
            </div>
            
            <div class="vh360-auth-links">
                <span><?php esc_html_e('Already have an account?', 'videohub360-theme'); ?></span>
                <a href="<?php echo esc_url(vh360_get_login_page_url()); ?>" class="vh360-auth-link">
                    <?php esc_html_e('Sign In', 'videohub360-theme'); ?>
                </a>
            </div>
            
        </div><!-- .vh360-business-choice-container -->
        
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
