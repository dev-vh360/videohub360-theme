# Per-Page Sidebar Control System Documentation

## Overview

The VideoHub360 theme now includes a comprehensive sidebar control system that gives site builders complete control over sidebar display, positioning, and selection on a per-page basis while maintaining sensible defaults.

## Features

### ✅ Global Defaults
- Set default sidebar behavior for Pages, Posts, and Archives
- Choose sidebar position (None, Left, or Right)
- Select which sidebar to display
- Configured via **Appearance → Customize → Global Design → Layout / Sidebar**

### ✅ Per-Page Overrides
- Override global settings on individual pages and posts
- Control sidebar layout and sidebar selection independently
- Managed via meta box in the WordPress editor (right sidebar)

### ✅ Automatic Compatibility
- WooCommerce checkout/cart pages: No sidebar (forced)
- Elementor canvas pages: No sidebar (forced)
- Video archives: No sidebar (forced)
- Activity feed template: Uses special activity sidebar
- Dashboard template: No sidebar (forced)

### ✅ Developer Friendly
- Extensible with filters and hooks
- Clean, documented code
- Support for custom post types

---

## User Guide

### Setting Global Defaults

1. Go to **Appearance → Customize**
2. Navigate to **Global Design → Layout / Sidebar**
3. Configure settings for each content type:
   - **Pages**: Default sidebar settings for all pages
   - **Posts**: Default sidebar settings for blog posts
   - **Archives**: Default sidebar settings for category/tag archives

#### Available Options:
- **Sidebar Layout**: None, Left Sidebar, or Right Sidebar
- **Default Sidebar**: Choose which sidebar widget area to display

### Overriding Settings Per Page

1. Edit any Page or Post
2. Look for the **Sidebar Settings** meta box (right sidebar)
3. Choose your overrides:
   - **Sidebar Layout**: Inherit Global, No Sidebar, Left Sidebar, or Right Sidebar
   - **Sidebar Selection**: Inherit Global, or choose a specific sidebar

The "Inherit Global" options show you what the current global setting is.

### Managing Sidebar Widgets

1. Go to **Appearance → Widgets**
2. Add widgets to your desired sidebar areas:
   - **Sidebar**: Main primary sidebar
   - **Activity Feed Sidebar**: Special sidebar for activity feed pages
   - **Footer 1-3**: Footer widget areas

---

## Technical Documentation

### Architecture

The sidebar system consists of four main components:

1. **Sidebar Resolver** (`includes/sidebar-resolver.php`)
   - Central logic for determining sidebar configuration
   - Checks per-page overrides, global defaults, and forced rules
   - Returns configuration array with `show_sidebar`, `sidebar_id`, and `position`

2. **Customizer Controls** (`includes/customizer/sidebar-controls.php`)
   - Registers global sidebar settings in Customizer
   - Provides sanitization callbacks
   - Creates dropdown controls for layout and sidebar selection

3. **Meta Box Controls** (`includes/sidebar-meta-box.php`)
   - Adds meta box to page/post editor
   - Handles saving of per-page overrides
   - Displays current global defaults for reference

4. **Template Integration**
   - Updated templates: `page.php`, `single.php`, `archive.php`, `index.php`, `sidebar.php`
   - Uses resolver to determine layout
   - Supports left/right sidebar positioning

### Key Functions

#### `vh360_resolve_sidebar($post_id = null)`
Main resolver function that returns sidebar configuration.

**Returns:**
```php
array(
    'show_sidebar' => bool,    // Whether to display a sidebar
    'sidebar_id'   => string,  // Which sidebar to display
    'position'     => string   // 'left' or 'right'
)
```

**Priority Order:**
1. Forced rules (WooCommerce, Elementor, special templates)
2. Per-page meta overrides
3. Global Customizer defaults

#### `vh360_has_sidebar($post_id = null)`
Quick check if the current page should have a sidebar.

#### `vh360_get_sidebar_id($post_id = null)`
Get the sidebar ID to display.

#### `vh360_get_sidebar_position($post_id = null)`
Get sidebar position ('left' or 'right').

#### `vh360_get_selectable_sidebars()`
Get array of registered sidebars available for selection.

### Filters

#### `vh360_selectable_sidebars`
Modify the list of sidebars available for selection.

```php
add_filter('vh360_selectable_sidebars', function($sidebars) {
    $sidebars['my-custom-sidebar'] = 'My Custom Sidebar';
    return $sidebars;
});
```

#### `vh360_sidebar_config`
Modify the final sidebar configuration before rendering.

```php
add_filter('vh360_sidebar_config', function($config, $post_id) {
    // Force no sidebar on specific posts
    if ($post_id === 123) {
        $config['show_sidebar'] = false;
    }
    return $config;
}, 10, 2);
```

#### `vh360_sidebar_meta_box_post_types`
Add meta box support to custom post types.

```php
add_filter('vh360_sidebar_meta_box_post_types', function($post_types) {
    $post_types[] = 'my_custom_post_type';
    return $post_types;
});
```

