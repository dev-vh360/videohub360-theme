
// == Share Modal Logic ==
/**
 * VideoHub360 frontend script
 *
 * This file powers much of the runtime behaviour for VideoHub360.  A
 * number of debug statements are scattered throughout the codebase to
 * aid development and troubleshooting.  To prevent those messages
 * from cluttering end‑user browser consoles in production, this
 * wrapper checks for a global `__VH360_DEBUG` flag.  When the flag is
 * undefined or falsy, it temporarily overrides `console.log` and
 * `console.warn` so that debug statements become no‑ops.  Errors
 * (`console.error`) are left untouched so that critical issues still
 * surface during execution.
 */

// Debug logging helpers - only log when __VH360_DEBUG is enabled
// Define on window to avoid redeclaration errors when multiple scripts load
if (typeof window !== 'undefined') {
  window.vh360IsDebug = window.vh360IsDebug || (() =>
    (typeof window !== 'undefined' && window.__VH360_DEBUG === true));

  window.vh360Log = window.vh360Log || ((...args) => {
    if (window.vh360IsDebug()) {
      console.log(...args);
    }
  });

  window.vh360Warn = window.vh360Warn || ((...args) => {
    if (window.vh360IsDebug()) {
      console.warn(...args);
    }
  });
}

(function() {
    // Guard against double initialization
    if (window.__vh360ShareModalInitialized) {
        return;
    }
    window.__vh360ShareModalInitialized = true;


    var shareBtn = document.getElementById('videohub360-share-btn');
    var modalOverlay = document.getElementById('videohub360-modal-overlay');
    var modalClose = document.getElementById('videohub360-modal-close');
    var copyBtn = document.getElementById('videohub360-copy-btn');
    var linkInput = document.getElementById('videohub360-link-input');
    var emailForm = document.getElementById('videohub360-email-form');
    var emailInput = document.getElementById('videohub360-email-input');
    var fromNameInput = document.getElementById('videohub360-from-name-input');
    var messageInput = document.getElementById('videohub360-message-input');
    var sendBtn = document.getElementById('videohub360-send-btn');

    /**
     * Detect if the current device is running iOS. This helper checks the user
     * agent for iPhone/iPad/iPod identifiers and also accounts for iPadOS 13+
     * devices which report themselves as "Mac" but expose touch events. We
     * intentionally avoid caching the result in case the page is loaded in
     * different contexts (e.g. inside an iframe) where the user agent string
     * could differ. Using a dedicated helper keeps the detection logic in one
     * place and makes it easy to update in the future.
     *
     * @returns {boolean} True if the device appears to be running iOS.
     */
    function vh360IsIOSDevice() {
        try {
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            // Standard iOS detection (iPhone, iPad, iPod)
            const iOSIdentifiers = /iPad|iPhone|iPod/;
            // iPad on iOS 13+ reports itself as Mac; use touch support to detect
            const isiPadOS13 = ua.includes('Mac') && 'ontouchend' in document;
            return iOSIdentifiers.test(ua) || isiPadOS13;
        } catch (e) {
            return false;
        }
    }

    // Expose the iOS detection helper globally so that other modules and
    // closures (e.g. bindMobileFullscreenEvents in the Agora code) can
    // reference it. Without this assignment, vh360IsIOSDevice remains scoped
    // to this IIFE and calls outside of this closure will result in a
    // ReferenceError, as observed on iOS when the fullscreen button was
    // clicked. Attaching it to the window makes it accessible globally.
    window.vh360IsIOSDevice = vh360IsIOSDevice;
    var emailMessage = document.getElementById('videohub360-email-message');
    var emailToggle = document.getElementById('videohub360-email-toggle');
    var emailFormContainer = document.getElementById('videohub360-email-form-container');

    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    function closeModal() {
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) closeModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) closeModal();
    });
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function() {
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                copyBtn.textContent = 'Copied!';
                copyBtn.classList.add('copied');
                setTimeout(function() {
                    copyBtn.textContent = 'Copy';
                    copyBtn.classList.remove('copied');
                }, 2000);
            } catch (err) {
                window.vh360Error('Failed to copy: ', err);
            }
        });
    }

    // Email form toggle functionality
    if (emailToggle && emailFormContainer) {
        emailToggle.addEventListener('click', function() {
            var isExpanded = emailFormContainer.classList.contains('expanded');

            if (isExpanded) {
                emailFormContainer.classList.remove('expanded');
                emailToggle.classList.remove('expanded');
            } else {
                emailFormContainer.classList.add('expanded');
                emailToggle.classList.add('expanded');
                // Focus on first input when expanded
                setTimeout(function() {
                    if (fromNameInput) fromNameInput.focus();
                }, 300);
            }
        });
    }

    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var email = emailInput.value.trim();
            var fromName = fromNameInput.value.trim();
            var message = messageInput.value.trim();

            if (!email) {
                showEmailMessage('Please enter an email address.', 'error');
                return;
            }
            if (!fromName) {
                showEmailMessage('Please enter your name.', 'error');
                return;
            }

            // Provide default message if none provided
            if (!message) {
                message = 'Check out this video I thought you might enjoy!';
            }

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            var formData = new FormData();
            formData.append('action', 'videohub360_share_email');
            formData.append('to_email', email);
            formData.append('from_name', fromName);
            formData.append('message', message);
            formData.append('post_id', vh360Data.postId);
            formData.append('nonce', vh360Data.shareEmailNonce);
            fetch(vh360Data.ajaxUrl, { method: 'POST', body: formData })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    showEmailMessage(data.data || 'Email sent successfully!', 'success');
                    emailInput.value = '';
                    messageInput.value = 'Check out this video I thought you might enjoy!'; // Reset to default

                    // Change button text to indicate success
                    sendBtn.textContent = 'Sent!';

                    // Auto-close modal after showing success message briefly
                    setTimeout(function() {
                        closeModal();
                    }, 1500);
                } else {
                    // Enhanced error handling with specific messages
                    let errorMsg = data.data || 'Failed to send email. Please try again.';

                    // Provide more helpful error messages
                    if (errorMsg.includes('Rate limit')) {
                        showEmailMessage(errorMsg, 'warning');
                    } else if (errorMsg.includes('SMTP')) {
                        showEmailMessage(errorMsg + ' Check the email configuration in admin settings.', 'error');
                    } else if (errorMsg.includes('configuration error')) {
                        showEmailMessage(errorMsg + ' Please contact the site administrator.', 'error');
                    } else {
                        showEmailMessage(errorMsg, 'error');
                    }
                }
            })
            .catch(function(error) {
                window.vh360Error('Email sharing error:', error);
                showEmailMessage('Network error while sending email. Please check your connection and try again.', 'error');
            })
            .finally(function() {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send Link';
            });
        });
    }
    function showEmailMessage(message, type) {
        emailMessage.textContent = message;
        emailMessage.className = 'videohub360-email-message ' + type;
        emailMessage.style.display = 'block';

        // Add accessibility attribute for screen readers
        emailMessage.setAttribute('role', 'alert');
        emailMessage.setAttribute('aria-live', 'polite');

        // Ensure email form container is expanded so message is visible
        if (emailFormContainer && !emailFormContainer.classList.contains('expanded')) {
            emailFormContainer.classList.add('expanded');
            emailToggle.classList.add('expanded');
        }

        // Different timeout for different message types
        let timeout = 5000; // Default 5 seconds
        if (type === 'warning') timeout = 8000; // 8 seconds for warnings
        if (type === 'error') timeout = 10000; // 10 seconds for errors

        setTimeout(function() {
            emailMessage.style.display = 'none';
            emailMessage.removeAttribute('role');
            emailMessage.removeAttribute('aria-live');
        }, timeout);
    }
})();

