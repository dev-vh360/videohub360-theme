<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for the theme.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * VH360 AJAX Handlers Class
 */
class VH360_Ajax_Handlers {
    
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
        // Load more videos (logged in and non-logged in)
        add_action('wp_ajax_vh360_load_more_videos', array($this, 'load_more_videos'));
        add_action('wp_ajax_nopriv_vh360_load_more_videos', array($this, 'load_more_videos'));
        
        // Join group (logged in only)
        add_action('wp_ajax_vh360_join_group', array($this, 'join_group'));
        
        // Leave group (logged in only)
        add_action('wp_ajax_vh360_leave_group', array($this, 'leave_group'));
        
        // Upload gallery image (logged in only)
        add_action('wp_ajax_vh360_upload_gallery_image', array($this, 'upload_gallery_image'));
        
        // Delete gallery image (logged in only)
        add_action('wp_ajax_vh360_delete_gallery_image', array($this, 'delete_gallery_image'));
        
        // Upload video file (logged in only)
        add_action('wp_ajax_vh360_upload_video_file', array($this, 'upload_video_file'));
        
        // Create video frontend (logged in only)
        add_action('wp_ajax_vh360_create_video_frontend', array($this, 'create_video_frontend'));
        
        // Save course frontend (logged in only)
        add_action('wp_ajax_vh360_save_course_frontend', array($this, 'save_course_frontend'));
        
        // Delete course frontend (logged in only)
        add_action('wp_ajax_vh360_delete_course_frontend', array($this, 'delete_course_frontend'));
        
