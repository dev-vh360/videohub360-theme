<?php
if (!defined('ABSPATH')) exit;

class VideoHub360_Consent_Admin {
    private $manager;

    public function __construct($manager) {
        $this->manager = $manager;
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'settings'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
    }

    public function menu() {
        add_submenu_page('edit.php?post_type=videohub360', __('Privacy & Consent', 'videohub360'), __('Privacy & Consent', 'videohub360'), 'manage_options', 'videohub360-privacy-consent', array($this, 'page'));
    }

    public function settings() {
        register_setting('vh360_consent_settings_group', VideoHub360_Consent_Manager::OPTION, array('sanitize_callback' => array('VideoHub360_Consent_Manager', 'sanitize_settings')));
    }

    public function assets($hook) {
        if (false === strpos($hook, 'videohub360-privacy-consent')) return;

        wp_enqueue_style('vh360-consent-admin', VIDEOHUB360_PLUGIN_URL . 'assets/css/consent-admin.css', array(), VIDEOHUB360_VERSION);
        wp_enqueue_script('vh360-consent-admin', VIDEOHUB360_PLUGIN_URL . 'assets/js/consent-admin.js', array(), VIDEOHUB360_VERSION, true);
        wp_localize_script('vh360-consent-admin', 'VH360ConsentAdmin', array(
            'modeLabels' => array(
                'disabled' => __('Disabled', 'videohub360'),
                'notice' => __('Notice Only', 'videohub360'),
                'opt_out' => __('Opt-Out', 'videohub360'),
                'strict' => __('Strict Opt-In', 'videohub360'),
            ),
            'modeDescriptions' => array(
                'disabled' => __('VideoHub360 does not display a consent interface or block optional functionality.', 'videohub360'),
                'notice' => __('Displays one informational notice. Optional VideoHub360 functionality is not blocked.', 'videohub360'),
                'opt_out' => __('Optional categories begin enabled and visitors can disable them. Global Privacy Control still disables advertising.', 'videohub360'),
                'strict' => __('Optional preferences, analytics, and advertising remain disabled until the visitor grants consent.', 'videohub360'),
            ),
        ));
    }

    private function option_name($key) {
        return VideoHub360_Consent_Manager::OPTION . '[' . $key . ']';
    }

    private function text_field($settings, $key, $label, $description = '', $attributes = '') {
        ?>
        <div class="vh360-consent-admin-field">
            <label for="vh360_<?php echo esc_attr($key); ?>"><strong><?php echo esc_html($label); ?></strong></label>
            <input class="regular-text" id="vh360_<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($this->option_name($key)); ?>" value="<?php echo esc_attr($settings[$key]); ?>" <?php echo $attributes; ?> />
            <?php if ($description) : ?><p class="description"><?php echo esc_html($description); ?></p><?php endif; ?>
        </div>
        <?php
    }

