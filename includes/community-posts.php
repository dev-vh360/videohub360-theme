<?php
/**
 * Community Posts system
 *
 * Provides a simple social posting feature for the Videohub360 Theme. Users can
 * create status updates with optional images, like posts, and view a feed of
 * community posts on the activity page. Posts are stored as a custom post
 * type and leverage WordPress comments for discussion. Likes are stored in
 * post meta and user meta.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register the community post type.
 *
 * Posts are stored as a public custom post type named `vh360_post`. Each
 * community post supports a title, content, featured image, comments and
 * author attribution. Posts are displayed on the activity feed and user
 * profiles.
 */
function vh360_register_community_post_type() {
    $labels = array(
        'name'               => __('Community Posts', 'videohub360-theme'),
        'singular_name'      => __('Community Post', 'videohub360-theme'),
        'add_new'            => __('Add New', 'videohub360-theme'),
        'add_new_item'       => __('Add New Community Post', 'videohub360-theme'),
        'edit_item'          => __('Edit Community Post', 'videohub360-theme'),
        'new_item'           => __('New Community Post', 'videohub360-theme'),
        'view_item'          => __('View Community Post', 'videohub360-theme'),
        'search_items'       => __('Search Community Posts', 'videohub360-theme'),
        'not_found'          => __('No community posts found', 'videohub360-theme'),
        'not_found_in_trash' => __('No community posts found in Trash', 'videohub360-theme'),
        'all_items'          => __('All Community Posts', 'videohub360-theme'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_in_rest'       => true,
        'supports'           => array('title', 'editor', 'thumbnail', 'comments', 'author'),
        'rewrite'            => array('slug' => 'community-post'),
        'has_archive'        => true,
        'menu_position'      => 30,
        'menu_icon'          => 'dashicons-format-status',
    );

    register_post_type('vh360_post', $args);
}
add_action('init', 'vh360_register_community_post_type');

/**
 * Safely refresh rewrite rules after community post rewrite changes.
 *
 * The community post type must already be registered before flushing, and the
 * stored version prevents an expensive rewrite flush on every request.
 */
function vh360_maybe_flush_community_post_rewrites() {
    $rewrite_version = '2026_06_community_post_single_routes';

    if (get_option('vh360_community_post_rewrite_version') === $rewrite_version) {
        return;
    }

    if (!post_type_exists('vh360_post')) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('vh360_community_post_rewrite_version', $rewrite_version, false);
}
add_action('init', 'vh360_maybe_flush_community_post_rewrites', 20);

/**
 * Keep published community posts commentable on single views.
 *
 * Older Activity Feed-created posts may have a closed comment_status because
 * the AJAX feed UI did not depend on WordPress' comments_open() check. Native
 * comments on single-vh360_post.php do use that check, so treat published,
 * non-password-protected community posts as open without affecting any other
 * post type or non-public post status.
 *
 * @param bool $open Whether comments are open for the post.
 * @param int  $post_id Post ID.
 * @return bool
 */
function vh360_community_post_comments_open($open, $post_id) {
    $post = get_post($post_id);

    if (!$post || 'vh360_post' !== $post->post_type) {
        return $open;
    }

    if ('publish' !== $post->post_status || !empty($post->post_password)) {
        return $open;
    }

    return true;
}
add_filter('comments_open', 'vh360_community_post_comments_open', 10, 2);

/**
 * Fire custom action when native WordPress comments are added.
 *
 * This bridges the native comment_post hook to our custom vh360_comment_created
 * action, which can be used by other parts of the theme (like notifications).
 *
 * @param int $comment_id Comment ID.
 * @param int|string $comment_approved Comment approval status.
 * @param array $commentdata Comment data array.
 */
function vh360_trigger_comment_notification($comment_id, $comment_approved, $commentdata) {
    // Handle all WordPress comment approval states: 1, '1', 'approve', 'approved'
    $approved_states = array(1, '1', 'approve', 'approved');
    if (in_array($comment_approved, $approved_states, false)) {
        $comment = get_comment($comment_id);
        if ($comment) {
            do_action('vh360_comment_created', $comment_id, $comment->comment_post_ID);
        }
    }
}
add_action('comment_post', 'vh360_trigger_comment_notification', 10, 3);

/**
 * Get community upload settings with defaults.
 *
 * @return array Upload settings array.
 */
function vh360_get_community_upload_settings() {
    $defaults = array(
        'enable_photos' => true,
        'enable_videos' => false,
        'photo_max_size' => 5, // MB
        'video_max_size' => 50, // MB
        'allowed_video_formats' => array('mp4'),
    );
    
    $settings = get_option('vh360_community_uploads_options', array());
    return wp_parse_args($settings, $defaults);
}

/**
 * Check if file upload is allowed based on settings.
 *
 * Validates the uploaded file against admin-configured settings for
 * community post uploads. Checks file type and size restrictions.
 *
 * @param string $file_type The MIME type of the uploaded file.
 * @param int    $file_size The size of the uploaded file in bytes.
 * @return array Array with 'allowed' (bool) and 'message' (string) keys.
 */
function vh360_validate_post_upload($file_type, $file_size) {
    $settings = vh360_get_community_upload_settings();
    $result = array(
        'allowed' => false,
        'message' => '',
    );
    
    // Determine if this is an image or video based on MIME type
    $is_image = strpos($file_type, 'image/') === 0;
    $is_video = strpos($file_type, 'video/') === 0;
    
    if ($is_image) {
        // Check if photo uploads are enabled
        if (empty($settings['enable_photos'])) {
            $result['message'] = __('Photo uploads are currently disabled.', 'videohub360-theme');
            return $result;
        }
        
        // Check file size
        $max_size_bytes = absint($settings['photo_max_size']) * 1024 * 1024;
        if ($file_size > $max_size_bytes) {
            $result['message'] = sprintf(
                /* translators: %d: maximum file size in MB */
                __('Photo exceeds maximum size of %d MB.', 'videohub360-theme'),
                absint($settings['photo_max_size'])
            );
            return $result;
        }
        
        $result['allowed'] = true;
        return $result;
    }
    
    if ($is_video) {
        // Check if video uploads are enabled
        if (empty($settings['enable_videos'])) {
            $result['message'] = __('Video uploads are currently disabled.', 'videohub360-theme');
            return $result;
        }
        
        // Check file size
        $max_size_bytes = absint($settings['video_max_size']) * 1024 * 1024;
        if ($file_size > $max_size_bytes) {
            $result['message'] = sprintf(
                /* translators: %d: maximum file size in MB */
                __('Video exceeds maximum size of %d MB.', 'videohub360-theme'),
                absint($settings['video_max_size'])
            );
            return $result;
        }
        
        // Check video format
        $allowed_formats = $settings['allowed_video_formats'];
        $extension = '';
        
        // Map MIME types to extensions
        $mime_to_ext = array(
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogv',
        );
        
        if (isset($mime_to_ext[$file_type])) {
            $extension = $mime_to_ext[$file_type];
        }
        
        if (empty($extension) || !in_array($extension, $allowed_formats, true)) {
            $result['message'] = sprintf(
                /* translators: %s: comma-separated list of allowed formats */
                __('Video format not allowed. Allowed formats: %s', 'videohub360-theme'),
                strtoupper(implode(', ', $allowed_formats))
            );
            return $result;
        }
        
        $result['allowed'] = true;
        return $result;
    }
    
    // Unsupported file type
    $result['message'] = __('Unsupported file type.', 'videohub360-theme');
    return $result;
}

/**
 * Handle front‑end community post creation.
 *
 * This function is hooked to the `admin_post_vh360_create_post` action so
 * WordPress will call it when a form is submitted with action="vh360_create_post".
 * It performs nonce verification, sanitizes input, creates a new post, handles
 * the featured image upload and redirects back to the referring page.
 */
function vh360_handle_post_creation() {
    // Only logged in users can post
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in to post.', 'videohub360-theme'));
    }

    // Verify nonce
    if (!isset($_POST['vh360_post_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_post_nonce'])), 'vh360_create_post')) {
        wp_die(__('Security check failed.', 'videohub360-theme'));
    }

    $user_id = get_current_user_id();
    $post_content = isset($_POST['vh360_post_content']) ? wp_kses_post(trim(wp_unslash($_POST['vh360_post_content']))) : '';
    $activity_video_asset_uuid = isset($_POST['vh360_activity_video_asset_uuid']) ? sanitize_text_field(wp_unslash($_POST['vh360_activity_video_asset_uuid'])) : '';
    $has_media = !empty($_FILES['vh360_post_media']['name']) || !empty($activity_video_asset_uuid);

    // Do not allow posts without content or media
    if (empty($post_content) && !$has_media) {
        $redirect_url = wp_get_referer();

        if (!$redirect_url) {
            $redirect_url = get_permalink(get_option('page_on_front'));
            if (!$redirect_url) {
                $redirect_url = home_url('/');
            }
        }

        wp_safe_redirect($redirect_url);
        exit;
    }

    // Insert the post
    $post_args = array(
        'post_type'      => 'vh360_post',
        'post_title'     => !empty($post_content) ? wp_trim_words($post_content, 10, '') : __('Media Post', 'videohub360-theme'),
        'post_content'   => $post_content,
        'post_status'    => 'publish',
        'post_author'    => $user_id,
        'comment_status' => 'open',
    );
    $post_id = wp_insert_post($post_args);

    // Handle media upload (photo or video)
    if ($post_id && $activity_video_asset_uuid && class_exists('VH360_Studio_Plugin')) {
        $asset = VH360_Studio_Plugin::instance()->video_storage()->associate_asset($activity_video_asset_uuid, $post_id, 'vh360_post');
        if (!is_wp_error($asset) && !empty($asset['id'])) {
            update_post_meta($post_id, '_vh360_studio_video_asset_id', absint($asset['id']));
            update_post_meta($post_id, 'vh360_post_media_type', 'video');
        }
    }

    if ($post_id && empty($activity_video_asset_uuid) && !empty($_FILES['vh360_post_media']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        $file_info = wp_check_filetype($_FILES['vh360_post_media']['name']);
        $file_type = '';
        $tmp_file = isset($_FILES['vh360_post_media']['tmp_name']) ? $_FILES['vh360_post_media']['tmp_name'] : '';
        
        // Verify the uploaded file is legitimate and get the actual MIME type
        if (!empty($tmp_file) && is_uploaded_file($tmp_file) && function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected_type = finfo_file($finfo, $tmp_file);
                finfo_close($finfo);
                if ($detected_type !== false) {
                    $file_type = $detected_type;
                }
            }
        }
        
        // Fallback to wp_check_filetype if finfo not available
        if (empty($file_type)) {
            $file_type = $file_info['type'];
        }
        
        $file_size = isset($_FILES['vh360_post_media']['size']) ? absint($_FILES['vh360_post_media']['size']) : 0;
        
        // Validate the upload
        $validation = vh360_validate_post_upload($file_type, $file_size);
        
        if ($validation['allowed']) {
            $file_id = media_handle_upload('vh360_post_media', $post_id);
            
            if (!is_wp_error($file_id)) {
                // Determine if this is a video or image
                $is_video = strpos($file_type, 'video/') === 0;
                
                if ($is_video) {
                    // Store video as post meta (not as featured image)
                    update_post_meta($post_id, '_vh360_video_attachment', $file_id);
                    // Store media type and ID for filtering
                    update_post_meta($post_id, 'vh360_post_media_type', 'video');
                    update_post_meta($post_id, 'vh360_post_media_id', $file_id);
                } else {
                    // Set as featured image for photos
                    set_post_thumbnail($post_id, $file_id);
                    // Store media type and ID for filtering
                    update_post_meta($post_id, 'vh360_post_media_type', 'photo');
                    update_post_meta($post_id, 'vh360_post_media_id', $file_id);
                }
            }
        } elseif (!empty($validation['message'])) {
            // Store validation error message in transient for user feedback
            set_transient('vh360_upload_error_' . $user_id, $validation['message'], 30);
        }
    }

    // Redirect back to where the user came from
    $redirect_url = wp_get_referer();

    if (!$redirect_url) {
        $redirect_url = get_permalink(get_option('page_on_front'));
        if (!$redirect_url) {
            $redirect_url = home_url('/');
        }
    }

    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_vh360_create_post', 'vh360_handle_post_creation');
