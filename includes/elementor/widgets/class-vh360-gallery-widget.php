<?php
/**
 * Elementor Single Gallery Widget
 *
 * Widget to display a single gallery.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Widget
 *
 * Elementor widget for displaying a single gallery.
 */
class VH360_Gallery_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'vh360_gallery';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'VH360 Gallery', 'videohub360-theme' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
		return array( 'gallery', 'photos', 'images', 'lightbox', 'vh360' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Gallery', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		// Get galleries for select.
		$galleries = vh360_get_galleries( array( 'posts_per_page' => 100 ) );
		$options   = array( '' => __( 'Select Gallery', 'videohub360-theme' ) );
		foreach ( $galleries as $gallery ) {
			$options[ $gallery->ID ] = $gallery->post_title;
		}

		$this->add_control(
			'gallery_id',
			array(
				'label'   => __( 'Select Gallery', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $options,
				'default' => '',
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
				'default'      => 'no',
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

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''          => __( 'Gallery Default', 'videohub360-theme' ),
					'grid'      => __( 'Grid', 'videohub360-theme' ),
					'masonry'   => __( 'Masonry', 'videohub360-theme' ),
					'justified' => __( 'Justified', 'videohub360-theme' ),
				),
				'default' => '',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''  => __( 'Gallery Default', 'videohub360-theme' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'default' => '',
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => __( 'Image Size', 'videohub360-theme' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					''          => __( 'Gallery Default', 'videohub360-theme' ),
					'thumbnail' => __( 'Thumbnail', 'videohub360-theme' ),
					'medium'    => __( 'Medium', 'videohub360-theme' ),
					'large'     => __( 'Large', 'videohub360-theme' ),
					'full'      => __( 'Full', 'videohub360-theme' ),
				),
				'default' => '',
			)
		);

		$this->add_control(
			'lightbox',
			array(
				'label'        => __( 'Enable Lightbox', 'videohub360-theme' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'videohub360-theme' ),
				'label_off'    => __( 'No', 'videohub360-theme' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-gallery-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'border_radius',
			array(
				'label'      => __( 'Border Radius', 'videohub360-theme' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .vh360-gallery-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style Section - Overlay.
		$this->start_controls_section(
			'style_overlay_section',
			array(
				'label' => __( 'Overlay', 'videohub360-theme' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'     => __( 'Overlay Color', 'videohub360-theme' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.5)',
				'selectors' => array(
					'{{WRAPPER}} .vh360-gallery-overlay' => 'background: linear-gradient(to bottom, transparent 0%, {{VALUE}} 100%);',
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

		if ( empty( $settings['gallery_id'] ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="vh360-gallery-notice">' . esc_html__( 'Please select a gallery.', 'videohub360-theme' ) . '</p>';
			}
			return;
		}

		$gallery_id = absint( $settings['gallery_id'] );

		// Build render args.
		$args = array();

		if ( ! empty( $settings['layout'] ) ) {
			$args['layout'] = $settings['layout'];
		}

		if ( ! empty( $settings['columns'] ) ) {
			$args['columns'] = absint( $settings['columns'] );
		}

		if ( ! empty( $settings['image_size'] ) ) {
			$args['size'] = $settings['image_size'];
		}

		$args['lightbox']   = 'yes' === $settings['lightbox'];
		$args['show_title'] = 'yes' === $settings['show_title'];

		// Enqueue lightbox script if enabled and registered.
		if ( $args['lightbox'] && wp_script_is( 'vh360-gallery-photoswipe', 'registered' ) ) {
			wp_enqueue_script( 'vh360-gallery-photoswipe' );
		}

		// Output gallery.
		echo vh360_render_gallery( $gallery_id, $args );
	}

	/**
	 * Render widget output in editor.
	 */
	protected function content_template() {
		?>
		<#
		if ( ! settings.gallery_id ) {
			#>
			<p class="vh360-gallery-notice"><?php esc_html_e( 'Please select a gallery.', 'videohub360-theme' ); ?></p>
			<#
		}
		#>
		<?php
	}
}
