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

    function getErrorMessage(response) {
        return (response && response.data && response.data.message) ? response.data.message : (config.i18n ? config.i18n.error : 'Error');
    }

    function captureButtonState($btn) {
        var originalText = $btn.text();
        var wasDisabled = $btn.prop('disabled');

        return function restoreButton() {
            $btn.prop('disabled', wasDisabled).text(originalText);
        };
    }

    /**
     * Start a new Stripe subscription checkout
     */
    $(document).on('click', '.vh360-start-subscription', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var planKey = $btn.data('plan-key');
        var restoreButton = captureButtonState($btn);

        if (!planKey) {
            alert(getErrorMessage());
            restoreButton();
            return;
        }

        if (!config.ajaxUrl || !config.nonces || !config.nonces.checkout) {
            alert(getErrorMessage());
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

            var msg = getErrorMessage(response);
            alert(msg);
            restoreButton();
        }).fail(function() {
            alert(getErrorMessage());
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
        var restoreButton = captureButtonState($btn);

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
            if (response && response.success) {
                window.location.reload();
            } else {
                var msg = getErrorMessage(response);
                alert(msg);
                restoreButton();
            }
        }).fail(function() {
            alert(getErrorMessage());
            restoreButton();
        });
    });

    /**
     * Reactivate subscription
     */
    $(document).on('click', '.vh360-reactivate-subscription', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var membershipId = $btn.data('membership-id');
        var restoreButton = captureButtonState($btn);

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_reactivate_subscription',
            nonce: config.nonces ? config.nonces.manage : '',
            membership_id: membershipId
        }, function(response) {
            if (response && response.success) {
                window.location.reload();
            } else {
                var msg = getErrorMessage(response);
                alert(msg);
                restoreButton();
            }
        }).fail(function() {
            alert(getErrorMessage());
            restoreButton();
        });
    });

    /**
     * Open Stripe billing portal
     */
    $(document).on('click', '.vh360-open-portal, .vh360-manage-billing', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var restoreButton = captureButtonState($btn);

        $btn.prop('disabled', true).text(config.i18n ? config.i18n.loading : 'Loading...');

        $.post(config.ajaxUrl, {
            action: 'vh360_stripe_portal',
            nonce: config.nonces ? config.nonces.portal : ''
        }, function(response) {
            if (response && response.success && response.data && response.data.portal_url) {
                window.location.href = response.data.portal_url;
            } else {
                var msg = getErrorMessage(response);
                alert(msg);
                restoreButton();
            }
        }).fail(function() {
            alert(getErrorMessage());
            restoreButton();
        });
    });


    $(function() {
        if (!config.selectedPlan || !config.autoCheckout) {
            return;
        }

        var $button = $('.vh360-start-subscription[data-plan-key="' + config.selectedPlan + '"]').first();
        if (!$button.length || $button.data('vh360AutoCheckoutStarted')) {
            return;
        }

        $button.data('vh360AutoCheckoutStarted', true).trigger('click');
    });

})(jQuery);
