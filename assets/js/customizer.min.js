/**
 * Theme Customizer enhancements for live preview
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Site title
    wp.customize('blogname', function(value) {
        value.bind(function(to) {
            $('.site-title a').text(to);
        });
    });

    // Site description
    wp.customize('blogdescription', function(value) {
        value.bind(function(to) {
            $('.site-description').text(to);
        });
    });

    // Container width
    wp.customize('videohub360_theme_container_width', function(value) {
        value.bind(function(to) {
            $(':root').css('--max-width', to + 'px');
        });
    });

})(jQuery);
