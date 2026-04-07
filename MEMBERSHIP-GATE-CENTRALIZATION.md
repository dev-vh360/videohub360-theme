# Membership Gate Centralization

## Overview

This document describes the centralized membership gate rendering system that ensures consistent UI and behavior across all membership-gated surfaces on the platform.

**Date:** 2026-04-07  
**Status:** ✅ Complete and Production-Ready

---

## Problem Statement

### Before Centralization

Prior to this refactor, membership gate rendering was scattered across multiple files:

1. **Activity Feed Template** (`template-activity-feed.php`)
   - Custom gate HTML (30+ lines)
   - Manual checks for `locked_message`
   - No `login_required` support

2. **Members Directory Template** (`template-members-directory.php`)
   - Custom upgrade notice HTML
   - Hardcoded messaging
   - No `locked_message` support

3. **AJAX Handler** (`includes/ajax-handlers.php`)
   - Custom upgrade notice in response
   - Hardcoded messaging
   - No settings support

4. **Single Video Template** (`single-videohub360.php`)
   - Used reflection to access private methods
   - Complex manual logic
   - Tightly coupled to class internals

### Issues

❌ **Inconsistent UI** - Different HTML/styling across surfaces  
❌ **Scattered logic** - Gate rendering duplicated in 4+ places  
❌ **Settings not respected** - `login_required` and `locked_message` ignored in most places  
❌ **Maintenance burden** - Changes needed in multiple files  
❌ **Code smell** - Reflection pattern, hardcoded messages

---

## Solution: Centralized Rendering

### Architecture

```
All Surfaces → vh360_render_membership_gate() → Centralized Logic → Consistent Output
```

**Single Source of Truth:**
- All gate rendering goes through one function
- Settings automatically applied everywhere
- Consistent UI/UX guaranteed

---

## Implementation

### Core Functions

All functions are located in:  
**`bundled-plugins/videohub360-memberships/includes/membership-helpers.php`**

#### 1. `vh360_render_membership_gate($context = array())`

**Purpose:** Main entry point for all membership gate rendering

**Parameters:**
```php
$context = array(
    'required_plan' => 'pro'  // Optional: specific plan key
);
```

**Logic Flow:**
```php
function vh360_render_membership_gate($context = array()) {
    // Get settings
    $options = get_option('vh360_membership_options', array());
    $login_required = $options['login_required'] ?? true;
    $pricing_url = $options['pricing_page_url'] ?? '';
    $custom_message = $options['locked_message'] ?? '';
    
    $user_id = get_current_user_id();
    
    // Route to appropriate gate
    if (!$user_id && $login_required) {
        return vh360_render_login_gate();
    }
    
    return vh360_render_upgrade_gate($required_plan, $custom_message, $pricing_url);
}
```

**Decision Matrix:**

| User State | `login_required` | Gate Shown |
|------------|------------------|------------|
| Logged out | true (default) | Login gate |
| Logged out | false | Upgrade gate |
| Logged in (non-member) | true | Upgrade gate |
| Logged in (non-member) | false | Upgrade gate |

**Returns:** HTML string for gate

---

#### 2. `vh360_render_login_gate()`

**Purpose:** Render login required gate

**Output:**
```html
<div class="vh360-membership-gate vh360-membership-login-required">
    <div class="vh360-membership-gate-content">
        <svg>...</svg>
        <h3>Login Required</h3>
        <p>Please log in to access this content.</p>
        <a href="[login_url]">Log In</a>
    </div>
</div>
```

**Features:**
- Uses `vh360_get_login_page_url()` if available
- Falls back to `wp_login_url()`
- Consistent styling with upgrade gate

---

#### 3. `vh360_render_upgrade_gate($required_plan, $custom_message, $pricing_url)`

**Purpose:** Render upgrade required gate

**Parameters:**
- `$required_plan` - Plan key (optional, for context)
- `$custom_message` - Custom message from settings (auto-loaded if not provided)
- `$pricing_url` - Pricing page URL (auto-loaded if not provided)

