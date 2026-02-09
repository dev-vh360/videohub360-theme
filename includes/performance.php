<?php
/**
 * Performance Optimizations
 *
 * This file contains all performance optimization functions for the theme.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Remove emoji scripts for better performance
 */
function vh360_remove_emoji_scripts() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    // Remove TinyMCE emoji plugin
    add_filter('tiny_mce_plugins', 'vh360_disable_emoji_tinymce');
    
    // Remove emoji DNS prefetch
    add_filter('wp_resource_hints', 'vh360_disable_emoji_dns_prefetch', 10, 2);
}
add_action('init', 'vh360_remove_emoji_scripts');

/**
 * Disable emoji plugin from TinyMCE
 *
 * @param array $plugins List of TinyMCE plugins.
 * @return array Modified list of TinyMCE plugins.
 */
function vh360_disable_emoji_tinymce($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return array();
}

/**
 * Remove emoji DNS prefetch
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Modified list of URLs.
 */
function vh360_disable_emoji_dns_prefetch($urls, $relation_type) {
    if ('dns-prefetch' === $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}

/**
 * Remove WordPress version from head for security
 */
function vh360_remove_wp_version() {
    remove_action('wp_head', 'wp_generator');
    add_filter('the_generator', '__return_empty_string');
}
add_action('init', 'vh360_remove_wp_version');

/**
 * Defer non-critical scripts for faster page load
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @param string $src    The script source URL.
 * @return string Modified script tag.
 */
function vh360_defer_scripts($tag, $handle, $src) {
    // List of scripts to defer
    $defer_scripts = array(
        'videohub360-theme-script',
        'dashboard-script',
        'gallery-script',
    );
    
    // Don't defer jQuery or admin scripts
    if (is_admin() || in_array($handle, array('jquery', 'jquery-core', 'jquery-migrate'))) {
        return $tag;
    }
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'vh360_defer_scripts', 10, 3);

/**
 * Preload critical resources
 */
function vh360_preload_resources() {
    // Preload critical CSS
    echo '<link rel="preload" href="' . esc_url(get_stylesheet_uri()) . '" as="style" />' . "\n";
    
    // Preload theme script
    echo '<link rel="preload" href="' . esc_url(VH360_THEME_URI . '/assets/js/theme.js') . '" as="script" />' . "\n";
}
add_action('wp_head', 'vh360_preload_resources', 1);

/**
 * Add lazy loading attribute to images
 *
 * @param array $attr Array of image attributes.
 * @return array Modified array of image attributes.
 */
function vh360_add_lazy_loading($attr) {
    // Don't add lazy loading to images in the admin or logo
    if (is_admin() || (isset($attr['class']) && strpos($attr['class'], 'custom-logo') !== false)) {
        return $attr;
    }
    
    // Add loading="lazy" attribute
    $attr['loading'] = 'lazy';
    
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'vh360_add_lazy_loading', 10, 1);

/**
 * Remove query strings from static resources
 *
 * @param string $src The source URL.
 * @return string The source URL without query string.
 */
function vh360_remove_query_strings($src) {
    // Only remove query strings from our theme assets
    if (strpos($src, VH360_THEME_URI) !== false) {
        $parts = explode('?', $src);
        return $parts[0];
    }
    return $src;
}
add_filter('style_loader_src', 'vh360_remove_query_strings', 10, 1);
add_filter('script_loader_src', 'vh360_remove_query_strings', 10, 1);

/**
 * Disable embed scripts if not needed
 */
function vh360_disable_embeds() {
    // Only disable on archive pages and front page where embeds are unlikely
    if (is_archive() || is_home() || is_search()) {
        wp_deregister_script('wp-embed');
    }
}
add_action('wp_footer', 'vh360_disable_embeds');

/**
 * Optimize WP head
 */
function vh360_optimize_wp_head() {
    // Remove unnecessary head links
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    
    // Remove REST API links if not needed (can be re-enabled if needed)
    if (!is_user_logged_in()) {
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    }
}
add_action('init', 'vh360_optimize_wp_head');
