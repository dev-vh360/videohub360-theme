<?php
/**
 * App Store Metadata Form Template
 * 
 * @var string $platform Platform identifier (ios or android)
 * @var array  $metadata Current metadata
 * @var array  $categories Available categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$platform_name = 'ios' === $platform ? 'iOS' : 'Android';
$option_name = 'ios' === $platform 
	? VH360_PWA_Store_Metadata::OPTION_IOS 
	: VH360_PWA_Store_Metadata::OPTION_ANDROID;
$settings_group = 'ios' === $platform 
	? 'vh360_pwa_appstore_ios_group' 
	: 'vh360_pwa_appstore_android_group';
?>

<div class="vh360-metadata-form">
	<h2>
		<?php 
		/* translators: %s: Platform name (iOS or Android) */
		echo esc_html( sprintf( __( '%s App Store Metadata', 'vh360-pwa-app' ), $platform_name ) ); 
		?>
	</h2>
	
	<p class="description">
		<?php 
		/* translators: %s: Platform name (iOS or Android) */
		echo esc_html( sprintf( __( 'This metadata will be included in your %s export pack. Use it when filling out your app store listing.', 'vh360-pwa-app' ), $platform_name ) ); 
		?>
	</p>
	
	<form method="post" action="options.php" class="vh360-appstore-metadata-form">
		<?php settings_fields( $settings_group ); ?>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_app_title">
						<?php esc_html_e( 'App Title', 'vh360-pwa-app' ); ?>
						<span class="required">*</span>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="<?php echo esc_attr( $platform ); ?>_app_title"
						name="<?php echo esc_attr( $option_name ); ?>[app_title]" 
						value="<?php echo esc_attr( $metadata['app_title'] ?? '' ); ?>" 
						class="regular-text vh360-char-counter" 
						maxlength="30"
						data-max="30"
						required
					>
					<p class="description">
						<?php esc_html_e( 'Maximum 30 characters. This appears as your app name in the store.', 'vh360-pwa-app' ); ?>
						<span class="char-count">0/30</span>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_short_description">
						<?php esc_html_e( 'Short Description', 'vh360-pwa-app' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="<?php echo esc_attr( $platform ); ?>_short_description"
						name="<?php echo esc_attr( $option_name ); ?>[short_description]" 
						value="<?php echo esc_attr( $metadata['short_description'] ?? '' ); ?>" 
						class="regular-text vh360-char-counter" 
						maxlength="80"
						data-max="80"
					>
					<p class="description">
						<?php esc_html_e( 'Maximum 80 characters. Brief tagline or subtitle.', 'vh360-pwa-app' ); ?>
						<span class="char-count">0/80</span>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_full_description">
						<?php esc_html_e( 'Full Description', 'vh360-pwa-app' ); ?>
					</label>
				</th>
				<td>
					<textarea 
						id="<?php echo esc_attr( $platform ); ?>_full_description"
						name="<?php echo esc_attr( $option_name ); ?>[full_description]" 
						rows="10" 
						class="large-text vh360-char-counter" 
						maxlength="4000"
						data-max="4000"
					><?php echo esc_textarea( $metadata['full_description'] ?? '' ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Maximum 4000 characters. Detailed description for your app store listing.', 'vh360-pwa-app' ); ?>
						<span class="char-count">0/4000</span>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_category">
						<?php esc_html_e( 'Category', 'vh360-pwa-app' ); ?>
					</label>
				</th>
				<td>
					<select 
						id="<?php echo esc_attr( $platform ); ?>_category"
						name="<?php echo esc_attr( $option_name ); ?>[category]"
					>
						<?php foreach ( $categories as $value => $label ) : ?>
							<option 
								value="<?php echo esc_attr( $value ); ?>"
								<?php selected( $metadata['category'] ?? '', $value ); ?>
							>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Select the category that best matches your app.', 'vh360-pwa-app' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_privacy_policy">
						<?php esc_html_e( 'Privacy Policy URL', 'vh360-pwa-app' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="url" 
						id="<?php echo esc_attr( $platform ); ?>_privacy_policy"
						name="<?php echo esc_attr( $option_name ); ?>[privacy_policy]" 
						value="<?php echo esc_attr( $metadata['privacy_policy'] ?? '' ); ?>" 
						class="regular-text"
					>
					<p class="description">
						<?php esc_html_e( 'Link to your privacy policy. Required by most app stores.', 'vh360-pwa-app' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_support_email">
						<?php esc_html_e( 'Support Email', 'vh360-pwa-app' ); ?>
						<span class="required">*</span>
					</label>
				</th>
				<td>
					<input 
						type="email" 
						id="<?php echo esc_attr( $platform ); ?>_support_email"
						name="<?php echo esc_attr( $option_name ); ?>[support_email]" 
						value="<?php echo esc_attr( $metadata['support_email'] ?? '' ); ?>" 
						class="regular-text"
						required
					>
					<p class="description">
						<?php esc_html_e( 'Contact email for user support. Required by app stores.', 'vh360-pwa-app' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $platform ); ?>_keywords">
						<?php esc_html_e( 'Keywords / Tags', 'vh360-pwa-app' ); ?>
					</label>
				</th>
				<td>
					<input 
						type="text" 
						id="<?php echo esc_attr( $platform ); ?>_keywords"
						name="<?php echo esc_attr( $option_name ); ?>[keywords]" 
						value="<?php echo esc_attr( $metadata['keywords'] ?? '' ); ?>" 
						class="regular-text"
					>
					<p class="description">
						<?php esc_html_e( 'Comma-separated keywords for search optimization.', 'vh360-pwa-app' ); ?>
						<?php if ( 'ios' === $platform ) : ?>
							<?php esc_html_e( 'iOS App Store allows up to 100 characters total.', 'vh360-pwa-app' ); ?>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			
			<?php if ( 'ios' === $platform ) : ?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $platform ); ?>_app_store_id">
							<?php esc_html_e( 'App Store ID', 'vh360-pwa-app' ); ?>
						</label>
					</th>
					<td>
						<input 
							type="text" 
							id="<?php echo esc_attr( $platform ); ?>_app_store_id"
							name="<?php echo esc_attr( $option_name ); ?>[app_store_id]" 
							value="<?php echo esc_attr( $metadata['app_store_id'] ?? '' ); ?>" 
							class="regular-text"
							placeholder="1234567890"
						>
						<p class="description">
							<?php esc_html_e( 'Optional. Your app\'s Apple App Store ID (numeric). Used for related_applications in manifest.', 'vh360-pwa-app' ); ?>
						</p>
					</td>
				</tr>
			<?php endif; ?>
			
			<?php if ( 'android' === $platform ) : ?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr( $platform ); ?>_package_name">
							<?php esc_html_e( 'Package Name', 'vh360-pwa-app' ); ?>
						</label>
					</th>
					<td>
						<input 
							type="text" 
							id="<?php echo esc_attr( $platform ); ?>_package_name"
							name="<?php echo esc_attr( $option_name ); ?>[package_name]" 
							value="<?php echo esc_attr( $metadata['package_name'] ?? '' ); ?>" 
							class="regular-text"
							placeholder="com.example.myapp"
						>
						<p class="description">
							<?php esc_html_e( 'Optional. Your app\'s Android package name. Used for related_applications in manifest.', 'vh360-pwa-app' ); ?>
						</p>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		
		<?php submit_button( __( 'Save Metadata', 'vh360-pwa-app' ) ); ?>
	</form>
	
	<div class="vh360-metadata-help" style="margin-top: 30px;">
		<h3><?php esc_html_e( 'Tips', 'vh360-pwa-app' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Fill out all required fields before exporting.', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Use clear, concise descriptions that highlight your app\'s unique value.', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'Character limits are enforced by app stores - stay within the limits shown.', 'vh360-pwa-app' ); ?></li>
			<li><?php esc_html_e( 'This metadata is for export only - it does not affect your live PWA.', 'vh360-pwa-app' ); ?></li>
		</ul>
	</div>
</div>
