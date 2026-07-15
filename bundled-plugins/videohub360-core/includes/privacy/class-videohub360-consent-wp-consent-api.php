<?php
if (!defined('ABSPATH')) exit;

class VideoHub360_Consent_WP_Consent_API {
    private $manager;
    private $map = array(
        'necessary'   => 'functional',
        'preferences' => 'preferences',
        'analytics'   => 'statistics-anonymous',
        'advertising' => 'marketing',
    );

    public function __construct($manager) {
        $this->manager = $manager;
        $plugin = defined('VIDEOHUB360_PLUGIN_FILE') ? plugin_basename(VIDEOHUB360_PLUGIN_FILE) : 'videohub360/videohub360.php';
        add_filter('wp_consent_api_registered_' . $plugin, '__return_true');
        add_filter('wp_get_consent_type', array($this, 'get_consent_type'));
        add_filter('videohub360_wp_consent_api_map', array($this, 'get_map'));
        add_filter('videohub360_consent_state', array($this, 'maybe_apply_external_override'), 20, 3);
        add_action('videohub360_consent_changed', array($this, 'sync_to_wp_consent_api'), 10, 2);
    }

    public function get_map() {
        return $this->map;
    }

    public function get_consent_type($type = '') {
        $settings = $this->manager ? $this->manager->get_settings() : array();
        $mode = isset($settings['mode']) ? $settings['mode'] : 'disabled';
        if ('strict' === $mode) {
            return 'optin';
        }
        if ('opt_out' === $mode) {
            return 'optout';
        }
        return $type;
    }

    public function maybe_apply_external_override($state, $settings, $cookie) {
        if (!apply_filters('videohub360_wp_consent_api_external_source_enabled', false, $state, $settings, $cookie)) {
            return $state;
        }
        if (!function_exists('wp_has_consent')) {
            return $state;
        }
        foreach ($this->map as $vh360_category => $wp_category) {
            if ('necessary' === $vh360_category) {
                continue;
            }
            $state['choices'][$vh360_category] = (bool) wp_has_consent($wp_category);
        }
        if (!empty($state['gpc'])) {
            $state['choices']['advertising'] = false;
        }
        return $state;
    }

    public function sync_to_wp_consent_api($new_state, $old_state) {
        if (!function_exists('wp_set_consent') || empty($new_state['choices']) || !apply_filters('videohub360_wp_consent_api_sync_enabled', true, $new_state, $old_state)) {
            return;
        }
        foreach ($this->map as $vh360_category => $wp_category) {
            $granted = 'necessary' === $vh360_category || !empty($new_state['choices'][$vh360_category]);
            wp_set_consent($wp_category, $granted ? 'allow' : 'deny');
        }
    }
}
