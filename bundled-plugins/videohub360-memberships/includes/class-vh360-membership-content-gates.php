<?php
/**
 * Content Gating Meta Boxes
 *
 * Adds meta boxes to posts, videos, events, etc. for membership-gating.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Content_Gates {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Content_Gates
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Content_Gates
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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'), 10, 2);
    }
    
    /**
     * Add meta boxes to post types
     */
    public function add_meta_boxes() {
        $post_types = array('post', 'videohub360', 'vh360_event', 'vh360_bulletin', 'vh360_gallery');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'vh360_membership_gate',
                __('Membership Access', 'videohub360-memberships'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_meta_box($post) {
        wp_nonce_field('vh360_membership_gate', 'vh360_membership_gate_nonce');
        
        $required_plan = get_post_meta($post->ID, '_vh360_membership_required', true);
        $plans = VH360_Membership_Plans::get_plan_registry();
        
        ?>
        <div class="vh360-membership-gate-meta">
            <p>
                <label for="vh360_membership_required">
                    <strong><?php esc_html_e('Required Plan:', 'videohub360-memberships'); ?></strong>
                </label>
            </p>
            <select name="vh360_membership_required" id="vh360_membership_required" style="width: 100%;">
                <option value=""><?php esc_html_e('No restriction (Public)', 'videohub360-memberships'); ?></option>
                <option value="any" <?php selected($required_plan, 'any'); ?>>
                    <?php esc_html_e('Any Active Membership', 'videohub360-memberships'); ?>
                </option>
                <optgroup label="<?php esc_attr_e('Specific Plans:', 'videohub360-memberships'); ?>">
                    <?php foreach ($plans as $key => $plan) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($required_plan, $key); ?>>
                            <?php echo esc_html($plan['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <p class="description">
                <?php esc_html_e('Lock this content to users with active memberships.', 'videohub360-memberships'); ?>
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
    public function save_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['vh360_membership_gate_nonce']) || !wp_verify_nonce($_POST['vh360_membership_gate_nonce'], 'vh360_membership_gate')) {
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
        
        // Save or delete meta
        if (isset($_POST['vh360_membership_required'])) {
            $value = sanitize_text_field($_POST['vh360_membership_required']);
            
            if (empty($value)) {
                delete_post_meta($post_id, '_vh360_membership_required');
            } else {
                update_post_meta($post_id, '_vh360_membership_required', $value);
            }
        }
    }
}
