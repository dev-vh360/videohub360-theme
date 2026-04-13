<?php
/**
 * WooCommerce base wrapper template
 *
 * This template provides the outer page structure for all WooCommerce pages
 * that are not handled by more specific templates (archive-product.php,
 * single-product.php). It replaces the generic archive.php / single.php
 * fallback so WooCommerce content gets a dedicated, sidebar-free wrapper.
 *
 * @package Videohub360_Theme
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div id="primary" class="site-content vh360-woocommerce-page">
    <div class="container no-sidebar">

        <main id="main" class="content-area">
            <?php woocommerce_content(); ?>
        </main><!-- #main -->

    </div><!-- .container -->
</div><!-- #primary -->

<?php
get_footer();
