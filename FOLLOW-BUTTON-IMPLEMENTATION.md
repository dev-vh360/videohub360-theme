# Follow Button Standardization Implementation

## Summary

This implementation standardizes follow button rendering across all profile types and the members directory, adding admin controls for follow button visibility.

## Changes Made

### 1. Admin Settings

#### Profile Settings (vh360_profile_options)
- **New Setting**: `show_header_follow_button` (default: `true`)
- **Location**: Admin → VH360 Theme → Profiles → "Profile Display Settings"
- **Controls**: Follow button visibility in all profile headers (standard, business, client)

#### Members Settings (vh360_members_options)
- **New Setting**: `show_card_follow_button` (default: `true`)
- **Location**: Admin → VH360 Theme → Members → "Display Settings"
- **Controls**: Follow button visibility on member cards in the directory

### 2. Profile Header Updates

#### Standard Profile Header (`template-parts/profile/header.php`)
- Added setting check before rendering follow button
- Follow button only renders when:
  - User is logged in
  - Viewing another user's profile (not own)
  - `show_header_follow_button` is enabled
  - `vh360_follow_button()` function exists

#### Business Profile Header (`template-parts/business/header.php`)
- Added follow button support to business profiles
- Refactored actions container to support both Message and Follow buttons independently
- Follow button shows even when message button is unavailable
- Actions area uses flex layout with proper gap for multiple buttons
- Follow button only renders when setting is enabled and user is logged in

#### Client Profile Header (`template-parts/client/header.php`)
- Added new header actions area for client profiles
- Follow button now available on client profiles
- Follows same conditional logic as other profile types

### 3. Member Card Refactoring (`template-parts/components/card-profile.php`)

#### Before
- Card built its own follow button using custom markup
- Used `vh360_is_following()` directly
- Created its own nonce
- Had hardcoded button classes and text

#### After
- Card now uses `vh360_follow_button()` helper
- New arg: `show_follow_button` (default: `true`)
- Follow button rendering delegated to shared helper
- Maintains card footer wrapper for styling consistency
- Benefits:
  - Single source of truth for follow button logic
  - Consistent nonce handling
  - Consistent AJAX behavior
  - Prevents code duplication and drift

### 4. Members Directory Page-Level Overrides

#### Meta Box Updates (`includes/members-directory-meta-box.php`)
- Added new field: "Profile Card Follow Button"
- Options: Inherit Global, Show, Hide
- Displays current global setting in "Inherit" option text
- Saves to post meta: `_vh360_members_directory_show_card_follow_button_override`

#### Mode Resolver Updates (`includes/members-directory-mode.php`)
- Extended effective mode to include `show_card_follow_button`
- Applies page-level override when present
- Falls back to global setting when set to "inherit"
- Returns resolved mode for use in templates and AJAX

#### Template Updates
- `template-members-directory.php`: Passes `show_follow_button` to cards based on resolved mode
- `includes/ajax-handlers.php`: AJAX search/filter/pagination respects resolved mode

### 5. CSS Styling

#### Business Header (`assets/css/business.css`)
- Updated `.vh360-business-actions` to use flex layout with gap
- Added styles for follow and unfollow button states
- Buttons display side-by-side when both message and follow are present
- Hover states and transitions for professional appearance

#### Client Header (`assets/css/client.css`)
- Added `.vh360-client-actions` styling
- Follow button integrates seamlessly with header layout
- Consistent sizing and spacing with other profile types

## Architecture Benefits

1. **Centralized Control**: Admins can globally enable/disable follow buttons without code changes
2. **Granular Override**: Individual directory pages can override global settings
3. **Code Reuse**: All follow buttons use the same helper function
4. **Consistency**: Follow state, nonce handling, and AJAX behavior is unified
5. **Maintainability**: Changes to follow button logic only need to happen in one place
6. **AJAX Consistency**: Search, filter, and pagination results respect the same settings as initial load

## Default Behavior

Both new settings default to `true`, preserving existing follow button behavior unless an admin explicitly disables it. This ensures:
- No breaking changes for existing installations
- Expected functionality remains available
- Admins can opt-out if desired

## Settings Flow

### Profile Headers
```
Global Setting (show_header_follow_button)
  ↓
Profile Header Template (standard/business/client)
  ↓
Conditional Rendering
  ↓
vh360_follow_button() Helper
```

### Member Cards
```
Global Setting (show_card_follow_button)
  ↓
Page Override (inherit/show/hide) [optional]
  ↓
vh360_get_members_directory_effective_mode()
  ↓
Template / AJAX Handler
  ↓
card-profile.php (show_follow_button arg)
  ↓
vh360_follow_button() Helper
```

## Backward Compatibility

- All new settings default to enabled (true)
- No breaking changes to existing functionality
- Graceful degradation if `vh360_follow_button()` doesn't exist
- Existing follow system remains unchanged

## Testing Checklist

✓ Profile headers respect global setting
✓ Business headers support both message and follow buttons
✓ Client headers now include follow button
✓ Member cards use shared helper
✓ Member cards respect global setting
✓ Page-level overrides work correctly
✓ AJAX maintains consistency
✓ No PHP syntax errors
✓ CSS styling looks professional
✓ Self-profiles never show follow button
✓ Logged-out users don't see follow buttons

## Files Modified

1. `includes/admin/class-vh360-theme-admin.php` - Added settings and sanitization
2. `includes/admin/pages/profiles.php` - Added profile follow button checkbox
3. `includes/admin/pages/members.php` - Added card follow button checkbox
4. `template-parts/profile/header.php` - Applied setting check
5. `template-parts/business/header.php` - Added follow button support
6. `template-parts/client/header.php` - Added follow button support
7. `template-parts/components/card-profile.php` - Refactored to use helper
8. `includes/members-directory-mode.php` - Added follow button to effective mode
9. `includes/members-directory-meta-box.php` - Added page-level override field
10. `template-members-directory.php` - Pass follow button setting to cards
11. `includes/ajax-handlers.php` - Pass follow button setting to cards
12. `assets/css/business.css` - Added follow button styling
13. `assets/css/client.css` - Added follow button styling

## Future Enhancements

Possible improvements for future iterations:
- Per-profile-type follow button control (e.g., enable on business but not client)
- Follow button customization (text, icons, placement)
- Integration with notification preferences
- Follow limits or restrictions based on user roles