**Output:**
```html
<div class="vh360-membership-gate vh360-membership-upgrade-required">
    <div class="vh360-membership-gate-content">
        <svg>...</svg>
        <h3>Premium Content</h3>
        [custom_message OR default_message]
        <a href="[pricing_url]">View Plans</a>
    </div>
</div>
```

**Features:**
- **Custom Message:** Uses `locked_message` from settings if set
- **Default Message:** Falls back to "This content requires an active membership"
- **Pricing Link:** Only shown if URL is set in settings
- **Auto-loading:** Gets settings if parameters not provided

---

## Usage Across Platform

### 1. Content Filtered Posts

**File:** `class-vh360-membership-frontend.php`

**Usage:**
```php
public function filter_content($content) {
    // ... access checks ...
    
    if (!$has_access) {
        return vh360_render_membership_gate(array('required_plan' => $required_plan));
    }
    
    return $content;
}
```

**Applied to:**
- Blog posts
- Pages
- Custom post types with membership requirements

---

### 2. Activity Feed Template

**File:** `template-activity-feed.php`

**Before:**
```php
<div class="vh360-membership-gate vh360-membership-upgrade-required" style="text-align: center; padding: 60px 20px;">
    <div class="vh360-membership-gate-content" style="max-width: 500px; margin: 0 auto;">
        <svg>...</svg>
        <h3><?php esc_html_e('Premium Feature', 'videohub360-theme'); ?></h3>
        <?php if ($custom_message) : ?>
            <div><?php echo wp_kses_post($custom_message); ?></div>
        <?php else : ?>
            <p><?php esc_html_e('The activity feed requires...', 'videohub360-theme'); ?></p>
        <?php endif; ?>
        <a href="..."><?php esc_html_e('View Membership Plans', 'videohub360-theme'); ?></a>
    </div>
</div>
```

**After:**
```php
<?php echo vh360_render_membership_gate(); ?>
```

**Benefits:**
- ✅ 27 lines removed
- ✅ `login_required` now respected
- ✅ `locked_message` automatically used
- ✅ Consistent with other surfaces

---

### 3. Members Directory Template

**File:** `template-members-directory.php`

**Before:**
```php
<div class="vh360-membership-upgrade-notice" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; border-top: 1px solid #ddd; margin-top: 30px;">
    <h4><?php esc_html_e('View Full Directory', 'videohub360-theme'); ?></h4>
    <p><?php esc_html_e('Upgrade to view all members...', 'videohub360-theme'); ?></p>
    <a href="..."><?php esc_html_e('Upgrade Now', 'videohub360-theme'); ?></a>
</div>
```

**After:**
```php
<?php echo vh360_render_membership_gate(); ?>
```

**New Features:**
- ✅ Now uses `locked_message` (was hardcoded)
- ✅ Respects `login_required` for logged-out users
- ✅ 10 lines removed

---

### 4. AJAX Members Directory

**File:** `includes/ajax-handlers.php`

**Before:**
```php
<div class="vh360-membership-upgrade-notice" style="...">
    <h4><?php esc_html_e('View Full Directory', 'videohub360-theme'); ?></h4>
    <p><?php esc_html_e('Upgrade to view all members...', 'videohub360-theme'); ?></p>
    <a href="..."><?php esc_html_e('Upgrade Now', 'videohub360-theme'); ?></a>
</div>
```

**After:**
```php
<?php echo vh360_render_membership_gate(); ?>
```

**New Features:**
- ✅ AJAX responses now match template rendering
- ✅ Settings respected in AJAX context
- ✅ 12 lines removed

---

### 5. Single Video Template

**File:** `bundled-plugins/videohub360-core/templates/single-videohub360.php`

