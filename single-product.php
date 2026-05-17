<?php
/**
 * WooCommerce single product template
 *
 * Renders a commerce-style product page with:
 *   • Left column  – product gallery / image
 *   • Right column – title, price, short description, add-to-cart
 *   • Lower section – full content, tabs, related products
 *
 * Membership products receive an additional CSS class so they can be
 * styled with a premium-plan presentation (stronger hierarchy, prominent
 * price, stronger CTA).
 *
 * This template completely replaces the generic single.php path for
 * WooCommerce products, so no blog-style post meta, entry footer,
 * post navigation, or generic WordPress comments are rendered.
 *
 * @package Videohub360_Theme
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Detect membership products for premium-plan styling.
$is_membership_product = false;
if ( class_exists( 'VH360_Membership_Plans' ) && method_exists( 'VH360_Membership_Plans', 'get_product_membership_mapping' ) ) {
    $mapping = VH360_Membership_Plans::get_product_membership_mapping( get_the_ID() );
    if ( $mapping && ! empty( $mapping['plan_key'] ) ) {
        $is_membership_product = true;
    }
}

$product_classes = 'vh360-woocommerce-page vh360-single-product';
if ( $is_membership_product ) {
    $product_classes .= ' vh360-membership-product';
}

// Resolve sidebar configuration for single product pages.
$sidebar_config = vh360_resolve_sidebar();
$has_sidebar    = $sidebar_config['show_sidebar'];
$sidebar_pos    = $sidebar_config['position'];

$container_class = 'container';
if ( $has_sidebar ) {
    $container_class .= ' has-sidebar sidebar-' . $sidebar_pos;
} else {
    $container_class .= ' no-sidebar';
}
?>

<div id="primary" class="site-content <?php echo esc_attr( $product_classes ); ?>">
    <div class="<?php echo esc_attr( $container_class ); ?>">

        <?php if ( $has_sidebar && 'left' === $sidebar_pos ) : ?>
            <?php get_sidebar(); ?>
        <?php endif; ?>

        <main id="main" class="content-area">

            <?php woocommerce_output_all_notices(); ?>

            <?php
            while ( have_posts() ) :
                the_post();

                wc_get_template_part( 'content', 'single-product' );

            endwhile;
            ?>

        </main><!-- #main -->

        <?php if ( $has_sidebar && 'right' === $sidebar_pos ) : ?>
            <?php get_sidebar(); ?>
        <?php endif; ?>

    </div><!-- .container -->
</div><!-- #primary -->

<?php
get_footer();

