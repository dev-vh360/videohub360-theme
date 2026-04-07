# VideoHub360 Membership System Implementation

## Overview

This document describes the native membership system implemented for VideoHub360. The system provides first-party membership management with WooCommerce integration for payment processing, following the architecture patterns already established in the theme.

## Architecture

### Design Principles

1. **First-party implementation**: Memberships are a native VH360 feature, not a third-party plugin
2. **WooCommerce for payments only**: WooCommerce handles transactions; VH360 handles membership logic
3. **Separation of concerns**: Roles ≠ Memberships ≠ Account Types
4. **Centralized access control**: Single source of truth for membership checks
5. **Consistent with existing patterns**: Follows established theme architecture

### Key Components

```
bundled-plugins/videohub360-memberships/
├── videohub360-memberships.php         # Main plugin file
├── includes/
│   ├── class-vh360-membership-database.php      # Database tables
│   ├── class-vh360-membership-plans.php         # Plan registry & product mapping
│   ├── class-vh360-membership-api.php           # CRUD operations
│   ├── class-vh360-membership-woocommerce.php   # Order integration
│   ├── class-vh360-membership-cron.php          # Scheduled tasks
│   ├── class-vh360-membership-frontend.php      # UI components
│   ├── class-vh360-membership-content-gates.php # Meta boxes
│   └── membership-helpers.php                   # API functions
└── assets/
    └── css/
        └── memberships.css                      # Frontend styles
```

## Database Schema

### wp_vh360_memberships

Main membership records table:

```sql
CREATE TABLE wp_vh360_memberships (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    plan_key varchar(100) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'active',
    source_order_id bigint(20) DEFAULT NULL,
    starts_at datetime NOT NULL,
    expires_at datetime DEFAULT NULL,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY plan_key (plan_key),
    KEY status (status),
    KEY expires_at (expires_at)
);
```

### wp_vh360_membership_events

Audit log for membership state changes:

```sql
CREATE TABLE wp_vh360_membership_events (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    membership_id bigint(20) NOT NULL,
    event_type varchar(50) NOT NULL,
    event_data text DEFAULT NULL,
    actor_id bigint(20) DEFAULT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY membership_id (membership_id),
    KEY event_type (event_type)
);
```

## Setup Guide

### 1. Initial Configuration

1. Navigate to **VH360 Theme → Memberships** in WordPress admin
2. Configure settings:
   - Enable memberships
   - Set pricing page URL
   - Configure renewal reminder days
   - Set grace period (optional)

### 2. Create Membership Products

1. Go to **Products → Add New** in WooCommerce
2. Create your membership product (e.g., "Pro Monthly")
3. In the sidebar, find **VH360 Membership Mapping**
4. Configure:
   - **Membership Plan**: Select or create plan key
   - **Duration**: Set membership length (e.g., 30 days)
   - **Duration Unit**: days/months/years/lifetime
   - **Grant Type**: Grant new or extend existing

### 3. Gate Content

1. Edit any post, video, event, bulletin, or gallery
2. Find the **Membership Access** meta box in sidebar
3. Select required plan:
   - **No restriction**: Public content
   - **Any Active Membership**: Any paid member
   - **Specific Plan**: Only specific plan holders

## API Usage

### Helper Functions

All template code should use these centralized helpers:

```php
// Check if user has active membership
if (vh360_user_has_active_membership($user_id)) {
    // User has active membership
}

// Check specific plan
if (vh360_user_has_membership_plan($user_id, 'pro_monthly')) {
    // User has Pro Monthly plan
}

// Get membership status
$status = vh360_get_user_membership_status($user_id);
// Returns: 'active', 'expired', 'cancelled', or false

// Check feature access
if (vh360_can_access_membership_feature('premium_videos', $user_id)) {
    // User can access feature
}

// Get active membership
$membership = vh360_get_active_membership($user_id);
if ($membership) {
    echo $membership->plan_key;
    echo $membership->expires_at;
}

// Check if post requires membership
$required = vh360_post_requires_membership($post_id);
if ($required) {
    // Post is gated
}
```

### Extending with Filters

```php
// Add custom membership plans
add_filter('vh360_membership_plans', function($plans) {
    $plans['custom_plan'] = array(
        'label' => 'Custom Plan',
        'duration' => 180,
        'duration_unit' => 'days',
        'features' => array('feature1', 'feature2'),
    );
    return $plans;
});

// Gate custom features
add_filter('vh360_feature_premium_videos_required_plans', function($plans) {
    return array('pro_monthly', 'pro_yearly');
});

// Customize page-level membership requirements
add_filter('vh360_current_page_requires_membership', function($requires) {
    if (is_page('special-page')) {
        return true;
    }
    return $requires;
});
```

## Workflow

### Purchase Flow

1. User adds membership product to cart
2. User completes checkout in WooCommerce
3. Order status changes to "processing" or "completed"
4. `VH360_Membership_WooCommerce::process_order()` triggers
5. System checks for membership-mapped products
6. Membership is granted or extended via API
7. Event is logged in `wp_vh360_membership_events`
8. User gains access to gated content

### Expiration Flow

1. Daily cron job `vh360_membership_check_expirations` runs
2. System finds memberships where `expires_at <= NOW()`
3. Status changed from 'active' to 'expired'
4. Expiration event logged
5. User loses access to gated content

### Renewal Reminder Flow

1. Daily cron job `vh360_membership_send_renewal_reminders` runs
2. System finds memberships expiring in X days (configured in admin)
3. Action hook `vh360_send_membership_renewal_reminder` fires
4. Theme or plugin can hook this to send emails
5. Reminder marked as sent to prevent duplicates

## Content Gating

