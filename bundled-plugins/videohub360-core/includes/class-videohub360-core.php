<?php
/**
 * VideoHub360 Core Class
 * 
 * Main plugin orchestrator that loads and initializes all components
 * 
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Core {
    
    /**
     * Plugin instance
     * 
     * @var VideoHub360_Core
     */
    private static $instance = null;
    
    /**
     * Plugin version
     * 
     * @var string
     */
    public $version = VIDEOHUB360_VERSION;
    
    /**
     * Plugin components
     * 
     * @var array
     */
    public $components = array();
    
    /**
     * Get plugin instance
     * 
     * @return VideoHub360_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        // Components will be initialized on the init hook
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('admin_notices', 'videohub360_license_admin_notice');
        add_action('admin_init', 'videohub360_gate_ajax_actions', 0);
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp', array($this, 'handle_view_tracking'));
        add_filter('template_include', array($this, 'force_archive_template'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load helper functions
        require_once VIDEOHUB360_INCLUDES_DIR . 'helpers/livestream-messages.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'helpers/course-access-helpers.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'helpers/video-ad-helpers.php';
        
        // Load component classes
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-post-types.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-admin.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-frontend.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-ajax.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-chat.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-widgets.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-video-quality.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-shortcode-builder.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-import-export.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-video-reactions.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-playlists.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-youtube-live-monitor.php';
        // Course / Lesson Foundation
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-course-foundation.php';
        // Learner Enrollment Model
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-course-enrollments.php';
        // Licensing / updates client
        require_once VIDEOHUB360_INCLUDES_DIR . 'class-videohub360-license.php';
        require_once VIDEOHUB360_INCLUDES_DIR . 'vh360-license-gate.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->components['post_types'] = new VideoHub360_Post_Types();
        $this->components['admin'] = new VideoHub360_Admin();
        $this->components['frontend'] = new VideoHub360_Frontend();
        $this->components['ajax'] = new VideoHub360_Ajax();
        $this->components['chat'] = new VideoHub360_Chat();
        $this->components['widgets'] = new VideoHub360_Widgets();
        $this->components['shortcode_builder'] = new VideoHub360_Shortcode_Builder();
        $this->components['import_export'] = new VideoHub360_Import_Export();
        $this->components['license'] = new VideoHub360_License();
        $this->components['course_foundation'] = new VideoHub360_Course_Foundation();
        $this->components['course_enrollments'] = VideoHub360_Course_Enrollments::get_instance();
        $this->components['youtube_live_monitor'] = new VideoHub360_YouTube_Live_Monitor();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if database needs upgrade
        $this->check_database_version();
        
        // Initialize components first
        $this->init_components();
        
        // Plugin initialization logic here
        $this->load_utility_functions();
        do_action('videohub360_init');
    }
    
    /**
     * Check database version and upgrade if needed
     */
    private function check_database_version() {
        $current_version = get_option('videohub360_chat_db_version', '1.0');
        
        // Upgrade to version 2.3 - Add private messaging support
        if (version_compare($current_version, '2.3', '<')) {
            $this->upgrade_database_to_2_3();
        }
        
        if (version_compare($current_version, '2.4', '<')) {
            // Update database tables with improved structure including 'kick' action type
            self::create_database_tables();
            
            // Add admin notice about database update
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html__('VideoHub360:', 'videohub360') . '</strong> ' . esc_html__('Moderation database tables have been updated to fix enforcement issues. Moderation should now work correctly for both chat and streams.', 'videohub360') . '</p>';
                echo '</div>';
            });
        }
        
        // Upgrade to version 2.7 - Add video reactions and playlists
        if (version_compare($current_version, '2.7', '<')) {
            self::create_database_tables();
            
            // Add admin notice about database update
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html__('VideoHub360:', 'videohub360') . '</strong> ' . esc_html__('Database updated! Video reactions (Like/Dislike) and playlists are now available.', 'videohub360') . '</p>';
                echo '</div>';
            });
        }
        
        // Check for moderation columns only in admin context or during upgrade
        if (is_admin()) {
            $this->ensure_moderation_columns();
        }
    }
    
    /**
     * Ensure moderation table has required columns
     * Only runs in admin context and uses transient to avoid repeated checks
     */
    private function ensure_moderation_columns() {
        // Check if we've already verified columns in the last 24 hours
        $columns_verified = get_transient('videohub360_moderation_columns_verified');
        if ($columns_verified) {
            return; // Already verified recently
        }
        
        global $wpdb;
        $moderation_table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$moderation_table_name'") == $moderation_table_name;
        if (!$table_exists) {
            return; // Table doesn't exist yet, will be created on activation
        }
        
        // Check for missing columns
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME IN ('target_uid', 'target_ip')",
            DB_NAME,
            $moderation_table_name
        ));
        
        $existing_columns = array();
        foreach ($column_exists as $col) {
            $existing_columns[] = $col->COLUMN_NAME;
        }
        
        $added_column = false;
        
        // Add target_uid column if missing
        if (!in_array('target_uid', $existing_columns)) {
            $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_uid bigint(20) unsigned DEFAULT NULL");
            if ($result !== false) {
                $wpdb->query("ALTER TABLE $moderation_table_name ADD KEY target_uid (target_uid)");
                $added_column = true;
            }
        }
        
        // Add target_ip column if missing
        if (!in_array('target_ip', $existing_columns)) {
            $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_ip varchar(45) DEFAULT NULL");
            if ($result !== false) {
                $wpdb->query("ALTER TABLE $moderation_table_name ADD KEY target_ip (target_ip)");
                $added_column = true;
            }
        }
        
        // Set transient to avoid checking again for 24 hours
        set_transient('videohub360_moderation_columns_verified', true, DAY_IN_SECONDS);
        
        // Show admin notice if columns were added
        if ($added_column && is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>' . esc_html__('VideoHub360:', 'videohub360') . '</strong> ' . esc_html__('Moderation database has been automatically updated. Guest user moderation should now work correctly.', 'videohub360') . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Upgrade database to version 2.3 - Add private messaging support
     */
    private function upgrade_database_to_2_3() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'videohub360_chat_messages';
            
            // Check if the table exists
            $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            if (!$table_exists) {
                self::create_database_tables();
                return;
            }
            
            // Check if message_type column already exists
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SHOW COLUMNS FROM $table_name LIKE %s", 
                'message_type'
            ));
            
            if (empty($column_exists)) {
                // Add private messaging columns
                $result = $wpdb->query("ALTER TABLE $table_name 
                             ADD COLUMN message_type enum('public', 'private') DEFAULT 'public' AFTER message,
                             ADD COLUMN recipient_id bigint(20) unsigned DEFAULT NULL AFTER message_type,
                             ADD KEY message_type (message_type),
                             ADD KEY recipient_id (recipient_id)");
                
                if ($result === false) {
                    // Database error occurred but continue silently
                } else {
                    update_option('videohub360_chat_db_version', '2.3');
                    
                    // Add admin notice about private messaging
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible">';
                        echo '<p><strong>' . esc_html__('VideoHub360:', 'videohub360') . '</strong> ' . esc_html__('Private messaging feature has been added! Users can now send private messages through the chat system.', 'videohub360') . '</p>';
                        echo '</div>';
                    });
                }
            } else {
                // Columns already exist, just update version
                update_option('videohub360_chat_db_version', '2.3');
            }
            
        } catch (Exception $e) {
            videohub360_debug_log('VideoHub360 Error in ' . __METHOD__ . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Load essential utility functions for backward compatibility
     */
    private function load_utility_functions() {
        // Add global utility functions that templates and other code might need
        
        if (!function_exists('videohub360_get_post_views')) {
            function videohub360_get_post_views($post_id) {
                $count = get_post_meta($post_id, '_videohub360_post_views_count', true);
                return $count ? $count : 0;
            }
        }
        
        if (!function_exists('videohub360_compact_views')) {
            function videohub360_compact_views($views) {
                $views = intval($views);
                
                if ($views < 1000) {
                    return (string) $views;
                } elseif ($views < 1000000) {
                    if ($views % 1000 == 0) {
                        return ($views / 1000) . 'k';
                    } else {
                        $formatted = round($views / 1000, 1);
                        return ($formatted == floor($formatted)) ? floor($formatted) . 'k' : $formatted . 'k';
                    }
                } elseif ($views < 1000000000) {
                    if ($views % 1000000 == 0) {
                        return ($views / 1000000) . 'M';
                    } else {
                        $formatted = round($views / 1000000, 1);
                        return ($formatted == floor($formatted)) ? floor($formatted) . 'M' : $formatted . 'M';
                    }
                } else {
                    if ($views % 1000000000 == 0) {
                        return ($views / 1000000000) . 'B';
                    } else {
                        $formatted = round($views / 1000000000, 1);
                        return ($formatted == floor($formatted)) ? floor($formatted) . 'B' : $formatted . 'B';
                    }
                }
            }
        }
        
        if (!function_exists('videohub360_user_can_moderate')) {
            function videohub360_user_can_moderate($user_id = null, $post_id = 0) {
                if (!$user_id) {
                    $user_id = get_current_user_id();
                }
                
                if (!$user_id) {
                    return false;
                }
                
                if (user_can($user_id, 'manage_options') || user_can($user_id, 'moderate_comments')) {
                    return true;
                }
                
                if ($post_id) {
                    $post = get_post($post_id);
                    
                    if ($post && $post->post_type === 'videohub360') {
                        
                        if ((int) $post->post_author === (int) $user_id) {
                            return true;
                        }
                        
                        if (user_can($user_id, 'edit_post', $post_id)) {
                            return true;
                        }
                    }
                }
                
                return false;
            }
        }
        
        if (!function_exists('videohub360_user_is_host')) {
            function videohub360_user_is_host() {
                if (current_user_can('administrator')) {
                    return true;
                }
                return false;
            }
        }
        
        if (!function_exists('videohub360_check_user_moderation_status')) {
            function videohub360_check_user_moderation_status($user_id, $post_id) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
                
                // Check if table exists
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                if (!$table_exists) {
                    return array('status' => 'allowed');
                }
                
                // Get current time for comparison
                $current_mysql_time = current_time('mysql');
                
                // Check for active bans
                $ban_query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                     WHERE target_user_id = %d AND post_id = %d AND action_type = 'ban' 
                     AND is_active = 1 
                     ORDER BY created_at DESC LIMIT 1",
                    $user_id, $post_id
                );
                
                $ban = $wpdb->get_row($ban_query);
                
                if ($ban) {
                    return array('status' => 'banned', 'reason' => $ban->reason, 'data' => $ban);
                }
                
                // Check for active timeouts - use current_time('mysql') for consistency 
                $timeout_query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                     WHERE target_user_id = %d AND post_id = %d AND action_type = 'timeout' 
                     AND is_active = 1 AND expiration_time > %s
                     ORDER BY created_at DESC LIMIT 1",
                    $user_id, $post_id, $current_mysql_time
                );
                
                $timeout = $wpdb->get_row($timeout_query);
                
                if ($timeout) {
                    return array(
                        'status' => 'timeout', 
                        'reason' => $timeout->reason, 
                        'expires' => $timeout->expiration_time,
                        'data' => $timeout
                    );
                }
                
                return array('status' => 'allowed');
            }
            
            /**
             * Check user moderation status by Agora UID and IP address (for guest users)
             */
            function videohub360_check_uid_moderation_status($uid, $post_id, $ip_address = null) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'videohub360_moderation_actions';
                
                // Check if table exists
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                if (!$table_exists) {
                    return array('status' => 'allowed');
                }
                
                // Get current time for comparison
                $current_mysql_time = current_time('mysql');
                
                // Build WHERE clause for UID and/or IP
                $where_conditions = array();
                $where_params = array();
                
                if ($uid) {
                    $where_conditions[] = "target_uid = %d";
                    $where_params[] = $uid;
                }
                
                if ($ip_address) {
                    $where_conditions[] = "target_ip = %s";
                    $where_params[] = $ip_address;
                }
                
                // If no UID or IP provided, return allowed
                if (empty($where_conditions)) {
                    return array('status' => 'allowed');
                }
                
                $where_clause = '(' . implode(' OR ', $where_conditions) . ')';
                
                // Check for active bans by UID or IP
                $ban_query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                     WHERE $where_clause AND post_id = %d AND action_type = 'ban' 
                     AND is_active = 1 AND source_type = 'agora'
                     ORDER BY created_at DESC LIMIT 1",
                    array_merge($where_params, array($post_id))
                );
                
                $ban = $wpdb->get_row($ban_query);
                
                if ($ban) {
                    return array('status' => 'banned', 'reason' => $ban->reason, 'data' => $ban);
                }
                
                // Check for active timeouts by UID or IP
                $timeout_query = $wpdb->prepare(
                    "SELECT * FROM $table_name 
                     WHERE $where_clause AND post_id = %d AND action_type = 'timeout' 
                     AND is_active = 1 AND expiration_time > %s AND source_type = 'agora'
                     ORDER BY created_at DESC LIMIT 1",
                    array_merge($where_params, array($post_id, $current_mysql_time))
                );
                
                $timeout = $wpdb->get_row($timeout_query);
                
                if ($timeout) {
                    return array(
                        'status' => 'timeout', 
                        'reason' => $timeout->reason, 
                        'expires' => $timeout->expiration_time,
                        'data' => $timeout
                    );
                }
                
                return array('status' => 'allowed');
            }
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('videohub360', false, dirname(plugin_basename(dirname(__FILE__))) . '/languages/');
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Add database indexes for performance
        self::add_database_indexes();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('vh360_youtube_live_check');

        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create chat messages table only if it doesn't exist
        $chat_table_name = $wpdb->prefix . 'videohub360_chat_messages';
        
        // Check if table exists before creating
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $chat_table_name));
        
        if (!$table_exists) {
            $chat_sql = "CREATE TABLE $chat_table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                post_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                username varchar(60) NOT NULL,
                user_avatar text,
                message text NOT NULL,
                message_type enum('public', 'private') DEFAULT 'public',
                recipient_id bigint(20) unsigned DEFAULT NULL,
                reply_to bigint(20) unsigned DEFAULT NULL,
                is_pinned tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY user_id (user_id),
                KEY created_at (created_at),
                KEY message_type (message_type),
                KEY recipient_id (recipient_id),
                KEY reply_to (reply_to),
                KEY is_pinned (is_pinned)
            ) $charset_collate;";
            
            $wpdb->query($chat_sql);
        }
        
        // Create moderation actions table
        $moderation_table_name = $wpdb->prefix . 'videohub360_moderation_actions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$moderation_table_name'") == $moderation_table_name;
        
        if ($table_exists) {
            // For existing tables, add target_uid and target_ip columns if they don't exist
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME IN ('target_uid', 'target_ip')",
                DB_NAME,
                $moderation_table_name
            ));
            
            $existing_columns = array();
            foreach ($column_exists as $col) {
                $existing_columns[] = $col->COLUMN_NAME;
            }
            
            // Add target_uid column if it doesn't exist
            if (!in_array('target_uid', $existing_columns)) {
                // Try with AFTER clause first, fall back to without if it fails
                $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_uid bigint(20) unsigned DEFAULT NULL AFTER target_user_id");
                if ($result === false) {
                    // Try without AFTER clause
                    $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_uid bigint(20) unsigned DEFAULT NULL");
                }
                
                if ($result !== false) {
                    $wpdb->query("ALTER TABLE $moderation_table_name ADD KEY target_uid (target_uid)");
                }
            }
            
            // Add target_ip column if it doesn't exist
            if (!in_array('target_ip', $existing_columns)) {
                // Try with AFTER clause first, fall back to without if it fails
                $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_ip varchar(45) DEFAULT NULL AFTER target_uid");
                if ($result === false) {
                    // Try without AFTER clause
                    $result = $wpdb->query("ALTER TABLE $moderation_table_name ADD COLUMN target_ip varchar(45) DEFAULT NULL");
                }
                
                if ($result !== false) {
                    $wpdb->query("ALTER TABLE $moderation_table_name ADD KEY target_ip (target_ip)");
                }
            }
        }
        
        // Create table if it doesn't exist (for fresh installations)
        $moderation_sql = "CREATE TABLE IF NOT EXISTS $moderation_table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            target_user_id bigint(20) unsigned NOT NULL,
            target_uid bigint(20) unsigned DEFAULT NULL,
            target_ip varchar(45) DEFAULT NULL,
            moderator_user_id bigint(20) unsigned NOT NULL,
            message_id bigint(20) unsigned DEFAULT NULL,
            action_type enum('ban', 'timeout', 'kick', 'report') NOT NULL,
            source_type enum('chat', 'agora') DEFAULT 'chat',
            reason text,
            expiration_time datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY target_user_id (target_user_id),
            KEY target_uid (target_uid),
            KEY target_ip (target_ip),
            KEY moderator_user_id (moderator_user_id),
            KEY message_id (message_id),
            KEY action_type (action_type),
            KEY source_type (source_type),
            KEY expiration_time (expiration_time),
            KEY is_active (is_active),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute moderation table creation
        $result_moderation = dbDelta($moderation_sql);
        
        // Create video reactions table
        $reactions_table_name = $wpdb->prefix . 'vh360_video_reactions';
        
        $reactions_sql = "CREATE TABLE IF NOT EXISTS $reactions_table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            video_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            reaction enum('like','dislike') NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_video (video_id, user_id),
            KEY video_id (video_id),
            KEY user_id (user_id),
            KEY reaction (reaction),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result_reactions = dbDelta($reactions_sql);
        
        // Create playlists table
        $playlists_table_name = $wpdb->prefix . 'vh360_playlists';
        
        $playlists_sql = "CREATE TABLE IF NOT EXISTS $playlists_table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            privacy enum('private','unlisted','public') DEFAULT 'private',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY privacy (privacy),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result_playlists = dbDelta($playlists_sql);
        
        // Create playlist items table
        $playlist_items_table_name = $wpdb->prefix . 'vh360_playlist_items';
        
        $playlist_items_sql = "CREATE TABLE IF NOT EXISTS $playlist_items_table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            playlist_id bigint(20) unsigned NOT NULL,
            video_id bigint(20) unsigned NOT NULL,
            position int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_playlist_video (playlist_id, video_id),
            KEY playlist_id (playlist_id),
            KEY video_id (video_id),
            KEY position (position),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result_playlist_items = dbDelta($playlist_items_sql);
        
        // Set database version options
        update_option('videohub360_chat_db_version', '2.7');
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = array(
            'videohub360_post_slug' => 'videohub360',
            'videohub360_category_slug' => 'videohub360-category',
            'videohub360_location_slug' => 'videohub360-location',
            'videohub360_series_slug' => 'videohub360-series'
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (!get_option($option_name)) {
                update_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Get component instance
     * 
     * @param string $component Component name
     * @return object|null Component instance or null if not found
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
    
    /**
     * Handle view tracking for single videos
     */
    public function handle_view_tracking() {
        if (is_singular('videohub360')) {
            global $post;
            if ($post) {
                $this->set_post_views($post->ID);
            }
        }
    }
    
    /**
     * Set post views
     */
    private function set_post_views($post_id) {
        $count = get_post_meta($post_id, '_videohub360_post_views_count', true);
        $count = ($count) ? $count + 1 : 1;
        update_post_meta($post_id, '_videohub360_post_views_count', $count);
    }
    
    /**
     * Force archive template for VideoHub360 archives
     */
    public function force_archive_template($template) {
        if (!is_admin() && is_post_type_archive('videohub360')) {
            $plugin_template = VIDEOHUB360_TEMPLATES_DIR . 'archive-videohub360.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
    
    /**
     * Add database indexes for frequently queried meta fields
     */
    private static function add_database_indexes() {
        global $wpdb;
        
        // Add indexes for frequently queried meta fields
        $indexes = array(
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX vh360_type_idx (meta_key(20), meta_value(20))",
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX vh360_live_idx (meta_key(20), meta_value(10))", 
            "ALTER TABLE {$wpdb->postmeta} ADD INDEX vh360_views_idx (meta_key(30), meta_value(50))"
        );
        
        foreach ($indexes as $sql) {
            // Check if index already exists before creating
            $table_name = $wpdb->postmeta;
            $index_name = '';
            if (strpos($sql, 'vh360_type_idx') !== false) {
                $index_name = 'vh360_type_idx';
            } elseif (strpos($sql, 'vh360_live_idx') !== false) {
                $index_name = 'vh360_live_idx';
            } elseif (strpos($sql, 'vh360_views_idx') !== false) {
                $index_name = 'vh360_views_idx';
            }
            
            if ($index_name) {
                $index_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.statistics 
                     WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                    DB_NAME, 
                    $table_name, 
                    $index_name
                ));
                
                if (!$index_exists) {
                        $wpdb->last_error = '';
                        $wpdb->query($sql);

    // If the views index fails (e.g. key length limits on utf8mb4/LONGTEXT), retry with a safer index.
    if ($index_name === 'vh360_views_idx' && $wpdb->last_error) {
        // Retry with an even safer index on meta_key only (broad compatibility).
        $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX vh360_views_idx (meta_key(30))");
    }
}
            }
        }
    }
}