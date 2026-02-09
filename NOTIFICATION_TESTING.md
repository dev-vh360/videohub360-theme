# Notification System Testing Guide

This guide explains how to test the Phase 1 notification system MVP implementation.

## Prerequisites

1. WordPress installation with the Videohub360 Theme active
2. At least 2 test user accounts (for testing follow and interaction features)
3. Community posts feature enabled
4. Browser with developer tools for debugging

## Testing Checklist

### 1. Database Table Creation

**Test:** Activate the theme and verify the database table is created.

**Steps:**
1. Activate the Videohub360 Theme (or re-activate if already active)
2. Check the database for the `wp_vh360_notifications` table
3. Verify the table has the correct structure:
   - `id` (bigint, auto_increment, primary key)
   - `user_id` (bigint)
   - `actor_id` (bigint)
   - `type` (varchar(50))
   - `object_id` (bigint)
   - `object_type` (varchar(50))
   - `content` (text)
   - `is_read` (tinyint)
   - `created_at` (datetime)
   - `read_at` (datetime, nullable)

**SQL Query to verify:**
```sql
DESCRIBE wp_vh360_notifications;
SHOW INDEX FROM wp_vh360_notifications;
```

**Expected Result:** Table exists with proper structure and indexes on `user_id`, `is_read`, and `created_at`.

---

### 2. Notification Bell Display

**Test:** Bell icon appears in header for logged-in users.

**Steps:**
1. Log in to the site as any user
2. Navigate to any page
3. Look for the notification bell icon in the header (next to the user menu)

**Expected Result:** 
- Bell icon is visible in the header
- Bell is positioned before the user menu
- Bell is only visible when logged in
- Hovering shows a hover effect

---

### 3. Follow Notification

**Test:** User receives notification when followed by another user.

**Steps:**
1. Log in as User A
2. Note User B's profile URL
3. Open User B's profile
4. Click the "Follow" button
5. Log out and log in as User B
6. Check the notification bell

**Expected Result:**
- User B should see an unread count badge on the bell (showing "1")
- Clicking the bell should show a notification: "User A started following you"
- Clicking the notification should take User B to User A's profile

---

### 4. Post Like Notification

**Test:** Post author receives notification when their post is liked.

**Steps:**
1. Log in as User A and create a community post
2. Note the post URL
3. Log out and log in as User B
4. Navigate to User A's post
5. Click the "Like" button (heart icon)
6. Log out and log in as User A
7. Check the notification bell

**Expected Result:**
- User A should see an unread count badge on the bell
- Notification should say: "User B liked your post"
- Clicking the notification should take User A to the post

---

### 5. Post Comment Notification

**Test:** Post author receives notification when someone comments.

**Steps:**
1. Log in as User A and create a community post
2. Log out and log in as User B
3. Find User A's post and add a comment
4. Log out and log in as User A
5. Check the notification bell

**Expected Result:**
- User A should see an unread count badge on the bell
- Notification should say: "User B commented on your post"
- Clicking the notification should take User A to the post

---

### 6. Comment Reply Notification

**Test:** Commenter receives notification when someone replies to their comment.

**Steps:**
1. Log in as User A and create a community post
2. Log in as User B and comment on User A's post
3. Log in as User C and reply to User B's comment
4. Log out and log in as User B
5. Check the notification bell

**Expected Result:**
- User B should see an unread count badge on the bell
- Notification should say: "User C replied to your comment"
- Clicking the notification should take User B to the post

---

### 7. Mention Notification (in Post)

**Test:** User receives notification when mentioned in a post.

**Steps:**
1. Ensure User B's username is "userb" (or note the actual username)
2. Log in as User A
3. Create a new community post with content: "Hey @userb, check this out!"
4. Log out and log in as User B
5. Check the notification bell

**Expected Result:**
- User B should see an unread count badge on the bell
- Notification should say: "User A mentioned you in a post"
- Clicking the notification should take User B to the post

