# Profile System Setup Guide

This theme includes a complete profile system that works seamlessly with existing WordPress users through enhanced author archives.

## Automatic Features

The profile system is automatically enabled and requires no configuration. Every WordPress user immediately has:

- **Profile Page**: Accessible at `yoursite.com/author/username`
- **User Stats**: Video count, total views, subscriber count
- **Video Grid**: All published videos with pagination and sorting
- **Bio Section**: User description from WordPress profile
- **Social Links**: Optional social media links

## Setting Up Profile Editing (Optional)

To enable frontend profile editing:

### 1. Create an Edit Profile Page

1. Go to **Pages > Add New** in WordPress admin
2. Enter a title like "Edit Profile"
3. In the **Page Attributes** section on the right, select **Template: Profile Edit**
4. Publish the page

That's it! The "Edit Profile" button on user profiles will now link to this page.

### 2. Add to Navigation (Optional)

For logged-in users, you can add a "View Profile" link to your navigation:

```php
// In your navigation or header template
if (is_user_logged_in()) {
    $current_user_id = get_current_user_id();
    $profile_url = get_author_posts_url($current_user_id);
    echo '<a href="' . esc_url($profile_url) . '">View Profile</a>';
}
```

## User Profile Fields

### Standard WordPress Fields

These fields are managed through **Users > Your Profile** or the frontend edit page:

- Display Name
- Bio/Description
- Email
- Website

### Custom Profile Fields

Additional fields stored as user meta:

- **Cover Image**: `_vh360_cover_image` (attachment ID or URL)
- **Twitter**: `_vh360_twitter` (URL)
- **Facebook**: `_vh360_facebook` (URL)
- **YouTube**: `_vh360_youtube` (URL)
- **Instagram**: `_vh360_instagram` (URL)
- **Custom Avatar**: `_vh360_custom_avatar` (attachment ID or URL)
- **Subscriber Count**: `_vh360_subscriber_count` (integer)

### Setting Custom Fields Programmatically

```php
// Set cover image (attachment ID)
update_user_meta($user_id, '_vh360_cover_image', $attachment_id);

// Set social links
update_user_meta($user_id, '_vh360_twitter', 'https://twitter.com/username');
update_user_meta($user_id, '_vh360_facebook', 'https://facebook.com/username');

// Set subscriber count
update_user_meta($user_id, '_vh360_subscriber_count', 1500);
```

## Helper Functions

The theme provides helper functions for accessing profile data:

```php
// Get user avatar URL
$avatar_url = vh360_get_user_avatar_url($user_id, 150);

// Get cover image
$cover_image = vh360_get_user_cover_image($user_id);

// Get user bio
$bio = vh360_get_user_bio($user_id);

// Get join date
$join_date = vh360_get_user_join_date($user_id, 'F Y'); // "November 2023"
$relative = vh360_get_user_join_date($user_id, 'relative'); // "Joined 3 months ago"

// Get video count
$video_count = vh360_get_user_video_count($user_id);

// Get social links
$social_links = vh360_get_user_social_links($user_id);
// Returns: ['twitter' => 'url', 'facebook' => 'url', ...]

// Get all user stats
$stats = vh360_get_user_stats($user_id);
// Returns: ['videos' => 10, 'views' => 5000, 'subscribers' => 100, 'likes' => 50]
```

## Customization

### Styling

Profile styles are in `assets/css/profiles.css` and use CSS custom properties:

**Note:** Some template parts include inline `<style>` blocks for component-specific styles. This is intentional for component encapsulation and ensures styles are loaded only when the component is used. These styles are small and don't significantly impact performance.

```css
:root {
    --primary-color: #2563eb;
    --text-color: #1f2937;
    --bg-color: #ffffff;
    --border-radius: 8px;
    /* ... more variables ... */
}
```

Override these in your child theme or custom CSS.

### Template Parts

You can override profile template parts in a child theme:

- `template-parts/profile/header.php` - Profile header
- `template-parts/profile/stats.php` - Statistics display
- `template-parts/profile/bio.php` - Bio section
- `template-parts/profile/videos.php` - Video grid

### Hooks and Filters

Add custom functionality:

```php
// Add custom content to profile header
add_action('vh360_profile_header_after', 'my_custom_profile_content');
function my_custom_profile_content() {
    echo '<div class="custom-content">Custom content here</div>';
}
```

## Security Notes

- ✅ All output is escaped with `esc_html()`, `esc_url()`, `esc_attr()`
- ✅ All input is sanitized appropriately
- ✅ Nonce verification on form submissions
- ✅ User capability checks for editing
- ✅ File upload validation (type and size)
- ✅ Maximum file size: 5MB
- ✅ Allowed file types: JPG, PNG, GIF

## Permissions

- **View Profiles**: Anyone can view any user's profile
- **Edit Profile**: Users can only edit their own profile
- **Admin Override**: Administrators can edit any user's profile

Check edit permission programmatically:

```php
$can_edit = vh360_user_can_edit_profile($user_id);
```

## Troubleshooting

### Profile page not showing

1. Make sure permalinks are enabled (Settings > Permalinks)
2. Save permalinks again (this flushes rewrite rules)
3. Verify user exists and has published content

### Edit profile button not working

1. Create a page using the "Profile Edit" template
2. Or it will default to wp-admin profile page

### Cover image not uploading

1. Check file is JPG, PNG, or GIF
2. Check file size is under 5MB
3. Verify upload directory permissions
4. Check PHP upload_max_filesize setting

### Videos not showing

1. Make sure user has published videos (post type: 'videohub360' or 'post')
2. Check if Videohub360 plugin is active for video post type

## Support

For issues or questions, please refer to the theme documentation or contact support.
