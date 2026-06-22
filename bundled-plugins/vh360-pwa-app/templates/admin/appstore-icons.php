<?php
/**
 * Icon Generator Template
 * 
 * @var VH360_PWA_Icon_Generator $icon_generator Icon generator instance
 * @var array $requirements Library requirements status
 * @var array $generated_icons Generated icons
 * @var string|false $master_icon Master icon path
 * @var array $required_sizes Required icon sizes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="vh360-icon-generator">
	<h2><?php esc_html_e( 'Icon Generator', 'vh360-pwa-app' ); ?></h2>
	
	<p class="description">
		<?php esc_html_e( 'Upload a master icon (1024×1024 PNG recommended) and generate all required app icon sizes automatically.', 'vh360-pwa-app' ); ?>
	</p>
	
	<?php if ( ! $requirements['available'] ) : ?>
		<div class="notice notice-error inline">
			<p>
				<strong><?php esc_html_e( 'Error:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Neither Imagick nor GD library is available. Please install one of these PHP extensions to use the icon generator.', 'vh360-pwa-app' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Contact your hosting provider to enable Imagick (preferred) or GD.', 'vh360-pwa-app' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success inline">
			<p>
				<strong><?php esc_html_e( 'Image Library:', 'vh360-pwa-app' ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: Library name (Imagick or GD) */
						__( '%s is available and ready to generate icons.', 'vh360-pwa-app' ),
						ucfirst( $requirements['preferred'] )
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php
	$opts           = function_exists( 'vh360_pwa_get_options' ) ? vh360_pwa_get_options() : array();
	$asset_version  = function_exists( 'vh360_pwa_get_asset_version' ) ? vh360_pwa_get_asset_version( is_array( $opts ) ? $opts : null ) : time();
	$version_url    = static function ( $url ) use ( $asset_version ) {
		return $url ? add_query_arg( 'v', $asset_version, $url ) : '';
	};
	$manifest_icons = function_exists( 'vh360_pwa_get_manifest_icons' ) ? vh360_pwa_get_manifest_icons() : array();
	$apple_icon     = function_exists( 'vh360_pwa_get_apple_touch_icon_url' ) ? vh360_pwa_get_apple_touch_icon_url() : '';
	$has_192        = false;
	$has_512        = false;
	$has_maskable   = false;
	foreach ( $manifest_icons as $manifest_icon ) {
		if ( '192x192' === ( $manifest_icon['sizes'] ?? '' ) && 'any' === ( $manifest_icon['purpose'] ?? '' ) ) {
			$has_192 = true;
		}
		if ( '512x512' === ( $manifest_icon['sizes'] ?? '' ) && 'any' === ( $manifest_icon['purpose'] ?? '' ) ) {
			$has_512 = true;
		}
		if ( '512x512' === ( $manifest_icon['sizes'] ?? '' ) && false !== strpos( (string) ( $manifest_icon['purpose'] ?? '' ), 'maskable' ) ) {
			$has_maskable = true;
		}
	}
	$root_manifest   = trailingslashit( ABSPATH ) . 'vh360-manifest.json';
	$stale_generated = function_exists( 'vh360_pwa_has_stale_generated_icons' ) ? vh360_pwa_has_stale_generated_icons() : false;
	$generated_found    = ( ! empty( $generated_icons['android'] ) || ! empty( $generated_icons['ios'] ) || ! empty( $generated_icons['maskable'] ) ) && ! $stale_generated;
	$stored_master_hash = (string) get_option( 'vh360_pwa_master_icon_hash', '' );
	$master_icon_hash   = $stored_master_hash ? substr( $stored_master_hash, 0, 12 ) : ( ( $master_icon && file_exists( $master_icon ) ) ? substr( hash_file( 'sha256', $master_icon ), 0, 12 ) : '' );
	$master_icon_mtime  = absint( get_option( 'vh360_pwa_master_icon_uploaded_at', 0 ) ) ?: ( ( $master_icon && file_exists( $master_icon ) ) ? filemtime( $master_icon ) : 0 );
	$icons_generated_at = absint( get_option( 'vh360_pwa_icons_generated_at', 0 ) );
	$last_icon_generation = get_option( 'vh360_pwa_last_icon_generation', array() );
	$manifest_url = function_exists( 'vh360_pwa_version_url' ) ? vh360_pwa_version_url( home_url( '/' . VH360_PWA_MANIFEST_SLUG ), is_array( $opts ) ? $opts : null ) : $version_url( home_url( '/' . VH360_PWA_MANIFEST_SLUG ) );
	?>
	<div class="notice <?php echo ( $has_192 && $has_512 ) ? 'notice-success' : 'notice-warning'; ?> inline">
		<p><strong><?php esc_html_e( 'PWA Manifest Icon Status', 'vh360-pwa-app' ); ?></strong></p>
		<ul style="list-style:disc;padding-left:20px;">
			<li><?php echo esc_html( sprintf( __( 'Master icon uploaded: %s', 'vh360-pwa-app' ), ( $master_icon && file_exists( $master_icon ) ) ? __( 'Yes', 'vh360-pwa-app' ) : __( 'No', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Generated icons found: %s', 'vh360-pwa-app' ), $generated_found ? __( 'Yes', 'vh360-pwa-app' ) : __( 'No', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Manifest 192×192 icon: %s', 'vh360-pwa-app' ), $has_192 ? __( 'Ready', 'vh360-pwa-app' ) : __( 'Missing', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Manifest 512×512 icon: %s', 'vh360-pwa-app' ), $has_512 ? __( 'Ready', 'vh360-pwa-app' ) : __( 'Missing', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Maskable 512×512 icon: %s', 'vh360-pwa-app' ), $has_maskable ? __( 'Ready', 'vh360-pwa-app' ) : __( 'Missing', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Apple touch icon: %s', 'vh360-pwa-app' ), $apple_icon ? __( 'Ready', 'vh360-pwa-app' ) : __( 'Missing', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Root manifest file updated: %s', 'vh360-pwa-app' ), file_exists( $root_manifest ) ? __( 'Yes', 'vh360-pwa-app' ) : __( 'No', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'PWA asset version: %s', 'vh360-pwa-app' ), $asset_version ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Master icon hash: %s', 'vh360-pwa-app' ), $master_icon_hash ? $master_icon_hash : __( 'Not available', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Master icon uploaded: %s', 'vh360-pwa-app' ), $master_icon_mtime ? date_i18n( 'Y-m-d H:i:s', $master_icon_mtime ) : __( 'Not available', 'vh360-pwa-app' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Latest icon generation: %s', 'vh360-pwa-app' ), $icons_generated_at ? date_i18n( 'Y-m-d H:i:s', $icons_generated_at ) : __( 'Never', 'vh360-pwa-app' ) ) ); ?></li>
		</ul>
		<?php if ( $stale_generated ) : ?>
			<p><strong><?php esc_html_e( 'Generated icon records exist, but one or more icon files are missing from the uploads directory. Please regenerate icons.', 'vh360-pwa-app' ); ?></strong></p>
		<?php endif; ?>
		<?php if ( ! $has_192 || ! $has_512 ) : ?>
			<p><?php esc_html_e( 'Your PWA manifest does not currently include app icons. Upload a master icon and click Generate All Icons, then save settings.', 'vh360-pwa-app' ); ?></p>
		<?php endif; ?>
	</div>
	
	<!-- Master Icon Upload -->
	<div class="vh360-icon-upload-section" style="margin: 30px 0;">
		<h3><?php esc_html_e( 'Step 1: Upload Master Icon', 'vh360-pwa-app' ); ?></h3>
		
		<?php if ( $master_icon && file_exists( $master_icon ) ) : ?>
			<div class="vh360-master-icon-preview" style="margin: 15px 0;">
				<p><strong><?php esc_html_e( 'Current Master Icon:', 'vh360-pwa-app' ); ?></strong></p>
				<img src="<?php echo esc_url( $version_url( content_url( str_replace( WP_CONTENT_DIR, '', $master_icon ) ) ) ); ?>" alt="Master Icon" style="max-width: 200px; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
				<p class="description"><?php echo esc_html( get_option( 'vh360_pwa_master_icon_basename', basename( $master_icon ) ) ); ?></p>
				<p class="description"><?php echo esc_html( sprintf( __( 'Size: %s bytes', 'vh360-pwa-app' ), (string) absint( get_option( 'vh360_pwa_master_icon_size_bytes', 0 ) ) ) ); ?></p>
			</div>
		<?php endif; ?>
		
		<form method="post" enctype="multipart/form-data" action="">
			<?php wp_nonce_field( 'vh360_pwa_upload_master_icon' ); ?>
			
			<p>
				<input type="file" name="master_icon" accept="image/png,image/jpeg" required <?php disabled( ! $requirements['available'] ); ?>>
			</p>
			
			<p class="description">
				<?php esc_html_e( 'Recommended: 1024×1024 PNG with transparent background for best quality across all sizes.', 'vh360-pwa-app' ); ?>
				<?php esc_html_e( 'Minimum: 512×512 PNG or JPEG.', 'vh360-pwa-app' ); ?>
			</p>
			
			<p>
				<button type="submit" name="vh360_pwa_upload_master_icon" class="button" <?php disabled( ! $requirements['available'] ); ?>>
					<?php esc_html_e( 'Upload Master Icon', 'vh360-pwa-app' ); ?>
				</button>
			</p>
		</form>
	</div>
	
	<!-- Icon Generation -->
	<div class="vh360-icon-generation-section" style="margin: 30px 0;">
		<h3><?php esc_html_e( 'Step 2: Generate All Icon Sizes', 'vh360-pwa-app' ); ?></h3>
		
		<p class="description">
			<?php esc_html_e( 'This will generate all required icon sizes for iOS, Android, and PWA manifests.', 'vh360-pwa-app' ); ?>
		</p>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'vh360_pwa_generate_icons' ); ?>
			
			<p>
				<button type="submit" name="vh360_pwa_generate_icons" class="button button-primary" <?php disabled( ! $master_icon || ! $requirements['available'] ); ?>>
					<?php esc_html_e( 'Generate All Icons', 'vh360-pwa-app' ); ?>
				</button>
			</p>
			
			<?php if ( ! $master_icon ) : ?>
				<p class="description">
					<?php esc_html_e( 'Upload a master icon first to enable icon generation.', 'vh360-pwa-app' ); ?>
				</p>
			<?php endif; ?>
		</form>
	</div>

	<div class="vh360-icon-actions-section" style="margin: 30px 0;">
		<h3><?php esc_html_e( 'Icon Maintenance Tools', 'vh360-pwa-app' ); ?></h3>
		<form method="post" action="" style="display:inline-block;margin-right:10px;">
			<?php wp_nonce_field( 'vh360_pwa_generate_icons' ); ?>
			<button type="submit" name="vh360_pwa_regenerate_icons_assets" class="button button-primary" <?php disabled( ! $master_icon || ! $requirements['available'] ); ?>><?php esc_html_e( 'Regenerate Icons + PWA Assets', 'vh360-pwa-app' ); ?></button>
		</form>
		<form method="post" action="" style="display:inline-block;">
			<?php wp_nonce_field( 'vh360_pwa_clear_generated_icons' ); ?>
			<button type="submit" name="vh360_pwa_clear_generated_icons" class="button"><?php esc_html_e( 'Clear Generated Icons', 'vh360-pwa-app' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( 'Generated files update immediately server-side and admin previews use changing URLs. Existing iOS home-screen icons may still require removing and re-adding the PWA; new installs will use the latest generated icons.', 'vh360-pwa-app' ); ?></p>
		<p class="description"><a href="<?php echo esc_url( $manifest_url ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( $manifest_url ); ?></code></a></p>
	</div>

	<?php if ( is_array( $last_icon_generation ) && ! empty( $last_icon_generation ) ) : ?>
		<div class="vh360-icon-diagnostics" style="margin: 30px 0;">
			<h3><?php esc_html_e( 'Latest Icon Generation Diagnostics', 'vh360-pwa-app' ); ?></h3>
			<pre style="max-width:980px;white-space:pre-wrap;background:#fff;border:1px solid #ccd0d4;padding:12px;"><?php echo esc_html( wp_json_encode( $last_icon_generation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
		</div>
	<?php endif; ?>
	
	<!-- Generated Icons Preview -->
	<?php if ( ! empty( $generated_icons ) ) : ?>
		<div class="vh360-generated-icons-section" style="margin: 30px 0;">
			<h3><?php esc_html_e( 'Generated Icons', 'vh360-pwa-app' ); ?></h3>
			
			<div class="vh360-icon-platform-group">
				<h4><?php esc_html_e( 'iOS Icons', 'vh360-pwa-app' ); ?></h4>
				<?php if ( ! empty( $generated_icons['ios'] ) ) : ?>
					<div class="vh360-icon-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin: 15px 0;">
						<?php foreach ( $generated_icons['ios'] as $size => $filename ) : ?>
							<?php
							$url = $version_url( is_array( $filename ) && ! empty( $filename['url'] ) ? (string) $filename['url'] : $icon_generator->get_upload_url() . '/' . $filename );
							?>
							<div class="vh360-icon-item" style="text-align: center; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
								<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $size ); ?>px" style="max-width: 100%; height: auto;">
								<p class="description" style="margin-top: 5px;"><?php echo esc_html( $size . 'x' . $size ); ?></p>
								<p class="description" style="word-break: break-all;"><code><?php echo esc_html( $url ); ?></code></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No iOS icons generated yet.', 'vh360-pwa-app' ); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="vh360-icon-platform-group" style="margin-top: 30px;">
				<h4><?php esc_html_e( 'Android Icons', 'vh360-pwa-app' ); ?></h4>
				<?php if ( ! empty( $generated_icons['android'] ) ) : ?>
					<div class="vh360-icon-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin: 15px 0;">
						<?php foreach ( $generated_icons['android'] as $size => $filename ) : ?>
							<?php
							$url = $version_url( is_array( $filename ) && ! empty( $filename['url'] ) ? (string) $filename['url'] : $icon_generator->get_upload_url() . '/' . $filename );
							?>
							<div class="vh360-icon-item" style="text-align: center; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
								<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $size ); ?>px" style="max-width: 100%; height: auto;">
								<p class="description" style="margin-top: 5px;"><?php echo esc_html( $size . 'x' . $size ); ?></p>
								<p class="description" style="word-break: break-all;"><code><?php echo esc_html( $url ); ?></code></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No Android icons generated yet.', 'vh360-pwa-app' ); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="vh360-icon-platform-group" style="margin-top: 30px;">
				<h4><?php esc_html_e( 'Maskable Icons (Android Adaptive)', 'vh360-pwa-app' ); ?></h4>
				<?php if ( ! empty( $generated_icons['maskable'] ) ) : ?>
					<div class="vh360-icon-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin: 15px 0;">
						<?php foreach ( $generated_icons['maskable'] as $size => $filename ) : ?>
							<?php
							$url = $version_url( is_array( $filename ) && ! empty( $filename['url'] ) ? (string) $filename['url'] : $icon_generator->get_upload_url() . '/' . $filename );
							?>
							<div class="vh360-icon-item" style="text-align: center; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
								<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $size ); ?>px maskable" style="max-width: 100%; height: auto;">
								<p class="description" style="margin-top: 5px;"><?php echo esc_html( $size . 'x' . $size ); ?> (maskable)</p>
								<p class="description" style="word-break: break-all;"><code><?php echo esc_html( $url ); ?></code></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No maskable icons generated yet.', 'vh360-pwa-app' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	
	<!-- Required Sizes Info -->
	<div class="vh360-required-sizes-info" style="margin: 30px 0;">
		<h3><?php esc_html_e( 'Required Icon Sizes', 'vh360-pwa-app' ); ?></h3>
		
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
			<div>
				<h4><?php esc_html_e( 'iOS', 'vh360-pwa-app' ); ?></h4>
				<ul style="list-style: disc; padding-left: 20px;">
					<?php foreach ( $required_sizes['ios'] as $size ) : ?>
						<li><?php echo esc_html( $size . 'x' . $size ); ?> px</li>
					<?php endforeach; ?>
				</ul>
			</div>
			
			<div>
				<h4><?php esc_html_e( 'Android', 'vh360-pwa-app' ); ?></h4>
				<ul style="list-style: disc; padding-left: 20px;">
					<?php foreach ( $required_sizes['android'] as $size ) : ?>
						<li><?php echo esc_html( $size . 'x' . $size ); ?> px</li>
					<?php endforeach; ?>
				</ul>
			</div>
			
			<div>
				<h4><?php esc_html_e( 'Maskable', 'vh360-pwa-app' ); ?></h4>
				<ul style="list-style: disc; padding-left: 20px;">
					<?php foreach ( $required_sizes['maskable'] as $size ) : ?>
						<li><?php echo esc_html( $size . 'x' . $size ); ?> px</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
	
	<!-- Tips -->
	<div class="vh360-icon-tips" style="margin: 30px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
		<h3><?php esc_html_e( 'Tips for Best Results', 'vh360-pwa-app' ); ?></h3>
		<ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
			<li><?php esc_html_e( 'Use a high-resolution source (1024×1024 or larger) for best quality', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'PNG format with transparent background is recommended', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Center your logo/icon with padding for maskable icons', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Test your icons on actual devices after generation', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Re-generate icons anytime by uploading a new master icon', 'vh360-pwa-app' ); ?></li>
		</ul>
	</div>
</div>
