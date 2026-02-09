/**
 * Header Navigation JavaScript
 *
 * Handles hamburger menu, search modal, and header interactions.
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize hamburger menu functionality
     */
    function initHamburgerMenu() {
        var toggle = document.querySelector('.hamburger-toggle');
        var menu = document.querySelector('.hamburger-menu');
        var backdrop = document.querySelector('.hamburger-menu__backdrop');
        var closeBtn = document.querySelector('.hamburger-close');
        
        if (!toggle || !menu) {
            return;
        }
        
        /**
         * Open hamburger menu
         */
        function openHamburgerMenu() {
            menu.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('hamburger-menu-open');
            
            // Focus first menu item
            var firstMenuItem = menu.querySelector('a');
            if (firstMenuItem) {
                setTimeout(function() {
                    firstMenuItem.focus();
                }, 300);
            }
        }
        
        /**
         * Close hamburger menu
         */
        function closeHamburgerMenu() {
            menu.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('hamburger-menu-open');
            toggle.focus();
        }
        
        /**
         * Toggle hamburger menu
         */
        function toggleHamburgerMenu() {
            var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeHamburgerMenu();
            } else {
                openHamburgerMenu();
            }
        }
        
        // Toggle button click
        toggle.addEventListener('click', toggleHamburgerMenu);
        
        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', closeHamburgerMenu);
        }
        
        // Backdrop click
        if (backdrop) {
            backdrop.addEventListener('click', closeHamburgerMenu);
        }
        
        // Store close functions for use in global ESC handler
        window.vh360HeaderNav = window.vh360HeaderNav || {};
        window.vh360HeaderNav.closeHamburgerMenu = closeHamburgerMenu;
    }
    
    /**
     * Initialize search modal functionality
     */
    function initSearchModal() {
        var searchToggle = document.querySelector('.header-search-toggle');
        var searchModal = document.querySelector('.header-search-modal');
        var searchBackdrop = document.querySelector('.header-search-modal__backdrop');
        var searchClose = document.querySelector('.header-search-close');
        var searchInput = document.querySelector('#header-search-input');
        
        if (!searchToggle || !searchModal) {
            return;
        }
        
        /**
         * Open search modal
         */
        function openSearchModal() {
            searchModal.setAttribute('aria-hidden', 'false');
            searchToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('search-modal-open');
            
            // Focus search input
            if (searchInput) {
                setTimeout(function() {
                    searchInput.focus();
                }, 100);
            }
        }
        
        /**
         * Close search modal
         */
        function closeSearchModal() {
            searchModal.setAttribute('aria-hidden', 'true');
            searchToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('search-modal-open');
            searchToggle.focus();
        }
        
        /**
         * Toggle search modal
         */
        function toggleSearchModal() {
            var isExpanded = searchToggle.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                closeSearchModal();
            } else {
                openSearchModal();
            }
        }
        
        // Toggle button click
        searchToggle.addEventListener('click', toggleSearchModal);
        
        // Close button click
        if (searchClose) {
            searchClose.addEventListener('click', closeSearchModal);
        }
        
        // Backdrop click
        if (searchBackdrop) {
            searchBackdrop.addEventListener('click', closeSearchModal);
        }
        
        // Store close function for use in global ESC handler
        window.vh360HeaderNav = window.vh360HeaderNav || {};
        window.vh360HeaderNav.closeSearchModal = closeSearchModal;
    }
    
    /**
     * Initialize global ESC key handler for all modals/menus
     */
    function initGlobalEscHandler() {
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Check hamburger menu
                var menu = document.querySelector('.hamburger-menu');
                if (menu && menu.getAttribute('aria-hidden') === 'false') {
                    if (window.vh360HeaderNav && window.vh360HeaderNav.closeHamburgerMenu) {
                        window.vh360HeaderNav.closeHamburgerMenu();
                    }
                    return;
                }
                
                // Check search modal
                var searchModal = document.querySelector('.header-search-modal');
                if (searchModal && searchModal.getAttribute('aria-hidden') === 'false') {
                    if (window.vh360HeaderNav && window.vh360HeaderNav.closeSearchModal) {
                        window.vh360HeaderNav.closeSearchModal();
                    }
                    return;
                }
            }
        });
    }
    
    /**
     * Initialize all header navigation functionality
     */
    function init() {
        initHamburgerMenu();
        initSearchModal();
        initGlobalEscHandler();
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
