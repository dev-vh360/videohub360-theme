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
            'required'           => true,
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'WooCommerce',
            'slug'               => 'woocommerce',
            'required'           => true,
            'version'            => '5.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'PWA App',
            'slug'     => 'vh360-pwa-app',
            'source'   => get_template_directory() . '/bundled-plugins/vh360-pwa-app.zip',
            'required' => true,
            'version'  => '', // Optional.
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Core',
            'slug'     => 'videohub360',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-core.zip',
            'required' => true,
            'version'  => '', // Optional.
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Community',
            'slug'     => 'videohub360-community',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-community.zip',
            'required' => true,
            'version'  => '', // Optional.
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Starter Sites',
            'slug'     => 'videohub360-starter-sites',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-starter-sites.zip',
            'required' => true,
            'version'  => '1.0.0',
            'force_activation'   => true,
            'force_deactivation' => false,
        ),
        array(
            'name'     => 'VideoHub360 Memberships',
            'slug'     => 'videohub360-memberships',
            'source'   => get_template_directory() . '/bundled-plugins/videohub360-memberships.zip',
            'required' => true,
            'version'  => '1.0.0',
            'force_activation'   => false,
            'force_deactivation' => false,
        ),
        array(
            'name'               => 'VideoHub360 Affiliates',
            'slug'               => 'videohub360-affiliates',
            'source'             => get_template_directory() . '/bundled-plugins/videohub360-affiliates.zip',
            'required'           => false,
            'version'            => '1.0.0',
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
        'dismissable'  => false,   // Do not allow dismissing when plugins are required.
        'dismiss_msg'  => '',
        'is_automatic' => true,    // Automatically activate plugins after installation.
        'message'      => '',
    );

    tgmpa( $plugins, $config );
}
