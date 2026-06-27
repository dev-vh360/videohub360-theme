<?php
/** Gallery archive card. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$image_count = vh360_get_gallery_image_count( get_the_ID() );
$cover_url   = get_the_post_thumbnail_url( get_the_ID(), 'vh360-gallery-thumb' );
if ( ! $cover_url ) { $cover_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' ); }
?>
<article id="gallery-<?php the_ID(); ?>" <?php post_class( 'vh360-gallery-card vh360-gallery-archive-card' ); ?>>
	<a href="<?php the_permalink(); ?>" class="vh360-gallery-card-link">
		<div class="vh360-gallery-card-cover">
			<?php if ( $cover_url ) : ?>
				<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" class="vh360-gallery-card-image">
			<?php else : ?>
				<div class="vh360-gallery-card-placeholder" aria-hidden="true"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>
			<?php endif; ?>
			<span class="vh360-gallery-card-count"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg><?php echo esc_html( $image_count ); ?></span>
			<div class="vh360-gallery-card-overlay"></div>
		</div>
		<div class="vh360-gallery-card-info">
			<h2 class="vh360-gallery-card-title"><?php the_title(); ?></h2>
			<div class="vh360-gallery-card-meta"><span class="vh360-gallery-card-author"><?php echo esc_html( get_the_author() ); ?></span><span class="vh360-gallery-card-date"><?php echo esc_html( get_the_date() ); ?></span></div>
		</div>
	</a>
</article>