        // Videos tab pagination (logged in only)
        add_action('wp_ajax_vh360_load_videos_tab', array($this, 'load_videos_tab'));
    }
    
    /**
     * Load more videos for infinite scroll
     */
    public function load_more_videos() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get parameters
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? absint($_POST['posts_per_page']) : 12;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // Build query args
        $args = array(
            'post_type' => 'videohub360',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
        );
        
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'videohub360_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            wp_send_json_error(array(
                'message' => esc_html__('No more videos found.', 'videohub360-theme'),
            ));
        }
        
        ob_start();
        
        while ($query->have_posts()) {
            $query->the_post();
            
            // Use video card component if it exists
            if (locate_template('template-parts/components/card-video.php')) {
                get_template_part('template-parts/components/card-video', null, array(
                    'video_id' => get_the_ID(),
                    'size' => 'medium',
                ));
            } else {
                // Fallback HTML
                ?>
                <div class="video-card">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('videohub360-video-thumb'); ?>
                        <h3><?php the_title(); ?></h3>
                    </a>
                </div>
                <?php
            }
        }
        
        wp_reset_postdata();
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'page' => $page,
            'max_pages' => $query->max_num_pages,
        ));
    }
    
    /**
     * Join a group
     */
    public function join_group() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to join a group.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_group_action')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get group ID
        $group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
        
        if (!$group_id) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid group ID.', 'videohub360-theme'),
            ));
        }
        
        $user_id = get_current_user_id();
        
        // Check if plugin function exists
        if (function_exists('videohub360_join_group')) {
            $result = videohub360_join_group($user_id, $group_id);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => esc_html__('Successfully joined the group!', 'videohub360-theme'),
                ));
            } else {
                wp_send_json_error(array(
                    'message' => esc_html__('Failed to join the group.', 'videohub360-theme'),
                ));
            }
        }
        
        // Fallback: add to user meta
        $user_groups = get_user_meta($user_id, '_vh360_joined_groups', true);
        if (!is_array($user_groups)) {
            $user_groups = array();
        }
        
        if (in_array($group_id, $user_groups)) {
            wp_send_json_error(array(
                'message' => esc_html__('You are already a member of this group.', 'videohub360-theme'),
            ));
        }
        
        $user_groups[] = $group_id;
        update_user_meta($user_id, '_vh360_joined_groups', $user_groups);
        
        // Update group member count
        $member_count = get_post_meta($group_id, '_vh360_member_count', true);
        $member_count = $member_count ? absint($member_count) : 0;
        update_post_meta($group_id, '_vh360_member_count', $member_count + 1);
        
        wp_send_json_success(array(
            'message' => esc_html__('Successfully joined the group!', 'videohub360-theme'),
        ));
    }
    
    /**
     * Leave a group
     */
    public function leave_group() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to leave a group.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_group_action')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get group ID
        $group_id = isset($_POST['group_id']) ? absint($_POST['group_id']) : 0;
        
        if (!$group_id) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid group ID.', 'videohub360-theme'),
            ));
        }
        
        $user_id = get_current_user_id();
        
        // Check if plugin function exists
        if (function_exists('videohub360_leave_group')) {
            $result = videohub360_leave_group($user_id, $group_id);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => esc_html__('Successfully left the group.', 'videohub360-theme'),
                ));
            } else {
                wp_send_json_error(array(
                    'message' => esc_html__('Failed to leave the group.', 'videohub360-theme'),
                ));
            }
        }
        
        // Fallback: remove from user meta
        $user_groups = get_user_meta($user_id, '_vh360_joined_groups', true);
        if (!is_array($user_groups)) {
            $user_groups = array();
        }
        
        $key = array_search($group_id, $user_groups);
        if ($key === false) {
            wp_send_json_error(array(
                'message' => esc_html__('You are not a member of this group.', 'videohub360-theme'),
            ));
        }
        
        unset($user_groups[$key]);
        update_user_meta($user_id, '_vh360_joined_groups', array_values($user_groups));
        
        // Update group member count
        $member_count = get_post_meta($group_id, '_vh360_member_count', true);
        $member_count = $member_count ? absint($member_count) : 0;
        if ($member_count > 0) {
            update_post_meta($group_id, '_vh360_member_count', $member_count - 1);
        }
        
        wp_send_json_success(array(
            'message' => esc_html__('Successfully left the group.', 'videohub360-theme'),
        ));
    }
    
    /**
     * Upload gallery image
     */
    public function upload_gallery_image() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to upload images.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_gallery_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array(
                'message' => esc_html__('No image was uploaded.', 'videohub360-theme'),
            ));
        }
        
        // Check user capability
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to upload files.', 'videohub360-theme'),
            ));
        }
        
        // Handle the upload
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $attachment_id = media_handle_upload('image', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array(
                'message' => $attachment_id->get_error_message(),
            ));
        }
        
        wp_send_json_success(array(
            'message' => esc_html__('Image uploaded successfully.', 'videohub360-theme'),
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
        ));
    }
    
    /**
     * Delete gallery image
     */
    public function delete_gallery_image() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to delete images.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_gallery_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get attachment ID
        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        
        if (!$attachment_id) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid attachment ID.', 'videohub360-theme'),
            ));
        }
        
        // Check if user can delete this attachment
        $attachment_author = get_post_field('post_author', $attachment_id);
        $current_user_id = get_current_user_id();
        
        if ($attachment_author != $current_user_id && !current_user_can('delete_others_posts')) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to delete this image.', 'videohub360-theme'),
            ));
        }
        
        // Delete the attachment
        $deleted = wp_delete_attachment($attachment_id, true);
        
        if (!$deleted) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to delete the image.', 'videohub360-theme'),
            ));
        }
        
        wp_send_json_success(array(
            'message' => esc_html__('Image deleted successfully.', 'videohub360-theme'),
        ));
    }
    
    /**
     * Upload video file
     */
    public function upload_video_file() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to upload files.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_video_upload')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if upload is enabled
        $settings = vh360_get_video_upload_settings();
        if (empty($settings['enable_video_upload'])) {
            wp_send_json_error(array(
                'message' => esc_html__('Video upload is currently disabled.', 'videohub360-theme'),
            ));
        }
        
        // Check user capability
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to upload files.', 'videohub360-theme'),
            ));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['vh360_video_file']) || $_FILES['vh360_video_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array(
                'message' => esc_html__('No file uploaded.', 'videohub360-theme'),
            ));
        }
        
        $file = $_FILES['vh360_video_file'];
        
        // Check file size
        $max_size = isset($settings['max_file_size']) ? $settings['max_file_size'] : 500;
        $max_size_bytes = $max_size * 1024 * 1024;
        
        if ($file['size'] > $max_size_bytes) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('File size exceeds maximum allowed (%d MB).', 'videohub360-theme'),
                    $max_size
                ),
            ));
        }
        
        // Check file extension and MIME type
        $allowed_formats = isset($settings['allowed_formats']) ? $settings['allowed_formats'] : 'mp4,webm,mov';
        $allowed_formats_array = array_map('trim', explode(',', $allowed_formats));
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_formats_array)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('File type not allowed. Allowed: %s', 'videohub360-theme'),
                    $allowed_formats
                ),
            ));
        }
        
        // Verify MIME type for additional security
        $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        
        if (empty($filetype['type']) || strpos($filetype['type'], 'video/') !== 0) {
            wp_send_json_error(array(
                'message' => __('Invalid file type. Only video files are allowed.', 'videohub360-theme'),
            ));
        }
        
        // Handle the upload
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $attachment_id = media_handle_upload('vh360_video_file', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array(
                'message' => $attachment_id->get_error_message(),
            ));
        }
        
        $video_url = wp_get_attachment_url($attachment_id);
        $file_size_mb = round($file['size'] / 1024 / 1024, 2);
        
        wp_send_json_success(array(
            'message' => esc_html__('Video uploaded successfully!', 'videohub360-theme'),
            'attachment_id' => $attachment_id,
            'video_url' => $video_url,
            'file_name' => basename($file['name']),
            'file_size' => $file_size_mb . ' MB',
        ));
    }
    
    /**
     * Search members (AJAX)
     */
    public function search_members() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_members_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get directory page ID and resolve effective mode
        $directory_page_id = isset($_POST['directory_page_id']) ? absint($_POST['directory_page_id']) : 0;
        
        // SECURITY STEP 3: Fail closed when directory context is missing or invalid
        $mode = null;
        if ($directory_page_id > 0) {
            $template = get_page_template_slug($directory_page_id);
            if ($template === 'template-members-directory.php') {
                $mode = vh360_get_members_directory_effective_mode($directory_page_id);
            }
        }
        
        // SECURITY: Fail closed - require valid directory context
        if (!$mode) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid directory context.', 'videohub360-theme'),
            ));
        }
        
        // Get members options for per_page
        $members_options = get_option('vh360_members_options', array());
        $per_page = isset($members_options['per_page']) ? absint($members_options['per_page']) : 12;
        
        // Check membership access for directory
        $has_directory_access = true;
        if (function_exists('vh360_can_access_membership_feature')) {
            $has_directory_access = vh360_can_access_membership_feature('members_directory', get_current_user_id());
        }
        
        // Limit results for non-members
        if (!$has_directory_access) {
            $per_page = 5;
        }
        
        // Get parameters
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $category = isset($_POST['category']) ? sanitize_title($_POST['category']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'registered';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // Build query args based on effective mode
        $args = array(
            'audience' => $mode['audience'],
            'account_types' => $mode['professionals_account_types'],
            'require_professional_approval' => $mode['professionals_require_approval'],
            'search' => $search,
            'category' => $category,
            'orderby' => $orderby,
            'order' => $order,
            'number' => $per_page,
            'offset' => $has_directory_access ? (($page - 1) * $per_page) : 0, // Non-members always see page 1
        );
        
        // For all_members mode, include role filter
        if ($mode['audience'] === 'all_members' && !empty($role)) {
            $args['role'] = $role;
        }
        
        // Handle join date filter
        if (isset($_POST['join_date']) && !empty($_POST['join_date'])) {
            $join_date = sanitize_text_field($_POST['join_date']);
            switch ($join_date) {
                case 'week':
                    $args['date_query'] = array(
                        array('after' => '7 days ago'),
                    );
                    break;
                case 'month':
                    $args['date_query'] = array(
                        array('after' => '30 days ago'),
                    );
                    break;
                case 'year':
                    $args['date_query'] = array(
                        array('after' => '1 year ago'),
                    );
                    break;
            }
        }
        
        $members = vh360_get_members($args);
        
        // SECURITY STEP 2: Belt-and-suspenders post-filter for professionals_only mode
        // This ensures no non-professionals leak even if the query was modified by another plugin/filter
        // NOTE: This post-filter is a safety net that should rarely execute. In normal operation,
        // the query builder's fail-closed logic prevents any non-professionals from being returned.
        // The N user meta lookups here (N = results per page, typically 12-24) are acceptable because:
        // 1. This only runs if another plugin bypassed our meta_query (rare)
        // 2. WordPress caches user meta automatically
        // 3. Security is more important than optimization for this edge case
        if ($mode['audience'] === 'professionals_only' && !empty($members)) {
            $allowed_account_types = !empty($mode['professionals_account_types']) 
                ? $mode['professionals_account_types'] 
                : array('professional', 'organization');
            $require_approval = $mode['professionals_require_approval'];
            
            $members = array_filter($members, function($member) use ($allowed_account_types, $require_approval) {
                // Check account type
                $account_type = get_user_meta($member->ID, '_vh360_account_type', true);
                if (!in_array($account_type, $allowed_account_types, true)) {
                    return false;
                }
                
                // If approval is required, check approval status
                if ($require_approval && $account_type === 'professional') {
                    $status = get_user_meta($member->ID, '_vh360_professional_status', true);
                    // Approved if: status='approved', OR status is empty/not set (legacy accounts)
                    // Organizations don't have professional_status meta, so they pass through
                    if ($status !== '' && $status !== 'approved') {
                        return false;
                    }
                }
                
                return true;
            });
            
            // Re-index array after filtering
            $members = array_values($members);
        }
        
        if (empty($members)) {
            wp_send_json_error(array(
                'message' => esc_html__('No members found.', 'videohub360-theme'),
            ));
        }
        
        ob_start();
        
        foreach ($members as $member) {
            get_template_part('template-parts/components/card-profile', null, array(
                'user_id' => $member->ID,
                'show_avatar' => true,
                'show_bio' => true,
                'show_stats' => !empty($mode['show_card_stats']),
                'show_follow_button' => !empty($mode['show_card_follow_button']),
                'avatar_size' => 80,
            ));
        }
        
        // Add upgrade notice for non-members
        if (!$has_directory_access) {
            echo vh360_render_membership_gate();
        }
        
        $html = ob_get_clean();
        
        // Get total count using shared helper (no pagination)
        // NOTE: Count uses the same query builder with the same fail-closed logic,
        // so it will be consistent with the display query in normal operation.
        // If a plugin modifies the query to bypass our meta_query, the post-filter
        // will remove leaked results from display but the count might not match.
        // This is an acceptable trade-off: security (no leaked data) > perfect pagination.
        $total_args = $args;
        unset($total_args['number']);
        unset($total_args['offset']);
        $total = vh360_get_member_count($total_args);
        
        // Limit total for non-members to prevent pagination beyond first page
        if (!$has_directory_access) {
            $total = min($total, 5);
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'page' => $page,
            'total' => $total,
            'max_pages' => ceil($total / $per_page),
        ));
    }
    
    /**
     * Delete video (AJAX)
     */
    public function delete_video() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to delete videos.', 'videohub360-theme'),
            ));
        }
        
        // Get video ID
        $video_id = isset($_POST['video_id']) ? absint($_POST['video_id']) : 0;
        
        if (!$video_id) {
            wp_send_json_error(array(
                'message' => esc_html__('Invalid video ID.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_delete_video_' . $video_id)) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Check if user can delete this video
        if (!vh360_user_can_delete_video($video_id)) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to delete this video.', 'videohub360-theme'),
            ));
        }
        
        // Delete the video
        $deleted = wp_trash_post($video_id);
        
        if (!$deleted) {
            wp_send_json_error(array(
                'message' => esc_html__('Failed to delete the video.', 'videohub360-theme'),
            ));
        }
        
        wp_send_json_success(array(
            'message' => esc_html__('Video deleted successfully.', 'videohub360-theme'),
        ));
    }
    
    /**
     * Load more activities (AJAX)
     */
    public function load_activities() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_activity_nonce')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed.', 'videohub360-theme'),
            ));
        }
        
        // Get parameters
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $limit = 20;
        
        // Get activities
        $activities = vh360_get_activities(array(
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset,
            'use_cache' => false,
        ));
        
        if (empty($activities)) {
            wp_send_json_error(array(
                'message' => esc_html__('No more activities found.', 'videohub360-theme'),
            ));
        }
        
        ob_start();
        
        foreach ($activities as $activity) {
            $user = get_userdata($activity['user_id']);
            if (!$user) {
                continue;
            }
            
            $profile_url = vh360_get_profile_url($activity['user_id']);
            $time_ago = vh360_format_activity_time($activity['timestamp']);
            $icon = vh360_get_activity_icon($activity['type']);
            
            ?>
            <div class="vh360-activity-item" data-activity-id="<?php echo esc_attr($activity['id']); ?>">
                <div class="vh360-activity-avatar">
                    <?php echo get_avatar($activity['user_id'], 40); ?>
                </div>
                <div class="vh360-activity-content">
                    <div class="vh360-activity-header">
                        <?php echo wp_kses_post($icon); ?>
                        <a href="<?php echo esc_url($profile_url); ?>" class="vh360-activity-user">
                            <?php echo esc_html($user->display_name); ?>
                        </a>
                        <span class="vh360-activity-time"><?php echo esc_html($time_ago); ?></span>
                    </div>
                    <div class="vh360-activity-body">
                        <?php
                        $content = $activity['content'];
                        switch ($activity['type']) {
                            case 'video_upload':
                                echo '<p>' . wp_kses_post(vh360_format_activity_content_link($content, __('uploaded a new video:', 'videohub360-theme'))) . '</p>';
                                break;
                            case 'post_publish':
                                echo '<p>' . wp_kses_post(vh360_format_activity_content_link($content, __('published a post:', 'videohub360-theme'))) . '</p>';
                                break;
                            case 'new_member':
                                echo '<p>' . esc_html__('joined the community', 'videohub360-theme') . '</p>';
                                break;
                            case 'profile_update':
                                echo '<p>' . esc_html__('updated their profile', 'videohub360-theme') . '</p>';
                                break;
                            case 'milestone':
                                echo '<p>';
                                if (!empty($content['link'])) {
                                    echo '<a href="' . esc_url($content['link']) . '">' . esc_html($content['title']) . '</a> ';
                                } else {
                                    echo esc_html($content['title']) . ' ';
                                }
                                if (!empty($content['meta'])) {
                                    echo esc_html($content['meta']);
                                }
                                echo '</p>';
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'offset' => $offset + count($activities),
            'count' => count($activities),
        ));
    }
    
    /**
     * Create video from frontend
     */
    public function create_video_frontend() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => esc_html__('You must be logged in to create videos.', 'videohub360-theme'),
            ));
        }
        
        // Verify nonce
        if (!isset($_POST['vh360_create_video_nonce']) || !wp_verify_nonce($_POST['vh360_create_video_nonce'], 'vh360_create_video')) {
            wp_send_json_error(array(
                'message' => esc_html__('Security check failed. Please try again.', 'videohub360-theme'),
            ));
        }
        
        // License soft-lock check
        $vh360_is_licensed = true;
        if (function_exists('vh360_theme_is_license_valid')) {
            $vh360_is_licensed = (bool) vh360_theme_is_license_valid();
        } elseif (function_exists('videohub360_license_is_valid')) {
            $vh360_is_licensed = (bool) videohub360_license_is_valid();
        }
        
        if (!$vh360_is_licensed) {
            wp_send_json_error(array(
                'message' => esc_html__('Your VideoHub360 license is inactive. Activate your license to create videos.', 'videohub360-theme'),
            ));
        }
        
        // Check user permissions - use helper with administrator override
        $can_create_videos = function_exists('vh360_user_can_create_videos')
            ? vh360_user_can_create_videos()
            : (current_user_can('manage_options') || current_user_can('vh360_create_videos'));
            
        if (!$can_create_videos) {
            wp_send_json_error(array(
                'message' => esc_html__('You do not have permission to create videos.', 'videohub360-theme'),
            ));
        }
        
        // Check if this is an edit or new creation
        $video_id = isset($_POST['video_id']) ? absint($_POST['video_id']) : 0;
        $edit_mode = $video_id > 0;

        if ($edit_mode) {
            // Verify user can edit this video
            $existing_post = get_post($video_id);
            if (!$existing_post || $existing_post->post_type !== 'videohub360' || $existing_post->post_author != get_current_user_id()) {
                wp_send_json_error(array(
                    'message' => esc_html__('You do not have permission to edit this video.', 'videohub360-theme'),
                ));
            }
        }
        
        // Get and sanitize inputs
        $title = isset($_POST['vh360_video_title']) ? sanitize_text_field($_POST['vh360_video_title']) : '';
        $description = isset($_POST['vh360_video_description']) ? wp_kses_post($_POST['vh360_video_description']) : '';
        $excerpt = isset($_POST['vh360_video_excerpt']) ? sanitize_textarea_field($_POST['vh360_video_excerpt']) : '';
        $action = isset($_POST['vh360_action']) ? sanitize_text_field($_POST['vh360_action']) : 'draft';
        
        // Video source
        $video_url = isset($_POST['vh360_video_url']) ? esc_url_raw($_POST['vh360_video_url']) : '';
        $custom_html = isset($_POST['vh360_custom_html']) ? vh360_sanitize_embed_code($_POST['vh360_custom_html']) : '';
        
        // Ad settings
        $ad_video_url = isset($_POST['vh360_ad_video_url']) ? esc_url_raw($_POST['vh360_ad_video_url']) : '';
        $midroll_ad_video_url = isset($_POST['vh360_midroll_ad_video_url']) ? esc_url_raw($_POST['vh360_midroll_ad_video_url']) : '';
        $midroll_ad_timing = isset($_POST['vh360_midroll_ad_timing']) ? absint($_POST['vh360_midroll_ad_timing']) : 0;
        $postroll_ad_video_url = isset($_POST['vh360_postroll_ad_video_url']) ? esc_url_raw($_POST['vh360_postroll_ad_video_url']) : '';
        
        // Advanced settings
        $override_quality = isset($_POST['vh360_override_quality']) ? 'yes' : 'no';
        $video_quality = isset($_POST['vh360_video_quality']) ? sanitize_text_field($_POST['vh360_video_quality']) : 'auto';
        $video_mirror = isset($_POST['vh360_video_mirror']) ? sanitize_text_field($_POST['vh360_video_mirror']) : '';
        $poster_url = isset($_POST['vh360_poster_url']) ? esc_url_raw($_POST['vh360_poster_url']) : '';
        
        // Tags
        $tags = isset($_POST['vh360_tags']) ? sanitize_text_field($_POST['vh360_tags']) : '';
        
        // Livestream settings (moved here for validation logic)
        $is_live = isset($_POST['vh360_is_live']) ? sanitize_text_field($_POST['vh360_is_live']) : 'no';
        $stream_type = isset($_POST['vh360_type']) ? sanitize_text_field($_POST['vh360_type']) : 'embed';
        $live_start_time = isset($_POST['vh360_live_start_time']) ? sanitize_text_field($_POST['vh360_live_start_time']) : '';
        $offline_message = isset($_POST['vh360_offline_message']) ? wp_kses_post($_POST['vh360_offline_message']) : '';
        $viewer_count = isset($_POST['vh360_viewer_count']) ? 'yes' : 'no';
        $chat_enabled = isset($_POST['vh360_chat_enabled']) ? 'yes' : 'no';
        $chat_placement = isset($_POST['vh360_chat_placement']) ? sanitize_text_field($_POST['vh360_chat_placement']) : '';
        $live_badge = isset($_POST['vh360_live_badge']) ? 'yes' : 'no';
        $badge_text = isset($_POST['vh360_badge_text']) ? sanitize_text_field($_POST['vh360_badge_text']) : 'LIVE';
        $badge_color = isset($_POST['vh360_badge_color']) ? sanitize_hex_color($_POST['vh360_badge_color']) : '#e53935';
        
        // Stream type specific fields
        $embed_code = isset($_POST['vh360_embed_code']) ? vh360_sanitize_embed_code($_POST['vh360_embed_code']) : '';
        $stream_url = isset($_POST['vh360_stream_url']) ? esc_url_raw($_POST['vh360_stream_url']) : '';
        $api_url = isset($_POST['vh360_api_url']) ? esc_url_raw($_POST['vh360_api_url']) : '';
        
        // Agora specific fields
        $agora_mode = isset($_POST['vh360_agora_mode']) ? sanitize_text_field($_POST['vh360_agora_mode']) : 'interactive';
        $agora_channel_name = isset($_POST['vh360_agora_channel_name']) ? sanitize_text_field($_POST['vh360_agora_channel_name']) : '';
        
        // Auto-generate Agora channel name if empty (like go-live implementation)
        if (empty($agora_channel_name) && $stream_type === 'agora') {
            $agora_channel_name = 'video-' . get_current_user_id() . '-' . time();
        }
        
        $agora_everyone_is_host = isset($_POST['vh360_agora_everyone_is_host']) ? 'yes' : 'no';
        $require_passcode = isset($_POST['vh360_require_passcode']) ? 'yes' : 'no';
        $new_passcode = ($require_passcode === 'yes') ? sanitize_text_field($_POST['vh360_host_passcode'] ?? '') : '';
        
        // Validate required fields
        if (empty($title)) {
            wp_send_json_error(array(
                'message' => esc_html__('Please provide a video title.', 'videohub360-theme'),
            ));
        }
        
        // No validation for video source - matching backend behavior
        // Backend save_meta_boxes() method does not validate video_url or custom_html
        // This allows flexibility for all content types including Agora livestreams
        
        // Determine post status
        $post_status = ($action === 'publish') ? 'publish' : 'draft';
        
        // Create or update the video post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_excerpt' => $excerpt,
            'post_type' => 'videohub360',
            'post_status' => $post_status,
        );
        
        if ($edit_mode) {
            $post_data['ID'] = $video_id;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_data['post_author'] = get_current_user_id();
            $post_id = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array(
                'message' => $post_id->get_error_message(),
            ));
        }
        
        // Save meta fields
        update_post_meta($post_id, 'video_url', $video_url);
        update_post_meta($post_id, 'videohub360_custom_html', $custom_html);
        update_post_meta($post_id, 'ad_video_url', $ad_video_url);
        update_post_meta($post_id, 'midroll_ad_video_url', $midroll_ad_video_url);
        update_post_meta($post_id, 'midroll_ad_timing', $midroll_ad_timing);
        update_post_meta($post_id, 'postroll_ad_video_url', $postroll_ad_video_url);
        update_post_meta($post_id, '_vh360_video_quality', $video_quality);
        update_post_meta($post_id, '_vh360_video_mirror', $video_mirror);
        update_post_meta($post_id, '_vh360_override_quality_settings', $override_quality);
        update_post_meta($post_id, '_videohub360_post_views_count', 0);
        
        if (!empty($poster_url)) {
            update_post_meta($post_id, '_vh360_poster_url', $poster_url);
        }
        
        // Save livestream meta fields
        update_post_meta($post_id, '_vh360_is_live', $is_live);
        update_post_meta($post_id, '_vh360_type', $stream_type);
        update_post_meta($post_id, '_vh360_live_start_time', $live_start_time);
        update_post_meta($post_id, '_vh360_offline_message', $offline_message);
        update_post_meta($post_id, '_vh360_viewer_count', $viewer_count);
        update_post_meta($post_id, '_vh360_chat_enabled', $chat_enabled);
        update_post_meta($post_id, '_vh360_chat_placement', $chat_placement);
        update_post_meta($post_id, '_vh360_live_badge', $live_badge);
        update_post_meta($post_id, '_vh360_badge_text', $badge_text);
        update_post_meta($post_id, '_vh360_badge_color', $badge_color);
        update_post_meta($post_id, '_vh360_embed_code', $embed_code);
        update_post_meta($post_id, '_vh360_stream_url', $stream_url);
        update_post_meta($post_id, '_vh360_api_url', $api_url);
        update_post_meta($post_id, '_vh360_agora_mode', $agora_mode);
        update_post_meta($post_id, '_vh360_agora_channel_name', $agora_channel_name);
        update_post_meta($post_id, '_vh360_agora_everyone_is_host', $agora_everyone_is_host);
        // Hash passcode before storing; clear if requirement is disabled; keep existing if blank.
        if ($require_passcode !== 'yes') {
            update_post_meta($post_id, '_vh360_host_passcode', '');
        } elseif ($new_passcode !== '') {
            update_post_meta($post_id, '_vh360_host_passcode', wp_hash_password($new_passcode));
        }
        // If require_passcode is yes and the field is blank, keep the existing passcode.
        update_post_meta($post_id, '_vh360_stream_stopped', 'no');
        
        // Ensure context is always 'default' for frontend created videos (not live_room)
        update_post_meta($post_id, '_vh360_context', 'default');
        
        // Handle taxonomies
        // Categories
        if (isset($_POST['vh360_categories']) && is_array($_POST['vh360_categories'])) {
            $categories = array_map('absint', $_POST['vh360_categories']);
            wp_set_post_terms($post_id, $categories, 'videohub360_category');
        }
        
        // Series
        if (isset($_POST['vh360_series'])) {
            $series = absint($_POST['vh360_series']);

            // When Course / Lesson Features are enabled, non-admin users may only assign
            // a lesson to a series they own (server-side enforcement mirrors the dropdown filter).
            if (
                $series
                && function_exists('videohub360_course_features_enabled')
                && videohub360_course_features_enabled()
                && !current_user_can('manage_options')
            ) {
                $owner_id = (int) get_term_meta($series, '_vh360_course_owner_user_id', true);

                if ($owner_id !== get_current_user_id()) {
                    wp_send_json_error(array(
                        'message' => esc_html__('You do not have permission to assign this lesson to that course.', 'videohub360-theme'),
                    ));
                }
            }

            wp_set_post_terms($post_id, $series ? array($series) : array(), 'videohub360_series');
        }
        
        // Location
        if (isset($_POST['vh360_location'])) {
            $location = absint($_POST['vh360_location']);
            wp_set_post_terms($post_id, $location ? array($location) : array(), 'videohub360_location');
        }
        
        // Tags (using post_tag taxonomy)
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags);
        }
        
        // Lesson Details meta (only when Course / Lesson Features are enabled)
        if ( function_exists('videohub360_course_features_enabled') && videohub360_course_features_enabled() ) {
            update_post_meta(
                $post_id,
                '_vh360_lesson_module_title',
                isset($_POST['_vh360_lesson_module_title']) ? sanitize_text_field(wp_unslash($_POST['_vh360_lesson_module_title'])) : ''
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_module_number',
                isset($_POST['_vh360_lesson_module_number']) ? absint($_POST['_vh360_lesson_module_number']) : 0
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_number',
                isset($_POST['_vh360_lesson_number']) ? absint($_POST['_vh360_lesson_number']) : 0
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_duration',
                isset($_POST['_vh360_lesson_duration']) ? sanitize_text_field(wp_unslash($_POST['_vh360_lesson_duration'])) : ''
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_resource_url',
                isset($_POST['_vh360_lesson_resource_url']) ? esc_url_raw(wp_unslash($_POST['_vh360_lesson_resource_url'])) : ''
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_resource_label',
                isset($_POST['_vh360_lesson_resource_label']) ? sanitize_text_field(wp_unslash($_POST['_vh360_lesson_resource_label'])) : ''
            );
            update_post_meta(
                $post_id,
                '_vh360_lesson_is_preview',
                isset($_POST['_vh360_lesson_is_preview']) ? 'yes' : 'no'
            );
        }
        
        // Handle featured image upload
        if (isset($_FILES['vh360_featured_image']) && $_FILES['vh360_featured_image']['error'] === UPLOAD_ERR_OK) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            
            $attachment_id = media_handle_upload('vh360_featured_image', $post_id);
            
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
        
        $status_message = $edit_mode 
            ? ($post_status === 'publish' 
                ? esc_html__('Video updated successfully!', 'videohub360-theme')
                : esc_html__('Video saved as draft successfully!', 'videohub360-theme'))
            : ($post_status === 'publish' 
                ? esc_html__('Video published successfully!', 'videohub360-theme')
                : esc_html__('Video saved as draft successfully!', 'videohub360-theme'));
        
        wp_send_json_success(array(
            'message' => $status_message,
            'post_id' => $post_id,
            'permalink' => get_permalink($post_id),
            'edit_link' => get_edit_post_link($post_id, 'raw'),
        ));
    }
    
    /**
     * Save (create or update) a course/series term from the frontend.
     */
    public function save_course_frontend() {
        // Verify nonce.
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
        }

        // Must be logged in.
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to manage courses.', 'videohub360-theme')));
        }

        // Course features must be enabled.
        if (!function_exists('videohub360_course_features_enabled') || !videohub360_course_features_enabled()) {
            wp_send_json_error(array('message' => esc_html__('Course features are not enabled.', 'videohub360-theme')));
        }

        $current_user_id = get_current_user_id();

        // Permission check.
        $can_create_courses = function_exists('vh360_user_can_create_videos')
            ? vh360_user_can_create_videos($current_user_id)
            : (current_user_can('manage_options') || current_user_can('vh360_create_videos'));

        if (!$can_create_courses) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to manage courses.', 'videohub360-theme')));
        }

        // Sanitize inputs.
        $course_name        = isset($_POST['vh360_course_name']) ? sanitize_text_field(wp_unslash($_POST['vh360_course_name'])) : '';
        $course_description = isset($_POST['vh360_course_description']) ? wp_kses_post(wp_unslash($_POST['vh360_course_description'])) : '';
        $course_id          = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

        if (empty($course_name)) {
            wp_send_json_error(array('message' => esc_html__('Course name is required.', 'videohub360-theme')));
        }

        $edit_mode = $course_id > 0;

        if ($edit_mode) {
            // Verify the term exists.
            $term = get_term($course_id, 'videohub360_series');
            if (!$term || is_wp_error($term)) {
                wp_send_json_error(array('message' => esc_html__('Course not found.', 'videohub360-theme')));
            }

            // Ownership check for non-admins.
            $owner_id = (int) get_term_meta($course_id, '_vh360_course_owner_user_id', true);
            if (!current_user_can('manage_options') && $owner_id !== $current_user_id) {
                wp_send_json_error(array('message' => esc_html__('You do not have permission to edit this course.', 'videohub360-theme')));
            }

            // Update term name/description.
            $result = wp_update_term($course_id, 'videohub360_series', array(
                'name'        => $course_name,
                'description' => $course_description,
            ));

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            $term_id = $course_id;

        } else {
            // Create new term.
            $result = wp_insert_term(
                $course_name,
                'videohub360_series',
                array(
                    'description' => $course_description,
                    'slug'        => sanitize_title($course_name),
                )
            );

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            $term_id = $result['term_id'];

            // Set ownership and instructor.
            update_term_meta($term_id, '_vh360_course_owner_user_id', $current_user_id);
            update_term_meta($term_id, '_vh360_course_instructor_user_id', $current_user_id);
        }

        // Save course term meta.
        update_term_meta($term_id, '_vh360_course_subtitle', sanitize_text_field(wp_unslash($_POST['_vh360_course_subtitle'] ?? '')));
        update_term_meta($term_id, '_vh360_course_short_description', sanitize_textarea_field(wp_unslash($_POST['_vh360_course_short_description'] ?? '')));
        update_term_meta($term_id, '_vh360_course_level', sanitize_key(wp_unslash($_POST['_vh360_course_level'] ?? '')));
        update_term_meta($term_id, '_vh360_course_duration', sanitize_text_field(wp_unslash($_POST['_vh360_course_duration'] ?? '')));
        update_term_meta($term_id, '_vh360_course_cta_text', sanitize_text_field(wp_unslash($_POST['_vh360_course_cta_text'] ?? '')));
        update_term_meta($term_id, '_vh360_course_cta_url', esc_url_raw(wp_unslash($_POST['_vh360_course_cta_url'] ?? '')));
        update_term_meta($term_id, '_vh360_course_order', absint($_POST['_vh360_course_order'] ?? 0));

        // Required membership — preserve special 'any' value.
        $required_membership = isset($_POST['_vh360_course_required_membership'])
            ? sanitize_key(wp_unslash($_POST['_vh360_course_required_membership']))
            : '';
        update_term_meta($term_id, '_vh360_course_required_membership', $required_membership);

        // Remove course image flag.
        if (!empty($_POST['vh360_remove_course_image'])) {
            delete_term_meta($term_id, '_vh360_course_featured_image_id');
        }

        // Handle course featured image upload.
        if (isset($_FILES['vh360_course_featured_image']) && $_FILES['vh360_course_featured_image']['error'] === UPLOAD_ERR_OK) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('vh360_course_featured_image', 0);

            if (!is_wp_error($attachment_id)) {
                update_term_meta($term_id, '_vh360_course_featured_image_id', $attachment_id);
            }
        }

        $message = $edit_mode
            ? esc_html__('Course updated successfully!', 'videohub360-theme')
            : esc_html__('Course created successfully!', 'videohub360-theme');

        $term_link = get_term_link($term_id, 'videohub360_series');

        wp_send_json_success(array(
            'message'   => $message,
            'term_id'   => $term_id,
            'term_link' => is_wp_error($term_link) ? '' : $term_link,
        ));
    }

    /**
     * Delete a course/series term from the frontend.
     */
    public function delete_course_frontend() {
        // Verify nonce.
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
        }

        // Must be logged in.
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in to manage courses.', 'videohub360-theme')));
        }

        // Course features must be enabled.
        if (!function_exists('videohub360_course_features_enabled') || !videohub360_course_features_enabled()) {
            wp_send_json_error(array('message' => esc_html__('Course features are not enabled.', 'videohub360-theme')));
        }

        $current_user_id = get_current_user_id();
        $course_id       = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

        if (!$course_id) {
            wp_send_json_error(array('message' => esc_html__('Invalid course ID.', 'videohub360-theme')));
        }

        // Verify the term exists.
        $term = get_term($course_id, 'videohub360_series');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(array('message' => esc_html__('Course not found.', 'videohub360-theme')));
        }

        // Ownership check for non-admins.
        $owner_id = (int) get_term_meta($course_id, '_vh360_course_owner_user_id', true);
        if (!current_user_can('manage_options') && $owner_id !== $current_user_id) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to delete this course.', 'videohub360-theme')));
        }

        // Block deletion if the course has lessons assigned.
        $lessons = function_exists('videohub360_get_course_lessons')
            ? videohub360_get_course_lessons($course_id, array('post_status' => array('publish', 'draft', 'pending', 'private')))
            : array();

        if (!empty($lessons)) {
            wp_send_json_error(array(
                'message' => esc_html__('This course has lessons assigned. Remove or reassign the lessons before deleting the course.', 'videohub360-theme'),
            ));
        }

        $result = wp_delete_term($course_id, 'videohub360_series');

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => esc_html__('Course deleted successfully.', 'videohub360-theme'),
        ));
    }

    /**
     * Load videos tab content via AJAX (for pagination)
     */
    public function load_videos_tab() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_dashboard_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('You must be logged in', 'videohub360-theme')));
        }
        
        $current_user_id = get_current_user_id();
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // Build query - exclude Live Rooms
        $args = array(
            'post_type' => 'videohub360',
            'author' => $current_user_id,
            'post_status' => $status,
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_vh360_context',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_vh360_context',
                    'value' => 'live_room',
                    'compare' => '!=',
                ),
            ),
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $videos_query = new WP_Query($args);
        
        // Generate videos HTML
        ob_start();
        if ($videos_query->have_posts()) {
            while ($videos_query->have_posts()) {
                $videos_query->the_post();
                ?>
                <div class="vh360-video-grid-item">
                    <article class="vh360-video-card" data-video-id="<?php the_ID(); ?>">
                        <a href="<?php the_permalink(); ?>" class="vh360-video-card-link">
                            <div class="vh360-video-thumbnail">
                                <div class="vh360-video-thumbnail-wrapper">
                                    <?php
                                    $thumbnail = vh360_get_video_thumbnail(get_the_ID(), 'videohub360-video-thumb');
                                    if ($thumbnail) :
                                    ?>
                                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                                    <?php else : ?>
                                        <div class="vh360-video-thumbnail-placeholder">
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $duration = vh360_get_video_duration(get_the_ID());
                                    if ($duration) :
                                    ?>
                                        <span class="vh360-video-duration"><?php echo esc_html($duration); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="vh360-video-card-body">
                                <h3 class="vh360-video-title"><?php the_title(); ?></h3>
                                
                                <div class="vh360-video-meta">
                                    <span class="vh360-video-views">
                                        <?php echo esc_html(vh360_format_number(vh360_get_video_views(get_the_ID()))); ?> <?php esc_html_e('views', 'videohub360-theme'); ?>
                                    </span>
                                    <span>•</span>
                                    <span class="vh360-video-date">
                                        <?php echo esc_html(get_the_date()); ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        
                        <!-- Video Actions -->
                        <div class="vh360-video-actions">
                            <a href="#create-video" class="vh360-video-action vh360-edit-video" data-video-id="<?php the_ID(); ?>" title="<?php esc_attr_e('Edit', 'videohub360-theme'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <button 
                                class="vh360-video-action vh360-video-delete" 
                                data-video-id="<?php the_ID(); ?>"
                                data-nonce="<?php echo esc_attr(wp_create_nonce('vh360_delete_video_' . get_the_ID())); ?>"
                                title="<?php esc_attr_e('Delete', 'videohub360-theme'); ?>"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </article>
                </div>
                <?php
            }
        }
        $videos_html = ob_get_clean();
        
        // Generate pagination HTML
        ob_start();
        if ($videos_query->max_num_pages > 1) {
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '?paged=%#%',
                'current' => max(1, $paged),
                'total' => $videos_query->max_num_pages,
                'prev_text' => '←',
                'next_text' => '→',
                'type' => 'plain',
                'add_args' => array(),
            );
            
            if (!empty($status) && $status !== 'publish') {
                $pagination_args['add_args']['video_status'] = $status;
            }
            if (!empty($search)) {
                $pagination_args['add_args']['video_search'] = $search;
            }
            
            echo paginate_links($pagination_args);
        }
        $pagination_html = ob_get_clean();
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'videos_html' => $videos_html,
            'pagination_html' => $pagination_html,
            'total_pages' => $videos_query->max_num_pages,
            'current_page' => $paged
        ));
    }
    
}

