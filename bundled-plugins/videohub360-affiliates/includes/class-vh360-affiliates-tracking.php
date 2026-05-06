<?php
/**
 * Referral link and cookie tracking.
 *
 * Detects the referral query parameter on incoming requests, validates the
 * affiliate code, records a visit and stores referral cookies.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Tracking {

    /** @var VH360_Affiliates_Tracking|null */
    private static $instance = null;

    /** Cookie names */
    const COOKIE_AFF_ID    = 'vh360_affiliate_id';
    const COOKIE_AFF_CODE  = 'vh360_affiliate_code';
    const COOKIE_VISIT_ID  = 'vh360_affiliate_visit_id';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Run early so cookies are set before any output
        add_action('init', array($this, 'handle_referral_visit'), 1);
    }

    /**
     * Check incoming request for referral parameter and process if found.
     */
    public function handle_referral_visit() {
        // Never track admin users
        if (is_admin()) {
            return;
        }

        $settings = vh360_affiliates_get_settings();
        $query_var = vh360_affiliates_query_var();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (empty($_GET[$query_var])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $raw_code = sanitize_text_field(wp_unslash($_GET[$query_var]));
        $affiliate = vh360_affiliates_get_active_affiliate($raw_code);
        if (!$affiliate) {
            return;
        }

        // Block self-referrals
        if (empty($settings['allow_self_referrals']) && is_user_logged_in()) {
            if ((int) $affiliate->user_id === (int) get_current_user_id()) {
                return;
            }
        }

        // Respect attribution model: first_click → do not overwrite existing valid cookie
        $model = $settings['attribution_model'] ?? 'first_click';
        if ($model === 'first_click' && $this->has_valid_cookie()) {
            return;
        }

        // Record the visit
        $visit_id = $this->record_visit($affiliate);

        // Set / overwrite cookies
        $this->set_cookies($affiliate->id, $affiliate->affiliate_code, $visit_id, (int) $settings['cookie_duration']);
    }

    /**
     * Check whether a valid referral cookie already exists.
     *
     * @return bool
     */
    private function has_valid_cookie() {
        if (empty($_COOKIE[self::COOKIE_AFF_ID])) {
            return false;
        }
        $aff_id    = (int) $_COOKIE[self::COOKIE_AFF_ID];
        $affiliate = VH360_Affiliates_Database::get_affiliate_by_id($aff_id);
        return $affiliate && $affiliate->status === 'active';
    }

    /**
     * Record a visit row in the database.
     *
     * @param object $affiliate
     * @return int Visit ID
     */
    private function record_visit($affiliate) {
        $raw_uri      = isset($_SERVER['REQUEST_URI'])
            ? wp_unslash($_SERVER['REQUEST_URI'])
            : '';
        // Only use REQUEST_URI if it starts with '/' to avoid open-redirect
        $safe_uri     = (strlen($raw_uri) && $raw_uri[0] === '/') ? $raw_uri : '/';
        $landing_url  = esc_url_raw(home_url($safe_uri));
        $referrer_url = isset($_SERVER['HTTP_REFERER'])
            ? esc_url_raw(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])))
            : '';
        $ip_raw       = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';
        $ua_raw       = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '';

        $data = array(
            'affiliate_id'     => (int) $affiliate->id,
            'affiliate_code'   => sanitize_text_field($affiliate->affiliate_code),
            'landing_url'      => $landing_url,
            'referrer_url'     => $referrer_url,
            'ip_hash'          => $ip_raw ? vh360_affiliates_hash($ip_raw) : null,
            'user_agent_hash'  => $ua_raw ? vh360_affiliates_hash($ua_raw) : null,
            'visitor_hash'     => $ip_raw ? vh360_affiliates_hash($ip_raw . $ua_raw) : null,
        );

        return (int) VH360_Affiliates_Database::insert_visit($data);
    }

    /**
     * Store referral cookies.
     *
     * @param int    $affiliate_id
     * @param string $affiliate_code
     * @param int    $visit_id
     * @param int    $duration_days
     */
    private function set_cookies($affiliate_id, $affiliate_code, $visit_id, $duration_days = 30) {
        if (headers_sent()) {
            return;
        }

        $expires = time() + ($duration_days * DAY_IN_SECONDS);
        $path    = COOKIEPATH ? COOKIEPATH : '/';
        $domain  = defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
        $secure  = is_ssl();

        $opts = array(
            'expires'  => $expires,
            'path'     => $path,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        if ($domain) {
            $opts['domain'] = $domain;
        }

        setcookie(self::COOKIE_AFF_ID,   (string) $affiliate_id,   $opts);
        setcookie(self::COOKIE_AFF_CODE, $affiliate_code,           $opts);
        setcookie(self::COOKIE_VISIT_ID, (string) $visit_id,        $opts);

        // Also populate $_COOKIE so same-request reads work
        $_COOKIE[self::COOKIE_AFF_ID]   = (string) $affiliate_id;
        $_COOKIE[self::COOKIE_AFF_CODE] = $affiliate_code;
        $_COOKIE[self::COOKIE_VISIT_ID] = (string) $visit_id;
    }

    // -------------------------------------------------------------------
    // Static helpers used by WooCommerce integration
    // -------------------------------------------------------------------

    /**
     * Read affiliate attribution from current cookies.
     *
     * @return array|null Keys: affiliate_id, affiliate_code, visit_id — or null.
     */
    public static function get_cookie_attribution() {
        $aff_id   = isset($_COOKIE[self::COOKIE_AFF_ID])   ? (int) sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_AFF_ID]))   : 0;
        $aff_code = isset($_COOKIE[self::COOKIE_AFF_CODE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_AFF_CODE]))        : '';
        $visit_id = isset($_COOKIE[self::COOKIE_VISIT_ID]) ? (int) sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_VISIT_ID]))  : 0;

        if (!$aff_id || !$aff_code) {
            return null;
        }

        return array(
            'affiliate_id'   => $aff_id,
            'affiliate_code' => $aff_code,
            'visit_id'       => $visit_id,
        );
    }

    /**
     * Clear referral cookies.
     */
    public static function clear_cookies() {
        $path   = COOKIEPATH ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
        $opts   = array('expires' => time() - HOUR_IN_SECONDS, 'path' => $path, 'httponly' => true, 'samesite' => 'Lax');
        if ($domain) {
            $opts['domain'] = $domain;
        }
        setcookie(self::COOKIE_AFF_ID,   '', $opts);
        setcookie(self::COOKIE_AFF_CODE, '', $opts);
        setcookie(self::COOKIE_VISIT_ID, '', $opts);
        unset($_COOKIE[self::COOKIE_AFF_ID], $_COOKIE[self::COOKIE_AFF_CODE], $_COOKIE[self::COOKIE_VISIT_ID]);
    }
}
