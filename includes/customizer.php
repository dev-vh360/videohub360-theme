<?php
/**
 * Theme Customizer
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function videohub360_theme_customize_register($wp_customize) {
    $wp_customize->get_setting('blogname')->transport         = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport  = 'postMessage';

    if (isset($wp_customize->selective_refresh)) {
        $wp_customize->selective_refresh->add_partial(
            'blogname',
            array(
                'selector'        => '.site-title a',
                'render_callback' => 'videohub360_theme_customize_partial_blogname',
            )
        );
        $wp_customize->selective_refresh->add_partial(
            'blogdescription',
            array(
                'selector'        => '.site-description',
                'render_callback' => 'videohub360_theme_customize_partial_blogdescription',
            )
        );
    }


    // ==========================================
    // PANEL ARCHITECTURE
    // ==========================================
    
    // 1. Header & Navigation - All navigation and header-related settings
    $wp_customize->add_panel('vh360_header_navigation', array(
        'title'       => __('Header & Navigation', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('Configure site identity, logo, navigation, community menu, and template headers.', 'videohub360-theme'),
    ));

    // 2. Global Design - All visual system settings (colors, typography, layout, sidebar, footer)
    $wp_customize->add_panel('vh360_global_design', array(
        'title'       => __('Global Design', 'videohub360-theme'),
        'priority'    => 40,
        'description' => __('Global design options including colors, typography, layout, sidebar, and footer.', 'videohub360-theme'),
    ));

    // 3. Community & Activity Feed - Activity feed and community features
    $wp_customize->add_panel('vh360_community_activity', array(
        'title'       => __('Community & Activity Feed', 'videohub360-theme'),
        'priority'    => 50,
        'description' => __('Customize activity feed and community sidebar settings.', 'videohub360-theme'),
    ));

    // 4. Authentication Pages - All auth-related settings in one place
    $wp_customize->add_panel('vh360_auth_pages', array(
        'title'       => __('Authentication Pages', 'videohub360-theme'),
        'priority'    => 60,
        'description' => __('Customize login, registration, and password reset pages.', 'videohub360-theme'),
    ));

    // 5. Site Behavior / Advanced - Non-visual, functional settings
    $wp_customize->add_panel('vh360_site_behavior', array(
        'title'       => __('Site Behavior / Advanced', 'videohub360-theme'),
        'priority'    => 70,
        'description' => __('Advanced settings for site behavior, author templates, and media handling.', 'videohub360-theme'),
    ));

    // Note: Do NOT assign core WordPress colors section to any panel
    // We use vh360_colors section only (defined in color-controls.php)

    // Layout options - Assign to Global Design panel
    $wp_customize->add_section('videohub360_theme_layout', array(
        'title'    => __('Layout Options', 'videohub360-theme'),
        'priority' => 30,
        'panel'    => 'vh360_global_design',
    ));

    // Container width
    $wp_customize->add_setting('videohub360_theme_container_width', array(
        'default'           => '1280',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('videohub360_theme_container_width', array(
        'label'       => __('Container Width (px)', 'videohub360-theme'),
        'section'     => 'videohub360_theme_layout',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 960,
            'max'  => 1920,
            'step' => 20,
        ),
    ));

    // ==========================================
    // COMMUNITY MENU - Split into multiple sections
    // ==========================================
    
    // Community Menu Behavior - Visibility and hiding rules
    $wp_customize->add_section('vh360_community_menu_behavior', array(
        'title'       => __('Community Menu - Behavior', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 50,
        'description' => __('Control visibility and display rules for the Community Menu. To enable Community Menu, select it under Site Header > Navigation Style.', 'videohub360-theme'),
    ));

    // Show to Logged-Out Users
    $wp_customize->add_setting('vh360_community_menu_logged_out', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('vh360_community_menu_logged_out', array(
        'label'       => __('Show to Logged-Out Users', 'videohub360-theme'),
        'description' => __('Display the Community Menu for visitors who are not logged in.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_behavior',
        'type'        => 'checkbox',
    ));

    // Hide on Dashboard Template
    $wp_customize->add_setting('vh360_community_menu_hide_dashboard', array(
        'default'           => 1,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('vh360_community_menu_hide_dashboard', array(
        'label'       => __('Hide on Dashboard Template', 'videohub360-theme'),
        'description' => __('Hide the Community Menu on the Dashboard template page.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_behavior',
        'type'        => 'checkbox',
    ));

    // Hide on Auth Pages
    $wp_customize->add_setting('vh360_community_menu_hide_auth', array(
        'default'           => 1,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('vh360_community_menu_hide_auth', array(
        'label'       => __('Hide on Auth Pages', 'videohub360-theme'),
        'description' => __('Hide the Community Menu on login, register, and password reset pages.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_behavior',
        'type'        => 'checkbox',
    ));

    // Compact Mode
    $wp_customize->add_setting('vh360_community_menu_compact', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('vh360_community_menu_compact', array(
        'label'       => __('Compact Mode (Icons Only)', 'videohub360-theme'),
        'description' => __('Display only icons without text labels (64px width).', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_behavior',
        'type'        => 'checkbox',
    ));

    // Community Menu Layout - Width and spacing
    $wp_customize->add_section('vh360_community_menu_layout', array(
        'title'       => __('Community Menu - Layout', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 51,
        'description' => __('Control width and spacing for the Community Menu.', 'videohub360-theme'),
    ));

    // Layout - Left Gutter
    $wp_customize->add_setting('vh360_community_menu_left_gutter', array(
        'default'           => 24,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_community_menu_left_gutter', array(
        'label'       => __('Left Gutter (px)', 'videohub360-theme'),
        'description' => __('Spacing to move the menu away from viewport edge. Affects feed centering.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_layout',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 0,
            'max'  => 140,
            'step' => 4,
        ),
    ));

    // Layout - Menu Width
    $wp_customize->add_setting('vh360_community_menu_width', array(
        'default'           => 280,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_community_menu_width', array(
        'label'       => __('Menu Width (px)', 'videohub360-theme'),
        'description' => __('Width of the Community Menu.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_layout',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 220,
            'max'  => 360,
            'step' => 10,
        ),
    ));

    // Community Menu Colors - Background, hover, active states
    $wp_customize->add_section('vh360_community_menu_colors', array(
        'title'       => __('Community Menu - Colors', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 52,
        'description' => __('Customize colors for the Community Menu. Leave blank to use global theme colors.', 'videohub360-theme'),
    ));

    // Background Color
    $wp_customize->add_setting('vh360_community_menu_bg_color', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_community_menu_bg_color', array(
        'label'       => __('Background Color', 'videohub360-theme'),
        'description' => __('Background color for the Community Menu.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_colors',
    )));

    // Text Color
    $wp_customize->add_setting('vh360_community_menu_text_color', array(
        'default'           => '#4b5563',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_community_menu_text_color', array(
        'label'       => __('Text Color', 'videohub360-theme'),
        'description' => __('Text color for menu items in the Community Menu.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_colors',
    )));

    // Hover Background Color
    $wp_customize->add_setting('vh360_community_menu_hover_bg_color', array(
        'default'           => '#f3f4f6',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_community_menu_hover_bg_color', array(
        'label'       => __('Hover Background Color', 'videohub360-theme'),
        'description' => __('Background color when hovering over menu items.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_colors',
    )));

    // Active Item Color
    $wp_customize->add_setting('vh360_community_menu_active_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_community_menu_active_color', array(
        'label'       => __('Active Item Color', 'videohub360-theme'),
        'description' => __('Text color for the active/current menu item.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_colors',
    )));

    // Active Background Color
    $wp_customize->add_setting('vh360_community_menu_active_bg_color', array(
        'default'           => '#eff6ff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'vh360_community_menu_active_bg_color', array(
        'label'       => __('Active Background Color', 'videohub360-theme'),
        'description' => __('Background color for the active/current menu item.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_colors',
    )));

    // Mobile Bottom Navigation Colors
    $wp_customize->add_section('vh360_mobile_nav_colors', array(
        'title'       => __('Mobile Bottom Navigation Colors', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 54,
        'description' => __('Customize the mobile bottom navigation bar, badges, user drawer, and drawer overlay colors.', 'videohub360-theme'),
    ));

    $mobile_nav_color_controls = array(
        'vh360_mobile_nav_bg_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Mobile Nav Background Color', 'videohub360-theme'),
            'description' => __('Background color for the fixed mobile bottom navigation bar.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_border_color' => array(
            'default'     => '#e5e7eb',
            'label'       => __('Mobile Nav Border Color', 'videohub360-theme'),
            'description' => __('Top border color for the fixed mobile bottom navigation bar.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_item_color' => array(
            'default'     => '#6b7280',
            'label'       => __('Mobile Nav Item Color', 'videohub360-theme'),
            'description' => __('Text and icon color for inactive mobile navigation items.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_active_item_color' => array(
            'default'     => '#3b82f6',
            'label'       => __('Mobile Nav Active Item Color', 'videohub360-theme'),
            'description' => __('Text and icon color for active mobile navigation items.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_badge_bg_color' => array(
            'default'     => '#3b82f6',
            'label'       => __('Mobile Nav Badge Background Color', 'videohub360-theme'),
            'description' => __('Background color for mobile navigation badges.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_badge_text_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Mobile Nav Badge Text Color', 'videohub360-theme'),
            'description' => __('Text color for mobile navigation badges.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_drawer_bg_color' => array(
            'default'     => '#ffffff',
            'label'       => __('Mobile Drawer Background Color', 'videohub360-theme'),
            'description' => __('Background color for the mobile user drawer.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_drawer_text_color' => array(
            'default'     => '#111827',
            'label'       => __('Mobile Drawer Text Color', 'videohub360-theme'),
            'description' => __('Primary text color for mobile drawer headings, links, and controls.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_drawer_muted_text_color' => array(
            'default'     => '#6b7280',
            'label'       => __('Mobile Drawer Muted Text Color', 'videohub360-theme'),
            'description' => __('Secondary and muted text color for the mobile drawer.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_drawer_border_color' => array(
            'default'     => '#e5e7eb',
            'label'       => __('Mobile Drawer Border Color', 'videohub360-theme'),
            'description' => __('Divider and avatar border color for the mobile drawer.', 'videohub360-theme'),
        ),
        'vh360_mobile_nav_overlay_color' => array(
            'default'     => '#000000',
            'label'       => __('Mobile Drawer Overlay Color', 'videohub360-theme'),
            'description' => __('Base color for the mobile drawer overlay. Overlay opacity is handled in CSS.', 'videohub360-theme'),
        ),
    );

    foreach ($mobile_nav_color_controls as $setting_id => $control_args) {
        $wp_customize->add_setting($setting_id, array(
            'default'           => $control_args['default'],
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $setting_id, array(
            'label'       => $control_args['label'],
            'description' => $control_args['description'],
            'section'     => 'vh360_mobile_nav_colors',
        )));
    }

    // Community Menu Typography - Font family, size, weight
    $wp_customize->add_section('vh360_community_menu_typography', array(
        'title'       => __('Community Menu - Typography', 'videohub360-theme'),
        'panel'       => 'vh360_header_navigation',
        'priority'    => 53,
        'description' => __('Customize typography for the Community Menu.', 'videohub360-theme'),
    ));

    // Typography - Font Family
    $wp_customize->add_setting('vh360_community_menu_font_family', array(
        'default'           => '',
        'sanitize_callback' => 'vh360_sanitize_community_menu_font_family',
        'transport'         => 'postMessage',
    ));

    $font_choices = array_merge(
        array('' => __('Inherit (Body)', 'videohub360-theme')),
        vh360_get_font_choices()
    );
    $wp_customize->add_control('vh360_community_menu_font_family', array(
        'label'       => __('Font Family', 'videohub360-theme'),
        'description' => __('Optional font family override for Community Menu. Leave as "Inherit" to use body font.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_typography',
        'type'        => 'select',
        'choices'     => $font_choices,
    ));

    // Typography - Font Size
    $wp_customize->add_setting('vh360_community_menu_font_size', array(
        'default'           => 15,
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_community_menu_font_size', array(
        'label'       => __('Font Size (px)', 'videohub360-theme'),
        'description' => __('Font size for menu items.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ));

    // Typography - Font Weight
    $wp_customize->add_setting('vh360_community_menu_font_weight', array(
        'default'           => 500,
        'sanitize_callback' => 'vh360_sanitize_community_menu_font_weight',
        'transport'         => 'postMessage',
    ));

    $wp_customize->add_control('vh360_community_menu_font_weight', array(
        'label'       => __('Font Weight', 'videohub360-theme'),
        'description' => __('Font weight for menu items.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu_typography',
        'type'        => 'select',
        'choices'     => array(
            400 => __('400 (Normal)', 'videohub360-theme'),
            500 => __('500 (Medium)', 'videohub360-theme'),
            600 => __('600 (Semi-Bold)', 'videohub360-theme'),
            700 => __('700 (Bold)', 'videohub360-theme'),
        ),
    ));
}
add_action('customize_register', 'videohub360_theme_customize_register');

/**
 * Assign additional sections to panels for better Customizer organization.
 *
 * This runs after core and custom sections have been registered.
 *
 * @param WP_Customize_Manager $wp_customize
 */