---

### 8. Mention Notification (in Comment)

**Test:** User receives notification when mentioned in a comment.

**Steps:**
1. Log in as User A and create a community post
2. Log in as User B
3. Add a comment on User A's post: "Hey @usera, great post!"
4. Log out and log in as User A
5. Check the notification bell

**Expected Result:**
- User A should see notification for both the comment AND the mention
- One notification should say: "User B mentioned you in a comment"
- Clicking the notification should take User A to the post

---

### 9. Notification Dropdown

**Test:** Clicking bell opens dropdown with notifications.

**Steps:**
1. Log in as a user with notifications
2. Click the notification bell icon
3. Observe the dropdown

**Expected Result:**
- Dropdown appears below the bell
- Shows up to 5 most recent notifications
- Each notification shows:
  - Actor's avatar
  - Notification message
  - Time ago (e.g., "5 minutes ago")
  - Unread indicator (blue dot) for unread notifications
- "Mark all as read" button appears if there are unread notifications
- "View all notifications" link at the bottom

---

### 10. Mark Single Notification as Read

**Test:** Clicking a notification marks it as read.

**Steps:**
1. Log in as a user with unread notifications
2. Click the notification bell
3. Note the unread count badge number
4. Click on one unread notification (blue background)

**Expected Result:**
- Redirected to the linked page
- Upon returning, the unread count decreases by 1
- The clicked notification no longer shows the blue dot
- The notification background is no longer highlighted

---

### 11. Mark All as Read

**Test:** "Mark all as read" button works correctly.

**Steps:**
1. Log in as a user with multiple unread notifications
2. Click the notification bell
3. Note the unread count badge number
4. Click "Mark all as read" button

**Expected Result:**
- Unread count badge disappears immediately
- All notifications in the dropdown lose their blue dot indicators
- All notification backgrounds change to read state (no highlight)
- "Mark all as read" button disappears

---

### 12. Empty State

**Test:** Empty state displays when no notifications exist.

**Steps:**
1. Create a new test user account (User D)
2. Log in as User D
3. Click the notification bell

**Expected Result:**
- Dropdown shows a bell icon (grayed out)
- Text displays: "No notifications yet"
- No "Mark all as read" button
- "View all notifications" link still appears

---

### 13. AJAX Polling

**Test:** Notification count updates automatically via polling.

**Steps:**
1. Log in as User A on one browser
2. Log in as User B on another browser (or incognito window)
3. As User B, follow User A or like User A's post
4. Watch User A's notification bell (do NOT click it)
5. Wait 30 seconds

**Expected Result:**
- Within 30 seconds, User A's notification bell badge appears
- Badge shows the new unread count
- No page refresh needed

---

### 14. Click Outside to Close

**Test:** Dropdown closes when clicking outside.

**Steps:**
1. Log in and click the notification bell to open dropdown
2. Click anywhere outside the bell/dropdown area

**Expected Result:**
- Dropdown closes immediately
- Bell aria-expanded attribute changes to "false"

---

### 15. Escape Key to Close

**Test:** Dropdown closes when pressing Escape key.

**Steps:**
1. Log in and click the notification bell to open dropdown
2. Press the Escape key

**Expected Result:**
- Dropdown closes immediately
- Focus returns to the bell button
- Bell aria-expanded attribute changes to "false"

---

### 16. Mobile Responsive

**Test:** Notification system works on mobile viewport.

**Steps:**
1. Open browser developer tools
2. Switch to mobile device emulation (e.g., iPhone 12)
3. Log in and test notification bell

**Expected Result:**
- Bell icon is appropriately sized for mobile
- Dropdown appears properly positioned (may be full-width on mobile)
- All interactions work with touch events
- Text and icons are readable

---

### 17. No Self-Notifications

**Test:** Users don't receive notifications for their own actions.

**Steps:**
1. Log in as User A
2. Like your own post
3. Comment on your own post
4. Check the notification bell

