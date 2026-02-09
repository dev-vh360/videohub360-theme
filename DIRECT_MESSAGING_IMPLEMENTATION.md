# Direct Messaging System Implementation

## Overview

A complete 1-on-1 direct messaging system has been implemented for the Videohub360 theme, allowing users to send private messages to each other through an intuitive dashboard interface.

## Features

### Core Functionality
- **Private 1-on-1 Conversations**: Users can message each other privately
- **Real-time Updates**: Messages poll every 10 seconds for new content
- **Unread Tracking**: Visual indicators for unread messages
- **Conversation Management**: Search users, delete conversations
- **Rate Limiting**: Maximum 10 messages per minute per user
- **Character Limit**: Configurable (default: 1000 characters)

### User Interface
- **Dashboard Tab**: Dedicated "Messages" tab in user dashboard
- **Two-Column Layout**: Conversation list (30%) + Active conversation (70%)
- **Header Icon**: Message icon in site header with unread count badge
- **Profile Integration**: "Send Message" button on user profiles
- **Mobile Responsive**: Stacked layout on devices under 768px
- **Search Functionality**: Find users to start new conversations
- **Empty States**: Helpful messages when no conversations exist

### Security
- ✅ Nonce verification on all AJAX requests
- ✅ User authentication checks
- ✅ Content sanitization with `wp_kses_post()`
- ✅ Escape all output
- ✅ Rate limiting (10 messages/minute)
- ✅ Users can only access their own messages
- ✅ No XSS vulnerabilities (verified by CodeQL)

### Performance
- ✅ Database indexes on critical columns
- ✅ Conversation list cached for 1 minute
- ✅ Loads 50 messages per conversation with pagination support
- ✅ Uses JOINs to avoid N+1 queries
- ✅ Polling only when browser tab is active

## File Structure

### Backend Files
```
/includes/
├── direct-messages.php                      # Core functions
├── class-vh360-dm-ajax.php                  # AJAX handlers
├── class-vh360-dm-notifications.php         # Notification integration
└── admin/
    ├── class-vh360-theme-admin.php          # Admin menu & settings registration
    └── pages/
        └── messages.php                      # Admin settings page
```

### Frontend Files
```
/template-parts/
├── dashboard/
│   └── messages.php                         # Dashboard messages tab UI
├── profile/
│   └── header.php                           # Modified: Added "Message" button
└── components/
    └── message-icon.php                     # Header message icon

/template-dashboard.php                      # Modified: Added messages tab
```

### Assets
```
/assets/
├── js/
│   └── direct-messages.js                   # All client-side functionality
└── css/
    ├── direct-messages.css                  # Additional DM styles
    └── profiles.css                         # Modified: Added message button style
```

## Database Schema

### Table: `{prefix}_vh360_direct_messages`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| sender_id | bigint(20) | User ID of sender |
| recipient_id | bigint(20) | User ID of recipient |
| message_content | text | Message content (sanitized) |
| created_at | datetime | Timestamp of message |
| read_at | datetime | Timestamp when read (NULL if unread) |
| deleted_by_sender | tinyint(1) | Soft delete flag for sender |
| deleted_by_recipient | tinyint(1) | Soft delete flag for recipient |

**Indexes:**
- PRIMARY KEY (id)
- KEY sender_id (sender_id)
- KEY recipient_id (recipient_id)
- KEY created_at (created_at)
- KEY recipient_read (recipient_id, read_at)

## Core Functions

### `vh360_send_message($sender_id, $recipient_id, $message)`
Sends a direct message from one user to another.

**Returns:** `int|false` - Message ID on success, false on failure

**Security:**
- Checks permissions with `vh360_can_send_message()`
- Enforces rate limiting
- Sanitizes content with `wp_kses_post()`
- Validates character limit

### `vh360_get_conversation($user1_id, $user2_id, $limit = 50, $offset = 0)`
Retrieves messages between two users.

**Returns:** `array` - Array of message objects

### `vh360_get_user_conversations($user_id, $limit = 50)`
Gets all conversations for a user with last message and unread count.

**Returns:** `array` - Array of conversation objects with user data

**Performance:** Cached for 1 minute

### `vh360_mark_messages_read($user_id, $other_user_id)`
Marks all messages in a conversation as read.

**Returns:** `bool` - Success status

### `vh360_get_unread_messages_count($user_id)`
Gets total unread message count for a user.

**Returns:** `int` - Count of unread messages

**Performance:** Cached for 1 minute

### `vh360_delete_conversation($user_id, $other_user_id)`
Soft deletes a conversation for a user.

**Returns:** `bool` - Success status

### `vh360_can_send_message($sender_id, $recipient_id)`
Checks if a user can send a message to another user.

**Returns:** `bool` - Permission status

**Checks:**
- DM system enabled
- Users exist
- Not messaging self
- Mutual follow requirement (if enabled)

### `vh360_get_dm_url($user_id)`
Gets URL to message a specific user.

**Returns:** `string` - Dashboard URL with parameters

## AJAX Endpoints

All endpoints require:
- Valid nonce (`vh360_dm_nonce`)
- User authentication
- POST request

### `wp_ajax_vh360_send_dm`
Sends a new message.

**Parameters:**
- `recipient_id` (int)
- `message` (string)

**Returns:**
```json
{
  "success": true,
  "data": {
    "message": "Message sent successfully.",
    "message_data": {
      "id": 123,
      "sender_id": 1,
      "recipient_id": 2,
      "message_content": "Hello!",
      "created_at": "2024-01-01 12:00:00",
      "is_sender": true
    }
  }
}
```

