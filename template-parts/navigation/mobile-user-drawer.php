<?php
/**
 * Mobile User Drawer
 *
 * Logged-in only slide-up drawer that combines user menu links and dashboard links.
 * Opened from the mobile bottom navigation.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    return;
}

$user_id   = get_current_user_id();
$user      = wp_get_current_user();
$avatar    = get_avatar_url( $user_id, array( 'size' => 96 ) );
$profile   = get_author_posts_url( $user_id );

// Use helper to get dashboard page URL
$dashboard_url = vh360_get_dashboard_page_url();

// "Go Live" CTA is sourced ONLY from vh360_mobile_drawer menu location
// and controlled by the vh360-go-live-cta CSS class
$go_live_item  = vh360_get_menu_item_by_class( 'vh360_mobile_drawer', 'vh360-go-live-cta' );
$go_live_url   = $go_live_item && ! empty( $go_live_item->url ) ? $go_live_item->url : '';
$go_live_label = $go_live_item && ! empty( $go_live_item->title ) ? wp_strip_all_tags( $go_live_item->title ) : __( 'Go Live', 'videohub360-theme' );
$show_go_live  = ! empty( $go_live_url );

$logout_url = function_exists( 'vh360_get_logout_url' ) ? vh360_get_logout_url( home_url() ) : wp_logout_url( home_url() );

?>

<div class="vh360-mobile-user-drawer" id="vh360-mobile-user-drawer" aria-hidden="true" role="dialog" aria-label="<?php esc_attr_e( 'User menu', 'videohub360-theme' ); ?>">
    <div class="vh360-mobile-user-drawer__backdrop" data-vh360-drawer-close></div>

    <div class="vh360-mobile-user-drawer__panel" role="document">
        <div class="vh360-mobile-user-drawer__header">
            <div class="vh360-mobile-user-drawer__user">
                <img class="vh360-mobile-user-drawer__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" loading="lazy" />
                <div class="vh360-mobile-user-drawer__meta">
                    <div class="vh360-mobile-user-drawer__name"><?php echo esc_html( $user->display_name ); ?></div>
                    <div class="vh360-mobile-user-drawer__email"><?php echo esc_html( $user->user_email ); ?></div>
                </div>
            </div>

            <button type="button" class="vh360-mobile-user-drawer__close" aria-label="<?php esc_attr_e( 'Close', 'videohub360-theme' ); ?>" data-vh360-drawer-close>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="M6 6 18 18"></path>
                </svg>
            </button>
        </div>

        <div class="vh360-mobile-user-drawer__content">
            <div class="vh360-mobile-user-drawer__section">
                <div class="vh360-mobile-user-drawer__section-title"><?php esc_html_e( 'Account', 'videohub360-theme' ); ?></div>
                <div class="vh360-mobile-user-drawer__links">
                    <a class="vh360-mobile-user-drawer__link" href="<?php echo esc_url( $profile ); ?>"><?php esc_html_e( 'View Profile', 'videohub360-theme' ); ?></a>
                    <a class="vh360-mobile-user-drawer__link" href="<?php echo esc_url( add_query_arg( 'tab', 'profile', $dashboard_url ) ); ?>"><?php esc_html_e( 'Edit Profile', 'videohub360-theme' ); ?></a>
                    <a class="vh360-mobile-user-drawer__link" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Logout', 'videohub360-theme' ); ?></a>
                </div>
            </div>

            <div class="vh360-mobile-user-drawer__section">
                <div class="vh360-mobile-user-drawer__section-title"><?php esc_html_e( 'Dashboard', 'videohub360-theme' ); ?></div>
                <div class="vh360-mobile-user-drawer__links">
                    <?php
                    // Check if a menu is assigned to vh360_mobile_drawer location
                    $locations = get_nav_menu_locations();
                    $has_drawer_menu = ! empty( $locations['vh360_mobile_drawer'] );
                    
                    if ( $has_drawer_menu ) {
                        // Fetch menu items from vh360_mobile_drawer location
                        $menu_id = (int) $locations['vh360_mobile_drawer'];
                        $menu_items = wp_get_nav_menu_items( $menu_id );
                        
                        // Check if menu items were successfully retrieved
                        if ( false !== $menu_items && ! empty( $menu_items ) ) {
                            // Apply visibility filtering pipeline (same as other menus)
                            $args = (object) array(
                                'theme_location' => 'vh360_mobile_drawer',
                                'menu'           => $menu_id,
                            );
                            $menu_items = apply_filters( 'wp_nav_menu_objects', $menu_items, $args );
                            
                            // Filter out the CTA item to prevent duplicates (it appears in footer)
                            $menu_items = vh360_filter_menu_items_excluding_class( $menu_items, 'vh360-go-live-cta' );
                            
                            // Output menu items using drawer markup
                            foreach ( $menu_items as $item ) {
                                if ( ! empty( $item->url ) ) {
                                    ?>
                                    <a class="vh360-mobile-user-drawer__link" href="<?php echo esc_url( $item->url ); ?>">
                                        <?php echo esc_html( $item->title ); ?>
                                    </a>
                                    <?php
                                }
                            }
                        } else {
                            // Menu assigned but empty - show admin notice
                            if ( function_exists( 'vh360_render_menu_admin_notice' ) ) {
                                vh360_render_menu_admin_notice( 'vh360_mobile_drawer', __( 'Mobile User Drawer', 'videohub360-theme' ) );
                            }
                        }
                    } else {
                        // No menu assigned - show admin notice
                        if ( function_exists( 'vh360_render_menu_admin_notice' ) ) {
                            vh360_render_menu_admin_notice( 'vh360_mobile_drawer', __( 'Mobile User Drawer', 'videohub360-theme' ) );
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if ( $show_go_live ) : ?>
        <!-- Primary CTA (driven by Appearance → Menus visibility rules) -->
        <div class="vh360-mobile-user-drawer__footer">
            <a class="vh360-mobile-user-drawer__go-live" href="<?php echo esc_url( $go_live_url ); ?>">
                <svg class="vh360-mobile-user-drawer__go-live-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <?php echo esc_html( $go_live_label ); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
