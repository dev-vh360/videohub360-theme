/* VideoHub360 patched: debug flag + vh360 namespace (non-destructive) */
if (typeof window !== 'undefined') {
  window.vh360 = window.vh360 || {};
  window.__VH360_DEBUG = window.__VH360_DEBUG || false;
}

/**
 * VideoHub360 Video Quality Manager
 * 
 * Handles video quality selection, switching, and mirror controls
 * for VideoHub360 video players and streaming
 * 
 * @version 1.0.0
 * @author AuthenticPerception
 */

class VideoHub360_VideoQualityManager {
    constructor(options = {}) {
        this.options = {
            containerId: options.containerId || null,
            allowQualitySwitching: options.allowQualitySwitching !== false,
            allowMirrorControl: options.allowMirrorControl !== false,
            defaultQuality: options.defaultQuality || 'high',
            defaultMirror: options.defaultMirror || 'disabled',
            autoQualityEnabled: options.autoQualityEnabled !== false,
            showQualityBadge: options.showQualityBadge !== false,
            ...options
        };

        // State management
        this.currentQuality = this.options.defaultQuality;
        this.currentMirror = this.options.defaultMirror;
        this.availableQualities = {};
        this.mirrorSettings = {};
        this.isInitialized = false;
        this.unifiedSettingsMode = false;
        this.container = null;
        this.videoElement = null;
        
        // UI elements
        this.qualityMenu = null;
        this.mirrorMenu = null;
        this.qualityBadge = null;
        this.settingsButton = null;
        
        // Initialize
        this.init();
    }

    /**
     * Initialize the quality manager
     */
    init() {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Initializing...');
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Config available:', !!window.vh360QualityConfig);
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Unified settings config available:', !!window.vh360UnifiedSettingsConfig);
        
        // Check for unified settings early
        if (window.vh360UnifiedSettingsConfig && window.vh360UnifiedSettingsConfig.enabled) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Unified settings detected - will skip UI creation but provide API');
            window.vh360UnifiedSettings = true;
            this.unifiedSettingsMode = true;
        }
        
        // Load configuration from WordPress
        this.loadConfiguration();
        
        // Check for Agora streaming detection
        this.initializeForAgoraStreaming();
        
        // Find container and video element
        this.setupContainer();
        
        if (this.container) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Container found:', this.container.className || this.container.id);
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Video element found:', !!this.videoElement);
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Available qualities:', Object.keys(this.availableQualities));
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Quality switching allowed:', this.options.allowQualitySwitching);
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Mirror control allowed:', this.options.allowMirrorControl);
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Agora streaming detected:', this.enableLiveQualitySwitch);
            
            // Check if container is initially hidden
            const isHidden = this.container.classList.contains('videohub360-hide') || 
                           getComputedStyle(this.container).display === 'none';
            
