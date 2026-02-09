<?php
/**
 * Dashboard Gallery Tab
 *
 * Gallery management interface for the frontend dashboard.
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user capabilities before displaying gallery management.
if ( ! is_user_logged_in() ) {
	?>
	<div class="vh360-dashboard-notice">
		<p><?php esc_html_e( 'Please log in to manage galleries.', 'videohub360-theme' ); ?></p>
	</div>
	<?php
	return;
}

$current_user_id = get_current_user_id();

// Check if capabilities class exists before calling static methods.
if ( ! class_exists( 'VH360_Gallery_Capabilities' ) ) {
	?>
	<div class="vh360-dashboard-notice">
		<p><?php esc_html_e( 'Gallery system is not available.', 'videohub360-theme' ); ?></p>
	</div>
	<?php
	return;
}

$can_create = VH360_Gallery_Capabilities::can_create_gallery();

// Verify user has at least edit capability for galleries.
// Note: Using edit_vh360_galleries (primitive cap) instead of read_vh360_gallery (meta cap)
// because meta capabilities require a specific post ID.
if ( ! current_user_can( 'edit_vh360_galleries' ) ) {
	?>
	<div class="vh360-dashboard-notice">
		<p><?php esc_html_e( 'You do not have permission to manage galleries.', 'videohub360-theme' ); ?></p>
	</div>
	<?php
	return;
}

$galleries       = vh360_get_user_galleries( $current_user_id );
$gallery_count   = count( $galleries );
$categories      = vh360_get_gallery_categories();
$settings        = vh360_get_gallery_settings();
?>

<div class="vh360-dashboard-header">
		<h2 class="vh360-dashboard-title"><?php esc_html_e( 'My Galleries', 'videohub360-theme' ); ?></h2>
		<?php if ( $can_create ) : ?>
			<div class="vh360-dashboard-actions">
				<button type="button" class="vh360-dashboard-btn vh360-gallery-create-btn" id="vh360-gallery-create-btn">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="12" y1="5" x2="12" y2="19"></line>
						<line x1="5" y1="12" x2="19" y2="12"></line>
					</svg>
					<?php esc_html_e( 'Create Gallery', 'videohub360-theme' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
	
	<!-- Gallery Stats -->
	<div class="vh360-dashboard-widgets vh360-gallery-stats">
		<div class="vh360-dashboard-widget">
			<div class="vh360-dashboard-widget-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
					<circle cx="8.5" cy="8.5" r="1.5"></circle>
					<polyline points="21 15 16 10 5 21"></polyline>
				</svg>
			</div>
			<div class="vh360-dashboard-widget-label"><?php esc_html_e( 'Total Galleries', 'videohub360-theme' ); ?></div>
			<div class="vh360-dashboard-widget-value"><?php echo esc_html( $gallery_count ); ?></div>
		</div>
		<div class="vh360-dashboard-widget">
			<div class="vh360-dashboard-widget-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="3" width="7" height="7"></rect>
					<rect x="14" y="3" width="7" height="7"></rect>
					<rect x="14" y="14" width="7" height="7"></rect>
					<rect x="3" y="14" width="7" height="7"></rect>
				</svg>
			</div>
			<div class="vh360-dashboard-widget-label"><?php esc_html_e( 'Total Images', 'videohub360-theme' ); ?></div>
			<div class="vh360-dashboard-widget-value" id="vh360-total-images-count">
				<?php
				$total_images = 0;
				foreach ( $galleries as $gallery ) {
					$total_images += vh360_get_gallery_image_count( $gallery->ID );
				}
				echo esc_html( $total_images );
				?>
			</div>
		</div>
	</div>
	
	<!-- Gallery Filters -->
	<div class="vh360-dashboard-filters">
		<div class="vh360-dashboard-filter-tabs">
			<button type="button" class="vh360-dashboard-filter-tab active" data-status="all"><?php esc_html_e( 'All', 'videohub360-theme' ); ?></button>
			<button type="button" class="vh360-dashboard-filter-tab" data-status="publish"><?php esc_html_e( 'Published', 'videohub360-theme' ); ?></button>
			<button type="button" class="vh360-dashboard-filter-tab" data-status="draft"><?php esc_html_e( 'Drafts', 'videohub360-theme' ); ?></button>
		</div>
		<div class="vh360-dashboard-search">
			<input type="text" class="vh360-dashboard-search-input" id="vh360-gallery-search" placeholder="<?php esc_attr_e( 'Search galleries...', 'videohub360-theme' ); ?>">
		</div>
	</div>
	
	<!-- Galleries Grid -->
	<div class="vh360-galleries-grid" id="vh360-galleries-grid">
		<?php if ( empty( $galleries ) ) : ?>
			<div class="vh360-dashboard-empty">
				<div class="vh360-dashboard-empty-icon">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
						<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
						<circle cx="8.5" cy="8.5" r="1.5"></circle>
						<polyline points="21 15 16 10 5 21"></polyline>
					</svg>
				</div>
				<p class="vh360-dashboard-empty-title"><?php esc_html_e( 'No galleries yet', 'videohub360-theme' ); ?></p>
				<p class="vh360-dashboard-empty-text"><?php esc_html_e( 'Create your first gallery to showcase your photos!', 'videohub360-theme' ); ?></p>
				<?php if ( $can_create ) : ?>
					<button type="button" class="vh360-dashboard-btn vh360-gallery-create-btn">
						<?php esc_html_e( 'Create Gallery', 'videohub360-theme' ); ?>
					</button>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<?php foreach ( $galleries as $gallery ) : ?>
				<?php
				$image_count = vh360_get_gallery_image_count( $gallery->ID );
				$cover_url   = get_the_post_thumbnail_url( $gallery->ID, 'medium' );
				$status      = $gallery->post_status;
				$can_edit    = VH360_Gallery_Capabilities::can_edit_gallery( $gallery->ID );
				$can_delete  = VH360_Gallery_Capabilities::can_delete_gallery( $gallery->ID );
				?>
				<div class="vh360-gallery-card" data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
					<div class="vh360-gallery-card-cover">
						<?php if ( $cover_url ) : ?>
							<img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( get_the_title( $gallery->ID ) ); ?>" loading="lazy">
						<?php else : ?>
							<div class="vh360-gallery-card-placeholder">
								<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
									<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
									<circle cx="8.5" cy="8.5" r="1.5"></circle>
									<polyline points="21 15 16 10 5 21"></polyline>
								</svg>
							</div>
						<?php endif; ?>
						<span class="vh360-gallery-card-count">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
								<circle cx="8.5" cy="8.5" r="1.5"></circle>
								<polyline points="21 15 16 10 5 21"></polyline>
							</svg>
							<?php echo esc_html( $image_count ); ?>
						</span>
						<?php if ( 'publish' !== $status ) : ?>
							<span class="vh360-gallery-card-status vh360-gallery-card-status-<?php echo esc_attr( $status ); ?>">
								<?php echo esc_html( ucfirst( $status ) ); ?>
							</span>
						<?php endif; ?>
						<?php if ( $can_edit || $can_delete ) : ?>
							<div class="vh360-gallery-card-actions">
								<?php if ( $can_edit ) : ?>
									<button type="button" class="vh360-gallery-action vh360-gallery-edit" 
											data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>"
											title="<?php esc_attr_e( 'Edit', 'videohub360-theme' ); ?>">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
											<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
										</svg>
									</button>
								<?php endif; ?>
								<a href="<?php echo esc_url( get_permalink( $gallery->ID ) ); ?>" 
								   class="vh360-gallery-action vh360-gallery-view"
								   title="<?php esc_attr_e( 'View', 'videohub360-theme' ); ?>" 
								   target="_blank">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
										<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
										<circle cx="12" cy="12" r="3"></circle>
									</svg>
								</a>
								<?php if ( $can_delete ) : ?>
									<button type="button" class="vh360-gallery-action vh360-gallery-delete" 
											data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>"
											title="<?php esc_attr_e( 'Delete', 'videohub360-theme' ); ?>">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
											<polyline points="3 6 5 6 21 6"></polyline>
											<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
										</svg>
									</button>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="vh360-gallery-card-info">
						<h3 class="vh360-gallery-card-title"><?php echo esc_html( get_the_title( $gallery->ID ) ); ?></h3>
						<span class="vh360-gallery-card-date"><?php echo esc_html( get_the_date( '', $gallery->ID ) ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

<!-- Create/Edit Gallery Modal -->
<div class="vh360-modal-overlay vh360-gallery-modal" id="vh360-gallery-modal">
	<div class="vh360-modal vh360-gallery-modal-content">
		<button type="button" class="vh360-modal-close" aria-label="<?php esc_attr_e( 'Close', 'videohub360-theme' ); ?>">&times;</button>
		
		<div class="vh360-gallery-modal-header">
			<h2 class="vh360-gallery-modal-title" id="vh360-gallery-modal-title"><?php esc_html_e( 'Create Gallery', 'videohub360-theme' ); ?></h2>
		</div>
		
		<form id="vh360-gallery-form" class="vh360-gallery-form">
			<input type="hidden" name="gallery_id" id="vh360-gallery-id" value="0">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vh360_gallery_nonce' ) ); ?>">
			
			<div class="vh360-gallery-form-section">
				<label for="vh360-gallery-title" class="vh360-form-label"><?php esc_html_e( 'Gallery Title', 'videohub360-theme' ); ?> <span class="required">*</span></label>
				<input type="text" id="vh360-gallery-title" name="title" class="vh360-form-input" required>
			</div>
			
			<div class="vh360-gallery-form-section">
				<label for="vh360-gallery-description" class="vh360-form-label"><?php esc_html_e( 'Description', 'videohub360-theme' ); ?></label>
				<textarea id="vh360-gallery-description" name="description" class="vh360-form-textarea" rows="3"></textarea>
			</div>
			
			<div class="vh360-gallery-form-row">
				<div class="vh360-gallery-form-section vh360-gallery-form-half">
					<label for="vh360-gallery-category" class="vh360-form-label"><?php esc_html_e( 'Category', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-category" name="categories[]" class="vh360-form-select">
						<option value=""><?php esc_html_e( 'Select Category', 'videohub360-theme' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="vh360-gallery-form-section vh360-gallery-form-half">
					<label for="vh360-gallery-tags" class="vh360-form-label"><?php esc_html_e( 'Tags', 'videohub360-theme' ); ?></label>
					<input type="text" id="vh360-gallery-tags" name="tags" class="vh360-form-input" placeholder="<?php esc_attr_e( 'tag1, tag2, tag3', 'videohub360-theme' ); ?>">
				</div>
			</div>
			
			<div class="vh360-gallery-form-section">
				<label class="vh360-form-label"><?php esc_html_e( 'Gallery Images', 'videohub360-theme' ); ?></label>
				<div class="vh360-gallery-dropzone" id="vh360-gallery-dropzone">
					<div class="vh360-dropzone-message">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
							<polyline points="17 8 12 3 7 8"></polyline>
							<line x1="12" y1="3" x2="12" y2="15"></line>
						</svg>
						<p><?php esc_html_e( 'Drag & drop images here or click to upload', 'videohub360-theme' ); ?></p>
						<span class="vh360-dropzone-hint">
							<?php
							/* translators: %1$s: max file size, %2$s: allowed file types */
							printf(
								esc_html__( 'Max file size: %1$s MB. Allowed: %2$s', 'videohub360-theme' ),
								esc_html( $settings['max_image_size'] ),
								esc_html( strtoupper( implode( ', ', $settings['allowed_image_types'] ) ) )
							);
							?>
						</span>
					</div>
					<input type="file" id="vh360-gallery-file-input" multiple accept="image/*" style="display: none;">
				</div>
				<p class="vh360-form-hint"><?php esc_html_e( 'Click the star icon on an image to set it as the gallery cover/thumbnail.', 'videohub360-theme' ); ?></p>
				<div class="vh360-gallery-images-preview" id="vh360-gallery-images-preview"></div>
			</div>
			
			<div class="vh360-gallery-form-row">
				<div class="vh360-gallery-form-section vh360-gallery-form-third">
					<label for="vh360-gallery-layout" class="vh360-form-label"><?php esc_html_e( 'Layout', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-layout" name="layout" class="vh360-form-select">
						<option value="grid"><?php esc_html_e( 'Grid', 'videohub360-theme' ); ?></option>
						<option value="masonry"><?php esc_html_e( 'Masonry', 'videohub360-theme' ); ?></option>
						<option value="justified"><?php esc_html_e( 'Justified', 'videohub360-theme' ); ?></option>
					</select>
				</div>
				<div class="vh360-gallery-form-section vh360-gallery-form-third">
					<label for="vh360-gallery-columns" class="vh360-form-label"><?php esc_html_e( 'Columns', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-columns" name="columns" class="vh360-form-select">
						<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['default_columns'], $i ); ?>><?php echo esc_html( $i ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="vh360-gallery-form-section vh360-gallery-form-third">
					<label for="vh360-gallery-status" class="vh360-form-label"><?php esc_html_e( 'Status', 'videohub360-theme' ); ?></label>
					<select id="vh360-gallery-status" name="status" class="vh360-form-select">
						<option value="publish"><?php esc_html_e( 'Published', 'videohub360-theme' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'videohub360-theme' ); ?></option>
					</select>
				</div>
			</div>
			
			<div class="vh360-gallery-form-section">
				<label class="vh360-form-checkbox">
					<input type="checkbox" name="lightbox" id="vh360-gallery-lightbox" value="1" checked>
					<?php esc_html_e( 'Enable Lightbox', 'videohub360-theme' ); ?>
				</label>
			</div>
			
			<div class="vh360-gallery-form-actions">
				<button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-modal-cancel">
					<?php esc_html_e( 'Cancel', 'videohub360-theme' ); ?>
				</button>
				<button type="submit" class="vh360-dashboard-btn" id="vh360-gallery-submit">
					<?php esc_html_e( 'Create Gallery', 'videohub360-theme' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Delete Confirmation Modal -->
<div class="vh360-modal-overlay vh360-gallery-delete-modal" id="vh360-gallery-delete-modal">
	<div class="vh360-modal">
		<div class="vh360-modal-content">
			<p><?php esc_html_e( 'Are you sure you want to delete this gallery? This action cannot be undone.', 'videohub360-theme' ); ?></p>
		</div>
		<div class="vh360-modal-actions">
			<button type="button" class="vh360-dashboard-btn vh360-dashboard-btn-secondary vh360-modal-cancel">
				<?php esc_html_e( 'Cancel', 'videohub360-theme' ); ?>
			</button>
			<button type="button" class="vh360-dashboard-btn vh360-danger-btn" id="vh360-gallery-confirm-delete">
				<?php esc_html_e( 'Delete', 'videohub360-theme' ); ?>
			</button>
		</div>
	</div>
</div>