**Expected Result:**
- No new notifications appear
- Users cannot notify themselves

---

### 18. Duplicate Prevention

**Test:** Duplicate notifications are prevented.

**Steps:**
1. Log in as User B
2. Like User A's post
3. Unlike User A's post
4. Like User A's post again (within 24 hours)
5. Log in as User A and check notifications

**Expected Result:**
- Only ONE "like" notification from User B appears
- No duplicate notifications for the same action within 24 hours

---

### 19. Performance Check

**Test:** Notification queries perform well.

**Steps:**
1. Create 50+ notifications for a test user
2. Log in as that user
3. Open browser developer tools Network tab
4. Click the notification bell

**Expected Result:**
- AJAX request completes in under 500ms
- Only 5 notifications are loaded initially
- No console errors
- Page remains responsive

---

### 20. Browser Console Check

**Test:** No JavaScript errors in console.

**Steps:**
1. Open browser developer tools Console tab
2. Log in and interact with notifications
3. Monitor for any errors

**Expected Result:**
- No JavaScript errors appear
- No failed AJAX requests
- All requests return successful responses

---

## Common Issues and Solutions

### Issue: Notification bell doesn't appear
**Solution:** 
- Ensure you're logged in
- Clear browser cache
- Check that notifications.css is loaded (view page source)
- Verify functions.php includes the notification system

### Issue: Unread count doesn't update
**Solution:**
- Check browser console for AJAX errors
- Verify nonce is being generated correctly
- Check that AJAX endpoint URLs are correct
- Ensure polling interval is set (default 30 seconds)

### Issue: Notifications aren't created
**Solution:**
- Check database table exists
- Verify action hooks are firing (add temporary logging)
- Check PHP error logs for issues
- Ensure notification triggers are initialized

### Issue: Dropdown doesn't open
**Solution:**
- Check JavaScript console for errors
- Verify jQuery is loaded
- Ensure notifications.js is enqueued
- Check that CSS is not conflicting with display

---

## Manual Database Verification

To check notifications directly in the database:

```sql
-- View all notifications for a user
SELECT * FROM wp_vh360_notifications WHERE user_id = 1 ORDER BY created_at DESC;

-- View unread notifications
SELECT * FROM wp_vh360_notifications WHERE user_id = 1 AND is_read = 0;

-- Count notifications by type
SELECT type, COUNT(*) as count FROM wp_vh360_notifications GROUP BY type;

-- Recent notifications
SELECT * FROM wp_vh360_notifications WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY);
```

---

## Debugging Tips

1. **Enable WordPress Debug Mode:**
   Add to `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. **Check AJAX Responses:**
   - Open Network tab in browser developer tools
   - Filter by "XHR" requests
   - Look for `admin-ajax.php` calls
   - Check request payload and response

3. **Add Temporary Logging:**
   ```php
   error_log('Notification created: ' . print_r($notification_id, true));
   ```

4. **Check User Meta:**
   ```sql
   SELECT * FROM wp_usermeta WHERE meta_key = 'vh360_unread_notification_count';
   ```

---

## Success Criteria

All tests should pass with:
- ✅ Notifications created for all trigger events
- ✅ Bell icon displays correctly
- ✅ Unread count badge shows correct number
- ✅ Dropdown opens and displays notifications
- ✅ Mark as read functionality works
- ✅ AJAX polling updates count automatically
- ✅ Mobile responsive design works
- ✅ No JavaScript console errors
- ✅ No PHP errors in logs
- ✅ Performance is good (queries under 100ms)

---

## Next Steps (Phase 2)

Once Phase 1 testing is complete, the following enhancements are planned:
- Dashboard notifications tab for viewing all notifications
- Notification preferences/settings
- Email notifications (optional)
- Push notifications (optional)
- Notification grouping for similar actions
- More notification types (group invites, live stream starts, etc.)
