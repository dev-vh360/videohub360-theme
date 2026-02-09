# VideoHub360 Import/Export - Technical Documentation

## Overview

This document provides technical details about the VideoHub360 Import/Export feature implementation for developers and maintainers.

## Architecture

### Class Structure

The import/export functionality is implemented in a single, self-contained class:

```
includes/class-videohub360-import-export.php
└── VideoHub360_Import_Export
    ├── Constants
    │   ├── MAX_IMPORT_FILE_SIZE (10MB)
    │   └── EXPORT_TRANSIENT_EXPIRATION (5 minutes)
    ├── Public Methods
    │   ├── export_video($post_id)
    │   ├── export_videos($post_ids)
    │   ├── generate_json_export($videos_data)
    │   ├── import_videos($json_data, $options)
    │   ├── validate_import_data($json_data)
    │   └── handle_duplicate(&$post_data, $duplicate_action)
    ├── Private Methods
    │   ├── import_single_video($video_data, $options)
    │   └── sanitize_meta_value($meta_key, $meta_value)
    ├── AJAX Handlers
    │   ├── ajax_export_videos()
    │   ├── ajax_export_all_videos()
    │   └── ajax_import_videos()
    └── Bulk Action Handlers
        ├── add_bulk_export_action($actions)
        ├── handle_bulk_export($redirect_to, $doaction, $post_ids)
        └── bulk_export_admin_notice()
```

### Integration Points

1. **Core Integration** (`class-videohub360-core.php`):
   - Class file loaded in `load_dependencies()`
   - Component initialized in `init_components()`
   - Stored in `$this->components['import_export']`

2. **Admin Integration** (`class-videohub360-admin.php`):
   - Submenu page added via `add_admin_menu()`
   - Admin UI rendered via `import_export_page()`
   - JavaScript for AJAX interactions included inline

3. **WordPress Hooks**:
   - `wp_ajax_vh360_export_videos`
   - `wp_ajax_vh360_export_all_videos`
   - `wp_ajax_vh360_import_videos`
   - `bulk_actions-edit-videohub360`
   - `handle_bulk_actions-edit-videohub360`
   - `admin_notices`

## Data Flow

### Export Flow

```
User Action (UI)
    ↓
AJAX Request (wp_ajax_vh360_export_all_videos)
    ↓
export_videos($post_ids)
    ↓
export_video($post_id) [for each video]
    ↓
generate_json_export($videos_data)
    ↓
JSON Response → Browser Download
```

### Import Flow

```
User Upload (UI)
    ↓
AJAX Request (wp_ajax_vh360_import_videos)
    ↓
File Validation (type, size)
    ↓
validate_import_data($json_data)
    ↓
import_videos($json_data, $options)
    ↓
import_single_video($video_data, $options) [for each video]
    ↓
    ├── handle_duplicate()
    ├── wp_insert_post() / wp_update_post()
    ├── update_post_meta()
    └── wp_set_object_terms()
    ↓
Results Summary → UI Display
```

## JSON Export Format Specification

### Version 1.0.0 Schema

```json
{
  "videohub360_export": {
    "version": "1.0.0",
    "export_date": "YYYY-MM-DD HH:MM:SS",
    "exported_by": "username",
    "videos": [
      {
        "post_data": {
          "title": "string (required)",
          "content": "string",
          "excerpt": "string",
          "status": "publish|draft|pending|private",
          "post_date": "YYYY-MM-DD HH:MM:SS",
          "post_date_gmt": "YYYY-MM-DD HH:MM:SS",
          "post_modified": "YYYY-MM-DD HH:MM:SS",
          "post_modified_gmt": "YYYY-MM-DD HH:MM:SS",
          "slug": "string"
        },
        "meta_data": {
          "video_url": "url|empty",
          "ad_video_url": "url|empty",
          "midroll_ad_video_url": "url|empty",
          "midroll_ad_timing": "string|empty",
          "postroll_ad_video_url": "url|empty",
          "postroll_ad_enabled": "yes|empty",
          "videohub360_custom_html": "html|empty",
          "_videohub360_post_views_count": "integer|empty",
          "_vh360_ad_click_url": "url|empty",
          "_vh360_midroll_ad_click_url": "url|empty",
          "_vh360_postroll_ad_click_url": "url|empty",
          "_vh360_is_live": "yes|no",
          "_vh360_type": "embed|mp4|stream|html",
          "_vh360_embed_code": "html|empty",
          "_vh360_stream_url": "url|empty",
          "_vh360_api_url": "url|empty",
          "_vh360_poster": "url|empty",
          "_vh360_viewer_count": "yes|no",
          "_vh360_live_badge": "yes|no",
          "_vh360_badge_text": "string",
          "_vh360_badge_color": "hex_color",
          "_vh360_offline_message": "string|empty",
          "_vh360_live_start_time": "YYYY-MM-DD HH:MM:SS|empty",
          "_vh360_stream_stopped": "yes|no",
          "_vh360_chat_enabled": "yes|no|empty",
          "_vh360_chat_placement": "inline|popup|sidebar|off|empty",
          "_vh360_agora_channel_name": "string|empty",
          "_vh360_agora_mode": "interactive|broadcast",
          "_vh360_agora_everyone_is_host": "yes|no",
          "_vh360_host_passcode": "string|empty",
          "_vh360_video_quality": "string|empty",
          "_vh360_video_mirror": "string|empty",
          "_vh360_override_quality_settings": "yes|no",
          "_vh360_sidebar_config": "array|empty"
        },
        "taxonomies": {
          "videohub360_category": [
            {
              "term_id": "integer",
              "name": "string",
              "slug": "string",
              "description": "string"
            }
          ],
          "videohub360_series": [...],
          "videohub360_location": [...],
          "videohub360_tag": [...]
        },
        "featured_image_url": "url|empty"
      }
    ]
  }
}
```

