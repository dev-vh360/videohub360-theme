# Changelog

All notable changes to the VideoHub360 Theme and its bundled platform components will be documented in this file.

This project follows semantic versioning.

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
- Avatar cropping with live preview, repositioning, EXIF orientation correction, configurable output settings, and automatic cleanup of replaced attachments

### Changed
- Profile picture editing uses centralized avatar processing across profile interfaces
- Camera mirroring is disabled for local Agora playback and for Studio Preview and Program output
- Publitio direct-browser uploads default to a maximum size of 4 GiB
- Existing installations using the former 300 MiB upload default migrate to 4 GiB without overwriting administrator-configured limits
- Public theme and bundled-plugin versions are standardized at `1.0.0`
- The production Core plugin installs into the `videohub360/` folder
- TGMPA metadata matches the bundled `1.0.0` plugin releases

### Security
- Avatar uploads use MIME type, crop-coordinate, and minimum-dimension validation

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
