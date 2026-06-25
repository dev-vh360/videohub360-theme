<?php
/**
 * Template Name: Register
 *
 * Custom registration page template with branded design
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
    if (isset($_GET['vh360_registration_error'])) {
        $error_code = sanitize_key(wp_unslash($_GET['vh360_registration_error']));
    } elseif (isset($_GET['error'])) {
        $error_code = sanitize_key(wp_unslash($_GET['error']));
    } else {
        $error_code = 'unknown';
    }
    $registration_error = $error_code;
}

get_header();

// Fetch customizable register content and appearance settings
$vh360_register_headline    = get_theme_mod('vh360_register_headline', __('Join {site_name}', 'videohub360-theme'));
$vh360_register_headline    = str_replace('{site_name}', get_bloginfo('name'), $vh360_register_headline);
$vh360_register_description = get_theme_mod('vh360_register_description', __('Create your account and start your video journey today!', 'videohub360-theme'));
// Gather benefits and icons; omit entries with empty text later
$vh360_register_benefits = array(
    array(
        'text' => get_theme_mod('vh360_register_benefit_1', __('Upload and share your videos', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_register_icon_1', '✓'),
    ),
    array(
        'text' => get_theme_mod('vh360_register_benefit_2', __('Comment and engage with content', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_register_icon_2', '✓'),
    ),
    array(
        'text' => get_theme_mod('vh360_register_benefit_3', __('Connect with other members', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_register_icon_3', '✓'),
    ),
    array(
        'text' => get_theme_mod('vh360_register_benefit_4', __('Build your profile and community', 'videohub360-theme')),
        'icon' => get_theme_mod('vh360_register_icon_4', '✓'),
    ),
);

// Collect custom registration field settings
$vh360_custom_fields = array();
for ($i = 1; $i <= 2; $i++) {
    $enabled = (bool) get_theme_mod("vh360_custom_field_{$i}_enable", false);
    $label   = get_theme_mod("vh360_custom_field_{$i}_label", '');
    $slug    = get_theme_mod("vh360_custom_field_{$i}_slug", '');
    if ($enabled && !empty($slug)) {
        $vh360_custom_fields[] = array(
            'label' => $label,
            'slug'  => sanitize_title($slug),
        );
    }
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main vh360-auth-page register-page">
        
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
                        <?php echo esc_html($vh360_register_headline); ?>
                    </h1>
                    
                    <p class="vh360-auth-description">
                        <?php echo esc_html($vh360_register_description); ?>
                    </p>
                    
                    <div class="vh360-auth-benefits">
                        <h3 class="vh360-auth-benefits-title">
                            <?php esc_html_e('Member Benefits:', 'videohub360-theme'); ?>
                        </h3>
                        <ul class="vh360-auth-benefits-list">
                            <?php foreach ($vh360_register_benefits as $benefit) :
                                $text = isset($benefit['text']) ? $benefit['text'] : '';
                                $icon = isset($benefit['icon']) ? $benefit['icon'] : '';
                                if (!empty($text)) : ?>
                                <li>
                                    <span class="vh360-auth-benefit-icon"><?php echo esc_html($icon); ?></span>
                                    <?php echo esc_html($text); ?>
                                </li>
                            <?php endif; endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="vh360-auth-form-wrapper register-page">
                <div class="vh360-auth-form-content">
                    <h2 class="vh360-auth-form-title">
                        <?php esc_html_e('Create Account', 'videohub360-theme'); ?>
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
                    
                    // Preserve form values (but sanitize them) - only if there's an error after POST
                    $submitted_first_name = '';
                    $submitted_last_name  = '';
                    $submitted_username   = '';
                    $submitted_email      = '';
                    if ($registration_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
                        $submitted_first_name = isset($_POST['vh360_first_name']) ? sanitize_text_field($_POST['vh360_first_name']) : '';
                        $submitted_last_name  = isset($_POST['vh360_last_name']) ? sanitize_text_field($_POST['vh360_last_name']) : '';
                        $submitted_username   = isset($_POST['vh360_username']) ? sanitize_user($_POST['vh360_username']) : '';
                        $submitted_email      = isset($_POST['vh360_email']) ? sanitize_email($_POST['vh360_email']) : '';
                    }
                    ?>
                    
                    <form method="post" action="" id="vh360-registerform" class="vh360-auth-form">
                        <?php wp_nonce_field('vh360_registration', 'vh360_register_nonce'); ?>
                        <?php $vh360_bridge_args = function_exists('vh360_get_recurring_membership_bridge_args') ? vh360_get_recurring_membership_bridge_args() : array(); ?>
                        <?php if (!empty($vh360_bridge_args['vh360_plan'])) : ?><input type="hidden" name="vh360_plan" value="<?php echo esc_attr($vh360_bridge_args['vh360_plan']); ?>" /><?php endif; ?>
                        <?php if (!empty($vh360_bridge_args['redirect_to'])) : ?><input type="hidden" name="vh360_redirect_to" value="<?php echo esc_attr($vh360_bridge_args['redirect_to']); ?>" /><?php endif; ?>
                        
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
                        
                        <?php get_template_part('template-parts/auth/invite-code-field', null, array('context' => 'general')); ?>

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
                        
                        <!-- Custom Fields -->
                        <?php
                        // Preserve submitted custom field values upon error
                        $submitted_custom = array();
                        if ($registration_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
                            foreach ($vh360_custom_fields as $cf) {
                                $key = 'vh360_custom_' . $cf['slug'];
                                $submitted_custom[$cf['slug']] = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
                            }
                        }
                        foreach ($vh360_custom_fields as $cf) :
                            $cf_slug  = $cf['slug'];
                            $cf_label = $cf['label'];
                            $input_name = 'vh360_custom_' . $cf_slug;
                            $prev_value = isset($submitted_custom[$cf_slug]) ? $submitted_custom[$cf_slug] : '';
                        ?>
                        <div class="vh360-auth-field">
                            <label for="<?php echo esc_attr($input_name); ?>">
                                <?php echo esc_html($cf_label); ?>
                            </label>
                            <input type="text" name="<?php echo esc_attr($input_name); ?>" id="<?php echo esc_attr($input_name); ?>" class="vh360-auth-input" value="<?php echo esc_attr($prev_value); ?>">
                        </div>
                        <?php endforeach; ?>

                        <button type="submit" name="vh360_register_submit" class="vh360-auth-submit">
                            <?php esc_html_e('Create Account', 'videohub360-theme'); ?>
                        </button>
                        
                        <div class="vh360-auth-links">
                            <span><?php esc_html_e('Already have an account?', 'videohub360-theme'); ?></span>
                            <a href="<?php echo esc_url((function_exists('vh360_append_recurring_membership_bridge_args') ? vh360_append_recurring_membership_bridge_args(vh360_get_login_page_url(), $vh360_bridge_args) : vh360_get_login_page_url())); ?>" class="vh360-auth-link">
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
