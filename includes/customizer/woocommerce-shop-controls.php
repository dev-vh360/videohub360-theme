<?php
/**
 * WooCommerce Shop Customizer Controls
 *
 * Adds a dedicated "Videohub360 Shop" section under the WooCommerce Customizer panel, with a Global Design fallback when WooCommerce's panel is unavailable.
 * Controls cover the shop hero, premium header card, category navigation,
 * benefits strip, and featured product module.
 *
 * @package Videohub360_Theme
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register WooCommerce shop Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function vh360_register_woocommerce_shop_customizer_controls( $wp_customize ) {

    // ======================================
    // SECTION
    // ======================================

    // Place the section inside the native WooCommerce Customizer panel when it
    // exists (WooCommerce registers it at priority 200).  Fall back to the
    // Videohub360 Global Design panel so the section is always reachable.
    $shop_panel = $wp_customize->get_panel( 'woocommerce' ) ? 'woocommerce' : 'vh360_global_design';

    $wp_customize->add_section( 'vh360_woocommerce_shop', array(
        'title'       => __( 'Videohub360 Shop', 'videohub360-theme' ),
        'priority'    => 90,
        'panel'       => $shop_panel,
        'description' => __( 'Configure the branded Videohub360 shop storefront.', 'videohub360-theme' ),
    ) );

    // ======================================
    // SHOP HERO
    // ======================================

    // Enable hero
    $wp_customize->add_setting( 'vh360_shop_hero_enable', array(
        'default'           => false,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_enable', array(
        'label'   => __( 'Enable Shop Hero Banner', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'checkbox',
        'priority' => 10,
    ) );

    // Eyebrow
    $wp_customize->add_setting( 'vh360_shop_hero_eyebrow', array(
        'default'           => 'Official Store',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_eyebrow', array(
        'label'   => __( 'Hero Eyebrow Text', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'text',
        'priority' => 11,
    ) );

    // Title
    $wp_customize->add_setting( 'vh360_shop_hero_title', array(
        'default'           => 'Build Your Video Platform',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_title', array(
        'label'   => __( 'Hero Title', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'text',
        'priority' => 12,
    ) );

    // Description
    $wp_customize->add_setting( 'vh360_shop_hero_description', array(
        'default'           => 'Browse official products, memberships, courses, support renewals, and upgrades for your Videohub360 site.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_description', array(
        'label'   => __( 'Hero Description', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'textarea',
        'priority' => 13,
    ) );

    // Primary button text
    $wp_customize->add_setting( 'vh360_shop_hero_primary_button_text', array(
        'default'           => 'Browse Products',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_primary_button_text', array(
        'label'   => __( 'Hero Primary Button Text', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'text',
        'priority' => 14,
    ) );

    // Primary button URL
    $wp_customize->add_setting( 'vh360_shop_hero_primary_button_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_primary_button_url', array(
        'label'   => __( 'Hero Primary Button URL', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'url',
        'priority' => 15,
    ) );

    // Secondary button text
    $wp_customize->add_setting( 'vh360_shop_hero_secondary_button_text', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_secondary_button_text', array(
        'label'   => __( 'Hero Secondary Button Text', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'text',
        'priority' => 16,
    ) );

    // Secondary button URL
    $wp_customize->add_setting( 'vh360_shop_hero_secondary_button_url', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_secondary_button_url', array(
        'label'   => __( 'Hero Secondary Button URL', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'url',
        'priority' => 17,
    ) );

    // Background image
    $wp_customize->add_setting( 'vh360_shop_hero_background_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'vh360_shop_hero_background_image', array(
        'label'   => __( 'Hero Background Image', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'priority' => 18,
    ) ) );

    // Overlay opacity
    $wp_customize->add_setting( 'vh360_shop_hero_overlay_opacity', array(
        'default'           => 45,
        'sanitize_callback' => 'vh360_sanitize_shop_overlay_opacity',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_overlay_opacity', array(
        'label'       => __( 'Hero Overlay Opacity (0–90)', 'videohub360-theme' ),
        'section'     => 'vh360_woocommerce_shop',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 90, 'step' => 5 ),
        'priority'    => 19,
    ) );

    // Alignment
    $wp_customize->add_setting( 'vh360_shop_hero_alignment', array(
        'default'           => 'left',
        'sanitize_callback' => 'vh360_sanitize_shop_alignment',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_hero_alignment', array(
        'label'   => __( 'Hero Content Alignment', 'videohub360-theme' ),
        'section' => 'vh360_woocommerce_shop',
        'type'    => 'select',
        'choices' => array(
            'left'   => __( 'Left', 'videohub360-theme' ),
            'center' => __( 'Center', 'videohub360-theme' ),
        ),
        'priority' => 20,
    ) );

    // ======================================
    // SHOP HEADER CARD
    // ======================================

    // Enable header card
    $wp_customize->add_setting( 'vh360_shop_header_enable', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_header_enable', array(
        'label'    => __( 'Enable Premium Shop Header', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 30,
    ) );

    // Badge text
    $wp_customize->add_setting( 'vh360_shop_header_badge', array(
        'default'           => 'Official Videohub360 Store',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_header_badge', array(
        'label'    => __( 'Header Badge Text', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'text',
        'priority' => 31,
    ) );

    // Show archive description
    $wp_customize->add_setting( 'vh360_shop_header_show_description', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_header_show_description', array(
        'label'    => __( 'Show Archive Description', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 32,
    ) );

    // Show product count
    $wp_customize->add_setting( 'vh360_shop_header_show_product_count', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_header_show_product_count', array(
        'label'    => __( 'Show Product Count in Header', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 33,
    ) );

    // Microcopy line
    $wp_customize->add_setting( 'vh360_shop_header_microcopy', array(
        'default'           => 'Secure checkout · Instant digital access · License-connected products',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_header_microcopy', array(
        'label'    => __( 'Header Microcopy / Trust Line', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'text',
        'priority' => 34,
    ) );

    // ======================================
    // CATEGORY NAVIGATION
    // ======================================

    $wp_customize->add_setting( 'vh360_shop_category_nav_enable', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_category_nav_enable', array(
        'label'    => __( 'Enable Category Navigation Pills', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 40,
    ) );

    $wp_customize->add_setting( 'vh360_shop_category_nav_limit', array(
        'default'           => 8,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_category_nav_limit', array(
        'label'       => __( 'Category Nav: Max Categories', 'videohub360-theme' ),
        'section'     => 'vh360_woocommerce_shop',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1, 'max' => 30, 'step' => 1 ),
        'priority'    => 41,
    ) );

    $wp_customize->add_setting( 'vh360_shop_category_nav_hide_empty', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_category_nav_hide_empty', array(
        'label'    => __( 'Category Nav: Hide Empty Categories', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 42,
    ) );

    // ======================================
    // BENEFITS / TRUST STRIP
    // ======================================

    $wp_customize->add_setting( 'vh360_shop_benefits_enable', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_benefits_enable', array(
        'label'    => __( 'Enable Benefits / Trust Strip', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 50,
    ) );

    $benefits_defaults = array(
        1 => array( 'title' => 'Secure Checkout',    'text' => 'Purchase safely through WooCommerce.' ),
        2 => array( 'title' => 'Instant Access',     'text' => 'Digital products are available after purchase.' ),
        3 => array( 'title' => 'License Connected',  'text' => 'Products can support license-based access.' ),
        4 => array( 'title' => 'Customer Support',   'text' => 'Built for Videohub360 customers.' ),
    );

    $priority = 51;
    foreach ( $benefits_defaults as $n => $defaults ) {
        $wp_customize->add_setting( "vh360_shop_benefit_{$n}_title", array(
            'default'           => $defaults['title'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( "vh360_shop_benefit_{$n}_title", array(
            /* translators: %d: benefit number */
            'label'    => sprintf( __( 'Benefit %d Title', 'videohub360-theme' ), $n ),
            'section'  => 'vh360_woocommerce_shop',
            'type'     => 'text',
            'priority' => $priority++,
        ) );

        $wp_customize->add_setting( "vh360_shop_benefit_{$n}_text", array(
            'default'           => $defaults['text'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( "vh360_shop_benefit_{$n}_text", array(
            /* translators: %d: benefit number */
            'label'    => sprintf( __( 'Benefit %d Description', 'videohub360-theme' ), $n ),
            'section'  => 'vh360_woocommerce_shop',
            'type'     => 'text',
            'priority' => $priority++,
        ) );
    }

    // ======================================
    // FEATURED PRODUCT
    // ======================================

    $wp_customize->add_setting( 'vh360_shop_featured_product_enable', array(
        'default'           => false,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_featured_product_enable', array(
        'label'    => __( 'Enable Featured Product Module', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'checkbox',
        'priority' => 70,
    ) );

    $wp_customize->add_setting( 'vh360_shop_featured_product_id', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    // Build a product choices list for the select control.
    $vh360_featured_product_choices = array( 0 => __( '— Select a product —', 'videohub360-theme' ) );
    if ( function_exists( 'wc_get_products' ) ) {
        $vh360_products = wc_get_products( array(
            'status'  => 'publish',
            'limit'   => 200,
            'orderby' => 'title',
            'order'   => 'ASC',
            'return'  => 'objects',
        ) );
        foreach ( $vh360_products as $vh360_p ) {
            $vh360_featured_product_choices[ $vh360_p->get_id() ] = $vh360_p->get_name();
        }
    }

    $wp_customize->add_control( 'vh360_shop_featured_product_id', array(
        'label'    => __( 'Featured Product', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'select',
        'choices'  => $vh360_featured_product_choices,
        'priority' => 71,
    ) );

    $wp_customize->add_setting( 'vh360_shop_featured_product_badge', array(
        'default'           => 'Featured',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'vh360_shop_featured_product_badge', array(
        'label'    => __( 'Featured Product Badge Text', 'videohub360-theme' ),
        'section'  => 'vh360_woocommerce_shop',
        'type'     => 'text',
        'priority' => 72,
    ) );
}
add_action( 'customize_register', 'vh360_register_woocommerce_shop_customizer_controls', 16 );

/**
 * Sanitize hero overlay opacity (0–90).
 *
 * @param mixed $value Raw input value.
 * @return int Clamped integer.
 */
function vh360_sanitize_shop_overlay_opacity( $value ) {
    $value = absint( $value );
    return min( 90, max( 0, $value ) );
}

/**
 * Sanitize hero alignment (whitelist: left, center).
 *
 * @param string $value Raw input value.
 * @return string Sanitized value.
 */
function vh360_sanitize_shop_alignment( $value ) {
    return in_array( $value, array( 'left', 'center' ), true ) ? $value : 'left';
}
