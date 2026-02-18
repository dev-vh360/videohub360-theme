<?php
/**
 * Elementor VideoHub360 Continue Watching Widget
 * 
 * Displays videos the user has started watching
 * 
 * @since 2.1.0
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class Elementor_VideoHub360_Continue_Watching_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { 
        return 'videohub360_continue_watching'; 
    }
    
    public function get_title() { 
        return __('VideoHub360 Continue Watching', 'videohub360'); 
    }
    
    public function get_icon() { 
        return 'eicon-time-line'; 
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
        
        $this->add_control('min_progress', [
            'label' => __('Minimum Progress (%)', 'videohub360'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
            'min' => 0,
            'max' => 100,
            'description' => __('Minimum watch progress to show video', 'videohub360'),
        ]);
        
        $this->add_control('max_progress', [
            'label' => __('Maximum Progress (%)', 'videohub360'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 95,
            'min' => 0,
            'max' => 100,
            'description' => __('Maximum watch progress to show video', 'videohub360'),
        ]);
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section('display_section', [
            'label' => __('Display Options', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('show_author', [
            'label' => __('Show Author', 'videohub360'),
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
        
        $this->add_control('show_progress_bar', [
            'label' => __('Show Progress Bar', 'videohub360'),
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
                echo '<div class="videohub360-no-videos-message">' . esc_html__('Continue Watching widget requires users to be logged in. (Preview Mode)', 'videohub360') . '</div>';
            }
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get watch progress data
        $progress_data = get_user_meta($user_id, 'vh360_watch_progress', true);
        if (!is_array($progress_data) || empty($progress_data)) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('You haven\'t started watching any videos yet.', 'videohub360') . '</div>';
            return;
        }
        
        // Filter by progress percentage
        $min_progress = floatval($settings['min_progress']);
        $max_progress = floatval($settings['max_progress']);
        $filtered_progress = array();
        
        foreach ($progress_data as $post_id => $data) {
            if (!isset($data['time']) || !isset($data['duration']) || $data['duration'] <= 0) {
                continue;
            }
            
            $percent = ($data['time'] / $data['duration']) * 100;
            
            if ($percent >= $min_progress && $percent <= $max_progress) {
                $filtered_progress[$post_id] = $data;
            }
        }
        
        if (empty($filtered_progress)) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('No videos in progress.', 'videohub360') . '</div>';
            return;
        }
        
        // Sort by updated time (most recent first)
        uasort($filtered_progress, function($a, $b) {
            return $b['updated'] - $a['updated'];
        });
        
        // Limit to posts_per_page
        $filtered_progress = array_slice($filtered_progress, 0, intval($settings['posts_per_page']), true);
        $post_ids = array_keys($filtered_progress);
        
        // Query posts
        $query_args = array(
            'post_type' => 'videohub360',
            'post__in' => $post_ids,
            'orderby' => 'post__in',
            'posts_per_page' => count($post_ids),
            'post_status' => 'publish',
        );
        
        $query = new WP_Query($query_args);
        
        if (!$query->have_posts()) {
            echo '<div class="videohub360-no-videos-message">' . esc_html__('No videos in progress.', 'videohub360') . '</div>';
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
        
        echo '<div class="videohub360-elementor-widget-wrapper videohub360-continue-watching-widget">';
        echo '<div class="videohub360-videos-grid ' . esc_attr($column_class) . ' ratio-16-9">';
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Calculate progress percentage
            $progress_percent = 0;
            if (isset($filtered_progress[$post_id])) {
                $data = $filtered_progress[$post_id];
                if ($data['duration'] > 0) {
                    $progress_percent = ($data['time'] / $data['duration']) * 100;
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
                'show_live_viewers' => 'yes',
                'progress_percent' => $settings['show_progress_bar'] ? $progress_percent : 0,
            );
            
            // Get widgets instance from core plugin
            $widgets = VideoHub360_Core::get_instance()->get_component('widgets');
            if ($widgets) {
                echo $widgets->render_video_card($post_id, $card_args);
            }
        }
        
        echo '</div>';
        echo '</div>';
        
        wp_reset_postdata();
    }
}
