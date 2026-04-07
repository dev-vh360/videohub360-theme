<?php
/**
 * Membership Plans Registry
 *
 * Central registry for membership plan definitions and WooCommerce product mapping.
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
     * @return array
     */
    public static function get_plan_registry() {
        $plans = array(
            'basic_monthly' => array(
                'label' => __('Basic (Monthly)', 'videohub360-memberships'),
                'duration' => 30,
                'duration_unit' => 'days',
                'features' => array(),
            ),
            'basic_yearly' => array(
                'label' => __('Basic (Yearly)', 'videohub360-memberships'),
                'duration' => 365,
                'duration_unit' => 'days',
                'features' => array(),
            ),
            'pro_monthly' => array(
                'label' => __('Pro (Monthly)', 'videohub360-memberships'),
                'duration' => 30,
                'duration_unit' => 'days',
                'features' => array(),
            ),
            'pro_yearly' => array(
                'label' => __('Pro (Yearly)', 'videohub360-memberships'),
                'duration' => 365,
                'duration_unit' => 'days',
                'features' => array(),
            ),
            'lifetime' => array(
                'label' => __('Lifetime', 'videohub360-memberships'),
                'duration' => 0,
                'duration_unit' => 'lifetime',
                'features' => array(),
            ),
        );
        
        return apply_filters('vh360_membership_plans', $plans);
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
