<?php
/**
 * Starter Sites Plugin Auto-Installer
 *
 * Handles automatic installation and activation of the bundled Starter Sites plugin.
 * This runs on theme activation to enable one-click onboarding flow.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * VH360 Starter Sites Installer Class
 */
class VH360_Starter_Sites_Installer {

    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file = 'videohub360-starter-sites/videohub360-starter-sites.php';

    /**
     * Bundled ZIP path
     *
     * @var string
     */
    private $zip_path;

    /**
     * Option name for run flag
     *
     * @var string
     */
    private $run_flag = 'vh360_run_starter_sites_install';

    /**
     * Option name for completion flag
     *
     * @var string
     */
    private $complete_flag = 'vh360_starter_sites_install_complete';

    /**
     * Option name for error storage
     *
     * @var string
     */
    private $error_flag = 'vh360_starter_sites_install_error';

    /**
     * Singleton instance
     *
     * @var VH360_Starter_Sites_Installer
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return VH360_Starter_Sites_Installer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->zip_path = get_template_directory() . '/bundled-plugins/videohub360-starter-sites.zip';

        // Set install flag on theme activation
        add_action('after_switch_theme', array($this, 'set_install_flag'));

        // Run installer on admin_init
        add_action('admin_init', array($this, 'maybe_install_plugin'));

        // Display admin notices for errors
        add_action('admin_notices', array($this, 'display_error_notice'));
    }

    /**
     * Set the install flag on theme activation
     */
    public function set_install_flag() {
        // Only set if not already complete
        if (!get_option($this->complete_flag)) {
            update_option($this->run_flag, 1);
            vh360_debug_log('VH360 Starter Sites Installer: Install flag set on theme activation');
        }
    }

    /**
     * Maybe install the plugin
     */
    public function maybe_install_plugin() {
        // Check if we should run
        if (!$this->should_run()) {
            return;
        }

        vh360_debug_log('VH360 Starter Sites Installer: Starting installation process');

        // Check if already active
        if ($this->is_plugin_active()) {
            $this->mark_complete();
            vh360_debug_log('VH360 Starter Sites Installer: Plugin already active, marked complete');
            return;
        }

        // Check if installed but inactive
        if ($this->is_plugin_installed()) {
            vh360_debug_log('VH360 Starter Sites Installer: Plugin installed, attempting activation');
            $this->activate_plugin();
            return;
        }

        // Install from ZIP
        vh360_debug_log('VH360 Starter Sites Installer: Plugin not found, attempting installation from ZIP');
        $this->install_plugin();
    }

    /**
     * Check if installer should run
     *
     * @return bool
     */
    private function should_run() {
        // Must be in admin
        if (!is_admin()) {
            return false;
        }

        // Must have install_plugins capability
        if (!current_user_can('install_plugins')) {
            return false;
        }

        // Must have run flag set
        if (!get_option($this->run_flag)) {
            return false;
        }

        // Must not be already complete
        if (get_option($this->complete_flag)) {
            delete_option($this->run_flag);
            return false;
        }

        return true;
    }

    /**
     * Check if plugin is active
     *
     * @return bool
     */
    private function is_plugin_active() {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($this->plugin_file);
    }

    /**
     * Check if plugin is installed
     *
     * @return bool
     */
    private function is_plugin_installed() {
        if (!function_exists('get_plugins')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        return isset($all_plugins[$this->plugin_file]);
    }

    /**
     * Activate the plugin
     */
    private function activate_plugin() {
        if (!function_exists('activate_plugin')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $result = activate_plugin($this->plugin_file);

        if (is_wp_error($result)) {
            $this->store_error('Activation failed: ' . $result->get_error_message());
            vh360_debug_log('VH360 Starter Sites Installer: Activation failed', array('error' => $result->get_error_message()));
        } else {
            $this->mark_complete();
            vh360_debug_log('VH360 Starter Sites Installer: Plugin activated successfully');
        }

        delete_option($this->run_flag);
    }

    /**
     * Install the plugin from ZIP
     */
    private function install_plugin() {
        // Verify ZIP exists
        if (!file_exists($this->zip_path)) {
            $this->store_error('Installation failed: Bundled plugin ZIP file not found at ' . $this->zip_path);
            delete_option($this->run_flag);
            vh360_debug_log('VH360 Starter Sites Installer: ZIP file not found', array('path' => $this->zip_path));
            return;
        }

        // Load WordPress upgrader classes
        if (!class_exists('Plugin_Upgrader')) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        // Use silent skin for background installation
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
        $skin = new WP_Ajax_Upgrader_Skin();

        // Create upgrader instance
        $upgrader = new Plugin_Upgrader($skin);

        // Install the plugin
        $result = $upgrader->install($this->zip_path);

        if (is_wp_error($result)) {
            $this->store_error('Installation failed: ' . $result->get_error_message());
            delete_option($this->run_flag);
            vh360_debug_log('VH360 Starter Sites Installer: Installation failed', array('error' => $result->get_error_message()));
            return;
        }

        if ($result === false) {
            $this->store_error('Installation failed: Unknown error during plugin installation');
            delete_option($this->run_flag);
            vh360_debug_log('VH360 Starter Sites Installer: Installation returned false');
            return;
        }

        vh360_debug_log('VH360 Starter Sites Installer: Plugin installed successfully, attempting activation');

        // Verify plugin file exists after install
        if (!$this->is_plugin_installed()) {
            $this->store_error('Installation completed but plugin file not found');
            delete_option($this->run_flag);
            vh360_debug_log('VH360 Starter Sites Installer: Plugin file not found after installation');
            return;
        }

        // Activate the newly installed plugin
        $this->activate_plugin();
    }

    /**
     * Mark installation as complete
     */
    private function mark_complete() {
        update_option($this->complete_flag, 1);
        delete_option($this->run_flag);
        delete_option($this->error_flag);
    }

    /**
     * Store error message
     *
     * @param string $message Error message
     */
    private function store_error($message) {
        update_option($this->error_flag, $message);
    }

    /**
     * Display error notice in admin
     */
    public function display_error_notice() {
        $error = get_option($this->error_flag);

        if (!$error) {
            return;
        }

        // Only show to users who can install plugins
        if (!current_user_can('install_plugins')) {
            return;
        }

        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>VH360 Starter Sites Installation Error:</strong></p>
            <p><?php echo esc_html($error); ?></p>
            <p>
                Please try installing the Starter Sites plugin manually from 
                <a href="<?php echo esc_url(admin_url('themes.php?page=tgmpa-install-plugins')); ?>">
                    Install Required Plugins
                </a>.
            </p>
        </div>
        <?php
    }
}

// Initialize the installer
VH360_Starter_Sites_Installer::get_instance();
