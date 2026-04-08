# Gallery Creation Membership Integration

## Problem

Gallery creation was not using the centralized membership-aware permission helper, causing it to bypass membership gating even when:
- `enable_memberships` was enabled
- `gate_create_galleries` was enabled  
- User did not have an active membership

This broke consistency with other frontend creation features (videos, posts, events, bulletins, live rooms).

## Root Cause

Gallery creation used the legacy capability-only check:
- `VH360_Gallery_Capabilities::can_create_gallery()` 

This occurred in:
1. `template-parts/dashboard/gallery.php` - Frontend UI
2. `includes/gallery/class-vh360-gallery-ajax.php` - AJAX handler

While other creation features were already using centralized helpers like:
- `vh360_user_can_create_videos()`
- `vh360_user_can_create_posts()`
- `vh360_user_can_create_events()`
- `vh360_user_can_create_bulletins()`

## Solution

### Files Modified

#### 1. `template-parts/dashboard/gallery.php` (line 28)

**Before:**
```php
// Check if capabilities class exists before calling static methods.
if ( ! class_exists( 'VH360_Gallery_Capabilities' ) ) {
	?>
	<div class="vh360-dashboard-notice">
		<p><?php esc_html_e( 'Gallery system is not available.', 'videohub360-theme' ); ?></p>
	</div>
	<?php
	return;
}

$can_create = VH360_Gallery_Capabilities::can_create_gallery();
```

**After:**
```php
// Check if user can create galleries using centralized permission helper.
$can_create = function_exists( 'vh360_user_can_create_galleries' ) && vh360_user_can_create_galleries( $current_user_id );
```

#### 2. `includes/gallery/class-vh360-gallery-ajax.php` (line 97-99)

**Before:**
```php
if ( ! VH360_Gallery_Capabilities::can_create_gallery() ) {
	$this->send_error( __( 'You do not have permission to create galleries.', 'videohub360-theme' ) );
}
```

**After:**
```php
// Use centralized membership-aware permission helper.
if ( ! function_exists( 'vh360_user_can_create_galleries' ) || ! vh360_user_can_create_galleries() ) {
	$this->send_error( __( 'You do not have permission to create galleries.', 'videohub360-theme' ) );
}
```

## Centralized Helper

The `vh360_user_can_create_galleries()` helper in `includes/permissions/helpers.php` enforces:

1. **Admin bypass**: Users with `manage_options` always have access
2. **Capability check**: Requires `create_vh360_galleries` capability
3. **Global membership toggle**: Respects `enable_memberships` setting
4. **Feature-specific gate**: Respects `gate_create_galleries` setting
5. **Membership entitlement**: Calls `vh360_can_access_membership_feature('create_galleries', $user_id)`

```php
function vh360_user_can_create_galleries($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admins always have access
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check capability
    if (!user_can($user_id, 'create_vh360_galleries')) {
        return false;
    }
    
    // Check membership if feature gating is enabled
    if (function_exists('vh360_can_access_membership_feature')) {
        static $options = null;
        if ($options === null) {
            $options = get_option('vh360_membership_options', array());
        }
        
        // Only enforce membership if system is enabled AND this specific gate is on
        if (!empty($options['enable_memberships']) && !empty($options['gate_create_galleries'])) {
            if (!vh360_can_access_membership_feature('create_galleries', $user_id)) {
                return false;
            }
        }
    }
    
    return apply_filters('vh360_user_can_create_galleries', true, $user_id);
}
```

## Behavior

### When `enable_memberships` is OFF
- Gallery creation works based on capability permissions only
- No membership restriction

### When `enable_memberships` is ON and `gate_create_galleries` is OFF
- Gallery creation works based on capability permissions only
- No membership restriction

### When `enable_memberships` is ON and `gate_create_galleries` is ON
- Gallery creation requires active membership
- Users without membership see "Create Gallery" button removed
- AJAX requests are rejected with permission error

### Administrator Users
- Always bypass membership gating
- Can create galleries regardless of membership status

## Consistency with Other Features

Gallery creation now follows the same pattern as:

| Feature | Dashboard Template | AJAX Handler | Centralized Helper |
|---------|-------------------|--------------|-------------------|
| Videos | `create-video.php` | `ajax-handlers.php` | `vh360_user_can_create_videos()` |
| Posts | `create-post.php` | `ajax-handlers.php` | `vh360_user_can_create_posts()` |
| Events | `events.php` | Event AJAX | `vh360_user_can_create_events()` |
| Bulletins | `bulletins.php` | `ajax-handlers.php` | `vh360_user_can_create_bulletins()` |
| **Galleries** | **`gallery.php`** | **Gallery AJAX** | **`vh360_user_can_create_galleries()`** |

## Legacy Code Preserved

The `VH360_Gallery_Capabilities` class remains intact and is still used for:
- Edit permissions: `can_edit_gallery()`
- Delete permissions: `can_delete_gallery()`
- Image management: `can_manage_gallery_images()`

The centralized helper incorporates capability checks, so capability logic is preserved while layering membership gating on top.

## Testing Scenarios

✅ **Membership OFF**: Gallery creation works with capability only  
✅ **Membership ON + gate OFF**: Gallery creation works with capability only  
✅ **Membership ON + gate ON**: Gallery creation requires membership  
✅ **Admin users**: Always bypass membership checks  
✅ **Non-admin with capability but no membership**: Blocked when gate is on  
✅ **Non-admin with capability and membership**: Allowed when gate is on  

## Related Documentation

- `BACKEND-FEATURE-GATING-IMPLEMENTATION.md` - Overall feature gating system
- `includes/permissions/helpers.php` - All centralized permission helpers
- `bundled-plugins/videohub360-memberships/includes/platform-integrations.php` - Feature plan filters
