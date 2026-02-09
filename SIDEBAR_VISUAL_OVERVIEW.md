# Sidebar Control System - Visual Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    VIDEOHUB360 SIDEBAR CONTROL SYSTEM                    │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                        1. GLOBAL DEFAULTS (Customizer)                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Appearance → Customize → Global Design → Layout / Sidebar               │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ PAGES                                                               │ │
│  │  Sidebar Layout:  [None] [Left] [Right*]                           │ │
│  │  Default Sidebar: [Primary Sidebar*]                               │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ POSTS                                                               │ │
│  │  Sidebar Layout:  [None] [Left] [Right*]                           │ │
│  │  Default Sidebar: [Primary Sidebar*]                               │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ ARCHIVES                                                            │ │
│  │  Sidebar Layout:  [None] [Left] [Right*]                           │ │
│  │  Default Sidebar: [Primary Sidebar*]                               │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

                                    ↓

┌─────────────────────────────────────────────────────────────────────────┐
│                    2. PER-PAGE OVERRIDES (Meta Box)                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Edit Page/Post → Sidebar Settings (Right Sidebar)                       │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ SIDEBAR SETTINGS                                                    │ │
│  │                                                                      │ │
│  │ Sidebar Layout:                                                     │ │
│  │  ┌─────────────────────────────────────────────────────────────┐  │ │
│  │  │ [Inherit Global (Right Sidebar)]                             │  │ │
│  │  │  No Sidebar                                                   │  │ │
│  │  │  Left Sidebar                                                 │  │ │
│  │  │  Right Sidebar                                                │  │ │
│  │  └─────────────────────────────────────────────────────────────┘  │ │
│  │                                                                      │ │
│  │ Sidebar Selection:                                                  │ │
│  │  ┌─────────────────────────────────────────────────────────────┐  │ │
│  │  │ [Inherit Global (Primary Sidebar)]                           │  │ │
│  │  │  Primary Sidebar                                              │  │ │
│  │  │  Activity Feed Sidebar                                        │  │ │
│  │  └─────────────────────────────────────────────────────────────┘  │ │
│  │                                                                      │ │
│  │ ℹ️  Global settings can be changed in                              │ │
│  │    Appearance → Customize → Layout / Sidebar                       │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

                                    ↓

┌─────────────────────────────────────────────────────────────────────────┐
│                      3. SIDEBAR RESOLVER (Backend)                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Function: vh360_resolve_sidebar()                                       │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ RESOLUTION PRIORITY (Top to Bottom)                                │ │
│  ├────────────────────────────────────────────────────────────────────┤ │
│  │                                                                      │ │
│  │ 1. FORCED RULES (Highest Priority)                                 │ │
│  │    ├─ WooCommerce Checkout      → No Sidebar                       │ │
│  │    ├─ WooCommerce Cart          → No Sidebar                       │ │
│  │    ├─ WooCommerce My Account    → No Sidebar                       │ │
│  │    ├─ Elementor Canvas          → No Sidebar                       │ │
│  │    ├─ Video Archives            → No Sidebar                       │ │
│  │    ├─ Dashboard Template        → No Sidebar                       │ │
│  │    └─ Activity Feed Template    → Activity Feed Sidebar            │ │
│  │                                                                      │ │
│  │                          ↓ (if not forced)                          │ │
│  │                                                                      │ │
│  │ 2. PER-PAGE META OVERRIDES                                         │ │
│  │    ├─ _vh360_sidebar_layout     → none/left/right                  │ │
│  │    └─ _vh360_sidebar_choice     → sidebar ID                       │ │
│  │                                                                      │ │
│  │                          ↓ (if inherit)                             │ │
│  │                                                                      │ │
│  │ 3. GLOBAL CUSTOMIZER DEFAULTS                                      │ │
│  │    ├─ vh360_sidebar_layout_{type}   → none/left/right              │ │
│  │    └─ vh360_sidebar_default_{type}  → sidebar ID                   │ │
│  │                                                                      │ │
│  │                          ↓ (if not set)                             │ │
│  │                                                                      │ │
│  │ 4. HARDCODED DEFAULTS                                              │ │
│  │    ├─ Layout: right                                                │ │
│  │    └─ Sidebar: sidebar-1                                           │ │
│  │                                                                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│  Returns:                                                                 │
│  {                                                                        │
│    show_sidebar: true/false,                                             │
│    sidebar_id:   'sidebar-1',                                            │
│    position:     'left'/'right'                                          │
│  }                                                                        │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

                                    ↓

┌─────────────────────────────────────────────────────────────────────────┐
│                       4. TEMPLATE RENDERING                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Templates: page.php, single.php, archive.php, index.php                │
│                                                                           │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │                                                                      │ │
│  │  IF position = 'left':                                              │ │
│  │                                                                      │ │
│  │  ┌──────────────┬─────────────────────────────────────────────┐   │ │
│  │  │              │                                               │   │ │
│  │  │   SIDEBAR    │           MAIN CONTENT                       │   │ │
│  │  │   (300px)    │           (Fluid)                            │   │ │
│  │  │              │                                               │   │ │
│  │  └──────────────┴─────────────────────────────────────────────┘   │ │
│  │                                                                      │ │
│  │  IF position = 'right':                                             │ │
│  │                                                                      │ │
│  │  ┌─────────────────────────────────────────────┬──────────────┐   │ │
│  │  │                                               │              │   │ │
│  │  │           MAIN CONTENT                       │   SIDEBAR    │   │ │
│  │  │           (Fluid)                            │   (300px)    │   │ │
│  │  │                                               │              │   │ │
│  │  └─────────────────────────────────────────────┴──────────────┘   │ │
│  │                                                                      │ │
│  │  IF no sidebar:                                                     │ │
│  │                                                                      │ │
│  │  ┌────────────────────────────────────────────────────────────┐   │ │
│  │  │                                                              │   │ │
│  │  │                  MAIN CONTENT (Full Width)                  │   │ │
│  │  │                                                              │   │ │
│  │  └────────────────────────────────────────────────────────────┘   │ │
│  │                                                                      │ │
│  │  MOBILE (< 1024px): Sidebar always stacks below content            │ │
│  │                                                                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

                                    ↓

