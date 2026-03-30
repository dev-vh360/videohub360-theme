<?php
/**
 * VH360 Menu Meta Boxes
 *
 * Adds "Dashboard Menu Items" and "Mobile Bottom Nav Items" meta boxes
 * to the Appearance → Menus screen, allowing admins to easily add
 * preconfigured menu items with correct URLs and CSS classes.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register VH360 custom menu meta boxes
 */
function vh360_register_nav_menu_meta_boxes() {
    add_meta_box(
        'vh360-dashboard-menu-items',
        __('Dashboard Menu Items', 'videohub360-theme'),
        'vh360_render_dashboard_menu_items_meta_box',
        'nav-menus',
        'side',
        'default'
    );

    add_meta_box(
        'vh360-mobile-bottom-menu-items',
        __('Mobile Bottom Nav Items', 'videohub360-theme'),
        'vh360_render_mobile_bottom_menu_items_meta_box',
        'nav-menus',
        'side',
        'default'
    );
}
add_action('admin_init', 'vh360_register_nav_menu_meta_boxes');

/**
 * Get Dashboard Menu Item Definitions
 *
 * Returns an array of dashboard tab items with correct URLs and labels.
 * Built from the dashboard tabs registry for consistency.
 *
 * @return array Array of menu item definitions.
 * @since 1.0.0
 */
function vh360_get_dashboard_menu_item_definitions() {
    $dashboard_url = vh360_get_dashboard_page_url();
    $registry = vh360_get_dashboard_tabs_registry();

    $definitions = array();

    foreach ( $registry as $tab_id => $tab_config ) {
        // Get label (use callback if available, otherwise use default label)
        $label = $tab_config['label'];
        if ( $tab_config['label_callback'] && is_callable( $tab_config['label_callback'] ) ) {
            $label = call_user_func( $tab_config['label_callback'], get_current_user_id() );
        }

        $definitions[] = array(
            'id'    => 'vh360-dashboard-' . $tab_id,
            'title' => $label,
            'url'   => $dashboard_url . '#' . $tab_id,
        );
    }

    /**
     * Filter dashboard menu item definitions.
     *
     * Allows plugins to add or modify dashboard menu items.
     *
     * @param array $definitions Array of menu item definitions.
     * @since 1.0.0
     */
    return apply_filters( 'vh360_dashboard_menu_item_definitions', $definitions );
}

/**
 * Get Mobile Bottom Nav Item Definitions
 *
 * Returns an array of mobile bottom navigation items with correct URLs and icon meta.
 * Icon meta is used for icon display via the shared icon system, and specific CSS 
 * classes are used only for special behavior flags (avatar drawer, notification badge).
 *
 * @return array Array of menu item definitions.
 * @since 1.0.0
 */
function vh360_get_mobile_bottom_nav_item_definitions() {
    $dashboard_url = vh360_get_dashboard_page_url();

    $definitions = array(
        array(
            'id'      => 'vh360-mobile-activity',
            'title'   => __('Activity', 'videohub360-theme'),
            'url'     => vh360_get_activity_page_url(),
            'icon'    => 'activity',
            'classes' => array(),
        ),
        array(
            'id'      => 'vh360-mobile-notifications',
            'title'   => __('Notifications', 'videohub360-theme'),
            'url'     => add_query_arg('tab', 'notifications', $dashboard_url),
            'icon'    => 'notifications',
            'classes' => array('vh360-icon-notifications'), // Behavior flag for badge
        ),
        array(
            'id'      => 'vh360-mobile-members',
            'title'   => __('Members', 'videohub360-theme'),
            'url'     => vh360_get_members_page_url(),
            'icon'    => 'members',
            'classes' => array(),
        ),
        array(
            'id'      => 'vh360-mobile-menu',
            'title'   => __('Menu', 'videohub360-theme'),
            'url'     => '#',
            'icon'    => '',
            'classes' => array('vh360-icon-avatar'), // Behavior flag for drawer
        ),
    );

    /**
     * Filter mobile bottom nav item definitions.
     *
     * Allows plugins to add or modify mobile bottom nav items.
     *
     * @param array $definitions Array of menu item definitions.
     * @since 1.0.0
     */
    return apply_filters('vh360_mobile_bottom_nav_item_definitions', $definitions);
}

/**
 * Render Dashboard Menu Items Meta Box
 *
 * Outputs a checklist of dashboard menu items that can be added to any menu.
 * Uses WordPress's standard menu item format so items save correctly.
 */
