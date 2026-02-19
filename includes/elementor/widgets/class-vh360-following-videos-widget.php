<?php
/**
 * Elementor VideoHub360 Following Videos Widget
 * 
 * Displays videos from authors the user is following
 * 
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class VH360_Following_Videos_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { 
        return 'vh360_following_videos'; 
    }
    
    public function get_title() { 
        return __('VH360 Following Videos', 'videohub360-theme'); 
    }
    
    public function get_icon() { 
        return 'eicon-heart'; 
    }
    
    public function get_categories() { 
        return ['vh360-theme']; 
    }
    
    public function get_style_depends() {
        return ['vh360-frontend'];
    }
    
    public function get_script_depends() {
        return ['vh360-frontend-core'];
    }

    protected function register_controls() {
        // Content Tab - Settings Section
        $this->start_controls_section('settings_section', [
            'label' => __('Settings', 'videohub360-theme'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('posts_per_page', [
            'label' => __('Number of Videos', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min' => 1,
            'max' => 30,
        ]);
        
        $this->add_control('columns', [
            'label' => __('Columns', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto' => __('Auto', 'videohub360-theme'),
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
        ]);
        
        $this->add_control('orderby', [
            'label' => __('Order By', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date' => __('Date', 'videohub360-theme'),
                'rand' => __('Random', 'videohub360-theme'),
                'title' => __('Title', 'videohub360-theme'),
            ],
        ]);
        
        $this->add_control('order', [
            'label' => __('Order', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'DESC',
            'options' => [
                'DESC' => __('Descending', 'videohub360-theme'),
                'ASC' => __('Ascending', 'videohub360-theme'),
            ],
        ]);
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section('display_section', [
            'label' => __('Display Options', 'videohub360-theme'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('show_author', [
            'label' => __('Show Author', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_views', [
            'label' => __('Show Views', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_date', [
            'label' => __('Show Date', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_live_badge', [
            'label' => __('Show Live Badge', 'videohub360-theme'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Must be logged in
        if (!is_user_logged_in()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="videohub360-no-videos-message">' . esc_html__('Following Videos widget requires users to be logged in. (Preview Mode)', 'videohub360-theme') . '</div>';
            }
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get following user IDs
        if (!function_exists('vh360_get_following_user_ids')) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('Follow system not available.', 'videohub360-theme') . '</div>';
            return;
        }
        
        $following = vh360_get_following_user_ids($user_id);
        
        if (empty($following)) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('You are not following anyone yet.', 'videohub360-theme') . '</div>';
            return;
        }
        
        // Build query args
        $query_args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => intval($settings['posts_per_page']),
            'post_status' => 'publish',
            'author__in' => $following,
            'orderby' => sanitize_text_field($settings['orderby']),
            'order' => sanitize_text_field($settings['order']),
            'meta_query' => array(
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
            ),
        );
        
        $query = new WP_Query($query_args);
        
        if (!$query->have_posts()) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('No videos from people you follow.', 'videohub360-theme') . '</div>';
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
            'show_live_badge' => $settings['show_live_badge'] ? 'yes' : 'no',
            'show_live_viewers' => 'yes',
        );
        
        echo '<div class="videohub360-elementor-widget-wrapper vh360-following-videos-widget">';
        echo '<div class="videohub360-videos-grid ' . esc_attr($column_class) . ' ratio-16-9">';
        
        while ($query->have_posts()) {
            $query->the_post();
            
            // Use core plugin's card renderer
            $widgets = VideoHub360_Core::get_instance()->get_component('widgets');
            if ($widgets) {
                echo $widgets->render_video_card(get_the_ID(), $card_args);
            }
        }
        
        echo '</div>';
        echo '</div>';
        
        wp_reset_postdata();
    }
}
