<?php
/**
 * Event AJAX Handlers
 *
 * Handles AJAX requests for frontend event creation, editing, and deletion.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Event_Ajax
 *
 * Handles event-related AJAX operations.
 */
class VH360_Event_Ajax {

    /**
     * Singleton instance.
     *
     * @var VH360_Event_Ajax|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return VH360_Event_Ajax
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action('wp_ajax_vh360_create_event', array($this, 'create_event'));
        add_action('wp_ajax_vh360_update_event', array($this, 'update_event'));
        add_action('wp_ajax_vh360_delete_event', array($this, 'delete_event'));
        add_action('wp_ajax_vh360_get_event', array($this, 'get_event'));
        add_action('wp_ajax_vh360_load_events', array($this, 'load_events'));
        add_action('wp_ajax_vh360_upload_event_image', array($this, 'upload_event_image'));
        
        // RSVP actions
        add_action('wp_ajax_vh360_event_rsvp', array($this, 'handle_rsvp'));
        add_action('wp_ajax_nopriv_vh360_event_rsvp', array($this, 'handle_rsvp'));
        add_action('wp_ajax_vh360_get_event_rsvps', array($this, 'get_event_rsvps'));
    }

    /**
     * Create a new event via AJAX.
     */
    public function create_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to create events', 'videohub360-theme')));
            return;
        }

        // Check user permissions
        if (!vh360_user_can_create_events()) {
            wp_send_json_error(array('message' => __('You do not have permission to create events', 'videohub360-theme')));
            return;
        }

        
        // License soft-lock: block creating new events when unlicensed
        if ( function_exists( 'vh360_theme_is_license_valid' ) && ! vh360_theme_is_license_valid() ) {
            wp_send_json_error( array( 'message' => __( 'Your VideoHub360 license is inactive. Activate your license to create events.', 'videohub360-theme' ) ) );
            return;
        }
