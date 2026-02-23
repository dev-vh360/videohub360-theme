# Collapsible Booking Section - User Guide

## Overview

The Business profile booking section has been redesigned as a collapsible interface for a cleaner, less overwhelming user experience.

## What Changed

### Before
```
Business Profile Page
├── Header with Name, Type, etc.
├── Navigation Tabs
└── 📅 Book an Appointment [Always Visible]
    ├── Date Picker
    └── [All Available Time Slots] ← Takes up lots of space
        09:00 - 09:30 [Book]
        09:30 - 10:00 [Book]
        10:00 - 10:30 [Book]
        ... (16+ slots visible)
```

### After
```
Business Profile Page
├── Header with Name, Type, etc.
├── Navigation Tabs
└── [▶ 📅 Book an Appointment] ← Collapsed by default
    
    Click to expand ↓
    
    [▼ 📅 Book an Appointment]
    ├── Date Picker
    └── Available Time Slots (loaded on demand)
```

## How It Works

### For Clients Viewing Professional Profiles

**Step 1: Initial View**
- Profile loads with booking section collapsed
- Click "Book an Appointment" to expand

**Step 2: Expand to Book**
- Click the toggle button
- Section smoothly expands with animation
- Available time slots load automatically
- Select date and book your appointment

**Step 3: Collapse When Done**
- Click "Book an Appointment" again to collapse
- Clean profile view returns

### For Professionals Viewing Their Own Profile

The collapsible section works the same way, but shows "Manage Your Availability" link when expanded instead of the booking interface.

## Technical Implementation

### HTML Structure

```html
<div class="vh360-business-booking">
    <!-- Toggle Button -->
    <button type="button" 
            class="vh360-business-booking-toggle" 
            id="vh360-booking-toggle"
            aria-expanded="false"
            aria-controls="vh360-booking-content">
        <span class="vh360-booking-toggle-text">
            [Calendar Icon] Book an Appointment
        </span>
        <svg class="vh360-booking-toggle-icon">
            [Chevron Icon - Rotates on expand]
        </svg>
    </button>
    
    <!-- Collapsible Content -->
    <div class="vh360-business-booking-content" 
         id="vh360-booking-content" 
         style="display: none;">
        <!-- Date picker and slots here -->
    </div>
</div>
```

### JavaScript Logic

```javascript
// Initialize collapsible
initCollapsible: function() {
    // Click handler
    $('#vh360-booking-toggle').on('click', function() {
        const isExpanded = $(this).attr('aria-expanded') === 'true';
        
        if (isExpanded) {
            // Collapse
            $content.slideUp(300);
            $(this).attr('aria-expanded', 'false');
        } else {
            // Expand
            $content.slideDown(300);
            $(this).attr('aria-expanded', 'true');
            
            // Lazy-load slots on first expand
            loadSlotsIfNeeded();
        }
    });
}
```

### CSS Styling

```css
/* Toggle Button */
.vh360-business-booking-toggle {
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.vh360-business-booking-toggle:hover {
    background-color: #f9fafb;
}

/* Chevron Animation */
.vh360-booking-toggle-icon {
    transition: transform 0.3s ease;
}

.vh360-business-booking-toggle[aria-expanded="true"] .vh360-booking-toggle-icon {
    transform: rotate(180deg);
}

/* Collapsible Content */
.vh360-business-booking-content {
    transition: all 0.3s ease;
}
```

## Features

### ✅ User Experience
- **Cleaner profiles**: No overwhelming wall of time slots
- **On-demand loading**: Slots load only when needed
- **Smooth animations**: Professional slide transitions
- **Visual feedback**: Chevron rotates, hover states clear

### ✅ Accessibility
- **ARIA attributes**: `aria-expanded`, `aria-controls`
- **Keyboard support**: Enter and Space keys work
- **Focus indicators**: Clear outline for keyboard navigation
- **Screen reader friendly**: Proper button semantics

