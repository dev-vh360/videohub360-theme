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
     * Plugin file reference for update checks.
     *
     * @var string
     */
    private $plugin_file = 'videohub360/videohub360.php';

    /**
     * Constructor.
     */
    public function __construct() {
        if ( is_admin() ) {
            $this->init_admin_hooks();
        }

        // Hook into WordPress' update system.
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_plugin_updates' ) );
        add_filter( 'plugins_api', array( $this, 'filter_plugin_info' ), 10, 3 );
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
            // No valid license, don't advertise updates.
            return $transient;
        }

        $license_key = $data['license_key'];
        $current_version = isset( $transient->checked[ $this->plugin_file ] ) ? $transient->checked[ $this->plugin_file ] : VIDEOHUB360_VERSION;

        $server_url = $this->get_license_server_url();
        $endpoint   = $server_url . '/wp-json/vh360/v1/update-check';

        $response = wp_remote_post( $endpoint, array(
            'timeout' => 15,
            'headers' => array( 'Accept' => 'application/json' ),
            'body'    => array(
                'license_key'     => $license_key,
                'current_version' => $current_version,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $transient;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code || empty( $body ) ) {
            return $transient;
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || empty( $data['new_version'] ) ) {
            // No update available or malformed response.
            return $transient;
        }

        $new_version  = $data['new_version'];
        $download_url = isset( $data['download_url'] ) ? $data['download_url'] : '';

        if ( ! $download_url ) {
            return $transient;
        }

        $update              = new stdClass();
        $update->slug        = 'videohub360';
        $update->plugin      = $this->plugin_file;
        $update->new_version = $new_version;
        $update->package     = $download_url;
        $update->url         = 'https://videohub360.com';

        $transient->response[ $this->plugin_file ] = $update;

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
        if ( 'plugin_information' !== $action || empty( $args->slug ) || 'videohub360' !== $args->slug ) {
            return $result;
        }

        // At minimum, return the existing result.
        // You can expand this later to pull changelog and details from your server.
        return $result;
    }
}
