/**
 * User Menu Dropdown Functionality
 * 
 * @package Videohub360_Theme
 * @since 1.0.0
 */

(function() {
    'use strict';
    
    // Constants
    const MOBILE_BREAKPOINT = 768;
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserMenu);
    } else {
        initUserMenu();
    }
    
    function initUserMenu() {
        const menuContainer = document.querySelector('.vh360-user-menu');
        
        if (!menuContainer) {
            return;
        }
        
        const toggleButton = menuContainer.querySelector('.vh360-user-menu-toggle');
        const dropdown = menuContainer.querySelector('.vh360-user-dropdown');
        
        if (!toggleButton || !dropdown) {
            return;
        }
        
        let isOpen = false;
        
        /**
         * Toggle dropdown open/closed
         */
        function toggleDropdown() {
            isOpen = !isOpen;
            
            if (isOpen) {
                openDropdown();
            } else {
                closeDropdown();
            }
        }
        
        /**
         * Open dropdown
         */
        function openDropdown() {
            isOpen = true;
            highlightCurrentPage();
            dropdown.removeAttribute('hidden');
            dropdown.classList.add('active');
            toggleButton.setAttribute('aria-expanded', 'true');
            
            // Focus first menu item for accessibility
            setTimeout(() => {
                const firstMenuItem = dropdown.querySelector('.vh360-user-menu-item');
                if (firstMenuItem) {
                    firstMenuItem.focus();
                }
            }, 100);
            
            // Prevent body scroll on mobile
            if (window.innerWidth <= MOBILE_BREAKPOINT) {
                document.body.style.overflow = 'hidden';
            }
        }
        
        /**
         * Close dropdown
         */
        function closeDropdown() {
            isOpen = false;
            dropdown.classList.remove('active');
            toggleButton.setAttribute('aria-expanded', 'false');
            
            // Wait for animation to complete before hiding
            setTimeout(() => {
                if (!isOpen) {
                    dropdown.setAttribute('hidden', '');
                }
            }, 200);
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
        
        /**
         * Handle toggle button click
         */
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleDropdown();
        });
        
        /**
         * Close dropdown when clicking outside
         */
        document.addEventListener('click', function(e) {
            if (isOpen && !menuContainer.contains(e.target)) {
                closeDropdown();
            }
        });
        
        /**
         * Close dropdown on ESC key
         */
        document.addEventListener('keydown', function(e) {
            if (isOpen && e.key === 'Escape') {
                closeDropdown();
                toggleButton.focus();
            }
        });
        
        /**
         * Handle keyboard navigation within dropdown
         */
        dropdown.addEventListener('keydown', function(e) {
            if (!isOpen) {
                return;
            }
            
            const menuItems = Array.from(dropdown.querySelectorAll('.vh360-user-menu-item'));
            const currentIndex = menuItems.indexOf(document.activeElement);
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < menuItems.length - 1) {
                        menuItems[currentIndex + 1].focus();
                    } else {
                        menuItems[0].focus();
                    }
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        menuItems[currentIndex - 1].focus();
                    } else {
                        menuItems[menuItems.length - 1].focus();
                    }
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    menuItems[0].focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    menuItems[menuItems.length - 1].focus();
                    break;
                    
                case 'Tab':
                    // Allow tab but close dropdown after last item
                    if (!e.shiftKey && currentIndex === menuItems.length - 1) {
                        closeDropdown();
                    } else if (e.shiftKey && currentIndex === 0) {
                        closeDropdown();
                    }
                    break;
            }
        });
        
        /**
         * Close dropdown when menu item is clicked
         */
        const menuItems = dropdown.querySelectorAll('.vh360-user-menu-item');
        menuItems.forEach(function(item) {
            item.addEventListener('click', function() {
                // Small delay to allow navigation to start
                setTimeout(closeDropdown, 100);
            });
        });
        
        /**
         * Handle window resize
         */
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (isOpen && window.innerWidth > MOBILE_BREAKPOINT) {
                    // Restore body scroll when resizing from mobile to desktop
                    document.body.style.overflow = '';
                }
            }, 250);
        });
        
        /**
         * Helper function to normalize path by removing trailing slash
         */
        function normalizePath(path) {
            const normalizedPath = path || '';

            if (normalizedPath === '/') {
                return normalizedPath;
            }

            return normalizedPath.replace(/\/$/, '');
        }

        /**
         * Helper function to normalize search strings
         */
        function normalizeSearch(search) {
            return search || '';
        }

        /**
         * Helper function to normalize hash fragments
         */
        function normalizeHash(hash) {
            return hash || '';
        }

        /**
         * Check whether a menu URL should be eligible for active highlighting
         */
        function isHighlightableMenuUrl(url) {
            if (url.origin !== window.location.origin) {
                return false;
            }

            if (url.searchParams.has('vh360_logout') || url.searchParams.get('action') === 'logout') {
                return false;
            }

            return true;
        }

        /**
         * Highlight current page in menu
         */
        function highlightCurrentPage() {
            const currentUrl = new URL(window.location.href);
            const currentPath = normalizePath(currentUrl.pathname);
            const currentSearch = normalizeSearch(currentUrl.search);
            const currentHash = normalizeHash(currentUrl.hash);
            const allMenuItems = dropdown.querySelectorAll('.vh360-user-menu-item');

            allMenuItems.forEach(function(item) {
                item.classList.remove('current-page');

                const itemHref = item.getAttribute('href');
                if (!itemHref) {
                    return;
                }

                try {
                    const itemUrl = new URL(itemHref, window.location.origin);

                    if (!isHighlightableMenuUrl(itemUrl)) {
                        return;
                    }

                    const itemPath = normalizePath(itemUrl.pathname);
                    const itemSearch = normalizeSearch(itemUrl.search);
                    const itemHash = normalizeHash(itemUrl.hash);

                    const pathsMatch = currentPath === itemPath;
                    const searchesMatch = currentSearch === itemSearch;
                    const hashesMatch = currentHash === itemHash;

                    if (pathsMatch && searchesMatch && hashesMatch) {
                        item.classList.add('current-page');
                    }
                } catch (e) {
                    // Ignore URLs that cannot be parsed.
                }
            });
        }

        highlightCurrentPage();
        window.addEventListener('hashchange', highlightCurrentPage);
    }
})();