function vh360_render_dashboard_menu_items_meta_box() {
    global $_nav_menu_placeholder, $nav_menu_selected_id;

    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

    $items = vh360_get_dashboard_menu_item_definitions();
    ?>
    <div id="vh360-dashboard-menu-items" class="posttypediv">
        <div id="tabs-panel-vh360-dashboard-menu-items" class="tabs-panel tabs-panel-active">
            <p class="description" style="margin: 10px 12px;">
                <?php esc_html_e( 'Dashboard items must include #tab fragments for the tab system to work correctly. Do not modify the URLs after adding items.', 'videohub360-theme' ); ?>
                <br/>
                <em><?php esc_html_e( 'Note: Some items only appear for certain account types (e.g., Business Profile for professionals). Labels may vary by user type (Appointments vs My Appointments).', 'videohub360-theme' ); ?></em>
            </p>

            <ul id="vh360-dashboard-menu-items-checklist" class="categorychecklist form-no-clear">
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    $item_id = $_nav_menu_placeholder;
                    $_nav_menu_placeholder--;
                    ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $item_id ); ?>" />
                            <?php echo esc_html( $item['title'] ); ?>
                        </label>
                        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-type]" value="custom" />
                        <input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-title]" value="<?php echo esc_attr( $item['title'] ); ?>" />
                        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-url]" value="<?php echo esc_url( $item['url'] ); ?>" />
                        <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-classes]" value="" />
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p class="button-controls wp-clearfix">
            <span class="list-controls">
                <a href="<?php echo esc_url( admin_url( 'nav-menus.php?page-tab=all&amp;selectall=1#vh360-dashboard-menu-items' ) ); ?>" class="select-all"><?php esc_html_e( 'Select All', 'videohub360-theme' ); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'videohub360-theme' ); ?>" name="add-vh360-dashboard-menu-item" id="submit-vh360-dashboard-menu-items" />
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/**
 * Render Mobile Bottom Nav Items Meta Box
 *
 * Outputs a checklist of mobile bottom nav items with icons.
 * Icon meta is saved via the shared icon system, and specific CSS classes
 * are used only for special behavior flags.
 */
function vh360_render_mobile_bottom_menu_items_meta_box() {
    global $_nav_menu_placeholder, $nav_menu_selected_id;

    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

    $items = vh360_get_mobile_bottom_nav_item_definitions();
    ?>
    <div id="vh360-mobile-bottom-menu-items" class="posttypediv">
        <div id="tabs-panel-vh360-mobile-bottom-menu-items" class="tabs-panel tabs-panel-active">
            <p class="description" style="margin: 10px 12px;">
                <?php esc_html_e( 'Add mobile bottom nav items. Keep 3–5 items (maximum 5 enforced automatically).', 'videohub360-theme' ); ?>
            </p>

            <ul id="vh360-mobile-bottom-menu-items-checklist" class="categorychecklist form-no-clear">
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    $item_id = $_nav_menu_placeholder;
                    $_nav_menu_placeholder--;
                    $classes_str = ! empty( $item['classes'] ) ? implode( ' ', $item['classes'] ) : '';
                    $icon = ! empty( $item['icon'] ) ? $item['icon'] : '';
                    ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-object-id]" value="<?php echo esc_attr( $item_id ); ?>" />
                            <?php echo esc_html( $item['title'] ); ?>
                        </label>
                        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-type]" value="custom" />
                        <input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-title]" value="<?php echo esc_attr( $item['title'] ); ?>" />
                        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-url]" value="<?php echo esc_url( $item['url'] ); ?>" />
                        <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-classes]" value="<?php echo esc_attr( $classes_str ); ?>" />
                        <?php if ( $icon ) : ?>
                            <input type="hidden" name="menu-item[<?php echo esc_attr( $item_id ); ?>][menu-item-vh360-icon]" value="<?php echo esc_attr( $icon ); ?>" />
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p class="button-controls wp-clearfix">
            <span class="list-controls">
                <a href="<?php echo esc_url( admin_url( 'nav-menus.php?page-tab=all&amp;selectall=1#vh360-mobile-bottom-menu-items' ) ); ?>" class="select-all"><?php esc_html_e( 'Select All', 'videohub360-theme' ); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'videohub360-theme' ); ?>" name="add-vh360-mobile-bottom-menu-item" id="submit-vh360-mobile-bottom-menu-items" />
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}


/**
 * Save menu item icon meta from custom field
 *
 * Hooks into wp_update_nav_menu_item to save the icon meta when menu items
 * are added via the Mobile Bottom Nav meta box.
 *
 * @param int   $menu_id         ID of the updated menu.
 * @param int   $menu_item_db_id ID of the updated menu item.
 * @param array $args            An array of arguments used to update the menu item.
 */
function vh360_save_mobile_nav_menu_item_icon( $menu_id, $menu_item_db_id, $args ) {
    // Verify nonce for security (WordPress menu save nonce)
    // Note: Do not sanitize nonce before verification - pass raw value
    if ( ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['update-nav-menu-nonce'] ), 'update-nav-menu' ) ) {
        return;
    }
    
    // Check if icon data is present in the request
    if ( isset( $_POST['menu-item-vh360-icon'][ $menu_item_db_id ] ) ) {
        $icon_slug = sanitize_key( wp_unslash( $_POST['menu-item-vh360-icon'][ $menu_item_db_id ] ) );
        
        // Validate icon against allowed icons
        if ( ! empty( $icon_slug ) ) {
            $allowed_icons = array_keys( vh360_cm_icon_choices() );
            if ( in_array( $icon_slug, $allowed_icons, true ) ) {
                update_post_meta( $menu_item_db_id, '_vh360_menu_icon', $icon_slug );
            }
        }
    }
}
add_action( 'wp_update_nav_menu_item', 'vh360_save_mobile_nav_menu_item_icon', 10, 3 );

