# YouTube-Style WordPress Comment System

## Overview

The VideoHub360 Theme includes a custom YouTube-style comment system for native WordPress comments that matches the existing activity/community feed UI. This provides a consistent, modern comment experience across all content types while preserving full WordPress comment functionality.

## Architecture

### Core Components

1. **Custom Walker Class** (`includes/comments/class-vh360-youtube-comment-walker.php`)
   - Extends `Walker_Comment`
   - Renders comments with YouTube-style markup
   - Bulk-loads like data to avoid N+1 queries
   - Supports nested replies with toggle buttons

2. **Custom Comment Form** (`includes/comments/comment-form-youtube-style.php`)
   - YouTube-style textarea with avatar
   - Matches activity feed styling
   - Full accessibility support
   - Responsive design

3. **Template Integration** (`comments.php`)
   - Uses custom walker and form
   - Maintains WordPress comment features
   - Includes pagination and navigation

4. **JavaScript Handler** (`assets/js/wp-comments-handler.js`)
   - AJAX like/unlike functionality
   - Reply toggle interactions
   - Kebab menu actions
   - Form handling with loading states

5. **Styling** (`assets/css/comments-youtube-style.css`)
   - Reuses activity feed classes
   - Responsive breakpoints
   - Dark mode support
   - Print styles

## Features

### User Experience
- **Facebook-style comment bubbles** with rounded corners
- **Like/Unlike buttons** with real-time count updates
- **Reply toggle buttons** for nested comment threads
- **Kebab menus** for edit/delete actions (permission-based)
- **Time ago** display (e.g., "5 minutes ago")
- **Empty states** for posts without comments
- **Loading indicators** during form submission

### Technical Features
- **Bulk data loading** - Fetches all like data in a single query
- **Optimistic UI updates** - Instant visual feedback
- **Proper escaping** - All user content safely sanitized
- **ARIA labels** - Full screen reader support
- **Keyboard navigation** - All interactive elements accessible
- **Nonce verification** - Security via `vh360_activity_nonce`
- **Error handling** - Graceful fallbacks for failed requests

### WordPress Compatibility
- ✅ Comment threading (nested replies)
- ✅ Comment pagination
- ✅ Comment moderation
- ✅ Awaiting moderation notices
- ✅ Comment editing (via admin)
- ✅ Comment deletion
- ✅ Anti-spam plugin support
- ✅ Gravatar support
- ✅ Logged-in and guest commenting

## DOM Structure

Comments use the same classes as the activity feed for consistency:

```html
<div class="vh360-comment-item" data-comment-id="123">
    <div class="vh360-comment-row">
        <!-- Avatar Column -->
        <div class="vh360-comment-avatar">
            <img src="..." alt="..." />
        </div>
        
        <!-- Content Column -->
        <div class="vh360-comment-main">
            <!-- Header (outside bubble) -->
            <div class="vh360-comment-header">
                <strong class="vh360-comment-author">John Doe</strong>
                <button class="vh360-kebab-toggle">...</button>
            </div>
            
            <!-- Comment Bubble -->
            <div class="vh360-comment-bubble">
                <div class="vh360-comment-text">
                    Comment text here...
                </div>
            </div>
            
            <!-- Actions (under bubble) -->
            <div class="vh360-comment-actions">
                <span class="vh360-comment-time">5 minutes ago</span>
                <button class="vh360-action-like">Like</button>
                <button class="vh360-action-reply">Reply</button>
                <span class="vh360-like-count">3 likes</span>
            </div>
        </div>
    </div>
    
    <!-- Nested Replies -->
    <div class="vh360-replies-list">
        <!-- Recursive structure -->
    </div>
</div>
```

## CSS Classes

### Container Classes
- `.vh360-comments-section` - Main comments container
- `.vh360-comments-thread` - Comments list wrapper
- `.vh360-comments-title` - Comment count heading

### Comment Structure Classes
- `.vh360-comment-item` - Individual comment wrapper
- `.vh360-comment-row` - Grid layout (avatar + content)
- `.vh360-comment-avatar` - Avatar container (40px top-level, 32px replies)
- `.vh360-comment-main` - Content column
- `.vh360-comment-header` - Name + kebab menu row
- `.vh360-comment-bubble` - Rounded comment box
- `.vh360-comment-text` - Comment content
- `.vh360-comment-actions` - Action buttons row

### Action Classes
- `.vh360-action-like` - Like button
- `.vh360-action-reply` - Reply button
- `.vh360-action-separator` - Bullet separator (•)
- `.vh360-like-count` - Like count display
- `.vh360-comment-time` - Time ago link

### Reply Classes
- `.vh360-comment-reply` - Additional class for nested replies
- `.vh360-comment-replies` - Replies container
- `.vh360-toggle-replies` - Toggle button
- `.vh360-replies-list` - Replies list
- `.vh360-replies-list--hidden` - Hidden state

### Menu Classes
- `.vh360-kebab-toggle` - Three-dot menu button
- `.vh360-kebab-dot` - Individual dots
- `.vh360-actions-menu` - Dropdown menu
- `.vh360-actions-menu--open` - Visible state
- `.vh360-actions-menu-item` - Menu item button

### Form Classes
- `.vh360-comment-form` - Form container with avatar
- `.vh360-comment-input-wrapper` - Textarea + button wrapper
- `.vh360-comment-textarea` - Comment input field
- `.vh360-comment-send-btn` - Submit button
- `.vh360-btn-loading` - Loading state class
- `.vh360-btn-spinner` - Loading spinner