function vh360_customize_assign_panels($wp_customize) {
    // ==========================================
    // HEADER & NAVIGATION PANEL
    // ==========================================
    $header_nav_panel = $wp_customize->get_panel('vh360_header_navigation');
    
    if ($header_nav_panel) {
        // Site Identity section (WordPress core)
        $site_identity = $wp_customize->get_section('title_tagline');
        if ($site_identity) {
            $site_identity->panel    = 'vh360_header_navigation';
            $site_identity->priority = 10;
        }

        // Branding section (Logo Settings)
        $branding = $wp_customize->get_section('vh360_branding');
        if ($branding) {
            $branding->panel    = 'vh360_header_navigation';
            $branding->priority = 20;
        }

        // Main Header Settings (Site Header)
        $main_header = $wp_customize->get_section('vh360_main_header_settings');
        if ($main_header) {
            $main_header->panel    = 'vh360_header_navigation';
            $main_header->priority = 30;
        }

        // Page header settings (Template Headers)
        $template_headers = $wp_customize->get_section('vh360_header_settings');
        if ($template_headers) {
            $template_headers->panel    = 'vh360_header_navigation';
            $template_headers->priority = 40;
        }
    }

    // ==========================================
    // GLOBAL DESIGN PANEL
    // ==========================================
    $global_panel = $wp_customize->get_panel('vh360_global_design');
    
    if ($global_panel) {
        // Colors section (vh360_colors - our custom color system)
        $colors = $wp_customize->get_section('vh360_colors');
        if ($colors) {
            $colors->panel    = 'vh360_global_design';
            $colors->priority = 10;
        }

        // Typography section
        $typography = $wp_customize->get_section('vh360_typography');
        if ($typography) {
            $typography->panel    = 'vh360_global_design';
            $typography->priority = 20;
        }

        // Layout / Sidebar section (already assigned in sidebar-controls.php)
        // Just ensure priority is correct
        $sidebar = $wp_customize->get_section('vh360_sidebar_settings');
        if ($sidebar) {
            $sidebar->panel    = 'vh360_global_design';
            $sidebar->priority = 35;
        }

        // Footer Settings section
        $footer = $wp_customize->get_section('vh360_footer_settings');
        if ($footer) {
            $footer->panel    = 'vh360_global_design';
            $footer->priority = 40;
        }
    }

    // ==========================================
    // COMMUNITY & ACTIVITY FEED PANEL
    // ==========================================
    $community_panel = $wp_customize->get_panel('vh360_community_activity');
    
    if ($community_panel) {
        // Activity Feed Design section
        $activity_feed = $wp_customize->get_section('vh360_activity_feed_design');
        if ($activity_feed) {
            $activity_feed->panel    = 'vh360_community_activity';
            $activity_feed->priority = 10;
        }

        // Activity Sidebar section
        $activity_sidebar = $wp_customize->get_section('vh360_activity_sidebar');
        if ($activity_sidebar) {
            $activity_sidebar->panel    = 'vh360_community_activity';
            $activity_sidebar->priority = 20;
        }
    }

    // ==========================================
    // AUTHENTICATION PAGES PANEL
    // ==========================================
    $auth_panel = $wp_customize->get_panel('vh360_auth_pages');
    
    if ($auth_panel) {
        // All authentication-related sections
        $auth_sections = array(
            // Global shared design
            'vh360_auth_pages_design'              => 10,
            // Page/form content
            'vh360_login_content'                  => 20,
            'vh360_register_content'               => 30,
            'vh360_registration_landing_content'   => 40,
            'vh360_professional_register_content'  => 50,
            'vh360_instructor_register_content'    => 60,
            'vh360_client_register_content'        => 70,
            'vh360_lost_password_content'          => 80,
            'vh360_reset_password_content'         => 90,
            // Registration settings
            'vh360_registration_fields'            => 100,
            'vh360_registration_notifications'     => 110,
            // Login redirect settings
            'vh360_login_redirects'                => 120,
        );

        foreach ($auth_sections as $section_id => $priority) {
            $section = $wp_customize->get_section($section_id);
            if ($section) {
                $section->panel    = 'vh360_auth_pages';
                $section->priority = $priority;
            }
        }
    }

    // ==========================================
    // SITE BEHAVIOR / ADVANCED PANEL
    // ==========================================
    $behavior_panel = $wp_customize->get_panel('vh360_site_behavior');
    
    if ($behavior_panel) {
        // Author Template Mode section (routing/logic)
        $author_template = $wp_customize->get_section('vh360_author_template');
        if ($author_template) {
            $author_template->panel    = 'vh360_site_behavior';
            $author_template->priority = 10;
        }

        // Global Settings section (behavior settings)
        $global_settings = $wp_customize->get_section('vh360_global_settings');
        if ($global_settings) {
            $global_settings->panel    = 'vh360_site_behavior';
            $global_settings->priority = 20;
        }

        // Media Settings section (YouTube playback behavior)
        $media_settings = $wp_customize->get_section('vh360_media_settings');
        if ($media_settings) {
            $media_settings->panel    = 'vh360_site_behavior';
            $media_settings->priority = 30;
        }
    }
}
add_action('customize_register', 'vh360_customize_assign_panels', 20);



