<?php
/**
 * Community Menu Template Part
 *
 * Displays the persistent left rail navigation menu on desktop.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if Community Menu should be displayed
if (!vh360_show_community_menu()) {
    return;
}

$current_user = wp_get_current_user();
$is_compact = (bool) get_theme_mod('vh360_community_menu_compact', 0) || vh360_force_compact_community_menu();
$is_forced_compact = vh360_force_compact_community_menu();
$menu_classes = array('vh360-community-menu');

if ($is_compact) {
    $menu_classes[] = 'vh360-community-menu--compact';
}
?>

<aside id="vh360-community-menu" class="<?php echo esc_attr(implode(' ', $menu_classes)); ?>" aria-label="<?php esc_attr_e('Community Navigation', 'videohub360-theme'); ?>">
    <div class="vh360-community-menu__inner">
        
        <?php if ($is_compact) : ?>
            <!-- Toggle Button for Compact Mode -->
            <button 
                type="button" 
                class="vh360-community-menu__toggle" 
                aria-controls="vh360-community-menu" 
                aria-expanded="false"
                aria-label="<?php esc_attr_e('Expand community menu', 'videohub360-theme'); ?>"
            >
                <svg class="vh360-community-menu__toggle-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="vh360-community-menu__toggle-label"><?php esc_html_e('Menu', 'videohub360-theme'); ?></span>
            </button>
        <?php endif; ?>
        
        <?php if (is_user_logged_in()) : ?>
            <!-- User Profile Card -->
            <div class="vh360-community-menu__profile-card">
                <a href="<?php echo esc_url(get_author_posts_url($current_user->ID)); ?>" class="vh360-community-menu__profile-link">
                    <?php echo get_avatar($current_user->ID, 48, '', '', array('class' => 'vh360-community-menu__avatar')); ?>
                    <div class="vh360-community-menu__profile-info">
                        <span class="vh360-community-menu__profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="vh360-community-menu__profile-username">@<?php echo esc_html($current_user->user_login); ?></span>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <!-- Navigation Menu -->
        <nav class="vh360-community-menu__nav" aria-label="<?php esc_attr_e('Community Menu', 'videohub360-theme'); ?>">
            <?php
            if (has_nav_menu('community')) {
                wp_nav_menu(array(
                    'theme_location'  => 'community',
                    'container'       => false,
                    'menu_class'      => 'vh360-community-menu__list',
                    'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'depth'           => 1,
                    'walker'          => new VH360_Community_Menu_Walker(),
                    'fallback_cb'     => false,
                ));
            } else {
                // Show admin notice if no menu assigned
                if (current_user_can('edit_theme_options')) {
                    ?>
                    <div class="vh360-community-menu__notice">
                        <p><?php esc_html_e('No menu assigned to Community Menu location.', 'videohub360-theme'); ?></p>
                        <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" class="vh360-community-menu__notice-link">
                            <?php esc_html_e('Assign a menu', 'videohub360-theme'); ?>
                        </a>
                    </div>
                    <?php
                }
            }
            ?>
        </nav>

    </div><!-- .vh360-community-menu__inner -->
</aside><!-- .vh360-community-menu -->
