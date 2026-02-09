<?php
/**
 * Single Gallery Template
 *
 * Displays a single gallery with images.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$gallery_id     = get_the_ID();
	$images         = vh360_get_gallery_images( $gallery_id, vh360_get_gallery_image_size( $gallery_id ) );
	$layout         = vh360_get_gallery_layout( $gallery_id );
	$columns        = vh360_get_gallery_columns( $gallery_id );
	$has_lightbox   = vh360_gallery_has_lightbox( $gallery_id );
	$categories     = wp_get_post_terms( $gallery_id, 'vh360_gallery_category' );
	$tags           = wp_get_post_terms( $gallery_id, 'vh360_gallery_tag' );
	$settings       = vh360_get_gallery_settings();

	// Enqueue custom lightbox script if lightbox is enabled.
	if ( $has_lightbox ) {
		wp_enqueue_script( 'vh360-gallery-photoswipe' );
	}
	?>

	<article id="gallery-<?php the_ID(); ?>" <?php post_class( 'vh360-single-gallery' ); ?>>
		
		<header class="vh360-single-gallery-header">
			<div class="vh360-container">
				<nav class="vh360-gallery-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'videohub360-theme' ); ?>">
					<a href="<?php echo esc_url( vh360_get_gallery_archive_url() ); ?>"><?php esc_html_e( 'Galleries', 'videohub360-theme' ); ?></a>
					<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<span class="vh360-breadcrumb-sep">/</span>
						<a href="<?php echo esc_url( get_term_link( $categories[0] ) ); ?>"><?php echo esc_html( $categories[0]->name ); ?></a>
					<?php endif; ?>
					<span class="vh360-breadcrumb-sep">/</span>
					<span class="vh360-breadcrumb-current"><?php the_title(); ?></span>
				</nav>

				<h1 class="vh360-single-gallery-title"><?php the_title(); ?></h1>

				<div class="vh360-single-gallery-meta">
					<div class="vh360-gallery-author">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 40 ); ?>
						<div class="vh360-gallery-author-info">
							<span class="vh360-gallery-author-name"><?php the_author(); ?></span>
							<span class="vh360-gallery-date"><?php echo esc_html( get_the_date() ); ?></span>
						</div>
					</div>
					<div class="vh360-gallery-stats">
						<span class="vh360-gallery-image-count">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
								<circle cx="8.5" cy="8.5" r="1.5"></circle>
								<polyline points="21 15 16 10 5 21"></polyline>
							</svg>
							<?php
							/* translators: %d: number of images */
							printf( esc_html( _n( '%d image', '%d images', count( $images ), 'videohub360-theme' ) ), count( $images ) );
							?>
						</span>
					</div>
				</div>

				<?php if ( has_excerpt() ) : ?>
					<div class="vh360-single-gallery-excerpt">
						<?php the_excerpt(); ?>
					</div>
				<?php endif; ?>
			</div>
		</header>

		<div class="vh360-single-gallery-content">
			<div class="vh360-container">
				<?php if ( ! empty( $images ) ) : ?>
					<?php echo vh360_render_gallery( $gallery_id ); ?>
				<?php else : ?>
					<div class="vh360-gallery-no-images">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
							<circle cx="8.5" cy="8.5" r="1.5"></circle>
							<polyline points="21 15 16 10 5 21"></polyline>
						</svg>
						<p><?php esc_html_e( 'No images have been added to this gallery yet.', 'videohub360-theme' ); ?></p>
					</div>
				<?php endif; ?>

				<?php
				$content = get_the_content();
				if ( ! empty( $content ) ) :
				?>
					<div class="vh360-single-gallery-description">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php if ( ( ! empty( $categories ) && ! is_wp_error( $categories ) ) || ( ! empty( $tags ) && ! is_wp_error( $tags ) ) ) : ?>
					<footer class="vh360-single-gallery-footer">
						<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
							<div class="vh360-gallery-categories">
								<strong><?php esc_html_e( 'Categories:', 'videohub360-theme' ); ?></strong>
								<?php foreach ( $categories as $cat ) : ?>
									<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="vh360-gallery-term">
										<?php echo esc_html( $cat->name ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
							<div class="vh360-gallery-tags">
								<strong><?php esc_html_e( 'Tags:', 'videohub360-theme' ); ?></strong>
								<?php foreach ( $tags as $tag ) : ?>
									<a href="<?php echo esc_url( get_term_link( $tag ) ); ?>" class="vh360-gallery-term">
										#<?php echo esc_html( $tag->name ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</footer>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( $settings['enable_comments'] ) && comments_open() ) : ?>
			<section class="vh360-single-gallery-comments">
				<div class="vh360-container">
					<?php comments_template(); ?>
				</div>
			</section>
		<?php endif; ?>

	</article>

	<?php
	// Related galleries.
	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
		$cat_ids = wp_list_pluck( $categories, 'term_id' );
		$related = get_posts( array(
			'post_type'      => 'vh360_gallery',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'post__not_in'   => array( $gallery_id ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'vh360_gallery_category',
					'field'    => 'term_id',
					'terms'    => $cat_ids,
				),
			),
		) );

		if ( ! empty( $related ) ) :
		?>
			<section class="vh360-related-galleries">
				<div class="vh360-container">
					<h2 class="vh360-related-title"><?php esc_html_e( 'Related Galleries', 'videohub360-theme' ); ?></h2>
					<div class="vh360-related-grid">
						<?php foreach ( $related as $rel_gallery ) : ?>
							<?php
							$rel_cover = get_the_post_thumbnail_url( $rel_gallery->ID, 'medium' );
							$rel_count = vh360_get_gallery_image_count( $rel_gallery->ID );
							?>
							<article class="vh360-gallery-card">
								<a href="<?php echo esc_url( get_permalink( $rel_gallery->ID ) ); ?>" class="vh360-gallery-card-link">
									<div class="vh360-gallery-card-cover">
										<?php if ( $rel_cover ) : ?>
											<img src="<?php echo esc_url( $rel_cover ); ?>" 
												 alt="<?php echo esc_attr( $rel_gallery->post_title ); ?>"
												 loading="lazy">
										<?php else : ?>
											<div class="vh360-gallery-card-placeholder">
												<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
													<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
													<circle cx="8.5" cy="8.5" r="1.5"></circle>
													<polyline points="21 15 16 10 5 21"></polyline>
												</svg>
											</div>
										<?php endif; ?>
										<?php if ( $rel_count > 0 ) : ?>
											<span class="vh360-gallery-card-count">
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
													<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
													<circle cx="8.5" cy="8.5" r="1.5"></circle>
													<polyline points="21 15 16 10 5 21"></polyline>
												</svg>
												<?php echo esc_html( $rel_count ); ?>
											</span>
										<?php endif; ?>
									</div>
									<div class="vh360-gallery-card-info">
										<h3 class="vh360-gallery-card-title"><?php echo esc_html( $rel_gallery->post_title ); ?></h3>
									</div>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php
		endif;
	endif;
	?>

<?php endwhile; ?>

<?php
get_footer();