// Initialize the AJAX handlers and store instance
$vh360_ajax_handlers = new VH360_Ajax_Handlers();

// Add AJAX actions for members directory
add_action('wp_ajax_vh360_search_members', array($vh360_ajax_handlers, 'search_members'));
add_action('wp_ajax_nopriv_vh360_search_members', array($vh360_ajax_handlers, 'search_members'));

// Add AJAX actions for activity feed
add_action('wp_ajax_vh360_load_activities', array($vh360_ajax_handlers, 'load_activities'));
add_action('wp_ajax_nopriv_vh360_load_activities', array($vh360_ajax_handlers, 'load_activities'));

add_action('wp_ajax_vh360_filter_activities', array($vh360_ajax_handlers, 'load_activities'));
add_action('wp_ajax_nopriv_vh360_filter_activities', array($vh360_ajax_handlers, 'load_activities'));

// Add AJAX action for deleting videos
add_action('wp_ajax_vh360_delete_video', array($vh360_ajax_handlers, 'delete_video'));

/**
 * Bulletin AJAX Handlers
 */

/**
 * Mark bulletin as read
 */
function vh360_ajax_mark_bulletin_read() {
    check_ajax_referer('vh360_bulletin_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $bulletin_id = isset($_POST['bulletin_id']) ? intval($_POST['bulletin_id']) : 0;
    
    if (!$bulletin_id) {
        wp_send_json_error(array(
            'message' => esc_html__('Invalid bulletin ID', 'videohub360-theme')
        ));
    }
    
    $user_id = get_current_user_id();
    
    $result = vh360_mark_bulletin_read($bulletin_id, $user_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => esc_html__('Bulletin marked as read', 'videohub360-theme'),
            'unread_count' => vh360_get_unread_bulletin_count($user_id)
        ));
    }
    
    wp_send_json_error(array(
        'message' => esc_html__('Failed to mark as read', 'videohub360-theme')
    ));
}
add_action('wp_ajax_vh360_mark_bulletin_read', 'vh360_ajax_mark_bulletin_read');

