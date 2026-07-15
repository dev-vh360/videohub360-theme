(function () {
    'use strict';

var VH360StorageCompat = window.VH360Storage || (function(){
  var memory = {};
  function persistentAllowed(){ return !window.VH360ConsentExpected; }
  return {
    getPreference: function(key, def){ if(!persistentAllowed()) { return Object.prototype.hasOwnProperty.call(memory, key) ? memory[key] : def; } try { var value = window['localStorage'].getItem(key); return value === null ? def : value; } catch (e) { return def; } },
    setPreference: function(key, value){ memory[key] = value; if(!persistentAllowed()) { return; } try { window['localStorage'].setItem(key, value); } catch (e) {} },
    removePreference: function(key){ delete memory[key]; if(!persistentAllowed()) { return; } try { window['localStorage'].removeItem(key); } catch (e) {} },
    registerPreferenceKey: function(){}
  };
})();
    const root = document.querySelector('[data-vh360-studio-entry-router]');
    if (!root) { return; }
    const key = 'vh360StudioMode';
    const params = new URLSearchParams(window.location.search);
    const explicit = params.get('studio_mode');
    function remember(mode) { try { VH360StorageCompat.setPreference(key, mode); } catch (e) {} }
    root.querySelectorAll('[data-studio-mode-choice]').forEach(function (link) {
        link.addEventListener('click', function () { remember(link.getAttribute('data-studio-mode-choice')); });
    });
    if (explicit === 'mobile' || explicit === 'desktop') { remember(explicit); return; }
    let stored = '';
    try { stored = VH360StorageCompat.getPreference(key) || ''; } catch (e) {}
    const mode = (stored === 'mobile' || stored === 'desktop') ? stored : (function () {
        const compact = window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
        const coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
        const touch = navigator.maxTouchPoints > 0 || 'ontouchstart' in window;
        return compact && coarse && touch ? 'mobile' : 'desktop';
    })();
    const url = mode === 'mobile' ? root.dataset.mobileUrl : root.dataset.desktopUrl;
    if (url) { window.location.replace(url); }
})();