add_action('admin_post_nopriv_vh360_create_post', 'vh360_handle_post_creation');

/**
 * AJAX handler to toggle like on a community post.
 *
 * Sends JSON success with new like count or error message. Likes are stored
 * in post meta (`vh360_likes`) as an array of user IDs. Each user can like
 * a post only once. Users must be logged in.
 */
function vh360_ajax_toggle_like() {
    // Check user
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to like posts.', 'videohub360-theme')));
    }
    $user_id = get_current_user_id();

    // Verify nonce - accept both vh360_activity_nonce (new) and vh360_like_post (legacy) for backwards compatibility
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    $nonce_valid = wp_verify_nonce($nonce, 'vh360_activity_nonce') || wp_verify_nonce($nonce, 'vh360_like_post');
    if (!$nonce_valid) {
        wp_send_json_error(array('message' => __('Security check failed.', 'videohub360-theme')));
    }

    // Get post ID
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'vh360_post') {
        wp_send_json_error(array('message' => __('Invalid post.', 'videohub360-theme')));
    }

    // Get current likes
    $likes = get_post_meta($post_id, 'vh360_likes', true);
    if (!is_array($likes)) {
        $likes = array();
    }

    // Toggle like
    $liked = false;
    if (in_array($user_id, $likes)) {
        // Unlike
        $likes = array_diff($likes, array($user_id));
    } else {
        // Like
        $likes[] = $user_id;
        $liked = true;
        
        // Trigger notification action for new like
        do_action('vh360_post_liked', $post_id, $user_id);
    }
    update_post_meta($post_id, 'vh360_likes', $likes);

    // Send response with updated count and status
    wp_send_json_success(array(
        'count' => count($likes),
        'liked' => $liked,
    ));
}
add_action('wp_ajax_vh360_toggle_like', 'vh360_ajax_toggle_like');
add_action('wp_ajax_nopriv_vh360_toggle_like', 'vh360_ajax_toggle_like');

/**
 * AJAX handler to share a community post (Facebook-style).
 *
 * Creates a new post that references the original via meta, with optional
 * user comment. The shared post displays the original post as an embedded card.
 */
function vh360_ajax_share_post() {
    // Check user
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to share posts.', 'videohub360-theme')));
    }
    $user_id = get_current_user_id();

    // Verify nonce - using vh360_activity_nonce to match what's localized in enqueue-manager.php
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'vh360_activity_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'videohub360-theme')));
    }

    // Get post ID and optional share comment
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $share_comment = isset($_POST['share_comment']) ? wp_kses_post(trim(wp_unslash($_POST['share_comment']))) : '';
    $share_type = isset($_POST['share_type']) ? sanitize_text_field($_POST['share_type']) : 'timeline';
    
    if (!$post_id || get_post_type($post_id) !== 'vh360_post') {
        wp_send_json_error(array('message' => __('Invalid post.', 'videohub360-theme')));
    }

    // Get original post
    $original = get_post($post_id);
    if (!$original) {
        wp_send_json_error(array('message' => __('Post not found.', 'videohub360-theme')));
    }

    // Create new shared post with user's comment (if any)
    $post_title = !empty($share_comment) 
        ? wp_trim_words($share_comment, 10, '') 
        : __('Shared a post', 'videohub360-theme');
        
    $new_post_args = array(
        'post_type'      => 'vh360_post',
        'post_status'    => 'publish',
        'post_author'    => $user_id,
        'post_title'     => $post_title,
        'post_content'   => $share_comment, // User's comment, not original content
        'comment_status' => 'open',
    );
    $new_post_id = wp_insert_post($new_post_args);
    if (!$new_post_id) {
        wp_send_json_error(array('message' => __('Could not share the post.', 'videohub360-theme')));
    }

    // Store share metadata
    update_post_meta($new_post_id, 'vh360_shared_from', $post_id);
    update_post_meta($new_post_id, 'vh360_share_comment', $share_comment);
    update_post_meta($new_post_id, 'vh360_share_type', $share_type);

    // Increment share count on original post (with atomic update for thread safety)
    $current_count = (int) get_post_meta($post_id, 'vh360_share_count', true);
    $new_share_count = $current_count + 1;
    
    // Use update_post_meta which provides atomic updates in WordPress
    if (class_exists('VH360_Post_Shares')) {
        $new_share_count = VH360_Post_Shares::increment($post_id);
    } else {
        update_post_meta($post_id, 'vh360_share_count', $new_share_count);
    }

    // Render the new shared post HTML
    $new_post = get_post($new_post_id);
    ob_start();
    vh360_render_community_post($new_post, true);
    $post_html = ob_get_clean();

    // Success
    wp_send_json_success(array(
        'message' => __('Post shared successfully.', 'videohub360-theme'),
        'post_html' => $post_html,
        'share_count' => $new_share_count,
        'post_id' => $post_id,
    ));
}
add_action('wp_ajax_vh360_share_post', 'vh360_ajax_share_post');
add_action('wp_ajax_nopriv_vh360_share_post', 'vh360_ajax_share_post');

/**
 * Get community posts for the activity feed.
 *
 * @param array $args Optional query arguments.
 * @return array Array of WP_Post objects.
 */
/**
 * Retrieve community posts for the feed.
 *
 * This helper wraps WP_Query and provides sensible defaults for paged
 * retrieval of the custom post type used for community posts. An optional
 * parameter allows narrowing the query to only posts authored by users
 * the current user follows. When `following_only` is true, the query
 * will look up the current user's list of followed user IDs and
 * constrain the result set to posts from those authors. If the user is
 * not logged in or has no followed users, the query falls back to
 * returning all posts.
 *
 * @param array $args {
 *     Optional arguments to customise the feed query.
 *
 *     @type int  $posts_per_page Number of posts to return. Default 20.
 *     @type int  $paged          Page number for pagination. Default 1.
 *     @type bool $following_only Whether to limit results to followed authors. Default false.
 * }
 * @return WP_Post[] An array of post objects.
 */
function vh360_get_community_posts($args = array()) {
    $defaults = array(
        'posts_per_page' => 20,
        'paged'          => 1,
        'following_only' => false,
    );
    $args = wp_parse_args($args, $defaults);

    $query_args = array(
        'post_type'      => 'vh360_post',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $args['posts_per_page'],
        'paged'          => (int) $args['paged'],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows' => true,
    );

    // If the feed should show only posts from authors the current user follows,
    // gather the list of author IDs and apply an author__in filter. When the
    // current user is not logged in or has no followed users, the filter is
    // omitted to avoid accidentally returning an empty result set.
    if ( ! empty( $args['following_only'] ) && is_user_logged_in() ) {
        $current_user_id = get_current_user_id();
        $following_ids   = vh360_get_following_user_ids( $current_user_id );
        
        // Always include the current user's own posts in My Feed
        // This ensures users can see their own content even if they follow nobody
        $following_ids[] = (int) $current_user_id;
        $following_ids   = array_unique( $following_ids );
        
        if ( ! empty( $following_ids ) ) {
            $query_args['author__in'] = $following_ids;
        }
    }

    $query = new WP_Query($query_args);
    return $query->posts;
}

// Helper: vh360_get_following_user_ids()
//
// The helper function `vh360_get_following_user_ids()` is defined in
// `includes/follow-system.php` and exposes a consistent API for retrieving
// an array of user IDs that a given user is following. Defining the
// function only once prevents fatal redeclare errors when the theme is
// loaded alongside other components. Do not redefine it here.  


/**
 * Render a community post.
 *
 * Outputs HTML for a single community post including avatar, author name,
 * timestamp, content, featured image, like button and counts. This helper
 * function can be used in templates.
 *
 * @param WP_Post $post The post object.
 * @param bool $show_full If true show full content, else excerpt.
 */

/**
 * Render user mentions in content as profile links.
 *
 * Converts @username patterns into links to that user's profile, when a matching
 * user_login is found. Falls back to plain text if no user exists.
 *
 * @param string $content Raw post/comment content.
 * @return string Sanitized HTML with clickable mentions.
 */
