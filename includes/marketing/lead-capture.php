<?php
/**
 * Lead Capture / Newsletter CTA frontend integration.
 *
 * @package Videohub360_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allowed HTML for lead capture safe embeds.
 *
 * Script tags and event handler attributes are intentionally not allowed.
 *
 * @return array
 */
function vh360_lead_capture_allowed_html() {
    $common = array(
        'id' => true,
        'class' => true,
        'style' => true,
        'title' => true,
        'role' => true,
        'aria-label' => true,
        'aria-describedby' => true,
        'aria-labelledby' => true,
        'aria-hidden' => true,
        'data-*' => true,
    );

    return array(
        'form' => array_merge($common, array(
            'action' => true,
            'method' => true,
            'target' => true,
            'accept-charset' => true,
            'autocomplete' => true,
            'name' => true,
            'novalidate' => true,
        )),
        'input' => array_merge($common, array(
            'type' => true,
            'name' => true,
            'value' => true,
            'placeholder' => true,
            'required' => true,
            'checked' => true,
            'disabled' => true,
            'readonly' => true,
            'autocomplete' => true,
            'maxlength' => true,
            'minlength' => true,
            'min' => true,
            'max' => true,
            'step' => true,
        )),
        'label' => array_merge($common, array('for' => true)),
        'button' => array_merge($common, array('type' => true, 'name' => true, 'value' => true, 'disabled' => true)),
        'textarea' => array_merge($common, array('name' => true, 'placeholder' => true, 'rows' => true, 'cols' => true, 'required' => true)),
        'select' => array_merge($common, array('name' => true, 'required' => true, 'multiple' => true)),
        'option' => array_merge($common, array('value' => true, 'selected' => true)),
        'div' => $common,
        'span' => $common,
        'p' => $common,
        'br' => array(),
        'strong' => $common,
        'em' => $common,
        'small' => $common,
        'a' => array_merge($common, array('href' => true, 'target' => true, 'rel' => true)),
        'ul' => $common,
        'ol' => $common,
        'li' => $common,
        'fieldset' => $common,
        'legend' => $common,
        'h2' => $common,
        'h3' => $common,
        'h4' => $common,
        'img' => array_merge($common, array('src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true)),
    );
}

/**
 * Check if configured lead capture source has content.
 *
 * @return bool
 */
function vh360_lead_capture_has_form_source() {
    $source = get_theme_mod('vh360_lead_capture_form_source', 'shortcode');

    if ('embed_html' === $source) {
        return '' !== trim((string) get_theme_mod('vh360_lead_capture_embed_html', ''));
    }

    return '' !== trim((string) get_theme_mod('vh360_lead_capture_shortcode', ''));
}

/**
 * Determine whether the current post/page is excluded.
 *
 * @return bool
 */
function vh360_lead_capture_is_excluded_view() {
    $excluded = trim((string) get_theme_mod('vh360_lead_capture_excluded_pages', ''));
    if ('' === $excluded || !is_singular()) {
        return false;
    }

    $current_id = get_queried_object_id();
    $post = get_post($current_id);
    if (!$post) {
        return false;
    }

    $tokens = array_filter(array_map('trim', explode(',', $excluded)));
    foreach ($tokens as $token) {
        if ((string) $current_id === $token || $post->post_name === sanitize_title($token)) {
            return true;
        }
    }

    return false;
}

/**
 * Detect whether the lead capture CTA should show on the current view.
 *
 * @return bool
 */
function vh360_lead_capture_should_show_on_current_view() {
    if ((bool) get_theme_mod('vh360_lead_capture_show_all_site', 0)) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_homepage', 1) && (is_front_page() || is_home())) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_activity_feed', 0) && is_page_template('template-activity-feed.php')) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_members_directory', 0) && is_page_template('template-members-directory.php')) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_course_catalog', 0) && is_page_template('template-course-catalog.php')) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_single_video', 0) && is_singular('videohub360')) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_single_post', 0) && is_singular('post')) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_shop', 0) && function_exists('is_shop') && is_shop()) {
        return true;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_show_product', 0) && function_exists('is_product') && is_product()) {
        return true;
    }

    return false;
}

/**
 * Determine whether lead capture can render for visitors on the current request.
 *
 * @return bool
 */
function vh360_lead_capture_is_enabled() {
    if (!(bool) get_theme_mod('vh360_lead_capture_enabled', 0)) {
        return false;
    }

    if ((bool) get_theme_mod('vh360_lead_capture_hide_logged_in', 0) && is_user_logged_in()) {
        return false;
    }

    if (!vh360_lead_capture_has_form_source()) {
        return false;
    }

    if (vh360_lead_capture_is_excluded_view()) {
        return false;
    }

    return vh360_lead_capture_should_show_on_current_view();
}

/**
 * Whether admins should see an unconfigured placeholder.
 *
 * @return bool
 */
function vh360_lead_capture_should_show_admin_placeholder() {
    return (bool) get_theme_mod('vh360_lead_capture_enabled', 0)
        && current_user_can('manage_options')
        && !vh360_lead_capture_has_form_source()
        && !vh360_lead_capture_is_excluded_view()
        && vh360_lead_capture_should_show_on_current_view();
}

/**
 * Get rendered form markup from shortcode or safe HTML.
 *
 * @return string
 */