// == Apply Custom Badge Colors ==
/**
 * Apply custom badge colors from data attributes
 * This follows CodeCanyon best practices by avoiding inline styles
 */
(function applyCustomBadgeColors() {
    'use strict';

    function applyBadgeColors() {
        const badges = document.querySelectorAll('.videohub360-live-badge[data-badge-color]');
        badges.forEach(function(badge) {
            const customColor = badge.getAttribute('data-badge-color');
            if (customColor) {
                badge.style.backgroundColor = customColor;
            }
        });
    }

    // Apply on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyBadgeColors);
    } else {
        applyBadgeColors();
    }

    // Also observe for dynamically added badges
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && node.classList.contains('videohub360-live-badge') && node.hasAttribute('data-badge-color')) {
                            const customColor = node.getAttribute('data-badge-color');
                            if (customColor) {
                                node.style.backgroundColor = customColor;
                            }
                        }
                        // Check children
                        const badges = node.querySelectorAll && node.querySelectorAll('.videohub360-live-badge[data-badge-color]');
                        if (badges) {
                            badges.forEach(function(badge) {
                                const customColor = badge.getAttribute('data-badge-color');
                                if (customColor) {
                                    badge.style.backgroundColor = customColor;
                                }
                            });
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    window.vh360Log('VideoHub360: Custom badge color handler initialized');
})();

