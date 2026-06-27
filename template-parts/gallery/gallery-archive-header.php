<?php
/** Gallery archive header. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_tax = is_tax( array( 'vh360_gallery_category', 'vh360_gallery_tag' ) );
if ( ! $is_tax && ! get_theme_mod( 'vh360_gallery_archive_show_header', true ) ) {
	return;
}

$title       = get_theme_mod( 'vh360_gallery_archive_title', __( 'Galleries', 'videohub360-theme' ) );
$description = get_theme_mod( 'vh360_gallery_archive_description', __( 'Browse photo galleries from the community.', 'videohub360-theme' ) );

if ( $is_tax ) {
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
