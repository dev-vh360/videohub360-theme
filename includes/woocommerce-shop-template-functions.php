<?php
/**
 * WooCommerce Shop Template Functions
 *
 * Rendering helpers for the upgraded Videohub360 shop archive. Each function
 * checks that the required WooCommerce functions exist before calling them so
 * no fatal errors occur if WooCommerce is inactive.
 *
 * @package Videohub360_Theme
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Product card hook enhancements (archive loop)
// ---------------------------------------------------------------------------

/**
 * Render a product category badge overlaid on the product image.
 * Hooked to woocommerce_before_shop_loop_item_title at priority 11
 * (after the thumbnail renders at priority 10).
 * Only outputs on shop/category/tag/taxonomy archives and product search results.
 */
function vh360_shop_render_product_type_badge() {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    // Only output on shop archive contexts – skip single product, widgets, shortcodes, etc.
    $is_product_search = is_search() && 'product' === get_query_var( 'post_type' );
    if (
        ! is_shop()
        && ! is_product_category()
        && ! is_product_tag()
        && ! ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
        && ! $is_product_search
    ) {
        return;
    }

    global $product;
    if ( ! $product ) {
        return;
    }

    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }

    // Use the first non-uncategorized category.
    $label = '';
    foreach ( $terms as $term ) {
        if ( 'uncategorized' !== $term->slug ) {
            $label = $term->name;
            break;
        }
    }

    if ( '' === $label ) {
        return;
    }

    echo '<span class="vh360-product-card-badge">' . esc_html( $label ) . '</span>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'vh360_shop_render_product_type_badge', 11 );

/**
 * Render a short excerpt below the product title inside the loop card.
 * Hooked to woocommerce_after_shop_loop_item_title at priority 7.
 * Only outputs on shop/category/tag/taxonomy archives and product search results.
 */
function vh360_shop_render_product_excerpt() {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    // Only output on shop archive contexts – skip single product, widgets, shortcodes, etc.
    $is_product_search = is_search() && 'product' === get_query_var( 'post_type' );
    if (
        ! is_shop()
        && ! is_product_category()
        && ! is_product_tag()
        && ! ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
        && ! $is_product_search
    ) {
        return;
    }

    global $product;
    if ( ! $product ) {
        return;
    }

    // Prefer short description, fall back to excerpt.
    $text = $product->get_short_description();
    if ( '' === $text ) {
        $text = get_the_excerpt();
    }

    $text = strip_tags( $text );
    if ( '' === $text ) {
        return;
    }

    // Trim to roughly 20 words.
    $words = explode( ' ', $text );
    if ( count( $words ) > 20 ) {
        $text = implode( ' ', array_slice( $words, 0, 20 ) ) . '…';
    }

    echo '<p class="vh360-product-card-excerpt">' . esc_html( $text ) . '</p>';
}
add_action( 'woocommerce_after_shop_loop_item_title', 'vh360_shop_render_product_excerpt', 7 );

// ---------------------------------------------------------------------------
// Archive section renderers
// ---------------------------------------------------------------------------

/**
 * Render the optional hero banner section.
 */
