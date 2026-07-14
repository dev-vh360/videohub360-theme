<?php
/**
 * VideoHub360 License Client
 *
 * Handles license activation UI inside the plugin and talks to the
 * VideoHub360 Licensing server (running on your store site).
 *
 * This class does NOT create licenses – that is handled by the
 * videohub360-licensing plugin on the store. Here we only:
 *
 *  - Provide a License screen in the admin
 *  - Store the license key and status in wp_options
 *  - Validate the license against the remote server
 *  - Hook into WordPress' plugin update system so that only
 *    valid, in-support licenses receive automatic updates.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VideoHub360_License {

    /**
     * Option key for storing license data.
     *
     * @var string
     */
    private $option_key = 'videohub360_license_data';

    /**
     * Products array for update checks.
     *
     * Populated via get_plugin_products() to keep the registry in one place.
     *
     * @var array
     */
    private $products = array();

    /**
     * Theme slug for update checks.
     *
     * @var string
     */
    private $theme_slug = 'videohub360-theme';

    /**
     * Return the canonical map of product slugs to plugin file paths.
     *
     * Every bundled plugin that should be checked for updates via the
     * licensing server must be listed here. The key is the product_slug
     * sent to the API; the value is the WordPress plugin file path
     * (folder/main-file.php).
     *
     * @since 1.1.0
     * @return array<string,string>
     */
    private static function get_plugin_products() {
        return array(
            'videohub360-core'           => 'videohub360/videohub360.php',
            'videohub360-community'      => 'videohub360-community/videohub360-community.php',
            'videohub360-studio'         => 'videohub360-studio/videohub360-studio.php',
            'vh360-pwa-app'              => 'vh360-pwa-app/vh360-pwa-app.php',
            'videohub360-memberships'    => 'videohub360-memberships/videohub360-memberships.php',
            'videohub360-starter-sites'  => 'videohub360-starter-sites/videohub360-starter-sites.php',
        );
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->products = self::get_plugin_products();

        if ( is_admin() ) {
            $this->init_admin_hooks();
        }

        // Plugin updates
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_plugin_updates' ) );
        add_filter( 'plugins_api', array( $this, 'filter_plugin_info' ), 10, 3 );
        
        // Theme updates
        add_filter( 'pre_set_site_transient_update_themes', array( $this, 'filter_theme_updates' ) );
        add_filter( 'themes_api', array( $this, 'filter_theme_info' ), 10, 3 );
        
        // Clear cache when admin manually checks for updates
        add_action( 'load-update-core.php', array( $this, 'clear_update_cache' ) );
    }

    /**
     * Initialize admin hooks (only loaded in the dashboard).
     */
    private function init_admin_hooks() {
        add_action( 'admin_menu', array( $this, 'add_license_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_license_form_submission' ) );
    }

    /**
     * Add a "License" submenu under the VideoHub360 menu.
     */
    public function add_license_menu() {
        add_submenu_page(
            'edit.php?post_type=videohub360',
            __( 'VideoHub360 License', 'videohub360' ),
            __( 'License', 'videohub360' ),
            'manage_options',
            'videohub360-license',
            array( $this, 'render_license_page' )
        );
    }

    /**
     * Handle the License form submission.
     */
    public function handle_license_form_submission() {
        if ( ! isset( $_POST['videohub360_license_submit'] ) && ! isset( $_POST['videohub360_license_deactivate'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( 'videohub360_license_save', 'videohub360_license_nonce' );

        $license_key = isset( $_POST['videohub360_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['videohub360_license_key'] ) ) : '';


        // Deactivate on this site.
        if ( isset( $_POST['videohub360_license_deactivate'] ) ) {
            if ( '' === $license_key ) {
                return;
            }

            $deactivated = $this->remote_deactivate_site( $license_key );

            $data = array(
                'license_key' => $license_key,
                'status'      => $deactivated['success'] ? 'deactivated' : 'error',
                'message'     => $deactivated['message'],
            );

            update_option( $this->option_key, $data );
            return;
        }

        if ( '' === $license_key ) {
            // Clear license.
            $data = array(
                'license_key' => '',
                'status'      => 'empty',
                'message'     => __( 'License key cleared.', 'videohub360' ),
            );
            update_option( $this->option_key, $data );
            return;
        }

        // Activate site with remote server (enforces site limits).
        $activation = $this->remote_activate_site( $license_key );
        if ( empty( $activation['success'] ) ) {
            $data = array(
                'license_key' => $license_key,
                'status'      => 'invalid',
                'message'     => $activation['message'] ?? __( 'Activation failed.', 'videohub360' ),
                'active_sites'=> $activation['active_sites'] ?? array(),
            );
            update_option( $this->option_key, $data );
            return;
        }

        // Validate license with remote server (returns plan / dates).
        $result = $this->remote_validate_license( $license_key );

        $data = array(
            'license_key'        => $license_key,
            'status'             => $result['status'],
            'message'            => $result['message'],
            'plan'               => $result['plan'],
            'max_sites'          => $result['max_sites'],
            'sites_used'         => $result['sites_used'],
            'license_expires_at' => $result['license_expires_at'],
            'support_expires_at' => $result['support_expires_at'],
        );

        update_option( $this->option_key, $data );
    }

    /**
     * Render the License settings page.
     */
    public function render_license_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $data        = get_option( $this->option_key, array() );
        $license_key = isset( $data['license_key'] ) ? $data['license_key'] : '';
        $status      = isset( $data['status'] ) ? $data['status'] : 'unknown';
        $message     = isset( $data['message'] ) ? $data['message'] : '';

        $plan               = isset( $data['plan'] ) ? $data['plan'] : '';
        $max_sites          = isset( $data['max_sites'] ) ? (int) $data['max_sites'] : 0;
        $sites_used         = isset( $data['sites_used'] ) ? (int) $data['sites_used'] : 0;
        $license_expires_at = isset( $data['license_expires_at'] ) ? $data['license_expires_at'] : '';
        $support_expires_at = isset( $data['support_expires_at'] ) ? $data['support_expires_at'] : '';

        $server_url = $this->get_license_server_url();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'VideoHub360 License', 'videohub360' ); ?></h1>
            <p><?php esc_html_e( 'Enter the license key you received after purchasing VideoHub360. A valid license is required to receive automatic updates and support.', 'videohub360' ); ?></p>

            <form method="post">
                <?php wp_nonce_field( 'videohub360_license_save', 'videohub360_license_nonce' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="videohub360_license_key"><?php esc_html_e( 'License Key', 'videohub360' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="videohub360_license_key" name="videohub360_license_key" class="regular-text" value="<?php echo esc_attr( $license_key ); ?>" />
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: license server URL */
                                    esc_html__( 'Licenses are validated against: %s', 'videohub360' ),
                                    '<code>' . esc_html( $server_url ) . '</code>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save & Activate License', 'videohub360' ), 'primary', 'videohub360_license_submit' );
                if ( 'valid' === ( $license_data['status'] ?? '' ) ) {
                    submit_button( __( 'Deactivate License On This Site', 'videohub360' ), 'secondary', 'videohub360_license_deactivate', false );
                }
 ?>
            </form>

            <h2><?php esc_html_e( 'License Status', 'videohub360' ); ?></h2>
            <?php if ( $message ) : ?>
                <p><strong><?php echo esc_html( ucfirst( $status ) ); ?>:</strong> <?php echo esc_html( $message ); ?></p>
            <?php else : ?>
                <p><?php esc_html_e( 'No license has been activated yet.', 'videohub360' ); ?></p>
            <?php endif; ?>

            <?php if ( 'valid' === $status ) : ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( 'Plan', 'videohub360' ); ?></th>
                        <td><?php echo esc_html( $plan ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sites Used', 'videohub360' ); ?></th>
                        <td><?php echo esc_html( $sites_used ) . ' / ' . esc_html( $max_sites ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'License Updates Until', 'videohub360' ); ?></th>
                        <td>
                            <?php
                            if ( $license_expires_at ) {
                                echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_expires_at ) ) );
                            } else {
                                esc_html_e( 'Lifetime', 'videohub360' );
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Support Until', 'videohub360' ); ?></th>
                        <td>
                            <?php
                            if ( $support_expires_at ) {
                                echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $support_expires_at ) ) );
                            } else {
                                esc_html_e( 'N/A', 'videohub360' );
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get the base URL of the license server.
     *
     * This should be the domain where the VideoHub360 Licensing plugin is installed.
     * You can override it with the `videohub360_license_server_url` filter or by defining
     * the VIDEOHUB360_LICENSE_SERVER_URL constant.
     *
     * @return string
     */
    private function get_license_server_url() {
        $url = defined( 'VIDEOHUB360_LICENSE_SERVER_URL' ) ? VIDEOHUB360_LICENSE_SERVER_URL : '';

        if ( empty( $url ) ) {
            $url = 'https://videohub360.com'; // Placeholder, override in your own build.
        }

        /**
         * Filter the license server URL.
         *
         * @param string $url License server base URL.
         */
        $url = apply_filters( 'videohub360_license_server_url', $url );

        return untrailingslashit( $url );
    }

    /**
     * Perform a remote license validation request.
     *
     * @param string $license_key
     * @return array
     */
    
    private function remote_activate_site( $license_key ) {
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/activate-site';

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'body'    => array(
                'license_key' => $license_key,
                'site_url'    => home_url(),
                'site_name'   => get_bloginfo( 'name' ),
            ),
        );

        $response = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code || empty( $data ) || ! is_array( $data ) ) {
            return array( 'success' => false, 'message' => __( 'Unexpected response from license server.', 'videohub360' ) );
        }

        // Server uses {success:boolean, message:string, ...}
        if ( empty( $data['success'] ) ) {
            $message = isset( $data['message'] ) ? (string) $data['message'] : __( 'Activation failed.', 'videohub360' );
            return array( 'success' => false, 'message' => $message, 'active_sites' => $data['active_sites'] ?? array() );
        }

        return array(
            'success' => true,
            'message' => isset( $data['message'] ) ? (string) $data['message'] : __( 'Site activated.', 'videohub360' ),
        );
    }

    private function remote_deactivate_site( $license_key ) {
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/deactivate-site';

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'body'    => array(
                'license_key' => $license_key,
                'site_url'    => home_url(),
            ),
        );

        $response = wp_remote_post( $endpoint, $args );
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code || empty( $data ) || ! is_array( $data ) ) {
            return array( 'success' => false, 'message' => __( 'Unexpected response from license server.', 'videohub360' ) );
        }

        if ( empty( $data['success'] ) ) {
            $message = isset( $data['message'] ) ? (string) $data['message'] : __( 'Deactivation failed.', 'videohub360' );
            return array( 'success' => false, 'message' => $message );
        }

        return array(
            'success' => true,
            'message' => isset( $data['message'] ) ? (string) $data['message'] : __( 'Site deactivated.', 'videohub360' ),
        );
    }


