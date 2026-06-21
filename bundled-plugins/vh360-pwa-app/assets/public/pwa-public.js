(function () {
  'use strict';

  // Safe access to localized settings
  var CFG = window.VH360PWA || {};
  var swUrl = CFG.swUrl || '/vh360-sw.js';
  var offlineUrl = CFG.offlineUrl || '/vh360-offline.html';
  var appName = CFG.appShortName || 'this app'; // Use PWA short_name with fallback

  // Banner config
  var showBanner = !!CFG.showInstallBanner;
  var bannerText = CFG.installBannerText || 'Install this app';
  // App-store friendly button labels (never imply a native prompt will appear unless it actually can).
  var labelInstall = 'Install';
  var labelHowTo = 'How to install';
  var dismissDays = parseInt(CFG.bannerDismissDays || 7, 10);

  // State
  var deferredPrompt = null;

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
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
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

  function storageKey() {
    return 'vh360_pwa_install_banner_dismissed_until';
  }

  function dismissedUntil() {
    try {
      return parseInt(localStorage.getItem(storageKey()) || '0', 10);
    } catch (e) {
      return 0;
    }
  }

  function setDismissed(days) {
    try {
      var ms = Date.now() + (days * 24 * 60 * 60 * 1000);
      localStorage.setItem(storageKey(), String(ms));
    } catch (e) {}
  }

  function ensureModal() {
    if (document.getElementById('vh360-pwa-modal')) return;

    var modal = document.createElement('div');
    modal.id = 'vh360-pwa-modal';
    modal.className = 'vh360-pwa-modal vh360-pwa-hidden';
    modal.innerHTML =
      '<div class="vh360-pwa-modal__backdrop" data-vh360-pwa-close="1"></div>' +
      '<div class="vh360-pwa-modal__panel" role="dialog" aria-modal="true" aria-labelledby="vh360-pwa-modal-title">' +
        '<button class="vh360-pwa-modal__close" type="button" aria-label="Close" data-vh360-pwa-close="1">×</button>' +
        '<h3 id="vh360-pwa-modal-title" class="vh360-pwa-modal__title">Install ' + escapeHtml(appName) + '</h3>' +
        '<div class="vh360-pwa-modal__body" id="vh360-pwa-modal-body"></div>' +
      '</div>';
    document.body.appendChild(modal);
  }

  function setInstallButtonsPromptAvailable(isAvailable) {
    var label = isAvailable ? labelInstall : labelHowTo;
    // Update banner + shortcode buttons (anything using the data attribute)
    try {
      var btns = document.querySelectorAll('[data-vh360-pwa-install="1"]');
      for (var i = 0; i < btns.length; i++) {
        // Only adjust button-like elements
        btns[i].textContent = label;
      }
    } catch (e) {}
  }

  function openModal(html) {
    ensureModal();
    var modal = document.getElementById('vh360-pwa-modal');
    var body = document.getElementById('vh360-pwa-modal-body');
    if (!modal || !body) return;
    body.innerHTML = html;
    modal.classList.remove('vh360-pwa-hidden');
    document.documentElement.classList.add('vh360-pwa-modal-open');
  }

  function closeModal() {
    var modal = document.getElementById('vh360-pwa-modal');
    if (!modal) return;
    modal.classList.add('vh360-pwa-hidden');
    document.documentElement.classList.remove('vh360-pwa-modal-open');
  }

  function showChromeEdgeInstructions() {
    var html =
      '<p>Install the app from your browser menu.</p>' +
      '<p>Click the <strong>Install</strong> icon in the address bar, or open the <strong>⋮</strong> menu → <strong>Install ' + escapeHtml(appName) + '</strong>.</p>';
    openModal(html);
  }

  function showFirefoxInstructions() {
    // Firefox desktop does not offer a true PWA install flow.
    if (isFirefoxDesktop()) {
      var htmlDesktop =
        '<p>For the best app experience, install ' + escapeHtml(appName) + ' using <strong>Chrome</strong>, <strong>Edge</strong>, or <strong>Safari</strong>.</p>' +
        '<p>You can still bookmark this page in Firefox for quick access.</p>';
      openModal(htmlDesktop);
      return;
    }

    // Firefox on Android supports "Add to Home Screen".
    var html =
      '<p>Add ' + escapeHtml(appName) + ' to your home screen for quick access.</p>' +
      '<p>Open the browser menu → <strong>Add to Home Screen</strong>.</p>';
    openModal(html);
  }

  function showSafariDesktopInstructions() {
    var html =
      '<p>Install the app on your Mac for a full-screen, app-like experience.</p>' +
      '<p>From the Safari menu bar, choose <strong>File → Add to Dock</strong>.</p>';
    openModal(html);
  }

  function showIOSInstructions() {
    var html =
      '<p>Install on iPhone/iPad:</p>' +
      '<p>Tap <strong>Share</strong> → <strong>Add to Home Screen</strong> → <strong>Add</strong>.</p>';
    openModal(html);
  }

  function createBanner() {
    if (document.getElementById('vh360-pwa-install-banner')) return;

    var bar = document.createElement('div');
    bar.id = 'vh360-pwa-install-banner';
    bar.className = 'vh360-pwa-banner';
    bar.innerHTML =
      '<div class="vh360-pwa-banner__inner">' +
        '<div class="vh360-pwa-banner__text">' + escapeHtml(bannerText) + '</div>' +
        '<div class="vh360-pwa-banner__actions">' +
          '<button type="button" class="vh360-pwa-btn" data-vh360-pwa-install="1">' + escapeHtml(labelHowTo) + '</button>' +
          '<button type="button" class="vh360-pwa-banner__close" aria-label="Dismiss" data-vh360-pwa-dismiss="1">×</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(bar);
  }

  function maybeShowBanner() {
    if (!showBanner) return;
    if (isStandalone()) return;
    // Firefox desktop doesn't support true PWA installs; avoid showing the install banner.
    if (isFirefoxDesktop()) return;
    if (dismissedUntil() > Date.now()) return;

    createBanner();
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

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
    if (CFG.showRefreshButton) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vh360-pwa-refresh-button';
      btn.textContent = CFG.refreshLabel || 'Refresh';
      btn.addEventListener('click', function () { window.location.reload(); });
      document.body.appendChild(btn);
    }
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

  // Capture install prompt when available (Chrome/Edge/Android)
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    // Now that the prompt is available, update the button label to "Install".
    setInstallButtonsPromptAvailable(true);
  });

  // Click handling (delegated so it works even if banner is injected later)
  document.addEventListener('click', function (e) {
    var t = e.target;

    if (t && t.closest && t.closest('[data-vh360-pwa-close="1"]')) {
      e.preventDefault();
      closeModal();
      return;
    }

    if (t && t.closest && t.closest('[data-vh360-pwa-dismiss="1"]')) {
      e.preventDefault();
      setDismissed(dismissDays);
      var bar = document.getElementById('vh360-pwa-install-banner');
      if (bar && bar.parentNode) bar.parentNode.removeChild(bar);
      return;
    }

    if (t && t.closest && t.closest('[data-vh360-pwa-install="1"]')) {
      e.preventDefault();

      // If prompt is available, we can offer the true native install flow.
      if (deferredPrompt && deferredPrompt.prompt) {
        deferredPrompt.prompt();
        // Some browsers return a promise, some don't.
        try {
          deferredPrompt.userChoice.then(function () {
            deferredPrompt = null;
            setInstallButtonsPromptAvailable(false);
          }).catch(function () {
            deferredPrompt = null;
            setInstallButtonsPromptAvailable(false);
          });
        } catch (err) {
          deferredPrompt = null;
          setInstallButtonsPromptAvailable(false);
        }
        return;
      }

      // No native prompt available — show app-store-friendly instructions immediately.
      if (isIOS()) {
        showIOSInstructions();
      } else if (isSafariDesktop()) {
        showSafariDesktopInstructions();
      } else if (isFirefox()) {
        showFirefoxInstructions();
      } else {
        showChromeEdgeInstructions();
      }
      return;
    }
  });

  // Init
  document.addEventListener('DOMContentLoaded', function () {
    // One-time device tools (run in the visitor's browser):
    // - vh360_pwa_reset=1 (reset banner dismissal)
    // - vh360_pwa_tool=clear_caches | unregister_sw | reset_device
    var ranTool = false;
    try {
      var params = new URLSearchParams(window.location.search);

      // Simple banner reset.
      if (params.get('vh360_pwa_reset') === '1') {
        try { localStorage.removeItem(storageKey()); } catch (e1) {}
        ranTool = true;
      }

      var tool = params.get('vh360_pwa_tool');
      if (tool) {
        ranTool = true;

        var tasks = [];

        if (tool === 'clear_caches' || tool === 'reset_device') {
          if (window.caches && caches.keys) {
            tasks.push(
              caches.keys().then(function (keys) {
                return Promise.all(keys.map(function (k) { return caches.delete(k); }));
              })
            );
          }
        }

        if (tool === 'unregister_sw' || tool === 'reset_device') {
          if (navigator.serviceWorker && navigator.serviceWorker.getRegistrations) {
            tasks.push(
              navigator.serviceWorker.getRegistrations().then(function (regs) {
                return Promise.all(regs.map(function (r) { return r.unregister(); }));
              })
            );
          }
        }

        if (tool === 'reset_device') {
          // Reset banner dismissal as part of the full reset.
          try { localStorage.removeItem(storageKey()); } catch (e2) {}
        }

        Promise.all(tasks).then(function () {
          // Show a friendly confirmation.
          var msg = '';
          if (tool === 'clear_caches') {
            msg = '<p><strong>Done.</strong> PWA caches were cleared on this device.</p><p>You can close this tab now.</p>';
          } else if (tool === 'unregister_sw') {
            msg = '<p><strong>Done.</strong> The service worker was unregistered on this device.</p><p>You can close this tab now.</p>';
          } else {
            msg = '<p><strong>Done.</strong> This device was reset (service worker + caches + install banner).</p><p>You can close this tab now.</p>';
          }
          openModal(msg);
          // After clearing the banner dismissal, allow the banner to reappear.
          maybeShowBanner();
        }).catch(function () {
          openModal('<p><strong>Action completed.</strong> If you still see issues, try reloading the page once.</p><p>You can close this tab now.</p>');
        });
      }

      // Clean the URL so the action doesn't run again on refresh.
      if (ranTool && window.history && history.replaceState) {
        params.delete('vh360_pwa_reset');
        params.delete('vh360_pwa_tool');
        var newQs = params.toString();
        var newUrl = window.location.pathname + (newQs ? '?' + newQs : '') + window.location.hash;
        history.replaceState({}, document.title, newUrl);
      }
    } catch (e) {}
    updateStandaloneClass();
    // Modal container
    ensureModal();
    // Default label ("How to install") unless/until the browser provides a native prompt.
    setInstallButtonsPromptAvailable(!!(deferredPrompt && deferredPrompt.prompt));
    // Banner
    maybeShowBanner();
    // Ensure any newly injected banner button gets the correct label.
    setInstallButtonsPromptAvailable(!!(deferredPrompt && deferredPrompt.prompt));
    initRefreshControls();
  });

  window.addEventListener('load', function () {
    registerSW();
  });
})();
