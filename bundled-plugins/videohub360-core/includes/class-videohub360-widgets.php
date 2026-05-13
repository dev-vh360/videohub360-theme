<?php
/**
 * VideoHub360 Widgets Class
 * 
 * Handles Elementor widget registration and enhanced shortcode functionality
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Widgets {
    
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
        // Register shortcodes - single registration point
        add_action('init', array($this, 'register_shortcodes'), 10);
        
        // Register Elementor widgets - single registration point
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        
        // Register styles for Elementor to use in editor and frontend
        add_action('elementor/frontend/after_register_styles', array($this, 'register_elementor_styles'));
        
        // Register scripts for Elementor to use in editor and frontend
        add_action('elementor/frontend/after_register_scripts', array($this, 'register_elementor_scripts'));
        
        // Register course-mode.css globally (needed for shortcode pages without Elementor)
        add_action('wp_enqueue_scripts', array($this, 'register_global_styles'), 5);
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('videohub360_videos', array($this, 'enhanced_videos_shortcode'));
        add_shortcode('videohub360_hero', array($this, 'hero_banner_shortcode'));
        add_shortcode('vh360_course_catalog', array($this, 'course_catalog_shortcode'));
    }
    
    /**
     * Register Elementor widgets
     * 
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_elementor_widgets($widgets_manager) {
        // Ensure Elementor is loaded
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Load videos widget
        require_once __DIR__ . '/elementor-videohub360-widget.php';
        if (class_exists('Elementor_VideoHub360_Videos_Widget')) {
            $widgets_manager->register(new Elementor_VideoHub360_Videos_Widget());
        }
        
        // Load hero widget
        require_once __DIR__ . '/elementor-videohub360-hero-widget.php';
        if (class_exists('Elementor_VideoHub360_Hero_Widget')) {
            $widgets_manager->register(new Elementor_VideoHub360_Hero_Widget());
        }
        
        // Load live now widget
        require_once __DIR__ . '/elementor-videohub360-live-now-widget.php';
        if (class_exists('Elementor_VideoHub360_Live_Now_Widget')) {
            $widgets_manager->register(new Elementor_VideoHub360_Live_Now_Widget());
        }
        
        // Load continue watching widget
        require_once __DIR__ . '/elementor-videohub360-continue-watching-widget.php';
        if (class_exists('Elementor_VideoHub360_Continue_Watching_Widget')) {
            $widgets_manager->register(new Elementor_VideoHub360_Continue_Watching_Widget());
        }
        
        // Load course catalog widget
        require_once __DIR__ . '/elementor-videohub360-course-catalog-widget.php';
        if (class_exists('Elementor_VideoHub360_Course_Catalog_Widget')) {
            $widgets_manager->register(new Elementor_VideoHub360_Course_Catalog_Widget());
        }
    }
    
    /**
     * Register styles for Elementor widgets
     * 
     * This ensures vh360-frontend and vh360-variables are properly registered
     * so Elementor can include them in its CSS generation for pages using our widgets.
     */
    public function register_elementor_styles() {
        // Register variables.css
        $variables_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/variables.css';
        $variables_css_url = VIDEOHUB360_ASSETS_URL . 'css/variables.css';
        $variables_ver = file_exists($variables_css_path) ? filemtime($variables_css_path) : VIDEOHUB360_VERSION;
        
        wp_register_style(
            'vh360-variables',
            $variables_css_url,
            array(),
            $variables_ver
        );
        
        // Register frontend.css (depends on variables)
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/frontend.css';
        $css_url = VIDEOHUB360_ASSETS_URL . 'css/frontend.css';
        $css_ver = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;
        
        wp_register_style(
            'vh360-frontend',
            $css_url,
            array('vh360-variables'),
            $css_ver
        );
        
        // Register course-mode.css so the shortcode and widget can enqueue it on demand
        $this->maybe_register_course_mode_style();
    }
    
    /**
     * Register course-mode.css if not already registered.
     * Shared by register_elementor_styles() and register_global_styles().
     */
    private function maybe_register_course_mode_style() {
        if ( wp_style_is('vh360-course-mode', 'registered') ) {
            return;
        }
        $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/course-mode.css';
        $css_url  = VIDEOHUB360_ASSETS_URL . 'css/course-mode.css';
        $css_ver  = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;

        wp_register_style( 'vh360-course-mode', $css_url, array(), $css_ver );
    }
    
    /**
     * Register scripts for Elementor widgets
     * 
     * Hook: elementor/frontend/after_register_scripts
     * 
     * Registers scripts so Elementor widgets can reference them via get_script_depends().
     * This ensures scripts load correctly when widgets are used standalone.
     */
    public function register_elementor_scripts() {
        // Register simplified mobile controls
        $mobile_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/simplified-mobile-controls.js';
        $mobile_js_url = VIDEOHUB360_ASSETS_URL . 'js/simplified-mobile-controls.js';
        $mobile_js_ver = file_exists($mobile_js_path) ? filemtime($mobile_js_path) : VIDEOHUB360_VERSION;
        
        wp_register_script(
            'vh360-simplified-mobile-controls',
            $mobile_js_url,
            array(),
            $mobile_js_ver,
            true
        );
        
        // Register view layout manager
        $view_layout_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/view-layout-manager.js';
        $view_layout_js_url = VIDEOHUB360_ASSETS_URL . 'js/view-layout-manager.js';
        $view_layout_js_ver = file_exists($view_layout_js_path) ? filemtime($view_layout_js_path) : VIDEOHUB360_VERSION;
        
        wp_register_script(
            'vh360-view-layout-manager',
            $view_layout_js_url,
            array('vh360-simplified-mobile-controls'),
            $view_layout_js_ver,
            true
        );
        
        // Register frontend-core.js (contains batch live viewer update functionality)
        $frontend_core_js_path = VIDEOHUB360_PLUGIN_DIR . 'assets/js/frontend-core.js';
        $frontend_core_js_url = VIDEOHUB360_ASSETS_URL . 'js/frontend-core.js';
        $frontend_core_js_ver = file_exists($frontend_core_js_path) ? filemtime($frontend_core_js_path) : VIDEOHUB360_VERSION;
        
        wp_register_script(
            'vh360-frontend-core',
            $frontend_core_js_url,
            array('jquery', 'vh360-view-layout-manager'),
            $frontend_core_js_ver,
            true
        );
        
        // Add minimal localized data needed for batch live viewer functionality
        // Note: Full localized data is added by VideoHub360_Frontend::enqueue_frontend_assets()
        // This minimal version ensures AJAX works even when widgets load via Elementor
        wp_localize_script('vh360-frontend-core', 'vh360Data', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'chatNonce' => wp_create_nonce('videohub360_chat_nonce'),
            'watchNonce' => wp_create_nonce('vh360_watch_progress_nonce'),
            'videoReactionNonce' => wp_create_nonce('vh360_video_reaction'),
            'playlistNonce' => wp_create_nonce('vh360_playlist'),
        ));
    }
    
    /**
     * Register global styles (non-Elementor pages)
     *
     * Registers course-mode.css so the shortcode can call wp_enqueue_style()
     * on any page type.
     */
    public function register_global_styles() {
        $this->maybe_register_course_mode_style();
    }
    
    /**
     * Enhanced videos shortcode handler
     */
    public function enhanced_videos_shortcode($atts) {
        $atts = shortcode_atts(array(
            // Basic display options
            'display' => 'grid',           // grid or list
            'posts' => 6,                  // number of videos to show
            
            // Category/Taxonomy filtering
            'category' => '',              // category slug or ID (videohub360_cat)
            'series' => '',                // series slug or ID (videohub360_series)
            'location' => '',              // location slug or ID (videohub360_location)
            'tag' => '',                   // post tags (slug or ID)
            
            // Sorting & Ordering
            'orderby' => 'date',           // date, title, views, menu_order, rand
            'order' => 'DESC',             // ASC or DESC
            'meta_key' => '',              // for custom field sorting
            
            // Video type filtering
            'video_type' => 'all',         // all, live, regular, embed
            'live_only' => 'no',           // yes/no - show only live videos
            'exclude_live' => 'no',        // yes/no - exclude live videos
            
            // Display customization
            'columns' => 'auto',           // auto, 1, 2, 3, 4 for grid view
            'grid_gap' => '20px',          // spacing between grid items
            'image_ratio' => '16:9',       // 16:9, 4:3, 1:1
            'show_views' => 'yes',         // yes/no
            'show_date' => 'yes',          // yes/no
            'show_excerpt' => 'no',        // yes/no
            'show_live_badge' => 'yes',    // yes/no
            'excerpt_length' => 120,       // number of characters
            'badge_color' => '#e53935',    // live badge color
            'badge_text' => esc_html__('LIVE', 'videohub360'),        // live badge text
            
            // Author display options
            'show_author' => 'yes',        // yes/no - show author badge
            'show_avatar' => 'yes',        // yes/no - show avatar in author badge
            'show_username' => 'yes',      // yes/no - show @username (currently not displayed per design)
            
            // Exclusion/Inclusion options
            'exclude' => '',               // comma-separated post IDs to exclude
            'exclude_current' => 'no',     // yes/no - exclude current post
            'include' => '',               // comma-separated post IDs to include only
        ), $atts, 'videohub360_videos');

        // Sanitize and validate inputs
        $display = sanitize_text_field($atts['display']);
        $posts_per_page = max(1, min(30, intval($atts['posts'])));
        $orderby = sanitize_text_field($atts['orderby']);
        $order = strtoupper(sanitize_text_field($atts['order'])) === 'ASC' ? 'ASC' : 'DESC';
        $columns = sanitize_text_field($atts['columns']);
        $grid_gap = sanitize_text_field($atts['grid_gap']);
        $image_ratio = sanitize_text_field($atts['image_ratio']);
        $excerpt_length = max(50, min(500, intval($atts['excerpt_length'])));
        
        // Build WP_Query arguments
        $query_args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => $posts_per_page,
            'post_status' => 'publish',
        );
        
        // Handle ordering
        if ($orderby === 'views') {
            $query_args['meta_key'] = '_videohub360_post_views_count';
            $query_args['orderby'] = 'meta_value_num';
        } elseif ($orderby === 'rand') {
            $query_args['orderby'] = 'rand';
        } else {
            $query_args['orderby'] = $orderby;
        }
        $query_args['order'] = $order;
        
        // Handle taxonomy filters
        $tax_query = array();
        if (!empty($atts['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category']),
            );
        }
        if (!empty($atts['series'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_series',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['series']),
            );
        }
        if (!empty($atts['location'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_location',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['location']),
            );
        }
        if (!empty($atts['tag'])) {
            $tax_query[] = array(
                'taxonomy' => 'videohub360_tag',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['tag']),
            );
        }
        
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // Handle video type and live filtering
        $meta_query = array();
        if ($atts['live_only'] === 'yes') {
            $meta_query[] = array(
                'key' => '_vh360_is_live',
                'value' => 'yes',
                'compare' => '='
            );
        } elseif ($atts['exclude_live'] === 'yes') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_vh360_is_live',
                    'value' => 'no',
                    'compare' => '='
                ),
                array(
                    'key' => '_vh360_is_live',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        // Always exclude community Live Rooms from widget/shortcode listings.
        // Live Rooms are videohub360 posts with _vh360_context = 'live_room'.
        // We still want to include:
        // - Normal videos (context = 'default')
        // - Older videos with no _vh360_context meta set.
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_vh360_context',
                'value'   => 'live_room',
                'compare' => '!=',
            ),
            array(
                'key'     => '_vh360_context',
                'compare' => 'NOT EXISTS',
            ),
        );

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }


        // Handle include/exclude
        if (!empty($atts['include'])) {
            $include_ids = array_map('intval', explode(',', $atts['include']));
            $query_args['post__in'] = $include_ids;
        }
        
        if (!empty($atts['exclude'])) {
            $exclude_ids = array_map('intval', explode(',', $atts['exclude']));
            $query_args['post__not_in'] = $exclude_ids;
        }
        
        // Exclude current post if requested
        if ($atts['exclude_current'] === 'yes' && is_singular()) {
            global $post;
            if ($post && $post->ID) {
                if (!isset($query_args['post__not_in'])) {
                    $query_args['post__not_in'] = array();
                }
                $query_args['post__not_in'][] = $post->ID;
            }
        }

        $query = new WP_Query($query_args);
        
        // Dynamic column settings for grid
        $column_class = 'auto-columns';
        
        if ($columns !== 'auto' && $display === 'grid') {
            $columns_int = intval($columns);
            if ($columns_int >= 1 && $columns_int <= 4) {
                $column_class = 'cols-' . $columns_int;
            }
        }
        
        // Aspect ratio class
        $ratio_class = 'ratio-16-9'; // default
        if ($image_ratio === '4:3') {
            $ratio_class = 'ratio-4-3';
        } elseif ($image_ratio === '1:1') {
            $ratio_class = 'ratio-1-1';
        }
        
        ob_start();
        
        // Ensure we have the CSS styles
        $this->ensure_shortcode_styles();
        
        if (!$query->have_posts()) {
            wp_reset_postdata();
            return '<div class="videohub360-no-videos-message">' . esc_html__('No videos found.', 'videohub360') . '</div>';
        }
        
        ?>
        <div class="videohub360-videos-<?php echo esc_attr($display); ?> <?php echo esc_attr($column_class); ?> <?php echo esc_attr($ratio_class); ?>" style="--grid-gap: <?php echo esc_attr($grid_gap); ?>;">
            <?php if ($query->have_posts()): ?>
                <?php while ($query->have_posts()): $query->the_post();
                    echo $this->render_video_card(get_the_ID(), $atts);
                endwhile; ?>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Ensure shortcode styles are loaded
     */
    private function ensure_shortcode_styles() {
        // variables.css first
        if (!wp_style_is('vh360-variables')) {
            $variables_css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/variables.css';
            $variables_css_url  = VIDEOHUB360_ASSETS_URL . 'css/variables.css';
            $variables_ver = file_exists($variables_css_path) ? filemtime($variables_css_path) : VIDEOHUB360_VERSION;

            wp_enqueue_style('vh360-variables', $variables_css_url, array(), $variables_ver);
        }

        // frontend.css depends on variables
        if (!wp_style_is('vh360-frontend')) {
            $css_path = VIDEOHUB360_PLUGIN_DIR . 'assets/css/frontend.css';
            $css_url  = VIDEOHUB360_ASSETS_URL . 'css/frontend.css';
            $css_ver  = file_exists($css_path) ? filemtime($css_path) : VIDEOHUB360_VERSION;

            wp_enqueue_style('vh360-frontend', $css_url, array('vh360-variables'), $css_ver);
        }
    }
    
    /**
     * Hero banner shortcode handler
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered hero banner HTML
     */
    public function hero_banner_shortcode($atts) {
        // Enqueue hero assets
        $this->enqueue_hero_assets();
        
        // Parse attributes
        $atts = shortcode_atts(array(
            // Mode
            'mode' => 'single',                    // single|slider
            
            // Layout
            'layout' => 'video_left',              // video_left|video_right
            'aspect_ratio' => '16:9',              // 16:9|4:3|1:1
            'theme' => 'light',                    // light|dark|transparent|custom
            'custom_bg_color' => '#ffffff',        // custom background color (hex)
            'custom_text_color' => '#000000',      // custom text color (hex)
            'gap' => '40px',                       // spacing between columns
            'padding' => '60px 20px',              // section padding
            'max_width' => '600px',                // max width of content area
            
            // Slider settings
            'show_arrows' => 'yes',                // yes|no
            'show_dots' => 'yes',                  // yes|no
            'transition_type' => 'slide',          // slide|fade
            'autoplay' => 'no',                    // yes|no
            'autoplay_delay' => '5000',            // milliseconds
            'pause_on_hover' => 'yes',             // yes|no
            
            // Single slide content (for mode=single)
            'video_type' => 'thumbnail',           // thumbnail|mp4|embed|html
            'poster' => '',                        // poster image URL
            'video_url' => '',                     // MP4 URL
            'embed_url' => '',                     // YouTube/Vimeo/Twitch URL
            'custom_html' => '',                   // Custom HTML (sanitized)
            'video_autoplay' => 'no',              // yes|no (muted only)
            'video_loop' => 'no',                  // yes|no
            'video_controls' => 'yes',             // yes|no
            'video_preload' => 'metadata',         // none|metadata|auto
            'click_action' => 'open_single',       // open_single|open_modal|swap_to_video
            'link_url' => '',                      // Link URL for thumbnail click
            
            // Content
            'eyebrow' => '',                       // Badge/eyebrow text
            'headline' => '',                      // Main headline
            'subhead' => '',                       // Subheadline
            'cta1_label' => '',                    // CTA 1 label
            'cta1_url' => '',                      // CTA 1 URL
            'cta1_new_tab' => 'no',                // yes|no
            'cta2_label' => '',                    // CTA 2 label
            'cta2_url' => '',                      // CTA 2 URL
            'cta2_new_tab' => 'no',                // yes|no
            'icon_url' => '',                      // Small icon/logo URL
            
            // Multi-slide support
            'include' => '',                       // Post IDs for slide content (future)
        ), $atts, 'videohub360_hero');
        
        // Build configuration array
        $config = array(
            'mode' => sanitize_text_field($atts['mode']),
            'layout' => sanitize_text_field($atts['layout']),
            'aspect_ratio' => sanitize_text_field($atts['aspect_ratio']),
            'theme' => sanitize_text_field($atts['theme']),
            'custom_bg_color' => sanitize_text_field($atts['custom_bg_color']),
            'custom_text_color' => sanitize_text_field($atts['custom_text_color']),
            'gap' => sanitize_text_field($atts['gap']),
            'padding' => sanitize_text_field($atts['padding']),
            'max_width' => sanitize_text_field($atts['max_width']),
            'show_arrows' => $atts['show_arrows'] === 'yes',
            'show_dots' => $atts['show_dots'] === 'yes',
            'transition_type' => in_array(sanitize_text_field($atts['transition_type']), array('slide', 'fade'), true) ? sanitize_text_field($atts['transition_type']) : 'slide',
            'autoplay' => $atts['autoplay'] === 'yes',
            'autoplay_delay' => intval($atts['autoplay_delay']),
            'pause_on_hover' => $atts['pause_on_hover'] === 'yes',
            'slides' => array(
                array(
                    'video_type' => sanitize_text_field($atts['video_type']),
                    'poster' => esc_url_raw($atts['poster']),
                    'video_url' => esc_url_raw($atts['video_url']),
                    'embed_url' => esc_url_raw($atts['embed_url']),
                    'custom_html' => $atts['custom_html'],
                    'autoplay' => $atts['video_autoplay'] === 'yes',
                    'loop' => $atts['video_loop'] === 'yes',
                    'controls' => $atts['video_controls'] === 'yes',
                    'preload' => sanitize_text_field($atts['video_preload']),
                    'click_action' => sanitize_text_field($atts['click_action']),
                    'link_url' => esc_url_raw($atts['link_url']),
                    'eyebrow' => sanitize_text_field($atts['eyebrow']),
                    'headline' => sanitize_text_field($atts['headline']),
                    'subhead' => sanitize_text_field($atts['subhead']),
                    'cta1_label' => sanitize_text_field($atts['cta1_label']),
                    'cta1_url' => esc_url_raw($atts['cta1_url']),
                    'cta1_new_tab' => $atts['cta1_new_tab'] === 'yes',
                    'cta2_label' => sanitize_text_field($atts['cta2_label']),
                    'cta2_url' => esc_url_raw($atts['cta2_url']),
                    'cta2_new_tab' => $atts['cta2_new_tab'] === 'yes',
                    'icon_url' => esc_url_raw($atts['icon_url']),
                ),
            ),
        );
        
        // Load renderer
        if (!class_exists('VideoHub360_Hero_Renderer')) {
            require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-hero-renderer.php';
        }
        
        // Render and return
        return VideoHub360_Hero_Renderer::render($config);
    }
    
    /**
     * Enqueue hero banner assets
     */
    private function enqueue_hero_assets() {
        // Enqueue CSS
        if (!wp_style_is('vh360-hero')) {
            wp_enqueue_style(
                'vh360-hero',
                VIDEOHUB360_ASSETS_URL . 'css/hero.css',
                array(),
                VIDEOHUB360_VERSION
            );
        }
        
        // Enqueue JS
        if (!wp_script_is('vh360-hero')) {
            wp_enqueue_script(
                'vh360-hero',
                VIDEOHUB360_ASSETS_URL . 'js/hero.js',
                array(),
                VIDEOHUB360_VERSION,
                true
            );
        }
    }
    
    /**
     * Render a single video card
     * 
     * @param int $post_id The post ID to render
     * @param array $args Configuration arguments
     * @return string Rendered card HTML
     */
    public function render_video_card($post_id, $args = array()) {
        // Default arguments
        $defaults = array(
            'show_author' => 'yes',
            'show_avatar' => 'yes',
            'show_views' => 'yes',
            'show_date' => 'yes',
            'show_excerpt' => 'no',
            'show_live_badge' => 'yes',
            'show_live_viewers' => 'yes',
            'excerpt_length' => 120,
            'badge_text' => esc_html__('LIVE', 'videohub360'),
            'badge_color' => '#e53935',
            'progress_percent' => 0, // For continue watching
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Get post data
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Setup post data for template tags
        global $post_backup;
        $post_backup = $GLOBALS['post'];
        $GLOBALS['post'] = $post;
        setup_postdata($post);
        
        $views = get_post_meta($post_id, '_videohub360_post_views_count', true);
        $views = $views ? $views : 0;
        
        // Check live badge settings - respect stream stopped status
        $is_live = get_post_meta($post_id, '_vh360_is_live', true);
        $stream_stopped = get_post_meta($post_id, '_vh360_stream_stopped', true);
        $live_badge = get_post_meta($post_id, '_vh360_live_badge', true);
        
        // Show live badge if:
        // 1. Live badge setting is not explicitly disabled AND
        // 2. Post is marked as live AND 
        // 3. Stream is not stopped AND
        // 4. Args setting allows live badge
        $show_live_badge = $args['show_live_badge'] === 'yes' && 
                          $live_badge !== 'no' && 
                          $is_live === 'yes' && 
                          $stream_stopped !== 'yes';
        
        $badge_text = !empty($args['badge_text']) ? $args['badge_text'] : esc_html__('LIVE', 'videohub360');
        $badge_color = !empty($args['badge_color']) ? $args['badge_color'] : '#e53935';
        
        // Gather author data for YouTube-style details row
        $author_id    = (int) get_post_field('post_author', $post_id);
        $display_name = get_the_author_meta('display_name', $author_id);
        $profile_url  = function_exists('videohub360_get_profile_url') ? videohub360_get_profile_url($author_id) : get_author_posts_url($author_id);
        $avatar_url   = function_exists('videohub360_get_avatar_url') ? videohub360_get_avatar_url($author_id, 36) : get_avatar_url($author_id, array('size' => 36));

        ob_start();
        ?>
        <div class="videohub360-videos-item">
            <a href="<?php the_permalink(); ?>" class="videohub360-videos-thumb-wrap">
                <?php if (has_post_thumbnail()) {
                    the_post_thumbnail('medium', array('class' => 'videohub360-videos-thumb', 'alt' => get_the_title()));
                } else { ?>
                    <div class="videohub360-videos-thumb" style="background:#ccc; width:100%; height:100%;"></div>
                <?php } ?>
                <?php if ($show_live_badge): ?>
                    <span class="videohub360-live-badge" style="background-color: <?php echo esc_attr($badge_color); ?>;">
                        <?php echo esc_html($badge_text); ?>
                    </span>
                <?php endif; ?>
                <?php if ($args['show_live_viewers'] === 'yes' && $show_live_badge): ?>
                    <span class="vh360-live-viewers-badge" data-post-id="<?php echo esc_attr($post_id); ?>">
                        <span class="vh360-viewer-count">•</span> <?php echo esc_html__('watching', 'videohub360'); ?>
                    </span>
                <?php endif; ?>
                <?php if ($args['progress_percent'] > 0 && $args['progress_percent'] < 100): ?>
                    <div class="vh360-progress-bar">
                        <div class="vh360-progress-fill" style="width: <?php echo esc_attr($args['progress_percent']); ?>%;"></div>
                    </div>
                <?php endif; ?>
                <span class="videohub360-videos-play-btn" aria-label="<?php echo esc_attr__('Play video', 'videohub360'); ?>">
                    <svg viewBox="0 0 60 60">
                        <circle cx="30" cy="30" r="28" opacity="0.18"/>
                        <polygon points="24,18 46,30 24,42" />
                    </svg>
                </span>
            </a>
            
            <div class="videohub360-videos-content">
                <div class="videohub360-videos-details">
                    <?php if ($args['show_author'] === 'yes' && $args['show_avatar'] === 'yes'): ?>
                        <a href="<?php echo esc_url($profile_url); ?>" class="videohub360-videos-avatar-link" tabindex="-1" aria-hidden="true">
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="videohub360-videos-avatar" width="36" height="36" loading="lazy">
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <div class="videohub360-videos-text">
                        <a href="<?php the_permalink(); ?>" class="videohub360-videos-title">
                            <?php the_title(); ?>
                        </a>

                        <?php if ($args['show_author'] === 'yes'): ?>
                            <a href="<?php echo esc_url($profile_url); ?>" class="videohub360-videos-channel">
                                <?php echo esc_html($display_name); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($args['show_views'] === 'yes' || $args['show_date'] === 'yes'): ?>
                            <div class="videohub360-videos-meta">
                                <?php if ($args['show_views'] === 'yes'): ?>
                                    <span class="views-count"><?php echo videohub360_compact_views($views); ?> <?php echo esc_html__('views', 'videohub360'); ?></span>
                                <?php endif; ?>
                                <?php if ($args['show_views'] === 'yes' && $args['show_date'] === 'yes'): ?>
                                    <span class="videohub360-meta-separator">•</span>
                                <?php endif; ?>
                                <?php if ($args['show_date'] === 'yes'): ?>
                                    <span class="publish-date"><?php echo get_the_date(); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($args['show_excerpt'] === 'yes'): ?>
                            <div class="videohub360-videos-excerpt">
                                <?php 
                                $excerpt = get_the_excerpt();
                                $excerpt_length = intval($args['excerpt_length']);
                                if (strlen($excerpt) > $excerpt_length) {
                                    echo esc_html(substr($excerpt, 0, $excerpt_length)) . '...';
                                } else {
                                    echo esc_html($excerpt);
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        // Restore original post data
        $GLOBALS['post'] = $post_backup;
        wp_reset_postdata();
        
        return ob_get_clean();
    }

    /**
     * Course Catalog shortcode handler
     *
     * Renders all videohub360_series terms as course cards.
     * Only active when Course / Lesson Features are enabled.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function course_catalog_shortcode( $atts ) {
        // Guard: Course / Lesson Features must be enabled
        if ( ! function_exists('videohub360_course_features_enabled') || ! videohub360_course_features_enabled() ) {
            if ( current_user_can('manage_options') ) {
                return '<p class="vh360-course-catalog-disabled">' . esc_html__('Course / Lesson Features must be enabled to use the Course Catalog.', 'videohub360') . '</p>';
            }
            return '';
        }

        $atts = shortcode_atts( array(
            'columns'            => 3,
            'limit'              => 12,
            'hide_empty'         => 'yes',
            'orderby'            => 'meta_order',
            'order'              => 'ASC',
            'show_filters'       => 'no',
            'show_search'        => 'yes',
            'show_sort'          => 'yes',
            'search_placeholder' => '',
            'show_result_count'  => 'yes',
            'show_instructor'    => 'yes',
            'show_lesson_count'  => 'yes',
            'show_access_badge'  => 'yes',
            'show_description'   => 'yes',
        ), $atts, 'vh360_course_catalog' );

        // Enqueue styles
        wp_enqueue_style('vh360-course-mode');

        // Fix: query ALL terms first (without a limit) so we can sort correctly,
        // then apply the limit via array_slice() after sorting.
        $terms = get_terms( array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => ( $atts['hide_empty'] === 'yes' ),
            // Omitting 'number' fetches all terms so we can sort before limiting.
        ) );

        if ( is_wp_error($terms) || empty($terms) ) {
            return '<p class="vh360-course-catalog-empty">' . esc_html__('No courses found.', 'videohub360') . '</p>';
        }

        // Sort all terms, then slice to the requested limit.
        $desc = ( strtoupper( $atts['order'] ) === 'DESC' );

        if ( $atts['orderby'] === 'meta_order' ) {
            usort( $terms, function( $a, $b ) use ( $desc ) {
                $order_a = (int) get_term_meta( $a->term_id, '_vh360_course_order', true );
                $order_b = (int) get_term_meta( $b->term_id, '_vh360_course_order', true );
                $cmp = ( $order_a === $order_b ) ? strcmp( $a->name, $b->name ) : ( $order_a <=> $order_b );
                return $desc ? -$cmp : $cmp;
            } );
        } elseif ( $atts['orderby'] === 'name' ) {
            usort( $terms, function( $a, $b ) use ( $desc ) {
                $cmp = strcmp( $a->name, $b->name );
                return $desc ? -$cmp : $cmp;
            } );
        }

        $limit = absint( $atts['limit'] );
        if ( $limit > 0 ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        $columns            = max( 1, min( 6, absint( $atts['columns'] ) ) );
        $show_filters       = ( $atts['show_filters']      === 'yes' );
        $show_search        = ( $atts['show_search']       === 'yes' );
        $show_sort          = ( $atts['show_sort']         === 'yes' );
        $show_result_count  = ( $atts['show_result_count'] === 'yes' );
        $search_placeholder = ( $atts['search_placeholder'] !== '' )
            ? $atts['search_placeholder']
            : __( 'Search courses...', 'videohub360' );

        // Unique ID so multiple catalogs on the same page work independently.
        static $catalog_instance = 0;
        $catalog_id = 'vh360-catalog-' . ( ++$catalog_instance );

        $show_controls = ( $show_search || $show_filters || $show_sort || $show_result_count );

        ob_start();
        ?>
        <div class="vh360-course-catalog" id="<?php echo esc_attr($catalog_id); ?>" data-columns="<?php echo esc_attr($columns); ?>">

            <?php if ( $show_controls ) : ?>
            <div class="vh360-course-catalog-controls">

                <?php if ( $show_search ) : ?>
                <div class="vh360-course-catalog-search-wrap">
                    <input
                        type="search"
                        class="vh360-course-catalog-search"
                        placeholder="<?php echo esc_attr( $search_placeholder ); ?>"
                        aria-label="<?php echo esc_attr( $search_placeholder ); ?>"
                    >
                </div>
                <?php endif; ?>

                <div class="vh360-course-catalog-filter-row">
                    <?php if ( $show_filters ) : ?>
                    <div class="vh360-course-catalog-filter-pills" role="group" aria-label="<?php esc_attr_e( 'Filter courses', 'videohub360' ); ?>">
                        <button type="button" class="vh360-catalog-filter-pill is-active" data-filter="all">
                            <?php esc_html_e( 'All Courses', 'videohub360' ); ?>
                        </button>
                        <button type="button" class="vh360-catalog-filter-pill" data-filter="level:beginner">
                            <?php esc_html_e( 'Beginner', 'videohub360' ); ?>
                        </button>
                        <button type="button" class="vh360-catalog-filter-pill" data-filter="level:intermediate">
                            <?php esc_html_e( 'Intermediate', 'videohub360' ); ?>
                        </button>
                        <button type="button" class="vh360-catalog-filter-pill" data-filter="level:advanced">
                            <?php esc_html_e( 'Advanced', 'videohub360' ); ?>
                        </button>
                        <button type="button" class="vh360-catalog-filter-pill" data-filter="access:free">
                            <?php esc_html_e( 'Free Access', 'videohub360' ); ?>
                        </button>
                        <button type="button" class="vh360-catalog-filter-pill" data-filter="access:member">
                            <?php esc_html_e( 'Member Access', 'videohub360' ); ?>
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_sort ) : ?>
                    <div class="vh360-course-catalog-sort-wrap">
                        <select class="vh360-course-catalog-sort" aria-label="<?php esc_attr_e( 'Sort courses', 'videohub360' ); ?>">
                            <option value="default"><?php esc_html_e( 'Default Order', 'videohub360' ); ?></option>
                            <option value="title-asc"><?php esc_html_e( 'Name A–Z', 'videohub360' ); ?></option>
                            <option value="title-desc"><?php esc_html_e( 'Name Z–A', 'videohub360' ); ?></option>
                            <option value="lessons-desc"><?php esc_html_e( 'Most Lessons', 'videohub360' ); ?></option>
                            <option value="lessons-asc"><?php esc_html_e( 'Fewest Lessons', 'videohub360' ); ?></option>
                            <option value="order-asc"><?php esc_html_e( 'Course Order', 'videohub360' ); ?></option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if ( $show_search || $show_filters || $show_sort ) : ?>
                    <button type="button" class="vh360-course-catalog-clear" style="display:none;">
                        <?php esc_html_e( 'Clear Filters', 'videohub360' ); ?>
                    </button>
                    <?php endif; ?>
                </div><!-- .vh360-course-catalog-filter-row -->

                <?php if ( $show_result_count ) : ?>
                <p class="vh360-course-catalog-count" aria-live="polite"></p>
                <?php endif; ?>

            </div><!-- .vh360-course-catalog-controls -->
            <?php endif; ?>

            <div class="vh360-course-catalog-grid vh360-course-catalog-cols-<?php echo esc_attr($columns); ?>">
                <?php foreach ( $terms as $term ) :
                    $subtitle      = get_term_meta( $term->term_id, '_vh360_course_subtitle', true );
                    $short_desc    = get_term_meta( $term->term_id, '_vh360_course_short_description', true );
                    $level         = get_term_meta( $term->term_id, '_vh360_course_level', true );
                    $duration      = get_term_meta( $term->term_id, '_vh360_course_duration', true );
                    $image_id      = (int) get_term_meta( $term->term_id, '_vh360_course_featured_image_id', true );
                    $required_plan = function_exists('videohub360_get_course_required_membership')
                                        ? videohub360_get_course_required_membership( $term->term_id )
                                        : '';
                    $lessons       = function_exists('videohub360_get_course_lessons')
                                        ? videohub360_get_course_lessons( $term->term_id )
                                        : array();
                    $instructor    = ( $atts['show_instructor'] === 'yes' && function_exists('videohub360_get_course_instructor') )
                                        ? videohub360_get_course_instructor( $term->term_id )
                                        : false;
                    $term_link     = get_term_link( $term );
                    $lesson_count  = count( $lessons );

                    // Normalize level for data attribute (lowercase, trimmed).
                    $level_key = strtolower( trim( $level ) );

                    // Determine access type for data attribute.
                    $access_key = ( empty($required_plan) || $required_plan === false ) ? 'free' : 'member';

                    // Access badge label
                    $access_badge_label = '';
                    if ( $atts['show_access_badge'] === 'yes' ) {
                        if ( $access_key === 'free' ) {
                            $access_badge_label = esc_html__( 'Free Access', 'videohub360' );
                        } elseif ( $required_plan === 'any' ) {
                            $access_badge_label = esc_html__( 'Member Access', 'videohub360' );
                        } else {
                            $access_badge_label = ucwords( str_replace( array('_', '-'), ' ', $required_plan ) );
                        }
                    }

                    // Build searchable blob for client-side search.
                    $search_blob = implode( ' ', array_filter( array(
                        $term->name,
                        $term->description,
                        $subtitle,
                        $short_desc,
                        $level,
                        $duration,
                        $instructor ? $instructor->display_name : '',
                        $access_badge_label,
                    ) ) );

                    $course_order = (int) get_term_meta( $term->term_id, '_vh360_course_order', true );
                ?>
                <div class="vh360-course-catalog-card"
                     data-level="<?php echo esc_attr( $level_key ); ?>"
                     data-access="<?php echo esc_attr( $access_key ); ?>"
                     data-title="<?php echo esc_attr( strtolower( $term->name ) ); ?>"
                     data-search="<?php echo esc_attr( strtolower( wp_strip_all_tags( $search_blob ) ) ); ?>"
                     data-lessons="<?php echo esc_attr( $lesson_count ); ?>"
                     data-order="<?php echo esc_attr( $course_order ); ?>">
                    <?php if ( $image_id ) : ?>
                    <a href="<?php echo esc_url( is_wp_error($term_link) ? '#' : $term_link ); ?>" class="vh360-course-catalog-image" aria-hidden="true" tabindex="-1">
                        <?php echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'class' => 'vh360-course-catalog-img', 'alt' => esc_attr($term->name) ) ); ?>
                    </a>
                    <?php else : ?>
                    <a href="<?php echo esc_url( is_wp_error($term_link) ? '#' : $term_link ); ?>" class="vh360-course-catalog-image vh360-course-catalog-image-placeholder" aria-hidden="true" tabindex="-1">
                        <span class="vh360-course-catalog-image-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        </span>
                    </a>
                    <?php endif; ?>

                    <div class="vh360-course-catalog-body">
                        <?php if ( $access_badge_label ) : ?>
                        <span class="vh360-course-catalog-badge"><?php echo esc_html($access_badge_label); ?></span>
                        <?php endif; ?>

                        <h3 class="vh360-course-catalog-title">
                            <a href="<?php echo esc_url( is_wp_error($term_link) ? '#' : $term_link ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </a>
                        </h3>

                        <?php if ( $atts['show_description'] === 'yes' && ( $subtitle || $short_desc ) ) : ?>
                        <p class="vh360-course-catalog-description">
                            <?php echo esc_html( $subtitle ?: $short_desc ); ?>
                        </p>
                        <?php endif; ?>

                        <div class="vh360-course-catalog-meta">
                            <?php if ( $level ) : ?>
                            <span class="vh360-course-catalog-meta-item vh360-course-catalog-level">
                                <?php echo esc_html( ucfirst($level) ); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ( $duration ) : ?>
                            <span class="vh360-course-catalog-meta-item vh360-course-catalog-duration">
                                <?php echo esc_html( $duration ); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ( $atts['show_lesson_count'] === 'yes' ) : ?>
                            <span class="vh360-course-catalog-meta-item vh360-course-catalog-lessons">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: %d: number of lessons */
                                        _n( '%d Lesson', '%d Lessons', $lesson_count, 'videohub360' ),
                                        $lesson_count
                                    )
                                );
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if ( $atts['show_instructor'] === 'yes' && $instructor ) : ?>
                        <div class="vh360-course-catalog-instructor">
                            <?php echo get_avatar( $instructor->ID, 24, '', $instructor->display_name, array( 'class' => 'vh360-course-catalog-instructor-avatar' ) ); ?>
                            <span class="vh360-course-catalog-instructor-name"><?php echo esc_html( $instructor->display_name ); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="vh360-course-catalog-footer">
                        <a href="<?php echo esc_url( is_wp_error($term_link) ? '#' : $term_link ); ?>" class="vh360-course-catalog-button">
                            <?php esc_html_e( 'View Course', 'videohub360' ); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ( $show_controls ) : ?>
            <p class="vh360-course-catalog-no-results" style="display:none;">
                <?php esc_html_e( 'No courses match your search or filter.', 'videohub360' ); ?>
            </p>
            <?php endif; ?>

        </div>

        <?php if ( $show_controls ) : ?>
        <script>
        (function() {
            var catalog = document.getElementById(<?php echo wp_json_encode( $catalog_id ); ?>);
            if (!catalog) return;

            var cards     = catalog.querySelectorAll('.vh360-course-catalog-card');
            var pills     = catalog.querySelectorAll('.vh360-catalog-filter-pill');
            var searchEl  = catalog.querySelector('.vh360-course-catalog-search');
            var sortEl    = catalog.querySelector('.vh360-course-catalog-sort');
            var clearBtn  = catalog.querySelector('.vh360-course-catalog-clear');
            var countEl   = catalog.querySelector('.vh360-course-catalog-count');
            var noResults = catalog.querySelector('.vh360-course-catalog-no-results');
            var grid      = catalog.querySelector('.vh360-course-catalog-grid');

            var state = { search: '', filter: 'all', sort: 'default' };

            function cardMatches(card) {
                var matchesSearch = true;
                var matchesFilter = true;

                if (state.search) {
                    var haystack = card.getAttribute('data-search') || '';
                    matchesSearch = haystack.indexOf(state.search) !== -1;
                }

                if (state.filter !== 'all') {
                    var parts = state.filter.split(':');
                    matchesFilter = card.getAttribute('data-' + parts[0]) === parts[1];
                }

                return matchesSearch && matchesFilter;
            }

            function sortCards() {
                if (!grid) return;
                var sorted = Array.prototype.slice.call(cards);
                sorted.sort(function(a, b) {
                    switch (state.sort) {
                        case 'title-asc':
                            return (a.dataset.title || '').localeCompare(b.dataset.title || '', undefined, {sensitivity:'base'});
                        case 'title-desc':
                            return (b.dataset.title || '').localeCompare(a.dataset.title || '', undefined, {sensitivity:'base'});
                        case 'lessons-desc':
                            return parseInt(b.dataset.lessons || '0', 10) - parseInt(a.dataset.lessons || '0', 10);
                        case 'lessons-asc':
                            return parseInt(a.dataset.lessons || '0', 10) - parseInt(b.dataset.lessons || '0', 10);
                        case 'order-asc':
                            return parseInt(a.dataset.order || '0', 10) - parseInt(b.dataset.order || '0', 10);
                        default:
                            return 0;
                    }
                });
                sorted.forEach(function(card) { grid.appendChild(card); });
            }

            function updateCount(visible) {
                if (!countEl) return;
                var total = cards.length;
                var fmt;
                if (visible === total) {
                    fmt = total === 1
                        ? (<?php echo wp_json_encode( __( 'Showing %d course', 'videohub360' ) ); ?>)
                        : (<?php echo wp_json_encode( __( 'Showing %d courses', 'videohub360' ) ); ?>);
                    countEl.textContent = fmt.replace('%d', total);
                } else {
                    fmt = total === 1
                        ? (<?php echo wp_json_encode( __( 'Showing %d of %d course', 'videohub360' ) ); ?>)
                        : (<?php echo wp_json_encode( __( 'Showing %d of %d courses', 'videohub360' ) ); ?>);
                    countEl.textContent = fmt.replace('%d', visible).replace('%d', total);
                }
            }

            function applyState() {
                var visible = 0;
                cards.forEach(function(card) {
                    var show = cardMatches(card);
                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                sortCards();

                if (noResults) noResults.style.display = (visible === 0) ? '' : 'none';
                updateCount(visible);

                var isActive = (state.search !== '' || state.filter !== 'all' || state.sort !== 'default');
                if (clearBtn) clearBtn.style.display = isActive ? '' : 'none';
            }

            // Search
            if (searchEl) {
                searchEl.addEventListener('input', function() {
                    state.search = this.value.toLowerCase().trim();
                    applyState();
                });
            }

            // Filter pills
            pills.forEach(function(pill) {
                pill.addEventListener('click', function() {
                    state.filter = this.getAttribute('data-filter');
                    pills.forEach(function(p) { p.classList.remove('is-active'); });
                    this.classList.add('is-active');
                    applyState();
                });
            });

            // Sort
            if (sortEl) {
                sortEl.addEventListener('change', function() {
                    state.sort = this.value;
                    applyState();
                });
            }

            // Clear
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    state.search = '';
                    state.filter = 'all';
                    state.sort   = 'default';
                    if (searchEl) searchEl.value = '';
                    if (sortEl)   sortEl.value   = 'default';
                    pills.forEach(function(p) { p.classList.remove('is-active'); });
                    var allPill = catalog.querySelector('.vh360-catalog-filter-pill[data-filter="all"]');
                    if (allPill) allPill.classList.add('is-active');
                    applyState();
                });
            }

            // Initial count
            applyState();
        })();
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