function vh360_render_mentions( $content ) {
    if ( empty( $content ) ) {
        return '';
    }

    $pattern = '/@([A-Za-z0-9_\.]+)/';

    $content = preg_replace_callback(
        $pattern,
        function( $matches ) {
            $handle = $matches[1];
            $user   = get_user_by( 'login', $handle );
            if ( ! $user ) {
                return $matches[0];
            }

            $url = vh360_get_profile_url( $user->ID );
            if ( ! $url ) {
                return $matches[0];
            }

            $mention = '@' . $handle;

            return sprintf(
                '<a href="%s" class="vh360-mention">%s</a>',
                esc_url( $url ),
                esc_html( $mention )
            );
        },
        $content
    );

    // Allow safe HTML, including our mention links.
    $allowed_tags        = wp_kses_allowed_html( 'post' );
    $allowed_tags['a']   = array(
        'href'  => array(),
        'class' => array(),
        'rel'   => array(),
        'target'=> array(),
    );

    return wpautop( wp_kses( $content, $allowed_tags ) );
}

/**
 * Extract URLs from raw text content.
 *
 * @param string $text
 * @return array Unique list of URLs in the order they appear.
 */
function vh360_extract_urls_from_text( $text ) {
    $urls = array();
    if ( empty( $text ) ) {
        return $urls;
    }

    $pattern = '#\bhttps?://[^\s<]+#i';
    if ( preg_match_all( $pattern, $text, $matches ) ) {
        foreach ( $matches[0] as $url ) {
            $urls[] = $url;
        }
    }

    // Remove duplicates while preserving order.
    $urls = array_values( array_unique( $urls ) );

    return $urls;
}

/**
 * Determine if a URL is internal (same host as site) or external.
 *
 * @param string $url
 * @return bool True if internal, false otherwise.
 */
function vh360_is_internal_url( $url ) {
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $url_host  = wp_parse_url( $url, PHP_URL_HOST );

    if ( empty( $url_host ) ) {
        // Relative URL or something we can't parse: treat as internal.
        return true;
    }

    return ( strtolower( $site_host ) === strtolower( $url_host ) );
}

/**
 * Build HTML for a clickable link with correct target depending on internal/external.
 *
 * @param string $url
 * @param string|null $label
 * @return string
 */
function vh360_build_activity_link_tag( $url, $label = null ) {
    if ( null === $label ) {
        $label = $url;
    }
    $label = esc_html( $label );
    $url   = esc_url( $url );

    $attrs = '';
    if ( vh360_is_internal_url( $url ) ) {
        $attrs = '';
    } else {
        $attrs = ' target="_blank" rel="noopener noreferrer"';
    }

    return '<a href="' . $url . '"' . $attrs . '>' . $label . '</a>';
}

/**
 * Fetch basic metadata for an arbitrary URL using Open Graph tags or the page <title>.
 *
 * This is used to approximate rich link previews (similar to social platforms) without
 * relying on external APIs. Results are cached in a transient to avoid repeated
 * remote requests for the same URL.
 *
 * @param string $url
 * @return array{title:string,description:string,image:string}
 */
function vh360_fetch_url_metadata( $url ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        return array(
            'title'       => '',
            'description' => '',
            'image'       => '',
        );
    }

    $cache_key = 'vh360_url_meta_' . md5( $url );
    $cached    = get_transient( $cache_key );
    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }

    $meta = array(
        'title'       => '',
        'description' => '',
        'image'       => '',
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout'     => 4,
            'redirection' => 3,
        )
    );

    if ( is_wp_error( $response ) ) {
        set_transient( $cache_key, $meta, HOUR_IN_SECONDS );
        return $meta;
    }

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        set_transient( $cache_key, $meta, HOUR_IN_SECONDS );
        return $meta;
    }

    // Limit search space for performance.
    $snippet = substr( $body, 0, 200000 );

    // Helper to clean text.
    $clean = function( $value ) {
        $value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
        $value = wp_strip_all_tags( $value );
        return trim( $value );
    };

    // Open Graph title
    if ( preg_match( '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $snippet, $m ) ) {
        $meta['title'] = $clean( $m[1] );
    }

    // Open Graph description
    if ( preg_match( '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $snippet, $m ) ) {
        $meta['description'] = $clean( $m[1] );
    }

    // Open Graph image
    if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $snippet, $m ) ) {
        $meta['image'] = esc_url_raw( trim( $m[1] ) );
    }

    // Fallback to <title> tag if needed.
    if ( '' === $meta['title'] && preg_match( '/<title[^>]*>(.*?)<\/title>/is', $snippet, $m ) ) {
        $meta['title'] = $clean( $m[1] );
    }

    // Fallback to meta name="description".
    if ( '' === $meta['description'] && preg_match( '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $snippet, $m ) ) {
        $meta['description'] = $clean( $m[1] );
    }

    // Cache for 12 hours to reduce external calls.
    set_transient( $cache_key, $meta, 12 * HOUR_IN_SECONDS );

    return $meta;
}

/**
 * Fetch oEmbed data for a URL (cached).
 *
 * WordPress supports oEmbed providers (including YouTube). For providers that
 * are increasingly hostile to HTML scraping, oEmbed is the most reliable way
 * to retrieve canonical metadata such as titles.
 *
 * @param string $url
 * @return object|null oEmbed response object, or null on failure.
 */
function vh360_get_oembed_data_cached( $url ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        return null;
    }

    $cache_key = 'vh360_oembed_' . md5( $url );
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        // We store either an object (serialized) or an explicit false marker.
        return is_object( $cached ) ? $cached : null;
    }

    $oembed = _wp_oembed_get_object();
    if ( ! $oembed ) {
        set_transient( $cache_key, 0, HOUR_IN_SECONDS );
        return null;
    }

    $data = $oembed->get_data( $url );
    if ( empty( $data ) || ! is_object( $data ) ) {
        // Cache failures briefly to avoid repeated outbound requests.
        set_transient( $cache_key, 0, HOUR_IN_SECONDS );
        return null;
    }

    // Cache successful oEmbed data for 12 hours.
    set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
    return $data;
}

/**
 * Build an inline YouTube preview with play button.
 *
 * Renders a lightweight placeholder with thumbnail and play icon.
 * No iframe is loaded until the user clicks on the preview.
 *
 * @param string $video_id YouTube video ID.
 * @param string $url      Original YouTube URL.
 * @return string HTML for inline preview.
 */
