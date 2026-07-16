<?php
/**
 * Plugin Name: VH360 PWA & App
 * Plugin URI: https://videohub360.com
 * Description: Adds PWA capabilities (manifest, service worker, offline fallback, install prompt) for the Videohub360 Theme.
 * Version: 1.0.5
 * Author: VideoHub360
 * Author URI: https://videohub360.com
 * Text Domain: vh360-pwa-app
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent duplicate plugin loading
if ( defined( 'VH360_PWA_APP_VERSION' ) ) {
	return;
}

define( 'VH360_PWA_APP_VERSION', '1.0.5' );
define( 'VH360_PWA_APP_FILE', __FILE__ );
define( 'VH360_PWA_APP_DIR', plugin_dir_path( __FILE__ ) );
define( 'VH360_PWA_APP_URL', plugin_dir_url( __FILE__ ) );


/**
 * Get a cache-busting version for a plugin-owned asset.
 *
 * @param string $relative_path Asset path relative to the plugin root.
 * @return string
 */
if (!function_exists('vh360_pwa_app_asset_version')) {
    function vh360_pwa_app_asset_version($relative_path) {
        $relative_path = ltrim($relative_path, '/');
        $file_path = VH360_PWA_APP_DIR . $relative_path;

        if (file_exists($file_path)) {
            return VH360_PWA_APP_VERSION . '-' . filemtime($file_path);
        }

        return VH360_PWA_APP_VERSION;
    }
}

/**
 * License gate helpers
 */
if ( ! function_exists( 'vh360_pwa_is_licensed' ) ) {
    function vh360_pwa_is_licensed() : bool {
        return function_exists( 'videohub360_license_is_valid' ) && videohub360_license_is_valid();
    }
}

if ( ! function_exists( 'vh360_pwa_license_required_message' ) ) {
    function vh360_pwa_license_required_message() : string {
        return __( 'This feature requires an active VideoHub360 license (VideoHub360 → License).', 'vh360-pwa-app' );
    }
}

/**
 * Debug logging helper for VH360 PWA plugin
 * Only logs when WP_DEBUG is enabled
 *
 * @param string $message Log message
 * @param array $context Optional context data
 */
if ( ! function_exists( 'vh360_pwa_debug_log' ) ) {
    function vh360_pwa_debug_log( $message, $context = array() ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! function_exists( 'error_log' ) ) {
            return;
        }
        
        if ( ! empty( $context ) ) {
            $message .= ': ' . print_r( $context, true );
        }
        
        error_log( $message );
    }
}

add_action(
    'admin_init',
    function () {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            return;
        }

        if ( vh360_pwa_is_licensed() ) {
            return;
        }

        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        if ( '' === $action ) {
            return;
        }

        $blocked = array(
            'vh360_pwa_push_send',
            'vh360_pwa_push_send_frontend',
            'vh360_pwa_push_send_test',
            'vh360_pwa_push_run_validation',
            'vh360_test_apns',
            'vh360_test_fcm',
            'vh360_send_test_device',
        );

        if ( in_array( $action, $blocked, true ) ) {
            wp_send_json_error(
                array(
                    'message' => vh360_pwa_license_required_message(),
                    'code'    => 'vh360_license_required',
                ),
                403
            );
        }
    },
    0
);

add_action(
    'admin_notices',
    function () {
        if ( vh360_pwa_is_licensed() ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $url = admin_url( 'admin.php?page=videohub360-license' );
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'VH360 PWA & App features are locked until your VideoHub360 license is activated.', 'vh360-pwa-app' ) . ' ';
        echo '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Activate License', 'vh360-pwa-app' ) . '</a>';
        echo '</p></div>';
    }
);


// Back-compat / internal convenience constant.
// Some modules referenced VH360_PWA_APP_PATH; keep it defined to avoid runtime errors.
if ( ! defined( 'VH360_PWA_APP_PATH' ) ) {
	define( 'VH360_PWA_APP_PATH', VH360_PWA_APP_DIR );
}

if ( ! defined( 'VH360_PWA_ALLOWED_THEME_TEXTDOMAIN' ) ) {
	define( 'VH360_PWA_ALLOWED_THEME_TEXTDOMAIN', 'videohub360-theme' );
}

