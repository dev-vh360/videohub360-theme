<?php
/**
 * VideoHub360 Course Foundation
 *
 * Adds native course/lesson data structures on top of the existing
 * videohub360 post type and videohub360_series taxonomy without creating
 * new CPTs or breaking existing video behaviour.
 *
 * Activated by the "Enable Course / Lesson Features" toggle in VideoHub360
 * Settings (option: videohub360_enable_course_features).
 *
 * @package VideoHub360_Core
 * @since   2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class VideoHub360_Course_Foundation {

    /**
     * Constructor – register all hooks.
     */
    public function __construct() {
        // Core meta registration runs always so that REST / importer can
        // read/write course & lesson meta even before the admin toggle is on.
        add_action( 'init', array( $this, 'register_meta' ), 20 );

        // Admin UI hooks – only when course features are enabled.
        add_action( 'admin_init', array( $this, 'maybe_register_admin_hooks' ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Feature flag                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Check whether course features are enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option( 'videohub360_enable_course_features', 0 );
    }

    /* ------------------------------------------------------------------ */
    /*  Admin hooks (conditional)                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Register admin-only hooks when the feature toggle is on.
     */
    public function maybe_register_admin_hooks() {
        if ( ! self::is_enabled() ) {
            return;
        }

        // Lesson meta box on videohub360 posts.
        add_action( 'add_meta_boxes',          array( $this, 'add_lesson_meta_box' ) );
        add_action( 'save_post_videohub360',   array( $this, 'save_lesson_meta_box' ) );

        // Course term meta fields on videohub360_series.
        add_action( 'videohub360_series_add_form_fields',  array( $this, 'add_series_term_fields' ) );
        add_action( 'videohub360_series_edit_form_fields', array( $this, 'edit_series_term_fields' ) );
        add_action( 'created_videohub360_series',          array( $this, 'save_series_term_meta' ) );
        add_action( 'edited_videohub360_series',           array( $this, 'save_series_term_meta' ) );

        // Admin notice on the series list when course features are active.
        add_action( 'videohub360_series_pre_add_form', array( $this, 'series_admin_notice' ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Meta registration (REST + importer compatibility)                   */
    /* ------------------------------------------------------------------ */

    /**
     * Register post meta and term meta so REST API and importers can
     * read/write them correctly.
     */
    public function register_meta() {
        // ---- Lesson post meta ---------------------------------------- //
        $lesson_int_fields = array(
            '_vh360_lesson_module_number',
            '_vh360_lesson_number',
        );
        foreach ( $lesson_int_fields as $key ) {
            register_post_meta( 'videohub360', $key, array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( $this, 'meta_auth_callback' ),
            ) );
        }

        $lesson_string_fields = array(
            '_vh360_lesson_module_title',
            '_vh360_lesson_duration',
            '_vh360_lesson_resource_label',
        );
        foreach ( $lesson_string_fields as $key ) {
            register_post_meta( 'videohub360', $key, array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => array( $this, 'meta_auth_callback' ),
            ) );
        }

        register_post_meta( 'videohub360', '_vh360_lesson_resource_url', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array( $this, 'meta_auth_callback' ),
        ) );

        register_post_meta( 'videohub360', '_vh360_lesson_is_preview', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
            'auth_callback'     => array( $this, 'meta_auth_callback' ),
        ) );

        // ---- Course term meta ----------------------------------------- //
        $course_string_fields = array(
            '_vh360_course_subtitle',
            '_vh360_course_level',
            '_vh360_course_duration',
            '_vh360_course_cta_text',
        );
        foreach ( $course_string_fields as $key ) {
            register_term_meta( 'videohub360_series', $key, array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => array( $this, 'term_meta_auth_callback' ),
            ) );
        }

        register_term_meta( 'videohub360_series', '_vh360_course_short_description', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_textarea_field',
            'auth_callback'     => array( $this, 'term_meta_auth_callback' ),
        ) );

        register_term_meta( 'videohub360_series', '_vh360_course_cta_url', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'esc_url_raw',
            'auth_callback'     => array( $this, 'term_meta_auth_callback' ),
        ) );

        register_term_meta( 'videohub360_series', '_vh360_course_required_membership', array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => array( $this, 'sanitize_membership_key' ),
            'auth_callback'     => array( $this, 'term_meta_auth_callback' ),
        ) );

        $course_int_fields = array(
            '_vh360_course_instructor_user_id',
            '_vh360_course_featured_image_id',
            '_vh360_course_order',
        );
        foreach ( $course_int_fields as $key ) {
            register_term_meta( 'videohub360_series', $key, array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( $this, 'term_meta_auth_callback' ),
            ) );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Lesson meta box                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Register the "Lesson Details" meta box.
     */
    public function add_lesson_meta_box() {
        add_meta_box(
            'vh360_lesson_details',
            __( 'Lesson Details', 'videohub360' ),
            array( $this, 'render_lesson_meta_box' ),
            'videohub360',
            'normal',
            'default'
        );
    }

    /**
     * Render the Lesson Details meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_lesson_meta_box( $post ) {
        wp_nonce_field( 'vh360_lesson_meta_box', 'vh360_lesson_meta_nonce' );

        $module_title    = get_post_meta( $post->ID, '_vh360_lesson_module_title',  true );
        $module_number   = get_post_meta( $post->ID, '_vh360_lesson_module_number', true );
        $lesson_number   = get_post_meta( $post->ID, '_vh360_lesson_number',        true );
        $duration        = get_post_meta( $post->ID, '_vh360_lesson_duration',      true );
        $resource_url    = get_post_meta( $post->ID, '_vh360_lesson_resource_url',  true );
        $resource_label  = get_post_meta( $post->ID, '_vh360_lesson_resource_label', true );
        $is_preview      = get_post_meta( $post->ID, '_vh360_lesson_is_preview',    true );
        ?>
        <style>
            .vh360-lesson-meta-table th { width: 180px; padding: 8px 10px; vertical-align: middle; }
            .vh360-lesson-meta-table td { padding: 8px 10px; }
        </style>
        <table class="form-table vh360-lesson-meta-table">
            <tr>
                <th scope="row"><label for="vh360_lesson_module_title"><?php esc_html_e( 'Module Title', 'videohub360' ); ?></label></th>
                <td>
                    <input type="text" id="vh360_lesson_module_title" name="_vh360_lesson_module_title"
                           value="<?php echo esc_attr( $module_title ); ?>" style="width:300px;" />
                    <p class="description"><?php esc_html_e( 'e.g. Foundations', 'videohub360' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vh360_lesson_module_number"><?php esc_html_e( 'Module Number', 'videohub360' ); ?></label></th>
                <td>
                    <input type="number" id="vh360_lesson_module_number" name="_vh360_lesson_module_number"
                           value="<?php echo esc_attr( $module_number ); ?>" min="0" style="width:80px;" />
                    <p class="description"><?php esc_html_e( 'Used for grouping and sorting lessons.', 'videohub360' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vh360_lesson_number"><?php esc_html_e( 'Lesson Number', 'videohub360' ); ?></label></th>
                <td>
                    <input type="number" id="vh360_lesson_number" name="_vh360_lesson_number"
                           value="<?php echo esc_attr( $lesson_number ); ?>" min="0" style="width:80px;" />
                    <p class="description"><?php esc_html_e( 'Order within the module.', 'videohub360' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vh360_lesson_duration"><?php esc_html_e( 'Duration', 'videohub360' ); ?></label></th>
                <td>
                    <input type="text" id="vh360_lesson_duration" name="_vh360_lesson_duration"
                           value="<?php echo esc_attr( $duration ); ?>" style="width:150px;" placeholder="<?php esc_attr_e( '12 min', 'videohub360' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vh360_lesson_resource_url"><?php esc_html_e( 'Resource URL', 'videohub360' ); ?></label></th>
                <td>
                    <input type="url" id="vh360_lesson_resource_url" name="_vh360_lesson_resource_url"
                           value="<?php echo esc_attr( $resource_url ); ?>" style="width:400px;" />
                    <p class="description"><?php esc_html_e( 'Optional worksheet or download link.', 'videohub360' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vh360_lesson_resource_label"><?php esc_html_e( 'Resource Label', 'videohub360' ); ?></label></th>
                <td>
                    <input type="text" id="vh360_lesson_resource_label" name="_vh360_lesson_resource_label"
                           value="<?php echo esc_attr( $resource_label ); ?>" style="width:300px;" placeholder="<?php esc_attr_e( 'Download Worksheet', 'videohub360' ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Free Preview', 'videohub360' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_vh360_lesson_is_preview" value="yes"
                               <?php checked( $is_preview, 'yes' ); ?> />
                        <?php esc_html_e( 'Mark this lesson as a free preview', 'videohub360' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save lesson meta box data.
     *
     * @param int $post_id Post ID.
     */
    public function save_lesson_meta_box( $post_id ) {
        if ( ! isset( $_POST['vh360_lesson_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['vh360_lesson_meta_nonce'], 'vh360_lesson_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        update_post_meta( $post_id, '_vh360_lesson_module_title',
            sanitize_text_field( $_POST['_vh360_lesson_module_title'] ?? '' ) );
        update_post_meta( $post_id, '_vh360_lesson_module_number',
            absint( $_POST['_vh360_lesson_module_number'] ?? 0 ) );
        update_post_meta( $post_id, '_vh360_lesson_number',
            absint( $_POST['_vh360_lesson_number'] ?? 0 ) );
        update_post_meta( $post_id, '_vh360_lesson_duration',
            sanitize_text_field( $_POST['_vh360_lesson_duration'] ?? '' ) );
        update_post_meta( $post_id, '_vh360_lesson_resource_url',
            esc_url_raw( $_POST['_vh360_lesson_resource_url'] ?? '' ) );
        update_post_meta( $post_id, '_vh360_lesson_resource_label',
            sanitize_text_field( $_POST['_vh360_lesson_resource_label'] ?? '' ) );
        update_post_meta( $post_id, '_vh360_lesson_is_preview',
            isset( $_POST['_vh360_lesson_is_preview'] ) ? 'yes' : 'no' );
    }

    /* ------------------------------------------------------------------ */
    /*  Course term meta – add form (new term)                              */
    /* ------------------------------------------------------------------ */

    /**
     * Render course fields on the Add New Series form.
     */
    public function add_series_term_fields() {
        ?>
        <div class="form-field">
            <h3><?php esc_html_e( 'Course / Learning Track Details', 'videohub360' ); ?></h3>
            <p style="color:#666;"><?php esc_html_e( 'When Course / Lesson Features are enabled, this series can be used as a Course or Learning Track.', 'videohub360' ); ?></p>
        </div>
        <?php foreach ( $this->get_course_term_fields() as $field ) : ?>
        <div class="form-field">
            <label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
            <?php $this->render_term_field( $field, '' ); ?>
            <?php if ( ! empty( $field['desc'] ) ) : ?>
                <p><?php echo esc_html( $field['desc'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach;
    }

    /**
     * Render course fields on the Edit Series form.
     *
     * @param WP_Term $term Current term.
     */
    public function edit_series_term_fields( $term ) {
        ?>
        <tr class="form-field">
            <th scope="row" colspan="2">
                <h3 style="margin:20px 0 5px 0;"><?php esc_html_e( 'Course / Learning Track Details', 'videohub360' ); ?></h3>
                <p style="color:#666;font-weight:normal;"><?php esc_html_e( 'When Course / Lesson Features are enabled, this series can be used as a Course or Learning Track.', 'videohub360' ); ?></p>
            </th>
        </tr>
        <?php foreach ( $this->get_course_term_fields() as $field ) :
            $value = get_term_meta( $term->term_id, $field['key'], true );
            ?>
        <tr class="form-field">
            <th scope="row"><label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
            <td>
                <?php $this->render_term_field( $field, $value ); ?>
                <?php if ( ! empty( $field['desc'] ) ) : ?>
                    <p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    }

    /**
     * Save course term meta.
     *
     * @param int $term_id Term ID.
     */
    public function save_series_term_meta( $term_id ) {
        if ( ! isset( $_POST['vh360_course_term_nonce'] ) ||
             ! wp_verify_nonce( $_POST['vh360_course_term_nonce'], 'vh360_course_term_meta' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        foreach ( $this->get_course_term_fields() as $field ) {
            $raw = $_POST[ $field['key'] ] ?? '';

            switch ( $field['sanitize'] ) {
                case 'absint':
                    $value = absint( $raw );
                    break;
                case 'url':
                    $value = esc_url_raw( $raw );
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field( $raw );
                    break;
                case 'membership':
                    $value = $this->sanitize_membership_key( $raw );
                    break;
                default:
                    $value = sanitize_text_field( $raw );
            }

            update_term_meta( $term_id, $field['key'], $value );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin notice on series screen                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Display a contextual notice above the Add Series form.
     */
    public function series_admin_notice() {
        echo '<div class="notice notice-info inline"><p>';
        echo wp_kses_post( sprintf(
            /* translators: %s = admin settings URL */
            __( '<strong>Course / Lesson Mode is active.</strong> Use series as Courses or Learning Tracks. You can adjust this in <a href="%s">VideoHub360 Settings</a>.', 'videohub360' ),
            esc_url( admin_url( 'edit.php?post_type=videohub360&page=videohub360-settings' ) )
        ) );
        echo '</p></div>';
    }

    /* ------------------------------------------------------------------ */
    /*  Helper: course term field definitions                               */
    /* ------------------------------------------------------------------ */

    /**
     * Return the list of course term meta field definitions.
     *
     * @return array[]
     */
    private function get_course_term_fields() {
        return array(
            array(
                'key'      => '_vh360_course_subtitle',
                'id'       => 'vh360_course_subtitle',
                'label'    => __( 'Course Subtitle', 'videohub360' ),
                'type'     => 'text',
                'sanitize' => 'text',
                'desc'     => __( 'Short subtitle for course cards and landing pages.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_short_description',
                'id'       => 'vh360_course_short_description',
                'label'    => __( 'Short Description', 'videohub360' ),
                'type'     => 'textarea',
                'sanitize' => 'textarea',
                'desc'     => __( 'Used in course catalog cards and headers.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_level',
                'id'       => 'vh360_course_level',
                'label'    => __( 'Course Level', 'videohub360' ),
                'type'     => 'select',
                'options'  => array(
                    ''             => __( '— Select —', 'videohub360' ),
                    'beginner'     => __( 'Beginner', 'videohub360' ),
                    'intermediate' => __( 'Intermediate', 'videohub360' ),
                    'advanced'     => __( 'Advanced', 'videohub360' ),
                    'all'          => __( 'All Levels', 'videohub360' ),
                ),
                'sanitize' => 'text',
                'desc'     => '',
            ),
            array(
                'key'      => '_vh360_course_duration',
                'id'       => 'vh360_course_duration',
                'label'    => __( 'Course Duration', 'videohub360' ),
                'type'     => 'text',
                'sanitize' => 'text',
                'desc'     => __( 'e.g. 6 modules / 18 lessons', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_instructor_user_id',
                'id'       => 'vh360_course_instructor_user_id',
                'label'    => __( 'Instructor User ID', 'videohub360' ),
                'type'     => 'number',
                'sanitize' => 'absint',
                'desc'     => __( 'Optional. If empty, the author of the first lesson is used.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_featured_image_id',
                'id'       => 'vh360_course_featured_image_id',
                'label'    => __( 'Featured Image ID', 'videohub360' ),
                'type'     => 'number',
                'sanitize' => 'absint',
                'desc'     => __( 'Attachment ID for the course card/catalog image.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_required_membership',
                'id'       => 'vh360_course_required_membership',
                'label'    => __( 'Required Membership', 'videohub360' ),
                'type'     => 'text',
                'sanitize' => 'membership',
                'desc'     => __( 'Membership plan key required to access this course. Use "any" for any active plan, or leave empty for public access.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_cta_text',
                'id'       => 'vh360_course_cta_text',
                'label'    => __( 'CTA Button Text', 'videohub360' ),
                'type'     => 'text',
                'sanitize' => 'text',
                'desc'     => __( 'e.g. Start Learning, View Course', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_cta_url',
                'id'       => 'vh360_course_cta_url',
                'label'    => __( 'CTA URL', 'videohub360' ),
                'type'     => 'url',
                'sanitize' => 'url',
                'desc'     => __( 'Optional custom CTA URL. Leave empty to use the term link.', 'videohub360' ),
            ),
            array(
                'key'      => '_vh360_course_order',
                'id'       => 'vh360_course_order',
                'label'    => __( 'Course Order', 'videohub360' ),
                'type'     => 'number',
                'sanitize' => 'absint',
                'desc'     => __( 'Manual ordering for course catalog output.', 'videohub360' ),
            ),
        );
    }

    /**
     * Render a single term meta field input.
     *
     * @param array  $field  Field definition.
     * @param string $value  Current saved value.
     */
    private function render_term_field( $field, $value ) {
        // Nonce emitted once at the start of each render pass.
        static $nonce_emitted = false;
        if ( ! $nonce_emitted ) {
            wp_nonce_field( 'vh360_course_term_meta', 'vh360_course_term_nonce' );
            $nonce_emitted = true;
        }

        $id   = esc_attr( $field['id'] );
        $name = esc_attr( $field['key'] );
        $val  = esc_attr( $value );

        switch ( $field['type'] ) {
            case 'textarea':
                echo '<textarea id="' . $id . '" name="' . $name . '" rows="4" style="width:100%;max-width:500px;">'
                     . esc_textarea( $value ) . '</textarea>';
                break;

            case 'select':
                echo '<select id="' . $id . '" name="' . $name . '" style="width:200px;">';
                foreach ( $field['options'] as $opt_val => $opt_label ) {
                    echo '<option value="' . esc_attr( $opt_val ) . '"'
                         . selected( $value, $opt_val, false ) . '>'
                         . esc_html( $opt_label ) . '</option>';
                }
                echo '</select>';
                break;

            case 'number':
                echo '<input type="number" id="' . $id . '" name="' . $name . '" value="' . $val . '" min="0" style="width:100px;" />';
                break;

            case 'url':
                echo '<input type="url" id="' . $id . '" name="' . $name . '" value="' . $val . '" style="width:100%;max-width:500px;" />';
                break;

            default: // text
                echo '<input type="text" id="' . $id . '" name="' . $name . '" value="' . $val . '" style="width:100%;max-width:400px;" />';
                break;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Sanitisation callbacks                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Sanitise a membership plan key.
     * Accepts "any" or a regular sanitize_key() value.
     *
     * @param  string $value Raw value.
     * @return string
     */
    public function sanitize_membership_key( $value ) {
        if ( 'any' === $value ) {
            return 'any';
        }
        return sanitize_key( $value );
    }

    /**
     * Sanitise a yes/no meta value.
     *
     * @param  string $value Raw value.
     * @return string 'yes' or 'no'.
     */
    public function sanitize_yes_no( $value ) {
        return ( 'yes' === $value ) ? 'yes' : 'no';
    }

    /**
     * Auth callback for registered post meta – requires edit_posts capability.
     *
     * @return bool
     */
    public function meta_auth_callback() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Auth callback for registered term meta – requires manage_categories capability.
     *
     * @return bool
     */
    public function term_meta_auth_callback() {
        return current_user_can( 'manage_categories' );
    }
}

/* ====================================================================== */
/*  Global helper functions                                                 */
/* ====================================================================== */

if ( ! function_exists( 'videohub360_course_features_enabled' ) ) {
    /**
     * Check whether course/lesson features are enabled.
     *
     * @return bool
     */
    function videohub360_course_features_enabled() {
        return (bool) get_option( 'videohub360_enable_course_features', 0 );
    }
}

/* ------------------------------------------------------------------ */
/*  Label helpers                                                        */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'videohub360_get_course_label' ) ) {
    /**
     * Return the singular or plural label for "Course".
     *
     * @param  bool $plural Whether to return the plural form.
     * @return string
     */
    function videohub360_get_course_label( $plural = false ) {
        $label = $plural ? __( 'Courses', 'videohub360' ) : __( 'Course', 'videohub360' );
        return apply_filters( 'videohub360_course_label', $label, $plural );
    }
}

if ( ! function_exists( 'videohub360_get_lesson_label' ) ) {
    /**
     * Return the singular or plural label for "Lesson".
     *
     * @param  bool $plural Whether to return the plural form.
     * @return string
     */
    function videohub360_get_lesson_label( $plural = false ) {
        $label = $plural ? __( 'Lessons', 'videohub360' ) : __( 'Lesson', 'videohub360' );
        return apply_filters( 'videohub360_lesson_label', $label, $plural );
    }
}

if ( ! function_exists( 'videohub360_get_topic_label' ) ) {
    /**
     * Return the singular or plural label for "Topic".
     *
     * @param  bool $plural Whether to return the plural form.
     * @return string
     */
    function videohub360_get_topic_label( $plural = false ) {
        $label = $plural ? __( 'Topics', 'videohub360' ) : __( 'Topic', 'videohub360' );
        return apply_filters( 'videohub360_topic_label', $label, $plural );
    }
}

if ( ! function_exists( 'videohub360_get_instructor_label' ) ) {
    /**
     * Return the singular or plural label for "Instructor".
     *
     * @param  bool $plural Whether to return the plural form.
     * @return string
     */
    function videohub360_get_instructor_label( $plural = false ) {
        $label = $plural ? __( 'Instructors', 'videohub360' ) : __( 'Instructor', 'videohub360' );
        return apply_filters( 'videohub360_instructor_label', $label, $plural );
    }
}

/* ------------------------------------------------------------------ */
/*  Course / lesson retrieval helpers                                    */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'videohub360_get_lesson_course' ) ) {
    /**
     * Return the primary videohub360_series term for a lesson.
     *
     * @param  int $post_id Lesson post ID.
     * @return WP_Term|false Primary series term, or false.
     */
    function videohub360_get_lesson_course( $post_id ) {
        $terms = get_the_terms( $post_id, 'videohub360_series' );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return false;
        }
        // Sort by term_id for consistency.
        usort( $terms, function( $a, $b ) {
            return $a->term_id <=> $b->term_id;
        } );
        return $terms[0];
    }
}

if ( ! function_exists( 'videohub360_get_lesson_courses' ) ) {
    /**
     * Return all videohub360_series terms for a lesson.
     *
     * @param  int $post_id Lesson post ID.
     * @return WP_Term[]
     */
    function videohub360_get_lesson_courses( $post_id ) {
        $terms = get_the_terms( $post_id, 'videohub360_series' );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }
}

if ( ! function_exists( 'videohub360_get_course_lessons' ) ) {
    /**
     * Return published lessons for a course, ordered by module and lesson number.
     *
     * @param  int   $term_id Course (series) term ID.
     * @param  array $args    Optional WP_Query overrides.
     * @return WP_Post[]
     */
    function videohub360_get_course_lessons( $term_id, $args = array() ) {
        $defaults = array(
            'post_type'      => 'videohub360',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'videohub360_series',
                    'field'    => 'term_id',
                    'terms'    => absint( $term_id ),
                ),
            ),
        );

        $query_args = wp_parse_args( $args, $defaults );
        $query      = new WP_Query( $query_args );
        $posts      = $query->posts;

        if ( empty( $posts ) ) {
            return array();
        }

        // Pre-fetch lesson meta for all posts to avoid N*log(N) DB queries.
        $post_ids = wp_list_pluck( $posts, 'ID' );
        $mod_nums = array();
        $les_nums = array();
        foreach ( $post_ids as $pid ) {
            $mod_nums[ $pid ] = (int) get_post_meta( $pid, '_vh360_lesson_module_number', true );
            $les_nums[ $pid ] = (int) get_post_meta( $pid, '_vh360_lesson_number', true );
        }

        // Sort by module number → lesson number → post date.
        usort( $posts, function( $a, $b ) use ( $mod_nums, $les_nums ) {
            $mod_cmp = $mod_nums[ $a->ID ] <=> $mod_nums[ $b->ID ];
            if ( $mod_cmp !== 0 ) {
                return $mod_cmp;
            }

            $num_cmp = $les_nums[ $a->ID ] <=> $les_nums[ $b->ID ];
            if ( $num_cmp !== 0 ) {
                return $num_cmp;
            }

            // Fall back to post date.
            return strtotime( $a->post_date ) <=> strtotime( $b->post_date );
        } );

        return $posts;
    }
}

if ( ! function_exists( 'videohub360_get_lesson_navigation' ) ) {
    /**
     * Return navigation data for a lesson within its primary course.
     *
     * Returns an array with keys:
     *   course       WP_Term|false
     *   prev_id      int|false
     *   next_id      int|false
     *   index        int   (1-based)
     *   total        int
     *
     * @param  int $post_id Lesson post ID.
     * @return array
     */
    function videohub360_get_lesson_navigation( $post_id ) {
        $nav = array(
            'course'  => false,
            'prev_id' => false,
            'next_id' => false,
            'index'   => 0,
            'total'   => 0,
        );

        $course = videohub360_get_lesson_course( $post_id );
        if ( ! $course ) {
            return $nav;
        }

        $nav['course'] = $course;
        $lessons       = videohub360_get_course_lessons( $course->term_id );
        $total         = count( $lessons );
        $nav['total']  = $total;

        foreach ( $lessons as $i => $lesson ) {
            if ( (int) $lesson->ID === (int) $post_id ) {
                $nav['index'] = $i + 1;

                if ( $i > 0 ) {
                    $nav['prev_id'] = $lessons[ $i - 1 ]->ID;
                }
                if ( $i < $total - 1 ) {
                    $nav['next_id'] = $lessons[ $i + 1 ]->ID;
                }
                break;
            }
        }

        return $nav;
    }
}

if ( ! function_exists( 'videohub360_get_course_instructor' ) ) {
    /**
     * Return the WP_User for a course's instructor.
     *
     * Precedence:
     * 1. _vh360_course_instructor_user_id term meta
     * 2. Author of the first lesson in the course
     * 3. false
     *
     * @param  int $term_id Course (series) term ID.
     * @return WP_User|false
     */
    function videohub360_get_course_instructor( $term_id ) {
        $user_id = (int) get_term_meta( $term_id, '_vh360_course_instructor_user_id', true );

        if ( $user_id > 0 ) {
            $user = get_userdata( $user_id );
            return $user ? $user : false;
        }

        $lessons = videohub360_get_course_lessons( $term_id );
        if ( ! empty( $lessons ) ) {
            $author_id = (int) $lessons[0]->post_author;
            if ( $author_id > 0 ) {
                $user = get_userdata( $author_id );
                return $user ? $user : false;
            }
        }

        return false;
    }
}

if ( ! function_exists( 'videohub360_get_course_required_membership' ) ) {
    /**
     * Return the membership plan key required to access a course.
     *
     * @param  int $term_id Course (series) term ID.
     * @return string|false Plan key, 'any', or false.
     */
    function videohub360_get_course_required_membership( $term_id ) {
        $value = get_term_meta( $term_id, '_vh360_course_required_membership', true );

        if ( empty( $value ) ) {
            return false;
        }

        return $value;
    }
}

if ( ! function_exists( 'videohub360_get_effective_lesson_required_membership' ) ) {
    /**
     * Return the effective membership requirement for a lesson.
     *
     * Precedence:
     * 1. Lesson-level _vh360_membership_required (post meta) — always wins.
     * 2. Course-level _vh360_course_required_membership (term meta).
     * 3. false — no restriction.
     *
     * IMPORTANT: this function must NOT call vh360_post_requires_membership()
     * to avoid infinite recursion.
     *
     * @param  int $post_id Lesson post ID.
     * @return string|false Plan key, 'any', or false.
     */
    function videohub360_get_effective_lesson_required_membership( $post_id ) {
        // 1. Lesson-level override.
        $lesson_plan = get_post_meta( $post_id, '_vh360_membership_required', true );
        if ( ! empty( $lesson_plan ) ) {
            return $lesson_plan;
        }

        // 2. Course-level inheritance.
        $course = videohub360_get_lesson_course( $post_id );
        if ( $course ) {
            $course_plan = videohub360_get_course_required_membership( $course->term_id );
            if ( ! empty( $course_plan ) ) {
                return $course_plan;
            }
        }

        return false;
    }
}
