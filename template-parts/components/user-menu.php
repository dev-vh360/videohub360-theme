<?php
/**
 * User Menu Component
 * 
 * Displays user avatar dropdown menu or sign in button
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
$current_user_id = $is_logged_in ? get_current_user_id() : 0;
$user = $is_logged_in ? wp_get_current_user() : null;

// Get user data
if ($is_logged_in) {
    $avatar_url = vh360_get_user_avatar_url($current_user_id, 40);
    // Fallback to default mystery person avatar if empty
    if (empty($avatar_url)) {
        $avatar_url = get_avatar_url($current_user_id, array('size' => 40, 'default' => 'mystery'));
    }
    $display_name = $user->display_name;
    $user_email = $user->user_email;
}
?>

<div class="vh360-user-menu">
    <?php if ($is_logged_in) : ?>
        <!-- Avatar Button -->
        <button 
            class="vh360-user-menu-toggle" 
            type="button"
            aria-label="<?php esc_attr_e('User menu', 'videohub360-theme'); ?>"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <img 
                src="<?php echo esc_url($avatar_url); ?>" 
                alt="<?php echo esc_attr($display_name); ?>"
                class="vh360-user-avatar"
            >
        </button>
        
        <!-- Dropdown Menu -->
        <div class="vh360-user-dropdown" hidden>
            <!-- Menu Items -->
            <nav class="vh360-user-nav" role="navigation" aria-label="<?php esc_attr_e('User menu', 'videohub360-theme'); ?>">
                <?php echo vh360_get_user_menu_items(); ?>
            </nav>
        </div>
    <?php else : ?>
        <!-- Sign In Button with Smart Detection -->
        <?php if (get_theme_mod('header_show_signin_button', true)) : ?>
            <a 
                href="<?php echo esc_url(vh360_get_login_page_url()); ?>" 
                class="vh360-sign-in-btn"
            >
                <?php esc_html_e('Sign In', 'videohub360-theme'); ?>
            </a>
        <?php endif; ?>
        
        <!-- Register Button (only when logged out and registration is enabled) -->
        <?php if (get_theme_mod('header_show_register_button', true) && get_option('users_can_register')) : ?>
            <a 
                href="<?php echo esc_url(vh360_get_register_page_url()); ?>" 
                class="vh360-register-btn"
            >
                <?php esc_html_e('Register', 'videohub360-theme'); ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
