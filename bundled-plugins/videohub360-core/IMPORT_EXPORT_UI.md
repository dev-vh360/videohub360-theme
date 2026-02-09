# VideoHub360 Import/Export - User Interface Guide

## Admin Menu Location

The Import/Export feature is accessible from the WordPress admin sidebar:

```
WordPress Admin
└── VideoHub360
    ├── All Videos
    ├── Add New
    ├── Dashboard
    ├── Categories
    ├── Series
    ├── Locations
    ├── Tags
    ├── Settings
    ├── Analytics
    ├── Shortcodes
    └── Import/Export  ← NEW!
```

## Page Layout

The Import/Export page is divided into two main sections:

### Header Section
- **Page Title**: "Import/Export Videos"
- **Description**: Brief explanation of the feature
- Clean, professional WordPress admin styling

---

## Export Section (Top Half)

### Section Title
📤 **Export Videos**

### Information Box (Blue)
**What gets exported:**
- All post content (title, content, excerpt, status)
- Video URLs (main video, ads, mid-roll, post-roll)
- All meta fields (view counts, livestream settings, quality settings)
- Taxonomy terms (categories, series, locations, tags)
- Featured image URL (reference only, not the file itself)

**Note:** Chat messages and moderation data are not exported.

### Statistics Display
```
Total videos available: 47
```
(Shows current video count in the database)

### Action Button
```
┌─────────────────────────────────┐
│  📥 Export All Videos           │  ← Large primary button
└─────────────────────────────────┘
```

### Export Results Area
Hidden by default. Shows after export completes:

**Success Message (Green Box):**
```
✓ Success! 47 videos exported successfully. 
  Download should start automatically.
```

**Error Message (Red Box):**
```
✗ Error: Failed to communicate with server
```

---

## Import Section (Bottom Half)

### Section Title
📥 **Import Videos**

### Description
"Import videos from a JSON export file created by VideoHub360."

### Upload Form

#### File Selection
```
Select JSON File:
┌────────────────────────────────────┐
│  Choose File  No file chosen       │
└────────────────────────────────────┘
Choose a JSON file exported from VideoHub360
```

#### Duplicate Handling Options
```
Duplicate Handling:

○ Skip duplicates (default)
  Videos with existing titles or slugs will be skipped

○ Update existing videos  
  Overwrite existing videos with the same slug

○ Create new with modified slug
  Import all videos, appending numbers to duplicate slugs
```

### Action Button
```
┌─────────────────────────────────┐
│  📤 Import Videos               │  ← Large primary button
└─────────────────────────────────┘
```

### Import Results Area
Hidden by default. Shows after import completes:

**Success Message (Green Box):**
```
Import Complete!

Imported: 45 videos
Updated: 0 videos
Skipped: 2 videos

Warnings:
• Video "Sample Video 1" already exists and was skipped
• Video "Sample Video 2" already exists and was skipped
```

**Error Message (Red Box):**
```
Import Failed

Error: Invalid JSON format
• Video 3: Missing required field "title"
• Video 5: Invalid post data structure
```

---

## Helper Box (Bottom)

### Information Box (Blue)
💡 **Tip: Export Selected Videos**

To export specific videos instead of all videos, go to the Videos list page, select the videos you want to export, and choose "Export Selected" from the bulk actions dropdown.

---

## Bulk Actions on Videos List Page

When on the "All Videos" page:

```
Bulk Actions ▼    [Apply]
├── Edit
├── Move to Trash
└── Export Selected  ← NEW!
```

**Usage:**
1. Check boxes next to videos to export
2. Select "Export Selected" from dropdown
3. Click "Apply"
4. JSON file downloads automatically
5. Success message appears at top of page

---

## User Interactions

### Export All Videos Flow

1. **Click Button**: User clicks "Export All Videos"
2. **Button Changes**: 
   - Text changes to "Exporting..."
   - Button becomes disabled
3. **AJAX Request**: Background request to server
4. **Success**:
   - Success message appears (green box)
   - File downloads automatically
   - Button returns to normal
5. **Error**:
   - Error message appears (red box)
   - Button returns to normal

### Import Videos Flow

1. **Select File**: User clicks "Choose File" and selects JSON
2. **Select Option**: User chooses duplicate handling
3. **Click Button**: User clicks "Import Videos"
4. **Button Changes**:
   - Text changes to "Importing..."
   - Button becomes disabled
