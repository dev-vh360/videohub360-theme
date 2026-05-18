<?php
/**
 * User Menu Helper Functions
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Replace dynamic placeholder URLs (e.g. #user-profile) inside ANY wp_nav_menu() output.
 *
 * Context:
 * - The "User Menu Items" meta box adds menu items with placeholder URLs like "#user-profile".
 * - Those items can be inserted into *any* menu/location (header, footer, etc.).
 * - The header's built-in profile link works because it is generated directly via theme code.
 * - Menu items created in Appearance > Menus won't work unless we translate placeholders at render time.
 */
function vh360_translate_dynamic_menu_placeholders($sorted_menu_items, $args) {
    if (is_admin() || !is_user_logged_in() || empty($sorted_menu_items)) {
        return $sorted_menu_items;
    }

    $current_user_id = get_current_user_id();

    foreach ($sorted_menu_items as $menu_item) {
        if (empty($menu_item) || empty($menu_item->url)) {
            continue;
        }

        // Detect placeholder URL stored as either "#user-profile" or a full URL that contains the fragment.
        $fragment = (string) parse_url($menu_item->url, PHP_URL_FRAGMENT);
        if ($menu_item->url === VH360_USER_PROFILE_PLACEHOLDER || $fragment === 'user-profile') {
            $profile_url = vh360_get_profile_url($current_user_id);
            if (!empty($profile_url)) {
                $menu_item->url = esc_url($profile_url);
            }
            continue;
        }

        // Normalize logout items to the theme's custom logout handler.
        // (Same behavior as vh360_get_custom_user_menu_items(), but applied globally.)
        if (strpos($menu_item->url, 'wp-login.php') !== false && strpos($menu_item->url, 'action=logout') !== false) {
            $redirect_to = '';
            $parsed_url = parse_url($menu_item->url);
            if (!empty($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                if (!empty($query_params['redirect_to'])) {
                    $redirect_to = $query_params['redirect_to'];
                }
            }
            $menu_item->url = vh360_get_logout_url($redirect_to);
        }
    }

    return $sorted_menu_items;
}
add_filter('wp_nav_menu_objects', 'vh360_translate_dynamic_menu_placeholders', 20, 2);

/**
 * Available user menu icons
 */
define('VH360_USER_MENU_ICONS', array(
    'dashboard',
    'profile',
    'edit',
    'videos',
    'activity',
    'settings',
    'signout',
    'members',
));

/**
 * Default icon for menu items without specific icon
 */
define('VH360_USER_MENU_DEFAULT_ICON', 'default');

/**
 * Placeholder URL for dynamic profile links
 */
define('VH360_USER_PROFILE_PLACEHOLDER', '#user-profile');

/**
 * Get user menu items
 * 
 * @return string Menu items HTML
 */
function vh360_get_user_menu_items() {
    if (!is_user_logged_in()) {
        return '';
    }
    
    $current_user_id = get_current_user_id();
    
    // Check if a custom menu is assigned to the user-menu location
    if (has_nav_menu('user-menu')) {
        return vh360_get_custom_user_menu_items($current_user_id);
    }
    
    // Default menu items (fallback)
    $menu_items = vh360_get_default_user_menu_items($current_user_id);
    
    /**
     * Filter user menu items
     * 
     * @param array $menu_items Array of menu items
     * @param int $current_user_id Current user ID
     */
    $menu_items = apply_filters('vh360_user_menu_items', $menu_items, $current_user_id);
    
    $output = '';
    
    foreach ($menu_items as $item) {
        // Add divider if needed
        if (!empty($item['divider_before'])) {
            $output .= '<div class="vh360-user-menu-divider"></div>';
        }
        
        $output .= vh360_get_menu_item_html(
            $item['url'],
            $item['label'],
            $item['icon'],
            isset($item['args']) ? $item['args'] : array()
        );
    }
    
    return $output;
}

/**
 * Get default user menu items
 * 
 * @param int $current_user_id Current user ID
 * @return array Default menu items (empty - users must create their own menu)
 */
function vh360_get_default_user_menu_items($current_user_id) {
    // Return empty array - users must create their own user menu via Appearance > Menus
    return array();
}

/**
 * Get custom user menu items from WordPress menu
 * 
 * @param int $current_user_id Current user ID
 * @return string Menu items HTML
 */
function vh360_get_custom_user_menu_items($current_user_id) {
    // Load the *actual* menu assigned to the 'user-menu' theme location.
    // NOTE: wp_get_nav_menu_items('user-menu') attempts to load a menu by name/slug,
    // which is not the same as the theme location assignment and can return the wrong menu.
    $locations = get_nav_menu_locations();
    if (empty($locations['user-menu'])) {
        return '';
    }

    $menu_id = (int) $locations['user-menu'];
    $menu_items = wp_get_nav_menu_items($menu_id);
    
    if (!$menu_items) {
        return '';
    }
    
    $output = '';
    $prev_item = null;
    
    foreach ($menu_items as $menu_item) {
        // Replace placeholder URL with the actual current user's profile URL.
        // WordPress may store/sanitize custom URLs as full URLs containing a fragment,
        // e.g. https://example.com/#user-profile, so we check both the raw value and fragment.
        $menu_item_fragment = '';
        if (!empty($menu_item->url)) {
            $menu_item_fragment = (string) parse_url($menu_item->url, PHP_URL_FRAGMENT);
        }

        if ($menu_item->url === VH360_USER_PROFILE_PLACEHOLDER || $menu_item_fragment === 'user-profile') {
            $profile_url = vh360_get_profile_url($current_user_id);
            if (!empty($profile_url)) {
                $menu_item->url = esc_url($profile_url);
            }
        }
        
        // Replace WordPress logout URLs with custom logout handler
        // Check if this is a logout URL (wp-login.php AND action=logout)
        if (strpos($menu_item->url, 'wp-login.php') !== false && strpos($menu_item->url, 'action=logout') !== false) {
            // Extract redirect_to parameter if present
            // Note: redirect_to validation happens in vh360_handle_custom_logout() handler
            $redirect_to = '';
            $parsed_url = parse_url($menu_item->url);
            if (!empty($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                if (!empty($query_params['redirect_to'])) {
                    $redirect_to = $query_params['redirect_to'];
                }
            }
            // Replace with custom logout URL
            $menu_item->url = vh360_get_logout_url($redirect_to);
        }
        
        // Check if this item should have a divider before it
        // This can be controlled via menu item CSS classes
        $classes = $menu_item->classes;
        $has_divider = in_array('divider-before', $classes) || in_array('menu-divider', $classes);
        
        if ($has_divider && $prev_item !== null) {
            $output .= '<div class="vh360-user-menu-divider"></div>';
        }
        
        // Determine icon from CSS classes or description field
        $icon = vh360_get_menu_item_icon_from_classes($classes);
        if (!$icon && !empty($menu_item->description)) {
            // Allow icon name in description field, but validate it
            $potential_icon = sanitize_key($menu_item->description);
            if (in_array($potential_icon, VH360_USER_MENU_ICONS)) {
                $icon = $potential_icon;
            }
        }
        if (!$icon) {
            $icon = VH360_USER_MENU_DEFAULT_ICON; // Use a default icon if none specified
        }
        
        $args = array(
            'class' => implode(' ', $classes),
            'target' => $menu_item->target,
        );
        
        $output .= vh360_get_menu_item_html(
            $menu_item->url,
            $menu_item->title,
            $icon,
            $args
        );
        
        $prev_item = $menu_item;
    }
    
    return $output;
}

/**
 * Get icon name from menu item CSS classes
 * 
 * @param array $classes Menu item CSS classes
 * @return string Icon name or empty string
 */
function vh360_get_menu_item_icon_from_classes($classes) {
    // Look for icon classes like 'icon-dashboard', 'menu-icon-profile', etc.
    $icon_prefixes = array('icon-', 'menu-icon-', 'vh360-icon-');
    
    foreach ($classes as $class) {
        foreach ($icon_prefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                $icon_name = str_replace($prefix, '', $class);
                // Validate the extracted icon name
                if (in_array($icon_name, VH360_USER_MENU_ICONS)) {
                    return $icon_name;
                }
            }
        }
    }
    
    // Check for exact icon name matches (without prefix)
    foreach ($classes as $class) {
        if (in_array($class, VH360_USER_MENU_ICONS)) {
            return $class;
        }
    }
    
    return '';
}

/**
 * Get menu item HTML
 * 
 * @param string $url Menu item URL
 * @param string $label Menu item label
 * @param string $icon SVG icon name
 * @param array $args Additional arguments
 * @return string Menu item HTML
 */
function vh360_get_menu_item_html($url, $label, $icon, $args = array()) {
    $defaults = array(
        'class' => '',
        'target' => '',
        'rel' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $classes = array('vh360-user-menu-item');
    if (!empty($args['class'])) {
        $classes[] = esc_attr($args['class']);
    }
    
    $icon_html = vh360_get_user_menu_icon($icon);
    
    $attrs = '';
    if (!empty($args['target'])) {
        $attrs .= ' target="' . esc_attr($args['target']) . '"';
    }
    if (!empty($args['rel'])) {
        $attrs .= ' rel="' . esc_attr($args['rel']) . '"';
    }
    
    $output = sprintf(
        '<a href="%s" class="%s"%s>%s<span class="vh360-menu-item-label">%s</span></a>',
        esc_url($url),
        esc_attr(implode(' ', $classes)),
        $attrs,
        $icon_html,
        esc_html($label)
    );
    
    return $output;
}

/**
 * Get user menu icon
 * 
 * @param string $icon_name Icon name
 * @return string SVG icon HTML
 */
function vh360_get_user_menu_icon($icon_name) {
    // Default icon (dots)
    $default_icon = '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>';
    
    $icons = array(
        'dashboard' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
        'profile' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        'edit' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
        'videos' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
        'activity' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>',
        'settings' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m8.66-15.66l-4.24 4.24m-8.49 8.49l-4.24 4.24M23 12h-6m-6 0H1m20.66 8.66l-4.24-4.24m-8.49-8.49l-4.24-4.24"></path></svg>',
        'signout' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
        'members' => '<svg class="vh360-menu-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'default' => $default_icon,
    );
    
    // Return requested icon or default
    if (isset($icons[$icon_name])) {
        return $icons[$icon_name];
    }
    
    // Safety fallback
    return isset($icons['default']) ? $icons['default'] : $default_icon;
}

/**
 * Register User Menu meta box for WordPress menu admin
 */
function vh360_register_user_menu_meta_box() {
    add_meta_box(
        'vh360-user-menu-items',
        __('User Menu Items', 'videohub360-theme'),
        'vh360_render_user_menu_meta_box',
        'nav-menus',
        'side',
        'default'
    );
}
add_action('admin_init', 'vh360_register_user_menu_meta_box');

/**
 * Render the User Menu Items meta box
 */
function vh360_render_user_menu_meta_box() {
    global $_nav_menu_placeholder;
    
    // Initialize placeholder ID for WordPress menu items
    // WordPress uses negative IDs for custom links that haven't been saved yet
    // Decrement to ensure unique negative ID
    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
    
    $current_user_id = get_current_user_id();
    
    // Define user menu items
    $user_menu_items = array(
        array(
            'title' => __('Dashboard', 'videohub360-theme'),
            'url' => home_url('/dashboard/'),
            'icon' => 'dashboard',
        ),
        array(
            'title' => __('My Profile', 'videohub360-theme'),
            'url' => VH360_USER_PROFILE_PLACEHOLDER,  // Placeholder that will be replaced dynamically
            'icon' => 'profile',
        ),
        array(
            'title' => __('Edit Profile', 'videohub360-theme'),
            'url' => function_exists( 'vh360_get_profile_edit_url' ) ? vh360_get_profile_edit_url() : home_url('/dashboard/?tab=profile'),
            'icon' => 'edit',
        ),
        array(
            'title' => __('My Videos', 'videohub360-theme'),
            'url' => home_url('/dashboard/#videos'),
            'icon' => 'videos',
        ),
        array(
            'title' => __('Members Directory', 'videohub360-theme'),
            'url' => home_url('/members/'),
            'icon' => 'members',
        ),
        array(
            'title' => __('Activity Feed', 'videohub360-theme'),
            'url' => home_url('/activity/'),
            'icon' => 'activity',
        ),
        array(
            'title' => __('Settings', 'videohub360-theme'),
            'url' => home_url('/dashboard/#settings'),
            'icon' => 'settings',
        ),
        array(
            'title' => __('Sign Out', 'videohub360-theme'),
            'url' => vh360_get_logout_url(home_url('/')),
            'icon' => 'signout',
        ),
    );
    
    /**
     * Filter user menu items available in meta box
     * 
     * @param array $user_menu_items Array of menu items
     */
    $user_menu_items = apply_filters('vh360_user_menu_meta_box_items', $user_menu_items);
    ?>
    
    <div id="posttype-vh360-user-menu" class="posttypediv">
        <div id="tabs-panel-vh360-user-menu" class="tabs-panel tabs-panel-active">
            <ul id="vh360-user-menu-checklist" class="categorychecklist form-no-clear">
                <?php foreach ($user_menu_items as $key => $item) : 
                    $_nav_menu_placeholder++;
                ?>
                <li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" 
                               name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-object-id]" 
                               value="-1" /> 
                        <?php echo esc_html($item['title']); ?>
                    </label>
                    <input type="hidden" class="menu-item-type" 
                           name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-type]" 
                           value="custom" />
                    <input type="hidden" class="menu-item-title" 
                           name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-title]" 
                           value="<?php echo esc_attr($item['title']); ?>" />
                    <input type="hidden" class="menu-item-url" 
                           name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-url]" 
                           value="<?php echo esc_url($item['url']); ?>" />
                    <input type="hidden" class="menu-item-classes" 
                           name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-classes]" 
                           value="icon-<?php echo esc_attr($item['icon']); ?>" />
                    <input type="hidden" class="menu-item-description" 
                           name="menu-item[<?php echo esc_attr($_nav_menu_placeholder); ?>][menu-item-description]" 
                           value="<?php echo esc_attr($item['icon']); ?>" />
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p class="button-controls">
            <span class="list-controls">
                <a href="<?php echo esc_url(admin_url('nav-menus.php?page-tab=all&selectall=1#posttype-vh360-user-menu')); ?>" class="select-all aria-button-if-js"><?php esc_html_e('Select All', 'videohub360-theme'); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button-secondary submit-add-to-menu right" 
                       value="<?php esc_attr_e('Add to Menu', 'videohub360-theme'); ?>" 
                       name="add-post-type-menu-item" id="submit-posttype-vh360-user-menu" />
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/**
 * Register Mobile Drawer Items meta box for WordPress menu admin
 */
