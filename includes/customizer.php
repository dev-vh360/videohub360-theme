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
    $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';

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


    // Add Header & Branding panel for site identity, logo settings, site header, and template headers
    $wp_customize->add_panel('vh360_header_branding', array(
        'title'       => __('Header & Branding', 'videohub360-theme'),
        'priority'    => 30,
        'description' => __('Configure site identity, logo, navigation, and template headers.', 'videohub360-theme'),
    ));

    // Add Global Design panel for layout, colors, and typography
    $wp_customize->add_panel('vh360_global_design', array(
        'title'       => __('Global Design', 'videohub360-theme'),
        'priority'    => 40,
        'description' => __('Global design options including colors, typography, and layout.', 'videohub360-theme'),
    ));

    // Create Components Panel for component-specific overrides
    $wp_customize->add_panel('vh360_components', array(
        'title'       => __('Component Overrides', 'videohub360-theme'),
        'description' => __('Override global design settings for specific components. Leave blank to use global colors.', 'videohub360-theme'),
        'priority'    => 41,
    ));

    // Attach the core Colors section to the Global Design panel if it exists
    $colors_section = $wp_customize->get_section('colors');
    if ($colors_section) {
        $colors_section->panel    = 'vh360_global_design';
        $colors_section->priority = 10;
        if (empty($colors_section->title)) {
            $colors_section->title = __('Colors', 'videohub360-theme');
        }
    }

    // Note: Theme color settings are now in color-controls.php with vh360_* prefix
    // to consolidate all color settings in one place and work with color presets

    // Layout options
    $wp_customize->add_section('videohub360_theme_layout', array(
        'title'    => __('Layout Options', 'videohub360-theme'),
        'priority' => 40,
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

    // Community Menu Settings (moved to Components panel)
    $wp_customize->add_section('vh360_community_menu', array(
        'title'       => __('Community Menu', 'videohub360-theme'),
        'panel'       => 'vh360_components',
        'priority'    => 10,
        'description' => __('Customize the community menu. Leave color fields empty to use global theme colors. To enable Community Menu, select it under Header & Branding > Site Header > Navigation Style.', 'videohub360-theme'),
    ));

    // Show to Logged-Out Users
    $wp_customize->add_setting('vh360_community_menu_logged_out', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control('vh360_community_menu_logged_out', array(
        'label'       => __('Show to Logged-Out Users', 'videohub360-theme'),
        'description' => __('Display the Community Menu for visitors who are not logged in.', 'videohub360-theme'),
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
        'type'        => 'checkbox',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
    )));

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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
        'type'        => 'select',
        'choices'     => array(
            400 => __('400 (Normal)', 'videohub360-theme'),
            500 => __('500 (Medium)', 'videohub360-theme'),
            600 => __('600 (Semi-Bold)', 'videohub360-theme'),
            700 => __('700 (Bold)', 'videohub360-theme'),
        ),
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
        'section'     => 'vh360_community_menu',
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
        'section'     => 'vh360_community_menu',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 220,
            'max'  => 360,
            'step' => 10,
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
    // Assign sections to Header & Branding panel
    $header_branding_panel = $wp_customize->get_panel('vh360_header_branding');

    if ($header_branding_panel) {
        // Site Identity section (WordPress core)
        $site_identity = $wp_customize->get_section('title_tagline');
        if ($site_identity) {
            $site_identity->panel    = 'vh360_header_branding';
            $site_identity->priority = 10;
        }

        // Branding section (Logo Settings)
        $branding = $wp_customize->get_section('vh360_branding');
        if ($branding) {
            $branding->panel    = 'vh360_header_branding';
            $branding->priority = 15;
        }

        // Main Header Settings (Site Header)
        $main_header = $wp_customize->get_section('vh360_main_header_settings');
        if ($main_header) {
            $main_header->panel    = 'vh360_header_branding';
            $main_header->priority = 20;
        }

        // Page header settings (Template Headers)
        $template_headers = $wp_customize->get_section('vh360_header_settings');
        if ($template_headers) {
            $template_headers->panel    = 'vh360_header_branding';
            $template_headers->priority = 25;
        }
    }

    // Assign sections to Global Design panel
    $global_panel = $wp_customize->get_panel('vh360_global_design');

    if ($global_panel) {
        // Typography section
        $typography = $wp_customize->get_section('vh360_typography');
        if ($typography) {
            $typography->panel    = 'vh360_global_design';
            $typography->priority = 20;
        }
    }

    // Authentication Pages panel
    $wp_customize->add_panel('vh360_auth_pages', array(
        'title'       => __('Authentication Pages', 'videohub360-theme'),
        'priority'    => 50,
        'description' => __('Customize login and registration pages.', 'videohub360-theme'),
    ));

    // Helper to attach a section to the auth panel.
    $auth_sections = array(
        'vh360_login_content',
        'vh360_login_design',
        'vh360_register_content',
        'vh360_register_design',
        'vh360_registration_fields',
        'vh360_registration_notifications',
        'vh360_lost_password_content',
        'vh360_lost_password_design',
        'vh360_reset_password_content',
        'vh360_reset_password_design',
    );

    foreach ($auth_sections as $section_id) {
        $section = $wp_customize->get_section($section_id);
        if ($section) {
            $section->panel = 'vh360_auth_pages';
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