/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function videohub360_theme_customize_partial_blogname() {
    bloginfo('name');
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function videohub360_theme_customize_partial_blogdescription() {
    bloginfo('description');
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function videohub360_theme_customize_preview_js() {
    wp_enqueue_script('videohub360-theme-customizer', get_template_directory_uri() . '/assets/js/customizer.js', array('customize-preview'), wp_get_theme()->get('Version'), true);
    
    // Enqueue new customizer preview script
    wp_enqueue_script('vh360-customizer-preview', get_template_directory_uri() . '/assets/js/customizer-preview.js', array('customize-preview', 'jquery'), wp_get_theme()->get('Version'), true);
}
add_action('customize_preview_init', 'videohub360_theme_customize_preview_js');

/**
 * Output custom CSS based on customizer settings
 */
function videohub360_theme_customizer_css() {
    // Note: Primary and secondary colors are now handled by dynamic-css.php using vh360_* theme mods
    // This function only outputs container width which is not included in dynamic-css.php
    $container_width = get_theme_mod('videohub360_theme_container_width', '1280');

    ?>
    <style type="text/css">
        :root {
            --max-width: <?php echo esc_attr($container_width); ?>px;
        }
    </style>
    <?php
}
add_action('wp_head', 'videohub360_theme_customizer_css');

/**
 * Sanitize Community Menu Font Family
 *
 * Allows empty string (inherit) or valid font choice
 *
 * @param string $input Font family value.
 * @return string Sanitized font family.
 */
function vh360_sanitize_community_menu_font_family($input) {
    // Allow empty string (inherit)
    if ($input === '') {
        return '';
    }
    
    // Validate against font choices
    $valid = array_keys(vh360_get_font_choices());
    return in_array($input, $valid, true) ? $input : '';
}

/**
 * Sanitize Community Menu Font Weight
 *
 * @param int $input Font weight value.
 * @return int Sanitized font weight.
 */
function vh360_sanitize_community_menu_font_weight($input) {
    $allowed = array(400, 500, 600, 700);
    return in_array((int) $input, $allowed, true) ? (int) $input : 500;
}

/**
 * Sanitize Header Menu Font Family
 *
 * @param string $input Font family value.
 * @return string Sanitized font family.
 */
function vh360_sanitize_header_menu_font_family($input) {
    // Allow empty string (inherit)
    if ($input === '') {
        return '';
    }
    
    // Validate against font choices
    $valid = array_keys(vh360_get_font_choices());
    return in_array($input, $valid, true) ? $input : '';
}

/**
 * Sanitize Header Menu Font Weight
 *
 * @param int $input Font weight value.
 * @return int Sanitized font weight.
 */
function vh360_sanitize_header_menu_font_weight($input) {
    $allowed = array(400, 500, 600, 700);
    return in_array((int) $input, $allowed, true) ? (int) $input : 500;
}

/**
 * Sanitize Header Menu Text Transform
 *
 * @param string $input Text transform value.
 * @return string Sanitized text transform.
 */
function vh360_sanitize_header_menu_text_transform($input) {
    $allowed = array('none', 'uppercase', 'capitalize');
    return in_array($input, $allowed, true) ? $input : 'none';
}

/**
 * Sanitize Header Menu Letter Spacing
 *
 * @param mixed $input Letter spacing value.
 * @return float Sanitized letter spacing (clamped to -2 to 5).
 */
function vh360_sanitize_header_menu_letter_spacing($input) {
    $value = is_numeric($input) ? floatval($input) : 0;
    // Clamp to reasonable range
    return max(-2, min(5, $value));
}
