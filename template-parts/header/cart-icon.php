<?php
/**
 * Cart Icon Component
 *
 * WooCommerce cart icon with item count badge.
 * Only renders if WooCommerce is active.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only show if WooCommerce is active
if (!function_exists('WC')) {
    return;
}

$cart_count = WC()->cart->get_cart_contents_count();
$cart_url = wc_get_cart_url();
?>

<a href="<?php echo esc_url($cart_url); ?>" class="header-icon header-cart-link" aria-label="<?php esc_attr_e('Shopping cart', 'videohub360-theme'); ?>">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="9" cy="21" r="1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="20" cy="21" r="1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <?php if ($cart_count > 0) : ?>
        <span class="header-cart-count" data-count="<?php echo esc_attr($cart_count); ?>">
            <?php echo esc_html($cart_count > 99 ? '99+' : $cart_count); ?>
        </span>
    <?php endif; ?>
</a>