function vh360_register_mobile_drawer_menu_meta_box() {
    add_meta_box(
        'vh360-mobile-drawer-items',
        __('Mobile Drawer Items', 'videohub360-theme'),
        'vh360_render_mobile_drawer_menu_meta_box',
        'nav-menus',
        'side',
        'default'
    );
}
add_action('admin_init', 'vh360_register_mobile_drawer_menu_meta_box');

/**
 * Render the Mobile Drawer Items meta box
 *
 * Uses the centralized dashboard tabs registry to build drawer items,
 * eliminating duplication and ensuring consistency with dashboard navigation.
 */
function vh360_render_mobile_drawer_menu_meta_box() {
    global $_nav_menu_placeholder;
    
    // Continue from current placeholder value to avoid ID collisions
    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
    
    // Get drawer menu items from the shared helper (using query URL format)
    $drawer_menu_items = vh360_get_dashboard_surface_item_definitions( 'query' );
    
    /**
     * Filter mobile drawer items available in meta box
     * 
     * @param array $drawer_menu_items Array of drawer menu items
     */
    $drawer_menu_items = apply_filters('vh360_mobile_drawer_meta_box_items', $drawer_menu_items);
    ?>
    
    <div id="posttype-vh360-mobile-drawer" class="posttypediv">
        <div id="tabs-panel-vh360-mobile-drawer" class="tabs-panel tabs-panel-active">
            <p class="description" style="margin: 10px 12px;">
                <?php esc_html_e( 'Mobile drawer items are built from the dashboard tabs registry. Visibility and labels automatically match dashboard permissions and account types.', 'videohub360-theme' ); ?>
            </p>
            <ul id="vh360-mobile-drawer-checklist" class="categorychecklist form-no-clear">
                <?php 
                foreach ($drawer_menu_items as $drawer_item) : 
                    $item_id = $_nav_menu_placeholder;
                    $_nav_menu_placeholder--;
                ?>
                <li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" 
                               name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-object-id]" 
                               value="<?php echo esc_attr($item_id); ?>" /> 
                        <?php echo esc_html($drawer_item['title']); ?>
                    </label>
                    <input type="hidden" class="menu-item-type" 
                           name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-type]" 
                           value="custom" />
                    <input type="hidden" class="menu-item-title" 
                           name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-title]" 
                           value="<?php echo esc_attr($drawer_item['title']); ?>" />
                    <input type="hidden" class="menu-item-url" 
                           name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-url]" 
                           value="<?php echo esc_url($drawer_item['url']); ?>" />
                    <input type="hidden" class="menu-item-classes" 
                           name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-classes]" 
                           value="" />
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p class="button-controls wp-clearfix">
            <span class="list-controls">
                <a href="<?php echo esc_url(admin_url('nav-menus.php#posttype-vh360-mobile-drawer')); ?>" class="select-all"><?php esc_html_e('Select All', 'videohub360-theme'); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button submit-add-to-menu right" 
                       value="<?php esc_attr_e('Add to Menu', 'videohub360-theme'); ?>" 
                       name="add-post-type-menu-item" id="submit-posttype-vh360-mobile-drawer" />
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/**
 * Get a menu item by CSS class from a theme location
 * 
 * @param string $location Theme location slug (e.g. 'vh360_mobile_drawer')
 * @param string $class CSS class to search for
 * @return WP_Post|null Menu item object or null if not found
 */
