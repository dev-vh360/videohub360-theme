<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Push Notifications Admin
 * 
 * Handles admin UI for push notifications.
 */
class VH360_PWA_Push_Admin {
	/** @var string */
	private $page_slug = 'vh360-pwa-push';

	/** @var VH360_PWA_Push_Manager */
	private $push_manager;

	/**
	 * Constructor
	 */
	public function __construct( VH360_PWA_Push_Manager $push_manager ) {
		$this->push_manager = $push_manager;
	}

	/**
	 * Register hooks
	 */
	public function register() : void {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_vh360_pwa_push_send', array( $this, 'ajax_send_notification' ) );
		add_action( 'wp_ajax_vh360_pwa_push_send_frontend', array( $this, 'ajax_send_notification_frontend' ) );
		add_action( 'wp_ajax_vh360_pwa_push_send_test', array( $this, 'ajax_send_test' ) );
		add_action( 'wp_ajax_vh360_pwa_push_run_validation', array( $this, 'ajax_run_validation' ) );
		// Native push AJAX handlers (Phase 2.2)
		add_action( 'wp_ajax_vh360_test_apns', array( $this, 'ajax_test_apns' ) );
		add_action( 'wp_ajax_vh360_test_fcm', array( $this, 'ajax_test_fcm' ) );
		add_action( 'wp_ajax_vh360_send_test_device', array( $this, 'ajax_send_test_device' ) );
	}

	/**
	 * Add admin menu
	 */
	public function admin_menu() : void {
		add_submenu_page(
			'vh360-pwa-app',
			__( 'Push Notifications', 'vh360-pwa-app' ),
			__( 'Push Notifications', 'vh360-pwa-app' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( string $hook ) : void {
		if ( false === strpos( $hook, $this->page_slug ) ) {
			return;
		}

		wp_enqueue_style(
			'vh360-pwa-push-admin',
			VH360_PWA_APP_URL . 'assets/admin/push-admin.css',
			array(),
			VH360_PWA_APP_VERSION
		);

		wp_enqueue_script(
			'vh360-pwa-push-admin',
			VH360_PWA_APP_URL . 'assets/admin/push-admin.js',
			array( 'jquery' ),
			VH360_PWA_APP_VERSION,
			true
		);

		// Native push admin assets (Phase 2.2)
		wp_enqueue_style(
			'vh360-pwa-push-native-admin',
			VH360_PWA_APP_URL . 'assets/admin/push-native-admin.css',
			array( 'vh360-pwa-push-admin' ),
			VH360_PWA_APP_VERSION
		);

		wp_enqueue_script(
			'vh360-pwa-push-native-admin',
			VH360_PWA_APP_URL . 'assets/admin/push-native-admin.js',
			array( 'jquery', 'vh360-pwa-push-admin' ),
			VH360_PWA_APP_VERSION,
			true
		);

		wp_localize_script(
			'vh360-pwa-push-admin',
			'VH360PushAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vh360_pwa_push_admin' ),
			)
		);
	}

	/**
	 * Handle admin actions
	 */
	public function handle_actions() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save settings
		if ( isset( $_POST['vh360_pwa_push_save_settings'] ) ) {
			check_admin_referer( 'vh360_pwa_push_settings', '_wpnonce_settings' );
			$this->save_settings();
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=setup&saved=1' ) );
			exit;
		}

		// Save event settings
		if ( isset( $_POST['vh360_pwa_push_save_events'] ) ) {
			check_admin_referer( 'vh360_pwa_push_events' );
			$this->save_event_settings();
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=events&saved=1' ) );
			exit;
		}

		// Reset settings
		if ( isset( $_POST['vh360_pwa_push_reset'] ) ) {
			check_admin_referer( 'vh360_pwa_push_reset', '_wpnonce_reset' );
			$this->push_manager->reset_settings();
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=setup&reset=1' ) );
			exit;
		}

		// Flush rewrite rules
		if ( isset( $_POST['vh360_pwa_push_flush_rewrite'] ) ) {
			check_admin_referer( 'vh360_pwa_push_flush_rewrite', '_wpnonce_flush' );
			flush_rewrite_rules( false );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=setup&flushed=1' ) );
			exit;
		}
	
		// Validate configuration
		if ( isset( $_POST['vh360_pwa_push_validate_settings'] ) ) {
			check_admin_referer( 'vh360_pwa_push_settings', '_wpnonce_settings' );
			$errors = $this->push_manager->validate_current_settings();
			$user_id = get_current_user_id();
			set_transient( 'vh360_pwa_push_validation_' . $user_id, $errors, 60 );
			wp_safe_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=setup&validated=1' ) );
			exit;
		}

	}


	/**
	 * Save settings
	 */
	private function save_settings() : void {
		$input = $_POST['vh360_push'] ?? array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$settings = array(
			'mode'            => in_array( $input['mode'] ?? '', array( 'provider', 'native', 'hybrid' ), true ) 
				? sanitize_text_field( $input['mode'] ) 
				: 'provider',
			'active_provider' => sanitize_text_field( $input['active_provider'] ?? 'onesignal' ),
		);

		// Provider settings (OneSignal)
		if ( isset( $input['providers']['onesignal'] ) && is_array( $input['providers']['onesignal'] ) ) {
			$os_input = $input['providers']['onesignal'];
			
			// Get current settings to preserve existing API key if not updating
			$current_settings = $this->push_manager->get_settings();
			$current_api_key = $current_settings['providers']['onesignal']['rest_api_key'] ?? '';
			
			// If API key field is empty and we have a current key, keep it
			$new_api_key = trim( $os_input['rest_api_key'] ?? '' );
			if ( empty( $new_api_key ) && ! empty( $current_api_key ) ) {
				$new_api_key = $current_api_key;
			}
			
			$settings['providers']['onesignal'] = array(
				'app_id'             => sanitize_text_field( $os_input['app_id'] ?? '' ),
				'rest_api_key'       => sanitize_text_field( $new_api_key ),
				'sw_mode'            => 'root_proxy', // Fixed for Phase 1
				'sw_scope'           => '/',
				'default_click_url'  => esc_url_raw( $os_input['default_click_url'] ?? home_url( '/' ) ),
				'default_icon_url'   => esc_url_raw( $os_input['default_icon_url'] ?? '' ),
				'auto_prompt'        => ! empty( $os_input['auto_prompt'] ),
				'auto_prompt_delay'  => isset( $os_input['auto_prompt_delay'] ) ? absint( $os_input['auto_prompt_delay'] ) : 0,
				'auto_prompt_scroll' => ! empty( $os_input['auto_prompt_scroll'] ),
				'auto_prompt_login'  => ! empty( $os_input['auto_prompt_login'] ),
			);
		}

		// Native push settings (Phase 2.2)
		if ( isset( $input['providers']['native'] ) && is_array( $input['providers']['native'] ) ) {
			$native_input = $input['providers']['native'];
			
			// Get current settings to preserve existing encrypted key and server key if not updating
			$current_settings = $this->push_manager->get_settings();
			$current_apns_key = $current_settings['providers']['native']['apns_key_file'] ?? '';
			$current_fcm_key = $current_settings['providers']['native']['fcm_server_key'] ?? '';
			
			// Handle APNs .p8 file upload
			$apns_key_file = $current_apns_key; // Keep current by default
			if ( ! empty( $_FILES['vh360_push_apns_key_file']['tmp_name'] ) ) {
				$file = $_FILES['vh360_push_apns_key_file'];
				
				// Validate file extension
				$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
				if ( $file_ext === 'p8' ) {
					$file_contents = file_get_contents( $file['tmp_name'] );
					
					// Validate PEM format
					if ( strpos( $file_contents, '-----BEGIN PRIVATE KEY-----' ) !== false ) {
						// Validate that it's actually a valid private key
						$test_key = openssl_pkey_get_private( $file_contents );
						if ( $test_key !== false ) {
							openssl_free_key( $test_key );
							
							// Encrypt and store
							$native_adapter = $this->push_manager->get_adapter( 'native' );
							if ( $native_adapter ) {
								$apns_key_file = $native_adapter->encrypt_apns_key( $file_contents );
							}
						}
					}
				}
			}
			
			// Handle FCM server key (password field - keep current if empty)
			$new_fcm_key = trim( $native_input['fcm_server_key'] ?? '' );
			if ( empty( $new_fcm_key ) && ! empty( $current_fcm_key ) ) {
				$new_fcm_key = $current_fcm_key;
			}
			
			$settings['providers']['native'] = array(
				'apns_enabled'     => ! empty( $native_input['apns_enabled'] ),
				'apns_key_id'      => sanitize_text_field( $native_input['apns_key_id'] ?? '' ),
				'apns_team_id'     => sanitize_text_field( $native_input['apns_team_id'] ?? '' ),
				'apns_bundle_id'   => sanitize_text_field( $native_input['apns_bundle_id'] ?? '' ),
				'apns_environment' => in_array( $native_input['apns_environment'] ?? '', array( 'production', 'sandbox' ), true ) 
					? sanitize_text_field( $native_input['apns_environment'] ) 
					: 'production',
				'apns_key_file'    => $apns_key_file,
				'fcm_enabled'      => ! empty( $native_input['fcm_enabled'] ),
				'fcm_project_id'   => sanitize_text_field( $native_input['fcm_project_id'] ?? '' ),
				'fcm_server_key'   => sanitize_text_field( $new_fcm_key ),
				'fcm_sender_id'    => sanitize_text_field( $native_input['fcm_sender_id'] ?? '' ),
			);
		}

		$this->push_manager->update_settings( $settings );
	}