5. **AJAX Upload**: File uploads in background
6. **Progress**: Processing happens server-side
7. **Success**:
   - Success message with counts (green box)
   - File input resets
   - Button returns to normal
8. **Error**:
   - Error message with details (red box)
   - Button returns to normal

### Bulk Export Flow

1. **Select Videos**: Check boxes on videos list
2. **Choose Action**: Select "Export Selected" from bulk actions
3. **Click Apply**: Initiate export
4. **Page Reload**: Brief page reload
5. **Success Banner**: Success message at top
6. **Auto Download**: File downloads immediately

---

## Visual Design Elements

### Colors
- **Primary Button**: Blue (#0073aa)
- **Success Box**: Light green background (#d4edda), green border (#c3e6cb)
- **Error Box**: Light red background (#f8d7da), red border (#f5c6cb)
- **Info Box**: Light blue background (#e7f3fe), blue left border (#0073aa)
- **Section Background**: White (#ffffff)
- **Border**: Light gray (#ccd0d4)

### Typography
- **Page Title**: H1, WordPress admin default
- **Section Titles**: H2, with emoji icon
- **Field Labels**: Bold, medium size
- **Descriptions**: Regular weight, slightly smaller
- **Error/Success Text**: Regular weight, good contrast

### Spacing
- **Section Padding**: 20px
- **Element Margins**: 15-20px between elements
- **Border Radius**: 4px on boxes
- **Box Shadow**: Subtle 0 1px 1px rgba(0,0,0,0.04)

### Icons
- 📤 Export icon (down arrow in box)
- 📥 Import icon (up arrow in box)
- ✓ Success checkmark
- ✗ Error X
- 💡 Tip lightbulb
- 📹 Video icon (in dashicons)
- ⚠ Warning triangle

---

## Responsive Behavior

### Desktop (>1200px)
- Full width sections
- Side-by-side when possible
- All elements clearly visible

### Tablet (768px-1199px)
- Sections stack vertically
- Buttons remain full width
- Adequate spacing maintained

### Mobile (<768px)
- All sections stack vertically
- Buttons adapt to screen width
- Text sizes adjust for readability
- File input optimized for mobile

---

## Accessibility Features

### Keyboard Navigation
- All interactive elements are keyboard accessible
- Tab order follows logical flow
- Enter key submits forms
- Escape key closes dialogs (if any)

### Screen Readers
- Proper label associations
- ARIA labels where needed
- Status messages announced
- Error messages clearly identified

### Visual
- High contrast text
- Clear focus indicators
- Large clickable targets
- Readable font sizes

---

## Loading States

### Export Button States
```
Default:    [📥 Export All Videos]
Loading:    [Exporting...]  (disabled, slightly grayed)
Success:    [📥 Export All Videos]  (returns to default)
Error:      [📥 Export All Videos]  (returns to default)
```

### Import Button States
```
Default:    [📤 Import Videos]
Loading:    [Importing...]  (disabled, slightly grayed)
Success:    [📤 Import Videos]  (returns to default)
Error:      [📤 Import Videos]  (returns to default)
```

---

## Error Messages

### Common Error Types

**File Upload Errors:**
- "No file uploaded or upload error"
- "Invalid file type. Please upload a JSON file."
- "File is too large. Maximum size is 10MB."

**JSON Errors:**
- "Invalid JSON format"
- "Missing videohub360_export key"
- "Missing required fields in export data"

**Permission Errors:**
- "Insufficient permissions"

**Network Errors:**
- "Failed to communicate with server"

---

## Success Messages

### Export Success
```
Success! X videos exported successfully. 
Download should start automatically.
```

### Import Success
```
Import Complete!

Imported: X videos
Updated: Y videos
Skipped: Z videos

[Warnings/Errors if any]
```

---

## Best Practices for UI Usage

1. **Before Export**: Check video count is as expected
2. **After Export**: Verify file downloaded successfully
3. **Before Import**: Read duplicate handling options carefully
4. **After Import**: Review results summary completely
5. **For Errors**: Note error messages for troubleshooting
6. **For Large Imports**: Be patient - may take time
7. **File Management**: Save exports with descriptive names

---

## Future UI Enhancements (Potential)

- Progress bar for large imports/exports
- Preview of import file contents
- Select specific videos from import file
- Export/import settings profiles
- Drag-and-drop file upload
- Real-time validation feedback
- Export schedule configuration
- Import history log

---

This UI design provides a clean, professional, and user-friendly experience that integrates seamlessly with WordPress admin design standards while maintaining VideoHub360's branding and functionality.