### `wp_ajax_vh360_load_conversation`
Loads messages between current user and another user.

**Parameters:**
- `user_id` (int)
- `limit` (int, optional, default: 50)
- `offset` (int, optional, default: 0)

**Returns:**
```json
{
  "success": true,
  "data": {
    "messages": [...],
    "other_user": {
      "id": 2,
      "display_name": "John Doe",
      "avatar_url": "..."
    },
    "can_send": true
  }
}
```

### `wp_ajax_vh360_load_conversations`
Loads conversation list for current user.

**Parameters:**
- `limit` (int, optional, default: 50)

**Returns:**
```json
{
  "success": true,
  "data": {
    "conversations": [...],
    "total_unread": 5
  }
}
```

### `wp_ajax_vh360_mark_dm_read`
Marks conversation as read.

**Parameters:**
- `user_id` (int)

### `wp_ajax_vh360_delete_conversation`
Deletes conversation.

**Parameters:**
- `user_id` (int)

### `wp_ajax_vh360_check_new_dm`
Checks for new messages.

**Parameters:**
- `last_check` (datetime string)

### `wp_ajax_vh360_search_users_dm`
Searches users to message.

**Parameters:**
- `search` (string, min 2 characters)

**Returns:**
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 2,
        "display_name": "John Doe",
        "username": "johndoe",
        "avatar_url": "..."
      }
    ]
  }
}
```

## Admin Settings

Access: **WP Admin > VH360 Theme > Messages**

### Available Settings

1. **Enable Direct Messaging**
   - Enable/disable the entire system
   - Default: Enabled

2. **Require Mutual Following**
   - Users must follow each other to message
   - Default: Disabled

3. **Message Character Limit**
   - Maximum characters per message
   - Range: 100-5000
   - Default: 1000

4. **Message Retention Period**
   - Days to keep deleted messages
   - 0 = Keep forever
   - Default: 0

## JavaScript API

### Initialization
```javascript
// Automatically initializes when dashboard is loaded
// Checks for 'user' parameter in URL to open specific conversation
```

### Key Functions
- `loadConversations()` - Loads conversation list
- `openConversation(userId)` - Opens conversation with user
- `sendMessage()` - Sends message from compose area
- `deleteConversation()` - Deletes active conversation
- `searchUsers(searchTerm)` - Searches for users
- `checkNewMessages()` - Polls for new messages

### State Management
```javascript
DMState = {
  activeConversationUserId: null,
  lastCheckTime: null,
  pollInterval: null,
  isPolling: false,
  conversations: []
}
```

## Localization

The system uses `vh360DirectMessages` localized object with:

```javascript
{
  ajaxUrl: 'admin-ajax.php',
  nonce: 'security-nonce',
  currentUserId: 123,
  pollInterval: 10000,
  i18n: {
    send: 'Send',
    sending: 'Sending...',
    typeMessage: 'Type a message...',
    // ... more strings
  }
}
```

## Integration Points

### Notifications
Messages trigger the `vh360_message_sent` action:
```php
do_action('vh360_message_sent', $message_id, $sender_id, $recipient_id, $message);
```

This creates a notification of type `message` using the existing notification system.

### Follow System
If mutual following is required, the system checks:
```php
vh360_is_following($recipient_id, $sender_id) && 
vh360_is_following($sender_id, $recipient_id)
```

## Mobile Responsiveness

- **Desktop (>768px)**: Side-by-side layout
- **Mobile (≤768px)**: Stacked layout with toggle between list and conversation
- Auto-adjusts message bubble widths
- Touch-friendly buttons and inputs

## Accessibility

- ✅ Semantic HTML structure
- ✅ ARIA labels on interactive elements
- ✅ Keyboard navigation support
- ✅ Focus states on all interactive elements
- ✅ Screen reader friendly
- ✅ Reduced motion support

## Browser Compatibility

Tested and working on:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

Potential features for future development:
- [ ] Message attachments (images, files)
- [ ] Typing indicators
- [ ] Online/offline status
- [ ] Message reactions/emojis
- [ ] Message search
- [ ] Block users
- [ ] Report inappropriate messages
- [ ] Export conversation
- [ ] Group messaging
- [ ] Video/voice calls
- [ ] Desktop notifications

## Troubleshooting

### Messages Not Sending
1. Check if DM is enabled in admin settings
2. Verify user has permission to message recipient
3. Check browser console for JavaScript errors
4. Verify nonce is valid
5. Check rate limiting (max 10 msg/min)

### Conversations Not Loading
1. Check database table exists
2. Verify AJAX URL is correct
3. Check browser console for errors
4. Clear transient cache

### Missing Unread Badge
1. Verify user is logged in
2. Check if messages marked as read
3. Clear browser cache
4. Check transient cache

## Support

For issues or questions:
1. Check browser console for errors
2. Enable WordPress debug mode
3. Check server error logs
4. Review database table structure

## Changelog

### Version 1.0.0 (2024-12-02)
- Initial implementation
- Full 1-on-1 messaging system
- Dashboard UI with two-column layout
- Header icon integration
- Profile button integration
- Admin settings panel
- AJAX handlers with security
- Notification integration
- Mobile responsive design
- Rate limiting and caching

## Credits

Developed for Videohub360 Theme
- Database design following WordPress standards
- Security best practices implemented
- Performance optimized with caching and indexes
- Fully integrated with existing theme architecture