**Before (using reflection):**
```php
if (!$user_has_access) {
    if (class_exists('VH360_Membership_Frontend')) {
        $frontend = VH360_Membership_Frontend::get_instance();
        $reflection = new ReflectionClass($frontend);
        
        if (!get_current_user_id()) {
            $method = $reflection->getMethod('render_login_required_notice');
            $method->setAccessible(true);
            echo $method->invoke($frontend);
        } else {
            $method = $reflection->getMethod('render_upgrade_required_notice');
            $method->setAccessible(true);
            echo $method->invoke($frontend, $required_plan);
        }
    }
}
```

**After:**
```php
if (!$user_has_access) {
    echo vh360_render_membership_gate(array('required_plan' => $required_plan));
}
```

**Improvements:**
- ✅ No more reflection
- ✅ Not coupled to class internals
- ✅ Clean, readable code
- ✅ 12 lines removed
- ✅ Future-proof

---

## Settings Behavior

### `login_required` Setting

**Location:** `vh360_membership_options['login_required']`  
**Default:** `true`  
**Type:** Boolean

#### Behavior Matrix

| Setting | User State | Gate Shown | Use Case |
|---------|------------|------------|----------|
| `true` | Logged out | Login gate | Require authentication first |
| `true` | Logged in (non-member) | Upgrade gate | Standard membership flow |
| `false` | Logged out | Upgrade gate | Preview/teaser approach |
| `false` | Logged in (non-member) | Upgrade gate | Same as true |

#### When to Use `false`

**Scenario:** You want logged-out users to see what they're missing

**Example:**
```
User visits activity feed (logged out, login_required = false)
→ Sees upgrade gate: "Join to participate in conversations"
→ No login gate barrier
→ Can see value proposition immediately
```

**Traditional flow (login_required = true):**
```
User visits activity feed (logged out)
→ Sees login gate: "Please log in"
→ Must login first
→ Then sees upgrade gate if not member
```

#### Implementation

The setting is checked in `vh360_render_membership_gate()`:

```php
if (!$user_id && $login_required) {
    return vh360_render_login_gate();
}

return vh360_render_upgrade_gate(...);
```

**Applied Everywhere:**
- ✅ Activity feed
- ✅ Members directory (template + AJAX)
- ✅ Single video pages
- ✅ Content filtered posts

---

### `locked_message` Setting

**Location:** `vh360_membership_options['locked_message']`  
**Default:** Empty (uses default message)  
**Type:** String (HTML allowed)

#### Purpose

Allows site administrators to set custom messaging on all membership gates.

#### Behavior

**If set:**
```html
<div class="vh360-membership-custom-message">
    [custom message from admin]
</div>
```

**If empty:**
```html
<p>This content requires an active membership to access.</p>
```

#### Example

**Admin sets:**
```
Join our premium community to access exclusive content, 
connect with other members, and get priority support!
```

**Appears on:**
- ✅ Activity feed gate
- ✅ Members directory gate (NEW - was hardcoded)
- ✅ AJAX directory responses (NEW - was hardcoded)
- ✅ Video page gates
- ✅ Content post gates

#### Implementation

The message is used in `vh360_render_upgrade_gate()`:

```php
<?php if ($custom_message) : ?>
    <div class="vh360-membership-custom-message">
        <?php echo wp_kses_post($custom_message); ?>
    </div>
<?php else : ?>
    <p><?php esc_html_e('This content requires...', 'videohub360-memberships'); ?></p>
<?php endif; ?>
```

**Security:** Uses `wp_kses_post()` to sanitize HTML

---

### `pricing_page_url` Setting

**Location:** `vh360_membership_options['pricing_page_url']`  
**Default:** Empty  
**Type:** URL

#### Behavior

**If set:**
```html
<a href="[pricing_url]" class="vh360-membership-gate-button">
    View Plans
</a>
```

**If empty:**
- Button not displayed
- Users see message but no action link

#### Usage

Set this to your membership pricing/signup page. All gates will link to it.

---

## Code Reduction

### Lines Removed

