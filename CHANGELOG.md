# Changelog

All notable changes to the VideoHub360 Theme and its bundled platform components will be documented in this file.

This project follows semantic versioning.

---

## Unreleased

### Added
- Avatar cropping system with client-side interface using Cropper.js
- Live preview and repositioning for profile picture uploads
- Centralized avatar processing with EXIF orientation correction
- Configurable avatar settings in admin (output size, quality, minimum dimensions)
- MIME type validation using wp_check_filetype_and_ext() for enhanced security
- Automatic cleanup of old avatar attachments after successful upload

### Changed
- Profile picture upload now uses centralized vh360_process_profile_avatar_upload() helper
- Both profile editing templates (template-profile-edit.php and dashboard) now share identical avatar processing logic

### Security
- Enhanced MIME type validation with defense-in-depth approach
- Crop coordinates sanitized before server processing
- Dimension validation prevents low-quality uploads

---

## 1.0.0 – Initial Release

### Added
- Video-first WordPress theme serving as the presentation layer for the VideoHub360 platform
- Support for live streaming and on-demand video experiences
- Community-oriented layouts for activity feeds, user profiles, and social interaction
- Integrated notifications and direct messaging user interface
- Elementor-compatible templates and layout support
- WordPress Customizer controls for branding, layout, and navigation
- Progressive Web App (PWA) and app-ready integration via bundled plugins
- Translation-ready and RTL language support

### Performance
- Lightweight front-end architecture with minimal CSS and JavaScript
- Optimized asset loading for improved Core Web Vitals
- Clean semantic HTML5 structure

### Stability
- Consistent branding and versioning across theme and bundled plugins
- Production-safe debug logging with gated console and server logs
- Release-ready packaging and directory structure

---

Future updates will build on this foundation with additional features, improvements, and refinements.
