<?php
/**
 * Template part for displaying single videohub360 posts
 * 
 * This is a lightweight wrapper that allows the Videohub360 plugin
 * to use its own template while still maintaining theme structure
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// The Videohub360 plugin provides its own single template
// We'll just output basic structure here and let the plugin's template handle the rest
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('videohub360-single'); ?>>
    <?php
    // Let the Videohub360 plugin handle all the video display logic
    the_content();
    ?>
</article><!-- #post-<?php the_ID(); ?> -->