### Meta Keys

Per-page settings are stored as post meta:
- `_vh360_sidebar_layout`: Layout choice ('inherit', 'none', 'left', 'right')
- `_vh360_sidebar_choice`: Sidebar selection ('inherit' or sidebar ID)

### Theme Mods

Global defaults are stored as theme mods:
- `vh360_sidebar_layout_page`: Page sidebar layout
- `vh360_sidebar_default_page`: Page default sidebar
- `vh360_sidebar_layout_post`: Post sidebar layout
- `vh360_sidebar_default_post`: Post default sidebar
- `vh360_sidebar_layout_archive`: Archive sidebar layout
- `vh360_sidebar_default_archive`: Archive default sidebar

### Body Classes

The resolver automatically adds body classes:
- `has-sidebar`: When sidebar is displayed
- `no-sidebar`: When no sidebar
- `sidebar-left`: When sidebar is on the left
- `sidebar-right`: When sidebar is on the right

### CSS

Sidebar layouts are styled in `assets/css/sidebar-layout.css` with:
- Grid-based responsive layout
- Sticky sidebar positioning
- Mobile-first approach (sidebar stacks below content on tablets)
- Special handling for WooCommerce and Elementor pages

---

## Examples

### Example 1: Custom Post Type Support

Add sidebar controls to a custom post type:

```php
add_filter('vh360_sidebar_meta_box_post_types', function($post_types) {
    $post_types[] = 'portfolio';
    return $post_types;
});
```

### Example 2: Register Custom Sidebar

```php
function my_custom_sidebar() {
    register_sidebar(array(
        'name'          => 'Shop Sidebar',
        'id'            => 'shop-sidebar',
        'description'   => 'Sidebar for shop pages',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'my_custom_sidebar');

// Make it selectable
add_filter('vh360_selectable_sidebars', function($sidebars) {
    $sidebars['shop-sidebar'] = 'Shop Sidebar';
    return $sidebars;
});
```

### Example 3: Force Sidebar on Specific Pages

```php
add_filter('vh360_sidebar_config', function($config, $post_id) {
    // Always show right sidebar on page ID 42
    if ($post_id === 42) {
        $config['show_sidebar'] = true;
        $config['position'] = 'right';
        $config['sidebar_id'] = 'sidebar-1';
    }
    return $config;
}, 10, 2);
```

### Example 4: Programmatic Control

```php
// Check if current page has sidebar
if (vh360_has_sidebar()) {
    echo 'This page has a sidebar!';
}

// Get sidebar position
$position = vh360_get_sidebar_position();
echo 'Sidebar is on the ' . $position;

// Get full configuration
$config = vh360_resolve_sidebar();
var_dump($config);
```

---

## Troubleshooting

### Sidebar Not Showing

1. **Check if widgets are added**: Go to Appearance → Widgets and add widgets to your sidebar
2. **Check page settings**: Edit the page and verify the Sidebar Settings meta box
3. **Check global defaults**: Go to Appearance → Customize → Global Design → Layout / Sidebar
4. **Check forced rules**: Some pages (checkout, dashboard, etc.) can't show sidebars

### Wrong Sidebar Displaying

1. **Check per-page override**: The page may have a specific sidebar selected
2. **Check global default**: The content type may be using a different default sidebar
3. **Clear cache**: If using a caching plugin, clear the cache

### Sidebar on Wrong Side

1. **Check per-page position**: Edit the page and check Sidebar Settings meta box
2. **Check global position**: Go to Customizer and verify the Layout setting
3. **Check mobile**: On tablets/mobile, sidebar always appears below content

### Customizer Settings Not Applying

1. **Click "Publish"**: Make sure you save your Customizer changes
2. **Check page override**: The page may have an override set to "Inherit Global"
3. **Refresh page**: Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)

---

## Compatibility

### WooCommerce
- ✅ Checkout: No sidebar (forced)
- ✅ Cart: No sidebar (forced)
- ✅ My Account: No sidebar (forced)
- ✅ Shop pages: Respects sidebar settings

### Elementor
- ✅ Canvas template: No sidebar (forced)
- ✅ Full width: No sidebar (detected automatically)
- ✅ Regular pages: Respects sidebar settings

### Other Plugins
- ✅ Works with any widget plugin
- ✅ Compatible with caching plugins
- ✅ Compatible with translation plugins

---

## Support & Feedback

For issues, questions, or feature requests regarding the sidebar system, please contact the theme developer or create an issue in the theme repository.

---

## Changelog

### Version 1.2.0
- ✅ Initial implementation of per-page sidebar control system
- ✅ Global Customizer defaults for Pages, Posts, and Archives
- ✅ Per-page meta box controls
- ✅ Centralized sidebar resolver
- ✅ Left/right sidebar positioning
- ✅ WooCommerce and Elementor compatibility
- ✅ Developer filters and hooks
- ✅ Responsive sidebar layouts
