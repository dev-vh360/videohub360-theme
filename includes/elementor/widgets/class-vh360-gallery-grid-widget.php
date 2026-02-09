<?php
/**
 * Elementor Gallery Grid Widget
 *
 * Widget to display a grid of galleries.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Grid_Widget
 *
 * Elementor widget for displaying multiple galleries in a grid.
 */
class VH360_Gallery_Grid_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'vh360_gallery_grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'VH360 Gallery Grid', 'videohub360-theme' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-gallery-justified';
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
		return array( 'gallery', 'grid', 'portfolio', 'photos', 'vh360' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Query', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of Galleries', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 50,
				'step'    => 1,
				'default' => 6,
			)
		);

		// Get categories for select.
		$categories      = vh360_get_gallery_categories();
		$category_options = array( '' => __( 'All Categories', 'videohub360-theme' ) );
		foreach ( $categories as $category ) {
			$category_options[ $category->slug ] = $category->name;
		}

		$this->add_control(
			'category',
			array(
				'label'   => __( 'Category', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $category_options,
				'default' => '',
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'date'       => __( 'Date', 'videohub360-theme' ),
					'title'      => __( 'Title', 'videohub360-theme' ),
					'menu_order' => __( 'Menu Order', 'videohub360-theme' ),
					'rand'       => __( 'Random', 'videohub360-theme' ),
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

		$this->end_controls_section();

		// Layout Section.
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => __( 'Layout', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'videohub360-theme' ),
				'type'           => \Elementor\Controls_Manager::SELECT,
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
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
			'show_count',
			array(
				'label'        => __( 'Show Image Count', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label'        => __( 'Show Author', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// Style Section - Grid.
		$this->start_controls_section(
			'style_grid_section',
			array(
				'label' => __( 'Grid', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-gallery-grid-shortcode' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Card.
		$this->start_controls_section(
			'style_card_section',
			array(
				'label' => __( 'Card', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'    => 8,
					'right'  => 8,
					'bottom' => 8,
					'left'   => 8,
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-gallery-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .vh360-gallery-card',
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => __( 'Background Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-gallery-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Title.
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

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .vh360-gallery-card-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .vh360-gallery-card-title',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$query_args = array(
			'post_type'      => 'vh360_gallery',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $settings['count'] ),
			'orderby'        => $settings['orderby'],
			'order'          => $settings['order'],
		);

		// Filter by category.
		if ( ! empty( $settings['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'vh360_gallery_category',
					'field'    => 'slug',
					'terms'    => $settings['category'],
				),
			);
		}

		$galleries = get_posts( $query_args );

		if ( empty( $galleries ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="vh360-gallery-notice">' . esc_html__( 'No galleries found.', 'videohub360-theme' ) . '</p>';
			}
			return;
		}

		$columns     = $settings['columns'];
		$show_title  = 'yes' === $settings['show_title'];
		$show_count  = 'yes' === $settings['show_count'];
		$show_author = 'yes' === $settings['show_author'];

		$wrapper_class = 'vh360-gallery-grid-shortcode vh360-gallery-cols-' . esc_attr( $columns );
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>">
			<?php foreach ( $galleries as $gallery ) : ?>
				<?php
				$image_count = vh360_get_gallery_image_count( $gallery->ID );
				$cover_url   = get_the_post_thumbnail_url( $gallery->ID, 'vh360-gallery-thumb' );
				if ( ! $cover_url ) {
					$cover_url = get_the_post_thumbnail_url( $gallery->ID, 'medium' );
				}
				?>
				<article class="vh360-gallery-card" data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>">
					<a href="<?php echo esc_url( get_permalink( $gallery->ID ) ); ?>" class="vh360-gallery-card-link">
						<div class="vh360-gallery-card-cover">
							<?php if ( $cover_url ) : ?>
								<img src="<?php echo esc_url( $cover_url ); ?>" 
									 alt="<?php echo esc_attr( get_the_title( $gallery->ID ) ); ?>"
									 loading="lazy"
									 class="vh360-gallery-card-image">
							<?php else : ?>
								<div class="vh360-gallery-card-placeholder">
									<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
										<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
										<circle cx="8.5" cy="8.5" r="1.5"></circle>
										<polyline points="21 15 16 10 5 21"></polyline>
									</svg>
								</div>
							<?php endif; ?>
							<?php if ( $show_count && $image_count > 0 ) : ?>
								<span class="vh360-gallery-card-count">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
										<circle cx="8.5" cy="8.5" r="1.5"></circle>
										<polyline points="21 15 16 10 5 21"></polyline>
									</svg>
									<?php echo esc_html( $image_count ); ?>
								</span>
							<?php endif; ?>
							<div class="vh360-gallery-card-overlay"></div>
						</div>
						<?php if ( $show_title || $show_author ) : ?>
							<div class="vh360-gallery-card-info">
								<?php if ( $show_title ) : ?>
									<h3 class="vh360-gallery-card-title"><?php echo esc_html( get_the_title( $gallery->ID ) ); ?></h3>
								<?php endif; ?>
								<?php if ( $show_author ) : ?>
									<span class="vh360-gallery-card-author">
										<?php
										/* translators: %s: author name */
										printf( esc_html__( 'by %s', 'videohub360-theme' ), esc_html( get_the_author_meta( 'display_name', $gallery->post_author ) ) );
										?>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