	/**
	 * Save event settings
	 */
	private function save_event_settings() : void {
		$input = $_POST['vh360_push_events'] ?? array();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		// Get events instance
		$events = VH360_PWA_App::instance()->push_events;
		if ( ! $events ) {
			return;
		}

		$settings = array(
			'new_post_enabled' => ! empty( $input['new_post_enabled'] ),
			'new_post_template' => array(
				'title' => sanitize_text_field( $input['new_post_template']['title'] ?? '' ),
				'body'  => sanitize_textarea_field( $input['new_post_template']['body'] ?? '' ),
			),
			'livestream_enabled' => ! empty( $input['livestream_enabled'] ),
			'livestream_template' => array(
				'title' => sanitize_text_field( $input['livestream_template']['title'] ?? '' ),
				'body'  => sanitize_textarea_field( $input['livestream_template']['body'] ?? '' ),
			),
			'comment_enabled' => ! empty( $input['comment_enabled'] ),
			'comment_template' => array(
				'title' => sanitize_text_field( $input['comment_template']['title'] ?? '' ),
				'body'  => sanitize_textarea_field( $input['comment_template']['body'] ?? '' ),
			),
			'post_types' => array_map( 'sanitize_text_field', (array) ( $input['post_types'] ?? array( 'post' ) ) ),
		);

		$events->update_settings( $settings );
	}

