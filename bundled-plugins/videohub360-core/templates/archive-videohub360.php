<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
global $wp_query;
$wp_query->is_404 = false;

get_header();

// Get filter selections
$selected_cat    = isset($_GET['videohub360_cat']) ? intval($_GET['videohub360_cat']) : '';
$selected_series = isset($_GET['videohub360_series']) ? intval($_GET['videohub360_series']) : '';
$selected_location = isset($_GET['videohub360_location']) ? intval($_GET['videohub360_location']) : '';
$search_term     = isset($_GET['videohub360_search']) ? sanitize_text_field($_GET['videohub360_search']) : '';

// Get all categories, series, and locations (custom taxonomies)
$videohub360_categories = get_terms([
    'taxonomy' => 'videohub360_category',
    'orderby' => 'name',
    'hide_empty' => false,
]);
$videohub360_series = get_terms([
    'taxonomy' => 'videohub360_series',
    'orderby' => 'name',
    'hide_empty' => false,
]);
$videohub360_locations = get_terms([
    'taxonomy' => 'videohub360_location',
    'orderby' => 'name',
    'hide_empty' => false,
]);

// Build query
$paged = max(1, get_query_var('paged') ? get_query_var('paged') : get_query_var('page'));
$query_args = [
    'post_type'      => 'videohub360',
    'posts_per_page' => get_option('posts_per_page'),
    'paged'          => $paged,
];

// Build taxonomy queries
$tax_query = [];
if ($selected_cat) {
    $tax_query[] = [
        'taxonomy' => 'videohub360_category',
        'field'    => 'term_id',
        'terms'    => $selected_cat,
    ];
}
if ($selected_series) {
    $tax_query[] = [
        'taxonomy' => 'videohub360_series',
        'field'    => 'term_id',
        'terms'    => $selected_series,
    ];
}
if ($selected_location) {
    $tax_query[] = [
        'taxonomy' => 'videohub360_location',
        'field'    => 'term_id',
        'terms'    => $selected_location,
    ];
}

// If we have any taxonomies to query, add relation
if (count($tax_query) > 1) {
    $query_args['tax_query'] = [
        'relation' => 'AND',
    ];
    foreach ($tax_query as $condition) {
        $query_args['tax_query'][] = $condition;
    }
} elseif (count($tax_query) === 1) {
    $query_args['tax_query'] = $tax_query;
}

// Add search if provided
if ($search_term) {
    $query_args['s'] = $search_term;
}

// Exclude community Live Rooms from this archive.
// Live Rooms are videohub360 posts with _vh360_context = 'live_room'.
// We still want to include:
// - Normal videos (context = 'default')
// - Older videos with no _vh360_context meta set.
$meta_query = isset($query_args['meta_query']) && is_array($query_args['meta_query'])
    ? $query_args['meta_query']
    : [];

$meta_query[] = [
    'relation' => 'OR',
    [
        'key'     => '_vh360_context',
        'value'   => 'live_room',
        'compare' => '!=',
    ],
    [
        'key'     => '_vh360_context',
        'compare' => 'NOT EXISTS',
    ],
];

$query_args['meta_query'] = $meta_query;

// Create a custom WP_Query
$videohub360_query = new WP_Query($query_args);

// Store header visibility state for conditional class
$show_header = function_exists('videohub360_show_archive_header') ? videohub360_show_archive_header() : true;
?>
<?php 
// Check if Astra theme is active (header already output via hook)
$is_astra = function_exists('videohub360_is_astra_theme') && videohub360_is_astra_theme();
?>
<?php if ($show_header && !$is_astra): ?>
<div class="videohub360-archive-header">
    <h1 class="videohub360-archive-title">
        <?php echo esc_html( function_exists('videohub360_get_archive_title') ? videohub360_get_archive_title() : __('Archive', 'videohub360') ); ?>
    </h1>
