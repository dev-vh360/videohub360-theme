# WordPress Importer

This directory contains a bundled version of the official WordPress Importer plugin (v0.9.5) for use with the Starter Sites plugin.

## What's Included

This is a complete, self-contained implementation of the WordPress XML (WXR) importer, including:

- **Main Importer Class** (`class-wp-import.php`) - Handles the complete import process for WordPress export files
- **Multiple Parsers** (`parsers/`) - Five different XML parsing implementations for maximum compatibility
- **PHP XML Toolkit** (`php-toolkit/`) - Modern XML processing library for advanced parsing
- **Compatibility Layer** (`compat.php`) - Functions for backwards compatibility with older WordPress versions

## Version

- **WordPress Importer:** 0.9.5
- **Source:** https://github.com/WordPress/wordpress-importer
- **License:** GPL v2 or later

## Security

This version includes the fix for CVE-2024-13889 (PHP object injection vulnerability that affected versions <= 0.8.3).

## Usage

The importer is automatically loaded when the Starter Sites plugin needs to import WordPress XML content files. No manual initialization required.

```php
// The WP_Import class is available after loading the bootstrap
require_once VH360_STARTER_SITES_INCLUDES . 'wordpress-importer/wordpress-importer.php';

// Create an importer instance
$importer = new WP_Import();
$importer->fetch_attachments = true;

// Import a WordPress export file
$result = $importer->import($file_path);
```

## Why Bundled?

This is bundled with the Starter Sites plugin to:
1. **Simplify installation** - No need for users to manually install a separate importer plugin
2. **Ensure compatibility** - We control which version is used
3. **Self-contained** - The Starter Sites plugin works out of the box
4. **Version control** - Updates are tested and versioned with the theme

## Parsers

The importer includes multiple parser implementations that are tried in order of preference:

1. **WXR_Parser_XML_Processor** - Modern, fastest parser using WordPress XML Processor (recommended)
2. **WXR_Parser_SimpleXML** - Uses PHP's SimpleXML extension
3. **WXR_Parser_XML** - Uses PHP's XMLReader
4. **WXR_Parser_Regex** - Legacy regex-based parser (deprecated, included for compatibility only)

The importer automatically selects the best available parser for the server environment.

## Attribution

This is the official WordPress Importer maintained by the WordPress.org team.
- **Official Plugin Page:** https://wordpress.org/plugins/wordpress-importer/
- **GitHub Repository:** https://github.com/WordPress/wordpress-importer
- **Authors:** wordpressdotorg and contributors

## License

GPL v2 or later - Same as WordPress core and the original plugin.

## Modifications

The only modification from the official version is the bootstrap file (`wordpress-importer.php`) which has been adapted to:
- Load the importer on-demand rather than via admin_init hook
- Be compatible with bundled plugin architecture
- Remove plugin registration (not needed for bundled use)

All core importer files are unmodified from the official 0.9.5 release.
