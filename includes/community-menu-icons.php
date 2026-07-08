<?php
/**
 * Videohub360 Menu Icon Registry
 *
 * Provides whitelisted icons and SVG output for Videohub360 menu
 * locations that support icons, including the Community Menu and
 * Mobile Bottom Nav.
 * All SVGs are theme-controlled and stored in code (not database).
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get allowed icon choices for Videohub360 menu locations that support icons.
 *
 * @return array Associative array of icon slugs and labels.
 */
function vh360_menu_icon_choices() {
    return array(
        'about-us'           => __( 'About Us', 'videohub360-theme' ),
        'activity'           => __( 'Activity', 'videohub360-theme' ),
        'analytics'          => __( 'Analytics', 'videohub360-theme' ),
        'appointments'       => __( 'Appointments', 'videohub360-theme' ),
        'availability'       => __( 'Availability', 'videohub360-theme' ),
        'bible'              => __( 'Bible', 'videohub360-theme' ),
        'blog'               => __( 'Blog', 'videohub360-theme' ),
        'booking'            => __( 'Booking', 'videohub360-theme' ),
        'bookmarks'          => __( 'Bookmarks', 'videohub360-theme' ),
        'bulletins'          => __( 'Bulletins', 'videohub360-theme' ),
        'business'           => __( 'Business', 'videohub360-theme' ),
        'calendar'           => __( 'Calendar', 'videohub360-theme' ),
        'categories'         => __( 'Categories', 'videohub360-theme' ),
        'checkout'           => __( 'Checkout', 'videohub360-theme' ),
        'clients'            => __( 'Clients', 'videohub360-theme' ),
        'comments'           => __( 'Comments', 'videohub360-theme' ),
        'communities'        => __( 'Communities', 'videohub360-theme' ),
        'connections'        => __( 'Connections', 'videohub360-theme' ),
        'contact-us'         => __( 'Contact Us', 'videohub360-theme' ),
        'course-catalog'     => __( 'Course Catalog', 'videohub360-theme' ),
        'courses'            => __( 'Courses', 'videohub360-theme' ),
        'create'             => __( 'Create', 'videohub360-theme' ),
        'create-post'        => __( 'Create Post', 'videohub360-theme' ),
        'create-video'       => __( 'Add Video', 'videohub360-theme' ),
        'dashboard'          => __( 'Dashboard', 'videohub360-theme' ),
        'directory'          => __( 'Directory', 'videohub360-theme' ),
        'donate'             => __( 'Donate', 'videohub360-theme' ),
        'events'             => __( 'Events', 'videohub360-theme' ),
        'explore'            => __( 'Explore', 'videohub360-theme' ),
        'followers'          => __( 'Followers', 'videohub360-theme' ),
        'following'          => __( 'Following', 'videohub360-theme' ),
        'galleries'          => __( 'Galleries', 'videohub360-theme' ),
        'groups'             => __( 'Groups', 'videohub360-theme' ),
        'help'               => __( 'Help', 'videohub360-theme' ),
        'home'               => __( 'Home', 'videohub360-theme' ),
        'install-app'        => __( 'Install App', 'videohub360-theme' ),
        'instructor'         => __( 'Instructor', 'videohub360-theme' ),
        'lessons'            => __( 'Lessons', 'videohub360-theme' ),
        'liked'              => __( 'Liked Content', 'videohub360-theme' ),
        'lists'              => __( 'Lists', 'videohub360-theme' ),
        'live'               => __( 'Live', 'videohub360-theme' ),
        'logout'             => __( 'Log out', 'videohub360-theme' ),
        'media'              => __( 'Media', 'videohub360-theme' ),
        'members'            => __( 'Members', 'videohub360-theme' ),
        'membership'         => __( 'Membership', 'videohub360-theme' ),
        'messages'           => __( 'Messages', 'videohub360-theme' ),
        'ministries'         => __( 'Ministries', 'videohub360-theme' ),
        'notifications'      => __( 'Notifications', 'videohub360-theme' ),
        'offline'            => __( 'Offline', 'videohub360-theme' ),
        'photos'             => __( 'Photos', 'videohub360-theme' ),
        'plans'              => __( 'Plans', 'videohub360-theme' ),
        'playlists'          => __( 'Playlists', 'videohub360-theme' ),
        'prayer-request'     => __( 'Prayer Request', 'videohub360-theme' ),
        'pricing'            => __( 'Pricing', 'videohub360-theme' ),
        'professionals'      => __( 'Professionals', 'videohub360-theme' ),
        'profile'            => __( 'Profile', 'videohub360-theme' ),
        'locked'             => __( 'Protected Content', 'videohub360-theme' ),
        'providers'          => __( 'Providers', 'videohub360-theme' ),
        'push-notifications' => __( 'Push Notifications', 'videohub360-theme' ),
        'app'                => __( 'PWA / App', 'videohub360-theme' ),
        'search'             => __( 'Search', 'videohub360-theme' ),
        'series'             => __( 'Series', 'videohub360-theme' ),
        'service-business'   => __( 'Service (Business)', 'videohub360-theme' ),
        'service-worship'    => __( 'Service (Church)', 'videohub360-theme' ),
        'settings'           => __( 'Settings', 'videohub360-theme' ),
        'shares'             => __( 'Shares', 'videohub360-theme' ),
        'shop'               => __( 'Shop', 'videohub360-theme' ),
        'support'            => __( 'Support', 'videohub360-theme' ),
        'topics'             => __( 'Topics', 'videohub360-theme' ),
        'upgrade'            => __( 'Upgrade', 'videohub360-theme' ),
        'upload'             => __( 'Upload', 'videohub360-theme' ),
        'verified'           => __( 'Verified', 'videohub360-theme' ),
        'video'              => __( 'Video', 'videohub360-theme' ),
        'videos'             => __( 'Videos', 'videohub360-theme' ),
    );
}

