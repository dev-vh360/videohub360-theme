<?php
/**
 * Paid Membership Settings Admin Page
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current options.
$dashboard_style_defaults = class_exists('VH360_Membership_Subscription_Management')
    ? VH360_Membership_Subscription_Management::get_dashboard_card_style_defaults()
    : array(
        'subscription_card_bg_color' => '#ffffff',
        'subscription_card_border_color' => '#e0e0e0',
        'subscription_card_title_color' => '#333333',
        'subscription_card_price_color' => '#333333',
        'subscription_card_text_color' => '#666666',
        'subscription_card_button_bg_color' => '#0073aa',
        'subscription_card_button_text_color' => '#ffffff',
    );
$pricing_style_defaults = class_exists('VH360_Membership_Plans')
    ? VH360_Membership_Plans::get_pricing_style_defaults()
    : array(
        'pricing_card_background_color' => '#ffffff',
        'pricing_card_border_color' => '#e5e7eb',
        'pricing_card_text_color' => '#4b5563',
        'pricing_card_title_color' => '#111827',
        'pricing_card_price_color' => '#111827',
        'pricing_card_description_color' => '#6b7280',
        'pricing_card_feature_text_color' => '#4b5563',
        'pricing_card_button_background_color' => '#2563eb',
        'pricing_card_button_text_color' => '#ffffff',
        'pricing_card_button_hover_background_color' => '#1d4ed8',
        'pricing_card_featured_border_color' => '#2563eb',
        'pricing_card_featured_badge_background_color' => '#dbeafe',
        'pricing_card_featured_badge_text_color' => '#1d4ed8',
        'pricing_toggle_active_background_color' => '#2563eb',
        'pricing_toggle_active_text_color' => '#ffffff',
        'pricing_toggle_inactive_background_color' => '#ffffff',
        'pricing_toggle_inactive_text_color' => '#1f2937',
    );
$membership_option_defaults = array_merge(array(
    'enable_memberships' => true,
    'pricing_page_url' => '',
    'support_url' => '',
    'contact_url' => '',
    'course_purchase_destination' => 'product_page',
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
    'subscription_card_button_label' => '',
), $dashboard_style_defaults, $pricing_style_defaults);
$options = wp_parse_args(get_option('vh360_membership_options', array()), $membership_option_defaults);
if (!in_array($options['course_purchase_destination'], array('product_page', 'add_to_cart'), true)) {
    $options['course_purchase_destination'] = 'product_page';
}

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

// Membership plans are managed by the VideoHub360 Memberships plugin.
$plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
$plan_config = $plans;

?>


<div class="wrap">
    <h1><?php esc_html_e('Paid Membership Settings', 'videohub360-theme'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e('General', 'videohub360-theme'); ?></a>
        <a href="#stripe" class="nav-tab" data-tab="stripe"><?php esc_html_e('Stripe / Recurring', 'videohub360-theme'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=vh360-theme-memberships&tab=membership-plans')); ?>" class="nav-tab" data-tab="membership-plans"><?php esc_html_e('Membership Plans', 'videohub360-theme'); ?></a>
        <a href="#styling" class="nav-tab" data-tab="styling"><?php esc_html_e('Styling', 'videohub360-theme'); ?></a>
        <a href="#stats" class="nav-tab" data-tab="stats"><?php esc_html_e('Statistics', 'videohub360-theme'); ?></a>
    </h2>
    
    <!-- General Settings Tab -->
    <div id="tab-general" class="vh360-tab-content" style="display:block;">
        <form method="post" action="options.php">
            <?php
            settings_fields('vh360_membership_settings');
            ?>
            <input type="hidden" name="vh360_membership_options[_settings_section]" value="general" />
            
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
                        <th scope="row"><label for="support_url"><?php esc_html_e('Support URL', 'videohub360-theme'); ?></label></th>
                        <td>
                            <input type="url" name="vh360_membership_options[support_url]" id="support_url" value="<?php echo esc_attr($options['support_url']); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Optional support page URL shown in the membership dashboard Billing & Support area.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contact_url"><?php esc_html_e('Contact URL', 'videohub360-theme'); ?></label></th>
                        <td>
                            <input type="url" name="vh360_membership_options[contact_url]" id="contact_url" value="<?php echo esc_attr($options['contact_url']); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Optional fallback contact URL used when no support URL is configured.', 'videohub360-theme'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="course_purchase_destination">
                                <?php esc_html_e('Course Purchase Button Destination', 'videohub360-theme'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="vh360_membership_options[course_purchase_destination]" id="course_purchase_destination">
                                <option value="product_page" <?php selected($options['course_purchase_destination'], 'product_page'); ?>>
                                    <?php esc_html_e('Product Page', 'videohub360-theme'); ?>
                                </option>
                                <option value="add_to_cart" <?php selected($options['course_purchase_destination'], 'add_to_cart'); ?>>
                                    <?php esc_html_e('Add to Cart', 'videohub360-theme'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose where the Buy Course button sends visitors for individually sold courses. Product Page is recommended as the default. Add to Cart sends the product directly to the cart using WooCommerce’s add-to-cart URL.', 'videohub360-theme'); ?>
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
            
            <?php submit_button(__('Save Settings', 'videohub360-theme')); ?>
        </form>
    </div>
    

    <!-- Styling Tab -->
    <div id="tab-styling" class="vh360-tab-content" style="display:none;">
        <form method="post" action="options.php">
            <?php settings_fields('vh360_membership_settings'); ?>
            <input type="hidden" name="vh360_membership_options[_settings_section]" value="styling" />

            <h3><?php esc_html_e('Dashboard Subscription Card Styling', 'videohub360-theme'); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e('Controls the subscription and membership cards shown inside the user dashboard Membership tab and membership management area.', 'videohub360-theme'); ?>
            </p>
            <table class="form-table" role="presentation"><tbody>
                <?php
                $dashboard_color_fields = array(
                    'subscription_card_bg_color' => __('Card Background Color', 'videohub360-theme'),
                    'subscription_card_border_color' => __('Card Border Color', 'videohub360-theme'),
                    'subscription_card_title_color' => __('Plan Title Color', 'videohub360-theme'),
                    'subscription_card_price_color' => __('Plan Price Color', 'videohub360-theme'),
                    'subscription_card_text_color' => __('Plan Text Color', 'videohub360-theme'),
                    'subscription_card_button_bg_color' => __('Subscribe Button Background Color', 'videohub360-theme'),
                    'subscription_card_button_text_color' => __('Subscribe Button Text Color', 'videohub360-theme'),
                );
                foreach ($dashboard_color_fields as $field => $label) : ?>
                    <tr><th scope="row"><label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th><td><input type="text" name="vh360_membership_options[<?php echo esc_attr($field); ?>]" id="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($options[$field]); ?>" class="vh360-color-picker" data-default-color="" /></td></tr>
                <?php endforeach; ?>
                <tr><th scope="row"><label for="subscription_card_button_label"><?php esc_html_e('Subscribe Button Label', 'videohub360-theme'); ?></label></th><td><input type="text" name="vh360_membership_options[subscription_card_button_label]" id="subscription_card_button_label" value="<?php echo esc_attr($options['subscription_card_button_label']); ?>" class="regular-text" placeholder="<?php esc_attr_e('Subscribe', 'videohub360-theme'); ?>" /><p class="description"><?php esc_html_e('Custom label for dashboard subscription card buttons. Leave empty to use default.', 'videohub360-theme'); ?></p></td></tr>
            </tbody></table>

            <h3><?php esc_html_e('Pricing Plan Card Styling', 'videohub360-theme'); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e('Controls the public pricing plan cards rendered by the [vh360_pricing_toggle] shortcode.', 'videohub360-theme'); ?>
            </p>
            <table class="form-table" role="presentation"><tbody>
                <?php
                $pricing_color_fields = array(
                    'pricing_card_background_color' => __('Pricing Card Background Color', 'videohub360-theme'),
                    'pricing_card_border_color' => __('Pricing Card Border Color', 'videohub360-theme'),
                    'pricing_card_text_color' => __('Pricing Card Text Color', 'videohub360-theme'),
                    'pricing_card_title_color' => __('Pricing Card Title Color', 'videohub360-theme'),
                    'pricing_card_price_color' => __('Pricing Card Price Color', 'videohub360-theme'),
                    'pricing_card_description_color' => __('Pricing Card Description Color', 'videohub360-theme'),
                    'pricing_card_feature_text_color' => __('Pricing Card Feature Text Color', 'videohub360-theme'),
                    'pricing_card_button_background_color' => __('Pricing Button Background Color', 'videohub360-theme'),
                    'pricing_card_button_text_color' => __('Pricing Button Text Color', 'videohub360-theme'),
                    'pricing_card_button_hover_background_color' => __('Pricing Button Hover Background Color', 'videohub360-theme'),
                    'pricing_card_featured_border_color' => __('Featured Card Border / Accent Color', 'videohub360-theme'),
                    'pricing_card_featured_badge_background_color' => __('Featured Badge Background Color', 'videohub360-theme'),
                    'pricing_card_featured_badge_text_color' => __('Featured Badge Text Color', 'videohub360-theme'),
                    'pricing_toggle_active_background_color' => __('Toggle Active Background Color', 'videohub360-theme'),
                    'pricing_toggle_active_text_color' => __('Toggle Active Text Color', 'videohub360-theme'),
                    'pricing_toggle_inactive_background_color' => __('Toggle Inactive Background Color', 'videohub360-theme'),
                    'pricing_toggle_inactive_text_color' => __('Toggle Inactive Text Color', 'videohub360-theme'),
                );
                foreach ($pricing_color_fields as $field => $label) : ?>
                    <tr><th scope="row"><label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th><td><input type="text" name="vh360_membership_options[<?php echo esc_attr($field); ?>]" id="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($options[$field]); ?>" class="vh360-color-picker" data-default-color="<?php echo esc_attr($options[$field]); ?>" /></td></tr>
                <?php endforeach; ?>
            </tbody></table>
            <script>jQuery(document).ready(function($){ $('.vh360-color-picker').wpColorPicker(); });</script>
            <?php submit_button(__('Save Styling Settings', 'videohub360-theme')); ?>
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
    
    <!-- Membership Plans Tab -->
    <div id="tab-membership-plans" class="vh360-tab-content" style="display:none;">
        <?php if (class_exists('VH360_Membership_Plans_Admin')) : ?>
            <?php VH360_Membership_Plans_Admin::get_instance()->render_manager(false); ?>
        <?php else : ?>
            <h2><?php esc_html_e('Membership Plans', 'videohub360-theme'); ?></h2>
            <div class="notice notice-warning inline"><p><?php esc_html_e('Activate the VideoHub360 Memberships plugin to manage membership plans.', 'videohub360-theme'); ?></p></div>
        <?php endif; ?>
        <p><code>[vh360_pricing_toggle show_lifetime="true" show_free="true"]</code></p>
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
            <li><?php esc_html_e('For recurring plans: Configure Stripe keys in the Stripe tab, then configure billing and Stripe Price IDs in Membership Plans', 'videohub360-theme'); ?></li>
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
        var params = new URLSearchParams(window.location.search);
        if (tab === 'membership-plans' && params.get('tab') !== 'membership-plans') {
            window.location.href = $(this).attr('href');
            return;
        }
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target tab content
        $('.vh360-tab-content').hide();
        $('#tab-' + tab).show();
        
        // Store in hash
        window.location.hash = tab;
    });
    
    // Restore tab from query string or hash.
    var params = new URLSearchParams(window.location.search);
    var requestedTab = params.get('tab') || window.location.hash.replace('#', '');
    if (requestedTab === 'plan-mapping') {
        params.set('tab', 'membership-plans');
        window.location.href = window.location.pathname + '?' + params.toString();
        return;
    }
    if (requestedTab === 'membership-plans' && params.get('tab') !== 'membership-plans') {
        params.set('tab', 'membership-plans');
        window.location.href = window.location.pathname + '?' + params.toString();
        return;
    }
    if (requestedTab && $('[data-tab="' + requestedTab + '"]').length) {
        $('[data-tab="' + requestedTab + '"]').trigger('click');
    }
});
</script>