function vh360_shop_render_hero() {
    if ( ! absint( get_theme_mod( 'vh360_shop_hero_enable', 0 ) ) ) {
        return;
    }

    $title       = get_theme_mod( 'vh360_shop_hero_title', 'Build Your Video Platform' );
    $eyebrow     = get_theme_mod( 'vh360_shop_hero_eyebrow', 'Official Store' );
    $description = get_theme_mod( 'vh360_shop_hero_description', '' );
    $btn1_text   = get_theme_mod( 'vh360_shop_hero_primary_button_text', 'Browse Products' );
    $btn1_url    = get_theme_mod( 'vh360_shop_hero_primary_button_url', '' );
    $btn2_text   = get_theme_mod( 'vh360_shop_hero_secondary_button_text', '' );
    $btn2_url    = get_theme_mod( 'vh360_shop_hero_secondary_button_url', '' );
    $bg_image    = get_theme_mod( 'vh360_shop_hero_background_image', '' );
    $opacity     = vh360_sanitize_shop_overlay_opacity( get_theme_mod( 'vh360_shop_hero_overlay_opacity', 45 ) );
    $alignment   = vh360_sanitize_shop_alignment( get_theme_mod( 'vh360_shop_hero_alignment', 'left' ) );

    $hero_style = '';
    if ( $bg_image ) {
        $hero_style = ' style="background-image: url(' . esc_url( $bg_image ) . ');"';
    }

    $overlay_style = 'style="opacity: ' . esc_attr( $opacity / 100 ) . ';"';
    $align_class   = 'vh360-shop-hero--align-' . esc_attr( $alignment );
    ?>
    <div class="vh360-shop-hero <?php echo esc_attr( $align_class ); ?>"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute value is escaped above ?>>
        <div class="vh360-shop-hero-overlay" <?php echo $overlay_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- style values are escaped above ?>></div>
        <div class="vh360-shop-hero-inner">
            <?php if ( $eyebrow ) : ?>
                <p class="vh360-shop-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
            <?php endif; ?>
            <?php if ( $title ) : ?>
                <h2 class="vh360-shop-hero-title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>
            <?php if ( $description ) : ?>
                <p class="vh360-shop-hero-description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
            <?php if ( $btn1_text || $btn2_text ) : ?>
                <div class="vh360-shop-hero-actions">
                    <?php if ( $btn1_text && $btn1_url ) : ?>
                        <a href="<?php echo esc_url( $btn1_url ); ?>" class="vh360-shop-hero-btn vh360-shop-hero-btn--primary">
                            <?php echo esc_html( $btn1_text ); ?>
                        </a>
                    <?php elseif ( $btn1_text ) : ?>
                        <a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>" class="vh360-shop-hero-btn vh360-shop-hero-btn--primary">
                            <?php echo esc_html( $btn1_text ); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( $btn2_text && $btn2_url ) : ?>
                        <a href="<?php echo esc_url( $btn2_url ); ?>" class="vh360-shop-hero-btn vh360-shop-hero-btn--secondary">
                            <?php echo esc_html( $btn2_text ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render the premium shop header card.
 */
function vh360_shop_render_header() {
    if ( ! apply_filters( 'woocommerce_show_page_title', true ) ) {
        return;
    }

    $enabled       = absint( get_theme_mod( 'vh360_shop_header_enable', 1 ) );
    $badge         = get_theme_mod( 'vh360_shop_header_badge', 'Official Videohub360 Store' );
    $show_desc     = absint( get_theme_mod( 'vh360_shop_header_show_description', 1 ) );
    $show_count    = absint( get_theme_mod( 'vh360_shop_header_show_product_count', 1 ) );
    $microcopy     = get_theme_mod( 'vh360_shop_header_microcopy', 'Secure checkout · Instant digital access · License-connected products' );

    if ( $enabled ) {
        ?>
        <div class="vh360-shop-header-card">
            <div class="vh360-shop-header-card-inner">
                <div class="vh360-shop-header-card-meta">
                    <?php if ( $badge ) : ?>
                        <span class="vh360-shop-header-badge"><?php echo esc_html( $badge ); ?></span>
                    <?php endif; ?>
                    <h1 class="vh360-shop-title"><?php woocommerce_page_title(); ?></h1>
                    <?php if ( $show_count ) : ?>
                        <p class="vh360-shop-header-count">
                            <?php echo esc_html( vh360_shop_get_product_count_text() ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $microcopy ) : ?>
                        <p class="vh360-shop-header-microcopy"><?php echo esc_html( $microcopy ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( $show_desc ) : ?>
                <div class="vh360-shop-header-description">
                    <?php do_action( 'woocommerce_archive_description' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        // Minimal fallback: render the original plain header.
        ?>
        <header class="vh360-shop-header">
            <h1 class="vh360-shop-title"><?php woocommerce_page_title(); ?></h1>
            <?php do_action( 'woocommerce_archive_description' ); ?>
        </header>
        <?php
    }
}

/**
 * Render the benefits / trust strip.
 */
function vh360_shop_render_benefits() {
    if ( ! absint( get_theme_mod( 'vh360_shop_benefits_enable', 1 ) ) ) {
        return;
    }

    $benefits = array();
    for ( $i = 1; $i <= 4; $i++ ) {
        $title = get_theme_mod( "vh360_shop_benefit_{$i}_title", '' );
        $text  = get_theme_mod( "vh360_shop_benefit_{$i}_text", '' );
        if ( $title || $text ) {
            $benefits[] = array( 'title' => $title, 'text' => $text );
        }
    }

    if ( empty( $benefits ) ) {
        return;
    }

    $icons = array(
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    );

    echo '<div class="vh360-shop-benefits">';
    foreach ( $benefits as $index => $benefit ) {
        $icon = isset( $icons[ $index ] ) ? $icons[ $index ] : '';
        echo '<div class="vh360-shop-benefit">';
        if ( $icon ) {
            echo '<span class="vh360-shop-benefit-icon">' . $icon . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup
        }
        echo '<div class="vh360-shop-benefit-content">';
        if ( $benefit['title'] ) {
            echo '<strong class="vh360-shop-benefit-title">' . esc_html( $benefit['title'] ) . '</strong>';
        }
        if ( $benefit['text'] ) {
            echo '<span class="vh360-shop-benefit-text">' . esc_html( $benefit['text'] ) . '</span>';
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Render the optional featured product module.
 */
function vh360_shop_render_featured_product() {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return;
    }

    if ( ! absint( get_theme_mod( 'vh360_shop_featured_product_enable', 0 ) ) ) {
        return;
    }

    $product_id = absint( get_theme_mod( 'vh360_shop_featured_product_id', 0 ) );
    if ( ! $product_id ) {
        return;
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_visible() || 'publish' !== get_post_status( $product_id ) ) {
        return;
    }

    $badge        = get_theme_mod( 'vh360_shop_featured_product_badge', 'Featured' );
    $permalink    = get_permalink( $product_id );
    $image_html   = $product->get_image( 'woocommerce_thumbnail' );
    $title        = $product->get_name();
    $price_html   = $product->get_price_html();
    $short_desc   = $product->get_short_description();
    if ( '' === $short_desc ) {
        $short_desc = get_the_excerpt( $product_id );
    }
    $short_desc = wp_strip_all_tags( $short_desc );

    // Trim excerpt to 24 words.
    $words = explode( ' ', $short_desc );
    if ( count( $words ) > 24 ) {
        $short_desc = implode( ' ', array_slice( $words, 0, 24 ) ) . '…';
    }
    ?>
    <div class="vh360-shop-featured-product">
        <a href="<?php echo esc_url( $permalink ); ?>" class="vh360-shop-featured-product-image">
            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce product image HTML ?>
        </a>
        <div class="vh360-shop-featured-product-details">
            <?php if ( $badge ) : ?>
                <span class="vh360-shop-featured-badge"><?php echo esc_html( $badge ); ?></span>
            <?php endif; ?>
            <h2 class="vh360-shop-featured-title">
                <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
            </h2>
            <?php if ( $short_desc ) : ?>
                <p class="vh360-shop-featured-excerpt"><?php echo esc_html( $short_desc ); ?></p>
            <?php endif; ?>
            <?php if ( $price_html ) : ?>
                <div class="vh360-shop-featured-price"><?php echo wp_kses_post( $price_html ); ?></div>
            <?php endif; ?>
            <div class="vh360-shop-featured-actions">
                <a href="<?php echo esc_url( $permalink ); ?>" class="button vh360-shop-featured-cta">
                    <?php esc_html_e( 'View Product', 'videohub360-theme' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render category navigation pills.
 */
function vh360_shop_render_category_nav() {
    if ( ! function_exists( 'is_shop' ) ) {
        return;
    }

    if ( ! absint( get_theme_mod( 'vh360_shop_category_nav_enable', 1 ) ) ) {
        return;
    }

    $limit      = absint( get_theme_mod( 'vh360_shop_category_nav_limit', 8 ) );
    $hide_empty = (bool) absint( get_theme_mod( 'vh360_shop_category_nav_hide_empty', 1 ) );

    $terms = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => $hide_empty,
        'number'     => $limit,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'exclude'    => array( get_option( 'default_product_cat', 0 ) ),
    ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }

    // Determine active term.
    $current_term = null;
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $current_term = get_queried_object();
    }

    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    ?>
    <nav class="vh360-shop-category-nav" aria-label="<?php esc_attr_e( 'Product categories', 'videohub360-theme' ); ?>">
        <a href="<?php echo esc_url( $shop_url ); ?>"
           class="vh360-shop-category-pill<?php echo ( ! $current_term ) ? ' is-active' : ''; ?>">
            <?php esc_html_e( 'All Products', 'videohub360-theme' ); ?>
        </a>
        <?php foreach ( $terms as $term ) : ?>
            <?php
            $is_active = ( $current_term && (int) $current_term->term_id === (int) $term->term_id );
            $term_url  = get_term_link( $term );
            if ( is_wp_error( $term_url ) ) {
                continue;
            }
            ?>
            <a href="<?php echo esc_url( $term_url ); ?>"
               class="vh360-shop-category-pill<?php echo $is_active ? ' is-active' : ''; ?>">
                <?php echo esc_html( $term->name ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

/**
 * Render the enhanced shop toolbar.
 */
function vh360_shop_render_toolbar() {
    ?>
    <div class="vh360-shop-toolbar">
        <div class="vh360-shop-toolbar-left">
            <?php woocommerce_result_count(); ?>
            <?php
            // Show current category label when on a product category archive.
            if ( function_exists( 'is_product_category' ) && is_product_category() ) {
                $cat = get_queried_object();
                if ( $cat && ! empty( $cat->name ) ) {
                    echo '<span class="vh360-shop-toolbar-category">' . esc_html( $cat->name ) . '</span>';
                }
            }
            ?>
        </div>
        <div class="vh360-shop-toolbar-right">
            <form role="search" method="get" class="vh360-shop-toolbar-search"
                  action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="post_type" value="product">
                <input
                    type="search"
                    name="s"
                    class="vh360-shop-toolbar-search-input"
                    placeholder="<?php esc_attr_e( 'Search products&hellip;', 'videohub360-theme' ); ?>"
                    value="<?php echo esc_attr( function_exists( 'is_search' ) && is_search() ? get_search_query() : '' ); ?>"
                    aria-label="<?php esc_attr_e( 'Search products', 'videohub360-theme' ); ?>"
                >
                <button type="submit" class="vh360-shop-toolbar-search-btn" aria-label="<?php esc_attr_e( 'Search', 'videohub360-theme' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </form>
            <?php woocommerce_catalog_ordering(); ?>
        </div>
    </div>
    <?php
}

/**
 * Render the branded empty / no-products state.
 */
function vh360_shop_render_empty_state() {
    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
    ?>
    <div class="vh360-shop-empty-state">
        <div class="vh360-shop-empty-state-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
        </div>
        <h2 class="vh360-shop-empty-title">
            <?php esc_html_e( 'No products found', 'videohub360-theme' ); ?>
        </h2>
        <p class="vh360-shop-empty-text">
            <?php esc_html_e( 'Try adjusting your filters or return to all products.', 'videohub360-theme' ); ?>
        </p>
        <a href="<?php echo esc_url( $shop_url ); ?>" class="button vh360-shop-empty-cta">
            <?php esc_html_e( 'View All Products', 'videohub360-theme' ); ?>
        </a>
        <?php do_action( 'woocommerce_no_products_found' ); ?>
        <?php if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) : ?>
            <p class="vh360-shop-empty-admin-note">
                <?php esc_html_e( 'Admin note: Add products in Products → Add New, or assign products to this category.', 'videohub360-theme' ); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Utility helpers
// ---------------------------------------------------------------------------

/**
 * Get a human-readable product count string for the current archive.
 *
 * @return string Formatted product count string.
 */
function vh360_shop_get_product_count_text() {
    if ( ! function_exists( 'wc_get_loop_prop' ) ) {
        return '';
    }

    $total = (int) wc_get_loop_prop( 'total' );
    if ( $total <= 0 ) {
        // Try a raw WP_Query count.
        global $wp_query;
        $total = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
    }

    if ( $total <= 0 ) {
        return '';
    }

    return sprintf(
        /* translators: %d: number of products */
        _n( '%d product', '%d products', $total, 'videohub360-theme' ),
        $total
    );
}

/**
 * Get the current archive description (category, tag, or main shop).
 *
 * @return string Description HTML, already sanitised via wp_kses_post.
 */
function vh360_shop_get_current_archive_description() {
    if ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $cat  = get_queried_object();
        $desc = isset( $cat->description ) ? $cat->description : '';
        return wp_kses_post( $desc );
    }

    if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
        $tag  = get_queried_object();
        $desc = isset( $tag->description ) ? $tag->description : '';
        return wp_kses_post( $desc );
    }

    // Main shop page – use post content.
    $shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
    if ( $shop_id > 0 ) {
        $post = get_post( $shop_id );
        if ( $post ) {
            return wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
        }
    }

    return '';
}
