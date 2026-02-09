# Featured Image Upload - UI Guide

## Visual Overview

### Form Layout (Desktop)

The Go Live form now includes a new "Featured Image (optional)" field positioned between the "Description" field and the "Livestream Mode" selector.

```
┌─────────────────────────────────────────────────────────────┐
│                    Create a Live Room                        │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Live Room Title *                                           │
│  [Ask Me Anything, Office Hours, Product...]                 │
│                                                               │
│  Description (optional)                                      │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Let your community know what to expect...            │  │
│  │                                                        │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  Featured Image (optional)                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  [Choose File]  No file chosen                        │  │
│  └──────────────────────────────────────────────────────┘  │
│  Upload a thumbnail image for your Live Room.                │
│  Supported formats: JPG, PNG, GIF, WebP (Max 5MB)           │
│                                                               │
│  Livestream Mode                                             │
│  [Interactive (host + guests can speak)            ▼]       │
│                                                               │
│  Agora Channel Name (optional)                               │
│  [Leave blank to auto-generate...]                          │
│                                                               │
│  [Create Live Room]                                          │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### With Image Selected (Preview State)

When a user selects an image file, a preview appears below the file input:

```
┌─────────────────────────────────────────────────────────────┐
│  Featured Image (optional)                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  [Choose File]  my-thumbnail.jpg                      │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                                                   [×] │  │
│  │  ┌──────────────────────────────────────────────┐   │  │
│  │  │                                                │   │  │
│  │  │         [PREVIEW IMAGE APPEARS HERE]          │   │  │
│  │  │                                                │   │  │
│  │  └──────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                               │
│  Upload a thumbnail image for your Live Room.                │
│  Supported formats: JPG, PNG, GIF, WebP (Max 5MB)           │
└─────────────────────────────────────────────────────────────┘
```

## UI Components

### 1. File Input Field
- **Style**: Bordered input with dashed border (indicates drag capability visually)
- **Color**: Light gray background (#f7fafc) with blue border on hover (#4299e1)
- **Button**: "Choose File" button with clean styling
- **Behavior**: Opens file picker on click

### 2. Image Preview Container
- **Display**: Initially hidden, shown via fade-in animation when image selected
- **Style**: Rounded corners, subtle shadow
- **Max Width**: 400px on desktop, 100% on mobile
- **Image Fit**: Cover mode, max height 300px

### 3. Remove Button
- **Position**: Absolute, top-right corner of preview
- **Style**: Circular (32px), semi-transparent black background
- **Icon**: X icon (close symbol)
- **Hover Effect**: Turns red, scales up slightly
- **Action**: Clears file selection and hides preview

### 4. Help Text
- **Position**: Below file input
- **Content**: "Upload a thumbnail image for your Live Room. Supported formats: JPG, PNG, GIF, WebP (Max 5MB)"
- **Style**: Smaller, muted text color

## User Interactions

### Happy Path
1. User clicks "Choose File" button
2. File picker dialog opens
3. User selects an image file
4. Preview immediately appears with selected image
5. User can continue filling other fields
6. User submits form
7. Live room is created with featured image

### Remove Image
1. User clicks the [×] button on preview
2. Preview fades out
3. File input is cleared
4. User can select a different image if desired

### Invalid File Type
1. User selects a non-image file (e.g., PDF)
2. Alert appears: "Please select a valid image file (JPG, PNG, GIF, or WebP)."
3. Selection is cleared
4. User must select a valid image

### File Too Large
1. User selects an image larger than 5MB
2. Alert appears: "Image file size must be less than 5MB."
3. Selection is cleared
4. User must select a smaller image

## Responsive Behavior

### Mobile (< 768px)
- File input adjusts to full width
- Preview container uses 100% width (no max-width constraint)
- Button text and padding slightly reduced for better fit
- Touch-friendly button sizes maintained

### Tablet (768px - 1024px)
- Similar to desktop but with adjusted spacing
- Preview container maintains reasonable max-width

### Desktop (> 1024px)
- Full desktop experience as described above
- Ample spacing and larger preview area

## Accessibility Features

- **Keyboard Navigation**: File input is fully keyboard accessible
- **Screen Readers**: Proper labels and ARIA attributes on form fields
- **Focus States**: Clear focus indicators on all interactive elements
- **Alternative Text**: Preview image includes alt text
- **Button Labels**: Remove button has aria-label for screen readers

## Error Handling

### Client-Side Validation
- File type validation happens immediately on selection
- File size validation happens immediately on selection
- Clear error messages via browser alerts

### Server-Side Handling
- Upload errors are silently ignored
- Live room creation is not blocked by upload failures
- Featured image is optional - form works with or without it

## Theme Integration

The feature integrates seamlessly with the existing dashboard design:
- Uses existing CSS variables for colors (--border-color, --primary-color, etc.)
- Matches dashboard card and form styling
- Consistent spacing and typography
- Follows theme's design patterns

## Browser Support

- **Modern Browsers**: Full support (Chrome, Firefox, Safari, Edge)
- **FileReader API**: Required for preview functionality
- **Fallback**: Without JavaScript, file input still works (no preview)

## Visual Design Details

### Colors
- **File Input Border**: #cbd5e0 (gray-300)
- **File Input Hover**: #4299e1 (blue-500)
- **Background**: #f7fafc (gray-50)
- **Button Hover**: #4299e1 (blue-500) with white text
- **Remove Button**: rgba(0,0,0,0.6)
- **Remove Hover**: #e53935 (red-600)

### Spacing
- Form group margin: 16px
- Input padding: 12px
- Preview margin-top: 16px
- Button padding: 8px 16px

### Borders
- File input: 2px dashed
- Preview container: Rounded corners (8px)
- Button: Rounded corners (6px, 50% for remove button)

### Shadows
- Preview: 0 2px 8px rgba(0,0,0,0.1)
- Focus: 0 0 0 3px rgba(66,153,225,0.1)

## Future Enhancement Mockups

### Potential Drag-and-Drop Area
```
┌─────────────────────────────────────────────────────┐
│         [Upload Icon]                                │
│                                                       │
│    Drag and drop your image here                    │
│         or click to browse                           │
│                                                       │
│    JPG, PNG, GIF, WebP • Max 5MB                    │
└─────────────────────────────────────────────────────┘
```

### Potential Image Editor
Could add basic editing tools after image selection:
- Crop to aspect ratio
- Rotate
- Adjust brightness/contrast
- Apply filters

These enhancements are not included in the current implementation but could be added in future iterations.
