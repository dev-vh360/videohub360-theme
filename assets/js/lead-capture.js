(function () {
    'use strict';

    var config = window.vh360LeadCapture || {};
    var storageKey = config.storageKey || 'vh360_lead_capture_dismissed';

    function getStorageTimestamp() {
        try {
            return parseInt(window.localStorage.getItem(storageKey), 10) || 0;
        } catch (error) {
            return 0;
        }
    }

    function setStorageTimestamp() {
        try {
            window.localStorage.setItem(storageKey, String(Date.now()));
        } catch (error) {
            // localStorage may be disabled; dismissal still works for this page view.
        }
    }

    function shouldRespectDismissal(root) {
        return root.getAttribute('data-hide-after-dismiss') === '1' || config.hideAfterDismiss === true;
    }

    function isDismissed(root) {
        if (!shouldRespectDismissal(root)) {
            return false;
        }

        var timestamp = getStorageTimestamp();
        if (!timestamp) {
            return false;
        }

        var days = parseInt(root.getAttribute('data-frequency-days'), 10);
        if (isNaN(days)) {
            days = parseInt(config.frequencyDays, 10) || 0;
        }

        if (days <= 0) {
            return true;
        }

        return Date.now() - timestamp < days * 24 * 60 * 60 * 1000;
    }

    function markDismissed(root) {
        root.setAttribute('data-dismissed', '1');
        if (shouldRespectDismissal(root)) {
            setStorageTimestamp();
        }
    }

    function showModal(root) {
        if (isDismissed(root)) {
            return;
        }

        var overlay = root.querySelector('.vh360-lead-capture__overlay');
        var modal = root.querySelector('.vh360-lead-capture__modal');
        if (!modal) {
            return;
        }

        if (overlay) {
            overlay.hidden = false;
        }
        modal.hidden = false;
        root.classList.add('is-open');

        var focusTarget = modal.querySelector('.vh360-lead-capture__close, input, button, textarea, select, a[href]');
        if (focusTarget && typeof focusTarget.focus === 'function') {
            focusTarget.focus();
        }
    }

    function closeModal(root, dismiss) {
        var overlay = root.querySelector('.vh360-lead-capture__overlay');
        var modal = root.querySelector('.vh360-lead-capture__modal');
        if (overlay) {
            overlay.hidden = true;
        }
        if (modal) {
            modal.hidden = true;
        }
        root.classList.remove('is-open');
        if (dismiss) {
            markDismissed(root);
        }
    }

    function dismissBlock(root) {
        markDismissed(root);
        root.hidden = true;
    }

    function initRoot(root) {
        var mode = root.getAttribute('data-display-mode') || config.displayMode || 'inline';

        if (mode === 'popup') {
            if (isDismissed(root)) {
                return;
            }
            var delay = parseInt(root.getAttribute('data-popup-delay'), 10);
            if (isNaN(delay)) {
                delay = parseInt(config.popupDelay, 10) || 0;
            }
            window.setTimeout(function () {
                showModal(root);
            }, Math.max(0, delay) * 1000);
        }

        if (mode === 'floating_button' && isDismissed(root)) {
            root.hidden = true;
            return;
        }

        if (mode === 'footer_banner' && isDismissed(root)) {
            root.hidden = true;
            return;
        }

        var floatingButton = root.querySelector('.vh360-lead-capture__floating-button');
        if (floatingButton) {
            floatingButton.addEventListener('click', function () {
                showModal(root);
            });
        }

        var overlay = root.querySelector('.vh360-lead-capture__overlay');
        if (overlay) {
            overlay.addEventListener('click', function () {
                closeModal(root, true);
            });
        }

        root.querySelectorAll('.vh360-lead-capture__close').forEach(function (button) {
            button.addEventListener('click', function () {
                if (mode === 'footer_banner' || mode === 'inline') {
                    dismissBlock(root);
                } else {
                    closeModal(root, true);
                }
            });
        });

        root.addEventListener('submit', function () {
            var success = root.querySelector('.vh360-lead-capture__success');
            if (success) {
                success.hidden = false;
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.vh360-lead-capture.is-open').forEach(function (root) {
            closeModal(root, true);
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.vh360-lead-capture').forEach(initRoot);
    });
}());
