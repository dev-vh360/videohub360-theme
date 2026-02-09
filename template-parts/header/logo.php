<?php
/**
 * Logo Component
 *
 * Displays custom logo if set, otherwise site title and tagline.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="site-branding">
    <?php
    if (has_custom_logo()) {
        the_custom_logo();
    } else {
    ?>
        <h1 class="site-title">
            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                <?php bloginfo('name'); ?>
            </a>
        </h1>
        <?php
        $description = get_bloginfo('description', 'display');
        if ($description || is_customize_preview()) :
        ?>
            <p class="site-description"><?php echo esc_html($description); ?></p>
        <?php
        endif;
    }
    ?>
</div><!-- .site-branding -->
