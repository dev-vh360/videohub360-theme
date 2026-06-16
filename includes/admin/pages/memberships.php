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

// Get current options
$options = get_option('vh360_membership_options', array(
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
    'subscription_card_bg_color' => '',
    'subscription_card_border_color' => '',
    'subscription_card_title_color' => '',
    'subscription_card_price_color' => '',
    'subscription_card_text_color' => '',
    'subscription_card_button_label' => '',
    'subscription_card_button_bg_color' => '',
    'subscription_card_button_text_color' => '',
));

$options = wp_parse_args($options, array(
    'pricing_page_url' => '',
    'support_url' => '',
    'contact_url' => '',
    'course_purchase_destination' => 'product_page',
));
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

// Get custom plan registry.
$plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
$plan_config = $plans;

// Handle custom plan manager save.
if (isset($_POST['vh360_save_plan_config']) && check_admin_referer('vh360_plan_config_nonce', 'vh360_plan_config_nonce_field')) {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to manage membership plans.', 'videohub360-theme'));
    }

    $submitted = isset($_POST['plans']) && is_array($_POST['plans']) ? wp_unslash($_POST['plans']) : array();
    $delete = isset($_POST['delete_plan']) ? sanitize_key(wp_unslash($_POST['delete_plan'])) : '';
    $duplicate = isset($_POST['duplicate_plan']) ? sanitize_key(wp_unslash($_POST['duplicate_plan'])) : '';
    $new_plans = array();

    foreach ($submitted as $row_key => $plan) {
        $row_key = sanitize_key($row_key);
        if ($delete && $delete === $row_key) {
            continue;
        }
        $plan['id'] = !empty($plan['id']) ? sanitize_key($plan['id']) : $row_key;
        $plan['is_enabled'] = !empty($plan['is_enabled']);
        $plan['is_featured'] = !empty($plan['is_featured']);
        if (isset($plan['features']) && is_string($plan['features'])) {
            $plan['features'] = preg_split('/\r\n|\r|\n/', $plan['features']);
        }
        $new_plans[$plan['id']] = $plan;
        if ($duplicate && $duplicate === $row_key) {
            $copy = $plan;
            $copy['id'] = sanitize_key($plan['id'] . '_copy');
            $copy['name'] = sanitize_text_field($plan['name'] . ' Copy');
            $copy['display_order'] = absint($plan['display_order']) + 1;
            $new_plans[$copy['id']] = $copy;
        }
    }

    if (!empty($_POST['new_plan']) && is_array($_POST['new_plan'])) {
        $plan = wp_unslash($_POST['new_plan']);
        if (!empty($plan['id']) || !empty($plan['name'])) {
            $plan['id'] = !empty($plan['id']) ? sanitize_key($plan['id']) : sanitize_title($plan['name']);
            $plan['is_enabled'] = !empty($plan['is_enabled']);
            $plan['is_featured'] = !empty($plan['is_featured']);
            $plan['features'] = isset($plan['features']) ? preg_split('/\r\n|\r|\n/', $plan['features']) : array();
            $new_plans[$plan['id']] = $plan;
        }
    }

    if (class_exists('VH360_Membership_Plans')) {
        VH360_Membership_Plans::save_plans($new_plans);
    }
    $plans = class_exists('VH360_Membership_Plans') ? VH360_Membership_Plans::get_plan_registry() : array();
    $plan_config = $plans;
    echo '<div class="notice notice-success"><p>' . esc_html__('Membership plans saved.', 'videohub360-theme') . '</p></div>';
}


