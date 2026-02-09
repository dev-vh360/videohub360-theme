<?php
/**
 * Elementor VideoHub360 Videos Widget
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class Elementor_VideoHub360_Videos_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { 
        return 'videohub360_videos'; 
    }
    
    public function get_title() { 
        return __('VideoHub360 Videos Grid/List', 'videohub360'); 
    }
    
    public function get_icon() { 
        return 'eicon-gallery-grid'; 
    }
    
    public function get_categories() { 
        return ['general']; 
    }

    protected function register_controls() {
        // Content Tab - Query Section
        $this->start_controls_section('query_section', [
            'label' => __('Query', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        // Get taxonomy terms for dropdowns
        $categories = get_terms([
            'taxonomy' => 'videohub360_category',
            'hide_empty' => false,
        ]);
        $category_options = ['' => __('All Categories', 'videohub360')];
        if (!is_wp_error($categories)) {
            foreach ($categories as $term) {
                $category_options[$term->slug] = $term->name;
            }
        }
        
        $series = get_terms([
            'taxonomy' => 'videohub360_series',
            'hide_empty' => false,
        ]);
        $series_options = ['' => __('All Series', 'videohub360')];
        if (!is_wp_error($series)) {
            foreach ($series as $term) {
                $series_options[$term->slug] = $term->name;
            }
        }
        
        $locations = get_terms([
            'taxonomy' => 'videohub360_location',
            'hide_empty' => false,
        ]);
        $location_options = ['' => __('All Locations', 'videohub360')];
        if (!is_wp_error($locations)) {
            foreach ($locations as $term) {
                $location_options[$term->slug] = $term->name;
            }
        }
        
        $this->add_control('category', [
            'label' => __('Category', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => $category_options,
        ]);
        
        $this->add_control('series', [
            'label' => __('Series', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => $series_options,
        ]);
        
        $this->add_control('location', [
            'label' => __('Location', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => $location_options,
        ]);
        
        $this->add_control('tag', [
            'label' => __('Tag (slug)', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'placeholder' => __('Enter tag slug', 'videohub360'),
        ]);
        
        $this->end_controls_section();
        
        // Content Tab - Display Section
        $this->start_controls_section('display_section', [
            'label' => __('Display', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('display', [
            'label' => __('Display Style', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'grid',
            'options' => [
                'grid' => __('Grid', 'videohub360'),
                'list' => __('List', 'videohub360'),
            ],
        ]);
        
        $this->add_control('columns', [
            'label' => __('Columns', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto' => __('Auto (Responsive)', 'videohub360'),
                '1' => __('1 Column', 'videohub360'),
                '2' => __('2 Columns', 'videohub360'),
                '3' => __('3 Columns', 'videohub360'),
                '4' => __('4 Columns', 'videohub360'),
            ],
            'condition' => [
                'display' => 'grid',
            ],
        ]);
        
        $this->add_control('posts', [
            'label' => __('Number of Videos', 'videohub360'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 30,
        ]);
        
        $this->add_control('show_views', [
            'label' => __('Show View Count', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Show', 'videohub360'),
            'label_off' => __('Hide', 'videohub360'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);
        
        $this->add_control('show_date', [
            'label' => __('Show Date', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Show', 'videohub360'),
            'label_off' => __('Hide', 'videohub360'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);
        
        $this->add_control('orderby', [
            'label' => __('Order By', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date' => __('Date', 'videohub360'),
                'title' => __('Title', 'videohub360'),
                'views' => __('Views', 'videohub360'),
                'random' => __('Random', 'videohub360'),
            ],
        ]);
        
        $this->add_control('order', [
            'label' => __('Order', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'DESC',
            'options' => [
                'DESC' => __('Descending', 'videohub360'),
                'ASC' => __('Ascending', 'videohub360'),
            ],
        ]);
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes
        $shortcode_atts = [];
        
        $shortcode_atts[] = 'display="' . esc_attr($settings['display']) . '"';
        $shortcode_atts[] = 'posts="' . intval($settings['posts']) . '"';
        
        if (!empty($settings['category'])) {
            $shortcode_atts[] = 'category="' . esc_attr($settings['category']) . '"';
        }
        if (!empty($settings['series'])) {
            $shortcode_atts[] = 'series="' . esc_attr($settings['series']) . '"';
        }
        if (!empty($settings['location'])) {
            $shortcode_atts[] = 'location="' . esc_attr($settings['location']) . '"';
        }
        if (!empty($settings['tag'])) {
            $shortcode_atts[] = 'tag="' . esc_attr($settings['tag']) . '"';
        }
        
        // Layout
        if (!empty($settings['columns']) && $settings['columns'] !== 'auto') {
            $shortcode_atts[] = 'columns="' . esc_attr($settings['columns']) . '"';
        }
        
        // Visibility
        if ($settings['show_views'] === '') {
            $shortcode_atts[] = 'show_views="no"';
        }
        if ($settings['show_date'] === '') {
            $shortcode_atts[] = 'show_date="no"';
        }
        
        // Sorting
        if (!empty($settings['orderby']) && $settings['orderby'] !== 'date') {
            $shortcode_atts[] = 'orderby="' . esc_attr($settings['orderby']) . '"';
        }
        if (!empty($settings['order']) && $settings['order'] !== 'DESC') {
            $shortcode_atts[] = 'order="' . esc_attr($settings['order']) . '"';
        }
        
        // Output shortcode
        $shortcode = '[videohub360_videos ' . implode(' ', $shortcode_atts) . ']';
        
        // Add Elementor context wrapper
        echo '<div class="videohub360-elementor-widget-wrapper">';
        echo do_shortcode($shortcode);
        echo '</div>';
    }
}