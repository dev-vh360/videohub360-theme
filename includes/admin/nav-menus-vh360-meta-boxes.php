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

if (!defined('ABSPATH')) {
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
 * Each item includes a #tab fragment that the VH360_Dashboard_Menu_Walker uses.
 *
 * @return array Array of menu item definitions.
 * @since 1.0.0
 */
function vh360_get_dashboard_menu_item_definitions() {
    $dashboard_url = vh360_get_dashboard_page_url();
    
    $definitions = array(
        array(
            'id'    => 'vh360-dashboard-overview',
            'title' => __('Overview', 'videohub360-theme'),
            'url'   => $dashboard_url . '#overview',
        ),
        array(
            'id'    => 'vh360-dashboard-create-video',
            'title' => __('+ Create', 'videohub360-theme'),
            'url'   => $dashboard_url . '#create-video',
        ),
        array(
            'id'    => 'vh360-dashboard-videos',
            'title' => __('My Videos', 'videohub360-theme'),
            'url'   => $dashboard_url . '#videos',
        ),
        array(
            'id'    => 'vh360-dashboard-live-rooms',
            'title' => __('Live Rooms', 'videohub360-theme'),
            'url'   => $dashboard_url . '#live-rooms',
        ),
        array(
            'id'    => 'vh360-dashboard-messages',
            'title' => __('Messages', 'videohub360-theme'),
            'url'   => $dashboard_url . '#messages',
        ),
        array(
            'id'    => 'vh360-dashboard-notifications',
            'title' => __('Notifications', 'videohub360-theme'),
            'url'   => $dashboard_url . '#notifications',
        ),
        array(
            'id'    => 'vh360-dashboard-push-notifications',
            'title' => __('Push Notifications', 'videohub360-theme'),
            'url'   => $dashboard_url . '#push-notifications',
        ),
        array(
            'id'    => 'vh360-dashboard-create-post',
            'title' => __('Create Post', 'videohub360-theme'),
            'url'   => $dashboard_url . '#create-post',
        ),
        array(
            'id'    => 'vh360-dashboard-galleries',
            'title' => __('Galleries', 'videohub360-theme'),
            'url'   => $dashboard_url . '#galleries',
        ),
        array(
            'id'    => 'vh360-dashboard-events',
            'title' => __('Events', 'videohub360-theme'),
            'url'   => $dashboard_url . '#events',
        ),
        array(
            'id'    => 'vh360-dashboard-bulletins',
            'title' => __('Bulletins', 'videohub360-theme'),
            'url'   => $dashboard_url . '#bulletins',
        ),
        array(
            'id'    => 'vh360-dashboard-profile',
            'title' => __('Profile', 'videohub360-theme'),
            'url'   => $dashboard_url . '#profile',
        ),
        array(
            'id'    => 'vh360-dashboard-settings',
            'title' => __('Settings', 'videohub360-theme'),
            'url'   => $dashboard_url . '#settings',
        ),
    );
    
    /**
     * Filter dashboard menu item definitions.
     *
     * Allows plugins to add or modify dashboard menu items.
     *
     * @param array $definitions Array of menu item definitions.
     * @since 1.0.0
     */
    return apply_filters('vh360_dashboard_menu_item_definitions', $definitions);
}

/**
 * Get Mobile Bottom Nav Item Definitions
 *
 * Returns an array of mobile bottom navigation items with correct URLs and CSS classes.
 * The CSS classes control which icon is displayed in the mobile bottom nav.
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
            'classes' => array('vh360-icon-activity'),
        ),
        array(
            'id'      => 'vh360-mobile-notifications',
            'title'   => __('Notifications', 'videohub360-theme'),
            'url'     => add_query_arg('tab', 'notifications', $dashboard_url),
            'classes' => array('vh360-icon-notifications'),
        ),
        array(
            'id'      => 'vh360-mobile-members',
            'title'   => __('Members', 'videohub360-theme'),
            'url'     => vh360_get_members_page_url(),
            'classes' => array('vh360-icon-members'),
        ),
        array(
            'id'      => 'vh360-mobile-menu',
            'title'   => __('Menu', 'videohub360-theme'),
            'url'     => '#',
            'classes' => array('vh360-icon-avatar'),
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
                <?php esc_html_e('Dashboard items must include #tab fragments for the tab system to work correctly.', 'videohub360-theme'); ?>
            </p>
            
            <ul id="vh360-dashboard-menu-items-checklist" class="categorychecklist form-no-clear">
                <?php foreach ($items as $item) : ?>
                    <?php
                    $item_id = $_nav_menu_placeholder;
                    $_nav_menu_placeholder--;
                    ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-object-id]" value="<?php echo esc_attr($item_id); ?>" />
                            <?php echo esc_html($item['title']); ?>
                        </label>
                        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-type]" value="custom" />
                        <input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-title]" value="<?php echo esc_attr($item['title']); ?>" />
                        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-url]" value="<?php echo esc_url($item['url']); ?>" />
                        <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-classes]" value="" />
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p class="button-controls wp-clearfix">
            <span class="list-controls">
                <a href="<?php echo esc_url(admin_url('nav-menus.php?page-tab=all&amp;selectall=1#vh360-dashboard-menu-items')); ?>" class="select-all"><?php esc_html_e('Select All', 'videohub360-theme'); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'videohub360-theme'); ?>" name="add-vh360-dashboard-menu-item" id="submit-vh360-dashboard-menu-items" />
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
 * Includes CSS classes that control which icon displays in the mobile nav.
 */
function vh360_render_mobile_bottom_menu_items_meta_box() {
    global $_nav_menu_placeholder, $nav_menu_selected_id;
    
    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
    
    $items = vh360_get_mobile_bottom_nav_item_definitions();
    ?>
    <div id="vh360-mobile-bottom-menu-items" class="posttypediv">
        <div id="tabs-panel-vh360-mobile-bottom-menu-items" class="tabs-panel tabs-panel-active">
            <p class="description" style="margin: 10px 12px;">
                <?php esc_html_e('Mobile Bottom Nav uses CSS classes for icons; these items set them automatically. Keep 3–5 items.', 'videohub360-theme'); ?>
            </p>
            
            <ul id="vh360-mobile-bottom-menu-items-checklist" class="categorychecklist form-no-clear">
                <?php foreach ($items as $item) : ?>
                    <?php
                    $item_id = $_nav_menu_placeholder;
                    $_nav_menu_placeholder--;
                    $classes_str = ! empty( $item['classes'] ) ? implode( ' ', $item['classes'] ) : '';
                    ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-object-id]" value="<?php echo esc_attr($item_id); ?>" />
                            <?php echo esc_html($item['title']); ?>
                        </label>
                        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-type]" value="custom" />
                        <input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-title]" value="<?php echo esc_attr($item['title']); ?>" />
                        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-url]" value="<?php echo esc_url($item['url']); ?>" />
                        <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr($item_id); ?>][menu-item-classes]" value="<?php echo esc_attr($classes_str); ?>" />
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <p class="button-controls wp-clearfix">
            <span class="list-controls">
                <a href="<?php echo esc_url(admin_url('nav-menus.php?page-tab=all&amp;selectall=1#vh360-mobile-bottom-menu-items')); ?>" class="select-all"><?php esc_html_e('Select All', 'videohub360-theme'); ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'videohub360-theme'); ?>" name="add-vh360-mobile-bottom-menu-item" id="submit-vh360-mobile-bottom-menu-items" />
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}
