<?php
/**
 * VideoHub360 Import/Export Class
 * 
 * Handles video import and export functionality for transferring videos between WordPress sites
 * 
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VideoHub360_Import_Export {
    
    /**
     * Maximum file size for imports (in bytes)
     * Default: 10MB
     */
    const MAX_IMPORT_FILE_SIZE = 10485760; // 10 * 1024 * 1024
    
    /**
     * Transient expiration time for bulk exports (in seconds)
     * Default: 5 minutes
     */
    const EXPORT_TRANSIENT_EXPIRATION = 300;

    /**
     * Allowed course term meta keys for export and import.
     */
    const ALLOWED_COURSE_TERM_META = array(
        '_vh360_course_subtitle',
        '_vh360_course_short_description',
        '_vh360_course_level',
        '_vh360_course_duration',
        '_vh360_course_required_membership',
        '_vh360_course_cta_text',
        '_vh360_course_cta_url',
        '_vh360_course_order',
        '_vh360_course_instructor_user_id',
        '_vh360_course_owner_user_id',
        '_vh360_course_featured_image_id',
    );
    
    /**
     * Runtime cache of imported media URLs to attachment IDs.
     *
     * Prevents downloading the same image multiple times during one import.
     *
     * @var array
     */
    private $imported_media_cache = array();

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
        // Allow JSON file uploads for import
        add_filter('upload_mimes', array($this, 'allow_json_upload'));
        
        // AJAX handlers for export
        add_action('wp_ajax_vh360_export_videos', array($this, 'ajax_export_videos'));
        add_action('wp_ajax_vh360_export_all_videos', array($this, 'ajax_export_all_videos'));
        
        // AJAX handlers for import
        add_action('wp_ajax_vh360_import_videos', array($this, 'ajax_import_videos'));
        
        // Bulk action handlers
        add_filter('bulk_actions-edit-videohub360', array($this, 'add_bulk_export_action'));
        add_filter('handle_bulk_actions-edit-videohub360', array($this, 'handle_bulk_export'), 10, 3);
        add_action('admin_notices', array($this, 'bulk_export_admin_notice'));
    }
    
    /**
     * Allow JSON file uploads
     * 
     * @param array $mimes Existing MIME types
     * @return array Modified MIME types
     */
    public function allow_json_upload($mimes) {
        // Only allow for users with edit_posts capability
        if (current_user_can('edit_posts')) {
            $mimes['json'] = 'application/json';
        }
        return $mimes;
    }
    
    /**
     * Export a single video to JSON format
     * 
     * @param int $post_id Video post ID
     * @return array|WP_Error Video data array or error
     */
    public function export_video($post_id) {
        // Verify post exists and is a videohub360 post
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'videohub360') {
            return new WP_Error('invalid_post', __('Invalid video post ID', 'videohub360'));
        }
        
        // Get post data
        $post_data = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'post_modified' => $post->post_modified,
            'post_modified_gmt' => $post->post_modified_gmt,
            'slug' => $post->post_name,
        );
        
        // Get all meta data
        $meta_data = array(
            // Video URLs
            'video_url' => get_post_meta($post_id, 'video_url', true),
            'ad_video_url' => get_post_meta($post_id, 'ad_video_url', true),
            'midroll_ad_video_url' => get_post_meta($post_id, 'midroll_ad_video_url', true),
            'midroll_ad_timing' => get_post_meta($post_id, 'midroll_ad_timing', true),
            'postroll_ad_video_url' => get_post_meta($post_id, 'postroll_ad_video_url', true),
            'postroll_ad_enabled' => get_post_meta($post_id, 'postroll_ad_enabled', true),
            
            // Custom HTML
            'videohub360_custom_html' => get_post_meta($post_id, 'videohub360_custom_html', true),
            
            // View count
            '_videohub360_post_views_count' => get_post_meta($post_id, '_videohub360_post_views_count', true),
            
            // Ad click-through URLs
            '_vh360_ad_click_url' => get_post_meta($post_id, '_vh360_ad_click_url', true),
            '_vh360_midroll_ad_click_url' => get_post_meta($post_id, '_vh360_midroll_ad_click_url', true),
            '_vh360_postroll_ad_click_url' => get_post_meta($post_id, '_vh360_postroll_ad_click_url', true),
            
            // Livestream fields
            '_vh360_is_live' => get_post_meta($post_id, '_vh360_is_live', true),
            '_vh360_type' => get_post_meta($post_id, '_vh360_type', true),
            '_vh360_embed_code' => get_post_meta($post_id, '_vh360_embed_code', true),
            '_vh360_stream_url' => get_post_meta($post_id, '_vh360_stream_url', true),
            '_vh360_api_url' => get_post_meta($post_id, '_vh360_api_url', true),
            '_vh360_poster' => get_post_meta($post_id, '_vh360_poster', true),
            '_vh360_viewer_count' => get_post_meta($post_id, '_vh360_viewer_count', true),
            '_vh360_live_badge' => get_post_meta($post_id, '_vh360_live_badge', true),
            '_vh360_badge_text' => get_post_meta($post_id, '_vh360_badge_text', true),
            '_vh360_badge_color' => get_post_meta($post_id, '_vh360_badge_color', true),
            '_vh360_offline_message' => get_post_meta($post_id, '_vh360_offline_message', true),
            '_vh360_live_start_time' => get_post_meta($post_id, '_vh360_live_start_time', true),
            '_vh360_stream_stopped' => get_post_meta($post_id, '_vh360_stream_stopped', true),
            '_vh360_chat_enabled' => get_post_meta($post_id, '_vh360_chat_enabled', true),
            '_vh360_chat_placement' => get_post_meta($post_id, '_vh360_chat_placement', true),
            '_vh360_agora_channel_name' => get_post_meta($post_id, '_vh360_agora_channel_name', true),
            '_vh360_agora_mode' => get_post_meta($post_id, '_vh360_agora_mode', true),
            '_vh360_agora_everyone_is_host' => get_post_meta($post_id, '_vh360_agora_everyone_is_host', true),
            '_vh360_host_passcode' => get_post_meta($post_id, '_vh360_host_passcode', true),
            
            // Video quality settings
            '_vh360_video_quality' => get_post_meta($post_id, '_vh360_video_quality', true),
            '_vh360_video_mirror' => get_post_meta($post_id, '_vh360_video_mirror', true),
            '_vh360_override_quality_settings' => get_post_meta($post_id, '_vh360_override_quality_settings', true),
            
            // Sidebar configuration
            '_vh360_sidebar_config' => get_post_meta($post_id, '_vh360_sidebar_config', true),

            // Course lesson metadata
            '_vh360_lesson_module_title' => get_post_meta($post_id, '_vh360_lesson_module_title', true),
            '_vh360_lesson_module_number' => get_post_meta($post_id, '_vh360_lesson_module_number', true),
            '_vh360_lesson_number' => get_post_meta($post_id, '_vh360_lesson_number', true),
            '_vh360_lesson_duration' => get_post_meta($post_id, '_vh360_lesson_duration', true),
            '_vh360_lesson_resource_url' => get_post_meta($post_id, '_vh360_lesson_resource_url', true),
            '_vh360_lesson_resource_label' => get_post_meta($post_id, '_vh360_lesson_resource_label', true),
            '_vh360_lesson_is_preview' => get_post_meta($post_id, '_vh360_lesson_is_preview', true),
        );
        
        // Get taxonomies
        $taxonomies = array(
            'videohub360_category' => wp_get_post_terms($post_id, 'videohub360_category', array('fields' => 'all')),
            'videohub360_series' => wp_get_post_terms($post_id, 'videohub360_series', array('fields' => 'all')),
            'videohub360_location' => wp_get_post_terms($post_id, 'videohub360_location', array('fields' => 'all')),
            'videohub360_tag' => wp_get_post_terms($post_id, 'videohub360_tag', array('fields' => 'all')),
        );
        
        // Get featured image URL and portable attachment data.
        $featured_image_url = '';
        $featured_image     = null;

        if ( has_post_thumbnail( $post_id ) ) {
            $thumbnail_id       = get_post_thumbnail_id( $post_id );
            $featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );
            $featured_image     = $this->build_attachment_export_data( $thumbnail_id );
        }
        
        // Build video data array
        $video_data = array(
            'post_data'          => $post_data,
            'meta_data'          => $meta_data,
            'taxonomies'         => $taxonomies,

            // Backward-compatible simple URL.
            'featured_image_url' => $featured_image_url,

            // Portable full attachment data for new imports.
            'featured_image'     => $featured_image,
        );
        
        return $video_data;
    }
    
    /**
     * Export multiple videos to JSON format
     * 
     * @param array $post_ids Array of video post IDs
     * @return array Array of video data
     */
    public function export_videos($post_ids) {
        $videos = array();
        
        foreach ($post_ids as $post_id) {
            $video_data = $this->export_video($post_id);
            if (!is_wp_error($video_data)) {
                $videos[] = $video_data;
            }
        }
        
        return $videos;
    }
    
    /**
     * Generate properly formatted JSON export
     * 
     * @param array $videos_data Array of video data
     * @return string JSON string
     */
    public function generate_json_export($videos_data) {
        $current_user = wp_get_current_user();

        $course_terms = $this->collect_course_terms($videos_data);
        
        $export_data = array(
            'videohub360_export' => array(
                'version' => '1.0.0',
                'export_date' => current_time('mysql'),
                'exported_by' => $current_user->user_login,
                'videos' => $videos_data,
                'course_terms' => $course_terms,
            )
        );
        
        return wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Collect unique videohub360_series term data (including course term meta)
     * for all series used by the exported videos.
     *
     * @param array $videos_data Array of video data as returned by export_video().
     * @return array Array of course term data objects.
     */
    private function collect_course_terms($videos_data) {
        $seen_slugs = array();
        $course_terms = array();

        foreach ($videos_data as $video_data) {
            if (!isset($video_data['taxonomies']['videohub360_series'])) {
                continue;
            }

            $series_terms = $video_data['taxonomies']['videohub360_series'];
            if (!is_array($series_terms) || empty($series_terms)) {
                continue;
            }

            foreach ($series_terms as $term_obj) {
                $slug = $this->get_term_field($term_obj, 'slug');
                if (!$slug || isset($seen_slugs[$slug])) {
                    continue;
                }

                $term = get_term_by('slug', $slug, 'videohub360_series');
                if (!$term) {
                    continue;
                }

                $seen_slugs[$slug] = true;

                $meta_data = array();
                foreach (self::ALLOWED_COURSE_TERM_META as $meta_key) {
                    $meta_data[$meta_key] = get_term_meta($term->term_id, $meta_key, true);
                }

                $course_featured_image_id = absint( get_term_meta( $term->term_id, '_vh360_course_featured_image_id', true ) );
                $course_featured_image    = $course_featured_image_id ? $this->build_attachment_export_data( $course_featured_image_id ) : null;

                $course_terms[] = array(
                    'taxonomy'           => 'videohub360_series',
                    'name'               => $term->name,
                    'slug'               => $term->slug,
                    'description'        => $term->description,
                    'meta_data'          => $meta_data,

                    // Portable course image data.
                    'featured_image'     => $course_featured_image,

                    // Backward-compatible simple URL.
                    'featured_image_url' => $course_featured_image ? $course_featured_image['url'] : '',
                );
            }
        }

        return $course_terms;
    }

    /**
     * Build portable attachment export data.
     *
     * @param int $attachment_id Attachment ID.
     * @return array|null
     */
    private function build_attachment_export_data( $attachment_id ) {
        $attachment_id = absint( $attachment_id );

        if ( ! $attachment_id ) {
            return null;
        }

        $url = wp_get_attachment_url( $attachment_id );

        if ( ! $url ) {
            return null;
        }

        $attachment = get_post( $attachment_id );

        return array(
            'id'          => $attachment_id,
            'url'         => esc_url_raw( $url ),
            'filename'    => basename( get_attached_file( $attachment_id ) ),
            'title'       => $attachment ? $attachment->post_title : '',
            'caption'     => $attachment ? $attachment->post_excerpt : '',
            'description' => $attachment ? $attachment->post_content : '',
            'alt'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
            'mime_type'   => get_post_mime_type( $attachment_id ),
        );
    }

    /**
     * Import a remote image URL into the Media Library.
     *
     * @param string $url        Remote image URL.
     * @param int    $parent_id  Optional parent post ID.
     * @param array  $image_data Optional exported attachment metadata.
     * @return int|WP_Error Attachment ID on success.
     */
    private function import_remote_image( $url, $parent_id = 0, $image_data = array() ) {
        $url = esc_url_raw( trim( (string) $url ) );

        if ( empty( $url ) ) {
            return new WP_Error( 'vh360_empty_image_url', __( 'Empty image URL.', 'videohub360' ) );
        }

        if ( isset( $this->imported_media_cache[ $url ] ) ) {
            return absint( $this->imported_media_cache[ $url ] );
        }

        $parsed = wp_parse_url( $url );

        if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
            return new WP_Error( 'vh360_invalid_image_url', __( 'Invalid image URL scheme.', 'videohub360' ) );
        }

        // Check for an already-imported attachment by original source URL.
        $existing = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_vh360_import_source_url',
            'meta_value'     => $url,
        ) );

        if ( ! empty( $existing ) ) {
            $attachment_id = absint( $existing[0] );
            $this->imported_media_cache[ $url ] = $attachment_id;
            return $attachment_id;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url( $url, 30 );

        if ( is_wp_error( $tmp ) ) {
            return $tmp;
        }

        $filename = ! empty( $image_data['filename'] )
            ? sanitize_file_name( $image_data['filename'] )
            : sanitize_file_name( basename( wp_parse_url( $url, PHP_URL_PATH ) ) );

        if ( empty( $filename ) ) {
            $filename = 'vh360-imported-image.jpg';
        }

        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp,
        );

        $allowed_mimes = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
        );

        $filetype = wp_check_filetype_and_ext( $tmp, $filename, $allowed_mimes );

        if ( empty( $filetype['type'] ) || strpos( $filetype['type'], 'image/' ) !== 0 ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return new WP_Error( 'vh360_invalid_image_type', __( 'Remote file is not a supported image type.', 'videohub360' ) );
        }

        $attachment_id = media_handle_sideload( $file_array, absint( $parent_id ) );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            return $attachment_id;
        }

        update_post_meta( $attachment_id, '_vh360_import_source_url', $url );

        if ( ! empty( $image_data['alt'] ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $image_data['alt'] ) );
        }

        $attachment_update = array(
            'ID' => $attachment_id,
        );

        if ( ! empty( $image_data['title'] ) ) {
            $attachment_update['post_title'] = sanitize_text_field( $image_data['title'] );
        }

        if ( ! empty( $image_data['caption'] ) ) {
            $attachment_update['post_excerpt'] = sanitize_textarea_field( $image_data['caption'] );
        }

        if ( ! empty( $image_data['description'] ) ) {
            $attachment_update['post_content'] = wp_kses_post( $image_data['description'] );
        }

        if ( count( $attachment_update ) > 1 ) {
            wp_update_post( $attachment_update );
        }

        $this->imported_media_cache[ $url ] = absint( $attachment_id );

        return absint( $attachment_id );
    }

    /**
     * Safely read a named field from a term value that may be either an
     * associative array (from json_decode with assoc=true) or an object.
     *
     * @param array|object $term  Term data.
     * @param string       $field Field name.
     * @return string
     */
    private function get_term_field($term, $field) {
        if (is_array($term)) {
            return isset($term[$field]) ? $term[$field] : '';
        }
        if (is_object($term)) {
            return isset($term->$field) ? $term->$field : '';
        }
        return '';
    }
    
    /**
     * Validate import data structure
     * 
     * @param array $json_data Decoded JSON data
     * @return bool|WP_Error True if valid, WP_Error otherwise
     */
    public function validate_import_data($json_data) {
        // Check if data is an array
        if (!is_array($json_data)) {
            return new WP_Error('invalid_format', __('Invalid JSON format', 'videohub360'));
        }
        
        // Check for main structure
        if (!isset($json_data['videohub360_export'])) {
            return new WP_Error('invalid_structure', __('Missing videohub360_export key', 'videohub360'));
        }
        
        $export_data = $json_data['videohub360_export'];
        
        // Check required fields
        if (!isset($export_data['version']) || !isset($export_data['videos'])) {
            return new WP_Error('missing_fields', __('Missing required fields in export data', 'videohub360'));
        }
        
        // Check if videos is an array
        if (!is_array($export_data['videos'])) {
            return new WP_Error('invalid_videos', __('Videos data must be an array', 'videohub360'));
        }
        
        // Validate each video has required structure
        foreach ($export_data['videos'] as $index => $video) {
            if (!isset($video['post_data']) || !is_array($video['post_data'])) {
                return new WP_Error('invalid_video_data', sprintf(__('Invalid post data for video at index %d', 'videohub360'), $index));
            }
            
            if (!isset($video['post_data']['title'])) {
                return new WP_Error('missing_title', sprintf(__('Missing title for video at index %d', 'videohub360'), $index));
            }
        }
        
        return true;
    }
    
    /**
     * Handle duplicate video based on options
     * 
     * @param array $post_data Post data being imported (passed by reference)
     * @param string $duplicate_action Action to take: 'skip', 'update', 'create_new'
     * @return int|bool|WP_Error Post ID, false if no duplicate, or WP_Error
     */
    public function handle_duplicate(&$post_data, $duplicate_action = 'skip') {
        // Check for existing post by slug
        $existing_post = get_page_by_path($post_data['slug'], OBJECT, 'videohub360');
        
        // If no duplicate, return false to indicate new post should be created
        if (!$existing_post) {
            // Also check by title as fallback
            $args = array(
                'post_type' => 'videohub360',
                'title' => $post_data['title'],
                'posts_per_page' => 1,
                'post_status' => 'any',
            );
            $posts = get_posts($args);
            
            if (empty($posts)) {
                return false; // No duplicate found
            }
            
            $existing_post = $posts[0];
        }
        
        // Handle based on action
        switch ($duplicate_action) {
            case 'skip':
                return new WP_Error('duplicate_skipped', sprintf(__('Video "%s" already exists and was skipped', 'videohub360'), $post_data['title']));
                
            case 'update':
                // Return existing post ID to update it
                return $existing_post->ID;
                
            case 'create_new':
                // Modify slug to make it unique
                $base_slug = $post_data['slug'];
                $counter = 1;
                do {
                    $new_slug = $base_slug . '-' . $counter;
                    $check = get_page_by_path($new_slug, OBJECT, 'videohub360');
                    $counter++;
                } while ($check);
                
                $post_data['slug'] = $new_slug;
                return false; // Proceed with creation with modified slug
                
            default:
                return new WP_Error('invalid_action', __('Invalid duplicate action', 'videohub360'));
        }
    }
    
    /**
     * Import videos from JSON data
     * 
     * @param array $json_data Decoded JSON data
     * @param array $options Import options (duplicate_action, etc.)
     * @return array Success/error report
     */
    public function import_videos($json_data, $options = array()) {
        // Validate data first
        $validation = $this->validate_import_data($json_data);
        if (is_wp_error($validation)) {
            return array(
                'success' => false,
                'error' => $validation->get_error_message(),
            );
        }
        
        // Default options
        $defaults = array(
            'duplicate_action' => 'skip', // skip, update, create_new
        );
        $options = wp_parse_args($options, $defaults);
        
        $videos = $json_data['videohub360_export']['videos'];
        $results = array(
            'success' => true,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
            'warnings' => array(),
        );
        
        foreach ($videos as $index => $video_data) {
            try {
                $result = $this->import_single_video($video_data, $options);
                
                if (is_wp_error($result)) {
                    if ($result->get_error_code() === 'duplicate_skipped') {
                        $results['skipped']++;
                        $results['warnings'][] = $result->get_error_message();
                    } else {
                        $results['errors'][] = sprintf(__('Video %d: %s', 'videohub360'), $index + 1, $result->get_error_message());
                    }
                } elseif (isset($result['updated']) && $result['updated']) {
                    $results['updated']++;
                } else {
                    $results['imported']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = sprintf(__('Video %d: %s', 'videohub360'), $index + 1, $e->getMessage());
            }
        }

        // Import course term meta if present in the export.
        if (isset($json_data['videohub360_export']['course_terms']) && is_array($json_data['videohub360_export']['course_terms'])) {
            $this->import_course_terms($json_data['videohub360_export']['course_terms']);
        }
        
        return $results;
    }
    
    /**
     * Import a single video
     * 
     * @param array $video_data Video data to import
     * @param array $options Import options
     * @return int|array|WP_Error Post ID, array with 'updated' flag, or WP_Error
     */
    private function import_single_video($video_data, $options) {
        $post_data = $video_data['post_data'];
        
        // Check for duplicates
        $duplicate_check = $this->handle_duplicate($post_data, $options['duplicate_action']);
        
        if (is_wp_error($duplicate_check)) {
            return $duplicate_check; // Return error (likely skipped)
        }
        
        $updating = false;
        if ($duplicate_check !== false) {
            // We're updating an existing post
            $post_id = $duplicate_check;
            $updating = true;
            
            $post_args = array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($post_data['title']),
                'post_content' => wp_kses_post($post_data['content']),
                'post_excerpt' => sanitize_textarea_field($post_data['excerpt']),
                'post_status' => sanitize_key($post_data['status']),
            );
            
            $result = wp_update_post($post_args, true);
        } else {
            // Create new post
            $post_args = array(
                'post_type' => 'videohub360',
                'post_title' => sanitize_text_field($post_data['title']),
                'post_content' => wp_kses_post($post_data['content']),
                'post_excerpt' => sanitize_textarea_field($post_data['excerpt']),
                'post_status' => sanitize_key($post_data['status']),
                'post_name' => sanitize_title($post_data['slug']),
                'post_date' => sanitize_text_field($post_data['post_date']),
                'post_date_gmt' => sanitize_text_field($post_data['post_date_gmt']),
            );
            
            $result = wp_insert_post($post_args, true);
            $post_id = $result;
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Import meta data
        if (isset($video_data['meta_data']) && is_array($video_data['meta_data'])) {
            foreach ($video_data['meta_data'] as $meta_key => $meta_value) {
                // Sanitize meta values based on type
                $sanitized_value = $this->sanitize_meta_value($meta_key, $meta_value);
                update_post_meta($post_id, $meta_key, $sanitized_value);
            }
        }
        
        // Import taxonomies
        if (isset($video_data['taxonomies']) && is_array($video_data['taxonomies'])) {
            foreach ($video_data['taxonomies'] as $taxonomy => $terms) {
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                if (is_array($terms) && !empty($terms)) {
                    $term_ids = array();
                    
                    foreach ($terms as $term_data) {
                        // Support both associative arrays (json_decode true) and objects.
                        if (!is_array($term_data) && !is_object($term_data)) {
                            continue;
                        }

                        $term_slug = $this->get_term_field($term_data, 'slug');
                        $term_name = $this->get_term_field($term_data, 'name');
                        $term_desc = $this->get_term_field($term_data, 'description');

                        $term_slug = sanitize_title($term_slug);
                        $term_name = sanitize_text_field($term_name);
                        $term_desc = sanitize_textarea_field($term_desc);

                        if (!$term_slug || !$term_name) {
                            continue;
                        }

                        // Check if term exists
                        $term = get_term_by('slug', $term_slug, $taxonomy);
                        
                        if (!$term) {
                            // Create term if it doesn't exist
                            $new_term = wp_insert_term(
                                $term_name,
                                $taxonomy,
                                array(
                                    'slug'        => $term_slug,
                                    'description' => $term_desc,
                                )
                            );
                            
                            if (!is_wp_error($new_term)) {
                                $term_ids[] = (int) $new_term['term_id'];
                            }
                        } else {
                            $term_ids[] = (int) $term->term_id;
                        }
                    }
                    
                    // Assign terms to post
                    if (!empty($term_ids)) {
                        wp_set_object_terms($post_id, $term_ids, $taxonomy);
                    }
                }
            }
        }
        
        // Import and assign featured image if present.
        $image_url  = '';
        $image_data = array();

        if ( isset( $video_data['featured_image'] ) && is_array( $video_data['featured_image'] ) ) {
            $image_data = $video_data['featured_image'];
            $image_url  = isset( $image_data['url'] ) ? esc_url_raw( $image_data['url'] ) : '';
        }

        if ( empty( $image_url ) && ! empty( $video_data['featured_image_url'] ) ) {
            $image_url = esc_url_raw( $video_data['featured_image_url'] );
        }

        if ( ! empty( $image_url ) ) {
            $attachment_id = $this->import_remote_image( $image_url, $post_id, $image_data );

            if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }

        // Optionally localize video poster image URLs.
        $poster_url = get_post_meta( $post_id, '_vh360_poster', true );

        if ( ! empty( $poster_url ) && preg_match( '#^https?://#i', $poster_url ) ) {
            $poster_attachment_id = $this->import_remote_image( $poster_url, $post_id );

            if ( ! is_wp_error( $poster_attachment_id ) && $poster_attachment_id ) {
                $local_poster_url = wp_get_attachment_url( $poster_attachment_id );

                if ( $local_poster_url ) {
                    update_post_meta( $post_id, '_vh360_poster', esc_url_raw( $local_poster_url ) );
                }
            }
        }
        
        if ($updating) {
            return array('updated' => true, 'post_id' => $post_id);
        }
        
        return $post_id;
    }
    
    /**
     * Import course term meta from exported course_terms data.
     *
     * @param array $course_terms Array of course term data from JSON.
     */
    private function import_course_terms($course_terms) {
        // URL meta keys within the allowed set.
        $url_meta_keys = array('_vh360_course_cta_url');

        // Numeric (integer) meta keys within the allowed set.
        $int_meta_keys = array(
            '_vh360_course_order',
            '_vh360_course_instructor_user_id',
            '_vh360_course_owner_user_id',
        );

        foreach ($course_terms as $course_term_data) {
            if (!is_array($course_term_data)) {
                continue;
            }

            $taxonomy = isset($course_term_data['taxonomy']) ? sanitize_key($course_term_data['taxonomy']) : 'videohub360_series';
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $slug = sanitize_title(isset($course_term_data['slug']) ? $course_term_data['slug'] : '');
            $name = sanitize_text_field(isset($course_term_data['name']) ? $course_term_data['name'] : '');
            $desc = sanitize_textarea_field(isset($course_term_data['description']) ? $course_term_data['description'] : '');

            if (!$slug || !$name) {
                continue;
            }

            $term = get_term_by('slug', $slug, $taxonomy);

            if (!$term) {
                $new_term = wp_insert_term(
                    $name,
                    $taxonomy,
                    array(
                        'slug'        => $slug,
                        'description' => $desc,
                    )
                );

                if (is_wp_error($new_term)) {
                    continue;
                }

                $term_id = (int) $new_term['term_id'];
            } else {
                $term_id = (int) $term->term_id;
            }

            // Import course term meta.
            if (!isset($course_term_data['meta_data']) || !is_array($course_term_data['meta_data'])) {
                continue;
            }

            foreach ($course_term_data['meta_data'] as $meta_key => $meta_value) {
                if (!in_array($meta_key, self::ALLOWED_COURSE_TERM_META, true)) {
                    continue;
                }

                // Do not import source-site attachment IDs directly.
                // Course featured image ID must be remapped after sideloading.
                if ( $meta_key === '_vh360_course_featured_image_id' ) {
                    continue;
                }

                if (in_array($meta_key, $url_meta_keys, true)) {
                    $sanitized = esc_url_raw((string) $meta_value);
                } elseif (in_array($meta_key, $int_meta_keys, true)) {
                    $sanitized = absint($meta_value);
                } elseif ($meta_key === '_vh360_course_short_description') {
                    $sanitized = sanitize_textarea_field((string) $meta_value);
                } else {
                    $sanitized = sanitize_text_field((string) $meta_value);
                }

                update_term_meta($term_id, $meta_key, $sanitized);
            }

            // Import and remap course featured image.
            $course_image_url  = '';
            $course_image_data = array();

            if ( isset( $course_term_data['featured_image'] ) && is_array( $course_term_data['featured_image'] ) ) {
                $course_image_data = $course_term_data['featured_image'];
                $course_image_url  = isset( $course_image_data['url'] ) ? esc_url_raw( $course_image_data['url'] ) : '';
            }

            if ( empty( $course_image_url ) && ! empty( $course_term_data['featured_image_url'] ) ) {
                $course_image_url = esc_url_raw( $course_term_data['featured_image_url'] );
            }

            if ( ! empty( $course_image_url ) ) {
                $attachment_id = $this->import_remote_image( $course_image_url, 0, $course_image_data );

                if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
                    update_term_meta( $term_id, '_vh360_course_featured_image_id', absint( $attachment_id ) );
                }
            }
        }
    }

    /**
     * Sanitize meta value based on key
     * 
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return mixed Sanitized value
     */
    private function sanitize_meta_value($meta_key, $meta_value) {
        // URL fields
        $url_fields = array(
            'video_url',
            'ad_video_url',
            'midroll_ad_video_url',
            'postroll_ad_video_url',
            '_vh360_ad_click_url',
            '_vh360_midroll_ad_click_url',
            '_vh360_postroll_ad_click_url',
            '_vh360_stream_url',
            '_vh360_api_url',
            '_vh360_poster',
            '_vh360_lesson_resource_url',
        );
        
        if (in_array($meta_key, $url_fields)) {
            return esc_url_raw($meta_value);
        }
        
        // Numeric fields
        $numeric_fields = array(
            '_videohub360_post_views_count',
            '_vh360_lesson_module_number',
            '_vh360_lesson_number',
        );

        if (in_array($meta_key, $numeric_fields, true)) {
            return absint($meta_value);
        }
        
        // HTML/text areas
        if ($meta_key === 'videohub360_custom_html' || $meta_key === '_vh360_embed_code') {
            return vh360_sanitize_embed_code($meta_value);
        }
        
        // Array/JSON fields
        if ($meta_key === '_vh360_sidebar_config') {
            if (is_array($meta_value)) {
                return $meta_value;
            }
            return maybe_unserialize($meta_value);
        }

        // Lesson boolean flag: normalize to 'yes' or 'no'.
        if ($meta_key === '_vh360_lesson_is_preview') {
            $val = sanitize_key((string) $meta_value);
            return $val === 'yes' ? 'yes' : 'no';
        }

        // Plain-text lesson fields.
        $lesson_text_fields = array(
            '_vh360_lesson_module_title',
            '_vh360_lesson_duration',
            '_vh360_lesson_resource_label',
        );

        if (in_array($meta_key, $lesson_text_fields, true)) {
            return sanitize_text_field($meta_value);
        }
        
        // Default: sanitize as text
        return sanitize_text_field($meta_value);
    }
    
    /**
     * AJAX handler for exporting videos
     */
    public function ajax_export_videos() {
        // Verify nonce
        check_ajax_referer('vh360_import_export_nonce', 'nonce');
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360')));
        }
        
        // Get post IDs
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => __('No videos selected', 'videohub360')));
        }
        
        // Export videos
        $videos_data = $this->export_videos($post_ids);
        $json = $this->generate_json_export($videos_data);
        
        wp_send_json_success(array(
            'json' => $json,
            'count' => count($videos_data),
        ));
    }
    
    /**
     * AJAX handler for exporting all videos
     */
    public function ajax_export_all_videos() {
        // Verify nonce
        check_ajax_referer('vh360_import_export_nonce', 'nonce');
        
        // Check capabilities (consistent with single video export)
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360')));
        }
        
        // Get all published videos
        $args = array(
            'post_type' => 'videohub360',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );
        
        $post_ids = get_posts($args);
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => __('No videos found to export', 'videohub360')));
        }
        
        // Export videos
        $videos_data = $this->export_videos($post_ids);
        $json = $this->generate_json_export($videos_data);
        
        wp_send_json_success(array(
            'json' => $json,
            'count' => count($videos_data),
        ));
    }
    
    /**
     * AJAX handler for importing videos
     */
    public function ajax_import_videos() {
        // Verify nonce
        check_ajax_referer('vh360_import_export_nonce', 'nonce');
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'videohub360')));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No file uploaded or upload error', 'videohub360')));
        }
        
        // Validate file type - check both extension and MIME type
        $filename = $_FILES['import_file']['name'];
        $file_type = wp_check_filetype($filename, array('json' => 'application/json'));
        
        // Also check the extension directly as a fallback
        $filename_parts = explode('.', $filename);
        $extension = strtolower(end($filename_parts));
        
        if ($file_type['ext'] !== 'json' && $extension !== 'json') {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a JSON file.', 'videohub360')));
        }
        
        // Validate file size to prevent memory exhaustion
        if ($_FILES['import_file']['size'] > self::MAX_IMPORT_FILE_SIZE) {
            $max_size_mb = self::MAX_IMPORT_FILE_SIZE / (1024 * 1024);
            wp_send_json_error(array('message' => sprintf(__('File is too large. Maximum size is %dMB.', 'videohub360'), $max_size_mb)));
        }
        
        // Read file contents
        $json_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $json_data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid JSON format', 'videohub360')));
        }
        
        // Get import options
        $options = array(
            'duplicate_action' => isset($_POST['duplicate_action']) ? sanitize_key($_POST['duplicate_action']) : 'skip',
        );
        
        // Import videos
        $results = $this->import_videos($json_data, $options);
        
        if ($results['success']) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error($results);
        }
    }
    
    /**
     * Add bulk export action to videos list
     * 
     * @param array $actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function add_bulk_export_action($actions) {
        $actions['vh360_export'] = __('Export Selected', 'videohub360');
        return $actions;
    }
    
    /**
     * Handle bulk export action
     * 
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being taken
     * @param array $post_ids Selected post IDs
     * @return string Modified redirect URL
     */
    public function handle_bulk_export($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'vh360_export') {
            return $redirect_to;
        }
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }
        
        // Export videos
        $videos_data = $this->export_videos($post_ids);
        $json = $this->generate_json_export($videos_data);
        
        // Store in transient for download
        $transient_key = 'vh360_bulk_export_' . wp_get_current_user()->ID;
        set_transient($transient_key, $json, self::EXPORT_TRANSIENT_EXPIRATION);
        
        // Add query arg to trigger download
        $redirect_to = add_query_arg(
            array(
                'vh360_bulk_export' => 'success',
                'vh360_export_count' => count($videos_data),
                'vh360_export_key' => $transient_key,
            ),
            $redirect_to
        );
        
        return $redirect_to;
    }
    
    /**
     * Display admin notice after bulk export
     */
    public function bulk_export_admin_notice() {
        if (!isset($_GET['vh360_bulk_export']) || $_GET['vh360_bulk_export'] !== 'success') {
            return;
        }
        
        $count = isset($_GET['vh360_export_count']) ? intval($_GET['vh360_export_count']) : 0;
        $transient_key = isset($_GET['vh360_export_key']) ? sanitize_key($_GET['vh360_export_key']) : '';
        
        if ($count > 0 && $transient_key) {
            $json = get_transient($transient_key);
            
            if ($json) {
                // Output download script
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php 
                        printf(
                            _n('%d video exported successfully.', '%d videos exported successfully.', $count, 'videohub360'),
                            $count
                        );
                        ?>
                    </p>
                </div>
                <script>
                    (function() {
                        var json = <?php echo wp_json_encode($json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                        var blob = new Blob([json], {type: 'application/json'});
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'videohub360-export-<?php echo esc_attr(date('Y-m-d-His')); ?>.json';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    })();
                </script>
                <?php
                
                // Delete transient
                delete_transient($transient_key);
            }
        }
    }
}
