<?php
if (!defined('ABSPATH')) exit;

class VideoHub360_Consent_Frontend {
    private $manager;

    public function __construct($manager) {
        $this->manager = $manager;
        add_action('wp_enqueue_scripts', array($this, 'assets'), 0);
        add_action('wp_footer', array($this, 'render'));
        add_shortcode('videohub360_privacy_choices', array($this, 'shortcode'));
    }

    public function assets() {
        if (!$this->manager->is_enabled()) return;

        wp_enqueue_style('vh360-consent-manager', VIDEOHUB360_PLUGIN_URL . 'assets/css/variables.css', array(), videohub360_asset_version('assets/css/variables.css'));
        wp_enqueue_style('vh360-consent-ui', VIDEOHUB360_PLUGIN_URL . 'assets/css/consent-manager.css', array('vh360-consent-manager'), videohub360_asset_version('assets/css/consent-manager.css'));
        wp_enqueue_script('vh360-consent-manager', VIDEOHUB360_PLUGIN_URL . 'assets/js/consent-manager.js', array(), videohub360_asset_version('assets/js/consent-manager.js'), false);
        wp_add_inline_script('vh360-consent-manager', 'window.VH360ConsentExpected=true;', 'before');
        wp_localize_script('vh360-consent-manager', 'VH360ConsentConfig', $this->manager->frontend_config());
    }

    public function shortcode() {
        if (!$this->manager->is_enabled() || $this->manager->is_notice_only()) return '';

        $settings = $this->manager->get_settings();
        return '<button type="button" class="vh360-consent-open">' . esc_html($settings['privacy_choices_text']) . '</button>';
    }

    public function render() {
        if (!$this->manager->is_enabled()) return;

        $settings = $this->manager->get_settings();
        $policy = $settings['privacy_policy_url'];
        $is_notice_only = $this->manager->is_notice_only();
        $root_class = $is_notice_only ? 'vh360-consent-root--notice' : 'vh360-consent-root--choices';

        if (!$policy && $settings['privacy_policy_page_id']) {
            $policy = get_permalink($settings['privacy_policy_page_id']);
        }
        ?>
        <div class="vh360-consent-root <?php echo esc_attr($root_class); ?> vh360-consent-<?php echo esc_attr($settings['banner_position']); ?>" data-vh360-consent-root hidden>
            <section class="vh360-consent-banner" role="region" aria-label="<?php esc_attr_e('Privacy notice', 'videohub360'); ?>">
                <?php if ($is_notice_only) : ?>
                    <h2><?php esc_html_e('Privacy notice', 'videohub360'); ?></h2>
                    <p><?php echo esc_html($settings['banner_description']); ?></p>
                    <p class="vh360-consent-error" role="alert" hidden></p>
                    <div class="vh360-consent-actions" data-vh360-consent-notice-only>
                        <button type="button" data-vh360-consent-action="acknowledge"><?php esc_html_e('Acknowledge', 'videohub360'); ?></button>
                        <?php if ($policy) : ?>
                            <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Privacy Policy', 'videohub360'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <?php if (!empty($settings['show_persistent_control'])) : ?>
                        <button type="button" class="vh360-consent-close" aria-label="<?php esc_attr_e('Close privacy notice', 'videohub360'); ?>">×</button>
                    <?php endif; ?>
                    <h2><?php echo esc_html($settings['banner_heading']); ?></h2>
                    <p><?php echo esc_html($settings['banner_description']); ?></p>
                    <p class="vh360-consent-error" role="alert" hidden></p>
                    <div class="vh360-consent-actions" data-vh360-consent-full-controls>
                        <button type="button" data-vh360-consent-action="accept-all"><?php echo esc_html($settings['accept_all_text']); ?></button>
                        <button type="button" data-vh360-consent-action="reject-optional"><?php echo esc_html($settings['reject_optional_text']); ?></button>
                        <button type="button" data-vh360-consent-action="manage"><?php echo esc_html($settings['manage_preferences_text']); ?></button>
                        <?php if ($policy) : ?>
                            <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Privacy Policy', 'videohub360'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!$is_notice_only) : ?>
                <div class="vh360-consent-modal" role="dialog" aria-modal="true" aria-labelledby="vh360-consent-title" hidden>
                    <div class="vh360-consent-panel">
                        <button type="button" class="vh360-consent-modal-close" aria-label="<?php esc_attr_e('Close preferences', 'videohub360'); ?>">×</button>
                        <h2 id="vh360-consent-title"><?php esc_html_e('Privacy preferences', 'videohub360'); ?></h2>
                        <p class="vh360-consent-gpc" hidden><?php esc_html_e('Your browser privacy signal is active, so advertising consent is turned off.', 'videohub360'); ?></p>
                        <p class="vh360-consent-error" role="alert" hidden></p>
                        <div data-vh360-consent-full-controls>
                            <fieldset>
                                <legend><?php esc_html_e('Consent categories', 'videohub360'); ?></legend>
                                <label><input type="checkbox" checked disabled> <?php esc_html_e('Necessary (always active)', 'videohub360'); ?></label>
                                <label><input type="checkbox" data-vh360-consent-category="preferences"> <?php esc_html_e('Preferences', 'videohub360'); ?></label>
                                <label><input type="checkbox" data-vh360-consent-category="analytics"> <?php esc_html_e('Analytics', 'videohub360'); ?></label>
                                <label><input type="checkbox" data-vh360-consent-category="advertising"> <?php esc_html_e('Advertising', 'videohub360'); ?></label>
                            </fieldset>
                            <button type="button" data-vh360-consent-action="save"><?php echo esc_html($settings['save_preferences_text']); ?></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$is_notice_only && !empty($settings['show_persistent_control'])) : ?>
            <button type="button" class="vh360-consent-open vh360-consent-floating"><?php echo esc_html($settings['privacy_choices_text']); ?></button>
        <?php endif; ?>
        <?php
    }
}