// == Video Reactions (Like/Dislike) Logic ==
(function() {
    'use strict';

    // Guard against double initialization
    if (window.__vh360ReactionsInitialized) {
        return;
    }
    window.__vh360ReactionsInitialized = true;

    const reactionButtons = document.querySelectorAll('.vh360-reaction-btn');

    if (!reactionButtons.length) {
        return;
    }

    // Handle reaction button clicks
    reactionButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            // Check if login is required
            if (btn.getAttribute('data-login-required') === 'true') {
                if (typeof window.vh360ShowLoginModal === 'function') {
                    window.vh360ShowLoginModal();
                } else if (typeof vh360Data !== 'undefined' && vh360Data.userLoginUrl) {
                    window.location.href = vh360Data.userLoginUrl;
                }
                return;
            }

            const videoId = btn.getAttribute('data-video-id');
            const reaction = btn.getAttribute('data-reaction');
            const isActive = btn.classList.contains('active');

            // If already active, clear reaction; otherwise, set it
            if (isActive) {
                clearReaction(videoId, btn);
            } else {
                setReaction(videoId, reaction, btn);
            }
        });
    });

    function setReaction(videoId, reaction, clickedBtn) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vh360_set_video_reaction');
        formData.append('video_id', videoId);
        formData.append('reaction', reaction);
        formData.append('nonce', vh360Data.videoReactionNonce);

        // Disable button during request
        clickedBtn.disabled = true;

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionUI(data.data.counts, data.data.userReaction);
            } else {
                window.vh360Warn('VideoHub360: Reaction failed:', data.data);
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Reaction error:', error);
        })
        .finally(() => {
            clickedBtn.disabled = false;
        });
    }

    function clearReaction(videoId, clickedBtn) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'vh360_clear_video_reaction');
        formData.append('video_id', videoId);
        formData.append('nonce', vh360Data.videoReactionNonce);

        // Disable button during request
        clickedBtn.disabled = true;

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionUI(data.data.counts, data.data.userReaction);
            } else {
                window.vh360Warn('VideoHub360: Clear reaction failed:', data.data);
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Clear reaction error:', error);
        })
        .finally(() => {
            clickedBtn.disabled = false;
        });
    }

    function updateReactionUI(counts, userReaction) {
        // Update like button
        const likeBtn = document.querySelector('.vh360-like-btn');
        if (likeBtn) {
            const likeCount = likeBtn.querySelector('.vh360-reaction-count');
            if (likeCount) {
                likeCount.textContent = counts.likes;
            }
            if (userReaction === 'like') {
                likeBtn.classList.add('active');
            } else {
                likeBtn.classList.remove('active');
            }
            likeBtn.setAttribute('aria-pressed', userReaction === 'like' ? 'true' : 'false');
        }

        // Update dislike button
        const dislikeBtn = document.querySelector('.vh360-dislike-btn');
        if (dislikeBtn) {
            const dislikeCount = dislikeBtn.querySelector('.vh360-reaction-count');
            if (dislikeCount) {
                dislikeCount.textContent = counts.dislikes;
            }
            if (userReaction === 'dislike') {
                dislikeBtn.classList.add('active');
            } else {
                dislikeBtn.classList.remove('active');
            }
            dislikeBtn.setAttribute('aria-pressed', userReaction === 'dislike' ? 'true' : 'false');
        }
    }

    window.vh360Log('VideoHub360: Reactions initialized');
})();