function vh360_build_youtube_inline_preview( $video_id, $url ) {
    $thumb_url = vh360_get_youtube_thumbnail_url( $video_id, 'maxresdefault' );
    $embed_url = vh360_get_youtube_embed_url( $video_id );
    
    // Try to get video title from oEmbed
    $title = '';
    $oembed_data = vh360_get_oembed_data_cached( $url );
    if ( $oembed_data && ! empty( $oembed_data->title ) ) {
        $title = (string) $oembed_data->title;
    } else {
        $title = __( 'YouTube Video', 'videohub360-theme' );
    }
    
    $html  = '<div class="vh360-youtube-preview" data-video-id="' . esc_attr( $video_id ) . '" data-embed-url="' . esc_attr( $embed_url ) . '" role="button" aria-label="' . esc_attr( sprintf( __( 'Play video: %s', 'videohub360-theme' ), $title ) ) . '" tabindex="0">';
    $html .= '<div class="vh360-youtube-thumbnail">';
    $html .= '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" />';
    $html .= '<div class="vh360-youtube-play-button" aria-hidden="true">';
    $html .= '<svg viewBox="0 0 68 48" width="68" height="48">';
    $html .= '<path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path>';
    $html .= '<path d="M 45,24 27,14 27,34" fill="#fff"></path>';
    $html .= '</svg>';
    $html .= '</div>';
    $html .= '</div>';
    if ( $title ) {
        $html .= '<div class="vh360-youtube-title">' . esc_html( $title ) . '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Build an external YouTube link (non-inline mode).
 *
 * @param string $video_id YouTube video ID.
 * @param string $url      Original YouTube URL.
 * @return string HTML for external link card.
 */
function vh360_build_youtube_external_link( $video_id, $url ) {
    $thumb_url = vh360_get_youtube_thumbnail_url( $video_id, 'hqdefault' );
    
    // Try to get video title from oEmbed
    $title = '';
    $oembed_data = vh360_get_oembed_data_cached( $url );
    if ( $oembed_data && ! empty( $oembed_data->title ) ) {
        $title = (string) $oembed_data->title;
    } else {
        $title = __( 'YouTube Video', 'videohub360-theme' );
    }
    
    $link_tag = vh360_build_activity_link_tag( $url, $url );
    
    $html  = '<div class="vh360-link-preview-card vh360-link-preview-card--youtube">';
    if ( $thumb_url ) {
        $html .= '<div class="vh360-link-preview-thumbnail">';
        $html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">';
        $html .= '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" />';
        $html .= '</a>';
        $html .= '</div>';
    }
    $html .= '<div class="vh360-link-preview-meta">';
    $html .= '<div class="vh360-link-preview-title">' . esc_html( $title ) . '</div>';
    $html .= '<div class="vh360-link-preview-url">' . $link_tag . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Build a small preview card for a URL, if applicable.
 *
 * For YouTube URLs, we use the YouTube thumbnail and support inline playback
 * based on admin settings. For internal links that resolve to a post ID, we use
 * the post thumbnail (if any) and title. For other URLs, we render a simple
 * text-only card.
 *
 * @param string $url
 * @param int|null $parent_post_id
 * @return string HTML for preview card, or empty string if none.
 */
function vh360_build_link_preview_card( $url, $parent_post_id = null ) {
    $url = esc_url_raw( $url );
    if ( empty( $url ) ) {
        return '';
    }

    // Check for YouTube URLs using the new centralized helper
    $youtube_id = vh360_get_youtube_video_id_from_url( $url );
    if ( $youtube_id ) {
        // Get admin setting for YouTube playback behavior
        $playback_mode = get_theme_mod( 'vh360_youtube_playback', 'inline' );
        
        if ( 'inline' === $playback_mode ) {
            // Inline playable embed (default)
            return vh360_build_youtube_inline_preview( $youtube_id, $url );
        } else {
            // External link mode
            return vh360_build_youtube_external_link( $youtube_id, $url );
        }
    }

    // Attempt to fetch Open Graph / basic metadata once for this URL.
    $meta = vh360_fetch_url_metadata( $url );

    // Internal WordPress content preview (if URL maps to a post)
    $post_id = url_to_postid( $url );
    if ( $post_id ) {
        $title      = get_the_title( $post_id );
        $thumb_url  = get_the_post_thumbnail_url( $post_id, 'medium' );
        $link_label = $title ? $title : $url;
        $link_tag   = vh360_build_activity_link_tag( $url, $link_label );

        $html  = '<div class="vh360-link-preview-card vh360-link-preview-card--internal">';
        if ( $thumb_url ) {
            $html .= '<div class="vh360-link-preview-thumbnail">';
            $html .= '<a href="' . esc_url( $url ) . '">';
            $html .= '<img src="' . esc_url( $thumb_url ) . '" alt="" loading="lazy" />';
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '<div class="vh360-link-preview-meta">';
        if ( $title ) {
            $html .= '<div class="vh360-link-preview-title">' . esc_html( $title ) . '</div>';
        }
        $html .= '<div class="vh360-link-preview-url">' . vh360_build_activity_link_tag( $url, $url ) . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // Generic external or unknown URL: use metadata if available, otherwise a basic card.
    $link_tag = vh360_build_activity_link_tag( $url, $url );

    $html  = '<div class="vh360-link-preview-card vh360-link-preview-card--generic">';
    $html .= '<div class="vh360-link-preview-meta">';
    if ( ! empty( $meta['title'] ) ) {
        $html .= '<div class="vh360-link-preview-title">' . esc_html( $meta['title'] ) . '</div>';
    }
    $html .= '<div class="vh360-link-preview-url">' . $link_tag . '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Format activity content for posts/comments:
 * - Apply @mention rendering.
 * - Convert plain URLs to clickable links with proper targets.
 * - Append simple link preview cards (YouTube, internal posts, generic).
 *
 * @param string   $text
 * @param string   $context        'post' or 'comment' (for future tweaks).
 * @param int|null $parent_post_id Optional parent post ID.
 * @return string HTML
 */
function vh360_format_activity_content( $text, $context = 'post', $parent_post_id = null ) {
    if ( '' === $text || null === $text ) {
        return '';
    }

    // Start with mentions transformed into links.
    $rendered = vh360_render_mentions( $text );

    // Collect URLs from the original raw text.
    $urls = vh360_extract_urls_from_text( $text );
    if ( empty( $urls ) ) {
        return wp_kses_post( $rendered );
    }

    // Replace plain-text URLs in the rendered string with clickable <a> tags.
    foreach ( $urls as $url ) {
        $safe_url   = esc_url_raw( $url );
        $link_tag   = vh360_build_activity_link_tag( $safe_url, $safe_url );
        // Use preg_quote to safely build regex for replacement (case-insensitive).
        $pattern    = '/' . preg_quote( $url, '/' ) . '(?![^<]*>)/';
        $rendered   = preg_replace( $pattern, $link_tag, $rendered );
    }

    // Build previews. Limit to avoid overly tall cards.
    $preview_cards = array();
    $max_previews  = 3;
    foreach ( $urls as $url ) {
        if ( count( $preview_cards ) >= $max_previews ) {
            break;
        }
        $card_html = vh360_build_link_preview_card( $url, $parent_post_id );
        if ( $card_html ) {
            $preview_cards[] = $card_html;
        }
    }

    $output  = wp_kses_post( $rendered );
    if ( ! empty( $preview_cards ) ) {
        $output .= '<div class="vh360-link-previews">';
        foreach ( $preview_cards as $card ) {
            // The preview cards themselves are trusted HTML we generate.
            $output .= $card;
        }
        $output .= '</div>';
    }

    return $output;
}

/**
 * Check if a post is a shared post.
 *
 * @param int|WP_Post $post Post ID or post object.
 * @return bool True if this is a shared post, false otherwise.
 */
function vh360_is_shared_post($post) {
    if (is_numeric($post)) {
        $post_id = (int) $post;
    } elseif ($post instanceof WP_Post) {
        $post_id = $post->ID;
    } else {
        return false;
    }
    
    $shared_from = get_post_meta($post_id, 'vh360_shared_from', true);
    return !empty($shared_from);
}

/**
 * Render the original post card for a shared post.
 *
 * @param WP_Post $original_post The original post being shared.
 * @param int $sharing_post_id The ID of the post that's sharing this.
 */
function vh360_render_original_post_card($original_post, $sharing_post_id = 0) {
    if (!$original_post || $original_post->post_status !== 'publish') {
        // Original post deleted or not available
        ?>
        <div class="vh360-original-post-card vh360-original-post-deleted">
            <p><?php esc_html_e('This post is no longer available.', 'videohub360-theme'); ?></p>
        </div>
        <?php
        return;
    }
    
    $author_id = $original_post->post_author;
    $author = get_userdata($author_id);
    $profile_url = vh360_get_profile_url($author_id);
    $time_ago = human_time_diff(get_the_time('U', $original_post), current_time('timestamp')) . ' ' . __('ago', 'videohub360-theme');
    $post_url = get_permalink($original_post->ID);
    ?>
    <a href="<?php echo esc_url($post_url); ?>" class="vh360-original-card-link">
        <div class="vh360-original-post-card">
            <div class="vh360-original-card-header">
                <div class="vh360-original-card-avatar">
                    <?php echo get_avatar($author_id, 32); ?>
                </div>
                <div class="vh360-original-card-meta">
                    <div class="vh360-original-card-author">
                        <?php echo esc_html($author ? $author->display_name : ''); ?>
                    </div>
                    <div class="vh360-original-card-time"><?php echo esc_html($time_ago); ?></div>
                </div>
            </div>
            
            <?php if (!empty($original_post->post_content)) : ?>
                <div class="vh360-original-card-content">
                    <?php echo vh360_format_activity_content($original_post->post_content, 'post', $original_post->ID); ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Show featured image
            if (has_post_thumbnail($original_post)) :
                $thumbnail_id = get_post_thumbnail_id($original_post);
                $full_image_url = wp_get_attachment_image_url($thumbnail_id, 'large');
            ?>
                <div class="vh360-original-card-image">
                    <?php echo get_the_post_thumbnail($original_post, 'large'); ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Show video if present
            $video_attachment_id = get_post_meta($original_post->ID, '_vh360_video_attachment', true);
            if ($video_attachment_id) :
                $video_url = wp_get_attachment_url($video_attachment_id);
                $video_type = get_post_mime_type($video_attachment_id);
                if ($video_url && $video_type) :
            ?>
                <div class="vh360-original-card-video">
                    <video controls class="vh360-video-player" preload="metadata">
                        <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($video_type); ?>">
                        <?php esc_html_e('Your browser does not support the video tag.', 'videohub360-theme'); ?>
                    </video>
                </div>
            <?php
                endif;
            endif;
            ?>
        </div>
    </a>
    <?php
}

/**
 * Render a shared post (post that references another post).
 *
 * @param WP_Post $post The shared post object.
 * @param bool $show_full Whether to show full content.
 */
function vh360_render_shared_post($post, $show_full = true) {
    if (!$post) {
        return;
    }
    
    $author_id = $post->post_author;
    $author = get_userdata($author_id);
    $profile_url = vh360_get_profile_url($author_id);
    $time_ago = human_time_diff(get_the_time('U', $post), current_time('timestamp')) . ' ' . __('ago', 'videohub360-theme');
    
    // Get original post
    $original_post_id = get_post_meta($post->ID, 'vh360_shared_from', true);
    $original_post = $original_post_id ? get_post($original_post_id) : null;
    
    // Get share comment
    $share_comment = get_post_meta($post->ID, 'vh360_share_comment', true);
    if (empty($share_comment)) {
        $share_comment = $post->post_content;
    }
    
    // Get likes for this shared post
    $likes = get_post_meta($post->ID, 'vh360_likes', true);
    if (!is_array($likes)) {
        $likes = array();
    }
    $liked = is_user_logged_in() ? in_array(get_current_user_id(), $likes) : false;
    
    $current_user_id = get_current_user_id();
    $can_edit_post = is_user_logged_in() && ((int) $current_user_id === (int) $author_id || current_user_can('edit_post', $post->ID));
    $can_delete_post = is_user_logged_in() && ((int) $current_user_id === (int) $author_id || current_user_can('delete_post', $post->ID));
    ?>
    
    <article class="vh360-community-post vh360-shared-post" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <div class="vh360-community-avatar">
            <?php echo get_avatar($author_id, 40); ?>
        </div>
        <div class="vh360-community-content">
            <div class="vh360-community-header">
                <a href="<?php echo esc_url($profile_url); ?>" class="vh360-community-author">
                    <?php echo esc_html($author ? $author->display_name : ''); ?>
                </a>
                <span class="vh360-share-indicator"><?php esc_html_e('shared a post', 'videohub360-theme'); ?></span>
                <span class="vh360-community-time"><?php echo esc_html($time_ago); ?></span>
                <?php if ($can_edit_post || $can_delete_post) : ?>
                    <div class="vh360-community-actions">
                        <button type="button"
                                class="vh360-kebab-toggle"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <span class="vh360-kebab-dot"></span>
                            <span class="vh360-kebab-dot"></span>
                            <span class="vh360-kebab-dot"></span>
                        </button>
                        <div class="vh360-actions-menu" role="menu">
                            <?php if ($can_edit_post) : ?>
                                <button type="button"
                                        class="vh360-actions-menu-item vh360-post-edit-btn"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        role="menuitem">
                                    <?php esc_html_e('Edit', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                            <?php if ($can_delete_post) : ?>
                                <button type="button"
                                        class="vh360-actions-menu-item vh360-post-delete-btn"
                                        data-post-id="<?php echo esc_attr($post->ID); ?>"
                                        role="menuitem">
                                    <?php esc_html_e('Delete', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($share_comment)) : ?>
                <div class="vh360-community-body">
                    <div class="vh360-community-text vh360-share-comment">
                        <?php echo vh360_format_activity_content($share_comment, 'post', $post->ID); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Render original post card
            vh360_render_original_post_card($original_post, $post->ID);
            ?>
            
            <?php
            // Post Stats Row - Always visible (same as regular posts)
            if (class_exists('VH360_Post_Shares')) {
                $post_id = $post->ID;
                
                // Get counts
                $like_count = count($likes);
                $comment_count = VH360_Post_Shares::get_comment_count($post_id);
                $share_count = VH360_Post_Shares::get_count($post_id);
                ?>
                <div class="vh360-post-stats" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <!-- Like Button - Always visible with thumbs up icon -->
                    <button class="vh360-stat-item vh360-stat-likes <?php echo $liked ? 'vh360-stat-active' : ''; ?>" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="like"
                            type="button">
                        <svg class="vh360-stat-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 11H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <span class="vh360-stat-count" data-stat="likes"><?php echo esc_html(vh360_format_number($like_count)); ?></span>
                    </button>
                    
                    <span class="vh360-stat-separator">•</span>
                    
                    <!-- Comment Button - Always visible -->
                    <button class="vh360-stat-item vh360-stat-comments" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="comments"
                            type="button">
                        <span class="vh360-stat-count" data-stat="comments"><?php echo esc_html(vh360_format_number($comment_count)); ?></span>
                        <span class="vh360-stat-label"><?php esc_html_e('COMMENTS', 'videohub360-theme'); ?></span>
                    </button>
                    
                    <span class="vh360-stat-separator">•</span>
                    
                    <!-- Share Button - Always visible with share icon -->
                    <button class="vh360-stat-item vh360-stat-shares" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="share"
                            type="button">
                        <svg class="vh360-stat-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 6.65685 16.3431 8 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 15C7.65685 15 9 13.6569 9 12C9 10.3431 7.65685 9 6 9C4.34315 9 3 10.3431 3 12C3 13.6569 4.34315 15 6 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18 22C19.6569 22 21 20.6569 21 19C21 17.3431 19.6569 16 18 16C16.3431 16 15 17.3431 15 19C15 20.6569 16.3431 22 18 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.59 13.51L15.42 17.49" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15.41 6.51L8.59 10.49" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="vh360-stat-count" data-stat="shares"><?php echo esc_html(vh360_format_number($share_count)); ?></span>
                    </button>
                </div>
                <?php
            }
            ?>
            
            <div class="vh360-comments-section" id="vh360-comments-section-<?php echo esc_attr($post->ID); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <div class="vh360-comments-list" data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php vh360_render_activity_comments($post->ID); ?>
                </div>

                <?php if (is_user_logged_in()) : ?>
                    <form class="vh360-comment-form" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <div class="vh360-comment-avatar">
                            <?php echo get_avatar(get_current_user_id(), 32); ?>
                        </div>
                        <div class="vh360-comment-input-wrapper">
                            <textarea class="vh360-comment-textarea" 
                                      name="comment" 
                                      rows="1" 
                                      placeholder="<?php esc_attr_e('Write a comment...', 'videohub360-theme'); ?>"
                                      aria-label="<?php esc_attr_e('Write a comment', 'videohub360-theme'); ?>"></textarea>
                            <input type="hidden" name="parent_id" value="0" />
                            <button type="button" 
                                    class="vh360-comment-send-btn" 
                                    disabled
                                    aria-label="<?php esc_attr_e('Send comment', 'videohub360-theme'); ?>">
                                <span class="vh360-btn-text">
                                    <svg class="vh360-comment-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                                    </svg>
                                </span>
                                <span class="vh360-btn-spinner" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="vh360-comments-login-hint">
                        <?php esc_html_e('Please log in to comment.', 'videohub360-theme'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

function vh360_render_community_post($post, $show_full = true, $skip_comments = false) {
    if (!$post) {
        return;
    }
    
    // Check if this is a shared post - render differently
    if (vh360_is_shared_post($post)) {
        vh360_render_shared_post($post, $show_full);
        return;
    }
    
    // Regular post rendering
    $author_id = $post->post_author;
    $author = get_userdata($author_id);
    $profile_url = vh360_get_profile_url($author_id);
    $time_ago = human_time_diff(get_the_time('U', $post), current_time('timestamp')) . ' ' . __('ago', 'videohub360-theme');
    $likes = get_post_meta($post->ID, 'vh360_likes', true);
    if (!is_array($likes)) {
        $likes = array();
    }
    $liked = is_user_logged_in() ? in_array(get_current_user_id(), $likes) : false;

    $current_user_id   = get_current_user_id();
    $can_edit_post     = is_user_logged_in() && ( (int) $current_user_id === (int) $author_id || current_user_can( 'edit_post', $post->ID ) );
    $can_delete_post   = is_user_logged_in() && ( (int) $current_user_id === (int) $author_id || current_user_can( 'delete_post', $post->ID ) );
    ?>

    <article class="vh360-community-post" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <div class="vh360-community-avatar">
            <?php echo get_avatar($author_id, 40); ?>
        </div>
        <div class="vh360-community-content">
            <div class="vh360-community-header">
                <a href="<?php echo esc_url($profile_url); ?>" class="vh360-community-author">
                    <?php echo esc_html($author ? $author->display_name : ''); ?>
                </a>
                <span class="vh360-community-time"><?php echo esc_html($time_ago); ?></span>
                <?php if ( $can_edit_post || $can_delete_post ) : ?>
                    <div class="vh360-community-actions">
                        <button type="button"
                                class="vh360-kebab-toggle"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <span class="vh360-kebab-dot"></span>
                            <span class="vh360-kebab-dot"></span>
                            <span class="vh360-kebab-dot"></span>
                        </button>
                        <div class="vh360-actions-menu" role="menu">
                            <?php if ( $can_edit_post ) : ?>
                                <button type="button"
                                        class="vh360-actions-menu-item vh360-post-edit-btn"
                                        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
                                        role="menuitem">
                                    <?php esc_html_e( 'Edit', 'videohub360-theme' ); ?>
                                </button>
                            <?php endif; ?>
                            <?php if ( $can_delete_post ) : ?>
                                <button type="button"
                                        class="vh360-actions-menu-item vh360-post-delete-btn"
                                        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
                                        role="menuitem">
                                    <?php esc_html_e( 'Delete', 'videohub360-theme' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="vh360-community-body">
                <?php
                // Check if this community post is linked to a live room
                $live_room_id = get_post_meta($post->ID, 'vh360_live_room_id', true);

                if ($live_room_id) {
                    $live_status = get_post_meta($post->ID, 'vh360_live_status', true);
                    $is_currently_live = get_post_meta($live_room_id, '_vh360_is_live', true) === 'yes';
                    
                    // Render LIVE badge and Join button if currently live
                    if ($is_currently_live && $live_status === 'live') {
                        $room_permalink = get_permalink($live_room_id);
                        ?>
                        <div class="vh360-live-badge-container">
                            <span class="vh360-live-badge">🔴 LIVE</span>
                            <a href="<?php echo esc_url($room_permalink); ?>" class="vh360-join-live-button">
                                <?php esc_html_e('Join Live Room', 'videohub360-theme'); ?>
                            </a>
                        </div>
                        <?php
                    }
                    
                    // Render "Live ended" state
                    if ((!$is_currently_live && $live_status === 'live') || $live_status === 'ended') {
                        ?>
                        <div class="vh360-live-ended-container">
                            <span class="vh360-ended-badge"><?php esc_html_e('Live ended', 'videohub360-theme'); ?></span>
                        </div>
                        <?php
                    }
                }
                ?>
                <div class="vh360-community-text">
                <?php
                if ( $show_full ) {
                    $vh360_content_source = $post->post_content;
                } else {
                    $vh360_content_source = wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 );
                }
                echo vh360_format_activity_content( $vh360_content_source, 'post', $post->ID );
                ?>
                </div>
                <?php if (has_post_thumbnail($post)) : 
                    $thumbnail_id = get_post_thumbnail_id($post);
                    $full_image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
                ?>
                    <div class="vh360-community-image">
                        <a href="<?php echo esc_url($full_image_url); ?>" class="vh360-image-lightbox" data-post-id="<?php echo esc_attr($post->ID); ?>">
                            <?php echo get_the_post_thumbnail($post, 'large'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php
                // Check for managed Studio asset first, then legacy video attachment.
                $studio_asset_id = get_post_meta($post->ID, '_vh360_studio_video_asset_id', true);
                $studio_playback = ($studio_asset_id && function_exists('vh360_studio_get_video_playback')) ? vh360_studio_get_video_playback($studio_asset_id) : null;
                if ($studio_playback && 'ready' === $studio_playback['status']) :
                ?>
                    <div class="vh360-community-video">
                        <?php if ('embed' === $studio_playback['render_mode'] && !empty($studio_playback['embed_url'])) : ?>
                            <iframe src="<?php echo esc_url($studio_playback['embed_url']); ?>" allowfullscreen loading="lazy"></iframe>
                        <?php else : ?>
                            <video controls class="vh360-video-player" preload="metadata" poster="<?php echo esc_url($studio_playback['poster_url']); ?>"><source src="<?php echo esc_url($studio_playback['src']); ?>" type="<?php echo esc_attr($studio_playback['mime_type']); ?>"></video>
                        <?php endif; ?>
                    </div>
                <?php elseif ($studio_playback && in_array($studio_playback['status'], array('pending','uploading','processing'), true)) : ?>
                    <p class="vh360-video-processing"><?php esc_html_e('Video is processing. Please check back soon.', 'videohub360-theme'); ?></p>
                <?php elseif ($studio_playback && 'failed' === $studio_playback['status']) : ?>
                    <p class="vh360-video-processing-failed"><?php esc_html_e('Video processing could not be completed.', 'videohub360-theme'); ?></p>
                <?php else :
                    $video_attachment_id = get_post_meta($post->ID, '_vh360_video_attachment', true);
                    if ($video_attachment_id) :
                        $video_url = wp_get_attachment_url($video_attachment_id);
                        $video_type = get_post_mime_type($video_attachment_id);
                        if ($video_url && $video_type) :
                ?>
                    <div class="vh360-community-video"><video controls class="vh360-video-player" preload="metadata"><source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($video_type); ?>"><?php esc_html_e('Your browser does not support the video tag.', 'videohub360-theme'); ?></video></div>
                <?php endif; endif; endif; ?>
            </div>
            
            <?php
            // Interactive Stats Row - Always visible (replaces old Like/Share buttons)
            if (class_exists('VH360_Post_Shares')) {
                $post_id = $post->ID;
                
                // Get counts
                $like_count = count($likes); // Already fetched above
                $comment_count = VH360_Post_Shares::get_comment_count($post_id);
                $share_count = VH360_Post_Shares::get_count($post_id);
                ?>
                <div class="vh360-post-stats" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <!-- Like Button - Always visible with thumbs up icon -->
                    <button class="vh360-stat-item vh360-stat-likes <?php echo $liked ? 'vh360-stat-active' : ''; ?>" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="like"
                            type="button"
                            aria-label="<?php esc_attr_e('Like this post', 'videohub360-theme'); ?>">
                        <svg class="vh360-stat-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 11H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <span class="vh360-stat-count" data-stat="likes"><?php echo esc_html(vh360_format_number($like_count)); ?></span>
                    </button>
                    
                    <span class="vh360-stat-separator">•</span>
                    
                    <!-- Comment Button - Always visible -->
                    <button class="vh360-stat-item vh360-stat-comments" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="comments"
                            type="button"
                            aria-label="<?php esc_attr_e('View comments', 'videohub360-theme'); ?>">
                        <span class="vh360-stat-count" data-stat="comments"><?php echo esc_html(vh360_format_number($comment_count)); ?></span>
                        <span class="vh360-stat-label"><?php esc_html_e('COMMENTS', 'videohub360-theme'); ?></span>
                    </button>
                    
                    <span class="vh360-stat-separator">•</span>
                    
                    <!-- Share Button - Always visible with share icon -->
                    <button class="vh360-stat-item vh360-stat-shares" 
                            data-post-id="<?php echo esc_attr($post_id); ?>" 
                            data-action="share"
                            type="button"
                            aria-label="<?php esc_attr_e('Share this post', 'videohub360-theme'); ?>">
                        <svg class="vh360-stat-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 8C19.6569 8 21 6.65685 21 5C21 3.34315 19.6569 2 18 2C16.3431 2 15 3.34315 15 5C15 6.65685 16.3431 8 18 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 15C7.65685 15 9 13.6569 9 12C9 10.3431 7.65685 9 6 9C4.34315 9 3 10.3431 3 12C3 13.6569 4.34315 15 6 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18 22C19.6569 22 21 20.6569 21 19C21 17.3431 19.6569 16 18 16C16.3431 16 15 17.3431 15 19C15 20.6569 16.3431 22 18 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8.59 13.51L15.42 17.49" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15.41 6.51L8.59 10.49" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="vh360-stat-count" data-stat="shares"><?php echo esc_html(vh360_format_number($share_count)); ?></span>
                    </button>
                </div>
                <?php
            }
            ?>

            <?php if (!$skip_comments) : ?>
            <div id="vh360-comments-section-<?php echo esc_attr($post->ID); ?>" class="vh360-comments-section" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <div class="vh360-comments-list" data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php vh360_render_activity_comments($post->ID); ?>
                </div>

                <?php if (is_user_logged_in()) : ?>
                    <form class="vh360-comment-form" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <div class="vh360-comment-avatar">
                            <?php echo get_avatar(get_current_user_id(), 32); ?>
                        </div>
                        <div class="vh360-comment-input-wrapper">
                            <textarea class="vh360-comment-textarea" 
                                      name="comment" 
                                      rows="1" 
                                      placeholder="<?php esc_attr_e('Write a comment...', 'videohub360-theme'); ?>"
                                      aria-label="<?php esc_attr_e('Write a comment', 'videohub360-theme'); ?>"></textarea>
                            <input type="hidden" name="parent_id" value="0" />
                            <button type="button" 
                                    class="vh360-comment-send-btn" 
                                    disabled
                                    aria-label="<?php esc_attr_e('Send comment', 'videohub360-theme'); ?>">
                                <span class="vh360-btn-text">
                                    <svg class="vh360-comment-send-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                                    </svg>
                                </span>
                                <span class="vh360-btn-spinner" aria-hidden="true"></span>
                            </button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="vh360-comments-login-hint">
                        <?php esc_html_e('Please log in to comment.', 'videohub360-theme'); ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

/**
 * Build a simple parent/replies tree of activity comments for a vh360_post.
 * Facebook-style: All replies (regardless of depth) are flattened into the 'children' array.
 *
 * @param int $post_id
 * @return array
 */
function vh360_get_activity_comments_tree($post_id) {
    $args = array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'orderby' => 'comment_date_gmt',
        'order'   => 'ASC',
        'type'    => 'comment',
    );

    $comments = get_comments($args);
    if (empty($comments)) {
        return array();
    }

    $tree = array();
    $all_comments = array();

    // Index all comments by ID for quick lookup
    foreach ($comments as $comment) {
        $all_comments[$comment->comment_ID] = $comment;
    }

    // Collect top-level comments
    foreach ($comments as $comment) {
        $parent = (int) $comment->comment_parent;
        if (0 === $parent) {
            $tree[$comment->comment_ID] = array(
                'comment'  => $comment,
                'children' => array(),
            );
        }
    }

    // Attach ALL replies to their root parent (flattened, like Facebook)
    foreach ($comments as $comment) {
        $parent = (int) $comment->comment_parent;
        if (0 !== $parent) {
            // Find the root parent (top-level comment)
            $root_parent = vh360_find_root_parent($comment->comment_parent, $all_comments);
            
            // Add this reply to the root parent's children array
            if (isset($tree[$root_parent])) {
                $tree[$root_parent]['children'][] = $comment;
            }
        }
    }

    return $tree;
}

/**
 * Helper function to find the root parent (top-level comment) of a comment.
 * Walks up the parent chain until it finds a comment with parent = 0.
 *
 * @param int $comment_id The comment ID to start from
 * @param array $all_comments Indexed array of all comments
 * @return int The root parent comment ID
 */
function vh360_find_root_parent($comment_id, $all_comments) {
    if (!isset($all_comments[$comment_id])) {
        return 0;
    }
    
    $comment = $all_comments[$comment_id];
    $parent = (int) $comment->comment_parent;
    
    // If this comment has no parent, it's the root
    if (0 === $parent) {
        return $comment_id;
    }
    
    // Otherwise, recursively find the root parent
    return vh360_find_root_parent($parent, $all_comments);
}

/**
 * Render Facebook-style activity comments for a vh360_post.
 *
 * @param int $post_id
 */
function vh360_render_activity_comments($post_id) {
    $tree = vh360_get_activity_comments_tree($post_id);
    if (empty($tree)) {
        return;
    }
    
    // Bulk fetch like data for performance (avoid N+1 queries)
    $all_comment_ids = array();
    foreach ($tree as $node) {
        $all_comment_ids[] = $node['comment']->comment_ID;
        if (!empty($node['children'])) {
            foreach ($node['children'] as $child) {
                $all_comment_ids[] = $child->comment_ID;
            }
        }
    }
    
    $like_counts = array();
    $user_liked_comments = array();
    $current_user_id = get_current_user_id();
    
    if (class_exists('VH360_Comment_Likes') && !empty($all_comment_ids)) {
        $like_counts = VH360_Comment_Likes::get_counts_for_comments($all_comment_ids);
        if ($current_user_id) {
            $user_liked_comments = VH360_Comment_Likes::get_user_liked_comments($all_comment_ids, $current_user_id);
        }
    }
    ?>
    <div class="vh360-comments-thread">
        <?php foreach ($tree as $node) :
            $comment  = $node['comment'];
            $children = $node['children'];
            ?>
            <div class="vh360-comment-item" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                <div class="vh360-comment-row">
                    <!-- Avatar Column -->
                    <div class="vh360-comment-avatar">
                        <?php
                        $avatar_user = $comment->user_id ? $comment->user_id : $comment->comment_author_email;
                        echo get_avatar($avatar_user, 40);
                        ?>
                    </div>
                    
                    <!-- Content Column -->
                    <div class="vh360-comment-main">
                        <!-- Header Row (OUTSIDE bubble) - Name + Kebab -->
                        <div class="vh360-comment-header">
                            <strong class="vh360-comment-author" data-user-id="<?php echo esc_attr($comment->user_id); ?>" data-username="<?php echo esc_attr($comment->user_id ? get_userdata($comment->user_id)->user_login : ''); ?>">
                                <?php echo esc_html($comment->comment_author); ?>
                            </strong>
                            <?php
                            // Edit/Delete menu for comment author or moderators
                            $is_comment_author  = is_user_logged_in() && ( (int) $current_user_id === (int) $comment->user_id );
                            $can_moderate       = current_user_can( 'moderate_comments' );
                            $can_edit_comment   = is_user_logged_in() && ( $is_comment_author || $can_moderate );
                            $can_delete_comment = $can_edit_comment;
                            ?>
                            <?php if ( $can_edit_comment || $can_delete_comment ) : ?>
                                <div class="vh360-comment-actions-menu-wrapper">
                                    <button type="button"
                                            class="vh360-kebab-toggle"
                                            aria-label="<?php esc_attr_e('Comment options', 'videohub360-theme'); ?>"
                                            aria-haspopup="true"
                                            aria-expanded="false">
                                        <span class="vh360-kebab-dot"></span>
                                        <span class="vh360-kebab-dot"></span>
                                        <span class="vh360-kebab-dot"></span>
                                    </button>
                                    <!-- Menu dropdown (will be handled by JS) -->
                                    <div class="vh360-actions-menu vh360-actions-menu--hidden" role="menu">
                                        <?php if ( $can_edit_comment ) : ?>
                                            <button type="button"
                                                    class="vh360-actions-menu-item vh360-comment-edit-btn"
                                                    data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>"
                                                    role="menuitem">
                                                <?php esc_html_e( 'Edit', 'videohub360-theme' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ( $can_delete_comment ) : ?>
                                            <button type="button"
                                                    class="vh360-actions-menu-item vh360-comment-delete-btn"
                                                    data-comment-id="<?php echo esc_attr( $comment->comment_ID ); ?>"
                                                    role="menuitem">
                                                <?php esc_html_e( 'Delete', 'videohub360-theme' ); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comment Bubble (text ONLY) -->
                        <div class="vh360-comment-bubble">
                            <div class="vh360-comment-text">
                                <?php echo vh360_format_activity_content( $comment->comment_content, 'comment', $comment->comment_post_ID ); ?>
                            </div>
                        </div>
                        
                        <!-- Actions Row (UNDER bubble) -->
                        <div class="vh360-comment-actions">
                            <span class="vh360-comment-time">
                                <?php echo esc_html(human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp')) . ' ago'); ?>
                            </span>
                            
                            <?php if (is_user_logged_in()) : ?>
                                <span class="vh360-action-separator">•</span>
                                <?php
                                // Get like data from bulk-fetched arrays
                                $like_count = isset($like_counts[$comment->comment_ID]) ? $like_counts[$comment->comment_ID] : 0;
                                $user_has_liked = in_array($comment->comment_ID, $user_liked_comments);
                                $liked_class = $user_has_liked ? 'vh360-liked' : '';
                                ?>
                                <button class="vh360-action-like <?php echo esc_attr($liked_class); ?>" 
                                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                        aria-label="<?php esc_attr_e('Like this comment', 'videohub360-theme'); ?>">
                                    <?php esc_html_e('Like', 'videohub360-theme'); ?>
                                </button>
                                
                                <span class="vh360-action-separator">•</span>
                                <button class="vh360-action-reply" 
                                        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                        aria-label="<?php esc_attr_e('Reply to this comment', 'videohub360-theme'); ?>">
                                    <?php esc_html_e('Reply', 'videohub360-theme'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($like_count > 0) : ?>
                                <span class="vh360-like-count" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                    <?php echo esc_html(sprintf(_n('%d like', '%d likes', $like_count, 'videohub360-theme'), $like_count)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($children)) : 
                $reply_count = count($children);
            ?>
                <!-- Replies Container -->
                <div class="vh360-comment-replies">
                    <button type="button" class="vh360-toggle-replies" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php 
                        /* translators: %d: number of replies */
                        printf( _n( 'View %d reply', 'View %d replies', $reply_count, 'videohub360-theme' ), $reply_count ); 
                        ?>
                    </button>
                    
                    <div class="vh360-replies-list vh360-replies-list--hidden">
                        <?php foreach ($children as $reply) : ?>
                            <!-- Each reply uses same .vh360-comment-item structure -->
                            <div class="vh360-comment-item vh360-comment-reply" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                <div class="vh360-comment-row">
                                    <!-- Avatar Column -->
                                    <div class="vh360-comment-avatar">
                                        <?php
                                        $reply_avatar = $reply->user_id ? $reply->user_id : $reply->comment_author_email;
                                        echo get_avatar($reply_avatar, 32);
                                        ?>
                                    </div>
                                    
                                    <!-- Content Column -->
                                    <div class="vh360-comment-main">
                                        <!-- Header Row (OUTSIDE bubble) - Name + Kebab -->
                                        <div class="vh360-comment-header">
                                            <strong class="vh360-comment-author" data-user-id="<?php echo esc_attr($reply->user_id); ?>" data-username="<?php echo esc_attr($reply->user_id ? get_userdata($reply->user_id)->user_login : ''); ?>">
                                                <?php echo esc_html($reply->comment_author); ?>
                                            </strong>
                                            <?php
                                            // Edit/Delete menu for reply author or moderators
                                            $is_reply_author = is_user_logged_in() && ( (int) $current_user_id === (int) $reply->user_id );
                                            $can_moderate_reply = current_user_can( 'moderate_comments' );
                                            $can_edit_reply   = is_user_logged_in() && ( $is_reply_author || $can_moderate_reply );
                                            $can_delete_reply = $can_edit_reply;
                                            ?>
                                            <?php if ( $can_edit_reply || $can_delete_reply ) : ?>
                                                <div class="vh360-comment-actions-menu-wrapper">
                                                    <button type="button"
                                                            class="vh360-kebab-toggle"
                                                            aria-label="<?php esc_attr_e('Comment options', 'videohub360-theme'); ?>"
                                                            aria-haspopup="true"
                                                            aria-expanded="false">
                                                        <span class="vh360-kebab-dot"></span>
                                                        <span class="vh360-kebab-dot"></span>
                                                        <span class="vh360-kebab-dot"></span>
                                                    </button>
                                                    <!-- Menu dropdown (will be handled by JS) -->
                                                    <div class="vh360-actions-menu vh360-actions-menu--hidden" role="menu">
                                                        <?php if ( $can_edit_reply ) : ?>
                                                            <button type="button"
                                                                    class="vh360-actions-menu-item vh360-comment-edit-btn"
                                                                    data-comment-id="<?php echo esc_attr( $reply->comment_ID ); ?>"
                                                                    role="menuitem">
                                                                <?php esc_html_e( 'Edit', 'videohub360-theme' ); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ( $can_delete_reply ) : ?>
                                                            <button type="button"
                                                                    class="vh360-actions-menu-item vh360-comment-delete-btn"
                                                                    data-comment-id="<?php echo esc_attr( $reply->comment_ID ); ?>"
                                                                    role="menuitem">
                                                                <?php esc_html_e( 'Delete', 'videohub360-theme' ); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Comment Bubble (text ONLY) -->
                                        <div class="vh360-comment-bubble">
                                            <div class="vh360-comment-text">
                                                <?php echo vh360_format_activity_content( $reply->comment_content, 'comment', $reply->comment_post_ID ); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions Row (UNDER bubble) -->
                                        <div class="vh360-comment-actions">
                                            <span class="vh360-comment-time">
                                                <?php echo esc_html(human_time_diff(strtotime($reply->comment_date_gmt), current_time('timestamp')) . ' ago'); ?>
                                            </span>
                                            
                                            <?php if (is_user_logged_in()) : ?>
                                                <span class="vh360-action-separator">•</span>
                                                <?php
                                                // Get like data from bulk-fetched arrays
                                                $reply_like_count = isset($like_counts[$reply->comment_ID]) ? $like_counts[$reply->comment_ID] : 0;
                                                $reply_user_has_liked = in_array($reply->comment_ID, $user_liked_comments);
                                                $reply_liked_class = $reply_user_has_liked ? 'vh360-liked' : '';
                                                ?>
                                                <button class="vh360-action-like <?php echo esc_attr($reply_liked_class); ?>" 
                                                        data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>"
                                                        aria-label="<?php esc_attr_e('Like this comment', 'videohub360-theme'); ?>">
                                                    <?php esc_html_e('Like', 'videohub360-theme'); ?>
                                                </button>
                                                
                                                <span class="vh360-action-separator">•</span>
                                                <button class="vh360-action-reply" 
                                                        data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>"
                                                        aria-label="<?php esc_attr_e('Reply to this comment', 'videohub360-theme'); ?>">
                                                    <?php esc_html_e('Reply', 'videohub360-theme'); ?>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($reply_like_count > 0) : ?>
                                                <span class="vh360-like-count" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                    <?php echo esc_html(sprintf(_n('%d like', '%d likes', $reply_like_count, 'videohub360-theme'), $reply_like_count)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * AJAX handler to add a new activity comment or reply.
 */
function vh360_ajax_add_activity_comment() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to comment.', 'videohub360-theme')));
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'vh360_comment_post')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'videohub360-theme')));
    }

    $post_id   = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $parent_id = isset($_POST['parent_id']) ? absint($_POST['parent_id']) : 0;
    $content   = isset($_POST['comment']) ? wp_kses_post(trim(wp_unslash($_POST['comment']))) : '';

    if (!$post_id || get_post_type($post_id) !== 'vh360_post') {
        wp_send_json_error(array('message' => __('Invalid post.', 'videohub360-theme')));
    }

    if ('' === $content) {
        wp_send_json_error(array('message' => __('Please enter a comment.', 'videohub360-theme')));
    }

    $user_id = get_current_user_id();
    $user    = get_userdata($user_id);

    $commentdata = array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $user ? $user->display_name : '',
        'comment_author_email' => $user ? $user->user_email : '',
        'comment_content'      => $content,
        'comment_type'         => '',
        'comment_parent'       => $parent_id,
        'user_id'              => $user_id,
        'comment_approved'     => 1,
    );

    $comment_id = wp_insert_comment($commentdata);
    if (!$comment_id) {
        wp_send_json_error(array('message' => __('Could not save comment. Please try again.', 'videohub360-theme')));
    }
    
    // Fire action for notification system
    do_action('vh360_comment_created', $comment_id, $post_id);

    ob_start();
    vh360_render_activity_comments($post_id);
    $html = ob_get_clean();
    
    // Get new comment count for this specific post - real-time update (Fix #4)
    $comment_count_args = array(
        'post_id' => $post_id,
        'status' => 'approve',
        'count' => true,
    );
    $new_comment_count = get_comments($comment_count_args);

    wp_send_json_success(array(
        'message' => __('Comment added.', 'videohub360-theme'),
        'html'    => $html,
        'new_comment_count' => $new_comment_count,
    ));
}
add_action('wp_ajax_vh360_add_activity_comment', 'vh360_ajax_add_activity_comment');

/**
 * AJAX handler for submitting main post comment (top-level)
 * Separate from vh360_add_activity_comment which handles replies
 */
add_action('wp_ajax_vh360_submit_comment', 'vh360_handle_submit_comment');
function vh360_handle_submit_comment() {
    check_ajax_referer('vh360_comment_post', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to comment.', 'videohub360-theme')));
    }
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    
    if (!$post_id || empty($content)) {
        wp_send_json_error(array('message' => __('Invalid data.', 'videohub360-theme')));
    }
    
    $post = get_post($post_id);
    if (!$post || get_post_type($post_id) !== 'vh360_post') {
        wp_send_json_error(array('message' => __('Invalid post.', 'videohub360-theme')));
    }
    
    $user = wp_get_current_user();
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_parent' => 0, // Top-level comment
        'comment_content' => $content,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'user_id' => $user->ID,
        'comment_approved' => 1,
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => __('Failed to post comment.', 'videohub360-theme')));
    }
    
    // Fire action for notification system
    do_action('vh360_comment_created', $comment_id, $post_id);
    
    // Get new comment count for this specific post (Fix #4)
    $comment_count_args = array(
        'post_id' => $post_id,
        'status' => 'approve',
        'count' => true,
    );
    $new_comment_count = get_comments($comment_count_args);

    // Render updated comments section
    ob_start();
    vh360_render_activity_comments($post_id);
    $comments_html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $comments_html,
        'comment_id' => $comment_id,
        'new_comment_count' => $new_comment_count
    ));
}

/**
 * AJAX handler to search users for @mention autocomplete.
 *
 * Expects:
 * - q: partial query string.
 *
 * Returns a JSON list of matching users (id, name, handle, avatar).
 */
function vh360_ajax_search_user_mentions() {
    // Only logged-in users can search mentions.
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array(
            'message' => __( 'You must be logged in to search mentions.', 'videohub360-theme' ),
        ) );
    }

    check_ajax_referer( 'vh360_activity_nonce', 'nonce' );

    $query = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
    if ( '' === $query || strlen( $query ) < 2 ) {
        wp_send_json_success( array( 'users' => array() ) );
    }

    $args = array(
        'number'         => 8,
        'search'         => '*' . $query . '*',
        'search_columns' => array( 'user_login', 'user_nicename', 'display_name' ),
        'fields'         => array( 'ID', 'user_login', 'user_nicename', 'display_name' ),
    );

    $user_query = new WP_User_Query( $args );
    $results    = array();

    if ( ! empty( $user_query->results ) ) {
        foreach ( $user_query->results as $user ) {
            $first_name = get_user_meta( $user->ID, 'first_name', true );
            $last_name  = get_user_meta( $user->ID, 'last_name', true );
            $full_name  = trim( sprintf( '%s %s', $first_name, $last_name ) );
            if ( '' === $full_name ) {
                $full_name = $user->display_name ? $user->display_name : $user->user_login;
            }

            $results[] = array(
                'id'     => $user->ID,
                'name'   => $full_name,
                'handle'      => $user->user_login,
                'username'    => $user->user_nicename,
                'avatar'      => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
                'profile_url' => function_exists( 'vh360_get_profile_url' ) ? vh360_get_profile_url( $user->ID ) : get_author_posts_url( $user->ID ),
            );
        }
    }

    wp_send_json_success(
        array(
            'users' => $results,
        )
    );
}

/**
 * AJAX: Delete a community post from the activity feed.
 */
function vh360_ajax_delete_community_post() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in to delete posts.', 'videohub360-theme' ) ) );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'vh360_post_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post.', 'videohub360-theme' ) ) );
    }

    $post = get_post( $post_id );
    if ( ! $post || 'vh360_post' !== $post->post_type ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post.', 'videohub360-theme' ) ) );
    }

    $current_user_id = get_current_user_id();
    if ( (int) $post->post_author !== (int) $current_user_id && ! current_user_can( 'delete_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to delete this post.', 'videohub360-theme' ) ) );
    }

    if ( ! wp_trash_post( $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not delete post. Please try again.', 'videohub360-theme' ) ) );
    }

    wp_send_json_success( array( 'message' => __( 'Post deleted.', 'videohub360-theme' ) ) );
}
add_action( 'wp_ajax_vh360_delete_community_post', 'vh360_ajax_delete_community_post' );