</div>
<?php endif; ?>
<div class="videohub360-archive-mainwrap<?php echo !$show_header ? ' videohub360-no-header' : ''; ?>">
    <?php if (videohub360_show_category_filter() || videohub360_show_series_filter() || videohub360_show_location_filter()): ?>
    <!-- .videohub360-sidebar (desktop) -->
    <aside class="videohub360-sidebar" id="videohub360-sidebar" aria-label="<?php echo esc_attr__('Filter videos', 'videohub360'); ?>" tabindex="-1">
        <button class="videohub360-filter-close-btn" id="videohub360-filter-close-btn" aria-label="<?php echo esc_attr__('Close filter', 'videohub360'); ?>" type="button">&times;</button>
        <h2><?php echo esc_html__('Filter', 'videohub360'); ?></h2>
        <form method="get" action="<?php echo esc_url(get_post_type_archive_link('videohub360')); ?>" class="videohub360-filter-form" id="videohub360-filter-form">
            <?php if ($search_term): ?>
                <input type="hidden" name="videohub360_search" value="<?php echo esc_attr($search_term); ?>">
            <?php endif; ?>
            <?php if (videohub360_show_category_filter()): ?>
            <div class="videohub360-filter-group">
                <label for="videohub360_cat" class="videohub360-filter-label"><?php echo esc_html(videohub360_get_category_filter_label()); ?></label>
                <select name="videohub360_cat" id="videohub360_cat" class="videohub360-filter-select">
                    <option value="">
                        <?php
                        $label = videohub360_get_category_filter_label();
                        // Internationalize 'All {label}s'
                        if ($label === esc_html__('Category', 'videohub360')) {
                            echo esc_html__('All Categories', 'videohub360');
                        } else {
                            /* translators: %s is the plural form of a taxonomy label */
                            printf(esc_html__('All %ss', 'videohub360'), esc_html($label));
                        }
                        ?>
                    </option>
                    <?php foreach ($videohub360_categories as $cat): ?>
                        <option value="<?php echo $cat->term_id; ?>" <?php selected($selected_cat, $cat->term_id); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (videohub360_show_series_filter()): ?>
            <div class="videohub360-filter-group">
                <label for="videohub360_series" class="videohub360-filter-label"><?php echo esc_html(videohub360_get_series_filter_label()); ?></label>
                <select name="videohub360_series" id="videohub360_series" class="videohub360-filter-select">
                    <option value=""><?php printf(esc_html__('All %s', 'videohub360'), esc_html(videohub360_get_series_filter_label())); ?></option>
                    <?php foreach ($videohub360_series as $series): ?>
                        <option value="<?php echo $series->term_id; ?>" <?php selected($selected_series, $series->term_id); ?>>
                            <?php echo esc_html($series->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (videohub360_show_location_filter()): ?>
            <div class="videohub360-filter-group">
                <label for="videohub360_location" class="videohub360-filter-label"><?php echo esc_html(videohub360_get_location_filter_label()); ?></label>
                <select name="videohub360_location" id="videohub360_location" class="videohub360-filter-select">
                    <option value=""><?php printf(esc_html__('All %ss', 'videohub360'), esc_html(videohub360_get_location_filter_label())); ?></option>
                    <?php foreach ($videohub360_locations as $location): ?>
                        <option value="<?php echo $location->term_id; ?>" <?php selected($selected_location, $location->term_id); ?>>
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="videohub360-filter-go-row">
                <button type="submit" class="videohub360-filter-go-btn" id="videohub360-filter-go-btn"><?php echo esc_html__('Go', 'videohub360'); ?></button>
            </div>
        </form>
    </aside>
    <?php endif; ?>
    <div class="videohub360-videos-main-content">
        <!-- Unified search bar -->
        <div class="videohub360-search-bar-wrap">
            <form method="get" action="<?php echo esc_url(get_post_type_archive_link('videohub360')); ?>" class="videohub360-search-bar-form" role="search">
                <label for="videohub360_search" class="vh360-visually-hidden"><?php echo esc_html__('Search videos', 'videohub360'); ?></label>
                <div class="videohub360-search-field">
                    <input
                        type="search"
                        name="videohub360_search"
                        id="videohub360_search"
                        value="<?php echo esc_attr($search_term); ?>"
                        placeholder="<?php echo esc_attr__('Search videos...', 'videohub360'); ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="videohub360-search-submit" aria-label="<?php echo esc_attr__('Search', 'videohub360'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <?php
                // If filters are active, preserve them on search
                if ($selected_cat)      echo '<input type="hidden" name="videohub360_cat" value="' . intval($selected_cat) . '">';
                if ($selected_series)   echo '<input type="hidden" name="videohub360_series" value="' . intval($selected_series) . '">';
                if ($selected_location) echo '<input type="hidden" name="videohub360_location" value="' . intval($selected_location) . '">';
                ?>
            </form>
        </div>
        
        <!-- Filter status indicators (when filters are applied) -->
        <?php if ($selected_cat || $selected_series || $selected_location || $search_term): ?>
        <div class="videohub360-filter-status">
            <div>
                <?php 
                $active_filters = array();
                
                if ($search_term) {
                    $active_filters[] = sprintf(esc_html__('Search: "%s"', 'videohub360'), esc_html($search_term));
                }
                
                if ($selected_cat) {
                    $cat_name = '';
                    foreach ($videohub360_categories as $cat) {
                        if ($cat->term_id == $selected_cat) {
                            $cat_name = $cat->name;
                            break;
                        }
                    }
                    $active_filters[] = videohub360_get_category_filter_label() . ': ' . esc_html($cat_name);
                }
                
                if ($selected_series) {
                    $series_name = '';
                    foreach ($videohub360_series as $series) {
                        if ($series->term_id == $selected_series) {
                            $series_name = $series->name;
                            break;
                        }
                    }
                    $active_filters[] = videohub360_get_series_filter_label() . ': ' . esc_html($series_name);
                }
                
                if ($selected_location) {
                    $location_name = '';
                    foreach ($videohub360_locations as $location) {
                        if ($location->term_id == $selected_location) {
                            $location_name = $location->name;
                            break;
                        }
                    }
                    $active_filters[] = videohub360_get_location_filter_label() . ': ' . esc_html($location_name);
                }
                
                echo esc_html__('Filters:', 'videohub360') . ' ' . implode(' | ', $active_filters);
                ?>
            </div>
            <a href="<?php echo esc_url(get_post_type_archive_link('videohub360')); ?>"><?php echo esc_html__('Clear All Filters', 'videohub360'); ?></a>
        </div>
        <?php endif; ?>
        
        <!-- Responsive Filter Toggle Button (mobile only, right above grid) -->
        <?php if (videohub360_show_category_filter() || videohub360_show_series_filter() || videohub360_show_location_filter()): ?>
        <button class="videohub360-filter-toggle-btn" id="videohub360-filter-toggle-btn" aria-controls="videohub360-sidebar" aria-expanded="false" type="button">
            <?php echo esc_html__('☰ Show Filters', 'videohub360'); ?>
        </button>
        <div id="videohub360-filter-overlay"></div>
        <?php endif; ?>
        
        <?php if ($videohub360_query->have_posts()): ?>
        <div class="videohub360-videos-grid">
        <?php
            $widgets = VideoHub360_Core::get_instance()->get_component('widgets');
            while ($videohub360_query->have_posts()) : $videohub360_query->the_post();
                if ($widgets && method_exists($widgets, 'render_video_card')) {
                    echo $widgets->render_video_card(get_the_ID(), array(
                        'show_author'       => 'yes',
                        'show_avatar'       => 'yes',
                        'show_views'        => 'yes',
                        'show_date'         => 'yes',
                        'show_excerpt'      => 'no',
                        'show_live_badge'   => 'yes',
                        'show_live_viewers' => 'yes',
                        'use_archive_live_badge_vars' => true,
                    ));
                }
            endwhile;
        ?>
        </div>
        <?php
        // Custom pagination with our custom query
        echo '<div class="videohub360-pagination">' . paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $videohub360_query->max_num_pages,
            'prev_text' => __('« Prev', 'videohub360'),
            'next_text' => __('Next »', 'videohub360'),
        )) . '</div>';
        else: ?>
            <div class="videohub360-no-videos-message">
                <p><?php echo esc_html__('No videos found.', 'videohub360'); ?></p>
            </div>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
</div>
<?php get_footer(); ?>
