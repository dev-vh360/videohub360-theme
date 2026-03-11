<?php

// Disable block-based Widgets editor in admin only.
// Reason: Prevents wp-editor enqueue notice on Widgets screen caused by Elementor/WooCommerce (WP 5.8+).
add_action('after_setup_theme', function () {
    if (is_admin()) {
        remove_theme_support('widgets-block-editor');
    }
}, 100);


/**
 * Videohub360 Theme Functions
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define Theme Constants
 */
define('VH360_THEME_VERSION', wp_get_theme()->get('Version'));
define('VH360_THEME_DIR', get_template_directory());
define('VH360_THEME_URI', get_template_directory_uri());

/**
 * Debug logging helper for VideoHub360 theme
 * Only logs when WP_DEBUG is enabled
 *
 * @param string $message Log message
 * @param array $context Optional context data
 */
if (!function_exists('vh360_debug_log')) {
    function vh360_debug_log($message, $context = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !function_exists('error_log')) {
            return;
        }

        if (!empty($context)) {
            $message .= ': ' . print_r($context, true);
        }

        error_log($message);
    }
}

/**
 * Theme setup
 */
function videohub360_theme_setup() {
    // Load theme textdomain for translation support
    load_theme_textdomain('videohub360-theme', get_template_directory() . '/languages');

    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails
    add_theme_support('post-thumbnails');

    // Set custom thumbnail sizes
    add_image_size('videohub360-video-thumb', 480, 270, true); // 16:9 ratio
    add_image_size('videohub360-video-large', 1280, 720, true);

    // Add new image sizes for upcoming features
    add_image_size('vh360-profile-avatar', 300, 300, true);
    add_image_size('vh360-group-cover', 1200, 400, true);
    add_image_size('vh360-gallery-thumb', 400, 400, true);

    // Register navigation menus
    register_nav_menus(array(
        'primary'   => esc_html__('Primary Menu', 'videohub360-theme'),
        'footer'    => esc_html__('Footer Menu', 'videohub360-theme'),
        'dashboard' => esc_html__('Dashboard Menu', 'videohub360-theme'),
        'user-menu' => esc_html__('User Menu (Dropdown)', 'videohub360-theme'),
        'vh360_mobile_bottom' => esc_html__('Mobile Bottom Nav', 'videohub360-theme'),
        'community' => esc_html__('Community Menu (Left Rail)', 'videohub360-theme'),
        'vh360_mobile_drawer' => esc_html__('Mobile User Drawer', 'videohub360-theme'),
    ));

    // Switch default core markup to output valid HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Add support for custom header
    add_theme_support('custom-header', array(
        'default-image'      => '',
        'width'              => 1920,
        'height'             => 500,
        'flex-height'        => true,
        'flex-width'         => true,
        'header-text'        => true,
        'default-text-color' => '1f2937',
    ));

    // Add support for custom background
    add_theme_support('custom-background', array(
        'default-color' => 'ffffff',
    ));

    // Add support for editor styles
    add_theme_support('editor-styles');

    // Add support for responsive embeds
    add_theme_support('responsive-embeds');

    // Add support for wide and full alignment
    add_theme_support('align-wide');

    // Content width
    if (!isset($content_width)) {
        $content_width = 1280;
    }
}
add_action('after_setup_theme', 'videohub360_theme_setup');

/**
 * Register Professional Role
 *
 * Creates a custom role for professionals with event creation capabilities.
 * This role is assigned to users who register as professionals through business registration.
 */
function vh360_register_professional_role() {
    // Check if role already exists
    if (get_role('vh360_professional')) {
        return;
    }

    // Get subscriber capabilities as a base
    $subscriber = get_role('subscriber');
    $capabilities = $subscriber ? $subscriber->capabilities : array('read' => true);

    // Add event creation capability
    $capabilities['vh360_create_events'] = true;

    // Register the professional role
    add_role(
        'vh360_professional',
        __('Professional', 'videohub360-theme'),
        $capabilities
    );
}
add_action('init', 'vh360_register_professional_role');

/**
 * Set default comment options on theme activation
 *
 * Automatically enables login-only commenting for a community-focused platform.
 * Admins can still change these settings later in WP Admin → Settings → Discussion.
 */
add_action('after_switch_theme', function () {
    // Default platform behavior: require login to comment
    update_option('comment_registration', 1);

    // Keep WP's guest-comment requirements consistent if it's ever enabled later
    update_option('require_name_email', 1);

    // Register professional role on theme activation
    vh360_register_professional_role();
});

/**
 * Add body class for Community Menu
 *
 * @param array $classes Existing body classes.
 * @return array Modified body classes.
 */
function vh360_community_menu_body_class($classes) {
    if (vh360_show_community_menu()) {
        $classes[] = 'has-community-menu';

        // Add compact mode class if enabled globally OR forced for specific pages
        if (get_theme_mod('vh360_community_menu_compact', 0) || vh360_force_compact_community_menu()) {
            $classes[] = 'community-menu-compact';
        }
    }
    return $classes;
}
add_filter('body_class', 'vh360_community_menu_body_class');

/**
 * Add body class for unified search mode
 *
 * When search results are not grouped, add a class to the body element
 * for CSS styling and to prevent hidden filter tabs from being focusable.
 *
 * @param array $classes Existing body classes
 * @return array Modified body classes
 */
function vh360_search_mode_body_class($classes) {
    $group_results = get_theme_mod('vh360_search_group_results', true);
    if (!$group_results) {
        $classes[] = 'vh360-search-unified';
    }
    return $classes;
}
add_filter('body_class', 'vh360_search_mode_body_class');

/**
 * Required bundled plugins (install/activate).
 */
require_once VH360_THEME_DIR . '/includes/tgmpa/class-tgm-plugin-activation.php';
require_once VH360_THEME_DIR . '/includes/tgmpa/vh360-tgmpa.php';

/**
 * Elementor dependency check
 */
require_once VH360_THEME_DIR . '/includes/elementor-dependency-check.php';

/**
 * Authentication system
 * Note: auth-helpers.php must be loaded first as it contains base functions used by other auth files
 */
require_once VH360_THEME_DIR . '/includes/auth-helpers.php';
require_once VH360_THEME_DIR . '/includes/auth-filters.php';
require_once VH360_THEME_DIR . '/includes/community-gate.php';
require_once VH360_THEME_DIR . '/includes/wp-login-redirect.php';


/**
 * Enqueue scripts and styles
 */
