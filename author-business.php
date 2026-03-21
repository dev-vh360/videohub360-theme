<?php
/**
 * Author Business Template (Professional/Organization)
 *
 * Displays author pages in Business mode for professionals and organizations.
 * Shows business information, services, content, and contact details.
 * 
 * Note: This file is loaded for professional/organization account types
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

// Get current tab from URL, default to 'services'
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'services';

// Define valid tabs for business profiles (contact removed - use DM system instead)
$valid_tabs = array('services', 'about', 'content');

// Validate tab
if (!in_array($current_tab, $valid_tabs, true)) {
    $current_tab = 'services';
}
?>

<div id="primary" class="site-content">
    <div class="container">
        <div class="vh360-business-profile-wrapper">
            
            <!-- Business Header Section -->
            <?php get_template_part('template-parts/business/header'); ?>

            <!-- Business Navigation -->
            <?php get_template_part('template-parts/business/navigation'); ?>

            <!-- Business Content -->
            <div class="vh360-business-content">
                
                <?php
                // Load the appropriate tab content
                switch ($current_tab) {
                    case 'services':
                        get_template_part('template-parts/business/services');
                        break;
                        
                    case 'about':
                        get_template_part('template-parts/business/about');
                        break;
                        
                    case 'content':
                        get_template_part('template-parts/business/content');
                        break;
                        
                    default:
                        get_template_part('template-parts/business/services');
                        break;
                }
                ?>
                
            </div><!-- .vh360-business-content -->
            
        </div><!-- .vh360-business-profile-wrapper -->
    </div><!-- .container -->
</div><!-- #primary -->
