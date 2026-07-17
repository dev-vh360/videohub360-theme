(function () {
    'use strict';

    var config = window.VH360ConsentAdmin || {};
    var modeField = document.querySelector('[data-vh360-consent-mode]');
    var labels = config.modeLabels || {};
    var descriptions = config.modeDescriptions || {};

    function appliesToMode(element, mode) {
        var modes = (element.getAttribute('data-vh360-consent-modes') || '').split(/\s+/);
        return modes.indexOf(mode) !== -1;
    }

    function updateModeVisibility() {
        if (!modeField) {
            return;
        }

        var mode = modeField.value;
        document.querySelectorAll('[data-vh360-consent-modes]').forEach(function (element) {
            element.hidden = !appliesToMode(element, mode);
        });

        document.querySelectorAll('[data-vh360-consent-mode-label]').forEach(function (element) {
            element.textContent = labels[mode] || '';
        });

        document.querySelectorAll('[data-vh360-consent-mode-description]').forEach(function (element) {
            element.textContent = descriptions[mode] || '';
        });
    }

    if (modeField) {
        modeField.addEventListener('change', updateModeVisibility);
        updateModeVisibility();
    }
}());