/**
 * AJAX: Update/edit a community post from the activity feed.
 */
function vh360_ajax_update_community_post() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in to edit posts.', 'videohub360-theme' ) ) );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'vh360_post_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ) );
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    $content = isset( $_POST['content'] ) ? wp_kses_post( trim( wp_unslash( $_POST['content'] ) ) ) : '';

    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post.', 'videohub360-theme' ) ) );
    }
    if ( '' === $content ) {
        wp_send_json_error( array( 'message' => __( 'Post content cannot be empty.', 'videohub360-theme' ) ) );
    }

    $post = get_post( $post_id );
    if ( ! $post || 'vh360_post' !== $post->post_type ) {
        wp_send_json_error( array( 'message' => __( 'Invalid post.', 'videohub360-theme' ) ) );
    }

    $current_user_id = get_current_user_id();
    if ( (int) $post->post_author !== (int) $current_user_id && ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this post.', 'videohub360-theme' ) ) );
    }

    $update = wp_update_post(
        array(
            'ID'           => $post_id,
            'post_content' => $content,
        ),
        true
    );

    if ( is_wp_error( $update ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not update post. Please try again.', 'videohub360-theme' ) ) );
    }

    // Re-render the updated post HTML so the front-end can replace it.
    $updated_post = get_post( $post_id );
    ob_start();
    vh360_render_community_post( $updated_post, true );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'message' => __( 'Post updated.', 'videohub360-theme' ),
            'html'    => $html,
        )
    );
}
add_action( 'wp_ajax_vh360_update_community_post', 'vh360_ajax_update_community_post' );

