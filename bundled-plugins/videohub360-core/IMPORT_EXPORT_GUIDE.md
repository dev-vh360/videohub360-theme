# VideoHub360 Import/Export Guide

## Overview

The VideoHub360 Import/Export feature allows you to transfer videos between different WordPress sites running the VideoHub360 plugin. This is useful for:

- Migrating videos to a new site
- Backing up video content
- Syncing videos across multiple sites
- Duplicating content for testing

## Accessing the Feature

Navigate to: **VideoHub360 → Import/Export** in the WordPress admin panel.

## What Gets Exported

### Post Data
- Title
- Content (description)
- Excerpt
- Post status (published, draft, pending, private)
- Publication date
- Slug

### Video Meta Data
- Video URL (main video file)
- Pre-roll ad video URL
- Mid-roll ad video URL and timing
- Post-roll ad video URL and settings
- Ad click-through URLs (for all ad types)
- View count
- Custom HTML embed code

### Livestream Settings
- Is Live flag
- Livestream type (embed, stream URL, etc.)
- Embed code
- Stream URL and API URL
- Poster image URL
- Viewer count settings
- Live badge configuration
- Badge text and color
- Offline message
- Stream start time
- Agora channel name and settings
- Agora mode (interactive/broadcast)
- Host passcode

### Video Quality Settings
- Video quality override
- Mirror settings
- Quality preferences

### Sidebar Configuration
- Custom sidebar settings

### Taxonomies
- Categories (videohub360_category)
- Series (videohub360_series)
- Locations (videohub360_location)
- Tags (videohub360_tag)

### Other
- Featured image URL (reference only - the actual image file is not transferred)

## What Does NOT Get Exported

- Chat messages
- Moderation data
- Video files themselves (only URLs are stored)
- Featured image files (only URLs are stored)

## Export Methods

### Method 1: Export All Videos

1. Navigate to **VideoHub360 → Import/Export**
2. Click the **"Export All Videos"** button
3. A JSON file will be automatically downloaded to your computer
4. The file will be named: `videohub360-export-YYYY-MM-DD-HHMMSS.json`

### Method 2: Export Selected Videos

1. Navigate to **VideoHub360 → All Videos**
2. Select the videos you want to export using the checkboxes
3. From the **Bulk Actions** dropdown, select **"Export Selected"**
4. Click **Apply**
5. A JSON file will be automatically downloaded

## Import Process

### Step 1: Prepare Import File

Ensure you have a valid JSON export file from VideoHub360 (version 1.0.0 or compatible).

### Step 2: Import Videos

1. Navigate to **VideoHub360 → Import/Export**
2. Click **"Choose File"** and select your JSON export file
3. Select your duplicate handling preference:
   - **Skip duplicates** (default): Videos with existing titles/slugs will be skipped
   - **Update existing**: Existing videos will be updated with imported data
   - **Create new with modified slug**: All videos will be imported, duplicates get numbered slugs
4. Click **"Import Videos"**
5. Wait for the import to complete
6. Review the import results showing:
   - Number of videos imported
   - Number of videos updated
   - Number of videos skipped
   - Any warnings or errors

## Duplicate Handling

### Skip Duplicates (Recommended for first-time imports)
- Videos are checked by slug and title
- If a match is found, the video is skipped
- Original videos remain unchanged
- Safe option with no data loss

### Update Existing (Use with caution)
- Existing videos with matching slugs are overwritten
- All meta data is replaced with imported data
- Use this when you want to sync/update videos between sites
- **Warning**: This will overwrite any changes made to existing videos

### Create New with Modified Slug
- All videos are imported, even duplicates
- Duplicate slugs get a number appended (e.g., `my-video-1`, `my-video-2`)
- Use this when you want to keep both versions
- Can result in duplicate content if not managed

## File Specifications

### File Format
- Format: JSON
- Extension: `.json`
- Max Size: 10MB (configurable in code)
- Encoding: UTF-8

