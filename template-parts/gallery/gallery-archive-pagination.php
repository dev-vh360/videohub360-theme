<?php
/** Gallery archive pagination. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$query   = isset( $args['query'] ) ? $args['query'] : null;
$filters = isset( $args['filters'] ) ? $args['filters'] : vh360_get_gallery_archive_filters();
$paged   = isset( $args['paged'] ) ? absint( $args['paged'] ) : 1;
if ( ! ( $query instanceof WP_Query ) || $query->max_num_pages <= 1 ) { return; }
$add_args = array_filter( array(
	'gallery_search'   => $filters['search'],
	'gallery_category' => $filters['category'],
	'gallery_tag'      => $filters['tag'],
	'gallery_sort'     => 'date_desc' !== $filters['sort'] ? $filters['sort'] : '',
) );
?>
<nav class="vh360-gallery-pagination" aria-label="<?php esc_attr_e( 'Gallery navigation', 'videohub360-theme' ); ?>">
	<?php echo wp_kses_post( paginate_links( array( 'total' => $query->max_num_pages, 'current' => $paged, 'prev_text' => '&larr; ' . esc_html__( 'Previous', 'videohub360-theme' ), 'next_text' => esc_html__( 'Next', 'videohub360-theme' ) . ' &rarr;', 'type' => 'list', 'add_args' => $add_args ) ) ); ?>
</nav>