/**
 * Dismiss bulletin
 */
function vh360_ajax_dismiss_bulletin() {
    check_ajax_referer('vh360_bulletin_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $bulletin_id = isset($_POST['bulletin_id']) ? intval($_POST['bulletin_id']) : 0;
    
    if (!$bulletin_id) {
        wp_send_json_error(array(
            'message' => esc_html__('Invalid bulletin ID', 'videohub360-theme')
        ));
    }
    
    $user_id = get_current_user_id();
    
    $result = vh360_dismiss_bulletin($bulletin_id, $user_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => esc_html__('Bulletin dismissed', 'videohub360-theme'),
            'unread_count' => vh360_get_unread_bulletin_count($user_id)
        ));
    }
    
    wp_send_json_error(array(
        'message' => esc_html__('Failed to dismiss', 'videohub360-theme')
    ));
}
add_action('wp_ajax_vh360_dismiss_bulletin', 'vh360_ajax_dismiss_bulletin');

/**
 * Mark all bulletins as read
 */
function vh360_ajax_mark_all_bulletins_read() {
    check_ajax_referer('vh360_bulletin_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $user_id = get_current_user_id();
    $bulletins = vh360_get_active_bulletins('all', $user_id);
    
    foreach ($bulletins as $bulletin) {
        vh360_mark_bulletin_read($bulletin->ID, $user_id);
    }
    
    wp_send_json_success(array(
        'message' => esc_html__('All bulletins marked as read', 'videohub360-theme'),
        'unread_count' => 0
    ));
}
add_action('wp_ajax_vh360_mark_all_bulletins_read', 'vh360_ajax_mark_all_bulletins_read');

/**
 * Get bulletin count (for real-time updates)
 */
function vh360_ajax_get_bulletin_count() {
    check_ajax_referer('vh360_bulletin_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $user_id = get_current_user_id();
    $count = vh360_get_unread_bulletin_count($user_id);
    
    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_vh360_get_bulletin_count', 'vh360_ajax_get_bulletin_count');

/**
 * Get post data for editing
 */
function vh360_ajax_get_post_data() {
    check_ajax_referer('vh360_edit_post_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array(
            'message' => esc_html__('Invalid post ID', 'videohub360-theme')
        ));
    }
    
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'post') {
        wp_send_json_error(array(
            'message' => esc_html__('Post not found', 'videohub360-theme')
        ));
    }
    
    // Check if user can edit this post
    if (!vh360_user_can_manage_dashboard_post($post_id, get_current_user_id())) {
        wp_send_json_error(array(
            'message' => esc_html__('You are not allowed to edit this post.', 'videohub360-theme')
        ));
    }
    
    // Get categories
    $categories = wp_get_post_categories($post_id);
    
    // Get tags as comma-separated string
    $tags = get_the_tags($post_id);
    $tag_names = array();
    if ($tags) {
        foreach ($tags as $tag) {
            $tag_names[] = $tag->name;
        }
    }
    
    // Get featured image
    $thumbnail_id = get_post_thumbnail_id($post_id);
    $thumbnail_url = '';
    if ($thumbnail_id) {
        $thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'medium');
    }
    
    wp_send_json_success(array(
        'title' => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
        'status' => $post->post_status,
        'categories' => $categories,
        'tags' => implode(', ', $tag_names),
        'thumbnail_id' => $thumbnail_id,
        'thumbnail_url' => $thumbnail_url
    ));
}
add_action('wp_ajax_vh360_get_post_data', 'vh360_ajax_get_post_data');

