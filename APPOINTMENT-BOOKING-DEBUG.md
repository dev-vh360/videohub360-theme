# Appointment Booking System - Debugging Guide

## How It Works

### 1. **Professional Sets Availability**
- Go to Dashboard → Availability tab
- Set appointment duration (15min - 2hrs)
- Set buffer time between appointments (0-30min)
- Add time blocks for each day of the week
  - Example: Monday 09:00 - 17:00
  - Can have multiple blocks per day
  - Example: Monday 09:00-12:00 AND 14:00-17:00 (lunch break)
- Click "Save Settings"

**What happens behind the scenes:**
- JavaScript collects all time blocks
- Sends AJAX request to `vh360_save_availability_settings`
- Data saved to user meta:
  - `_vh360_availability_slot_minutes` (e.g., 30)
  - `_vh360_availability_buffer_minutes` (e.g., 0)
  - `_vh360_availability_weekly` (serialized array)

**Weekly array structure:**
```php
array(
    'mon' => array(
        array('start' => '09:00', 'end' => '17:00')
    ),
    'tue' => array(
        array('start' => '09:00', 'end' => '17:00')
    ),
    // ... etc
)
```

### 2. **Client Views Business Profile**
- Visit professional's author page (Business Mode)
- See "Book an Appointment" section
- Date picker defaults to today
- Slots load automatically

**What happens behind the scenes:**
- `business-booking.js` loads on page
- Auto-fetches slots for today via AJAX
- Calls `vh360_get_professional_slots` with:
  - `professional_id` - viewed author
  - `date` - selected date (Y-m-d format)
  - `nonce` - security token

### 3. **Server Generates Slots**
Function: `vh360_get_open_appointment_slots($professional_id, $range_start, $range_end)`

**Process:**
1. Get professional's availability settings from user meta
2. Loop through each day in date range
3. For each day:
   - Get day of week (mon, tue, wed, etc.)
   - Check if professional has time blocks for that day
   - For each time block:
     - Generate slots at `slot_minutes` intervals
     - Example: 09:00-17:00 with 30min slots = 16 slots
     - Skip slots in the past
     - Check for conflicts (blocks and bookings)
     - Add valid slots to array
4. Return all open slots

**Slot structure:**
```php
array(
    'datetime' => '2026-02-24 09:00:00',
    'start' => '09:00',
    'end' => '09:30',
    'date' => '2026-02-24',
)
```

### 4. **Slots Displayed**
- JavaScript receives slots array
- Groups slots by date
- Renders each date section with slots
- Shows "Book" button for each slot
- Logged-in users can book directly
- Non-logged-in users see "Login to Book"

### 5. **Client Books Slot**
- Click "Book" button
- AJAX calls `vh360_book_appointment_slot`
- Server:
  - Verifies slot is still available
  - Creates `vh360_event` post with `kind=availability`
  - Sets start/end date/time meta
  - Sets `max_attendees=1`
  - Adds client's RSVP automatically
  - Sends notification to professional
- Client redirected to appointment details page

## Common Issues & Solutions

### Issue: "No appointment slots available for this date"

**Possible causes:**

#### 1. **No availability set**
**Check:** Dashboard → Availability tab
- Are there time blocks for the days you're checking?
- Did you click "Save Settings"?

**Debug:** Open browser console on Business profile, look for:
```javascript
Slots AJAX response: {success: true, data: {...}}
```

Check `data.debug.weekly_summary` - should show "X blocks" for each day.

If all days show "0 blocks", availability wasn't saved.

#### 2. **Time format issue**
**Symptom:** Settings saved but no slots generated
**Check:** Console → `data.debug.weekly_summary` shows blocks but `slot_count: 0`

**Possible issue:** Time values not in correct format (should be HH:MM like "09:00", "17:00")

#### 3. **Timezone mismatch**
**Symptom:** Slots generated but filtered as "past"
**Check:** Your site timezone vs professional's timezone

The system uses `_vh360_availability_timezone` or falls back to `wp_timezone_string()`.

If your site is in UTC but professional is in EST, 9am EST might be 2pm UTC (past if you check in afternoon UTC).

#### 4. **All slots in the past**
The system filters out any slots where `$slot_start <= $now`.

If you set hours for 9am-5pm but it's already 6pm, no slots will show for today.

**Solution:** Check tomorrow's date or earlier hours.

#### 5. **Conflicts with blocks/bookings**
If professional has:
- Block events during that time
- Already booked appointments

Those slots won't appear.

**Debug:** Check for `vh360_event` posts with:
- `post_author` = professional ID
- `_vh360_event_kind` = 'block' or 'availability'
- Date/time overlapping requested slots

### Debug Checklist

1. **Open Business profile in browser**
2. **Open Developer Console (F12)**
3. **Look for AJAX response:**
```javascript
Slots AJAX response: {
  success: true,
  data: {
    slots: [...],  // Should have items if slots available
    debug: {
      slot_count: X,  // How many slots generated
      has_weekly_settings: true,  // Must be true
      slot_minutes: 30,
      weekly_summary: {
        mon: "1 blocks",  // Must not be "0 blocks" for days you want
        tue: "1 blocks",
        // etc
      }
    }
  }
}
```

4. **If `has_weekly_settings: false` or all days show "0 blocks":**
   - Go to Dashboard → Availability
   - Add time blocks
   - Click Save Settings
   - Refresh Business profile

5. **If `slot_count: 0` but weekly_summary shows blocks:**
   - Check timezone settings
   - Try selecting tomorrow's date
   - Check for block events

6. **If AJAX fails or returns error:**
   - Check browser console for error
   - Check WordPress debug.log
   - Verify nonce is working

### Testing Flow

**As Professional:**
1. Dashboard → Availability
2. Add Monday: 09:00 - 17:00
3. Set duration: 30 minutes
4. Save Settings
5. Visit your own Business profile
6. Should see "Manage Your Availability" link (not booking UI)

**As Client (or logged-out):**
1. Visit professional's Business profile
2. See date picker with today's date
3. See "Loading..." then slots grid
4. If professional set Mon 09:00-17:00 with 30min slots:
   - Should see 16 slots (09:00-09:30, 09:30-10:00, ... 16:30-17:00)
   - Only future slots shown
5. Click "Book" → redirected to login OR booking happens
6. After booking: slot disappears, notification sent

## File Reference

- **Availability UI:** `template-parts/dashboard/availability.php`
- **Slot generation:** `includes/availability-functions.php`
- **AJAX handlers:** `includes/class-vh360-availability-ajax.php`
- **Frontend JS:** `assets/js/business-booking.js`
- **Business header:** `template-parts/business/header.php`
- **Enqueuing:** `includes/enqueue-manager.php`

## Data Flow Summary

```
Professional Dashboard
    ↓ (Save Settings)
User Meta: _vh360_availability_weekly
    ↓ (Client visits profile)
AJAX: vh360_get_professional_slots
    ↓ (Server generates)
vh360_get_open_appointment_slots()
    ↓ (Returns slots)
JavaScript renders slots
    ↓ (Client clicks Book)
AJAX: vh360_book_appointment_slot
    ↓ (Server creates)
vh360_event post (kind=availability)
    ↓ (Success)
Notification + Redirect
```