// == Save to Playlist Logic ==
(function() {
    'use strict';

    // Guard against double initialization
    if (window.__vh360PlaylistsInitialized) {
        return;
    }
    window.__vh360PlaylistsInitialized = true;

    const saveBtn = document.getElementById('vh360-save-btn');
    const modalOverlay = document.getElementById('vh360-playlist-modal-overlay');
    const modalClose = document.getElementById('vh360-playlist-modal-close');
    const playlistList = document.getElementById('vh360-playlist-list');
    const createToggle = document.getElementById('vh360-create-playlist-toggle');
    const createForm = document.getElementById('vh360-create-playlist-form');
    const cancelCreate = document.getElementById('vh360-cancel-create-playlist');
    const submitCreate = document.getElementById('vh360-submit-create-playlist');
    const playlistTitle = document.getElementById('vh360-new-playlist-title');

    if (!saveBtn || !modalOverlay) {
        return;
    }

    let currentVideoId = null;
    let userPlaylists = [];
    let playlistsWithVideo = [];

    // Open modal
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // Check if login is required
        if (saveBtn.getAttribute('data-login-required') === 'true') {
            if (typeof window.vh360ShowLoginModal === 'function') {
                window.vh360ShowLoginModal();
            } else if (typeof vh360Data !== 'undefined' && vh360Data.userLoginUrl) {
                window.location.href = vh360Data.userLoginUrl;
            }
            return;
        }

        currentVideoId = saveBtn.getAttribute('data-video-id');
        modalOverlay.style.display = 'flex';
        loadPlaylists();
    });

    // Close modal
    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
            createForm.style.display = 'none';
            playlistTitle.value = '';
        });
    }

    // Close on overlay click
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.style.display = 'none';
            createForm.style.display = 'none';
            playlistTitle.value = '';
        }
    });

    // Toggle create playlist form
    if (createToggle) {
        createToggle.addEventListener('click', function() {
            createForm.style.display = createForm.style.display === 'none' ? 'block' : 'none';
            if (createForm.style.display === 'block') {
                playlistTitle.focus();
            }
        });
    }

    // Cancel create playlist
    if (cancelCreate) {
        cancelCreate.addEventListener('click', function() {
            createForm.style.display = 'none';
            playlistTitle.value = '';
        });
    }

    // Submit create playlist
    if (submitCreate) {
        submitCreate.addEventListener('click', function() {
            createPlaylist();
        });
    }

    // Submit on Enter key
    if (playlistTitle) {
        playlistTitle.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                createPlaylist();
            }
        });
    }

    function loadPlaylists() {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        playlistList.innerHTML = '<p class="vh360-playlist-loading">Loading playlists...</p>';

        const formData = new FormData();
        formData.append('action', 'vh360_get_my_playlists');
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                userPlaylists = data.data.playlists || [];
                loadPlaylistsWithVideo();
            } else {
                playlistList.innerHTML = '<p class="vh360-playlist-error">Failed to load playlists.</p>';
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Load playlists error:', error);
            playlistList.innerHTML = '<p class="vh360-playlist-error">Failed to load playlists.</p>';
        });
    }

    function loadPlaylistsWithVideo() {
        const formData = new FormData();
        formData.append('action', 'vh360_get_playlists_with_video');
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistsWithVideo = data.data.playlist_ids || [];
                renderPlaylists();
            } else {
                renderPlaylists();
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Load playlists with video error:', error);
            renderPlaylists();
        });
    }

    function renderPlaylists() {
        if (userPlaylists.length === 0) {
            playlistList.innerHTML = '<p class="vh360-playlist-empty">You don\'t have any playlists yet. Create one below!</p>';
            return;
        }

        let html = '<div class="vh360-playlist-items">';
        userPlaylists.forEach(function(playlist) {
            const isChecked = playlistsWithVideo.includes(parseInt(playlist.id));
            html += '<label class="vh360-playlist-item">';
            html += '<input type="checkbox" class="vh360-playlist-checkbox" ';
            html += 'data-playlist-id="' + playlist.id + '" ';
            html += (isChecked ? 'checked' : '') + '>';
            html += '<span class="vh360-playlist-name">' + escapeHtml(playlist.title) + '</span>';
            html += '<span class="vh360-playlist-count">(' + playlist.video_count + ')</span>';
            html += '</label>';
        });
        html += '</div>';

        playlistList.innerHTML = html;

        // Add event listeners to checkboxes
        const checkboxes = playlistList.querySelectorAll('.vh360-playlist-checkbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const playlistId = checkbox.getAttribute('data-playlist-id');
                if (checkbox.checked) {
                    addToPlaylist(playlistId, checkbox);
                } else {
                    removeFromPlaylist(playlistId, checkbox);
                }
            });
        });
    }

    function createPlaylist() {
        const title = playlistTitle.value.trim();
        if (!title) {
            return;
        }

        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        submitCreate.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_create_playlist');
        formData.append('title', title);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistTitle.value = '';
                createForm.style.display = 'none';
                loadPlaylists();
            } else {
                window.vh360Warn('VideoHub360: Create playlist failed:', data.data);
            }
        })
        .catch(error => {
            window.vh360Warn('VideoHub360: Create playlist error:', error);
        })
        .finally(() => {
            submitCreate.disabled = false;
        });
    }

    function addToPlaylist(playlistId, checkbox) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        checkbox.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_add_to_playlist');
        formData.append('playlist_id', playlistId);
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                playlistsWithVideo.push(parseInt(playlistId));
            } else {
                checkbox.checked = false;
                window.vh360Warn('VideoHub360: Add to playlist failed:', data.data);
            }
        })
        .catch(error => {
            checkbox.checked = false;
            window.vh360Warn('VideoHub360: Add to playlist error:', error);
        })
        .finally(() => {
            checkbox.disabled = false;
        });
    }

    function removeFromPlaylist(playlistId, checkbox) {
        if (typeof vh360Data === 'undefined' || !vh360Data.ajaxUrl) {
            window.vh360Warn('VideoHub360: vh360Data not available');
            return;
        }

        checkbox.disabled = true;

        const formData = new FormData();
        formData.append('action', 'vh360_remove_from_playlist');
        formData.append('playlist_id', playlistId);
        formData.append('video_id', currentVideoId);
        formData.append('nonce', vh360Data.playlistNonce);

        fetch(vh360Data.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const index = playlistsWithVideo.indexOf(parseInt(playlistId));
                if (index > -1) {
                    playlistsWithVideo.splice(index, 1);
                }
            } else {
                checkbox.checked = true;
                window.vh360Warn('VideoHub360: Remove from playlist failed:', data.data);
            }
        })
        .catch(error => {
            checkbox.checked = true;
            window.vh360Warn('VideoHub360: Remove from playlist error:', error);
        })
        .finally(() => {
            checkbox.disabled = false;
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    window.vh360Log('VideoHub360: Playlists initialized');
})();
