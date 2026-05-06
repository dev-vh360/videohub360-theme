<?php
/**
 * Privacy support: data export and erasure via WordPress privacy tools.
 *
 * @package VideoHub360_Affiliates
 */

if (!defined('ABSPATH')) exit;

class VH360_Affiliates_Privacy {

    /** @var VH360_Affiliates_Privacy|null */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers',   array($this, 'register_eraser'));
    }

    public function register_exporter($exporters) {
        $exporters['vh360-affiliates'] = array(
            'exporter_friendly_name' => __('VideoHub360 Affiliates', 'videohub360-affiliates'),
            'callback'               => array($this, 'export_user_data'),
        );
        return $exporters;
    }

    public function register_eraser($erasers) {
        $erasers['vh360-affiliates'] = array(
            'eraser_friendly_name' => __('VideoHub360 Affiliates', 'videohub360-affiliates'),
            'callback'             => array($this, 'erase_user_data'),
        );
        return $erasers;
    }

    /**
     * Export all affiliate personal data for a given email address.
     *
     * @param string $email
     * @param int    $page
     * @return array
     */
    public function export_user_data($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return array('data' => array(), 'done' => true);
        }

        $affiliate = VH360_Affiliates_Database::get_affiliate_by_user_id($user->ID);
        if (!$affiliate) {
            return array('data' => array(), 'done' => true);
        }

        $data_groups = array();

        // Affiliate account data
        $data_groups[] = array(
            'group_id'    => 'vh360-affiliate-account',
            'group_label' => __('Affiliate Account', 'videohub360-affiliates'),
            'item_id'     => 'affiliate-' . $affiliate->id,
            'data'        => array(
                array('name' => __('Affiliate Code',    'videohub360-affiliates'), 'value' => $affiliate->affiliate_code),
                array('name' => __('Status',            'videohub360-affiliates'), 'value' => $affiliate->status),
                array('name' => __('Payment Email',     'videohub360-affiliates'), 'value' => $affiliate->payment_email),
                array('name' => __('Commission Type',   'videohub360-affiliates'), 'value' => $affiliate->commission_type),
                array('name' => __('Commission Rate',   'videohub360-affiliates'), 'value' => $affiliate->commission_rate),
                array('name' => __('Created',           'videohub360-affiliates'), 'value' => $affiliate->created_at),
            ),
        );

        // Commission summaries
        $totals = VH360_Affiliates_Database::get_commission_totals($affiliate->id);
        $data_groups[] = array(
            'group_id'    => 'vh360-affiliate-commissions',
            'group_label' => __('Affiliate Commission Totals', 'videohub360-affiliates'),
            'item_id'     => 'affiliate-commissions-' . $affiliate->id,
            'data'        => array(
                array('name' => __('Pending',  'videohub360-affiliates'), 'value' => $totals['pending']),
                array('name' => __('Approved', 'videohub360-affiliates'), 'value' => $totals['approved']),
                array('name' => __('Paid',     'videohub360-affiliates'), 'value' => $totals['paid']),
            ),
        );

        return array('data' => $data_groups, 'done' => true);
    }

    /**
     * Anonymise affiliate personal data for a given email address.
     *
     * The affiliate record itself (commissions, referrals, payouts) is kept
     * for financial/audit integrity. Only personal identifiers are removed.
     *
     * @param string $email
     * @param int    $page
     * @return array
     */
    public function erase_user_data($email, $page = 1) {
        $user = get_user_by('email', $email);
        if (!$user) {
            return array('items_removed' => 0, 'items_retained' => 0, 'messages' => array(), 'done' => true);
        }

        $affiliate = VH360_Affiliates_Database::get_affiliate_by_user_id($user->ID);
        if (!$affiliate) {
            return array('items_removed' => 0, 'items_retained' => 0, 'messages' => array(), 'done' => true);
        }

        // Anonymise payment email; keep financial records intact
        VH360_Affiliates_Database::update_affiliate($affiliate->id, array(
            'payment_email' => '',
            'notes'         => __('[Personal data erased]', 'videohub360-affiliates'),
        ));

        return array(
            'items_removed'  => 1,
            'items_retained' => 1,
            'messages'       => array(
                __('Affiliate payment email was erased. Commission and payout records are retained for financial compliance.', 'videohub360-affiliates'),
            ),
            'done'           => true,
        );
    }
}
