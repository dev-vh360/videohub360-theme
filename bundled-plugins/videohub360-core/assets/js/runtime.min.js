/**
 * VideoHub360 lightweight frontend runtime.
 */
(function(window) {
    'use strict';

    window.vh360 = window.vh360 || {};
    window.vh360IsDebug = window.vh360IsDebug || function() {
        return window.__VH360_DEBUG === true;
    };
    window.vh360Log = window.vh360Log || function() {
        if (window.vh360IsDebug() && window.console && window.console.log) {
            window.console.log.apply(window.console, arguments);
        }
    };
    window.vh360Warn = window.vh360Warn || function() {
        if (window.vh360IsDebug() && window.console && window.console.warn) {
            window.console.warn.apply(window.console, arguments);
        }
    };
    window.vh360Error = window.vh360Error || function() {
        if (window.console && window.console.error) {
            window.console.error.apply(window.console, arguments);
        }
    };

    window.vh360ShowLoginModal = window.vh360ShowLoginModal || function() {
        if (window.vh360Data && window.vh360Data.userLoginUrl) {
            window.location.href = window.vh360Data.userLoginUrl;
        }
    };
})(window);
