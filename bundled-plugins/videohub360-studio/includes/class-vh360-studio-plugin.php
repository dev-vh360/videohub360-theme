<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Plugin {
    private static $instance;
    private $registry;
    private $jobs;
    public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
    private function __construct() {
        VH360_Studio_Database::maybe_install();
        $this->registry = new VH360_Studio_Provider_Registry();
        $this->jobs = new VH360_Studio_Recording_Jobs( $this->registry );
        add_filter( 'vh360_dashboard_tabs_registry', array( $this, 'register_dashboard_tab' ), 20, 2 );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }
    public function jobs() { return $this->jobs; }
    public function registry() { return $this->registry; }
    public function register_rest_routes() { ( new VH360_Studio_REST_Controller( $this->jobs ) )->register_routes(); }
    public function register_dashboard_tab( $tabs, $user_id ) {
        $tabs['studio'] = array(
            'label' => __( 'Studio', 'videohub360-studio' ),
            'label_callback' => null,
            'show_callback' => function() { return is_user_logged_in(); },
            'show_in_menu_builder' => true,
            'content_callback' => array( $this, 'render_dashboard_tab' ),
            'icon_svg' => '<svg class="vh360-dashboard-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="12" cy="12" r="3"></circle><path d="M7 5V3h10v2"></path></svg>',
        );
        return $tabs;
    }
    public function render_dashboard_tab( $user_id ) {
        $jobs = $this->jobs->list( $user_id, 10 );
        $registry = $this->registry;
        include VH360_STUDIO_TEMPLATES_DIR . 'dashboard-studio.php';
    }
}
