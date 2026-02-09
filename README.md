# Videohub360 Theme

A lightweight, fast WordPress theme built specifically for the Videohub360 plugin with full Elementor support.

## Description

Videohub360 Theme is a modern, performance-optimized WordPress theme designed to perfectly complement the Videohub360 plugin. It features clean, semantic HTML5 markup, minimal CSS/JS loading, and comprehensive Elementor Page Builder integration.

## Features

### Core Features
- **Lightweight & Fast**: Minimal CSS/JS footprint for optimal performance
- **Elementor Compatible**: Full support for Elementor Page Builder with custom locations
- **Videohub360 Integration**: Optimized styling for video players, galleries, and archives
- **Responsive Design**: Mobile-first approach with fluid layouts
- **Accessibility Ready**: Semantic HTML5 and ARIA landmarks
- **Performance Optimized**: Async/defer scripts, no jQuery dependency

### Theme Support
- Custom Logo
- Custom Header
- Custom Background
- Navigation Menus (Primary & Footer)
- Widget Areas (Sidebar + 3 Footer Areas)
- Post Thumbnails with custom sizes
- HTML5 markup
- Editor Styles
- Responsive Embeds
- Wide & Full Alignment

### Elementor Integration
- Canvas template support
- Theme location registration (header, footer, single, archive)
- Custom video widget styling
- Live preview compatibility

### Video Features
- Optimized video player layouts
- Grid-based video galleries
- Large thumbnail support
- Video-specific single post templates
- Video archive pages with filtering
- Live badge support for livestreams

### Dashboard Features
- Complete frontend dashboard with 5 tabs
- User stats (videos, views, subscribers, likes)
- Video management (view, edit, delete)
- Profile quick edit with cover upload
- Activity feed with filters
- Settings management (account, privacy, notifications)
- Mobile responsive design
- AJAX operations for smooth UX
- Custom notification and modal systems

## Installation

1. Download the theme ZIP file or clone from repository
2. Upload to `/wp-content/themes/` directory
3. Activate the theme through 'Appearance > Themes' in WordPress
4. Install and activate the Videohub360 plugin
5. (Optional) Install Elementor Page Builder for enhanced functionality

## Configuration

### Customizer Options

Navigate to **Appearance > Customize** to configure:

- **Site Identity**: Logo, site title, tagline
- **Theme Colors**: Primary and secondary colors
- **Layout Options**: Container width
- **Header Options**: Custom header image
- **Background**: Custom background color or image
- **Menus**: Configure primary and footer navigation
- **Widgets**: Configure sidebar and footer widget areas

### Widget Areas

The theme includes 4 widget areas:
- **Sidebar**: Appears on blog posts and pages
- **Footer 1-3**: Three footer columns

### Menu Locations

- **Primary Menu**: Main navigation in header
- **Footer Menu**: Footer navigation links

## Elementor Setup

1. Install and activate Elementor (free or Pro)
2. Go to **Elementor > Settings**
3. Configure theme locations as needed
4. Build custom headers, footers, or single post templates

## Video Archive Setup

The theme automatically styles video archives created by the Videohub360 plugin:

- Video custom post type archives
- Video category archives
- Video series archives
- Video location archives

## Performance

The theme is optimized for Core Web Vitals:

- Minimal HTTP requests
- Deferred non-critical scripts
- Optimized CSS delivery
- No emoji scripts
- Lazy loading support
- Semantic HTML for faster parsing

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- iOS Safari
- Chrome Mobile

## Development

### File Structure

```
videohub360-theme/
├── assets/
│   ├── css/
│   │   ├── elementor-preview.css
│   │   └── videohub360-integration.css
│   ├── js/
│   │   ├── customizer.js
│   │   └── theme.js
│   └── images/
├── includes/
│   ├── customizer.php
│   ├── elementor-support.php
│   └── template-tags.php
├── template-parts/
│   ├── content.php
│   ├── content-none.php
│   ├── content-page.php
│   ├── content-single.php
│   ├── content-video.php
│   └── content-videohub360-single.php
├── 404.php
├── archive.php
├── footer.php
├── functions.php
├── header.php
├── index.php
├── page.php
├── sidebar.php
├── single.php
└── style.css
```

## Changelog

### Version 1.0.0
- Initial release
- Full Elementor support
- Videohub360 plugin integration
- Responsive design
- Performance optimizations
- Customizer options
- Widget areas
- Navigation menus

## Credits

- Theme: Videohub360 Theme
- Author: vh360
- License: GPL v2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html

## Support

For support, please visit:
- Theme Documentation: https://videohub360.com/theme-docs
- Videohub360 Plugin: https://videohub360.com
- Support Forum: https://videohub360.com/support

## License

This theme is licensed under GPL v2 or later.

Copyright (C) 2024 vh360

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
