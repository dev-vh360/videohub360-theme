<?php
/**
 * Business Profile About Tab
 *
 * Displays about information for business profiles
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

$bio = get_user_meta($author_id, 'description', true);
$business_name = get_user_meta($author_id, '_vh360_business_name', true);
?>

<div class="vh360-business-about">
    
    <h2><?php esc_html_e('About', 'videohub360-theme'); ?></h2>
    
    <?php if ($business_name) : ?>
        <div class="vh360-business-name-section">
            <h3><?php echo esc_html($business_name); ?></h3>
        </div>
    <?php endif; ?>
    
    <?php if ($bio) : ?>
        <div class="vh360-business-bio">
            <p><?php echo esc_html($bio); ?></p>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No information available.', 'videohub360-theme'); ?></p>
    <?php endif; ?>
    
</div>
