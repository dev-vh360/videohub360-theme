# VH360 PWA Push Notifications - Phase 1

## Overview

This implementation adds OneSignal Web Push Notifications to the VH360 PWA & App plugin. The system is architected to support multiple push providers and delivery modes (Provider, Native, Hybrid) with Phase 1 focusing on OneSignal Web Push.

## Features Implemented

### 1. Core Architecture
- **Push Manager**: Central manager that routes push notifications based on mode
- **Adapter Interface**: Standardized interface for all push providers
- **OneSignal Adapter**: Full implementation of OneSignal Web Push integration
- **Extensible Design**: Ready for Phase 2 native push integration

### 2. Admin Interface
Located at: **PWA & App → Push Notifications**

Three main tabs:
- **Setup**: Configure OneSignal credentials and settings
- **Diagnostics**: System checks, service worker validation, and test sends
- **Send**: Send notifications to all subscribers

### 3. OneSignal Integration

#### Required Settings
- **OneSignal App ID**: Your OneSignal application ID (UUID format)
- **OneSignal REST API Key**: Your REST API key for server-side sending
- **Default Click URL**: URL to open when notification is clicked (optional)
- **Default Icon URL**: Default icon for notifications (optional)

#### Service Worker Management
The plugin automatically serves OneSignal service worker files at:
- `/OneSignalSDKWorker.js`
- `/OneSignalSDKUpdaterWorker.js`

No manual file upload required!

### 4. Frontend Integration

#### Shortcode: `[vh360_push_subscribe]`

Displays a subscription widget with automatic state management:
- **Unsupported**: Browser doesn't support push notifications
- **Not Subscribed**: Shows "Enable Notifications" button
- **Subscribed**: Shows confirmation message
- **Blocked**: Shows instructions to reset permissions

Example usage:
```
[vh360_push_subscribe button_text="Get Notifications" enabled_text="You're subscribed!"]
```

### 5. Notification Sending

#### From Admin Panel
1. Go to Push Notifications → Send tab
2. Enter title, body, and click URL
3. Click "Send Notification" to send to all subscribers

#### Test Sends
Use the "Send Test Push" button in the Diagnostics tab to send test notifications.
Rate limited to 1 test per minute per admin user.

### 6. Logging & Diagnostics

#### Activity Log
- Tracks last 50 send/test operations
- Shows timestamp, action, title, and status
- Viewable in Diagnostics tab

#### System Checks
- HTTPS verification (required for web push)
- Service worker endpoint accessibility
- Settings validation
- Browser support notes

#### Support Report
Export a comprehensive diagnostics report for troubleshooting:
- Plugin and WordPress versions
- Configuration status (with secrets redacted)
- Service worker endpoint status
- Recent activity log

### 7. Security Features

- REST API key never exposed to frontend
- Settings validation before SDK loading
- Rate limiting on test sends
- Nonce protection on all admin actions

## Setup Instructions

### 1. Get OneSignal Credentials

