/**
 * Customizer Live Preview
 *
 * Handles real-time preview updates in the WordPress Customizer
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

(function($) {
    'use strict';

    // Helper function to update CSS variable
    function updateCSSVariable(variable, value) {
        document.documentElement.style.setProperty('--' + variable, value);
    }

    // Color Controls
    wp.customize('vh360_primary_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('primary-color', newval);
        });
    });

    wp.customize('vh360_secondary_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('secondary-color', newval);
        });
    });

    wp.customize('vh360_header_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('header-bg-color', newval);
        });
    });

    wp.customize('vh360_header_bg_color_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('header-bg-end', newval);
        });
    });

    wp.customize('vh360_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('text-color', newval);
        });
    });

    wp.customize('vh360_text_light_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('text-light', newval);
        });
    });

    wp.customize('vh360_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('bg-color', newval);
        });
    });

    wp.customize('vh360_bg_light_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('bg-light', newval);
        });
    });

    wp.customize('vh360_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('border-color', newval);
        });
    });

    wp.customize('vh360_success_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('success-color', newval);
        });
    });

    wp.customize('vh360_error_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('error-color', newval);
        });
    });

    wp.customize('vh360_warning_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('warning-color', newval);
        });
    });

    wp.customize('vh360_info_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('info-color', newval);
        });
    });

    // Accent Color
    wp.customize('vh360_accent_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('accent-color', newval);
        });
    });

    // Navigation & Header Controls
    wp.customize('vh360_hamburger_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('hamburger-bg-color', newval);
        });
    });

    wp.customize('vh360_hamburger_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('hamburger-text-color', newval);
        });
    });

    wp.customize('vh360_hamburger_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('hamburger-hover-bg-color', newval);
        });
    });

    wp.customize('vh360_hamburger_active_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('hamburger-active-color', newval);
        });
    });

    wp.customize('vh360_hamburger_icon_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('hamburger-icon-color', newval);
        });
    });

    wp.customize('vh360_nav_link_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('nav-link-color', newval);
        });
    });

    // Button Controls
    wp.customize('vh360_button_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('button-bg-color', newval);
        });
    });

    wp.customize('vh360_button_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('button-text-color', newval);
        });
    });

    wp.customize('vh360_button_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('button-hover-bg-color', newval);
        });
    });

    wp.customize('vh360_button_hover_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('button-hover-text-color', newval);
        });
    });

    // Typography Controls
    wp.customize('vh360_font_size', function(value) {
        value.bind(function(newval) {
            $('html').css('font-size', newval + 'px');
        });
    });

    wp.customize('vh360_line_height', function(value) {
        value.bind(function(newval) {
            $('body').css('line-height', newval);
        });
    });

    wp.customize('vh360_heading_font', function(value) {
        value.bind(function(newval) {
            var fontFamily = getFontFamily(newval);
            $('h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6').css('font-family', fontFamily);
        });
    });

    wp.customize('vh360_body_font', function(value) {
        value.bind(function(newval) {
            var fontFamily = getFontFamily(newval);
            $('body').css('font-family', fontFamily);
        });
    });

    // Helper function to get font family string
    function getFontFamily(font) {
        var systemFonts = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif";
        
        if (font === 'system') {
            return systemFonts;
        }
        
        return "'" + font + "', " + systemFonts;
    }

    // Login Page Content
    wp.customize('vh360_login_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-heading').text(newval);
        });
    });

    wp.customize('vh360_login_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-description').text(newval);
        });
    });

    wp.customize('vh360_login_feature_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_login_feature_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_login_feature_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-text').text(newval);
        });
    });

    // Register Page Content
    wp.customize('vh360_register_headline', function(value) {
        value.bind(function(newval) {
            var siteName = wp.customize('blogname')();
            var headline = newval.replace('{site_name}', siteName);
            $('.vh360-auth-heading').text(headline);
        });
    });

    // Update register headline when site name changes
    wp.customize('blogname', function(value) {
        value.bind(function(newval) {
            var headline = wp.customize('vh360_register_headline')();
            if (headline && headline.indexOf('{site_name}') !== -1) {
                var updatedHeadline = headline.replace('{site_name}', newval);
                $('.vh360-auth-heading').text(updatedHeadline);
            }
        });
    });

    wp.customize('vh360_register_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-description').text(newval);
        });
    });

    wp.customize('vh360_register_benefit_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-benefits-list li:eq(0)').html('<span class="vh360-auth-benefit-icon">✓</span>' + newval);
        });
    });

    wp.customize('vh360_register_benefit_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-benefits-list li:eq(1)').html('<span class="vh360-auth-benefit-icon">✓</span>' + newval);
        });
    });

    wp.customize('vh360_register_benefit_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-benefits-list li:eq(2)').html('<span class="vh360-auth-benefit-icon">✓</span>' + newval);
        });
    });

    wp.customize('vh360_register_benefit_4', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-benefits-list li:eq(3)').html('<span class="vh360-auth-benefit-icon">✓</span>' + newval);
        });
    });

    // Lost Password Page Content Controls
    wp.customize('vh360_lost_password_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-heading').text(newval);
        });
    });

    wp.customize('vh360_lost_password_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-description').text(newval);
        });
    });

    wp.customize('vh360_lost_password_feature_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_lost_password_feature_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_lost_password_feature_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_lost_password_icon_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-icon').text(newval);
        });
    });

    wp.customize('vh360_lost_password_icon_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-icon').text(newval);
        });
    });

    wp.customize('vh360_lost_password_icon_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-icon').text(newval);
        });
    });

    // Lost Password Page Design Controls (CSS Variables)
    wp.customize('vh360_lost_password_page_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-page-bg', newval);
        });
    });

    wp.customize('vh360_lost_password_form_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-form-bg', newval);
        });
    });

    wp.customize('vh360_lost_password_welcome_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-welcome-bg-start', newval);
        });
    });

    wp.customize('vh360_lost_password_welcome_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-welcome-bg-end', newval);
        });
    });

    wp.customize('vh360_lost_password_welcome_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-welcome-text', newval);
        });
    });

    wp.customize('vh360_lost_password_welcome_heading_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-welcome-heading', newval);
        });
    });

    wp.customize('vh360_lost_password_welcome_description_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-welcome-description', newval);
        });
    });

    wp.customize('vh360_lost_password_form_title_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-form-title', newval);
        });
    });

    wp.customize('vh360_lost_password_label_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-label', newval);
        });
    });

    wp.customize('vh360_lost_password_input_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-input-border', newval);
        });
    });

    wp.customize('vh360_lost_password_input_focus_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-input-focus-border', newval);
        });
    });

    wp.customize('vh360_lost_password_input_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-input-text', newval);
        });
    });

    wp.customize('vh360_lost_password_input_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-input-bg', newval);
        });
    });

    wp.customize('vh360_lost_password_button_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-button-bg-start', newval);
        });
    });

    wp.customize('vh360_lost_password_button_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-button-bg-end', newval);
        });
    });

    wp.customize('vh360_lost_password_button_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-button-text', newval);
        });
    });

    wp.customize('vh360_lost_password_link_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-link', newval);
        });
    });

    wp.customize('vh360_lost_password_link_hover_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-link-hover', newval);
        });
    });

    wp.customize('vh360_lost_password_error_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-error-bg', newval);
        });
    });

    wp.customize('vh360_lost_password_error_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-error-text', newval);
        });
    });

    wp.customize('vh360_lost_password_error_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-error-border', newval);
        });
    });

    wp.customize('vh360_lost_password_success_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-success-bg', newval);
        });
    });

    wp.customize('vh360_lost_password_success_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-success-text', newval);
        });
    });

    wp.customize('vh360_lost_password_success_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-success-border', newval);
        });
    });

    wp.customize('vh360_lost_password_secondary_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-secondary-text', newval);
        });
    });

    wp.customize('vh360_lost_password_required_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('lost-password-required', newval);
        });
    });

    // Reset Password Page Content Controls
    wp.customize('vh360_reset_password_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-heading').text(newval);
        });
    });

    wp.customize('vh360_reset_password_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-description').text(newval);
        });
    });

    wp.customize('vh360_reset_password_feature_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_reset_password_feature_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_reset_password_feature_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-text').text(newval);
        });
    });

    wp.customize('vh360_reset_password_icon_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-icon').text(newval);
        });
    });

    wp.customize('vh360_reset_password_icon_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-icon').text(newval);
        });
    });

    wp.customize('vh360_reset_password_icon_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-icon').text(newval);
        });
    });

    // Reset Password Page Design Controls (CSS Variables)
    wp.customize('vh360_reset_password_page_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-page-bg', newval);
        });
    });

    wp.customize('vh360_reset_password_form_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-form-bg', newval);
        });
    });

    wp.customize('vh360_reset_password_welcome_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-welcome-bg-start', newval);
        });
    });

    wp.customize('vh360_reset_password_welcome_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-welcome-bg-end', newval);
        });
    });

    wp.customize('vh360_reset_password_welcome_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-welcome-text', newval);
        });
    });

    wp.customize('vh360_reset_password_welcome_heading_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-welcome-heading', newval);
        });
    });

    wp.customize('vh360_reset_password_welcome_description_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-welcome-description', newval);
        });
    });

    wp.customize('vh360_reset_password_form_title_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-form-title', newval);
        });
    });

    wp.customize('vh360_reset_password_label_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-label', newval);
        });
    });

    wp.customize('vh360_reset_password_input_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-input-border', newval);
        });
    });

    wp.customize('vh360_reset_password_input_focus_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-input-focus-border', newval);
        });
    });

    wp.customize('vh360_reset_password_input_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-input-text', newval);
        });
    });

    wp.customize('vh360_reset_password_input_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-input-bg', newval);
        });
    });

    wp.customize('vh360_reset_password_button_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-button-bg-start', newval);
        });
    });

    wp.customize('vh360_reset_password_button_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-button-bg-end', newval);
        });
    });

    wp.customize('vh360_reset_password_button_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-button-text', newval);
        });
    });

    wp.customize('vh360_reset_password_link_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-link', newval);
        });
    });

    wp.customize('vh360_reset_password_link_hover_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-link-hover', newval);
        });
    });

    wp.customize('vh360_reset_password_error_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-error-bg', newval);
        });
    });

    wp.customize('vh360_reset_password_error_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-error-text', newval);
        });
    });

    wp.customize('vh360_reset_password_error_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-error-border', newval);
        });
    });

    wp.customize('vh360_reset_password_success_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-success-bg', newval);
        });
    });

    wp.customize('vh360_reset_password_success_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-success-text', newval);
        });
    });

    wp.customize('vh360_reset_password_success_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-success-border', newval);
        });
    });

    wp.customize('vh360_reset_password_secondary_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-secondary-text', newval);
        });
    });

    wp.customize('vh360_reset_password_required_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('reset-password-required', newval);
        });
    });

    // Footer Color Controls
    wp.customize('vh360_footer_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('footer-bg-color', newval);
        });
    });

    wp.customize('vh360_footer_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('footer-text-color', newval);
        });
    });

    wp.customize('vh360_footer_link_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('footer-link-color', newval);
        });
    });

    wp.customize('vh360_footer_link_hover_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('footer-link-hover-color', newval);
        });
    });

    // Footer Copyright Text
    wp.customize('vh360_footer_copyright_text', function(value) {
        value.bind(function(newval) {
            var processed = processFooterPlaceholders(newval);
            $('.footer-copyright').html(processed);
        });
    });

    // Update copyright when site name changes
    wp.customize('blogname', function(value) {
        value.bind(function(newval) {
            var copyrightText = wp.customize('vh360_footer_copyright_text')();
            if (copyrightText && copyrightText.indexOf('{site_name}') !== -1) {
                var processed = processFooterPlaceholders(copyrightText);
                $('.footer-copyright').html(processed);
            }
        });
    });

    // Footer Powered By Toggle
    wp.customize('vh360_footer_show_powered_by', function(value) {
        value.bind(function(newval) {
            if (newval) {
                $('.footer-powered-by').show();
            } else {
                $('.footer-powered-by').hide();
            }
        });
    });

    // Footer Powered By Text
    wp.customize('vh360_footer_powered_by_text', function(value) {
        value.bind(function(newval) {
            var processed = processFooterPlaceholders(newval);
            $('.footer-powered-by').html(processed);
        });
    });

    /**
     * Process footer text placeholders
     * 
     * @param {string} text Text with placeholders
     * @return {string} Processed text
     */
    function processFooterPlaceholders(text) {
        var year = new Date().getFullYear();
        var siteName = wp.customize('blogname')();
        var wordpressLink = '<a href="https://wordpress.org/">WordPress</a>';
        var videohub360Link = '<a href="https://videohub360.com">VideoHub360</a>';
        
        text = text.replace('{year}', year);
        text = text.replace('{site_name}', siteName);
        text = text.replace('{wordpress}', wordpressLink);
        text = text.replace('{videohub360}', videohub360Link);
        
        return text;
    }

})(jQuery);