/**
 * Update post via AJAX
 */
function vh360_ajax_update_post() {
    check_ajax_referer('vh360_edit_post_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array(
            'message' => esc_html__('Invalid post ID', 'videohub360-theme')
        ));
    }
    
    // Check if user can edit this post
    if (!vh360_user_can_manage_dashboard_post($post_id, get_current_user_id())) {
        wp_send_json_error(array(
            'message' => esc_html__('You are not allowed to edit this post.', 'videohub360-theme')
        ));
    }
    
    // Sanitize and validate inputs
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
    $excerpt = isset($_POST['excerpt']) ? sanitize_textarea_field($_POST['excerpt']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
    
    // Validate status
    if (!in_array($status, array('publish', 'draft'), true)) {
        $status = 'draft';
    }
    
    // Validate title
    if (empty($title)) {
        wp_send_json_error(array(
            'message' => esc_html__('Post title is required', 'videohub360-theme')
        ));
    }
    
    // Update post
    $updated = wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status' => $status
    ), true);
    
    if (is_wp_error($updated)) {
        wp_send_json_error(array(
            'message' => $updated->get_error_message()
        ));
    }
    
    // Update categories
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        $categories = array_map('absint', $_POST['categories']);
        wp_set_post_categories($post_id, $categories);
    }
    
    // Update tags
    if (isset($_POST['tags'])) {
        $tags = sanitize_text_field($_POST['tags']);
        wp_set_post_tags($post_id, $tags);
    }
    
    // Handle featured image update
    if (isset($_POST['featured_image_id'])) {
        $thumbnail_id = absint($_POST['featured_image_id']);
        if ($thumbnail_id > 0) {
            set_post_thumbnail($post_id, $thumbnail_id);
        } elseif ($thumbnail_id === 0) {
            // Remove featured image if set to 0
            delete_post_thumbnail($post_id);
        }
    }
    
    wp_send_json_success(array(
        'message' => esc_html__('Post updated successfully', 'videohub360-theme')
    ));
}
add_action('wp_ajax_vh360_update_post', 'vh360_ajax_update_post');

