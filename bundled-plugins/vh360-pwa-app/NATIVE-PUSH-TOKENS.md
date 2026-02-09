# Phase 2.1: Native Push Token Management - Implementation Guide

## Overview

This implementation adds foundational database layer, token management system, and REST API endpoints for native push notifications (iOS APNs + Android FCM).

## Components Implemented

### 1. Database Layer

**Table**: `wp_vh360_push_tokens`

**Schema**:
- `id` - Primary key (auto-increment)
- `user_id` - Links token to WordPress user (nullable for guest tokens)
- `device_token` - The actual push token (TEXT, supports long FCM tokens)
- `platform` - ENUM('ios', 'android')
- `wrapper_type` - VARCHAR(50) - capacitor, cordova, flutter, react-native
- `device_info` - JSON - Device metadata
- `app_version` - VARCHAR(20)
- `created_at` - DATETIME
- `last_active` - DATETIME
- `is_active` - TINYINT(1) - Soft delete flag

**Indexes**:
- PRIMARY KEY on `id`
- INDEX on `user_id`, `platform`, `last_active`, `is_active`
- UNIQUE KEY on (`device_token`(191), `platform`) - Prevents duplicates

### 2. Token Manager Class

**File**: `includes/push/class-vh360-pwa-push-token-manager.php`

**Key Methods**:
- `register_token($data)` - Register or update a token
- `get_tokens($args)` - Retrieve tokens with filters
- `get_user_tokens($user_id)` - Get tokens for specific user
- `deactivate_token($token_id)` - Soft delete
- `delete_token($token_id)` - Hard delete
- `cleanup_old_tokens($days)` - Remove old inactive tokens
- `validate_token_format($token, $platform)` - Validate token format
- `get_statistics()` - Get token statistics

**Token Format Validation**:
- **iOS (APNs)**: 64 hexadecimal characters
- **Android (FCM)**: 140+ alphanumeric characters with underscores/hyphens/colons

### 3. REST API Endpoints

**File**: `includes/push/class-vh360-pwa-push-rest-api.php`

**Namespace**: `/wp-json/vh360-pwa/v1/push/`

#### Endpoints:

**1. Register Token**
```
POST /wp-json/vh360-pwa/v1/push/register-token
```
- **Rate Limit**: 10 requests per minute per IP
- **Authentication**: Optional (links to user if logged in)
- **Request Body**:
```json
{
  "device_token": "abc123...",
  "platform": "ios",
  "wrapper_type": "capacitor",
  "device_info": {
    "os_version": "17.2",
    "model": "iPhone 14 Pro"
  },
  "app_version": "1.0.5"
}
```
- **Response**:
```json
{
  "success": true,
  "token_id": 123,
  "message": "Token registered successfully"
}
```

**2. Update Token Activity**
```
PUT /wp-json/vh360-pwa/v1/push/update-token
```
- **Rate Limit**: 60 requests per hour per IP
- **Purpose**: Keep token active
- **Request Body**:
```json
{
  "device_token": "abc123...",
  "platform": "ios"
}
```

**3. Unregister Token**
```
DELETE /wp-json/vh360-pwa/v1/push/unregister-token
```
- **Purpose**: User opts out or uninstalls app
- **Request Body**:
```json
{
  "device_token": "abc123...",
  "platform": "ios"
}
```

**4. Get My Tokens**
```
GET /wp-json/vh360-pwa/v1/push/my-tokens
```
- **Authentication**: Required (logged-in user)
- **Response**: List of user's tokens

### 4. Admin UI

**File**: `includes/push/class-vh360-pwa-push-tokens-admin.php`

**Page Location**: PWA & App → Push Notifications → Device Tokens

**Features**:
- Token statistics dashboard (total, active, iOS, Android, inactive, guest)
- Searchable/filterable list table (WP_List_Table)
- Filter by platform (iOS/Android) and status (Active/Inactive)
- Search by user email or ID
- Bulk actions: Activate, Deactivate, Delete
- Token detail modal with full information
- Row actions: View, Activate/Deactivate, Delete

**CSS**: `assets/admin/push-tokens.css`
**JS**: `assets/admin/push-tokens.js`

### 5. Token Lifecycle Management

**User Login Hook**:
- Links tokens to users when they log in
- Uses cookie-based approach for WordPress compatibility

**Cron Job**:
- Action: `vh360_pwa_push_token_cleanup`
- Schedule: Weekly
- Function: Removes inactive tokens older than 90 days (configurable)

### 6. Integration Points

**Main Plugin File** (`vh360-pwa-app.php`):
- Loads new classes
- Registers activation/deactivation hooks
- Initializes token manager and REST API
- Schedules cron job

**Activation Hook**:
- Creates database table
- Schedules cleanup cron
- Flushes rewrite rules

**Deactivation Hook**:
- Clears scheduled cron

**Uninstall** (`uninstall.php`):
- Drops tokens table
- Deletes options

**Push Manager** (`includes/push/class-vh360-pwa-push-manager.php`):
- Added native provider settings defaults:
  - `token_cleanup_days`: 90
  - `rate_limit_per_minute`: 10
  - `rate_limit_per_hour`: 60

**Push Admin** (`includes/push/class-vh360-pwa-push-admin.php`):
- Added Device Tokens section to Diagnostics tab
- Shows token statistics with link to management page

## Security Measures

1. **Rate Limiting**:
   - Transient-based per-IP rate limiting
   - Different limits for different endpoints
   - 429 status code when exceeded

