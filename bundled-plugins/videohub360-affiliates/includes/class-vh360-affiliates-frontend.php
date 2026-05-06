<?php
/**
 * Frontend shortcodes: affiliate registration and dashboard.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Frontend {

    /** @var VH360_Affiliates_Frontend|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('vh360_affiliate_registration', array($this, 'shortcode_registration'));
        add_shortcode('vh360_affiliate_dashboard',    array($this, 'shortcode_dashboard'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX: update payment email
        add_action('wp_ajax_vh360_aff_update_payment_email', array($this, 'ajax_update_payment_email'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('vh360-affiliates-frontend', VH360_AFFILIATES_URL . 'assets/css/frontend.css', array(), VH360_AFFILIATES_VERSION);
        if (!is_user_logged_in()) {
            return;
        }
        wp_enqueue_script('vh360-affiliates-frontend', VH360_AFFILIATES_URL . 'assets/js/frontend.js', array('jquery'), VH360_AFFILIATES_VERSION, true);
        wp_localize_script('vh360-affiliates-frontend', 'vh360AffFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('vh360_aff_frontend'),
            'copied'  => esc_html__('Copied!', 'videohub360-affiliates'),
        ));
    }

    // -----------------------------------------------------------------------
    // Registration shortcode
    // -----------------------------------------------------------------------

    public function shortcode_registration($atts) {
        if (!is_user_logged_in()) {
            return '<div class="vh360-affiliate-page"><div class="vh360-affiliate-status-card vh360-affiliate-status-login">'
                . '<h3>' . esc_html__('Sign in to apply', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('You need an account before joining the affiliate program.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        $user_id   = get_current_user_id();
        $existing  = VH360_Affiliates_Database::get_affiliate_by_user_id($user_id);

        if ($existing) {
            $status_class = 'vh360-affiliate-status-' . sanitize_html_class($existing->status);
            $label        = esc_html(vh360_affiliates_status_label($existing->status));

            if ($existing->status === 'pending') {
                $title = esc_html__('Application Under Review', 'videohub360-affiliates');
                $desc  = esc_html__('Your affiliate application is being reviewed. You will be notified by email once a decision is made.', 'videohub360-affiliates');
            } elseif ($existing->status === 'active') {
                $title = esc_html__('You Are an Active Affiliate', 'videohub360-affiliates');
                $desc  = esc_html__('Your affiliate account is active. Visit your dashboard to view your referral link and commissions.', 'videohub360-affiliates');
            } elseif ($existing->status === 'rejected') {
                $title = esc_html__('Application Not Approved', 'videohub360-affiliates');
                $desc  = esc_html__('Your affiliate application was not approved. Please contact support for more information.', 'videohub360-affiliates');
            } elseif ($existing->status === 'suspended') {
                $title = esc_html__('Account Suspended', 'videohub360-affiliates');
                $desc  = esc_html__('Your affiliate account is currently suspended. Please contact support.', 'videohub360-affiliates');
            } else {
                $title = sprintf(
                    /* translators: %s: status label */
                    esc_html__('Application Status: %s', 'videohub360-affiliates'),
                    $label
                );
                $desc = '';
            }

            return '<div class="vh360-affiliate-page"><div class="vh360-affiliate-status-card ' . esc_attr($status_class) . '">'
                . '<h3>' . $title . '</h3>'
                . ($desc ? '<p>' . $desc . '</p>' : '')
                . '</div></div>';
        }

        // Handle form submission
        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vh360_aff_reg_nonce'])) {
            $result = $this->process_registration();
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $success = __('Your affiliate application has been submitted. You will be notified by email once it is reviewed.', 'videohub360-affiliates');
            }
        }

        ob_start();
        include VH360_AFFILIATES_DIR . 'templates/affiliate-registration.php';
        return ob_get_clean();
    }

    /**
     * Process the registration form POST.
     *
     * @return int|WP_Error Affiliate ID or error.
     */
    private function process_registration() {
        if (!isset($_POST['vh360_aff_reg_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_aff_reg_nonce'])), 'vh360_aff_registration')) {
            return new WP_Error('nonce', __('Security check failed.', 'videohub360-affiliates'));
        }

        if (!is_user_logged_in()) {
            return new WP_Error('auth', __('You must be logged in.', 'videohub360-affiliates'));
        }

        $user_id = get_current_user_id();
        if (VH360_Affiliates_Database::get_affiliate_by_user_id($user_id)) {
            return new WP_Error('duplicate', __('You have already applied.', 'videohub360-affiliates'));
        }

        $user          = wp_get_current_user();
        $payment_email = isset($_POST['payment_email'])
            ? sanitize_email(wp_unslash($_POST['payment_email']))
            : $user->user_email;

        $settings  = vh360_affiliates_get_settings();
        $status    = empty($settings['require_manual_approval']) ? 'active' : 'pending';
        $code      = vh360_affiliates_generate_code($user->user_login);

        $aff_id = VH360_Affiliates_Database::insert_affiliate(array(
            'user_id'          => $user_id,
            'affiliate_code'   => $code,
            'status'           => $status,
            'commission_type'  => $settings['default_commission_type'],
            'commission_rate'  => $settings['default_commission_rate'],
            'payment_email'    => $payment_email,
        ));

        if (!$aff_id) {
            return new WP_Error('db', __('Could not save your application. Please try again.', 'videohub360-affiliates'));
        }

        // Notify site admin
        vh360_affiliates_send_email(
            get_option('admin_email'),
            __('New affiliate application received', 'videohub360-affiliates'),
            sprintf(
                /* translators: %1$s: display name, %2$s: email */
                __('A new affiliate application has been received from %1$s (%2$s). Please review it in the Affiliates admin panel.', 'videohub360-affiliates'),
                sanitize_text_field($user->display_name),
                sanitize_email($user->user_email)
            )
        );

        // If auto-approved, notify the affiliate too
        if ($status === 'active') {
            vh360_affiliates_send_email(
                $user->user_email,
                __('Your affiliate application has been approved', 'videohub360-affiliates'),
                sprintf(
                    /* translators: %s: affiliate code */
                    __("Your affiliate application has been automatically approved.\n\nYour affiliate code: %s", 'videohub360-affiliates'),
                    $code
                )
            );
        }

        return $aff_id;
    }

    // -----------------------------------------------------------------------
    // Dashboard shortcode
    // -----------------------------------------------------------------------

    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="vh360-affiliate-dashboard"><div class="vh360-affiliate-status-card vh360-affiliate-status-login">'
                . '<h3>' . esc_html__('Sign in to view your dashboard', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('You must be logged in to access your affiliate dashboard.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        $user_id   = get_current_user_id();
        $affiliate = VH360_Affiliates_Database::get_affiliate_by_user_id($user_id);

        if (!$affiliate) {
            return '<div class="vh360-affiliate-dashboard"><div class="vh360-affiliate-status-card vh360-affiliate-status-login">'
                . '<h3>' . esc_html__('Not Registered as an Affiliate', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('You are not registered as an affiliate. Apply through the affiliate registration page.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        if ($affiliate->status === 'pending') {
            return '<div class="vh360-affiliate-dashboard"><div class="vh360-affiliate-status-card vh360-affiliate-status-pending">'
                . '<h3>' . esc_html__('Application Under Review', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('Your affiliate application is currently under review. You will be notified once it is approved.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        if ($affiliate->status === 'rejected') {
            return '<div class="vh360-affiliate-dashboard"><div class="vh360-affiliate-status-card vh360-affiliate-status-rejected">'
                . '<h3>' . esc_html__('Application Not Approved', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('Your affiliate application was not approved. Please contact support for more information.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        if ($affiliate->status === 'suspended') {
            return '<div class="vh360-affiliate-dashboard"><div class="vh360-affiliate-status-card vh360-affiliate-status-suspended">'
                . '<h3>' . esc_html__('Account Suspended', 'videohub360-affiliates') . '</h3>'
                . '<p>' . esc_html__('Your affiliate account is currently suspended. Please contact support.', 'videohub360-affiliates') . '</p>'
                . '</div></div>';
        }

        // Active — show full dashboard
        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vh360_aff_email_nonce'])) {
            $result = $this->process_email_update($affiliate->id);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $success   = __('Payment email updated.', 'videohub360-affiliates');
                $affiliate = VH360_Affiliates_Database::get_affiliate_by_user_id($user_id); // reload
            }
        }

        $totals       = VH360_Affiliates_Database::get_commission_totals($affiliate->id);
        $visits       = VH360_Affiliates_Database::get_visit_count($affiliate->id);
        $refs         = VH360_Affiliates_Database::get_referral_count($affiliate->id);
        $commissions  = VH360_Affiliates_Database::get_recent_commissions($affiliate->id);
        $referrals    = VH360_Affiliates_Database::get_recent_referrals($affiliate->id);
        $payouts      = VH360_Affiliates_Database::get_payouts($affiliate->id);
        $referral_url = vh360_affiliates_build_referral_url($affiliate->affiliate_code);

        ob_start();
        include VH360_AFFILIATES_DIR . 'templates/affiliate-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Process payment email update from dashboard.
     *
     * @param int $affiliate_id
     * @return true|WP_Error
     */
    private function process_email_update($affiliate_id) {
        if (!isset($_POST['vh360_aff_email_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vh360_aff_email_nonce'])), 'vh360_aff_update_email')) {
            return new WP_Error('nonce', __('Security check failed.', 'videohub360-affiliates'));
        }

        $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($affiliate_id);
        if (!$affiliate || (int) $affiliate->user_id !== (int) get_current_user_id()) {
            return new WP_Error('auth', __('Access denied.', 'videohub360-affiliates'));
        }

        $email = sanitize_email(wp_unslash($_POST['payment_email'] ?? ''));
        if (!is_email($email)) {
            return new WP_Error('email', __('Please provide a valid email address.', 'videohub360-affiliates'));
        }

        VH360_Affiliates_Database::update_affiliate($affiliate_id, array('payment_email' => $email));
        return true;
    }

    /**
     * AJAX handler: update payment email.
     */
    public function ajax_update_payment_email() {
        check_ajax_referer('vh360_aff_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Not authenticated.', 'videohub360-affiliates')));
        }

        $user_id   = get_current_user_id();
        $affiliate = VH360_Affiliates_Database::get_affiliate_by_user_id($user_id);

        if (!$affiliate) {
            wp_send_json_error(array('message' => __('No affiliate record found.', 'videohub360-affiliates')));
        }

        $email = sanitize_email(wp_unslash($_POST['payment_email'] ?? ''));
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'videohub360-affiliates')));
        }

        VH360_Affiliates_Database::update_affiliate($affiliate->id, array('payment_email' => $email));
        wp_send_json_success(array('message' => __('Payment email updated.', 'videohub360-affiliates')));
    }
}
