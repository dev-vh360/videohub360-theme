<?php
/**
 * Elementor VideoHub360 Live Now Widget
 * 
 * Displays currently live videos
 * 
 * @since 2.1.0
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class Elementor_VideoHub360_Live_Now_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { 
        return 'videohub360_live_now'; 
    }
    
    public function get_title() { 
        return __('VideoHub360 Live Now', 'videohub360'); 
    }
    
    public function get_icon() { 
        return 'eicon-live'; 
    }
    
    public function get_categories() { 
        return ['general']; 
    }

    protected function register_controls() {
        // Content Tab - Settings Section
        $this->start_controls_section('settings_section', [
            'label' => __('Settings', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('posts_per_page', [
            'label' => __('Number of Videos', 'videohub360'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 30,
        ]);
        
        $this->add_control('columns', [
            'label' => __('Columns', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto' => __('Auto', 'videohub360'),
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
        ]);
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section('display_section', [
            'label' => __('Display Options', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('show_title', [
            'label' => __('Show Title', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_author', [
            'label' => __('Show Author', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_live_viewers', [
            'label' => __('Show Live Viewers', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_views', [
            'label' => __('Show Views', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_date', [
            'label' => __('Show Date', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build query args for live videos
        $query_args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => intval($settings['posts_per_page']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_vh360_is_live',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_vh360_stream_stopped',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => '_vh360_stream_stopped',
                        'value' => 'yes',
                        'compare' => '!='
                    )
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_vh360_context',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => '_vh360_context',
                        'value' => 'live_room',
                        'compare' => '!='
                    )
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($query_args);
        
        if (!$query->have_posts()) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('No live videos at the moment.', 'videohub360') . '</div>';
            wp_reset_postdata();
            return;
        }
        
        // Determine column class
        $column_class = 'auto-columns';
        if ($settings['columns'] !== 'auto') {
            $columns_int = intval($settings['columns']);
            if ($columns_int >= 1 && $columns_int <= 4) {
                $column_class = 'cols-' . $columns_int;
            }
        }
        
        // Prepare card arguments
        $card_args = array(
            'show_author' => $settings['show_author'] ? 'yes' : 'no',
            'show_avatar' => 'yes',
            'show_views' => $settings['show_views'] ? 'yes' : 'no',
            'show_date' => $settings['show_date'] ? 'yes' : 'no',
            'show_excerpt' => 'no',
            'show_live_badge' => 'yes',
            'show_live_viewers' => $settings['show_live_viewers'] ? 'yes' : 'no',
        );
        
        echo '<div class="videohub360-elementor-widget-wrapper videohub360-live-now-widget">';
        echo '<div class="videohub360-videos-grid ' . esc_attr($column_class) . ' ratio-16-9">';
        
        while ($query->have_posts()) {
            $query->the_post();
            echo VideoHub360()->widgets->render_video_card(get_the_ID(), $card_args);
        }
        
        echo '</div>';
        echo '</div>';
        
        wp_reset_postdata();
    }
}
