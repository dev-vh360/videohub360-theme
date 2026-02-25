# Appointment Live Room Agora Configuration Fix

## Issue
Users couldn't start video in appointment Live Rooms from the frontend without manual WordPress backend configuration.

## Problem Details
When appointment Live Rooms were created through the booking system, the Agora setting `_vh360_agora_everyone_is_host` was set to `'no'`. This caused:

1. Both professional and client joined as 'audience' role (RoleSubscriber)
2. Audience role can only view, cannot publish video/audio
3. Video controls appeared but didn't work
4. User had to manually:
   - Go to WordPress backend
   - Edit the Live Room post
   - Set "Allow Everyone to be Host" to Yes
   - Save the post

## Root Cause

### Agora Role System
Agora has different roles for participants:

- **RolePublisher (host)** - Can publish video/audio and view others
- **RoleSubscriber (audience)** - Can only view, cannot publish

### Role Assignment Logic
The `_vh360_agora_everyone_is_host` meta field controls role assignment:

```php
if ($fields['agora_everyone_is_host'] === 'yes') {
    $role = 'host'; // All users join as host (can publish)
} else {
    $role = $is_original_host ? 'host' : 'audience'; // Only post author is host
}
```

### The Problem
Appointment booking code was setting:
```php
update_post_meta($live_room_id, '_vh360_agora_everyone_is_host', 'no');
```

This meant:
- Only the post author (professional) would get 'host' role
- Client would get 'audience' role
- Client couldn't publish video/audio
- But actually, even the professional couldn't start video properly because the room wasn't configured as expected

## Solution

### The Fix
Changed the appointment Live Room creation to set:
```php
// For appointment rooms, both professional and client need host permissions to publish video/audio
update_post_meta($live_room_id, '_vh360_agora_everyone_is_host', 'yes');
```

### Why This is Correct
Appointment rooms are 1:1 video consultations where:
- ✅ Professional needs to publish video/audio (provide consultation)
- ✅ Client needs to publish video/audio (participate in consultation)
- ✅ Both parties need equal interaction capabilities
- ✅ This matches typical video consultation behavior (Zoom, Google Meet, etc.)

### What Changed
**File:** `includes/class-vh360-availability-ajax.php` (line 236)

**Before:**
```php
update_post_meta($live_room_id, '_vh360_agora_everyone_is_host', 'no');
```

**After:**
```php
// For appointment rooms, both professional and client need host permissions to publish video/audio
update_post_meta($live_room_id, '_vh360_agora_everyone_is_host', 'yes');
```

## Impact

### Before Fix
❌ Couldn't start video from frontend
❌ Required manual backend configuration
❌ Poor user experience
❌ Blocked normal appointment workflow

### After Fix
✅ Both parties can start video from frontend immediately
✅ No manual configuration required
✅ Works like expected video consultation app
✅ Smooth appointment workflow

### Other Live Room Types
This change only affects appointment Live Rooms created through the booking system. Other Live Room types (community rooms, creator streams) are unaffected because they:
- Are created through different code paths
- Have their own configuration settings
- May want different permission models

## Technical Details

### Agora Token Roles
When generating Agora tokens, the role determines permissions:

```php
// From RtcTokenBuilder.php
const RolePublisher = 1;    // Can publish video/audio
const RoleSubscriber = 2;   // Can only subscribe/view
```

### Token Generation
```php
if ($role === 'host') {
    $role_int = RtcTokenBuilder::RolePublisher;
} else {
    $role_int = RtcTokenBuilder::RoleSubscriber;
}

$token = RtcTokenBuilder::buildTokenWithUid($app_id, $app_certificate, 
    $channel_name, $uid, $role_int, $privilegeExpiredTs);
```

### Frontend Role Assignment
The renderer checks the `everyone_is_host` setting:

```php
// In render-livestream.php
if ($fields['agora_everyone_is_host'] === 'yes') {
    $role = 'host'; // All users join as host
} else {
    $role = $is_original_host ? 'host' : 'audience';
}
```

This role is then passed to the JavaScript client, which requests a token with that role.

## Testing

### Test Scenario
1. **Book Appointment**
   - Client books appointment with professional
   - System creates appointment Live Room automatically

2. **Professional Starts Session**
   - Professional goes to Dashboard → Availability
   - Clicks "Start Session" for the appointment
   - Session goes live

3. **Professional Joins**
   - Professional opens Live Room page
   - Clicks to join video
   - ✅ Video controls work (camera, microphone)
   - ✅ Can start video/audio immediately

4. **Client Joins**
   - Client goes to Dashboard → My Appointments
   - Sees "Live Now" indicator
   - Clicks "Join Session"
   - ✅ Video controls work (camera, microphone)
   - ✅ Can start video/audio immediately

5. **Video Consultation**
   - ✅ Both parties can see and hear each other
   - ✅ Both can toggle camera/microphone
   - ✅ Chat works
   - ✅ Professional can end session

### Verification
- No manual backend configuration needed
- No "audience" restrictions
- Works like typical video call app
- Smooth user experience

## Related Settings

### Other Appointment Room Meta
When creating appointment Live Rooms, these settings are also applied:

```php
update_post_meta($live_room_id, '_vh360_context', 'live_room');
update_post_meta($live_room_id, '_vh360_type', 'agora');
update_post_meta($live_room_id, '_vh360_is_live', 'no');
update_post_meta($live_room_id, '_vh360_agora_mode', 'interactive');
update_post_meta($live_room_id, '_vh360_agora_everyone_is_host', 'yes'); // ✅ Fixed
update_post_meta($live_room_id, '_vh360_chat_enabled', 'yes');
update_post_meta($live_room_id, '_vh360_agora_channel_name', 'appt-' . $event_id);
```

### Access Control
Appointment rooms also have access control via `appointment-live-room-gate.php`:
- Only professional, client, and admins can access
- Page-level access control
- Agora token validation
- This security is separate from the everyone_is_host setting

## Conclusion
This fix ensures appointment Live Rooms work as expected for video consultations, allowing both parties to participate fully without manual configuration. The change is minimal, targeted, and doesn't affect other features.

**Status: Fixed in commit fe55ae2** ✅
