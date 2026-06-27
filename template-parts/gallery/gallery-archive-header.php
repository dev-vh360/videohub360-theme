<?php
/** Gallery archive header. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_tax       = is_tax( array( 'vh360_gallery_category', 'vh360_gallery_tag' ) );
$queried_term = $is_tax ? get_queried_object() : null;
$filter_cat   = isset( $_GET['gallery_category'] ) ? sanitize_key( wp_unslash( $_GET['gallery_category'] ) ) : '';
$filter_tag   = isset( $_GET['gallery_tag'] ) ? sanitize_key( wp_unslash( $_GET['gallery_tag'] ) ) : '';
$has_search   = isset( $_GET['gallery_search'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['gallery_search'] ) );
$show_term_header = $queried_term instanceof WP_Term && ! $has_search;

if ( $show_term_header && 'vh360_gallery_category' === $queried_term->taxonomy ) {
	$show_term_header = ( '' === $filter_cat || $queried_term->slug === $filter_cat ) && '' === $filter_tag;
} elseif ( $show_term_header && 'vh360_gallery_tag' === $queried_term->taxonomy ) {
	$show_term_header = ( '' === $filter_tag || $queried_term->slug === $filter_tag ) && '' === $filter_cat;
}

if ( ! $is_tax && ! get_theme_mod( 'vh360_gallery_archive_show_header', true ) ) {
	return;
}

$title       = get_theme_mod( 'vh360_gallery_archive_title', __( 'Galleries', 'videohub360-theme' ) );
$description = get_theme_mod( 'vh360_gallery_archive_description', __( 'Browse photo galleries from the community.', 'videohub360-theme' ) );

if ( $show_term_header ) {
	$title       = single_term_title( '', false );
	$description = term_description();
}
?>
<header class="vh360-gallery-archive-header">
	<div class="vh360-container">
		<h1 class="vh360-gallery-archive-title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $description ) : ?>
			<div class="vh360-gallery-archive-description"><?php echo wp_kses_post( wpautop( $description ) ); ?></div>
		<?php endif; ?>
	</div>
</header>