## Security Implementation

### Input Validation

1. **File Upload Validation**:
   ```php
   - File extension check: JSON only
   - File size check: MAX_IMPORT_FILE_SIZE (10MB)
   - File type verification via wp_check_filetype()
   ```

2. **JSON Validation**:
   ```php
   - Structure validation via validate_import_data()
   - Required field checks (videohub360_export, version, videos, post_data, title)
   - Data type validation (arrays, strings)
   ```

3. **Data Sanitization**:
   ```php
   - URLs: esc_url_raw()
   - Text: sanitize_text_field()
   - HTML: wp_kses_post()
   - Numbers: absint()
   - Keys: sanitize_key()
   ```

### Access Control

1. **Capability Checks**:
   - All operations require: `edit_posts` capability
   - Covers: Editors and Administrators
   - Excludes: Authors, Contributors, Subscribers

2. **Nonce Verification**:
   - All AJAX requests verify nonce: `vh360_import_export_nonce`
   - Check performed via: `check_ajax_referer()`
   - Nonces escaped in JavaScript: `esc_js()`

### Output Escaping

1. **JavaScript Context**:
   - Nonces: `esc_js(wp_create_nonce())`
   - JSON output: `wp_json_encode()` with security flags:
     - `JSON_HEX_TAG`
     - `JSON_HEX_APOS`
     - `JSON_HEX_QUOT`
     - `JSON_HEX_AMP`

2. **HTML Context**:
   - All output escaped with appropriate functions
   - Attributes: `esc_attr()`
   - URLs: `esc_url()`
   - Text: `esc_html()`
   - Translation strings: `esc_html__()`

## Error Handling

### Validation Errors

Returns `WP_Error` objects with descriptive messages:
- `invalid_post`: Invalid video post ID
- `invalid_format`: Invalid JSON format
- `invalid_structure`: Missing required keys
- `missing_fields`: Missing required fields
- `invalid_videos`: Videos not an array
- `invalid_video_data`: Invalid post data structure
- `missing_title`: Missing video title
- `duplicate_skipped`: Video already exists (when skipping)
- `invalid_action`: Invalid duplicate action

### Import Results Structure

```php
array(
    'success' => boolean,
    'imported' => integer,
    'updated' => integer,
    'skipped' => integer,
    'errors' => array(),
    'warnings' => array()
)
```

## Database Operations

### Posts

- **Insert**: `wp_insert_post($post_args, true)`
- **Update**: `wp_update_post($post_args, true)`
- **Query**: `get_posts()`, `get_page_by_path()`

### Meta Data

- **Update**: `update_post_meta($post_id, $meta_key, $meta_value)`
- **Read**: `get_post_meta($post_id, $meta_key, true)`

### Taxonomies

- **Read Terms**: `wp_get_post_terms($post_id, $taxonomy, ['fields' => 'all'])`
- **Check Term**: `get_term_by('slug', $slug, $taxonomy)`
- **Create Term**: `wp_insert_term($name, $taxonomy, $args)`
- **Assign Terms**: `wp_set_object_terms($post_id, $term_ids, $taxonomy)`

## Performance Considerations

### Memory Management

1. **File Size Limits**: Maximum 10MB to prevent memory exhaustion
2. **Batch Processing**: Videos imported one at a time
3. **Transient Usage**: Bulk exports stored temporarily (5 minutes)
4. **JSON Encoding**: Uses memory-efficient streaming where possible

### Query Optimization

1. **Bulk Operations**: Single query per video for meta data
2. **Taxonomy Checks**: Individual term lookups (unavoidable for accuracy)
3. **Post Lookup**: Efficient slug-based lookups via `get_page_by_path()`

### Scalability

