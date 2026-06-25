<?php
/**
 * Theme Admin Panel
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class VH360_Theme_Admin {
    
    /**
     * Minimum number of columns for gallery layouts.
     */
    const GALLERY_MIN_COLUMNS = 1;
    
    /**
     * Maximum number of columns for gallery layouts.
     */
    const GALLERY_MAX_COLUMNS = 6;
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    

    /**
     * Minimal fallback keys for dashboard subscription card styling when the memberships plugin is unavailable.
     *
     * Real color defaults live in VH360_Membership_Subscription_Management::get_dashboard_card_style_defaults().
     *
     * @return array
     */
    public static function get_membership_dashboard_card_style_fallbacks() {
        return array_fill_keys(array(
            'subscription_card_bg_color',
            'subscription_card_border_color',
            'subscription_card_title_color',
            'subscription_card_price_color',
            'subscription_card_text_color',
            'subscription_card_button_bg_color',
            'subscription_card_button_text_color',
            'dashboard_current_plan_button_bg_color',
            'dashboard_current_plan_button_text_color',
            'dashboard_current_plan_button_border_color',
        ), '');
    }

    /**
     * Minimal fallback keys for pricing plan card styling when the memberships plugin is unavailable.
     *
     * Real color defaults live in VH360_Membership_Plans::get_pricing_style_defaults().
     *
     * @return array
     */
    public static function get_membership_pricing_card_style_fallbacks() {
        return array_fill_keys(array(
            'pricing_card_background_color',
            'pricing_card_border_color',
            'pricing_card_text_color',
            'pricing_card_title_color',
            'pricing_card_price_color',
            'pricing_card_description_color',
            'pricing_card_feature_text_color',
            'pricing_card_button_background_color',
            'pricing_card_button_text_color',
            'pricing_card_button_hover_background_color',
            'pricing_card_featured_border_color',
            'pricing_card_featured_badge_background_color',
            'pricing_card_featured_badge_text_color',
            'pricing_toggle_active_background_color',
            'pricing_toggle_active_text_color',
            'pricing_toggle_inactive_background_color',
            'pricing_toggle_inactive_text_color',
        ), '');
    }


    /**
     * Get dashboard subscription card style defaults without assuming plugin helper availability.
     *
     * @return array
     */
    public static function get_membership_dashboard_card_style_defaults() {
        if (
            class_exists('VH360_Membership_Subscription_Management')
            && method_exists('VH360_Membership_Subscription_Management', 'get_dashboard_card_style_defaults')
        ) {
            return VH360_Membership_Subscription_Management::get_dashboard_card_style_defaults();
        }

        return self::get_membership_dashboard_card_style_fallbacks();
    }

    /**
     * Get pricing plan card style defaults without assuming plugin helper availability.
     *
     * @return array
     */
    public static function get_membership_pricing_card_style_defaults() {
        if (
            class_exists('VH360_Membership_Plans')
            && method_exists('VH360_Membership_Plans', 'get_pricing_style_defaults')
        ) {
            return VH360_Membership_Plans::get_pricing_style_defaults();
        }

        return self::get_membership_pricing_card_style_fallbacks();
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'add_advanced_submenu'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_init', array($this, 'sync_permissions_capabilities'));
        add_action('wp_ajax_vh360_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_vh360_export_customizer', array($this, 'ajax_export_customizer'));
        add_action('wp_ajax_vh360_import_customizer', array($this, 'ajax_import_customizer'));
        add_action('wp_ajax_vh360_clear_cache', array($this, 'ajax_clear_cache'));
        
        // Notification admin AJAX handlers
        add_action('wp_ajax_vh360_manual_notification_cleanup', array($this, 'ajax_manual_notification_cleanup'));
        add_action('wp_ajax_vh360_reset_all_notifications', array($this, 'ajax_reset_all_notifications'));
        
        // Business/Professional approval AJAX handlers
        add_action('wp_ajax_vh360_approve_professional', array($this, 'ajax_approve_professional'));
        add_action('wp_ajax_vh360_reject_professional', array($this, 'ajax_reject_professional'));
        
        // Custom profile fields AJAX handlers
        add_action('wp_ajax_vh360_save_custom_profile_field', array($this, 'ajax_save_custom_profile_field'));
        add_action('wp_ajax_vh360_delete_custom_profile_field', array($this, 'ajax_delete_custom_profile_field'));
        add_action('wp_ajax_vh360_save_builtin_field_settings', array($this, 'ajax_save_builtin_field_settings'));

        // CSV export handler
        add_action('admin_post_vh360_export_user_profiles', array($this, 'export_user_profiles_csv'));
    }
    
    /**
     * Add admin menu and submenus
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('VH360 Theme Dashboard', 'videohub360-theme'),
            __('VH360 Theme', 'videohub360-theme'),
            'manage_options',
            'vh360-theme',
            array($this, 'render_dashboard'),
            'dashicons-admin-appearance',
            59
        );
        
        // Dashboard submenu (same as parent)
        add_submenu_page(
            'vh360-theme',
            __('Theme Dashboard', 'videohub360-theme'),
            __('Dashboard', 'videohub360-theme'),
            'manage_options',
            'vh360-theme',
            array($this, 'render_dashboard')
        );
        
        // Appearance submenu
        add_submenu_page(
            'vh360-theme',
            __('Appearance Settings', 'videohub360-theme'),
            __('Appearance', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-appearance',
            array($this, 'render_appearance')
        );
        
        // Profile Settings submenu
        add_submenu_page(
            'vh360-theme',
            __('Profile Settings', 'videohub360-theme'),
            __('Profile Settings', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-profiles',
            array($this, 'render_profiles')
        );

        // Profile Data viewer submenu
        add_submenu_page(
            'vh360-theme',
            __('Profile Data', 'videohub360-theme'),
            __('Profile Data', 'videohub360-theme'),
            'manage_options',
            'vh360-profile-data',
            array($this, 'render_profile_data_page')
        );

        // Activity Feed submenu
        add_submenu_page(
            'vh360-theme',
            __('Activity Feed Settings', 'videohub360-theme'),
            __('Activity Feed', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-activity',
            array($this, 'render_activity')
        );
        
        // Members submenu
        add_submenu_page(
            'vh360-theme',
            __('Members Directory Settings', 'videohub360-theme'),
            __('Members', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-members',
            array($this, 'render_members')
        );
        
        // Page Templates submenu
        add_submenu_page(
            'vh360-theme',
            __('Page Templates Guide', 'videohub360-theme'),
            __('Page Templates', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-templates',
            array($this, 'render_templates')
        );
        
        // Gallery submenu
        add_submenu_page(
            'vh360-theme',
            __('Gallery Settings', 'videohub360-theme'),
            __('Gallery', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-gallery',
            array($this, 'render_gallery')
        );
        
        // Notifications submenu
        add_submenu_page(
            'vh360-theme',
            __('Notification Settings', 'videohub360-theme'),
            __('Notifications', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-notifications',
            array($this, 'render_notifications')
        );
        
        // Direct Messages submenu
        add_submenu_page(
            'vh360-theme',
            __('Direct Messages Settings', 'videohub360-theme'),
            __('Messages', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-messages',
            array($this, 'render_messages')
        );
        
        // Permissions submenu
        add_submenu_page(
            'vh360-theme',
            __('Permissions Settings', 'videohub360-theme'),
            __('Permissions', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-permissions',
            array($this, 'render_permissions')
        );
        
        // Template Visibility submenu
        add_submenu_page(
            'vh360-theme',
            __('Template Visibility Settings', 'videohub360-theme'),
            __('Template Visibility', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-access',
            array($this, 'render_access')
        );
        
        // Business submenu
        add_submenu_page(
            'vh360-theme',
            __('Business Settings', 'videohub360-theme'),
            __('Business', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-business',
            array($this, 'render_business')
        );
        
        // Invites submenu
        add_submenu_page(
            'vh360-theme',
            __('Invite Management', 'videohub360-theme'),
            __('Invites', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-invites',
            array($this, 'render_invites')
        );

        // Membership submenu
        add_submenu_page(
            'vh360-theme',
            __('Paid Membership Settings', 'videohub360-theme'),
            __('Paid Memberships', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-memberships',
            array($this, 'render_memberships')
        );
        
    }

    /**
     * Add Advanced submenu after other VH360 Theme submenus.
     */
    public function add_advanced_submenu() {
        add_submenu_page(
            'vh360-theme',
            __('Advanced Settings', 'videohub360-theme'),
            __('Advanced', 'videohub360-theme'),
            'manage_options',
            'vh360-theme-advanced',
            array($this, 'render_advanced')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on theme admin pages
        if (strpos($hook, 'vh360-theme') === false) {
            return;
        }
        
        // Enqueue color picker on membership settings page
        if (strpos($hook, 'memberships') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'vh360-theme-admin',
            VH360_THEME_URI . '/assets/admin/css/theme-admin.css',
            array(),
            VH360_THEME_VERSION
        );
        
        // Enqueue admin JS
        wp_enqueue_script(
            'vh360-theme-admin',
            VH360_THEME_URI . '/assets/admin/js/theme-admin.js',
            array('jquery'),
            VH360_THEME_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vh360-theme-admin', 'vh360Admin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vh360_admin_nonce'),
            'importNonce' => wp_create_nonce('vh360_import_settings'),
            'customizerImportNonce' => wp_create_nonce('vh360_import_customizer'),
            'confirmReset' => __('Are you sure you want to reset all settings? This action cannot be undone.', 'videohub360-theme'),
            'confirmClearCache' => __('Are you sure you want to clear all theme cache?', 'videohub360-theme'),
            'confirmClearActivities' => __('Are you sure you want to clear old activities? This action cannot be undone.', 'videohub360-theme'),
        ));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Appearance settings
        register_setting('vh360_appearance_settings', 'vh360_appearance_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_appearance_settings'),
            'default' => array(
                'enable_profiles' => true,
                'enable_bulletins' => true,
                'enable_activity' => true,
                'enable_members' => true,
                'enable_user_menu' => true,
                'custom_css' => '',
                'enable_minification' => false,
                'enable_lazy_loading' => true,
            ),
        ));
        
        // Invite settings
        register_setting('vh360_invite_settings', 'vh360_invite_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_invite_settings'),
            'default' => array(
                'invite_only_registration' => 0,
                'expiration_days' => 14,
                'creator_role' => 'members',
                'required_registration_forms' => array('general', 'client', 'professional', 'instructor'),
            ),
        ));

        // Profile settings
        register_setting('vh360_profile_settings', 'vh360_profile_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_profile_settings'),
            'default' => array(
                'enable_profiles' => true,
                'show_avatar' => true,
                'show_cover' => true,
                'show_social' => true,
                'show_stats' => true,
                'show_header_follow_button' => true,
                'social_platforms' => array('website', 'twitter', 'facebook', 'youtube', 'instagram'),
                'avatar_max_size' => 2,
                'cover_max_size' => 5,
                'enable_avatar_cropper' => true,
                'avatar_output_size' => 300,
                'avatar_min_width' => 300,
                'avatar_min_height' => 300,
                'avatar_quality' => 90,
                'avatar_allowed_types' => array(
                    'image/jpeg',
                    'image/pjpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/heic',
                    'image/heif',
                    'image/heic-sequence',
                    'image/heif-sequence',
                ),
            ),
        ));
        
        // Activity settings
        register_setting('vh360_activity_settings', 'vh360_activity_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_activity_settings'),
            'default' => array(
                'enable_tracking' => true,
                'track_types' => array('video_upload', 'new_member', 'profile_update', 'milestone'),
                'retention_days' => 30,
                'per_page' => 20,
            ),
        ));
        
        // Community Upload settings
        register_setting('vh360_activity_settings', 'vh360_community_uploads_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_community_uploads_settings'),
            'default' => array(
                'enable_photos' => true,
                'enable_videos' => false,
                'photo_max_size' => 5,
                'video_max_size' => 50,
                'allowed_video_formats' => array('mp4'),
            ),
        ));
        
        // Members settings
        register_setting('vh360_members_settings', 'vh360_members_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_members_settings'),
            'default' => vh360_get_default_members_directory_options(),
        ));
        
        // Gallery settings
        register_setting('vh360_gallery_settings', 'vh360_gallery_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_gallery_settings'),
            'default' => array(
                'enable_galleries' => true,
                'enable_frontend_upload' => true,
                'max_images_per_gallery' => 50,
                'max_image_size' => 5,
                'allowed_image_types' => array('jpg', 'jpeg', 'png', 'gif', 'webp'),
                'default_layout' => 'grid',
                'default_columns' => 3,
                'enable_lightbox' => true,
                'enable_comments' => true,
                'galleries_per_page' => 12,
            ),
        ));
        
        // Notification settings
        register_setting('vh360_notification_settings', 'vh360_notification_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_notification_settings'),
            'default' => array(
                'enable_system' => true,
                'polling_interval' => 30,
                'max_per_user' => 100,
                'retention_days' => 30,
                'enable_types' => array(
                    'follow' => true,
                    'like' => true,
                    'comment' => true,
                    'reply' => true,
                    'mention' => true,
                    'share' => true,
                ),
                'enable_caching' => true,
                'cleanup_schedule' => 'daily',
            ),
        ));
        
        // Direct messages settings
        register_setting('vh360_dm_settings', 'vh360_dm_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_dm_settings'),
            'default' => array(
                'enable_dm' => true,
                'require_mutual_follow' => false,
                'char_limit' => 1000,
                'retention_days' => 0,
            ),
        ));
        
        // Advanced settings
        register_setting('vh360_advanced_settings', 'vh360_advanced_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_advanced_settings'),
            'default' => array(
                'debug_mode' => false,
                'enable_logging' => false,
                'show_deprecated' => false,
                'transient_expiration' => 3600,
            ),
        ));
        
        // Permissions settings
        register_setting('vh360_permissions_settings', 'vh360_permissions_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_permissions_settings'),
            'default' => array(
                'create_posts_roles' => array('administrator'),
                'create_videos_roles' => array('administrator', 'vh360_instructor'),
                'host_live_roles' => array('administrator'),
            ),
        ));
        
        // Access/visibility settings
        register_setting('vh360_access_settings', 'vh360_access_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_access_settings'),
            'default' => array(
                'dashboard'         => 1,
                'profile_edit'      => 1,
                'members_directory' => 0,
                'activity_feed'     => 1,
                'author_profiles'   => 0,
            ),
        ));
        
        // Business settings
        register_setting('vh360_business_settings', 'vh360_business_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_business_settings'),
            'default' => array(
                'require_professional_approval' => false,
            ),
        ));
        
        // Membership settings
        $dashboard_card_style_defaults = self::get_membership_dashboard_card_style_defaults();
        $pricing_card_style_defaults = self::get_membership_pricing_card_style_defaults();
        register_setting('vh360_membership_settings', 'vh360_membership_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_membership_settings'),
            'default' => array_merge(array(
                'enable_memberships' => true,
                'pricing_page_url' => '',
                'support_url' => '',
                'contact_url' => '',
                'course_purchase_destination' => 'product_page',
                'login_required' => true,
                'locked_message' => '',
                'reminder_days' => 7,
                'grace_period_days' => 0,
                // Feature gating toggles (default to 0 = not gated)
                'gate_live_rooms' => 0,
                'gate_create_videos' => 0,
                'gate_create_posts' => 0,
                'gate_create_events' => 0,
                'gate_create_bulletins' => 0,
                'gate_create_galleries' => 0,
                'gate_direct_messages' => 0,
                'gate_activity_feed' => 0,
                'gate_members_directory' => 0,
                'gate_appointments' => 0,
                'gate_push_notifications' => 0,
                'subscription_card_button_label' => '',
            ), $dashboard_card_style_defaults, $pricing_card_style_defaults),
        ));
        
        // Stripe / recurring billing settings
        register_setting('vh360_stripe_settings', 'vh360_stripe_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_stripe_settings'),
            'default' => array(
                'enable_recurring' => 0,
                'test_mode' => 1,
                'publishable_key' => '',
                'secret_key' => '',
                'test_publishable_key' => '',
                'test_secret_key' => '',
                'webhook_secret' => '',
                'enable_portal' => 0,
                'cancellation_behavior' => 'at_period_end',
            ),
        ));
        
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_POST['vh360_admin_action']) || !current_user_can('manage_options')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['vh360_admin_action']);
        
        // Verify nonce
        if (!isset($_POST['vh360_admin_nonce']) || !wp_verify_nonce($_POST['vh360_admin_nonce'], 'vh360_admin_action')) {
            wp_die(__('Security check failed', 'videohub360-theme'));
        }
        
        switch ($action) {
            case 'clear_cache':
                $this->clear_theme_cache();
                wp_redirect(add_query_arg('cache_cleared', '1', wp_get_referer()));
                exit;
                
            case 'clear_activities':
                $this->clear_old_activities();
                wp_redirect(add_query_arg('activities_cleared', '1', wp_get_referer()));
                exit;

            case 'repair_course_ownership':
                $repair_counts = $this->repair_course_ownership_metadata();
                wp_redirect(add_query_arg(array(
                    'course_ownership_repaired' => '1',
                    'repaired'                  => $repair_counts['repaired'],
                    'already_valid'             => $repair_counts['already_valid'],
                    'skipped'                   => $repair_counts['skipped'],
                ), wp_get_referer()));
                exit;
                
            case 'reset_settings':
                $this->reset_all_settings();
                wp_redirect(add_query_arg('settings_reset', '1', wp_get_referer()));
                exit;
        }
    }
    
    /**
     * Clear theme cache
     */
    private function clear_theme_cache() {
        global $wpdb;
        
        // Delete all vh360 transients using prepared statement
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_vh360_') . '%',
                $wpdb->esc_like('_transient_timeout_vh360_') . '%'
            )
        );
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Clear old activities
     */
    private function clear_old_activities() {
        $options = get_option('vh360_activity_options', array());
        $retention_days = isset($options['retention_days']) ? absint($options['retention_days']) : 30;
        
        $activities = get_option('vh360_activity_feed', array());
        if (!is_array($activities)) {
            return;
        }
        
        $cutoff_time = time() - ($retention_days * DAY_IN_SECONDS);
        
        $filtered_activities = array_filter($activities, function($activity) use ($cutoff_time) {
            return isset($activity['timestamp']) && $activity['timestamp'] >= $cutoff_time;
        });
        
        update_option('vh360_activity_feed', $filtered_activities);
    }
    
    /**
     * Reset all settings
     */
    private function reset_all_settings() {
        delete_option('vh360_appearance_options');
        delete_option('vh360_profile_options');
        delete_option('vh360_activity_options');
        delete_option('vh360_members_options');
        delete_option('vh360_advanced_options');
        delete_option('vh360_access_options');
        delete_option('vh360_custom_profile_fields');
        delete_option('vh360_builtin_field_settings');
    }
    
    /**
     * Repair missing explicit course owner metadata for legacy/imported courses.
     *
     * @return array Repair counts.
     */
    private function repair_course_ownership_metadata() {
        $counts = array(
            'repaired'      => 0,
            'already_valid' => 0,
            'skipped'       => 0,
        );

        if ( ! taxonomy_exists( 'videohub360_series' ) ) {
            return $counts;
        }

        $courses = get_terms(array(
            'taxonomy'   => 'videohub360_series',
            'hide_empty' => false,
        ));

        if ( is_wp_error( $courses ) || empty( $courses ) ) {
            return $counts;
        }

        foreach ( $courses as $course ) {
            $term_id  = absint( $course->term_id );
            $owner_id = (int) get_term_meta( $term_id, '_vh360_course_owner_user_id', true );

            if ( $this->user_is_eligible_course_owner( $owner_id ) ) {
                $counts['already_valid']++;
                continue;
            }

            $fallback_owner_id = 0;
            $instructor_id     = (int) get_term_meta( $term_id, '_vh360_course_instructor_user_id', true );

            if ( $this->user_is_eligible_course_owner( $instructor_id ) ) {
                $fallback_owner_id = $instructor_id;
            } elseif ( function_exists( 'videohub360_get_first_course_lesson_author_id' ) ) {
                $lesson_author_id = videohub360_get_first_course_lesson_author_id( $term_id );
                if ( $this->user_is_eligible_course_owner( $lesson_author_id ) ) {
                    $fallback_owner_id = $lesson_author_id;
                }
            }

            if ( $fallback_owner_id ) {
                update_term_meta( $term_id, '_vh360_course_owner_user_id', $fallback_owner_id );
                $counts['repaired']++;
                continue;
            }

            $counts['skipped']++;
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( sprintf( 'VH360 course ownership repair skipped term #%d (%s): no eligible instructor or lesson author found.', $term_id, $course->name ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }

        return $counts;
    }

    /**
     * Check whether a user is eligible to be assigned as a course owner.
     *
     * @param int $user_id User ID.
     * @return bool
     */
    private function user_is_eligible_course_owner( $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return false;
        }

        if ( function_exists( 'vh360_user_is_eligible_course_owner' ) ) {
            return vh360_user_is_eligible_course_owner( $user_id );
        }

        return (bool) get_userdata( $user_id );
    }

    /**
     * Sanitize appearance settings
     */
    public function sanitize_appearance_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields - explicitly set to false if not present (unchecked)
        $sanitized['enable_profiles'] = isset($input['enable_profiles']) ? (bool) $input['enable_profiles'] : false;
        $sanitized['enable_bulletins'] = isset($input['enable_bulletins']) ? (bool) $input['enable_bulletins'] : false;
        $sanitized['enable_activity'] = isset($input['enable_activity']) ? (bool) $input['enable_activity'] : false;
        $sanitized['enable_members'] = isset($input['enable_members']) ? (bool) $input['enable_members'] : false;
        $sanitized['enable_user_menu'] = isset($input['enable_user_menu']) ? (bool) $input['enable_user_menu'] : false;
        $sanitized['enable_minification'] = isset($input['enable_minification']) ? (bool) $input['enable_minification'] : false;
        $sanitized['enable_lazy_loading'] = isset($input['enable_lazy_loading']) ? (bool) $input['enable_lazy_loading'] : false;
        
        // Text field
        $sanitized['custom_css'] = isset($input['custom_css']) ? wp_strip_all_tags($input['custom_css']) : '';
        
        return $sanitized;
    }
    
    /**
     * Sanitize profile settings
     */
    public function sanitize_profile_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields - explicitly set to false if not present (unchecked)
        $sanitized['enable_profiles'] = isset($input['enable_profiles']) ? (bool) $input['enable_profiles'] : false;
        $sanitized['show_avatar'] = isset($input['show_avatar']) ? (bool) $input['show_avatar'] : false;
        $sanitized['show_cover'] = isset($input['show_cover']) ? (bool) $input['show_cover'] : false;
        $sanitized['show_social'] = isset($input['show_social']) ? (bool) $input['show_social'] : false;
        $sanitized['show_stats'] = isset($input['show_stats']) ? (bool) $input['show_stats'] : false;
        $sanitized['show_header_follow_button'] = isset($input['show_header_follow_button']) ? (bool) $input['show_header_follow_button'] : false;
        $sanitized['enable_avatar_cropper'] = isset($input['enable_avatar_cropper']) ? (bool) $input['enable_avatar_cropper'] : false;
        
        // Array field for social platforms - default to empty array if not set
        $sanitized['social_platforms'] = $this->sanitize_array_input(
            isset($input['social_platforms']) ? $input['social_platforms'] : null
        );
        
        // Array field for avatar allowed types.
        // Always merge with the full default list so saving the settings page
        // (which has no visible field for this) never blanks out the allowed types.
        $default_avatar_allowed_types = array(
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/heic',
            'image/heif',
            'image/heic-sequence',
            'image/heif-sequence',
        );

        $sanitized['avatar_allowed_types'] = isset($input['avatar_allowed_types']) && is_array($input['avatar_allowed_types'])
            ? array_values(array_unique(array_merge(
                $default_avatar_allowed_types,
                $this->sanitize_array_input($input['avatar_allowed_types'])
            )))
            : $default_avatar_allowed_types;
        
        // Numeric fields with defaults
        $sanitized['avatar_max_size'] = isset($input['avatar_max_size']) ? absint($input['avatar_max_size']) : 2;
        $sanitized['cover_max_size'] = isset($input['cover_max_size']) ? absint($input['cover_max_size']) : 5;
        $sanitized['avatar_output_size'] = isset($input['avatar_output_size']) ? absint($input['avatar_output_size']) : 300;
        $sanitized['avatar_min_width'] = isset($input['avatar_min_width']) ? absint($input['avatar_min_width']) : 300;
        $sanitized['avatar_min_height'] = isset($input['avatar_min_height']) ? absint($input['avatar_min_height']) : 300;
        $sanitized['avatar_quality'] = isset($input['avatar_quality']) ? min(100, max(1, absint($input['avatar_quality']))) : 90;
        
        return $sanitized;
    }
    
    /**
     * Sanitize activity settings
     */
    public function sanitize_activity_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox field - explicitly set to false if not present (unchecked)
        $sanitized['enable_tracking'] = isset($input['enable_tracking']) ? (bool) $input['enable_tracking'] : false;
        
        // Array field for track types - default to empty array if not set
        $sanitized['track_types'] = $this->sanitize_array_input(
            isset($input['track_types']) ? $input['track_types'] : null
        );
        
        // Numeric fields with defaults
        $sanitized['retention_days'] = isset($input['retention_days']) ? absint($input['retention_days']) : 30;
        $sanitized['per_page'] = isset($input['per_page']) ? absint($input['per_page']) : 20;
        
        return $sanitized;
    }
    
    /**
     * Sanitize community uploads settings
     *
     * @param array $input Raw input from the settings form.
     * @return array Sanitized settings.
     */
    public function sanitize_community_uploads_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields
        $sanitized['enable_photos'] = isset($input['enable_photos']) ? (bool) $input['enable_photos'] : false;
        $sanitized['enable_videos'] = isset($input['enable_videos']) ? (bool) $input['enable_videos'] : false;
        
        // Numeric fields with min/max limits
        $photo_max = isset($input['photo_max_size']) ? absint($input['photo_max_size']) : 5;
        $sanitized['photo_max_size'] = max(1, min(10, $photo_max)); // 1-10 MB
        
        $video_max = isset($input['video_max_size']) ? absint($input['video_max_size']) : 50;
        $sanitized['video_max_size'] = max(1, min(100, $video_max)); // 1-100 MB
        
        // Array field for allowed video formats - whitelist only valid formats
        $valid_formats = array('mp4', 'webm', 'ogv');
        $input_formats = isset($input['allowed_video_formats']) && is_array($input['allowed_video_formats'])
            ? $input['allowed_video_formats']
            : array('mp4');
        $sanitized['allowed_video_formats'] = array_intersect($input_formats, $valid_formats);
        
        // If no valid formats remain, default to mp4
        if (empty($sanitized['allowed_video_formats'])) {
            $sanitized['allowed_video_formats'] = array('mp4');
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize members settings
     */
    public function sanitize_members_settings($input) {
        $sanitized = array();
        $defaults = vh360_get_default_members_directory_options();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields - explicitly set to false if not present (unchecked)
        $sanitized['enable_directory'] = isset($input['enable_directory']) ? (bool) $input['enable_directory'] : false;
        $sanitized['enable_search'] = isset($input['enable_search']) ? (bool) $input['enable_search'] : false;
        $sanitized['professionals_require_approval'] = isset($input['professionals_require_approval']) ? (bool) $input['professionals_require_approval'] : false;
        $sanitized['show_card_stats'] = isset($input['show_card_stats']) ? (bool) $input['show_card_stats'] : false;
        $sanitized['show_card_follow_button'] = isset($input['show_card_follow_button']) ? (bool) $input['show_card_follow_button'] : false;
        $sanitized['enable_category_filter'] = isset($input['enable_category_filter']) ? (bool) $input['enable_category_filter'] : false;
        
        // Numeric field with centralized default and UI bounds
        $per_page = isset($input['per_page']) ? absint($input['per_page']) : absint($defaults['per_page']);
        $sanitized['per_page'] = min(100, max(6, $per_page));
        
        // Text field
        $sanitized['default_sort'] = isset($input['default_sort']) ? sanitize_text_field($input['default_sort']) : $defaults['default_sort'];
        
        // Directory audience - validate against allowed values
        $allowed_audiences = array('all_members', 'professionals_only');
        $sanitized['directory_audience'] = isset($input['directory_audience']) && in_array($input['directory_audience'], $allowed_audiences, true) 
            ? $input['directory_audience'] 
            : $defaults['directory_audience'];
        
        // Professionals account types - validate against allowed values
        $allowed_account_types = vh360_get_professionals_directory_account_types();
        $professionals_account_types = isset($input['professionals_account_types']) && is_array($input['professionals_account_types'])
            ? array_intersect($input['professionals_account_types'], $allowed_account_types)
            : $defaults['professionals_account_types'];
        
        // SECURITY: Enforce non-empty account types when professionals_only is selected
        // Empty account_types in professionals_only mode would cause a security vulnerability
        if ($sanitized['directory_audience'] === 'professionals_only' && empty($professionals_account_types)) {
            // Force defaults to prevent data leak
            $sanitized['professionals_account_types'] = $defaults['professionals_account_types'];
        } else {
            // Ensure at least one type is selected, fallback to default
            $sanitized['professionals_account_types'] = !empty($professionals_account_types) 
                ? array_values($professionals_account_types)
                : $defaults['professionals_account_types'];
        }
        
        // Array field for visible roles - sanitize and validate against registered roles
        $valid_roles = function_exists('wp_roles') && wp_roles() ? array_keys(wp_roles()->roles) : array();
        $visible_roles = isset($input['visible_roles']) && is_array($input['visible_roles'])
            ? array_values(array_intersect(
                array_map('sanitize_text_field', $input['visible_roles']),
                $valid_roles
            ))
            : $defaults['visible_roles'];

        if (empty($visible_roles)) {
            $visible_roles = $defaults['visible_roles'];
        }

        $sanitized['visible_roles'] = $visible_roles;
        
        // Sanitize member categories
        $sanitized['member_categories'] = array();
        if (isset($input['member_categories']) && is_array($input['member_categories'])) {
            $categories = array();
            $seen_slugs = array();
            
            foreach ($input['member_categories'] as $category) {
                if (!is_array($category)) {
                    continue;
                }
                
                // Sanitize slug
                $slug = isset($category['slug']) ? sanitize_title($category['slug']) : '';
                
                // Skip empty slugs or duplicate slugs
                if (empty($slug) || in_array($slug, $seen_slugs, true)) {
                    continue;
                }
                
                // Sanitize label
                $label = isset($category['label']) ? sanitize_text_field($category['label']) : '';
                
                // Skip if label is empty
                if (empty($label)) {
                    continue;
                }
                
                // Sanitize enabled
                $enabled = isset($category['enabled']) ? (bool) $category['enabled'] : false;
                
                // Sanitize sort_order
                $sort_order = isset($category['sort_order']) ? absint($category['sort_order']) : 0;
                
                // Add to categories array
                $categories[] = array(
                    'slug' => $slug,
                    'label' => $label,
                    'enabled' => $enabled,
                    'sort_order' => $sort_order,
                );
                
                $seen_slugs[] = $slug;
            }
            
            // Sort categories by sort_order, then by label
            usort($categories, function($a, $b) {
                if ($a['sort_order'] === $b['sort_order']) {
                    return strcmp($a['label'], $b['label']);
                }
                return $a['sort_order'] - $b['sort_order'];
            });
            
            $sanitized['member_categories'] = $categories;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize gallery settings
     */
    public function sanitize_gallery_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields
        $sanitized['enable_galleries'] = isset($input['enable_galleries']) ? (bool) $input['enable_galleries'] : false;
        $sanitized['enable_frontend_upload'] = isset($input['enable_frontend_upload']) ? (bool) $input['enable_frontend_upload'] : false;
        $sanitized['enable_lightbox'] = isset($input['enable_lightbox']) ? (bool) $input['enable_lightbox'] : false;
        $sanitized['enable_comments'] = isset($input['enable_comments']) ? (bool) $input['enable_comments'] : false;
        
        // Numeric fields with min/max limits
        $max_images = isset($input['max_images_per_gallery']) ? absint($input['max_images_per_gallery']) : 50;
        $sanitized['max_images_per_gallery'] = max(1, min(500, $max_images));
        
        $max_size = isset($input['max_image_size']) ? absint($input['max_image_size']) : 5;
        $sanitized['max_image_size'] = max(1, min(50, $max_size));
        
        $per_page = isset($input['galleries_per_page']) ? absint($input['galleries_per_page']) : 12;
        $sanitized['galleries_per_page'] = max(1, min(100, $per_page));
        
        $columns = isset($input['default_columns']) ? absint($input['default_columns']) : 3;
        $sanitized['default_columns'] = max(self::GALLERY_MIN_COLUMNS, min(self::GALLERY_MAX_COLUMNS, $columns));
        
        // Select field for layout
        $valid_layouts = array('grid', 'masonry', 'justified');
        $sanitized['default_layout'] = isset($input['default_layout']) && in_array($input['default_layout'], $valid_layouts, true) 
            ? $input['default_layout'] 
            : 'grid';
        
        // Array field for allowed image types
        $valid_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $input_types = isset($input['allowed_image_types']) && is_array($input['allowed_image_types'])
            ? $input['allowed_image_types']
            : array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $sanitized['allowed_image_types'] = array_intersect($input_types, $valid_types);
        
        // If no valid types remain, default to common types
        if (empty($sanitized['allowed_image_types'])) {
            $sanitized['allowed_image_types'] = array('jpg', 'jpeg', 'png');
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize notification settings
     */
    public function sanitize_notification_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean fields
        $sanitized['enable_system'] = isset($input['enable_system']) ? (bool) $input['enable_system'] : true;
        $sanitized['enable_caching'] = isset($input['enable_caching']) ? (bool) $input['enable_caching'] : true;
        
        // Numeric fields
        $sanitized['polling_interval'] = isset($input['polling_interval']) ? absint($input['polling_interval']) : 30;
        if ($sanitized['polling_interval'] < 10) {
            $sanitized['polling_interval'] = 10;
        }
        
        $sanitized['max_per_user'] = isset($input['max_per_user']) ? absint($input['max_per_user']) : 100;
        if ($sanitized['max_per_user'] < 10) {
            $sanitized['max_per_user'] = 10;
        } elseif ($sanitized['max_per_user'] > 1000) {
            $sanitized['max_per_user'] = 1000;
        }
        
        $sanitized['retention_days'] = isset($input['retention_days']) ? absint($input['retention_days']) : 30;
        if ($sanitized['retention_days'] < 1) {
            $sanitized['retention_days'] = 1;
        } elseif ($sanitized['retention_days'] > 365) {
            $sanitized['retention_days'] = 365;
        }
        
        // Notification types
        $default_types = array('follow', 'like', 'comment', 'reply', 'mention', 'share');
        $sanitized['enable_types'] = array();
        
        if (isset($input['enable_types']) && is_array($input['enable_types'])) {
            foreach ($default_types as $type) {
                $sanitized['enable_types'][$type] = isset($input['enable_types'][$type]) ? (bool) $input['enable_types'][$type] : false;
            }
        } else {
            // Default all to true if not set
            foreach ($default_types as $type) {
                $sanitized['enable_types'][$type] = true;
            }
        }
        
        // Cleanup schedule
        $valid_schedules = array('hourly', 'twicedaily', 'daily', 'weekly');
        $sanitized['cleanup_schedule'] = isset($input['cleanup_schedule']) && in_array($input['cleanup_schedule'], $valid_schedules)
            ? $input['cleanup_schedule']
            : 'daily';
        
        return $sanitized;
    }
    
    /**
     * Sanitize direct messages settings
     */
    public function sanitize_dm_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        $sanitized['enable_dm'] = isset($input['enable_dm']) ? (bool) $input['enable_dm'] : true;
        $sanitized['require_mutual_follow'] = isset($input['require_mutual_follow']) ? (bool) $input['require_mutual_follow'] : false;
        $sanitized['char_limit'] = isset($input['char_limit']) ? absint($input['char_limit']) : 1000;
        $sanitized['retention_days'] = isset($input['retention_days']) ? absint($input['retention_days']) : 0;
        
        // Ensure minimum char limit
        if ($sanitized['char_limit'] < 100) {
            $sanitized['char_limit'] = 100;
        }
        
        // Ensure maximum char limit
        if ($sanitized['char_limit'] > 5000) {
            $sanitized['char_limit'] = 5000;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize advanced settings
     */
    public function sanitize_advanced_settings($input) {
        $sanitized = array();
        
        // Ensure input is an array
        if (!is_array($input)) {
            $input = array();
        }
        
        // Boolean checkbox fields - explicitly set to false if not present (unchecked)
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? (bool) $input['debug_mode'] : false;
        $sanitized['enable_logging'] = isset($input['enable_logging']) ? (bool) $input['enable_logging'] : false;
        $sanitized['show_deprecated'] = isset($input['show_deprecated']) ? (bool) $input['show_deprecated'] : false;
        
        // Numeric field with default
        $sanitized['transient_expiration'] = isset($input['transient_expiration']) ? absint($input['transient_expiration']) : 3600;
        
        return $sanitized;
    }
    
    /**
     * Sanitize custom profile fields option.
     *
     * Each field definition is validated via VH360_Profile_Fields.
     *
     * @param mixed $input Raw input.
     * @return array Sanitized field definitions keyed by field_id.
     */
    public function sanitize_custom_profile_fields($input) {
        if (null === $input) {
            return get_option('vh360_custom_profile_fields', array());
        }

        if (!class_exists('VH360_Profile_Fields')) {
            return array();
        }

        return VH360_Profile_Fields::get_instance()->sanitize_custom_fields_option($input);
    }
    
    /**
     * Sanitize built-in field visibility settings.
     *
     * @param mixed $input Raw input.
     * @return array Sanitized overrides keyed by field_id.
     */
    public function sanitize_builtin_field_settings($input) {
        if (null === $input) {
            return get_option('vh360_builtin_field_settings', array());
        }

        if (!is_array($input)) {
            return array();
        }
        $sanitized = array();
        foreach ($input as $field_id => $settings) {
            $field_id = sanitize_key($field_id);
            if (!$field_id || !is_array($settings)) {
                continue;
            }
            $sanitized[$field_id] = array(
                'show_on_public_about' => !empty($settings['show_on_public_about']) ? 1 : 0,
                'status'               => (isset($settings['status']) && 'inactive' === $settings['status']) ? 'inactive' : 'active',
            );
        }
        return $sanitized;
    }
    
    /**
     * AJAX: Save or update a single custom profile field.
     */
    public function ajax_save_custom_profile_field() {
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        if (!class_exists('VH360_Profile_Fields')) {
            wp_send_json_error(__('Profile fields system unavailable', 'videohub360-theme'));
        }
        
        $raw = isset($_POST['field']) && is_array($_POST['field']) ? $_POST['field'] : array();
        $manager = VH360_Profile_Fields::get_instance();
        $field = $manager->sanitize_field_definition($raw);
        
        if (!$field) {
            wp_send_json_error(__('Invalid field data. Check that Field Key is set and not a reserved built-in key.', 'videohub360-theme'));
        }
        
        if (empty($field['label'])) {
            wp_send_json_error(__('Field label is required.', 'videohub360-theme'));
        }
        
        $existing = get_option('vh360_custom_profile_fields', array());
        if (!is_array($existing)) {
            $existing = array();
        }
        
        $existing[$field['field_id']] = $field;
        update_option('vh360_custom_profile_fields', $existing);
        
        wp_send_json_success(array(
            'message' => __('Field saved successfully.', 'videohub360-theme'),
            'field'   => $field,
        ));
    }
    
    /**
     * AJAX: Delete a custom profile field definition.
     *
     * Does NOT delete user meta — existing saved values are preserved.
     */
    public function ajax_delete_custom_profile_field() {
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        $field_id = sanitize_key(isset($_POST['field_id']) ? $_POST['field_id'] : '');
        if (!$field_id) {
            wp_send_json_error(__('Field ID is required.', 'videohub360-theme'));
        }
        
        $existing = get_option('vh360_custom_profile_fields', array());
        if (!is_array($existing)) {
            $existing = array();
        }
        
        if (!isset($existing[$field_id])) {
            wp_send_json_error(__('Field not found.', 'videohub360-theme'));
        }
        
        unset($existing[$field_id]);
        update_option('vh360_custom_profile_fields', $existing);
        
        // NOTE: User meta (_vh360_custom_profile_{field_id}) is intentionally left intact.
        
        wp_send_json_success(array(
            'message' => __('Field deleted. Existing user data has been preserved.', 'videohub360-theme'),
        ));
    }
    
    /**
     * AJAX: Save built-in field visibility overrides.
     */
    public function ajax_save_builtin_field_settings() {
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        $raw_settings = isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized    = $this->sanitize_builtin_field_settings($raw_settings);
        update_option('vh360_builtin_field_settings', $sanitized);
        
        wp_send_json_success(array(
            'message' => __('Built-in field settings saved.', 'videohub360-theme'),
        ));
    }
    
    /**
     * Sanitize invite settings.
     */
    public function sanitize_invite_settings($input) {
        $input = is_array($input) ? $input : array();
        $allowed_roles = array('members', 'approved_professionals', 'instructors', 'admins');
        $allowed_forms = array('general', 'client', 'professional', 'instructor');
        $creator_role = isset($input['creator_role']) ? sanitize_key($input['creator_role']) : 'members';
        $required_forms = array();
        if (isset($input['required_registration_forms']) && is_array($input['required_registration_forms'])) {
            foreach ($input['required_registration_forms'] as $form_key) {
                $form_key = sanitize_key($form_key);
                if (in_array($form_key, $allowed_forms, true)) {
                    $required_forms[] = $form_key;
                }
            }
        }

        return array(
            'invite_only_registration' => !empty($input['invite_only_registration']) ? 1 : 0,
            'expiration_days' => isset($input['expiration_days']) ? absint($input['expiration_days']) : 14,
            'creator_role' => in_array($creator_role, $allowed_roles, true) ? $creator_role : 'members',
            'required_registration_forms' => array_values(array_unique($required_forms)),
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/dashboard.php';
    }
    
    /**
     * Render appearance page
     */
    public function render_appearance() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/appearance.php';
    }
    
    /**
     * Render profiles page
     */
    public function render_profiles() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/profiles.php';
    }
    
    /**
     * Render activity page
     */
    public function render_activity() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/activity.php';
    }
    
    /**
     * Render members page
     */
    public function render_members() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/members.php';
    }
    
    /**
     * Render templates page
     */
    public function render_templates() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/templates.php';
    }
    
    /**
     * Render advanced page
     */
    public function render_advanced() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/advanced.php';
    }
    
    /**
     * Render gallery page
     */
    public function render_gallery() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/gallery.php';
    }
    
    /**
     * Render notifications settings page
     */
    public function render_notifications() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/notifications.php';
    }
    
    /**
     * Render direct messages settings page
     */
    public function render_messages() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/messages.php';
    }
    
    /**
     * Render permissions settings page
     */
    public function render_permissions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/permissions.php';
    }
    
    /**
     * Render access/visibility settings page
     */
    public function render_access() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/access.php';
    }
    
    /**
     * Render business settings page
     */
    public function render_business() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/business.php';
    }
    
    /**
     * Render Invites settings page
     */
    public function render_invites() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/invites.php';
    }

    /**
     * Render Memberships settings page
     */
    public function render_memberships() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'videohub360-theme'));
        }
        include VH360_THEME_DIR . '/includes/admin/pages/memberships.php';
    }

    /**
     * Render Profile Data viewer page
     */
    public function render_profile_data_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'videohub360-theme' ) );
        }
        include VH360_THEME_DIR . '/includes/admin/pages/profile-data.php';
    }

    // ----------------------------------------------------------------
    // Profile Data Helper Methods
    // ----------------------------------------------------------------

    /**
     * Build and return the complete field definitions used by both the
     * CSV export and the backend profile data viewer.
     *
     * Returns an associative array with keys:
     *   'standard_columns'      meta_key => label  (WP user object fields)
     *   'vh360_meta_columns'    meta_key => label  (core VH360 usermeta)
     *   'builtin_field_columns' meta_key => label  (VH360_Profile_Fields built-ins)
     *   'builtin_toggle_columns'meta_key => label  (visibility toggles for built-ins)
     *   'custom_field_columns'  meta_key => label  (admin-created custom fields)
     *   'custom_toggle_columns' meta_key => label  (visibility toggles for custom fields)
     *   'builtin_fields'        full field definitions (or empty array)
     *   'custom_fields'         full custom field definitions (or empty array)
     *
     * @return array
     */
    public function get_profile_export_field_definitions() {
        $standard_columns = array(
            'user_id'         => __( 'User ID', 'videohub360-theme' ),
            'user_login'      => __( 'Username', 'videohub360-theme' ),
            'user_email'      => __( 'Email', 'videohub360-theme' ),
            'display_name'    => __( 'Display Name', 'videohub360-theme' ),
            'first_name'      => __( 'First Name', 'videohub360-theme' ),
            'last_name'       => __( 'Last Name', 'videohub360-theme' ),
            'user_url'        => __( 'Website URL', 'videohub360-theme' ),
            'description'     => __( 'Bio / Description', 'videohub360-theme' ),
            'user_registered' => __( 'Registered Date', 'videohub360-theme' ),
            'roles'           => __( 'Roles', 'videohub360-theme' ),
        );

        $vh360_meta_columns = array(
            '_vh360_account_type'      => __( 'Account Type', 'videohub360-theme' ),
            'vh360_profile_picture_id' => __( 'Profile Picture ID', 'videohub360-theme' ),
            '_vh360_profile_cover_id'  => __( 'Cover Image ID', 'videohub360-theme' ),
            '_vh360_twitter'           => __( 'Twitter/X', 'videohub360-theme' ),
            '_vh360_facebook'          => __( 'Facebook', 'videohub360-theme' ),
            '_vh360_youtube'           => __( 'YouTube', 'videohub360-theme' ),
            '_vh360_instagram'         => __( 'Instagram', 'videohub360-theme' ),
            '_vh360_linkedin'          => __( 'LinkedIn', 'videohub360-theme' ),
            '_vh360_tiktok'            => __( 'TikTok', 'videohub360-theme' ),
            '_vh360_twitch'            => __( 'Twitch', 'videohub360-theme' ),
        );

        $builtin_fields        = array();
        $builtin_field_columns = array();
        $builtin_toggle_columns = array();

        if ( class_exists( 'VH360_Profile_Fields' ) ) {
            $builtin_fields = VH360_Profile_Fields::get_instance()->get_builtin_fields();
            foreach ( $builtin_fields as $field ) {
                $meta_key = isset( $field['meta_key'] ) ? $field['meta_key'] : '';
                $label    = isset( $field['label'] ) ? $field['label'] : $meta_key;
                if ( $meta_key && ! isset( $vh360_meta_columns[ $meta_key ] ) ) {
                    $builtin_field_columns[ $meta_key ] = sprintf(
                        /* translators: %s: field label */
                        __( 'Built-in Field: %s', 'videohub360-theme' ),
                        $label
                    );
                }
                if ( ! empty( $field['allow_user_public_toggle'] ) ) {
                    $field_id = isset( $field['field_id'] ) ? $field['field_id'] : '';
                    if ( $field_id ) {
                        $toggle_meta_key = '_vh360_profile_field_public_' . $field_id;
                        $builtin_toggle_columns[ $toggle_meta_key ] = sprintf(
                            /* translators: %s: field label */
                            __( 'Visibility: %s', 'videohub360-theme' ),
                            $label
                        );
                    }
                }
            }
        }

        $raw_custom_fields     = get_option( 'vh360_custom_profile_fields', array() );
        $custom_fields         = is_array( $raw_custom_fields ) ? $raw_custom_fields : array();
        $custom_field_columns  = array();
        $custom_toggle_columns = array();

        foreach ( $custom_fields as $field ) {
            $field_id = isset( $field['field_id'] ) ? $field['field_id'] : '';
            $label    = isset( $field['label'] ) ? $field['label'] : $field_id;
            if ( isset( $field['meta_key'] ) && $field['meta_key'] ) {
                $meta_key = $field['meta_key'];
            } else {
                $meta_key = '_vh360_custom_profile_' . $field_id;
            }
            if ( $meta_key ) {
                $custom_field_columns[ $meta_key ] = sprintf(
                    /* translators: %s: field label */
                    __( 'Custom Field: %s', 'videohub360-theme' ),
                    $label
                );
            }
            if ( ! empty( $field['allow_user_public_toggle'] ) && $field_id ) {
                $toggle_meta_key = '_vh360_profile_field_public_' . $field_id;
                $custom_toggle_columns[ $toggle_meta_key ] = sprintf(
                    /* translators: %s: field label */
                    __( 'Visibility: %s', 'videohub360-theme' ),
                    $label
                );
            }
        }

        return array(
            'standard_columns'       => $standard_columns,
            'vh360_meta_columns'     => $vh360_meta_columns,
            'builtin_field_columns'  => $builtin_field_columns,
            'builtin_toggle_columns' => $builtin_toggle_columns,
            'custom_field_columns'   => $custom_field_columns,
            'custom_toggle_columns'  => $custom_toggle_columns,
            'builtin_fields'         => $builtin_fields,
            'custom_fields'          => $custom_fields,
        );
    }

    /**
     * Retrieve all profile data for a single user, ready for admin display.
     *
     * Returns an associative array with the same section keys as the CSV export:
     *   'wp'            Standard WordPress user fields (key => value).
     *   'vh360_meta'    Core VH360 user meta (key => ['label'=>..., 'value'=>...]).
     *   'builtin_fields'Built-in VH360 profile fields (array of field data).
     *   'custom_fields' Admin-created custom fields (array of field data).
     *
     * @param int $user_id WordPress user ID.
     * @return array|false Array of data or false if the user does not exist.
     */
    public function get_user_profile_data_for_admin( $user_id ) {
        $user = get_user_by( 'id', absint( $user_id ) );
        if ( ! $user ) {
            return false;
        }

        $defs      = $this->get_profile_export_field_definitions();
        $user_meta = get_user_meta( $user->ID );

        // --- WordPress user info ---
        $wp = array(
            'user_id'         => $user->ID,
            'user_login'      => $user->user_login,
            'user_email'      => $user->user_email,
            'display_name'    => $user->display_name,
            'first_name'      => isset( $user_meta['first_name'][0] ) ? $user_meta['first_name'][0] : '',
            'last_name'       => isset( $user_meta['last_name'][0] ) ? $user_meta['last_name'][0] : '',
            'user_url'        => $user->user_url,
            'description'     => isset( $user_meta['description'][0] ) ? $user_meta['description'][0] : '',
            'user_registered' => $user->user_registered,
            'roles'           => $user->roles,
        );

        // --- Core VH360 meta ---
        $vh360_meta = array();
        foreach ( $defs['vh360_meta_columns'] as $meta_key => $label ) {
            $raw_value = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
            $vh360_meta[ $meta_key ] = array(
                'label' => $label,
                'value' => $raw_value,
            );
        }

        // --- Built-in profile fields ---
        $builtin_section = array();
        foreach ( $defs['builtin_fields'] as $field ) {
            $meta_key = isset( $field['meta_key'] ) ? $field['meta_key'] : '';
            if ( ! $meta_key ) {
                continue;
            }
            $raw_value  = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
            $field_id   = isset( $field['field_id'] ) ? $field['field_id'] : '';
            $visibility = null;
            if ( ! empty( $field['allow_user_public_toggle'] ) && $field_id ) {
                $vis_meta_key = '_vh360_profile_field_public_' . $field_id;
                $visibility   = $this->get_profile_field_visibility_label( $user->ID, $field_id );
            }
            $builtin_section[] = array(
                'field_id'   => $field_id,
                'label'      => isset( $field['label'] ) ? $field['label'] : $meta_key,
                'meta_key'   => $meta_key,
                'type'       => isset( $field['type'] ) ? $field['type'] : 'text',
                'value'      => $raw_value,
                'visibility' => $visibility,
            );
        }

        // --- Custom profile fields ---
        $custom_section = array();
        foreach ( $defs['custom_fields'] as $field ) {
            $field_id = isset( $field['field_id'] ) ? $field['field_id'] : '';
            $label    = isset( $field['label'] ) ? $field['label'] : $field_id;
            $type     = isset( $field['type'] ) ? $field['type'] : 'text';
            if ( isset( $field['meta_key'] ) && $field['meta_key'] ) {
                $meta_key = $field['meta_key'];
            } else {
                $meta_key = '_vh360_custom_profile_' . $field_id;
            }
            $raw_value  = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
            $visibility = null;
            if ( ! empty( $field['allow_user_public_toggle'] ) && $field_id ) {
                $visibility = $this->get_profile_field_visibility_label( $user->ID, $field_id );
            }
            $custom_section[] = array(
                'field_id'   => $field_id,
                'label'      => $label,
                'meta_key'   => $meta_key,
                'type'       => $type,
                'value'      => $raw_value,
                'visibility' => $visibility,
            );
        }

        return array(
            'user'           => $user,
            'wp'             => $wp,
            'vh360_meta'     => $vh360_meta,
            'builtin_fields' => $builtin_section,
            'custom_fields'  => $custom_section,
        );
    }

    /**
     * Format a user meta value for safe HTML display in the admin.
     *
     * - Arrays are joined as a comma-separated string.
     * - Booleans / '1'/'0' are displayed as Yes/No.
     * - URLs are returned as-is (caller must use esc_url when echoing).
     * - Long textarea content is returned with line-breaks preserved
     *   (caller must use wp_kses_post or echo nl2br(esc_html(...))).
     * - Empty values return an em dash placeholder.
     *
     * @param  mixed  $value       Raw meta value (single, not the array wrapper).
     * @param  string $field_type  Optional field type hint ('url', 'textarea', 'checkbox').
     * @return string
     */
    public function format_profile_value_for_admin( $value, $field_type = '' ) {
        $unserialized = @maybe_unserialize( $value );

        if ( is_array( $unserialized ) ) {
            $parts = array_filter( array_map( 'strval', $unserialized ), static function( $v ) {
                return '' !== $v;
            } );
            if ( empty( $parts ) ) {
                return '&mdash;';
            }
            return esc_html( implode( ', ', $parts ) );
        }

        if ( is_object( $unserialized ) ) {
            return '&mdash;';
        }

        $scalar = (string) $value;

        if ( '' === $scalar ) {
            return '&mdash;';
        }

        // Checkbox-style fields.
        if ( 'checkbox' === $field_type ) {
            return '1' === $scalar ? esc_html__( 'Yes', 'videohub360-theme' ) : esc_html__( 'No', 'videohub360-theme' );
        }

        // Generic 1/0 booleans.
        if ( '1' === $scalar ) {
            return esc_html__( 'Yes', 'videohub360-theme' );
        }
        if ( '0' === $scalar ) {
            return esc_html__( 'No', 'videohub360-theme' );
        }

        return esc_html( $scalar );
    }

    /**
     * Return the human-readable visibility label for a profile field.
     *
     * '0' stored in the toggle meta = Private (user opted out of public).
     * Anything else (including not set) = Public.
     *
     * @param int    $user_id  WordPress user ID.
     * @param string $field_id VH360 field ID.
     * @return string 'Public' or 'Private'
     */
    public function get_profile_field_visibility_label( $user_id, $field_id ) {
        $meta_key = '_vh360_profile_field_public_' . $field_id;
        $raw      = (string) get_user_meta( absint( $user_id ), $meta_key, true );
        return ( '0' === $raw )
            ? __( 'Private', 'videohub360-theme' )
            : __( 'Public', 'videohub360-theme' );
    }

    /**
     * Sanitize permissions settings
     */
    public function sanitize_permissions_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }
        
        $output = array();
        $all_roles = wp_roles()->roles;
        
        // Sanitize each role array
        $keys = array(
            'create_posts_roles',
            'create_videos_roles',
            'host_live_roles',
            'create_events_roles',
            'create_bulletins_roles',
            'bulletin_banner_roles',
            'create_galleries_roles',
            'publish_galleries_roles',
            'upload_media_roles',
        );
        foreach ($keys as $key) {
            $output[$key] = array();
            
            if (isset($input[$key]) && is_array($input[$key])) {
                foreach ($input[$key] as $role_key) {
                    $role_key = sanitize_key($role_key);
                    // Only include if role exists
                    if (isset($all_roles[$role_key])) {
                        $output[$key][] = $role_key;
                    }
                }
            }
            
            // Always ensure administrator is included
            if (!in_array('administrator', $output[$key], true)) {
                $output[$key][] = 'administrator';
            }
        }
        
        // Set transient to trigger capability sync on next admin_init
        set_transient('vh360_permissions_needs_sync', true, 60);
        
        return $output;
    }
    
    /**
     * Sanitize access/visibility settings
     */
    public function sanitize_access_settings($input) {
        // Get targets from registry to ensure we only save known keys
        $targets = vh360_get_access_control_targets();
        
        $output = array();
        
        foreach ($targets as $key => $target) {
            // Normalize to 1 or 0
            $output[$key] = !empty($input[$key]) ? 1 : 0;
        }
        
        return $output;
    }
    
    /**
     * Sanitize business settings
     */
    public function sanitize_business_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }
        
        $output = array();
        
        // Sanitize require_professional_approval checkbox
        $output['require_professional_approval'] = !empty($input['require_professional_approval']) ? 1 : 0;
        
        return $output;
    }

    /**
     * Sanitize membership settings
     */
    public function sanitize_membership_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }

        $output = get_option('vh360_membership_options', array());
        $output = is_array($output) ? $output : array();
        $section = isset($input['_settings_section']) ? sanitize_key($input['_settings_section']) : 'general';

        if ('general' === $section) {
            // Sanitize enable_memberships checkbox
            $output['enable_memberships'] = !empty($input['enable_memberships']) ? 1 : 0;

            // Sanitize pricing_page_url
            $output['pricing_page_url'] = !empty($input['pricing_page_url']) ? esc_url_raw($input['pricing_page_url']) : '';
            $output['support_url'] = !empty($input['support_url']) ? esc_url_raw($input['support_url']) : '';
            $output['contact_url'] = !empty($input['contact_url']) ? esc_url_raw($input['contact_url']) : '';

            // Sanitize course purchase destination.
            $course_purchase_destination = isset($input['course_purchase_destination']) ? sanitize_key($input['course_purchase_destination']) : 'product_page';
            $output['course_purchase_destination'] = in_array($course_purchase_destination, array('product_page', 'add_to_cart'), true)
                ? $course_purchase_destination
                : 'product_page';

            // Sanitize login_required checkbox
            $output['login_required'] = !empty($input['login_required']) ? 1 : 0;

            // Sanitize locked_message
            $output['locked_message'] = !empty($input['locked_message']) ? wp_kses_post($input['locked_message']) : '';

            // Sanitize reminder_days
            $output['reminder_days'] = isset($input['reminder_days']) ? absint($input['reminder_days']) : 7;

            // Sanitize grace_period_days
            $output['grace_period_days'] = isset($input['grace_period_days']) ? absint($input['grace_period_days']) : 0;

            // Sanitize feature gating toggles
            $output['gate_live_rooms'] = !empty($input['gate_live_rooms']) ? 1 : 0;
            $output['gate_create_videos'] = !empty($input['gate_create_videos']) ? 1 : 0;
            $output['gate_create_posts'] = !empty($input['gate_create_posts']) ? 1 : 0;
            $output['gate_create_events'] = !empty($input['gate_create_events']) ? 1 : 0;
            $output['gate_create_bulletins'] = !empty($input['gate_create_bulletins']) ? 1 : 0;
            $output['gate_create_galleries'] = !empty($input['gate_create_galleries']) ? 1 : 0;
            $output['gate_direct_messages'] = !empty($input['gate_direct_messages']) ? 1 : 0;
            $output['gate_activity_feed'] = !empty($input['gate_activity_feed']) ? 1 : 0;
            $output['gate_members_directory'] = !empty($input['gate_members_directory']) ? 1 : 0;
            $output['gate_appointments'] = !empty($input['gate_appointments']) ? 1 : 0;
            $output['gate_push_notifications'] = !empty($input['gate_push_notifications']) ? 1 : 0;
        }

        // Sanitize dashboard subscription card and pricing plan card styling.
        $dashboard_card_style_defaults = self::get_membership_dashboard_card_style_defaults();
        $pricing_card_style_defaults = self::get_membership_pricing_card_style_defaults();
        $color_fields = array_merge(array_keys($dashboard_card_style_defaults), array_keys($pricing_card_style_defaults));
        foreach ($color_fields as $field) {
            if (array_key_exists($field, $input)) {
                $output[$field] = sanitize_hex_color($input[$field]) ?: '';
            }
        }
        if (array_key_exists('subscription_card_button_label', $input)) {
            $output['subscription_card_button_label'] = sanitize_text_field($input['subscription_card_button_label']);
        }
        
        return $output;
    }
    
    /**
     * Sanitize Stripe settings
     */
    public function sanitize_stripe_settings($input) {
        if (!is_array($input)) {
            $input = array();
        }
        
        $output = array();
        
        $output['enable_recurring'] = !empty($input['enable_recurring']) ? 1 : 0;
        $output['test_mode'] = !empty($input['test_mode']) ? 1 : 0;
        
        // Live keys
        $output['publishable_key'] = isset($input['publishable_key']) ? sanitize_text_field($input['publishable_key']) : '';
        $output['secret_key'] = isset($input['secret_key']) ? sanitize_text_field($input['secret_key']) : '';
        
        // Test keys
        $output['test_publishable_key'] = isset($input['test_publishable_key']) ? sanitize_text_field($input['test_publishable_key']) : '';
        $output['test_secret_key'] = isset($input['test_secret_key']) ? sanitize_text_field($input['test_secret_key']) : '';
        
        // Webhook secret
        $output['webhook_secret'] = isset($input['webhook_secret']) ? sanitize_text_field($input['webhook_secret']) : '';
        
        // Portal and behavior
        $output['enable_portal'] = !empty($input['enable_portal']) ? 1 : 0;
        $output['cancellation_behavior'] = isset($input['cancellation_behavior']) && in_array($input['cancellation_behavior'], array('at_period_end', 'immediate'), true)
            ? $input['cancellation_behavior']
            : 'at_period_end';
        
        return $output;
    }
    
    /**
     * Sync capabilities to roles based on saved permissions
     * Uses a non-destructive approach that only manages roles we previously controlled
     */
    public function sync_permissions_capabilities() {
        // Only run for admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we need to sync (using transient to avoid running on every page load)
        $needs_sync = get_transient('vh360_permissions_needs_sync');
        if ($needs_sync === false) {
            return;
        }
        
        // Delete the transient so we don't run again until needed
        delete_transient('vh360_permissions_needs_sync');
        
        // Get current saved permissions
        $current_permissions = get_option('vh360_permissions_options', array(
            'create_posts_roles' => array('administrator'),
            'create_videos_roles' => array('administrator', 'vh360_instructor'),
            'host_live_roles' => array('administrator'),
            'create_events_roles' => array('administrator', 'editor', 'author', 'vh360_professional'),
            'create_bulletins_roles' => array('administrator', 'editor', 'author', 'contributor'),
            'bulletin_banner_roles' => array('administrator', 'editor'),
            'create_galleries_roles' => array('administrator', 'editor', 'author', 'contributor'),
            'publish_galleries_roles' => array('administrator', 'editor', 'author'),
            'upload_media_roles' => array('administrator', 'editor', 'author'),
        ));
        
        // Get previously applied permissions
        $previous_permissions = get_option('vh360_permissions_applied_roles', array(
            'create_posts_roles' => array(),
            'create_videos_roles' => array(),
            'host_live_roles' => array(),
            'create_events_roles' => array(),
            'create_bulletins_roles' => array(),
            'bulletin_banner_roles' => array(),
            'create_galleries_roles' => array(),
            'publish_galleries_roles' => array(),
            'upload_media_roles' => array(),
        ));
        
        // Compute final upload roles (AUTO + manual override)
        $auto_upload_roles = array_unique(array_merge(
            isset($current_permissions['create_bulletins_roles']) ? (array) $current_permissions['create_bulletins_roles'] : array(),
            isset($current_permissions['create_galleries_roles']) ? (array) $current_permissions['create_galleries_roles'] : array()
        ));
        
        // admin always
        if (!in_array('administrator', $auto_upload_roles, true)) {
            $auto_upload_roles[] = 'administrator';
        }
        
        // Retrieve manually selected roles from Upload Media permission UI setting
        $manual_upload_roles = isset($current_permissions['upload_media_roles'])
            ? (array) $current_permissions['upload_media_roles']
            : array('administrator');
        
        // Merge auto-granted and manually selected roles into final upload permission set
        $final_upload_roles = array_unique(array_merge($auto_upload_roles, $manual_upload_roles));
        
        // Make the sync system treat upload_media_roles as the source of truth for upload_files
        $current_permissions['upload_media_roles'] = $final_upload_roles;
        
        // Map role keys to capabilities (now supports arrays of capabilities)
        $capability_map = array(
            'create_posts_roles' => array('vh360_create_posts'),
            'create_videos_roles' => array('vh360_create_videos'),
            'host_live_roles' => array('vh360_host_live_rooms'),
            'create_events_roles' => array('vh360_create_events'),
            'create_bulletins_roles' => array('vh360_create_bulletins'),
            'bulletin_banner_roles' => array('vh360_manage_bulletin_banner'),
            'create_galleries_roles' => array(
                'create_vh360_galleries',
                'edit_vh360_galleries',
                'edit_vh360_gallery',
                'read_vh360_gallery',
                'delete_vh360_gallery',
                'delete_vh360_galleries',
                'assign_vh360_gallery_terms',
            ),
            'publish_galleries_roles' => array(
                'publish_vh360_galleries',
                'edit_published_vh360_galleries',
                'delete_published_vh360_galleries',
            ),
            'upload_media_roles' => array('upload_files'),
        );
        
        $updated = false;
        
        foreach ($capability_map as $role_key => $capabilities) {
            // Ensure capabilities is always an array
            $capabilities = (array) $capabilities;
            
            $current_roles = isset($current_permissions[$role_key]) ? $current_permissions[$role_key] : array('administrator');
            $previous_roles = isset($previous_permissions[$role_key]) ? $previous_permissions[$role_key] : array();
            
            // Ensure administrator is always included
            if (!in_array('administrator', $current_roles, true)) {
                $current_roles[] = 'administrator';
            }
            
            // Calculate differences
            $roles_to_add = array_diff($current_roles, $previous_roles);
            $roles_to_remove = array_diff($previous_roles, $current_roles);
            
            // Add capabilities to new roles
            foreach ($roles_to_add as $role_name) {
                $role = get_role($role_name);
                if ($role) {
                    foreach ($capabilities as $capability) {
                        if (!$role->has_cap($capability)) {
                            $role->add_cap($capability);
                            $updated = true;
                        }
                    }
                }
            }
            
            // Remove capabilities from roles no longer selected
            foreach ($roles_to_remove as $role_name) {
                $role = get_role($role_name);
                if ($role) {
                    foreach ($capabilities as $capability) {
                        if ($role->has_cap($capability)) {
                            $role->remove_cap($capability);
                            $updated = true;
                        }
                    }
                }
            }
        }
        
        // Update the applied roles tracker if anything changed
        if ($updated) {
            update_option('vh360_permissions_applied_roles', $current_permissions);
        }
    }
    
    /**
     * Recursively sanitize settings array
     */
    private function sanitize_settings_array($array) {
        if (!is_array($array)) {
            return sanitize_text_field($array);
        }
        
        $sanitized = array();
        foreach ($array as $key => $value) {
            $sanitized[sanitize_key($key)] = is_array($value) 
                ? $this->sanitize_settings_array($value) 
                : sanitize_text_field($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize Customizer data recursively while preserving native types
     *
     * Unlike sanitize_settings_array(), this preserves booleans, integers, floats, and arrays.
     * Only string values are sanitized with sanitize_text_field().
     *
     * @param mixed $data The data to sanitize
     * @return mixed Sanitized data with preserved types
     */
    private function sanitize_customizer_data($data) {
        // Preserve null
        if (is_null($data)) {
            return null;
        }
        
        // Preserve booleans
        if (is_bool($data)) {
            return $data;
        }
        
        // Preserve integers and floats
        if (is_int($data) || is_float($data)) {
            return $data;
        }
        
        // Recursively sanitize arrays
        if (is_array($data)) {
            $sanitized = array();
            foreach ($data as $key => $value) {
                // Sanitize key but preserve numeric keys
                $sanitized_key = is_string($key) ? sanitize_key($key) : $key;
                $sanitized[$sanitized_key] = $this->sanitize_customizer_data($value);
            }
            return $sanitized;
        }
        
        // Sanitize strings
        if (is_string($data)) {
            return sanitize_text_field($data);
        }
        
        // For any other type, return as-is (shouldn't normally happen)
        return $data;
    }
    
    /**
     * Sanitize array input field
     *
     * Helper method to sanitize array inputs from checkboxes or multi-select fields.
     *
     * @param array|mixed $input The input value to sanitize.
     * @return array Sanitized array of text values.
     */
    private function sanitize_array_input($input) {
        if (!isset($input) || !is_array($input)) {
            return array();
        }
        return array_map('sanitize_text_field', $input);
    }
    
    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        // Check nonce
        if (!check_ajax_referer('vh360_import_settings', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        // Get settings from POST data
        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
            wp_send_json_error(__('No settings data provided', 'videohub360-theme'));
        }
        
        // Sanitize settings data recursively
        $settings = $this->sanitize_settings_array($_POST['settings']);
        
        // Import each settings group
        if (isset($settings['appearance'])) {
            update_option('vh360_appearance_options', $settings['appearance']);
        }
        
        if (isset($settings['profile'])) {
            update_option('vh360_profile_options', $settings['profile']);
        }
        
        if (isset($settings['activity'])) {
            update_option('vh360_activity_options', $settings['activity']);
        }
        
        if (isset($settings['members'])) {
            update_option('vh360_members_options', $settings['members']);
        }
        
        if (isset($settings['advanced'])) {
            update_option('vh360_advanced_options', $settings['advanced']);
        }
        
        if (isset($settings['access'])) {
            update_option('vh360_access_options', $settings['access']);
        }
        
        if (isset($settings['membership'])) {
            update_option('vh360_membership_options', $settings['membership']);
        }

        // Import safe standalone VideoHub360 Core options.
        if ( isset( $settings['videohub360_core'] ) && is_array( $settings['videohub360_core'] ) ) {
            if ( array_key_exists( 'enable_course_features', $settings['videohub360_core'] ) ) {
                update_option(
                    'videohub360_enable_course_features',
                    ! empty( $settings['videohub360_core']['enable_course_features'] ) ? 1 : 0
                );
            }
        }
        
        // Import custom profile field definitions (sanitized).
        if (isset($settings['custom_profile_fields']) && is_array($settings['custom_profile_fields'])) {
            $sanitized_fields = $this->sanitize_custom_profile_fields($settings['custom_profile_fields']);
            update_option('vh360_custom_profile_fields', $sanitized_fields);
        }
        
        // Import built-in field visibility overrides.
        if (isset($settings['builtin_field_settings']) && is_array($settings['builtin_field_settings'])) {
            $sanitized_builtin = $this->sanitize_builtin_field_settings($settings['builtin_field_settings']);
            update_option('vh360_builtin_field_settings', $sanitized_builtin);
        }
        
        wp_send_json_success(__('Settings imported successfully', 'videohub360-theme'));
    }
    
    /**
     * AJAX handler for exporting Customizer settings
     */
    public function ajax_export_customizer() {
        // Check nonce
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        // Get all theme mods
        $theme_mods = get_theme_mods();
        
        // Filter out invalid keys (numeric keys like "0")
        if (is_array($theme_mods)) {
            $filtered_mods = array();
            foreach ($theme_mods as $key => $value) {
                // Only include valid string keys (not numeric indices)
                if (is_string($key) && !is_numeric($key)) {
                    $filtered_mods[$key] = $value;
                }
            }
            $theme_mods = $filtered_mods;
        }
        
        // Build payload in the format expected by the Starter Sites importer
        $customizer_data = array(
            'mods' => $theme_mods ? $theme_mods : array(),
            'options' => array(), // Empty as theme uses theme mods, not Customizer options
        );
        
        wp_send_json_success($customizer_data);
    }
    
    /**
     * AJAX handler for importing Customizer settings
     */
    public function ajax_import_customizer() {
        // Check nonce
        if (!check_ajax_referer('vh360_import_customizer', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        // Get customizer data from POST
        if (!isset($_POST['customizer_data']) || !is_array($_POST['customizer_data'])) {
            wp_send_json_error(__('No customizer data provided', 'videohub360-theme'));
        }
        
        // Sanitize recursively while preserving native types
        $customizer_data = $this->sanitize_customizer_data($_POST['customizer_data']);
        
        // Import theme mods
        if (isset($customizer_data['mods']) && is_array($customizer_data['mods'])) {
            foreach ($customizer_data['mods'] as $key => $value) {
                set_theme_mod($key, $value);
            }
        }
        
        // Import options
        if (isset($customizer_data['options']) && is_array($customizer_data['options'])) {
            foreach ($customizer_data['options'] as $key => $value) {
                update_option($key, $value);
            }
        }
        
        wp_send_json_success(__('Customizer settings imported successfully', 'videohub360-theme'));
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Check nonce
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'videohub360-theme'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'videohub360-theme'));
        }
        
        // Clear cache
        $this->clear_theme_cache();
        
        wp_send_json_success(__('Cache cleared successfully', 'videohub360-theme'));
    }
    
    /**
     * AJAX handler for manual notification cleanup
     */
    public function ajax_manual_notification_cleanup() {
        // Check nonce
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360-theme')));
        }
        
        // Get retention days from settings
        $options = get_option('vh360_notification_options', array());
        $retention_days = isset($options['retention_days']) ? absint($options['retention_days']) : 30;
        
        // Delete old notifications
        global $wpdb;
        $system = VH360_Notification_System::get_instance();
        $table_name = $system->get_table_name();
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
        
        if ($deleted === false) {
            wp_send_json_error(array('message' => __('Cleanup failed', 'videohub360-theme')));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: number of notifications deleted */
                __('Cleanup completed. %d notifications deleted.', 'videohub360-theme'),
                $deleted
            )
        ));
    }
    
    /**
     * AJAX handler for resetting all notifications
     */
    public function ajax_reset_all_notifications() {
        // Check nonce
        if (!check_ajax_referer('vh360_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360-theme')));
        }
        
        // Delete all notifications using DELETE instead of TRUNCATE for better control
        global $wpdb;
        $system = VH360_Notification_System::get_instance();
        $table_name = $system->get_table_name();
        
        // Verify table name is valid (security check)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', str_replace($wpdb->prefix, '', $table_name))) {
            wp_send_json_error(array('message' => __('Invalid table name', 'videohub360-theme')));
        }
        
        // Use DELETE instead of TRUNCATE for better transaction support
        $deleted = $wpdb->query("DELETE FROM {$table_name}");
        
        if ($deleted === false) {
            wp_send_json_error(array('message' => __('Reset failed', 'videohub360-theme')));
        }
        
        // Clear all user notification count caches
        $wpdb->delete($wpdb->usermeta, array('meta_key' => 'vh360_unread_notification_count'), array('%s'));
        
        wp_send_json_success(array(
            'message' => __('All notifications have been deleted.', 'videohub360-theme')
        ));
    }
    
    /**
     * AJAX handler for approving a professional
     */
    public function ajax_approve_professional() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_approve_professional_' . $user_id)) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360-theme')));
        }
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'videohub360-theme')));
        }
        
        // Get user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found', 'videohub360-theme')));
        }
        
        // Set status to approved
        update_user_meta($user_id, '_vh360_professional_status', 'approved');
        
        // Set role to vh360_professional
        $user->set_role('vh360_professional');
        
        // Send approval notification email
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Your Professional Account Has Been Approved', 'videohub360-theme'), $site_name);
        $message = sprintf(__('Hello %s,', 'videohub360-theme'), $user->display_name) . "\n\n";
        $message .= sprintf(__('Great news! Your professional account on %s has been approved.', 'videohub360-theme'), $site_name) . "\n\n";
        $message .= __('You now have access to all professional features, including:', 'videohub360-theme') . "\n";
        $message .= __('- Create and manage events', 'videohub360-theme') . "\n";
        $message .= __('- Set availability for appointments', 'videohub360-theme') . "\n";
        $message .= __('- Showcase your business profile', 'videohub360-theme') . "\n\n";
        $message .= sprintf(__('Visit your dashboard: %s', 'videohub360-theme'), home_url('/dashboard/')) . "\n\n";
        $message .= sprintf(__('Thank you for joining %s!', 'videohub360-theme'), $site_name) . "\n";
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Professional %s has been approved.', 'videohub360-theme'), $user->display_name)
        ));
    }
    
    /**
     * AJAX handler for rejecting a professional
     */
    public function ajax_reject_professional() {
        // Get user ID
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_reject_professional_' . $user_id)) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360-theme')));
        }
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'videohub360-theme')));
        }
        
        // Get user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found', 'videohub360-theme')));
        }
        
        // Set status to rejected
        update_user_meta($user_id, '_vh360_professional_status', 'rejected');
        
        // Keep as subscriber role (or set explicitly)
        if ($user->roles && in_array('vh360_professional', $user->roles, true)) {
            $user->set_role('subscriber');
        }
        
        // Send rejection notification email
        $site_name = get_bloginfo('name');
        $subject = sprintf(__('[%s] Professional Account Application Status', 'videohub360-theme'), $site_name);
        $message = sprintf(__('Hello %s,', 'videohub360-theme'), $user->display_name) . "\n\n";
        $message .= sprintf(__('Thank you for your interest in becoming a professional on %s.', 'videohub360-theme'), $site_name) . "\n\n";
        $message .= __('After review, we are unable to approve your professional account at this time.', 'videohub360-theme') . "\n\n";
        $message .= __('If you have questions or would like to discuss this decision, please contact us.', 'videohub360-theme') . "\n\n";
        $message .= __('You can still use the platform as a standard member.', 'videohub360-theme') . "\n";
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Professional %s has been rejected.', 'videohub360-theme'), $user->display_name)
        ));
    }

    /**
     * Export all user profile data as a CSV file.
     *
     * Accessible only to administrators via admin-post.php.
     */
    public function export_user_profiles_csv() {
        // Capability check.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                esc_html__( 'You do not have permission to perform this action.', 'videohub360-theme' ),
                esc_html__( 'Permission denied', 'videohub360-theme' ),
                array( 'response' => 403 )
            );
        }

        // Nonce verification.
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'vh360_export_user_profiles' ) ) {
            wp_die(
                esc_html__( 'Security check failed.', 'videohub360-theme' ),
                esc_html__( 'Security check failed', 'videohub360-theme' ),
                array( 'response' => 403 )
            );
        }

        // ----------------------------------------------------------------
        // Build column definitions via shared helper.
        // ----------------------------------------------------------------
        $defs                  = $this->get_profile_export_field_definitions();
        $standard_columns      = $defs['standard_columns'];
        $vh360_meta_columns    = $defs['vh360_meta_columns'];
        $builtin_field_columns = $defs['builtin_field_columns'];
        $builtin_toggle_columns= $defs['builtin_toggle_columns'];
        $custom_field_columns  = $defs['custom_field_columns'];
        $custom_toggle_columns = $defs['custom_toggle_columns'];

        // ----------------------------------------------------------------
        // CSV headers.
        // ----------------------------------------------------------------
        $headers = array_merge(
            array_values( $standard_columns ),
            array_values( $vh360_meta_columns ),
            array_values( $builtin_field_columns ),
            array_values( $builtin_toggle_columns ),
            array_values( $custom_field_columns ),
            array_values( $custom_toggle_columns )
        );

        // ----------------------------------------------------------------
        // Send HTTP headers.
        // ----------------------------------------------------------------
        $filename = 'vh360-user-profiles-' . gmdate( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // UTF-8 BOM for Excel compatibility.
        fwrite( $output, "\xEF\xBB\xBF" );

        fputcsv( $output, $headers );

        // ----------------------------------------------------------------
        // Stream rows in batches.
        // ----------------------------------------------------------------
        $batch_size = 200;
        $paged      = 1;

        do {
            $users = get_users( array(
                'number'  => $batch_size,
                'paged'   => $paged,
                'orderby' => 'ID',
                'order'   => 'ASC',
            ) );

            foreach ( $users as $user ) {
                $user_meta = get_user_meta( $user->ID );

                $row = array();

                // Standard WP fields.
                $row[] = $user->ID;
                $row[] = $user->user_login;
                $row[] = $user->user_email;
                $row[] = $user->display_name;
                $row[] = isset( $user_meta['first_name'][0] ) ? $user_meta['first_name'][0] : '';
                $row[] = isset( $user_meta['last_name'][0] ) ? $user_meta['last_name'][0] : '';
                $row[] = $user->user_url;
                $row[] = isset( $user_meta['description'][0] ) ? $user_meta['description'][0] : '';
                $row[] = $user->user_registered;
                $row[] = implode( ', ', $user->roles );

                // VH360 meta fields.
                foreach ( array_keys( $vh360_meta_columns ) as $meta_key ) {
                    $value = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
                    $row[] = $this->csv_format_meta_value( $value );
                }

                // Built-in profile fields.
                foreach ( array_keys( $builtin_field_columns ) as $meta_key ) {
                    $value = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
                    $row[] = $this->csv_format_meta_value( $value );
                }

                // Built-in visibility toggles.
                foreach ( array_keys( $builtin_toggle_columns ) as $meta_key ) {
                    $raw   = isset( $user_meta[ $meta_key ][0] ) ? (string) $user_meta[ $meta_key ][0] : '';
                    $row[] = ( '0' === $raw ) ? __( 'Private', 'videohub360-theme' ) : __( 'Public', 'videohub360-theme' );
                }

                // Custom profile fields.
                foreach ( array_keys( $custom_field_columns ) as $meta_key ) {
                    $value = isset( $user_meta[ $meta_key ][0] ) ? $user_meta[ $meta_key ][0] : '';
                    $row[] = $this->csv_format_meta_value( $value );
                }

                // Custom visibility toggles.
                foreach ( array_keys( $custom_toggle_columns ) as $meta_key ) {
                    $raw   = isset( $user_meta[ $meta_key ][0] ) ? (string) $user_meta[ $meta_key ][0] : '';
                    $row[] = ( '0' === $raw ) ? __( 'Private', 'videohub360-theme' ) : __( 'Public', 'videohub360-theme' );
                }

                fputcsv( $output, $row );
            }

            $paged++;
        } while ( count( $users ) === $batch_size );

        fclose( $output );
        exit;
    }

    /**
     * Format a user meta value for CSV output.
     *
     * - Unserialized arrays are joined with a comma-space.
     * - Scalar 1/0 values representing checkboxes output Yes/No.
     * - Other scalars are returned as-is.
     *
     * @param mixed $value Raw meta value (already single-value, not the array wrapper).
     * @return string
     */
    private function csv_format_meta_value( $value ) {
        // Attempt to unserialize if it looks serialized.
        $unserialized = @maybe_unserialize( $value );

        if ( is_array( $unserialized ) ) {
            // Filter out empty items and join.
            $parts = array_filter( array_map( 'strval', $unserialized ), function( $v ) {
                return '' !== $v;
            } );
            return implode( ', ', $parts );
        }

        if ( is_object( $unserialized ) ) {
            return '';
        }

        $scalar = (string) $value;

        // Treat '1'/'0' as Yes/No for checkbox-style fields.
        if ( '1' === $scalar ) {
            return __( 'Yes', 'videohub360-theme' );
        }
        if ( '0' === $scalar ) {
            return __( 'No', 'videohub360-theme' );
        }

        return $scalar;
    }
}