| File | Lines Removed | Description |
|------|---------------|-------------|
| `template-activity-feed.php` | 27 | Custom gate HTML |
| `template-members-directory.php` | 10 | Custom upgrade notice |
| `ajax-handlers.php` | 12 | Custom upgrade notice |
| `single-videohub360.php` | 12 | Reflection code |
| `class-vh360-membership-frontend.php` | 15 | Duplicate logic |
| **Total** | **76** | Duplicate code eliminated |

### Lines Added

| File | Lines Added | Description |
|------|-------------|-------------|
| `membership-helpers.php` | 137 | Centralized functions |

**Net:** +61 lines overall, but -76 lines of duplicate code removed

---

## Architecture Diagram

### Before

```
┌─────────────────────┐
│ Activity Feed       │──► Custom Gate HTML
├─────────────────────┤
│ Members Directory   │──► Custom Gate HTML
├─────────────────────┤
│ AJAX Handler        │──► Custom Gate HTML
├─────────────────────┤
│ Video Template      │──► Reflection → Private Methods
├─────────────────────┤
│ Frontend Class      │──► Private render methods
└─────────────────────┘

❌ 5 different implementations
❌ Settings ignored in most places
❌ Inconsistent behavior
```

### After

```
                    ┌──────────────────────────────┐
                    │  vh360_render_membership_gate()  │
                    │  (membership-helpers.php)     │
                    └──────────────────────────────┘
                               │
                ┌──────────────┴──────────────┐
                │                             │
        ┌───────▼────────┐        ┌──────────▼──────┐
        │ Login Gate     │        │ Upgrade Gate    │
        │ (login_required)│        │ (locked_message)│
        └────────────────┘        └─────────────────┘
                │                             │
    ┌───────────┴───────────┬────────┬───────┴──────────┐
    │                       │        │                  │
┌───▼─────┐    ┌────────────▼──┐  ┌─▼──────┐  ┌────────▼─────┐
│Activity │    │Members Directory│  │ AJAX   │  │Video Template│
│  Feed   │    │  (Template)     │  │Handler │  │              │
└─────────┘    └─────────────────┘  └────────┘  └──────────────┘

✅ 1 centralized implementation
✅ Settings applied everywhere
✅ Consistent behavior
```

---

## Testing Guide

### Test Scenario 1: Login Required = True

**Setup:**
```php
update_option('vh360_membership_options', array(
    'enable_memberships' => true,
    'login_required' => true,
    'locked_message' => '',
    'pricing_page_url' => 'https://example.com/pricing'
));
```

**Tests:**

1. **Logged-out user visits activity feed**
   - Expected: Login gate
   - Message: "Please log in to access this content"
   - Button: "Log In" → links to login page

2. **Logged-out user visits gated video**
   - Expected: Login gate
   - Same UI as activity feed

3. **Logged-in non-member visits activity feed**
   - Expected: Upgrade gate
   - Message: "This content requires an active membership"
   - Button: "View Plans" → links to pricing page

4. **Logged-in non-member searches members (AJAX)**
   - Expected: 5 results + upgrade gate at bottom
   - Same gate UI as template

5. **Active member visits activity feed**
   - Expected: Full feed (no gate)

---

### Test Scenario 2: Login Required = False

**Setup:**
```php
update_option('vh360_membership_options', array(
    'enable_memberships' => true,
    'login_required' => false,
    'locked_message' => '',
    'pricing_page_url' => 'https://example.com/pricing'
));
```

**Tests:**

1. **Logged-out user visits activity feed**
   - Expected: Upgrade gate (NOT login gate)
   - Message: "This content requires an active membership"
   - Button: "View Plans"

2. **Logged-out user visits gated video**
   - Expected: Upgrade gate
   - Can see pricing immediately without logging in

3. **Logged-in non-member visits activity feed**
   - Expected: Upgrade gate (unchanged)

---

### Test Scenario 3: Custom Message

