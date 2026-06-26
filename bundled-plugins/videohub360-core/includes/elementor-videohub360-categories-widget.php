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

        // ── Style Tab: Category Item Style ────────────────────────────────
        $this->start_controls_section( 'item_style_section', [
            'label' => __( 'Category Item Style', 'videohub360' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'item_bg_color', [
            'label'     => __( 'Background Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card, {{WRAPPER}} .vh360-category-pill, {{WRAPPER}} .vh360-category-compact-link' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'item_hover_bg_color', [
            'label'     => __( 'Hover Background Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card:hover, {{WRAPPER}} .vh360-category-pill:hover, {{WRAPPER}} .vh360-category-compact-link:hover' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'item_text_color', [
            'label'     => __( 'Text Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-title, {{WRAPPER}} .vh360-category-pill-title, {{WRAPPER}} .vh360-category-compact-title' => 'color: {{VALUE}};',
            ],
            'separator' => 'before',
        ] );

        $this->add_control( 'item_hover_text_color', [
            'label'     => __( 'Hover Text Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card:hover .vh360-category-card-title, {{WRAPPER}} .vh360-category-pill:hover .vh360-category-pill-title, {{WRAPPER}} .vh360-category-compact-link:hover .vh360-category-compact-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'item_desc_color', [
            'label'     => __( 'Description Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-description' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'count_text_color', [
            'label'     => __( 'Count / Badge Text Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-count, {{WRAPPER}} .vh360-category-pill-count, {{WRAPPER}} .vh360-category-compact-count' => 'color: {{VALUE}};',
            ],
            'separator' => 'before',
        ] );

        $this->add_control( 'count_bg_color', [
            'label'     => __( 'Count / Badge Background', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-count, {{WRAPPER}} .vh360-category-pill-count, {{WRAPPER}} .vh360-category-compact-count' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'item_border_heading', [
            'label'     => __( 'Border', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .vh360-category-card, {{WRAPPER}} .vh360-category-pill, {{WRAPPER}} .vh360-category-compact-link',
        ] );

        $this->add_control( 'item_hover_border_color', [
            'label'     => __( 'Hover Border Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card:hover, {{WRAPPER}} .vh360-category-pill:hover, {{WRAPPER}} .vh360-category-compact-link:hover' => 'border-color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'item_border_radius', [
            'label'      => __( 'Border Radius', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-card, {{WRAPPER}} .vh360-category-pill, {{WRAPPER}} .vh360-category-compact-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'item_padding', [
            'label'      => __( 'Content Padding', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-card-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .vh360-category-pill'      => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                '{{WRAPPER}} .vh360-category-compact-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'      => 'item_box_shadow',
            'label'     => __( 'Box Shadow', 'videohub360' ),
            'selector'  => '{{WRAPPER}} .vh360-category-card, {{WRAPPER}} .vh360-category-pill, {{WRAPPER}} .vh360-category-compact-link',
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_hover_box_shadow',
            'label'    => __( 'Hover Box Shadow', 'videohub360' ),
            'selector' => '{{WRAPPER}} .vh360-category-card:hover, {{WRAPPER}} .vh360-category-pill:hover, {{WRAPPER}} .vh360-category-compact-link:hover',
        ] );

        $this->end_controls_section();

        // ── Style Tab: Card Media Style ───────────────────────────────────
        $this->start_controls_section( 'card_media_style_section', [
            'label'     => __( 'Card Media Style', 'videohub360' ),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'layout' => 'cards',
            ],
        ] );

        $this->add_control( 'media_bg_color', [
            'label'     => __( 'Media Background Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-thumb, {{WRAPPER}} .vh360-category-card-placeholder' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'media_height', [
            'label'      => __( 'Media Height', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh', '%' ],
            'range'      => [
                'px' => [ 'min' => 60, 'max' => 600 ],
                'vh' => [ 'min' => 5, 'max' => 80 ],
                '%'  => [ 'min' => 5, 'max' => 100 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-card-thumb' => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: unset;',
            ],
        ] );

        $this->add_control( 'media_icon_color', [
            'label'     => __( 'Placeholder Icon Color', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .vh360-category-card-placeholder' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        // ── Style Tab: Cards Layout ───────────────────────────────────────
        $this->start_controls_section( 'cards_layout_section', [
            'label'     => __( 'Cards Layout', 'videohub360' ),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'layout' => 'cards',
            ],
        ] );

        $this->add_responsive_control( 'cards_gap', [
            'label'      => __( 'Card Gap', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 80 ],
                'em' => [ 'min' => 0, 'max' => 5 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-grid' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'card_title_heading', [
            'label'     => __( 'Title', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'card_title_typography',
            'selector' => '{{WRAPPER}} .vh360-category-card-title',
        ] );

        $this->add_control( 'card_desc_heading', [
            'label'     => __( 'Description', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'card_desc_typography',
            'selector' => '{{WRAPPER}} .vh360-category-card-description',
        ] );

        $this->add_control( 'card_count_heading', [
            'label'     => __( 'Count Badge', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'card_count_typography',
            'selector' => '{{WRAPPER}} .vh360-category-card-count',
        ] );

        $this->end_controls_section();

        // ── Style Tab: Pills Layout ───────────────────────────────────────
        $this->start_controls_section( 'pills_layout_section', [
            'label'     => __( 'Pills Layout', 'videohub360' ),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'layout' => 'pills',
            ],
        ] );

        $this->add_responsive_control( 'pills_gap', [
            'label'      => __( 'Pill Gap', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 40 ],
                'em' => [ 'min' => 0, 'max' => 3 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-pills' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'pill_padding', [
            'label'      => __( 'Pill Padding', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'pill_border_radius', [
            'label'      => __( 'Pill Border Radius', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-pill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'pill_typography_heading', [
            'label'     => __( 'Typography', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'pill_typography',
            'selector' => '{{WRAPPER}} .vh360-category-pill',
        ] );

        $this->end_controls_section();

        // ── Style Tab: Compact Layout ─────────────────────────────────────
        $this->start_controls_section( 'compact_layout_section', [
            'label'     => __( 'Compact Layout', 'videohub360' ),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => [
                'layout' => 'compact',
            ],
        ] );

        $this->add_responsive_control( 'compact_gap', [
            'label'      => __( 'Item Gap', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'em' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 40 ],
                'em' => [ 'min' => 0, 'max' => 3 ],
            ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-compact' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'compact_item_padding', [
            'label'      => __( 'Item Padding', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'em', '%' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-compact-link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'separator' => 'before',
        ] );

        $this->add_responsive_control( 'compact_item_border_radius', [
            'label'      => __( 'Item Border Radius', 'videohub360' ),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'em' ],
            'selectors'  => [
                '{{WRAPPER}} .vh360-category-compact-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'compact_title_heading', [
            'label'     => __( 'Title Typography', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'compact_title_typography',
            'selector' => '{{WRAPPER}} .vh360-category-compact-title',
        ] );

        $this->add_control( 'compact_count_heading', [
            'label'     => __( 'Count Typography', 'videohub360' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'compact_count_typography',
            'selector' => '{{WRAPPER}} .vh360-category-compact-count',
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