/**
 * Delete post via AJAX
 */
function vh360_ajax_delete_post() {
    check_ajax_referer('vh360_edit_post_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array(
            'message' => esc_html__('Invalid post ID', 'videohub360-theme')
        ));
    }
    
    // Check if user can delete this post
    if (!vh360_user_can_manage_dashboard_post($post_id, get_current_user_id())) {
        wp_send_json_error(array(
            'message' => esc_html__('You are not allowed to delete this post.', 'videohub360-theme')
        ));
    }
    
    // Delete the post (move to trash, not permanent delete)
    $deleted = wp_delete_post($post_id, false); // false = don't force delete, move to trash instead
    
    if (!$deleted) {
        wp_send_json_error(array(
            'message' => esc_html__('Failed to delete post', 'videohub360-theme')
        ));
    }
    
    wp_send_json_success(array(
        'message' => esc_html__('Post deleted successfully', 'videohub360-theme')
    ));
}
add_action('wp_ajax_vh360_delete_post', 'vh360_ajax_delete_post');

/**
 * Upload image for posts via AJAX
 */
function vh360_ajax_upload_image() {
    check_ajax_referer('vh360_edit_post_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => esc_html__('Not logged in', 'videohub360-theme')
        ));
    }
    
    // Check if user can upload files
    if (!current_user_can('upload_files')) {
        wp_send_json_error(array(
            'message' => esc_html__('You do not have permission to upload files', 'videohub360-theme')
        ));
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array(
            'message' => esc_html__('No image was uploaded', 'videohub360-theme')
        ));
    }
    
    // Handle the upload
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    
    $attachment_id = media_handle_upload('image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array(
            'message' => $attachment_id->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => esc_html__('Image uploaded successfully', 'videohub360-theme'),
        'attachment_id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id)
    ));
}
add_action('wp_ajax_vh360_upload_image', 'vh360_ajax_upload_image');

