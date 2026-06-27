<?php
/** Gallery archive results. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$query = isset( $args['query'] ) ? $args['query'] : null;
?>
<div class="vh360-gallery-results-container">
	<div class="vh360-container">
		<?php if ( $query instanceof WP_Query && $query->have_posts() ) : ?>
			<div class="vh360-gallery-archive-grid">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php get_template_part( 'template-parts/gallery/gallery-archive-card' ); ?>
				<?php endwhile; ?>
			</div>
			<?php get_template_part( 'template-parts/gallery/gallery-archive-pagination', null, $args ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/gallery/gallery-archive-empty' ); ?>
		<?php endif; ?>
	</div>
</div>
