<?php
/**
 * Author Client Template
 *
 * Displays author pages in Client mode for client account types.
 * Shows minimal client profile with about and activity information.
 * 
 * Note: This file is loaded for client account types
 * This is a partial template loaded by author.php - does not call header/footer
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the author being displayed
$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    get_template_part('template-parts/content', 'none');
    return;
}

// Get current tab from URL, default to 'about'
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'about';

// Define valid tabs for client profiles
$valid_tabs = array('about', 'activity');

// Validate tab
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'about';
}
?>

<div id="primary" class="site-content">
    <div class="vh360-client-page">
        
        <!-- Client Header Section -->
        <?php get_template_part('template-parts/client/header'); ?>

        <!-- Client Navigation -->
        <?php get_template_part('template-parts/client/navigation'); ?>

        <div class="container">
            <div class="vh360-client-content">
                
                <?php
                // Load the appropriate tab content
                switch ($current_tab) {
                    case 'about':
                        get_template_part('template-parts/client/about');
                        break;
                        
                    case 'activity':
                        get_template_part('template-parts/client/activity');
                        break;
                        
                    default:
                        get_template_part('template-parts/client/about');
                        break;
                }
                ?>
                
            </div><!-- .vh360-client-content -->
        </div><!-- .container -->
        
    </div><!-- .vh360-client-page -->
</div><!-- #primary -->
