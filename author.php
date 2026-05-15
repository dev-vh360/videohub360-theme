<?php
/**
 * Author Archive Router
 * 
 * Routes to Profile (social), Channel (video), Business, Course, or Client template
 * based on account type. Enforces profile privacy settings.
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

// Enforce profile visibility privacy
$visibility = get_user_meta($author_id, '_vh360_profile_visibility', true);
if (!$visibility) {
    $visibility = 'public';
}

$current_user_id = get_current_user_id();
$is_owner = ($current_user_id === $author_id);
$is_admin = current_user_can('manage_options');

// Check privacy rules
$access_denied = false;

if ($visibility === 'private') {
    // Private: only owner or admin can view
    if (!$is_owner && !$is_admin) {
        $access_denied = true;
    }
} elseif ($visibility === 'members') {
    // Members only: must be logged in
    if (!is_user_logged_in()) {
        $access_denied = true;
    }
}

// If access denied, show restricted content
if ($access_denied) {
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

// Get display mode based on account type
$display_mode = vh360_get_author_display_mode($author_id);

// Route to appropriate template based on display mode
switch ($display_mode) {
    case 'business':
        get_template_part('author', 'business');
        break;
        
    case 'client':
        get_template_part('author', 'client');
        break;
        
    case 'course':
        get_template_part('author', 'course');
        break;
        
    case 'channel':
        get_template_part('author', 'channel');
        break;
        
    case 'profile':
    default:
        get_template_part('author', 'profile');
        break;
}

get_footer();
