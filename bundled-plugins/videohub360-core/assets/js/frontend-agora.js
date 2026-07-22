// == Participant Moderation System ==
/**
 * Adds 3-dot moderation menu to participant video containers
 * Only visible to hosts and allows timeout/ban actions
 */
function addParticipantModerationMenu(playerElement, uid, displayName) {
    // Only add menu if user has moderation permissions
    window.vh360Log('VideoHub360: addParticipantModerationMenu called', {
        uid,
        displayName,
        canModerate: window.config?.canModerate,
        securityCanModerate: window.config?.security?.can_moderate
    });

    if (!window.config?.canModerate && !window.config?.security?.can_moderate) {
        window.vh360Log('VideoHub360: User does not have moderation permissions, menu not added');
        return;
    }

    // Ensure we have valid parameters
    if (!playerElement || !uid) {
        window.vh360Warn('VideoHub360: Invalid parameters for moderation menu');
        return;
    }

    // Ensure display name is valid
    const participantName = displayName || 'Participant';

    // Don't add menu if one already exists
    if (playerElement.querySelector('.vh360-participant-menu-container')) {
        return;
    }

    // Create menu button container
    const menuContainer = document.createElement('div');
    menuContainer.className = 'vh360-participant-menu-container';

    // Create 3-dot menu button
    const menuButton = document.createElement('button');
    menuButton.className = 'vh360-participant-menu-btn';
    menuButton.innerHTML = '⋯';
    menuButton.setAttribute('aria-label', `Moderate ${displayName}`);

    // Create dropdown menu
    const dropdownMenu = document.createElement('div');
    dropdownMenu.className = 'vh360-participant-dropdown';

    // Create menu options
    const menuOptions = [
        { id: 'timeout', icon: '⏱️', text: 'Timeout 5min', color: '#ff5722' },
        { id: 'ban', icon: '🚫', text: 'Ban', color: '#f44336' }
    ];

    menuOptions.forEach(option => {
        const menuItem = document.createElement('button');
        menuItem.className = 'vh360-participant-menu-option';
        menuItem.innerHTML = `
            <span class="menu-icon">${option.icon}</span>
            <span>${option.text}</span>
        `;

        // Click handler
        menuItem.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            hideParticipantMenu();
            isDropdownOpen = false;

            // Get current display name (try to get updated name from remoteUsers if available)
            let currentDisplayName = displayName;
            if (typeof window.getCurrentDisplayName === 'function') {
                currentDisplayName = window.getCurrentDisplayName(uid) || displayName;
            }

            showModerationConfirmation(uid, currentDisplayName, option.id);
        });

        dropdownMenu.appendChild(menuItem);
    });

    menuContainer.appendChild(menuButton);
    playerElement.appendChild(menuContainer);

    // Append dropdown to document body to avoid clipping
    document.body.appendChild(dropdownMenu);

    // State tracking for menu visibility
    let menuHideTimeout = null;
    let isDropdownOpen = false;

    // Show menu button on player hover
    playerElement.addEventListener('mouseenter', () => {
        menuContainer.style.opacity = '1';
        // Clear any pending hide timeout
        if (menuHideTimeout) {
            clearTimeout(menuHideTimeout);
            menuHideTimeout = null;
        }
    });

    // Hide menu button and dropdown when leaving player (with delay)
    playerElement.addEventListener('mouseleave', () => {
        menuContainer.style.opacity = '0';
        // Add delay before hiding dropdown to allow user to reach it
        if (isDropdownOpen) {
            menuHideTimeout = setTimeout(() => {
                hideParticipantMenu();
                isDropdownOpen = false;
                menuHideTimeout = null;
            }, 300); // 300ms delay to allow mouse movement to dropdown
        }
    });

    // Keep menu open when hovering over dropdown
    dropdownMenu.addEventListener('mouseenter', () => {
        // Cancel hide timeout when mouse enters dropdown
        if (menuHideTimeout) {
            clearTimeout(menuHideTimeout);
            menuHideTimeout = null;
        }
    });

    // Hide menu when leaving dropdown
    dropdownMenu.addEventListener('mouseleave', () => {
        hideParticipantMenu();
        isDropdownOpen = false;
    });

    // Menu button click handler
    menuButton.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Hide other open menus
        hideParticipantMenu();

        // Set dropdown as open
        isDropdownOpen = true;

        // Clear any pending hide timeout
        if (menuHideTimeout) {
            clearTimeout(menuHideTimeout);
            menuHideTimeout = null;
        }

        // Calculate position based on button location
        const buttonRect = menuButton.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const dropdownWidth = 140;
        const dropdownHeight = 80; // Approximate height for 2 items

        // Calculate initial position (below and to the right of button)
        let left = buttonRect.right - dropdownWidth;
        let top = buttonRect.bottom + 4;

        // Adjust if dropdown would go off-screen to the right
        if (left + dropdownWidth > viewportWidth) {
            left = buttonRect.left - dropdownWidth + 8;
        }

        // Adjust if dropdown would go off-screen to the left
        if (left < 8) {
            left = 8;
        }

        // Adjust if dropdown would go off-screen at the bottom
        if (top + dropdownHeight > viewportHeight) {
            top = buttonRect.top - dropdownHeight - 4;
        }

        // Apply calculated position
        dropdownMenu.style.left = left + 'px';
        dropdownMenu.style.top = top + 'px';
        dropdownMenu.style.display = 'block';

        // Add click outside listener to hide menu
        const hideOnClickOutside = (event) => {
            if (!menuButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                hideParticipantMenu();
                isDropdownOpen = false;
                document.removeEventListener('click', hideOnClickOutside);
            }
        };
        setTimeout(() => document.addEventListener('click', hideOnClickOutside), 0);
    });

    // Store reference for cleanup
    playerElement._moderationMenu = menuContainer;
    playerElement._moderationDropdown = dropdownMenu;
}

/**
 * Hides all open participant dropdown menus
 */
function hideParticipantMenu() {
    const openMenus = document.querySelectorAll('.vh360-participant-dropdown');
    openMenus.forEach(menu => {
        menu.style.display = 'none';
    });
}

/**
 * Shows confirmation dialog for moderation actions
 */
function showModerationConfirmation(uid, displayName, actionType) {
    const actionMessages = {
        timeout: `timeout "${displayName}" for 5 minutes?`,
        ban: `permanently ban "${displayName}" from the stream?`
    };

    const message = `Are you sure you want to ${actionMessages[actionType]}`;

    // Create custom confirmation modal
    const modal = document.createElement('div');
    modal.className = 'vh360-moderation-confirm-modal';

    const modalContent = document.createElement('div');
    modalContent.className = 'vh360-moderation-confirm-content';

    // Warning icon
    const iconDiv = document.createElement("div");
    iconDiv.className = 'vh360-moderation-modal-icon';
    iconDiv.textContent = "⚠️";
    modalContent.appendChild(iconDiv);

    // Title
    const title = document.createElement("h3");
    title.className = 'vh360-moderation-modal-title';
    title.textContent = "Confirm Moderation Action";
    modalContent.appendChild(title);

    // Message
    const msgP = document.createElement("p");
    msgP.className = 'vh360-moderation-modal-message';
    msgP.textContent = message;
    modalContent.appendChild(msgP);

    // Buttons container
    const btnWrap = document.createElement("div");
    btnWrap.className = 'vh360-moderation-modal-buttons';

    // Cancel button
    const cancelBtn = document.createElement("button");
    cancelBtn.id = "vh360-confirm-cancel";
    cancelBtn.textContent = "Cancel";
    btnWrap.appendChild(cancelBtn);

    // Action button
    const actionBtn = document.createElement("button");
    actionBtn.id = "vh360-confirm-action";
    actionBtn.className = `vh360-action-${actionType}`;
    actionBtn.textContent = actionType.charAt(0).toUpperCase() + actionType.slice(1);
    btnWrap.appendChild(actionBtn);

    // Append buttons container
    modalContent.appendChild(btnWrap);

    modal.appendChild(modalContent);

    // Check if we're in fullscreen mode and append to appropriate parent
    const isInFullscreen = !!(document.fullscreenElement ||
                             document.webkitFullscreenElement ||
                             document.mozFullScreenElement ||
                             document.msFullscreenElement);

    if (isInFullscreen) {
        const fullscreenElement = document.fullscreenElement ||
                               document.webkitFullscreenElement ||
                               document.mozFullScreenElement ||
                               document.msFullscreenElement;

        if (fullscreenElement && fullscreenElement.id === 'vh360-agora-player') {
            fullscreenElement.appendChild(modal);
            window.vh360Log('VideoHub360: Moderation confirmation modal appended to fullscreen element');
        } else {
            document.body.appendChild(modal);
        }
    } else {
        document.body.appendChild(modal);
    }

    // Event handlers
    const closeModal = () => {
        try {
            if (modal && modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        } catch (error) {
            window.vh360Error('Error closing moderation modal:', error);
            // Force modal removal if normal removal fails
            const existingModal = document.querySelector('.vh360-moderation-confirm-modal');
            if (existingModal && existingModal.parentNode) {
                existingModal.parentNode.removeChild(existingModal);
            }
        }
    };

    cancelBtn.addEventListener('click', closeModal);

    actionBtn.addEventListener('click', () => {
        try {
            executeParticipantModeration(uid, displayName, actionType);
            closeModal();
        } catch (error) {
            window.vh360Error('Error executing moderation:', error);
            closeModal(); // Still close modal even if there's an error
        }
    });

    // Close on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Close on Escape key
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}



// sendModerationCommand function moved inside initializeAgoraPlayer scope to access security variable

/**
 * Removes participant from UI
 */
function removeParticipantFromUI(uid) {
    if (window.vh360RemoveParticipantTile) {
        window.vh360Log('Agora: Removing participant via persistent registry cleanup:', uid);
        window.vh360RemoveParticipantTile(uid);
        if (window.vh360LayoutManager) {
            window.vh360LayoutManager.updateLayout(window.remoteUsers || {});
        }
        return;
    }

    const playerElement = document.getElementById(`player-${uid}`);
    if (playerElement) {
        window.vh360Log('Agora: Removing participant from UI:', uid);

        // Smooth removal animation
        playerElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        playerElement.style.opacity = '0';
        playerElement.style.transform = 'scale(0.8)';

        setTimeout(() => {
            if (playerElement.parentNode) {
                // Clean up video track binding before removing element
                if (window.videoElementManager) {
                    window.videoElementManager.unregisterTrackBinding(playerElement.id);
                }

                playerElement.parentNode.removeChild(playerElement);
                window.vh360Log('Agora: Participant element removed from DOM:', uid);
            }

            // Clean up from remoteUsers tracking
            if (window.remoteUsers && window.remoteUsers[uid]) {
                delete window.remoteUsers[uid];
                window.vh360Log('Agora: Participant removed from remoteUsers tracking:', uid);
            }

            // Update layout manager if available
            if (window.vh360LayoutManager) {
                window.vh360LayoutManager.updateLayout(window.remoteUsers || {});
                window.vh360Log('Agora: Layout manager updated after participant removal');
            }

        }, 300);
    } else {
        window.vh360Log('Agora: Player element not found for removal:', uid);
    }
}

/**
 * Shows moderation toast messages
 */
function showModerationToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.vh360-moderation-toast');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `vh360-moderation-toast toast-${type}`;

    toast.textContent = message;
    document.body.appendChild(toast);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }, 4000);
}

