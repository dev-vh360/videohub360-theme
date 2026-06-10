<?php
/**
 * VideoHub360 Admin Class
 * 
 * Handles admin panel functionality including settings page and meta boxes
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_videohub360', array($this, 'save_meta_boxes'));
        
        
        // License gating (soft lock): block creating NEW videos without an active license
        add_action('load-post-new.php', array($this, 'maybe_block_new_video_creation'));
        add_action('admin_notices', array($this, 'license_required_notice'));

        // Admin AJAX hooks for stream control
        add_action('wp_ajax_vh360_admin_stop_stream', array($this, 'admin_stop_stream'));
        add_action('wp_ajax_vh360_admin_restart_stream', array($this, 'admin_restart_stream'));
    }
    
    /**
     * Add admin menu - VideoHub360 Settings submenu
     */
    public function add_admin_menu() {
        global $submenu;
        
        // Add Dashboard submenu
        add_submenu_page(
            'edit.php?post_type=videohub360',
            'VideoHub360 Dashboard',
            'Dashboard',
            'manage_options',
            'videohub360-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=videohub360',
            'VideoHub360 Settings',
            'Settings',
            'manage_options',
            'videohub360-settings',
            array($this, 'settings_page')
        );
        
        // Add Analytics submenu
        add_submenu_page(
            'edit.php?post_type=videohub360',
            'Ad Click Analytics',
            'Analytics',
            'manage_options',
            'videohub360-analytics',
            array($this, 'analytics_page')
        );
        
        // Add Shortcodes submenu
        add_submenu_page(
            'edit.php?post_type=videohub360',
            'Shortcodes',
            'Shortcodes',
            'manage_options',
            'videohub360-shortcodes',
            array($this, 'shortcodes_page')
        );
        
        // Add Import/Export submenu
        add_submenu_page(
            'edit.php?post_type=videohub360',
            'Import/Export',
            'Import/Export',
            'edit_posts',
            'videohub360-import-export',
            array($this, 'import_export_page')
        );

        // Enrollment Backfill tool (admin-only).
        if ( function_exists( 'videohub360_course_features_enabled' ) && videohub360_course_features_enabled() ) {
            add_submenu_page(
                'edit.php?post_type=videohub360',
                'Enrollment Backfill',
                'Enrollment Backfill',
                'manage_options',
                'videohub360-enrollment-backfill',
                array( $this, 'enrollment_backfill_page' )
            );
        }
        
        // Reorder submenu to make Dashboard first (after default WordPress items)
        // Priority 999 ensures this runs after all menu items are added
        add_action('admin_menu', array($this, 'reorder_submenu'), 999);
    }
    
    /**
     * Reorder submenu to place Dashboard as the first custom item
     * This places Dashboard right after "All Videos" in the menu
     */
    public function reorder_submenu() {
        global $submenu;
        
        $menu_slug = 'edit.php?post_type=videohub360';
        
        // Check if submenu exists
        if (!isset($submenu[$menu_slug]) || !is_array($submenu[$menu_slug])) {
            return;
        }
        
        // Find the Dashboard item and remove it
        $dashboard_item = null;
        foreach ($submenu[$menu_slug] as $key => $item) {
            if (isset($item[2]) && $item[2] === 'videohub360-dashboard') {
                $dashboard_item = $item;
                unset($submenu[$menu_slug][$key]);
                break;
            }
        }
        
        // If Dashboard not found, nothing to reorder
        if ($dashboard_item === null) {
            return;
        }
        
        // Rebuild the submenu array with Dashboard in position 1
        // Position 0 is typically "All Videos" from WordPress
        $reordered = array();
        $inserted = false;
        
        foreach ($submenu[$menu_slug] as $key => $item) {
            $reordered[] = $item;
            
            // Insert Dashboard after the first item (All Videos)
            if (!$inserted && count($reordered) === 1) {
                $reordered[] = $dashboard_item;
                $inserted = true;
            }
        }
        
        // If we haven't inserted yet (edge case), add Dashboard at the end
        if (!$inserted) {
            $reordered[] = $dashboard_item;
        }
        
        // Replace the submenu with reordered version
        $submenu[$menu_slug] = $reordered;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue on VideoHub360 admin pages
        if (strpos($hook, 'videohub360') === false) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'vh360-admin',
            VIDEOHUB360_ASSETS_URL . 'css/admin.css',
            array(),
            VIDEOHUB360_VERSION
        );
        
        // Enqueue dashboard-specific assets
        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) === 'videohub360-dashboard') {
            // Enqueue dashboard-specific CSS
            wp_enqueue_style(
                'vh360-admin-dashboard',
                VIDEOHUB360_ASSETS_URL . 'css/admin-dashboard.css',
                array('vh360-admin'),
                VIDEOHUB360_VERSION
            );
            
            // Enqueue Chart.js for statistics visualization
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                array(),
                '4.4.0',
                true
            );
            
            // Enqueue dashboard-specific JS
            wp_enqueue_script(
                'vh360-admin-dashboard',
                VIDEOHUB360_ASSETS_URL . 'js/admin-dashboard.js',
                array('jquery', 'chartjs'),
                VIDEOHUB360_VERSION,
                true
            );
            
            // Localize script for AJAX and translations
            wp_localize_script(
                'vh360-admin-dashboard',
                'vh360Dashboard',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vh360_dashboard_nonce'),
                    'strings' => array(
                        'loading' => __('Loading...', 'videohub360'),
                        'error' => __('Error loading data', 'videohub360')
                    )
                )
            );
        }
        
        // Enqueue admin JS and CSS on shortcodes page
        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) === 'videohub360-shortcodes') {
            // Enqueue shortcodes-specific CSS
            wp_enqueue_style(
                'vh360-admin-shortcodes',
                VIDEOHUB360_ASSETS_URL . 'css/admin-shortcodes.css',
                array('vh360-admin'),
                VIDEOHUB360_VERSION
            );
            
            // Enqueue original admin JS
            wp_enqueue_script(
                'vh360-admin',
                VIDEOHUB360_ASSETS_URL . 'js/admin.js',
                array('jquery'),
                VIDEOHUB360_VERSION,
                true
            );
            
            // Enqueue shortcodes-specific JS
            wp_enqueue_script(
                'vh360-admin-shortcodes',
                VIDEOHUB360_ASSETS_URL . 'js/admin-shortcodes.js',
                array('jquery', 'vh360-admin'),
                VIDEOHUB360_VERSION,
                true
            );
            
            // Localize script for translations
            wp_localize_script(
                'vh360-admin',
                'vh360Admin',
                array(
                    'copiedText' => __('Copied!', 'videohub360')
                )
            );
            
            // Localize shortcode builder script
            wp_localize_script(
                'vh360-admin-shortcodes',
                'vh360ShortcodeBuilder',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('vh360_shortcode_builder')
                )
            );
        }
    }
    
    /**
     * Add meta boxes for VideoHub360 posts
     */
    public function add_meta_boxes() {
        add_meta_box(
            'videohub360_video_meta',
            'VideoHub360 Video Details',
            array($this, 'video_meta_box_callback'),
            'videohub360',
            'normal',
            'high'
        );
        
        add_meta_box(
            'videohub360_sidebar_config',
            'VideoHub360 Sidebar Configuration',
            array($this, 'sidebar_config_meta_box_callback'),
            'videohub360',
            'side',
            'default'
        );
    }
    
    /**
     * VideoHub360 Settings Page
     */
    public function settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['videohub360_settings_nonce'] ?? '', 'videohub360_settings')) {
            // Update global ad URL
            update_option('videohub360_global_ad_url', esc_url_raw($_POST['videohub360_global_ad_url']));
            
            // Update global mid-roll and post-roll ad settings
            update_option('videohub360_global_midroll_ad_url', esc_url_raw($_POST['videohub360_global_midroll_ad_url']));
            update_option('videohub360_global_midroll_timing', sanitize_text_field($_POST['videohub360_global_midroll_timing']));
            update_option('videohub360_global_postroll_ad_url', esc_url_raw($_POST['videohub360_global_postroll_ad_url']));
            update_option('videohub360_global_postroll_enabled', isset($_POST['videohub360_global_postroll_enabled']) ? 1 : 0);
            
            // Update ad click-through settings
            update_option('vh360_global_ad_click_url', esc_url_raw($_POST['vh360_global_ad_click_url'] ?? ''));
            update_option('vh360_ad_click_tracking_enabled', isset($_POST['vh360_ad_click_tracking_enabled']) ? 1 : 0);
            update_option('vh360_ad_click_new_tab', isset($_POST['vh360_ad_click_new_tab']) ? 1 : 0);
            
            // Update slug settings
            $old_post_slug = $this->get_post_slug();
            $old_category_slug = $this->get_category_slug();
            $old_location_slug = $this->get_location_slug();
            $old_series_slug = $this->get_series_slug();
            
            update_option('videohub360_post_slug', sanitize_title($_POST['videohub360_post_slug']));
            update_option('videohub360_category_slug', sanitize_title($_POST['videohub360_category_slug']));
            update_option('videohub360_location_slug', sanitize_title($_POST['videohub360_location_slug']));
            update_option('videohub360_series_slug', sanitize_title($_POST['videohub360_series_slug']));
            
            // Update archive filter settings
            update_option('videohub360_show_category_filter', isset($_POST['videohub360_show_category_filter']) ? 1 : 0);
            update_option('videohub360_show_series_filter', isset($_POST['videohub360_show_series_filter']) ? 1 : 0);
            update_option('videohub360_show_location_filter', isset($_POST['videohub360_show_location_filter']) ? 1 : 0);
            update_option('videohub360_category_label', sanitize_text_field($_POST['videohub360_category_label']));
            update_option('videohub360_series_label', sanitize_text_field($_POST['videohub360_series_label']));
            update_option('videohub360_location_label', sanitize_text_field($_POST['videohub360_location_label']));
            // Archive header options
            update_option('videohub360_show_archive_header', isset($_POST['videohub360_show_archive_header']) ? 1 : 0);
            update_option('videohub360_archive_title', sanitize_text_field($_POST['videohub360_archive_title'] ?? ''));
    
            
            // Update chat settings
            update_option('videohub360_chat_enabled', isset($_POST['videohub360_chat_enabled']) ? 1 : 0);
            update_option('videohub360_chat_placement', sanitize_text_field($_POST['videohub360_chat_placement'] ?? 'inline'));
            update_option('videohub360_chat_cleanup_days', intval($_POST['videohub360_chat_cleanup_days']));
            update_option('videohub360_chat_rate_limit', intval($_POST['videohub360_chat_rate_limit']));
            update_option('videohub360_chat_message_limit', intval($_POST['videohub360_chat_message_limit']));
            
            // Update Agora settings
            update_option('vh360_agora_app_id', sanitize_text_field($_POST['vh360_agora_app_id']));
            update_option('vh360_agora_app_certificate', sanitize_text_field($_POST['vh360_agora_app_certificate']));
            update_option('vh360_agora_require_tokens', isset($_POST['vh360_agora_require_tokens']) ? 1 : 0);
            
            // Update interactive livestream settings
            update_option('videohub360_force_login_everyone_host', isset($_POST['videohub360_force_login_everyone_host']) ? 1 : 0);
            
            // Update login modal settings
            update_option('videohub360_login_modal_type', sanitize_text_field($_POST['videohub360_login_modal_type'] ?? 'default'));
            update_option('videohub360_login_modal_shortcode', sanitize_text_field($_POST['videohub360_login_modal_shortcode'] ?? ''));
            update_option('videohub360_login_modal_redirect_url', esc_url_raw($_POST['videohub360_login_modal_redirect_url'] ?? ''));
            update_option('videohub360_login_modal_js_function', sanitize_text_field($_POST['videohub360_login_modal_js_function'] ?? ''));
            
            // Update email settings
            update_option('videohub360_email_notifications', isset($_POST['videohub360_email_notifications']) ? 1 : 0);
            update_option('videohub360_email_admin', sanitize_email($_POST['videohub360_email_admin'] ?? ''));
            update_option('videohub360_email_from_name', sanitize_text_field($_POST['videohub360_email_from_name'] ?? ''));
            update_option('videohub360_email_from_email', sanitize_email($_POST['videohub360_email_from_email'] ?? ''));
            
            // Update video quality settings
            $default_quality = VideoHub360_Video_Quality::validate_quality($_POST['videohub360_default_quality'] ?? 'high');
            if ($default_quality) {
                update_option('videohub360_default_quality', $default_quality);
            }
            
            $default_mirror = VideoHub360_Video_Quality::validate_mirror($_POST['videohub360_default_mirror'] ?? 'disabled');
            if ($default_mirror !== false) {
                update_option('videohub360_default_mirror', $default_mirror);
            }
            
            update_option('videohub360_allow_quality_switching', isset($_POST['videohub360_allow_quality_switching']) ? 1 : 0);
            update_option('videohub360_allow_mirror_control', isset($_POST['videohub360_allow_mirror_control']) ? 1 : 0);
            update_option('videohub360_enable_4k_streaming', isset($_POST['videohub360_enable_4k_streaming']) ? 1 : 0);
            update_option('videohub360_show_quality_badge', isset($_POST['videohub360_show_quality_badge']) ? 1 : 0);
            
            // Update livestream offline/ended message settings
            update_option('vh360_default_stream_ended_html', wp_kses_post($_POST['vh360_default_stream_ended_html'] ?? ''));
            update_option('vh360_default_live_room_offline_html', wp_kses_post($_POST['vh360_default_live_room_offline_html'] ?? ''));
            update_option('vh360_stream_ended_by_moderator_html', wp_kses_post($_POST['vh360_stream_ended_by_moderator_html'] ?? ''));
            update_option('vh360_stream_ended_needs_restart_html', wp_kses_post($_POST['vh360_stream_ended_needs_restart_html'] ?? ''));
            update_option('vh360_default_stream_ended_icon', sanitize_text_field($_POST['vh360_default_stream_ended_icon'] ?? '📴'));
            update_option('vh360_default_live_room_offline_icon', sanitize_text_field($_POST['vh360_default_live_room_offline_icon'] ?? '🔴'));
            
            // Course / Lesson Features
            update_option('videohub360_enable_course_features', isset($_POST['videohub360_enable_course_features']) ? 1 : 0);
            
            // Refresh permalink structure if needed
            $new_post_slug = get_option('videohub360_post_slug');
            $new_category_slug = get_option('videohub360_category_slug');
            $new_location_slug = get_option('videohub360_location_slug');
            $new_series_slug = get_option('videohub360_series_slug');
            
            if ($old_post_slug !== $new_post_slug || $old_category_slug !== $new_category_slug || 
                $old_location_slug !== $new_location_slug || $old_series_slug !== $new_series_slug) {
                flush_rewrite_rules();
            }
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        // Get current option values
        $global_ad_url = get_option('videohub360_global_ad_url', '');
        $global_midroll_ad_url = get_option('videohub360_global_midroll_ad_url', '');
        $global_midroll_timing = get_option('videohub360_global_midroll_timing', '');
        $global_postroll_ad_url = get_option('videohub360_global_postroll_ad_url', '');
        $global_postroll_enabled = get_option('videohub360_global_postroll_enabled', 0);
        
        // Ad click-through settings
        $global_ad_click_url = get_option('vh360_global_ad_click_url', '');
        $ad_click_tracking_enabled = get_option('vh360_ad_click_tracking_enabled', 0);
        $ad_click_new_tab = get_option('vh360_ad_click_new_tab', 1);
        
        $post_slug = get_option('videohub360_post_slug', 'videohub360');
        $category_slug = get_option('videohub360_category_slug', 'videohub360-category'); 
        $location_slug = get_option('videohub360_location_slug', 'videohub360-location');
        $series_slug = get_option('videohub360_series_slug', 'videohub360-series');
        $enable_course_features = get_option('videohub360_enable_course_features', 0);
        
        $show_category_filter = get_option('videohub360_show_category_filter', 1);
        $show_series_filter = get_option('videohub360_show_series_filter', 1);
        $show_location_filter = get_option('videohub360_show_location_filter', 1);
        $category_label = get_option('videohub360_category_label', 'Category');
        $series_label = get_option('videohub360_series_label', 'Series');
        $location_label = get_option('videohub360_location_label', 'Location');
        
        $show_archive_header = get_option('videohub360_show_archive_header', 1);
        $archive_title       = get_option('videohub360_archive_title', 'Archive');
    
        
        $chat_enabled = get_option('videohub360_chat_enabled', 1);
        $chat_placement = get_option('videohub360_chat_placement', 'inline');
        $chat_cleanup_days = get_option('videohub360_chat_cleanup_days', 30);
        $chat_rate_limit = get_option('videohub360_chat_rate_limit', 5);
        $chat_message_limit = get_option('videohub360_chat_message_limit', 500);
        
        // Video quality settings
        $default_quality = get_option('videohub360_default_quality', 'high');
        $default_mirror = get_option('videohub360_default_mirror', 'disabled');
        $allow_quality_switching = get_option('videohub360_allow_quality_switching', 1);
        $allow_mirror_control = get_option('videohub360_allow_mirror_control', 1);
        $enable_4k_streaming = get_option('videohub360_enable_4k_streaming', 0);
        $show_quality_badge = get_option('videohub360_show_quality_badge', 1);
        
        // Livestream offline/ended message settings
        $default_stream_ended_html = get_option('vh360_default_stream_ended_html', '');
        $default_live_room_offline_html = get_option('vh360_default_live_room_offline_html', '');
        $stream_ended_by_moderator_html = get_option('vh360_stream_ended_by_moderator_html', '');
        $stream_ended_needs_restart_html = get_option('vh360_stream_ended_needs_restart_html', '');
        $default_stream_ended_icon = get_option('vh360_default_stream_ended_icon', '📴');
        $default_live_room_offline_icon = get_option('vh360_default_live_room_offline_icon', '🔴');
        
        ?>
        <div class="wrap">
            <h1>VideoHub360 Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('videohub360_settings', 'videohub360_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Global Ad Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Global Pre-roll Ad URL</th>
                        <td>
                            <input type="url" name="videohub360_global_ad_url" value="<?php echo esc_attr($global_ad_url); ?>" style="width: 400px;" />
                            <p class="description">This will be used for all videos unless overridden individually</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Global Mid-roll Ad URL</th>
                        <td>
                            <input type="url" name="videohub360_global_midroll_ad_url" value="<?php echo esc_attr($global_midroll_ad_url); ?>" style="width: 400px;" />
                            <p class="description">Global mid-roll ad URL for all videos</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Global Mid-roll Timing</th>
                        <td>
                            <input type="text" name="videohub360_global_midroll_timing" value="<?php echo esc_attr($global_midroll_timing); ?>" style="width: 200px;" />
                            <p class="description">Comma-separated seconds (e.g., "30,60,120")</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Global Post-roll Ad URL</th>
                        <td>
                            <input type="url" name="videohub360_global_postroll_ad_url" value="<?php echo esc_attr($global_postroll_ad_url); ?>" style="width: 400px;" />
                            <p class="description">Global post-roll ad URL for all videos</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Global Post-roll Enabled</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_global_postroll_enabled" value="1" <?php checked($global_postroll_enabled, 1); ?> />
                                Enable post-roll ads globally
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Ad Click-Through Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Global Ad Click-Through URL</th>
                        <td>
                            <input type="url" name="vh360_global_ad_click_url" value="<?php echo esc_attr($global_ad_click_url); ?>" style="width: 400px;" placeholder="https://videohub360.com/advertiser-page" />
                            <p class="description">Default click-through URL for all video ads. This will be used unless overridden on individual videos. Leave blank to disable ad clicks globally.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Click Tracking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_ad_click_tracking_enabled" value="1" <?php checked($ad_click_tracking_enabled, 1); ?> />
                                Track ad clicks for analytics
                            </label>
                            <p class="description">When enabled, ad clicks will be logged for reporting purposes.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Open Links in New Tab</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_ad_click_new_tab" value="1" <?php checked($ad_click_new_tab, 1); ?> />
                                Open ad click-through links in a new browser tab
                            </label>
                            <p class="description">Recommended: Keep users on your site while opening advertiser pages in a new tab.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">URL Slug Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Video Post Slug</th>
                        <td>
                            <input type="text" name="videohub360_post_slug" value="<?php echo esc_attr($post_slug); ?>" style="width: 200px;" />
                            <p class="description">URL slug for individual videos</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Category Slug</th>
                        <td>
                            <input type="text" name="videohub360_category_slug" value="<?php echo esc_attr($category_slug); ?>" style="width: 200px;" />
                            <p class="description">URL slug for video categories</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Location Slug</th>
                        <td>
                            <input type="text" name="videohub360_location_slug" value="<?php echo esc_attr($location_slug); ?>" style="width: 200px;" />
                            <p class="description">URL slug for video locations</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Series Slug</th>
                        <td>
                            <input type="text" name="videohub360_series_slug" value="<?php echo esc_attr($series_slug); ?>" style="width: 200px;" />
                            <p class="description">URL slug for video series</p>
                        </td>
                    </tr>
                    <tr>
                        
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Archive Page Header</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Show Header Banner</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_show_archive_header" value="1" <?php checked($show_archive_header, 1); ?> />
                                Enable the full-width banner header on the archive page
                            </label>
                            <p class="description">Disable this if your theme constrains content width or has no header space.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Header Title</th>
                        <td>
                            <input type="text" name="videohub360_archive_title" value="<?php echo esc_attr($archive_title); ?>" style="width:300px;" />
                            <p class="description">Defaults to “Archive”.</p>
                        </td>
                    </tr>
    <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Archive Page Filters</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Category Filter</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_show_category_filter" value="1" <?php checked($show_category_filter, 1); ?> />
                                Show category filter
                            </label>
                            <br><br>
                            <label for="videohub360_category_label">Filter Label:</label>
                            <input type="text" name="videohub360_category_label" id="videohub360_category_label" value="<?php echo esc_attr($category_label); ?>" style="width: 200px;" />
                            <p class="description">Label displayed for the category filter dropdown</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Series Filter</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_show_series_filter" value="1" <?php checked($show_series_filter, 1); ?> />
                                Show series filter
                            </label>
                            <br><br>
                            <label for="videohub360_series_label">Filter Label:</label>
                            <input type="text" name="videohub360_series_label" id="videohub360_series_label" value="<?php echo esc_attr($series_label); ?>" style="width: 200px;" />
                            <p class="description">Label displayed for the series filter dropdown</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Location Filter</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_show_location_filter" value="1" <?php checked($show_location_filter, 1); ?> />
                                Show location filter
                            </label>
                            <br><br>
                            <label for="videohub360_location_label">Filter Label:</label>
                            <input type="text" name="videohub360_location_label" id="videohub360_location_label" value="<?php echo esc_attr($location_label); ?>" style="width: 200px;" />
                            <p class="description">Label displayed for the location filter dropdown</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 20px 0 10px 0;">Agora.io Settings</h3>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 0.95em;">
                                <strong>🚀 Scenario 2: Multi-site, per-site credentials</strong><br>
                                Configure global Agora credentials once for all livestreams on this site. Each video uses its own channel name for secure, isolated streaming.
                            </p>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">Agora App ID</th>
                        <td>
                            <input type="text" name="vh360_agora_app_id" value="<?php echo esc_attr(get_option('vh360_agora_app_id', '')); ?>" style="width: 400px;" placeholder="Enter your Agora App ID" />
                            <p class="description">
                                <strong>Global App ID for all livestreams on this site.</strong><br>
                                Get this from your <a href="https://console.agora.io/" target="_blank">Agora Console</a> → Project Settings → App ID.<br>
                                This will be used for all Agora livestreams instead of per-video App IDs.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Agora App Certificate</th>
                        <td>
                            <input type="password" name="vh360_agora_app_certificate" value="<?php echo esc_attr(get_option('vh360_agora_app_certificate', '')); ?>" style="width: 400px;" placeholder="Enter your Agora App Certificate" />
                            <p class="description">
                                <strong>Required for secure token generation.</strong><br>
                                Get this from your <a href="https://console.agora.io/" target="_blank">Agora Console</a> → Project Settings → App Certificate.<br>
                                Keep this secure and don't share it publicly.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require Agora Tokens</th>
                        <td>
                            <label>
                                <input type="checkbox" name="vh360_agora_require_tokens" value="1" <?php checked(get_option('vh360_agora_require_tokens', 1), 1); ?> />
                                Require secure Agora tokens before users can join livestreams
                            </label>
                            <p class="description">
                                <strong>This should remain enabled on production sites.</strong><br>
                                Disabling this is for local testing only. When disabled, users can join Agora channels without a server-issued token.
                            </p>
                            <?php if (!get_option('vh360_agora_require_tokens', 1)) : ?>
                                <p style="color: #c00; font-weight: bold; margin-top: 8px;">
                                    ⚠️ Warning: Tokenless Agora access is not recommended for production. Enable this option before going live.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Chat Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Enable Chat</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_chat_enabled" value="1" <?php checked($chat_enabled, 1); ?> />
                                Enable chat functionality
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chat Placement</th>
                        <td>
                            <select name="videohub360_chat_placement" style="width: 300px;">
                                <option value="inline"  <?php selected($chat_placement, 'inline'); ?>>Inline (replaces comments)</option>
                                <option value="popup"   <?php selected($chat_placement, 'popup'); ?>>Popup (button opens overlay)</option>
                                <option value="sidebar" <?php selected($chat_placement, 'sidebar'); ?>>Sidebar (YouTube-style)</option>
                                <option value="off"     <?php selected($chat_placement, 'off'); ?>>Off (hide chat completely)</option>
                            </select>
                            <p class="description">
                                <strong>Inline:</strong> Chat renders in the page layout where comments would be.<br>
                                <strong>Popup:</strong> Chat renders as an overlay popup controlled by an "Open Chat" button near the title/meta area.<br>
                                <strong>Sidebar:</strong> Chat appears in the right sidebar above the latest videos on desktop, and below the video on mobile.<br>
                                <strong>Off:</strong> Hide chat completely (same as disabling chat above).
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chat Cleanup Days</th>
                        <td>
                            <input type="number" name="videohub360_chat_cleanup_days" value="<?php echo esc_attr($chat_cleanup_days); ?>" min="1" style="width: 100px;" />
                            <p class="description">Delete chat messages older than this many days</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Rate Limit (messages/minute)</th>
                        <td>
                            <input type="number" name="videohub360_chat_rate_limit" value="<?php echo esc_attr($chat_rate_limit); ?>" min="1" style="width: 100px;" />
                            <p class="description">Maximum messages per user per minute</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Message Character Limit</th>
                        <td>
                            <input type="number" name="videohub360_chat_message_limit" value="<?php echo esc_attr($chat_message_limit); ?>" min="10" style="width: 100px;" />
                            <p class="description">Maximum characters per chat message</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Interactive Livestream Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Login Requirement for Everyone-Is-Host Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_force_login_everyone_host" value="1" <?php checked(get_option('videohub360_force_login_everyone_host', 1), 1); ?> />
                                Require login for viewers to request host privileges
                            </label>
                            <p class="description">When enabled, only logged-in users can request to become hosts in interactive livestreams</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Login Modal Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Login Modal Type</th>
                        <td>
                            <?php $login_modal_type = get_option('videohub360_login_modal_type', 'default'); ?>
                            <select name="videohub360_login_modal_type" style="width: 200px;">
                                <option value="default" <?php selected($login_modal_type, 'default'); ?>>Default WordPress Login</option>
                                <option value="shortcode" <?php selected($login_modal_type, 'shortcode'); ?>>Custom Shortcode</option>
                                <option value="redirect" <?php selected($login_modal_type, 'redirect'); ?>>Redirect to URL</option>
                                <option value="javascript" <?php selected($login_modal_type, 'javascript'); ?>>Custom JavaScript Function</option>
                                <option value="builtin" <?php selected($login_modal_type, 'builtin'); ?>>Built-in Login Form (AJAX)</option>
                            </select>
                            <p class="description">Choose how login prompts should be handled</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Custom Shortcode</th>
                        <td>
                            <input type="text" name="videohub360_login_modal_shortcode" value="<?php echo esc_attr(get_option('videohub360_login_modal_shortcode', '')); ?>" style="width: 400px;" placeholder="[your_login_form]" />
                            <p class="description">Used when "Custom Shortcode" is selected</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URL</th>
                        <td>
                            <input type="url" name="videohub360_login_modal_redirect_url" value="<?php echo esc_attr(get_option('videohub360_login_modal_redirect_url', '')); ?>" style="width: 400px;" placeholder="https://yoursite.com/login" />
                            <p class="description">Used when "Redirect to URL" is selected</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">JavaScript Function</th>
                        <td>
                            <input type="text" name="videohub360_login_modal_js_function" value="<?php echo esc_attr(get_option('videohub360_login_modal_js_function', '')); ?>" style="width: 400px;" placeholder="myCustomLoginFunction" />
                            <p class="description">Function name to call when "Custom JavaScript Function" is selected</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">🎬 Video Quality & Streaming Settings</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Default Video Quality</th>
                        <td>
                            <select name="videohub360_default_quality" style="width: 300px;">
                                <?php 
                                $quality_options = VideoHub360_Video_Quality::get_quality_options();
                                foreach ($quality_options as $key => $label) {
                                    echo '<option value="' . esc_attr($key) . '"' . selected($default_quality, $key, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Default video quality for all players. Users can change this if switching is enabled.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Mirror Setting</th>
                        <td>
                            <select name="videohub360_default_mirror" style="width: 300px;">
                                <?php 
                                $mirror_options = VideoHub360_Video_Quality::get_mirror_options();
                                foreach ($mirror_options as $key => $label) {
                                    echo '<option value="' . esc_attr($key) . '"' . selected($default_mirror, $key, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Default mirror/flip setting for video players.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Quality & Mirror Controls</th>
                        <td>
                            <label><input type="checkbox" name="videohub360_allow_quality_switching" value="1" <?php checked($allow_quality_switching, 1); ?> /> Allow users to change video quality</label><br>
                            <label><input type="checkbox" name="videohub360_allow_mirror_control" value="1" <?php checked($allow_mirror_control, 1); ?> /> Allow users to control mirror/flip settings</label><br>
                            <label><input type="checkbox" name="videohub360_show_quality_badge" value="1" <?php checked($show_quality_badge, 1); ?> /> Show quality badge on videos</label>
                            <p class="description">Enable user controls for video quality and mirror settings.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">4K Streaming</th>
                        <td>
                            <label><input type="checkbox" name="videohub360_enable_4k_streaming" value="1" <?php checked($enable_4k_streaming, 1); ?> /> Enable 4K Ultra HD streaming options</label>
                            <p class="description"><strong>Note:</strong> 4K streaming requires higher bandwidth and processing power. Only enable if your hosting can handle the increased load.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Livestream Offline / Ended Messages</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Default Stream Ended HTML</th>
                        <td>
                            <textarea name="vh360_default_stream_ended_html" rows="5" style="width: 100%; max-width: 600px; font-family: monospace;"><?php echo esc_textarea($default_stream_ended_html); ?></textarea>
                            <p class="description">HTML allowed. Leave empty to use icon + default text. Use classes like vh360-offline-message for consistent styling.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Stream Ended Icon</th>
                        <td>
                            <input type="text" name="vh360_default_stream_ended_icon" value="<?php echo esc_attr($default_stream_ended_icon); ?>" style="width: 100px;" />
                            <p class="description">Emoji or icon character. Used when HTML field is empty.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Live Room Offline HTML</th>
                        <td>
                            <textarea name="vh360_default_live_room_offline_html" rows="5" style="width: 100%; max-width: 600px; font-family: monospace;"><?php echo esc_textarea($default_live_room_offline_html); ?></textarea>
                            <p class="description">HTML allowed. Leave empty to use icon + default text.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Live Room Offline Icon</th>
                        <td>
                            <input type="text" name="vh360_default_live_room_offline_icon" value="<?php echo esc_attr($default_live_room_offline_icon); ?>" style="width: 100px;" />
                            <p class="description">Emoji or icon character. Used when HTML field is empty.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stream Ended by Moderator HTML</th>
                        <td>
                            <textarea name="vh360_stream_ended_by_moderator_html" rows="5" style="width: 100%; max-width: 600px; font-family: monospace;"><?php echo esc_textarea($stream_ended_by_moderator_html); ?></textarea>
                            <p class="description">HTML allowed. Shown when a moderator manually ends the stream.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Stream Ended / Host Needs Restart HTML</th>
                        <td>
                            <textarea name="vh360_stream_ended_needs_restart_html" rows="5" style="width: 100%; max-width: 600px; font-family: monospace;"><?php echo esc_textarea($stream_ended_needs_restart_html); ?></textarea>
                            <p class="description">HTML allowed. Shown when the host needs to restart the stream.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" colspan="2"><h3 style="margin: 20px 0 10px 0;">Course / Lesson Features</h3></th>
                    </tr>
                    <tr>
                        <th scope="row">Enable Course / Lesson Features</th>
                        <td>
                            <label>
                                <input type="checkbox" name="videohub360_enable_course_features" value="1" <?php checked($enable_course_features, 1); ?> />
                                Enable Course / Lesson Features
                            </label>
                            <p class="description">Adds course and lesson presentation fields using existing VideoHub360 videos and series. Videos can be presented as lessons, and series can be presented as courses or learning tracks.</p>
                        </td>
                    </tr>
                    
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Video meta box callback
     */
    public function video_meta_box_callback($post) {
        // Nonce field for security
        wp_nonce_field('videohub360_video_meta_box', 'videohub360_video_meta_box_nonce');

        // Regular video fields
        $video_url = get_post_meta($post->ID, 'video_url', true);
        $ad_video_url = get_post_meta($post->ID, 'ad_video_url', true);
        $views = get_post_meta($post->ID, '_videohub360_post_views_count', true);
        $custom_html = get_post_meta($post->ID, 'videohub360_custom_html', true);
        
        // New mid-roll and post-roll ad fields
        $midroll_ad_url = get_post_meta($post->ID, 'midroll_ad_video_url', true);
        $midroll_timing = get_post_meta($post->ID, 'midroll_ad_timing', true);
        $postroll_ad_url = get_post_meta($post->ID, 'postroll_ad_video_url', true);
        $postroll_enabled = get_post_meta($post->ID, 'postroll_ad_enabled', true);
        
        // Ad click-through URLs
        $ad_click_url = get_post_meta($post->ID, '_vh360_ad_click_url', true);
        $midroll_ad_click_url = get_post_meta($post->ID, '_vh360_midroll_ad_click_url', true);
        $postroll_ad_click_url = get_post_meta($post->ID, '_vh360_postroll_ad_click_url', true);

        // Livestream fields
        $livestream_fields = [
            'is_live' => get_post_meta($post->ID, '_vh360_is_live', true) ?: 'no',
            'type' => get_post_meta($post->ID, '_vh360_type', true) ?: 'embed',
            'embed_code' => get_post_meta($post->ID, '_vh360_embed_code', true),
            'stream_url' => get_post_meta($post->ID, '_vh360_stream_url', true),
            'api_url' => get_post_meta($post->ID, '_vh360_api_url', true),
            'poster' => get_post_meta($post->ID, '_vh360_poster', true),
            'viewer_count' => get_post_meta($post->ID, '_vh360_viewer_count', true) ?: 'no',
            'live_badge' => get_post_meta($post->ID, '_vh360_live_badge', true) ?: 'yes',
            'badge_text' => get_post_meta($post->ID, '_vh360_badge_text', true) ?: 'LIVE',
            'badge_color' => get_post_meta($post->ID, '_vh360_badge_color', true) ?: '#e53935',
            'offline_message' => get_post_meta($post->ID, '_vh360_offline_message', true),
            'live_start_time' => get_post_meta($post->ID, '_vh360_live_start_time', true),
            'stream_stopped' => get_post_meta($post->ID, '_vh360_stream_stopped', true) ?: 'no',
            'context' => get_post_meta($post->ID, '_vh360_context', true) ?: 'default',
            'chat_enabled' => get_post_meta($post->ID, '_vh360_chat_enabled', true),
            'chat_placement' => get_post_meta($post->ID, '_vh360_chat_placement', true),
            'agora_channel_name' => get_post_meta($post->ID, '_vh360_agora_channel_name', true),
            'agora_mode' => get_post_meta($post->ID, '_vh360_agora_mode', true) ?: 'interactive',
            'agora_everyone_is_host' => get_post_meta($post->ID, '_vh360_agora_everyone_is_host', true) ?: 'no',
            'host_passcode' => get_post_meta($post->ID, '_vh360_host_passcode', true),
        ];

        // Video quality and mirror settings for this post
        $post_quality = get_post_meta($post->ID, '_vh360_video_quality', true) ?: '';
        $post_mirror = get_post_meta($post->ID, '_vh360_video_mirror', true) ?: '';
        $post_quality_override = get_post_meta($post->ID, '_vh360_override_quality_settings', true) ?: 'no';

        ?>
        <style>
        .vh360-meta-section { 
            border: 1px solid #ddd; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            background: #f9f9f9;
        }
        .vh360-meta-section h3 { 
            margin-top: 0; 
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1em;
        }
        .vh360-field { 
            margin-bottom: 15px; 
        }
        .vh360-field label { 
            font-weight: 600; 
            display: block; 
            margin-bottom: 5px;
        }
        .vh360-field input, .vh360-field textarea, .vh360-field select { 
            width: 100%; 
            max-width: 100%;
        }
        .vh360-field input[type="number"] { 
            width: 120px; 
        }
        .vh360-field input[type="color"] { 
            width: 60px; 
        }
        .vh360-field input[type="checkbox"] { 
            width: auto; 
            margin-right: 8px;
        }
        .vh360-field small { 
            color: #666; 
            font-style: italic;
            display: block;
            margin-top: 5px;
        }
        .vh360-livestream-type-fields .vh360-field {
            display: none;
        }
        </style>

        <!-- Regular Video Settings Section -->
        <div class="vh360-meta-section">
            <h3>📹 Video Settings</h3>
            <div class="vh360-field">
                <label for="videohub360_video_url">Video MP4 URL:</label>
                <input type="url" id="videohub360_video_url" name="videohub360_video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://videohub360.com/video.mp4" />
                <small>URL to your MP4 video file for regular video playback.</small>
            </div>
            <div class="vh360-field">
                <label for="videohub360_ad_video_url">Ad (Pre-roll) MP4 URL:</label>
                <input type="url" id="videohub360_ad_video_url" name="videohub360_ad_video_url" value="<?php echo esc_attr($ad_video_url); ?>" placeholder="https://videohub360.com/ad.mp4" />
                <small>Leave blank if no ad is needed. If not set, the global ad will be used if available.</small>
            </div>
            <div class="vh360-field">
                <label for="videohub360_midroll_ad_url">Mid-Roll Ad MP4 URL:</label>
                <input type="url" id="videohub360_midroll_ad_url" name="videohub360_midroll_ad_url" value="<?php echo esc_attr($midroll_ad_url); ?>" placeholder="https://videohub360.com/midroll-ad.mp4" />
                <small>Leave blank to use global mid-roll ad or disable mid-roll ads for this video.</small>
            </div>
            <div class="vh360-field">
                <label for="videohub360_midroll_timing">Mid-Roll Ad Timing (seconds):</label>
                <input type="text" id="videohub360_midroll_timing" name="videohub360_midroll_timing" value="<?php echo esc_attr($midroll_timing); ?>" placeholder="30,60,120" />
                <small>Comma-separated list of seconds when mid-roll ads should play (e.g., "30,60,120" for ads at 30s, 1min, and 2min). Leave blank to use global settings.</small>
            </div>
            <div class="vh360-field">
                <label for="videohub360_postroll_ad_url">Post-Roll Ad MP4 URL:</label>
                <input type="url" id="videohub360_postroll_ad_url" name="videohub360_postroll_ad_url" value="<?php echo esc_attr($postroll_ad_url); ?>" placeholder="https://videohub360.com/postroll-ad.mp4" />
                <small>Leave blank to use global post-roll ad or disable post-roll ads for this video.</small>
            </div>
            <div class="vh360-field">
                <label>
                    <input type="checkbox" id="videohub360_postroll_enabled" name="videohub360_postroll_enabled" value="yes" <?php checked($postroll_enabled, 'yes'); ?> />
                    Enable Post-Roll Ad for this video
                </label>
                <small>When checked, a post-roll ad will play after the main video ends.</small>
            </div>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;" />
            <h4 style="margin: 10px 0; color: #0073aa;">💰 Ad Click-Through URLs</h4>
            
            <div class="vh360-field">
                <label for="vh360_ad_click_url">Pre-roll Ad Click URL:</label>
                <input type="url" id="vh360_ad_click_url" name="vh360_ad_click_url" value="<?php echo esc_attr($ad_click_url); ?>" placeholder="https://videohub360.com/advertiser-page" />
                <?php
                $global_click_url = get_option('vh360_global_ad_click_url', '');
                $effective_url = !empty($ad_click_url) ? $ad_click_url : $global_click_url;
                ?>
                <small>
                    <?php if (!empty($ad_click_url)): ?>
                        <strong>✓ Custom URL:</strong> This video will use its own click-through URL.
                    <?php elseif (!empty($global_click_url)): ?>
                        <strong>⚠ Using Global URL:</strong> <?php echo esc_html($global_click_url); ?>
                    <?php else: ?>
                        <strong>✗ No Click URL:</strong> Ad will not be clickable. Set a URL here or configure a global default.
                    <?php endif; ?>
                </small>
            </div>
            
            <div class="vh360-field">
                <label for="vh360_midroll_ad_click_url">Mid-roll Ad Click URL (Optional):</label>
                <input type="url" id="vh360_midroll_ad_click_url" name="vh360_midroll_ad_click_url" value="<?php echo esc_attr($midroll_ad_click_url); ?>" placeholder="https://videohub360.com/advertiser-page" />
                <small>Leave blank to use Pre-roll URL or global default. Use this only if mid-roll needs a different destination.</small>
            </div>
            
            <div class="vh360-field">
                <label for="vh360_postroll_ad_click_url">Post-roll Ad Click URL (Optional):</label>
                <input type="url" id="vh360_postroll_ad_click_url" name="vh360_postroll_ad_click_url" value="<?php echo esc_attr($postroll_ad_click_url); ?>" placeholder="https://videohub360.com/advertiser-page" />
                <small>Leave blank to use Pre-roll URL or global default. Use this only if post-roll needs a different destination.</small>
            </div>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;" />
            
            <div class="vh360-field">
                <label for="videohub360_manual_views">Manual View Count:</label>
                <input type="number" id="videohub360_manual_views" name="videohub360_manual_views" value="<?php echo esc_attr($views ? $views : 0); ?>" min="0" />
                <small>Set this to manually override the views for this video.</small>
            </div>
            <div class="vh360-field">
                <label for="videohub360_custom_html">Custom HTML Embed (overrides video player):</label>
                <textarea id="videohub360_custom_html" name="videohub360_custom_html" rows="5"><?php echo esc_textarea($custom_html); ?></textarea>
                <small>Paste any embed code here (YouTube, Vimeo, or other HTML). If filled, this will be displayed instead of the MP4 player.</small>
            </div>
        </div>

        <!-- Video Quality Settings Section -->
        <div class="vh360-meta-section">
            <h3>🎬 Video Quality & Mirror Settings</h3>
            <div class="vh360-field">
                <label>
                    <input type="checkbox" id="vh360_override_quality_settings" name="vh360_override_quality_settings" value="yes" <?php checked($post_quality_override, 'yes'); ?> />
                    Override global quality settings for this video
                </label>
                <small>When checked, you can set specific quality and mirror settings for this individual video.</small>
            </div>
            <div class="vh360-quality-override-fields" style="<?php echo $post_quality_override === 'yes' ? '' : 'display: none;'; ?>">
                <div class="vh360-field">
                    <label for="vh360_video_quality">Video Quality:</label>
                    <select name="vh360_video_quality" id="vh360_video_quality">
                        <option value="">Use Global Default</option>
                        <?php 
                        $quality_options = VideoHub360_Video_Quality::get_quality_options();
                        foreach ($quality_options as $key => $label) {
                            echo '<option value="' . esc_attr($key) . '"' . selected($post_quality, $key, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <small>Set the video quality for this specific video. Leave as "Use Global Default" to inherit from global settings.</small>
                </div>
                <div class="vh360-field">
                    <label for="vh360_video_mirror">Mirror Setting:</label>
                    <select name="vh360_video_mirror" id="vh360_video_mirror">
                        <option value="">Use Global Default</option>
                        <?php 
                        $mirror_options = VideoHub360_Video_Quality::get_mirror_options();
                        foreach ($mirror_options as $key => $label) {
                            echo '<option value="' . esc_attr($key) . '"' . selected($post_mirror, $key, false) . '>' . esc_html($label) . '</option>';
                        }
                        ?>
                    </select>
                    <small>Set the mirror/flip setting for this specific video. Leave as "Use Global Default" to inherit from global settings.</small>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const overrideCheckbox = document.getElementById('vh360_override_quality_settings');
            const overrideFields = document.querySelector('.vh360-quality-override-fields');
            
            if (overrideCheckbox && overrideFields) {
                overrideCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        overrideFields.style.display = '';
                    } else {
                        overrideFields.style.display = 'none';
                    }
                });
            }
            
            // Ad click URL validation
            const adClickUrlFields = [
                document.getElementById('vh360_ad_click_url'),
                document.getElementById('vh360_midroll_ad_click_url'),
                document.getElementById('vh360_postroll_ad_click_url')
            ];
            
            function validateURL(url) {
                if (!url || url.trim() === '') return true; // Empty is valid
                
                // Block dangerous schemes
                const lowerUrl = url.toLowerCase();
                if (lowerUrl.startsWith('javascript:') || lowerUrl.startsWith('data:') || lowerUrl.startsWith('vbscript:')) {
                    return false;
                }
                
                try {
                    const urlObj = new URL(url);
                    // Only allow http and https protocols
                    if (urlObj.protocol !== 'http:' && urlObj.protocol !== 'https:') {
                        return false;
                    }
                    // Basic hostname validation
                    if (!urlObj.hostname || urlObj.hostname.length < 1) {
                        return false;
                    }
                    return true;
                } catch (e) {
                    return false;
                }
            }
            
            function showValidationFeedback(input, isValid) {
                // Remove existing feedback
                const existingFeedback = input.parentElement.querySelector('.vh360-url-validation');
                if (existingFeedback) {
                    existingFeedback.remove();
                }
                
                if (!input.value || input.value.trim() === '') {
                    input.style.borderColor = '';
                    return;
                }
                
                // Add visual feedback
                const feedback = document.createElement('div');
                feedback.className = 'vh360-url-validation';
                feedback.style.cssText = 'margin-top: 5px; font-size: 12px;';
                
                if (isValid) {
                    input.style.borderColor = '#4caf50';
                    feedback.style.color = '#4caf50';
                    feedback.textContent = '✓ Valid URL';
                } else {
                    input.style.borderColor = '#f44336';
                    feedback.style.color = '#f44336';
                    feedback.textContent = '✗ Invalid URL format. Must start with http:// or https://';
                }
                
                // Insert feedback after the input's next sibling (the small tag)
                const nextElement = input.nextElementSibling;
                if (nextElement && nextElement.tagName === 'SMALL') {
                    nextElement.parentNode.insertBefore(feedback, nextElement.nextSibling);
                } else {
                    input.parentNode.insertBefore(feedback, input.nextSibling);
                }
            }
            
            adClickUrlFields.forEach(function(field) {
                if (!field) return;
                
                field.addEventListener('blur', function() {
                    const isValid = validateURL(this.value);
                    showValidationFeedback(this, isValid);
                });
                
                field.addEventListener('input', function() {
                    // Clear validation on input
                    const existingFeedback = this.parentElement.querySelector('.vh360-url-validation');
                    if (existingFeedback) {
                        existingFeedback.remove();
                    }
                    this.style.borderColor = '';
                });
            });
        });
        </script>

        <!-- Livestream Settings Section -->
        <div class="vh360-meta-section">
            <h3>🔴 Livestream Settings</h3>
            <p style="background: #e7f3ff; padding: 12px; border-radius: 4px; border-left: 4px solid #2196F3; margin-bottom: 15px; font-size: 0.95em;">
                <strong>💡 Quick Setup Guide:</strong><br>
                1. Set "Currently Live Status" to "Yes" to enable livestream mode<br>
                2. Choose your "Stream Source Type" (Agora.io recommended for interactive streaming)<br>
                3. Configure source-specific settings below<br>
                4. Save and view your post to see the live player
            </p>
            <div class="vh360-field">
                <label for="vh360_context">Usage Context:</label>
                <select name="vh360_context" id="vh360_context">
                    <option value="default" <?php selected($livestream_fields['context'], 'default'); ?>>Default video / public template</option>
                    <option value="live_room" <?php selected($livestream_fields['context'], 'live_room'); ?>>Community Live Room (use community template)</option>
                </select>
                <small>Choose where this livestream should appear. Community Live Room uses the private community template in your Videohub360 theme.</small>
            </div>

            <div class="vh360-field">
                <label for="vh360_is_live">Currently Live Status:</label>
                <select name="vh360_is_live" id="vh360_is_live">
                    <option value="no" <?php selected($livestream_fields['is_live'], 'no'); ?>>No - Regular Video Mode</option>
                    <option value="yes" <?php selected($livestream_fields['is_live'], 'yes'); ?>>Yes - Livestream Mode</option>
                </select>
                <small>When set to "Yes", this post will display livestream functionality instead of regular video player.</small>
            </div>
            <div class="vh360-field">
                <label for="vh360_live_start_time">Live Start Time:</label>
                <input type="datetime-local" name="vh360_live_start_time" id="vh360_live_start_time" value="<?php echo esc_attr($livestream_fields['live_start_time']); ?>">
                <small>Set the date and time when the livestream started (for "Started streaming X minutes ago" display).</small>
            </div>
            <div class="vh360-field">
                <label for="vh360_offline_message">Offline Message or Placeholder:</label>
                <textarea name="vh360_offline_message" id="vh360_offline_message" rows="3"><?php echo esc_textarea($livestream_fields['offline_message']); ?></textarea>
                <small>Shown when livestream status is "No" or when livestream is offline. You can use text, HTML, or an image tag.</small>
            </div>
            <div class="vh360-field">
                <label for="vh360_type">Stream Source Type:</label>
                <select name="vh360_type" id="vh360_type">
                    <option value="embed" <?php selected($livestream_fields['type'], 'embed'); ?>>Embed (YouTube Live, Twitch, etc.)</option>
                    <option value="selfhosted" <?php selected($livestream_fields['type'], 'selfhosted'); ?>>Self-Hosted HLS/DASH</option>
                    <option value="api" <?php selected($livestream_fields['type'], 'api'); ?>>Streaming API Platform</option>
                    <option value="agora" <?php selected($livestream_fields['type'], 'agora'); ?>>Agora.io WebRTC (Recommended for Interactive)</option>
                </select>
                <small><strong>Agora.io</strong> offers the best experience for interactive livestreams with audience participation, built-in chat, and real-time engagement.</small>
            </div>
            <div class="vh360-livestream-type-fields">
                <div class="vh360-field vh360-embed">
                    <label for="vh360_embed_code">Embed Code:</label>
                    <textarea name="vh360_embed_code" id="vh360_embed_code" rows="3"><?php echo esc_textarea($livestream_fields['embed_code']); ?></textarea>
                    <small>Paste your iframe/embed HTML here (YouTube Live, Twitch, etc.).</small>
                </div>
                <div class="vh360-field vh360-selfhosted">
                    <label for="vh360_stream_url">Self-Hosted Stream URL (HLS/DASH):</label>
                    <input type="text" name="vh360_stream_url" id="vh360_stream_url" value="<?php echo esc_attr($livestream_fields['stream_url']); ?>">
                    <small>HLS (.m3u8) or DASH (.mpd) stream URL.</small>
                </div>
                <div class="vh360-field vh360-api">
                    <label for="vh360_api_url">API Playback URL (HLS/DASH):</label>
                    <input type="text" name="vh360_api_url" id="vh360_api_url" value="<?php echo esc_attr($livestream_fields['api_url']); ?>">
                    <small>API-provided playback URL for platforms like Mux, AWS IVS.</small>
                </div>
                <div class="vh360-field vh360-agora">
                    <label for="vh360_agora_mode">Streaming Mode:</label>
                    <select name="vh360_agora_mode" id="vh360_agora_mode">
                        <option value="interactive" <?php selected($livestream_fields['agora_mode'], 'interactive'); ?>>Interactive Mode</option>
                        <option value="broadcast" <?php selected($livestream_fields['agora_mode'], 'broadcast'); ?>>Broadcast Mode</option>
                    </select>
                    <small><strong>Interactive Mode:</strong> Allows audience members to request to join as hosts and interact in real-time.</small>
                </div>
                <div class="vh360-field vh360-agora">
                    <label for="vh360_agora_channel_name">Channel Name (Required):</label>
                    <input type="text" name="vh360_agora_channel_name" id="vh360_agora_channel_name" value="<?php echo esc_attr($livestream_fields['agora_channel_name']); ?>" required>
                    <small>The Agora channel name for this specific livestream. Use alphanumeric characters only.</small>
                </div>
                <div class="vh360-field vh360-agora">
                    <label>
                        <input type="checkbox" name="vh360_agora_everyone_is_host" value="yes" <?php checked($livestream_fields['agora_everyone_is_host'], 'yes'); ?> />
                        Allow Everyone to be Host
                    </label>
                    <small>When enabled, all viewers can directly join as hosts. <em>Cannot be used with passcode requirement.</em></small>
                </div>
                <div class="vh360-field vh360-agora vh360-passcode-section" style="border-left: 3px solid #0073aa; padding-left: 15px; margin-left: 10px;">
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">Access Control</h4>
                    <label>
                        <input type="checkbox" name="vh360_require_passcode" id="vh360_require_passcode" value="yes" <?php checked(!empty($livestream_fields['host_passcode']), true); ?> />
                        Require Passcode To Join
                    </label>
                    <small>When enabled, viewers must enter a passcode to join as presenters. <em>Cannot be used with "Allow Everyone to be Host".</em></small>
                    <div id="vh360_passcode_field" style="margin-top: 10px; <?php echo empty($livestream_fields['host_passcode']) ? 'display: none;' : ''; ?>">
                        <?php if (!empty($livestream_fields['host_passcode'])) : ?>
                            <p style="margin-bottom: 6px;"><em><?php esc_html_e('A host passcode is currently set.', 'videohub360'); ?></em></p>
                        <?php endif; ?>
                        <label for="vh360_host_passcode">
                            <?php echo !empty($livestream_fields['host_passcode']) ? esc_html__('Set New Host Passcode:', 'videohub360') : esc_html__('Host Passcode:', 'videohub360'); ?>
                        </label>
                        <input type="password" name="vh360_host_passcode" id="vh360_host_passcode" value="" placeholder="<?php echo !empty($livestream_fields['host_passcode']) ? esc_attr__('Leave blank to keep existing passcode', 'videohub360') : esc_attr__('Enter passcode', 'videohub360'); ?>" style="font-family: monospace; font-weight: bold;" autocomplete="new-password">
                        <small><?php esc_html_e('Viewers will need to enter this passcode to join as presenters.', 'videohub360'); ?></small>
                    </div>
                </div>
            </div>
            <div class="vh360-field">
                <label>
                    <input type="checkbox" name="vh360_viewer_count" value="yes" <?php checked($livestream_fields['viewer_count'], 'yes'); ?> />
                    Show Viewer Count
                </label>
            </div>
            <div class="vh360-field">
                <label>
                    <input type="checkbox" name="vh360_chat_enabled" id="vh360_chat_enabled" value="yes" <?php checked($livestream_fields['chat_enabled'], 'yes'); ?> />
                    Enable live chat for this video
                </label>
                <small>When checked, live chat will be available for this livestream regardless of global settings. When unchecked, the global live chat setting will be used.</small>
            </div>
            
            <div class="vh360-field">
                <label for="vh360_chat_placement">Chat Placement Override:</label>
                <select name="vh360_chat_placement" id="vh360_chat_placement">
                    <option value="" <?php selected($livestream_fields['chat_placement'], ''); ?>>Use Global Default</option>
                    <option value="inline"  <?php selected($livestream_fields['chat_placement'], 'inline'); ?>>Inline (replaces comments)</option>
                    <option value="popup"   <?php selected($livestream_fields['chat_placement'], 'popup'); ?>>Popup (button opens overlay)</option>
                    <option value="sidebar" <?php selected($livestream_fields['chat_placement'], 'sidebar'); ?>>Sidebar (YouTube-style)</option>
                    <option value="off"     <?php selected($livestream_fields['chat_placement'], 'off'); ?>>Off (hide chat)</option>
                </select>
                <small>Override the global chat placement setting for this specific video. Leave as "Use Global Default" to inherit from global settings.</small>
            </div>
            
            <!-- Stream Control Section -->
            <div class="vh360-field">
                <label>Stream Control:</label>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <button type="button" id="vh360-stop-stream-btn" class="button button-secondary" onclick="videohub360StopStream(<?php echo absint($post->ID); ?>)">
                        🛑 Stop Stream
                    </button>
                    <button type="button" id="vh360-restart-stream-btn" class="button button-secondary" onclick="videohub360RestartStream(<?php echo absint($post->ID); ?>)">
                        ▶️ Restart Stream
                    </button>
                </div>
                <small>Stop Stream: Removes LIVE badges and "watching now" messages while keeping stream settings. Restart Stream: Re-enables all live indicators.</small>
                <input type="hidden" name="vh360_stream_stopped" id="vh360_stream_stopped" value="<?php echo esc_attr($livestream_fields['stream_stopped']); ?>">
            </div>
            
            <div class="vh360-field">
                <label>
                    <input type="checkbox" name="vh360_live_badge" value="yes" <?php checked($livestream_fields['live_badge'], 'yes'); ?> />
                    Show Live Badge
                </label>
            </div>
            <div class="vh360-field">
                <label for="vh360_badge_text">Live Badge Text:</label>
                <input type="text" name="vh360_badge_text" id="vh360_badge_text" value="<?php echo esc_attr($livestream_fields['badge_text']); ?>" placeholder="LIVE">
            </div>
            <div class="vh360-field">
                <label for="vh360_badge_color">Live Badge Color:</label>
                <input type="color" name="vh360_badge_color" id="vh360_badge_color" value="<?php echo esc_attr($livestream_fields['badge_color']); ?>">
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show/hide livestream type-specific fields
            function toggleLivestreamFields() {
                var selectedType = $('#vh360_type').val();
                $('.vh360-livestream-type-fields .vh360-field').hide();
                $('.vh360-livestream-type-fields .vh360-' + selectedType).show();
            }

            $('#vh360_type').on('change', toggleLivestreamFields);
            toggleLivestreamFields(); // Initialize on page load
        });
        
        // Stream control functions
        function videohub360StopStream(postId) {
            if (!confirm('Are you sure you want to stop this stream? This will remove LIVE badges and pause all live indicators.')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_admin_stop_stream',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('vh360_admin_stream_control'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Stream stopped successfully. LIVE badges have been hidden.');
                        document.getElementById('vh360_stream_stopped').value = 'yes';
                    } else {
                        alert('Failed to stop stream: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Failed to communicate with server. Please try again.');
                }
            });
        }
        
        function videohub360RestartStream(postId) {
            if (!confirm('Are you sure you want to restart this stream? This will re-enable LIVE badges and all live indicators.')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vh360_admin_restart_stream',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('vh360_admin_stream_control'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Stream restarted successfully. LIVE badges have been re-enabled.');
                        document.getElementById('vh360_stream_stopped').value = 'no';
                    } else {
                        alert('Failed to restart stream: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Failed to communicate with server. Please try again.');
                }
            });
        }
        
        // Handle passcode checkbox toggle and mutual exclusivity
        document.addEventListener('DOMContentLoaded', function() {
            const modeSelect = document.getElementById('vh360_agora_mode');
            const passcodeSection = document.querySelector('.vh360-passcode-section');
            const requirePasscodeCheckbox = document.getElementById('vh360_require_passcode');
            const passcodeField = document.getElementById('vh360_passcode_field');
            const passcodeInput = document.getElementById('vh360_host_passcode');
            const allowEveryoneCheckbox = document.querySelector('input[name="vh360_agora_everyone_is_host"]');
            
            // Handle mode change to show/hide passcode section
            function togglePasscodeSection() {
                if (modeSelect && passcodeSection) {
                    if (modeSelect.value === 'interactive') {
                        passcodeSection.style.display = 'block';
                    } else {
                        passcodeSection.style.display = 'none';
                    }
                }
            }
            
            // Initial state
            togglePasscodeSection();
            
            // Listen for mode changes
            if (modeSelect) {
                modeSelect.addEventListener('change', togglePasscodeSection);
            }
            
            // Handle mutual exclusivity between "Allow Everyone to be Host" and "Require Passcode To Join"
            if (allowEveryoneCheckbox && requirePasscodeCheckbox) {
                allowEveryoneCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        requirePasscodeCheckbox.checked = false;
                        passcodeField.style.display = 'none';
                        passcodeInput.value = '';
                    }
                });
                
                requirePasscodeCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        allowEveryoneCheckbox.checked = false;
                        passcodeField.style.display = 'block';
                        // Auto-generate a passcode if empty
                        if (!passcodeInput.value.trim()) {
                            passcodeInput.value = generateRandomPasscode();
                        }
                    } else {
                        passcodeField.style.display = 'none';
                        passcodeInput.value = '';
                    }
                });
            }
        });
        
        function generateRandomPasscode() {
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 6; i++) {
                result += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return result;
        }
        </script>
        <?php
    }
    
    /**
     * Sidebar configuration meta box callback
     */
    public function sidebar_config_meta_box_callback($post) {
        // Nonce field for security
        wp_nonce_field('videohub360_sidebar_config_box', 'videohub360_sidebar_config_nonce');

        // Get current settings
        $sidebar_config = get_post_meta($post->ID, '_vh360_sidebar_config', true);
        $sidebar_config = wp_parse_args($sidebar_config, array(
            'enable_custom' => 'no',
            'video_layout' => 'sidebar',
            'custom_title' => '',
            'category_filter' => '',
            'series_filter' => '',
            'location_filter' => '',
            'tag_filter' => '',
            'video_type_filter' => 'all',
            'num_videos' => 6,
            'order_by' => 'date',
            'order_direction' => 'DESC',
            'exclude_current' => 'yes',
            'include_posts' => '',
            'exclude_posts' => ''
        ));

        ?>
        <style>
        .vh360-sidebar-field { 
            margin-bottom: 12px; 
        }
        .vh360-sidebar-field label { 
            font-weight: 600; 
            display: block; 
            margin-bottom: 4px;
            font-size: 12px;
        }
        .vh360-sidebar-field select,
        .vh360-sidebar-field input[type="text"],
        .vh360-sidebar-field input[type="number"] { 
            width: 100%; 
            max-width: 100%;
            font-size: 12px;
        }
        .vh360-sidebar-field input[type="number"] { 
            width: 80px; 
        }
        .vh360-sidebar-field input[type="checkbox"] { 
            width: auto; 
            margin-right: 6px;
        }
        .vh360-sidebar-field small { 
            color: #666; 
            font-style: italic;
            display: block;
            margin-top: 3px;
            font-size: 11px;
        }
        .vh360-sidebar-conditional {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .vh360-sidebar-conditional.active {
            display: block;
        }
        </style>

        <div class="vh360-sidebar-field">
            <label for="vh360_video_layout">Video Layout:</label>
            <select name="vh360_video_layout" id="vh360_video_layout">
                <option value="sidebar" <?php selected($sidebar_config['video_layout'], 'sidebar'); ?>>Sidebar Layout</option>
                <option value="full-width" <?php selected($sidebar_config['video_layout'], 'full-width'); ?>>Full Width Layout</option>
            </select>
            <small>Choose between sidebar layout or full-width layout for this video</small>
        </div>

        <div class="vh360-sidebar-field">
            <label>
                <input type="checkbox" name="vh360_sidebar_enable_custom" value="yes" <?php checked($sidebar_config['enable_custom'], 'yes'); ?> id="vh360_sidebar_enable_custom" />
                Enable Custom Sidebar Settings
            </label>
            <small>Use custom filtering instead of default latest videos</small>
        </div>

        <div class="vh360-sidebar-conditional" id="vh360_sidebar_custom_settings">
            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_custom_title">Custom Title:</label>
                <input type="text" name="vh360_sidebar_custom_title" id="vh360_sidebar_custom_title" value="<?php echo esc_attr($sidebar_config['custom_title']); ?>" placeholder="Latest Videos" />
                <small>Leave empty to use default "Latest Videos" title</small>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_category">Category Filter:</label>
                <select name="vh360_sidebar_category" id="vh360_sidebar_category">
                    <option value="">All Categories</option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'videohub360_category',
                        'hide_empty' => false,
                    ));
                    if (!is_wp_error($categories)) {
                        foreach ($categories as $term) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($term->slug),
                                selected($sidebar_config['category_filter'], $term->slug, false),
                                esc_html($term->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_series">Series Filter:</label>
                <select name="vh360_sidebar_series" id="vh360_sidebar_series">
                    <option value="">All Series</option>
                    <?php
                    $series = get_terms(array(
                        'taxonomy' => 'videohub360_series',
                        'hide_empty' => false,
                    ));
                    if (!is_wp_error($series)) {
                        foreach ($series as $term) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($term->slug),
                                selected($sidebar_config['series_filter'], $term->slug, false),
                                esc_html($term->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_location">Location Filter:</label>
                <select name="vh360_sidebar_location" id="vh360_sidebar_location">
                    <option value="">All Locations</option>
                    <?php
                    $locations = get_terms(array(
                        'taxonomy' => 'videohub360_location',
                        'hide_empty' => false,
                    ));
                    if (!is_wp_error($locations)) {
                        foreach ($locations as $term) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($term->slug),
                                selected($sidebar_config['location_filter'], $term->slug, false),
                                esc_html($term->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_tag">Tag Filter:</label>
                <input type="text" name="vh360_sidebar_tag" id="vh360_sidebar_tag" value="<?php echo esc_attr($sidebar_config['tag_filter']); ?>" placeholder="tag-slug" />
                <small>Enter tag slug (optional)</small>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_video_type">Video Type Filter:</label>
                <select name="vh360_sidebar_video_type" id="vh360_sidebar_video_type">
                    <option value="all" <?php selected($sidebar_config['video_type_filter'], 'all'); ?>>All Videos</option>
                    <option value="live_only" <?php selected($sidebar_config['video_type_filter'], 'live_only'); ?>>Live Videos Only</option>
                    <option value="regular_only" <?php selected($sidebar_config['video_type_filter'], 'regular_only'); ?>>Regular Videos Only</option>
                    <option value="embed_only" <?php selected($sidebar_config['video_type_filter'], 'embed_only'); ?>>Embed Videos Only</option>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_num_videos">Number of Videos:</label>
                <input type="number" name="vh360_sidebar_num_videos" id="vh360_sidebar_num_videos" value="<?php echo esc_attr($sidebar_config['num_videos']); ?>" min="1" max="30" />
                <small>1-30 videos</small>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_order_by">Order By:</label>
                <select name="vh360_sidebar_order_by" id="vh360_sidebar_order_by">
                    <option value="date" <?php selected($sidebar_config['order_by'], 'date'); ?>>Date</option>
                    <option value="title" <?php selected($sidebar_config['order_by'], 'title'); ?>>Title</option>
                    <option value="views" <?php selected($sidebar_config['order_by'], 'views'); ?>>View Count</option>
                    <option value="menu_order" <?php selected($sidebar_config['order_by'], 'menu_order'); ?>>Menu Order</option>
                    <option value="rand" <?php selected($sidebar_config['order_by'], 'rand'); ?>>Random</option>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_order_direction">Order Direction:</label>
                <select name="vh360_sidebar_order_direction" id="vh360_sidebar_order_direction">
                    <option value="DESC" <?php selected($sidebar_config['order_direction'], 'DESC'); ?>>Descending</option>
                    <option value="ASC" <?php selected($sidebar_config['order_direction'], 'ASC'); ?>>Ascending</option>
                </select>
            </div>

            <div class="vh360-sidebar-field">
                <label>
                    <input type="checkbox" name="vh360_sidebar_exclude_current" value="yes" <?php checked($sidebar_config['exclude_current'], 'yes'); ?> />
                    Exclude Current Post
                </label>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_include_posts">Include Specific Posts:</label>
                <input type="text" name="vh360_sidebar_include_posts" id="vh360_sidebar_include_posts" value="<?php echo esc_attr($sidebar_config['include_posts']); ?>" placeholder="1,2,3" />
                <small>Comma-separated post IDs</small>
            </div>

            <div class="vh360-sidebar-field">
                <label for="vh360_sidebar_exclude_posts">Exclude Specific Posts:</label>
                <input type="text" name="vh360_sidebar_exclude_posts" id="vh360_sidebar_exclude_posts" value="<?php echo esc_attr($sidebar_config['exclude_posts']); ?>" placeholder="4,5,6" />
                <small>Comma-separated post IDs</small>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleSidebarSettings() {
                if ($('#vh360_sidebar_enable_custom').is(':checked')) {
                    $('#vh360_sidebar_custom_settings').addClass('active');
                } else {
                    $('#vh360_sidebar_custom_settings').removeClass('active');
                }
            }

            $('#vh360_sidebar_enable_custom').on('change', toggleSidebarSettings);
            toggleSidebarSettings(); // Initialize on page load
        });
        </script>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['videohub360_video_meta_box_nonce'])) return;
        if (!wp_verify_nonce($_POST['videohub360_video_meta_box_nonce'], 'videohub360_video_meta_box')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save regular video fields
        if (isset($_POST['videohub360_video_url'])) {
            update_post_meta($post_id, 'video_url', esc_url_raw($_POST['videohub360_video_url']));
        }
        if (isset($_POST['videohub360_ad_video_url'])) {
            update_post_meta($post_id, 'ad_video_url', esc_url_raw($_POST['videohub360_ad_video_url']));
        }
        // Save new mid-roll and post-roll ad fields
        if (isset($_POST['videohub360_midroll_ad_url'])) {
            update_post_meta($post_id, 'midroll_ad_video_url', esc_url_raw($_POST['videohub360_midroll_ad_url']));
        }
        if (isset($_POST['videohub360_midroll_timing'])) {
            // Validate and clean timing values (comma-separated seconds)
            $timing = sanitize_text_field($_POST['videohub360_midroll_timing']);
            // Remove any invalid characters and ensure only numbers and commas
            $timing = preg_replace('/[^0-9,]/', '', $timing);
            update_post_meta($post_id, 'midroll_ad_timing', $timing);
        }
        if (isset($_POST['videohub360_postroll_ad_url'])) {
            update_post_meta($post_id, 'postroll_ad_video_url', esc_url_raw($_POST['videohub360_postroll_ad_url']));
        }
        // Save post-roll enabled checkbox
        $postroll_enabled = isset($_POST['videohub360_postroll_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, 'postroll_ad_enabled', $postroll_enabled);
        
        // Save ad click-through URLs
        if (isset($_POST['vh360_ad_click_url'])) {
            update_post_meta($post_id, '_vh360_ad_click_url', esc_url_raw($_POST['vh360_ad_click_url']));
        }
        if (isset($_POST['vh360_midroll_ad_click_url'])) {
            update_post_meta($post_id, '_vh360_midroll_ad_click_url', esc_url_raw($_POST['vh360_midroll_ad_click_url']));
        }
        if (isset($_POST['vh360_postroll_ad_click_url'])) {
            update_post_meta($post_id, '_vh360_postroll_ad_click_url', esc_url_raw($_POST['vh360_postroll_ad_click_url']));
        }
        
        if (isset($_POST['videohub360_manual_views'])) {
            $views = absint($_POST['videohub360_manual_views']);
            update_post_meta($post_id, '_videohub360_post_views_count', $views);
        }
        if (isset($_POST['videohub360_custom_html'])) {
            update_post_meta($post_id, 'videohub360_custom_html', vh360_sanitize_embed_code($_POST['videohub360_custom_html']));
        }

        // Save video quality settings
        $quality_override = isset($_POST['vh360_override_quality_settings']) ? 'yes' : 'no';
        update_post_meta($post_id, '_vh360_override_quality_settings', $quality_override);
        
        if ($quality_override === 'yes') {
            // Only save quality settings if override is enabled
            if (isset($_POST['vh360_video_quality'])) {
                $quality = VideoHub360_Video_Quality::validate_quality($_POST['vh360_video_quality']);
                if ($quality !== false) {
                    update_post_meta($post_id, '_vh360_video_quality', $quality);
                } else {
                    delete_post_meta($post_id, '_vh360_video_quality');
                }
            }
            
            if (isset($_POST['vh360_video_mirror'])) {
                $mirror = VideoHub360_Video_Quality::validate_mirror($_POST['vh360_video_mirror']);
                if ($mirror !== false) {
                    update_post_meta($post_id, '_vh360_video_mirror', $mirror);
                } else {
                    delete_post_meta($post_id, '_vh360_video_mirror');
                }
            }
        } else {
            // Clear quality settings if override is disabled
            delete_post_meta($post_id, '_vh360_video_quality');
            delete_post_meta($post_id, '_vh360_video_mirror');
        }

        // Save livestream fields
        $livestream_fields = [
            'is_live' => sanitize_text_field($_POST['vh360_is_live'] ?? 'no'),
            'type' => sanitize_text_field($_POST['vh360_type'] ?? ''),
            'embed_code' => isset($_POST['vh360_embed_code']) ? $_POST['vh360_embed_code'] : '',
            'stream_url' => sanitize_text_field($_POST['vh360_stream_url'] ?? ''),
            'api_url' => sanitize_text_field($_POST['vh360_api_url'] ?? ''),
            'poster' => sanitize_text_field($_POST['vh360_poster'] ?? ''),
            'viewer_count' => isset($_POST['vh360_viewer_count']) ? 'yes' : 'no',
            'live_badge' => isset($_POST['vh360_live_badge']) ? 'yes' : 'no',
            'badge_text' => sanitize_text_field($_POST['vh360_badge_text'] ?? 'LIVE'),
            'badge_color' => sanitize_text_field($_POST['vh360_badge_color'] ?? '#e53935'),
            'offline_message' => wp_kses_post($_POST['vh360_offline_message'] ?? ''),
            'live_start_time' => sanitize_text_field($_POST['vh360_live_start_time'] ?? ''),
            'stream_stopped' => sanitize_text_field($_POST['vh360_stream_stopped'] ?? 'no'),
            'context' => sanitize_text_field($_POST['vh360_context'] ?? 'default'),
            'chat_enabled' => isset($_POST['vh360_chat_enabled']) ? 'yes' : 'no',
            'chat_placement' => sanitize_text_field($_POST['vh360_chat_placement'] ?? ''),
            'agora_channel_name' => sanitize_text_field($_POST['vh360_agora_channel_name'] ?? ''),
            'agora_mode' => sanitize_text_field($_POST['vh360_agora_mode'] ?? 'interactive'),
            'agora_everyone_is_host' => isset($_POST['vh360_agora_everyone_is_host']) ? 'yes' : 'no',
            // host_passcode is handled separately below to support hashing and "leave blank = keep existing".
        ];

        // Handle host passcode separately: hash new values, keep existing when blank, clear when unchecked.
        $require_passcode = isset($_POST['vh360_require_passcode']) && $_POST['vh360_require_passcode'] === 'yes';
        if (!$require_passcode) {
            // Checkbox unchecked — clear the stored passcode.
            update_post_meta($post_id, '_vh360_host_passcode', '');
        } else {
            $new_passcode = sanitize_text_field($_POST['vh360_host_passcode'] ?? '');
            if ($new_passcode !== '') {
                // New passcode provided — hash it before storing.
                update_post_meta($post_id, '_vh360_host_passcode', wp_hash_password($new_passcode));
            }
            // If blank, do nothing — existing passcode is preserved.
        }

        // Fire action hooks for live room state transitions (only for live_room context)
        // Capture old state BEFORE updating meta, then update, then fire hooks based on transition
        if ($livestream_fields['context'] === 'live_room') {
            // Capture old state for transition detection (BEFORE meta update)
            $old_is_live = get_post_meta($post_id, '_vh360_is_live', true);
            // Normalize empty/falsy values to ensure consistent comparison
            $old_is_live = ($old_is_live === 'yes') ? 'yes' : 'no';
            $new_is_live = ($livestream_fields['is_live'] === 'yes') ? 'yes' : 'no';

            // Update all livestream meta fields (excluding host_passcode, handled above)
            foreach ($livestream_fields as $field => $value) {
                update_post_meta($post_id, '_vh360_' . $field, $value);
            }

            // Live room started: offline→live transition
            if ($old_is_live === 'no' && $new_is_live === 'yes') {
                do_action('vh360_live_room_started', $post_id);
            }

            // Live room ended: live→offline transition
            if ($old_is_live === 'yes' && $new_is_live === 'no') {
                do_action('vh360_live_room_ended', $post_id);
            }
        } else {
            // Non-live-room posts: just update meta without hook logic
            foreach ($livestream_fields as $field => $value) {
                update_post_meta($post_id, '_vh360_' . $field, $value);
            }
        }
        
        // Save sidebar configuration if nonce is present
        if (isset($_POST['videohub360_sidebar_config_nonce']) && 
            wp_verify_nonce($_POST['videohub360_sidebar_config_nonce'], 'videohub360_sidebar_config_box')) {
            
            $sidebar_config = array(
                'enable_custom' => isset($_POST['vh360_sidebar_enable_custom']) ? 'yes' : 'no',
                'video_layout' => sanitize_text_field($_POST['vh360_video_layout'] ?? 'sidebar'),
                'custom_title' => sanitize_text_field($_POST['vh360_sidebar_custom_title'] ?? ''),
                'category_filter' => sanitize_text_field($_POST['vh360_sidebar_category'] ?? ''),
                'series_filter' => sanitize_text_field($_POST['vh360_sidebar_series'] ?? ''),
                'location_filter' => sanitize_text_field($_POST['vh360_sidebar_location'] ?? ''),
                'tag_filter' => sanitize_text_field($_POST['vh360_sidebar_tag'] ?? ''),
                'video_type_filter' => sanitize_text_field($_POST['vh360_sidebar_video_type'] ?? 'all'),
                'num_videos' => max(1, min(30, intval($_POST['vh360_sidebar_num_videos'] ?? 6))),
                'order_by' => sanitize_text_field($_POST['vh360_sidebar_order_by'] ?? 'date'),
                'order_direction' => sanitize_text_field($_POST['vh360_sidebar_order_direction'] ?? 'DESC'),
                'exclude_current' => isset($_POST['vh360_sidebar_exclude_current']) ? 'yes' : 'no',
                'include_posts' => sanitize_text_field($_POST['vh360_sidebar_include_posts'] ?? ''),
                'exclude_posts' => sanitize_text_field($_POST['vh360_sidebar_exclude_posts'] ?? '')
            );
            
            update_post_meta($post_id, '_vh360_sidebar_config', $sidebar_config);
        }
    }
    
    /**
     * Helper functions for getting slug options
     */
    private function get_post_slug() {
        return get_option('videohub360_post_slug', 'videohub360');
    }
    
    private function get_category_slug() {
        return get_option('videohub360_category_slug', 'videohub360-category');
    }
    
    private function get_location_slug() {
        return get_option('videohub360_location_slug', 'videohub360-location');
    }
    
    private function get_series_slug() {
        return get_option('videohub360_series_slug', 'videohub360-series');
    }
    
    /**
     * Handle admin stop stream AJAX
     */
    public function admin_stop_stream() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_admin_stream_control')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $post_id = absint($_POST['post_id'] ?? 0);
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        try {
            // Set stream as stopped
            update_post_meta($post_id, '_vh360_stream_stopped', 'yes');
            
            wp_send_json_success(array(
                'message' => 'Stream stopped successfully',
                'post_id' => $post_id,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to stop stream: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle admin restart stream AJAX
     */
    public function admin_restart_stream() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vh360_admin_stream_control')) {
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $post_id = absint($_POST['post_id'] ?? 0);
        
        // Validate post exists and is videohub360 type
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            wp_send_json_error('Invalid video post');
            return;
        }
        
        try {
            // Remove stream stopped status
            delete_post_meta($post_id, '_vh360_stream_stopped');
            
            // Set live status if it wasn't already set
            $is_live = get_post_meta($post_id, '_vh360_is_live', true);
            if (empty($is_live)) {
                update_post_meta($post_id, '_vh360_is_live', 'yes');
            }
            
            wp_send_json_success(array(
                'message' => 'Stream restarted successfully',
                'post_id' => $post_id,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to restart stream: ' . $e->getMessage());
        }
    }
    
    /**
     * Dashboard page - Main overview and quick actions
     */
    public function dashboard_page() {
        // Get dashboard statistics
        $stats = $this->get_dashboard_stats();
        $recent_videos = $this->get_recent_videos(5);
        $system_status = $this->get_system_status();
        
        ?>
        <div class="wrap vh360-dashboard">
            <h1>
                <span class="dashicons dashicons-video-alt3"></span>
                <?php echo esc_html__('VideoHub360 Dashboard', 'videohub360'); ?>
            </h1>
            
            <!-- Stats Overview Cards -->
            <div class="vh360-stats-grid">
                <div class="vh360-stat-card vh360-stat-videos">
                    <div class="vh360-stat-icon">
                        <span class="dashicons dashicons-video-alt3"></span>
                    </div>
                    <div class="vh360-stat-content">
                        <div class="vh360-stat-number"><?php echo number_format($stats['total_videos']); ?></div>
                        <div class="vh360-stat-label"><?php echo esc_html__('Total Videos', 'videohub360'); ?></div>
                    </div>
                </div>
                
                <div class="vh360-stat-card vh360-stat-views">
                    <div class="vh360-stat-icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="vh360-stat-content">
                        <div class="vh360-stat-number"><?php echo number_format($stats['total_views']); ?></div>
                        <div class="vh360-stat-label"><?php echo esc_html__('Total Views', 'videohub360'); ?></div>
                    </div>
                </div>
                
                <div class="vh360-stat-card vh360-stat-live">
                    <div class="vh360-stat-icon">
                        <span class="dashicons dashicons-video-alt2"></span>
                    </div>
                    <div class="vh360-stat-content">
                        <div class="vh360-stat-number"><?php echo number_format($stats['live_streams']); ?></div>
                        <div class="vh360-stat-label"><?php echo esc_html__('Live Streams', 'videohub360'); ?></div>
                    </div>
                </div>
                
                <div class="vh360-stat-card vh360-stat-categories">
                    <div class="vh360-stat-icon">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <div class="vh360-stat-content">
                        <div class="vh360-stat-number"><?php echo number_format($stats['categories']); ?></div>
                        <div class="vh360-stat-label"><?php echo esc_html__('Categories', 'videohub360'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="vh360-dashboard-grid">
                <!-- Recent Uploads -->
                <div class="vh360-dashboard-section">
                    <div class="vh360-section-header">
                        <h2>
                            <span class="dashicons dashicons-update"></span>
                            <?php echo esc_html__('Recent Uploads', 'videohub360'); ?>
                        </h2>
                        <a href="<?php echo admin_url('edit.php?post_type=videohub360'); ?>" class="button button-secondary">
                            <?php echo esc_html__('View All', 'videohub360'); ?>
                        </a>
                    </div>
                    
                    <?php if (!empty($recent_videos)): ?>
                        <table class="vh360-dashboard-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Video', 'videohub360'); ?></th>
                                    <th><?php echo esc_html__('Status', 'videohub360'); ?></th>
                                    <th><?php echo esc_html__('Views', 'videohub360'); ?></th>
                                    <th><?php echo esc_html__('Date', 'videohub360'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_videos as $video): ?>
                                    <tr>
                                        <td class="vh360-video-title">
                                            <a href="<?php echo get_edit_post_link($video->ID); ?>">
                                                <?php echo esc_html($video->post_title); ?>
                                            </a>
                                            <?php if (get_post_meta($video->ID, '_vh360_is_live', true) === 'yes'): ?>
                                                <span class="vh360-badge vh360-badge-live"><?php echo esc_html__('LIVE', 'videohub360'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_class = 'vh360-status-' . $video->post_status;
                                            $status_label = ucfirst($video->post_status);
                                            ?>
                                            <span class="vh360-status-badge <?php echo esc_attr($status_class); ?>">
                                                <?php echo esc_html($status_label); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $views = get_post_meta($video->ID, '_videohub360_post_views_count', true);
                                            echo number_format($views ? $views : 0);
                                            ?>
                                        </td>
                                        <td><?php echo human_time_diff(strtotime($video->post_date), current_time('timestamp')) . ' ' . __('ago', 'videohub360'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="vh360-empty-state">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <p><?php echo esc_html__('No videos yet. Create your first video!', 'videohub360'); ?></p>
                            <a href="<?php echo admin_url('post-new.php?post_type=videohub360'); ?>" class="button button-primary">
                                <?php echo esc_html__('Add New Video', 'videohub360'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="vh360-dashboard-sidebar">
                    <div class="vh360-dashboard-section vh360-quick-actions">
                        <div class="vh360-section-header">
                            <h2>
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                <?php echo esc_html__('Quick Actions', 'videohub360'); ?>
                            </h2>
                        </div>
                        <div class="vh360-action-buttons">
                            <a href="<?php echo admin_url('post-new.php?post_type=videohub360'); ?>" class="vh360-action-button">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php echo esc_html__('Add New Video', 'videohub360'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=videohub360'); ?>" class="vh360-action-button">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php echo esc_html__('Manage Videos', 'videohub360'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=videohub360&page=videohub360-settings'); ?>" class="vh360-action-button">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php echo esc_html__('Settings', 'videohub360'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=videohub360&page=videohub360-analytics'); ?>" class="vh360-action-button">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php echo esc_html__('View Analytics', 'videohub360'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=videohub360&page=videohub360-shortcodes'); ?>" class="vh360-action-button">
                                <span class="dashicons dashicons-editor-code"></span>
                                <?php echo esc_html__('Shortcode Builder', 'videohub360'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="vh360-dashboard-section vh360-system-status">
                        <div class="vh360-section-header">
                            <h2>
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php echo esc_html__('System Status', 'videohub360'); ?>
                            </h2>
                        </div>
                        <div class="vh360-status-list">
                            <div class="vh360-status-item">
                                <span class="vh360-status-label"><?php echo esc_html__('Chat Enabled', 'videohub360'); ?></span>
                                <span class="vh360-status-value <?php echo $system_status['chat_enabled'] ? 'vh360-status-active' : 'vh360-status-inactive'; ?>">
                                    <?php echo $system_status['chat_enabled'] ? esc_html__('Yes', 'videohub360') : esc_html__('No', 'videohub360'); ?>
                                </span>
                            </div>
                            <div class="vh360-status-item">
                                <span class="vh360-status-label"><?php echo esc_html__('Agora Configured', 'videohub360'); ?></span>
                                <span class="vh360-status-value <?php echo $system_status['agora_configured'] ? 'vh360-status-active' : 'vh360-status-inactive'; ?>">
                                    <?php echo $system_status['agora_configured'] ? esc_html__('Yes', 'videohub360') : esc_html__('No', 'videohub360'); ?>
                                </span>
                            </div>
                            <div class="vh360-status-item">
                                <span class="vh360-status-label"><?php echo esc_html__('Ad Tracking', 'videohub360'); ?></span>
                                <span class="vh360-status-value <?php echo $system_status['ad_tracking'] ? 'vh360-status-active' : 'vh360-status-inactive'; ?>">
                                    <?php echo $system_status['ad_tracking'] ? esc_html__('Enabled', 'videohub360') : esc_html__('Disabled', 'videohub360'); ?>
                                </span>
                            </div>
                            <div class="vh360-status-item">
                                <span class="vh360-status-label"><?php echo esc_html__('Plugin Version', 'videohub360'); ?></span>
                                <span class="vh360-status-value"><?php echo esc_html(VIDEOHUB360_VERSION); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Helpful Resources -->
                    <div class="vh360-dashboard-section vh360-resources">
                        <div class="vh360-section-header">
                            <h2>
                                <span class="dashicons dashicons-book"></span>
                                <?php echo esc_html__('Resources & Support', 'videohub360'); ?>
                            </h2>
                        </div>
                        <div class="vh360-resource-links">
                            <a href="https://videohub360.com/documentation/" target="_blank" class="vh360-resource-link">
                                <span class="dashicons dashicons-media-document"></span>
                                <?php echo esc_html__('Documentation', 'videohub360'); ?>
                            </a>
                            <a href="https://videohub360.com/support/" target="_blank" class="vh360-resource-link">
                                <span class="dashicons dashicons-sos"></span>
                                <?php echo esc_html__('Get Support', 'videohub360'); ?>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=videohub360&page=videohub360-shortcodes'); ?>" class="vh360-resource-link">
                                <span class="dashicons dashicons-editor-help"></span>
                                <?php echo esc_html__('Shortcode Guide', 'videohub360'); ?>
                            </a>
                            <a href="https://videohub360.com/changelog/" target="_blank" class="vh360-resource-link">
                                <span class="dashicons dashicons-format-aside"></span>
                                <?php echo esc_html__('Changelog', 'videohub360'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activity Chart (if data available) -->
            <?php if ($stats['total_views'] > 0): ?>
                <div class="vh360-dashboard-section vh360-chart-section">
                    <div class="vh360-section-header">
                        <h2>
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php echo esc_html__('Views Overview (Last 7 Days)', 'videohub360'); ?>
                        </h2>
                    </div>
                    <div class="vh360-chart-container">
                        <canvas id="vh360-views-chart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     * 
     * @return array Statistics data
     */
    private function get_dashboard_stats() {
        return self::calculate_statistics();
    }
    
    /**
     * Calculate plugin statistics (shared method)
     * Static method to allow sharing with AJAX class
     * 
     * @return array Statistics data
     */
    public static function calculate_statistics() {
        global $wpdb;
        
        // Get total videos count
        $total_videos = wp_count_posts('videohub360');
        $published_videos = isset($total_videos->publish) ? $total_videos->publish : 0;
        
        // Get total views across all videos
        $total_views = $wpdb->get_var(
            "SELECT SUM(CAST(meta_value AS UNSIGNED)) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_videohub360_post_views_count'"
        );
        
        // Get live streams count
        $live_streams = $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_vh360_is_live' 
             AND meta_value = 'yes'"
        );
        
        // Get categories count
        $categories = wp_count_terms(array(
            'taxonomy' => 'videohub360-category',
            'hide_empty' => false
        ));
        
        return array(
            'total_videos' => $published_videos,
            'total_views' => $total_views ? intval($total_views) : 0,
            'live_streams' => $live_streams ? intval($live_streams) : 0,
            'categories' => is_wp_error($categories) ? 0 : $categories
        );
    }
    
    /**
     * Get recent videos
     */
    private function get_recent_videos($limit = 5) {
        $args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => $limit,
            'post_status' => array('publish', 'draft', 'pending'),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Get system status information
     */
    private function get_system_status() {
        return array(
            'chat_enabled' => get_option('videohub360_chat_enabled', 1),
            'agora_configured' => !empty(get_option('vh360_agora_app_id', '')) && !empty(get_option('vh360_agora_app_certificate', '')),
            'ad_tracking' => get_option('vh360_ad_click_tracking_enabled', 0)
        );
    }
    
    /**
     * Analytics page - Ad Click Statistics
     */
    public function analytics_page() {
        // Check if ad click tracking is enabled
        $tracking_enabled = get_option('vh360_ad_click_tracking_enabled', 0);
        
        // Get videohub360 posts with ad clicks (limit to recent posts for performance)
        // Allow filtering the limit for large sites
        $post_limit = apply_filters('vh360_analytics_page_post_limit', 100);
        
        $args = array(
            'post_type' => 'videohub360',
            'posts_per_page' => absint($post_limit), // Limit for performance
            'post_status' => 'publish',
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $posts = get_posts($args);
        
        $total_clicks = array(
            'preroll' => 0,
            'midroll' => 0,
            'postroll' => 0
        );
        
        $video_stats = array();
        
        foreach ($posts as $post_id) {
            $preroll_count = get_post_meta($post_id, '_vh360_ad_clicks_count_preroll', true);
            $midroll_count = get_post_meta($post_id, '_vh360_ad_clicks_count_midroll', true);
            $postroll_count = get_post_meta($post_id, '_vh360_ad_clicks_count_postroll', true);
            
            $preroll_count = $preroll_count ? intval($preroll_count) : 0;
            $midroll_count = $midroll_count ? intval($midroll_count) : 0;
            $postroll_count = $postroll_count ? intval($postroll_count) : 0;
            
            $total_clicks['preroll'] += $preroll_count;
            $total_clicks['midroll'] += $midroll_count;
            $total_clicks['postroll'] += $postroll_count;
            
            $video_total = $preroll_count + $midroll_count + $postroll_count;
            
            if ($video_total > 0) {
                $video_stats[] = array(
                    'id' => $post_id,
                    'title' => get_the_title($post_id),
                    'clicks' => $video_total,
                    'preroll' => $preroll_count,
                    'midroll' => $midroll_count,
                    'postroll' => $postroll_count
                );
            }
        }
        
        // Sort by total clicks (descending)
        usort($video_stats, function($a, $b) {
            return $b['clicks'] - $a['clicks'];
        });
        
        // Get top 10 for full page view
        $top_videos = array_slice($video_stats, 0, 10);
        
        $grand_total = $total_clicks['preroll'] + $total_clicks['midroll'] + $total_clicks['postroll'];
        
        ?>
        <div class="wrap">
            <h1>Ad Click Analytics</h1>
            
            <?php if (!$tracking_enabled): ?>
                <div class="notice notice-warning">
                    <p><strong>Ad Click Tracking is Disabled</strong></p>
                    <p>To start tracking ad clicks, enable "Enable Click Tracking" in <a href="<?php echo esc_url(admin_url('edit.php?post_type=videohub360&page=videohub360-settings')); ?>">VideoHub360 Settings</a>.</p>
                </div>
            <?php endif; ?>
            
            <style>
                .vh360-analytics-stats { margin-top: 20px; }
                .vh360-stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .vh360-stat-box { padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
                .vh360-stat-number { font-size: 32px; font-weight: 600; color: #0073aa; line-height: 1.2; }
                .vh360-stat-label { font-size: 13px; color: #646970; margin-top: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
                .vh360-top-videos { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
                .vh360-top-videos h2 { margin: 0; padding: 15px 20px; border-bottom: 1px solid #ccd0d4; font-size: 14px; font-weight: 600; }
                .vh360-video-table { width: 100%; border-collapse: collapse; }
                .vh360-video-table th { padding: 12px 20px; text-align: left; border-bottom: 1px solid #ccd0d4; background: #f6f7f7; font-size: 12px; font-weight: 600; color: #646970; text-transform: uppercase; }
                .vh360-video-table td { padding: 12px 20px; border-bottom: 1px solid #f0f0f1; }
                .vh360-video-table tr:last-child td { border-bottom: none; }
                .vh360-video-table tr:hover { background: #f6f7f7; }
                .vh360-video-title a { color: #2271b1; text-decoration: none; font-weight: 500; }
                .vh360-video-title a:hover { color: #135e96; }
                .vh360-video-clicks { color: #0073aa; font-weight: 600; text-align: right; }
                .vh360-click-breakdown { font-size: 11px; color: #646970; margin-top: 4px; }
                .vh360-no-data { text-align: center; padding: 40px 20px; color: #646970; }
                .vh360-no-data p { margin: 10px 0; }
            </style>
            
            <div class="vh360-analytics-stats">
                <?php if ($grand_total > 0): ?>
                    <div class="vh360-stats-summary">
                        <div class="vh360-stat-box">
                            <div class="vh360-stat-number"><?php echo number_format($grand_total); ?></div>
                            <div class="vh360-stat-label">Total Clicks</div>
                        </div>
                        <div class="vh360-stat-box">
                            <div class="vh360-stat-number"><?php echo number_format($total_clicks['preroll']); ?></div>
                            <div class="vh360-stat-label">Pre-roll Clicks</div>
                        </div>
                        <div class="vh360-stat-box">
                            <div class="vh360-stat-number"><?php echo number_format($total_clicks['midroll']); ?></div>
                            <div class="vh360-stat-label">Mid-roll Clicks</div>
                        </div>
                        <div class="vh360-stat-box">
                            <div class="vh360-stat-number"><?php echo number_format($total_clicks['postroll']); ?></div>
                            <div class="vh360-stat-label">Post-roll Clicks</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($top_videos)): ?>
                        <div class="vh360-top-videos">
                            <h2>Top Performing Videos</h2>
                            <table class="vh360-video-table">
                                <thead>
                                    <tr>
                                        <th>Video Title</th>
                                        <th style="text-align: right;">Total Clicks</th>
                                        <th style="text-align: right;">Pre-roll</th>
                                        <th style="text-align: right;">Mid-roll</th>
                                        <th style="text-align: right;">Post-roll</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_videos as $video): ?>
                                        <tr>
                                            <td class="vh360-video-title">
                                                <a href="<?php echo esc_url(get_edit_post_link($video['id'])); ?>">
                                                    <?php echo esc_html($video['title']); ?>
                                                </a>
                                            </td>
                                            <td class="vh360-video-clicks">
                                                <?php echo number_format($video['clicks']); ?>
                                            </td>
                                            <td style="text-align: right; color: #646970;">
                                                <?php echo number_format($video['preroll']); ?>
                                            </td>
                                            <td style="text-align: right; color: #646970;">
                                                <?php echo number_format($video['midroll']); ?>
                                            </td>
                                            <td style="text-align: right; color: #646970;">
                                                <?php echo number_format($video['postroll']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="vh360-top-videos">
                        <div class="vh360-no-data">
                            <p><strong>No ad clicks recorded yet.</strong></p>
                            <p>Ad clicks will appear here once viewers start clicking on your video ads.</p>
                            <?php if (!$tracking_enabled): ?>
                                <p style="margin-top: 20px;">
                                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=videohub360&page=videohub360-settings')); ?>" class="button button-primary">
                                        Enable Click Tracking
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Shortcodes page - Display all available shortcodes with documentation
     */
    public function shortcodes_page() {
        ?>
        <div class="wrap vh360-shortcodes-page">
            <!-- Page Header with Search and History -->
            <div class="vh360-shortcodes-header">
                <div>
                    <h1><?php echo esc_html__('VideoHub360 Shortcodes', 'videohub360'); ?></h1>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div class="vh360-search-container">
                        <input type="text" id="vh360-search-shortcodes" placeholder="<?php esc_attr_e('Search shortcodes, parameters, or examples...', 'videohub360'); ?>" />
                        <button type="button" id="vh360-clear-search" aria-label="<?php esc_attr_e('Clear search', 'videohub360'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    
                    <div class="vh360-copy-history-dropdown">
                        <button type="button" class="vh360-copy-history-toggle">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Recently Copied', 'videohub360'); ?>
                        </button>
                        <div class="vh360-copy-history-menu">
                            <div class="vh360-copy-history-header">
                                <span><?php esc_html_e('Copy History', 'videohub360'); ?></span>
                                <button type="button" id="vh360-clear-history" class="button button-small">
                                    <?php esc_html_e('Clear', 'videohub360'); ?>
                                </button>
                            </div>
                            <div id="vh360-copy-history-items"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="vh360-shortcodes-intro">
                <?php echo esc_html__('Use these shortcodes to display videos and hero banners anywhere on your WordPress site. Click the copy button next to any example to copy it to your clipboard.', 'videohub360'); ?>
            </p>
            
            <!-- No Results Message (Hidden by default) -->
            <div id="vh360-no-results">
                <span class="dashicons dashicons-search"></span>
                <h3><?php esc_html_e('No results found', 'videohub360'); ?></h3>
                <p><?php esc_html_e('Try a different search term or clear your search to see all shortcodes.', 'videohub360'); ?></p>
                <button type="button" class="button vh360-clear-search-btn">
                    <?php esc_html_e('Clear Search', 'videohub360'); ?>
                </button>
            </div>
            
            <!-- Shortcode Builder -->
            <div class="vh360-shortcode-builder">
                <h2><span class="vh360-section-icon">🔧</span><?php echo esc_html__('Interactive Shortcode Builder', 'videohub360'); ?></h2>
                <p class="vh360-builder-intro">
                    <?php echo esc_html__('Build custom shortcodes step-by-step by selecting options below. The builder will generate the shortcode for you.', 'videohub360'); ?>
                </p>
                
                <div class="vh360-builder-form" id="vh360-shortcode-builder">
                    <!-- Step 1: Choose Shortcode Type -->
                    <div class="vh360-builder-step">
                        <h3><span class="vh360-step-number">1</span><?php esc_html_e('Choose Shortcode Type', 'videohub360'); ?></h3>
                        <select id="vh360-shortcode-type" class="vh360-param-input">
                            <option value=""><?php esc_html_e('-- Select a shortcode type --', 'videohub360'); ?></option>
                            <option value="hero"><?php esc_html_e('Hero Banner / Slider', 'videohub360'); ?></option>
                            <option value="videos"><?php esc_html_e('Video Grid / List', 'videohub360'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Step 2: Configure Parameters -->
                    <div class="vh360-builder-step">
                        <h3><span class="vh360-step-number">2</span><?php esc_html_e('Configure Parameters', 'videohub360'); ?></h3>
                        
                        <?php
                        // Load the shortcode builder class
                        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-shortcode-builder.php';
                        
                        // Hero Banner Parameters
                        echo '<div id="vh360-params-hero" class="vh360-builder-params" style="display:none;">';
                        $hero_params = VideoHub360_Shortcode_Builder::get_hero_parameters();
                        foreach ($hero_params as $param_key => $param_config) {
                            $this->render_builder_param('hero_' . $param_key, $param_config);
                        }
                        echo '</div>';
                        
                        // Video Grid Parameters
                        echo '<div id="vh360-params-videos" class="vh360-builder-params" style="display:none;">';
                        $video_params = VideoHub360_Shortcode_Builder::get_video_grid_parameters();
                        foreach ($video_params as $param_key => $param_config) {
                            $this->render_builder_param('videos_' . $param_key, $param_config);
                        }
                        echo '</div>';
                        ?>
                    </div>
                    
                    <!-- Step 3: Generate Shortcode -->
                    <div class="vh360-builder-step">
                        <h3><span class="vh360-step-number">3</span><?php esc_html_e('Generate & Copy', 'videohub360'); ?></h3>
                        <div class="vh360-builder-actions">
                            <button type="button" id="vh360-generate-shortcode" class="button button-primary button-large">
                                <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Generate Shortcode', 'videohub360'); ?>
                            </button>
                            <button type="button" id="vh360-reset-builder" class="button button-secondary button-large">
                                <span class="dashicons dashicons-image-rotate"></span> <?php esc_html_e('Reset Builder', 'videohub360'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Generated Shortcode Output -->
                    <div class="vh360-generated-output" style="display:none;">
                        <h3><?php esc_html_e('Generated Shortcode', 'videohub360'); ?></h3>
                        <textarea id="vh360-generated-shortcode" readonly></textarea>
                        <div class="vh360-generated-actions">
                            <button type="button" id="vh360-copy-generated" class="button button-primary">
                                <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy to Clipboard', 'videohub360'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Export/Import Configuration -->
                    <div class="vh360-export-import">
                        <button type="button" id="vh360-export-config" class="button">
                            <span class="dashicons dashicons-download"></span> <?php esc_html_e('Export Configuration', 'videohub360'); ?>
                        </button>
                        <button type="button" id="vh360-import-btn" class="button">
                            <span class="dashicons dashicons-upload"></span> <?php esc_html_e('Import Configuration', 'videohub360'); ?>
                        </button>
                        <input type="file" id="vh360-import-config" accept=".json" style="display:none;" />
                    </div>
                </div>
            </div>
            
            <!-- Taxonomy Reference Tool -->
            <div class="vh360-accordion">
                <div class="vh360-accordion-header">
                    <h2><span class="vh360-section-icon">📚</span><?php echo esc_html__('Available Categories, Series & Locations', 'videohub360'); ?></h2>
                    <span class="dashicons dashicons-arrow-down vh360-accordion-icon"></span>
                </div>
                <div class="vh360-accordion-content">
                    <p><?php echo esc_html__('Copy taxonomy slugs to use in your shortcodes. Click the copy icon next to any slug to copy it to your clipboard.', 'videohub360'); ?></p>
                    <div id="vh360-taxonomy-reference"></div>
                </div>
            </div>
            
            <!-- Common Use Cases Section -->
            <?php $this->render_common_use_cases(); ?>
            
            <!-- Hero Banner Shortcodes Section -->
            <div class="vh360-shortcode-category vh360-category-hero">
                <h2><span class="vh360-section-icon">🎬</span><?php echo esc_html__('Hero Banner & Slider', 'videohub360'); ?></h2>
                <p class="vh360-category-description">
                    <?php echo esc_html__('Create stunning hero banners and sliders with image banners, video backgrounds, embeds, or custom content.', 'videohub360'); ?>
                </p>
                
                <!-- Basic Hero -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Basic Hero Banner', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Simple hero banner with image and content on the right.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_hero video_type="image" poster="https://videohub360.com/poster.jpg" image_action="link" image_link_url="/videos/" headline="Welcome to Our Site" subhead="Discover amazing video content" cta1_label="Watch Now" cta1_url="/videos/"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero video_type="image" poster="https://videohub360.com/poster.jpg" image_action="link" image_link_url="/videos/" headline="Welcome to Our Site" subhead="Discover amazing video content" cta1_label="Watch Now" cta1_url="/videos/"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                    <div style="margin-top: 10px;">
                        <button type="button" class="button vh360-preview-btn" data-shortcode='[videohub360_hero video_type="image" poster="https://videohub360.com/poster.jpg" image_action="link" image_link_url="/videos/" headline="Welcome to Our Site" subhead="Discover amazing video content" cta1_label="Watch Now" cta1_url="/videos/"]'>
                            <span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Preview', 'videohub360'); ?>
                        </button>
                    </div>
                    <div class="vh360-preview-container"></div>
                </div>
                
                <!-- Video Background Hero -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Hero with Video Background', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Hero banner with auto-playing muted video background.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_hero video_type="mp4" video_url="https://videohub360.com/video.mp4" video_autoplay="yes" video_loop="yes" video_controls="no" headline="Experience Innovation" cta1_label="Learn More" cta1_url="/about/"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero video_type="mp4" video_url="https://videohub360.com/video.mp4" video_autoplay="yes" video_loop="yes" video_controls="no" headline="Experience Innovation" cta1_label="Learn More" cta1_url="/about/"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Hero Slider -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Auto-Playing Hero Slider', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Multiple slides with automatic transitions and navigation controls.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_hero mode="slider" autoplay="yes" autoplay_delay="5000" show_arrows="yes" show_dots="yes" transition_type="slide"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero mode="slider" autoplay="yes" autoplay_delay="5000" show_arrows="yes" show_dots="yes" transition_type="slide"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Expandable Image Hero -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Expandable Image Banner (Lightbox)', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Hero banner with a click-to-expand image lightbox.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_hero video_type="image" poster="https://videohub360.com/banner.jpg" image_action="lightbox" headline="Click to Expand" cta1_label="Learn More" cta1_url="/about/"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero video_type="image" poster="https://videohub360.com/banner.jpg" image_action="lightbox" headline="Click to Expand" cta1_label="Learn More" cta1_url="/about/"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Hero Parameters Table -->
                <div class="vh360-parameters-table">
                    <h4><?php echo esc_html__('Hero Banner Parameters', 'videohub360'); ?></h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Parameter', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Default', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Options', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Description', 'videohub360'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>mode</code></td>
                                <td>single</td>
                                <td>single, slider</td>
                                <td><?php echo esc_html__('Display mode: single slide or multi-slide carousel', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>layout</code></td>
                                <td>video_left</td>
                                <td>video_left, video_right</td>
                                <td><?php echo esc_html__('Position of video/image relative to content', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>theme</code></td>
                                <td>light</td>
                                <td>light, dark, transparent, custom</td>
                                <td><?php echo esc_html__('Color theme for the hero section', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>aspect_ratio</code></td>
                                <td>16:9</td>
                                <td>16:9, 4:3, 1:1</td>
                                <td><?php echo esc_html__('Video/image aspect ratio', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_type</code></td>
                                <td>image</td>
                                <td>image, mp4, embed, html</td>
                                <td><?php echo esc_html__('Type of media to display', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>poster</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('Image/banner URL or video poster URL', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>image_action</code></td>
                                <td>none</td>
                                <td>none, link, lightbox</td>
                                <td><?php echo esc_html__('Image click behavior: no action, open link, or expand in lightbox', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>image_link_url</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('URL to open when image is clicked (requires image_action="link")', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>image_link_new_tab</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Open image link in a new tab', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>image_link_nofollow</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Add rel="nofollow" to the image link', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_url</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('MP4 video file URL', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>embed_url</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('YouTube/Vimeo/Twitch embed URL', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_autoplay</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Auto-play video (muted only)', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_loop</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Loop video playback', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_controls</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Show video player controls', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>headline</code></td>
                                <td></td>
                                <td>text</td>
                                <td><?php echo esc_html__('Main headline text', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>subhead</code></td>
                                <td></td>
                                <td>text</td>
                                <td><?php echo esc_html__('Subheadline text', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>eyebrow</code></td>
                                <td></td>
                                <td>text</td>
                                <td><?php echo esc_html__('Small badge/eyebrow text above headline', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>cta1_label</code></td>
                                <td></td>
                                <td>text</td>
                                <td><?php echo esc_html__('Primary call-to-action button label', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>cta1_url</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('Primary CTA button URL', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>cta1_new_tab</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Open primary CTA in new tab', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>cta2_label</code></td>
                                <td></td>
                                <td>text</td>
                                <td><?php echo esc_html__('Secondary call-to-action button label', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>cta2_url</code></td>
                                <td></td>
                                <td>URL</td>
                                <td><?php echo esc_html__('Secondary CTA button URL', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>autoplay</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Auto-advance slides in slider mode', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>autoplay_delay</code></td>
                                <td>5000</td>
                                <td>milliseconds</td>
                                <td><?php echo esc_html__('Delay between slide transitions', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_arrows</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Show navigation arrows in slider', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_dots</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Show dot indicators in slider', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>transition_type</code></td>
                                <td>slide</td>
                                <td>slide, fade</td>
                                <td><?php echo esc_html__('Slider transition animation', 'videohub360'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Video Grid/List Shortcodes Section -->
            <div class="vh360-shortcode-category vh360-category-videos">
                <h2><span class="vh360-section-icon">📹</span><?php echo esc_html__('Video Grid & List Display', 'videohub360'); ?></h2>
                <p class="vh360-category-description">
                    <?php echo esc_html__('Display videos in grid or list layouts with powerful filtering and sorting options.', 'videohub360'); ?>
                </p>
                
                <!-- Basic Grid -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Basic Video Grid', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Display 6 videos in a responsive grid layout.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos display="grid" posts="6"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos display="grid" posts="6"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- List View -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Video List View', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Display videos in a vertical list with thumbnails and metadata.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos display="list" posts="8"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos display="list" posts="8"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Custom Columns -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('4-Column Grid', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Display videos in a fixed 4-column grid layout.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos display="grid" columns="4" posts="12"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos display="grid" columns="4" posts="12"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Category Filter -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Filter by Category', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Show only videos from a specific category.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos category="tutorials" posts="9" columns="3"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos category="tutorials" posts="9" columns="3"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Series Filter -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Filter by Series', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Display videos from a specific series.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos series="getting-started" posts="6"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos series="getting-started" posts="6"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Most Viewed -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Most Viewed Videos', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Sort videos by view count to show most popular content.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos orderby="views" order="DESC" posts="8" columns="4"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos orderby="views" order="DESC" posts="8" columns="4"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Live Videos Only -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Live Videos Only', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Display only livestream videos.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos live_only="yes" posts="6"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos live_only="yes" posts="6"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Example -->
                <div class="vh360-shortcode-item">
                    <h3><?php echo esc_html__('Advanced: Multiple Filters', 'videohub360'); ?></h3>
                    <p><?php echo esc_html__('Combine category filter, custom columns, sorting, and display options.', 'videohub360'); ?></p>
                    <div class="vh360-shortcode-example">
                        <code class="vh360-code-block">[videohub360_videos category="tutorials" orderby="views" order="DESC" posts="9" columns="3" show_excerpt="yes" excerpt_length="120" exclude_current="yes"]</code>
                        <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos category="tutorials" orderby="views" order="DESC" posts="9" columns="3" show_excerpt="yes" excerpt_length="120" exclude_current="yes"]'>
                            <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Copy', 'videohub360'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Video Grid Parameters Table -->
                <div class="vh360-parameters-table">
                    <h4><?php echo esc_html__('Video Grid/List Parameters', 'videohub360'); ?></h4>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Parameter', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Default', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Options', 'videohub360'); ?></th>
                                <th><?php echo esc_html__('Description', 'videohub360'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>display</code></td>
                                <td>grid</td>
                                <td>grid, list</td>
                                <td><?php echo esc_html__('Layout type: grid or list view', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>posts</code></td>
                                <td>6</td>
                                <td>1-30</td>
                                <td><?php echo esc_html__('Number of videos to display', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>columns</code></td>
                                <td>auto</td>
                                <td>auto, 1, 2, 3, 4</td>
                                <td><?php echo esc_html__('Number of columns in grid layout', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>category</code></td>
                                <td></td>
                                <td>category slug</td>
                                <td><?php echo esc_html__('Filter by video category', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>series</code></td>
                                <td></td>
                                <td>series slug</td>
                                <td><?php echo esc_html__('Filter by video series', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>location</code></td>
                                <td></td>
                                <td>location slug</td>
                                <td><?php echo esc_html__('Filter by video location', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>tag</code></td>
                                <td></td>
                                <td>tag slug</td>
                                <td><?php echo esc_html__('Filter by video tag', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>orderby</code></td>
                                <td>date</td>
                                <td>date, title, views, menu_order, rand</td>
                                <td><?php echo esc_html__('Field to sort videos by', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>order</code></td>
                                <td>DESC</td>
                                <td>ASC, DESC</td>
                                <td><?php echo esc_html__('Sort direction: ascending or descending', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>video_type</code></td>
                                <td>all</td>
                                <td>all, live, regular, embed</td>
                                <td><?php echo esc_html__('Filter by video type', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>live_only</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Show only livestream videos', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>exclude_live</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Exclude livestream videos', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_views</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Display view count', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_date</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Display publish date', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_excerpt</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Display video excerpt/description', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>excerpt_length</code></td>
                                <td>120</td>
                                <td>50-500</td>
                                <td><?php echo esc_html__('Maximum excerpt characters', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_live_badge</code></td>
                                <td>yes</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Show LIVE badge on livestream videos', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>grid_gap</code></td>
                                <td>20px</td>
                                <td>CSS units</td>
                                <td><?php echo esc_html__('Spacing between grid items', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>image_ratio</code></td>
                                <td>16:9</td>
                                <td>16:9, 4:3, 1:1</td>
                                <td><?php echo esc_html__('Thumbnail aspect ratio', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>exclude</code></td>
                                <td></td>
                                <td>comma-separated IDs</td>
                                <td><?php echo esc_html__('Exclude specific video IDs', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>exclude_current</code></td>
                                <td>no</td>
                                <td>yes, no</td>
                                <td><?php echo esc_html__('Exclude current video (useful for related videos)', 'videohub360'); ?></td>
                            </tr>
                            <tr>
                                <td><code>include</code></td>
                                <td></td>
                                <td>comma-separated IDs</td>
                                <td><?php echo esc_html__('Include only specific video IDs', 'videohub360'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Usage Tips Section -->
            <div class="vh360-shortcode-category">
                <h2><?php echo esc_html__('Usage Tips', 'videohub360'); ?></h2>
                <div class="vh360-tips-grid">
                    <div class="vh360-tip-box">
                        <h4><?php echo esc_html__('🎯 Finding Taxonomy Slugs', 'videohub360'); ?></h4>
                        <p><?php echo esc_html__('To use category, series, or location filters, you need the taxonomy slug. Find it by navigating to the taxonomy page and checking the URL or hovering over the term name.', 'videohub360'); ?></p>
                    </div>
                    
                    <div class="vh360-tip-box">
                        <h4><?php echo esc_html__('📱 Responsive Design', 'videohub360'); ?></h4>
                        <p><?php echo esc_html__('Grid layouts automatically adjust to mobile devices. Fixed column counts (3, 4) become 2 columns on tablets and 1 column on phones.', 'videohub360'); ?></p>
                    </div>
                    
                    <div class="vh360-tip-box">
                        <h4><?php echo esc_html__('🎨 Combine Parameters', 'videohub360'); ?></h4>
                        <p><?php echo esc_html__('You can combine multiple parameters to create highly customized displays. For example, filter by category AND sort by views AND exclude current video.', 'videohub360'); ?></p>
                    </div>
                    
                    <div class="vh360-tip-box">
                        <h4><?php echo esc_html__('🔄 Related Videos', 'videohub360'); ?></h4>
                        <p><?php echo esc_html__('Use exclude_current="yes" in single video templates to show related videos without displaying the current video in the list.', 'videohub360'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render a builder parameter field
     */
    private function render_builder_param($name, $config) {
        $default = isset($config['default']) ? $config['default'] : '';
        $type = isset($config['type']) ? $config['type'] : 'text';
        ?>
        <div class="vh360-param-group">
            <label class="vh360-param-label">
                <?php echo esc_html($config['label']); ?>
                <?php if (isset($config['description']) && !empty($config['description'])): ?>
                    <span class="vh360-tooltip-trigger" data-tooltip="<?php echo esc_attr($config['description']); ?>">?</span>
                <?php endif; ?>
            </label>
            <?php if (isset($config['description'])): ?>
                <div class="vh360-param-description"><?php echo esc_html($config['description']); ?></div>
            <?php endif; ?>
            
            <?php if ($type === 'select'): ?>
                <select name="<?php echo esc_attr($name); ?>" class="vh360-param-input" data-default="<?php echo esc_attr($default); ?>">
                    <?php foreach ($config['options'] as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $default); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'number'): ?>
                <input type="number" 
                       name="<?php echo esc_attr($name); ?>" 
                       class="vh360-param-input" 
                       value="<?php echo esc_attr($default); ?>"
                       data-default="<?php echo esc_attr($default); ?>"
                       <?php if (isset($config['min'])): ?>min="<?php echo esc_attr($config['min']); ?>"<?php endif; ?>
                       <?php if (isset($config['max'])): ?>max="<?php echo esc_attr($config['max']); ?>"<?php endif; ?>
                />
            <?php else: ?>
                <input type="text" 
                       name="<?php echo esc_attr($name); ?>" 
                       class="vh360-param-input" 
                       value="<?php echo esc_attr($default); ?>"
                       data-default="<?php echo esc_attr($default); ?>"
                       placeholder="<?php echo isset($config['placeholder']) ? esc_attr($config['placeholder']) : ''; ?>"
                />
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render common use cases section
     */
    private function render_common_use_cases() {
        ?>
        <div class="vh360-use-cases">
            <h2><span class="vh360-section-icon">⚡</span><?php echo esc_html__('Common Use Cases & Quick Starts', 'videohub360'); ?></h2>
            <p><?php echo esc_html__('Get started quickly with these pre-configured shortcode examples for common scenarios. Just copy and paste!', 'videohub360'); ?></p>
            
            <div class="vh360-use-cases-grid">
                <!-- Use Case 1 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🔥 Homepage Featured Videos</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-beginner"><?php esc_html_e('Beginner', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Display your most viewed videos in an eye-catching 3-column grid. Perfect for your homepage or landing page.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos orderby="views" order="DESC" posts="6" columns="3"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos orderby="views" order="DESC" posts="6" columns="3"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 2 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>📂 Category Showcase</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-beginner"><?php esc_html_e('Beginner', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Show videos from a specific category with custom column layout. Great for category landing pages.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos category="tutorials" posts="9" columns="3" show_excerpt="yes"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos category="tutorials" posts="9" columns="3" show_excerpt="yes"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 3 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🔴 Latest Livestreams</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-beginner"><?php esc_html_e('Beginner', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Display only livestream videos in a dedicated section. Shows live badge automatically.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos live_only="yes" posts="6" columns="2"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos live_only="yes" posts="6" columns="2"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 4 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>📺 Series Playlist</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-intermediate"><?php esc_html_e('Intermediate', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Create a binge-worthy playlist from a video series. Sorted chronologically for best viewing experience.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos series="season-1" orderby="menu_order" order="ASC" posts="12" display="list"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos series="season-1" orderby="menu_order" order="ASC" posts="12" display="list"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 5 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🎬 Hero Video Background</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-intermediate"><?php esc_html_e('Intermediate', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Create an immersive hero section with auto-playing video background. Perfect for modern landing pages.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_hero video_type="mp4" video_url="https://videohub360.com/bg.mp4" video_autoplay="yes" video_loop="yes" video_controls="no" headline="Welcome to Our Platform" cta1_label="Get Started" cta1_url="/signup/"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero video_type="mp4" video_url="https://videohub360.com/bg.mp4" video_autoplay="yes" video_loop="yes" video_controls="no" headline="Welcome to Our Platform" cta1_label="Get Started" cta1_url="/signup/"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 6 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🎪 Hero Slider Carousel</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-advanced"><?php esc_html_e('Advanced', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Auto-rotating hero slider with featured content. Includes navigation arrows and dot indicators.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_hero mode="slider" autoplay="yes" autoplay_delay="5000" show_arrows="yes" show_dots="yes" transition_type="fade"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_hero mode="slider" autoplay="yes" autoplay_delay="5000" show_arrows="yes" show_dots="yes" transition_type="fade"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 7 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🔗 Related Videos Widget</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-intermediate"><?php esc_html_e('Intermediate', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Show related videos from the same category, excluding the current video. Ideal for single video pages.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos category="tutorials" exclude_current="yes" posts="6" columns="3" show_views="yes"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos category="tutorials" exclude_current="yes" posts="6" columns="3" show_views="yes"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 8 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>📱 Mobile-Optimized Grid</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-beginner"><?php esc_html_e('Beginner', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Responsive 2-column grid that adapts perfectly to mobile devices. Clean and fast loading.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos posts="8" columns="2" image_ratio="16:9" show_excerpt="no"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos posts="8" columns="2" image_ratio="16:9" show_excerpt="no"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 9 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>🎲 Random Video Discovery</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-beginner"><?php esc_html_e('Beginner', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Display random videos each time the page loads. Great for discovery and engagement.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos orderby="rand" posts="4" columns="4" exclude_live="yes"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos orderby="rand" posts="4" columns="4" exclude_live="yes"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
                
                <!-- Use Case 10 -->
                <div class="vh360-use-case">
                    <div class="vh360-use-case-header">
                        <div>
                            <h3>📝 Detailed List View</h3>
                            <span class="vh360-difficulty-badge vh360-difficulty-intermediate"><?php esc_html_e('Intermediate', 'videohub360'); ?></span>
                        </div>
                    </div>
                    <p><?php esc_html_e('Comprehensive list view with excerpts, metadata, and detailed information. Perfect for archives.', 'videohub360'); ?></p>
                    <code class="vh360-code-block">[videohub360_videos display="list" posts="10" show_excerpt="yes" excerpt_length="200" show_views="yes" show_date="yes"]</code>
                    <button type="button" class="button vh360-copy-btn" data-shortcode='[videohub360_videos display="list" posts="10" show_excerpt="yes" excerpt_length="200" show_views="yes" show_date="yes"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'videohub360'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Import/Export page - Display UI for importing and exporting videos
     */
    public function import_export_page() {
        // Get video count for export
        $video_count = wp_count_posts('videohub360');
        $total_videos = $video_count->publish + $video_count->draft + $video_count->pending + $video_count->private;
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Import/Export Videos', 'videohub360'); ?></h1>
            <p><?php echo esc_html__('Transfer VideoHub360 videos between WordPress sites using JSON export/import.', 'videohub360'); ?></p>
            
            <style>
                .vh360-import-export-section {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                }
                .vh360-import-export-section h2 {
                    margin-top: 0;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #eee;
                }
                .vh360-form-field {
                    margin: 15px 0;
                }
                .vh360-form-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                .vh360-results {
                    background: #f0f0f1;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 15px;
                    margin-top: 20px;
                }
                .vh360-results.success {
                    background: #d4edda;
                    border-color: #c3e6cb;
                }
                .vh360-results.error {
                    background: #f8d7da;
                    border-color: #f5c6cb;
                }
                .vh360-results ul {
                    margin: 10px 0;
                    padding-left: 20px;
                }
                .vh360-info-box {
                    background: #e7f3fe;
                    border-left: 4px solid #0073aa;
                    padding: 12px;
                    margin: 15px 0;
                }
            </style>
            
            <!-- Export Section -->
            <div class="vh360-import-export-section">
                <h2>📤 <?php echo esc_html__('Export Videos', 'videohub360'); ?></h2>
                <p><?php echo esc_html__('Export all your videos to a JSON file that can be imported into another WordPress site.', 'videohub360'); ?></p>
                
                <div class="vh360-info-box">
                    <p><strong><?php echo esc_html__('What gets exported:', 'videohub360'); ?></strong></p>
                    <ul style="margin: 10px 0;">
                        <li><?php echo esc_html__('All post content (title, content, excerpt, status)', 'videohub360'); ?></li>
                        <li><?php echo esc_html__('Video URLs (main video, ads, mid-roll, post-roll)', 'videohub360'); ?></li>
                        <li><?php echo esc_html__('All meta fields (view counts, livestream settings, quality settings)', 'videohub360'); ?></li>
                        <li><?php echo esc_html__('Taxonomy terms (categories, series, locations, tags)', 'videohub360'); ?></li>
                        <li><?php echo esc_html__('Featured image URL (reference only, not the file itself)', 'videohub360'); ?></li>
                    </ul>
                    <p><strong><?php echo esc_html__('Note:', 'videohub360'); ?></strong> <?php echo esc_html__('Chat messages and moderation data are not exported.', 'videohub360'); ?></p>
                </div>
                
                <div class="vh360-form-field">
                    <p>
                        <strong><?php echo esc_html__('Total videos available:', 'videohub360'); ?></strong> 
                        <?php echo esc_html(number_format($total_videos)); ?>
                    </p>
                </div>
                
                <button type="button" id="vh360-export-all-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                    <?php echo esc_html__('Export All Videos', 'videohub360'); ?>
                </button>
                
                <div id="vh360-export-results" class="vh360-results" style="display:none;"></div>
            </div>
            
            <!-- Import Section -->
            <div class="vh360-import-export-section">
                <h2>📥 <?php echo esc_html__('Import Videos', 'videohub360'); ?></h2>
                <p><?php echo esc_html__('Import videos from a JSON export file created by VideoHub360.', 'videohub360'); ?></p>
                
                <form id="vh360-import-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('vh360_import_export_nonce', 'vh360_import_nonce'); ?>
                    
                    <div class="vh360-form-field">
                        <label for="vh360-import-file">
                            <?php echo esc_html__('Select JSON File:', 'videohub360'); ?>
                        </label>
                        <input type="file" id="vh360-import-file" name="import_file" accept=".json" required />
                        <p class="description">
                            <?php echo esc_html__('Choose a JSON file exported from VideoHub360', 'videohub360'); ?>
                        </p>
                    </div>
                    
                    <div class="vh360-form-field">
                        <label><?php echo esc_html__('Duplicate Handling:', 'videohub360'); ?></label>
                        <p>
                            <label>
                                <input type="radio" name="duplicate_action" value="skip" checked />
                                <?php echo esc_html__('Skip duplicates', 'videohub360'); ?>
                            </label>
                            <span class="description" style="display:block; margin-left: 25px;">
                                <?php echo esc_html__('Videos with existing titles or slugs will be skipped', 'videohub360'); ?>
                            </span>
                        </p>
                        <p>
                            <label>
                                <input type="radio" name="duplicate_action" value="update" />
                                <?php echo esc_html__('Update existing videos', 'videohub360'); ?>
                            </label>
                            <span class="description" style="display:block; margin-left: 25px;">
                                <?php echo esc_html__('Overwrite existing videos with the same slug', 'videohub360'); ?>
                            </span>
                        </p>
                        <p>
                            <label>
                                <input type="radio" name="duplicate_action" value="create_new" />
                                <?php echo esc_html__('Create new with modified slug', 'videohub360'); ?>
                            </label>
                            <span class="description" style="display:block; margin-left: 25px;">
                                <?php echo esc_html__('Import all videos, appending numbers to duplicate slugs', 'videohub360'); ?>
                            </span>
                        </p>
                    </div>
                    
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                        <?php echo esc_html__('Import Videos', 'videohub360'); ?>
                    </button>
                </form>
                
                <div id="vh360-import-results" class="vh360-results" style="display:none;"></div>
            </div>
            
            <!-- Bulk Export Note -->
            <div class="vh360-info-box">
                <h3 style="margin-top: 0;"><?php echo esc_html__('Tip: Export Selected Videos', 'videohub360'); ?></h3>
                <p>
                    <?php echo esc_html__('To export specific videos instead of all videos, go to the Videos list page, select the videos you want to export, and choose "Export Selected" from the bulk actions dropdown.', 'videohub360'); ?>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Export all videos
            $('#vh360-export-all-btn').on('click', function() {
                var $btn = $(this);
                var $results = $('#vh360-export-results');
                
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Exporting...', 'videohub360')); ?>');
                $results.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vh360_export_all_videos',
                        nonce: '<?php echo esc_js(wp_create_nonce('vh360_import_export_nonce')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create download
                            var blob = new Blob([response.data.json], {type: 'application/json'});
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = 'videohub360-export-' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.json';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                            
                            $results.removeClass('error').addClass('success')
                                .html('<p><strong><?php echo esc_js(__('Success!', 'videohub360')); ?></strong> ' + response.data.count + ' <?php echo esc_js(__('videos exported successfully. Download should start automatically.', 'videohub360')); ?></p>')
                                .show();
                        } else {
                            $results.removeClass('success').addClass('error')
                                .html('<p><strong><?php echo esc_js(__('Error:', 'videohub360')); ?></strong> ' + (response.data.message || '<?php echo esc_js(__('Export failed', 'videohub360')); ?>') + '</p>')
                                .show();
                        }
                    },
                    error: function() {
                        $results.removeClass('success').addClass('error')
                            .html('<p><strong><?php echo esc_js(__('Error:', 'videohub360')); ?></strong> <?php echo esc_js(__('Failed to communicate with server', 'videohub360')); ?></p>')
                            .show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top: 3px;"></span> <?php echo esc_js(__('Export All Videos', 'videohub360')); ?>');
                    }
                });
            });
            
            // Import videos
            $('#vh360-import-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'vh360_import_videos');
                formData.append('nonce', '<?php echo esc_js(wp_create_nonce('vh360_import_export_nonce')); ?>');
                
                var $results = $('#vh360-import-results');
                var $btn = $(this).find('button[type="submit"]');
                
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Importing...', 'videohub360')); ?>');
                $results.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<h3><?php echo esc_js(__('Import Complete!', 'videohub360')); ?></h3>';
                            html += '<p><strong><?php echo esc_js(__('Imported:', 'videohub360')); ?></strong> ' + data.imported + ' <?php echo esc_js(__('videos', 'videohub360')); ?></p>';
                            
                            if (data.updated > 0) {
                                html += '<p><strong><?php echo esc_js(__('Updated:', 'videohub360')); ?></strong> ' + data.updated + ' <?php echo esc_js(__('videos', 'videohub360')); ?></p>';
                            }
                            
                            if (data.skipped > 0) {
                                html += '<p><strong><?php echo esc_js(__('Skipped:', 'videohub360')); ?></strong> ' + data.skipped + ' <?php echo esc_js(__('videos', 'videohub360')); ?></p>';
                            }
                            
                            if (data.warnings && data.warnings.length > 0) {
                                html += '<p><strong><?php echo esc_js(__('Warnings:', 'videohub360')); ?></strong></p><ul>';
                                data.warnings.forEach(function(warning) {
                                    html += '<li>' + warning + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            if (data.errors && data.errors.length > 0) {
                                html += '<p><strong><?php echo esc_js(__('Errors:', 'videohub360')); ?></strong></p><ul>';
                                data.errors.forEach(function(error) {
                                    html += '<li>' + error + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            $results.removeClass('error').addClass('success').html(html).show();
                            $('#vh360-import-form')[0].reset();
                        } else {
                            var html = '<p><strong><?php echo esc_js(__('Import Failed', 'videohub360')); ?></strong></p>';
                            if (response.data && response.data.error) {
                                html += '<p>' + response.data.error + '</p>';
                            } else if (response.data && response.data.message) {
                                html += '<p>' + response.data.message + '</p>';
                            }
                            
                            if (response.data && response.data.errors && response.data.errors.length > 0) {
                                html += '<ul>';
                                response.data.errors.forEach(function(error) {
                                    html += '<li>' + error + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            $results.removeClass('success').addClass('error').html(html).show();
                        }
                    },
                    error: function() {
                        $results.removeClass('success').addClass('error')
                            .html('<p><strong><?php echo esc_js(__('Error:', 'videohub360')); ?></strong> <?php echo esc_js(__('Failed to communicate with server', 'videohub360')); ?></p>')
                            .show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> <?php echo esc_js(__('Import Videos', 'videohub360')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    /**
     * Check whether the local site has an active VideoHub360 license.
     *
     * The license client stores status in the `videohub360_license_data` option.
     * We consider the license active only when status === 'valid'.
     *
     * @return bool
     */
    private function is_license_valid() {
        $data = get_option( 'videohub360_license_data', array() );
        return ( isset( $data['status'] ) && 'valid' === $data['status'] );
    }

    /**
     * Soft lock: prevent creating NEW Video posts when the license is inactive.
     * This only blocks the "Add New" screen for the videohub360 post type.
     */
    public function maybe_block_new_video_creation() {
        if ( ! is_admin() ) {
            return;
        }

        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'post';
        if ( 'videohub360' !== $post_type ) {
            return;
        }

        if ( $this->is_license_valid() ) {
            return;
        }

        // Redirect back to the list table with a notice flag.
        $redirect = add_query_arg(
            array( 'vh360_license_required' => '1' ),
            admin_url( 'edit.php?post_type=videohub360' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Show an admin notice when video creation is blocked due to missing license.
     */
    public function license_required_notice() {
        if ( ! is_admin() ) {
            return;
        }

        if ( empty( $_GET['vh360_license_required'] ) ) {
            return;
        }

        // Only show on the videohub360 list screen.
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && isset( $screen->post_type ) && 'videohub360' !== $screen->post_type ) {
            return;
        }

        if ( $this->is_license_valid() ) {
            return;
        }

        $license_url = admin_url( 'admin.php?page=videohub360-license' );

        echo '<div class="notice notice-error"><p>'
            . esc_html__( 'Creating new videos is locked until your VideoHub360 license is activated.', 'videohub360' )
            . ' <a href="' . esc_url( $license_url ) . '">'
            . esc_html__( 'Activate License', 'videohub360' )
            . '</a>'
            . '</p></div>';
    }

    /**
     * Enrollment Backfill admin page.
     *
     * Allows admins to scan active course entitlements and create missing
     * enrollment rows in batches.
     */
    public function enrollment_backfill_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'videohub360' ) );
        }
        $nonce = wp_create_nonce( 'vh360_enrollment_backfill' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Enrollment Backfill', 'videohub360' ); ?></h1>
            <p><?php esc_html_e( 'Scan active course entitlements and create missing enrollment rows. Process runs in batches of 50 to avoid timeouts.', 'videohub360' ); ?></p>

            <div id="vh360-backfill-status" style="margin: 1rem 0; padding: 0.75rem 1rem; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;"></div>

            <p>
                <button id="vh360-backfill-start" class="button button-primary">
                    <?php esc_html_e( 'Start Backfill', 'videohub360' ); ?>
                </button>
            </p>

            <script>
            (function() {
                var btn    = document.getElementById('vh360-backfill-start');
                var status = document.getElementById('vh360-backfill-status');
                var nonce  = <?php echo wp_json_encode( $nonce ); ?>;
                var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

                var totals = { created: 0, skipped: 0 };

                function setStatus(msg) {
                    status.style.display = 'block';
                    status.textContent = msg;
                }

                function runBatch(offset) {
                    setStatus('Processing… (offset ' + offset + ')');
                    var data = new FormData();
                    data.append('action', 'vh360_enrollment_backfill');
                    data.append('nonce', nonce);
                    data.append('batch_offset', offset);

                    fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (!res.success) {
                                setStatus('Error: ' + (res.data && res.data.message ? res.data.message : 'Unknown error'));
                                btn.disabled = false;
                                return;
                            }
                            totals.created += res.data.created;
                            totals.skipped += res.data.skipped;
                            if (res.data.next_offset !== null) {
                                runBatch(res.data.next_offset);
                            } else {
                                setStatus('Done. Created: ' + totals.created + ', Skipped: ' + totals.skipped + '.');
                                btn.disabled = false;
                            }
                        })
                        .catch(function(err) {
                            setStatus('Request failed: ' + err);
                            btn.disabled = false;
                        });
                }

                btn.addEventListener('click', function() {
                    btn.disabled = true;
                    totals = { created: 0, skipped: 0 };
                    runBatch(0);
                });
            }());
            </script>
        </div>
        <?php
    }


}
