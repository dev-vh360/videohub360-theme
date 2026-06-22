<?php
/**
 * Stripe Bootstrap
 *
 * Handles Stripe API configuration and provides a centralized
 * access point for Stripe settings.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Stripe_Bootstrap {
    
    /**
     * Singleton instance
     *
     * @var VH360_Stripe_Bootstrap
     */
    private static $instance = null;
    
    /**
     * Stripe settings cache
     *
     * @var array
     */
    private $settings = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Stripe_Bootstrap
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
        // Nothing to hook — settings are loaded on demand
    }
    
    /**
     * Get all Stripe settings
     *
     * @return array
     */
    public function get_settings() {
        if ($this->settings === null) {
            $this->settings = get_option('vh360_stripe_settings', array());
        }
        return $this->settings;
    }
    
    /**
     * Check if Stripe recurring billing is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        $settings = $this->get_settings();
        return !empty($settings['enable_recurring']);
    }
    
    /**
     * Check if Stripe is in test mode
     *
     * @return bool
     */
    public function is_test_mode() {
        $settings = $this->get_settings();
        return !empty($settings['test_mode']);
    }
    
    /**
     * Get the active secret key (test or live)
     *
     * @return string
     */
    public function get_secret_key() {
        $settings = $this->get_settings();
        
        if ($this->is_test_mode()) {
            return isset($settings['test_secret_key']) ? $settings['test_secret_key'] : '';
        }
        
        return isset($settings['secret_key']) ? $settings['secret_key'] : '';
    }
    
    /**
     * Get the active publishable key (test or live)
     *
     * @return string
     */
    public function get_publishable_key() {
        $settings = $this->get_settings();
        
        if ($this->is_test_mode()) {
            return isset($settings['test_publishable_key']) ? $settings['test_publishable_key'] : '';
        }
        
        return isset($settings['publishable_key']) ? $settings['publishable_key'] : '';
    }
    
    /**
     * Get the webhook signing secret
     *
     * @return string
     */
    public function get_webhook_secret() {
        $settings = $this->get_settings();
        return isset($settings['webhook_secret']) ? $settings['webhook_secret'] : '';
    }
    
    /**
     * Check if customer portal is enabled
     *
     * @return bool
     */
    public function is_portal_enabled() {
        $settings = $this->get_settings();
        return !empty($settings['enable_portal']);
    }
    
    /**
     * Get default cancellation behavior
     *
     * @return string 'at_period_end' or 'immediate'
     */
    public function get_cancellation_behavior() {
        $settings = $this->get_settings();
        return isset($settings['cancellation_behavior']) ? $settings['cancellation_behavior'] : 'at_period_end';
    }
    
    /**
     * Check if Stripe payment credentials are available.
     *
     * One-time payment features, such as VideoHub360 Giving, only need
     * usable API credentials and must not require recurring memberships
     * to be enabled.
     *
     * @return bool
     */
    public function has_payment_credentials() {
        return !empty($this->get_secret_key())
            && !empty($this->get_publishable_key());
    }

    /**
     * Check if Stripe recurring membership billing is properly configured.
     *
     * @return bool
     */
    public function is_configured() {
        return $this->is_enabled()
            && $this->has_payment_credentials();
    }
    
    /**
     * Make a Stripe API request
     *
     * Uses WordPress HTTP API instead of the Stripe PHP SDK
     * to avoid bundling the SDK with the theme.
     *
     * @param string $endpoint API endpoint (e.g., '/v1/customers')
     * @param array  $body     Request body
     * @param string $method   HTTP method
     * @return array|WP_Error Decoded response or WP_Error
     */
    public function api_request($endpoint, $body = array(), $method = 'POST') {
        $secret_key = $this->get_secret_key();
        
        if (empty($secret_key)) {
            return new WP_Error('stripe_not_configured', __('Stripe API key is not configured.', 'videohub360-memberships'));
        }
        
        $url = 'https://api.stripe.com' . $endpoint;
        
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
        );
        
        if (!empty($body) && in_array($method, array('POST', 'PATCH'), true)) {
            $args['body'] = $body;
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 400) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Stripe API error', 'videohub360-memberships');
            $error_type = isset($body['error']['type']) ? $body['error']['type'] : 'api_error';
            return new WP_Error('stripe_api_error', $error_message, array(
                'status' => $status_code,
                'type'   => $error_type,
                'body'   => $body,
            ));
        }
        
        return $body;
    }
    
    /**
     * Make a Stripe GET request
     *
     * @param string $endpoint API endpoint
     * @param array  $params   Query parameters
     * @return array|WP_Error
     */
    public function api_get($endpoint, $params = array()) {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        
        return $this->api_request($endpoint, array(), 'GET');
    }
    
    /**
     * Make a Stripe DELETE request
     *
     * @param string $endpoint API endpoint
     * @return array|WP_Error
     */
    public function api_delete($endpoint) {
        return $this->api_request($endpoint, array(), 'DELETE');
    }
}