// == Agora Player Initialization ==
window.initializeAgoraPlayer = function(config) {
    // === Responsive Constants and Utilities ===
    const BREAKPOINTS = {
        MOBILE: 768,
        TABLET: 1024,
        DESKTOP: 1200
    };

    // Responsive detection utilities
    function getScreenSize() {
        const width = window.innerWidth;
        if (width < BREAKPOINTS.MOBILE) return 'mobile';
        if (width < BREAKPOINTS.TABLET) return 'tablet';
        return 'desktop';
    }

    function isMobile() {
        return window.innerWidth < BREAKPOINTS.MOBILE;
    }

    // === Multi-View Layout Manager ===
    // ViewLayoutManager has been moved to separate module for better maintainability
    // Import the new ViewLayoutManager from view-layout-manager.js

    // Check if ViewLayoutManager is available (loaded from separate module)
    if (typeof ViewLayoutManager === 'undefined') {
        window.vh360Error('ViewLayoutManager not found. Make sure view-layout-manager.js is loaded.');
        // Create a minimal fallback class to prevent errors
        window.ViewLayoutManager = class {
            constructor() {
                window.vh360Warn('Using fallback ViewLayoutManager. Some features may not work.');
                this.currentView = 'speaker';
                this.isTransitioning = false;
            }
            init() {}
            switchView() {}
            updateLayout() {}
            destroy() {}
        };
    }

    // Initialize layout manager
    let viewLayoutManager = null;


    function destroyViewLayoutManager() {
        const manager = viewLayoutManager || window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        cleanupThumbnailRailScrolling();
        if (manager && typeof manager.destroy === 'function') {
            try {
                manager.destroy();
            } catch (error) {
                window.vh360Warn('Agora: Error destroying layout manager:', error);
            }
        }
        viewLayoutManager = null;
        window.vh360LayoutManager = null;
        window.viewLayoutManager = null;
        if (window.vh360) {
            window.vh360.viewLayoutManager = null;
        }
    }

    // === Early Error Handler ===
    function showEarlyAgoraError(message) {
        window.vh360Error('Agora Error:', message);
        const playerContainer = document.getElementById('vh360-agora-player') || document.getElementById('vh360-agora-local-player');
        if (playerContainer) {
            playerContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 200px; color: #fff; font-size: 1.1em; background: #333; border-radius: 8px; text-align: center; padding: 20px;">' + message + '</div>';
        }
    }

    // === SDK and DOM Validation ===
    if (typeof AgoraRTC === 'undefined') {
        window.vh360Error('Agora: SDK not loaded');
        showEarlyAgoraError('Livestream service unavailable. Please refresh the page.');
        return null;
    }

    // Validate required DOM containers
    const requiredContainers = ['vh360-agora-player', 'vh360-agora-local-player'];
    const missingContainers = requiredContainers.filter(id => !document.getElementById(id));
    if (missingContainers.length > 0) {
        window.vh360Error('Agora: Missing required DOM containers:', missingContainers);
        showEarlyAgoraError('Video player not properly initialized. Please refresh the page.');
        return null;
    }

    // Validate configuration
    if (!config.appId || !config.channelName) {
        window.vh360Error('Agora: Missing required configuration', { appId: !!config.appId, channelName: !!config.channelName });
        showEarlyAgoraError('Livestream configuration incomplete. Please contact the administrator.');
        return null;
    }

    window.vh360Log('Agora: Initialization started with config:', {
        mode: config.mode,
        agoraMode: config.agoraMode,
        appId: config.appId ? 'present' : 'missing',
        channelName: config.channelName ? 'present' : 'missing'
    });

    let client = null;
    let localTracks = { videoTrack: null, audioTrack: null };

    let remoteUsers = {};
    // Session-only subscription state prevents duplicate subscribe calls and stale retries.
    const remoteSubscriptionStates = new Map();
    const remotePublicationGenerations = new Map();
    const REMOTE_SUBSCRIPTION_MAX_ATTEMPTS = 5;
    let remoteReconciliationTimer = null;
    let isAudioMuted = false;
    let isVideoMuted = false;
    let isPresenter = false;
    // Tracks whether a server-approved host token has been applied to the active Agora client.
    // Must be true before startPublishing() is allowed to proceed when tokens are required.
    // Set only after server returns role:'host' AND the token is applied via renewToken() or rejoin.
    let hasServerApprovedPublishToken = false;
    let currentUserUID = null; // Store the current user's Agora UID after joining
    let isAgoraSessionJoined = false;
    let isAgoraSessionReplacementInProgress = false;
    let latestAgoraTokenResponse = null;
    let agoraTokenRenewalInProgress = false;
    let agoraTokenRecoveryInProgress = false;
    let agoraTokenRenewalTimer = null;
    let participantJoinAudioContext = null;
    let participantJoinSoundUnlocked = false;
    const participantJoinSoundThrottle = new Map();

    // iOS immersive fullscreen state
    let isIOSImmersiveFullscreen = false;
    let iosImmersiveScrollY = 0;
    let iosImmersivePreviousActiveElement = null;
    let iosImmersiveOriginalParent = null;
    let iosImmersivePlaceholder = null;
    let iosImmersiveViewportAnimationFrame = null;
    let iosImmersiveViewportSyncTimers = [];
    let broadcastFullscreenControlsTimer = null;
    let broadcastFullscreenTapHandler = null;

    // Voice-activated video switching variables
    let activeSpeakerUid = null;
    let lastActiveSpeakerChange = 0;
    let activeSpeakerDebounceTimeout = null;
    let speakerCandidateUid = null;
    let speakerCandidateSince = 0;

    // Original host tracking for initial view preference
    let originalHostUID = null; // Store the original host's UID for initial view display

    // Simplified video element management system
    // Note: Agora tracks stay bound to elements even when moved in DOM,
    // so we don't need rebinding logic anymore
    const videoElementManager = {
        // Track active video element bindings for reference only
        elementTrackBindings: new Map(),

        // Register a video track binding
        registerTrackBinding(elementId, isLocal, remoteTrack = null) {
            this.elementTrackBindings.set(elementId, {
                isLocal,
                remoteTrack,
                timestamp: Date.now()
            });
        },

        // Clean up track binding
        unregisterTrackBinding(elementId) {
            this.elementTrackBindings.delete(elementId);
        },

        // Get current video element in main player
        getCurrentMainVideoElement() {
            const mainPlayer = document.getElementById("vh360-agora-local-player");
            return mainPlayer ? mainPlayer.querySelector('[id^="player-"]') : null;
        },

        // Check if element is local user's video
        isLocalUserElement(element) {
            return element && element.id === `player-${currentUserUID}`;
        }
    };

    // Expose video manager globally for other modules
    window.videoElementManager = videoElementManager;
    const ACTIVE_SPEAKER_SAMPLE_INTERVAL = 250;
    const ACTIVE_SPEAKER_ENTER_THRESHOLD = 0.58;
    const ACTIVE_SPEAKER_EXIT_THRESHOLD = 0.35;
    const ACTIVE_SPEAKER_ATTACK_MS = 400;
    const ACTIVE_SPEAKER_RELEASE_MS = 1400;
    const ACTIVE_SPEAKER_SWITCH_COOLDOWN_MS = 700;
    const ACTIVE_SPEAKER_DOMINANCE_MARGIN = 0.10;
    const ACTIVE_SPEAKER_EMA_ALPHA = 0.45;
    const AGORA_STREAM_HIGH = 0;
    const AGORA_STREAM_LOW = 1;
    const AGORA_FALLBACK_AUDIO_ONLY = 2;

    let volumeThreshold = ACTIVE_SPEAKER_ENTER_THRESHOLD; // normalized getVolumeLevel() threshold
    let switchingCooldown = ACTIVE_SPEAKER_SWITCH_COOLDOWN_MS;
    let isVolumenIndicationEnabled = false;
    let activeSpeakerSampleTimer = null;
    let activeSpeakerReleaseSince = 0;
    const activeSpeakerLevels = new Map();
    const activeSpeakerCandidates = new Map();
    const requestedRemoteStreamTypes = new Map();
    const actualRemoteStreamTypes = new Map();
    const remoteFallbackStates = new Map();
    let dualStreamConfigured = false;
    let autoplayFailureBound = false;
    let autoplayPreviousCallback = null;
    let autoplayWrapper = null;
    let diagnosticsTimer = null;
    const diagnosticsHistory = [];
    const participantDiagnostics = new Map();



    function isDebugModeEnabled() {
        return !!(window.__VH360_DEBUG || config.debug || (window.vh360Debug && window.vh360Debug.enabled));
    }

    function sanitizeStats(value, depth = 0) {
        if (depth > 3 || value == null) return value == null ? null : '[depth-limit]';
        if (typeof value !== 'object') return Number.isNaN(value) ? null : value;
        const output = Array.isArray(value) ? [] : {};
        Object.keys(value).forEach((key) => {
            if (/token|deviceId|authorization|secret|key/i.test(key)) return;
            output[key] = sanitizeStats(value[key], depth + 1);
        });
        return output;
    }

    function trimHistory(list, limit) {
        while (list.length > limit) list.shift();
    }

    function recordParticipantDiagnostic(uid, event) {
        if (!isDebugModeEnabled() || !uid) return;
        const key = normalizeParticipantUid(uid);
        const list = participantDiagnostics.get(key) || [];
        list.push(sanitizeStats({ timestamp: Date.now(), uid: key, ...(event || {}) }));
        trimHistory(list, 24);
        participantDiagnostics.set(key, list);
    }

    function recordClientDiagnostic(event) {
        if (!isDebugModeEnabled()) return;
        diagnosticsHistory.push(sanitizeStats({ timestamp: Date.now(), connectionState: client && client.connectionState, ...(event || {}) }));
        trimHistory(diagnosticsHistory, 24);
    }

    function exposeDiagnosticsSnapshot() {
        window.vh360 = window.vh360 || {};
        window.vh360.agoraDiagnostics = window.vh360.agoraDiagnostics || {};
        window.vh360.agoraDiagnostics.getSnapshot = function () {
            const participants = {};
            participantDiagnostics.forEach((history, uid) => { participants[uid] = history.slice(); });
            return { samples: diagnosticsHistory.slice(), participants };
        };
    }

    function collectAgoraDiagnostics() {
        if (!isDebugModeEnabled() || !client) return;
        const sample = { rtcStats: {}, localAudioStats: {}, localVideoStats: {}, remoteAudioStats: {}, remoteVideoStats: {} };
        try { if (typeof client.getRTCStats === 'function') sample.rtcStats = client.getRTCStats(); } catch (error) {}
        try { if (typeof client.getLocalAudioStats === 'function') sample.localAudioStats = client.getLocalAudioStats(); } catch (error) {}
        try { if (typeof client.getLocalVideoStats === 'function') sample.localVideoStats = client.getLocalVideoStats(); } catch (error) {}
        try { if (typeof client.getRemoteAudioStats === 'function') sample.remoteAudioStats = client.getRemoteAudioStats(); } catch (error) {}
        try { if (typeof client.getRemoteVideoStats === 'function') sample.remoteVideoStats = client.getRemoteVideoStats(); } catch (error) {}
        sample.requestedStreamTypes = Object.fromEntries(requestedRemoteStreamTypes);
        sample.actualStreamTypes = Object.fromEntries(actualRemoteStreamTypes);
        sample.fallbackStates = Object.fromEntries(remoteFallbackStates);
        recordClientDiagnostic(sample);
    }

    function startAgoraDiagnostics() {
        exposeDiagnosticsSnapshot();
        if (!isDebugModeEnabled() || diagnosticsTimer) return;
        diagnosticsTimer = setInterval(collectAgoraDiagnostics, 5000);
        collectAgoraDiagnostics();
    }

    function stopAgoraDiagnostics() {
        if (diagnosticsTimer) clearInterval(diagnosticsTimer);
        diagnosticsTimer = null;
    }

    async function configureInteractiveDualStream() {
        if (!client || config.agoraMode !== 'interactive' || dualStreamConfigured) return;
        try {
            if (typeof client.enableDualStream === 'function') await client.enableDualStream();
            if (typeof client.setLowStreamParameter === 'function') {
                try { await client.setLowStreamParameter({ width: 480, height: 270, framerate: 15, bitrate: 400 }); }
                catch (error) { window.vh360Warn('Agora: Custom low stream profile rejected; using SDK default low stream', error); }
            }
            if (typeof client.setRemoteDefaultVideoStreamType === 'function') await client.setRemoteDefaultVideoStreamType(AGORA_STREAM_LOW);
            dualStreamConfigured = true;
            window.vh360Log('Agora: Interactive dual stream configured');
        } catch (error) {
            window.vh360Warn('Agora: Dual stream setup unavailable; continuing with single stream', error);
        }
    }

    function requestRemoteStreamType(uid, streamType) {
        if (!client || config.agoraMode !== 'interactive' || !uid || typeof client.setRemoteVideoStreamType !== 'function') return;
        const key = normalizeParticipantUid(uid);
        const user = getCurrentRemoteUser(uid);
        if (!user || !user.hasVideo) return;
        if (requestedRemoteStreamTypes.get(key) === streamType) return;
        requestedRemoteStreamTypes.set(key, streamType);
        Promise.resolve(client.setRemoteVideoStreamType(uid, streamType)).catch((error) => {
            requestedRemoteStreamTypes.delete(key);
            window.vh360Warn('Agora: Failed to request remote stream type', { uid, streamType, error });
        });
    }

    function configureRemoteFallback(uid) {
        if (!client || typeof client.setStreamFallbackOption !== 'function' || !uid) return;
        Promise.resolve(client.setStreamFallbackOption(uid, AGORA_FALLBACK_AUDIO_ONLY)).catch((error) => {
            window.vh360Warn('Agora: Failed to configure stream fallback', { uid, error });
        });
    }

    function clearRemoteStreamSelectionState(uid = null) {
        if (uid == null) {
            requestedRemoteStreamTypes.clear();
            actualRemoteStreamTypes.clear();
            remoteFallbackStates.clear();
            return;
        }
        const key = normalizeParticipantUid(uid);
        requestedRemoteStreamTypes.delete(key);
        actualRemoteStreamTypes.delete(key);
        remoteFallbackStates.delete(key);
    }

    function requestFeaturedStreamTypes() {
        if (config.agoraMode !== 'interactive') return;
        const pinned = getPinnedParticipantUidFromLayout();
        const featured = pinned || activeSpeakerUid || getPreferredFallbackParticipant();
        participantRegistry.forEach((participant) => {
            if (!participant || participant.isLocal || !participant.videoTrack || participant.cameraOn === false) return;
            requestRemoteStreamType(participant.uid, String(participant.uid) === String(featured) ? AGORA_STREAM_HIGH : AGORA_STREAM_LOW);
        });
    }

    function bindAutoplayFailureRecovery() {
        if (autoplayFailureBound || !window.AgoraRTC) return;
        autoplayPreviousCallback = typeof window.AgoraRTC.onAutoplayFailed === 'function' ? window.AgoraRTC.onAutoplayFailed : null;
        autoplayWrapper = function () {
            if (autoplayPreviousCallback) autoplayPreviousCallback.apply(window.AgoraRTC, arguments);
            showAutoplayRecoveryPrompt();
            recordClientDiagnostic({ autoplayFailure: true });
        };
        window.AgoraRTC.onAutoplayFailed = autoplayWrapper;
        autoplayFailureBound = true;
    }

    function unbindAutoplayFailureRecovery() {
        if (!autoplayFailureBound || !window.AgoraRTC) return;
        if (window.AgoraRTC.onAutoplayFailed === autoplayWrapper) window.AgoraRTC.onAutoplayFailed = autoplayPreviousCallback;
        autoplayFailureBound = false;
        autoplayPreviousCallback = null;
        autoplayWrapper = null;
    }

    function getOrCreateAutoplayRecoveryPrompt() {
        let button = document.getElementById('vh360-agora-autoplay-recovery');
        if (!button) {
            const player = document.getElementById('vh360-agora-player');
            if (!player) return null;
            button = document.createElement('button');
            button.type = 'button';
            button.id = 'vh360-agora-autoplay-recovery';
            button.className = 'vh360-agora-autoplay-recovery';
            player.appendChild(button);
        }
        button.textContent = window.innerWidth <= 768 ? 'Tap to resume audio' : 'Resume livestream audio';
        if (!button.dataset.vh360AutoplayBound) {
            button.addEventListener('click', handleAutoplayRecoveryGesture);
            button.dataset.vh360AutoplayBound = 'true';
        }
        return button;
    }

    function showAutoplayRecoveryPrompt() {
        const prompt = getOrCreateAutoplayRecoveryPrompt();
        if (prompt) prompt.hidden = false;
    }

    async function handleAutoplayRecoveryGesture() {
        await resumeAgoraAudioContextForPlayback().catch(() => {});
        participantRegistry.forEach((participant) => {
            if (participant.audioTrack && shouldPlayRemoteAudio(participant.uid)) {
                try { participant.audioTrack.play(); } catch (error) { recordParticipantDiagnostic(participant.uid, { autoplayRetryFailed: true }); }
            }
            const playbackProblem = !participant.videoTrack || ['failed', 'waiting-for-frame', 'attaching'].includes(participant.videoPlaybackState || '') || participant.videoAutoplayBlocked;
            const isPlaying = participant.videoTrack && (participant.videoTrack.isPlaying || participant.videoTrack._isPlaying);
            if (participant.videoTrack && typeof participant.videoTrack.play === 'function' && participant.videoContainerElement && (!isPlaying || playbackProblem)) {
                try { participant.videoTrack.play(participant.videoContainerElement, participant.isLocal ? { mirror: false } : undefined); } catch (error) {}
            }
        });
        const prompt = document.getElementById('vh360-agora-autoplay-recovery');
        if (prompt) prompt.hidden = true;
        recordClientDiagnostic({ autoplayRecovered: true });
    }

    // Server-verified participant identity tracking
    const verifiedIdentityCache = new Map();
    const pendingIdentityRequests = new Map();

    // Moderation state tracking
    let isBeingModerated = false; // Flag to prevent UI updates after moderation action

    // Apply allowEveryoneIsHost logic for Interactive mode
    let isHost = config.isHost;
    let currentRole = config.role;
    if (config.studioControlled) {
        isHost = false;
        currentRole = 'audience';
        config.isHost = false;
        config.role = 'audience';
    }

    // Initialize stream started flag - controls should not show until stream begins
    window.vh360StreamStarted = false;

    window.vh360Log("VideoHub360: Initial role setup in initializeAgoraPlayer:");
    window.vh360Log("- Config role:", config.role);
    window.vh360Log("- Config isHost:", config.isHost);
    window.vh360Log("- Config agoraMode:", config.agoraMode);
    window.vh360Log("- Config allowEveryoneIsHost:", config.allowEveryoneIsHost);
    window.vh360Log("- Config isOriginalHost:", config.isOriginalHost);
    window.vh360Log('[VH360 Public Live] Viewer mode', {
        uid: config.uid,
        role: config.role,
        studioControlled: !!config.studioControlled
    });

    let isOriginalHost = config.studioControlled ? false : config.isOriginalHost;
    let canModerate = config.canModerate;
    let security = config.security || {};
    const isStudioHostViewer = !!(
        config.studioControlled &&
        config.viewerUserId &&
        config.studioHostUserId &&
        String(config.viewerUserId) === String(config.studioHostUserId)
    );
    const allowEveryoneHostForThisViewer = !!(
        config.allowEveryoneIsHost &&
        config.agoraMode === 'interactive' &&
        !isStudioHostViewer
    );
    let participantAudioMonitoringEnabled = true;

    if (allowEveryoneHostForThisViewer) {
        isHost = true;
        currentRole = 'host';
        window.vh360Log("VideoHub360: Everyone-is-host mode enabled for this viewer, setting role to host");
    } else if (isStudioHostViewer) {
        window.vh360Log("VideoHub360: Studio host viewer detected, suppressing everyone-host auto-publish on public page");
    }

    window.vh360Log("VideoHub360: Final role setup:");
    window.vh360Log("- currentRole:", currentRole);
    window.vh360Log("- isHost:", isHost);

    if (config.studioControlled) {
        config.displayName = config.viewerDisplayName || config.displayName || 'Guest';
        config.userId = config.viewerUserId || config.userId || 0;
        security.user_id = config.viewerUserId || security.user_id || 0;
        security.display_name = config.viewerDisplayName || security.display_name || config.displayName || 'Guest';
        security.avatar_url = config.viewerAvatarUrl || config.viewerAvatar || security.avatar_url || config.avatarUrl || '';
    }

    const vh360BeforeLeaveHandlers = new Set();
    const vh360BeforeEndHandlers = new Set();
    async function runAgoraLifecycleHandlers(handlers) {
        for (const handler of Array.from(handlers)) {
            await handler();
        }
    }
    window.vh360AgoraLifecycle = {
        registerBeforeLeave: (handler) => { if (typeof handler === 'function') { vh360BeforeLeaveHandlers.add(handler); } },
        registerBeforeEnd: (handler) => { if (typeof handler === 'function') { vh360BeforeEndHandlers.add(handler); } },
        unregisterBeforeLeave: (handler) => vh360BeforeLeaveHandlers.delete(handler),
        unregisterBeforeEnd: (handler) => vh360BeforeEndHandlers.delete(handler)
    };

    // === Helper Functions ===

    /**
     * Get current display name for a UID from remoteUsers
     */
    function getCurrentDisplayName(uid) {
        if (remoteUsers[uid] && remoteUsers[uid].displayName && !remoteUsers[uid].displayName.startsWith('User ')) {
            return remoteUsers[uid].displayName;
        }
        return null;
    }

    // Make getCurrentDisplayName available globally
    window.getCurrentDisplayName = getCurrentDisplayName;



    // Persistent participant tiles: Agora UID owns live track attachment; WordPress user ID stays as platform identity.
    const participantRegistry = new Map();
    window.vh360AgoraParticipants = participantRegistry;

    function dispatchAgoraParticipantEvent(type, participant) {
        window.dispatchEvent(new CustomEvent(type, { detail: { participant: participant, uid: participant && participant.uid } }));
    }

    let thumbnailRailStage = null;
    let thumbnailRailResizeTimeout = null;
    let thumbnailRailPointerState = null;
    let thumbnailRailTouchState = null;
    const thumbnailRailHandlers = {
        wheel: null,
        pointerDown: null,
        pointerMove: null,
        pointerUp: null,
        touchStart: null,
        touchMove: null,
        touchEnd: null,
        resize: null,
        fullscreenChange: null
    };

    function normalizeParticipantUid(uid) {
        return uid === null || typeof uid === 'undefined' ? null : String(uid);
    }

    function getParticipantStage() {
        const local = document.getElementById("vh360-agora-local-player");
        if (local) {
            local.classList.add('vh360-participant-stage', 'vh360-persistent-speaker-stage');
            ensureThumbnailRailScrolling(local);
            const remote = document.getElementById("vh360-agora-remote-players");
            if (remote) {
                remote.classList.remove('vh360-persistent-speaker-stage', 'vh360-participant-stage');
                remote.setAttribute('aria-hidden', 'true');
            }
            return local;
        }
        return document.getElementById("vh360-agora-remote-players");
    }


    function parseStagePixelValue(styles, propertyName, fallback = 0) {
        const rawValue = styles.getPropertyValue(propertyName).trim();
        const parsed = parseFloat(rawValue);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function getThumbnailRailAxis(stage) {
        return stage && stage.classList.contains('vh360-thumbnail-rail-vertical') ? 'vertical' : 'horizontal';
    }

    function ensureThumbnailRailViewport(stage) {
        if (!stage) return null;
        let viewport = stage.querySelector(':scope > .vh360-thumbnail-rail-viewport');
        if (!viewport) {
            viewport = document.createElement('div');
            viewport.className = 'vh360-thumbnail-rail-viewport';
            viewport.setAttribute('aria-hidden', 'true');
            stage.appendChild(viewport);
        }
        return viewport;
    }

    function updateThumbnailRailLayoutMode(stage) {
        if (!stage) return 'horizontal';
        const player = stage.closest('#vh360-agora-player') || document.getElementById('vh360-agora-player');
        const container = stage.closest('.vh360-multi-view-container');
        const isImmersive = !!(player && player.classList.contains('vh360-ios-immersive-fullscreen'));
        const isNativeFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || (window.isInFullscreen && window.isInFullscreen()));
        const viewport = window.visualViewport;
        const viewportWidth = isImmersive && viewport ? viewport.width : window.innerWidth;
        const viewportHeight = isImmersive && viewport ? viewport.height : window.innerHeight;
        const isLandscape = viewportWidth > viewportHeight;
        const isTouchDevice = !!((window.matchMedia && window.matchMedia('(pointer: coarse)').matches) || navigator.maxTouchPoints > 0);
        const isGallery = !!(container && container.classList.contains('vh360-gallery-view'));
        const isBroadcast = !!(player && player.classList.contains('vh360-broadcast-fullscreen'));
        const useVerticalRail = (isImmersive || isNativeFullscreen) && isLandscape && isTouchDevice && !isGallery && !isBroadcast;
        const previousAxis = stage.dataset.thumbnailRailAxis || 'horizontal';
        const axis = useVerticalRail ? 'vertical' : 'horizontal';
        const hasThumbnails = stage.querySelectorAll('.vh360-participant-tile[data-thumbnail-index]').length > 0;

        stage.classList.toggle('vh360-thumbnail-rail-vertical', useVerticalRail);
        stage.dataset.thumbnailRailAxis = axis;
        if (useVerticalRail) ensureThumbnailRailViewport(stage);
        if (player) player.classList.toggle('vh360-landscape-participant-rail-active', useVerticalRail && hasThumbnails);
        if (previousAxis !== axis) {
            stage.style.setProperty('--vh360-thumbnail-scroll-offset', '0px');
            stage.dataset.thumbnailScrollOffset = '0';
        }
        return axis;
    }

    function getThumbnailRailMetrics(stage) {
        if (!stage) return null;
        const axis = getThumbnailRailAxis(stage);
        const styles = window.getComputedStyle(stage);
        const stagePadding = parseStagePixelValue(styles, '--vh360-speaker-stage-padding', 16);
        const controlsHeight = parseStagePixelValue(styles, '--vh360-agora-controls-height', 64);
        const thumbnailCount = stage.querySelectorAll('.vh360-participant-tile[data-thumbnail-index]').length;
        const rect = stage.getBoundingClientRect();

        if (axis === 'vertical') {
            const railViewport = ensureThumbnailRailViewport(stage);
            const railRect = railViewport.getBoundingClientRect();
            const tiles = Array.from(stage.querySelectorAll('.vh360-participant-tile[data-thumbnail-index]'))
                .sort((a, b) => Number(a.dataset.thumbnailIndex) - Number(b.dataset.thumbnailIndex));
            const firstTileRect = tiles[0] ? tiles[0].getBoundingClientRect() : null;
            const secondTileRect = tiles[1] ? tiles[1].getBoundingClientRect() : null;
            const thumbnailWidth = firstTileRect ? firstTileRect.width : railRect.width;
            const thumbnailHeight = firstTileRect ? firstTileRect.height : thumbnailWidth * 0.5625;
            const thumbnailGap = firstTileRect && secondTileRect
                ? Math.max(0, secondTileRect.top - firstTileRect.bottom)
                : 8;
            const availableHeight = Math.max(0, railRect.height);
            const totalHeight = thumbnailCount > 0 ? (thumbnailCount * thumbnailHeight) + (Math.max(0, thumbnailCount - 1) * thumbnailGap) : 0;
            const maxScroll = Math.max(0, totalHeight - availableHeight);
            return {
                axis,
                thumbnailCount,
                thumbnailWidth,
                thumbnailHeight,
                thumbnailGap,
                availableHeight,
                totalHeight,
                maxScroll,
                railLeft: railRect.left,
                railRightEdge: railRect.right,
                railTop: railRect.top,
                railBottom: railRect.bottom
            };
        }

        const thumbnailWidth = parseStagePixelValue(styles, '--vh360-thumbnail-width', 160);
        const thumbnailHeight = parseStagePixelValue(styles, '--vh360-thumbnail-height', 90);
        const thumbnailGap = parseStagePixelValue(styles, '--vh360-thumbnail-gap', 10);
        const thumbnailRailHeight = parseStagePixelValue(styles, '--vh360-thumbnail-rail-height', thumbnailHeight);
        const thumbnailBottom = controlsHeight + stagePadding;
        const availableWidth = Math.max(0, stage.clientWidth - (stagePadding * 2));
        const totalWidth = thumbnailCount > 0 ? (thumbnailCount * thumbnailWidth) + (Math.max(0, thumbnailCount - 1) * thumbnailGap) : 0;
        const maxScroll = Math.max(0, totalWidth - availableWidth);
        const railTop = Math.max(rect.top, rect.bottom - thumbnailBottom - thumbnailRailHeight - stagePadding);
        const railBottom = Math.max(railTop, rect.bottom - controlsHeight);
        return { axis, thumbnailCount, thumbnailWidth, thumbnailHeight, thumbnailGap, stagePadding, controlsHeight, thumbnailRailHeight, availableWidth, totalWidth, maxScroll, railTop, railBottom, railLeft: rect.left, railRightEdge: rect.right };
    }

    function getThumbnailScrollOffset(stage) {
        if (!stage) return 0;
        const rawOffset = stage.style.getPropertyValue('--vh360-thumbnail-scroll-offset') || '0';
        const parsed = parseFloat(rawOffset);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function clampThumbnailScrollOffset(stage, offset) {
        if (!stage) return 0;
        const maxScroll = parseFloat(stage.dataset.thumbnailMaxScroll || '0');
        const safeMax = Number.isFinite(maxScroll) ? maxScroll : 0;
        const safeOffset = Number.isFinite(offset) ? offset : 0;
        return Math.max(0, Math.min(safeOffset, safeMax));
    }

    function setThumbnailScrollOffset(stage, offset) {
        if (!stage) return 0;
        const clampedOffset = clampThumbnailScrollOffset(stage, offset);
        stage.style.setProperty('--vh360-thumbnail-scroll-offset', `${clampedOffset}px`);
        stage.dataset.thumbnailScrollOffset = String(clampedOffset);
        stage.classList.toggle('is-thumbnail-scrolled-start', clampedOffset > 0);
        const maxScroll = parseFloat(stage.dataset.thumbnailMaxScroll || '0') || 0;
        stage.classList.toggle('is-thumbnail-scrolled-end', clampedOffset >= maxScroll - 1 && maxScroll > 0);
        return clampedOffset;
    }

    function isThumbnailRailLayout(stage) {
        const container = stage ? stage.closest('.vh360-multi-view-container, #vh360-agora-player') : null;
        return !!stage && !container?.classList.contains('vh360-gallery-view');
    }

    function updateThumbnailRailOverflow(stage) {
        if (!stage) return;
        updateThumbnailRailLayoutMode(stage);
        const metrics = getThumbnailRailMetrics(stage);
        if (!metrics) return;
        stage.dataset.thumbnailMaxScroll = String(metrics.maxScroll);
        stage.dataset.thumbnailCount = String(metrics.thumbnailCount);
        const hasOverflow = metrics.maxScroll > 1 && isThumbnailRailLayout(stage);
        stage.classList.toggle('has-thumbnail-overflow', hasOverflow);
        if (!hasOverflow) {
            setThumbnailScrollOffset(stage, 0);
            return;
        }
        setThumbnailScrollOffset(stage, getThumbnailScrollOffset(stage));
    }

    function isPointInThumbnailRail(stage, clientX, clientY) {
        const metrics = getThumbnailRailMetrics(stage);
        return !!metrics && clientX >= metrics.railLeft && clientX <= metrics.railRightEdge && clientY >= metrics.railTop && clientY <= metrics.railBottom;
    }

    function shouldIgnoreThumbnailRailEventTarget(target) {
        return !!(target && target.closest && target.closest('button, a, input, select, textarea, .vh360-view-selector, .vh360-view-dropdown-menu, .vh360-participant-menu-container, .vh360-participant-dropdown'));
    }

    function scheduleThumbnailRailOverflowUpdate(stage = thumbnailRailStage) {
        if (thumbnailRailResizeTimeout) {
            clearTimeout(thumbnailRailResizeTimeout);
        }
        thumbnailRailResizeTimeout = setTimeout(() => {
            thumbnailRailResizeTimeout = null;
            updateThumbnailRailOverflow(stage);
        }, 120);
    }

    function handleThumbnailRailWheel(event) {
        const stage = thumbnailRailStage;
        if (!stage || !stage.classList.contains('has-thumbnail-overflow') || !isThumbnailRailLayout(stage) || !isPointInThumbnailRail(stage, event.clientX, event.clientY)) return;
        const delta = getThumbnailRailAxis(stage) === 'vertical'
            ? event.deltaY || event.deltaX
            : (Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY);
        if (!delta) return;
        event.preventDefault();
        setThumbnailScrollOffset(stage, getThumbnailScrollOffset(stage) + delta);
    }

    function handleThumbnailRailPointerDown(event) {
        const stage = thumbnailRailStage;
        if (!stage || !stage.classList.contains('has-thumbnail-overflow') || !isThumbnailRailLayout(stage) || shouldIgnoreThumbnailRailEventTarget(event.target) || !isPointInThumbnailRail(stage, event.clientX, event.clientY)) return;
        thumbnailRailPointerState = {
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            startOffset: getThumbnailScrollOffset(stage),
            dragging: false
        };
        if (stage.setPointerCapture) {
            try { stage.setPointerCapture(event.pointerId); } catch (error) {}
        }
    }

    function handleThumbnailRailPointerMove(event) {
        const stage = thumbnailRailStage;
        const state = thumbnailRailPointerState;
        if (!stage || !state || state.pointerId !== event.pointerId) return;
        const deltaX = state.startX - event.clientX;
        const deltaY = state.startY - event.clientY;
        const axis = getThumbnailRailAxis(stage);
        const axisDelta = axis === 'vertical' ? deltaY : deltaX;
        const crossDelta = axis === 'vertical' ? deltaX : deltaY;
        if (!state.dragging && (Math.abs(axisDelta) <= 6 || Math.abs(axisDelta) <= Math.abs(crossDelta))) return;
        state.dragging = true;
        event.preventDefault();
        stage.classList.add('is-thumbnail-rail-dragging');
        setThumbnailScrollOffset(stage, state.startOffset + axisDelta);
    }

    function endThumbnailRailPointerDrag(event) {
        const stage = thumbnailRailStage;
        if (stage) stage.classList.remove('is-thumbnail-rail-dragging');
        if (stage && thumbnailRailPointerState && stage.releasePointerCapture) {
            try { stage.releasePointerCapture(thumbnailRailPointerState.pointerId); } catch (error) {}
        }
        thumbnailRailPointerState = null;
    }

    function handleThumbnailRailTouchStart(event) {
        const stage = thumbnailRailStage;
        const touch = event.touches && event.touches[0];
        if (!stage || !touch || !stage.classList.contains('has-thumbnail-overflow') || !isThumbnailRailLayout(stage) || shouldIgnoreThumbnailRailEventTarget(event.target) || !isPointInThumbnailRail(stage, touch.clientX, touch.clientY)) return;
        thumbnailRailTouchState = {
            startX: touch.clientX,
            startY: touch.clientY,
            startOffset: getThumbnailScrollOffset(stage),
            dragging: false
        };
    }

    function handleThumbnailRailTouchMove(event) {
        const stage = thumbnailRailStage;
        const state = thumbnailRailTouchState;
        const touch = event.touches && event.touches[0];
        if (!stage || !state || !touch) return;
        const deltaX = state.startX - touch.clientX;
        const deltaY = state.startY - touch.clientY;
        const axis = getThumbnailRailAxis(stage);
        const axisDelta = axis === 'vertical' ? deltaY : deltaX;
        const crossDelta = axis === 'vertical' ? deltaX : deltaY;
        if (!state.dragging) {
            if (Math.abs(axisDelta) < 8 || Math.abs(axisDelta) < Math.abs(crossDelta) * 1.2) return;
            state.dragging = true;
            stage.classList.add('is-thumbnail-rail-dragging');
        }
        event.preventDefault();
        setThumbnailScrollOffset(stage, state.startOffset + axisDelta);
    }

    function endThumbnailRailTouchDrag() {
        if (thumbnailRailStage) thumbnailRailStage.classList.remove('is-thumbnail-rail-dragging');
        thumbnailRailTouchState = null;
    }

    function ensureThumbnailRailScrolling(stage) {
        if (!stage || thumbnailRailStage === stage) return;
        cleanupThumbnailRailScrolling();
        thumbnailRailStage = stage;
        thumbnailRailHandlers.wheel = handleThumbnailRailWheel;
        thumbnailRailHandlers.pointerDown = handleThumbnailRailPointerDown;
        thumbnailRailHandlers.pointerMove = handleThumbnailRailPointerMove;
        thumbnailRailHandlers.pointerUp = endThumbnailRailPointerDrag;
        thumbnailRailHandlers.touchStart = handleThumbnailRailTouchStart;
        thumbnailRailHandlers.touchMove = handleThumbnailRailTouchMove;
        thumbnailRailHandlers.touchEnd = endThumbnailRailTouchDrag;
        thumbnailRailHandlers.resize = () => scheduleThumbnailRailOverflowUpdate(stage);
        thumbnailRailHandlers.fullscreenChange = () => scheduleThumbnailRailOverflowUpdate(stage);

        stage.addEventListener('wheel', thumbnailRailHandlers.wheel, { passive: false });
        stage.addEventListener('pointerdown', thumbnailRailHandlers.pointerDown);
        stage.addEventListener('pointermove', thumbnailRailHandlers.pointerMove);
        stage.addEventListener('pointerup', thumbnailRailHandlers.pointerUp);
        stage.addEventListener('pointercancel', thumbnailRailHandlers.pointerUp);
        stage.addEventListener('touchstart', thumbnailRailHandlers.touchStart, { passive: true });
        stage.addEventListener('touchmove', thumbnailRailHandlers.touchMove, { passive: false });
        stage.addEventListener('touchend', thumbnailRailHandlers.touchEnd);
        stage.addEventListener('touchcancel', thumbnailRailHandlers.touchEnd);
        window.addEventListener('resize', thumbnailRailHandlers.resize);
        window.addEventListener('orientationchange', thumbnailRailHandlers.resize);
        document.addEventListener('fullscreenchange', thumbnailRailHandlers.fullscreenChange);
        document.addEventListener('webkitfullscreenchange', thumbnailRailHandlers.fullscreenChange);
    }

    function cleanupThumbnailRailScrolling() {
        const stage = thumbnailRailStage;
        if (thumbnailRailResizeTimeout) {
            clearTimeout(thumbnailRailResizeTimeout);
            thumbnailRailResizeTimeout = null;
        }
        if (stage) {
            if (thumbnailRailHandlers.wheel) stage.removeEventListener('wheel', thumbnailRailHandlers.wheel);
            if (thumbnailRailHandlers.pointerDown) stage.removeEventListener('pointerdown', thumbnailRailHandlers.pointerDown);
            if (thumbnailRailHandlers.pointerMove) stage.removeEventListener('pointermove', thumbnailRailHandlers.pointerMove);
            if (thumbnailRailHandlers.pointerUp) {
                stage.removeEventListener('pointerup', thumbnailRailHandlers.pointerUp);
                stage.removeEventListener('pointercancel', thumbnailRailHandlers.pointerUp);
            }
            if (thumbnailRailHandlers.touchStart) stage.removeEventListener('touchstart', thumbnailRailHandlers.touchStart);
            if (thumbnailRailHandlers.touchMove) stage.removeEventListener('touchmove', thumbnailRailHandlers.touchMove);
            if (thumbnailRailHandlers.touchEnd) {
                stage.removeEventListener('touchend', thumbnailRailHandlers.touchEnd);
                stage.removeEventListener('touchcancel', thumbnailRailHandlers.touchEnd);
            }
            stage.classList.remove('has-thumbnail-overflow', 'is-thumbnail-rail-dragging', 'is-thumbnail-scrolled-start', 'is-thumbnail-scrolled-end', 'vh360-thumbnail-rail-vertical');
            const player = stage.closest('#vh360-agora-player') || document.getElementById('vh360-agora-player');
            if (player) player.classList.remove('vh360-landscape-participant-rail-active');
            stage.style.removeProperty('--vh360-thumbnail-scroll-offset');
            delete stage.dataset.thumbnailMaxScroll;
            delete stage.dataset.thumbnailScrollOffset;
            delete stage.dataset.thumbnailCount;
            delete stage.dataset.thumbnailRailAxis;
        }
        if (thumbnailRailHandlers.resize) {
            window.removeEventListener('resize', thumbnailRailHandlers.resize);
            window.removeEventListener('orientationchange', thumbnailRailHandlers.resize);
        }
        if (thumbnailRailHandlers.fullscreenChange) {
            document.removeEventListener('fullscreenchange', thumbnailRailHandlers.fullscreenChange);
            document.removeEventListener('webkitfullscreenchange', thumbnailRailHandlers.fullscreenChange);
        }
        Object.keys(thumbnailRailHandlers).forEach((key) => { thumbnailRailHandlers[key] = null; });
        thumbnailRailPointerState = null;
        thumbnailRailTouchState = null;
        thumbnailRailStage = null;
    }

    function isStudioHostUid(uid) {
        return !!(config.studioControlled && config.studioHostUid && String(uid) === String(config.studioHostUid));
    }

    function shouldShowParticipantAudioMonitorControl() {
        return !!(isStudioHostViewer && config.studioControlled && config.agoraMode === 'interactive');
    }

    function shouldPlayRemoteAudio(uid) {
        if (isStudioHostViewer && isStudioHostUid(uid)) {
            return false;
        }

        if (shouldShowParticipantAudioMonitorControl() && !participantAudioMonitoringEnabled) {
            return false;
        }

        return true;
    }

    async function resumeAgoraAudioContextForPlayback() {
        if (window.AgoraRTC && typeof window.AgoraRTC.resumeAudioContext === 'function') {
            await window.AgoraRTC.resumeAudioContext();
        }
    }

    function stopLocallyPlayingParticipantAudio() {
        participantRegistry.forEach((participant) => {
            if (!participant || !participant.audioTrack || isStudioHostUid(participant.uid)) return;
            if (typeof participant.audioTrack.stop === 'function') {
                try { participant.audioTrack.stop(); } catch (error) { window.vh360Warn('Agora: Failed to stop local participant audio playback:', { uid: participant.uid, error }); }
            }
        });
    }

    async function applyParticipantAudioMonitoringState() {
        if (!participantAudioMonitoringEnabled) {
            stopLocallyPlayingParticipantAudio();
            return;
        }
        try { await resumeAgoraAudioContextForPlayback(); } catch (error) { window.vh360Warn('Agora: Failed to resume audio context for participant monitoring:', error); }
        participantRegistry.forEach((participant) => {
            if (!participant || !participant.audioTrack || typeof participant.audioTrack.play !== 'function') return;
            if (!shouldPlayRemoteAudio(participant.uid)) return;
            try { participant.audioTrack.play(); } catch (error) { window.vh360Warn('Agora: Failed to resume participant audio playback:', { uid: participant.uid, error }); }
        });
    }

    function resolveWordPressUserId(uid, options = {}) {
        const cachedIdentity = verifiedIdentityCache.get(normalizeParticipantUid(uid));
        if (Object.prototype.hasOwnProperty.call(options, 'wordpressUserId')) return options.wordpressUserId || null;
        if (cachedIdentity && cachedIdentity.wordpressUserId) return cachedIdentity.wordpressUserId;
        if (isStudioHostUid(uid)) return config.studioHostUserId || null;
        if (remoteUsers[uid] && remoteUsers[uid].wordpressUserId) return remoteUsers[uid].wordpressUserId;
        if (currentUserUID && String(uid) === String(currentUserUID)) return config.viewerUserId || security.user_id || null;
        return null;
    }

    function resolveParticipantDisplayName(uid, options = {}) {
        const cachedIdentity = verifiedIdentityCache.get(normalizeParticipantUid(uid));
        if (options.displayName) return options.displayName;
        if (cachedIdentity && cachedIdentity.displayName) return cachedIdentity.displayName;
        if (isStudioHostUid(uid) && config.studioHostDisplayName) return config.studioHostDisplayName;
        if (remoteUsers[uid] && remoteUsers[uid].displayName) return remoteUsers[uid].displayName;
        if (currentUserUID && String(uid) === String(currentUserUID)) return config.viewerDisplayName || config.displayName || security.display_name || 'Participant';
        return 'Participant';
    }

    function getOrCreateParticipant(uid, options = {}) {
        const key = normalizeParticipantUid(uid);
        if (!key) return null;
        let participant = participantRegistry.get(key);
        if (!participant) {
            participant = {
                uid: key,
                agoraUid: key,
                wordpressUserId: resolveWordPressUserId(key, options),
                isLocal: !!options.isLocal || (currentUserUID && key === String(currentUserUID)),
                isOriginalHost: !!options.isOriginalHost || key === String(originalHostUID || '') || !!(verifiedIdentityCache.get(key) && verifiedIdentityCache.get(key).isOriginalHost),
                isActiveSpeaker: false,
                audioTrack: null,
                videoTrack: null,
                tileElement: null,
                displayName: resolveParticipantDisplayName(key, options),
                cameraOn: false,
                audioOn: false
            };
            participantRegistry.set(key, participant);
            dispatchAgoraParticipantEvent('vh360:agora-participant-added', participant);
        } else {
            participant.wordpressUserId = resolveWordPressUserId(key, { wordpressUserId: participant.wordpressUserId || options.wordpressUserId });
            participant.displayName = resolveParticipantDisplayName(key, { displayName: options.displayName || participant.displayName });
            participant.isLocal = participant.isLocal || !!options.isLocal || (currentUserUID && key === String(currentUserUID));
            participant.isOriginalHost = participant.isOriginalHost || !!options.isOriginalHost || key === String(originalHostUID || '');
        }
        ensureParticipantTile(participant);
        updateParticipantTile(participant);
        return participant;
    }

    function cleanupLegacyStageMessages(stage) {
        if (!stage) return;
        const selectors = [
            ':scope > .waiting-message',
            ':scope > .vh360-waiting-message',
            ':scope > .vh360-waiting-text',
            ':scope > .vh360-agora-stage-status',
            ':scope > .vh360-agora-status-message',
            ':scope > [data-vh360-stage-status="true"]',
            ':scope > #agora-error-overlay',
            ':scope > #agora-success-overlay'
        ];

        selectors.forEach((selector) => {
            try {
                stage.querySelectorAll(selector).forEach((element) => element.remove());
            } catch (error) {
                // Some older browsers may not support :scope; ignore and avoid broad child cleanup.
            }
        });

        stage.querySelectorAll('.vh360-agora-stage-status, .vh360-agora-status-message, [data-vh360-stage-status="true"]').forEach((element) => {
            if (element.parentElement === stage) {
                element.remove();
            }
        });
    }

    function ensureParticipantTile(participant) {
        const stage = getParticipantStage();
        if (!stage) return null;
        cleanupLegacyStageMessages(stage);

        let tile = participant.tileElement || document.getElementById(`player-${participant.uid}`);
        if (tile && (!tile.isConnected || tile.parentElement !== stage)) {
            stage.appendChild(tile);
        }
        if (!tile) {
            tile = document.createElement('div');
            tile.id = `player-${participant.uid}`;
            tile.className = 'vh360-participant-tile vh360-video-remote camera-off audio-muted';
            const video = document.createElement('div');
            video.id = `player-video-${participant.uid}`;
            video.className = 'vh360-participant-video';
            tile.appendChild(video);
            const placeholder = document.createElement('div');
            placeholder.className = 'vh360-video-content vh360-participant-placeholder';
            placeholder.innerHTML = '<div class="vh360-camera-icon vh360-icon-small">📹</div><div class="vh360-placeholder-text">Camera Off</div>';
            tile.appendChild(placeholder);
            const name = document.createElement('div');
            name.className = 'vh360-user-info vh360-video-name-overlay';
            tile.appendChild(name);
            ensureParticipantStateIndicators(participant, tile);
            stage.appendChild(tile);
        }
        ensureParticipantStateIndicators(participant, tile);
        participant.tileElement = tile;
        let videoContainer = tile.querySelector('.vh360-participant-video');
        if (!videoContainer) {
            videoContainer = document.createElement('div');
            videoContainer.id = `player-video-${participant.uid}`;
            videoContainer.className = 'vh360-participant-video';
            tile.insertBefore(videoContainer, tile.firstChild);
        }
        participant.videoContainerElement = videoContainer;
        return tile;
    }


    function ensureParticipantStateIndicators(participant, tile) {
        if (!participant || !tile) return null;

        tile.querySelectorAll('.volume-indicator').forEach((legacyIndicator) => legacyIndicator.remove());

        let layer = tile.querySelector('.vh360-participant-status-layer');
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'vh360-participant-status-layer';
            layer.setAttribute('aria-hidden', 'true');
            tile.appendChild(layer);
        }

        let roleBadges = layer.querySelector('.vh360-participant-role-badges');
        if (!roleBadges) {
            roleBadges = document.createElement('div');
            roleBadges.className = 'vh360-participant-role-badges';
            layer.appendChild(roleBadges);
        }

        let mediaIndicators = layer.querySelector('.vh360-participant-media-indicators');
        if (!mediaIndicators) {
            mediaIndicators = document.createElement('div');
            mediaIndicators.className = 'vh360-participant-media-indicators';
            layer.appendChild(mediaIndicators);
        }

        let speakingIndicator = layer.querySelector('.vh360-participant-speaking-indicator');
        if (!speakingIndicator) {
            speakingIndicator = document.createElement('div');
            speakingIndicator.className = 'vh360-participant-speaking-indicator';
            speakingIndicator.textContent = 'Speaking';
            layer.appendChild(speakingIndicator);
        }

        return { layer, roleBadges, mediaIndicators, speakingIndicator };
    }

    function updateParticipantStateIndicators(participant, tile) {
        const indicators = ensureParticipantStateIndicators(participant, tile);
        if (!participant || !tile || !indicators) return;

        const roleBadges = indicators.roleBadges;
        const mediaIndicators = indicators.mediaIndicators;
        roleBadges.textContent = '';
        mediaIndicators.textContent = '';

        if (participant.isLocal) {
            const youBadge = document.createElement('span');
            youBadge.className = 'vh360-participant-badge vh360-participant-badge-you';
            youBadge.textContent = 'You';
            roleBadges.appendChild(youBadge);
        }

        if (participant.isOriginalHost) {
            const hostBadge = document.createElement('span');
            hostBadge.className = 'vh360-participant-badge vh360-participant-badge-host';
            hostBadge.textContent = 'Host';
            roleBadges.appendChild(hostBadge);
        }

        if (!participant.audioTrack || participant.audioOn === false) {
            const micIndicator = document.createElement('span');
            micIndicator.className = 'vh360-participant-media-indicator vh360-participant-mic-muted';
            micIndicator.textContent = 'Mic off';
            mediaIndicators.appendChild(micIndicator);
        }

        if (!participant.videoTrack || participant.cameraOn === false) {
            const cameraIndicator = document.createElement('span');
            cameraIndicator.className = 'vh360-participant-media-indicator vh360-participant-camera-off';
            cameraIndicator.textContent = 'Camera off';
            mediaIndicators.appendChild(cameraIndicator);
        }

        indicators.speakingIndicator.textContent = participant.isSpeaking ? 'Speaking' : '';
    }

    function setParticipantSpeakingState(uid, level) {
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        if (!participant) return;
        const isSpeaking = Number(level) >= ACTIVE_SPEAKER_EXIT_THRESHOLD;
        if (participant.isSpeaking === isSpeaking) {
            return;
        }
        participant.isSpeaking = isSpeaking;
        updateParticipantTile(participant);
    }

    function setActiveAgoraVideoClasses(participant, hasActiveVideo) {
        const tile = participant && participant.tileElement ? participant.tileElement : null;
        const container = participant && participant.videoContainerElement ? participant.videoContainerElement : null;
        const stage = container ? container.closest('#vh360-agora-local-player, #vh360-agora-remote-players') : getParticipantStage();

        [tile, container].forEach((element) => {
            if (element) {
                element.classList.toggle('vh360-has-active-agora-video', !!hasActiveVideo);
            }
        });

        if (stage) {
            const stageHasActiveVideo = !!hasActiveVideo || !!stage.querySelector('.vh360-participant-tile.vh360-has-active-agora-video, .vh360-participant-video.vh360-has-active-agora-video');
            stage.classList.toggle('vh360-has-active-agora-video', stageHasActiveVideo);
        }
    }

    function ensureParticipantFocusControl(participant, tile) {
        if (!participant || !tile || config.agoraMode !== 'interactive') return;
        let button = tile.querySelector('.vh360-participant-focus-btn');
        if (!button) {
            button = document.createElement('button');
            button.type = 'button';
            button.className = 'vh360-participant-focus-btn';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
                if (layoutManager && typeof layoutManager.toggleParticipantFocus === 'function') {
                    layoutManager.toggleParticipantFocus(participant.uid);
                }
            });
            tile.appendChild(button);
        }

        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        const isFocused = !!(layoutManager && typeof layoutManager.getPinnedParticipantUid === 'function' && layoutManager.getPinnedParticipantUid() === String(participant.uid));
        const name = participant.displayName || 'Participant';
        button.textContent = isFocused ? 'Unfocus' : 'Focus';
        button.setAttribute('aria-label', `${isFocused ? 'Unfocus' : 'Focus'} ${name}`);
        button.setAttribute('aria-pressed', String(isFocused));
    }

    function updateParticipantTile(participant) {
        const tile = ensureParticipantTile(participant);
        if (!tile) return;
        tile.dataset.agoraUid = participant.agoraUid || participant.uid;
        tile.dataset.wordpressUserId = participant.wordpressUserId || '';
        tile.dataset.identitySource = participant.wordpressUserId ? 'wordpress' : 'agora';
        tile.classList.toggle('is-local-user', !!participant.isLocal);
        tile.classList.toggle('is-original-host', !!participant.isOriginalHost);
        tile.classList.toggle('is-active-speaker', !!participant.isActiveSpeaker);
        tile.classList.toggle('has-video', !!participant.videoTrack && participant.cameraOn !== false);
        setActiveAgoraVideoClasses(participant, !!participant.videoTrack && participant.cameraOn !== false);
        tile.classList.toggle('has-audio', !!participant.audioTrack && participant.audioOn !== false);
        tile.classList.toggle('camera-off', !participant.videoTrack || participant.cameraOn === false);
        tile.classList.toggle('audio-muted', !participant.audioTrack || participant.audioOn === false);
        tile.classList.toggle('is-speaking', !!participant.isSpeaking);
        tile.dataset.audioState = participant.audioTrack && participant.audioOn !== false ? 'on' : 'muted';
        tile.dataset.videoState = participant.videoTrack && participant.cameraOn !== false ? 'on' : 'off';
        tile.dataset.participantRole = participant.isOriginalHost ? 'host' : 'participant';
        tile.dataset.speaking = participant.isSpeaking ? 'true' : 'false';
        const isFocused = isFocusedParticipant(participant.uid);
        tile.classList.toggle('is-focused-participant', isFocused);
        tile.classList.toggle('is-featured', shouldFeatureParticipant(participant.uid));
        ensureParticipantFocusControl(participant, tile);
        const label = tile.querySelector('.vh360-user-info, .vh360-video-name-overlay');
        if (label) label.textContent = participant.displayName || 'Participant';
        const stateParts = [participant.displayName || 'Participant'];
        if (participant.isLocal) stateParts.push('You');
        if (participant.isOriginalHost) stateParts.push('Host');
        stateParts.push(tile.dataset.videoState === 'on' ? 'camera on' : 'camera off');
        stateParts.push(tile.dataset.audioState === 'on' ? 'mic on' : 'mic muted');
        if (participant.isSpeaking) stateParts.push('speaking');
        if (isFocused) stateParts.push('focused');
        tile.setAttribute('aria-label', stateParts.join(', '));
        tile.setAttribute('title', stateParts.join(', '));
        updateParticipantStateIndicators(participant, tile);

        const speakerBadge = tile.querySelector('.active-speaker-badge');
        if (speakerBadge && participant.videoTrack && participant.cameraOn !== false) {
            speakerBadge.style.display = 'none';
        }
        dispatchAgoraParticipantEvent('vh360:agora-participant-updated', participant);
    }

    function getPinnedParticipantUidFromLayout() {
        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        if (layoutManager && typeof layoutManager.getPinnedParticipantUid === 'function') {
            return layoutManager.getPinnedParticipantUid();
        }
        return layoutManager && layoutManager.pinnedParticipantUid ? layoutManager.pinnedParticipantUid : null;
    }

    function isFocusedParticipant(uid) {
        const pinnedUid = getPinnedParticipantUidFromLayout();
        return !!pinnedUid && normalizeParticipantUid(uid) === String(pinnedUid);
    }

    function shouldFeatureParticipant(uid) {
        const key = normalizeParticipantUid(uid);
        if (!key) return false;

        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        const currentLayoutView = layoutManager && layoutManager.currentView ? layoutManager.currentView : 'speaker';

        // Gallery View keeps every persistent tile in the grid. Keep the single-participant
        // fallback so one-person rooms still fill the stage, but do not feature active speakers.
        if (currentLayoutView === 'gallery') {
            return participantRegistry.size === 1;
        }

        if (currentLayoutView === 'focus') {
            const pinnedUid = getPinnedParticipantUidFromLayout();
            if (pinnedUid && participantRegistry.has(String(pinnedUid))) {
                return key === String(pinnedUid);
            }
            if (layoutManager && typeof layoutManager.unpinParticipant === 'function') {
                layoutManager.unpinParticipant();
            }
        }

        if (activeSpeakerUid && key === String(activeSpeakerUid)) return true;
        if (!activeSpeakerUid && originalHostUID && key === String(originalHostUID)) return true;
        if (!activeSpeakerUid && !originalHostUID && currentUserUID && key === String(currentUserUID)) return true;
        return participantRegistry.size === 1;
    }

    function refreshFeaturedParticipantTiles() {
        const stage = getParticipantStage();
        const participantCount = participantRegistry.size;
        if (stage) {
            stage.classList.toggle('has-single-participant', participantCount <= 1);
            stage.classList.toggle('has-multiple-participants', participantCount > 1);
        }

        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        if (layoutManager) {
            layoutManager.participantCount = participantCount;
            if (typeof layoutManager.updateViewSelectorState === 'function') {
                layoutManager.updateViewSelectorState();
            }
        }

        participantRegistry.forEach((participant) => updateParticipantTile(participant));

        let thumbnailIndex = 0;
        let previousThumbnailCount = stage ? parseInt(stage.dataset.thumbnailCount || '0', 10) || 0 : 0;
        participantRegistry.forEach((participant) => {
            if (!participant.tileElement) return;

            const isFeatured = participant.tileElement.classList.contains('is-featured');
            if (isFeatured) {
                participant.tileElement.style.removeProperty('--vh360-thumbnail-index');
                delete participant.tileElement.dataset.thumbnailIndex;
                return;
            }

            participant.tileElement.style.setProperty('--vh360-thumbnail-index', thumbnailIndex);
            participant.tileElement.dataset.thumbnailIndex = String(thumbnailIndex);
            thumbnailIndex += 1;
        });

        if (stage) {
            updateThumbnailRailLayoutMode(stage);
            if (previousThumbnailCount !== thumbnailIndex) {
                setThumbnailScrollOffset(stage, 0);
            }
            updateThumbnailRailOverflow(stage);
        }
    }


    window.vh360RefreshFeaturedParticipantTiles = refreshFeaturedParticipantTiles;

    function setParticipantVideoPlaybackState(participant, state, details = {}) {
        if (!participant) return;
        participant.videoPlaybackState = state || 'off';
        participant.videoPlaybackDetails = { ...(participant.videoPlaybackDetails || {}), ...details, updatedAt: Date.now() };
        if (participant.tileElement) {
            participant.tileElement.dataset.videoPlaybackState = participant.videoPlaybackState;
            participant.tileElement.classList.toggle('vh360-video-reconnecting', participant.videoPlaybackState === 'reconnecting');
            participant.tileElement.classList.toggle('vh360-video-fallback', participant.videoPlaybackState === 'fallback');
            participant.tileElement.classList.toggle('vh360-video-attaching', ['subscribed', 'attaching', 'waiting-for-frame'].includes(participant.videoPlaybackState));
            participant.tileElement.classList.toggle('vh360-video-failed', participant.videoPlaybackState === 'failed');
        }
        recordParticipantDiagnostic(participant.uid, { playbackState: participant.videoPlaybackState, playbackDetails: participant.videoPlaybackDetails });
    }

    function waitForFirstRemoteVideoFrame(videoTrack, timeoutMs = 2500) {
        return new Promise((resolve) => {
            let settled = false;
            let timeout = null;
            const finish = (ok) => {
                if (settled) return;
                settled = true;
                if (timeout) clearTimeout(timeout);
                try {
                    if (videoTrack && typeof videoTrack.off === 'function') videoTrack.off('first-frame-decoded', onFrame);
                } catch (error) {}
                resolve(!!ok);
            };
            const onFrame = () => finish(true);
            try {
                if (videoTrack && typeof videoTrack.once === 'function') videoTrack.once('first-frame-decoded', onFrame);
                else if (videoTrack && typeof videoTrack.on === 'function') videoTrack.on('first-frame-decoded', onFrame);
            } catch (error) {}
            timeout = setTimeout(() => {
                const hasPlayingState = videoTrack && (videoTrack.isPlaying || videoTrack._isPlaying);
                finish(!!hasPlayingState);
            }, timeoutMs);
        });
    }

    function attachParticipantVideo(participant, videoTrack, isLocalTrack = false) {
        if (!participant || !videoTrack || typeof videoTrack.play !== 'function') {
            window.vh360Warn('Agora: Cannot attach participant video; missing participant or playable track', {
                uid: participant && participant.uid,
                hasTrack: !!videoTrack
            });
            return false;
        }

        const previousTrack = participant.videoTrack;
        participant.videoTrack = videoTrack;
        participant.cameraOn = true;
        setParticipantVideoPlaybackState(participant, 'attaching');

        const tile = ensureParticipantTile(participant);
        const container = tile ? tile.querySelector('.vh360-participant-video') : null;
        if (!container) {
            window.vh360Warn('Agora: Cannot attach participant video; missing video container', participant.uid);
            setParticipantVideoPlaybackState(participant, 'failed', { reason: 'missing-container' });
            return false;
        }

        participant.videoContainerElement = container;
        updateParticipantTile(participant);

        try {
            const oldVideoElements = Array.from(container.querySelectorAll('video'));
            if (previousTrack && previousTrack !== videoTrack) {
                setParticipantVideoPlaybackState(participant, 'waiting-for-frame');
            }
            videoTrack.play(container, isLocalTrack ? { mirror: false } : undefined);
            setActiveAgoraVideoClasses(participant, true);
            if (isLocalTrack || !previousTrack || previousTrack === videoTrack) {
                videoElementManager.registerTrackBinding(container.id, !!isLocalTrack, isLocalTrack ? null : videoTrack);
            }
            if (isLocalTrack) {
                setParticipantVideoPlaybackState(participant, 'playing');
            } else {
                waitForFirstRemoteVideoFrame(videoTrack).then((ready) => {
                    if (participant.videoTrack !== videoTrack) return;
                    if (ready) {
                        const currentVideos = Array.from(container.querySelectorAll('video'));
                        oldVideoElements.forEach((element) => {
                            if (currentVideos.includes(element) && currentVideos.length > 1) element.remove();
                        });
                        const remaining = Array.from(container.querySelectorAll('video'));
                        remaining.slice(0, Math.max(0, remaining.length - 1)).forEach((element) => element.remove());
                        if (window.videoElementManager) videoElementManager.registerTrackBinding(container.id, false, videoTrack);
                    }
                    setParticipantVideoPlaybackState(participant, ready ? 'playing' : 'waiting-for-frame', { firstFrameDecoded: ready });
                    updateParticipantTile(participant);
                    cleanupParticipantVideoStyles(participant);
                });
            }
            setTimeout(() => cleanupParticipantVideoStyles(participant), 200);
            window.vh360Log('Agora: Attached video track to persistent participant tile', {
                uid: participant.uid,
                containerId: container.id,
                preservedExistingElement: !!(previousTrack && previousTrack !== videoTrack),
                playbackState: participant.videoPlaybackState
            });
            return true;
        } catch (error) {
            participant.videoTrack = previousTrack || null;
            if (!previousTrack) participant.cameraOn = false;
            setParticipantVideoPlaybackState(participant, previousTrack ? 'playing' : 'failed', { error: error && (error.message || error.code || String(error)) });
            setActiveAgoraVideoClasses(participant, !!previousTrack);
            updateParticipantTile(participant);
            window.vh360Warn('Agora: Failed to play video track in participant tile', {
                uid: participant.uid,
                containerId: container.id,
                error
            });
            return false;
        }
    }

    function cleanupParticipantVideoStyles(participant) {
        const videoElement = participant && participant.tileElement ? participant.tileElement.querySelector('video') : null;
        if (!videoElement) return;
        videoElement.style.width = ''; videoElement.style.height = ''; videoElement.style.maxWidth = ''; videoElement.style.maxHeight = ''; videoElement.style.objectFit = '';
        videoElement.removeAttribute('width'); videoElement.removeAttribute('height');
    }

    function removeParticipantTile(uid) {
        const key = normalizeParticipantUid(uid);
        const participant = participantRegistry.get(key);
        const tile = participant ? participant.tileElement : document.getElementById(`player-${key}`);
        if (tile && tile._moderationDropdown) tile._moderationDropdown.remove();
        if (participant && participant.videoContainerElement && window.videoElementManager) window.videoElementManager.unregisterTrackBinding(participant.videoContainerElement.id);
        if (participant) setActiveAgoraVideoClasses(participant, false);
        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        if (layoutManager && typeof layoutManager.handleParticipantLeft === 'function') {
            layoutManager.handleParticipantLeft(key);
        }
        if (tile) tile.remove();
        participantRegistry.delete(key);
        if (participant) { dispatchAgoraParticipantEvent('vh360:agora-participant-removed', participant); }
        if (remoteUsers && remoteUsers[key]) {
            delete remoteUsers[key];
        }
        refreshFeaturedParticipantTiles();
    }

    function clearAllParticipantTiles(options = {}) {
        participantRegistry.forEach((participant) => {
            if (participant.tileElement && participant.tileElement._moderationDropdown) {
                participant.tileElement._moderationDropdown.remove();
            }
            if (participant.videoContainerElement && window.videoElementManager) {
                window.videoElementManager.unregisterTrackBinding(participant.videoContainerElement.id);
            }
            if (participant.tileElement) {
                participant.tileElement.remove();
            }
        });

        participantRegistry.clear();
        Object.keys(remoteUsers).forEach((uid) => {
            delete remoteUsers[uid];
        });
        window.remoteUsers = remoteUsers;
        if (window.vh360AgoraPlayer && window.vh360AgoraPlayer.remoteUsers !== remoteUsers) {
            window.vh360AgoraPlayer.remoteUsers = remoteUsers;
        }
        activeSpeakerUid = null;
        speakerCandidateUid = null;
        speakerCandidateSince = 0;
        if (activeSpeakerDebounceTimeout) {
            clearTimeout(activeSpeakerDebounceTimeout);
            activeSpeakerDebounceTimeout = null;
        }
        lastActiveSpeakerChange = 0;

        if (options.clearDetachedDom !== false) {
            document.querySelectorAll('[id^="player-"]').forEach((element) => {
                if (element._moderationDropdown) {
                    element._moderationDropdown.remove();
                }
                element.remove();
            });
        }
    }

    window.vh360RemoveParticipantTile = removeParticipantTile;
    window.vh360ClearAllParticipantTiles = clearAllParticipantTiles;

    function setLocalPlayerStatusHTML(html) {
        clearAllParticipantTiles();
        const localPlayer = document.getElementById("vh360-agora-local-player");
        if (localPlayer) {
            localPlayer.innerHTML = '<div class="vh360-agora-stage-status" data-vh360-stage-status="true">' + html + '</div>';
        }
    }

    // === Participant Moderation Functions ===

    /**
     * Sends moderation command via Agora data stream for real-time communication
     */
    function sendModerationCommand(commandData) {
        window.vh360Log('Agora: ⚡ CRITICAL: Sending moderation command:', commandData);

        if (!window.sendDataStreamMessage) {
            window.vh360Error('Agora: sendDataStreamMessage function not available');
            // If data stream messaging is not available, try direct approach for self-moderation
            if (commandData.target_user_id && commandData.target_user_id == security.user_id && window.handleModerationAction) {
                window.vh360Log('Agora: Attempting direct self-moderation fallback');
                window.handleModerationAction(commandData);
            }
            return;
        }

        try {
            // Use the existing sendDataStreamMessage function which handles connection checks
            const messageData = {
                type: 'moderation_action',
                ...commandData
            };

            window.vh360Log('Agora: ⚡ CRITICAL: Broadcasting moderation message:', messageData);
            window.sendDataStreamMessage(messageData);
            window.vh360Log('Agora: Moderation command sent successfully');

            // Additional fallback: if we're moderating ourselves, handle it directly too
            if (commandData.target_user_id && commandData.target_user_id == security.user_id && window.handleModerationAction) {
                window.vh360Log('Agora: Handling self-moderation directly as fallback');
                setTimeout(() => {
                    window.handleModerationAction(commandData);
                }, 200); // Reduced delay for faster self-moderation
            }

            // Enhanced retry pattern with more aggressive initial attempts
            const retries = [500, 1200, 2500, 4500, 7500]; // 5 retries with faster initial attempts

            retries.forEach((delay, index) => {
                setTimeout(() => {
                    try {
                        window.vh360Log(`Agora: ⚡ CRITICAL: Sending moderation command retry #${index + 1}`);
                        window.sendDataStreamMessage(messageData);
                    } catch (error) {
                        window.vh360Error(`Agora: Retry #${index + 1} failed:`, error);
                    }
                }, delay);
            });

            // More aggressive immediate fallback checks
            setTimeout(() => {
                if (window.triggerImmediateModerationCheck) {
                    window.vh360Log('Agora: Triggering immediate moderation check after 1.5s');
                    window.triggerImmediateModerationCheck();
                }
            }, 1500);

            setTimeout(() => {
                if (window.triggerImmediateModerationCheck) {
                    window.vh360Log('Agora: Triggering second immediate moderation check after 4s');
                    window.triggerImmediateModerationCheck();
                }
            }, 4000);

            setTimeout(() => {
                if (window.triggerImmediateModerationCheck) {
                    window.vh360Log('Agora: Triggering final fallback moderation check after 8s');
                    window.triggerImmediateModerationCheck();
                }
            }, 8000);

        } catch (error) {
            window.vh360Error('Agora: ⚡ CRITICAL: Failed to send moderation command:', error);

            // Fallback: if we're moderating ourselves, handle it directly
            if (commandData.target_user_id && commandData.target_user_id == security.user_id && window.handleModerationAction) {
                window.vh360Log('Agora: Falling back to direct self-moderation due to send failure');
                window.handleModerationAction(commandData);
            }
        }
    }
    /**
     * Executes the moderation action via AJAX and real-time communication
     */
    function executeParticipantModeration(uid, displayName, actionType) {
        // Validate parameters
        if (!uid || !actionType) {
            showModerationToast('Invalid moderation parameters', 'error');
            return;
        }

        window.vh360Log('Agora: Executing moderation - UID:', uid, 'Action:', actionType, 'Display Name:', displayName);
        window.vh360Log('Agora: Current remoteUsers data:', remoteUsers);

        // Ensure we have a proper display name
        const participantName = displayName || 'Participant';

        // Show loading state
        showModerationToast(`${actionType.charAt(0).toUpperCase() + actionType.slice(1)}ing ${participantName}...`, 'info');

        // Get target WordPress user ID from the participant registry first; Agora UID is not always a WordPress ID.
        const participantRecord = participantRegistry.get(normalizeParticipantUid(uid));
        let targetUserId = 0;
        if (participantRecord && participantRecord.wordpressUserId) {
            targetUserId = participantRecord.wordpressUserId;
            window.vh360Log('Agora: Found WordPress user ID in participant registry for UID', uid, ':', targetUserId);
        } else if (remoteUsers[uid] && remoteUsers[uid].wordpressUserId) {
            targetUserId = remoteUsers[uid].wordpressUserId;
            window.vh360Log('Agora: Found WordPress user ID in remoteUsers for UID', uid, ':', targetUserId);
        } else if (uid === currentUserUID || String(uid) === String(currentUserUID)) {
            // If moderating self
            targetUserId = security.user_id;
            window.vh360Log('Agora: Self-moderation detected, using own WordPress ID:', targetUserId);
        } else {
            window.vh360Log('Agora: No WordPress user ID found for UID', uid, 'in participant registry or remoteUsers:', { participantRecord, remoteUsers });
        }

        window.vh360Log('Agora: Final targetUserId for moderation:', targetUserId);

        // Validate required data
        if (!vh360Data.postId || !vh360Data.moderationNonce) {
            window.vh360Error('Agora: Missing required moderation data:', {
                postId: vh360Data.postId,
                moderationNonce: vh360Data.moderationNonce ? 'present' : 'missing'
            });
            showModerationToast('Missing required moderation data', 'error');
            return;
        }

        // Enhanced debug logging
        window.vh360Log('Agora: Preparing moderation AJAX request:', {
            action: 'vh360_remove_participant',
            postId: vh360Data.postId,
            targetUid: uid,
            targetUserId: targetUserId,
            actionType: actionType,
            displayName: participantName,
            hasNonce: !!vh360Data.moderationNonce
        });

        // Prepare AJAX data
        const formData = new FormData();
        formData.append('action', 'vh360_remove_participant');
        formData.append('nonce', vh360Data.moderationNonce);
        formData.append('post_id', vh360Data.postId);
        formData.append('target_uid', uid);
        formData.append('target_user_id', targetUserId);
        formData.append('action_type', actionType);
        formData.append('display_name', participantName);
        formData.append('reason', `Moderated via 3-dot menu (${actionType})`);

        // Send AJAX request
        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            window.vh360Log('Agora: Moderation AJAX response:', data);

            if (data.success) {
                // Show success message
                window.vh360Log('Agora: Moderation action successful:', data.data);
                showModerationToast(data.data.message, 'success');

                // Send real-time moderation command via Agora data stream
                if (window.sendDataStreamMessage && data.data.realtime_data) {
                    sendModerationCommand(data.data.realtime_data);
                }

                // Also trigger the standard immediate checks
                if (window.triggerImmediateModerationCheck) {
                    setTimeout(() => window.triggerImmediateModerationCheck(), 2000); // First check after 2 seconds
                    setTimeout(() => window.triggerImmediateModerationCheck(), 5000); // Second check after 5 seconds
                    setTimeout(() => window.triggerImmediateModerationCheck(), 10000); // Third check after 10 seconds
                }

                // Remove participant from UI after short delay
                setTimeout(() => {
                    removeParticipantFromUI(uid);
                }, 1000);

            } else {
                // Enhanced error message for debugging
                const errorMessage = data.data || 'Failed to moderate participant';
                window.vh360Error('Agora: Moderation AJAX failed:', {
                    fullResponse: data,
                    errorMessage: errorMessage,
                    success: data.success
                });
                showModerationToast(errorMessage, 'error');
            }
        })
        .catch(error => {
            window.vh360Error('Moderation error:', error);
            window.vh360Error('Moderation request details:', {
                url: vh360Data.ajaxUrl,
                postId: vh360Data.postId,
                targetUid: uid,
                actionType: actionType,
                nonce: vh360Data.moderationNonce ? 'present' : 'missing'
            });

            if (error.message.includes('Failed to fetch')) {
                showModerationToast('Network error - please check your connection', 'error');
            } else {
                showModerationToast('An error occurred during moderation: ' + error.message, 'error');
            }
        });
    }

    // Make executeParticipantModeration available globally from within this scope
    window.executeParticipantModeration = executeParticipantModeration;

    // Get control elements
    const muteAudioBtn = document.getElementById('vh360-agora-mute-audio');
    const participantAudioBtn = document.getElementById('vh360-agora-participant-audio');
    const muteVideoBtn = document.getElementById('vh360-agora-mute-video');
    const joinAsPresenterBtn = document.getElementById('vh360-agora-join-presenter');
    const leaveBtn = document.getElementById('vh360-agora-leave');
    const endStreamBtn = document.getElementById('vh360-agora-end-stream');
    const controlsContainer = document.getElementById('vh360-agora-controls');

    // Get overlay element (button is handled via event delegation below)
    const joinOverlay = document.getElementById('vh360-join-livestream-overlay');

    // === Permission Helper Functions ===
    function isUserAdministrator() {
        return window.vh360Data && window.vh360Data.user_role === 'administrator';
    }

    function shouldShowControlsForUser() {
        // Show controls to all users in all modes
        // Individual buttons will be filtered by role and permissions
        return true;
    }

    function shouldShowModerationButton() {
        // Moderation button for users who can moderate
        return canModerate || isOriginalHost;
    }

    function shouldShowEndStreamButton() {
        // End stream button only for original host
        return isOriginalHost;
    }

    function getAllButtonElements() {
        // Return array of all button elements that might need transformation
        return [muteAudioBtn, participantAudioBtn, muteVideoBtn, joinAsPresenterBtn, leaveBtn, endStreamBtn, moderationBtn].filter(btn => btn);
    }

    // == Agora Client Setup ==
    try {
        client = AgoraRTC.createClient({ mode: config.mode, codec: "vp8" });
        window.vh360Log("Agora: Client created successfully with mode:", config.mode, "Initial role:", currentRole);

        // Make client globally available for moderation checks
        window.agoraClient = client;
    } catch (error) {
        window.vh360Error("Agora: Failed to create client", error);
        showEarlyAgoraError("Failed to initialize livestream client. Please refresh the page.");
        return null;
    }

    // Enable audio volume indication for voice-activated switching in interactive mode
    bindAutoplayFailureRecovery();
    startAgoraDiagnostics();
    if (config.agoraMode === 'interactive') {
        enableVolumeIndication();
        configureInteractiveDualStream();
    }

    // In SDK v4, setClientRole is deprecated for rtc mode
    // Role management is now handled via join options and publishing state

    // == Voice-Activated Video Switching Functions ==

    function enableVolumeIndication() {
        try {
            if (typeof client.off === 'function') client.off("volume-indicator", handleVolumeIndication);
            client.enableAudioVolumeIndicator();
            isVolumenIndicationEnabled = true;
            window.vh360Log("Agora: Audio volume indication enabled as fallback visual source");
            client.on("volume-indicator", handleVolumeIndication);
        } catch (error) {
            window.vh360Warn("Agora: Failed to enable volume indication", error);
            isVolumenIndicationEnabled = false;
        }
    }

    function resetActiveSpeakerState() {
        if (activeSpeakerDebounceTimeout) clearTimeout(activeSpeakerDebounceTimeout);
        activeSpeakerDebounceTimeout = null;
        speakerCandidateUid = null;
        speakerCandidateSince = 0;
        activeSpeakerReleaseSince = 0;
        activeSpeakerLevels.clear();
        activeSpeakerCandidates.clear();
    }

    function stopActiveSpeakerDetection() {
        if (activeSpeakerSampleTimer) clearInterval(activeSpeakerSampleTimer);
        activeSpeakerSampleTimer = null;
        resetActiveSpeakerState();
    }

    function hasInspectableAudioTracks() {
        if (localTracks.audioTrack && typeof localTracks.audioTrack.getVolumeLevel === 'function') return true;
        for (const participant of participantRegistry.values()) {
            if (participant.audioTrack && typeof participant.audioTrack.getVolumeLevel === 'function') return true;
        }
        return false;
    }

    function startActiveSpeakerDetection() {
        if (config.agoraMode !== 'interactive' || !client || !isAgoraSessionJoined || client.connectionState !== 'CONNECTED') return;
        if (!hasInspectableAudioTracks()) return;
        if (activeSpeakerSampleTimer) return;
        activeSpeakerSampleTimer = setInterval(sampleActiveSpeakerLevels, ACTIVE_SPEAKER_SAMPLE_INTERVAL);
        sampleActiveSpeakerLevels();
        window.vh360Log('Agora: Active speaker sampling started');
    }

    function normalizeVolumeLevel(value) {
        const number = Number(value);
        if (!Number.isFinite(number) || number < 0) return 0;
        return Math.max(0, Math.min(1, number));
    }

    function sampleParticipantAudioLevel(uid, audioTrack) {
        if (!audioTrack || typeof audioTrack.getVolumeLevel !== 'function') return 0;
        try { return normalizeVolumeLevel(audioTrack.getVolumeLevel()); }
        catch (error) { return 0; }
    }

    function getAudioLevelSamples() {
        const samples = [];
        if (localTracks.audioTrack && currentUserUID) samples.push({ uid: currentUserUID, level: sampleParticipantAudioLevel(currentUserUID, localTracks.audioTrack) });
        participantRegistry.forEach((participant) => {
            if (participant && !participant.isLocal && participant.audioTrack) {
                samples.push({ uid: participant.uid, level: sampleParticipantAudioLevel(participant.uid, participant.audioTrack) });
            }
        });
        return samples;
    }

    function getPreferredFallbackParticipant() {
        if (originalHostUID && participantRegistry.has(normalizeParticipantUid(originalHostUID))) return originalHostUID;
        if (currentUserUID && participantRegistry.has(normalizeParticipantUid(currentUserUID))) return currentUserUID;
        const first = participantRegistry.values().next();
        return first && first.value ? first.value.uid : null;
    }

    function sampleActiveSpeakerLevels() {
        if (config.agoraMode !== 'interactive' || !client || client.connectionState !== 'CONNECTED') return;
        const samples = getAudioLevelSamples();
        if (!samples.length) { stopActiveSpeakerDetection(); return; }
        let strongest = null;
        samples.forEach(({ uid, level }) => {
            const key = normalizeParticipantUid(uid);
            const previous = activeSpeakerLevels.get(key) || 0;
            const smoothed = (previous * (1 - ACTIVE_SPEAKER_EMA_ALPHA)) + (level * ACTIVE_SPEAKER_EMA_ALPHA);
            activeSpeakerLevels.set(key, smoothed);
            updateVolumeIndicator(uid, smoothed);
            if (smoothed >= ACTIVE_SPEAKER_ENTER_THRESHOLD && (!strongest || smoothed > strongest.level)) strongest = { uid, key, level: smoothed };
        });
        const now = Date.now();
        const currentKey = activeSpeakerUid ? normalizeParticipantUid(activeSpeakerUid) : null;
        const currentLevel = currentKey ? (activeSpeakerLevels.get(currentKey) || 0) : 0;
        if (currentKey && currentLevel >= ACTIVE_SPEAKER_EXIT_THRESHOLD) {
            if (activeSpeakerDebounceTimeout) { clearTimeout(activeSpeakerDebounceTimeout); activeSpeakerDebounceTimeout = null; }
            activeSpeakerReleaseSince = 0;
        }
        if (!strongest) {
            activeSpeakerCandidates.clear();
            if (activeSpeakerUid) {
                if (!activeSpeakerReleaseSince) activeSpeakerReleaseSince = now;
                if (now - activeSpeakerReleaseSince >= ACTIVE_SPEAKER_RELEASE_MS) setActiveSpeaker(null);
            }
            return;
        }
        activeSpeakerReleaseSince = 0;
        if (currentKey && strongest.key === currentKey) {
            activeSpeakerCandidates.clear();
            return;
        }
        if (currentKey && strongest.level < currentLevel + ACTIVE_SPEAKER_DOMINANCE_MARGIN) return;
        if (now - lastActiveSpeakerChange < ACTIVE_SPEAKER_SWITCH_COOLDOWN_MS) return;
        if (!shouldSwitchToSpeaker(strongest.uid)) return;
        const candidateSince = activeSpeakerCandidates.get(strongest.key) || now;
        activeSpeakerCandidates.set(strongest.key, candidateSince);
        activeSpeakerCandidates.forEach((_, key) => { if (key !== strongest.key) activeSpeakerCandidates.delete(key); });
        if (now - candidateSince >= ACTIVE_SPEAKER_ATTACK_MS) {
            setActiveSpeaker(strongest.uid);
            requestFeaturedStreamTypes();
            activeSpeakerCandidates.clear();
        }
    }

    function handleVolumeIndication(volumes) {
        if (!isVolumenIndicationEnabled || config.agoraMode !== 'interactive' || !Array.isArray(volumes)) return;
        let fallbackSpeaker = null;
        volumes.forEach((volumeInfo) => {
            const normalized = normalizeVolumeLevel(Number(volumeInfo.level) / 100);
            updateVolumeIndicator(volumeInfo.uid, normalized);
            recordParticipantDiagnostic(volumeInfo.uid, { volumeIndicator: normalized });
            if (!activeSpeakerSampleTimer && normalized >= ACTIVE_SPEAKER_ENTER_THRESHOLD && (!fallbackSpeaker || normalized > fallbackSpeaker.level)) {
                fallbackSpeaker = { uid: volumeInfo.uid, level: normalized };
            }
        });
        if (fallbackSpeaker && fallbackSpeaker.uid && shouldSwitchToSpeaker(fallbackSpeaker.uid)) {
            setActiveSpeaker(fallbackSpeaker.uid);
        }
    }

    function setActiveSpeaker(uid) {
        // Guard against race conditions during view transitions
        const layoutManager = window.vh360LayoutManager;
        const allowSpeakerChange = layoutManager
            ? (typeof layoutManager.shouldAllowSpeakerSwitch === 'function'
                ? layoutManager.shouldAllowSpeakerSwitch()
                : layoutManager.shouldAllowVideoMovement())
            : true;

        if (!allowSpeakerChange) {
            window.vh360Log('[VH360 Debug] Blocking speaker change during view transition:', uid);
            return;
        }

        speakerCandidateUid = null;
        speakerCandidateSince = 0;

        // Clear any pending debounce timeout
        if (activeSpeakerDebounceTimeout) {
            clearTimeout(activeSpeakerDebounceTimeout);
            activeSpeakerDebounceTimeout = null;
        }

        // Remove active speaker styling from previous speaker
        if (activeSpeakerUid) {
            updateActiveSpeakerVisuals(activeSpeakerUid, false);
        }

        // Set new active speaker
        activeSpeakerUid = uid;
        lastActiveSpeakerChange = Date.now();

        if (uid) {
            window.vh360Log("Agora: Active speaker changed to UID:", uid);
            window.vh360Log('[VH360 Debug] Setting active speaker:', uid);

            // Add active speaker styling to new speaker
            updateActiveSpeakerVisuals(uid, true);

            // Switch main video to active speaker (unless Focus View has a pinned participant).
            if (shouldSwitchToSpeaker(uid)) {
                switchMainVideoToSpeaker(uid);
            }
        } else {
            window.vh360Log("Agora: No active speaker");
            window.vh360Log('[VH360 Debug] Clearing active speaker');

            // When active speaker is cleared (silence), return to original host view in interactive mode
            const mainPlayer = document.getElementById("vh360-agora-local-player");
            const currentMainElement = mainPlayer ? mainPlayer.querySelector('[id^="player-"]') : null;

            // In interactive mode, non-original hosts should always return to viewing the original host during silence
            if (config.agoraMode === 'interactive' && !isOriginalHost && originalHostUID && remoteUsers[originalHostUID]) {
                const isOriginalHostInMain = currentMainElement && currentMainElement.id === `player-${originalHostUID}`;

                if (!isOriginalHostInMain) {
                    window.vh360Log("Agora: Silence detected - returning to original host view");
                    switchMainVideoToSpeaker(originalHostUID);
                }
            }
        }

        requestFeaturedStreamTypes();
    }

    function shouldSwitchToSpeaker(uid) {
        const layoutManager = window.vh360LayoutManager || window.viewLayoutManager || window.vh360?.viewLayoutManager;
        const pinnedUid = getPinnedParticipantUidFromLayout();
        if (layoutManager && layoutManager.currentView === 'focus' && pinnedUid) {
            window.vh360Log('[VH360 Debug] Focus pinned participant retained; active speaker did not steal featured area:', { pinnedUid, activeSpeakerUid: uid });
            refreshFeaturedParticipantTiles();
            return false;
        }
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        return !!participant;
    }

    function switchMainVideoToSpeaker(uid) {
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        if (!participant) {
            window.vh360Warn("Agora: Speaker participant not found for UID:", uid);
            return;
        }

        window.vh360Log('[VH360 Debug] Featuring speaker without moving video DOM:', uid);
        refreshFeaturedParticipantTiles();
    }

    function updateVolumeIndicator(uid, level) {
        setParticipantSpeakingState(uid, level);
    }

    function updateActiveSpeakerVisuals(uid, isActive) {
        const participant = getOrCreateParticipant(uid);
        if (!participant) return;

        participant.isActiveSpeaker = !!isActive;
        refreshFeaturedParticipantTiles();

        const playerElement = participant.tileElement;
        if (!playerElement) return;

        let speakerBadge = playerElement.querySelector('.active-speaker-badge');
        if (isActive && playerElement.classList.contains('camera-off')) {
            if (!speakerBadge) {
                speakerBadge = document.createElement('div');
                speakerBadge.className = 'active-speaker-badge';
                speakerBadge.textContent = 'SPEAKING (AUDIO)';
                playerElement.appendChild(speakerBadge);
            }
            speakerBadge.style.display = 'block';
        } else if (speakerBadge) {
            speakerBadge.style.display = 'none';
        }
    }

    // Data stream for audience/host requests
    let dataStream = null;

    // Fix for frontend.js data stream error
    async function initDataStream() {
        // Check for client object
        if (!client) {
            return;
        }

        // Check for SDK version compatibility
        if (typeof client.createDataStream === 'function') {
            try {
                const createdDataStream = await client.createDataStream({ reliable: true, ordered: true });
                dataStream = createdDataStream;
                window.vh360Log('Agora: Data stream created successfully');
            } catch (err) {
                window.vh360Log('Agora: Data stream not available - using polling for moderation');
                dataStream = null;
            }
        } else if (typeof client.createDataChannel === 'function') {
            try {
                const dataChannel = client.createDataChannel();
                dataStream = {
                    send: function(message) {
                        dataChannel.send(message);
                    }
                };
                window.vh360Log('Agora: Data channel created successfully');
            } catch (err) {
                window.vh360Log('Agora: Data channel not available - using polling for moderation');
                dataStream = null;
            }
        } else {
            window.vh360Log('Agora: Data stream not supported by SDK - using polling for moderation');
            dataStream = null;
        }
    }

    // -- Controls UI handling --

    function setAgoraControlButtonContent(button, state) {
        if (!button || !state) return;
        button.classList.add('vh360-agora-control-btn', 'vh360-agora-media-control-btn');
        button.classList.toggle('is-audio-on', state.type === 'audio' && !state.isMuted);
        button.classList.toggle('is-audio-muted', state.type === 'audio' && state.isMuted);
        button.classList.toggle('is-camera-on', state.type === 'video' && !state.isMuted);
        button.classList.toggle('is-camera-off', state.type === 'video' && state.isMuted);
        button.setAttribute('aria-label', state.accessibleLabel);
        button.setAttribute('title', state.accessibleLabel);
        button.innerHTML = `<span class="vh360-agora-control-icon" aria-hidden="true">${state.icon}</span><span class="vh360-agora-control-label">${state.label}</span>`;
    }

    function updateAgoraControlButtonStates() {
        setAgoraControlButtonContent(muteAudioBtn, isAudioMuted ? {
            type: 'audio',
            isMuted: true,
            accessibleLabel: 'Unmute microphone',
            icon: '🔇',
            label: 'Unmute'
        } : {
            type: 'audio',
            isMuted: false,
            accessibleLabel: 'Mute microphone',
            icon: '🎤',
            label: 'Mute'
        });

        if (participantAudioBtn) {
            participantAudioBtn.classList.add('vh360-agora-control-btn', 'vh360-agora-media-control-btn', 'vh360-agora-participant-audio-btn');
            participantAudioBtn.classList.toggle('is-participant-audio-on', participantAudioMonitoringEnabled);
            participantAudioBtn.classList.toggle('is-participant-audio-muted', !participantAudioMonitoringEnabled);
            participantAudioBtn.setAttribute('aria-pressed', participantAudioMonitoringEnabled ? 'true' : 'false');
            const participantAudioLabel = participantAudioMonitoringEnabled ? 'Participant Audio: On' : 'Participant Audio: Muted';
            participantAudioBtn.setAttribute('aria-label', participantAudioLabel);
            participantAudioBtn.setAttribute('title', participantAudioLabel);
            participantAudioBtn.innerHTML = `<span class="vh360-agora-control-icon" aria-hidden="true">${participantAudioMonitoringEnabled ? '🔊' : '🔈'}</span><span class="vh360-agora-control-label">${participantAudioLabel}</span>`;
        }

        setAgoraControlButtonContent(muteVideoBtn, isVideoMuted ? {
            type: 'video',
            isMuted: true,
            accessibleLabel: 'Turn camera on',
            icon: '📹',
            label: 'Turn On'
        } : {
            type: 'video',
            isMuted: false,
            accessibleLabel: 'Turn camera off',
            icon: '📹',
            label: 'Camera'
        });
    }

    function updateControlsVisibility() {
        if (!controlsContainer) return;

        // Check if user should see controls at all
        const shouldShow = shouldShowControlsForUser();
        if (!shouldShow) {
            controlsContainer.style.display = 'none';
            return;
        }

        controlsContainer.style.display = 'flex';
        if (muteAudioBtn) muteAudioBtn.style.display = 'none';
        if (participantAudioBtn) participantAudioBtn.style.display = 'none';
        if (muteVideoBtn) muteVideoBtn.style.display = 'none';
        if (joinAsPresenterBtn) joinAsPresenterBtn.style.display = 'none';

        // Show controls for hosts OR appointment participants with publish permission
        // Note: For appointment clients, canPublish is true only when:
        // 1. They are the booked client (validated server-side)
        // 2. The session status is 'active' (professional has started)
        // 3. They have been promoted to 'host' role in handleJoinLivestream()
        const isAppointmentPublisher = config.appointment && config.appointment.isAppointment && config.appointment.canPublish;

        if (currentRole === 'host' || isAppointmentPublisher) {
            if (muteAudioBtn) {
                muteAudioBtn.style.display = 'inline-flex';
            }
            if (muteVideoBtn) {
                muteVideoBtn.style.display = 'inline-flex';
            }
        } else if (currentRole === 'audience') {
            if (config.agoraMode === 'interactive' && joinAsPresenterBtn && security.is_logged_in && !isOriginalHost) {
                joinAsPresenterBtn.style.display = 'inline-block';
            }
        }

        if (participantAudioBtn) {
            participantAudioBtn.style.display = shouldShowParticipantAudioMonitorControl() ? 'inline-flex' : 'none';
        }

        if (leaveBtn) leaveBtn.style.display = 'inline-block';
        if (endStreamBtn) endStreamBtn.style.display = shouldShowEndStreamButton() ? 'inline-block' : 'none';
        updateAgoraControlButtonStates();

        // Handle moderation button visibility
        const moderationBtn = document.getElementById('vh360-moderation-panel-btn');
        if (moderationBtn) {
            moderationBtn.style.display = shouldShowModerationButton() ? 'inline-block' : 'none';
        }
    }

    if (participantAudioBtn) {
        participantAudioBtn.addEventListener('click', () => {
            if (!shouldShowParticipantAudioMonitorControl()) return;
            participantAudioMonitoringEnabled = !participantAudioMonitoringEnabled;
            updateAgoraControlButtonStates();
            applyParticipantAudioMonitoringState();
        });
    }

    // -- Data Stream Messaging --
    async function sendDataStreamMessage(data) {
        try {
            // Enhanced validation for moderation messages
            if (data.type === 'moderation_action') {
                window.vh360Log('Agora: Sending moderation message');

                // Validate required fields
                if (!data.action || (!data.target_uid && !data.target_user_id)) {
                    window.vh360Error('Agora: Invalid moderation data - missing required fields:', data);
                    return;
                }
            }

            if (client && client.connectionState === 'CONNECTED') {
                const message = JSON.stringify({
                    ...data,
                    timestamp: Date.now(),
                    fromUserId: security.user_id,
                    fromDisplayName: security.display_name
                });

                if (dataStream !== null) {
                    try {
                        client.sendStreamMessage(dataStream, message);
                        window.vh360Log("Agora: Data stream message sent successfully", data.type);

                        // For moderation actions, also trigger local event as backup
                        if (data.type === 'moderation_action') {
                            window.dispatchEvent(new CustomEvent('agoraDataMessage', { detail: JSON.parse(message) }));
                            window.dispatchEvent(new CustomEvent('vh360:agora-data-message', { detail: JSON.parse(message) }));
                        }
                    } catch (streamError) {
                        window.vh360Warn("Agora: Failed to send data stream message:", streamError);
                        // Fallback to local event when stream message fails
                        window.dispatchEvent(new CustomEvent('agoraDataMessage', { detail: JSON.parse(message) }));
                            window.dispatchEvent(new CustomEvent('vh360:agora-data-message', { detail: JSON.parse(message) }));
                    }
                } else {
                    // Data stream not available - polling will handle moderation
                    window.dispatchEvent(new CustomEvent('agoraDataMessage', { detail: JSON.parse(message) }));
                            window.dispatchEvent(new CustomEvent('vh360:agora-data-message', { detail: JSON.parse(message) }));
                }
            } else {
                window.vh360Warn("Agora: Client not connected, cannot send data stream message");
                // Graceful degradation - still trigger local handling for non-critical messages
                if (data.type !== 'user_info') {
                    window.dispatchEvent(new CustomEvent('agoraDataMessage', { detail: data }));
                    window.dispatchEvent(new CustomEvent('vh360:agora-data-message', { detail: data }));
                }
            }
        } catch (error) {
            window.vh360Error('Agora: Failed to send data stream message', error);
        }
    }

    // Make sendDataStreamMessage globally available for moderation system
    window.sendDataStreamMessage = sendDataStreamMessage;
    // Make handleModerationAction globally available for fallback scenarios
    window.handleModerationAction = handleModerationAction;

    client.on("stream-message", (uid, stream, message) => {
        try {
            window.vh360Log('Agora: Raw data stream message received from UID:', uid);
            const data = JSON.parse(message);
            window.vh360Log('Agora: Parsed data stream message:', data);

            // Validate critical message fields
            if (data.type === 'moderation_action') {
                window.vh360Log('Agora: Processing critical moderation message:', {
                    type: data.type,
                    action: data.action,
                    target_uid: data.target_uid,
                    target_user_id: data.target_user_id,
                    currentUserUID: currentUserUID,
                    myWordPressID: security.user_id
                });
            }

            window.dispatchEvent(new CustomEvent('vh360:agora-data-message', { detail: data }));
            handleDataMessage(data);
        } catch (error) {
            window.vh360Error('Agora: Failed to parse data stream message', error);
            window.vh360Error('Agora: Raw message that failed to parse:', message);

            // For critical moderation messages, still try to trigger a check
            if (message.includes('moderation_action') && window.triggerImmediateModerationCheck) {
                window.vh360Log('Agora: Detected moderation message despite parse error, triggering check');
                setTimeout(() => window.triggerImmediateModerationCheck(), 500);
            }
        }
    });
    window.addEventListener('agoraDataMessage', (event) => {
        const data = event.detail;
        window.vh360Log('Agora: Received local data event:', data);

        // Enhanced logging for moderation actions
        if (data.type === 'moderation_action') {
            window.vh360Log('Agora: ⚡ CRITICAL: Received moderation action via local event:', data);
        }

        handleDataMessage(data);

        // Additional safety: For ANY data message activity, schedule a moderation check for all users
        // This catches cases where messages are received but processed incorrectly
        if (window.triggerImmediateModerationCheck) {
            setTimeout(() => {
                window.vh360Log('Agora: Running safety moderation check after local data event');
                window.triggerImmediateModerationCheck();
            }, 1500);
        }
    });

    // Server-authoritative participant identity resolution.
    let identityBatchTimer = null;
    const queuedIdentityUids = new Set();

    function applyVerifiedIdentity(uid, identity) {
        const key = normalizeParticipantUid(uid);
        if (!key || !identity || !identity.display_name) return null;
        const verified = {
            displayName: identity.display_name,
            wordpressUserId: identity.wordpress_user_id || null,
            avatarUrl: identity.avatar_url || '',
            isGuest: !!identity.is_guest,
            isStudioHost: !!identity.is_studio_host,
            isOriginalHost: !!identity.is_original_host || !!identity.is_studio_host
        };
        verifiedIdentityCache.set(key, verified);
        if (verified.isOriginalHost && !originalHostUID) {
            originalHostUID = key;
        }
        if (verified.isOriginalHost && !isOriginalHost && config.agoraMode === 'interactive') {
            setTimeout(checkAndSetInitialHostView, 0);
        }
        if (remoteUsers[key]) {
            remoteUsers[key].displayName = verified.displayName;
            remoteUsers[key].wordpressUserId = verified.wordpressUserId || remoteUsers[key].wordpressUserId;
            remoteUsers[key].isOriginalHost = verified.isOriginalHost;
        }
        const participant = participantRegistry.get(key);
        if (participant) {
            participant.displayName = verified.displayName;
            participant.wordpressUserId = verified.wordpressUserId || participant.wordpressUserId;
            participant.avatarUrl = verified.avatarUrl || participant.avatarUrl;
            participant.isOriginalHost = participant.isOriginalHost || verified.isOriginalHost;
            updateParticipantTile(participant);
        }
        updateRemoteUserDisplayName(key, verified.displayName);
        return verified;
    }

    async function lookupParticipantIdentities(uids) {
        const keys = Array.from(new Set((uids || []).map(normalizeParticipantUid).filter(Boolean)))
            .filter((key) => !verifiedIdentityCache.has(key));
        if (!keys.length) return {};

        const allIdentities = {};
        for (let index = 0; index < keys.length; index += 50) {
            const chunk = keys.slice(index, index + 50);
            const formData = new FormData();
            formData.append('action', 'vh360_lookup_agora_participant_identities');
            formData.append('nonce', vh360Data.agoraIdentityNonce || vh360Data.agoraTokenNonce);
            formData.append('post_id', vh360Data.postId);
            formData.append('channel_name', config.channelName);
            chunk.forEach((key) => formData.append('uids[]', key));

            const response = await fetch(vh360Data.ajaxUrl, { method: 'POST', body: formData });
            const data = await response.json();
            const identities = data && data.success && data.data && data.data.identities ? data.data.identities : {};
            Object.assign(allIdentities, identities);
            Object.keys(identities).forEach((key) => applyVerifiedIdentity(key, identities[key]));
        }
        return allIdentities;
    }

    function flushIdentityBatch() {
        const uids = Array.from(queuedIdentityUids);
        queuedIdentityUids.clear();
        identityBatchTimer = null;
        if (!uids.length) return;

        for (let index = 0; index < uids.length; index += 50) {
            const chunk = uids.slice(index, index + 50);
            const request = lookupParticipantIdentities(chunk).catch((error) => {
                window.vh360Error('VideoHub360: Failed to batch lookup verified identities', error);
                return {};
            }).finally(() => {
                chunk.forEach((uid) => pendingIdentityRequests.delete(uid));
            });
            chunk.forEach((uid) => pendingIdentityRequests.set(uid, request.then((identities) => {
                const identity = identities[uid];
                return identity && identity.display_name ? identity.display_name : null;
            })));
        }
    }

    async function lookupDisplayNameByUID(uid) {
        const key = normalizeParticipantUid(uid);
        if (!key) return null;
        if (verifiedIdentityCache.has(key)) return verifiedIdentityCache.get(key).displayName;
        if (isStudioHostUid(key) && config.studioHostDisplayName) {
            const studioIdentity = applyVerifiedIdentity(key, {
                uid: key,
                display_name: config.studioHostDisplayName,
                wordpress_user_id: config.studioHostUserId || null,
                avatar_url: config.studioHostAvatarUrl || config.studioHostAvatar || '',
                is_studio_host: true,
                is_original_host: true
            });
            return studioIdentity ? studioIdentity.displayName : null;
        }
        if (pendingIdentityRequests.has(key)) return pendingIdentityRequests.get(key);

        queuedIdentityUids.add(key);
        const request = new Promise((resolve) => {
            const poll = () => {
                if (!pendingIdentityRequests.has(key) && verifiedIdentityCache.has(key)) {
                    resolve(verifiedIdentityCache.get(key).displayName);
                } else if (!pendingIdentityRequests.has(key) && !queuedIdentityUids.has(key)) {
                    resolve(null);
                } else {
                    setTimeout(poll, 25);
                }
            };
            setTimeout(poll, 25);
        });
        pendingIdentityRequests.set(key, request);
        if (!identityBatchTimer) {
            identityBatchTimer = setTimeout(flushIdentityBatch, 25);
        }
        return request;
    }

    async function resolveAndUpdateDisplayName(uid) {
        const key = normalizeParticipantUid(uid);
        if (!key) return;
        const existing = remoteUsers[key] && remoteUsers[key].displayName;
        if (existing && existing !== 'Participant' && !existing.startsWith('User ')) return;
        const displayName = await lookupDisplayNameByUID(key);
        if (displayName) {
            remoteUsers[key] = { ...(remoteUsers[key] || {}), displayName };
            updateRemoteUserDisplayName(key, displayName);
            window.vh360Log('VideoHub360: Resolved verified display name for UID', key, ':', displayName);
        }
    }

    function resolveExistingRemoteIdentities() {
        const uids = Object.keys(remoteUsers || {});
        if (uids.length) lookupParticipantIdentities(uids);
    }

    function updateRemoteUserDisplayName(uid, displayName) {
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        if (participant) {
            participant.displayName = displayName;
            updateParticipantTile(participant);
        }
        const playerElement = document.getElementById(`player-${uid}`);
        if (playerElement) {
            const userInfo = playerElement.querySelector('.vh360-user-info, .vh360-video-name-overlay');
            if (userInfo) {
                userInfo.textContent = displayName;
            }
        }
    }

    /**
     * Switch to original host view when they become available
     * This handles cases where original host joins after other participants
     */
    function checkAndSetInitialHostView() {
        // Only applies in interactive mode for non-host participants
        if (isOriginalHost || config.agoraMode !== 'interactive') {
            return;
        }

        // Check if original host UID is available and they have published video
        if (!originalHostUID || !remoteUsers[originalHostUID]) {
            return;
        }

        const mainPlayer = document.getElementById("vh360-agora-local-player");
        if (!mainPlayer) {
            return;
        }

        // Check if original host is already in main view - if so, we're done
        const currentMainElement = mainPlayer.querySelector('[id^="player-"]');
        const hasOriginalHostInMain = currentMainElement && currentMainElement.id === `player-${originalHostUID}`;
        if (hasOriginalHostInMain) {
            return;
        }

        // Original host is available but not in main view - switch to them
        const originalHostElement = document.getElementById(`player-${originalHostUID}`);
        if (originalHostElement && originalHostElement.parentElement) {
            window.vh360Log('VideoHub360: Original host now available - switching to main view');
            switchMainVideoToSpeaker(originalHostUID);
        }
    }
    function handleDataMessage(data) {
        // Trigger immediate moderation check whenever ANY data stream activity is detected
        // This ensures all users (logged-in and guests) quickly detect if they've been moderated
        if (window.triggerImmediateModerationCheck) {
            window.vh360Log('Agora: Data stream activity detected, triggering immediate moderation check');
            // Delay slightly to allow current message processing to complete first
            setTimeout(() => {
                window.triggerImmediateModerationCheck();
            }, 200);
        }

        if (data.type === 'user_info') {
            return;
        }

        if (data.type === 'moderation_action') {
            window.vh360Log('Agora: ⚡ CRITICAL: Received moderation_action data:', data);
            handleModerationAction(data);

            // Trigger immediate checks for all users
            if (window.triggerImmediateModerationCheck) {
                setTimeout(() => window.triggerImmediateModerationCheck(), 100);
                setTimeout(() => window.triggerImmediateModerationCheck(), 1000);
                setTimeout(() => window.triggerImmediateModerationCheck(), 3000);
                setTimeout(() => window.triggerImmediateModerationCheck(), 7000);
            }
        }
    }

    function unlockParticipantJoinSound() {
        if (participantJoinSoundUnlocked) {
            return;
        }

        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                return;
            }

            participantJoinAudioContext = participantJoinAudioContext || new AudioContextClass();

            if (participantJoinAudioContext.state === 'suspended') {
                participantJoinAudioContext.resume().catch(() => {});
            }

            participantJoinSoundUnlocked = true;
        } catch (error) {
            window.vh360Warn('VideoHub360: Unable to unlock participant join sound:', error);
        }
    }

    function playParticipantJoinedSound(uid) {
        if (config.agoraMode !== 'interactive') {
            return;
        }

        if (isBeingModerated) {
            return;
        }

        if (uid && currentUserUID && String(uid) === String(currentUserUID)) {
            return;
        }

        const now = Date.now();
        const lastPlayedAt = participantJoinSoundThrottle.get(uid) || 0;

        // Prevent duplicate sounds for the same user during reconnect or event bursts.
        if (now - lastPlayedAt < 5000) {
            return;
        }

        participantJoinSoundThrottle.set(uid, now);

        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                return;
            }

            participantJoinAudioContext = participantJoinAudioContext || new AudioContextClass();

            if (participantJoinAudioContext.state === 'suspended') {
                participantJoinAudioContext.resume().catch(() => {});
                return;
            }

            const ctx = participantJoinAudioContext;
            const startTime = ctx.currentTime;

            const gain = ctx.createGain();
            gain.gain.setValueAtTime(0.0001, startTime);
            gain.gain.exponentialRampToValueAtTime(0.08, startTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, startTime + 0.45);
            gain.connect(ctx.destination);

            const firstTone = ctx.createOscillator();
            firstTone.type = 'sine';
            firstTone.frequency.setValueAtTime(660, startTime);
            firstTone.connect(gain);
            firstTone.start(startTime);
            firstTone.stop(startTime + 0.18);

            const secondTone = ctx.createOscillator();
            secondTone.type = 'sine';
            secondTone.frequency.setValueAtTime(880, startTime + 0.16);
            secondTone.connect(gain);
            secondTone.start(startTime + 0.16);
            secondTone.stop(startTime + 0.42);
        } catch (error) {
            window.vh360Warn('VideoHub360: Failed to play participant join sound:', error);
        }
    }

    document.addEventListener('click', unlockParticipantJoinSound, { once: true });
    document.addEventListener('touchstart', unlockParticipantJoinSound, { once: true });

    // -- Remote User Events --
    client.on("user-joined", (user) => {
        window.vh360Log('Agora: Remote user joined:', user && user.uid);

        if (!user || !user.uid) {
            return;
        }

        if (config.agoraMode !== 'interactive') {
            return;
        }

        resolveAndUpdateDisplayName(user.uid);
        playParticipantJoinedSound(user.uid);
    });

    let remoteSubscriptionSession = 0;

    function remoteSubscriptionKey(uid, mediaType) {
        return `${normalizeParticipantUid(uid)}:${mediaType}`;
    }

    function beginAgoraSessionReplacement(reason) {
        isAgoraSessionReplacementInProgress = true;
        isAgoraSessionJoined = false;
        resetRemoteSubscriptionSession(reason);
    }

    function completeAgoraSessionReplacement(joined) {
        isAgoraSessionReplacementInProgress = false;
        isAgoraSessionJoined = !!joined;
    }

    function currentRemotePublicationGeneration(uid, mediaType) {
        return remotePublicationGenerations.get(remoteSubscriptionKey(uid, mediaType)) || 0;
    }

    function advanceRemotePublicationGeneration(uid, mediaType) {
        const key = remoteSubscriptionKey(uid, mediaType);
        const generation = currentRemotePublicationGeneration(uid, mediaType) + 1;
        remotePublicationGenerations.set(key, generation);
        if (mediaType === 'video') clearRemoteStreamSelectionState(uid);
        return generation;
    }

    function resetRemoteSubscriptionSession(reason) {
        remoteSubscriptionSession += 1;
        remoteSubscriptionStates.forEach((state) => {
            if (state.timer) clearTimeout(state.timer);
        });
        remoteSubscriptionStates.clear();
        remotePublicationGenerations.clear();
        clearRemoteStreamSelectionState();
        if (remoteReconciliationTimer) clearTimeout(remoteReconciliationTimer);
        remoteReconciliationTimer = null;
        window.vh360Log('Agora: Remote subscription session reset', { reason, session: remoteSubscriptionSession });
    }

    function clearRemoteSubscription(uid, mediaType, expectedState = null) {
        const key = remoteSubscriptionKey(uid, mediaType);
        const state = remoteSubscriptionStates.get(key);
        if (!state || (expectedState && state !== expectedState)) return;
        if (state.timer) clearTimeout(state.timer);
        remoteSubscriptionStates.delete(key);
    }

    function clearRemoteSubscriptionsForUser(uid) {
        ['audio', 'video'].forEach((mediaType) => clearRemoteSubscription(uid, mediaType));
    }

    function getCurrentRemoteUser(uid) {
        return (client && client.remoteUsers || []).find((user) => String(user.uid) === String(uid));
    }

    function isCurrentRemoteSubscriptionState(key, state) {
        return state.session === remoteSubscriptionSession && state.publicationGeneration === currentRemotePublicationGeneration(state.uid, state.mediaType) && remoteSubscriptionStates.get(key) === state;
    }

    function isRemotePublicationCurrent(user, mediaType, session) {
        if (session !== undefined && session !== remoteSubscriptionSession) return false;
        if (!client || !user || !user.uid || !['audio', 'video'].includes(mediaType) || String(user.uid) === String(currentUserUID)) return false;
        const current = getCurrentRemoteUser(user.uid);
        return !!current && (mediaType === 'video' ? !!current.hasVideo : !!current.hasAudio);
    }

    function isRemotePublicationReady(user, mediaType, state) {
        if (!state || !['ready', 'attaching', 'sdk-subscribed'].includes(state.status)) return false;
        const participant = participantRegistry.get(normalizeParticipantUid(user.uid));
        if (mediaType === 'audio') {
            return !!(user.audioTrack && participant && participant.audioTrack === user.audioTrack && state.track === user.audioTrack);
        }
        if (!user || !user.hasVideo || !user.videoTrack || !participant) return false;
        if (participant.videoTrack !== user.videoTrack || state.track !== user.videoTrack) return false;
        return ['subscribed', 'attaching', 'waiting-for-frame', 'playing', 'reconnecting', 'fallback'].includes(participant.videoPlaybackState || 'subscribed');
    }

    function scheduleRemoteReconciliation(reason, delay = 0) {
        if (remoteReconciliationTimer) clearTimeout(remoteReconciliationTimer);
        const session = remoteSubscriptionSession;
        remoteReconciliationTimer = setTimeout(() => {
            remoteReconciliationTimer = null;
            if (session === remoteSubscriptionSession) reconcileRemoteSubscriptions(reason);
        }, delay);
    }

    function scheduleRemoteSubscriptionRetry(user, mediaType, state, error) {
        if (!isCurrentRemoteSubscriptionState(remoteSubscriptionKey(user.uid, mediaType), state) || !isRemotePublicationCurrent(user, mediaType, state.session)) return;
        if (state.attempts >= REMOTE_SUBSCRIPTION_MAX_ATTEMPTS) {
            state.status = 'failed';
            state.lastError = error;
            remoteSubscriptionStates.set(remoteSubscriptionKey(user.uid, mediaType), state);
            window.vh360Warn('Agora: Remote subscription attempts exhausted', { uid: user.uid, mediaType, attempt: state.attempts, error });
            return;
        }
        const delay = Math.min(4000, 500 * Math.pow(2, Math.max(0, state.attempts - 1))) + Math.floor(Math.random() * 150);
        state.status = 'retry-waiting';
        state.lastError = error;
        state.timer = setTimeout(() => {
            state.timer = null;
            if (remoteSubscriptionStates.get(remoteSubscriptionKey(user.uid, mediaType)) !== state) return;
            const currentUser = getCurrentRemoteUser(user.uid);
            if (currentUser && isRemotePublicationCurrent(currentUser, mediaType, state.session)) subscribeToRemotePublication(currentUser, mediaType);
        }, delay);
        remoteSubscriptionStates.set(remoteSubscriptionKey(user.uid, mediaType), state);
        window.vh360Warn('Agora: Remote subscription retry scheduled', { uid: user.uid, mediaType, attempt: state.attempts, delay, error });
    }

    async function subscribeToRemotePublication(user, mediaType, options = {}) {
        if (isAgoraSessionReplacementInProgress || !isAgoraSessionJoined || !client || client.connectionState !== 'CONNECTED' || !isRemotePublicationCurrent(user, mediaType)) return;
        const key = remoteSubscriptionKey(user.uid, mediaType);
        const generation = currentRemotePublicationGeneration(user.uid, mediaType);
        let state = remoteSubscriptionStates.get(key) || {
            uid: user.uid, mediaType, attempts: 0, status: 'idle', timer: null,
            session: remoteSubscriptionSession, publicationGeneration: generation,
            sdkSubscribed: false, retryPhase: null, publicationTrack: null
        };
        if (state.session !== remoteSubscriptionSession || state.publicationGeneration !== generation) return;
        if (!remoteSubscriptionStates.has(key)) remoteSubscriptionStates.set(key, state);
        if (state.status === 'subscribing' || state.status === 'attaching') return;
        if (state.timer) { clearTimeout(state.timer); state.timer = null; }
        if (options.freshAttempt || state.status === 'failed') state.attempts = 0;

        const alreadySubscribed = state.sdkSubscribed === true;
        if (alreadySubscribed) {
            // The SDK subscription survived; count this bounded attachment repair separately.
            state.attempts += 1;
        }
        try {
            if (!alreadySubscribed) {
                state.status = 'subscribing';
                state.attempts += 1;
                remoteSubscriptionStates.set(key, state);
                await client.subscribe(user, mediaType);
                if (!isCurrentRemoteSubscriptionState(key, state) || !isRemotePublicationCurrent(user, mediaType, state.session)) return;
                state.sdkSubscribed = true;
                state.retryPhase = null;
                state.status = 'sdk-subscribed';
            }
            state.status = 'attaching';
            remoteSubscriptionStates.set(key, state);
            const attached = await attachSubscribedRemotePublication(user, mediaType);
            if (!isCurrentRemoteSubscriptionState(key, state) || !isRemotePublicationCurrent(user, mediaType, state.session)) return;
            if (!attached) throw new Error('Remote track attachment was not ready');
            state.status = 'ready';
            state.retryPhase = null;
            state.track = mediaType === 'video' ? user.videoTrack : user.audioTrack;
            state.publicationTrack = state.track;
            state.lastError = null;
            remoteSubscriptionStates.set(key, state);
            if (state.attempts > 1) window.vh360Log('Agora: Remote subscription recovered', { uid: user.uid, mediaType, attempt: state.attempts });
        } catch (error) {
            const message = (error && (error.code || error.message)) || 'unknown';
            state.retryPhase = state.sdkSubscribed ? 'attach' : 'subscribe';
            scheduleRemoteSubscriptionRetry(user, mediaType, state, message);
        }
    }

    function clearUnpublishedRemoteMedia(uid, mediaType) {
        clearRemoteSubscription(uid, mediaType);
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        if (!participant) return;
        if (mediaType === 'video') {
            clearRemoteStreamSelectionState(uid);
            if (participant.videoContainerElement) {
                if (window.videoElementManager) window.videoElementManager.unregisterTrackBinding(participant.videoContainerElement.id);
                participant.videoContainerElement.replaceChildren();
            }
            participant.videoTrack = null;
            participant.cameraOn = false;
            setParticipantVideoPlaybackState(participant, 'off', { reason: 'unpublished' });
        } else {
            participant.audioTrack = null;
            participant.audioOn = false;
            activeSpeakerLevels.delete(normalizeParticipantUid(uid));
            activeSpeakerCandidates.delete(normalizeParticipantUid(uid));
        }
        updateParticipantTile(participant);
    }

    function reconcileRemoteSubscriptions(reason) {
        if (!client || !currentUserUID || !isAgoraSessionJoined || client.connectionState !== 'CONNECTED') return;
        window.vh360Log('Agora: Remote subscription reconciliation started', { reason });
        const currentUsers = client.remoteUsers || [];
        const currentIds = new Set(currentUsers.map((user) => normalizeParticipantUid(user.uid)));
        const removeIfAbsent = (uid) => {
            if (normalizeParticipantUid(uid) !== normalizeParticipantUid(currentUserUID) && !currentIds.has(normalizeParticipantUid(uid))) {
                clearRemoteSubscriptionsForUser(uid);
                removeParticipantTile(uid);
                delete remoteUsers[uid];
            }
        };
        remoteSubscriptionStates.forEach((state, key) => removeIfAbsent(key.split(':')[0]));
        participantRegistry.forEach((participant, uid) => removeIfAbsent(uid));
        Object.keys(remoteUsers).forEach(removeIfAbsent);

        currentUsers.forEach((user) => {
            if (!user || String(user.uid) === String(currentUserUID)) return;
            ['video', 'audio'].forEach((mediaType) => {
                const published = mediaType === 'video' ? !!user.hasVideo : !!user.hasAudio;
                const state = remoteSubscriptionStates.get(remoteSubscriptionKey(user.uid, mediaType));
                if (!published) {
                    clearUnpublishedRemoteMedia(user.uid, mediaType);
                    return;
                }
                if (state && state.publicationGeneration !== currentRemotePublicationGeneration(user.uid, mediaType)) {
                    clearRemoteSubscription(user.uid, mediaType, state);
                }
                if (!isRemotePublicationReady(user, mediaType, remoteSubscriptionStates.get(remoteSubscriptionKey(user.uid, mediaType)))) {
                    window.vh360Log('Agora: Reconciliation repairing remote publication', { uid: user.uid, mediaType, reason });
                    subscribeToRemotePublication(user, mediaType, { freshAttempt: state && state.status === 'failed' });
                }
            });
        });
    }

    async function attachSubscribedRemotePublication(user, mediaType) {
        if (mediaType === "video") {
            window.vh360Log('[VH360 Public Live] Remote video received', {
                remoteUid: user && user.uid,
                mediaType: mediaType
            });
            window.vh360Log('Agora: public page received user-published video', {
                uid: user && user.uid,
                hasVideoTrack: !!(user && user.videoTrack)
            });
            const remoteVideoTrack = user.videoTrack;
            if (!remoteVideoTrack) {
                window.vh360Warn("Agora: No video track available for user:", user.uid);
                return false;
            }

            let initialDisplayName = 'Participant';
            let wordpressUserId = null;
            let isUserOriginalHost = user.uid === originalHostUID;
            const isStudioHost = isStudioHostUid(user.uid);

            if (isStudioHost) {
                initialDisplayName = config.studioHostDisplayName || initialDisplayName;
                wordpressUserId = config.studioHostUserId || null;
                isUserOriginalHost = false;
            }

            if (remoteUsers[user.uid]) {
                initialDisplayName = remoteUsers[user.uid].displayName || initialDisplayName;
                wordpressUserId = remoteUsers[user.uid].wordpressUserId || null;
            } else if (user.uid == currentUserUID) {
                initialDisplayName = security.display_name;
                wordpressUserId = security.user_id;
            }

            remoteUsers[user.uid] = {
                ...(remoteUsers[user.uid] || {}),
                ...user,
                displayName: initialDisplayName,
                wordpressUserId: wordpressUserId
            };

            const participant = getOrCreateParticipant(user.uid, {
                displayName: initialDisplayName,
                wordpressUserId: wordpressUserId,
                isOriginalHost: isUserOriginalHost
            });
            if (isStudioHost && participant) {
                participant.displayName = config.studioHostDisplayName || participant.displayName;
                participant.wordpressUserId = config.studioHostUserId || participant.wordpressUserId;
            }
            setParticipantVideoPlaybackState(participant, participant.videoTrack && participant.videoTrack !== remoteVideoTrack ? 'attaching' : 'subscribed');
            window.vh360Log('Agora: public page attaching subscribed video', {
                uid: user && user.uid,
                hasVideoTrack: !!remoteVideoTrack
            });
            const attached = attachParticipantVideo(participant, remoteVideoTrack, false);
            if (!attached) return false;
            configureRemoteFallback(user.uid);
            requestFeaturedStreamTypes();

            if ((isOriginalHost || config.canModerate) && user.uid !== security.user_id && participant.tileElement && !participant.tileElement.querySelector('.vh360-participant-menu-container')) {
                addParticipantModerationMenu(participant.tileElement, user.uid, participant.displayName);
            }

            if (initialDisplayName === 'Participant' || initialDisplayName.startsWith('User ')) {
                resolveAndUpdateDisplayName(user.uid);
            }
            refreshFeaturedParticipantTiles();
            return true;
        }
        if (mediaType === "audio") {
            if (!user.audioTrack || typeof user.audioTrack.play !== 'function') {
                window.vh360Warn("Agora: Invalid audio track for user:", user.uid);
                return false;
            }
            const isStudioHostAudio = isStudioHostUid(user.uid);
            let displayName = resolveParticipantDisplayName(user.uid);
            let wordpressUserId = resolveWordPressUserId(user.uid);
            remoteUsers[user.uid] = { ...(remoteUsers[user.uid] || {}), ...user, displayName, wordpressUserId };
            const participant = getOrCreateParticipant(user.uid, { displayName, wordpressUserId });
            participant.audioTrack = user.audioTrack || participant.audioTrack;
            participant.audioOn = !!participant.audioTrack;
            const shouldPlayAudioLocally = shouldPlayRemoteAudio(user.uid);
            if (shouldPlayAudioLocally) {
                try {
                    user.audioTrack.play();
                } catch (error) {
                    window.vh360Warn("Agora: Failed to play remote audio track:", { uid: user.uid, error });
                    return false;
                }
            } else {
                if (typeof user.audioTrack.stop === 'function') {
                    try { user.audioTrack.stop(); } catch (error) { window.vh360Warn('Agora: Failed to stop suppressed remote audio playback:', { uid: user.uid, error }); }
                }
                if (isStudioHostViewer && isStudioHostAudio) {
                    window.vh360Log('Agora: Suppressed Studio Program audio playback on Studio host viewer', { uid: user.uid });
                } else {
                    window.vh360Log('Agora: Suppressed participant audio playback locally', { uid: user.uid });
                }
            }

            // Initialize volume tracking for this user in interactive mode
            if (config.agoraMode === 'interactive' && isVolumenIndicationEnabled) {
                // Volume indication will automatically track this user's audio
                window.vh360Log("Agora: Audio track subscribed for UID:", user.uid, "- Volume tracking enabled");
            }

            updateParticipantTile(participant);
            startActiveSpeakerDetection();
            if (displayName === 'Participant' || displayName.startsWith('User ')) {
                resolveAndUpdateDisplayName(user.uid);
            }
            return true;
        }
        return false;
    }

    client.on("user-published", (user, mediaType) => {
        if (isBeingModerated || isAgoraSessionReplacementInProgress || !isAgoraSessionJoined || !client || client.connectionState !== 'CONNECTED') return;
        const currentUser = getCurrentRemoteUser(user && user.uid) || user;
        if (!currentUser || !currentUser.uid) return;
        const key = remoteSubscriptionKey(currentUser.uid, mediaType);
        const state = remoteSubscriptionStates.get(key);
        if (state) clearRemoteSubscription(currentUser.uid, mediaType, state);
        // Agora's publication event is authoritative; never infer its identity from an old track or DOM binding.
        advanceRemotePublicationGeneration(currentUser.uid, mediaType);
        subscribeToRemotePublication(currentUser, mediaType);
    });
    client.on("user-unpublished", (user, mediaType) => {
        clearRemoteSubscription(user.uid, mediaType);
        advanceRemotePublicationGeneration(user.uid, mediaType);
        if (mediaType === "video") {
            clearRemoteStreamSelectionState(user.uid);
            const participant = getOrCreateParticipant(user.uid);
            if (participant) {
                if (participant.videoContainerElement) {
                    if (window.videoElementManager) {
                        window.videoElementManager.unregisterTrackBinding(participant.videoContainerElement.id);
                    }
                    participant.videoContainerElement.replaceChildren();
                }
                participant.videoTrack = null;
                participant.cameraOn = false;
                setParticipantVideoPlaybackState(participant, 'off', { reason: 'user-unpublished' });
                updateParticipantTile(participant);
                window.vh360Log("Agora: User camera off, updating persistent tile for UID:", user.uid);
            }
        }

        // Handle audio unpublished - check for cleaning up active speaker
        if (mediaType === "audio") {
            window.vh360Log("Agora: User unpublished audio, UID:", user.uid);

            const participant = participantRegistry.get(normalizeParticipantUid(user.uid));
            if (participant) {
                participant.audioTrack = null;
                participant.audioOn = false;
                participant.isSpeaking = false;
                activeSpeakerLevels.delete(normalizeParticipantUid(user.uid));
                activeSpeakerCandidates.delete(normalizeParticipantUid(user.uid));
                updateParticipantTile(participant);
            }
            // Clean up active speaker if this user was the active speaker and now has no audio
            if (user.uid === activeSpeakerUid) {
                setActiveSpeaker(null);
                window.vh360Log("Agora: Active speaker lost audio track, clearing active speaker");
            }
        }

        // Keep active speaker status if this user was the active speaker with remaining tracks
        if (user.uid === activeSpeakerUid && (user.audioTrack || user.videoTrack)) {
            window.vh360Log("Agora: Active speaker still has tracks after unpublishing", mediaType);
        }
    });

    client.on("user-left", (user) => {
        if (!user || !user.uid) {
            return;
        }

        clearRemoteSubscriptionsForUser(user.uid);
        removeParticipantTile(user.uid);
        if (remoteUsers[user.uid]) delete remoteUsers[user.uid];
        participantJoinSoundThrottle.delete(user.uid);
        activeSpeakerLevels.delete(normalizeParticipantUid(user.uid));
        activeSpeakerCandidates.delete(normalizeParticipantUid(user.uid));
        requestedRemoteStreamTypes.delete(normalizeParticipantUid(user.uid));
        actualRemoteStreamTypes.delete(normalizeParticipantUid(user.uid));
        remoteFallbackStates.delete(normalizeParticipantUid(user.uid));

        if (user.uid === activeSpeakerUid) {
            setActiveSpeaker(null);
        }

        if (isStudioHostUid(user.uid)) {
            setTimeout(() => handleAudienceNoRemoteUsers(), 100);
            return;
        }

        if (currentRole === 'audience' && Object.keys(remoteUsers).length === 0) {
            setTimeout(() => handleAudienceNoRemoteUsers(), 100);
        }
    });

    client.on("client-role-changed", async (oldRole, newRole) => {
        window.vh360Log('VideoHub360: Client role changed', {
            oldRole: oldRole,
            newRole: newRole,
            hasServerApprovedPublishToken: hasServerApprovedPublishToken
        });

        currentRole = newRole;
        updateControlsVisibility();

        if (newRole === 'host') {
            if (!hasServerApprovedPublishToken && config.requireAgoraTokens) {
                window.vh360Log('VideoHub360: Host role event received before publish token approval; waiting for normal presenter flow.');
                return;
            }

            // Avoid duplicate publish calls when the presenter flow already started publishing.
            if (localTracks.audioTrack || localTracks.videoTrack) {
                window.vh360Log('VideoHub360: Already publishing — skipping duplicate startPublishing() from role-change event.');
                return;
            }

            await startPublishing();
            return;
        }

        // Role changed away from host — reset approval state.
        hasServerApprovedPublishToken = false;
        isPresenter = false;

        if (localTracks.audioTrack || localTracks.videoTrack) {
            await stopPublishing();
        }
    });

    /**
     * Enhanced cleanup functions for better user experience
     */
    function cleanupFrozenVideoFrames() {
        window.vh360Log("Agora: Cleaning up frozen video frames");

        // Find all video elements in remote player containers
        const videoSelectors = [
            '#vh360-agora-remote-players video',
            '.vh360-remote-player video',
            '[id*="player-"] video',
            'video[src^="blob:"]'
        ];

        videoSelectors.forEach(selector => {
            const videos = document.querySelectorAll(selector);
            videos.forEach(video => {
                try {
                    // Check for stale/frozen video streams
                    if (video.srcObject) {
                        const tracks = video.srcObject.getTracks();
                        const hasLiveTracks = tracks.some(track => track.readyState === 'live');

                        if (!hasLiveTracks) {
                            window.vh360Log("Agora: Cleaning up frozen video element with dead tracks");
                            video.pause();
                            video.srcObject = null;
                            // Leave the video element but clear its source
                        }
                    }
                } catch (error) {
                    window.vh360Warn("Agora: Error cleaning video element:", error);
                }
            });
        });
    }

    function cleanupStaleVideoElements() {
        window.vh360Log("Agora: Cleaning up stale video elements");

        // Get all player elements
        const playerElements = document.querySelectorAll('[id^="player-"]');

        playerElements.forEach(playerElement => {
            const uid = playerElement.id.replace('player-', '');

            // Check if this player corresponds to a current remote user
            if (!remoteUsers[uid]) {
                window.vh360Log(`Agora: Removing stale player element for UID ${uid}`);

                removeParticipantTile(uid);
            }
        });
    }

    function markRemoteVideosAsReconnecting() {
        const remotePlayers = document.querySelectorAll('[id^="remote-player-"], [id^="player-"]');

        remotePlayers.forEach((playerElement) => {
            playerElement.classList.add('vh360-agora-reconnecting');
        });
    }

    function clearRemoteVideosReconnectingState() {
        const remotePlayers = document.querySelectorAll('.vh360-agora-reconnecting');

        remotePlayers.forEach((playerElement) => {
            playerElement.classList.remove('vh360-agora-reconnecting');
        });
    }

    // Page navigation detection for faster cleanup
    window.addEventListener('beforeunload', () => {
        window.vh360Log("Agora: Page navigation detected, cleaning up immediately");

        // Exit iOS immersive fullscreen if active before unloading
        if (isIOSImmersiveFullscreen) {
            exitIOSImmersiveFullscreen();
        }

        // Stop proactive token renewal when the page is leaving.
        clearAgoraTokenRenewalTimer();

        // Clean up any frozen frames immediately when user navigates away
        cleanupFrozenVideoFrames();

        // If we're connected to the stream, attempt immediate cleanup
        if (client && client.connectionState === 'CONNECTED') {
            try {
                // Quick cleanup without waiting for normal disconnect flow
                cleanupStaleVideoElements();
            } catch (error) {
                window.vh360Log("Agora: Error during page navigation cleanup:", error);
            }
        }
    });

    client.on("token-privilege-will-expire", async () => {
        window.vh360Log('VideoHub360: Agora token will expire soon; renewing now.');
        const renewed = await renewAgoraToken('will-expire');

        if (!renewed && config.requireAgoraTokens) {
            window.vh360Warn('VideoHub360: Agora token renewal failed before expiry.');
        }
    });

    client.on("token-privilege-did-expire", async () => {
        window.vh360Warn('VideoHub360: Agora token expired; attempting rejoin recovery.');
        agoraTokenRecoveryInProgress = true;

        try {
            const recovered = await recoverExpiredAgoraToken();

            if (recovered) {
                return;
            }

            showAgoraError('Livestream access expired. Please refresh the page to rejoin.');
        } finally {
            agoraTokenRecoveryInProgress = false;
        }
    });

    // -- Enhanced Network and Connection Event Handlers --
    client.on("connection-state-change", (curState, prevState, reason) => {
        window.vh360Log("Agora: Connection state changed", { prevState, curState, reason });

        if (curState === "DISCONNECTED") {
            stopActiveSpeakerDetection();
            // Clean up frozen video frames immediately on disconnect
            cleanupFrozenVideoFrames();

            if (agoraTokenRecoveryInProgress) {
                window.vh360Log("Agora: Disconnect occurred during token recovery; suppressing refresh prompt.");
                return;
            }

            let errorMessage = "Connection lost. ";
            if (reason === "NETWORK_ERROR") {
                errorMessage += "Please check your internet connection and refresh the page.";
            } else if (reason === "SERVER_ERROR") {
                errorMessage += "Server connection failed. Please try again later.";
            } else if (reason === "LEAVE") {
                // Normal disconnection, don't show error
                window.vh360Log("Agora: Normal disconnection");
                return;
            } else {
                errorMessage += "Please refresh the page and try again.";
            }
            showAgoraError(errorMessage);
        } else if (curState === "RECONNECTING") {
            window.vh360Log("Agora: Attempting to reconnect...");
            markRemoteVideosAsReconnecting();
        } else if (curState === "CONNECTED" && prevState !== "CONNECTED" && !isAgoraSessionReplacementInProgress) {
            window.vh360Log("Agora: Successfully reconnected");
            startActiveSpeakerDetection();
            requestFeaturedStreamTypes();
            scheduleRemoteReconciliation('connection-restored', 500);
            clearRemoteVideosReconnectingState();
            isAgoraSessionJoined = true;
            scheduleRemoteReconciliation('connection-restored', 750);
            // Clear any error messages
            const localPlayer = document.getElementById("vh360-agora-local-player");
            if (localPlayer) {
                const errorOverlay = localPlayer.querySelector('#agora-error-overlay');
                if (errorOverlay) errorOverlay.remove();
            }
        }
    });



    function getEventUid(evt) {
        return evt && (evt.uid || evt.userId || evt.remoteUid || evt.uid_ || (evt.user && evt.user.uid));
    }

    function markParticipantVideoState(uid, state, details = {}) {
        const participant = participantRegistry.get(normalizeParticipantUid(uid));
        if (!participant) return;
        if ((state === 'reconnecting' || state === 'fallback') && participant.videoTrack) participant.cameraOn = true;
        setParticipantVideoPlaybackState(participant, state, details);
        updateParticipantTile(participant);
    }

    ['media-reconnect-start', 'media-reconnect-end', 'stream-type-changed', 'stream-fallback', 'network-quality'].forEach((eventName) => {
        if (!client || typeof client.on !== 'function') return;
        client.on(eventName, function () {
            const args = Array.from(arguments);
            recordClientDiagnostic({ event: eventName, args: sanitizeStats(args) });
            if (eventName === 'network-quality') return;
            const uid = getEventUid(args[0]) || args[0];
            if (!uid) return;
            if (eventName === 'media-reconnect-start') {
                markParticipantVideoState(uid, 'reconnecting', { event: eventName });
            } else if (eventName === 'media-reconnect-end') {
                const participant = participantRegistry.get(normalizeParticipantUid(uid));
                markParticipantVideoState(uid, participant && participant.videoTrack ? 'playing' : 'subscribed', { event: eventName });
                scheduleRemoteReconciliation('media-reconnect-end', 250);
            } else if (eventName === 'stream-type-changed') {
                const streamType = args[1] != null ? args[1] : (args[0] && args[0].streamType);
                actualRemoteStreamTypes.set(normalizeParticipantUid(uid), streamType);
                recordParticipantDiagnostic(uid, { actualStreamType: streamType });
            } else if (eventName === 'stream-fallback') {
                const fallback = args[1] != null ? args[1] : (args[0] && (args[0].fallbackType || args[0].state));
                remoteFallbackStates.set(normalizeParticipantUid(uid), fallback);
                const participant = participantRegistry.get(normalizeParticipantUid(uid));
                if (String(fallback).toLowerCase() === 'fallback') {
                    markParticipantVideoState(uid, 'fallback', { fallback });
                } else {
                    markParticipantVideoState(uid, participant && participant.videoTrack ? 'playing' : 'subscribed', { fallback });
                }
            }
        });
    });

    client.on("exception", (evt) => {
        window.vh360Warn("Agora: Exception occurred", evt);

        if (evt.code === "WEBSOCKET_DISCONNECTED") {
            // Clean up frozen frames when websocket disconnects
            cleanupFrozenVideoFrames();
            showAgoraError("Connection interrupted. Please check your network and refresh the page.");
        } else if (evt.code === "NETWORK_QUALITY_POOR") {
            window.vh360Warn("Agora: Poor network quality detected");
            // Could show a network quality warning here
        } else if (evt.code.includes("DISCONNECT") || evt.code.includes("CONNECTION")) {
            // Handle various connection-related exceptions
            cleanupFrozenVideoFrames();
            window.vh360Log("Agora: Connection exception handled with cleanup");
        }
    });

    // -- Quality Configuration Functions --
    /**
     * Get Agora video encoder configuration from quality management system
     */
    function buildAgoraEncoderConfig(qualityData) {
        const baseline = { width: 1280, height: 720, frameRate: 30, bitrateMin: 1200, bitrateMax: 3000 };
        if (!qualityData || qualityData.resolution === 'adaptive' || qualityData.bitrate === 'adaptive') {
            return { ...baseline };
        }
        const resolution = typeof qualityData.resolution === 'string' ? qualityData.resolution : '';
        const parts = resolution.split('x').map(Number);
        const bitrate = Number(qualityData.bitrate);
        return {
            width: Number.isFinite(parts[0]) && parts[0] > 0 ? parts[0] : baseline.width,
            height: Number.isFinite(parts[1]) && parts[1] > 0 ? parts[1] : baseline.height,
            frameRate: Number.isFinite(Number(qualityData.fps)) && Number(qualityData.fps) > 0 ? Number(qualityData.fps) : baseline.frameRate,
            bitrateMin: Number.isFinite(bitrate) && bitrate > 0 ? Math.floor(bitrate * 0.4 / 1000) : baseline.bitrateMin,
            bitrateMax: Number.isFinite(bitrate) && bitrate > 0 ? Math.floor(bitrate / 1000) : baseline.bitrateMax
        };
    }

    function getAgoraVideoConfig() {
        if (!window.vh360QualityManager) {
            window.vh360Log('Quality manager not available, using default video config');
            return { encoderConfig: buildAgoraEncoderConfig(null) };
        }

        const currentQuality = window.vh360QualityManager.getCurrentQuality();
        return { encoderConfig: buildAgoraEncoderConfig(currentQuality && currentQuality.data) };
    }

    /**
     * Get Agora audio encoder configuration
     */
    function getAgoraAudioConfig() {
        return {
            AEC: true,
            ANS: true,
            AGC: true,
            encoderConfig: {
                sampleRate: 48000,
                stereo: false,
                bitrate: 128
            }
        };
    }

    /**
     * Update video quality for active Agora stream
     */
    async function updateLiveStreamQuality(quality, qualityData) {
        if (!localTracks.videoTrack || !client) {
            throw new Error('No active video track or client available');
        }

        window.vh360Log('Updating live stream quality to:', quality);
        const newVideoConfig = { encoderConfig: buildAgoraEncoderConfig(qualityData) };

        if (typeof localTracks.videoTrack.setEncoderConfiguration === 'function') {
            try {
                await localTracks.videoTrack.setEncoderConfiguration(newVideoConfig.encoderConfig);
                window.vh360Log('Successfully updated live stream encoder configuration without republish:', quality);
                if (typeof showAgoraSuccess === 'function') showAgoraSuccess(`Stream quality updated to ${String(quality).toUpperCase()}`);
                return;
            } catch (error) {
                window.vh360Warn('Agora: In-place encoder update failed; preparing fallback replacement', error);
            }
        }

        let newVideoTrack = null;
        let newVideoPublished = false;
        const oldVideoTrack = localTracks.videoTrack;
        try {
            newVideoTrack = await AgoraRTC.createCameraVideoTrack(newVideoConfig);
            await client.unpublish([oldVideoTrack]);
            localTracks.videoTrack = newVideoTrack;
            await client.publish([localTracks.videoTrack]);
            newVideoPublished = true;
            try { oldVideoTrack.stop(); oldVideoTrack.close(); } catch (cleanupError) {}
            const localParticipant = getOrCreateParticipant(currentUserUID || security.user_id || config.uid, {
                isLocal: true,
                isOriginalHost: isOriginalHost,
                displayName: config.displayName || security.display_name,
                wordpressUserId: security.user_id || null
            });
            attachParticipantVideo(localParticipant, localTracks.videoTrack, true);
            window.vh360Log('Successfully updated live stream quality to:', quality);
            if (typeof showAgoraSuccess === 'function') showAgoraSuccess(`Stream quality updated to ${String(quality).toUpperCase()}`);
        } catch (error) {
            if (newVideoPublished && newVideoTrack && client && client.connectionState === 'CONNECTED') {
                try { await client.unpublish([newVideoTrack]); } catch (rollbackUnpublishError) {}
            }
            if (newVideoTrack) {
                try { newVideoTrack.stop(); newVideoTrack.close(); } catch (cleanupError) {}
            }
            localTracks.videoTrack = oldVideoTrack;
            if (oldVideoTrack && client && client.connectionState === 'CONNECTED') {
                try { await client.publish([oldVideoTrack]); } catch (rollbackError) { window.vh360Warn('Agora: Failed to republish previous video track after quality fallback failure', rollbackError); }
            }
            window.vh360Error('Error during quality update:', error);
            throw error;
        }
    }

    /**
     * Show success message for quality changes
     */
    function showAgoraSuccess(message) {
        // Create or update success overlay
        const localPlayer = document.getElementById("vh360-agora-local-player");
        if (localPlayer) {
            const existingOverlay = localPlayer.querySelector('#agora-success-overlay');
            if (existingOverlay) existingOverlay.remove();

            const overlay = document.createElement('div');
            overlay.id = 'agora-success-overlay';
            overlay.style.cssText = `
                position: absolute; top: 10px; right: 10px;
                background: rgba(76, 175, 80, 0.9); color: white;
                padding: 8px 12px; border-radius: 4px;
                font-size: 12px; z-index: 1000;
            `;
            overlay.textContent = message;
            localPlayer.style.position = 'relative';
            localPlayer.appendChild(overlay);

            // Auto-remove after 3 seconds
            setTimeout(() => overlay.remove(), 3000);
        }
    }

    // -- Publishing (Host) --
    async function startPublishing() {
        if (config.studioControlled && config.agoraMode !== 'interactive') {
            window.vh360Log('Studio-controlled broadcast stream: public page publishing disabled.');
            return;
        }
        try {
            window.vh360Log("VideoHub360: startPublishing() called");
            window.vh360Log("VideoHub360: Current role at publish time:", currentRole);
            window.vh360Log("VideoHub360: Config details:", {
                role: config.role,
                mode: config.mode,
                agoraMode: config.agoraMode,
                isHost: isHost,
                isOriginalHost: isOriginalHost
            });

            // Authorization guards must run before any camera/microphone access or local preview rendering.
            // Fail-closed: block publishing unless a server-approved host token has already been applied.
            if (config.requireAgoraTokens && !hasServerApprovedPublishToken) {
                window.vh360Error("VideoHub360: Blocked publish attempt — no server-approved host token applied.");
                showAgoraError('You do not have permission to publish to this livestream.');
                return;
            }

            if (currentRole !== "host") {
                window.vh360Error("VideoHub360: Blocked publish attempt — currentRole is '" + currentRole + "' not 'host'.");
                throw new Error("Cannot publish: user role is '" + currentRole + "' but should be 'host'");
            }

            window.vh360Log("Agora: Starting to publish tracks");

            if (!localTracks.audioTrack || !localTracks.videoTrack) {
                try {
                    // Get quality configuration from the quality management system
                    const videoConfig = getAgoraVideoConfig();
                    const audioConfig = getAgoraAudioConfig();

                    [localTracks.audioTrack, localTracks.videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks(audioConfig, videoConfig);
                    window.vh360Log("Agora: Successfully created audio and video tracks");

                    if (!isOriginalHost) {
                        isAudioMuted = true;
                        await localTracks.audioTrack.setMuted(true);
                    }
                } catch (deviceError) {
                    window.vh360Error("Agora: Device access failed", deviceError);

                    // Provide graceful degradation - try audio-only if possible
                    let fallbackMessage = "Camera/microphone access failed. ";
                    if (deviceError.code === 'PERMISSION_DENIED' || deviceError.message.includes('Permission denied')) {
                        fallbackMessage += "Please allow camera and microphone access in your browser settings.";
                    } else if (deviceError.code === 'NOT_FOUND' || deviceError.message.includes('not found')) {
                        fallbackMessage += "No camera or microphone found. Please connect your devices.";
                    } else if (deviceError.code === 'NOT_READABLE' || deviceError.message.includes('in use')) {
                        fallbackMessage += "Your camera/microphone is being used by another application.";
                    } else {
                        fallbackMessage += "Please check your browser settings and device connections.";
                    }

                    showAgoraError(fallbackMessage);

                    // Try to maintain basic viewer functionality
                    setLocalPlayerStatusHTML('<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 1.1em; background: #333; text-align: center; padding: 20px;">Device Access Failed<br><small style="font-size: 0.8em; opacity: 0.7;">' + fallbackMessage + '</small></div>');
                    return; // Don't try to publish if we can't get tracks
                }
            }

            const localParticipant = getOrCreateParticipant(currentUserUID || security.user_id || config.uid, {
                isLocal: true,
                isOriginalHost: isOriginalHost,
                displayName: config.displayName || security.display_name,
                wordpressUserId: security.user_id || null
            });

            if (localTracks.audioTrack) {
                localParticipant.audioTrack = localTracks.audioTrack;
                localParticipant.audioOn = !isAudioMuted;
                if (isAudioMuted) localParticipant.isSpeaking = false;
            }

            if (localTracks.videoTrack && typeof localTracks.videoTrack.play === 'function') {
                attachParticipantVideo(localParticipant, localTracks.videoTrack, true);
                window.vh360Log('VideoHub360: Local video attached to persistent participant tile');
            } else {
                localParticipant.cameraOn = false;
                setParticipantVideoPlaybackState(localParticipant, 'off', { reason: 'local-stop' });
                updateParticipantTile(localParticipant);
                window.vh360Warn("Agora: Invalid local video track for publishing");
            }

            refreshFeaturedParticipantTiles();

            await configureInteractiveDualStream();
            await client.publish([localTracks.audioTrack, localTracks.videoTrack]);
            window.vh360Log("Agora: Successfully published tracks");
            window.vh360Log('VideoHub360: Publish completed for UID:', currentUserUID);
            startActiveSpeakerDetection();
            requestFeaturedStreamTypes();

            if (muteAudioBtn) {
                updateAgoraControlButtonStates();
                muteAudioBtn.style.backgroundColor = isAudioMuted ? '#e53935' : 'transparent';
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) {
                    participant.audioOn = !isAudioMuted;
                    if (isAudioMuted) participant.isSpeaking = false;
                    updateParticipantTile(participant);
                }
            }
            if (muteVideoBtn) {
                updateAgoraControlButtonStates();
                muteVideoBtn.style.backgroundColor = isVideoMuted ? '#e53935' : 'transparent';
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) { participant.cameraOn = !isVideoMuted; updateParticipantTile(participant); }
            }
        } catch (error) {
            window.vh360Error("Agora: Publishing failed", error);
            window.vh360Error('VideoHub360: Publish failed for UID:', currentUserUID, error);

            if (!isOriginalHost && config.agoraMode === 'interactive' && currentUserUID) {
                removeParticipantTile(currentUserUID);
            }

            // Provide specific error messages for publishing failures
            let errorMessage = "Failed to start streaming. ";
            if (error.code === 'PUBLISH_REQUEST_INVALID') {
                errorMessage += "Invalid streaming request. Please refresh and try again.";
            } else if (error.code === 'NETWORK_ERROR') {
                errorMessage += "Network connection issue. Please check your internet and try again.";
            } else {
                errorMessage += "Please check your connection and device settings.";
            }

            showAgoraError(errorMessage);
            throw error;
        }
    }
    async function stopPublishing() {
        try {
            if (localTracks.audioTrack) {
                localTracks.audioTrack.stop();
                localTracks.audioTrack.close();
                localTracks.audioTrack = null;
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) {
                    participant.audioTrack = null;
                    participant.audioOn = false;
                    participant.isSpeaking = false;
                    updateParticipantTile(participant);
                }
            }
            if (localTracks.videoTrack) {
                localTracks.videoTrack.stop();
                localTracks.videoTrack.close();
                localTracks.videoTrack = null;
            }
            const localParticipant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
            if (localParticipant) {
                localParticipant.audioTrack = null;
                localParticipant.videoTrack = null;
                localParticipant.audioOn = false;
                localParticipant.isSpeaking = false;
                localParticipant.cameraOn = false;
                setParticipantVideoPlaybackState(localParticipant, 'off', { reason: 'local-stop' });
                updateParticipantTile(localParticipant);
            }
        } catch (error) {}
    }

    // -- Controls --
    if (muteAudioBtn) {
        muteAudioBtn.addEventListener('click', async () => {
            if (localTracks.audioTrack) {
                await localTracks.audioTrack.setMuted(!isAudioMuted);
                isAudioMuted = !isAudioMuted;
                updateAgoraControlButtonStates();
                muteAudioBtn.style.backgroundColor = isAudioMuted ? '#e53935' : 'transparent';
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) {
                    participant.audioOn = !isAudioMuted;
                    if (isAudioMuted) participant.isSpeaking = false;
                    updateParticipantTile(participant);
                }
            }
        });
    }
    if (muteVideoBtn) {
        muteVideoBtn.addEventListener('click', async () => {
            if (localTracks.videoTrack) {
                await localTracks.videoTrack.setMuted(!isVideoMuted);
                isVideoMuted = !isVideoMuted;
                updateAgoraControlButtonStates();
                muteVideoBtn.style.backgroundColor = isVideoMuted ? '#e53935' : 'transparent';
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) { participant.cameraOn = !isVideoMuted; updateParticipantTile(participant); }
            }
        });
    }
    // Join as Presenter button handler (replaces raise hand functionality)
    if (joinAsPresenterBtn && config.agoraMode === 'interactive') {
        joinAsPresenterBtn.addEventListener('click', async () => {
            if (config.agoraMode !== 'interactive') {
                showAgoraError('Join as presenter is only available in Interactive mode.');
                return;
            }
            if (isStudioHostViewer) {
                showAgoraError('Studio is already publishing your live feed. Use Studio to control this livestream.');
                return;
            }
            if (!security.is_logged_in) {
                showAgoraError('Please log in to join as a presenter.');
                return;
            }
            if (config.studioControlled && config.agoraMode !== 'interactive') {
                window.vh360Log('Studio-controlled broadcast stream: public page publishing disabled.');
                return;
            }
            if (isOriginalHost) {
                showAgoraError('Administrators are already hosts and cannot join as presenters.');
                return;
            }

            // Check access control — collect passcode if required, then validate server-side.
            let presenterPasscode = '';
            if (config.hostPasscodeRequired) {
                // Passcode is required — prompt the user and send it to the server for validation.
                const userPasscode = prompt('Enter the host passcode to join as a presenter:');
                if (!userPasscode) {
                    // User cancelled the prompt.
                    return;
                }
                presenterPasscode = userPasscode;
            } else if (!config.allowEveryoneIsHost) {
                // Neither passcode nor "Allow Everyone" is enabled — access denied.
                showAgoraError('Access denied. The host has not enabled "Allow Everyone to be Host" or set up a passcode for joining as presenter.');
                return;
            }
            // Do NOT compare the passcode in JavaScript. Send it to the server.

            // If we reach here, either no passcode was required or the user entered one.
            if (currentRole === 'audience') {
                try {
                    joinAsPresenterBtn.disabled = true;
                    joinAsPresenterBtn.textContent = '⏳ Joining...';

                    // Request a host token from the server.
                    // The server validates the passcode and decides whether to approve host role.
                    let tokenResponse;
                    try {
                        tokenResponse = await requestTokenFromServer(config.channelName, config.uid, 'host', presenterPasscode);
                    } catch (tokenError) {
                        joinAsPresenterBtn.disabled = false;
                        joinAsPresenterBtn.textContent = '🎭 Go Live';
                        joinAsPresenterBtn.style.backgroundColor = 'transparent';
                        showAgoraError(tokenError.message || 'Failed to request presenter access. Please try again.');
                        return;
                    }

                    // The server-returned role is authoritative.
                    if (!tokenResponse || tokenResponse.role !== 'host') {
                        joinAsPresenterBtn.disabled = false;
                        joinAsPresenterBtn.textContent = '🎭 Go Live';
                        joinAsPresenterBtn.style.backgroundColor = 'transparent';
                        showAgoraError('You do not have permission to join as a presenter.');
                        return;
                    }

                    if (config.requireAgoraTokens && !tokenResponse.token) {
                        joinAsPresenterBtn.disabled = false;
                        joinAsPresenterBtn.textContent = '🎭 Go Live';
                        joinAsPresenterBtn.style.backgroundColor = 'transparent';
                        showAgoraError('Unable to join as presenter because a valid host token was not issued.');
                        return;
                    }

                    // Apply the server-approved host token to the active Agora client before
                    // publishing. Without this the user would still hold an audience/subscriber
                    // token and startPublishing() would be rejected by the Agora service.
                    if (tokenResponse.token) {
                        let tokenApplied = false;
                        try {
                            if (client && typeof client.renewToken === 'function') {
                                await client.renewToken(tokenResponse.token);
                            }
                            if (config.mode === 'live' && client && typeof client.setClientRole === 'function') {
                                await client.setClientRole('host');
                            }
                            tokenApplied = true;
                            hasServerApprovedPublishToken = true;
                        } catch (renewError) {
                            window.vh360Warn('VideoHub360: Host token renewal failed; attempting leave/rejoin as host:', renewError);
                            try {
                                await rejoinWithHostToken(tokenResponse);
                                tokenApplied = true;
                                hasServerApprovedPublishToken = true;
                            } catch (rejoinError) {
                                joinAsPresenterBtn.disabled = false;
                                joinAsPresenterBtn.textContent = '🎭 Go Live';
                                joinAsPresenterBtn.style.backgroundColor = 'transparent';
                                showAgoraError('Unable to upgrade your livestream permissions. Please leave and rejoin, then try again.');
                                return;
                            }
                        }
                        if (!tokenApplied) {
                            joinAsPresenterBtn.disabled = false;
                            joinAsPresenterBtn.textContent = '🎭 Go Live';
                            joinAsPresenterBtn.style.backgroundColor = 'transparent';
                            showAgoraError('Unable to upgrade your livestream permissions. Please leave and rejoin, then try again.');
                            return;
                        }

                        latestAgoraTokenResponse = tokenResponse;
                        if (tokenResponse.expiresAt) {
                            scheduleAgoraTokenRenewal(tokenResponse.expiresAt);
                        }
                    }

                    // Server approved host role and token is applied — proceed to publish.
                    await promoteToHost();

                    joinAsPresenterBtn.textContent = '⬇️ Leave Presenter';
                    joinAsPresenterBtn.style.backgroundColor = '#4CAF50';
                    joinAsPresenterBtn.disabled = false;
                } catch (error) {
                    joinAsPresenterBtn.disabled = false;
                    joinAsPresenterBtn.textContent = '🎭 Go Live';
                    joinAsPresenterBtn.style.backgroundColor = 'transparent';
                    showAgoraError("Failed to join as presenter. Please check your camera/microphone permissions and try again.");
                }
            } else if (currentRole === 'host' && !isOriginalHost) {
                // Allow leaving presenter mode
                try {
                    joinAsPresenterBtn.disabled = true;
                    joinAsPresenterBtn.textContent = '⏳ Leaving...';
                    await stopPublishing();
                    // In SDK v4, role changes are handled via publishing state, not setClientRole
                    // The user becomes audience by simply stopping publishing
                    currentRole = 'audience';
                    isPresenter = false;
                    hasServerApprovedPublishToken = false;
                    isAudioMuted = false;
                    isVideoMuted = false;
                    joinAsPresenterBtn.textContent = '🎭 Go Live';
                    joinAsPresenterBtn.style.backgroundColor = 'transparent';
                    joinAsPresenterBtn.disabled = false;
                    updateControlsVisibility();
                    window.vh360Log("Agora: User role changed to audience via unpublishing");

                    // Update mobile controls visibility when role changes
                    if (typeof window.updateMobileControlsVisibility === 'function') {
                        window.updateMobileControlsVisibility();
                    }

                    setTimeout(() => {
                        const remainingRemoteUsers = Object.keys(remoteUsers).length;
                        if (remainingRemoteUsers === 0) showAudienceWaitingMessage();
                    }, 100);
                } catch (error) {
                    joinAsPresenterBtn.disabled = false;
                    joinAsPresenterBtn.textContent = '⬇️ Leave Presenter';
                    window.vh360Error("Agora: Failed to leave presenter mode", error);
                    showAgoraError("Failed to leave presenter mode. Please try again.");
                }
            }
        });
    }
    /**
     * Leave and rejoin the Agora channel using a server-approved host token.
     *
     * Used as a fallback when client.renewToken() fails during presenter promotion.
     * After this call the client is re-joined with publisher privileges; the caller
     * should proceed directly to track creation / publishing without another join.
     */
    async function rejoinWithHostToken(tokenResponse) {
        if (!client || !tokenResponse || !tokenResponse.token) {
            throw new Error('Missing live connection session or presenter authorization for rejoin.');
        }

        // The next join is a new Agora session; old subscriptions cannot be reused.
        beginAgoraSessionReplacement('host-token-rejoin');

        // Stop and release any existing local tracks before leaving.
        await stopPublishing();

        try {
            isAgoraSessionJoined = false;
            await client.leave();
        } catch (leaveError) {
            window.vh360Warn('VideoHub360: client.leave() before host rejoin failed:', leaveError);
        }

        if (config.mode === 'live' && typeof client.setClientRole === 'function') {
            await client.setClientRole('host');
        }

        const rejoinUid = tokenResponse.uid || config.uid;
        try {
            await client.join(
                config.appId,
                tokenResponse.channel || config.channelName,
                tokenResponse.token,
                Number(rejoinUid)
            );
        } catch (joinError) {
            completeAgoraSessionReplacement(false);
            throw joinError;
        }

        latestAgoraTokenResponse = tokenResponse;
        currentUserUID = Number(tokenResponse.uid || config.uid);
        completeAgoraSessionReplacement(true);
        currentRole = 'host';
        isPresenter = true;
        reconcileRemoteSubscriptions('host-token-rejoin');
        scheduleRemoteReconciliation('host-token-rejoin-stabilization', 750);
        enableVolumeIndication();
        startActiveSpeakerDetection();
        requestFeaturedStreamTypes();
    }

    async function promoteToHost() {
        try {
            let audioTrack, videoTrack;
            try {
                const videoConfig = getAgoraVideoConfig();
                const audioConfig = getAgoraAudioConfig();
                [audioTrack, videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks(audioConfig, videoConfig);
                isAudioMuted = true;
                await audioTrack.setMuted(true);
            } catch (deviceError) {
                let deviceErrorMessage = "Cannot access camera/microphone. ";
                if (deviceError.code === 'PERMISSION_DENIED' || deviceError.message.includes('Permission denied')) {
                    deviceErrorMessage += "Please allow camera and microphone access in your browser settings, then request to join again.";
                } else if (deviceError.code === 'NOT_FOUND' || deviceError.message.includes('not found')) {
                    deviceErrorMessage += "No camera or microphone found. Please connect your devices and try again.";
                } else if (deviceError.code === 'NOT_READABLE' || deviceError.message.includes('in use')) {
                    deviceErrorMessage += "Your camera/microphone is being used by another application. Please close other apps and try again.";
                } else {
                    deviceErrorMessage += "Please check your browser settings and try again.";
                }
                throw new Error(deviceErrorMessage);
            }
            localTracks.audioTrack = audioTrack;
            localTracks.videoTrack = videoTrack;
            // In SDK v4, role changes are handled via publishing state, not setClientRole
            // The user becomes host by starting to publish
            currentRole = 'host';
            isPresenter = true;
            window.vh360Log("Agora: User role changed to host via publishing");

            // Update mobile controls visibility when role changes
            if (typeof window.updateMobileControlsVisibility === 'function') {
                window.updateMobileControlsVisibility();
            }

            if (joinAsPresenterBtn) {
                joinAsPresenterBtn.textContent = '⬇️ Leave Presenter';
                joinAsPresenterBtn.style.backgroundColor = '#4CAF50';
                joinAsPresenterBtn.disabled = false;
            }
            await startPublishing();
            updateControlsVisibility();
        } catch (error) {
            if (localTracks.audioTrack) {
                localTracks.audioTrack.stop();
                localTracks.audioTrack.close();
                localTracks.audioTrack = null;
                const participant = currentUserUID ? participantRegistry.get(String(currentUserUID)) : null;
                if (participant) {
                    participant.audioTrack = null;
                    participant.audioOn = false;
                    participant.isSpeaking = false;
                    updateParticipantTile(participant);
                }
            }
            if (localTracks.videoTrack) {
                localTracks.videoTrack.stop();
                localTracks.videoTrack.close();
                localTracks.videoTrack = null;
            }
            if (joinAsPresenterBtn) {
                joinAsPresenterBtn.disabled = false;
                joinAsPresenterBtn.textContent = '🎭 Go Live';
                joinAsPresenterBtn.style.backgroundColor = 'transparent';
            }
            isPresenter = false;
            hasServerApprovedPublishToken = false;
            showAgoraError(error.message || "Failed to join as host. Please check your camera/microphone permissions and try again.");
        }
    }

    /**
     * Handles incoming moderation actions from the host
     */
    function handleModerationAction(data) {
        window.vh360Log('Agora: Processing moderation action:', data);
        window.vh360Log('Agora: Current user info - UID:', currentUserUID, 'WordPress ID:', security.user_id);

        // Enhanced validation of moderation data
        if (!data.action || !data.target_uid && !data.target_user_id) {
            window.vh360Warn('Agora: Invalid moderation data received:', data);
            return;
        }

        // Check if this moderation action is targeted at the current user
        // Compare both target_uid (Agora UID) and target_user_id (WordPress user ID)
        const isTargetedByUID = data.target_uid && currentUserUID && data.target_uid == currentUserUID;
        const isTargetedByWordPressID = data.target_user_id && data.target_user_id == security.user_id;
        const isTargetedAtMe = isTargetedByUID || isTargetedByWordPressID;

        window.vh360Log('Agora: Moderation target check:', {
            target_uid: data.target_uid,
            target_user_id: data.target_user_id,
            my_agora_uid: currentUserUID,
            my_wordpress_id: security.user_id,
            isTargetedByUID,
            isTargetedByWordPressID,
            isTargetedAtMe
        });

        if (isTargetedAtMe) {
            window.vh360Log('Agora: ⚡ CRITICAL: Received moderation action targeted at current user:', data);

            // Set moderation flag to prevent any further UI updates
            isBeingModerated = true;

            // IMMEDIATE UI FREEZE: Immediately freeze the video display to show disconnection is happening
            const localPlayer = document.getElementById('vh360-agora-local-player');
            const remotePlayersContainer = document.getElementById('vh360-agora-remote-players');

            // Add visual freeze effect immediately
            if (localPlayer) {
                localPlayer.style.filter = 'grayscale(100%) blur(3px)';
                localPlayer.style.opacity = '0.5';
                localPlayer.style.pointerEvents = 'none'; // Prevent any interaction
            }
            if (remotePlayersContainer) {
                remotePlayersContainer.style.filter = 'grayscale(100%) blur(3px)';
                remotePlayersContainer.style.opacity = '0.5';
                remotePlayersContainer.style.pointerEvents = 'none'; // Prevent any interaction
            }

            // Immediately show notification
            const actionText = data.action.charAt(0).toUpperCase() + data.action.slice(1);
            const moderatorName = data.moderator_name || 'the host';

            let message = '';
            let notificationType = 'warning';

            switch (data.action) {
                case 'kick':
                    message = `You have been kicked from the stream by ${moderatorName}.`;
                    break;
                case 'timeout':
                    const duration = data.expiration_time ? new Date(data.expiration_time) : null;
                    if (duration) {
                        const minutes = Math.ceil((duration.getTime() - Date.now()) / (1000 * 60));
                        message = `You have been timed out for ${minutes} minute${minutes > 1 ? 's' : ''} by ${moderatorName}.`;
                    } else {
                        message = `You have been timed out for 5 minutes by ${moderatorName}.`;
                    }
                    break;
                case 'ban':
                    message = `You have been permanently banned from this stream by ${moderatorName}.`;
                    notificationType = 'error';
                    break;
                default:
                    message = `You have been moderated (${data.action}) by ${moderatorName}.`;
            }

            // Show notification immediately
            showModerationNotification(message, notificationType);

            // Disconnect immediately with minimal delay - no need to wait for server checks
            setTimeout(() => {
                window.vh360Log('Agora: ⚡ CRITICAL: Disconnecting user due to moderation action');
                if (window.agoraClient) {
                    const disconnectReason = data.action === 'kick' ? 'You were kicked from the stream' :
                                           data.action === 'timeout' ? 'You are currently timed out' :
                                           'You have been banned from this stream';
                    disconnectFromStream(disconnectReason);
                }
            }, 300); // Reduced to 300ms for even faster response

        } else {
            window.vh360Log('Agora: Received moderation action for another user:', data);
        }
    }

    /**
     * Shows moderation notification to the user
     */
    function showModerationNotification(message, type = 'info') {
        // Remove existing moderation notifications
        const existingNotifications = document.querySelectorAll('.vh360-moderation-notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `vh360-moderation-notification notification-${type}`;

        const icons = {
            error: '🚫',
            warning: '⚠️',
            info: 'ℹ️'
        };

        notification.innerHTML = `
            <div class="notification-icon">${icons[type]}</div>
            <div style="line-height: 1.4;">${message}</div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds for non-error notifications
        if (type !== 'error') {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'moderationNotificationFadeOut 0.3s ease-out';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 5000);
        }
    }

    /**
     * Safely disconnects from the stream with cleanup
     */
    function disconnectFromStream(reason) {
        try {
            window.vh360Log('Agora: Disconnecting from stream -', reason);

            // Exit iOS immersive fullscreen if active
            if (isIOSImmersiveFullscreen) {
                exitIOSImmersiveFullscreen();
            }

            stopActiveSpeakerDetection();
            stopAgoraDiagnostics();
            unbindAutoplayFailureRecovery();
            const autoplayPrompt = document.getElementById('vh360-agora-autoplay-recovery');
            if (autoplayPrompt) autoplayPrompt.remove();
            document.removeEventListener('vh360:agora-focus-changed', handleAgoraLayoutStreamSelectionChange);
            document.removeEventListener('vh360:agora-layout-changed', handleAgoraLayoutStreamSelectionChange);

            // Set moderation flag
            isBeingModerated = true;

            // Tear down layout controls/listeners before hiding or clearing player UI.
            destroyViewLayoutManager();

            // IMMEDIATE UI UPDATE: Hide video players first for instant visual feedback
            const localPlayer = document.getElementById('vh360-agora-local-player');
            const remotePlayersContainer = document.getElementById('vh360-agora-remote-players');

            clearAllParticipantTiles();
            if (localPlayer) {
                localPlayer.style.opacity = '0';
                localPlayer.style.pointerEvents = 'none';
            }
            if (remotePlayersContainer) {
                remotePlayersContainer.style.opacity = '0';
                remotePlayersContainer.style.pointerEvents = 'none';
            }

            // Stop all moderation polling intervals
            if (moderationPollingIntervals && moderationPollingIntervals.length > 0) {
                window.vh360Log('Agora: Clearing', moderationPollingIntervals.length, 'moderation polling intervals');
                moderationPollingIntervals.forEach(intervalId => {
                    clearInterval(intervalId);
                });
                moderationPollingIntervals = [];
            }

            // Set disconnect flag to prevent further polling
            isDisconnected = true;

            // Reset polling guards
            periodicCheckRunning = false;

            // Stop periodic moderation checks
            if (window.triggerImmediateModerationCheck) {
                window.triggerImmediateModerationCheck = null;
            }

            clearAgoraTokenRenewalTimer();
            isAgoraSessionJoined = false;
            resetRemoteSubscriptionSession('channel-leave');
            latestAgoraTokenResponse = null;
            agoraTokenRecoveryInProgress = false;

            // IMMEDIATE: Show disconnection overlay on top of everything
            const joinOverlay = document.getElementById('vh360-join-livestream-overlay');
            if (joinOverlay) {
                joinOverlay.style.display = 'flex';
                joinOverlay.style.zIndex = '99999'; // Ensure it's on top
                joinOverlay.innerHTML = `
                    <div style="text-align:center;color:#fff;max-width:400px;padding:20px;">
                        <div style="font-size:2.5em;margin-bottom:16px;">🔴</div>
                        <h3 style="color:#fff;margin-bottom:16px;font-size:1.4em;">Disconnected</h3>
                        <p style="margin-bottom:20px;color:#ccc;line-height:1.4;">${reason}</p>
                        <button onclick="location.reload()" style="background:#666;color:#fff;border:none;padding:12px 24px;border-radius:6px;font-size:1.1em;font-weight:600;cursor:pointer;">Refresh Page</button>
                    </div>
                `;
            }

            // Leave the channel
            if (window.agoraClient) {
                window.agoraClient.leave().catch(error => {
                    window.vh360Warn('Agora: Error leaving channel:', error);
                });
            }

            // Clean up local tracks
            if (localVideoTrack) {
                localVideoTrack.stop();
                localVideoTrack.close();
                localVideoTrack = null;
            }
            if (localAudioTrack) {
                localAudioTrack.stop();
                localAudioTrack.close();
                localAudioTrack = null;
            }

            // Reset UI state
            isJoined = false;
            currentRole = null;
            hasServerApprovedPublishToken = false;
            clearAllParticipantTiles();
            currentUserUID = null; // Clear stored UID

            window.vh360Log('Agora: Disconnection and cleanup completed');

        } catch (error) {
            window.vh360Error('Agora: Error during disconnection:', error);
        }
    }

    if (leaveBtn) {
        leaveBtn.addEventListener('click', async () => {
            if (confirm('Leave the livestream?')) {
                await runAgoraLifecycleHandlers(vh360BeforeLeaveHandlers);
                await leaveChannel();
            }
        });
    }
    if (endStreamBtn && canModerate) {
        endStreamBtn.addEventListener('click', async () => {
            if (confirm('End the entire livestream for all participants? This action cannot be undone.')) {
                await runAgoraLifecycleHandlers(vh360BeforeEndHandlers);
                endStreamBtn.disabled = true;
                endStreamBtn.textContent = 'Ending...';
                try {
                    const formData = new FormData();
                    formData.append('action', 'vh360_end_stream');
                    formData.append('nonce', vh360Data.endStreamNonce);
                    formData.append('post_id', vh360Data.postId);
                    const response = await fetch(vh360Data.ajaxUrl, { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.success) {
                        const moderatorHtml = vh360Data?.livestreamMessages?.endedByModeratorHtml;
                        setLocalPlayerStatusHTML((moderatorHtml && moderatorHtml.trim())
                            ? moderatorHtml
                            : '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 1.2em; background: #333; flex-direction: column;"><div>📴 Stream Ended</div><div style="font-size: 0.8em; margin-top: 8px; text-align: center;">The livestream has been ended by the moderator</div></div>');
                        if (controlsContainer) {
                            controlsContainer.innerHTML = '<div style="color: #fff; text-align: center; width: 100%; padding: 10px;">Stream ended. <a href="#" onclick="location.reload()" style="color: #4CAF50;">Refresh page</a> to see updated status.</div>';
                        }
                        await leaveChannel();
                    } else {
                        alert('Failed to end stream: ' + (data.data || 'Unknown error'));
                        endStreamBtn.disabled = false;
                        endStreamBtn.textContent = 'End Stream';
                    }
                } catch (error) {
                    alert('Failed to end stream due to network error. Please try again or use the admin panel.');
                    endStreamBtn.disabled = false;
                    endStreamBtn.textContent = 'End Stream';
                }
            }
        });
    }

    function isStudioReplayProcessingStatus(status) {
        const data = status || {};
        if (data.replay_processing === true) {
            return true;
        }
        const hasStudioJob = !!(data.studio_job_id || vh360Data?.studioJobId || config?.studioJobId);
        const isStudioControlled = data.studio_controlled === true || vh360Data?.studioControlled === true || config?.studioControlled === true;
        const replayReady = data.studio_replay_ready === true || vh360Data?.studioReplayReady === true || config?.studioReplayReady === true;
        const replayPending = data.studio_replay_pending === true || vh360Data?.studioReplayPending === true || config?.studioReplayPending === true;
        const replayFailed = data.studio_replay_failed === true || vh360Data?.studioReplayFailed === true || config?.studioReplayFailed === true;
        return isStudioControlled && hasStudioJob && replayPending && !replayReady && !replayFailed;
    }

    function showStreamEndedMessage(status) {
        clearAllParticipantTiles();
        remoteUsers = {};
        window.vh360StreamStarted = false;

        const liveControls = document.getElementById('vh360-agora-controls');
        if (liveControls) {
            liveControls.style.display = 'none';
        }

        const localPlayer = document.getElementById("vh360-agora-local-player");
        if (!localPlayer) return;

        const replayProcessing = isStudioReplayProcessingStatus(status);
        const endedHtml = replayProcessing
            ? vh360Data?.livestreamMessages?.replayProcessingHtml
            : vh360Data?.livestreamMessages?.endedDefaultHtml;

        localPlayer.replaceChildren();
        const messageContainer = document.createElement('div');
        messageContainer.className = 'vh360-stream-ended-message vh360-offline-message';
        messageContainer.innerHTML = endedHtml || (replayProcessing
            ? '<div class="vh360-stream-ended-content"><div class="vh360-stream-ended-icon">📴</div><h3 class="vh360-stream-ended-title">Stream Ended</h3><p class="vh360-stream-ended-text">Thanks for watching. The replay is being prepared and will be available here soon.</p></div>'
            : '<div class="vh360-stream-ended-content"><div class="vh360-stream-ended-icon">📴</div><h3 class="vh360-stream-ended-title">Stream Ended</h3><p class="vh360-stream-ended-text">This livestream has ended.</p></div>');
        localPlayer.appendChild(messageContainer);
    }

    function fetchStreamStatus() {
        if (!vh360Data || !vh360Data.postId) return Promise.reject('No post ID available');

        var formData = new FormData();
        formData.append('action', 'vh360_get_stream_status');
        formData.append('post_id', vh360Data.postId);

        return fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        }).then(function(response) { return response.json(); });
    }

    function handleStreamEndedFromServer(status) {
        stopStreamStatusPolling();
        if (localTracks.audioTrack || localTracks.videoTrack) {
            stopPublishing().catch(function(error) {
                window.vh360Warn('VideoHub360: Failed to stop local tracks after stream ended:', error);
            });
        }
        showStreamEndedMessage(status);
    }

    function handleAudienceNoRemoteUsers() {
        fetchStreamStatus()
            .then(function(data) {
                if (!data.success) {
                    window.vh360Warn('VideoHub360: Stream status check failed after host left:', data.data);
                    return;
                }

                if (data.data.stream_stopped === true) {
                    handleStreamEndedFromServer(data.data);
                } else if (data.data.stream_live === false) {
                    showAudienceWaitingMessage();
                }
            })
            .catch(function(error) {
                window.vh360Warn('VideoHub360: Stream status check failed after host left:', error);
            });
    }

    function showAudienceWaitingMessage() {
        clearAllParticipantTiles();
        const localPlayer = document.getElementById("vh360-agora-local-player");
        if (localPlayer) {
            localPlayer.replaceChildren();
            const messageContainer = document.createElement('div');
            messageContainer.className = 'waiting-message vh360-waiting-container';

            // Check if this is an appointment room
            if (config.appointment && config.appointment.isAppointment) {
                // Appointment-specific waiting message
                if (config.appointment.userRole === 'client') {
                    messageContainer.innerHTML = '<div class="vh360-waiting-text">Waiting for the professional to start the session...</div>';
                } else {
                    // For professional or admin in appointment
                    messageContainer.innerHTML = '<div class="vh360-waiting-text">Waiting for stream to start...</div>';
                }
            } else if (config.agoraMode === 'interactive') {
                // Regular interactive room (non-appointment)
                messageContainer.innerHTML = '<div class="vh360-waiting-text">Click "Go Live" to join as host</div>';
            } else {
                // Broadcast mode
                messageContainer.innerHTML = '<div class="vh360-waiting-text">Waiting for host to start stream...</div>';
            }
            localPlayer.appendChild(messageContainer);
        }
    }

    /**
     * Update appointment overlay state
     * Centralizes appointment overlay UI updates based on session state
     */
    function updateAppointmentOverlay(state) {
        if (!config.appointment || !config.appointment.isAppointment) {
            return; // Not an appointment, do nothing
        }

        const joinOverlay = document.getElementById('vh360-join-livestream-overlay');
        if (!joinOverlay) {
            return;
        }

        const overlayContent = joinOverlay.querySelector('.vh360-overlay-content');
        if (!overlayContent) {
            return;
        }

        // Clear existing content
        overlayContent.innerHTML = '';

        // Add icon
        const icon = document.createElement('div');
        icon.className = 'vh360-overlay-icon';
        icon.textContent = '🔴';
        overlayContent.appendChild(icon);

        const title = document.createElement('h3');
        title.className = 'vh360-overlay-title';

        const description = document.createElement('p');
        description.className = 'vh360-overlay-description';

        const userRole = config.appointment.userRole;

        switch (state) {
            case 'too_early':
                title.textContent = 'Session Not Open Yet';
                description.textContent = config.appointment.message || 'This session will open shortly before the scheduled time.';
                joinOverlay.style.display = 'flex';
                break;

            case 'waiting_for_host':
                title.textContent = 'Waiting for Professional';
                description.textContent = 'The professional will start the session shortly.';
                joinOverlay.style.display = 'flex';
                break;

            case 'ready_to_join':
                if (userRole === 'client') {
                    title.textContent = 'Join Session';
                    description.textContent = 'The professional is ready for you.';

                    const joinButton = document.createElement('button');
                    joinButton.id = 'vh360-join-livestream-btn';
                    joinButton.className = 'vh360-overlay-btn';
                    joinButton.textContent = 'Join Session';
                    overlayContent.appendChild(title);
                    overlayContent.appendChild(description);
                    overlayContent.appendChild(joinButton);

                    joinOverlay.style.display = 'flex';
                    return; // Button added, return early
                } else {
                    // Professional or admin
                    title.textContent = 'Session Ready';
                    description.textContent = 'Click to start the session.';
                }
                joinOverlay.style.display = 'flex';
                break;

            case 'active':
                // Session is active, overlay should be hidden
                joinOverlay.style.display = 'none';
                return;

            case 'ended':
                title.textContent = 'Session Ended';
                description.textContent = 'This appointment session has been completed.';
                joinOverlay.style.display = 'flex';
                break;

            default:
                // Unknown state, show generic waiting
                title.textContent = 'Appointment Session';
                description.textContent = config.appointment.message || 'Please wait...';
                joinOverlay.style.display = 'flex';
        }

        overlayContent.appendChild(title);
        overlayContent.appendChild(description);
    }

    // Function to request token from server.
    // The server is the single source of truth for role authorization.
    // Returns { token, role, message } or throws on error.
    async function requestTokenFromServer(channelName, uid, role, presenterPasscode) {
        const normalizedUid = Number(uid);

        if (!Number.isInteger(normalizedUid) || normalizedUid <= 0) {
            throw new Error('Invalid livestream session identity for token request.');
        }

        window.vh360Log('VideoHub360: Requesting token with role:', role);
        window.vh360Log('VideoHub360: Token request params:', { channelName, uid: normalizedUid, role });

        const formData = new FormData();
        formData.append('action', 'vh360_generate_agora_token');
        formData.append('nonce', vh360Data.agoraTokenNonce);
        formData.append('post_id', vh360Data.postId);
        formData.append('channel_name', channelName);
        formData.append('uid', String(normalizedUid));
        formData.append('uid_signature', config.uidSignature || '');
        formData.append('role', role || 'audience');
        // Only append passcode when requesting presenter/host access.
        formData.append('passcode', presenterPasscode || '');

        const response = await fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            window.vh360Log('VideoHub360: Token response received:', data.data);
            const approvedRole = data.data.role || role;

            if (data.data.token) {
                window.vh360Log('VideoHub360: Token generated with server-approved role:', approvedRole);
                return {
                    token: data.data.token,
                    role: approvedRole,
                    message: data.data.message || '',
                    uid: data.data.uid,
                    channel: data.data.channel,
                    expiresAt: data.data.expires_at || null,
                    roleInt: data.data.role_int || null
                };
            } else {
                // No token — development/tokenless mode (only when vh360_agora_require_tokens is disabled).
                window.vh360Log('VideoHub360: No token in response (development/tokenless mode).');
                return {
                    token: null,
                    role: approvedRole,
                    message: data.data.message || '',
                    uid: data.data.uid,
                    channel: data.data.channel,
                    expiresAt: data.data.expires_at || null,
                    roleInt: data.data.role_int || null
                };
            }
        } else {
            const errMsg = (typeof data.data === 'string' ? data.data : null) || 'Token request failed';
            window.vh360Error('VideoHub360: Token request failed:', errMsg);
            throw new Error(errMsg);
        }
    }

    function clearAgoraTokenRenewalTimer() {
        if (agoraTokenRenewalTimer) {
            clearTimeout(agoraTokenRenewalTimer);
            agoraTokenRenewalTimer = null;
        }
    }

    function scheduleAgoraTokenRenewal(expiresAt) {
        if (!expiresAt || !config.requireAgoraTokens) {
            return;
        }

        clearAgoraTokenRenewalTimer();

        const expiresAtMs = Number(expiresAt) * 1000;

        if (!Number.isFinite(expiresAtMs) || expiresAtMs <= 0) {
            window.vh360Warn('VideoHub360: Cannot schedule Agora token renewal because expiry metadata is invalid:', expiresAt);
            return;
        }

        const renewAtMs = expiresAtMs - (10 * 60 * 1000);
        const delay = Math.max(60 * 1000, renewAtMs - Date.now());

        agoraTokenRenewalTimer = setTimeout(async () => {
            const renewed = await renewAgoraToken('proactive-timer');

            if (renewed && latestAgoraTokenResponse && latestAgoraTokenResponse.expiresAt) {
                scheduleAgoraTokenRenewal(latestAgoraTokenResponse.expiresAt);
            }
        }, delay);
    }

    async function renewAgoraToken(reason = 'scheduled') {
        if (!client || !config.requireAgoraTokens) {
            return false;
        }

        if (agoraTokenRenewalInProgress) {
            window.vh360Log('VideoHub360: Agora token renewal already in progress, skipping duplicate request.');
            return false;
        }

        const renewalUid = Number(currentUserUID || config.uid);

        if (!Number.isInteger(renewalUid) || renewalUid <= 0) {
            window.vh360Warn('VideoHub360: Cannot renew Agora token because UID is invalid.');
            return false;
        }

        agoraTokenRenewalInProgress = true;

        try {
            window.vh360Log('VideoHub360: Renewing Agora token:', reason);

            const tokenResponse = await requestTokenFromServer(
                config.channelName,
                renewalUid,
                currentRole || 'audience'
            );

            latestAgoraTokenResponse = tokenResponse;

            if (!tokenResponse || !tokenResponse.token) {
                if (config.requireAgoraTokens) {
                    throw new Error('Token renewal failed because no token was issued.');
                }

                return false;
            }

            await client.renewToken(tokenResponse.token);

            if (tokenResponse.role === 'host') {
                hasServerApprovedPublishToken = true;
            } else if (currentRole === 'host') {
                window.vh360Warn('VideoHub360: Server did not renew host privileges; reverting to audience token state.');
                hasServerApprovedPublishToken = false;
                currentRole = tokenResponse.role || 'audience';

                if (localTracks.audioTrack || localTracks.videoTrack) {
                    await stopPublishing();
                }
            }

            if (tokenResponse.expiresAt) {
                scheduleAgoraTokenRenewal(tokenResponse.expiresAt);
            }

            window.vh360Log('VideoHub360: Agora token renewed successfully.');
            return true;
        } catch (error) {
            window.vh360Error('VideoHub360: Agora token renewal failed:', error);
            return false;
        } finally {
            agoraTokenRenewalInProgress = false;
        }
    }

    async function recoverExpiredAgoraToken() {
        if (!client || !config.requireAgoraTokens) {
            return false;
        }

        if (agoraTokenRenewalInProgress) {
            window.vh360Log('VideoHub360: Agora token request already in progress, skipping expired-token recovery duplicate.');
            return false;
        }

        const renewalUid = Number(currentUserUID || config.uid);

        if (!Number.isInteger(renewalUid) || renewalUid <= 0) {
            window.vh360Warn('VideoHub360: Cannot recover expired Agora token because UID is invalid.');
            return false;
        }

        const wasPublishing = currentRole === 'host' && (
            localTracks.audioTrack || localTracks.videoTrack
        );

        agoraTokenRenewalInProgress = true;

        try {
            const tokenResponse = await requestTokenFromServer(
                config.channelName,
                renewalUid,
                currentRole || 'audience'
            );

            latestAgoraTokenResponse = tokenResponse;

            if (!tokenResponse || !tokenResponse.token) {
                window.vh360Warn('VideoHub360: Expired token recovery failed because no token was issued.');
                return false;
            }

            beginAgoraSessionReplacement('expired-token-rejoin');
            let joinedUid;
            try {
                joinedUid = await client.join(
                    config.appId,
                    tokenResponse.channel || config.channelName,
                    tokenResponse.token,
                    Number(tokenResponse.uid || renewalUid)
                );
            } catch (joinError) {
                completeAgoraSessionReplacement(false);
                window.vh360Warn('VideoHub360: Expired token rejoin failed:', joinError);
                return false;
            }

            currentUserUID = Number(joinedUid || tokenResponse.uid || renewalUid);
            completeAgoraSessionReplacement(true);
            reconcileRemoteSubscriptions('token-rejoin');
            scheduleRemoteReconciliation('token-rejoin-stabilization', 750);
            enableVolumeIndication();
            startActiveSpeakerDetection();
            requestFeaturedStreamTypes();

            if (config.mode === 'live' && client && typeof client.setClientRole === 'function') {
                try {
                    await client.setClientRole(tokenResponse.role === 'host' ? 'host' : 'audience');
                } catch (roleError) {
                    window.vh360Warn('VideoHub360: Failed to restore client role after expired token rejoin:', roleError);
                }
            }

            if (tokenResponse.role === 'host') {
                hasServerApprovedPublishToken = true;
                currentRole = 'host';

                if (wasPublishing && !localTracks.audioTrack && !localTracks.videoTrack) {
                    await startPublishing();
                }
            } else {
                hasServerApprovedPublishToken = false;
                currentRole = tokenResponse.role || 'audience';

                if (localTracks.audioTrack || localTracks.videoTrack) {
                    await stopPublishing();
                }
            }

            if (tokenResponse.expiresAt) {
                scheduleAgoraTokenRenewal(tokenResponse.expiresAt);
            }

            window.vh360Log('VideoHub360: Expired Agora token recovery completed successfully.');
            return true;
        } catch (error) {
            window.vh360Error('VideoHub360: Expired Agora token recovery failed:', error);
            return false;
        } finally {
            agoraTokenRenewalInProgress = false;
        }
    }

    // Function to check user moderation status
    async function checkModerationStatus() {
        try {
            const formData = new FormData();
            formData.append('action', 'videohub360_check_moderation_status');
            formData.append('post_id', vh360Data.postId);

            // For guest users, send their Agora UID
            if (currentUserUID) {
                formData.append('agora_uid', currentUserUID);
            }

            const response = await fetch(vh360Data.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            // Check if user was disconnected during the request OR got auth error
            if (isDisconnected || isBeingModerated) {
                throw new Error('User disconnected');
            }

            // Check for HTTP errors (403/401 mean user is unauthorized/banned)
            if (response.status === 403 || response.status === 401) {
                throw new Error('User unauthorized');
            }

            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                window.vh360Error('VideoHub360: Moderation status check failed:', data.data);
                throw new Error(data.data || 'Moderation status check failed');
            }
        } catch (error) {
            // Only log if not disconnected (prevents console spam)
            if (!isDisconnected && !isBeingModerated) {
                window.vh360Error('VideoHub360: Moderation status check error:', error);
            }
            throw error;
        }
    }



    // Moderation polling intervals - store globally so they can be cleared
    let moderationPollingIntervals = [];
    let isDisconnected = false; // Flag to prevent polling after disconnect
    let periodicCheckRunning = false; // Guard to prevent multiple polling loops

    // Periodic moderation check for active participants
    function startPeriodicModerationCheck() {
        // Guard: Prevent multiple polling loops from running
        if (periodicCheckRunning) {
            window.vh360Log('Agora: Periodic moderation check already running, skipping');
            return;
        }
        periodicCheckRunning = true;
        // Ultra-aggressive moderation check - polling is critical for moderation detection
        let checkCount = 0;
        const rapidChecks = 20; // First 20 checks will be every 500ms = 10 seconds total
        const moderateChecks = 30; // Next 10 checks will be every 2 seconds = 20 seconds total

        const performCheck = async () => {
            if (currentUserUID && window.agoraClient && !isDisconnected && !isBeingModerated) {
                try {
                    const moderationStatus = await checkModerationStatus();

                    if (!moderationStatus.can_join_stream) {
                        const errorMessage = moderationStatus.message || 'You are no longer allowed in this stream.';
                        window.vh360Log('Agora: User is moderated - disconnecting');

                        // Apply visual freeze effect
                        const localPlayer = document.getElementById('vh360-agora-local-player');
                        const remotePlayersContainer = document.getElementById('vh360-agora-remote-players');

                        if (localPlayer) {
                            localPlayer.style.filter = 'grayscale(100%) blur(3px)';
                            localPlayer.style.opacity = '0.5';
                        }
                        if (remotePlayersContainer) {
                            remotePlayersContainer.style.filter = 'grayscale(100%) blur(3px)';
                            remotePlayersContainer.style.opacity = '0.5';
                        }

                        // Show notification and disconnect
                        showModerationNotification(errorMessage, moderationStatus.status === 'banned' ? 'error' : 'warning');
                        setTimeout(() => {
                            disconnectFromStream(errorMessage);
                        }, 500);
                    }
                } catch (error) {
                    // Silently ignore errors if user is disconnected (prevents console spam)
                    if (!isDisconnected && !isBeingModerated) {
                        window.vh360Warn('Agora: Moderation check failed:', error);
                    }
                }
            }
        };

        // Make the check function available for immediate triggering
        window.triggerImmediateModerationCheck = performCheck;

        // Immediate first check
        performCheck();

        // Rapid checks: Every 500ms for first 10 seconds
        const rapidInterval = setInterval(() => {
            checkCount++;
            performCheck();

            if (checkCount >= rapidChecks) {
                clearInterval(rapidInterval);

                // Moderate frequency: Every 2 seconds for next 20 seconds
                const moderateInterval = setInterval(() => {
                    checkCount++;
                    performCheck();

                    if (checkCount >= moderateChecks) {
                        clearInterval(moderateInterval);
                        // Regular checks every 10 seconds
                        const regularInterval = setInterval(performCheck, 10000);
                        moderationPollingIntervals.push(regularInterval);
                    }
                }, 2000);
                moderationPollingIntervals.push(moderateInterval);
            }
        }, 500);
        moderationPollingIntervals.push(rapidInterval);
    }

    async function joinChannel() {
        try {
            window.vh360Log('VideoHub360: joinChannel() called with currentRole:', currentRole);
            window.vh360Log('VideoHub360: Config role at join time:', config.role);
            window.vh360Log('VideoHub360: isHost flag:', isHost);
            window.vh360Log('VideoHub360: isOriginalHost flag:', isOriginalHost);
            window.vh360Log('VideoHub360: Mode:', config.agoraMode);

            window.vh360Log('Agora: Attempting to join channel', config.channelName, 'as', currentRole);

            // Check moderation status before joining (for all users)
            window.vh360Log('Agora: Checking moderation status before joining...');

            try {
                const moderationStatus = await checkModerationStatus();
                if (!moderationStatus.can_join_stream) {
                    const errorMessage = moderationStatus.message || 'You are not allowed to join this stream.';
                    showAgoraError(errorMessage);
                    return;
                }
            } catch (error) {
                window.vh360Warn('Agora: Failed to check moderation status, proceeding with join:', error);
                // Continue with join attempt even if moderation check fails
            }

            // Request token dynamically before joining.
            // requestTokenFromServer() throws on error; handle fail-closed below.
            const normalizedUid = Number(config.uid);

            if (!Number.isInteger(normalizedUid) || normalizedUid <= 0) {
                throw new Error('Missing or invalid livestream session identity.');
            }

            window.vh360Log('VideoHub360: Using Agora UID for join/token flow:', normalizedUid);

            let tokenResponse;
            try {
                tokenResponse = await requestTokenFromServer(config.channelName, normalizedUid, currentRole);
            } catch (tokenError) {
                if (config.requireAgoraTokens) {
                    // Fail closed: propagate the error so the outer catch shows it.
                    throw tokenError;
                }
                // Development mode only: allow tokenless join when tokens are not required.
                window.vh360Warn('VideoHub360: Token request failed, proceeding without token (dev mode):', tokenError);
                tokenResponse = {
                    token: null,
                    role: currentRole,
                    message: '',
                    uid: normalizedUid,
                    channel: config.channelName,
                    expiresAt: null,
                    roleInt: null
                };
            }

            const token = tokenResponse.token;
            latestAgoraTokenResponse = tokenResponse;

            // Fail closed: if tokens are required and none was issued, stop the join.
            if (config.requireAgoraTokens && !token) {
                throw new Error('Unable to join livestream because valid live connection authorization was not issued.');
            }

            // The server is the authority on the approved role.
            // If the server downgraded host to audience, respect that.
            if (currentRole === 'host' && tokenResponse.role !== 'host') {
                window.vh360Warn('VideoHub360: Server returned role', tokenResponse.role, '— downgrading from host. Will not publish.');
                currentRole = tokenResponse.role;
                isHost = false;
            }

            window.vh360Log('VideoHub360: Token received for role:', currentRole);

            // Additional verification: if we're supposed to be host, double-check before join
            if (currentRole === 'host' && config.mode === 'live') {
                window.vh360Log('VideoHub360: Verified host role before join - mode:', config.mode, 'role:', currentRole);
            }

            await configureInteractiveDualStream();
            const uid = await client.join(config.appId, config.channelName, token, normalizedUid);
            window.vh360Log('Agora: Successfully joined channel with UID:', uid);
            window.vh360Log('VideoHub360: Role after successful join:', currentRole);
            currentUserUID = uid; // Store the current user's UID for later use
            isAgoraSessionJoined = true;
            reconcileRemoteSubscriptions('joined');
            scheduleRemoteReconciliation('join-stabilization', 750);
            enableVolumeIndication();
            startActiveSpeakerDetection();
            startAgoraDiagnostics();
            requestFeaturedStreamTypes();
            resolveExistingRemoteIdentities();
            if (tokenResponse.expiresAt) {
                scheduleAgoraTokenRenewal(tokenResponse.expiresAt);
            }

            // If we are the original host, set our UID as the original host UID
            if (isOriginalHost) {
                originalHostUID = uid;
                window.vh360Log('VideoHub360: Current user is original host, setting originalHostUID:', originalHostUID);
            }

            // Start periodic moderation check for all users
            window.vh360Log('Agora: Starting periodic moderation check...');
            startPeriodicModerationCheck();

            // Initialize layout manager after successful join and controls setup
            // Initialize ViewLayoutManager for all modes and users
            // It handles fullscreen functionality which should be available to everyone
            if (!viewLayoutManager) {
                const isAdmin = isUserAdministrator();

                // Always initialize - fullscreen and other basic functionality needed by all users
                viewLayoutManager = new ViewLayoutManager(config.agoraMode, isAdmin);

                // Make layout manager globally accessible
                window.vh360LayoutManager = viewLayoutManager;
                window.viewLayoutManager = viewLayoutManager;
                window.vh360 = window.vh360 || {};
                window.vh360.viewLayoutManager = viewLayoutManager;

                // Ensure controls are still visible after layout manager setup
                setTimeout(() => {
                    updateControlsVisibility();
                }, 100);
            }

            // POTENTIAL FIX: In SDK v4 with live mode, we may need to explicitly set client role after join
            if (config.mode === 'live' && typeof client.setClientRole === 'function') {
                try {
                    window.vh360Log("VideoHub360: Setting explicit client role after join for live mode:", currentRole);
                    await client.setClientRole(currentRole);
                    window.vh360Log("VideoHub360: Client role set successfully after join to:", currentRole);
                } catch (roleError) {
                    window.vh360Warn("VideoHub360: Failed to set client role after join, continuing anyway:", roleError);
                }
            }

            await initDataStream();

            if (currentRole === "host") {
                window.vh360Log('VideoHub360: User is host, starting publishing...');
                // Server approved host role and the token was issued for host — mark publish as authorized.
                hasServerApprovedPublishToken = true;
                await startPublishing();
            } else {
                window.vh360Log('VideoHub360: User is audience, showing waiting message...');
                showAudienceWaitingMessage();
            }
            updateControlsVisibility();

            // Update mobile controls visibility after joining
            if (typeof window.updateMobileControlsVisibility === 'function') {
                window.updateMobileControlsVisibility();
            }
        } catch (error) {
            window.vh360Error('Agora: Join channel failed:', error);

            let errorMessage = "Failed to connect to livestream.";

            // Provide specific error messages based on error type
            if (error.code === 'INVALID_VENDOR_KEY') {
                errorMessage = "Invalid live connection settings. Please contact the administrator.";
            } else if (error.code === 'INVALID_CHANNEL_NAME') {
                errorMessage = "Invalid channel configuration. Please contact the administrator.";
            } else if (error.code === 'OPERATION_ABORTED' || error.code === 'NETWORK_ERROR') {
                errorMessage = "Network connection failed. Please check your internet connection and try again.";
            } else if (error.code === 'WEBSOCKET_DISCONNECTED') {
                errorMessage = "Connection lost. Please refresh the page and try again.";
            } else if (error.message && error.message.includes('WebSocket')) {
                errorMessage = "Connection failed. Please check your network and refresh the page.";
            } else if (error.message) {
                // Use the server or token error message directly (e.g. membership/access denial).
                errorMessage = error.message;
            } else {
                errorMessage = "Failed to connect to livestream. Please refresh and try again.";
            }

            showAgoraError(errorMessage);
        }
    }
    async function leaveChannel() {
        try {
            // Exit iOS immersive fullscreen if active before leaving
            if (isIOSImmersiveFullscreen) {
                exitIOSImmersiveFullscreen();
            }

            stopActiveSpeakerDetection();
            stopAgoraDiagnostics();
            unbindAutoplayFailureRecovery();
            const autoplayPrompt = document.getElementById('vh360-agora-autoplay-recovery');
            if (autoplayPrompt) autoplayPrompt.remove();
            document.removeEventListener('vh360:agora-focus-changed', handleAgoraLayoutStreamSelectionChange);
            document.removeEventListener('vh360:agora-layout-changed', handleAgoraLayoutStreamSelectionChange);

            // Tear down layout controls/listeners before leaving or clearing player UI.
            destroyViewLayoutManager();

            // Stop stream status polling
            stopStreamStatusPolling();

            clearAgoraTokenRenewalTimer();
            isAgoraSessionJoined = false;
            resetRemoteSubscriptionSession('channel-leave');
            latestAgoraTokenResponse = null;
            agoraTokenRecoveryInProgress = false;

            // Clean up voice-activated switching
            if (isVolumenIndicationEnabled) {
                client.off("volume-indicator", handleVolumeIndication);
                isVolumenIndicationEnabled = false;
            }
            clearAllParticipantTiles();

            await stopPublishing();
            hasServerApprovedPublishToken = false;
            isAgoraSessionJoined = false;
            await client.leave();
            const localPlayer = document.getElementById("vh360-agora-local-player");
            if (localPlayer) {
                localPlayer.textContent = '';
                const disconnectedMessage = document.createElement('div');
                disconnectedMessage.className = 'vh360-agora-status-message';
                disconnectedMessage.style.cssText = 'display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 1.2em; background: #333;';
                disconnectedMessage.textContent = 'Disconnected';
                localPlayer.appendChild(disconnectedMessage);
            }
        } catch (error) {}
    }
    function showAgoraError(message) {
        const localPlayer = document.getElementById("vh360-agora-local-player");
        let errorOverlay = document.getElementById('agora-error-overlay');
        if (!errorOverlay) {
            errorOverlay = document.createElement('div');
            errorOverlay.id = 'agora-error-overlay';
            errorOverlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.9);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 15;
                text-align: center;
                padding: 20px;
                font-size: 1em;
                line-height: 1.4;
            `;
            localPlayer.appendChild(errorOverlay);
        }
        errorOverlay.innerHTML = `
            <div>
                <div style="color: #ff6b6b; margin-bottom: 12px; font-size: 1.2em;">⚠️ Error</div>
                <div style="margin-bottom: 16px;">${message}</div>
                <button onclick="this.parentElement.parentElement.style.display='none'"
                        style="background: #4CAF50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                    Dismiss
                </button>
            </div>
        `;
        errorOverlay.style.display = 'flex';
        setTimeout(() => {
            if (errorOverlay && errorOverlay.style.display !== 'none') {
                errorOverlay.style.display = 'none';
            }
        }, 10000);
    }

    // Stream status polling for audience users
    let streamStatusPollInterval = null;
    let isStreamStatusPollingActive = false;

    function shouldPollStreamStatus() {
        return config.studioControlled ? !isOriginalHost : (!isOriginalHost && currentRole === 'audience');
    }

    function startStreamStatusPolling() {
        if (shouldPollStreamStatus() && !isStreamStatusPollingActive) {
            isStreamStatusPollingActive = true;
            streamStatusPollInterval = setInterval(checkStreamStatus, 3000); // Poll every 3 seconds
            checkStreamStatus(); // Check immediately
            window.vh360Log('VideoHub360: Started stream status polling');
        }
    }

    function stopStreamStatusPolling() {
        if (streamStatusPollInterval) {
            clearInterval(streamStatusPollInterval);
            streamStatusPollInterval = null;
        }
        isStreamStatusPollingActive = false;
        window.vh360Log('VideoHub360: Stopped stream status polling');
    }

    // Pause polling when page is not visible to save resources
    function handleVisibilityChange() {
        if (document.hidden) {
            if (isStreamStatusPollingActive && streamStatusPollInterval) {
                clearInterval(streamStatusPollInterval);
                streamStatusPollInterval = null;
                window.vh360Log('VideoHub360: Paused stream status polling (page hidden)');
            }
        } else {
            if (client && currentUserUID && isAgoraSessionJoined && client.connectionState === 'CONNECTED') {
                scheduleRemoteReconciliation('visibility-resume', 500);
                startActiveSpeakerDetection();
                requestFeaturedStreamTypes();
            }
            if (isStreamStatusPollingActive && !streamStatusPollInterval) {
                streamStatusPollInterval = setInterval(checkStreamStatus, 3000);
                checkStreamStatus(); // Check immediately when page becomes visible
                window.vh360Log('VideoHub360: Resumed stream status polling (page visible)');
            }
        }
    }

    function handleAgoraLayoutStreamSelectionChange() {
        setTimeout(requestFeaturedStreamTypes, 0);
    }

    // Listen for visibility and layout/focus changes that alter the featured participant.
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('vh360:agora-focus-changed', handleAgoraLayoutStreamSelectionChange);
    document.addEventListener('vh360:agora-layout-changed', handleAgoraLayoutStreamSelectionChange);

    function checkStreamStatus() {
        if (!vh360Data || !vh360Data.postId) return;

        fetchStreamStatus()
        .then(function(data) {
            if (data.success) {
                if (data.data.stream_stopped === true) {
                    window.vh360Log('VideoHub360: Stream detected as stopped');
                    handleStreamEndedFromServer(data.data);
                    return;
                }

                if (data.data.is_live) {
                    // Stream is now live
                    window.vh360Log('VideoHub360: Stream detected as live');
                    stopStreamStatusPolling();

                    // Check if this is an appointment room
                    if (config.appointment && config.appointment.isAppointment) {
                        // Appointment-specific behavior
                        const userRole = config.appointment.userRole;

                        if (userRole === 'client') {
                            // For appointment clients: show "Join Session" button, do NOT auto-join
                            window.vh360Log('VideoHub360: Appointment client - showing Join Session button');
                            updateAppointmentOverlay('ready_to_join');

                            // Do NOT mark stream as started or update controls yet
                            // Client must click "Join Session" button first
                            // vh360StreamStarted will be set when client joins via button click

                            // Do NOT hide overlay or auto-join for appointment clients
                            // Client must click "Join Session" button
                        } else {
                            // Professional or admin in appointment - can auto-join
                            window.vh360Log('VideoHub360: Appointment professional/admin - auto-joining');

                            if (joinOverlay) {
                                joinOverlay.style.display = 'none';
                            }

                            window.vh360StreamStarted = true;
                            const mobileControls = document.getElementById('vh360-agora-controls');
                            if (mobileControls) {
                                mobileControls.style.display = 'flex';
                            }

                            updateControlsVisibility();

                            setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Connecting to session...</div></div></div>');

                            joinChannel().catch(error => {
                                window.vh360Error('Failed to join session:', error);
                                showAgoraError('Failed to connect to session. Please refresh and try again.');
                            });
                        }
                    } else {
                        // Regular (non-appointment) livestream - keep original behavior
                        window.vh360Log('VideoHub360: Regular livestream - auto-joining');

                        // Hide the join overlay
                        if (joinOverlay) {
                            joinOverlay.style.display = 'none';
                        }

                        // Mark stream as started and show controls now that live stream has begun
                        window.vh360StreamStarted = true;
                        const mobileControls = document.getElementById('vh360-agora-controls');
                        if (mobileControls) {
                            mobileControls.style.display = 'flex';
                        }

                        // Update desktop controls visibility for broadcast mode hosts
                        updateControlsVisibility();

                        // Show loading message
                        setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Connecting to livestream...</div></div></div>');

                        // Start the connection
                        joinChannel().catch(error => {
                            window.vh360Error('Failed to join livestream:', error);
                            showAgoraError('Failed to connect to livestream. Please refresh and try again.');
                        });
                    }
                } else {
                    // Stream is not live yet, keep polling
                    // Optionally update the waiting message
                    const localPlayer = document.getElementById("vh360-agora-local-player");
                    if (localPlayer && !localPlayer.querySelector('.waiting-message')) {
                        showAudienceWaitingMessage();
                    }

                    // For appointment clients, update overlay state
                    if (config.appointment && config.appointment.isAppointment && config.appointment.userRole === 'client') {
                        updateAppointmentOverlay('waiting_for_host');
                    }
                }
            } else {
                window.vh360Warn('VideoHub360: Stream status check failed:', data.data);
            }
        })
        .catch(function(error) {
            window.vh360Error('VideoHub360: Stream status check error:', error);
            // Continue polling even on error - temporary network issues shouldn't stop the process
        });
    }

    function setStreamStatus(status) {
        if (!vh360Data || !vh360Data.postId) return Promise.reject('No post ID available');

        window.vh360Log('VideoHub360: Setting stream status to:', status);

        var formData = new FormData();
        formData.append('action', 'vh360_set_stream_status');
        formData.append('nonce', vh360Data.agoraTokenNonce);
        formData.append('post_id', vh360Data.postId);
        formData.append('status', status);

        return fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                window.vh360Log('VideoHub360: Stream status updated successfully to:', status);
                return data;
            } else {
                throw new Error(data.data || 'Failed to update stream status');
            }
        })
        .catch(function(error) {
            window.vh360Error('VideoHub360: Failed to set stream status:', error);
            throw error;
        });
    }

    // Simple join livestream button functionality
    function handleJoinLivestream() {
        window.vh360Log('VideoHub360: handleJoinLivestream() called');
        if (config.studioControlled && config.agoraMode !== 'interactive') {
            window.vh360Log('Studio-controlled broadcast stream: public page publishing disabled.');
        }
        window.vh360Log('VideoHub360: Current role at button click:', currentRole);
        window.vh360Log('VideoHub360: isOriginalHost:', isOriginalHost);
        window.vh360Log('VideoHub360: Config:', config);

        // Check if login is required based on admin setting and everyone-is-host mode
        var loginRequired = true;

        // If everyone-is-host mode is enabled and admin setting allows guest join, login is not required
        if (allowEveryoneHostForThisViewer && vh360Data.forceLoginEveryoneHost == 0) {
            loginRequired = false;
            window.vh360Log('VideoHub360: Guest join allowed - everyone-is-host mode enabled and login not required by admin setting');
        }

        if (!security.is_logged_in && loginRequired) {
            window.vh360Log('VideoHub360: Redirecting to login - user not logged in and login is required');
            window.location.href = vh360Data.userLoginUrl;
            return;
        }
        if (config.studioControlled && config.agoraMode !== 'interactive') {
            if (joinOverlay) {
                joinOverlay.style.display = 'none';
            }
            window.vh360StreamStarted = true;
            setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div>Connecting to livestream...</div></div>');
            joinChannel().catch(error => {
                window.vh360Error('Failed to join Studio-controlled livestream:', error);
                showAgoraError('Failed to connect to livestream. Please refresh and try again.');
            });
        } else if (isOriginalHost) {
            setStreamStatus('yes').then(function(data) {
                // PATCH: Update badges and "Started x ago" instantly
                if (data && data.success && data.data && data.data.live_start_time) {
                    // Show live badge
                    var liveBadge = document.querySelector('.videohub360-live-badge');
                    if (liveBadge) liveBadge.style.display = 'inline-block';
                    // Show watching now badge
                    var viewersBadge = document.querySelector('.videohub360-live-viewers');
                    if (viewersBadge) viewersBadge.style.display = 'inline-block';
                    // Set the start time meta and start its timer
                    var startMeta = document.getElementById('vh360-stream-started-meta');
                    if (startMeta) {
                        startMeta.dataset.start = data.data.live_start_time;
                        startMeta.style.display = 'inline-block';
                        function updateStarted() {
                            var start = startMeta.dataset.start;
                            var startTime = new Date(start.replace(' ', 'T'));
                            var now = new Date();
                            var diffMs = now - startTime;
                            if (isNaN(diffMs) || diffMs < 0) {
                                startMeta.textContent = '';
                                return;
                            }
                            var totalSeconds = Math.floor(diffMs / 1000);
                            var totalMinutes = Math.floor(totalSeconds / 60);
                            var hours = Math.floor(totalMinutes / 60);
                            var days = Math.floor(hours / 24);
                            var display = 'Started streaming ';
                            if (days > 0) display += days + ' day' + (days > 1 ? 's' : '') + ' ago';
                            else if (hours > 0) display += hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
                            else if (totalMinutes > 0) display += totalMinutes + ' minute' + (totalMinutes > 1 ? 's' : '') + ' ago';
                            else display += 'just now';
                            startMeta.textContent = display;
                        }
                        updateStarted();
                        setInterval(updateStarted, 60000);
                    }
                }
                // Hide the join overlay
                if (joinOverlay) {
                    joinOverlay.style.display = 'none';
                }

                // Mark stream as started and show controls now that live stream has begun
                window.vh360StreamStarted = true;
                const mobileControls = document.getElementById('vh360-agora-controls');
                if (mobileControls) {
                    mobileControls.style.display = 'flex';
                }

                // Update desktop controls visibility for broadcast mode hosts
                updateControlsVisibility();

                // Show loading message
                setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Starting livestream...</div></div></div>');
                joinChannel().catch(error => {
                    window.vh360Error('Failed to start livestream:', error);
                    showAgoraError('Failed to start livestream. Please refresh and try again.');
                    setStreamStatus('no');
                });
            }).catch(function(error) {
                window.vh360Error('Failed to set stream status:', error);
                // Check if the error is because stream has been ended
                var errorMessage = error.message || error.toString();
                if (errorMessage.includes('Stream has been ended') || errorMessage.includes('stream has been ended')) {
                    // Display stream ended message instead of error
                    const needsRestartHtml = vh360Data?.livestreamMessages?.endedNeedsRestartHtml;
                    setLocalPlayerStatusHTML((needsRestartHtml && needsRestartHtml.trim())
                        ? needsRestartHtml
                        : '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #fff; font-size: 1.2em; background: #333; flex-direction: column;"><div>📴 Stream Ended</div><div style="font-size: 0.8em; margin-top: 8px; text-align: center;">This stream has ended. The host needs to restart it to go live again.</div></div>');
                    // Hide join overlay if it exists
                    if (joinOverlay) {
                        joinOverlay.style.display = 'none';
                    }
                } else {
                    showAgoraError('Failed to start livestream. Please try again.');
                }
            });
        } else {
            // Check if everyone should join as hosts in interactive mode (Zoom-style)
            if (allowEveryoneHostForThisViewer) {
                window.vh360Log('VideoHub360: Everyone-as-host mode enabled for this viewer, joining immediately as host');
                if (joinOverlay) {
                    joinOverlay.style.display = 'none';
                }

                // Mark stream as started and show controls now that live stream has begun
                window.vh360StreamStarted = true;
                const mobileControls = document.getElementById('vh360-agora-controls');
                if (mobileControls) {
                    mobileControls.style.display = 'flex';
                }

                // Update desktop controls visibility for broadcast mode hosts
                updateControlsVisibility();

                setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Joining as host...</div></div></div>');
                // Join immediately as host without waiting for stream status
                joinChannel().catch(error => {
                    window.vh360Error('Failed to join livestream as host:', error);
                    showAgoraError('Failed to join livestream. Please refresh and try again.');
                });
            } else if (config.appointment && config.appointment.isAppointment && config.appointment.canPublish) {
                // Appointment participant who can publish (professional or client in active session)
                // SECURITY: canPublish is determined server-side in videohub360.php based on:
                // - Professional (room owner): always true
                // - Client (booked user): true only when session status is 'active'
                // - Others: false (not included in appointment context)
                // This ensures only authorized participants can publish during appropriate times
                window.vh360Log('VideoHub360: Appointment participant with publish permission, promoting to host');
                window.vh360Log('VideoHub360: Appointment context:', config.appointment);

                // Promote to host role for appointment publishing
                // This allows client to call startPublishing() and access camera/mic controls
                currentRole = 'host';
                isHost = true;

                window.vh360Log('VideoHub360: Promoted appointment participant - currentRole:', currentRole, 'isHost:', isHost);

                if (joinOverlay) {
                    joinOverlay.style.display = 'none';
                }

                // Mark stream as started and show controls
                window.vh360StreamStarted = true;
                const mobileControls = document.getElementById('vh360-agora-controls');
                if (mobileControls) {
                    mobileControls.style.display = 'flex';
                }

                // Update desktop controls visibility
                updateControlsVisibility();

                setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Joining appointment session...</div></div></div>');

                // Join as host with publish capability
                joinChannel().catch(error => {
                    window.vh360Error('Failed to join appointment session:', error);
                    showAgoraError('Failed to join appointment. Please refresh and try again.');
                });
            } else {
                // Standard behavior: start polling for stream status or join directly
                if (joinOverlay) {
                    joinOverlay.style.display = 'none';
                }

                // Mark stream as started and show controls now that live stream has begun
                window.vh360StreamStarted = true;
                const mobileControls = document.getElementById('vh360-agora-controls');
                if (mobileControls) {
                    mobileControls.style.display = 'flex';
                }

                // Update desktop controls visibility for broadcast mode hosts
                updateControlsVisibility();

                setLocalPlayerStatusHTML('<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#fff;font-size:1.2em;"><div style="text-align:center;"><div style="margin-bottom:12px;">🔄</div><div>Connecting to livestream...</div></div></div>');
                joinChannel().catch(error => {
                    window.vh360Error('Failed to join livestream:', error);
                    showAgoraError('Failed to connect to livestream. Please refresh and try again.');
                });
            }
        }
    }

    function getEventTargetClosest(eventTarget, selector) {
        if (!eventTarget) return null;
        if (eventTarget instanceof Element) {
            return eventTarget.closest(selector);
        }
        if (eventTarget.parentElement instanceof Element) {
            return eventTarget.parentElement.closest(selector);
        }
        return null;
    }

    // Add event listener for join button using event delegation
    // This handles both the initial button and any dynamically recreated buttons (e.g., appointment "Join Session")
    document.addEventListener('click', function(e) {
        const btn = getEventTargetClosest(e.target, '#vh360-join-livestream-btn');
        if (!btn) return;

        window.vh360Log('VideoHub360: Join button clicked (via delegation)');
        handleJoinLivestream();
    });

    // Add hover effects using event delegation
    document.addEventListener('mouseenter', function(e) {
        const btn = getEventTargetClosest(e.target, '#vh360-join-livestream-btn');
        if (!btn) return;
        btn.style.background = '#d32f2f';
    }, true);

    document.addEventListener('mouseleave', function(e) {
        const btn = getEventTargetClosest(e.target, '#vh360-join-livestream-btn');
        if (!btn) return;
        btn.style.background = '#e53935';
    }, true);

    // Don't auto-join anymore - wait for user to click the button OR stream status changes
    // The old code was: joinChannel();
    // Now we show the join overlay for hosts and start polling for audience
    // UNLESS allowEveryoneIsHost is enabled in interactive mode

    // Initialize appointment overlay state if this is an appointment
    if (config.appointment && config.appointment.isAppointment) {
        window.vh360Log('VideoHub360: Appointment room detected, role:', config.appointment.userRole, 'status:', config.appointment.status);

        if (config.appointment.userRole === 'client') {
            // Client in appointment - set initial overlay state based on session status
            const status = config.appointment.status;

            if (status === 'too_early') {
                updateAppointmentOverlay('too_early');
            } else if (status === 'waiting_for_host' || status === 'ready') {
                updateAppointmentOverlay('waiting_for_host');
                // Start polling to detect when professional starts
                startStreamStatusPolling();
            } else if (status === 'active') {
                // Session is already active
                updateAppointmentOverlay('ready_to_join');
            } else if (status === 'ended') {
                updateAppointmentOverlay('ended');
            } else {
                // Default waiting state
                updateAppointmentOverlay('waiting_for_host');
                startStreamStatusPolling();
            }
        } else if (isOriginalHost) {
            // Professional sees the "Start Session" button
            window.vh360Log('VideoHub360: Professional in appointment, waiting for Start Session button click');
        } else {
            // Admin or other authorized user
            window.vh360Log('VideoHub360: Admin in appointment room');
            startStreamStatusPolling();
        }
    } else if (isOriginalHost) {
        // Host sees the "Start Live Stream" button and waits for click
        window.vh360Log('VideoHub360: Host detected, waiting for Start Live Stream button click');
    } else if (allowEveryoneHostForThisViewer) {
        // In everyone-as-host mode with interactive streaming, eligible users can join immediately.
        window.vh360Log('VideoHub360: Everyone-as-host mode enabled for this viewer, showing join button for immediate host access');
        // Keep the join overlay visible so users can click to join as hosts
    } else {
        // Audience users start polling for stream status
        window.vh360Log('VideoHub360: Audience user detected, starting stream status polling');
        startStreamStatusPolling();
    }

    // Debug/cleanup global
    window.remoteUsers = remoteUsers;
    window.vh360AgoraPlayer = {
        client,
        localTracks,
        remoteUsers,
        config,
        currentRole: () => currentRole,
        isHost: () => isHost,
        leaveChannel,
        startPublishing,
        stopPublishing,
        startStreamStatusPolling,
        stopStreamStatusPolling,
        setStreamStatus,
        // Voice-activated switching functions
        enableVolumeIndication,
        activeSpeaker: () => activeSpeakerUid,
        setActiveSpeaker,
        isVolumeIndicationEnabled: () => isVolumenIndicationEnabled,
        volumeThreshold,
        switchingCooldown
    };
    window.vh360AgoraState = {
        isJoined: () => isAgoraSessionJoined,
        currentRole: () => currentRole,
        activeSpeaker: () => activeSpeakerUid,
        localTracks: () => localTracks
    };

    // Initialize debug flag for view transition logging
    // Set window.__VH360_DEBUG = true in console to enable debug logging
    if (typeof window.__VH360_DEBUG === 'undefined') {
        window.__VH360_DEBUG = false;
    }

    // Mobile Zoom-style controls with auto-hide functionality
        if (window.innerWidth <= 768) {
            setTimeout(function() {
                if (!window.vh360MobileControlsInitialized) {
                    initializeMobileControls();
                }
            }, 1000);
        }

        // Also check on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                setTimeout(function() {
                    if (!window.vh360MobileControlsInitialized) {
                        initializeMobileControls();
                    }
                }, 100);
            } else {
                // Reset initialization flag when switching to desktop
                window.vh360MobileControlsInitialized = false;
            }
        });

    // Initialize simplified mobile controls
    function initializeMobileControls() {
        // Use simplified mobile controls implementation
        const mobileConfig = {
            agoraMode: config.agoraMode,
            currentRole: currentRole,
            isHost: isHost,
            isOriginalHost: isOriginalHost,
            canModerate: canModerate
        };

        const result = initializeSimplifiedMobileControls(mobileConfig);

        // Always try to bind mobile fullscreen events on mobile devices, regardless of layout manager state
        if (window.innerWidth <= 768) {
            window.vh360Log('VideoHub360: Mobile device detected, ensuring mobile fullscreen binding...');

            // Use multiple attempts to ensure binding happens
            setTimeout(() => bindMobileFullscreenEvents(), 50);
            setTimeout(() => bindMobileFullscreenEvents(), 200);
            setTimeout(() => bindMobileFullscreenEvents(), 500);
        }

        return result;
    }

    // Helper function to bind fullscreen events specifically for mobile
    function bindMobileFullscreenEvents() {
        window.vh360Log('VideoHub360 Mobile: bindMobileFullscreenEvents() called, window width:', window.innerWidth);

        // Find the existing fullscreen button created in PHP
        const fullscreenBtn = document.getElementById('vh360-agora-fullscreen-btn');

        if (!fullscreenBtn) {
            window.vh360Log('VideoHub360 Mobile: No fullscreen button found to bind events');
            return;
        }

        // Check if already has mobile event listeners (to avoid double binding)
        if (fullscreenBtn.dataset.mobileEventsbound === 'true') {
            window.vh360Log('VideoHub360 Mobile: Mobile fullscreen events already bound');
            return;
        }

        // Check if desktop events are bound - if so, remove them for mobile
        if (fullscreenBtn.dataset.desktopEventsbound === 'true') {
            window.vh360Log('VideoHub360 Mobile: Desktop events detected, removing for mobile binding');
            // Clone the button to remove all event listeners
            const newButton = fullscreenBtn.cloneNode(true);
            fullscreenBtn.parentNode.replaceChild(newButton, fullscreenBtn);
            // Update reference
            const refreshedBtn = document.getElementById('vh360-agora-fullscreen-btn');
            if (refreshedBtn) {
                refreshedBtn.dataset.desktopEventsbound = 'false';
            }
        }

        // Re-get button reference in case it was cloned
        const mobileFullscreenBtn = document.getElementById('vh360-agora-fullscreen-btn');
        if (!mobileFullscreenBtn) {
            window.vh360Log('VideoHub360 Mobile: Button not found after cleanup');
            return;
        }

        // Check if the standard fullscreen API is supported. Normally, if it's not
        // supported we would hide the fullscreen button. However, on iOS devices
        // we still show the button because we can use native video fullscreen
        // methods even when the Fullscreen API is unavailable.
        if (!ViewLayoutManager.isFullscreenSupported()) {
            const isiOS = vh360IsIOSDevice();
            if (!isiOS) {
                window.vh360Log('VideoHub360 Mobile: Fullscreen API not supported, hiding fullscreen button');
                mobileFullscreenBtn.style.display = 'none';
                return;
            }
        }

        window.vh360Log('VideoHub360 Mobile: Binding mobile fullscreen button events...');

        // Add click handler with error handling
        mobileFullscreenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.vh360Log('VideoHub360 Mobile: Mobile fullscreen button clicked');
            toggleMobileFullscreen();
        });

        // Mark as bound to avoid double binding
        mobileFullscreenBtn.dataset.mobileEventsbound = 'true';

        // Note: Fullscreen change event listeners are now centralized in ViewLayoutManager
        // to avoid duplication. The updateMobileFullscreenButton function is exposed globally
        // so ViewLayoutManager can call it from centralized event listeners.

        window.vh360Log('VideoHub360 Mobile: Mobile fullscreen button events bound successfully');
    }

    // Mobile-specific fullscreen toggle function
    function toggleMobileFullscreen() {
        window.vh360Log('VideoHub360 Mobile: toggleMobileFullscreen() called');

        // Determine if we are on an iOS device (including iPadOS reporting as Mac)
        const isiOS = vh360IsIOSDevice();
        // Determine if we should restrict fullscreen to broadcast mode
        // Use the local `config` passed to initializeAgoraPlayer rather than
        // expecting a global window.config. When this function executes inside
        // initializeAgoraPlayer, `config` is available via closure. Referencing
        // window.config caused `undefined` on iOS and prevented broadcast
        // detection, so use the captured config directly.
        const isBroadcast = config && config.agoraMode === 'broadcast';
        const isAgoraBroadcast = isAgoraBroadcastMode();

        // On iOS, keep Agora interactive users and every Agora broadcast role inside our
        // custom container fullscreen. Native video fullscreen exposes browser
        // play/pause controls that can leave live broadcasts paused on exit.
        if (isiOS) {
            window.vh360Log(
                isAgoraBroadcast
                    ? 'VideoHub360 Mobile: iOS Agora broadcast – bypassing native video fullscreen for custom immersive fullscreen'
                    : 'VideoHub360 Mobile: iOS interactive mode – using immersive fullscreen'
            );
            toggleIOSImmersiveFullscreen();
            return;
        }

        // If we're already in fullscreen, attempt to exit regardless of platform
        if (window.isInFullscreen && window.isInFullscreen()) {
            window.vh360Log('VideoHub360 Mobile: Currently in fullscreen, attempting to exit...');
            // Attempt to exit using the generic exitFullscreen API if available
            if (window.exitFullscreen) {
                window.exitFullscreen().then(() => {
                    updateMobileFullscreenButton(false);
                    teardownBroadcastFullscreenPresentation();
                    resumeAgoraBroadcastPlayback('standard-fullscreen-exit');
                    window.vh360Log('VideoHub360 Mobile: Exited fullscreen successfully');
                }).catch((err) => {
                    window.vh360Error('VideoHub360 Mobile: Failed to exit fullscreen:', err);
                    updateMobileFullscreenButton(false);
                    teardownBroadcastFullscreenPresentation();
                    resumeAgoraBroadcastPlayback('standard-fullscreen-exit-error');
                });
            }
            return;
        }

        // At this point we know we're not currently fullscreen. Agora broadcast
        // mobile users of every role are routed through custom container fullscreen;
        // native video fullscreen is intentionally bypassed to avoid browser
        // play/pause behavior when exiting live broadcasts.

        // For non-iOS devices (or iOS outside broadcast), use the standard Fullscreen API
        // First find the best element to fullscreen
        let targetElement = null;
        const containerSelectors = [
            '#vh360-agora-player',
            '.vh360-multi-view-container',
            '.vh360-video-container',
            '.videohub360-container',
            '#videohub360-main-container',
            '.vh360-container'
        ];
        for (const selector of containerSelectors) {
            const el = document.querySelector(selector);
            if (el && el.offsetWidth > 0 && el.offsetHeight > 0) {
                targetElement = el;
                window.vh360Log(`VideoHub360 Mobile: Found fullscreen target element: ${selector}`);
                break;
            }
        }
        // Fallback to document.body if no element found
        if (!targetElement) {
            targetElement = document.body;
            window.vh360Log('VideoHub360 Mobile: Using document.body as fullscreen target fallback');
        }
        // If still not found, show error
        if (!targetElement) {
            window.vh360Error('VideoHub360 Mobile: No suitable element found for fullscreen');
            alert('Video player not found for fullscreen mode.');
            return;
        }
        // Attempt to enter fullscreen on the chosen element
        try {
            // Indicate we're attempting fullscreen to prevent double clicks
            targetElement.classList.add('vh360-entering-fullscreen');
            if (isAgoraBroadcast && targetElement.id === 'vh360-agora-player') {
                setupBroadcastFullscreenPresentation(targetElement, { nativeFullscreenBypassed: isiOS && isBroadcast });
            }
            // Use standard API; do not delay for user gesture compliance
            window.enterFullscreen(targetElement).then(() => {
                targetElement.classList.remove('vh360-entering-fullscreen');
                updateMobileFullscreenButton(true);
                window.vh360Log('VideoHub360 Mobile: Entered fullscreen successfully');
            }).catch((err) => {
                targetElement.classList.remove('vh360-entering-fullscreen');
                teardownBroadcastFullscreenPresentation();
                window.vh360Error('VideoHub360 Mobile: Failed to enter fullscreen:', err);
                let errorMessage = 'Fullscreen failed. ';
                if (err.name === 'NotAllowedError') {
                    errorMessage += 'Please try tapping the fullscreen button again.';
                } else if (err.name === 'TypeError') {
                    errorMessage += 'Fullscreen not available for this content.';
                } else {
                    errorMessage += 'Please try again.';
                }
                alert(errorMessage);
                window.vh360Log('VideoHub360 Mobile: Fullscreen error shown to user:', errorMessage);
            });
        } catch (error) {
            window.vh360Error('VideoHub360 Mobile: Unexpected error in toggleMobileFullscreen:', error);
            alert('An error occurred while trying to toggle fullscreen.');
        }
    }

    function isAgoraBroadcastMode() {
        return !!(config && config.agoraMode === 'broadcast');
    }

    function getAgoraBroadcastFullscreenPlayer() {
        return document.getElementById('vh360-agora-player');
    }

    function getActiveBroadcastVideoPath(player) {
        if (!player) {
            return null;
        }

        const ignoredSelector = '.vh360-participant-placeholder, .waiting-message, .vh360-waiting-message, .vh360-agora-stage-status, .vh360-agora-status-message, [hidden], [aria-hidden="true"]';
        const candidates = Array.from(player.querySelectorAll('video, canvas, .agora_video_player, [class*="agora_video"]'));
        const visibleCandidate = candidates.find((candidate) => {
            if (!candidate || candidate.closest(ignoredSelector)) {
                return false;
            }

            const style = window.getComputedStyle(candidate);
            if (!style || style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
                return false;
            }

            const rect = candidate.getBoundingClientRect();
            if (rect.width <= 0 || rect.height <= 0) {
                return false;
            }

            if (candidate.tagName && candidate.tagName.toLowerCase() === 'video') {
                return !candidate.paused || candidate.readyState > 0 || candidate.videoWidth > 0 || candidate.videoHeight > 0;
            }

            return true;
        });

        if (!visibleCandidate) {
            return null;
        }

        const activeVideo = visibleCandidate.closest('.agora_video_player, [class*="agora_video"]') || visibleCandidate;
        const videoRoot = activeVideo.closest('.vh360-participant-video, .vh360-participant-tile, .vh360-video-player, .vh360-video-container, .vh360-agora-video-container, .vh360-video-main, .vh360-video-remote, [id^="player-video-"], [id^="player-"]') || activeVideo.parentElement || activeVideo;
        const stage = activeVideo.closest('#vh360-agora-local-player, #vh360-agora-remote-players, .vh360-multi-view-container, .vh360-agora-video-area') || videoRoot;

        return { activeVideo, videoRoot, stage };
    }

    function clearBroadcastFullscreenVideoPath(player) {
        if (!player) {
            return;
        }

        player.querySelectorAll('.vh360-broadcast-fullscreen-active-video, .vh360-broadcast-fullscreen-video-root, .vh360-broadcast-fullscreen-stage').forEach((element) => {
            element.classList.remove(
                'vh360-broadcast-fullscreen-active-video',
                'vh360-broadcast-fullscreen-video-root',
                'vh360-broadcast-fullscreen-stage'
            );
        });
    }

    function markBroadcastFullscreenVideoPath(player) {
        clearBroadcastFullscreenVideoPath(player);

        const path = getActiveBroadcastVideoPath(player);
        if (path) {
            path.activeVideo.classList.add('vh360-broadcast-fullscreen-active-video');
            path.videoRoot.classList.add('vh360-broadcast-fullscreen-video-root');
            path.stage.classList.add('vh360-broadcast-fullscreen-stage');
        }

        window.vh360Log('VideoHub360 Mobile: Broadcast fullscreen video path selection', {
            currentRole,
            isOriginalHost,
            agoraMode: config && config.agoraMode,
            foundActiveVideo: !!path,
            activeVideo: path && (path.activeVideo.id || path.activeVideo.className || path.activeVideo.tagName),
            videoRoot: path && (path.videoRoot.id || path.videoRoot.className || path.videoRoot.tagName),
            stage: path && (path.stage.id || path.stage.className || path.stage.tagName)
        });

        return path;
    }

    function setBroadcastFullscreenControlsVisible(isVisible) {
        const player = getAgoraBroadcastFullscreenPlayer();
        if (!player || !player.classList.contains('vh360-broadcast-fullscreen')) {
            return;
        }

        player.classList.toggle('vh360-broadcast-controls-visible', !!isVisible);
        player.classList.toggle('vh360-broadcast-controls-hidden', !isVisible);
        document.body.classList.toggle('vh360-broadcast-controls-visible', !!isVisible);
        document.body.classList.toggle('vh360-broadcast-controls-hidden', !isVisible);

        if (broadcastFullscreenControlsTimer) {
            clearTimeout(broadcastFullscreenControlsTimer);
            broadcastFullscreenControlsTimer = null;
        }

        if (isVisible) {
            broadcastFullscreenControlsTimer = setTimeout(() => {
                setBroadcastFullscreenControlsVisible(false);
            }, 2800);
        }
    }

    function setupBroadcastFullscreenPresentation(player, options = {}) {
        if (!isAgoraBroadcastMode() || !player) {
            return;
        }

        player.classList.add('vh360-broadcast-fullscreen');
        document.body.classList.add('vh360-broadcast-fullscreen-active');
        markBroadcastFullscreenVideoPath(player);
        window.vh360Log('VideoHub360 Mobile: Broadcast custom fullscreen presentation setup', {
            currentRole,
            isOriginalHost,
            agoraMode: config && config.agoraMode,
            nativeFullscreenBypassed: !!options.nativeFullscreenBypassed
        });
        setBroadcastFullscreenControlsVisible(true);

        if (broadcastFullscreenTapHandler) {
            player.removeEventListener('click', broadcastFullscreenTapHandler);
        }

        broadcastFullscreenTapHandler = (event) => {
            if (event.target.closest('button, a, input, select, textarea, [role="button"], #vh360-ios-immersive-exit-btn')) {
                return;
            }

            setBroadcastFullscreenControlsVisible(
                !player.classList.contains('vh360-broadcast-controls-visible')
            );
        };

        player.addEventListener('click', broadcastFullscreenTapHandler);
    }

    function teardownBroadcastFullscreenPresentation() {
        const player = getAgoraBroadcastFullscreenPlayer();

        if (broadcastFullscreenControlsTimer) {
            clearTimeout(broadcastFullscreenControlsTimer);
            broadcastFullscreenControlsTimer = null;
        }

        if (player && broadcastFullscreenTapHandler) {
            player.removeEventListener('click', broadcastFullscreenTapHandler);
        }

        broadcastFullscreenTapHandler = null;

        if (player) {
            clearBroadcastFullscreenVideoPath(player);
            player.classList.remove(
                'vh360-broadcast-fullscreen',
                'vh360-broadcast-controls-visible',
                'vh360-broadcast-controls-hidden'
            );
        }

        document.body.classList.remove(
            'vh360-broadcast-fullscreen-active',
            'vh360-broadcast-controls-visible',
            'vh360-broadcast-controls-hidden'
        );
    }

    function resumeAgoraBroadcastPlayback(reason) {
        if (!isAgoraBroadcastMode()) {
            return;
        }

        const player = document.getElementById('vh360-agora-player');
        if (!player) {
            return;
        }

        const videos = Array.from(player.querySelectorAll('video'));
        videos.forEach((video) => {
            if (!video || !video.paused || typeof video.play !== 'function') {
                return;
            }

            const rect = video.getBoundingClientRect();
            const style = window.getComputedStyle(video);
            if (rect.width <= 0 || rect.height <= 0 || style.display === 'none' || style.visibility === 'hidden') {
                return;
            }

            try {
                const playResult = video.play();
                if (playResult && typeof playResult.catch === 'function') {
                    playResult.catch((error) => {
                        window.vh360Warn('VideoHub360 Mobile: Agora broadcast playback resume was rejected', {
                            reason,
                            error
                        });
                    });
                }
            } catch (error) {
                window.vh360Warn('VideoHub360 Mobile: Unable to resume Agora broadcast playback', {
                    reason,
                    error
                });
            }
        });
    }

    function handleAgoraBroadcastFullscreenChange() {
        const isStandardFullscreen = window.isInFullscreen && window.isInFullscreen();
        if (!isStandardFullscreen && !isIOSImmersiveFullscreen) {
            teardownBroadcastFullscreenPresentation();
            resumeAgoraBroadcastPlayback('fullscreenchange-exit');
        }
    }

    document.addEventListener('fullscreenchange', handleAgoraBroadcastFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleAgoraBroadcastFullscreenChange);

    // Update fullscreen button appearance for mobile
    // Exposed globally so ViewLayoutManager can call it from centralized event listeners
    window.updateMobileFullscreenButton = function(isFullscreen) {
        const fullscreenBtn = document.getElementById('vh360-agora-fullscreen-btn');
        if (!fullscreenBtn) return;

        const svg = fullscreenBtn.querySelector('svg');
        if (!svg) return;

        if (isFullscreen) {
            // Exit fullscreen icon - standardized
            svg.innerHTML = '<path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>';
            fullscreenBtn.title = 'Exit fullscreen';
        } else {
            // Enter fullscreen icon - standardized
            svg.innerHTML = '<path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>';
            fullscreenBtn.title = 'Toggle fullscreen';
        }
    }

    // == iOS Immersive Fullscreen Helpers ==

    function updateIOSImmersiveFullscreenButton(isActive) {
        const btn = document.getElementById('vh360-agora-fullscreen-btn');
        if (!btn) {
            return;
        }
        btn.classList.toggle('active', !!isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        btn.setAttribute('title', isActive ? 'Exit fullscreen' : 'Fullscreen');
        btn.setAttribute('aria-label', isActive ? 'Exit fullscreen' : 'Fullscreen');

        const svg = btn.querySelector('svg');
        if (svg) {
            if (isActive) {
                svg.innerHTML = '<path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>';
            } else {
                svg.innerHTML = '<path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>';
            }
        }
    }

    function portalIOSImmersivePlayer(player) {
        if (!player || player.parentNode === document.body) {
            return;
        }

        iosImmersiveOriginalParent = player.parentNode;

        iosImmersivePlaceholder = document.createElement('div');
        iosImmersivePlaceholder.id = 'vh360-ios-immersive-placeholder';
        iosImmersivePlaceholder.setAttribute('aria-hidden', 'true');
        iosImmersivePlaceholder.style.display = 'none';

        iosImmersiveOriginalParent.insertBefore(iosImmersivePlaceholder, player);

        document.body.appendChild(player);

        player.classList.add('vh360-ios-immersive-portaled');

        window.vh360Log('VideoHub360 iOS Immersive: Player moved to body portal.');
    }

    function restoreIOSImmersivePlayer(player) {
        if (!player) {
            return;
        }

        if (iosImmersiveOriginalParent && iosImmersivePlaceholder) {
            iosImmersiveOriginalParent.insertBefore(player, iosImmersivePlaceholder);
            iosImmersivePlaceholder.remove();

            window.vh360Log('VideoHub360 iOS Immersive: Player restored from body portal.');
        }

        player.classList.remove('vh360-ios-immersive-portaled');

        iosImmersiveOriginalParent = null;
        iosImmersivePlaceholder = null;
    }

    function ensureIOSImmersiveExitButton() {
        var exitBtn = document.getElementById('vh360-ios-immersive-exit-btn');

        if (exitBtn) {
            exitBtn.style.display = 'flex';
            return;
        }

        exitBtn = document.createElement('button');
        exitBtn.id = 'vh360-ios-immersive-exit-btn';
        exitBtn.type = 'button';
        exitBtn.setAttribute('aria-label', 'Exit fullscreen');
        exitBtn.innerHTML = '&times;';
        exitBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            exitIOSImmersiveFullscreen();
        });

        document.body.appendChild(exitBtn);
    }

    function getIOSImmersiveViewportDimensions() {
        const visualViewport = window.visualViewport;
        const documentElement = document.documentElement;
        const body = document.body;
        const fallbackWidth = window.innerWidth || documentElement.clientWidth || (body ? body.clientWidth : 0);
        const fallbackHeight = window.innerHeight || documentElement.clientHeight || (body ? body.clientHeight : 0);

        return {
            width: (visualViewport && visualViewport.width) || fallbackWidth,
            height: (visualViewport && visualViewport.height) || fallbackHeight
        };
    }

    function cancelIOSImmersiveViewportSynchronization() {
        if (iosImmersiveViewportAnimationFrame !== null) {
            window.cancelAnimationFrame(iosImmersiveViewportAnimationFrame);
            iosImmersiveViewportAnimationFrame = null;
        }

        iosImmersiveViewportSyncTimers.forEach((timer) => window.clearTimeout(timer));
        iosImmersiveViewportSyncTimers = [];
    }

    function synchronizeIOSImmersiveViewport() {
        if (!isIOSImmersiveFullscreen) {
            return;
        }

        const player = document.getElementById('vh360-agora-player');
        if (!player) {
            return;
        }

        const viewport = getIOSImmersiveViewportDimensions();
        player.style.setProperty('--vh360-visual-viewport-width', viewport.width + 'px');
        player.style.setProperty('--vh360-visual-viewport-height', viewport.height + 'px');
        updateIOSImmersiveOrientationClass(player, viewport.width, viewport.height);
        updateThumbnailRailLayoutMode(getParticipantStage());

        if (typeof window.vh360RefreshFeaturedParticipantTiles === 'function') {
            window.vh360RefreshFeaturedParticipantTiles();
        }
        scheduleThumbnailRailOverflowUpdate();
    }

    function scheduleIOSImmersiveViewportSynchronization() {
        cancelIOSImmersiveViewportSynchronization();
        synchronizeIOSImmersiveViewport();

        iosImmersiveViewportAnimationFrame = window.requestAnimationFrame(() => {
            iosImmersiveViewportAnimationFrame = null;
            synchronizeIOSImmersiveViewport();
        });

        [100, 250].forEach((delay) => {
            const timer = window.setTimeout(() => {
                iosImmersiveViewportSyncTimers = iosImmersiveViewportSyncTimers.filter((pendingTimer) => pendingTimer !== timer);
                synchronizeIOSImmersiveViewport();
            }, delay);
            iosImmersiveViewportSyncTimers.push(timer);
        });
    }

    function enterIOSImmersiveFullscreen() {
        const player = document.getElementById('vh360-agora-player');
        if (!player) {
            window.vh360Warn('VideoHub360 iOS Immersive: Player container not found.');
            return;
        }
        if (isIOSImmersiveFullscreen) {
            return;
        }

        iosImmersiveScrollY = window.scrollY || document.documentElement.scrollTop || 0;
        iosImmersivePreviousActiveElement = document.activeElement;

        portalIOSImmersivePlayer(player);

        document.documentElement.classList.add('vh360-ios-immersive-active');
        document.body.classList.add('vh360-ios-immersive-active');
        player.classList.add('vh360-ios-immersive-fullscreen');
        if (isAgoraBroadcastMode()) {
            setupBroadcastFullscreenPresentation(player, { nativeFullscreenBypassed: true });
        }

        document.body.style.top = '-' + iosImmersiveScrollY + 'px';

        isIOSImmersiveFullscreen = true;

        scheduleIOSImmersiveViewportSynchronization();

        updateIOSImmersiveFullscreenButton(true);

        ensureIOSImmersiveExitButton();

        window.dispatchEvent(new Event('resize'));
        window.dispatchEvent(new Event('vh360:fullscreenchange'));

        window.vh360Log('VideoHub360 iOS Immersive: Entered immersive fullscreen');
    }

    function exitIOSImmersiveFullscreen() {
        if (!isIOSImmersiveFullscreen) {
            return;
        }

        const player = document.getElementById('vh360-agora-player');

        cancelIOSImmersiveViewportSynchronization();

        document.documentElement.classList.remove('vh360-ios-immersive-active');
        document.body.classList.remove('vh360-ios-immersive-active');

        if (player) {
            clearBroadcastFullscreenVideoPath(player);
            player.style.removeProperty('--vh360-visual-viewport-width');
            player.style.removeProperty('--vh360-visual-viewport-height');
            player.classList.remove(
                'vh360-ios-immersive-fullscreen',
                'vh360-ios-immersive-portaled',
                'vh360-ios-immersive-landscape',
                'vh360-ios-immersive-portrait'
            );
        }

        document.documentElement.style.removeProperty('--vh360-visual-viewport-height');

        teardownBroadcastFullscreenPresentation();

        restoreIOSImmersivePlayer(player);

        document.body.style.top = '';

        window.scrollTo(0, iosImmersiveScrollY || 0);

        isIOSImmersiveFullscreen = false;

        updateIOSImmersiveFullscreenButton(false);

        var exitBtn = document.getElementById('vh360-ios-immersive-exit-btn');
        if (exitBtn) {
            exitBtn.style.display = 'none';
        }

        if (
            iosImmersivePreviousActiveElement &&
            typeof iosImmersivePreviousActiveElement.focus === 'function'
        ) {
            try {
                iosImmersivePreviousActiveElement.focus({ preventScroll: true });
            } catch (focusErr) {
                // Ignore focus restoration errors.
            }
        }

        iosImmersivePreviousActiveElement = null;

        window.dispatchEvent(new Event('resize'));
        window.dispatchEvent(new Event('vh360:fullscreenchange'));

        resumeAgoraBroadcastPlayback('ios-immersive-exit');

        window.vh360Log('VideoHub360 iOS Immersive: Exited immersive fullscreen');
    }

    function toggleIOSImmersiveFullscreen() {
        if (isIOSImmersiveFullscreen) {
            exitIOSImmersiveFullscreen();
        } else {
            enterIOSImmersiveFullscreen();
        }
    }

    function updateIOSImmersiveOrientationClass(player, viewportWidth, viewportHeight) {
        if (!player || !isIOSImmersiveFullscreen) {
            return;
        }

        var isLandscape = viewportWidth !== viewportHeight
            ? viewportWidth > viewportHeight
            : window.matchMedia('(orientation: landscape)').matches;

        player.classList.toggle('vh360-ios-immersive-landscape', isLandscape);
        player.classList.toggle('vh360-ios-immersive-portrait', !isLandscape);

        window.vh360Log('VideoHub360 iOS Immersive: Orientation layout updated', {
            isLandscape: isLandscape,
            viewportWidth: viewportWidth,
            viewportHeight: viewportHeight
        });
    }

    function handleIOSImmersiveViewportEvent() {
        if (!isIOSImmersiveFullscreen) {
            return;
        }
        scheduleIOSImmersiveViewportSynchronization();
    }

    window.addEventListener('resize', handleIOSImmersiveViewportEvent);
    window.addEventListener('orientationchange', handleIOSImmersiveViewportEvent);

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', handleIOSImmersiveViewportEvent);
        window.visualViewport.addEventListener('scroll', handleIOSImmersiveViewportEvent);
    }

    // Function to update mobile controls visibility when role changes
    function updateMobileControlsVisibility() {
        // Use simplified mobile controls if available, otherwise fallback to legacy behavior
        if (window.vh360SimplifiedMobileControls) {
            // Update the config with current values
            window.vh360SimplifiedMobileControls.config = {
                agoraMode: config.agoraMode,
                currentRole: currentRole,
                isHost: isHost,
                isOriginalHost: isOriginalHost,
                canModerate: canModerate
            };
            window.vh360SimplifiedMobileControls.updateVisibility();
            return;
        }

        // Legacy fallback - should not be needed with simplified implementation
        if (window.innerWidth > 768) {
            return; // Not mobile
        }

        const controlsContainer = document.getElementById('vh360-agora-controls');
        if (!controlsContainer) {
            return;
        }

        // Don't show controls until stream has actually started
        if (!window.vh360StreamStarted) {
            controlsContainer.style.display = 'none';
            window.vh360Log('Legacy controls: Stream not started yet, keeping controls hidden');
            return;
        }

        if (!shouldShowControlsForUser()) {
            controlsContainer.style.display = 'none';
        } else {
            controlsContainer.style.display = 'flex';
        }
    }

    // Make updateMobileControlsVisibility globally available
    window.updateMobileControlsVisibility = updateMobileControlsVisibility;

    // Listen for quality changes from the quality management system
    document.addEventListener('vh360:qualityChanged', async (event) => {
        const { quality, qualityData } = event.detail;
        window.vh360Log('Quality change requested:', quality, qualityData);

        // Only update if we're currently streaming
        if (localTracks.videoTrack && currentRole === 'host') {
            try {
                await updateLiveStreamQuality(quality, qualityData);
            } catch (error) {
                window.vh360Error('Failed to update live stream quality:', error);
                // Show user-friendly error message
                if (typeof showAgoraError === 'function') {
                    showAgoraError('Failed to change stream quality. Please try again.');
                }
            }
        } else {
            window.vh360Log('Quality preference saved for next stream session');
        }
    });
};

