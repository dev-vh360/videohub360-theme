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
    'gate_live_rooms' => 0,
    'gate_create_videos' => 0,
    'gate_create_posts' => 0,
    'gate_create_events' => 0,
    'gate_create_bulletins' => 0,
    'gate_create_galleries' => 0,
    'gate_direct_messages' => 0,
    'gate_activity_feed' => 0,
    'gate_members_directory' => 0,
    'gate_appointments' => 0,
    'gate_push_notifications' => 0,
    'subscription_card_bg_color' => '',
    'subscription_card_border_color' => '',
    'subscription_card_title_color' => '',
    'subscription_card_price_color' => '',
    'subscription_card_text_color' => '',
    'subscription_card_button_label' => '',
    'subscription_card_button_bg_color' => '',
    'subscription_card_button_text_color' => '',
));

// Get Stripe settings
$stripe_settings = get_option('vh360_stripe_settings', array(
    'enable_recurring' => 0,
    'test_mode' => 1,
    'publishable_key' => '',
    'secret_key' => '',
    'test_publishable_key' => '',
    'test_secret_key' => '',
    'webhook_secret' => '',
    'enable_portal' => 0,
    'cancellation_behavior' => 'at_period_end',
));

// Get plan config
$plan_config = get_option('vh360_membership_plan_config', array());
$plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();

// Handle plan config save
if (isset($_POST['vh360_save_plan_config']) && check_admin_referer('vh360_plan_config_nonce', 'vh360_plan_config_nonce_field')) {
    $new_config = array();
    
    if (isset($_POST['plan_config']) && is_array($_POST['plan_config'])) {
        foreach ($_POST['plan_config'] as $plan_key => $config) {
            $plan_key = sanitize_key($plan_key);
            $new_config[$plan_key] = array(
                'billing_mode'    => isset($config['billing_mode']) && in_array($config['billing_mode'], array('one_time', 'recurring'), true) ? $config['billing_mode'] : 'one_time',
                'stripe_price_id' => isset($config['stripe_price_id']) ? sanitize_text_field($config['stripe_price_id']) : '',
                'auto_renew'      => !empty($config['auto_renew']),
                'trial_days'      => isset($config['trial_days']) ? absint($config['trial_days']) : 0,
                'display_label'   => isset($config['display_label']) ? sanitize_text_field($config['display_label']) : '',
                'display_price'   => isset($config['display_price']) ? sanitize_text_field($config['display_price']) : '',
                'display_description' => isset($config['display_description']) ? sanitize_textarea_field($config['display_description']) : '',
                'display_features' => array(),
            );
            // Parse display_features from newline-separated textarea
            if (isset($config['display_features']) && is_string($config['display_features'])) {
                $lines = preg_split('/\r\n|\r|\n/', $config['display_features']);
                $new_config[$plan_key]['display_features'] = array_values(array_filter(array_map('sanitize_text_field', $lines)));
            }
        }
    }
    
    update_option('vh360_membership_plan_config', $new_config);
    $plan_config = $new_config;
    
    // Refresh plans after save
    $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Plan configuration saved.', 'videohub360-theme') . '</p></div>';
}

?>