/**
 * AJAX: Delete an activity comment.
 */
function vh360_ajax_delete_activity_comment() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in to delete comments.', 'videohub360-theme' ) ) );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'vh360_comment_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ) );
    }

    $comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
    if ( ! $comment_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ) );
    }

    $comment = get_comment( $comment_id );
    if ( ! $comment ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ) );
    }

    $post_id = (int) $comment->comment_post_ID;
    $post    = get_post( $post_id );
    if ( ! $post || 'vh360_post' !== $post->post_type ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment context.', 'videohub360-theme' ) ) );
    }

    $current_user_id = get_current_user_id();
    $is_comment_author = (int) $comment->user_id === (int) $current_user_id;
    $can_moderate      = current_user_can( 'moderate_comments' );

    if ( ! $is_comment_author && ! $can_moderate ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to delete this comment.', 'videohub360-theme' ) ) );
    }

    if ( ! wp_delete_comment( $comment_id, true ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not delete comment. Please try again.', 'videohub360-theme' ) ) );
    }

    ob_start();
    vh360_render_activity_comments( $post_id );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'message' => __( 'Comment deleted.', 'videohub360-theme' ),
            'html'    => $html,
        )
    );
}
add_action( 'wp_ajax_vh360_delete_activity_comment', 'vh360_ajax_delete_activity_comment' );

