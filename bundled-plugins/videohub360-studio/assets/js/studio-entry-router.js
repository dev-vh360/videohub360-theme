(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-entry-router]');
    if (!root) { return; }

    try {
        if (window.VH360Storage && typeof window.VH360Storage.removePreference === 'function') {
            window.VH360Storage.removePreference('vh360StudioMode');
        } else {
            window.localStorage.removeItem('vh360StudioMode');
        }
    } catch (error) {}

    const params = new URLSearchParams(window.location.search);
    const explicit = params.get('studio_mode');
    if (explicit === 'mobile' || explicit === 'desktop') { return; }
    const mode = (function () {
        const compact = window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
        const coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
        const touch = navigator.maxTouchPoints > 0 || 'ontouchstart' in window;
        return compact && coarse && touch ? 'mobile' : 'desktop';
    })();
    const url = mode === 'mobile' ? root.dataset.mobileUrl : root.dataset.desktopUrl;
    if (url) { window.location.replace(url); }
})();
