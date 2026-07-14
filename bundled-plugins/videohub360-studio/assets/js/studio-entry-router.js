(function () {
    'use strict';
    const root = document.querySelector('[data-vh360-studio-entry-router]');
    if (!root) { return; }
    const key = 'vh360StudioMode';
    const params = new URLSearchParams(window.location.search);
    const explicit = params.get('studio_mode');
    function remember(mode) { try { window.localStorage.setItem(key, mode); } catch (e) {} }
    root.querySelectorAll('[data-studio-mode-choice]').forEach(function (link) {
        link.addEventListener('click', function () { remember(link.getAttribute('data-studio-mode-choice')); });
    });
    if (explicit === 'mobile' || explicit === 'desktop') { remember(explicit); return; }
    let stored = '';
    try { stored = window.localStorage.getItem(key) || ''; } catch (e) {}
    const mode = (stored === 'mobile' || stored === 'desktop') ? stored : (function () {
        const compact = window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
        const coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
        const touch = navigator.maxTouchPoints > 0 || 'ontouchstart' in window;
        return compact && coarse && touch ? 'mobile' : 'desktop';
    })();
    const url = mode === 'mobile' ? root.dataset.mobileUrl : root.dataset.desktopUrl;
    if (url) { window.location.replace(url); }
})();
