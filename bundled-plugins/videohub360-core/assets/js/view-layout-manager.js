/* VideoHub360 patched: debug flag + vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
  window.__VH360_DEBUG = window.__VH360_DEBUG || false;
}

/**
 * VideoHub360 View Layout Manager
 * 
 * Modular view management system for Speaker and Gallery View
 * Extracted from frontend.js for better maintainability and reduced complexity
 * 
 * @since 2.0.1
 */

class ViewLayoutManager {
    constructor(agoraMode = 'interactive', isAdmin = false) {
        this.agoraMode = agoraMode; // 'interactive' or 'broadcast'
        this.isAdmin = isAdmin;
        this.currentView = 'speaker'; // Default view mode
        this.pinnedParticipantUid = null;
        this.participantCount = 0;
        this.containerElement = null;
        this.remoteContainer = null;
        this.localContainer = null;
        this.viewSelector = null;
        this.viewDropdownToggle = null;
        this.viewDropdownMenu = null;
        this.boundViewDropdownOutsideHandler = null;
        this.boundViewDropdownKeyHandler = null;
        this.boundViewDropdownFullscreenHandler = null;
        this.boundFullscreenKeyHandler = null;
        this.isViewDropdownOpen = false;
        this.isTransitioning = false; // Guard against race conditions during view transitions
        this.transitionTimeout = null;
        this.fullscreenBtn = null;
        
        // Initialize the layout system
        this.init();
        
        // Add fullscreen event listeners
        this.initializeFullscreenHandlers();
    }
    
    init() {
        this.loadUserPreference();
        
        // Broadcast mode does not support Gallery View yet. Bind fullscreen controls only
        // and avoid showing a Speaker/Gallery selector that cannot control a layout.
        if (this.agoraMode === 'broadcast') {
            this.bindFullscreenEvents();
            return;
        }

        // Interactive mode: Speaker/Gallery dropdown plus Focus state support.
        this.createViewSelector();
        this.setupContainers();
    }
    
    // Utility function to determine if speaker switching can run safely during layout transitions.
    shouldAllowSpeakerSwitch() {
        return !this.isTransitioning;
    }

    // Backward-compatible alias for older callers. This no longer means live Agora
    // video DOM nodes may be moved.
    shouldAllowVideoMovement() {
        return this.shouldAllowSpeakerSwitch();
    }
    
    // Debug logging utility
    debugLog(message, ...args) {
        if (window.__VH360_DEBUG) {
            if (window.__VH360_DEBUG) console.log(`[VH360 Layout Manager] ${message}`, ...args);
        }
    }
    
    loadUserPreference() {
        try {
            const saved = localStorage.getItem('vh360-layout-view-preference');
            // Keep Phase 2 supported views; migrate only unsupported legacy layouts.
            if (saved === 'large-gallery') {
                if (window.__VH360_DEBUG) console.log('Migrating unsupported legacy preference to speaker view');
                this.currentView = 'speaker';
                this.saveUserPreference(); // Update localStorage with supported preference
            } else if (saved && this.isValidView(saved)) {
                this.currentView = saved;
            }
            // Add defensive cleanup - remove any old gallery classes from container
            this.cleanupLegacyGalleryClasses();
        } catch (e) {
            if (window.__VH360_DEBUG) console.log('Could not load layout view preference:', e);
        }
    }
    
    saveUserPreference() {
        try {
            localStorage.setItem('vh360-layout-view-preference', this.currentView);
        } catch (e) {
            if (window.__VH360_DEBUG) console.log('Could not save layout view preference:', e);
        }
    }
    
    cleanupLegacyGalleryClasses() {
        // Defensive cleanup to remove any old gallery classes from container
        if (this.containerElement) {
            this.containerElement.classList.remove('vh360-large-gallery-view');
            
            // Remove only empty legacy gallery grid wrappers. Never detach live participant/video nodes.
            const gridWrapper = this.containerElement.querySelector('.vh360-grid-wrapper');
            if (gridWrapper) {
                const liveNodes = gridWrapper.querySelectorAll('#vh360-agora-local-player, #vh360-agora-remote-players, [id^="player-"]');
                if (liveNodes.length === 0) {
                    gridWrapper.remove();
                } else {
                    gridWrapper.classList.remove('vh360-grid-wrapper');
                    gridWrapper.removeAttribute('style');
                }
            }
            
            // Remove any pagination controls
            const paginationControls = this.containerElement.querySelector('#vh360-pagination-controls, .vh360-pagination-controls');
            if (paginationControls) {
                paginationControls.remove();
            }
        }
    }
    
