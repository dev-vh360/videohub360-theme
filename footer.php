<?php
/**
 * The footer template
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Skip footer rendering on auth pages if setting is enabled
if (function_exists('vh360_is_auth_page') && vh360_is_auth_page() && get_theme_mod('vh360_hide_footer_on_auth_pages', 1)) {
    // Close the open divs before returning
    echo '</div><!-- #content -->';
    echo '</div><!-- .site-layout-wrapper -->';
    echo '</div><!-- #page -->';
    echo '</div><!-- .vh360-pwa-app-scroll -->';
    if (is_user_logged_in()) {
        get_template_part('template-parts/navigation/mobile-bottom-nav');
        get_template_part('template-parts/navigation/mobile-user-drawer');
    }
    echo '</div><!-- .vh360-pwa-app-shell -->';
    wp_footer(); // Required for WordPress and plugins to enqueue scripts/styles
    echo '</body></html>';
    return;
}
?>

    </div><!-- #content -->

    </div><!-- .site-layout-wrapper -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            
            <?php if (is_active_sidebar('footer-1') || is_active_sidebar('footer-2') || is_active_sidebar('footer-3') || is_active_sidebar('footer-4')) : ?>
                <div class="footer-widgets">
                    <div class="footer-widget-area">
                        <?php if (is_active_sidebar('footer-1')) : ?>
                            <div class="footer-widget">
                                <?php dynamic_sidebar('footer-1'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (is_active_sidebar('footer-2')) : ?>
                            <div class="footer-widget">
                                <?php dynamic_sidebar('footer-2'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (is_active_sidebar('footer-3')) : ?>
                            <div class="footer-widget">
                                <?php dynamic_sidebar('footer-3'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (is_active_sidebar('footer-4')) : ?>
                            <div class="footer-widget">
                                <?php dynamic_sidebar('footer-4'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="site-info">
                <?php
                // Footer menu
                if (has_nav_menu('footer')) {
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_id'        => 'footer-menu',
                        'container'      => 'nav',
                        'container_class' => 'footer-navigation',
                        'depth'          => 1,
                    ));
                }
                
                // Get footer copyright text
                $copyright_text = get_theme_mod('vh360_footer_copyright_text', '© {year} {site_name}. All rights reserved.');
                $processed_copyright = vh360_process_footer_placeholders($copyright_text);
                ?>
                
                <p class="footer-copyright">
                    <?php echo wp_kses_post($processed_copyright); ?>
                </p>
                
                <?php 
                // Show developed by section if enabled
                if (get_theme_mod('vh360_footer_show_powered_by', 1)) :
                    $powered_by_text = get_theme_mod('vh360_footer_powered_by_text', 'Developed by {videohub360}');
                    $processed_powered_by = vh360_process_footer_placeholders($powered_by_text);
                ?>
                <p class="footer-powered-by">
                    <?php echo wp_kses_post($processed_powered_by); ?>
                </p>
                <?php endif; ?>
            </div><!-- .site-info -->
        </div><!-- .container -->
    </footer><!-- #colophon -->

</div><!-- #page -->

</div><!-- .vh360-pwa-app-scroll -->

<?php if ( is_user_logged_in() ) : ?>
    <?php get_template_part( 'template-parts/navigation/mobile-bottom-nav' ); ?>
    <?php get_template_part( 'template-parts/navigation/mobile-user-drawer' ); ?>
<?php endif; ?>

</div><!-- .vh360-pwa-app-shell -->

<?php wp_footer(); ?>

</body>
</html>