function vh360_get_lead_capture_form_markup() {
    $source = get_theme_mod('vh360_lead_capture_form_source', 'shortcode');

    if ('embed_html' === $source) {
        $html = trim((string) get_theme_mod('vh360_lead_capture_embed_html', ''));
        return '' === $html ? '' : wp_kses($html, vh360_lead_capture_allowed_html());
    }

    $shortcode = trim((string) get_theme_mod('vh360_lead_capture_shortcode', ''));
    if ('' === $shortcode) {
        return '';
    }

    $first_tag = '';
    if (preg_match('/\[\s*([a-zA-Z0-9_\-]+)/', $shortcode, $matches)) {
        $first_tag = $matches[1];
    }

    if ($first_tag && !shortcode_exists($first_tag)) {
        if (current_user_can('manage_options')) {
            return '<div class="vh360-lead-capture__notice" role="status">' . esc_html(sprintf(__('The configured shortcode [%s] is not available. Please activate the form plugin or update the Lead Capture shortcode.', 'videohub360-theme'), $first_tag)) . '</div>';
        }

        return '';
    }

    $rendered = do_shortcode($shortcode);

    if ($rendered === $shortcode && $first_tag && current_user_can('manage_options')) {
        return '<div class="vh360-lead-capture__notice" role="status">' . esc_html__('The configured lead capture shortcode did not render output. Please verify the shortcode and plugin.', 'videohub360-theme') . '</div>';
    }

    return $rendered;
}

/**
 * Render lead capture markup.
 *
 * @param string $context Render context.
 */
function vh360_render_lead_capture($context = '') {
    static $rendered_contexts = array();
    global $vh360_lead_capture_rendered;

    $display_mode = get_theme_mod('vh360_lead_capture_display_mode', 'inline');
    $context = $context ? sanitize_key($context) : $display_mode;
    $is_placeholder = false;

    if (!vh360_lead_capture_is_enabled()) {
        if (!vh360_lead_capture_should_show_admin_placeholder()) {
            return;
        }
        $is_placeholder = true;
    }

    if (isset($rendered_contexts[$context])) {
        return;
    }

    $rendered_contexts[$context] = true;
    $vh360_lead_capture_rendered = true;

    $args = array(
        'context' => $context,
        'display_mode' => $display_mode,
        'form_markup' => $is_placeholder ? '' : vh360_get_lead_capture_form_markup(),
        'headline' => get_theme_mod('vh360_lead_capture_headline', __('Stay connected', 'videohub360-theme')),
        'description' => get_theme_mod('vh360_lead_capture_description', __('Get updates, announcements, and new content delivered to your inbox.', 'videohub360-theme')),
        'button_text' => get_theme_mod('vh360_lead_capture_button_text', __('Sign up', 'videohub360-theme')),
        'consent_text' => get_theme_mod('vh360_lead_capture_consent_text', __('By signing up, you agree to receive updates and marketing emails.', 'videohub360-theme')),
        'success_message' => get_theme_mod('vh360_lead_capture_success_message', __('Thanks for signing up.', 'videohub360-theme')),
        'hide_after_dismiss' => (bool) get_theme_mod('vh360_lead_capture_hide_after_dismiss', 1),
        'frequency_days' => absint(get_theme_mod('vh360_lead_capture_frequency_days', 7)),
        'popup_delay' => absint(get_theme_mod('vh360_lead_capture_popup_delay', 5)),
        'is_placeholder' => $is_placeholder,
    );

    if (!$is_placeholder && '' === trim((string) $args['form_markup'])) {
        return;
    }

    get_template_part('template-parts/marketing/lead-capture', null, $args);
}

/**
 * Render inline lead capture placement.
 *
 * @param string $context Context suffix.
 */
function vh360_render_inline_lead_capture($context = 'inline') {
    if ('inline' !== get_theme_mod('vh360_lead_capture_display_mode', 'inline')) {
        return;
    }

    vh360_render_lead_capture($context);
}

/**
 * Footer render fallback and global modes.
 */
function vh360_lead_capture_footer_render() {
    global $vh360_lead_capture_rendered;

    $display_mode = get_theme_mod('vh360_lead_capture_display_mode', 'inline');

    if (in_array($display_mode, array('popup', 'floating_button', 'footer_banner'), true)) {
        vh360_render_lead_capture('footer-' . $display_mode);
        return;
    }

    if (!empty($vh360_lead_capture_rendered)) {
        return;
    }

    // Safe inline fallback for locations where a template-specific insertion point is unavailable.
    vh360_render_inline_lead_capture('footer-inline');
}
add_action('wp_footer', 'vh360_lead_capture_footer_render', 25);

/**
 * Enqueue assets only when the current request can display lead capture.
 */
function vh360_lead_capture_enqueue_assets() {
    if (!vh360_lead_capture_is_enabled() && !vh360_lead_capture_should_show_admin_placeholder()) {
        return;
    }

    wp_enqueue_style(
        'vh360-lead-capture',
        VH360_THEME_URI . '/assets/css/lead-capture.css',
        array('videohub360-theme-style'),
        VH360_THEME_VERSION
    );

    wp_enqueue_script(
        'vh360-lead-capture',
        VH360_THEME_URI . '/assets/js/lead-capture.js',
        array(),
        VH360_THEME_VERSION,
        true
    );

    wp_localize_script('vh360-lead-capture', 'vh360LeadCapture', array(
        'displayMode' => get_theme_mod('vh360_lead_capture_display_mode', 'inline'),
        'popupDelay' => absint(get_theme_mod('vh360_lead_capture_popup_delay', 5)),
        'frequencyDays' => absint(get_theme_mod('vh360_lead_capture_frequency_days', 7)),
        'hideAfterDismiss' => (bool) get_theme_mod('vh360_lead_capture_hide_after_dismiss', 1),
        'storageKey' => 'vh360_lead_capture_dismissed',
        'isCustomizerPreview' => is_customize_preview(),
    ));
}
add_action('wp_enqueue_scripts', 'vh360_lead_capture_enqueue_assets', 30);
