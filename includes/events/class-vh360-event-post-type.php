<?php
/**
 * Event Post Type Registration
 *
 * Registers the vh360_event custom post type, category and tag taxonomies.
 *
 * @package Videohub360_Theme
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class VH360_Event_Post_Type
 *
 * Handles event post type and taxonomy registration.
 */
class VH360_Event_Post_Type {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'vh360_event';

    /**
     * Category taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY_CATEGORY = 'vh360_event_category';

    /**
     * Tag taxonomy slug.
     *
     * @var string
     */
    const TAXONOMY_TAG = 'vh360_event_tag';

    /**
     * Singleton instance.
     *
     * @var VH360_Event_Post_Type|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return VH360_Event_Post_Type
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
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_taxonomies'), 5);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_event_meta'), 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'render_admin_columns'), 10, 2);
        add_action('template_redirect', array($this, 'handle_ics_download'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_gallery_assets'));
    }

    /**
     * Register event post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Events', 'Post Type General Name', 'videohub360-theme'),
            'singular_name'         => _x('Event', 'Post Type Singular Name', 'videohub360-theme'),
            'menu_name'             => __('Events', 'videohub360-theme'),
            'name_admin_bar'        => __('Event', 'videohub360-theme'),
            'archives'              => __('Event Archives', 'videohub360-theme'),
            'attributes'            => __('Event Attributes', 'videohub360-theme'),
            'parent_item_colon'     => __('Parent Event:', 'videohub360-theme'),
            'all_items'             => __('All Events', 'videohub360-theme'),
            'add_new_item'          => __('Add New Event', 'videohub360-theme'),
            'add_new'               => __('Add New', 'videohub360-theme'),
            'new_item'              => __('New Event', 'videohub360-theme'),
            'edit_item'             => __('Edit Event', 'videohub360-theme'),
            'update_item'           => __('Update Event', 'videohub360-theme'),
            'view_item'             => __('View Event', 'videohub360-theme'),
            'view_items'            => __('View Events', 'videohub360-theme'),
            'search_items'          => __('Search Events', 'videohub360-theme'),
            'not_found'             => __('No events found', 'videohub360-theme'),
            'not_found_in_trash'    => __('No events found in Trash', 'videohub360-theme'),
            'featured_image'        => __('Event Image', 'videohub360-theme'),
            'set_featured_image'    => __('Set event image', 'videohub360-theme'),
            'remove_featured_image' => __('Remove event image', 'videohub360-theme'),
            'use_featured_image'    => __('Use as event image', 'videohub360-theme'),
            'insert_into_item'      => __('Insert into event', 'videohub360-theme'),
            'uploaded_to_this_item' => __('Uploaded to this event', 'videohub360-theme'),
            'items_list'            => __('Events list', 'videohub360-theme'),
            'items_list_navigation' => __('Events list navigation', 'videohub360-theme'),
            'filter_items_list'     => __('Filter events list', 'videohub360-theme'),
        );

        $args = array(
            'label'                 => __('Event', 'videohub360-theme'),
            'description'           => __('Community events', 'videohub360-theme'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 26,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
            'rewrite'               => array('slug' => 'events'),
            'show_in_rest'          => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register event taxonomies.
     */
    public function register_taxonomies() {
        // Register Category Taxonomy
        $category_labels = array(
            'name'                       => _x('Event Categories', 'Taxonomy General Name', 'videohub360-theme'),
            'singular_name'              => _x('Event Category', 'Taxonomy Singular Name', 'videohub360-theme'),
            'menu_name'                  => __('Categories', 'videohub360-theme'),
            'all_items'                  => __('All Categories', 'videohub360-theme'),
            'parent_item'                => __('Parent Category', 'videohub360-theme'),
            'parent_item_colon'          => __('Parent Category:', 'videohub360-theme'),
            'new_item_name'              => __('New Category Name', 'videohub360-theme'),
            'add_new_item'               => __('Add New Category', 'videohub360-theme'),
            'edit_item'                  => __('Edit Category', 'videohub360-theme'),
            'update_item'                => __('Update Category', 'videohub360-theme'),
            'view_item'                  => __('View Category', 'videohub360-theme'),
            'separate_items_with_commas' => __('Separate categories with commas', 'videohub360-theme'),
            'add_or_remove_items'        => __('Add or remove categories', 'videohub360-theme'),
            'choose_from_most_used'      => __('Choose from the most used', 'videohub360-theme'),
            'popular_items'              => __('Popular Categories', 'videohub360-theme'),
            'search_items'               => __('Search Categories', 'videohub360-theme'),
            'not_found'                  => __('Not Found', 'videohub360-theme'),
            'no_terms'                   => __('No categories', 'videohub360-theme'),
            'items_list'                 => __('Categories list', 'videohub360-theme'),
            'items_list_navigation'      => __('Categories list navigation', 'videohub360-theme'),
        );

        $category_args = array(
            'labels'                     => $category_labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array('slug' => 'event-category'),
            'show_in_rest'               => true,
        );

        register_taxonomy(self::TAXONOMY_CATEGORY, array(self::POST_TYPE), $category_args);

        // Register Tag Taxonomy
        $tag_labels = array(
            'name'                       => _x('Event Tags', 'Taxonomy General Name', 'videohub360-theme'),
            'singular_name'              => _x('Event Tag', 'Taxonomy Singular Name', 'videohub360-theme'),
            'menu_name'                  => __('Tags', 'videohub360-theme'),
            'all_items'                  => __('All Tags', 'videohub360-theme'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'new_item_name'              => __('New Tag Name', 'videohub360-theme'),
            'add_new_item'               => __('Add New Tag', 'videohub360-theme'),
            'edit_item'                  => __('Edit Tag', 'videohub360-theme'),
            'update_item'                => __('Update Tag', 'videohub360-theme'),
            'view_item'                  => __('View Tag', 'videohub360-theme'),
            'separate_items_with_commas' => __('Separate tags with commas', 'videohub360-theme'),
            'add_or_remove_items'        => __('Add or remove tags', 'videohub360-theme'),
            'choose_from_most_used'      => __('Choose from the most used', 'videohub360-theme'),
            'popular_items'              => __('Popular Tags', 'videohub360-theme'),
            'search_items'               => __('Search Tags', 'videohub360-theme'),
            'not_found'                  => __('Not Found', 'videohub360-theme'),
            'no_terms'                   => __('No tags', 'videohub360-theme'),
            'items_list'                 => __('Tags list', 'videohub360-theme'),
            'items_list_navigation'      => __('Tags list navigation', 'videohub360-theme'),
        );

        $tag_args = array(
            'labels'                     => $tag_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array('slug' => 'event-tag'),
            'show_in_rest'               => true,
        );

        register_taxonomy(self::TAXONOMY_TAG, array(self::POST_TYPE), $tag_args);
    }

    /**
     * Add meta boxes for event post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vh360_event_details',
            __('Event Details', 'videohub360-theme'),
            array($this, 'render_event_details_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'vh360_event_location',
            __('Event Location', 'videohub360-theme'),
            array($this, 'render_event_location_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'vh360_event_registration',
            __('Registration & Tickets', 'videohub360-theme'),
            array($this, 'render_event_registration_meta_box'),
            self::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'vh360_event_organizer',
            __('Organizer Information', 'videohub360-theme'),
            array($this, 'render_event_organizer_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'vh360_event_gallery',
            __('Event Gallery', 'videohub360-theme'),
            array($this, 'render_gallery_meta_box'),
            self::POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * Render event details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_event_details_meta_box($post) {
        wp_nonce_field('vh360_event_details', 'vh360_event_details_nonce');

        $start_date = get_post_meta($post->ID, '_vh360_event_start_date', true);
        $start_time = get_post_meta($post->ID, '_vh360_event_start_time', true);
        $end_date = get_post_meta($post->ID, '_vh360_event_end_date', true);
        $end_time = get_post_meta($post->ID, '_vh360_event_end_time', true);
        $status = get_post_meta($post->ID, '_vh360_event_status', true);
        if (empty($status)) {
            $status = 'scheduled';
        }

        ?>
        <table class="form-table">
            <tr>
                <th><label for="vh360_event_start_date"><?php esc_html_e('Start Date', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="date" id="vh360_event_start_date" name="vh360_event_start_date" 
                           value="<?php echo esc_attr($start_date); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_start_time"><?php esc_html_e('Start Time', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="time" id="vh360_event_start_time" name="vh360_event_start_time" 
                           value="<?php echo esc_attr($start_time); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_end_date"><?php esc_html_e('End Date', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="date" id="vh360_event_end_date" name="vh360_event_end_date" 
                           value="<?php echo esc_attr($end_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_end_time"><?php esc_html_e('End Time', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="time" id="vh360_event_end_time" name="vh360_event_end_time" 
                           value="<?php echo esc_attr($end_time); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_status"><?php esc_html_e('Event Status', 'videohub360-theme'); ?></label></th>
                <td>
                    <select id="vh360_event_status" name="vh360_event_status">
                        <option value="scheduled" <?php selected($status, 'scheduled'); ?>><?php esc_html_e('Scheduled', 'videohub360-theme'); ?></option>
                        <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'videohub360-theme'); ?></option>
                        <option value="postponed" <?php selected($status, 'postponed'); ?>><?php esc_html_e('Postponed', 'videohub360-theme'); ?></option>
                        <option value="completed" <?php selected($status, 'completed'); ?>><?php esc_html_e('Completed', 'videohub360-theme'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render event location meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_event_location_meta_box($post) {
        $location_type = get_post_meta($post->ID, '_vh360_event_location_type', true);
        if (empty($location_type)) {
            $location_type = 'physical';
        }
        $venue_name = get_post_meta($post->ID, '_vh360_event_venue_name', true);
        $venue_address = get_post_meta($post->ID, '_vh360_event_venue_address', true);
        $venue_city = get_post_meta($post->ID, '_vh360_event_venue_city', true);
        $venue_state = get_post_meta($post->ID, '_vh360_event_venue_state', true);
        $venue_zip = get_post_meta($post->ID, '_vh360_event_venue_zip', true);
        $venue_country = get_post_meta($post->ID, '_vh360_event_venue_country', true);
        $online_url = get_post_meta($post->ID, '_vh360_event_online_url', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e('Location Type', 'videohub360-theme'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="vh360_event_location_type" value="physical" 
                               <?php checked($location_type, 'physical'); ?> 
                               class="vh360-location-type-toggle">
                        <?php esc_html_e('Physical Location', 'videohub360-theme'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="vh360_event_location_type" value="online" 
                               <?php checked($location_type, 'online'); ?> 
                               class="vh360-location-type-toggle">
                        <?php esc_html_e('Online Event', 'videohub360-theme'); ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="vh360_event_location_type" value="both" 
                               <?php checked($location_type, 'both'); ?> 
                               class="vh360-location-type-toggle">
                        <?php esc_html_e('Both (Hybrid)', 'videohub360-theme'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <div id="vh360-physical-location-fields" style="<?php echo ($location_type === 'online') ? 'display:none;' : ''; ?>">
            <h4><?php esc_html_e('Venue Details', 'videohub360-theme'); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label for="vh360_event_venue_name"><?php esc_html_e('Venue Name', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_name" name="vh360_event_venue_name" 
                               value="<?php echo esc_attr($venue_name); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vh360_event_venue_address"><?php esc_html_e('Address', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_address" name="vh360_event_venue_address" 
                               value="<?php echo esc_attr($venue_address); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vh360_event_venue_city"><?php esc_html_e('City', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_city" name="vh360_event_venue_city" 
                               value="<?php echo esc_attr($venue_city); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vh360_event_venue_state"><?php esc_html_e('State/Province', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_state" name="vh360_event_venue_state" 
                               value="<?php echo esc_attr($venue_state); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vh360_event_venue_zip"><?php esc_html_e('Zip/Postal Code', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_zip" name="vh360_event_venue_zip" 
                               value="<?php echo esc_attr($venue_zip); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="vh360_event_venue_country"><?php esc_html_e('Country', 'videohub360-theme'); ?></label></th>
                    <td><input type="text" id="vh360_event_venue_country" name="vh360_event_venue_country" 
                               value="<?php echo esc_attr($venue_country); ?>" class="regular-text"></td>
                </tr>
            </table>
        </div>

        <div id="vh360-online-location-fields" style="<?php echo ($location_type === 'physical') ? 'display:none;' : ''; ?>">
            <h4><?php esc_html_e('Online Meeting Details', 'videohub360-theme'); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label for="vh360_event_online_url"><?php esc_html_e('Meeting URL', 'videohub360-theme'); ?></label></th>
                    <td>
                        <input type="url" id="vh360_event_online_url" name="vh360_event_online_url" 
                               value="<?php echo esc_attr($online_url); ?>" class="regular-text" 
                               placeholder="https://zoom.us/...">
                        <p class="description"><?php esc_html_e('Zoom, Google Meet, or other online meeting link', 'videohub360-theme'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.vh360-location-type-toggle').on('change', function() {
                var locationType = $('input[name="vh360_event_location_type"]:checked').val();
                if (locationType === 'online') {
                    $('#vh360-physical-location-fields').hide();
                    $('#vh360-online-location-fields').show();
                } else if (locationType === 'physical') {
                    $('#vh360-physical-location-fields').show();
                    $('#vh360-online-location-fields').hide();
                } else {
                    $('#vh360-physical-location-fields').show();
                    $('#vh360-online-location-fields').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render event registration meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_event_registration_meta_box($post) {
        $registration_required = get_post_meta($post->ID, '_vh360_event_registration_required', true);
        $registration_deadline = get_post_meta($post->ID, '_vh360_event_registration_deadline', true);
        $max_attendees = get_post_meta($post->ID, '_vh360_event_max_attendees', true);
        $cost_type = get_post_meta($post->ID, '_vh360_event_cost_type', true);
        if (empty($cost_type)) {
            $cost_type = 'free';
        }
        $cost_amount = get_post_meta($post->ID, '_vh360_event_cost_amount', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="vh360_event_registration_required"><?php esc_html_e('Registration Required', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="checkbox" id="vh360_event_registration_required" name="vh360_event_registration_required" 
                           value="1" <?php checked($registration_required, '1'); ?>>
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_registration_deadline"><?php esc_html_e('Registration Deadline', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="date" id="vh360_event_registration_deadline" name="vh360_event_registration_deadline" 
                           value="<?php echo esc_attr($registration_deadline); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="vh360_event_max_attendees"><?php esc_html_e('Maximum Attendees', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="number" id="vh360_event_max_attendees" name="vh360_event_max_attendees" 
                           value="<?php echo esc_attr($max_attendees); ?>" class="small-text" min="0">
                    <p class="description"><?php esc_html_e('Leave empty for unlimited', 'videohub360-theme'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Cost', 'videohub360-theme'); ?></label></th>
                <td>
                    <select id="vh360_event_cost_type" name="vh360_event_cost_type">
                        <option value="free" <?php selected($cost_type, 'free'); ?>><?php esc_html_e('Free', 'videohub360-theme'); ?></option>
                        <option value="paid" <?php selected($cost_type, 'paid'); ?>><?php esc_html_e('Paid', 'videohub360-theme'); ?></option>
                        <option value="donation" <?php selected($cost_type, 'donation'); ?>><?php esc_html_e('Donation', 'videohub360-theme'); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="vh360-cost-amount-row" style="<?php echo ($cost_type === 'free') ? 'display:none;' : ''; ?>">
                <th><label for="vh360_event_cost_amount"><?php esc_html_e('Amount', 'videohub360-theme'); ?></label></th>
                <td>
                    <input type="number" id="vh360_event_cost_amount" name="vh360_event_cost_amount" 
                           value="<?php echo esc_attr($cost_amount); ?>" class="small-text" min="0" step="0.01">
                    <p class="description"><?php esc_html_e('Suggested amount for donations', 'videohub360-theme'); ?></p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('#vh360_event_cost_type').on('change', function() {
                if ($(this).val() === 'free') {
                    $('#vh360-cost-amount-row').hide();
                } else {
                    $('#vh360-cost-amount-row').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render event organizer meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_event_organizer_meta_box($post) {
        $organizer_name = get_post_meta($post->ID, '_vh360_event_organizer_name', true);
        $organizer_email = get_post_meta($post->ID, '_vh360_event_organizer_email', true);
        $organizer_phone = get_post_meta($post->ID, '_vh360_event_organizer_phone', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="vh360_event_organizer_name"><?php esc_html_e('Name', 'videohub360-theme'); ?></label></th>
                <td><input type="text" id="vh360_event_organizer_name" name="vh360_event_organizer_name" 
                           value="<?php echo esc_attr($organizer_name); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="vh360_event_organizer_email"><?php esc_html_e('Email', 'videohub360-theme'); ?></label></th>
                <td><input type="email" id="vh360_event_organizer_email" name="vh360_event_organizer_email" 
                           value="<?php echo esc_attr($organizer_email); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="vh360_event_organizer_phone"><?php esc_html_e('Phone', 'videohub360-theme'); ?></label></th>
                <td><input type="tel" id="vh360_event_organizer_phone" name="vh360_event_organizer_phone" 
                           value="<?php echo esc_attr($organizer_phone); ?>" class="widefat"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save event meta data.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_event_meta($post_id, $post) {
        // Check if nonce is set
        if (!isset($_POST['vh360_event_details_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['vh360_event_details_nonce'], 'vh360_event_details')) {
            return;
        }

        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save event details
        $fields = array(
            '_vh360_event_start_date'           => 'sanitize_text_field',
            '_vh360_event_start_time'           => 'sanitize_text_field',
            '_vh360_event_end_date'             => 'sanitize_text_field',
            '_vh360_event_end_time'             => 'sanitize_text_field',
            '_vh360_event_status'               => 'sanitize_text_field',
            '_vh360_event_location_type'        => 'sanitize_text_field',
            '_vh360_event_venue_name'           => 'sanitize_text_field',
            '_vh360_event_venue_address'        => 'sanitize_text_field',
            '_vh360_event_venue_city'           => 'sanitize_text_field',
            '_vh360_event_venue_state'          => 'sanitize_text_field',
            '_vh360_event_venue_zip'            => 'sanitize_text_field',
            '_vh360_event_venue_country'        => 'sanitize_text_field',
            '_vh360_event_online_url'           => 'esc_url_raw',
            '_vh360_event_registration_deadline'=> 'sanitize_text_field',
            '_vh360_event_max_attendees'        => 'absint',
            '_vh360_event_cost_type'            => 'sanitize_text_field',
            '_vh360_event_cost_amount'          => 'sanitize_text_field',
            '_vh360_event_organizer_name'       => 'sanitize_text_field',
            '_vh360_event_organizer_email'      => 'sanitize_email',
            '_vh360_event_organizer_phone'      => 'sanitize_text_field',
        );

        foreach ($fields as $field => $sanitize_callback) {
            if (isset($_POST[substr($field, 1)])) {
                $value = call_user_func($sanitize_callback, $_POST[substr($field, 1)]);
                update_post_meta($post_id, $field, $value);
            }
        }

        // Handle checkbox
        $registration_required = isset($_POST['vh360_event_registration_required']) ? '1' : '0';
        update_post_meta($post_id, '_vh360_event_registration_required', $registration_required);

        // Save gallery image IDs
        if (isset($_POST['vh360_event_gallery_image_ids']) && isset($_POST['vh360_event_gallery_nonce'])
            && wp_verify_nonce($_POST['vh360_event_gallery_nonce'], 'vh360_event_gallery')) {
            $gallery_ids = vh360_sanitize_event_gallery_image_ids(
                wp_unslash($_POST['vh360_event_gallery_image_ids'])
            );

            if (!empty($gallery_ids)) {
                update_post_meta($post_id, '_vh360_event_gallery_image_ids', $gallery_ids);
            } else {
                delete_post_meta($post_id, '_vh360_event_gallery_image_ids');
            }
        }
    }

    /**
     * Render Event Gallery meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_gallery_meta_box($post) {
        wp_nonce_field('vh360_event_gallery', 'vh360_event_gallery_nonce');

        $gallery_ids = get_post_meta($post->ID, '_vh360_event_gallery_image_ids', true);

        if (!is_array($gallery_ids)) {
            $gallery_ids = array();
        }

        $ids_string = implode(',', array_map('absint', $gallery_ids));
        ?>
        <p class="description">
            <?php esc_html_e('Add up to 5 additional images for this event. These images appear near the event description on the single event page.', 'videohub360-theme'); ?>
        </p>

        <div id="vh360-event-gallery-admin-wrap" style="margin-top: 12px;">
            <input type="hidden" id="vh360_event_gallery_image_ids" name="vh360_event_gallery_image_ids"
                   value="<?php echo esc_attr($ids_string); ?>">

            <div id="vh360-event-gallery-admin-preview" class="vh360-event-gallery-admin-preview">
                <?php foreach ($gallery_ids as $image_id) :
                    $image_id  = absint($image_id);
                    $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                    $full_url  = wp_get_attachment_image_url($image_id, 'full');
                    $alt       = get_post_meta($image_id, '_wp_attachment_image_alt', true);

                    if (!$thumb_url) {
                        continue;
                    }
                    ?>
                    <div class="vh360-event-gallery-admin-item" data-id="<?php echo esc_attr($image_id); ?>">
                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($alt); ?>">
                        <button type="button" class="vh360-event-gallery-admin-remove"
                                aria-label="<?php esc_attr_e('Remove image', 'videohub360-theme'); ?>">
                            &times;
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" id="vh360-event-gallery-admin-add" class="button">
                    <?php esc_html_e('Add Gallery Images', 'videohub360-theme'); ?>
                </button>
                <span id="vh360-event-gallery-admin-count" style="margin-left: 8px; color: #666;">
                    <?php
                    /* translators: %d: number of images */
                    printf(esc_html__('%d / 5 images selected', 'videohub360-theme'), count($gallery_ids));
                    ?>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts and styles for the event gallery meta box.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public function enqueue_admin_gallery_assets($hook_suffix) {
        global $post;

        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        if (!$post || self::POST_TYPE !== $post->post_type) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'vh360-event-gallery-admin',
            get_template_directory_uri() . '/assets/css/admin/event-gallery-admin.css',
            array(),
            vh360_theme_asset_version('assets/css/admin/event-gallery-admin.css')
        );

        wp_enqueue_script(
            'vh360-event-gallery-admin',
            get_template_directory_uri() . '/assets/js/admin/event-gallery-admin.js',
            array('jquery', 'media-upload', 'media-views'),
            vh360_theme_asset_version('assets/js/admin/event-gallery-admin.js'),
            true
        );

        wp_localize_script(
            'vh360-event-gallery-admin',
            'vh360EventGalleryAdmin',
            array(
                'maxImages' => 5,
                'i18n'      => array(
                    'selectImages' => __('Select Event Gallery Images', 'videohub360-theme'),
                    'useImages'    => __('Use Selected Images', 'videohub360-theme'),
                    'maxImages'    => __('You can add up to 5 gallery images.', 'videohub360-theme'),
                    'removeImage'  => __('Remove image', 'videohub360-theme'),
                ),
            )
        );
    }

