# Members Directory & Activity Feed

Complete guide for using and customizing the community features in Videohub360 Theme.

## Table of Contents

1. [Overview](#overview)
2. [Setup Instructions](#setup-instructions)
3. [Members Directory](#members-directory)
4. [Activity Feed](#activity-feed)
5. [Customization](#customization)
6. [Troubleshooting](#troubleshooting)
7. [Developer Reference](#developer-reference)

---

## Overview

The Videohub360 Theme includes two powerful community features:

### Members Directory
- Browse all site members with beautiful card layout
- Real-time AJAX search by username or display name
- Filter by user role and join date
- Sort by newest, alphabetical, or most videos
- Grid/List view toggle
- Responsive pagination

### Activity Feed
- Real-time community activity stream
- Track video uploads, new members, profile updates, and milestones
- Filter by activity type
- Infinite scroll with load more button
- Mobile-responsive timeline design

---

## Setup Instructions

### 1. Create Members Directory Page

1. In WordPress admin, go to **Pages → Add New**
2. Enter page title: "Members" (or your preferred title)
3. In the **Page Attributes** section on the right, select **Template: Members Directory**
4. Set a permalink slug: `/members/`
5. Click **Publish**

**Recommended Settings:**
- **Display:** Full Width (no sidebar)
- **Comments:** Disabled
- **Navigation:** Add to Primary Menu

### 2. Create Activity Feed Page

1. In WordPress admin, go to **Pages → Add New**
2. Enter page title: "Activity" (or your preferred title)
3. In the **Page Attributes** section, select **Template: Activity Feed**
4. Set a permalink slug: `/activity/`
5. Click **Publish**

**Recommended Settings:**
- **Display:** Full Width (no sidebar)
- **Comments:** Disabled
- **Navigation:** Add to Primary Menu

### 3. Configure Navigation

Add these pages to your site navigation:

```
Dashboard → Appearance → Menus
```

1. Select your Primary Menu
2. Add "Members" page
3. Add "Activity" page
4. Arrange in desired order
5. Save Menu

---

## Members Directory

### Features

#### Search
Real-time AJAX search filters members as you type:
- Searches display names
- Searches usernames
- Case-insensitive matching
- Instant results

#### Filters

**By Role:**
- All Roles (default)
- Subscriber
- Contributor
- Author
- Editor
- Administrator

**By Join Date:**
- All Time (default)
- Last 7 Days
- Last 30 Days
- Last Year

#### Sorting Options

- **Newest First** - Most recently registered members
- **Oldest First** - Original members first
- **A-Z** - Alphabetical by display name
- **Z-A** - Reverse alphabetical
- **Most Videos** - Members with most content

#### View Modes

**Grid View:**
- Card-based layout
- 3-4 columns (responsive)
- Best for browsing

**List View:**
- Single column layout
- More detailed information
- Better for scanning

*View preference is saved in browser localStorage*

#### URL Parameters

The directory supports deep linking with URL parameters:

```
/members/?search=john&role=author&joined=month&sort=display_name_asc&page=2
```

This allows:
- Shareable filtered views
- Bookmarkable searches
- Browser back/forward navigation

---

## Activity Feed

### Layout

The Activity Feed uses a clean **2-column layout**:

- **Main Feed (Left):** Composer, feed tabs (My Feed / Explore), and community posts
- **Right Sidebar:** Who to Follow, Trending Topics, and Ad Slot widgets. Right Sidebar Ad Slot is managed through Appearance → Widgets → Activity Feed Ad Slot. Use 300×250 or 300×600 creatives.

**Navigation:** The Community Menu (global left rail) provides persistent navigation across all pages, including profile links and site navigation.

**Responsive Behavior:**
- **Desktop (≥1024px):** Full 2-column layout with right sidebar
- **Tablet (768px-1024px):** 2-column layout with narrower right sidebar  
- **Mobile (<768px):** Single column (main feed only), sidebars hidden

### Activity Types

The feed tracks four types of community activities:

#### 1. Video Upload
Triggered when a user publishes a new video.

```
[User] uploaded a new video: [Video Title]
```

**Tracked on:**
- `publish_post` hook
- `publish_videohub360` hook

#### 2. New Member
Triggered when a new user registers.

```
[User] joined the community
```

**Tracked on:**
- `user_register` hook

#### 3. Profile Update
Triggered when a user updates their profile.

```
[User] updated their profile
```

**Tracked on:**
- `profile_update` hook
- Includes 1-hour cooldown to prevent duplicates

#### 4. Milestone
Triggered when a video reaches view milestones.

```
[Video Title] reached 1K views
```

**Supported Milestones:**
- 1,000 views (1K)
- 10,000 views (10K)
- 100,000 views (100K)
- 1,000,000 views (1M)

**Tracked via custom action:**
```php
do_action('vh360_video_milestone', $video_id, $user_id, '1000_views');
```

### Features

#### Filter Tabs
Click to filter activities by type:
- **All Activity** - Shows everything
- **Videos** - Only video uploads
- **Members** - Only new members
- **Milestones** - Only achievements

#### Infinite Scroll
Activities load automatically as you scroll down:
- Loads 20 activities at a time
- Smooth animations
- Load More button as fallback

#### Real-Time Updates
Activities appear immediately after they occur:
- No page refresh needed
- Chronological order (newest first)
- Human-readable timestamps ("2 hours ago")

---

## Customization

### Changing Colors

#### Members Directory Colors

Edit `assets/css/members-directory.css`:

```css
/* Header Gradient */
.vh360-members-header {
    background: linear-gradient(135deg, #YOUR-COLOR-1 0%, #YOUR-COLOR-2 100%);
}

/* Active Filter/Button Color */
.vh360-search-input:focus {
    border-color: #YOUR-PRIMARY-COLOR;
}
```

#### Activity Feed Colors

Edit `assets/css/activity-feed.css`:

```css
/* Header Gradient */
.vh360-activity-header {
    background: linear-gradient(135deg, #YOUR-COLOR-1 0%, #YOUR-COLOR-2 100%);
}

/* Active Tab */
.vh360-filter-tab.active {
    background: linear-gradient(135deg, #YOUR-COLOR-1 0%, #YOUR-COLOR-2 100%);
}
```

### Modifying Members Per Page

Edit `template-members-directory.php` (line ~158):

```php
$members = vh360_get_members(array(
    'number' => 12, // Change this number
    'orderby' => 'registered',
    'order' => 'DESC',
));
```

Also update JavaScript in `assets/js/members-directory.js` (line ~105):

```javascript
const per_page = 12; // Change this number
```

### Modifying Activities Per Load

Edit `template-activity-feed.php` (line ~126):

```php
$activities = vh360_get_activities(array(
    'type' => 'all',
    'limit' => 20, // Change this number
    'offset' => 0,
));
```

Also update JavaScript in `assets/js/activity-feed.js` (line ~34):

```javascript
offset: 20, // Change this number
```

### Custom Activity Types

Add custom activity types by extending the tracker:

**1. Add to valid types** in `includes/activity-tracker.php`:

```php
$valid_types = array(
    'video_upload', 
    'new_member', 
    'profile_update', 
    'milestone',
    'custom_type' // Your custom type
);
```

**2. Create custom tracking function:**

```php
function vh360_track_custom_activity($user_id, $data) {
    vh360_track_activity($user_id, 'custom_type', array(
        'title' => $data['title'],
        'link' => $data['link'],
        'meta' => $data['meta'],
    ));
}
add_action('your_custom_hook', 'vh360_track_custom_activity', 10, 2);
```

**3. Add icon** in `includes/helpers.php`:

```php
function vh360_get_activity_icon($type) {
    $icons = array(
        // ... existing icons
        'custom_type' => '<svg>...</svg>',
    );
    return isset($icons[$type]) ? $icons[$type] : '';
}
```

**4. Add display logic** in templates:

```php
case 'custom_type':
    echo '<p>' . esc_html__('Custom activity text', 'videohub360-theme') . '</p>';
    break;
```

### Activity Retention Period

Change how long activities are stored:

Edit `includes/activity-tracker.php` (line ~311):

```php
function vh360_cleanup_old_activities() {
    vh360_delete_old_activities(90); // Days to keep (default: 90)
}
```

### Maximum Stored Activities

Limit the number of activities stored:

Edit `includes/activity-tracker.php` (line ~53):

```php
// Keep only the most recent 100 activities
$activities = array_slice($activities, 0, 100); // Change this number
```

---

## Troubleshooting

### Members Not Showing

**Check 1: User Role**
Ensure users have proper roles assigned in WordPress.

**Check 2: Published Content**
Some filters require users to have published content.

**Check 3: Cache**
Clear browser cache and any WordPress caching plugins.

**Check 4: Template**
Verify the page is using the correct template:
```
Pages → Edit Page → Page Attributes → Template
```

### Activities Not Appearing

**Check 1: Activity Tracking**
Verify `activity-tracker.php` is loaded:

```php
// In functions.php
require_once VH360_THEME_DIR . '/includes/activity-tracker.php';
```

**Check 2: Hooks**
Ensure WordPress hooks are firing correctly.

**Check 3: Database**
Check if activities are being stored:

```php
// In WordPress admin, run this in a plugin:
$activities = get_option('vh360_activity_feed', array());
var_dump($activities);
```

**Check 4: Permissions**
Ensure the database can be written to.

### AJAX Not Working

**Check 1: jQuery**
Ensure jQuery is loaded (it's included with WordPress).

**Check 2: Nonce**
Verify nonces are being created correctly.

**Check 3: Console Errors**
Open browser Developer Tools (F12) and check Console for errors.

**Check 4: Admin AJAX URL**
Verify admin-ajax.php is accessible:
```
https://yoursite.com/wp-admin/admin-ajax.php
```

### Search Too Slow

**Solution 1: Reduce Members Per Page**
Lower the number from 12 to 6 or 8.

**Solution 2: Database Indexing**
Ensure WordPress database tables are properly indexed.

**Solution 3: Caching**
Implement object caching (Redis, Memcached).

### Mobile Layout Issues

**Check 1: Viewport Meta**
Ensure your header.php includes:
```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

**Check 2: CSS Loading**
Verify CSS files are being enqueued properly.

**Check 3: Theme Conflicts**
Test with a default WordPress theme to isolate issues.

---

## Developer Reference

### Functions

#### Members Directory Functions

```php
// Get members with filters
vh360_get_members(array $args);

// Get total member count
vh360_get_member_count(string $role = '');

// Check if user can edit profile
vh360_user_can_edit_profile(int $user_id = 0);

// Get user profile URL
vh360_get_profile_url(int $user_id = 0);

// Format numbers (1000 → 1K)
vh360_format_number(int $number);

// Get user statistics
vh360_get_user_stats(int $user_id = 0);

// Check if user is active
vh360_is_user_active(int $user_id);
```

#### Activity Feed Functions

```php
// Track new activity
vh360_track_activity(int $user_id, string $type, array $content);

// Get activities with filters
vh360_get_activities(array $args);

// Delete old activities
vh360_delete_old_activities(int $days = 90);

// Get activity count
vh360_get_activity_count(string $type = 'all');

// Format activity timestamp
vh360_format_activity_time(int $timestamp);

// Get activity type icon
vh360_get_activity_icon(string $type);
```

### Hooks

#### Actions

```php
// Track video upload
add_action('publish_post', 'your_function');
add_action('publish_videohub360', 'your_function');

// Track new member
add_action('user_register', 'your_function');

// Track profile update
add_action('profile_update', 'your_function');

// Track milestone
do_action('vh360_video_milestone', $video_id, $user_id, $milestone);
```

#### Filters

```php
// Modify member query args
add_filter('vh360_member_query_args', 'your_function');

// Modify activity query args
add_filter('vh360_activity_query_args', 'your_function');

// Modify activity output
add_filter('vh360_activity_content', 'your_function', 10, 2);
```

### AJAX Endpoints

#### Members Directory

**Action:** `vh360_search_members`

**Parameters:**
- `nonce` - Security nonce
- `search` - Search query
- `role` - User role filter
- `join_date` - Join date filter
- `orderby` - Sort field
- `order` - Sort direction
- `page` - Page number

**Response:**
```json
{
    "success": true,
    "data": {
        "html": "<div>...</div>",
        "page": 1,
        "total": 50,
        "max_pages": 5
    }
}
```

#### Activity Feed

**Action:** `vh360_load_activities`

**Parameters:**
- `nonce` - Security nonce
- `type` - Activity type filter
- `offset` - Pagination offset

**Response:**
```json
{
    "success": true,
    "data": {
        "html": "<div>...</div>",
        "offset": 20,
        "count": 20
    }
}
```

### Database Storage

Activities are stored in `wp_options` table:

**Option Name:** `vh360_activity_feed`

**Structure:**
```php
array(
    array(
        'id' => 'activity_xxxxx',
        'user_id' => 123,
        'type' => 'video_upload',
        'content' => array(
            'title' => 'Video Title',
            'link' => 'https://...',
            'meta' => 'Additional data'
        ),
        'timestamp' => 1234567890
    ),
    // ... more activities
)
```

**Caching:**
- Activities are cached using WordPress transients
- Cache key format: `vh360_activities_{md5(args)}`
- Cache duration: 5 minutes
- Cache is cleared when new activity is added

### Performance Tips

1. **Limit Activities:** Keep maximum stored activities at 100
2. **Enable Object Caching:** Use Redis or Memcached
3. **Optimize Images:** Use lazy loading for avatars
4. **CDN:** Serve static assets from CDN
5. **Cleanup:** Run cleanup regularly to remove old activities

### Security

All AJAX requests use:
- ✅ Nonce verification
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Capability checks
- ✅ SQL injection prevention (via WP_User_Query)

### Browser Support

- ✅ Chrome (latest 2 versions)
- ✅ Firefox (latest 2 versions)
- ✅ Safari (latest 2 versions)
- ✅ Edge (latest 2 versions)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Accessibility

- ✅ ARIA labels on interactive elements
- ✅ Keyboard navigation support
- ✅ Screen reader friendly
- ✅ Semantic HTML5 markup
- ✅ Focus indicators

---

## Support

For issues, questions, or feature requests:

1. Check this documentation first
2. Review the [Troubleshooting](#troubleshooting) section
3. Contact theme support

---

## Changelog

### Version 1.0.0
- Initial release
- Members Directory with search, filter, sort
- Activity Feed with infinite scroll
- Mobile-responsive design
- Full AJAX support
- Comprehensive documentation

---

**Last Updated:** November 2025
**Author:** Videohub360 Theme Team
