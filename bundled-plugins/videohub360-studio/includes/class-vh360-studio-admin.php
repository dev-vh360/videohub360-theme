<?php
/**
 * Studio admin settings and diagnostics.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VH360_Studio_Admin {
    private $registry;
    private $jobs;

    public function __construct( VH360_Studio_Provider_Registry $registry, VH360_Studio_Recording_Jobs $jobs ) {
        $this->registry = $registry;
        $this->jobs     = $jobs;
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_vh360_studio_test_publitio_connection', array( $this, 'test_publitio_connection' ) );
        add_action( 'admin_post_vh360_studio_test_publitio_upload', array( $this, 'test_publitio_upload' ) );
        add_action( 'admin_post_vh360_studio_test_publitio_direct_upload_setup', array( $this, 'test_publitio_direct_upload_setup' ) );
        add_filter( 'vh360_studio_temp_recording_retention_days', array( $this, 'filter_retention_days' ) );
        add_filter( 'vh360_studio_max_total_recording_size', array( $this, 'filter_max_total_size' ) );
    }

    public function admin_menu() {
        $cap = 'manage_options';
        if ( menu_page_url( 'videohub360', false ) ) {
            add_submenu_page( 'videohub360', __( 'Studio', 'videohub360-studio' ), __( 'Studio', 'videohub360-studio' ), $cap, 'vh360-studio-settings', array( $this, 'render_page' ) );
            return;
        }
        add_menu_page( __( 'VH360 Studio', 'videohub360-studio' ), __( 'VH360 Studio', 'videohub360-studio' ), $cap, 'vh360-studio-settings', array( $this, 'render_page' ), 'dashicons-video-alt3', 58 );
    }

    public function register_settings() {
        $settings = array(
            'vh360_studio_default_replay_storage_provider' => array( $this, 'sanitize_provider' ),
            'vh360_studio_publitio_api_key'               => array( $this, 'sanitize_api_key' ),
            'vh360_studio_publitio_api_secret'            => array( $this, 'sanitize_secret' ),
            'vh360_studio_publitio_upload_mode'           => array( $this, 'sanitize_upload_mode' ),
            'vh360_studio_publitio_upload_preset_id'      => 'sanitize_text_field',
            'vh360_studio_publitio_folder'                => 'sanitize_text_field',
            'vh360_studio_publitio_privacy'               => array( $this, 'sanitize_privacy' ),
            'vh360_studio_publitio_option_download'       => array( $this, 'sanitize_bool_option' ),
            'vh360_studio_publitio_option_hls'            => array( $this, 'sanitize_bool_option' ),
            'vh360_studio_publitio_player_id'             => 'sanitize_text_field',
            'vh360_studio_publitio_adtag_id'              => 'sanitize_text_field',
            'vh360_studio_local_media_fallback_enabled'   => array( $this, 'sanitize_bool_option' ),
            'vh360_studio_temp_retention_days'            => array( $this, 'sanitize_retention_days' ),
            'vh360_studio_max_total_recording_size'       => 'absint',
        );
        foreach ( $settings as $name => $sanitize ) {
            register_setting( 'vh360_studio_settings', $name, array( 'sanitize_callback' => $sanitize ) );
        }
    }

    public function sanitize_provider( $value ) {
        $value = sanitize_key( $value );
        return $this->registry->has_storage_provider( $value ) ? $value : 'videopress';
    }

    public function sanitize_api_key( $value ) {
        $new = sanitize_text_field( $value );
        if ( $new !== get_option( 'vh360_studio_publitio_api_key', '' ) ) {
            $this->clear_publitio_connection_status();
        }
        return $new;
    }

    public function sanitize_secret( $value ) {
        $value = (string) $value;
        if ( '' === trim( $value ) || '********' === $value ) {
            return get_option( 'vh360_studio_publitio_api_secret', '' );
        }
        $new = sanitize_text_field( $value );
        if ( $new !== get_option( 'vh360_studio_publitio_api_secret', '' ) ) {
            $this->clear_publitio_connection_status();
        }
        return $new;
    }

    private function clear_publitio_connection_status() {
        delete_option( 'vh360_studio_publitio_last_tested_at' );
        delete_option( 'vh360_studio_publitio_last_status' );
        delete_option( 'vh360_studio_publitio_last_error' );
    }

    public function sanitize_privacy( $value ) { return 'private' === sanitize_key( $value ) ? 'private' : 'public'; }
    public function sanitize_upload_mode( $value ) { return 'direct_browser' === sanitize_key( $value ) ? 'direct_browser' : 'server_relay'; }
    public function sanitize_bool_option( $value ) { return rest_sanitize_boolean( $value ) ? '1' : '0'; }
    public function sanitize_retention_days( $value ) { return max( 1, min( 30, absint( $value ) ) ); }
    public function filter_retention_days( $days ) { return absint( get_option( 'vh360_studio_temp_retention_days', $days ) ); }
    public function filter_max_total_size( $bytes ) { $saved = absint( get_option( 'vh360_studio_max_total_recording_size', 0 ) ); return $saved ? $saved : $bytes; }

    public function test_publitio_connection() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to manage Studio settings.', 'videohub360-studio' ) ); }
        check_admin_referer( 'vh360_studio_test_publitio_connection' );
        $provider = $this->registry->get_storage_provider( 'publitio' );
        $tested   = current_time( 'mysql' );
        $status   = 'failed';
        $error    = '';
        if ( $provider && method_exists( $provider, 'test_connection' ) ) {
            $result = $provider->test_connection();
            if ( is_wp_error( $result ) ) { $error = sanitize_text_field( $result->get_error_message() ); } else { $status = 'success'; }
        } else {
            $error = __( 'Publitio provider is unavailable.', 'videohub360-studio' );
        }
        update_option( 'vh360_studio_publitio_last_tested_at', $tested );
        update_option( 'vh360_studio_publitio_last_status', $status );
        update_option( 'vh360_studio_publitio_last_error', $error );
        wp_safe_redirect( add_query_arg( array( 'page'=>'vh360-studio-settings', 'publitio_test'=>$status ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function test_publitio_upload() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to manage Studio settings.', 'videohub360-studio' ) ); }
        check_admin_referer( 'vh360_studio_test_publitio_upload' );
        $tmp = wp_tempnam( 'vh360-studio-publitio-test.png' );
        $status = 'failed';
        $error = '';
        $png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true );
        if ( ! $tmp || ! $png || false === file_put_contents( $tmp, $png ) ) {
            $error = __( 'Unable to create a local Publitio upload test file.', 'videohub360-studio' );
        } else {
            $client = new VH360_Studio_Publitio_Client();
            $result = $client->create_file( $tmp, array( 'title' => 'VH360 Studio upload test', 'privacy' => '0', 'option_download' => '0', 'option_hls' => '0', 'option_ad' => '0' ), 'image/png', 'vh360-studio-publitio-test.png' );
            if ( is_wp_error( $result ) ) {
                $error = sanitize_text_field( $result->get_error_message() );
            } else {
                $status = 'success';
            }
        }
        if ( $tmp && file_exists( $tmp ) ) { @unlink( $tmp ); }
        update_option( 'vh360_studio_publitio_last_upload_tested_at', current_time( 'mysql' ) );
        update_option( 'vh360_studio_publitio_last_upload_status', $status );
        update_option( 'vh360_studio_publitio_last_upload_error', $error );
        wp_safe_redirect( add_query_arg( array( 'page'=>'vh360-studio-settings', 'publitio_upload_test'=>$status ), admin_url( 'admin.php' ) ) );
        exit;
    }


    public function test_publitio_direct_upload_setup() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to manage Studio settings.', 'videohub360-studio' ) ); }
        check_admin_referer( 'vh360_studio_test_publitio_direct_upload_setup' );
        $status = 'failed';
        $error  = '';
        $preset = sanitize_text_field( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) );
        $client = new VH360_Studio_Publitio_Client();
        if ( ! $preset ) {
            $error = __( 'Publitio Upload Preset ID is required for direct browser uploads.', 'videohub360-studio' );
        } elseif ( ! $client->has_credentials() ) {
            $error = __( 'Publitio API credentials are required so WordPress can verify direct uploads.', 'videohub360-studio' );
        } elseif ( 'success' !== sanitize_key( get_option( 'vh360_studio_publitio_last_status', '' ) ) ) {
            $error = __( 'Run a successful Publitio connection test before enabling direct upload.', 'videohub360-studio' );
        } else {
            $status = 'success';
        }
        update_option( 'vh360_studio_publitio_direct_last_tested_at', current_time( 'mysql' ) );
        update_option( 'vh360_studio_publitio_direct_last_status', $status );
        update_option( 'vh360_studio_publitio_direct_last_error', sanitize_text_field( $error ) );
        wp_safe_redirect( add_query_arg( array( 'page'=>'vh360-studio-settings', 'publitio_direct_test'=>$status ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $providers = $this->registry->get_storage_providers();
        ?>
        <div class="wrap vh360-studio-admin">
            <h1><?php esc_html_e( 'VH360 Studio Settings', 'videohub360-studio' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'vh360_studio_settings' ); ?>
                <h2><?php esc_html_e( 'Replay Provider Settings', 'videohub360-studio' ); ?></h2>
                <table class="form-table" role="presentation"><tbody>
                    <tr><th><label for="vh360-default-provider"><?php esc_html_e( 'Default replay storage provider', 'videohub360-studio' ); ?></label></th><td><select id="vh360-default-provider" name="vh360_studio_default_replay_storage_provider"><?php foreach ( $providers as $id => $provider ) : ?><option value="<?php echo esc_attr( $id ); ?>" <?php selected( get_option( 'vh360_studio_default_replay_storage_provider', 'videopress' ), $id ); ?>><?php echo esc_html( $provider->get_label() ); ?></option><?php endforeach; ?></select></td></tr>
                    <?php $this->text_row( 'vh360_studio_publitio_api_key', __( 'Publitio API Key', 'videohub360-studio' ) ); ?>
                    <tr><th><?php esc_html_e( 'Publitio upload mode', 'videohub360-studio' ); ?></th><td><select name="vh360_studio_publitio_upload_mode"><option value="server_relay" <?php selected( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ), 'server_relay' ); ?>><?php esc_html_e( 'Server relay upload', 'videohub360-studio' ); ?></option><option value="direct_browser" <?php selected( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ), 'direct_browser' ); ?>><?php esc_html_e( 'Direct browser upload', 'videohub360-studio' ); ?></option></select><p class="description"><?php esc_html_e( 'Server relay remains the fallback. Direct browser upload requires an unsigned Publitio Upload Preset.', 'videohub360-studio' ); ?></p></td></tr>
                    <tr><th><label for="vh360_studio_publitio_upload_preset_id"><?php esc_html_e( 'Publitio Upload Preset ID', 'videohub360-studio' ); ?></label></th><td><input type="text" class="regular-text" id="vh360_studio_publitio_upload_preset_id" name="vh360_studio_publitio_upload_preset_id" value="<?php echo esc_attr( get_option( 'vh360_studio_publitio_upload_preset_id', '' ) ); ?>"><p class="description"><?php esc_html_e( 'Required for direct browser uploads. Create an Upload Preset in Publitio and enable unsigned uploads for that preset. The preset ID is visible to authorized Studio users; do not enter an API secret here.', 'videohub360-studio' ); ?></p></td></tr>
                    <tr><th><label for="vh360_studio_publitio_api_secret"><?php esc_html_e( 'Publitio API Secret', 'videohub360-studio' ); ?></label></th><td><input type="password" id="vh360_studio_publitio_api_secret" name="vh360_studio_publitio_api_secret" value="<?php echo esc_attr( get_option( 'vh360_studio_publitio_api_secret', '' ) ? '********' : '' ); ?>" autocomplete="new-password"><p class="description"><?php esc_html_e( 'Saved secrets are masked. Enter a new value only to replace it.', 'videohub360-studio' ); ?></p></td></tr>
                    <?php $this->text_row( 'vh360_studio_publitio_folder', __( 'Publitio Folder ID', 'videohub360-studio' ) ); ?>
                    <tr><th><?php esc_html_e( 'Publitio privacy', 'videohub360-studio' ); ?></th><td><select name="vh360_studio_publitio_privacy"><option value="public" <?php selected( get_option( 'vh360_studio_publitio_privacy', 'public' ), 'public' ); ?>><?php esc_html_e( 'Public', 'videohub360-studio' ); ?></option><option value="private" <?php selected( get_option( 'vh360_studio_publitio_privacy', 'public' ), 'private' ); ?>><?php esc_html_e( 'Private', 'videohub360-studio' ); ?></option></select></td></tr>
                    <?php $this->checkbox_row( 'vh360_studio_publitio_option_download', __( 'Publitio downloads enabled', 'videohub360-studio' ) ); ?>
                    <?php $this->checkbox_row( 'vh360_studio_publitio_option_hls', __( 'Publitio HLS enabled', 'videohub360-studio' ) ); ?>
                    <?php $this->text_row( 'vh360_studio_publitio_player_id', __( 'Publitio Player ID', 'videohub360-studio' ) ); ?>
                    <?php $this->text_row( 'vh360_studio_publitio_adtag_id', __( 'Publitio Adtag ID', 'videohub360-studio' ) ); ?>
                    <?php $this->checkbox_row( 'vh360_studio_local_media_fallback_enabled', __( 'Local Media fallback enabled', 'videohub360-studio' ), true ); ?>
                    <tr><th><label for="vh360_studio_temp_retention_days"><?php esc_html_e( 'Temporary recording retention days', 'videohub360-studio' ); ?></label></th><td><input type="number" min="1" max="30" id="vh360_studio_temp_retention_days" name="vh360_studio_temp_retention_days" value="<?php echo esc_attr( get_option( 'vh360_studio_temp_retention_days', 3 ) ); ?>"></td></tr>
                    <tr><th><label for="vh360_studio_max_total_recording_size"><?php esc_html_e( 'Maximum Studio recording upload size (bytes)', 'videohub360-studio' ); ?></label></th><td><input type="number" min="1048576" id="vh360_studio_max_total_recording_size" name="vh360_studio_max_total_recording_size" value="<?php echo esc_attr( get_option( 'vh360_studio_max_total_recording_size', '' ) ); ?>"><p class="description"><?php esc_html_e( 'Leave blank to use the Studio default.', 'videohub360-studio' ); ?></p></td></tr>
                </tbody></table>
                <?php submit_button(); ?>
            </form>
            <?php $this->render_connection_test(); ?>
            <?php $this->render_provider_status(); ?>
            <?php $this->render_server_readiness(); ?>
            <?php $this->render_recent_jobs(); ?>
        </div>
        <?php
    }

    private function text_row( $option, $label ) { ?><tr><th><label for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label></th><td><input type="text" class="regular-text" id="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $option ); ?>" value="<?php echo esc_attr( get_option( $option, '' ) ); ?>"></td></tr><?php }
    private function checkbox_row( $option, $label, $default = false ) { ?><tr><th><?php echo esc_html( $label ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( $option ); ?>" value="1" <?php checked( get_option( $option, $default ? '1' : '0' ), '1' ); ?>> <?php esc_html_e( 'Enabled', 'videohub360-studio' ); ?></label></td></tr><?php }

    private function render_connection_test() { ?>
        <h2><?php esc_html_e( 'Publitio Connection and Upload Tests', 'videohub360-studio' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;"><?php wp_nonce_field( 'vh360_studio_test_publitio_connection' ); ?><input type="hidden" name="action" value="vh360_studio_test_publitio_connection"><?php submit_button( __( 'Test Publitio Connection', 'videohub360-studio' ), 'secondary', 'submit', false ); ?></form>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;"><?php wp_nonce_field( 'vh360_studio_test_publitio_upload' ); ?><input type="hidden" name="action" value="vh360_studio_test_publitio_upload"><?php submit_button( __( 'Test Publitio Upload', 'videohub360-studio' ), 'secondary', 'submit', false ); ?></form>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;"><?php wp_nonce_field( 'vh360_studio_test_publitio_direct_upload_setup' ); ?><input type="hidden" name="action" value="vh360_studio_test_publitio_direct_upload_setup"><?php submit_button( __( 'Test Publitio Direct Upload Setup', 'videohub360-studio' ), 'secondary', 'submit', false ); ?></form>
    <?php }

    private function render_provider_status() {
        echo '<h2>' . esc_html__( 'Provider Status Checks', 'videohub360-studio' ) . '</h2><div class="vh360-studio-provider-status">';
        foreach ( $this->registry->get_storage_providers() as $id => $provider ) {
            echo '<div class="postbox" style="padding:12px;margin-bottom:12px;"><h3>' . esc_html( $provider->get_label() ) . '</h3>';
            echo '<p><strong>' . esc_html__( 'Available:', 'videohub360-studio' ) . '</strong> ' . esc_html( $provider->is_available() ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ) ) . '</p>';
            echo '<p><strong>' . esc_html__( 'Supports publishing:', 'videohub360-studio' ) . '</strong> ' . esc_html( $provider->supports_publish() ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ) ) . '</p>';
            if ( 'publitio' === $id ) { echo '<p>' . esc_html( $this->publitio_status_message() ) . '</p>'; }
            if ( 'local_media' === $id ) { echo '<p>' . esc_html__( 'Local Media saves the replay to the WordPress Media Library. This is a fallback for shorter recordings and development/testing. VideoPress or Publitio is recommended for long replays.', 'videohub360-studio' ) . '</p>'; }
            echo '</div>';
        }
        echo '</div>';
    }

    private function publitio_status_message() {
        $key = get_option( 'vh360_studio_publitio_api_key', '' ) ? __( 'API key saved', 'videohub360-studio' ) : __( 'API key missing', 'videohub360-studio' );
        $secret = get_option( 'vh360_studio_publitio_api_secret', '' ) ? __( 'API secret saved (masked)', 'videohub360-studio' ) : __( 'API secret missing', 'videohub360-studio' );
        $last = get_option( 'vh360_studio_publitio_last_tested_at', __( 'Never tested', 'videohub360-studio' ) );
        $status = get_option( 'vh360_studio_publitio_last_status', __( 'unknown', 'videohub360-studio' ) );
        $error = get_option( 'vh360_studio_publitio_last_error', '' );
        $upload_last = get_option( 'vh360_studio_publitio_last_upload_tested_at', __( 'Never tested', 'videohub360-studio' ) );
        $upload_status = get_option( 'vh360_studio_publitio_last_upload_status', __( 'unknown', 'videohub360-studio' ) );
        $upload_error = get_option( 'vh360_studio_publitio_last_upload_error', '' );
        $direct_mode = 'direct_browser' === sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) ) ? __( 'Direct browser upload enabled', 'videohub360-studio' ) : __( 'Server relay upload enabled', 'videohub360-studio' );
        $preset = get_option( 'vh360_studio_publitio_upload_preset_id', '' ) ? __( 'Upload preset ID saved', 'videohub360-studio' ) : __( 'Upload preset ID missing', 'videohub360-studio' );
        $direct_last = get_option( 'vh360_studio_publitio_direct_last_tested_at', __( 'Never tested', 'videohub360-studio' ) );
        $direct_status = get_option( 'vh360_studio_publitio_direct_last_status', __( 'unknown', 'videohub360-studio' ) );
        $direct_error = get_option( 'vh360_studio_publitio_direct_last_error', '' );
        return trim( $key . '. ' . $secret . '. ' . $direct_mode . '. ' . $preset . '. ' . sprintf( __( 'Last connection test: %1$s (%2$s).', 'videohub360-studio' ), $last, $status ) . ' ' . ( $error ? sprintf( __( 'Last connection error: %s', 'videohub360-studio' ), wp_html_excerpt( $error, 160, '…' ) ) : '' ) . ' ' . sprintf( __( 'Last upload test: %1$s (%2$s).', 'videohub360-studio' ), $upload_last, $upload_status ) . ' ' . ( $upload_error ? sprintf( __( 'Last upload error: %s', 'videohub360-studio' ), wp_html_excerpt( $upload_error, 160, '…' ) ) : '' ) . ' ' . sprintf( __( 'Last direct upload setup test: %1$s (%2$s).', 'videohub360-studio' ), $direct_last, $direct_status ) . ' ' . ( $direct_error ? sprintf( __( 'Last direct upload setup error: %s', 'videohub360-studio' ), wp_html_excerpt( $direct_error, 160, '…' ) ) : '' ) );
    }

    private function render_server_readiness() {
        $chunks = new VH360_Studio_Recording_Chunks( $this->jobs );
        $settings = $chunks->upload_settings();
        $uploads = wp_upload_dir();
        $tmp = $chunks->get_base_directory();
        echo '<h2>' . esc_html__( 'Server Readiness Checks', 'videohub360-studio' ) . '</h2><table class="widefat striped"><tbody>';
        $rows = array(
            __( 'PHP upload max filesize', 'videohub360-studio' ) => ini_get( 'upload_max_filesize' ),
            __( 'PHP post max size', 'videohub360-studio' ) => ini_get( 'post_max_size' ),
            __( 'PHP max execution time', 'videohub360-studio' ) => ini_get( 'max_execution_time' ),
            __( 'Memory limit', 'videohub360-studio' ) => ini_get( 'memory_limit' ),
            __( 'Uploads path writable', 'videohub360-studio' ) => ( empty( $uploads['error'] ) && wp_is_writable( $uploads['basedir'] ) ) ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ),
            __( 'Studio temp path writable', 'videohub360-studio' ) => ( wp_mkdir_p( $tmp ) && wp_is_writable( $tmp ) ) ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ),
            __( 'Free disk space', 'videohub360-studio' ) => function_exists( 'disk_free_space' ) && @disk_free_space( $uploads['basedir'] ) ? size_format( @disk_free_space( $uploads['basedir'] ) ) : __( 'Unavailable', 'videohub360-studio' ),
            __( 'Max chunk size', 'videohub360-studio' ) => size_format( $settings['max_chunk_size'] ),
            __( 'Max recording size', 'videohub360-studio' ) => size_format( $settings['max_total_recording_size'] ),
            __( 'Cleanup retention window', 'videohub360-studio' ) => sprintf( _n( '%d day', '%d days', absint( get_option( 'vh360_studio_temp_retention_days', 3 ) ), 'videohub360-studio' ), absint( get_option( 'vh360_studio_temp_retention_days', 3 ) ) ),
            __( 'cURL extension available', 'videohub360-studio' ) => function_exists( 'curl_init' ) ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ),
            __( 'CURLFile available', 'videohub360-studio' ) => class_exists( 'CURLFile' ) ? __( 'Yes', 'videohub360-studio' ) : __( 'No', 'videohub360-studio' ),
            __( 'Publitio upload timeout', 'videohub360-studio' ) => sprintf( __( '%d seconds', 'videohub360-studio' ), (int) apply_filters( 'vh360_studio_publitio_upload_timeout', 300 ) ),
            __( 'Publitio direct upload mode', 'videohub360-studio' ) => 'direct_browser' === sanitize_key( get_option( 'vh360_studio_publitio_upload_mode', 'server_relay' ) ) ? __( 'Enabled', 'videohub360-studio' ) : __( 'Disabled', 'videohub360-studio' ),
            __( 'Publitio Upload Preset ID', 'videohub360-studio' ) => get_option( 'vh360_studio_publitio_upload_preset_id', '' ) ? __( 'Configured; visible to authorized Studio users', 'videohub360-studio' ) : __( 'Missing', 'videohub360-studio' ),
            __( 'Direct upload fallback', 'videohub360-studio' ) => __( 'Server relay remains available when WordPress chunks are uploaded.', 'videohub360-studio' ),
        );
        foreach ( $rows as $label => $value ) { echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>'; }
        echo '</tbody></table>';
    }

    private function render_recent_jobs() {
        $jobs = $this->jobs->list( get_current_user_id(), 20 );
        echo '<h2>' . esc_html__( 'Recent Publish Diagnostics', 'videohub360-studio' ) . '</h2><table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__( 'Creator', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Provider', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Job Status', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Publish Status', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Created', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Updated', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Last Error', 'videohub360-studio' ) . '</th><th>' . esc_html__( 'Replay', 'videohub360-studio' ) . '</th></tr></thead><tbody>';
        foreach ( $jobs as $job ) {
            if ( ! in_array( $job['status'], array( 'failed', 'processing', 'ready' ), true ) ) { continue; }
            $user = get_userdata( absint( $job['user_id'] ) );
            $replay = ! empty( $job['replay_video_id'] ) ? get_permalink( absint( $job['replay_video_id'] ) ) : '';
            echo '<tr><td>' . esc_html( $job['id'] ) . '</td><td>' . esc_html( $user ? $user->display_name : $job['user_id'] ) . '</td><td>' . esc_html( $job['storage_provider'] ) . '</td><td>' . esc_html( $job['status'] ) . '</td><td>' . esc_html( $job['publish_provider_status'] ?: '—' ) . '</td><td>' . esc_html( $job['created_at'] ) . '</td><td>' . esc_html( $job['updated_at'] ) . '</td><td>' . esc_html( wp_html_excerpt( $job['error_message'] ?: '—', 120, '…' ) ) . '</td><td>' . ( $replay ? '<a href="' . esc_url( $replay ) . '">' . esc_html__( 'Open', 'videohub360-studio' ) . '</a>' : '—' ) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