	/**
	 * Render admin page
	 */
	public function render_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'setup';
		$tabs = array(
			'setup'       => __( 'Setup', 'vh360-pwa-app' ),
			'diagnostics' => __( 'Diagnostics', 'vh360-pwa-app' ),
			'send'        => __( 'Send', 'vh360-pwa-app' ),
			'events'      => __( 'Events', 'vh360-pwa-app' ),
		);

		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'setup';
		}

		echo '<div class="wrap vh360-pwa-push-admin">';
		echo '<h1>' . esc_html__( 'Push Notifications', 'vh360-pwa-app' ) . '</h1>';

		// Show status messages
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'vh360-pwa-app' ) . '</p></div>';
		}
		if ( isset( $_GET['reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset successfully.', 'vh360-pwa-app' ) . '</p></div>';
		}
		if ( isset( $_GET['flushed'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rewrite rules flushed successfully.', 'vh360-pwa-app' ) . '</p></div>';
		}

		if ( isset( $_GET['validated'] ) ) {
			$user_id = get_current_user_id();
			$errors = get_transient( 'vh360_pwa_push_validation_' . $user_id );
			delete_transient( 'vh360_pwa_push_validation_' . $user_id );
			if ( empty( $errors ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configuration is valid.', 'vh360-pwa-app' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Configuration has issues:', 'vh360-pwa-app' ) . '</strong></p><ul style="margin-left:20px;">';
				foreach ( (array) $errors as $err ) {
					echo '<li>' . esc_html( $err ) . '</li>';
				}
				echo '</ul></div>';
			}
		}

		// Render status summary dashboard
		$this->render_status_summary();

		// Render tabs
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			$active = ( $k === $tab ) ? ' nav-tab-active' : '';
			$url = esc_url( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=' . $k ) );
			echo '<a class="nav-tab' . $active . '" href="' . $url . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';

		echo '<div class="vh360-pwa-push-tab-content">';
		switch ( $tab ) {
			case 'setup':
				$this->render_tab_setup();
				break;
			case 'diagnostics':
				$this->render_tab_diagnostics();
				break;
			case 'send':
				$this->render_tab_send();
				break;
			case 'events':
				$this->render_tab_events();
				break;
		}
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render status summary dashboard
	 */
	private function render_status_summary() : void {
		$settings = $this->push_manager->get_settings();
		$validation_errors = $this->push_manager->validate_current_settings();
		
		// Determine overall status
		$has_critical_errors = false;
		$has_warnings = false;
		
		// Check configuration
		$config_status = empty( $validation_errors );
		if ( ! $config_status ) {
			$has_critical_errors = true;
		}
		
		// Check service worker accessibility
		$sw_worker_url = home_url( '/OneSignalSDKWorker.js' );
		$sw_response = wp_remote_get( $sw_worker_url, array( 'timeout' => 10 ) );
		$sw_status = ! is_wp_error( $sw_response ) && 200 === wp_remote_retrieve_response_code( $sw_response );
		if ( ! $sw_status ) {
			$has_critical_errors = true;
		}
		
		// Check HTTPS
		if ( ! is_ssl() ) {
			$has_warnings = true;
		}
		
		// Get subscriber count
		$subscriber_count = 0;
		$subscriber_error = null;
		if ( $config_status ) {
			$adapter = $this->push_manager->get_adapter( 'onesignal' );
			if ( $adapter && method_exists( $adapter, 'get_subscriber_count' ) ) {
				$onesignal_settings = $settings['providers']['onesignal'] ?? array();
				$count_result = $adapter->get_subscriber_count( $onesignal_settings );
				if ( ! empty( $count_result['success'] ) ) {
					$subscriber_count = $count_result['count'] ?? 0;
				} else {
					$subscriber_error = $count_result['error'] ?? __( 'Unknown error', 'vh360-pwa-app' );
				}
			}
		}
		
		// Determine border color
		$border_color = '#46b450'; // Green
		if ( $has_critical_errors ) {
			$border_color = '#dc3232'; // Red
		} elseif ( $has_warnings ) {
			$border_color = '#f39c12'; // Orange
		}
		
		echo '<div class="vh360-push-status-summary" style="background: #fff; border-left: 4px solid ' . esc_attr( $border_color ) . '; padding: 15px 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
		echo '<h2 style="margin-top: 0; font-size: 18px;">' . esc_html__( 'System Status', 'vh360-pwa-app' ) . '</h2>';
		
		echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
		
		// Configuration Status
		echo '<div>';
		echo '<strong>' . esc_html__( 'Configuration', 'vh360-pwa-app' ) . ':</strong> ';
		if ( $config_status ) {
			echo '<span class="vh360-status-ok">✅ ' . esc_html__( 'Valid', 'vh360-pwa-app' ) . '</span>';
		} else {
			echo '<span class="vh360-status-error">❌ ' . esc_html__( 'Invalid', 'vh360-pwa-app' ) . '</span>';
		}
		echo '</div>';
		
		// Service Worker Status
		echo '<div>';
		echo '<strong>' . esc_html__( 'Service Worker', 'vh360-pwa-app' ) . ':</strong> ';
		if ( $sw_status ) {
			echo '<span class="vh360-status-ok">✅ ' . esc_html__( 'Ready', 'vh360-pwa-app' ) . '</span>';
		} else {
			echo '<span class="vh360-status-error">❌ ' . esc_html__( 'Not Found', 'vh360-pwa-app' ) . '</span>';
		}
		echo '</div>';
		
		// SDK Status
		echo '<div>';
		echo '<strong>' . esc_html__( 'SDK Status', 'vh360-pwa-app' ) . ':</strong> ';
		if ( $config_status ) {
			echo '<span class="vh360-status-ok">✅ ' . esc_html__( 'Loaded', 'vh360-pwa-app' ) . '</span>';
		} else {
			echo '<span class="vh360-status-info">⏳ ' . esc_html__( 'Pending Config', 'vh360-pwa-app' ) . '</span>';
		}
		echo '</div>';
		
		// Subscriber Count
		echo '<div>';
		echo '<strong>' . esc_html__( 'Subscribers', 'vh360-pwa-app' ) . ':</strong> ';
		if ( $subscriber_error ) {
			echo '<span class="vh360-status-info">ℹ️ ' . esc_html__( 'N/A', 'vh360-pwa-app' ) . '</span>';
		} else {
			echo '<span>👥 ' . esc_html( number_format_i18n( $subscriber_count ) ) . '</span>';
		}
		echo '</div>';
		
		echo '</div>';
		
		// Show critical errors
		if ( $has_critical_errors && ! empty( $validation_errors ) ) {
			echo '<div style="margin-top: 10px; padding: 10px; background: #fff8dc; border-left: 3px solid #f39c12;">';
			echo '<strong>' . esc_html__( 'Issues:', 'vh360-pwa-app' ) . '</strong>';
			echo '<ul style="margin: 5px 0 0 20px;">';
			foreach ( $validation_errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul>';
			echo '</div>';
		}
		
		echo '</div>';
	}

	/**
	 * Render validation checklist
	 */
	private function render_validation_checklist() : void {
		echo '<div class="vh360-validation-checklist">';
		echo '<h2>' . esc_html__( 'Production Validation Checklist', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'Verify your push notification setup is ready for production:', 'vh360-pwa-app' ) . '</p>';

		// Get validation results
		$validation_results = get_option( 'vh360_pwa_push_validation_results', array() );
		$last_validated = get_option( 'vh360_pwa_push_last_validation', 0 );

		// Define checklist items
		$checklist_items = array(
			'sw_worker_js' => array(
				'label' => __( 'OneSignalSDKWorker.js returns 200 and contains JavaScript', 'vh360-pwa-app' ),
				'test'  => 'check_sw_worker',
			),
			'sw_updater_js' => array(
				'label' => __( 'OneSignalSDKUpdaterWorker.js returns 200 and contains JavaScript', 'vh360-pwa-app' ),
				'test'  => 'check_sw_updater',
			),
			'https_enabled' => array(
				'label' => __( 'HTTPS enabled', 'vh360-pwa-app' ),
				'test'  => 'check_https',
			),
			'sw_scope_root' => array(
				'label' => __( 'Service worker scope is root (/)', 'vh360-pwa-app' ),
				'test'  => 'check_sw_scope',
			),
			'test_subscription' => array(
				'label' => __( 'Test subscription successful', 'vh360-pwa-app' ),
				'test'  => 'manual',
			),
			'test_push_received' => array(
				'label' => __( 'Test push received', 'vh360-pwa-app' ),
				'test'  => 'manual',
			),
			'live_send_success' => array(
				'label' => __( 'Live send successful', 'vh360-pwa-app' ),
				'test'  => 'manual',
			),
			'no_cdn_blocking' => array(
				'label' => __( 'No CDN/cache blocking detected', 'vh360-pwa-app' ),
				'test'  => 'check_cdn',
			),
		);

		// Display checklist
		echo '<div id="vh360-validation-checklist-items">';
		foreach ( $checklist_items as $key => $item ) {
			$result = $validation_results[ $key ] ?? null;
			$status = 'pending';
			$icon = '⏳';
			$details = '';

			if ( null !== $result ) {
				if ( $result['passed'] ) {
					$status = 'passed';
					$icon = '✅';
				} else {
					$status = 'failed';
					$icon = '❌';
					$details = $result['error'] ?? '';
				}
			}

			echo '<div class="vh360-validation-item ' . esc_attr( $status ) . '" data-item="' . esc_attr( $key ) . '">';
			echo '<span class="vh360-validation-icon">' . $icon . '</span>';
			echo '<div style="flex: 1;">';
			echo '<strong>' . esc_html( $item['label'] ) . '</strong>';
			if ( $details ) {
				echo '<div class="vh360-validation-details">' . esc_html( $details ) . '</div>';
			}
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';

		// Show last validation time
		if ( $last_validated ) {
			echo '<p style="color: #666; font-size: 13px;">';
			echo esc_html__( 'Last validated:', 'vh360-pwa-app' ) . ' ';
			echo esc_html( human_time_diff( $last_validated, current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'vh360-pwa-app' );
			echo '</p>';
		}

		// Run validation button
		echo '<p>';
		echo '<button type="button" class="button button-primary" id="vh360_push_run_validation">' . esc_html__( 'Run Full Validation', 'vh360-pwa-app' ) . '</button> ';
		echo '<span id="vh360-validation-status" style="margin-left: 10px;"></span>';
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Render Setup tab
	 */
	private function render_tab_setup() : void {
		$settings = $this->push_manager->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$active_provider = $settings['active_provider'] ?? 'onesignal';
		$onesignal_settings = $settings['providers']['onesignal'] ?? array();

		$adapter = $this->push_manager->get_adapter( 'onesignal' );
		if ( ! $adapter ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'OneSignal adapter is not available.', 'vh360-pwa-app' ) . '</p></div>';
			return;
		}

		echo '<form method="post" action="" enctype="multipart/form-data">';
		wp_nonce_field( 'vh360_pwa_push_settings', '_wpnonce_settings' );

		echo '<h2>' . esc_html__( 'Configuration', 'vh360-pwa-app' ) . '</h2>';

		echo '<table class="form-table" role="presentation">';

		// Mode selector
		echo '<tr><th scope="row">' . esc_html__( 'Push Mode', 'vh360-pwa-app' ) . '</th><td>';
		echo '<select name="vh360_push[mode]" id="vh360_push_mode">';
		echo '<option value="provider"' . selected( $mode, 'provider', false ) . '>' . esc_html__( 'Provider (Web Push)', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="native"' . selected( $mode, 'native', false ) . '>' . esc_html__( 'Native (Mobile Apps)', 'vh360-pwa-app' ) . '</option>';
		echo '<option value="hybrid"' . selected( $mode, 'hybrid', false ) . '>' . esc_html__( 'Hybrid (Web + Mobile)', 'vh360-pwa-app' ) . '</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Provider mode uses OneSignal Web Push. Native mode uses APNs/FCM for mobile apps. Hybrid mode uses both.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		// Active provider
		echo '<tr><th scope="row">' . esc_html__( 'Provider', 'vh360-pwa-app' ) . '</th><td>';
		echo '<select name="vh360_push[active_provider]">';
		echo '<option value="onesignal"' . selected( $active_provider, 'onesignal', false ) . '>' . esc_html( $adapter->get_label() ) . '</option>';
		echo '</select>';
		echo '</td></tr>';

		echo '</table>';

		echo '<h2>' . esc_html__( 'OneSignal Settings', 'vh360-pwa-app' ) . '</h2>';
		
		// Help text
		echo '<div class="vh360-help-box" style="background:#f0f0f1;padding:15px;margin:15px 0;border-left:4px solid #2271b1;">';
		echo '<h4 style="margin-top:0;">' . esc_html__( 'How to get these values:', 'vh360-pwa-app' ) . '</h4>';
		echo '<p>' . esc_html__( 'Log in to your OneSignal dashboard, then go to Settings → Keys & IDs. You\'ll find your App ID and REST API Key there.', 'vh360-pwa-app' ) . '</p>';
		echo '<p><a href="https://documentation.onesignal.com/docs/keys-and-ids" target="_blank" rel="noopener">' . esc_html__( 'View OneSignal Keys & IDs Documentation', 'vh360-pwa-app' ) . '</a></p>';
		echo '</div>';

		echo '<table class="form-table" role="presentation">';

		// Render OneSignal fields
		$fields = $adapter->get_settings_fields();
		foreach ( $fields as $field ) {
			$key = $field['key'];
			$value = $onesignal_settings[ $key ] ?? '';
			$required = ! empty( $field['required'] ) ? ' <span class="required">*</span>' : '';

			echo '<tr><th scope="row">' . esc_html( $field['label'] ) . $required . '</th><td>';

			switch ( $field['type'] ) {
				case 'password':
					// For password fields, don't expose the actual value
					// Show placeholder if value exists, allow updating only
					if ( $value ) {
						echo '<input type="password" class="regular-text" name="vh360_push[providers][onesignal][' . esc_attr( $key ) . ']" placeholder="' . esc_attr( str_repeat( '•', 40 ) ) . '">';
						echo '<p class="description">' . esc_html__( 'Leave blank to keep current value, or enter a new key to update.', 'vh360-pwa-app' ) . '</p>';
											echo '<p class=\"description\"><span class=\"vh360-status-ok\">✓</span> ' . esc_html__( 'Key is set (hidden).', 'vh360-pwa-app' ) . '</p>';
} else {
						echo '<input type="password" class="regular-text" name="vh360_push[providers][onesignal][' . esc_attr( $key ) . ']" value="">';
					}
					break;
				case 'checkbox':
					echo '<label><input type="checkbox" name="vh360_push[providers][onesignal][' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $value ), true, false ) . '> ' . esc_html__( 'Enable', 'vh360-pwa-app' ) . '</label>';
					break;
				case 'number':
					echo '<input type="number" class="small-text" name="vh360_push[providers][onesignal][' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" min="0">';
					break;
				case 'text':
				default:
					echo '<input type="text" class="regular-text" name="vh360_push[providers][onesignal][' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
					break;
			}

			if ( ! empty( $field['description'] ) ) {
				echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}

			echo '</td></tr>';
		}



		// Service Worker mode (fixed)
		echo '<tr><th scope="row">' . esc_html__( 'Service Worker Delivery', 'vh360-pwa-app' ) . '</th><td>';
		echo '<strong>' . esc_html__( 'Managed by plugin', 'vh360-pwa-app' ) . '</strong>';
		echo '<p class="description">' . esc_html__( 'The plugin automatically serves OneSignal service worker files at the site root. No manual file upload required.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';

		// Native Push Settings Section (Phase 2.2)
		$native_adapter = $this->push_manager->get_adapter( 'native' );
		$native_settings = $settings['providers']['native'] ?? array();
		
		if ( $native_adapter ) {
			echo '<hr style="margin: 40px 0;">';
			echo '<h2>' . esc_html__( 'Native Push Settings (APNs + FCM)', 'vh360-pwa-app' ) . '</h2>';
			
			// Help text
			echo '<div class="vh360-help-box" style="background:#f0f0f1;padding:15px;margin:15px 0;border-left:4px solid #2271b1;">';
			echo '<h4 style="margin-top:0;">' . esc_html__( 'How to get these credentials:', 'vh360-pwa-app' ) . '</h4>';
			echo '<p><strong>APNs (iOS):</strong> ' . esc_html__( 'Go to Apple Developer Portal → Certificates, Identifiers & Profiles → Keys. Create a new key with APNs enabled, download the .p8 file, and note your Key ID and Team ID.', 'vh360-pwa-app' ) . '</p>';
			echo '<p><strong>FCM (Android):</strong> ' . esc_html__( 'Go to Firebase Console → Project Settings → Cloud Messaging. Find your Legacy Server Key and Sender ID.', 'vh360-pwa-app' ) . '</p>';
			echo '</div>';
			
			echo '<h3>' . esc_html__( 'Apple Push Notification Service (APNs)', 'vh360-pwa-app' ) . '</h3>';
			echo '<table class="form-table" role="presentation">';
			
			// APNs Enabled
			echo '<tr><th scope="row">' . esc_html__( 'Enable APNs (iOS)', 'vh360-pwa-app' ) . '</th><td>';
			echo '<label><input type="checkbox" name="vh360_push[providers][native][apns_enabled]" value="1"' . checked( ! empty( $native_settings['apns_enabled'] ), true, false ) . '> ' . esc_html__( 'Enable iOS push notifications', 'vh360-pwa-app' ) . '</label>';
			echo '</td></tr>';
			
			// APNs Key ID
			echo '<tr><th scope="row">' . esc_html__( 'APNs Key ID', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" class="regular-text" name="vh360_push[providers][native][apns_key_id]" value="' . esc_attr( $native_settings['apns_key_id'] ?? '' ) . '">';
			echo '<p class="description">' . esc_html__( '10-character identifier for your APNs key.', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// APNs Team ID
			echo '<tr><th scope="row">' . esc_html__( 'APNs Team ID', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" class="regular-text" name="vh360_push[providers][native][apns_team_id]" value="' . esc_attr( $native_settings['apns_team_id'] ?? '' ) . '">';
			echo '<p class="description">' . esc_html__( 'Your Apple Developer Team ID.', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// App Bundle ID
			echo '<tr><th scope="row">' . esc_html__( 'App Bundle ID', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" class="regular-text" name="vh360_push[providers][native][apns_bundle_id]" value="' . esc_attr( $native_settings['apns_bundle_id'] ?? '' ) . '">';
			echo '<p class="description">' . esc_html__( 'Your iOS app bundle identifier (e.g., com.example.app).', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// APNs Environment
			echo '<tr><th scope="row">' . esc_html__( 'APNs Environment', 'vh360-pwa-app' ) . '</th><td>';
			echo '<select name="vh360_push[providers][native][apns_environment]">';
			echo '<option value="production"' . selected( $native_settings['apns_environment'] ?? 'production', 'production', false ) . '>' . esc_html__( 'Production', 'vh360-pwa-app' ) . '</option>';
			echo '<option value="sandbox"' . selected( $native_settings['apns_environment'] ?? 'production', 'sandbox', false ) . '>' . esc_html__( 'Sandbox', 'vh360-pwa-app' ) . '</option>';
			echo '</select>';
			echo '<p class="description">' . esc_html__( 'Use sandbox for development builds, production for App Store builds.', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// APNs Key File
			echo '<tr><th scope="row">' . esc_html__( 'APNs Private Key (.p8)', 'vh360-pwa-app' ) . '</th><td>';
			if ( ! empty( $native_settings['apns_key_file'] ) ) {
				echo '<p class="description"><span class="vh360-status-ok">✓</span> ' . esc_html__( 'Key uploaded (encrypted and stored securely)', 'vh360-pwa-app' ) . '</p>';
				echo '<p><input type="file" name="vh360_push_apns_key_file" accept=".p8"></p>';
				echo '<p class="description">' . esc_html__( 'Upload a new .p8 file to replace the existing key.', 'vh360-pwa-app' ) . '</p>';
			} else {
				echo '<input type="file" name="vh360_push_apns_key_file" accept=".p8">';
				echo '<p class="description">' . esc_html__( 'Upload your APNs .p8 private key file. The key will be encrypted and stored securely.', 'vh360-pwa-app' ) . '</p>';
			}
			echo '</td></tr>';
			
			// Test APNs Button
			echo '<tr><th scope="row">' . esc_html__( 'Test APNs Connection', 'vh360-pwa-app' ) . '</th><td>';
			echo '<button type="button" class="button" id="vh360_test_apns">' . esc_html__( 'Test APNs Connection', 'vh360-pwa-app' ) . '</button>';
			echo '<div id="vh360_apns_test_result" style="margin-top: 10px;"></div>';
			echo '</td></tr>';
			
			echo '</table>';
			
			echo '<h3>' . esc_html__( 'Firebase Cloud Messaging (FCM)', 'vh360-pwa-app' ) . '</h3>';
			echo '<table class="form-table" role="presentation">';
			
			// FCM Enabled
			echo '<tr><th scope="row">' . esc_html__( 'Enable FCM (Android)', 'vh360-pwa-app' ) . '</th><td>';
			echo '<label><input type="checkbox" name="vh360_push[providers][native][fcm_enabled]" value="1"' . checked( ! empty( $native_settings['fcm_enabled'] ), true, false ) . '> ' . esc_html__( 'Enable Android push notifications', 'vh360-pwa-app' ) . '</label>';
			echo '</td></tr>';
			
			// FCM Project ID
			echo '<tr><th scope="row">' . esc_html__( 'FCM Project ID', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" class="regular-text" name="vh360_push[providers][native][fcm_project_id]" value="' . esc_attr( $native_settings['fcm_project_id'] ?? '' ) . '">';
			echo '<p class="description">' . esc_html__( 'Your Firebase project ID.', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// FCM Server Key
			echo '<tr><th scope="row">' . esc_html__( 'FCM Server Key', 'vh360-pwa-app' ) . '</th><td>';
			if ( ! empty( $native_settings['fcm_server_key'] ) ) {
				echo '<input type="password" class="regular-text" name="vh360_push[providers][native][fcm_server_key]" placeholder="' . esc_attr( str_repeat( '•', 40 ) ) . '">';
				echo '<p class="description">' . esc_html__( 'Leave blank to keep current value, or enter a new key to update.', 'vh360-pwa-app' ) . '</p>';
				echo '<p class="description"><span class="vh360-status-ok">✓</span> ' . esc_html__( 'Key is set (hidden).', 'vh360-pwa-app' ) . '</p>';
			} else {
				echo '<input type="password" class="regular-text" name="vh360_push[providers][native][fcm_server_key]" value="">';
				echo '<p class="description">' . esc_html__( 'Your FCM legacy server key.', 'vh360-pwa-app' ) . '</p>';
			}
			echo '</td></tr>';
			
			// FCM Sender ID
			echo '<tr><th scope="row">' . esc_html__( 'FCM Sender ID', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" class="regular-text" name="vh360_push[providers][native][fcm_sender_id]" value="' . esc_attr( $native_settings['fcm_sender_id'] ?? '' ) . '">';
			echo '<p class="description">' . esc_html__( 'Your FCM sender ID (Project Number).', 'vh360-pwa-app' ) . '</p>';
			echo '</td></tr>';
			
			// Test FCM Button
			echo '<tr><th scope="row">' . esc_html__( 'Test FCM Connection', 'vh360-pwa-app' ) . '</th><td>';
			echo '<button type="button" class="button" id="vh360_test_fcm">' . esc_html__( 'Test FCM Connection', 'vh360-pwa-app' ) . '</button>';
			echo '<div id="vh360_fcm_test_result" style="margin-top: 10px;"></div>';
			echo '</td></tr>';
			
			echo '</table>';
		}

		echo '<p class="submit">';
		submit_button( __( 'Save Settings', 'vh360-pwa-app' ), 'primary', 'vh360_pwa_push_save_settings', false );
		submit_button( __( 'Validate Configuration', 'vh360-pwa-app' ), 'secondary', 'vh360_pwa_push_validate_settings', false );
		echo '</p>';

		echo '</form>';

		// Reset section
		echo '<hr style="margin: 40px 0;">';
		echo '<h2>' . esc_html__( 'Reset Settings', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'Clear all push notification settings and logs. This action cannot be undone.', 'vh360-pwa-app' ) . '</p>';
		echo '<form method="post" action="" onsubmit="return confirm(\'' . esc_js( __( 'Are you sure you want to reset all push notification settings? This cannot be undone.', 'vh360-pwa-app' ) ) . '\');">';
		wp_nonce_field( 'vh360_pwa_push_reset', '_wpnonce_reset' );
		submit_button( __( 'Reset Push Setup', 'vh360-pwa-app' ), 'delete', 'vh360_pwa_push_reset', false );
		echo '</form>';

		// Flush Rewrite Rules section
		echo '<hr style="margin: 40px 0;">';
		echo '<h2>' . esc_html__( 'Flush Rewrite Rules', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'If service worker endpoints return 404 after changing permalinks or caching settings, click this button to refresh WordPress rewrite rules.', 'vh360-pwa-app' ) . '</p>';
		echo '<form method="post" action="">';
		wp_nonce_field( 'vh360_pwa_push_flush_rewrite', '_wpnonce_flush' );
		submit_button( __( 'Flush Rewrite Rules', 'vh360-pwa-app' ), 'secondary', 'vh360_pwa_push_flush_rewrite', false );
		echo '</form>';
	}

	/**
	 * Render Diagnostics tab
	 */
	private function render_tab_diagnostics() : void {
		$settings = $this->push_manager->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$active_provider = $settings['active_provider'] ?? '';
		$onesignal_settings = $settings['providers']['onesignal'] ?? array();

		echo '<h2>' . esc_html__( 'System Checks', 'vh360-pwa-app' ) . '</h2>';

		echo '<table class="widefat striped" style="max-width: 800px;">';
		echo '<thead><tr><th>' . esc_html__( 'Check', 'vh360-pwa-app' ) . '</th><th>' . esc_html__( 'Status', 'vh360-pwa-app' ) . '</th><th>' . esc_html__( 'Details', 'vh360-pwa-app' ) . '</th></tr></thead>';
		echo '<tbody>';

		// Mode check
		echo '<tr><td>' . esc_html__( 'Push Mode', 'vh360-pwa-app' ) . '</td><td>';
		echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html( ucfirst( $mode ) ) . '</td></tr>';

		// Active provider check
		echo '<tr><td>' . esc_html__( 'Active Provider', 'vh360-pwa-app' ) . '</td><td>';
		$adapter = $this->push_manager->get_adapter( $active_provider );
		if ( $adapter ) {
			echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html( $adapter->get_label() ) . '</td></tr>';
		} else {
			echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html__( 'Provider not found', 'vh360-pwa-app' ) . '</td></tr>';
		}

		// HTTPS check
		echo '<tr><td>' . esc_html__( 'HTTPS', 'vh360-pwa-app' ) . '</td><td>';
		if ( is_ssl() ) {
			echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html__( 'Site is using HTTPS', 'vh360-pwa-app' ) . '</td></tr>';
		} else {
			echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html__( 'HTTPS is required for web push notifications', 'vh360-pwa-app' ) . '</td></tr>';
		}

		// Browser support note
		echo '<tr><td>' . esc_html__( 'Browser Support', 'vh360-pwa-app' ) . '</td><td>';
		echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'Web push is supported in most modern browsers. Private browsing modes do not support push notifications.', 'vh360-pwa-app' ) . '</td></tr>';

		// Service Worker endpoints check
		$sw_urls = array(
			'/OneSignalSDKWorker.js'        => 'OneSignal Worker',
			'/OneSignalSDKUpdaterWorker.js' => 'OneSignal Updater',
		);

		foreach ( $sw_urls as $path => $label ) {
			$url = home_url( $path );
			echo '<tr><td>' . esc_html( $label ) . '</td><td>';
			
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
			if ( is_wp_error( $response ) ) {
				echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html( $response->get_error_message() ) . '</td></tr>';
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				if ( 200 === $code ) {
					echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html__( 'Accessible', 'vh360-pwa-app' ) . ' (<code>' . esc_html( $url ) . '</code>)</td></tr>';
				} else {
					echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html__( 'HTTP', 'vh360-pwa-app' ) . ' ' . esc_html( $code ) . ' - <code>' . esc_html( $url ) . '</code></td></tr>';
				}
			}
		}

		// Settings validation
		echo '<tr><td>' . esc_html__( 'Configuration', 'vh360-pwa-app' ) . '</td><td>';
		$validation_errors = $this->push_manager->validate_current_settings();
		if ( empty( $validation_errors ) ) {
			echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html__( 'All settings are valid', 'vh360-pwa-app' ) . '</td></tr>';
		} else {
			echo '<span class="vh360-status-error">✗</span></td><td>';
			echo '<ul style="margin:0;padding-left:20px;">';
			foreach ( $validation_errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul>';
			echo '</td></tr>';
		}

		echo '</tbody></table>';

		// Device Tokens Statistics (Phase 2.1)
		$app = VH360_PWA_App::instance();
		if ( $app->token_manager && $app->token_manager->table_exists() ) {
			echo '<h2>' . esc_html__( 'Device Tokens', 'vh360-pwa-app' ) . '</h2>';
			$token_stats = $app->token_manager->get_statistics();

			echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:15px;margin-bottom:20px;">';
			echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">';
			
			echo '<div style="text-align:center;">';
			echo '<div style="font-size:24px;font-weight:600;color:#2271b1;">' . number_format_i18n( $token_stats['total'] ) . '</div>';
			echo '<div style="font-size:13px;color:#646970;">' . esc_html__( 'Total Tokens', 'vh360-pwa-app' ) . '</div>';
			echo '</div>';

			echo '<div style="text-align:center;">';
			echo '<div style="font-size:24px;font-weight:600;color:#46b450;">' . number_format_i18n( $token_stats['active'] ) . '</div>';
			echo '<div style="font-size:13px;color:#646970;">' . esc_html__( 'Active Tokens', 'vh360-pwa-app' ) . '</div>';
			echo '</div>';

			echo '<div style="text-align:center;">';
			echo '<div style="font-size:24px;font-weight:600;">🍎 ' . number_format_i18n( $token_stats['ios'] ) . '</div>';
			echo '<div style="font-size:13px;color:#646970;">' . esc_html__( 'iOS Tokens', 'vh360-pwa-app' ) . '</div>';
			echo '</div>';

			echo '<div style="text-align:center;">';
			echo '<div style="font-size:24px;font-weight:600;">🤖 ' . number_format_i18n( $token_stats['android'] ) . '</div>';
			echo '<div style="font-size:13px;color:#646970;">' . esc_html__( 'Android Tokens', 'vh360-pwa-app' ) . '</div>';
			echo '</div>';

			if ( $token_stats['inactive'] > 0 ) {
				echo '<div style="text-align:center;">';
				echo '<div style="font-size:24px;font-weight:600;color:#dba617;">⚠️ ' . number_format_i18n( $token_stats['inactive'] ) . '</div>';
				echo '<div style="font-size:13px;color:#646970;">' . esc_html__( 'Inactive Tokens', 'vh360-pwa-app' ) . '</div>';
				echo '</div>';
			}

			echo '</div>';

			$next_cleanup = wp_next_scheduled( 'vh360_pwa_push_token_cleanup' );
			if ( $next_cleanup ) {
				echo '<p style="margin:10px 0 0;color:#646970;font-size:13px;">';
				echo '<em>' . sprintf(
					esc_html__( 'Next cleanup scheduled: %s', 'vh360-pwa-app' ),
					esc_html( human_time_diff( $next_cleanup, current_time( 'timestamp' ) ) . ' from now' )
				) . '</em>';
				echo '</p>';
			}

			echo '<p style="margin:10px 0 0;">';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=vh360-pwa-push-tokens' ) ) . '" class="button">' . esc_html__( 'Manage Device Tokens', 'vh360-pwa-app' ) . '</a>';
			echo '</p>';

			echo '</div>';
		}

		// Native Push Status (Phase 2.2)
		if ( $mode === 'native' || $mode === 'hybrid' ) {
			echo '<h2>' . esc_html__( 'Native Push Status', 'vh360-pwa-app' ) . '</h2>';
			
			$native_settings = $settings['providers']['native'] ?? array();
			$apns_test = get_transient( 'vh360_apns_test_result' );
			$fcm_test = get_transient( 'vh360_fcm_test_result' );
			$last_send = get_option( 'vh360_push_native_last_send', array() );
			$token_manager = VH360_PWA_App::instance()->token_manager;
			
			echo '<table class="widefat striped" style="max-width: 800px;">';
			echo '<thead><tr><th>' . esc_html__( 'Check', 'vh360-pwa-app' ) . '</th><th>' . esc_html__( 'Status', 'vh360-pwa-app' ) . '</th><th>' . esc_html__( 'Details', 'vh360-pwa-app' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			// APNs Status
			echo '<tr><td>' . esc_html__( 'APNs Connection', 'vh360-pwa-app' ) . '</td><td>';
			if ( empty( $native_settings['apns_enabled'] ) ) {
				echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'APNs is disabled', 'vh360-pwa-app' ) . '</td></tr>';
			} elseif ( $apns_test && ! empty( $apns_test['valid'] ) ) {
				echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html( $apns_test['message'] ) . '</td></tr>';
			} elseif ( $apns_test ) {
				echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html( $apns_test['message'] ) . '</td></tr>';
			} else {
				echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'Not tested yet. Go to Setup tab to test connection.', 'vh360-pwa-app' ) . '</td></tr>';
			}
			
			// FCM Status
			echo '<tr><td>' . esc_html__( 'FCM Connection', 'vh360-pwa-app' ) . '</td><td>';
			if ( empty( $native_settings['fcm_enabled'] ) ) {
				echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'FCM is disabled', 'vh360-pwa-app' ) . '</td></tr>';
			} elseif ( $fcm_test && ! empty( $fcm_test['valid'] ) ) {
				echo '<span class="vh360-status-ok">✓</span></td><td>' . esc_html( $fcm_test['message'] ) . '</td></tr>';
			} elseif ( $fcm_test ) {
				echo '<span class="vh360-status-error">✗</span></td><td>' . esc_html( $fcm_test['message'] ) . '</td></tr>';
			} else {
				echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'Not tested yet. Go to Setup tab to test connection.', 'vh360-pwa-app' ) . '</td></tr>';
			}
			
			// Active iOS Tokens
			if ( $token_manager ) {
				$ios_tokens = $token_manager->get_tokens( array( 'platform' => 'ios', 'active_only' => true ) );
				echo '<tr><td>' . esc_html__( 'Active iOS Tokens', 'vh360-pwa-app' ) . '</td><td>';
				echo '<span class="vh360-status-ok">✓</span></td><td>' . count( $ios_tokens ) . '</td></tr>';
				
				// Active Android Tokens
				$android_tokens = $token_manager->get_tokens( array( 'platform' => 'android', 'active_only' => true ) );
				echo '<tr><td>' . esc_html__( 'Active Android Tokens', 'vh360-pwa-app' ) . '</td><td>';
				echo '<span class="vh360-status-ok">✓</span></td><td>' . count( $android_tokens ) . '</td></tr>';
			}
			
			// Last Send
			echo '<tr><td>' . esc_html__( 'Last Native Send', 'vh360-pwa-app' ) . '</td><td>';
			if ( ! empty( $last_send['timestamp'] ) ) {
				echo '<span class="vh360-status-info">ℹ</span></td><td>';
				echo sprintf(
					esc_html__( '%s ago - Sent: %d, Failed: %d', 'vh360-pwa-app' ),
					human_time_diff( $last_send['timestamp'], current_time( 'timestamp' ) ),
					$last_send['sent'] ?? 0,
					$last_send['failed'] ?? 0
				);
				echo '</td></tr>';
			} else {
				echo '<span class="vh360-status-info">ℹ</span></td><td>' . esc_html__( 'No sends yet', 'vh360-pwa-app' ) . '</td></tr>';
			}
			
			echo '</tbody></table>';
			
			// Test Send to Device Form
			echo '<h3>' . esc_html__( 'Send Test to Device', 'vh360-pwa-app' ) . '</h3>';
			echo '<p>' . esc_html__( 'Send a test notification to a specific device token for testing.', 'vh360-pwa-app' ) . '</p>';
			echo '<div id="vh360-native-test-form" style="background:#fff;border:1px solid #ccd0d4;padding:15px;margin-bottom:20px;">';
			echo '<table class="form-table">';
			echo '<tr><th scope="row">' . esc_html__( 'Device Token', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" id="vh360_test_device_token" class="large-text" placeholder="' . esc_attr__( 'Enter device token', 'vh360-pwa-app' ) . '">';
			echo '</td></tr>';
			echo '<tr><th scope="row">' . esc_html__( 'Platform', 'vh360-pwa-app' ) . '</th><td>';
			echo '<select id="vh360_test_device_platform">';
			echo '<option value="ios">' . esc_html__( 'iOS', 'vh360-pwa-app' ) . '</option>';
			echo '<option value="android">' . esc_html__( 'Android', 'vh360-pwa-app' ) . '</option>';
			echo '</select>';
			echo '</td></tr>';
			echo '<tr><th scope="row">' . esc_html__( 'Title', 'vh360-pwa-app' ) . '</th><td>';
			echo '<input type="text" id="vh360_test_device_title" class="regular-text" value="' . esc_attr__( 'Test Notification', 'vh360-pwa-app' ) . '">';
			echo '</td></tr>';
			echo '<tr><th scope="row">' . esc_html__( 'Body', 'vh360-pwa-app' ) . '</th><td>';
			echo '<textarea id="vh360_test_device_body" class="large-text" rows="3">' . esc_textarea( __( 'This is a test push notification', 'vh360-pwa-app' ) ) . '</textarea>';
			echo '</td></tr>';
			echo '</table>';
			echo '<p><button type="button" class="button button-primary" id="vh360_send_test_device_btn">' . esc_html__( 'Send Test Push', 'vh360-pwa-app' ) . '</button></p>';
			echo '<div id="vh360_test_device_result" style="margin-top:10px;"></div>';
			echo '</div>';
		}

		// CDN/Cache warning
		echo '<div class="vh360-help-box" style="background:#fff8dc;padding:15px;margin:20px 0;border-left:4px solid #f39c12;">';
		echo '<h3 style="margin-top:0;">' . esc_html__( 'Common Issue: CDN/Cache Blocking', 'vh360-pwa-app' ) . '</h3>';
		echo '<p>' . esc_html__( 'If service worker files return 403/404 errors, your CDN or caching plugin may be blocking them. Whitelist these paths:', 'vh360-pwa-app' ) . '</p>';
		echo '<ul>';
		echo '<li><code>/OneSignalSDKWorker.js</code></li>';
		echo '<li><code>/OneSignalSDKUpdaterWorker.js</code></li>';
		echo '</ul>';
		echo '<p>' . esc_html__( 'Refer to your CDN/cache plugin documentation for instructions on excluding URLs.', 'vh360-pwa-app' ) . '</p>';
		echo '</div>';

		// Production Validation Checklist
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-info inline" style="margin: 20px 0 10px;">';
			echo '<p><strong>' . esc_html__( 'Developer Note', 'vh360-pwa-app' ) . '</strong><br>';
			echo esc_html__( 'The Production Validation Checklist reflects OneSignal diagnostic test states, not live push delivery. In some WordPress/PWA configurations, items may remain "Not yet tested" even when push notifications are fully functional.', 'vh360-pwa-app' );
			echo '</p>';
			echo '</div>';
		}
		$this->render_validation_checklist();

		// Send test
		echo '<h2>' . esc_html__( 'Send Test Push', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'Send a test notification to all subscribers. Make sure you have saved valid settings first.', 'vh360-pwa-app' ) . '</p>';
		echo '<div id="vh360-push-test-form">';
		echo '<p><input type="text" id="vh360_push_test_title" class="regular-text" placeholder="' . esc_attr__( 'Test notification title', 'vh360-pwa-app' ) . '" value="' . esc_attr__( 'Test from VH360 PWA', 'vh360-pwa-app' ) . '"></p>';
		echo '<p><textarea id="vh360_push_test_body" class="large-text" rows="3" placeholder="' . esc_attr__( 'Test notification body', 'vh360-pwa-app' ) . '">' . esc_textarea( __( 'This is a test push notification.', 'vh360-pwa-app' ) ) . '</textarea></p>';
		echo '<p><button type="button" class="button button-primary" id="vh360_push_send_test_btn">' . esc_html__( 'Send Test Push', 'vh360-pwa-app' ) . '</button></p>';
		echo '<div id="vh360-push-test-result" style="margin-top:10px;"></div>';
		echo '</div>';

		// Recent logs
		echo '<h2>' . esc_html__( 'Recent Activity', 'vh360-pwa-app' ) . '</h2>';
		$logs = $this->push_manager->get_logs( 10 );
		if ( empty( $logs ) ) {
			echo '<p>' . esc_html__( 'No activity yet.', 'vh360-pwa-app' ) . '</p>';
		} else {
			// Calculate statistics
			$total_sent = 0;
			$total_success = 0;
			$total_failed = 0;
			foreach ( $logs as $log ) {
				$total_sent++;
				$has_success = false;
				foreach ( $log['results'] as $result ) {
					if ( ! empty( $result['success'] ) ) {
						$has_success = true;
					}
				}
				if ( $has_success ) {
					$total_success++;
				} else {
					$total_failed++;
				}
			}
			
			// Show summary stats
			if ( $total_sent > 0 ) {
				$success_rate = round( ( $total_success / $total_sent ) * 100 );
				echo '<div style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2271b1;">';
				echo '<strong>' . esc_html__( 'Statistics (last 10):', 'vh360-pwa-app' ) . '</strong> ';
				echo esc_html( sprintf( __( 'Success Rate: %d%%', 'vh360-pwa-app' ), $success_rate ) ) . ' | ';
				echo esc_html( sprintf( __( 'Success: %d', 'vh360-pwa-app' ), $total_success ) ) . ' | ';
				echo esc_html( sprintf( __( 'Failed: %d', 'vh360-pwa-app' ), $total_failed ) );
				echo '</div>';
			}
			
			echo '<table class="widefat striped">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Time', 'vh360-pwa-app' ) . '</th>';
			echo '<th>' . esc_html__( 'Action', 'vh360-pwa-app' ) . '</th>';
			echo '<th>' . esc_html__( 'Title', 'vh360-pwa-app' ) . '</th>';
			echo '<th>' . esc_html__( 'Recipients', 'vh360-pwa-app' ) . '</th>';
			echo '<th>' . esc_html__( 'Delivered', 'vh360-pwa-app' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'vh360-pwa-app' ) . '</th>';
			echo '</tr></thead>';
			echo '<tbody>';
			foreach ( $logs as $log ) {
				echo '<tr>';
				echo '<td>' . esc_html( $log['timestamp'] ) . '</td>';
				echo '<td>' . esc_html( $log['action'] ) . '</td>';
				echo '<td>' . esc_html( $log['title'] ) . '</td>';
				echo '<td>' . esc_html( $log['recipients_count'] ?? 0 ) . '</td>';
				echo '<td>' . esc_html( $log['delivered_count'] ?? 0 ) . '</td>';
				echo '<td>';
				$any_success = false;
				foreach ( $log['results'] as $result ) {
					if ( ! empty( $result['success'] ) ) {
						$any_success = true;
					}
				}
				if ( $any_success ) {
					echo '<span class="vh360-status-ok">✓ ' . esc_html__( 'Success', 'vh360-pwa-app' ) . '</span>';
				} else {
					echo '<span class="vh360-status-error">✗ ' . esc_html__( 'Failed', 'vh360-pwa-app' ) . '</span>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		// Export diagnostics
		echo '<h2>' . esc_html__( 'Export Support Report', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'Copy this report when contacting support:', 'vh360-pwa-app' ) . '</p>';
		$report = $this->generate_diagnostics_report();
		echo '<textarea class="large-text code" rows="10" readonly onclick="this.select();">' . esc_textarea( $report ) . '</textarea>';
		echo '<p><button type="button" class="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.previousElementSibling.value).then(() => alert(\'Copied to clipboard!\'));">' . esc_html__( 'Copy to Clipboard', 'vh360-pwa-app' ) . '</button></p>';
	}

	/**
	 * Generate diagnostics report
	 */
	private function generate_diagnostics_report() : string {
		$settings = $this->push_manager->get_settings();
		$mode = $settings['mode'] ?? 'provider';
		$active_provider = $settings['active_provider'] ?? '';
		$onesignal_settings = $settings['providers']['onesignal'] ?? array();

		$report = "VH360 PWA Push Notifications - Diagnostics Report\n";
		$report .= "Generated: " . current_time( 'mysql' ) . "\n";
		$report .= "---\n\n";

		$report .= "Plugin Version: " . VH360_PWA_APP_VERSION . "\n";
		$report .= "WordPress Version: " . get_bloginfo( 'version' ) . "\n";
		$report .= "Site URL: " . home_url() . "\n";
		$report .= "HTTPS: " . ( is_ssl() ? 'Yes' : 'No' ) . "\n";
		$report .= "---\n\n";

		$report .= "Push Mode: " . ucfirst( $mode ) . "\n";
		$report .= "Active Provider: " . $active_provider . "\n";

		// Redact sensitive info
		$app_id = $onesignal_settings['app_id'] ?? '';
		if ( $app_id ) {
			$app_id_display = substr( $app_id, 0, 8 ) . '...' . substr( $app_id, -4 );
		} else {
			$app_id_display = '(not set)';
		}
		$report .= "OneSignal App ID: " . $app_id_display . "\n";
		$report .= "OneSignal API Key: " . ( ! empty( $onesignal_settings['rest_api_key'] ) ? '(set)' : '(not set)' ) . "\n";
		$report .= "---\n\n";

		// Service worker checks
		$report .= "Service Worker Checks:\n";
		$sw_urls = array(
			'/OneSignalSDKWorker.js'        => 'OneSignal Worker',
			'/OneSignalSDKUpdaterWorker.js' => 'OneSignal Updater',
		);
		foreach ( $sw_urls as $path => $label ) {
			$url = home_url( $path );
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
			if ( is_wp_error( $response ) ) {
				$report .= "  $label: ERROR - " . $response->get_error_message() . "\n";
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				$report .= "  $label: HTTP $code\n";
			}
		}
		$report .= "---\n\n";

		// Validation
		$validation_errors = $this->push_manager->validate_current_settings();
		if ( empty( $validation_errors ) ) {
			$report .= "Configuration: Valid\n";
		} else {
			$report .= "Configuration Errors:\n";
			foreach ( $validation_errors as $error ) {
				$report .= "  - " . $error . "\n";
			}
		}
		$report .= "---\n\n";

		// Recent logs
		$logs = $this->push_manager->get_logs( 5 );
		$report .= "Recent Activity (" . count( $logs ) . " entries):\n";
		if ( empty( $logs ) ) {
			$report .= "  (none)\n";
		} else {
			foreach ( $logs as $log ) {
				$status = 'Failed';
				foreach ( $log['results'] as $result ) {
					if ( ! empty( $result['success'] ) ) {
						$status = 'Success';
						break;
					}
				}
				$report .= "  " . $log['timestamp'] . " - " . $log['action'] . " - " . $log['title'] . " - " . $status . "\n";
			}
		}

		return $report;
	}

	/**
	 * Render Send tab
	 */
	private function render_tab_send() : void {
		
		$vh360_licensed = function_exists( 'vh360_pwa_is_licensed' ) ? vh360_pwa_is_licensed() : true;
		if ( ! $vh360_licensed ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html( vh360_pwa_license_required_message() ) . '</p></div>';
			echo '<fieldset disabled="disabled" style="opacity:0.55;pointer-events:none;">';
		}
$settings = $this->push_manager->get_settings();
		$onesignal_settings = $settings['providers']['onesignal'] ?? array();

		echo '<h2>' . esc_html__( 'Send Notification', 'vh360-pwa-app' ) . '</h2>';

		// Validation check
		$validation_errors = $this->push_manager->validate_current_settings();
		if ( ! empty( $validation_errors ) ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Configuration Error:', 'vh360-pwa-app' ) . '</strong></p><ul>';
			foreach ( $validation_errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul><p>' . esc_html__( 'Please fix these issues in the Setup tab before sending notifications.', 'vh360-pwa-app' ) . '</p></div>';
		}

		echo '<div id="vh360-push-send-form">';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Title', 'vh360-pwa-app' ) . ' <span class="required">*</span></th><td>';
		echo '<input type="text" id="vh360_push_send_title" class="regular-text" placeholder="' . esc_attr__( 'Notification title', 'vh360-pwa-app' ) . '">';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Body', 'vh360-pwa-app' ) . ' <span class="required">*</span></th><td>';
		echo '<textarea id="vh360_push_send_body" class="large-text" rows="4" placeholder="' . esc_attr__( 'Notification message', 'vh360-pwa-app' ) . '"></textarea>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Click URL', 'vh360-pwa-app' ) . '</th><td>';
		$default_url = $onesignal_settings['default_click_url'] ?? home_url( '/' );
		echo '<input type="text" id="vh360_push_send_url" class="regular-text" placeholder="' . esc_attr( $default_url ) . '" value="' . esc_attr( $default_url ) . '">';
		echo '<p class="description">' . esc_html__( 'URL to open when notification is clicked.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Audience', 'vh360-pwa-app' ) . '</th><td>';
		echo '<strong>' . esc_html__( 'All Subscribers', 'vh360-pwa-app' ) . '</strong>';
		echo '<p class="description">' . esc_html__( 'Phase 1 sends to all subscribers. Segmentation will be available in future updates.', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';

		echo '<p class="submit">';
		echo '<button type="button" class="button button-primary button-large" id="vh360_push_send_btn"' . ( ! empty( $validation_errors ) ? ' disabled' : '' ) . '>' . esc_html__( 'Send Notification', 'vh360-pwa-app' ) . '</button>';
		echo '</p>';

		echo '<div id="vh360-push-send-result" style="margin-top:20px;"></div>';

		echo '</div>';
	
		if ( ! $vh360_licensed ) { echo '</fieldset>'; }
}

	/**
	 * Render Events tab
	 */
	private function render_tab_events() : void {
		// Get events instance
		$events = VH360_PWA_App::instance()->push_events;
		if ( ! $events ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Events system is not available.', 'vh360-pwa-app' ) . '</p></div>';
			return;
		}

		$settings = $events->get_settings();

		echo '<h2>' . esc_html__( 'Event-Triggered Notifications', 'vh360-pwa-app' ) . '</h2>';
		echo '<p>' . esc_html__( 'Automatically send push notifications when certain events occur on your site.', 'vh360-pwa-app' ) . '</p>';

		echo '<form method="post" action="">';
		wp_nonce_field( 'vh360_pwa_push_events' );

		// New Post Event
		echo '<div class="vh360-help-box" style="margin: 20px 0;">';
		echo '<h3>' . esc_html__( 'New Post Published', 'vh360-pwa-app' ) . '</h3>';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Enable', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_push_events[new_post_enabled]" value="1"' . checked( ! empty( $settings['new_post_enabled'] ), true, false ) . '> ' . esc_html__( 'Send notification when a new post is published', 'vh360-pwa-app' ) . '</label>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Title Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_push_events[new_post_template][title]" value="' . esc_attr( $settings['new_post_template']['title'] ?? '' ) . '">';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {site_name}, {author_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Body Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<textarea class="large-text" rows="3" name="vh360_push_events[new_post_template][body]">' . esc_textarea( $settings['new_post_template']['body'] ?? '' ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {post_excerpt}, {site_name}, {author_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';

		// Livestream Event
		echo '<div class="vh360-help-box" style="margin: 20px 0;">';
		echo '<h3>' . esc_html__( 'Livestream Started', 'vh360-pwa-app' ) . '</h3>';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Enable', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_push_events[livestream_enabled]" value="1"' . checked( ! empty( $settings['livestream_enabled'] ), true, false ) . '> ' . esc_html__( 'Send notification when a livestream starts', 'vh360-pwa-app' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'For post type "livestream" or posts tagged with "livestream"', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Title Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_push_events[livestream_template][title]" value="' . esc_attr( $settings['livestream_template']['title'] ?? '' ) . '">';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {site_name}, {author_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Body Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<textarea class="large-text" rows="3" name="vh360_push_events[livestream_template][body]">' . esc_textarea( $settings['livestream_template']['body'] ?? '' ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {post_excerpt}, {site_name}, {author_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';

		// Comment Event
		echo '<div class="vh360-help-box" style="margin: 20px 0;">';
		echo '<h3>' . esc_html__( 'New Comment', 'vh360-pwa-app' ) . '</h3>';
		echo '<table class="form-table" role="presentation">';

		echo '<tr><th scope="row">' . esc_html__( 'Enable', 'vh360-pwa-app' ) . '</th><td>';
		echo '<label><input type="checkbox" name="vh360_push_events[comment_enabled]" value="1"' . checked( ! empty( $settings['comment_enabled'] ), true, false ) . '> ' . esc_html__( 'Send notification when a new comment is approved', 'vh360-pwa-app' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Note: Can generate high notification volume', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Title Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<input type="text" class="regular-text" name="vh360_push_events[comment_template][title]" value="' . esc_attr( $settings['comment_template']['title'] ?? '' ) . '">';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {comment_author}, {site_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Body Template', 'vh360-pwa-app' ) . '</th><td>';
		echo '<textarea class="large-text" rows="3" name="vh360_push_events[comment_template][body]">' . esc_textarea( $settings['comment_template']['body'] ?? '' ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Available variables: {post_title}, {comment_author}, {comment_excerpt}, {site_name}', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';
		echo '</div>';

		// Post Types
		echo '<h3>' . esc_html__( 'Post Types', 'vh360-pwa-app' ) . '</h3>';
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Enabled Post Types', 'vh360-pwa-app' ) . '</th><td>';
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$enabled_types = $settings['post_types'] ?? array( 'post' );
		foreach ( $post_types as $type ) {
			$checked = in_array( $type->name, $enabled_types, true ) ? ' checked' : '';
			echo '<label style="display: block; margin-bottom: 5px;">';
			echo '<input type="checkbox" name="vh360_push_events[post_types][]" value="' . esc_attr( $type->name ) . '"' . $checked . '> ';
			echo esc_html( $type->label );
			echo '</label>';
		}
		echo '<p class="description">' . esc_html__( 'Select which post types should trigger notifications', 'vh360-pwa-app' ) . '</p>';
		echo '</td></tr>';
		echo '</table>';

		echo '<p class="submit">';
		submit_button( __( 'Save Event Settings', 'vh360-pwa-app' ), 'primary', 'vh360_pwa_push_save_events', false );
		echo '</p>';

		echo '</form>';

		// Info box about post meta box
		echo '<div class="vh360-help-box" style="background:#f0f6fc;border-left-color:#0073aa;">';
		echo '<h4 style="margin-top:0;">' . esc_html__( 'Per-Post Control', 'vh360-pwa-app' ) . '</h4>';
		echo '<p>' . esc_html__( 'When editing posts, you\'ll find a "Push Notification" meta box in the sidebar where you can enable or disable push notifications for individual posts.', 'vh360-pwa-app' ) . '</p>';
		echo '</div>';
	}

	/**
	 * AJAX: Send notification
	 */
	public function ajax_send_notification() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vh360-pwa-app' ) ) );
		}

		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$body = sanitize_textarea_field( $_POST['body'] ?? '' );
		$url = esc_url_raw( $_POST['url'] ?? '' );

		if ( empty( $title ) || empty( $body ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and body are required.', 'vh360-pwa-app' ) ) );
		}

		$message = array(
			'title'     => $title,
			'body'      => $body,
			'click_url' => $url,
		);

		$results = $this->push_manager->send( $message );

		// Check if any result was successful
		$any_success = false;
		foreach ( $results as $result ) {
			if ( ! empty( $result['success'] ) ) {
				$any_success = true;
				break;
			}
		}

		if ( $any_success ) {
			wp_send_json_success( array(
				'message' => __( 'Notification sent successfully!', 'vh360-pwa-app' ),
				'results' => $results,
			) );
		} else {
			$error_messages = array();
			foreach ( $results as $mode => $result ) {
				if ( ! empty( $result['error'] ) ) {
					$error_messages[] = ucfirst( $mode ) . ': ' . $result['error'];
				}
			}
			wp_send_json_error( array(
				'message' => implode( ' | ', $error_messages ),
				'results' => $results,
			) );
		}
	}

	/**
	 * AJAX: Send notification from the frontend dashboard
	 *
	 * This endpoint is intended for theme-side frontend forms.
	 */
	public function ajax_send_notification_frontend() : void {
		check_ajax_referer( 'vh360_pwa_push_frontend', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'vh360-pwa-app' ) ) );
		}

		$capability = apply_filters( 'vh360_pwa_push_frontend_capability', 'vh360_send_push' );
		// Always allow site admins, even if the custom capability wasn't granted yet.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( $capability ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vh360-pwa-app' ) ) );
		}

		// Rate limiting: 1 send per 30 seconds per user (filterable)
		$user_id = get_current_user_id();
		$window = absint( apply_filters( 'vh360_pwa_push_frontend_rate_limit_seconds', 30 ) );
		$rl_key = 'vh360_pwa_push_last_frontend_' . $user_id;
		$last_send = (int) get_transient( $rl_key );
		if ( $last_send && ( time() - $last_send ) < $window ) {
			wp_send_json_error( array(
				'message' => sprintf( __( 'Please wait %d seconds before sending another notification.', 'vh360-pwa-app' ), $window ),
			) );
		}

		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$body  = sanitize_textarea_field( $_POST['body'] ?? '' );
		$url   = esc_url_raw( $_POST['url'] ?? '' );
		$icon  = esc_url_raw( $_POST['icon'] ?? '' );

		if ( empty( $title ) || empty( $body ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and message are required.', 'vh360-pwa-app' ) ) );
		}

		$message = array(
			'title'     => $title,
			'body'      => $body,
			'click_url' => $url,
		);
		if ( ! empty( $icon ) ) {
			$message['icon_url'] = $icon;
		}

		$results = $this->push_manager->send( $message );

		// Store rate-limit timestamp only after attempting a send
		set_transient( $rl_key, time(), max( 1, $window ) );

		// Check if any result was successful
		$any_success = false;
		foreach ( $results as $result ) {
			if ( ! empty( $result['success'] ) ) {
				$any_success = true;
				break;
			}
		}

		if ( $any_success ) {
			wp_send_json_success( array(
				'message' => __( 'Notification sent successfully!', 'vh360-pwa-app' ),
				'results' => $results,
			) );
		}

		$error_messages = array();
		foreach ( $results as $mode => $result ) {
			if ( ! empty( $result['error'] ) ) {
				$error_messages[] = ucfirst( $mode ) . ': ' . $result['error'];
			}
		}
		wp_send_json_error( array(
			'message' => ! empty( $error_messages ) ? implode( ' | ', $error_messages ) : __( 'Notification failed to send.', 'vh360-pwa-app' ),
			'results' => $results,
		) );
	}

	/**
	 * AJAX: Send test notification
	 */
	public function ajax_send_test() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vh360-pwa-app' ) ) );
		}

		// Rate limiting
		$last_test = get_transient( 'vh360_pwa_push_last_test_' . get_current_user_id() );
		if ( $last_test ) {
			$wait_time = 60 - ( time() - $last_test );
			if ( $wait_time > 0 ) {
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'Please wait %d seconds before sending another test.', 'vh360-pwa-app' ),
						$wait_time
					),
				) );
			}
		}

		$title = sanitize_text_field( $_POST['title'] ?? '' );
		$body = sanitize_textarea_field( $_POST['body'] ?? '' );

		if ( empty( $title ) || empty( $body ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and body are required.', 'vh360-pwa-app' ) ) );
		}

		$message = array(
			'title' => $title,
			'body'  => $body,
		);

		$results = $this->push_manager->send_test( $message );

		// Set rate limit
		set_transient( 'vh360_pwa_push_last_test_' . get_current_user_id(), time(), 60 );

		// Check if any result was successful
		$any_success = false;
		foreach ( $results as $result ) {
			if ( ! empty( $result['success'] ) ) {
				$any_success = true;
				break;
			}
		}

		if ( $any_success ) {
			wp_send_json_success( array(
				'message' => __( 'Test notification sent successfully!', 'vh360-pwa-app' ),
				'results' => $results,
			) );
		} else {
			$error_messages = array();
			foreach ( $results as $mode => $result ) {
				if ( ! empty( $result['error'] ) ) {
					$error_messages[] = ucfirst( $mode ) . ': ' . $result['error'];
				}
			}
			wp_send_json_error( array(
				'message' => implode( ' | ', $error_messages ),
				'results' => $results,
			) );
		}
	}

	/**
	 * AJAX: Run validation checklist
	 */
	public function ajax_run_validation() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vh360-pwa-app' ) ) );
		}

		$settings = $this->push_manager->get_settings();
		$results = array();

		// Check OneSignalSDKWorker.js
		$sw_worker_url = home_url( '/OneSignalSDKWorker.js' );
		$response = wp_remote_get( $sw_worker_url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			$results['sw_worker_js'] = array(
				'passed' => false,
				'error'  => $response->get_error_message(),
			);
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$is_js = ( 200 === $code ) && ( false !== strpos( $body, 'importScripts' ) || false !== strpos( $body, 'OneSignal' ) );
			$results['sw_worker_js'] = array(
				'passed' => $is_js,
				'error'  => $is_js ? '' : sprintf( __( 'HTTP %d or invalid content', 'vh360-pwa-app' ), $code ),
			);
		}

		// Check OneSignalSDKUpdaterWorker.js
		$sw_updater_url = home_url( '/OneSignalSDKUpdaterWorker.js' );
		$response = wp_remote_get( $sw_updater_url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			$results['sw_updater_js'] = array(
				'passed' => false,
				'error'  => $response->get_error_message(),
			);
		} else {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$is_js = ( 200 === $code ) && ( false !== strpos( $body, 'importScripts' ) || false !== strpos( $body, 'OneSignal' ) );
			$results['sw_updater_js'] = array(
				'passed' => $is_js,
				'error'  => $is_js ? '' : sprintf( __( 'HTTP %d or invalid content', 'vh360-pwa-app' ), $code ),
			);
		}

		// Check HTTPS
		$results['https_enabled'] = array(
			'passed' => is_ssl(),
			'error'  => is_ssl() ? '' : __( 'HTTPS is required for push notifications', 'vh360-pwa-app' ),
		);

		// Check SW scope
		$sw_scope = $settings['providers']['onesignal']['sw_scope'] ?? '/';
		$results['sw_scope_root'] = array(
			'passed' => '/' === $sw_scope,
			'error'  => '/' === $sw_scope ? '' : sprintf( __( 'Current scope: %s', 'vh360-pwa-app' ), $sw_scope ),
		);

		// Check CDN blocking
		$cdn_check_passed = true;
		$cdn_errors = array();
		foreach ( array( $sw_worker_url, $sw_updater_url ) as $url ) {
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
			if ( ! is_wp_error( $response ) ) {
				$code = wp_remote_retrieve_response_code( $response );
				if ( 403 === $code || 404 === $code ) {
					$cdn_check_passed = false;
					$cdn_errors[] = basename( $url ) . ': HTTP ' . $code;
				}
			}
		}
		$results['no_cdn_blocking'] = array(
			'passed' => $cdn_check_passed,
			'error'  => $cdn_check_passed ? '' : implode( ', ', $cdn_errors ),
		);

		// Manual checks - preserve existing state
		$existing_results = get_option( 'vh360_pwa_push_validation_results', array() );
		foreach ( array( 'test_subscription', 'test_push_received', 'live_send_success' ) as $manual_key ) {
			if ( isset( $existing_results[ $manual_key ] ) ) {
				$results[ $manual_key ] = $existing_results[ $manual_key ];
			} else {
				$results[ $manual_key ] = array(
					'passed' => false,
					'error'  => __( 'Not yet tested', 'vh360-pwa-app' ),
				);
			}
		}

		// Save results
		update_option( 'vh360_pwa_push_validation_results', $results );
		update_option( 'vh360_pwa_push_last_validation', current_time( 'timestamp' ) );

		// Count passed/failed
		$passed = 0;
		$failed = 0;
		foreach ( $results as $result ) {
			if ( $result['passed'] ) {
				$passed++;
			} else {
				$failed++;
			}
		}

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'Validation complete: %d passed, %d failed', 'vh360-pwa-app' ),
				$passed,
				$failed
			),
			'results' => $results,
		) );
	}

	/**
	 * AJAX handler: Test APNs connection
	 */
	public function ajax_test_apns() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'vh360-pwa-app' ) ) );
			return;
		}

		$settings = $this->push_manager->get_settings();
		$native_settings = $settings['providers']['native'] ?? array();
		$native_adapter = $this->push_manager->get_adapter( 'native' );

		if ( ! $native_adapter ) {
			wp_send_json_error( array( 'message' => __( 'Native adapter not available', 'vh360-pwa-app' ) ) );
			return;
		}

		$result = $native_adapter->validate_apns_credentials( $native_settings );

		// Store result in transient
		set_transient( 'vh360_apns_test_result', $result, 300 );

		if ( $result['valid'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX handler: Test FCM connection
	 */
	public function ajax_test_fcm() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'vh360-pwa-app' ) ) );
			return;
		}

		$settings = $this->push_manager->get_settings();
		$native_settings = $settings['providers']['native'] ?? array();
		$native_adapter = $this->push_manager->get_adapter( 'native' );

		if ( ! $native_adapter ) {
			wp_send_json_error( array( 'message' => __( 'Native adapter not available', 'vh360-pwa-app' ) ) );
			return;
		}

		$result = $native_adapter->validate_fcm_credentials( $native_settings );

		// Store result in transient
		set_transient( 'vh360_fcm_test_result', $result, 300 );

		if ( $result['valid'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX handler: Send test notification to device
	 */
	public function ajax_send_test_device() : void {
		check_ajax_referer( 'vh360_pwa_push_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'vh360-pwa-app' ) ) );
			return;
		}

		// Rate limiting: 1 test per minute
		$rate_limit_key = 'vh360_test_device_' . get_current_user_id();
		if ( get_transient( $rate_limit_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Please wait before sending another test. Rate limit: 1 per minute.', 'vh360-pwa-app' ) ) );
			return;
		}

		// Get input
		$device_token = sanitize_text_field( $_POST['device_token'] ?? '' );
		$platform = sanitize_text_field( $_POST['platform'] ?? '' );
		$title = sanitize_text_field( $_POST['title'] ?? 'Test Notification' );
		$body = sanitize_textarea_field( $_POST['body'] ?? 'This is a test push notification' );

		// Validate input
		if ( empty( $device_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Device token is required', 'vh360-pwa-app' ) ) );
			return;
		}

		if ( ! in_array( $platform, array( 'ios', 'android' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid platform', 'vh360-pwa-app' ) ) );
			return;
		}

		// Get native adapter
		$native_adapter = $this->push_manager->get_adapter( 'native' );
		if ( ! $native_adapter ) {
			wp_send_json_error( array( 'message' => __( 'Native adapter not available', 'vh360-pwa-app' ) ) );
			return;
		}

		// Get settings
		$settings = $this->push_manager->get_settings();
		$native_settings = $settings['providers']['native'] ?? array();

		// Build message
		$message = array(
			'title' => $title,
			'body'  => $body,
		);

		// Send to device
		$result = $native_adapter->send_to_device( $device_token, $platform, $message, $native_settings );

		// Set rate limit
		set_transient( $rate_limit_key, true, 60 );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => __( 'Test notification sent successfully!', 'vh360-pwa-app' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $result['error'] ?? __( 'Failed to send notification', 'vh360-pwa-app' ) ) );
		}
	}
}


// --- Added Phase 1 completion: Setup Validate button + Auto-prompt fields rendering handled above ---