// == Desktop Auto-Hide Controls for Interactive Mode ==
function initializeDesktopAutoHideControls() {
    // Only apply to desktop devices (>768px)
    if (window.innerWidth <= 768) {
        return;
    }

    const multiViewContainer = document.querySelector('.vh360-multi-view-container');
    const videoArea = document.querySelector('#vh360-agora-local-player');

    if (!multiViewContainer || !videoArea) {
        return;
    }

    let autoHideTimeout;

    // Function to show controls
    function showControls() {
        multiViewContainer.classList.remove('vh360-controls-hidden');
        resetAutoHideTimer();
    }

    // Function to hide controls
    function hideControls() {
        multiViewContainer.classList.add('vh360-controls-hidden');
        clearTimeout(autoHideTimeout);
    }

    // Function to reset the auto-hide timer
    function resetAutoHideTimer() {
        clearTimeout(autoHideTimeout);
        autoHideTimeout = setTimeout(hideControls, 3000); // Hide after 3 seconds
    }

    // Show controls when video area is clicked
    videoArea.addEventListener('click', function(e) {
        // Prevent event from bubbling to avoid conflicts
        e.stopPropagation();
        showControls();
    });

    // Show controls on mouse movement within the container
    multiViewContainer.addEventListener('mousemove', showControls);

    // Show controls when hovering over control elements themselves
    const controlsContainer = document.querySelector('#vh360-agora-controls, .vh360-agora-controls');
    if (controlsContainer) {
        controlsContainer.addEventListener('mouseenter', function() {
            clearTimeout(autoHideTimeout);
            multiViewContainer.classList.remove('vh360-controls-hidden');
        });

        controlsContainer.addEventListener('mouseleave', resetAutoHideTimer);
    }

    // Start the auto-hide timer initially
    resetAutoHideTimer();

    window.vh360Log('Desktop auto-hide controls initialized');
}

