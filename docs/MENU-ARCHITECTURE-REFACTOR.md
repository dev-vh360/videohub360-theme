# Menu Architecture Refactor

**Date:** 2026-04-01  
**Status:** Implementation Complete, Testing Required

## Summary

Refactored the mobile drawer menu item system to eliminate duplication with the dashboard menu items. Both systems now derive from the centralized dashboard tabs registry, ensuring consistency and maintainability.

## Problem Addressed

**Before this refactor:**
- Dashboard Menu Items: Read from `vh360_get_dashboard_tabs_registry()` ✅
- Mobile Drawer Items: Hardcoded array of 12 items ❌
- User Menu Items: Separate (account actions) ✅
- Mobile Bottom Nav Items: Separate (icons/behavior) ✅

**Issue:** The mobile drawer maintained its own hardcoded list of dashboard items, causing:
- Duplication of tab definitions
- Risk of drift between drawer and dashboard
- Missing advanced features (dynamic labels, visibility rules)
- Manual maintenance when adding new tabs

## Solution Implemented

Created a shared helper function that both Dashboard Menu Items and Mobile Drawer Items use:

```php
vh360_get_dashboard_surface_item_definitions( $url_format, $user_id )
```

This helper:
- Reads from the centralized `vh360_get_dashboard_tabs_registry()`
- Supports both URL formats: 'fragment' (#tab-id) and 'query' (?tab=tab-id)
- Respects visibility rules (show_callback)
- Resolves dynamic labels (label_callback)
- Provides single source of truth for dashboard-based navigation

## Files Modified

### 1. `includes/navigation/dashboard-tabs.php`
**Added:** New shared helper function `vh360_get_dashboard_surface_item_definitions()`
- Lines 185-262
- Handles URL format conversion
- Applies visibility and label callbacks
- Returns standardized item definitions

### 2. `includes/admin/nav-menus-vh360-meta-boxes.php`
**Refactored:** `vh360_get_dashboard_menu_item_definitions()`
- Lines 42-72
- Now uses shared helper with 'fragment' URL format
- Removed ~20 lines of duplicate registry processing

### 3. `includes/user-menu-functions.php`
**Refactored:** `vh360_render_mobile_drawer_menu_meta_box()`
- Lines 497-540
- Removed 50+ lines of hardcoded item array
- Now uses shared helper with 'query' URL format
- Added descriptive help text

## Architecture Maintained

The refactor preserves the correct separation of concerns:

### Four Menu Locations (Unchanged)
1. **dashboard** - Dashboard tab navigation with fragment URLs
2. **user-menu** - Account/dropdown actions (separate, unchanged)
3. **vh360_mobile_bottom** - Mobile shortcuts with icons (separate, unchanged)
4. **vh360_mobile_drawer** - Mobile dashboard menu (now uses registry)

### Three Admin Item Systems (Unchanged)
1. **User Menu Items** - Account actions, user-specific URLs
2. **Dashboard/Drawer Items** - Shared dashboard tab source ✨ NEW
3. **Mobile Bottom Nav Items** - Icons, special behavior, 5-item limit

## Benefits

### 1. Single Source of Truth
- Only one place to define dashboard tabs: `includes/navigation/dashboard-tabs.php`
- No more manual synchronization between surfaces

### 2. Automatic Consistency
- New tabs added to registry automatically appear in both meta boxes
- Visibility rules apply consistently
- Labels stay synchronized

### 3. Advanced Features Now Work
- **Dynamic Labels:** "Appointments" vs "My Appointments" based on account type
- **Visibility Rules:** Push Notifications only for users with capability
- **Account-Type Items:** Business Profile only for professionals/organizations
- **Approval-Based Items:** Availability only for approved professionals

### 4. Maintainability
- Future tab additions: Update registry only
- No risk of drawer items drifting from dashboard
- Easier to understand and maintain

## URL Format Details

### Dashboard Menu Items (Fragment Format)
```
https://example.com/dashboard/#overview
https://example.com/dashboard/#create-video
https://example.com/dashboard/#videos
```
- Used by dashboard walker
- JavaScript handles tab switching
- Preserves current dashboard page state

### Mobile Drawer Items (Query Format)
```
https://example.com/dashboard/?tab=overview
https://example.com/dashboard/?tab=create-video
https://example.com/dashboard/?tab=videos
```
- Used by mobile drawer menu
- Server-side tab activation
- Works without JavaScript

Both formats point to the same tabs but differ in front-end handling requirements.

## Registry Features

The dashboard tabs registry (`vh360_get_dashboard_tabs_registry()`) defines:

### Basic Properties
- `label` - Default display label
- `icon_svg` - SVG markup for icons

### Advanced Callbacks
- `label_callback` - Function to compute dynamic labels per user
- `show_callback` - Function to determine visibility per user

### Example: Dynamic Label
```php
'appointments' => array(
    'label' => __( 'Appointments', 'videohub360-theme' ),
    'label_callback' => function( $user_id ) {
        $account_type = vh360_get_user_account_type( $user_id );
        if ( in_array( $account_type, array( 'professional', 'organization' ), true ) ) {
            return __( 'Appointments', 'videohub360-theme' );
        } else {
            return __( 'My Appointments', 'videohub360-theme' );
        }
    },
    'show_callback' => '__return_true',
),
```

### Example: Visibility Rule
```php
'push-notifications' => array(
    'label' => __( 'Push Notifications', 'videohub360-theme' ),
    'show_callback' => function( $user_id ) {
        return current_user_can( 'vh360_send_push' );
    },
),
```

## Testing Checklist

### Admin Testing (Appearance → Menus)

- [ ] **Dashboard Menu Items meta box**
  - [ ] Shows all registry tabs
  - [ ] Labels resolve correctly (check "Appointments" label variation)
  - [ ] URLs use fragment format (#tab-id)
  - [ ] Items can be added to menus
  
- [ ] **Mobile Drawer Items meta box**
  - [ ] Shows same registry tabs as Dashboard
  - [ ] Labels match Dashboard items
  - [ ] URLs use query format (?tab=tab-id)
  - [ ] Items can be added to menus
  - [ ] Help text displays correctly

- [ ] **User Menu Items meta box**
  - [ ] Still shows separate account actions
  - [ ] Not affected by dashboard changes

- [ ] **Mobile Bottom Nav Items meta box**
  - [ ] Still shows 4 mobile items with icons
  - [ ] Special behavior classes preserved
  - [ ] Not affected by dashboard changes

### Front-End Testing

- [ ] **Dashboard Menu**
  - [ ] Navigation still works with fragment URLs
  - [ ] Tab switching functions correctly
  - [ ] Icons display properly
  - [ ] Visibility rules respected (professional-only items)

- [ ] **Mobile Drawer**
  - [ ] Navigation works with query URLs
  - [ ] All registry tabs appear
  - [ ] Dynamic labels display correctly
  - [ ] Permission-based items show/hide appropriately

- [ ] **Mobile Bottom Nav**
  - [ ] Icons display correctly
  - [ ] 5-item limit enforced
  - [ ] Special behaviors work (notification badge, drawer toggle)

- [ ] **User Menu**
  - [ ] Account actions work
  - [ ] Profile link resolves correctly
  - [ ] Not affected by changes

### Visibility Rule Testing

Test with different user types:

- [ ] **Creator Account**
  - [ ] Cannot see "Business Profile" tab
  - [ ] Cannot see "Availability" tab
  - [ ] Sees "My Appointments" label

- [ ] **Professional Account (Unapproved)**
  - [ ] Sees "Business Profile" tab
  - [ ] Cannot see "Availability" tab
  - [ ] Cannot see "Events" tab
  - [ ] Sees "Appointments" label

- [ ] **Professional Account (Approved)**
  - [ ] Sees "Business Profile" tab
  - [ ] Sees "Availability" tab
  - [ ] Sees "Events" tab
  - [ ] Sees "Appointments" label

- [ ] **User with vh360_send_push capability**
  - [ ] Sees "Push Notifications" tab

- [ ] **User without vh360_send_push capability**
  - [ ] Cannot see "Push Notifications" tab

## Code Quality

### Syntax Validation
✅ All modified files passed PHP syntax check:
- `includes/navigation/dashboard-tabs.php`
- `includes/admin/nav-menus-vh360-meta-boxes.php`
- `includes/user-menu-functions.php`

### File Loading Order
✅ Verified correct dependency order in `functions.php`:
1. Line 1016: `helpers.php` (provides vh360_get_dashboard_page_url)
2. Line 1031: `dashboard-tabs.php` (provides registry and shared helper)
3. Line 1195: `nav-menus-vh360-meta-boxes.php` (uses shared helper)

### Audit Results
✅ No other hardcoded dashboard tab lists found in codebase
✅ Registry already used by dashboard walker (functions.php)
✅ No conflicting definitions or duplicate sources

## Future Maintenance

### Adding a New Dashboard Tab

**Before this refactor:** Required updates in 2 places
1. Update `vh360_get_dashboard_tabs_registry()` in `dashboard-tabs.php`
2. Update hardcoded array in `vh360_render_mobile_drawer_menu_meta_box()`

**After this refactor:** Requires update in 1 place only
1. Update `vh360_get_dashboard_tabs_registry()` in `dashboard-tabs.php`

The new tab automatically appears in:
- Dashboard Menu Items meta box
- Mobile Drawer Items meta box
- Dashboard front-end navigation
- Mobile drawer front-end navigation

### Example: Adding "Bookings" Tab

```php
// In includes/navigation/dashboard-tabs.php
'bookings' => array(
    'label' => __( 'Bookings', 'videohub360-theme' ),
    'label_callback' => null,
    'show_callback' => function( $user_id ) {
        // Only show for professionals
        $account_type = vh360_get_user_account_type( $user_id );
        return in_array( $account_type, array( 'professional', 'organization' ), true );
    },
    'icon_svg' => '<svg>...</svg>',
),
```

That's it! No other changes needed. Both admin meta boxes will include it automatically.

## Backward Compatibility

### Filter Compatibility
Both systems maintain their original filter hooks:
- `vh360_dashboard_menu_item_definitions` - Still works
- `vh360_mobile_drawer_meta_box_items` - Still works

Plugins or custom code using these filters continue to function.

### URL Format Compatibility
- Dashboard items still use fragment URLs (#tab-id)
- Drawer items still use query URLs (?tab=tab-id)
- Front-end rendering logic unchanged

### Menu Location Compatibility
All 4 menu locations remain registered and functional:
- `dashboard`
- `user-menu`
- `vh360_mobile_bottom`
- `vh360_mobile_drawer`

## Conclusion

This refactor successfully eliminates duplication while maintaining the correct architectural separation. The four menu locations remain distinct because they serve different front-end purposes, but the underlying data source for dashboard-based items is now centralized.

**Key Achievement:** Future dashboard tab additions or modifications only require updating the registry in one location, with all dependent systems automatically staying in sync.