### State Classes
- `.vh360-liked` - Active like state (blue color)
- `[aria-expanded="true"]` - Expanded replies toggle
- `.comment-awaiting-moderation` - Pending moderation state

## JavaScript API

### Initialization
The comment handler auto-initializes on pages with `.vh360-comments-section`.

### Events
All events are delegated to the document for dynamic content:

```javascript
// Like button
$('.vh360-wp-comment-like').click()

// Reply button  
$('.vh360-wp-comment-reply').click()

// Toggle replies
$('.vh360-wp-toggle-replies').click()

// Kebab menu
$('.vh360-kebab-toggle').click()

// Menu actions
$('.vh360-wp-comment-edit-btn').click()
$('.vh360-wp-comment-delete-btn').click()
```

### AJAX Endpoints

**Comment Like/Unlike:**
```javascript
{
    action: 'vh360_toggle_comment_like',
    comment_id: 123,
    nonce: vh360CommentsData.activityNonce
}
```

**Response:**
```javascript
{
    success: true,
    data: {
        liked: true,
        count: 5
    }
}
```

## Localized Data

JavaScript receives the following data via `vh360CommentsData`:

```javascript
{
    ajaxUrl: '/wp-admin/admin-ajax.php',
    activityNonce: 'abc123...',
    adminUrl: '/wp-admin/',
    isUserLoggedIn: true,
    i18n: {
        likeError: 'Unable to like comment. Please try again.',
        deleteConfirm: 'Are you sure you want to delete this comment?'
    }
}
```

## Usage Examples

### Display Comments (Automatic)
Simply use WordPress's standard comment template tag:

```php
<?php comments_template(); ?>
```

The custom walker and form are automatically loaded when comments are displayed.

### Custom Comment List
To manually output comments with the YouTube walker:

```php
<?php
wp_list_comments(vh360_get_youtube_comment_list_args());
?>
```

### Custom Comment Form
To manually output the YouTube-style form:

```php
<?php
vh360_youtube_style_comment_form();
?>
```

## Performance Optimizations

1. **Bulk Loading** - All like data fetched in a single query
2. **Conditional Enqueue** - CSS/JS only loaded when needed
3. **Optimistic Updates** - Instant UI feedback, confirmed via AJAX
4. **Delegated Events** - Single event listener for all comments
5. **CSS Reuse** - Shares styles with activity feed (no duplication)

## Accessibility Features

- ✅ Semantic HTML5 structure
- ✅ ARIA labels on all interactive elements
- ✅ ARIA `role` attributes (menu, menuitem, list)
- ✅ ARIA `aria-expanded` for toggles
- ✅ ARIA `aria-pressed` for like buttons
- ✅ ARIA `aria-live` for dynamic count updates
- ✅ `aria-label` for icon-only buttons
- ✅ Keyboard navigation support
- ✅ Focus visible indicators
- ✅ Screen reader announcements

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- iOS Safari (latest)
- Chrome Android (latest)

## Integration with VideoHub360 Plugins

The comment system integrates with:

1. **videohub360-community** plugin
   - Shares `VH360_Comment_Likes` class
   - Uses same AJAX handler (`vh360_toggle_comment_like`)
   - Shares nonce (`vh360_activity_nonce`)

2. **videohub360-core** plugin
   - Uses debug logging helpers (`vh360Log`, `vh360Warn`)
   - Follows same security patterns

## Customization

### Override CSS
Theme CSS can be customized via child theme:

```css
/* child-theme/style.css */
.vh360-comment-bubble {
    background: #your-color;
    border-radius: 20px; /* More rounded */
}
```

### Filter Comment Form Args
Modify form arguments via WordPress filter:

```php
add_filter('comment_form_defaults', 'custom_comment_form_args');
function custom_comment_form_args($defaults) {
    $defaults['title_reply'] = 'Share your thoughts';
    return $defaults;
}
```

### Extend Walker
Create a custom walker that extends the YouTube walker:

```php
class My_Custom_Comment_Walker extends VH360_YouTube_Comment_Walker {
    protected function render_comment_content($comment, $depth, $args) {
        // Custom rendering logic
        return parent::render_comment_content($comment, $depth, $args);
    }
}
```

## Troubleshooting

### Likes Not Working
1. Ensure videohub360-community plugin is active
2. Check browser console for JavaScript errors
3. Verify nonce is being generated: `vh360CommentsData.activityNonce`
4. Check user is logged in (likes require authentication)

### Styles Not Matching
1. Clear browser cache and WordPress cache
2. Verify `comments-youtube-style.css` is enqueued
3. Check for CSS conflicts in browser DevTools
4. Ensure `activity-feed.css` is also loaded

### Comments Not Displaying
1. Verify comments are open on the post
2. Check comment moderation settings
3. Test with default WordPress theme
4. Review PHP error logs

## Future Enhancements

Potential additions (not yet implemented):

- [ ] Inline comment editing (currently redirects to admin)
- [ ] Real-time comment updates (WebSocket/polling)
- [ ] Comment reactions (beyond like/dislike)
- [ ] Markdown support in comments
- [ ] Comment attachments (images/videos)
- [ ] @mentions in comments
- [ ] Comment sorting options

## Credits

Developed by VideoHub360 Team  
License: GPL v2 or later  
Website: https://videohub360.com

## Support

For issues or questions:
- GitHub Issues: https://github.com/dev-vh360/Videohub360-Theme
- Documentation: https://videohub360.com/docs
