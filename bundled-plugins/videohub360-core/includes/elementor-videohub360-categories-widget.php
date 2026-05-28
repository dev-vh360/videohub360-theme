<?php
/**
 * Elementor VideoHub360 Browse Categories Widget
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Elementor\\Widget_Base' ) ) {
    return;
}

class Elementor_VideoHub360_Categories_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'videohub360_categories';
    }

    public function get_title() {
        return __( 'VideoHub360 Browse Categories', 'videohub360' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_style_depends() {
        return [ 'vh360-categories', 'vh360-variables' ];
    }

    protected function register_controls() {

        // ── Display Section ───────────────────────────────────────────────
        $this->start_controls_section( 'display_section', [
            'label' => __( 'Display', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'layout', [
            'label'   => __( 'Layout', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'cards',
            'options' => [
                'cards'   => __( 'Cards', 'videohub360' ),
                'pills'   => __( 'Pills', 'videohub360' ),
                'compact' => __( 'Compact', 'videohub360' ),
            ],
        ] );

        $this->add_control( 'columns', [
            'label'     => __( 'Columns', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => '4',
            'options'   => [
                '2' => __( '2 Columns', 'videohub360' ),
                '3' => __( '3 Columns', 'videohub360' ),
                '4' => __( '4 Columns', 'videohub360' ),
                '5' => __( '5 Columns', 'videohub360' ),
                '6' => __( '6 Columns', 'videohub360' ),
            ],
            'condition' => [
                'layout!' => 'pills',
            ],
        ] );

        $this->add_control( 'show_count', [
            'label'        => __( 'Show Video Count', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Show', 'videohub360' ),
            'label_off'    => __( 'Hide', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_description', [
            'label'        => __( 'Show Description', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Show', 'videohub360' ),
            'label_off'    => __( 'Hide', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [
                'layout' => 'cards',
            ],
        ] );

        $this->add_control( 'show_latest_thumbnail', [
            'label'        => __( 'Show Latest Video Thumbnail', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Show', 'videohub360' ),
            'label_off'    => __( 'Hide', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [
                'layout' => 'cards',
            ],
        ] );

        $this->end_controls_section();

        // ── Query Section ─────────────────────────────────────────────────
        $this->start_controls_section( 'query_section', [
            'label' => __( 'Query', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'limit', [
            'label'   => __( 'Number of Categories', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 8,
            'min'     => 1,
            'max'     => 50,
        ] );

        $this->add_control( 'hide_empty', [
            'label'        => __( 'Hide Empty Categories', 'videohub360' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'videohub360' ),
            'label_off'    => __( 'No', 'videohub360' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'orderby', [
            'label'   => __( 'Order By', 'videohub360' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'name',
            'options' => [
                'name'  => __( 'Name', 'videohub360' ),
                'count' => __( 'Video Count', 'videohub360' ),
                'slug'  => __( 'Slug', 'videohub360' ),
                'id'    => __( 'ID', 'videohub360' ),
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

        $this->add_control( 'include', [
            'label'       => __( 'Include Term IDs', 'videohub360' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'e.g. 5,12,34', 'videohub360' ),
            'description' => __( 'Comma-separated category term IDs to include.', 'videohub360' ),
        ] );

        $this->add_control( 'exclude', [
            'label'       => __( 'Exclude Term IDs', 'videohub360' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'e.g. 3,7', 'videohub360' ),
            'description' => __( 'Comma-separated category term IDs to exclude.', 'videohub360' ),
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $atts = array(
            'layout'                => esc_attr( $s['layout'] ),
            'columns'               => intval( $s['columns'] ),
            'limit'                 => intval( $s['limit'] ),
            'hide_empty'            => $s['hide_empty'] ? 'yes' : 'no',
            'orderby'               => esc_attr( $s['orderby'] ),
            'order'                 => esc_attr( $s['order'] ),
            'show_count'            => $s['show_count'] ? 'yes' : 'no',
            'show_description'      => $s['show_description'] ? 'yes' : 'no',
            'show_latest_thumbnail' => $s['show_latest_thumbnail'] ? 'yes' : 'no',
            'include'               => esc_attr( $s['include'] ),
            'exclude'               => esc_attr( $s['exclude'] ),
        );

        // Build attribute string for the shortcode
        $atts_string = '';
        foreach ( $atts as $key => $value ) {
            $atts_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
        }

        echo '<div class="videohub360-elementor-widget-wrapper">';
        echo do_shortcode( '[videohub360_categories' . $atts_string . ']' );
        echo '</div>';
    }
}
