<?php
/**
 * Author Archive Router
 * 
 * Routes to Profile (social) or Channel (video) template based on theme setting.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

// Get template mode
$template_mode = vh360_get_author_template_mode();

// Route to appropriate template
if ($template_mode === 'channel') {
    // Load channel template
    get_template_part('author', 'channel');
} else {
    // Load profile template (existing behavior)
    get_template_part('author', 'profile');
}

get_footer();
