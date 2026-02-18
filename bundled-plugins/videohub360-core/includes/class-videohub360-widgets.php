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
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('videohub360_videos', array($this, 'enhanced_videos_shortcode'));
        add_shortcode('videohub360_hero', array($this, 'hero_banner_shortcode'));
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
                <a href="<?php the_permalink(); ?>" class="videohub360-videos-title">
                    <?php the_title(); ?>
                </a>
                
                <?php if ($args['show_author'] === 'yes'): ?>
                    <?php 
                    // videohub360_render_author_badge() handles all escaping internally
                    echo videohub360_render_author_badge($post_id, array(
                        'variant' => 'compact',
                        'show_avatar' => $args['show_avatar'] === 'yes',
                        'show_username' => false, // Only show display name
                        'avatar_size' => 32,
                    )); 
                    ?>
                <?php endif; ?>
                
                <?php if ($args['show_views'] === 'yes' || $args['show_date'] === 'yes'): ?>
                    <div class="videohub360-videos-meta">
                <?php if ($args['show_views'] === 'yes'): ?>
                            <span class="views-count"><?php echo videohub360_compact_views($views); ?> <?php echo esc_html__('views', 'videohub360'); ?></span>
                        <?php endif; ?>
                        <?php if ($args['show_date'] === 'yes'): ?>
                            <?php if ($args['show_views'] === 'yes') echo ' • '; ?>
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
        <?php
        
        // Restore original post data
        $GLOBALS['post'] = $post_backup;
        wp_reset_postdata();
        
        return ob_get_clean();
    }
}
