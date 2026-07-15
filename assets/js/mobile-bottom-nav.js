(function () {
  'use strict';

  var nav = document.querySelector('.vh360-mobile-bottom-nav');

  function initDiagnostics() {
    var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
    var standaloneQuery = window.matchMedia ? window.matchMedia('(display-mode: standalone)') : null;
    var diagnosticsKey = 'vh360_mobile_nav_diagnostics';
    var snapshots = [];
    var safeAreaProbe = null;
    var viewportProbes = {};
    var scrollTimer = null;

    function isStandaloneMode() {
      return window.navigator.standalone === true || !!(standaloneQuery && standaloneQuery.matches);
    }

    function isEnabled() {
      return !!(nav && mobileQuery && mobileQuery.matches && isStandaloneMode());
    }

    function applyProbeBase(el) {
      el.style.position = 'absolute';
      el.style.left = '0';
      el.style.top = '0';
      el.style.width = '0';
      el.style.visibility = 'hidden';
      el.style.pointerEvents = 'none';
      el.style.opacity = '0';
      el.style.overflow = 'hidden';
    }

    function ensureProbes() {
      if (!document.body) return;

      if (!safeAreaProbe) {
        safeAreaProbe = document.createElement('div');
        applyProbeBase(safeAreaProbe);
        safeAreaProbe.style.height = '0';
        safeAreaProbe.style.paddingTop = 'env(safe-area-inset-top, 0px)';
        safeAreaProbe.style.paddingBottom = 'env(safe-area-inset-bottom, 0px)';
        safeAreaProbe.style.paddingLeft = 'env(safe-area-inset-left, 0px)';
        safeAreaProbe.style.paddingRight = 'env(safe-area-inset-right, 0px)';
        document.body.appendChild(safeAreaProbe);
      }

      ['vh', 'svh', 'dvh', 'lvh'].forEach(function (unit) {
        if (viewportProbes[unit]) return;
        if (window.CSS && window.CSS.supports && !window.CSS.supports('height', '100' + unit)) return;
        var probe = document.createElement('div');
        applyProbeBase(probe);
        probe.style.height = '100' + unit;
        document.body.appendChild(probe);
        viewportProbes[unit] = probe;
      });
    }

    function removeProbes() {
      if (safeAreaProbe && safeAreaProbe.parentNode) {
        safeAreaProbe.parentNode.removeChild(safeAreaProbe);
      }
      safeAreaProbe = null;
      Object.keys(viewportProbes).forEach(function (unit) {
        var probe = viewportProbes[unit];
        if (probe && probe.parentNode) {
          probe.parentNode.removeChild(probe);
        }
      });
      viewportProbes = {};
    }

    function rectData(rect) {
      if (!rect) return null;
      return {
        top: rect.top,
        right: rect.right,
        bottom: rect.bottom,
        left: rect.left,
        width: rect.width,
        height: rect.height
      };
    }

    function recordSnapshot(reason) {
      if (!isEnabled()) {
        removeProbes();
        return;
      }

      ensureProbes();

      var docEl = document.documentElement;
      var body = document.body;
      var scrollingElement = document.scrollingElement;
      var navRect = nav.getBoundingClientRect();
      var bodyRect = body ? body.getBoundingClientRect() : null;
      var navStyles = window.getComputedStyle(nav);
      var safeStyles = safeAreaProbe ? window.getComputedStyle(safeAreaProbe) : null;
      var vv = window.visualViewport || null;
      var viewportUnits = {};

      Object.keys(viewportProbes).forEach(function (unit) {
        viewportUnits['100' + unit] = window.getComputedStyle(viewportProbes[unit]).height;
      });

      var visualViewportBottom = vv ? vv.offsetTop + vv.height : null;
      var snapshot = {
        timestamp: new Date().toISOString(),
        reason: reason,
        navigatorStandalone: window.navigator.standalone === true,
        displayModeStandalone: !!(standaloneQuery && standaloneQuery.matches),
        screenOrientationType: screen.orientation ? screen.orientation.type : null,
        screenOrientationAngle: screen.orientation ? screen.orientation.angle : null,
        devicePixelRatio: window.devicePixelRatio,
        screenWidth: screen.width,
        screenHeight: screen.height,
        screenAvailWidth: screen.availWidth,
        screenAvailHeight: screen.availHeight,
        windowInnerWidth: window.innerWidth,
        windowInnerHeight: window.innerHeight,
        windowOuterWidth: window.outerWidth,
        windowOuterHeight: window.outerHeight,
        windowScrollX: window.scrollX,
        windowScrollY: window.scrollY,
        documentClientWidth: docEl.clientWidth,
        documentClientHeight: docEl.clientHeight,
        documentScrollHeight: docEl.scrollHeight,
        bodyRect: rectData(bodyRect),
        scrollingElementTagName: scrollingElement ? scrollingElement.tagName : null,
        scrollingElementScrollTop: scrollingElement ? scrollingElement.scrollTop : null,
        visualViewport: vv ? {
          width: vv.width,
          height: vv.height,
          offsetTop: vv.offsetTop,
          offsetLeft: vv.offsetLeft,
          pageTop: vv.pageTop,
          pageLeft: vv.pageLeft,
          scale: vv.scale
        } : null,
        navRect: rectData(navRect),
        navComputedStyles: {
          position: navStyles.position,
          top: navStyles.top,
          bottom: navStyles.bottom,
          height: navStyles.height,
          paddingTop: navStyles.paddingTop,
          paddingBottom: navStyles.paddingBottom,
          transform: navStyles.transform,
          overflow: navStyles.overflow,
          contain: navStyles.contain,
          willChange: navStyles.willChange
        },
        navBottomMinusWindowInnerHeight: navRect.bottom - window.innerHeight,
        navBottomMinusDocumentClientHeight: navRect.bottom - docEl.clientHeight,
        navBottomMinusVisualViewportBottom: visualViewportBottom === null ? null : navRect.bottom - visualViewportBottom,
        safeAreaInsets: safeStyles ? {
          top: safeStyles.paddingTop,
          bottom: safeStyles.paddingBottom,
          left: safeStyles.paddingLeft,
          right: safeStyles.paddingRight
        } : null,
        viewportUnitHeights: viewportUnits
      };

      snapshots.push(snapshot);
      if (snapshots.length > 50) {
        snapshots = snapshots.slice(snapshots.length - 50);
      }

      try {
        sessionStorage.setItem(diagnosticsKey, JSON.stringify(snapshots));
      } catch (e) {}

      if (window.console && console.log) {
        console.log('VH360 mobile nav diagnostics', snapshot);
      }
    }

    function recordAfterAnimationFrame(reason) {
      window.requestAnimationFrame(function () {
        recordSnapshot(reason);
      });
    }

    function recordOrientationSequence() {
      recordSnapshot('orientationchange immediate');
      [100, 300, 700, 1500].forEach(function (delay) {
        window.setTimeout(function () {
          recordSnapshot('orientationchange +' + delay + 'ms');
        }, delay);
      });
    }

    if (!isEnabled()) {
      removeProbes();
      return;
    }

    recordSnapshot('initial page load');

    window.addEventListener('orientationchange', recordOrientationSequence);
    window.addEventListener('resize', function () {
      recordAfterAnimationFrame('window resize');
    });

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', function () {
        recordAfterAnimationFrame('visualViewport resize');
      });
    }

    window.addEventListener('scroll', function () {
      window.clearTimeout(scrollTimer);
      scrollTimer = window.setTimeout(function () {
        recordSnapshot('scroll gesture end');
      }, 160);
    }, { passive: true });
  }

  initDiagnostics();

  var menuBtn = document.querySelector('.vh360-mobile-bottom-nav__menu-btn');
  var drawer = document.getElementById('vh360-mobile-user-drawer');

  if (!menuBtn || !drawer) {
    return;
  }

  var closeElements = drawer.querySelectorAll('[data-vh360-drawer-close]');
  var previouslyFocused = null;

  function openDrawer() {
    previouslyFocused = document.activeElement;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    menuBtn.setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('vh360-drawer-open');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    menuBtn.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('vh360-drawer-open');
    document.body.style.overflow = '';
    if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
      previouslyFocused.focus();
    }
  }

  menuBtn.addEventListener('click', function () {
    var isOpen = drawer.classList.contains('is-open');
    if (isOpen) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  closeElements.forEach(function (el) {
    el.addEventListener('click', function () {
      closeDrawer();
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });
})();
