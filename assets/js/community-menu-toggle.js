/**
 * Community Menu Toggle
 * 
 * Handles expanding/collapsing the Community Menu when in compact mode.
 * Persists the user's preference across page loads using localStorage.
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Storage key for the expanded state
    const STORAGE_KEY = 'vh360_community_menu_expanded';

    /**
     * Safely get value from localStorage
     * 
     * @param {string} key The storage key
     * @return {string|null} The stored value or null if not available
     */
    function getStorageItem(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            // localStorage may not be available in some browsers/privacy modes
            return null;
        }
    }

    /**
     * Safely set value in localStorage
     * 
     * @param {string} key The storage key
     * @param {string} value The value to store
     */
    function setStorageItem(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            // Silently fail if localStorage is not available
        }
    }

    /**
     * Initialize the toggle functionality
     */
    function init() {
        const $toggle = $('.vh360-community-menu__toggle');
        
        if (!$toggle.length) {
            return;
        }

        // Check if menu should be expanded on page load
        const isExpanded = getStorageItem(STORAGE_KEY) === '1';
        
        if (isExpanded) {
            expandMenu();
        }

        // Bind click handler
        $toggle.on('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }

    /**
     * Toggle the menu between expanded and collapsed
     */
    function toggleMenu() {
        const $body = $('body');
        const isCurrentlyExpanded = $body.hasClass('community-menu-expanded');
        
        if (isCurrentlyExpanded) {
            collapseMenu();
        } else {
            expandMenu();
        }
    }

    /**
     * Expand the menu
     */
    function expandMenu() {
        const $body = $('body');
        const $toggle = $('.vh360-community-menu__toggle');
        
        $body.addClass('community-menu-expanded');
        $toggle.attr('aria-expanded', 'true');
        $toggle.attr('aria-label', vh360CommunityMenuToggle.collapseLabel || 'Collapse community menu');
        
        // Save preference
        setStorageItem(STORAGE_KEY, '1');
    }

    /**
     * Collapse the menu
     */
    function collapseMenu() {
        const $body = $('body');
        const $toggle = $('.vh360-community-menu__toggle');
        
        $body.removeClass('community-menu-expanded');
        $toggle.attr('aria-expanded', 'false');
        $toggle.attr('aria-label', vh360CommunityMenuToggle.expandLabel || 'Expand community menu');
        
        // Save preference
        setStorageItem(STORAGE_KEY, '0');
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