**Setup:**
```php
update_option('vh360_membership_options', array(
    'enable_memberships' => true,
    'login_required' => true,
    'locked_message' => '<strong>Join our premium community</strong> to access exclusive content!',
    'pricing_page_url' => 'https://example.com/pricing'
));
```

**Tests:**

1. **Non-member visits activity feed**
   - Expected: Upgrade gate with custom message
   - Message: "Join our premium community to access exclusive content!"
   - HTML formatting preserved (strong tag works)

2. **Non-member visits members directory**
   - Expected: Same custom message
   - Previously was hardcoded, now uses setting

3. **Non-member searches members (AJAX)**
   - Expected: Same custom message in AJAX response
   - Previously was hardcoded, now uses setting

4. **Non-member visits gated video**
   - Expected: Same custom message
   - Consistent everywhere

---

### Test Scenario 4: No Pricing URL

**Setup:**
```php
update_option('vh360_membership_options', array(
    'enable_memberships' => true,
    'login_required' => true,
    'locked_message' => '',
    'pricing_page_url' => ''  // Empty
));
```

**Tests:**

1. **Non-member visits activity feed**
   - Expected: Upgrade gate WITHOUT button
   - Message shown
   - No "View Plans" button
   - Gate still renders correctly

---

## Migration Notes

### For Existing Implementations

If you have custom templates or plugins that render membership gates:

#### ❌ Don't Do This Anymore

```php
// Old pattern - manual gate HTML
<div class="vh360-membership-gate">
    <h3>Members Only</h3>
    <p>Please upgrade to access this feature.</p>
    <a href="...">Upgrade</a>
</div>

// Old pattern - reflection
$frontend = VH360_Membership_Frontend::get_instance();
$reflection = new ReflectionClass($frontend);
$method = $reflection->getMethod('render_upgrade_required_notice');
$method->setAccessible(true);
echo $method->invoke($frontend, $plan);
```

#### ✅ Do This Instead

```php
// New pattern - centralized function
echo vh360_render_membership_gate(array(
    'required_plan' => 'pro'  // Optional
));
```

### Benefits of Migration

1. **Automatic settings compliance** - `login_required` and `locked_message` respected
2. **Consistent UI** - Same look and feel everywhere
3. **Maintainability** - One place to update
4. **Future-proof** - Changes propagate automatically

---

## Developer Guidelines

### Adding New Gated Features

When adding a new membership-gated feature to the platform:

#### Step 1: Check Access

```php
$has_access = vh360_can_access_membership_feature('your_feature', get_current_user_id());
```

#### Step 2: Render Gate if No Access

```php
if (!$has_access) {
    echo vh360_render_membership_gate();
    return; // or exit;
}

// Render your feature content
```

#### Step 3: Define Required Plans (Optional)

```php
add_filter('vh360_feature_your_feature_required_plans', function() {
    return array('any'); // Any active membership
    // OR
    return array('pro_monthly', 'pro_yearly'); // Specific plans
    // OR
    return array(); // No restriction (disable feature gating)
});
```

### Template Integration

**In page templates:**
```php
<?php
// Check access
if (!vh360_can_access_membership_feature('your_feature', get_current_user_id())) {
    ?>
    <div class="your-container-class">
        <?php echo vh360_render_membership_gate(); ?>
    </div>
    <?php
    get_footer();
    exit;
}
?>

<!-- Your feature content here -->
```

**In AJAX handlers:**
```php
// Check access
$has_access = vh360_can_access_membership_feature('your_feature', get_current_user_id());

if (!$has_access) {
    ob_start();
    echo vh360_render_membership_gate();
    $gate_html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $gate_html,
        'restricted' => true
    ));
}

// Return your feature data
```

### NEVER Do These Things

❌ **Don't create custom gate HTML**
```php
// Bad
echo '<div class="custom-gate">Please upgrade</div>';
```

❌ **Don't use reflection**
```php
// Bad
$reflection = new ReflectionClass($frontend);
```

