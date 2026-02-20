<?php
/**
 * Business Profile Header
 *
 * Displays header section for business profiles (professional/organization)
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$author = get_userdata($author_id);

if (!$author) {
    return;
}

// Get business meta
$business_name = get_user_meta($author_id, '_vh360_business_name', true);
$business_type = get_user_meta($author_id, '_vh360_business_type', true);
$location = get_user_meta($author_id, '_vh360_location', true);

$display_name = $business_name ? $business_name : $author->display_name;
?>

<div class="vh360-business-header">
    <div class="container">
        <div class="vh360-business-header-content">
            
            <div class="vh360-business-avatar">
                <?php echo get_avatar($author_id, 150); ?>
            </div>
            
            <div class="vh360-business-info">
                <h1 class="vh360-business-name"><?php echo esc_html($display_name); ?></h1>
                
                <?php if ($business_type) : ?>
                    <p class="vh360-business-type"><?php echo esc_html($business_type); ?></p>
                <?php endif; ?>
                
                <?php if ($location) : ?>
                    <p class="vh360-business-location">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>