┌─────────────────────────────────────────────────────────────────────────┐
│                     5. BODY CLASSES (Automatic)                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  Added automatically based on configuration:                             │
│                                                                           │
│  • has-sidebar      → When sidebar is displayed                         │
│  • no-sidebar       → When no sidebar                                   │
│  • sidebar-left     → When sidebar on left                              │
│  • sidebar-right    → When sidebar on right                             │
│                                                                           │
│  Example: <body class="page has-sidebar sidebar-right">                 │
│                                                                           │
│  Use in CSS:                                                             │
│  .has-sidebar .content-area { ... }                                     │
│  .sidebar-left .widget-area { ... }                                     │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                    DEVELOPER EXTENSIBILITY                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  FILTERS:                                                                 │
│                                                                           │
│  • vh360_selectable_sidebars         → Add/remove selectable sidebars   │
│  • vh360_sidebar_config              → Override final configuration     │
│  • vh360_sidebar_meta_box_post_types → Add meta box to CPTs             │
│                                                                           │
│  FUNCTIONS:                                                               │
│                                                                           │
│  • vh360_resolve_sidebar()           → Get full configuration           │
│  • vh360_has_sidebar()               → Quick check if has sidebar       │
│  • vh360_get_sidebar_id()            → Get sidebar ID                   │
│  • vh360_get_sidebar_position()      → Get position (left/right)        │
│  • vh360_get_selectable_sidebars()   → Get available sidebars           │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                         TYPICAL USE CASES                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  1. Blog Posts with Sidebar                                              │
│     → Global Default: Right sidebar with Primary Sidebar                │
│     → All posts get sidebar automatically                                │
│                                                                           │
│  2. Landing Pages without Sidebar                                        │
│     → Per-Page Override: No Sidebar                                     │
│     → Clean, full-width landing page                                    │
│                                                                           │
│  3. Shop Pages with Custom Sidebar                                       │
│     → Per-Page Override: Right sidebar with Shop Sidebar                │
│     → E-commerce specific widgets                                        │
│                                                                           │
│  4. WooCommerce Checkout                                                 │
│     → Forced Rule: No Sidebar                                           │
│     → Clean checkout experience                                          │
│                                                                           │
│  5. Activity Feed                                                         │
│     → Forced Rule: Activity Feed Sidebar on right                       │
│     → Trending topics, recommended users                                 │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

## File Structure

```
videohub360-theme/
│
├── includes/
│   ├── sidebar-resolver.php         ← Central Logic
│   ├── sidebar-meta-box.php         ← Per-Page Controls
│   └── customizer/
│       └── sidebar-controls.php     ← Global Defaults
│
├── assets/css/
│   └── sidebar-layout.css           ← Responsive Styles
│
├── page.php                         ← Uses Resolver
├── single.php                       ← Uses Resolver
├── archive.php                      ← Uses Resolver
├── index.php                        ← Uses Resolver
├── sidebar.php                      ← Dynamic Sidebar
│
└── Documentation/
    ├── SIDEBAR_CONTROL_SYSTEM.md    ← Complete Guide
    ├── SIDEBAR_QUICK_REFERENCE.md   ← Developer Reference
    └── SIDEBAR_IMPLEMENTATION_SUMMARY.md
```

## Quick Start for Users

1. **Set Global Defaults**
   - Go to: Appearance → Customize → Global Design → Layout / Sidebar
   - Configure Pages, Posts, and Archives

2. **Override on Specific Pages**
   - Edit any Page/Post
   - Find "Sidebar Settings" meta box (right sidebar)
   - Choose layout and sidebar

3. **Add Widgets**
   - Go to: Appearance → Widgets
   - Add widgets to your sidebar areas

4. **Preview**
   - Changes in Customizer show live preview
   - Per-page changes visible immediately after save

## Quick Start for Developers

```php
// Check if page has sidebar
if (vh360_has_sidebar()) {
    // Get configuration
    $config = vh360_resolve_sidebar();
    echo $config['position']; // 'left' or 'right'
    echo $config['sidebar_id']; // 'sidebar-1', etc.
}

// Add custom sidebar
add_filter('vh360_selectable_sidebars', function($sidebars) {
    $sidebars['my-sidebar'] = 'My Custom Sidebar';
    return $sidebars;
});

// Override behavior
add_filter('vh360_sidebar_config', function($config, $post_id) {
    if (is_singular('portfolio')) {
        $config['sidebar_id'] = 'portfolio-sidebar';
    }
    return $config;
}, 10, 2);
```
