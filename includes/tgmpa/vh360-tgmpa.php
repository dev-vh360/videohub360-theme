<?php
/**
 * TGMPA config for VideoHub360.
 *
 * Bundles required plugins inside the theme and shows the standard
 * "Install Required Plugins" admin screen.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'tgmpa_register', 'vh360_register_required_plugins' );

function vh360_register_required_plugins() {

    $plugins = array(
        array(
            'name'               => 'Elementor',
            'slug'               => 'elementor',
            'required'           => true,
            'version'            => '3.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'Contact Form 7',
            'slug'               => 'contact-form-7',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'WooCommerce',
            'slug'               => 'woocommerce',
            'required'           => false,
            'version'            => '5.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VH360 PWA & App',
            'slug'     => 'vh360-pwa-app',
            'source'   => get_template_directory() . '/bundled-plugins/vh360-pwa-app.zip',
            'required' => false,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Core',
            'slug'     => 'videohub360',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-core.zip',
            'required' => true,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'VideoHub360 Studio',
            'slug'               => 'videohub360-studio',
            'source'             => get_template_directory() . '/bundled-plugins/videohub360-studio.zip',
            'required'           => true,
            'version'            => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Community',
            'slug'     => 'videohub360-community',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-community.zip',
            'required' => false,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Starter Sites',
            'slug'     => 'videohub360-starter-sites',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-starter-sites.zip',
            'required' => false,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Memberships',
            'slug'     => 'videohub360-memberships',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-memberships.zip',
            'required' => false,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
    );

    $config = array(
        'id'           => 'videohub360',
        'default_path' => '',
        'menu'         => 'tgmpa-install-plugins',
        'parent_slug'  => 'themes.php',
        'capability'   => 'edit_theme_options',
        'has_notices'  => true,
        'dismissable'  => true,    // Allow administrators to dismiss combined required/recommended notices.
        'dismiss_msg'  => '',
        'is_automatic' => false,   // Do not automatically activate plugins after installation.
        'message'      => '',
    );

    tgmpa( $plugins, $config );
}