// Initialize desktop auto-hide when ViewLayoutManager is ready
document.addEventListener('DOMContentLoaded', function() {
    // Delay initialization to ensure ViewLayoutManager has created the layout
    setTimeout(function() {
        if (window.innerWidth > 768 && document.querySelector('.vh360-multi-view-container')) {
            initializeDesktopAutoHideControls();
        }
    }, 1500);
});

// Also initialize on window resize if switching to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && document.querySelector('.vh360-multi-view-container')) {
        setTimeout(initializeDesktopAutoHideControls, 500);
    }
});

// == Global Fullscreen Handler for Participant Dropdowns ==
// This handles moving all participant dropdown menus when entering/exiting fullscreen
(function() {
    const agoraPlayer = document.getElementById('vh360-agora-player');
    if (!agoraPlayer) {
        return; // No Agora player found
    }

    // Helper function to check if we're in fullscreen mode
    function isInFullscreenMode() {
        return !!(document.fullscreenElement ||
                 document.webkitFullscreenElement ||
                 document.mozFullScreenElement ||
                 document.msFullscreenElement);
    }

    // Helper function to handle fullscreen change for all participant dropdowns
    function handleParticipantDropdownsFullscreen() {
        const allDropdowns = document.querySelectorAll('.vh360-participant-dropdown');

        if (isInFullscreenMode()) {
            // Entering fullscreen - move all dropdowns inside the fullscreen element
            const fullscreenElement = document.fullscreenElement ||
                                   document.webkitFullscreenElement ||
                                   document.mozFullScreenElement ||
                                   document.msFullscreenElement;

            if (fullscreenElement && fullscreenElement.id === 'vh360-agora-player') {
                allDropdowns.forEach(dropdown => {
                    if (dropdown.parentNode !== fullscreenElement) {
                        fullscreenElement.appendChild(dropdown);
                    }
                });
                if (window.__VH360_DEBUG && allDropdowns.length > 0) {
                    window.vh360Log(`VideoHub360: ${allDropdowns.length} participant dropdown(s) moved inside fullscreen element`);
                }
            }
        } else {
            // Exiting fullscreen - move all dropdowns back to document.body
            allDropdowns.forEach(dropdown => {
                if (dropdown.parentNode !== document.body) {
                    document.body.appendChild(dropdown);
                }
            });
            if (window.__VH360_DEBUG && allDropdowns.length > 0) {
                window.vh360Log(`VideoHub360: ${allDropdowns.length} participant dropdown(s) moved back to document.body`);
            }
        }
    }

    // Listen for all fullscreen change events (cross-browser)
    document.addEventListener('fullscreenchange', handleParticipantDropdownsFullscreen);
    document.addEventListener('webkitfullscreenchange', handleParticipantDropdownsFullscreen);
    document.addEventListener('mozfullscreenchange', handleParticipantDropdownsFullscreen);
    document.addEventListener('MSFullscreenChange', handleParticipantDropdownsFullscreen);

    window.vh360Log('VideoHub360: Global fullscreen change listeners added for participant dropdowns');
})();