            if (isHidden) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Container initially hidden, waiting for it to become visible...');
                this.waitForContainerVisible();
            } else {
                this.finalizeInitialization();
            }
            
        } else {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Container not found');
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Searched for containers:', [
                '.videohub360-custom-embed-container',
                '.vh360-livestream-player-wrap', 
                '.vh360-agora-player',
                '#videohub360-main-container',
                '.videohub360-player-container',
                '.vh360-player-wrapper'
            ]);
        }
    }

    /**
     * Initialize for Agora streaming
     */
    initializeForAgoraStreaming() {
        // Check if this is an Agora-enabled video
        const isAgoraVideo = document.querySelector('[data-source-type="agora"]') || 
                            document.querySelector('#vh360-agora-player') ||
                            document.querySelector('.vh360-agora-player') ||
                            document.querySelector('#vh360-agora-local-player') ||
                            window.vh360AgoraPlayer;
        
        if (isAgoraVideo) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Agora streaming detected');
            
            // Enable real-time quality switching for Agora
            this.enableLiveQualitySwitch = true;
            
            // Add indicator that quality controls affect live streaming
            setTimeout(() => this.addAgoraQualityIndicator(), 1000);
        }
    }

    /**
     * Add Agora quality indicator to UI
     */
    addAgoraQualityIndicator() {
        const qualityBadge = document.querySelector('.vh360-quality-badge');
        if (qualityBadge) {
            qualityBadge.title = 'Live Stream Quality - Changes apply immediately to your stream';
            qualityBadge.style.cursor = 'pointer';
            
            // Add visual indicator for live streaming
            const liveIndicator = document.createElement('span');
            liveIndicator.innerHTML = ' 🔴';
            liveIndicator.style.fontSize = '8px';
            liveIndicator.title = 'Live streaming quality control';
            qualityBadge.appendChild(liveIndicator);
        }
        
        const settingsButton = document.querySelector('.vh360-settings-button');
        if (settingsButton) {
            settingsButton.title = 'Video Quality Settings (Live Stream Control)';
        }
    }

    /**
     * Wait for container to become visible
     */
    waitForContainerVisible() {
        const checkVisibility = () => {
            if (!this.container) return;
            
            const isVisible = !this.container.classList.contains('videohub360-hide') && 
                            getComputedStyle(this.container).display !== 'none';
            
            if (isVisible) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Container is now visible, finalizing initialization...');
                this.finalizeInitialization();
            } else {
                // Check again after a short delay
                setTimeout(checkVisibility, 100);
            }
        };
        
        // Also listen for class changes using MutationObserver
        if (window.MutationObserver) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (!this.container.classList.contains('videohub360-hide')) {
                            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Container visibility changed, finalizing initialization...');
                            observer.disconnect();
                            this.finalizeInitialization();
                        }
                    }
                });
            });
            
            observer.observe(this.container, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
        
        // Start checking visibility
        setTimeout(checkVisibility, 100);
    }

    /**
     * Complete the initialization process
     */
    finalizeInitialization() {
        if (this.isInitialized) return; // Already initialized
        
        this.createUI();
        
        // Only bind UI events if not in unified settings mode
        if (!this.unifiedSettingsMode) {
            this.bindEvents();
        }
        
        this.isInitialized = true;
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Initialized successfully (unified mode:', !!this.unifiedSettingsMode, ')');
        
        // Dispatch ready event
        const event = new CustomEvent('vh360:qualityManagerReady', {
            detail: { manager: this }
        });
        document.dispatchEvent(event);
    }

    /**
     * Load configuration from WordPress localized data
     */
    loadConfiguration() {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Loading configuration...');
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: window.vh360QualityConfig:', window.vh360QualityConfig);
        
        if (window.vh360QualityConfig) {
            this.availableQualities = window.vh360QualityConfig.presets || {};
            this.mirrorSettings = window.vh360QualityConfig.mirrors || {};
            this.options.allowQualitySwitching = window.vh360QualityConfig.allow_quality_switching !== false;
            this.options.allowMirrorControl = window.vh360QualityConfig.allow_mirror_control !== false;
            this.options.showQualityBadge = window.vh360QualityConfig.show_quality_badge !== false;
            
            // Set defaults from config
            if (window.vh360QualityConfig.default_quality) {
                this.currentQuality = window.vh360QualityConfig.default_quality;
            }
            if (window.vh360QualityConfig.default_mirror) {
                this.currentMirror = window.vh360QualityConfig.default_mirror;
            }
            
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Configuration loaded from WordPress');
        } else {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Configuration not found, using defaults');
            this.setDefaultConfiguration();
        }
    }

    /**
     * Set default configuration if WordPress data not available
     */
    setDefaultConfiguration() {
        this.availableQualities = {
            'low': { label: '480p (Low)', bitrate: 1500000, resolution: '854x480', fps: 24 },
            'medium': { label: '720p (Medium)', bitrate: 3000000, resolution: '1280x720', fps: 30 },
            'high': { label: '1080p (High)', bitrate: 8000000, resolution: '1920x1080', fps: 30 },
            'ultra': { label: '1080p+ (Ultra)', bitrate: 12000000, resolution: '1920x1080', fps: 60 },
            '4k': { label: '4K (Professional)', bitrate: 25000000, resolution: '3840x2160', fps: 30 }
        };
        
        this.mirrorSettings = {
            'disabled': { label: 'Disabled' },
            'horizontal': { label: 'Horizontal Mirror' },
            'vertical': { label: 'Vertical Mirror' },
            'both': { label: 'Both Directions' }
        };
    }

    /**
     * Setup container and find video element
     */
    setupContainer() {
        if (this.options.containerId) {
            this.container = document.getElementById(this.options.containerId);
        } else {
            // Auto-detect VideoHub360 containers based on actual template structure
            this.container = document.querySelector(
                '.videohub360-custom-embed-container, ' +
                '.vh360-livestream-player-wrap, ' +
                '.vh360-agora-player, ' +
                '#videohub360-main-container, ' +
                '.videohub360-player-container, ' +
                '.vh360-player-wrapper'
            );
            
            // Fallback: create container around main video element if found
            if (!this.container) {
                const videoElement = document.querySelector('#videohub360-main-video, #vh360-livestream-video, #vh360-agora-player');
                if (videoElement && videoElement.parentElement) {
                    this.container = videoElement.parentElement;
                }
            }
        }
        
        if (this.container) {
            this.videoElement = this.container.querySelector('video, #videohub360-main-video, #vh360-livestream-video, .agora-video-player, iframe');
        }
    }

    /**
     * Create quality control UI (disabled for unified settings integration)
     */
    createUI() {
        if (!this.container) return;
        
        // Check if unified settings is enabled
        if (this.unifiedSettingsMode || this.options.disableUI || window.vh360UnifiedSettings) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: UI creation disabled (unified settings mode)');
            // Set flag to indicate unified settings is active
            window.vh360UnifiedSettings = true;
            // Just apply initial mirror setting without creating UI
            this.applyMirrorSetting(this.currentMirror);
            return;
        }
        
        // Original UI creation code (fallback)
        // Create main controls wrapper
        const controlsWrapper = this.createControlsWrapper();
        
        // Create quality badge
        if (this.options.showQualityBadge) {
            this.createQualityBadge(controlsWrapper);
        }
        
        // Create settings button and menus
        if (this.options.allowQualitySwitching || this.options.allowMirrorControl) {
            this.createSettingsButton(controlsWrapper);
            this.createMenus(controlsWrapper);
        }
        
        // Apply initial mirror setting
        this.applyMirrorSetting(this.currentMirror);
        
        // Update quality badge
        this.updateQualityBadge();
    }

    /**
     * Create controls wrapper element
     */
    createControlsWrapper() {
        let wrapper = this.container.querySelector('.vh360-quality-controls');
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'vh360-quality-controls';
            wrapper.innerHTML = `
                <style>
                .vh360-quality-controls {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    z-index: 1000;
                    display: flex;
                    gap: 8px;
                    align-items: center;
                    pointer-events: auto;
                }
                
                /* Ensure parent containers have position relative for proper positioning */
                .videohub360-custom-embed-container,
                .vh360-livestream-player-wrap,
                .vh360-agora-player,
                #videohub360-main-container {
                    position: relative !important;
                }
                
                .vh360-quality-badge {
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                    cursor: default;
                    white-space: nowrap;
                }
                
                .vh360-settings-button {
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    border: none;
                    padding: 8px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    transition: background-color 0.2s;
                    line-height: 1;
                }
                
                .vh360-settings-button:hover {
                    background: rgba(0, 0, 0, 0.9);
                }
                
                .vh360-quality-menu {
                    position: absolute;
                    top: 100%;
                    right: 0;
                    background: rgba(0, 0, 0, 0.9);
                    border-radius: 4px;
                    padding: 8px;
                    min-width: 180px;
                    display: none;
                    margin-top: 4px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
                }
                
                .vh360-quality-menu.show {
                    display: block;
                }
                
                .vh360-menu-section {
                    margin-bottom: 12px;
                }
                
                .vh360-menu-section:last-child {
                    margin-bottom: 0;
                }
                
                .vh360-menu-title {
                    color: #ccc;
                    font-size: 11px;
                    text-transform: uppercase;
                    margin-bottom: 6px;
                    font-weight: bold;
                }
                
                .vh360-menu-item {
                    display: block;
                    color: white;
                    text-decoration: none;
                    padding: 6px 8px;
                    border-radius: 3px;
                    font-size: 13px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                    border: none;
                    background: none;
                    width: 100%;
                    text-align: left;
                    white-space: nowrap;
                }
                
                .vh360-menu-item:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                .vh360-menu-item.active {
                    background: rgba(76, 175, 80, 0.7);
                }
                
                .vh360-video-mirrored-horizontal {
                    transform: scaleX(-1) !important;
                }
                
                .vh360-video-mirrored-vertical {
                    transform: scaleY(-1) !important;
                }
                
                .vh360-video-mirrored-both {
                    transform: scaleX(-1) scaleY(-1) !important;
                }
                
                /* Mobile responsiveness */
                @media (max-width: 480px) {
                    .vh360-quality-controls {
                        top: 5px;
                        right: 5px;
                        gap: 4px;
                    }
                    
                    .vh360-quality-badge {
                        font-size: 11px;
                        padding: 3px 6px;
                    }
                    
                    .vh360-settings-button {
                        padding: 6px;
                        font-size: 12px;
                    }
                    
                    .vh360-quality-menu {
                        min-width: 160px;
                        right: -20px;
                    }
                }
                </style>
            `;
            
            // Ensure container has position relative
            if (this.container && getComputedStyle(this.container).position === 'static') {
                this.container.style.position = 'relative';
            }
            
            this.container.appendChild(wrapper);
        }
        
        return wrapper;
    }

    /**
     * Create quality badge
     */
    createQualityBadge(wrapper) {
        this.qualityBadge = document.createElement('div');
        this.qualityBadge.className = 'vh360-quality-badge';
        wrapper.appendChild(this.qualityBadge);
    }

    /**
     * Create settings button
     */
    createSettingsButton(wrapper) {
        this.settingsButton = document.createElement('button');
        this.settingsButton.className = 'vh360-settings-button';
        this.settingsButton.innerHTML = '⚙️';
        this.settingsButton.title = 'Video Quality Settings';
        wrapper.appendChild(this.settingsButton);
    }

    /**
     * Create dropdown menus
     */
    createMenus(wrapper) {
        const menuContainer = document.createElement('div');
        menuContainer.style.position = 'relative';
        
        this.qualityMenu = document.createElement('div');
        this.qualityMenu.className = 'vh360-quality-menu';
        
        let menuHTML = '';
        
        // Quality selection section
        if (this.options.allowQualitySwitching && Object.keys(this.availableQualities).length > 0) {
            menuHTML += `
                <div class="vh360-menu-section">
                    <div class="vh360-menu-title">Video Quality</div>
                    ${Object.entries(this.availableQualities).map(([key, quality]) => `
                        <button class="vh360-menu-item quality-option ${key === this.currentQuality ? 'active' : ''}" 
                                data-quality="${key}">
                            ${quality.label}
                        </button>
                    `).join('')}
                </div>
            `;
        }
        
        // Mirror control section
        if (this.options.allowMirrorControl && Object.keys(this.mirrorSettings).length > 0) {
            menuHTML += `
                <div class="vh360-menu-section">
                    <div class="vh360-menu-title">Mirror Control</div>
                    ${Object.entries(this.mirrorSettings).map(([key, mirror]) => `
                        <button class="vh360-menu-item mirror-option ${key === this.currentMirror ? 'active' : ''}" 
                                data-mirror="${key}">
                            ${mirror.label}
                        </button>
                    `).join('')}
                </div>
            `;
        }
        
        this.qualityMenu.innerHTML = menuHTML;
        menuContainer.appendChild(this.qualityMenu);
        wrapper.appendChild(menuContainer);
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        if (this.settingsButton) {
            this.settingsButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleMenu();
            });
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.qualityMenu && !this.qualityMenu.contains(e.target) && 
                this.settingsButton && !this.settingsButton.contains(e.target)) {
                this.hideMenu();
            }
        });
        
        // Quality selection events
        if (this.qualityMenu) {
            this.qualityMenu.addEventListener('click', (e) => {
                if (e.target.classList.contains('quality-option')) {
                    const quality = e.target.dataset.quality;
                    this.setQuality(quality);
                    this.hideMenu();
                } else if (e.target.classList.contains('mirror-option')) {
                    const mirror = e.target.dataset.mirror;
                    this.setMirror(mirror);
                    this.hideMenu();
                }
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'q') {
                this.toggleMenu();
                e.preventDefault();
            }
        });
    }

    /**
     * Toggle settings menu
     */
    toggleMenu() {
        if (!this.qualityMenu) return;
        
        if (this.qualityMenu.classList.contains('show')) {
            this.hideMenu();
        } else {
            this.showMenu();
        }
    }

    /**
     * Show settings menu
     */
    showMenu() {
        if (this.qualityMenu) {
            this.qualityMenu.classList.add('show');
        }
    }

    /**
     * Hide settings menu
     */
    hideMenu() {
        if (this.qualityMenu) {
            this.qualityMenu.classList.remove('show');
        }
    }

    /**
     * Set video quality
     */
    setQuality(quality) {
        if (!this.availableQualities[quality]) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Invalid quality:', quality);
            return false;
        }
        
        const previousQuality = this.currentQuality;
        this.currentQuality = quality;
        
        // Update active state in menu
        this.updateMenuActiveStates();
        
        // Update quality badge
        this.updateQualityBadge();
        
        // Trigger quality change event
        this.triggerQualityChangeEvent(quality, previousQuality);
        
        // Save preference
        this.saveUserPreference('quality', quality);
        
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Quality changed to:', quality);
        return true;
    }

    /**
     * Set mirror setting
     */
    setMirror(mirror) {
        if (!this.mirrorSettings[mirror]) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Invalid mirror setting:', mirror);
            return false;
        }
        
        const previousMirror = this.currentMirror;
        this.currentMirror = mirror;
        
        // Apply mirror transformation
        this.applyMirrorSetting(mirror);
        
        // Update active state in menu
        this.updateMenuActiveStates();
        
        // Trigger mirror change event
        this.triggerMirrorChangeEvent(mirror, previousMirror);
        
        // Save preference
        this.saveUserPreference('mirror', mirror);
        
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Mirror setting changed to:', mirror);
        return true;
    }

    /**
     * Apply mirror transformation to video element
     */
    applyMirrorSetting(mirror) {
        if (!this.videoElement && !this.container) return;
        
        const target = this.videoElement || this.container.querySelector(
            'video, #videohub360-main-video, #vh360-livestream-video, .agora-video-player, [id*="player"], iframe'
        );
        if (!target) return;
        
        // Remove existing mirror classes
        target.classList.remove(
            'vh360-video-mirrored-horizontal',
            'vh360-video-mirrored-vertical', 
            'vh360-video-mirrored-both'
        );
        
        // Apply new mirror class
        switch (mirror) {
            case 'horizontal':
                target.classList.add('vh360-video-mirrored-horizontal');
                break;
            case 'vertical':
                target.classList.add('vh360-video-mirrored-vertical');
                break;
            case 'both':
                target.classList.add('vh360-video-mirrored-both');
                break;
            default:
                // 'disabled' - no transformation needed
                break;
        }
    }

    /**
     * Update quality badge text
     */
    updateQualityBadge() {
        if (this.qualityBadge && this.availableQualities[this.currentQuality]) {
            this.qualityBadge.textContent = this.availableQualities[this.currentQuality].label;
        }
    }

    /**
     * Update active states in menu items
     */
    updateMenuActiveStates() {
        if (!this.qualityMenu) return;
        
        // Update quality options
        this.qualityMenu.querySelectorAll('.quality-option').forEach(item => {
            if (item.dataset.quality === this.currentQuality) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        // Update mirror options
        this.qualityMenu.querySelectorAll('.mirror-option').forEach(item => {
            if (item.dataset.mirror === this.currentMirror) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    /**
     * Trigger quality change event
     */
    triggerQualityChangeEvent(newQuality, previousQuality) {
        const event = new CustomEvent('vh360:qualityChanged', {
            detail: {
                quality: newQuality,
                previousQuality: previousQuality,
                qualityData: this.availableQualities[newQuality],
                manager: this
            }
        });
        
        if (this.container) {
            this.container.dispatchEvent(event);
        }
        document.dispatchEvent(event);
    }

    /**
     * Trigger mirror change event
     */
    triggerMirrorChangeEvent(newMirror, previousMirror) {
        const event = new CustomEvent('vh360:mirrorChanged', {
            detail: {
                mirror: newMirror,
                previousMirror: previousMirror,
                mirrorData: this.mirrorSettings[newMirror],
                manager: this
            }
        });
        
        if (this.container) {
            this.container.dispatchEvent(event);
        }
        document.dispatchEvent(event);
    }

    /**
     * Save user preference to localStorage
     */
    saveUserPreference(type, value) {
        try {
            const prefs = JSON.parse(localStorage.getItem('vh360_quality_prefs') || '{}');
            prefs[type] = value;
            localStorage.setItem('vh360_quality_prefs', JSON.stringify(prefs));
        } catch (error) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Failed to save preference:', error);
        }
    }

    /**
     * Load user preferences from localStorage
     */
    loadUserPreferences() {
        try {
            const prefs = JSON.parse(localStorage.getItem('vh360_quality_prefs') || '{}');
            
            if (prefs.quality && this.availableQualities[prefs.quality]) {
                this.currentQuality = prefs.quality;
            }
            
            if (prefs.mirror && this.mirrorSettings[prefs.mirror]) {
                this.currentMirror = prefs.mirror;
            }
        } catch (error) {
            if (window.__VH360_DEBUG) console.warn('VideoHub360 Quality Manager: Failed to load preferences:', error);
        }
    }

    /**
     * Get current quality information
     */
    getCurrentQuality() {
        return {
            key: this.currentQuality,
            label: this.availableQualities[this.currentQuality]?.label || this.currentQuality,
            data: this.availableQualities[this.currentQuality] || null
        };
    }

    /**
     * Get current mirror setting
     */
    getCurrentMirror() {
        return {
            key: this.currentMirror,
            label: this.mirrorSettings[this.currentMirror]?.label || this.currentMirror,
            data: this.mirrorSettings[this.currentMirror] || null
        };
    }

    /**
     * Get available quality options in array format for unified settings
     */
    getAvailableQualities() {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Getting available qualities from:', this.availableQualities);
        return Object.entries(this.availableQualities).map(([key, data]) => ({
            key: key,
            label: data.label || data.resolution || key,
            data: data
        }));
    }

    /**
     * Get available mirror options in array format for unified settings
     */
    getAvailableMirrorOptions() {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Getting available mirror options from:', this.mirrorSettings);
        return Object.entries(this.mirrorSettings).map(([key, data]) => ({
            key: key,
            label: data.label || key,
            data: data
        }));
    }

    /**
     * Destroy the quality manager
     */
    destroy() {
        if (this.container) {
            const controls = this.container.querySelector('.vh360-quality-controls');
            if (controls) {
                controls.remove();
            }
        }
        
        this.isInitialized = false;
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Destroyed');
    }
}

// Auto-initialize on DOM ready for VideoHub360 contexts
document.addEventListener('DOMContentLoaded', function() {
    if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: DOM ready, checking for video content...');
    
    const initializeManager = () => {
        // Only initialize if we're on a VideoHub360 page with video content
        const hasVideoContent = document.querySelector(
            '.videohub360-custom-embed-container, ' +
            '.vh360-livestream-player-wrap, ' +
            '.vh360-agora-player, ' +
            '#videohub360-main-container, ' +
            '#videohub360-main-video, ' +
            '#vh360-livestream-video, ' +
            '.videohub360-player-container, ' +
            '.vh360-player-wrapper'
        );
        
        if (hasVideoContent) {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Video content detected, initializing...');
            if (!window.vh360QualityManager) {
                window.vh360QualityManager = new VideoHub360_VideoQualityManager();
            }
            return true;
        } else {
            if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: No video content detected');
            return false;
        }
    };
    
    // Try immediate initialization
    if (!initializeManager()) {
        // If no content found, try again after a short delay (for dynamically loaded content)
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Retrying initialization after delay...');
        setTimeout(() => {
            if (!initializeManager()) {
                if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: No video content found after retry, skipping initialization');
            }
        }, 1000);
    }
});

// Manual initialization function for debugging
window.vh360InitQualityManager = function(options = {}) {
    if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Manual initialization requested');
    if (window.vh360QualityManager) {
        if (window.__VH360_DEBUG) console.log('VideoHub360 Quality Manager: Destroying existing instance');
        window.vh360QualityManager.destroy();
    }
    window.vh360QualityManager = new VideoHub360_VideoQualityManager(options);
    return window.vh360QualityManager;
};

// Debug function to check current state
window.vh360DebugQualityManager = function() {
    if (window.__VH360_DEBUG) console.log('=== VideoHub360 Quality Manager Debug ===');
    if (window.__VH360_DEBUG) console.log('Manager exists:', !!window.vh360QualityManager);
    if (window.__VH360_DEBUG) console.log('Config exists:', !!window.vh360QualityConfig);
    if (window.__VH360_DEBUG) console.log('Config data:', window.vh360QualityConfig);
    
    const containers = document.querySelectorAll(
        '.videohub360-custom-embed-container, ' +
        '.vh360-livestream-player-wrap, ' +
        '.vh360-agora-player, ' +
        '#videohub360-main-container, ' +
        '#videohub360-main-video, ' +
        '#vh360-livestream-video, ' +
        '.videohub360-player-container, ' +
        '.vh360-player-wrapper'
    );
    if (window.__VH360_DEBUG) console.log('Containers found:', containers.length, containers);
    
    const hiddenContainers = document.querySelectorAll('.videohub360-hide');
    if (window.__VH360_DEBUG) console.log('Hidden containers:', hiddenContainers.length, hiddenContainers);
    
    if (window.vh360QualityManager) {
        if (window.__VH360_DEBUG) console.log('Manager initialized:', window.vh360QualityManager.isInitialized);
        if (window.__VH360_DEBUG) console.log('Manager container:', window.vh360QualityManager.container);
        if (window.__VH360_DEBUG) console.log('Manager video element:', window.vh360QualityManager.videoElement);
        if (window.__VH360_DEBUG) console.log('Manager quality controls:', document.querySelectorAll('.vh360-quality-controls'));
    }
    
    return {
        managerExists: !!window.vh360QualityManager,
        configExists: !!window.vh360QualityConfig,
        containersFound: containers.length,
        containers: containers,
        hiddenContainers: hiddenContainers.length
    };
};

// Test function to create demo controls
window.vh360CreateTestControls = function() {
    const testContainer = document.createElement('div');
    testContainer.id = 'vh360-test-container';
    testContainer.style.cssText = `
        position: relative;
        width: 640px;
        height: 360px;
        background: #333;
        margin: 20px auto;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-family: Arial, sans-serif;
    `;
    testContainer.innerHTML = '<h3>VideoHub360 Quality Controls Demo<br><small>Settings should appear in top-right corner</small></h3>';
    
    document.body.appendChild(testContainer);
    
    // Initialize quality manager for this test container
    const manager = new VideoHub360_VideoQualityManager({
        containerId: 'vh360-test-container',
        allowQualitySwitching: true,
        allowMirrorControl: true,
        showQualityBadge: true
    });
    
    if (window.__VH360_DEBUG) console.log('Test container created with quality manager');
    return manager;
};

// Export for manual initialization
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VideoHub360_VideoQualityManager;
}

/* Namespacing compatibility: copy any existing window.* functions into window.vh360 (non-destructive) */
;(function(){
  window.vh360 = window.vh360 || {};
  var _names = ['MutationObserver', 'vh360AgoraPlayer', 'vh360CreateTestControls', 'vh360DebugQualityManager', 'vh360InitQualityManager', 'vh360QualityConfig', 'vh360QualityManager', 'vh360UnifiedSettings', 'vh360UnifiedSettingsConfig'];
  _names.forEach(function(n){ try{ if (window[n] && !window.vh360[n]) window.vh360[n] = window[n]; }catch(e){} });
})();