- **Small Sites** (<100 videos): No issues
- **Medium Sites** (100-1000 videos): Works well, may take a few seconds
- **Large Sites** (>1000 videos): Consider exporting in batches using bulk actions

## Extending the Feature

### Adding New Meta Fields

To export/import additional meta fields:

1. Add to `export_video()` method in the `$meta_data` array:
   ```php
   'your_new_field' => get_post_meta($post_id, 'your_new_field', true),
   ```

2. Add sanitization in `sanitize_meta_value()` if needed:
   ```php
   if ($meta_key === 'your_new_field') {
       return sanitize_your_way($meta_value);
   }
   ```

### Adding New Taxonomies

To support additional taxonomies:

1. Add to `export_video()` method in the `$taxonomies` array:
   ```php
   'your_taxonomy' => wp_get_post_terms($post_id, 'your_taxonomy', array('fields' => 'all')),
   ```

2. Import logic will handle it automatically

### Custom Duplicate Handling

Add new cases to `handle_duplicate()`:

```php
case 'your_custom_action':
    // Your logic here
    return $result;
```

### Modifying File Size Limit

Change the constant:

```php
const MAX_IMPORT_FILE_SIZE = 20971520; // 20MB
```

Or use a filter (add to class):

```php
add_filter('vh360_max_import_file_size', function($size) {
    return 20971520; // 20MB
});
```

## Testing

### Unit Test Coverage Needed

1. **Export Functions**:
   - `export_video()` with valid/invalid post IDs
   - `export_videos()` with multiple videos
   - `generate_json_export()` format validation

2. **Import Functions**:
   - `validate_import_data()` with valid/invalid JSON
   - `handle_duplicate()` with all three modes
   - `import_single_video()` with various scenarios

3. **Sanitization**:
   - `sanitize_meta_value()` for all field types

### Integration Test Scenarios

1. Export → Import round-trip
2. Bulk export → Bulk import
3. Duplicate handling (all three modes)
4. Taxonomy creation on import
5. Error handling for invalid data

### Manual Testing Checklist

- [ ] Export single video
- [ ] Export all videos
- [ ] Bulk export selected videos
- [ ] Import with skip duplicates
- [ ] Import with update existing
- [ ] Import with create new slugs
- [ ] Import creates missing taxonomy terms
- [ ] File size validation works
- [ ] Invalid JSON rejection
- [ ] UI feedback messages appear correctly
- [ ] Downloads work in all browsers
- [ ] Nonce validation prevents unauthorized access

## Troubleshooting

### Common Issues

1. **"Maximum execution time exceeded"**:
   - Increase PHP `max_execution_time`
   - Export/import in smaller batches

2. **"Allowed memory size exhausted"**:
   - Increase PHP `memory_limit`
   - Reduce import file size (split into multiple files)

3. **"Cannot create term"**:
   - Check taxonomy is registered
   - Verify user has correct capabilities

4. **Downloads not working**:
   - Check browser popup blocker
   - Verify transient storage is working
   - Check filesystem permissions

### Debug Mode

To enable debugging, add to wp-config.php:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Add debug logging to methods:

```php
if (WP_DEBUG) {
    error_log('VH360 Import: ' . print_r($data, true));
}
```

## Version History

### 1.0.0 (2025-11-23)
- Initial implementation
- Export/import core functionality
- Bulk actions support
- Admin UI
- Security measures
- Documentation

## Future Enhancements

Potential improvements for future versions:

1. **Background Processing**: Use WordPress background jobs for large imports
2. **Progress Indicators**: Real-time progress for long-running operations
3. **Media Import**: Option to download and import featured images
4. **Partial Imports**: Select specific videos from import file
5. **Export Profiles**: Save export configurations for reuse
6. **CSV Format**: Alternative export format for spreadsheet compatibility
7. **Remote Imports**: Import directly from URL
8. **Scheduled Exports**: Automatic regular backups
9. **Differential Sync**: Only export/import changed videos
10. **Compression**: GZIP compression for smaller files

## API Reference

### Filters

```php
// Modify export data before JSON generation
apply_filters('vh360_export_video_data', $video_data, $post_id);

// Modify import options
apply_filters('vh360_import_options', $options);

// Customize file size limit
apply_filters('vh360_max_import_file_size', self::MAX_IMPORT_FILE_SIZE);
```

### Actions

```php
// After successful export
do_action('vh360_after_export', $post_ids, $json);

// After successful import
do_action('vh360_after_import', $results);

// Before video import
do_action('vh360_before_import_video', $video_data);

// After video import
do_action('vh360_after_import_video', $post_id, $video_data);
```

## Support

For technical support or to report bugs:

1. Check this documentation
2. Review error logs
3. Test with example-export.json
4. Contact VideoHub360 development team

## License

This feature is part of VideoHub360 and follows the same GPL v2 or later license.
