<?php
/**
 * Elementor VideoHub360 Course Catalog Widget
 *
 * Renders the [vh360_course_catalog] shortcode inside Elementor.
 * All display logic lives in VideoHub360_Widgets::course_catalog_shortcode().
 *
 * @since 2.6.0
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('Elementor\\Widget_Base') ) {
    return;
}

class Elementor_VideoHub360_Course_Catalog_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'videohub360_course_catalog';
    }

    public function get_title() {
        return __( 'VideoHub360 Course Catalog', 'videohub360' );
    }

    public function get_icon() {
        return 'eicon-library-open';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_style_depends() {
        return ['vh360-course-mode'];
    }

    protected function register_controls() {
        /* ------------------------------------------------------------------ */
        /*  Display Section                                                     */
        /* ------------------------------------------------------------------ */
        $this->start_controls_section( 'display_section', [
            'label' => __( 'Display', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'columns', [
            'label'   => __( 'Columns', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => [
                '1' => __( '1 Column', 'videohub360' ),
                '2' => __( '2 Columns', 'videohub360' ),
                '3' => __( '3 Columns', 'videohub360' ),
                '4' => __( '4 Columns', 'videohub360' ),
            ],
        ] );

        $this->add_control( 'limit', [
            'label'   => __( 'Limit', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 12,
            'min'     => 1,
            'max'     => 100,
        ] );

        $this->add_control( 'hide_empty', [
            'label'        => __( 'Hide Empty Courses', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_filters', [
            'label'        => __( 'Show Filters', 'videohub360' ),
            'description'  => __( 'Displays filter pills: All Courses, Beginner, Intermediate, Advanced, Free Access, Member Access.', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'no',
        ] );

        $this->add_control( 'show_search', [
            'label'        => __( 'Show Search', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_sort', [
            'label'        => __( 'Show Sort', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_result_count', [
            'label'        => __( 'Show Result Count', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'search_placeholder', [
            'label'       => __( 'Search Placeholder', 'videohub360' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => __( 'Search courses...', 'videohub360' ),
            'placeholder' => __( 'Search courses...', 'videohub360' ),
        ] );

        $this->end_controls_section();

        /* ------------------------------------------------------------------ */
        /*  Query Section                                                       */
        /* ------------------------------------------------------------------ */
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query & Order', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'orderby', [
            'label'   => __( 'Order By', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'meta_order',
            'options' => [
                'meta_order' => __( 'Custom Order', 'videohub360' ),
                'name'       => __( 'Name (A–Z)', 'videohub360' ),
            ],
        ] );

        $this->add_control( 'order', [
            'label'   => __( 'Order', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'ASC',
            'options' => [
                'ASC'  => __( 'Ascending', 'videohub360' ),
                'DESC' => __( 'Descending', 'videohub360' ),
            ],
        ] );

        $this->end_controls_section();

        /* ------------------------------------------------------------------ */
        /*  Fields Section                                                      */
        /* ------------------------------------------------------------------ */
        $this->start_controls_section( 'fields_section', [
            'label' => __( 'Fields', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_instructor', [
            'label'        => __( 'Show Instructor', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_lesson_count', [
            'label'        => __( 'Show Lesson Count', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_access_badge', [
            'label'        => __( 'Show Access Badge', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_description', [
            'label'        => __( 'Show Description', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        // Guard: show friendly notice in Elementor editor/preview when disabled
        if ( ! function_exists('videohub360_course_features_enabled') || ! videohub360_course_features_enabled() ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
                echo '<div style="padding:20px;background:#fff8e1;border-left:4px solid #f9a825;color:#555;">'
                   . esc_html__( 'VideoHub360 Course Catalog: Course / Lesson Features must be enabled to display courses.', 'videohub360' )
                   . '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();

        // Validate columns to ensure it's within the expected range (1-6)
        $columns_raw = isset($settings['columns']) ? absint($settings['columns']) : 3;
        $columns     = ( $columns_raw >= 1 && $columns_raw <= 6 ) ? (string) $columns_raw : '3';

        $limit             = isset($settings['limit'])              ? absint($settings['limit'])                               : 12;
        $hide_empty        = isset($settings['hide_empty'])         ? ( $settings['hide_empty']         === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_filters      = isset($settings['show_filters'])       ? ( $settings['show_filters']        === 'yes' ? 'yes' : 'no' ) : 'no';
        $show_search       = isset($settings['show_search'])        ? ( $settings['show_search']         === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_sort         = isset($settings['show_sort'])          ? ( $settings['show_sort']           === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_result_count = isset($settings['show_result_count'])  ? ( $settings['show_result_count']   === 'yes' ? 'yes' : 'no' ) : 'yes';
        $search_placeholder = isset($settings['search_placeholder']) ? sanitize_text_field($settings['search_placeholder'])        : '';
        $orderby           = isset($settings['orderby'])            ? sanitize_text_field($settings['orderby'])                    : 'meta_order';
        $order             = ( isset($settings['order']) && strtoupper($settings['order']) === 'DESC' ) ? 'DESC' : 'ASC';
        $show_instructor   = isset($settings['show_instructor'])    ? ( $settings['show_instructor']     === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_lesson_count = isset($settings['show_lesson_count'])  ? ( $settings['show_lesson_count']   === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_access_badge = isset($settings['show_access_badge'])  ? ( $settings['show_access_badge']   === 'yes' ? 'yes' : 'no' ) : 'yes';
        $show_description  = isset($settings['show_description'])   ? ( $settings['show_description']    === 'yes' ? 'yes' : 'no' ) : 'yes';

        $shortcode = sprintf(
            '[vh360_course_catalog columns="%s" limit="%d" hide_empty="%s" orderby="%s" order="%s" show_filters="%s" show_search="%s" show_sort="%s" show_result_count="%s" search_placeholder="%s" show_instructor="%s" show_lesson_count="%s" show_access_badge="%s" show_description="%s"]',
            esc_attr($columns),
            $limit,
            esc_attr($hide_empty),
            esc_attr($orderby),
            esc_attr($order),
            esc_attr($show_filters),
            esc_attr($show_search),
            esc_attr($show_sort),
            esc_attr($show_result_count),
            esc_attr($search_placeholder),
            esc_attr($show_instructor),
            esc_attr($show_lesson_count),
            esc_attr($show_access_badge),
            esc_attr($show_description)
        );

        echo do_shortcode($shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    protected function content_template() {
        // Elementor JS preview template (live editor preview)
        ?>
        <div style="padding:20px;background:#f0faf4;border:1px solid #c3e6cb;border-radius:6px;color:#1f5c3a;text-align:center;">
            <strong><?php esc_html_e( 'VideoHub360 Course Catalog', 'videohub360' ); ?></strong><br>
            <small><?php esc_html_e( 'Preview on frontend to see course cards.', 'videohub360' ); ?></small>
        </div>
        <?php
    }
}
