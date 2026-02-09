<?php
/**
 * Stats Card Partial
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Required variables: $icon, $label, $value, $status (optional)
$icon = isset($icon) ? $icon : 'dashicons-chart-area';
$label = isset($label) ? $label : '';
$value = isset($value) ? $value : '0';
$status = isset($status) ? $status : 'default';
$link = isset($link) ? $link : '';
?>

<div class="vh360-stats-card vh360-stats-<?php echo esc_attr($status); ?>">
    <?php if ($link) : ?>
        <a href="<?php echo esc_url($link); ?>" class="vh360-stats-link">
    <?php endif; ?>
    
    <div class="vh360-stats-icon">
        <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
    </div>
    <div class="vh360-stats-content">
        <div class="vh360-stats-value"><?php echo esc_html($value); ?></div>
        <div class="vh360-stats-label"><?php echo esc_html($label); ?></div>
    </div>
    
    <?php if ($link) : ?>
        </a>
    <?php endif; ?>
</div>
