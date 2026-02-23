<?php
/**
 * Business Profile Navigation
 *
 * Navigation tabs for business profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'services';
$base_url = get_author_posts_url($author_id);
?>

<nav class="vh360-business-navigation">
    <div class="container">
        <ul class="vh360-business-tabs">
            <li class="<?php echo ($current_tab === 'services') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'services', $base_url)); ?>">
                    <?php esc_html_e('Services', 'videohub360-theme'); ?>
                </a>
            </li>
            <li class="<?php echo ($current_tab === 'about') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'about', $base_url)); ?>">
                    <?php esc_html_e('About', 'videohub360-theme'); ?>
                </a>
            </li>
            <li class="<?php echo ($current_tab === 'content') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'content', $base_url)); ?>">
                    <?php esc_html_e('Content', 'videohub360-theme'); ?>
                </a>
            </li>
            <li class="<?php echo ($current_tab === 'contact') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'contact', $base_url)); ?>">
                    <?php esc_html_e('Contact', 'videohub360-theme'); ?>
                </a>
            </li>
        </ul>
    </div>
</nav>