❌ **Don't manually check login_required**
```php
// Bad - function handles this
if (!is_user_logged_in() && $login_required) {
    // manual logic
}
```

❌ **Don't hardcode messages**
```php
// Bad
echo '<p>Upgrade to access this feature</p>';
```

✅ **Always use the centralized function**
```php
// Good
echo vh360_render_membership_gate();
```

---

## Troubleshooting

### Gate Not Appearing

**Check:**
1. Is `enable_memberships` set to `true` in settings?
2. Is the feature registered with required plans?
3. Is `vh360_can_access_membership_feature()` returning `false`?

**Debug:**
```php
$options = get_option('vh360_membership_options');
var_dump($options['enable_memberships']); // Should be true

$has_access = vh360_can_access_membership_feature('activity_feed', get_current_user_id());
var_dump($has_access); // Should be false for non-members
```

### Wrong Gate Type Showing

**Issue:** Seeing upgrade gate when expecting login gate (or vice versa)

**Check:**
1. User's login status: `get_current_user_id()`
2. `login_required` setting value
3. Review decision matrix in this document

**Debug:**
```php
$user_id = get_current_user_id();
var_dump($user_id); // 0 = logged out, >0 = logged in

$options = get_option('vh360_membership_options');
var_dump($options['login_required']); // true or false
```

### Custom Message Not Appearing

**Check:**
1. Is `locked_message` set in settings?
2. Is it only on upgrade gates? (Login gate doesn't use custom message)

**Debug:**
```php
$options = get_option('vh360_membership_options');
var_dump($options['locked_message']);
```

### Styling Issues

**Issue:** Gate looks different than expected

**Check:**
1. CSS file loaded: `vh360-memberships/assets/css/memberships.css`
2. Theme CSS conflicts

**Debug:**
```php
// In browser console
document.querySelector('.vh360-membership-gate');
// Check computed styles
```

---

## Future Enhancements

### Possible Additions

1. **Context-Aware Messages**
   - Different messages per feature
   - `vh360_render_membership_gate(array('message' => 'Custom for this feature'))`

2. **Gate Templates**
   - Allow themes to override gate template
   - `get_template_part('membership/gate')`

3. **A/B Testing Support**
   - Test different messages/CTAs
   - Track conversion rates

4. **Analytics Integration**
   - Track gate impressions
   - Track click-through rates

5. **Multi-Tier Messaging**
   - Different messages for different plan levels
   - "Upgrade to Pro" vs "Upgrade to Enterprise"

### Extensibility

**Custom Gate Filters:**
```php
// Future enhancement
add_filter('vh360_membership_gate_html', function($html, $context) {
    // Modify gate HTML before rendering
    return $html;
}, 10, 2);
```

**Custom Button Text:**
```php
// Future enhancement
add_filter('vh360_membership_gate_button_text', function($text, $gate_type) {
    if ($gate_type === 'login') {
        return 'Sign In Now';
    }
    return 'Get Access';
}, 10, 2);
```

---

## Related Documentation

- **`MEMBERSHIP-SYSTEM-IMPLEMENTATION.md`** - Overall membership architecture
- **`MEMBERSHIP-CRITICAL-FIXES.md`** - Template-level access enforcement
- **`MEMBERSHIP-AJAX-FIX.md`** - AJAX handler enforcement
- **`MEMBERSHIP-LOGIC-FIXES.md`** - Logic consistency fixes

---

## Summary

✅ **Centralized rendering** - One function, one pattern  
✅ **Settings compliance** - `login_required` and `locked_message` everywhere  
✅ **Code quality** - No reflection, no duplication  
✅ **Consistency** - Same UI/behavior across all surfaces  
✅ **Maintainability** - Single point of change  
✅ **Production-ready** - Fully tested and documented

**Key Function:**
```php
vh360_render_membership_gate($context = array())
```

**Use it everywhere.** ✨
