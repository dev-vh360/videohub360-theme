# Mobile User Drawer Admin Setup Guide

This guide explains how to configure the Mobile User Drawer menu and Push Notifications access in the Videohub360 Theme.

## Overview

The Mobile User Drawer is a slide-up panel that appears on mobile devices, providing quick access to dashboard features. As of this update:

- The drawer links are now **admin-controlled** via WordPress menus
- Push Notifications access is **role-based** using the `vh360_send_push` capability
- Admins can select which user roles can send push notifications

## Setup Instructions

### 1. Configure Push Notification Roles (PWA Plugin)

Before creating the menu, configure which roles can access Push Notifications:

1. Navigate to **WP Admin → VH360 PWA & App → General** tab
2. Scroll to **Push Notification Sender Roles** section
3. Select the roles that should have access to send push notifications:
   - Administrator (always included)
   - Editor
   - Author
   - Contributor
   - Subscriber
   - Any custom roles
4. Click **Save Settings**

**Note:** Users in the selected roles will automatically receive the `vh360_send_push` capability and see the Push Notifications tab in their dashboard.

### 2. Create the Mobile Drawer Menu

1. Navigate to **WP Admin → Appearance → Menus**
2. Create a new menu (e.g., "Mobile Drawer")
3. Assign it to the **Mobile User Drawer** location
4. Add custom links to the menu:

#### Example Menu Items

Add these as **Custom Links** in your menu:

**Note:** Adjust the paths below based on your dashboard page location. If your dashboard is at `/my-dashboard/`, change `/dashboard/` to `/my-dashboard/` in all URLs below.

##### Create Video
- **URL:** `/dashboard/?tab=create-video`
- **Link Text:** `Create Video`
- **Description:** Allows users to upload and create video content

##### Push Notifications
- **URL:** `/dashboard/?tab=push-notifications`
- **Link Text:** `Push Notifications`
- **Description:** Allows authorized users to send push notifications

##### My Videos
- **URL:** `/dashboard/?tab=videos`
- **Link Text:** `My Videos`

##### Messages
- **URL:** `/dashboard/?tab=messages`
- **Link Text:** `Messages`

##### Settings
- **URL:** `/dashboard/?tab=settings`
- **Link Text:** `Settings`

### 3. Apply Role-Based Visibility (Optional)

You can control which menu items appear for different user roles using the existing VH360 menu visibility meta controls:

1. Expand a menu item in the menu editor
2. Look for the **VH360 Visibility** section (if available)
3. Configure visibility rules:
   - **Logged-in users only**
   - **Specific roles** (e.g., show Push Notifications only to Administrators and Editors)
   - **Guest users only**

**Note:** The Push Notifications item will only be visible to users with the `vh360_send_push` capability, regardless of menu visibility settings.

### 4. Dashboard Tab URLs

When creating menu items, you can link to any dashboard tab using this URL pattern:

```
/dashboard/?tab=TAB_NAME
```

Available tab names include:
- `overview` - Dashboard home
- `create-video` - Video upload form
- `videos` - My Videos list
- `live-rooms` - Live streaming rooms
- `go-live` - Start a live stream
- `messages` - Direct messages
- `notifications` - Activity notifications
- `push-notifications` - Send push notifications (role-based)
- `create-post` - Create blog post
- `profile` - Edit profile
- `galleries` - Photo galleries
- `events` - Events management
- `bulletins` - Bulletins/announcements
- `settings` - User settings

## Fallback Behavior

If no menu is assigned to the **Mobile User Drawer** location, the drawer will display a default set of hardcoded links. This ensures the drawer remains functional even without configuration.

## Testing Your Configuration

1. **Test Role-Based Access:**
   - Log in as different user roles
   - Open the mobile drawer (mobile view or responsive mode)
   - Verify each role sees only the appropriate menu items

2. **Test Push Notifications Access:**
   - Only users with the `vh360_send_push` capability should see:
     - Push Notifications in the mobile drawer (if added to menu)
     - Push Notifications tab in the dashboard sidebar
     - Push Notifications tab content

3. **Test Menu Visibility:**
   - Verify role-based visibility rules work as expected
   - Test with logged-in and logged-out users

## Troubleshooting

### Push Notifications tab not appearing

1. Check that the user's role is selected in **VH360 PWA & App → General → Push Notification Sender Roles**
2. Save the settings to ensure capabilities are granted
3. Log out and log back in
4. Clear any caching plugins

### Mobile Drawer not showing menu items

1. Verify the menu is assigned to the **Mobile User Drawer** location
2. Check that menu items have valid URLs
3. Ensure users have permission to view the items (check visibility rules)

### Dashboard tabs not opening correctly

1. Ensure the dashboard page uses the `template-dashboard.php` template
2. Verify the tab name in the URL matches an available tab
3. Check that JavaScript is enabled and loading correctly

## Need Help?

If you encounter issues with the Mobile User Drawer or Push Notifications configuration, please check:

1. Theme version (ensure you're running the latest version)
2. PWA plugin version
3. WordPress and PHP versions meet minimum requirements
4. Console for JavaScript errors
5. Server error logs for PHP errors