<div class="wrap">
    <h1><?php esc_html_e('Membership Settings', 'videohub360-theme'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General', 'videohub360-theme'); ?></a>
        <a href="#stripe" class="nav-tab" data-tab="stripe"><?php esc_html_e('Stripe / Recurring', 'videohub360-theme'); ?></a>
        <a href="#plan-mapping" class="nav-tab" data-tab="plan-mapping"><?php esc_html_e('Plan Configuration', 'videohub360-theme'); ?></a>
        <a href="#stats" class="nav-tab" data-tab="stats"><?php esc_html_e('Statistics', 'videohub360-theme'); ?></a>
    </h2>
    
    <!-- General Settings Tab -->
    <div id="tab-general" class="vh360-tab-content" style="display:block;">
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
                                <?php esc_html_e('When enabled, WooCommerce products can be mapped to membership plans and Stripe recurring subscriptions can be used.', 'videohub360-theme'); ?>
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
                                <?php esc_html_e('Send renewal reminder this many days before membership expires. Set to 0 to disable reminders. For recurring subscriptions, reminders are only sent when user action is needed.', 'videohub360-theme'); ?>
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
                    
                    <tr>
                        <th colspan="2">
                            <h3 style="margin-top: 30px; margin-bottom: 10px;">
                                <?php esc_html_e('Feature Gating', 'videohub360-theme'); ?>
                            </h3>
                            <p class="description" style="margin-bottom: 20px;">
                                <?php esc_html_e('Select which features should require an active membership when the membership system is enabled. Features not selected will be controlled only by role/capability permissions.', 'videohub360-theme'); ?>
                            </p>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Frontend Creation Features', 'videohub360-theme'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_create_videos]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_create_videos']); ?> />
                                    <?php esc_html_e('Video Creation', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_create_posts]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_create_posts']); ?> />
                                    <?php esc_html_e('Post Creation', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_create_events]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_create_events']); ?> />
                                    <?php esc_html_e('Event Creation', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_create_bulletins]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_create_bulletins']); ?> />
                                    <?php esc_html_e('Bulletin Creation', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_create_galleries]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_create_galleries']); ?> />
                                    <?php esc_html_e('Gallery Creation', 'videohub360-theme'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Restrict dashboard content creation features to members only.', 'videohub360-theme'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Platform Features', 'videohub360-theme'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_live_rooms]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_live_rooms']); ?> />
                                    <?php esc_html_e('Live Rooms', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_direct_messages]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_direct_messages']); ?> />
                                    <?php esc_html_e('Direct Messages', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_activity_feed]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_activity_feed']); ?> />
                                    <?php esc_html_e('Activity Feed', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_members_directory]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_members_directory']); ?> />
                                    <?php esc_html_e('Members Directory', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_appointments]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_appointments']); ?> />
                                    <?php esc_html_e('Appointments', 'videohub360-theme'); ?>
                                </label>
                                <br />
                                <label>
                                    <input type="checkbox" 
                                           name="vh360_membership_options[gate_push_notifications]" 
                                           value="1" 
                                           <?php checked(1, $options['gate_push_notifications']); ?> />
                                    <?php esc_html_e('Push Notifications', 'videohub360-theme'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Restrict platform features to members only.', 'videohub360-theme'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e('Subscription Card Styling', 'videohub360-theme'); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e('Customize the appearance of the recurring subscription plan card shown to non-members. Leave fields empty to use defaults.', 'videohub360-theme'); ?>
            </p>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_bg_color">
                                <?php esc_html_e('Card Background Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_bg_color]" 
                                   id="subscription_card_bg_color" 
                                   value="<?php echo esc_attr($options['subscription_card_bg_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_border_color">
                                <?php esc_html_e('Card Border Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_border_color]" 
                                   id="subscription_card_border_color" 
                                   value="<?php echo esc_attr($options['subscription_card_border_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_title_color">
                                <?php esc_html_e('Plan Title Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_title_color]" 
                                   id="subscription_card_title_color" 
                                   value="<?php echo esc_attr($options['subscription_card_title_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_price_color">
                                <?php esc_html_e('Plan Price Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_price_color]" 
                                   id="subscription_card_price_color" 
                                   value="<?php echo esc_attr($options['subscription_card_price_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_text_color">
                                <?php esc_html_e('Plan Text Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_text_color]" 
                                   id="subscription_card_text_color" 
                                   value="<?php echo esc_attr($options['subscription_card_text_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                            <p class="description">
                                <?php esc_html_e('Applies to description, features, and trial text.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_button_label">
                                <?php esc_html_e('Subscribe Button Label', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_button_label]" 
                                   id="subscription_card_button_label" 
                                   value="<?php echo esc_attr($options['subscription_card_button_label']); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e('Subscribe', 'videohub360-theme'); ?>" />
                            <p class="description">
                                <?php esc_html_e('Custom label for the subscribe button. Leave empty to use default.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_button_bg_color">
                                <?php esc_html_e('Subscribe Button Background Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_button_bg_color]" 
                                   id="subscription_card_button_bg_color" 
                                   value="<?php echo esc_attr($options['subscription_card_button_bg_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="subscription_card_button_text_color">
                                <?php esc_html_e('Subscribe Button Text Color', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_membership_options[subscription_card_button_text_color]" 
                                   id="subscription_card_button_text_color" 
                                   value="<?php echo esc_attr($options['subscription_card_button_text_color']); ?>" 
                                   class="vh360-color-picker" 
                                   data-default-color="" />
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <script>
            jQuery(document).ready(function($) {
                $('.vh360-color-picker').wpColorPicker();
            });
            </script>
            
            <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
        </form>
    </div>
    
    <!-- Stripe Settings Tab -->
    <div id="tab-stripe" class="vh360-tab-content" style="display:none;">
        <form method="post" action="options.php">
            <?php settings_fields('vh360_stripe_settings'); ?>
            
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="enable_recurring">
                                <?php esc_html_e('Enable Recurring Billing', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="vh360_stripe_settings[enable_recurring]" 
                                       id="enable_recurring" 
                                       value="1" 
                                       <?php checked(1, $stripe_settings['enable_recurring']); ?> />
                                <?php esc_html_e('Enable Stripe recurring subscriptions', 'videohub360-theme'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, plans configured as "recurring" will use Stripe for billing.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_test_mode">
                                <?php esc_html_e('Test Mode', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="vh360_stripe_settings[test_mode]" 
                                       id="stripe_test_mode" 
                                       value="1" 
                                       <?php checked(1, $stripe_settings['test_mode']); ?> />
                                <?php esc_html_e('Use Stripe test keys', 'videohub360-theme'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('When enabled, test API keys are used instead of live keys.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3><?php esc_html_e('Live API Keys', 'videohub360-theme'); ?></h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_publishable_key">
                                <?php esc_html_e('Publishable Key', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_stripe_settings[publishable_key]" 
                                   id="stripe_publishable_key" 
                                   value="<?php echo esc_attr($stripe_settings['publishable_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="pk_live_..." />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_secret_key">
                                <?php esc_html_e('Secret Key', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="vh360_stripe_settings[secret_key]" 
                                   id="stripe_secret_key" 
                                   value="<?php echo esc_attr($stripe_settings['secret_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="sk_live_..." />
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3><?php esc_html_e('Test API Keys', 'videohub360-theme'); ?></h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_test_publishable_key">
                                <?php esc_html_e('Test Publishable Key', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="vh360_stripe_settings[test_publishable_key]" 
                                   id="stripe_test_publishable_key" 
                                   value="<?php echo esc_attr($stripe_settings['test_publishable_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="pk_test_..." />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_test_secret_key">
                                <?php esc_html_e('Test Secret Key', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="vh360_stripe_settings[test_secret_key]" 
                                   id="stripe_test_secret_key" 
                                   value="<?php echo esc_attr($stripe_settings['test_secret_key']); ?>" 
                                   class="regular-text" 
                                   placeholder="sk_test_..." />
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3><?php esc_html_e('Webhook', 'videohub360-theme'); ?></h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_webhook_secret">
                                <?php esc_html_e('Webhook Signing Secret', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password" 
                                   name="vh360_stripe_settings[webhook_secret]" 
                                   id="stripe_webhook_secret" 
                                   value="<?php echo esc_attr($stripe_settings['webhook_secret']); ?>" 
                                   class="regular-text" 
                                   placeholder="whsec_..." />
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: webhook endpoint URL */
                                    esc_html__('Webhook endpoint: %s', 'videohub360-theme'),
                                    '<code>' . esc_html(rest_url('vh360-memberships/v1/stripe-webhook')) . '</code>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h3><?php esc_html_e('Portal & Behavior', 'videohub360-theme'); ?></h3>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_enable_portal">
                                <?php esc_html_e('Customer Portal', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="vh360_stripe_settings[enable_portal]" 
                                       id="stripe_enable_portal" 
                                       value="1" 
                                       <?php checked(1, $stripe_settings['enable_portal']); ?> />
                                <?php esc_html_e('Enable Stripe Customer Portal for self-service billing management', 'videohub360-theme'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="stripe_cancellation_behavior">
                                <?php esc_html_e('Cancellation Behavior', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="vh360_stripe_settings[cancellation_behavior]" id="stripe_cancellation_behavior">
                                <option value="at_period_end" <?php selected($stripe_settings['cancellation_behavior'], 'at_period_end'); ?>>
                                    <?php esc_html_e('Cancel at end of billing period', 'videohub360-theme'); ?>
                                </option>
                                <option value="immediate" <?php selected($stripe_settings['cancellation_behavior'], 'immediate'); ?>>
                                    <?php esc_html_e('Cancel immediately', 'videohub360-theme'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('How cancellations are handled when a user cancels their subscription.', 'videohub360-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button(__('Save Stripe Settings', 'videohub360-theme')); ?>
        </form>
    </div>
    
    <!-- Plan Configuration Tab -->
    <div id="tab-plan-mapping" class="vh360-tab-content" style="display:none;">
        <form method="post">
            <?php wp_nonce_field('vh360_plan_config_nonce', 'vh360_plan_config_nonce_field'); ?>
            <input type="hidden" name="vh360_save_plan_config" value="1" />
            
            <p class="description" style="margin-bottom: 20px;">
                <?php esc_html_e('Configure billing mode and Stripe price IDs for each membership plan. Plans set to "Recurring" will use Stripe for subscription billing. Plans set to "One-Time" will continue using WooCommerce orders.', 'videohub360-theme'); ?>
            </p>
            
            <?php foreach ($plans as $key => $plan) : 
                $config = isset($plan_config[$key]) ? $plan_config[$key] : array();
                $billing_mode = isset($plan['billing_mode']) ? $plan['billing_mode'] : 'one_time';
                $stripe_price_id = isset($plan['stripe_price_id']) ? $plan['stripe_price_id'] : '';
                $auto_renew = isset($plan['auto_renew']) ? $plan['auto_renew'] : false;
                $trial_days = isset($plan['trial_days']) ? $plan['trial_days'] : 0;
                $display_label = isset($plan['display_label']) ? $plan['display_label'] : '';
                $display_price = isset($plan['display_price']) ? $plan['display_price'] : '';
                $display_description = isset($plan['display_description']) ? $plan['display_description'] : '';
                $display_features = isset($plan['display_features']) && is_array($plan['display_features']) ? $plan['display_features'] : array();
            ?>
            <div class="vh360-plan-config-block" style="background: #fff; border: 1px solid #ccd0d4; padding: 15px 20px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">
                    <?php echo esc_html($plan['label']); ?>
                    <code style="font-size: 12px; margin-left: 8px;"><?php echo esc_html($key); ?></code>
                </h3>

                <h4 style="margin-bottom: 8px;"><?php esc_html_e('Billing Configuration', 'videohub360-theme'); ?></h4>
                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th scope="row"><?php esc_html_e('Billing Mode', 'videohub360-theme'); ?></th>
                        <td>
                            <select name="plan_config[<?php echo esc_attr($key); ?>][billing_mode]">
                                <option value="one_time" <?php selected($billing_mode, 'one_time'); ?>>
                                    <?php esc_html_e('One-Time (WooCommerce)', 'videohub360-theme'); ?>
                                </option>
                                <option value="recurring" <?php selected($billing_mode, 'recurring'); ?>>
                                    <?php esc_html_e('Recurring (Stripe)', 'videohub360-theme'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Stripe Price ID', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="text" 
                                   name="plan_config[<?php echo esc_attr($key); ?>][stripe_price_id]" 
                                   value="<?php echo esc_attr($stripe_price_id); ?>" 
                                   class="regular-text" 
                                   placeholder="price_..." />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto-Renew', 'videohub360-theme'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="plan_config[<?php echo esc_attr($key); ?>][auto_renew]" 
                                       value="1" 
                                       <?php checked(true, $auto_renew); ?> />
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Trial Days', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="number" 
                                   name="plan_config[<?php echo esc_attr($key); ?>][trial_days]" 
                                   value="<?php echo esc_attr($trial_days); ?>" 
                                   min="0" 
                                   class="small-text" />
                        </td>
                    </tr>
                </table>

                <h4 style="margin-bottom: 8px;"><?php esc_html_e('Frontend Display', 'videohub360-theme'); ?></h4>
                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th scope="row"><?php esc_html_e('Display Name', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="text" 
                                   name="plan_config[<?php echo esc_attr($key); ?>][display_label]" 
                                   value="<?php echo esc_attr($display_label); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php echo esc_attr($plan['label']); ?>" />
                            <p class="description"><?php esc_html_e('Leave empty to use the default plan name.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Display Price', 'videohub360-theme'); ?></th>
                        <td>
                            <input type="text" 
                                   name="plan_config[<?php echo esc_attr($key); ?>][display_price]" 
                                   value="<?php echo esc_attr($display_price); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e('e.g. $9.99/mo', 'videohub360-theme'); ?>" />
                            <p class="description"><?php esc_html_e('Price text shown on the frontend plan card.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Short Description', 'videohub360-theme'); ?></th>
                        <td>
                            <textarea name="plan_config[<?php echo esc_attr($key); ?>][display_description]" 
                                      rows="3" 
                                      class="large-text"><?php echo esc_textarea($display_description); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Features', 'videohub360-theme'); ?></th>
                        <td>
                            <textarea name="plan_config[<?php echo esc_attr($key); ?>][display_features]" 
                                      rows="4" 
                                      class="large-text"><?php echo esc_textarea(implode("\n", $display_features)); ?></textarea>
                            <p class="description"><?php esc_html_e('One feature per line. Displayed as a list on the frontend plan card.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endforeach; ?>
            
            <?php submit_button(__('Save Plan Configuration', 'videohub360-theme')); ?>
        </form>
    </div>
    
    <!-- Statistics Tab -->
    <div id="tab-stats" class="vh360-tab-content" style="display:none;">
        <h2><?php esc_html_e('Membership Statistics', 'videohub360-theme'); ?></h2>
        
        <?php
        if (class_exists('VH360_Membership_Database')) {
            global $wpdb;
            $table = VH360_Membership_Database::get_memberships_table();
            
            // Get statistics
            $total_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $active_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW())");
            $expired_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'expired'");
            $recurring_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE billing_mode = 'recurring' AND status = 'active'");
            $one_time_memberships = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE billing_mode = 'one_time' AND status = 'active'");
            $cancel_pending = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE cancel_at_period_end = 1 AND status = 'active'");
            
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
                        <td><?php esc_html_e('Active (One-Time / WooCommerce)', 'videohub360-theme'); ?></td>
                        <td><strong><?php echo esc_html($one_time_memberships); ?></strong></td>
                    </tr>
                    <tr class="alternate">
                        <td><?php esc_html_e('Active (Recurring / Stripe)', 'videohub360-theme'); ?></td>
                        <td><strong><?php echo esc_html($recurring_memberships); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Cancellation Pending (End of Period)', 'videohub360-theme'); ?></td>
                        <td><strong style="color: #f0ad4e;"><?php echo esc_html($cancel_pending); ?></strong></td>
                    </tr>
                    <tr class="alternate">
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
            <li><?php esc_html_e('Enable memberships in the General tab', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('For one-time plans: Create WooCommerce products and configure "VH360 Membership Mapping" on each product', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('For recurring plans: Configure Stripe keys in the Stripe tab, then set billing mode and Stripe Price IDs in Plan Configuration', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Add your Stripe webhook endpoint URL to your Stripe Dashboard webhook settings', 'videohub360-theme'); ?></li>
            <li><?php esc_html_e('Use post meta "_vh360_membership_required" to lock individual posts/videos to specific membership plans', 'videohub360-theme'); ?></li>
        </ol>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target tab content
        $('.vh360-tab-content').hide();
        $('#tab-' + tab).show();
        
        // Store in hash
        window.location.hash = tab;
    });
    
    // Restore tab from hash
    var hash = window.location.hash.replace('#', '');
    if (hash && $('[data-tab="' + hash + '"]').length) {
        $('[data-tab="' + hash + '"]').trigger('click');
    }
});
</script>

