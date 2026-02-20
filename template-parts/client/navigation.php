<?php
/**
 * Client Profile Navigation
 *
 * Navigation tabs for client profiles
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$author_id = get_queried_object_id();
$current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'about';
$base_url = get_author_posts_url($author_id);
?>

<nav class="vh360-client-navigation">
    <div class="container">
        <ul class="vh360-client-tabs">
            <li class="<?php echo ($current_tab === 'about') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'about', $base_url)); ?>">
                    <?php esc_html_e('About', 'videohub360-theme'); ?>
                </a>
            </li>
            <li class="<?php echo ($current_tab === 'activity') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(add_query_arg('tab', 'activity', $base_url)); ?>">
                    <?php esc_html_e('Activity', 'videohub360-theme'); ?>
                </a>
            </li>
        </ul>
    </div>
</nav>