/* === VideoHub360: Unified Auto‑Hide for Agora Controls (desktop & mobile, broadcast & interactive) === */
(function() {
    try {
        const root = document.getElementById('vh360-agora-player') || document.querySelector('.vh360-multi-view-container');
        if (!root) return;
        const controls = root.querySelector('#vh360-agora-controls, .vh360-agora-controls');
        if (!controls) return;

        let hideT = null;
        const HIDE_AFTER_MS = 3000;
        const HIDDEN_CLASS = 'vh360-controls-hidden';

        function show() {
            root.classList.remove(HIDDEN_CLASS);
            reset();
        }
        function hide() {
            root.classList.add(HIDDEN_CLASS);
        }
        function reset() {
            if (hideT) clearTimeout(hideT);
            hideT = setTimeout(hide, HIDE_AFTER_MS);
        }
        // Interaction events that should reveal controls
        const reveal = () => show();
        ['mousemove','pointermove','touchstart','click','keydown','wheel'].forEach(evt => {
            root.addEventListener(evt, reveal, {passive: true});
        });

        // Keep visible while hovering over the controls themselves
        controls.addEventListener('mouseenter', () => {
            if (hideT) clearTimeout(hideT);
        });
        controls.addEventListener('mouseleave', reset);

        // Initialize
        show();

        // Reinitialize on resize (layout can change between broadcast/interactive)
        window.addEventListener('resize', () => {
            // simple reset on resize
            show();
        });

        window.vh360Log('VideoHub360: Unified auto-hide initialized');
    } catch (e) {
        if (window && window.__VH360_DEBUG) window.vh360Warn('VideoHub360: auto-hide init error', e);
    }
})();