/**
 * Save bulletin (create or update)
 */
function vh360_save_bulletin() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_bulletin_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => esc_html__('You must be logged in', 'videohub360-theme')));
    }
    
    // Check permission
    if (!vh360_user_can_create_bulletins()) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to create bulletins', 'videohub360-theme')));
    }
    
    
    // License soft-lock: block creating new bulletins when unlicensed
    if ( function_exists( 'vh360_theme_is_license_valid' ) && ! vh360_theme_is_license_valid() ) {
        // If this request is creating a new bulletin (no bulletin_id), block it. Editing existing bulletins remains allowed.
        $incoming_bulletin_id = isset($_POST['bulletin_id']) ? absint($_POST['bulletin_id']) : 0;
        if ( 0 === $incoming_bulletin_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Your VideoHub360 license is inactive. Activate your license to create bulletins.', 'videohub360-theme' ) ) );
        }
    }
    // Get and sanitize data
    $bulletin_id = isset($_POST['bulletin_id']) ? absint($_POST['bulletin_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
    $excerpt = isset($_POST['excerpt']) ? sanitize_textarea_field($_POST['excerpt']) : '';
    $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : 'normal';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'info';
    $audience_type = isset($_POST['audience_type']) ? sanitize_text_field($_POST['audience_type']) : 'site_wide';
    $audience_target = isset($_POST['audience_target']) ? sanitize_text_field($_POST['audience_target']) : '';
    $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : '';
    $dismissible = isset($_POST['dismissible']) ? sanitize_text_field($_POST['dismissible']) : '0';
    $show_banner = isset($_POST['show_banner']) ? sanitize_text_field($_POST['show_banner']) : '0';
    $featured_image_id = isset($_POST['featured_image_id']) ? absint($_POST['featured_image_id']) : 0;
    
    // Validate title
    if (empty($title)) {
        wp_send_json_error(array('message' => esc_html__('Title is required', 'videohub360-theme')));
    }
    
    // Prepare post data
    $post_data = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_type'    => 'vh360_bulletin',
        'post_status'  => 'publish',
    );
    
    // Update or create
    if ($bulletin_id > 0) {
        // Check permission for this specific bulletin
        if (!vh360_user_can_edit_bulletin($bulletin_id)) {
            wp_send_json_error(array('message' => esc_html__('You do not have permission to edit this bulletin', 'videohub360-theme')));
        }
        
        $post_data['ID'] = $bulletin_id;
        $result = wp_update_post($post_data, true);
        $message = esc_html__('Bulletin updated successfully', 'videohub360-theme');
    } else {
        $post_data['post_author'] = get_current_user_id();
        $result = wp_insert_post($post_data, true);
        $message = esc_html__('Bulletin created successfully', 'videohub360-theme');
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    
    $bulletin_id = $result;
    
    // Save meta fields
    update_post_meta($bulletin_id, '_vh360_bulletin_priority', $priority);
    
    // Validate and save audience type
    $valid_audience_types = array('site_wide', 'role', 'user');
    if (!in_array($audience_type, $valid_audience_types, true)) {
        $audience_type = 'site_wide';
    }
    update_post_meta($bulletin_id, '_vh360_bulletin_type', $audience_type);
    
    // Save audience target (role slug or user ID)
    if ($audience_type === 'site_wide') {
        delete_post_meta($bulletin_id, '_vh360_bulletin_target');
    } else {
        update_post_meta($bulletin_id, '_vh360_bulletin_target', $audience_target);
    }
    
    // Store the dashboard UI "Category" (announcement/alert/update/info) separately
    update_post_meta($bulletin_id, '_vh360_bulletin_display_type', $type);
    
    // Enforce banner permissions and constraints
    $can_manage_banner = current_user_can('vh360_manage_bulletin_banner');
    $final_show_banner = '0';
    
    if ($can_manage_banner && $show_banner === '1') {
        // Only allow banner if audience is site_wide AND priority is urgent
        if ($audience_type === 'site_wide' && $priority === 'urgent') {
            $final_show_banner = '1';
        }
    }
    update_post_meta($bulletin_id, '_vh360_bulletin_show_banner', $final_show_banner);
    
    // Expiry date: store as timestamp (numeric) or delete if empty.
    // Frontend sends YYYY-MM-DD
    if (!empty($expiry_date)) {
        $ts = strtotime($expiry_date . ' 23:59:59'); // end-of-day expiry
        if ($ts) {
            update_post_meta($bulletin_id, '_vh360_bulletin_expiry_date', (int) $ts);
        } else {
            delete_post_meta($bulletin_id, '_vh360_bulletin_expiry_date');
        }
    } else {
        delete_post_meta($bulletin_id, '_vh360_bulletin_expiry_date');
    }
    
    update_post_meta($bulletin_id, '_vh360_bulletin_dismissible', $dismissible);
    
    // Set featured image if provided
    if ($featured_image_id > 0) {
        set_post_thumbnail($bulletin_id, $featured_image_id);
    }
    
    wp_send_json_success(array(
        'message' => $message,
        'bulletin_id' => $bulletin_id,
        'permalink' => get_permalink($bulletin_id)
    ));
}
add_action('wp_ajax_vh360_save_bulletin', 'vh360_save_bulletin');

/**
 * Upload bulletin featured image
 */
function vh360_upload_bulletin_image() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_bulletin_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => esc_html__('You must be logged in to upload images', 'videohub360-theme')));
    }
    
    // Check upload permission
    if (!current_user_can('upload_files')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to upload files', 'videohub360-theme')));
    }
    
    // Check if file was uploaded
    if (empty($_FILES['image'])) {
        wp_send_json_error(array('message' => esc_html__('No image file provided', 'videohub360-theme')));
    }
    
    // Check for upload errors
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => esc_html__('Image upload failed', 'videohub360-theme')));
    }
    
    // Validate file type
    $file_path = $_FILES['image']['tmp_name'];
    $wp_filetype = wp_check_filetype_and_ext($file_path, $_FILES['image']['name']);
    
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    
    if (!$wp_filetype['type'] || !in_array($wp_filetype['type'], $allowed_types, true)) {
        wp_send_json_error(array('message' => esc_html__('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed', 'videohub360-theme')));
    }
    
    // Verify it's actually an image
    $image_info = getimagesize($file_path);
    if ($image_info === false) {
        wp_send_json_error(array('message' => esc_html__('File is not a valid image', 'videohub360-theme')));
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['image']['size'] > $max_size) {
        wp_send_json_error(array('message' => esc_html__('File size too large. Maximum 5MB allowed', 'videohub360-theme')));
    }
    
    // Handle the upload
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    
    $attachment_id = media_handle_upload('image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
    }
    
    $attachment_url = wp_get_attachment_url($attachment_id);
    
    wp_send_json_success(array(
        'message' => esc_html__('Image uploaded successfully', 'videohub360-theme'),
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url
    ));
}
add_action('wp_ajax_vh360_upload_bulletin_image', 'vh360_upload_bulletin_image');

