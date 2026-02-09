<?php
/**
 * Elementor Dependency Check
 *
 * Ensures Elementor is active before running Elementor-dependent features.
 * Shows admin notices if Elementor is missing or inactive.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if Elementor is active and loaded.
 *
 * @return bool True if Elementor is active and loaded.
 */
function vh360_is_elementor_active() {
	// Check if Elementor is loaded via action hook.
	if ( did_action( 'elementor/loaded' ) ) {
		return true;
	}

	// Check if Elementor constant is defined.
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		return true;
	}

	// Check if plugin is active via WordPress function (admin context only for performance).
	// Only load plugin.php if it hasn't been loaded yet.
	if ( is_admin() ) {
		// Check if plugin.php is already loaded by looking for one of its functions.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Safely call is_plugin_active (function_exists check prevents fatal errors).
		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( 'elementor/elementor.php' );
		}
	}

	// On frontend, if neither action nor constant exists, Elementor is not loaded.
	return false;
}

/**
 * Display admin notice when Elementor is not active.
 *
 * This notice prompts administrators to install and activate Elementor
 * via the TGMPA install page.
 */
function vh360_elementor_dependency_admin_notice() {
	// Only show to users who can install plugins.
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	// Don't show if Elementor is active.
	if ( vh360_is_elementor_active() ) {
		return;
	}

	// Get TGMPA install page URL.
	$install_url = admin_url( 'themes.php?page=tgmpa-install-plugins' );

	?>
	<div class="notice notice-error">
		<p>
			<strong><?php echo esc_html__( 'VideoHub360 Theme:', 'videohub360-theme' ); ?></strong>
			<?php echo esc_html__( 'This theme requires Elementor to function properly.', 'videohub360-theme' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $install_url ); ?>">
				<?php echo esc_html__( 'Install Required Plugins', 'videohub360-theme' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'vh360_elementor_dependency_admin_notice' );
