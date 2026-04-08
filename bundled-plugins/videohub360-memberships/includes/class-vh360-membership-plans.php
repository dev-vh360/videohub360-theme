<?php
/**
 * Membership Plans Registry
 *
 * Central registry for membership plan definitions, WooCommerce product mapping,
 * and recurring billing configuration.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Plans {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Plans
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Plans
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
        // Add meta boxes to products
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('save_post', array($this, 'save_product_meta'), 10, 2);
    }
    
    /**
     * Get default plan registry
     *
     * Returns hardcoded defaults merged with admin-configured billing overrides
     * stored in vh360_membership_plan_config option.
     *
     * @return array
     */
    public static function get_plan_registry() {
        $plans = array(
            'basic_monthly' => array(
                'label' => __('Basic (Monthly)', 'videohub360-memberships'),
                'duration' => 30,
                'duration_unit' => 'days',
                'billing_mode' => 'one_time',
                'stripe_price_id' => '',
                'auto_renew' => false,
                'trial_days' => 0,
                'features' => array(),
                'display_label' => '',
                'display_price' => '',
                'display_description' => '',
                'display_features' => array(),
            ),
            'basic_yearly' => array(
                'label' => __('Basic (Yearly)', 'videohub360-memberships'),
                'duration' => 365,
                'duration_unit' => 'days',
                'billing_mode' => 'one_time',
                'stripe_price_id' => '',
                'auto_renew' => false,
                'trial_days' => 0,
                'features' => array(),
                'display_label' => '',
                'display_price' => '',
                'display_description' => '',
                'display_features' => array(),
            ),
            'pro_monthly' => array(
                'label' => __('Pro (Monthly)', 'videohub360-memberships'),
                'duration' => 30,
                'duration_unit' => 'days',
                'billing_mode' => 'one_time',
                'stripe_price_id' => '',
                'auto_renew' => false,
                'trial_days' => 0,
                'features' => array(),
                'display_label' => '',
                'display_price' => '',
                'display_description' => '',
                'display_features' => array(),
            ),
            'pro_yearly' => array(
                'label' => __('Pro (Yearly)', 'videohub360-memberships'),
                'duration' => 365,
                'duration_unit' => 'days',
                'billing_mode' => 'one_time',
                'stripe_price_id' => '',
                'auto_renew' => false,
                'trial_days' => 0,
                'features' => array(),
                'display_label' => '',
                'display_price' => '',
                'display_description' => '',
                'display_features' => array(),
            ),
            'lifetime' => array(
                'label' => __('Lifetime', 'videohub360-memberships'),
                'duration' => 0,
                'duration_unit' => 'lifetime',
                'billing_mode' => 'one_time',
                'stripe_price_id' => '',
                'auto_renew' => false,
                'trial_days' => 0,
                'features' => array(),
                'display_label' => '',
                'display_price' => '',
                'display_description' => '',
                'display_features' => array(),
            ),
        );
        
        // Merge with admin-stored plan configuration
        $stored_config = get_option('vh360_membership_plan_config', array());
        if (!empty($stored_config) && is_array($stored_config)) {
            foreach ($stored_config as $plan_key => $overrides) {
                if (isset($plans[$plan_key]) && is_array($overrides)) {
                    $plans[$plan_key] = array_merge($plans[$plan_key], $overrides);
                }
            }
        }
        
        return apply_filters('vh360_membership_plans', $plans);
    }
    
    /**
     * Get plan billing configuration
     *
     * @param string $plan_key Plan key
     * @return array|false Plan billing config or false
     */
    public static function get_plan_billing_config($plan_key) {
        $plans = self::get_plan_registry();
        
        if (!isset($plans[$plan_key])) {
            return false;
        }
        
        $plan = $plans[$plan_key];
        
        return array(
            'billing_mode'    => isset($plan['billing_mode']) ? $plan['billing_mode'] : 'one_time',
            'stripe_price_id' => isset($plan['stripe_price_id']) ? $plan['stripe_price_id'] : '',
            'auto_renew'      => isset($plan['auto_renew']) ? (bool) $plan['auto_renew'] : false,
            'trial_days'      => isset($plan['trial_days']) ? (int) $plan['trial_days'] : 0,
        );
    }
    
    /**
     * Get all recurring plans
     *
     * @return array Plans configured for recurring billing
     */
    public static function get_recurring_plans() {
        $plans = self::get_plan_registry();
        $recurring = array();
        
        foreach ($plans as $key => $plan) {
            if (isset($plan['billing_mode']) && $plan['billing_mode'] === 'recurring') {
                $recurring[$key] = $plan;
            }
        }
        
        return $recurring;
    }
    
    /**
     * Get plan key by Stripe price ID
     *
     * @param string $stripe_price_id Stripe price ID
     * @return string|false Plan key or false
     */
    public static function get_plan_key_by_stripe_price($stripe_price_id) {
        if (empty($stripe_price_id)) {
            return false;
        }
        
        $plans = self::get_plan_registry();
        
        foreach ($plans as $key => $plan) {
            if (isset($plan['stripe_price_id']) && $plan['stripe_price_id'] === $stripe_price_id) {
                return $key;
            }
        }
        
        return false;
    }
    
    /**
     * Save plan configuration (billing and frontend display)
     *
     * @param array $config Array of plan_key => config overrides
     * @return bool
     */
    public static function save_plan_config($config) {
        if (!is_array($config)) {
            return false;
        }
        
        $sanitized = array();
        $allowed_keys = array('billing_mode', 'stripe_price_id', 'auto_renew', 'trial_days', 'display_label', 'display_price', 'display_description', 'display_features');
        
        foreach ($config as $plan_key => $overrides) {
            $plan_key = sanitize_key($plan_key);
            $sanitized[$plan_key] = array();
            
            foreach ($overrides as $key => $value) {
                if (!in_array($key, $allowed_keys, true)) {
                    continue;
                }
                
                switch ($key) {
                    case 'billing_mode':
                        $sanitized[$plan_key][$key] = in_array($value, array('one_time', 'recurring'), true) ? $value : 'one_time';
                        break;
                    case 'stripe_price_id':
                        $sanitized[$plan_key][$key] = sanitize_text_field($value);
                        break;
                    case 'auto_renew':
                        $sanitized[$plan_key][$key] = (bool) $value;
                        break;
                    case 'trial_days':
                        $sanitized[$plan_key][$key] = absint($value);
                        break;
                    case 'display_label':
                    case 'display_price':
                        $sanitized[$plan_key][$key] = sanitize_text_field($value);
                        break;
                    case 'display_description':
                        $sanitized[$plan_key][$key] = sanitize_textarea_field($value);
                        break;
                    case 'display_features':
                        if (is_array($value)) {
                            $sanitized[$plan_key][$key] = array_values(array_filter(array_map('sanitize_text_field', $value)));
                        } else {
                            // Support newline-separated string from textarea
                            $lines = preg_split('/\r\n|\r|\n/', (string) $value);
                            $sanitized[$plan_key][$key] = array_values(array_filter(array_map('sanitize_text_field', $lines)));
                        }
                        break;
                }
            }
        }
        
        return update_option('vh360_membership_plan_config', $sanitized);
    }
    
    /**
     * Get membership mapping for a product
     *
     * @param int $product_id Product ID
     * @return array|false Membership mapping data or false if not set
     */
    public static function get_product_membership_mapping($product_id) {
        $plan_key = get_post_meta($product_id, '_vh360_membership_plan', true);
        
        if (empty($plan_key)) {
            return false;
        }
        
        $duration = (int) get_post_meta($product_id, '_vh360_membership_duration', true);
        $duration_unit = get_post_meta($product_id, '_vh360_membership_duration_unit', true);
        $grant_type = get_post_meta($product_id, '_vh360_membership_grant_type', true);
        
        if (empty($duration_unit)) {
            $duration_unit = 'days';
        }
        
        if (empty($grant_type)) {
            $grant_type = 'grant';
        }
        
        return array(
            'plan_key' => $plan_key,
            'duration' => $duration,
            'duration_unit' => $duration_unit,
            'grant_type' => $grant_type,
        );
    }
    
    /**
     * Add meta box to products
     */
    public function add_product_meta_box() {
        add_meta_box(
            'vh360_membership_mapping',
            __('VH360 Membership Mapping', 'videohub360-memberships'),
            array($this, 'render_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box($post) {
        wp_nonce_field('vh360_membership_mapping', 'vh360_membership_mapping_nonce');
        
        $plan_key = get_post_meta($post->ID, '_vh360_membership_plan', true);
        $duration = get_post_meta($post->ID, '_vh360_membership_duration', true);
        $duration_unit = get_post_meta($post->ID, '_vh360_membership_duration_unit', true);
        $grant_type = get_post_meta($post->ID, '_vh360_membership_grant_type', true);
        
        if (empty($duration_unit)) {
            $duration_unit = 'days';
        }
        
        if (empty($grant_type)) {
            $grant_type = 'grant';
        }
        
        $plans = self::get_plan_registry();
        
        ?>
        <div class="vh360-membership-mapping">
            <p>
                <label for="vh360_membership_plan">
                    <strong><?php esc_html_e('Membership Plan:', 'videohub360-memberships'); ?></strong>
                </label>
                <select name="vh360_membership_plan" id="vh360_membership_plan" style="width: 100%;">
                    <option value=""><?php esc_html_e('None (Regular Product)', 'videohub360-memberships'); ?></option>
                    <?php foreach ($plans as $key => $plan) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($plan_key, $key); ?>>
                            <?php echo esc_html($plan['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label for="vh360_membership_duration">
                    <strong><?php esc_html_e('Duration:', 'videohub360-memberships'); ?></strong>
                </label>
                <input type="number" name="vh360_membership_duration" id="vh360_membership_duration" value="<?php echo esc_attr($duration); ?>" min="0" style="width: 70px;"/>
                <select name="vh360_membership_duration_unit" id="vh360_membership_duration_unit">
                    <option value="days" <?php selected($duration_unit, 'days'); ?>><?php esc_html_e('Days', 'videohub360-memberships'); ?></option>
                    <option value="months" <?php selected($duration_unit, 'months'); ?>><?php esc_html_e('Months', 'videohub360-memberships'); ?></option>
                    <option value="years" <?php selected($duration_unit, 'years'); ?>><?php esc_html_e('Years', 'videohub360-memberships'); ?></option>
                    <option value="lifetime" <?php selected($duration_unit, 'lifetime'); ?>><?php esc_html_e('Lifetime', 'videohub360-memberships'); ?></option>
                </select>
            </p>
            
            <p>
                <label for="vh360_membership_grant_type">
                    <strong><?php esc_html_e('Grant Type:', 'videohub360-memberships'); ?></strong>
                </label>
                <select name="vh360_membership_grant_type" id="vh360_membership_grant_type" style="width: 100%;">
                    <option value="grant" <?php selected($grant_type, 'grant'); ?>><?php esc_html_e('Grant New', 'videohub360-memberships'); ?></option>
                    <option value="extend" <?php selected($grant_type, 'extend'); ?>><?php esc_html_e('Extend Existing', 'videohub360-memberships'); ?></option>
                </select>
            </p>
            
            <p class="description">
                <?php esc_html_e('When this product is purchased, grant or extend the selected membership plan.', 'videohub360-memberships'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     */
    public function save_product_meta($post_id, $post) {
        // Check if this is a product
        if ($post->post_type !== 'product') {
            return;
        }
        
        // Check nonce
        if (!isset($_POST['vh360_membership_mapping_nonce']) || !wp_verify_nonce($_POST['vh360_membership_mapping_nonce'], 'vh360_membership_mapping')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save plan key
        if (isset($_POST['vh360_membership_plan'])) {
            update_post_meta($post_id, '_vh360_membership_plan', sanitize_text_field($_POST['vh360_membership_plan']));
        }
        
        // Save duration
        if (isset($_POST['vh360_membership_duration'])) {
            update_post_meta($post_id, '_vh360_membership_duration', absint($_POST['vh360_membership_duration']));
        }
        
        // Save duration unit
        if (isset($_POST['vh360_membership_duration_unit'])) {
            update_post_meta($post_id, '_vh360_membership_duration_unit', sanitize_text_field($_POST['vh360_membership_duration_unit']));
        }
        
        // Save grant type
        if (isset($_POST['vh360_membership_grant_type'])) {
            update_post_meta($post_id, '_vh360_membership_grant_type', sanitize_text_field($_POST['vh360_membership_grant_type']));
        }
    }
}
