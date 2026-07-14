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

  var visualViewportController = {
    viewportFrame: null,
    lastOffset: 0
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

  function getLowestLabelRect() {
    var labels = nav ? nav.querySelectorAll('.vh360-mobile-bottom-nav__label') : [];
    var lowest = null;

    labels.forEach(function (label) {
      var rect = label.getBoundingClientRect();
      if (!lowest || rect.bottom > lowest.bottom) {
        lowest = rect;
      }
    });

    return lowest;
  }

  function clearVisualViewportOffset(reason) {
    if (visualViewportController.viewportFrame !== null) {
      window.cancelAnimationFrame(visualViewportController.viewportFrame);
      visualViewportController.viewportFrame = null;
    }

    visualViewportController.lastOffset = 0;
    html.style.removeProperty('--vh360-mobile-nav-visual-offset');
    debugLog('visual offset cleared', { reason: reason });
  }

  function calculateVisualViewportOffset(viewport) {
    var layoutHeight = html.clientHeight;
    var visualBottom = viewport.offsetTop + viewport.height;
    var rawGap = Math.max(0, layoutHeight - visualBottom);
    var navHeight = nav ? nav.getBoundingClientRect().height : 0;
    var maxOffset = Math.max(0, navHeight || 0);
    var clampedGap = Math.min(rawGap, maxOffset);
    var devicePixelRatio = window.devicePixelRatio || 1;

    return Math.round(clampedGap * devicePixelRatio) / devicePixelRatio;
  }

  function updateVisualViewportOffset() {
    var viewport = window.visualViewport || null;
    var active = isStandaloneMode() && isBottomNavVisible();
    var scale = viewport && viewport.scale ? viewport.scale : 1;

    if (!active || !viewport || Math.abs(scale - 1) > 0.01) {
      clearVisualViewportOffset(!active ? 'inactive' : (!viewport ? 'missing visualViewport' : 'scaled viewport'));
      return;
    }

    var nextOffset = calculateVisualViewportOffset(viewport);
    if (Math.abs(nextOffset - visualViewportController.lastOffset) < 0.5) {
      debugVisualViewportOffset(nextOffset, viewport, 'unchanged');
      return;
    }

    visualViewportController.lastOffset = nextOffset;
    html.style.setProperty('--vh360-mobile-nav-visual-offset', nextOffset.toFixed(2) + 'px');
    debugVisualViewportOffset(nextOffset, viewport, 'applied');
  }

  function scheduleVisualViewportUpdate() {
    if (visualViewportController.viewportFrame !== null) {
      return;
    }

    visualViewportController.viewportFrame = window.requestAnimationFrame(function () {
      visualViewportController.viewportFrame = null;
      updateVisualViewportOffset();
    });
  }

  function debugVisualViewportOffset(offset, viewport, status) {
    if (!debugEnabled) {
      return;
    }

    var navRect = nav ? nav.getBoundingClientRect() : null;
    var innerRect = navItems ? navItems.getBoundingClientRect() : null;
    var labelRect = getLowestLabelRect();
    var visibleBottom = viewport ? viewport.offsetTop + viewport.height : null;

    debugLog('visual viewport offset ' + status, {
      documentElementClientHeight: html.clientHeight,
      visualViewportHeight: viewport ? viewport.height : null,
      visualViewportOffsetTop: viewport ? viewport.offsetTop : null,
      visualViewportScale: viewport ? viewport.scale : null,
      calculatedBottomGap: offset,
      appliedCssOffset: window.getComputedStyle(html).getPropertyValue('--vh360-mobile-nav-visual-offset').trim() || '0px',
      navRect: rectToObject(navRect),
      innerRect: rectToObject(innerRect),
      lowestLabelRect: rectToObject(labelRect),
      visibleVisualViewportBottom: visibleBottom,
      navBottomDelta: navRect && visibleBottom !== null ? Math.abs(navRect.bottom - visibleBottom) : null
    });
  }

  function initVisualViewportAnchoring() {
    if (!nav) {
      return;
    }

    scheduleVisualViewportUpdate();

    if (window.visualViewport) {
      window.visualViewport.addEventListener('scroll', scheduleVisualViewportUpdate, { passive: true });
      window.visualViewport.addEventListener('resize', scheduleVisualViewportUpdate, { passive: true });
    } else {
      window.addEventListener('scroll', scheduleVisualViewportUpdate, { passive: true });
    }

    if (screen.orientation && typeof screen.orientation.addEventListener === 'function') {
      screen.orientation.addEventListener('change', scheduleVisualViewportUpdate);
    } else {
      window.addEventListener('orientationchange', scheduleVisualViewportUpdate);
    }

    window.addEventListener('resize', scheduleVisualViewportUpdate);
    window.addEventListener('pageshow', scheduleVisualViewportUpdate);

    if (displayModeQuery) {
      if (typeof displayModeQuery.addEventListener === 'function') {
        displayModeQuery.addEventListener('change', scheduleVisualViewportUpdate);
      } else if (typeof displayModeQuery.addListener === 'function') {
        displayModeQuery.addListener(scheduleVisualViewportUpdate);
      }
    }
  }

  initDrawer();
  initVisualViewportAnchoring();
})();
