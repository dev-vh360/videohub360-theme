<?php
/**
 * Membership Frontend Handler
 *
 * Handles frontend display and user-facing features.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

class VH360_Membership_Frontend {
    
    /**
     * Singleton instance
     *
     * @var VH360_Membership_Frontend
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return VH360_Membership_Frontend
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('the_content', array($this, 'filter_content'), 10);
        add_filter('vh360_current_page_requires_membership', array($this, 'check_page_membership_requirement'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'vh360-memberships',
            VH360_MEMBERSHIPS_URL . 'assets/css/memberships.css',
            array(),
            VH360_MEMBERSHIPS_VERSION
        );
    }
    
    /**
     * Filter content for membership-gated posts
     *
     * @param string $content Post content
     * @return string
     */
    public function filter_content($content) {
        if (!is_singular() || is_admin()) {
            return $content;
        }
        
        $post_id = get_the_ID();

        if (
            get_post_type($post_id) === 'videohub360'
            && function_exists('videohub360_course_features_enabled')
            && videohub360_course_features_enabled()
            && function_exists('videohub360_user_can_access_lesson')
        ) {
            if (videohub360_user_can_access_lesson($post_id, get_current_user_id())) {
                return $content;
            }

            return vh360_render_membership_gate(array(
                'required_plan' => vh360_post_requires_membership($post_id) ?: 'course',
            ));
        }

        $required_plan = vh360_post_requires_membership($post_id);
        
        if (!$required_plan) {
            return $content;
        }
        
        // Check if user has access
        $user_id = get_current_user_id();
        
        // Check membership access
        if ($user_id) {
            // Check if user has the required plan
            if ($required_plan === 'any') {
                $has_access = vh360_user_has_active_membership($user_id);
            } else {
                $has_access = vh360_user_has_membership_plan($user_id, $required_plan);
            }
            
            if ($has_access) {
                return $content;
            }
        }
        
        // User doesn't have access - use centralized gate renderer
        return vh360_render_membership_gate(array('required_plan' => $required_plan));
    }
    
    /**
     * Render login required notice
     *
     * @return string
     */
    private function render_login_required_notice() {
        $login_url = function_exists('vh360_get_login_page_url') 
            ? vh360_get_login_page_url() 
            : wp_login_url(get_permalink());
            
        ob_start();
        ?>
        <div class="vh360-membership-gate vh360-membership-login-required">
            <div class="vh360-membership-gate-content">
                <svg class="vh360-membership-gate-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <h3><?php esc_html_e('Login Required', 'videohub360-memberships'); ?></h3>
                <p><?php esc_html_e('Please log in to access this content.', 'videohub360-memberships'); ?></p>
                <a href="<?php echo esc_url($login_url); ?>" class="vh360-membership-gate-button">
                    <?php esc_html_e('Log In', 'videohub360-memberships'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render upgrade required notice
     *
     * @param string $required_plan Required plan key
     * @return string
     */
    private function render_upgrade_required_notice($required_plan) {
        $options = get_option('vh360_membership_options', array());
        $pricing_url = isset($options['pricing_page_url']) ? $options['pricing_page_url'] : '';
        $custom_message = isset($options['locked_message']) ? $options['locked_message'] : '';
        
        ob_start();
        ?>
        <div class="vh360-membership-gate vh360-membership-upgrade-required">
            <div class="vh360-membership-gate-content">
                <svg class="vh360-membership-gate-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                </svg>
                <h3><?php esc_html_e('Premium Content', 'videohub360-memberships'); ?></h3>
                <?php if ($custom_message) : ?>
                    <div class="vh360-membership-custom-message">
                        <?php echo wp_kses_post($custom_message); ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('This content requires an active membership to access.', 'videohub360-memberships'); ?></p>
                <?php endif; ?>
                <?php if ($pricing_url) : ?>
                    <a href="<?php echo esc_url($pricing_url); ?>" class="vh360-membership-gate-button">
                        <?php esc_html_e('View Plans', 'videohub360-memberships'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check if current page requires membership
     *
     * @param bool $requires Current value
     * @return bool
     */
    public function check_page_membership_requirement($requires) {
        // Check if memberships are enabled
        $options = get_option('vh360_membership_options', array());
        if (empty($options['enable_memberships'])) {
            return $requires;
        }
        
        // Check for specific page templates that require membership
        global $post;
        if ($post) {
            $template = get_page_template_slug($post->ID);
            
            // Activity feed has its own template-level gate, do NOT redirect
            // Let the template handle gating to show inline upgrade notice
            if ($template === 'template-activity-feed.php') {
                return false; // Skip redirect gate for activity feed
            }
            
            // Check post meta for individual pages
            $page_requires = get_post_meta($post->ID, '_vh360_page_requires_membership', true);
            if ($page_requires) {
                return true;
            }
        }
        
        // Check for post types that require membership
        if (is_singular(array('vh360_event', 'vh360_bulletin', 'vh360_gallery'))) {
            $required_plan = vh360_post_requires_membership(get_the_ID());
            if ($required_plan) {
                return true;
            }
        }
        
        return $requires;
    }
}
