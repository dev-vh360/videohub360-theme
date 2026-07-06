<?php
/**
 * Main Studio plugin runtime.
 *
 * @package VH360_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VH360_Studio_Plugin {
    private static $instance;
    private $registry;
    private $jobs;

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        VH360_Studio_Database::maybe_install();
        $this->registry = new VH360_Studio_Provider_Registry();
        $this->jobs     = new VH360_Studio_Recording_Jobs( $this->registry );
        new VH360_Studio_Assets( $this->registry );
        new VH360_Studio_Media_Admin();

        add_filter( 'vh360_dashboard_tabs_registry', array( $this, 'register_dashboard_tab' ), 20, 2 );
        VH360_Studio_Recording_Cleanup::schedule();
        add_action( VH360_Studio_Recording_Cleanup::HOOK, array( new VH360_Studio_Recording_Cleanup( $this->jobs ), 'run' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function jobs() {
        return $this->jobs;
    }

    public function registry() {
        return $this->registry;
    }

    public function register_rest_routes() {
        ( new VH360_Studio_REST_Controller( $this->jobs ) )->register_routes();
    }

    public function register_dashboard_tab( $tabs, $user_id ) {
        $tabs['studio'] = array(
            'label'                => __( 'Studio', 'videohub360-studio' ),
            'label_callback'       => null,
            'show_callback'        => function( $callback_user_id = null ) {
                return VH360_Studio_Permissions::user_can_access_studio( $callback_user_id );
            },
            'show_in_menu_builder' => true,
            'content_callback'     => array( $this, 'render_dashboard_tab' ),
            'icon_svg'             => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M7 5V3h10v2"></path></svg>',
        );
        return $tabs;
    }

    public function render_dashboard_tab( $user_id ) {
        if ( ! VH360_Studio_Permissions::user_can_access_studio( $user_id ) ) {
            echo '<p>' . esc_html__( 'You do not have permission to access VH360 Studio.', 'videohub360-studio' ) . '</p>';
            return;
        }

        $jobs            = $this->jobs->list( $user_id, 10 );
        $registry        = $this->registry;
        $default_preset  = VH360_Studio_Quality_Presets::DEFAULT_PRESET;
        $quality_presets = VH360_Studio_Quality_Presets::get_presets();

        include VH360_STUDIO_TEMPLATES_DIR . 'dashboard-studio.php';
    }
}
