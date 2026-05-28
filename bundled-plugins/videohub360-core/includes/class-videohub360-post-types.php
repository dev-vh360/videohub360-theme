<?php
/**
 * VideoHub360 Post Types Class
 * 
 * Handles custom post type and taxonomy registration
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->register_helper_functions();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('pre_get_posts', array($this, 'modify_main_query'));
        add_filter('query_vars', array($this, 'register_query_vars'));
    }
    
    /**
     * Register helper functions as global functions
     */
    private function register_helper_functions() {
        if (!function_exists('videohub360_show_category_filter')) {
            function videohub360_show_category_filter() {
                return get_option('videohub360_show_category_filter', 1);
            }
        }
        
        if (!function_exists('videohub360_show_series_filter')) {
            function videohub360_show_series_filter() {
                return get_option('videohub360_show_series_filter', 1);
            }
        }
        
        if (!function_exists('videohub360_show_location_filter')) {
            function videohub360_show_location_filter() {
                return get_option('videohub360_show_location_filter', 1);
            }
        }
        
        if (!function_exists('videohub360_get_category_filter_label')) {
            function videohub360_get_category_filter_label() {
                // Default fallback is translated; admins can override via option
                return get_option('videohub360_category_label', esc_html__('Category', 'videohub360'));
            }
        }

        if (!function_exists('videohub360_get_series_filter_label')) {
            function videohub360_get_series_filter_label() {
                return get_option('videohub360_series_label', esc_html__('Series', 'videohub360'));
            }
        }

        if (!function_exists('videohub360_get_location_filter_label')) {
            function videohub360_get_location_filter_label() {
                return get_option('videohub360_location_label', esc_html__('Location', 'videohub360'));
            }
        }
        
        // Do not redeclare videohub360_compact_views here; the helper is defined in the core class.
    
        if (!function_exists('videohub360_show_archive_header')) {
            function videohub360_show_archive_header() {
                return (int) get_option('videohub360_show_archive_header', 1);
            }
        }
        if (!function_exists('videohub360_get_archive_title')) {
            function videohub360_get_archive_title() {
                $default = esc_html__('Archive', 'videohub360');
                $title = get_option('videohub360_archive_title', $default) ?: $default;
                return apply_filters('videohub360/archive_title', $title);
            }
        }
}
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        $labels = array(
            'name'               => _x('Videos', 'Post Type General Name', 'videohub360'),
            'singular_name'      => _x('VideoHub360 Video', 'Post Type Singular Name', 'videohub360'),
            'add_new'            => esc_html__('Add New Video', 'videohub360'),
            'add_new_item'       => esc_html__('Add New Video', 'videohub360'),
            'edit_item'          => esc_html__('Edit VideoHub360 Video', 'videohub360'),
            'new_item'           => esc_html__('New VideoHub360 Video', 'videohub360'),
            'view_item'          => esc_html__('View VideoHub360 Video', 'videohub360'),
            'search_items'       => esc_html__('Search VideoHub360 Videos', 'videohub360'),
            'not_found'          => esc_html__('No videos found', 'videohub360'),
            'not_found_in_trash' => esc_html__('No videos found in Trash', 'videohub360'),
            'parent_item_colon'  => '',
            'menu_name'          => esc_html__('VideoHub360', 'videohub360')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'author' ),
            'has_archive' => true,
            'rewrite' => array( 'slug' => $this->get_post_slug() ),
            'show_in_rest' => true,
        );
        register_post_type( 'videohub360', $args );
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('videohub360_category', 'videohub360', array(
            'labels' => array(
                'name'              => esc_html__('Video Categories', 'videohub360'),
                'singular_name'     => esc_html__('Video Category', 'videohub360'),
                'search_items'      => esc_html__('Search Video Categories', 'videohub360'),
                'all_items'         => esc_html__('All Video Categories', 'videohub360'),
                'parent_item'       => esc_html__('Parent Video Category', 'videohub360'),
                'parent_item_colon' => esc_html__('Parent Video Category:', 'videohub360'),
                'edit_item'         => esc_html__('Edit Video Category', 'videohub360'),
                'update_item'       => esc_html__('Update Video Category', 'videohub360'),
                'add_new_item'      => esc_html__('Add New Video Category', 'videohub360'),
                'new_item_name'     => esc_html__('New Video Category Name', 'videohub360'),
                'menu_name'         => esc_html__('Video Categories', 'videohub360'),
            ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite'      => array('slug' => $this->get_category_slug()),
            'query_var'    => true,
        ));

        register_taxonomy('videohub360_location', 'videohub360', array(
            'labels' => array(
                'name'                       => esc_html__('Locations', 'videohub360'),
                'singular_name'              => esc_html__('Location', 'videohub360'),
                'search_items'               => esc_html__('Search Locations', 'videohub360'),
                'popular_items'              => esc_html__('Popular Locations', 'videohub360'),
                'all_items'                  => esc_html__('All Locations', 'videohub360'),
                'edit_item'                  => esc_html__('Edit Location', 'videohub360'),
                'update_item'                => esc_html__('Update Location', 'videohub360'),
                'add_new_item'               => esc_html__('Add New Location', 'videohub360'),
                'new_item_name'              => esc_html__('New Location Name', 'videohub360'),
                'separate_items_with_commas' => esc_html__('Separate locations with commas', 'videohub360'),
                'add_or_remove_items'        => esc_html__('Add or remove locations', 'videohub360'),
                'choose_from_most_used'      => esc_html__('Choose from the most used locations', 'videohub360'),
                'menu_name'                  => esc_html__('Locations', 'videohub360'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => $this->get_location_slug()),
            'query_var'         => true,
        ));

        register_taxonomy('videohub360_tag', 'videohub360', array(
            'labels' => array(
                'name'          => esc_html__('Video Tags', 'videohub360'),
                'singular_name' => esc_html__('Video Tag', 'videohub360'),
                'search_items'  => esc_html__('Search Video Tags', 'videohub360'),
                'all_items'     => esc_html__('All Video Tags', 'videohub360'),
                'edit_item'     => esc_html__('Edit Video Tag', 'videohub360'),
                'update_item'   => esc_html__('Update Video Tag', 'videohub360'),
                'add_new_item'  => esc_html__('Add New Video Tag', 'videohub360'),
                'new_item_name' => esc_html__('New Video Tag Name', 'videohub360'),
                'menu_name'     => esc_html__('Video Tags', 'videohub360'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'video-tag'),
        ));

        register_taxonomy('videohub360_series', 'videohub360', array(
            'labels' => array(
                'name'              => esc_html__('Series', 'videohub360'),
                'singular_name'     => esc_html__('Series', 'videohub360'),
                'search_items'      => esc_html__('Search Series', 'videohub360'),
                'all_items'         => esc_html__('All Series', 'videohub360'),
                'parent_item'       => esc_html__('Parent Series', 'videohub360'),
                'parent_item_colon' => esc_html__('Parent Series:', 'videohub360'),
                'edit_item'         => esc_html__('Edit Series', 'videohub360'),
                'update_item'       => esc_html__('Update Series', 'videohub360'),
                'add_new_item'      => esc_html__('Add New Series', 'videohub360'),
                'new_item_name'     => esc_html__('New Series Name', 'videohub360'),
                'menu_name'         => esc_html__('Series', 'videohub360'),
            ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite'      => array('slug' => $this->get_series_slug()),
            'query_var'    => true,
        ));
    }
    
    /**
     * Modify main query
     */
    public function modify_main_query($query) {
        if (
            !is_admin() && $query->is_main_query() &&
            (
                isset($_GET['videohub360_series']) ||
                isset($_GET['videohub360_location']) ||
                isset($_GET['videohub360_cat']) ||
                isset($_GET['videohub360_search'])
            )
        ) {
            $query->is_archive = true;
            $query->is_post_type_archive = true;
            $query->is_home = false;
            $query->is_tax = false;
            $query->is_404 = false;
        }
    }
    
    /**
     * Register query vars for filtering
     */
    public function register_query_vars($vars) {
        $vars[] = 'videohub360_cat';
        $vars[] = 'videohub360_series';
        $vars[] = 'videohub360_location'; 
        $vars[] = 'videohub360_search';
        return $vars;
    }
    
    /**
     * Get post slug from options
     */
    private function get_post_slug() {
        return get_option('videohub360_post_slug', 'videohub360');
    }
    
    /**
     * Get category slug from options
     */
    private function get_category_slug() {
        return get_option('videohub360_category_slug', 'videohub360-category');
    }
    
    /**
     * Get location slug from options
     */
    private function get_location_slug() {
        return get_option('videohub360_location_slug', 'videohub360-location');
    }
    
    /**
     * Get series slug from options
     */
    private function get_series_slug() {
        return get_option('videohub360_series_slug', 'videohub360-series');
    }
}