/**
 * AJAX: Update/edit an activity comment.
 */
function vh360_ajax_update_activity_comment() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( 'You must be logged in to edit comments.', 'videohub360-theme' ) ) );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'vh360_comment_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'videohub360-theme' ) ) );
    }

    $comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0;
    $content    = isset( $_POST['content'] ) ? wp_kses_post( trim( wp_unslash( $_POST['content'] ) ) ) : '';

    if ( ! $comment_id ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ) );
    }
    if ( '' === $content ) {
        wp_send_json_error( array( 'message' => __( 'Comment content cannot be empty.', 'videohub360-theme' ) ) );
    }

    $comment = get_comment( $comment_id );
    if ( ! $comment ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment.', 'videohub360-theme' ) ) );
    }

    $post_id = (int) $comment->comment_post_ID;
    $post    = get_post( $post_id );
    if ( ! $post || 'vh360_post' !== $post->post_type ) {
        wp_send_json_error( array( 'message' => __( 'Invalid comment context.', 'videohub360-theme' ) ) );
    }

    $current_user_id = get_current_user_id();
    $is_comment_author = (int) $comment->user_id === (int) $current_user_id;
    $can_moderate      = current_user_can( 'moderate_comments' );

    if ( ! $is_comment_author && ! $can_moderate ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this comment.', 'videohub360-theme' ) ) );
    }

    $updated = wp_update_comment(
        array(
            'comment_ID'      => $comment_id,
            'comment_content' => $content,
        ),
        true
    );

    if ( ! $updated ) {
        wp_send_json_error( array( 'message' => __( 'Could not update comment. Please try again.', 'videohub360-theme' ) ) );
    }

    ob_start();
    vh360_render_activity_comments( $post_id );
    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'message' => __( 'Comment updated.', 'videohub360-theme' ),
            'html'    => $html,
        )
    );
}
add_action( 'wp_ajax_vh360_update_activity_comment', 'vh360_ajax_update_activity_comment' );

