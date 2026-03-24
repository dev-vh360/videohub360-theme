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

    // =============================================================================
    // CONFIGURATION
    // =============================================================================

    /**
     * Font configuration mapping
     * Maps font slugs to both Google Fonts API names and CSS font-family values
     */
    var FONT_CONFIG = {
        'system': {
            googleFont: null,
            cssFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
        },
        'roboto': {
            googleFont: 'Roboto:400,500,600,700',
            cssFamily: '"Roboto", sans-serif'
        },
        'open-sans': {
            googleFont: 'Open+Sans:400,500,600,700',
            cssFamily: '"Open Sans", sans-serif'
        },
        'lato': {
            googleFont: 'Lato:400,500,600,700',
            cssFamily: '"Lato", sans-serif'
        },
        'montserrat': {
            googleFont: 'Montserrat:400,500,600,700',
            cssFamily: '"Montserrat", sans-serif'
        },
        'poppins': {
            googleFont: 'Poppins:400,500,600,700',
            cssFamily: '"Poppins", sans-serif'
        },
        'raleway': {
            googleFont: 'Raleway:400,500,600,700',
            cssFamily: '"Raleway", sans-serif'
        },
        'ubuntu': {
            googleFont: 'Ubuntu:400,500,600,700',
            cssFamily: '"Ubuntu", sans-serif'
        },
        'nunito': {
            googleFont: 'Nunito:400,500,600,700',
            cssFamily: '"Nunito", sans-serif'
        },
        'playfair-display': {
            googleFont: 'Playfair+Display:400,500,600,700',
            cssFamily: '"Playfair Display", serif'
        },
        'merriweather': {
            googleFont: 'Merriweather:400,700',
            cssFamily: '"Merriweather", serif'
        }
    };

    // =============================================================================
    // HELPER FUNCTIONS
    // =============================================================================

    // Helper function to update CSS variable
    function updateCSSVariable(variable, value) {
        document.documentElement.style.setProperty('--' + variable, value);
    }

    /**
     * Helper function to update benefit text while preserving icon
     * 
     * Updates the text content of a benefit list item while preserving
     * the icon span element that precedes the text.
     * 
     * @param {string} selector jQuery selector for the list item
     * @param {string} newText New text content to set
     */
    function updateBenefitText(selector, newText) {
        var $benefit = $(selector);
        if ($benefit.length) {
            // Find all text nodes and replace the first one (after the icon)
            var textNodes = $benefit.contents().filter(function() {
                return this.nodeType === 3; // Text node
            });
            if (textNodes.length > 0) {
                // Replace text node with new text node
                textNodes.first().replaceWith(document.createTextNode(newText));
            }
        }
    }

    /**
     * Helper function to get the client card element
     * 
     * Finds the client card by its secondary button class, with a fallback
     * to the last card if the secondary button is not found (single-card mode).
     * 
     * @return {jQuery} jQuery object containing the client card
     */
    function getClientCard() {
        var $clientCard = $('.vh360-business-choice-card:has(.vh360-button-secondary)');
        if ($clientCard.length === 0) {
            // Fallback: if no secondary button found, use last card
            $clientCard = $('.vh360-business-choice-card').last();
        }
        return $clientCard;
    }

    // =============================================================================
    // COLOR CONTROLS
    // =============================================================================

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

    // =============================================================================
    // COMMUNITY MENU BINDINGS
    // =============================================================================
    
    // Community Menu - Background Color
    wp.customize('vh360_community_menu_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-bg-color', newval);
        });
    });

    // Community Menu - Text Color
    wp.customize('vh360_community_menu_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-text-color', newval);
        });
    });

    // Community Menu - Hover Background Color
    wp.customize('vh360_community_menu_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-hover-bg-color', newval);
        });
    });

    // Community Menu - Active Color
    wp.customize('vh360_community_menu_active_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-active-color', newval);
        });
    });

    // Community Menu - Active Background Color
    wp.customize('vh360_community_menu_active_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-active-bg-color', newval);
        });
    });

    // Community Menu - Font Family
    wp.customize('vh360_community_menu_font_family', function(value) {
        value.bind(function(newval) {
            if (newval && newval !== '') {
                // Load Google Font if needed
                loadGoogleFont(newval);
                // Get the font family CSS value
                var fontFamily = getFontFamilyCSS(newval);
                updateCSSVariable('community-menu-font-family', fontFamily);
            } else {
                // Remove custom font family to inherit from body
                document.documentElement.style.removeProperty('--community-menu-font-family');
            }
        });
    });

    // Community Menu - Font Size
    wp.customize('vh360_community_menu_font_size', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-font-size', newval + 'px');
        });
    });

    // Community Menu - Font Weight
    wp.customize('vh360_community_menu_font_weight', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-font-weight', newval);
        });
    });

    // Community Menu - Left Gutter
    wp.customize('vh360_community_menu_left_gutter', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-left-gutter', newval + 'px');
        });
    });

    // Community Menu - Width
    wp.customize('vh360_community_menu_width', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('community-menu-width', newval + 'px');
        });
    });

    // =============================================================================
    // ACTIVITY FEED BINDINGS
    // =============================================================================

    // Activity Feed - Tab Color
    wp.customize('vh360_feed_tab_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('feed-tab-color', newval);
        });
    });

    // Activity Feed - Tab Hover Color
    wp.customize('vh360_feed_tab_hover_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('feed-tab-hover-color', newval);
        });
    });

    // Activity Feed - Mention Color
    wp.customize('vh360_mention_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('mention-color', newval);
        });
    });

    // =============================================================================
    // AUTH PAGES BINDINGS (Consolidated for Login, Register, Lost Password, Reset)
    // =============================================================================

    // Auth Pages - Page Background Color
    wp.customize('vh360_auth_page_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-page-bg', newval);
        });
    });

    // Auth Pages - Form Background Color
    wp.customize('vh360_auth_form_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-form-bg', newval);
        });
    });

    // Auth Pages - Welcome Background Gradient Start
    wp.customize('vh360_auth_welcome_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-welcome-bg-start', newval);
        });
    });

    // Auth Pages - Welcome Background Gradient End
    wp.customize('vh360_auth_welcome_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-welcome-bg-end', newval);
        });
    });

    // Auth Pages - Welcome Text Color
    wp.customize('vh360_auth_welcome_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-welcome-text', newval);
        });
    });

    // Auth Pages - Form Title Color
    wp.customize('vh360_auth_form_title_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-form-title', newval);
        });
    });

    // Auth Pages - Label Color
    wp.customize('vh360_auth_label_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-label', newval);
        });
    });

    // Auth Pages - Input Border Color
    wp.customize('vh360_auth_input_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-input-border', newval);
        });
    });

    // Auth Pages - Input Focus Border Color
    wp.customize('vh360_auth_input_focus_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-input-focus-border', newval);
        });
    });

    // Auth Pages - Button Background Gradient Start
    wp.customize('vh360_auth_button_bg_start', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-button-bg-start', newval);
        });
    });

    // Auth Pages - Button Background Gradient End
    wp.customize('vh360_auth_button_bg_end', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-button-bg-end', newval);
        });
    });

    // Auth Pages - Button Text Color
    wp.customize('vh360_auth_button_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-button-text', newval);
        });
    });

    // Auth Pages - Link Color
    wp.customize('vh360_auth_link_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-link', newval);
        });
    });

    // Auth Pages - Error Background Color
    wp.customize('vh360_auth_error_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-error-bg', newval);
        });
    });

    // Auth Pages - Error Text Color
    wp.customize('vh360_auth_error_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-error-text', newval);
        });
    });

    // Auth Pages - Success Background Color
    wp.customize('vh360_auth_success_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-success-bg', newval);
        });
    });

    // =============================================================================
    // HEADER CONTROLS BINDINGS (Page Headers)
    // =============================================================================

    // Activity Page Header - Title
    wp.customize('vh360_activity_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-activity-header .vh360-page-header__title').text(newval);
        });
    });

    // Activity Page Header - Description
    wp.customize('vh360_activity_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-activity-header .vh360-page-header__description').text(newval);
        });
    });

    // Members Directory Header - Title
    wp.customize('vh360_members_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-members-header .vh360-page-header__title').text(newval);
        });
    });

    // Members Directory Header - Description
    wp.customize('vh360_members_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-members-header .vh360-page-header__description').text(newval);
        });
    });

    // Bulletins Archive Header - Title
    wp.customize('vh360_bulletins_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-bulletins-header .vh360-page-header__title').text(newval);
        });
    });

    // Bulletins Archive Header - Description
    wp.customize('vh360_bulletins_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-bulletins-header .vh360-page-header__description').text(newval);
        });
    });

    // Blog Archive Header - Title
    wp.customize('vh360_blog_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-blog-header .vh360-page-header__title').text(newval);
        });
    });

    // Blog Archive Header - Description
    wp.customize('vh360_blog_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-blog-header .vh360-page-header__description').text(newval);
        });
    });

    // Live Room Header - Title
    wp.customize('vh360_live_room_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-live-room-header .vh360-page-header__title').text(newval);
        });
    });

    // Live Room Header - Description
    wp.customize('vh360_live_room_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-live-room-header .vh360-page-header__description').text(newval);
        });
    });

    // Events Header - Title
    wp.customize('vh360_events_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-events-header .vh360-page-header__title').text(newval);
        });
    });

    // Events Header - Description
    wp.customize('vh360_events_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-events-header .vh360-page-header__description').text(newval);
        });
    });

    // =============================================================================
    // SITE IDENTITY BINDINGS
    // =============================================================================

    // Site Title - Font Size
    wp.customize('vh360_site_title_font_size', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-font-size', newval + 'px');
        });
    });

    // Site Title - Color
    wp.customize('vh360_site_title_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-color', newval);
        });
    });

    // Site Title - Font Weight
    wp.customize('vh360_site_title_font_weight', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-font-weight', newval);
        });
    });

    // Site Title - Top Margin
    wp.customize('vh360_site_title_top_margin', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-top-margin', newval + 'px');
        });
    });

    // Site Title - Line Height
    wp.customize('vh360_site_title_line_height', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-line-height', newval);
        });
    });

    // Site Title - Vertical Alignment
    wp.customize('vh360_site_title_vertical_align', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-title-vertical-align', newval);
        });
    });

    // =============================================================================
    // FORM CONTENT CONTROLS BINDINGS (Icons)
    // =============================================================================

    // Login Page Icon 1
    wp.customize('vh360_login_icon_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(0) .vh360-auth-feature-icon').text(newval);
        });
    });

    // Login Page Icon 2
    wp.customize('vh360_login_icon_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(1) .vh360-auth-feature-icon').text(newval);
        });
    });

    // Login Page Icon 3
    wp.customize('vh360_login_icon_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-feature:eq(2) .vh360-auth-feature-icon').text(newval);
        });
    });

    // Register Page Icon 1
    wp.customize('vh360_register_icon_1', function(value) {
        value.bind(function(newval) {
            // Register page uses benefit icons in list items
            var $benefitIcon = $('.vh360-auth-benefits-list li:eq(0) .vh360-auth-benefit-icon');
            if ($benefitIcon.length) {
                $benefitIcon.text(newval);
            }
        });
    });

    // Register Page Icon 2
    wp.customize('vh360_register_icon_2', function(value) {
        value.bind(function(newval) {
            var $benefitIcon = $('.vh360-auth-benefits-list li:eq(1) .vh360-auth-benefit-icon');
            if ($benefitIcon.length) {
                $benefitIcon.text(newval);
            }
        });
    });

    // Register Page Icon 3
    wp.customize('vh360_register_icon_3', function(value) {
        value.bind(function(newval) {
            var $benefitIcon = $('.vh360-auth-benefits-list li:eq(2) .vh360-auth-benefit-icon');
            if ($benefitIcon.length) {
                $benefitIcon.text(newval);
            }
        });
    });

    // Register Page Icon 4
    wp.customize('vh360_register_icon_4', function(value) {
        value.bind(function(newval) {
            var $benefitIcon = $('.vh360-auth-benefits-list li:eq(3) .vh360-auth-benefit-icon');
            if ($benefitIcon.length) {
                $benefitIcon.text(newval);
            }
        });
    });

    // =============================================================================
    // BUSINESS LANDING PAGE BINDINGS (template-register-business.php)
    // =============================================================================

    // Business Landing - Headline
    wp.customize('vh360_business_landing_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-title').text(newval);
        });
    });

    // Business Landing - Description
    wp.customize('vh360_business_landing_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-description').text(newval);
        });
    });

    // Business Landing - Professional Card Title
    wp.customize('vh360_business_professional_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-card-title').text(newval);
        });
    });

    // Business Landing - Professional Card Description
    wp.customize('vh360_business_professional_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-card-description').text(newval);
        });
    });

    // Business Landing - Professional Features (1-4)
    wp.customize('vh360_business_professional_feature_1', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-features li:eq(0)').text(newval);
        });
    });

    wp.customize('vh360_business_professional_feature_2', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-features li:eq(1)').text(newval);
        });
    });

    wp.customize('vh360_business_professional_feature_3', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-features li:eq(2)').text(newval);
        });
    });

    wp.customize('vh360_business_professional_feature_4', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-features li:eq(3)').text(newval);
        });
    });

    // Business Landing - Professional Button
    wp.customize('vh360_business_professional_button', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-card:eq(0) .vh360-business-choice-button').text(newval);
        });
    });

    // Business Landing - Client Card Title
    wp.customize('vh360_business_client_title', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-card-title').text(newval);
        });
    });

    // Business Landing - Client Card Description
    wp.customize('vh360_business_client_description', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-card-description').text(newval);
        });
    });

    // Business Landing - Client Features (1-4)
    wp.customize('vh360_business_client_feature_1', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-features li:eq(0)').text(newval);
        });
    });

    wp.customize('vh360_business_client_feature_2', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-features li:eq(1)').text(newval);
        });
    });

    wp.customize('vh360_business_client_feature_3', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-features li:eq(2)').text(newval);
        });
    });

    wp.customize('vh360_business_client_feature_4', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-features li:eq(3)').text(newval);
        });
    });

    // Business Landing - Client Button
    wp.customize('vh360_business_client_button', function(value) {
        value.bind(function(newval) {
            getClientCard().find('.vh360-business-choice-button').text(newval);
        });
    });

    // Business Landing - Footer Text
    wp.customize('vh360_business_landing_footer_text', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-footer span').text(newval);
        });
    });

    // Business Landing - Footer Link
    wp.customize('vh360_business_landing_footer_link', function(value) {
        value.bind(function(newval) {
            $('.vh360-business-choice-footer .vh360-auth-link').text(newval);
        });
    });

    // =============================================================================
    // PROFESSIONAL REGISTRATION PAGE BINDINGS (template-register-professional.php)
    // =============================================================================

    // Professional Registration - Headline
    wp.customize('vh360_professional_register_headline', function(value) {
        value.bind(function(newval) {
            // Target the professional registration page specifically
            $('.vh360-auth-wrapper.register-professional .vh360-auth-heading').text(newval);
        });
    });

    // Professional Registration - Description
    wp.customize('vh360_professional_register_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-professional .vh360-auth-description').text(newval);
        });
    });

    // Professional Registration - Benefits Heading
    wp.customize('vh360_professional_register_benefits_heading', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-professional .vh360-auth-benefits-title').text(newval);
        });
    });

    // Professional Registration - Benefits 1-4
    wp.customize('vh360_professional_register_benefit_1', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-professional .vh360-auth-benefits-list li:eq(0)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_2', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-professional .vh360-auth-benefits-list li:eq(1)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_3', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-professional .vh360-auth-benefits-list li:eq(2)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_4', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-professional .vh360-auth-benefits-list li:eq(3)', newval);
        });
    });

    // Professional Registration - Button/Form Title
    wp.customize('vh360_professional_register_button', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-professional .vh360-auth-form-title').text(newval);
        });
    });

    // =============================================================================
    // CLIENT REGISTRATION PAGE BINDINGS (template-register-client.php)
    // =============================================================================

    // Client Registration - Headline
    wp.customize('vh360_client_register_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-client .vh360-auth-heading').text(newval);
        });
    });

    // Client Registration - Description
    wp.customize('vh360_client_register_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-client .vh360-auth-description').text(newval);
        });
    });

    // Client Registration - Benefits Heading
    wp.customize('vh360_client_register_benefits_heading', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-client .vh360-auth-benefits-title').text(newval);
        });
    });

    // Client Registration - Benefits 1-4
    wp.customize('vh360_client_register_benefit_1', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-client .vh360-auth-benefits-list li:eq(0)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_2', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-client .vh360-auth-benefits-list li:eq(1)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_3', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-client .vh360-auth-benefits-list li:eq(2)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_4', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.vh360-auth-wrapper.register-client .vh360-auth-benefits-list li:eq(3)', newval);
        });
    });

    // Client Registration - Button/Form Title
    wp.customize('vh360_client_register_button', function(value) {
        value.bind(function(newval) {
            $('.vh360-auth-wrapper.register-client .vh360-auth-form-title').text(newval);
        });
    });

    // =============================================================================
    // HELPER FUNCTIONS FOR GOOGLE FONTS
    // =============================================================================

    /**
     * Load Google Font dynamically in Customizer preview
     * 
     * Creates and appends a stylesheet link element for the specified Google Font
     * if it hasn't been loaded yet. Only loads fonts that match the predefined
     * font configuration. System fonts and unrecognized fonts are silently ignored.
     * 
     * Note: This function loads fonts from Google Fonts API over HTTPS. The fonts
     * are loaded for preview purposes only and don't require SRI as they're not
     * used in production - the actual font loading on the frontend is handled by
     * the theme's font enqueueing system.
     * 
     * @param {string} fontSlug Font slug (e.g., 'roboto', 'open-sans')
     */
    function loadGoogleFont(fontSlug) {
        // System fonts or empty slugs don't need loading
        if (!fontSlug || fontSlug === '' || fontSlug === 'system') {
            return;
        }

        // Get font configuration
        var fontConfig = FONT_CONFIG[fontSlug];
        if (!fontConfig || !fontConfig.googleFont) {
            // Unknown font slug or system font - silently ignore
            return;
        }

        // Check if font is already loaded to prevent duplicate loading
        var fontId = 'google-font-' + fontSlug;
        if (document.getElementById(fontId)) {
            return; // Already loaded
        }

        // Create and append link element to load the Google Font
        var link = document.createElement('link');
        link.id = fontId;
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + fontConfig.googleFont + '&display=swap';
        document.head.appendChild(link);
    }

    /**
     * Get CSS font-family value from font slug
     * 
     * @param {string} fontSlug Font slug
     * @return {string} CSS font-family value
     */
    function getFontFamilyCSS(fontSlug) {
        var fontConfig = FONT_CONFIG[fontSlug];
        if (fontConfig && fontConfig.cssFamily) {
            return fontConfig.cssFamily;
        }
        // Default to system fonts if slug not found
        return FONT_CONFIG.system.cssFamily;
    }

})(jQuery);
