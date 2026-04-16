<?php
/**
 * Dynamic CSS Generation
 *
 * Generates CSS variables and styles from customizer settings
 *
 * @package Videohub360_Theme
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate and output dynamic CSS
 */
function vh360_output_dynamic_css() {
    // Get color settings
    $primary_color     = get_theme_mod('vh360_primary_color', '#2563eb');
    $secondary_color   = get_theme_mod('vh360_secondary_color', '#1e40af');
    $accent_color      = get_theme_mod('vh360_accent_color', '#f59e0b');
    $header_bg_color   = get_theme_mod('vh360_header_bg_color', '#667eea');
    $header_bg_end     = get_theme_mod('vh360_header_bg_color_end', '#764ba2');
    $text_color        = get_theme_mod('vh360_text_color', '#1f2937');
    $text_light_color  = get_theme_mod('vh360_text_light_color', '#6b7280');
    $bg_color          = get_theme_mod('vh360_bg_color', '#ffffff');
    $site_header_bg    = get_theme_mod('vh360_site_header_bg_color', '#ffffff');
    $bg_light_color    = get_theme_mod('vh360_bg_light_color', '#f9fafb');
    $border_color      = get_theme_mod('vh360_border_color', '#e5e7eb');
    $success_color     = get_theme_mod('vh360_success_color', '#10b981');
    $error_color       = get_theme_mod('vh360_error_color', '#ef4444');
    $warning_color     = get_theme_mod('vh360_warning_color', '#f59e0b');
    $info_color        = get_theme_mod('vh360_info_color', '#6366f1');
    
    // Navigation & Header colors
    $hamburger_bg_color         = get_theme_mod('vh360_hamburger_bg_color', '#ffffff');
    $hamburger_text_color       = get_theme_mod('vh360_hamburger_text_color', '#1f2937');
    $hamburger_hover_bg_color   = get_theme_mod('vh360_hamburger_hover_bg_color', '#f9fafb');
    $hamburger_active_color     = get_theme_mod('vh360_hamburger_active_color', '#2563eb');
    $hamburger_icon_color       = get_theme_mod('vh360_hamburger_icon_color', '#1f2937');
    $nav_link_color             = get_theme_mod('vh360_nav_link_color', '#1f2937');
    
    // Community Menu colors
    $community_menu_bg_color        = get_theme_mod('vh360_community_menu_bg_color', '#ffffff');
    $community_menu_text_color      = get_theme_mod('vh360_community_menu_text_color', '#4b5563');
    $community_menu_hover_bg_color  = get_theme_mod('vh360_community_menu_hover_bg_color', '#f3f4f6');
    $community_menu_active_color    = get_theme_mod('vh360_community_menu_active_color', '#2563eb');
    $community_menu_active_bg_color = get_theme_mod('vh360_community_menu_active_bg_color', '#eff6ff');
    
    // Community Menu typography and layout
    $community_menu_font_family = get_theme_mod('vh360_community_menu_font_family', '');
    $community_menu_font_size   = get_theme_mod('vh360_community_menu_font_size', 15);
    $community_menu_font_weight = get_theme_mod('vh360_community_menu_font_weight', 500);
    $community_menu_left_gutter = get_theme_mod('vh360_community_menu_left_gutter', 24);
    $community_menu_width       = get_theme_mod('vh360_community_menu_width', 280);
    
    // Header Menu typography
    $header_menu_font_family = get_theme_mod('vh360_header_menu_font_family', '');
    $header_menu_font_size   = get_theme_mod('vh360_header_menu_font_size', 16);
    $header_menu_font_weight = get_theme_mod('vh360_header_menu_font_weight', 500);
    $header_menu_text_transform = get_theme_mod('vh360_header_menu_text_transform', 'none');
    $header_menu_letter_spacing = get_theme_mod('vh360_header_menu_letter_spacing', 0);
    
    // Button colors
    $button_bg_color            = get_theme_mod('vh360_button_bg_color', '#2563eb');
    $button_text_color          = get_theme_mod('vh360_button_text_color', '#ffffff');
    $button_hover_bg_color      = get_theme_mod('vh360_button_hover_bg_color', '#1e40af');
    $button_hover_text_color    = get_theme_mod('vh360_button_hover_text_color', '#ffffff');

    // Get footer settings
    $footer_bg_color         = get_theme_mod('vh360_footer_bg_color', '#1f2937');
    $footer_text_color       = get_theme_mod('vh360_footer_text_color', '#f9fafb');
    $footer_link_color       = get_theme_mod('vh360_footer_link_color', '#f9fafb');
    $footer_link_hover_color = get_theme_mod('vh360_footer_link_hover_color', '#ffffff');

    // Activity Feed colors
    $feed_tab_color       = get_theme_mod('vh360_feed_tab_color', '#65676b');
    $feed_tab_hover_color = get_theme_mod('vh360_feed_tab_hover_color', '#050505');
    $mention_color        = get_theme_mod('vh360_mention_color', '#2563eb');

    // Get typography settings
    $heading_font = get_theme_mod('vh360_heading_font', 'system');
    $body_font    = get_theme_mod('vh360_body_font', 'system');
    $font_size    = get_theme_mod('vh360_font_size', '16');
    $line_height  = get_theme_mod('vh360_line_height', '1.6');

    // Build font family strings
    $heading_font_family = vh360_get_font_family($heading_font);
    $body_font_family    = vh360_get_font_family($body_font);

    // Additional settings for branding
    $logo_max_width = get_theme_mod('vh360_logo_max_width', 110);
    
    // Calculate header height based on logo size (min 80px, scales with logo)
    // Header height = logo width * 0.6, with 80px minimum for usability
    $header_height = max(80, round($logo_max_width * 0.6));
    
    // Auth Pages Colors (Consolidated - shared by Login, Register, Lost Password, Reset Password)
    $auth_page_bg_color             = get_theme_mod('vh360_auth_page_bg_color', '#f3f4f6');
    $auth_form_bg_color             = get_theme_mod('vh360_auth_form_bg_color', '#ffffff');
    $auth_welcome_bg_start          = get_theme_mod('vh360_auth_welcome_bg_start', '#667eea');
    $auth_welcome_bg_end            = get_theme_mod('vh360_auth_welcome_bg_end', '#764ba2');
    $auth_welcome_text_color        = get_theme_mod('vh360_auth_welcome_text_color', '#ffffff');
    $auth_form_title_color          = get_theme_mod('vh360_auth_form_title_color', '#1f2937');
    $auth_label_color               = get_theme_mod('vh360_auth_label_color', '#374151');
    $auth_input_border_color        = get_theme_mod('vh360_auth_input_border_color', '#e5e7eb');
    $auth_input_focus_border_color  = get_theme_mod('vh360_auth_input_focus_border_color', '#667eea');
    $auth_button_bg_start           = get_theme_mod('vh360_auth_button_bg_start', '#667eea');
    $auth_button_bg_end             = get_theme_mod('vh360_auth_button_bg_end', '#764ba2');
    $auth_button_text_color         = get_theme_mod('vh360_auth_button_text_color', '#ffffff');
    $auth_link_color                = get_theme_mod('vh360_auth_link_color', '#667eea');
    $auth_error_bg_color            = get_theme_mod('vh360_auth_error_bg_color', '#fee');
    $auth_error_text_color          = get_theme_mod('vh360_auth_error_text_color', '#c00');
    $auth_success_bg_color          = get_theme_mod('vh360_auth_success_bg_color', '#efe');
    
    // Site title styling
    $site_title_font_size       = absint(get_theme_mod('vh360_site_title_font_size', 24));
    $site_title_color           = get_theme_mod('vh360_site_title_color', '#2563eb');
    $site_title_font_weight     = get_theme_mod('vh360_site_title_font_weight', '700');
    $site_title_top_margin      = intval(get_theme_mod('vh360_site_title_top_margin', 0));
    $site_title_line_height     = floatval(get_theme_mod('vh360_site_title_line_height', 1));
    $site_title_vertical_align  = get_theme_mod('vh360_site_title_vertical_align', 'center');

    // Output CSS
    ?>
    <style id="vh360-dynamic-css">
        :root {
            /* Brand Colors */
            --primary-color: <?php echo esc_attr($primary_color); ?>;
            --secondary-color: <?php echo esc_attr($secondary_color); ?>;
            --accent-color: <?php echo esc_attr($accent_color); ?>;
            
            /* Text & Background Colors */
            --text-color: <?php echo esc_attr($text_color); ?>;
            --text-light: <?php echo esc_attr($text_light_color); ?>;
            --bg-color: <?php echo esc_attr($bg_color); ?>;
            --site-header-bg-color: <?php echo esc_attr($site_header_bg); ?>;
            --bg-light: <?php echo esc_attr($bg_light_color); ?>;
            --border-color: <?php echo esc_attr($border_color); ?>;
            
            /* Navigation & Header Colors */
            --header-bg-color: <?php echo esc_attr($header_bg_color); ?>;
            --header-bg-end: <?php echo esc_attr($header_bg_end); ?>;
            --hamburger-bg-color: <?php echo esc_attr($hamburger_bg_color); ?>;
            --hamburger-text-color: <?php echo esc_attr($hamburger_text_color); ?>;
            --hamburger-hover-bg-color: <?php echo esc_attr($hamburger_hover_bg_color); ?>;
            --hamburger-active-color: <?php echo esc_attr($hamburger_active_color); ?>;
            --hamburger-icon-color: <?php echo esc_attr($hamburger_icon_color); ?>;
            --nav-link-color: <?php echo esc_attr($nav_link_color); ?>;
            
            /* Community Menu Colors */
            --community-menu-bg-color: <?php echo esc_attr($community_menu_bg_color); ?>;
            --community-menu-text-color: <?php echo esc_attr($community_menu_text_color); ?>;
            --community-menu-hover-bg-color: <?php echo esc_attr($community_menu_hover_bg_color); ?>;
            --community-menu-active-color: <?php echo esc_attr($community_menu_active_color); ?>;
            --community-menu-active-bg-color: <?php echo esc_attr($community_menu_active_bg_color); ?>;
            
            /* Community Menu Layout & Typography */
            --community-menu-left-gutter: <?php echo absint($community_menu_left_gutter); ?>px;
            --community-menu-width: <?php echo absint($community_menu_width); ?>px;
            --community-menu-font-size: <?php echo absint($community_menu_font_size); ?>px;
            --community-menu-font-weight: <?php echo absint($community_menu_font_weight); ?>;
            <?php if (!empty($community_menu_font_family)) : ?>
            --community-menu-font-family: <?php echo vh360_get_font_family($community_menu_font_family); ?>;
            <?php endif; ?>
            
            /* Header Menu Typography */
            --header-menu-font-size: <?php echo absint($header_menu_font_size); ?>px;
            --header-menu-font-weight: <?php echo absint($header_menu_font_weight); ?>;
            --header-menu-text-transform: <?php echo esc_attr($header_menu_text_transform); ?>;
            --header-menu-letter-spacing: <?php echo floatval($header_menu_letter_spacing); ?>px;
            <?php if (!empty($header_menu_font_family)) : ?>
            --header-menu-font-family: <?php echo vh360_get_font_family($header_menu_font_family); ?>;
            <?php endif; ?>
            
            /* Button Colors */
            --button-bg-color: <?php echo esc_attr($button_bg_color); ?>;
            --button-text-color: <?php echo esc_attr($button_text_color); ?>;
            --button-hover-bg-color: <?php echo esc_attr($button_hover_bg_color); ?>;
            --button-hover-text-color: <?php echo esc_attr($button_hover_text_color); ?>;
            
            /* Status & Alert Colors */
            --success-color: <?php echo esc_attr($success_color); ?>;
            --error-color: <?php echo esc_attr($error_color); ?>;
            --warning-color: <?php echo esc_attr($warning_color); ?>;
            --info-color: <?php echo esc_attr($info_color); ?>;

            /* Activity Feed Colors */
            --feed-tab-color: <?php echo esc_attr($feed_tab_color); ?>;
            --feed-tab-hover-color: <?php echo esc_attr($feed_tab_hover_color); ?>;
            --mention-color: <?php echo esc_attr($mention_color); ?>;

            /* Semantic Surface Tokens */
            --surface-1: <?php echo esc_attr($bg_color); ?>; /* Main page background */
            --surface-2: <?php echo esc_attr($bg_color); ?>; /* Card background (same as main for now) */
            --surface-3: <?php echo esc_attr($bg_light_color); ?>; /* Hover/alternate background */

            /* Semantic Text Tokens */
            --text-1: <?php echo esc_attr($text_color); ?>; /* Primary text */
            --text-2: <?php echo esc_attr($text_light_color); ?>; /* Secondary text */

            /* Semantic Border Tokens */
            --border-1: <?php echo esc_attr($border_color); ?>; /* Standard borders */

            /* Semantic Interactive Tokens */
            --ring-1: <?php echo esc_attr($primary_color); ?>; /* Focus outlines */

            /* Shadow Tokens */
            --shadow-1: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-2: 0 4px 12px rgba(0, 0, 0, 0.15);

            /* Footer Color Variables */
            --footer-bg-color: <?php echo esc_attr($footer_bg_color); ?>;
            --footer-text-color: <?php echo esc_attr($footer_text_color); ?>;
            --footer-link-color: <?php echo esc_attr($footer_link_color); ?>;
            --footer-link-hover-color: <?php echo esc_attr($footer_link_hover_color); ?>;

            /* Branding Variables */
            --logo-max-width: <?php echo esc_attr($logo_max_width); ?>px;
            --header-height: <?php echo esc_attr($header_height); ?>px;
            
            /* Auth Pages Variables (Shared by Login, Register, Lost Password, Reset Password) */
            --auth-page-bg: <?php echo esc_attr($auth_page_bg_color); ?>;
            --auth-form-bg: <?php echo esc_attr($auth_form_bg_color); ?>;
            --auth-welcome-bg-start: <?php echo esc_attr($auth_welcome_bg_start); ?>;
            --auth-welcome-bg-end: <?php echo esc_attr($auth_welcome_bg_end); ?>;
            --auth-welcome-text: <?php echo esc_attr($auth_welcome_text_color); ?>;
            --auth-form-title: <?php echo esc_attr($auth_form_title_color); ?>;
            --auth-label: <?php echo esc_attr($auth_label_color); ?>;
            --auth-input-border: <?php echo esc_attr($auth_input_border_color); ?>;
            --auth-input-focus-border: <?php echo esc_attr($auth_input_focus_border_color); ?>;
            --auth-button-bg-start: <?php echo esc_attr($auth_button_bg_start); ?>;
            --auth-button-bg-end: <?php echo esc_attr($auth_button_bg_end); ?>;
            --auth-button-text: <?php echo esc_attr($auth_button_text_color); ?>;
            --auth-link: <?php echo esc_attr($auth_link_color); ?>;
            --auth-error-bg: <?php echo esc_attr($auth_error_bg_color); ?>;
            --auth-error-text: <?php echo esc_attr($auth_error_text_color); ?>;
            --auth-success-bg: <?php echo esc_attr($auth_success_bg_color); ?>;
                
            /* Site Title Styling Variables */
                --site-title-font-size: <?php echo $site_title_font_size; ?>px;
                --site-title-color: <?php echo esc_attr($site_title_color); ?>;
                --site-title-font-weight: <?php echo esc_attr($site_title_font_weight); ?>;
                --site-title-top-margin: <?php echo $site_title_top_margin; ?>px;
                --site-title-line-height: <?php echo $site_title_line_height; ?>;
                --site-title-vertical-align: <?php echo esc_attr($site_title_vertical_align); ?>;
        }

        /* Page Header Styles - Unified across all templates */
        .vh360-activity-header,
        .vh360-members-header,
        .vh360-bulletins-header,
        .vh360-events-header,
        .vh360-blog-header,
        .videohub360-archive-header {
            background: linear-gradient(135deg, var(--header-bg-color) 0%, var(--header-bg-end) 100%);
            color: #ffffff;
            padding: 3rem 0 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Ensure spacing when template headers are disabled (Bulletins/Events/Blog) */
        .vh360-bulletins-archive.vh360-template-header-off,
        .vh360-events-archive.vh360-template-header-off,
        .vh360-blog-archive.vh360-template-header-off {
            padding-top: 50px;
        }


        /* Typography */
        html {
            font-size: <?php echo absint($font_size); ?>px;
        }

        body {
            font-family: <?php echo $body_font_family; ?>;
            line-height: <?php echo esc_attr($line_height); ?>;
        }

        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: <?php echo $heading_font_family; ?>;
        }

        /* Logo Sizing */
        .custom-logo,
        .site-branding .custom-logo {
            max-width: var(--logo-max-width);
            /* max-height accounts for header padding (10px top + 10px bottom = 20px) */
            max-height: calc(var(--header-height) - 20px);
            width: auto;
            height: auto;
            display: block;
        }
        .vh360-auth-logo img {
            max-width: var(--logo-max-width);
            height: auto;
        }

        /* Auth Page Backgrounds (all auth pages share same colors) */
        .vh360-auth-page {
            background-color: var(--auth-page-bg);
        }
        .vh360-auth-form-content {
            background-color: var(--auth-form-bg);
        }
        
        /* Site Title Styling */
        .site-branding {
            display: flex;
            align-items: var(--site-title-vertical-align);
        }
        
        .site-title,
        .site-branding .site-title a {
            font-size: var(--site-title-font-size);
            color: var(--site-title-color);
            font-weight: var(--site-title-font-weight);
            margin-top: var(--site-title-top-margin);
            line-height: var(--site-title-line-height);
            margin-bottom: 0;
        }
        
        .site-title a:hover {
            color: var(--site-title-color);
            opacity: 0.8;
        }

        /* Conditional Header Visibility */
        <?php if (!get_theme_mod('vh360_show_activity_header', 1)) : ?>
        .vh360-activity-header { display: none; }
        <?php endif; ?>
        <?php if (!get_theme_mod('vh360_show_members_header', 1)) : ?>
        .vh360-members-header { display: none; }
        <?php endif; ?>
        <?php if (!get_theme_mod('vh360_show_bulletins_header', 1)) : ?>
        .vh360-bulletins-header { display: none; }
        <?php endif; ?>
        <?php if (!get_theme_mod('vh360_show_events_header', 1)) : ?>
        .vh360-events-header { display: none; }
        <?php endif; ?>
        <?php if (!get_theme_mod('vh360_show_blog_header', 1)) : ?>
        .vh360-blog-header { display: none; }
        <?php endif; ?>
        <?php if (!get_theme_mod('vh360_show_archive_header', 1)) : ?>
        .videohub360-archive-header { display: none; }
        <?php endif; ?>

        /* Footer Styles */
        .site-footer {
            background-color: var(--footer-bg-color);
            color: var(--footer-text-color);
        }

        .site-footer a {
            color: var(--footer-link-color);
        }

        .site-footer a:hover {
            color: var(--footer-link-hover-color);
        }

        /* Community Menu Styles */
        body.has-community-menu .vh360-community-menu {
            background-color: var(--community-menu-bg-color);
        }

        .vh360-community-menu__list a {
            color: var(--community-menu-text-color);
        }

        .vh360-community-menu__list a:hover {
            background-color: var(--community-menu-hover-bg-color);
        }

        .vh360-community-menu__list .current-menu-item > a,
        .vh360-community-menu__list .current_page_item > a,
        .vh360-community-menu__list .current-menu-ancestor > a {
            background-color: var(--community-menu-active-bg-color);
            color: var(--community-menu-active-color);
        }

        .vh360-community-menu__list .current-menu-item > a::before,
        .vh360-community-menu__list .current_page_item > a::before,
        .vh360-community-menu__list .current-menu-ancestor > a::before {
            background-color: var(--community-menu-active-color);
        }
    </style>
    <?php
}
add_action('wp_head', 'vh360_output_dynamic_css', 99);

/**
 * Get font family CSS string
 *
 * @param string $font Font identifier.
 * @return string Font family CSS value.
 */
function vh360_get_font_family($font) {
    $system_fonts = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif";
    
    if ($font === 'system') {
        return $system_fonts;
    }
    
    // Return Google Font with system fallback
    // Note: Font name is already sanitized by Customizer sanitizer, no escaping needed here
    // This value will be output in CSS context (inside <style> tag), not HTML attribute context
    return "'" . $font . "', " . $system_fonts;
}