function videohub360_theme_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style(
        'videohub360-theme-style',
        get_stylesheet_uri(),
        array(),
        VH360_THEME_VERSION
    );

    // Sidebar layout styles
    wp_enqueue_style(
        'vh360-sidebar-layout',
        VH360_THEME_URI . '/assets/css/sidebar-layout.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    // Community Menu styles - always enqueue (visibility controlled by body class)
    wp_enqueue_style(
        'vh360-community-menu',
        VH360_THEME_URI . '/assets/css/community-menu.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    // Mobile-only UX hardening (disable landscape usage overlay, safe areas, etc.)
    // Only enqueue if orientation lock is enabled via Customizer
    $vh360_enable_orientation_lock = (bool) get_theme_mod('vh360_enable_orientation_lock', 0);
    if ($vh360_enable_orientation_lock) {
        wp_enqueue_style(
            'vh360-mobile-orientation-lock',
            VH360_THEME_URI . '/assets/css/mobile-orientation-lock.css',
            array('videohub360-theme-style'),
            VH360_THEME_VERSION
        );
    }

    // Utility classes for JavaScript-generated UI elements
    wp_enqueue_style(
        'vh360-utilities',
        VH360_THEME_URI . '/assets/css/utilities.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    // Enqueue custom scripts
    wp_enqueue_script(
        'videohub360-theme-script',
        VH360_THEME_URI . '/assets/js/theme.js',
        array(),
        VH360_THEME_VERSION,
        true
    );

    // Enqueue community script for posts and likes
    wp_enqueue_script(
        'vh360-community-script',
        VH360_THEME_URI . '/assets/js/community.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Enqueue community uploads CSS (required for lightbox overlay)
    wp_enqueue_style(
        'vh360-community-uploads',
        VH360_THEME_URI . '/assets/css/community-uploads.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    wp_enqueue_script(
        'vh360-mentions-script',
        VH360_THEME_URI . '/assets/js/vh360-mentions.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Localize community script with AJAX data and strings
    wp_localize_script('vh360-community-script', 'vh360Community', array(
        'ajaxurl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('vh360_like_post'),
        'shareNonce'   => wp_create_nonce('vh360_share_post'),
        'commentNonce' => wp_create_nonce('vh360_comment_post'),
        'mentionNonce' => wp_create_nonce('vh360_user_mentions'),
        'postActionsNonce'    => wp_create_nonce('vh360_post_actions'),
        'commentActionsNonce' => wp_create_nonce('vh360_comment_actions'),
        'currentUserAvatar' => get_avatar_url(get_current_user_id(), array('size' => 28)),
        'strings'      => array(
            'error'              => __('An error occurred. Please try again.', 'videohub360-theme'),
            'shared'             => __('Post shared successfully.', 'videohub360-theme'),
            'editPostPrompt'     => __('Edit your post:', 'videohub360-theme'),
            'editCommentPrompt'  => __('Edit your comment:', 'videohub360-theme'),
            'confirmDeletePost'  => __('Are you sure you want to delete this post?', 'videohub360-theme'),
            'confirmDeleteComment' => __('Are you sure you want to delete this comment?', 'videohub360-theme'),
            'photo'              => __('Photo', 'videohub360-theme'),
            'video'              => __('Video', 'videohub360-theme'),
            /* translators: 1: file type (Photo or Video), 2: maximum size in MB */
            'fileTooLarge'       => __('%1$s exceeds maximum size of %2$d MB.', 'videohub360-theme'),
            'close'              => __('Close', 'videohub360-theme'),
        ),
        'i18n' => array(
            'writeReply' => __('Write a reply...', 'videohub360-theme'),
            'sendReply'  => __('Send reply', 'videohub360-theme'),
        ),
    ));

    // Threaded comments script for singular views
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    /*
     * Additional UI enhancements for the activity feed.
     *
     * This stylesheet adds the Explore/My Feed tabs, responsive grid layout for the feed
     * and sidebar, and styles for the trending topics and recommended profiles widgets.
     */
    wp_enqueue_style(
        'vh360-feed-ui-enhancements',
        VH360_THEME_URI . '/assets/css/feed-ui-enhancements.css',
        array(),
        VH360_THEME_VERSION
    );

    // Live Room stylesheet (conditional - only on Live Room pages)
    global $post;
    if ($post && get_post_type($post) === 'videohub360') {
        $context = get_post_meta($post->ID, '_vh360_context', true);
        if ($context === 'live_room') {
            wp_enqueue_style(
                'vh360-live-room',
                VH360_THEME_URI . '/assets/css/live-room.css',
                array('videohub360-theme-style'),
                VH360_THEME_VERSION
            );
        }
    }

    // Enqueue connections page styles
    if (is_page_template('template-connections.php')) {
        wp_enqueue_style(
            'vh360-profiles',
            VH360_THEME_URI . '/assets/css/profiles.css',
            array('videohub360-theme-style'),
            VH360_THEME_VERSION
        );
        wp_enqueue_style(
            'vh360-connections',
            VH360_THEME_URI . '/assets/css/connections.css',
            array('vh360-profiles'),
            VH360_THEME_VERSION
        );
    }

    // Enqueue follow system script
    wp_enqueue_script(
        'vh360-follow-system',
        VH360_THEME_URI . '/assets/js/follow-system.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    wp_localize_script('vh360-follow-system', 'vh360Follow', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'followText' => __('Follow', 'videohub360-theme'),
        'unfollowText' => __('Unfollow', 'videohub360-theme'),
        'errorText' => __('An error occurred. Please try again.', 'videohub360-theme'),
    ));

    // Enqueue notification script for logged-in users
    if (is_user_logged_in()) {
        // Mobile bottom navigation + user drawer (logged-in only)
        wp_enqueue_style(
            'vh360-mobile-bottom-nav',
            VH360_THEME_URI . '/assets/css/mobile-bottom-nav.css',
            array('videohub360-theme-style'),
            VH360_THEME_VERSION
        );

        wp_enqueue_script(
            'vh360-mobile-bottom-nav',
            VH360_THEME_URI . '/assets/js/mobile-bottom-nav.js',
            array(),
            VH360_THEME_VERSION,
            true
        );

        wp_enqueue_style(
            'vh360-notifications',
            VH360_THEME_URI . '/assets/css/notifications.css',
            array('videohub360-theme-style'),
            VH360_THEME_VERSION
        );

        wp_enqueue_script(
            'vh360-notifications',
            VH360_THEME_URI . '/assets/js/notifications.js',
            array('jquery'),
            VH360_THEME_VERSION,
            true
        );

        wp_localize_script('vh360-notifications', 'vh360Notifications', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_notifications'),
            'pollInterval' => 30000, // 30 seconds
            'i18n' => array(
                'markAllRead' => __('Mark all as read', 'videohub360-theme'),
                'viewAll' => __('View all notifications', 'videohub360-theme'),
                'noNotifications' => __('No notifications yet', 'videohub360-theme'),
                'error' => __('An error occurred. Please try again.', 'videohub360-theme'),
                'ago' => __('ago', 'videohub360-theme'),
            ),
        ));

        // Enqueue direct messages on dashboard page
        if (is_page_template('template-dashboard.php')) {
            wp_enqueue_style(
                'vh360-direct-messages',
                VH360_THEME_URI . '/assets/css/direct-messages.css',
                array('videohub360-theme-style'),
                VH360_THEME_VERSION
            );

            wp_enqueue_script(
                'vh360-direct-messages',
                VH360_THEME_URI . '/assets/js/direct-messages.js',
                array('jquery'),
                VH360_THEME_VERSION,
                true
            );

            wp_localize_script('vh360-direct-messages', 'vh360DirectMessages', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vh360_dm_nonce'),
                'currentUserId' => get_current_user_id(),
                'pollInterval' => 10000, // 10 seconds
                'i18n' => array(
                    'send' => __('Send', 'videohub360-theme'),
                    'sending' => __('Sending...', 'videohub360-theme'),
                    'typeMessage' => __('Type a message...', 'videohub360-theme'),
                    'searchUsers' => __('Search users...', 'videohub360-theme'),
                    'noConversations' => __('No conversations yet', 'videohub360-theme'),
                    'startConversation' => __('Search for a user to start a conversation', 'videohub360-theme'),
                    'noMessages' => __('No messages yet', 'videohub360-theme'),
                    'selectConversation' => __('Select a conversation to view messages', 'videohub360-theme'),
                    'deleteConfirm' => __('Are you sure you want to delete this conversation?', 'videohub360-theme'),
                    'characterLimit' => __('Character limit:', 'videohub360-theme'),
                    'error' => __('An error occurred. Please try again.', 'videohub360-theme'),
                    'messageSent' => __('Message sent', 'videohub360-theme'),
                    'conversationDeleted' => __('Conversation deleted', 'videohub360-theme'),
                ),
            ));

            // Push Notifications (requires VH360 PWA & App plugin capability)
            if ( current_user_can( 'vh360_send_push' ) ) {

                wp_enqueue_script(
                    'vh360-push-notifications',
                    VH360_THEME_URI . '/assets/js/push-notifications.js',
                    array('jquery'),
                    VH360_THEME_VERSION,
                    true
                );

                wp_localize_script('vh360-push-notifications', 'vh360PushNotifications', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vh360_pwa_push_frontend'),
                    'i18n' => array(
                        'sending' => __('Sending...', 'videohub360-theme'),
                        'sent' => __('Notification sent!', 'videohub360-theme'),
                        'error' => __('An error occurred. Please try again.', 'videohub360-theme'),
                    ),
                ));
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'videohub360_theme_scripts');

/**
 * Append Push Notifications item to the Dashboard menu output when a custom menu is assigned
 * to the "dashboard" theme location.
 *
 * When a menu is assigned, template-parts/dashboard/nav.php uses wp_nav_menu(), and the
 * hard-coded fallback items (including Push Notifications) are not rendered.
 */
function vh360_append_push_notifications_to_dashboard_menu( $items, $args ) {
    if ( empty( $args->theme_location ) || $args->theme_location !== 'dashboard' ) {
        return $items;
    }

    /* Only show for users with vh360_send_push capability. */
    if ( ! current_user_can( 'vh360_send_push' ) ) {
        return $items;
    }

    /* If already present (manually added), don't duplicate. */
    if ( stripos( $items, '#push-notifications' ) !== false || stripos( $items, 'data-tab="push-notifications"' ) !== false ) {
        return $items;
    }

    $label = esc_html__( 'Push Notifications', 'videohub360-theme' );
    $icon  = '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"></path><path d="M22 2L15 22l-4-9-9-4 20-7z"></path></svg>';

    $items .= '<li class="vh360-dashboard-nav-item"><a href="#push-notifications" class="vh360-dashboard-nav-link vh360-dashboard-tab" data-tab="push-notifications">' . $icon . '<span class="vh360-dashboard-nav-text">' . $label . '</span></a></li>';

    return $items;
}
add_filter( 'wp_nav_menu_items', 'vh360_append_push_notifications_to_dashboard_menu', 20, 2 );



/**
 * Enqueue header assets
 */
function vh360_enqueue_header_assets() {
    // Enqueue header layout CSS
    wp_enqueue_style(
        'vh360-header-layout',
        VH360_THEME_URI . '/assets/css/header-layout.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    // Enqueue header navigation JavaScript
    wp_enqueue_script(
        'vh360-header-navigation',
        VH360_THEME_URI . '/assets/js/header-navigation.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Enqueue centered search bar assets if search is enabled
    $show_search = get_theme_mod('vh360_show_search_icon', true);
    if ($show_search) {
        wp_enqueue_style(
            'vh360-search-bar-centered',
            VH360_THEME_URI . '/assets/css/search-bar-centered.css',
            array('videohub360-theme-style'),
            VH360_THEME_VERSION
        );

        wp_enqueue_script(
            'vh360-search-bar-centered',
            VH360_THEME_URI . '/assets/js/search-bar-centered.js',
            array('jquery'),
            VH360_THEME_VERSION,
            true
        );

        // Localize search bar script
        wp_localize_script('vh360-search-bar-centered', 'vh360SearchBar', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_search_nonce'),
            'defaultAvatar' => get_avatar_url(0),
            'groupResults' => (bool) get_theme_mod('vh360_search_group_results', true),
            'availableTypes' => vh360_get_available_search_type_keys(),
            'i18n' => array(
                'videos' => __('Videos', 'videohub360-theme'),
                'members' => __('Members', 'videohub360-theme'),
                'events' => __('Events', 'videohub360-theme'),
                'galleries' => __('Galleries', 'videohub360-theme'),
                'bulletins' => __('Bulletins', 'videohub360-theme'),
                'posts' => __('Posts', 'videohub360-theme'),
                'views' => __('views', 'videohub360-theme'),
                'images' => __('images', 'videohub360-theme'),
            ),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_header_assets');

/**
 * Enqueue Community Menu toggle script on compact mode pages
 */
function vh360_enqueue_community_menu_toggle() {
    // Only enqueue if Community Menu is shown AND compact mode is active (either forced or via Customizer)
    if (!vh360_show_community_menu()) {
        return;
    }

    $is_compact = (bool) get_theme_mod('vh360_community_menu_compact', 0) || vh360_force_compact_community_menu();

    if (!$is_compact) {
        return;
    }

    wp_enqueue_script(
        'vh360-community-menu-toggle',
        VH360_THEME_URI . '/assets/js/community-menu-toggle.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Localize script for translatable strings
    wp_localize_script('vh360-community-menu-toggle', 'vh360CommunityMenuToggle', array(
        'expandLabel' => __('Expand community menu', 'videohub360-theme'),
        'collapseLabel' => __('Collapse community menu', 'videohub360-theme'),
    ));
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_community_menu_toggle');

/**
 * Register gallery scripts and styles
 */
function vh360_register_gallery_assets() {
    $settings = vh360_get_gallery_settings();

    // Register gallery CSS
    wp_register_style(
        'vh360-gallery',
        VH360_THEME_URI . '/assets/css/gallery.css',
        array(),
        VH360_THEME_VERSION
    );

    // Register gallery dashboard CSS
    wp_register_style(
        'vh360-gallery-dashboard',
        VH360_THEME_URI . '/assets/css/gallery-dashboard.css',
        array(),
        VH360_THEME_VERSION
    );

    // Register Dropzone.js
    wp_register_script(
        'dropzone',
        'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js',
        array(),
        '5.9.3',
        true
    );

    wp_register_style(
        'dropzone',
        'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css',
        array(),
        '5.9.3'
    );

    // Register Sortable.js
    wp_register_script(
        'sortablejs',
        'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js',
        array(),
        '1.15.0',
        true
    );

    // Register main gallery script (for frontend gallery display)
    wp_register_script(
        'vh360-gallery-script',
        VH360_THEME_URI . '/assets/js/gallery.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Register gallery lightbox integration (custom lightbox, no external dependencies)
    wp_register_script(
        'vh360-gallery-photoswipe',
        VH360_THEME_URI . '/assets/js/gallery-photoswipe.js',
        array('jquery'),
        VH360_THEME_VERSION,
        true
    );

    // Register gallery dashboard script
    wp_register_script(
        'vh360-gallery-dashboard',
        VH360_THEME_URI . '/assets/js/gallery-dashboard.js',
        array('jquery', 'dropzone', 'sortablejs'),
        VH360_THEME_VERSION,
        true
    );

    // Get allowed file types for Dropzone
    $allowed_types = isset($settings['allowed_image_types']) ? $settings['allowed_image_types'] : array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $accepted_files = 'image/' . implode(',image/', $allowed_types);

    // Localize gallery script
    wp_localize_script('vh360-gallery-dashboard', 'vh360Gallery', array(
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('vh360_gallery_nonce'),
        'maxFileSize'    => isset($settings['max_image_size']) ? $settings['max_image_size'] : 5,
        'maxImages'      => isset($settings['max_images_per_gallery']) ? $settings['max_images_per_gallery'] : 50,
        'acceptedFiles'  => $accepted_files,
        'i18n'           => array(
            'createGallery'    => __('Create Gallery', 'videohub360-theme'),
            'editGallery'      => __('Edit Gallery', 'videohub360-theme'),
            'saveChanges'      => __('Save Changes', 'videohub360-theme'),
            'saving'           => __('Saving...', 'videohub360-theme'),
            'deleting'         => __('Deleting...', 'videohub360-theme'),
            'delete'           => __('Delete', 'videohub360-theme'),
            'noGalleries'      => __('No galleries yet', 'videohub360-theme'),
            'createFirstGallery' => __('Create your first gallery to showcase your photos!', 'videohub360-theme'),
            'uploadError'      => __('Upload failed. Please try again.', 'videohub360-theme'),
            'fileTooLarge'     => __('File is too large.', 'videohub360-theme'),
            'invalidFileType'  => __('Invalid file type.', 'videohub360-theme'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'vh360_register_gallery_assets', 5);

/**
 * Enqueue gallery dashboard assets on dashboard page
 */
function vh360_enqueue_gallery_dashboard_assets() {
    if (is_page_template('template-dashboard.php')) {
        wp_enqueue_style('dropzone');
        wp_enqueue_style('vh360-gallery-dashboard');
        wp_enqueue_script('dropzone');
        wp_enqueue_script('sortablejs');
        wp_enqueue_script('vh360-gallery-dashboard');
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_gallery_dashboard_assets', 25);

/**
 * Add crossorigin attribute to CDN scripts for better error reporting.
 * SRI integrity validation removed as CDN hashes may change and cause blocking.
 *
 * @param string $tag    The script or style tag.
 * @param string $handle The script or style handle.
 * @param string $src    The script or style source URL.
 * @return string Modified tag with crossorigin attribute.
 */
function vh360_add_cdn_crossorigin_attributes( $tag, $handle, $src ) {
    // Add crossorigin for CDN resources (helps with error reporting).
    if ( strpos( $src, 'cdnjs.cloudflare.com' ) !== false ) {
        if ( strpos( $tag, '<script' ) !== false && strpos( $tag, 'crossorigin' ) === false ) {
            $tag = str_replace( ' src=', ' crossorigin="anonymous" src=', $tag );
        }
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'vh360_add_cdn_crossorigin_attributes', 10, 3 );

/**
 * Add crossorigin attribute to CDN stylesheets.
 *
 * @param string $tag    The style tag.
 * @param string $handle The style handle.
 * @param string $href   The stylesheet URL.
 * @param string $media  The media type.
 * @return string Modified tag with crossorigin attribute.
 */
function vh360_add_cdn_style_crossorigin( $tag, $handle, $href, $media ) {
    // Add crossorigin for CDN resources.
    if ( strpos( $href, 'cdnjs.cloudflare.com' ) !== false && strpos( $tag, 'crossorigin' ) === false ) {
        $tag = str_replace( " href='", " crossorigin='anonymous' href='", $tag );
    }

    return $tag;
}
add_filter( 'style_loader_tag', 'vh360_add_cdn_style_crossorigin', 10, 4 );

/**
 * Register widget areas
 */
function videohub360_theme_widgets_init() {
    // Primary Sidebar (legacy, kept for backward compatibility)
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'videohub360-theme'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Legacy sidebar. Use specific sidebars below for better control.', 'videohub360-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    // Page Sidebar
    register_sidebar(array(
        'name'          => esc_html__('Page Sidebar', 'videohub360-theme'),
        'id'            => 'page-sidebar',
        'description'   => esc_html__('Sidebar for pages. Customize widgets specifically for page content.', 'videohub360-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    // Post Sidebar
    register_sidebar(array(
        'name'          => esc_html__('Post Sidebar', 'videohub360-theme'),
        'id'            => 'post-sidebar',
        'description'   => esc_html__('Sidebar for blog posts. Add blog-specific widgets here.', 'videohub360-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    // Product Sidebar (WooCommerce)
    if (class_exists('WooCommerce')) {
        register_sidebar(array(
            'name'          => esc_html__('Product Sidebar', 'videohub360-theme'),
            'id'            => 'product-sidebar',
            'description'   => esc_html__('Sidebar for WooCommerce product pages. Add product-related widgets.', 'videohub360-theme'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ));
    }

    // Footer widget areas
    for ($i = 1; $i <= 4; $i++) {
        register_sidebar(array(
            'name'          => sprintf(esc_html__('Footer %d', 'videohub360-theme'), $i),
            'id'            => 'footer-' . $i,
            'description'   => sprintf(esc_html__('Add widgets here to appear in footer column %d.', 'videohub360-theme'), $i),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ));
    }

    // Activity Feed Sidebar widget area
    register_sidebar(array(
        'name'          => esc_html__('Activity Feed Sidebar', 'videohub360-theme'),
        'id'            => 'activity-feed-sidebar',
        'description'   => esc_html__('Widgets for the activity feed right sidebar', 'videohub360-theme'),
        'before_widget' => '<div class="vh360-sidebar-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="vh360-widget-title">',
        'after_title'   => '</h3>',
    ));

    // Activity Feed Ad Slot widget area
    register_sidebar(array(
        'name'          => esc_html__('Activity Feed Ad Slot', 'videohub360-theme'),
        'id'            => 'activity-feed-ad',
        'description'   => esc_html__('Ad slot for the activity feed right sidebar. Use Custom HTML blocks, shortcode widgets, or ad plugin widgets.', 'videohub360-theme'),
        'before_widget' => '<div class="vh360-sidebar-widget vh360-ad-slot %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="vh360-widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'videohub360_theme_widgets_init');

/**
 * Elementor Support
 */
function videohub360_theme_elementor_support() {
    // Register Elementor locations
    if (did_action('elementor/loaded')) {
        require_once VH360_THEME_DIR . '/includes/elementor-support.php';
    }
}
add_action('after_setup_theme', 'videohub360_theme_elementor_support');

/**
 * Custom template tags
 */
require_once VH360_THEME_DIR . '/includes/template-tags.php';

/**
 * Customizer additions
 */
require_once VH360_THEME_DIR . '/includes/customizer.php';

/**
 * Customizer global controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/global-controls.php';

/**
 * Customizer color controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/color-controls.php';

/**
 * Customizer typography controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/typography-controls.php';

/**
 * Customizer branding controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/branding-controls.php';

/**
 * Customizer header controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/header-controls.php';

/**
 * Customizer form content controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/form-content-controls.php';

/**
 * Customizer auth pages controls (consolidated)
 */
require_once VH360_THEME_DIR . '/includes/customizer/auth-pages-controls.php';

/**
 * Customizer site identity controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/site-identity-controls.php';

/**
 * Customizer footer controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/footer-controls.php';

/**
 * Customizer activity sidebar controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/activity-sidebar-controls.php';

/**
 * Customizer activity feed controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/activity-feed-controls.php';

/**
 * Customizer sidebar controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/sidebar-controls.php';

/**
 * Customizer author template controls
 */
require_once VH360_THEME_DIR . '/includes/customizer/author-template-controls.php';

/**
 * Customizer media controls (YouTube playback, etc.)
 */
require_once VH360_THEME_DIR . '/includes/customizer/media-controls.php';

/**
 * Sidebar resolver - centralized sidebar logic
 */
require_once VH360_THEME_DIR . '/includes/sidebar-resolver.php';

/**
 * Sidebar meta box for per-page controls
 */
require_once VH360_THEME_DIR . '/includes/sidebar-meta-box.php';

/**
 * Members directory mode resolver
 */
require_once VH360_THEME_DIR . '/includes/members-directory-mode.php';

/**
 * Members directory meta box for per-page controls
 */
require_once VH360_THEME_DIR . '/includes/members-directory-meta-box.php';

/**
 * Dynamic CSS generation
 */
require_once VH360_THEME_DIR . '/includes/dynamic-css.php';

/**
 * Google Fonts integration
 */
require_once VH360_THEME_DIR . '/includes/google-fonts.php';

/**
 * Performance optimizations
 */
require_once VH360_THEME_DIR . '/includes/performance.php';

/**
 * Account type helpers
 */
require_once VH360_THEME_DIR . '/includes/account-types.php';

/**
 * Helper functions
 */
require_once VH360_THEME_DIR . '/includes/helpers.php';

/**
 * Profile avatar processing functions
 */
require_once VH360_THEME_DIR . '/includes/profile-avatar-functions.php';

/**
 * Smart asset enqueue manager
 */
require_once VH360_THEME_DIR . '/includes/enqueue-manager.php';

/**
 * Dashboard Tabs Registry (single source of truth)
 */
require_once VH360_THEME_DIR . '/includes/navigation/dashboard-tabs.php';

/**
 * Media helper functions (YouTube, etc.)
 */
require_once VH360_THEME_DIR . '/includes/media-helpers.php';

/**
 * AJAX handlers
 */
require_once VH360_THEME_DIR . '/includes/ajax-handlers.php';

/**
 * Search helper functions
 */
require_once VH360_THEME_DIR . '/includes/search/search-helpers.php';

/**
 * Advanced search AJAX handlers
 */
require_once VH360_THEME_DIR . '/includes/ajax-handlers-search.php';

/**
 * Activity tracker
 */
require_once VH360_THEME_DIR . '/includes/activity-tracker.php';

/**
 * Community posts system
 */
require_once VH360_THEME_DIR . '/includes/community-posts.php';
require_once VH360_THEME_DIR . '/includes/live-room-frontend-settings.php';

/**
 * Gallery system
 */
require_once VH360_THEME_DIR . '/includes/gallery/gallery-functions.php';
require_once VH360_THEME_DIR . '/includes/gallery/class-vh360-gallery-post-type.php';
require_once VH360_THEME_DIR . '/includes/gallery/class-vh360-gallery-capabilities.php';
require_once VH360_THEME_DIR . '/includes/gallery/class-vh360-gallery-ajax.php';
require_once VH360_THEME_DIR . '/includes/gallery/class-vh360-gallery-shortcodes.php';

/**
 * Elementor Integration
 */
require_once VH360_THEME_DIR . '/includes/elementor/class-vh360-elementor-integration.php';

/**
 * Bulletin system
 */
require_once VH360_THEME_DIR . '/includes/bulletin-system.php';

/**
 * Permission helpers
 */
require_once VH360_THEME_DIR . '/includes/permissions/helpers.php';

/**
 * Event system
 */
require_once VH360_THEME_DIR . '/includes/events/class-vh360-event-post-type.php';
require_once VH360_THEME_DIR . '/includes/events/class-vh360-event-capabilities.php';
require_once VH360_THEME_DIR . '/includes/events/class-vh360-event-ajax.php';
require_once VH360_THEME_DIR . '/includes/events/event-functions.php';

/**
 * Availability functions for appointment booking
 */
require_once VH360_THEME_DIR . '/includes/availability-functions.php';
require_once VH360_THEME_DIR . '/includes/class-vh360-availability-ajax.php';

/**
 * Exclude appointment-only events (availability and block) from public event archives
 */
function vh360_exclude_appointment_events_from_archives($query) {
    // Only apply to main query on event archives (not admin, not in dashboard)
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Only apply to vh360_event post type archives
    if (!is_post_type_archive('vh360_event') && !($query->is_tax() && $query->get('post_type') === 'vh360_event')) {
        return;
    }

    // Don't apply if we're viewing a specific author's business profile (handled separately in business header)
    if (is_author()) {
        return;
    }

    // Exclude availability and block kind events from public archives
    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = array();
    }

    $meta_query[] = array(
        'relation' => 'OR',
        array(
            'key' => '_vh360_event_kind',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => '_vh360_event_kind',
            'value' => 'event',
            'compare' => '='
        )
    );

    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'vh360_exclude_appointment_events_from_archives');

/**
 * User menu functions
 */
require_once VH360_THEME_DIR . '/includes/user-menu-functions.php';

/**
 * Community Menu icon registry
 */
require_once VH360_THEME_DIR . '/includes/community-menu-icons.php';

/**
 * Community Menu walker
 */
require_once VH360_THEME_DIR . '/includes/class-vh360-community-menu-walker.php';

/**
 * Admin notices
 */
require_once VH360_THEME_DIR . '/includes/admin-notices.php';

/**
 * Video upload settings helper function
 * Must be loaded before admin panel to be available on frontend
 */
if (!function_exists('vh360_get_video_upload_settings')) {
    function vh360_get_video_upload_settings() {
        $defaults = array(
            'enable_video_upload' => 1,
            'max_file_size' => 500,
            'allowed_formats' => 'mp4,webm,mov',
        );

        $options = get_option('vh360_video_upload_options', $defaults);
        return wp_parse_args($options, $defaults);
    }
}

/**
 * Admin panel
 */
if (is_admin()) {
    require_once VH360_THEME_DIR . '/includes/admin/class-vh360-theme-admin.php';
    VH360_Theme_Admin::get_instance();

    // Video upload settings page
    require_once VH360_THEME_DIR . '/includes/admin/video-upload-settings.php';

    // User account type and business profile fields
    require_once VH360_THEME_DIR . '/includes/admin/user-account-type.php';

    // Menu meta boxes for Dashboard and Mobile Bottom Nav items
    require_once VH360_THEME_DIR . '/includes/admin/nav-menus-vh360-meta-boxes.php';
}

// -----------------------------------------------------------------------------
//  Follow system and feed filtering
//
// Load the follow system helpers and register custom feed behaviour. These
// helpers enable a simple follow/unfollow system and allow users to filter
// their activity feed between posts from everyone (Explore) and posts from
// accounts they follow (My Feed). See includes/follow-system.php for details.

require_once VH360_THEME_DIR . '/includes/follow-system.php';
require_once VH360_THEME_DIR . '/includes/trending.php';
// Load recommended user helpers for the sidebar widget.
// This file defines functions such as `vh360_get_recommended_users()` to suggest profiles
// to users based on their following behaviour and popular accounts.
require_once VH360_THEME_DIR . '/includes/recommended.php';

/**
 * WooCommerce integration
 *
 * Keeps WooCommerce checkout intact while ensuring accounts created during
 * checkout respect theme expectations (display name, default role, and custom
 * registration fields) and routes account/login flows through the theme’s
 * authentication pages when available.
 */
if (class_exists('WooCommerce')) {
    require_once VH360_THEME_DIR . '/includes/woocommerce-integration.php';
}

/**
 * Latest Posts Widget
 */
require_once VH360_THEME_DIR . '/includes/class-vh360-latest-posts-widget.php';

/**
 * Notification system
 */
require_once VH360_THEME_DIR . '/includes/notifications.php';

/**
 * Direct messaging system
 */
require_once VH360_THEME_DIR . '/includes/direct-messages.php';
require_once VH360_THEME_DIR . '/includes/class-vh360-dm-ajax.php';
require_once VH360_THEME_DIR . '/includes/class-vh360-dm-notifications.php';

/**
 * Live activity handlers
 */
require_once VH360_THEME_DIR . '/includes/live-activity.php';

/**
 * Appointment Live Room access gate
 */
require_once VH360_THEME_DIR . '/includes/appointment-live-room-gate.php';

/**
 * Create direct messages database table on theme activation
 */
function vh360_create_dm_table_on_activation() {
    vh360_create_dm_table();
}
add_action('after_switch_theme', 'vh360_create_dm_table_on_activation');

/**
 * Register additional query variables. We use the `feed_type` query var
 * to determine which posts to show in the activity feed.
 *
 * @param array $vars Public query vars.
 * @return array
 */
function vh360_register_feed_query_var($vars) {
    $vars[] = 'feed_type';
    return $vars;
}
add_filter('query_vars', 'vh360_register_feed_query_var');

/**
 * Modify the main query for the community feed based on feed type.
 *
 * When the post type is `vh360-community-post` and the feed type is
 * anything other than `explore`, we restrict the authors shown to the
 * current user and the users they follow.
 *
 * @param WP_Query $query The query object.
 */
function vh360_filter_community_feed(WP_Query $query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Never restrict author archives. Profile/author pages use their own
    // queries and must not be overridden by the community feed logic.
    if (is_author()) {
        return;
    }
    // Only run on the front end for our community post type.
    $post_type = $query->get('post_type');
    // Our community feed uses the `vh360_post` custom post type.
    if ('vh360_post' !== $post_type) {
        return;
    }
    $feed_type = get_query_var('feed_type', 'my-feed');
    if ('explore' === $feed_type) {
        return;
    }
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return;
    }
    $authors = vh360_get_following_user_ids($current_user_id);
    $authors[] = $current_user_id;
    $authors = array_unique(array_map('intval', $authors));
    // Apply the author filter to the query. This automatically restricts
    // posts to those written by the selected authors.
    $query->set('author__in', $authors);
}
add_action('pre_get_posts', 'vh360_filter_community_feed');

/**
 * Ensure author/profile pagination URLs (e.g. /author/name/page/2/) never 404.
 *
 * The profile/author templates use custom WP_Query loops (vh360_post, videos,
 * bulletins, events). WordPress decides whether /page/N/ is valid based on the
 * MAIN author archive query. If the main query doesn't match the profile view,
 * WordPress marks the request as a 404 even if the custom loop would have posts.
 *
 * This hook aligns the MAIN author query with the current profile UI state
 * (tab/filter), so paged author URLs are always routable.
 */
function vh360_fix_author_archive_main_query(WP_Query $query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!is_author()) {
        return;
    }

    $tab    = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
    $filter = isset($_GET['filter']) ? sanitize_key(wp_unslash($_GET['filter'])) : '';

    // Defaults match template-parts/profile/posts.php and feed.php
    $post_type      = array('vh360_post');
    $posts_per_page = 20;
    $meta_query     = array();

    // Desktop feed filters (template-parts/profile/feed.php)
    if ($filter === 'bulletins') {
        $post_type = array('vh360_bulletin');
    } elseif ($filter === 'events') {
        $post_type = array('vh360_event');
    } elseif ($filter === 'photos') {
        $post_type  = array('vh360_post');
        $meta_query = array(
            array(
                'key'     => 'vh360_post_media_type',
                'value'   => 'photo',
                'compare' => '=',
            ),
        );
    } elseif ($filter === 'videos') {
        $post_type  = array('vh360_post');
        $meta_query = array(
            array(
                'key'     => 'vh360_post_media_type',
                'value'   => 'video',
                'compare' => '=',
            ),
        );
    }

    // Mobile/desktop "Videos" tab (template-parts/profile/videos.php)
    // This view is more strict: it requires a media attachment AND type video.
    if ($tab === 'videos') {
        $post_type      = array('vh360_post');
        $posts_per_page = 12;
        $meta_query     = array(
            array(
                'key'     => 'vh360_post_media_id',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => 'vh360_post_media_type',
                'value'   => 'video',
                'compare' => '=',
            ),
        );
    }

    $query->set('post_type', $post_type);
    $query->set('posts_per_page', $posts_per_page);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    } else {
        // Ensure we don't inherit a meta_query from elsewhere.
        $query->set('meta_query', array());
    }
}
add_action('pre_get_posts', 'vh360_fix_author_archive_main_query', 20);

/**
 * Add custom body classes
 */
function videohub360_theme_body_classes($classes) {
    // Add class if Elementor is active
    if (did_action('elementor/loaded')) {
        $classes[] = 'elementor-active';
    }

    // Add class for videohub360 plugin
    if (class_exists('VideoHub360_Core')) {
        $classes[] = 'videohub360-plugin-active';
    }

    // Add singular class
    if (is_singular()) {
        $classes[] = 'singular-post';
    }

    // Add auth page classes
    if (function_exists('vh360_is_auth_page') && vh360_is_auth_page()) {
        $classes[] = 'is-auth-page';

        // Add header-hidden class if setting is enabled
        if (get_theme_mod('vh360_hide_header_on_auth_pages', 1)) {
            $classes[] = 'vh360-auth-hide-header';
        }

        // Add footer-hidden class if setting is enabled
        if (get_theme_mod('vh360_hide_footer_on_auth_pages', 1)) {
            $classes[] = 'vh360-auth-hide-footer';
        }
    }

    // Add Live Room context class
    global $post;
    if ($post && get_post_type($post) === 'videohub360') {
        $context = get_post_meta($post->ID, '_vh360_context', true);
        if ($context === 'live_room') {
            $classes[] = 'vh360-is-live-room';
        }
    }

    return $classes;
}
add_filter('body_class', 'videohub360_theme_body_classes');

/**
 * Customize excerpt length
 */
function videohub360_theme_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'videohub360_theme_excerpt_length');

/**
 * Customize excerpt more string
 */
function videohub360_theme_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'videohub360_theme_excerpt_more');

/**
 * Add custom styles to video player
 */
function videohub360_theme_video_styles() {
    if (is_singular('videohub360') || is_post_type_archive('videohub360')) {
        wp_enqueue_style(
            'videohub360-theme-video',
            VH360_THEME_URI . '/assets/css/videohub360-integration.css',
            array(),
            VH360_THEME_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'videohub360_theme_video_styles', 20);

/**
 * Conditionally enqueue widget styles
 */
function vh360_enqueue_widget_styles() {
    if (is_active_widget(false, false, 'vh360_latest_posts_widget')) {
        wp_enqueue_style(
            'vh360-widgets',
            VH360_THEME_URI . '/assets/css/widgets.css',
            array(),
            VH360_THEME_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'vh360_enqueue_widget_styles');

/**
 * Output custom CSS from theme settings
 */
function videohub360_theme_custom_css() {
    $options = get_option('vh360_appearance_options', array());

    if (!empty($options['custom_css'])) {
        echo '<style id="vh360-custom-css">' . "\n";
        echo wp_strip_all_tags($options['custom_css']) . "\n";
        echo '</style>' . "\n";
    }
}
add_action('wp_head', 'videohub360_theme_custom_css', 100);

/**
 * Optimize video archive layout
 */
function videohub360_theme_video_grid_classes($classes) {
    if (is_post_type_archive('videohub360') || is_tax(array('videohub360_category', 'videohub360_series', 'videohub360_location'))) {
        $classes[] = 'video-archive-layout';
    }
    return $classes;
}
add_filter('body_class', 'videohub360_theme_video_grid_classes');




/**
 * Use a custom uploaded profile picture (if set) for user avatars.
 */
function vh360_custom_avatar_url($url, $id_or_email, $args) {
    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = $id_or_email->ID;
    } elseif ($id_or_email instanceof WP_Comment) {
        $user_id = (int) $id_or_email->user_id;
        if (!$user_id && !empty($id_or_email->comment_author_email)) {
            $user = get_user_by('email', $id_or_email->comment_author_email);
            if ($user) {
                $user_id = $user->ID;
            }
        }
    } elseif (is_string($id_or_email) && strpos($id_or_email, '@') !== false) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    }

    if (!$user_id) {
        return $url;
    }

    $avatar_id = get_user_meta($user_id, 'vh360_profile_picture_id', true);
    if (!$avatar_id) {
        return $url;
    }

    $avatar_url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
    if (!$avatar_url) {
        $avatar_url = wp_get_attachment_image_url($avatar_id, 'full');
    }

    return $avatar_url ? $avatar_url : $url;
}
add_filter('get_avatar_url', 'vh360_custom_avatar_url', 10, 3);


/**
 * Use the Live Room template for Videohub360 posts marked as community live rooms.
 */
function vh360_theme_maybe_use_live_room_template( $single ) {
    if ( is_singular( 'videohub360' ) ) {
        $post_id = get_queried_object_id();
        if ( $post_id ) {
            $context = get_post_meta( $post_id, '_vh360_context', true );

            if ( $context === 'live_room' ) {
                $live_room_template = locate_template( 'videohub360-live-room.php' );
                if ( $live_room_template ) {
                    return $live_room_template;
                }
            }
        }
    }

    return $single;
}
add_filter( 'single_template', 'vh360_theme_maybe_use_live_room_template' );

/**
 * Change Live Room permalinks to use /liveroom/ instead of /archive/
 *
 * This filter modifies the permalink for Videohub360 posts that have the
 * '_vh360_context' meta field set to 'live_room'. It replaces the default
 * post slug with 'liveroom' to create a distinct URL structure for Live Rooms.
 *
 * @param string  $post_link The post's permalink.
 * @param WP_Post $post      The post object.
 * @return string Modified permalink for Live Rooms, unchanged for other posts.
 */
function vh360_live_room_permalink( $post_link, $post ) {
	if ( $post->post_type === 'videohub360' ) {
		$context = get_post_meta( $post->ID, '_vh360_context', true );
		if ( $context === 'live_room' ) {
			// Replace the archive slug with liveroom
			$post_slug = get_option( 'videohub360_post_slug', 'videohub360' );
			$post_link = str_replace( '/' . $post_slug . '/', '/liveroom/', $post_link );
		}
	}
	return $post_link;
}
add_filter( 'post_type_link', 'vh360_live_room_permalink', 10, 2 );

/**
 * Add rewrite rule for liveroom URLs
 *
 * This function registers a custom rewrite rule to handle /liveroom/{post-name}
 * URLs and map them to the correct Videohub360 post. This ensures WordPress
 * can properly resolve and route Live Room permalinks.
 *
 * Note: After adding this code, users must flush rewrite rules by visiting
 * WordPress Admin > Settings > Permalinks and clicking "Save Changes".
 */
function vh360_live_room_rewrite_rules() {
	add_rewrite_rule(
		'^liveroom/([^/]+)/?$',
		'index.php?videohub360=$matches[1]',
		'top'
	);
}
add_action( 'init', 'vh360_live_room_rewrite_rules' );

/**
 * Mobile Bottom Nav (Appearance > Menus)
 *
 * - Theme location: vh360_mobile_bottom
 * - Enforces: min 3 items (fallback renders), max 5 items (trim)
 * - No dependencies: icons via menu item CSS classes + inline SVG
 * - Special classes:
 *   - vh360-icon-activity
 *   - vh360-icon-notifications (adds unread badge)
 *   - vh360-icon-members
 *   - vh360-icon-avatar (renders user's avatar and opens the drawer)
 */

/**
 * Get unread notifications count (safe wrapper).
 */
function vh360_mobile_bottom_nav_unread_notifications_count( $user_id ) {
    if ( ! $user_id ) {
        return 0;
    }
    if ( function_exists( 'vh360_get_unread_notification_count' ) ) {
        return (int) vh360_get_unread_notification_count( $user_id );
    }
    return 0;
}

/**
 * Trim mobile bottom nav to 5 items max.
 */
function vh360_mobile_bottom_nav_limit_items( $items, $args ) {
    if ( empty( $args->theme_location ) || 'vh360_mobile_bottom' !== $args->theme_location ) {
        return $items;
    }

    if ( ! is_array( $items ) ) {
        return $items;
    }

    /**
     * Important:
     * wp_nav_menu() may pass a flat list that includes child items even when
     * depth=1 is requested. If we slice the raw list, children can “consume”
     * slots and make the visible top-level items appear stuck/not updating.
     *
     * To keep the bottom nav predictable, we:
     * 1) Keep ONLY top-level items
     * 2) Trim to 5 max
     */
    $top_level = array();
    foreach ( $items as $it ) {
        if ( empty( $it->menu_item_parent ) || '0' === (string) $it->menu_item_parent ) {
            $top_level[] = $it;
        }
    }

    if ( count( $top_level ) > 5 ) {
        $top_level = array_slice( $top_level, 0, 5 );
    }

    $items = $top_level;

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'vh360_mobile_bottom_nav_limit_items', 10, 2 );

/**
 * Render icons + labels for mobile bottom nav items, based on CSS classes.
 */
function vh360_mobile_bottom_nav_item_output( $item_output, $item, $depth, $args ) {
    if ( empty( $args->theme_location ) || 'vh360_mobile_bottom' !== $args->theme_location ) {
        return $item_output;
    }

    $classes = is_array( $item->classes ) ? $item->classes : array();

    $is_avatar        = in_array( 'vh360-icon-avatar', $classes, true );
    $is_notifications = in_array( 'vh360-icon-notifications', $classes, true );
    $is_activity      = in_array( 'vh360-icon-activity', $classes, true );
    $is_members       = in_array( 'vh360-icon-members', $classes, true );
    $is_communities   = in_array( 'vh360-icon-communities', $classes, true );
    $is_home          = in_array( 'vh360-icon-home', $classes, true );

    // Inline SVGs (tiny + dependency-free).
    $svg_home     = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5"></path><path d="M5 10v11h14V10"></path><path d="M9 21v-6h6v6"></path></svg>';
    $svg_activity = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19h16"></path><path d="M4 15l4-4 4 4 8-8"></path></svg>';
    $svg_bell     = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';
    $svg_users    = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
    $svg_user     = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
    $svg_menu     = '<svg class="vh360-mobile-bottom-nav__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path></svg>';

    $icon_html = $svg_menu;
    if ( $is_home ) {
        $icon_html = $svg_home;
    } elseif ( $is_activity ) {
        $icon_html = $svg_activity;
    } elseif ( $is_notifications ) {
        $icon_html = $svg_bell;
    } elseif ( $is_communities ) {
        $icon_html = $svg_users;
    } elseif ( $is_members ) {
        $icon_html = $svg_user;
    }

    $label = isset( $item->title ) ? $item->title : '';

    // Avatar item becomes a button that opens the drawer.
    if ( $is_avatar ) {
        $user_id    = get_current_user_id();
        $avatar_url = get_avatar_url( $user_id, array( 'size' => 64 ) );
        $alt        = wp_get_current_user() ? wp_get_current_user()->display_name : '';

        $icon_html = '<span class="vh360-mobile-bottom-nav__avatar"><img src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" /></span>';

        $button  = '<button class="vh360-mobile-bottom-nav__item vh360-mobile-bottom-nav__menu-btn" type="button" aria-label="' . esc_attr( $label ? $label : __( 'Menu', 'videohub360-theme' ) ) . '" aria-controls="vh360-mobile-user-drawer" aria-expanded="false">';
        $button .= $icon_html;
        $button .= '<span class="vh360-mobile-bottom-nav__label">' . esc_html( $label ? $label : __( 'Menu', 'videohub360-theme' ) ) . '</span>';
        $button .= '</button>';
        return $button;
    }

    $href = ! empty( $item->url ) ? $item->url : '#';

    $out  = '<a class="vh360-mobile-bottom-nav__item" href="' . esc_url( $href ) . '" aria-label="' . esc_attr( $label ) . '">';
    $out .= $icon_html;
    $out .= '<span class="vh360-mobile-bottom-nav__label">' . esc_html( $label ) . '</span>';

    if ( $is_notifications ) {
        $count = vh360_mobile_bottom_nav_unread_notifications_count( get_current_user_id() );
        if ( $count > 0 ) {
            $out .= '<span class="vh360-mobile-bottom-nav__badge" aria-label="' . esc_attr__( 'Unread notifications', 'videohub360-theme' ) . '">' . esc_html( $count > 99 ? '99+' : $count ) . '</span>';
        }
    }

    $out .= '</a>';

    return $out;
}
add_filter( 'walker_nav_menu_start_el', 'vh360_mobile_bottom_nav_item_output', 10, 4 );

/**
 * Hide WordPress admin bar for all non-admin users on the front end.
 */
add_action('after_setup_theme', function () {
    if ( ! is_admin() && ! current_user_can('administrator') ) {
        show_admin_bar(false);
    }
});

/**
 * Keep users signed in longer (Facebook/X-like persistent sessions).
 * - Non-admins: 365 days
 * - Administrators: 14 days (safer for high-privilege accounts)
 *
 * Note: Users can still be logged out by manual logout, password changes,
 * security plugins, or if the browser/webview clears cookies/storage.
 */
add_filter('auth_cookie_expiration', function ($expiration, $user_id, $remember) {
    // Force persistent duration even if a custom login form doesn't send rememberme.
    $user = get_user_by('id', $user_id);
    if ($user && user_can($user, 'administrator')) {
        return 14 * DAY_IN_SECONDS;
    }
    return 365 * DAY_IN_SECONDS;
}, 10, 3);

/**
 * Auto-create and assign a default Dashboard Menu in Appearance → Menus.
 * Runs on theme activation and will NOT overwrite an existing assigned menu.
 */
add_action('after_switch_theme', function () {
    $location = 'dashboard';
    $locations = get_theme_mod('nav_menu_locations', array());

    // If a menu is already assigned to the Dashboard location, don't touch anything.
    if ( ! empty($locations[$location]) ) {
        return;
    }

    $menu_name = 'Dashboard Menu';
    $menu_obj  = wp_get_nav_menu_object($menu_name);

    if ( $menu_obj && ! is_wp_error($menu_obj) ) {
        $menu_id = (int) $menu_obj->term_id;
    } else {
        $menu_id = wp_create_nav_menu($menu_name);
        if ( is_wp_error($menu_id) ) {
            return;
        }
    }

    // If the menu already has items, don't duplicate.
    $existing_items = wp_get_nav_menu_items($menu_id);
    if ( ! empty($existing_items) ) {
        $locations[$location] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
        return;
    }

    // Use registry to build a curated default menu (clean subset for first-run UX)
    $dashboard_url = vh360_get_dashboard_page_url();
    $registry = vh360_get_dashboard_tabs_registry();
    
    // Curated tabs for activation default (8 core items)
    $default_tabs = array(
        'overview',
        'create-video',
        'videos',
        'live-rooms',
        'messages',
        'notifications',
        'appointments',
        'settings',
    );
    
    foreach ( $default_tabs as $tab_id ) {
        if ( ! isset( $registry[ $tab_id ] ) ) {
            continue;
        }
        
        $tab_config = $registry[ $tab_id ];
        $label = $tab_config['label'];
        
        // Use default label (not dynamic) for activation menu
        // Admins can customize later if needed
        
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title'  => $label,
            'menu-item-url'    => $dashboard_url . '#' . $tab_id,
            'menu-item-status' => 'publish',
            'menu-item-type'   => 'custom',
        ));
    }

    // Assign the new menu to the Dashboard menu location.
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
});

/**
 * Auto-create and assign a default Mobile Bottom Nav menu in Appearance → Menus.
 * Runs on theme activation and will NOT overwrite an existing assigned menu.
 */
add_action('after_switch_theme', function () {
    $location = 'vh360_mobile_bottom';
    $locations = get_theme_mod('nav_menu_locations', array());

    // If a menu is already assigned to the Mobile Bottom Nav location, don't touch anything.
    if ( ! empty($locations[$location]) ) {
        return;
    }

    $menu_name = 'Mobile Bottom Nav';
    $menu_obj  = wp_get_nav_menu_object($menu_name);

    if ( $menu_obj && ! is_wp_error($menu_obj) ) {
        $menu_id = (int) $menu_obj->term_id;
    } else {
        $menu_id = wp_create_nav_menu($menu_name);
        if ( is_wp_error($menu_id) ) {
            return;
        }
    }

    // If the menu already has items, don't duplicate.
    $existing_items = wp_get_nav_menu_items($menu_id);
    if ( ! empty($existing_items) ) {
        $locations[$location] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
        return;
    }

    // Use helper functions to get real page URLs
    $activity_url = vh360_get_activity_page_url();
    $members_url = vh360_get_members_page_url();
    $dashboard_url = vh360_get_dashboard_page_url();
    $notifications_url = add_query_arg('tab', 'notifications', $dashboard_url);

    // Create default menu items (4 items: Activity, Notifications, Members, Menu)
    // Each item includes the CSS class required for icon rendering
    $items = array(
        array(
            'title'   => __('Activity', 'videohub360-theme'),
            'url'     => $activity_url,
            'classes' => 'vh360-icon-activity',
        ),
        array(
            'title'   => __('Notifications', 'videohub360-theme'),
            'url'     => $notifications_url,
            'classes' => 'vh360-icon-notifications',
        ),
        array(
            'title'   => __('Members', 'videohub360-theme'),
            'url'     => $members_url,
            'classes' => 'vh360-icon-members',
        ),
        array(
            'title'   => __('Menu', 'videohub360-theme'),
            'url'     => '#',
            'classes' => 'vh360-icon-avatar',
        ),
    );

    foreach ( $items as $item_data ) {
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title'   => $item_data['title'],
            'menu-item-url'     => $item_data['url'],
            'menu-item-status'  => 'publish',
            'menu-item-type'    => 'custom',
            'menu-item-classes' => $item_data['classes'],
        ));
    }

    // Assign the new menu to the Mobile Bottom Nav menu location.
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
});

/**
 * VH360 Menu Item Role Visibility
 *
 * Adds a "Visibility" section to each Appearance → Menus item, allowing admins/designers to
 * choose which roles (and/or logged-in / guests) can see that menu item.
 *
 * Storage:
 * - _vh360_menu_visibility_roles: array of role slugs (e.g. ['editor','subscriber'])
 * - _vh360_menu_visibility_logged_in: '1' to show to any logged-in user
 * - _vh360_menu_visibility_guests: '1' to show to logged-out visitors
 *
 * Behavior:
 * - If no visibility options are set, item is shown to everyone (default).
 * - If roles are selected, item is shown only to logged-in users with any of those roles.
 * - If "Logged-in users" is checked, item is shown to any logged-in user.
 * - If "Guests" is checked, item is shown to logged-out visitors.
 * - Administrators (manage_options) always see all items.
 */

// Admin UI: render custom fields on menu items.
add_action('wp_nav_menu_item_custom_fields', function($item_id, $item, $depth, $args) {
    if ( ! current_user_can('edit_theme_options') ) {
        return;
    }

    $roles_selected = get_post_meta($item_id, '_vh360_menu_visibility_roles', true);
    if ( ! is_array($roles_selected) ) {
        $roles_selected = array();
    }

    $show_logged_in = (bool) get_post_meta($item_id, '_vh360_menu_visibility_logged_in', true);
    $show_guests    = (bool) get_post_meta($item_id, '_vh360_menu_visibility_guests', true);

    $wp_roles = wp_roles();
    $roles = $wp_roles ? $wp_roles->roles : array();
    ?>
    <p class="description description-wide vh360-menu-visibility-fields">
        <strong><?php echo esc_html__('Visibility', 'videohub360-theme'); ?></strong><br />

        <label style="display:block; margin: 4px 0;">
            <input type="checkbox"
                   name="vh360_menu_visibility_logged_in[<?php echo esc_attr($item_id); ?>]"
                   value="1" <?php checked($show_logged_in); ?> />
            <?php echo esc_html__('Logged-in users (any role)', 'videohub360-theme'); ?>
        </label>

        <label style="display:block; margin: 4px 0;">
            <input type="checkbox"
                   name="vh360_menu_visibility_guests[<?php echo esc_attr($item_id); ?>]"
                   value="1" <?php checked($show_guests); ?> />
            <?php echo esc_html__('Guests (logged out)', 'videohub360-theme'); ?>
        </label>

        <span style="display:block; margin-top: 6px; font-weight: 600;">
            <?php echo esc_html__('Roles', 'videohub360-theme'); ?>
        </span>
        <span style="display:block; margin: 2px 0 6px; color:#666;">
            <?php echo esc_html__('If any roles are checked, the item will only show to those roles (logged-in).', 'videohub360-theme'); ?>
        </span>

        <div style="display:flex; flex-wrap:wrap; gap: 10px;">
            <?php foreach ( (array) $roles as $role_key => $role_data ) :
                $label = isset($role_data['name']) ? $role_data['name'] : $role_key;
                $checked = in_array($role_key, $roles_selected, true);
            ?>
                <label style="display:inline-flex; align-items:center; gap:6px;">
                    <input type="checkbox"
                           name="vh360_menu_visibility_roles[<?php echo esc_attr($item_id); ?>][]"
                           value="<?php echo esc_attr($role_key); ?>" <?php checked($checked); ?> />
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <em style="display:block; margin-top: 6px; color:#666;">
            <?php echo esc_html__('Leave everything unchecked to show this item to everyone.', 'videohub360-theme'); ?>
        </em>
    </p>

    <p class="description description-wide vh360-menu-icon-field">
        <label for="vh360-menu-icon-<?php echo esc_attr($item_id); ?>">
            <strong><?php echo esc_html__('Community Menu Icon', 'videohub360-theme'); ?></strong><br />
            <span style="color:#666; font-size:12px;">
                <?php echo esc_html__('Optional icon for Community Menu. Only displays when this item is used in the Community Menu location.', 'videohub360-theme'); ?>
            </span>
        </label>
        <select
            id="vh360-menu-icon-<?php echo esc_attr($item_id); ?>"
            name="vh360_menu_icon[<?php echo esc_attr($item_id); ?>]"
            style="width:100%; margin-top:6px;">
            <option value=""><?php echo esc_html__('— No Icon —', 'videohub360-theme'); ?></option>
            <?php
            $current_icon = get_post_meta($item_id, '_vh360_menu_icon', true);
            $icon_choices = vh360_cm_icon_choices();
            foreach ($icon_choices as $slug => $label) :
            ?>
                <option value="<?php echo esc_attr($slug); ?>" <?php selected($current_icon, $slug); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}, 10, 4);

// Save menu item meta.
add_action('wp_update_nav_menu_item', function($menu_id, $menu_item_db_id, $args) {
    if ( ! current_user_can('edit_theme_options') ) {
        return;
    }

    // Logged-in visibility
    $logged_in = isset($_POST['vh360_menu_visibility_logged_in'][$menu_item_db_id]) ? '1' : '';
    if ( $logged_in ) {
        update_post_meta($menu_item_db_id, '_vh360_menu_visibility_logged_in', '1');
    } else {
        delete_post_meta($menu_item_db_id, '_vh360_menu_visibility_logged_in');
    }

    // Guests visibility
    $guests = isset($_POST['vh360_menu_visibility_guests'][$menu_item_db_id]) ? '1' : '';
    if ( $guests ) {
        update_post_meta($menu_item_db_id, '_vh360_menu_visibility_guests', '1');
    } else {
        delete_post_meta($menu_item_db_id, '_vh360_menu_visibility_guests');
    }

    // Roles visibility
    $roles = array();
    if ( isset($_POST['vh360_menu_visibility_roles'][$menu_item_db_id]) && is_array($_POST['vh360_menu_visibility_roles'][$menu_item_db_id]) ) {
        $roles = array_map('sanitize_key', (array) $_POST['vh360_menu_visibility_roles'][$menu_item_db_id]);
        $roles = array_values(array_unique(array_filter($roles)));
    }

    if ( ! empty($roles) ) {
        update_post_meta($menu_item_db_id, '_vh360_menu_visibility_roles', $roles);
    } else {
        delete_post_meta($menu_item_db_id, '_vh360_menu_visibility_roles');
    }

    // Icon field
    $icon = '';
    if (isset($_POST['vh360_menu_icon'][$menu_item_db_id])) {
        $icon = sanitize_key($_POST['vh360_menu_icon'][$menu_item_db_id]);

        // Validate against whitelist
        $allowed_icons = array_keys(vh360_cm_icon_choices());
        if (!empty($icon) && !in_array($icon, $allowed_icons, true)) {
            $icon = ''; // Invalid icon, clear it
        }
    }

    if (!empty($icon)) {
        update_post_meta($menu_item_db_id, '_vh360_menu_icon', $icon);
    } else {
        delete_post_meta($menu_item_db_id, '_vh360_menu_icon');
    }
}, 10, 3);

/**
 * Front-end filtering: remove menu items the current visitor should not see.
 */
add_filter('wp_nav_menu_objects', function($items, $args) {
    // Admins always see all items (useful for designers/testing).
    if ( is_user_logged_in() && current_user_can('manage_options') ) {
        return $items;
    }

    $is_logged_in = is_user_logged_in();
    $user_roles = array();
    if ( $is_logged_in ) {
        $user = wp_get_current_user();
        $user_roles = is_array($user->roles) ? $user->roles : array();
    }

    $allowed_ids = array();
    $items_by_id = array();
    $children_of = array();

    foreach ( (array) $items as $item ) {
        $items_by_id[$item->ID] = $item;
        $parent = (int) $item->menu_item_parent;
        if ( $parent > 0 ) {
            $children_of[$parent][] = $item->ID;
        }
    }

    // First pass: decide visibility per item.
    $visible = array();
    foreach ( (array) $items as $item ) {
        $item_id = (int) $item->ID;

        $roles_selected = get_post_meta($item_id, '_vh360_menu_visibility_roles', true);
        if ( ! is_array($roles_selected) ) {
            $roles_selected = array();
        }

        $show_logged_in = (bool) get_post_meta($item_id, '_vh360_menu_visibility_logged_in', true);
        $show_guests    = (bool) get_post_meta($item_id, '_vh360_menu_visibility_guests', true);

        // Default: show to everyone if no rules set.
        if ( empty($roles_selected) && ! $show_logged_in && ! $show_guests ) {
            $visible[$item_id] = true;
            continue;
        }

        $ok = false;

        // Guests rule
        if ( $show_guests && ! $is_logged_in ) {
            $ok = true;
        }

        // Logged-in rule
        if ( $show_logged_in && $is_logged_in ) {
            $ok = true;
        }

        // Roles rule: only applies if logged in and roles selected.
        if ( ! empty($roles_selected) && $is_logged_in ) {
            if ( array_intersect($user_roles, $roles_selected) ) {
                $ok = true;
            }
        }

        $visible[$item_id] = $ok;
    }

    // Second pass: remove hidden items and any descendants of hidden parents.
    $filtered = array();
    $hidden_parents = array();

    foreach ( (array) $items as $item ) {
        $item_id = (int) $item->ID;

        // If parent is hidden, hide this too.
        $parent = (int) $item->menu_item_parent;
        if ( $parent > 0 && isset($hidden_parents[$parent]) ) {
            continue;
        }

        if ( empty($visible[$item_id]) ) {
            $hidden_parents[$item_id] = true;
            continue;
        }

        $filtered[] = $item;
    }

    return $filtered;
}, 20, 2);

/**
 * Get a visible menu item (after VH360 visibility rules + wp_nav_menu_objects filters)
 * from a given menu location, matching a CSS class and/or other heuristics.
 *
 * This is used to power CTAs (like "Go Live") from Appearance → Menus so that the
 * same per-item Visibility controls apply.
 *
 * @param string   $theme_location Menu location slug.
 * @param string[] $match_classes  One or more CSS classes to match (menu item CSS Classes field).
 * @param array    $fallback_match Optional fallback match rules: ['title' => 'Go Live', 'url_contains' => 'tab=go-live'].
 * @return object|null WP_Post-like menu item object or null.
 */
function vh360_get_visible_menu_item_for_cta( $theme_location, $match_classes = array(), $fallback_match = array() ) {
    if ( empty( $theme_location ) ) {
        return null;
    }

    $locations = get_nav_menu_locations();
    if ( empty( $locations[ $theme_location ] ) ) {
        return null;
    }

    $menu_id = (int) $locations[ $theme_location ];
    if ( $menu_id <= 0 ) {
        return null;
    }

    $items = wp_get_nav_menu_items( $menu_id );
    if ( empty( $items ) || ! is_array( $items ) ) {
        return null;
    }

    // Apply the same filters wp_nav_menu() uses, including VH360 role visibility rules.
    $args = (object) array(
        'theme_location' => $theme_location,
        'menu'           => $menu_id,
    );
    $items = apply_filters( 'wp_nav_menu_objects', $items, $args );

    if ( empty( $items ) || ! is_array( $items ) ) {
        return null;
    }

    $match_classes = array_filter( array_map( 'sanitize_html_class', (array) $match_classes ) );
    $want_title    = isset( $fallback_match['title'] ) ? (string) $fallback_match['title'] : '';
    $want_url_part = isset( $fallback_match['url_contains'] ) ? (string) $fallback_match['url_contains'] : '';

    foreach ( $items as $item ) {
        if ( empty( $item->url ) ) {
            continue;
        }

        $classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();

        // Class match (preferred).
        if ( ! empty( $match_classes ) ) {
            foreach ( $match_classes as $cls ) {
                if ( in_array( $cls, $classes, true ) ) {
                    return $item;
                }
            }
        }

        // Fallback: title match
        if ( $want_title ) {
            $title = isset( $item->title ) ? wp_strip_all_tags( (string) $item->title ) : '';
            if ( $title && 0 === strcasecmp( $title, $want_title ) ) {
                return $item;
            }
        }

        // Fallback: URL contains
        if ( $want_url_part && false !== strpos( (string) $item->url, $want_url_part ) ) {
            return $item;
        }
    }

    return null;
}



/**
 * Walker for the Dashboard Menu location to match the theme's dashboard sidebar markup.
 */

/**
 * Walker for the Dashboard Menu location to match the theme's dashboard sidebar markup.
 */
class VH360_Dashboard_Menu_Walker extends Walker_Nav_Menu {

    /**
     * Get icon SVG from registry
     *
     * @param string $tab Tab ID
     * @return string SVG markup
     */
    private function vh360_icon_svg( $tab ) {
        $registry = vh360_get_dashboard_tabs_registry();
        $tab = strtolower( (string) $tab );

        if ( isset( $registry[ $tab ] ) && ! empty( $registry[ $tab ]['icon_svg'] ) ) {
            return $registry[ $tab ]['icon_svg'];
        }

        // Default generic icon for unknown tabs
        return '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path></svg>';
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $title = isset( $item->title ) ? $item->title : '';
        $url   = isset( $item->url ) ? $item->url : '';
        $tab   = '';

        if ( preg_match( '~#([a-z0-9\-]+)~i', $url, $m ) ) {
            $tab = strtolower( $m[1] );
        }

        // Check if tab exists in registry
        $registry = vh360_get_dashboard_tabs_registry();
        if ( $tab && isset( $registry[ $tab ] ) ) {
            $tab_config = $registry[ $tab ];

            // Apply visibility rules from registry
            $show_callback = $tab_config['show_callback'];
            if ( is_callable( $show_callback ) ) {
                $should_show = call_user_func( $show_callback, get_current_user_id() );
                if ( ! $should_show ) {
                    return; // Don't render this item
                }
            }

            // Apply dynamic label if available
            if ( $tab_config['label_callback'] && is_callable( $tab_config['label_callback'] ) ) {
                $title = call_user_func( $tab_config['label_callback'], get_current_user_id() );
            }
        }

        $href = $url;
        if ( $tab && function_exists( 'is_page_template' ) && is_page_template( 'template-dashboard.php' ) ) {
            $href = '#' . $tab;
        }

        $output .= '<li class="vh360-dashboard-nav-item">';
        $output .= '<a href="' . esc_url( $href ) . '" class="vh360-dashboard-nav-link vh360-dashboard-tab' . ( $tab === 'overview' ? ' active' : '' ) . '"';
        if ( $tab ) {
            $output .= ' data-tab="' . esc_attr( $tab ) . '"';
        }
        $output .= '>';
        $output .= $this->vh360_icon_svg( $tab );
        $output .= '<span class="vh360-dashboard-nav-text">' . esc_html( $title ) . '</span>';
        $output .= '</a>';
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}

/**
 * VH360 PWA Link Handling Hardening
 *
 * In iOS PWAs and some wrappers, links that open in a new tab/window (target=_blank / window.open)
 * can break out into an in-app Safari view with a "Done" header.
 *
 * This ensures internal (same-host) menu links never use target=_blank.
 */
add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
    if ( empty($atts['href']) ) {
        return $atts;
    }

    $href = (string) $atts['href'];

    // Only adjust absolute URLs on this site, or relative URLs.
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $href_host = wp_parse_url($href, PHP_URL_HOST);

    $is_internal = false;
    if ( 0 === strpos($href, '/') ) {
        $is_internal = true;
    } elseif ( $href_host && $site_host && strtolower($href_host) === strtolower($site_host) ) {
        $is_internal = true;
    }

    if ( $is_internal ) {
        // Prevent breaking out of PWA/wrapper.
        unset($atts['target']);
        if ( ! empty($atts['rel']) ) {
            // Remove noopener/noreferrer that were added for _blank cases.
            $atts['rel'] = trim(str_replace(array('noopener', 'noreferrer'), '', (string) $atts['rel']));
            if ( $atts['rel'] === '' ) {
                unset($atts['rel']);
            }
        }
    }

    return $atts;
}, 20, 4);

/**
 * Front-end safety net: force internal links to open in the same window (no window.open).
 * Works for custom markup outside menus too.
 */
add_action('wp_enqueue_scripts', function () {
    wp_register_script(
        'vh360-pwa-link-same-window',
        get_template_directory_uri() . '/assets/js/vh360-pwa-link-same-window.js',
        array(),
        '1.0.0',
        true
    );
    wp_enqueue_script('vh360-pwa-link-same-window');
});




/**
 * VideoHub360 License gate (theme)
 * Soft-lock UI until license is active.
 */

/**
 * Theme-level license helper (single source of truth for theme soft-lock UI + frontend handlers).
 *
 * Returns true only when the VideoHub360 Core license client is active and the current license is valid.
 *
 * @since 1.0.0
 */
function vh360_theme_is_license_valid() {
    return ( function_exists( 'videohub360_license_is_valid' ) && videohub360_license_is_valid() );
}

/**
 * Admin URL for license activation screen.
 *
 * @since 1.0.0
 */
function vh360_theme_get_license_admin_url() {
    return admin_url( 'admin.php?page=videohub360-license' );
}


add_action( 'admin_notices', function () {
    if ( function_exists( 'videohub360_license_is_valid' ) && videohub360_license_is_valid() ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    // If the main plugin isn't active, TGMPA will handle required plugins.
    if ( ! function_exists( 'videohub360_license_is_valid' ) ) {
        return;
    }
    $url = admin_url( 'admin.php?page=videohub360-license' );
    echo '<div class="notice notice-warning"><p>';
    echo esc_html__( 'VideoHub360 Theme is locked until your VideoHub360 license is activated.', 'videohub360' ) . ' ';
    echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Activate License', 'videohub360' ) . '</a>';
    echo '</p></div>';
} );

/**
 * Use custom comments template for community posts (PR #9)
 * Disables WordPress default "Leave a Reply" form
 */
add_filter('comments_template', 'vh360_custom_comments_template', 20);
function vh360_custom_comments_template($template) {
    // Only affect vh360_post post type
    if (get_post_type() === 'vh360_post') {
        // Return blank template (comments handled by activity feed rendering)
        // Use get_template_directory() to ensure it works with child themes
        $custom_template = get_template_directory() . '/templates/community-comments.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }

    return $template;
}

/**
 * Disable comments open for community posts (PR #9)
 * Prevents WordPress form from showing (we use custom commenting system)
 */
add_filter('comments_open', 'vh360_disable_wp_comments_for_community', 10, 2);
function vh360_disable_wp_comments_for_community($open, $post_id) {
    $post = get_post($post_id);

    // For community posts, return false to disable WP comments
    // (We use custom commenting system in activity feed)
    if ($post && $post->post_type === 'vh360_post') {
        return false;
    }

    return $open;
}


