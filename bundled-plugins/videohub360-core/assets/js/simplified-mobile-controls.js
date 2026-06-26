/* VideoHub360 patched: debug flag + vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
  window.__VH360_DEBUG = window.__VH360_DEBUG || false;
}

/**
 * Simplified Mobile Controls for Agora VideoHub360
 * 
 * A clean, single-container implementation that replaces the complex 
 * two-container system while preserving all existing functionality.
 */
class SimplifiedMobileControls {
    constructor(config) {
        this.config = config;
        this.container = null;
        this.isInitialized = false;
        this.scrollObserver = null;
        this.resizeHandler = null;
        
        // Global functions that must be preserved for existing integration
        window.updateMobileControlsVisibility = this.updateVisibility.bind(this);
    }

    /**
     * Initialize mobile controls if on mobile device
     */
    init() {
        // Only initialize on mobile devices
        if (window.innerWidth > 768) {
            if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Not mobile device, skipping initialization');
            return false;
        }
        
        // Prevent multiple initializations
        if (this.isInitialized) {
            if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Already initialized, skipping...');
            return false;
        }
        
        if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Initializing...');
        
        this.container = document.getElementById('vh360-agora-controls');
        const playerContainer = document.getElementById('vh360-agora-player');
        
        if (!this.container || !playerContainer) {
            if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Missing required elements');
            return false;
        }
        
        // Apply simplified mobile styling
        this.container.classList.add('vh360-mobile-controls-simple');
        
        // Apply role-based visibility and button permissions
        this.updateVisibility();
        this.updateButtonPermissions();
        
        // Setup scroll indicators
        this.setupScrollIndicators();
        
        // Setup event listeners
        this.setupEventListeners();
        
        this.isInitialized = true;
        if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Initialized successfully');
        return true;
    }
    
    /**
     * Update controls visibility based on user role and mode
     */
    updateVisibility() {
        if (window.innerWidth > 768 || !this.isInitialized) {
            return;
        }
        
        if (!this.container) return;
        
        if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Updating visibility');
        if (window.__VH360_DEBUG) console.log('- agoraMode:', this.config.agoraMode);
        if (window.__VH360_DEBUG) console.log('- currentRole:', this.config.currentRole || window.currentRole);
        if (window.__VH360_DEBUG) console.log('- shouldShow:', this.shouldShowControlsForUser());
        if (window.__VH360_DEBUG) console.log('- streamStarted:', window.vh360StreamStarted);
        
        // Don't show controls until stream has actually started
        if (!window.vh360StreamStarted) {
            this.container.style.display = 'none';
            this.container.classList.remove('vh360-mobile-controls-visible');
            if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Stream not started yet, keeping controls hidden');
            return;
        }
        
        if (!this.shouldShowControlsForUser()) {
            this.container.style.display = 'none';
            this.container.classList.remove('vh360-mobile-controls-visible');
        } else {
            this.container.style.display = 'flex';
            this.container.classList.add('vh360-mobile-controls-visible');
            
            // Update scroll indicators after visibility change
            setTimeout(() => this.updateScrollIndicators(), 100);
        }
    }
    
    /**
     * Update individual button permissions
     */
    updateButtonPermissions() {
        
        const moderationBtn = document.getElementById('vh360-moderation-panel-btn');
        if (moderationBtn && !this.shouldShowModerationButton()) {
            moderationBtn.style.display = 'none';
        }
        
        const endStreamBtn = document.getElementById('vh360-agora-end-stream');
        if (endStreamBtn && !this.shouldShowEndStreamButton()) {
            endStreamBtn.style.display = 'none';
        }
    }
    
    /**
     * Setup scroll indicators for horizontal overflow
     */
    setupScrollIndicators() {
        if (!this.container) return;
        
        this.updateScrollIndicators();
        
        // Update scroll indicators when content changes
        this.scrollObserver = new MutationObserver(() => {
            setTimeout(() => this.updateScrollIndicators(), 50);
        });
        
        this.scrollObserver.observe(this.container, { 
            childList: true, 
            subtree: true, 
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    /**
     * Update scroll indicators based on content overflow
     */
    updateScrollIndicators() {
        if (!this.container) return;
        
        // Force reflow for accurate measurements
        this.container.offsetWidth;
        
        const hasHorizontalScroll = this.container.scrollWidth > this.container.clientWidth;
        if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Scroll check - hasScroll:', hasHorizontalScroll);
        
        if (hasHorizontalScroll) {
            this.container.classList.add('vh360-has-scroll');
        } else {
            this.container.classList.remove('vh360-has-scroll');
        }
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Window resize handler
        this.resizeHandler = () => {
            setTimeout(() => this.updateScrollIndicators(), 100);
        };
        window.addEventListener('resize', this.resizeHandler);
    }
    
    /**
     * Check if controls should be shown for current user
     * Preserves exact logic from original implementation
     */
    shouldShowControlsForUser() {
        const currentRole = this.config.currentRole || window.currentRole;
        const isHost = this.config.isHost;
        const isOriginalHost = this.config.isOriginalHost;
        const agoraMode = this.config.agoraMode;
        
        // In broadcast mode, only hosts should see controls
        if (agoraMode === 'broadcast') {
            return currentRole === 'host' || isHost || isOriginalHost;
        }
        
        // In interactive mode, both hosts and logged-in audience can see appropriate controls
        if (agoraMode === 'interactive') {
            return true; // Individual buttons will be filtered by role
        }
        
        return true;
    }
    
    /**
     * Check if moderation button should be shown
     * Preserves exact logic from original implementation
     */
    shouldShowModerationButton() {
        return this.config.canModerate || this.config.isOriginalHost;
    }
    
    /**
     * Check if end stream button should be shown
     * Preserves exact logic from original implementation
     */
    shouldShowEndStreamButton() {
        return this.config.isOriginalHost;
    }
    
    /**
     * Cleanup method
     */
    destroy() {
        if (this.scrollObserver) {
            this.scrollObserver.disconnect();
            this.scrollObserver = null;
        }
        
        if (this.resizeHandler) {
            window.removeEventListener('resize', this.resizeHandler);
            this.resizeHandler = null;
        }
        
        this.isInitialized = false;
        if (window.__VH360_DEBUG) console.log('SimplifiedMobileControls: Cleaned up');
    }
}

// Global instance
window.vh360SimplifiedMobileControls = null;

/**
 * Initialize simplified mobile controls
 * This replaces the old initializeMobileControls() function
 */
function initializeSimplifiedMobileControls(config) {
    if (window.vh360SimplifiedMobileControls) {
        window.vh360SimplifiedMobileControls.destroy();
    }
    
    window.vh360SimplifiedMobileControls = new SimplifiedMobileControls(config);
    return window.vh360SimplifiedMobileControls.init();
}

// Export for use in the Agora player runtime
window.initializeSimplifiedMobileControls = initializeSimplifiedMobileControls;

/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['addEventListener', 'currentRole', 'initializeSimplifiedMobileControls', 'innerWidth', 'removeEventListener', 'updateMobileControlsVisibility', 'vh360SimplifiedMobileControls', 'vh360StreamStarted'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
