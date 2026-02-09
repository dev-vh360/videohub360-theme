# Modular Header System Documentation

## Overview

The Videohub360 Theme now features a fully customizable modular header system that provides complete control over navigation style and header icons through the WordPress Customizer. This system is designed to be flexible, accessible, and future-proof.

## Features

### ✅ Navigation Styles
- **Horizontal Navigation**: Traditional menu bar (desktop default)
- **Hamburger Menu**: Modern slide-out panel from left side
- **Mobile Responsive**: Automatically switches to hamburger on mobile devices

### ✅ Customizable Header Icons
All header icons are optional and can be enabled/disabled via Customizer:
- **Search Icon**: Placeholder for future advanced search feature
- **Cart Icon**: WooCommerce shopping cart with item count badge
- **Messages Icon**: Direct messages (logged-in users only)
- **Notifications Icon**: Notification bell (logged-in users only)
- **User Menu**: Avatar dropdown or sign-in button

### ✅ Icon Ordering
Icons can be reordered using a comma-separated list in the Customizer.

### ✅ Sticky Header
Optional sticky header that remains fixed at the top when scrolling.

## File Structure

```
template-parts/header/
├── header-layout.php          # Main header structure
├── logo.php                   # Logo component
├── navigation-horizontal.php  # Traditional horizontal nav
├── navigation-hamburger.php   # Hamburger slide-out menu
├── search-icon.php           # Search icon with modal
└── cart-icon.php             # WooCommerce cart icon

includes/customizer/
└── header-controls.php        # Customizer settings

assets/css/
└── header-layout.css         # Header styling

assets/js/
└── header-navigation.js      # Header interactivity
```

## Customizer Settings

Access via **Appearance > Customize > Header Settings**

### Navigation Settings
- **Navigation Style**: Choose between horizontal or hamburger menu
- **Sticky Header**: Enable/disable sticky positioning

### Header Icons
- **Show Search Icon**: Display search button (default: off)
- **Show Cart Icon**: Display WooCommerce cart (default: off)
- **Show Messages Icon**: Display messages icon for logged-in users (default: on)
- **Show Notifications Icon**: Display notifications bell for logged-in users (default: on)
- **Show User Menu**: Display user avatar/sign-in button (default: on)

### Advanced Settings
- **Icon Order**: Customize the order of header icons
  - Default: `search,cart,messages,notifications,user`
  - Valid values: search, cart, messages, notifications, user

## Header Layout Structure

The header is divided into two main zones:

### Left Zone
- Hamburger button (when hamburger mode is active or on mobile)
- Logo
- Horizontal navigation (when horizontal mode is active on desktop)

### Right Zone
- Customizable icons in user-defined order
- Icons appear only if enabled in Customizer
- Messages and notifications only appear for logged-in users
- Cart icon only appears if WooCommerce is active

## Hamburger Menu Features

### Design
- **Panel Width**: 320px (max 85% on tablet, 90% on mobile)
- **Position**: Slides in from left side
- **Backdrop**: Semi-transparent with blur effect
- **Animation**: Smooth cubic-bezier transition
- **Icon Animation**: 3 lines transform to X when open

### Interactions
- Click hamburger button to open
- Click backdrop to close
- Click X button to close
- Press ESC key to close
- Body scroll locked when open
- Keyboard navigation supported

## Search Modal Features

### Design
- **Modal Style**: Centered overlay
- **Backdrop**: Semi-transparent with blur
- **Form**: Single input with search button

### Interactions
- Click search icon to open
- Click backdrop to close
- Click X button to close
- Press ESC key to close
- Auto-focus on input field

### Future Integration
This is a placeholder component ready for future advanced search features:
- Real-time search results
- Search filters
- Search history
- Trending searches

## Cart Icon Features