/**
 * Delete bulletin
 */
function vh360_delete_bulletin() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_bulletin_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => esc_html__('You must be logged in', 'videohub360-theme')));
    }
    
    // Get bulletin ID
    $bulletin_id = isset($_POST['bulletin_id']) ? absint($_POST['bulletin_id']) : 0;
    
    if (!$bulletin_id) {
        wp_send_json_error(array('message' => esc_html__('Invalid bulletin ID', 'videohub360-theme')));
    }
    
    // Check permission - user must be able to edit the bulletin (author with permission or admin)
    if (!vh360_user_can_edit_bulletin($bulletin_id)) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to delete this bulletin', 'videohub360-theme')));
    }
    
    // Move to trash (false = don't force delete, moves to trash instead)
    $result = wp_trash_post($bulletin_id);
    
    if (!$result) {
        wp_send_json_error(array('message' => esc_html__('Failed to delete bulletin', 'videohub360-theme')));
    }
    
    wp_send_json_success(array(
        'message' => esc_html__('Bulletin deleted successfully', 'videohub360-theme')
    ));
}
add_action('wp_ajax_vh360_delete_bulletin', 'vh360_delete_bulletin');

/**
 * Get bulletin data for editing
 */
function vh360_get_bulletin() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_bulletin_nonce')) {
        wp_send_json_error(array('message' => esc_html__('Security check failed', 'videohub360-theme')));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => esc_html__('You must be logged in', 'videohub360-theme')));
    }
    
    // Get bulletin ID
    $bulletin_id = isset($_POST['bulletin_id']) ? absint($_POST['bulletin_id']) : 0;
    
    if (!$bulletin_id) {
        wp_send_json_error(array('message' => esc_html__('Invalid bulletin ID', 'videohub360-theme')));
    }
    
    // Get bulletin post
    $bulletin = get_post($bulletin_id);
    
    if (!$bulletin || $bulletin->post_type !== 'vh360_bulletin') {
        wp_send_json_error(array('message' => esc_html__('Bulletin not found', 'videohub360-theme')));
    }
    
    // Check permission
    if (!vh360_user_can_edit_bulletin($bulletin_id)) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to view this bulletin', 'videohub360-theme')));
    }
    
    // Get meta data
    $priority = get_post_meta($bulletin_id, '_vh360_bulletin_priority', true);
    $type = get_post_meta($bulletin_id, '_vh360_bulletin_display_type', true);
    $audience_type = get_post_meta($bulletin_id, '_vh360_bulletin_type', true);
    $audience_target = get_post_meta($bulletin_id, '_vh360_bulletin_target', true);
    $show_banner = get_post_meta($bulletin_id, '_vh360_bulletin_show_banner', true);
    $expiry_ts = get_post_meta($bulletin_id, '_vh360_bulletin_expiry_date', true);
    $expiry_date = '';
    if (!empty($expiry_ts) && is_numeric($expiry_ts)) {
        $expiry_date = wp_date('Y-m-d', (int) $expiry_ts);
    }
    $dismissible = get_post_meta($bulletin_id, '_vh360_bulletin_dismissible', true);
    
    // Backward compatibility for show_banner
    // If meta doesn't exist and bulletin is urgent + site_wide, return '1' for legacy behavior
    if ($show_banner === '' && $priority === 'urgent' && $audience_type === 'site_wide') {
        $show_banner = '1';
    }
    
    // Get featured image
    $featured_image_id = get_post_thumbnail_id($bulletin_id);
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';
    
    wp_send_json_success(array(
        'title' => $bulletin->post_title,
        'content' => $bulletin->post_content,
        'excerpt' => $bulletin->post_excerpt,
        'priority' => $priority ?: 'normal',
        'type' => $type ?: 'info',
        'audience_type' => $audience_type ?: 'site_wide',
        'audience_target' => $audience_target,
        'show_banner' => $show_banner,
        'expiry_date' => $expiry_date,
        'dismissible' => $dismissible,
        'featured_image_id' => $featured_image_id,
        'featured_image_url' => $featured_image_url
    ));
}
add_action('wp_ajax_vh360_get_bulletin', 'vh360_get_bulletin');

/**
 * Load blog posts via AJAX (for filtering and pagination)
 */
function vh360_load_blog_posts() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_blog_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
        return;
    }

    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $category = isset($_POST['category']) ? absint($_POST['category']) : 0;
    $tag = isset($_POST['tag']) ? absint($_POST['tag']) : 0;
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date_desc';

    // Build query args
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => get_option('posts_per_page', 10),
        'paged'          => $page,
    );

    // Apply search
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Apply category filter
    if ($category > 0) {
        $args['cat'] = $category;
    }

    // Apply tag filter
    if ($tag > 0) {
        $args['tag_id'] = $tag;
    }

    // Apply sorting
    switch ($sort) {
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'title_asc':
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
        case 'title_desc':
            $args['orderby'] = 'title';
            $args['order'] = 'DESC';
            break;
        case 'comment_count':
            $args['orderby'] = 'comment_count';
            $args['order'] = 'DESC';
            break;
        case 'date_desc':
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    $query = new WP_Query($args);

    ob_start();

    if ($query->have_posts()) {
        ?>
        <div class="vh360-blog-list">
            <?php 
            while ($query->have_posts()) {
                $query->the_post();
                get_template_part('template-parts/blog/blog-card', null, array(
                    'post_id' => get_the_ID(),
                ));
            }
            wp_reset_postdata();
            ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($query->max_num_pages > 1) : ?>
            <div class="vh360-blog-pagination" id="vh360-blog-pagination">
                <?php
                echo paginate_links(array(
                    'total'     => $query->max_num_pages,
                    'current'   => $page,
                    'prev_text' => '&larr; ' . __('Previous', 'videohub360-theme'),
                    'next_text' => __('Next', 'videohub360-theme') . ' &rarr;',
                    'type'      => 'list',
                    'end_size'  => 1,
                    'mid_size'  => 2,
                ));
                ?>
            </div>
        <?php endif; ?>
        <?php
    } else {
        get_template_part('template-parts/blog/blog-empty');
    }

    $html = ob_get_clean();

    wp_send_json_success(array(
        'html'  => $html,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
    ));
}
add_action('wp_ajax_vh360_load_blog_posts', 'vh360_load_blog_posts');
add_action('wp_ajax_nopriv_vh360_load_blog_posts', 'vh360_load_blog_posts');
