<?php
/** Gallery archive empty state. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="vh360-gallery-empty">
	<div class="vh360-gallery-empty-icon" aria-hidden="true"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg></div>
	<h2><?php esc_html_e( 'No galleries found.', 'videohub360-theme' ); ?></h2>
	<p><?php esc_html_e( 'Try adjusting your search or filters to find a gallery.', 'videohub360-theme' ); ?></p>
</div>