private function remote_validate_license( $license_key ) {
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/validate-license';

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
            'body'    => array(
                'license_key' => $license_key,
                'site_url'    => home_url(),
            ),
        );

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return array(
                'status'             => 'error',
                'message'            => sprintf( __( 'Could not contact license server: %s', 'videohub360' ), $response->get_error_message() ),
                'plan'               => '',
                'max_sites'          => 0,
                'sites_used'         => 0,
                'license_expires_at' => '',
                'support_expires_at' => '',
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code || empty( $body ) ) {
            return array(
                'status'             => 'error',
                'message'            => __( 'Unexpected response from license server.', 'videohub360' ),
                'plan'               => '',
                'max_sites'          => 0,
                'sites_used'         => 0,
                'license_expires_at' => '',
                'support_expires_at' => '',
            );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return array(
                'status'             => 'error',
                'message'            => __( 'License server returned invalid data.', 'videohub360' ),
                'plan'               => '',
                'max_sites'          => 0,
                'sites_used'         => 0,
                'license_expires_at' => '',
                'support_expires_at' => '',
            );
        }

        if ( empty( $data['valid'] ) || true !== $data['valid'] ) {
            $reason  = isset( $data['reason'] ) ? $data['reason'] : 'invalid';
            $message = __( 'License key is invalid.', 'videohub360' );

            if ( 'expired' === $reason ) {
                $message = __( 'Your license has expired. Please renew to continue receiving updates.', 'videohub360' );
            } elseif ( 'inactive' === $reason ) {
                $message = __( 'Your license is inactive. Please contact support.', 'videohub360' );
            } elseif ( 'not_found' === $reason ) {
                $message = __( 'License key not found. Please check your key or contact support.', 'videohub360' );
            }

            return array(
                'status'             => 'invalid',
                'message'            => $message,
                'plan'               => '',
                'max_sites'          => 0,
                'sites_used'         => 0,
                'license_expires_at' => '',
                'support_expires_at' => '',
            );
        }

        return array(
            'status'             => 'valid',
            'message'            => __( 'License activated successfully.', 'videohub360' ),
            'plan'               => isset( $data['plan'] ) ? $data['plan'] : '',
            'max_sites'          => isset( $data['max_sites'] ) ? (int) $data['max_sites'] : 0,
            'sites_used'         => isset( $data['sites_used'] ) ? (int) $data['sites_used'] : 0,
            'license_expires_at' => isset( $data['license_expires_at'] ) ? $data['license_expires_at'] : '',
            'support_expires_at' => isset( $data['support_expires_at'] ) ? $data['support_expires_at'] : '',
        );
    }

    /**
     * Hook into plugin update checks and ask the licensing server if an update
     * is available for this plugin, given the current license key.
     *
     * @param object $transient
     * @return object
     */
    public function filter_plugin_updates( $transient ) {
        if ( empty( $transient ) || ! isset( $transient->checked ) ) {
            return $transient;
        }

        $data = get_option( $this->option_key, array() );
        if ( empty( $data['license_key'] ) || 'valid' !== ( $data['status'] ?? '' ) ) {
            return $transient;
        }

        $license_key = $data['license_key'];
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/update-check';

        // Loop through all products
        foreach ( $this->products as $product_slug => $plugin_file ) {
            // Check cache first (12-hour cache)
            $cache_key = 'vh360_update_check_' . $product_slug;
            $cached = get_transient( $cache_key );
            
            if ( false !== $cached ) {
                // Handle new response format with success field
                if ( isset( $cached['success'] ) && $cached['success'] 
                     && ! empty( $cached['new_version'] ) 
                     && ! empty( $cached['download_url'] ) ) {
                    $update = new stdClass();
                    $update->slug        = $product_slug;
                    $update->plugin      = $plugin_file;
                    $update->new_version = $cached['new_version'];
                    $update->package     = $cached['download_url'];
                    $update->url         = 'https://videohub360.com';
                    $transient->response[$plugin_file] = $update;
                }
                continue;
            }
            
            // Get current version
            $current_version = isset( $transient->checked[$plugin_file] ) 
                ? $transient->checked[$plugin_file] 
                : '0.0.0';

            // Make API call with product_slug
            $response = wp_remote_post( $endpoint, array(
                'timeout' => 15,
                'headers' => array( 'Accept' => 'application/json' ),
                'body'    => array(
                    'license_key'     => $license_key,
                    'current_version' => $current_version,
                    'product_slug'    => $product_slug,
                    'site_url'        => home_url(),
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );

            // Handle 403 (invalid license)
            if ( $code === 403 ) {
                set_transient( $cache_key, array( 'success' => false ), 1 * HOUR_IN_SECONDS );
                continue;
            }

            if ( $code !== 200 || empty( $body ) ) {
                continue;
            }

            $response_data = json_decode( $body, true );
            
            // Cache for 12 hours
            set_transient( $cache_key, $response_data, 12 * HOUR_IN_SECONDS );

            // Check new response format
            if ( ! is_array( $response_data ) 
                 || ! isset( $response_data['success'] ) 
                 || ! $response_data['success'] 
                 || empty( $response_data['new_version'] ) ) {
                continue;
            }

            $new_version  = $response_data['new_version'];
            $download_url = isset( $response_data['download_url'] ) ? $response_data['download_url'] : '';

            if ( ! $download_url ) {
                continue;
            }

            // Add update to transient
            $update              = new stdClass();
            $update->slug        = $product_slug;
            $update->plugin      = $plugin_file;
            $update->new_version = $new_version;
            $update->package     = $download_url;
            $update->url         = 'https://videohub360.com';

            $transient->response[$plugin_file] = $update;
        }

        return $transient;
    }

    /**
     * Hook into theme update checks for the VideoHub360 theme.
     *
     * @param object $transient
     * @return object
     */
    public function filter_theme_updates( $transient ) {
        if ( empty( $transient ) || ! isset( $transient->checked ) ) {
            return $transient;
        }

        $data = get_option( $this->option_key, array() );
        if ( empty( $data['license_key'] ) || 'valid' !== ( $data['status'] ?? '' ) ) {
            return $transient;
        }

        $license_key = $data['license_key'];
        $theme_slug = $this->theme_slug;
        
        // Check cache
        $cache_key = 'vh360_update_check_' . $theme_slug;
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            // Handle new response format
            if ( isset( $cached['success'] ) && $cached['success'] 
                 && ! empty( $cached['new_version'] ) 
                 && ! empty( $cached['download_url'] ) ) {
                $transient->response[$theme_slug] = array(
                    'theme'       => $theme_slug,
                    'new_version' => $cached['new_version'],
                    'url'         => 'https://videohub360.com',
                    'package'     => $cached['download_url'],
                );
            }
            return $transient;
        }

        // Get current theme version
        $current_version = isset( $transient->checked[$theme_slug] ) 
            ? $transient->checked[$theme_slug] 
            : '0.0.0';

        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/update-check';

        $response = wp_remote_post( $endpoint, array(
            'timeout' => 15,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => array(
                'license_key'     => $license_key,
                'current_version' => $current_version,
                'product_slug'    => $theme_slug,
                'site_url'        => home_url(),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $transient;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        // Handle 403 (invalid license)
        if ( $code === 403 ) {
            set_transient( $cache_key, array( 'success' => false ), 1 * HOUR_IN_SECONDS );
            return $transient;
        }

        if ( $code !== 200 || empty( $body ) ) {
            return $transient;
        }

        $response_data = json_decode( $body, true );
        
        set_transient( $cache_key, $response_data, 12 * HOUR_IN_SECONDS );

        // Check new response format
        if ( ! is_array( $response_data ) 
             || ! isset( $response_data['success'] ) 
             || ! $response_data['success'] 
             || empty( $response_data['new_version'] ) ) {
            return $transient;
        }

        $new_version  = $response_data['new_version'];
        $download_url = isset( $response_data['download_url'] ) ? $response_data['download_url'] : '';

        if ( ! $download_url ) {
            return $transient;
        }

        $transient->response[$theme_slug] = array(
            'theme'       => $theme_slug,
            'new_version' => $new_version,
            'url'         => 'https://videohub360.com',
            'package'     => $download_url,
        );

        return $transient;
    }

    /**
     * Filter plugin information shown on the details modal (optional).
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function filter_plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
            return $result;
        }

        $valid_slugs = array_keys( $this->products );
        if ( ! in_array( $args->slug, $valid_slugs, true ) ) {
            return $result;
        }

        // Check cache first
        $cache_key = 'vh360_plugin_info_' . $args->slug;
        $info = get_transient( $cache_key );
        
        if ( false !== $info ) {
            return $info;
        }

        // Call dedicated product-info endpoint
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/product-info';
        
        $response = wp_remote_post( $endpoint, array(
            'timeout' => 10,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => array(
                'product_slug' => $args->slug,
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $result;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $code !== 200 || empty( $body ) ) {
            return $result;
        }
        
        $data = json_decode( $body, true );
        
        // Check success field for consistency
        if ( ! is_array( $data ) || ! isset( $data['success'] ) || ! $data['success'] ) {
            return $result;
        }
        
        // Convert to object format WordPress expects
        $info = (object) array(
            'name'          => isset( $data['name'] ) ? $data['name'] : '',
            'slug'          => isset( $data['slug'] ) ? $data['slug'] : $args->slug,
            'version'       => isset( $data['version'] ) ? $data['version'] : '',
            'author'        => isset( $data['author'] ) ? $data['author'] : '',
            'author_profile'=> isset( $data['author_url'] ) ? $data['author_url'] : '',
            'homepage'      => isset( $data['homepage'] ) ? $data['homepage'] : '',
            'requires'      => isset( $data['requires'] ) ? $data['requires'] : '',
            'requires_php'  => isset( $data['requires_php'] ) ? $data['requires_php'] : '',
            'tested'        => isset( $data['tested'] ) ? $data['tested'] : '',
            'sections'      => isset( $data['sections'] ) ? $data['sections'] : array(),
        );
        
        // Cache for 24 hours
        set_transient( $cache_key, $info, 24 * HOUR_IN_SECONDS );
        
        return $info;
    }

    /**
     * Filter theme information shown on the details modal.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function filter_theme_info( $result, $action, $args ) {
        if ( 'theme_information' !== $action || empty( $args->slug ) ) {
            return $result;
        }

        if ( $this->theme_slug !== $args->slug ) {
            return $result;
        }

        // Check cache
        $cache_key = 'vh360_theme_info_' . $args->slug;
        $info = get_transient( $cache_key );
        
        if ( false !== $info ) {
            return $info;
        }

        // Call product-info endpoint
        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/product-info';
        
        $response = wp_remote_post( $endpoint, array(
            'timeout' => 10,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => array(
                'product_slug' => $this->theme_slug,
            ),
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $result;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        
        if ( $code !== 200 || empty( $body ) ) {
            return $result;
        }
        
        $data = json_decode( $body, true );
        
        // Check success field
        if ( ! is_array( $data ) || ! isset( $data['success'] ) || ! $data['success'] ) {
            return $result;
        }
        
        // Convert to object
        $info = (object) array(
            'name'         => isset( $data['name'] ) ? $data['name'] : '',
            'slug'         => isset( $data['slug'] ) ? $data['slug'] : $args->slug,
            'version'      => isset( $data['version'] ) ? $data['version'] : '',
            'author'       => isset( $data['author'] ) ? $data['author'] : '',
            'homepage'     => isset( $data['homepage'] ) ? $data['homepage'] : '',
            'requires'     => isset( $data['requires'] ) ? $data['requires'] : '',
            'requires_php' => isset( $data['requires_php'] ) ? $data['requires_php'] : '',
            'tested'       => isset( $data['tested'] ) ? $data['tested'] : '',
            'sections'     => isset( $data['sections'] ) ? $data['sections'] : array(),
        );
        
        // Cache for 24 hours
        set_transient( $cache_key, $info, 24 * HOUR_IN_SECONDS );
        
        return $info;
    }

    /**
     * Clear all update caches when user clicks "Check Again".
     */
    public function clear_update_cache() {
        // Clear plugin update caches
        foreach ( $this->products as $product_slug => $plugin_file ) {
            delete_transient( 'vh360_update_check_' . $product_slug );
            delete_transient( 'vh360_plugin_info_' . $product_slug );
        }
        
        // Clear theme update cache
        delete_transient( 'vh360_update_check_' . $this->theme_slug );
        delete_transient( 'vh360_theme_info_' . $this->theme_slug );
        
        // Clear WordPress core update transients
        delete_site_transient( 'update_plugins' );
        delete_site_transient( 'update_themes' );
    }
}
