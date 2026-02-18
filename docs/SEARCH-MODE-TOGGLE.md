# Search Mode Toggle Feature

## Overview
The Videohub360 theme now includes a flexible search system that can display results in two different modes:
- **Grouped Mode** (default): Results organized by content type with filter tabs and category headings
- **Unified Mode**: Single flat list of results without categories or filters

## Customizer Setting

**Location:** Appearance > Customize > Site Header  
**Setting Name:** "Group Search Results by Content Type"  
**Default:** Enabled (Grouped Mode)

When disabled, the search will display results as a single unified list without filter tabs or category headings.

## Technical Architecture

### 1. Helper Functions (`includes/search/search-helpers.php`)

#### `vh360_get_available_search_types()`
Returns an ordered array of available search types based on registered post types.

**Returns:**
```php
array(
    'videos' => array(
        'key' => 'videos',
        'post_type' => 'videohub360',
        'label' => 'Videos'
    ),
    'members' => array(
        'key' => 'members',
        'post_type' => 'user',
        'label' => 'Members'
    ),
    // ... other types
)
```

**Order of Types:**
1. Videos (videohub360)
2. Members (users)
3. Events (vh360_event)
4. Galleries (vh360_gallery)
5. Bulletins (vh360_bulletin)
6. Community Posts (vh360_post)

#### `vh360_get_available_search_type_keys()`
Returns array of type keys only (e.g., `['videos', 'members', 'events']`)

#### `vh360_is_search_type_available($type)`
Checks if a specific search type is available.

### 2. Template Changes (`template-parts/header/search-bar-centered.php`)

**Grouped Mode:**
- Renders filter tabs for all available content types
- "All" tab always present
- Only shows tabs for registered post types

**Unified Mode:**
- No filter tabs rendered at all
- Cleaner, simpler interface
- Search input and results dropdown still fully functional

### 3. JavaScript (`assets/js/search-bar-centered.js`)

#### Configuration
```javascript
const isGroupedMode = vh360SearchBar.groupResults;
const availableTypes = vh360SearchBar.availableTypes;
```

#### Display Functions

**`displayGroupedResults(results, query)`**
- Shows results organized by content type
- Renders category headings (VIDEOS, MEMBERS, etc.)
- Maintains order based on `availableTypes`
- Filter tabs functional

**`displayUnifiedResults(results, query)`
- Flattens results into single array
- No category headings
- Maintains order based on `availableTypes`
- All results rendered in sequence

### 4. AJAX Handler (`includes/ajax-handlers-search.php`)

**Key Features:**
- Only queries registered/available post types
- Invalid type requests default to 'all'
- Debug logging when invalid types requested (WP_DEBUG mode)
- Reduces database queries for unused modules

### 5. CSS Styles (`assets/css/search-bar-centered.css`)

**Unified Mode Styles (`.vh360-search-unified`):**
```css
/* Hide filter tabs */
.vh360-search-unified .vh360-search-bar-centered__filters {
    display: none;
}

/* Hide group titles */
.vh360-search-unified .vh360-search-bar-centered__result-group-title {
    display: none;
}

/* Prevent focus on hidden tabs */
.vh360-search-unified .vh360-search-bar-centered__filter-tab {
    pointer-events: none;
    visibility: hidden;
    position: absolute;
    left: -9999px;
}
```

### 6. Body Class Filter (`functions.php`)

```php
function vh360_search_mode_body_class($classes) {
    $group_results = get_theme_mod('vh360_search_group_results', true);
    if (!$group_results) {
        $classes[] = 'vh360-search-unified';
    }
    return $classes;
}
add_filter('body_class', 'vh360_search_mode_body_class');
```

## Accessibility Features

### Grouped Mode
- Filter tabs are keyboard accessible
- Proper ARIA attributes on all interactive elements
- Tab navigation works through filters and results

### Unified Mode
- Filter tabs removed from DOM (not just hidden)
- No hidden focus traps
- Keyboard navigation still functional for results
- ESC key closes dropdown
- Enter key navigates to first result

## Performance Optimizations

1. **Post Type Detection**: Only queries registered post types
2. **No Unnecessary Queries**: Unused modules don't trigger database queries
3. **Body Class**: CSS changes applied via body class (no JS hiding after paint)
4. **Debounced Search**: 300ms debounce prevents excessive AJAX calls

## Extending the System

### Adding Custom Search Types

Use the `vh360_available_search_types` filter:

```php
add_filter('vh360_available_search_types', function($types) {
    if (post_type_exists('my_custom_type')) {
        $types['custom'] = array(
            'key' => 'custom',
            'post_type' => 'my_custom_type',
            'label' => __('Custom Type', 'my-textdomain'),
        );
    }
    return $types;
});
```

### Adding Search Handler for Custom Type

```php
function vh360_search_custom_type($query) {
    $args = array(
        'post_type' => 'my_custom_type',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        's' => $query,
    );
    
    $query_obj = new WP_Query($args);
    $results = array();
    
    // ... build results array
    
    return $results;
}
```

## Browser Compatibility

Tested and compatible with:
- Chrome/Edge (Chromium)
- Firefox
- Safari
- Mobile browsers (iOS Safari, Chrome Mobile)

## Migration Notes

**Backward Compatibility:**
- Default setting is `true` (Grouped Mode)
- Existing sites maintain current behavior
- No database migration required
- Safe to deploy without user action

## Debug Mode

When `WP_DEBUG` is enabled, the AJAX handler logs invalid search type requests:
```
VH360 Search: Invalid search type requested: invalid_type. Defaulting to "all".
```

## Future Enhancements

Possible improvements:
- Search result caching
- Highlighting in excerpts
- Advanced filtering options
- Search analytics
- Recent searches
