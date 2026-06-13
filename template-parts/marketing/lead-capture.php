<?php
/**
 * Lead Capture / Newsletter CTA template.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = wp_parse_args($args, array(
    'context' => 'inline',
    'display_mode' => 'inline',
    'form_markup' => '',
    'headline' => __('Stay connected', 'videohub360-theme'),
    'description' => __('Get updates, announcements, and new content delivered to your inbox.', 'videohub360-theme'),
    'button_text' => __('Sign up', 'videohub360-theme'),
    'consent_text' => __('By signing up, you agree to receive updates and marketing emails.', 'videohub360-theme'),
    'success_message' => __('Thanks for signing up.', 'videohub360-theme'),
    'hide_after_dismiss' => true,
    'frequency_days' => 7,
    'popup_delay' => 5,
    'is_placeholder' => false,
));

$display_mode = sanitize_key($args['display_mode']);
$context = sanitize_key($args['context']);
$instance_id = 'vh360-lead-capture-' . wp_unique_id();
$is_modal_mode = in_array($display_mode, array('popup', 'floating_button'), true);
$mode_class = 'footer_banner' === $display_mode ? 'footer' : str_replace('_button', '', $display_mode);
$wrapper_classes = array(
    'vh360-lead-capture',
    'vh360-lead-capture--' . sanitize_html_class($mode_class),
    'vh360-lead-capture--context-' . sanitize_html_class($context),
);

if (!empty($args['is_placeholder'])) {
    $wrapper_classes[] = 'vh360-lead-capture--placeholder';
}
?>
<div
    id="<?php echo esc_attr($instance_id); ?>"
    class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
    data-display-mode="<?php echo esc_attr($display_mode); ?>"
    data-hide-after-dismiss="<?php echo esc_attr(!empty($args['hide_after_dismiss']) ? '1' : '0'); ?>"
    data-frequency-days="<?php echo esc_attr(absint($args['frequency_days'])); ?>"
    data-popup-delay="<?php echo esc_attr(absint($args['popup_delay'])); ?>"
>
    <?php if (!empty($args['is_placeholder'])) : ?>
        <div class="vh360-lead-capture__card" role="status">
            <p class="vh360-lead-capture__notice">
                <?php esc_html_e('Lead Capture is enabled, but no form shortcode or embed code has been configured.', 'videohub360-theme'); ?>
            </p>
        </div>
    <?php elseif ('floating_button' === $display_mode) : ?>
        <button type="button" class="vh360-lead-capture__floating-button" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($instance_id); ?>-dialog">
            <?php echo esc_html($args['button_text']); ?>
        </button>
        <div class="vh360-lead-capture__overlay" hidden></div>
        <div id="<?php echo esc_attr($instance_id); ?>-dialog" class="vh360-lead-capture__modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($instance_id); ?>-title" hidden>
            <button type="button" class="vh360-lead-capture__close" aria-label="<?php esc_attr_e('Close lead capture form', 'videohub360-theme'); ?>">&times;</button>
            <div class="vh360-lead-capture__card">
                <h2 id="<?php echo esc_attr($instance_id); ?>-title" class="vh360-lead-capture__headline"><?php echo esc_html($args['headline']); ?></h2>
                <?php if (!empty($args['description'])) : ?>
                    <p class="vh360-lead-capture__description"><?php echo esc_html($args['description']); ?></p>
                <?php endif; ?>
                <div class="vh360-lead-capture__form"><?php echo $args['form_markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <?php if (!empty($args['consent_text'])) : ?>
                    <p class="vh360-lead-capture__consent"><?php echo esc_html($args['consent_text']); ?></p>
                <?php endif; ?>
                <p class="vh360-lead-capture__success" hidden><?php echo esc_html($args['success_message']); ?></p>
            </div>
        </div>
    <?php elseif ('popup' === $display_mode) : ?>
        <div class="vh360-lead-capture__overlay" hidden></div>
        <div id="<?php echo esc_attr($instance_id); ?>-dialog" class="vh360-lead-capture__modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($instance_id); ?>-title" hidden>
            <button type="button" class="vh360-lead-capture__close" aria-label="<?php esc_attr_e('Close lead capture form', 'videohub360-theme'); ?>">&times;</button>
            <div class="vh360-lead-capture__card">
                <h2 id="<?php echo esc_attr($instance_id); ?>-title" class="vh360-lead-capture__headline"><?php echo esc_html($args['headline']); ?></h2>
                <?php if (!empty($args['description'])) : ?>
                    <p class="vh360-lead-capture__description"><?php echo esc_html($args['description']); ?></p>
                <?php endif; ?>
                <div class="vh360-lead-capture__form"><?php echo $args['form_markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <?php if (!empty($args['consent_text'])) : ?>
                    <p class="vh360-lead-capture__consent"><?php echo esc_html($args['consent_text']); ?></p>
                <?php endif; ?>
                <p class="vh360-lead-capture__success" hidden><?php echo esc_html($args['success_message']); ?></p>
            </div>
        </div>
    <?php else : ?>
        <div class="vh360-lead-capture__card">
            <?php if ('footer_banner' === $display_mode && !empty($args['hide_after_dismiss'])) : ?>
                <button type="button" class="vh360-lead-capture__close" aria-label="<?php esc_attr_e('Dismiss lead capture banner', 'videohub360-theme'); ?>">&times;</button>
            <?php endif; ?>
            <div class="vh360-lead-capture__content">
                <div class="vh360-lead-capture__copy">
                    <h2 class="vh360-lead-capture__headline"><?php echo esc_html($args['headline']); ?></h2>
                    <?php if (!empty($args['description'])) : ?>
                        <p class="vh360-lead-capture__description"><?php echo esc_html($args['description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($args['consent_text'])) : ?>
                        <p class="vh360-lead-capture__consent"><?php echo esc_html($args['consent_text']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="vh360-lead-capture__form"><?php echo $args['form_markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            </div>
            <p class="vh360-lead-capture__success" hidden><?php echo esc_html($args['success_message']); ?></p>
        </div>
    <?php endif; ?>
</div>
