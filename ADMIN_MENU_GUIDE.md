# VH360 Theme Admin Menu System Guide

## Overview

The VH360 Theme includes a comprehensive admin menu system that provides a centralized interface for managing all theme-specific features through the WordPress backend. This guide explains how to use and customize the admin menu.

## Accessing the Admin Menu

After installing and activating the theme, you'll find a new menu item called **"VH360 Theme"** in your WordPress admin sidebar (below Settings). This menu is only visible to users with the `manage_options` capability (typically Administrators).

## Menu Structure

The admin menu consists of the following pages:

### 1. Dashboard (Main Page)

**Location:** VH360 Theme → Dashboard

The dashboard provides an overview of your theme installation:

- **Statistics Cards:** Quick view of total members, bulletins, activities, and active features
- **System Status:** Checks for Videohub360 plugin, required pages, and permalink structure
- **Quick Actions:** Buttons to access common admin tasks
- **Recent Activity Feed:** Last 10 activities from your site
- **Documentation Links:** Quick access to support resources

### 2. Appearance Settings

**Location:** VH360 Theme → Appearance

Configure visual and functional aspects of the theme:

- **WordPress Customizer Link:** Quick access to customize colors, fonts, and logo
- **Theme Features Toggle:** Enable/disable major features:
  - Profile System
  - Bulletin System
  - Activity Tracking
  - Members Directory
  - User Menu System
- **Performance Options:**
  - Asset Minification
  - Lazy Loading Images
- **Custom CSS:** Add custom CSS without editing theme files

**Settings Saved To:** `vh360_appearance_options` option

### 3. Profile Settings

**Location:** VH360 Theme → Profile Settings

Manage user profile system configuration:

- **Profile System Toggle:** Enable/disable the entire profile feature
- **Display Settings:** Control what appears on profiles
  - Profile Avatar
  - Cover Image
  - Social Links
  - User Statistics
- **Social Media Platforms:** Select which platforms users can add
  - Twitter (X)
  - Facebook
  - YouTube
  - Instagram
  - LinkedIn
  - TikTok
  - Twitch
- **Avatar Settings:** Configure maximum file size (1-10 MB)
- **Cover Image Settings:** Configure maximum file size (1-20 MB)

**Settings Saved To:** `vh360_profile_options` option

### 4. Activity Feed Settings

**Location:** VH360 Theme → Activity Feed

Configure activity tracking and display:

- **Activity Tracking Toggle:** Enable/disable activity tracking
- **Activity Types to Track:**
  - Video Uploads
  - New Member Registrations
  - Profile Updates
  - Milestones
- **Retention Period:** Set how many days to keep activities (7-365 days)
- **Activities Per Page:** Number of activities to display per page (5-100)
- **Activity Statistics:** View counts by activity type
- **Clear Old Activities:** Button to remove activities older than retention period

**Settings Saved To:** `vh360_activity_options` option

### 5. Members Directory Settings

**Location:** VH360 Theme → Members

Configure the members directory feature:

- **Members Directory Toggle:** Enable/disable the directory
- **Members Per Page:** Set pagination (6-100 members)
- **Default Sorting:** Choose default sort order
  - Newest First
  - Oldest First
  - Most Active
  - Alphabetical
- **Search Functionality:** Enable/disable search box
- **Visible Roles:** Select which user roles appear in the directory

**Settings Saved To:** `vh360_members_options` option

### 6. Page Templates Guide

**Location:** VH360 Theme → Page Templates

Complete guide to available page templates:

- **Template List:** All 7 available templates with descriptions:
  - Dashboard Template
  - Profile Edit Template
  - Login Template
  - Register Template
  - Members Directory Template
  - Activity Feed Template
  - Bulletins Template
- **Setup Instructions:** Step-by-step guide for each template
- **Status Indicators:** See which templates are already created
- **Quick Actions:** Create or edit pages directly from the guide
- **Shortcode Documentation:** Available shortcodes for each feature
- **Usage Examples:** Basic and full-featured setup guides

### 7. Advanced Settings

**Location:** VH360 Theme → Advanced

Advanced configuration and maintenance options:

- **Debug Settings:**
  - Debug Mode Toggle
  - Error Logging
  - Show Deprecated Notices
- **Cache Settings:**
  - Transient Expiration Time (300-86400 seconds)
  - Clear All Theme Cache button
- **Import/Export Settings:**
  - Export all settings as JSON
  - Import settings from JSON file
- **Danger Zone:**
  - Reset All Settings button (requires confirmation)
- **Database Information:** View theme option sizes

**Settings Saved To:** `vh360_advanced_options` option

## Features

### Security

All admin pages implement comprehensive security measures:

- **Nonce Verification:** All forms use WordPress nonces for CSRF protection
- **Capability Checks:** All pages check for `manage_options` capability
- **Input Sanitization:** All user inputs are sanitized appropriately
- **Output Escaping:** All outputs are escaped to prevent XSS
- **Prepared Statements:** SQL queries use `$wpdb->prepare()`

### Admin Notices

The system displays helpful admin notices:

- **Plugin Dependency Warning:** Shows if Videohub360 plugin is not active
- **Required Pages Missing:** Lists any missing page templates with setup link
- **Permalink Structure Warning:** Alerts if permalinks aren't configured
- **Success/Error Messages:** Feedback for all admin actions

### AJAX Functionality

Two AJAX endpoints are available:

1. **Clear Cache:** `vh360_clear_cache`
2. **Import Settings:** `vh360_import_settings`

Both require proper nonces and capability checks.

### Custom CSS Output

Custom CSS from Appearance Settings is automatically output to `wp_head` with the ID `vh360-custom-css`.

## File Structure

```
includes/
├── admin/
│   ├── class-vh360-theme-admin.php    # Main admin class
│   ├── pages/
│   │   ├── dashboard.php              # Dashboard page
│   │   ├── appearance.php             # Appearance settings
│   │   ├── profiles.php               # Profile settings
│   │   ├── activity.php               # Activity settings
│   │   ├── members.php                # Members settings
│   │   ├── templates.php              # Templates guide
│   │   └── advanced.php               # Advanced settings
│   └── partials/
│       ├── header.php                 # Admin page header
│       ├── footer.php                 # Admin page footer
│       └── stats-card.php             # Reusable stats card
├── admin-notices.php                  # Admin notice system
└── ...

assets/
└── admin/
    ├── css/
    │   └── theme-admin.css            # Admin styling
    └── js/
        └── theme-admin.js             # Admin JavaScript
```

## Extending the Admin Menu

### Adding a New Submenu Page

To add a new submenu page, edit `includes/admin/class-vh360-theme-admin.php`:

```php
// In add_admin_menu() method
add_submenu_page(
    'vh360-theme',
    __('New Page Title', 'videohub360-theme'),
    __('Menu Label', 'videohub360-theme'),
    'manage_options',
    'vh360-theme-newpage',
    array($this, 'render_newpage')
);

// Add render method
public function render_newpage() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
    }
    include VH360_THEME_DIR . '/includes/admin/pages/newpage.php';
}
```

### Adding New Settings

To add new settings, edit the `register_settings()` method:

```php
register_setting('vh360_newpage_settings', 'vh360_newpage_options', array(
    'sanitize_callback' => array($this, 'sanitize_newpage_settings'),
));
```

And add the sanitization method:

```php
public function sanitize_newpage_settings($input) {
    $sanitized = array();
    // Add sanitization logic
    return $sanitized;
}
```

## WordPress Hooks

The admin class uses the following hooks:

- `admin_menu` - Registers admin menu pages
- `admin_enqueue_scripts` - Enqueues admin assets
- `admin_init` - Registers settings and handles actions
- `admin_notices` - Displays admin notices
- `wp_head` - Outputs custom CSS
- `wp_ajax_vh360_clear_cache` - AJAX cache clearing
- `wp_ajax_vh360_import_settings` - AJAX settings import

## Best Practices

1. **Always use nonces** for form submissions and AJAX requests
2. **Check capabilities** before displaying sensitive information
3. **Sanitize inputs** using appropriate WordPress functions
4. **Escape outputs** using `esc_html()`, `esc_attr()`, etc.
5. **Use prepared statements** for any custom SQL queries
6. **Provide clear feedback** to users after actions
7. **Document your code** with PHPDoc comments

## Troubleshooting

### Menu Not Appearing

- Check that you're logged in as an Administrator
- Verify the theme is active
- Check that `includes/admin/class-vh360-theme-admin.php` exists

### Settings Not Saving

- Check browser console for JavaScript errors
- Verify nonces are being generated and validated
- Ensure user has `manage_options` capability
- Check for PHP errors in debug log

### Import/Export Not Working

- Ensure JSON file is valid
- Check file upload permissions
- Verify nonces in AJAX requests
- Check browser console for errors

### Cache Not Clearing

- Verify AJAX endpoint is registered
- Check nonce validation
- Ensure user has proper capabilities
- Look for PHP errors in debug log

## Support

For issues or questions about the admin menu system:

1. Check the [Documentation](https://videohub360.com/docs)
2. Visit the [Support Forum](https://videohub360.com/support)
3. Review the [Troubleshooting Guide](https://videohub360.com/docs/troubleshooting)

## Changelog

### Version 1.0.0
- Initial release of admin menu system
- 7 submenu pages for comprehensive theme management
- Import/Export functionality
- Complete security implementation
- Responsive admin interface
- WordPress coding standards compliance
