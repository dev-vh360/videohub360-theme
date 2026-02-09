# Comment Notification Fix

## Issue Fixed

**Problem:** Users were not receiving notifications when someone commented on their posts.

**Root Cause:** The notification trigger class was only listening to the native WordPress `comment_post` hook, but the AJAX comment handler fires a custom `vh360_comment_created` hook. The notification system wasn't listening to this custom hook.

**Solution:** Added a dedicated handler `on_custom_comment_created()` that listens to the `vh360_comment_created` action and processes notifications for AJAX-submitted comments.

## What's Fixed

### ✅ Comment Notifications
- **Top-level comments**: Post author now receives notification when someone comments on their post
- **Reply comments**: Parent comment author receives notification when someone replies to their comment
- **Mentions**: All users mentioned in comments receive notifications

### ✅ Reply Button Functionality
The reply button was already working correctly:
- Click "Reply" button to show reply form below the comment
- Type reply in the textarea
- Click "Reply" submit button to post the reply
- Reply is added as a nested comment (indented)

## How It Works

### Comment Flow:

1. **User submits comment** via AJAX (using `community.js`)
2. **Backend handler** `vh360_ajax_add_activity_comment()` creates the comment
3. **Action fired** `do_action('vh360_comment_created', $comment_id, $post_id)`
4. **Notification trigger** `on_custom_comment_created()` catches the action
5. **Notification created**:
   - If top-level comment → notify post author
   - If reply → notify parent comment author
   - Check for @mentions → notify mentioned users

### Reply Flow:

1. **User clicks "Reply"** button on a comment
2. **JavaScript** toggles `vh360-reply-form-visible` class on the reply form
3. **Form becomes visible** (CSS changes `display: none` to `display: flex`)
4. **User types reply** in the textarea
5. **User submits** reply form
6. **AJAX handler** creates comment with `parent_id` set
7. **Notification sent** to parent comment author

## Testing the Fix

### Test 1: Comment Notification
1. **User A** creates a community post
2. **User B** logs in and comments on User A's post
3. **Expected:** User A receives a notification: "User B commented on your post"
4. **Verify:** Check User A's notification bell for unread count

### Test 2: Reply Notification
1. **User A** creates a post
2. **User B** comments on the post
3. **User C** clicks "Reply" on User B's comment
4. **Reply form appears** below User B's comment (indented)
5. **User C** types reply and submits
6. **Expected:** User B receives notification: "User C replied to your comment"
7. **Verify:** Check User B's notification bell

### Test 3: Mention Notification
1. **User A** comments on a post with text: "Hey @userb check this out"
2. **Expected:** User B receives notification: "User A mentioned you in a comment"
3. **Verify:** Check User B's notification bell

## Technical Details

### Files Modified

**`includes/notifications/class-vh360-notification-triggers.php`**
- Added `vh360_comment_created` hook listener in `init_hooks()`
- Created `on_custom_comment_created($comment_id, $post_id)` method
- Handles both top-level comments and replies
- Processes @mentions in comment content

### Code Structure

```php
// Hook registration
add_action('vh360_comment_created', array($this, 'on_custom_comment_created'), 10, 2);

// Handler method
public function on_custom_comment_created($comment_id, $post_id) {
    // Get comment data
    // Check if reply or top-level
    // Create appropriate notification
    // Check for mentions
}
```

### Integration Points

1. **AJAX Comment Handler** (`includes/community-posts.php` line 1268)
   ```php
   do_action('vh360_comment_created', $comment_id, $post_id);
   ```

2. **Native WordPress Comment Bridge** (`includes/community-posts.php` line 72)
   ```php
   do_action('vh360_comment_created', $comment_id, $comment->comment_post_ID);
   ```

3. **Notification Trigger** (`includes/notifications/class-vh360-notification-triggers.php`)
   ```php
   add_action('vh360_comment_created', array($this, 'on_custom_comment_created'), 10, 2);
   ```

## Verification Checklist

After deploying this fix:

- [ ] User receives notification when someone comments on their post
- [ ] User receives notification when someone replies to their comment
- [ ] Reply button shows/hides reply form correctly
- [ ] Reply submission creates nested comment
- [ ] Reply notification is sent to parent comment author
- [ ] Post author notification is sent for top-level comments
- [ ] Mentions in comments trigger notifications
- [ ] No duplicate notifications are created
- [ ] Notifications appear in bell dropdown
- [ ] Bell badge shows correct unread count

## Common Issues

### Reply button does nothing
- **Check:** Browser console for JavaScript errors
- **Verify:** `community.js` is properly enqueued
- **Solution:** Clear browser cache and reload

### Still not receiving notifications
- **Check:** Database has `wp_vh360_notifications` table
- **Verify:** Theme was activated (creates table)
- **Check:** User meta for `vh360_unread_notification_count`
- **Solution:** Re-activate theme or manually create table

### Notifications delayed
- **Expected:** AJAX polling updates every 30 seconds
- **Manual:** Refresh page or click notification bell to force update
- **Normal:** Some delay is expected with polling

## Browser Console Testing

Open browser console and test:

```javascript
// Check if notification polling is running
console.log('Polling interval:', vh360Notifications.pollInterval);

// Manually trigger notification count update
jQuery.post(vh360Notifications.ajaxUrl, {
    action: 'vh360_get_notification_count',
    nonce: vh360Notifications.nonce
}, function(response) {
    console.log('Notification count:', response);
});
```

## Database Verification

Check notifications are being created:

```sql
-- View recent notifications
SELECT * FROM wp_vh360_notifications 
ORDER BY created_at DESC 
LIMIT 10;

-- Count by type
SELECT type, COUNT(*) as count 
FROM wp_vh360_notifications 
GROUP BY type;

-- Check comment notifications specifically
SELECT * FROM wp_vh360_notifications 
WHERE type IN ('comment', 'reply') 
ORDER BY created_at DESC;
```

## Changelog

### Version 1.2.0 - December 2024
- Fixed comment notifications not triggering
- Added `on_custom_comment_created()` handler
- Connected `vh360_comment_created` action to notification system
- Verified reply button functionality (already working)
- Improved documentation with testing guide
