<?php
/**
 * Membership Settings Admin Page
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current options
$options = get_option('vh360_membership_options', array(
    'enable_memberships' => true,
    'pricing_page_url' => '',
    'login_required' => true,
    'locked_message' => '',
    'reminder_days' => 7,
    'grace_period_days' => 0,
));

?>

<div class="wrap">
    <h1><?php esc_html_e('Membership Settings', 'videohub360-theme'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('vh360_membership_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="enable_memberships">
                            <?php esc_html_e('Enable Memberships', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vh360_membership_options[enable_memberships]" 
                                   id="enable_memberships" 
                                   value="1" 
                                   <?php checked(1, $options['enable_memberships']); ?> />
                            <?php esc_html_e('Enable membership system', 'videohub360-theme'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, WooCommerce products can be mapped to membership plans.', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pricing_page_url">
                            <?php esc_html_e('Pricing Page URL', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url" 
                               name="vh360_membership_options[pricing_page_url]" 
                               id="pricing_page_url" 
                               value="<?php echo esc_attr($options['pricing_page_url']); ?>" 
                               class="regular-text" 
                               placeholder="https://" />
                        <p class="description">
                            <?php esc_html_e('URL to your pricing/membership plans page. Used in upgrade prompts.', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="login_required">
                            <?php esc_html_e('Login Required', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vh360_membership_options[login_required]" 
                                   id="login_required" 
                                   value="1" 
                                   <?php checked(1, $options['login_required']); ?> />
                            <?php esc_html_e('Require login to view locked content', 'videohub360-theme'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, users must log in to see membership-locked content (even if they don\'t have a membership).', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="locked_message">
                            <?php esc_html_e('Locked Content Message', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        wp_editor(
                            $options['locked_message'],
                            'locked_message',
                            array(
                                'textarea_name' => 'vh360_membership_options[locked_message]',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                                'teeny' => true,
                            )
                        );
                        ?>
                        <p class="description">
                            <?php esc_html_e('Custom message shown when content is locked. Leave empty to use default message.', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="reminder_days">
                            <?php esc_html_e('Renewal Reminder', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               name="vh360_membership_options[reminder_days]" 
                               id="reminder_days" 
                               value="<?php echo esc_attr($options['reminder_days']); ?>" 
                               min="0" 
                               max="90" 
                               class="small-text" /> 
                        <?php esc_html_e('days before expiration', 'videohub360-theme'); ?>
                        <p class="description">
                            <?php esc_html_e('Send renewal reminder this many days before membership expires. Set to 0 to disable reminders.', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="grace_period_days">
                            <?php esc_html_e('Grace Period', 'videohub360-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               name="vh360_membership_options[grace_period_days]" 
                               id="grace_period_days" 
                               value="<?php echo esc_attr($options['grace_period_days']); ?>" 
                               min="0" 
                               max="30" 
                               class="small-text" /> 
                        <?php esc_html_e('days after expiration', 'videohub360-theme'); ?>
                        <p class="description">
                            <?php esc_html_e('Allow continued access for this many days after expiration. Set to 0 for no grace period.', 'videohub360-theme'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
    </form>
    
    <hr />
    
    <h2><?php esc_html_e('Membership Statistics', 'videohub360-theme'); ?></h2>
    
    <?php
    if (class_exists('VH360_Membership_Database')) {
        global $wpdb;
        $table = VH360_Membership_Database::get_memberships_table();
        
        // Get statistics
        $total_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $active_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW())");
        $expired_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'expired'");
        
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Metric', 'videohub360-theme'); ?></th>
                    <th><?php esc_html_e('Count', 'videohub360-theme'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Total Memberships', 'videohub360-theme'); ?></td>
                    <td><strong><?php echo esc_html($total_memberships); ?></strong></td>
                </tr>
                <tr class="alternate">
                    <td><?php esc_html_e('Active Memberships', 'videohub360-theme'); ?></td>
                    <td><strong style="color: #46b450;"><?php echo esc_html($active_memberships); ?></strong></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Expired Memberships', 'videohub360-theme'); ?></td>
                    <td><strong style="color: #dc3232;"><?php echo esc_html($expired_memberships); ?></strong></td>
                </tr>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p>' . esc_html__('Membership plugin not active.', 'videohub360-theme') . '</p>';
    }
    ?>
    
    <hr />
    
    <h2><?php esc_html_e('How to Set Up Memberships', 'videohub360-theme'); ?></h2>
    
    <ol>
        <li><?php esc_html_e('Create WooCommerce products for your membership plans', 'videohub360-theme'); ?></li>
        <li><?php esc_html_e('Edit each product and configure the "VH360 Membership Mapping" settings in the sidebar', 'videohub360-theme'); ?></li>
        <li><?php esc_html_e('Set the membership plan, duration, and grant type', 'videohub360-theme'); ?></li>
        <li><?php esc_html_e('When customers purchase these products, memberships will be automatically granted or extended', 'videohub360-theme'); ?></li>
        <li><?php esc_html_e('Use post meta "_vh360_membership_required" to lock individual posts/videos to specific membership plans', 'videohub360-theme'); ?></li>
    </ol>
    
    <p>
        <strong><?php esc_html_e('Note:', 'videohub360-theme'); ?></strong>
        <?php esc_html_e('This implementation uses fixed-term memberships with WooCommerce for payment processing. Recurring subscriptions via Stripe will be added in a future update.', 'videohub360-theme'); ?>
    </p>
</div>