### WooCommerce Integration
- Only displays if WooCommerce is active
- Shows current cart item count
- Badge displays count (max 99+)
- Links directly to cart page
- Badge color: Red (#ef4444)

## Responsive Behavior

### Desktop (>768px)
- Shows navigation style as selected in Customizer
- All enabled icons visible
- Full "Menu" label on hamburger button

### Tablet (≤768px)
- Forces hamburger menu regardless of setting
- All enabled icons visible
- Hamburger panel: max-width 85%

### Mobile (≤768px)
- Forces hamburger menu
- Hides "Menu" label text
- Reduces icon gaps
- Smaller icon sizes (36px)
- Hamburger panel: max-width 90%

## Accessibility Features

### ARIA Attributes
- `aria-label`: Descriptive labels on all buttons
- `aria-expanded`: State of toggleable menus
- `aria-hidden`: Visibility of modals/menus
- `aria-controls`: Links buttons to controlled elements
- `role="navigation"`: Proper navigation landmarks

### Keyboard Navigation
- ESC key closes all modals/menus
- Tab navigation through all interactive elements
- Focus management (returns to trigger button on close)
- Keyboard-accessible menu items

### Screen Reader Support
- Screen reader text where needed
- Proper heading hierarchy
- Alternative text for icons
- Descriptive link text

## Performance Optimizations

### Conditional Loading
- Cart icon only loads if WooCommerce is active
- Messages icon only for logged-in users
- Notifications icon only for logged-in users
- Icons only load if enabled in Customizer

### CSS Performance
- GPU-accelerated transforms
- Efficient transitions
- Minimal repaints/reflows
- BEM naming convention

### JavaScript Performance
- Event delegation where possible
- No jQuery dependency for header nav
- Optimized DOM queries
- Passive event listeners

## Browser Support

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Basic support (no blur effect)

## Extending the Header System

### Adding New Icons

1. Create new template part in `template-parts/header/`:
```php
<?php
// template-parts/header/wishlist-icon.php
if (!defined('ABSPATH')) exit;
?>
<a href="<?php echo esc_url($wishlist_url); ?>" class="header-icon header-wishlist-link">
    <!-- SVG icon here -->
</a>
```

2. Add customizer control in `includes/customizer/header-controls.php`:
```php
$wp_customize->add_setting('vh360_show_wishlist_icon', array(
    'default' => false,
    'sanitize_callback' => 'wp_validate_boolean',
));
$wp_customize->add_control('vh360_show_wishlist_icon', array(
    'label' => __('Show Wishlist Icon', 'videohub360-theme'),
    'section' => 'vh360_main_header_settings',
    'type' => 'checkbox',
));
```

3. Add to icon order in `header-layout.php`:
```php
case 'wishlist':
    if ($show_wishlist) {
        get_template_part('template-parts/header/wishlist-icon');
    }
    break;
```

4. Update sanitization in `header-controls.php`:
```php
$valid_icons = array('search', 'cart', 'messages', 'notifications', 'user', 'wishlist');
```

### Custom Hamburger Positions

Currently, hamburger always appears on the left. To add right-side option:

1. Add customizer control for position
2. Add class modifier: `site-header--hamburger-right`
3. Update CSS with right-side transforms
4. Update JavaScript (no changes needed)

## Troubleshooting

### Hamburger menu not appearing on mobile
- Check browser console for JavaScript errors
- Verify `header-navigation.js` is enqueued
- Clear browser cache
- Check CSS is loaded: `header-layout.css`

### Icons not showing
- Verify icon is enabled in Customizer
- Check user is logged in (for messages/notifications)
- Verify WooCommerce is active (for cart)
- Check icon order setting

### Sticky header not working
- Verify "Sticky Header" is enabled in Customizer
- Check for CSS conflicts with other plugins
- Verify z-index is sufficient (current: 1000)

### Customizer settings not applying
- Save and refresh the Customizer preview
- Check browser console for errors
- Verify `header-controls.php` is loaded
- Clear site and browser cache

## Migration from Old Header

The new header system replaces the old header markup in `header.php` (lines 36-84). The old system used:
- Basic menu toggle button
- Simple navigation dropdown
- Fixed header structure

The new system provides:
- Modular, maintainable structure
- Full Customizer control
- Better mobile experience
- Enhanced accessibility
- WooCommerce integration
- Future-proof architecture

## Developer Notes

### Class Naming Convention
- BEM methodology: `.block__element--modifier`
- Header classes: `.site-header`, `.site-header__inner`, `.site-header--sticky`
- Navigation: `.main-navigation--horizontal`, `.main-navigation--hamburger`
- Icons: `.header-icon`, `.header-cart-link`, `.header-search-toggle`

### Hooks and Filters
Currently no custom hooks, but these could be added for further extensibility:
- `vh360_before_header_left_zone`
- `vh360_after_header_left_zone`
- `vh360_before_header_right_zone`
- `vh360_after_header_right_zone`
- `vh360_header_icons_order` filter

### Theme Mod Keys
- `vh360_nav_style`: Navigation style (horizontal/hamburger)
- `vh360_sticky_header`: Sticky header enabled (boolean)
- `vh360_show_search_icon`: Search icon visibility (boolean)
- `vh360_show_cart_icon`: Cart icon visibility (boolean)
- `vh360_show_messages_icon`: Messages icon visibility (boolean)
- `vh360_show_notifications_icon`: Notifications icon visibility (boolean)
- `vh360_show_user_menu`: User menu visibility (boolean)
- `vh360_icon_order`: Icon display order (comma-separated string)

## Support

For issues or questions:
1. Check this documentation
2. Review browser console for errors
3. Verify WordPress and theme are up to date
4. Check for plugin conflicts
5. Contact theme support

## Changelog

### Version 1.0.0
- Initial release of modular header system
- Horizontal and hamburger navigation options
- Customizable header icons
- Icon ordering system
- Sticky header option
- Full accessibility support
- Mobile responsive design
- WooCommerce integration
- Search modal placeholder
