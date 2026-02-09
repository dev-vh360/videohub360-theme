# Videohub360 Theme Installation Guide

## Quick Start

Follow these simple steps to install and activate the Videohub360 theme on your WordPress site.

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Videohub360 plugin (recommended)
- Elementor Page Builder (optional, but recommended)

## Installation Methods

### Method 1: Manual Upload via WordPress Admin

1. **Prepare the theme:**
   - Download or create a ZIP file of the `videohub360-theme` folder
   - Ensure the folder contains all theme files

2. **Upload to WordPress:**
   - Log in to your WordPress admin panel
   - Navigate to **Appearance > Themes**
   - Click **Add New** button
   - Click **Upload Theme** button
   - Choose the `videohub360-theme.zip` file
   - Click **Install Now**

3. **Activate the theme:**
   - Once uploaded, click **Activate**
   - Your theme is now live!

### Method 2: FTP/File Manager Upload

1. **Connect to your server:**
   - Use FTP client (FileZilla, Cyberduck, etc.) or your hosting's file manager
   - Navigate to `/wp-content/themes/` directory

2. **Upload the theme:**
   - Upload the entire `videohub360-theme` folder to the themes directory
   - Ensure all files and folders are transferred correctly

3. **Activate via WordPress:**
   - Log in to WordPress admin
   - Navigate to **Appearance > Themes**
   - Find "Videohub360 Theme" and click **Activate**

### Method 3: Direct Server Copy (For Repository Clone)

```bash
# From the repository root
cd /path/to/Videohub360
cp -r videohub360-theme /path/to/wordpress/wp-content/themes/
```

## Post-Installation Setup

### Step 1: Configure Basic Settings

1. **Set Site Identity:**
   - Go to **Appearance > Customize > Site Identity**
   - Upload your logo (recommended: 300x100px)
   - Set site title and tagline
   - Click **Publish**

2. **Configure Menus:**
   - Go to **Appearance > Menus**
   - Create a new menu or edit existing
   - Assign to "Primary Menu" location
   - (Optional) Create footer menu and assign to "Footer Menu"

3. **Set Up Widgets (Optional):**
   - Go to **Appearance > Widgets**
   - Add widgets to:
     - Sidebar (appears on posts and pages)
     - Footer 1, 2, 3 (appears in footer columns)

### Step 2: Theme Customization

1. **Customize Colors:**
   - Go to **Appearance > Customize > Theme Colors**
   - Set Primary Color (default: #2563eb)
   - Set Secondary Color (default: #1e40af)
   - Preview changes in real-time
   - Click **Publish**

2. **Adjust Layout:**
   - Go to **Appearance > Customize > Layout Options**
   - Set Container Width (default: 1280px, range: 960-1920px)
   - Click **Publish**

3. **Background & Header:**
   - Configure custom background (**Appearance > Customize > Background**)
   - Set custom header image (**Appearance > Customize > Header Image**)

### Step 3: Install Recommended Plugins

For the best experience, install these plugins:

1. **Videohub360 Plugin** (Required for video features)
   - Provides video custom post type
   - Video player functionality
   - Video management
   
2. **Elementor Page Builder** (Highly Recommended)
   - Full page builder functionality
   - Custom layouts for videos
   - Advanced design options
   - Install free version or Elementor Pro

### Step 4: Configure Elementor (If Installed)

1. **Enable Theme Locations:**
   - Go to **Elementor > Settings**
   - Under "Theme Builder", enable locations you want to customize:
     - Header
     - Footer
     - Single Post
     - Archive
   
2. **Create Custom Templates (Optional):**
   - Go to **Templates > Theme Builder**
   - Create custom headers, footers, or single post templates
   - Assign to appropriate locations

### Step 5: Set Up Reading Settings

1. **Configure Homepage:**
   - Go to **Settings > Reading**
   - Choose homepage display:
     - Latest posts (blog)
     - Static page (select a page)
   
2. **Set Posts Per Page:**
   - Recommended: 9-12 for video archives (works well with grid layout)
   - Default: 10 posts per page

## Verification Checklist

After installation, verify these items:

- [ ] Theme appears in **Appearance > Themes**
- [ ] Theme activates without errors
- [ ] Site frontend loads properly
- [ ] Navigation menu displays correctly
- [ ] Video archives display in grid layout (if Videohub360 plugin active)
- [ ] Responsive design works on mobile devices
- [ ] Customizer options work correctly
- [ ] No PHP errors in error logs

## Troubleshooting

### Theme Not Appearing in Themes List

**Solution:**
- Ensure the theme folder is directly in `/wp-content/themes/`
- Verify `style.css` exists and has proper theme header
- Check file permissions (folders: 755, files: 644)

### Blank White Screen After Activation

**Solution:**
- Check PHP error logs
- Verify PHP version is 7.4 or higher
- Ensure all theme files were uploaded correctly
- Try re-uploading the theme

### Styling Issues or Missing CSS

**Solution:**
- Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
- Clear WordPress cache if using cache plugin
- Regenerate CSS in **Appearance > Customize > Publish**

### Videos Not Displaying Properly

**Solution:**
- Ensure Videohub360 plugin is installed and activated
- Check video custom post type is registered
- Verify video thumbnails are uploaded
- Clear cache and reload

### Elementor Not Working

**Solution:**
- Verify Elementor plugin is installed and activated
- Update Elementor to latest version
- Check Elementor system requirements
- Go to **Elementor > Settings** and check compatibility

## Next Steps

After successful installation:

1. **Create Content:**
   - Upload videos using Videohub360 plugin
   - Create pages and posts
   - Organize with categories

2. **Customize Design:**
   - Use Elementor to design custom layouts
   - Add widgets to sidebar and footer
   - Customize colors to match your brand

3. **Optimize Performance:**
   - Install caching plugin (WP Super Cache, W3 Total Cache)
   - Optimize images (Smush, ShortPixel)
   - Enable CDN if available

4. **Test Thoroughly:**
   - Test on different devices
   - Check all page templates
   - Verify video playback
   - Test navigation and forms

## Support Resources

- **Theme Documentation:** README.md file in theme directory
- **WordPress Codex:** https://codex.wordpress.org/
- **Elementor Documentation:** https://elementor.com/help/
- **Videohub360 Plugin:** Check plugin documentation

## Updating the Theme

When updates are available:

1. **Backup First:**
   - Backup your entire WordPress site
   - Export your customizer settings if possible

2. **Update Method:**
   - Download new version
   - Deactivate theme (switch to another theme temporarily)
   - Delete old version via FTP
   - Upload new version
   - Reactivate theme

3. **Verify After Update:**
   - Check all pages load correctly
   - Verify customizations are intact
   - Test video functionality
   - Clear all caches

## Getting Help

If you need assistance:

1. Check this installation guide thoroughly
2. Review the README.md for feature documentation
3. Check WordPress and PHP error logs
4. Verify system requirements are met
5. Contact theme support with detailed information about your issue

---

**Congratulations!** Your Videohub360 theme is now installed and ready to use. Enjoy creating beautiful video-centric websites!
