<?php
/**
 * Template Name: Registration Landing Page
 *
 * Account-type selection page for Service Professional, Instructor/Educator, and Client registration paths.
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

get_header();

// Get URLs for the registration forms
$professional_url = function_exists('vh360_get_professional_register_url') ? vh360_get_professional_register_url() : home_url('/register-professional/');
$instructor_url   = function_exists('vh360_get_instructor_register_url') ? vh360_get_instructor_register_url() : home_url('/register-instructor/');
$client_url       = function_exists('vh360_get_client_register_url') ? vh360_get_client_register_url() : home_url('/register-client/');

$show_professional = (bool) get_theme_mod('vh360_registration_landing_show_professional', true);
$show_instructor   = (bool) get_theme_mod('vh360_registration_landing_show_instructor', true);
$show_client       = (bool) get_theme_mod('vh360_registration_landing_show_client', true);
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page vh360-registration-landing-page">

        <div class="vh360-auth-container vh360-registration-choice-container">

            <!-- Header Section -->
            <div class="vh360-registration-choice-header">
                <?php if (has_custom_logo()) : ?>
                    <div class="vh360-auth-logo">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else : ?>
                    <h2 class="vh360-auth-site-title"><?php bloginfo('name'); ?></h2>
                <?php endif; ?>

                <h1 class="vh360-registration-choice-title">
                    <?php echo esc_html(get_theme_mod('vh360_registration_landing_headline', __('Choose Your Account Type', 'videohub360-theme'))); ?>
                </h1>

                <p class="vh360-registration-choice-description">
                    <?php echo esc_html(get_theme_mod('vh360_registration_landing_description', __('Select the registration path that best fits how you plan to use this platform.', 'videohub360-theme'))); ?>
                </p>
            </div>

            <!-- Choice Cards -->
            <div class="vh360-registration-choice-cards">

                <?php if ($show_professional) : ?>
                <!-- Service Professional Card -->
                <div class="vh360-registration-card vh360-registration-card-professional">
                    <div class="vh360-registration-card-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h2 class="vh360-registration-card-title">
                        <?php echo esc_html(get_theme_mod('vh360_registration_professional_title', __('Service Professional', 'videohub360-theme'))); ?>
                    </h2>
                    <p class="vh360-registration-card-description">
                        <?php echo esc_html(get_theme_mod('vh360_registration_professional_description', __('For healthcare providers, consultants, coaches, organizations, and service professionals who need a business profile, services, availability, and client booking.', 'videohub360-theme'))); ?>
                    </p>
                    <ul class="vh360-registration-card-features">
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_professional_feature_1', __('Business profile', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_professional_feature_2', __('Services and specialties', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_professional_feature_3', __('Availability and appointments', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_professional_feature_4', __('Client-focused tools', 'videohub360-theme'))); ?></li>
                    </ul>
                    <a href="<?php echo esc_url($professional_url); ?>" class="vh360-registration-card-button vh360-button-primary">
                        <?php echo esc_html(get_theme_mod('vh360_registration_professional_button', __('Sign Up as Service Professional', 'videohub360-theme'))); ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($show_instructor) : ?>
                <!-- Instructor / Educator Card -->
                <div class="vh360-registration-card vh360-registration-card-instructor">
                    <div class="vh360-registration-card-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path>
                        </svg>
                    </div>
                    <h2 class="vh360-registration-card-title">
                        <?php echo esc_html(get_theme_mod('vh360_registration_instructor_title', __('Instructor / Educator', 'videohub360-theme'))); ?>
                    </h2>
                    <p class="vh360-registration-card-description">
                        <?php echo esc_html(get_theme_mod('vh360_registration_instructor_description', __('For course creators, teachers, coaches, and educators who want to build courses, publish lessons, and share learning content.', 'videohub360-theme'))); ?>
                    </p>
                    <ul class="vh360-registration-card-features">
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_instructor_feature_1', __('Create and manage courses', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_instructor_feature_2', __('Publish lessons and videos', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_instructor_feature_3', __('Build an instructor profile', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_instructor_feature_4', __('Share educational resources', 'videohub360-theme'))); ?></li>
                    </ul>
                    <a href="<?php echo esc_url($instructor_url); ?>" class="vh360-registration-card-button vh360-button-primary">
                        <?php echo esc_html(get_theme_mod('vh360_registration_instructor_button', __('Sign Up as Instructor', 'videohub360-theme'))); ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($show_client) : ?>
                <!-- Client Card -->
                <div class="vh360-registration-card vh360-registration-card-client">
                    <div class="vh360-registration-card-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h2 class="vh360-registration-card-title">
                        <?php echo esc_html(get_theme_mod('vh360_registration_client_title', __('Client', 'videohub360-theme'))); ?>
                    </h2>
                    <p class="vh360-registration-card-description">
                        <?php echo esc_html(get_theme_mod('vh360_registration_client_description', __('For members, learners, customers, and clients who want to follow creators, book services, access content, and participate in the community.', 'videohub360-theme'))); ?>
                    </p>
                    <ul class="vh360-registration-card-features">
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_client_feature_1', __('Follow profiles', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_client_feature_2', __('Access member content', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_client_feature_3', __('Book services when available', 'videohub360-theme'))); ?></li>
                        <li><?php echo esc_html(get_theme_mod('vh360_registration_client_feature_4', __('Join the community', 'videohub360-theme'))); ?></li>
                    </ul>
                    <a href="<?php echo esc_url($client_url); ?>" class="vh360-registration-card-button vh360-button-secondary">
                        <?php echo esc_html(get_theme_mod('vh360_registration_client_button', __('Sign Up as Client', 'videohub360-theme'))); ?>
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <div class="vh360-auth-links">
                <span><?php esc_html_e('Already have an account?', 'videohub360-theme'); ?></span>
                <a href="<?php echo esc_url(vh360_get_login_page_url()); ?>" class="vh360-auth-link">
                    <?php esc_html_e('Sign In', 'videohub360-theme'); ?>
                </a>
            </div>

        </div><!-- .vh360-registration-choice-container -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