    /**
     * Add custom admin columns.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['event_date'] = __('Event Date', 'videohub360-theme');
                $new_columns['event_location'] = __('Location', 'videohub360-theme');
                $new_columns['event_status'] = __('Status', 'videohub360-theme');
            }
        }
        
        return $new_columns;
    }

    /**
     * Render custom admin columns.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_admin_columns($column, $post_id) {
        switch ($column) {
            case 'event_date':
                $start_date = get_post_meta($post_id, '_vh360_event_start_date', true);
                $start_time = get_post_meta($post_id, '_vh360_event_start_time', true);
                if ($start_date) {
                    echo esc_html(wp_date(get_option('date_format'), strtotime($start_date)));
                    if ($start_time) {
                        echo '<br>' . esc_html($start_time);
                    }
                } else {
                    echo '—';
                }
                break;

            case 'event_location':
                $location_type = get_post_meta($post_id, '_vh360_event_location_type', true);
                if ($location_type === 'online') {
                    echo '🌐 ' . esc_html__('Online', 'videohub360-theme');
                } elseif ($location_type === 'both') {
                    echo '🌐📍 ' . esc_html__('Hybrid', 'videohub360-theme');
                } else {
                    $venue_name = get_post_meta($post_id, '_vh360_event_venue_name', true);
                    if ($venue_name) {
                        echo '📍 ' . esc_html($venue_name);
                    } else {
                        echo '📍 ' . esc_html__('Physical', 'videohub360-theme');
                    }
                }
                break;

            case 'event_status':
                $status = get_post_meta($post_id, '_vh360_event_status', true);
                $status_labels = array(
                    'scheduled' => __('Scheduled', 'videohub360-theme'),
                    'cancelled' => __('Cancelled', 'videohub360-theme'),
                    'postponed' => __('Postponed', 'videohub360-theme'),
                    'completed' => __('Completed', 'videohub360-theme'),
                );
                $status_colors = array(
                    'scheduled' => '#10b981',
                    'cancelled' => '#ef4444',
                    'postponed' => '#f59e0b',
                    'completed' => '#6b7280',
                );
                if ($status && isset($status_labels[$status])) {
                    echo '<span style="color: ' . esc_attr($status_colors[$status]) . '">● ' . esc_html($status_labels[$status]) . '</span>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Handle ICS file download requests.
     */
    public function handle_ics_download() {
        // Get the request URI (sanitized)
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        // Check if URL matches the events download-ics pattern
        // Must contain both /events and download-ics in the path
        if (strpos($request_uri, '/events') === false || strpos($request_uri, 'download-ics') === false) {
            return;
        }

        // Additional validation: ensure event_id parameter exists
        if (!isset($_GET['event_id'])) {
            return;
        }

        // Get event ID from query parameter
        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;

        if (!$event_id) {
            wp_die(__('Invalid event ID', 'videohub360-theme'));
        }

        // Verify the post exists and is an event
        $event = get_post($event_id);
        if (!$event || $event->post_type !== self::POST_TYPE) {
            wp_die(__('Event not found', 'videohub360-theme'));
        }

        // Generate ICS content
        $ics_content = vh360_generate_event_ics($event_id);

        if (empty($ics_content)) {
            wp_die(__('Unable to generate calendar file', 'videohub360-theme'));
        }

        // Clean all output buffers to prevent contamination
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Ensure we're working with UTF-8 encoded content
        $ics_content = mb_convert_encoding($ics_content, 'UTF-8', 'UTF-8');
        
        // Calculate proper content length for multibyte strings
        $content_length = strlen($ics_content);

        // Set status code explicitly for stricter browsers
        status_header(200);
        
        // Set headers for file download
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="event-' . absint($event_id) . '.ics"');
        header('Content-Length: ' . $content_length);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Prevent any additional output
        header('Connection: close');

        // Output ICS content (ICS format is controlled and generated internally, safe to output)
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $ics_content;
        
        // Flush output and exit cleanly
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
}

// Initialize the class
VH360_Event_Post_Type::get_instance();