### Post-Level Gating

Content gating is applied via post meta `_vh360_membership_required`:

- Empty value = Public content
- `'any'` = Requires any active membership
- Plan key (e.g., `'pro_monthly'`) = Requires specific plan

The `VH360_Membership_Frontend` class filters `the_content` and replaces gated content with upgrade notices for non-members.

### Template-Level Gating

Use the `vh360_membership_access_gate()` function in `community-gate.php`:

```php
// In template files
if (!vh360_user_has_active_membership()) {
    // Show upgrade notice
    get_template_part('template-parts/membership/upgrade-required');
    return;
}
// Show premium content
```

## Admin Features

### Settings Page

Located at **VH360 Theme → Memberships**:

- Enable/disable system
- Set pricing page URL (for upgrade CTAs)
- Configure renewal reminders
- Set grace period
- View membership statistics

### Statistics Display

The admin page shows:

- Total memberships
- Active memberships
- Expired memberships

### Import/Export

Membership settings are included in theme options import/export:

- Export includes `vh360_membership_options`
- Import overwrites membership configuration
- **Note**: Live membership records are NOT exported

## Cron Jobs

### Scheduled Events

Two daily cron jobs are registered on plugin activation:

1. `vh360_membership_check_expirations` - Expires memberships
2. `vh360_membership_send_renewal_reminders` - Sends reminders

### Manual Triggers

For development/testing, you can manually trigger cron:

```php
do_action('vh360_membership_check_expirations');
do_action('vh360_membership_send_renewal_reminders');
```

## Frontend UI

### Locked Content Notice

When non-members view gated content, they see:

```html
<div class="vh360-membership-gate vh360-membership-upgrade-required">
    <div class="vh360-membership-gate-content">
        <svg class="vh360-membership-gate-icon">...</svg>
        <h3>Premium Content</h3>
        <p>This content requires an active membership to access.</p>
        <a href="[pricing_url]" class="vh360-membership-gate-button">View Plans</a>
    </div>
</div>
```

### Login Required Notice

When logged-out users view gated content:

```html
<div class="vh360-membership-gate vh360-membership-login-required">
    <div class="vh360-membership-gate-content">
        <svg class="vh360-membership-gate-icon">...</svg>
        <h3>Login Required</h3>
        <p>Please log in to access this content.</p>
        <a href="[login_url]" class="vh360-membership-gate-button">Log In</a>
    </div>
</div>
```

## Integration with Existing Systems

### Roles

Memberships are **completely separate** from WordPress roles:

- User roles define capabilities (what users can do)
- Memberships define access (what users can view)
- WooCommerce customer role is reset to site default (existing behavior)

### Account Types

Memberships are **separate** from VH360 account types:

- Account types: `creator`, `professional`, `organization`, `client`
- Memberships: Paid access plans
- A user can be a `professional` account type with a `basic_monthly` membership

### Permissions

Memberships work **alongside** existing permission helpers:

- `vh360_user_can_create_videos()` - Capability-based
- `vh360_user_has_membership_plan()` - Membership-based
- Both can be used together for complex access rules

## Limitations (Phase 1)

This initial implementation has intentional limitations:

1. **No recurring subscriptions**: One-time purchases only
2. **No Stripe webhook integration**: Manual renewal via repurchase
3. **No automatic renewal**: Users must manually renew
4. **Fixed-term plans only**: 30/90/365-day and lifetime plans

These limitations follow the problem statement recommendation to build a stable membership foundation before adding recurring billing infrastructure.

## Phase 2 Roadmap

Future enhancements (not included in this implementation):

1. **Stripe Subscription Integration**
   - Stripe customer ID storage
   - Subscription ID tracking
   - Webhook endpoint
   - Auto-renewal processing
   - Failed payment handling
   - Cancellation sync

2. **Enhanced Features**
   - Member-only dashboard tabs
   - Membership badges on profiles
   - Plan comparison table shortcode
   - Member directory filtering
   - Usage analytics

## Troubleshooting

### Memberships Not Being Granted

1. Check WooCommerce order status (must be "processing" or "completed")
2. Verify product has membership mapping set
3. Check order meta `_vh360_membership_processed`
4. Review order notes for membership grant confirmation

### Content Not Gated

1. Verify post meta `_vh360_membership_required` is set
2. Check membership settings are enabled
3. Ensure frontend class is initialized
4. Check `the_content` filter is running

### Cron Jobs Not Running

1. Verify WP-Cron is enabled (not disabled in wp-config.php)
2. Check scheduled events: `wp_get_scheduled_events()`
3. Manually trigger for testing (see Cron Jobs section above)

## Developer Notes

### Code Standards

This implementation follows:

- WordPress Coding Standards
- Existing VH360 theme patterns
- Singleton pattern for component classes
- Hook-based architecture
- Centralized helper functions

### Security

- All inputs sanitized via `sanitize_text_field()` or type-specific functions
- Nonce verification on all meta saves
- Capability checks (`manage_options`, `edit_post`) enforced
- SQL queries use `$wpdb->prepare()`
- URLs validated with `esc_url_raw()`

### Performance

- Transients not used (to avoid stale data for membership checks)
- Database queries optimized with proper indexes
- Cron jobs run daily (not on every page load)
- Meta boxes load only on relevant post types

## Support

For issues or questions about this implementation:

1. Check this documentation first
2. Review the problem statement for design rationale
3. Examine existing code patterns in the theme
4. Refer to inline code comments for implementation details

---

**Implementation Date**: April 2026  
**Architecture**: First-party bundled plugin  
**Integration**: WooCommerce payments, VH360 access control  
**Status**: Phase 1 Complete (Fixed-term memberships)
