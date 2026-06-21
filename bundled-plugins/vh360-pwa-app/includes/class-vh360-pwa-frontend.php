<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VH360_PWA_Frontend {
	public function register() : void {
		add_action( 'wp_head', array( $this, 'output_head_tags' ), 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_shortcode( 'vh360_pwa_install_button', array( $this, 'shortcode_install_button' ) );
		add_shortcode( 'vh360_pwa_status', array( $this, 'shortcode_status' ) );
		add_shortcode( 'vh360_push_subscribe', array( $this, 'shortcode_push_subscribe' ) );
	}

	public function output_head_tags() : void {
		if ( is_admin() || is_feed() ) {
			return;
		}
		$opts = vh360_pwa_get_options();
		if ( ! vh360_pwa_is_allowed_theme_active() ) {
			return;
		}
		if ( empty( $opts['enabled'] ) ) {
			return;
		}

		// Prefer a root-level manifest for maximum compatibility across hosts/CDNs.
		$manifest = esc_url( home_url( '/' . VH360_PWA_MANIFEST_SLUG ) );
		$theme_color = esc_attr( (string) $opts['theme_color'] );
		$splash_bg = esc_attr( (string) ( ! empty( $opts['splash_enabled'] ) ? $opts['splash_background_color'] : $opts['background_color'] ) );
		$apple_icon = function_exists( 'vh360_pwa_get_apple_touch_icon_url' ) ? esc_url( vh360_pwa_get_apple_touch_icon_url() ) : '';
		
		// Get app title for iOS - use short_name from PWA options or fallback to site name
		$app_title = ! empty( $opts['short_name'] ) && is_string( $opts['short_name'] ) ? $opts['short_name'] : get_bloginfo( 'name' );
		if ( empty( $app_title ) || ! is_string( $app_title ) ) {
			$app_title = ! empty( $opts['app_name'] ) && is_string( $opts['app_name'] ) ? $opts['app_name'] : 'VH360';
		}

		echo "\n<!-- VH360 PWA -->\n";
		// Important: in PHP single-quoted strings, "\n" is not interpreted as a newline.
		// If a theme outputs wp_head() in an unexpected place, literal "\\n" text can appear on the page.
		echo '<link rel="manifest" href="' . $manifest . '">' . "\n";
		echo '<meta name="theme-color" content="' . $theme_color . '">' . "\n";
		echo '<style id="vh360-pwa-launch-bg">html{background:' . $splash_bg . ';}</style>' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( $app_title ) . '">' . "\n";
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
		if ( $apple_icon ) {
			echo '<link rel="apple-touch-icon" href="' . $apple_icon . '">' . "\n";
		}
		if ( ! empty( $opts['splash_enabled'] ) && function_exists( 'vh360_pwa_get_ios_startup_images' ) ) {
			foreach ( vh360_pwa_get_ios_startup_images() as $startup ) {
				if ( ! empty( $startup['href'] ) && ! empty( $startup['media'] ) ) {
					echo '<link rel="apple-touch-startup-image" href="' . esc_url( $startup['href'] ) . '" media="' . esc_attr( $startup['media'] ) . '">' . "\n";
				}
			}
		}

		// Note: Diagnostic banners were intentionally removed. PWAs should not inject admin-only frontend warnings.
	}

	public function enqueue_scripts() : void {
		if ( is_admin() ) {
			return;
		}
		if ( ! vh360_pwa_is_enabled() ) {
			return;
		}

		$opts = vh360_pwa_get_options();

		wp_enqueue_script(
			'vh360-pwa-public',
			VH360_PWA_APP_URL . 'assets/public/pwa-public.js',
			array(),
			VH360_PWA_APP_VERSION,
			false
		);
		wp_enqueue_style(
			'vh360-pwa-public',
			VH360_PWA_APP_URL . 'assets/public/pwa-public.css',
			array(),
			VH360_PWA_APP_VERSION
		);

		wp_localize_script(
			'vh360-pwa-public',
			'VH360PWA',
			array(
				// Use root-level, static files for maximum compatibility across hosts/CDNs.
				'swUrl'              => home_url( '/' . VH360_PWA_SW_SLUG ),
				'offlineUrl'         => home_url( '/' . VH360_PWA_OFFLINE_SLUG ),
				'showInstallPrompt'  => ! empty( $opts['show_install_prompt'] ) ? 1 : 0,
				'installPromptText'  => (string) $opts['install_prompt_text'],
				'showInstallBanner'  => ! empty( $opts['show_install_banner'] ) ? 1 : 0,
				'installBannerText'  => (string) $opts['install_banner_text'],
				'bannerDismissDays'  => (int) $opts['install_banner_dismiss_days'],
				'showIosOnboarding' => ! empty( $opts['show_ios_onboarding'] ) ? 1 : 0,
				'debugMode' => ! empty( $opts['debug_mode'] ) ? 1 : 0,
				'isAdmin'   => current_user_can( 'manage_options' ) ? 1 : 0,
				// If OneSignal is active, it must own the root scope service worker.
				'skipSWRegister'     => $this->should_skip_sw_registration() ? 1 : 0,
				'appShortName'       => ! empty( $opts['short_name'] ) ? (string) $opts['short_name'] : get_bloginfo( 'name' ),
				'enablePullToRefresh' => ! empty( $opts['enable_pull_to_refresh'] ) ? 1 : 0,
				'showRefreshButton' => ! empty( $opts['show_refresh_button'] ) ? 1 : 0,
				'refreshLabel' => (string) $opts['refresh_label'],
			)
		);

		// Enqueue push notification scripts if configured
		$this->maybe_enqueue_push_scripts();
	}

	/**
	 * Maybe enqueue push notification scripts
	 */
	private function maybe_enqueue_push_scripts() : void {
		$push_manager = VH360_PWA_App::instance()->push_manager;
		if ( ! $push_manager ) {
			return;
		}

		$settings = $push_manager->get_settings();
		$mode = $settings['mode'] ?? '';
		
		// Only load in provider or hybrid mode
		if ( 'provider' !== $mode && 'hybrid' !== $mode ) {
			return;
		}

		$active_provider = $settings['active_provider'] ?? '';
		$adapter = $push_manager->get_adapter( $active_provider );
		
		if ( ! $adapter ) {
			return;
		}

		$provider_settings = $settings['providers'][ $active_provider ] ?? array();
		
		// Validate before enqueueing
		$errors = $adapter->validate_settings( $provider_settings );
		if ( ! empty( $errors ) ) {
			return;
		}

		// Enqueue provider SDK
		$adapter->enqueue_frontend_sdk( $provider_settings );

		// Localize push config
		$push_config = $adapter->get_frontend_bootstrap( $provider_settings );
		$push_config['currentUserId'] = get_current_user_id();
		wp_localize_script(
			'vh360-pwa-push-public',
			'VH360Push',
			$push_config
		);
	}

	/**
	 * Determine whether this plugin should skip registering its own service worker.
	 *
	 * When OneSignal is active, it registers/owns the root-scope service worker.
	 * Registering a second SW at the same scope causes conflicts and intermittent failures.
	 */
	private function should_skip_sw_registration() : bool {
		$push_manager = VH360_PWA_App::instance()->push_manager;
		if ( ! $push_manager ) {
			return false;
		}
		$settings = $push_manager->get_settings();
		$mode = $settings['mode'] ?? '';
		if ( 'provider' !== $mode && 'hybrid' !== $mode ) {
			return false;
		}
		$active_provider = $settings['active_provider'] ?? '';
		if ( 'onesignal' !== $active_provider ) {
			return false;
		}
		$adapter = $push_manager->get_adapter( $active_provider );
		if ( ! $adapter ) {
			return false;
		}
		$provider_settings = $settings['providers'][ $active_provider ] ?? array();
		$errors = $adapter->validate_settings( $provider_settings );
		return empty( $errors );
	}

	public function register_widgets() : void {
		if ( ! vh360_pwa_is_enabled() ) {
			return;
		}
		require_once VH360_PWA_APP_DIR . 'includes/class-vh360-pwa-status-widget.php';
		register_widget( 'VH360_PWA_Status_Widget' );
	}

	public function shortcode_install_button( $atts = array() ) : string {
		if ( ! vh360_pwa_is_enabled() ) {
			return '';
		}
		$atts = shortcode_atts(
			array(
				'text'  => '',
				'class' => 'vh360-pwa-install-button',
			),
			(array) $atts,
			'vh360_pwa_install_button'
		);

		$opts = vh360_pwa_get_options();
		$text = $atts['text'] ? (string) $atts['text'] : (string) $opts['install_prompt_text'];
		$text = esc_html( $text ? $text : 'Install app' );
		$class = esc_attr( (string) $atts['class'] );

		return '<button type="button" class="' . $class . '" data-vh360-pwa-install="1">' . $text . '</button>';
	}

	public function shortcode_status() : string {
		if ( ! vh360_pwa_is_enabled() ) {
			return '';
		}
		return '<div class="vh360-pwa-status" data-vh360-pwa-status="1"></div>';
	}

	/**
	 * Push subscription shortcode
	 */
	public function shortcode_push_subscribe( $atts = array() ) : string {
		$push_manager = VH360_PWA_App::instance()->push_manager;
		if ( ! $push_manager ) {
			return '';
		}

		$settings = $push_manager->get_settings();
		$mode = $settings['mode'] ?? '';
		
		// Only show in provider or hybrid mode
		if ( 'provider' !== $mode && 'hybrid' !== $mode ) {
			return '';
		}

		$active_provider = $settings['active_provider'] ?? '';
		$adapter = $push_manager->get_adapter( $active_provider );
		
		if ( ! $adapter ) {
			return '';
		}

		$provider_settings = $settings['providers'][ $active_provider ] ?? array();
		
		// Validate before showing UI
		$errors = $adapter->validate_settings( $provider_settings );
		if ( ! empty( $errors ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'button_text' => __( 'Enable Notifications', 'vh360-pwa-app' ),
				'enabled_text' => __( 'Notifications Enabled', 'vh360-pwa-app' ),
				'unsupported_text' => __( 'Push notifications not supported in this browser', 'vh360-pwa-app' ),
				'blocked_text' => __( 'Notifications blocked. Please reset permissions in your browser settings.', 'vh360-pwa-app' ),
			),
			(array) $atts,
			'vh360_push_subscribe'
		);

		ob_start();
		?>
		<div class="vh360-push-subscribe" data-vh360-push-subscribe="1">
			<div class="vh360-push-state vh360-push-unsupported" style="display:none;">
				<p><?php echo esc_html( $atts['unsupported_text'] ); ?></p>
			</div>
			<div class="vh360-push-state vh360-push-unsubscribed" style="display:none;">
				<button type="button" class="vh360-push-subscribe-btn" data-vh360-push-action="subscribe">
					<?php echo esc_html( $atts['button_text'] ); ?>
				</button>
			</div>
			<div class="vh360-push-state vh360-push-subscribed" style="display:none;">
				<p class="vh360-push-success">✓ <?php echo esc_html( $atts['enabled_text'] ); ?></p>
			</div>
			<div class="vh360-push-state vh360-push-blocked" style="display:none;">
				<p class="vh360-push-error"><?php echo esc_html( $atts['blocked_text'] ); ?></p>
			</div>
			<div class="vh360-push-state vh360-push-loading" style="display:none;">
				<p><?php esc_html_e( 'Loading...', 'vh360-pwa-app' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
