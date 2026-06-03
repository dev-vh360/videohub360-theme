<?php
/**
 * VideoHub360 Hero Banner Renderer
 * 
 * Handles rendering of hero banner/slider with video support
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Hero_Renderer {
    
    /**
     * Allowed HTML tags for custom HTML sanitization
     */
    private static $allowed_html = array(
        'div' => array('class' => array(), 'id' => array(), 'style' => array()),
        'span' => array('class' => array(), 'style' => array()),
        'p' => array('class' => array()),
        'h1' => array('class' => array()),
        'h2' => array('class' => array()),
        'h3' => array('class' => array()),
        'h4' => array('class' => array()),
        'h5' => array('class' => array()),
        'h6' => array('class' => array()),
        'a' => array('href' => array(), 'class' => array(), 'target' => array(), 'rel' => array()),
        'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'width' => array(), 'height' => array()),
        'strong' => array('class' => array()),
        'em' => array('class' => array()),
        'br' => array(),
        'iframe' => array(
            'src' => array(),
            'width' => array(),
            'height' => array(),
            'frameborder' => array(),
            'allowfullscreen' => array(),
            'allow' => array(),
            'loading' => array(),
            'style' => array(),
            'class' => array(),
            'title' => array(),
            'scrolling' => array(),
            'referrerpolicy' => array(),
        ),
        'figure' => array(
            'class' => array(),
            'style' => array(),
        ),
    );
    
    /**
     * Allowed embed domains
     */
    private static $allowed_embed_domains = array(
        'youtube.com',
        'youtube-nocookie.com',
        'youtu.be',
        'vimeo.com',
        'player.vimeo.com',
        'twitch.tv',
        'player.twitch.tv',
        'vadoo.tv',
        'api.vadoo.tv',
    );
    
    /**
     * Render hero banner/slider
     * 
     * @param array $config Configuration array
     * @return string HTML output
     */
    public static function render($config) {
        // Normalize and validate config
        $config = self::normalize_config($config);
        
        // Generate unique instance ID
        $instance_id = 'vh360-hero-' . substr(md5(serialize($config)), 0, 8);
        
        // Start output buffering
        ob_start();
        
        // Render CSS
        self::render_styles($instance_id, $config);
        
        // Render markup
        if ($config['mode'] === 'slider' && count($config['slides']) > 1) {
            self::render_slider($instance_id, $config);
        } else {
            self::render_single($instance_id, $config);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Normalize configuration array
     * 
     * @param array $config Raw configuration
     * @return array Normalized configuration
     */
    private static function normalize_config($config) {
        $defaults = array(
            'mode' => 'single',
            'layout' => 'video_left',
            'aspect_ratio' => '16:9',
            'theme' => 'light',
            'custom_bg_color' => '#ffffff',
            'custom_text_color' => '#000000',
            'gap' => '40px',
            'padding' => '60px 20px',
            'max_width' => '600px',
            'slides' => array(),
            'show_arrows' => true,
            'show_dots' => true,
            'transition_type' => 'slide',
            'autoplay' => false,
            'autoplay_delay' => 5000,
            'pause_on_hover' => true,
        );
        
        $config = wp_parse_args($config, $defaults);
        
        // Validate and sanitize CSS values
        $config['mode'] = in_array($config['mode'], array('single', 'slider'), true) ? $config['mode'] : 'single';
        $config['layout'] = in_array($config['layout'], array('video_left', 'video_right'), true) ? $config['layout'] : 'video_left';
        $config['aspect_ratio'] = in_array($config['aspect_ratio'], array('16:9', '4:3', '1:1'), true) ? $config['aspect_ratio'] : '16:9';
        $config['theme'] = in_array($config['theme'], array('light', 'dark', 'transparent', 'custom'), true) ? $config['theme'] : 'light';
        
        // Sanitize custom colors - avoid duplicate function calls
        $sanitized_bg = sanitize_hex_color($config['custom_bg_color']);
        $config['custom_bg_color'] = $sanitized_bg ? $sanitized_bg : '#ffffff';
        $sanitized_text = sanitize_hex_color($config['custom_text_color']);
        $config['custom_text_color'] = $sanitized_text ? $sanitized_text : '#000000';
        
        $config['transition_type'] = in_array($config['transition_type'], array('slide', 'fade'), true) ? $config['transition_type'] : 'slide';
        $config['gap'] = self::sanitize_css_value($config['gap'], '40px');
        $config['padding'] = self::sanitize_css_value($config['padding'], '60px 20px', true);
        $config['max_width'] = self::sanitize_css_value($config['max_width'], '600px');
        $config['autoplay_delay'] = max(1000, min(30000, intval($config['autoplay_delay'])));
        
        // Ensure slides is an array
        if (!is_array($config['slides']) || empty($config['slides'])) {
            $config['slides'] = array(self::get_default_slide());
        }
        
        // Normalize each slide
        foreach ($config['slides'] as $index => $slide) {
            $config['slides'][$index] = self::normalize_slide($slide);
        }
        
        return $config;
    }
    
    /**
     * Sanitize CSS value
     * 
     * @param string $value CSS value to sanitize
     * @param string $default Default value if validation fails
     * @param bool $allow_multiple Allow multiple values (e.g., padding: 10px 20px)
     * @return string Sanitized CSS value
     */
    private static function sanitize_css_value($value, $default = '0', $allow_multiple = false) {
        // Allow specific units
        $allowed_units = array('px', 'em', 'rem', '%', 'vh', 'vw');
        
        if ($allow_multiple) {
            // Split on whitespace for multiple values
            $values = preg_split('/\s+/', trim($value));
            $sanitized = array();
            
            foreach ($values as $val) {
                $sanitized_val = self::sanitize_single_css_value($val, $allowed_units);
                if ($sanitized_val !== false) {
                    $sanitized[] = $sanitized_val;
                }
            }
            
            return !empty($sanitized) ? implode(' ', $sanitized) : $default;
        }
        
        $sanitized = self::sanitize_single_css_value($value, $allowed_units);
        return $sanitized !== false ? $sanitized : $default;
    }
    
    /**
     * Sanitize single CSS value
     * 
     * @param string $value CSS value
     * @param array $allowed_units Allowed units
     * @return string|false Sanitized value or false if invalid
     */
    private static function sanitize_single_css_value($value, $allowed_units) {
        $value = trim($value);
        
        // Match number followed by unit
        if (preg_match('/^(-?\d+(?:\.\d+)?)(px|em|rem|%|vh|vw)$/', $value, $matches)) {
            $number = floatval($matches[1]);
            $unit = $matches[2];
            
            if (in_array($unit, $allowed_units, true)) {
                // Ensure reasonable ranges
                if ($unit === '%' && ($number < 0 || $number > 200)) {
                    return false;
                }
                if (in_array($unit, array('px', 'em', 'rem'), true) && ($number < 0 || $number > 2000)) {
                    return false;
                }
                
                return $number . $unit;
            }
        }
        
        return false;
    }
    
    /**
     * Get default slide configuration
     * 
     * @return array Default slide
     */
    private static function get_default_slide() {
        return array(
            'video_type' => 'image',
            'poster' => '',
            'video_url' => '',
            'embed_url' => '',
            'custom_html' => '',
            'autoplay' => false,
            'loop' => false,
            'controls' => true,
            'preload' => 'metadata',
            'image_action' => 'none',
            'image_link_url' => '',
            'image_link_new_tab' => false,
            'image_link_nofollow' => false,
            'eyebrow' => '',
            'headline' => __('Welcome to VideoHub360', 'videohub360'),
            'subhead' => __('Discover amazing video content', 'videohub360'),
            'cta1_label' => '',
            'cta1_url' => '',
            'cta1_new_tab' => false,
            'cta2_label' => '',
            'cta2_url' => '',
            'cta2_new_tab' => false,
            'icon_url' => '',
        );
    }
    
    /**
     * Normalize slide data
     * 
     * @param array $slide Raw slide data
     * @return array Normalized slide
     */
    private static function normalize_slide($slide) {
        $defaults = self::get_default_slide();
        $slide = wp_parse_args($slide, $defaults);
        
        // Normalize old thumbnail type to image
        if ($slide['video_type'] === 'thumbnail') {
            $slide['video_type'] = 'image';
        }
        
        // Validate media type
        $allowed_media_types = array('image', 'mp4', 'embed', 'html');
        $slide['video_type'] = in_array($slide['video_type'], $allowed_media_types, true)
            ? $slide['video_type']
            : 'image';
        
        // Sanitize all fields
        $slide['poster'] = esc_url_raw($slide['poster']);
        $slide['video_url'] = esc_url_raw($slide['video_url']);
        $slide['embed_url'] = self::sanitize_embed_url($slide['embed_url']);
        $slide['custom_html'] = wp_kses($slide['custom_html'], self::$allowed_html);
        
        // Sanitize image action fields
        $allowed_image_actions = array('none', 'link', 'lightbox');
        $slide['image_action'] = isset($slide['image_action']) && in_array($slide['image_action'], $allowed_image_actions, true)
            ? $slide['image_action']
            : 'none';
        $slide['image_link_url'] = esc_url_raw($slide['image_link_url']);
        $slide['image_link_new_tab'] = !empty($slide['image_link_new_tab']);
        $slide['image_link_nofollow'] = !empty($slide['image_link_nofollow']);
        
        $slide['eyebrow'] = sanitize_text_field($slide['eyebrow']);
        $slide['headline'] = sanitize_text_field($slide['headline']);
        $slide['subhead'] = sanitize_text_field($slide['subhead']);
        $slide['cta1_label'] = sanitize_text_field($slide['cta1_label']);
        $slide['cta1_url'] = esc_url_raw($slide['cta1_url']);
        $slide['cta2_label'] = sanitize_text_field($slide['cta2_label']);
        $slide['cta2_url'] = esc_url_raw($slide['cta2_url']);
        $slide['icon_url'] = esc_url_raw($slide['icon_url']);
        
        return $slide;
    }
    
    /**
     * Sanitize and validate embed URL
     * 
     * Auto-converts YouTube watch URLs to embed format
     * 
     * @param string $url Embed URL
     * @return string Sanitized URL or empty string
     */
    private static function sanitize_embed_url($url) {
        if (empty($url)) {
            return '';
        }
        
        $url = esc_url_raw($url);
        $parsed = parse_url($url);
        
        if (!isset($parsed['host'])) {
            return '';
        }
        
        // Try to convert YouTube URLs to embed format
        $url = self::convert_youtube_url_to_embed($url);
        
        // Re-parse after potential conversion
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return '';
        }
        
        // Check if domain is allowed
        $host = strtolower($parsed['host']);
        $allowed = false;
        
        foreach (self::$allowed_embed_domains as $domain) {
            if ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain) {
                $allowed = true;
                break;
            }
        }
        
        return $allowed ? $url : '';
    }
    
    /**
     * Convert YouTube watch/short URLs to embed format
     * 
     * @param string $url YouTube URL
     * @return string Embed URL or original URL if not YouTube
     */
    private static function convert_youtube_url_to_embed($url) {
        if (empty($url)) {
            return '';
        }
        
        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return $url;
        }
        
        $host = strtolower($parsed['host']);
        $video_id = '';
        
        // Check if it's a YouTube domain
        $is_youtube = ($host === 'youtube.com' || $host === 'www.youtube.com' || 
                       $host === 'youtube-nocookie.com' || $host === 'www.youtube-nocookie.com' || 
                       $host === 'youtu.be');
        
        if (!$is_youtube) {
            return $url; // Not YouTube, return as-is
        }
        
        // Extract video ID from different YouTube URL formats
        
        // Format 1: https://www.youtube.com/watch?v=VIDEO_ID
        if (strpos($parsed['path'], '/watch') === 0 && isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
            if (isset($query_params['v'])) {
                $video_id = $query_params['v'];
            }
        }
        // Format 2: https://youtu.be/VIDEO_ID
        elseif ($host === 'youtu.be' && !empty($parsed['path'])) {
            // Extract only the first path segment (video ID)
            $path_parts = explode('/', trim($parsed['path'], '/'));
            if (!empty($path_parts[0])) {
                $video_id = $path_parts[0];
            }
        }
        // Format 3: https://www.youtube.com/embed/VIDEO_ID (already embed URL)
        elseif (strpos($parsed['path'], '/embed/') === 0) {
            // Extract video ID after /embed/ (only first segment)
            $path_after_embed = substr($parsed['path'], 7); // Remove '/embed/'
            $path_parts = explode('/', trim($path_after_embed, '/'));
            if (!empty($path_parts[0])) {
                $video_id = $path_parts[0];
            }
        }
        
        // If no video ID found, return original URL
        if (empty($video_id)) {
            return $url;
        }
        
        // Sanitize video ID - YouTube IDs are alphanumeric, underscores, and hyphens
        // Typically 11 characters but can vary
        $video_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $video_id);
        
        // Validate length (YouTube IDs are typically 11 chars, but allow some flexibility)
        if (empty($video_id) || strlen($video_id) > 20) {
            return $url;
        }
        
        // Build embed URL with proper parameters
        $site_url = get_site_url();
        $embed_url = sprintf(
            'https://www.youtube-nocookie.com/embed/%s?enablejsapi=1&origin=%s&rel=0',
            $video_id,
            urlencode($site_url)
        );
        
        return $embed_url;
    }
    
    /**
     * Render styles for hero banner
     * 
     * @param string $instance_id Instance ID
     * @param array $config Configuration
     */
    private static function render_styles($instance_id, $config) {
        $aspect_ratio = self::get_aspect_ratio($config['aspect_ratio']);
        ?>
        <style>
            .<?php echo esc_attr($instance_id); ?> {
                position: relative;
                overflow: hidden;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-slide {
                display: grid;
                grid-template-columns: <?php echo $config['layout'] === 'video_left' ? '1fr 1fr' : '1fr 1fr'; ?>;
                gap: <?php echo esc_attr($config['gap']); ?>;
                align-items: center;
                padding: <?php echo esc_attr($config['padding']); ?>;
                min-height: 400px;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-dark .vh360-hero-slide {
                background: #1a1a1a;
                color: #fff;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-light .vh360-hero-slide {
                background: #f5f5f5;
                color: #1a1a1a;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-transparent .vh360-hero-slide {
                background: transparent;
                color: inherit;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-custom .vh360-hero-slide {
                background: var(--vh360-hero-bg-color, #ffffff);
                color: var(--vh360-hero-text-color, #000000);
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-video-area {
                position: relative;
                aspect-ratio: <?php echo esc_attr($aspect_ratio); ?>;
                background: #000;
                border-radius: 8px;
                overflow: hidden;
                <?php if ($config['layout'] === 'video_right') echo 'order: 2;'; ?>
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-content-area {
                padding: 20px;
                max-width: <?php echo esc_attr($config['max_width']); ?>;
                margin: 0 auto;
                <?php if ($config['layout'] === 'video_right') echo 'order: 1;'; ?>
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-icon-img {
                width: 60px;
                max-width: 60px;
                height: auto;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-eyebrow {
                font-family: var(--vh360-hero-eyebrow-font-family, inherit);
                font-size: var(--vh360-hero-eyebrow-font-size, 0.875rem);
                font-weight: var(--vh360-hero-eyebrow-font-weight, 600);
                line-height: var(--vh360-hero-eyebrow-line-height, 1.4);
                color: var(--vh360-hero-eyebrow-color, inherit);
                text-transform: uppercase;
                letter-spacing: 0.05em;
                margin-bottom: 12px;
                opacity: 0.8;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-headline {
                font-family: var(--vh360-hero-headline-font-family, inherit);
                font-size: var(--vh360-hero-headline-font-size, 2.5rem);
                font-weight: var(--vh360-hero-headline-font-weight, 700);
                line-height: var(--vh360-hero-headline-line-height, 1.2);
                color: var(--vh360-hero-headline-color, inherit);
                margin: 0 0 16px 0;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-subhead {
                font-family: var(--vh360-hero-subhead-font-family, inherit);
                font-size: var(--vh360-hero-subhead-font-size, 1.125rem);
                font-weight: var(--vh360-hero-subhead-font-weight, 400);
                line-height: var(--vh360-hero-subhead-line-height, 1.6);
                color: var(--vh360-hero-subhead-color, inherit);
                margin: 0 0 24px 0;
                opacity: 0.9;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-ctas {
                display: flex;
                gap: var(--vh360-hero-cta-gap, 12px);
                flex-wrap: wrap;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta {
                display: inline-flex;
                align-items: center;
                padding: var(--vh360-hero-cta-padding, 12px 24px);
                border-radius: var(--vh360-hero-cta-border-radius, 6px);
                font-weight: var(--vh360-hero-cta-font-weight, 600);
                text-decoration: none;
                transition: all 0.2s ease;
                cursor: pointer;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta.primary {
                background: var(--vh360-hero-cta-primary-bg, #e53935);
                color: var(--vh360-hero-cta-primary-color, #ffffff);
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta.primary:hover {
                background: var(--vh360-hero-cta-primary-hover-bg, #c62828);
                color: var(--vh360-hero-cta-primary-hover-color, var(--vh360-hero-cta-primary-color, #ffffff));
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta.secondary {
                background: var(--vh360-hero-cta-secondary-bg, transparent);
                color: var(--vh360-hero-cta-secondary-color, currentColor);
                border: 2px solid var(--vh360-hero-cta-secondary-border-color, currentColor);
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta.secondary:hover {
                background: var(--vh360-hero-cta-secondary-hover-bg, var(--vh360-hero-cta-secondary-bg, transparent));
                color: var(--vh360-hero-cta-secondary-hover-color, var(--vh360-hero-cta-secondary-color, currentColor));
                border-color: var(--vh360-hero-cta-secondary-hover-border-color, var(--vh360-hero-cta-secondary-border-color, currentColor));
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-video,
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-iframe,
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-poster {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            
            /* Slider specific styles */
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-slider .vh360-hero-track {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
                -ms-overflow-style: none;
                width: 100%;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-slider .vh360-hero-track::-webkit-scrollbar {
                display: none;
            }
            
            /* Track backgrounds for all themes */
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-dark.vh360-hero-slider .vh360-hero-track {
                background: #1a1a1a;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-light.vh360-hero-slider .vh360-hero-track {
                background: #f5f5f5;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-transparent.vh360-hero-slider .vh360-hero-track {
                background: transparent;
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-theme-custom.vh360-hero-slider .vh360-hero-track {
                background: var(--vh360-hero-bg-color, #ffffff);
            }
            
            .<?php echo esc_attr($instance_id); ?>.vh360-hero-slider .vh360-hero-slide {
                flex: 0 0 100%;
                min-width: 100%;
                scroll-snap-align: start;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-arrows {
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                display: flex;
                justify-content: space-between;
                padding: 0 20px;
                pointer-events: none;
                z-index: 10;
            }
            
            .vh360-hero-slider .vh360-hero-arrows button.vh360-hero-arrow,
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-arrows .vh360-hero-arrow {
                width: 40px;
                height: 40px;
                min-width: 40px;
                min-height: 40px;
                background: rgba(255, 255, 255, 0.9);
                border: none;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                pointer-events: all;
                transition: all 0.2s ease;
                padding: 0;
                margin: 0;
                line-height: 1;
                text-transform: none;
                font-family: inherit;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-arrow:hover {
                background: #fff;
                transform: scale(1.1);
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-arrow svg {
                width: 20px;
                height: 20px;
                fill: #1a1a1a;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-dots {
                display: flex;
                gap: 8px;
                justify-content: center;
                padding: 20px 0;
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 10;
                background: transparent;
                pointer-events: none;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-dots .vh360-hero-dot {
                pointer-events: all;
            }
            
            .vh360-hero-slider .vh360-hero-dots button.vh360-hero-dot,
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-dots .vh360-hero-dot {
                width: 10px;
                height: 10px;
                min-width: 10px;
                min-height: 10px;
                border-radius: 50%;
                background: rgba(0, 0, 0, 0.3);
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                padding: 0;
                margin: 0;
                line-height: 1;
                text-transform: none;
                font-family: inherit;
                pointer-events: all;
            }
            
            .<?php echo esc_attr($instance_id); ?> .vh360-hero-dot.active {
                background: #e53935;
                transform: scale(1.2);
            }
            
            /* Fade transition styles */
            .<?php echo esc_attr($instance_id); ?>[data-transition-type="fade"] .vh360-hero-track {
                display: block;
                position: relative;
                overflow: visible;
                min-height: 400px;
            }
            
            .<?php echo esc_attr($instance_id); ?>[data-transition-type="fade"] .vh360-hero-slide {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.6s ease-in-out;
            }
            
            .<?php echo esc_attr($instance_id); ?>[data-transition-type="fade"] .vh360-hero-slide.vh360-active {
                position: relative;
                opacity: 1;
                pointer-events: all;
                z-index: 1;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-slide {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-video-area,
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-content-area {
                    order: initial !important;
                }
                
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-headline {
                    font-size: var(--vh360-hero-headline-font-size-mobile, 1.75rem);
                }
                
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-ctas {
                    flex-direction: column;
                }
                
                .<?php echo esc_attr($instance_id); ?> .vh360-hero-cta {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Get aspect ratio value
     * 
     * @param string $ratio Aspect ratio string
     * @return string CSS aspect ratio value
     */
    private static function get_aspect_ratio($ratio) {
        $ratios = array(
            '16:9' => '16 / 9',
            '4:3' => '4 / 3',
            '1:1' => '1 / 1',
        );
        
        return isset($ratios[$ratio]) ? $ratios[$ratio] : '16 / 9';
    }
    
    /**
     * Render single hero
     * 
     * @param string $instance_id Instance ID
     * @param array $config Configuration
     */
    private static function render_single($instance_id, $config) {
        $slide = $config['slides'][0];
        $theme_class = 'vh360-hero-theme-' . esc_attr($config['theme']);
        
        // Build inline style for custom theme CSS variables
        $inline_style = '';
        if ($config['theme'] === 'custom') {
            $inline_style = sprintf(
                'style="--vh360-hero-bg-color: %s; --vh360-hero-text-color: %s;"',
                esc_attr($config['custom_bg_color']),
                esc_attr($config['custom_text_color'])
            );
        }
        ?>
        <div class="<?php echo esc_attr($instance_id); ?> vh360-hero-single <?php echo esc_attr($theme_class); ?>" <?php 
            echo $inline_style;
            if ($config['theme'] === 'custom') {
                echo ' data-bg-color="' . esc_attr($config['custom_bg_color']) . '"';
                echo ' data-text-color="' . esc_attr($config['custom_text_color']) . '"';
            }
        ?>>
            <?php self::render_slide($slide, $config); ?>
        </div>
        <?php
    }
    
    /**
     * Render slider
     * 
     * @param string $instance_id Instance ID
     * @param array $config Configuration
     */
    private static function render_slider($instance_id, $config) {
        $theme_class = 'vh360-hero-theme-' . esc_attr($config['theme']);
        
        // Build inline style for custom theme CSS variables
        $inline_style = '';
        if ($config['theme'] === 'custom') {
            $inline_style = sprintf(
                'style="--vh360-hero-bg-color: %s; --vh360-hero-text-color: %s;" ',
                esc_attr($config['custom_bg_color']),
                esc_attr($config['custom_text_color'])
            );
        }
        ?>
        <div class="<?php echo esc_attr($instance_id); ?> vh360-hero-slider <?php echo esc_attr($theme_class); ?>" 
             <?php echo $inline_style; ?>data-autoplay="<?php echo $config['autoplay'] ? 'true' : 'false'; ?>"
             data-delay="<?php echo esc_attr($config['autoplay_delay']); ?>"
             data-pause-on-hover="<?php echo $config['pause_on_hover'] ? 'true' : 'false'; ?>"
             data-transition-type="<?php echo esc_attr($config['transition_type']); ?>"<?php 
            if ($config['theme'] === 'custom') {
                echo ' data-bg-color="' . esc_attr($config['custom_bg_color']) . '"';
                echo ' data-text-color="' . esc_attr($config['custom_text_color']) . '"';
            }
        ?>>
            
            <div class="vh360-hero-track">
                <?php foreach ($config['slides'] as $index => $slide): ?>
                    <?php self::render_slide($slide, $config, $index); ?>
                <?php endforeach; ?>
            </div>
            
            <?php if ($config['show_arrows'] && count($config['slides']) > 1): ?>
                <div class="vh360-hero-arrows">
                    <button class="vh360-hero-arrow vh360-hero-prev" aria-label="<?php esc_attr_e('Previous slide', 'videohub360'); ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                        </svg>
                    </button>
                    <button class="vh360-hero-arrow vh360-hero-next" aria-label="<?php esc_attr_e('Next slide', 'videohub360'); ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($config['show_dots'] && count($config['slides']) > 1): ?>
                <div class="vh360-hero-dots">
                    <?php foreach ($config['slides'] as $index => $slide): ?>
                        <button class="vh360-hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-index="<?php echo esc_attr($index); ?>"
                                aria-label="<?php echo esc_attr(sprintf(esc_html__('Go to slide %d', 'videohub360'), $index + 1)); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render a single slide
     * 
     * @param array $slide Slide data
     * @param array $config Configuration
     * @param int $index Slide index (for slider mode)
     */
    private static function render_slide($slide, $config, $index = 0) {
        $slide_classes = 'vh360-hero-slide';
        // Add active class to first slide for fade transitions
        if ($index === 0 && isset($config['transition_type']) && $config['transition_type'] === 'fade') {
            $slide_classes .= ' vh360-active';
        }
        ?>
        <div class="<?php echo esc_attr($slide_classes); ?>" data-slide-index="<?php echo esc_attr($index); ?>">
            <div class="vh360-hero-video-area">
                <?php self::render_video($slide); ?>
            </div>
            
            <div class="vh360-hero-content-area">
                <?php if (!empty($slide['icon_url'])): ?>
                    <div class="vh360-hero-icon">
                        <img src="<?php echo esc_url($slide['icon_url']); ?>" alt="" class="vh360-hero-icon-img">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($slide['eyebrow'])): ?>
                    <div class="vh360-hero-eyebrow"><?php echo esc_html($slide['eyebrow']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($slide['headline'])): ?>
                    <h2 class="vh360-hero-headline"><?php echo esc_html($slide['headline']); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($slide['subhead'])): ?>
                    <p class="vh360-hero-subhead"><?php echo esc_html($slide['subhead']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($slide['cta1_label']) || !empty($slide['cta2_label'])): ?>
                    <div class="vh360-hero-ctas">
                        <?php if (!empty($slide['cta1_label']) && !empty($slide['cta1_url'])): ?>
                            <a href="<?php echo esc_url($slide['cta1_url']); ?>" 
                               class="vh360-hero-cta primary"
                               <?php echo $slide['cta1_new_tab'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                <?php echo esc_html($slide['cta1_label']); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($slide['cta2_label']) && !empty($slide['cta2_url'])): ?>
                            <a href="<?php echo esc_url($slide['cta2_url']); ?>" 
                               class="vh360-hero-cta secondary"
                               <?php echo $slide['cta2_new_tab'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                <?php echo esc_html($slide['cta2_label']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render video area based on type
     * 
     * @param array $slide Slide data
     */
    private static function render_video($slide) {
        switch ($slide['video_type']) {
            case 'mp4':
                self::render_mp4_video($slide);
                break;
            
            case 'embed':
                self::render_embed($slide);
                break;
            
            case 'html':
                self::render_custom_html($slide);
                break;
            
            case 'image':
            default:
                self::render_image($slide);
                break;
        }
    }
    
    /**
     * Render image / banner (no play overlay)
     * 
     * @param array $slide Slide data
     */
    private static function render_image($slide) {
        $poster = !empty($slide['poster']) ? $slide['poster'] : '';
        
        ob_start();
        
        if ($poster) {
            ?>
            <img src="<?php echo esc_url($poster); ?>" alt="" class="vh360-hero-image">
            <?php
        } else {
            ?>
            <div class="vh360-hero-poster vh360-hero-poster-placeholder"></div>
            <?php
        }
        
        $image_html = ob_get_clean();
        
        if ($slide['image_action'] === 'link' && !empty($slide['image_link_url'])) {
            $target = !empty($slide['image_link_new_tab']) ? ' target="_blank"' : '';
            $rel_parts = array();
            
            if (!empty($slide['image_link_new_tab'])) {
                $rel_parts[] = 'noopener';
            }
            
            if (!empty($slide['image_link_nofollow'])) {
                $rel_parts[] = 'nofollow';
            }
            
            $rel = !empty($rel_parts) ? ' rel="' . esc_attr(implode(' ', $rel_parts)) . '"' : '';
            
            echo '<a href="' . esc_url($slide['image_link_url']) . '" class="vh360-hero-image-link"' . $target . $rel . '>';
            echo $image_html;
            echo '</a>';
            return;
        }
        
        if ($slide['image_action'] === 'lightbox' && !empty($poster)) {
            echo '<button type="button" class="vh360-hero-image-expand" data-vh360-hero-lightbox="' . esc_attr($poster) . '" aria-label="' . esc_attr__('Expand image', 'videohub360') . '">';
            echo $image_html;
            echo '</button>';
            return;
        }
        
        echo $image_html;
    }
    
    /**
     * Render MP4 video
     * 
     * @param array $slide Slide data
     */
    private static function render_mp4_video($slide) {
        if (empty($slide['video_url'])) {
            self::render_image($slide);
            return;
        }
        ?>
        <video class="vh360-hero-video" 
               <?php echo $slide['autoplay'] ? 'autoplay muted' : ''; ?>
               <?php echo $slide['loop'] ? 'loop' : ''; ?>
               <?php echo $slide['controls'] ? 'controls' : ''; ?>
               <?php if (!empty($slide['poster'])): ?>poster="<?php echo esc_url($slide['poster']); ?>"<?php endif; ?>
               preload="<?php echo esc_attr($slide['preload']); ?>"
               playsinline>
            <source src="<?php echo esc_url($slide['video_url']); ?>" type="video/mp4">
            <?php esc_html_e('Your browser does not support the video tag.', 'videohub360'); ?>
        </video>
        <?php
    }
    
    /**
     * Render embed iframe
     * 
     * @param array $slide Slide data
     */
    private static function render_embed($slide) {
        $embed_url = $slide['embed_url'];
        
        // Fallback: If embed_url is empty but video_url has a YouTube link, use it
        if (empty($embed_url) && !empty($slide['video_url'])) {
            $converted = self::convert_youtube_url_to_embed($slide['video_url']);
            // Check if conversion produced a valid embed URL (contains /embed/)
            if (!empty($converted) && strpos($converted, '/embed/') !== false) {
                $embed_url = $converted;
            }
        }
        
        if (empty($embed_url)) {
            self::render_image($slide);
            return;
        }
        ?>
        <iframe class="vh360-hero-iframe" 
                src="<?php echo esc_url($embed_url); ?>"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        <?php
    }
    
    /**
     * Render custom HTML
     * 
     * @param array $slide Slide data
     */
    private static function render_custom_html($slide) {
        if (empty($slide['custom_html'])) {
            self::render_image($slide);
            return;
        }
        
        echo wp_kses($slide['custom_html'], self::$allowed_html);
    }
}