1. Create a free account at [OneSignal.com](https://onesignal.com)
2. Create a new app (select "Web Push" platform)
3. Go to Settings → Keys & IDs
4. Copy your **App ID** and **REST API Key**

### 2. Configure in WordPress

1. Go to **PWA & App → Push Notifications**
2. Navigate to the **Setup** tab
3. Enter your OneSignal App ID
4. Enter your OneSignal REST API Key
5. (Optional) Set default click URL and icon URL
6. Click **Save Settings**

### 3. Add Subscription Widget

Add the shortcode to any page or post:
```
[vh360_push_subscribe]
```

Or in your theme template:
```php
<?php echo do_shortcode('[vh360_push_subscribe]'); ?>
```

### 4. Verify Setup

1. Go to **Push Notifications → Diagnostics** tab
2. Check that all system checks are green
3. Verify service worker endpoints return HTTP 200
4. Send a test notification

## Requirements

- WordPress 5.0+
- PHP 7.4+
- **HTTPS** (required for web push notifications)
- OneSignal account (free tier available)

## Browser Support

Web push notifications are supported in:
- Chrome/Edge (desktop & Android)
- Firefox (desktop & Android)
- Safari 16+ (macOS 13+, iOS 16.4+)
- Opera (desktop & Android)

**Note**: Private/Incognito browsing modes do not support push notifications.

## Common Issues & Solutions

### Issue: Service Worker Returns 403/404

**Cause**: CDN or caching plugin blocking service worker files

**Solution**: 
1. Whitelist these paths in your CDN/cache settings:
   - `/OneSignalSDKWorker.js`
   - `/OneSignalSDKUpdaterWorker.js`
2. Clear your CDN and site caches
3. Re-test in Diagnostics tab

### Issue: "Not on HTTPS" Error

**Cause**: Site is not using HTTPS

**Solution**: 
- Install an SSL certificate
- Update WordPress site URL to use `https://`
- Web push requires HTTPS (except on localhost for development)

### Issue: Notifications Not Appearing

**Possible Causes**:
1. User denied permission (check Diagnostics tab)
2. Browser notification settings disabled
3. Do Not Disturb mode enabled (macOS/iOS)
4. Invalid OneSignal credentials

**Troubleshooting**:
1. Check Diagnostics tab for configuration errors
2. Send a test notification and check the activity log
3. Verify credentials in OneSignal dashboard
4. Check browser console for errors (F12)

### Issue: "Configuration Error" in Admin

**Cause**: Invalid or missing OneSignal credentials

**Solution**:
1. Verify App ID is in UUID format (e.g., `12345678-1234-1234-1234-123456789abc`)
2. Verify REST API Key is complete (should be 40+ characters)
3. Check for extra spaces or quotes in settings
4. Re-enter credentials from OneSignal dashboard

## Advanced Configuration

### Auto-Prompt Settings

By default, the plugin does NOT auto-prompt users. To enable:

1. Go to Setup tab
2. Check "Auto-prompt on page load"
3. Set delay in seconds (recommended: 5-10 seconds)

**Note**: OneSignal also provides its own prompt configuration in their dashboard.

### Prompt Policy

Default behavior (recommended):
- No auto-prompting
- User must explicitly click subscription button
- Follows best practices for permission requests

## Architecture Details

### Settings Storage
All settings stored in: `vh360_pwa_push_settings` (option)

Structure:
```php
array(
    'mode' => 'provider',  // provider | native | hybrid
    'active_provider' => 'onesignal',
    'providers' => array(
        'onesignal' => array(
            'app_id' => '',
            'rest_api_key' => '',
            'default_click_url' => '',
            'default_icon_url' => '',
            'auto_prompt' => false,
            // ... more settings
        )
    )
)
```

### Logging
Activity logs stored in: `vh360_pwa_push_logs` (option)
- Last 50 entries kept
- Automatically rotated

### Adapter Pattern
New providers can be added by:
1. Implementing `VH360_PWA_Push_Adapter_Interface`
2. Registering with Push Manager
3. No changes to admin UI required (auto-generated from adapter)

## Phase 2 Preview

Phase 2 will add:
- Native APNs (Apple Push Notification service)
- Native FCM (Firebase Cloud Messaging)
- Hybrid mode (send to both web and native)
- Advanced targeting/segmentation
- Scheduled notifications

The current architecture is ready for Phase 2 with no refactoring required.

## API Reference

### Push Manager Methods

```php
// Get push manager instance
$push_manager = VH360_PWA_App::instance()->push_manager;

// Send notification
$result = $push_manager->send(
    array(
        'title' => 'Hello',
        'body' => 'This is a notification',
        'click_url' => 'https://example.com'
    ),
    array() // audience (Phase 1: always all subscribers)
);

// Send test
$result = $push_manager->send_test(
    array(
        'title' => 'Test',
        'body' => 'Test notification'
    )
);

// Get logs
$logs = $push_manager->get_logs(10); // Last 10 entries

// Validate settings
$errors = $push_manager->validate_current_settings();
```

### Adapter Interface

All adapters must implement:
- `get_slug()`: Unique identifier
- `get_label()`: Display name
- `get_settings_fields()`: Settings UI definition
- `validate_settings()`: Validation logic
- `enqueue_frontend_sdk()`: Load provider SDK
- `get_frontend_bootstrap()`: Safe config for JS
- `send()`: Send notification
- `send_test()`: Send test notification
- `capabilities()`: Feature flags

## Support

For issues or questions:
1. Check Diagnostics tab and export support report
2. Review Common Issues section above
3. Contact plugin support with diagnostics report

## License

This feature is part of the VH360 PWA & App plugin.
Licensed under GPLv2 or later.
