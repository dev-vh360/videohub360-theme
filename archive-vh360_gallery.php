<?php
/**
 * Gallery Archive Template.
 *
 * @package Videohub360_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$vh360_gallery_archive_filters = vh360_get_gallery_archive_filters();
$vh360_gallery_archive_query   = vh360_get_gallery_archive_query( $vh360_gallery_archive_filters );
$vh360_gallery_archive_context = array(
	'query'   => $vh360_gallery_archive_query,
	'filters' => $vh360_gallery_archive_filters,
	'paged'   => max( 1, absint( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) ) ),
);
$vh360_gallery_archive_show_header = get_theme_mod( 'vh360_show_gallery_archive_header', get_theme_mod( 'vh360_gallery_archive_show_header', 1 ) );
$vh360_gallery_archive_classes     = ( is_tax( array( 'vh360_gallery_category', 'vh360_gallery_tag' ) ) || $vh360_gallery_archive_show_header ) ? 'vh360-gallery-archive' : 'vh360-gallery-archive vh360-template-header-off';
?>

<main id="primary" class="<?php echo esc_attr( $vh360_gallery_archive_classes ); ?>">
	<?php get_template_part( 'template-parts/gallery/gallery-archive-header', null, $vh360_gallery_archive_context ); ?>
	<?php get_template_part( 'template-parts/gallery/gallery-archive-controls', null, $vh360_gallery_archive_context ); ?>
	<?php get_template_part( 'template-parts/gallery/gallery-archive-results', null, $vh360_gallery_archive_context ); ?>
</main>

<?php
wp_reset_postdata();
get_footer();
