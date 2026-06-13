<?php
/**
 * Hamburger Navigation Component
 *
 * Hamburger menu with slide-out panel from left side.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<nav class="main-navigation main-navigation--hamburger" role="navigation" aria-label="<?php esc_attr_e('Primary navigation', 'videohub360-theme'); ?>">
    <button class="hamburger-toggle" aria-controls="hamburger-menu" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle menu', 'videohub360-theme'); ?>">
        <span class="hamburger-icon">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </span>
        <span class="hamburger-label"><?php esc_html_e('Menu', 'videohub360-theme'); ?></span>
    </button>
    
    <div id="hamburger-menu" class="hamburger-menu" aria-hidden="true">
        <div class="hamburger-menu__backdrop"></div>
        <div class="hamburger-menu__panel">
            <div class="hamburger-menu__header">
                <button class="hamburger-close" aria-label="<?php esc_attr_e('Close menu', 'videohub360-theme'); ?>">&times;</button>
            </div>
            <div class="hamburger-menu__body">
                <?php if (!is_user_logged_in()) : ?>
                    <!-- Authentication Links Section (Mobile Only) -->
                    <div class="hamburger-menu__auth-section">
                        <?php if (get_theme_mod('header_show_signin_button', true)) : ?>
                            <a href="<?php echo esc_url(vh360_get_login_page_url_with_redirect()); ?>" class="hamburger-auth-link hamburger-auth-link--signin" aria-label="<?php esc_attr_e('Sign in to your account', 'videohub360-theme'); ?>">
                                <?php echo vh360_get_signin_icon(20); ?>
                                <span><?php esc_html_e('Sign In', 'videohub360-theme'); ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (get_theme_mod('header_show_register_button', true) && get_option('users_can_register')) : ?>
                            <a href="<?php echo esc_url(vh360_get_register_page_url()); ?>" class="hamburger-auth-link hamburger-auth-link--register" aria-label="<?php esc_attr_e('Create a new account', 'videohub360-theme'); ?>">
                                <?php echo vh360_get_register_icon(20); ?>
                                <span><?php esc_html_e('Register', 'videohub360-theme'); ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <div class="hamburger-menu__divider"></div>
                    </div>
                <?php endif; ?>
                
                <?php
                // In "Community" navigation style, the desktop left rail is hidden below 1024px.
                // For tablet/mobile we want the hamburger to provide the *community* navigation.
                $vh360_nav_style = get_theme_mod('vh360_nav_style', 'horizontal');
                $vh360_menu_location = ($vh360_nav_style === 'community') ? 'community' : 'primary';
                $vh360_menu_id = ($vh360_menu_location === 'community') ? 'hamburger-community-menu' : 'hamburger-primary-menu';

                wp_nav_menu(array(
                    'theme_location' => $vh360_menu_location,
                    'menu_id'        => $vh360_menu_id,
                    'container'      => false,
                    'fallback_cb'    => false,
                ));
                ?>
            </div>
        </div>
    </div>
</nav><!-- .main-navigation--hamburger -->