function vh360_get_menu_item_by_class( $location, $class ) {
    if ( empty( $location ) || empty( $class ) ) {
        return null;
    }
    
    // Resolve theme location to menu term ID
    $locations = get_nav_menu_locations();
    if ( empty( $locations[ $location ] ) ) {
        return null;
    }
    
    $menu_id = (int) $locations[ $location ];
    if ( $menu_id <= 0 ) {
        return null;
    }
    
    // Fetch items from the menu
    $items = wp_get_nav_menu_items( $menu_id );
    if ( empty( $items ) || ! is_array( $items ) ) {
        return null;
    }
    
    // Return the first item whose classes contains $class
    foreach ( $items as $item ) {
        if ( vh360_menu_item_has_class( $item, $class ) ) {
            return $item;
        }
    }
    
    return null;
}

/**
 * Check if a menu item has a specific CSS class
 * 
 * @param WP_Post|object $item Menu item object
 * @param string $class CSS class to check for
 * @return bool True if item has the class, false otherwise
 */
function vh360_menu_item_has_class( $item, $class ) {
    if ( empty( $item ) || empty( $class ) ) {
        return false;
    }
    
    // Read $item->classes safely (array)
    $classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
    
    // Use strict comparison
    return in_array( $class, $classes, true );
}

/**
 * Remove items with a specific class from a menu item list
 * 
 * @param array $items Array of menu item objects
 * @param string $class CSS class to filter out
 * @return array Filtered array of menu items excluding those with the specified class
 */
function vh360_filter_menu_items_excluding_class( $items, $class ) {
    if ( empty( $items ) || ! is_array( $items ) || empty( $class ) ) {
        return $items;
    }
    
    // Return items excluding those where vh360_menu_item_has_class() is true
    return array_filter( $items, function( $item ) use ( $class ) {
        return ! vh360_menu_item_has_class( $item, $class );
    } );
}
