(function () {
  'use strict';

  var html = document.documentElement;
  var menuBtn = document.querySelector('.vh360-mobile-bottom-nav__menu-btn');
  var drawer = document.getElementById('vh360-mobile-user-drawer');

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

  initDrawer();
})();