// Validate required fields
        if (empty($_POST['title'])) {
            wp_send_json_error(array('message' => __('Event title is required', 'videohub360-theme')));
            return;
        }

        if (empty($_POST['start_date'])) {
            wp_send_json_error(array('message' => __('Start date is required', 'videohub360-theme')));
            return;
        }

        // Sanitize inputs
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content'] ?? '');
        $excerpt = sanitize_textarea_field($_POST['excerpt'] ?? '');
        $post_status = isset($_POST['status']) && in_array($_POST['status'], array('publish', 'draft'), true) 
            ? $_POST['status'] 
            : 'draft';

        // Create event post
        $event_data = array(
            'post_type'    => 'vh360_event',
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id(),
        );

        $event_id = wp_insert_post($event_data);

        if (is_wp_error($event_id)) {
            wp_send_json_error(array('message' => __('Failed to create event', 'videohub360-theme')));
            return;
        }

        // Save event meta
        $this->save_event_meta($event_id, $_POST);

        // Handle featured image if provided
        if (!empty($_POST['featured_image_id'])) {
            set_post_thumbnail($event_id, absint($_POST['featured_image_id']));
        }

        // Set categories if provided
        if (!empty($_POST['categories'])) {
            $categories = array_map('absint', (array) $_POST['categories']);
            wp_set_object_terms($event_id, $categories, 'vh360_event_category');
        }

        // Set tags if provided
        if (!empty($_POST['tags'])) {
            $tags = array_map('sanitize_text_field', (array) $_POST['tags']);
            wp_set_object_terms($event_id, $tags, 'vh360_event_tag');
        }

        wp_send_json_success(array(
            'message'  => __('Event created successfully', 'videohub360-theme'),
            'event_id' => $event_id,
            'url'      => get_permalink($event_id),
        ));
    }

    /**
     * Update an existing event via AJAX.
     */
    public function update_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to update events', 'videohub360-theme')));
            return;
        }

        // Get event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'videohub360-theme')));
            return;
        }

        // Check user permissions
        if (!VH360_Event_Capabilities::can_edit_event($event_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this event', 'videohub360-theme')));
            return;
        }

        // Sanitize inputs
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        $excerpt = sanitize_textarea_field($_POST['excerpt'] ?? '');
        $post_status = isset($_POST['status']) && in_array($_POST['status'], array('publish', 'draft'), true) 
            ? $_POST['status'] 
            : 'draft';

        // Update event post
        $event_data = array(
            'ID'           => $event_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => $post_status,
        );

        $result = wp_update_post($event_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => __('Failed to update event', 'videohub360-theme')));
            return;
        }

        // Save event meta
        $this->save_event_meta($event_id, $_POST);

        // Handle featured image
        if (isset($_POST['featured_image_id'])) {
            if (empty($_POST['featured_image_id'])) {
                delete_post_thumbnail($event_id);
            } else {
                set_post_thumbnail($event_id, absint($_POST['featured_image_id']));
            }
        }

        // Update categories if provided
        if (isset($_POST['categories'])) {
            $categories = array_map('absint', (array) $_POST['categories']);
            wp_set_object_terms($event_id, $categories, 'vh360_event_category');
        }

        // Update tags if provided
        if (isset($_POST['tags'])) {
            $tags = array_map('sanitize_text_field', (array) $_POST['tags']);
            wp_set_object_terms($event_id, $tags, 'vh360_event_tag');
        }

        wp_send_json_success(array(
            'message'  => __('Event updated successfully', 'videohub360-theme'),
            'event_id' => $event_id,
            'url'      => get_permalink($event_id),
        ));
    }

    /**
     * Delete an event via AJAX.
     */
    public function delete_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to delete events', 'videohub360-theme')));
            return;
        }

        // Get event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'videohub360-theme')));
            return;
        }

        // Check user permissions
        if (!VH360_Event_Capabilities::can_delete_event($event_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this event', 'videohub360-theme')));
            return;
        }

        // Delete event
        $result = wp_delete_post($event_id, true);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete event', 'videohub360-theme')));
            return;
        }

        wp_send_json_success(array(
            'message' => __('Event deleted successfully', 'videohub360-theme'),
        ));
    }

    /**
     * Get event data via AJAX.
     */
    public function get_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Get event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'videohub360-theme')));
            return;
        }

        $event = get_post($event_id);

        if (!$event || 'vh360_event' !== $event->post_type) {
            wp_send_json_error(array('message' => __('Event not found', 'videohub360-theme')));
            return;
        }

        // Get event meta
        $event_data = array(
            'id'                     => $event->ID,
            'title'                  => $event->post_title,
            'content'                => $event->post_content,
            'excerpt'                => $event->post_excerpt,
            'status'                 => $event->post_status,
            'featured_image_id'      => get_post_thumbnail_id($event->ID),
            'featured_image_url'     => get_the_post_thumbnail_url($event->ID, 'medium'),
            'kind'                   => get_post_meta($event->ID, '_vh360_event_kind', true),
            'start_date'             => get_post_meta($event->ID, '_vh360_event_start_date', true),
            'start_time'             => get_post_meta($event->ID, '_vh360_event_start_time', true),
            'end_date'               => get_post_meta($event->ID, '_vh360_event_end_date', true),
            'end_time'               => get_post_meta($event->ID, '_vh360_event_end_time', true),
            'location_type'          => get_post_meta($event->ID, '_vh360_event_location_type', true),
            'venue_name'             => get_post_meta($event->ID, '_vh360_event_venue_name', true),
            'venue_address'          => get_post_meta($event->ID, '_vh360_event_venue_address', true),
            'venue_city'             => get_post_meta($event->ID, '_vh360_event_venue_city', true),
            'venue_state'            => get_post_meta($event->ID, '_vh360_event_venue_state', true),
            'venue_zip'              => get_post_meta($event->ID, '_vh360_event_venue_zip', true),
            'venue_country'          => get_post_meta($event->ID, '_vh360_event_venue_country', true),
            'online_url'             => get_post_meta($event->ID, '_vh360_event_online_url', true),
            'event_status'           => get_post_meta($event->ID, '_vh360_event_status', true),
            'registration_required'  => get_post_meta($event->ID, '_vh360_event_registration_required', true),
            'registration_deadline'  => get_post_meta($event->ID, '_vh360_event_registration_deadline', true),
            'max_attendees'          => get_post_meta($event->ID, '_vh360_event_max_attendees', true),
            'cost_type'              => get_post_meta($event->ID, '_vh360_event_cost_type', true),
            'cost_amount'            => get_post_meta($event->ID, '_vh360_event_cost_amount', true),
            'organizer_name'         => get_post_meta($event->ID, '_vh360_event_organizer_name', true),
            'organizer_email'        => get_post_meta($event->ID, '_vh360_event_organizer_email', true),
            'organizer_phone'        => get_post_meta($event->ID, '_vh360_event_organizer_phone', true),
        );

        // Get categories
        $categories = wp_get_object_terms($event->ID, 'vh360_event_category', array('fields' => 'ids'));
        $event_data['categories'] = is_array($categories) ? $categories : array();

        // Get tags
        $tags = wp_get_object_terms($event->ID, 'vh360_event_tag', array('fields' => 'names'));
        $event_data['tags'] = is_array($tags) ? $tags : array();

        wp_send_json_success($event_data);
    }

    /**
     * Load events via AJAX (for filtering and pagination).
     */
    public function load_events() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'upcoming';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? absint($_POST['category']) : 0;

        $args = vh360_get_events_query_args(array(
            'paged' => $paged,
        ));

        // Apply search
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Apply category filter
        if ($category > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'vh360_event_category',
                    'field'    => 'term_id',
                    'terms'    => $category,
                ),
            );
        }

        // Apply time filter using WordPress date
        $today = wp_date('Y-m-d');
        
        if ($filter === 'upcoming') {
            $args['meta_query'] = array(
                array(
                    'key'     => '_vh360_event_start_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            );
        } elseif ($filter === 'past') {
            $args['meta_query'] = array(
                array(
                    'key'     => '_vh360_event_start_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            );
            $args['order'] = 'DESC';
        }

        $query = new WP_Query($args);

        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                get_template_part('template-parts/events/card-event', null, array(
                    'event_id' => get_the_ID(),
                ));
            }
            wp_reset_postdata();
        } else {
            ?>
            <div class="vh360-events-empty">
                <div class="vh360-events-empty-icon">📅</div>
                <h2 class="vh360-events-empty-title">
                    <?php esc_html_e('No Events Found', 'videohub360-theme'); ?>
                </h2>
                <p class="vh360-events-empty-text">
                    <?php esc_html_e('There are currently no events matching your criteria.', 'videohub360-theme'); ?>
                </p>
            </div>
            <?php
        }

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html'       => $html,
            'pagination' => array(
                'current'    => $paged,
                'total'      => $query->max_num_pages,
                'has_next'   => $paged < $query->max_num_pages,
                'has_prev'   => $paged > 1,
            ),
        ));
    }

    /**
     * Save event meta data.
     *
     * @param int   $event_id Event post ID.
     * @param array $data     POST data.
     */
    private function save_event_meta($event_id, $data) {
        $meta_fields = array(
            'kind'                  => 'sanitize_text_field',
            'start_date'            => 'sanitize_text_field',
            'start_time'            => 'sanitize_text_field',
            'end_date'              => 'sanitize_text_field',
            'end_time'              => 'sanitize_text_field',
            'location_type'         => 'sanitize_text_field',
            'venue_name'            => 'sanitize_text_field',
            'venue_address'         => 'sanitize_text_field',
            'venue_city'            => 'sanitize_text_field',
            'venue_state'           => 'sanitize_text_field',
            'venue_zip'             => 'sanitize_text_field',
            'venue_country'         => 'sanitize_text_field',
            'online_url'            => 'esc_url_raw',
            'event_status'          => 'sanitize_text_field',
            'registration_deadline' => 'sanitize_text_field',
            'max_attendees'         => 'absint',
            'cost_type'             => 'sanitize_text_field',
            'cost_amount'           => 'sanitize_text_field',
            'organizer_name'        => 'sanitize_text_field',
            'organizer_email'       => 'sanitize_email',
            'organizer_phone'       => 'sanitize_text_field',
        );

        foreach ($meta_fields as $field => $sanitize_callback) {
            if (isset($data[$field])) {
                $value = call_user_func($sanitize_callback, $data[$field]);
                
                // Validate event kind
                if ($field === 'kind') {
                    $allowed_kinds = array('event', 'availability', 'block');
                    if (!in_array($value, $allowed_kinds, true)) {
                        $value = 'event'; // Default to event if invalid
                    }
                }
                
                update_post_meta($event_id, '_vh360_event_' . $field, $value);
            }
        }
        
        // Auto-set max_attendees to 1 for availability kind if not explicitly set
        if (isset($data['kind']) && $data['kind'] === 'availability') {
            if (!isset($data['max_attendees']) || $data['max_attendees'] === '' || $data['max_attendees'] === null) {
                update_post_meta($event_id, '_vh360_event_max_attendees', 1);
            }
        }

        // Handle checkbox
        $registration_required = isset($data['registration_required']) && $data['registration_required'] ? '1' : '0';
        update_post_meta($event_id, '_vh360_event_registration_required', $registration_required);
    }

    /**
     * Handle RSVP to an event.
     */
    public function handle_rsvp() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to RSVP', 'videohub360-theme')));
            return;
        }

        // Get event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'videohub360-theme')));
            return;
        }

        $user_id = get_current_user_id();

        // Get existing RSVPs
        $rsvps = get_post_meta($event_id, '_vh360_event_rsvps', true);
        if (!is_array($rsvps)) {
            $rsvps = array();
        }

        // Check if user already RSVP'd
        $user_rsvp_index = array_search($user_id, array_column($rsvps, 'user_id'));
        
        if ($user_rsvp_index !== false) {
            // Remove RSVP
            unset($rsvps[$user_rsvp_index]);
            $rsvps = array_values($rsvps); // Re-index array
            $message = __('RSVP cancelled', 'videohub360-theme');
            $is_rsvpd = false;
        } else {
            // Get event kind to apply availability-specific checks
            $event_kind = function_exists('vh360_get_event_kind') ? vh360_get_event_kind($event_id) : 'event';
            
            // Check max attendees limit before adding new RSVP
            $max_attendees = get_post_meta($event_id, '_vh360_event_max_attendees', true);
            
            if (!empty($max_attendees) && is_numeric($max_attendees)) {
                $max_attendees = absint($max_attendees);
                if ($max_attendees > 0 && count($rsvps) >= $max_attendees) {
                    wp_send_json_error(array(
                        'message' => __('Sorry, this event has reached its maximum capacity.', 'videohub360-theme')
                    ));
                    return;
                }
            }
            
            // For availability slots, check for overlaps with blocks and booked slots
            if ($event_kind === 'availability' && function_exists('vh360_check_event_overlap')) {
                $event_post = get_post($event_id);
                if ($event_post) {
                    $author_id = $event_post->post_author;
                    $start_date = get_post_meta($event_id, '_vh360_event_start_date', true);
                    $start_time = get_post_meta($event_id, '_vh360_event_start_time', true);
                    $end_date = get_post_meta($event_id, '_vh360_event_end_date', true);
                    $end_time = get_post_meta($event_id, '_vh360_event_end_time', true);
                    
                    // Check if the availability slot overlaps with any blocks or already booked slots
                    $overlap_check = vh360_check_event_overlap($event_id, $author_id, $start_date, $start_time, $end_date, $end_time);
                    
                    if ($overlap_check['has_overlap']) {
                        wp_send_json_error(array(
                            'message' => $overlap_check['message']
                        ));
                        return;
                    }
                }
            }
            
            // Add RSVP
            $rsvps[] = array(
                'user_id' => $user_id,
                'time' => current_time('mysql'),
            );
            $message = __('RSVP confirmed!', 'videohub360-theme');
            $is_rsvpd = true;
        }

        // Update RSVPs
        update_post_meta($event_id, '_vh360_event_rsvps', $rsvps);
        update_post_meta($event_id, '_vh360_event_rsvp_count', count($rsvps));

        wp_send_json_success(array(
            'message' => $message,
            'is_rsvpd' => $is_rsvpd,
            'count' => count($rsvps),
        ));
    }

    /**
     * Get event RSVPs list.
     */
    public function get_event_rsvps() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view RSVPs', 'videohub360-theme')));
            return;
        }

        // Get event ID
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;

        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'videohub360-theme')));
            return;
        }

        $event = get_post($event_id);

        if (!$event || 'vh360_event' !== $event->post_type) {
            wp_send_json_error(array('message' => __('Event not found', 'videohub360-theme')));
            return;
        }

        // Check if user is event author
        if ((int) get_current_user_id() !== (int) $event->post_author && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to view RSVPs for this event', 'videohub360-theme')));
            return;
        }

        // Get RSVPs
        $rsvps = get_post_meta($event_id, '_vh360_event_rsvps', true);
        if (!is_array($rsvps) || empty($rsvps)) {
            wp_send_json_success(array(
                'html' => '<div class="vh360-rsvp-empty"><p>' . __('No RSVPs yet', 'videohub360-theme') . '</p></div>'
            ));
            return;
        }

        // Build HTML
        ob_start();
        ?>
        <div class="vh360-rsvp-list">
            <p class="vh360-rsvp-count"><?php echo sprintf(esc_html__('%d people have RSVP\'d', 'videohub360-theme'), count($rsvps)); ?></p>
            <div class="vh360-rsvp-items">
                <?php foreach ($rsvps as $rsvp) : 
                    $user = get_userdata($rsvp['user_id']);
                    if (!$user) continue;
                    ?>
                    <div class="vh360-rsvp-item">
                        <div class="vh360-rsvp-avatar">
                            <?php echo get_avatar($user->ID, 40); ?>
                        </div>
                        <div class="vh360-rsvp-info">
                            <div class="vh360-rsvp-name"><?php echo esc_html($user->display_name); ?></div>
                            <div class="vh360-rsvp-time"><?php echo sprintf(esc_html__('RSVP\'d %s', 'videohub360-theme'), human_time_diff(strtotime($rsvp['time']), current_time('timestamp')) . ' ' . __('ago', 'videohub360-theme')); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Upload event featured image.
     */
    public function upload_event_image() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vh360_event_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'videohub360-theme')));
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to upload images', 'videohub360-theme')));
            return;
        }

        // Check if file was uploaded
        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('No image file provided', 'videohub360-theme')));
            return;
        }

        // Check for upload errors
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Image upload failed', 'videohub360-theme')));
            return;
        }

        // Validate file type using WordPress function for security
        $file_path = $_FILES['image']['tmp_name'];
        $wp_filetype = wp_check_filetype_and_ext($file_path, $_FILES['image']['name']);
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!$wp_filetype['type'] || !in_array($wp_filetype['type'], $allowed_types, true)) {
            wp_send_json_error(array('message' => __('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed', 'videohub360-theme')));
            return;
        }
        
        // Additional validation: verify it's actually an image
        $image_info = getimagesize($file_path);
        if ($image_info === false) {
            wp_send_json_error(array('message' => __('File is not a valid image', 'videohub360-theme')));
            return;
        }

        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024;
        if ($_FILES['image']['size'] > $max_size) {
            wp_send_json_error(array('message' => __('File size too large. Maximum 5MB allowed', 'videohub360-theme')));
            return;
        }

        // Handle the upload
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('image', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
            return;
        }

        $attachment_url = wp_get_attachment_url($attachment_id);

        wp_send_json_success(array(
            'message'        => __('Image uploaded successfully', 'videohub360-theme'),
            'attachment_id'  => $attachment_id,
            'attachment_url' => $attachment_url,
        ));
    }
}

// Initialize the class
VH360_Event_Ajax::get_instance();
