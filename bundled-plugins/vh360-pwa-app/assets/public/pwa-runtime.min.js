(function () {
  'use strict';

  // Safe access to localized settings
  var CFG = window.VH360PWA || {};
  var swUrl = CFG.swUrl || '/vh360-sw.js';
  var offlineUrl = CFG.offlineUrl || '/vh360-offline.html';
  var appName = CFG.appShortName || 'this app'; // Use PWA short_name with fallback


  function ua() {
    return navigator.userAgent || '';
  }

  function isFirefox() {
    return /Firefox\//.test(ua());
  }

  function isAndroid() {
    return /Android/i.test(ua());
  }

  // Safari on macOS (not Chrome/Edge/Opera; not iOS).
  function isSafariDesktop() {
    if (isIOS()) return false;
    var u = ua();
    if (!/Safari\//.test(u)) return false;
    // Exclude Chromium-based and Opera
    if (/Chrome\//.test(u) || /Chromium\//.test(u) || /Edg\//.test(u) || /OPR\//.test(u)) return false;
    return true;
  }

  function isFirefoxDesktop() {
    return isFirefox() && !isAndroid();
  }

  function isIOS() {
    var platform = navigator.platform || '';
    return ((/iPad|iPhone|iPod/.test(navigator.userAgent) || (platform === 'MacIntel' && navigator.maxTouchPoints > 1)) && !window.MSStream);
  }

  function isStandalone() {
    // iOS: navigator.standalone, others: display-mode
    return (window.navigator.standalone === true) ||
      (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
  }

  function updateStandaloneClass() {
    if (isStandalone()) {
      document.documentElement.classList.add('vh360-pwa-standalone');
    } else {
      document.documentElement.classList.remove('vh360-pwa-standalone');
    }
  }

  updateStandaloneClass();


  function registerSW() {
    if (!('serviceWorker' in navigator)) return;
    // If OneSignal is active, it must own the root scope SW. In that case, our
    // caching/offline logic is imported into the OneSignal worker instead.
    if (CFG && CFG.skipSWRegister) return;
    navigator.serviceWorker.register(swUrl).then(function (registration) {
      setupUpdatePrompt(registration);
    }).catch(function () {
      // silently ignore; install UX still works without SW prompt (browser may not allow install)
    });
  }



  function isIgnoredRefreshTarget(target) {
    if (!target || !target.closest) return false;
    return !!target.closest('input,textarea,select,button,a,video,iframe,[role="dialog"],.modal,.vh360-pwa-modal,.vh360-live-room-player,.videohub360-video-player,.vh360-agora,.agora_video_player,.agora-player,[data-vh360-interactive]');
  }

  function initRefreshControls() {
    if (!isStandalone()) return;
    if (!CFG.enablePullToRefresh) return;
    var indicator = document.createElement('div');
    indicator.className = 'vh360-pwa-ptr';
    indicator.textContent = 'Pull to refresh';
    document.body.appendChild(indicator);
    var startY = 0, pulling = false, ready = false, threshold = 86;
    document.addEventListener('touchstart', function (e) {
      if (!isStandalone() || window.scrollY > 0 || isIgnoredRefreshTarget(e.target)) return;
      startY = e.touches && e.touches[0] ? e.touches[0].clientY : 0;
      pulling = true; ready = false;
    }, { passive: true });
    document.addEventListener('touchmove', function (e) {
      if (!pulling || !e.touches || !e.touches[0]) return;
      var diff = e.touches[0].clientY - startY;
      if (diff <= 0 || window.scrollY > 0) return;
      if (e.cancelable) e.preventDefault();
      ready = diff > threshold;
      indicator.textContent = ready ? 'Release to refresh' : 'Pull to refresh';
      indicator.style.transform = 'translate(-50%, ' + Math.min(diff / 2, 70) + 'px)';
      indicator.classList.add('is-visible');
    }, { passive: false });
    document.addEventListener('touchend', function () {
      if (!pulling) return;
      pulling = false;
      if (ready) {
        indicator.textContent = 'Refreshing…';
        setTimeout(function () { window.location.reload(); }, 120);
      } else {
        indicator.classList.remove('is-visible');
        indicator.style.transform = '';
      }
    }, { passive: true });
  }

  var reloadingForUpdate = false;
  function setupUpdatePrompt(registration) {
    if (!registration) return;
    function showUpdatePrompt(worker) {
      if (!worker || document.getElementById('vh360-pwa-update')) return;
      var prompt = document.createElement('div');
      prompt.id = 'vh360-pwa-update';
      prompt.className = 'vh360-pwa-update';
      prompt.innerHTML = '<span>New version available</span><button type="button">Refresh</button>';
      prompt.querySelector('button').addEventListener('click', function () {
        reloadingForUpdate = true;
        worker.postMessage({ type: 'VH360_PWA_SKIP_WAITING' });
      });
      document.body.appendChild(prompt);
    }
    if (registration.waiting) showUpdatePrompt(registration.waiting);
    registration.addEventListener('updatefound', function () {
      var worker = registration.installing;
      if (!worker) return;
      worker.addEventListener('statechange', function () {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) showUpdatePrompt(worker);
      });
    });
    navigator.serviceWorker.addEventListener('controllerchange', function () {
      if (reloadingForUpdate) window.location.reload();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateStandaloneClass();
    initRefreshControls();
  });

  window.addEventListener('load', function () {
    registerSW();
  });
})();
