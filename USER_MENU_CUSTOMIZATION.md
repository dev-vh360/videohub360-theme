# User Menu Customization Guide

The User Menu dropdown can be customized through WordPress Appearance > Menus.

## Setup Instructions

### 1. Create a Custom Menu

1. Go to **Appearance > Menus** in WordPress admin
2. Click "Create a new menu"
3. Give it a name (e.g., "User Dropdown Menu")
4. Click "Create Menu"

### 2. Assign to User Menu Location

1. Under "Menu Settings", check **"User Menu (Dropdown)"**
2. Click "Save Menu"

### 3. Add Menu Items

Add pages, custom links, or posts to your menu as needed. You can:
- Add any page from your site
- Add custom links to specific URLs
- Reorder items by dragging
- Delete items you don't need
- Nest items (though nesting is not recommended for the user menu)

## Adding Icons to Menu Items

Icons make the user menu more visually appealing. You can add icons in two ways:

### Method 1: Using CSS Classes

1. Click the "Screen Options" tab at the top right
2. Enable "CSS Classes" checkbox
3. Expand any menu item
4. In the "CSS Classes" field, add one of these icon classes:
   - `icon-dashboard` - Dashboard icon
   - `icon-profile` - Profile/user icon
   - `icon-edit` - Edit/pencil icon
   - `icon-videos` - Video camera icon
   - `icon-activity` - Activity/pulse icon
   - `icon-settings` - Settings/gear icon
   - `icon-signout` - Sign out/logout icon

**Example:** Add `icon-dashboard` in the CSS Classes field

### Method 2: Using Description Field

1. Click "Screen Options" and enable "Description"
2. Expand any menu item
3. In the "Description" field, type the icon name:
   - `dashboard`
   - `profile`
   - `edit`
   - `videos`
   - `activity`
   - `settings`
   - `signout`

**Note:** The description text won't be displayed; it's only used to determine the icon.

## Adding Dividers

To add a visual divider before a menu item (like the divider before "Sign Out"):

1. Enable "CSS Classes" in Screen Options
2. Expand the menu item where you want the divider
3. Add `divider-before` or `menu-divider` to the CSS Classes field

**Example:** For a "Sign Out" link, add `divider-before icon-signout` in CSS Classes

## Example Menu Setup

Here's a recommended menu structure:

1. **Dashboard**
   - URL: `/dashboard/`
   - CSS Classes: `icon-dashboard`

2. **My Profile**
   - URL: (Use your profile page)
   - CSS Classes: `icon-profile`

3. **Edit Profile**
   - URL: `/profile-edit/`
   - CSS Classes: `icon-edit`

4. **My Videos**
   - URL: `/dashboard/?tab=videos`
   - CSS Classes: `icon-videos`

5. **Activity**
   - URL: `/dashboard/?tab=activity`
   - CSS Classes: `icon-activity`

6. **Settings**
   - URL: `/dashboard/?tab=settings`
   - CSS Classes: `icon-settings`

7. **Sign Out**
   - URL: Use custom logout handler via `vh360_get_logout_url()` or WordPress logout URL (both redirect to custom pages)
   - CSS Classes: `divider-before icon-signout`

## Default Behavior

If no menu is assigned to the "User Menu (Dropdown)" location, the theme will automatically display the default menu items with all standard links and icons.

## Tips

- **Keep it Simple:** 5-8 items work best for a dropdown menu
- **Order Matters:** Items appear in the order you arrange them
- **Icons Help:** Icons make items easier to identify at a glance
- **Test It:** After saving, log in and test your menu
- **Mobile Friendly:** Keep menu item labels short for mobile displays

## Advanced Customization

For developers who want to customize beyond the menu builder:

### Using the Filter Hook

```php
add_filter('vh360_user_menu_items', function($menu_items, $user_id) {
    // Modify menu items array
    // Add, remove, or change items based on user role, capabilities, etc.
    
    return $menu_items;
}, 10, 2);
```

This filter only applies when using the default menu items (no custom menu assigned).

## Troubleshooting

**Menu not showing:**
- Make sure you've assigned the menu to "User Menu (Dropdown)" location
- Ensure you're logged in (the menu only shows for logged-in users)
- Check that menu items are published

**Icons not showing:**
- Verify the CSS class or description field has a valid icon name
- Check for typos in icon names
- Make sure "CSS Classes" or "Description" is enabled in Screen Options

**Menu looks wrong:**
- Try clearing your browser cache
- Check if you have any custom CSS that might conflict
- Verify menu items don't have unusual formatting

## Need Help?

If you need assistance with customizing your user menu, please contact theme support.
