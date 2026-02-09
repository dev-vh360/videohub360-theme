<?php
/**
 * Admin Page Footer
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

    </div><!-- .vh360-admin-content -->
    
    <div class="vh360-admin-footer">
        <p>
            <?php
            printf(
                /* translators: %s: Theme version */
                esc_html__('Videohub360 Theme v%s', 'videohub360-theme'),
                esc_html(VH360_THEME_VERSION)
            );
            ?>
            |
            <a href="https://videohub360.com/docs" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Documentation', 'videohub360-theme'); ?>
            </a>
            |
            <a href="https://videohub360.com/support" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('Support', 'videohub360-theme'); ?>
            </a>
        </p>
    </div>
    
</div><!-- .wrap -->
