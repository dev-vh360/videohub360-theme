# Business Booking Panel Guide

## Overview

The Business Mode professional profile uses a modern appointment booking panel instead of the older collapsible booking accordion.

The booking interface is displayed in a dedicated sidebar panel on desktop and stacks with the profile content on smaller screens. This keeps professional profiles cleaner and prevents appointment slots from expanding inside the profile header.

The booking panel is rendered from:

```text
template-parts/business/booking-panel.php
```

The booking behavior is handled by:

```text
assets/js/business-booking.js
```

The booking styles are defined in:

```text
assets/css/business.css
```

## Current Profile Layout

The Business Mode professional profile is rendered by:

```text
author-business.php
```

The page uses a two-column layout:

```text
Business Professional Profile
├── Modern cover/profile header
├── Profile body
│   ├── Main content column
│   │   ├── Navigation tabs
│   │   └── Active tab content
│   └── Booking sidebar
│       └── Business Booking Panel
```

The booking panel is no longer part of the header.

## Header CTA Behavior

The professional profile header includes an owner-aware CTA.

For visitors or clients viewing a professional profile:

```text
Book Appointment
```

This links to:

```text
#vh360-business-booking-panel
```

For the professional viewing their own profile:

```text
Manage Availability
```

This links to the dashboard availability tab.

Expected behavior:

```text
Visitor/client → Book Appointment → scrolls to booking panel
Profile owner → Manage Availability → opens availability management
```

## Booking Panel Behavior

The booking panel has two states.

### Visitor/client state

Visitors and clients see:

```text
Book an Appointment
Choose a date and available time.
Select a date
Available time chips
```

When a date is selected, available times load dynamically through AJAX.

The interface displays only the selected date’s available times.

Appointment times are displayed as compact chips/buttons:

```text
[9:00] [9:30] [10:00] [10:30]
```

This replaces the old large multi-day appointment cards.

### Owner state

When a professional views their own profile, the panel does not show booking controls.

Instead, it shows an owner message and a link to manage availability:

```text
You are viewing your own profile.
Manage Your Availability
```

## Important Current Markup

The booking panel wrapper uses:

```html
<div class="vh360-business-booking-panel" id="vh360-business-booking-panel">
```

The date picker uses:

```html
<input type="date" id="vh360-booking-date-picker">
```

The slots container uses:

```html
<div id="vh360-booking-slots-container" class="vh360-booking-slots-container">
```

The JavaScript depends on these IDs.

Do not rename these IDs unless the JavaScript is updated at the same time.

## Current JavaScript Flow

The booking JavaScript should follow this flow:

```text
1. Initialize booking script
2. Bind date picker change event
3. Load available slots for the selected date
4. Filter returned slots to the selected date
5. Render compact time chips
6. Handle booking when a time chip is clicked
7. Remove booked chip after successful booking
8. Show a no-slots message if no times remain
```

The current JavaScript should not rely on an accordion toggle.

There should be no dependency on the retired accordion toggle IDs or accordion content classes.

## Selected-Date Slot Filtering

The backend may return slots for multiple dates. The frontend should display only the currently selected date.

The expected JavaScript behavior is:

```js
const visibleSlots = slots.filter(function(slot) {
    return slot.date === selectedDate;
});
```

This prevents the booking panel from showing several days of appointments at once.

## Time Chip Rendering

Appointment times should render as compact buttons or login links.

Logged-in users should see clickable booking buttons:

```html
<button class="vh360-book-slot-btn vh360-booking-time-chip">
    9:00
</button>
```

Logged-out users should see login links:

```html
<a class="vh360-book-slot-login vh360-booking-time-chip">
    9:00
</a>
```

The UI should not render large appointment cards such as:

```text
09:00 - 09:30
[Book]
```

The current desired UI is:

```text
Available times
[9:00] [9:30] [10:00] [10:30]
```

## Current CSS Responsibilities

The following selectors are part of the current booking panel implementation and should remain styled:

```text
.vh360-business-booking-panel
.vh360-business-booking-panel-header
.vh360-business-booking-owner
.vh360-business-booking-picker
.vh360-booking-date-picker-wrapper
.vh360-booking-date-input
.vh360-booking-messages
.vh360-booking-loading
.vh360-booking-slots-container
.vh360-booking-date-group
.vh360-booking-date-header
.vh360-booking-time-chips
.vh360-booking-time-chip
.vh360-book-slot-btn
.vh360-book-slot-login
.vh360-booking-no-slots
```

Retired accordion selectors and IDs should not exist in active CSS or JavaScript.

## Desktop Layout

On desktop, the Business professional profile uses a two-column layout:

```text
Main content column
Booking sidebar column
```

The booking panel should appear in the right column and may be sticky:

```text
position: sticky;
top: 96px;
```

This keeps booking visible without letting appointment slots consume the full page width.

## Mobile Layout

On smaller screens, the profile body should become one column.

Expected mobile behavior:

```text
Header stacks cleanly
Main content displays normally
Booking panel stacks with content
Booking panel is not sticky
Time chips wrap naturally
No horizontal overflow
```

## Accessibility Requirements

The new booking panel is not an accordion, so it does not need accordion-specific ARIA attributes such as:

```text
aria-expanded
aria-controls
```

The date picker should keep a visible label.

Time chips should be keyboard-accessible because they are rendered as buttons or links.

Booking messages should be readable and visible near the booking panel.

## Files Involved

Current implementation files:

```text
author-business.php
template-parts/business/header.php
template-parts/business/booking-panel.php
assets/js/business-booking.js
assets/css/business.css
```

Related Business Mode client profile files:

```text
author-client.php
template-parts/client/header.php
assets/css/client.css
```

The client profile does not use the booking panel.

## Testing Checklist

### Visitor/client viewing a professional profile

Verify:

```text
Header CTA says “Book Appointment”
CTA links to #vh360-business-booking-panel
Booking panel appears in the sidebar on desktop
Date picker appears
Selecting a date loads available time chips
Only selected date times appear
Time chips are compact
Booking a time works
Logged-out users are sent to login
No old accordion toggle appears
No JavaScript console errors
```

### Professional viewing own profile

Verify:

```text
Header CTA says “Manage Availability”
CTA links to the dashboard availability tab
Booking panel shows owner/manage availability state
Date picker is not shown
Time chips are not shown
No JavaScript console errors
```

### Mobile

Verify:

```text
Booking panel stacks correctly
Panel is not sticky
Time chips wrap cleanly
No horizontal scrolling
```

### Regression checks

Verify:

```text
Business Mode client profile does not show a booking panel
Course Mode profiles do not show the Business booking panel
Course Mode instructor and learner profiles still work normally
```

## Summary

The Business Booking Panel improves Business Mode professional profiles by:

```text
Removing the old header-embedded booking accordion
Keeping appointments out of the profile header
Displaying booking in a contained sidebar panel
Showing only the selected date’s available times
Rendering time slots as compact chips
Providing owner-aware “Manage Availability” behavior
Maintaining a cleaner, more modern profile layout
```

This document replaces the old collapsible booking guide and should be kept in sync with the current booking panel implementation.