### JSON Structure
```json
{
  "videohub360_export": {
    "version": "1.0.0",
    "export_date": "2025-11-23 00:00:16",
    "exported_by": "admin",
    "videos": [
      {
        "post_data": {
          "title": "Video Title",
          "content": "Video description...",
          "excerpt": "Short excerpt...",
          "status": "publish",
          "post_date": "2025-11-23 00:00:00",
          "slug": "video-title"
        },
        "meta_data": {
          "video_url": "https://example.com/video.mp4",
          "_videohub360_post_views_count": "100",
          ...
        },
        "taxonomies": {
          "videohub360_category": [...],
          "videohub360_series": [...],
          ...
        },
        "featured_image_url": "https://example.com/image.jpg"
      }
    ]
  }
}
```

## Important Notes

### Taxonomy Terms
- If a taxonomy term (category, series, location, tag) doesn't exist on the destination site, it will be created automatically
- Term slugs are preserved to maintain consistency
- Parent-child relationships for hierarchical taxonomies are maintained

### Featured Images
- Only the URL is stored in the export
- Featured images are NOT automatically downloaded/uploaded
- You'll need to:
  - Manually set featured images after import, OR
  - Use a plugin like "Auto Upload Images" or "Media from FTP", OR
  - Ensure the images are accessible at the same URLs on the destination site

### Video URLs
- Video file URLs are preserved as-is
- Ensure videos are accessible at the exported URLs, or
- Update video URLs after import if hosting location changes

### Agora Settings
- Agora channel names and passcodes are exported
- You may want to regenerate these for security reasons after import
- Global Agora App ID and Certificate should be configured separately on the destination site

### View Counts
- View counts are exported and imported
- Use this to preserve statistics when migrating
- Set to 0 in the JSON file if you want to reset counts

## Troubleshooting

### Import Fails with "Invalid JSON Format"
- Verify the file is a valid JSON file exported by VideoHub360
- Check that the file hasn't been corrupted
- Ensure the file is not larger than 10MB

### Import Shows Many "Skipped" Videos
- This is normal when using "Skip duplicates" mode
- The videos already exist on the destination site
- Switch to "Update existing" if you want to overwrite them

### Some Videos Missing After Import
- Check the import results for errors
- Verify that required fields (title) are present in the JSON
- Review any error messages displayed after import

### Featured Images Not Appearing
- This is expected - featured image URLs are only stored as references
- You need to manually set featured images after import
- Consider using an image import plugin for bulk operations

## Security Considerations

### Permissions
- Only users with `edit_posts` capability can access import/export
- Typically this means Editors and Administrators
- Authors cannot access this feature

### File Validation
- Only JSON files are accepted
- Maximum file size is enforced (10MB by default)
- All imported data is sanitized before saving

### Sensitive Data
- Agora passcodes and channel names are included in exports
- Store export files securely
- Consider regenerating Agora credentials after import

## Best Practices

1. **Backup First**: Always backup your database before importing videos
2. **Test Import**: Test with a small export first on a staging site
3. **Review Results**: Always review the import summary for errors
4. **Verify Content**: Spot-check imported videos to ensure data integrity
5. **Update URLs**: If video hosting locations changed, update URLs after import
6. **Set Featured Images**: Plan to manually set featured images after import
7. **Check Taxonomies**: Verify that taxonomy terms were created correctly

## Advanced Usage

### Modifying Export Files

You can edit the JSON file before importing to:
- Update video URLs in bulk
- Change taxonomies
- Modify meta data
- Reset view counts
- Update dates

Always validate the JSON syntax after editing.

### Filtering Exports

The plugin exports all videos. To export specific videos:
1. Use the bulk action "Export Selected" on the videos list
2. Select only the videos you want
3. This gives you more control over what's exported

### Regular Backups

Consider setting up a regular export schedule:
1. Use the "Export All Videos" feature monthly
2. Store exports in a secure location
3. Keep multiple versions for safety
4. This provides a content backup independent of database backups

## Support

For issues or questions:
1. Check this guide first
2. Review the import/export results for specific error messages
3. Contact VideoHub360 support with:
   - WordPress version
   - VideoHub360 version
   - Export file (if relevant)
   - Error messages or unexpected behavior

## Version Compatibility

- Export Format Version: 1.0.0
- Minimum VideoHub360 Version: 1.0.0
- WordPress Minimum: 5.0

Future versions of VideoHub360 will maintain backward compatibility with this export format.
