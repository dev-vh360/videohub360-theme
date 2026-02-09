# Sidebar System Quick Reference

## Template Usage

### In Templates (page.php, single.php, etc.)

```php
// Get full sidebar configuration
$sidebar_config = vh360_resolve_sidebar();
$has_sidebar = $sidebar_config['show_sidebar'];
$sidebar_position = $sidebar_config['position'];

// Use in layout
if ($has_sidebar && 'left' === $sidebar_position) {
    get_sidebar(); // Show sidebar on left
}

// Main content
echo '<main>...</main>';

if ($has_sidebar && 'right' === $sidebar_position) {
    get_sidebar(); // Show sidebar on right
}
```

### Helper Functions

```php
// Quick checks
if (vh360_has_sidebar()) {
    echo 'Has sidebar';
}

// Get specific values
$sidebar_id = vh360_get_sidebar_id();
$position = vh360_get_sidebar_position();

// Get available sidebars
$sidebars = vh360_get_selectable_sidebars();
```

## Adding Custom Sidebars

```php
// 1. Register the sidebar
function my_register_sidebar() {
    register_sidebar(array(
        'name'          => 'My Custom Sidebar',
        'id'            => 'my-custom-sidebar',
        'description'   => 'Description here',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'my_register_sidebar');

// 2. Make it selectable
function my_add_selectable_sidebar($sidebars) {
    $sidebars['my-custom-sidebar'] = 'My Custom Sidebar';
    return $sidebars;
}
add_filter('vh360_selectable_sidebars', 'my_add_selectable_sidebar');
```

## Forcing Sidebar Behavior

```php
// Force specific sidebar on certain pages
function my_force_sidebar($config, $post_id) {
    // Force sidebar on page 42
    if ($post_id === 42) {
        $config['show_sidebar'] = true;
        $config['sidebar_id'] = 'my-custom-sidebar';
        $config['position'] = 'right';
    }
    
    // Hide sidebar on pages with specific template
    if (is_page_template('template-custom.php')) {
        $config['show_sidebar'] = false;
    }
    
    return $config;
}
add_filter('vh360_sidebar_config', 'my_force_sidebar', 10, 2);
```

## Custom Post Type Support

```php
// Add meta box to custom post types
function my_add_cpt_sidebar_support($post_types) {
    $post_types[] = 'portfolio';
    $post_types[] = 'product';
    return $post_types;
}
add_filter('vh360_sidebar_meta_box_post_types', 'my_add_cpt_sidebar_support');
```

## Content Type Detection

```php
// Get content type for customizer defaults
$type = vh360_get_content_type(); // Returns: 'page', 'post', or 'archive'

// In resolver, determines which global default to use:
// - vh360_sidebar_layout_page
// - vh360_sidebar_layout_post
// - vh360_sidebar_layout_archive
```

## Meta Keys

Per-page overrides stored as:
```php
$layout = get_post_meta($post_id, '_vh360_sidebar_layout', true);
// Returns: 'inherit', 'none', 'left', or 'right'

$choice = get_post_meta($post_id, '_vh360_sidebar_choice', true);
// Returns: 'inherit' or sidebar ID
```

## Theme Mods

Global defaults stored as:
```php
$layout = get_theme_mod('vh360_sidebar_layout_page', 'right');
$sidebar = get_theme_mod('vh360_sidebar_default_page', 'sidebar-1');
```

## Body Classes

Automatically added:
- `has-sidebar` - Page has a sidebar
- `no-sidebar` - Page has no sidebar
- `sidebar-left` - Sidebar on left
- `sidebar-right` - Sidebar on right

Use in CSS:
```css
.has-sidebar .content-area {
    /* Styles for content with sidebar */
}

.sidebar-left .content-area {
    /* Content styles when sidebar is left */
}
```

## Resolver Priority

1. **Forced Rules** (highest priority)
   - WooCommerce checkout/cart/account
   - Elementor canvas pages
   - Video archives
   - Special templates (dashboard, activity feed)

2. **Per-Page Overrides**
   - Meta box settings on individual pages/posts

3. **Global Defaults** (lowest priority)
   - Customizer settings per content type

## Common Patterns

### Hide Sidebar on Specific Category

```php
add_filter('vh360_sidebar_config', function($config, $post_id) {
    if (is_category('news')) {
        $config['show_sidebar'] = false;
    }
    return $config;
}, 10, 2);
```

### Different Sidebar for Different Post Types

```php
add_filter('vh360_sidebar_config', function($config, $post_id) {
    if (is_singular('portfolio')) {
        $config['sidebar_id'] = 'portfolio-sidebar';
    }
    return $config;
}, 10, 2);
```

### Debug Sidebar Configuration

```php
$config = vh360_resolve_sidebar();
echo '<pre>';
print_r($config);
echo '</pre>';
```

## Testing

To test the sidebar system:

1. **Global Settings**: Go to Customize → Global Design → Layout / Sidebar
2. **Per-Page**: Edit a page → Check Sidebar Settings meta box
3. **Forced Rules**: Try checkout page (should have no sidebar)
4. **Body Classes**: Check page source for body classes
5. **Filters**: Add filter and verify it affects output
