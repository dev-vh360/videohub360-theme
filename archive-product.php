<?php
/**
 * WooCommerce shop / product archive template
 *
 * Controls the layout for the main shop page, product category pages,
 * product tag pages, and any other product archive view.
 *
 * Renders a fully branded Videohub360 storefront: optional hero banner,
 * premium header card, WooCommerce notices, optional benefits strip,
 * optional featured product, optional category pills, enhanced toolbar,
 * the WooCommerce product loop, pagination, and a branded empty state.
 *
 * @package Videohub360_Theme
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent the default woocommerce_before_shop_loop callbacks from duplicating
// the result count and ordering dropdown that the custom toolbar already outputs.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

get_header();

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper        - 10 (outputs opening divs for the wc content)
 * @hooked woocommerce_breadcrumb                    - 20
 * @hooked WC_Structured_Data::generate_website_data - 30
 */
do_action( 'woocommerce_before_main_content' );
?>

<div id="primary" class="site-content vh360-woocommerce-page vh360-shop-archive">
    <div class="vh360-shop-archive-layout">

        <main id="main" class="content-area vh360-shop-archive-main">

            <?php
            // 1. Optional shop hero banner.
            if ( function_exists( 'vh360_shop_render_hero' ) ) {
                vh360_shop_render_hero();
            }

            // 2. Premium shop header card (replaces the plain .vh360-shop-header).
            if ( function_exists( 'vh360_shop_render_header' ) ) {
                vh360_shop_render_header();
            }

            // 3. WooCommerce notices (after header so they appear near the content).
            woocommerce_output_all_notices();

            // 4. Optional benefits / trust strip.
            if ( function_exists( 'vh360_shop_render_benefits' ) ) {
                vh360_shop_render_benefits();
            }

            // 5. Optional featured product module.
            if ( function_exists( 'vh360_shop_render_featured_product' ) ) {
                vh360_shop_render_featured_product();
            }

            // 6. Optional category navigation pills.
            if ( function_exists( 'vh360_shop_render_category_nav' ) ) {
                vh360_shop_render_category_nav();
            }
            ?>

            <?php if ( woocommerce_product_loop() ) : ?>

                <?php
                /**
                 * Hook: woocommerce_before_shop_loop.
                 * The default result-count/ordering callbacks have been removed above.
                 * Third-party plugins can still hook in here.
                 */
                do_action( 'woocommerce_before_shop_loop' );

                // 7. Enhanced toolbar with result count, search, and ordering.
                if ( function_exists( 'vh360_shop_render_toolbar' ) ) {
                    vh360_shop_render_toolbar();
                }
                ?>

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
                // 8. Branded empty / no-products state.
                if ( function_exists( 'vh360_shop_render_empty_state' ) ) {
                    vh360_shop_render_empty_state();
                } else {
                    /**
                     * Hook: woocommerce_no_products_found.
                     *
                     * @hooked wc_no_products_found - 10
                     */
                    do_action( 'woocommerce_no_products_found' );
                }
                ?>

            <?php endif; ?>

        </main><!-- #main -->

    </div><!-- .vh360-shop-archive-layout -->
</div><!-- #primary -->

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the wc content)
 */
do_action( 'woocommerce_after_main_content' );

get_footer();

