<?php
/**
 * Google Fonts Integration
 *
 * Loads selected Google Fonts based on customizer settings
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Font weights to load for Google Fonts
 */
define('VH360_GOOGLE_FONT_WEIGHTS', '300,400,500,600,700');

/**
 * Enqueue Google Fonts if selected
 */
function vh360_enqueue_google_fonts() {
    $heading_font = get_theme_mod('vh360_heading_font', 'system');
    $body_font    = get_theme_mod('vh360_body_font', 'system');
    $community_menu_font = get_theme_mod('vh360_community_menu_font_family', '');
    $header_menu_font = get_theme_mod('vh360_header_menu_font_family', '');

    // Collect unique fonts that need to be loaded
    $fonts_to_load = array();
    
    if ($heading_font !== 'system' && !in_array($heading_font, $fonts_to_load, true)) {
        $fonts_to_load[] = $heading_font;
    }
    
    if ($body_font !== 'system' && !in_array($body_font, $fonts_to_load, true)) {
        $fonts_to_load[] = $body_font;
    }
    
    // Add community menu font if set and not already in array
    if (!empty($community_menu_font) && $community_menu_font !== 'system' && !in_array($community_menu_font, $fonts_to_load, true)) {
        $fonts_to_load[] = $community_menu_font;
    }
    
    // Add header menu font if set and not already in array
    if (!empty($header_menu_font) && $header_menu_font !== 'system' && !in_array($header_menu_font, $fonts_to_load, true)) {
        $fonts_to_load[] = $header_menu_font;
    }

    // If no Google Fonts selected, return early
    if (empty($fonts_to_load)) {
        return;
    }

    // Build Google Fonts URL
    $font_families = array();
    foreach ($fonts_to_load as $font) {
        // Add font with configured weights
        $font_families[] = urlencode($font) . ':' . VH360_GOOGLE_FONT_WEIGHTS;
    }

    $fonts_url = 'https://fonts.googleapis.com/css?family=' . implode('|', $font_families) . '&display=swap';

    // Enqueue Google Fonts
    wp_enqueue_style(
        'vh360-google-fonts',
        $fonts_url,
        array(),
        null // No version for external fonts
    );
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_google_fonts', 5);

/**
 * Add preconnect for Google Fonts for better performance
 */
function vh360_add_google_fonts_preconnect() {
    $heading_font = get_theme_mod('vh360_heading_font', 'system');
    $body_font    = get_theme_mod('vh360_body_font', 'system');
    $community_menu_font = get_theme_mod('vh360_community_menu_font_family', '');
    $header_menu_font = get_theme_mod('vh360_header_menu_font_family', '');

    // Only add preconnect if using Google Fonts
    if ($heading_font !== 'system' || $body_font !== 'system' || (!empty($community_menu_font) && $community_menu_font !== 'system') || (!empty($header_menu_font) && $header_menu_font !== 'system')) {
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <?php
    }
}
add_action('wp_head', 'vh360_add_google_fonts_preconnect', 1);
