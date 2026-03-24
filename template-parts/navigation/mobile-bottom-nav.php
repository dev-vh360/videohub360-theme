<?php
/**
 * Mobile Bottom Navigation
 *
 * Logged-in only bottom navigation bar for mobile devices.
 *
 * Configurable via Appearance > Menus.
 * Theme location: vh360_mobile_bottom
 *
 * Layout notes:
 * - Minimum 3 items recommended for balanced mobile layout
 * - Layout handles 1-5 items gracefully with flexbox
 * - Maximum 5 items enforced by filter in functions.php
 *
 * Icon system (no dependencies):
 * - Add CSS classes to menu items (Screen Options > CSS Classes):
 *   - vh360-icon-activity
 *   - vh360-icon-notifications (adds unread badge)
 *   - vh360-icon-members
 *   - vh360-icon-communities
 *   - vh360-icon-avatar (renders user's avatar and opens the drawer)
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    return;
}

?>

<nav class="vh360-mobile-bottom-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'videohub360-theme' ); ?>">
    <div class="vh360-mobile-bottom-nav__inner">

        <?php if ( has_nav_menu( 'vh360_mobile_bottom' ) ) : ?>
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'vh360_mobile_bottom',
                    'container'      => false,
                    'menu_class'     => 'vh360-mobile-bottom-nav__menu',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                )
            );
            ?>
        <?php else : ?>
            <?php
            // Show admin-only notice when menu is not assigned
            if ( function_exists( 'vh360_render_menu_admin_notice' ) ) {
                vh360_render_menu_admin_notice( 'vh360_mobile_bottom', __( 'Mobile Bottom Nav', 'videohub360-theme' ) );
            }
            ?>
        <?php endif; ?>

    </div>
</nav>
