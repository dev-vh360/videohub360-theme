<?php
/**
 * Dashboard Tabs Registry
 *
 * Single source of truth for all dashboard tabs including IDs, labels,
 * visibility rules, icons, and dynamic label logic.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get Dashboard Tabs Registry
 *
 * Returns the complete registry of all dashboard tabs with their configuration.
 * This is the single source of truth for dashboard navigation.
 *
 * @param int|null $user_id User ID for context-sensitive visibility and labels. Defaults to current user.
 * @return array Associative array keyed by tab ID with configuration for each tab.
 * @since 1.0.0
 */
function vh360_get_dashboard_tabs_registry( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    $user_account_type = function_exists( 'vh360_get_user_account_type' ) ? vh360_get_user_account_type( $user_id ) : 'creator';
    $is_approved_professional = function_exists( 'vh360_is_professional_approved' ) ? vh360_is_professional_approved( $user_id ) : false;
    $is_pro_or_org = in_array( $user_account_type, array( 'professional', 'organization' ), true );

    $tabs = array(
        'overview' => array(
            'label' => __( 'Overview', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13h8V3H3v10zM13 21h8V11h-8v10zM13 3v6h8V3h-8zM3 21h8v-6H3v6z"/></svg>',
        ),
        'create-video' => array(
            'label' => __( '+ Upload', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
        ),
        'videos' => array(
            'label' => __( 'My Videos', 'videohub360-theme' ),
            'label_callback' => function( $user_id ) {
                if ( function_exists( 'vh360_dashboard_uses_lesson_labels' ) && vh360_dashboard_uses_lesson_labels( $user_id ) ) {
                    $lesson_label = function_exists( 'vh360_get_lesson_label' )
                        ? vh360_get_lesson_label( true )
                        : __( 'Lessons', 'videohub360-theme' );

                    return sprintf(
                        /* translators: %s = plural lesson label */
                        __( 'My %s', 'videohub360-theme' ),
                        $lesson_label
                    );
                }

                return __( 'My Videos', 'videohub360-theme' );
            },
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
        ),
        'courses' => array(
            'label' => __( 'My Courses', 'videohub360-theme' ),
            'label_callback' => function( $user_id ) {
                if ( function_exists( 'videohub360_get_course_label' ) ) {
                    return sprintf(
                        /* translators: %s = plural course label */
                        __( 'My %s', 'videohub360-theme' ),
                        videohub360_get_course_label( true )
                    );
                }
                return __( 'My Courses', 'videohub360-theme' );
            },
            'show_callback' => function( $user_id ) {
                $course_enabled = function_exists( 'videohub360_course_features_enabled' ) && videohub360_course_features_enabled();
                if ( ! $course_enabled ) {
                    return false;
                }
                if ( function_exists( 'vh360_user_can_create_videos' ) ) {
                    return vh360_user_can_create_videos( $user_id );
                }
                return current_user_can( 'manage_options' ) || current_user_can( 'vh360_create_videos' );
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"></path></svg>',
        ),
        'learning' => array(
            'label' => __( 'My Learning', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                return is_user_logged_in()
                    && function_exists( 'videohub360_course_features_enabled' )
                    && videohub360_course_features_enabled();
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>',
        ),
        'live-rooms' => array(
            'label' => __( 'Live Rooms', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="13" height="12" rx="2"></rect><path d="M15 10l5-3v10l-5-3V10z"></path></svg>',
        ),
        'messages' => array(
            'label' => __( 'Messages', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5A8.5 8.5 0 0 1 21 11v.5z"/></svg>',
        ),
        'notifications' => array(
            'label' => __( 'Notifications', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
        ),
        'push-notifications' => array(
            'label' => __( 'Push Notifications', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                return current_user_can( 'vh360_send_push' );
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"></path><path d="M22 2L15 22l-4-9-9-4 20-7z"></path></svg>',
        ),
        'liked-videos' => array(
            'label' => __( 'Liked Videos', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
        ),
        'playlists' => array(
            'label' => __( 'My Playlists', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"></path><rect x="15" y="9" width="6" height="6" rx="1"></rect></svg>',
        ),
        'create-post' => array(
            'label' => __( '+ Blog Posts', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>',
        ),
        'profile' => array(
            'label' => __( 'Edit Profile', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        ),
        'business-profile' => array(
            'label' => __( 'Business Profile', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                $account_type = function_exists( 'vh360_get_user_account_type' ) ? vh360_get_user_account_type( $user_id ) : 'creator';
                return in_array( $account_type, array( 'professional', 'organization' ), true );
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
        ),
        'galleries' => array(
            'label' => __( 'Galleries', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>',
        ),
        'events' => array(
            'label' => __( 'Events', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                $account_type = function_exists( 'vh360_get_user_account_type' ) ? vh360_get_user_account_type( $user_id ) : 'creator';
                $is_approved = function_exists( 'vh360_is_professional_approved' ) ? vh360_is_professional_approved( $user_id ) : false;
                // Hide for unapproved professionals
                if ( $account_type === 'professional' && ! $is_approved ) {
                    return false;
                }
                return true;
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path></svg>',
        ),
        'appointments' => array(
            'label' => __( 'Appointments', 'videohub360-theme' ),
            'label_callback' => function( $user_id ) {
                $account_type = function_exists( 'vh360_get_user_account_type' ) ? vh360_get_user_account_type( $user_id ) : 'creator';
                // Professionals see "Appointments", others see "My Appointments"
                if ( in_array( $account_type, array( 'professional', 'organization' ), true ) ) {
                    return __( 'Appointments', 'videohub360-theme' );
                } else {
                    return __( 'My Appointments', 'videohub360-theme' );
                }
            },
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><circle cx="12" cy="13" r="3" fill="currentColor"></circle></svg>',
        ),
        'availability' => array(
            'label' => __( 'Availability', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                $account_type = function_exists( 'vh360_get_user_account_type' ) ? vh360_get_user_account_type( $user_id ) : 'creator';
                $is_approved = function_exists( 'vh360_is_professional_approved' ) ? vh360_is_professional_approved( $user_id ) : false;
                // Only show for approved professionals/organizations
                return in_array( $account_type, array( 'professional', 'organization' ), true ) && $is_approved;
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        ),
        'bulletins' => array(
            'label' => __( 'Bulletins', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        ),
        'invites' => array(
            'label' => __( 'Invites', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => function( $user_id ) {
                return function_exists( 'vh360_user_can_create_invites' ) && vh360_user_can_create_invites( $user_id );
            },
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"></path><path d="M22 6l-10 7L2 6"></path></svg>',
        ),
        'settings' => array(
            'label' => __( 'Settings', 'videohub360-theme' ),
            'label_callback' => null,
            'show_callback' => '__return_true',
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 0 1-1.4 3.4h-.2a1.9 1.9 0 0 0-1.8 1.1 2 2 0 0 1-3.6 0 1.9 1.9 0 0 0-1.8-1.1H9a2 2 0 0 1-1.4-3.4l.1-.1A1.7 1.7 0 0 0 8.3 15a1.9 1.9 0 0 0-1.1-1.8 2 2 0 0 1 0-3.6 1.9 1.9 0 0 0 1.1-1.8 1.7 1.7 0 0 0-.3-1.8l-.1-.1A2 2 0 0 1 9 2.3h.2a1.9 1.9 0 0 0 1.8-1.1 2 2 0 0 1 3.6 0 1.9 1.9 0 0 0 1.8 1.1h.2a2 2 0 0 1 1.4 3.4l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.9 1.9 0 0 0 1.1 1.8 2 2 0 0 1 0 3.6 1.9 1.9 0 0 0-1.1 1.8z"></path></svg>',
        ),
    );

    /**
     * Filter dashboard tabs registry.
     *
     * Allows plugins to add or modify dashboard tabs configuration.
     *
     * @param array $tabs    Associative array of tab configurations.
     * @param int   $user_id User ID for context.
     * @since 1.0.0
     */
    return apply_filters( 'vh360_dashboard_tabs_registry', $tabs, $user_id );
}

/**
 * Get Dashboard Surface Item Definitions
 *
 * Shared helper that builds menu item definitions from the dashboard tabs registry.
 * Used by both Dashboard Menu Items and Mobile Drawer meta boxes to maintain consistency.
 *
 * Frontend context respects runtime show_callback visibility. Menu-builder context can
 * include admin-addable items that may not currently be frontend-enabled when a tab
 * explicitly opts in with show_in_menu_builder or menu_builder_callback.
 *
 * @param string   $url_format How to format URLs: 'fragment' for #tab-id, 'query' for ?tab=tab-id.
 * @param int|null $user_id    User ID for context-sensitive visibility and labels. Defaults to current user.
 * @param string   $context    Visibility context: 'frontend' or 'menu_builder'. Defaults to 'frontend'.
 * @return array Array of menu item definitions with id, title, menu_title, and url.
 * @since 1.0.0
 */
function vh360_get_dashboard_surface_item_definitions( $url_format = 'fragment', $user_id = null, $context = 'frontend' ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    // Get dashboard base URL
    $dashboard_url = function_exists( 'vh360_get_dashboard_page_url' ) 
        ? vh360_get_dashboard_page_url() 
        : home_url( '/dashboard/' );

    // Get the full registry with all tab configurations
    $registry = vh360_get_dashboard_tabs_registry( $user_id );

    $definitions = array();

    $is_menu_builder = ( 'menu_builder' === $context );

    foreach ( $registry as $tab_id => $tab_config ) {
        if ( $is_menu_builder ) {
            if ( isset( $tab_config['menu_builder_callback'] ) && is_callable( $tab_config['menu_builder_callback'] ) ) {
                if ( ! call_user_func( $tab_config['menu_builder_callback'], $user_id ) ) {
                    continue;
                }
            } elseif ( empty( $tab_config['show_in_menu_builder'] ) ) {
                $show_callback = isset( $tab_config['show_callback'] ) ? $tab_config['show_callback'] : null;

                if ( $show_callback && is_callable( $show_callback ) && ! call_user_func( $show_callback, $user_id ) ) {
                    continue;
                }
            }
        } else {
            $show_callback = isset( $tab_config['show_callback'] ) ? $tab_config['show_callback'] : null;

            if ( $show_callback && is_callable( $show_callback ) && ! call_user_func( $show_callback, $user_id ) ) {
                continue;
            }
        }

        // Get display label (use context-aware label callbacks if available, otherwise use default label)
        if ( $is_menu_builder && ! empty( $tab_config['menu_builder_label_callback'] ) && is_callable( $tab_config['menu_builder_label_callback'] ) ) {
            $label = call_user_func( $tab_config['menu_builder_label_callback'], $user_id );
        } elseif ( ! empty( $tab_config['label_callback'] ) && is_callable( $tab_config['label_callback'] ) ) {
            $label = call_user_func( $tab_config['label_callback'], $user_id );
        } else {
            $label = $tab_config['label'];
        }

        // Get the clean title saved to WordPress menu items. This may differ from
        // the menu-builder display label, which can include admin-only notes.
        if ( $is_menu_builder && ! empty( $tab_config['menu_builder_menu_title_callback'] ) && is_callable( $tab_config['menu_builder_menu_title_callback'] ) ) {
            $menu_title = call_user_func( $tab_config['menu_builder_menu_title_callback'], $user_id );
        } elseif ( $is_menu_builder && isset( $tab_config['menu_builder_menu_title'] ) ) {
            $menu_title = $tab_config['menu_builder_menu_title'];
        } elseif ( ! empty( $tab_config['menu_title_callback'] ) && is_callable( $tab_config['menu_title_callback'] ) ) {
            $menu_title = call_user_func( $tab_config['menu_title_callback'], $user_id );
        } elseif ( isset( $tab_config['menu_title'] ) ) {
            $menu_title = $tab_config['menu_title'];
        } elseif ( ! empty( $tab_config['label_callback'] ) && is_callable( $tab_config['label_callback'] ) ) {
            $menu_title = call_user_func( $tab_config['label_callback'], $user_id );
        } else {
            $menu_title = $tab_config['label'];
        }

        // Build URL based on format
        if ( $url_format === 'query' ) {
            // Query-based URL format: ?tab=tab-id (used by mobile drawer)
            $url = add_query_arg( 'tab', $tab_id, $dashboard_url );
        } else {
            // Fragment-based URL format: #tab-id (default, used by dashboard menu)
            $url = $dashboard_url . '#' . $tab_id;
        }

        $definitions[] = array(
            'id'    => 'vh360-dashboard-' . $tab_id,
            'title'      => $label,
            'menu_title' => $menu_title,
            'url'        => $url,
            'tab'   => $tab_id, // Include tab ID for compatibility
        );
    }

    /**
     * Filter dashboard surface item definitions.
     *
     * Allows plugins to add or modify dashboard surface items.
     *
     * @param array  $definitions Array of menu item definitions.
     * @param string $url_format  The URL format being used.
     * @param int    $user_id     User ID for context.
     * @param string $context     Visibility context.
     * @since 1.0.0
     */
    return apply_filters( 'vh360_dashboard_surface_item_definitions', $definitions, $url_format, $user_id, $context );
}
