<?php
if (!defined('ABSPATH')) exit;

class VideoHub360_Consent_Manager {
    const OPTION = 'vh360_consent_settings';
    const COOKIE = 'vh360_consent';
    private static $instance = null;
    private $services;

    public static function get_instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->services = new VideoHub360_Consent_Services($this);
    }

    public function init() {
        add_action('init', array($this, 'register_default_services'), 2);
        add_action('wp_ajax_vh360_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_nopriv_vh360_save_consent', array($this, 'ajax_save_consent'));
        add_action('wp_ajax_vh360_activity_ad_markup', array($this, 'ajax_activity_ad_markup'));
        add_action('wp_ajax_nopriv_vh360_activity_ad_markup', array($this, 'ajax_activity_ad_markup'));
        add_action('wp_print_scripts', array($this, 'gate_registered_scripts'), 100);
    }

    public function get_settings() {
        $defaults = array(
            'mode' => 'disabled', 'policy_version' => '1', 'expiration_days' => 180,
            'banner_heading' => __('Your privacy choices', 'videohub360'),
            'banner_description' => __('We use necessary storage for site functionality and optional storage for preferences, measurement, and advertising choices.', 'videohub360'),
            'privacy_policy_page_id' => 0, 'privacy_policy_url' => '',
            'accept_all_text' => __('Accept All', 'videohub360'),
            'reject_optional_text' => __('Reject Optional', 'videohub360'),
            'manage_preferences_text' => __('Manage Preferences', 'videohub360'),
            'save_preferences_text' => __('Save Preferences', 'videohub360'),
            'privacy_choices_text' => __('Privacy Choices', 'videohub360'),
            'show_persistent_control' => 1, 'banner_position' => 'bottom',
        );
        $saved = get_option(self::OPTION, array());
        return wp_parse_args(is_array($saved) ? $saved : array(), $defaults);
    }

    public static function sanitize_settings($input) {
        $input = is_array($input) ? $input : array();
        $out = array();
        $out['mode'] = in_array(($input['mode'] ?? 'disabled'), array('disabled','notice','opt_out','strict'), true) ? $input['mode'] : 'disabled';
        $out['policy_version'] = sanitize_text_field($input['policy_version'] ?? '1');
        $out['expiration_days'] = max(1, min(730, absint($input['expiration_days'] ?? 180)));
        foreach (array('banner_heading','banner_description','accept_all_text','reject_optional_text','manage_preferences_text','save_preferences_text','privacy_choices_text') as $key) $out[$key] = sanitize_text_field($input[$key] ?? '');
        $out['privacy_policy_page_id'] = absint($input['privacy_policy_page_id'] ?? 0);
        $out['privacy_policy_url'] = esc_url_raw($input['privacy_policy_url'] ?? '');
        $out['show_persistent_control'] = empty($input['show_persistent_control']) ? 0 : 1;
        $out['banner_position'] = in_array(($input['banner_position'] ?? 'bottom'), array('bottom','top'), true) ? $input['banner_position'] : 'bottom';
        return $out;
    }

    public function is_enabled() { return 'disabled' !== $this->get_settings()['mode']; }
    public function is_notice_only() { return 'notice' === $this->get_settings()['mode']; }
    public function gpc_active() { return isset($_SERVER['HTTP_SEC_GPC']) && '1' === (string) $_SERVER['HTTP_SEC_GPC']; }

    public function get_categories() {
        return apply_filters('videohub360_consent_categories', array(
            'necessary' => array('label' => __('Necessary', 'videohub360'), 'always' => true),
            'preferences' => array('label' => __('Preferences', 'videohub360')),
            'analytics' => array('label' => __('Analytics', 'videohub360')),
            'advertising' => array('label' => __('Advertising', 'videohub360')),
        ));
    }

    public function decode_cookie($value = null) {
        $value = null === $value ? ($_COOKIE[self::COOKIE] ?? '') : $value;
        if (!is_string($value) || strlen($value) > 2048 || '' === $value) return array();
        $decoded = json_decode(wp_unslash($value), true);
        if (!is_array($decoded) || absint($decoded['version'] ?? 0) !== 1) return array();
        $choices = is_array($decoded['choices'] ?? null) ? $decoded['choices'] : array();
        return array(
            'version' => 1,
            'policy_version' => sanitize_text_field($decoded['policy_version'] ?? ''),
            'choices' => array(
                'preferences' => !empty($choices['preferences']),
                'analytics' => !empty($choices['analytics']),
                'advertising' => !empty($choices['advertising']),
            ),
            'gpc' => !empty($decoded['gpc']),
            'updated_at' => absint($decoded['updated_at'] ?? 0),
            'notice_acknowledged' => !empty($decoded['notice_acknowledged']),
        );
    }

    public function get_state($cookie_value = null) {
        $settings = $this->get_settings();
        $mode = $settings['mode'];
        $cookie = $this->decode_cookie($cookie_value);
        $valid_policy = !empty($cookie) && $cookie['policy_version'] === (string) $settings['policy_version'];
        $gpc = $this->gpc_active();
        $choices = array('necessary' => true, 'preferences' => true, 'analytics' => true, 'advertising' => true);
        if ('strict' === $mode) $choices = array('necessary' => true, 'preferences' => false, 'analytics' => false, 'advertising' => false);
        if ($valid_policy && isset($cookie['choices'])) foreach (array('preferences','analytics','advertising') as $cat) $choices[$cat] = !empty($cookie['choices'][$cat]);
        if ('disabled' === $mode || 'notice' === $mode) $choices = array('necessary' => true, 'preferences' => true, 'analytics' => true, 'advertising' => true);
        if ($gpc) $choices['advertising'] = false;
        $state = array('mode' => $mode, 'enabled' => 'disabled' !== $mode, 'policy_version' => (string) $settings['policy_version'], 'choices' => $choices, 'gpc' => $gpc, 'needs_choice' => ('disabled' !== $mode && (!$valid_policy || ('notice' === $mode && empty($cookie['notice_acknowledged'])))));
        return apply_filters('videohub360_consent_state', $state, $settings, $cookie);
    }

    public function has_consent($category, $cookie_value = null) {
        $category = sanitize_key($category);
        if ('necessary' === $category) return true;
        $state = $this->get_state($cookie_value);
        return !empty($state['choices'][$category]);
    }

    public function register_default_services() {
        $this->services->register('activity-feed-ad-slot', array('category'=>'advertising','label'=>__('Activity Feed advertisements','videohub360'),'description'=>__('Displays advertising configured by the site administrator.','videohub360')));
        $this->services->register('vh360-third-party-push-sdk', array('category'=>'preferences','label'=>__('Push notification setup','videohub360'),'description'=>__('Loads optional browser push subscription tools after a visitor chooses to use them.','videohub360')));
    }
    public function services() { return $this->services; }

    public function ajax_save_consent() {
        check_ajax_referer('vh360_consent_nonce', 'nonce');
        $settings = $this->get_settings();
        if ('disabled' === $settings['mode']) wp_send_json_success($this->get_state());
        $old = $this->get_state();
        $choices = isset($_POST['choices']) && is_array($_POST['choices']) ? wp_unslash($_POST['choices']) : array();
        $data = array('version'=>1,'policy_version'=>(string)$settings['policy_version'],'choices'=>array('preferences'=>!empty($choices['preferences']),'analytics'=>!empty($choices['analytics']),'advertising'=>!empty($choices['advertising'])),'gpc'=>$this->gpc_active(),'updated_at'=>time(),'notice_acknowledged'=>!empty($_POST['notice_acknowledged']));
        if ($data['gpc']) $data['choices']['advertising'] = false;
        $this->set_cookie($data);
        $_COOKIE[self::COOKIE] = wp_json_encode($data);
        $new = $this->get_state();
        do_action('videohub360_consent_changed', $new, $old);
        foreach (array('preferences','analytics','advertising') as $cat) { if (empty($old['choices'][$cat]) && !empty($new['choices'][$cat])) do_action('videohub360_consent_granted', $cat); if (!empty($old['choices'][$cat]) && empty($new['choices'][$cat])) do_action('videohub360_consent_revoked', $cat); }
        wp_send_json_success($new);
    }

    public function set_cookie($data) {
        $settings = $this->get_settings();
        $expires = time() + (DAY_IN_SECONDS * absint($settings['expiration_days']));
        setcookie(self::COOKIE, wp_json_encode($data), array('expires'=>$expires,'path'=>'/','domain'=>COOKIE_DOMAIN,'secure'=>is_ssl(),'httponly'=>false,'samesite'=>'Lax'));
    }

    public function ajax_activity_ad_markup() {
        check_ajax_referer('vh360_activity_ad_nonce', 'nonce');
        if (!$this->has_consent('advertising')) wp_send_json_error(array('message'=>'consent_required'), 403);
        ob_start();
        if (is_active_sidebar('activity-feed-ad')) dynamic_sidebar('activity-feed-ad');
        $html = ob_get_clean();
        wp_send_json_success(array('html'=>$html));
    }

    public function gate_registered_scripts() {
        $blocked = apply_filters('videohub360_consent_script_handles', array());
        foreach ($blocked as $handle => $category) if (!$this->has_consent($category)) wp_dequeue_script($handle);
    }

    public function frontend_config() { return array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('vh360_consent_nonce'),'activityAdNonce'=>wp_create_nonce('vh360_activity_ad_nonce'),'settings'=>$this->get_settings(),'state'=>$this->get_state(),'services'=>$this->services->all(),'cookieName'=>self::COOKIE); }
}
