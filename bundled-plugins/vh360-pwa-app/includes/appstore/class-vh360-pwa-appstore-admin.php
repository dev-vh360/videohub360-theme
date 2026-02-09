<?php
/**
 * App Store Admin Controller
 * 
 * Handles the App Store admin tab, processing form submissions, and coordinating
 * between readiness checks, metadata storage, and export functionality.
 * 
 * This is a read-only dashboard with export functionality - it does NOT build
 * or submit native apps.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_AppStore_Admin {
	
	private $readiness_checker;
	private $metadata_handler;
	private $export_package;
	
	public function __construct() {
		$this->readiness_checker = new VH360_PWA_Readiness_Checker();
		$this->metadata_handler = new VH360_PWA_Store_Metadata();
		$this->export_package = new VH360_PWA_Export_Package();
	}
	
	/**
	 * Register hooks and actions.
	 */
	public function register() : void {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}
	
	/**
	 * Handle form submissions and export actions.
	 */
	public function handle_actions() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Sanitize export action parameter
		$export_action = isset( $_GET['vh360_pwa_export'] ) ? sanitize_key( $_GET['vh360_pwa_export'] ) : '';
		
		// Handle iOS export
		if ( 'ios' === $export_action ) {
			check_admin_referer( 'vh360_pwa_export_ios' );
			$this->export_package->export_ios_pack();
			exit; // export_ios_pack() already exits, but be explicit
		}
		
		// Handle Android export
		if ( 'android' === $export_action ) {
			check_admin_referer( 'vh360_pwa_export_android' );
			$this->export_package->export_android_pack();
			exit; // export_android_pack() already exits, but be explicit
		}
	}
	
	/**
	 * Register settings for metadata forms.
	 */
	public function register_settings() : void {
		// iOS metadata
		register_setting(
			'vh360_pwa_appstore_ios_group',
			VH360_PWA_Store_Metadata::OPTION_IOS,
			array( $this, 'sanitize_ios_metadata' )
		);
		
		// Android metadata
		register_setting(
			'vh360_pwa_appstore_android_group',
			VH360_PWA_Store_Metadata::OPTION_ANDROID,
			array( $this, 'sanitize_android_metadata' )
		);
	}
	
	/**
	 * Sanitize iOS metadata on save.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_ios_metadata( $input ) : array {
		$input = is_array( $input ) ? $input : array();
		
		// Clear readiness check cache when metadata changes
		$this->readiness_checker->clear_cache();
		
		return $this->metadata_handler->save_ios_metadata( $input ) 
			? $this->metadata_handler->get_ios_metadata() 
			: array();
	}
	
	/**
	 * Sanitize Android metadata on save.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_android_metadata( $input ) : array {
		$input = is_array( $input ) ? $input : array();
		
		// Clear readiness check cache when metadata changes
		$this->readiness_checker->clear_cache();
		
		return $this->metadata_handler->save_android_metadata( $input ) 
			? $this->metadata_handler->get_android_metadata() 
			: array();
	}
	
	/**
	 * Render the App Store admin page.
	 */
	public function render_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'readiness';
		
		$tabs = array(
			'readiness' => __( 'Readiness Check', 'vh360-pwa-app' ),
			'ios'       => __( 'iOS Metadata', 'vh360-pwa-app' ),
			'android'   => __( 'Android Metadata', 'vh360-pwa-app' ),
			'export'    => __( 'Export', 'vh360-pwa-app' ),
		);
		
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'readiness';
		}
		
		echo '<div class="wrap vh360-pwa-appstore">';
		echo '<h1>' . esc_html__( 'App Store Export', 'vh360-pwa-app' ) . '</h1>';
		
		// Important notice
		echo '<div class="notice notice-info"><p>';
		echo '<strong>' . esc_html__( 'Important:', 'vh360-pwa-app' ) . '</strong> ';
		echo esc_html__( 'This tool prepares and exports your PWA for app store wrapping. It does NOT build or submit native apps.', 'vh360-pwa-app' );
		echo '</p></div>';
		
		// Tabs
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			$active = ( $k === $tab ) ? ' nav-tab-active' : '';
			$url = esc_url( admin_url( 'admin.php?page=vh360-pwa-app-store&tab=' . $k ) );
			echo '<a class="nav-tab' . $active . '" href="' . $url . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
		
		// Tab content
		echo '<div class="vh360-appstore-content">';
		
		// Display settings errors/success messages
		settings_errors( 'vh360_pwa_appstore' );
		
		switch ( $tab ) {
			case 'ios':
				$this->render_ios_metadata_tab();
				break;
			case 'android':
				$this->render_android_metadata_tab();
				break;
			case 'export':
				$this->render_export_tab();
				break;
			case 'readiness':
			default:
				$this->render_readiness_tab();
				break;
		}
		echo '</div>';
		
		echo '</div>'; // .wrap
	}
	
	/**
	 * Render the readiness check tab.
	 */
	private function render_readiness_tab() : void {
		$template = VH360_PWA_APP_DIR . 'templates/admin/appstore-dashboard.php';
		if ( file_exists( $template ) ) {
			$readiness = $this->readiness_checker->run_checks();
			include $template;
		}
	}
	
	/**
	 * Render the iOS metadata tab.
	 */
	private function render_ios_metadata_tab() : void {
		$template = VH360_PWA_APP_DIR . 'templates/admin/appstore-metadata-form.php';
		if ( file_exists( $template ) ) {
			$platform = 'ios';
			$metadata = $this->metadata_handler->get_ios_metadata();
			$categories = $this->metadata_handler->get_categories();
			include $template;
		}
	}
	
	/**
	 * Render the Android metadata tab.
	 */
	private function render_android_metadata_tab() : void {
		$template = VH360_PWA_APP_DIR . 'templates/admin/appstore-metadata-form.php';
		if ( file_exists( $template ) ) {
			$platform = 'android';
			$metadata = $this->metadata_handler->get_android_metadata();
			$categories = $this->metadata_handler->get_categories();
			include $template;
		}
	}
	
	/**
	 * Render the export tab.
	 */
	private function render_export_tab() : void {
		$template = VH360_PWA_APP_DIR . 'templates/admin/appstore-export.php';
		if ( file_exists( $template ) ) {
			$readiness = $this->readiness_checker->run_checks();
			include $template;
		}
	}
}
