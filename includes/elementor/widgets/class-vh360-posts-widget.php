<?php
/**
 * Elementor Posts Widget
 *
 * Widget to display WordPress blog posts with customizable options.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Posts_Widget
 *
 * Elementor widget for displaying WordPress blog posts.
 */
class VH360_Posts_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'vh360_posts';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'VH360 Posts', 'videohub360-theme' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'vh360-theme' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'posts', 'blog', 'news', 'articles', 'vh360' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Query Controls Section.
		$this->start_controls_section(
			'query_section',
			array(
				'label' => __( 'Query', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Posts Per Page', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'date'          => __( 'Date', 'videohub360-theme' ),
					'title'         => __( 'Title', 'videohub360-theme' ),
					'comment_count' => __( 'Comment Count', 'videohub360-theme' ),
					'rand'          => __( 'Random', 'videohub360-theme' ),
					'menu_order'    => __( 'Menu Order', 'videohub360-theme' ),
				),
				'default' => 'date',
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'DESC' => __( 'Descending', 'videohub360-theme' ),
					'ASC'  => __( 'Ascending', 'videohub360-theme' ),
				),
				'default' => 'DESC',
			)
		);

		// Get categories for select.
		$categories = get_categories( array( 'hide_empty' => false ) );
		$cat_options = array();
		foreach ( $categories as $category ) {
			$cat_options[ $category->term_id ] = $category->name;
		}

		$this->add_control(
			'categories',
			array(
				'label'    => __( 'Filter by Categories', 'videohub360-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => $cat_options,
				'default'  => array(),
			)
		);

		// Get tags for select.
		$tags = get_tags( array( 'hide_empty' => false ) );
		$tag_options = array();
		foreach ( $tags as $tag ) {
			$tag_options[ $tag->term_id ] = $tag->name;
		}

		$this->add_control(
			'tags',
			array(
				'label'    => __( 'Filter by Tags', 'videohub360-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => $tag_options,
				'default'  => array(),
			)
		);

		$this->add_control(
			'exclude_current',
			array(
				'label'        => __( 'Exclude Current Post', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'offset',
			array(
				'label'       => __( 'Offset', 'videohub360-theme' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Number of posts to skip', 'videohub360-theme' ),
			)
		);

		$this->end_controls_section();

		// Layout Controls Section.
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => __( 'Layout', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout Style', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'grid'    => __( 'Grid', 'videohub360-theme' ),
					'list'    => __( 'List', 'videohub360-theme' ),
					'masonry' => __( 'Masonry', 'videohub360-theme' ),
				),
				'default' => 'grid',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'default' => '3',
				'condition' => array(
					'layout' => array( 'grid', 'masonry' ),
				),
			)
		);

		$this->add_control(
			'show_featured_image',
			array(
				'label'        => __( 'Show Featured Image', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		// Get available image sizes.
		$image_sizes = get_intermediate_image_sizes();
		$size_options = array();
		foreach ( $image_sizes as $size ) {
			$size_options[ $size ] = ucwords( str_replace( array( '-', '_' ), ' ', $size ) );
		}
		$size_options['full'] = __( 'Full', 'videohub360-theme' );

		$this->add_control(
			'image_size',
			array(
				'label'     => __( 'Image Size', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => $size_options,
				'default'   => 'medium',
				'condition' => array(
					'show_featured_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'     => __( 'Image Ratio', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'1-1'  => __( '1:1', 'videohub360-theme' ),
					'4-3'  => __( '4:3', 'videohub360-theme' ),
					'16-9' => __( '16:9', 'videohub360-theme' ),
					'21-9' => __( '21:9', 'videohub360-theme' ),
				),
				'default'   => '16-9',
				'condition' => array(
					'show_featured_image' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Content Controls Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show Title', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => __( 'Title HTML Tag', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				),
				'default'   => 'h3',
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => __( 'Show Excerpt', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'     => __( 'Excerpt Length', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 20,
				'min'       => 10,
				'max'       => 100,
				'condition' => array(
					'show_excerpt' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_read_more',
			array(
				'label'        => __( 'Show Read More', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label'     => __( 'Read More Text', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Read More', 'videohub360-theme' ),
				'condition' => array(
					'show_read_more' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_meta',
			array(
				'label'        => __( 'Show Post Meta', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'meta_data',
			array(
				'label'    => __( 'Meta to Display', 'videohub360-theme' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'options'  => array(
					'date'       => __( 'Date', 'videohub360-theme' ),
					'author'     => __( 'Author', 'videohub360-theme' ),
					'categories' => __( 'Categories', 'videohub360-theme' ),
					'comments'   => __( 'Comments Count', 'videohub360-theme' ),
				),
				'default'  => array( 'date', 'author' ),
				'condition' => array(
					'show_meta' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Pagination Controls Section.
		$this->start_controls_section(
			'pagination_section',
			array(
				'label' => __( 'Pagination', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_pagination',
			array(
				'label'        => __( 'Enable Pagination', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'description'  => __( 'Show pagination to navigate through posts', 'videohub360-theme' ),
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'   => __( 'Pagination Type', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'numbers'          => __( 'Numbers', 'videohub360-theme' ),
					'prev_next'        => __( 'Previous/Next', 'videohub360-theme' ),
					'numbers_and_prev_next' => __( 'Numbers + Previous/Next', 'videohub360-theme' ),
				),
				'default' => 'numbers_and_prev_next',
				'condition' => array(
					'enable_pagination' => 'yes',
				),
			)
		);

		$this->add_control(
			'pagination_page_limit',
			array(
				'label'       => __( 'Page Limit', 'videohub360-theme' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 2,
				'min'         => 1,
				'max'         => 10,
				'description' => __( 'Number of page links to show on either side of current page', 'videohub360-theme' ),
				'condition'   => array(
					'enable_pagination' => 'yes',
					'pagination_type'   => array( 'numbers', 'numbers_and_prev_next' ),
				),
			)
		);

		$this->add_control(
			'pagination_prev_label',
			array(
				'label'     => __( 'Previous Label', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( '&laquo; Previous', 'videohub360-theme' ),
				'condition' => array(
					'enable_pagination' => 'yes',
					'pagination_type!'  => 'numbers',
				),
			)
		);

		$this->add_control(
			'pagination_next_label',
			array(
				'label'     => __( 'Next Label', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Next &raquo;', 'videohub360-theme' ),
				'condition' => array(
					'enable_pagination' => 'yes',
					'pagination_type!'  => 'numbers',
				),
			)
		);

		$this->add_control(
			'pagination_align',
			array(
				'label'   => __( 'Alignment', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => array(
					'left'   => array(
						'title' => __( 'Left', 'videohub360-theme' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'videohub360-theme' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'videohub360-theme' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination' => 'text-align: {{VALUE}};',
				),
				'condition' => array(
					'enable_pagination' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Card Styling.
		$this->start_controls_section(
			'style_card_section',
			array(
				'label' => __( 'Card', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .vh360-post-card',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .vh360-post-card',
			)
		);

		$this->add_control(
			'card_padding',
			array(
				'label'      => __( 'Padding', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_hover_heading',
			array(
				'label'     => __( 'Hover Effects', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'card_hover_bg_color',
			array(
				'label'     => __( 'Hover Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-card:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_hover_transform',
			array(
				'label'      => __( 'Hover Transform (Y)', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => -20,
						'max' => 0,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => -4,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-card:hover' => 'transform: translateY({{SIZE}}{{UNIT}});',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Image Styling.
		$this->start_controls_section(
			'style_image_section',
			array(
				'label'     => __( 'Image', 'videohub360-theme' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_featured_image' => 'yes',
				),
			)
		);

		$this->add_control(
			'image_border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-thumbnail, {{WRAPPER}} .vh360-post-thumbnail img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_overlay_color',
			array(
				'label'     => __( 'Hover Overlay Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-thumbnail::after' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_overlay_opacity',
			array(
				'label'      => __( 'Hover Overlay Opacity', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.1,
					),
				),
				'default'    => array(
					'size' => 0.3,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-thumbnail:hover::after' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'image_hover_scale',
			array(
				'label'      => __( 'Hover Scale Effect', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 1.5,
						'step' => 0.05,
					),
				),
				'default'    => array(
					'size' => 1.1,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-thumbnail:hover img' => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Title Styling.
		$this->start_controls_section(
			'style_title_section',
			array(
				'label'     => __( 'Title', 'videohub360-theme' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .vh360-post-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => __( 'Hover Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_spacing',
			array(
				'label'      => __( 'Spacing', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Content Styling.
		$this->start_controls_section(
			'style_content_section',
			array(
				'label' => __( 'Content', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'excerpt_heading',
			array(
				'label' => __( 'Excerpt', 'videohub360-theme' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'excerpt_typography',
				'selector' => '{{WRAPPER}} .vh360-post-excerpt',
			)
		);

		$this->add_control(
			'excerpt_color',
			array(
				'label'     => __( 'Excerpt Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'meta_heading',
			array(
				'label'     => __( 'Post Meta', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .vh360-post-meta',
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => __( 'Meta Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'meta_icon_color',
			array(
				'label'     => __( 'Meta Icon Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-meta i' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_spacing',
			array(
				'label'      => __( 'Element Spacing', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-meta' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .vh360-post-excerpt' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Button Styling.
		$this->start_controls_section(
			'style_button_section',
			array(
				'label'     => __( 'Button', 'videohub360-theme' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_read_more' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .vh360-post-read-more',
			)
		);

		$this->start_controls_tabs( 'button_style_tabs' );

		// Normal state.
		$this->start_controls_tab(
			'button_normal_tab',
			array(
				'label' => __( 'Normal', 'videohub360-theme' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-read-more' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-read-more' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .vh360-post-read-more',
			)
		);

		$this->end_controls_tab();

		// Hover state.
		$this->start_controls_tab(
			'button_hover_tab',
			array(
				'label' => __( 'Hover', 'videohub360-theme' ),
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Text Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-read-more:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_bg_color',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-read-more:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-post-read-more:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-read-more' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-post-read-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Controls - Pagination Styling.
		$this->start_controls_section(
			'style_pagination_section',
			array(
				'label'     => __( 'Pagination', 'videohub360-theme' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_pagination' => 'yes',
				),
			)
		);

		$this->add_responsive_control(
			'pagination_spacing',
			array(
				'label'      => __( 'Spacing', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 30,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-posts-pagination' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .vh360-posts-pagination .page-numbers',
			)
		);

		$this->start_controls_tabs( 'pagination_style_tabs' );

		// Normal state.
		$this->start_controls_tab(
			'pagination_normal_tab',
			array(
				'label' => __( 'Normal', 'videohub360-theme' ),
			)
		);

		$this->add_control(
			'pagination_color',
			array(
				'label'     => __( 'Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_bg_color',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'pagination_border',
				'selector' => '{{WRAPPER}} .vh360-posts-pagination .page-numbers',
			)
		);

		$this->end_controls_tab();

		// Hover/Active state.
		$this->start_controls_tab(
			'pagination_hover_tab',
			array(
				'label' => __( 'Hover & Active', 'videohub360-theme' ),
			)
		);

		$this->add_control(
			'pagination_hover_color',
			array(
				'label'     => __( 'Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers:hover, {{WRAPPER}} .vh360-posts-pagination .page-numbers.current' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_hover_bg_color',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers:hover, {{WRAPPER}} .vh360-posts-pagination .page-numbers.current' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers:hover, {{WRAPPER}} .vh360-posts-pagination .page-numbers.current' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'pagination_border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'separator'  => 'before',
				'selectors'  => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'pagination_padding',
			array(
				'label'      => __( 'Padding', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-posts-pagination .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'pagination_gap',
			array(
				'label'      => __( 'Gap Between Items', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 8,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-posts-pagination' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Get current page number for pagination
		$paged = 1;
		if ( 'yes' === $settings['enable_pagination'] ) {
			if ( get_query_var( 'paged' ) ) {
				$paged = get_query_var( 'paged' );
			} elseif ( get_query_var( 'page' ) ) {
				$paged = get_query_var( 'page' );
			}
		}

		// Build query arguments.
		$query_args = array(
			'post_type'           => 'post',
			'posts_per_page'      => $settings['posts_per_page'],
			'orderby'             => $settings['orderby'],
			'order'               => $settings['order'],
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'paged'               => $paged,
		);

		// Add category filter.
		if ( ! empty( $settings['categories'] ) ) {
			$query_args['category__in'] = $settings['categories'];
		}

		// Add tag filter.
		if ( ! empty( $settings['tags'] ) ) {
			$query_args['tag__in'] = $settings['tags'];
		}

		// Exclude current post.
		if ( 'yes' === $settings['exclude_current'] && is_single() ) {
			$query_args['post__not_in'] = array( get_the_ID() );
		}

		// Add offset.
		if ( $settings['offset'] > 0 ) {
			// When using offset with pagination, we need to calculate the correct offset for each page
			if ( 'yes' === $settings['enable_pagination'] && $paged > 1 ) {
				$query_args['offset'] = $settings['offset'] + ( ( $paged - 1 ) * $settings['posts_per_page'] );
			} else {
				$query_args['offset'] = $settings['offset'];
			}
		}

		// Run query.
		$query = new WP_Query( $query_args );

		// Build container classes.
		$layout = $settings['layout'];
		$columns = $settings['columns'];
		$image_ratio = $settings['image_ratio'];
		
		$container_classes = array(
			'vh360-posts-grid',
			'vh360-posts-layout-' . esc_attr( $layout ),
			'vh360-posts-columns-' . esc_attr( $columns ),
			'vh360-posts-ratio-' . esc_attr( $image_ratio ),
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>">
			<?php
			if ( $query->have_posts() ) :
				while ( $query->have_posts() ) :
					$query->the_post();
					?>
					<article <?php post_class( 'vh360-post-card' ); ?>>
						<?php if ( 'yes' === $settings['show_featured_image'] && has_post_thumbnail() ) : ?>
							<div class="vh360-post-thumbnail">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( $settings['image_size'], array( 'loading' => 'lazy' ) ); ?>
								</a>
							</div>
						<?php endif; ?>
						
						<div class="vh360-post-content">
							<?php if ( 'yes' === $settings['show_meta'] && ! empty( $settings['meta_data'] ) ) : ?>
								<div class="vh360-post-meta">
									<?php
									foreach ( $settings['meta_data'] as $meta ) {
										switch ( $meta ) {
											case 'date':
												echo '<span class="vh360-post-meta-item vh360-post-date">';
												echo '<i class="far fa-calendar"></i> ';
												echo esc_html( get_the_date() );
												echo '</span>';
												break;
											case 'author':
												echo '<span class="vh360-post-meta-item vh360-post-author">';
												echo '<i class="far fa-user"></i> ';
												echo '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">';
												echo esc_html( get_the_author() );
												echo '</a>';
												echo '</span>';
												break;
											case 'categories':
												$categories = get_the_category();
												if ( ! empty( $categories ) ) {
													echo '<span class="vh360-post-meta-item vh360-post-categories">';
													echo '<i class="far fa-folder"></i> ';
													foreach ( $categories as $index => $category ) {
														if ( $index > 0 ) {
															echo ', ';
														}
														echo '<a href="' . esc_url( get_category_link( $category->term_id ) ) . '">';
														echo esc_html( $category->name );
														echo '</a>';
													}
													echo '</span>';
												}
												break;
											case 'comments':
												echo '<span class="vh360-post-meta-item vh360-post-comments">';
												echo '<i class="far fa-comments"></i> ';
												comments_number(
													esc_html__( '0 Comments', 'videohub360-theme' ),
													esc_html__( '1 Comment', 'videohub360-theme' ),
													esc_html__( '% Comments', 'videohub360-theme' )
												);
												echo '</span>';
												break;
										}
									}
									?>
								</div>
							<?php endif; ?>
							
							<?php if ( 'yes' === $settings['show_title'] ) : ?>
								<?php
								// Validate title tag against allowed values.
								$allowed_title_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
								$title_tag = in_array( $settings['title_tag'], $allowed_title_tags, true ) ? $settings['title_tag'] : 'h3';
								?>
								<<?php echo esc_html( $title_tag ); ?> class="vh360-post-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</<?php echo esc_html( $title_tag ); ?>>
							<?php endif; ?>
							
							<?php if ( 'yes' === $settings['show_excerpt'] ) : ?>
								<div class="vh360-post-excerpt">
									<?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), $settings['excerpt_length'], '...' ) ); ?>
								</div>
							<?php endif; ?>
							
							<?php if ( 'yes' === $settings['show_read_more'] ) : ?>
								<a href="<?php the_permalink(); ?>" class="vh360-post-read-more">
									<?php echo esc_html( $settings['read_more_text'] ); ?>
								</a>
							<?php endif; ?>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					echo '<p class="vh360-posts-notice">' . esc_html__( 'No posts found.', 'videohub360-theme' ) . '</p>';
				}
			endif;
			?>
		</div>
		<?php
		// Render pagination if enabled and there are multiple pages
		if ( 'yes' === $settings['enable_pagination'] && $query->max_num_pages > 1 ) {
			$this->render_pagination( $query, $settings );
		}
	}

	/**
	 * Render pagination.
	 *
	 * @param WP_Query $query The query object.
	 * @param array    $settings Widget settings.
	 */
	protected function render_pagination( $query, $settings ) {
		$pagination_type = $settings['pagination_type'];
		$page_limit = isset( $settings['pagination_page_limit'] ) ? absint( $settings['pagination_page_limit'] ) : 2;
		$prev_label = isset( $settings['pagination_prev_label'] ) ? esc_html( $settings['pagination_prev_label'] ) : __( '&laquo; Previous', 'videohub360-theme' );
		$next_label = isset( $settings['pagination_next_label'] ) ? esc_html( $settings['pagination_next_label'] ) : __( 'Next &raquo;', 'videohub360-theme' );

		// Build pagination arguments
		$pagination_args = array(
			'total'     => $query->max_num_pages,
			'current'   => max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ),
			'mid_size'  => $page_limit,
			'prev_text' => $prev_label,
			'next_text' => $next_label,
			'type'      => 'array',
		);

		// Adjust pagination based on type
		if ( 'prev_next' === $pagination_type ) {
			$pagination_args['show_all'] = false;
			$pagination_args['prev_next'] = true;
		} elseif ( 'numbers' === $pagination_type ) {
			$pagination_args['prev_next'] = false;
		}

		$pagination_links = paginate_links( $pagination_args );

		if ( is_array( $pagination_links ) && ! empty( $pagination_links ) ) {
			echo '<nav class="vh360-posts-pagination" role="navigation" aria-label="' . esc_attr__( 'Posts Navigation', 'videohub360-theme' ) . '">';
			echo wp_kses_post( implode( '', $pagination_links ) );
			echo '</nav>';
		}
	}

	/**
	 * Render widget output in editor.
	 */
	protected function content_template() {
		// Pre-render all translatable strings outside the JavaScript template
		$sample_post_alt = esc_js( __( 'Sample Post', 'videohub360-theme' ) );
		$january_text = esc_js( __( 'January', 'videohub360-theme' ) );
		$author_name_text = esc_js( __( 'Author Name', 'videohub360-theme' ) );
		$category_text = esc_js( __( 'Category', 'videohub360-theme' ) );
		$comments_text = esc_js( __( 'Comments', 'videohub360-theme' ) );
		$sample_title_text = esc_js( __( 'Sample Post Title', 'videohub360-theme' ) );
		$sample_excerpt_text = esc_js( __( 'This is a sample excerpt for post', 'videohub360-theme' ) );
		$excerpt_demo_text = esc_js( __( 'It demonstrates how the post content will be displayed with the current settings.', 'videohub360-theme' ) );
		$read_more_text = esc_js( __( 'Read More', 'videohub360-theme' ) );
		?>
		<#
		// Build container classes
		var layoutClass = 'vh360-posts-layout-' + settings.layout;
		var columnsClass = 'vh360-posts-columns-' + settings.columns;
		var ratioClass = 'vh360-posts-ratio-' + settings.image_ratio;
		
		// Get number of sample posts to display (max 6 for preview)
		var postsCount = Math.min(settings.posts_per_page || 6, 6);
		
		// Get placeholder image (use SVG data URI as it's more reliable)
		var placeholderImg = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600"%3E%3Crect fill="%23ddd" width="800" height="600"/%3E%3Ctext fill="%23999" font-family="sans-serif" font-size="30" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3ESample Image%3C/text%3E%3C/svg%3E';
		
		// Get title tag with fallback
		var titleTag = settings.title_tag || 'h3';
		
		// Get read more text with fallback (using pre-rendered PHP string)
		var readMoreText = settings.read_more_text || '<?php echo $read_more_text; ?>';
		
		// Pre-rendered translatable strings from PHP
		var i18n = {
			samplePostAlt: '<?php echo $sample_post_alt; ?>',
			january: '<?php echo $january_text; ?>',
			authorName: '<?php echo $author_name_text; ?>',
			category: '<?php echo $category_text; ?>',
			comments: '<?php echo $comments_text; ?>',
			sampleTitle: '<?php echo $sample_title_text; ?>',
			sampleExcerpt: '<?php echo $sample_excerpt_text; ?>',
			excerptDemo: '<?php echo $excerpt_demo_text; ?>'
		};
		#>
		<div class="vh360-posts-grid {{ layoutClass }} {{ columnsClass }} {{ ratioClass }}">
			<# for (var i = 1; i <= postsCount; i++) { #>
				<article class="vh360-post-card post-{{ i }} post type-post status-publish format-standard has-post-thumbnail">
					<# if (settings.show_featured_image === 'yes') { #>
						<div class="vh360-post-thumbnail">
							<a href="#">
								<img src="{{ placeholderImg }}" alt="{{ i18n.samplePostAlt }} {{ i }}">
							</a>
						</div>
					<# } #>
					
					<div class="vh360-post-content">
						<# if (settings.show_meta === 'yes' && settings.meta_data && settings.meta_data.length > 0) { #>
							<div class="vh360-post-meta">
								<# if (settings.meta_data.indexOf('date') !== -1) { #>
									<span class="vh360-post-meta-item vh360-post-date">
										<i class="far fa-calendar"></i> {{ i18n.january }} {{ i }}, 2024
									</span>
								<# } #>
								<# if (settings.meta_data.indexOf('author') !== -1) { #>
									<span class="vh360-post-meta-item vh360-post-author">
										<i class="far fa-user"></i> <a href="#">{{ i18n.authorName }}</a>
									</span>
								<# } #>
								<# if (settings.meta_data.indexOf('categories') !== -1) { #>
									<span class="vh360-post-meta-item vh360-post-categories">
										<i class="far fa-folder"></i> <a href="#">{{ i18n.category }}</a>
									</span>
								<# } #>
								<# if (settings.meta_data.indexOf('comments') !== -1) { #>
									<span class="vh360-post-meta-item vh360-post-comments">
										<i class="far fa-comments"></i> {{ i }} {{ i18n.comments }}
									</span>
								<# } #>
							</div>
						<# } #>
						
						<# if (settings.show_title === 'yes') { #>
							<{{ titleTag }} class="vh360-post-title">
								<a href="#">{{ i18n.sampleTitle }} {{ i }}</a>
							</{{ titleTag }}>
						<# } #>
						
						<# if (settings.show_excerpt === 'yes') { #>
							<div class="vh360-post-excerpt">
								{{ i18n.sampleExcerpt }} {{ i }}. {{ i18n.excerptDemo }}
							</div>
						<# } #>
						
						<# if (settings.show_read_more === 'yes') { #>
							<a href="#" class="vh360-post-read-more">
								{{ readMoreText }}
							</a>
						<# } #>
					</div>
				</article>
			<# } #>
		</div>
		<?php
	}
}