if ( ! defined( 'VH360_PWA_MANIFEST_SLUG' ) ) {
	define( 'VH360_PWA_MANIFEST_SLUG', 'vh360-manifest.json' );
}
if ( ! defined( 'VH360_PWA_SW_SLUG' ) ) {
	define( 'VH360_PWA_SW_SLUG', 'vh360-sw.js' );
}
if ( ! defined( 'VH360_PWA_OFFLINE_SLUG' ) ) {
	define( 'VH360_PWA_OFFLINE_SLUG', 'vh360-offline.html' );
}
if ( ! defined( 'VH360_PWA_LAUNCH_SHELL_SLUG' ) ) {
	define( 'VH360_PWA_LAUNCH_SHELL_SLUG', 'vh360-launch.html' );
}

// OneSignal SDK version
if ( ! defined( 'VH360_PWA_ONESIGNAL_SDK_VERSION' ) ) {
	define( 'VH360_PWA_ONESIGNAL_SDK_VERSION', 'v16' );
}

require_once VH360_PWA_APP_DIR . 'includes/helpers.php';
require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-endpoints.php';
require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-root-files.php';
require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-rewrite-monitor.php';
require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-admin.php';
require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-frontend.php';

// Push notification system
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-adapter-interface.php';
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-manager.php';
require_once VH360_PWA_APP_DIR . 'includes/push/providers/class-vh360-push-onesignal-adapter.php';
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-admin.php';
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-events.php';

// Native push token management (Phase 2.1)
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-token-manager.php';
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-rest-api.php';
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-tokens-admin.php';

// Native push adapters (Phase 2.2)
require_once VH360_PWA_APP_DIR . 'includes/push/libraries/class-vh360-apns-client.php';
require_once VH360_PWA_APP_DIR . 'includes/push/libraries/class-vh360-fcm-client.php';
require_once VH360_PWA_APP_DIR . 'includes/push/providers/class-vh360-push-native-adapter.php';

// Theme notification bridge
require_once VH360_PWA_APP_DIR . 'includes/push/class-vh360-pwa-push-theme-notification-bridge.php';

// App Store export feature classes
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-readiness-checker.php';
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-store-metadata.php';
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-manifest-enhancer.php';
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-icon-generator.php';
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-export-package.php';
require_once VH360_PWA_APP_DIR . 'includes/appstore/class-vh360-pwa-appstore-admin.php';

if ( ! function_exists( 'vh360_pwa_refresh_assets_after_customizer_save' ) ) {
	/**
	 * Refresh PWA asset and service worker freshness layers after Customizer saves.
	 */
	function vh360_pwa_refresh_assets_after_customizer_save() : void {
		if ( function_exists( 'vh360_pwa_bump_asset_version' ) ) {
			vh360_pwa_bump_asset_version();
		}

		if ( class_exists( 'VH360_PWA_Root_Files' ) ) {
			VH360_PWA_Root_Files::ensure_root_files();
		}
	}
}
add_action( 'customize_save_after', 'vh360_pwa_refresh_assets_after_customizer_save' );

