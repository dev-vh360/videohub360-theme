<?php
/**
 * Elementor Integration
 *
 * Registers Elementor widgets for the gallery system.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Elementor_Integration
 *
 * Handles Elementor widget registration.
 */
class VH360_Elementor_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var VH360_Elementor_Integration|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return VH360_Elementor_Integration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Check if Elementor is active.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register widget category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// Enqueue widget styles.
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Register widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'vh360-theme',
			array(
				'title' => __( 'VH360 Theme', 'videohub360-theme' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		// Include widget files.
		require_once VH360_THEME_DIR . '/includes/elementor/widgets/class-vh360-gallery-widget.php';
		require_once VH360_THEME_DIR . '/includes/elementor/widgets/class-vh360-gallery-grid-widget.php';
		require_once VH360_THEME_DIR . '/includes/elementor/widgets/class-vh360-posts-widget.php';
		require_once VH360_THEME_DIR . '/includes/elementor/widgets/class-vh360-following-videos-widget.php';

		// Register widgets.
		$widgets_manager->register( new VH360_Gallery_Widget() );
		$widgets_manager->register( new VH360_Gallery_Grid_Widget() );
		$widgets_manager->register( new VH360_Posts_Widget() );
		$widgets_manager->register( new VH360_Following_Videos_Widget() );
	}

	/**
	 * Enqueue widget styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'vh360-gallery' );
		wp_enqueue_style( 'vh360-gallery-dashboard' );

		// Enqueue posts widget styles.
		wp_enqueue_style(
			'vh360-elementor-posts-widget',
			get_template_directory_uri() . '/assets/css/elementor-posts-widget.css',
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
}

// Initialize.
add_action( 'init', array( 'VH360_Elementor_Integration', 'get_instance' ) );
