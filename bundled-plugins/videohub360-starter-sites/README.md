# VideoHub360 Starter Sites Plugin

One-click demo import system for VideoHub360 theme with manifest-driven package importing.

## Features

- **Remote Demo Registry**: Fetches available demos from a remote registry endpoint
- **Manifest-Driven Import**: Each demo is defined by a manifest.json that controls what gets imported
- **Phased Import Pipeline**: Imports content, widgets, Customizer settings, Elementor kits, and theme options in a controlled sequence
- **Post-Import Setup**: Automatically assigns homepage, posts page, and menus after import
- **Logging System**: Comprehensive logging of all import operations
- **Admin UI**: Clean, user-friendly interface integrated with VH360 Theme admin

## Installation

This plugin is bundled with the VideoHub360 theme and installed automatically via TGMPA.

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- VideoHub360 Theme (active)
- Recommended: 256MB PHP memory limit
- Recommended: 300 seconds max execution time

## Import Pipeline

The import process follows this sequence:

1. **Validate Environment** - Check PHP version, WordPress version, permissions, and server requirements
2. **Download Package** - Fetch manifest and download package files to temp directory
3. **Check Plugins** - Ensure required plugins are active
4. **Import Content** - Import WordPress XML with pages, posts, custom post types, and media
5. **Import Widgets** - Import widget settings to sidebars
6. **Import Customizer** - Import Customizer theme mods and options
7. **Import Elementor Kit** - Import Elementor global settings and templates
8. **Import Theme Options** - Import allowed VH360 theme settings (uses allowlist for security)
9. **Post-Import Setup** - Assign homepage, posts page, menus, flush rewrite rules, clear caches
10. **Verification** - Verify import completeness and report issues

## Demo Package Structure

Each demo package is controlled by a `manifest.json` file:

```json
{
  "demo_id": "demo-one",
  "version": "1.0.0",
  "description": "Demo One - Complete business site",
  "base_url": "https://demos.videohub360.com/demo-one/",
  "required_plugins": ["elementor", "videohub360", "videohub360-community"],
  "requires_elementor": true,
  "files": {
    "content": {
      "path": "content.xml"
    },
    "widgets": {
      "path": "widgets.json"
    },
    "customizer": {
      "path": "customizer.json"
    },
    "elementor_kit": {
      "path": "elementor-kit.zip"
    },
    "theme_options": {
      "path": "theme-options.json"
    }
  },
  "post_import": {
    "homepage": {
      "slug": "home"
    },
    "posts_page": {
      "slug": "blog"
    },
    "menus": {
      "primary": "Main Menu",
      "footer": "Footer Menu"
    }
  }
}
```

## Elementor Kit Structure

The Elementor kit importer supports both simple and full Elementor export formats:

### Supported Formats

**Full Elementor Export (Recommended)**
```
elementor-kit.zip
└── elementor-kit/
    ├── manifest.json              # Package metadata
    ├── site-settings.json         # Global Elementor settings
    ├── elementor-{id}.json        # Template/document files
    ├── content/
    │   └── *.json                 # Additional content structures
    └── taxonomies/
        └── *.json                 # Taxonomy data
```

**Legacy Format (Still Supported)**
```
elementor-kit.json                 # Single JSON file with settings
```

### Import Process

1. **ZIP Extraction & Normalization**: Extracts and normalizes the directory structure
   - Removes `__MACOSX`, `.DS_Store`, and hidden files
   - Detects nested folders (e.g., if extraction creates `elementor-kit/` subfolder)
   - Uses the actual kit root, not the extraction root

2. **Recursive File Discovery**: Scans all nested directories for JSON files

3. **File Role Detection**: Categorizes files by purpose:
   - `manifest.json` → Package structure reference
   - `site-settings.json` → Global Elementor kit settings
   - `elementor-*.json` → Templates/documents
   - `content/*.json` → Additional content structures
   - `taxonomies/*.json` → Taxonomy-related data

4. **Sequential Import**:
   - Import site settings (global kit configuration)
   - Import template/document files using Elementor's `import_template()` API
   - Templates imported as actual Elementor documents (creates WordPress posts with Elementor metadata)
   - Log detailed diagnostics for each step

### Template Import Details

Templates are imported using Elementor's official `import_template()` API:
- Each `elementor-*.json` file is base64-encoded and passed to Elementor's templates manager
- Creates WordPress posts with proper Elementor document metadata
- Templates become editable in Elementor after import
- Import format: `['fileData' => base64_encode($json), 'fileName' => 'template.json']`

### Logging

The importer provides detailed diagnostics:
- Detected root directory
- Number of JSON files found
- Presence of `manifest.json` and `site-settings.json`
- Template count and individual import results
- Per-template success/failure with specific reasons:
  - File not found
  - Invalid JSON (with error details)
  - Elementor not initialized
  - Import method not available
  - Template creation failures
- Final summary differentiates site settings vs templates:
  - "Site settings: imported | Templates: 4 found, 4 imported, 0 failed"

### Error Handling

Elementor kit import is **non-fatal**:
- Import failures log warnings but don't crash the full demo import
- Each template failure logged individually with specific reason
- Detailed error messages help identify structural issues
- Import continues even if Elementor is not fully initialized
- Final summary shows exactly what succeeded vs failed

## Theme Options Security

Only whitelisted theme options are imported. The allowlist is defined in `includes/helpers.php`:

```php
function vh360_ss_get_allowed_theme_options() {
    return array(
        'vh360_appearance_options' => array('site_layout', 'content_width', ...),
        'vh360_profile_options' => array('show_profile_stats', ...),
        // ...
    );
}
```

**Never imported:**
- License keys
- API keys
- SMTP credentials
- Webhook URLs
- Analytics secrets
- Domain-specific environment values

## Admin Interface

Access the Starter Sites admin at: **VH360 Theme → Starter Sites**

Features:
- System status display
- Demo grid with thumbnails
- Preview links
- One-click import
- Real-time progress tracking
- Import log viewer

## Filters and Actions

### Filters

```php
// Customize registry URL
add_filter('vh360_ss_registry_url', function($url) {
    return 'https://your-domain.com/demos/registry.json';
});

// Customize allowed theme options
add_filter('vh360_ss_allowed_theme_options', function($options) {
    $options['vh360_custom_options'] = array('custom_setting');
    return $options;
});
```

### Actions

```php
// Run custom code after import
add_action('vh360_after_demo_import', function() {
    // Your custom code here
});
```

## Logging

Import logs are stored in the WordPress options table:
- `vh360_ss_last_import_log` - Most recent import log
- `vh360_ss_import_history` - Last 10 import logs

View logs in the admin interface or retrieve programmatically:

```php
$last_log = VH360_Demo_Logger::get_last_log();
$history = VH360_Demo_Logger::get_history(10);
```

## Temporary Files

Temporary files are stored in `wp-content/uploads/vh360-starter-sites-temp/`

- Files are automatically cleaned up after import
- Old files (>24 hours) are automatically deleted daily
- Directory is protected with .htaccess

## Development Notes

### WordPress Importer

The plugin requires the WordPress Importer for content import. Include the actual WordPress Importer files in `includes/wordpress-importer/` or ensure the WordPress Importer plugin is installed.

### Testing

To test with a single demo:

1. Create a demo registry JSON file
2. Create demo package files (content.xml, widgets.json, etc.)
3. Create a manifest.json
4. Update the registry URL filter
5. Test the import process

## Version

1.0.0

## License

GPL v2 or later

## Support

For support, visit https://videohub360.com
