<?php
/**
 * The header template
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="vh360-pwa-app-shell">
    <div class="vh360-pwa-app-scroll" data-vh360-pwa-scroll>

<?php 
// Show urgent bulletin banner
$urgent_bulletins = vh360_get_urgent_bulletins();
if (!empty($urgent_bulletins)) :
    get_template_part('template-parts/bulletin/banner');
endif;
?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'videohub360-theme'); ?></a>

    <?php get_template_part('template-parts/header/header-layout'); ?>

    <div class="site-layout-wrapper">
        <?php get_template_part('template-parts/navigation/community-menu'); ?>

        <div id="content" class="site-content">