/* === VideoHub360: Unified Auto-Hide for Agora Controls (desktop & mobile, broadcast & interactive) === */
(function() {
    try {
        const root = document.getElementById('vh360-agora-player') || document.querySelector('.vh360-multi-view-container');
        if (!root) return;
        const controls = root.querySelector('#vh360-agora-controls, .vh360-agora-controls');
        if (!controls) return;

        let hideT = null;
        const HIDE_AFTER_MS = 3000;
        const HIDDEN_CLASS = 'vh360-controls-hidden';
        const LIVE_CLASS = 'vh360-stream-live';

        function show() {
            if (!root.classList.contains(LIVE_CLASS)) return;
            root.classList.remove(HIDDEN_CLASS);
            reset();
        }
        function hide() { root.classList.add(HIDDEN_CLASS); }
        function reset() {
            if (hideT) clearTimeout(hideT);
            hideT = setTimeout(hide, HIDE_AFTER_MS);
        }

        root.classList.add(HIDDEN_CLASS);

        const reveal = () => show();
        ['mousemove','pointermove','touchstart','click','keydown','wheel'].forEach(evt => {
            root.addEventListener(evt, reveal, {passive: true});
        });

        controls.addEventListener('mouseenter', () => { if (hideT) clearTimeout(hideT); });
        controls.addEventListener('mouseleave', reset);

        function markLiveAndShow() {
            if (!root.classList.contains(LIVE_CLASS)) {
                root.classList.add(LIVE_CLASS);
                show();
            } else {
                show();
            }
        }

        function watchVideoEl(video) {
            if (!video || video.__vh360_watched) return;
            video.__vh360_watched = true;
            if ((video.readyState || 0) >= 2 && !video.paused) markLiveAndShow();
            video.addEventListener('playing', markLiveAndShow, { once: true });
            video.addEventListener('loadeddata', () => { if (!video.paused) markLiveAndShow(); });
        }

        const mo = new MutationObserver((muts) => {
            muts.forEach(m => {
                m.addedNodes && m.addedNodes.forEach(node => {
                    if (node.tagName === 'VIDEO') watchVideoEl(node);
                    else if (node.querySelectorAll) node.querySelectorAll('video').forEach(watchVideoEl);
                });
            });
        });
        mo.observe(root, { childList: true, subtree: true });

        root.querySelectorAll('video').forEach(watchVideoEl);
        root.addEventListener('vh360:agora:stream-live', markLiveAndShow);
        window.addEventListener('resize', () => { show(); });
        window.vh360Log('VideoHub360: Unified auto-hide + prestart hide initialized (appended)');
    } catch (e) {
        if (window && window.__VH360_DEBUG) window.vh360Warn('VideoHub360: auto-hide init error', e);
    }
})();
