/**
 * VH360 Membership Management Frontend
 *
 * Handles payment checkout initiation, subscription cancellation,
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
     * Start a new recurring subscription checkout
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
     * Open billing portal
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

    /**
     * Dashboard recurring plan interval tabs
     */
    $(document).on('click', '[data-vh360-dashboard-plan-tab]', function(e) {
        e.preventDefault();

        var $tab = $(this);
        var interval = $tab.data('vh360-dashboard-plan-tab');
        var $switcher = $tab.closest('[data-vh360-dashboard-plan-switcher]');

        if (!interval || !$switcher.length) {
            return;
        }

        $switcher.find('[data-vh360-dashboard-plan-tab]').each(function() {
            var $item = $(this);
            var isActive = $item.data('vh360-dashboard-plan-tab') === interval;
            $item.toggleClass('is-active', isActive)
                .attr('aria-selected', isActive ? 'true' : 'false')
                .attr('tabindex', isActive ? '0' : '-1');
        });

        $switcher.find('[data-vh360-dashboard-plan-panel]').each(function() {
            var $panel = $(this);
            var isActive = $panel.data('vh360-dashboard-plan-panel') === interval;
            $panel.toggleClass('is-active', isActive).prop('hidden', !isActive);
        });
    });


    $(function() {
        if (!config.selectedPlan || !config.autoCheckout) {
            return;
        }

        var $card = $('.vh360-dashboard-plan-group-card.is-selected').first();
        var $button = $('.vh360-start-subscription[data-plan-key="' + config.selectedPlan + '"]').first();

        if ($card.length) {
            window.VH360ScrollContext && window.VH360ScrollContext.scrollElementIntoView ? window.VH360ScrollContext.scrollElementIntoView($card.get(0), 80, { behavior: 'smooth' }) : $('html, body').animate({ scrollTop: Math.max(0, $card.offset().top - 80) }, 250);
        }

        if (!$button.length || $button.data('vh360AutoCheckoutStarted')) {
            return;
        }

        $button.data('vh360AutoCheckoutStarted', true).trigger('click');
    });

})(jQuery);
