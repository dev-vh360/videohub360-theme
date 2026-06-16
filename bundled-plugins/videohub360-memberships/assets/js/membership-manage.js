/**
 * VH360 Membership Management Frontend
 *
 * Handles Stripe checkout initiation, subscription cancellation,
 * reactivation, and billing portal access from the frontend.
 *
 * @package VideoHub360_Memberships
 * @since 1.0.0
 */
(function($) {
    'use strict';

    var config = window.vh360MembershipManage || {};

    /**
     * Start a new Stripe subscription checkout
     */
    $(document).on('click', '.vh360-start-subscription', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var planKey = $btn.data('plan-key');
        var originalText = $btn.text();
        var wasDisabled = $btn.prop('disabled');

        function restoreButton() {
            $btn.prop('disabled', wasDisabled).text(originalText);
        }

        if (!planKey) {
            alert(config.i18n ? config.i18n.error : 'Error');
            restoreButton();
            return;
        }

        if (!config.ajaxUrl || !config.nonces || !config.nonces.checkout) {
            alert(config.i18n ? config.i18n.error : 'Error');
            restoreButton();
            return;
        }

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_create_checkout',
            nonce: config.nonces.checkout,
            plan_key: planKey
        }, function(response) {
            if (response && response.success && response.data && response.data.checkout_url) {
                window.location.href = response.data.checkout_url;
                return;
            }

            var msg = (response && response.data && response.data.message) ? response.data.message : (config.i18n ? config.i18n.error : 'Error');
            alert(msg);
            restoreButton();
        }).fail(function() {
            alert(config.i18n ? config.i18n.error : 'Error');
            restoreButton();
        });
    });

    /**
     * Cancel subscription
     */
    $(document).on('click', '.vh360-cancel-subscription', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var membershipId = $btn.data('membership-id');

        var confirmMsg = config.i18n ? config.i18n.confirmCancel : 'Are you sure you want to cancel?';
        if (!confirm(confirmMsg)) {
            return;
        }

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_cancel_subscription',
            nonce: config.nonces ? config.nonces.manage : '',
            membership_id: membershipId
        }, function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                var msg = (response.data && response.data.message) ? response.data.message : (config.i18n ? config.i18n.error : 'Error');
                alert(msg);
                $btn.prop('disabled', false).text('Cancel Subscription');
            }
        }).fail(function() {
            alert(config.i18n ? config.i18n.error : 'Error');
            $btn.prop('disabled', false).text('Cancel Subscription');
        });
    });

    /**
     * Reactivate subscription
     */
    $(document).on('click', '.vh360-reactivate-subscription', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var membershipId = $btn.data('membership-id');

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_reactivate_subscription',
            nonce: config.nonces ? config.nonces.manage : '',
            membership_id: membershipId
        }, function(response) {
            if (response.success) {
                window.location.reload();
            } else {
                var msg = (response.data && response.data.message) ? response.data.message : (config.i18n ? config.i18n.error : 'Error');
                alert(msg);
                $btn.prop('disabled', false).text('Reactivate Subscription');
            }
        }).fail(function() {
            alert(config.i18n ? config.i18n.error : 'Error');
            $btn.prop('disabled', false).text('Reactivate Subscription');
        });
    });

    /**
     * Open Stripe billing portal
     */
    $(document).on('click', '.vh360-open-portal', function(e) {
        e.preventDefault();
        var $btn = $(this);

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_portal',
            nonce: config.nonces ? config.nonces.portal : ''
        }, function(response) {
            if (response.success && response.data && response.data.portal_url) {
                window.location.href = response.data.portal_url;
            } else {
                var msg = (response.data && response.data.message) ? response.data.message : (config.i18n ? config.i18n.error : 'Error');
                alert(msg);
                $btn.prop('disabled', false).text('Manage Billing');
            }
        }).fail(function() {
            alert(config.i18n ? config.i18n.error : 'Error');
            $btn.prop('disabled', false).text('Manage Billing');
        });
    });

})(jQuery);
