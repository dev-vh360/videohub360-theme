<?php
/**
 * Elementor VideoHub360 Hero Banner Widget
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('Elementor\\Widget_Base')) {
    return;
}

class Elementor_VideoHub360_Hero_Widget extends \Elementor\Widget_Base {
    
    public function get_name() { 
        return 'videohub360_hero'; 
    }
    
    public function get_title() { 
        return esc_html__('VideoHub360 Hero Banner', 'videohub360'); 
    }
    
    public function get_icon() { 
        return 'eicon-slider-push'; 
    }
    
    public function get_categories() { 
        return ['general']; 
    }
    
    /**
     * Get script dependencies
     */
    public function get_script_depends() {
        return ['vh360-hero'];
    }
    
    /**
     * Get style dependencies
     */
    public function get_style_depends() {
        return ['vh360-hero'];
    }

    protected function register_controls() {
        
        // Mode Section
        $this->start_controls_section('mode_section', [
            'label' => __('Mode', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('mode', [
            'label' => __('Display Mode', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'single',
            'options' => [
                'single' => __('Single Hero', 'videohub360'),
                'slider' => __('Slider (Multiple Slides)', 'videohub360'),
            ],
        ]);
        
        $this->end_controls_section();
        
        // Slides Section (Repeater)
        $this->start_controls_section('slides_section', [
            'label' => __('Slides', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $repeater = new \Elementor\Repeater();
        
        // Media Settings
        $repeater->add_control('video_type', [
            'label' => __('Media Type', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'image',
            'options' => [
                'image' => __('Image / Banner', 'videohub360'),
                'mp4' => __('MP4 Video', 'videohub360'),
                'embed' => __('Embed Video', 'videohub360'),
                'html' => __('Custom HTML', 'videohub360'),
            ],
        ]);
        
        $repeater->add_control('poster', [
            'label' => __('Image / Poster', 'videohub360'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => [
                'url' => '',
            ],
        ]);
        
        $repeater->add_control('video_url', [
            'label' => __('MP4 Video URL', 'videohub360'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://videohub360.com/video.mp4',
            'condition' => [
                'video_type' => 'mp4',
            ],
        ]);
        
        $repeater->add_control('embed_url', [
            'label' => __('Embed URL', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://youtube.com/embed/...',
            'condition' => [
                'video_type' => 'embed',
            ],
        ]);
        
        $repeater->add_control('custom_html', [
            'label' => __('Custom HTML', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'rows' => 10,
            'condition' => [
                'video_type' => 'html',
            ],
        ]);
        
        $repeater->add_control('video_autoplay', [
            'label' => __('Autoplay (Muted)', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
            'condition' => [
                'video_type' => 'mp4',
            ],
        ]);
        
        $repeater->add_control('video_loop', [
            'label' => __('Loop', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
            'condition' => [
                'video_type' => 'mp4',
            ],
        ]);
        
        $repeater->add_control('video_controls', [
            'label' => __('Show Controls', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => [
                'video_type' => 'mp4',
            ],
        ]);
        
        $repeater->add_control('image_action', [
            'label' => __('Image Click Action', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none'     => __('No action', 'videohub360'),
                'link'     => __('Open link', 'videohub360'),
                'lightbox' => __('Expand image', 'videohub360'),
            ],
            'condition' => [
                'video_type' => 'image',
            ],
        ]);
        
        $repeater->add_control('image_link_url', [
            'label' => __('Image Link URL', 'videohub360'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://videohub360.com',
            'condition' => [
                'video_type' => 'image',
                'image_action' => 'link',
            ],
        ]);
        
        // Content
        $repeater->add_control('content_heading', [
            'label' => __('Content', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $repeater->add_control('icon_url', [
            'label' => __('Icon/Logo', 'videohub360'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);
        
        $repeater->add_control('eyebrow', [
            'label' => __('Eyebrow/Badge', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => __('NEW', 'videohub360'),
        ]);
        
        $repeater->add_control('headline', [
            'label' => __('Headline', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Welcome to VideoHub360', 'videohub360'),
        ]);
        
        $repeater->add_control('subhead', [
            'label' => __('Subheadline', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'rows' => 3,
            'default' => __('Discover amazing video content', 'videohub360'),
        ]);
        
        // CTA 1
        $repeater->add_control('cta1_heading', [
            'label' => __('Call to Action 1', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $repeater->add_control('cta1_label', [
            'label' => __('CTA 1 Label', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => __('Learn More', 'videohub360'),
        ]);
        
        $repeater->add_control('cta1_url', [
            'label' => __('CTA 1 URL', 'videohub360'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://videohub360.com',
        ]);
        
        // CTA 2
        $repeater->add_control('cta2_heading', [
            'label' => __('Call to Action 2', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $repeater->add_control('cta2_label', [
            'label' => __('CTA 2 Label', 'videohub360'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => __('Get Started', 'videohub360'),
        ]);
        
        $repeater->add_control('cta2_url', [
            'label' => __('CTA 2 URL', 'videohub360'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://videohub360.com',
        ]);
        
        $this->add_control('slides', [
            'label' => __('Slides', 'videohub360'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [
                [
                    'headline' => __('Welcome to VideoHub360', 'videohub360'),
                    'subhead' => __('Discover amazing video content', 'videohub360'),
                ],
            ],
            'title_field' => '{{{ headline }}}',
        ]);
        
        $this->end_controls_section();
        
        // Layout Section
        $this->start_controls_section('layout_section', [
            'label' => __('Layout', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        
        $this->add_control('layout', [
            'label' => __('Video Position', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'video_left',
            'options' => [
                'video_left' => __('Video Left, Content Right', 'videohub360'),
                'video_right' => __('Video Right, Content Left', 'videohub360'),
            ],
        ]);
        
        $this->add_control('aspect_ratio', [
            'label' => __('Video Aspect Ratio', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '16:9',
            'options' => [
                '16:9' => __('16:9 (Widescreen)', 'videohub360'),
                '4:3' => __('4:3 (Standard)', 'videohub360'),
                '1:1' => __('1:1 (Square)', 'videohub360'),
            ],
        ]);
        
        $this->add_control('theme', [
            'label' => __('Theme', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'light',
            'options' => [
                'light' => __('Light', 'videohub360'),
                'dark' => __('Dark', 'videohub360'),
                'transparent' => __('Transparent', 'videohub360'),
                'custom' => __('Custom', 'videohub360'),
            ],
        ]);
        
        $this->add_control('custom_bg_color', [
            'label' => __('Custom Background Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#ffffff',
            'condition' => [
                'theme' => 'custom',
            ],
        ]);
        
        $this->add_control('custom_text_color', [
            'label' => __('Custom Text Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#000000',
            'condition' => [
                'theme' => 'custom',
            ],
        ]);
        
        $this->add_control('gap', [
            'label' => __('Gap Between Columns', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ],
            'default' => [
                'size' => 40,
                'unit' => 'px',
            ],
        ]);
        
        $this->add_control('padding', [
            'label' => __('Section Padding', 'videohub360'),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'default' => [
                'top' => '60',
                'right' => '20',
                'bottom' => '60',
                'left' => '20',
                'unit' => 'px',
            ],
        ]);
        
        $this->add_control('max_width', [
            'label' => __('Content Max Width', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => [
                    'min' => 300,
                    'max' => 1200,
                ],
            ],
            'default' => [
                'size' => 600,
                'unit' => 'px',
            ],
        ]);
        
        $this->end_controls_section();
        
        // Slider Settings
        $this->start_controls_section('slider_section', [
            'label' => __('Slider Settings', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [
                'mode' => 'slider',
            ],
        ]);
        
        $this->add_control('show_arrows', [
            'label' => __('Show Arrows', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('show_dots', [
            'label' => __('Show Dots', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        
        $this->add_control('transition_type', [
            'label' => __('Transition Effect', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'slide',
            'options' => [
                'slide' => __('Slide', 'videohub360'),
                'fade' => __('Fade', 'videohub360'),
            ],
        ]);
        
        $this->add_control('autoplay', [
            'label' => __('Autoplay', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
        ]);
        
        $this->add_control('autoplay_delay', [
            'label' => __('Autoplay Delay (ms)', 'videohub360'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5000,
            'min' => 1000,
            'max' => 10000,
            'step' => 100,
            'condition' => [
                'autoplay' => 'yes',
            ],
        ]);
        
        $this->add_control('pause_on_hover', [
            'label' => __('Pause on Hover', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => [
                'autoplay' => 'yes',
            ],
        ]);
        
        $this->end_controls_section();
        
        // ========================================
        // STYLE TAB - Typography Section
        // ========================================
        
        $this->start_controls_section('vh360_hero_typography', [
            'label' => __('Typography', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        
        // Eyebrow Typography
        $this->add_control('eyebrow_heading', [
            'label' => __('Eyebrow', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
        ]);
        
        $this->add_control('eyebrow_color', [
            'label' => __('Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-eyebrow-color: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('eyebrow_font_size', [
            'label' => __('Font Size', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range' => [
                'px' => ['min' => 10, 'max' => 60],
                'em' => ['min' => 0.5, 'max' => 4],
                'rem' => ['min' => 0.5, 'max' => 4],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-eyebrow-font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);
        
        $this->add_control('eyebrow_font_family', [
            'label' => __('Font Family', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                'inherit' => __('Inherit', 'videohub360'),
                'Arial, sans-serif' => 'Arial',
                '"Helvetica Neue", Helvetica, Arial, sans-serif' => 'Helvetica Neue',
                'Georgia, serif' => 'Georgia',
                '"Times New Roman", Times, serif' => 'Times New Roman',
                '"Courier New", Courier, monospace' => 'Courier New',
                'Verdana, Geneva, sans-serif' => 'Verdana',
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-eyebrow-font-family: {{VALUE}};',
            ],
        ]);
        
        $this->add_control('eyebrow_font_weight', [
            'label' => __('Font Weight', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                '300' => __('Light (300)', 'videohub360'),
                '400' => __('Normal (400)', 'videohub360'),
                '500' => __('Medium (500)', 'videohub360'),
                '600' => __('Semi Bold (600)', 'videohub360'),
                '700' => __('Bold (700)', 'videohub360'),
                '800' => __('Extra Bold (800)', 'videohub360'),
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-eyebrow-font-weight: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('eyebrow_line_height', [
            'label' => __('Line Height', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => ['min' => 1, 'max' => 3, 'step' => 0.1],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-eyebrow-line-height: {{SIZE}};',
            ],
        ]);
        
        // Headline Typography
        $this->add_control('headline_heading', [
            'label' => __('Headline', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $this->add_control('headline_color', [
            'label' => __('Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-headline-color: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('headline_font_size', [
            'label' => __('Font Size', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range' => [
                'px' => ['min' => 10, 'max' => 120],
                'em' => ['min' => 0.5, 'max' => 8],
                'rem' => ['min' => 0.5, 'max' => 8],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-headline-font-size: {{SIZE}}{{UNIT}};',
                '(mobile){{WRAPPER}}' => '--vh360-hero-headline-font-size-mobile: {{SIZE}}{{UNIT}};',
            ],
        ]);
        
        $this->add_control('headline_font_family', [
            'label' => __('Font Family', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                'inherit' => __('Inherit', 'videohub360'),
                'Arial, sans-serif' => 'Arial',
                '"Helvetica Neue", Helvetica, Arial, sans-serif' => 'Helvetica Neue',
                'Georgia, serif' => 'Georgia',
                '"Times New Roman", Times, serif' => 'Times New Roman',
                '"Courier New", Courier, monospace' => 'Courier New',
                'Verdana, Geneva, sans-serif' => 'Verdana',
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-headline-font-family: {{VALUE}};',
            ],
        ]);
        
        $this->add_control('headline_font_weight', [
            'label' => __('Font Weight', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                '300' => __('Light (300)', 'videohub360'),
                '400' => __('Normal (400)', 'videohub360'),
                '500' => __('Medium (500)', 'videohub360'),
                '600' => __('Semi Bold (600)', 'videohub360'),
                '700' => __('Bold (700)', 'videohub360'),
                '800' => __('Extra Bold (800)', 'videohub360'),
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-headline-font-weight: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('headline_line_height', [
            'label' => __('Line Height', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => ['min' => 1, 'max' => 3, 'step' => 0.1],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-headline-line-height: {{SIZE}};',
            ],
        ]);
        
        // Subhead Typography
        $this->add_control('subhead_heading', [
            'label' => __('Subhead', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        
        $this->add_control('subhead_color', [
            'label' => __('Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-subhead-color: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('subhead_font_size', [
            'label' => __('Font Size', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range' => [
                'px' => ['min' => 10, 'max' => 60],
                'em' => ['min' => 0.5, 'max' => 4],
                'rem' => ['min' => 0.5, 'max' => 4],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-subhead-font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);
        
        $this->add_control('subhead_font_family', [
            'label' => __('Font Family', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                'inherit' => __('Inherit', 'videohub360'),
                'Arial, sans-serif' => 'Arial',
                '"Helvetica Neue", Helvetica, Arial, sans-serif' => 'Helvetica Neue',
                'Georgia, serif' => 'Georgia',
                '"Times New Roman", Times, serif' => 'Times New Roman',
                '"Courier New", Courier, monospace' => 'Courier New',
                'Verdana, Geneva, sans-serif' => 'Verdana',
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-subhead-font-family: {{VALUE}};',
            ],
        ]);
        
        $this->add_control('subhead_font_weight', [
            'label' => __('Font Weight', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                '' => __('Default', 'videohub360'),
                '300' => __('Light (300)', 'videohub360'),
                '400' => __('Normal (400)', 'videohub360'),
                '500' => __('Medium (500)', 'videohub360'),
                '600' => __('Semi Bold (600)', 'videohub360'),
                '700' => __('Bold (700)', 'videohub360'),
                '800' => __('Extra Bold (800)', 'videohub360'),
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-subhead-font-weight: {{VALUE}};',
            ],
        ]);
        
        $this->add_responsive_control('subhead_line_height', [
            'label' => __('Line Height', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => [
                'px' => ['min' => 1, 'max' => 3, 'step' => 0.1],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-subhead-line-height: {{SIZE}};',
            ],
        ]);
        
        $this->end_controls_section();

        // ========================================
        // STYLE TAB - CTA Buttons Section
        // ========================================

        $this->start_controls_section('vh360_hero_cta_buttons', [
            'label' => __('CTA Buttons', 'videohub360'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        // Shared CTA styles
        $this->add_control('cta_shared_heading', [
            'label' => __('Shared Button Styles', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
        ]);

        $this->add_control('cta_gap', [
            'label' => __('Gap Between Buttons', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => ['min' => 0, 'max' => 60, 'step' => 1],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('cta_padding', [
            'label' => __('Padding', 'videohub360'),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('cta_border_radius', [
            'label' => __('Border Radius', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range' => [
                'px' => ['min' => 0, 'max' => 100, 'step' => 1],
            ],
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('cta_font_weight', [
            'label' => __('Font Weight', 'videohub360'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''    => __('Default', 'videohub360'),
                '300' => __('Light (300)', 'videohub360'),
                '400' => __('Normal (400)', 'videohub360'),
                '500' => __('Medium (500)', 'videohub360'),
                '600' => __('Semi-Bold (600)', 'videohub360'),
                '700' => __('Bold (700)', 'videohub360'),
                '800' => __('Extra Bold (800)', 'videohub360'),
                '900' => __('Black (900)', 'videohub360'),
            ],
            'default' => '',
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-font-weight: {{VALUE}};',
            ],
        ]);

        // Primary CTA styles
        $this->add_control('cta_primary_heading', [
            'label' => __('Primary Button', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('cta_primary_bg', [
            'label' => __('Background Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-primary-bg: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_primary_color', [
            'label' => __('Text Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-primary-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_primary_hover_bg', [
            'label' => __('Hover Background Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-primary-hover-bg: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_primary_hover_color', [
            'label' => __('Hover Text Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-primary-hover-color: {{VALUE}};',
            ],
        ]);

        // Secondary CTA styles
        $this->add_control('cta_secondary_heading', [
            'label' => __('Secondary Button', 'videohub360'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('cta_secondary_bg', [
            'label' => __('Background Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-bg: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_secondary_color', [
            'label' => __('Text Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_secondary_border_color', [
            'label' => __('Border Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-border-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_secondary_hover_bg', [
            'label' => __('Hover Background Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-hover-bg: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_secondary_hover_color', [
            'label' => __('Hover Text Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-hover-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('cta_secondary_hover_border_color', [
            'label' => __('Hover Border Color', 'videohub360'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}}' => '--vh360-hero-cta-secondary-hover-border-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build configuration array
        $config = [
            'mode' => $settings['mode'],
            'layout' => $settings['layout'],
            'aspect_ratio' => $settings['aspect_ratio'],
            'theme' => $settings['theme'],
            'custom_bg_color' => isset($settings['custom_bg_color']) ? $settings['custom_bg_color'] : '#ffffff',
            'custom_text_color' => isset($settings['custom_text_color']) ? $settings['custom_text_color'] : '#000000',
            'gap' => isset($settings['gap']['size']) ? $settings['gap']['size'] . $settings['gap']['unit'] : '40px',
            'max_width' => isset($settings['max_width']['size']) ? $settings['max_width']['size'] . $settings['max_width']['unit'] : '600px',
            'show_arrows' => $settings['show_arrows'] === 'yes',
            'show_dots' => $settings['show_dots'] === 'yes',
            'transition_type' => isset($settings['transition_type']) && in_array($settings['transition_type'], ['slide', 'fade'], true) ? $settings['transition_type'] : 'slide',
            'autoplay' => isset($settings['autoplay']) && $settings['autoplay'] === 'yes',
            'autoplay_delay' => isset($settings['autoplay_delay']) ? intval($settings['autoplay_delay']) : 5000,
            'pause_on_hover' => isset($settings['pause_on_hover']) && $settings['pause_on_hover'] === 'yes',
            'slides' => [],
        ];
        
        // Build padding
        if (isset($settings['padding'])) {
            $padding = sprintf(
                '%s%s %s%s %s%s %s%s',
                $settings['padding']['top'],
                $settings['padding']['unit'],
                $settings['padding']['right'],
                $settings['padding']['unit'],
                $settings['padding']['bottom'],
                $settings['padding']['unit'],
                $settings['padding']['left'],
                $settings['padding']['unit']
            );
            $config['padding'] = $padding;
        }
        
        // Process slides
        if (!empty($settings['slides'])) {
            foreach ($settings['slides'] as $slide) {
                $config['slides'][] = [
                    'video_type' => $slide['video_type'],
                    'poster' => isset($slide['poster']['url']) ? $slide['poster']['url'] : '',
                    'video_url' => isset($slide['video_url']['url']) ? $slide['video_url']['url'] : '',
                    'embed_url' => isset($slide['embed_url']) ? $slide['embed_url'] : '',
                    'custom_html' => isset($slide['custom_html']) ? $slide['custom_html'] : '',
                    'autoplay' => isset($slide['video_autoplay']) && $slide['video_autoplay'] === 'yes',
                    'loop' => isset($slide['video_loop']) && $slide['video_loop'] === 'yes',
                    'controls' => isset($slide['video_controls']) && $slide['video_controls'] === 'yes',
                    'preload' => 'metadata',
                    'image_action' => isset($slide['image_action']) ? $slide['image_action'] : 'none',
                    'image_link_url' => isset($slide['image_link_url']['url']) ? $slide['image_link_url']['url'] : '',
                    'image_link_new_tab' => !empty($slide['image_link_url']['is_external']),
                    'image_link_nofollow' => !empty($slide['image_link_url']['nofollow']),
                    'icon_url' => isset($slide['icon_url']['url']) ? $slide['icon_url']['url'] : '',
                    'eyebrow' => isset($slide['eyebrow']) ? $slide['eyebrow'] : '',
                    'headline' => isset($slide['headline']) ? $slide['headline'] : '',
                    'subhead' => isset($slide['subhead']) ? $slide['subhead'] : '',
                    'cta1_label' => isset($slide['cta1_label']) ? $slide['cta1_label'] : '',
                    'cta1_url' => isset($slide['cta1_url']['url']) ? $slide['cta1_url']['url'] : '',
                    'cta1_new_tab' => isset($slide['cta1_url']['is_external']) && $slide['cta1_url']['is_external'],
                    'cta2_label' => isset($slide['cta2_label']) ? $slide['cta2_label'] : '',
                    'cta2_url' => isset($slide['cta2_url']['url']) ? $slide['cta2_url']['url'] : '',
                    'cta2_new_tab' => isset($slide['cta2_url']['is_external']) && $slide['cta2_url']['is_external'],
                ];
            }
        }
        
        // Load renderer
        if (!class_exists('VideoHub360_Hero_Renderer')) {
            require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-hero-renderer.php';
        }
        
        // Render
        echo VideoHub360_Hero_Renderer::render($config);
    }
}
