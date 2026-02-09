<?php
/**
 * Gallery Shortcodes
 *
 * Provides shortcode support for displaying galleries.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VH360_Gallery_Shortcodes
 *
 * Handles gallery shortcode registration and rendering.
 */
class VH360_Gallery_Shortcodes {

	/**
	 * Singleton instance.
	 *
	 * @var VH360_Gallery_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return VH360_Gallery_Shortcodes
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
		add_shortcode( 'vh360_gallery', array( $this, 'render_gallery' ) );
		add_shortcode( 'vh360_gallery_grid', array( $this, 'render_gallery_grid' ) );
	}

	/**
	 * Render single gallery shortcode.
	 *
	 * Usage: [vh360_gallery id="123"]
	 * or [vh360_gallery id="123" layout="masonry" columns="4" lightbox="yes"]
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Gallery HTML.
	 */
	public function render_gallery( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'id'         => 0,
			'layout'     => '',
			'columns'    => '',
			'size'       => '',
			'lightbox'   => '',
			'show_title' => 'no',
			'class'      => '',
		), $atts, 'vh360_gallery' );

		$gallery_id = absint( $atts['id'] );
		if ( ! $gallery_id ) {
			return '<p class="vh360-gallery-error">' . esc_html__( 'Gallery ID is required.', 'videohub360-theme' ) . '</p>';
		}

		$gallery = get_post( $gallery_id );
		if ( ! $gallery || 'vh360_gallery' !== $gallery->post_type ) {
			return '<p class="vh360-gallery-error">' . esc_html__( 'Gallery not found.', 'videohub360-theme' ) . '</p>';
		}

		// Check if gallery is published.
		if ( 'publish' !== $gallery->post_status && ! current_user_can( 'edit_vh360_gallery', $gallery_id ) ) {
			return '<p class="vh360-gallery-error">' . esc_html__( 'This gallery is not available.', 'videohub360-theme' ) . '</p>';
		}

		// Build render arguments.
		$args = array();

		if ( ! empty( $atts['layout'] ) ) {
			$args['layout'] = sanitize_text_field( $atts['layout'] );
		}

		if ( ! empty( $atts['columns'] ) ) {
			$args['columns'] = absint( $atts['columns'] );
		}

		if ( ! empty( $atts['size'] ) ) {
			$args['size'] = sanitize_text_field( $atts['size'] );
		}

		if ( ! empty( $atts['lightbox'] ) ) {
			$args['lightbox'] = in_array( strtolower( $atts['lightbox'] ), array( 'yes', 'true', '1' ), true );
		} else {
			// Default to gallery's lightbox setting if not specified.
			$args['lightbox'] = vh360_gallery_has_lightbox( $gallery_id );
		}

		$args['show_title'] = in_array( strtolower( $atts['show_title'] ), array( 'yes', 'true', '1' ), true );

		// Enqueue necessary scripts.
		$this->enqueue_gallery_assets( $args['lightbox'] );

		$output = vh360_render_gallery( $gallery_id, $args );

		// Wrap in shortcode container with custom class.
		$wrapper_class = 'vh360-gallery-shortcode';
		if ( ! empty( $atts['class'] ) ) {
			$wrapper_class .= ' ' . esc_attr( $atts['class'] );
		}

		return '<div class="' . esc_attr( $wrapper_class ) . '">' . $output . '</div>';
	}

	/**
	 * Render gallery grid shortcode.
	 *
	 * Usage: [vh360_gallery_grid]
	 * or [vh360_gallery_grid count="6" columns="3" category="nature"]
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Gallery grid HTML.
	 */
	public function render_gallery_grid( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'count'       => 6,
			'columns'     => 3,
			'category'    => '',
			'tag'         => '',
			'author'      => '',
			'orderby'     => 'date',
			'order'       => 'DESC',
			'show_title'  => 'yes',
			'show_count'  => 'yes',
			'show_author' => 'no',
			'class'       => '',
		), $atts, 'vh360_gallery_grid' );

		$query_args = array(
			'post_type'      => 'vh360_gallery',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['count'] ),
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
		);

		// Filter by category.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'vh360_gallery_category',
					'field'    => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		// Filter by tag.
		if ( ! empty( $atts['tag'] ) ) {
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array();
			}
			$query_args['tax_query'][] = array(
				'taxonomy' => 'vh360_gallery_tag',
				'field'    => is_numeric( $atts['tag'] ) ? 'term_id' : 'slug',
				'terms'    => $atts['tag'],
			);
		}

		// Filter by author.
		if ( ! empty( $atts['author'] ) ) {
			if ( is_numeric( $atts['author'] ) ) {
				$query_args['author'] = absint( $atts['author'] );
			} else {
				$user = get_user_by( 'login', $atts['author'] );
				if ( $user ) {
					$query_args['author'] = $user->ID;
				}
			}
		}

		$galleries = get_posts( $query_args );

		if ( empty( $galleries ) ) {
			return '<p class="vh360-gallery-empty">' . esc_html__( 'No galleries found.', 'videohub360-theme' ) . '</p>';
		}

		$columns     = absint( $atts['columns'] );
		$show_title  = in_array( strtolower( $atts['show_title'] ), array( 'yes', 'true', '1' ), true );
		$show_count  = in_array( strtolower( $atts['show_count'] ), array( 'yes', 'true', '1' ), true );
		$show_author = in_array( strtolower( $atts['show_author'] ), array( 'yes', 'true', '1' ), true );

		$wrapper_class = 'vh360-gallery-grid-shortcode vh360-gallery-cols-' . esc_attr( $columns );
		if ( ! empty( $atts['class'] ) ) {
			$wrapper_class .= ' ' . esc_attr( $atts['class'] );
		}

		ob_start();
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

		return ob_get_clean();
	}

	/**
	 * Enqueue gallery assets.
	 *
	 * @param bool $enable_lightbox Whether to enqueue lightbox script.
	 */
	private function enqueue_gallery_assets( $enable_lightbox = true ) {
		// Enqueue gallery CSS.
		wp_enqueue_style( 'vh360-gallery' );

		// Enqueue gallery JS.
		wp_enqueue_script( 'vh360-gallery-script' );

		// Enqueue custom lightbox script only if enabled.
		if ( $enable_lightbox ) {
			wp_enqueue_script( 'vh360-gallery-photoswipe' );
		}
	}
}

// Initialize.
VH360_Gallery_Shortcodes::get_instance();