add_action( 'wp_ajax_vh360_search_user_mentions', 'vh360_ajax_search_user_mentions' );

/**
 * Render the share modal HTML in footer.
 * Only renders on activity feed pages where community posts are shown.
 */
function vh360_render_share_modal() {
    // Only render on activity feed template or pages with community posts
    if (!is_user_logged_in()) {
        return;
    }
    
    // Check if this is the activity feed template
    if (!is_page_template('template-activity-feed.php')) {
        return;
    }
    ?>
    <div id="vh360-share-modal" class="vh360-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="vh360-share-modal-title">
        <div class="vh360-modal vh360-share-modal">
            <div class="vh360-modal-header">
                <h3 id="vh360-share-modal-title"><?php esc_html_e('Share Post', 'videohub360-theme'); ?></h3>
                <button type="button" class="vh360-modal-close" aria-label="<?php esc_attr_e('Close modal', 'videohub360-theme'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="vh360-modal-content">
                <div class="vh360-share-composer">
                    <div class="vh360-share-user">
                        <?php echo get_avatar(get_current_user_id(), 40); ?>
                        <span class="vh360-share-user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                    </div>
                    <textarea 
                        id="vh360-share-comment" 
                        class="vh360-share-textarea" 
                        placeholder="<?php esc_attr_e('Say something about this...', 'videohub360-theme'); ?>" 
                        rows="3"
                        aria-label="<?php esc_attr_e('Add a comment to your share', 'videohub360-theme'); ?>"></textarea>
                </div>
                
                <div id="vh360-share-preview" class="vh360-share-preview">
                    <!-- Original post preview will be inserted here -->
                </div>
            </div>
            
            <div class="vh360-modal-actions">
                <button type="button" class="vh360-btn vh360-btn-primary" id="vh360-share-timeline-btn">
                    <?php esc_html_e('Share to Timeline', 'videohub360-theme'); ?>
                </button>
                <button type="button" class="vh360-btn vh360-btn-secondary vh360-modal-close">
                    <?php esc_html_e('Cancel', 'videohub360-theme'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'vh360_render_share_modal');