    public function page() {
        if (!current_user_can('manage_options')) return;

        $settings = $this->manager->get_settings();
        $modes = array(
            'disabled' => __('Disabled', 'videohub360'),
            'notice' => __('Notice Only', 'videohub360'),
            'opt_out' => __('Opt-Out', 'videohub360'),
            'strict' => __('Strict Opt-In (recommended)', 'videohub360'),
        );
        ?>
        <div class="wrap vh360-consent-admin">
            <h1><?php esc_html_e('Privacy & Consent', 'videohub360'); ?></h1>
            <p class="description vh360-consent-admin-intro"><?php esc_html_e('Configure VideoHub360 consent behavior. This tool helps centralize choices but does not guarantee legal compliance.', 'videohub360'); ?></p>

            <form method="post" action="options.php" data-vh360-consent-admin-form>
                <?php settings_fields('vh360_consent_settings_group'); ?>

                <div class="vh360-consent-admin-layout">
                    <section class="card vh360-consent-admin-card vh360-consent-admin-mode-card">
                        <h2><?php esc_html_e('Consent Mode', 'videohub360'); ?></h2>
                        <div class="vh360-consent-admin-field">
                            <label for="vh360_consent_mode"><strong><?php esc_html_e('Consent mode', 'videohub360'); ?></strong></label>
                            <select id="vh360_consent_mode" name="<?php echo esc_attr($this->option_name('mode')); ?>" data-vh360-consent-mode>
                                <?php foreach ($modes as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['mode'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="vh360-consent-admin-mode-status"><span class="vh360-consent-admin-badge" data-vh360-consent-mode-label></span></p>
                        <p class="description" data-vh360-consent-mode-description></p>
                        <div class="notice notice-info inline vh360-consent-admin-callout">
                            <p><?php esc_html_e('Disabled mode preserves existing behavior and allows another consent-management plugin to control the site. Strict Opt-In is recommended when enabling VideoHub360 consent.', 'videohub360'); ?></p>
                        </div>
                    </section>

                    <section class="card vh360-consent-admin-card" data-vh360-consent-modes="notice opt_out strict">
                        <h2><?php esc_html_e('Policy and Retention', 'videohub360'); ?></h2>
                        <?php $this->text_field($settings, 'policy_version', __('Policy version', 'videohub360'), __('Increase this value when visitor choices need to be collected again for an updated policy.')); ?>
                        <?php $this->text_field($settings, 'expiration_days', __('Consent expiration days', 'videohub360'), __('Choices and Notice Only acknowledgments expire after this number of days.'), 'type="number" min="1" max="730" step="1"'); ?>
                    </section>

                    <section class="card vh360-consent-admin-card" data-vh360-consent-modes="notice opt_out strict">
                        <h2><?php esc_html_e('Notice and Banner Content', 'videohub360'); ?></h2>
                        <div data-vh360-consent-modes="opt_out strict">
                            <?php $this->text_field($settings, 'banner_heading', __('Banner heading', 'videohub360'), __('Used for Opt-Out and Strict Opt-In. Notice Only uses the translated heading “Privacy notice”.')); ?>
                        </div>
                        <?php $this->text_field($settings, 'banner_description', __('Banner description', 'videohub360'), __('Used as the visitor-facing explanation in every active consent mode.')); ?>
                        <div class="vh360-consent-admin-field">
                            <label for="vh360_privacy_policy_page_id"><strong><?php esc_html_e('Privacy Policy page', 'videohub360'); ?></strong></label>
                            <?php wp_dropdown_pages(array('id' => 'vh360_privacy_policy_page_id', 'name' => $this->option_name('privacy_policy_page_id'), 'selected' => absint($settings['privacy_policy_page_id']), 'show_option_none' => __('Select a page', 'videohub360'))); ?>
                            <p class="description"><?php esc_html_e('Used when no custom Privacy Policy URL is provided below.', 'videohub360'); ?></p>
                        </div>
                        <?php $this->text_field($settings, 'privacy_policy_url', __('Privacy Policy URL', 'videohub360'), __('When provided, this URL takes precedence over the selected Privacy Policy page.', 'videohub360')); ?>
                    </section>

                    <section class="card vh360-consent-admin-card" data-vh360-consent-modes="opt_out strict">
                        <h2><?php esc_html_e('Consent Action Labels', 'videohub360'); ?></h2>
                        <?php $this->text_field($settings, 'accept_all_text', __('Accept All button text', 'videohub360')); ?>
                        <?php $this->text_field($settings, 'reject_optional_text', __('Reject Optional button text', 'videohub360')); ?>
                        <?php $this->text_field($settings, 'manage_preferences_text', __('Manage Preferences button text', 'videohub360')); ?>
                        <?php $this->text_field($settings, 'save_preferences_text', __('Save Preferences button text', 'videohub360')); ?>
                        <?php $this->text_field($settings, 'privacy_choices_text', __('Privacy Choices link text', 'videohub360')); ?>
                    </section>

                    <section class="card vh360-consent-admin-card" data-vh360-consent-modes="notice opt_out strict">
                        <h2><?php esc_html_e('Visitor Controls', 'videohub360'); ?></h2>
                        <div class="vh360-consent-admin-field" data-vh360-consent-modes="opt_out strict">
                            <label for="vh360_show_persistent_control">
                                <input id="vh360_show_persistent_control" type="checkbox" name="<?php echo esc_attr($this->option_name('show_persistent_control')); ?>" value="1" <?php checked($settings['show_persistent_control'], 1); ?> />
                                <strong><?php esc_html_e('Persistent Privacy Choices control', 'videohub360'); ?></strong>
                            </label>
                            <p class="description"><?php esc_html_e('Show a persistent control that reopens visitor preferences.', 'videohub360'); ?></p>
                        </div>
                        <div class="vh360-consent-admin-field">
                            <label for="vh360_banner_position"><strong><?php esc_html_e('Banner position', 'videohub360'); ?></strong></label>
                            <select id="vh360_banner_position" name="<?php echo esc_attr($this->option_name('banner_position')); ?>">
                                <option value="bottom" <?php selected($settings['banner_position'], 'bottom'); ?>><?php esc_html_e('Bottom', 'videohub360'); ?></option>
                                <option value="top" <?php selected($settings['banner_position'], 'top'); ?>><?php esc_html_e('Top', 'videohub360'); ?></option>
                            </select>
                        </div>
                    </section>
                </div>

                <section class="card vh360-consent-admin-card vh360-consent-admin-integrations" data-vh360-consent-modes="notice opt_out strict">
                    <h2><?php esc_html_e('Advertising and Third-Party Integrations', 'videohub360'); ?></h2>
                    <p><?php esc_html_e('VideoHub360 can block its own personalized Activity Feed ad slot until advertising consent. Unrelated plugins may enqueue scripts outside widget markup; register known services or script handles with VideoHub360 consent filters so they can be controlled.', 'videohub360'); ?></p>
                </section>

                <p class="submit vh360-consent-admin-save-action">
                    <?php submit_button(__('Save Privacy & Consent Settings', 'videohub360'), 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>
        <?php
    }
}
