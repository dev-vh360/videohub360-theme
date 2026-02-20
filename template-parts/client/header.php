<?php
/**
 * Client Profile Header
 *
 * Displays header section for client profiles
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
?>

<div class="vh360-client-header">
    <div class="container">
        <div class="vh360-client-header-content">
            
            <div class="vh360-client-avatar">
                <?php echo get_avatar($author_id, 120); ?>
            </div>
            
            <div class="vh360-client-info">
                <h1 class="vh360-client-name"><?php echo esc_html($author->display_name); ?></h1>
                <p class="vh360-client-type"><?php esc_html_e('Client', 'videohub360-theme'); ?></p>
            </div>
            
        </div>
    </div>
</div>
