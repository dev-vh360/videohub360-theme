(function () {
  'use strict';

  var html = document.documentElement;
  var nav = document.querySelector('.vh360-mobile-bottom-nav');
  var navItems = nav ? nav.querySelector('.vh360-mobile-bottom-nav__inner') : null;
  var menuBtn = document.querySelector('.vh360-mobile-bottom-nav__menu-btn');
  var drawer = document.getElementById('vh360-mobile-user-drawer');
  var displayModeQuery = window.matchMedia ? window.matchMedia('(display-mode: standalone)') : null;
  var debugEnabled = false;

  try {
    debugEnabled = new URLSearchParams(window.location.search).get('vh360_nav_debug') === '1';
  } catch (error) {
    debugEnabled = false;
  }

  function initDrawer() {
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
      html.classList.add('vh360-drawer-open');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      menuBtn.setAttribute('aria-expanded', 'false');
      html.classList.remove('vh360-drawer-open');
      document.body.style.overflow = '';
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
        previouslyFocused.focus();
      }
    }

    menuBtn.addEventListener('click', function () {
      if (drawer.classList.contains('is-open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    closeElements.forEach(function (el) {
      el.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
        closeDrawer();
      }
    });
  }

  var navStabilizer = {
    active: false,
    generation: 0,
    settleTimer: null,
    frameOne: null,
    frameTwo: null,
    startedAt: 0,
    lastMeasurement: null,
    lastNavHeight: 0
  };

  function debugLog(label, data) {
    if (debugEnabled && window.console && typeof window.console.log === 'function') {
      window.console.log('[vh360-mobile-nav] ' + label, data);
    }
  }

  function isStandaloneMode() {
    return html.classList.contains('vh360-pwa-standalone') ||
      window.navigator.standalone === true ||
      (displayModeQuery && displayModeQuery.matches === true);
  }

  function isBottomNavVisible() {
    if (!nav) {
      return false;
    }

    var style = window.getComputedStyle(nav);
    return style.display !== 'none' && style.visibility !== 'hidden' && nav.getClientRects().length > 0;
  }

  function readPixelCustomProperty(name, fallback) {
    var value = window.getComputedStyle(html).getPropertyValue(name).trim();
    var parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function cancelPendingSettlement() {
    if (navStabilizer.settleTimer) {
      window.clearTimeout(navStabilizer.settleTimer);
      navStabilizer.settleTimer = null;
    }
    if (navStabilizer.frameOne) {
      window.cancelAnimationFrame(navStabilizer.frameOne);
      navStabilizer.frameOne = null;
    }
    if (navStabilizer.frameTwo) {
      window.cancelAnimationFrame(navStabilizer.frameTwo);
      navStabilizer.frameTwo = null;
    }
  }

  function rectToObject(rect) {
    if (!rect) {
      return null;
    }
    return {
      top: rect.top,
      right: rect.right,
      bottom: rect.bottom,
      left: rect.left,
      width: rect.width,
      height: rect.height
    };
  }

  function measureNavigationHeight() {
    var viewport = window.visualViewport || null;
    var navRect = nav ? nav.getBoundingClientRect() : null;
    var itemsRect = navItems ? navItems.getBoundingClientRect() : null;
    var contentHeight = readPixelCustomProperty('--vh360-mobile-nav-content-height', 56);
    var cssSafeBottom = readPixelCustomProperty('--vh360-mobile-nav-safe-bottom', 0);
    var measuredSafeBottom = navRect && itemsRect ? Math.max(0, navRect.height - itemsRect.height) : 0;
    var safeBottom = Math.max(cssSafeBottom, measuredSafeBottom);
    var minimumHeight = contentHeight + safeBottom;
    var measuredHeight = navRect ? Math.max(navRect.height, minimumHeight) : minimumHeight;

    return {
      orientation: screen.orientation ? screen.orientation.type : window.orientation,
      windowWidth: window.innerWidth,
      windowHeight: window.innerHeight,
      documentWidth: html.clientWidth,
      documentHeight: html.clientHeight,
      visualViewportWidth: viewport ? viewport.width : null,
      visualViewportHeight: viewport ? viewport.height : null,
      visualViewportOffsetTop: viewport ? viewport.offsetTop : null,
      visualViewportOffsetLeft: viewport ? viewport.offsetLeft : null,
      navRect: rectToObject(navRect),
      itemsRect: rectToObject(itemsRect),
      labelRects: Array.prototype.map.call(nav ? nav.querySelectorAll('.vh360-mobile-bottom-nav__label') : [], function (label) {
        return rectToObject(label.getBoundingClientRect());
      }),
      safeBottom: safeBottom,
      contentHeight: contentHeight,
      measuredHeight: measuredHeight,
      appliedBodyPadding: window.getComputedStyle(document.body).paddingBottom
    };
  }

  function measurementsAreStable(first, second) {
    var keys = ['windowWidth', 'windowHeight', 'documentWidth', 'documentHeight', 'visualViewportWidth', 'visualViewportHeight', 'measuredHeight'];
    return keys.every(function (key) {
      if (first[key] === null || second[key] === null) {
        return true;
      }
      return Math.abs(first[key] - second[key]) <= 1;
    });
  }

  function applyMeasuredNavigationHeight(measurement, generation) {
    if (generation !== navStabilizer.generation || !navStabilizer.active) {
      return;
    }

    navStabilizer.lastNavHeight = measurement.measuredHeight;
    html.style.setProperty('--vh360-mobile-nav-measured-height', measurement.measuredHeight.toFixed(2) + 'px');
    debugLog('applied', Object.assign({ generation: generation }, measurement));
  }

  function resetNavigationLayer(generation) {
    if (generation !== navStabilizer.generation) {
      return;
    }

    html.classList.add('vh360-mobile-nav-layer-reset');
    if (nav) {
      nav.getBoundingClientRect();
    }
    window.requestAnimationFrame(function () {
      if (generation === navStabilizer.generation) {
        html.classList.remove('vh360-mobile-nav-layer-reset');
      }
    });
  }

  function finishSettlement(measurement, generation) {
    applyMeasuredNavigationHeight(measurement, generation);
    resetNavigationLayer(generation);
    if (generation === navStabilizer.generation) {
      html.classList.remove('vh360-mobile-nav-is-settling');
      navStabilizer.lastMeasurement = measurement;
    }
  }

  function settleNavigationGeometry(generation) {
    if (generation !== navStabilizer.generation || !navStabilizer.active) {
      return;
    }

    navStabilizer.frameOne = window.requestAnimationFrame(function () {
      var first = measureNavigationHeight();
      navStabilizer.frameTwo = window.requestAnimationFrame(function () {
        var second = measureNavigationHeight();
        var elapsed = window.performance.now() - navStabilizer.startedAt;

        debugLog('measured', { generation: generation, first: first, second: second });

        if (!measurementsAreStable(first, second) && elapsed < 1200) {
          navStabilizer.settleTimer = window.setTimeout(function () {
            settleNavigationGeometry(generation);
          }, elapsed < 250 ? 100 : 250);
          return;
        }

        finishSettlement(second, generation);
      });
    });
  }

  function scheduleNavigationSettlement() {
    navStabilizer.active = isStandaloneMode() && isBottomNavVisible();

    if (!navStabilizer.active) {
      cancelPendingSettlement();
      html.classList.remove('vh360-mobile-nav-is-settling', 'vh360-mobile-nav-layer-reset');
      html.style.removeProperty('--vh360-mobile-nav-measured-height');
      debugLog('inactive', { standalone: isStandaloneMode(), hasNav: !!nav, visible: isBottomNavVisible() });
      return;
    }

    navStabilizer.generation += 1;
    cancelPendingSettlement();
    navStabilizer.startedAt = window.performance.now();
    navStabilizer.lastMeasurement = null;
    html.classList.add('vh360-mobile-nav-is-settling');
    html.style.removeProperty('--vh360-mobile-nav-measured-height');

    debugLog('scheduled', { generation: navStabilizer.generation, standalone: isStandaloneMode() });
    navStabilizer.settleTimer = window.setTimeout(function () {
      settleNavigationGeometry(navStabilizer.generation);
    }, 0);
  }

  function initNavigationStabilizer() {
    if (!nav) {
      return;
    }

    scheduleNavigationSettlement();

    if (screen.orientation && typeof screen.orientation.addEventListener === 'function') {
      screen.orientation.addEventListener('change', scheduleNavigationSettlement);
    } else {
      window.addEventListener('orientationchange', scheduleNavigationSettlement);
    }

    window.addEventListener('resize', scheduleNavigationSettlement);
    window.addEventListener('pageshow', scheduleNavigationSettlement);

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', scheduleNavigationSettlement);
    }

    if (displayModeQuery) {
      if (typeof displayModeQuery.addEventListener === 'function') {
        displayModeQuery.addEventListener('change', scheduleNavigationSettlement);
      } else if (typeof displayModeQuery.addListener === 'function') {
        displayModeQuery.addListener(scheduleNavigationSettlement);
      }
    }
  }

  initDrawer();
  initNavigationStabilizer();
})();