2. **Input Validation**:
   - Token format validation (iOS: 64 hex, Android: 140+ chars)
   - Platform enum validation
   - Sanitization of all user inputs

3. **SQL Injection Prevention**:
   - Uses WordPress prepared statements
   - Auto-escaping via wpdb methods

4. **XSS Prevention**:
   - esc_html(), esc_attr(), esc_url() throughout
   - Sanitization before storage

5. **CSRF Protection**:
   - Nonce verification for admin actions
   - WordPress REST API permission callbacks

6. **IP Detection**:
   - Uses only REMOTE_ADDR (not spoofable headers)
   - Suitable for production with proper proxy configuration

## Testing

### Unit Tests (Standalone)
Run: `php /tmp/test-token-manager.php`

Tests:
- Token format validation (iOS/Android)
- Rate limiting logic
- Data sanitization
- Statistics calculation

**Result**: All tests pass ✓

### REST API Tests
Run: `bash /tmp/test-rest-api.sh` (requires WordPress running)

Tests:
- Token registration (iOS/Android)
- Invalid token rejection
- Rate limiting enforcement
- Token update
- Authentication checks

### Code Quality
- **PHP Syntax**: No errors ✓
- **Code Review**: 4 issues found and fixed ✓
- **CodeQL Security**: No JavaScript vulnerabilities ✓

## Usage Examples

### Mobile App Integration

**1. Register Token on App Launch**:
```javascript
// Capacitor example
import { PushNotifications } from '@capacitor/push-notifications';

async function registerPushToken() {
  const result = await PushNotifications.register();
  
  // Get the token
  PushNotifications.addListener('registration', async (token) => {
    // Send to WordPress
    const response = await fetch('https://yoursite.com/wp-json/vh360-pwa/v1/push/register-token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        device_token: token.value,
        platform: 'ios', // or 'android'
        wrapper_type: 'capacitor',
        device_info: {
          model: Device.model,
          os_version: Device.osVersion
        },
        app_version: '1.0.0'
      })
    });
    
    const data = await response.json();
    console.log('Token registered:', data);
  });
}
```

**2. Update Token Activity (Background Task)**:
```javascript
// Update every 24 hours to keep token active
async function updateTokenActivity(deviceToken, platform) {
  await fetch('https://yoursite.com/wp-json/vh360-pwa/v1/push/update-token', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      device_token: deviceToken,
      platform: platform
    })
  });
}
```

**3. Unregister on Logout/Uninstall**:
```javascript
async function unregisterPushToken(deviceToken, platform) {
  await fetch('https://yoursite.com/wp-json/vh360-pwa/v1/push/unregister-token', {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      device_token: deviceToken,
      platform: platform
    })
  });
}
```

## Configuration

**Settings Location**: Push Manager settings array

```php
'providers' => [
  'native' => [
    'token_cleanup_days'    => 90,  // Days before deleting inactive tokens
    'rate_limit_per_minute' => 10,  // Max registrations per minute
    'rate_limit_per_hour'   => 60,  // Max updates per hour
  ]
]
```

## Database Queries

**Get all active iOS tokens**:
```sql
SELECT * FROM wp_vh360_push_tokens 
WHERE platform = 'ios' AND is_active = 1 
ORDER BY last_active DESC;
```

**Get tokens for specific user**:
```sql
SELECT * FROM wp_vh360_push_tokens 
WHERE user_id = 123 AND is_active = 1;
```

**Find inactive tokens older than 90 days**:
```sql
SELECT * FROM wp_vh360_push_tokens 
WHERE is_active = 0 
AND last_active < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## Troubleshooting

### Token Registration Fails
1. Check token format (iOS: 64 hex, Android: 140+ chars)
2. Verify platform is 'ios' or 'android'
3. Check rate limiting (wait 1 minute and retry)

### Tokens Not Visible in Admin
1. Verify database table exists: `SHOW TABLES LIKE 'wp_vh360_push_tokens';`
2. Check if tokens are active: `SELECT COUNT(*) FROM wp_vh360_push_tokens WHERE is_active = 1;`
3. Clear WordPress cache

### Cron Not Running
1. Check if scheduled: `wp cron event list --allow-root`
2. Manually trigger: `wp cron event run vh360_pwa_push_token_cleanup --allow-root`
3. Verify WP-Cron is enabled

## Future Enhancements (Not in Phase 2.1)

Phase 2.2 will add:
- Actual push notification sending via APNs/FCM
- Certificate/key management for APNs
- Firebase Cloud Messaging configuration
- Message queue system
- Delivery tracking

## Files Changed

**New Files**:
- `includes/push/class-vh360-pwa-push-token-manager.php` (440 lines)
- `includes/push/class-vh360-pwa-push-rest-api.php` (390 lines)
- `includes/push/class-vh360-pwa-push-tokens-admin.php` (660 lines)
- `assets/admin/push-tokens.css` (150 lines)
- `assets/admin/push-tokens.js` (60 lines)

**Modified Files**:
- `vh360-pwa-app.php` (+50 lines)
- `includes/push/class-vh360-pwa-push-manager.php` (+6 lines)
- `includes/push/class-vh360-pwa-push-admin.php` (+50 lines)
- `uninstall.php` (+10 lines)

**Total**: ~1,800 new lines of code

## WordPress Coding Standards

All code follows:
- WordPress PHP Coding Standards
- WordPress Documentation Standards
- WordPress Security Best Practices
- PSR-12 influenced formatting

## License

GPLv2 or later (consistent with main plugin)
