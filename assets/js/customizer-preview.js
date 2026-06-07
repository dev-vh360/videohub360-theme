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
     * Maps font identifiers to both Google Fonts API names and CSS font-family values
     * Keys MUST match exactly with values from vh360_get_font_choices() in PHP
     */
    var FONT_CONFIG = {
        'system': {
            googleFont: null,
            cssFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
        },
        'Roboto': {
            googleFont: 'Roboto:400,500,600,700',
            cssFamily: '"Roboto", sans-serif'
        },
        'Open Sans': {
            googleFont: 'Open+Sans:400,500,600,700',
            cssFamily: '"Open Sans", sans-serif'
        },
        'Lato': {
            googleFont: 'Lato:400,500,600,700',
            cssFamily: '"Lato", sans-serif'
        },
        'Montserrat': {
            googleFont: 'Montserrat:400,500,600,700',
            cssFamily: '"Montserrat", sans-serif'
        },
        'Raleway': {
            googleFont: 'Raleway:400,500,600,700',
            cssFamily: '"Raleway", sans-serif'
        },
        'Poppins': {
            googleFont: 'Poppins:400,500,600,700',
            cssFamily: '"Poppins", sans-serif'
        },
        'Nunito': {
            googleFont: 'Nunito:400,500,600,700',
            cssFamily: '"Nunito", sans-serif'
        },
        'Playfair Display': {
            googleFont: 'Playfair+Display:400,500,600,700',
            cssFamily: '"Playfair Display", serif'
        },
        'Merriweather': {
            googleFont: 'Merriweather:400,700',
            cssFamily: '"Merriweather", serif'
        },
        'PT Sans': {
            googleFont: 'PT+Sans:400,500,700',
            cssFamily: '"PT Sans", sans-serif'
        },
        'Source Sans Pro': {
            googleFont: 'Source+Sans+Pro:400,500,600,700',
            cssFamily: '"Source Sans Pro", sans-serif'
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

    wp.customize('vh360_page_header_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('page-header-text-color', newval);
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

    wp.customize('vh360_site_header_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('site-header-bg-color', newval);
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

    // Header Action Color Controls
    wp.customize('vh360_header_signin_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-signin-bg-color', newval);
        });
    });

    wp.customize('vh360_header_signin_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-signin-text-color', newval);
        });
    });

    wp.customize('vh360_header_signin_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-signin-hover-bg-color', newval);
        });
    });

    wp.customize('vh360_header_signin_hover_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-signin-hover-text-color', newval);
        });
    });

    wp.customize('vh360_header_register_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-register-text-color', newval);
        });
    });

    wp.customize('vh360_header_register_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-register-border-color', newval);
        });
    });

    wp.customize('vh360_header_register_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-register-hover-bg-color', newval);
        });
    });

    wp.customize('vh360_header_register_hover_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-register-hover-text-color', newval);
        });
    });

    wp.customize('vh360_header_icon_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-icon-color', newval);
        });
    });

    wp.customize('vh360_header_icon_hover_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-icon-hover-color', newval);
        });
    });

    wp.customize('vh360_header_icon_hover_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-icon-hover-bg-color', newval);
        });
    });

    wp.customize('vh360_header_notification_badge_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-notification-badge-bg-color', newval);
        });
    });

    wp.customize('vh360_header_notification_badge_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-notification-badge-text-color', newval);
        });
    });

    wp.customize('vh360_header_notification_badge_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-notification-badge-border-color', newval);
        });
    });

    wp.customize('vh360_header_message_badge_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-message-badge-bg-color', newval);
        });
    });

    wp.customize('vh360_header_message_badge_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-message-badge-text-color', newval);
        });
    });

    wp.customize('vh360_header_message_badge_border_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('vh360-header-message-badge-border-color', newval);
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
            // Load Google Font if needed
            loadGoogleFont(newval);
            // Get the font family CSS value
            var fontFamily = getFontFamilyCSS(newval);
            $('h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6').css('font-family', fontFamily);
        });
    });

    wp.customize('vh360_body_font', function(value) {
        value.bind(function(newval) {
            // Load Google Font if needed
            loadGoogleFont(newval);
            // Get the font family CSS value
            var fontFamily = getFontFamilyCSS(newval);
            $('body').css('font-family', fontFamily);
        });
    });

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
    // AUTH PAGES BINDINGS (Global Design)
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

    // Auth Pages - Input Background Color
    wp.customize('vh360_auth_input_bg_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-input-bg', newval);
        });
    });

    // Auth Pages - Input Text Color
    wp.customize('vh360_auth_input_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-input-text', newval);
        });
    });

    // Auth Pages - Muted / Helper Text Color
    wp.customize('vh360_auth_muted_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-muted-text', newval);
        });
    });

    // Auth Pages - Input Focus Shadow Color
    wp.customize('vh360_auth_input_focus_shadow_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-input-focus-shadow', newval);
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

    // Auth Pages - Button Hover Shadow Color
    wp.customize('vh360_auth_button_hover_shadow_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-button-hover-shadow', newval);
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

    // Auth Pages - Success Text Color
    wp.customize('vh360_auth_success_text_color', function(value) {
        value.bind(function(newval) {
            updateCSSVariable('auth-success-text', newval);
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

    // Course Catalog Header - Title
    wp.customize('vh360_course_catalog_header_title', function(value) {
        value.bind(function(newval) {
            $('.vh360-course-catalog-template-header .vh360-course-catalog-page-title').text(newval);
        });
    });

    // Course Catalog Header - Description
    wp.customize('vh360_course_catalog_header_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-course-catalog-template-header .vh360-course-catalog-page-description').text(newval);
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
    // REGISTRATION LANDING PAGE BINDINGS (template-register-landing.php)
    // =============================================================================

    wp.customize('vh360_registration_landing_headline', function(value) {
        value.bind(function(newval) {
            $('.vh360-registration-landing-page .vh360-registration-choice-header h1').text(newval);
        });
    });

    wp.customize('vh360_registration_landing_description', function(value) {
        value.bind(function(newval) {
            $('.vh360-registration-landing-page .vh360-registration-choice-description').text(newval);
        });
    });

    function bindRegistrationCardText(settingId, selector) {
        wp.customize(settingId, function(value) {
            value.bind(function(newval) {
                $(selector).text(newval);
            });
        });
    }

    var registrationCards = ['professional', 'instructor', 'client'];

    registrationCards.forEach(function(card) {
        var cardSelector = '.vh360-registration-card-' + card;

        bindRegistrationCardText(
            'vh360_registration_' + card + '_title',
            cardSelector + ' .vh360-registration-card-title'
        );

        bindRegistrationCardText(
            'vh360_registration_' + card + '_description',
            cardSelector + ' .vh360-registration-card-description'
        );

        for (var i = 1; i <= 4; i++) {
            bindRegistrationCardText(
                'vh360_registration_' + card + '_feature_' + i,
                cardSelector + ' .vh360-registration-card-features li:eq(' + (i - 1) + ')'
            );
        }

        bindRegistrationCardText(
            'vh360_registration_' + card + '_button',
            cardSelector + ' .vh360-registration-card-button'
        );
    });

    // =============================================================================
    // PROFESSIONAL REGISTRATION PAGE BINDINGS (template-register-professional.php)
    // =============================================================================

    // Professional Registration - Headline
    wp.customize('vh360_professional_register_headline', function(value) {
        value.bind(function(newval) {
            // Target the professional registration page specifically
            $('.professional-register-page .vh360-auth-heading').text(newval);
        });
    });

    // Professional Registration - Description
    wp.customize('vh360_professional_register_description', function(value) {
        value.bind(function(newval) {
            $('.professional-register-page .vh360-auth-description').text(newval);
        });
    });

    // Professional Registration - Benefits Heading
    wp.customize('vh360_professional_register_benefits_heading', function(value) {
        value.bind(function(newval) {
            $('.professional-register-page .vh360-auth-benefits-title').text(newval);
        });
    });

    // Professional Registration - Benefits 1-4
    wp.customize('vh360_professional_register_benefit_1', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.professional-register-page .vh360-auth-benefits-list li:eq(0)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_2', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.professional-register-page .vh360-auth-benefits-list li:eq(1)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_3', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.professional-register-page .vh360-auth-benefits-list li:eq(2)', newval);
        });
    });

    wp.customize('vh360_professional_register_benefit_4', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.professional-register-page .vh360-auth-benefits-list li:eq(3)', newval);
        });
    });

    // Professional Registration - Button/Form Title
    wp.customize('vh360_professional_register_button', function(value) {
        value.bind(function(newval) {
            $('.professional-register-page .vh360-auth-form-title, .professional-register-page .vh360-auth-submit').text(newval);
        });
    });

    // =============================================================================
    // INSTRUCTOR REGISTRATION PAGE BINDINGS (template-register-instructor.php)
    // =============================================================================

    wp.customize('vh360_instructor_register_headline', function(value) {
        value.bind(function(newval) {
            $('.instructor-register-page .vh360-auth-heading').text(newval);
        });
    });

    wp.customize('vh360_instructor_register_description', function(value) {
        value.bind(function(newval) {
            $('.instructor-register-page .vh360-auth-description').text(newval);
        });
    });

    wp.customize('vh360_instructor_register_benefits_heading', function(value) {
        value.bind(function(newval) {
            $('.instructor-register-page .vh360-auth-benefits-title').text(newval);
        });
    });

    wp.customize('vh360_instructor_register_benefit_1', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.instructor-register-page .vh360-auth-benefits-list li:eq(0)', newval);
        });
    });

    wp.customize('vh360_instructor_register_benefit_2', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.instructor-register-page .vh360-auth-benefits-list li:eq(1)', newval);
        });
    });

    wp.customize('vh360_instructor_register_benefit_3', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.instructor-register-page .vh360-auth-benefits-list li:eq(2)', newval);
        });
    });

    wp.customize('vh360_instructor_register_benefit_4', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.instructor-register-page .vh360-auth-benefits-list li:eq(3)', newval);
        });
    });

    wp.customize('vh360_instructor_register_button', function(value) {
        value.bind(function(newval) {
            $('.instructor-register-page .vh360-auth-form-title, .instructor-register-page .vh360-auth-submit').text(newval);
        });
    });

    // =============================================================================
    // CLIENT REGISTRATION PAGE BINDINGS (template-register-client.php)
    // =============================================================================

    // Client Registration - Headline
    wp.customize('vh360_client_register_headline', function(value) {
        value.bind(function(newval) {
            $('.client-register-page .vh360-auth-heading').text(newval);
        });
    });

    // Client Registration - Description
    wp.customize('vh360_client_register_description', function(value) {
        value.bind(function(newval) {
            $('.client-register-page .vh360-auth-description').text(newval);
        });
    });

    // Client Registration - Benefits Heading
    wp.customize('vh360_client_register_benefits_heading', function(value) {
        value.bind(function(newval) {
            $('.client-register-page .vh360-auth-benefits-title').text(newval);
        });
    });

    // Client Registration - Benefits 1-4
    wp.customize('vh360_client_register_benefit_1', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.client-register-page .vh360-auth-benefits-list li:eq(0)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_2', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.client-register-page .vh360-auth-benefits-list li:eq(1)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_3', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.client-register-page .vh360-auth-benefits-list li:eq(2)', newval);
        });
    });

    wp.customize('vh360_client_register_benefit_4', function(value) {
        value.bind(function(newval) {
            updateBenefitText('.client-register-page .vh360-auth-benefits-list li:eq(3)', newval);
        });
    });

    // Client Registration - Button/Form Title
    wp.customize('vh360_client_register_button', function(value) {
        value.bind(function(newval) {
            $('.client-register-page .vh360-auth-form-title, .client-register-page .vh360-auth-submit').text(newval);
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
     * @param {string} fontName Font name (e.g., 'Roboto', 'Open Sans')
     */
    function loadGoogleFont(fontName) {
        // System fonts or empty values don't need loading
        if (!fontName || fontName === '' || fontName === 'system') {
            return;
        }

        // Get font configuration
        var fontConfig = FONT_CONFIG[fontName];
        if (!fontConfig || !fontConfig.googleFont) {
            // Unknown font or system font - silently ignore
            return;
        }

        // Check if font is already loaded to prevent duplicate loading
        // Create a safe ID from the font name
        var fontId = 'google-font-' + fontName.replace(/\s+/g, '-').toLowerCase();
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
     * Get CSS font-family value from font name
     * 
     * @param {string} fontName Font name
     * @return {string} CSS font-family value
     */
    function getFontFamilyCSS(fontName) {
        var fontConfig = FONT_CONFIG[fontName];
        if (fontConfig && fontConfig.cssFamily) {
            return fontConfig.cssFamily;
        }
        // Default to system fonts if name not found
        return FONT_CONFIG.system.cssFamily;
    }

})(jQuery);
