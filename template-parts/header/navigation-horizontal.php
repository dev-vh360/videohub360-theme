<?php
/**
 * Horizontal Navigation Component
 *
 * Traditional WordPress nav menu using primary theme location.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<nav class="main-navigation main-navigation--horizontal" role="navigation" aria-label="<?php esc_attr_e('Primary navigation', 'videohub360-theme'); ?>">
    <?php
    wp_nav_menu(array(
        'theme_location' => 'primary',
        'menu_id'        => 'primary-menu',
        'container'      => false,
        'fallback_cb'    => false,
    ));
    ?>
</nav><!-- .main-navigation--horizontal -->
