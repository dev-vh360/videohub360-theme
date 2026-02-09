<?php
/**
 * App Store Readiness Dashboard Template
 * 
 * @var array $readiness Readiness check results
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="vh360-readiness-dashboard">
	<h2><?php esc_html_e( 'App Store Readiness', 'vh360-pwa-app' ); ?></h2>
	
	<p class="description">
		<?php esc_html_e( 'These checks verify that your PWA has the necessary configuration and assets for app store wrapper creation.', 'vh360-pwa-app' ); ?>
	</p>
	
	<?php if ( isset( $readiness['overall'] ) ) : ?>
		<div class="vh360-readiness-overall">
			<h3>
				<?php esc_html_e( 'Overall Readiness:', 'vh360-pwa-app' ); ?>
				<span class="vh360-readiness-score <?php echo $readiness['overall']['ready'] ? 'ready' : 'not-ready'; ?>">
					<?php echo esc_html( $readiness['overall']['percentage'] ); ?>%
					(<?php echo esc_html( $readiness['overall']['passed'] ); ?>/<?php echo esc_html( $readiness['overall']['total'] ); ?>)
				</span>
			</h3>
			
			<?php if ( $readiness['overall']['ready'] ) : ?>
				<div class="notice notice-success inline">
					<p>
						<strong><?php esc_html_e( 'Great!', 'vh360-pwa-app' ); ?></strong>
						<?php esc_html_e( 'Your PWA is ready for export. Go to the Export tab to download your wrapper packs.', 'vh360-pwa-app' ); ?>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php esc_html_e( 'Action Required:', 'vh360-pwa-app' ); ?></strong>
						<?php esc_html_e( 'Please fix the issues below before exporting.', 'vh360-pwa-app' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if ( isset( $readiness['checks'] ) && is_array( $readiness['checks'] ) ) : ?>
		<div class="vh360-readiness-checks">
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width: 60px;"><?php esc_html_e( 'Status', 'vh360-pwa-app' ); ?></th>
						<th><?php esc_html_e( 'Check', 'vh360-pwa-app' ); ?></th>
						<th><?php esc_html_e( 'Details', 'vh360-pwa-app' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $readiness['checks'] as $check_name => $check ) : ?>
						<tr>
							<td class="vh360-check-status">
								<?php if ( ! empty( $check['passed'] ) ) : ?>
									<span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px;"></span>
								<?php else : ?>
									<span class="dashicons dashicons-dismiss" style="color: #dc3232; font-size: 24px;"></span>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $check_name ) ) ); ?></strong>
							</td>
							<td>
								<?php echo esc_html( $check['message'] ?? '' ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	
	<div class="vh360-readiness-help" style="margin-top: 30px;">
		<h3><?php esc_html_e( 'Need Help?', 'vh360-pwa-app' ); ?></h3>
		<ul>
			<li>
				<strong><?php esc_html_e( 'HTTPS:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Install an SSL certificate for your domain. Most hosting providers offer free Let\'s Encrypt certificates.', 'vh360-pwa-app' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Manifest/Service Worker:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Ensure PWA is enabled on the General tab and the Videohub360 theme is active.', 'vh360-pwa-app' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Icons:', 'vh360-pwa-app' ); ?></strong>
				<?php
				printf(
					/* translators: %s: URL to Icons tab */
					__( 'Use the <a href="%s">Master Icon Generator</a> in the main PWA settings to generate all required icon sizes automatically.', 'vh360-pwa-app' ),
					esc_url( admin_url( 'admin.php?page=vh360-pwa-app&tab=icons' ) )
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'App Config:', 'vh360-pwa-app' ); ?></strong>
				<?php esc_html_e( 'Set an app name and choose "standalone" or "fullscreen" display mode on the General tab.', 'vh360-pwa-app' ); ?>
			</li>
		</ul>
		
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vh360-pwa-app' ) ); ?>" class="button">
				<?php esc_html_e( 'Go to PWA Settings', 'vh360-pwa-app' ); ?>
			</a>
			<button type="button" class="button" onclick="location.reload();">
				<?php esc_html_e( 'Refresh Checks', 'vh360-pwa-app' ); ?>
			</button>
		</p>
	</div>
</div>