    createViewSelector() {
        // Remove any stale selector so this manager owns the dropdown and its listeners.
        const existingSelector = document.getElementById('vh360-view-selector');
        if (existingSelector) {
            existingSelector.remove();
        }
        const existingMenu = document.getElementById('vh360-view-dropdown-menu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        const controlsContainer = document.getElementById('vh360-agora-controls');
        if (!controlsContainer) return;
        
        this.createViewDropdown(controlsContainer);
        
        // Fullscreen button is now created in PHP template - just bind events
        this.bindFullscreenEvents();
    }
    
    isValidView(viewType) {
        return viewType === 'speaker' || viewType === 'gallery' || viewType === 'focus';
    }

    getViewLabel(viewType) {
        if (viewType === 'gallery') return 'Gallery View';
        if (viewType === 'focus') return 'Focus View';
        return 'Speaker View';
    }

    getCompactViewLabel() {
        return 'Views ▾';
    }

    getSelectableViewTypes() {
        return ['speaker', 'gallery', 'focus'];
    }

    createViewOption(viewType) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'vh360-view-option';
        button.dataset.viewType = viewType;
        button.setAttribute('role', 'menuitemradio');
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (button.disabled || button.getAttribute('aria-disabled') === 'true') {
                return;
            }
            this.closeViewDropdown();
            this.switchView(viewType);
        });
        return button;
    }

    updateViewSelectorState() {
        const selector = this.viewSelector || document.getElementById('vh360-view-selector');
        const optionRoot = this.viewDropdownMenu || selector;
        if (!optionRoot) return;

        const hasPinnedParticipant = !!this.pinnedParticipantUid;
        optionRoot.querySelectorAll('[data-view-type]').forEach((button) => {
            const viewType = button.dataset.viewType;
            const isActive = viewType === this.currentView;
            const isFocusWithoutPin = viewType === 'focus' && !hasPinnedParticipant;
            const label = this.getViewLabel(viewType);

            button.classList.toggle('is-active', isActive);
            button.classList.toggle('is-disabled', isFocusWithoutPin);
            button.disabled = isFocusWithoutPin;
            button.setAttribute('aria-checked', String(isActive));
            button.setAttribute('aria-current', isActive ? 'true' : 'false');
            button.setAttribute('aria-disabled', String(isFocusWithoutPin));
            button.title = isFocusWithoutPin ? 'Select Focus on a participant first' : label;
            button.textContent = `${isActive ? '✓ ' : ''}${label}`;
        });

        if (this.viewDropdownToggle) {
            this.viewDropdownToggle.textContent = this.getCompactViewLabel();
            this.viewDropdownToggle.setAttribute('aria-expanded', String(this.isViewDropdownOpen));
        }
    }


    getActiveFullscreenElement() {
        return document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement ||
            null;
    }

    getViewDropdownPortalParent() {
        const agoraPlayer = document.getElementById('vh360-agora-player');
        const fullscreenElement = this.getActiveFullscreenElement();
        if (agoraPlayer && fullscreenElement === agoraPlayer) {
            return agoraPlayer;
        }
        return document.body;
    }

    syncViewDropdownPortal() {
        if (!this.viewDropdownMenu) return;
        const parent = this.getViewDropdownPortalParent();
        if (parent && this.viewDropdownMenu.parentElement !== parent) {
            parent.appendChild(this.viewDropdownMenu);
        }
    }

    handleViewDropdownFullscreenChange() {
        this.closeViewDropdown();
        this.syncViewDropdownPortal();
    }

    addViewDropdownFullscreenListeners() {
        if (this.boundViewDropdownFullscreenHandler) return;
        this.boundViewDropdownFullscreenHandler = this.handleViewDropdownFullscreenChange.bind(this);
        document.addEventListener('fullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.addEventListener('webkitfullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.addEventListener('mozfullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.addEventListener('MSFullscreenChange', this.boundViewDropdownFullscreenHandler);
        document.addEventListener('msfullscreenchange', this.boundViewDropdownFullscreenHandler);
    }

    removeViewDropdownFullscreenListeners() {
        if (!this.boundViewDropdownFullscreenHandler) return;
        document.removeEventListener('fullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.removeEventListener('webkitfullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.removeEventListener('mozfullscreenchange', this.boundViewDropdownFullscreenHandler);
        document.removeEventListener('MSFullscreenChange', this.boundViewDropdownFullscreenHandler);
        document.removeEventListener('msfullscreenchange', this.boundViewDropdownFullscreenHandler);
        this.boundViewDropdownFullscreenHandler = null;
    }

    createViewDropdown(controlsContainer) {
        const wrapper = document.createElement('div');
        wrapper.id = 'vh360-view-selector';
        wrapper.className = 'vh360-view-selector vh360-view-dropdown';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'vh360-view-dropdown-toggle vh360-agora-views-btn';
        toggle.textContent = this.getCompactViewLabel();
        toggle.setAttribute('aria-haspopup', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', 'vh360-view-dropdown-menu');
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.setViewDropdownOpen(!this.isViewDropdownOpen);
        });

        const menu = document.createElement('div');
        menu.id = 'vh360-view-dropdown-menu';
        menu.className = 'vh360-view-dropdown-menu';
        menu.setAttribute('role', 'menu');
        menu.setAttribute('aria-label', 'Video layout views');
        menu.hidden = true;

        this.getSelectableViewTypes().forEach((viewType) => {
            menu.appendChild(this.createViewOption(viewType));
        });

        wrapper.appendChild(toggle);
        controlsContainer.appendChild(wrapper);

        this.viewSelector = wrapper;
        this.viewDropdownToggle = toggle;
        this.viewDropdownMenu = menu;
        this.syncViewDropdownPortal();
        this.boundViewDropdownOutsideHandler = (event) => {
            const clickedSelector = this.viewSelector && this.viewSelector.contains(event.target);
            const clickedMenu = this.viewDropdownMenu && this.viewDropdownMenu.contains(event.target);
            if (!clickedSelector && !clickedMenu) {
                this.closeViewDropdown();
            }
        };
        this.boundViewDropdownKeyHandler = (event) => {
            if (event.key === 'Escape' && this.isViewDropdownOpen) {
                this.closeViewDropdown();
                if (this.viewDropdownToggle) this.viewDropdownToggle.focus();
            }
        };
        document.addEventListener('click', this.boundViewDropdownOutsideHandler);
        document.addEventListener('keydown', this.boundViewDropdownKeyHandler);
        this.addViewDropdownFullscreenListeners();
        this.updateViewSelectorState();
    }

    setViewDropdownOpen(isOpen) {
        this.isViewDropdownOpen = !!isOpen;
        this.syncViewDropdownPortal();
        if (this.viewSelector) {
            this.viewSelector.classList.toggle('is-open', this.isViewDropdownOpen);
        }
        if (this.viewDropdownMenu) {
            this.viewDropdownMenu.hidden = !this.isViewDropdownOpen;
            if (this.isViewDropdownOpen) {
                this.positionViewDropdownMenu();
            }
        }
        if (this.viewDropdownToggle) {
            this.viewDropdownToggle.setAttribute('aria-expanded', String(this.isViewDropdownOpen));
        }
    }

    closeViewDropdown() {
        this.setViewDropdownOpen(false);
    }

    positionViewDropdownMenu() {
        if (!this.viewDropdownToggle || !this.viewDropdownMenu) return;
        this.syncViewDropdownPortal();
        const toggleRect = this.viewDropdownToggle.getBoundingClientRect();
        const menu = this.viewDropdownMenu;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        const margin = 8;

        menu.style.visibility = 'hidden';
        menu.hidden = false;
        const menuWidth = Math.min(menu.offsetWidth || 172, Math.max(160, viewportWidth - (margin * 2)));
        const menuHeight = menu.offsetHeight || 140;
        const left = Math.max(margin, Math.min(toggleRect.right - menuWidth, viewportWidth - menuWidth - margin));
        const hasRoomAbove = toggleRect.top >= menuHeight + margin;
        const top = hasRoomAbove
            ? Math.max(margin, toggleRect.top - menuHeight - margin)
            : Math.min(viewportHeight - menuHeight - margin, toggleRect.bottom + margin);

        menu.style.width = `${menuWidth}px`;
        menu.style.left = `${left}px`;
        menu.style.top = `${Math.max(margin, top)}px`;
        menu.style.visibility = '';
    }
    
    bindFullscreenEvents() {
        // Find the existing fullscreen button created in PHP
        const fullscreenBtn = document.getElementById('vh360-agora-fullscreen-btn');
        
        if (!fullscreenBtn) {
            if (window.__VH360_DEBUG) console.log('VideoHub360: No fullscreen button found to bind events');
            return;
        }
        
        // Check if the standard fullscreen API is supported. If not, we normally
        // hide the button. However, on iOS devices running in Agora broadcast
        // mode we still show the button because we will use the native video
        // fullscreen methods instead. To detect iOS we replicate the helper
        // logic from frontend.js here (cannot rely on vh360IsIOSDevice in this
        // scope).
        if (!this.isFullscreenSupported()) {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            const iOSIdentifiers = /iPad|iPhone|iPod/;
            const isiPadOS13 = ua.includes('Mac') && 'ontouchend' in document;
            const isiOS = iOSIdentifiers.test(ua) || isiPadOS13;
            // For iOS devices we keep the button visible regardless of broadcast
            // mode because native video fullscreen can still be used. Only hide
            // on non-iOS devices when the Fullscreen API is unsupported.
            if (!isiOS) {
                if (window.__VH360_DEBUG) console.log('VideoHub360: Fullscreen API not supported, hiding fullscreen button');
                fullscreenBtn.style.display = 'none';
                return;
            }
        }
        
        // Check if mobile events are already bound - don't override mobile functionality
        if (fullscreenBtn.dataset.mobileEventsbound === 'true') {
            if (window.__VH360_DEBUG) console.log('VideoHub360: Mobile fullscreen events already bound, skipping desktop binding');
            this.fullscreenBtn = fullscreenBtn;
            return;
        }
        
        // On mobile devices, don't bind desktop events - wait for mobile events to be bound
        if (window.innerWidth <= 768) {
            if (window.__VH360_DEBUG) console.log('VideoHub360: Mobile device detected, skipping desktop fullscreen binding');
            this.fullscreenBtn = fullscreenBtn;
            return;
        }
        
        if (window.__VH360_DEBUG) console.log('VideoHub360: Binding desktop fullscreen button events...');
        
        // Add click handler with error handling
        fullscreenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (window.__VH360_DEBUG) console.log('VideoHub360: Desktop fullscreen button clicked');
            this.toggleFullscreen();
        });
        
        // Mark as desktop events bound
        fullscreenBtn.dataset.desktopEventsbound = 'true';
        
        this.fullscreenBtn = fullscreenBtn;
        if (window.__VH360_DEBUG) console.log('VideoHub360: Desktop fullscreen button events bound successfully');
    }
    
    isFullscreenSupported() {
        return ViewLayoutManager.isFullscreenSupported();
    }
    
    // Static method for fullscreen support detection - can be used anywhere
    static isFullscreenSupported() {
        return !!(
            document.fullscreenEnabled ||
            document.webkitFullscreenEnabled ||
            document.mozFullScreenEnabled ||
            document.msFullscreenEnabled ||
            document.documentElement.requestFullscreen ||
            document.documentElement.webkitRequestFullscreen ||
            document.documentElement.mozRequestFullScreen ||
            document.documentElement.msRequestFullscreen
        );
    }
    
    toggleFullscreen() {
        // Check if fullscreen API is supported
        if (!this.isFullscreenSupported()) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360: Fullscreen API not supported');
            alert('Fullscreen is not supported in this browser.');
            return;
        }
        
        // Try to find the best element for fullscreen
        let targetElement = document.getElementById('vh360-agora-player');
        if (!targetElement) {
            // Fallback to local player
            targetElement = document.getElementById('vh360-agora-local-player');
        }
        if (!targetElement) {
            // Fallback to any agora container
            targetElement = document.querySelector('.vh360-agora-player, .vh360-multi-view-container');
        }
        
        if (!targetElement) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: No suitable element found for fullscreen');
            alert('Video player not found for fullscreen mode.');
            return;
        }
        
        if (window.__VH360_DEBUG) console.log('VideoHub360: Attempting to toggle fullscreen on element:', targetElement);
        if (window.__VH360_DEBUG) console.log('VideoHub360: Element details:', {
            id: targetElement.id,
            className: targetElement.className,
            offsetWidth: targetElement.offsetWidth,
            offsetHeight: targetElement.offsetHeight,
            style: targetElement.style.cssText,
            display: window.getComputedStyle(targetElement).display,
            visibility: window.getComputedStyle(targetElement).visibility
        });
        
        try {
            if (window.isInFullscreen()) {
                if (window.__VH360_DEBUG) console.log('VideoHub360: Currently in fullscreen, attempting to exit...');
                window.exitFullscreen().then(() => {
                    this.updateFullscreenButton(false);
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Exited fullscreen successfully');
                }).catch(err => {
                    if (window.__VH360_DEBUG) console.error('VideoHub360: Failed to exit fullscreen:', err);
                    // Try to update button state anyway
                    this.updateFullscreenButton(false);
                });
            } else {
                if (window.__VH360_DEBUG) console.log('VideoHub360: Not in fullscreen, attempting to enter...');
                
                // Add a temporary class to indicate fullscreen attempt
                targetElement.classList.add('vh360-entering-fullscreen');
                
                window.enterFullscreen(targetElement).then(() => {
                    targetElement.classList.remove('vh360-entering-fullscreen');
                    this.updateFullscreenButton(true);
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Entered fullscreen successfully');
                }).catch(err => {
                    targetElement.classList.remove('vh360-entering-fullscreen');
                    if (window.__VH360_DEBUG) console.error('VideoHub360: Failed to enter fullscreen:', err);
                    if (window.__VH360_DEBUG) console.error('VideoHub360: Error details:', {
                        name: err.name,
                        message: err.message,
                        stack: err.stack
                    });
                    
                    // Provide user feedback based on error type
                    let errorMessage = 'Fullscreen failed: ';
                    if (err.name === 'NotAllowedError') {
                        errorMessage += 'Browser blocked fullscreen. Try clicking the fullscreen button again.';
                    } else if (err.name === 'TypeError') {
                        errorMessage += 'Fullscreen not supported for this element.';
                    } else {
                        errorMessage += err.message || 'Unknown error occurred.';
                    }
                    
                    // Show user-friendly error
                    if (this.fullscreenBtn) {
                        this.fullscreenBtn.title = errorMessage;
                        setTimeout(() => {
                            this.fullscreenBtn.title = 'Toggle fullscreen (F key)';
                        }, 5000);
                    }
                    
                    // Also show console log for debugging
                    if (window.__VH360_DEBUG) console.log('VideoHub360: Fullscreen error shown to user:', errorMessage);
                });
            }
        } catch (error) {
            if (window.__VH360_DEBUG) console.error('VideoHub360: Unexpected error in toggleFullscreen:', error);
            alert('An unexpected error occurred while trying to toggle fullscreen.');
        }
    }
    
    updateFullscreenButton(isFullscreen) {
        if (!this.fullscreenBtn) return;
        
        const textSpan = this.fullscreenBtn.querySelector('.vh360-fullscreen-text');
        const svg = this.fullscreenBtn.querySelector('svg');
        
        if (isFullscreen) {
            // Exit fullscreen icon - standardized
            svg.innerHTML = '<path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>';
            if (textSpan) textSpan.textContent = 'Exit Fullscreen';
            this.fullscreenBtn.title = 'Exit fullscreen';
        } else {
            // Enter fullscreen icon - standardized
            svg.innerHTML = '<path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>';
            if (textSpan) textSpan.textContent = 'Fullscreen';
            this.fullscreenBtn.title = 'Toggle fullscreen';
        }
    }
    
    handleFullscreenKeydown(e) {
        const agoraPlayer = document.getElementById('vh360-agora-player');
        const targetTagName = e.target && e.target.tagName ? e.target.tagName : '';
        if (!agoraPlayer || targetTagName === 'INPUT' || targetTagName === 'TEXTAREA') {
            return;
        }

        // F key to toggle fullscreen.
        if (e.key === 'f' || e.key === 'F') {
            e.preventDefault();
            this.toggleFullscreen();
        }

        // Escape exits fullscreen in the browser; update the button state after it completes.
        if (e.key === 'Escape' && window.isInFullscreen()) {
            setTimeout(() => {
                this.updateFullscreenButton(false);
            }, 100);
        }
    }

    initializeFullscreenHandlers() {
        // Only add fullscreen change listeners once globally.
        if (!window.vh360FullscreenListenersAdded) {
            window.vh360FullscreenChangeHandler = window.vh360FullscreenChangeHandler || (() => {
                const isFullscreen = window.isInFullscreen();
                // Update desktop button if ViewLayoutManager instance exists.
                if (window.viewLayoutManager && window.viewLayoutManager.updateFullscreenButton) {
                    window.viewLayoutManager.updateFullscreenButton(isFullscreen);
                }
                // Update mobile button if it exists.
                if (typeof window.updateMobileFullscreenButton === 'function') {
                    window.updateMobileFullscreenButton(isFullscreen);
                }
            });

            document.addEventListener('fullscreenchange', window.vh360FullscreenChangeHandler);
            document.addEventListener('webkitfullscreenchange', window.vh360FullscreenChangeHandler);
            document.addEventListener('mozfullscreenchange', window.vh360FullscreenChangeHandler);
            document.addEventListener('msfullscreenchange', window.vh360FullscreenChangeHandler);
            
            window.vh360FullscreenListenersAdded = true;
        }
        
        // Add removable keyboard support for F key and Escape.
        if (!this.boundFullscreenKeyHandler) {
            this.boundFullscreenKeyHandler = this.handleFullscreenKeydown.bind(this);
            document.addEventListener('keydown', this.boundFullscreenKeyHandler);
        }
    }
    
    setupContainers() {
        // Look for the main Agora player container first
        this.containerElement = document.getElementById('vh360-agora-player') || 
                              document.getElementById('vh360-agora-interactive-container') || 
                              document.querySelector('.vh360-agora-interactive');
        this.remoteContainer = document.getElementById('vh360-agora-remote-players');
        this.localContainer = document.getElementById('vh360-agora-local-player');
        
        // Debug logging for mobile issues
        if (window.__VH360_DEBUG) console.log('ViewLayoutManager: Container setup', {
            containerElement: this.containerElement?.id || 'not found',
            remoteContainer: this.remoteContainer?.id || 'not found', 
            localContainer: this.localContainer?.id || 'not found',
            isMobile: window.innerWidth <= 768
        });
        
        if (!this.containerElement) {
            if (window.__VH360_DEBUG) console.warn('ViewLayoutManager: Main container not found, layout management will be disabled');
            return;
        }
        
        if (!this.remoteContainer) {
            if (window.__VH360_DEBUG) console.warn('ViewLayoutManager: Remote players container not found');
        }
        
        if (!this.localContainer) {
            if (window.__VH360_DEBUG) console.warn('ViewLayoutManager: Local player container not found');
        }
        
        // Add layout class to main container
        this.containerElement.classList.add('vh360-multi-view-container');
        
        // Add multi-view active class to remote container to prevent CSS conflicts
        if (this.remoteContainer) {
            this.remoteContainer.classList.add('vh360-multi-view-active');
        }
        
        // Apply initial layout to ensure proper setup
        this.applyLayout();
    }
    
    switchView(viewType) {
        if (!this.isValidView(viewType)) {
            if (window.__VH360_DEBUG) console.log(`View type ${viewType} not supported, defaulting to speaker`);
            viewType = 'speaker';
        }
        
        if (viewType === 'focus' && !this.pinnedParticipantUid) {
            this.debugLog('Ignoring Focus View selection without a pinned participant');
            this.updateViewSelectorState();
            return;
        }

        const oldView = this.currentView;
        
        // Prevent rapid switching during transitions
        if (this.isTransitioning) {
            this.debugLog(`Ignoring view switch to ${viewType} - transition in progress`);
            return;
        }
        
        // Set transition guard
        this.isTransitioning = true;
        this.debugLog(`Starting view transition from ${oldView} to ${viewType}`);
        
        this.currentView = viewType;
        
        // Save preference
        this.saveUserPreference();
        
        // Safe cleanup of previous view without removing live video nodes/tracks
        this.safeCleanupPreviousView();
        
        // Apply layout immediately - CSS handles transitions
        this.applyLayout();
        this.updateViewSelectorState();
        if (typeof window.vh360RefreshFeaturedParticipantTiles === 'function') {
            window.vh360RefreshFeaturedParticipantTiles();
        }
        
        // Short timeout to allow CSS transitions to start, then re-enable switching
        if (this.transitionTimeout) {
            clearTimeout(this.transitionTimeout);
            this.transitionTimeout = null;
        }
        this.transitionTimeout = setTimeout(() => {
            this.isTransitioning = false;
            this.transitionTimeout = null;
            this.debugLog(`Completed view transition to ${viewType}`);
        }, 100);
        
        if (window.__VH360_DEBUG) console.log(`Switched from ${oldView} to ${viewType} view`);
    }
    

    pinParticipant(uid) {
        if (!uid) return;
        this.pinnedParticipantUid = String(uid);
        this.debugLog('Focus set', this.pinnedParticipantUid);
        this.switchView('focus');
        if (typeof window.vh360RefreshFeaturedParticipantTiles === 'function') {
            window.vh360RefreshFeaturedParticipantTiles();
        }
    }

    unpinParticipant(options = {}) {
        const oldUid = this.pinnedParticipantUid;
        this.pinnedParticipantUid = null;
        this.debugLog(options.reason === 'left' ? 'Pinned participant left; focus cleared' : 'Focus cleared', oldUid);
        if (this.currentView === 'focus') {
            this.debugLog('Fallback to speaker');
            this.switchView('speaker');
        } else if (typeof window.vh360RefreshFeaturedParticipantTiles === 'function') {
            window.vh360RefreshFeaturedParticipantTiles();
        }
    }

    toggleParticipantFocus(uid) {
        const key = uid ? String(uid) : '';
        if (!key) return;
        if (this.pinnedParticipantUid === key && this.currentView === 'focus') {
            this.unpinParticipant();
        } else {
            this.pinParticipant(key);
        }
    }

    getPinnedParticipantUid() {
        return this.pinnedParticipantUid;
    }

    handleParticipantLeft(uid) {
        if (uid && this.pinnedParticipantUid === String(uid)) {
            this.unpinParticipant({ reason: 'left' });
        }
    }

    updateLayout(participants) {
        if (!participants || typeof participants !== 'object') {
            participants = {};
        }
        this.participantCount = Object.keys(participants).length;
        if (this.participantCount < 1 && this.currentView === 'focus') {
            this.unpinParticipant();
            return;
        }
        this.applyLayout();
    }
    
    // Phase One persistent participant tiles: never reparent live Agora nodes during layout cleanup.
    // Speaker View is controlled by classes on the stable participant tiles.
    safeCleanupPreviousView() {
        if (!this.containerElement) return;

        this.debugLog('Skipping video-node reparenting for persistent participant tiles');
        this.cleanupLegacyGalleryClasses();
    }
    
    applyLayout() {
        if (!this.containerElement) return;
        
        // Clean up previous view-specific elements first
        this.cleanupViewElements();
        
        // Reset any inline styles that might conflict with CSS
        this.resetInlineStyles();
        
        // Remove all previous layout classes
        this.containerElement.classList.remove(
            'vh360-speaker-view', 
            'vh360-gallery-view', 
            'vh360-focus-view',
            'vh360-large-gallery-view'
        );
        
        if (!this.isValidView(this.currentView)) {
            this.currentView = 'speaker';
        }

        if (this.currentView === 'gallery') {
            this.containerElement.classList.add('vh360-gallery-view');
            this.applyGalleryView();
        } else if (this.currentView === 'focus') {
            if (!this.pinnedParticipantUid) {
                this.debugLog('Focus view requested without pinned participant; falling back to speaker');
                this.currentView = 'speaker';
                this.saveUserPreference();
                this.containerElement.classList.add('vh360-speaker-view');
                this.applySpeakerView();
            } else {
                this.containerElement.classList.add('vh360-speaker-view', 'vh360-focus-view');
                this.applySpeakerView();
            }
        } else {
            this.containerElement.classList.add('vh360-speaker-view');
            this.applySpeakerView();
        }
        this.updateViewSelectorState();
        
        this.debugLog(`Applied ${this.currentView} layout`);
    }
    
    cleanupViewElements() {
        if (!this.containerElement) return;
        
        // Remove initial class from remote players if present
        if (this.remoteContainer) {
            this.remoteContainer.classList.remove('vh360-remote-players-initial');
        }
        
        // Remove legacy grid wrappers without moving live Agora video nodes.
        // Persistent participant tiles remain in their existing stage; only empty/legacy wrappers are removed.
        const gridWrapper = this.containerElement.querySelector('.vh360-grid-wrapper');
        if (gridWrapper) {
            const liveParticipants = gridWrapper.querySelectorAll('[id^="player-"], #vh360-agora-local-player');
            if (liveParticipants.length === 0) {
                gridWrapper.remove();
            } else {
                gridWrapper.classList.remove('vh360-grid-wrapper');
                gridWrapper.removeAttribute('style');
            }
        }
        
        // Remove pagination controls if present (legacy cleanup)
        const pagination = this.containerElement.querySelector('.vh360-pagination-controls');
        if (pagination) {
            pagination.remove();
        }
        
        this.debugLog('View elements cleanup completed');
    }
    
    // Clean up inline styles that may conflict with CSS during view transitions
    resetInlineStyles() {
        if (!this.remoteContainer) return;
        
        // Remove inline width/height styles from all remote player elements
        const remotePlayerElements = this.remoteContainer.querySelectorAll('[id^="player-"]');
        remotePlayerElements.forEach(element => {
            // Clear dimension-related inline styles but preserve other positioning/background styles
            element.style.width = '';
            element.style.height = '';
            element.style.maxWidth = '';
            element.style.maxHeight = '';
            element.style.minWidth = '';
            element.style.minHeight = '';
        });
        
        // Clear container max-width
        this.remoteContainer.style.maxWidth = '';
        
        this.debugLog('Inline styles reset for CSS consistency');
    }
    
    applySpeakerView() {
        // Speaker view is handled entirely by CSS - no JavaScript style manipulation needed
        // The .vh360-speaker-view class on the container will trigger all necessary styling
        this.debugLog('Applied speaker view - layout handled by CSS');
    }
    
    applyGalleryView() {
        // Gallery view is handled entirely by CSS on persistent tiles; never move Agora nodes.
        this.debugLog('Applied gallery view - layout handled by CSS');
    }

    // Cleanup method to be called when layout manager is destroyed
    destroy() {
        // Clear any pending transition timeout/state owned by this manager.
        if (this.transitionTimeout) {
            clearTimeout(this.transitionTimeout);
            this.transitionTimeout = null;
        }
        this.isTransitioning = false;

        // Remove dropdown document listeners to avoid duplicate handlers after reinitialization.
        if (this.boundViewDropdownOutsideHandler) {
            document.removeEventListener('click', this.boundViewDropdownOutsideHandler);
            this.boundViewDropdownOutsideHandler = null;
        }
        if (this.boundViewDropdownKeyHandler) {
            document.removeEventListener('keydown', this.boundViewDropdownKeyHandler);
            this.boundViewDropdownKeyHandler = null;
        }
        if (this.boundFullscreenKeyHandler) {
            document.removeEventListener('keydown', this.boundFullscreenKeyHandler);
            this.boundFullscreenKeyHandler = null;
        }
        this.removeViewDropdownFullscreenListeners();

        // Remove generated selector DOM and clear stale references.
        const selector = this.viewSelector || document.getElementById('vh360-view-selector');
        if (selector && selector.parentElement) {
            selector.remove();
        }
        if (this.viewDropdownMenu && this.viewDropdownMenu.parentElement) {
            this.viewDropdownMenu.remove();
        }
        this.viewSelector = null;
        this.viewDropdownToggle = null;
        this.viewDropdownMenu = null;
        this.isViewDropdownOpen = false;
        
        // Note: Fullscreen event listeners are now global and managed centrally
        // They should not be removed when ViewLayoutManager is destroyed
        // as they update both desktop and mobile buttons
        
        // Reset container classes
        if (this.containerElement) {
            this.containerElement.classList.remove(
                'vh360-speaker-view', 
                'vh360-gallery-view', 
                'vh360-large-gallery-view',
                'vh360-focus-view',
                'vh360-multi-view-container'
            );
        }
        
        // Remove multi-view active class from remote container
        if (this.remoteContainer) {
            this.remoteContainer.classList.remove('vh360-multi-view-active');
        }
        
        if (window.__VH360_DEBUG) console.log('ViewLayoutManager destroyed');
    }
}

// Export for module usage (if supported)
if (typeof window !== 'undefined') {
    window.ViewLayoutManager = ViewLayoutManager;
}

// Export for Node.js compatibility
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ViewLayoutManager;
}

/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['ViewLayoutManager', 'enterFullscreen', 'exitFullscreen', 'getComputedStyle', 'innerWidth', 'isInFullscreen', 'videoElementManager'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
