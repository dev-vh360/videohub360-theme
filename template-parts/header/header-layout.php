<?php
/**
 * Header Layout Component
 *
 * Main modular header structure with customizable navigation and icon zones.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Skip header rendering on auth pages if setting is enabled
if (function_exists('vh360_is_auth_page') && vh360_is_auth_page() && get_theme_mod('vh360_hide_header_on_auth_pages', 1)) {
    return; // Exit early - no header markup rendered
}

// Get customizer settings with defaults
$nav_style = get_theme_mod('vh360_nav_style', 'horizontal');
$sticky_header = get_theme_mod('vh360_sticky_header', true);
$show_search = get_theme_mod('vh360_show_search_icon', true);
$show_cart = get_theme_mod('vh360_show_cart_icon', false);
$show_messages = get_theme_mod('vh360_show_messages_icon', true);
$show_notifications = get_theme_mod('vh360_show_notifications_icon', true);
$show_user_menu = get_theme_mod('vh360_show_user_menu', true);
$icon_order = get_theme_mod('vh360_icon_order', 'search,cart,messages,notifications,user');

// Parse icon order
$icon_order_array = array_map('trim', explode(',', $icon_order));

// Determine center slot content priority:
// 1. Centered search (if enabled)
// 2. Horizontal navigation (if enabled and search is disabled)
// 3. Nothing (empty center)
$center_slot_content = null;
$show_nav_in_left = false;

if ($show_search) {
    $center_slot_content = 'search';
} elseif ($nav_style === 'horizontal') {
    $center_slot_content = 'horizontal-nav';
} else {
    $center_slot_content = null;
}

// Show horizontal nav in left zone only if it's not being used in center
$show_nav_in_left = ($nav_style === 'horizontal' && $center_slot_content !== 'horizontal-nav');

// Build header classes
$header_classes = array('site-header');
if ($sticky_header) {
    $header_classes[] = 'site-header--sticky';
}
if ($nav_style === 'hamburger') {
    $header_classes[] = 'site-header--hamburger';
    $header_classes[] = 'site-header--hamburger-left';
}
if ($center_slot_content === 'horizontal-nav') {
    $header_classes[] = 'site-header--nav-centered';
}
?>

<header id="masthead" class="<?php echo esc_attr(implode(' ', $header_classes)); ?>">
    <div class="container">
        <div class="site-header__inner">
            <!-- LEFT ZONE -->
            <div class="site-header__left">
                <?php 
                // Always render hamburger menu for mobile responsiveness
                // CSS controls visibility: hidden on desktop unless hamburger mode is selected
                // Always visible on mobile regardless of desktop setting
                get_template_part('template-parts/header/navigation-hamburger'); 
                ?>
                
                <?php get_template_part('template-parts/header/logo'); ?>
                
                <?php if ($show_nav_in_left) : ?>
                    <?php get_template_part('template-parts/header/navigation-horizontal'); ?>
                <?php endif; ?>
            </div><!-- .site-header__left -->
            
            <!-- CENTER ZONE -->
            <div class="site-header__center">
                <?php if ($center_slot_content === 'search') : ?>
                    <?php get_template_part('template-parts/header/search-bar-centered'); ?>
                <?php elseif ($center_slot_content === 'horizontal-nav') : ?>
                    <?php get_template_part('template-parts/header/navigation-horizontal'); ?>
                <?php endif; ?>
            </div><!-- .site-header__center -->
            
            <!-- RIGHT ZONE -->
            <div class="site-header__right">
                <?php
                // Render icons in custom order (excluding search, now in center)
                foreach ($icon_order_array as $icon) {
                    switch ($icon) {
                        case 'search':
                            // Search is now in center zone, skip here
                            break;
                        
                        case 'cart':
                            if ($show_cart) {
                                get_template_part('template-parts/header/cart-icon');
                            }
                            break;
                        
                        case 'messages':
                            if ($show_messages && is_user_logged_in()) {
                                get_template_part('template-parts/components/message-icon');
                            }
                            break;
                        
                        case 'notifications':
                            if ($show_notifications && is_user_logged_in()) {
                                get_template_part('template-parts/notifications/notification-bell');
                            }
                            break;
                        
                        case 'user':
                            if ($show_user_menu) {
                                get_template_part('template-parts/components/user-menu');
                            }
                            break;
                    }
                }
                ?>
            </div><!-- .site-header__right -->
        </div><!-- .site-header__inner -->
    </div><!-- .container -->
</header><!-- #masthead -->
