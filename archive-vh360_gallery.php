<?php
/**
 * Gallery Archive Template
 *
 * Displays the archive of all galleries.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$settings        = vh360_get_gallery_settings();
$per_page        = isset( $settings['galleries_per_page'] ) ? absint( $settings['galleries_per_page'] ) : 12;
$categories      = vh360_get_gallery_categories( array( 'hide_empty' => true ) );
$current_cat     = get_query_var( 'vh360_gallery_category', '' );
$paged           = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

// Build query args.
$query_args = array(
	'post_type'      => 'vh360_gallery',
	'post_status'    => 'publish',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

// Filter by category if set.
if ( $current_cat ) {
	$query_args['tax_query'] = array(
		array(
			'taxonomy' => 'vh360_gallery_category',
			'field'    => 'slug',
			'terms'    => $current_cat,
		),
	);
}

$galleries_query = new WP_Query( $query_args );
?>

<div class="vh360-gallery-archive">
	<div class="vh360-gallery-archive-header">
		<div class="vh360-container">
			<h1 class="vh360-gallery-archive-title">
				<?php
				if ( $current_cat ) {
					$term = get_term_by( 'slug', $current_cat, 'vh360_gallery_category' );
					if ( $term ) {
						echo esc_html( $term->name );
					} else {
						esc_html_e( 'Galleries', 'videohub360-theme' );
					}
				} else {
					esc_html_e( 'Galleries', 'videohub360-theme' );
				}
				?>
			</h1>
			<?php if ( ! empty( $categories ) ) : ?>
				<div class="vh360-gallery-archive-filters">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'vh360_gallery' ) ); ?>" 
					   class="vh360-gallery-filter-btn <?php echo empty( $current_cat ) ? 'active' : ''; ?>">
						<?php esc_html_e( 'All', 'videohub360-theme' ); ?>
					</a>
					<?php foreach ( $categories as $category ) : ?>
						<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" 
						   class="vh360-gallery-filter-btn <?php echo $current_cat === $category->slug ? 'active' : ''; ?>">
							<?php echo esc_html( $category->name ); ?>
							<span class="vh360-filter-count">(<?php echo esc_html( $category->count ); ?>)</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="vh360-container">
		<?php if ( $galleries_query->have_posts() ) : ?>
			<div class="vh360-gallery-archive-grid">
				<?php while ( $galleries_query->have_posts() ) : ?>
					<?php $galleries_query->the_post(); ?>
					<?php
					$image_count = vh360_get_gallery_image_count( get_the_ID() );
					$cover_url   = get_the_post_thumbnail_url( get_the_ID(), 'vh360-gallery-thumb' );
					if ( ! $cover_url ) {
						$cover_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
					}
					?>
					<article id="gallery-<?php the_ID(); ?>" <?php post_class( 'vh360-gallery-card' ); ?>>
						<a href="<?php the_permalink(); ?>" class="vh360-gallery-card-link">
							<div class="vh360-gallery-card-cover">
								<?php if ( $cover_url ) : ?>
									<img src="<?php echo esc_url( $cover_url ); ?>" 
										 alt="<?php the_title_attribute(); ?>"
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
								<?php if ( $image_count > 0 ) : ?>
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
							<div class="vh360-gallery-card-info">
								<h2 class="vh360-gallery-card-title"><?php the_title(); ?></h2>
								<div class="vh360-gallery-card-meta">
									<span class="vh360-gallery-card-author">
										<?php echo esc_html( get_the_author() ); ?>
									</span>
									<span class="vh360-gallery-card-date">
										<?php echo esc_html( get_the_date() ); ?>
									</span>
								</div>
							</div>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			// Pagination.
			$total_pages = $galleries_query->max_num_pages;
			if ( $total_pages > 1 ) :
			?>
				<nav class="vh360-gallery-pagination" aria-label="<?php esc_attr_e( 'Gallery navigation', 'videohub360-theme' ); ?>">
					<?php
					echo paginate_links( array(
						'total'     => $total_pages,
						'current'   => $paged,
						'prev_text' => '&larr; ' . __( 'Previous', 'videohub360-theme' ),
						'next_text' => __( 'Next', 'videohub360-theme' ) . ' &rarr;',
						'type'      => 'list',
					) );
					?>
				</nav>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>

		<?php else : ?>
			<div class="vh360-gallery-empty">
				<div class="vh360-gallery-empty-icon">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
						<circle cx="8.5" cy="8.5" r="1.5"></circle>
						<polyline points="21 15 16 10 5 21"></polyline>
					</svg>
				</div>
				<h2><?php esc_html_e( 'No galleries found', 'videohub360-theme' ); ?></h2>
				<p><?php esc_html_e( 'There are no galleries to display yet.', 'videohub360-theme' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
