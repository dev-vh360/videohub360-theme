<?php
/**
 * WooCommerce shop / product archive template
 *
 * Controls the layout for the main shop page, product category pages,
 * product tag pages, and any other product archive view.
 *
 * The template renders a branded shop header, a result-count / ordering
 * toolbar, and a responsive product grid that matches the Videohub360
 * design system.
 *
 * @package Videohub360_Theme
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div id="primary" class="site-content vh360-woocommerce-page vh360-shop-archive">
    <div class="vh360-shop-archive-layout">

        <main id="main" class="content-area vh360-shop-archive-main">

            <?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
                <header class="vh360-shop-header">
                    <h1 class="vh360-shop-title"><?php woocommerce_page_title(); ?></h1>
                    <?php
                    // Archive description (set in WooCommerce → Settings or category description).
                    do_action( 'woocommerce_archive_description' );
                    ?>
                </header>
            <?php endif; ?>

            <?php woocommerce_output_all_notices(); ?>

            <?php if ( woocommerce_product_loop() ) : ?>

                <div class="vh360-shop-toolbar">
                    <?php
                    woocommerce_result_count();
                    woocommerce_catalog_ordering();
                    ?>
                </div>

                <?php woocommerce_product_loop_start(); ?>

                <?php
                if ( wc_get_loop_prop( 'total' ) ) {
                    while ( have_posts() ) {
                        the_post();

                        /**
                         * Hook: woocommerce_shop_loop.
                         */
                        do_action( 'woocommerce_shop_loop' );

                        wc_get_template_part( 'content', 'product' );
                    }
                }
                ?>

                <?php woocommerce_product_loop_end(); ?>

                <?php
                /**
                 * Hook: woocommerce_after_shop_loop.
                 *
                 * @hooked woocommerce_pagination - 10
                 */
                do_action( 'woocommerce_after_shop_loop' );
                ?>

            <?php else : ?>
                <?php
                /**
                 * Hook: woocommerce_no_products_found.
                 *
                 * @hooked wc_no_products_found - 10
                 */
                do_action( 'woocommerce_no_products_found' );
                ?>
            <?php endif; ?>

        </main><!-- #main -->

    </div><!-- .vh360-shop-archive-layout -->
</div><!-- #primary -->

<?php
get_footer();
