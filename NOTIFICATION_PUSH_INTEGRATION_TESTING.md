# Notification Push Integration Testing Guide

## Overview
This document provides testing procedures for the Theme Notification → PWA Push integration.

## Prerequisites
- WordPress installation with Videohub360 theme active
- VH360 PWA & App plugin active
- OneSignal or Native push configured
- At least 2 test users with push subscriptions

## Test Cases

### Test 1: Follow Notification → Push
**Objective:** Verify follow notifications trigger push to correct user

**Steps:**
1. User A follows User B
2. Verify User B receives in-app notification
3. Verify User B receives push notification on their device
4. Verify push message: "[User A name] started following you"
5. Verify push click opens User A's profile
6. Verify User A does NOT receive a push

**Expected Result:** ✅ User B receives targeted push, User A does not

---

### Test 2: Mention Notification → Push
**Objective:** Verify mention notifications trigger push

**Steps:**
1. User A mentions @UserB in a post or comment
2. Verify User B receives in-app notification
3. Verify User B receives push notification
4. Verify push message: "[User A name] mentioned you"
5. Verify push click opens the post with mention

**Expected Result:** ✅ User B receives targeted push with correct message

---

### Test 3: Reply Notification → Push
**Objective:** Verify reply notifications trigger push

**Steps:**
1. User B comments on a post
2. User A replies to User B's comment
3. Verify User B receives in-app notification
4. Verify User B receives push notification
5. Verify push message: "[User A name] replied to your comment"
6. Verify push click opens the post

**Expected Result:** ✅ User B receives targeted push

---

### Test 4: Direct Message → Push
**Objective:** Verify DM notifications trigger push

**Steps:**
1. User A sends a message to User B
2. Verify User B receives in-app notification
3. Verify User B receives push notification
4. Verify push message contains message preview
5. Verify push click opens messages page
6. Verify message preview is plain text (no HTML tags)

**Expected Result:** ✅ User B receives targeted push with message preview

---

### Test 5: HTML Stripping
**Objective:** Verify HTML tags are removed from push messages

**Steps:**
1. Create notification that would contain `<strong>` tags (e.g., follow)
2. Check push notification received
3. Verify no `<strong>`, `<b>`, or other HTML tags visible in push

**Expected Result:** ✅ Push shows plain text only

---

### Test 6: User Preferences - Disable Notification Type
**Objective:** Verify preference filter prevents both notification and push

**Steps:**
1. User B navigates to notification preferences
2. User B disables "follow" notifications
3. User A follows User B
4. Verify User B receives NO in-app notification
5. Verify User B receives NO push notification

**Expected Result:** ✅ No notification or push created when disabled

---

### Test 7: User Preferences - Message Type Available
**Objective:** Verify 'message' type appears in preferences UI

**Steps:**
1. Navigate to notification preferences page
2. Verify "Messages" or "Direct Messages" option visible
3. Toggle the setting
4. Verify setting saves correctly

**Expected Result:** ✅ Message type available in preferences

---

### Test 8: OneSignal External User ID
**Objective:** Verify logged-in users linked to OneSignal

**Steps:**
1. Open browser console (F12)
2. Log in as a user
3. Subscribe to push notifications
4. Look for console message: "[VH360 Push] OneSignal external user ID set: [user_id]"
5. In OneSignal dashboard, verify user appears with external user ID

**Expected Result:** ✅ User ID set and visible in console

---

### Test 9: Notification Types - Like & Comment (Disabled by Default)
**Objective:** Verify like/comment notifications do NOT trigger push

**Steps:**
1. User A likes User B's post
2. Verify User B receives in-app notification
3. Verify User B does NOT receive push notification
4. Repeat for comment

**Expected Result:** ✅ In-app notification only, no push (by design)

---

### Test 10: No Self-Notification
**Objective:** Verify users don't receive notifications for their own actions

**Steps:**
1. User A likes their own post
2. User A comments on their own post
3. Verify NO in-app notification created
4. Verify NO push sent

**Expected Result:** ✅ No notification or push for self-actions

---

### Test 11: Multiple Users - Targeted Push
**Objective:** Verify push only sent to intended recipient

**Steps:**
1. Have 3 users (A, B, C) all subscribed to push
2. User A follows User B
3. Verify ONLY User B receives push
4. Verify User A and User C do NOT receive push

**Expected Result:** ✅ Only User B receives the push

---

## Troubleshooting

### Push Not Received
1. Check push provider configured (OneSignal/Native)
2. Verify user has active push subscription
3. Check browser console for errors
4. Verify external user ID set (for OneSignal)
5. Check PHP error logs for bridge errors

### In-App Notification But No Push
1. Verify notification type is in allowlist (follow, mention, reply, message)
2. Check if push provider settings are correct
3. Verify bridge class initialized (check PHP logs)

### HTML Tags Visible in Push
1. Report as bug - should be stripped by `wp_strip_all_tags()`

## Debug Mode

To enable debug logging, add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check `/wp-content/debug.log` for bridge messages:
- "[VH360 Push Bridge] Push sent for notification #..."
- "[VH360 Push Bridge] Push failed for notification #..."

## Success Metrics

All 11 tests should pass:
- ✅ 4 notification types trigger push (follow, mention, reply, message)
- ✅ HTML stripped from all push messages
- ✅ User preferences control both in-app and push
- ✅ Only recipient receives push (not broadcast)
- ✅ OneSignal users linked by external ID
- ✅ No self-notifications
- ✅ Like/comment notifications do NOT trigger push (by design)

