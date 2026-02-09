<?php
/**
 * Gallery Settings Page
 *
 * @package Videohub360_Theme
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = __( 'Gallery Settings', 'videohub360-theme' );
include VH360_THEME_DIR . '/includes/admin/partials/header.php';

$options = get_option( 'vh360_gallery_options', array() );
$defaults = array(
	'enable_galleries'       => true,
	'enable_frontend_upload' => true,
	'max_images_per_gallery' => 50,
	'max_image_size'         => 5,
	'allowed_image_types'    => array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ),
	'default_layout'         => 'grid',
	'default_columns'        => 3,
	'enable_lightbox'        => true,
	'enable_comments'        => true,
	'galleries_per_page'     => 12,
);
$options = wp_parse_args( $options, $defaults );
?>

<div class="vh360-admin-settings">
	
	<form method="post" action="options.php">
		<?php settings_fields( 'vh360_gallery_settings' ); ?>
		
		<!-- General Settings -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'General Settings', 'videohub360-theme' ); ?></h2>
			<p><?php esc_html_e( 'Configure the main gallery feature settings.', 'videohub360-theme' ); ?></p>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Galleries', 'videohub360-theme' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="vh360_gallery_options[enable_galleries]" value="1" <?php checked( $options['enable_galleries'], true ); ?>>
								<?php esc_html_e( 'Enable the photo gallery feature', 'videohub360-theme' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Disabling this will hide galleries from the frontend but preserve existing data.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Frontend Upload', 'videohub360-theme' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="vh360_gallery_options[enable_frontend_upload]" value="1" <?php checked( $options['enable_frontend_upload'], true ); ?>>
								<?php esc_html_e( 'Allow users to create galleries from the frontend dashboard', 'videohub360-theme' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Comments', 'videohub360-theme' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="vh360_gallery_options[enable_comments]" value="1" <?php checked( $options['enable_comments'], true ); ?>>
								<?php esc_html_e( 'Allow comments on galleries', 'videohub360-theme' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<!-- Upload Settings -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'Upload Settings', 'videohub360-theme' ); ?></h2>
			<p><?php esc_html_e( 'Configure image upload restrictions and limits.', 'videohub360-theme' ); ?></p>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max Images Per Gallery', 'videohub360-theme' ); ?></th>
						<td>
							<input type="number" name="vh360_gallery_options[max_images_per_gallery]" 
								   value="<?php echo esc_attr( $options['max_images_per_gallery'] ); ?>" 
								   min="1" max="500" class="small-text">
							<p class="description"><?php esc_html_e( 'Maximum number of images allowed in a single gallery.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max Image Size', 'videohub360-theme' ); ?></th>
						<td>
							<input type="number" name="vh360_gallery_options[max_image_size]" 
								   value="<?php echo esc_attr( $options['max_image_size'] ); ?>" 
								   min="1" max="50" class="small-text">
							<span><?php esc_html_e( 'MB', 'videohub360-theme' ); ?></span>
							<p class="description"><?php esc_html_e( 'Maximum file size for uploaded images.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Allowed Image Types', 'videohub360-theme' ); ?></th>
						<td>
							<?php
							$all_types = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
							foreach ( $all_types as $type ) :
							?>
								<label style="margin-right: 15px;">
									<input type="checkbox" name="vh360_gallery_options[allowed_image_types][]" 
										   value="<?php echo esc_attr( $type ); ?>" 
										   <?php checked( in_array( $type, $options['allowed_image_types'], true ) ); ?>>
									<?php echo esc_html( strtoupper( $type ) ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'Select which image formats users can upload.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<!-- Display Settings -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'Display Settings', 'videohub360-theme' ); ?></h2>
			<p><?php esc_html_e( 'Configure default display options for galleries.', 'videohub360-theme' ); ?></p>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Layout', 'videohub360-theme' ); ?></th>
						<td>
							<select name="vh360_gallery_options[default_layout]">
								<option value="grid" <?php selected( $options['default_layout'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'videohub360-theme' ); ?></option>
								<option value="masonry" <?php selected( $options['default_layout'], 'masonry' ); ?>><?php esc_html_e( 'Masonry', 'videohub360-theme' ); ?></option>
								<option value="justified" <?php selected( $options['default_layout'], 'justified' ); ?>><?php esc_html_e( 'Justified', 'videohub360-theme' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Default layout for new galleries.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Columns', 'videohub360-theme' ); ?></th>
						<td>
							<select name="vh360_gallery_options[default_columns]">
								<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $options['default_columns'], $i ); ?>><?php echo esc_html( $i ); ?></option>
								<?php endfor; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Default number of columns for new galleries.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Lightbox', 'videohub360-theme' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="vh360_gallery_options[enable_lightbox]" value="1" <?php checked( $options['enable_lightbox'], true ); ?>>
								<?php esc_html_e( 'Enable lightbox for image viewing by default', 'videohub360-theme' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Galleries Per Page', 'videohub360-theme' ); ?></th>
						<td>
							<input type="number" name="vh360_gallery_options[galleries_per_page]" 
								   value="<?php echo esc_attr( $options['galleries_per_page'] ); ?>" 
								   min="1" max="100" class="small-text">
							<p class="description"><?php esc_html_e( 'Number of galleries to show per page on archive.', 'videohub360-theme' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<!-- Shortcode Reference -->
		<div class="vh360-admin-card">
			<h2><?php esc_html_e( 'Shortcode Reference', 'videohub360-theme' ); ?></h2>
			<p><?php esc_html_e( 'Use these shortcodes to display galleries on any page or post.', 'videohub360-theme' ); ?></p>
			
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Shortcode', 'videohub360-theme' ); ?></th>
						<th><?php esc_html_e( 'Description', 'videohub360-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>[vh360_gallery id="123"]</code></td>
						<td><?php esc_html_e( 'Display a single gallery by ID', 'videohub360-theme' ); ?></td>
					</tr>
					<tr>
						<td><code>[vh360_gallery id="123" layout="masonry" columns="4"]</code></td>
						<td><?php esc_html_e( 'Display gallery with custom layout and columns', 'videohub360-theme' ); ?></td>
					</tr>
					<tr>
						<td><code>[vh360_gallery_grid count="6"]</code></td>
						<td><?php esc_html_e( 'Display a grid of recent galleries', 'videohub360-theme' ); ?></td>
					</tr>
					<tr>
						<td><code>[vh360_gallery_grid category="nature" columns="3"]</code></td>
						<td><?php esc_html_e( 'Display galleries from a specific category', 'videohub360-theme' ); ?></td>
					</tr>
				</tbody>
			</table>
			
			<h3 style="margin-top: 20px;"><?php esc_html_e( 'Available Parameters', 'videohub360-theme' ); ?></h3>
			<ul style="list-style: disc; padding-left: 20px;">
				<li><strong>id</strong> - <?php esc_html_e( 'Gallery ID (required for single gallery)', 'videohub360-theme' ); ?></li>
				<li><strong>layout</strong> - <?php esc_html_e( 'grid, masonry, or justified', 'videohub360-theme' ); ?></li>
				<li><strong>columns</strong> - <?php esc_html_e( '1 to 6', 'videohub360-theme' ); ?></li>
				<li><strong>size</strong> - <?php esc_html_e( 'thumbnail, medium, large, or full', 'videohub360-theme' ); ?></li>
				<li><strong>lightbox</strong> - <?php esc_html_e( 'yes or no', 'videohub360-theme' ); ?></li>
				<li><strong>count</strong> - <?php esc_html_e( 'Number of galleries to show (for grid)', 'videohub360-theme' ); ?></li>
				<li><strong>category</strong> - <?php esc_html_e( 'Category slug or ID', 'videohub360-theme' ); ?></li>
				<li><strong>tag</strong> - <?php esc_html_e( 'Tag slug or ID', 'videohub360-theme' ); ?></li>
				<li><strong>author</strong> - <?php esc_html_e( 'Author ID or username', 'videohub360-theme' ); ?></li>
			</ul>
		</div>
		
		<?php submit_button(); ?>
		
	</form>
	
</div>

<?php
include VH360_THEME_DIR . '/includes/admin/partials/footer.php';
