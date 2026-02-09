<?php
/**
 * App Store Export Template
 * 
 * @var array $readiness Readiness check results
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_ready = isset( $readiness['overall']['ready'] ) && $readiness['overall']['ready'];

// Check for generated icons
$icon_generator = new VH360_PWA_Icon_Generator();
$generated_icons = $icon_generator->get_generated_icons();
$has_generated_icons = ! empty( $generated_icons['ios'] ) || ! empty( $generated_icons['android'] );

// Check requirements
$requirements = $icon_generator->check_requirements();
?>

<div class="vh360-export-tab">
	<h2><?php esc_html_e( 'Export Wrapper Packs', 'vh360-pwa-app' ); ?></h2>
	
	<p class="description">
		<?php esc_html_e( 'Download prepared packages containing your PWA data, metadata, and setup instructions for creating iOS and Android app wrappers.', 'vh360-pwa-app' ); ?>
	</p>
	
	<?php if ( ! class_exists( 'ZipArchive' ) ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Error:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'PHP ZipArchive extension is not available. Export functionality is disabled.', 'vh360-pwa-app' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Please contact your hosting provider to enable the ZipArchive extension.', 'vh360-pwa-app' ); ?>
			</p>
		</div>
	<?php endif; ?>
	
	<?php if ( ! $requirements['available'] ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Warning:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Neither Imagick nor GD library is available. You will not be able to generate icons.', 'vh360-pwa-app' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=icons' ) ); ?>" class="button">
					<?php esc_html_e( 'Go to Icon Generator', 'vh360-pwa-app' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
	
	<?php if ( ! $has_generated_icons ) : ?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Recommendation:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'No icons have been generated yet. For best results, generate a complete icon set before exporting.', 'vh360-pwa-app' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=icons' ) ); ?>" class="button">
					<?php esc_html_e( 'Generate Icons', 'vh360-pwa-app' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
	
	<?php if ( ! $is_ready ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Warning:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Your PWA is not fully ready. Please address the issues on the Readiness Check tab before exporting.', 'vh360-pwa-app' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=readiness' ) ); ?>" class="button">
					<?php esc_html_e( 'View Readiness Check', 'vh360-pwa-app' ); ?>
				</a>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p>
				<strong><?php esc_html_e( 'Ready!', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Your PWA passes all readiness checks and is ready for export.', 'vh360-pwa-app' ); ?>
			</p>
		</div>
	<?php endif; ?>
	
	<div class="vh360-export-packages" style="display: grid; gap: 20px; margin-top: 30px;">
		
		<!-- iOS Export -->
		<div class="vh360-export-card" style="border: 1px solid #ddd; padding: 20px; background: #fff;">
			<h3>
				<span class="dashicons dashicons-smartphone" style="font-size: 24px; width: 24px; height: 24px;"></span>
				<?php esc_html_e( 'iOS Wrapper Pack', 'vh360-pwa-app' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Generates a ZIP file containing:', 'vh360-pwa-app' ); ?></p>
			<ul style="list-style: disc; padding-left: 20px;">
				<li><?php esc_html_e( 'Current manifest.json snapshot', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'All uploaded icons organized by size', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'metadata-ios.json with your configured metadata', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'README-iOS.txt with Capacitor setup instructions', 'vh360-pwa-app' ); ?></li>
			</ul>
			
			<p class="description">
				<strong><?php esc_html_e( 'Requirements:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Apple Developer Program ($99/year), macOS with Xcode', 'vh360-pwa-app' ); ?>
			</p>
			
			<?php if ( ! class_exists( 'ZipArchive' ) ) : ?>
				<p class="notice notice-error inline">
					<?php esc_html_e( 'PHP ZipArchive extension is required but not available. Contact your hosting provider.', 'vh360-pwa-app' ); ?>
				</p>
			<?php else : ?>
				<p style="margin-top: 15px;">
					<a 
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=export&vh360_pwa_export=ios' ), 'vh360_pwa_export_ios' ) ); ?>" 
						class="button button-primary button-large"
						<?php echo ! $is_ready ? 'onclick="return confirm(\'' . esc_js( __( 'Your PWA is not fully ready. Export anyway?', 'vh360-pwa-app' ) ) . '\');"' : ''; ?>
					>
						<span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
						<?php esc_html_e( 'Export iOS Wrapper Pack', 'vh360-pwa-app' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		
		<!-- Android Export -->
		<div class="vh360-export-card" style="border: 1px solid #ddd; padding: 20px; background: #fff;">
			<h3>
				<span class="dashicons dashicons-smartphone" style="font-size: 24px; width: 24px; height: 24px;"></span>
				<?php esc_html_e( 'Android Wrapper Pack', 'vh360-pwa-app' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Generates a ZIP file containing:', 'vh360-pwa-app' ); ?></p>
			<ul style="list-style: disc; padding-left: 20px;">
				<li><?php esc_html_e( 'Current manifest.json snapshot', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'All uploaded icons organized by size', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'metadata-android.json with your configured metadata', 'vh360-pwa-app' ); ?></li>
				<li><?php esc_html_e( 'README-Android.txt with Bubblewrap/TWA setup instructions', 'vh360-pwa-app' ); ?></li>
			</ul>
			
			<p class="description">
				<strong><?php esc_html_e( 'Requirements:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Google Play Console account ($25 one-time), Digital Asset Links verification', 'vh360-pwa-app' ); ?>
			</p>
			
			<?php if ( ! class_exists( 'ZipArchive' ) ) : ?>
				<p class="notice notice-error inline">
					<?php esc_html_e( 'PHP ZipArchive extension is required but not available. Contact your hosting provider.', 'vh360-pwa-app' ); ?>
				</p>
			<?php else : ?>
				<p style="margin-top: 15px;">
					<a 
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=export&vh360_pwa_export=android' ), 'vh360_pwa_export_android' ) ); ?>" 
						class="button button-primary button-large"
						<?php echo ! $is_ready ? 'onclick="return confirm(\'' . esc_js( __( 'Your PWA is not fully ready. Export anyway?', 'vh360-pwa-app' ) ) . '\');"' : ''; ?>
					>
						<span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
						<?php esc_html_e( 'Export Android Wrapper Pack', 'vh360-pwa-app' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="vh360-export-disclaimer" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #dc3232;">
		<h3><?php esc_html_e( 'Important Disclaimers', 'vh360-pwa-app' ); ?></h3>
		<ul style="list-style: disc; padding-left: 20px;">
			<li><?php esc_html_e( 'These exports prepare data only - they do NOT build or submit native apps', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'You must use external tools (Capacitor, PWABuilder, Bubblewrap) to create actual apps', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'App Store/Play Store submission happens entirely outside WordPress', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'There are no guarantees that Apple or Google will approve your app', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'You are responsible for following app store guidelines and policies', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Exports are generated on-demand and not stored permanently', 'vh360-pwa-app' ); ?></li>
		</ul>
		
		<h4 style="margin-top: 20px;"><?php esc_html_e( 'Recommended Tools', 'vh360-pwa-app' ); ?></h4>
		<ul style="list-style: disc; padding-left: 20px;">
			<li>
				<strong><?php esc_html_e( 'PWABuilder:', 'vh360-pwa-app' ); ?></strong>
				<a href="https://www.pwabuilder.com/" target="_blank" rel="noopener">https://www.pwabuilder.com/</a>
				<?php esc_html_e( '(Easiest option for both platforms)', 'vh360-pwa-app' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Capacitor:', 'vh360-pwa-app' ); ?></strong>
				<a href="https://capacitorjs.com/" target="_blank" rel="noopener">https://capacitorjs.com/</a>
				<?php esc_html_e( '(Best for iOS)', 'vh360-pwa-app' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Bubblewrap:', 'vh360-pwa-app' ); ?></strong>
				<a href="https://github.com/GoogleChromeLabs/bubblewrap" target="_blank" rel="noopener">https://github.com/GoogleChromeLabs/bubblewrap</a>
				<?php esc_html_e( '(Official Google TWA tool for Android)', 'vh360-pwa-app' ); ?>
			</li>
		</ul>
	</div>
</div>
