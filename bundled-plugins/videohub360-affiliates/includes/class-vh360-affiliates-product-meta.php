<?php
/**
 * Product-level affiliate commission settings.
 *
 * Adds a meta box to WooCommerce product edit pages.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Product_Meta {

    /** @var VH360_Affiliates_Product_Meta|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_product', array($this, 'save_meta_box'), 10, 2);

        // Variable product variation support
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_fields'), 10, 3);
        add_action('woocommerce_save_product_variation',            array($this, 'save_variation_fields'), 10, 2);
    }

    /**
     * Register meta box on product edit screen.
     */
    public function add_meta_box() {
        add_meta_box(
            'vh360-affiliate-product-meta',
            __('Affiliate Commissions', 'videohub360-affiliates'),
            array($this, 'render_meta_box'),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render the meta box content.
     *
     * @param WP_Post $post
     */
    public function render_meta_box($post) {
        wp_nonce_field('vh360_affiliate_product_meta', 'vh360_affiliate_product_nonce');

        $exclude = get_post_meta($post->ID, '_vh360_affiliate_exclude',         true);
        $type    = get_post_meta($post->ID, '_vh360_affiliate_commission_type', true);
        $rate    = get_post_meta($post->ID, '_vh360_affiliate_commission_rate', true);

        ?>
        <p>
            <label>
                <input type="checkbox" name="vh360_affiliate_exclude" value="1" <?php checked('1', $exclude); ?>>
                <?php esc_html_e('Exclude from affiliate commissions', 'videohub360-affiliates'); ?>
            </label>
        </p>
        <p><strong><?php esc_html_e('Commission Override (optional)', 'videohub360-affiliates'); ?></strong><br>
        <span class="description"><?php esc_html_e('Leave blank to use the global default rate. Fill in both fields to override for this product.', 'videohub360-affiliates'); ?></span></p>
        <p>
            <label><?php esc_html_e('Commission type', 'videohub360-affiliates'); ?></label><br>
            <select name="vh360_affiliate_commission_type">
                <option value=""          <?php selected('', $type); ?>><?php esc_html_e('— Use global —', 'videohub360-affiliates'); ?></option>
                <option value="percentage" <?php selected('percentage', $type); ?>><?php esc_html_e('Percentage (%)', 'videohub360-affiliates'); ?></option>
                <option value="flat"       <?php selected('flat',       $type); ?>><?php esc_html_e('Flat amount',    'videohub360-affiliates'); ?></option>
            </select>
        </p>
        <p>
            <label><?php esc_html_e('Commission rate', 'videohub360-affiliates'); ?></label><br>
            <input type="number" name="vh360_affiliate_commission_rate" value="<?php echo esc_attr($rate); ?>" step="0.01" min="0" style="width:100%" placeholder="<?php esc_attr_e('Leave blank for global default', 'videohub360-affiliates'); ?>">
        </p>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public function save_meta_box($post_id, $post) {
        if (!isset($_POST['vh360_affiliate_product_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_affiliate_product_nonce'])), 'vh360_affiliate_product_meta')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        update_post_meta($post_id, '_vh360_affiliate_exclude',
            isset($_POST['vh360_affiliate_exclude']) ? '1' : '');
        $comm_type = isset($_POST['vh360_affiliate_commission_type'])
            ? sanitize_key(wp_unslash($_POST['vh360_affiliate_commission_type']))
            : '';
        update_post_meta($post_id, '_vh360_affiliate_commission_type', $comm_type);
        update_post_meta($post_id, '_vh360_affiliate_commission_rate',
            isset($_POST['vh360_affiliate_commission_rate']) && $_POST['vh360_affiliate_commission_rate'] !== ''
                ? (float) wp_unslash($_POST['vh360_affiliate_commission_rate'])
                : '');
    }

    /**
     * Add affiliate commission fields to variation attributes panel.
     *
     * @param int    $loop
     * @param array  $variation_data
     * @param WP_Post $variation
     */
    public function add_variation_fields($loop, $variation_data, $variation) {
        $type    = get_post_meta($variation->ID, '_vh360_affiliate_commission_type', true);
        $rate    = get_post_meta($variation->ID, '_vh360_affiliate_commission_rate', true);
        $exclude = get_post_meta($variation->ID, '_vh360_affiliate_exclude',         true);

        echo '<div class="vh360-aff-variation-settings" style="padding:6px 0;border-top:1px solid #ddd;margin-top:6px;">';
        echo '<p><strong>' . esc_html__('Affiliate Commissions', 'videohub360-affiliates') . '</strong></p>';
        echo '<label><input type="checkbox" name="vh360_aff_var_exclude[' . esc_attr($loop) . ']" value="1" ' . checked('1', $exclude, false) . '> ' . esc_html__('Exclude variation from affiliate commissions', 'videohub360-affiliates') . '</label><br>';
        echo '<p class="description">' . esc_html__('Override commission settings (leave blank to use product/global default):', 'videohub360-affiliates') . '</p>';
        echo '<select name="vh360_aff_var_type[' . esc_attr($loop) . ']">';
        echo '<option value="" '          . selected('', $type, false)            . '>' . esc_html__('— Use product/global —', 'videohub360-affiliates') . '</option>';
        echo '<option value="percentage" ' . selected('percentage', $type, false) . '>' . esc_html__('Percentage', 'videohub360-affiliates') . '</option>';
        echo '<option value="flat" '       . selected('flat',       $type, false) . '>' . esc_html__('Flat',       'videohub360-affiliates') . '</option>';
        echo '</select>';
        echo ' <input type="number" name="vh360_aff_var_rate[' . esc_attr($loop) . ']" value="' . esc_attr($rate) . '" step="0.01" min="0" placeholder="' . esc_attr__('Rate', 'videohub360-affiliates') . '">';
        echo '</div>';
    }

    /**
     * Save variation affiliate fields.
     *
     * @param int $variation_id
     * @param int $loop
     */
    public function save_variation_fields($variation_id, $loop) {
        if (!current_user_can('edit_post', $variation_id)) {
            return;
        }

        update_post_meta($variation_id, '_vh360_affiliate_exclude',
            isset($_POST['vh360_aff_var_exclude'][$loop]) ? '1' : '');
        $var_type = isset($_POST['vh360_aff_var_type'][$loop])
            ? sanitize_key(wp_unslash($_POST['vh360_aff_var_type'][$loop]))
            : '';
        update_post_meta($variation_id, '_vh360_affiliate_commission_type', $var_type);
        update_post_meta($variation_id, '_vh360_affiliate_commission_rate',
            isset($_POST['vh360_aff_var_rate'][$loop]) && $_POST['vh360_aff_var_rate'][$loop] !== ''
                ? (float) wp_unslash($_POST['vh360_aff_var_rate'][$loop])
                : '');
    }
}