### ✅ Performance
- **Lazy loading**: Slots fetch only on first expand
- **Reduced initial load**: Less data on page load
- **CSS transitions**: Smooth, hardware-accelerated

### ✅ Mobile Friendly
- **Responsive**: Works on all screen sizes
- **Touch-friendly**: Large tap targets
- **Optimized spacing**: Adjusted padding on mobile

## Customization

### Change Animation Speed

In `business-booking.js`:
```javascript
$content.slideDown(300); // Change 300 to your preferred ms
```

In `business.css`:
```css
.vh360-booking-toggle-icon {
    transition: transform 0.3s ease; /* Adjust timing */
}
```

### Keep Expanded by Default

In `business-booking.js`, change:
```javascript
// From:
$content.addClass('vh360-booking-collapsed').hide();

// To:
$content.addClass('vh360-booking-expanded').show();
$toggle.attr('aria-expanded', 'true');
```

### Different Toggle Icon

Replace the chevron SVG in `template-parts/business/header.php`:
```html
<!-- Current: Chevron down -->
<polyline points="6 9 12 15 18 9"></polyline>

<!-- Alternative: Plus/Minus -->
<!-- Use your preferred icon -->
```

## Browser Support

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers
- ✅ IE11 (with polyfills)

## Troubleshooting

### Section Won't Expand

**Check:**
1. jQuery is loaded
2. `business-booking.js` is enqueued
3. No JavaScript errors in console
4. Toggle button has correct ID

**Debug:**
```javascript
console.log('Toggle exists:', $('#vh360-booking-toggle').length);
console.log('Content exists:', $('#vh360-booking-content').length);
```

### Slots Don't Load When Expanded

**Check:**
1. AJAX endpoints working
2. User has availability set
3. Console for error messages

**Debug:**
```javascript
// In initCollapsible(), add:
console.log('Section expanded, loading slots...');
```

### Animation Feels Choppy

**Solutions:**
1. Reduce transition time: `300ms` → `200ms`
2. Use `ease-out` instead of `ease`
3. Disable transitions on slow devices

## Files Modified

1. **template-parts/business/header.php**
   - Changed `<h2>` to `<button>` toggle
   - Added collapsible content wrapper
   - Added ARIA attributes

2. **assets/js/business-booking.js**
   - Added `initCollapsible()` method
   - Toggle click handler
   - Keyboard support
   - Lazy-load logic

3. **assets/css/business.css**
   - Toggle button styles
   - Hover/focus states
   - Chevron rotation animation
   - Responsive mobile styles

## Best Practices

### For Theme Developers

1. **Don't remove ARIA attributes** - They're crucial for accessibility
2. **Test keyboard navigation** - Tab, Enter, Space should all work
3. **Keep animations subtle** - 300ms is good, don't go longer
4. **Maintain lazy-loading** - Don't auto-load slots on page load

### For Site Administrators

1. **Encourage professionals to set availability** - Empty section still works but isn't useful
2. **Test on mobile devices** - Ensure toggle is touch-friendly
3. **Monitor performance** - Lazy-loading should improve page speed

## Future Enhancements

Possible improvements for future versions:

1. **Remember state**: Use localStorage to remember if user expanded section
2. **Deep linking**: Open expanded if URL contains `#booking`
3. **Animation options**: Let users choose slide vs. fade
4. **Auto-expand on share**: Expand if visitor came from "book appointment" link

## Support

For issues or questions:
1. Check browser console for errors
2. Verify jQuery version (3.5+)
3. Test with browser dev tools
4. Review ARIA attributes in inspector

## Summary

The collapsible booking section provides:
- ✅ **Cleaner profiles** without overwhelming content
- ✅ **Better UX** with on-demand information
- ✅ **Full accessibility** with ARIA and keyboard support
- ✅ **Performance boost** with lazy-loading
- ✅ **Professional feel** with smooth animations

Users can focus on profile content first, then expand booking when ready!
