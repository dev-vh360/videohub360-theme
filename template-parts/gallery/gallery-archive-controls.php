<?php
/** Gallery archive controls. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$filters    = isset( $args['filters'] ) ? $args['filters'] : vh360_get_gallery_archive_filters();
$query      = isset( $args['query'] ) ? $args['query'] : null;
$categories = vh360_get_gallery_categories( array( 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
$tags       = get_terms( array( 'taxonomy' => 'vh360_gallery_tag', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
$base_url   = vh360_get_gallery_archive_base_url();
?>
<section class="vh360-blog-controls vh360-gallery-controls" aria-label="<?php esc_attr_e( 'Gallery filters', 'videohub360-theme' ); ?>">
	<div class="vh360-container">
		<form class="vh360-gallery-controls-form" method="get" action="<?php echo esc_url( $base_url ); ?>">
			<div class="vh360-blog-controls-row vh360-gallery-controls-row">
				<div class="vh360-blog-search vh360-gallery-search">
					<label class="screen-reader-text" for="vh360-gallery-search"><?php esc_html_e( 'Search galleries', 'videohub360-theme' ); ?></label>
					<input type="search" id="vh360-gallery-search" name="gallery_search" placeholder="<?php esc_attr_e( 'Search galleries...', 'videohub360-theme' ); ?>" class="vh360-blog-search-input vh360-gallery-search-input" value="<?php echo esc_attr( $filters['search'] ); ?>">
				</div>
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
				<div class="vh360-blog-filter vh360-gallery-filter">
					<label for="vh360-gallery-category" class="vh360-blog-filter-label vh360-gallery-filter-label"><?php esc_html_e( 'Category', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-category" name="gallery_category" class="vh360-blog-select vh360-gallery-select">
						<option value=""><?php esc_html_e( 'All Categories', 'videohub360-theme' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $filters['category'], $category->slug ); ?>><?php echo esc_html( $category->name ); ?> (<?php echo absint( $category->count ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
				<div class="vh360-blog-filter vh360-gallery-filter">
					<label for="vh360-gallery-tag" class="vh360-blog-filter-label vh360-gallery-filter-label"><?php esc_html_e( 'Tag', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-tag" name="gallery_tag" class="vh360-blog-select vh360-gallery-select">
						<option value=""><?php esc_html_e( 'All Tags', 'videohub360-theme' ); ?></option>
						<?php foreach ( $tags as $tag ) : ?>
							<option value="<?php echo esc_attr( $tag->slug ); ?>" <?php selected( $filters['tag'], $tag->slug ); ?>><?php echo esc_html( $tag->name ); ?> (<?php echo absint( $tag->count ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
				<div class="vh360-blog-filter vh360-gallery-filter">
					<label for="vh360-gallery-sort" class="vh360-blog-filter-label vh360-gallery-filter-label"><?php esc_html_e( 'Sort By', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-sort" name="gallery_sort" class="vh360-blog-select vh360-gallery-select">
						<option value="date_desc" <?php selected( $filters['sort'], 'date_desc' ); ?>><?php esc_html_e( 'Newest First', 'videohub360-theme' ); ?></option>
						<option value="date_asc" <?php selected( $filters['sort'], 'date_asc' ); ?>><?php esc_html_e( 'Oldest First', 'videohub360-theme' ); ?></option>
						<option value="title_asc" <?php selected( $filters['sort'], 'title_asc' ); ?>><?php esc_html_e( 'Title A–Z', 'videohub360-theme' ); ?></option>
						<option value="title_desc" <?php selected( $filters['sort'], 'title_desc' ); ?>><?php esc_html_e( 'Title Z–A', 'videohub360-theme' ); ?></option>
					</select>
				</div>
			</div>
			<div class="vh360-gallery-controls-actions"><button type="submit" class="vh360-gallery-controls-submit"><?php esc_html_e( 'Apply Filters', 'videohub360-theme' ); ?></button></div>
		</form>
		<?php if ( $query instanceof WP_Query ) : ?>
			<div class="vh360-blog-results-count vh360-gallery-results-count"><?php echo esc_html( sprintf( _n( '%s gallery found', '%s galleries found', (int) $query->found_posts, 'videohub360-theme' ), number_format_i18n( $query->found_posts ) ) ); ?></div>
		<?php endif; ?>
	</div>
</section>
