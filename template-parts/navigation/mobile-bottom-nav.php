<?php
/**
 * Mobile Bottom Navigation
 *
 * Logged-in only bottom navigation bar for mobile devices.
 *
 * Configurable via Appearance > Menus.
 * Theme location: vh360_mobile_bottom
 *
 * Rules:
 * - Minimum 3 items. If fewer, a safe fallback renders.
 * - Maximum 5 items (extra items are trimmed by a filter in functions.php).
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

$use_configured_menu = false;

if ( has_nav_menu( 'vh360_mobile_bottom' ) ) {
    $locations = get_nav_menu_locations();
    if ( ! empty( $locations['vh360_mobile_bottom'] ) ) {
        $items = wp_get_nav_menu_items( (int) $locations['vh360_mobile_bottom'] );
        if ( is_array( $items ) ) {
            // Count only top-level items (depth is forced to 1, but still be defensive).
            $top_level = array_filter(
                $items,
                function( $i ) {
                    return empty( $i->menu_item_parent ) || '0' === (string) $i->menu_item_parent;
                }
            );
            $count = count( $top_level );
            if ( $count >= 3 ) {
                $use_configured_menu = true;
            }
        }
    }
}

?>

<nav class="vh360-mobile-bottom-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'videohub360-theme' ); ?>">
    <div class="vh360-mobile-bottom-nav__inner">

        <?php if ( $use_configured_menu ) : ?>
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
            // Safe fallback (matches your preferred default set).
            $user_id      = get_current_user_id();
            $activity_url = home_url( '/activity/' );
            $members_url  = home_url( '/members/' );

            // Find dashboard page URL (used for notifications tab).
            $dashboard_page = get_pages(
                array(
                    'meta_key'   => '_wp_page_template',
                    'meta_value' => 'template-dashboard.php',
                    'number'     => 1,
                )
            );
            $dashboard_url     = ! empty( $dashboard_page ) ? get_permalink( $dashboard_page[0]->ID ) : home_url( '/' );
            $notifications_url = add_query_arg( 'tab', 'notifications', $dashboard_url );
            $unread_count      = function_exists( 'vh360_get_unread_notification_count' ) ? (int) vh360_get_unread_notification_count( $user_id ) : 0;

            $avatar_url = get_avatar_url( $user_id, array( 'size' => 64 ) );
            $avatar_alt = wp_get_current_user()->display_name;
            ?>

            <a class="vh360-mobile-bottom-nav__item" href="<?php echo esc_url( $activity_url ); ?>" aria-label="<?php esc_attr_e( 'Activity', 'videohub360-theme' ); ?>">
                <svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 19h16"></path>
                    <path d="M4 15l4-4 4 4 8-8"></path>
                </svg>
                <span class="vh360-mobile-bottom-nav__label"><?php esc_html_e( 'Activity', 'videohub360-theme' ); ?></span>
            </a>

            <a class="vh360-mobile-bottom-nav__item" href="<?php echo esc_url( $notifications_url ); ?>" aria-label="<?php esc_attr_e( 'Notifications', 'videohub360-theme' ); ?>">
                <svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="vh360-mobile-bottom-nav__label"><?php esc_html_e( 'Notifications', 'videohub360-theme' ); ?></span>
                <?php if ( $unread_count > 0 ) : ?>
                    <span class="vh360-mobile-bottom-nav__badge" aria-label="<?php esc_attr_e( 'Unread notifications', 'videohub360-theme' ); ?>">
                        <?php echo esc_html( $unread_count > 99 ? '99+' : $unread_count ); ?>
                    </span>
                <?php endif; ?>
            </a>

            <a class="vh360-mobile-bottom-nav__item" href="<?php echo esc_url( $members_url ); ?>" aria-label="<?php esc_attr_e( 'Members', 'videohub360-theme' ); ?>">
                <svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="vh360-mobile-bottom-nav__label"><?php esc_html_e( 'Members', 'videohub360-theme' ); ?></span>
            </a>

            <button class="vh360-mobile-bottom-nav__item vh360-mobile-bottom-nav__menu-btn" type="button" aria-label="<?php esc_attr_e( 'Menu', 'videohub360-theme' ); ?>" aria-controls="vh360-mobile-user-drawer" aria-expanded="false">
                <span class="vh360-mobile-bottom-nav__avatar">
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $avatar_alt ); ?>" loading="lazy" />
                </span>
                <span class="vh360-mobile-bottom-nav__label"><?php esc_html_e( 'Menu', 'videohub360-theme' ); ?></span>
            </button>

        <?php endif; ?>

    </div>
</nav>