if ( ! class_exists( 'VH360_PWA_App' ) ) {
final class VH360_PWA_App {
	/** @var VH360_PWA_App */
	private static $instance;

	/** @var VH360_PWA_Endpoints */
	public $endpoints;

	/** @var VH360_PWA_Admin */
	public $admin;

	/** @var VH360_PWA_Frontend */
	public $frontend;

	/** @var VH360_PWA_AppStore_Admin */
	public $appstore_admin;

	/** @var VH360_PWA_Push_Manager */
	public $push_manager;

	/** @var VH360_PWA_Push_Admin */
	public $push_admin;

	/** @var VH360_PWA_Push_Events */
	public $push_events;

	/** @var VH360_PWA_Push_Token_Manager */
	public $token_manager;

	/** @var VH360_PWA_Push_REST_API */
	public $push_rest_api;

	/** @var VH360_Push_Tokens_Admin */
	public $tokens_admin;

	/** @var VH360_PWA_Push_Theme_Notification_Bridge */
	public $notification_bridge;

	public static function instance() : VH360_PWA_App {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( VH360_PWA_APP_FILE, array( $this, 'on_activate' ) );
		register_deactivation_hook( VH360_PWA_APP_FILE, array( $this, 'on_deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		// Self-heal missing root files (service worker + manifest) if they were deleted.
		add_action( 'admin_init', array( $this, 'maybe_repair_root_files' ) );
		// Safety: if plugin files are replaced without deactivating/activating, activation hooks won't run.
		// Ensure the sender roles keep the capability needed for the frontend Push Notifications tab.
		add_action( 'admin_init', array( $this, 'ensure_push_sender_capability' ) );
		
		// Wire saved push sender roles into the capability grant filter
		add_filter( 'vh360_pwa_push_sender_roles', array( $this, 'get_push_sender_roles' ) );
	}
	
	/**
	 * Get push sender roles from saved options.
	 * 
	 * @param array $default_roles Default roles if none saved.
	 * @return array Array of role keys.
	 */
	public function get_push_sender_roles( $default_roles ): array {
		$opts = vh360_pwa_get_options();
		$saved_roles = isset( $opts['push_sender_roles'] ) && is_array( $opts['push_sender_roles'] ) 
			? $opts['push_sender_roles'] 
			: $default_roles;
		
		// Ensure it's a valid array
		if ( ! is_array( $saved_roles ) || empty( $saved_roles ) ) {
			// Use default from options instead of hardcoded
			$opts_with_defaults = vh360_pwa_get_options();
			$saved_roles = $opts_with_defaults['push_sender_roles'];
		}
		
		// Always ensure administrator is included
		if ( ! in_array( 'administrator', $saved_roles, true ) ) {
			$saved_roles[] = 'administrator';
		}
		
		return $saved_roles;
	}

	/**
	 * Ensure roles have the vh360_send_push capability even if activation hook did not run.
	 * Runs in admin context only.
	 */
	public function ensure_push_sender_capability() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$roles_to_grant = apply_filters( 'vh360_pwa_push_sender_roles', array( 'administrator', 'editor' ) );
		if ( ! is_array( $roles_to_grant ) ) {
			return;
		}

		// Get all roles to check for removal
		$all_roles = wp_roles()->roles;
		
		// Only manage capabilities for standard WordPress roles to avoid interfering with custom roles
		$standard_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
		
		foreach ( $standard_roles as $role_key ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}
			
			// Grant capability to selected roles
			if ( in_array( $role_key, $roles_to_grant, true ) ) {
				if ( ! $role->has_cap( 'vh360_send_push' ) ) {
					$role->add_cap( 'vh360_send_push' );
				}
			} else {
				// Remove capability from standard roles not in the list
				// Custom roles are left untouched
				if ( $role->has_cap( 'vh360_send_push' ) ) {
					$role->remove_cap( 'vh360_send_push' );
				}
			}
		}
	}

	public function plugins_loaded() : void {
		// Load translations.
		load_plugin_textdomain( 'vh360-pwa-app', false, dirname( plugin_basename( VH360_PWA_APP_FILE ) ) . '/languages' );
	}

	public function init() : void {
		$this->endpoints = new VH360_PWA_Endpoints();
		$this->endpoints->register();

		// Initialize rewrite monitor
		$monitor = new VH360_PWA_Rewrite_Monitor();
		$monitor->register();

		$this->frontend = new VH360_PWA_Frontend();
		$this->frontend->register();

		// Initialize push system
		$this->push_manager = new VH360_PWA_Push_Manager();
		$onesignal_adapter = new VH360_Push_OneSignal_Adapter();
		$this->push_manager->register_adapter( $onesignal_adapter );
		
		// Register native push adapter (Phase 2.2)
		$native_adapter = new VH360_Push_Native_Adapter();
		$this->push_manager->register_adapter( $native_adapter );

		// Initialize native push token management (Phase 2.1)
		$this->token_manager = new VH360_PWA_Push_Token_Manager();
		$this->push_rest_api = new VH360_PWA_Push_REST_API( $this->token_manager );
		$this->push_rest_api->register();

		// Initialize push events
		$this->push_events = new VH360_PWA_Push_Events( $this->push_manager );
		$this->push_events->register();

		// Initialize theme notification bridge
		if ( class_exists( 'VH360_PWA_Push_Manager' ) && $this->push_manager ) {
			$this->notification_bridge = new VH360_PWA_Push_Theme_Notification_Bridge( $this->push_manager );
		}

		// Hook into wp_login for token linking
		add_action( 'wp_login', array( $this, 'on_user_login' ), 10, 2 );

		// Register token cleanup cron handler
		add_action( 'vh360_pwa_push_token_cleanup', array( $this, 'cleanup_old_tokens' ) );

		if ( is_admin() ) {
			$this->admin = new VH360_PWA_Admin();
			$this->admin->register();
			
			$this->appstore_admin = new VH360_PWA_AppStore_Admin();
			$this->appstore_admin->register();

			$this->push_admin = new VH360_PWA_Push_Admin( $this->push_manager );
			$this->push_admin->register();

			$this->tokens_admin = new VH360_Push_Tokens_Admin( $this->token_manager );
			$this->tokens_admin->register();
		}
	}

	public function on_activate() : void {
		// Ensure default options exist.
		vh360_pwa_get_options();

		// Create push tokens table
		VH360_PWA_Push_Token_Manager::create_table();

		// Register rewrite rules for endpoints.
		VH360_PWA_Endpoints::add_rewrite_rules();
		flush_rewrite_rules( false );

		// Schedule token cleanup cron
		if ( ! wp_next_scheduled( 'vh360_pwa_push_token_cleanup' ) ) {
			wp_schedule_event( time(), 'weekly', 'vh360_pwa_push_token_cleanup' );
		}

		// Ensure required root files exist for PWA + OneSignal.
		VH360_PWA_Root_Files::ensure_root_files();

		// Capability for sending push notifications from the frontend dashboard.
		// Default: administrators + editors.
		$roles_to_grant = apply_filters( 'vh360_pwa_push_sender_roles', array( 'administrator', 'editor' ) );
		if ( is_array( $roles_to_grant ) ) {
			foreach ( $roles_to_grant as $role_key ) {
				$role = get_role( $role_key );
				if ( $role && ! $role->has_cap( 'vh360_send_push' ) ) {
					$role->add_cap( 'vh360_send_push' );
				}
			}
		}
	}

	public function on_deactivate() : void {
		flush_rewrite_rules();

		// Clear scheduled cron
		wp_clear_scheduled_hook( 'vh360_pwa_push_token_cleanup' );
	}

	/**
	 * Attempt to restore required root files if they go missing.
	 * Runs only for admins to avoid overhead.
	 */
	public function maybe_repair_root_files() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		VH360_PWA_Root_Files::ensure_root_files();
	}

	/**
	 * Handle user login - link pending tokens to user
	 * 
	 * @param string $user_login
	 * @param WP_User $user
	 */
	public function on_user_login( $user_login, $user ) : void {
		// Use a cookie-based approach instead of session_id for better WordPress compatibility
		$cookie_name = 'vh360_pending_token';
		if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
			$pending_token = json_decode( stripslashes( $_COOKIE[ $cookie_name ] ), true );
			if ( is_array( $pending_token ) && ! empty( $pending_token['device_token'] ) && $this->token_manager ) {
				$this->token_manager->link_token_to_user(
					$pending_token['device_token'],
					$user->ID
				);
				// Clear the cookie
				setcookie( $cookie_name, '', time() - 3600, '/' );
			}
		}
	}

	/**
	 * Cleanup old tokens (cron job handler)
	 */
	public function cleanup_old_tokens() : void {
		if ( ! $this->token_manager ) {
			return;
		}

		$settings = $this->push_manager->get_settings();
		$cleanup_days = isset( $settings['providers']['native']['token_cleanup_days'] ) 
			? absint( $settings['providers']['native']['token_cleanup_days'] ) 
			: 90;

		$deleted = $this->token_manager->cleanup_old_tokens( $cleanup_days );
		vh360_pwa_debug_log( sprintf( 'VH360 Push: Cleaned up %d old tokens', $deleted ) );
	}
}
} // end class_exists check

if ( class_exists( 'VH360_PWA_App' ) ) {
	VH360_PWA_App::instance();
}
