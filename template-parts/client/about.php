<?php
/**
 * Client Profile About Tab
 *
 * Displays about information for client profiles
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
$joined_date = get_userdata($author_id)->user_registered;
?>

<div class="vh360-client-about">
    
    <h2><?php esc_html_e('About', 'videohub360-theme'); ?></h2>
    
    <div class="vh360-client-info-section">
        <p>
            <strong><?php esc_html_e('Member since:', 'videohub360-theme'); ?></strong>
            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($joined_date))); ?>
        </p>
    </div>
    
    <?php if ($bio) : ?>
        <div class="vh360-client-bio">
            <h3><?php esc_html_e('Bio', 'videohub360-theme'); ?></h3>
            <p><?php echo esc_html($bio); ?></p>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No bio available.', 'videohub360-theme'); ?></p>
    <?php endif; ?>
    
</div>
