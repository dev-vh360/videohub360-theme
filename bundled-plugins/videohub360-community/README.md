# VideoHub360 Community Plugin

A WordPress plugin that provides community engagement features for the VideoHub360 Theme.

## Overview

This plugin handles the data layer and business logic for community features including comment likes, share tracking, and activity engagement. It follows WordPress best practices by separating data/logic (plugin) from presentation (theme).

## Why a Separate Plugin?

Following WordPress architecture principles, this plugin separates concerns:

- **Plugin Responsibility:** Data storage, business logic, AJAX endpoints
- **Theme Responsibility:** HTML rendering, CSS styling, JavaScript UI interactions

### Benefits

1. **Data Persistence:** Community data persists even if the theme is changed
2. **Clean Architecture:** Clear separation between data and presentation layers
3. **Marketplace Ready:** Plugin can be distributed independently
4. **Reusability:** Can be used with multiple themes

## Features

### Comment Likes System

Allows users to like comments on posts. Uses a custom database table for efficient storage and retrieval.

**Database Table:** `wp_vh360_comment_likes`

**Schema:**
- `id` - Primary key
- `comment_id` - WordPress comment ID (indexed)
- `user_id` - WordPress user ID (indexed)
- `created_at` - Timestamp when like was created
- Unique constraint on (comment_id, user_id) prevents duplicate likes

**Helper Functions:**
- `VH360_Comment_Likes::get_count($comment_id)` - Get like count for a comment
- `VH360_Comment_Likes::user_has_liked($comment_id, $user_id)` - Check if user liked a comment
- `VH360_Comment_Likes::toggle($comment_id, $user_id)` - Toggle like status

### Share Tracking

Tracks the number of times posts are shared. Uses WordPress post meta for simple storage.

**Post Meta Key:** `vh360_share_count`

**Helper Functions:**
- `VH360_Post_Shares::get_count($post_id)` - Get share count for a post
- `VH360_Post_Shares::increment($post_id)` - Increment share count

## AJAX Endpoints

The plugin registers two AJAX actions for logged-in users:

### Toggle Comment Like
- **Action:** `vh360_toggle_comment_like`
- **Parameters:** 
  - `comment_id` (int) - Comment ID
  - `nonce` (string) - Security nonce (action: `vh360_comment_like`)
- **Response:** `{'success': true, 'data': {'liked': bool, 'count': int}}`

### Increment Share Count
- **Action:** `vh360_increment_share`
- **Parameters:**
  - `post_id` (int) - Post ID
  - `nonce` (string) - Security nonce (action: `vh360_share`)
- **Response:** `{'success': true, 'data': {'count': int}}`

## Theme Integration

The theme should:

1. **Use Helper Functions** to retrieve data:
   ```php
   $like_count = VH360_Comment_Likes::get_count($comment_id);
   $has_liked = VH360_Comment_Likes::user_has_liked($comment_id, $user_id);
   $share_count = VH360_Post_Shares::get_count($post_id);
   ```

2. **Create Nonces** for AJAX requests:
   ```php
   wp_localize_script('my-script', 'vh360Data', array(
       'commentLikeNonce' => wp_create_nonce('vh360_comment_like'),
       'shareNonce' => wp_create_nonce('vh360_share'),
   ));
   ```

3. **Call AJAX Endpoints** from JavaScript:
   ```javascript
   jQuery.post(ajaxurl, {
       action: 'vh360_toggle_comment_like',
       comment_id: commentId,
       nonce: vh360Data.commentLikeNonce
   });
   ```

## Installation

### Automatic (with Theme)

This plugin is bundled with the VideoHub360 Theme and will be automatically installed when the theme is activated.

### Manual Installation

1. Upload the `videohub360-community` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Database tables will be created automatically on activation

## Database Upgrades

The plugin includes a versioning system for database schema changes:

- Current version is stored in `vh360_community_db_version` option
- On `admin_init`, the plugin checks if an upgrade is needed
- `dbDelta()` is used for safe schema updates

## Uninstallation

When the plugin is deleted (not just deactivated), it:

1. Drops the `wp_vh360_comment_likes` table
2. Deletes all plugin options
3. Removes all share count post meta
4. Cleans up transients

**Note:** Deactivation does NOT delete data. Only uninstall (delete) removes data.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Version History

### 1.0.0 (Current)
- Initial release
- Comment likes system with database table
- Share tracking with post meta
- AJAX endpoints for frontend interaction
- Database upgrade/versioning system
- Proper activation and deactivation hooks

## Support

For issues and feature requests, please visit:
- Website: https://videohub360.com
- Repository: (if applicable)

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