if (!function_exists('vh360_render_membership_plan_fields')) {
    function vh360_render_membership_plan_fields($key, $plan, $is_new = false) {
        $name = $is_new ? 'new_plan' : 'plans[' . $key . ']';
        $features = isset($plan['features']) && is_array($plan['features']) ? implode("\n", $plan['features']) : '';
        ?>
        <table class="form-table" role="presentation"><tbody>
            <tr><th><?php esc_html_e('Plan Key', 'videohub360-theme'); ?></th><td><input class="regular-text" name="<?php echo esc_attr($name); ?>[id]" value="<?php echo esc_attr($plan['id']); ?>" placeholder="creator_monthly" /> <p class="description"><?php esc_html_e('Unique lowercase slug. Used by gates, checkout, and member records.', 'videohub360-theme'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Name / Label', 'videohub360-theme'); ?></th><td><input name="<?php echo esc_attr($name); ?>[name]" value="<?php echo esc_attr($plan['name']); ?>" placeholder="Creator Monthly" /> <input name="<?php echo esc_attr($name); ?>[label]" value="<?php echo esc_attr($plan['label']); ?>" placeholder="Creator" /></td></tr>
            <tr><th><?php esc_html_e('Description', 'videohub360-theme'); ?></th><td><textarea class="large-text" rows="2" name="<?php echo esc_attr($name); ?>[description]"><?php echo esc_textarea($plan['description']); ?></textarea></td></tr>
            <tr><th><?php esc_html_e('Plan Group', 'videohub360-theme'); ?></th><td><input name="<?php echo esc_attr($name); ?>[plan_group]" value="<?php echo esc_attr($plan['plan_group']); ?>" placeholder="creator" /> <p class="description"><?php esc_html_e('Connects monthly/yearly versions of the same plan for toggle alignment.', 'videohub360-theme'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Billing', 'videohub360-theme'); ?></th><td><select name="<?php echo esc_attr($name); ?>[billing_type]"><?php foreach (array('recurring','one_time','lifetime','free') as $type) : ?><option value="<?php echo esc_attr($type); ?>" <?php selected($plan['billing_type'], $type); ?>><?php echo esc_html($type); ?></option><?php endforeach; ?></select> <select name="<?php echo esc_attr($name); ?>[billing_interval]"><?php foreach (array('monthly','yearly','lifetime','one_time','free') as $interval) : ?><option value="<?php echo esc_attr($interval); ?>" <?php selected($plan['billing_interval'], $interval); ?>><?php echo esc_html($interval); ?></option><?php endforeach; ?></select> <p class="description"><?php esc_html_e('Billing Type controls checkout behavior; Billing Interval controls frontend toggle placement.', 'videohub360-theme'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Pricing', 'videohub360-theme'); ?></th><td><input type="number" step="0.01" name="<?php echo esc_attr($name); ?>[price]" value="<?php echo esc_attr($plan['price']); ?>" placeholder="19.00" /> <input name="<?php echo esc_attr($name); ?>[currency]" value="<?php echo esc_attr($plan['currency']); ?>" size="4" /> <input name="<?php echo esc_attr($name); ?>[compare_at_price]" value="<?php echo esc_attr($plan['compare_at_price']); ?>" placeholder="Compare at" /> <input name="<?php echo esc_attr($name); ?>[savings_text]" value="<?php echo esc_attr($plan['savings_text']); ?>" placeholder="Save 20%" /></td></tr>
            <tr><th><?php esc_html_e('Checkout', 'videohub360-theme'); ?></th><td><input class="regular-text" name="<?php echo esc_attr($name); ?>[stripe_price_id]" value="<?php echo esc_attr($plan['stripe_price_id']); ?>" placeholder="price_..." /> <input type="number" name="<?php echo esc_attr($name); ?>[woocommerce_product_id]" value="<?php echo esc_attr($plan['woocommerce_product_id']); ?>" placeholder="Woo Product ID" /> <select name="<?php echo esc_attr($name); ?>[checkout_behavior]"><?php foreach (array('stripe','woocommerce','add_to_cart','product_page','free') as $behavior) : ?><option value="<?php echo esc_attr($behavior); ?>" <?php selected($plan['checkout_behavior'], $behavior); ?>><?php echo esc_html($behavior); ?></option><?php endforeach; ?></select><p class="description"><?php esc_html_e('Stripe Price ID is required for active recurring Stripe plans. WooCommerce Product ID is used for one-time/lifetime product-based plans.', 'videohub360-theme'); ?></p></td></tr>
            <tr><th><?php esc_html_e('Access & Display', 'videohub360-theme'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr($name); ?>[is_enabled]" value="1" <?php checked(!empty($plan['is_enabled'])); ?> /> <?php esc_html_e('Enabled', 'videohub360-theme'); ?></label> <label><input type="checkbox" name="<?php echo esc_attr($name); ?>[is_featured]" value="1" <?php checked(!empty($plan['is_featured'])); ?> /> <?php esc_html_e('Featured / Recommended', 'videohub360-theme'); ?></label> <?php esc_html_e('Tier', 'videohub360-theme'); ?> <input type="number" class="small-text" name="<?php echo esc_attr($name); ?>[tier_level]" value="<?php echo esc_attr($plan['tier_level']); ?>" /> <?php esc_html_e('Order', 'videohub360-theme'); ?> <input type="number" class="small-text" name="<?php echo esc_attr($name); ?>[display_order]" value="<?php echo esc_attr($plan['display_order']); ?>" /></td></tr>
            <tr><th><?php esc_html_e('Button & Features', 'videohub360-theme'); ?></th><td><input class="regular-text" name="<?php echo esc_attr($name); ?>[button_text]" value="<?php echo esc_attr($plan['button_text']); ?>" /> <textarea class="large-text" rows="4" name="<?php echo esc_attr($name); ?>[features]" placeholder="One feature per line"><?php echo esc_textarea($features); ?></textarea></td></tr>
        </tbody></table>
        <?php
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Paid Membership Settings', 'videohub360-theme'); ?></h1>
    
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
            <h2><?php esc_html_e('Membership Plans Manager', 'videohub360-theme'); ?></h2>
            <p class="description"><?php esc_html_e('Create, edit, duplicate, delete, enable, disable, reorder, and connect any number of membership plans. Plan Group connects monthly/yearly versions of the same plan. Billing Interval controls pricing toggle placement. Billing Type controls checkout behavior. Tier Level controls access hierarchy.', 'videohub360-theme'); ?></p>
            <p><code>[vh360_pricing_toggle show_lifetime="true" show_free="true"]</code></p>
            <?php foreach ($plans as $key => $plan) : ?>
                <details class="vh360-plan-config-block" open style="background:#fff;border:1px solid #ccd0d4;padding:15px 20px;margin-bottom:18px;">
                    <summary><strong><?php echo esc_html($plan['name']); ?></strong> <code><?php echo esc_html($key); ?></code> <?php echo !empty($plan['is_enabled']) ? esc_html__('Enabled', 'videohub360-theme') : esc_html__('Disabled', 'videohub360-theme'); ?></summary>
                    <?php vh360_render_membership_plan_fields($key, $plan); ?>
                    <p>
                        <button class="button" type="submit" name="duplicate_plan" value="<?php echo esc_attr($key); ?>"><?php esc_html_e('Duplicate Plan', 'videohub360-theme'); ?></button>
                        <button class="button button-link-delete" type="submit" name="delete_plan" value="<?php echo esc_attr($key); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this membership plan?', 'videohub360-theme')); ?>');"><?php esc_html_e('Delete Plan', 'videohub360-theme'); ?></button>
                    </p>
                </details>
            <?php endforeach; ?>

            <details class="vh360-plan-config-block" style="background:#f6f7f7;border:1px solid #ccd0d4;padding:15px 20px;margin-bottom:18px;">
                <summary><strong><?php esc_html_e('Add New Plan', 'videohub360-theme'); ?></strong></summary>
                <?php vh360_render_membership_plan_fields('new_plan', array('id'=>'','name'=>'','label'=>'','description'=>'','plan_group'=>'','billing_type'=>'recurring','billing_interval'=>'monthly','price'=>'','currency'=>'USD','compare_at_price'=>'','savings_text'=>'','stripe_price_id'=>'','woocommerce_product_id'=>0,'features'=>array(),'tier_level'=>0,'is_featured'=>false,'is_enabled'=>true,'display_order'=>999,'button_text'=>'Choose Plan','checkout_behavior'=>'stripe'), true); ?>
            </details>
            <?php submit_button(__('Save Membership Plans', 'videohub360-theme')); ?>
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