/**
 * Get allowed icon choices for Community Menu.
 *
 * Kept for backward compatibility with existing integrations.
 *
 * @return array Associative array of icon slugs and labels.
 */
function vh360_cm_icon_choices() {
    return vh360_menu_icon_choices();
}

/**
 * Get the SVG registry for Videohub360 menu icons.
 *
 * @return array Associative array of icon slugs and inline SVG markup.
 */
function vh360_menu_icon_svg_registry() {
    return array(
        'about-us'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
        'activity'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3 13h2v7H3v-7zm8-10h2v17h-2V3zm-4 6h2v11H7V9zm12-2h2v13h-2V7z"/></svg>',
        'analytics'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>',
        'appointments'       => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm11 8H6v10h12V10zm-9 2h3v3H9v-3z"/></svg>',
        'availability'       => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm11 8H6v10h12V10zm-7 7l-3-3 1.4-1.4 1.6 1.6 3.6-3.6L16 12l-5 5z"/></svg>',
        'bible'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18 2H8C6.9 2 6 2.9 6 4v16c0 1.1.9 2 2 2h10V2zm-2 16H8V4h8v14zM4 6H3c-.55 0-1 .45-1 1v13c0 1.1.9 2 2 2h1v-2H4V6zM10 7h4v2h-4V7zm0 4h4v2h-4v-2z"/></svg>',
        'blog'               => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M6 2h9l3 3v17H6V2zm9 1.5V6h2.5L15 3.5zM8 9h8v2H8V9zm0 4h8v2H8v-2zm0 4h6v2H8v-2z"/></svg>',
        'booking'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M5 4h14v16H5V4zm3 4h8v2H8V8zm0 4h8v2H8v-2zm0 4h5v2H8v-2z"/></svg>',
        'bookmarks'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>',
        'bulletins'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>',
        'business'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 4h4a2 2 0 0 1 2 2v2h4v12H4V8h4V6a2 2 0 0 1 2-2zm0 4h4V6h-4v2zm-4 4v6h12v-6H6z"/></svg>',
        'calendar'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h2v2h6V2h2v2h3v18H4V4h3V2zm11 8H6v10h12V10z"/></svg>',
        'categories'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>',
        'checkout'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 14h7.5c.75 0 1.4-.41 1.74-1.03L20 6H6.2L5.3 4H2v2h2l3.6 7.6L6.2 16H19v-2H7.2z"/></svg>',
        'clients'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm8 0a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM2 20c.5-3.5 3-6 6-6s5.5 2.5 6 6H2zm10 0c.3-2 1.2-3.8 2.6-5 3.2.2 5.8 2.4 6.4 5h-9z"/></svg>',
        'comments'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h16v12H7l-3 4V4zm4 4h8v2H8V8zm0 4h6v2H8v-2z"/></svg>',
        'communities'        => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
        'connections'        => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 7a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm8 10a3 3 0 1 1 6 0 3 3 0 0 1-6 0zM3 17a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm6-6l2 2 2-2 2 2-2 2-2-2-2 2-2-2 2-2z"/></svg>',
        'contact-us'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>',
        'course-catalog'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3 4h8v16H3V4zm10 0h8v16h-8V4zM5 7h4v2H5V7zm0 4h4v2H5v-2zm10-4h4v2h-4V7zm0 4h4v2h-4v-2z"/></svg>',
        'courses'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h14a2 2 0 0 1 2 2v14H6a2 2 0 0 1-2-2V4zm2 2v12h12V6H6zm2 2h8v2H8V8zm0 4h6v2H8v-2z"/></svg>',
        'create'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4z"/></svg>',
        'create-post'        => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M5 4h14v16H5V4zm3 4h8v2H8V8zm0 4h8v2H8v-2zm7 3h2v2h-2v2h-2v-2h-2v-2h2v-2h2v2z"/></svg>',
        'create-video'       => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 6h12a2 2 0 0 1 2 2v2l4-3v10l-4-3v2a2 2 0 0 1-2 2H4V6zm5 3v6l5-3-5-3z"/></svg>',
        'dashboard'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
        'directory'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h16v16H4V4zm3 3h4v4H7V7zm0 6h4v4H7v-4zm6-5h4v2h-4V8zm0 6h4v2h-4v-2z"/></svg>',
        'donate'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.65-7 10-7 10z"/></svg>',
        'events'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>',
        'explore'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5.5-2.5l7.51-3.49L17.5 6.5 9.99 9.99 6.5 17.5zm5.5-6.6c.61 0 1.1.49 1.1 1.1s-.49 1.1-1.1 1.1-1.1-.49-1.1-1.1.49-1.1 1.1-1.1z"/></svg>',
        'followers'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M9 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0 2c-3 0-6 2-7 5h14c-1-3-4-5-7-5zm10-5V5h-2v3h-3v2h3v3h2v-3h3V8h-3z"/></svg>',
        'following'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M9 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0 2c-3 0-6 2-7 5h14c-1-3-4-5-7-5zm8.5 4.5l-2.5-2.5-1.4 1.4 3.9 3.9 6-6-1.4-1.4-4.6 4.6z"/></svg>',
        'galleries'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 5h14v12H4V5zm2 2v8h10V7H6zm3 11h11V8h2v12H9v-2z"/></svg>',
        'groups'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 12.75c1.63 0 3.07.39 4.24.9 1.08.48 1.76 1.56 1.76 2.73V18H6v-1.61c0-1.18.68-2.26 1.76-2.73 1.17-.52 2.61-.91 4.24-.91zM4 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm1.13 1.1c-.37-.06-.74-.1-1.13-.1-.99 0-1.93.21-2.78.58C.48 14.9 0 15.62 0 16.43V18h4.5v-1.61c0-.83.23-1.61.63-2.29zM20 13c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm4 3.43c0-.81-.48-1.53-1.22-1.85-.85-.37-1.79-.58-2.78-.58-.39 0-.76.04-1.13.1.4.68.63 1.46.63 2.29V18H24v-1.57zM12 6c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3z"/></svg>',
        'help'               => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>',
        'home'               => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 3l10 9h-3v9h-5v-6h-4v6H5v-9H2l10-9z"/></svg>',
        'install-app'        => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm5 4v6H9l3 4 3-4h-3V6z"/></svg>',
        'instructor'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 3l10 5-10 5L2 8l10-5zm-6 8.2l6 3 6-3V16c0 2-3 4-6 4s-6-2-6-4v-4.8z"/></svg>',
        'lessons'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 5h16v3H4V5zm0 5h10v9H4v-9zm12 0h4v2h-4v-2zm0 4h4v2h-4v-2z"/></svg>',
        'liked'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11z"/></svg>',
        'lists'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>',
        'live'               => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5v-9l7 4.5-7 4.5z"/></svg>',
        'logout'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>',
        'media'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 6h16v12H4V6zm2 2v8h12V8H6zm15-3v14a3 3 0 0 1-3 3H6v-2h12a1 1 0 0 0 1-1V5h2z"/></svg>',
        'members'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
        'membership'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6z"/></svg>',
        'messages'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>',
        'ministries'         => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M11 2h2v3h2v2h-2v2h-2V7H9V5h2V2zm1 7c-4.42 0-8 3.58-8 8v5h16v-5c0-4.42-3.58-8-8-8zm6 11H6v-3c0-3.31 2.69-6 6-6s6 2.69 6 6v3z"/></svg>',
        'notifications'      => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>',
        'offline'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 18l-4-4h3V6h2v8h3l-4 4zM5 20h14v2H5v-2zM4 4l16 16 1.4-1.4L5.4 2.6 4 4z"/></svg>',
        'photos'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M21 5v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2zm-2 0H5v14h14V5zm-3 11H8l2.5-3.5 1.5 2 2-2.5L19 16zM9 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
        'plans'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h16v16H4V4zm3 4h10v2H7V8zm0 4h10v2H7v-2zm0 4h6v2H7v-2z"/></svg>',
        'playlists'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 6h12v2H4V6zm0 4h12v2H4v-2zm0 4h8v2H4v-2zm11 0v6l5-3-5-3z"/></svg>',
        'prayer-request'     => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 2c1.1 0 2 .9 2 2v3h4V4c0-1.1.9-2 2-2s2 .9 2 2v7c0 1.66-1.34 3-3 3h-1v8h-2v-8h-4v8H8v-8H7c-1.66 0-3-1.34-3-3V4c0-1.1.9-2 2-2s2 .9 2 2v7h2V4c0-1.1.9-2 2-2z"/></svg>',
        'pricing'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2l9 9-9 11-9-11 9-9zm0 4l-5 5 5 6 5-6-5-5z"/></svg>',
        'professionals'      => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm-7 16c.6-3.2 3.4-6 7-6s6.4 2.8 7 6H5zm10-11h2v2h-2V9z"/></svg>',
        'profile'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
        'locked'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17 9h1a2 2 0 0 1 2 2v9H4v-9a2 2 0 0 1 2-2h1V7a5 5 0 0 1 10 0v2zm-2 0V7a3 3 0 0 0-6 0v2h6z"/></svg>',
        'providers'          => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 5h16v14H4V5zm2 2v10h12V7H6zm2 2h4v4H8V9zm6 0h2v2h-2V9zm0 4h2v2h-2v-2z"/></svg>',
        'push-notifications' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-2.6-1.4-4.8-4-5.6V4a2 2 0 0 0-4 0v1.4c-2.6.8-4 3-4 5.6v5l-2 2v1h16v-1l-2-2zm3-11l-2 2-2-2 2-2 2 2z"/></svg>',
        'app'                => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 4v12h10V6H7zm4 13h2v1h-2v-1z"/></svg>',
        'search'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M9.5 3a6.5 6.5 0 0 1 5.18 10.42l5.45 5.45-1.42 1.42-5.45-5.45A6.5 6.5 0 1 1 9.5 3zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>',
        'series'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 4h12v3H4V4zm2 5h12v3H6V9zm2 5h12v6H8v-6z"/></svg>',
        'service-business'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 4h4a2 2 0 0 1 2 2v2h4a2 2 0 0 1 2 2v3H0v-3a2 2 0 0 1 2-2h4V6a2 2 0 0 1 2-2zm6 4V6a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v2h8zm8 7v5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-5h9v2h6v-2h9z"/></svg>',
        'service-worship'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M11 2h2v3h2v2h-2v2h-2V7H9V5h2V2zm1 7l7 4v9h-5v-5H10v5H5v-9l7-4zm-5 6h10v-1.6l-5-2.86-5 2.86V15z"/></svg>',
        'settings'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>',
        'shares'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18 16.1c-.76 0-1.44.3-1.96.77L8.9 12.7c.05-.23.1-.46.1-.7s-.05-.47-.1-.7l7.05-4.11A3 3 0 1 0 15 5c0 .24.05.47.1.7L8.05 9.81A3 3 0 1 0 8.05 14l7.12 4.18c-.04.2-.07.41-.07.62a2.9 2.9 0 1 0 2.9-2.7z"/></svg>',
        'shop'               => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>',
        'support'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/></svg>',
        'topics'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4 5h16v11H7l-3 3V5zm4 3h8v2H8V8zm0 4h6v2H8v-2z"/></svg>',
        'upgrade'            => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 3l6 6h-4v8h-4V9H6l6-6zm-7 16h14v2H5v-2z"/></svg>',
        'upload'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 3l5 5h-3v7h-4V8H7l5-5zm-7 14h14v4H5v-4z"/></svg>',
        'verified'           => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M23 12l-2.44-2.79.34-3.69-3.61-.82-1.89-3.2L12 2.96 8.6 1.5 6.71 4.69 3.1 5.5l.34 3.7L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69L23 12zm-12.91 4.72l-3.8-3.81 1.48-1.48 2.32 2.33 5.85-5.87 1.48 1.48-7.33 7.35z"/></svg>',
        'video'              => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm3 3v8l6-4-6-4z"/></svg>',
        'videos'             => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4zM14 13h-3v3H9v-3H6v-2h3V8h2v3h3v2z"/></svg>',
    );
}

/**
 * Get inline SVG for icon slug.
 *
 * @param string $slug Icon slug.
 * @return string Inline SVG markup or empty string if invalid.
 */
function vh360_get_menu_icon_svg( $slug ) {
    $slug  = sanitize_key( $slug );
    $icons = vh360_menu_icon_svg_registry();

    if ( ! array_key_exists( $slug, $icons ) ) {
        return '';
    }

    return $icons[ $slug ];
}

/**
 * Get inline SVG for Community Menu icon slug.
 *
 * Kept for backward compatibility with existing integrations.
 *
 * @param string $slug Icon slug.
 * @return string Inline SVG markup or empty string if invalid.
 */
function vh360_cm_get_icon_svg( $slug ) {
    return vh360_get_menu_icon_svg( $slug );
}

/**
 * Find selectable menu icons that are missing SVGs.
 *
 * Useful for development/testing without running on every frontend request.
 *
 * @return array Icon slugs present in choices but missing from the SVG registry.
 */
function vh360_missing_menu_icon_svgs() {
    return array_values( array_diff( array_keys( vh360_menu_icon_choices() ), array_keys( vh360_menu_icon_svg_registry() ) ) );
}
