<?php
/**
 * VideoHub360 Shortcode Builder Class
 * 
 * Handles shortcode generation and AJAX endpoints for the shortcode builder
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Shortcode_Builder {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX endpoint for shortcode preview
        add_action('wp_ajax_vh360_preview_shortcode', array($this, 'ajax_preview_shortcode'));
        
        // AJAX endpoint for getting taxonomy terms
        add_action('wp_ajax_vh360_get_taxonomy_terms', array($this, 'ajax_get_taxonomy_terms'));
    }
    
    /**
     * AJAX handler for shortcode preview
     */
    public function ajax_preview_shortcode() {
        // Verify nonce
        check_ajax_referer('vh360_shortcode_builder', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to preview shortcodes.', 'videohub360')
            ));
        }
        
        // Get and validate shortcode - use wp_unslash to preserve brackets and quotes
        $shortcode = isset($_POST['shortcode']) ? wp_unslash($_POST['shortcode']) : '';
        
        // Basic validation to ensure it looks like a shortcode
        if (empty($shortcode) || strpos($shortcode, '[') !== 0 || strpos($shortcode, ']') === false) {
            wp_send_json_error(array(
                'message' => __('Invalid shortcode format.', 'videohub360')
            ));
        }
        
        // Execute shortcode and capture output
        ob_start();
        echo do_shortcode($shortcode);
        $output = ob_get_clean();
        
        // Return preview HTML
        wp_send_json_success(array(
            'html' => $output,
            'shortcode' => esc_html($shortcode)
        ));
    }
    
    /**
     * AJAX handler for getting taxonomy terms
     */
    public function ajax_get_taxonomy_terms() {
        // Verify nonce
        check_ajax_referer('vh360_shortcode_builder', 'nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access taxonomy data.', 'videohub360')
            ));
        }
        
        // Get taxonomy terms
        $taxonomies = array(
            'videohub360_category' => array(
                'label' => __('Categories', 'videohub360'),
                'terms' => array()
            ),
            'videohub360_series' => array(
                'label' => __('Series', 'videohub360'),
                'terms' => array()
            ),
            'videohub360_location' => array(
                'label' => __('Locations', 'videohub360'),
                'terms' => array()
            )
        );
        
        foreach ($taxonomies as $tax_name => $tax_data) {
            $terms = get_terms(array(
                'taxonomy' => $tax_name,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $taxonomies[$tax_name]['terms'][] = array(
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'count' => $term->count
                    );
                }
            }
        }
        
        wp_send_json_success($taxonomies);
    }
    
    /**
     * Get hero banner parameters configuration
     */
    public static function get_hero_parameters() {
        return array(
            'mode' => array(
                'label' => __('Mode', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'single' => __('Single', 'videohub360'),
                    'slider' => __('Slider', 'videohub360')
                ),
                'default' => 'single',
                'description' => __('Display mode: single slide or multi-slide carousel', 'videohub360')
            ),
            'layout' => array(
                'label' => __('Layout', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'video_left' => __('Video Left', 'videohub360'),
                    'video_right' => __('Video Right', 'videohub360')
                ),
                'default' => 'video_left',
                'description' => __('Position of video/image relative to content', 'videohub360')
            ),
            'theme' => array(
                'label' => __('Theme', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'light' => __('Light', 'videohub360'),
                    'dark' => __('Dark', 'videohub360'),
                    'transparent' => __('Transparent', 'videohub360'),
                    'custom' => __('Custom', 'videohub360')
                ),
                'default' => 'light',
                'description' => __('Color theme for the hero section', 'videohub360')
            ),
            'aspect_ratio' => array(
                'label' => __('Aspect Ratio', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    '16:9' => '16:9',
                    '4:3' => '4:3',
                    '1:1' => '1:1'
                ),
                'default' => '16:9',
                'description' => __('Video/image aspect ratio', 'videohub360')
            ),
            'video_type' => array(
                'label' => __('Video Type', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'thumbnail' => __('Thumbnail', 'videohub360'),
                    'mp4' => __('MP4 Video', 'videohub360'),
                    'embed' => __('Embed', 'videohub360'),
                    'html' => __('HTML', 'videohub360')
                ),
                'default' => 'thumbnail',
                'description' => __('Type of media to display', 'videohub360')
            ),
            'poster' => array(
                'label' => __('Poster URL', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Poster/thumbnail image URL', 'videohub360')
            ),
            'video_url' => array(
                'label' => __('Video URL', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('MP4 video file URL', 'videohub360')
            ),
            'embed_url' => array(
                'label' => __('Embed URL', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('YouTube/Vimeo/Twitch embed URL', 'videohub360')
            ),
            'headline' => array(
                'label' => __('Headline', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Main headline text', 'videohub360')
            ),
            'subhead' => array(
                'label' => __('Subheadline', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Subheadline text', 'videohub360')
            ),
            'eyebrow' => array(
                'label' => __('Eyebrow', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Small badge/eyebrow text above headline', 'videohub360')
            ),
            'cta1_label' => array(
                'label' => __('CTA 1 Label', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Primary call-to-action button label', 'videohub360')
            ),
            'cta1_url' => array(
                'label' => __('CTA 1 URL', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Primary CTA button URL', 'videohub360')
            ),
            'video_autoplay' => array(
                'label' => __('Video Autoplay', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'videohub360'),
                    'yes' => __('Yes', 'videohub360')
                ),
                'default' => 'no',
                'description' => __('Auto-play video (muted only)', 'videohub360')
            ),
            'video_loop' => array(
                'label' => __('Video Loop', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'videohub360'),
                    'yes' => __('Yes', 'videohub360')
                ),
                'default' => 'no',
                'description' => __('Loop video playback', 'videohub360')
            )
        );
    }
    
    /**
     * Get video grid/list parameters configuration
     */
    public static function get_video_grid_parameters() {
        return array(
            'display' => array(
                'label' => __('Display Type', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'grid' => __('Grid', 'videohub360'),
                    'list' => __('List', 'videohub360')
                ),
                'default' => 'grid',
                'description' => __('Layout type: grid or list view', 'videohub360')
            ),
            'posts' => array(
                'label' => __('Posts Count', 'videohub360'),
                'type' => 'number',
                'min' => 1,
                'max' => 30,
                'default' => 6,
                'description' => __('Number of videos to display (1-30)', 'videohub360')
            ),
            'columns' => array(
                'label' => __('Columns', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'auto' => __('Auto', 'videohub360'),
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4'
                ),
                'default' => 'auto',
                'description' => __('Number of columns in grid layout', 'videohub360')
            ),
            'category' => array(
                'label' => __('Category Slug', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Filter by video category slug', 'videohub360')
            ),
            'series' => array(
                'label' => __('Series Slug', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Filter by video series slug', 'videohub360')
            ),
            'location' => array(
                'label' => __('Location Slug', 'videohub360'),
                'type' => 'text',
                'default' => '',
                'description' => __('Filter by video location slug', 'videohub360')
            ),
            'orderby' => array(
                'label' => __('Order By', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'date' => __('Date', 'videohub360'),
                    'title' => __('Title', 'videohub360'),
                    'views' => __('Views', 'videohub360'),
                    'menu_order' => __('Menu Order', 'videohub360'),
                    'rand' => __('Random', 'videohub360')
                ),
                'default' => 'date',
                'description' => __('Field to sort videos by', 'videohub360')
            ),
            'order' => array(
                'label' => __('Order', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'DESC' => __('Descending', 'videohub360'),
                    'ASC' => __('Ascending', 'videohub360')
                ),
                'default' => 'DESC',
                'description' => __('Sort direction', 'videohub360')
            ),
            'live_only' => array(
                'label' => __('Live Only', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'videohub360'),
                    'yes' => __('Yes', 'videohub360')
                ),
                'default' => 'no',
                'description' => __('Show only livestream videos', 'videohub360')
            ),
            'show_excerpt' => array(
                'label' => __('Show Excerpt', 'videohub360'),
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'videohub360'),
                    'yes' => __('Yes', 'videohub360')
                ),
                'default' => 'no',
                'description' => __('Display video excerpt/description', 'videohub360')
            ),
            'excerpt_length' => array(
                'label' => __('Excerpt Length', 'videohub360'),
                'type' => 'number',
                'min' => 50,
                'max' => 500,
                'default' => 120,
                'description' => __('Maximum excerpt characters (50-500)', 'videohub360')
            )
        );
    }
}
